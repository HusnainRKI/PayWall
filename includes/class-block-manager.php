<?php
/**
 * Block Manager Class
 *
 * @package PaywallPremiumContent
 */

namespace Pc;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Block Manager Class
 */
class Block_Manager {
    
    /**
     * Register Gutenberg blocks
     */
    public function register_blocks() {
        // Register gate start block
        register_block_type( 'pc/gate-start', array(
            'render_callback' => array( $this, 'render_gate_start' ),
            'attributes' => array(
                'price' => array( 'type' => 'number', 'default' => 500 ),
                'currency' => array( 'type' => 'string', 'default' => 'USD' ),
                'expiresDays' => array( 'type' => 'number' ),
                'adFree' => array( 'type' => 'boolean', 'default' => false ),
            ),
        ));
        
        // Register unlock CTA block
        register_block_type( 'pc/unlock-cta', array(
            'render_callback' => array( $this, 'render_unlock_cta' ),
            'attributes' => array(
                'itemId' => array( 'type' => 'number' ),
                'style' => array( 'type' => 'string', 'default' => 'filled' ),
            ),
        ));
    }
    
    /**
     * Render gate start block
     */
    public function render_gate_start( $attributes ) {
        // This block marks the start of premium content
        return '<!-- pc:gate-start -->';
    }
    
    /**
     * Render unlock CTA block
     */
    public function render_unlock_cta( $attributes ) {
        if ( empty( $attributes['itemId'] ) ) {
            return '';
        }
        
        $item_id = $attributes['itemId'];
        $style = $attributes['style'] ?? 'filled';
        
        // Check if user has access
        $access_manager = Plugin::instance()->access;
        $user_id = get_current_user_id();
        $guest_email = $access_manager->get_guest_email();
        
        if ( $access_manager->has_access( $user_id, $item_id, $guest_email ) ) {
            return ''; // Don't show CTA if already unlocked
        }
        
        // Get item details
        $db = Plugin::instance()->db;
        $item = $db->get_item( $item_id );
        
        if ( ! $item ) {
            return '';
        }
        
        $price_display = $this->format_price( $item->price_minor, $item->currency );
        
        $class = 'pc-unlock-cta pc-unlock-cta--' . esc_attr( $style );
        
        $html = sprintf( 
            '<div class="%s" data-item-id="%d">',
            esc_attr( $class ),
            $item_id
        );
        
        $html .= sprintf(
            '<button class="pc-unlock-btn" data-item-id="%d">%s</button>',
            $item_id,
            sprintf( __( 'Unlock for %s', 'paywall-premium-content' ), $price_display )
        );
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Format price for display
     */
    private function format_price( $price_minor, $currency ) {
        $price = $price_minor / 100;
        
        $symbol = '$';
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
}