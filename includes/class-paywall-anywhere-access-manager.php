<?php
/**
 * Access Manager Class
 *
 * @package Paywall_Anywhere\Data
 */

namespace Paywall_Anywhere\Data;

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
     *
     * @param int         $user_id     User ID.
     * @param int         $item_id     Item ID.
     * @param string|null $guest_email Guest email.
     * @return bool
     */
    public function has_access( $user_id, $item_id, $guest_email = null ) {
        // Allow editors and admins to preview
        if ( $user_id && current_user_can( 'edit_posts' ) ) {
            return true;
        }
        
        $db = \Paywall_Anywhere\Plugin::instance()->db;
        
        // Check user entitlements
        if ( $user_id ) {
            $entitlements = $db->get_user_entitlements( $user_id );
            foreach ( $entitlements as $entitlement ) {
                if ( (int) $entitlement->item_id === (int) $item_id ) {
                    return true;
                }
            }
        }
        
        // Check guest entitlements
        if ( $guest_email ) {
            $entitlements = $db->get_guest_entitlements( $guest_email );
            foreach ( $entitlements as $entitlement ) {
                if ( (int) $entitlement->item_id === (int) $item_id ) {
                    return true;
                }
            }
        }
        
        // Check session-based access
        return $this->check_session_access( $item_id );
    }
    
    /**
     * Check session-based access
     *
     * @param int $item_id Item ID.
     * @return bool
     */
    private function check_session_access( $item_id ) {
        if ( ! session_id() ) {
            session_start();
        }
        
        $session_access = $_SESSION['paywall_anywhere_access'] ?? array();
        return in_array( (int) $item_id, array_map( 'intval', $session_access ), true );
    }
    
    /**
     * Check if paywall should be bypassed
     *
     * @return bool
     */
    public function should_bypass_paywall() {
        // Always allow in admin area
        if ( is_admin() ) {
            return true;
        }
        
        // Allow for users with edit capabilities
        if ( current_user_can( 'edit_posts' ) ) {
            return true;
        }
        
        // Allow in REST API for authenticated users with edit capabilities
        if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
            return current_user_can( 'edit_posts' );
        }
        
        // Allow in feed requests (but content will be filtered)
        if ( is_feed() ) {
            return false; // We want to filter feeds
        }
        
        return false;
    }
    
    /**
     * Get locked content map for a post
     *
     * @param int $post_id Post ID.
     * @return array
     */
    public function get_locked_map( $post_id ) {
        $locked_map = get_post_meta( $post_id, '_paywall_anywhere_locked_map', true );
        return is_array( $locked_map ) ? $locked_map : array();
    }
    
    /**
     * Update locked content map for a post
     *
     * @param int   $post_id    Post ID.
     * @param array $locked_map Locked content map.
     * @return bool
     */
    public function update_locked_map( $post_id, $locked_map ) {
        return update_post_meta( $post_id, '_paywall_anywhere_locked_map', $locked_map );
    }
    
    /**
     * Sync guest entitlements when user logs in
     *
     * @param string   $user_login Username.
     * @param \WP_User $user       User object.
     */
    public function sync_guest_entitlements( $user_login, $user ) {
        $guest_email = null;
        
        // Check session for guest email
        if ( session_id() && isset( $_SESSION['paywall_anywhere_guest_email'] ) ) {
            $guest_email = sanitize_email( $_SESSION['paywall_anywhere_guest_email'] );
        }
        
        // Check cookie for guest email
        if ( ! $guest_email && isset( $_COOKIE['paywall_anywhere_guest_email'] ) ) {
            $guest_email = sanitize_email( $_COOKIE['paywall_anywhere_guest_email'] );
        }
        
        if ( ! $guest_email || $guest_email !== $user->user_email ) {
            return;
        }
        
        $db = \Paywall_Anywhere\Plugin::instance()->db;
        $guest_entitlements = $db->get_guest_entitlements( $guest_email );
        
        if ( empty( $guest_entitlements ) ) {
            return;
        }
        
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
            unset( $_SESSION['paywall_anywhere_guest_email'] );
            unset( $_SESSION['paywall_anywhere_access'] );
        }
        
        setcookie( 'paywall_anywhere_guest_email', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN );
    }
    
    /**
     * Set guest access
     *
     * @param string $email    Guest email.
     * @param array  $item_ids Item IDs to grant access to.
     */
    public function set_guest_access( $email, $item_ids = array() ) {
        // Start session if not already started
        if ( ! session_id() ) {
            session_start();
        }
        
        $_SESSION['paywall_anywhere_guest_email'] = sanitize_email( $email );
        
        if ( ! empty( $item_ids ) ) {
            $_SESSION['paywall_anywhere_access'] = array_map( 'absint', $item_ids );
        }
        
        // Set cookie for longer persistence
        setcookie( 'paywall_anywhere_guest_email', $email, time() + WEEK_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN );
    }
    
    /**
     * Handle magic link access
     */
    public function handle_magic_link() {
        if ( ! isset( $_GET['paywall_anywhere_token'] ) ) {
            return;
        }
        
        $token = sanitize_text_field( $_GET['paywall_anywhere_token'] );
        $token_hash = hash( 'sha256', $token );
        
        $db = \Paywall_Anywhere\Plugin::instance()->db;
        $entitlement = $db->get_entitlement_by_token( $token_hash );
        
        if ( ! $entitlement ) {
            wp_die( __( 'Invalid or expired access token.', 'paywall-anywhere' ) );
        }
        
        // Check if token is expired (1 hour TTL by default)
        $ttl = \Paywall_Anywhere\Plugin::instance()->get_option( 'magic_link_ttl', 3600 );
        $created_time = strtotime( $entitlement->granted_at );
        
        if ( time() - $created_time > $ttl ) {
            wp_die( __( 'Access token has expired.', 'paywall-anywhere' ) );
        }
        
        // Grant access
        if ( $entitlement->guest_email ) {
            $this->set_guest_access( $entitlement->guest_email, array( $entitlement->item_id ) );
        }
        
        // Invalidate the token (one-time use)
        global $wpdb;
        $wpdb->update(
            $db->get_entitlements_table(),
            array( 'token_hash' => null ),
            array( 'id' => $entitlement->id ),
            array( '%s' ),
            array( '%d' )
        );
        
        // Redirect to the content
        $item = $db->get_item( $entitlement->item_id );
        if ( $item && $item->post_id ) {
            $redirect_url = get_permalink( $item->post_id );
            wp_safe_redirect( $redirect_url );
            exit;
        }
    }
    
    /**
     * Generate secure token for magic links
     *
     * @return string
     */
    public function generate_token() {
        return bin2hex( random_bytes( 32 ) );
    }
    
    /**
     * Hash token for database storage
     *
     * @param string $token Token to hash.
     * @return string
     */
    public function hash_token( $token ) {
        return hash( 'sha256', $token );
    }
}