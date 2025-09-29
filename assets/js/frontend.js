/**
 * PayWall Premium Content - Frontend JavaScript
 */

(function($) {
    'use strict';
    
    /**
     * PayWall Frontend Handler
     */
    var PayWallFrontend = {
        
        init: function() {
            this.bindEvents();
        },
        
        bindEvents: function() {
            // Handle unlock button clicks
            $(document).on('click', '.pc-unlock-btn', this.handleUnlockClick.bind(this));
            
            // Handle payment modal events
            $(document).on('click', '.pc-payment-modal .pc-modal-close', this.closePaymentModal.bind(this));
            $(document).on('click', '.pc-payment-modal-backdrop', this.closePaymentModal.bind(this));
            
            // Handle payment form submission
            $(document).on('submit', '.pc-payment-form', this.handlePaymentSubmit.bind(this));
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
                url: pc_ajax.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'pc_create_item_for_purchase',
                    nonce: pc_ajax.nonce,
                    post_id: postId,
                    scope: scope,
                    selector: selector
                },
                success: function(response) {
                    if (response.success && response.data.item_id) {
                        self.showPaymentModal(response.data.item_id);
                    } else {
                        self.showError(response.data.message || 'Failed to create payment item');
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
            $('.pc-payment-modal').fadeIn(200);
            $('body').addClass('pc-modal-open');
            
            // Focus management for accessibility
            $('.pc-payment-modal .pc-email-input').focus();
        },
        
        closePaymentModal: function(e) {
            if (e) {
                e.preventDefault();
            }
            
            $('.pc-payment-modal').fadeOut(200, function() {
                $(this).remove();
            });
            $('body').removeClass('pc-modal-open');
        },
        
        getPaymentModalHtml: function(itemId) {
            return `
                <div class="pc-payment-modal">
                    <div class="pc-payment-modal-backdrop"></div>
                    <div class="pc-payment-modal-content">
                        <div class="pc-payment-modal-header">
                            <h3>Unlock Premium Content</h3>
                            <button class="pc-modal-close" aria-label="Close">×</button>
                        </div>
                        <div class="pc-payment-modal-body">
                            <form class="pc-payment-form" data-item-id="${itemId}">
                                <div class="pc-form-group">
                                    <label for="pc-email">Email Address</label>
                                    <input type="email" id="pc-email" name="email" class="pc-email-input" required>
                                    <small>You'll receive a magic link to access the content</small>
                                </div>
                                <div class="pc-form-actions">
                                    <button type="submit" class="pc-pay-btn">
                                        <span class="pc-btn-text">Continue to Payment</span>
                                        <span class="pc-btn-loader" style="display: none;">Processing...</span>
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
            var $button = $form.find('.pc-pay-btn');
            var itemId = $form.data('item-id');
            var email = $form.find('input[name="email"]').val();
            
            if (!email || !itemId) {
                this.showError('Please enter a valid email address');
                return;
            }
            
            this.setButtonLoading($button, true);
            
            $.ajax({
                url: pc_ajax.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'pc_create_payment',
                    nonce: pc_ajax.nonce,
                    item_id: itemId,
                    email: email
                },
                success: function(response) {
                    if (response.success && response.checkout_url) {
                        window.location.href = response.checkout_url;
                    } else {
                        this.showError(response.error || 'Payment initialization failed');
                        this.setButtonLoading($button, false);
                    }
                }.bind(this),
                error: function() {
                    this.showError('Network error occurred');
                    this.setButtonLoading($button, false);
                }.bind(this)
            });
        },
        
        setButtonLoading: function($button, loading) {
            if (loading) {
                $button.prop('disabled', true);
                $button.find('.pc-btn-text').hide();
                $button.find('.pc-btn-loader').show();
            } else {
                $button.prop('disabled', false);
                $button.find('.pc-btn-text').show();
                $button.find('.pc-btn-loader').hide();
            }
        },
        
        showError: function(message) {
            // Create or update error notification
            var $error = $('.pc-error-notice');
            
            if ($error.length === 0) {
                $error = $('<div class="pc-error-notice"></div>');
                $('body').append($error);
            }
            
            $error.text(message).fadeIn(200);
            
            // Auto-hide after 5 seconds
            setTimeout(function() {
                $error.fadeOut(200);
            }, 5000);
        },
        
        showSuccess: function(message) {
            // Create or update success notification
            var $success = $('.pc-success-notice');
            
            if ($success.length === 0) {
                $success = $('<div class="pc-success-notice"></div>');
                $('body').append($success);
            }
            
            $success.text(message).fadeIn(200);
            
            // Auto-hide after 3 seconds
            setTimeout(function() {
                $success.fadeOut(200);
            }, 3000);
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        PayWallFrontend.init();
    });
    
})(jQuery);