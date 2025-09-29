<?php
/**
 * Access Manager Class
 *
 * @package PaywallPremiumContent
 */

namespace Pc;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Access Manager Class
 */
class Access_Manager {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'init', array( $this, 'handle_magic_link' ) );
        add_action( 'wp_login', array( $this, 'sync_guest_entitlements' ), 10, 2 );
    }
    
    /**
     * Check if user has access to a specific item
     */
    public function has_access( $user_id, $item_id, $guest_email = null ) {
        // Allow editors and admins to preview
        if ( $user_id && current_user_can( 'edit_posts' ) ) {
            return true;
        }
        
        $db = Plugin::instance()->db;
        
        // Check user entitlements
        if ( $user_id ) {
            $entitlements = $db->get_user_entitlements( $user_id );
            foreach ( $entitlements as $entitlement ) {
                if ( $entitlement->item_id == $item_id ) {
                    return true;
                }
            }
        }
        
        // Check guest entitlements
        if ( $guest_email ) {
            $entitlements = $db->get_guest_entitlements( $guest_email );
            foreach ( $entitlements as $entitlement ) {
                if ( $entitlement->item_id == $item_id ) {
                    return true;
                }
            }
        }
        
        // Check session access via magic link
        if ( isset( $_SESSION['pc_access'] ) && is_array( $_SESSION['pc_access'] ) ) {
            return in_array( (int) $item_id, $_SESSION['pc_access'] );
        }
        
        return false;
    }
    
    /**
     * Check if user has access to post content
     */
    public function has_post_access( $post_id, $scope = 'post', $selector = '' ) {
        $user_id = get_current_user_id();
        $guest_email = $this->get_guest_email();
        
        // Get items for this post
        $db = Plugin::instance()->db;
        $items = $db->get_items_by_post( $post_id );
        
        foreach ( $items as $item ) {
            if ( $item->scope === $scope && $item->selector === $selector ) {
                return $this->has_access( $user_id, $item->id, $guest_email );
            }
        }
        
        return false;
    }
    
    /**
     * Get guest email from session or cookie
     */
    public function get_guest_email() {
        // Start session if not already started
        if ( ! session_id() ) {
            session_start();
        }
        
        // Check session first
        if ( isset( $_SESSION['pc_guest_email'] ) ) {
            return sanitize_email( $_SESSION['pc_guest_email'] );
        }
        
        // Check cookie
        if ( isset( $_COOKIE['pc_guest_email'] ) ) {
            return sanitize_email( $_COOKIE['pc_guest_email'] );
        }
        
        return null;
    }
    
    /**
     * Set guest access
     */
    public function set_guest_access( $email, $item_ids = array() ) {
        // Start session if not already started
        if ( ! session_id() ) {
            session_start();
        }
        
        $_SESSION['pc_guest_email'] = sanitize_email( $email );
        
        if ( ! empty( $item_ids ) ) {
            $_SESSION['pc_access'] = array_map( 'absint', $item_ids );
        }
        
        // Set cookie for longer persistence
        setcookie( 'pc_guest_email', $email, time() + WEEK_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN );
    }
    
    /**
     * Handle magic link access
     */
    public function handle_magic_link() {
        if ( ! isset( $_GET['pc_token'] ) ) {
            return;
        }
        
        $token = sanitize_text_field( $_GET['pc_token'] );
        $token_hash = hash( 'sha256', $token );
        
        $db = Plugin::instance()->db;
        $entitlement = $db->get_entitlement_by_token( $token_hash );
        
        if ( ! $entitlement ) {
            wp_die( __( 'Invalid or expired access token.', 'paywall-premium-content' ) );
        }
        
        // Grant access
        if ( $entitlement->guest_email ) {
            $this->set_guest_access( $entitlement->guest_email, array( $entitlement->item_id ) );
        }
        
        // Redirect to the post
        $redirect_url = get_permalink( $entitlement->post_id );
        if ( $redirect_url ) {
            wp_safe_redirect( $redirect_url );
            exit;
        }
    }
    
    /**
     * Sync guest entitlements when user logs in
     */
    public function sync_guest_entitlements( $user_login, $user ) {
        $guest_email = $this->get_guest_email();
        
        if ( ! $guest_email || $guest_email !== $user->user_email ) {
            return;
        }
        
        $db = Plugin::instance()->db;
        
        // Get guest entitlements
        $guest_entitlements = $db->get_guest_entitlements( $guest_email );
        
        // Transfer to user account
        global $wpdb;
        foreach ( $guest_entitlements as $entitlement ) {
            $wpdb->update(
                $db->get_entitlements_table(),
                array( 
                    'user_id' => $user->ID,
                    'guest_email' => null 
                ),
                array( 'id' => $entitlement->id ),
                array( '%d', '%s' ),
                array( '%d' )
            );
        }
        
        // Clear guest session
        if ( session_id() ) {
            unset( $_SESSION['pc_guest_email'] );
            unset( $_SESSION['pc_access'] );
        }
        
        setcookie( 'pc_guest_email', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN );
    }
    
    /**
     * Generate secure token for magic links
     */
    public function generate_token() {
        return bin2hex( random_bytes( 32 ) );
    }
    
    /**
     * Hash token for database storage
     */
    public function hash_token( $token ) {
        return hash( 'sha256', $token );
    }
    
    /**
     * Check if current request should bypass paywall
     */
    public function should_bypass_paywall() {
        // Bypass for admin areas
        if ( is_admin() ) {
            return true;
        }
        
        // Bypass for REST API requests from editors
        if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
            if ( current_user_can( 'edit_posts' ) ) {
                return true;
            }
        }
        
        // Bypass for feed requests
        if ( is_feed() ) {
            return false; // Feeds should show teasers only
        }
        
        // Bypass for preview requests by editors
        if ( is_preview() && current_user_can( 'edit_posts' ) ) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Get locked content map for post
     */
    public function get_locked_map( $post_id ) {
        $locked_map = get_post_meta( $post_id, '_pc_locked_map', true );
        
        if ( empty( $locked_map ) || ! is_array( $locked_map ) ) {
            return array();
        }
        
        return $locked_map;
    }
    
    /**
     * Update locked content map for post
     */
    public function update_locked_map( $post_id, $locked_map ) {
        return update_post_meta( $post_id, '_pc_locked_map', $locked_map );
    }
    
    /**
     * Add item to locked map
     */
    public function add_to_locked_map( $post_id, $item_data ) {
        $locked_map = $this->get_locked_map( $post_id );
        $locked_map[] = $item_data;
        
        return $this->update_locked_map( $post_id, $locked_map );
    }
    
    /**
     * Remove item from locked map
     */
    public function remove_from_locked_map( $post_id, $scope, $selector ) {
        $locked_map = $this->get_locked_map( $post_id );
        
        $locked_map = array_filter( $locked_map, function( $item ) use ( $scope, $selector ) {
            return ! ( $item['scope'] === $scope && $item['selector'] === $selector );
        });
        
        return $this->update_locked_map( $post_id, array_values( $locked_map ) );
    }
}