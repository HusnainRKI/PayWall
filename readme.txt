=== Paywall Anywhere ===
Contributors: husnainrki
Donate link: https://example.com/donate
Tags: paywall, premium, content, blocks, stripe, woocommerce, gutenberg
Requires at least: 6.5
Tested up to: 6.4
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Lock whole posts, specific blocks, single paragraphs, or media—anywhere in WordPress. Server-side, secure, Stripe & WooCommerce ready.

== Description ==

Paywall Anywhere gives you granular control over your premium content. Lock entire posts, individual Gutenberg blocks, specific paragraphs, or media files with complete server-side security.

= Key Features =

**🔒 Granular Locking**
* Whole-post paywall with "Lock From Here ↓" block
* Individual block locking via Inspector controls
* Paragraph-level locking within any block
* Media protection with blurred placeholders

**🛡️ Server-Side Security**
* No content leakage to DOM, RSS feeds, or APIs
* Complete protection for oEmbed, OpenGraph, AMP
* Editor previews for content creators
* Magic link authentication for guests

**💳 Flexible Payments**
* Native Stripe integration
* WooCommerce Simple Product support
* One-time purchases or time-based access
* Guest checkout with email magic links

**⚡ Performance Optimized**
* Cache-friendly teaser content
* CDN compatibility with proper headers
* Minimal database queries
* Clean, efficient code

= Use Cases =

* **Premium Articles**: Lock entire blog posts or specific sections
* **Tutorial Content**: Gate advanced steps in how-to guides
* **Media Library**: Protect downloads, images, or video content
* **Course Content**: Create tiered access to educational material
* **Member Content**: Combine with existing membership systems

= Developer Friendly =

* Extensive hooks and filters
* REST API endpoints
* Helper functions: `paywall_anywhere_is_unlocked()`
* Block editor extensions
* Shortcode support

= Security & Compliance =

✅ WordPress.org review standards compliant
✅ CSRF protection on all forms
✅ Proper capability checks
✅ Input sanitization and output escaping
✅ No obfuscated code
✅ GPL v2 licensed

== Installation ==

= Automatic Installation =

1. Go to Plugins → Add New in your WordPress admin
2. Search for "Paywall Anywhere"
3. Click "Install Now" and then "Activate"
4. Configure settings under Settings → Paywall Anywhere

= Manual Installation =

1. Download the plugin ZIP file
2. Upload to `/wp-content/plugins/paywall-anywhere/`
3. Activate through the 'Plugins' menu in WordPress
4. Configure payment providers in settings

= After Installation =

**Stripe Setup:**
1. Get API keys from your Stripe dashboard
2. Add keys in Settings → Paywall Anywhere
3. Set up webhook endpoint for payment completion

**WooCommerce Setup:**
1. Install and activate WooCommerce
2. Enable WooCommerce integration in plugin settings
3. Premium items will auto-create as Simple Products

== Frequently Asked Questions ==

= Is content really secure from leakage? =

Yes. Locked content is filtered server-side and never sent to the browser for unauthorized users. This includes HTML source, REST API responses, RSS feeds, and meta tags.

= Can I use this with caching plugins? =

Absolutely. The plugin is designed to work with popular caching solutions. Teaser content is cacheable, while unlocked content sets appropriate private cache headers.

= What happens to guest purchases when users register? =

When a guest user creates an account with the same email address, their purchases are automatically transferred to their user account.

= Can I customize the paywall appearance? =

Yes. The plugin provides filters for customizing placeholder HTML, unlock buttons, and teaser content. CSS classes are also provided for styling.

= Does this work with headless WordPress? =

Yes. The plugin provides full REST API endpoints for headless implementations while maintaining server-side security.

= Are there any content restrictions? =

The plugin respects WordPress content policies. All content rules that apply to your WordPress site apply to premium content as well.

== Screenshots ==

1. **Block Editor Integration** - Lock any block from the Inspector controls
2. **Paragraph-Level Locking** - Select specific paragraphs to lock within blocks
3. **Gate Start Block** - Mark where premium content begins
4. **Admin Settings** - Configure payment providers and options
5. **Premium Items List** - Manage all locked content in one place
6. **Entitlements Management** - View and manage user access
7. **Payment Providers** - Stripe and WooCommerce integration
8. **Magic Link Email** - Secure guest access via email

== Changelog ==

= 1.0.0 =
* Initial release
* Whole-post, block-level, and paragraph-level locking
* Stripe and WooCommerce payment integration
* Magic link guest access system
* Server-side content security
* Gutenberg block editor integration
* REST API endpoints
* Admin interface for managing premium content
* Comprehensive developer hooks and filters
* Cache-friendly implementation
* WordPress.org review compliance

== Upgrade Notice ==

= 1.0.0 =
Initial release of Paywall Anywhere. Transform your WordPress site into a premium content platform with granular locking controls.

== Privacy Policy ==

This plugin processes the following personal data:

**Guest Email Addresses:**
* Purpose: Magic link access for guest purchases
* Retention: Until user creates account or plugin uninstall
* Sharing: Not shared with third parties

**Payment Data:**
* Stripe: Processed by Stripe, not stored locally
* WooCommerce: Handled by WooCommerce privacy policies

**Access Logs:**
* Purpose: Security and debugging (if WP_DEBUG enabled)
* Retention: Standard WordPress log rotation
* Data: Access attempts, no personal information

Users can request data deletion by contacting site administrators. Plugin uninstall removes all stored data with admin confirmation.

== Support ==

For support and documentation:
* Plugin documentation: [example.com/paywall-anywhere-docs](https://example.com/paywall-anywhere-docs)
* Support forum: WordPress.org plugin support
* Developer resources: GitHub repository

== Credits ==

* Stripe integration uses Stripe API
* WooCommerce integration uses WooCommerce hooks
* Block editor extensions use WordPress Gutenberg APIs
* Icons from WordPress Dashicons and custom SVGs