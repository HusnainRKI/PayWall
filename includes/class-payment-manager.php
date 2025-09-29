<?php
/**
 * Payment Manager Class
 *
 * @package PaywallPremiumContent
 */

namespace Pc;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Payment Manager Class
 */
class Payment_Manager {
    
    /**
     * Initialize payment handlers
     */
    public function init() {
        // AJAX handlers for purchase
        add_action( 'wp_ajax_pc_create_payment', array( $this, 'handle_create_payment' ) );
        add_action( 'wp_ajax_nopriv_pc_create_payment', array( $this, 'handle_create_payment' ) );
        
        // Stripe webhook handler
        add_action( 'wp_ajax_pc_stripe_webhook', array( $this, 'handle_stripe_webhook' ) );
        add_action( 'wp_ajax_nopriv_pc_stripe_webhook', array( $this, 'handle_stripe_webhook' ) );
        
        // WooCommerce integration
        add_action( 'woocommerce_order_status_completed', array( $this, 'handle_woocommerce_completion' ) );
    }
    
    /**
     * Handle payment creation request
     */
    public function handle_create_payment() {
        check_ajax_referer( 'pc_nonce', 'nonce' );
        
        $item_id = absint( $_POST['item_id'] ?? 0 );
        $email = sanitize_email( $_POST['email'] ?? '' );
        
        if ( ! $item_id || ! $email ) {
            wp_die( json_encode( array( 'error' => 'Invalid parameters' ) ) );
        }
        
        $db = Plugin::instance()->db;
        $item = $db->get_item( $item_id );
        
        if ( ! $item ) {
            wp_die( json_encode( array( 'error' => 'Item not found' ) ) );
        }
        
        // Check if Stripe is enabled
        if ( Plugin::instance()->get_option( 'stripe_enabled' ) ) {
            $result = $this->create_stripe_session( $item, $email );
        } else {
            $result = array( 'error' => 'No payment gateway enabled' );
        }
        
        wp_die( json_encode( $result ) );
    }
    
    /**
     * Create Stripe checkout session
     */
    private function create_stripe_session( $item, $email ) {
        $stripe_secret = Plugin::instance()->get_option( 'stripe_secret_key' );
        
        if ( ! $stripe_secret ) {
            return array( 'error' => 'Stripe not configured' );
        }
        
        // This is a simplified implementation
        // In a real implementation, you would use the Stripe PHP SDK
        return array(
            'success' => true,
            'checkout_url' => '#stripe-checkout',
            'session_id' => 'mock_session_' . $item->id
        );
    }
    
    /**
     * Handle Stripe webhook
     */
    public function handle_stripe_webhook() {
        // Verify webhook signature and process payment completion
        // This is a placeholder for the full Stripe webhook implementation
        
        $payload = file_get_contents( 'php://input' );
        $event = json_decode( $payload, true );
        
        if ( $event['type'] === 'checkout.session.completed' ) {
            $this->process_stripe_completion( $event['data']['object'] );
        }
        
        http_response_code( 200 );
        exit;
    }
    
    /**
     * Process Stripe payment completion
     */
    private function process_stripe_completion( $session ) {
        // Extract metadata and create entitlement
        $item_id = $session['metadata']['item_id'] ?? 0;
        $email = $session['customer_details']['email'] ?? '';
        
        if ( $item_id && $email ) {
            $this->create_entitlement_with_magic_link( $item_id, $email, 'stripe' );
        }
    }
    
    /**
     * Handle WooCommerce order completion
     */
    public function handle_woocommerce_completion( $order_id ) {
        $order = wc_get_order( $order_id );
        
        if ( ! $order ) {
            return;
        }
        
        // Check if order contains premium items
        foreach ( $order->get_items() as $item ) {
            $product_id = $item->get_product_id();
            $item_id = get_post_meta( $product_id, '_pc_item_id', true );
            
            if ( $item_id ) {
                $email = $order->get_billing_email();
                $this->create_entitlement_with_magic_link( $item_id, $email, 'woocommerce' );
            }
        }
    }
    
    /**
     * Create entitlement and send magic link
     */
    private function create_entitlement_with_magic_link( $item_id, $email, $source ) {
        $db = Plugin::instance()->db;
        $access_manager = Plugin::instance()->access;
        $item = $db->get_item( $item_id );
        
        if ( ! $item ) {
            return false;
        }
        
        // Generate token and calculate expiry
        $token = $access_manager->generate_token();
        $token_hash = $access_manager->hash_token( $token );
        
        $expires_at = null;
        if ( $item->expires_days ) {
            $expires_at = gmdate( 'Y-m-d H:i:s', time() + ( $item->expires_days * DAY_IN_SECONDS ) );
        }
        
        // Create entitlement
        $entitlement_id = $db->create_entitlement( array(
            'guest_email' => $email,
            'item_id' => $item_id,
            'expires_at' => $expires_at,
            'source' => $source,
            'token_hash' => $token_hash,
        ));
        
        if ( $entitlement_id ) {
            // Send magic link email
            $this->send_magic_link_email( $email, $token, $item );
            return true;
        }
        
        return false;
    }
    
    /**
     * Send magic link email
     */
    private function send_magic_link_email( $email, $token, $item ) {
        $post = get_post( $item->post_id );
        $magic_link = add_query_arg( 'pc_token', $token, get_permalink( $item->post_id ) );
        
        $subject = sprintf( 
            __( 'Your premium content access for "%s"', 'paywall-premium-content' ),
            $post->post_title 
        );
        
        $message = sprintf(
            __( "Thank you for your purchase!\n\nClick the link below to access your premium content:\n%s\n\nThis link will expire in %d hours.", 'paywall-premium-content' ),
            $magic_link,
            Plugin::instance()->get_option( 'magic_link_ttl', 24 )
        );
        
        wp_mail( $email, $subject, $message );
    }
}