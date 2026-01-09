/**
 * Toggle Switches JS
 * Konvertiert Checkboxen automatisch in Toggle-Switches
 * 
 * @package Ensemble
 */

(function($) {
    'use strict';
    
    /**
     * Initialize toggle switches
     */
    function initToggleSwitches() {
        // Konvertiere Checkboxen in Weekday Selector
        convertWeekdaySelectorToggles();
        
        // Konvertiere Radio Buttons in Recurring End Options
        convertRecurringEndRadios();
        
        // Konvertiere "Show Artist Genres" Toggle
        convertShowArtistGenresToggle();
        
        // Pills werden NICHT konvertiert - sie behalten ihr ursprüngliches Design
    }
    
    /**
     * Konvertiere Weekday Selector Checkboxen zu Toggles
     */
    function convertWeekdaySelectorToggles() {
        $('.es-weekday-selector label').each(function() {
            const $label = $(this);
            const $checkbox = $label.find('input[type="checkbox"]');
            
            if ($checkbox.length && !$checkbox.next('.es-toggle-switch').length) {
                const $toggle = $('<span class="es-toggle-switch"></span>');
                $checkbox.after($toggle);
            }
        });
    }
    
    /**
     * Konvertiere Radio Buttons in Recurring End Options
     */
    function convertRecurringEndRadios() {
        $('.es-recurring-end-options .es-radio-label').each(function() {
            const $label = $(this);
            const $radio = $label.find('input[type="radio"]');
            
            if ($radio.length && !$radio.next('.es-toggle-switch').length) {
                const $toggle = $('<span class="es-toggle-switch"></span>');
                $radio.after($toggle);
                
                // Move toggle to be right after radio
                $toggle.insertAfter($radio);
            }
        });
        
        // Handle enabling/disabling of associated inputs
        $('.es-recurring-end-options input[type="radio"]').on('change', function() {
            const $radio = $(this);
            const $label = $radio.closest('.es-radio-label');
            
            // Disable all inputs in other labels
            $('.es-recurring-end-options .es-radio-label input[type="date"], .es-recurring-end-options .es-radio-label input[type="number"]')
                .prop('disabled', true);
            
            // Enable inputs in this label
            $label.find('input[type="date"], input[type="number"]').prop('disabled', false);
        });
    }
    
    /**
     * Konvertiere "Show Artist Genres" Toggle
     */
    function convertShowArtistGenresToggle() {
        const $checkbox = $('#es-show-artist-genres');
        
        if ($checkbox.length && !$checkbox.next('.es-toggle-switch').length) {
            const $label = $checkbox.closest('label');
            const $toggle = $('<span class="es-toggle-switch"></span>');
            
            // Wenn es ein label.es-toggle-label ist
            if ($label.hasClass('es-toggle-label')) {
                $checkbox.after($toggle);
            } else {
                // Erstelle ein neues Toggle-Label
                const labelText = $checkbox.next('span').text();
                const fieldHelp = $checkbox.siblings('.es-field-help').detach();
                
                const $newLabel = $('<label class="es-toggle-label"></label>');
                $newLabel.append($checkbox);
                $newLabel.append($toggle);
                $newLabel.append('<span class="es-toggle-label-text">' + labelText + '</span>');
                
                if (fieldHelp.length) {
                    $newLabel.append(fieldHelp);
                }
                
                $label.replaceWith($newLabel);
            }
        }
    }
    
    /**
     * Re-initialize toggles when DOM changes (für dynamisch geladene Inhalte)
     */
    function setupMutationObserver() {
        const targetNode = document.querySelector('.es-wizard-wrap') || 
                          document.querySelector('.es-manager-wrap') ||
                          document.body;
        
        if (!targetNode) return;
        
        const config = { childList: true, subtree: true };
        
        const callback = function(mutationsList, observer) {
            for(let mutation of mutationsList) {
                if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                    // Verzögerte Initialisierung für dynamische Inhalte
                    setTimeout(initToggleSwitches, 100);
                }
            }
        };
        
        const observer = new MutationObserver(callback);
        observer.observe(targetNode, config);
    }
    
    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        initToggleSwitches();
        setupMutationObserver();
        
        // Re-init when modal is shown
        $(document).on('click', '#es-create-artist-btn, #es-create-location-btn', function() {
            setTimeout(initToggleSwitches, 300);
        });
        
        // Re-init when form is loaded for editing
        $(document).on('ensemble:artistLoaded ensemble:locationLoaded ensemble:eventLoaded', function() {
            setTimeout(initToggleSwitches, 100);
        });
    });
    
})(jQuery);
