<?php
/**
 * Database Manager Class
 *
 * @package PaywallPremiumContent
 */

namespace Pc;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Database Manager Class
 */
class Database_Manager {
    
    /**
     * Get premium items table name
     */
    public function get_items_table() {
        global $wpdb;
        return $wpdb->prefix . 'premium_items';
    }
    
    /**
     * Get entitlements table name
     */
    public function get_entitlements_table() {
        global $wpdb;
        return $wpdb->prefix . 'premium_entitlements';
    }
    
    /**
     * Create a premium item
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
        
        $result = $wpdb->insert(
            $this->get_items_table(),
            array(
                'site_id' => absint( $data['site_id'] ),
                'post_id' => absint( $data['post_id'] ),
                'scope' => sanitize_text_field( $data['scope'] ),
                'selector' => sanitize_textarea_field( $data['selector'] ),
                'price_minor' => absint( $data['price_minor'] ),
                'currency' => sanitize_text_field( $data['currency'] ),
                'expires_days' => $data['expires_days'] ? absint( $data['expires_days'] ) : null,
                'status' => sanitize_text_field( $data['status'] )
            ),
            array( '%d', '%d', '%s', '%s', '%d', '%s', '%d', '%s' )
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Update a premium item
     */
    public function update_item( $id, $data ) {
        global $wpdb;
        
        $allowed_fields = array(
            'scope', 'selector', 'price_minor', 'currency', 
            'expires_days', 'status'
        );
        
        $update_data = array();
        $formats = array();
        
        foreach ( $data as $key => $value ) {
            if ( in_array( $key, $allowed_fields ) ) {
                $update_data[ $key ] = $value;
                
                if ( in_array( $key, array( 'price_minor', 'expires_days' ) ) ) {
                    $formats[] = '%d';
                } else {
                    $formats[] = '%s';
                }
            }
        }
        
        if ( empty( $update_data ) ) {
            return false;
        }
        
        return $wpdb->update(
            $this->get_items_table(),
            $update_data,
            array( 'id' => absint( $id ) ),
            $formats,
            array( '%d' )
        );
    }
    
    /**
     * Get premium item by ID
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
     */
    public function get_items_by_post( $post_id ) {
        global $wpdb;
        
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$this->get_items_table()} WHERE post_id = %d AND status = 'active'",
            $post_id
        ) );
    }
    
    /**
     * Delete a premium item
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
            'meta' => null
        );
        
        $data = wp_parse_args( $data, $defaults );
        
        // Serialize meta if it's an array
        if ( is_array( $data['meta'] ) ) {
            $data['meta'] = wp_json_encode( $data['meta'] );
        }
        
        $result = $wpdb->insert(
            $this->get_entitlements_table(),
            array(
                'user_id' => $data['user_id'] ? absint( $data['user_id'] ) : null,
                'guest_email' => $data['guest_email'] ? sanitize_email( $data['guest_email'] ) : null,
                'item_id' => absint( $data['item_id'] ),
                'expires_at' => $data['expires_at'],
                'source' => sanitize_text_field( $data['source'] ),
                'token_hash' => $data['token_hash'] ? sanitize_text_field( $data['token_hash'] ) : null,
                'meta' => $data['meta']
            ),
            array( '%d', '%s', '%d', '%s', '%s', '%s', '%s' )
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Get entitlement by ID
     */
    public function get_entitlement( $id ) {
        global $wpdb;
        
        $entitlement = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$this->get_entitlements_table()} WHERE id = %d",
            $id
        ) );
        
        if ( $entitlement && $entitlement->meta ) {
            $entitlement->meta = json_decode( $entitlement->meta, true );
        }
        
        return $entitlement;
    }
    
    /**
     * Get user entitlements
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
     */
    public function get_guest_entitlements( $email ) {
        global $wpdb;
        
        $entitlements = $wpdb->get_results( $wpdb->prepare(
            "SELECT e.*, i.post_id, i.scope, i.selector 
             FROM {$this->get_entitlements_table()} e 
             JOIN {$this->get_items_table()} i ON e.item_id = i.id 
             WHERE e.guest_email = %s 
             AND (e.expires_at IS NULL OR e.expires_at > NOW())",
            $email
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
     */
    public function get_entitlement_by_token( $token_hash ) {
        global $wpdb;
        
        $entitlement = $wpdb->get_row( $wpdb->prepare(
            "SELECT e.*, i.post_id, i.scope, i.selector 
             FROM {$this->get_entitlements_table()} e 
             JOIN {$this->get_items_table()} i ON e.item_id = i.id 
             WHERE e.token_hash = %s 
             AND (e.expires_at IS NULL OR e.expires_at > NOW())",
            $token_hash
        ) );
        
        if ( $entitlement && $entitlement->meta ) {
            $entitlement->meta = json_decode( $entitlement->meta, true );
        }
        
        return $entitlement;
    }
    
    /**
     * Delete expired entitlements
     */
    public function cleanup_expired_entitlements() {
        global $wpdb;
        
        return $wpdb->query(
            "DELETE FROM {$this->get_entitlements_table()} 
             WHERE expires_at IS NOT NULL AND expires_at < NOW()"
        );
    }
    
    /**
     * Delete expired tokens
     */
    public function cleanup_expired_tokens() {
        global $wpdb;
        
        $ttl_hours = Plugin::instance()->get_option( 'magic_link_ttl', 24 );
        
        return $wpdb->query( $wpdb->prepare(
            "UPDATE {$this->get_entitlements_table()} 
             SET token_hash = NULL 
             WHERE token_hash IS NOT NULL 
             AND granted_at < DATE_SUB(NOW(), INTERVAL %d HOUR)",
            $ttl_hours
        ) );
    }
}