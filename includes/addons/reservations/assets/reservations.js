/**
 * Ensemble Reservations Pro - Frontend Scripts
 * 
 * @package Ensemble
 * @subpackage Addons/Reservations Pro
 */

(function($) {
    'use strict';
    
    var EnsembleReservations = {
        
        config: window.ensembleReservations || {},
        
        init: function() {
            // Form handling is done inline in template
            // This file is for additional enhancements
            
            this.initGuestCounter();
            this.initTypeSelector();
        },
        
        initGuestCounter: function() {
            // Already handled in template, but add keyboard support
            $(document).on('keydown', '.es-guests-selector input', function(e) {
                var $input = $(this);
                var val = parseInt($input.val()) || 1;
                var min = parseInt($input.attr('min')) || 1;
                var max = parseInt($input.attr('max')) || 20;
                
                if (e.key === 'ArrowUp' && val < max) {
                    $input.val(val + 1);
                    e.preventDefault();
                } else if (e.key === 'ArrowDown' && val > min) {
                    $input.val(val - 1);
                    e.preventDefault();
                }
            });
        },
        
        initTypeSelector: function() {
            // Keyboard accessibility for type buttons
            $(document).on('keydown', '.es-type-button', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    $(this).click();
                    e.preventDefault();
                }
            });
        }
    };
    
    $(document).ready(function() {
        EnsembleReservations.init();
    });
    
    window.EnsembleReservations = EnsembleReservations;
    
})(jQuery);
