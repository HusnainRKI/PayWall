<?php
/**
 * REST API Controller Class
 *
 * @package PaywallPremiumContent
 */

namespace Pc;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * REST API Controller Class
 */
class Rest_Api_Controller {
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        register_rest_route( 'pc/v1', '/items', array(
            'methods' => 'GET',
            'callback' => array( $this, 'get_items' ),
            'permission_callback' => array( $this, 'check_permissions' ),
        ));
        
        register_rest_route( 'pc/v1', '/items', array(
            'methods' => 'POST',
            'callback' => array( $this, 'create_item' ),
            'permission_callback' => array( $this, 'check_permissions' ),
        ));
        
        register_rest_route( 'pc/v1', '/items/(?P<id>\d+)', array(
            'methods' => 'PUT',
            'callback' => array( $this, 'update_item' ),
            'permission_callback' => array( $this, 'check_permissions' ),
        ));
        
        register_rest_route( 'pc/v1', '/items/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array( $this, 'delete_item' ),
            'permission_callback' => array( $this, 'check_permissions' ),
        ));
        
        register_rest_route( 'pc/v1', '/entitlements', array(
            'methods' => 'GET',
            'callback' => array( $this, 'get_entitlements' ),
            'permission_callback' => array( $this, 'check_permissions' ),
        ));
    }
    
    /**
     * Check API permissions
     */
    public function check_permissions() {
        return current_user_can( 'edit_posts' );
    }
    
    /**
     * Get premium items
     */
    public function get_items( $request ) {
        $post_id = $request->get_param( 'post' );
        $db = Plugin::instance()->db;
        
        if ( $post_id ) {
            $items = $db->get_items_by_post( $post_id );
        } else {
            // Get all items (with pagination)
            global $wpdb;
            $items = $wpdb->get_results( "SELECT * FROM {$db->get_items_table()} ORDER BY created_at DESC LIMIT 50" );
        }
        
        return rest_ensure_response( $items );
    }
    
    /**
     * Create premium item
     */
    public function create_item( $request ) {
        $params = $request->get_json_params();
        
        $data = array(
            'post_id' => absint( $params['post_id'] ?? 0 ),
            'scope' => sanitize_text_field( $params['scope'] ?? 'post' ),
            'selector' => sanitize_textarea_field( $params['selector'] ?? '' ),
            'price_minor' => absint( $params['price_minor'] ?? 500 ),
            'currency' => sanitize_text_field( $params['currency'] ?? 'USD' ),
            'expires_days' => ! empty( $params['expires_days'] ) ? absint( $params['expires_days'] ) : null,
        );
        
        $db = Plugin::instance()->db;
        $item_id = $db->create_item( $data );
        
        if ( $item_id ) {
            // Update locked map
            $access_manager = Plugin::instance()->access;
            $access_manager->add_to_locked_map( $data['post_id'], array(
                'scope' => $data['scope'],
                'selector' => $data['selector'],
                'price_minor' => $data['price_minor'],
                'currency' => $data['currency'],
                'expires_days' => $data['expires_days'],
            ));
            
            return rest_ensure_response( array( 'id' => $item_id, 'success' => true ) );
        }
        
        return new \WP_Error( 'creation_failed', 'Failed to create item', array( 'status' => 500 ) );
    }
    
    /**
     * Update premium item
     */
    public function update_item( $request ) {
        $item_id = $request->get_param( 'id' );
        $params = $request->get_json_params();
        
        $data = array();
        $allowed_fields = array( 'scope', 'selector', 'price_minor', 'currency', 'expires_days', 'status' );
        
        foreach ( $allowed_fields as $field ) {
            if ( isset( $params[ $field ] ) ) {
                $data[ $field ] = $params[ $field ];
            }
        }
        
        $db = Plugin::instance()->db;
        $result = $db->update_item( $item_id, $data );
        
        if ( $result !== false ) {
            return rest_ensure_response( array( 'success' => true ) );
        }
        
        return new \WP_Error( 'update_failed', 'Failed to update item', array( 'status' => 500 ) );
    }
    
    /**
     * Delete premium item
     */
    public function delete_item( $request ) {
        $item_id = $request->get_param( 'id' );
        
        $db = Plugin::instance()->db;
        $item = $db->get_item( $item_id );
        
        if ( ! $item ) {
            return new \WP_Error( 'not_found', 'Item not found', array( 'status' => 404 ) );
        }
        
        $result = $db->delete_item( $item_id );
        
        if ( $result ) {
            // Remove from locked map
            $access_manager = Plugin::instance()->access;
            $access_manager->remove_from_locked_map( $item->post_id, $item->scope, $item->selector );
            
            return rest_ensure_response( array( 'success' => true ) );
        }
        
        return new \WP_Error( 'deletion_failed', 'Failed to delete item', array( 'status' => 500 ) );
    }
    
    /**
     * Get entitlements
     */
    public function get_entitlements( $request ) {
        $user_id = $request->get_param( 'user' );
        $email = $request->get_param( 'email' );
        
        global $wpdb;
        $db = Plugin::instance()->db;
        
        $query = "SELECT e.*, i.post_id, i.scope, i.selector, i.price_minor, i.currency 
                  FROM {$db->get_entitlements_table()} e 
                  JOIN {$db->get_items_table()} i ON e.item_id = i.id";
        
        $params = array();
        
        if ( $user_id ) {
            $query .= " WHERE e.user_id = %d";
            $params[] = $user_id;
        } elseif ( $email ) {
            $query .= " WHERE e.guest_email = %s";
            $params[] = $email;
        }
        
        $query .= " ORDER BY e.granted_at DESC LIMIT 100";
        
        if ( ! empty( $params ) ) {
            $entitlements = $wpdb->get_results( $wpdb->prepare( $query, $params ) );
        } else {
            $entitlements = $wpdb->get_results( $query );
        }
        
        foreach ( $entitlements as $entitlement ) {
            if ( $entitlement->meta ) {
                $entitlement->meta = json_decode( $entitlement->meta, true );
            }
        }
        
        return rest_ensure_response( $entitlements );
    }
}