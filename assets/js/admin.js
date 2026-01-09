/**
 * Ensemble Admin JavaScript
 * 
 * @package Ensemble
 */

(function($) {
    'use strict';
    
    // State
    let currentEventId = null;
    let hasUnsavedChanges = false;
    let mediaUploader = null;
    let currentSortOrder = 'asc'; // Sort by event date: asc = oldest first, desc = newest first
    
    // Initialize
    $(document).ready(function() {
        initModalScrollLock();
        initTabs();
        initEventsList();
        initWizardForm();
        initImageUpload();
        initGalleryUpload();
        initMediaDropzone();
        initUnsavedChangesWarning();
        initTimelineTracking();
        checkUrlParams();
    });
    
    /**
     * Initialize modal scroll lock
     * Prevents body scroll when modal is open
     */
    function initModalScrollLock() {
        // Override jQuery fadeIn for .es-modal
        const originalFadeIn = $.fn.fadeIn;
        $.fn.fadeIn = function() {
            const result = originalFadeIn.apply(this, arguments);
            if (this.hasClass('es-modal')) {
                $('body').addClass('es-modal-open');
            }
            return result;
        };
        
        // Override jQuery fadeOut for .es-modal
        const originalFadeOut = $.fn.fadeOut;
        $.fn.fadeOut = function() {
            const $el = this;
            const isModal = $el.hasClass('es-modal');
            
            if (isModal) {
                const args = Array.prototype.slice.call(arguments);
                const originalCallback = typeof args[0] === 'function' ? args[0] : 
                                        typeof args[1] === 'function' ? args[1] : null;
                
                const newCallback = function() {
                    if (originalCallback) originalCallback.call(this);
                    // Check if any modal is still visible
                    if ($('.es-modal:visible').length === 0) {
                        $('body').removeClass('es-modal-open');
                    }
                };
                
                if (typeof args[0] === 'function') {
                    args[0] = newCallback;
                } else if (typeof args[1] === 'function') {
                    args[1] = newCallback;
                } else if (args.length === 0) {
                    args.push(newCallback);
                } else {
                    args.push(newCallback);
                }
                
                return originalFadeOut.apply($el, args);
            }
            
            return originalFadeOut.apply(this, arguments);
        };
        
        // ESC key closes modal
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $('body').hasClass('es-modal-open')) {
                $('.es-modal:visible').fadeOut();
            }
        });
    }
    
    /**
     * Check URL parameters for edit
     */
    function checkUrlParams() {
        const urlParams = new URLSearchParams(window.location.search);
        const editEventId = urlParams.get('edit');
        
        if (editEventId) {
            // Switch to wizard tab and load event
            // Don't parseInt - could be virtual ID like "virtual_123_20251115"
            editEvent(editEventId);
        }
    }
    
    /**
     * Initialize tabs (now handles wizard show/hide without tabs)
     */
    function initTabs() {
        // Legacy tab support - if tabs exist
        $('.es-tab').on('click', function() {
            const tabName = $(this).data('tab');
            
            // Update active tab
            $('.es-tab').removeClass('active');
            $(this).addClass('active');
            
            // Show/hide content
            $('.es-tab-content').hide();
            $('.es-tab-content[data-content="' + tabName + '"]').show();
            
            // If switching to events list, reload
            if (tabName === 'events') {
                loadEvents();
            }
        });
    }
    
    /**
     * Show wizard form
     */
    function showWizardForm() {
        $('.es-events-tab-content').hide();
        $('.es-tab-content[data-content="wizard"]').show();
    }
    
    /**
     * Show events list
     */
    function showEventsList() {
        $('.es-tab-content[data-content="wizard"]').hide();
        $('.es-events-tab-content').show();
        loadEvents();
    }
    
    /**
     * Initialize events list
     */
    function initEventsList() {
        // Load events on page load
        loadEvents();
        
        // Create new event button
        $('#es-create-new-btn').on('click', function() {
            resetForm();
            showWizardForm();
        });
        
        // View Toggle
        $('.es-view-btn').on('click', function() {
            const viewMode = $(this).data('view');
            
            // Update active button
            $('.es-view-btn').removeClass('active');
            $(this).addClass('active');
            
            // Toggle view class on grid
            const $grid = $('#es-events-list');
            if (viewMode === 'list') {
                $grid.addClass('es-list-view');
            } else {
                $grid.removeClass('es-list-view');
            }
            
            // Save preference
            localStorage.setItem('es_wizard_view_mode', viewMode);
        });
        
        // Restore view preference
        const savedView = localStorage.getItem('es_wizard_view_mode');
        if (savedView === 'list') {
            $('.es-view-btn[data-view="list"]').click();
        }
        
        // Live search with debounce
        let searchTimeout;
        $('#es-event-search').on('input', function() {
            clearTimeout(searchTimeout);
            const search = $(this).val();
            
            searchTimeout = setTimeout(function() {
                applyFilters();
            }, 300);
        });
        
        // Filter changes
        $('#es-filter-category, #es-filter-location, #es-filter-date, #es-filter-status').on('change', function() {
            // Clear bulk selection when filters change
            clearBulkSelection();
            
            applyFilters();
            // Sync legend highlighting
            syncLegendHighlight();
        });
        
        // Category Legend Click Handler
        $(document).on('click', '.es-wizard-toolbar .es-legend-item', function() {
            const categoryId = $(this).data('category-id');
            const $item = $(this);
            
            if ($item.hasClass('es-legend-active')) {
                // Deactivate - clear category filter
                $item.removeClass('es-legend-active');
                $('#es-filter-category').val('');
            } else {
                // Activate - set category filter
                $('.es-wizard-toolbar .es-legend-item').removeClass('es-legend-active');
                $item.addClass('es-legend-active');
                $('#es-filter-category').val(categoryId.toString());
            }
            
            applyFilters();
        });
        
        // Clear filters
        $('#es-clear-filters').on('click', function() {
            $('#es-event-search').val('');
            $('#es-filter-category').val('');
            $('#es-filter-location').val('');
            $('#es-filter-date').val('');
            $('#es-filter-status').val('');
            $(this).hide();
            // Clear legend highlighting
            $('.es-wizard-toolbar .es-legend-item').removeClass('es-legend-active');
            updateFilterBadge();
            loadEvents();
        });
        
        // Filter Toggle Button
        $('#es-toggle-filters').on('click', function() {
            const $panel = $('#es-filter-panel');
            const $btn = $(this);
            
            $panel.slideToggle(200);
            $btn.toggleClass('es-filters-active');
        });
        
        // Update filter badge when filters change
        $('#es-filter-category, #es-filter-location, #es-filter-date, #es-filter-status').on('change', function() {
            updateFilterBadge();
        });
        
        // Sort toggle button
        $('#es-sort-events').on('click', function() {
            const $btn = $(this);
            const $arrow = $btn.find('.es-sort-arrow');
            
            // Toggle sort order
            if (currentSortOrder === 'asc') {
                currentSortOrder = 'desc';
                $arrow.text('↓');
                $btn.attr('title', 'Newest first');
            } else {
                currentSortOrder = 'asc';
                $arrow.text('↑');
                $btn.attr('title', 'Oldest first');
            }
            
            $btn.data('order', currentSortOrder);
            
            // Refresh with new sort order
            refreshEventList();
        });
    }
    
    /**
     * Update filter badge count
     */
    function updateFilterBadge() {
        let count = 0;
        if ($('#es-filter-category').val()) count++;
        if ($('#es-filter-location').val()) count++;
        if ($('#es-filter-date').val()) count++;
        if ($('#es-filter-status').val()) count++;
        
        const $badge = $('.es-filter-badge');
        const $btn = $('#es-toggle-filters');
        
        if (count > 0) {
            $badge.text(count).show();
            $btn.addClass('es-filters-active');
        } else {
            $badge.hide();
            $btn.removeClass('es-filters-active');
        }
    }
    
    /**
     * Sync legend highlighting with category filter dropdown
     */
    function syncLegendHighlight() {
        const selectedCategory = $('#es-filter-category').val();
        $('.es-wizard-toolbar .es-legend-item').removeClass('es-legend-active');
        if (selectedCategory) {
            $(`.es-wizard-toolbar .es-legend-item[data-category-id="${selectedCategory}"]`).addClass('es-legend-active');
        }
    }
    
    /**
     * Apply filters to events list
     */
    function applyFilters() {
        const filters = {
            search: $('#es-event-search').val(),
            category: $('#es-filter-category').val(),
            location: $('#es-filter-location').val(),
            date: $('#es-filter-date').val(),
            status: $('#es-filter-status').val(),
            sort_order: currentSortOrder
        };
        
        // Show clear button if any filter is active (excluding search)
        const hasDropdownFilters = filters.category || filters.location || filters.date || filters.status;
        $('#es-clear-filters').toggle(hasDropdownFilters);
        
        // Update filter badge
        updateFilterBadge();
        
        // Load filtered events
        loadFilteredEvents(filters);
    }
    
    /**
     * Load filtered events
     */
    function loadFilteredEvents(filters) {
        $('#es-events-list').html('<div class="es-loading">Loading events...</div>');
        
        $.ajax({
            url: ensembleAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'es_filter_events',
                nonce: ensembleAjax.nonce,
                filters: filters
            },
            success: function(response) {
                if (response.success) {
                    renderEvents(response.data);
                } else {
                    showMessage('error', response.data.message || 'Failed to load events');
                }
            },
            error: function() {
                showMessage('error', 'Failed to load events');
            }
        });
    }
    
    /**
     * Refresh event list - respects current filters
     */
    function refreshEventList() {
        // Always use applyFilters to ensure sort order is included
        applyFilters();
    }
    
    /**
     * Load all events (resets filters but keeps sort order)
     */
    function loadEvents() {
        // Reset all filter dropdowns
        $('#es-event-search').val('');
        $('#es-filter-category').val('');
        $('#es-filter-location').val('');
        $('#es-filter-date').val('');
        $('#es-filter-status').val('');
        $('#es-clear-filters').hide();
        
        // Use applyFilters to include sort order
        applyFilters();
    }
    
    /**
     * Search events
     */
    function searchEvents(search) {
        $('#es-events-list').html('<div class="es-loading">Searching...</div>');
        
        $.ajax({
            url: ensembleAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'es_search_events',
                nonce: ensembleAjax.nonce,
                search: search
            },
            success: function(response) {
                if (response.success) {
                    renderEvents(response.data);
                } else {
                    showMessage('error', response.data.message || 'Search failed');
                }
            },
            error: function() {
                showMessage('error', 'Search failed');
            }
        });
    }
    
    /**
     * Render events list
     */
    function renderEvents(events) {
        if (!events || events.length === 0) {
            $('#es-events-list').html(`
                <div class="es-empty-state">
                    <div class="es-empty-state-icon">${ES_ICONS.calendar}</div>
                    <h3>No events found</h3>
                    <p>Create your first event to get started!</p>
                    <button class="button button-primary es-create-first-event">
                        Create Event
                    </button>
                </div>
            `);
            
            // Bind click handler for empty state button
            $('.es-create-first-event').on('click', function() {
                resetForm();
                showWizardForm();
            });
            return;
        }
        
        let html = '';
        events.forEach(function(event) {
            const date = event.date ? formatDate(event.date) : 'No date';
            const time = event.time ? event.time : '';
            const location = event.location_name ? event.location_name : 'No location';
            const artists = event.artist_names && event.artist_names.length > 0 
                ? event.artist_names.join(', ') 
                : 'No artists';
            const isVirtual = event.is_virtual || false;
            const isRecurring = event.is_recurring || false;
            const virtualBadge = isVirtual ? '<span class="es-badge es-badge-virtual" title="Virtual recurring instance">' + ES_ICONS.sync + ' Virtual</span>' : '';
            const recurringBadge = isRecurring && !isVirtual ? '<span class="es-badge es-badge-recurring" title="Recurring event">' + ES_ICONS.sync + ' Recurring</span>' : '';
            
            // Status badge
            const eventStatus = event.event_status || 'publish';
            let statusBadge = '';
            if (eventStatus !== 'publish') {
                const statusLabels = {
                    'draft': 'Entwurf',
                    'cancelled': 'Abgesagt',
                    'postponed': 'Verschoben'
                };
                const statusLabel = statusLabels[eventStatus] || eventStatus;
                statusBadge = `<span class="es-badge es-badge-status es-badge-status-${eventStatus}">${statusLabel}</span>`;
            }
            
            // Get featured image or placeholder
            const imageUrl = event.featured_image || ensembleAjax.pluginUrl + 'assets/images/event-placeholder.svg';
            
            // Get categories
            let categoriesHtml = '';
            if (event.categories && event.categories.length > 0) {
                categoriesHtml = event.categories.map(function(cat) {
                    return '<span class="es-category-badge">' + escapeHtml(cat.name) + '</span>';
                }).join('');
            }
            
            // Card class based on status
            const statusClass = eventStatus !== 'publish' ? `es-event-status-${eventStatus}` : '';
            
            html += `
                <div class="es-event-card ${isVirtual ? 'es-virtual-event' : ''} ${statusClass}" data-event-id="${event.id}" data-event-status="${eventStatus}">
                    <div class="es-event-card-image">
                        <input type="checkbox" class="es-event-select" data-event-id="${event.id}">
                        <img src="${imageUrl}" alt="${escapeHtml(event.title)}" loading="lazy">
                        <div class="es-event-card-badges">
                            ${statusBadge}
                            ${virtualBadge}
                            ${recurringBadge}
                        </div>
                    </div>
                    <div class="es-event-card-content">
                        <h3 class="es-event-card-title">${escapeHtml(event.title)}</h3>
                        
                        ${categoriesHtml ? '<div class="es-event-card-categories">' + categoriesHtml + '</div>' : ''}
                        
                        <div class="es-event-card-meta">
                            <div class="es-event-card-meta-item">
                                <span class="dashicons dashicons-calendar-alt"></span>
                                <span>${date} ${time}</span>
                            </div>
                            <div class="es-event-card-meta-item">
                                <span class="dashicons dashicons-location"></span>
                                <span>${escapeHtml(location)}</span>
                            </div>
                            ${artists !== 'No artists' ? `
                            <div class="es-event-card-meta-item">
                                <span class="dashicons dashicons-admin-users"></span>
                                <span>${escapeHtml(artists)}</span>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                    <div class="es-event-card-actions">
                        <button class="button es-edit-event" data-event-id="${event.id}">
                            <span class="dashicons dashicons-edit"></span> Edit
                        </button>
                        <button class="button es-copy-event" data-event-id="${event.id}" title="Event kopieren">
                            <span class="dashicons dashicons-admin-page"></span>
                        </button>
                        <button class="button es-delete-event" data-event-id="${event.id}">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </div>
                </div>
            `;
        });
        
        $('#es-events-list').html(html);
        
        // Bind edit/delete/copy buttons
        $('.es-edit-event').on('click', function() {
            const eventId = $(this).data('event-id');
            editEvent(eventId);
        });
        
        $('.es-copy-event').on('click', function() {
            const eventId = $(this).data('event-id');
            copyEvent(eventId);
        });
        
        $('.es-delete-event').on('click', function() {
            const eventId = $(this).data('event-id');
            deleteEvent(eventId);
        });
        
        // Update bulk actions UI
        updateBulkActionsUI();
    }
    
    /**
     * Initialize wizard form
     */
    function initWizardForm() {
        // Form submission (Save & Close)
        $('#es-event-form').on('submit', function(e) {
            e.preventDefault();
            saveEvent(true); // Close after save
        });
        
        // Save button (Save without closing)
        $('#es-save-btn').on('click', function() {
            saveEvent(false); // Stay on form
        });
        
        // Cancel button
        $('#es-cancel-btn').on('click', function() {
            if (hasUnsavedChanges) {
                if (!confirm(ensembleAjax.strings.unsavedChanges)) {
                    return;
                }
            }
            resetForm();
            showEventsList();
        });
        
        // Delete button
        $('#es-delete-event-btn').on('click', function() {
            if (currentEventId) {
                deleteEvent(currentEventId);
            }
        });
        
        // Copy button
        $('#es-copy-event-btn').on('click', function() {
            if (currentEventId) {
                copyEvent(currentEventId);
            }
        });
        
        // Track changes
        $('#es-event-form').on('change input', 'input, select, textarea', function() {
            hasUnsavedChanges = true;
        });
        
        // Category change - load custom steps
        $('input[name="categories[]"]').on('change', function() {
            loadCustomWizardSteps();
        });
        
        // ====================================
        // Duration Type System (Festival/Exhibition)
        // ====================================
        
        // Duration type change handler
        $('input[name="duration_type"]').on('change', function() {
            handleDurationTypeChange($(this).val());
        });
        
        // Has children checkbox change
        $('#es-has-children').on('change', function() {
            if ($(this).is(':checked')) {
                // If event is already saved, show the child events list with Add button
                if (currentEventId) {
                    $('#es-child-events-items').html('<div class="es-child-events-empty">' + (ensembleAjax.strings.noChildEvents || 'No sub-events yet. Click below to add one.') + '</div>');
                    $('#es-child-events-count').text('0');
                    $('#es-child-events-list').slideDown(200);
                    $('#es-children-info').hide();
                } else {
                    // Event not saved yet - show info message
                    $('#es-children-info').slideDown(200);
                    $('#es-child-events-list').hide();
                }
            } else {
                // Unchecked - hide everything
                $('#es-children-info').slideUp(200);
                $('#es-child-events-list').slideUp(200);
            }
        });
        
        // ====================================
        // Add Child Event Link
        // ====================================
        
        $('#es-add-child-event').on('click', function(e) {
            e.preventDefault();
            
            // Save current event first if it has unsaved changes
            if (hasUnsavedChanges) {
                if (confirm(ensembleAjax.strings.saveBeforeAddChild || 'Save current event before creating a sub-event?')) {
                    saveEvent(false);
                    // After save, we would need to handle the redirect
                    // For now, just show instruction
                }
                return;
            }
            
            // Create a new event with this as parent
            const parentId = currentEventId;
            const parentTitle = $('#es-event-title').val();
            
            if (!parentId) {
                alert('Please save the parent event first.');
                return;
            }
            
            // Reset form for new event
            resetForm();
            
            // Set parent event
            $('#es-parent-event').val(parentId);
            
            // Set default title suggestion
            const childCount = parseInt($('#es-child-events-count').text()) || 0;
            $('#es-event-title').val(parentTitle + ' - ' + (ensembleAjax.strings.day || 'Day') + ' ' + (childCount + 1));
            
            // Set to single event type (children are usually single events)
            $('input[name="duration_type"][value="single"]').prop('checked', true);
            handleDurationTypeChange('single');
            
            // Show parent event section
            $('.es-parent-event-section').show();
            
            // Go to first step
            goToStep(1);
            
            // Show form
            showEventForm();
        });
        
        // ====================================
        // Link Existing Event
        // ====================================
        
        let linkSearchTimeout = null;
        let allLinkableEvents = []; // Cache for all linkable events
        
        $('#es-link-existing-event').on('click', function(e) {
            e.preventDefault();
            
            if (!currentEventId) {
                alert(ensembleAjax.strings.saveFirst || 'Please save the parent event first.');
                return;
            }
            
            // Clear previous search
            $('#es-link-event-search').val('');
            
            // Show modal and loading state
            $('#es-link-event-modal').fadeIn(200);
            $('#es-link-event-results').html('<p class="es-loading">' + (ensembleAjax.strings.loadingEvents || 'Loading events...') + '</p>');
            
            // Load all linkable events immediately
            loadLinkableEvents();
        });
        
        // Close modal
        $('#es-link-event-modal .es-modal-close').on('click', function() {
            $('#es-link-event-modal').fadeOut(200);
        });
        
        // Close modal on outside click
        $('#es-link-event-modal').on('click', function(e) {
            if ($(e.target).is('#es-link-event-modal')) {
                $(this).fadeOut(200);
            }
        });
        
        // Filter events on search input
        $('#es-link-event-search').on('input', function() {
            const query = $(this).val().toLowerCase();
            
            if (!query) {
                // Show all events
                renderLinkSearchResults(allLinkableEvents);
                return;
            }
            
            // Filter cached events
            const filtered = allLinkableEvents.filter(function(event) {
                return event.title.toLowerCase().includes(query) || 
                       (event.date && event.date.includes(query));
            });
            
            if (filtered.length > 0) {
                renderLinkSearchResults(filtered);
            } else {
                $('#es-link-event-results').html('<p class="es-no-results">' + (ensembleAjax.strings.noEventsFound || 'No matching events') + '</p>');
            }
        });
        
        function loadLinkableEvents() {
            $.ajax({
                url: ensembleAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'es_get_linkable_events',
                    nonce: ensembleAjax.nonce,
                    exclude: currentEventId
                },
                success: function(response) {
                    if (response.success && response.data.events && response.data.events.length > 0) {
                        allLinkableEvents = response.data.events;
                        renderLinkSearchResults(allLinkableEvents);
                    } else {
                        allLinkableEvents = [];
                        $('#es-link-event-results').html('<p class="es-no-results">' + (ensembleAjax.strings.noLinkableEvents || 'No events available to link. All events are either already linked or are parent events.') + '</p>');
                    }
                },
                error: function() {
                    $('#es-link-event-results').html('<p class="es-error">' + (ensembleAjax.strings.loadError || 'Error loading events') + '</p>');
                }
            });
        }
        
        function renderLinkSearchResults(events) {
            const $container = $('#es-link-event-results');
            $container.empty();
            
            // Show count info
            const countText = events.length === 1 
                ? (ensembleAjax.strings.oneEventAvailable || '1 event available')
                : (events.length + ' ' + (ensembleAjax.strings.eventsAvailable || 'events available'));
            $container.append('<p class="es-results-count">' + countText + '</p>');
            
            // Sort by date (newest first)
            events.sort(function(a, b) {
                if (!a.date) return 1;
                if (!b.date) return -1;
                return new Date(b.date) - new Date(a.date);
            });
            
            const $list = $('<div class="es-results-list"></div>');
            
            events.forEach(function(event) {
                const dateDisplay = event.date ? formatDate(event.date) : '-';
                
                const $item = $('<div class="es-search-result-item" data-id="' + event.id + '">' +
                    '<span class="es-result-date">' + dateDisplay + '</span>' +
                    '<span class="es-result-title">' + escapeHtml(event.title) + '</span>' +
                    '<button type="button" class="es-link-btn button button-small">' + (ensembleAjax.strings.link || 'Link') + '</button>' +
                '</div>');
                
                $list.append($item);
            });
            
            $container.append($list);
            
            // Bind link button
            $container.find('.es-link-btn').on('click', function(e) {
                e.stopPropagation();
                const eventId = $(this).closest('.es-search-result-item').data('id');
                linkEventToParent(eventId);
            });
        }
        
        function linkEventToParent(childId) {
            $.ajax({
                url: ensembleAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'es_link_child_event',
                    nonce: ensembleAjax.nonce,
                    child_id: childId,
                    parent_id: currentEventId
                },
                success: function(response) {
                    if (response.success) {
                        // Close modal
                        $('#es-link-event-modal').fadeOut(200);
                        
                        // Reload child events list
                        $.ajax({
                            url: ensembleAjax.ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'es_get_child_events',
                                nonce: ensembleAjax.nonce,
                                parent_id: currentEventId
                            },
                            success: function(res) {
                                if (res.success && res.data.children) {
                                    renderChildEventsList(res.data.children);
                                    $('#es-child-events-count').text(res.data.children.length);
                                }
                            }
                        });
                        
                        // Show success message
                        showToast(response.data.message || 'Event linked successfully!', 'success');
                    } else {
                        alert(response.data.message || 'Failed to link event');
                    }
                },
                error: function() {
                    alert(ensembleAjax.strings.linkError || 'Error linking event');
                }
            });
        }
        
        // Initialize duration type on page load
        handleDurationTypeChange($('input[name="duration_type"]:checked').val() || 'single');
    }
    
    /**
     * Handle duration type changes (single, multi_day, permanent)
     * Shows/hides relevant date fields and options
     */
    function handleDurationTypeChange(durationType) {
        // Hide all date rows first
        $('.es-date-single-row').hide();
        $('.es-date-range-row').hide();
        $('.es-date-permanent-row').hide();
        $('.es-sub-events-section').hide();
        $('.es-parent-event-section').hide();
        
        // Reset required attributes
        $('#es-event-date').prop('required', false);
        $('#es-event-date-start').prop('required', false);
        
        switch (durationType) {
            case 'single':
                // Show single date field
                $('.es-date-single-row').show();
                $('#es-event-date').prop('required', true);
                
                // Show parent event section (child can be single event)
                $('.es-parent-event-section').show();
                
                // Show time fields
                $('.es-time-row').show();
                break;
                
            case 'multi_day':
                // Show date range fields
                $('.es-date-range-row').show();
                $('#es-event-date-start').prop('required', true);
                
                // Copy date to start field if not set
                if (!$('#es-event-date-start').val() && $('#es-event-date').val()) {
                    $('#es-event-date-start').val($('#es-event-date').val());
                }
                
                // Show sub-events section
                $('.es-sub-events-section').show();
                
                // Show time fields (for daily schedule)
                $('.es-time-row').show();
                break;
                
            case 'permanent':
                // Show permanent date field (opening date)
                $('.es-date-permanent-row').show();
                
                // Copy date to permanent field if not set
                if (!$('#es-event-date-permanent').val() && $('#es-event-date').val()) {
                    $('#es-event-date-permanent').val($('#es-event-date').val());
                }
                
                // Show sub-events section
                $('.es-sub-events-section').show();
                
                // Hide time fields (permanent events don't have specific times)
                $('.es-time-row').hide();
                break;
        }
        
        // Update has_children visibility based on checkbox
        if ($('#es-has-children').is(':checked')) {
            $('#es-children-info').show();
        } else {
            $('#es-children-info').hide();
        }
    }
    
    /**
     * Load and display child events list for a parent event
     */
    function loadChildEventsList(parentId, childIds) {
        if (!parentId || !childIds || childIds.length === 0) {
            $('#es-child-events-list').hide();
            return;
        }
        
        // Update count
        $('#es-child-events-count').text(childIds.length);
        
        // Fetch child event details via AJAX
        $.ajax({
            url: ensembleAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'es_get_child_events',
                nonce: ensembleAjax.nonce,
                parent_id: parentId
            },
            success: function(response) {
                if (response.success && response.data.children) {
                    renderChildEventsList(response.data.children);
                } else {
                    // Fallback: Show basic list with IDs
                    renderChildEventsBasic(childIds);
                }
            },
            error: function() {
                // Fallback: Show basic list with IDs
                renderChildEventsBasic(childIds);
            }
        });
        
        // Show the list container
        $('#es-child-events-list').show();
        $('#es-children-info').hide();
    }
    
    /**
     * Render child events list with full details
     */
    function renderChildEventsList(children) {
        const $container = $('#es-child-events-items');
        $container.empty();
        
        if (children.length === 0) {
            $container.html('<div class="es-child-events-empty">' + (ensembleAjax.strings.noChildEvents || 'No sub-events yet') + '</div>');
            return;
        }
        
        children.forEach(function(child) {
            const dateDisplay = child.date ? formatDate(child.date) : '-';
            
            const $item = $('<div class="es-child-event-item" data-id="' + child.id + '">' +
                '<span class="es-child-event-date">' + dateDisplay + '</span>' +
                '<span class="es-child-event-title">' + escapeHtml(child.title) + '</span>' +
                '<div class="es-child-event-actions">' +
                    '<button type="button" class="es-child-event-edit" data-id="' + child.id + '">' + (ensembleAjax.strings.edit || 'Edit') + '</button>' +
                    '<button type="button" class="es-child-event-unlink" data-id="' + child.id + '">' + (ensembleAjax.strings.unlink || 'Unlink') + '</button>' +
                '</div>' +
            '</div>');
            
            $container.append($item);
        });
        
        // Bind edit click
        $container.find('.es-child-event-edit').on('click', function() {
            const childId = $(this).data('id');
            loadEventById(childId);
        });
        
        // Bind unlink click
        $container.find('.es-child-event-unlink').on('click', function() {
            const childId = $(this).data('id');
            unlinkChildEvent(childId);
        });
    }
    
    /**
     * Render basic child events list (fallback)
     */
    function renderChildEventsBasic(childIds) {
        const $container = $('#es-child-events-items');
        $container.empty();
        
        childIds.forEach(function(childId) {
            const $item = $('<div class="es-child-event-item" data-id="' + childId + '">' +
                '<span class="es-child-event-title">Event #' + childId + '</span>' +
                '<div class="es-child-event-actions">' +
                    '<button type="button" class="es-child-event-edit" data-id="' + childId + '">' + (ensembleAjax.strings.edit || 'Edit') + '</button>' +
                '</div>' +
            '</div>');
            
            $container.append($item);
        });
        
        // Bind edit click
        $container.find('.es-child-event-edit').on('click', function() {
            const childId = $(this).data('id');
            loadEventById(childId);
        });
    }
    
    /**
     * Unlink a child event from its parent
     */
    function unlinkChildEvent(childId) {
        if (!confirm(ensembleAjax.strings.unlinkConfirm || 'Remove this event from the parent? The event will not be deleted.')) {
            return;
        }
        
        $.ajax({
            url: ensembleAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'es_unlink_child_event',
                nonce: ensembleAjax.nonce,
                child_id: childId
            },
            success: function(response) {
                if (response.success) {
                    // Remove from list
                    $('#es-child-events-items [data-id="' + childId + '"]').fadeOut(300, function() {
                        $(this).remove();
                        // Update count
                        const newCount = $('#es-child-events-items').children().length;
                        $('#es-child-events-count').text(newCount);
                        
                        if (newCount === 0) {
                            $('#es-child-events-items').html('<div class="es-child-events-empty">' + (ensembleAjax.strings.noChildEvents || 'No sub-events yet') + '</div>');
                        }
                    });
                    showMessage('success', response.data.message || 'Event unlinked');
                } else {
                    showMessage('error', response.data.message || 'Failed to unlink event');
                }
            }
        });
    }
    
    /**
     * Load event by ID (for editing child events)
     */
    function loadEventById(eventId) {
        $.ajax({
            url: ensembleAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'es_get_event',
                nonce: ensembleAjax.nonce,
                event_id: eventId
            },
            success: function(response) {
                if (response.success && response.data) {
                    populateForm(response.data);
                    showEventForm();
                } else {
                    showMessage('error', 'Failed to load event');
                }
            }
        });
    }
    
    /**
     * Format date for display
     */
    function formatDate(dateStr) {
        if (!dateStr) return '-';
        const date = new Date(dateStr);
        if (isNaN(date.getTime())) return dateStr;
        
        const options = { day: '2-digit', month: '2-digit', year: 'numeric' };
        return date.toLocaleDateString('de-DE', options);
    }
    
    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    /**
     * Save event
     */
    function saveEvent(closeAfterSave = true) {
        // Get description from WYSIWYG if available
        let description = '';
        if (typeof tinymce !== 'undefined' && tinymce.get('es-event-description')) {
            description = tinymce.get('es-event-description').getContent();
        } else {
            description = $('#es-event-description').val() || '';
        }
        
        // Get additional info from WYSIWYG if available
        let additionalInfo = '';
        if (typeof tinymce !== 'undefined' && tinymce.get('es-event-additional-info')) {
            additionalInfo = tinymce.get('es-event-additional-info').getContent();
        } else {
            additionalInfo = $('#es-event-additional-info').val() || '';
        }
        
        const formData = {
            action: 'es_save_event',
            nonce: ensembleAjax.nonce,
            event_id: currentEventId || 0,
            title: $('#es-event-title').val(),
            event_date: $('#es-event-date').val(),
            event_time: $('#es-event-time').val(),
            event_time_end: $('#es-event-time-end').val(),
            event_description: description,
            event_additional_info: additionalInfo,
            event_location: $('input[name="event_location"]:checked').val() || '',
            event_venue: $('#es-event-venue').val() || '',
            venue_config: $('#es-venue-config').val() || '{}',
            event_artist: [],
            artist_times: {},
            artist_venues: {},
            artist_session_titles: {},
            event_price: $('#es-event-price').val(),
            event_price_note: $('#es-event-price-note').val() || '',
            event_ticket_url: $('#es-event-ticket-url').val(),
            event_button_text: $('#es-event-button-text').val(),
            event_external_url: $('#es-event-external-url').val() || '',
            event_external_text: $('#es-event-external-text').val() || '',
            event_badge: $('#es-event-badge').val() || '',
            event_badge_custom: $('#es-event-badge-custom').val() || '',
            featured_image_id: $('#es-featured-image-id').val(),
            gallery_ids: $('#es-gallery-ids').val(),
            categories: [],
            event_genres: [],
            show_artist_genres: $('#es-show-artist-genres').is(':checked') ? '1' : '0',
            event_status: $('input[name="event_status"]:checked').val() || 'publish',
            acf: {},
            
            // Duration Type System (Festival/Exhibition)
            duration_type: $('input[name="duration_type"]:checked').val() || 'single',
            event_date_start: $('#es-event-date-start').val() || '',
            event_date_end: $('#es-event-date-end').val() || '',
            event_date_permanent: $('#es-event-date-permanent').val() || '',
            has_children: $('#es-has-children').is(':checked') ? '1' : '0',
            parent_event_id: $('#es-parent-event').val() || ''
        };
        
        // Get selected artists from pills with times and venues
        $('#es-artist-selection .es-artist-pill').each(function() {
            const $pill = $(this);
            const $checkbox = $pill.find('.es-artist-checkbox');
            
            if ($checkbox.is(':checked')) {
                const artistId = $checkbox.val();
                formData.event_artist.push(artistId);
                
                // Get artist time if set
                const artistTime = $pill.find('.es-artist-time').val();
                if (artistTime) {
                    formData.artist_times[artistId] = artistTime;
                }
                
                // Get artist venue if set
                const artistVenue = $pill.find('.es-artist-venue').val();
                if (artistVenue) {
                    formData.artist_venues[artistId] = artistVenue;
                }
                
                // Get artist session title if set
                const artistSessionTitle = $pill.find('.es-artist-session-title').val();
                if (artistSessionTitle) {
                    formData.artist_session_titles[artistId] = artistSessionTitle;
                }
            }
        });
        
        // Fallback: Also check old pill-based artist selection
        if (formData.event_artist.length === 0) {
            $('input[name="event_artist[]"]:checked').each(function() {
                formData.event_artist.push($(this).val());
            });
        }
        
        // Convert objects to JSON strings for proper serialization
        formData.artist_times = JSON.stringify(formData.artist_times);
        formData.artist_venues = JSON.stringify(formData.artist_venues);
        formData.artist_session_titles = JSON.stringify(formData.artist_session_titles);
        
        // Get selected categories
        $('input[name="categories[]"]:checked').each(function() {
            formData.categories.push($(this).val());
        });
        
        // Get selected genres
        $('input[name="event_genres[]"]:checked').each(function() {
            formData.event_genres.push($(this).val());
        });
        
        // Get ACF field values from custom steps
        $('.es-custom-step').find('input, select, textarea').each(function() {
            const $field = $(this);
            const fieldName = $field.attr('name');
            
            if (fieldName && fieldName.startsWith('acf[')) {
                // Extract field key from name: acf[field_key]
                const fieldKey = fieldName.match(/acf\[([^\]]+)\]/)[1];
                
                if ($field.attr('type') === 'checkbox') {
                    formData.acf[fieldKey] = $field.is(':checked') ? '1' : '0';
                } else {
                    formData.acf[fieldKey] = $field.val();
                }
            }
        });
        
        // Get reservation settings (if fields exist)
        if ($('#es-reservation-enabled').length) {
            formData.reservation_enabled = $('#es-reservation-enabled').is(':checked') ? '1' : '0';
            
            // Get reservation types
            formData.reservation_types = [];
            $('input[name="reservation_types[]"]:checked').each(function() {
                formData.reservation_types.push($(this).val());
            });
            
            // Get capacity and deadline
            formData.reservation_capacity = $('#es-reservation-capacity').val() || '';
            formData.reservation_deadline_hours = $('#es-reservation-deadline').val() || '24';
            formData.reservation_auto_confirm = $('input[name="reservation_auto_confirm"]').is(':checked') ? '1' : '0';
        }
        
        // Get tickets data (from Tickets addon)
        if ($('#es-tickets-data').length) {
            formData.tickets_data = $('#es-tickets-data').val() || '[]';
        }
        
        // Get agenda data (from Agenda addon)
        if ($('#es-agenda-data').length) {
            formData.agenda_data = $('#es-agenda-data').val() || '{}';
        }
        
        // Get agenda breaks (simple timeline breaks)
        if ($('#es-agenda-breaks').length) {
            formData.agenda_breaks = $('#es-agenda-breaks').val() || '[]';
        }
        
        // Get downloads data (from Downloads addon)
        if ($('#es-downloads-data').length) {
            formData.downloads_data = $('#es-downloads-data').val() || '[]';
        }
        
        // Get event contacts (from Staff addon)
        // Use .selected class because checkboxes are hidden
        formData.event_contacts = [];
        $('#es-contact-selection .es-contact-pill.selected').each(function() {
            var contactId = $(this).data('contact-id');
            if (contactId) {
                formData.event_contacts.push(contactId.toString());
            }
        });
        console.log('Saving event contacts:', formData.event_contacts);
        
        // Get hero video settings
        if ($('#es-hero-video-url').length) {
            formData.hero_video_url = $('#es-hero-video-url').val() || '';
            formData.hero_video_autoplay = $('input[name="hero_video_autoplay"]').is(':checked') ? '1' : '0';
            formData.hero_video_loop = $('input[name="hero_video_loop"]').is(':checked') ? '1' : '0';
            formData.hero_video_controls = $('input[name="hero_video_controls"]').is(':checked') ? '1' : '0';
        }
        
        // Get recurring rules if available
        if (typeof window.EnsembleRecurring !== 'undefined') {
            const recurringRules = window.EnsembleRecurring.getRules();
            if (recurringRules) {
                formData.is_recurring = '1';
                formData.recurring_rules = recurringRules;
            }
        }
        
        // Show loading state
        const $submitBtn = $('#es-event-form button[type="submit"]');
        const originalText = $submitBtn.text();
        $submitBtn.prop('disabled', true).text(ensembleAjax.strings.saving);
        
        $.ajax({
            url: ensembleAjax.ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                $submitBtn.prop('disabled', false).text(originalText);
                
                if (response.success) {
                    showMessage('success', response.data.message);
                    hasUnsavedChanges = false;
                    currentEventId = response.data.event_id;
                    
                    // Update event ID field in case this was a new event
                    $('#es-event-id').val(currentEventId);
                    
                    // Show delete and copy buttons if editing
                    if (currentEventId) {
                        $('#es-delete-event-btn').show();
                        $('#es-copy-event-btn').show();
                    }
                    
                    // Reload events list (respects current filters)
                    refreshEventList();
                    
                    // Only close if requested
                    if (closeAfterSave) {
                        setTimeout(function() {
                            resetForm();
                            showEventsList();
                        }, 1500);
                    } else {
                        // Just show a toast that it was saved
                        showToast('Event gespeichert', 'success');
                        
                        // Update child events list if has_children is enabled
                        if ($('#es-has-children').is(':checked')) {
                            // Show the child events list with Add button
                            $('#es-child-events-list').show();
                            $('#es-children-info').hide();
                            
                            // Load existing children (if any)
                            $.ajax({
                                url: ensembleAjax.ajaxurl,
                                type: 'POST',
                                data: {
                                    action: 'es_get_child_events',
                                    nonce: ensembleAjax.nonce,
                                    parent_id: currentEventId
                                },
                                success: function(res) {
                                    if (res.success && res.data.children && res.data.children.length > 0) {
                                        renderChildEventsList(res.data.children);
                                        $('#es-child-events-count').text(res.data.children.length);
                                    } else {
                                        // No children yet - show empty state with Add button
                                        $('#es-child-events-items').html('<div class="es-child-events-empty">' + (ensembleAjax.strings.noChildEvents || 'No sub-events yet. Click below to add one.') + '</div>');
                                        $('#es-child-events-count').text('0');
                                    }
                                }
                            });
                        }
                    }
                } else {
                    showMessage('error', response.data.message || ensembleAjax.strings.error);
                }
            },
            error: function() {
                $submitBtn.prop('disabled', false).text(originalText);
                showMessage('error', ensembleAjax.strings.error);
            }
        });
    }
    
    /**
     * Edit event
     */
    function editEvent(eventId) {
        console.log('editEvent called with ID:', eventId, 'Type:', typeof eventId);
        
        // Check if it's a virtual event
        const isVirtual = typeof eventId === 'string' && eventId.indexOf('virtual_') === 0;
        
        console.log('Is virtual:', isVirtual);
        
        if (isVirtual) {
            // Convert virtual to real first
            console.log('Converting virtual event to real:', eventId);
            
            $.ajax({
                url: ensembleAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'es_convert_virtual_to_real',
                    nonce: ensembleAjax.nonce,
                    virtual_id: eventId,
                    modifications: {} // No modifications yet
                },
                success: function(response) {
                    console.log('Convert response:', response);
                    if (response.success) {
                        // Now edit the newly created real event
                        const realEventId = response.data.event_id;
                        console.log('Converted to real event ID:', realEventId);
                        // false = don't show convert back box for freshly converted events
                        loadRealEventForEdit(realEventId, false);
                    } else {
                        showMessage('error', response.data.message || 'Failed to convert virtual event');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Convert error:', error);
                    showMessage('error', 'Failed to convert virtual event');
                }
            });
        } else {
            // Normal real event
            console.log('Loading real event:', eventId);
            loadRealEventForEdit(eventId, false);
        }
    }
    
    /**
     * Load real event for editing
     */
    function loadRealEventForEdit(eventId, wasVirtual) {
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
                    const event = response.data;
                    populateForm(event);
                    
                    // Show convert back option if:
                    // 1. It was just converted (wasVirtual param is true), OR
                    // 2. The event has was_virtual flag from the database
                    if (wasVirtual || event.was_virtual) {
                        showConvertBackOption(event);
                    }
                    
                    // Go to Step 1 when loading an event
                    if (window.ensembleWizard && typeof window.ensembleWizard.goToStep === 'function') {
                        window.ensembleWizard.goToStep(1);
                    }
                    
                    showWizardForm();
                } else {
                    showMessage('error', response.data.message || 'Failed to load event');
                }
            },
            error: function() {
                showMessage('error', 'Failed to load event');
            }
        });
    }
    
    /**
     * Show option to convert back to virtual
     */
    function showConvertBackOption(event) {
        // Add convert back section after delete button
        const convertBackHtml = `
            <div class="es-convert-back-section" style="margin-top: 20px; padding: 15px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 4px;">
                <p style="margin: 0 0 10px 0;">
                    <strong>ℹ️ This event was converted from a recurring instance.</strong>
                </p>
                <p style="margin: 0 0 15px 0; color: #856404;">
                    You can restore this event back to the recurring series. This will delete the individual event and recreate it as a virtual instance.
                </p>
                <button type="button" id="es-restore-virtual-btn" class="button button-secondary" style="margin-right: 10px;">
                    <span class="es-btn-icon">${ES_ICONS.sync}</span> Restore to Recurring Series
                </button>
                <button type="button" id="es-close-restore-box-btn" class="button">
                    Close
                </button>
            </div>
        `;
        
        // Insert before form actions
        if (!$('.es-convert-back-section').length) {
            $('.es-form-actions').before(convertBackHtml);
            
            // Add event handlers
            $('#es-restore-virtual-btn').on('click', function() {
                if (confirm('Restore this event to the recurring series? This will delete the individual event.')) {
                    deleteEventAndRestoreVirtual(currentEventId);
                }
            });
            
            $('#es-close-restore-box-btn').on('click', function() {
                $('.es-convert-back-section').slideUp(300, function() {
                    $(this).remove();
                });
            });
        }

    }
    
    /**
     * Delete event and restore as virtual
     */
    function deleteEventAndRestoreVirtual(eventId) {
        if (!confirm('Delete this event and restore it as part of the recurring series?')) {
            return;
        }
        
        $.ajax({
            url: ensembleAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'es_restore_to_virtual',
                nonce: ensembleAjax.nonce,
                event_id: eventId
            },
            success: function(response) {
                if (response.success) {
                    showMessage('success', 'Event restored to recurring series');
                    resetForm();
                    showEventsList();
                    refreshEventList();
                } else {
                    showMessage('error', response.data.message || 'Failed to restore event');
                }
            },
            error: function() {
                showMessage('error', 'Failed to restore event');
            }
        });
    }
    
    /**
     * Delete event
     */
    function deleteEvent(eventId) {
        if (!confirm(ensembleAjax.strings.deleteConfirm)) {
            return;
        }
        
        $.ajax({
            url: ensembleAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'es_delete_event',
                nonce: ensembleAjax.nonce,
                event_id: eventId
            },
            success: function(response) {
                if (response.success) {
                    showMessage('success', response.data.message);
                    resetForm();
                    showEventsList();
                    refreshEventList();
                } else {
                    showMessage('error', response.data.message || 'Failed to delete event');
                }
            },
            error: function() {
                showMessage('error', 'Failed to delete event');
            }
        });
    }
    
    /**
     * Copy event with all ACF fields
     */
    function copyEvent(eventId) {
        if (!confirm('Möchten Sie dieses Event kopieren?\n\nEs wird eine Kopie mit allen Daten (inklusive ACF-Felder) als Entwurf erstellt.')) {
            return;
        }
        
        const $copyBtn = $('#es-copy-event-btn');
        const originalText = $copyBtn.html();
        $copyBtn.prop('disabled', true).html('<span class="dashicons dashicons-update" style="animation: spin 1s linear infinite;"></span> Kopiere...');
        
        $.ajax({
            url: ensembleAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'es_copy_event',
                nonce: ensembleAjax.nonce,
                event_id: eventId
            },
            success: function(response) {
                if (response.success) {
                    showMessage('success', response.data.message);
                    
                    // Load the copied event in the wizard
                    setTimeout(function() {
                        editEvent(response.data.event_id);
                        refreshEventList(); // Refresh event list
                    }, 500);
                } else {
                    showMessage('error', response.data.message || 'Fehler beim Kopieren des Events');
                    $copyBtn.prop('disabled', false).html(originalText);
                }
            },
            error: function() {
                showMessage('error', 'Fehler beim Kopieren des Events');
                $copyBtn.prop('disabled', false).html(originalText);
            }
        });
    }
    
    /**
     * Populate form with event data
     */
    function populateForm(event) {
        currentEventId = event.id;
        hasUnsavedChanges = false;
        
        $('#es-event-id').val(event.id);
        $('#es-event-title').val(event.title);
        
        // Set description - handle both WYSIWYG and textarea
        if (typeof tinymce !== 'undefined' && tinymce.get('es-event-description')) {
            tinymce.get('es-event-description').setContent(event.description || '');
        }
        $('#es-event-description').val(event.description || '');
        
        // Set additional info - handle both WYSIWYG and textarea
        if (typeof tinymce !== 'undefined' && tinymce.get('es-event-additional-info')) {
            tinymce.get('es-event-additional-info').setContent(event.additional_info || '');
        }
        $('#es-event-additional-info').val(event.additional_info || '');
        
        $('#es-event-date').val(event.date);
        $('#es-event-time').val(event.time);
        $('#es-event-time-end').val(event.time_end || '');
        
        // ====================================
        // Duration Type System (Festival/Exhibition)
        // ====================================
        
        // Set duration type radio
        const durationType = event.duration_type || 'single';
        $('input[name="duration_type"]').prop('checked', false);
        $('input[name="duration_type"][value="' + durationType + '"]').prop('checked', true);
        
        // Set date fields based on duration type
        if (durationType === 'multi_day') {
            $('#es-event-date-start').val(event.date || '');
            $('#es-event-date-end').val(event.date_end || '');
        } else if (durationType === 'permanent') {
            $('#es-event-date-permanent').val(event.date || '');
        }
        
        // Set has_children checkbox
        $('#es-has-children').prop('checked', event.has_children === true);
        
        // Set parent event
        if (event.parent_event_id) {
            $('#es-parent-event').val(event.parent_event_id);
        } else {
            $('#es-parent-event').val('');
        }
        
        // Trigger duration type change to show/hide relevant fields
        handleDurationTypeChange(durationType);
        
        // ====================================
        // Load Child Events List (if this is a parent)
        // ====================================
        
        if (event.has_children) {
            // Always show child events list when has_children is enabled
            if (event.child_events_count > 0) {
                // Load existing child events
                loadChildEventsList(event.id, event.child_event_ids);
            } else {
                // No children yet - show empty list with "Add" button
                $('#es-child-events-items').html('<div class="es-child-events-empty">' + (ensembleAjax.strings.noChildEvents || 'No sub-events yet. Click below to add one.') + '</div>');
                $('#es-child-events-count').text('0');
                $('#es-child-events-list').show();  // Show list so Add button is visible!
                $('#es-children-info').hide();
            }
        } else {
            // has_children not enabled - hide everything
            $('#es-child-events-items').empty();
            $('#es-child-events-count').text('0');
            $('#es-child-events-list').hide();
            $('#es-children-info').hide();
        }
        
        // ====================================
        $('#es-event-price').val(event.price);
        $('#es-event-price-note').val(event.price_note || '');
        $('#es-event-ticket-url').val(event.ticket_url || '');
        $('#es-event-button-text').val(event.button_text || '');
        
        // External link
        $('#es-event-external-url').val(event.external_url || '');
        $('#es-event-external-text').val(event.external_text || '');
        
        // Badge settings
        $('#es-event-badge').val(event.badge || '');
        $('#es-event-badge-custom').val(event.badge_custom || '');
        
        // Event Status
        $('input[name="event_status"]').prop('checked', false);
        const status = event.event_status || 'publish';
        $('input[name="event_status"][value="' + status + '"]').prop('checked', true);
        
        // Location (radio pills)
        $('input[name="event_location"]').prop('checked', false);
        console.log('🎫 Loading event - venue_config:', event.venue_config);
        
        // Store venue_config in hidden field FIRST - before triggering location change
        // This way the change handler can read it and restore it
        if (event.venue_config && Object.keys(event.venue_config).length > 0) {
            $('#es-venue-config').val(JSON.stringify(event.venue_config));
            console.log('🎫 Set venue_config in hidden field');
        }
        
        // Store venue_config for later application
        const venueConfigToApply = event.venue_config;
        
        if (event.location_id) {
            $('input[name="event_location"][value="' + event.location_id + '"]').prop('checked', true);
            // Trigger change to show venue selection if multivenue
            setTimeout(function() {
                console.log('🎫 Triggering location change');
                $('input[name="event_location"][value="' + event.location_id + '"]').trigger('change');
                
                // Wait for toggles to be generated
                function waitForTogglesAndApply(attempts) {
                    const $toggles = $('.es-venue-toggle-item');
                    console.log('🎫 Waiting for toggles, attempt:', attempts, 'found:', $toggles.length);
                    
                    if ($toggles.length > 0) {
                        // Toggles are ready, apply venue config
                        if (event.venue) {
                            $('#es-event-venue').val(event.venue);
                        }
                        
                        // Config should already be applied by the change handler reading from hidden field
                        // But apply it again just to be safe
                        if (venueConfigToApply && Object.keys(venueConfigToApply).length > 0) {
                            console.log('🎫 Re-applying venue config to be safe');
                            applyVenueConfig(venueConfigToApply);
                        }
                        
                        // Populate artist selections
                        setTimeout(function() {
                            populateArtistSelections(event);
                        }, 100);
                    } else if (attempts < 20) {
                        // Try again in 100ms
                        setTimeout(function() {
                            waitForTogglesAndApply(attempts + 1);
                        }, 100);
                    } else {
                        console.log('🎫 Gave up waiting for toggles');
                        // Still populate artists even without toggles
                        populateArtistSelections(event);
                    }
                }
                
                // Start waiting for toggles
                setTimeout(function() {
                    waitForTogglesAndApply(0);
                }, 150);
                
            }, 100);
        } else {
            // No location - still populate artist selections
            populateArtistSelections(event);
        }
        
        // Fallback: Also set old pill-based artists
        $('input[name="event_artist[]"]').prop('checked', false);
        if (event.artist_ids) {
            if (!Array.isArray(event.artist_ids)) {
                event.artist_ids = [event.artist_ids];
            }
            event.artist_ids.forEach(function(artistId) {
                $('input[name="event_artist[]"][value="' + artistId + '"]').prop('checked', true);
            });
        }
        
        // Restore artist order if available
        if (event.artist_order) {
            const orderIds = event.artist_order.split(',').map(id => parseInt(id));
            const $container = $('#es-artist-selection');
            
            // Sort pills according to saved order
            orderIds.forEach(function(artistId) {
                const $pill = $container.find('.es-artist-pill[data-artist-id="' + artistId + '"]');
                if ($pill.length) {
                    $container.append($pill);
                }
            });
            
            // Store the order
            $('#es-artist-order').val(event.artist_order);
        }
        
        // Categories
        $('input[name="categories[]"]').prop('checked', false);
        if (event.categories) {
            event.categories.forEach(function(cat) {
                $('input[name="categories[]"][value="' + cat.id + '"]').prop('checked', true);
            });
            
            // Load custom steps for selected categories
            // We need to wait a moment for the checkboxes to be updated
            setTimeout(function() {
                loadCustomWizardStepsForEdit(event);
            }, 100);
        }
        
        // Genres
        $('input[name="event_genres[]"]').prop('checked', false);
        if (event.genres) {
            event.genres.forEach(function(genre) {
                $('input[name="event_genres[]"][value="' + genre.id + '"]').prop('checked', true);
            });
        }
        
        // Show artist genres checkbox
        $('#es-show-artist-genres').prop('checked', event.show_artist_genres || false);
        
        // Featured image
        if (event.featured_image) {
            $('#es-featured-image-id').val(event.featured_image);
            $('#es-featured-image-preview').html(`
                <img src="${event.featured_image}" alt="">
                <button type="button" class="es-remove-image">×</button>
            `).addClass('has-image');
            
            // Update dropzone state if using new dropzone
            $('.es-media-dropzone[data-type="featured"]').addClass('has-media');
            $('.es-media-dropzone[data-type="featured"] .es-media-preview').html(`
                <div class="es-media-preview-item" data-id="${event.featured_image_id || ''}">
                    <img src="${event.featured_image}" alt="">
                    <button type="button" class="es-remove-media" title="Remove">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>
            `);
            
            $('.es-remove-image').on('click', removeImage);
        } else {
            // Clear featured image when not set
            $('#es-featured-image-id').val('');
            $('#es-featured-image-preview').html('').removeClass('has-image');
            $('.es-media-dropzone[data-type="featured"]').removeClass('has-media');
            $('.es-media-dropzone[data-type="featured"] .es-media-preview').html('');
        }
        
        // Gallery images
        if (event.gallery && event.gallery.length > 0) {
            const galleryIds = event.gallery.map(img => img.id);
            $('#es-gallery-ids').val(galleryIds.join(','));
            
            let galleryHtml = '';
            event.gallery.forEach(function(img) {
                galleryHtml += `
                    <div class="es-gallery-item" data-id="${img.id}">
                        <img src="${img.url}" alt="">
                        <button type="button" class="es-gallery-remove" title="Remove">×</button>
                    </div>
                `;
            });
            $('#es-gallery-preview').html(galleryHtml);
            
            // Update dropzone state
            $('.es-media-dropzone[data-type="gallery"]').addClass('has-media');
            
            // Bind remove handlers
            $('.es-gallery-remove').on('click', function() {
                removeGalleryImage($(this).closest('.es-gallery-item'));
            });
            
            // Reinitialize sortable after populating gallery
            initGallerySortable();
        } else {
            // Clear gallery when not set
            $('#es-gallery-ids').val('');
            $('#es-gallery-preview').html('');
            $('.es-media-dropzone[data-type="gallery"]').removeClass('has-media');
        }
        
        // Load recurring rules if available
        if (typeof window.EnsembleRecurring !== 'undefined') {
            window.EnsembleRecurring.loadRules(event.id);
        }
        
        // Remove convert back section if event was not converted
        // (It will be added back by loadRealEventForEdit if needed)
        $('.es-convert-back-section').remove();
        
        // Reservation settings (if fields exist)
        if ($('#es-reservation-enabled').length) {
            // Handle both boolean and string '1' values
            const reservationEnabled = event.reservation_enabled === true || event.reservation_enabled === '1' || event.reservation_enabled === 1;
            $('#es-reservation-enabled').prop('checked', reservationEnabled);
            
            if (reservationEnabled) {
                $('#es-reservation-options').show();
            } else {
                $('#es-reservation-options').hide();
            }
            
            // Reservation types
            $('input[name="reservation_types[]"]').prop('checked', false);
            if (event.reservation_types && Array.isArray(event.reservation_types)) {
                event.reservation_types.forEach(function(type) {
                    $('input[name="reservation_types[]"][value="' + type + '"]').prop('checked', true);
                });
            } else if (!reservationEnabled) {
                // Default to guestlist when not enabled
                $('input[name="reservation_types[]"][value="guestlist"]').prop('checked', true);
            }
            
            // Capacity
            $('#es-reservation-capacity').val(event.reservation_capacity || '');
            
            // Deadline
            $('#es-reservation-deadline').val(event.reservation_deadline_hours || 24);
            
            // Auto confirm - handle both boolean and string
            const autoConfirm = event.reservation_auto_confirm === true || event.reservation_auto_confirm === '1' || event.reservation_auto_confirm === 1;
            $('input[name="reservation_auto_confirm"]').prop('checked', autoConfirm);
        }
        
        // Hero Video settings
        if ($('#es-hero-video-url').length) {
            $('#es-hero-video-url').val(event.hero_video_url || '');
            
            // Video options - handle boolean and string values
            const autoplay = event.hero_video_autoplay === true || event.hero_video_autoplay === '1' || event.hero_video_autoplay === 1;
            const loop = event.hero_video_loop === true || event.hero_video_loop === '1' || event.hero_video_loop === 1;
            const controls = event.hero_video_controls === true || event.hero_video_controls === '1' || event.hero_video_controls === 1;
            
            $('input[name="hero_video_autoplay"]').prop('checked', autoplay);
            $('input[name="hero_video_loop"]').prop('checked', loop);
            $('input[name="hero_video_controls"]').prop('checked', controls);
            
            // Show preview if video URL is set
            if (event.hero_video_url) {
                updateVideoPreview(event.hero_video_url);
            }
        }
        
        // Tickets data (from Tickets addon)
        if ($('#es-tickets-data').length && event.tickets) {
            $('#es-tickets-data').val(JSON.stringify(event.tickets));
            // Trigger re-render if wizard is initialized
            if (typeof window.EnsembleTicketsWizard !== 'undefined') {
                window.EnsembleTicketsWizard.loadTickets();
                window.EnsembleTicketsWizard.render();
            }
        }
        
        // Agenda data (from Agenda addon)
        if ($('#es-agenda-data').length && event.agenda) {
            $('#es-agenda-data').val(JSON.stringify(event.agenda));
            // Trigger re-render if agenda editor is initialized
            if (typeof window.EnsembleAgenda !== 'undefined' && window.EnsembleAgenda.Editor) {
                window.EnsembleAgenda.Editor.agenda = event.agenda;
                window.EnsembleAgenda.Editor.render();
            }
        }
        
        // Downloads data (from Downloads addon)
        if ($('#es-downloads-data').length && event.downloads) {
            var downloadIds = event.downloads.map(function(d) { return d.id; });
            $('#es-downloads-data').val(JSON.stringify(downloadIds));
            // Trigger re-render via custom event
            $(document).trigger('es:downloads:loaded', [event.downloads]);
        }
        
        // Event contacts (from Staff addon)
        if ($('#es-contact-selection').length) {
            // Reset all contacts
            $('.es-contact-pill').removeClass('selected');
            $('input[name="event_contacts[]"]').prop('checked', false);
            
            // Set selected contacts
            if (event.contacts && Array.isArray(event.contacts)) {
                event.contacts.forEach(function(contactId) {
                    var $pill = $('.es-contact-pill[data-contact-id="' + contactId + '"]');
                    $pill.addClass('selected');
                    $pill.find('input[name="event_contacts[]"]').prop('checked', true);
                });
            }
        }
        
        // Trigger event for other addons to hook into
        $(document).trigger('ensemble_event_loaded', [event]);
        
        // Show delete and copy buttons
        $('#es-delete-event-btn').show();
        $('#es-copy-event-btn').show();
    }
    
    /**
     * Reset form
     */
    function resetForm() {
        currentEventId = null;
        hasUnsavedChanges = false;
        
        $('#es-event-form')[0].reset();
        $('#es-event-id').val('');
        $('#es-featured-image-id').val('');
        $('#es-featured-image-preview').html('').removeClass('has-image');
        $('#es-gallery-ids').val('');
        $('#es-gallery-preview').html('');
        $('#es-delete-event-btn').hide();
        $('#es-copy-event-btn').hide();
        
        // Reset media dropzones
        $('.es-media-dropzone').removeClass('has-media');
        $('.es-media-dropzone .es-media-preview').html('');
        $('.es-media-dropzone .es-gallery-preview').html('');
        
        // Reset hero video
        $('#es-hero-video-url').val('');
        $('input[name="hero_video_autoplay"]').prop('checked', true);
        $('input[name="hero_video_loop"]').prop('checked', true);
        $('input[name="hero_video_controls"]').prop('checked', false);
        $('#es-hero-video-preview').hide();
        $('#es-video-preview-frame').attr('src', '');
        
        // Reset WYSIWYG editor
        if (typeof tinymce !== 'undefined' && tinymce.get('es-event-description')) {
            tinymce.get('es-event-description').setContent('');
        }
        
        // Reset additional info WYSIWYG
        if (typeof tinymce !== 'undefined' && tinymce.get('es-event-additional-info')) {
            tinymce.get('es-event-additional-info').setContent('');
        }
        
        // Reset external link fields
        $('#es-event-external-url').val('');
        $('#es-event-external-text').val('');
        
        // Reset badge and social fields
        $('#es-event-badge').val('');
        $('#es-event-badge-custom').val('');
        
        // Reset venue configuration
        $('#es-venue-config').val('{}');
        $('#es-event-venue').val('');
        $('#es-venue-toggles').empty();
        $('#es-venue-selection').hide();
        
        // Reset venue selection trackers
        currentLocationId = null;
        venueSelectionInitialized = false;
        
        // ====================================
        // Reset Duration Type System
        // ====================================
        
        // Reset to single event type
        $('input[name="duration_type"][value="single"]').prop('checked', true);
        handleDurationTypeChange('single');
        
        // Clear date fields
        $('#es-event-date-start').val('');
        $('#es-event-date-end').val('');
        $('#es-event-date-permanent').val('');
        
        // Reset checkboxes
        $('#es-has-children').prop('checked', false);
        $('#es-children-info').hide();
        
        // Clear child events list
        $('#es-child-events-items').empty();
        $('#es-child-events-count').text('0');
        $('#es-child-events-list').hide();
        
        // Reset parent event
        $('#es-parent-event').val('');
        
        // Show global genres again
        $('#es-global-genres').find('#es-genre-pills').show();
        $('#es-global-genres').find('.es-pill-add[data-es-quick-add="genre"]').show();
        
        // Remove custom steps
        $('.es-custom-step').remove();
        
        // Remove convert back section
        $('.es-convert-back-section').remove();
        
        // Clear recurring form
        if (typeof window.EnsembleRecurring !== 'undefined') {
            window.EnsembleRecurring.clear();
        }
        
        // Reset reservation fields
        if ($('#es-reservation-enabled').length) {
            $('#es-reservation-enabled').prop('checked', false);
            $('#es-reservation-options').hide();
            $('input[name="reservation_types[]"]').prop('checked', false);
            $('input[name="reservation_types[]"][value="guestlist"]').prop('checked', true);
            $('#es-reservation-capacity').val('');
            $('#es-reservation-deadline').val(24);
            $('input[name="reservation_auto_confirm"]').prop('checked', true);
        }
        
        // Go to Step 1
        if (window.ensembleWizard && typeof window.ensembleWizard.goToStep === 'function') {
            window.ensembleWizard.goToStep(1);
        }
        
        // Reset artist pills
        $('.es-artist-pill .es-artist-checkbox').prop('checked', false);
        $('.es-artist-pill .es-artist-time').val('');
        $('.es-artist-pill .es-artist-venue').val('');
        $('.es-artist-pill').each(function() {
            updatePillIndicators($(this));
        });
        
        // Reset contact pills (Staff addon)
        $('.es-contact-pill').removeClass('selected');
        $('input[name="event_contacts[]"]').prop('checked', false);
    }
    
    /**
     * Initialize image upload
     */
    function initImageUpload() {
        $('#es-upload-image-btn').on('click', function(e) {
            e.preventDefault();
            
            if (mediaUploader) {
                mediaUploader.open();
                return;
            }
            
            mediaUploader = wp.media({
                title: 'Select Event Image',
                button: { text: 'Use this image' },
                multiple: false
            });
            
            mediaUploader.on('select', function() {
                const attachment = mediaUploader.state().get('selection').first().toJSON();
                $('#es-featured-image-id').val(attachment.id);
                $('#es-featured-image-preview').html(`
                    <img src="${attachment.url}" alt="">
                    <button type="button" class="es-remove-image">Remove</button>
                `).addClass('has-image');
                
                $('.es-remove-image').on('click', removeImage);
                hasUnsavedChanges = true;
            });
            
            mediaUploader.open();
        });
    }
    
    /**
     * Remove image
     */
    function removeImage() {
        $('#es-featured-image-id').val('');
        $('#es-featured-image-preview').html('').removeClass('has-image');
        hasUnsavedChanges = true;
    }
    
    /**
     * Initialize gallery upload
     */
    let galleryUploader = null;
    
    function initGalleryUpload() {
        const $preview = $('#es-gallery-preview');
        const $button = $('#es-upload-gallery-btn');
        
        if ($button.length === 0) return;
        
        $button.on('click', function(e) {
            e.preventDefault();
            
            if (galleryUploader) {
                galleryUploader.open();
                return;
            }
            
            galleryUploader = wp.media({
                title: 'Select Gallery Images',
                button: { text: 'Add to Gallery' },
                multiple: true,
                library: { type: 'image' }
            });
            
            galleryUploader.on('select', function() {
                const attachments = galleryUploader.state().get('selection').toJSON();
                
                // Get current gallery IDs
                let currentIds = $('#es-gallery-ids').val();
                currentIds = currentIds ? currentIds.split(',').map(id => parseInt(id)) : [];
                
                attachments.forEach(function(attachment) {
                    // Don't add duplicates
                    if (currentIds.indexOf(attachment.id) === -1) {
                        currentIds.push(attachment.id);
                        
                        // Add preview
                        const thumbUrl = attachment.sizes && attachment.sizes.thumbnail 
                            ? attachment.sizes.thumbnail.url 
                            : attachment.url;
                        
                        $('#es-gallery-preview').append(`
                            <div class="es-gallery-item" data-id="${attachment.id}">
                                <img src="${thumbUrl}" alt="">
                                <button type="button" class="es-gallery-remove" title="Remove">×</button>
                            </div>
                        `);
                    }
                });
                
                // Update hidden field
                $('#es-gallery-ids').val(currentIds.join(','));
                
                // Bind remove handlers for new items
                $('.es-gallery-remove').off('click').on('click', function() {
                    removeGalleryImage($(this).closest('.es-gallery-item'));
                });
                
                // Re-initialize sortable after adding new items
                initGallerySortable();
                
                hasUnsavedChanges = true;
            });
            
            galleryUploader.open();
        });
        
        // Initialize sortable on page load
        initGallerySortable();
    }
    
    /**
     * Initialize gallery sortable functionality
     */
    function initGallerySortable() {
        const $preview = $('#es-gallery-preview');
        
        if (!$preview.length || !$.fn.sortable) return;
        
        // Destroy existing sortable if already initialized
        if ($preview.hasClass('ui-sortable')) {
            $preview.sortable('destroy');
        }
        
        $preview.sortable({
            items: '.es-gallery-item',
            cursor: 'grabbing',
            opacity: 0.65,
            placeholder: 'es-gallery-item-placeholder',
            tolerance: 'pointer',
            revert: 150,
            start: function(e, ui) {
                ui.item.addClass('es-gallery-dragging');
                // Set placeholder size to match item
                ui.placeholder.height(ui.item.height());
                ui.placeholder.width(ui.item.width());
            },
            stop: function(e, ui) {
                ui.item.removeClass('es-gallery-dragging');
            },
            update: function() {
                updateGalleryOrder();
                hasUnsavedChanges = true;
            }
        });
    }
    
    /**
     * Update gallery order in hidden input based on DOM order
     */
    function updateGalleryOrder() {
        const ids = [];
        $('#es-gallery-preview .es-gallery-item').each(function() {
            ids.push($(this).data('id'));
        });
        $('#es-gallery-ids').val(ids.join(','));
    }
    
    /**
     * Remove single gallery image
     */
    function removeGalleryImage($item) {
        const removeId = parseInt($item.data('id'));
        
        // Update hidden field
        let currentIds = $('#es-gallery-ids').val();
        currentIds = currentIds ? currentIds.split(',').map(id => parseInt(id)) : [];
        currentIds = currentIds.filter(id => id !== removeId);
        $('#es-gallery-ids').val(currentIds.join(','));
        
        // Remove preview
        $item.fadeOut(200, function() {
            $(this).remove();
        });
        
        hasUnsavedChanges = true;
    }
    
    /**
     * Initialize Media Dropzone (Drag & Drop)
     */
    function initMediaDropzone() {
        const $dropzones = $('.es-media-dropzone');
        
        if (!$dropzones.length) return;
        
        $dropzones.each(function() {
            const $dropzone = $(this);
            const type = $dropzone.data('type'); // 'featured' or 'gallery'
            
            // Prevent default drag behaviors
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                $dropzone[0].addEventListener(eventName, preventDefaults, false);
            });
            
            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            // Highlight drop zone when item is dragged over
            ['dragenter', 'dragover'].forEach(eventName => {
                $dropzone[0].addEventListener(eventName, highlight, false);
            });
            
            ['dragleave', 'drop'].forEach(eventName => {
                $dropzone[0].addEventListener(eventName, unhighlight, false);
            });
            
            function highlight() {
                $dropzone.addClass('es-dropzone-active');
            }
            
            function unhighlight() {
                $dropzone.removeClass('es-dropzone-active');
            }
            
            // Handle dropped files
            $dropzone[0].addEventListener('drop', handleDrop, false);
            
            function handleDrop(e) {
                const dt = e.dataTransfer;
                const files = dt.files;
                
                if (files.length) {
                    handleFiles(files);
                }
            }
            
            function handleFiles(files) {
                // Filter for images only
                const imageFiles = Array.from(files).filter(file => 
                    file.type.startsWith('image/')
                );
                
                if (imageFiles.length === 0) {
                    showMessage('error', 'Please drop image files only.');
                    return;
                }
                
                // For now, just open the media library
                // WordPress's async-upload requires complex authentication
                // The media library handles uploads properly
                if (type === 'featured') {
                    $('#es-upload-image-btn').trigger('click');
                } else {
                    $('#es-upload-gallery-btn').trigger('click');
                }
                
                // Show hint to user
                showMessage('info', 'Please select the dropped images from the Media Library.');
            }
        });
        
        // Remove featured image
        $(document).on('click', '.es-media-dropzone[data-type="featured"] .es-remove-media', function(e) {
            e.preventDefault();
            const $dropzone = $(this).closest('.es-media-dropzone');
            $dropzone.find('.es-media-preview').empty();
            $dropzone.removeClass('has-media');
            $('#es-featured-image-id').val('');
            hasUnsavedChanges = true;
        });
        
        // Remove gallery image (from dropzone upload)
        $(document).on('click', '.es-media-dropzone[data-type="gallery"] .es-remove-gallery-image', function(e) {
            e.preventDefault();
            const $item = $(this).closest('.es-gallery-item');
            removeGalleryImage($item);
        });
        
        // Hero video URL preview
        let videoDebounce;
        $('#es-hero-video-url').on('input change', function() {
            const url = $(this).val();
            clearTimeout(videoDebounce);
            videoDebounce = setTimeout(function() {
                updateVideoPreview(url);
            }, 500);
        });
    }
    
    /**
     * Update video preview based on URL
     */
    function updateVideoPreview(url) {
        const $preview = $('#es-hero-video-preview');
        const $frame = $('#es-video-preview-frame');
        
        if (!url) {
            $preview.hide();
            $frame.attr('src', '');
            return;
        }
        
        const embedUrl = getVideoEmbedUrl(url);
        
        if (embedUrl) {
            $frame.attr('src', embedUrl);
            $preview.show();
        } else {
            $preview.hide();
            $frame.attr('src', '');
        }
    }
    
    /**
     * Get embeddable URL from video URL
     */
    function getVideoEmbedUrl(url) {
        if (!url) return null;
        
        // YouTube
        let match = url.match(/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/);
        if (match) {
            return `https://www.youtube.com/embed/${match[1]}?autoplay=0&mute=1`;
        }
        
        // Vimeo
        match = url.match(/vimeo\.com\/(?:video\/)?(\d+)/);
        if (match) {
            return `https://player.vimeo.com/video/${match[1]}?autoplay=0&muted=1`;
        }
        
        // Direct MP4 - return null (can't embed in iframe)
        if (url.endsWith('.mp4') || url.endsWith('.webm')) {
            return null;
        }
        
        return null;
    }
    
    /**
     * Initialize unsaved changes warning
     */
    function initUnsavedChangesWarning() {
        $(window).on('beforeunload', function() {
            if (hasUnsavedChanges) {
                return ensembleAjax.strings.unsavedChanges;
            }
        });
    }
    
    /**
     * Show message
     */
    function showMessage(type, message) {
        const $msg = $('#es-message');
        $msg.removeClass('success error')
            .addClass(type)
            .text(message)
            .fadeIn();
        
        setTimeout(function() {
            $msg.fadeOut();
        }, 4000);
    }
    
    /**
     * Format date
     * Note: We append T00:00:00 to treat the date as local time, not UTC
     * This prevents timezone-related off-by-one-day issues
     */
    function formatDate(dateString) {
        if (!dateString) return '';
        // Ensure we're treating the date as local time, not UTC
        // By appending T00:00:00, JavaScript interprets it as local midnight
        const date = new Date(dateString + 'T00:00:00');
        return date.toLocaleDateString('de-DE', { 
            year: 'numeric', 
            month: 'short', 
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
     * Load custom wizard steps based on selected categories
     */
    function loadCustomWizardSteps() {
        // Remove existing custom steps
        $('.es-form-section.es-custom-step').remove();
        
        // Get selected categories
        const selectedCategories = [];
        $('input[name="categories[]"]:checked').each(function() {
            selectedCategories.push($(this).val());
        });
        
        if (selectedCategories.length === 0) {
            return;
        }
        
        // Load custom steps for ALL selected categories (combine field groups)
        $.ajax({
            url: ensembleAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'es_get_custom_wizard_steps',
                nonce: ensembleAjax.nonce,
                category_ids: selectedCategories
            },
            success: function(response) {
                if (response.success && response.data.steps.length > 0) {
                    renderCustomSteps(response.data.steps);
                }
            },
            error: function() {
                console.error('Failed to load custom wizard steps');
            }
        });
    }
    
    /**
     * Render custom steps in the wizard
     */
    function renderCustomSteps(steps) {
        // Remove old custom steps
        $('.es-custom-step').remove();
        $('.es-timeline-step.custom').remove();
        $('.es-timeline-connector.custom').remove();
        
        // Find the insert point (after Featured Image section, before form actions)
        const $formInsertPoint = $('.es-form-actions');
        const $timelineInsertPoint = $('.es-wizard-timeline');
        
        let stepNumber = 7; // Start after standard steps (1-6)
        
        steps.forEach(function(step) {
            // Add to timeline
            const $connector = $('<div>').addClass('es-timeline-connector custom');
            const $timelineStep = $('<div>')
                .addClass('es-timeline-step custom')
                .attr('data-step', stepNumber);
            
            const $number = $('<div>')
                .addClass('es-timeline-step-number')
                .text(stepNumber);
            
            const $label = $('<div>')
                .addClass('es-timeline-step-label')
                .text(step.title);
            
            $timelineStep.append($number, $label);
            $timelineInsertPoint.append($connector, $timelineStep);
            
            // Add form section (hidden initially)
            const $section = $('<div>')
                .addClass('es-form-section es-custom-step')
                .attr('data-step', stepNumber)
                .attr('data-step-key', step.key)
                .css('display', 'none');
            
            const $title = $('<h2>').text(step.title);
            $section.append($title);
            $section.append(step.fields_html);
            
            $section.insertBefore($formInsertPoint);
            
            stepNumber++;
        });
        
        // Update total steps count
        const totalSteps = 6 + steps.length; // Base 6 steps + custom steps
        if (window.ensembleWizard) {
            window.ensembleWizard.setTotalSteps(totalSteps);
        }
        
        // Re-bind change tracking for new fields
        $('.es-custom-step').on('change input', 'input, select, textarea', function() {
            hasUnsavedChanges = true;
        });
    }
    
    /**
     * Load custom wizard steps when editing an event
     */
    function loadCustomWizardStepsForEdit(event) {
        // Get selected categories
        const selectedCategories = [];
        $('input[name="categories[]"]:checked').each(function() {
            selectedCategories.push($(this).val());
        });
        
        if (selectedCategories.length === 0) {
            return;
        }
        
        $.ajax({
            url: ensembleAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'es_get_custom_wizard_steps',
                nonce: ensembleAjax.nonce,
                category_ids: selectedCategories
            },
            success: function(response) {
                if (response.success && response.data.steps.length > 0) {
                    renderCustomSteps(response.data.steps);
                    
                    // Populate custom field values if they exist in event data
                    if (event.acf_fields) {
                        populateCustomFields(event.acf_fields);
                    }
                }
            },
            error: function() {
                console.error('Failed to load custom wizard steps');
            }
        });
    }
    
    /**
     * Populate custom ACF field values
     */
    function populateCustomFields(acfFields) {
        $.each(acfFields, function(fieldKey, fieldValue) {
            const $field = $('[name="acf[' + fieldKey + ']"]');
            
            if ($field.length) {
                if ($field.attr('type') === 'checkbox') {
                    $field.prop('checked', fieldValue == '1' || fieldValue == true);
                } else {
                    $field.val(fieldValue);
                }
            }
        });
    }
    
    /**
     * Initialize timeline tracking / Step Navigation
     */
    function initTimelineTracking() {
        // Initialize wizard step navigation
        let currentStep = 1;
        let totalSteps = 6; // Updated: 6 steps total
        
        updateWizardStep(currentStep);
        
        // Next button
        $('.es-wizard-next').on('click', function() {
            if (currentStep < totalSteps) {
                currentStep++;
                updateWizardStep(currentStep);
            }
        });
        
        // Previous button
        $('.es-wizard-prev').on('click', function() {
            if (currentStep > 1) {
                currentStep--;
                updateWizardStep(currentStep);
            }
        });
        
        // Legacy: Save button (if present)
        $('.es-wizard-save').on('click', function() {
            saveEvent(false);
        });
        
        // Click on timeline step to jump
        $(document).on('click', '.es-timeline-step', function() {
            const stepNumber = $(this).data('step');
            if (stepNumber <= totalSteps) {
                currentStep = stepNumber;
                updateWizardStep(currentStep);
            }
        });
        
        /**
         * Update wizard to show current step
         */
        function updateWizardStep(step) {
            currentStep = step;
            $('#es-current-step').val(step);
            
            // Hide all sections
            $('.es-form-section[data-step]').hide().removeClass('active');
            
            // Show current step sections
            $('.es-form-section[data-step="' + step + '"]').show().addClass('active');
            
            // Update timeline
            $('.es-timeline-step').removeClass('active completed');
            
            $('.es-timeline-step').each(function() {
                const $step = $(this);
                const thisStep = $step.data('step');
                
                if (thisStep < step) {
                    $step.addClass('completed');
                } else if (thisStep === step) {
                    $step.addClass('active');
                }
            });
            
            // Update connectors
            $('.es-timeline-connector').removeClass('active');
            $('.es-timeline-connector').each(function(index) {
                if (index < step - 1) {
                    $(this).addClass('active');
                }
            });
            
            // Update navigation buttons
            if (step === 1) {
                $('.es-wizard-prev').hide();
            } else {
                $('.es-wizard-prev').show();
            }
            
            // Show/hide next button based on step
            if (step === totalSteps) {
                $('.es-wizard-next').hide();
            } else {
                $('.es-wizard-next').show();
            }
            
            // Save buttons are always visible (removed from here, shown via CSS)
            
            // Scroll to top of wizard
            const $container = $('.es-wizard-form-container');
            if ($container.length) {
                $container.get(0).scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
        
        // Make updateWizardStep and variables accessible
        window.ensembleWizard = {
            currentStep: function() { return currentStep; },
            totalSteps: function() { return totalSteps; },
            setTotalSteps: function(total) { 
                totalSteps = total; 
                updateWizardStep(currentStep); // Refresh display
            },
            goToStep: function(step) {
                if (step >= 1 && step <= totalSteps) {
                    currentStep = step;
                    updateWizardStep(step);
                }
            },
            updateDisplay: function() {
                updateWizardStep(currentStep);
            }
        };
    }
    
    /**
     * Initialize Smart Pills for Location & Artists
     */
    function initSmartPills() {
        // Initialize location pills
        initPillGroup('#es-location-pills', {
            searchPlaceholder: 'Search locations...',
            showTopN: 10,
            isMultiple: false // Radio buttons
        });
        
        // Initialize artist pills
        initPillGroup('#es-artist-pills', {
            searchPlaceholder: 'Search artists...',
            showTopN: 10,
            isMultiple: true // Checkboxes
        });
    }
    
    /**
     * Initialize a pill group with search and show more
     */
    function initPillGroup(selector, options) {
        const $container = $(selector);
        if ($container.length === 0) return;
        
        const $pills = $container.find('.es-pill');
        const totalPills = $pills.length;
        
        console.log('Init Pills:', selector, 'Total:', totalPills); // Debug
        
        // If less than showTopN, no need for extras
        if (totalPills <= options.showTopN) {
            console.log('Not enough pills for smart features'); // Debug
            return;
        }
        
        // Check if already initialized
        if ($container.find('.es-pills-search').length > 0) {
            console.log('Already initialized'); // Debug
            return;
        }
        
        // Add search input before pills
        const searchHtml = `
            <div class="es-pills-search">
                <input type="text" class="es-pills-search-input" placeholder="${options.searchPlaceholder}">
                <span class="es-pills-search-icon dashicons dashicons-search"></span>
            </div>
        `;
        $container.prepend(searchHtml);
        
        // Wrap pills in a container if not already wrapped
        if (!$pills.parent().hasClass('es-pills-list')) {
            $pills.wrapAll('<div class="es-pills-list"></div>');
        }
        const $pillsList = $container.find('.es-pills-list');
        
        // Hide pills after showTopN
        $pills.each(function(index) {
            if (index >= options.showTopN) {
                $(this).addClass('es-pill-hidden');
            }
        });
        
        // Add "Show more" button after pills list
        const showMoreHtml = `
            <button type="button" class="es-pills-show-more">
                <span class="show-more-text">Show all (${totalPills - options.showTopN} more)</span>
                <span class="show-less-text" style="display: none;">Show less</span>
            </button>
        `;
        $pillsList.after(showMoreHtml);
        
        const $searchInput = $container.find('.es-pills-search-input');
        const $showMoreBtn = $container.find('.es-pills-show-more');
        
        console.log('Smart Pills initialized!', {
            total: totalPills,
            visible: options.showTopN,
            hidden: totalPills - options.showTopN
        });
        
        // Search functionality
        $searchInput.on('input', function() {
            const searchTerm = $(this).val().toLowerCase();
            
            if (searchTerm === '') {
                // Reset to initial state
                $pills.removeClass('es-pill-filtered');
                $pills.each(function(index) {
                    if (index >= options.showTopN) {
                        $(this).addClass('es-pill-hidden');
                    } else {
                        $(this).removeClass('es-pill-hidden');
                    }
                });
                $showMoreBtn.show();
                $container.find('.es-pills-no-results').remove();
            } else {
                // Filter pills
                let visibleCount = 0;
                $pills.each(function() {
                    const pillText = $(this).find('span').last().text().toLowerCase();
                    if (pillText.includes(searchTerm)) {
                        $(this).removeClass('es-pill-hidden').addClass('es-pill-filtered');
                        visibleCount++;
                    } else {
                        $(this).addClass('es-pill-hidden').removeClass('es-pill-filtered');
                    }
                });
                
                // Hide show more button when searching
                $showMoreBtn.hide();
                
                // Show "no results" message
                $container.find('.es-pills-no-results').remove();
                if (visibleCount === 0) {
                    $pillsList.after('<p class="es-pills-no-results">No results found</p>');
                }
            }
        });
        
        // Show more/less functionality
        $showMoreBtn.on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const isExpanded = $pillsList.hasClass('expanded');
            
            if (isExpanded) {
                // Collapse
                $pills.each(function(index) {
                    if (index >= options.showTopN) {
                        $(this).addClass('es-pill-hidden');
                    }
                });
                $pillsList.removeClass('expanded');
                $(this).find('.show-more-text').show();
                $(this).find('.show-less-text').hide();
            } else {
                // Expand
                $pills.removeClass('es-pill-hidden');
                $pillsList.addClass('expanded');
                $(this).find('.show-more-text').hide();
                $(this).find('.show-less-text').show();
            }
        });
    }
    
    // Initialize smart pills when wizard step 3 becomes visible
    $(document).on('click', '.es-timeline-step[data-step="3"]', function() {
        setTimeout(function() {
            initSmartPills();
            initVenueSelectionOnce();
            initArtistTimeFields();
        }, 100);
    });
    
    // Also init when wizard next button reaches step 3
    $(document).on('click', '.es-wizard-next', function() {
        setTimeout(function() {
            if ($('.es-form-section[data-step="3"]').is(':visible')) {
                initSmartPills();
                initVenueSelectionOnce();
                initArtistTimeFields();
            }
        }, 100);
    });
    
    // Track if venue selection was already initialized
    let venueSelectionInitialized = false;
    let currentLocationId = null;
    
    /**
     * Initialize Venue Selection only once per location
     */
    function initVenueSelectionOnce() {
        const selectedLocationId = $('input[name="event_location"]:checked').val();
        
        // Only reinitialize if location actually changed or not yet initialized
        if (venueSelectionInitialized && currentLocationId === selectedLocationId) {
            console.log('🏠 Venue selection already initialized for this location, skipping');
            return;
        }
        
        initVenueSelection();
    }
    
    /**
     * Initialize Venue Selection for Multivenue Locations
     */
    function initVenueSelection() {
        // Get genres HTML for venue toggles
        const genresHtml = $('#es-genre-pills').length ? $('#es-genre-pills').html() : '';
        
        // Listen to location selection changes
        $(document).off('change', 'input[name="event_location"]').on('change', 'input[name="event_location"]', function() {
            const $selectedPill = $(this).closest('.es-pill');
            const newLocationId = $(this).val();
            const isMultivenue = $selectedPill.data('multivenue') === 1 || $selectedPill.data('multivenue') === '1';
            const venuesData = $selectedPill.data('venues');
            
            const $venueSelection = $('#es-venue-selection');
            const $venueToggles = $('#es-venue-toggles');
            const $globalGenres = $('#es-global-genres');
            
            // Check if toggles already exist for THIS location
            const togglesExist = $venueToggles.children().length > 0;
            const sameLocation = currentLocationId === newLocationId;
            
            console.log('🏠 Location change:', {
                newLocationId,
                currentLocationId,
                sameLocation,
                togglesExist,
                isMultivenue
            });
            
            // If toggles exist and it's the same location, DON'T rebuild - just return
            if (togglesExist && sameLocation && isMultivenue) {
                console.log('🏠 Toggles already exist for this location, keeping them');
                $venueSelection.slideDown();
                return;
            }
            
            // Save current config BEFORE rebuilding
            let savedConfig = null;
            const currentConfigStr = $('#es-venue-config').val();
            if (currentConfigStr && currentConfigStr !== '{}') {
                try {
                    savedConfig = JSON.parse(currentConfigStr);
                    console.log('🏠 Saved config from hidden field:', savedConfig);
                } catch(e) {
                    console.log('🏠 Could not parse config:', currentConfigStr);
                }
            }
            
            // Update current location tracker
            currentLocationId = newLocationId;
            venueSelectionInitialized = true;
            
            if (isMultivenue && venuesData && venuesData.length > 0) {
                // Build venue toggles HTML
                let togglesHtml = '';
                venuesData.forEach(function(venue, index) {
                    const capacityText = venue.capacity ? ` (${venue.capacity} Pers.)` : '';
                    const venueId = venue.name.toLowerCase().replace(/[^a-z0-9]/g, '-');
                    
                    togglesHtml += `
                    <div class="es-venue-toggle-item" data-venue-name="${escapeHtml(venue.name)}">
                        <div class="es-venue-toggle-header">
                            <label class="es-toggle-switch es-venue-toggle-switch">
                                <input type="checkbox" 
                                       class="es-venue-checkbox" 
                                       name="venue_enabled[${escapeHtml(venue.name)}]"
                                       value="1">
                                <span class="es-toggle-slider"></span>
                            </label>
                            <div class="es-venue-info">
                                <span class="es-venue-original-name">${escapeHtml(venue.name)}${capacityText}</span>
                                <div class="es-venue-custom-name-wrap" style="display: none;">
                                    <input type="text" 
                                           class="es-venue-custom-name" 
                                           name="venue_custom_name[${escapeHtml(venue.name)}]"
                                           placeholder="${esWizardL10n && esWizardL10n.customVenueName ? esWizardL10n.customVenueName : 'Custom name for this event...'}"
                                           value="">
                                </div>
                            </div>
                        </div>
                        <div class="es-venue-genres" style="display: none;">
                            <label class="es-venue-genres-label">${esWizardL10n && esWizardL10n.genresForRoom ? esWizardL10n.genresForRoom : 'Genres for this room'}:</label>
                            <div class="es-pill-group es-venue-genre-pills" data-venue="${escapeHtml(venue.name)}">
                                ${genresHtml.replace(/name="event_genres\[\]"/g, 'name="venue_genres[' + escapeHtml(venue.name) + '][]"')}
                            </div>
                        </div>
                    </div>`;
                });
                
                $venueToggles.html(togglesHtml);
                $venueSelection.slideDown();
                
                // Hide global genres pill group but keep artist genres option visible if needed
                $globalGenres.find('#es-genre-pills').slideUp();
                $globalGenres.find('.es-pill-add[data-es-quick-add="genre"]').slideUp();
                
                // Also update artist venue dropdowns
                updateArtistVenueDropdowns(venuesData);
                
                // Init venue toggle handlers
                initVenueToggleHandlers();
                
                // Init sortable for venue genre pills
                setTimeout(initVenueGenreSortable, 50);
                
                // Restore saved config if we had one (for same location rebuild OR loading from DB)
                if (savedConfig && Object.keys(savedConfig).length > 0) {
                    console.log('🏠 Restoring config after rebuild:', savedConfig);
                    setTimeout(function() {
                        applyVenueConfig(savedConfig);
                    }, 100);
                }
            } else {
                $venueSelection.slideUp();
                $venueToggles.empty();
                // Show global genres again
                $globalGenres.find('#es-genre-pills').slideDown();
                $globalGenres.find('.es-pill-add[data-es-quick-add="genre"]').slideDown();
                // Hide artist venue dropdowns
                $('.es-artist-venue').hide();
                // Clear venue config
                $('#es-venue-config').val('{}');
            }
        });
        
        // Trigger change if location already selected (for edit mode) - only if toggles don't exist yet
        const $selectedLocation = $('input[name="event_location"]:checked');
        if ($selectedLocation.length > 0 && $('#es-venue-toggles').children().length === 0) {
            $selectedLocation.trigger('change');
        }
    }
    
    /**
     * Initialize Venue Toggle Handlers
     */
    function initVenueToggleHandlers() {
        // Handle venue toggle changes
        $(document).off('change', '.es-venue-checkbox').on('change', '.es-venue-checkbox', function() {
            const $item = $(this).closest('.es-venue-toggle-item');
            const $customNameWrap = $item.find('.es-venue-custom-name-wrap');
            const $genres = $item.find('.es-venue-genres');
            const isChecked = $(this).is(':checked');
            
            if (isChecked) {
                $item.addClass('es-venue-active');
                $customNameWrap.slideDown();
                $genres.slideDown();
            } else {
                $item.removeClass('es-venue-active');
                $customNameWrap.slideUp();
                $genres.slideUp();
            }
            
            // Update hidden venue config
            updateVenueConfig();
            
            // Update artist venue options to only show active venues
            updateArtistVenueOptions();
        });
        
        // Handle custom name input changes
        $(document).off('input', '.es-venue-custom-name').on('input', '.es-venue-custom-name', function() {
            updateVenueConfig();
            updateArtistVenueOptions();
        });
        
        // Handle venue genre checkbox changes
        $(document).off('change', '.es-venue-genre-pills input').on('change', '.es-venue-genre-pills input', function() {
            updateVenueConfig();
        });
    }
    
    /**
     * Update Venue Configuration JSON
     */
    function updateVenueConfig() {
        const config = {};
        
        $('.es-venue-toggle-item').each(function() {
            const $item = $(this);
            const venueName = $item.data('venue-name');
            const isEnabled = $item.find('.es-venue-checkbox').is(':checked');
            
            if (isEnabled) {
                config[venueName] = {
                    enabled: true,
                    customName: $item.find('.es-venue-custom-name').val() || '',
                    genres: []
                };
                
                // Collect selected genres for this venue
                $item.find('.es-venue-genre-pills input:checked').each(function() {
                    config[venueName].genres.push($(this).val());
                });
            }
        });
        
        const configJson = JSON.stringify(config);
        console.log('🏠 updateVenueConfig:', config, 'JSON:', configJson);
        
        $('#es-venue-config').val(configJson);
        
        // Also set the first active venue in the old field for backward compatibility
        const activeVenues = Object.keys(config);
        $('#es-event-venue').val(activeVenues.length > 0 ? activeVenues[0] : '');
    }
    
    /**
     * Update Artist Venue Options to only show active venues
     */
    function updateArtistVenueOptions() {
        const activeVenues = [];
        
        $('.es-venue-toggle-item').each(function() {
            const $item = $(this);
            if ($item.find('.es-venue-checkbox').is(':checked')) {
                const venueName = $item.data('venue-name');
                const customName = $item.find('.es-venue-custom-name').val();
                activeVenues.push({
                    name: venueName,
                    display: customName || venueName
                });
            }
        });
        
        // Update all artist venue dropdowns
        $('.es-artist-venue, .es-popover-venue').each(function() {
            const $select = $(this);
            const currentVal = $select.val();
            $select.empty().append('<option value="">Raum</option>');
            
            activeVenues.forEach(function(venue) {
                $select.append(`<option value="${escapeHtml(venue.name)}">${escapeHtml(venue.display)}</option>`);
            });
            
            // Restore previous value if still valid
            if (currentVal && activeVenues.some(v => v.name === currentVal)) {
                $select.val(currentVal);
            }
        });
    }
    
    /**
     * Apply saved venue configuration when editing an event
     */
    function applyVenueConfig(venueConfig) {
        console.log('🏠 applyVenueConfig called with:', venueConfig);
        
        if (!venueConfig || typeof venueConfig !== 'object') {
            console.log('🏠 No valid venue config');
            return;
        }
        
        const $toggleItems = $('.es-venue-toggle-item');
        console.log('🏠 Found toggle items:', $toggleItems.length);
        
        // Apply settings to each venue toggle
        Object.keys(venueConfig).forEach(function(venueName) {
            const config = venueConfig[venueName];
            console.log('🏠 Processing venue:', venueName, config);
            
            const $item = $(`.es-venue-toggle-item[data-venue-name="${venueName}"]`);
            console.log('🏠 Found item for', venueName, ':', $item.length);
            
            if ($item.length && config.enabled) {
                // Enable the venue (this triggers the change handler which shows fields)
                const $checkbox = $item.find('.es-venue-checkbox');
                $checkbox.prop('checked', true);
                
                // Manually add active class and show fields (don't rely on animation)
                $item.addClass('es-venue-active');
                $item.find('.es-venue-custom-name-wrap').show();
                $item.find('.es-venue-genres').show();
                
                // Set custom name
                if (config.customName) {
                    $item.find('.es-venue-custom-name').val(config.customName);
                    console.log('🏠 Set custom name:', config.customName);
                }
                
                // Set genres
                if (config.genres && config.genres.length > 0) {
                    $item.find('.es-venue-genre-pills input').prop('checked', false);
                    config.genres.forEach(function(genreId) {
                        $item.find(`.es-venue-genre-pills input[value="${genreId}"]`).prop('checked', true);
                    });
                }
            }
        });
        
        // Update venue config hidden field
        updateVenueConfig();
        
        // Update artist venue options
        updateArtistVenueOptions();
    }
    
    /**
     * Update Artist Venue Dropdowns
     */
    function updateArtistVenueDropdowns(venues) {
        $('.es-artist-venue').each(function() {
            const $select = $(this);
            $select.empty().append('<option value="">Raum</option>');
            venues.forEach(function(venue) {
                $select.append(`<option value="${escapeHtml(venue.name)}">${escapeHtml(venue.name)}</option>`);
            });
            // Show venue dropdown for checked artists
            if ($select.closest('.es-artist-item').find('input[type="checkbox"]').is(':checked')) {
                $select.show();
            }
        });
    }
    
    /**
     * Populate Artist Selections (checkboxes, times, venues)
     * Called after location venues are loaded
     */
    function populateArtistSelections(event) {
        // Get if current location is multivenue
        const $selectedLocation = $('input[name="event_location"]:checked').closest('.es-pill');
        const isMultivenue = $selectedLocation.length && 
            ($selectedLocation.data('multivenue') === 1 || $selectedLocation.data('multivenue') === '1' || $selectedLocation.data('multivenue') === true);
        
        $('#es-artist-selection .es-artist-pill').each(function() {
            const $pill = $(this);
            const artistId = $pill.data('artist-id');
            const $checkbox = $pill.find('.es-artist-checkbox');
            const $timeInput = $pill.find('.es-artist-time');
            const $venueInput = $pill.find('.es-artist-venue');
            const $sessionTitleInput = $pill.find('.es-artist-session-title');
            
            // Check if this artist is in the event
            const isSelected = event.artist_ids && (
                Array.isArray(event.artist_ids) 
                    ? event.artist_ids.includes(artistId) || event.artist_ids.includes(String(artistId)) || event.artist_ids.includes(parseInt(artistId))
                    : event.artist_ids == artistId
            );
            
            $checkbox.prop('checked', isSelected);
            
            if (isSelected) {
                // Set artist time if available
                if (event.artist_times && (event.artist_times[artistId] || event.artist_times[String(artistId)])) {
                    const timeVal = event.artist_times[artistId] || event.artist_times[String(artistId)];
                    $timeInput.val(timeVal);
                }
                
                // Set artist venue if available
                if (event.artist_venues) {
                    const venueVal = event.artist_venues[artistId] || event.artist_venues[String(artistId)];
                    if (venueVal) {
                        $venueInput.val(venueVal);
                    }
                }
                
                // Set artist session title if available
                if (event.artist_session_titles) {
                    const titleVal = event.artist_session_titles[artistId] || event.artist_session_titles[String(artistId)];
                    if (titleVal) {
                        $sessionTitleInput.val(titleVal);
                    }
                }
            } else {
                $timeInput.val('');
                $venueInput.val('');
                $sessionTitleInput.val('');
            }
            
            // Update indicators
            updatePillIndicators($pill);
        });
    }
    
    /**
     * Initialize Artist Pills with Popover
     */
    function initArtistTimeFields() {
        let currentPopoverPill = null;
        const $popover = $('#es-artist-popover');
        
        // Click on pill label to toggle selection
        $(document).off('click', '.es-artist-pill-label').on('click', '.es-artist-pill-label', function(e) {
            const $pill = $(this).closest('.es-artist-pill');
            const $checkbox = $pill.find('.es-artist-checkbox');
            
            // Toggle checkbox
            $checkbox.prop('checked', !$checkbox.is(':checked')).trigger('change');
        });
        
        // Checkbox change handler
        $(document).off('change', '.es-artist-pill .es-artist-checkbox').on('change', '.es-artist-pill .es-artist-checkbox', function() {
            const $pill = $(this).closest('.es-artist-pill');
            
            if (!$(this).is(':checked')) {
                // Clear values when unchecked
                $pill.find('.es-artist-time').val('');
                $pill.find('.es-artist-venue').val('');
                updatePillIndicators($pill);
                closePopover();
            }
        });
        
        // Click on edit button to open popover
        $(document).off('click', '.es-artist-pill-edit').on('click', '.es-artist-pill-edit', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const $pill = $(this).closest('.es-artist-pill');
            openPopover($pill);
        });
        
        // Close popover
        $(document).off('click', '.es-popover-close').on('click', '.es-popover-close', function() {
            closePopover();
        });
        
        // OK button
        $(document).off('click', '.es-popover-ok').on('click', '.es-popover-ok', function() {
            savePopoverValues();
            closePopover();
        });
        
        // Remove button
        $(document).off('click', '.es-popover-remove').on('click', '.es-popover-remove', function() {
            if (currentPopoverPill) {
                currentPopoverPill.find('.es-artist-checkbox').prop('checked', false).trigger('change');
            }
            closePopover();
        });
        
        // Close popover when clicking outside
        $(document).off('click.popover').on('click.popover', function(e) {
            if ($popover.is(':visible') && !$(e.target).closest('.es-artist-popover, .es-artist-pill-edit').length) {
                closePopover();
            }
        });
        
        // Enter key in popover closes it
        $popover.off('keypress').on('keypress', 'input', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                savePopoverValues();
                closePopover();
            }
        });
        
        // Close popover on scroll
        $(window).off('scroll.popover').on('scroll.popover', function() {
            if ($popover.is(':visible')) {
                closePopover();
            }
        });
        
        // Also close on modal scroll
        $('.es-wizard-modal-body').off('scroll.popover').on('scroll.popover', function() {
            if ($popover.is(':visible')) {
                closePopover();
            }
        });
        
        function openPopover($pill) {
            currentPopoverPill = $pill;
            
            const artistName = $pill.find('.es-artist-pill-name').text();
            const artistTime = $pill.find('.es-artist-time').val() || '';
            const artistVenue = $pill.find('.es-artist-venue').val() || '';
            const artistSessionTitle = $pill.find('.es-artist-session-title').val() || '';
            
            // Set popover values
            $popover.find('.es-popover-title').text(artistName);
            $popover.find('#es-popover-session-title').val(artistSessionTitle);
            $popover.find('#es-popover-time').val(artistTime);
            $popover.find('#es-popover-venue').val(artistVenue);
            
            // Check if location is multivenue
            const $selectedLocation = $('input[name="event_location"]:checked').closest('.es-pill');
            const isMultivenue = $selectedLocation.length && 
                ($selectedLocation.data('multivenue') === 1 || $selectedLocation.data('multivenue') === '1' || $selectedLocation.data('multivenue') === true);
            
            if (isMultivenue) {
                // Populate venue dropdown
                const venues = $selectedLocation.data('venues') || [];
                const $venueSelect = $popover.find('#es-popover-venue');
                $venueSelect.find('option:not(:first)').remove();
                
                if (Array.isArray(venues)) {
                    venues.forEach(function(venue) {
                        const venueName = typeof venue === 'object' ? venue.name : venue;
                        $venueSelect.append($('<option>').val(venueName).text(venueName));
                    });
                }
                
                $venueSelect.val(artistVenue);
                $popover.find('.es-popover-venue-field').show();
            } else {
                $popover.find('.es-popover-venue-field').hide();
            }
            
            // Position popover - check if enough space below, otherwise open above
            const pillRect = $pill[0].getBoundingClientRect();
            const popoverWidth = 260;
            const popoverHeight = isMultivenue ? 220 : 150; // Approximate height
            const viewportHeight = window.innerHeight;
            const spaceBelow = viewportHeight - pillRect.bottom;
            const spaceAbove = pillRect.top;
            
            // Calculate left position, keeping popover within viewport
            let leftPos = pillRect.left;
            if (leftPos + popoverWidth > window.innerWidth - 20) {
                leftPos = window.innerWidth - popoverWidth - 20;
            }
            if (leftPos < 10) {
                leftPos = 10;
            }
            
            let topPos;
            if (spaceBelow >= popoverHeight + 20 || spaceBelow >= spaceAbove) {
                // Open below
                topPos = pillRect.bottom + 10;
                $popover.removeClass('es-popover-above').addClass('es-popover-below');
            } else {
                // Open above
                topPos = pillRect.top - popoverHeight - 10;
                $popover.removeClass('es-popover-below').addClass('es-popover-above');
            }
            
            $popover.css({
                top: topPos,
                left: leftPos
            }).fadeIn(150);
            
            // Focus time input
            setTimeout(function() {
                $popover.find('#es-popover-time').focus();
            }, 100);
        }
        
        function closePopover() {
            $popover.fadeOut(100);
            currentPopoverPill = null;
        }
        
        function savePopoverValues() {
            if (!currentPopoverPill) return;
            
            const sessionTitle = $popover.find('#es-popover-session-title').val();
            const time = $popover.find('#es-popover-time').val();
            const venue = $popover.find('#es-popover-venue').val();
            
            currentPopoverPill.find('.es-artist-session-title').val(sessionTitle);
            currentPopoverPill.find('.es-artist-time').val(time);
            currentPopoverPill.find('.es-artist-venue').val(venue);
            
            updatePillIndicators(currentPopoverPill);
            
            // Auto-activate genres for the venue based on this artist
            if (venue) {
                const artistGenres = currentPopoverPill.data('artist-genres');
                if (artistGenres && Array.isArray(artistGenres) && artistGenres.length > 0) {
                    activateGenresForVenue(venue, artistGenres);
                }
            }
        }
        
        // Initialize indicators for all pills
        $('.es-artist-pill').each(function() {
            updatePillIndicators($(this));
        });
    }
    
    /**
     * Auto-activate genres for a venue based on artist genres
     */
    function activateGenresForVenue(venueName, genreIds) {
        const $venueItem = $(`.es-venue-toggle-item[data-venue-name="${venueName}"]`);
        if (!$venueItem.length) return;
        
        // Make sure the venue is enabled
        const $checkbox = $venueItem.find('.es-venue-checkbox');
        if (!$checkbox.is(':checked')) {
            $checkbox.prop('checked', true).trigger('change');
        }
        
        // Activate the genres
        const $genrePills = $venueItem.find('.es-venue-genre-pills');
        genreIds.forEach(function(genreId) {
            const $genreCheckbox = $genrePills.find(`input[value="${genreId}"]`);
            if ($genreCheckbox.length && !$genreCheckbox.is(':checked')) {
                $genreCheckbox.prop('checked', true);
                console.log('🎸 Auto-activated genre', genreId, 'for venue', venueName);
            }
        });
        
        // Update venue config
        updateVenueConfig();
    }
    
    /**
     * Recalculate all venue genres based on assigned artists
     * Call this to sync genres with artist assignments
     */
    function syncVenueGenresFromArtists() {
        // Collect all artist-venue assignments
        const venueArtists = {};
        
        $('#es-artist-selection .es-artist-pill').each(function() {
            const $pill = $(this);
            const isSelected = $pill.find('.es-artist-checkbox').is(':checked');
            if (!isSelected) return;
            
            const venue = $pill.find('.es-artist-venue').val();
            if (!venue) return;
            
            const artistGenres = $pill.data('artist-genres');
            if (!artistGenres || !Array.isArray(artistGenres)) return;
            
            if (!venueArtists[venue]) {
                venueArtists[venue] = [];
            }
            venueArtists[venue] = venueArtists[venue].concat(artistGenres);
        });
        
        // Apply genres to each venue
        Object.keys(venueArtists).forEach(function(venueName) {
            const genreIds = [...new Set(venueArtists[venueName])]; // Remove duplicates
            activateGenresForVenue(venueName, genreIds);
        });
        
        console.log('🎸 Synced venue genres from artists:', venueArtists);
    }
    
    // Expose sync function globally
    window.syncVenueGenresFromArtists = syncVenueGenresFromArtists;
    
    // Sync button click handler
    $(document).on('click', '#es-sync-venue-genres', function(e) {
        e.preventDefault();
        syncVenueGenresFromArtists();
        
        // Visual feedback
        const $btn = $(this);
        const $icon = $btn.find('.dashicons');
        $icon.addClass('es-spin');
        
        setTimeout(function() {
            $icon.removeClass('es-spin');
            showToast('Genres synchronisiert!', 'success');
        }, 500);
    });
    
    /**
     * Update pill indicators (T for time, R for room)
     */
    function updatePillIndicators($pill) {
        const time = $pill.find('.es-artist-time').val();
        const venue = $pill.find('.es-artist-venue').val();
        
        $pill.find('.es-indicator-time').toggleClass('active', !!time);
        $pill.find('.es-indicator-venue').toggleClass('active', !!venue);
    }
    
    /**
     * Helper: Escape HTML
     */
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // ========================================
    // FEATURE: DRAG & DROP ARTIST SORTING
    // ========================================
    
    function initArtistSortable() {
        if ($.fn.sortable && $('#es-artist-selection').length) {
            $('#es-artist-selection').sortable({
                items: '.es-artist-pill',
                handle: '.es-artist-pill-label',
                placeholder: 'es-artist-pill-placeholder',
                tolerance: 'pointer',
                cursor: 'grabbing',
                opacity: 0.8,
                update: function(event, ui) {
                    // Update hidden order field
                    updateArtistOrder();
                    hasUnsavedChanges = true;
                    console.log('🎵 Artist order updated');
                }
            });
            
            // Add grab cursor on hover
            $('#es-artist-selection').on('mouseenter', '.es-artist-pill-label', function() {
                $(this).css('cursor', 'grab');
            });
        }
    }
    
    function updateArtistOrder() {
        const order = [];
        $('#es-artist-selection .es-artist-pill').each(function(index) {
            const artistId = $(this).data('artist-id');
            if (artistId) {
                order.push(artistId);
            }
        });
        
        // Store order in hidden field
        if (!$('#es-artist-order').length) {
            $('#es-event-form').append('<input type="hidden" id="es-artist-order" name="artist_order" value="">');
        }
        $('#es-artist-order').val(order.join(','));
    }
    
    // ========================================
    // FEATURE: DRAG & DROP GENRE SORTING
    // ========================================
    
    function initGenreSortable() {
        if (!$.fn.sortable) return;
        
        // Global genre pills
        if ($('#es-genre-pills').length) {
            $('#es-genre-pills').sortable({
                items: '.es-pill',
                placeholder: 'es-pill-placeholder',
                tolerance: 'pointer',
                cursor: 'grabbing',
                opacity: 0.8,
                update: function(event, ui) {
                    hasUnsavedChanges = true;
                    console.log('🎸 Global genre order updated');
                }
            });
        }
        
        // Venue-specific genre pills (use document delegation for dynamic content)
        initVenueGenreSortable();
    }
    
    function initVenueGenreSortable() {
        if (!$.fn.sortable) return;
        
        // Initialize sortable on all venue genre pill groups
        $('.es-venue-genre-pills').each(function() {
            const $pillGroup = $(this);
            
            // Skip if already initialized
            if ($pillGroup.hasClass('ui-sortable')) return;
            
            $pillGroup.sortable({
                items: '.es-pill',
                placeholder: 'es-pill-placeholder',
                tolerance: 'pointer',
                cursor: 'grabbing',
                opacity: 0.8,
                update: function(event, ui) {
                    // Update venue config when order changes
                    updateVenueConfig();
                    hasUnsavedChanges = true;
                    console.log('🎸 Venue genre order updated for:', $pillGroup.data('venue'));
                }
            });
        });
    }
    
    // ========================================
    // FEATURE: KEYBOARD SHORTCUTS
    // ========================================
    
    function initKeyboardShortcuts() {
        $(document).on('keydown', function(e) {
            // Ctrl+S or Cmd+S = Save (without closing)
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                
                // Only save if form is visible
                if ($('#es-event-form').is(':visible')) {
                    saveEvent(false); // Save without closing
                }
            }
            
            // ESC = Close popover or modal
            if (e.key === 'Escape') {
                // Close artist popover
                if ($('.es-artist-popover').is(':visible')) {
                    closePopover();
                }
                
                // Close quick-add modal
                if ($('#es-quick-modal-overlay').hasClass('active')) {
                    $('#es-quick-modal-close').click();
                }
            }
        });
        
        console.log('⌨️ Keyboard shortcuts initialized (Ctrl+S, ESC)');
    }
    
    // ========================================
    // FEATURE: AUTOSAVE (DRAFT)
    // ========================================
    
    let autosaveTimer = null;
    let lastAutosave = null;
    const AUTOSAVE_INTERVAL = 60000; // 60 seconds
    
    function initAutosave() {
        // Only autosave if there's an event being edited
        $('#es-event-form').on('input change', 'input, textarea, select', function() {
            hasUnsavedChanges = true;
            scheduleAutosave();
        });
        
        console.log('💾 Autosave initialized (60s interval)');
    }
    
    function scheduleAutosave() {
        // Clear existing timer
        if (autosaveTimer) {
            clearTimeout(autosaveTimer);
        }
        
        // Schedule new autosave
        autosaveTimer = setTimeout(function() {
            performAutosave();
        }, AUTOSAVE_INTERVAL);
    }
    
    function performAutosave() {
        // Don't autosave if no changes or no title
        const title = $('#es-event-title').val();
        if (!hasUnsavedChanges || !title || title.length < 3) {
            return;
        }
        
        // Don't autosave too frequently
        const now = Date.now();
        if (lastAutosave && (now - lastAutosave) < 30000) {
            return;
        }
        
        console.log('💾 Autosaving...');
        
        // Collect form data
        const formData = new FormData($('#es-event-form')[0]);
        formData.append('action', 'es_autosave_event');
        formData.append('nonce', ensembleAjax.nonce);
        formData.append('is_autosave', '1');
        
        $.ajax({
            url: ensembleAjax.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    lastAutosave = Date.now();
                    
                    // If this was a new event, update the event ID
                    if (response.data.event_id && !$('#es-event-id').val()) {
                        $('#es-event-id').val(response.data.event_id);
                        showToast('Entwurf gespeichert', 'success');
                    } else {
                        // Existing event - just show "saved"
                        showToast('Automatisch gespeichert', 'success');
                    }
                    console.log('💾 Autosave successful');
                }
            },
            error: function() {
                console.log('💾 Autosave failed');
            }
        });
    }
    
    // ========================================
    // FEATURE: LOCATION CONFLICT CHECK
    // ========================================
    
    function initLocationConflictCheck() {
        // Check when date or location changes
        $('#es-event-date, #es-event-time-start, #es-event-time-end').on('change', checkLocationConflict);
        $('input[name="event_location"]').on('change', checkLocationConflict);
        
        console.log('🔍 Location conflict check initialized');
    }
    
    function checkLocationConflict() {
        const date = $('#es-event-date').val();
        const timeStart = $('#es-event-time-start').val();
        const timeEnd = $('#es-event-time-end').val();
        const locationId = $('input[name="event_location"]:checked').val();
        const currentEventId = $('#es-event-id').val();
        
        // Need at least date and location
        if (!date || !locationId) {
            hideConflictWarning();
            return;
        }
        
        $.ajax({
            url: ensembleAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'es_check_location_conflict',
                nonce: ensembleAjax.nonce,
                date: date,
                time_start: timeStart,
                time_end: timeEnd,
                location_id: locationId,
                exclude_event_id: currentEventId
            },
            success: function(response) {
                if (response.success && response.data.has_conflict) {
                    showConflictWarning(response.data.conflicts);
                } else {
                    hideConflictWarning();
                }
            }
        });
    }
    
    function showConflictWarning(conflicts) {
        // Remove existing warning
        hideConflictWarning();
        
        let conflictHtml = '<div class="es-conflict-warning">';
        conflictHtml += '<span class="dashicons dashicons-warning"></span>';
        conflictHtml += '<div class="es-conflict-content">';
        conflictHtml += '<strong>⚠️ Location bereits belegt!</strong>';
        conflictHtml += '<ul>';
        
        conflicts.forEach(function(conflict) {
            conflictHtml += '<li>' + escapeHtml(conflict.title) + ' (' + conflict.time + ')</li>';
        });
        
        conflictHtml += '</ul>';
        conflictHtml += '</div></div>';
        
        // Insert after location selection
        $('#es-location-pills').after(conflictHtml);
    }
    
    function hideConflictWarning() {
        $('.es-conflict-warning').remove();
    }
    
    // ========================================
    // TOAST NOTIFICATION HELPER
    // ========================================
    
    function showToast(message, type = 'info') {
        // Remove existing toast
        $('.es-toast').remove();
        
        const bgColor = {
            'success': '#10b981',
            'error': '#ef4444',
            'warning': '#f59e0b',
            'info': '#3b82f6'
        }[type] || '#3b82f6';
        
        const icon = {
            'success': 'yes',
            'error': 'no',
            'warning': 'warning',
            'info': 'info'
        }[type] || 'info';
        
        const toast = $(`
            <div class="es-toast" style="
                position: fixed;
                bottom: 30px;
                right: 30px;
                background: ${bgColor};
                color: white;
                padding: 12px 20px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 100000;
                display: flex;
                align-items: center;
                gap: 10px;
                animation: es-slideUp 0.3s ease;
            ">
                <span class="dashicons dashicons-${icon}"></span>
                ${escapeHtml(message)}
            </div>
        `);
        
        $('body').append(toast);
        
        setTimeout(function() {
            toast.fadeOut(300, function() {
                $(this).remove();
            });
        }, 3000);
    }
    
    // ========================================
    // FEATURE: BULK ACTIONS
    // ========================================
    
    let selectedEventIds = new Set();
    
    function initBulkActions() {
        // Handle calendar event selection (Shift+Click or Ctrl+Click)
        $(document).on('click', '.fc-event', function(e) {
            if (e.shiftKey || e.ctrlKey || e.metaKey) {
                e.preventDefault();
                e.stopPropagation();
                
                const $event = $(this);
                const eventId = $event.data('event-id') || $event.closest('[data-event-id]').data('event-id');
                
                if (!eventId) return;
                
                toggleEventSelection(eventId, $event);
            }
        });
        
        // Handle event card checkbox selection
        $(document).on('change', '.es-event-select', function(e) {
            e.stopPropagation();
            const eventId = $(this).data('event-id');
            const $card = $(this).closest('.es-event-card');
            
            if ($(this).is(':checked')) {
                selectedEventIds.add(eventId);
                $card.addClass('es-selected');
            } else {
                selectedEventIds.delete(eventId);
                $card.removeClass('es-selected');
            }
            
            updateBulkActionsUI();
        });
        
        // Select all visible events
        $(document).on('click', '#es-select-all-events', function(e) {
            e.preventDefault();
            const allChecked = $('.es-event-select:visible').length === $('.es-event-select:visible:checked').length;
            
            $('.es-event-select:visible').each(function() {
                const eventId = $(this).data('event-id');
                const $card = $(this).closest('.es-event-card');
                
                if (allChecked) {
                    $(this).prop('checked', false);
                    selectedEventIds.delete(eventId);
                    $card.removeClass('es-selected');
                } else {
                    $(this).prop('checked', true);
                    selectedEventIds.add(eventId);
                    $card.addClass('es-selected');
                }
            });
            
            updateBulkActionsUI();
        });
        
        // Quick select by status from filter dropdown
        $('#es-filter-status').on('dblclick', function() {
            const status = $(this).val();
            if (!status) return;
            
            // Select all events with this status
            $('.es-event-card').each(function() {
                const eventStatus = $(this).data('event-status');
                const eventId = $(this).data('event-id');
                const $checkbox = $(this).find('.es-event-select');
                
                if (eventStatus === status) {
                    $checkbox.prop('checked', true);
                    selectedEventIds.add(eventId);
                    $(this).addClass('es-selected');
                }
            });
            
            updateBulkActionsUI();
            showToast('Alle "' + $('#es-filter-status option:selected').text() + '" Events ausgewählt', 'info');
        });
        
        // Apply bulk action
        $('#es-bulk-apply').on('click', function() {
            const action = $('#es-bulk-action-select').val();
            
            if (!action) {
                showToast('Bitte wähle eine Aktion', 'warning');
                return;
            }
            
            if (selectedEventIds.size === 0) {
                showToast('Keine Events ausgewählt', 'warning');
                return;
            }
            
            // Confirm destructive actions
            if (action === 'delete' || action === 'trash') {
                if (!confirm('Bist du sicher? Diese Aktion kann nicht rückgängig gemacht werden.')) {
                    return;
                }
            }
            
            performBulkAction(action, Array.from(selectedEventIds));
        });
        
        // Clear selection on escape
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && selectedEventIds.size > 0) {
                clearBulkSelection();
            }
        });
        
        // Clear selection button
        $(document).on('click', '#es-bulk-clear', function(e) {
            e.preventDefault();
            clearBulkSelection();
        });
        
        console.log('📦 Bulk actions initialized (Checkbox or Shift+Click)');
    }
    
    function toggleEventSelection(eventId, $element) {
        if (selectedEventIds.has(eventId)) {
            selectedEventIds.delete(eventId);
            $element.removeClass('es-selected');
        } else {
            selectedEventIds.add(eventId);
            $element.addClass('es-selected');
        }
        updateBulkActionsUI();
    }
    
    function updateBulkActionsUI() {
        const count = selectedEventIds.size;
        
        if (count > 0) {
            $('.es-bulk-selected-count').text(count);
        } else {
            $('.es-bulk-selected-count').text('');
        }
    }
    
    function clearBulkSelection() {
        selectedEventIds.clear();
        $('.fc-event').removeClass('es-selected');
        $('.es-event-card').removeClass('es-selected');
        $('.es-event-select').prop('checked', false);
        updateBulkActionsUI();
    }
    
    function performBulkAction(action, eventIds) {
        $.ajax({
            url: ensembleAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'es_bulk_event_action',
                nonce: ensembleAjax.nonce,
                bulk_action: action,
                event_ids: eventIds
            },
            success: function(response) {
                if (response.success) {
                    showToast(response.data.message, 'success');
                    clearBulkSelection();
                    
                    // Refresh event list
                    refreshEventList();
                    
                    // Refresh calendar if visible
                    if (window.ensembleCalendar) {
                        window.ensembleCalendar.refetchEvents();
                    }
                } else {
                    showToast(response.data.message || 'Fehler bei Bulk-Aktion', 'error');
                }
            },
            error: function() {
                showToast('Netzwerkfehler', 'error');
            }
        });
    }
    
    // Initialize new features
    $(document).ready(function() {
        initArtistSortable();
        initGenreSortable();
        initKeyboardShortcuts();
        initAutosave();
        initLocationConflictCheck();
        initBulkActions();
    });
    
})(jQuery);