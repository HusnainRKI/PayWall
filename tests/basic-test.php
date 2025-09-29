<?php
/**
 * Basic Plugin Test
 */

// Simulate WordPress environment
define( 'ABSPATH', '/tmp/' );
define( 'WP_DEBUG', true );

// Mock WordPress functions
function plugin_dir_path( $file ) { return dirname( $file ) . '/'; }
function plugin_dir_url( $file ) { return 'http://example.com/wp-content/plugins/' . basename( dirname( $file ) ) . '/'; }
function add_action() {}
function add_filter() {}
function wp_parse_args( $args, $defaults ) { return array_merge( $defaults, $args ); }
function get_option( $key, $default = false ) { return $default; }
function update_option() { return true; }
function __( $text ) { return $text; }
function esc_attr( $text ) { return $text; }
function esc_html( $text ) { return $text; }
function sanitize_text_field( $text ) { return $text; }
function wp_json_encode( $data ) { return json_encode( $data ); }
function current_user_can() { return false; }
function get_current_user_id() { return 0; }

// Additional WordPress functions needed
function register_activation_hook( $file, $function ) {}
function register_deactivation_hook( $file, $function ) {}

// Mock globals
global $wp_version;
$wp_version = '6.5';

// Set up path constants first (only if not already defined)
if ( ! defined( 'PAYWALL_ANYWHERE_PLUGIN_FILE' ) ) {
    define( 'PAYWALL_ANYWHERE_PLUGIN_FILE', dirname(__DIR__) . '/paywall-anywhere.php' );
}
if ( ! defined( 'PAYWALL_ANYWHERE_PLUGIN_PATH' ) ) {
    define( 'PAYWALL_ANYWHERE_PLUGIN_PATH', dirname(__DIR__) . '/' );
}
if ( ! defined( 'PAYWALL_ANYWHERE_PLUGIN_URL' ) ) {
    define( 'PAYWALL_ANYWHERE_PLUGIN_URL', 'http://example.com/wp-content/plugins/paywall/' );
}
if ( ! defined( 'PAYWALL_ANYWHERE_VERSION' ) ) {
    define( 'PAYWALL_ANYWHERE_VERSION', '1.0.0' );
}

// Test autoloader  
require_once dirname(__DIR__) . '/paywall-anywhere.php';

echo "PayWall Anywhere Plugin Test - Basic Autoloader\n";

try {
    // Test if classes can be loaded
    if ( class_exists( 'Paywall_Anywhere\\Plugin' ) ) {
        echo "✓ Plugin class loaded successfully\n";
    } else {
        echo "✗ Plugin class failed to load\n";
    }
    
    if ( class_exists( 'Paywall_Anywhere\\Data\\Database_Manager' ) ) {
        echo "✓ Database_Manager class loaded successfully\n";
    } else {
        echo "✗ Database_Manager class failed to load\n";
    }
    
    if ( class_exists( 'Paywall_Anywhere\\Data\\Access_Manager' ) ) {
        echo "✓ Access_Manager class loaded successfully\n";
    } else {
        echo "✗ Access_Manager class failed to load\n";
    }
    
    // Test helper functions
    if ( function_exists( 'paywall_anywhere_format_price' ) ) {
        $formatted = paywall_anywhere_format_price( 500, 'USD' );
        if ( $formatted === '$5.00' ) {
            echo "✓ Helper function paywall_anywhere_format_price works correctly\n";
        } else {
            echo "✗ Helper function paywall_anywhere_format_price returned: {$formatted}\n";
        }
    } else {
        echo "✗ Helper function paywall_anywhere_format_price not found\n";
    }
    
    
} catch ( Exception $e ) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

