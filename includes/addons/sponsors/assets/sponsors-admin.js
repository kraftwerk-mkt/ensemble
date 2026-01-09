/**
 * Ensemble Sponsors Admin
 * Following exact pattern from Artist/Location managers
 * 
 * @package Ensemble
 * @subpackage Addons/Sponsors
 */

(function($) {
    'use strict';

    // State
    var currentSponsorId = 0;
    var currentView = 'grid';
    var selectedSponsors = [];
    var mediaUploader = null;

    // Icons
    var ICONS = {
        sponsor: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>'
    };

    // Initialize
    $(document).ready(function() {
        loadSponsors();
        initToolbar();
        initViewToggle();
        initModal();
        initForm();
        initImageUpload();
    });

    /**
     * Load all sponsors
     */
    function loadSponsors() {
        $('#es-sponsors-container').html('<div class="es-loading">Loading sponsors...</div>');
        
        $.ajax({
            url: esSponsorsAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'es_get_sponsors',
                nonce: esSponsorsAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    renderSponsors(response.data);
                } else {
                    $('#es-sponsors-container').html('<div class="es-empty-state"><p>Error loading sponsors</p></div>');
                }
            },
            error: function() {
                $('#es-sponsors-container').html('<div class="es-empty-state"><p>Error loading sponsors</p></div>');
            }
        });
    }

    /**
     * Render sponsors grid/list
     */
    function renderSponsors(sponsors) {
        if (!sponsors || sponsors.length === 0) {
            $('#es-sponsors-container').html(
                '<div class="es-empty-state">' +
                    '<div class="es-empty-state-icon">' + ICONS.sponsor + '</div>' +
                    '<h3>No sponsors yet</h3>' +
                    '<p>Add your first sponsor to get started!</p>' +
                    '<button class="button button-primary" onclick="jQuery(\'#es-create-sponsor-btn\').click();">' +
                        'Add New Sponsor' +
                    '</button>' +
                '</div>'
            );
            return;
        }
        
        $('#es-sponsors-container').removeClass('es-grid-view es-list-view').addClass('es-' + currentView + '-view');
        
        var html = '';
        sponsors.forEach(function(sponsor) {
            var image = sponsor.logo_url 
                ? '<img src="' + sponsor.logo_url + '" alt="' + escapeHtml(sponsor.name) + '" class="es-item-image">'
                : '<div class="es-item-image no-image">' + ICONS.sponsor + '</div>';
            
            var category = sponsor.category || 'No category';
            var badges = '';
            
            // Main sponsor badge (highest priority)
            if (sponsor.is_main) {
                badges += '<span class="es-badge es-badge--warning" title="Main Sponsor">★ Main</span>';
            }
            
            if (sponsor.is_global) {
                badges += '<span class="es-badge es-badge--success">Global</span>';
            } else if (sponsor.events && sponsor.events.length > 0) {
                badges += '<span class="es-badge es-badge--info">' + sponsor.events.length + ' Event(s)</span>';
            }
            
            html += '<div class="es-item-card' + (sponsor.is_main ? ' es-item-card--main' : '') + '" data-sponsor-id="' + sponsor.id + '">' +
                '<input type="checkbox" class="es-item-checkbox" data-id="' + sponsor.id + '">' +
                image +
                '<div class="es-item-body">' +
                    '<div class="es-item-info">' +
                        '<h3 class="es-item-title">' + escapeHtml(sponsor.name) + '</h3>' +
                        '<div class="es-item-meta">' +
                            '<div class="es-item-meta-item">' +
                                '<span class="dashicons dashicons-category"></span>' +
                                '<span>' + escapeHtml(category) + '</span>' +
                            '</div>' +
                        '</div>' +
                        (badges ? '<div class="es-item-badges">' + badges + '</div>' : '') +
                    '</div>' +
                    '<div class="es-item-actions">' +
                        '<button class="button es-edit-btn" data-id="' + sponsor.id + '">Edit</button>' +
                        (sponsor.website ? '<a href="' + sponsor.website + '" target="_blank" class="button">Website</a>' : '') +
                    '</div>' +
                '</div>' +
            '</div>';
        });
        
        $('#es-sponsors-container').html(html);
        
        // Re-attach click handlers
        attachCardHandlers();
    }

    /**
     * Attach card click handlers
     */
    function attachCardHandlers() {
        // Card click to edit
        $('.es-item-card').on('click', function(e) {
            if ($(e.target).is('input, button, a') || $(e.target).closest('button, a').length) {
                return;
            }
            var id = $(this).data('sponsor-id');
            loadSponsor(id);
        });
        
        // Edit button
        $('.es-edit-btn').on('click', function(e) {
            e.stopPropagation();
            var id = $(this).data('id');
            loadSponsor(id);
        });
        
        // Checkbox selection
        $('.es-item-checkbox').on('click', function(e) {
            e.stopPropagation();
            updateSelection();
        });
    }

    /**
     * Initialize toolbar
     */
    function initToolbar() {
        // Search
        $('#es-sponsor-search').on('keypress', function(e) {
            if (e.which === 13) {
                searchSponsors();
            }
        });
        
        $('#es-sponsor-search-btn').on('click', searchSponsors);
        
        // Category filter
        $('#es-sponsor-category-filter').on('change', function() {
            // Reload with filter
            loadSponsors();
        });
        
        // Create new
        $('#es-create-sponsor-btn').on('click', function() {
            resetForm();
            $('#es-modal-title').text('Add New Sponsor');
            $('#es-delete-sponsor-btn').hide();
            $('#es-sponsor-shortcode-section').hide();
            $('#es-sponsor-modal').fadeIn(200);
        });
        
        // Bulk delete
        $('#es-bulk-delete-btn').on('click', function() {
            if (selectedSponsors.length === 0) return;
            if (!confirm('Delete ' + selectedSponsors.length + ' sponsor(s)?')) return;
            bulkDelete();
        });
    }

    /**
     * Initialize view toggle
     */
    function initViewToggle() {
        $('.es-view-btn').on('click', function() {
            $('.es-view-btn').removeClass('active');
            $(this).addClass('active');
            currentView = $(this).data('view');
            $('#es-sponsors-container').removeClass('es-grid-view es-list-view').addClass('es-' + currentView + '-view');
        });
    }

    /**
     * Initialize modal
     */
    function initModal() {
        // Close modal
        $('.es-modal-close, .es-modal-close-btn').on('click', function() {
            $('#es-sponsor-modal').fadeOut(200);
        });
        
        // Close on outside click
        $('#es-sponsor-modal').on('click', function(e) {
            if ($(e.target).is('.es-modal')) {
                $(this).fadeOut(200);
            }
        });
        
        // Escape key
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $('#es-sponsor-modal').is(':visible')) {
                $('#es-sponsor-modal').fadeOut(200);
            }
        });
    }

    /**
     * Initialize form
     */
    function initForm() {
        // Global toggle
        $('#es-sponsor-is-global').on('change', function() {
            if ($(this).is(':checked')) {
                $('#es-sponsor-events-row').slideUp(200);
            } else {
                $('#es-sponsor-events-row').slideDown(200);
            }
        });
        
        // Main sponsor toggle - show/hide caption field
        $('#es-sponsor-is-main').on('change', function() {
            if ($(this).is(':checked')) {
                $('#es-sponsor-main-caption-row').slideDown(200);
            } else {
                $('#es-sponsor-main-caption-row').slideUp(200);
            }
        });
        
        // Form submit
        $('#es-sponsor-form').on('submit', function(e) {
            e.preventDefault();
            saveSponsor();
        });
        
        // Delete button
        $('#es-delete-sponsor-btn').on('click', function() {
            if (!confirm(esSponsorsAdmin.i18n.confirmDelete)) return;
            deleteSponsor(currentSponsorId);
        });
        
        // Copy shortcode
        $('.es-copy-btn').on('click', function() {
            var target = $(this).data('target');
            var text = $('#' + target).text();
            navigator.clipboard.writeText(text);
            showMessage('Copied!', 'success');
        });
    }

    /**
     * Initialize image upload with drag & drop
     */
    function initImageUpload() {
        var $zone = $('#es-sponsor-logo-container');
        var $placeholder = $('#es-logo-placeholder');
        var $preview = $('#es-sponsor-logo-preview');
        
        // Click to upload
        $placeholder.on('click', function() {
            openMediaUploader();
        });
        
        // Prevent default drag on document
        $(document).on('dragover drop', function(e) {
            e.preventDefault();
        });
        
        // Drag & drop
        $zone.on('dragover dragenter', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).addClass('es-drag-over');
        });
        
        $zone.on('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var rect = this.getBoundingClientRect();
            if (e.originalEvent.clientX < rect.left || e.originalEvent.clientX > rect.right ||
                e.originalEvent.clientY < rect.top || e.originalEvent.clientY > rect.bottom) {
                $(this).removeClass('es-drag-over');
            }
        });
        
        $zone.on('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('es-drag-over');
            
            var files = e.originalEvent.dataTransfer.files;
            if (files.length > 0 && files[0].type.match('image.*')) {
                uploadFile(files[0]);
            }
        });
        
        // Remove image
        $preview.on('click', '.es-remove-image', function(e) {
            e.stopPropagation();
            $('#es-sponsor-logo-id').val('');
            $preview.hide().find('img').attr('src', '');
            $placeholder.show();
        });
    }

    /**
     * Open media uploader
     */
    function openMediaUploader() {
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }
        
        mediaUploader = wp.media({
            title: 'Select Sponsor Logo',
            button: { text: 'Use as Logo' },
            multiple: false
        });
        
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            setLogoPreview(attachment.id, attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url);
        });
        
        mediaUploader.open();
    }

    /**
     * Upload file via WordPress async-upload
     */
    function uploadFile(file) {
        // Show loading
        var $placeholder = $('#es-logo-placeholder');
        var originalHtml = $placeholder.html();
        $placeholder.html('<span class="spinner is-active" style="float:none;margin:20px;"></span>');
        
        var formData = new FormData();
        formData.append('async-upload', file);
        formData.append('name', file.name);
        formData.append('action', 'upload-attachment');
        formData.append('_wpnonce', esSponsorsAdmin.uploadNonce || '');
        
        $.ajax({
            url: esSponsorsAdmin.asyncUploadUrl,
            type: 'POST',
            data: formData,
            dataType: 'json',
            processData: false,
            contentType: false,
            success: function(response) {
                $placeholder.html(originalHtml);
                if (response && response.success && response.data) {
                    var data = response.data;
                    var url = data.sizes && data.sizes.medium ? data.sizes.medium.url : data.url;
                    setLogoPreview(data.id, url);
                } else {
                    console.error('Upload error:', response);
                    showMessage('Upload failed', 'error');
                }
            },
            error: function(xhr, status, error) {
                $placeholder.html(originalHtml);
                console.error('Upload AJAX error:', error);
                showMessage('Upload failed: ' + error, 'error');
            }
        });
    }

    /**
     * Set logo preview
     */
    function setLogoPreview(id, url) {
        $('#es-sponsor-logo-id').val(id);
        $('#es-sponsor-logo-preview').show().find('img').attr('src', url);
        $('#es-logo-placeholder').hide();
    }

    /**
     * Load single sponsor for editing
     */
    function loadSponsor(id) {
        $.ajax({
            url: esSponsorsAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'es_get_sponsor',
                sponsor_id: id,
                nonce: esSponsorsAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    populateForm(response.data);
                    $('#es-sponsor-modal').fadeIn(200);
                }
            }
        });
    }

    /**
     * Populate form with sponsor data
     */
    function populateForm(sponsor) {
        currentSponsorId = sponsor.id;
        
        $('#es-sponsor-id').val(sponsor.id);
        $('#es-sponsor-name').val(sponsor.name);
        $('#es-sponsor-website').val(sponsor.website);
        $('#es-sponsor-description').val(sponsor.description);
        $('#es-sponsor-active-from').val(sponsor.active_from);
        $('#es-sponsor-active-until').val(sponsor.active_until);
        $('#es-sponsor-order').val(sponsor.menu_order);
        $('#es-sponsor-is-global').prop('checked', sponsor.is_global);
        $('#es-sponsor-is-main').prop('checked', sponsor.is_main);
        $('#es-sponsor-main-caption').val(sponsor.main_caption || '');
        
        // Toggle events row
        if (sponsor.is_global) {
            $('#es-sponsor-events-row').hide();
        } else {
            $('#es-sponsor-events-row').show();
        }
        
        // Toggle main caption row
        if (sponsor.is_main) {
            $('#es-sponsor-main-caption-row').show();
        } else {
            $('#es-sponsor-main-caption-row').hide();
        }
        
        // Categories
        $('input[name="categories[]"]').prop('checked', false);
        if (sponsor.categories) {
            sponsor.categories.forEach(function(cat) {
                $('input[name="categories[]"][value="' + cat.id + '"]').prop('checked', true);
            });
        }
        
        // Events
        $('#es-sponsor-events').val(sponsor.event_ids || []);
        
        // Logo
        if (sponsor.logo_id && sponsor.logo_url) {
            setLogoPreview(sponsor.logo_id, sponsor.logo_url);
        } else {
            $('#es-sponsor-logo-id').val('');
            $('#es-sponsor-logo-preview').hide();
            $('#es-logo-placeholder').show();
        }
        
        // Shortcode
        $('#es-sponsor-shortcode').text('[ensemble_sponsor id="' + sponsor.id + '"]');
        $('#es-sponsor-shortcode-section').show();
        
        $('#es-modal-title').text('Edit Sponsor');
        $('#es-delete-sponsor-btn').show();
    }

    /**
     * Reset form
     */
    function resetForm() {
        currentSponsorId = 0;
        $('#es-sponsor-form')[0].reset();
        $('#es-sponsor-id').val('');
        $('#es-sponsor-logo-id').val('');
        $('#es-sponsor-logo-preview').hide();
        $('#es-logo-placeholder').show();
        $('#es-sponsor-events-row').show();
        $('#es-sponsor-main-caption-row').hide();
        $('input[name="categories[]"]').prop('checked', false);
    }

    /**
     * Save sponsor
     */
    function saveSponsor() {
        var $btn = $('#es-sponsor-form button[type="submit"]');
        $btn.prop('disabled', true).text('Saving...');
        
        var categories = [];
        $('input[name="categories[]"]:checked').each(function() {
            categories.push($(this).val());
        });
        
        var formData = {
            action: 'es_save_sponsor',
            nonce: esSponsorsAdmin.nonce,
            sponsor_id: $('#es-sponsor-id').val(),
            name: $('#es-sponsor-name').val(),
            website: $('#es-sponsor-website').val(),
            description: $('#es-sponsor-description').val(),
            logo_id: $('#es-sponsor-logo-id').val(),
            active_from: $('#es-sponsor-active-from').val(),
            active_until: $('#es-sponsor-active-until').val(),
            menu_order: $('#es-sponsor-order').val(),
            is_global: $('#es-sponsor-is-global').is(':checked') ? 1 : 0,
            is_main: $('#es-sponsor-is-main').is(':checked') ? 1 : 0,
            main_caption: $('#es-sponsor-main-caption').val(),
            events: $('#es-sponsor-events').val() || [],
            categories: categories
        };
        
        $.ajax({
            url: esSponsorsAdmin.ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                $btn.prop('disabled', false).text('Save Sponsor');
                
                if (response.success) {
                    $('#es-sponsor-modal').fadeOut(200);
                    loadSponsors();
                    showMessage(esSponsorsAdmin.i18n.saved, 'success');
                } else {
                    showMessage(response.data.message || esSponsorsAdmin.i18n.error, 'error');
                }
            },
            error: function() {
                $btn.prop('disabled', false).text('Save Sponsor');
                showMessage(esSponsorsAdmin.i18n.error, 'error');
            }
        });
    }

    /**
     * Delete sponsor
     */
    function deleteSponsor(id) {
        $.ajax({
            url: esSponsorsAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'es_delete_sponsor',
                sponsor_id: id,
                nonce: esSponsorsAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#es-sponsor-modal').fadeOut(200);
                    loadSponsors();
                    showMessage(esSponsorsAdmin.i18n.deleted, 'success');
                }
            }
        });
    }

    /**
     * Search sponsors
     */
    function searchSponsors() {
        var query = $('#es-sponsor-search').val().toLowerCase();
        
        $('.es-item-card').each(function() {
            var name = $(this).find('.es-item-title').text().toLowerCase();
            $(this).toggle(name.indexOf(query) !== -1);
        });
    }

    /**
     * Update selection
     */
    function updateSelection() {
        selectedSponsors = [];
        $('.es-item-checkbox:checked').each(function() {
            selectedSponsors.push($(this).data('id'));
        });
        
        if (selectedSponsors.length > 0) {
            $('#es-bulk-delete-btn').show().text('Delete (' + selectedSponsors.length + ')');
        } else {
            $('#es-bulk-delete-btn').hide();
        }
    }

    /**
     * Bulk delete
     */
    function bulkDelete() {
        // Would need AJAX endpoint for bulk delete
        selectedSponsors.forEach(function(id) {
            deleteSponsor(id);
        });
    }

    /**
     * Show message
     */
    function showMessage(text, type) {
        var $msg = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + text + '</p></div>');
        $('.es-manager-wrap h1').after($msg);
        setTimeout(function() {
            $msg.fadeOut(function() { $(this).remove(); });
        }, 3000);
    }

    /**
     * Escape HTML
     */
    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

})(jQuery);
