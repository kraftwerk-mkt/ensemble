<?php
/**
 * Gallery Manager Template
 *
 * @package Ensemble
 * @since 3.0.0 - Video Support added
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$manager = new ES_Gallery_Manager();

// Get dynamic labels
$gallery_singular = __('Gallery', 'ensemble');
$gallery_plural = __('Galleries', 'ensemble');
$artist_label = class_exists('ES_Label_System') ? ES_Label_System::get_label('artist', false) : __('Artist', 'ensemble');
$location_label = class_exists('ES_Label_System') ? ES_Label_System::get_label('location', false) : __('Location', 'ensemble');
$event_label = class_exists('ES_Label_System') ? ES_Label_System::get_label('event', false) : __('Event', 'ensemble');

// Get available events, artists, locations for linking
$event_post_type = function_exists('ensemble_get_post_type') ? ensemble_get_post_type() : 'ensemble_event';
$events = get_posts(array(
    'post_type'      => $event_post_type,
    'posts_per_page' => -1,
    'orderby'        => 'title',
    'order'          => 'ASC',
    'post_status'    => array('publish', 'draft'),
));

$artists = get_posts(array(
    'post_type'      => 'ensemble_artist',
    'posts_per_page' => -1,
    'orderby'        => 'title',
    'order'          => 'ASC',
    'post_status'    => 'publish',
));

$locations = get_posts(array(
    'post_type'      => 'ensemble_location',
    'posts_per_page' => -1,
    'orderby'        => 'title',
    'order'          => 'ASC',
    'post_status'    => 'publish',
));

// Get categories
$categories = get_terms(array(
    'taxonomy'   => 'ensemble_gallery_category',
    'hide_empty' => false,
));
if (is_wp_error($categories)) {
    $categories = array();
}

// Nonce for AJAX
$ajax_nonce = wp_create_nonce('ensemble_ajax');
?>

<div class="wrap es-manager-wrap es-galleries-wrap">
    <h1><?php echo esc_html($gallery_plural); ?> Manager</h1>
    
    <div class="es-manager-container">
        
        <!-- Toolbar -->
        <div class="es-wizard-toolbar es-gallery-toolbar">
            <div class="es-toolbar-row es-toolbar-main-row">
                <div class="es-filter-search">
                    <input type="text" 
                           id="es-gallery-search" 
                           class="es-search-input" 
                           placeholder="<?php printf(__('Search %s...', 'ensemble'), strtolower($gallery_plural)); ?>">
                    <span class="es-search-icon"><span class="dashicons dashicons-search"></span></span>
                </div>
                
                <span class="es-toolbar-divider"></span>
                
                <div class="es-bulk-actions-inline" id="es-bulk-actions">
                    <span class="es-bulk-selected-count" id="es-selected-count" style="display:none;"></span>
                    <select id="es-bulk-action-select">
                        <option value=""><?php _e('Bulk Actions', 'ensemble'); ?></option>
                        <option value="delete"><?php _e('Delete', 'ensemble'); ?></option>
                    </select>
                    <button id="es-apply-bulk-action" class="button">
                        <span class="dashicons dashicons-yes"></span>
                    </button>
                </div>
                
                <div class="es-toolbar-spacer"></div>
                
                <div class="es-view-toggle">
                    <button class="es-view-btn active" data-view="grid" title="<?php _e('Grid View', 'ensemble'); ?>">
                        <span class="dashicons dashicons-grid-view"></span>
                    </button>
                    <button class="es-view-btn" data-view="list" title="<?php _e('List View', 'ensemble'); ?>">
                        <span class="dashicons dashicons-list-view"></span>
                    </button>
                </div>
                
                <span id="es-gallery-count" class="es-item-count"></span>
                
                <button id="es-create-gallery-btn" class="button button-primary">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <?php printf(__('Add %s', 'ensemble'), $gallery_singular); ?>
                </button>
            </div>
        </div>
        
        <!-- Galleries Container -->
        <div id="es-galleries-container" class="es-items-container es-view-grid">
            <div class="es-loading"><?php _e('Loading...', 'ensemble'); ?></div>
        </div>
        
    </div>
    
    <!-- Gallery Modal -->
    <div id="es-gallery-modal" class="es-modal" style="display: none;">
        <div class="es-modal-content es-modal-large es-modal-scrollable">
            <span class="es-modal-close">&times;</span>
            
            <div class="es-modal-header">
                <h2 id="es-modal-title"><?php printf(__('Add New %s', 'ensemble'), $gallery_singular); ?></h2>
            </div>
            
            <form id="es-gallery-form" class="es-manager-form">
                <input type="hidden" id="es-gallery-id" name="gallery_id" value="">
                
                <div class="es-form-sections">
                    
                    <!-- Basic Info -->
                    <div class="es-form-section">
                        <div class="es-form-row">
                            <div class="es-form-field es-form-field-large">
                                <label for="es-gallery-title"><?php _e('Title', 'ensemble'); ?> <span class="required">*</span></label>
                                <input type="text" id="es-gallery-title" name="title" required>
                            </div>
                        </div>
                        
                        <div class="es-form-row">
                            <div class="es-form-field">
                                <label for="es-gallery-description"><?php _e('Description', 'ensemble'); ?></label>
                                <textarea id="es-gallery-description" name="description" rows="3"></textarea>
                            </div>
                        </div>
                        
                        <div class="es-form-row es-form-row-3col">
                            <div class="es-form-field">
                                <label for="es-gallery-category"><?php _e('Category', 'ensemble'); ?></label>
                                <select id="es-gallery-category" name="categories[]" multiple>
                                    <?php foreach ($categories as $cat) : ?>
                                        <option value="<?php echo esc_attr($cat->term_id); ?>"><?php echo esc_html($cat->name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="es-form-field">
                                <label for="es-gallery-layout"><?php _e('Layout', 'ensemble'); ?></label>
                                <select id="es-gallery-layout" name="layout">
                                    <option value="grid"><?php _e('Grid', 'ensemble'); ?></option>
                                    <option value="masonry"><?php _e('Masonry', 'ensemble'); ?></option>
                                    <option value="carousel"><?php _e('Carousel', 'ensemble'); ?></option>
                                    <option value="filmstrip"><?php _e('Filmstrip', 'ensemble'); ?></option>
                                </select>
                            </div>
                            
                            <div class="es-form-field">
                                <label for="es-gallery-columns"><?php _e('Columns', 'ensemble'); ?></label>
                                <input type="number" id="es-gallery-columns" name="columns" value="4" min="2" max="8">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Link Section -->
                    <div class="es-form-section">
                        <h3 class="es-form-section-title">
                            <span class="dashicons dashicons-admin-links"></span>
                            <?php _e('Link to', 'ensemble'); ?>
                        </h3>
                        <p class="es-form-section-desc"><?php _e('Link this gallery to an event, artist, or location.', 'ensemble'); ?></p>
                        
                        <div class="es-form-row es-form-row-3col">
                            <div class="es-form-field">
                                <label><span class="dashicons dashicons-calendar-alt"></span> <?php echo esc_html($event_label); ?></label>
                                <select id="es-gallery-linked-event" name="linked_event">
                                    <option value=""><?php _e('— None —', 'ensemble'); ?></option>
                                    <?php foreach ($events as $event) : ?>
                                        <option value="<?php echo esc_attr($event->ID); ?>"><?php echo esc_html($event->post_title); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="es-form-field">
                                <label><span class="dashicons dashicons-admin-users"></span> <?php echo esc_html($artist_label); ?></label>
                                <select id="es-gallery-linked-artist" name="linked_artist">
                                    <option value=""><?php _e('— None —', 'ensemble'); ?></option>
                                    <?php foreach ($artists as $artist) : ?>
                                        <option value="<?php echo esc_attr($artist->ID); ?>"><?php echo esc_html($artist->post_title); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="es-form-field">
                                <label><span class="dashicons dashicons-location"></span> <?php echo esc_html($location_label); ?></label>
                                <select id="es-gallery-linked-location" name="linked_location">
                                    <option value=""><?php _e('— None —', 'ensemble'); ?></option>
                                    <?php foreach ($locations as $location) : ?>
                                        <option value="<?php echo esc_attr($location->ID); ?>"><?php echo esc_html($location->post_title); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Images Section -->
                    <div class="es-form-section">
                        <h3 class="es-form-section-title">
                            <span class="dashicons dashicons-format-image"></span>
                            <?php _e('Images', 'ensemble'); ?>
                        </h3>
                        
                        <div class="es-gallery-media-container">
                            <div class="es-gallery-images-grid" id="es-gallery-images-grid"></div>
                            
                            <div class="es-gallery-upload-zone" id="es-gallery-upload-zone">
                                <span class="dashicons dashicons-upload"></span>
                                <p><?php _e('Drop images here or click to upload', 'ensemble'); ?></p>
                            </div>
                            
                            <button type="button" class="button es-add-images-btn">
                                <span class="dashicons dashicons-plus-alt2"></span>
                                <?php _e('Add Images', 'ensemble'); ?>
                            </button>
                        </div>
                        <input type="hidden" name="image_ids" id="es-gallery-image-ids" value="">
                    </div>
                    
                    <!-- Videos Section -->
                    <div class="es-form-section">
                        <h3 class="es-form-section-title">
                            <span class="dashicons dashicons-video-alt3"></span>
                            <?php _e('Videos', 'ensemble'); ?>
                        </h3>
                        <p class="es-form-section-desc"><?php _e('Add YouTube, Vimeo, or self-hosted videos.', 'ensemble'); ?></p>
                        
                        <div class="es-gallery-videos-list" id="es-gallery-videos-list"></div>
                        
                        <div class="es-video-add-row">
                            <input type="url" id="es-new-video-url" placeholder="<?php esc_attr_e('Paste YouTube, Vimeo, or video URL...', 'ensemble'); ?>">
                            <button type="button" class="button es-add-video-btn">
                                <span class="dashicons dashicons-plus-alt2"></span>
                                <?php _e('Add Video', 'ensemble'); ?>
                            </button>
                        </div>
                        <p class="es-field-hint">
                            <?php _e('Supported:', 'ensemble'); ?> 
                            <code>youtube.com</code>, <code>youtu.be</code>, <code>vimeo.com</code>, <code>.mp4</code>, <code>.webm</code>
                        </p>
                        <input type="hidden" name="videos" id="es-gallery-videos-data" value="[]">
                    </div>
                    
                </div>
                
                <div class="es-modal-footer">
                    <button type="button" class="button es-modal-cancel"><?php _e('Cancel', 'ensemble'); ?></button>
                    <button type="submit" class="button button-primary">
                        <span class="dashicons dashicons-saved"></span>
                        <?php _e('Save Gallery', 'ensemble'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Templates -->
<script type="text/html" id="tmpl-es-gallery-card">
    <div class="es-item-card es-gallery-card" data-id="{{ data.id }}">
        <div class="es-card-select">
            <input type="checkbox" class="es-select-item" data-id="{{ data.id }}">
        </div>
        <div class="es-card-thumbnail es-gallery-thumbnail">
            <# if (data.featured_image) { #>
                <img src="{{ data.featured_image }}" alt="">
            <# } else if (data.images && data.images.length) { #>
                <img src="{{ data.images[0].url }}" alt="">
            <# } else { #>
                <div class="es-card-placeholder"><span class="dashicons dashicons-format-gallery"></span></div>
            <# } #>
            <div class="es-gallery-counts">
                <# if (data.image_count > 0) { #>
                    <span class="es-count-badge"><span class="dashicons dashicons-format-image"></span> {{ data.image_count }}</span>
                <# } #>
                <# if (data.video_count > 0) { #>
                    <span class="es-count-badge es-count-video"><span class="dashicons dashicons-video-alt3"></span> {{ data.video_count }}</span>
                <# } #>
            </div>
            <# if (data.source_type) { #>
                <span class="es-source-badge es-source-{{ data.source_type }}">{{ data.source_label }}</span>
            <# } #>
        </div>
        <div class="es-card-content">
            <h3 class="es-card-title">{{ data.title }}</h3>
            <# if (data.category) { #>
                <div class="es-card-meta">{{ data.category }}</div>
            <# } #>
            <div class="es-gallery-links">
                <# if (data.linked_event) { #>
                    <span class="es-link-tag es-link-event"><span class="dashicons dashicons-calendar-alt"></span> {{ data.linked_event.title }}</span>
                <# } #>
                <# if (data.linked_artist) { #>
                    <span class="es-link-tag es-link-artist"><span class="dashicons dashicons-admin-users"></span> {{ data.linked_artist.title }}</span>
                <# } #>
                <# if (data.linked_location) { #>
                    <span class="es-link-tag es-link-location"><span class="dashicons dashicons-location"></span> {{ data.linked_location.title }}</span>
                <# } #>
            </div>
        </div>
        <div class="es-card-actions">
            <button type="button" class="es-btn-icon es-edit-item" data-id="{{ data.id }}" title="<?php _e('Edit', 'ensemble'); ?>">
                <span class="dashicons dashicons-edit"></span>
            </button>
            <button type="button" class="es-btn-icon es-delete-item" data-id="{{ data.id }}" title="<?php _e('Delete', 'ensemble'); ?>">
                <span class="dashicons dashicons-trash"></span>
            </button>
        </div>
    </div>
</script>

<script type="text/html" id="tmpl-es-gallery-row">
    <div class="es-item-row" data-id="{{ data.id }}">
        <div class="es-row-select"><input type="checkbox" class="es-select-item" data-id="{{ data.id }}"></div>
        <div class="es-row-thumbnail">
            <# if (data.featured_image || (data.images && data.images.length)) { #>
                <img src="{{ data.featured_image || data.images[0].url }}" alt="">
            <# } else { #>
                <span class="dashicons dashicons-format-gallery"></span>
            <# } #>
        </div>
        <div class="es-row-title">{{ data.title }}</div>
        <div class="es-row-meta">
            <# if (data.image_count > 0) { #>{{ data.image_count }} <?php _e('images', 'ensemble'); ?><# } #>
            <# if (data.video_count > 0) { #>, {{ data.video_count }} <?php _e('videos', 'ensemble'); ?><# } #>
        </div>
        <div class="es-row-links">
            <# if (data.linked_event) { #><span class="es-link-tag es-link-event">{{ data.linked_event.title }}</span><# } #>
            <# if (data.linked_artist) { #><span class="es-link-tag es-link-artist">{{ data.linked_artist.title }}</span><# } #>
            <# if (data.linked_location) { #><span class="es-link-tag es-link-location">{{ data.linked_location.title }}</span><# } #>
        </div>
        <div class="es-row-actions">
            <button type="button" class="es-btn-icon es-edit-item" data-id="{{ data.id }}"><span class="dashicons dashicons-edit"></span></button>
            <button type="button" class="es-btn-icon es-delete-item" data-id="{{ data.id }}"><span class="dashicons dashicons-trash"></span></button>
        </div>
    </div>
</script>

<script type="text/html" id="tmpl-es-image-item">
    <div class="es-media-item es-image-item" data-id="{{ data.id }}">
        <img src="{{ data.url }}" alt="">
        <button type="button" class="es-media-remove"><span class="dashicons dashicons-no-alt"></span></button>
        <span class="es-media-drag"><span class="dashicons dashicons-move"></span></span>
    </div>
</script>

<script type="text/html" id="tmpl-es-video-item">
    <div class="es-video-item" data-index="{{ data.index }}">
        <div class="es-video-thumb">
            <# if (data.thumbnail) { #>
                <img src="{{ data.thumbnail }}" alt="">
            <# } else { #>
                <span class="dashicons dashicons-video-alt3"></span>
            <# } #>
            <span class="es-video-provider es-provider-{{ data.provider }}">{{ data.provider }}</span>
        </div>
        <div class="es-video-info">
            <input type="text" class="es-video-title-input" value="{{ data.title }}" placeholder="<?php esc_attr_e('Video title (optional)', 'ensemble'); ?>">
            <span class="es-video-url-display">{{ data.url }}</span>
        </div>
        <button type="button" class="es-video-remove"><span class="dashicons dashicons-no-alt"></span></button>
    </div>
</script>

<style>
/* Gallery Manager Specific Styles */
.es-galleries-wrap .es-gallery-thumbnail {
    position: relative;
    aspect-ratio: 16/10;
    background: #f0f0f1;
}
.es-gallery-counts {
    position: absolute;
    bottom: 8px;
    right: 8px;
    display: flex;
    gap: 6px;
}
.es-count-badge {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    padding: 4px 8px;
    background: rgba(0,0,0,0.75);
    color: #fff;
    font-size: 11px;
    border-radius: 4px;
}
.es-count-badge .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}
.es-source-badge {
    position: absolute;
    top: 8px;
    left: 8px;
    padding: 3px 8px;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    border-radius: 3px;
    background: #3858e9;
    color: #fff;
}
.es-source-event { background: #2e7d32; }
.es-source-artist { background: #1565c0; }
.es-source-location { background: #e65100; }

.es-gallery-links {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 8px;
}
.es-link-tag {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 8px;
    font-size: 11px;
    border-radius: 4px;
    background: #f0f0f1;
    color: #50575e;
}
.es-link-tag .dashicons {
    font-size: 12px;
    width: 12px;
    height: 12px;
}
.es-link-event { background: #e8f5e9; color: #2e7d32; }
.es-link-artist { background: #e3f2fd; color: #1565c0; }
.es-link-location { background: #fff3e0; color: #e65100; }

/* Form Section */
.es-form-section {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #e0e0e0;
}
.es-form-section:first-child {
    margin-top: 0;
    padding-top: 0;
    border-top: none;
}
.es-form-section-title {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0 0 8px;
    font-size: 14px;
    font-weight: 600;
}
.es-form-section-desc {
    margin: 0 0 15px;
    color: #646970;
    font-size: 13px;
}
.es-form-row-3col {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
}
@media (max-width: 782px) {
    .es-form-row-3col { grid-template-columns: 1fr; }
}

/* Media Container */
.es-gallery-media-container {
    border: 2px dashed #c3c4c7;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 10px;
    min-height: 120px;
}
.es-gallery-images-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(90px, 1fr));
    gap: 10px;
}
.es-gallery-upload-zone {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 30px;
    color: #646970;
    text-align: center;
}
.es-gallery-upload-zone .dashicons {
    font-size: 32px;
    width: 32px;
    height: 32px;
    margin-bottom: 10px;
    opacity: 0.5;
}
.es-gallery-images-grid:not(:empty) ~ .es-gallery-upload-zone { display: none; }
.es-gallery-images-grid:empty ~ .es-add-images-btn { display: none; }

.es-media-item {
    position: relative;
    aspect-ratio: 1;
    border-radius: 6px;
    overflow: hidden;
    cursor: grab;
}
.es-media-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.es-media-remove {
    position: absolute;
    top: 4px;
    right: 4px;
    width: 22px;
    height: 22px;
    padding: 0;
    border: none;
    background: rgba(0,0,0,0.7);
    color: #fff;
    border-radius: 50%;
    cursor: pointer;
    opacity: 0;
    transition: opacity 0.15s;
    display: flex;
    align-items: center;
    justify-content: center;
}
.es-media-item:hover .es-media-remove { opacity: 1; }
.es-media-drag {
    position: absolute;
    bottom: 4px;
    left: 4px;
    width: 22px;
    height: 22px;
    background: rgba(0,0,0,0.5);
    color: #fff;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.15s;
}
.es-media-item:hover .es-media-drag { opacity: 1; }

/* Videos */
.es-gallery-videos-list {
    margin-bottom: 15px;
}
.es-video-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px;
    background: #f6f7f7;
    border: 1px solid #dcdcde;
    border-radius: 6px;
    margin-bottom: 8px;
}
.es-video-thumb {
    position: relative;
    width: 100px;
    height: 56px;
    flex-shrink: 0;
    border-radius: 4px;
    overflow: hidden;
    background: #1d2327;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #646970;
}
.es-video-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.es-video-provider {
    position: absolute;
    bottom: 4px;
    left: 4px;
    padding: 2px 6px;
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
    color: #fff;
    border-radius: 3px;
    background: #50575e;
}
.es-provider-youtube { background: #ff0000; }
.es-provider-vimeo { background: #1ab7ea; }
.es-provider-local { background: #50575e; }
.es-video-info {
    flex: 1;
    min-width: 0;
}
.es-video-title-input {
    width: 100%;
    margin-bottom: 4px;
}
.es-video-url-display {
    display: block;
    font-size: 11px;
    color: #646970;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.es-video-remove {
    padding: 6px;
    border: none;
    background: transparent;
    color: #646970;
    cursor: pointer;
    border-radius: 4px;
}
.es-video-remove:hover {
    background: #fcecec;
    color: #d63638;
}
.es-video-add-row {
    display: flex;
    gap: 10px;
}
.es-video-add-row input {
    flex: 1;
}
.es-field-hint {
    margin: 10px 0 0;
    font-size: 12px;
    color: #646970;
}
.es-field-hint code {
    padding: 2px 5px;
    background: #f0f0f1;
    border-radius: 3px;
}
</style>

<script>
jQuery(document).ready(function($) {
    const nonce = '<?php echo esc_js($ajax_nonce); ?>';
    
    const GalleryManager = {
        galleries: [],
        images: [],
        videos: [],
        currentView: 'grid',
        
        init: function() {
            this.bindEvents();
            this.loadGalleries();
            this.initSortable();
        },
        
        bindEvents: function() {
            $('#es-create-gallery-btn').on('click', () => this.openModal());
            $('.es-modal-close, .es-modal-cancel').on('click', () => this.closeModal());
            $('#es-gallery-form').on('submit', (e) => this.saveGallery(e));
            $(document).on('click', '.es-edit-item', (e) => this.editGallery(e));
            $(document).on('click', '.es-delete-item', (e) => this.deleteGallery(e));
            $(document).on('click', '.es-add-images-btn, .es-gallery-upload-zone', () => this.openMediaLibrary());
            $(document).on('click', '.es-media-remove', (e) => this.removeImage(e));
            $(document).on('click', '.es-add-video-btn', () => this.addVideo());
            $('#es-new-video-url').on('keypress', (e) => { if (e.which === 13) { e.preventDefault(); this.addVideo(); }});
            $(document).on('click', '.es-video-remove', (e) => this.removeVideo(e));
            $(document).on('change', '.es-video-title-input', (e) => this.updateVideoTitle(e));
            $('.es-view-btn').on('click', (e) => this.toggleView(e));
            $('#es-gallery-search').on('input', this.debounce(() => this.filterGalleries(), 300));
            $(document).on('change', '.es-select-item', () => this.updateSelection());
            $('#es-apply-bulk-action').on('click', () => this.applyBulkAction());
        },
        
        initSortable: function() {
            $('#es-gallery-images-grid').sortable({
                items: '.es-media-item',
                tolerance: 'pointer',
                update: () => this.updateImageOrder()
            });
        },
        
        loadGalleries: function() {
            $('#es-galleries-container').html('<div class="es-loading"><?php _e('Loading...', 'ensemble'); ?></div>');
            
            $.post(ajaxurl, { action: 'ensemble_get_galleries', nonce: nonce }, (response) => {
                if (response.success) {
                    this.galleries = response.data || [];
                    this.renderGalleries();
                } else {
                    $('#es-galleries-container').html('<div class="es-empty-state"><p><?php _e('Error loading galleries', 'ensemble'); ?></p></div>');
                }
            }).fail(() => {
                $('#es-galleries-container').html('<div class="es-empty-state"><p><?php _e('Error loading galleries', 'ensemble'); ?></p></div>');
            });
        },
        
        renderGalleries: function() {
            const $container = $('#es-galleries-container');
            const tmpl = this.currentView === 'grid' ? wp.template('es-gallery-card') : wp.template('es-gallery-row');
            
            if (!this.galleries.length) {
                $container.html(`
                    <div class="es-empty-state">
                        <span class="dashicons dashicons-format-gallery"></span>
                        <h3><?php _e('No galleries yet', 'ensemble'); ?></h3>
                        <p><?php _e('Create your first gallery to showcase images and videos.', 'ensemble'); ?></p>
                        <button type="button" class="button button-primary" onclick="jQuery('#es-create-gallery-btn').click()">
                            <?php _e('Create Gallery', 'ensemble'); ?>
                        </button>
                    </div>
                `);
                return;
            }
            
            $container.removeClass('es-view-grid es-view-list').addClass('es-view-' + this.currentView);
            $container.html(this.galleries.map(g => tmpl(g)).join(''));
            $('#es-gallery-count').text(this.galleries.length + ' <?php _e('galleries', 'ensemble'); ?>');
        },
        
        openModal: function(data) {
            this.resetForm();
            if (data) {
                this.loadGalleryData(data);
                $('#es-modal-title').text('<?php printf(__('Edit %s', 'ensemble'), $gallery_singular); ?>');
            } else {
                $('#es-modal-title').text('<?php printf(__('Add New %s', 'ensemble'), $gallery_singular); ?>');
            }
            $('#es-gallery-modal').fadeIn(200);
        },
        
        closeModal: function() {
            $('#es-gallery-modal').fadeOut(200);
        },
        
        resetForm: function() {
            this.images = [];
            this.videos = [];
            $('#es-gallery-form')[0].reset();
            $('#es-gallery-id').val('');
            this.renderImages();
            this.renderVideos();
        },
        
        loadGalleryData: function(data) {
            $('#es-gallery-id').val(data.id);
            $('#es-gallery-title').val(data.title);
            $('#es-gallery-description').val(data.description);
            $('#es-gallery-layout').val(data.layout || 'grid');
            $('#es-gallery-columns').val(data.columns || 4);
            
            if (data.categories && data.categories.length) {
                $('#es-gallery-category').val(data.categories.map(c => c.id));
            }
            
            $('#es-gallery-linked-event').val(data.linked_event ? data.linked_event.id : '');
            $('#es-gallery-linked-artist').val(data.linked_artist ? data.linked_artist.id : '');
            $('#es-gallery-linked-location').val(data.linked_location ? data.linked_location.id : '');
            
            this.images = data.images || [];
            this.videos = data.videos || [];
            this.renderImages();
            this.renderVideos();
        },
        
        editGallery: function(e) {
            e.preventDefault();
            const id = $(e.currentTarget).data('id');
            
            $.post(ajaxurl, { action: 'ensemble_get_gallery', gallery_id: id, nonce: nonce }, (response) => {
                if (response.success) {
                    this.openModal(response.data);
                }
            });
        },
        
        saveGallery: function(e) {
            e.preventDefault();
            const $btn = $('#es-gallery-form button[type="submit"]').prop('disabled', true);
            
            $.post(ajaxurl, {
                action: 'ensemble_save_gallery',
                nonce: nonce,
                gallery_id: $('#es-gallery-id').val(),
                title: $('#es-gallery-title').val(),
                description: $('#es-gallery-description').val(),
                categories: $('#es-gallery-category').val() || [],
                layout: $('#es-gallery-layout').val(),
                columns: $('#es-gallery-columns').val(),
                linked_event: $('#es-gallery-linked-event').val(),
                linked_artist: $('#es-gallery-linked-artist').val(),
                linked_location: $('#es-gallery-linked-location').val(),
                image_ids: this.images.map(i => i.id).join(','),
                videos: JSON.stringify(this.videos)
            }, (response) => {
                $btn.prop('disabled', false);
                if (response.success) {
                    this.closeModal();
                    this.loadGalleries();
                } else {
                    alert(response.data?.message || '<?php _e('Error saving', 'ensemble'); ?>');
                }
            });
        },
        
        deleteGallery: function(e) {
            e.preventDefault();
            if (!confirm('<?php _e('Delete this gallery?', 'ensemble'); ?>')) return;
            
            const id = $(e.currentTarget).data('id');
            $.post(ajaxurl, { action: 'ensemble_delete_gallery', gallery_id: id, nonce: nonce }, (response) => {
                if (response.success) {
                    this.galleries = this.galleries.filter(g => g.id !== id);
                    this.renderGalleries();
                }
            });
        },
        
        openMediaLibrary: function() {
            const frame = wp.media({
                title: '<?php _e('Select Images', 'ensemble'); ?>',
                button: { text: '<?php _e('Add to Gallery', 'ensemble'); ?>' },
                multiple: true,
                library: { type: 'image' }
            });
            
            frame.on('select', () => {
                frame.state().get('selection').toJSON().forEach(att => {
                    if (!this.images.find(i => i.id === att.id)) {
                        this.images.push({
                            id: att.id,
                            url: att.sizes?.medium?.url || att.url,
                            full: att.url
                        });
                    }
                });
                this.renderImages();
            });
            frame.open();
        },
        
        renderImages: function() {
            const $grid = $('#es-gallery-images-grid').empty();
            const tmpl = wp.template('es-image-item');
            this.images.forEach(img => $grid.append(tmpl(img)));
            $('#es-gallery-image-ids').val(this.images.map(i => i.id).join(','));
            
            if (this.images.length) {
                $('.es-gallery-upload-zone').hide();
                $('.es-add-images-btn').show();
            } else {
                $('.es-gallery-upload-zone').show();
                $('.es-add-images-btn').hide();
            }
        },
        
        removeImage: function(e) {
            e.preventDefault();
            const id = $(e.currentTarget).closest('.es-media-item').data('id');
            this.images = this.images.filter(i => i.id !== id);
            this.renderImages();
        },
        
        updateImageOrder: function() {
            const order = [];
            $('#es-gallery-images-grid .es-media-item').each(function() {
                const id = $(this).data('id');
                const img = GalleryManager.images.find(i => i.id === id);
                if (img) order.push(img);
            });
            this.images = order;
            $('#es-gallery-image-ids').val(this.images.map(i => i.id).join(','));
        },
        
        addVideo: function() {
            const url = $('#es-new-video-url').val().trim();
            if (!url) return;
            
            const video = this.parseVideoUrl(url);
            if (!video) {
                alert('<?php _e('Invalid video URL', 'ensemble'); ?>');
                return;
            }
            
            this.videos.push(video);
            this.renderVideos();
            $('#es-new-video-url').val('');
        },
        
        parseVideoUrl: function(url) {
            let provider = 'local', id = '', thumbnail = '';
            
            // YouTube
            let match = url.match(/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]+)/);
            if (match) {
                provider = 'youtube';
                id = match[1];
                thumbnail = 'https://img.youtube.com/vi/' + id + '/hqdefault.jpg';
            }
            
            // Vimeo
            if (!match) {
                match = url.match(/vimeo\.com\/(?:video\/)?(\d+)/);
                if (match) {
                    provider = 'vimeo';
                    id = match[1];
                }
            }
            
            // Local video
            if (!match && url.match(/\.(mp4|webm|ogg|mov|m4v)$/i)) {
                provider = 'local';
            }
            
            if (!match && provider === 'local' && !url.match(/\.(mp4|webm|ogg|mov|m4v)$/i)) {
                return null;
            }
            
            return { url: url, title: '', provider: provider, thumbnail: thumbnail };
        },
        
        renderVideos: function() {
            const $list = $('#es-gallery-videos-list').empty();
            const tmpl = wp.template('es-video-item');
            this.videos.forEach((v, i) => {
                v.index = i;
                $list.append(tmpl(v));
            });
            $('#es-gallery-videos-data').val(JSON.stringify(this.videos));
        },
        
        removeVideo: function(e) {
            const idx = $(e.currentTarget).closest('.es-video-item').data('index');
            this.videos.splice(idx, 1);
            this.renderVideos();
        },
        
        updateVideoTitle: function(e) {
            const idx = $(e.target).closest('.es-video-item').data('index');
            this.videos[idx].title = $(e.target).val();
            $('#es-gallery-videos-data').val(JSON.stringify(this.videos));
        },
        
        toggleView: function(e) {
            const view = $(e.currentTarget).data('view');
            $('.es-view-btn').removeClass('active');
            $(e.currentTarget).addClass('active');
            this.currentView = view;
            this.renderGalleries();
        },
        
        filterGalleries: function() {
            const q = $('#es-gallery-search').val().toLowerCase();
            const filtered = q ? this.galleries.filter(g => 
                g.title.toLowerCase().includes(q) || (g.category && g.category.toLowerCase().includes(q))
            ) : this.galleries;
            
            const $container = $('#es-galleries-container');
            const tmpl = this.currentView === 'grid' ? wp.template('es-gallery-card') : wp.template('es-gallery-row');
            $container.html(filtered.length ? filtered.map(g => tmpl(g)).join('') : '<div class="es-empty-state"><p><?php _e('No galleries found', 'ensemble'); ?></p></div>');
        },
        
        updateSelection: function() {
            const count = $('.es-select-item:checked').length;
            $('#es-selected-count').text(count + ' <?php _e('selected', 'ensemble'); ?>').toggle(count > 0);
        },
        
        applyBulkAction: function() {
            const action = $('#es-bulk-action-select').val();
            const ids = [];
            $('.es-select-item:checked').each(function() { ids.push($(this).data('id')); });
            
            if (!action || !ids.length) return;
            
            if (action === 'delete' && confirm('<?php _e('Delete selected galleries?', 'ensemble'); ?>')) {
                $.post(ajaxurl, { action: 'ensemble_bulk_delete_galleries', gallery_ids: ids, nonce: nonce }, () => this.loadGalleries());
            }
        },
        
        debounce: function(fn, ms) {
            let t;
            return function(...args) { clearTimeout(t); t = setTimeout(() => fn.apply(this, args), ms); };
        }
    };
    
    GalleryManager.init();
});
</script>
