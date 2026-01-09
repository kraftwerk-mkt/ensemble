/**
 * Ensemble Gallery Pro - Admin JavaScript
 * 
 * @package Ensemble
 * @subpackage Addons/Gallery Pro
 */

(function($) {
    'use strict';
    
    /**
     * Gallery Pro Admin
     */
    var EnsembleGalleryProAdmin = {
        
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
            // Layout preview when changing default layout
            $(document).on('change', '.es-layout-option input', function() {
                var layout = $(this).val();
                EnsembleGalleryProAdmin.updateLayoutPreview(layout);
            });
        },
        
        /**
         * Update layout preview
         * 
         * @param {string} layout
         */
        updateLayoutPreview: function(layout) {
            // Could add dynamic preview here
            console.log('Layout changed to:', layout);
        }
    };
    
    /**
     * Document ready
     */
    $(document).ready(function() {
        EnsembleGalleryProAdmin.init();
    });
    
})(jQuery);
