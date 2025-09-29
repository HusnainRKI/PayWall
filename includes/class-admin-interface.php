<?php
/**
 * Admin Interface Class
 *
 * @package PaywallPremiumContent
 */

namespace Pc;

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
    }
    
    /**
     * Add admin menu pages
     */
    public function add_admin_menu() {
        add_menu_page(
            __( 'PayWall Premium', 'paywall-premium-content' ),
            __( 'PayWall', 'paywall-premium-content' ),
            'manage_options',
            'paywall-premium',
            array( $this, 'render_main_page' ),
            'dashicons-lock',
            30
        );
        
        add_submenu_page(
            'paywall-premium',
            __( 'Premium Items', 'paywall-premium-content' ),
            __( 'Premium Items', 'paywall-premium-content' ),
            'manage_options',
            'paywall-premium',
            array( $this, 'render_main_page' )
        );
        
        add_submenu_page(
            'paywall-premium',
            __( 'Entitlements', 'paywall-premium-content' ),
            __( 'Entitlements', 'paywall-premium-content' ),
            'manage_options',
            'paywall-entitlements',
            array( $this, 'render_entitlements_page' )
        );
        
        add_submenu_page(
            'paywall-premium',
            __( 'Settings', 'paywall-premium-content' ),
            __( 'Settings', 'paywall-premium-content' ),
            'manage_options',
            'paywall-settings',
            array( $this, 'render_settings_page' )
        );
    }
    
    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting( 'pc_settings_group', 'pc_settings' );
        
        // General section
        add_settings_section(
            'pc_general_section',
            __( 'General Settings', 'paywall-premium-content' ),
            null,
            'paywall-settings'
        );
        
        add_settings_field(
            'currency',
            __( 'Default Currency', 'paywall-premium-content' ),
            array( $this, 'render_currency_field' ),
            'paywall-settings',
            'pc_general_section'
        );
        
        add_settings_field(
            'default_price',
            __( 'Default Price (cents)', 'paywall-premium-content' ),
            array( $this, 'render_default_price_field' ),
            'paywall-settings',
            'pc_general_section'
        );
        
        // Stripe section
        add_settings_section(
            'pc_stripe_section',
            __( 'Stripe Settings', 'paywall-premium-content' ),
            null,
            'paywall-settings'
        );
        
        add_settings_field(
            'stripe_enabled',
            __( 'Enable Stripe', 'paywall-premium-content' ),
            array( $this, 'render_stripe_enabled_field' ),
            'paywall-settings',
            'pc_stripe_section'
        );
        
        add_settings_field(
            'stripe_public_key',
            __( 'Stripe Publishable Key', 'paywall-premium-content' ),
            array( $this, 'render_stripe_public_key_field' ),
            'paywall-settings',
            'pc_stripe_section'
        );
        
        add_settings_field(
            'stripe_secret_key',
            __( 'Stripe Secret Key', 'paywall-premium-content' ),
            array( $this, 'render_stripe_secret_key_field' ),
            'paywall-settings',
            'pc_stripe_section'
        );
    }
    
    /**
     * Render main admin page
     */
    public function render_main_page() {
        ?>
        <div class="wrap">
            <h1><?php _e( 'Premium Items', 'paywall-premium-content' ); ?></h1>
            
            <div id="pc-admin-app">
                <!-- React admin interface will be mounted here -->
                <p><?php _e( 'Loading...', 'paywall-premium-content' ); ?></p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render entitlements page
     */
    public function render_entitlements_page() {
        ?>
        <div class="wrap">
            <h1><?php _e( 'Entitlements', 'paywall-premium-content' ); ?></h1>
            
            <div id="pc-entitlements-app">
                <!-- React entitlements interface will be mounted here -->
                <p><?php _e( 'Loading...', 'paywall-premium-content' ); ?></p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e( 'PayWall Settings', 'paywall-premium-content' ); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields( 'pc_settings_group' );
                do_settings_sections( 'paywall-settings' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Render currency field
     */
    public function render_currency_field() {
        $options = get_option( 'pc_settings', array() );
        $currency = $options['currency'] ?? 'USD';
        ?>
        <select name="pc_settings[currency]">
            <option value="USD" <?php selected( $currency, 'USD' ); ?>>USD ($)</option>
            <option value="EUR" <?php selected( $currency, 'EUR' ); ?>>EUR (€)</option>
            <option value="GBP" <?php selected( $currency, 'GBP' ); ?>>GBP (£)</option>
            <option value="JPY" <?php selected( $currency, 'JPY' ); ?>>JPY (¥)</option>
        </select>
        <?php
    }
    
    /**
     * Render default price field
     */
    public function render_default_price_field() {
        $options = get_option( 'pc_settings', array() );
        $default_price = $options['default_price'] ?? 500;
        ?>
        <input type="number" name="pc_settings[default_price]" value="<?php echo esc_attr( $default_price ); ?>" min="1" />
        <p class="description"><?php _e( 'Default price in cents (e.g., 500 = $5.00)', 'paywall-premium-content' ); ?></p>
        <?php
    }
    
    /**
     * Render Stripe enabled field
     */
    public function render_stripe_enabled_field() {
        $options = get_option( 'pc_settings', array() );
        $stripe_enabled = $options['stripe_enabled'] ?? false;
        ?>
        <input type="checkbox" name="pc_settings[stripe_enabled]" value="1" <?php checked( $stripe_enabled ); ?> />
        <label><?php _e( 'Enable Stripe payments', 'paywall-premium-content' ); ?></label>
        <?php
    }
    
    /**
     * Render Stripe public key field
     */
    public function render_stripe_public_key_field() {
        $options = get_option( 'pc_settings', array() );
        $stripe_public_key = $options['stripe_public_key'] ?? '';
        ?>
        <input type="text" name="pc_settings[stripe_public_key]" value="<?php echo esc_attr( $stripe_public_key ); ?>" class="regular-text" />
        <?php
    }
    
    /**
     * Render Stripe secret key field
     */
    public function render_stripe_secret_key_field() {
        $options = get_option( 'pc_settings', array() );
        $stripe_secret_key = $options['stripe_secret_key'] ?? '';
        ?>
        <input type="password" name="pc_settings[stripe_secret_key]" value="<?php echo esc_attr( $stripe_secret_key ); ?>" class="regular-text" />
        <?php
    }
}