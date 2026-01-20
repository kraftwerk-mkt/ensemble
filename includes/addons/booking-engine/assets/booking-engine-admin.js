/**
 * Booking Engine Admin JavaScript
 * 
 * @package Ensemble
 * @subpackage Addons/BookingEngine
 */

(function($) {
    'use strict';
    
    // State
    var state = {
        bookings: [],
        currentPage: 1,
        perPage: 50,
        totalBookings: 0,
        filters: {
            event_id: '',
            status: '',
            booking_type: '',
            search: ''
        },
        selectedBooking: null
    };
    
    // Initialize
    $(document).ready(function() {
        initFilters();
        initModals();
        initActions();
        loadBookings();
    });
    
    /**
     * Initialize filters
     */
    function initFilters() {
        // Filter selects
        $('#es-filter-event, #es-filter-type, #es-filter-status').on('change', function() {
            state.filters.event_id = $('#es-filter-event').val();
            state.filters.booking_type = $('#es-filter-type').val();
            state.filters.status = $('#es-filter-status').val();
            state.currentPage = 1;
            loadBookings();
        });
        
        // Search with debounce
        var searchTimeout;
        $('#es-search-bookings').on('input', function() {
            clearTimeout(searchTimeout);
            var val = $(this).val();
            searchTimeout = setTimeout(function() {
                state.filters.search = val;
                state.currentPage = 1;
                loadBookings();
            }, 300);
        });
        
        // Pagination
        $('.es-page-prev').on('click', function() {
            if (state.currentPage > 1) {
                state.currentPage--;
                loadBookings();
            }
        });
        
        $('.es-page-next').on('click', function() {
            var totalPages = Math.ceil(state.totalBookings / state.perPage);
            if (state.currentPage < totalPages) {
                state.currentPage++;
                loadBookings();
            }
        });
        
        // Export
        $('.es-export-menu a').on('click', function(e) {
            e.preventDefault();
            var format = $(this).data('format');
            exportBookings(format);
        });
    }
    
    /**
     * Initialize modals
     */
    function initModals() {
        // Add booking button
        $('#es-add-booking').on('click', function() {
            openBookingModal();
        });
        
        // Close modals
        $('.es-modal-close, .es-modal-cancel, .es-modal-overlay').on('click', function() {
            closeModals();
        });
        
        // Save booking
        $('#es-save-booking').on('click', function() {
            saveBooking();
        });
        
        // Resend email
        $('#es-resend-email').on('click', function() {
            if (state.selectedBooking) {
                resendEmail(state.selectedBooking.id);
            }
        });
        
        // Check in from detail modal
        $('#es-detail-checkin').on('click', function() {
            if (state.selectedBooking) {
                checkinBooking(state.selectedBooking.id);
            }
        });
        
        // Confirm from detail modal
        $('#es-detail-confirm').on('click', function() {
            if (state.selectedBooking) {
                updateStatus(state.selectedBooking.id, 'confirmed');
                closeModals();
            }
        });
        
        // Cancel from detail modal
        $('#es-detail-cancel').on('click', function() {
            if (state.selectedBooking && confirm(ensembleBookingEngine.strings.confirmCancel)) {
                updateStatus(state.selectedBooking.id, 'cancelled');
                closeModals();
            }
        });
        
        // ESC key closes modals
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModals();
            }
        });
    }
    
    /**
     * Initialize row actions (delegated)
     */
    function initActions() {
        // View booking
        $('#es-bookings-tbody').on('click', '.es-action-view', function() {
            var bookingId = $(this).closest('tr').data('id');
            viewBooking(bookingId);
        });
        
        // Check in
        $('#es-bookings-tbody').on('click', '.es-action-checkin', function() {
            var bookingId = $(this).closest('tr').data('id');
            if (confirm(ensembleBookingEngine.strings.confirmCheckin)) {
                checkinBooking(bookingId);
            }
        });
        
        // Confirm booking (pending -> confirmed)
        $('#es-bookings-tbody').on('click', '.es-action-confirm', function() {
            var bookingId = $(this).closest('tr').data('id');
            updateStatus(bookingId, 'confirmed');
        });
        
        // Cancel booking
        $('#es-bookings-tbody').on('click', '.es-action-cancel', function() {
            var bookingId = $(this).closest('tr').data('id');
            if (confirm(ensembleBookingEngine.strings.confirmCancel)) {
                updateStatus(bookingId, 'cancelled');
            }
        });
        
        // Delete
        $('#es-bookings-tbody').on('click', '.es-action-delete', function() {
            var bookingId = $(this).closest('tr').data('id');
            if (confirm(ensembleBookingEngine.strings.confirmDelete)) {
                deleteBooking(bookingId);
            }
        });
        
        // Status change
        $('#es-bookings-tbody').on('change', '.es-status-select', function() {
            var bookingId = $(this).closest('tr').data('id');
            var newStatus = $(this).val();
            updateStatus(bookingId, newStatus);
        });
        
        // Click on row to view
        $('#es-bookings-tbody').on('click', 'td:not(.es-col-checkbox):not(.es-col-actions)', function() {
            var bookingId = $(this).closest('tr').data('id');
            if (bookingId) {
                viewBooking(bookingId);
            }
        });
    }
    
    /**
     * Load bookings via AJAX
     */
    function loadBookings() {
        showLoading();
        
        $.ajax({
            url: ensembleBookingEngine.ajaxUrl,
            type: 'POST',
            data: {
                action: 'es_get_bookings',
                nonce: ensembleBookingEngine.nonce,
                event_id: state.filters.event_id,
                status: state.filters.status,
                booking_type: state.filters.booking_type,
                search: state.filters.search,
                limit: state.perPage,
                offset: (state.currentPage - 1) * state.perPage
            },
            success: function(response) {
                if (response.success) {
                    state.bookings = response.data.bookings;
                    renderBookings();
                    updateStats();
                } else {
                    showError(response.data.message);
                }
            },
            error: function() {
                showError(ensembleBookingEngine.strings.error);
            }
        });
    }
    
    /**
     * Render bookings table
     */
    function renderBookings() {
        var $tbody = $('#es-bookings-tbody');
        $tbody.empty();
        
        if (state.bookings.length === 0) {
            $('#es-bookings-table').hide();
            $('#es-empty-state').show();
            $('#es-pagination').hide();
            return;
        }
        
        $('#es-bookings-table').show();
        $('#es-empty-state').hide();
        
        state.bookings.forEach(function(booking) {
            var row = renderBookingRow(booking);
            $tbody.append(row);
        });
        
        updatePagination();
    }
    
    /**
     * Render single booking row
     */
    function renderBookingRow(booking) {
        var typeIcon = booking.booking_type === 'ticket' ? 'ticket' : 'groups';
        var typeClass = 'es-type-' + booking.booking_type;
        var statusClass = 'es-status-' + booking.status;
        var paymentClass = 'es-payment-' + booking.payment_status;
        
        var row = '<tr data-id="' + booking.id + '">';
        row += '<td class="es-col-checkbox"><input type="checkbox" value="' + booking.id + '"></td>';
        row += '<td class="es-col-code"><span class="es-booking-code">' + escapeHtml(booking.confirmation_code) + '</span></td>';
        row += '<td class="es-col-event">' + escapeHtml(booking.event_title || '-') + '</td>';
        row += '<td class="es-col-type"><span class="es-type-badge ' + typeClass + '"><span class="dashicons dashicons-' + typeIcon + '"></span>' + escapeHtml(booking.type_label) + '</span></td>';
        row += '<td class="es-col-customer"><div class="es-customer-info"><span class="es-customer-name">' + escapeHtml(booking.customer_name) + '</span><span class="es-customer-email">' + escapeHtml(booking.customer_email) + '</span></div></td>';
        row += '<td class="es-col-guests">' + booking.guests + '</td>';
        row += '<td class="es-col-status"><span class="es-status-badge ' + statusClass + '">' + escapeHtml(booking.status_label) + '</span></td>';
        row += '<td class="es-col-payment"><span class="es-payment-badge ' + paymentClass + '">' + escapeHtml(booking.payment_label) + '</span></td>';
        row += '<td class="es-col-date">' + formatDate(booking.created_at) + '</td>';
        row += '<td class="es-col-actions">' + renderRowActions(booking) + '</td>';
        row += '</tr>';
        
        return row;
    }
    
    /**
     * Render row actions
     */
    function renderRowActions(booking) {
        var actions = '<div class="es-row-actions">';
        actions += '<button type="button" class="es-row-action es-action-view" title="' + ensembleBookingEngine.strings.view + '"><span class="dashicons dashicons-visibility"></span></button>';
        
        // Status change actions based on current status
        if (booking.status === 'pending') {
            actions += '<button type="button" class="es-row-action es-action-confirm" title="' + ensembleBookingEngine.strings.confirm + '"><span class="dashicons dashicons-yes-alt"></span></button>';
            actions += '<button type="button" class="es-row-action es-action-checkin" title="' + ensembleBookingEngine.strings.checkin + '"><span class="dashicons dashicons-yes"></span></button>';
        } else if (booking.status === 'confirmed') {
            actions += '<button type="button" class="es-row-action es-action-checkin" title="' + ensembleBookingEngine.strings.checkin + '"><span class="dashicons dashicons-yes"></span></button>';
        }
        
        // Cancel action for pending or confirmed
        if (booking.status === 'pending' || booking.status === 'confirmed') {
            actions += '<button type="button" class="es-row-action es-action-cancel" title="' + ensembleBookingEngine.strings.cancel + '"><span class="dashicons dashicons-dismiss"></span></button>';
        }
        
        actions += '<button type="button" class="es-row-action es-action-delete" title="' + ensembleBookingEngine.strings.delete + '"><span class="dashicons dashicons-trash"></span></button>';
        actions += '</div>';
        
        return actions;
    }
    
    /**
     * Update stats cards
     */
    function updateStats() {
        var total = state.bookings.length;
        var confirmed = state.bookings.filter(function(b) { return b.status === 'confirmed'; }).length;
        var pending = state.bookings.filter(function(b) { return b.status === 'pending'; }).length;
        var checkedIn = state.bookings.filter(function(b) { return b.status === 'checked_in'; }).length;
        
        $('#stat-total').text(total);
        $('#stat-confirmed').text(confirmed);
        $('#stat-pending').text(pending);
        $('#stat-checkedin').text(checkedIn);
    }
    
    /**
     * Update pagination
     */
    function updatePagination() {
        var totalPages = Math.ceil(state.bookings.length / state.perPage);
        
        if (totalPages <= 1) {
            $('#es-pagination').hide();
            return;
        }
        
        $('#es-pagination').show();
        $('.es-pagination-info').text('Page ' + state.currentPage + ' of ' + totalPages);
        $('.es-page-prev').prop('disabled', state.currentPage <= 1);
        $('.es-page-next').prop('disabled', state.currentPage >= totalPages);
    }
    
    /**
     * View booking details
     */
    function viewBooking(bookingId) {
        $.ajax({
            url: ensembleBookingEngine.ajaxUrl,
            type: 'POST',
            data: {
                action: 'es_get_booking',
                nonce: ensembleBookingEngine.nonce,
                booking_id: bookingId
            },
            success: function(response) {
                if (response.success) {
                    state.selectedBooking = response.data.booking;
                    renderDetailModal(response.data.booking);
                    $('#es-detail-modal').show();
                }
            }
        });
    }
    
    /**
     * Render detail modal content
     */
    function renderDetailModal(booking) {
        var html = '<div class="es-detail-grid">';
        
        // Left column
        html += '<div class="es-detail-column">';
        
        // Booking info
        html += '<div class="es-detail-section">';
        html += '<h4>Booking Information</h4>';
        html += '<div class="es-detail-row"><span class="es-detail-label">Code</span><span class="es-detail-value es-booking-code">' + escapeHtml(booking.confirmation_code) + '</span></div>';
        html += '<div class="es-detail-row"><span class="es-detail-label">Type</span><span class="es-detail-value">' + escapeHtml(booking.type_label) + '</span></div>';
        html += '<div class="es-detail-row"><span class="es-detail-label">Status</span><span class="es-detail-value"><span class="es-status-badge es-status-' + booking.status + '">' + escapeHtml(booking.status_label) + '</span></span></div>';
        html += '<div class="es-detail-row"><span class="es-detail-label">Guests</span><span class="es-detail-value">' + booking.guests + '</span></div>';
        if (booking.element_label) {
            html += '<div class="es-detail-row"><span class="es-detail-label">Seat/Table</span><span class="es-detail-value">' + escapeHtml(booking.element_label) + '</span></div>';
        }
        html += '</div>';
        
        // Customer info
        html += '<div class="es-detail-section">';
        html += '<h4>Customer</h4>';
        html += '<div class="es-detail-row"><span class="es-detail-label">Name</span><span class="es-detail-value">' + escapeHtml(booking.customer_name) + '</span></div>';
        html += '<div class="es-detail-row"><span class="es-detail-label">Email</span><span class="es-detail-value">' + escapeHtml(booking.customer_email) + '</span></div>';
        if (booking.customer_phone) {
            html += '<div class="es-detail-row"><span class="es-detail-label">Phone</span><span class="es-detail-value">' + escapeHtml(booking.customer_phone) + '</span></div>';
        }
        html += '</div>';
        
        html += '</div>';
        
        // Right column
        html += '<div class="es-detail-column">';
        
        // Event info
        html += '<div class="es-detail-section">';
        html += '<h4>Event</h4>';
        html += '<div class="es-detail-row"><span class="es-detail-label">Event</span><span class="es-detail-value">' + escapeHtml(booking.event_title) + '</span></div>';
        if (booking.event_date) {
            html += '<div class="es-detail-row"><span class="es-detail-label">Date</span><span class="es-detail-value">' + escapeHtml(booking.event_date) + '</span></div>';
        }
        html += '</div>';
        
        // Payment info (if ticket)
        if (booking.booking_type === 'ticket') {
            html += '<div class="es-detail-section">';
            html += '<h4>Payment</h4>';
            html += '<div class="es-detail-row"><span class="es-detail-label">Amount</span><span class="es-detail-value">' + formatPrice(booking.price, booking.currency) + '</span></div>';
            html += '<div class="es-detail-row"><span class="es-detail-label">Status</span><span class="es-detail-value"><span class="es-payment-badge es-payment-' + booking.payment_status + '">' + escapeHtml(booking.payment_label) + '</span></span></div>';
            html += '</div>';
        }
        
        // QR Code
        if (booking.qr_code) {
            html += '<div class="es-detail-section">';
            html += '<h4>QR Code</h4>';
            html += '<div class="es-detail-qr"><img src="' + escapeHtml(booking.qr_code) + '" alt="QR Code"></div>';
            html += '</div>';
        }
        
        html += '</div>';
        html += '</div>';
        
        // Notes
        if (booking.customer_notes || booking.internal_notes) {
            html += '<div class="es-detail-section">';
            html += '<h4>Notes</h4>';
            if (booking.customer_notes) {
                html += '<div class="es-detail-row"><span class="es-detail-label">Customer</span><span class="es-detail-value">' + escapeHtml(booking.customer_notes) + '</span></div>';
            }
            if (booking.internal_notes) {
                html += '<div class="es-detail-row"><span class="es-detail-label">Internal</span><span class="es-detail-value">' + escapeHtml(booking.internal_notes) + '</span></div>';
            }
            html += '</div>';
        }
        
        $('#es-detail-content').html(html);
        
        // Update button visibility based on status
        // Confirm button: only show for pending
        if (booking.status === 'pending') {
            $('#es-detail-confirm').show();
        } else {
            $('#es-detail-confirm').hide();
        }
        
        // Cancel button: show for pending and confirmed
        if (booking.status === 'pending' || booking.status === 'confirmed') {
            $('#es-detail-cancel').show();
        } else {
            $('#es-detail-cancel').hide();
        }
        
        // Checkin button: show for pending and confirmed
        if (booking.status === 'checked_in' || booking.status === 'cancelled') {
            $('#es-detail-checkin').hide();
        } else {
            $('#es-detail-checkin').show();
        }
    }
    
    /**
     * Open booking modal for add/edit
     */
    function openBookingModal(booking) {
        $('#es-booking-form')[0].reset();
        
        if (booking) {
            $('#es-modal-title').text('Edit Booking');
            $('#booking_id').val(booking.id);
            $('#booking_event_id').val(booking.event_id);
            $('#booking_type').val(booking.booking_type);
            $('#booking_status').val(booking.status);
            $('#customer_name').val(booking.customer_name);
            $('#customer_email').val(booking.customer_email);
            $('#customer_phone').val(booking.customer_phone);
            $('#booking_guests').val(booking.guests);
            $('#internal_notes').val(booking.internal_notes);
        } else {
            $('#es-modal-title').text('Add Booking');
            $('#booking_id').val('');
        }
        
        $('#es-booking-modal').show();
        $('#customer_name').focus();
    }
    
    /**
     * Save booking
     */
    function saveBooking() {
        var $form = $('#es-booking-form');
        var $btn = $('#es-save-booking');
        
        // Validate
        if (!$form[0].checkValidity()) {
            $form[0].reportValidity();
            return;
        }
        
        $btn.prop('disabled', true).text('Saving...');
        
        var data = {
            action: 'es_create_booking',
            nonce: ensembleBookingEngine.nonce,
            event_id: $('#booking_event_id').val(),
            booking_type: $('#booking_type').val(),
            status: $('#booking_status').val(),
            customer_name: $('#customer_name').val(),
            customer_email: $('#customer_email').val(),
            customer_phone: $('#customer_phone').val(),
            guests: $('#booking_guests').val(),
            internal_notes: $('#internal_notes').val()
        };
        
        var bookingId = $('#booking_id').val();
        if (bookingId) {
            data.action = 'es_update_booking';
            data.booking_id = bookingId;
        }
        
        $.ajax({
            url: ensembleBookingEngine.ajaxUrl,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    closeModals();
                    loadBookings();
                    showNotice(response.data.message, 'success');
                } else {
                    showNotice(response.data.message, 'error');
                }
            },
            error: function() {
                showNotice(ensembleBookingEngine.strings.error, 'error');
            },
            complete: function() {
                $btn.prop('disabled', false).text('Save Booking');
            }
        });
    }
    
    /**
     * Check in booking
     */
    function checkinBooking(bookingId) {
        $.ajax({
            url: ensembleBookingEngine.ajaxUrl,
            type: 'POST',
            data: {
                action: 'es_checkin_booking',
                nonce: ensembleBookingEngine.nonce,
                booking_id: bookingId
            },
            success: function(response) {
                if (response.success) {
                    loadBookings();
                    closeModals();
                    showNotice(ensembleBookingEngine.strings.checkedIn, 'success');
                } else {
                    showNotice(response.data.message, 'error');
                }
            }
        });
    }
    
    /**
     * Update booking status
     */
    function updateStatus(bookingId, newStatus) {
        $.ajax({
            url: ensembleBookingEngine.ajaxUrl,
            type: 'POST',
            data: {
                action: 'es_update_booking_status',
                nonce: ensembleBookingEngine.nonce,
                booking_id: bookingId,
                status: newStatus
            },
            success: function(response) {
                if (response.success) {
                    loadBookings();
                    showNotice(ensembleBookingEngine.strings.statusUpdated, 'success');
                }
            }
        });
    }
    
    /**
     * Delete booking
     */
    function deleteBooking(bookingId) {
        $.ajax({
            url: ensembleBookingEngine.ajaxUrl,
            type: 'POST',
            data: {
                action: 'es_delete_booking',
                nonce: ensembleBookingEngine.nonce,
                booking_id: bookingId
            },
            success: function(response) {
                if (response.success) {
                    loadBookings();
                    showNotice(ensembleBookingEngine.strings.deleted, 'success');
                } else {
                    showNotice(response.data.message, 'error');
                }
            }
        });
    }
    
    /**
     * Resend confirmation email
     */
    function resendEmail(bookingId) {
        var $btn = $('#es-resend-email');
        $btn.prop('disabled', true);
        
        $.ajax({
            url: ensembleBookingEngine.ajaxUrl,
            type: 'POST',
            data: {
                action: 'es_resend_booking_email',
                nonce: ensembleBookingEngine.nonce,
                booking_id: bookingId
            },
            success: function(response) {
                if (response.success) {
                    showNotice('Email sent!', 'success');
                } else {
                    showNotice(response.data.message, 'error');
                }
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    }
    
    /**
     * Export bookings
     */
    function exportBookings(format) {
        var params = new URLSearchParams({
            action: 'es_export_bookings',
            nonce: ensembleBookingEngine.nonce,
            format: format,
            event_id: state.filters.event_id,
            status: state.filters.status,
            type: state.filters.booking_type
        });
        
        window.location.href = ensembleBookingEngine.ajaxUrl + '?' + params.toString();
    }
    
    /**
     * Close all modals
     */
    function closeModals() {
        $('.es-modal').hide();
        state.selectedBooking = null;
    }
    
    /**
     * Show loading state
     */
    function showLoading() {
        $('#es-bookings-tbody').html(
            '<tr class="es-loading-row"><td colspan="10"><div class="es-loading"><span class="spinner is-active"></span>' + 
            ensembleBookingEngine.strings.loading + '</div></td></tr>'
        );
    }
    
    /**
     * Show error message
     */
    function showError(message) {
        $('#es-bookings-tbody').html(
            '<tr><td colspan="10" style="text-align:center;color:var(--es-danger);">' + escapeHtml(message) + '</td></tr>'
        );
    }
    
    /**
     * Show notice
     */
    function showNotice(message, type) {
        // Use WordPress admin notice or custom toast
        var $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + escapeHtml(message) + '</p></div>');
        $('.es-manager-header').after($notice);
        
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
    }
    
    /**
     * Format date
     */
    function formatDate(dateStr) {
        if (!dateStr) return '-';
        var date = new Date(dateStr);
        return date.toLocaleDateString();
    }
    
    /**
     * Format price
     */
    function formatPrice(price, currency) {
        if (!price || price === '0.00') return '-';
        return new Intl.NumberFormat('de-DE', {
            style: 'currency',
            currency: currency || 'EUR'
        }).format(price);
    }
    
    /**
     * Escape HTML
     */
    function escapeHtml(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
    
    // =========================================
    // WIZARD INTEGRATION
    // =========================================
    
    /**
     * Booking Card Module for Event Wizard
     */
    var BookingWizard = {
        
        init: function() {
            // Only initialize if we're in the wizard
            if (!$('.es-booking-mode-pills').length) {
                return;
            }
            
            this.bindEvents();
            this.updateVisibility();
        },
        
        bindEvents: function() {
            var self = this;
            
            // Booking mode change
            $(document).on('change', 'input[name="booking_mode"]', function() {
                self.updateVisibility();
            });
            
            // Reservation types change
            $(document).on('change', 'input[name="reservation_types[]"]', function() {
                self.updateCapacityFields();
            });
            
            // Event load handler (from wizard)
            $(document).on('es-wizard-event-loaded', function(e, data) {
                self.loadEventData(data);
            });
        },
        
        updateVisibility: function() {
            var mode = $('input[name="booking_mode"]:checked').val();
            
            // Show/hide reservation options
            if (mode === 'reservation' || mode === 'both') {
                $('#es-booking-reservation-options').slideDown(200);
                this.updateCapacityFields();
            } else {
                $('#es-booking-reservation-options').slideUp(200);
            }
            
            // Show/hide ticket options
            if (mode === 'ticket' || mode === 'both') {
                $('#es-booking-ticket-options').slideDown(200);
            } else {
                $('#es-booking-ticket-options').slideUp(200);
            }
        },
        
        updateCapacityFields: function() {
            var types = [];
            $('input[name="reservation_types[]"]:checked').each(function() {
                types.push($(this).val());
            });
            
            $('#es-cap-guestlist').toggle(types.indexOf('guestlist') !== -1);
            $('#es-cap-table').toggle(types.indexOf('table') !== -1);
            $('#es-cap-vip').toggle(types.indexOf('vip') !== -1);
        },
        
        loadEventData: function(data) {
            if (!data) return;
            
            // Set booking mode
            var mode = data.booking_mode || 'none';
            $('input[name="booking_mode"][value="' + mode + '"]').prop('checked', true);
            
            // Set reservation types
            $('input[name="reservation_types[]"]').prop('checked', false);
            var types = data.reservation_types || ['guestlist'];
            if (Array.isArray(types)) {
                types.forEach(function(type) {
                    $('input[name="reservation_types[]"][value="' + type + '"]').prop('checked', true);
                });
            }
            
            // Set capacities
            $('input[name="reservation_capacity_guestlist"]').val(data.reservation_capacity_guestlist || '');
            $('input[name="reservation_capacity_table"]').val(data.reservation_capacity_table || '');
            $('input[name="reservation_capacity_vip"]').val(data.reservation_capacity_vip || '');
            $('input[name="reservation_capacity"]').val(data.reservation_capacity || '');
            $('input[name="reservation_max_guests"]').val(data.reservation_max_guests || 10);
            $('input[name="reservation_deadline_hours"]').val(data.reservation_deadline_hours || 24);
            
            // Auto confirm
            $('input[name="reservation_auto_confirm"]').prop('checked', data.reservation_auto_confirm == '1');
            
            // Ticket mode
            var ticketMode = data.reservation_ticket_mode || 'none';
            $('input[name="reservation_ticket_mode"][value="' + ticketMode + '"]').prop('checked', true);
            
            // Update visibility
            this.updateVisibility();
            
            // Show stats if event exists
            if (data.id) {
                $('.es-booking-stats-row').show();
                $('#booking-manage-link').attr('href', 
                    ensembleBookingEngine.adminUrl + 'admin.php?page=ensemble-bookings&event_id=' + data.id
                );
                this.loadStats(data.id);
            } else {
                $('.es-booking-stats-row').hide();
            }
        },
        
        loadStats: function(eventId) {
            $.ajax({
                url: ensembleBookingEngine.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'es_get_booking_stats',
                    nonce: typeof ensembleWizard !== 'undefined' ? ensembleWizard.nonce : ensembleBookingEngine.nonce,
                    event_id: eventId
                },
                success: function(response) {
                    if (response.success) {
                        $('#booking-stat-total').text(response.data.total || 0);
                        $('#booking-stat-confirmed').text(response.data.confirmed || 0);
                        $('#booking-stat-pending').text(response.data.pending || 0);
                        $('#booking-stat-checkedin').text(response.data.checked_in || 0);
                    }
                }
            });
        }
    };
    
    // Initialize wizard module when ready
    $(document).ready(function() {
        BookingWizard.init();
    });
    
})(jQuery);
