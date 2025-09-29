<?php
/**
 * REST API Controller Class
 *
 * @package Paywall_Anywhere\Rest
 */

namespace Paywall_Anywhere\Rest;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * REST API Controller Class
 */
class Api_Controller {
    
    /**
     * Namespace for the REST API
     */
    const NAMESPACE = 'paywall-anywhere/v1';
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        // Items endpoints
        register_rest_route( self::NAMESPACE, '/items', array(
            array(
                'methods' => \WP_REST_Server::READABLE,
                'callback' => array( $this, 'get_items' ),
                'permission_callback' => array( $this, 'check_permissions' ),
                'args' => array(
                    'post' => array(
                        'description' => __( 'Filter by post ID', 'paywall-anywhere' ),
                        'type' => 'integer',
                        'sanitize_callback' => 'absint',
                    ),
                    'scope' => array(
                        'description' => __( 'Filter by scope', 'paywall-anywhere' ),
                        'type' => 'string',
                        'enum' => array( 'post', 'block', 'paragraph', 'media', 'route_print' ),
                    ),
                    'status' => array(
                        'description' => __( 'Filter by status', 'paywall-anywhere' ),
                        'type' => 'string',
                        'enum' => array( 'active', 'archived' ),
                        'default' => 'active',
                    ),
                ),
            ),
            array(
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => array( $this, 'create_item' ),
                'permission_callback' => array( $this, 'check_create_permissions' ),
                'args' => array(
                    'post_id' => array(
                        'required' => true,
                        'type' => 'integer',
                        'sanitize_callback' => 'absint',
                    ),
                    'scope' => array(
                        'required' => true,
                        'type' => 'string',
                        'enum' => array( 'post', 'block', 'paragraph', 'media', 'route_print' ),
                    ),
                    'selector' => array(
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                        'default' => '',
                    ),
                    'price_minor' => array(
                        'required' => true,
                        'type' => 'integer',
                        'minimum' => 0,
                    ),
                    'currency' => array(
                        'type' => 'string',
                        'enum' => array( 'USD', 'EUR', 'GBP', 'JPY' ),
                        'default' => 'USD',
                    ),
                    'expires_days' => array(
                        'type' => 'integer',
                        'minimum' => 0,
                    ),
                ),
            ),
        ) );
        
        register_rest_route( self::NAMESPACE, '/items/(?P<id>\d+)', array(
            array(
                'methods' => \WP_REST_Server::READABLE,
                'callback' => array( $this, 'get_item' ),
                'permission_callback' => array( $this, 'check_permissions' ),
                'args' => array(
                    'id' => array(
                        'required' => true,
                        'type' => 'integer',
                        'sanitize_callback' => 'absint',
                    ),
                ),
            ),
            array(
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => array( $this, 'update_item' ),
                'permission_callback' => array( $this, 'check_create_permissions' ),
                'args' => array(
                    'id' => array(
                        'required' => true,
                        'type' => 'integer',
                        'sanitize_callback' => 'absint',
                    ),
                    'price_minor' => array(
                        'type' => 'integer',
                        'minimum' => 0,
                    ),
                    'currency' => array(
                        'type' => 'string',
                        'enum' => array( 'USD', 'EUR', 'GBP', 'JPY' ),
                    ),
                    'expires_days' => array(
                        'type' => 'integer',
                        'minimum' => 0,
                    ),
                    'status' => array(
                        'type' => 'string',
                        'enum' => array( 'active', 'archived' ),
                    ),
                ),
            ),
            array(
                'methods' => \WP_REST_Server::DELETABLE,
                'callback' => array( $this, 'delete_item' ),
                'permission_callback' => array( $this, 'check_create_permissions' ),
                'args' => array(
                    'id' => array(
                        'required' => true,
                        'type' => 'integer',
                        'sanitize_callback' => 'absint',
                    ),
                ),
            ),
        ) );
        
        // Entitlements endpoints
        register_rest_route( self::NAMESPACE, '/entitlements', array(
            array(
                'methods' => \WP_REST_Server::READABLE,
                'callback' => array( $this, 'get_entitlements' ),
                'permission_callback' => array( $this, 'check_permissions' ),
                'args' => array(
                    'user' => array(
                        'description' => __( 'Filter by user ID', 'paywall-anywhere' ),
                        'type' => 'integer',
                        'sanitize_callback' => 'absint',
                    ),
                    'email' => array(
                        'description' => __( 'Filter by guest email', 'paywall-anywhere' ),
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_email',
                    ),
                    'item' => array(
                        'description' => __( 'Filter by item ID', 'paywall-anywhere' ),
                        'type' => 'integer',
                        'sanitize_callback' => 'absint',
                    ),
                ),
            ),
            array(
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => array( $this, 'create_entitlement' ),
                'permission_callback' => array( $this, 'check_admin_permissions' ),
                'args' => array(
                    'item_id' => array(
                        'required' => true,
                        'type' => 'integer',
                        'sanitize_callback' => 'absint',
                    ),
                    'user_id' => array(
                        'type' => 'integer',
                        'sanitize_callback' => 'absint',
                    ),
                    'guest_email' => array(
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_email',
                    ),
                    'expires_at' => array(
                        'type' => 'string',
                        'format' => 'date-time',
                    ),
                    'source' => array(
                        'type' => 'string',
                        'enum' => array( 'stripe', 'woocommerce', 'manual' ),
                        'default' => 'manual',
                    ),
                ),
            ),
        ) );
        
        register_rest_route( self::NAMESPACE, '/entitlements/(?P<id>\d+)', array(
            array(
                'methods' => \WP_REST_Server::DELETABLE,
                'callback' => array( $this, 'revoke_entitlement' ),
                'permission_callback' => array( $this, 'check_admin_permissions' ),
                'args' => array(
                    'id' => array(
                        'required' => true,
                        'type' => 'integer',
                        'sanitize_callback' => 'absint',
                    ),
                ),
            ),
        ) );
        
        // Access check endpoint
        register_rest_route( self::NAMESPACE, '/access', array(
            array(
                'methods' => \WP_REST_Server::READABLE,
                'callback' => array( $this, 'check_access' ),
                'permission_callback' => '__return_true', // Public endpoint
                'args' => array(
                    'post_id' => array(
                        'required' => true,
                        'type' => 'integer',
                        'sanitize_callback' => 'absint',
                    ),
                    'scope' => array(
                        'type' => 'string',
                        'enum' => array( 'post', 'block', 'paragraph', 'media', 'route_print' ),
                        'default' => 'post',
                    ),
                    'selector' => array(
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                        'default' => '',
                    ),
                ),
            ),
        ) );
    }
    
    /**
     * Get items
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function get_items( $request ) {
        $db = \Paywall_Anywhere\Plugin::instance()->db;
        
        if ( $request->get_param( 'post' ) ) {
            $items = $db->get_items_by_post( $request->get_param( 'post' ) );
        } else {
            global $wpdb;
            $items = $wpdb->get_results( "SELECT * FROM {$db->get_items_table()} WHERE status = 'active' ORDER BY created_at DESC LIMIT 100" );
        }
        
        $data = array();
        foreach ( $items as $item ) {
            $data[] = $this->prepare_item_for_response( $item );
        }
        
        return rest_ensure_response( $data );
    }
    
    /**
     * Get single item
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function get_item( $request ) {
        $db = \Paywall_Anywhere\Plugin::instance()->db;
        $item = $db->get_item( $request->get_param( 'id' ) );
        
        if ( ! $item ) {
            return new \WP_Error( 'paywall_anywhere_item_not_found', __( 'Item not found', 'paywall-anywhere' ), array( 'status' => 404 ) );
        }
        
        return rest_ensure_response( $this->prepare_item_for_response( $item ) );
    }
    
    /**
     * Create item
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function create_item( $request ) {
        $db = \Paywall_Anywhere\Plugin::instance()->db;
        
        // Check if user can edit the post
        if ( ! current_user_can( 'edit_post', $request->get_param( 'post_id' ) ) ) {
            return new \WP_Error( 'paywall_anywhere_cannot_edit_post', __( 'You cannot edit this post', 'paywall-anywhere' ), array( 'status' => 403 ) );
        }
        
        $data = array(
            'post_id' => $request->get_param( 'post_id' ),
            'scope' => $request->get_param( 'scope' ),
            'selector' => $request->get_param( 'selector' ),
            'price_minor' => $request->get_param( 'price_minor' ),
            'currency' => $request->get_param( 'currency' ),
            'expires_days' => $request->get_param( 'expires_days' ),
        );
        
        $item_id = $db->create_item( $data );
        
        if ( ! $item_id ) {
            return new \WP_Error( 'paywall_anywhere_create_failed', __( 'Failed to create item', 'paywall-anywhere' ), array( 'status' => 500 ) );
        }
        
        $item = $db->get_item( $item_id );
        return rest_ensure_response( $this->prepare_item_for_response( $item ) );
    }
    
    /**
     * Update item
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function update_item( $request ) {
        $db = \Paywall_Anywhere\Plugin::instance()->db;
        $item = $db->get_item( $request->get_param( 'id' ) );
        
        if ( ! $item ) {
            return new \WP_Error( 'paywall_anywhere_item_not_found', __( 'Item not found', 'paywall-anywhere' ), array( 'status' => 404 ) );
        }
        
        // Check if user can edit the post
        if ( ! current_user_can( 'edit_post', $item->post_id ) ) {
            return new \WP_Error( 'paywall_anywhere_cannot_edit_post', __( 'You cannot edit this post', 'paywall-anywhere' ), array( 'status' => 403 ) );
        }
        
        $update_data = array();
        $allowed_fields = array( 'price_minor', 'currency', 'expires_days', 'status' );
        
        foreach ( $allowed_fields as $field ) {
            if ( $request->has_param( $field ) ) {
                $update_data[ $field ] = $request->get_param( $field );
            }
        }
        
        if ( empty( $update_data ) ) {
            return new \WP_Error( 'paywall_anywhere_no_data', __( 'No data to update', 'paywall-anywhere' ), array( 'status' => 400 ) );
        }
        
        $result = $db->update_item( $item->id, $update_data );
        
        if ( ! $result ) {
            return new \WP_Error( 'paywall_anywhere_update_failed', __( 'Failed to update item', 'paywall-anywhere' ), array( 'status' => 500 ) );
        }
        
        $updated_item = $db->get_item( $item->id );
        return rest_ensure_response( $this->prepare_item_for_response( $updated_item ) );
    }
    
    /**
     * Delete item
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function delete_item( $request ) {
        $db = \Paywall_Anywhere\Plugin::instance()->db;
        $item = $db->get_item( $request->get_param( 'id' ) );
        
        if ( ! $item ) {
            return new \WP_Error( 'paywall_anywhere_item_not_found', __( 'Item not found', 'paywall-anywhere' ), array( 'status' => 404 ) );
        }
        
        // Check if user can edit the post
        if ( ! current_user_can( 'edit_post', $item->post_id ) ) {
            return new \WP_Error( 'paywall_anywhere_cannot_edit_post', __( 'You cannot edit this post', 'paywall-anywhere' ), array( 'status' => 403 ) );
        }
        
        $result = $db->delete_item( $item->id );
        
        if ( ! $result ) {
            return new \WP_Error( 'paywall_anywhere_delete_failed', __( 'Failed to delete item', 'paywall-anywhere' ), array( 'status' => 500 ) );
        }
        
        return rest_ensure_response( array( 'deleted' => true ) );
    }
    
    /**
     * Get entitlements
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function get_entitlements( $request ) {
        $db = \Paywall_Anywhere\Plugin::instance()->db;
        
        global $wpdb;
        $where_conditions = array();
        $where_values = array();
        
        if ( $request->get_param( 'user' ) ) {
            $where_conditions[] = 'e.user_id = %d';
            $where_values[] = $request->get_param( 'user' );
        }
        
        if ( $request->get_param( 'email' ) ) {
            $where_conditions[] = 'e.guest_email = %s';
            $where_values[] = $request->get_param( 'email' );
        }
        
        if ( $request->get_param( 'item' ) ) {
            $where_conditions[] = 'e.item_id = %d';
            $where_values[] = $request->get_param( 'item' );
        }
        
        $where_clause = empty( $where_conditions ) ? '' : 'WHERE ' . implode( ' AND ', $where_conditions );
        
        $sql = "SELECT e.*, i.post_id, i.scope, i.selector, i.price_minor, i.currency
                FROM {$db->get_entitlements_table()} e 
                JOIN {$db->get_items_table()} i ON e.item_id = i.id 
                $where_clause
                ORDER BY e.granted_at DESC LIMIT 100";
        
        if ( ! empty( $where_values ) ) {
            $entitlements = $wpdb->get_results( $wpdb->prepare( $sql, $where_values ) );
        } else {
            $entitlements = $wpdb->get_results( $sql );
        }
        
        $data = array();
        foreach ( $entitlements as $entitlement ) {
            $data[] = $this->prepare_entitlement_for_response( $entitlement );
        }
        
        return rest_ensure_response( $data );
    }
    
    /**
     * Create entitlement
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function create_entitlement( $request ) {
        $db = \Paywall_Anywhere\Plugin::instance()->db;
        
        $data = array(
            'item_id' => $request->get_param( 'item_id' ),
            'user_id' => $request->get_param( 'user_id' ),
            'guest_email' => $request->get_param( 'guest_email' ),
            'expires_at' => $request->get_param( 'expires_at' ),
            'source' => $request->get_param( 'source' ),
        );
        
        $entitlement_id = $db->create_entitlement( $data );
        
        if ( ! $entitlement_id ) {
            return new \WP_Error( 'paywall_anywhere_create_failed', __( 'Failed to create entitlement', 'paywall-anywhere' ), array( 'status' => 500 ) );
        }
        
        return rest_ensure_response( array( 'id' => $entitlement_id, 'created' => true ) );
    }
    
    /**
     * Revoke entitlement
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function revoke_entitlement( $request ) {
        $db = \Paywall_Anywhere\Plugin::instance()->db;
        $result = $db->revoke_entitlement( $request->get_param( 'id' ) );
        
        if ( ! $result ) {
            return new \WP_Error( 'paywall_anywhere_revoke_failed', __( 'Failed to revoke entitlement', 'paywall-anywhere' ), array( 'status' => 500 ) );
        }
        
        return rest_ensure_response( array( 'revoked' => true ) );
    }
    
    /**
     * Check access
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function check_access( $request ) {
        $has_access = \paywall_anywhere_is_unlocked( array(
            'post_id' => $request->get_param( 'post_id' ),
            'scope' => $request->get_param( 'scope' ),
            'selector' => $request->get_param( 'selector' ),
        ) );
        
        return rest_ensure_response( array( 'has_access' => $has_access ) );
    }
    
    /**
     * Prepare item for response
     *
     * @param object $item Item object.
     * @return array
     */
    private function prepare_item_for_response( $item ) {
        return array(
            'id' => (int) $item->id,
            'post_id' => (int) $item->post_id,
            'scope' => $item->scope,
            'selector' => $item->selector,
            'price_minor' => (int) $item->price_minor,
            'currency' => $item->currency,
            'expires_days' => $item->expires_days ? (int) $item->expires_days : null,
            'status' => $item->status,
            'created_at' => $item->created_at,
            'updated_at' => $item->updated_at,
            'formatted_price' => \paywall_anywhere_format_price( $item->price_minor, $item->currency ),
        );
    }
    
    /**
     * Prepare entitlement for response
     *
     * @param object $entitlement Entitlement object.
     * @return array
     */
    private function prepare_entitlement_for_response( $entitlement ) {
        return array(
            'id' => (int) $entitlement->id,
            'user_id' => $entitlement->user_id ? (int) $entitlement->user_id : null,
            'guest_email' => $entitlement->guest_email,
            'item_id' => (int) $entitlement->item_id,
            'granted_at' => $entitlement->granted_at,
            'expires_at' => $entitlement->expires_at,
            'source' => $entitlement->source,
            'item' => array(
                'post_id' => (int) $entitlement->post_id,
                'scope' => $entitlement->scope,
                'selector' => $entitlement->selector,
                'price_minor' => (int) $entitlement->price_minor,
                'currency' => $entitlement->currency,
                'formatted_price' => \paywall_anywhere_format_price( $entitlement->price_minor, $entitlement->currency ),
            ),
        );
    }
    
    /**
     * Check permissions for reading
     *
     * @return bool
     */
    public function check_permissions() {
        return current_user_can( 'edit_posts' );
    }
    
    /**
     * Check permissions for creating/editing
     *
     * @return bool
     */
    public function check_create_permissions() {
        return current_user_can( 'edit_posts' );
    }
    
    /**
     * Check admin permissions
     *
     * @return bool
     */
    public function check_admin_permissions() {
        return current_user_can( 'manage_options' );
    }
}