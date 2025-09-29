<?php
/**
 * Plugin Name: PayWall Premium Content
 * Plugin URI: https://github.com/HusnainRKI/PayWall
 * Description: A self-hosted WordPress plugin that lets site admins lock premium content until payment with granular control over posts, blocks, paragraphs, and media.
 * Version: 1.0.0
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Author: HusnainRKI
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: paywall-premium-content
 * Domain Path: /languages
 *
 * @package PaywallPremiumContent
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define constants
define( 'PC_PLUGIN_FILE', __FILE__ );
define( 'PC_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'PC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'PC_VERSION', '1.0.0' );
define( 'PC_MIN_WP_VERSION', '6.5' );
define( 'PC_MIN_PHP_VERSION', '8.1' );

// Check WordPress and PHP version compatibility
if ( ! pc_is_compatible() ) {
    return;
}

// Autoloader for classes
spl_autoload_register( 'pc_autoloader' );

// Load helper functions
require_once PC_PLUGIN_PATH . 'includes/helper-functions.php';

function pc_autoloader( $class_name ) {
    if ( strpos( $class_name, 'Pc\\' ) !== 0 ) {
        return;
    }
    
    $class_name = str_replace( 'Pc\\', '', $class_name );
    $class_name = str_replace( '_', '-', $class_name );
    $class_file = PC_PLUGIN_PATH . 'includes/class-' . strtolower( $class_name ) . '.php';
    
    if ( file_exists( $class_file ) ) {
        require_once $class_file;
    }
}

/**
 * Check if the environment is compatible with the plugin
 */
function pc_is_compatible() {
    global $wp_version;
    
    if ( version_compare( PHP_VERSION, PC_MIN_PHP_VERSION, '<' ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="error"><p>' . 
                 sprintf( 
                     __( 'PayWall Premium Content requires PHP %s or higher. You are running PHP %s.', 'paywall-premium-content' ),
                     PC_MIN_PHP_VERSION,
                     PHP_VERSION 
                 ) . 
                 '</p></div>';
        });
        return false;
    }
    
    if ( version_compare( $wp_version, PC_MIN_WP_VERSION, '<' ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="error"><p>' . 
                 sprintf( 
                     __( 'PayWall Premium Content requires WordPress %s or higher. You are running WordPress %s.', 'paywall-premium-content' ),
                     PC_MIN_WP_VERSION,
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
function pc_init() {
    // Load text domain for translations
    load_plugin_textdomain( 'paywall-premium-content', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    
    // Initialize main plugin class
    Pc\Plugin::instance();
}

// Hook plugin initialization
add_action( 'plugins_loaded', 'pc_init' );

// Activation hook
register_activation_hook( __FILE__, function() {
    Pc\Installer::activate();
});

// Deactivation hook
register_deactivation_hook( __FILE__, function() {
    Pc\Installer::deactivate();
});

// Uninstall hook
register_uninstall_hook( __FILE__, function() {
    Pc\Installer::uninstall();
});