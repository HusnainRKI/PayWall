<?php
/**
 * Block Manager Class
 *
 * @package PaywallPremiumContent
 */

namespace Pc;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Block Manager Class
 */
class Block_Manager {
    
    /**
     * Register Gutenberg blocks
     */
    public function register_blocks() {
        // Register gate start block using block.json
        register_block_type( PC_PLUGIN_PATH . 'blocks/gate-start/block.json', array(
            'render_callback' => array( $this, 'render_gate_start' ),
        ));
        
        // Register unlock CTA block using block.json
        register_block_type( PC_PLUGIN_PATH . 'blocks/unlock-cta/block.json', array(
            'render_callback' => array( $this, 'render_unlock_cta' ),
        ));
        
        // Add block category
        add_filter( 'block_categories_all', array( $this, 'add_block_category' ), 10, 2 );
        
        // Hook to save block lock metadata
        add_action( 'save_post', array( $this, 'save_block_lock_metadata' ) );
    }
    
    /**
     * Add custom block category
     */
    public function add_block_category( $categories, $editor_context ) {
        return array_merge(
            $categories,
            array(
                array(
                    'slug'  => 'paywall',
                    'title' => __( 'PayWall', 'paywall-premium-content' ),
                    'icon'  => 'lock',
                ),
            )
        );
    }
    
    /**
     * Save block lock metadata when post is saved
     */
    public function save_block_lock_metadata( $post_id ) {
        // Skip autosaves and revisions
        if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
            return;
        }
        
        // Check permissions
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
        
        $post = get_post( $post_id );
        if ( ! $post ) {
            return;
        }
        
        // Parse blocks and extract lock metadata
        $blocks = parse_blocks( $post->post_content );
        $locked_map = $this->extract_locked_blocks( $blocks );
        
        // Save or update locked map
        $access_manager = Plugin::instance()->access;
        $access_manager->update_locked_map( $post_id, $locked_map );
        
        // Create/update premium items in database
        $this->sync_premium_items( $post_id, $locked_map );
    }
    
    /**
     * Extract locked blocks from block tree
     */
    private function extract_locked_blocks( $blocks, $locked_map = array() ) {
        foreach ( $blocks as $block ) {
            // Check if this block is locked
            if ( isset( $block['attrs']['pcLocked'] ) && $block['attrs']['pcLocked'] ) {
                $locked_map[] = array(
                    'scope' => 'block',
                    'selector' => $block['attrs']['clientId'] ?? uniqid( 'block_' ),
                    'price_minor' => $block['attrs']['pcPrice'] ?? 500,
                    'currency' => $block['attrs']['pcCurrency'] ?? 'USD',
                    'expires_days' => $block['attrs']['pcExpiresDays'] ?? 30,
                );
            }
            
            // Check for gate start blocks
            if ( $block['blockName'] === 'pc/gate-start' ) {
                $locked_map[] = array(
                    'scope' => 'gate',
                    'selector' => $block['attrs']['clientId'] ?? uniqid( 'gate_' ),
                    'price_minor' => $block['attrs']['price'] ?? 500,
                    'currency' => $block['attrs']['currency'] ?? 'USD',
                    'expires_days' => $block['attrs']['expiresDays'] ?? 30,
                );
            }
            
            // Process inner blocks recursively
            if ( ! empty( $block['innerBlocks'] ) ) {
                $locked_map = $this->extract_locked_blocks( $block['innerBlocks'], $locked_map );
            }
        }
        
        return $locked_map;
    }
    
    /**
     * Sync premium items with database
     */
    private function sync_premium_items( $post_id, $locked_map ) {
        $db = Plugin::instance()->db;
        
        // Get existing items for this post
        $existing_items = $db->get_items_by_post( $post_id );
        $existing_selectors = array_column( $existing_items, 'selector' );
        
        // Create new items
        foreach ( $locked_map as $item_data ) {
            if ( ! in_array( $item_data['selector'], $existing_selectors ) ) {
                $db->create_item( array_merge( $item_data, array( 'post_id' => $post_id ) ) );
            }
        }
        
        // Remove items that are no longer in the locked map
        $current_selectors = array_column( $locked_map, 'selector' );
        foreach ( $existing_items as $existing_item ) {
            if ( ! in_array( $existing_item->selector, $current_selectors ) ) {
                $db->delete_item( $existing_item->id );
            }
        }
    }
    
    /**
     * Render gate start block
     */
    public function render_gate_start( $attributes ) {
        // This block marks the start of premium content
        return '<!-- pc:gate-start -->';
    }
    
    /**
     * Render unlock CTA block
     */
    public function render_unlock_cta( $attributes ) {
        if ( empty( $attributes['itemId'] ) ) {
            return '';
        }
        
        $item_id = $attributes['itemId'];
        $style = $attributes['style'] ?? 'filled';
        
        // Check if user has access
        $access_manager = Plugin::instance()->access;
        $user_id = get_current_user_id();
        $guest_email = $access_manager->get_guest_email();
        
        if ( $access_manager->has_access( $user_id, $item_id, $guest_email ) ) {
            return ''; // Don't show CTA if already unlocked
        }
        
        // Get item details
        $db = Plugin::instance()->db;
        $item = $db->get_item( $item_id );
        
        if ( ! $item ) {
            return '';
        }
        
        $price_display = $this->format_price( $item->price_minor, $item->currency );
        
        $class = 'pc-unlock-cta pc-unlock-cta--' . esc_attr( $style );
        
        $html = sprintf( 
            '<div class="%s" data-item-id="%d">',
            esc_attr( $class ),
            $item_id
        );
        
        $html .= sprintf(
            '<button class="pc-unlock-btn" data-item-id="%d">%s</button>',
            $item_id,
            sprintf( __( 'Unlock for %s', 'paywall-premium-content' ), $price_display )
        );
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Format price for display
     */
    private function format_price( $price_minor, $currency ) {
        $price = $price_minor / 100;
        
        $symbol = '$';
        $symbols = array(
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
        );
        
        if ( isset( $symbols[ $currency ] ) ) {
            $symbol = $symbols[ $currency ];
        }
        
        return $symbol . number_format( $price, 2 );
    }
}