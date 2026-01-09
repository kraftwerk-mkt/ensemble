/**
 * Ensemble Recurring Events JavaScript
 * 
 * @package Ensemble
 */

(function($) {
    'use strict';
    
    // State
    let recurringPreviewData = null;
    
    /**
     * Initialize recurring events functionality
     */
    function initRecurring() {
        console.log('Ensemble Recurring: Initializing...');
        console.log('Toggle element:', $('#es-recurring-toggle').length);
        
        // Toggle recurring options
        $('#es-recurring-toggle').on('change', function() {
            console.log('Toggle changed:', $(this).is(':checked'));
            if ($(this).is(':checked')) {
                $('#es-recurring-options').slideDown();
                // Set start date to event date and pre-select weekday/day of month
                updateRecurringStartDate();
                const pattern = $('#es-recurring-pattern').val();
                if (pattern === 'weekly') {
                    preselectWeekday();
                }
                // Monthly pattern doesn't need preselection - uses day of month from start_date
            } else {
                $('#es-recurring-options').slideUp();
            }
        });
        
        // Pattern selection
        $('#es-recurring-pattern').on('change', function() {
            const pattern = $(this).val();
            console.log('Pattern changed:', pattern);
            showPatternOptions(pattern);
            
            // Hide preview when pattern changes - user needs to re-preview
            $('#es-recurring-preview').slideUp();
            
            // Auto-select based on event date
            if (pattern === 'weekly') {
                preselectWeekday();
            }
            // Monthly pattern doesn't need preselection - uses day of month from start_date
        });
        
        // Preview button
        $('#es-recurring-preview-btn').on('click', function() {
            console.log('Preview button clicked');
            previewRecurringInstances();
        });
        
        // Event date change - update weekday/day of month preselection
        $('#es-event-date').on('change', function() {
            if ($('#es-recurring-toggle').is(':checked')) {
                const pattern = $('#es-recurring-pattern').val();
                if (pattern === 'weekly') {
                    preselectWeekday();
                }
                // Monthly pattern doesn't need preselection - uses day of month from start_date
            }
        });
        
        // End type radio buttons
        $('input[name="recurring_end_type"]').on('change', function() {
            const endType = $(this).val();
            
            // Hide preview when settings change
            $('#es-recurring-preview').slideUp();
            
            // Disable all end inputs
            $('#es-recurring-end-date').prop('disabled', true);
            $('#es-recurring-end-count').prop('disabled', true);
            
            // Enable the selected one
            if (endType === 'date') {
                $('#es-recurring-end-date').prop('disabled', false);
            } else if (endType === 'count') {
                $('#es-recurring-end-count').prop('disabled', false);
            }
        });
        
        // Hide preview when any recurring settings change
        $('#es-recurring-daily-interval, #es-recurring-weekly-interval, #es-recurring-monthly-interval, #es-recurring-end-date, #es-recurring-end-count').on('change', function() {
            $('#es-recurring-preview').slideUp();
        });
        
        // Hide preview when weekdays change
        $('input[name="recurring_weekdays[]"]').on('change', function() {
            $('#es-recurring-preview').slideUp();
        });
        
        // Hide preview when custom dates change
        $('#es-recurring-custom-dates').on('input', function() {
            $('#es-recurring-preview').slideUp();
        });
        
        // Initialize with default pattern
        showPatternOptions('weekly');
        
        console.log('Ensemble Recurring: Initialized successfully');
    }
    
    /**
     * Pre-select the weekday based on event date (for Weekly pattern)
     */
    function preselectWeekday() {
        const eventDate = $('#es-event-date').val();
        if (!eventDate) {
            return;
        }
        
        // IMPORTANT: Append T00:00:00 to treat date as local time, not UTC
        // Without this, "2025-11-26" might be interpreted as Nov 25 in certain timezones
        const date = new Date(eventDate + 'T00:00:00');
        let dayOfWeek = date.getDay();
        
        // Convert to ISO format (1=Monday, 7=Sunday)
        // JavaScript: 0=Sunday, 1=Monday, ... 6=Saturday
        // ISO/PHP: 1=Monday, 2=Tuesday, ... 7=Sunday
        if (dayOfWeek === 0) {
            dayOfWeek = 7; // Sunday
        }
        
        // Clear all checkboxes first
        $('input[name="recurring_weekdays[]"]').prop('checked', false);
        
        // Check the corresponding day
        $('input[name="recurring_weekdays[]"][value="' + dayOfWeek + '"]').prop('checked', true);
        
        console.log('Preselected weekday:', dayOfWeek, 'for date:', eventDate, 'day name:', date.toLocaleDateString('de-DE', {weekday: 'long'}));
    }
    
    /**
     * Show pattern-specific options
     */
    function showPatternOptions(pattern) {
        // Hide all pattern options
        $('.es-recurring-pattern-options').hide();
        
        // Show selected pattern options
        $('#es-recurring-' + pattern).show();
        
        // Hide "Ends" section for custom pattern (user defines exact dates)
        const $endsSection = $('.es-recurring-end-options').closest('.es-form-row');
        if (pattern === 'custom') {
            $endsSection.hide();
        } else {
            $endsSection.show();
        }
    }
    
    /**
     * Update recurring start date from event date
     */
    function updateRecurringStartDate() {
        const eventDate = $('#es-event-date').val();
        if (eventDate) {
            // Store in hidden field if needed
            // For now, we'll use event_date as the start date
        }
    }
    
    /**
     * Preview recurring instances
     */
    function previewRecurringInstances() {
        const rules = collectRecurringRules();
        
        if (!rules) {
            showMessage('Please fill in all required fields', 'error');
            return;
        }
        
        // Debug: Log the rules being sent
        console.log('Preview rules:', rules);
        
        // Show loading
        $('#es-recurring-preview-btn').prop('disabled', true).text('Generating...');
        
        $.ajax({
            url: ensembleAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'es_preview_recurring',
                nonce: ensembleAjax.nonce,
                rules: rules
            },
            success: function(response) {
                console.log('Preview response:', response);
                if (response.success) {
                    displayRecurringPreview(response.data.instances, response.data.count, response.data.months);
                    recurringPreviewData = response.data;
                } else {
                    showMessage(response.data.message || 'Failed to generate preview', 'error');
                }
            },
            error: function() {
                showMessage('Error generating preview', 'error');
            },
            complete: function() {
                $('#es-recurring-preview-btn').prop('disabled', false).text('Preview Dates');
            }
        });
    }
    
    /**
     * Collect recurring rules from form
     */
    function collectRecurringRules() {
        const pattern = $('#es-recurring-pattern').val();
        const startDate = $('#es-event-date').val();
        const timeStart = $('#es-event-time').val();
        const timeEnd = $('#es-event-time-end').val();
        
        if (!startDate) {
            return null;
        }
        
        const rules = {
            pattern: pattern,
            start_date: startDate,
            time_start: timeStart,
            time_end: timeEnd
        };
        
        // End options
        const endType = $('input[name="recurring_end_type"]:checked').val();
        rules.end_type = endType;
        
        if (endType === 'date') {
            rules.end_date = $('#es-recurring-end-date').val();
        } else if (endType === 'count') {
            rules.end_count = parseInt($('#es-recurring-end-count').val()) || 10;
        }
        
        // Collect pattern-specific data
        switch (pattern) {
            case 'daily':
                rules.interval = $('#es-recurring-daily-interval').val() || 1;
                break;
                
            case 'weekly':
                rules.interval = $('#es-recurring-weekly-interval').val() || 1;
                rules.weekdays = [];
                $('input[name="recurring_weekdays[]"]:checked').each(function() {
                    rules.weekdays.push(parseInt($(this).val()));
                });
                if (rules.weekdays.length === 0) {
                    // Use current day of week if none selected
                    const date = new Date(startDate);
                    rules.weekdays = [date.getDay() || 7]; // Convert Sunday from 0 to 7
                }
                break;
                
            case 'monthly':
                rules.interval = $('#es-recurring-monthly-interval').val() || 1;
                // Monthly repeats on the same day of month as start_date
                // No need to collect weekdays
                break;
                
            case 'custom':
                const customDates = $('#es-recurring-custom-dates').val();
                if (!customDates) {
                    return null;
                }
                rules.custom_dates = customDates.split('\n').map(d => d.trim()).filter(d => d);
                break;
        }
        
        return rules;
    }
    
    /**
     * Display recurring preview
     */
    function displayRecurringPreview(instances, count, months) {
        const $preview = $('#es-recurring-preview');
        const $list = $('#es-recurring-preview-list');
        
        // Update count with better messaging
        let countText = '(' + count + ' date' + (count !== 1 ? 's' : '');
        if (months) {
            countText += ' in next ' + months + ' month' + (months !== 1 ? 's' : '');
        }
        countText += ')';
        $('#es-recurring-preview-count').text(countText);
        
        // Build list HTML
        let html = '<ul>';
        instances.slice(0, 10).forEach(function(instance) {
            html += '<li>';
            html += '<strong>' + instance.formatted_date + '</strong>';
            if (instance.time_start) {
                html += ' at ' + instance.time_start;
                if (instance.time_end) {
                    html += ' - ' + instance.time_end;
                }
            }
            html += '</li>';
        });
        html += '</ul>';
        
        if (count > 10) {
            html += '<p><em>Showing first 10 of ' + count + ' dates...</em></p>';
        }
        
        $list.html(html);
        $preview.slideDown();
    }
    
    /**
     * Get recurring rules for saving
     */
    function getRecurringRulesForSave() {
        if (!$('#es-recurring-toggle').is(':checked')) {
            return null;
        }
        
        return collectRecurringRules();
    }
    
    /**
     * Load recurring rules into form
     */
    function loadRecurringRules(eventId) {
        $.ajax({
            url: ensembleAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'es_get_recurring_rules',
                nonce: ensembleAjax.nonce,
                event_id: eventId
            },
            success: function(response) {
                if (response.success && response.data.is_recurring) {
                    const rules = response.data.rules;
                    
                    // Check the recurring toggle
                    $('#es-recurring-toggle').prop('checked', true).trigger('change');
                    
                    // Set pattern
                    $('#es-recurring-pattern').val(rules.pattern).trigger('change');
                    
                    // Set pattern-specific fields
                    switch (rules.pattern) {
                        case 'daily':
                            $('#es-recurring-daily-interval').val(rules.interval || 1);
                            break;
                            
                        case 'weekly':
                            $('#es-recurring-weekly-interval').val(rules.interval || 1);
                            // Set weekdays
                            if (rules.weekdays) {
                                rules.weekdays.forEach(function(day) {
                                    $('input[name="recurring_weekdays[]"][value="' + day + '"]').prop('checked', true);
                                });
                            }
                            break;
                            
                        case 'monthly':
                            $('#es-recurring-monthly-interval').val(rules.interval || 1);
                            // No weekdays needed - monthly uses day of month from start_date
                            break;
                            
                        case 'custom':
                            if (rules.custom_dates) {
                                $('#es-recurring-custom-dates').val(rules.custom_dates.join('\n'));
                            }
                            break;
                    }
                    
                    // Auto-preview
                    previewRecurringInstances();
                }
            }
        });
    }
    
    /**
     * Clear recurring form
     */
    function clearRecurringForm() {
        $('#es-recurring-toggle').prop('checked', false);
        $('#es-recurring-options').hide();
        $('#es-recurring-preview').hide();
        $('#es-recurring-pattern').val('weekly');
        $('input[name="recurring_weekdays[]"]').prop('checked', false);
        $('#es-recurring-custom-dates').val('');
        recurringPreviewData = null;
    }
    
    /**
     * Show message
     */
    function showMessage(message, type) {
        const $message = $('#es-message');
        $message.removeClass('success error').addClass(type);
        $message.text(message);
        $message.fadeIn();
        
        setTimeout(function() {
            $message.fadeOut();
        }, 3000);
    }
    
    // Export functions for use in main admin.js
    window.EnsembleRecurring = {
        init: initRecurring,
        getRules: getRecurringRulesForSave,
        loadRules: loadRecurringRules,
        clear: clearRecurringForm
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        initRecurring();
    });
    
})(jQuery);
