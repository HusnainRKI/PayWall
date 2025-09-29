<?php
/**
 * Block Manager Class
 *
 * @package Paywall_Anywhere\Blocks
 */

namespace Paywall_Anywhere\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Block Manager Class
 */
class Block_Manager {
    
    /**
     * Register blocks
     */
    public function register_blocks() {
        // Register gate start block
        register_block_type( 'paywall-anywhere/gate-start', array(
            'render_callback' => array( $this, 'render_gate_start_block' ),
            'attributes' => array(
                'price' => array(
                    'type' => 'number',
                    'default' => 500,
                ),
                'currency' => array(
                    'type' => 'string',
                    'default' => 'USD',
                ),
                'expiresDays' => array(
                    'type' => 'number',
                    'default' => 30,
                ),
                'adFree' => array(
                    'type' => 'boolean',
                    'default' => false,
                ),
                'customMessage' => array(
                    'type' => 'string',
                    'default' => '',
                ),
            ),
        ) );
        
        // Register unlock CTA block
        register_block_type( 'paywall-anywhere/unlock-cta', array(
            'render_callback' => array( $this, 'render_unlock_cta_block' ),
            'attributes' => array(
                'itemId' => array(
                    'type' => 'number',
                    'default' => 0,
                ),
                'providers' => array(
                    'type' => 'array',
                    'default' => array( 'stripe', 'woocommerce' ),
                ),
                'style' => array(
                    'type' => 'string',
                    'default' => 'filled',
                ),
                'text' => array(
                    'type' => 'string',
                    'default' => '',
                ),
            ),
        ) );
    }
    
    /**
     * Render gate start block
     *
     * @param array $attributes Block attributes.
     * @return string
     */
    public function render_gate_start_block( $attributes ) {
        $post_id = get_the_ID();
        if ( ! $post_id ) {
            return '';
        }
        
        $price = absint( $attributes['price'] ?? 500 );
        $currency = \paywall_anywhere_sanitize_currency( $attributes['currency'] ?? 'USD' );
        $expires_days = absint( $attributes['expiresDays'] ?? 30 ) ?: null;
        
        // Create or find the item
        $db = \Paywall_Anywhere\Plugin::instance()->db;
        $item = $db->get_item_by_criteria( $post_id, 'post', '' );
        
        if ( ! $item ) {
            // Create the item
            $item_id = $db->create_item( array(
                'post_id' => $post_id,
                'scope' => 'post',
                'price_minor' => $price,
                'currency' => $currency,
                'expires_days' => $expires_days,
            ) );
            
            if ( $item_id ) {
                $item = $db->get_item( $item_id );
            }
        }
        
        if ( ! $item ) {
            return '<div class="paywall-anywhere-error">' . __( 'Error creating premium item', 'paywall-anywhere' ) . '</div>';
        }
        
        // Check if user has access
        $access_manager = \Paywall_Anywhere\Plugin::instance()->access;
        if ( $access_manager->has_access( get_current_user_id(), $item->id ) ) {
            // User has access, show nothing (content below will be visible)
            return '';
        }
        
        // Check if we should bypass (editor preview)
        if ( $access_manager->should_bypass_paywall() ) {
            return '<div class="paywall-anywhere-gate-preview">' . 
                   '<span class="paywall-anywhere-gate-icon">🚪</span>' .
                   '<span class="paywall-anywhere-gate-text">' . __( 'Paywall Gate (Preview)', 'paywall-anywhere' ) . '</span>' .
                   '</div>';
        }
        
        // User doesn't have access, show paywall
        $message = $attributes['customMessage'] ?? sprintf(
            __( 'This content is locked. Unlock for %s to continue reading.', 'paywall-anywhere' ),
            \paywall_anywhere_format_price( $item->price_minor, $item->currency )
        );
        
        return \paywall_anywhere_get_placeholder_html( array(
            'type' => 'gate',
            'message' => $message,
            'item_id' => $item->id,
        ) );
    }
    
    /**
     * Render unlock CTA block
     *
     * @param array $attributes Block attributes.
     * @return string
     */
    public function render_unlock_cta_block( $attributes ) {
        $item_id = absint( $attributes['itemId'] ?? 0 );
        
        if ( ! $item_id ) {
            return '<div class="paywall-anywhere-error">' . __( 'No item ID specified', 'paywall-anywhere' ) . '</div>';
        }
        
        $db = \Paywall_Anywhere\Plugin::instance()->db;
        $item = $db->get_item( $item_id );
        
        if ( ! $item ) {
            return '<div class="paywall-anywhere-error">' . __( 'Premium item not found', 'paywall-anywhere' ) . '</div>';
        }
        
        // Check if user has access
        $access_manager = \Paywall_Anywhere\Plugin::instance()->access;
        if ( $access_manager->has_access( get_current_user_id(), $item->id ) ) {
            // User has access, show success message
            return '<div class="paywall-anywhere-unlocked">' . 
                   '<span class="paywall-anywhere-unlocked-icon">✅</span>' .
                   '<span class="paywall-anywhere-unlocked-text">' . __( 'Content unlocked!', 'paywall-anywhere' ) . '</span>' .
                   '</div>';
        }
        
        $style = sanitize_text_field( $attributes['style'] ?? 'filled' );
        $text = sanitize_text_field( $attributes['text'] ?? '' );
        
        if ( empty( $text ) ) {
            $text = sprintf( __( 'Unlock for %s', 'paywall-anywhere' ), \paywall_anywhere_format_price( $item->price_minor, $item->currency ) );
        }
        
        return \paywall_anywhere_get_unlock_button_html( $item_id, array(
            'text' => $text,
            'style' => $style,
            'class' => 'paywall-anywhere-cta-button',
        ) );
    }
    
    /**
     * Register block editor assets
     */
    public function register_editor_assets() {
        wp_register_script(
            'paywall-anywhere-blocks',
            PAYWALL_ANYWHERE_PLUGIN_URL . 'assets/js/blocks.js',
            array( 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components' ),
            PAYWALL_ANYWHERE_VERSION,
            true
        );
        
        wp_register_style(
            'paywall-anywhere-blocks-editor',
            PAYWALL_ANYWHERE_PLUGIN_URL . 'assets/css/blocks-editor.css',
            array( 'wp-edit-blocks' ),
            PAYWALL_ANYWHERE_VERSION
        );
        
        wp_localize_script( 'paywall-anywhere-blocks', 'paywallAnywhereBlocks', array(
            'currencies' => \Paywall_Anywhere\Plugin::instance()->get_supported_currencies(),
            'defaultPrice' => \Paywall_Anywhere\Plugin::instance()->get_option( 'default_price', 500 ),
            'defaultCurrency' => \Paywall_Anywhere\Plugin::instance()->get_option( 'default_currency', 'USD' ),
            'defaultExpires' => \Paywall_Anywhere\Plugin::instance()->get_option( 'default_expires_days', 30 ),
        ) );
    }
    
    /**
     * Register frontend assets
     */
    public function register_frontend_assets() {
        wp_register_style(
            'paywall-anywhere-blocks',
            PAYWALL_ANYWHERE_PLUGIN_URL . 'assets/css/blocks.css',
            array(),
            PAYWALL_ANYWHERE_VERSION
        );
        
        // Enqueue on posts that have paywall blocks
        if ( has_block( 'paywall-anywhere/gate-start' ) || has_block( 'paywall-anywhere/unlock-cta' ) ) {
            wp_enqueue_style( 'paywall-anywhere-blocks' );
        }
    }
}