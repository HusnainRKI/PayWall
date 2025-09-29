<?php
/**
 * Uninstall Paywall Anywhere
 *
 * This file is executed when the plugin is uninstalled.
 * It removes all plugin data including database tables, options, and post meta.
 *
 * @package Paywall_Anywhere
 */

// Prevent direct access
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Security check: only proceed if confirmed
if ( ! get_option( 'paywall_anywhere_confirm_uninstall' ) ) {
    return;
}

/**
 * Remove all plugin data
 */
function paywall_anywhere_uninstall_cleanup() {
    global $wpdb;
    
    // Remove database tables
    $items_table = $wpdb->prefix . 'paywall_anywhere_items';
    $entitlements_table = $wpdb->prefix . 'paywall_anywhere_entitlements';
    
    $wpdb->query( "DROP TABLE IF EXISTS $entitlements_table" );
    $wpdb->query( "DROP TABLE IF EXISTS $items_table" );
    
    // Remove all plugin options
    $options = array(
        'paywall_anywhere_version',
        'paywall_anywhere_activation_time',
        'paywall_anywhere_deactivation_time',
        'paywall_anywhere_default_currency',
        'paywall_anywhere_default_price',
        'paywall_anywhere_default_expires_days',
        'paywall_anywhere_teaser_length',
        'paywall_anywhere_stripe_enabled',
        'paywall_anywhere_stripe_publishable_key',
        'paywall_anywhere_stripe_secret_key',
        'paywall_anywhere_stripe_webhook_secret',
        'paywall_anywhere_woocommerce_enabled',
        'paywall_anywhere_magic_link_ttl',
        'paywall_anywhere_confirm_uninstall',
    );
    
    foreach ( $options as $option ) {
        delete_option( $option );
    }
    
    // Remove post meta
    $wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key LIKE 'paywall_anywhere_%'" );
    $wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key LIKE '_paywall_anywhere_%'" );
    
    // Remove user meta
    $wpdb->query( "DELETE FROM $wpdb->usermeta WHERE meta_key LIKE 'paywall_anywhere_%'" );
    
    // Clear transients
    $wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_paywall_anywhere_%'" );
    $wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_timeout_paywall_anywhere_%'" );
    
    // Clear any cached data
    if ( function_exists( 'wp_cache_flush' ) ) {
        wp_cache_flush();
    }
}

// Execute cleanup
paywall_anywhere_uninstall_cleanup();