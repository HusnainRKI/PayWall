<?php
/**
 * Shortcodes Class
 *
 * @package Paywall_Anywhere
 */

namespace Paywall_Anywhere;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Shortcodes Class
 */
class Paywall_Anywhere_Shortcodes {
    
    /**
     * Initialize shortcodes
     */
    public function init() {
        add_shortcode( 'paywall_anywhere_unlock_button', array( $this, 'unlock_button_shortcode' ) );
        add_shortcode( 'paywall_anywhere_premium_content', array( $this, 'premium_content_shortcode' ) );
        add_shortcode( 'paywall_anywhere_teaser', array( $this, 'teaser_shortcode' ) );
        add_shortcode( 'paywall_anywhere_lock', array( $this, 'lock_shortcode' ) );
    }
    
    /**
     * Unlock button shortcode
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public function unlock_button_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'item' => '',
            'text' => __( 'Unlock Content', 'paywall-anywhere' ),
            'style' => 'filled',
            'class' => ''
        ), $atts, 'paywall_anywhere_unlock_button' );
        
        if ( empty( $atts['item'] ) ) {
            return '';
        }
        
        // Parse item format "scope:selector"
        $item_parts = explode( ':', $atts['item'], 2 );
        if ( count( $item_parts ) !== 2 ) {
            return '';
        }
        
        $scope = \paywall_anywhere_sanitize_scope( $item_parts[0] );
        $selector = sanitize_text_field( $item_parts[1] );
        
        // Get post ID
        $post_id = get_the_ID();
        if ( ! $post_id ) {
            return '';
        }
        
        // Find or create the item
        $db = Plugin::instance()->db;
        $item = $db->get_item_by_criteria( $post_id, $scope, $selector );
        
        if ( ! $item ) {
            // Create item with default settings
            $default_price = Plugin::instance()->get_option( 'default_price', 500 );
            $default_currency = Plugin::instance()->get_option( 'default_currency', 'USD' );
            $default_expires = Plugin::instance()->get_option( 'default_expires_days', 30 );
            
            $item_id = $db->create_item( array(
                'post_id' => $post_id,
                'scope' => $scope,
                'selector' => $selector,
                'price_minor' => $default_price,
                'currency' => $default_currency,
                'expires_days' => $default_expires,
            ) );
            
            if ( ! $item_id ) {
                return '';
            }
            
            $item = $db->get_item( $item_id );
        }
        
        if ( ! $item ) {
            return '';
        }
        
        // Check if user already has access
        $access_manager = Plugin::instance()->access;
        if ( $access_manager->has_access( get_current_user_id(), $item->id ) ) {
            return ''; // User has access, don't show unlock button
        }
        
        return \paywall_anywhere_get_unlock_button_html( $item->id, array(
            'text' => $atts['text'],
            'style' => $atts['style'],
            'class' => $atts['class'],
        ) );
    }
    
    /**
     * Premium content shortcode
     *
     * @param array  $atts    Shortcode attributes.
     * @param string $content Shortcode content.
     * @return string
     */
    public function premium_content_shortcode( $atts, $content = '' ) {
        $atts = shortcode_atts( array(
            'price' => '5.00',
            'currency' => 'USD',
            'expires' => '30',
            'teaser' => ''
        ), $atts, 'paywall_anywhere_premium_content' );
        
        if ( empty( $content ) ) {
            return '';
        }
        
        $post_id = get_the_ID();
        if ( ! $post_id ) {
            return do_shortcode( $content );
        }
        
        // Convert price to minor units
        $price_minor = (int) ( floatval( $atts['price'] ) * 100 );
        $currency = \paywall_anywhere_sanitize_currency( $atts['currency'] );
        $expires_days = absint( $atts['expires'] ) ?: null;
        
        // Create unique selector for this shortcode instance
        $selector = 'shortcode_' . md5( $content . $price_minor . $currency );
        
        // Find or create the item
        $db = Plugin::instance()->db;
        $item = $db->get_item_by_criteria( $post_id, 'block', $selector );
        
        if ( ! $item ) {
            $item_id = $db->create_item( array(
                'post_id' => $post_id,
                'scope' => 'block',
                'selector' => $selector,
                'price_minor' => $price_minor,
                'currency' => $currency,
                'expires_days' => $expires_days,
            ) );
            
            if ( $item_id ) {
                $item = $db->get_item( $item_id );
            }
        }
        
        if ( ! $item ) {
            return do_shortcode( $content );
        }
        
        // Check if user has access
        $access_manager = Plugin::instance()->access;
        if ( $access_manager->has_access( get_current_user_id(), $item->id ) ) {
            return '<div class="paywall-anywhere-premium-content">' . do_shortcode( $content ) . '</div>';
        }
        
        // User doesn't have access, show teaser and unlock button
        $teaser_html = '';
        if ( ! empty( $atts['teaser'] ) ) {
            $teaser_html = '<div class="paywall-anywhere-teaser">' . esc_html( $atts['teaser'] ) . '</div>';
        }
        
        $unlock_button = \paywall_anywhere_get_unlock_button_html( $item->id, array(
            'text' => sprintf( __( 'Unlock for %s', 'paywall-anywhere' ), \paywall_anywhere_format_price( $price_minor, $currency ) ),
        ) );
        
        return '<div class="paywall-anywhere-premium-content-locked">' . $teaser_html . $unlock_button . '</div>';
    }
    
    /**
     * Teaser shortcode
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public function teaser_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'post' => get_the_ID(),
            'length' => '150',
            'more' => __( 'Read more...', 'paywall-anywhere' )
        ), $atts, 'paywall_anywhere_teaser' );
        
        $post_id = absint( $atts['post'] );
        if ( ! $post_id ) {
            return '';
        }
        
        $post = get_post( $post_id );
        if ( ! $post ) {
            return '';
        }
        
        // Check if there are any premium items for this post
        $db = Plugin::instance()->db;
        $items = $db->get_items_by_post( $post_id );
        
        if ( empty( $items ) ) {
            // No premium content, return full content
            return apply_filters( 'the_content', $post->post_content );
        }
        
        // Get teaser content
        $content = wp_strip_all_tags( $post->post_content );
        $length = absint( $atts['length'] );
        $teaser = wp_trim_words( $content, $length );
        
        $more_link = '';
        if ( ! empty( $atts['more'] ) && $post_id !== get_the_ID() ) {
            $more_link = ' <a href="' . esc_url( get_permalink( $post_id ) ) . '">' . esc_html( $atts['more'] ) . '</a>';
        }
        
        /**
         * Filter teaser content
         *
         * @param string $teaser  Teaser content.
         * @param int    $post_id Post ID.
         * @param array  $atts    Shortcode attributes.
         */
        $teaser = apply_filters( 'paywall_anywhere_teaser_html', $teaser, $post_id, $atts );
        
        return '<div class="paywall-anywhere-teaser">' . $teaser . $more_link . '</div>';
    }
    
    /**
     * Lock shortcode - locks content until payment
     *
     * @param array  $atts    Shortcode attributes.
     * @param string $content Shortcode content.
     * @return string
     */
    public function lock_shortcode( $atts, $content = '' ) {
        $atts = shortcode_atts( array(
            'price' => '', // In cents, will use default if not specified
            'expires_days' => '', // Days until expiry, will use default if not specified
            'currency' => '', // Will use default if not specified
            'teaser' => '' // Optional teaser text to show instead of content
        ), $atts, 'paywall_anywhere_lock' );
        
        // If no content, return empty
        if ( empty( $content ) ) {
            return '';
        }
        
        $post_id = get_the_ID();
        if ( ! $post_id ) {
            // Outside of post context, return content as-is
            return do_shortcode( $content );
        }
        
        // Get default values from settings
        $default_price = get_option( 'paywall_anywhere_default_price', 500 );
        $default_currency = get_option( 'paywall_anywhere_default_currency', 'USD' );
        $default_expires = get_option( 'paywall_anywhere_default_expires_days', 30 );
        
        // Use provided values or defaults
        $price_minor = ! empty( $atts['price'] ) ? absint( $atts['price'] ) : $default_price;
        $currency = ! empty( $atts['currency'] ) ? sanitize_text_field( $atts['currency'] ) : $default_currency;
        $expires_days = ! empty( $atts['expires_days'] ) ? absint( $atts['expires_days'] ) : $default_expires;
        
        // Convert 0 expiry to null (never expires)
        if ( $expires_days === 0 ) {
            $expires_days = null;
        }
        
        // Create unique selector for this shortcode instance
        $selector = 'shortcode_lock_' . md5( serialize( $atts ) . $content );
        
        // Find or create the item
        $db = Plugin::instance()->db;
        $existing_items = $db->get_items_by_post( $post_id );
        $item = null;
        
        // Look for existing item with this selector
        foreach ( $existing_items as $existing_item ) {
            if ( $existing_item->selector === $selector ) {
                $item = $existing_item;
                break;
            }
        }
        
        // Create item if it doesn't exist
        if ( ! $item ) {
            $item_id = $db->create_item( array(
                'post_id' => $post_id,
                'scope' => 'block',
                'selector' => $selector,
                'price_minor' => $price_minor,
                'currency' => $currency,
                'expires_days' => $expires_days,
                'status' => 'active'
            ) );
            
            if ( $item_id ) {
                $item = $db->get_item( $item_id );
            }
        }
        
        if ( ! $item ) {
            // Fallback: return content if item creation failed
            return do_shortcode( $content );
        }
        
        // Check if current user has access
        $access_manager = Plugin::instance()->access;
        $user_id = get_current_user_id();
        
        // Also check for guest access via cookies/session
        $has_access = false;
        if ( $user_id ) {
            $has_access = $access_manager->has_access( $user_id, $item->id );
        } else {
            // Check guest access
            $has_access = $access_manager->has_guest_access( $item->id );
        }
        
        if ( $has_access ) {
            // User has access, return unlocked content
            return '<div class="paywall-anywhere-unlocked-content" data-item-id="' . esc_attr( $item->id ) . '">' . 
                   do_shortcode( $content ) . 
                   '</div>';
        }
        
        // User doesn't have access, show locked state
        $teaser_content = '';
        if ( ! empty( $atts['teaser'] ) ) {
            $teaser_content = '<div class="paywall-anywhere-shortcode-teaser">' . 
                             wp_kses_post( $atts['teaser'] ) . 
                             '</div>';
        }
        
        // Generate unlock button
        $unlock_button = $this->generate_unlock_button_html( $item );
        
        // Return locked content placeholder
        return '<div class="paywall-anywhere-locked-shortcode" data-item-id="' . esc_attr( $item->id ) . '">' .
               '<div class="paywall-anywhere-lock-overlay">' .
               '<div class="paywall-anywhere-lock-content">' .
               '<div class="paywall-anywhere-lock-icon">🔒</div>' .
               '<h4>' . __( 'Premium Content Locked', 'paywall-anywhere' ) . '</h4>' .
               '<p>' . sprintf( 
                   __( 'Unlock this content for %s', 'paywall-anywhere' ), 
                   $this->format_price( $item->price_minor, $item->currency )
               ) . '</p>' .
               $teaser_content .
               $unlock_button .
               '</div>' .
               '</div>' .
               '</div>';
    }
    
    /**
     * Generate unlock button HTML
     *
     * @param object $item Premium item object
     * @return string
     */
    private function generate_unlock_button_html( $item ) {
        $button_text = sprintf( 
            __( 'Unlock for %s', 'paywall-anywhere' ), 
            $this->format_price( $item->price_minor, $item->currency )
        );
        
        return '<button type="button" ' .
               'class="paywall-anywhere-unlock-btn button button-primary" ' .
               'data-item-id="' . esc_attr( $item->id ) . '">' .
               esc_html( $button_text ) .
               '</button>';
    }
    
    /**
     * Format price for display
     *
     * @param int    $price_minor Price in minor units (cents)
     * @param string $currency    Currency code
     * @return string
     */
    private function format_price( $price_minor, $currency ) {
        if ( function_exists( 'paywall_anywhere_format_price' ) ) {
            return paywall_anywhere_format_price( $price_minor, $currency );
        }
        
        // Fallback formatting
        $price = $price_minor / 100;
        $symbol = $currency === 'USD' ? '$' : $currency . ' ';
        return $symbol . number_format( $price, 2 );
    }
}