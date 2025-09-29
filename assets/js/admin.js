/**
 * Paywall Anywhere - Admin JavaScript
 */

(function($) {
    'use strict';
    
    /**
     * Admin Interface Handler
     */
    var PaywallAnywhereAdmin = {
        
        init: function() {
            this.bindEvents();
            this.initValidation();
            this.initTabSwitching();
        },
        
        bindEvents: function() {
            // Stripe test connection
            $(document).on('click', '#test-stripe-connection', this.testStripeConnection.bind(this));
            
            // Form validation on submit
            $(document).on('submit', '#paywall-anywhere-general-form, #paywall-anywhere-payments-form', this.validateForm.bind(this));
            
            // Real-time validation
            $(document).on('input', 'input[name="paywall_anywhere_default_price"]', this.validatePrice.bind(this));
            $(document).on('input', 'input[name="paywall_anywhere_default_expires_days"]', this.validateExpiresDays.bind(this));
            $(document).on('input', 'input[name="paywall_anywhere_teaser_length"]', this.validateTeaserLength.bind(this));
            $(document).on('input', 'input[name="paywall_anywhere_magic_link_ttl"]', this.validateMagicLinkTTL.bind(this));
            
            // Enable/disable test connection based on keys
            $(document).on('input', '#stripe_publishable_key, #stripe_secret_key', this.toggleStripeTestButton.bind(this));
            
            // Premium items page functionality
            $(document).on('click', '#scan-locked-items-btn, #scan-empty-state-btn', this.scanLockedItems.bind(this));
            $(document).on('click', '.paywall-anywhere-edit-item-btn', this.startInlineEdit.bind(this));
            $(document).on('click', '.paywall-anywhere-save-item-btn', this.saveInlineEdit.bind(this));
            $(document).on('click', '.paywall-anywhere-cancel-edit-btn', this.cancelInlineEdit.bind(this));
            $(document).on('dblclick', '.paywall-anywhere-inline-edit-price, .paywall-anywhere-inline-edit-expiry', this.startInlineEditField.bind(this));
            $(document).on('keydown', '.paywall-anywhere-edit-input', this.handleInlineEditKeydown.bind(this));
            $(document).on('blur', '.paywall-anywhere-edit-input', this.handleInlineEditBlur.bind(this));
        },
        
        initValidation: function() {
            // Add validation styling
            $('<style>')
                .prop('type', 'text/css')
                .html('.paywall-anywhere-validation-error { display: none; color: #dc3232; margin-top: 5px; font-size: 12px; } .paywall-anywhere-field-invalid { border-color: #dc3232 !important; box-shadow: 0 0 2px rgba(220, 50, 50, 0.8); }')
                .appendTo('head');
        },
        
        initTabSwitching: function() {
            // Handle tab switching with URL updates
            $('.nav-tab').on('click', function(e) {
                var href = $(this).attr('href');
                if (href && href.indexOf('tab=') !== -1) {
                    // Let the browser handle the navigation
                    return true;
                }
            });
        },
        
        validateForm: function(e) {
            var isValid = true;
            var $form = $(e.target);
            
            // Clear previous errors
            $form.find('.paywall-anywhere-validation-error').hide();
            $form.find('.paywall-anywhere-field-invalid').removeClass('paywall-anywhere-field-invalid');
            
            // Validate each field
            if ($form.attr('id') === 'paywall-anywhere-general-form') {
                isValid &= this.validatePrice();
                isValid &= this.validateExpiresDays();
                isValid &= this.validateTeaserLength();
            } else if ($form.attr('id') === 'paywall-anywhere-payments-form') {
                isValid &= this.validateMagicLinkTTL();
            }
            
            if (!isValid) {
                e.preventDefault();
                this.showValidationSummary();
            }
            
            return isValid;
        },
        
        validatePrice: function() {
            var $field = $('input[name="paywall_anywhere_default_price"]');
            var value = parseInt($field.val());
            var $error = $('#price-error');
            
            if (isNaN(value) || value < 0) {
                this.showFieldError($field, $error, 'Price must be a positive integer (in cents).');
                return false;
            }
            
            if (value > 99999999) { // $999,999.99 max
                this.showFieldError($field, $error, 'Price cannot exceed $999,999.99 (99999999 cents).');
                return false;
            }
            
            this.hideFieldError($field, $error);
            return true;
        },
        
        validateExpiresDays: function() {
            var $field = $('input[name="paywall_anywhere_default_expires_days"]');
            var value = parseInt($field.val());
            var $error = $('#expires-error');
            
            if (isNaN(value) || value < 0) {
                this.showFieldError($field, $error, 'Expiry days must be 0 or greater (0 = never expires).');
                return false;
            }
            
            if (value > 3650) { // 10 years max
                this.showFieldError($field, $error, 'Expiry days cannot exceed 3650 (10 years).');
                return false;
            }
            
            this.hideFieldError($field, $error);
            return true;
        },
        
        validateTeaserLength: function() {
            var $field = $('input[name="paywall_anywhere_teaser_length"]');
            var value = parseInt($field.val());
            var $error = $('#teaser-error');
            
            if (isNaN(value) || value < 0) {
                this.showFieldError($field, $error, 'Teaser length must be 0 or greater.');
                return false;
            }
            
            if (value > 1000) {
                this.showFieldError($field, $error, 'Teaser length cannot exceed 1000 words.');
                return false;
            }
            
            this.hideFieldError($field, $error);
            return true;
        },
        
        validateMagicLinkTTL: function() {
            var $field = $('input[name="paywall_anywhere_magic_link_ttl"]');
            var value = parseInt($field.val());
            var $error = $('#ttl-error');
            
            if (isNaN(value) || value < 300) {
                this.showFieldError($field, $error, 'TTL must be at least 300 seconds (5 minutes).');
                return false;
            }
            
            if (value > 86400) { // 24 hours max
                this.showFieldError($field, $error, 'TTL cannot exceed 86400 seconds (24 hours).');
                return false;
            }
            
            this.hideFieldError($field, $error);
            return true;
        },
        
        showFieldError: function($field, $error, message) {
            $field.addClass('paywall-anywhere-field-invalid');
            $error.text(message).show();
        },
        
        hideFieldError: function($field, $error) {
            $field.removeClass('paywall-anywhere-field-invalid');
            $error.hide();
        },
        
        showValidationSummary: function() {
            // Scroll to first error
            var $firstError = $('.paywall-anywhere-validation-error:visible').first();
            if ($firstError.length) {
                $('html, body').animate({
                    scrollTop: $firstError.offset().top - 100
                }, 300);
            }
        },
        
        toggleStripeTestButton: function() {
            var publishableKey = $('#stripe_publishable_key').val().trim();
            var secretKey = $('#stripe_secret_key').val().trim();
            var $testBtn = $('#test-stripe-connection');
            
            if (publishableKey && secretKey) {
                $testBtn.prop('disabled', false);
            } else {
                $testBtn.prop('disabled', true);
            }
        },
        
        testStripeConnection: function(e) {
            e.preventDefault();
            
            var $btn = $(e.target);
            var $result = $('#stripe-test-result');
            var publishableKey = $('#stripe_publishable_key').val().trim();
            var secretKey = $('#stripe_secret_key').val().trim();
            
            if (!publishableKey || !secretKey) {
                $result.html('<div class="paywall-anywhere-notice paywall-anywhere-notice-error">Please enter both publishable and secret keys.</div>');
                return;
            }
            
            // Show loading state
            $btn.prop('disabled', true).text('Testing...');
            $result.html('<div class="paywall-anywhere-notice">Testing Stripe connection...</div>');
            
            // Make AJAX request to test endpoint
            $.ajax({
                url: paywallAnywhereAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'paywall_anywhere_test_stripe',
                    publishable_key: publishableKey,
                    secret_key: secretKey,
                    nonce: paywallAnywhereAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $result.html('<div class="paywall-anywhere-notice paywall-anywhere-notice-success"><strong>✓ Connection successful!</strong> ' + response.data.message + '</div>');
                    } else {
                        $result.html('<div class="paywall-anywhere-notice paywall-anywhere-notice-error"><strong>✗ Connection failed:</strong> ' + response.data.message + '</div>');
                    }
                },
                error: function() {
                    $result.html('<div class="paywall-anywhere-notice paywall-anywhere-notice-error"><strong>✗ Connection failed:</strong> Network error occurred.</div>');
                },
                complete: function() {
                    $btn.prop('disabled', false).text('Test Connection');
                }
            });
        },
        
        // Premium Items Management
        scanLockedItems: function(e) {
            e.preventDefault();
            
            var $btn = $(e.target);
            var originalText = $btn.text();
            
            // Show loading state
            $btn.prop('disabled', true).text('Scanning...');
            
            // Submit the hidden form
            $('#scan-locked-items-form').submit();
            
            // Note: In a real implementation, you'd want to do this via AJAX
            // For now, we're using form submission which will reload the page
        },
        
        startInlineEdit: function(e) {
            e.preventDefault();
            
            var $btn = $(e.target);
            var itemId = $btn.data('item-id');
            var $row = $btn.closest('tr');
            
            // Hide edit button, show save/cancel buttons
            $btn.hide();
            $row.find('.paywall-anywhere-save-item-btn[data-item-id="' + itemId + '"]').show();
            $row.find('.paywall-anywhere-cancel-edit-btn[data-item-id="' + itemId + '"]').show();
            $row.find('.paywall-anywhere-delete-form').hide();
            
            // Enable inline editing for this row
            $row.find('.paywall-anywhere-inline-edit-price, .paywall-anywhere-inline-edit-expiry').each(function() {
                var $container = $(this);
                var $display = $container.find('.paywall-anywhere-display-value');
                var $input = $container.find('.paywall-anywhere-edit-input');
                
                $display.hide();
                $input.show().focus();
            });
        },
        
        saveInlineEdit: function(e) {
            e.preventDefault();
            
            var $btn = $(e.target);
            var itemId = $btn.data('item-id');
            var $row = $btn.closest('tr');
            
            // Get the new values
            var newPrice = parseInt($row.find('.paywall-anywhere-inline-edit-price .paywall-anywhere-edit-input').val());
            var newExpiry = $row.find('.paywall-anywhere-inline-edit-expiry .paywall-anywhere-edit-input').val();
            
            // Validate
            if (isNaN(newPrice) || newPrice < 0) {
                alert('Please enter a valid price (0 or greater).');
                return;
            }
            
            if (newExpiry !== '' && (isNaN(parseInt(newExpiry)) || parseInt(newExpiry) < 0)) {
                alert('Please enter a valid expiry (0 or greater, empty for never).');
                return;
            }
            
            // Show loading state
            $btn.prop('disabled', true).text('Saving...');
            
            // Set form values and submit
            $('#update-item-id').val(itemId);
            $('#update-item-price').val(newPrice);
            $('#update-item-expires').val(newExpiry);
            $('#update-item-form').submit();
        },
        
        cancelInlineEdit: function(e) {
            e.preventDefault();
            
            var $btn = $(e.target);
            var itemId = $btn.data('item-id');
            var $row = $btn.closest('tr');
            
            // Restore original values and UI state
            $row.find('.paywall-anywhere-inline-edit-price, .paywall-anywhere-inline-edit-expiry').each(function() {
                var $container = $(this);
                var $display = $container.find('.paywall-anywhere-display-value');
                var $input = $container.find('.paywall-anywhere-edit-input');
                
                // Reset input to original value
                $input.val($input.data('original-value') || $input.val());
                
                $input.hide();
                $display.show();
            });
            
            // Restore button states
            $btn.hide();
            $row.find('.paywall-anywhere-save-item-btn[data-item-id="' + itemId + '"]').hide();
            $row.find('.paywall-anywhere-edit-item-btn[data-item-id="' + itemId + '"]').show();
            $row.find('.paywall-anywhere-delete-form').show();
        },
        
        startInlineEditField: function(e) {
            var $container = $(e.target).closest('.paywall-anywhere-inline-edit-price, .paywall-anywhere-inline-edit-expiry');
            var $display = $container.find('.paywall-anywhere-display-value');
            var $input = $container.find('.paywall-anywhere-edit-input');
            
            // Store original value
            $input.data('original-value', $input.val());
            
            $display.hide();
            $input.show().focus().select();
        },
        
        handleInlineEditKeydown: function(e) {
            if (e.which === 13) { // Enter key
                e.preventDefault();
                $(e.target).blur();
            } else if (e.which === 27) { // Escape key
                e.preventDefault();
                var $input = $(e.target);
                var $container = $input.closest('.paywall-anywhere-inline-edit-price, .paywall-anywhere-inline-edit-expiry');
                var $display = $container.find('.paywall-anywhere-display-value');
                
                // Restore original value
                $input.val($input.data('original-value') || $input.val());
                $input.hide();
                $display.show();
            }
        },
        
        handleInlineEditBlur: function(e) {
            var $input = $(e.target);
            var $container = $input.closest('.paywall-anywhere-inline-edit-price, .paywall-anywhere-inline-edit-expiry');
            var $display = $container.find('.paywall-anywhere-display-value');
            
            // Simple validation
            var value = $input.val();
            if ($container.hasClass('paywall-anywhere-inline-edit-price')) {
                if (isNaN(parseInt(value)) || parseInt(value) < 0) {
                    $input.val($input.data('original-value') || 0);
                }
            } else if ($container.hasClass('paywall-anywhere-inline-edit-expiry')) {
                if (value !== '' && (isNaN(parseInt(value)) || parseInt(value) < 0)) {
                    $input.val($input.data('original-value') || '');
                }
            }
            
            $input.hide();
            $display.show();
        }
    };
    
    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        PaywallAnywhereAdmin.init();
    });
    
})(jQuery);