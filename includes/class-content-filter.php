<?php
/**
 * Content Filter Class
 *
 * @package PaywallPremiumContent
 */

namespace Pc;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Content Filter Class
 */
class Content_Filter {
    
    /**
     * Initialize content filtering
     */
    public function init() {
        // Filter post content
        add_filter( 'the_content', array( $this, 'filter_post_content' ), 10 );
        
        // Filter REST API content
        add_filter( 'rest_prepare_post', array( $this, 'filter_rest_content' ), 10, 3 );
        
        // Filter RSS feeds
        add_filter( 'the_content_feed', array( $this, 'filter_feed_content' ) );
        add_filter( 'the_excerpt_rss', array( $this, 'filter_feed_excerpt' ) );
        
        // Filter meta descriptions and OpenGraph
        add_filter( 'wp_head', array( $this, 'filter_meta_content' ), 1 );
        
        // Filter oEmbed responses
        add_filter( 'oembed_response_data', array( $this, 'filter_oembed_content' ), 10, 4 );
        
        // Filter block rendering
        add_filter( 'render_block', array( $this, 'filter_block_render' ), 10, 2 );
    }
    
    /**
     * Filter post content on frontend
     */
    public function filter_post_content( $content ) {
        global $post;
        
        if ( ! $post || Plugin::instance()->access->should_bypass_paywall() ) {
            return $content;
        }
        
        return $this->process_content( $content, $post->ID );
    }
    
    /**
     * Process content and apply paywall rules
     */
    public function process_content( $content, $post_id ) {
        $locked_map = Plugin::instance()->access->get_locked_map( $post_id );
        
        if ( empty( $locked_map ) ) {
            return $content;
        }
        
        // Parse blocks from content
        $blocks = parse_blocks( $content );
        $processed_blocks = $this->process_blocks( $blocks, $post_id, $locked_map );
        
        return serialize_blocks( $processed_blocks );
    }
    
    /**
     * Process blocks recursively
     */
    private function process_blocks( $blocks, $post_id, $locked_map ) {
        $access_manager = Plugin::instance()->access;
        
        foreach ( $blocks as &$block ) {
            // Check if this block is locked
            $is_locked = $this->is_block_locked( $block, $locked_map );
            
            if ( $is_locked ) {
                $item_data = $this->get_locked_item_data( $block, $locked_map );
                
                if ( $item_data && ! $access_manager->has_post_access( $post_id, $item_data['scope'], $item_data['selector'] ) ) {
                    // Replace with placeholder
                    $block = $this->create_placeholder_block( $item_data, $post_id );
                    continue;
                }
            }
            
            // Process paragraph-level locks
            if ( $block['blockName'] === 'core/paragraph' ) {
                $block = $this->process_paragraph_locks( $block, $post_id, $locked_map );
            }
            
            // Process inner blocks recursively
            if ( ! empty( $block['innerBlocks'] ) ) {
                $block['innerBlocks'] = $this->process_blocks( $block['innerBlocks'], $post_id, $locked_map );
            }
        }
        
        return $blocks;
    }
    
    /**
     * Check if block is locked
     */
    private function is_block_locked( $block, $locked_map ) {
        if ( empty( $block['attrs']['clientId'] ) ) {
            return false;
        }
        
        $client_id = $block['attrs']['clientId'];
        
        foreach ( $locked_map as $item ) {
            if ( $item['scope'] === 'block' && $item['selector'] === $client_id ) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get locked item data for block
     */
    private function get_locked_item_data( $block, $locked_map ) {
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
     * Process paragraph-level locks
     */
    private function process_paragraph_locks( $block, $post_id, $locked_map ) {
        if ( empty( $block['attrs']['clientId'] ) || empty( $block['innerHTML'] ) ) {
            return $block;
        }
        
        $client_id = $block['attrs']['clientId'];
        $access_manager = Plugin::instance()->access;
        
        // Find locked paragraphs in this block
        $locked_paragraphs = array();
        foreach ( $locked_map as $item ) {
            if ( $item['scope'] === 'paragraph' && strpos( $item['selector'], $client_id ) === 0 ) {
                $paragraph_index = intval( str_replace( $client_id . ':', '', $item['selector'] ) );
                $locked_paragraphs[ $paragraph_index ] = $item;
            }
        }
        
        if ( empty( $locked_paragraphs ) ) {
            return $block;
        }
        
        // Split content into paragraphs
        $paragraphs = $this->split_into_paragraphs( $block['innerHTML'] );
        
        // Replace locked paragraphs with placeholders
        foreach ( $locked_paragraphs as $index => $item_data ) {
            if ( isset( $paragraphs[ $index ] ) && ! $access_manager->has_post_access( $post_id, $item_data['scope'], $item_data['selector'] ) ) {
                $paragraphs[ $index ] = $this->create_paragraph_placeholder( $item_data, $post_id );
            }
        }
        
        // Reconstruct block content
        $block['innerHTML'] = implode( "\n\n", $paragraphs );
        
        return $block;
    }
    
    /**
     * Split content into paragraphs
     */
    private function split_into_paragraphs( $content ) {
        // Remove paragraph tags and split by double line breaks
        $content = strip_tags( $content, '<p>' );
        $content = str_replace( array( '</p>', '<p>' ), array( '', '' ), $content );
        
        return array_filter( explode( "\n\n", $content ), 'trim' );
    }
    
    /**
     * Create placeholder block
     */
    private function create_placeholder_block( $item_data, $post_id ) {
        $placeholder_html = $this->get_placeholder_html( $item_data, $post_id );
        
        return array(
            'blockName' => 'pc/placeholder',
            'attrs' => array(
                'scope' => $item_data['scope'],
                'price' => $item_data['price_minor'],
                'currency' => $item_data['currency'],
            ),
            'innerBlocks' => array(),
            'innerHTML' => $placeholder_html,
            'innerContent' => array( $placeholder_html )
        );
    }
    
    /**
     * Create paragraph placeholder
     */
    private function create_paragraph_placeholder( $item_data, $post_id ) {
        return $this->get_placeholder_html( $item_data, $post_id, 'paragraph' );
    }
    
    /**
     * Get placeholder HTML
     */
    private function get_placeholder_html( $item_data, $post_id, $type = 'block' ) {
        $price_display = $this->format_price( $item_data['price_minor'], $item_data['currency'] );
        $expires_text = '';
        
        if ( ! empty( $item_data['expires_days'] ) ) {
            $expires_text = sprintf( 
                __( 'Access for %d days', 'paywall-premium-content' ), 
                $item_data['expires_days'] 
            );
        }
        
        $class = $type === 'paragraph' ? 'pc-paragraph-placeholder' : 'pc-block-placeholder';
        
        $html = sprintf( 
            '<div class="pc-placeholder %s" data-scope="%s" data-selector="%s">',
            esc_attr( $class ),
            esc_attr( $item_data['scope'] ),
            esc_attr( $item_data['selector'] )
        );
        
        $html .= '<div class="pc-placeholder-content">';
        $html .= '<div class="pc-placeholder-icon">🔒</div>';
        $html .= '<div class="pc-placeholder-text">';
        $html .= '<h4>' . __( 'Premium Content', 'paywall-premium-content' ) . '</h4>';
        $html .= '<p>' . __( 'This content is available to premium subscribers.', 'paywall-premium-content' ) . '</p>';
        
        if ( $expires_text ) {
            $html .= '<p class="pc-expires">' . esc_html( $expires_text ) . '</p>';
        }
        
        $html .= '</div>';
        $html .= '<div class="pc-placeholder-cta">';
        $html .= sprintf( 
            '<button class="pc-unlock-btn" data-post-id="%d" data-scope="%s" data-selector="%s">%s</button>',
            $post_id,
            esc_attr( $item_data['scope'] ),
            esc_attr( $item_data['selector'] ),
            sprintf( __( 'Unlock for %s', 'paywall-premium-content' ), $price_display )
        );
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        
        return apply_filters( 'pc_placeholder_html', $html, $item_data, $post_id );
    }
    
    /**
     * Format price for display
     */
    private function format_price( $price_minor, $currency ) {
        $price = $price_minor / 100;
        
        $symbol = '$'; // Default to USD
        $symbols = array(
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
        );
        
        if ( isset( $symbols[ $currency ] ) ) {
            $symbol = $symbols[ $currency ];
        }
        
        return $symbol . number_format( $price, 2 );
    }
    
    /**
     * Filter REST API content
     */
    public function filter_rest_content( $response, $post, $request ) {
        if ( ! current_user_can( 'edit_posts' ) ) {
            $filtered_content = $this->process_content( $post->post_content, $post->ID );
            $response->data['content']['rendered'] = $filtered_content;
            
            // Also filter excerpt
            $response->data['excerpt']['rendered'] = $this->get_teaser_excerpt( $post->ID );
        }
        
        return $response;
    }
    
    /**
     * Filter feed content
     */
    public function filter_feed_content( $content ) {
        global $post;
        
        if ( ! $post ) {
            return $content;
        }
        
        return $this->get_teaser_content( $post->ID );
    }
    
    /**
     * Filter feed excerpt
     */
    public function filter_feed_excerpt( $excerpt ) {
        global $post;
        
        if ( ! $post ) {
            return $excerpt;
        }
        
        return $this->get_teaser_excerpt( $post->ID );
    }
    
    /**
     * Get teaser content for feeds
     */
    private function get_teaser_content( $post_id ) {
        $teaser_html = get_post_meta( $post_id, '_pc_teaser_html', true );
        
        if ( $teaser_html ) {
            return $teaser_html;
        }
        
        // Generate teaser from content
        $content = get_post_field( 'post_content', $post_id );
        $teaser_count = Plugin::instance()->get_option( 'teaser_count', 2 );
        
        $paragraphs = $this->split_into_paragraphs( strip_tags( $content ) );
        $teaser_paragraphs = array_slice( $paragraphs, 0, $teaser_count );
        
        return implode( "\n\n", $teaser_paragraphs ) . "\n\n" . __( '[Premium content continues...]', 'paywall-premium-content' );
    }
    
    /**
     * Get teaser excerpt
     */
    private function get_teaser_excerpt( $post_id ) {
        $teaser = $this->get_teaser_content( $post_id );
        return wp_trim_words( strip_tags( $teaser ), 55 );
    }
    
    /**
     * Filter meta content in head
     */
    public function filter_meta_content() {
        if ( ! is_singular() ) {
            return;
        }
        
        global $post;
        $locked_map = Plugin::instance()->access->get_locked_map( $post->ID );
        
        if ( empty( $locked_map ) ) {
            return;
        }
        
        // Filter meta description
        add_filter( 'wp_head', function() use ( $post ) {
            $teaser_excerpt = $this->get_teaser_excerpt( $post->ID );
            echo '<meta name="description" content="' . esc_attr( $teaser_excerpt ) . '">' . "\n";
        }, 5 );
        
        // Filter OpenGraph
        add_filter( 'wp_head', function() use ( $post ) {
            $teaser_excerpt = $this->get_teaser_excerpt( $post->ID );
            echo '<meta property="og:description" content="' . esc_attr( $teaser_excerpt ) . '">' . "\n";
        }, 6 );
    }
    
    /**
     * Filter oEmbed responses
     */
    public function filter_oembed_content( $data, $post, $width, $height ) {
        $locked_map = Plugin::instance()->access->get_locked_map( $post->ID );
        
        if ( ! empty( $locked_map ) ) {
            $data['html'] = $this->get_teaser_content( $post->ID );
        }
        
        return $data;
    }
    
    /**
     * Filter individual block rendering
     */
    public function filter_block_render( $block_content, $block ) {
        // This is called for each block, allowing fine-grained control
        return $block_content;
    }
}