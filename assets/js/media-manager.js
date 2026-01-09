/**
 * Ensemble Media Manager JavaScript
 * Shared functions for social links, gallery and video management
 * 
 * @package Ensemble
 */

(function($) {
    'use strict';
    
    // Export functions to global scope
    window.EnsembleMediaManager = {
        initSocialLinks: initSocialLinks,
        initGallery: initGallery,
        getSocialLinks: getSocialLinks,
        getGalleryIds: getGalleryIds,
        populateSocialLinks: populateSocialLinks,
        populateGallery: populateGallery,
        resetSocialLinks: resetSocialLinks,
        resetGallery: resetGallery
    };
    
    let socialLinkCounter = 0;
    let galleryUploader = null;
    
    /**
     * Social Media Links Management
     */
    function initSocialLinks(containerId, prefix) {
        const $container = $('#' + containerId);
        const $addBtn = $('#es-add-social-link-btn');
        
        if ($container.length === 0) return;
        
        // Add button click
        $addBtn.off('click').on('click', function() {
            addSocialLinkRow(containerId, prefix);
        });
        
        // Handle remove buttons (delegated)
        $container.on('click', '.es-remove-social-link', function() {
            $(this).closest('.es-social-link-row').fadeOut(200, function() {
                $(this).remove();
            });
        });
        
        // URL change detection for icon update
        $container.on('blur', '.es-social-url-input', function() {
            updateSocialIcon($(this));
        });
    }
    
    function addSocialLinkRow(containerId, prefix, url = '') {
        const $container = $('#' + containerId);
        const rowId = prefix + '-social-' + (++socialLinkCounter);
        const icon = detectSocialIcon(url);
        
        const html = `
            <div class="es-social-link-row" data-row-id="${rowId}">
                <span class="es-social-icon dashicons dashicons-${icon}"></span>
                <input type="url" 
                       class="es-social-url-input" 
                       name="${prefix}_social_links[]" 
                       value="${escapeHtml(url)}"
                       placeholder="https://...">
                <button type="button" class="button es-remove-social-link">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>
        `;
        
        $container.append(html);
    }
    
    function updateSocialIcon($input) {
        const url = $input.val();
        const icon = detectSocialIcon(url);
        $input.siblings('.es-social-icon')
              .attr('class', 'es-social-icon dashicons dashicons-' + icon);
    }
    
    function detectSocialIcon(url) {
        if (!url) return 'share';
        
        const urlLower = url.toLowerCase();
        
        // Platform detection
        if (urlLower.includes('facebook.com') || urlLower.includes('fb.com')) {
            return 'facebook';
        } else if (urlLower.includes('instagram.com')) {
            return 'instagram';
        } else if (urlLower.includes('twitter.com') || urlLower.includes('x.com')) {
            return 'twitter';
        } else if (urlLower.includes('youtube.com') || urlLower.includes('youtu.be')) {
            return 'video-alt3';
        } else if (urlLower.includes('linkedin.com')) {
            return 'linkedin';
        } else if (urlLower.includes('tiktok.com')) {
            return 'video-alt2';
        } else if (urlLower.includes('spotify.com')) {
            return 'format-audio';
        } else if (urlLower.includes('soundcloud.com')) {
            return 'format-audio';
        } else if (urlLower.includes('bandcamp.com')) {
            return 'format-audio';
        } else if (urlLower.includes('vimeo.com')) {
            return 'video-alt';
        } else if (urlLower.includes('pinterest.com')) {
            return 'pinterest';
        } else if (urlLower.includes('whatsapp.com')) {
            return 'whatsapp';
        }
        
        return 'share';
    }
    
    function getSocialLinks(containerId) {
        const links = [];
        $('#' + containerId + ' .es-social-url-input').each(function() {
            const url = $(this).val().trim();
            if (url) {
                links.push(url);
            }
        });
        return links;
    }
    
    function populateSocialLinks(containerId, prefix, links) {
        const $container = $('#' + containerId);
        $container.empty();
        
        if (links && links.length > 0) {
            links.forEach(function(url) {
                addSocialLinkRow(containerId, prefix, url);
            });
        }
    }
    
    function resetSocialLinks(containerId) {
        $('#' + containerId).empty();
        socialLinkCounter = 0;
    }
    
    /**
     * Gallery Management
     */
    function initGallery(buttonId, previewId, inputId) {
        const $button = $('#' + buttonId);
        const $preview = $('#' + previewId);
        const $input = $('#' + inputId);
        
        if ($button.length === 0) return;
        
        $button.off('click').on('click', function(e) {
            e.preventDefault();
            
            // Create the media frame if it doesn't exist
            if (galleryUploader) {
                galleryUploader.open();
                return;
            }
            
            galleryUploader = wp.media({
                title: 'Select Gallery Images',
                button: {
                    text: 'Add to Gallery'
                },
                multiple: true,
                library: {
                    type: 'image'
                }
            });
            
            galleryUploader.on('select', function() {
                const selection = galleryUploader.state().get('selection');
                const currentIds = $input.val() ? $input.val().split(',') : [];
                
                selection.map(function(attachment) {
                    attachment = attachment.toJSON();
                    
                    // Check if already exists
                    if (currentIds.includes(attachment.id.toString())) {
                        return;
                    }
                    
                    currentIds.push(attachment.id);
                    addGalleryImage(previewId, attachment.id, attachment.url);
                });
                
                $input.val(currentIds.join(','));
            });
            
            galleryUploader.open();
        });
        
        // Handle remove buttons (delegated)
        $preview.on('click', '.es-remove-gallery-image', function() {
            const $item = $(this).closest('.es-gallery-item');
            const imageId = $item.data('image-id');
            
            $item.fadeOut(200, function() {
                $(this).remove();
                removeGalleryId(inputId, imageId);
            });
        });
        
        // Make gallery sortable
        initGallerySortable($preview, previewId, inputId);
    }
    
    /**
     * Initialize or reinitialize sortable for gallery
     */
    function initGallerySortable($preview, previewId, inputId) {
        if (!$.fn.sortable) return;
        
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
                updateGalleryOrder(previewId, inputId);
            }
        });
    }
    
    function addGalleryImage(previewId, imageId, imageUrl) {
        const $preview = $('#' + previewId);
        
        const html = `
            <div class="es-gallery-item" data-image-id="${imageId}">
                <img src="${imageUrl}" alt="">
                <button type="button" class="es-remove-gallery-image">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>
        `;
        
        $preview.append(html);
    }
    
    function removeGalleryId(inputId, imageId) {
        const $input = $('#' + inputId);
        let ids = $input.val() ? $input.val().split(',') : [];
        ids = ids.filter(id => id != imageId);
        $input.val(ids.join(','));
    }
    
    function updateGalleryOrder(previewId, inputId) {
        const $preview = $('#' + previewId);
        const ids = [];
        
        $preview.find('.es-gallery-item').each(function() {
            ids.push($(this).data('image-id'));
        });
        
        $('#' + inputId).val(ids.join(','));
    }
    
    function getGalleryIds(inputId) {
        const value = $('#' + inputId).val();
        return value ? value.split(',').filter(id => id) : [];
    }
    
    function populateGallery(previewId, inputId, galleryData) {
        const $preview = $('#' + previewId);
        const $input = $('#' + inputId);
        
        $preview.empty();
        $input.val('');
        
        if (galleryData && galleryData.length > 0) {
            const ids = [];
            
            galleryData.forEach(function(image) {
                addGalleryImage(previewId, image.id, image.url);
                ids.push(image.id);
            });
            
            $input.val(ids.join(','));
        }
        
        // Reinitialize sortable after populating
        initGallerySortable($preview, previewId, inputId);
    }
    
    function resetGallery(previewId, inputId) {
        $('#' + previewId).empty();
        $('#' + inputId).val('');
        galleryUploader = null;
    }
    
    /**
     * Helper function
     */
    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }
    
})(jQuery);
