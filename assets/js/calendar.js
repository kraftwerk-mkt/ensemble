/**
 * Ensemble Calendar JavaScript
 * 
 * @package Ensemble
 */

(function($) {
    'use strict';
    
    // Filter state
    let activeFilters = {
        search: '',
        category: '',
        location: '',
        artist: ''
    };
    
    // Initialize
    $(document).ready(function() {
        initCalendarEvents();
        initDragAndDrop();
        initModal();
        initFilters();
    });
    
    /**
     * Initialize filter system
     */
    function initFilters() {
        // Search input with debounce
        let searchTimeout;
        $('#es-calendar-search').on('input', function() {
            clearTimeout(searchTimeout);
            const searchValue = $(this).val().trim();
            
            searchTimeout = setTimeout(function() {
                activeFilters.search = searchValue;
                updateActiveFilters();
                applyFilters();
            }, 300);
        });
        
        // Filter selects
        $('.es-filter-select').on('change', function() {
            const filterType = $(this).data('filter');
            const filterValue = $(this).val();
            activeFilters[filterType] = filterValue;
            updateActiveFilters();
            applyFilters();
        });
        
        // Clear all filters
        $('#es-clear-filters').on('click', function() {
            clearAllFilters();
        });
        
        // Remove individual filter chip
        $(document).on('click', '.es-filter-chip-remove', function() {
            const filterType = $(this).closest('.es-filter-chip').data('filter');
            removeFilter(filterType);
        });
        
        // Category legend click - quick filter
        $(document).on('click', '.es-legend-item', function() {
            const categoryId = $(this).data('category-id');
            const $item = $(this);
            
            // Toggle active state
            if ($item.hasClass('es-legend-active')) {
                // Deactivate - clear category filter
                $item.removeClass('es-legend-active');
                activeFilters.category = '';
                $('#es-filter-category').val('');
            } else {
                // Activate - set category filter
                $('.es-legend-item').removeClass('es-legend-active');
                $item.addClass('es-legend-active');
                activeFilters.category = categoryId.toString();
                $('#es-filter-category').val(categoryId);
            }
            
            updateActiveFilters();
            applyFilters();
        });
    }
    
    /**
     * Apply filters to calendar
     */
    function applyFilters() {
        // Show all events first
        $('.es-calendar-event').show().closest('.es-calendar-cell').removeClass('es-filtered-empty');
        
        let hasActiveFilters = false;
        
        // Apply search filter
        if (activeFilters.search) {
            hasActiveFilters = true;
            const searchLower = activeFilters.search.toLowerCase();
            $('.es-calendar-event').each(function() {
                const title = $(this).find('.es-event-title').text().toLowerCase();
                const description = $(this).data('description') || '';
                
                if (title.indexOf(searchLower) === -1 && description.toLowerCase().indexOf(searchLower) === -1) {
                    $(this).hide();
                }
            });
        }
        
        // Apply category filter
        if (activeFilters.category) {
            hasActiveFilters = true;
            $('.es-calendar-event').each(function() {
                const categories = $(this).data('categories') || [];
                if (!categories.includes(parseInt(activeFilters.category))) {
                    $(this).hide();
                }
            });
        }
        
        // Apply location filter
        if (activeFilters.location) {
            hasActiveFilters = true;
            $('.es-calendar-event').each(function() {
                const location = $(this).data('location-id');
                if (location != activeFilters.location) {
                    $(this).hide();
                }
            });
        }
        
        // Apply artist filter
        if (activeFilters.artist) {
            hasActiveFilters = true;
            $('.es-calendar-event').each(function() {
                const artists = $(this).data('artists') || [];
                if (!artists.includes(parseInt(activeFilters.artist))) {
                    $(this).hide();
                }
            });
        }
        
        // Update cells: mark as filtered-empty if no visible events
        $('.es-calendar-cell').each(function() {
            const visibleEvents = $(this).find('.es-calendar-event:visible').length;
            if (visibleEvents === 0 && hasActiveFilters) {
                $(this).addClass('es-filtered-empty');
            }
        });
        
        // Sync legend highlighting with active category filter
        $('.es-legend-item').removeClass('es-legend-active');
        if (activeFilters.category) {
            $(`.es-legend-item[data-category-id="${activeFilters.category}"]`).addClass('es-legend-active');
        }
        
        // Show/hide "no results" message
        updateNoResultsMessage(hasActiveFilters);
    }
    
    /**
     * Update active filter chips
     */
    function updateActiveFilters() {
        const $container = $('#es-active-filters');
        $container.empty();
        
        let hasFilters = false;
        
        // Search chip
        if (activeFilters.search) {
            hasFilters = true;
            $container.append(createFilterChip('search', 'Search: ' + activeFilters.search));
        }
        
        // Category chip
        if (activeFilters.category) {
            hasFilters = true;
            const categoryName = $('#es-filter-category option:selected').text();
            $container.append(createFilterChip('category', categoryName));
        }
        
        // Location chip
        if (activeFilters.location) {
            hasFilters = true;
            const locationName = $('#es-filter-location option:selected').text();
            $container.append(createFilterChip('location', locationName));
        }
        
        // Artist chip
        if (activeFilters.artist) {
            hasFilters = true;
            const artistName = $('#es-filter-artist option:selected').text();
            $container.append(createFilterChip('artist', artistName));
        }
        
        // Show/hide container and clear button
        if (hasFilters) {
            $container.show();
            $('#es-clear-filters').show();
        } else {
            $container.hide();
            $('#es-clear-filters').hide();
        }
    }
    
    /**
     * Create filter chip HTML
     */
    function createFilterChip(type, label) {
        return `
            <div class="es-filter-chip" data-filter="${type}">
                <span class="es-filter-chip-label">${label}</span>
                <button type="button" class="es-filter-chip-remove" aria-label="Remove filter">
                    <span>×</span>
                </button>
            </div>
        `;
    }
    
    /**
     * Remove single filter
     */
    function removeFilter(type) {
        activeFilters[type] = '';
        
        // Reset UI
        if (type === 'search') {
            $('#es-calendar-search').val('');
        } else {
            $(`#es-filter-${type}`).val('');
        }
        
        updateActiveFilters();
        applyFilters();
    }
    
    /**
     * Clear all filters
     */
    function clearAllFilters() {
        activeFilters = {
            search: '',
            category: '',
            location: '',
            artist: ''
        };
        
        // Reset UI
        $('#es-calendar-search').val('');
        $('.es-filter-select').val('');
        
        updateActiveFilters();
        applyFilters();
    }
    
    /**
     * Update no results message
     */
    function updateNoResultsMessage(hasFilters) {
        $('.es-no-results').remove();
        
        if (hasFilters) {
            const visibleEvents = $('.es-calendar-event:visible').length;
            if (visibleEvents === 0) {
                const message = `
                    <div class="es-no-results">
                        <div class="es-no-results-icon">${ES_ICONS.search}</div>
                        <p>No events found matching your filters.</p>
                        <button type="button" class="button" onclick="$('#es-clear-filters').click()">
                            Clear Filters
                        </button>
                    </div>
                `;
                $('.es-calendar-grid, .es-week-view, .es-day-view').append(message);
            }
        }
    }
    
    /**
     * Initialize calendar event handlers
     */
    function initCalendarEvents() {
        // Click on calendar event
        $(document).on('click', '.es-calendar-event', function(e) {
            e.stopPropagation();
            const eventId = $(this).data('event-id');
            showEventDetails(eventId);
        });
        
        // Click on calendar cell (month view)
        $(document).on('click', '.es-calendar-cell', function(e) {
            if ($(e.target).hasClass('es-calendar-event') || $(e.target).closest('.es-calendar-event').length) {
                return; // Event click handler will handle this
            }
            
            const date = $(this).data('date');
            if (date) {
                // Navigate to day view
                const url = ensembleAjax.calendarUrl + '&view=day&date=' + date;
                window.location.href = url;
            }
        });
    }
    
    /**
     * Show event details in modal
     */
    function showEventDetails(eventId) {
        $('#es-modal-body').html('<div class="es-loading">Loading...</div>');
        $('#es-event-modal').fadeIn();
        
        $.ajax({
            url: ensembleAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'es_get_event',
                nonce: ensembleAjax.nonce,
                event_id: eventId
            },
            success: function(response) {
                if (response.success) {
                    renderEventDetails(response.data);
                } else {
                    $('#es-modal-body').html('<div class="es-error">' + response.data.message + '</div>');
                }
            },
            error: function() {
                $('#es-modal-body').html('<div class="es-error">Failed to load event details</div>');
            }
        });
    }
    
    /**
     * Render event details in modal
     */
    function renderEventDetails(event) {
        let html = '<div class="es-modal-event">';
        
        // Title
        html += '<h2>' + escapeHtml(event.title) + '</h2>';
        
        // Date & Time
        if (event.date) {
            html += '<div class="es-modal-meta">';
            html += '<span class="dashicons dashicons-calendar-alt"></span>';
            html += '<strong>Date:</strong> ' + formatDate(event.date);
            if (event.time) {
                html += ' at ' + event.time;
            }
            html += '</div>';
        }
        
        // Location (support both 'location' and 'location_name' for compatibility)
        const locationName = event.location_name || event.location || '';
        if (locationName) {
            html += '<div class="es-modal-meta">';
            html += '<span class="dashicons dashicons-location"></span>';
            html += '<strong>Location:</strong> ' + escapeHtml(locationName);
            html += '</div>';
        }
        
        // Artists
        if (event.artist_names && event.artist_names.length > 0) {
            html += '<div class="es-modal-meta">';
            html += '<span class="dashicons dashicons-admin-users"></span>';
            html += '<strong>Artists:</strong> ' + escapeHtml(event.artist_names.join(', '));
            html += '</div>';
        }
        
        // Price
        if (event.price) {
            html += '<div class="es-modal-meta">';
            html += '<span class="dashicons dashicons-tickets-alt"></span>';
            html += '<strong>Price:</strong> ' + escapeHtml(event.price);
            html += '</div>';
        }
        
        // Description
        if (event.description) {
            html += '<div class="es-modal-description">';
            html += '<h3>Description</h3>';
            html += '<p>' + escapeHtml(event.description) + '</p>';
            html += '</div>';
        }
        
        // Categories
        if (event.categories && event.categories.length > 0) {
            html += '<div class="es-modal-categories">';
            event.categories.forEach(function(cat) {
                html += '<span class="es-category-badge">' + escapeHtml(cat.name) + '</span>';
            });
            html += '</div>';
        }
        
        // Featured Image
        if (event.featured_image) {
            html += '<div class="es-modal-image">';
            html += '<img src="' + event.featured_image + '" alt="' + escapeHtml(event.title) + '">';
            html += '</div>';
        }
        
        // Actions
        html += '<div class="es-modal-actions">';
        html += '<a href="' + ensembleAjax.wizardUrl + '&edit=' + event.id + '" class="button button-primary">Edit Event</a>';
        html += '<button class="button es-modal-close-btn">Close</button>';
        html += '</div>';
        
        html += '</div>';
        
        $('#es-modal-body').html(html);
    }
    
    /**
     * Initialize modal
     */
    function initModal() {
        // Close modal on X click
        $(document).on('click', '.es-modal-close', function() {
            $('#es-event-modal').fadeOut();
        });
        
        // Close modal on close button
        $(document).on('click', '.es-modal-close-btn', function() {
            $('#es-event-modal').fadeOut();
        });
        
        // Close modal on outside click
        $(document).on('click', '#es-event-modal', function(e) {
            if (e.target.id === 'es-event-modal') {
                $('#es-event-modal').fadeOut();
            }
        });
        
        // Close modal on ESC key
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $('#es-event-modal').is(':visible')) {
                $('#es-event-modal').fadeOut();
            }
        });
    }
    
    /**
     * Format date
     */
    function formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString + 'T00:00:00');
        return date.toLocaleDateString('en-US', { 
            weekday: 'long',
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        });
    }
    
    /**
     * Escape HTML
     */
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    /**
     * Initialize Drag and Drop
     */
    let draggedEvent = null;
    let draggedEventData = null;
    
    function initDragAndDrop() {
        // Drag start
        $(document).on('dragstart', '.es-calendar-event[draggable="true"]', function(e) {
            draggedEvent = this;
            
            // Collect event data
            draggedEventData = {
                id: $(this).data('event-id'),
                date: $(this).data('event-date'),
                time: $(this).data('event-time'),
                title: $(this).find('.es-event-title').text().trim(),
                isVirtual: $(this).hasClass('es-virtual-event')
            };
            
            // Visual feedback
            $(this).addClass('es-dragging');
            e.originalEvent.dataTransfer.effectAllowed = 'move';
            e.originalEvent.dataTransfer.setData('text/html', this.innerHTML);
        });
        
        // Drag end
        $(document).on('dragend', '.es-calendar-event', function(e) {
            $(this).removeClass('es-dragging');
            $('.es-calendar-cell, .es-week-day-cell').removeClass('es-drop-target');
        });
        
        // Drag over calendar cell
        $(document).on('dragover', '.es-calendar-cell, .es-week-day-cell', function(e) {
            if (e.preventDefault) {
                e.preventDefault();
            }
            
            e.originalEvent.dataTransfer.dropEffect = 'move';
            $(this).addClass('es-drop-target');
            
            return false;
        });
        
        // Drag leave calendar cell
        $(document).on('dragleave', '.es-calendar-cell, .es-week-day-cell', function(e) {
            $(this).removeClass('es-drop-target');
        });
        
        // Drop on calendar cell
        $(document).on('drop', '.es-calendar-cell, .es-week-day-cell', function(e) {
            if (e.stopPropagation) {
                e.stopPropagation();
            }
            
            $(this).removeClass('es-drop-target');
            
            const newDate = $(this).data('date');
            
            if (!newDate || !draggedEventData) {
                return false;
            }
            
            // Don't move if same date
            if (newDate === draggedEventData.date) {
                return false;
            }
            
            // Show confirmation dialog
            showMoveConfirmation(draggedEventData, newDate);
            
            return false;
        });
    }
    
    /**
     * Show move confirmation dialog
     */
    function showMoveConfirmation(eventData, newDate) {
        const dateFormatted = formatDate(newDate);
        
        // Build message
        let message = '<div class="es-confirm-content">';
        message += '<div class="es-confirm-icon">' + ES_ICONS.calendar + '</div>';
        message += '<h3>' + ensembleL10n.confirmMove + '</h3>';
        message += '<p class="es-event-title-box">' + escapeHtml(eventData.title) + '</p>';
        message += '<p class="es-date-change">';
        message += '<span class="es-arrow">→</span> ' + dateFormatted;
        message += '</p>';
        
        if (eventData.isVirtual) {
            message += '<p class="es-virtual-notice">';
            message += '<span class="es-notice-icon">ℹ️</span> ';
            message += ensembleL10n.virtualEventNotice;
            message += '</p>';
        }
        
        message += '</div>';
        
        // Create dialog
        const dialog = $('<div class="es-confirm-dialog">' + message + '</div>');
        const overlay = $('<div class="es-confirm-overlay"></div>');
        
        // Buttons
        const buttonContainer = $('<div class="es-confirm-buttons"></div>');
        const cancelBtn = $('<button class="es-btn es-btn-cancel">' + ensembleL10n.cancel + '</button>');
        const confirmBtn = $('<button class="es-btn es-btn-confirm">' + ensembleL10n.confirm + '</button>');
        
        buttonContainer.append(cancelBtn).append(confirmBtn);
        dialog.append(buttonContainer);
        
        // Add to DOM
        $('body').append(overlay).append(dialog);
        
        // Animate in
        setTimeout(function() {
            overlay.addClass('es-active');
            dialog.addClass('es-active');
        }, 10);
        
        // Cancel
        cancelBtn.on('click', function() {
            closeConfirmDialog(overlay, dialog);
        });
        
        overlay.on('click', function() {
            closeConfirmDialog(overlay, dialog);
        });
        
        // Confirm
        confirmBtn.on('click', function() {
            confirmBtn.prop('disabled', true).html('<span class="spinner is-active"></span>');
            moveEventToDate(eventData.id, newDate, eventData.time, overlay, dialog);
        });
    }
    
    /**
     * Close confirmation dialog
     */
    function closeConfirmDialog(overlay, dialog) {
        overlay.removeClass('es-active');
        dialog.removeClass('es-active');
        
        setTimeout(function() {
            overlay.remove();
            dialog.remove();
        }, 300);
    }
    
    /**
     * Move event to new date via AJAX
     */
    function moveEventToDate(eventId, newDate, eventTime, overlay, dialog) {
        $.ajax({
            url: ensembleAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'es_move_event',
                nonce: ensembleAjax.nonce,
                event_id: eventId,
                new_date: newDate,
                new_time: parseTime(eventTime)
            },
            success: function(response) {
                if (response.success) {
                    showNotification(response.data.message, 'success');
                    closeConfirmDialog(overlay, dialog);
                    
                    // Reload calendar after short delay
                    setTimeout(function() {
                        location.reload();
                    }, 800);
                } else {
                    showNotification(response.data.message, 'error');
                    closeConfirmDialog(overlay, dialog);
                }
            },
            error: function() {
                showNotification(ensembleL10n.errorMoving, 'error');
                closeConfirmDialog(overlay, dialog);
            }
        });
    }
    
    /**
     * Parse time from event time string
     */
    function parseTime(timeString) {
        if (!timeString || timeString === '' || timeString.toLowerCase().includes('all')) {
            return null;
        }
        
        const match = timeString.match(/(\d{1,2}):(\d{2})/);
        if (match) {
            return match[0];
        }
        
        return null;
    }
    
    /**
     * Show notification
     */
    function showNotification(message, type) {
        const notification = $('<div class="es-notification es-notification-' + type + '">' + escapeHtml(message) + '</div>');
        $('body').append(notification);
        
        setTimeout(function() {
            notification.addClass('es-active');
        }, 10);
        
        setTimeout(function() {
            notification.removeClass('es-active');
            setTimeout(function() {
                notification.remove();
            }, 300);
        }, 3000);
    }
    
    /**
     * Initialize month view enhancements
     */
    function initMonthViewEnhancements() {
        initMoreEventsToggle();
        initEventTooltips();
    }
    
    /**
     * Toggle "more events" expansion
     */
    function initMoreEventsToggle() {
        $(document).on('click', '.es-more-events', function(e) {
            e.stopPropagation();
            const $cell = $(this).closest('.es-calendar-cell');
            const $events = $cell.find('.es-cell-events');
            const $moreBtn = $(this);
            
            // Toggle expanded state
            if ($cell.hasClass('es-expanded')) {
                // Collapse
                $cell.removeClass('es-expanded');
                $events.find('.es-hidden-event').slideUp(200);
                const hiddenCount = $moreBtn.data('hidden-count');
                $moreBtn.html('+' + hiddenCount + ' more');
            } else {
                // Expand
                $cell.addClass('es-expanded');
                $events.find('.es-hidden-event').slideDown(200);
                $moreBtn.html('− Show less');
            }
        });
    }
    
    /**
     * Event hover tooltips
     */
    function initEventTooltips() {
        let tooltipTimeout;
        let $activeTooltip = null;
        
        // Show tooltip on hover
        $(document).on('mouseenter', '.es-calendar-event', function(e) {
            const $event = $(this);
            
            // Don't show tooltip if dragging
            if ($event.hasClass('es-dragging')) {
                return;
            }
            
            // Clear any existing timeout
            clearTimeout(tooltipTimeout);
            
            // Wait 500ms before showing tooltip
            tooltipTimeout = setTimeout(function() {
                // Remove any existing tooltip
                $('.es-event-tooltip').remove();
                
                // Build tooltip content
                const title = $event.find('.es-event-title').text();
                const time = $event.data('event-time');
                const location = $event.data('location-name');
                const categories = $event.data('category-text');
                const artists = $event.data('artist-text');
                const description = $event.data('description');
                
                // Safe icon getter with fallback
                const getIcon = function(name, fallback) {
                    if (typeof ES_ICONS !== 'undefined' && ES_ICONS[name]) {
                        return ES_ICONS[name];
                    }
                    return fallback || '';
                };
                
                let tooltipHtml = '<div class="es-event-tooltip">';
                tooltipHtml += '<div class="es-tooltip-header">';
                tooltipHtml += '<strong>' + title + '</strong>';
                tooltipHtml += '</div>';
                
                tooltipHtml += '<div class="es-tooltip-body">';
                
                if (time) {
                    tooltipHtml += '<div class="es-tooltip-row">';
                    tooltipHtml += getIcon('clock', '🕐') + ' <span>' + time + '</span>';
                    tooltipHtml += '</div>';
                }
                
                if (location) {
                    tooltipHtml += '<div class="es-tooltip-row">';
                    tooltipHtml += getIcon('location', '📍') + ' <span>' + location + '</span>';
                    tooltipHtml += '</div>';
                }
                
                if (categories) {
                    tooltipHtml += '<div class="es-tooltip-row">';
                    tooltipHtml += getIcon('category', '🏷️') + ' <span>' + categories + '</span>';
                    tooltipHtml += '</div>';
                }
                
                if (artists) {
                    tooltipHtml += '<div class="es-tooltip-row">';
                    tooltipHtml += getIcon('artist', '👤') + ' <span>' + artists + '</span>';
                    tooltipHtml += '</div>';
                }
                
                if (description) {
                    tooltipHtml += '<div class="es-tooltip-description">' + description + '</div>';
                }
                
                tooltipHtml += '</div>'; // body
                tooltipHtml += '<div class="es-tooltip-footer">Click to view details</div>';
                tooltipHtml += '</div>';
                
                // Create tooltip element
                const $tooltip = $(tooltipHtml);
                $('body').append($tooltip);
                $activeTooltip = $tooltip;
                
                // Position tooltip
                positionTooltip($tooltip, $event);
                
                // Fade in
                setTimeout(function() {
                    $tooltip.addClass('es-tooltip-visible');
                }, 10);
                
            }, 500);
        });
        
        // Hide tooltip on mouse leave
        $(document).on('mouseleave', '.es-calendar-event', function() {
            clearTimeout(tooltipTimeout);
            
            if ($activeTooltip) {
                $activeTooltip.removeClass('es-tooltip-visible');
                setTimeout(function() {
                    $activeTooltip.remove();
                    $activeTooltip = null;
                }, 200);
            }
        });
        
        // Hide tooltip on scroll
        $('.es-calendar-grid, .es-cell-events').on('scroll', function() {
            if ($activeTooltip) {
                $activeTooltip.remove();
                $activeTooltip = null;
            }
        });
    }
    
    /**
     * Position tooltip relative to event
     */
    function positionTooltip($tooltip, $event) {
        const eventRect = $event[0].getBoundingClientRect();
        const tooltipWidth = 300;
        const tooltipHeight = $tooltip.outerHeight();
        const padding = 10;
        
        // Try to position to the right of the event
        let left = eventRect.right + padding;
        let top = eventRect.top;
        
        // Check if tooltip would go off screen to the right
        if (left + tooltipWidth > window.innerWidth - padding) {
            // Position to the left instead
            left = eventRect.left - tooltipWidth - padding;
        }
        
        // Check if tooltip would go off screen at bottom
        if (top + tooltipHeight > window.innerHeight - padding) {
            top = window.innerHeight - tooltipHeight - padding;
        }
        
        // Check if tooltip would go off screen at top
        if (top < padding) {
            top = padding;
        }
        
        $tooltip.css({
            left: left + 'px',
            top: top + 'px'
        });
    }
    
    /**
     * Initialize mini calendar
     */
    function initMiniCalendar() {
        // Mini calendar navigation
        $(document).on('click', '.es-mini-nav', function() {
            const direction = $(this).data('direction');
            const $miniDays = $('.es-mini-days');
            let year = parseInt($miniDays.data('year'));
            let month = parseInt($miniDays.data('month'));
            
            if (direction === 'prev') {
                month--;
                if (month < 1) {
                    month = 12;
                    year--;
                }
            } else {
                month++;
                if (month > 12) {
                    month = 1;
                    year++;
                }
            }
            
            // Update mini calendar via AJAX
            loadMiniCalendar(year, month);
        });
        
        // Click on mini calendar day
        $(document).on('click', '.es-mini-day', function() {
            const date = $(this).data('date');
            if (date) {
                navigateToDate(date);
            }
        });
        
        // Quick jump date picker
        $('#es-jump-date').on('change', function() {
            const date = $(this).val();
            if (date) {
                navigateToDate(date);
            }
        });
    }
    
    /**
     * Load mini calendar for specific month
     */
    function loadMiniCalendar(year, month) {
        $.ajax({
            url: ensembleAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'es_get_mini_calendar',
                nonce: ensembleAjax.nonce,
                year: year,
                month: month
            },
            success: function(response) {
                if (response.success) {
                    $('.es-mini-month-label').text(response.data.label);
                    $('.es-mini-days').html(response.data.html).data('year', year).data('month', month);
                }
            }
        });
    }
    
    /**
     * Navigate to specific date
     */
    function navigateToDate(date) {
        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('date', date);
        window.location.href = currentUrl.toString();
    }
    
    /**
     * Initialize agenda view
     */
    function initAgendaView() {
        // Click on agenda event to show details
        $(document).on('click', '.es-agenda-event', function(e) {
            // Don't trigger if clicking action buttons
            if ($(e.target).closest('.es-agenda-event-actions').length) {
                return;
            }
            
            const eventId = $(this).data('event-id');
            showEventDetails(eventId);
        });
    }
    
    // Initialize all enhancements when DOM is ready
    $(document).ready(function() {
        if ($('.es-calendar-month').length) {
            initMonthViewEnhancements();
        }
        
        if ($('.es-calendar-agenda').length) {
            initAgendaView();
        }
        
        // Quick Jump date picker
        $('#es-jump-date').on('change', function() {
            const date = $(this).val();
            if (date) {
                navigateToDate(date);
            }
        });
        
        // Actions Dropdown Toggle
        $('#es-actions-toggle').on('click', function(e) {
            e.stopPropagation();
            $('#es-actions-dropdown').toggle();
        });
        
        // Close dropdown when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.es-calendar-actions').length) {
                $('#es-actions-dropdown').hide();
            }
        });
        
        // Close dropdown when clicking a link
        $('.es-action-item').on('click', function() {
            $('#es-actions-dropdown').hide();
        });
    });
    
})(jQuery);
