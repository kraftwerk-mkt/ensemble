/**
 * Staff Frontend JavaScript
 * 
 * Handles contact form submissions and file uploads
 * 
 * @package Ensemble
 * @subpackage Addons/Staff
 */

(function($) {
    'use strict';
    
    const StaffFrontend = {
        
        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            const self = this;
            
            // Contact form submission
            $(document).on('submit', '.es-contact-form', function(e) {
                e.preventDefault();
                self.submitForm($(this));
            });
            
            // File input change
            $(document).on('change', '.es-contact-form__file-input', function() {
                self.handleFileSelect($(this));
            });
            
            // Drag and drop
            $(document).on('dragover dragenter', '.es-contact-form__file-wrapper', function(e) {
                e.preventDefault();
                $(this).addClass('is-dragover');
            });
            
            $(document).on('dragleave drop', '.es-contact-form__file-wrapper', function(e) {
                e.preventDefault();
                $(this).removeClass('is-dragover');
            });
            
            $(document).on('drop', '.es-contact-form__file-wrapper', function(e) {
                e.preventDefault();
                const files = e.originalEvent.dataTransfer.files;
                if (files.length) {
                    $(this).find('.es-contact-form__file-input')[0].files = files;
                    $(this).find('.es-contact-form__file-input').trigger('change');
                }
            });
        },
        
        /**
         * Handle file selection
         * 
         * @param {jQuery} $input
         */
        handleFileSelect: function($input) {
            const $wrapper = $input.closest('.es-contact-form__file-wrapper');
            const $fileName = $wrapper.find('.es-contact-form__file-name');
            
            if ($input[0].files.length > 0) {
                const file = $input[0].files[0];
                
                // Validate file size
                const maxSize = this.getMaxFileSize($input);
                if (file.size > maxSize) {
                    alert(ensembleStaffFrontend.strings.fileTooLarge.replace('%s', Math.round(maxSize / 1024 / 1024)));
                    $input.val('');
                    $wrapper.removeClass('has-file');
                    return;
                }
                
                $fileName.text(file.name);
                $wrapper.addClass('has-file');
            } else {
                $fileName.text('');
                $wrapper.removeClass('has-file');
            }
        },
        
        /**
         * Get max file size from accept attribute
         * 
         * @param {jQuery} $input
         * @return {number} Max size in bytes
         */
        getMaxFileSize: function($input) {
            // Default to 10MB, actual limit is enforced server-side
            return 10 * 1024 * 1024;
        },
        
        /**
         * Submit contact form
         * 
         * @param {jQuery} $form
         */
        submitForm: function($form) {
            const self = this;
            const $btn = $form.find('.es-btn--primary');
            const $success = $form.find('.es-contact-form__success');
            const $error = $form.find('.es-contact-form__error');
            
            // Hide previous messages
            $success.hide();
            $error.hide();
            
            // Show loading state
            $btn.addClass('is-loading').prop('disabled', true);
            
            // Prepare form data
            const formData = new FormData($form[0]);
            formData.append('action', 'es_submit_abstract');
            formData.append('nonce', ensembleStaffFrontend.nonce);
            
            $.ajax({
                url: ensembleStaffFrontend.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        $success.find('span').text(response.data.message);
                        $success.slideDown();
                        
                        // Reset form
                        $form[0].reset();
                        $form.find('.es-contact-form__file-wrapper').removeClass('has-file');
                        
                        // Scroll to message
                        self.scrollToElement($success);
                    } else {
                        // Show error message
                        $error.find('span').text(response.data.message || ensembleStaffFrontend.strings.error);
                        $error.slideDown();
                        
                        self.scrollToElement($error);
                    }
                },
                error: function(xhr, status, error) {
                    // Show generic error
                    $error.find('span').text(ensembleStaffFrontend.strings.error);
                    $error.slideDown();
                    
                    self.scrollToElement($error);
                },
                complete: function() {
                    // Remove loading state
                    $btn.removeClass('is-loading').prop('disabled', false);
                }
            });
        },
        
        /**
         * Scroll to element smoothly
         * 
         * @param {jQuery} $element
         */
        scrollToElement: function($element) {
            if ($element.length) {
                $('html, body').animate({
                    scrollTop: $element.offset().top - 100
                }, 500);
            }
        }
    };
    
    // Make it global for debugging
    window.StaffFrontend = StaffFrontend;
    
    // Initialize on document ready
    $(document).ready(function() {
        StaffFrontend.init();
    });
    
})(jQuery);
