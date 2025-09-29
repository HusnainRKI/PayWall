<?php
/**
 * Helper Functions
 *
 * @package PaywallPremiumContent
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Check if user has access to specific content
 *
 * @param array $args Arguments: post_id, scope, selector, user_id, guest_email
 * @return bool
 */
function pc_is_unlocked( $args = array() ) {
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
    
    $access_manager = Pc\Plugin::instance()->access;
    
    if ( ! $args['guest_email'] ) {
        $args['guest_email'] = $access_manager->get_guest_email();
    }
    
    return $access_manager->has_post_access( 
        $args['post_id'], 
        $args['scope'], 
        $args['selector'] 
    );
}

/**
 * Get premium items for a post
 *
 * @param int $post_id Post ID
 * @return array
 */
function pc_get_post_items( $post_id ) {
    if ( ! $post_id ) {
        return array();
    }
    
    $db = Pc\Plugin::instance()->db;
    return $db->get_items_by_post( $post_id );
}

/**
 * Get user entitlements
 *
 * @param int $user_id User ID
 * @return array
 */
function pc_get_user_entitlements( $user_id = null ) {
    if ( ! $user_id ) {
        $user_id = get_current_user_id();
    }
    
    if ( ! $user_id ) {
        return array();
    }
    
    $db = Pc\Plugin::instance()->db;
    return $db->get_user_entitlements( $user_id );
}

/**
 * Format price for display
 *
 * @param int $price_minor Price in minor units (cents)
 * @param string $currency Currency code
 * @return string
 */
function pc_format_price( $price_minor, $currency = 'USD' ) {
    $price = $price_minor / 100;
    
    $symbols = array(
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'JPY' => '¥',
        'CAD' => 'C$',
        'AUD' => 'A$',
    );
    
    $symbol = $symbols[ $currency ] ?? $currency . ' ';
    
    if ( $currency === 'JPY' ) {
        return $symbol . number_format( $price );
    }
    
    return $symbol . number_format( $price, 2 );
}

/**
 * Check if content should be cached
 *
 * @param int $post_id Post ID
 * @return bool
 */
function pc_should_cache_content( $post_id ) {
    $access_manager = Pc\Plugin::instance()->access;
    $locked_map = $access_manager->get_locked_map( $post_id );
    
    // Don't cache if there are locked items and user has access
    if ( ! empty( $locked_map ) ) {
        $user_id = get_current_user_id();
        $guest_email = $access_manager->get_guest_email();
        
        if ( $user_id || $guest_email ) {
            // Check if user has any access
            $db = Pc\Plugin::instance()->db;
            
            if ( $user_id ) {
                $entitlements = $db->get_user_entitlements( $user_id );
            } else {
                $entitlements = $db->get_guest_entitlements( $guest_email );
            }
            
            foreach ( $entitlements as $entitlement ) {
                if ( $entitlement->post_id == $post_id ) {
                    return false; // Don't cache for users with access
                }
            }
        }
    }
    
    return true;
}

/**
 * Get teaser content for a post
 *
 * @param int $post_id Post ID
 * @param int $paragraph_count Number of paragraphs to show
 * @return string
 */
function pc_get_teaser_content( $post_id, $paragraph_count = null ) {
    if ( ! $paragraph_count ) {
        $paragraph_count = Pc\Plugin::instance()->get_option( 'teaser_count', 2 );
    }
    
    $content = get_post_field( 'post_content', $post_id );
    $content = wp_strip_all_tags( $content );
    
    $paragraphs = array_filter( explode( "\n\n", $content ), 'trim' );
    $teaser_paragraphs = array_slice( $paragraphs, 0, $paragraph_count );
    
    $teaser = implode( "\n\n", $teaser_paragraphs );
    
    if ( count( $paragraphs ) > $paragraph_count ) {
        $teaser .= "\n\n" . __( '[Continue reading with premium access...]', 'paywall-premium-content' );
    }
    
    return $teaser;
}

/**
 * Generate unlock button HTML
 *
 * @param array $args Button arguments
 * @return string
 */
function pc_unlock_button( $args = array() ) {
    $defaults = array(
        'item_id' => 0,
        'post_id' => get_the_ID(),
        'scope' => 'post',
        'selector' => '',
        'text' => '',
        'class' => 'pc-unlock-btn',
        'style' => 'filled',
    );
    
    $args = wp_parse_args( $args, $defaults );
    
    if ( ! $args['text'] ) {
        if ( $args['item_id'] ) {
            $db = Pc\Plugin::instance()->db;
            $item = $db->get_item( $args['item_id'] );
            
            if ( $item ) {
                $price_display = pc_format_price( $item->price_minor, $item->currency );
                $args['text'] = sprintf( __( 'Unlock for %s', 'paywall-premium-content' ), $price_display );
            }
        }
        
        if ( ! $args['text'] ) {
            $args['text'] = __( 'Unlock Premium Content', 'paywall-premium-content' );
        }
    }
    
    $data_attrs = '';
    if ( $args['item_id'] ) {
        $data_attrs .= sprintf( ' data-item-id="%d"', $args['item_id'] );
    }
    if ( $args['post_id'] ) {
        $data_attrs .= sprintf( ' data-post-id="%d"', $args['post_id'] );
    }
    if ( $args['scope'] ) {
        $data_attrs .= sprintf( ' data-scope="%s"', esc_attr( $args['scope'] ) );
    }
    if ( $args['selector'] ) {
        $data_attrs .= sprintf( ' data-selector="%s"', esc_attr( $args['selector'] ) );
    }
    
    return sprintf(
        '<button class="%s"%s>%s</button>',
        esc_attr( $args['class'] ),
        $data_attrs,
        esc_html( $args['text'] )
    );
}

/**
 * Output unlock button
 *
 * @param array $args Button arguments
 */
function pc_the_unlock_button( $args = array() ) {
    echo pc_unlock_button( $args );
}

/**
 * Check if current user can manage premium content
 *
 * @return bool
 */
function pc_can_manage_premium_content() {
    return current_user_can( 'manage_options' ) || current_user_can( 'edit_posts' );
}

/**
 * Log security events
 *
 * @param string $event Event type
 * @param array $data Event data
 */
function pc_log_security_event( $event, $data = array() ) {
    if ( ! Pc\Plugin::instance()->get_option( 'security_logging', true ) ) {
        return;
    }
    
    $log_entry = array(
        'timestamp' => current_time( 'mysql' ),
        'event' => $event,
        'user_id' => get_current_user_id(),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'data' => $data,
    );
    
    $logs = get_option( 'pc_security_logs', array() );
    array_unshift( $logs, $log_entry );
    
    // Keep only last 1000 entries
    $logs = array_slice( $logs, 0, 1000 );
    
    update_option( 'pc_security_logs', $logs );
}