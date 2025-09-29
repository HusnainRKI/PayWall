<?php
/**
 * Plugin Activator Class
 *
 * @package Paywall_Anywhere
 */

namespace Paywall_Anywhere;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Plugin Activator Class
 */
class Activator {
    
    /**
     * Activate the plugin
     *
     * Create database tables, set default options, and flush rewrite rules.
     */
    public static function activate() {
        // Create database tables
        self::create_tables();
        
        // Set default options
        self::set_default_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Set activation flag
        update_option( 'paywall_anywhere_activation_time', time() );
    }
    
    /**
     * Create database tables
     */
    private static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Items table
        $items_table = $wpdb->prefix . 'paywall_anywhere_items';
        $items_sql = "CREATE TABLE $items_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            site_id bigint(20) NOT NULL DEFAULT 1,
            post_id bigint(20) NOT NULL DEFAULT 0,
            scope enum('post','block','paragraph','media','route_print') NOT NULL DEFAULT 'post',
            selector text,
            price_minor int(11) NOT NULL DEFAULT 0,
            currency char(3) NOT NULL DEFAULT 'USD',
            expires_days int(11) DEFAULT NULL,
            status enum('active','archived') NOT NULL DEFAULT 'active',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY site_post (site_id, post_id),
            KEY scope_selector (scope, selector(100)),
            KEY status (status)
        ) $charset_collate;";
        
        // Entitlements table
        $entitlements_table = $wpdb->prefix . 'paywall_anywhere_entitlements';
        $entitlements_sql = "CREATE TABLE $entitlements_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) DEFAULT NULL,
            guest_email varchar(100) DEFAULT NULL,
            item_id bigint(20) NOT NULL,
            granted_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            expires_at datetime DEFAULT NULL,
            source enum('stripe','woocommerce','manual') NOT NULL DEFAULT 'manual',
            token_hash varchar(64) DEFAULT NULL,
            meta json DEFAULT NULL,
            PRIMARY KEY (id),
            KEY user_item (user_id, item_id),
            KEY guest_item (guest_email, item_id),
            KEY token_hash (token_hash),
            KEY expires_at (expires_at),
            FOREIGN KEY (item_id) REFERENCES $items_table(id) ON DELETE CASCADE
        ) $charset_collate;";
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $items_sql );
        dbDelta( $entitlements_sql );
    }
    
    /**
     * Set default plugin options
     */
    private static function set_default_options() {
        $defaults = array(
            'default_currency' => 'USD',
            'default_price' => 500, // $5.00 in cents
            'default_expires_days' => 30,
            'teaser_length' => 150,
            'stripe_enabled' => false,
            'woocommerce_enabled' => false,
            'magic_link_ttl' => 3600, // 1 hour
        );
        
        foreach ( $defaults as $key => $value ) {
            if ( false === get_option( 'paywall_anywhere_' . $key ) ) {
                update_option( 'paywall_anywhere_' . $key, $value );
            }
        }
    }
}