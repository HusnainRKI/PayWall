<?php
/**
 * Plugin Name: Paywall Anywhere
 * Plugin URI: https://example.com/paywall-anywhere
 * Description: Lock whole posts, specific blocks, single paragraphs, or media—anywhere in WordPress. Server-side, secure, Stripe & WooCommerce ready.
 * Version: 1.0.0
 * Author: {Your Name/Brand}
 * Author URI: https://example.com
 * Text Domain: paywall-anywhere
 * Domain Path: /languages
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define constants
define( 'PAYWALL_ANYWHERE_PLUGIN_FILE', __FILE__ );
define( 'PAYWALL_ANYWHERE_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'PAYWALL_ANYWHERE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'PAYWALL_ANYWHERE_VERSION', '1.0.0' );
define( 'PAYWALL_ANYWHERE_MIN_WP_VERSION', '6.5' );
define( 'PAYWALL_ANYWHERE_MIN_PHP_VERSION', '8.1' );

// Check WordPress and PHP version compatibility
if ( ! paywall_anywhere_is_compatible() ) {
    return;
}

// Autoloader for classes
spl_autoload_register( 'paywall_anywhere_autoloader' );

// Load helper functions
require_once PAYWALL_ANYWHERE_PLUGIN_PATH . 'includes/functions-paywall-anywhere-helpers.php';

/**
 * Autoloader for Paywall Anywhere classes
 *
 * @param string $class_name The class name to load.
 */
function paywall_anywhere_autoloader( $class_name ) {
    if ( strpos( $class_name, 'Paywall_Anywhere\\' ) !== 0 ) {
        return;
    }
    
    // Define namespace to file mapping
    $namespace_mapping = array(
        'Paywall_Anywhere\\Data\\Database_Manager' => 'database-manager',
        'Paywall_Anywhere\\Data\\Access_Manager' => 'access-manager',
        'Paywall_Anywhere\\Rendering\\Content_Filter' => 'content-filter',
        'Paywall_Anywhere\\Blocks\\Block_Manager' => 'block-manager',
        'Paywall_Anywhere\\Payments\\Payment_Manager' => 'payment-manager',
        'Paywall_Anywhere\\Admin\\Admin_Interface' => 'admin-interface',
        'Paywall_Anywhere\\Rest\\Api_Controller' => 'rest-api-controller',
        'Paywall_Anywhere\\Activator' => 'activator',
        'Paywall_Anywhere\\Deactivator' => 'deactivator',
        'Paywall_Anywhere\\Plugin' => 'plugin',
        'Paywall_Anywhere\\Paywall_Anywhere_Shortcodes' => 'shortcodes',
    );
    
    if ( isset( $namespace_mapping[ $class_name ] ) ) {
        $class_file = PAYWALL_ANYWHERE_PLUGIN_PATH . 'includes/class-paywall-anywhere-' . $namespace_mapping[ $class_name ] . '.php';
        if ( file_exists( $class_file ) ) {
            require_once $class_file;
        }
    } else {
        // Fallback to original logic for other classes
        $class_name = str_replace( 'Paywall_Anywhere\\', '', $class_name );
        $class_name = str_replace( '_', '-', $class_name );
        $class_name = str_replace( '\\', '-', $class_name );
        $class_file = PAYWALL_ANYWHERE_PLUGIN_PATH . 'includes/class-paywall-anywhere-' . strtolower( $class_name ) . '.php';
        
        if ( file_exists( $class_file ) ) {
            require_once $class_file;
        }
    }
}

/**
 * Check if the environment is compatible with Paywall Anywhere
 *
 * @return bool
 */
function paywall_anywhere_is_compatible() {
    global $wp_version;
    
    if ( version_compare( PHP_VERSION, PAYWALL_ANYWHERE_MIN_PHP_VERSION, '<' ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="error"><p>' . 
                 sprintf( 
                     __( 'Paywall Anywhere requires PHP %s or higher. You are running PHP %s.', 'paywall-anywhere' ),
                     PAYWALL_ANYWHERE_MIN_PHP_VERSION,
                     PHP_VERSION 
                 ) . 
                 '</p></div>';
        });
        return false;
    }
    
    if ( version_compare( $wp_version, PAYWALL_ANYWHERE_MIN_WP_VERSION, '<' ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="error"><p>' . 
                 sprintf( 
                     __( 'Paywall Anywhere requires WordPress %s or higher. You are running WordPress %s.', 'paywall-anywhere' ),
                     PAYWALL_ANYWHERE_MIN_WP_VERSION,
                     $GLOBALS['wp_version'] 
                 ) . 
                 '</p></div>';
        });
        return false;
    }
    
    return true;
}

/**
 * Initialize the plugin
 */
function paywall_anywhere_init() {
    // Load text domain for translations
    load_plugin_textdomain( 'paywall-anywhere', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    
    // Initialize main plugin class
    Paywall_Anywhere\Plugin::instance();
}

// Hook plugin initialization
add_action( 'plugins_loaded', 'paywall_anywhere_init' );

// Activation hook
register_activation_hook( __FILE__, function() {
    require_once PAYWALL_ANYWHERE_PLUGIN_PATH . 'includes/class-paywall-anywhere-activator.php';
    Paywall_Anywhere\Activator::activate();
});

// Deactivation hook
register_deactivation_hook( __FILE__, function() {
    require_once PAYWALL_ANYWHERE_PLUGIN_PATH . 'includes/class-paywall-anywhere-deactivator.php';
    Paywall_Anywhere\Deactivator::deactivate();
});