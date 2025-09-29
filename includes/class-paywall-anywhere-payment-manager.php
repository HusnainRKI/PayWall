<?php
/**
 * Payment Manager Class
 *
 * @package Paywall_Anywhere\Payments
 */

namespace Paywall_Anywhere\Payments;

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
        add_action( 'wp_ajax_paywall_anywhere_create_payment', array( $this, 'handle_create_payment' ) );
        add_action( 'wp_ajax_nopriv_paywall_anywhere_create_payment', array( $this, 'handle_create_payment' ) );
        
        // AJAX handler for creating items on the fly
        add_action( 'wp_ajax_paywall_anywhere_create_item_for_purchase', array( $this, 'handle_create_item_for_purchase' ) );
        add_action( 'wp_ajax_nopriv_paywall_anywhere_create_item_for_purchase', array( $this, 'handle_create_item_for_purchase' ) );
        
        // Stripe webhook handler
        add_action( 'wp_ajax_paywall_anywhere_stripe_webhook', array( $this, 'handle_stripe_webhook' ) );
        add_action( 'wp_ajax_nopriv_paywall_anywhere_stripe_webhook', array( $this, 'handle_stripe_webhook' ) );
        
        // WooCommerce integration
        add_action( 'woocommerce_order_status_completed', array( $this, 'handle_woocommerce_completion' ) );
    }
    
    /**
     * Handle payment creation request
     */
    public function handle_create_payment() {
        check_ajax_referer( 'paywall_anywhere_nonce', 'nonce' );
        
        $item_id = absint( $_POST['item_id'] ?? 0 );
        $email = sanitize_email( $_POST['email'] ?? '' );
        
        if ( ! $item_id || ! $email ) {
            wp_die( wp_json_encode( array( 'error' => 'Invalid parameters' ) ) );
        }
        
        $db = \Paywall_Anywhere\Plugin::instance()->db;
        $item = $db->get_item( $item_id );
        
        if ( ! $item ) {
            wp_die( wp_json_encode( array( 'error' => 'Item not found' ) ) );
        }
        
        // Check if Stripe is enabled
        $stripe_enabled = \Paywall_Anywhere\Plugin::instance()->get_option( 'stripe_enabled', false );
        
        if ( $stripe_enabled ) {
            $result = $this->create_stripe_session( $item, $email );
            wp_die( wp_json_encode( $result ) );
        }
        
        // Check if WooCommerce is enabled
        if ( class_exists( 'WooCommerce' ) ) {
            $result = $this->create_woocommerce_product( $item, $email );
            wp_die( wp_json_encode( $result ) );
        }
        
        wp_die( wp_json_encode( array( 'error' => 'No payment method available' ) ) );
    }
    
    /**
     * Handle creating items for purchase
     */
    public function handle_create_item_for_purchase() {
        check_ajax_referer( 'paywall_anywhere_nonce', 'nonce' );
        
        $post_id = absint( $_POST['post_id'] ?? 0 );
        $scope = sanitize_text_field( $_POST['scope'] ?? 'post' );
        $selector = sanitize_text_field( $_POST['selector'] ?? '' );
        $price = absint( $_POST['price'] ?? 500 );
        $currency = sanitize_text_field( $_POST['currency'] ?? 'USD' );
        $expires_days = absint( $_POST['expires_days'] ?? 0 ) ?: null;
        
        if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
            wp_die( wp_json_encode( array( 'error' => 'Permission denied' ) ) );
        }
        
        $db = \Paywall_Anywhere\Plugin::instance()->db;
        
        // Check if item already exists
        $existing_item = $db->get_item_by_criteria( $post_id, $scope, $selector );
        
        if ( $existing_item ) {
            wp_die( wp_json_encode( array( 'success' => true, 'item_id' => $existing_item->id ) ) );
        }
        
        // Create new item
        $item_id = $db->create_item( array(
            'post_id' => $post_id,
            'scope' => \paywall_anywhere_sanitize_scope( $scope ),
            'selector' => $selector,
            'price_minor' => $price,
            'currency' => \paywall_anywhere_sanitize_currency( $currency ),
            'expires_days' => $expires_days,
        ) );
        
        if ( $item_id ) {
            wp_die( wp_json_encode( array( 'success' => true, 'item_id' => $item_id ) ) );
        } else {
            wp_die( wp_json_encode( array( 'success' => false, 'message' => 'Failed to create item' ) ) );
        }
    }
    
    /**
     * Create Stripe checkout session
     *
     * @param object $item  Premium item.
     * @param string $email Customer email.
     * @return array
     */
    private function create_stripe_session( $item, $email ) {
        $stripe_secret = \Paywall_Anywhere\Plugin::instance()->get_option( 'stripe_secret_key' );
        
        if ( ! $stripe_secret ) {
            return array( 'error' => 'Stripe not configured' );
        }
        
        // This is a simplified implementation
        // In a real implementation, you would use the Stripe PHP SDK
        $post = get_post( $item->post_id );
        $success_url = add_query_arg( 'paywall_anywhere_success', '1', get_permalink( $item->post_id ) );
        $cancel_url = get_permalink( $item->post_id );
        
        // Mock Stripe session creation
        $session_data = array(
            'id' => 'cs_mock_' . wp_generate_password( 24, false ),
            'url' => 'https://checkout.stripe.com/pay/mock_session',
            'success_url' => $success_url,
            'cancel_url' => $cancel_url,
            'customer_email' => $email,
            'line_items' => array(
                array(
                    'price_data' => array(
                        'currency' => strtolower( $item->currency ),
                        'product_data' => array(
                            'name' => sprintf( 
                                __( 'Access to %s', 'paywall-anywhere' ), 
                                $post ? $post->post_title : 'Premium Content' 
                            ),
                        ),
                        'unit_amount' => $item->price_minor,
                    ),
                    'quantity' => 1,
                ),
            ),
            'metadata' => array(
                'item_id' => $item->id,
                'post_id' => $item->post_id,
                'scope' => $item->scope,
                'selector' => $item->selector,
            ),
        );
        
        return array(
            'success' => true,
            'checkout_url' => $session_data['url'],
            'session_id' => $session_data['id']
        );
    }
    
    /**
     * Create WooCommerce product for item
     *
     * @param object $item  Premium item.
     * @param string $email Customer email.
     * @return array
     */
    private function create_woocommerce_product( $item, $email ) {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return array( 'error' => 'WooCommerce not active' );
        }
        
        // Check if product already exists
        $product_id = get_post_meta( $item->post_id, '_paywall_anywhere_wc_product_' . $item->id, true );
        
        if ( ! $product_id ) {
            // Create new Simple Product
            $post = get_post( $item->post_id );
            $product_name = sprintf( 
                __( 'Access to %s', 'paywall-anywhere' ), 
                $post ? $post->post_title : 'Premium Content' 
            );
            
            $product = new \WC_Product_Simple();
            $product->set_name( $product_name );
            $product->set_regular_price( $item->price_minor / 100 ); // Convert from cents
            $product->set_virtual( true );
            $product->set_downloadable( false );
            $product->set_sold_individually( true );
            $product->set_status( 'publish' );
            
            $product_id = $product->save();
            
            if ( $product_id ) {
                // Link product to item
                update_post_meta( $product_id, '_paywall_anywhere_item_id', $item->id );
                update_post_meta( $item->post_id, '_paywall_anywhere_wc_product_' . $item->id, $product_id );
            }
        }
        
        if ( $product_id ) {
            $checkout_url = wc_get_checkout_url() . '?add-to-cart=' . $product_id;
            
            return array(
                'success' => true,
                'checkout_url' => $checkout_url,
                'product_id' => $product_id
            );
        }
        
        return array( 'error' => 'Failed to create product' );
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
     *
     * @param array $session Stripe session data.
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
     *
     * @param int $order_id Order ID.
     */
    public function handle_woocommerce_completion( $order_id ) {
        $order = wc_get_order( $order_id );
        
        if ( ! $order ) {
            return;
        }
        
        // Check if order contains premium items
        foreach ( $order->get_items() as $item ) {
            $product_id = $item->get_product_id();
            $item_id = get_post_meta( $product_id, '_paywall_anywhere_item_id', true );
            
            if ( $item_id ) {
                $email = $order->get_billing_email();
                $this->create_entitlement_with_magic_link( $item_id, $email, 'woocommerce' );
            }
        }
    }
    
    /**
     * Create entitlement and send magic link
     *
     * @param int    $item_id Item ID.
     * @param string $email   Customer email.
     * @param string $source  Payment source.
     * @return bool
     */
    private function create_entitlement_with_magic_link( $item_id, $email, $source ) {
        $db = \Paywall_Anywhere\Plugin::instance()->db;
        $access_manager = \Paywall_Anywhere\Plugin::instance()->access;
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
            
            // Fire action
            do_action( 'paywall_anywhere_entitlement_created', $entitlement_id, $item_id, $email );
            do_action( 'paywall_anywhere_payment_completed', $item_id, $email, $source );
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Send magic link email
     *
     * @param string $email Customer email.
     * @param string $token Access token.
     * @param object $item  Premium item.
     */
    private function send_magic_link_email( $email, $token, $item ) {
        $post = get_post( $item->post_id );
        $magic_link = add_query_arg( 'paywall_anywhere_token', $token, get_permalink( $item->post_id ) );
        
        $subject = sprintf( 
            __( 'Your access to %s', 'paywall-anywhere' ), 
            $post ? $post->post_title : 'Premium Content' 
        );
        
        $message = sprintf(
            __( "Hello!\n\nYour payment has been processed successfully. Click the link below to access your premium content:\n\n%s\n\nThis link will expire in %d hour(s) for security.\n\nThank you for your purchase!", 'paywall-anywhere' ),
            $magic_link,
            \Paywall_Anywhere\Plugin::instance()->get_option( 'magic_link_ttl', 3600 ) / 3600
        );
        
        $headers = array( 'Content-Type: text/plain; charset=UTF-8' );
        
        wp_mail( $email, $subject, $message, $headers );
        
        \paywall_anywhere_log( 'Magic link email sent', array(
            'email' => $email,
            'item_id' => $item->id,
            'post_id' => $item->post_id
        ) );
    }
}