/**
 * Booking Engine Frontend JavaScript
 * 
 * Handles booking form submissions and user interactions
 * Includes Floor Plan integration
 * 
 * @package Ensemble
 * @subpackage Addons/BookingEngine
 */

(function($) {
    'use strict';
    
    /**
     * Booking Engine Frontend Controller
     */
    window.ESBookingEngine = {
        
        /**
         * Active widgets
         */
        widgets: {},
        
        /**
         * Initialize booking forms
         */
        init: function() {
            var self = this;
            
            // Initialize each booking widget
            $('.es-booking-widget').each(function() {
                var $widget = $(this);
                var eventId = $widget.data('event-id');
                
                if (eventId) {
                    self.initWidget($widget, eventId);
                }
            });
            
            // Handle form submissions
            $(document).on('submit', '.es-booking-form', this.handleFormSubmit.bind(this));
            
            // Handle guest count changes
            $(document).on('change', '.es-booking-form select[name="guests"]', this.updateGuestInfo.bind(this));
            
            // Real-time availability check (debounced)
            var availabilityTimeout;
            $(document).on('change', '.es-booking-form select[name="guests"]', function() {
                var $form = $(this).closest('.es-booking-form');
                clearTimeout(availabilityTimeout);
                availabilityTimeout = setTimeout(function() {
                    ESBookingEngine.checkAvailability($form);
                }, 300);
            });
            
            // Floor Plan integration events
            $(document).on('ensemble:floorplan:element-selected', this.handleFloorPlanSelection.bind(this));
            $(document).on('ensemble:floorplan:element-deselected', this.handleFloorPlanDeselection.bind(this));
            
            // Change selection button
            $(document).on('click', '.es-booking-change-selection', this.handleChangeSelection.bind(this));
        },
        
        /**
         * Initialize single widget
         */
        initWidget: function($widget, eventId) {
            var hasFloorPlan = $widget.data('has-floor-plan') === true || $widget.data('has-floor-plan') === 'true';
            
            this.widgets[eventId] = {
                $widget: $widget,
                $form: $widget.find('.es-booking-form'),
                hasFloorPlan: hasFloorPlan,
                selectedElement: null
            };
            
            // Load booked elements for floor plan
            if (hasFloorPlan) {
                var $bookedScript = $widget.find('.es-booking-booked-elements');
                if ($bookedScript.length) {
                    try {
                        var bookedElements = JSON.parse($bookedScript.text());
                        this.widgets[eventId].bookedElements = bookedElements;
                    } catch (e) {
                        console.error('Failed to parse booked elements:', e);
                    }
                }
            }
        },
        
        /**
         * Handle floor plan element selection
         */
        handleFloorPlanSelection: function(e, data) {
            var self = this;
            var eventId = data.eventId;
            var widget = this.widgets[eventId];
            
            if (!widget) return;
            
            var $widget = widget.$widget;
            var $form = widget.$form;
            var element = data.element;
            
            // Store selection
            widget.selectedElement = element;
            
            // Update hidden fields
            $form.find('input[name="floor_plan_id"]').val(data.floorPlanId);
            $form.find('input[name="element_id"]').val(element.id);
            $form.find('input[name="element_type"]').val(element.type);
            
            // Build element label
            var label = element.label || this.getElementTypeLabel(element.type);
            if (element.number) {
                label += ' #' + element.number;
            }
            $form.find('input[name="element_label"]').val(label);
            
            // Update selected element display
            var $selected = $widget.find('.es-booking-selected-element');
            $selected.find('.es-booking-selected-label').text(label);
            $selected.show();
            
            // Hide floor plan notice
            $widget.find('.es-booking-floor-plan-notice').hide();
            
            // Get the fields container for scroll target
            var $fieldsContainer = $widget.find('.es-booking-fields');
            
            // Show form fields
            $widget.find('.es-booking-fields, .es-booking-privacy, .es-booking-submit').slideDown(300);
            
            // Update guest selector based on element capacity
            this.updateGuestSelector($form, element);
            
            // Enable submit button
            $form.find('.es-booking-btn').prop('disabled', false);
            
            // Close floor plan modal if open
            if (typeof ESFloorPlanFrontend !== 'undefined') {
                var $modal = $('#es-booking-modal-' + data.floorPlanId);
                if ($modal.length && $modal.hasClass('es-active')) {
                    ESFloorPlanFrontend.closeBookingModal($modal);
                }
            }
            
            // Scroll to form after a delay to let slideDown complete
            setTimeout(function() {
                // Scroll to the selected element display or form fields
                var $scrollTarget = $selected.is(':visible') ? $selected : $fieldsContainer;
                if ($scrollTarget.length && $scrollTarget.offset()) {
                    var targetTop = $scrollTarget.offset().top;
                    var windowHeight = $(window).height();
                    // Only scroll if target is not already in view
                    var scrollTop = $(window).scrollTop();
                    if (targetTop < scrollTop || targetTop > scrollTop + windowHeight - 200) {
                        $('html, body').stop().animate({
                            scrollTop: Math.max(0, targetTop - 120)
                        }, 400);
                    }
                }
            }, 350);
        },
        
        /**
         * Handle floor plan element deselection
         */
        handleFloorPlanDeselection: function(e, data) {
            var eventId = data.eventId;
            var widget = this.widgets[eventId];
            
            if (!widget) return;
            
            var $widget = widget.$widget;
            var $form = widget.$form;
            
            // Clear selection
            widget.selectedElement = null;
            
            // Clear hidden fields
            $form.find('input[name="floor_plan_id"]').val('');
            $form.find('input[name="element_id"]').val('');
            $form.find('input[name="element_type"]').val('');
            $form.find('input[name="element_label"]').val('');
            
            // Hide selected element display
            $widget.find('.es-booking-selected-element').hide();
            
            // Show floor plan notice
            $widget.find('.es-booking-floor-plan-notice').show();
            
            // Hide form fields
            $widget.find('.es-booking-fields, .es-booking-privacy, .es-booking-submit').slideUp(200);
            
            // Disable submit button
            $form.find('.es-booking-btn').prop('disabled', true);
        },
        
        /**
         * Handle change selection button click
         */
        handleChangeSelection: function(e) {
            e.preventDefault();
            
            var $widget = $(e.target).closest('.es-booking-widget');
            var eventId = $widget.data('event-id');
            
            // Trigger deselection
            $(document).trigger('ensemble:floorplan:element-deselected', {
                eventId: eventId
            });
            
            // Scroll to floor plan
            var $floorPlan = $widget.find('.es-booking-floor-plan-container');
            if ($floorPlan.length) {
                $('html, body').animate({
                    scrollTop: $floorPlan.offset().top - 50
                }, 300);
            }
        },
        
        /**
         * Update guest selector based on element capacity
         */
        updateGuestSelector: function($form, element) {
            var $select = $form.find('select[name="guests"]');
            var maxCapacity = element.seats || element.capacity || 10;
            
            // Get available capacity (subtract already booked)
            var available = maxCapacity;
            
            // Rebuild options
            $select.empty();
            for (var i = 1; i <= available; i++) {
                var label = i + ' ' + (i === 1 ? ensembleBookingFrontend.strings.person : ensembleBookingFrontend.strings.people);
                $select.append('<option value="' + i + '">' + label + '</option>');
            }
        },
        
        /**
         * Get element type label
         */
        getElementTypeLabel: function(type) {
            var labels = {
                'table': ensembleBookingFrontend.strings.table || 'Table',
                'seat': ensembleBookingFrontend.strings.seat || 'Seat',
                'mat': ensembleBookingFrontend.strings.mat || 'Mat',
                'standing': ensembleBookingFrontend.strings.standing || 'Standing',
                'booth': ensembleBookingFrontend.strings.booth || 'Booth',
                'area': ensembleBookingFrontend.strings.area || 'Area',
                'lounge': ensembleBookingFrontend.strings.lounge || 'Lounge'
            };
            return labels[type] || type;
        },
        
        /**
         * Handle form submission
         * 
         * @param {Event} e
         */
        handleFormSubmit: function(e) {
            e.preventDefault();
            
            var $form = $(e.target);
            var $widget = $form.closest('.es-booking-widget');
            var $btn = $form.find('.es-booking-btn');
            var $messages = $form.find('.es-booking-messages');
            var $success = $form.find('.es-booking-success');
            var $error = $form.find('.es-booking-error');
            var isWaitlist = $form.find('input[name="waitlist"]').val() === '1';
            var eventId = $widget.data('event-id');
            var widget = this.widgets[eventId];
            
            // Validate floor plan selection if required
            if (widget && widget.hasFloorPlan && !widget.selectedElement) {
                this.showError($form, ensembleBookingFrontend.strings.selectSpot || 'Please select a spot from the floor plan');
                return;
            }
            
            // Validate
            if (!this.validateForm($form)) {
                return;
            }
            
            // Show loading state
            $form.addClass('is-loading');
            $btn.prop('disabled', true);
            $messages.hide();
            $success.hide();
            $error.hide();
            
            // Prepare data
            var formData = $form.serializeArray();
            formData.push({
                name: 'action',
                value: isWaitlist ? 'es_join_waitlist' : 'es_submit_booking'
            });
            formData.push({
                name: 'nonce',
                value: $form.find('input[name="booking_nonce"]').val()
            });
            
            // Submit
            $.ajax({
                url: ensembleBookingFrontend.ajaxUrl,
                type: 'POST',
                data: $.param(formData),
                success: function(response) {
                    $form.removeClass('is-loading');
                    $btn.prop('disabled', false);
                    
                    if (response.success) {
                        // Show success message
                        $messages.show();
                        $success.show();
                        
                        // Show confirmation code if available
                        if (response.data.confirmation_code) {
                            $success.find('.es-confirmation-code').text(
                                ensembleBookingFrontend.strings.confirmationCode + ': ' + response.data.confirmation_code
                            );
                        }
                        
                        // Hide form fields
                        $form.find('.es-booking-fields, .es-booking-privacy, .es-booking-submit').fadeOut();
                        $widget.find('.es-booking-floor-plan-section').fadeOut();
                        $widget.find('.es-booking-selected-element').fadeOut();
                        
                        // Scroll to success message - but only if not already visible
                        setTimeout(function() {
                            if ($success.length && $success.offset()) {
                                var successTop = $success.offset().top;
                                var scrollTop = $(window).scrollTop();
                                var windowHeight = $(window).height();
                                
                                // Only scroll if success message is not in view
                                if (successTop < scrollTop || successTop > scrollTop + windowHeight - 100) {
                                    $('html, body').stop().animate({
                                        scrollTop: Math.max(0, successTop - 150)
                                    }, 400);
                                }
                            }
                        }, 100);
                        
                        // Refresh floor plan status
                        if (widget && widget.hasFloorPlan && typeof ESFloorPlanFrontend !== 'undefined') {
                            var floorPlanId = $form.find('input[name="floor_plan_id"]').val();
                            if (floorPlanId) {
                                ESFloorPlanFrontend.refreshStatus(parseInt(floorPlanId));
                            }
                        }
                        
                        // Trigger event for other scripts
                        $(document).trigger('ensemble:booking:success', [response.data, $form]);
                        
                    } else {
                        // Show error
                        ESBookingEngine.showError($form, response.data.message || ensembleBookingFrontend.strings.error);
                    }
                },
                error: function(xhr, status, error) {
                    $form.removeClass('is-loading');
                    $btn.prop('disabled', false);
                    
                    ESBookingEngine.showError($form, ensembleBookingFrontend.strings.error);
                    console.error('Booking error:', error);
                }
            });
        },
        
        /**
         * Show error message
         */
        showError: function($form, message) {
            var $messages = $form.find('.es-booking-messages');
            var $error = $form.find('.es-booking-error');
            
            $messages.show();
            $error.show();
            $error.find('.es-error-message').text(message);
            
            // Scroll to error - only if not in view
            setTimeout(function() {
                if ($error.length && $error.offset()) {
                    var errorTop = $error.offset().top;
                    var scrollTop = $(window).scrollTop();
                    var windowHeight = $(window).height();
                    
                    if (errorTop < scrollTop || errorTop > scrollTop + windowHeight - 100) {
                        $('html, body').stop().animate({
                            scrollTop: Math.max(0, errorTop - 150)
                        }, 400);
                    }
                }
            }, 50);
        },
        
        /**
         * Validate form
         * 
         * @param {jQuery} $form
         * @return {boolean}
         */
        validateForm: function($form) {
            var isValid = true;
            
            // Check required fields
            $form.find('[required]:visible').each(function() {
                var $field = $(this);
                var value = $field.val();
                
                if ($field.is(':checkbox')) {
                    if (!$field.is(':checked')) {
                        isValid = false;
                        ESBookingEngine.highlightError($field.closest('.es-checkbox-label'));
                    }
                } else if (!value || value.trim() === '') {
                    isValid = false;
                    ESBookingEngine.highlightError($field);
                }
            });
            
            // Validate email
            var $email = $form.find('input[name="customer_email"]:visible');
            if ($email.length && $email.val()) {
                var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test($email.val())) {
                    isValid = false;
                    this.highlightError($email);
                }
            }
            
            return isValid;
        },
        
        /**
         * Highlight field error
         * 
         * @param {jQuery} $field
         */
        highlightError: function($field) {
            $field.css('border-color', 'var(--es-error, #ef4444)');
            
            setTimeout(function() {
                $field.css('border-color', '');
            }, 2000);
            
            // Scroll to first error - only if not in view
            if (!this.highlightError.scrolled && $field.length && $field.offset()) {
                var fieldTop = $field.offset().top;
                var scrollTop = $(window).scrollTop();
                var windowHeight = $(window).height();
                
                if (fieldTop < scrollTop + 50 || fieldTop > scrollTop + windowHeight - 100) {
                    $('html, body').stop().animate({
                        scrollTop: Math.max(0, fieldTop - 150)
                    }, 400);
                }
                
                this.highlightError.scrolled = true;
                
                var self = this;
                setTimeout(function() {
                    self.highlightError.scrolled = false;
                }, 500);
            }
        },
        
        /**
         * Update guest info display
         */
        updateGuestInfo: function(e) {
            var $form = $(e.target).closest('.es-booking-form');
            var guests = parseInt($(e.target).val()) || 1;
            
            // Could show additional info about group booking
            // Or dynamically show guest name fields
        },
        
        /**
         * Check availability in real-time
         * 
         * @param {jQuery} $form
         */
        checkAvailability: function($form) {
            var eventId = $form.find('input[name="event_id"]').val();
            var bookingType = $form.find('input[name="booking_type"]').val();
            var quantity = $form.find('select[name="guests"]').val() || 1;
            var elementId = $form.find('input[name="element_id"]').val() || null;
            
            $.ajax({
                url: ensembleBookingFrontend.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'es_check_booking_availability',
                    nonce: $form.find('input[name="booking_nonce"]').val(),
                    event_id: eventId,
                    booking_type: bookingType,
                    quantity: quantity,
                    element_id: elementId
                },
                success: function(response) {
                    if (response.success) {
                        ESBookingEngine.updateAvailabilityDisplay($form, response.data);
                    }
                }
            });
        },
        
        /**
         * Update availability display
         * 
         * @param {jQuery} $form
         * @param {object} data
         */
        updateAvailabilityDisplay: function($form, data) {
            var $widget = $form.closest('.es-booking-widget');
            var $availability = $widget.find('.es-booking-availability');
            var $spots = $availability.find('.es-spots-remaining');
            var $btn = $form.find('.es-booking-btn');
            
            if (data.remaining !== null) {
                if (data.remaining > 0) {
                    $spots.text(data.remaining + ' ' + ensembleBookingFrontend.strings.spotsRemaining);
                    $availability.show();
                    $btn.prop('disabled', false);
                } else {
                    $spots.text(ensembleBookingFrontend.strings.fullyBooked);
                    $spots.css({
                        'background': 'rgba(239, 68, 68, 0.1)',
                        'color': 'var(--es-error, #ef4444)'
                    });
                    $btn.prop('disabled', true);
                }
            }
        },
        
        /**
         * Public API: Open booking form with floor plan element
         */
        openWithElement: function(floorPlanId, elementId, seats, eventId) {
            var widget = this.widgets[eventId];
            if (!widget) return;
            
            // Find element data from floor plan
            if (typeof ESFloorPlanFrontend !== 'undefined') {
                var instance = ESFloorPlanFrontend.getInstance(floorPlanId);
                if (instance && instance.data.elements) {
                    var element = instance.data.elements.find(function(el) {
                        return el.id === elementId;
                    });
                    
                    if (element) {
                        element.preselectedSeats = seats;
                        $(document).trigger('ensemble:floorplan:element-selected', {
                            eventId: eventId,
                            floorPlanId: floorPlanId,
                            element: element
                        });
                    }
                }
            }
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        ESBookingEngine.init();
    });
    
    // Also initialize for dynamically loaded content
    $(document).on('ensemble:content:loaded', function() {
        ESBookingEngine.init();
    });
    
})(jQuery);
