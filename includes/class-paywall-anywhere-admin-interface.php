<?php
/**
 * Admin Interface Class
 *
 * @package Paywall_Anywhere\Admin
 */

namespace Paywall_Anywhere\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Admin Interface Class
 */
class Admin_Interface {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
        add_action( 'wp_ajax_paywall_anywhere_test_stripe', array( $this, 'ajax_test_stripe_connection' ) );
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __( 'Paywall Anywhere', 'paywall-anywhere' ),
            __( 'Paywall Anywhere', 'paywall-anywhere' ),
            'manage_options',
            'paywall-anywhere',
            array( $this, 'settings_page' ),
            'dashicons-lock',
            30
        );
        
        add_submenu_page(
            'paywall-anywhere',
            __( 'Settings', 'paywall-anywhere' ),
            __( 'Settings', 'paywall-anywhere' ),
            'manage_options',
            'paywall-anywhere',
            array( $this, 'settings_page' )
        );
        
        add_submenu_page(
            'paywall-anywhere',
            __( 'Premium Items', 'paywall-anywhere' ),
            __( 'Premium Items', 'paywall-anywhere' ),
            'manage_options',
            'paywall-anywhere-items',
            array( $this, 'items_page' )
        );
        
        add_submenu_page(
            'paywall-anywhere',
            __( 'Entitlements', 'paywall-anywhere' ),
            __( 'Entitlements', 'paywall-anywhere' ),
            'manage_options',
            'paywall-anywhere-entitlements',
            array( $this, 'entitlements_page' )
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting( 'paywall_anywhere_settings', 'paywall_anywhere_default_currency' );
        register_setting( 'paywall_anywhere_settings', 'paywall_anywhere_default_price' );
        register_setting( 'paywall_anywhere_settings', 'paywall_anywhere_default_expires_days' );
        register_setting( 'paywall_anywhere_settings', 'paywall_anywhere_teaser_length' );
        register_setting( 'paywall_anywhere_settings', 'paywall_anywhere_stripe_enabled' );
        register_setting( 'paywall_anywhere_settings', 'paywall_anywhere_stripe_publishable_key' );
        register_setting( 'paywall_anywhere_settings', 'paywall_anywhere_stripe_secret_key' );
        register_setting( 'paywall_anywhere_settings', 'paywall_anywhere_stripe_webhook_secret' );
        register_setting( 'paywall_anywhere_settings', 'paywall_anywhere_woocommerce_enabled' );
        register_setting( 'paywall_anywhere_settings', 'paywall_anywhere_magic_link_ttl' );
        
        // Add settings sections
        add_settings_section(
            'paywall_anywhere_general',
            __( 'General Settings', 'paywall-anywhere' ),
            array( $this, 'general_section_callback' ),
            'paywall_anywhere_settings'
        );
        
        add_settings_section(
            'paywall_anywhere_payments',
            __( 'Payment Settings', 'paywall-anywhere' ),
            array( $this, 'payments_section_callback' ),
            'paywall_anywhere_settings'
        );
        
        // Add settings fields
        $this->add_settings_fields();
    }
    
    /**
     * Add settings fields
     */
    private function add_settings_fields() {
        // General settings
        add_settings_field(
            'default_currency',
            __( 'Default Currency', 'paywall-anywhere' ),
            array( $this, 'currency_field_callback' ),
            'paywall_anywhere_settings',
            'paywall_anywhere_general'
        );
        
        add_settings_field(
            'default_price',
            __( 'Default Price (cents)', 'paywall-anywhere' ),
            array( $this, 'price_field_callback' ),
            'paywall_anywhere_settings',
            'paywall_anywhere_general'
        );
        
        add_settings_field(
            'default_expires_days',
            __( 'Default Expires (days)', 'paywall-anywhere' ),
            array( $this, 'expires_field_callback' ),
            'paywall_anywhere_settings',
            'paywall_anywhere_general'
        );
        
        add_settings_field(
            'teaser_length',
            __( 'Teaser Length (words)', 'paywall-anywhere' ),
            array( $this, 'teaser_field_callback' ),
            'paywall_anywhere_settings',
            'paywall_anywhere_general'
        );
        
        // Payment settings
        add_settings_field(
            'stripe_enabled',
            __( 'Enable Stripe', 'paywall-anywhere' ),
            array( $this, 'stripe_enabled_field_callback' ),
            'paywall_anywhere_settings',
            'paywall_anywhere_payments'
        );
        
        add_settings_field(
            'stripe_keys',
            __( 'Stripe API Keys', 'paywall-anywhere' ),
            array( $this, 'stripe_keys_field_callback' ),
            'paywall_anywhere_settings',
            'paywall_anywhere_payments'
        );
        
        add_settings_field(
            'woocommerce_enabled',
            __( 'Enable WooCommerce', 'paywall-anywhere' ),
            array( $this, 'woocommerce_enabled_field_callback' ),
            'paywall_anywhere_settings',
            'paywall_anywhere_payments'
        );
        
        add_settings_field(
            'magic_link_ttl',
            __( 'Magic Link TTL (seconds)', 'paywall-anywhere' ),
            array( $this, 'magic_link_ttl_field_callback' ),
            'paywall_anywhere_settings',
            'paywall_anywhere_payments'
        );
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.', 'paywall-anywhere' ) );
        }
        
        // Get current tab
        $current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'general';
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            
            <!-- Tabs Navigation -->
            <nav class="nav-tab-wrapper wp-clearfix">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=paywall-anywhere&tab=general' ) ); ?>" 
                   class="nav-tab <?php echo $current_tab === 'general' ? 'nav-tab-active' : ''; ?>">
                    <?php _e( 'General Settings', 'paywall-anywhere' ); ?>
                </a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=paywall-anywhere&tab=payments' ) ); ?>" 
                   class="nav-tab <?php echo $current_tab === 'payments' ? 'nav-tab-active' : ''; ?>">
                    <?php _e( 'Payment Settings', 'paywall-anywhere' ); ?>
                </a>
            </nav>
            
            <!-- Tab Content -->
            <div class="paywall-anywhere-tab-content">
                <?php if ( $current_tab === 'general' ) : ?>
                    <?php $this->render_general_settings_tab(); ?>
                <?php elseif ( $current_tab === 'payments' ) : ?>
                    <?php $this->render_payments_settings_tab(); ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Items page
     */
    public function items_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.', 'paywall-anywhere' ) );
        }
        
        $db = \Paywall_Anywhere\Plugin::instance()->db;
        
        // Handle actions
        if ( isset( $_POST['action'] ) && wp_verify_nonce( $_POST['paywall_anywhere_nonce'], 'paywall_anywhere_admin_action' ) ) {
            switch ( $_POST['action'] ) {
                case 'delete_item':
                    $item_id = absint( $_POST['item_id'] );
                    if ( $item_id && $db->delete_item( $item_id ) ) {
                        echo '<div class="notice notice-success"><p>' . __( 'Item deleted successfully.', 'paywall-anywhere' ) . '</p></div>';
                    }
                    break;
                case 'scan_locked_items':
                    $scanned = $this->scan_and_register_locked_items();
                    echo '<div class="notice notice-success"><p>' . sprintf( __( 'Scan completed. Found and registered %d locked items.', 'paywall-anywhere' ), $scanned ) . '</p></div>';
                    break;
                case 'update_item':
                    $item_id = absint( $_POST['item_id'] );
                    $price = absint( $_POST['price'] );
                    $expires_days = $_POST['expires_days'] === '' ? null : absint( $_POST['expires_days'] );
                    
                    if ( $item_id && $db->update_item( $item_id, array( 
                        'price_minor' => $price,
                        'expires_days' => $expires_days
                    ) ) ) {
                        echo '<div class="notice notice-success"><p>' . __( 'Item updated successfully.', 'paywall-anywhere' ) . '</p></div>';
                    }
                    break;
            }
        }
        
        // Get filter parameters
        $scope_filter = isset( $_GET['scope'] ) ? sanitize_text_field( $_GET['scope'] ) : '';
        $status_filter = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';
        $search_query = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
        
        // Build query
        global $wpdb;
        $table = $db->get_items_table();
        $where_conditions = array( '1=1' );
        $params = array();
        
        if ( ! empty( $scope_filter ) ) {
            $where_conditions[] = 'scope = %s';
            $params[] = $scope_filter;
        }
        
        if ( ! empty( $status_filter ) ) {
            $where_conditions[] = 'status = %s';
            $params[] = $status_filter;
        }
        
        if ( ! empty( $search_query ) ) {
            $where_conditions[] = '(post_id IN (SELECT ID FROM ' . $wpdb->posts . ' WHERE post_title LIKE %s) OR selector LIKE %s)';
            $params[] = '%' . $wpdb->esc_like( $search_query ) . '%';
            $params[] = '%' . $wpdb->esc_like( $search_query ) . '%';
        }
        
        $where_clause = implode( ' AND ', $where_conditions );
        $query = "SELECT * FROM {$table} WHERE {$where_clause} ORDER BY updated_at DESC, created_at DESC LIMIT 100";
        
        if ( ! empty( $params ) ) {
            $items = $wpdb->get_results( $wpdb->prepare( $query, $params ) );
        } else {
            $items = $wpdb->get_results( $query );
        }
        
        ?>
        <div class="wrap">
            <h1>
                <?php echo esc_html( get_admin_page_title() ); ?>
                <button type="button" class="page-title-action" id="scan-locked-items-btn">
                    <?php _e( 'Scan & Register Locked Items', 'paywall-anywhere' ); ?>
                </button>
            </h1>
            
            <!-- Filters and Search -->
            <div class="paywall-anywhere-items-filters">
                <form method="get" class="paywall-anywhere-filters-form">
                    <input type="hidden" name="page" value="paywall-anywhere-items">
                    
                    <div class="paywall-anywhere-filter-row">
                        <select name="scope" id="scope-filter">
                            <option value=""><?php _e( 'All Scopes', 'paywall-anywhere' ); ?></option>
                            <option value="post" <?php selected( $scope_filter, 'post' ); ?>><?php _e( 'Post', 'paywall-anywhere' ); ?></option>
                            <option value="block" <?php selected( $scope_filter, 'block' ); ?>><?php _e( 'Block', 'paywall-anywhere' ); ?></option>
                            <option value="paragraph" <?php selected( $scope_filter, 'paragraph' ); ?>><?php _e( 'Paragraph', 'paywall-anywhere' ); ?></option>
                            <option value="media" <?php selected( $scope_filter, 'media' ); ?>><?php _e( 'Media', 'paywall-anywhere' ); ?></option>
                        </select>
                        
                        <select name="status" id="status-filter">
                            <option value=""><?php _e( 'All Statuses', 'paywall-anywhere' ); ?></option>
                            <option value="active" <?php selected( $status_filter, 'active' ); ?>><?php _e( 'Active', 'paywall-anywhere' ); ?></option>
                            <option value="archived" <?php selected( $status_filter, 'archived' ); ?>><?php _e( 'Archived', 'paywall-anywhere' ); ?></option>
                        </select>
                        
                        <input type="search" 
                               name="s" 
                               value="<?php echo esc_attr( $search_query ); ?>" 
                               placeholder="<?php esc_attr_e( 'Search items by post title or content...', 'paywall-anywhere' ); ?>" 
                               class="paywall-anywhere-search-input">
                        
                        <button type="submit" class="button"><?php _e( 'Filter', 'paywall-anywhere' ); ?></button>
                        
                        <?php if ( ! empty( $scope_filter ) || ! empty( $status_filter ) || ! empty( $search_query ) ) : ?>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=paywall-anywhere-items' ) ); ?>" class="button">
                                <?php _e( 'Clear Filters', 'paywall-anywhere' ); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <!-- Items Table -->
            <div class="paywall-anywhere-items-table-container">
                <table class="paywall-anywhere-table wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e( 'Item', 'paywall-anywhere' ); ?></th>
                            <th><?php _e( 'Post', 'paywall-anywhere' ); ?></th>
                            <th><?php _e( 'Price', 'paywall-anywhere' ); ?></th>
                            <th><?php _e( 'Expiry', 'paywall-anywhere' ); ?></th>
                            <th><?php _e( 'Status', 'paywall-anywhere' ); ?></th>
                            <th><?php _e( 'Updated', 'paywall-anywhere' ); ?></th>
                            <th><?php _e( 'Actions', 'paywall-anywhere' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( empty( $items ) ) : ?>
                            <tr>
                                <td colspan="7" class="paywall-anywhere-empty-state">
                                    <div class="paywall-anywhere-empty-content">
                                        <h3><?php _e( 'No premium items found', 'paywall-anywhere' ); ?></h3>
                                        <p><?php _e( 'Start by scanning for locked content or create new premium items in your posts.', 'paywall-anywhere' ); ?></p>
                                        <button type="button" class="button button-primary" id="scan-empty-state-btn">
                                            <?php _e( 'Scan for Locked Items', 'paywall-anywhere' ); ?>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php else : ?>
                            <?php foreach ( $items as $item ) : ?>
                                <tr data-item-id="<?php echo esc_attr( $item->id ); ?>">
                                    <td>
                                        <div class="paywall-anywhere-item-info">
                                            <strong class="paywall-anywhere-item-title">
                                                <?php 
                                                $title = $this->get_item_display_title( $item );
                                                echo esc_html( $title );
                                                ?>
                                            </strong>
                                            <div class="paywall-anywhere-item-scope">
                                                <span class="paywall-anywhere-scope-badge paywall-anywhere-scope-<?php echo esc_attr( $item->scope ); ?>">
                                                    <?php echo esc_html( ucfirst( $item->scope ) ); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ( $item->post_id ) : ?>
                                            <a href="<?php echo esc_url( get_edit_post_link( $item->post_id ) ); ?>" target="_blank">
                                                <?php echo esc_html( get_the_title( $item->post_id ) ); ?>
                                                <span class="dashicons dashicons-external" style="font-size: 12px; margin-left: 4px;"></span>
                                            </a>
                                        <?php else : ?>
                                            <span class="paywall-anywhere-no-post">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="paywall-anywhere-inline-edit-price" data-field="price" data-item-id="<?php echo esc_attr( $item->id ); ?>">
                                            <span class="paywall-anywhere-display-value">
                                                <?php echo esc_html( \paywall_anywhere_format_price( $item->price_minor, $item->currency ) ); ?>
                                            </span>
                                            <input type="number" 
                                                   class="paywall-anywhere-edit-input" 
                                                   value="<?php echo esc_attr( $item->price_minor ); ?>" 
                                                   min="0" 
                                                   step="1" 
                                                   style="display: none; width: 80px;">
                                        </div>
                                    </td>
                                    <td>
                                        <div class="paywall-anywhere-inline-edit-expiry" data-field="expires_days" data-item-id="<?php echo esc_attr( $item->id ); ?>">
                                            <span class="paywall-anywhere-display-value">
                                                <?php 
                                                if ( $item->expires_days ) {
                                                    printf( _n( '%d day', '%d days', $item->expires_days, 'paywall-anywhere' ), $item->expires_days );
                                                } else {
                                                    _e( 'Never', 'paywall-anywhere' );
                                                }
                                                ?>
                                            </span>
                                            <input type="number" 
                                                   class="paywall-anywhere-edit-input" 
                                                   value="<?php echo esc_attr( $item->expires_days ); ?>" 
                                                   min="0" 
                                                   step="1" 
                                                   style="display: none; width: 80px;"
                                                   placeholder="0 = never">
                                        </div>
                                    </td>
                                    <td>
                                        <span class="paywall-anywhere-status paywall-anywhere-status-<?php echo esc_attr( $item->status ); ?>">
                                            <?php echo esc_html( ucfirst( $item->status ) ); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        $updated = $item->updated_at ?? $item->created_at;
                                        echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $updated ) ) );
                                        ?>
                                    </td>
                                    <td>
                                        <div class="paywall-anywhere-item-actions">
                                            <button type="button" class="button button-small paywall-anywhere-edit-item-btn" data-item-id="<?php echo esc_attr( $item->id ); ?>">
                                                <?php _e( 'Edit', 'paywall-anywhere' ); ?>
                                            </button>
                                            <button type="button" class="button button-small paywall-anywhere-save-item-btn" data-item-id="<?php echo esc_attr( $item->id ); ?>" style="display: none;">
                                                <?php _e( 'Save', 'paywall-anywhere' ); ?>
                                            </button>
                                            <button type="button" class="button button-small paywall-anywhere-cancel-edit-btn" data-item-id="<?php echo esc_attr( $item->id ); ?>" style="display: none;">
                                                <?php _e( 'Cancel', 'paywall-anywhere' ); ?>
                                            </button>
                                            <form method="post" style="display: inline;" class="paywall-anywhere-delete-form">
                                                <?php wp_nonce_field( 'paywall_anywhere_admin_action', 'paywall_anywhere_nonce' ); ?>
                                                <input type="hidden" name="action" value="delete_item">
                                                <input type="hidden" name="item_id" value="<?php echo esc_attr( $item->id ); ?>">
                                                <button type="submit" class="button button-small paywall-anywhere-btn-danger" onclick="return confirm('<?php esc_attr_e( 'Are you sure?', 'paywall-anywhere' ); ?>')">
                                                    <?php _e( 'Delete', 'paywall-anywhere' ); ?>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Hidden forms for AJAX actions -->
            <form id="scan-locked-items-form" method="post" style="display: none;">
                <?php wp_nonce_field( 'paywall_anywhere_admin_action', 'paywall_anywhere_nonce' ); ?>
                <input type="hidden" name="action" value="scan_locked_items">
            </form>
            
            <form id="update-item-form" method="post" style="display: none;">
                <?php wp_nonce_field( 'paywall_anywhere_admin_action', 'paywall_anywhere_nonce' ); ?>
                <input type="hidden" name="action" value="update_item">
                <input type="hidden" name="item_id" id="update-item-id">
                <input type="hidden" name="price" id="update-item-price">
                <input type="hidden" name="expires_days" id="update-item-expires">
            </form>
        </div>
        
        <style>
        .paywall-anywhere-items-filters {
            background: white;
            border: 1px solid #ccd0d4;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        
        .paywall-anywhere-filter-row {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .paywall-anywhere-search-input {
            flex: 1;
            min-width: 200px;
            max-width: 400px;
        }
        
        .paywall-anywhere-items-table-container {
            background: white;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .paywall-anywhere-empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        
        .paywall-anywhere-empty-content h3 {
            color: #666;
            margin: 0 0 10px 0;
        }
        
        .paywall-anywhere-empty-content p {
            color: #999;
            margin: 0 0 20px 0;
        }
        
        .paywall-anywhere-item-info {
            max-width: 300px;
        }
        
        .paywall-anywhere-item-title {
            display: block;
            margin-bottom: 5px;
        }
        
        .paywall-anywhere-scope-badge {
            padding: 2px 6px;
            border-radius: 8px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .paywall-anywhere-scope-post {
            background: #e3f2fd;
            color: #1565c0;
        }
        
        .paywall-anywhere-scope-block {
            background: #f3e5f5;
            color: #7b1fa2;
        }
        
        .paywall-anywhere-scope-paragraph {
            background: #e8f5e8;
            color: #2e7d32;
        }
        
        .paywall-anywhere-scope-media {
            background: #fff3e0;
            color: #f57c00;
        }
        
        .paywall-anywhere-inline-edit-price,
        .paywall-anywhere-inline-edit-expiry {
            cursor: pointer;
            padding: 4px;
            border-radius: 3px;
            transition: background-color 0.2s;
        }
        
        .paywall-anywhere-inline-edit-price:hover,
        .paywall-anywhere-inline-edit-expiry:hover {
            background: #f0f0f0;
        }
        
        .paywall-anywhere-item-actions {
            white-space: nowrap;
        }
        
        .paywall-anywhere-item-actions .button {
            margin-right: 5px;
        }
        
        .paywall-anywhere-no-post {
            color: #999;
        }
        </style>
        <?php
    }
    
    /**
     * Entitlements page
     */
    public function entitlements_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.', 'paywall-anywhere' ) );
        }
        
        $db = \Paywall_Anywhere\Plugin::instance()->db;
        
        // Handle actions
        if ( isset( $_POST['action'] ) && wp_verify_nonce( $_POST['paywall_anywhere_nonce'], 'paywall_anywhere_admin_action' ) ) {
            switch ( $_POST['action'] ) {
                case 'revoke_entitlement':
                    $entitlement_id = absint( $_POST['entitlement_id'] );
                    if ( $entitlement_id && $db->revoke_entitlement( $entitlement_id ) ) {
                        echo '<div class="notice notice-success"><p>' . __( 'Entitlement revoked successfully.', 'paywall-anywhere' ) . '</p></div>';
                    }
                    break;
            }
        }
        
        global $wpdb;
        $entitlements = $wpdb->get_results( 
            "SELECT e.*, i.post_id, i.scope, i.selector, i.price_minor, i.currency
             FROM {$db->get_entitlements_table()} e 
             JOIN {$db->get_items_table()} i ON e.item_id = i.id 
             ORDER BY e.granted_at DESC LIMIT 100" 
        );
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e( 'ID', 'paywall-anywhere' ); ?></th>
                        <th><?php _e( 'User/Email', 'paywall-anywhere' ); ?></th>
                        <th><?php _e( 'Post', 'paywall-anywhere' ); ?></th>
                        <th><?php _e( 'Price', 'paywall-anywhere' ); ?></th>
                        <th><?php _e( 'Source', 'paywall-anywhere' ); ?></th>
                        <th><?php _e( 'Granted', 'paywall-anywhere' ); ?></th>
                        <th><?php _e( 'Expires', 'paywall-anywhere' ); ?></th>
                        <th><?php _e( 'Actions', 'paywall-anywhere' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $entitlements ) ) : ?>
                        <tr>
                            <td colspan="8"><?php _e( 'No entitlements found.', 'paywall-anywhere' ); ?></td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ( $entitlements as $entitlement ) : ?>
                            <tr>
                                <td><?php echo esc_html( $entitlement->id ); ?></td>
                                <td>
                                    <?php if ( $entitlement->user_id ) : ?>
                                        <?php $user = get_user_by( 'id', $entitlement->user_id ); ?>
                                        <?php echo $user ? esc_html( $user->display_name ) : __( 'User not found', 'paywall-anywhere' ); ?>
                                    <?php else : ?>
                                        <?php echo esc_html( $entitlement->guest_email ); ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ( $entitlement->post_id ) : ?>
                                        <a href="<?php echo esc_url( get_edit_post_link( $entitlement->post_id ) ); ?>">
                                            <?php echo esc_html( get_the_title( $entitlement->post_id ) ); ?>
                                        </a>
                                    <?php else : ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html( \paywall_anywhere_format_price( $entitlement->price_minor, $entitlement->currency ) ); ?></td>
                                <td><?php echo esc_html( ucfirst( $entitlement->source ) ); ?></td>
                                <td><?php echo esc_html( $entitlement->granted_at ); ?></td>
                                <td>
                                    <?php if ( $entitlement->expires_at ) : ?>
                                        <?php echo esc_html( $entitlement->expires_at ); ?>
                                    <?php else : ?>
                                        <?php _e( 'Never', 'paywall-anywhere' ); ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="post" style="display: inline;">
                                        <?php wp_nonce_field( 'paywall_anywhere_admin_action', 'paywall_anywhere_nonce' ); ?>
                                        <input type="hidden" name="action" value="revoke_entitlement">
                                        <input type="hidden" name="entitlement_id" value="<?php echo esc_attr( $entitlement->id ); ?>">
                                        <button type="submit" class="button button-small" onclick="return confirm('<?php esc_attr_e( 'Are you sure?', 'paywall-anywhere' ); ?>')">
                                            <?php _e( 'Revoke', 'paywall-anywhere' ); ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    /**
     * Enqueue admin scripts
     *
     * @param string $hook Current admin page hook.
     */
    public function enqueue_admin_scripts( $hook ) {
        if ( strpos( $hook, 'paywall-anywhere' ) === false ) {
            return;
        }
        
        wp_enqueue_style( 
            'paywall-anywhere-admin', 
            PAYWALL_ANYWHERE_PLUGIN_URL . 'assets/css/admin.css', 
            array(), 
            PAYWALL_ANYWHERE_VERSION 
        );
        
        wp_enqueue_script(
            'paywall-anywhere-admin',
            PAYWALL_ANYWHERE_PLUGIN_URL . 'assets/js/admin.js',
            array( 'jquery' ),
            PAYWALL_ANYWHERE_VERSION,
            true
        );
        
        wp_localize_script(
            'paywall-anywhere-admin',
            'paywallAnywhereAdmin',
            array(
                'ajaxurl' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'paywall_anywhere_admin_ajax' )
            )
        );
    }
    
    // Settings field callbacks
    public function general_section_callback() {
        echo '<p>' . __( 'Configure general settings for Paywall Anywhere.', 'paywall-anywhere' ) . '</p>';
    }
    
    public function payments_section_callback() {
        echo '<p>' . __( 'Configure payment providers and options.', 'paywall-anywhere' ) . '</p>';
    }
    
    public function currency_field_callback() {
        $value = get_option( 'paywall_anywhere_default_currency', 'USD' );
        $currencies = \Paywall_Anywhere\Plugin::instance()->get_supported_currencies();
        
        echo '<select name="paywall_anywhere_default_currency">';
        foreach ( $currencies as $code => $currency ) {
            printf( 
                '<option value="%s" %s>%s (%s)</option>',
                esc_attr( $code ),
                selected( $value, $code, false ),
                esc_html( $currency['name'] ),
                esc_html( $currency['symbol'] )
            );
        }
        echo '</select>';
    }
    
    public function price_field_callback() {
        $value = get_option( 'paywall_anywhere_default_price', 500 );
        printf( '<input type="number" name="paywall_anywhere_default_price" value="%d" min="0" step="1" />', $value );
        echo '<p class="description">' . __( 'Default price in cents (e.g., 500 = $5.00)', 'paywall-anywhere' ) . '</p>';
    }
    
    public function expires_field_callback() {
        $value = get_option( 'paywall_anywhere_default_expires_days', 30 );
        printf( '<input type="number" name="paywall_anywhere_default_expires_days" value="%d" min="0" step="1" />', $value );
        echo '<p class="description">' . __( 'Default access duration in days (0 = never expires)', 'paywall-anywhere' ) . '</p>';
    }
    
    public function teaser_field_callback() {
        $value = get_option( 'paywall_anywhere_teaser_length', 150 );
        printf( '<input type="number" name="paywall_anywhere_teaser_length" value="%d" min="0" step="1" />', $value );
        echo '<p class="description">' . __( 'Number of words to show in teasers', 'paywall-anywhere' ) . '</p>';
    }
    
    public function stripe_enabled_field_callback() {
        $value = get_option( 'paywall_anywhere_stripe_enabled', false );
        printf( '<input type="checkbox" name="paywall_anywhere_stripe_enabled" value="1" %s />', checked( $value, true, false ) );
        echo '<label>' . __( 'Enable Stripe payments', 'paywall-anywhere' ) . '</label>';
    }
    
    public function stripe_keys_field_callback() {
        $publishable = get_option( 'paywall_anywhere_stripe_publishable_key', '' );
        $secret = get_option( 'paywall_anywhere_stripe_secret_key', '' );
        $webhook = get_option( 'paywall_anywhere_stripe_webhook_secret', '' );
        
        echo '<table class="form-table">';
        printf( 
            '<tr><th>%s</th><td><input type="text" name="paywall_anywhere_stripe_publishable_key" value="%s" class="regular-text" /></td></tr>',
            __( 'Publishable Key', 'paywall-anywhere' ),
            esc_attr( $publishable )
        );
        printf( 
            '<tr><th>%s</th><td><input type="password" name="paywall_anywhere_stripe_secret_key" value="%s" class="regular-text" /></td></tr>',
            __( 'Secret Key', 'paywall-anywhere' ),
            esc_attr( $secret )
        );
        printf( 
            '<tr><th>%s</th><td><input type="password" name="paywall_anywhere_stripe_webhook_secret" value="%s" class="regular-text" /></td></tr>',
            __( 'Webhook Secret', 'paywall-anywhere' ),
            esc_attr( $webhook )
        );
        echo '</table>';
    }
    
    public function woocommerce_enabled_field_callback() {
        $value = get_option( 'paywall_anywhere_woocommerce_enabled', false );
        $disabled = ! class_exists( 'WooCommerce' );
        
        printf( 
            '<input type="checkbox" name="paywall_anywhere_woocommerce_enabled" value="1" %s %s />',
            checked( $value, true, false ),
            disabled( $disabled, true, false )
        );
        echo '<label>' . __( 'Enable WooCommerce integration', 'paywall-anywhere' ) . '</label>';
        
        if ( $disabled ) {
            echo '<p class="description">' . __( 'WooCommerce plugin is required', 'paywall-anywhere' ) . '</p>';
        }
    }
    
    public function magic_link_ttl_field_callback() {
        $value = get_option( 'paywall_anywhere_magic_link_ttl', 3600 );
        printf( '<input type="number" name="paywall_anywhere_magic_link_ttl" value="%d" min="300" step="60" />', $value );
        echo '<p class="description">' . __( 'How long magic links remain valid (in seconds)', 'paywall-anywhere' ) . '</p>';
    }
    
    /**
     * Render general settings tab
     */
    private function render_general_settings_tab() {
        ?>
        <form method="post" action="options.php" id="paywall-anywhere-general-form">
            <?php settings_fields( 'paywall_anywhere_settings' ); ?>
            
            <div class="paywall-anywhere-settings-section">
                <h3><?php _e( 'General Settings', 'paywall-anywhere' ); ?></h3>
                <div class="paywall-anywhere-settings-content">
                    <p><?php _e( 'Configure general settings for Paywall Anywhere.', 'paywall-anywhere' ); ?></p>
                    
                    <table class="paywall-anywhere-form-table">
                        <tbody>
                            <tr>
                                <th><?php _e( 'Default Currency', 'paywall-anywhere' ); ?></th>
                                <td>
                                    <?php $this->currency_field_callback(); ?>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e( 'Default Price (cents)', 'paywall-anywhere' ); ?></th>
                                <td>
                                    <?php $this->price_field_callback(); ?>
                                    <div class="paywall-anywhere-validation-error" id="price-error" style="display: none; color: #dc3232; margin-top: 5px;"></div>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e( 'Default Expires (days)', 'paywall-anywhere' ); ?></th>
                                <td>
                                    <?php $this->expires_field_callback(); ?>
                                    <div class="paywall-anywhere-validation-error" id="expires-error" style="display: none; color: #dc3232; margin-top: 5px;"></div>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e( 'Teaser Length (words)', 'paywall-anywhere' ); ?></th>
                                <td>
                                    <?php $this->teaser_field_callback(); ?>
                                    <div class="paywall-anywhere-validation-error" id="teaser-error" style="display: none; color: #dc3232; margin-top: 5px;"></div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <?php submit_button(); ?>
        </form>
        <?php
    }
    
    /**
     * Render payments settings tab
     */
    private function render_payments_settings_tab() {
        $woo_active = class_exists( 'WooCommerce' );
        ?>
        <form method="post" action="options.php" id="paywall-anywhere-payments-form">
            <?php settings_fields( 'paywall_anywhere_settings' ); ?>
            
            <!-- Stripe Settings -->
            <div class="paywall-anywhere-settings-section">
                <h3><?php _e( 'Stripe Settings', 'paywall-anywhere' ); ?></h3>
                <div class="paywall-anywhere-settings-content">
                    <p><?php _e( 'Configure Stripe payment integration.', 'paywall-anywhere' ); ?></p>
                    
                    <table class="paywall-anywhere-form-table">
                        <tbody>
                            <tr>
                                <th><?php _e( 'Enable Stripe', 'paywall-anywhere' ); ?></th>
                                <td>
                                    <?php $this->stripe_enabled_field_callback(); ?>
                                </td>
                            </tr>
                            <tr id="stripe-keys-row">
                                <th><?php _e( 'Stripe API Keys', 'paywall-anywhere' ); ?></th>
                                <td>
                                    <?php $this->enhanced_stripe_keys_field_callback(); ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- WooCommerce Settings -->
            <div class="paywall-anywhere-settings-section">
                <h3><?php _e( 'WooCommerce Settings', 'paywall-anywhere' ); ?></h3>
                <div class="paywall-anywhere-settings-content">
                    <p><?php _e( 'Configure WooCommerce integration.', 'paywall-anywhere' ); ?></p>
                    
                    <?php if ( ! $woo_active ) : ?>
                        <div class="paywall-anywhere-notice paywall-anywhere-notice-warning">
                            <strong><?php _e( 'WooCommerce Status:', 'paywall-anywhere' ); ?></strong>
                            <?php _e( 'WooCommerce plugin is not active. Install and activate WooCommerce to enable integration.', 'paywall-anywhere' ); ?>
                        </div>
                    <?php else : ?>
                        <div class="paywall-anywhere-notice paywall-anywhere-notice-success">
                            <strong><?php _e( 'WooCommerce Status:', 'paywall-anywhere' ); ?></strong>
                            <?php _e( 'WooCommerce is active and ready for integration.', 'paywall-anywhere' ); ?>
                        </div>
                    <?php endif; ?>
                    
                    <table class="paywall-anywhere-form-table">
                        <tbody>
                            <tr>
                                <th><?php _e( 'Enable WooCommerce', 'paywall-anywhere' ); ?></th>
                                <td>
                                    <?php $this->woocommerce_enabled_field_callback(); ?>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e( 'Magic Link TTL (seconds)', 'paywall-anywhere' ); ?></th>
                                <td>
                                    <?php $this->magic_link_ttl_field_callback(); ?>
                                    <div class="paywall-anywhere-validation-error" id="ttl-error" style="display: none; color: #dc3232; margin-top: 5px;"></div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <?php submit_button(); ?>
        </form>
        <?php
    }
    
    /**
     * Enhanced Stripe keys field with test connection
     */
    public function enhanced_stripe_keys_field_callback() {
        $publishable = get_option( 'paywall_anywhere_stripe_publishable_key', '' );
        $secret = get_option( 'paywall_anywhere_stripe_secret_key', '' );
        $webhook = get_option( 'paywall_anywhere_stripe_webhook_secret', '' );
        
        ?>
        <div class="paywall-anywhere-stripe-keys">
            <div style="margin-bottom: 15px;">
                <label for="stripe_publishable_key"><strong><?php _e( 'Publishable Key', 'paywall-anywhere' ); ?></strong></label>
                <input type="text" 
                       id="stripe_publishable_key"
                       name="paywall_anywhere_stripe_publishable_key" 
                       value="<?php echo esc_attr( $publishable ); ?>" 
                       class="regular-text" 
                       placeholder="pk_test_..." />
                <div class="paywall-anywhere-validation-error" id="publishable-key-error" style="display: none; color: #dc3232; margin-top: 5px;"></div>
            </div>
            
            <div style="margin-bottom: 15px;">
                <label for="stripe_secret_key"><strong><?php _e( 'Secret Key', 'paywall-anywhere' ); ?></strong></label>
                <input type="password" 
                       id="stripe_secret_key"
                       name="paywall_anywhere_stripe_secret_key" 
                       value="<?php echo esc_attr( $secret ); ?>" 
                       class="regular-text"
                       placeholder="sk_test_..." />
                <div class="paywall-anywhere-validation-error" id="secret-key-error" style="display: none; color: #dc3232; margin-top: 5px;"></div>
            </div>
            
            <div style="margin-bottom: 15px;">
                <label for="stripe_webhook_secret"><strong><?php _e( 'Webhook Secret', 'paywall-anywhere' ); ?></strong></label>
                <input type="password" 
                       id="stripe_webhook_secret"
                       name="paywall_anywhere_stripe_webhook_secret" 
                       value="<?php echo esc_attr( $webhook ); ?>" 
                       class="regular-text"
                       placeholder="whsec_..." />
                <div class="paywall-anywhere-validation-error" id="webhook-secret-error" style="display: none; color: #dc3232; margin-top: 5px;"></div>
            </div>
            
            <div>
                <button type="button" 
                        id="test-stripe-connection" 
                        class="button button-secondary paywall-anywhere-test-stripe-btn"
                        <?php echo empty( $publishable ) || empty( $secret ) ? 'disabled' : ''; ?>>
                    <?php _e( 'Test Connection', 'paywall-anywhere' ); ?>
                </button>
                <div id="stripe-test-result" style="margin-top: 10px;"></div>
        </div>
        <?php
    }
    
    /**
     * AJAX handler for testing Stripe connection
     */
    public function ajax_test_stripe_connection() {
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'paywall_anywhere_admin_ajax' ) ) {
            wp_die( 'Security check failed' );
        }
        
        // Check capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Insufficient permissions' ) );
        }
        
        $publishable_key = sanitize_text_field( $_POST['publishable_key'] ?? '' );
        $secret_key = sanitize_text_field( $_POST['secret_key'] ?? '' );
        
        if ( empty( $publishable_key ) || empty( $secret_key ) ) {
            wp_send_json_error( array( 'message' => 'Both publishable and secret keys are required' ) );
        }
        
        // Basic key format validation
        if ( ! preg_match( '/^pk_(test|live)_/', $publishable_key ) ) {
            wp_send_json_error( array( 'message' => 'Invalid publishable key format' ) );
        }
        
        if ( ! preg_match( '/^sk_(test|live)_/', $secret_key ) ) {
            wp_send_json_error( array( 'message' => 'Invalid secret key format' ) );
        }
        
        // Test the connection by making a simple API call
        $test_result = $this->test_stripe_api_connection( $secret_key );
        
        if ( $test_result['success'] ) {
            wp_send_json_success( array( 
                'message' => $test_result['message']
            ) );
        } else {
            wp_send_json_error( array( 
                'message' => $test_result['message'] 
            ) );
        }
    }
    
    /**
     * Test Stripe API connection
     * 
     * @param string $secret_key Stripe secret key
     * @return array Result array with success status and message
     */
    private function test_stripe_api_connection( $secret_key ) {
        $response = wp_remote_get( 'https://api.stripe.com/v1/account', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $secret_key,
                'User-Agent' => 'PaywallAnywhere/1.0.0'
            ),
            'timeout' => 15
        ) );
        
        if ( is_wp_error( $response ) ) {
            return array(
                'success' => false,
                'message' => 'Failed to connect to Stripe API: ' . $response->get_error_message()
            );
        }
        
        $response_code = wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );
        
        if ( $response_code === 200 ) {
            $data = json_decode( $response_body, true );
            if ( isset( $data['email'] ) ) {
                $environment = strpos( $secret_key, '_test_' ) !== false ? 'Test' : 'Live';
                return array(
                    'success' => true,
                    'message' => sprintf( 
                        '%s environment connected successfully. Account: %s', 
                        $environment, 
                        $data['email'] 
                    )
                );
            }
        }
        
        $error_data = json_decode( $response_body, true );
        $error_message = 'Unknown error occurred';
        
        if ( isset( $error_data['error']['message'] ) ) {
            $error_message = $error_data['error']['message'];
        } elseif ( $response_code === 401 ) {
            $error_message = 'Invalid API key';
        } elseif ( $response_code === 403 ) {
            $error_message = 'API key does not have required permissions';
        }
        
        return array(
            'success' => false,
            'message' => $error_message
        );
    }
    
    /**
     * Get display title for an item based on its scope and content
     */
    private function get_item_display_title( $item ) {
        switch ( $item->scope ) {
            case 'post':
                return __( 'Full Post Access', 'paywall-anywhere' );
            case 'block':
                if ( $item->selector ) {
                    return sprintf( __( 'Block: %s', 'paywall-anywhere' ), $this->truncate_text( $item->selector, 30 ) );
                }
                return __( 'Gutenberg Block', 'paywall-anywhere' );
            case 'paragraph':
                if ( $item->selector ) {
                    return sprintf( __( 'Paragraph: %s', 'paywall-anywhere' ), $this->truncate_text( $item->selector, 40 ) );
                }
                return __( 'Text Paragraph', 'paywall-anywhere' );
            case 'media':
                if ( $item->post_id ) {
                    $attachment = get_post( $item->post_id );
                    if ( $attachment ) {
                        return sprintf( __( 'Media: %s', 'paywall-anywhere' ), $attachment->post_title );
                    }
                }
                return __( 'Media File', 'paywall-anywhere' );
            default:
                return sprintf( __( '%s Content', 'paywall-anywhere' ), ucfirst( $item->scope ) );
        }
    }
    
    /**
     * Truncate text with ellipsis
     */
    private function truncate_text( $text, $length = 50 ) {
        if ( strlen( $text ) <= $length ) {
            return $text;
        }
        return substr( $text, 0, $length ) . '...';
    }
    
    /**
     * Scan posts for locked content and register as premium items
     */
    private function scan_and_register_locked_items() {
        $db = \Paywall_Anywhere\Plugin::instance()->db;
        $scanned_count = 0;
        
        // Get all published posts
        $posts = get_posts( array(
            'post_type' => array( 'post', 'page' ),
            'post_status' => 'publish',
            'numberposts' => -1,
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => '_paywall_anywhere_locked',
                    'value' => '1',
                    'compare' => '='
                ),
                array(
                    'key' => '_paywall_anywhere_gate_position',
                    'compare' => 'EXISTS'
                )
            )
        ) );
        
        foreach ( $posts as $post ) {
            // Check if post-level lock exists
            $is_post_locked = get_post_meta( $post->ID, '_paywall_anywhere_locked', true );
            $gate_position = get_post_meta( $post->ID, '_paywall_anywhere_gate_position', true );
            
            if ( $is_post_locked || $gate_position ) {
                // Check if item already exists
                $existing = $db->get_items_by_post( $post->ID );
                $post_item_exists = false;
                
                foreach ( $existing as $item ) {
                    if ( $item->scope === 'post' ) {
                        $post_item_exists = true;
                        break;
                    }
                }
                
                if ( ! $post_item_exists ) {
                    // Create post-level item
                    $default_price = get_option( 'paywall_anywhere_default_price', 500 );
                    $default_currency = get_option( 'paywall_anywhere_default_currency', 'USD' );
                    $default_expires = get_option( 'paywall_anywhere_default_expires_days', 30 );
                    
                    $db->create_item( array(
                        'post_id' => $post->ID,
                        'scope' => 'post',
                        'selector' => '',
                        'price_minor' => $default_price,
                        'currency' => $default_currency,
                        'expires_days' => $default_expires > 0 ? $default_expires : null,
                        'status' => 'active'
                    ) );
                    
                    $scanned_count++;
                }
            }
            
            // Scan post content for shortcodes and locked blocks
            $content = $post->post_content;
            
            // Check for paywall shortcodes
            if ( strpos( $content, '[paywall_anywhere_lock' ) !== false ) {
                preg_match_all( '/\[paywall_anywhere_lock[^\]]*\](.*?)\[\/paywall_anywhere_lock\]/s', $content, $matches );
                
                foreach ( $matches[0] as $index => $match ) {
                    $shortcode_content = $matches[1][$index];
                    
                    // Check if this shortcode item already exists
                    $selector = 'shortcode_' . md5( $match );
                    $existing_shortcode = $wpdb->get_var( $wpdb->prepare(
                        "SELECT id FROM {$db->get_items_table()} WHERE post_id = %d AND selector = %s",
                        $post->ID,
                        $selector
                    ) );
                    
                    if ( ! $existing_shortcode ) {
                        // Parse shortcode attributes
                        $atts = shortcode_parse_atts( str_replace( array( '[paywall_anywhere_lock', ']' ), '', $match ) );
                        $price = isset( $atts['price'] ) ? absint( $atts['price'] ) : get_option( 'paywall_anywhere_default_price', 500 );
                        $expires_days = isset( $atts['expires_days'] ) ? absint( $atts['expires_days'] ) : get_option( 'paywall_anywhere_default_expires_days', 30 );
                        
                        $db->create_item( array(
                            'post_id' => $post->ID,
                            'scope' => 'block',
                            'selector' => $selector,
                            'price_minor' => $price,
                            'currency' => get_option( 'paywall_anywhere_default_currency', 'USD' ),
                            'expires_days' => $expires_days > 0 ? $expires_days : null,
                            'status' => 'active'
                        ) );
                        
                        $scanned_count++;
                    }
                }
            }
            
            // Check for Gutenberg blocks with paywall attributes
            if ( has_blocks( $content ) ) {
                $blocks = parse_blocks( $content );
                $this->scan_blocks_for_paywall( $blocks, $post->ID, $db, $scanned_count );
            }
        }
        
        return $scanned_count;
    }
    
    /**
     * Recursively scan blocks for paywall attributes
     */
    private function scan_blocks_for_paywall( $blocks, $post_id, $db, &$scanned_count ) {
        foreach ( $blocks as $block ) {
            // Check if block has paywall attributes
            if ( isset( $block['attrs']['paywallAnywhereLocked'] ) && $block['attrs']['paywallAnywhereLocked'] ) {
                $selector = 'block_' . $block['blockName'] . '_' . md5( serialize( $block ) );
                
                // Check if this block item already exists
                global $wpdb;
                $existing_block = $wpdb->get_var( $wpdb->prepare(
                    "SELECT id FROM {$db->get_items_table()} WHERE post_id = %d AND selector = %s",
                    $post_id,
                    $selector
                ) );
                
                if ( ! $existing_block ) {
                    $price = isset( $block['attrs']['paywallAnywherePrice'] ) ? absint( $block['attrs']['paywallAnywherePrice'] ) : get_option( 'paywall_anywhere_default_price', 500 );
                    $expires_days = isset( $block['attrs']['paywallAnywhereExpiresDays'] ) ? absint( $block['attrs']['paywallAnywhereExpiresDays'] ) : get_option( 'paywall_anywhere_default_expires_days', 30 );
                    
                    $db->create_item( array(
                        'post_id' => $post_id,
                        'scope' => 'block',
                        'selector' => $selector,
                        'price_minor' => $price,
                        'currency' => get_option( 'paywall_anywhere_default_currency', 'USD' ),
                        'expires_days' => $expires_days > 0 ? $expires_days : null,
                        'status' => 'active'
                    ) );
                    
                    $scanned_count++;
                }
            }
            
            // Recursively scan inner blocks
            if ( ! empty( $block['innerBlocks'] ) ) {
                $this->scan_blocks_for_paywall( $block['innerBlocks'], $post_id, $db, $scanned_count );
            }
        }
    }
}