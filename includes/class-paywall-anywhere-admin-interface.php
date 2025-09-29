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
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'paywall_anywhere_settings' );
                do_settings_sections( 'paywall_anywhere_settings' );
                submit_button();
                ?>
            </form>
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
            }
        }
        
        global $wpdb;
        $items = $wpdb->get_results( "SELECT * FROM {$db->get_items_table()} ORDER BY created_at DESC LIMIT 100" );
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e( 'ID', 'paywall-anywhere' ); ?></th>
                        <th><?php _e( 'Post', 'paywall-anywhere' ); ?></th>
                        <th><?php _e( 'Scope', 'paywall-anywhere' ); ?></th>
                        <th><?php _e( 'Price', 'paywall-anywhere' ); ?></th>
                        <th><?php _e( 'Status', 'paywall-anywhere' ); ?></th>
                        <th><?php _e( 'Created', 'paywall-anywhere' ); ?></th>
                        <th><?php _e( 'Actions', 'paywall-anywhere' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $items ) ) : ?>
                        <tr>
                            <td colspan="7"><?php _e( 'No premium items found.', 'paywall-anywhere' ); ?></td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ( $items as $item ) : ?>
                            <tr>
                                <td><?php echo esc_html( $item->id ); ?></td>
                                <td>
                                    <?php if ( $item->post_id ) : ?>
                                        <a href="<?php echo esc_url( get_edit_post_link( $item->post_id ) ); ?>">
                                            <?php echo esc_html( get_the_title( $item->post_id ) ); ?>
                                        </a>
                                    <?php else : ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html( $item->scope ); ?></td>
                                <td><?php echo esc_html( \paywall_anywhere_format_price( $item->price_minor, $item->currency ) ); ?></td>
                                <td>
                                    <span class="paywall-anywhere-status paywall-anywhere-status-<?php echo esc_attr( $item->status ); ?>">
                                        <?php echo esc_html( ucfirst( $item->status ) ); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html( $item->created_at ); ?></td>
                                <td>
                                    <form method="post" style="display: inline;">
                                        <?php wp_nonce_field( 'paywall_anywhere_admin_action', 'paywall_anywhere_nonce' ); ?>
                                        <input type="hidden" name="action" value="delete_item">
                                        <input type="hidden" name="item_id" value="<?php echo esc_attr( $item->id ); ?>">
                                        <button type="submit" class="button button-small" onclick="return confirm('<?php esc_attr_e( 'Are you sure?', 'paywall-anywhere' ); ?>')">
                                            <?php _e( 'Delete', 'paywall-anywhere' ); ?>
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
}