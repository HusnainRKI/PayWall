<?php
/**
 * Database Manager Class
 *
 * @package Paywall_Anywhere\Data
 */

namespace Paywall_Anywhere\Data;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Database Manager Class
 */
class Database_Manager {
    
    /**
     * Get premium items table name
     *
     * @return string
     */
    public function get_items_table() {
        global $wpdb;
        return $wpdb->prefix . 'paywall_anywhere_items';
    }
    
    /**
     * Get entitlements table name
     *
     * @return string
     */
    public function get_entitlements_table() {
        global $wpdb;
        return $wpdb->prefix . 'paywall_anywhere_entitlements';
    }
    
    /**
     * Create a premium item
     *
     * @param array $data Item data.
     * @return int|false Item ID on success, false on failure.
     */
    public function create_item( $data ) {
        global $wpdb;
        
        $defaults = array(
            'site_id' => get_current_blog_id(),
            'post_id' => 0,
            'scope' => 'post',
            'selector' => '',
            'price_minor' => 0,
            'currency' => 'USD',
            'expires_days' => null,
            'status' => 'active'
        );
        
        $data = wp_parse_args( $data, $defaults );
        
        // Sanitize data
        $data['site_id'] = absint( $data['site_id'] );
        $data['post_id'] = absint( $data['post_id'] );
        $data['scope'] = \paywall_anywhere_sanitize_scope( $data['scope'] );
        $data['selector'] = sanitize_text_field( $data['selector'] );
        $data['price_minor'] = absint( $data['price_minor'] );
        $data['currency'] = \paywall_anywhere_sanitize_currency( $data['currency'] );
        $data['expires_days'] = $data['expires_days'] ? absint( $data['expires_days'] ) : null;
        $data['status'] = in_array( $data['status'], array( 'active', 'archived' ), true ) ? $data['status'] : 'active';
        
        $result = $wpdb->insert(
            $this->get_items_table(),
            $data,
            array( '%d', '%d', '%s', '%s', '%d', '%s', '%d', '%s' )
        );
        
        if ( $result ) {
            return $wpdb->insert_id;
        }
        
        return false;
    }
    
    /**
     * Update a premium item
     *
     * @param int   $id   Item ID.
     * @param array $data Item data.
     * @return bool
     */
    public function update_item( $id, $data ) {
        global $wpdb;
        
        // Remove ID from data if present
        unset( $data['id'] );
        
        // Sanitize data
        if ( isset( $data['scope'] ) ) {
            $data['scope'] = \paywall_anywhere_sanitize_scope( $data['scope'] );
        }
        if ( isset( $data['currency'] ) ) {
            $data['currency'] = \paywall_anywhere_sanitize_currency( $data['currency'] );
        }
        if ( isset( $data['selector'] ) ) {
            $data['selector'] = sanitize_text_field( $data['selector'] );
        }
        if ( isset( $data['price_minor'] ) ) {
            $data['price_minor'] = absint( $data['price_minor'] );
        }
        if ( isset( $data['expires_days'] ) ) {
            $data['expires_days'] = $data['expires_days'] ? absint( $data['expires_days'] ) : null;
        }
        
        return $wpdb->update(
            $this->get_items_table(),
            $data,
            array( 'id' => absint( $id ) ),
            null, // Let WordPress determine format
            array( '%d' )
        );
    }
    
    /**
     * Get a premium item by ID
     *
     * @param int $id Item ID.
     * @return object|null
     */
    public function get_item( $id ) {
        global $wpdb;
        
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$this->get_items_table()} WHERE id = %d",
            $id
        ) );
    }
    
    /**
     * Get premium items by post ID
     *
     * @param int $post_id Post ID.
     * @return array
     */
    public function get_items_by_post( $post_id ) {
        global $wpdb;
        
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$this->get_items_table()} WHERE post_id = %d AND status = 'active'",
            $post_id
        ) );
    }
    
    /**
     * Get item by criteria
     *
     * @param int    $post_id  Post ID.
     * @param string $scope    Scope.
     * @param string $selector Selector.
     * @return object|null
     */
    public function get_item_by_criteria( $post_id, $scope, $selector = '' ) {
        global $wpdb;
        
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$this->get_items_table()} 
             WHERE post_id = %d AND scope = %s AND selector = %s AND status = 'active'",
            $post_id,
            $scope,
            $selector
        ) );
    }
    
    /**
     * Delete a premium item
     *
     * @param int $id Item ID.
     * @return bool
     */
    public function delete_item( $id ) {
        global $wpdb;
        
        return $wpdb->delete(
            $this->get_items_table(),
            array( 'id' => absint( $id ) ),
            array( '%d' )
        );
    }
    
    /**
     * Create an entitlement
     *
     * @param array $data Entitlement data.
     * @return int|false Entitlement ID on success, false on failure.
     */
    public function create_entitlement( $data ) {
        global $wpdb;
        
        $defaults = array(
            'user_id' => null,
            'guest_email' => null,
            'item_id' => 0,
            'expires_at' => null,
            'source' => 'manual',
            'token_hash' => null,
            'meta' => null,
        );
        
        $data = wp_parse_args( $data, $defaults );
        
        // Sanitize data
        $data['user_id'] = $data['user_id'] ? absint( $data['user_id'] ) : null;
        $data['guest_email'] = $data['guest_email'] ? sanitize_email( $data['guest_email'] ) : null;
        $data['item_id'] = absint( $data['item_id'] );
        $data['source'] = in_array( $data['source'], array( 'stripe', 'woocommerce', 'manual' ), true ) ? $data['source'] : 'manual';
        $data['token_hash'] = $data['token_hash'] ? sanitize_text_field( $data['token_hash'] ) : null;
        $data['meta'] = $data['meta'] ? wp_json_encode( $data['meta'] ) : null;
        
        $result = $wpdb->insert(
            $this->get_entitlements_table(),
            $data,
            array( '%d', '%s', '%d', '%s', '%s', '%s', '%s' )
        );
        
        if ( $result ) {
            return $wpdb->insert_id;
        }
        
        return false;
    }
    
    /**
     * Get user entitlements
     *
     * @param int $user_id User ID.
     * @return array
     */
    public function get_user_entitlements( $user_id ) {
        global $wpdb;
        
        $entitlements = $wpdb->get_results( $wpdb->prepare(
            "SELECT e.*, i.post_id, i.scope, i.selector 
             FROM {$this->get_entitlements_table()} e 
             JOIN {$this->get_items_table()} i ON e.item_id = i.id 
             WHERE e.user_id = %d 
             AND (e.expires_at IS NULL OR e.expires_at > NOW())",
            $user_id
        ) );
        
        foreach ( $entitlements as $entitlement ) {
            if ( $entitlement->meta ) {
                $entitlement->meta = json_decode( $entitlement->meta, true );
            }
        }
        
        return $entitlements;
    }
    
    /**
     * Get guest entitlements by email
     *
     * @param string $email Guest email.
     * @return array
     */
    public function get_guest_entitlements( $email ) {
        global $wpdb;
        
        $entitlements = $wpdb->get_results( $wpdb->prepare(
            "SELECT e.*, i.post_id, i.scope, i.selector 
             FROM {$this->get_entitlements_table()} e 
             JOIN {$this->get_items_table()} i ON e.item_id = i.id 
             WHERE e.guest_email = %s 
             AND (e.expires_at IS NULL OR e.expires_at > NOW())",
            sanitize_email( $email )
        ) );
        
        foreach ( $entitlements as $entitlement ) {
            if ( $entitlement->meta ) {
                $entitlement->meta = json_decode( $entitlement->meta, true );
            }
        }
        
        return $entitlements;
    }
    
    /**
     * Get entitlement by token hash
     *
     * @param string $token_hash Token hash.
     * @return object|null
     */
    public function get_entitlement_by_token( $token_hash ) {
        global $wpdb;
        
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$this->get_entitlements_table()} WHERE token_hash = %s",
            $token_hash
        ) );
    }
    
    /**
     * Revoke an entitlement
     *
     * @param int $id Entitlement ID.
     * @return bool
     */
    public function revoke_entitlement( $id ) {
        global $wpdb;
        
        return $wpdb->delete(
            $this->get_entitlements_table(),
            array( 'id' => absint( $id ) ),
            array( '%d' )
        );
    }
    
    /**
     * Clean up expired entitlements
     *
     * @return int Number of entitlements cleaned up.
     */
    public function cleanup_expired_entitlements() {
        global $wpdb;
        
        $result = $wpdb->query(
            "DELETE FROM {$this->get_entitlements_table()} 
             WHERE expires_at IS NOT NULL AND expires_at < NOW()"
        );
        
        return (int) $result;
    }
}