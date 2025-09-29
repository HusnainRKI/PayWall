/**
 * Paywall Anywhere - Frontend JavaScript
 */

(function($) {
    'use strict';
    
    /**
     * Paywall Anywhere Frontend Handler
     */
    var PaywallAnywhereFrontend = {
        
        init: function() {
            this.bindEvents();
        },
        
        bindEvents: function() {
            // Handle unlock button clicks
            $(document).on('click', '.paywall-anywhere-unlock-btn', this.handleUnlockClick.bind(this));
            
            // Handle payment modal events
            $(document).on('click', '.paywall-anywhere-payment-modal .paywall-anywhere-modal-close', this.closePaymentModal.bind(this));
            $(document).on('click', '.paywall-anywhere-payment-modal-backdrop', this.closePaymentModal.bind(this));
            
            // Handle payment form submission
            $(document).on('submit', '.paywall-anywhere-payment-form', this.handlePaymentSubmit.bind(this));
            
            // Handle ESC key to close modal
            $(document).on('keydown', this.handleKeydown.bind(this));
        },
        
        handleUnlockClick: function(e) {
            e.preventDefault();
            
            var $button = $(e.target);
            var itemId = $button.data('item-id');
            var postId = $button.data('post-id');
            var scope = $button.data('scope');
            var selector = $button.data('selector');
            
            if (!itemId && postId && scope && selector) {
                // Create item on the fly for granular locking
                this.createItemAndShowPayment(postId, scope, selector);
            } else if (itemId) {
                this.showPaymentModal(itemId);
            }
        },
        
        createItemAndShowPayment: function(postId, scope, selector) {
            var self = this;
            
            $.ajax({
                url: paywall_anywhere_ajax.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'paywall_anywhere_create_item_for_purchase',
                    nonce: paywall_anywhere_ajax.nonce,
                    post_id: postId,
                    scope: scope,
                    selector: selector
                },
                success: function(response) {
                    if (response.success && response.item_id) {
                        self.showPaymentModal(response.item_id);
                    } else {
                        self.showError(response.message || 'Failed to create payment item');
                    }
                },
                error: function() {
                    self.showError('Network error occurred');
                }
            });
        },
        
        showPaymentModal: function(itemId) {
            var modalHtml = this.getPaymentModalHtml(itemId);
            $('body').append(modalHtml);
            $('.paywall-anywhere-payment-modal').fadeIn(200);
            $('body').addClass('paywall-anywhere-modal-open');
            
            // Focus management for accessibility
            $('.paywall-anywhere-payment-modal .paywall-anywhere-email-input').focus();
        },
        
        getPaymentModalHtml: function(itemId) {
            return `
                <div class="paywall-anywhere-payment-modal" role="dialog" aria-labelledby="paywall-anywhere-modal-title" aria-describedby="paywall-anywhere-modal-description">
                    <div class="paywall-anywhere-payment-modal-backdrop"></div>
                    <div class="paywall-anywhere-modal-content">
                        <div class="paywall-anywhere-modal-header">
                            <h2 id="paywall-anywhere-modal-title">${paywall_anywhere_ajax.strings.unlock_content || 'Unlock Content'}</h2>
                            <button class="paywall-anywhere-modal-close" aria-label="${paywall_anywhere_ajax.strings.close || 'Close'}">&times;</button>
                        </div>
                        <div class="paywall-anywhere-modal-body">
                            <p id="paywall-anywhere-modal-description">${paywall_anywhere_ajax.strings.payment_description || 'Enter your email address to unlock this premium content.'}</p>
                            <form class="paywall-anywhere-payment-form" data-item-id="${itemId}">
                                <div class="paywall-anywhere-form-group">
                                    <label for="paywall-anywhere-email">${paywall_anywhere_ajax.strings.email_address || 'Email Address'}</label>
                                    <input type="email" id="paywall-anywhere-email" class="paywall-anywhere-email-input" required>
                                </div>
                                <div class="paywall-anywhere-form-actions">
                                    <button type="submit" class="paywall-anywhere-btn paywall-anywhere-btn-primary">
                                        <span class="paywall-anywhere-btn-text">${paywall_anywhere_ajax.strings.proceed_to_payment || 'Proceed to Payment'}</span>
                                        <span class="paywall-anywhere-spinner" style="display: none;"></span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            `;
        },
        
        handlePaymentSubmit: function(e) {
            e.preventDefault();
            
            var $form = $(e.target);
            var $button = $form.find('button[type="submit"]');
            var $spinner = $button.find('.paywall-anywhere-spinner');
            var $buttonText = $button.find('.paywall-anywhere-btn-text');
            
            var itemId = $form.data('item-id');
            var email = $form.find('.paywall-anywhere-email-input').val();
            
            if (!email || !itemId) {
                this.showError('Please provide a valid email address');
                return;
            }
            
            // Show loading state
            $button.prop('disabled', true);
            $spinner.show();
            $buttonText.text(paywall_anywhere_ajax.strings.processing || 'Processing...');
            
            var self = this;
            
            $.ajax({
                url: paywall_anywhere_ajax.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'paywall_anywhere_create_payment',
                    nonce: paywall_anywhere_ajax.nonce,
                    item_id: itemId,
                    email: email
                },
                success: function(response) {
                    if (response.success && response.checkout_url) {
                        // Redirect to payment provider
                        window.location.href = response.checkout_url;
                    } else {
                        self.showError(response.message || 'Payment setup failed');
                        self.resetPaymentButton($button, $spinner, $buttonText);
                    }
                },
                error: function() {
                    self.showError('Network error occurred');
                    self.resetPaymentButton($button, $spinner, $buttonText);
                }
            });
        },
        
        resetPaymentButton: function($button, $spinner, $buttonText) {
            $button.prop('disabled', false);
            $spinner.hide();
            $buttonText.text(paywall_anywhere_ajax.strings.proceed_to_payment || 'Proceed to Payment');
        },
        
        closePaymentModal: function(e) {
            if (e) {
                e.preventDefault();
            }
            
            $('.paywall-anywhere-payment-modal').fadeOut(200, function() {
                $(this).remove();
            });
            $('body').removeClass('paywall-anywhere-modal-open');
        },
        
        handleKeydown: function(e) {
            // Close modal on ESC key
            if (e.keyCode === 27 && $('.paywall-anywhere-payment-modal').is(':visible')) {
                this.closePaymentModal();
            }
        },
        
        showError: function(message) {
            // Remove existing error messages
            $('.paywall-anywhere-error-message').remove();
            
            var errorHtml = `
                <div class="paywall-anywhere-error-message" role="alert">
                    <p>${message}</p>
                    <button class="paywall-anywhere-error-close" aria-label="${paywall_anywhere_ajax.strings.close || 'Close'}">&times;</button>
                </div>
            `;
            
            $('body').append(errorHtml);
            
            // Auto-hide error after 5 seconds
            setTimeout(function() {
                $('.paywall-anywhere-error-message').fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
            
            // Manual close handler
            $(document).on('click', '.paywall-anywhere-error-close', function() {
                $(this).closest('.paywall-anywhere-error-message').fadeOut(300, function() {
                    $(this).remove();
                });
            });
        }
    };
    
    /**
     * Initialize when DOM is ready
     */
    $(document).ready(function() {
        PaywallAnywhereFrontend.init();
    });
    
    /**
     * Handle magic link success parameters
     */
    $(document).ready(function() {
        var urlParams = new URLSearchParams(window.location.search);
        
        if (urlParams.get('paywall_anywhere_success') === '1') {
            // Show success message
            var successHtml = `
                <div class="paywall-anywhere-success-message" role="alert">
                    <p>${paywall_anywhere_ajax.strings.payment_success || 'Payment successful! You now have access to this content.'}</p>
                    <button class="paywall-anywhere-success-close" aria-label="${paywall_anywhere_ajax.strings.close || 'Close'}">&times;</button>
                </div>
            `;
            
            $('body').append(successHtml);
            
            // Auto-hide success message after 10 seconds
            setTimeout(function() {
                $('.paywall-anywhere-success-message').fadeOut(300, function() {
                    $(this).remove();
                });
            }, 10000);
            
            // Manual close handler
            $(document).on('click', '.paywall-anywhere-success-close', function() {
                $(this).closest('.paywall-anywhere-success-message').fadeOut(300, function() {
                    $(this).remove();
                });
            });
            
            // Clean up URL
            if (history.replaceState) {
                var cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
                history.replaceState({}, document.title, cleanUrl);
            }
        }
    });
    
})(jQuery);