/**
 * Ensemble Location Manager JavaScript
 * 
 * @package Ensemble
 */

(function($) {
    'use strict';
    
    // State
    let currentLocationId = null;
    let hasUnsavedChanges = false;
    let mediaUploader = null;
    let currentView = 'grid';
    let selectedLocations = [];
    
    // Initialize
    $(document).ready(function() {
        loadLocations();
        initToolbar();
        initViewToggle();
        initLocationForm();
        initImageUpload();
        initMediaFeatures();
        initModal();
        initMultivenue();
        initOpeningHours();
    });
    
    /**
     * Initialize Multivenue functionality
     */
    function initMultivenue() {
        // Toggle Multivenue section
        $('#es-location-multivenue').on('change', function() {
            if ($(this).is(':checked')) {
                $('#es-venues-section').slideDown();
                // Add first venue if empty
                if ($('#es-venues-list .es-venue-item').length === 0) {
                    addVenue();
                }
            } else {
                $('#es-venues-section').slideUp();
            }
            hasUnsavedChanges = true;
        });
        
        // Add venue button
        $('#es-add-venue-btn').on('click', function() {
            addVenue();
        });
        
        // Remove venue (delegated)
        $(document).on('click', '.es-remove-venue-btn', function() {
            $(this).closest('.es-venue-item').slideUp(200, function() {
                $(this).remove();
                reindexVenues();
            });
            hasUnsavedChanges = true;
        });
    }
    
    /**
     * Initialize Opening Hours functionality
     */
    function initOpeningHours() {
        // Toggle opening hours section
        $('#es-location-has-opening-hours').on('change', function() {
            if ($(this).is(':checked')) {
                $('#es-opening-hours-container').slideDown();
            } else {
                $('#es-opening-hours-container').slideUp();
            }
            hasUnsavedChanges = true;
        });
        
        // Toggle closed state for each day
        $(document).on('change', '.es-closed-toggle', function() {
            const $row = $(this).closest('.es-opening-hours-row');
            if ($(this).is(':checked')) {
                $row.addClass('is-closed');
                $row.find('.es-time-input').prop('disabled', true);
            } else {
                $row.removeClass('is-closed');
                $row.find('.es-time-input').prop('disabled', false);
            }
            hasUnsavedChanges = true;
        });
        
        // Copy Monday to all days
        $('#es-copy-monday-to-all').on('click', function() {
            const mondayOpen = $('.es-opening-hours-row[data-day="monday"] .es-time-open').val();
            const mondayClose = $('.es-opening-hours-row[data-day="monday"] .es-time-close').val();
            const mondayClosed = $('.es-opening-hours-row[data-day="monday"] .es-closed-toggle').is(':checked');
            
            $('.es-opening-hours-row').each(function() {
                $(this).find('.es-time-open').val(mondayOpen);
                $(this).find('.es-time-close').val(mondayClose);
                const $toggle = $(this).find('.es-closed-toggle');
                $toggle.prop('checked', mondayClosed);
                
                // Apply visual state
                if (mondayClosed) {
                    $(this).addClass('is-closed');
                    $(this).find('.es-time-input').prop('disabled', true);
                } else {
                    $(this).removeClass('is-closed');
                    $(this).find('.es-time-input').prop('disabled', false);
                }
            });
            hasUnsavedChanges = true;
        });
        
        // Copy Monday to weekdays only
        $('#es-copy-monday-to-weekdays').on('click', function() {
            const mondayOpen = $('.es-opening-hours-row[data-day="monday"] .es-time-open').val();
            const mondayClose = $('.es-opening-hours-row[data-day="monday"] .es-time-close').val();
            const mondayClosed = $('.es-opening-hours-row[data-day="monday"] .es-closed-toggle').is(':checked');
            
            const weekdays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
            weekdays.forEach(function(day) {
                const $row = $('.es-opening-hours-row[data-day="' + day + '"]');
                $row.find('.es-time-open').val(mondayOpen);
                $row.find('.es-time-close').val(mondayClose);
                const $toggle = $row.find('.es-closed-toggle');
                $toggle.prop('checked', mondayClosed);
                
                if (mondayClosed) {
                    $row.addClass('is-closed');
                    $row.find('.es-time-input').prop('disabled', true);
                } else {
                    $row.removeClass('is-closed');
                    $row.find('.es-time-input').prop('disabled', false);
                }
            });
            
            // Set Saturday and Sunday as closed
            ['saturday', 'sunday'].forEach(function(day) {
                const $row = $('.es-opening-hours-row[data-day="' + day + '"]');
                $row.find('.es-closed-toggle').prop('checked', true);
                $row.addClass('is-closed');
                $row.find('.es-time-input').prop('disabled', true);
            });
            hasUnsavedChanges = true;
        });
    }
    
    /**
     * Get opening hours data for saving
     */
    function getOpeningHoursData() {
        const hours = {};
        $('.es-opening-hours-row').each(function() {
            const day = $(this).data('day');
            hours[day] = {
                open: $(this).find('.es-time-open').val(),
                close: $(this).find('.es-time-close').val(),
                closed: $(this).find('.es-closed-toggle').is(':checked')
            };
        });
        return hours;
    }
    
    /**
     * Populate opening hours in form
     */
    function populateOpeningHours(data) {
        if (!data || !data.has_opening_hours) {
            $('#es-location-has-opening-hours').prop('checked', false);
            $('#es-opening-hours-container').hide();
            resetOpeningHoursFields();
            return;
        }
        
        $('#es-location-has-opening-hours').prop('checked', true);
        $('#es-opening-hours-container').show();
        $('#es-opening-hours-note').val(data.opening_hours_note || '');
        
        const hours = data.opening_hours || {};
        $('.es-opening-hours-row').each(function() {
            const day = $(this).data('day');
            if (hours[day]) {
                $(this).find('.es-time-open').val(hours[day].open || '');
                $(this).find('.es-time-close').val(hours[day].close || '');
                const isClosed = hours[day].closed === true || hours[day].closed === '1' || hours[day].closed === 1;
                $(this).find('.es-closed-toggle').prop('checked', isClosed);
                if (isClosed) {
                    $(this).addClass('is-closed');
                    $(this).find('.es-time-input').prop('disabled', true);
                } else {
                    $(this).removeClass('is-closed');
                    $(this).find('.es-time-input').prop('disabled', false);
                }
            }
        });
    }
    
    /**
     * Reset opening hours fields
     */
    function resetOpeningHoursFields() {
        $('#es-opening-hours-note').val('');
        $('.es-opening-hours-row').each(function() {
            $(this).find('.es-time-open').val('');
            $(this).find('.es-time-close').val('');
            $(this).find('.es-closed-toggle').prop('checked', false);
            $(this).removeClass('is-closed');
            $(this).find('.es-time-input').prop('disabled', false);
        });
    }
    
    /**
     * Reset opening hours completely
     */
    function resetOpeningHours() {
        $('#es-location-has-opening-hours').prop('checked', false);
        $('#es-opening-hours-container').hide();
        resetOpeningHoursFields();
    }
    
    /**
     * Add a new venue row
     */
    function addVenue(venueData = {}) {
        const index = $('#es-venues-list .es-venue-item').length;
        const venueName = venueData.name || '';
        const venueCapacity = venueData.capacity || '';
        
        // Strings mit Fallback
        const strings = (typeof ensembleAjax !== 'undefined' && ensembleAjax.strings) ? ensembleAjax.strings : {};
        const labelName = strings.venueName || 'Raum-Name';
        const labelCapacity = strings.venueCapacity || 'Kapazität';
        const placeholderName = strings.venueNamePlaceholder || 'z.B. Hauptsaal, Foyer, Club';
        const titleRemove = strings.removeVenue || 'Raum entfernen';
        
        const html = `
            <div class="es-venue-item" data-index="${index}">
                <div class="es-venue-fields">
                    <div class="es-venue-field es-venue-name">
                        <label>${labelName} *</label>
                        <input type="text" name="venues[${index}][name]" value="${escapeHtml(venueName)}" placeholder="${placeholderName}" required>
                    </div>
                    <div class="es-venue-field es-venue-capacity">
                        <label>${labelCapacity}</label>
                        <input type="number" name="venues[${index}][capacity]" value="${venueCapacity}" min="0" placeholder="0">
                    </div>
                    <button type="button" class="es-remove-venue-btn button-link-delete" title="${titleRemove}">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>
            </div>
        `;
        
        $('#es-venues-list').append(html);
        hasUnsavedChanges = true;
    }
    
    /**
     * Reindex venue fields after removal
     */
    function reindexVenues() {
        $('#es-venues-list .es-venue-item').each(function(index) {
            $(this).attr('data-index', index);
            $(this).find('input[name*="[name]"]').attr('name', `venues[${index}][name]`);
            $(this).find('input[name*="[capacity]"]').attr('name', `venues[${index}][capacity]`);
        });
    }
    
    /**
     * Get venues data for saving
     */
    function getVenuesData() {
        const venues = [];
        $('#es-venues-list .es-venue-item').each(function() {
            const name = $(this).find('input[name*="[name]"]').val();
            const capacity = $(this).find('input[name*="[capacity]"]').val();
            if (name) {
                venues.push({
                    name: name,
                    capacity: capacity ? parseInt(capacity) : 0
                });
            }
        });
        return venues;
    }
    
    /**
     * Populate venues in form
     */
    function populateVenues(venues) {
        $('#es-venues-list').empty();
        if (venues && venues.length > 0) {
            venues.forEach(function(venue) {
                addVenue(venue);
            });
        }
    }
    
    /**
     * Load all locations
     */
    function loadLocations() {
        $('#es-locations-container').html('<div class="es-loading">Loading locations...</div>');
        
        $.ajax({
            url: ensembleAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'es_get_locations',
                nonce: ensembleAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    renderLocations(response.data);
                } else {
                    showMessage('error', response.data.message || 'Failed to load locations');
                }
            },
            error: function() {
                showMessage('error', 'Failed to load locations');
            }
        });
    }
    
    /**
     * Render locations
     */
    function renderLocations(locations) {
        if (!locations || locations.length === 0) {
            $('#es-locations-container').html(`
                <div class="es-empty-state">
                    <div class="es-empty-state-icon">${ES_ICONS.location}</div>
                    <h3>No locations yet</h3>
                    <p>Add your first location to get started!</p>
                    <button class="button button-primary" onclick="jQuery('#es-create-location-btn').click();">
                        ${window.esLocationLabels ? window.esLocationLabels.addNew : 'Add New Location'}
                    </button>
                </div>
            `);
            return;
        }
        
        $('#es-locations-container').removeClass('es-grid-view es-list-view').addClass('es-' + currentView + '-view');
        
        let html = '';
        locations.forEach(function(location) {
            const image = location.featured_image 
                ? `<img src="${location.featured_image}" alt="${escapeHtml(location.name)}" class="es-item-image">`
                : `<div class="es-item-image no-image">${ES_ICONS.location}</div>`;
            
            const locationType = location.location_type ? location.location_type : 'No type';
            const city = location.city ? location.city : 'No city';
            const capacity = location.capacity ? location.capacity + ' capacity' : 'Capacity not set';
            const eventCount = location.event_count || 0;
            
            html += `
                <div class="es-item-card" data-location-id="${location.id}">
                    <input type="checkbox" class="es-item-checkbox" data-id="${location.id}">
                    ${image}
                    <div class="es-item-body">
                        <div class="es-item-info">
                            <h3 class="es-item-title">${escapeHtml(location.name)}</h3>
                            <div class="es-item-meta">
                                <div class="es-item-meta-item">
                                    <span class="dashicons dashicons-admin-home"></span>
                                    <span>${escapeHtml(locationType)}</span>
                                </div>
                                <div class="es-item-meta-item">
                                    <span class="dashicons dashicons-location"></span>
                                    <span>${escapeHtml(city)}</span>
                                </div>
                                <div class="es-item-meta-item">
                                    <span class="dashicons dashicons-groups"></span>
                                    <span>${escapeHtml(capacity)}</span>
                                </div>
                                <div class="es-item-meta-item">
                                    <span class="dashicons dashicons-tickets-alt"></span>
                                    <span>${eventCount} events</span>
                                </div>
                            </div>
                        </div>
                        <div class="es-item-actions">
                            <button class="button es-edit-location" data-location-id="${location.id}">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 20h9"/><path d="M16.5 3.5l3 3L7 19l-4 1 1-4z"/>
                                </svg>
                                Edit
                            </button>
                            <button class="es-icon-btn es-copy-location" data-location-id="${location.id}" title="Copy">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                                </svg>
                            </button>
                            <button class="es-icon-btn es-icon-btn-danger es-delete-location" data-location-id="${location.id}" title="Löschen">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M4 7h16"/><path d="M10 4h4"/><path d="M6 7l1 13h10l1-13"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });
        
        $('#es-locations-container').html(html);
        
        // Bind events
        $('.es-edit-location').on('click', function(e) {
            e.stopPropagation();
            const locationId = $(this).data('location-id');
            editLocation(locationId);
        });
        
        $('.es-copy-location').on('click', function(e) {
            e.stopPropagation();
            const locationId = $(this).data('location-id');
            copyLocation(locationId, $(this));
        });
        
        $('.es-delete-location').on('click', function(e) {
            e.stopPropagation();
            const locationId = $(this).data('location-id');
            deleteLocation(locationId);
        });
        
        $('.es-item-card').on('click', function(e) {
            if (!$(e.target).is('.es-item-checkbox')) {
                const locationId = $(this).data('location-id');
                editLocation(locationId);
            }
        });
        
        $('.es-item-checkbox').on('change', function(e) {
            e.stopPropagation();
            updateSelection();
        });
    }
    
    /**
     * Initialize toolbar
     */
    function initToolbar() {
        $('#es-create-location-btn').on('click', function() {
            resetForm();
            $('#es-location-modal').fadeIn();
        });
        
        $('#es-location-search-btn').on('click', function() {
            const search = $('#es-location-search').val();
            searchLocations(search);
        });
        
        $('#es-location-search').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                const search = $(this).val();
                searchLocations(search);
            }
        });
        
        $('#es-bulk-delete-btn').on('click', function() {
            bulkDeleteLocations();
        });
    }
    
    /**
     * Initialize view toggle
     */
    function initViewToggle() {
        $('.es-view-btn').on('click', function() {
            currentView = $(this).data('view');
            $('.es-view-btn').removeClass('active');
            $(this).addClass('active');
            $('#es-locations-container').removeClass('es-grid-view es-list-view').addClass('es-' + currentView + '-view');
        });
    }
    
    /**
     * Initialize location form
     */
    function initLocationForm() {
        $('#es-location-form').on('submit', function(e) {
            e.preventDefault();
            saveLocation();
        });
        
        $('#es-delete-location-btn').on('click', function() {
            if (currentLocationId) {
                deleteLocation(currentLocationId);
            }
        });
        
        $('#es-location-form').on('change input', 'input, select, textarea', function() {
            hasUnsavedChanges = true;
        });
    }
    
    /**
     * Save location
     */
    function saveLocation() {
        // Get checked location types from pills
        const selectedTypes = [];
        $('#es-location-type-pills input[type="checkbox"]:checked').each(function() {
            selectedTypes.push($(this).val());
        });
        
        // Get social links
        const socialLinks = (typeof EnsembleMediaManager !== 'undefined') 
            ? EnsembleMediaManager.getSocialLinks('es-location-social-links')
            : [];
        
        // Get gallery IDs
        const galleryIds = (typeof EnsembleMediaManager !== 'undefined')
            ? EnsembleMediaManager.getGalleryIds('es-location-gallery-ids')
            : [];
        
        // Get Multivenue data
        const isMultivenue = $('#es-location-multivenue').is(':checked');
        const venues = isMultivenue ? getVenuesData() : [];
        
        // Get Opening Hours data
        const hasOpeningHours = $('#es-location-has-opening-hours').is(':checked');
        const openingHours = hasOpeningHours ? getOpeningHoursData() : {};
        
        // Get description from WYSIWYG if available
        let description = '';
        if (typeof tinymce !== 'undefined' && tinymce.get('es-location-description')) {
            description = tinymce.get('es-location-description').getContent();
        } else {
            description = $('#es-location-description').val() || '';
        }
        
        const formData = {
            action: 'es_save_location',
            nonce: ensembleAjax.nonce,
            location_id: currentLocationId || 0,
            name: $('#es-location-name').val(),
            description: description,
            location_type: selectedTypes,
            address: $('#es-location-address').val(),
            city: $('#es-location-city').val(),
            capacity: $('#es-location-capacity').val(),
            website: $('#es-location-website').val(),
            social_links: socialLinks,
            // Multivenue fields
            is_multivenue: isMultivenue ? 1 : 0,
            venues: JSON.stringify(venues),
            // Opening Hours fields
            has_opening_hours: hasOpeningHours ? 1 : 0,
            opening_hours: JSON.stringify(openingHours),
            opening_hours_note: $('#es-opening-hours-note').val(),
            youtube: $('#es-location-youtube').val(),
            vimeo: $('#es-location-vimeo').val(),
            featured_image_id: $('#es-location-image-id').val(),
            gallery_ids: galleryIds,
            // Maps Add-on fields
            zip_code: $('#es-location-zip').val(),
            additional_info: $('#es-location-additional-info').val(),
            latitude: $('#es-location-latitude').val(),
            longitude: $('#es-location-longitude').val(),
            show_map: $('#es-location-show-map').is(':checked') ? 1 : 0,
            map_type: $('#es-location-map-type').val()
        };
        
        // Get location contacts (from Staff addon)
        formData.location_contacts = [];
        $('#es-location-contact-selection .es-contact-pill.selected').each(function() {
            var contactId = $(this).data('contact-id');
            if (contactId) {
                formData.location_contacts.push(contactId.toString());
            }
        });
        
        const $submitBtn = $('#es-location-form button[type="submit"]');
        const originalText = $submitBtn.text();
        $submitBtn.prop('disabled', true).text('Saving...');
        
        $.ajax({
            url: ensembleAjax.ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                $submitBtn.prop('disabled', false).text(originalText);
                
                if (response.success) {
                    showMessage('success', response.data.message);
                    hasUnsavedChanges = false;
                    currentLocationId = response.data.location_id;
                    $('#es-location-modal').fadeOut();
                    loadLocations();
                } else {
                    showMessage('error', response.data.message || 'Failed to save location');
                }
            },
            error: function() {
                $submitBtn.prop('disabled', false).text(originalText);
                showMessage('error', 'Failed to save location');
            }
        });
    }
    
    /**
     * Edit location
     */
    function editLocation(locationId) {
        $.ajax({
            url: ensembleAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'es_get_location',
                nonce: ensembleAjax.nonce,
                location_id: locationId
            },
            success: function(response) {
                if (response.success) {
                    populateForm(response.data);
                    $('#es-location-modal').fadeIn();
                } else {
                    showMessage('error', response.data.message || 'Failed to load location');
                }
            },
            error: function() {
                showMessage('error', 'Failed to load location');
            }
        });
    }
    
    /**
     * Populate form
     */
    function populateForm(location) {
        currentLocationId = location.id;
        hasUnsavedChanges = false;
        
        $('#es-modal-title').text(window.esLocationLabels ? window.esLocationLabels.edit : 'Edit Location');
        $('#es-location-id').val(location.id);
        $('#es-location-name').val(location.name);
        
        // Set description - handle both WYSIWYG and textarea
        if (typeof tinymce !== 'undefined' && tinymce.get('es-location-description')) {
            tinymce.get('es-location-description').setContent(location.description || '');
        }
        $('#es-location-description').val(location.description || '');
        
        $('#es-location-address').val(location.address);
        $('#es-location-city').val(location.city);
        $('#es-location-capacity').val(location.capacity);
        $('#es-location-website').val(location.website);
        $('#es-location-youtube').val(location.youtube || '');
        $('#es-location-vimeo').val(location.vimeo || '');
        
        // Multivenue fields
        const isMultivenue = location.is_multivenue || false;
        $('#es-location-multivenue').prop('checked', isMultivenue);
        if (isMultivenue) {
            $('#es-venues-section').show();
            populateVenues(location.venues || []);
        } else {
            $('#es-venues-section').hide();
            $('#es-venues-list').empty();
        }
        
        // Opening Hours fields
        populateOpeningHours({
            has_opening_hours: location.has_opening_hours,
            opening_hours: location.opening_hours,
            opening_hours_note: location.opening_hours_note
        });
        
        // Maps Add-on fields
        $('#es-location-zip').val(location.zip_code || '');
        $('#es-location-additional-info').val(location.additional_info || '');
        $('#es-location-latitude').val(location.latitude || '');
        $('#es-location-longitude').val(location.longitude || '');
        // Handle show_map: true/false, 1/0, '1'/'0', or undefined (default true)
        var showMap = location.show_map;
        if (showMap === undefined || showMap === null || showMap === '') {
            showMap = true; // Default to true for new locations
        } else {
            showMap = showMap === true || showMap === 1 || showMap === '1';
        }
        $('#es-location-show-map').prop('checked', showMap);
        $('#es-location-map-type').val(location.map_type || 'embedded');
        
        // Set location types (pills with checkboxes)
        $('#es-location-type-pills input[type="checkbox"]').prop('checked', false);
        if (location.location_types && location.location_types.length > 0) {
            location.location_types.forEach(function(type) {
                $('#es-location-type-pills input[value="' + type.id + '"]').prop('checked', true);
            });
        }
        
        // Populate social links
        if (typeof EnsembleMediaManager !== 'undefined') {
            EnsembleMediaManager.populateSocialLinks('es-location-social-links', 'location', location.social_links || []);
        }
        
        // Populate featured image
        if (location.featured_image) {
            $('#es-location-image-id').val(location.featured_image_id || '');
            $('#es-location-image-preview').html(`
                <img src="${location.featured_image}" alt="">
                <button type="button" class="es-remove-image">Remove</button>
            `).addClass('has-image');
            
            $('.es-remove-image').on('click', removeImage);
        } else {
            // Clear image preview if no image
            $('#es-location-image-id').val('');
            $('#es-location-image-preview').html('').removeClass('has-image');
        }
        
        // Populate gallery
        if (typeof EnsembleMediaManager !== 'undefined') {
            // Always call populateGallery - it handles empty arrays
            EnsembleMediaManager.populateGallery('es-location-gallery-preview', 'es-location-gallery-ids', location.gallery || []);
        } else {
            // Fallback: clear gallery if no EnsembleMediaManager
            $('#es-location-gallery-ids').val('');
            $('#es-location-gallery-preview').html('');
        }
        
        // Show Google Maps link
        if (location.maps_link) {
            $('#es-location-map-link').attr('href', location.maps_link);
            $('#es-location-map-link-container').show();
        }
        
        // Show stats
        $('#es-location-stats').show();
        $('#es-location-event-count').text(location.event_count);
        $('#es-location-created').text(formatDate(location.created));
        $('#es-location-modified').text(formatDate(location.modified));
        
        // Trigger event for addons (e.g., Downloads)
        $(document).trigger('es:location:loaded', [location]);
        
        // Load location contacts (from Staff addon)
        $('#es-location-contact-selection .es-contact-pill').removeClass('selected');
        $('#es-location-contact-selection .es-contact-checkbox').prop('checked', false);
        if (location.contacts && Array.isArray(location.contacts)) {
            location.contacts.forEach(function(contactId) {
                var $pill = $('#es-location-contact-selection .es-contact-pill[data-contact-id="' + contactId + '"]');
                if ($pill.length) {
                    $pill.addClass('selected');
                    $pill.find('.es-contact-checkbox').prop('checked', true);
                }
            });
        }
        
        $('#es-delete-location-btn').show();
    }
    
    /**
     * Reset form
     */
    function resetForm() {
        currentLocationId = null;
        hasUnsavedChanges = false;
        
        $('#es-modal-title').text(window.esLocationLabels ? window.esLocationLabels.addNew : 'Add New Location');
        $('#es-location-form')[0].reset();
        $('#es-location-id').val('');
        $('#es-location-image-id').val('');
        $('#es-location-image-preview').html('').removeClass('has-image');
        $('#es-location-type-pills input[type="checkbox"]').prop('checked', false);
        $('#es-location-map-link-container').hide();
        $('#es-location-stats').hide();
        $('#es-delete-location-btn').hide();
        
        // Reset description WYSIWYG
        if (typeof tinymce !== 'undefined' && tinymce.get('es-location-description')) {
            tinymce.get('es-location-description').setContent('');
        }
        
        // Reset Multivenue
        $('#es-location-multivenue').prop('checked', false);
        $('#es-venues-section').hide();
        $('#es-venues-list').empty();
        
        // Reset Opening Hours
        resetOpeningHours();
        
        // Reset media features
        if (typeof EnsembleMediaManager !== 'undefined') {
            EnsembleMediaManager.resetSocialLinks('es-location-social-links');
            EnsembleMediaManager.resetGallery('es-location-gallery-preview', 'es-location-gallery-ids');
        }
        
        // Reset location contacts (from Staff addon)
        $('#es-location-contact-selection .es-contact-pill').removeClass('selected');
        $('#es-location-contact-selection .es-contact-checkbox').prop('checked', false);
        
        // Trigger reset event for addons (e.g., Downloads)
        $(document).trigger('es:location:reset');
    }
    
    /**
     * Delete location
     */
    function deleteLocation(locationId) {
        console.log('🗑️ deleteLocation called with ID:', locationId);
        
        $.ajax({
            url: ensembleAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'es_delete_location',
                nonce: ensembleAjax.nonce,
                location_id: locationId
            },
            success: function(response) {
                console.log('🗑️ Delete response:', response);
                if (response.success) {
                    showMessage('success', response.data.message);
                    $('#es-location-modal').fadeOut();
                    loadLocations();
                } else {
                    showMessage('error', response.data.message || 'Failed to delete location');
                }
            },
            error: function() {
                showMessage('error', 'Failed to delete location');
            }
        });
    }
    
    /**
     * Copy location
     */
    function copyLocation(locationId, $btn) {
        console.log('📋 copyLocation called with ID:', locationId);
        
        // Show loading state
        const $icon = $btn.find('svg');
        const originalSvg = $icon.prop('outerHTML');
        $btn.prop('disabled', true);
        $icon.replaceWith('<span class="dashicons dashicons-update es-spin"></span>');
        
        $.ajax({
            url: ensembleAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'es_copy_location',
                nonce: ensembleAjax.nonce,
                location_id: locationId
            },
            success: function(response) {
                console.log('📋 Copy response:', response);
                if (response.success) {
                    showMessage('success', response.data.message || 'Location copied!');
                    loadLocations();
                    // Open the copy for editing
                    setTimeout(function() {
                        editLocation(response.data.location_id);
                    }, 500);
                } else {
                    showMessage('error', response.data.message || 'Failed to copy location');
                }
            },
            error: function() {
                showMessage('error', 'Failed to copy location');
            },
            complete: function() {
                $btn.prop('disabled', false);
                $btn.find('.dashicons').replaceWith(originalSvg);
            }
        });
    }
    
    /**
     * Search locations
     */
    function searchLocations(search) {
        if (!search) {
            loadLocations();
            return;
        }
        
        $('#es-locations-container').html('<div class="es-loading">Searching...</div>');
        
        $.ajax({
            url: ensembleAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'es_search_locations',
                nonce: ensembleAjax.nonce,
                search: search
            },
            success: function(response) {
                if (response.success) {
                    renderLocations(response.data);
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
     * Update selection
     */
    function updateSelection() {
        selectedLocations = [];
        $('.es-item-checkbox:checked').each(function() {
            selectedLocations.push($(this).data('id'));
            $(this).closest('.es-item-card').addClass('selected');
        });
        $('.es-item-checkbox:not(:checked)').each(function() {
            $(this).closest('.es-item-card').removeClass('selected');
        });
        
        if (selectedLocations.length > 0) {
            $('#es-bulk-delete-btn').show();
        } else {
            $('#es-bulk-delete-btn').hide();
        }
    }
    
    /**
     * Bulk delete locations
     */
    function bulkDeleteLocations() {
        if (selectedLocations.length === 0) return;
        
        console.log('🗑️ bulkDeleteLocations called, count:', selectedLocations.length);
        
        $.ajax({
            url: ensembleAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'es_bulk_delete_locations',
                nonce: ensembleAjax.nonce,
                location_ids: selectedLocations
            },
            success: function(response) {
                console.log('🗑️ Bulk delete response:', response);
                if (response.success) {
                    showMessage('success', `Deleted ${response.data.deleted} location(s)`);
                    selectedLocations = [];
                    $('#es-bulk-delete-btn').hide();
                    loadLocations();
                } else {
                    showMessage('error', response.data.message || 'Bulk delete failed');
                }
            },
            error: function() {
                showMessage('error', 'Bulk delete failed');
            }
        });
    }
    
    /**
     * Initialize image upload
     */
    function initImageUpload() {
        $('#es-upload-location-image-btn').on('click', function(e) {
            e.preventDefault();
            
            if (mediaUploader) {
                mediaUploader.open();
                return;
            }
            
            mediaUploader = wp.media({
                title: 'Select Location Photo',
                button: { text: 'Use this photo' },
                multiple: false
            });
            
            mediaUploader.on('select', function() {
                const attachment = mediaUploader.state().get('selection').first().toJSON();
                $('#es-location-image-id').val(attachment.id);
                $('#es-location-image-preview').html(`
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
        $('#es-location-image-id').val('');
        $('#es-location-image-preview').html('').removeClass('has-image');
        hasUnsavedChanges = true;
    }
    
    /**
     * Initialize media features (social links, gallery, video)
     */
    function initMediaFeatures() {
        // Initialize social links
        if (typeof EnsembleMediaManager !== 'undefined') {
            EnsembleMediaManager.initSocialLinks('es-location-social-links', 'location');
            EnsembleMediaManager.initGallery('es-add-gallery-image-btn', 'es-location-gallery-preview', 'es-location-gallery-ids');
        }
    }
    
    /**
     * Initialize modal
     */
    function initModal() {
        $('.es-modal-close').on('click', function() {
            if (hasUnsavedChanges) {
                if (!confirm('You have unsaved changes. Do you want to leave?')) {
                    return;
                }
            }
            $('#es-location-modal').fadeOut();
        });
        
        $('.es-modal-close-btn').on('click', function() {
            if (hasUnsavedChanges) {
                if (!confirm('You have unsaved changes. Do you want to leave?')) {
                    return;
                }
            }
            $('#es-location-modal').fadeOut();
        });
        
        $('#es-location-modal').on('click', function(e) {
            if (e.target.id === 'es-location-modal') {
                if (hasUnsavedChanges) {
                    if (!confirm('You have unsaved changes. Do you want to leave?')) {
                        return;
                    }
                }
                $('#es-location-modal').fadeOut();
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
     */
    function formatDate(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { 
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
    
})(jQuery);
