<?php
/**
 * Main Plugin Class
 *
 * @package Paywall_Anywhere
 */

namespace Paywall_Anywhere;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Main Plugin Class
 */
class Plugin {
    
    /**
     * Single instance of this class
     *
     * @var Plugin|null
     */
    private static $instance = null;
    
    /**
     * Database manager instance
     *
     * @var Data\Database_Manager
     */
    public $db;
    
    /**
     * Access manager instance
     *
     * @var Data\Access_Manager
     */
    public $access;
    
    /**
     * Content filter instance
     *
     * @var Rendering\Content_Filter
     */
    public $content_filter;
    
    /**
     * Block manager instance
     *
     * @var Blocks\Block_Manager
     */
    public $blocks;
    
    /**
     * Payment manager instance
     *
     * @var Payments\Payment_Manager
     */
    public $payments;
    
    /**
     * Admin interface instance
     *
     * @var Admin\Admin_Interface
     */
    public $admin;
    
    /**
     * Shortcodes instance
     *
     * @var Paywall_Anywhere_Shortcodes
     */
    public $shortcodes;
    
    /**
     * REST API controller instance
     *
     * @var Rest\Api_Controller
     */
    public $rest_api;
    
    /**
     * Get single instance
     *
     * @return Plugin
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_components();
        $this->setup_hooks();
    }
    
    /**
     * Initialize plugin components
     */
    private function init_components() {
        // Initialize managers in the correct order
        $this->db = new Data\Database_Manager();
        $this->access = new Data\Access_Manager();
        $this->content_filter = new Paywall_Anywhere_Content_Filter();
        $this->blocks = new Paywall_Anywhere_Block_Manager();
        $this->payments = new Paywall_Anywhere_Payment_Manager();
        $this->admin = new Paywall_Anywhere_Admin_Interface();
        $this->shortcodes = new Paywall_Anywhere_Shortcodes();
        $this->rest_api = new Paywall_Anywhere_Rest_Api_Controller();
    }
    
    /**
     * Setup WordPress hooks
     */
    private function setup_hooks() {
        add_action( 'init', array( $this, 'init' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
        add_action( 'rest_api_init', array( $this->rest_api, 'register_routes' ) );
    }
    
    /**
     * Initialize plugin components
     */
    public function init() {
        // Register blocks
        $this->blocks->register_blocks();
        
        // Initialize content filtering
        $this->content_filter->init();
        
        // Initialize payment handlers
        $this->payments->init();
        
        // Initialize shortcodes
        $this->shortcodes->init();
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_frontend_scripts() {
        wp_enqueue_style( 
            'paywall-anywhere-frontend', 
            PAYWALL_ANYWHERE_PLUGIN_URL . 'assets/css/frontend.css', 
            array(), 
            PAYWALL_ANYWHERE_VERSION 
        );
        
        wp_enqueue_script( 
            'paywall-anywhere-frontend', 
            PAYWALL_ANYWHERE_PLUGIN_URL . 'assets/js/frontend.js', 
            array( 'jquery' ), 
            PAYWALL_ANYWHERE_VERSION, 
            true 
        );
        
        wp_localize_script( 'paywall-anywhere-frontend', 'paywall_anywhere_ajax', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'paywall_anywhere_nonce' ),
            'strings' => array(
                'loading' => __( 'Loading...', 'paywall-anywhere' ),
                'error' => __( 'An error occurred. Please try again.', 'paywall-anywhere' ),
            )
        ));
    }
    
    /**
     * Enqueue admin scripts and styles
     *
     * @param string $hook Current admin page hook.
     */
    public function enqueue_admin_scripts( $hook ) {
        wp_enqueue_style( 
            'paywall-anywhere-admin', 
            PAYWALL_ANYWHERE_PLUGIN_URL . 'assets/css/admin.css', 
            array(), 
            PAYWALL_ANYWHERE_VERSION 
        );
        
        // Enqueue editor scripts for Gutenberg integration
        if ( $hook === 'post.php' || $hook === 'post-new.php' ) {
            wp_enqueue_script( 
                'paywall-anywhere-editor', 
                PAYWALL_ANYWHERE_PLUGIN_URL . 'assets/js/editor.js', 
                array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor', 'wp-components', 'wp-data' ), 
                PAYWALL_ANYWHERE_VERSION, 
                true 
            );
            
            wp_localize_script( 'paywall-anywhere-editor', 'paywall_anywhere_editor', array(
                'rest_url' => get_rest_url( null, 'paywall-anywhere/v1/' ),
                'nonce' => wp_create_nonce( 'wp_rest' ),
                'currencies' => $this->get_supported_currencies(),
                'post_id' => get_the_ID(),
            ));
        }
    }
    
    /**
     * Get supported currencies
     *
     * @return array
     */
    public function get_supported_currencies() {
        return array(
            'USD' => array( 'name' => 'US Dollar', 'symbol' => '$' ),
            'EUR' => array( 'name' => 'Euro', 'symbol' => '€' ),
            'GBP' => array( 'name' => 'British Pound', 'symbol' => '£' ),
            'JPY' => array( 'name' => 'Japanese Yen', 'symbol' => '¥' ),
        );
    }
    
    /**
     * Get plugin option
     *
     * @param string $key Option key.
     * @param mixed  $default Default value.
     * @return mixed
     */
    public function get_option( $key, $default = null ) {
        return get_option( 'paywall_anywhere_' . $key, $default );
    }
    
    /**
     * Update plugin option
     *
     * @param string $key Option key.
     * @param mixed  $value Option value.
     * @return bool
     */
    public function update_option( $key, $value ) {
        return update_option( 'paywall_anywhere_' . $key, $value );
    }
}