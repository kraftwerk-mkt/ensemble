/**
 * Ensemble Tickets Add-on - Admin Scripts
 * 
 * @package Ensemble
 * @subpackage Addons/Tickets
 */

(function($) {
    'use strict';

    /**
     * Tickets Admin Handler
     */
    const EnsembleTicketsAdmin = {
        
        /**
         * Current tickets data
         */
        tickets: [],
        
        /**
         * Current editing ticket
         */
        currentTicket: null,
        
        /**
         * Post ID
         */
        postId: 0,
        
        /**
         * Initialize
         */
        init: function() {
            this.postId = $('#post_ID').val() || 0;
            this.loadTickets();
            this.bindEvents();
            this.initSortable();
        },
        
        /**
         * Load tickets from hidden input
         */
        loadTickets: function() {
            const $input = $('#ensemble_tickets_data');
            if ($input.length && $input.val()) {
                try {
                    this.tickets = JSON.parse($input.val());
                } catch (e) {
                    this.tickets = [];
                }
            }
        },
        
        /**
         * Save tickets to hidden input
         */
        saveTickets: function() {
            $('#ensemble_tickets_data').val(JSON.stringify(this.tickets));
        },
        
        /**
         * Bind events
         */
        bindEvents: function() {
            const self = this;
            
            // Add ticket button
            $(document).on('click', '.es-add-ticket-button', function(e) {
                e.preventDefault();
                self.openTicketForm();
            });
            
            // Edit ticket
            $(document).on('click', '.es-ticket-admin-action--edit', function(e) {
                e.preventDefault();
                const ticketId = $(this).closest('.es-tickets-admin-item').data('ticket-id');
                self.openTicketForm(ticketId);
            });
            
            // Delete ticket
            $(document).on('click', '.es-ticket-admin-action--delete', function(e) {
                e.preventDefault();
                const ticketId = $(this).closest('.es-tickets-admin-item').data('ticket-id');
                self.deleteTicket(ticketId);
            });
            
            // Close modal
            $(document).on('click', '.es-ticket-form-close, .es-ticket-form-cancel', function(e) {
                e.preventDefault();
                self.closeTicketForm();
            });
            
            // Close on backdrop click
            $(document).on('click', '.es-ticket-form-overlay', function(e) {
                if ($(e.target).is('.es-ticket-form-overlay')) {
                    self.closeTicketForm();
                }
            });
            
            // Save ticket
            $(document).on('click', '.es-ticket-form-save', function(e) {
                e.preventDefault();
                self.saveTicket();
            });
            
            // Toggle section
            $(document).on('click', '.es-ticket-form-section-header', function() {
                $(this).closest('.es-ticket-form-section').toggleClass('is-collapsed');
            });
            
            // Escape key closes modal
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && $('.es-ticket-form-overlay.is-active').length) {
                    self.closeTicketForm();
                }
            });
        },
        
        /**
         * Initialize sortable
         */
        initSortable: function() {
            const self = this;
            
            if ($.fn.sortable) {
                $('.es-tickets-admin-list').sortable({
                    handle: '.es-ticket-drag-handle',
                    placeholder: 'ui-sortable-placeholder',
                    update: function(event, ui) {
                        self.reorderTickets();
                    }
                });
            }
        },
        
        /**
         * Reorder tickets after drag
         */
        reorderTickets: function() {
            const self = this;
            const newOrder = [];
            
            $('.es-tickets-admin-item').each(function() {
                const ticketId = $(this).data('ticket-id');
                const ticket = self.tickets.find(t => t.id === ticketId);
                if (ticket) {
                    newOrder.push(ticket);
                }
            });
            
            this.tickets = newOrder;
            this.saveTickets();
        },
        
        /**
         * Open ticket form modal
         * 
         * @param {string|null} ticketId Ticket ID to edit, or null for new
         */
        openTicketForm: function(ticketId) {
            const self = this;
            ticketId = ticketId || null;
            
            if (ticketId) {
                this.currentTicket = this.tickets.find(t => t.id === ticketId);
            } else {
                this.currentTicket = null;
            }
            
            // Create or show modal
            let $overlay = $('.es-ticket-form-overlay');
            
            if (!$overlay.length) {
                $overlay = this.createModal();
                $('body').append($overlay);
            }
            
            // Populate form
            this.populateForm();
            
            // Show modal
            setTimeout(function() {
                $overlay.addClass('is-active');
            }, 10);
        },
        
        /**
         * Close ticket form modal
         */
        closeTicketForm: function() {
            $('.es-ticket-form-overlay').removeClass('is-active');
            this.currentTicket = null;
        },
        
        /**
         * Create modal HTML
         * 
         * @returns {jQuery} Modal element
         */
        createModal: function() {
            const providers = ensembleTicketsAdmin.providers;
            const ticketTypes = ensembleTicketsAdmin.ticketTypes;
            const statuses = ensembleTicketsAdmin.statuses;
            const strings = ensembleTicketsAdmin.strings;
            
            let providerOptions = '';
            for (const [id, provider] of Object.entries(providers)) {
                providerOptions += '<div class="es-ticket-provider-option">' +
                    '<input type="radio" name="ticket_provider" id="provider_' + id + '" value="' + id + '">' +
                    '<label for="provider_' + id + '">' +
                    '<span class="es-ticket-provider-icon">' +
                    '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
                    '<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>' +
                    '<polyline points="15 3 21 3 21 9"/>' +
                    '<line x1="10" y1="14" x2="21" y2="3"/>' +
                    '</svg></span>' +
                    '<span class="es-ticket-provider-name">' + provider.name + '</span>' +
                    '</label></div>';
            }
            
            let typeOptions = '';
            for (const [id, type] of Object.entries(ticketTypes)) {
                typeOptions += '<div class="es-ticket-type-option">' +
                    '<input type="radio" name="ticket_type" id="type_' + id + '" value="' + id + '">' +
                    '<label for="type_' + id + '">' +
                    '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
                    '<path d="M3 9a2 2 0 0 0 0 4v3a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-3a2 2 0 0 0 0-4V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2z"/>' +
                    '</svg> ' + type.name + '</label></div>';
            }
            
            let statusOptions = '';
            for (const [id, status] of Object.entries(statuses)) {
                statusOptions += '<option value="' + id + '">' + status.name + '</option>';
            }
            
            const html = '<div class="es-ticket-form-overlay">' +
                '<div class="es-ticket-form-modal">' +
                '<div class="es-ticket-form-header">' +
                '<h3>' + strings.addTicket + '</h3>' +
                '<button type="button" class="es-ticket-form-close">' +
                '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
                '<line x1="18" y1="6" x2="6" y2="18"></line>' +
                '<line x1="6" y1="6" x2="18" y2="18"></line>' +
                '</svg></button></div>' +
                
                '<div class="es-ticket-form-body">' +
                '<form id="es-ticket-form">' +
                '<input type="hidden" name="ticket_id" id="ticket_id" value="">' +
                
                '<div class="es-ticket-form-row">' +
                '<label class="es-ticket-form-label">Provider</label>' +
                '<div class="es-ticket-provider-grid">' + providerOptions + '</div></div>' +
                
                '<div class="es-ticket-form-row">' +
                '<label class="es-ticket-form-label">Ticket Type</label>' +
                '<div class="es-ticket-type-grid">' + typeOptions + '</div></div>' +
                
                '<div class="es-ticket-form-row">' +
                '<label class="es-ticket-form-label" for="ticket_name">Name (optional)</label>' +
                '<input type="text" class="es-ticket-form-input" id="ticket_name" name="ticket_name" placeholder="e.g. VIP Package, Early Bird Special">' +
                '<p class="es-ticket-form-hint">Custom name for this ticket. Leave empty to use the type name.</p></div>' +
                
                '<div class="es-ticket-form-row">' +
                '<label class="es-ticket-form-label" for="ticket_url">Ticket URL *</label>' +
                '<input type="url" class="es-ticket-form-input" id="ticket_url" name="ticket_url" placeholder="https://..." required></div>' +
                
                '<div class="es-ticket-form-row-group">' +
                '<div class="es-ticket-form-row">' +
                '<label class="es-ticket-form-label" for="ticket_price">Price</label>' +
                '<input type="number" class="es-ticket-form-input" id="ticket_price" name="ticket_price" min="0" step="0.01" placeholder="0.00"></div>' +
                '<div class="es-ticket-form-row">' +
                '<label class="es-ticket-form-label" for="ticket_price_max">Price Max (Range)</label>' +
                '<input type="number" class="es-ticket-form-input" id="ticket_price_max" name="ticket_price_max" min="0" step="0.01" placeholder="0.00"></div></div>' +
                
                '<div class="es-ticket-form-row">' +
                '<label class="es-ticket-form-label" for="ticket_availability">Availability</label>' +
                '<select class="es-ticket-form-select" id="ticket_availability" name="ticket_availability">' + statusOptions + '</select></div>' +
                
                '<div class="es-ticket-form-row">' +
                '<label class="es-ticket-form-label" for="ticket_description">Description (optional)</label>' +
                '<textarea class="es-ticket-form-textarea" id="ticket_description" name="ticket_description" placeholder="Short description or included features..."></textarea></div>' +
                
                '<div class="es-ticket-form-section is-collapsed">' +
                '<div class="es-ticket-form-section-header">' +
                '<h4>Tracking Parameters</h4>' +
                '<span class="es-ticket-form-section-toggle">' +
                '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
                '<polyline points="6 9 12 15 18 9"></polyline></svg></span></div>' +
                '<div class="es-ticket-form-section-body">' +
                '<div class="es-ticket-form-row-group">' +
                '<div class="es-ticket-form-row">' +
                '<label class="es-ticket-form-label" for="tracking_utm_source">UTM Source</label>' +
                '<input type="text" class="es-ticket-form-input" id="tracking_utm_source" name="tracking_utm_source" placeholder="e.g. website"></div>' +
                '<div class="es-ticket-form-row">' +
                '<label class="es-ticket-form-label" for="tracking_utm_medium">UTM Medium</label>' +
                '<input type="text" class="es-ticket-form-input" id="tracking_utm_medium" name="tracking_utm_medium" placeholder="e.g. event_page"></div></div>' +
                '<div class="es-ticket-form-row">' +
                '<label class="es-ticket-form-label" for="tracking_utm_campaign">UTM Campaign</label>' +
                '<input type="text" class="es-ticket-form-input" id="tracking_utm_campaign" name="tracking_utm_campaign" placeholder="e.g. summer_festival_2024"></div>' +
                '<div class="es-ticket-form-row">' +
                '<label class="es-ticket-form-label" for="tracking_custom">Custom Parameters</label>' +
                '<input type="text" class="es-ticket-form-input" id="tracking_custom" name="tracking_custom" placeholder="e.g. ref=123&affiliate=abc">' +
                '<p class="es-ticket-form-hint">Additional query parameters to append to the URL.</p></div>' +
                '</div></div>' +
                '</form></div>' +
                
                '<div class="es-ticket-form-footer">' +
                '<button type="button" class="es-ticket-form-cancel">' + strings.cancel + '</button>' +
                '<button type="button" class="es-ticket-form-save">' + strings.save + '</button>' +
                '</div></div></div>';
            
            return $(html);
        },
        
        /**
         * Populate form with ticket data
         */
        populateForm: function() {
            const ticket = this.currentTicket;
            const $form = $('#es-ticket-form');
            
            // Reset form
            $form[0].reset();
            
            // Set title
            const title = ticket ? ensembleTicketsAdmin.strings.editTicket : ensembleTicketsAdmin.strings.addTicket;
            $('.es-ticket-form-header h3').text(title);
            
            if (ticket) {
                $('#ticket_id').val(ticket.id);
                $('input[name="ticket_provider"][value="' + ticket.provider + '"]').prop('checked', true);
                $('input[name="ticket_type"][value="' + ticket.type + '"]').prop('checked', true);
                $('#ticket_name').val(ticket.name || '');
                $('#ticket_url').val(ticket.url || '');
                $('#ticket_price').val(ticket.price || '');
                $('#ticket_price_max').val(ticket.price_max || '');
                $('#ticket_availability').val(ticket.availability || 'available');
                $('#ticket_description').val(ticket.description || '');
                
                // Tracking
                if (ticket.tracking) {
                    $('#tracking_utm_source').val(ticket.tracking.utm_source || '');
                    $('#tracking_utm_medium').val(ticket.tracking.utm_medium || '');
                    $('#tracking_utm_campaign').val(ticket.tracking.utm_campaign || '');
                    $('#tracking_custom').val(ticket.tracking.custom || '');
                }
            } else {
                // Defaults for new ticket
                $('input[name="ticket_provider"][value="custom"]').prop('checked', true);
                $('input[name="ticket_type"][value="standard"]').prop('checked', true);
                $('#ticket_availability').val('available');
            }
        },
        
        /**
         * Save ticket from form
         */
        saveTicket: function() {
            // Validate required fields
            const url = $('#ticket_url').val();
            if (!url) {
                alert('Please enter a ticket URL.');
                $('#ticket_url').focus();
                return;
            }
            
            // Gather data
            const ticketData = {
                id: $('#ticket_id').val() || 'ticket_' + Date.now(),
                provider: $('input[name="ticket_provider"]:checked').val() || 'custom',
                type: $('input[name="ticket_type"]:checked').val() || 'standard',
                name: $('#ticket_name').val(),
                url: url,
                price: parseFloat($('#ticket_price').val()) || 0,
                price_max: parseFloat($('#ticket_price_max').val()) || 0,
                currency: ensembleTicketsAdmin.currency,
                availability: $('#ticket_availability').val(),
                description: $('#ticket_description').val(),
                tracking: {
                    utm_source: $('#tracking_utm_source').val(),
                    utm_medium: $('#tracking_utm_medium').val(),
                    utm_campaign: $('#tracking_utm_campaign').val(),
                    custom: $('#tracking_custom').val()
                }
            };
            
            // Update or add ticket
            const existingIndex = this.tickets.findIndex(t => t.id === ticketData.id);
            if (existingIndex >= 0) {
                this.tickets[existingIndex] = ticketData;
            } else {
                this.tickets.push(ticketData);
            }
            
            // Save and refresh
            this.saveTickets();
            this.renderTicketList();
            this.closeTicketForm();
        },
        
        /**
         * Delete ticket
         * 
         * @param {string} ticketId Ticket ID
         */
        deleteTicket: function(ticketId) {
            if (!confirm(ensembleTicketsAdmin.strings.confirmDelete)) {
                return;
            }
            
            this.tickets = this.tickets.filter(t => t.id !== ticketId);
            this.saveTickets();
            this.renderTicketList();
        },
        
        /**
         * Render ticket list
         */
        renderTicketList: function() {
            const $container = $('.es-tickets-admin');
            const $list = $container.find('.es-tickets-admin-list');
            const $empty = $container.find('.es-tickets-empty-state');
            
            if (this.tickets.length === 0) {
                $list.hide();
                if (!$empty.length) {
                    $container.prepend(this.getEmptyStateHtml());
                } else {
                    $empty.show();
                }
                return;
            }
            
            $empty.hide();
            $list.show().empty();
            
            this.tickets.forEach(ticket => {
                $list.append(this.getTicketItemHtml(ticket));
            });
            
            // Re-init sortable
            this.initSortable();
        },
        
        /**
         * Get empty state HTML
         * 
         * @returns {string} HTML
         */
        getEmptyStateHtml: function() {
            return '<div class="es-tickets-empty-state">' +
                '<div class="es-tickets-empty-state-icon">' +
                '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
                '<path d="M3 9a2 2 0 0 0 0 4v3a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-3a2 2 0 0 0 0-4V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2z"/>' +
                '<line x1="12" y1="7" x2="12" y2="17"/></svg></div>' +
                '<p>' + ensembleTicketsAdmin.strings.noTickets + '</p></div>';
        },
        
        /**
         * Get ticket item HTML
         * 
         * @param {Object} ticket Ticket data
         * @returns {string} HTML
         */
        getTicketItemHtml: function(ticket) {
            const type = ensembleTicketsAdmin.ticketTypes[ticket.type] || { name: ticket.type };
            const status = ensembleTicketsAdmin.statuses[ticket.availability] || { name: ticket.availability };
            const provider = ensembleTicketsAdmin.providers[ticket.provider] || { name: ticket.provider };
            
            // Format price
            let priceDisplay = ticket.price > 0 ? this.formatPrice(ticket.price) : 'Free';
            if (ticket.price_max > ticket.price) {
                priceDisplay += ' - ' + this.formatPrice(ticket.price_max);
            }
            
            // Display name
            const displayName = ticket.name || type.name;
            
            return '<li class="es-tickets-admin-item" data-ticket-id="' + ticket.id + '">' +
                '<div class="es-ticket-drag-handle">' +
                '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
                '<line x1="8" y1="6" x2="8" y2="6.01"/><line x1="8" y1="12" x2="8" y2="12.01"/>' +
                '<line x1="8" y1="18" x2="8" y2="18.01"/><line x1="16" y1="6" x2="16" y2="6.01"/>' +
                '<line x1="16" y1="12" x2="16" y2="12.01"/><line x1="16" y1="18" x2="16" y2="18.01"/>' +
                '</svg></div>' +
                '<div class="es-ticket-admin-info">' +
                '<h4 class="es-ticket-admin-name">' + displayName + 
                '<span class="es-ticket-admin-type">' + type.name + '</span></h4>' +
                '<div class="es-ticket-admin-meta">' +
                '<span class="es-ticket-admin-meta-item">' +
                '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
                '<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>' +
                '<polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg> ' +
                provider.name + '</span>' +
                '<span class="es-ticket-admin-status es-ticket-admin-status--' + ticket.availability + '">' +
                status.name + '</span></div></div>' +
                '<div class="es-ticket-admin-price">' + priceDisplay + '</div>' +
                '<div class="es-ticket-admin-actions">' +
                '<button type="button" class="es-ticket-admin-action es-ticket-admin-action--edit" title="Edit">' +
                '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
                '<path d="M12 20h9"/><path d="M16.5 3.5l3 3L7 19l-4 1 1-4z"/></svg></button>' +
                '<button type="button" class="es-ticket-admin-action es-ticket-admin-action--delete" title="Delete">' +
                '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
                '<path d="M4 7h16"/><path d="M10 4h4"/><path d="M6 7l1 13h10l1-13"/>' +
                '<line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg></button>' +
                '</div></li>';
        },
        
        /**
         * Format price
         * 
         * @param {number} price Price value
         * @returns {string} Formatted price
         */
        formatPrice: function(price) {
            const currency = ensembleTicketsAdmin.currency || 'EUR';
            const symbols = { EUR: '€', USD: '$', GBP: '£', CHF: 'CHF' };
            const symbol = symbols[currency] || currency + ' ';
            
            return price.toFixed(2).replace('.', ',') + ' ' + symbol;
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        if ($('#ensemble_tickets').length) {
            EnsembleTicketsAdmin.init();
        }
    });

})(jQuery);

/**
 * Ensemble Tickets - Wizard Integration
 */
(function($) {
    'use strict';

    const EnsembleTicketsWizard = {
        
        tickets: [],
        currentTicket: null,
        $modal: null,
        
        /**
         * Initialize
         */
        init: function() {
            if (!$('#es-tickets-wizard-list').length) {
                return;
            }
            
            this.loadTickets();
            this.createModal();
            this.bindEvents();
            this.render();
        },
        
        /**
         * Load tickets from hidden input and merge global tickets
         */
        loadTickets: function() {
            // Load local event tickets
            const $input = $('#es-tickets-data');
            if ($input.length && $input.val()) {
                try {
                    this.tickets = JSON.parse($input.val());
                } catch (e) {
                    this.tickets = [];
                }
            }
            
            // Mark local tickets
            this.tickets.forEach(t => { t.is_global = false; });
            
            // Load and merge global tickets
            const globalTickets = ensembleTicketsAdmin?.globalTickets || [];
            const excludedGlobal = this.getExcludedGlobalTickets();
            
            globalTickets.forEach(globalTicket => {
                // Skip if excluded
                if (excludedGlobal.includes(globalTicket.id)) {
                    return;
                }
                
                // Add global ticket with flag
                globalTicket.is_global = true;
                this.tickets.push(globalTicket);
            });
        },
        
        /**
         * Get excluded global ticket IDs for this event
         */
        getExcludedGlobalTickets: function() {
            const $input = $('#es-excluded-global-tickets');
            if ($input.length && $input.val()) {
                try {
                    return JSON.parse($input.val());
                } catch (e) {
                    return [];
                }
            }
            return [];
        },
        
        /**
         * Save tickets to hidden input (only local tickets)
         */
        saveTickets: function() {
            // Filter out global tickets - they shouldn't be saved to event meta
            const localTickets = this.tickets.filter(t => !t.is_global);
            $('#es-tickets-data').val(JSON.stringify(localTickets));
        },
        
        /**
         * Bind events
         */
        bindEvents: function() {
            const self = this;
            
            // Add ticket button
            $(document).on('click', '#es-add-ticket-wizard-btn', function(e) {
                e.preventDefault();
                self.openModal();
            });
            
            // Edit ticket
            $(document).on('click', '.es-ticket-wizard-edit', function(e) {
                e.preventDefault();
                const ticketId = $(this).closest('.es-ticket-wizard-item').data('ticket-id');
                self.openModal(ticketId);
            });
            
            // Delete ticket
            $(document).on('click', '.es-ticket-wizard-delete', function(e) {
                e.preventDefault();
                const $item = $(this).closest('.es-ticket-wizard-item');
                const ticketId = $item.data('ticket-id');
                const isGlobal = $item.data('is-global');
                
                if (isGlobal) {
                    // For global tickets, exclude instead of delete
                    if (confirm(ensembleTicketsAdmin?.strings?.confirmExclude || 'Exclude this global ticket from this event?')) {
                        self.excludeGlobalTicket(ticketId);
                    }
                } else {
                    if (confirm(ensembleTicketsAdmin?.strings?.confirmDelete || 'Really delete this ticket?')) {
                        self.deleteTicket(ticketId);
                    }
                }
            });
            
            // Close modal
            $(document).on('click', '.es-ticket-wizard-modal-close, .es-ticket-wizard-cancel', function(e) {
                e.preventDefault();
                self.closeModal();
            });
            
            // Save ticket
            $(document).on('click', '.es-ticket-wizard-save', function(e) {
                e.preventDefault();
                self.saveTicket();
            });
            
            // Close on backdrop click
            $(document).on('click', '.es-ticket-wizard-modal', function(e) {
                if ($(e.target).is('.es-ticket-wizard-modal')) {
                    self.closeModal();
                }
            });
            
            // ESC key closes modal
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && self.$modal && self.$modal.hasClass('is-active')) {
                    self.closeModal();
                }
            });
        },
        
        /**
         * Create modal
         */
        createModal: function() {
            // Debug: Log what we receive from PHP
            console.log('ensembleTicketsAdmin:', ensembleTicketsAdmin);
            
            const providers = ensembleTicketsAdmin?.providers || {
                'custom': { name: 'Custom Provider' },
                'eventbrite': { name: 'Eventbrite' },
                'eventim': { name: 'Eventim' },
                'ticketmaster': { name: 'Ticketmaster' },
                'reservix': { name: 'Reservix' },
                'tickets_io': { name: 'tickets.io' }
            };
            
            // Statuses from PHP have 'label' property, not 'name'
            const defaultStatuses = {
                'available': { label: 'Available' },
                'limited': { label: 'Limited' },
                'few_left': { label: 'Few Left' },
                'presale': { label: 'Presale' },
                'sold_out': { label: 'Sold Out' },
                'cancelled': { label: 'Cancelled' }
            };
            
            const statuses = ensembleTicketsAdmin?.statuses || defaultStatuses;
            
            let providerOptions = '';
            for (const [id, provider] of Object.entries(providers)) {
                const providerName = provider?.name || provider || id;
                providerOptions += `<option value="${id}">${providerName}</option>`;
            }
            
            let statusOptions = '';
            for (const [id, status] of Object.entries(statuses)) {
                // PHP sends 'label', fallback to 'name', then to id
                let displayName = id; // Default fallback
                if (status && typeof status === 'object') {
                    displayName = status.label || status.name || id;
                } else if (typeof status === 'string') {
                    displayName = status;
                }
                statusOptions += `<option value="${id}">${displayName}</option>`;
            }
            
            const html = `
                <div class="es-ticket-wizard-modal" id="es-ticket-wizard-modal">
                    <div class="es-ticket-wizard-modal-content">
                        <div class="es-ticket-wizard-modal-header">
                            <h3>Add Ticket</h3>
                            <button type="button" class="es-ticket-wizard-modal-close">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                </svg>
                            </button>
                        </div>
                        <div class="es-ticket-wizard-modal-body">
                            <input type="hidden" id="es-tw-ticket-id" value="">
                            
                            <div class="es-form-row">
                                <label for="es-tw-provider">Provider</label>
                                <select id="es-tw-provider">${providerOptions}</select>
                            </div>
                            
                            <div class="es-form-row">
                                <label for="es-tw-name">Ticket Name</label>
                                <input type="text" id="es-tw-name" placeholder="e.g. VIP Ticket, Early Bird...">
                            </div>
                            
                            <div class="es-form-row">
                                <label for="es-tw-url">Ticket URL *</label>
                                <input type="url" id="es-tw-url" placeholder="https://..." required>
                            </div>
                            
                            <div class="es-form-row-group">
                                <div class="es-form-row">
                                    <label for="es-tw-price">Price (€)</label>
                                    <input type="number" id="es-tw-price" min="0" step="0.01" placeholder="0.00">
                                </div>
                                <div class="es-form-row">
                                    <label for="es-tw-price-max">Price to (optional)</label>
                                    <input type="number" id="es-tw-price-max" min="0" step="0.01" placeholder="0.00">
                                </div>
                            </div>
                            
                            <div class="es-form-row">
                                <label for="es-tw-availability">Availability</label>
                                <select id="es-tw-availability">${statusOptions}</select>
                            </div>
                            
                            <div class="es-form-row">
                                <label for="es-tw-custom-text">Custom Text (optional)</label>
                                <input type="text" id="es-tw-custom-text" placeholder="e.g. All 3 days included">
                            </div>
                        </div>
                        <div class="es-ticket-wizard-modal-footer">
                            <button type="button" class="button es-ticket-wizard-cancel">Cancel</button>
                            <button type="button" class="button button-primary es-ticket-wizard-save">Save</button>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(html);
            this.$modal = $('#es-ticket-wizard-modal');
        },
        
        /**
         * Open modal
         */
        openModal: function(ticketId) {
            ticketId = ticketId || null;
            
            // Reset form
            this.$modal.find('#es-tw-ticket-id').val('');
            this.$modal.find('#es-tw-provider').val('custom');
            this.$modal.find('#es-tw-name').val('');
            this.$modal.find('#es-tw-url').val('');
            this.$modal.find('#es-tw-price').val('');
            this.$modal.find('#es-tw-price-max').val('');
            this.$modal.find('#es-tw-availability').val('available');
            this.$modal.find('#es-tw-custom-text').val('');
            
            if (ticketId) {
                const ticket = this.tickets.find(t => t.id === ticketId);
                if (ticket) {
                    this.$modal.find('#es-tw-ticket-id').val(ticket.id);
                    this.$modal.find('#es-tw-provider').val(ticket.provider || 'custom');
                    this.$modal.find('#es-tw-name').val(ticket.name || '');
                    this.$modal.find('#es-tw-url').val(ticket.url || '');
                    this.$modal.find('#es-tw-price').val(ticket.price || '');
                    this.$modal.find('#es-tw-price-max').val(ticket.price_max || '');
                    this.$modal.find('#es-tw-availability').val(ticket.availability || 'available');
                    this.$modal.find('#es-tw-custom-text').val(ticket.custom_text || '');
                    
                    this.$modal.find('.es-ticket-wizard-modal-header h3').text('Edit Ticket');
                }
            } else {
                this.$modal.find('.es-ticket-wizard-modal-header h3').text('Add Ticket');
            }
            
            this.$modal.addClass('is-active');
        },
        
        /**
         * Close modal
         */
        closeModal: function() {
            this.$modal.removeClass('is-active');
            this.currentTicket = null;
        },
        
        /**
         * Save ticket
         */
        saveTicket: function() {
            const url = this.$modal.find('#es-tw-url').val();
            if (!url) {
                alert('Please enter a ticket URL.');
                this.$modal.find('#es-tw-url').focus();
                return;
            }
            
            const ticketData = {
                id: this.$modal.find('#es-tw-ticket-id').val() || 'ticket_' + Date.now(),
                provider: this.$modal.find('#es-tw-provider').val(),
                name: this.$modal.find('#es-tw-name').val(),
                url: url,
                price: parseFloat(this.$modal.find('#es-tw-price').val()) || 0,
                price_max: parseFloat(this.$modal.find('#es-tw-price-max').val()) || 0,
                currency: ensembleTicketsAdmin?.currency || 'EUR',
                availability: this.$modal.find('#es-tw-availability').val(),
                custom_text: this.$modal.find('#es-tw-custom-text').val(),
                is_global: false
            };
            
            // Update or add
            const existingIndex = this.tickets.findIndex(t => t.id === ticketData.id);
            if (existingIndex >= 0) {
                this.tickets[existingIndex] = ticketData;
            } else {
                this.tickets.push(ticketData);
            }
            
            this.saveTickets();
            this.render();
            this.closeModal();
        },
        
        /**
         * Delete ticket
         */
        deleteTicket: function(ticketId) {
            this.tickets = this.tickets.filter(t => t.id !== ticketId);
            this.saveTickets();
            this.render();
        },
        
        /**
         * Exclude a global ticket from this event
         */
        excludeGlobalTicket: function(ticketId) {
            // Remove from display
            this.tickets = this.tickets.filter(t => t.id !== ticketId);
            
            // Add to exclusion list
            let excluded = this.getExcludedGlobalTickets();
            if (!excluded.includes(ticketId)) {
                excluded.push(ticketId);
            }
            $('#es-excluded-global-tickets').val(JSON.stringify(excluded));
            
            this.render();
        },
        
        /**
         * Render ticket list
         */
        render: function() {
            const $list = $('#es-tickets-wizard-list');
            const $empty = $('#es-tickets-wizard-empty');
            
            $list.empty();
            
            if (this.tickets.length === 0) {
                $empty.show();
                return;
            }
            
            $empty.hide();
            
            const providers = ensembleTicketsAdmin?.providers || {};
            const statuses = ensembleTicketsAdmin?.statuses || {};
            
            this.tickets.forEach(ticket => {
                const providerData = providers[ticket.provider];
                const providerName = providerData?.name || ticket.provider || 'Link';
                
                const statusData = statuses[ticket.availability];
                let statusLabel = ticket.availability || 'Available';
                if (statusData && typeof statusData === 'object') {
                    statusLabel = statusData.label || statusData.name || ticket.availability;
                }
                
                const displayName = ticket.name || providerName;
                const isGlobal = ticket.is_global === true;
                
                let priceDisplay = 'Free';
                if (ticket.price > 0) {
                    priceDisplay = ticket.price.toFixed(2).replace('.', ',') + ' €';
                    if (ticket.price_max > ticket.price) {
                        priceDisplay += ' - ' + ticket.price_max.toFixed(2).replace('.', ',') + ' €';
                    }
                }
                
                // Global badge
                const globalBadge = isGlobal ? '<span class="es-ticket-global-badge" title="Global Ticket">●</span>' : '';
                
                // Custom text
                const customTextHtml = ticket.custom_text ? `<div class="es-ticket-custom-text">${ticket.custom_text}</div>` : '';
                
                // For global tickets, edit button is hidden and delete becomes "exclude"
                const editButton = isGlobal ? '' : `
                    <button type="button" class="es-ticket-wizard-edit" title="Edit">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 20h9"/>
                            <path d="M16.5 3.5l3 3L7 19l-4 1 1-4z"/>
                        </svg>
                    </button>
                `;
                
                const deleteTitle = isGlobal ? 'Exclude from this event' : 'Delete';
                
                const html = `
                    <div class="es-ticket-wizard-item ${isGlobal ? 'es-ticket-wizard-item--global' : ''}" data-ticket-id="${ticket.id}" data-is-global="${isGlobal}">
                        <div class="es-ticket-provider-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 9a2 2 0 0 0 0 4v3a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-3a2 2 0 0 0 0-4V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2z"/>
                            </svg>
                        </div>
                        <div class="es-ticket-wizard-info">
                            <h4>${displayName} ${globalBadge}</h4>
                            ${customTextHtml}
                            <div class="es-ticket-meta">
                                <span>${providerName}</span>
                                <span>•</span>
                                <span>${statusLabel}</span>
                            </div>
                        </div>
                        <div class="es-ticket-wizard-price">${priceDisplay}</div>
                        <div class="es-ticket-wizard-actions">
                            ${editButton}
                            <button type="button" class="es-ticket-wizard-delete es-delete" title="${deleteTitle}">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    ${isGlobal ? 
                                        '<path d="M18 6L6 18M6 6l12 12"/>' :
                                        '<path d="M4 7h16"/><path d="M10 4h4"/><path d="M6 7l1 13h10l1-13"/>'
                                    }
                                </svg>
                            </button>
                        </div>
                    </div>
                `;
                
                $list.append(html);
            });
        }
    };

    // Export to global scope for admin.js access
    window.EnsembleTicketsWizard = EnsembleTicketsWizard;

    // Initialize on document ready
    $(document).ready(function() {
        EnsembleTicketsWizard.init();
    });

})(jQuery);
