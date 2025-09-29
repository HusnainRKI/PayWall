# Paywall Anywhere

[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://www.php.net/)
[![WordPress Version](https://img.shields.io/badge/WordPress-6.5%2B-blue.svg)](https://wordpress.org/)
[![License](https://img.shields.io/badge/License-GPLv2%20or%20later-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

Lock whole posts, specific blocks, single paragraphs, or media—anywhere in WordPress. Server-side, secure, Stripe & WooCommerce ready.

## Features

### ✨ Granular Content Control
- **Whole-post paywall** - Lock entire posts behind a paywall with the "Lock From Here ↓" dynamic block
- **Block-level locking** - Lock individual Gutenberg blocks (paragraphs, images, galleries, etc.) from the Inspector
- **Paragraph-level locking** - Lock specific paragraphs within a block using toolbar actions
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

### 🎯 Smart Access Management
- **Magic links** - Secure email-based access for guest users
- **User account sync** - Automatic transfer of guest purchases to user accounts
- **Flexible teasers** - Show first N paragraphs or custom teaser content

## Quick Start

### Installation

1. Upload the plugin files to `/wp-content/plugins/paywall-anywhere/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure payment providers in Settings → Paywall Anywhere

### Basic Usage

#### Lock Entire Posts
Add the "Paywall: Gate Start" block where you want the paywall to begin. Everything below will be locked.

#### Lock Individual Blocks
1. Select any Gutenberg block
2. In the Inspector panel, enable "Lock this block"
3. Set price, currency, and expiry options

#### Lock Specific Paragraphs
1. Select text within a paragraph block
2. Click the lock icon in the toolbar
3. Configure pricing in the Inspector

### Payment Setup

#### Stripe Configuration
```php
// Set in WordPress admin or use constants
define( 'PAYWALL_ANYWHERE_STRIPE_PUBLISHABLE_KEY', 'pk_test_...' );
define( 'PAYWALL_ANYWHERE_STRIPE_SECRET_KEY', 'sk_test_...' );
define( 'PAYWALL_ANYWHERE_STRIPE_WEBHOOK_SECRET', 'whsec_...' );
```

#### WooCommerce Integration
1. Enable WooCommerce integration in settings
2. Premium items automatically create Simple Products
3. Use existing WooCommerce checkout flow

## Developer API

### Helper Functions

#### Check Access
```php
// Check if content is unlocked
$is_unlocked = paywall_anywhere_is_unlocked( array(
    'post_id' => 123,
    'scope' => 'post', // or 'block', 'paragraph', 'media'
    'selector' => '', // block clientId or paragraph index
    'user_id' => get_current_user_id(),
    'guest_email' => 'user@example.com'
) );

// Simplified version
$has_access = paywall_anywhere_can_access_item( array(
    'post_id' => get_the_ID(),
    'scope' => 'block',
    'selector' => 'block-abc123'
) );
```

#### Format Prices
```php
// Format price for display
$price_display = paywall_anywhere_format_price( 500, 'USD' ); // $5.00
$price_display = paywall_anywhere_format_price( 299, 'EUR' ); // €2.99
```

#### Generate UI Elements
```php
// Generate unlock button
$button_html = paywall_anywhere_get_unlock_button_html( $item_id, array(
    'text' => 'Unlock Now',
    'style' => 'filled', // or 'outline'
    'class' => 'custom-class'
) );

// Generate placeholder content
$placeholder = paywall_anywhere_get_placeholder_html( array(
    'type' => 'content',
    'message' => 'Premium content locked',
    'item_id' => $item_id
) );
```

### Hooks & Filters

#### Actions
- `paywall_anywhere_entitlement_created` - Fired when new entitlement is created
- `paywall_anywhere_payment_completed` - Fired when payment is completed
- `paywall_anywhere_access_granted` - Fired when access is granted to content

#### Filters
- `paywall_anywhere_can_access_item` - Filter access check results
- `paywall_anywhere_placeholder_html` - Customize placeholder HTML
- `paywall_anywhere_teaser_html` - Customize teaser content
- `paywall_anywhere_unlock_button_html` - Customize unlock button HTML

### REST API Endpoints

All endpoints require authentication and proper permissions.

#### GET /wp-json/paywall-anywhere/v1/items
Get premium items.

**Parameters:**
- `post` - Filter by post ID

#### POST /wp-json/paywall-anywhere/v1/items
Create a new premium item.

#### GET /wp-json/paywall-anywhere/v1/entitlements
Get entitlements.

**Parameters:**
- `user` - Filter by user ID
- `email` - Filter by guest email

## Blocks

### paywall-anywhere/gate-start
Marks the start of premium content. Everything below this block will be locked unless explicitly marked as free.

**Attributes:**
- `price` - Price in minor currency units
- `currency` - Currency code (USD, EUR, GBP, JPY)
- `expiresDays` - Access duration in days
- `adFree` - Whether to hide ads for premium users

### paywall-anywhere/unlock-cta
Renders unlock button(s) with payment provider choices.

**Attributes:**
- `itemId` - Premium item ID
- `providers` - Array of enabled payment providers
- `style` - Button style (filled, outline)

## WordPress.org Review Compliance

This plugin is designed to meet WordPress.org review standards:

- **Security First**: All inputs sanitized, outputs escaped, CSRF protection on all forms
- **No Obfuscated Code**: All code is readable and well-documented
- **Proper Licensing**: GPL v2 or later, compatible with WordPress core
- **No Hidden Features**: All functionality is documented and transparent
- **Opt-in Only**: No tracking or telemetry without explicit user consent

## Requirements

- **WordPress:** 6.5 or higher
- **PHP:** 8.1 or higher
- **Stripe Account:** For Stripe payments (optional)
- **WooCommerce:** For WooCommerce integration (optional)

## Installation Notes

### For Developers
- Set up Stripe test keys in `wp-config.php` or admin settings
- Configure cache rules for CDN if using
- Review security headers for media protection

### Live Keys & Manual Steps
**TODO for deployment:**
1. Replace placeholder URLs in plugin header
2. Add live Stripe keys via admin or constants
3. Configure webhook endpoints
4. Test payment flows
5. Set up cache vary rules for CDN

## License

This plugin is licensed under the GPL v2 or later.

```
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
```