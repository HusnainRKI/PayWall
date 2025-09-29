<?php
/**
 * Plugin Deactivator Class
 *
 * @package Paywall_Anywhere
 */

namespace Paywall_Anywhere;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Plugin Deactivator Class
 */
class Deactivator {
    
    /**
     * Deactivate the plugin
     *
     * Clean up temporary data and flush rewrite rules.
     */
    public static function deactivate() {
        // Clear any cached data
        self::clear_cache();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Set deactivation flag
        update_option( 'paywall_anywhere_deactivation_time', time() );
    }
    
    /**
     * Clear plugin cache and transients
     */
    private static function clear_cache() {
        // Delete transients
        delete_transient( 'paywall_anywhere_items_cache' );
        delete_transient( 'paywall_anywhere_stats_cache' );
        
        // Clear object cache if available
        if ( function_exists( 'wp_cache_flush' ) ) {
            wp_cache_flush();
        }
    }
}