<?php
/**
 * Shortcodes Class
 *
 * @package PaywallPremiumContent
 */

namespace Pc;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Shortcodes Class
 */
class Shortcodes {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->register_shortcodes();
    }
    
    /**
     * Register shortcodes
     */
    private function register_shortcodes() {
        add_shortcode( 'pc_unlock_button', array( $this, 'unlock_button_shortcode' ) );
        add_shortcode( 'pc_premium_content', array( $this, 'premium_content_shortcode' ) );
        add_shortcode( 'pc_teaser', array( $this, 'teaser_shortcode' ) );
    }
    
    /**
     * Unlock button shortcode
     * [pc_unlock_button item="post:123" text="Unlock Now"]
     */
    public function unlock_button_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'item' => '',
            'item_id' => 0,
            'post_id' => get_the_ID(),
            'scope' => 'post',
            'selector' => '',
            'text' => '',
            'style' => 'filled',
            'class' => 'pc-unlock-btn',
        ), $atts );
        
        // Parse item parameter (format: "scope:selector")
        if ( $atts['item'] && ! $atts['item_id'] ) {
            if ( strpos( $atts['item'], ':' ) !== false ) {
                list( $atts['scope'], $atts['selector'] ) = explode( ':', $atts['item'], 2 );
            } else {
                $atts['scope'] = 'post';
                $atts['selector'] = $atts['item'];
            }
        }
        
        return pc_unlock_button( $atts );
    }
    
    /**
     * Premium content shortcode
     * [pc_premium_content scope="post" price="5.00"]Content here[/pc_premium_content]
     */
    public function premium_content_shortcode( $atts, $content = '' ) {
        $atts = shortcode_atts( array(
            'scope' => 'post',
            'selector' => '',
            'price' => '',
            'currency' => 'USD',
            'expires' => '',
            'teaser' => '',
        ), $atts );
        
        $post_id = get_the_ID();
        if ( ! $post_id ) {
            return $content;
        }
        
        // Check if user has access
        $access_manager = Plugin::instance()->access;
        
        if ( $access_manager->has_post_access( $post_id, $atts['scope'], $atts['selector'] ) ) {
            return do_shortcode( $content );
        }
        
        // Show teaser or placeholder
        $teaser_content = $atts['teaser'] ? $atts['teaser'] : __( 'This is premium content.', 'paywall-premium-content' );
        
        $placeholder = '<div class="pc-shortcode-placeholder">';
        $placeholder .= '<div class="pc-teaser">' . esc_html( $teaser_content ) . '</div>';
        
        // Add unlock button
        $button_args = array(
            'post_id' => $post_id,
            'scope' => $atts['scope'],
            'selector' => $atts['selector'],
        );
        
        if ( $atts['price'] ) {
            $button_args['text'] = sprintf( 
                __( 'Unlock for %s%s', 'paywall-premium-content' ), 
                $atts['currency'] === 'USD' ? '$' : $atts['currency'] . ' ',
                $atts['price']
            );
        }
        
        $placeholder .= pc_unlock_button( $button_args );
        $placeholder .= '</div>';
        
        return $placeholder;
    }
    
    /**
     * Teaser shortcode
     * [pc_teaser post_id="123" paragraphs="2"]
     */
    public function teaser_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'post_id' => get_the_ID(),
            'paragraphs' => 2,
            'show_unlock' => 'yes',
        ), $atts );
        
        if ( ! $atts['post_id'] ) {
            return '';
        }
        
        $teaser = pc_get_teaser_content( $atts['post_id'], absint( $atts['paragraphs'] ) );
        
        if ( $atts['show_unlock'] === 'yes' ) {
            $teaser .= "\n\n" . pc_unlock_button( array(
                'post_id' => $atts['post_id'],
                'scope' => 'post',
            ));
        }
        
        return wpautop( $teaser );
    }
}