<?php
/**
 * Gallery Manager Template
 *
 * @package Ensemble
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Enqueue required scripts and styles
wp_enqueue_media();
wp_enqueue_script('jquery-ui-sortable');
wp_enqueue_style('es-manager', ENSEMBLE_PLUGIN_URL . 'assets/css/manager.css', array(), ENSEMBLE_VERSION);

$manager = new ES_Gallery_Manager();

// Get categories for filter
$categories = get_terms(array(
    'taxonomy'   => 'ensemble_gallery_category',
    'hide_empty' => false,
));

// Get events for linking
$events = get_posts(array(
    'post_type'      => ensemble_get_post_type(),
    'posts_per_page' => -1,
    'orderby'        => 'title',
    'order'          => 'ASC',
    'post_status'    => 'publish',
));

// Get artists for linking
$artists = get_posts(array(
    'post_type'      => 'ensemble_artist',
    'posts_per_page' => -1,
    'orderby'        => 'title',
    'order'          => 'ASC',
    'post_status'    => 'publish',
));

// Get locations for linking
$locations = get_posts(array(
    'post_type'      => 'ensemble_location',
    'posts_per_page' => -1,
    'orderby'        => 'title',
    'order'          => 'ASC',
    'post_status'    => 'publish',
));

// Get labels
$artist_label = ES_Label_System::get_label('artist', false);
$location_label = ES_Label_System::get_label('location', false);
$event_label = ES_Label_System::get_label('event', false);
?>

<div class="wrap es-manager-wrap es-galleries-wrap">
    <h1><?php _e('Galleries', 'ensemble'); ?></h1>
    
    <div class="es-manager-container">
        
        <!-- Toolbar -->
        <div class="es-manager-toolbar">
            <div class="es-toolbar-left">
                <div class="es-search-box">
                    <input type="text" id="es-gallery-search" placeholder="<?php _e('Search galleries...', 'ensemble'); ?>">
                    <button id="es-gallery-search-btn" class="button"><?php _e('Search', 'ensemble'); ?></button>
                </div>
                
                <?php if (!empty($categories) && !is_wp_error($categories)): ?>
                <select id="es-gallery-category-filter" class="es-filter-select">
                    <option value=""><?php _e('All Categories', 'ensemble'); ?></option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo esc_attr($cat->term_id); ?>">
                            <?php echo esc_html($cat->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php endif; ?>
            </div>
            
            <div class="es-toolbar-right">
                <button id="es-bulk-delete-btn" class="button" style="display: none;">
                    <?php _e('Delete Selected', 'ensemble'); ?>
                </button>
                <button id="es-create-gallery-btn" class="button button-primary">
                    <?php _e('Add New Gallery', 'ensemble'); ?>
                </button>
            </div>
        </div>
        
        <!-- View Toggle -->
        <div class="es-view-toggle">
            <button class="es-view-btn active" data-view="grid">
                <span class="dashicons dashicons-grid-view"></span>
                <?php _e('Grid', 'ensemble'); ?>
            </button>
            <button class="es-view-btn" data-view="list">
                <span class="dashicons dashicons-list-view"></span>
                <?php _e('List', 'ensemble'); ?>
            </button>
        </div>
        
        <!-- Galleries List/Grid -->
        <div id="es-galleries-container" class="es-items-container">
            <div class="es-loading"><?php _e('Loading galleries...', 'ensemble'); ?></div>
        </div>
        
    </div>
    
    <!-- Gallery Modal -->
    <div id="es-gallery-modal" class="es-modal" style="display: none;">
        <div class="es-modal-content es-modal-large">
            <span class="es-modal-close">&times;</span>
            
            <h2 id="es-modal-title"><?php _e('Add New Gallery', 'ensemble'); ?></h2>
            
            <form id="es-gallery-form" class="es-manager-form">
                
                <input type="hidden" id="es-gallery-id" name="gallery_id" value="">
                
                <div class="es-form-grid">
                    
                    <!-- Left Column -->
                    <div class="es-form-column">
                        
                        <div class="es-form-section">
                            <h3><?php _e('Basic Information', 'ensemble'); ?></h3>
                            
                            <div class="es-form-row">
                                <label for="es-gallery-title"><?php _e('Gallery Title', 'ensemble'); ?> *</label>
                                <input type="text" id="es-gallery-title" name="title" required placeholder="<?php _e('e.g. Summer Festival 2024 Photos', 'ensemble'); ?>">
                            </div>
                            
                            <div class="es-form-row">
                                <label for="es-gallery-description"><?php _e('Description', 'ensemble'); ?></label>
                                <textarea id="es-gallery-description" name="description" rows="3" placeholder="<?php _e('Optional description for this gallery...', 'ensemble'); ?>"></textarea>
                            </div>
                            
                            <?php if (!empty($categories) && !is_wp_error($categories)): ?>
                            <div class="es-form-row">
                                <label><?php _e('Category', 'ensemble'); ?></label>
                                <div class="es-pill-group" id="es-gallery-category-pills">
                                    <?php foreach ($categories as $cat): ?>
                                        <label class="es-pill">
                                            <input type="checkbox" name="categories[]" value="<?php echo esc_attr($cat->term_id); ?>">
                                            <span><?php echo esc_html($cat->name); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <p class="es-field-help">
                                    <a href="<?php echo admin_url('edit-tags.php?taxonomy=ensemble_gallery_category&post_type=ensemble_gallery'); ?>" target="_blank">
                                        <?php _e('+ Manage categories', 'ensemble'); ?>
                                    </a>
                                </p>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="es-form-section">
                            <h3><?php _e('Link to Content', 'ensemble'); ?></h3>
                            <p class="es-field-help" style="margin-top: 0;">
                                <?php _e('Optional: Connect this gallery to an event, artist, or location.', 'ensemble'); ?>
                            </p>
                            
                            <div class="es-form-row">
                                <label for="es-gallery-linked-event"><?php echo esc_html($event_label); ?></label>
                                <select id="es-gallery-linked-event" name="linked_event">
                                    <option value=""><?php _e('— Not linked —', 'ensemble'); ?></option>
                                    <?php foreach ($events as $event): ?>
                                        <option value="<?php echo esc_attr($event->ID); ?>">
                                            <?php echo esc_html($event->post_title); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="es-form-row">
                                <label for="es-gallery-linked-artist"><?php echo esc_html($artist_label); ?></label>
                                <select id="es-gallery-linked-artist" name="linked_artist">
                                    <option value=""><?php _e('— Not linked —', 'ensemble'); ?></option>
                                    <?php foreach ($artists as $artist): ?>
                                        <option value="<?php echo esc_attr($artist->ID); ?>">
                                            <?php echo esc_html($artist->post_title); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="es-form-row">
                                <label for="es-gallery-linked-location"><?php echo esc_html($location_label); ?></label>
                                <select id="es-gallery-linked-location" name="linked_location">
                                    <option value=""><?php _e('— Not linked —', 'ensemble'); ?></option>
                                    <?php foreach ($locations as $location): ?>
                                        <option value="<?php echo esc_attr($location->ID); ?>">
                                            <?php echo esc_html($location->post_title); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="es-form-section">
                            <h3><?php _e('Display Settings', 'ensemble'); ?></h3>
                            
                            <div class="es-form-row">
                                <label for="es-gallery-layout"><?php _e('Default Layout', 'ensemble'); ?></label>
                                <select id="es-gallery-layout" name="layout">
                                    <option value="grid"><?php _e('Grid', 'ensemble'); ?></option>
                                    <option value="masonry"><?php _e('Masonry', 'ensemble'); ?></option>
                                    <option value="slider"><?php _e('Slider', 'ensemble'); ?></option>
                                </select>
                            </div>
                            
                            <div class="es-form-row">
                                <label for="es-gallery-columns"><?php _e('Columns', 'ensemble'); ?></label>
                                <select id="es-gallery-columns" name="columns">
                                    <option value="2">2</option>
                                    <option value="3" selected>3</option>
                                    <option value="4">4</option>
                                    <option value="5">5</option>
                                </select>
                            </div>
                            
                            <div class="es-form-row">
                                <label class="es-checkbox-inline">
                                    <input type="checkbox" id="es-gallery-lightbox" name="lightbox" value="1" checked>
                                    <span><?php _e('Enable Lightbox', 'ensemble'); ?></span>
                                </label>
                            </div>
                        </div>
                        
                    </div>
                    
                    <!-- Right Column -->
                    <div class="es-form-column">
                        
                        <div class="es-form-section">
                            <h3><?php _e('Gallery Images', 'ensemble'); ?></h3>
                            
                            <div class="es-form-row">
                                <div id="es-gallery-images-container">
                                    <div class="es-gallery-upload-zone">
                                        <button type="button" id="es-add-gallery-images-btn" class="button button-large">
                                            <span class="dashicons dashicons-images-alt2"></span>
                                            <?php _e('Add Images', 'ensemble'); ?>
                                        </button>
                                        <p class="es-drop-hint"><?php _e('or drag & drop images here', 'ensemble'); ?></p>
                                    </div>
                                    <div id="es-gallery-images-preview" class="es-gallery-preview"></div>
                                    <input type="hidden" id="es-gallery-image-ids" name="image_ids" value="">
                                </div>
                                <small class="es-field-help"><?php _e('Drag images to reorder. Click × to remove.', 'ensemble'); ?></small>
                            </div>
                        </div>
                        
                        <div class="es-form-section">
                            <h3><?php _e('Cover Image', 'ensemble'); ?></h3>
                            <p class="es-field-help" style="margin-top: 0;">
                                <?php _e('Optional custom cover. If not set, the first gallery image is used.', 'ensemble'); ?>
                            </p>
                            
                            <div class="es-form-row">
                                <div id="es-gallery-cover-container">
                                    <button type="button" id="es-upload-cover-btn" class="button">
                                        <span class="dashicons dashicons-format-image"></span>
                                        <?php _e('Select Cover Image', 'ensemble'); ?>
                                    </button>
                                    <div id="es-gallery-cover-preview"></div>
                                    <input type="hidden" id="es-gallery-cover-id" name="featured_image_id" value="">
                                </div>
                            </div>
                        </div>
                        
                        <div class="es-form-section" id="es-gallery-stats" style="display: none;">
                            <h3><?php _e('Statistics', 'ensemble'); ?></h3>
                            
                            <div class="es-stat-item">
                                <span class="es-stat-label"><?php _e('Images:', 'ensemble'); ?></span>
                                <span class="es-stat-value" id="es-gallery-image-count">0</span>
                            </div>
                            
                            <div class="es-stat-item">
                                <span class="es-stat-label"><?php _e('Created:', 'ensemble'); ?></span>
                                <span class="es-stat-value" id="es-gallery-created">-</span>
                            </div>
                            
                            <div class="es-stat-item">
                                <span class="es-stat-label"><?php _e('Last Modified:', 'ensemble'); ?></span>
                                <span class="es-stat-value" id="es-gallery-modified">-</span>
                            </div>
                        </div>
                        
                        <div class="es-form-section">
                            <h3><?php _e('Shortcode', 'ensemble'); ?></h3>
                            <div id="es-gallery-shortcode-preview" style="display: none;">
                                <code class="es-shortcode-display" id="es-gallery-shortcode"></code>
                                <button type="button" class="button es-copy-shortcode" data-target="es-gallery-shortcode">
                                    <?php _e('Copy', 'ensemble'); ?>
                                </button>
                            </div>
                            <p class="es-field-help" id="es-gallery-shortcode-hint">
                                <?php _e('Shortcode will be available after saving.', 'ensemble'); ?>
                            </p>
                        </div>
                        
                    </div>
                    
                </div>
                
                <div class="es-form-actions">
                    <button type="submit" class="button button-primary button-large">
                        <?php _e('Save Gallery', 'ensemble'); ?>
                    </button>
                    <button type="button" class="es-modal-close-btn button button-large">
                        <?php _e('Cancel', 'ensemble'); ?>
                    </button>
                    <button type="button" id="es-delete-gallery-btn" class="button button-link-delete" style="display: none;">
                        <?php _e('Delete Gallery', 'ensemble'); ?>
                    </button>
                </div>
                
            </form>
            
        </div>
    </div>
    
    <!-- Success/Error Messages -->
    <div id="es-message" class="es-message" style="display: none;"></div>
    
</div>

<style>
/* Gallery Preview Grid */
.es-gallery-preview {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 8px;
    margin-top: 15px;
}

.es-gallery-preview-item {
    position: relative;
    aspect-ratio: 1;
    border-radius: var(--ensemble-card-radius, 8px);
    overflow: hidden;
    background: #f0f0f0;
}

.es-gallery-preview-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.es-gallery-preview-item .es-remove-image {
    position: absolute;
    top: 4px;
    right: 4px;
    width: 24px;
    height: 24px;
    background: rgba(0,0,0,0.7);
    color: #fff;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    font-size: 16px;
    line-height: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.2s;
}

.es-gallery-preview-item:hover .es-remove-image {
    opacity: 1;
}

.es-gallery-preview-item.sortable-ghost {
    opacity: 0.4;
}

/* Sortable placeholder */
.es-sortable-placeholder {
    background: #e0e0e0;
    border: 2px dashed #ccc;
    border-radius: var(--ensemble-card-radius, 8px);
    aspect-ratio: 1;
}

/* Drag handle */
.es-gallery-preview-item .es-drag-handle {
    position: absolute;
    bottom: 4px;
    left: 4px;
    width: 24px;
    height: 24px;
    background: rgba(0,0,0,0.5);
    color: #fff;
    border-radius: 4px;
    cursor: move;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    opacity: 0;
    transition: opacity 0.2s;
}

.es-gallery-preview-item:hover .es-drag-handle {
    opacity: 1;
}

/* Drag & drop zone */
#es-gallery-images-container {
    border: 2px dashed transparent;
    border-radius: 8px;
    transition: all 0.2s;
    padding: 10px;
    margin: -10px;
}

#es-gallery-images-container.es-drag-over {
    border-color: var(--es-primary, #0073aa);
    background: rgba(0, 115, 170, 0.05);
}

/* Shortcode display */
.es-shortcode-display {
    display: block;
    padding: 10px 12px;
    background: #f5f5f5;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-family: monospace;
    font-size: 13px;
    margin-bottom: 10px;
    word-break: break-all;
}

/* Filter select */
.es-filter-select {
    margin-left: 10px;
}

/* Upload zone */
.es-gallery-upload-zone {
    text-align: center;
    padding: 20px;
    border: 2px dashed var(--es-border, #ddd);
    border-radius: 8px;
    background: var(--es-background, #f9f9f9);
    transition: all 0.2s;
}

.es-gallery-upload-zone:hover,
#es-gallery-images-container.es-drag-over .es-gallery-upload-zone {
    border-color: var(--es-primary, #0073aa);
    background: rgba(0, 115, 170, 0.05);
}

.es-drop-hint {
    margin: 10px 0 0;
    color: var(--es-text-secondary, #666);
    font-size: 13px;
}

/* Upload progress */
.es-upload-progress {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px;
    background: #e8f4fc;
    border-radius: 4px;
    margin-top: 10px;
    color: #0073aa;
    font-size: 13px;
}

.es-upload-progress .spinner {
    margin: 0;
}
</style>

<script>
jQuery(document).ready(function($) {
    var currentGalleryId = 0;
    var imageIds = [];
    
    // Load galleries
    function loadGalleries() {
        $('#es-galleries-container').html('<div class="es-loading"><?php _e('Loading galleries...', 'ensemble'); ?></div>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'es_get_galleries',
                nonce: '<?php echo wp_create_nonce('ensemble_ajax'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    renderGalleries(response.data);
                } else {
                    $('#es-galleries-container').html('<div class="es-no-items"><?php _e('No galleries found.', 'ensemble'); ?></div>');
                }
            }
        });
    }
    
    // Render galleries grid
    function renderGalleries(galleries) {
        if (!galleries || galleries.length === 0) {
            $('#es-galleries-container').html(
                '<div class="es-empty-state">' +
                    '<div class="es-empty-state-icon"><span class="dashicons dashicons-format-gallery"></span></div>' +
                    '<h3><?php _e('No galleries yet', 'ensemble'); ?></h3>' +
                    '<p><?php _e('Create your first gallery to get started!', 'ensemble'); ?></p>' +
                    '<button class="button button-primary" onclick="jQuery(\'#es-create-gallery-btn\').click();">' +
                        '<?php _e('Add New Gallery', 'ensemble'); ?>' +
                    '</button>' +
                '</div>'
            );
            return;
        }
        
        // Use manager.css classes
        $('#es-galleries-container').removeClass('es-list-view').addClass('es-grid-view');
        
        var html = '';
        
        galleries.forEach(function(gallery) {
            var coverImage = gallery.featured_image || (gallery.images && gallery.images[0] ? gallery.images[0].url : '');
            var imageCount = gallery.image_count || 0;
            
            html += '<div class="es-item-card" data-id="' + gallery.id + '">';
            html += '<input type="checkbox" class="es-item-checkbox" data-id="' + gallery.id + '">';
            
            if (coverImage) {
                html += '<img src="' + coverImage + '" alt="" class="es-item-image">';
            } else {
                html += '<div class="es-item-image no-image"><span class="dashicons dashicons-format-gallery"></span></div>';
            }
            
            html += '<div class="es-item-body">';
            html += '<div class="es-item-info">';
            html += '<h3 class="es-item-title">' + escapeHtml(gallery.title) + '</h3>';
            html += '<div class="es-item-meta">';
            html += '<div class="es-item-meta-item">';
            html += '<span class="dashicons dashicons-images-alt2"></span>';
            html += '<span>' + imageCount + ' <?php _e('Images', 'ensemble'); ?></span>';
            html += '</div>';
            if (gallery.category) {
                html += '<div class="es-item-meta-item">';
                html += '<span class="dashicons dashicons-category"></span>';
                html += '<span>' + escapeHtml(gallery.category) + '</span>';
                html += '</div>';
            }
            html += '</div>'; // .es-item-meta
            html += '</div>'; // .es-item-info
            html += '<div class="es-item-actions">';
            html += '<button class="button es-edit-btn" data-id="' + gallery.id + '"><?php _e('Edit', 'ensemble'); ?></button>';
            if (gallery.slug) {
                html += '<a href="<?php echo home_url('/gallery/'); ?>' + gallery.slug + '/" target="_blank" class="button"><?php _e('View', 'ensemble'); ?></a>';
            }
            html += '</div>'; // .es-item-actions
            html += '</div>'; // .es-item-body
            html += '</div>'; // .es-item-card
        });
        
        $('#es-galleries-container').html(html);
        
        // Attach click handlers
        attachCardHandlers();
    }
    
    // Attach card click handlers
    function attachCardHandlers() {
        $('.es-item-card').off('click').on('click', function(e) {
            if ($(e.target).is('input, button, a') || $(e.target).closest('button, a').length) {
                return;
            }
            var id = $(this).data('id');
            loadGallery(id);
        });
        
        $('.es-edit-btn').off('click').on('click', function(e) {
            e.stopPropagation();
            var id = $(this).data('id');
            loadGallery(id);
        });
    }
    
    // Escape HTML helper
    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Open modal for new gallery
    $('#es-create-gallery-btn').on('click', function() {
        currentGalleryId = 0;
        imageIds = [];
        $('#es-gallery-form')[0].reset();
        $('#es-gallery-id').val('');
        $('#es-modal-title').text('<?php _e('Add New Gallery', 'ensemble'); ?>');
        $('#es-delete-gallery-btn').hide();
        $('#es-gallery-stats').hide();
        $('#es-gallery-images-preview').empty();
        $('#es-gallery-cover-preview').empty();
        $('#es-gallery-shortcode-preview').hide();
        $('#es-gallery-shortcode-hint').show();
        $('#es-gallery-modal').fadeIn(200);
    });
    
    // Load single gallery
    function loadGallery(id) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'es_get_gallery',
                gallery_id: id,
                nonce: '<?php echo wp_create_nonce('ensemble_ajax'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    populateForm(response.data);
                    $('#es-gallery-modal').fadeIn(200);
                }
            }
        });
    }
    
    // Populate form with gallery data
    function populateForm(gallery) {
        currentGalleryId = gallery.id;
        $('#es-gallery-id').val(gallery.id);
        $('#es-gallery-title').val(gallery.title);
        $('#es-gallery-description').val(gallery.description);
        $('#es-gallery-layout').val(gallery.layout);
        $('#es-gallery-columns').val(gallery.columns);
        $('#es-gallery-lightbox').prop('checked', gallery.lightbox);
        
        // Set linked items
        $('#es-gallery-linked-event').val(gallery.linked_event ? gallery.linked_event.id : '');
        $('#es-gallery-linked-artist').val(gallery.linked_artist ? gallery.linked_artist.id : '');
        $('#es-gallery-linked-location').val(gallery.linked_location ? gallery.linked_location.id : '');
        
        // Set categories
        $('input[name="categories[]"]').prop('checked', false);
        if (gallery.categories) {
            gallery.categories.forEach(function(cat) {
                $('input[name="categories[]"][value="' + cat.id + '"]').prop('checked', true);
            });
        }
        
        // Set images
        imageIds = [];
        var imagesHtml = '';
        if (gallery.images) {
            gallery.images.forEach(function(img) {
                imageIds.push(img.id);
                imagesHtml += '<div class="es-gallery-preview-item" data-id="' + img.id + '">';
                imagesHtml += '<img src="' + img.url + '" alt="">';
                imagesHtml += '<button type="button" class="es-remove-image">&times;</button>';
                imagesHtml += '</div>';
            });
        }
        $('#es-gallery-images-preview').html(imagesHtml);
        $('#es-gallery-image-ids').val(imageIds.join(','));
        
        // Set cover image
        if (gallery.featured_image_id) {
            $('#es-gallery-cover-id').val(gallery.featured_image_id);
            $('#es-gallery-cover-preview').html('<img src="' + gallery.featured_image + '" style="max-width: 150px; border-radius: 8px;">');
        }
        
        // Stats
        $('#es-gallery-image-count').text(gallery.image_count);
        $('#es-gallery-created').text(gallery.created);
        $('#es-gallery-modified').text(gallery.modified);
        $('#es-gallery-stats').show();
        
        // Shortcode
        $('#es-gallery-shortcode').text('[ensemble_gallery id="' + gallery.id + '"]');
        $('#es-gallery-shortcode-preview').show();
        $('#es-gallery-shortcode-hint').hide();
        
        $('#es-modal-title').text('<?php _e('Edit Gallery', 'ensemble'); ?>');
        $('#es-delete-gallery-btn').show();
    }
    
    // Add images
    $('#es-add-gallery-images-btn').on('click', function(e) {
        e.preventDefault();
        
        var frame = wp.media({
            title: '<?php _e('Select Gallery Images', 'ensemble'); ?>',
            button: { text: '<?php _e('Add to Gallery', 'ensemble'); ?>' },
            multiple: true
        });
        
        frame.on('select', function() {
            var attachments = frame.state().get('selection').toJSON();
            
            attachments.forEach(function(attachment) {
                if (imageIds.indexOf(attachment.id) === -1) {
                    imageIds.push(attachment.id);
                    var thumbUrl = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
                    var html = '<div class="es-gallery-preview-item" data-id="' + attachment.id + '">';
                    html += '<img src="' + thumbUrl + '" alt="">';
                    html += '<button type="button" class="es-remove-image">&times;</button>';
                    html += '</div>';
                    $('#es-gallery-images-preview').append(html);
                }
            });
            
            $('#es-gallery-image-ids').val(imageIds.join(','));
        });
        
        frame.open();
    });
    
    // Remove image
    $(document).on('click', '.es-remove-image', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var item = $(this).closest('.es-gallery-preview-item');
        var id = parseInt(item.data('id'));
        
        imageIds = imageIds.filter(function(imgId) {
            return parseInt(imgId) !== id;
        });
        
        item.remove();
        $('#es-gallery-image-ids').val(imageIds.join(','));
    });
    
    // Initialize sortable for gallery images
    function initSortable() {
        var $preview = $('#es-gallery-images-preview');
        if ($preview.length === 0) return;
        
        if ($preview.hasClass('ui-sortable')) {
            $preview.sortable('refresh');
            return;
        }
        
        $preview.sortable({
            items: '.es-gallery-preview-item',
            cursor: 'move',
            opacity: 0.7,
            placeholder: 'es-sortable-placeholder',
            tolerance: 'pointer',
            update: function(event, ui) {
                imageIds = [];
                $preview.find('.es-gallery-preview-item').each(function() {
                    imageIds.push(parseInt($(this).data('id')));
                });
                $('#es-gallery-image-ids').val(imageIds.join(','));
            }
        });
    }
    
    // Initialize on page load
    initSortable();
    
    // Drag & drop upload zone
    var $dropContainer = $('#es-gallery-images-container');
    var $dropZone = $dropContainer.find('.es-gallery-upload-zone');
    
    // Prevent default drag behaviors on document
    $(document).on('dragover drop', function(e) {
        e.preventDefault();
    });
    
    // Drag over
    $dropContainer.on('dragover dragenter', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $dropZone.addClass('es-drag-over');
    });
    
    // Drag leave
    $dropContainer.on('dragleave', function(e) {
        e.preventDefault();
        e.stopPropagation();
        // Only remove class if we're leaving the container
        var rect = $dropContainer[0].getBoundingClientRect();
        if (e.originalEvent.clientX < rect.left || e.originalEvent.clientX > rect.right ||
            e.originalEvent.clientY < rect.top || e.originalEvent.clientY > rect.bottom) {
            $dropZone.removeClass('es-drag-over');
        }
    });
    
    // Drop handler
    $dropContainer.on('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $dropZone.removeClass('es-drag-over');
        
        var files = e.originalEvent.dataTransfer.files;
        if (files && files.length > 0) {
            handleFileUpload(files);
        }
    });
    
    // Handle file upload
    function handleFileUpload(files) {
        var imageFiles = Array.from(files).filter(function(file) {
            return file.type.match('image.*');
        });
        
        if (imageFiles.length === 0) {
            alert('<?php _e('Please select image files only.', 'ensemble'); ?>');
            return;
        }
        
        // Show loading
        var $loading = $('<div class="es-upload-progress"><span class="spinner is-active" style="float:none;"></span> <?php _e('Uploading', 'ensemble'); ?> <span class="count">0</span>/' + imageFiles.length + '</div>');
        $dropZone.append($loading);
        
        var completed = 0;
        
        imageFiles.forEach(function(file, index) {
            uploadSingleFile(file, function(id, url) {
                completed++;
                $loading.find('.count').text(completed);
                
                if (id && url) {
                    addImageToPreview(id, url);
                }
                
                if (completed >= imageFiles.length) {
                    $loading.fadeOut(300, function() {
                        $(this).remove();
                    });
                }
            });
        });
    }
    
    // Upload single file via WordPress media
    function uploadSingleFile(file, callback) {
        var formData = new FormData();
        formData.append('async-upload', file);
        formData.append('name', file.name);
        formData.append('action', 'upload-attachment');
        formData.append('_wpnonce', '<?php echo wp_create_nonce('media-form'); ?>');
        
        $.ajax({
            url: '<?php echo admin_url('async-upload.php'); ?>',
            type: 'POST',
            data: formData,
            dataType: 'json',
            processData: false,
            contentType: false,
            success: function(response) {
                if (response && response.success && response.data) {
                    var data = response.data;
                    var thumbUrl = data.sizes && data.sizes.thumbnail ? data.sizes.thumbnail.url : data.url;
                    callback(data.id, thumbUrl);
                } else {
                    console.error('Upload response error:', response);
                    callback(null, null);
                }
            },
            error: function(xhr, status, error) {
                console.error('Upload AJAX error:', error);
                callback(null, null);
            }
        });
    }
    
    // Add image to preview
    function addImageToPreview(id, url) {
        id = parseInt(id);
        
        // Check for duplicates
        if (imageIds.indexOf(id) !== -1) return;
        
        imageIds.push(id);
        
        var html = '<div class="es-gallery-preview-item" data-id="' + id + '">' +
            '<img src="' + url + '" alt="">' +
            '<button type="button" class="es-remove-image" title="<?php _e('Remove', 'ensemble'); ?>">&times;</button>' +
            '<span class="es-drag-handle dashicons dashicons-move" title="<?php _e('Drag to reorder', 'ensemble'); ?>"></span>' +
            '</div>';
        
        $('#es-gallery-images-preview').append(html);
        $('#es-gallery-image-ids').val(imageIds.join(','));
        
        // Refresh sortable
        initSortable();
    }
    
    // Upload cover image
    $('#es-upload-cover-btn').on('click', function(e) {
        e.preventDefault();
        
        var frame = wp.media({
            title: '<?php _e('Select Cover Image', 'ensemble'); ?>',
            button: { text: '<?php _e('Use as Cover', 'ensemble'); ?>' },
            multiple: false
        });
        
        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            var thumbUrl = attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;
            
            $('#es-gallery-cover-id').val(attachment.id);
            $('#es-gallery-cover-preview').html('<img src="' + thumbUrl + '" style="max-width: 150px; border-radius: 8px;">');
        });
        
        frame.open();
    });
    
    // Save gallery
    $('#es-gallery-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = {
            action: 'es_save_gallery',
            nonce: '<?php echo wp_create_nonce('ensemble_ajax'); ?>',
            gallery_id: $('#es-gallery-id').val(),
            title: $('#es-gallery-title').val(),
            description: $('#es-gallery-description').val(),
            layout: $('#es-gallery-layout').val(),
            columns: $('#es-gallery-columns').val(),
            lightbox: $('#es-gallery-lightbox').is(':checked') ? 1 : 0,
            linked_event: $('#es-gallery-linked-event').val(),
            linked_artist: $('#es-gallery-linked-artist').val(),
            linked_location: $('#es-gallery-linked-location').val(),
            featured_image_id: $('#es-gallery-cover-id').val(),
            image_ids: imageIds
        };
        
        // Get categories
        var categories = [];
        $('input[name="categories[]"]:checked').each(function() {
            categories.push($(this).val());
        });
        formData.categories = categories;
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#es-gallery-modal').fadeOut(200);
                    loadGalleries();
                    showMessage('<?php _e('Gallery saved successfully!', 'ensemble'); ?>', 'success');
                } else {
                    showMessage(response.data.message || '<?php _e('Error saving gallery', 'ensemble'); ?>', 'error');
                }
            }
        });
    });
    
    // Delete gallery
    $('#es-delete-gallery-btn').on('click', function() {
        if (!confirm('<?php _e('Are you sure you want to delete this gallery?', 'ensemble'); ?>')) {
            return;
        }
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'es_delete_gallery',
                gallery_id: currentGalleryId,
                nonce: '<?php echo wp_create_nonce('ensemble_ajax'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $('#es-gallery-modal').fadeOut(200);
                    loadGalleries();
                    showMessage('<?php _e('Gallery deleted.', 'ensemble'); ?>', 'success');
                }
            }
        });
    });
    
    // Close modal
    $('.es-modal-close, .es-modal-close-btn').on('click', function() {
        $('#es-gallery-modal').fadeOut(200);
    });
    
    // Search
    $('#es-gallery-search-btn').on('click', function() {
        var search = $('#es-gallery-search').val();
        // Implement search
    });
    
    // Copy shortcode
    $(document).on('click', '.es-copy-shortcode', function() {
        var target = $(this).data('target');
        var text = $('#' + target).text();
        navigator.clipboard.writeText(text);
        $(this).text('<?php _e('Copied!', 'ensemble'); ?>');
        setTimeout(function() {
            $('.es-copy-shortcode').text('<?php _e('Copy', 'ensemble'); ?>');
        }, 2000);
    });
    
    // Show message
    function showMessage(text, type) {
        var $msg = $('#es-message');
        $msg.removeClass('success error').addClass(type).text(text).fadeIn(200);
        setTimeout(function() {
            $msg.fadeOut(200);
        }, 3000);
    }
    
    // Initial load
    loadGalleries();
});
</script>
