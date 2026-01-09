/**
 * Ensemble Advanced Features
 * - Exception Management (Skip Dates)
 * - Calendar Drag & Drop
 * - Export Functions (iCal, PDF)
 * - Delete from Calendar
 * 
 * @package Ensemble
 */

(function($) {
    'use strict';
    
    // ===================================
    // 1. EXCEPTION MANAGEMENT (Skip Dates)
    // ===================================
    
    const ExceptionManager = {
        
        init: function() {
            this.bindEvents();
            this.loadExceptions();
        },
        
        bindEvents: function() {
            // Add exception button
            $(document).on('click', '#es-add-exception-btn', this.addException.bind(this));
            
            // Delete exception button
            $(document).on('click', '.es-delete-exception', this.deleteException.bind(this));
            
            // Load exceptions when event is loaded
            $(document).on('eventLoaded', this.loadExceptions.bind(this));
        },
        
        addException: function() {
            const eventId = $('#es-event-id').val();
            const date = $('#es-exception-date').val();
            const reason = $('#es-exception-reason').val() || '';
            
            if (!eventId || !date) {
                alert('Please select a date');
                return;
            }
            
            $.ajax({
                url: ensembleAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'es_add_recurring_exception',
                    nonce: ensembleAjax.nonce,
                    event_id: eventId,
                    date: date,
                    reason: reason
                },
                success: (response) => {
                    if (response.success) {
                        this.renderException(date, reason);
                        $('#es-exception-date').val('');
                        $('#es-exception-reason').val('');
                        
                        // Refresh preview if available
                        if (typeof window.EnsembleRecurring !== 'undefined') {
                            window.EnsembleRecurring.previewInstances();
                        }
                    } else {
                        alert(response.data.message || 'Failed to add exception');
                    }
                },
                error: function() {
                    alert('Failed to add exception');
                }
            });
        },
        
        deleteException: function(e) {
            const eventId = $('#es-event-id').val();
            const date = $(e.currentTarget).data('date');
            
            if (!confirm('Remove this exception? The event will appear on this date again.')) {
                return;
            }
            
            $.ajax({
                url: ensembleAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'es_remove_recurring_exception',
                    nonce: ensembleAjax.nonce,
                    event_id: eventId,
                    date: date
                },
                success: (response) => {
                    if (response.success) {
                        $(e.currentTarget).closest('.es-exception-item').fadeOut(300, function() {
                            $(this).remove();
                        });
                        
                        // Refresh preview
                        if (typeof window.EnsembleRecurring !== 'undefined') {
                            window.EnsembleRecurring.previewInstances();
                        }
                    }
                }
            });
        },
        
        loadExceptions: function() {
            const eventId = $('#es-event-id').val();
            if (!eventId) return;
            
            $.ajax({
                url: ensembleAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'es_get_recurring_exceptions',
                    nonce: ensembleAjax.nonce,
                    event_id: eventId
                },
                success: (response) => {
                    if (response.success && response.data.exceptions) {
                        $('#es-exceptions-list').empty();
                        Object.entries(response.data.exceptions).forEach(([date, reason]) => {
                            this.renderException(date, reason);
                        });
                    }
                }
            });
        },
        
        renderException: function(date, reason) {
            const formattedDate = new Date(date).toLocaleDateString('de-DE', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            
            const html = `
                <div class="es-exception-item" data-date="${date}">
                    <div class="es-exception-date">
                        <span class="es-exception-icon">🗓️</span>
                        <strong>${formattedDate}</strong>
                    </div>
                    ${reason ? `<div class="es-exception-reason">${this.escapeHtml(reason)}</div>` : ''}
                    <button type="button" class="button es-delete-exception" data-date="${date}">
                        ✕ Remove
                    </button>
                </div>
            `;
            
            $('#es-exceptions-list').append(html);
        },
        
        escapeHtml: function(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };
    
    // ===================================
    // 2. CALENDAR DRAG & DROP
    // ===================================
    
    const CalendarDragDrop = {
        
        init: function() {
            if (typeof $.fn.draggable === 'undefined' || typeof $.fn.droppable === 'undefined') {
                console.log('jQuery UI not loaded, skipping drag & drop');
                return;
            }
            
            this.initDraggable();
            this.initDroppable();
        },
        
        initDraggable: function() {
            $('.es-calendar-event').draggable({
                revert: 'invalid',
                helper: 'clone',
                cursor: 'move',
                zIndex: 10000,
                start: function(event, ui) {
                    $(this).css('opacity', '0.5');
                },
                stop: function(event, ui) {
                    $(this).css('opacity', '1');
                }
            });
        },
        
        initDroppable: function() {
            $('.es-calendar-cell').droppable({
                accept: '.es-calendar-event',
                hoverClass: 'es-drop-hover',
                drop: (event, ui) => {
                    const eventId = ui.draggable.data('event-id');
                    const eventTitle = ui.draggable.find('.es-event-title').text();
                    const newDate = $(event.target).data('date');
                    const isVirtual = ui.draggable.hasClass('es-virtual-event');
                    
                    this.handleDrop(eventId, eventTitle, newDate, isVirtual);
                }
            });
        },
        
        handleDrop: function(eventId, eventTitle, newDate, isVirtual) {
            if (isVirtual) {
                this.showVirtualMoveDialog(eventId, eventTitle, newDate);
            } else {
                this.moveEvent(eventId, newDate);
            }
        },
        
        showVirtualMoveDialog: function(eventId, eventTitle, newDate) {
            const formattedDate = new Date(newDate).toLocaleDateString('de-DE', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            
            const dialog = $(`
                <div class="es-dialog-overlay">
                    <div class="es-dialog">
                        <h3>Move Recurring Event Instance</h3>
                        <p><strong>${this.escapeHtml(eventTitle)}</strong></p>
                        <p>This is a recurring event instance. Moving it will create a separate event on <strong>${formattedDate}</strong>.</p>
                        <div class="es-dialog-actions">
                            <button type="button" class="button button-primary" id="es-confirm-move">Move this event</button>
                            <button type="button" class="button" id="es-cancel-move">Cancel</button>
                        </div>
                    </div>
                </div>
            `);
            
            $('body').append(dialog);
            
            $('#es-confirm-move').on('click', () => {
                this.convertAndMove(eventId, newDate);
                dialog.remove();
            });
            
            $('#es-cancel-move').on('click', () => {
                dialog.remove();
            });
        },
        
        convertAndMove: function(virtualId, newDate) {
            $.ajax({
                url: ensembleAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'es_convert_virtual_to_real',
                    nonce: ensembleAjax.nonce,
                    virtual_id: virtualId,
                    modifications: {
                        event_date: newDate
                    }
                },
                success: (response) => {
                    if (response.success) {
                        // Reload calendar
                        if (typeof window.loadCalendar === 'function') {
                            window.loadCalendar();
                        } else {
                            location.reload();
                        }
                    }
                }
            });
        },
        
        moveEvent: function(eventId, newDate) {
            $.ajax({
                url: ensembleAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'es_move_event',
                    nonce: ensembleAjax.nonce,
                    event_id: eventId,
                    new_date: newDate
                },
                success: (response) => {
                    if (response.success) {
                        // Reload calendar
                        if (typeof window.loadCalendar === 'function') {
                            window.loadCalendar();
                        } else {
                            location.reload();
                        }
                    }
                }
            });
        },
        
        escapeHtml: function(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };
    
    // ===================================
    // 3. EXPORT FUNCTIONS
    // ===================================
    
    const ExportManager = {
        
        init: function() {
            this.bindEvents();
        },
        
        bindEvents: function() {
            $(document).on('click', '#es-export-ical', this.exportICal.bind(this));
            $(document).on('click', '#es-export-pdf', this.exportPDF.bind(this));
        },
        
        exportICal: function() {
            const currentView = $('.es-calendar-views .button-primary').data('view') || 'month';
            const currentDate = $('#es-current-date').val() || new Date().toISOString().split('T')[0];
            
            window.location.href = ensembleAjax.ajaxurl + 
                '?action=es_export_ical' +
                '&nonce=' + ensembleAjax.nonce +
                '&view=' + currentView +
                '&date=' + currentDate;
        },
        
        exportPDF: function() {
            alert('PDF Export is coming soon!');
            // TODO: Implement PDF export
        }
    };
    
    // ===================================
    // 4. DELETE FROM CALENDAR
    // ===================================
    
    const CalendarDelete = {
        
        init: function() {
            this.bindEvents();
        },
        
        bindEvents: function() {
            $(document).on('click', '.es-calendar-event', this.showEventMenu.bind(this));
            $(document).on('click', '.es-calendar-delete-btn', this.deleteEvent.bind(this));
        },
        
        showEventMenu: function(e) {
            // Only on right-click or if Ctrl is held
            if (e.ctrlKey || e.metaKey) {
                e.preventDefault();
                e.stopPropagation();
                
                const eventId = $(e.currentTarget).data('event-id');
                const eventTitle = $(e.currentTarget).find('.es-event-title').text();
                const isVirtual = $(e.currentTarget).hasClass('es-virtual-event');
                
                this.showContextMenu(e, eventId, eventTitle, isVirtual);
            }
        },
        
        showContextMenu: function(e, eventId, eventTitle, isVirtual) {
            // Remove existing menu
            $('.es-context-menu').remove();
            
            const menu = $(`
                <div class="es-context-menu" style="position: absolute; top: ${e.pageY}px; left: ${e.pageX}px;">
                    <button type="button" class="es-calendar-edit-btn" data-event-id="${eventId}">
                        ${ES_ICONS.edit} Edit
                    </button>
                    <button type="button" class="es-calendar-delete-btn" data-event-id="${eventId}" data-is-virtual="${isVirtual}">
                        ${ES_ICONS.trash} Delete
                    </button>
                </div>
            `);
            
            $('body').append(menu);
            
            // Close menu on outside click
            setTimeout(() => {
                $(document).one('click', () => {
                    menu.remove();
                });
            }, 100);
            
            // Edit handler
            menu.find('.es-calendar-edit-btn').on('click', (e) => {
                e.stopPropagation();
                menu.remove();
                window.location.href = '?page=ensemble-wizard&edit=' + eventId;
            });
        },
        
        deleteEvent: function(e) {
            e.stopPropagation();
            
            const eventId = $(e.currentTarget).data('event-id');
            const isVirtual = $(e.currentTarget).data('is-virtual');
            
            if (!confirm('Delete this event?')) {
                return;
            }
            
            const action = isVirtual ? 'es_delete_virtual_event' : 'es_delete_event';
            const data = {
                action: action,
                nonce: ensembleAjax.nonce
            };
            
            if (isVirtual) {
                data.virtual_id = eventId;
            } else {
                data.event_id = eventId;
            }
            
            $.ajax({
                url: ensembleAjax.ajaxurl,
                type: 'POST',
                data: data,
                success: (response) => {
                    if (response.success) {
                        // Reload calendar
                        if (typeof window.loadCalendar === 'function') {
                            window.loadCalendar();
                        } else {
                            location.reload();
                        }
                    }
                }
            });
            
            $('.es-context-menu').remove();
        }
    };
    
    // ===================================
    // INITIALIZE ON DOCUMENT READY
    // ===================================
    
    $(document).ready(function() {
        
        // Initialize Exception Manager if on wizard page
        if ($('#es-event-form').length) {
            ExceptionManager.init();
        }
        
        // Initialize Drag & Drop if on calendar page
        if ($('.es-calendar-month, .es-calendar-week').length) {
            // Wait for calendar to be fully loaded
            setTimeout(() => {
                CalendarDragDrop.init();
            }, 500);
        }
        
        // Initialize Export Manager if on calendar page
        if ($('.es-calendar-toolbar').length) {
            ExportManager.init();
        }
        
        // Initialize Calendar Delete
        if ($('.es-calendar-month, .es-calendar-week').length) {
            CalendarDelete.init();
        }
    });
    
})(jQuery);
