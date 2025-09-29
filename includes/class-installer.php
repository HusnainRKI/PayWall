<?php
/**
 * Plugin Installer Class
 *
 * @package PaywallPremiumContent
 */

namespace Pc;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Installer Class
 */
class Installer {
    
    /**
     * Plugin activation
     */
    public static function activate() {
        self::create_tables();
        self::create_default_options();
        self::schedule_events();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Set activation flag
        update_option( 'pc_activated', true );
    }
    
    /**
     * Plugin deactivation
     */
    public static function deactivate() {
        self::clear_scheduled_events();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        delete_option( 'pc_activated' );
    }
    
    /**
     * Plugin uninstall
     */
    public static function uninstall() {
        if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
            return;
        }
        
        // Remove tables
        self::drop_tables();
        
        // Remove options
        self::remove_options();
        
        // Remove user meta
        self::remove_user_meta();
        
        // Remove post meta
        self::remove_post_meta();
    }
    
    /**
     * Create custom database tables
     */
    private static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Premium items table
        $items_table = $wpdb->prefix . 'premium_items';
        $items_sql = "CREATE TABLE $items_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            site_id bigint(20) unsigned NOT NULL DEFAULT 1,
            post_id bigint(20) unsigned NOT NULL,
            scope enum('post','block','paragraph','media','route_print') NOT NULL DEFAULT 'post',
            selector text,
            price_minor int(11) unsigned NOT NULL DEFAULT 0,
            currency char(3) NOT NULL DEFAULT 'USD',
            expires_days int(11) unsigned DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            status enum('active','archived') NOT NULL DEFAULT 'active',
            PRIMARY KEY (id),
            KEY site_id (site_id),
            KEY post_id (post_id),
            KEY scope (scope),
            KEY status (status)
        ) $charset_collate;";
        
        // Entitlements table
        $entitlements_table = $wpdb->prefix . 'premium_entitlements';
        $entitlements_sql = "CREATE TABLE $entitlements_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned DEFAULT NULL,
            guest_email varchar(100) DEFAULT NULL,
            item_id bigint(20) unsigned NOT NULL,
            granted_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            expires_at datetime DEFAULT NULL,
            source enum('stripe','woocommerce','manual') NOT NULL DEFAULT 'manual',
            token_hash varchar(64) DEFAULT NULL,
            meta longtext,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY guest_email (guest_email),
            KEY item_id (item_id),
            KEY token_hash (token_hash),
            KEY expires_at (expires_at),
            FOREIGN KEY (item_id) REFERENCES $items_table(id) ON DELETE CASCADE
        ) $charset_collate;";
        
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $items_sql );
        dbDelta( $entitlements_sql );
        
        // Update database version
        update_option( 'pc_db_version', PC_VERSION );
    }
    
    /**
     * Create default plugin options
     */
    private static function create_default_options() {
        $default_settings = array(
            'currency' => 'USD',
            'default_price' => 500, // $5.00 in cents
            'default_expires_days' => 30,
            'stripe_enabled' => false,
            'stripe_public_key' => '',
            'stripe_secret_key' => '',
            'woocommerce_enabled' => false,
            'teaser_mode' => 'paragraphs',
            'teaser_count' => 2,
            'magic_link_ttl' => 24, // hours
            'blur_strength' => 5,
            'watermark_enabled' => true,
            'cache_compatibility' => true,
        );
        
        update_option( 'pc_settings', $default_settings );
    }
    
    /**
     * Schedule recurring events
     */
    private static function schedule_events() {
        // Schedule cleanup of expired entitlements
        if ( ! wp_next_scheduled( 'pc_cleanup_expired' ) ) {
            wp_schedule_event( time(), 'daily', 'pc_cleanup_expired' );
        }
        
        // Schedule cleanup of expired magic tokens
        if ( ! wp_next_scheduled( 'pc_cleanup_tokens' ) ) {
            wp_schedule_event( time(), 'hourly', 'pc_cleanup_tokens' );
        }
    }
    
    /**
     * Clear scheduled events
     */
    private static function clear_scheduled_events() {
        wp_clear_scheduled_hook( 'pc_cleanup_expired' );
        wp_clear_scheduled_hook( 'pc_cleanup_tokens' );
    }
    
    /**
     * Drop custom tables
     */
    private static function drop_tables() {
        global $wpdb;
        
        $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}premium_entitlements" );
        $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}premium_items" );
    }
    
    /**
     * Remove plugin options
     */
    private static function remove_options() {
        delete_option( 'pc_settings' );
        delete_option( 'pc_db_version' );
        delete_option( 'pc_activated' );
    }
    
    /**
     * Remove user meta data
     */
    private static function remove_user_meta() {
        delete_metadata( 'user', 0, 'pc_entitlements', '', true );
    }
    
    /**
     * Remove post meta data
     */
    private static function remove_post_meta() {
        delete_metadata( 'post', 0, '_pc_teaser_html', '', true );
        delete_metadata( 'post', 0, '_pc_mode', '', true );
        delete_metadata( 'post', 0, '_pc_locked_map', '', true );
    }
}