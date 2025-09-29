<?php
/**
 * Content Filter Class
 *
 * @package Paywall_Anywhere\Rendering
 */

namespace Paywall_Anywhere\Rendering;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Content Filter Class
 * 
 * Handles server-side content filtering to ensure locked content never leaks.
 */
class Content_Filter {
    
    /**
     * Initialize content filtering
     */
    public function init() {
        // Main content filtering
        add_filter( 'the_content', array( $this, 'filter_post_content' ), 10 );
        
        // Filter REST API responses
        add_filter( 'rest_prepare_post', array( $this, 'filter_rest_response' ), 10, 3 );
        
        // Filter RSS feeds
        add_filter( 'the_content_feed', array( $this, 'filter_feed_content' ) );
        add_filter( 'the_excerpt_rss', array( $this, 'filter_feed_content' ) );
        
        // Filter meta tags for SEO/social
        add_action( 'wp_head', array( $this, 'filter_meta_tags' ), 1 );
        
        // Filter oEmbed responses
        add_filter( 'oembed_response_data', array( $this, 'filter_oembed_content' ), 10, 4 );
        
        // Filter block rendering
        add_filter( 'render_block', array( $this, 'filter_block_render' ), 10, 2 );
    }
    
    /**
     * Filter post content on frontend
     *
     * @param string $content Post content.
     * @return string
     */
    public function filter_post_content( $content ) {
        global $post;
        
        if ( ! $post || \Paywall_Anywhere\Plugin::instance()->access->should_bypass_paywall() ) {
            return $content;
        }
        
        return $this->process_content( $content, $post->ID );
    }
    
    /**
     * Process content and apply paywall rules
     *
     * @param string $content Content to process.
     * @param int    $post_id Post ID.
     * @return string
     */
    public function process_content( $content, $post_id ) {
        $locked_map = \Paywall_Anywhere\Plugin::instance()->access->get_locked_map( $post_id );
        
        if ( empty( $locked_map ) ) {
            return $content;
        }
        
        // Parse blocks from content
        $blocks = parse_blocks( $content );
        $processed_content = '';
        $gate_triggered = false;
        
        foreach ( $blocks as $block ) {
            if ( $block['blockName'] === 'paywall-anywhere/gate-start' ) {
                $gate_triggered = true;
                $processed_content .= $this->render_gate_start_block( $block, $post_id );
                continue;
            }
            
            if ( $gate_triggered ) {
                // After gate, check if this block is explicitly marked as free
                if ( ! $this->is_block_marked_free( $block ) ) {
                    // Replace with placeholder or teaser
                    $processed_content .= $this->get_content_placeholder( $post_id, 'post' );
                    break;
                }
            }
            
            // Check for individual block locks
            $block_lock = $this->find_block_lock( $block, $locked_map );
            if ( $block_lock && ! $this->user_has_access_to_lock( $block_lock, $post_id ) ) {
                $processed_content .= $this->get_content_placeholder( $post_id, 'block', $block['attrs']['clientId'] ?? '' );
                continue;
            }
            
            // Process paragraph-level locks
            $block = $this->process_paragraph_locks( $block, $post_id, $locked_map );
            
            // Render the block
            $processed_content .= render_block( $block );
        }
        
        return $processed_content;
    }
    
    /**
     * Render gate start block
     *
     * @param array $block   Block data.
     * @param int   $post_id Post ID.
     * @return string
     */
    private function render_gate_start_block( $block, $post_id ) {
        $attrs = $block['attrs'] ?? array();
        $price = $attrs['price'] ?? 500;
        $currency = $attrs['currency'] ?? 'USD';
        
        // Create or find the item
        $db = \Paywall_Anywhere\Plugin::instance()->db;
        $item = $db->get_item_by_criteria( $post_id, 'post', '' );
        
        if ( ! $item ) {
            // Create the item
            $item_id = $db->create_item( array(
                'post_id' => $post_id,
                'scope' => 'post',
                'price_minor' => absint( $price ),
                'currency' => \paywall_anywhere_sanitize_currency( $currency ),
                'expires_days' => $attrs['expiresDays'] ?? null,
            ) );
        } else {
            $item_id = $item->id;
        }
        
        if ( $item_id ) {
            return \paywall_anywhere_get_unlock_button_html( $item_id, array(
                'text' => __( 'Continue Reading', 'paywall-anywhere' ),
                'class' => 'paywall-anywhere-gate-button',
            ) );
        }
        
        return '';
    }
    
    /**
     * Check if block is marked as free
     *
     * @param array $block Block data.
     * @return bool
     */
    private function is_block_marked_free( $block ) {
        return ! empty( $block['attrs']['paywallAnywhereFree'] );
    }
    
    /**
     * Find block lock in locked map
     *
     * @param array $block      Block data.
     * @param array $locked_map Locked content map.
     * @return array|null
     */
    private function find_block_lock( $block, $locked_map ) {
        if ( empty( $block['attrs']['clientId'] ) ) {
            return null;
        }
        
        $client_id = $block['attrs']['clientId'];
        
        foreach ( $locked_map as $item ) {
            if ( $item['scope'] === 'block' && $item['selector'] === $client_id ) {
                return $item;
            }
        }
        
        return null;
    }
    
    /**
     * Check if user has access to a specific lock
     *
     * @param array $lock    Lock data.
     * @param int   $post_id Post ID.
     * @return bool
     */
    private function user_has_access_to_lock( $lock, $post_id ) {
        $db = \Paywall_Anywhere\Plugin::instance()->db;
        $item = $db->get_item_by_criteria( $post_id, $lock['scope'], $lock['selector'] );
        
        if ( ! $item ) {
            return true; // No item found, allow access
        }
        
        $access_manager = \Paywall_Anywhere\Plugin::instance()->access;
        return $access_manager->has_access( get_current_user_id(), $item->id );
    }
    
    /**
     * Process paragraph-level locks
     *
     * @param array $block      Block data.
     * @param int   $post_id    Post ID.
     * @param array $locked_map Locked content map.
     * @return array
     */
    private function process_paragraph_locks( $block, $post_id, $locked_map ) {
        if ( empty( $block['attrs']['clientId'] ) || empty( $block['innerHTML'] ) ) {
            return $block;
        }
        
        $client_id = $block['attrs']['clientId'];
        $access_manager = \Paywall_Anywhere\Plugin::instance()->access;
        
        // Find locked paragraphs in this block
        $locked_paragraphs = array();
        foreach ( $locked_map as $item ) {
            if ( $item['scope'] === 'paragraph' && strpos( $item['selector'], $client_id ) === 0 ) {
                $paragraph_index = (int) str_replace( $client_id . ':', '', $item['selector'] );
                $locked_paragraphs[] = $paragraph_index;
            }
        }
        
        if ( empty( $locked_paragraphs ) ) {
            return $block;
        }
        
        // Split content by paragraphs and replace locked ones
        $content = $block['innerHTML'];
        $paragraphs = preg_split( '/(<p[^>]*>.*?<\/p>)/is', $content, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );
        
        $paragraph_count = 0;
        foreach ( $paragraphs as $key => $paragraph ) {
            if ( preg_match( '/^<p[^>]*>/', $paragraph ) ) {
                if ( in_array( $paragraph_count, $locked_paragraphs, true ) ) {
                    // Check if user has access
                    $item = \Paywall_Anywhere\Plugin::instance()->db->get_item_by_criteria( 
                        $post_id, 
                        'paragraph', 
                        $client_id . ':' . $paragraph_count 
                    );
                    
                    if ( $item && ! $access_manager->has_access( get_current_user_id(), $item->id ) ) {
                        $paragraphs[ $key ] = $this->get_content_placeholder( $post_id, 'paragraph', $client_id . ':' . $paragraph_count );
                    }
                }
                $paragraph_count++;
            }
        }
        
        $block['innerHTML'] = implode( '', $paragraphs );
        return $block;
    }
    
    /**
     * Get content placeholder
     *
     * @param int    $post_id  Post ID.
     * @param string $scope    Content scope.
     * @param string $selector Selector.
     * @return string
     */
    private function get_content_placeholder( $post_id, $scope, $selector = '' ) {
        $db = \Paywall_Anywhere\Plugin::instance()->db;
        $item = $db->get_item_by_criteria( $post_id, $scope, $selector );
        
        if ( ! $item ) {
            return '<div class="paywall-anywhere-placeholder">' . __( 'Premium content', 'paywall-anywhere' ) . '</div>';
        }
        
        return \paywall_anywhere_get_placeholder_html( array(
            'type' => $scope,
            'item_id' => $item->id,
            'message' => sprintf(
                __( 'Unlock this %s for %s', 'paywall-anywhere' ),
                $scope,
                \paywall_anywhere_format_price( $item->price_minor, $item->currency )
            ),
        ) );
    }
    
    /**
     * Filter REST API responses
     *
     * @param \WP_REST_Response $response Response object.
     * @param \WP_Post          $post     Post object.
     * @param \WP_REST_Request  $request  Request object.
     * @return \WP_REST_Response
     */
    public function filter_rest_response( $response, $post, $request ) {
        if ( \Paywall_Anywhere\Plugin::instance()->access->should_bypass_paywall() ) {
            return $response;
        }
        
        $data = $response->get_data();
        
        if ( isset( $data['content']['rendered'] ) ) {
            $data['content']['rendered'] = $this->process_content( $data['content']['rendered'], $post->ID );
        }
        
        if ( isset( $data['excerpt']['rendered'] ) ) {
            $data['excerpt']['rendered'] = $this->get_teaser_content( $post->ID );
        }
        
        $response->set_data( $data );
        return $response;
    }
    
    /**
     * Filter feed content
     *
     * @param string $content Content.
     * @return string
     */
    public function filter_feed_content( $content ) {
        global $post;
        
        if ( ! $post ) {
            return $content;
        }
        
        return $this->get_teaser_content( $post->ID );
    }
    
    /**
     * Get teaser content for a post
     *
     * @param int $post_id Post ID.
     * @return string
     */
    private function get_teaser_content( $post_id ) {
        $teaser = get_post_meta( $post_id, '_paywall_anywhere_teaser_html', true );
        
        if ( $teaser ) {
            return $teaser;
        }
        
        // Generate teaser from content
        $post = get_post( $post_id );
        if ( ! $post ) {
            return '';
        }
        
        $content = wp_strip_all_tags( $post->post_content );
        $teaser_length = \Paywall_Anywhere\Plugin::instance()->get_option( 'teaser_length', 150 );
        $teaser = wp_trim_words( $content, $teaser_length );
        
        /**
         * Filter teaser content
         *
         * @param string $teaser  Teaser content.
         * @param int    $post_id Post ID.
         */
        return apply_filters( 'paywall_anywhere_teaser_html', $teaser, $post_id );
    }
    
    /**
     * Filter meta tags for SEO/social
     */
    public function filter_meta_tags() {
        global $post;
        
        if ( ! $post || \Paywall_Anywhere\Plugin::instance()->access->should_bypass_paywall() ) {
            return;
        }
        
        $locked_map = \Paywall_Anywhere\Plugin::instance()->access->get_locked_map( $post->ID );
        
        if ( empty( $locked_map ) ) {
            return;
        }
        
        // Override OpenGraph description with teaser
        add_action( 'wp_head', function() use ( $post ) {
            $teaser_excerpt = wp_trim_words( wp_strip_all_tags( $this->get_teaser_content( $post->ID ) ), 20 );
            echo '<meta property="og:description" content="' . esc_attr( $teaser_excerpt ) . '">' . "\n";
        }, 6 );
    }
    
    /**
     * Filter oEmbed responses
     *
     * @param array    $data   oEmbed data.
     * @param \WP_Post $post   Post object.
     * @param int      $width  Width.
     * @param int      $height Height.
     * @return array
     */
    public function filter_oembed_content( $data, $post, $width, $height ) {
        $locked_map = \Paywall_Anywhere\Plugin::instance()->access->get_locked_map( $post->ID );
        
        if ( ! empty( $locked_map ) ) {
            $data['html'] = $this->get_teaser_content( $post->ID );
        }
        
        return $data;
    }
    
    /**
     * Filter individual block rendering
     *
     * @param string $block_content Block content.
     * @param array  $block         Block data.
     * @return string
     */
    public function filter_block_render( $block_content, $block ) {
        // Additional block-level filtering can be added here
        return $block_content;
    }
}