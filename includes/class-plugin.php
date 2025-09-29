<?php
/**
 * Main Plugin Class
 *
 * @package PaywallPremiumContent
 */

namespace Pc;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Main Plugin Class
 */
class Plugin {
    
    /**
     * Single instance of this class
     */
    private static $instance = null;
    
    /**
     * Database manager instance
     */
    public $db;
    
    /**
     * Access manager instance
     */
    public $access;
    
    /**
     * Content filter instance
     */
    public $content_filter;
    
    /**
     * Block manager instance
     */
    public $blocks;
    
    /**
     * Payment manager instance
     */
    public $payments;
    
    /**
     * Admin interface instance
     */
    public $admin;
    
    /**
     * Shortcodes instance
     */
    public $shortcodes;
    
    /**
     * Get single instance
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
        $this->init_hooks();
        $this->init_components();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action( 'init', array( $this, 'init' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
    }
    
    /**
     * Initialize plugin components
     */
    private function init_components() {
        $this->db = new Database_Manager();
        $this->access = new Access_Manager();
        $this->content_filter = new Content_Filter();
        $this->blocks = new Block_Manager();
        $this->payments = new Payment_Manager();
        
        if ( is_admin() ) {
            $this->admin = new Admin_Interface();
        }
        
        $this->shortcodes = new Shortcodes();
    }
    
    /**
     * Initialize plugin on WordPress init
     */
    public function init() {
        // Register blocks
        $this->blocks->register_blocks();
        
        // Initialize content filtering
        $this->content_filter->init();
        
        // Initialize payment handlers
        $this->payments->init();
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_frontend_scripts() {
        wp_enqueue_style( 
            'pc-frontend', 
            PC_PLUGIN_URL . 'assets/css/frontend.css', 
            array(), 
            PC_VERSION 
        );
        
        wp_enqueue_script( 
            'pc-frontend', 
            PC_PLUGIN_URL . 'assets/js/frontend.js', 
            array( 'jquery' ), 
            PC_VERSION, 
            true 
        );
        
        wp_localize_script( 'pc-frontend', 'pc_ajax', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'pc_nonce' ),
            'strings' => array(
                'loading' => __( 'Loading...', 'paywall-premium-content' ),
                'error' => __( 'An error occurred. Please try again.', 'paywall-premium-content' ),
            )
        ));
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts( $hook ) {
        wp_enqueue_style( 
            'pc-admin', 
            PC_PLUGIN_URL . 'assets/css/admin.css', 
            array(), 
            PC_VERSION 
        );
        
        // Enqueue editor scripts for Gutenberg integration
        if ( $hook === 'post.php' || $hook === 'post-new.php' ) {
            wp_enqueue_script( 
                'pc-editor', 
                PC_PLUGIN_URL . 'assets/js/editor.js', 
                array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor', 'wp-components', 'wp-data' ), 
                PC_VERSION, 
                true 
            );
            
            wp_localize_script( 'pc-editor', 'pc_editor', array(
                'rest_url' => get_rest_url( null, 'pc/v1/' ),
                'nonce' => wp_create_nonce( 'wp_rest' ),
            ));
        }
    }
    
    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        $rest_controller = new Rest_Api_Controller();
        $rest_controller->register_routes();
    }
    
    /**
     * Get plugin option with default fallback
     */
    public function get_option( $key, $default = false ) {
        $options = get_option( 'pc_settings', array() );
        return isset( $options[ $key ] ) ? $options[ $key ] : $default;
    }
    
    /**
     * Update plugin option
     */
    public function update_option( $key, $value ) {
        $options = get_option( 'pc_settings', array() );
        $options[ $key ] = $value;
        update_option( 'pc_settings', $options );
    }
}