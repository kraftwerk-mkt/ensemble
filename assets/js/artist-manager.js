/**
 * Ensemble Artist Manager JavaScript
 * 
 * @package Ensemble
 */

(function($) {
    'use strict';
    
    // State
    let currentArtistId = null;
    let hasUnsavedChanges = false;
    let mediaUploader = null;
    let currentView = 'grid';
    let selectedArtists = [];
    
    // Initialize
    $(document).ready(function() {
        loadArtists();
        initToolbar();
        initViewToggle();
        initArtistForm();
        initImageUpload();
        initMediaFeatures();
        initModal();
        initHeroVideoToggle();
    });
    
    /**
     * Initialize Hero Video Toggle
     */
    function initHeroVideoToggle() {
        // Toggle hero video section
        $('#es-artist-has-hero-video').on('change', function() {
            if ($(this).is(':checked')) {
                $('#es-hero-video-container').slideDown();
            } else {
                $('#es-hero-video-container').slideUp();
            }
        });
        
        // Auto-detect hero video URL and show toggle
        $('#es-artist-hero-video-url').on('change blur', function() {
            const url = $(this).val();
            if (url && !$('#es-artist-has-hero-video').is(':checked')) {
                $('#es-artist-has-hero-video').prop('checked', true);
                $('#es-hero-video-container').show();
            }
        });
    }
    
    /**
     * Load all artists
     */
    function loadArtists() {
        $('#es-artists-container').html('<div class="es-loading">Loading artists...</div>');
        
        $.ajax({
            url: ensembleAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'es_get_artists',
                nonce: ensembleAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    renderArtists(response.data);
                } else {
                    showMessage('error', response.data.message || 'Failed to load artists');
                }
            },
            error: function() {
                showMessage('error', 'Failed to load artists');
            }
        });
    }
    
    // Make loadArtists globally accessible
    window.loadArtists = loadArtists;
    
    /**
     * Render artists
     */
    function renderArtists(artists) {
        if (!artists || artists.length === 0) {
            $('#es-artists-container').html(`
                <div class="es-empty-state">
                    <div class="es-empty-state-icon">${ES_ICONS.artist}</div>
                    <h3>No artists yet</h3>
                    <p>Add your first artist to get started!</p>
                    <button class="button button-primary" onclick="jQuery('#es-create-artist-btn').click();">
                        ${window.esArtistLabels ? window.esArtistLabels.addNew : 'Add New Artist'}
                    </button>
                </div>
            `);
            return;
        }
        
        $('#es-artists-container').removeClass('es-grid-view es-list-view').addClass('es-' + currentView + '-view');
        
        let html = '';
        artists.forEach(function(artist) {
            const image = artist.featured_image 
                ? `<img src="${artist.featured_image}" alt="${escapeHtml(artist.name)}" class="es-item-image">`
                : `<div class="es-item-image no-image">${ES_ICONS.artist}</div>`;
            
            const genre = artist.genre ? artist.genre : '';
            const artistType = artist.artist_type ? artist.artist_type : '';
            const displayMeta = artistType || genre || 'No category';
            const eventCount = artist.event_count || 0;
            
            // Get first genre and type IDs for filtering
            const genreId = (artist.genres && artist.genres.length > 0) ? artist.genres[0].id : '';
            const typeId = (artist.artist_types && artist.artist_types.length > 0) ? artist.artist_types[0].id : '';
            
            html += `
                <div class="es-item-card" data-artist-id="${artist.id}" data-genre-id="${genreId}" data-type-id="${typeId}">
                    <input type="checkbox" class="es-item-checkbox" data-id="${artist.id}">
                    ${image}
                    <div class="es-item-body">
                        <div class="es-item-info">
                            <h3 class="es-item-title">${escapeHtml(artist.name)}</h3>
                            <div class="es-item-meta">
                                ${artistType ? `
                                <div class="es-item-meta-item">
                                    <span class="dashicons dashicons-tag"></span>
                                    <span>${escapeHtml(artistType)}</span>
                                </div>
                                ` : ''}
                                ${genre ? `
                                <div class="es-item-meta-item">
                                    <span class="dashicons dashicons-admin-users"></span>
                                    <span>${escapeHtml(genre)}</span>
                                </div>
                                ` : ''}
                                <div class="es-item-meta-item">
                                    <span class="dashicons dashicons-tickets-alt"></span>
                                    <span>${eventCount} events</span>
                                </div>
                            </div>
                        </div>
                        <div class="es-item-actions">
                            <button class="button es-edit-artist" data-artist-id="${artist.id}">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 20h9"/><path d="M16.5 3.5l3 3L7 19l-4 1 1-4z"/>
                                </svg>
                                Edit
                            </button>
                            <button class="es-icon-btn es-icon-btn-danger es-delete-artist" data-artist-id="${artist.id}" title="Löschen">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M4 7h16"/><path d="M10 4h4"/><path d="M6 7l1 13h10l1-13"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });
        
        $('#es-artists-container').html(html);
        
        // Bind events
        $('.es-edit-artist').on('click', function(e) {
            e.stopPropagation();
            const artistId = $(this).data('artist-id');
            editArtist(artistId);
        });
        
        $('.es-delete-artist').on('click', function(e) {
            e.stopPropagation();
            const artistId = $(this).data('artist-id');
            deleteArtist(artistId);
        });
        
        $('.es-item-card').on('click', function(e) {
            if (!$(e.target).is('.es-item-checkbox')) {
                const artistId = $(this).data('artist-id');
                editArtist(artistId);
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
        $('#es-create-artist-btn').on('click', function() {
            resetForm();
            $('#es-artist-modal').fadeIn();
        });
        
        $('#es-artist-search-btn').on('click', function() {
            const search = $('#es-artist-search').val();
            searchArtists(search);
        });
        
        $('#es-artist-search').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                const search = $(this).val();
                searchArtists(search);
            }
        });
        
        $('#es-bulk-delete-btn').on('click', function() {
            bulkDeleteArtists();
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
            $('#es-artists-container').removeClass('es-grid-view es-list-view').addClass('es-' + currentView + '-view');
        });
    }
    
    /**
     * Initialize artist form
     */
    function initArtistForm() {
        $('#es-artist-form').on('submit', function(e) {
            e.preventDefault();
            saveArtist();
        });
        
        $('#es-delete-artist-btn').on('click', function() {
            if (currentArtistId) {
                deleteArtist(currentArtistId);
            }
        });
        
        $('#es-artist-form').on('change input', 'input, select, textarea', function() {
            hasUnsavedChanges = true;
        });
        
        // Hero video URL preview
        $('#es-artist-hero-video-url').on('change blur', function() {
            updateHeroVideoPreview($(this).val());
        });
    }
    
    /**
     * Save artist
     */
    function saveArtist() {
        // Get checked genres from pills
        const selectedGenres = [];
        $('#es-artist-genre-pills input[type="checkbox"]:checked').each(function() {
            selectedGenres.push($(this).val());
        });
        
        // Get checked artist types from pills
        const selectedTypes = [];
        $('#es-artist-type-pills input[type="checkbox"]:checked').each(function() {
            selectedTypes.push($(this).val());
        });
        
        // Get social links
        const socialLinks = (typeof EnsembleMediaManager !== 'undefined') 
            ? EnsembleMediaManager.getSocialLinks('es-artist-social-links')
            : [];
        
        // Get gallery IDs
        const galleryIds = (typeof EnsembleMediaManager !== 'undefined')
            ? EnsembleMediaManager.getGalleryIds('es-artist-gallery-ids')
            : [];
        
        // Get description from WYSIWYG if available
        let description = '';
        if (typeof tinymce !== 'undefined' && tinymce.get('es-artist-description')) {
            description = tinymce.get('es-artist-description').getContent();
        } else {
            description = $('#es-artist-description').val() || '';
        }
        
        const formData = {
            action: 'es_save_artist',
            nonce: ensembleAjax.nonce,
            artist_id: currentArtistId || 0,
            name: $('#es-artist-name').val(),
            description: description,
            references: $('#es-artist-references').val(),
            genre: selectedGenres,
            artist_type: selectedTypes,
            email: $('#es-artist-email').val(),
            website: $('#es-artist-website').val(),
            social_links: socialLinks,
            youtube: $('#es-artist-youtube').val(),
            vimeo: $('#es-artist-vimeo').val(),
            featured_image_id: $('#es-artist-image-id').val(),
            gallery_ids: galleryIds,
            // Hero video fields
            hero_video_url: $('#es-artist-hero-video-url').val(),
            hero_video_autoplay: $('#es-artist-hero-video-autoplay').is(':checked') ? 1 : 0,
            hero_video_loop: $('#es-artist-hero-video-loop').is(':checked') ? 1 : 0,
            hero_video_controls: $('#es-artist-hero-video-controls').is(':checked') ? 1 : 0,
            // Single layout preference
            single_layout: $('#es-artist-single-layout').val(),
            // Professional info fields (NEW)
            position: $('#es-artist-position').val(),
            company: $('#es-artist-company').val(),
            additional: $('#es-artist-additional').val()
        };
        
        const $submitBtn = $('#es-artist-form button[type="submit"]');
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
                    currentArtistId = response.data.artist_id;
                    $('#es-artist-modal').fadeOut();
                    loadArtists();
                } else {
                    showMessage('error', response.data.message || 'Failed to save artist');
                }
            },
            error: function() {
                $submitBtn.prop('disabled', false).text(originalText);
                showMessage('error', 'Failed to save artist');
            }
        });
    }
    
    /**
     * Edit artist
     */
    function editArtist(artistId) {
        $.ajax({
            url: ensembleAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'es_get_artist',
                nonce: ensembleAjax.nonce,
                artist_id: artistId
            },
            success: function(response) {
                if (response.success) {
                    populateForm(response.data);
                    $('#es-artist-modal').fadeIn();
                } else {
                    showMessage('error', response.data.message || 'Failed to load artist');
                }
            },
            error: function() {
                showMessage('error', 'Failed to load artist');
            }
        });
    }
    
    /**
     * Populate form
     */
    function populateForm(artist) {
        currentArtistId = artist.id;
        hasUnsavedChanges = false;
        
        $('#es-modal-title').text(window.esArtistLabels ? window.esArtistLabels.edit : 'Edit Artist');
        $('#es-artist-id').val(artist.id);
        $('#es-artist-name').val(artist.name);
        
        // Set description - handle both WYSIWYG and textarea
        if (typeof tinymce !== 'undefined' && tinymce.get('es-artist-description')) {
            tinymce.get('es-artist-description').setContent(artist.description || '');
        }
        $('#es-artist-description').val(artist.description || '');
        
        $('#es-artist-references').val(artist.references || '');
        $('#es-artist-email').val(artist.email || '');
        $('#es-artist-website').val(artist.website);
        $('#es-artist-youtube').val(artist.youtube || '');
        $('#es-artist-vimeo').val(artist.vimeo || '');
        
        // Professional info fields (NEW)
        $('#es-artist-position').val(artist.position || '');
        $('#es-artist-company').val(artist.company || '');
        $('#es-artist-additional').val(artist.additional || '');
        
        // Hero video fields
        $('#es-artist-hero-video-url').val(artist.hero_video_url || '');
        $('#es-artist-hero-video-autoplay').prop('checked', artist.hero_video_autoplay !== false);
        $('#es-artist-hero-video-loop').prop('checked', artist.hero_video_loop !== false);
        $('#es-artist-hero-video-controls').prop('checked', artist.hero_video_controls === true);
        
        // Hero video toggle - show section if URL exists
        const hasHeroVideo = artist.hero_video_url && artist.hero_video_url.length > 0;
        $('#es-artist-has-hero-video').prop('checked', hasHeroVideo);
        if (hasHeroVideo) {
            $('#es-hero-video-container').show();
        } else {
            $('#es-hero-video-container').hide();
        }
        
        // Single layout preference
        $('#es-artist-single-layout').val(artist.single_layout || 'default');
        
        // Update hero video preview if URL exists
        updateHeroVideoPreview(artist.hero_video_url);
        
        // Set genres (pills with checkboxes)
        $('#es-artist-genre-pills input[type="checkbox"]').prop('checked', false);
        if (artist.genres && artist.genres.length > 0) {
            artist.genres.forEach(function(genre) {
                $('#es-artist-genre-pills input[value="' + genre.id + '"]').prop('checked', true);
            });
        }
        
        // Set artist types (pills with checkboxes)
        $('#es-artist-type-pills input[type="checkbox"]').prop('checked', false);
        if (artist.artist_types && artist.artist_types.length > 0) {
            artist.artist_types.forEach(function(type) {
                $('#es-artist-type-pills input[value="' + type.id + '"]').prop('checked', true);
            });
        }
        
        // Populate social links
        if (typeof EnsembleMediaManager !== 'undefined') {
            EnsembleMediaManager.populateSocialLinks('es-artist-social-links', 'artist', artist.social_links || []);
        }
        
        // Populate featured image
        if (artist.featured_image) {
            $('#es-artist-image-id').val(artist.featured_image_id || '');
            $('#es-artist-image-preview').html(`
                <img src="${artist.featured_image}" alt="">
                <button type="button" class="es-remove-image">Remove</button>
            `).addClass('has-image');
            
            $('.es-remove-image').on('click', removeImage);
        } else {
            // Clear image preview if no image
            $('#es-artist-image-id').val('');
            $('#es-artist-image-preview').html('').removeClass('has-image');
        }
        
        // Populate gallery
        if (typeof EnsembleMediaManager !== 'undefined') {
            // Always call populateGallery - it handles empty arrays
            EnsembleMediaManager.populateGallery('es-artist-gallery-preview', 'es-artist-gallery-ids', artist.gallery || []);
        } else {
            // Fallback: clear gallery if no EnsembleMediaManager
            $('#es-artist-gallery-ids').val('');
            $('#es-artist-gallery-preview').html('');
        }
        
        // Show stats
        $('#es-artist-stats').show();
        $('#es-artist-event-count').text(artist.event_count);
        $('#es-artist-created').text(formatDate(artist.created));
        $('#es-artist-modified').text(formatDate(artist.modified));
        
        $('#es-delete-artist-btn').show();
    }
    
    /**
     * Update hero video preview
     */
    function updateHeroVideoPreview(url) {
        const $preview = $('#es-artist-hero-video-preview');
        const $frame = $('#es-artist-video-preview-frame');
        
        if (!url) {
            $preview.hide();
            $frame.attr('src', '');
            return;
        }
        
        let embedUrl = '';
        
        // YouTube
        let match = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&?]+)/);
        if (match) {
            embedUrl = 'https://www.youtube.com/embed/' + match[1];
        }
        
        // Vimeo
        if (!embedUrl) {
            match = url.match(/vimeo\.com\/(\d+)/);
            if (match) {
                embedUrl = 'https://player.vimeo.com/video/' + match[1];
            }
        }
        
        if (embedUrl) {
            $frame.attr('src', embedUrl);
            $preview.show();
        } else {
            $preview.hide();
        }
    }
    
    /**
     * Reset form
     */
    function resetForm() {
        currentArtistId = null;
        hasUnsavedChanges = false;
        
        $('#es-modal-title').text(window.esArtistLabels ? window.esArtistLabels.addNew : 'Add New Artist');
        $('#es-artist-form')[0].reset();
        $('#es-artist-id').val('');
        $('#es-artist-image-id').val('');
        $('#es-artist-image-preview').html('').removeClass('has-image');
        $('#es-artist-genre-pills input[type="checkbox"]').prop('checked', false);
        $('#es-artist-type-pills input[type="checkbox"]').prop('checked', false);
        $('#es-artist-stats').hide();
        $('#es-delete-artist-btn').hide();
        
        // Reset description WYSIWYG
        if (typeof tinymce !== 'undefined' && tinymce.get('es-artist-description')) {
            tinymce.get('es-artist-description').setContent('');
        }
        
        // Reset hero video fields
        $('#es-artist-hero-video-url').val('');
        $('#es-artist-hero-video-autoplay').prop('checked', true);
        $('#es-artist-hero-video-loop').prop('checked', true);
        $('#es-artist-hero-video-controls').prop('checked', false);
        $('#es-artist-hero-video-preview').hide();
        $('#es-artist-video-preview-frame').attr('src', '');
        $('#es-artist-has-hero-video').prop('checked', false);
        $('#es-hero-video-container').hide();
        
        // Reset layout preference
        $('#es-artist-single-layout').val('default');
        
        // Reset media features
        if (typeof EnsembleMediaManager !== 'undefined') {
            EnsembleMediaManager.resetSocialLinks('es-artist-social-links');
            EnsembleMediaManager.resetGallery('es-artist-gallery-preview', 'es-artist-gallery-ids');
        }
    }
    
    /**
     * Delete artist
     */
    function deleteArtist(artistId) {
        if (!confirm('Are you sure you want to delete this artist?')) {
            return;
        }
        
        $.ajax({
            url: ensembleAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'es_delete_artist',
                nonce: ensembleAjax.nonce,
                artist_id: artistId
            },
            success: function(response) {
                if (response.success) {
                    showMessage('success', response.data.message);
                    $('#es-artist-modal').fadeOut();
                    loadArtists();
                } else {
                    showMessage('error', response.data.message || 'Failed to delete artist');
                }
            },
            error: function() {
                showMessage('error', 'Failed to delete artist');
            }
        });
    }
    
    /**
     * Search artists
     */
    function searchArtists(search) {
        if (!search) {
            loadArtists();
            return;
        }
        
        $('#es-artists-container').html('<div class="es-loading">Searching...</div>');
        
        $.ajax({
            url: ensembleAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'es_search_artists',
                nonce: ensembleAjax.nonce,
                search: search
            },
            success: function(response) {
                if (response.success) {
                    renderArtists(response.data);
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
        selectedArtists = [];
        $('.es-item-checkbox:checked').each(function() {
            selectedArtists.push($(this).data('id'));
            $(this).closest('.es-item-card').addClass('selected');
        });
        $('.es-item-checkbox:not(:checked)').each(function() {
            $(this).closest('.es-item-card').removeClass('selected');
        });
        
        if (selectedArtists.length > 0) {
            $('#es-bulk-delete-btn').show();
        } else {
            $('#es-bulk-delete-btn').hide();
        }
    }
    
    /**
     * Bulk delete artists
     */
    function bulkDeleteArtists() {
        if (selectedArtists.length === 0) return;
        
        if (!confirm(`Are you sure you want to delete ${selectedArtists.length} artist(s)?`)) {
            return;
        }
        
        $.ajax({
            url: ensembleAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'es_bulk_delete_artists',
                nonce: ensembleAjax.nonce,
                artist_ids: selectedArtists
            },
            success: function(response) {
                if (response.success) {
                    showMessage('success', `Deleted ${response.data.deleted} artist(s)`);
                    selectedArtists = [];
                    $('#es-bulk-delete-btn').hide();
                    loadArtists();
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
        $('#es-upload-artist-image-btn').on('click', function(e) {
            e.preventDefault();
            
            if (mediaUploader) {
                mediaUploader.open();
                return;
            }
            
            mediaUploader = wp.media({
                title: 'Select Artist Photo',
                button: { text: 'Use this photo' },
                multiple: false
            });
            
            mediaUploader.on('select', function() {
                const attachment = mediaUploader.state().get('selection').first().toJSON();
                $('#es-artist-image-id').val(attachment.id);
                $('#es-artist-image-preview').html(`
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
        $('#es-artist-image-id').val('');
        $('#es-artist-image-preview').html('').removeClass('has-image');
        hasUnsavedChanges = true;
    }
    
    /**
     * Initialize media features (social links, gallery, video)
     */
    function initMediaFeatures() {
        // Initialize social links
        if (typeof EnsembleMediaManager !== 'undefined') {
            EnsembleMediaManager.initSocialLinks('es-artist-social-links', 'artist');
            EnsembleMediaManager.initGallery('es-add-gallery-image-btn', 'es-artist-gallery-preview', 'es-artist-gallery-ids');
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
            $('#es-artist-modal').fadeOut();
        });
        
        $('.es-modal-close-btn').on('click', function() {
            if (hasUnsavedChanges) {
                if (!confirm('You have unsaved changes. Do you want to leave?')) {
                    return;
                }
            }
            $('#es-artist-modal').fadeOut();
        });
        
        $('#es-artist-modal').on('click', function(e) {
            if (e.target.id === 'es-artist-modal') {
                if (hasUnsavedChanges) {
                    if (!confirm('You have unsaved changes. Do you want to leave?')) {
                        return;
                    }
                }
                $('#es-artist-modal').fadeOut();
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
