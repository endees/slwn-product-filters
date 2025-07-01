/**
 * Admin JavaScript for SLWN Product Filters
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // Initialize admin functionality
        SLWNProductFiltersAdmin.init();
        
    });
    
    /**
     * Admin object
     */
    window.SLWNProductFiltersAdmin = {
        
        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
        },
        
        /**
         * Bind events
         */
        bindEvents: function() {
            // Settings form submission
            $('form[action="options.php"]').on('submit', this.handleFormSubmit);
            
            // Test button click
            $(document).on('click', '.slwn-product-filters-test-button', this.handleTestButton);
            
            // Enable/disable filters toggle
            $(document).on('change', '#enable_filters', this.handleFiltersToggle);
        },
        
        /**
         * Handle form submit
         */
        handleFormSubmit: function(e) {
            var $form = $(this);
            var $submitButton = $form.find('input[type="submit"]');
            
            // Show loading state
            $submitButton.prop('disabled', true);
            $submitButton.after('<span class="slwn-product-filters-spinner"></span>');
        },
        
        /**
         * Handle test button
         */
        handleTestButton: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            $button.prop('disabled', true);
            $button.text('Testowanie...');
            
            // AJAX request
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'slwn_get_filter_options',
                    nonce: $('#slwn_product_filters_admin_nonce').val()
                },
                success: function(response) {
                    if (response.success) {
                        SLWNProductFiltersAdmin.showNotice(response.message, 'success');
                    } else {
                        SLWNProductFiltersAdmin.showNotice(response.message || 'Wystąpił błąd', 'error');
                    }
                },
                error: function() {
                    SLWNProductFiltersAdmin.showNotice('Wystąpił błąd połączenia', 'error');
                },
                complete: function() {
                    $button.prop('disabled', false);
                    $button.text('Test');
                }
            });
        },
        
        /**
         * Handle filters toggle
         */
        handleFiltersToggle: function() {
            var $checkbox = $(this);
            var isEnabled = $checkbox.prop('checked');
            
            // Show/hide related options
            $('.filters-dependent').toggle(isEnabled);
            
            if (isEnabled) {
                SLWNProductFiltersAdmin.showNotice('Filtry produktów zostały włączone', 'info');
            } else {
                SLWNProductFiltersAdmin.showNotice('Filtry produktów zostały wyłączone', 'info');
            }
        },
        
        /**
         * Show admin notice
         */
        showNotice: function(message, type) {
            type = type || 'info';
            
            var $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
            $('.wrap h1').after($notice);
            
            // Auto dismiss after 5 seconds
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        },
        
        /**
         * Validate form
         */
        validateForm: function($form) {
            var isValid = true;
            
            // Add validation logic here
            
            return isValid;
        }
    };
    
})(jQuery);
