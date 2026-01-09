/**
 * Countdown Addon - JavaScript
 * 
 * @package Ensemble
 * @subpackage Addons/Countdown
 */

(function($) {
    'use strict';
    
    const ESCountdown = {
        
        /**
         * Initialize all countdowns on page
         */
        init: function() {
            $('.es-countdown-wrapper[data-countdown]').each(function() {
                ESCountdown.initCountdown($(this));
            });
        },
        
        /**
         * Initialize single countdown
         */
        initCountdown: function($wrapper) {
            const targetDate = new Date($wrapper.data('countdown'));
            const hideWhenPassed = $wrapper.data('hide-passed') === true || $wrapper.data('hide-passed') === 'true';
            const showRunning = $wrapper.data('show-running') === true || $wrapper.data('show-running') === 'true';
            const runningText = $wrapper.data('running-text');
            const passedText = $wrapper.data('passed-text');
            
            // Store previous values for animation
            $wrapper.data('prevValues', {});
            
            // Update immediately
            ESCountdown.updateCountdown($wrapper, targetDate, hideWhenPassed, showRunning, runningText, passedText);
            
            // Update every second
            const interval = setInterval(function() {
                ESCountdown.updateCountdown($wrapper, targetDate, hideWhenPassed, showRunning, runningText, passedText);
            }, 1000);
            
            // Store interval for cleanup
            $wrapper.data('interval', interval);
        },
        
        /**
         * Update countdown display
         */
        updateCountdown: function($wrapper, targetDate, hideWhenPassed, showRunning, runningText, passedText) {
            const now = new Date();
            const diff = targetDate - now;
            
            // Event has passed
            if (diff <= 0) {
                this.handlePassed($wrapper, hideWhenPassed, showRunning, runningText, passedText);
                return;
            }
            
            // Calculate time units
            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
            const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((diff % (1000 * 60)) / 1000);
            
            // Update display with animation
            this.updateUnit($wrapper, 'days', days);
            this.updateUnit($wrapper, 'hours', hours);
            this.updateUnit($wrapper, 'minutes', minutes);
            this.updateUnit($wrapper, 'seconds', seconds);
            
            // Update circle progress (if style is circle)
            if ($wrapper.hasClass('es-countdown-style-circle')) {
                this.updateCircleProgress($wrapper, days, hours, minutes, seconds);
            }
        },
        
        /**
         * Update single unit with animation
         */
        updateUnit: function($wrapper, unit, value) {
            const $unit = $wrapper.find('.es-countdown-' + unit);
            const $value = $unit.find('.es-countdown-value');
            
            if ($value.length === 0) return;
            
            const displayValue = value.toString().padStart(2, '0');
            const prevValues = $wrapper.data('prevValues') || {};
            
            // Only animate if value changed
            if (prevValues[unit] !== displayValue) {
                $value.addClass('es-countdown-flip');
                
                setTimeout(function() {
                    $value.text(displayValue);
                    $value.removeClass('es-countdown-flip');
                }, 150);
                
                prevValues[unit] = displayValue;
                $wrapper.data('prevValues', prevValues);
            }
            
            // Update label for singular/plural
            if (esCountdown && esCountdown.labels) {
                const $label = $unit.find('.es-countdown-label');
                if ($label.length > 0) {
                    const labelKey = value === 1 ? unit.slice(0, -1) : unit; // Remove 's' for singular
                    const label = esCountdown.labels[labelKey] || esCountdown.labels[unit];
                    if (label) {
                        $label.text(label);
                    }
                }
            }
        },
        
        /**
         * Update circle progress bars
         */
        updateCircleProgress: function($wrapper, days, hours, minutes, seconds) {
            // Days: percentage of 30 days
            const daysProgress = Math.min((days / 30) * 100, 100);
            $wrapper.find('.es-countdown-days').css('--progress', daysProgress);
            
            // Hours: percentage of 24
            const hoursProgress = (hours / 24) * 100;
            $wrapper.find('.es-countdown-hours').css('--progress', hoursProgress);
            
            // Minutes: percentage of 60
            const minutesProgress = (minutes / 60) * 100;
            $wrapper.find('.es-countdown-minutes').css('--progress', minutesProgress);
            
            // Seconds: percentage of 60
            const secondsProgress = (seconds / 60) * 100;
            $wrapper.find('.es-countdown-seconds').css('--progress', secondsProgress);
        },
        
        /**
         * Handle passed event
         */
        handlePassed: function($wrapper, hideWhenPassed, showRunning, runningText, passedText) {
            // Clear interval
            const interval = $wrapper.data('interval');
            if (interval) {
                clearInterval(interval);
            }
            
            // Hide if setting enabled
            if (hideWhenPassed) {
                $wrapper.fadeOut(300);
                return;
            }
            
            // Check if we should show "running" status
            // (This would need end time logic, simplified here)
            
            // Show passed message
            const $timer = $wrapper.find('.es-countdown-timer');
            if ($timer.length > 0) {
                $timer.replaceWith(
                    '<div class="es-countdown-status es-countdown-status-passed">' +
                    '<span class="es-countdown-status-text">' + (passedText || 'Event beendet') + '</span>' +
                    '</div>'
                );
            }
            
            $wrapper.addClass('es-countdown-passed');
        },
        
        /**
         * Destroy countdown (cleanup)
         */
        destroy: function($wrapper) {
            const interval = $wrapper.data('interval');
            if (interval) {
                clearInterval(interval);
            }
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        ESCountdown.init();
    });
    
    // Cleanup on page unload
    $(window).on('beforeunload', function() {
        $('.es-countdown-wrapper[data-countdown]').each(function() {
            ESCountdown.destroy($(this));
        });
    });
    
})(jQuery);
