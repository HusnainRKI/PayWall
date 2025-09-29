<?php
/**
 * Paywall Anywhere Helper Functions
 *
 * @package Paywall_Anywhere
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Check if user has access to specific content
 *
 * This is the main helper function for developers to check access.
 *
 * @param array $args Arguments: post_id, scope, selector, user_id, guest_email
 * @return bool
 */
function paywall_anywhere_is_unlocked( $args = array() ) {
    $defaults = array(
        'post_id' => get_the_ID(),
        'scope' => 'post',
        'selector' => '',
        'user_id' => get_current_user_id(),
        'guest_email' => null,
    );
    
    $args = wp_parse_args( $args, $defaults );
    
    if ( ! $args['post_id'] ) {
        return false;
    }
    
    // Get the plugin instance
    $plugin = Paywall_Anywhere\Plugin::instance();
    
    if ( ! $plugin || ! $plugin->access ) {
        return false;
    }
    
    // Find the item in the database
    $item = $plugin->db->get_item_by_criteria( 
        $args['post_id'], 
        $args['scope'], 
        $args['selector'] 
    );
    
    if ( ! $item ) {
        // No paywall item found, content is free
        return true;
    }
    
    // Check access through the access manager
    return $plugin->access->has_access( 
        $args['user_id'], 
        $item->id, 
        $args['guest_email'] 
    );
}

/**
 * Check if current user can access a specific item
 *
 * Wrapper function for the most common use case.
 *
 * @param array $args Access check arguments.
 * @return bool
 */
function paywall_anywhere_can_access_item( $args ) {
    /**
     * Filter access check results
     *
     * @param bool  $has_access Whether user has access.
     * @param array $args       Access check arguments.
     */
    return apply_filters( 'paywall_anywhere_can_access_item', paywall_anywhere_is_unlocked( $args ), $args );
}

/**
 * Get formatted price for display
 *
 * @param int    $price_minor Price in minor units (cents).
 * @param string $currency    Currency code.
 * @return string
 */
function paywall_anywhere_format_price( $price_minor, $currency = 'USD' ) {
    $symbols = array(
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'JPY' => '¥',
    );
    
    $symbol = $symbols[ $currency ] ?? '$';
    
    // Convert from minor units to major units
    $divisor = ( $currency === 'JPY' ) ? 1 : 100;
    $price = $price_minor / $divisor;
    
    return $symbol . number_format( $price, ( $currency === 'JPY' ) ? 0 : 2 );
}

/**
 * Generate unlock button HTML
 *
 * @param int   $item_id Item ID.
 * @param array $args    Button arguments.
 * @return string
 */
function paywall_anywhere_get_unlock_button_html( $item_id, $args = array() ) {
    $defaults = array(
        'text' => __( 'Unlock Content', 'paywall-anywhere' ),
        'class' => 'paywall-anywhere-unlock-btn',
        'style' => 'filled',
    );
    
    $args = wp_parse_args( $args, $defaults );
    
    $plugin = Paywall_Anywhere\Plugin::instance();
    $item = $plugin->db->get_item( $item_id );
    
    if ( ! $item ) {
        return '';
    }
    
    $price_display = paywall_anywhere_format_price( $item->price_minor, $item->currency );
    
    $html = sprintf(
        '<button class="%s paywall-anywhere-unlock-btn-%s" data-item-id="%d" data-price="%s">%s %s</button>',
        esc_attr( $args['class'] ),
        esc_attr( $args['style'] ),
        absint( $item_id ),
        esc_attr( $price_display ),
        esc_html( $args['text'] ),
        esc_html( $price_display )
    );
    
    /**
     * Filter unlock button HTML
     *
     * @param string $html    Button HTML.
     * @param int    $item_id Item ID.
     * @param array  $args    Button arguments.
     */
    return apply_filters( 'paywall_anywhere_unlock_button_html', $html, $item_id, $args );
}

/**
 * Get placeholder HTML for locked content
 *
 * @param array $args Placeholder arguments.
 * @return string
 */
function paywall_anywhere_get_placeholder_html( $args = array() ) {
    $defaults = array(
        'type' => 'content',
        'message' => __( 'This content is locked. Please unlock to continue reading.', 'paywall-anywhere' ),
        'show_unlock_button' => true,
        'item_id' => 0,
    );
    
    $args = wp_parse_args( $args, $defaults );
    
    $html = '<div class="paywall-anywhere-placeholder paywall-anywhere-placeholder-' . esc_attr( $args['type'] ) . '">';
    $html .= '<div class="paywall-anywhere-lock-icon">🔒</div>';
    $html .= '<p class="paywall-anywhere-message">' . esc_html( $args['message'] ) . '</p>';
    
    if ( $args['show_unlock_button'] && $args['item_id'] ) {
        $html .= paywall_anywhere_get_unlock_button_html( $args['item_id'] );
    }
    
    $html .= '</div>';
    
    /**
     * Filter placeholder HTML
     *
     * @param string $html Placeholder HTML.
     * @param array  $args Placeholder arguments.
     */
    return apply_filters( 'paywall_anywhere_placeholder_html', $html, $args );
}

/**
 * Log debug information (if WP_DEBUG is enabled)
 *
 * @param string $message Log message.
 * @param mixed  $data    Additional data to log.
 */
function paywall_anywhere_log( $message, $data = null ) {
    if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
        return;
    }
    
    $log_message = '[Paywall Anywhere] ' . $message;
    
    if ( $data !== null ) {
        $log_message .= ' | Data: ' . wp_json_encode( $data );
    }
    
    error_log( $log_message );
}

/**
 * Sanitize scope value
 *
 * @param string $scope Scope value.
 * @return string
 */
function paywall_anywhere_sanitize_scope( $scope ) {
    $allowed_scopes = array( 'post', 'block', 'paragraph', 'media', 'route_print' );
    return in_array( $scope, $allowed_scopes, true ) ? $scope : 'post';
}

/**
 * Sanitize currency code
 *
 * @param string $currency Currency code.
 * @return string
 */
function paywall_anywhere_sanitize_currency( $currency ) {
    $allowed_currencies = array( 'USD', 'EUR', 'GBP', 'JPY' );
    return in_array( $currency, $allowed_currencies, true ) ? $currency : 'USD';
}