# PayWall Premium Content

A self-hosted WordPress plugin that lets site admins lock premium content until payment with granular control over posts, blocks, paragraphs, and media.

## Features

### ✨ Granular Content Control
- **Whole-post paywall** - Lock entire posts behind a paywall
- **Block-level locking** - Lock individual Gutenberg blocks (paragraphs, images, galleries, etc.)
- **Paragraph-level locking** - Lock specific paragraphs within a block
- **Media protection** - Lock individual images, audio, and downloads with blurred placeholders

### 🔒 Server-Side Security
- **No content leakage** - Locked content never appears in HTML, REST API, RSS feeds, or meta tags
- **Complete protection** - Covers oEmbed, OpenGraph, AMP, and print routes
- **Editor previews** - Content creators can preview locked content with edit permissions

### 💳 Flexible Payment Options
- **Stripe integration** - Native Stripe checkout for direct payments
- **WooCommerce support** - Integration with WooCommerce Simple Products
- **One-time purchases** - Pay-per-post or pay-per-section model
- **Time-based access** - Optional expiry (7, 30, 365 days)

### 🎯 User Experience
- **Guest purchases** - Email-based purchases with magic links
- **User account sync** - Guest purchases transfer to user accounts on login
- **Teaser content** - Show first N paragraphs or custom teasers
- **Cache friendly** - Compatible with popular caching plugins and CDNs

### 🛠 Developer Friendly
- **Hooks & Filters** - Extensive customization options
- **REST API** - Full REST API for headless implementations
- **Shortcodes** - `[pc_unlock_button]`, `[pc_premium_content]`, `[pc_teaser]`
- **Helper functions** - `pc_is_unlocked()`, `pc_format_price()`, etc.

## Requirements

- WordPress 6.5+
- PHP 8.1+
- Gutenberg 17+
- MySQL 5.7+ or MariaDB 10.2+

## Installation

1. Download the plugin ZIP file
2. Upload to `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Configure settings under 'PayWall' in the admin menu

## Quick Start

### 1. Basic Setup
1. Go to **PayWall > Settings**
2. Set your default currency and pricing
3. Configure Stripe API keys (for payments)
4. Save settings

### 2. Lock Entire Posts
1. Add the **"Paywall: Gate Start"** block to your post
2. Set price and access duration in the block settings
3. All content below the gate will be locked

### 3. Lock Individual Blocks
1. Select any Gutenberg block (paragraph, image, etc.)
2. In the block inspector, find **"PayWall Settings"**
3. Toggle **"Lock this block"**
4. Set pricing and duration

### 4. Lock Specific Paragraphs
1. Select text within a paragraph block
2. Use the toolbar action **"Lock selected paragraph"**
3. Configure pricing in the block inspector

### 5. Add Purchase Buttons
1. Add the **"Paywall: Unlock CTA"** block
2. Select which premium item to unlock
3. Customize button style and text

## Gutenberg Blocks

### Paywall: Gate Start
Marks the start of premium content. Everything below this block will be locked for non-premium users.

**Settings:**
- Price (in cents)
- Currency (USD, EUR, GBP, JPY)
- Access duration (1-365 days)
- Ad-free mode toggle
- Include print/PDF routes

### Paywall: Unlock CTA
Displays a call-to-action button for purchasing premium content.

**Settings:**
- Premium item selection
- Button style (filled/outline)
- Alignment (left/center/right)
- Custom button text

## Shortcodes

### [pc_unlock_button]
Display an unlock button for premium content.

```php
[pc_unlock_button item="post:123" text="Unlock Now" style="filled"]
```

**Parameters:**
- `item` - Format: "scope:selector" (e.g., "post:123", "block:abc123")
- `text` - Custom button text
- `style` - Button style: "filled" or "outline"
- `class` - Additional CSS classes

### [pc_premium_content]
Wrap content that should be locked behind a paywall.

```php
[pc_premium_content price="5.00" expires="30"]
This content is only visible to premium users.
[/pc_premium_content]
```

**Parameters:**
- `price` - Price in dollars (e.g., "5.00")
- `currency` - Currency code (default: "USD")
- `expires` - Access duration in days
- `teaser` - Custom teaser text

### [pc_teaser]
Display teaser content for a post.

```php
[pc_teaser post_id="123" paragraphs="2" show_unlock="yes"]
```

**Parameters:**
- `post_id` - Post ID (default: current post)
- `paragraphs` - Number of paragraphs to show (default: 2)
- `show_unlock` - Show unlock button ("yes"/"no")

## Developer API

### Helper Functions

#### pc_is_unlocked( $args )
Check if user has access to specific content.

```php
$has_access = pc_is_unlocked( array(
    'post_id' => 123,
    'scope' => 'block',
    'selector' => 'abc123'
) );
```

#### pc_format_price( $price_minor, $currency )
Format price for display.

```php
$formatted = pc_format_price( 500, 'USD' ); // Returns "$5.00"
```

#### pc_get_user_entitlements( $user_id )
Get all entitlements for a user.

```php
$entitlements = pc_get_user_entitlements( get_current_user_id() );
```

### Hooks & Filters

#### Actions
- `pc_entitlement_created` - Fired when new entitlement is created
- `pc_payment_completed` - Fired when payment is completed
- `pc_access_granted` - Fired when access is granted to content

#### Filters
- `pc_can_access_item` - Filter access check results
- `pc_placeholder_html` - Customize placeholder HTML
- `pc_teaser_html` - Customize teaser content
- `pc_unlock_button_html` - Customize unlock button HTML

### REST API Endpoints

All endpoints require authentication and proper permissions.

#### GET /wp-json/pc/v1/items
Get premium items.

**Parameters:**
- `post` - Filter by post ID

#### POST /wp-json/pc/v1/items
Create a new premium item.

#### GET /wp-json/pc/v1/entitlements
Get entitlements.

**Parameters:**
- `user` - Filter by user ID
- `email` - Filter by guest email

## Admin Interface

### Premium Items
View and manage all premium items across your site:
- Post/block/paragraph breakdown
- Pricing and currency
- Sales statistics
- Active/expired status

### Entitlements
Track who has access to what:
- User/guest email
- Purchase date and source
- Expiry dates
- Export to CSV

### Settings
Configure global plugin settings:
- Default pricing and currency
- Payment gateway settings (Stripe, WooCommerce)
- Teaser behavior
- Cache compatibility
- Security logging

## Security Features

### Content Protection
- Server-side filtering prevents content leakage
- REST API endpoint protection
- RSS feed filtering
- Meta tag and OpenGraph filtering
- AMP page compatibility
- Print route protection

### Access Control
- Secure token-based magic links
- Session management for guests
- User account synchronization
- Automatic cleanup of expired access

### Security Logging
- Failed access attempts
- Payment webhook events
- Administrative actions
- IP address tracking

## Caching Compatibility

The plugin works with popular caching solutions:

- **WP Rocket** - Automatic cache bypass for authenticated users
- **W3 Total Cache** - Cookie-based cache variations
- **WP Super Cache** - Dynamic content handling
- **Cloudflare** - Cache-Control header management
- **CDN support** - Vary headers for proper caching

## Performance Optimization

- **Lazy loading** - Premium items loaded on demand
- **Database indexing** - Optimized queries for large datasets
- **Asset optimization** - Minimal frontend footprint
- **Cache-friendly** - Separates cached and dynamic content

## Accessibility

- **WCAG 2.1 AA compliant** - All UI elements meet accessibility standards
- **Keyboard navigation** - Full keyboard support
- **Screen reader friendly** - Proper ARIA labels and announcements
- **Focus management** - Logical tab order and focus handling

## Internationalization

- **Translation ready** - All strings are translatable
- **RTL support** - Right-to-left language support
- **Text domain** - `paywall-premium-content`
- **POT file included** - Easy translation workflow

## Troubleshooting

### Common Issues

**Content still visible after locking**
- Clear all caches (plugin, server, CDN)
- Check user permissions (editors can preview)
- Verify block lock settings are saved

**Payment not working**
- Check Stripe API keys in settings
- Verify webhook endpoints are configured
- Check payment gateway logs

**Magic links not working**
- Check email delivery (spam folder)
- Verify token hasn't expired (24 hours default)
- Check server timezone settings

### Debug Mode
Enable WordPress debug mode to see detailed error messages:

```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
```

## Support

- **Documentation** - [GitHub Wiki](https://github.com/HusnainRKI/PayWall/wiki)
- **Issues** - [GitHub Issues](https://github.com/HusnainRKI/PayWall/issues)
- **Discussions** - [GitHub Discussions](https://github.com/HusnainRKI/PayWall/discussions)

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the GPL v2 or later - see the [LICENSE](LICENSE) file for details.

## Changelog

### v1.0.0
- Initial release
- Complete paywall system with granular control
- Stripe and WooCommerce integration
- Gutenberg block editor support
- REST API and developer tools
- Security and caching optimizations