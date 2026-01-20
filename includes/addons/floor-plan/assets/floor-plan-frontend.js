/**
 * Floor Plan Pro - Frontend Display
 *
 * Renders floor plans on frontend with booking functionality
 * Includes Booking Engine integration via jQuery events
 *
 * @package Ensemble
 * @subpackage Addons/FloorPlan
 */

(function($) {
    'use strict';

    /**
     * Floor Plan Frontend Renderer
     */
    window.ESFloorPlanFrontend = {

        /**
         * Active floor plans
         */
        instances: {},

        /**
         * Tooltip element
         */
        tooltip: null,
        
        /**
         * Currently selected element per instance
         */
        selectedElements: {},

        /**
         * Initialize all floor plans on page
         */
        init: function() {
            var self = this;

            // Create tooltip element
            this.createTooltip();

            // Initialize each floor plan
            $('.es-floor-plan-wrapper').each(function() {
                var $wrapper = $(this);
                var floorPlanId = $wrapper.data('floor-plan-id');
                
                if (floorPlanId) {
                    self.initFloorPlan($wrapper, floorPlanId);
                }
            });

            // Global event handlers
            this.bindGlobalEvents();
        },

        /**
         * Create tooltip element
         */
        createTooltip: function() {
            if ($('.es-fp-tooltip').length === 0) {
                $('body').append('<div class="es-fp-tooltip"></div>');
            }
            this.tooltip = $('.es-fp-tooltip');
        },

        /**
         * Initialize single floor plan
         */
        initFloorPlan: function($wrapper, floorPlanId) {
            var self = this;
            var $dataScript = $wrapper.find('.es-floor-plan-data');
            
            if (!$dataScript.length) {
                console.error('Floor plan data not found for ID:', floorPlanId);
                return;
            }

            var data;
            try {
                data = JSON.parse($dataScript.text());
            } catch (e) {
                console.error('Failed to parse floor plan data:', e);
                return;
            }

            var containerId = 'es-floor-plan-' + floorPlanId;
            var $container = $('#' + containerId);

            if (!$container.length) {
                console.error('Floor plan container not found:', containerId);
                return;
            }
            
            // Check if embedded in booking widget
            var isEmbedded = $wrapper.closest('.es-booking-floor-plan-container').length > 0;
            
            // Get mode from data (default to reservation for backward compatibility)
            var mode = data.mode || (data.bookable ? 'reservation' : 'display');

            // Create instance
            var instance = {
                id: floorPlanId,
                data: data,
                $wrapper: $wrapper,
                $container: $container,
                stage: null,
                layer: null,
                elementStatus: {},
                bookable: data.bookable,
                isEmbedded: isEmbedded,
                mode: mode,
                ticketCategories: data.ticketCategories || {}
            };

            // Render canvas
            this.renderCanvas(instance);

            // Load booking status if bookable
            if (data.bookable && data.eventId) {
                this.loadBookingStatus(instance);
            }

            // Store instance
            this.instances[floorPlanId] = instance;

            // Hide loading
            $container.find('.es-floor-plan-loading').fadeOut();
        },

        /**
         * Render Konva canvas
         */
        renderCanvas: function(instance) {
            var self = this;
            var data = instance.data;
            var canvas = data.canvas;
            var $container = instance.$container;
            var $wrapper = instance.$wrapper.find('.es-floor-plan-canvas-wrap');

            // Calculate dimensions with max-height limit
            var containerWidth = $container.width();
            var scale = containerWidth / canvas.width;
            var containerHeight = canvas.height * scale;
            
            // Apply max-height limit (default 600px, or from wrapper style)
            var maxHeight = 600;
            var wrapperMaxHeight = $wrapper.css('max-height');
            if (wrapperMaxHeight && wrapperMaxHeight !== 'none') {
                maxHeight = parseInt(wrapperMaxHeight, 10) || 600;
            }
            
            if (containerHeight > maxHeight) {
                containerHeight = maxHeight;
                scale = containerHeight / canvas.height;
                containerWidth = canvas.width * scale;
            }

            // Create stage
            instance.stage = new Konva.Stage({
                container: 'es-floor-plan-' + instance.id,
                width: containerWidth,
                height: containerHeight,
                scaleX: scale,
                scaleY: scale
            });
            
            // Prevent scroll jump on click - comprehensive fix
            var stageContainer = instance.stage.container();
            stageContainer.tabIndex = -1;
            stageContainer.style.outline = 'none';
            stageContainer.style.userSelect = 'none';
            
            // Prevent any default behavior that could cause scroll
            $(stageContainer).on('click mousedown touchstart', function(e) {
                e.preventDefault();
                // Don't let any click event bubble up and cause focus/scroll
                e.stopPropagation();
            });
            
            // Also prevent focus events
            $(stageContainer).on('focus focusin', function(e) {
                e.preventDefault();
                this.blur();
            });

            // Background layer
            var bgLayer = new Konva.Layer();
            instance.stage.add(bgLayer);

            // Load background image if present
            if (canvas.background) {
                var imageObj = new Image();
                imageObj.onload = function() {
                    var bgImage = new Konva.Image({
                        x: 0,
                        y: 0,
                        image: imageObj,
                        width: canvas.width,
                        height: canvas.height,
                        opacity: 0.3
                    });
                    bgLayer.add(bgImage);
                    bgLayer.draw();
                };
                imageObj.src = canvas.background;
            }

            // Main layer
            instance.layer = new Konva.Layer();
            instance.stage.add(instance.layer);
            
            // Prevent click on empty stage area from causing scroll
            instance.stage.on('click tap', function(e) {
                if (e.evt) {
                    e.evt.preventDefault();
                }
            });

            // Render elements
            data.elements.forEach(function(element) {
                self.renderElement(instance, element);
            });

            instance.layer.draw();

            // Handle window resize
            $(window).on('resize', function() {
                self.handleResize(instance);
            });
        },

        /**
         * Render single element
         */
        renderElement: function(instance, element) {
            var self = this;
            var data = instance.data;

            // Get color
            var color = this.getElementColor(element.type);
            
            // Get section color if assigned
            if (element.section_id && data.sections) {
                var section = data.sections.find(function(s) { 
                    return s.id === element.section_id; 
                });
                if (section) {
                    color = section.color;
                }
            }

            // Create group
            var group = new Konva.Group({
                x: element.x,
                y: element.y,
                rotation: element.rotation || 0,
                id: element.id,
                name: 'element'
            });

            // Shape
            var shape;
            if (element.shape === 'round') {
                shape = new Konva.Circle({
                    x: element.width / 2,
                    y: element.height / 2,
                    radius: Math.min(element.width, element.height) / 2,
                    fill: color,
                    opacity: 0.8,
                    stroke: color,
                    strokeWidth: 2
                });
            } else {
                shape = new Konva.Rect({
                    x: 0,
                    y: 0,
                    width: element.width,
                    height: element.height,
                    fill: color,
                    opacity: 0.8,
                    stroke: color,
                    strokeWidth: 2,
                    cornerRadius: element.type === 'stage' ? 0 : 4
                });
            }

            group.add(shape);

            // Label
            var labelText = element.label || (element.number ? element.number.toString() : '');
            if (labelText) {
                var label = new Konva.Text({
                    x: 0,
                    y: element.height / 2 - 8,
                    width: element.width,
                    text: labelText,
                    fontSize: 14,
                    fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
                    fontStyle: 'bold',
                    fill: '#fff',
                    align: 'center'
                });
                group.add(label);
            }

            // Status indicator (for bookable elements)
            if (element.bookable) {
                var statusDot = new Konva.Circle({
                    x: element.width - 10,
                    y: 10,
                    radius: 8,
                    fill: '#10B981', // Default: available
                    stroke: '#fff',
                    strokeWidth: 2,
                    name: 'status-dot'
                });
                group.add(statusDot);
            }

            // Store element data in group
            group.setAttr('elementData', element);

            // Events for bookable elements
            if (element.bookable && instance.bookable) {
                group.on('mouseenter', function() {
                    document.body.style.cursor = 'pointer';
                    shape.opacity(1);
                    instance.layer.draw();
                    
                    // Show tooltip
                    self.showTooltip(instance, element, group);
                });

                group.on('mouseleave', function() {
                    document.body.style.cursor = 'default';
                    shape.opacity(0.8);
                    instance.layer.draw();
                    
                    // Hide tooltip
                    self.hideTooltip();
                });

                group.on('click tap', function(e) {
                    // Store current scroll position BEFORE any processing
                    var savedScrollY = window.scrollY || window.pageYOffset;
                    var savedScrollX = window.scrollX || window.pageXOffset;
                    
                    // Prevent default browser behavior and stop propagation
                    if (e.evt) {
                        e.evt.preventDefault();
                        e.evt.stopPropagation();
                    }
                    // Cancel any default stage behavior
                    e.cancelBubble = true;
                    
                    self.handleElementClick(instance, element, group, shape);
                    
                    // Restore scroll position if it changed unexpectedly (within 20ms)
                    // This catches browser-induced scrolls but happens before our intended scroll
                    setTimeout(function() {
                        var currentScrollY = window.scrollY || window.pageYOffset;
                        // If scroll jumped more than 100px unexpectedly, restore it
                        if (Math.abs(currentScrollY - savedScrollY) > 100) {
                            window.scrollTo(savedScrollX, savedScrollY);
                        }
                    }, 20);
                    
                    return false;
                });
            } else if (!element.bookable) {
                // Non-bookable elements show tooltip on hover
                group.on('mouseenter', function() {
                    self.showTooltip(instance, element, group);
                });

                group.on('mouseleave', function() {
                    self.hideTooltip();
                });
            }

            instance.layer.add(group);
        },
        
        /**
         * Handle element click
         */
        handleElementClick: function(instance, element, group, shape) {
            var self = this;
            
            // Check if sold out
            var status = instance.elementStatus[element.id];
            if (status && status.status === 'sold_out') {
                return; // Don't allow selection of sold out elements
            }
            
            // Handle based on mode
            if (instance.mode === 'ticket') {
                // Ticket mode - trigger event for ticket form to handle
                this.handleTicketSelection(instance, element, group, shape);
            } else if (instance.isEmbedded) {
                // Embedded in booking widget - use direct selection
                this.selectElement(instance, element, group, shape);
            } else {
                // Standard reservation modal behavior
                this.openBookingModal(instance, element);
            }
        },
        
        /**
         * Handle ticket selection - triggers event for ticket form
         */
        handleTicketSelection: function(instance, element, group, shape) {
            var self = this;
            var data = instance.data;
            
            // Get ticket category for this element
            var ticketCat = instance.ticketCategories[element.id];
            
            if (!ticketCat) {
                console.warn('No ticket category linked to element:', element.id);
                return;
            }
            
            // Visual feedback - highlight selected element
            this.selectElement(instance, element, group, shape);
            
            // Trigger event for ticket form to handle
            $(document).trigger('ensemble:floorplan:ticket-selected', {
                eventId: data.eventId,
                floorPlanId: instance.id,
                element: element,
                ticketCategory: ticketCat,
                section: this.getElementSection(instance, element)
            });
            
            // Scroll to ticket form if exists
            var $ticketForm = $('.es-tickets-container[data-event-id="' + data.eventId + '"]');
            if (!$ticketForm.length) {
                // Also try generic ticket form on page
                $ticketForm = $('.es-tickets-container').first();
            }
            
            if ($ticketForm.length) {
                // Find the ticket item matching this category
                var $ticketItem = $ticketForm.find('.es-ticket-item[data-category-id="' + ticketCat.id + '"]');
                
                if ($ticketItem.length) {
                    // Highlight and scroll to ticket
                    $ticketItem.addClass('es-highlight-pulse');
                    setTimeout(function() {
                        $ticketItem.removeClass('es-highlight-pulse');
                    }, 2000);
                    
                    // Set quantity to 1 if currently 0
                    var $qtyInput = $ticketItem.find('.es-ticket-qty-input');
                    if ($qtyInput.length && parseInt($qtyInput.val()) === 0) {
                        $qtyInput.val(1).trigger('change');
                    }
                    
                    // Smooth scroll to ticket form
                    $('html, body').animate({
                        scrollTop: $ticketForm.offset().top - 100
                    }, 500);
                }
            }
        },
        
        /**
         * Select element (for embedded mode)
         */
        selectElement: function(instance, element, group, shape) {
            var self = this;
            var data = instance.data;
            var previousSelection = this.selectedElements[instance.id];
            
            // Deselect previous element
            if (previousSelection) {
                var prevGroup = instance.layer.findOne('#' + previousSelection.id);
                if (prevGroup) {
                    var prevShape = prevGroup.findOne('Rect') || prevGroup.findOne('Circle');
                    if (prevShape) {
                        prevShape.strokeWidth(2);
                        prevShape.stroke(this.getElementColor(previousSelection.type));
                    }
                }
            }
            
            // Select new element
            this.selectedElements[instance.id] = element;
            
            // Visual feedback - highlight selected
            shape.strokeWidth(4);
            shape.stroke('#10B981'); // Success green
            instance.layer.draw();
            
            // Trigger event for booking engine (which handles scrolling)
            $(document).trigger('ensemble:floorplan:element-selected', {
                eventId: data.eventId,
                floorPlanId: instance.id,
                element: element,
                section: this.getElementSection(instance, element)
            });
        },
        
        /**
         * Deselect element
         */
        deselectElement: function(instance) {
            var self = this;
            var previousSelection = this.selectedElements[instance.id];
            
            if (previousSelection) {
                var prevGroup = instance.layer.findOne('#' + previousSelection.id);
                if (prevGroup) {
                    var prevShape = prevGroup.findOne('Rect') || prevGroup.findOne('Circle');
                    if (prevShape) {
                        prevShape.strokeWidth(2);
                        prevShape.stroke(this.getElementColor(previousSelection.type));
                    }
                }
                
                delete this.selectedElements[instance.id];
                instance.layer.draw();
            }
        },
        
        /**
         * Get element's section
         */
        getElementSection: function(instance, element) {
            if (!element.section_id || !instance.data.sections) {
                return null;
            }
            return instance.data.sections.find(function(s) { 
                return s.id === element.section_id; 
            });
        },

        /**
         * Get element color by type
         */
        getElementColor: function(type) {
            var colors = {
                table: '#3B82F6',
                section: '#8B5CF6',
                stage: '#EF4444',
                bar: '#F59E0B',
                entrance: '#10B981',
                lounge: '#EC4899',
                dancefloor: '#06B6D4',
                amenity: '#6B7280',
                custom: '#374151'
            };
            return colors[type] || '#64748b';
        },

        /**
         * Show tooltip
         */
        showTooltip: function(instance, element, group) {
            var self = this;
            var data = instance.data;
            var text = element.label || this.getTypeLabel(element.type);
            
            if (element.number) {
                text = text + ' #' + element.number;
            }

            if (element.bookable) {
                var capacity = element.seats || element.capacity || 0;
                if (capacity > 0) {
                    text += '\n' + capacity + ' ' + (typeof esFloorPlanFrontend !== 'undefined' && esFloorPlanFrontend.strings ? esFloorPlanFrontend.strings.seats : 'Seats');
                }

                // Add availability status
                var status = instance.elementStatus[element.id];
                if (status) {
                    if (status.status === 'sold_out') {
                        text += '\n' + (typeof esFloorPlanFrontend !== 'undefined' && esFloorPlanFrontend.strings ? esFloorPlanFrontend.strings.soldOut : 'Sold Out');
                    } else if (status.status === 'partial') {
                        text += '\n' + status.available + ' ' + (typeof esFloorPlanFrontend !== 'undefined' && esFloorPlanFrontend.strings ? esFloorPlanFrontend.strings.available : 'Available');
                    } else {
                        text += '\n' + (typeof esFloorPlanFrontend !== 'undefined' && esFloorPlanFrontend.strings ? esFloorPlanFrontend.strings.available : 'Available');
                    }
                }
            }

            // Get position using getBoundingClientRect (works with position: fixed)
            var stage = instance.stage;
            var scale = stage.scaleX();
            var stageBox = stage.container().getBoundingClientRect();
            var groupPos = group.getAbsolutePosition();

            // For position: fixed, use viewport coordinates directly (no scroll offset needed)
            var x = stageBox.left + (groupPos.x + element.width / 2) * scale;
            var y = stageBox.top + groupPos.y * scale - 10;

            // Make sure tooltip exists
            if (!this.tooltip || !this.tooltip.length) {
                this.createTooltip();
            }

            this.tooltip
                .html(text.replace(/\n/g, '<br>'))
                .css({
                    left: x + 'px',
                    top: y + 'px',
                    transform: 'translate(-50%, -100%)'
                })
                .addClass('es-visible');
        },

        /**
         * Hide tooltip
         */
        hideTooltip: function() {
            this.tooltip.removeClass('es-visible');
        },

        /**
         * Get type label
         */
        getTypeLabel: function(type) {
            var labels = {
                table: 'Table',
                section: 'Section',
                stage: 'Stage',
                bar: 'Bar',
                entrance: 'Entrance',
                lounge: 'Lounge',
                dancefloor: 'Dancefloor',
                amenity: 'Amenity',
                custom: 'Area'
            };
            return labels[type] || type;
        },

        /**
         * Load booking status from server
         */
        loadBookingStatus: function(instance) {
            var self = this;
            var data = instance.data;

            $.ajax({
                url: esFloorPlanFrontend.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'es_get_floor_plan_status',
                    nonce: esFloorPlanFrontend.nonce,
                    floor_plan_id: instance.id,
                    event_id: data.eventId
                },
                success: function(response) {
                    if (response.success && response.data.element_status) {
                        instance.elementStatus = response.data.element_status;
                        self.updateElementStatusDisplay(instance);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Failed to load floor plan status:', error);
                }
            });
        },

        /**
         * Update element status display
         */
        updateElementStatusDisplay: function(instance) {
            var self = this;

            Object.keys(instance.elementStatus).forEach(function(elementId) {
                var status = instance.elementStatus[elementId];
                var group = instance.layer.findOne('#' + elementId);
                
                if (!group) return;

                var statusDot = group.findOne('.status-dot');
                if (!statusDot) return;

                // Update color based on status
                if (status.status === 'sold_out') {
                    statusDot.fill('#EF4444'); // Red
                    
                    // Also dim the element
                    var shape = group.findOne('Rect') || group.findOne('Circle');
                    if (shape) {
                        shape.opacity(0.4);
                    }
                } else if (status.status === 'partial') {
                    statusDot.fill('#F59E0B'); // Orange/Yellow
                } else {
                    statusDot.fill('#10B981'); // Green
                }
            });

            instance.layer.draw();
        },

        /**
         * Open booking modal
         */
        openBookingModal: function(instance, element) {
            var self = this;
            var data = instance.data;
            var $modal = $('#es-booking-modal-' + instance.id);

            if (!$modal.length) return;

            // Check if sold out
            var status = instance.elementStatus[element.id];
            if (status && status.status === 'sold_out') {
                return; // Don't open modal for sold out elements
            }

            // Get section info
            var section = this.getElementSection(instance, element);

            // Populate modal
            var name = element.label || this.getTypeLabel(element.type);
            if (element.number) {
                name += ' #' + element.number;
            }

            $modal.find('.es-booking-element-name').text(name);

            // Section badge
            if (section) {
                $modal.find('.es-booking-section-badge')
                    .text(section.name)
                    .css('background-color', section.color)
                    .show();
            } else {
                $modal.find('.es-booking-section-badge').hide();
            }

            // Capacity
            var capacity = element.seats || element.capacity || 0;
            var available = capacity;
            if (status) {
                available = status.available;
            }

            $modal.find('.es-booking-capacity-text').text(capacity + ' ' + esFloorPlanFrontend.strings.seats);
            $modal.find('.es-booking-available-text').text(available + ' ' + esFloorPlanFrontend.strings.available);

            // Price
            var price = element.price || (section ? section.default_price : 0);
            if (price > 0) {
                $modal.find('.es-booking-price-text').text(this.formatPrice(price) + ' ' + esFloorPlanFrontend.strings.perPerson);
                $modal.find('.es-booking-price').show();
            } else {
                $modal.find('.es-booking-price').hide();
            }

            // Description
            if (element.description) {
                $modal.find('.es-booking-description').text(element.description).show();
            } else {
                $modal.find('.es-booking-description').hide();
            }

            // Seats selector
            var $seats = $modal.find('.es-booking-seats');
            $seats.empty();
            for (var i = 1; i <= available; i++) {
                $seats.append('<option value="' + i + '">' + i + '</option>');
            }

            // Store element data
            $modal.data('element', element);
            $modal.data('instance', instance);

            // Show modal
            $modal.addClass('es-active');
        },

        /**
         * Close booking modal
         */
        closeBookingModal: function($modal) {
            $modal.removeClass('es-active');
        },

        /**
         * Format price
         */
        formatPrice: function(price) {
            // Use locale formatting if available
            if (typeof esFloorPlanFrontend !== 'undefined' && esFloorPlanFrontend.currencySymbol) {
                return esFloorPlanFrontend.currencySymbol + parseFloat(price).toFixed(2);
            }
            return 'â‚¬' + parseFloat(price).toFixed(2);
        },

        /**
         * Handle window resize
         */
        handleResize: function(instance) {
            var $container = instance.$container;
            var $wrapper = instance.$wrapper.find('.es-floor-plan-canvas-wrap');
            var canvas = instance.data.canvas;

            var containerWidth = $container.width();
            var scale = containerWidth / canvas.width;
            var containerHeight = canvas.height * scale;
            
            // Apply max-height limit
            var maxHeight = 600;
            var wrapperMaxHeight = $wrapper.css('max-height');
            if (wrapperMaxHeight && wrapperMaxHeight !== 'none') {
                maxHeight = parseInt(wrapperMaxHeight, 10) || 600;
            }
            
            if (containerHeight > maxHeight) {
                containerHeight = maxHeight;
                scale = containerHeight / canvas.height;
                containerWidth = canvas.width * scale;
            }

            instance.stage.width(containerWidth);
            instance.stage.height(containerHeight);
            instance.stage.scaleX(scale);
            instance.stage.scaleY(scale);
            instance.stage.draw();
        },

        /**
         * Bind global events
         */
        bindGlobalEvents: function() {
            var self = this;

            // Close modal on overlay click
            $(document).on('click', '.es-floor-plan-booking-modal', function(e) {
                if ($(e.target).hasClass('es-floor-plan-booking-modal')) {
                    self.closeBookingModal($(this));
                }
            });

            // Close modal on X click
            $(document).on('click', '.es-booking-modal-close', function() {
                self.closeBookingModal($(this).closest('.es-floor-plan-booking-modal'));
            });

            // Close modal on ESC
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    $('.es-floor-plan-booking-modal.es-active').each(function() {
                        self.closeBookingModal($(this));
                    });
                }
            });

            // Reserve button click
            $(document).on('click', '.es-booking-reserve-btn', function(e) {
                e.preventDefault();
                var $modal = $(this).closest('.es-floor-plan-booking-modal');
                var element = $modal.data('element');
                var instance = $modal.data('instance');
                var seats = $modal.find('.es-booking-seats').val();

                self.handleReservation(instance, element, seats);
            });

            // Tickets button click
            $(document).on('click', '.es-booking-tickets-btn', function(e) {
                e.preventDefault();
                var $modal = $(this).closest('.es-floor-plan-booking-modal');
                var element = $modal.data('element');
                var instance = $modal.data('instance');
                var seats = $modal.find('.es-booking-seats').val();

                self.handleTicketPurchase(instance, element, seats);
            });
            
            // Listen for deselection events from booking engine
            $(document).on('ensemble:floorplan:element-deselected', function(e, data) {
                var instance = self.instances[Object.keys(self.instances).find(function(id) {
                    return self.instances[id].data.eventId == data.eventId;
                })];
                
                if (instance) {
                    self.deselectElement(instance);
                }
            });
        },

        /**
         * Handle reservation
         */
        handleReservation: function(instance, element, seats) {
            var data = instance.data;

            // Trigger event for booking engine integration
            $(document).trigger('ensemble:floorplan:element-selected', {
                eventId: data.eventId,
                floorPlanId: instance.id,
                element: element,
                seats: seats,
                section: this.getElementSection(instance, element)
            });
            
            // Close modal
            this.closeBookingModal($('#es-booking-modal-' + instance.id));

            // If ESBookingEngine is not available, use fallback
            if (typeof ESBookingEngine === 'undefined') {
                // Build reservation URL with parameters
                var params = new URLSearchParams({
                    floor_plan_id: instance.id,
                    element_id: element.id,
                    seats: seats,
                    event_id: data.eventId
                });

                // Redirect to reservation page
                var eventUrl = window.location.href.split('?')[0];
                window.location.href = eventUrl + '?' + params.toString() + '#reservation-form';
            }
        },

        /**
         * Handle ticket purchase
         */
        handleTicketPurchase: function(instance, element, seats) {
            var data = instance.data;

            // Trigger event for booking engine integration
            $(document).trigger('ensemble:floorplan:element-selected', {
                eventId: data.eventId,
                floorPlanId: instance.id,
                element: element,
                seats: seats,
                section: this.getElementSection(instance, element),
                mode: 'ticket'
            });
            
            // Close modal
            this.closeBookingModal($('#es-booking-modal-' + instance.id));

            // If ESBookingEngine is not available, use fallback
            if (typeof ESBookingEngine === 'undefined') {
                // Build ticket URL with parameters
                var params = new URLSearchParams({
                    floor_plan_id: instance.id,
                    element_id: element.id,
                    quantity: seats,
                    event_id: data.eventId
                });

                // Redirect to tickets page
                var eventUrl = window.location.href.split('?')[0];
                window.location.href = eventUrl + '?' + params.toString() + '#tickets';
            }
        },

        /**
         * Public API: Refresh floor plan status
         */
        refreshStatus: function(floorPlanId) {
            var instance = this.instances[floorPlanId];
            if (instance && instance.bookable && instance.data.eventId) {
                this.loadBookingStatus(instance);
            }
        },

        /**
         * Public API: Get instance
         */
        getInstance: function(floorPlanId) {
            return this.instances[floorPlanId] || null;
        },
        
        /**
         * Public API: Get selected element for instance
         */
        getSelectedElement: function(floorPlanId) {
            return this.selectedElements[floorPlanId] || null;
        },
        
        /**
         * Public API: Select element programmatically
         */
        selectElementById: function(floorPlanId, elementId) {
            var instance = this.instances[floorPlanId];
            if (!instance) return false;
            
            var element = instance.data.elements.find(function(el) {
                return el.id === elementId;
            });
            
            if (!element || !element.bookable) return false;
            
            var group = instance.layer.findOne('#' + elementId);
            if (!group) return false;
            
            var shape = group.findOne('Rect') || group.findOne('Circle');
            this.selectElement(instance, element, group, shape);
            
            return true;
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        if ($('.es-floor-plan-wrapper').length && typeof Konva !== 'undefined') {
            ESFloorPlanFrontend.init();
        }
    });

})(jQuery);
