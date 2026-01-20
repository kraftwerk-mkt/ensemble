<?php
/**
 * Artist Manager Template
 *
 * @package Ensemble
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$manager = new ES_Artist_Manager();
$wizard = new ES_Wizard();
$genres = $wizard->get_genres();
$artist_types = $wizard->get_artist_types();

// Get dynamic labels
$artist_singular = ES_Label_System::get_label('artist', false);
$artist_plural = ES_Label_System::get_label('artist', true);
?>

<div class="wrap es-manager-wrap es-artists-wrap">
    <h1><?php echo esc_html($artist_plural); ?> Manager</h1>
    
    <?php if (!function_exists('acf')): ?>
    <div class="notice notice-error" style="margin: 20px 0; padding: 15px; border-left: 4px solid #dc3232;">
        <h2 style="margin-top: 0;"><?php ES_Icons::icon('warning'); ?> <?php _e('Advanced Custom Fields (ACF) Required', 'ensemble'); ?></h2>
        <p style="font-size: 14px; margin: 10px 0;">
            <strong><?php _e('ACF must be installed and activated for Ensemble to work correctly.', 'ensemble'); ?></strong>
        </p>
        <p style="margin: 15px 0 10px 0;">
            <a href="<?php echo admin_url('plugin-install.php?s=advanced+custom+fields&tab=search&type=term'); ?>" 
               class="button button-primary">
                <?php _e('Install ACF Now', 'ensemble'); ?>
            </a>
        </p>
    </div>
    <?php endif; ?>
    
    <div class="es-manager-container">
        
        <!-- Compact Toolbar (analog zu Event Wizard) -->
        <div class="es-wizard-toolbar es-artist-toolbar">
            <!-- Main Row: Search, Filter Toggle, Bulk Actions, View Toggle, Create -->
            <div class="es-toolbar-row es-toolbar-main-row">
                <div class="es-filter-search">
                    <input type="text" 
                           id="es-artist-search" 
                           class="es-search-input" 
                           placeholder="<?php printf(__('Search %s...', 'ensemble'), strtolower($artist_plural)); ?>">
                    <span class="es-search-icon"><?php ES_Icons::icon('search'); ?></span>
                </div>
                
                <!-- Filter Toggle Button -->
                <button type="button" id="es-toggle-filters" class="button es-filter-toggle-btn" title="<?php _e('Show/Hide Filters', 'ensemble'); ?>">
                    <span class="dashicons dashicons-filter"></span>
                    <span class="es-filter-badge" style="display:none;">0</span>
                </button>
                
                <span class="es-toolbar-divider"></span>
                
                <!-- Inline Bulk Actions -->
                <div class="es-bulk-actions-inline" id="es-bulk-actions">
                    <span class="es-bulk-selected-count" id="es-selected-count"></span>
                    <select id="es-bulk-action-select" title="<?php _e('Bulk Actions', 'ensemble'); ?>">
                        <option value=""><?php _e('Bulk Actions', 'ensemble'); ?></option>
                        <option value="delete"><?php _e('Delete', 'ensemble'); ?></option>
                        <option value="assign_genre"><?php _e('Assign Genre', 'ensemble'); ?></option>
                        <option value="assign_type"><?php _e('Assign Type', 'ensemble'); ?></option>
                        <option value="remove_genre"><?php _e('Remove Genre', 'ensemble'); ?></option>
                        <option value="remove_type"><?php _e('Remove Type', 'ensemble'); ?></option>
                    </select>
                    <button id="es-apply-bulk-action" class="button" title="<?php _e('Apply Bulk Action', 'ensemble'); ?>">
                        <span class="dashicons dashicons-yes"></span>
                    </button>
                </div>
                
                <div class="es-toolbar-spacer"></div>
                
                <!-- View Toggle -->
                <div class="es-view-toggle">
                    <button class="es-view-btn active" data-view="grid" title="<?php _e('Grid View', 'ensemble'); ?>">
                        <span class="dashicons dashicons-grid-view"></span>
                    </button>
                    <button class="es-view-btn" data-view="list" title="<?php _e('List View', 'ensemble'); ?>">
                        <span class="dashicons dashicons-list-view"></span>
                    </button>
                </div>
                
                <!-- Artist Count -->
                <span id="es-artist-count" class="es-item-count"></span>
                
                <button id="es-bulk-quick-add-btn" class="button">
                    <?php ES_Icons::icon('lightning'); ?>
                    <?php _e('Quick Add', 'ensemble'); ?>
                </button>
                
                <button id="es-create-artist-btn" class="button button-primary">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <?php printf(__('Add %s', 'ensemble'), $artist_singular); ?>
                </button>
            </div>
            
            <!-- Collapsible Filter Panel -->
            <div class="es-toolbar-row es-filter-panel" id="es-filter-panel" style="display:none;">
                <select id="es-filter-genre" class="es-filter-select" data-filter="genre">
                    <option value=""><?php _e('All Genres', 'ensemble'); ?></option>
                    <?php foreach ($genres as $genre): ?>
                    <option value="<?php echo esc_attr($genre['id']); ?>"><?php echo esc_html($genre['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                
                <select id="es-filter-type" class="es-filter-select" data-filter="type">
                    <option value=""><?php _e('All Types', 'ensemble'); ?></option>
                    <?php foreach ($artist_types as $type): ?>
                    <option value="<?php echo esc_attr($type['id']); ?>"><?php echo esc_html($type['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                
                <button type="button" id="es-clear-filters" class="button" style="display:none;">
                    <span class="dashicons dashicons-dismiss"></span>
                    <?php _e('Clear', 'ensemble'); ?>
                </button>
            </div>
        </div>
        
        <!-- Artists List/Grid -->
        <div id="es-artists-container" class="es-items-container">
            <div class="es-loading"><?php printf(__('Loading %s...', 'ensemble'), strtolower($artist_plural)); ?></div>
        </div>
        
    </div>
    
    <!-- Artist Modal -->
    <div id="es-artist-modal" class="es-modal" style="display: none;">
        <div class="es-modal-content es-modal-large es-modal-scrollable">
            <span class="es-modal-close">&times;</span>
            
            <div class="es-modal-header">
                <h2 id="es-modal-title"><?php printf(__('Add New %s', 'ensemble'), $artist_singular); ?></h2>
                <div class="es-modal-header-actions">
                    <button type="button" id="es-copy-artist-btn" class="button" style="display: none;" title="<?php printf(esc_attr__('Copy %s', 'ensemble'), $artist_singular); ?>">
                        <span class="dashicons dashicons-admin-page"></span>
                        <?php _e('Copy', 'ensemble'); ?>
                    </button>
                </div>
            </div>
            
            <form id="es-artist-form" class="es-manager-form es-artist-form-redesign">
                
                <input type="hidden" id="es-artist-id" name="artist_id" value="">
                
                <div class="es-form-sections">
                    
                    <!-- ============================================
                         SECTION 1: Basic Information
                         ============================================ -->
                    <div class="es-form-card">
                        <div class="es-form-card-header">
                            <div class="es-form-card-icon">
                                <span class="dashicons dashicons-admin-users"></span>
                            </div>
                            <div class="es-form-card-title">
                                <h3><?php _e('Basic Information', 'ensemble'); ?></h3>
                                <p class="es-form-card-desc"><?php _e('Name, genres, type and description', 'ensemble'); ?></p>
                            </div>
                        </div>
                        
                        <div class="es-form-card-body">
                            <div class="es-form-row">
                                <label for="es-artist-name"><?php printf(__('%s Name', 'ensemble'), $artist_singular); ?> *</label>
                                <input type="text" id="es-artist-name" name="name" required placeholder="<?php printf(__('e.g. %s Name...', 'ensemble'), $artist_singular); ?>">
                            </div>
                            
                            <?php if (!empty($genres)): ?>
                            <div class="es-form-row">
                                <label><?php _e('Genres', 'ensemble'); ?></label>
                                <div class="es-pill-group" id="es-artist-genre-pills">
                                    <?php foreach ($genres as $genre): ?>
                                        <label class="es-pill">
                                            <input type="checkbox" name="genre[]" value="<?php echo esc_attr($genre['id']); ?>">
                                            <span><?php echo esc_html($genre['name']); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <a href="<?php echo admin_url('admin.php?page=ensemble-taxonomies&tab=genres'); ?>" 
                                   class="es-pill-add" target="_blank">
                                    <span class="dashicons dashicons-plus-alt2"></span>
                                    <?php _e('Manage Genres', 'ensemble'); ?>
                                </a>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($artist_types)): ?>
                            <div class="es-form-row">
                                <label><?php printf(__('%s Type', 'ensemble'), $artist_singular); ?></label>
                                <div class="es-pill-group" id="es-artist-type-pills">
                                    <?php foreach ($artist_types as $type): ?>
                                        <label class="es-pill">
                                            <input type="checkbox" name="artist_type[]" value="<?php echo esc_attr($type['id']); ?>">
                                            <span><?php echo esc_html($type['name']); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <a href="<?php echo admin_url('admin.php?page=ensemble-taxonomies&tab=artist-types'); ?>" 
                                   class="es-pill-add" target="_blank">
                                    <span class="dashicons dashicons-plus-alt2"></span>
                                    <?php _e('Manage Types', 'ensemble'); ?>
                                </a>
                            </div>
                            <?php endif; ?>
                            
                            <div class="es-form-row">
                                <label for="es-artist-description"><?php _e('Description', 'ensemble'); ?></label>
                                <div class="es-wysiwyg-wrap">
                                    <?php 
                                    wp_editor('', 'es-artist-description', array(
                                        'textarea_name' => 'description',
                                        'textarea_rows' => 6,
                                        'media_buttons' => false,
                                        'teeny'         => true,
                                        'quicktags'     => array('buttons' => 'strong,em,link,ul,ol,li'),
                                        'tinymce'       => array(
                                            'toolbar1'  => 'bold,italic,underline,strikethrough,|,bullist,numlist,|,link,unlink,|,removeformat',
                                            'toolbar2'  => '',
                                            'statusbar' => false,
                                            'resize'    => false,
                                        ),
                                    ));
                                    ?>
                                </div>
                            </div>
                            
                            <!-- Professional Info (NEW) -->
                            <div class="es-form-row-inline">
                                <div class="es-form-row es-form-row-flex-1">
                                    <label for="es-artist-position"><?php _e('Position / Title', 'ensemble'); ?></label>
                                    <input type="text" id="es-artist-position" name="position" placeholder="<?php _e('e.g. CEO, Professor, Head of...', 'ensemble'); ?>">
                                </div>
                                <div class="es-form-row es-form-row-flex-1">
                                    <label for="es-artist-company"><?php _e('Organization / Company', 'ensemble'); ?></label>
                                    <input type="text" id="es-artist-company" name="company" placeholder="<?php _e('e.g. Anthropic, TU MÃ¼nchen', 'ensemble'); ?>">
                                </div>
                            </div>
                            
                            <div class="es-form-row">
                                <label for="es-artist-additional"><?php _e('Additional Info', 'ensemble'); ?></label>
                                <textarea id="es-artist-additional" name="additional" rows="2" placeholder="<?php _e('e.g. Author of 3 bestsellers, Award winner...', 'ensemble'); ?>"></textarea>
                                <small class="es-field-help"><?php _e('Brief additional information displayed on the profile', 'ensemble'); ?></small>
                            </div>
                            
                            <div class="es-form-row">
                                <label for="es-artist-references"><?php _e('References / Credits', 'ensemble'); ?></label>
                                <input type="text" id="es-artist-references" name="references" placeholder="<?php _e('e.g. Resident DJ @ Club XY, Festival ABC', 'ensemble'); ?>">
                                <small class="es-field-help"><?php _e('Residencies, past gigs, notable venues/events', 'ensemble'); ?></small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- ============================================
                         SECTION 2: Photos & Videos
                         ============================================ -->
                    <div class="es-form-card">
                        <div class="es-form-card-header">
                            <div class="es-form-card-icon">
                                <span class="dashicons dashicons-format-image"></span>
                            </div>
                            <div class="es-form-card-title">
                                <h3><?php _e('Photos & Videos', 'ensemble'); ?></h3>
                                <p class="es-form-card-desc"><?php _e('Images and video links', 'ensemble'); ?></p>
                            </div>
                        </div>
                        
                        <div class="es-form-card-body">
                            <div class="es-form-row-inline es-media-uploads">
                                <div class="es-form-row es-form-row-flex-1">
                                    <label><?php _e('Featured Photo', 'ensemble'); ?></label>
                                    <div id="es-artist-image-container" class="es-image-upload-box">
                                        <button type="button" id="es-upload-artist-image-btn" class="es-upload-btn">
                                            <span class="dashicons dashicons-format-image"></span>
                                            <span><?php _e('Select Photo', 'ensemble'); ?></span>
                                        </button>
                                        <div id="es-artist-image-preview"></div>
                                        <input type="hidden" id="es-artist-image-id" name="featured_image_id" value="">
                                    </div>
                                </div>
                                
                                <div class="es-form-row es-form-row-flex-1">
                                    <label><?php _e('Gallery', 'ensemble'); ?></label>
                                    <div id="es-artist-gallery-container" class="es-gallery-upload-box">
                                        <button type="button" id="es-add-gallery-image-btn" class="es-upload-btn">
                                            <span class="dashicons dashicons-images-alt2"></span>
                                            <span><?php _e('Add Gallery Images', 'ensemble'); ?></span>
                                        </button>
                                        <div id="es-artist-gallery-preview"></div>
                                        <input type="hidden" id="es-artist-gallery-ids" name="gallery_ids" value="">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="es-form-subsection">
                                <h4><?php _e('Video Links', 'ensemble'); ?></h4>
                                
                                <div class="es-form-row-inline">
                                    <div class="es-form-row">
                                        <label for="es-artist-youtube">
                                            <span class="dashicons dashicons-youtube" style="color: #FF0000;"></span>
                                            <?php _e('YouTube', 'ensemble'); ?>
                                        </label>
                                        <input type="url" id="es-artist-youtube" name="youtube" placeholder="https://youtube.com/watch?v=...">
                                    </div>
                                    
                                    <div class="es-form-row">
                                        <label for="es-artist-vimeo">
                                            <span class="dashicons dashicons-vimeo" style="color: #1AB7EA;"></span>
                                            <?php _e('Vimeo', 'ensemble'); ?>
                                        </label>
                                        <input type="url" id="es-artist-vimeo" name="vimeo" placeholder="https://vimeo.com/...">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- ============================================
                         SECTION 3: Hero Video (Collapsible)
                         ============================================ -->
                    <div class="es-form-card es-form-card-collapsible">
                        <div class="es-form-card-header es-form-card-header-toggle" id="es-hero-video-header">
                            <div class="es-form-card-icon">
                                <span class="dashicons dashicons-video-alt3"></span>
                            </div>
                            <div class="es-form-card-title">
                                <h3><?php _e('Hero Video', 'ensemble'); ?></h3>
                                <p class="es-form-card-desc"><?php _e('Optional background video for Featured Layout', 'ensemble'); ?></p>
                            </div>
                            <label class="es-toggle es-toggle--header">
                                <input type="checkbox" id="es-artist-has-hero-video" name="has_hero_video" value="1">
                                <span class="es-toggle-track"></span>
                            </label>
                        </div>
                        
                        <div class="es-form-card-body es-collapsible-content" id="es-hero-video-container" style="display: none;">
                            <div class="es-form-row">
                                <label for="es-artist-hero-video-url"><?php _e('Video URL', 'ensemble'); ?></label>
                                <input type="url" 
                                       id="es-artist-hero-video-url" 
                                       name="hero_video_url" 
                                       placeholder="<?php _e('https://youtube.com/watch?v=... or https://vimeo.com/...', 'ensemble'); ?>">
                                <small class="es-field-help"><?php _e('YouTube, Vimeo or direct MP4 URL. Falls back to Featured Image if empty.', 'ensemble'); ?></small>
                            </div>
                            
                            <div class="es-form-row">
                                <label><?php _e('Video Options', 'ensemble'); ?></label>
                                <div class="es-video-options">
                                    <label class="es-checkbox-inline">
                                        <input type="checkbox" id="es-artist-hero-video-autoplay" name="hero_video_autoplay" value="1" checked>
                                        <span><?php _e('Autoplay (muted)', 'ensemble'); ?></span>
                                    </label>
                                    <label class="es-checkbox-inline">
                                        <input type="checkbox" id="es-artist-hero-video-loop" name="hero_video_loop" value="1" checked>
                                        <span><?php _e('Loop', 'ensemble'); ?></span>
                                    </label>
                                    <label class="es-checkbox-inline">
                                        <input type="checkbox" id="es-artist-hero-video-controls" name="hero_video_controls" value="1">
                                        <span><?php _e('Show controls', 'ensemble'); ?></span>
                                    </label>
                                </div>
                            </div>
                            
                            <div id="es-artist-hero-video-preview" class="es-video-preview" style="display: none;">
                                <div class="es-video-preview-container">
                                    <iframe id="es-artist-video-preview-frame"></iframe>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- ============================================
                         SECTION 4: Contact & Social Media
                         ============================================ -->
                    <div class="es-form-card">
                        <div class="es-form-card-header">
                            <div class="es-form-card-icon">
                                <span class="dashicons dashicons-share"></span>
                            </div>
                            <div class="es-form-card-title">
                                <h3><?php _e('Contact & Social Media', 'ensemble'); ?></h3>
                                <p class="es-form-card-desc"><?php _e('Email, website and social media profiles', 'ensemble'); ?></p>
                            </div>
                        </div>
                        
                        <div class="es-form-card-body">
                            <div class="es-form-row-inline">
                                <div class="es-form-row">
                                    <label for="es-artist-email">
                                        <span class="dashicons dashicons-email"></span>
                                        <?php _e('E-Mail', 'ensemble'); ?>
                                    </label>
                                    <input type="email" id="es-artist-email" name="email" placeholder="contact@example.com">
                                </div>
                                
                                <div class="es-form-row">
                                    <label for="es-artist-website">
                                        <span class="dashicons dashicons-admin-site-alt3"></span>
                                        <?php _e('Website', 'ensemble'); ?>
                                    </label>
                                    <input type="url" id="es-artist-website" name="website" placeholder="https://www.example.com">
                                </div>
                            </div>
                            
                            <div class="es-form-row">
                                <label><?php _e('Social Media Links', 'ensemble'); ?></label>
                                <div id="es-artist-social-links" class="es-social-links-container">
                                    <!-- Social links will be added here dynamically -->
                                </div>
                                <button type="button" id="es-add-social-link-btn" class="button">
                                    <span class="dashicons dashicons-plus-alt2"></span>
                                    <?php _e('Add Social Link', 'ensemble'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- ============================================
                         SECTION 5: Display Settings
                         ============================================ -->
                    <div class="es-form-card">
                        <div class="es-form-card-header">
                            <div class="es-form-card-icon">
                                <span class="dashicons dashicons-welcome-widgets-menus"></span>
                            </div>
                            <div class="es-form-card-title">
                                <h3><?php _e('Display Settings', 'ensemble'); ?></h3>
                                <p class="es-form-card-desc"><?php _e('Layout options for single page', 'ensemble'); ?></p>
                            </div>
                        </div>
                        
                        <div class="es-form-card-body">
                            <div class="es-form-row">
                                <label for="es-artist-single-layout"><?php _e('Single Page Layout', 'ensemble'); ?></label>
                                <select id="es-artist-single-layout" name="single_layout">
                                    <option value="default"><?php _e('Standard (from Layout Set)', 'ensemble'); ?></option>
                                    <option value="featured"><?php _e('Featured (Fullscreen Hero)', 'ensemble'); ?></option>
                                </select>
                                <small class="es-field-help"><?php _e('Featured layout automatically shows an artist slider when multiple artists exist.', 'ensemble'); ?></small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- ============================================
                         SECTION 6: Statistics (nur bei Edit)
                         ============================================ -->
                    <div class="es-form-card es-form-card-stats" id="es-artist-stats" style="display: none;">
                        <div class="es-form-card-header">
                            <div class="es-form-card-icon">
                                <span class="dashicons dashicons-chart-bar"></span>
                            </div>
                            <div class="es-form-card-title">
                                <h3><?php _e('Statistics', 'ensemble'); ?></h3>
                            </div>
                        </div>
                        
                        <div class="es-form-card-body">
                            <div class="es-stats-grid">
                                <div class="es-stat-item">
                                    <span class="es-stat-value" id="es-artist-event-count">0</span>
                                    <span class="es-stat-label"><?php _e('Events', 'ensemble'); ?></span>
                                </div>
                                <div class="es-stat-item">
                                    <span class="es-stat-value" id="es-artist-created">-</span>
                                    <span class="es-stat-label"><?php _e('Created', 'ensemble'); ?></span>
                                </div>
                                <div class="es-stat-item">
                                    <span class="es-stat-value" id="es-artist-modified">-</span>
                                    <span class="es-stat-label"><?php _e('Modified', 'ensemble'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                </div>
                
                <!-- Form Actions (sticky footer) -->
                <div class="es-form-actions es-form-actions-sticky">
                    <div class="es-form-actions-left">
                        <button type="button" id="es-delete-artist-btn" class="button button-link-delete" style="display: none;">
                            <span class="dashicons dashicons-trash"></span>
                            <?php _e('Delete', 'ensemble'); ?>
                        </button>
                    </div>
                    <div class="es-form-actions-right">
                        <button type="button" class="es-modal-close-btn button button-large">
                            <?php _e('Cancel', 'ensemble'); ?>
                        </button>
                        <button type="submit" class="button button-primary button-large">
                            <span class="dashicons dashicons-saved"></span>
                            <?php printf(__('Save %s', 'ensemble'), $artist_singular); ?>
                        </button>
                    </div>
                </div>
                
            </form>
            
        </div>
    </div>
    
    <!-- Success/Error Messages -->
    <div id="es-message" class="es-message" style="display: none;"></div>
    
    <!-- Bulk Quick Add Modal -->
    <div id="es-bulk-quick-add-modal" class="es-modal" style="display: none;">
        <div class="es-modal-content es-modal-xlarge">
            <span class="es-modal-close">&times;</span>
            
            <h2><?php ES_Icons::icon('lightning'); ?> <?php printf(__('Quick Add Multiple %s', 'ensemble'), $artist_plural); ?></h2>
            <p class="es-modal-subtitle"><?php _e('Add multiple artists at once. Fill in the rows and click "Create All".', 'ensemble'); ?></p>
            
            <div class="es-bulk-add-table-wrapper">
                <table class="es-bulk-add-table" id="es-bulk-add-table">
                    <thead>
                        <tr>
                            <th style="width: 30px;">#</th>
                            <th style="width: 200px;"><?php _e('Name', 'ensemble'); ?> *</th>
                            <th style="width: 250px;"><?php _e('Reference / Quote', 'ensemble'); ?></th>
                            <th style="width: 250px;"><?php _e('Social Media URL', 'ensemble'); ?></th>
                            <th style="width: 150px;"><?php _e('Image', 'ensemble'); ?></th>
                            <th style="width: 50px;"></th>
                        </tr>
                    </thead>
                    <tbody id="es-bulk-add-rows">
                        <!-- Rows werden per JS hinzugefÃ¼gt -->
                    </tbody>
                </table>
            </div>
            
            <div class="es-bulk-add-actions">
                <button type="button" id="es-bulk-add-row-btn" class="button">
                    <?php ES_Icons::icon('plus'); ?>
                    <?php _e('Add Row', 'ensemble'); ?>
                </button>
                <button type="button" id="es-bulk-add-5-rows-btn" class="button">
                    <?php _e('Add 5 Rows', 'ensemble'); ?>
                </button>
            </div>
            
            <div class="es-form-actions" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd;">
                <button type="button" id="es-bulk-create-btn" class="button button-primary button-large">
                    <?php ES_Icons::icon('check'); ?>
                    <?php printf(__('Create All %s', 'ensemble'), $artist_plural); ?>
                </button>
                <button type="button" class="es-modal-close-btn button button-large">
                    <?php _e('Cancel', 'ensemble'); ?>
                </button>
                <span id="es-bulk-progress" style="display: none; margin-left: 15px;">
                    <span class="spinner is-active" style="float: none; margin: 0 5px 0 0;"></span>
                    <span id="es-bulk-progress-text"><?php _e('Creating...', 'ensemble'); ?></span>
                </span>
            </div>
            
        </div>
    </div>
    
    <style>
    /* Bulk Quick Add Modal Styles */
    .es-modal-xlarge {
        max-width: 1100px !important;
        width: 95% !important;
    }
    
    .es-modal-subtitle {
        color: #666;
        margin: -10px 0 20px;
        font-size: 14px;
    }
    
    .es-bulk-add-table-wrapper {
        max-height: 400px;
        overflow-y: auto;
        border: 1px solid #ddd;
        border-radius: 4px;
        margin-bottom: 15px;
    }
    
    .es-bulk-add-table {
        width: 100%;
        border-collapse: collapse;
        background: #fff;
    }
    
    .es-bulk-add-table th {
        background: #f5f5f5;
        padding: 12px 10px;
        text-align: left;
        font-weight: 600;
        font-size: 13px;
        border-bottom: 2px solid #ddd;
        position: sticky;
        top: 0;
        z-index: 10;
    }
    
    .es-bulk-add-table td {
        padding: 8px 10px;
        border-bottom: 1px solid #eee;
        vertical-align: middle;
    }
    
    .es-bulk-add-table tr:hover {
        background: #f9f9f9;
    }
    
    .es-bulk-add-table tr.es-row-success {
        background: #d4edda !important;
    }
    
    .es-bulk-add-table tr.es-row-error {
        background: #f8d7da !important;
    }
    
    .es-bulk-add-table input[type="text"] {
        width: 100%;
        padding: 8px 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 13px;
    }
    
    .es-bulk-add-table input[type="text"]:focus {
        border-color: #2271b1;
        box-shadow: 0 0 0 1px #2271b1;
        outline: none;
    }
    
    .es-bulk-add-table .es-row-number {
        color: #999;
        font-weight: 600;
        text-align: center;
    }
    
    .es-bulk-image-cell {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .es-bulk-image-preview {
        width: 40px;
        height: 40px;
        border-radius: 4px;
        object-fit: cover;
        background: #f0f0f0;
        display: none;
    }
    
    .es-bulk-image-preview.has-image {
        display: block;
    }
    
    .es-bulk-upload-btn {
        padding: 6px 12px !important;
        font-size: 12px !important;
    }
    
    .es-bulk-remove-row {
        background: none;
        border: none;
        color: #dc3232;
        cursor: pointer;
        padding: 5px;
        opacity: 0.5;
        transition: opacity 0.2s;
    }
    
    .es-bulk-remove-row:hover {
        opacity: 1;
    }
    
    .es-bulk-add-actions {
        display: flex;
        gap: 10px;
    }
    
    #es-bulk-quick-add-btn {
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    
    #es-bulk-quick-add-btn .es-icon {
        width: 16px;
        height: 16px;
    }
    
    /* Modal Header with Actions */
    #es-artist-modal .es-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }
    
    #es-artist-modal .es-modal-header-actions {
        display: flex;
        gap: 8px;
        align-items: center;
    }
    
    #es-artist-modal .es-modal-header-actions .button {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 6px 12px;
    }
    
    #es-artist-modal .es-modal-header-actions .dashicons {
        font-size: 16px;
        width: 16px;
        height: 16px;
    }
    
    /* Spin Animation for Copy Button */
    .es-spin {
        animation: es-spin 1s linear infinite;
    }
    
    @keyframes es-spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        
        // =========================================
        // BULK QUICK ADD FUNCTIONALITY
        // =========================================
        
        var bulkRowCount = 0;
        var mediaFrame = null;
        var currentImageCell = null;
        
        // Open Bulk Quick Add Modal
        $('#es-bulk-quick-add-btn').on('click', function() {
            $('#es-bulk-quick-add-modal').fadeIn(200);
            // Add initial rows if empty
            if ($('#es-bulk-add-rows tr').length === 0) {
                addBulkRows(5);
            }
        });
        
        // Close modal
        $('#es-bulk-quick-add-modal .es-modal-close, #es-bulk-quick-add-modal .es-modal-close-btn').on('click', function() {
            $('#es-bulk-quick-add-modal').fadeOut(200);
        });
        
        // Add single row
        $('#es-bulk-add-row-btn').on('click', function() {
            addBulkRows(1);
        });
        
        // Add 5 rows
        $('#es-bulk-add-5-rows-btn').on('click', function() {
            addBulkRows(5);
        });
        
        // Add rows function
        function addBulkRows(count) {
            for (var i = 0; i < count; i++) {
                bulkRowCount++;
                var row = `
                    <tr data-row="${bulkRowCount}">
                        <td class="es-row-number">${bulkRowCount}</td>
                        <td><input type="text" name="name_${bulkRowCount}" class="es-bulk-name" placeholder=\"<?php printf(__('%s name', 'ensemble'), $artist_singular); ?>\"></td>
                        <td><input type="text" name="reference_${bulkRowCount}" class="es-bulk-reference" placeholder="<?php _e('Quote or reference', 'ensemble'); ?>"></td>
                        <td><input type="text" name="social_${bulkRowCount}" class="es-bulk-social" placeholder="https://instagram.com/..."></td>
                        <td>
                            <div class="es-bulk-image-cell">
                                <img src="" class="es-bulk-image-preview" alt="">
                                <input type="hidden" name="image_${bulkRowCount}" class="es-bulk-image-id">
                                <button type="button" class="button es-bulk-upload-btn"><?php _e('Upload', 'ensemble'); ?></button>
                            </div>
                        </td>
                        <td>
                            <button type="button" class="es-bulk-remove-row" title="<?php _e('Remove row', 'ensemble'); ?>">
                                <span class="dashicons dashicons-no-alt"></span>
                            </button>
                        </td>
                    </tr>
                `;
                $('#es-bulk-add-rows').append(row);
            }
            
            // Scroll to bottom
            var wrapper = $('.es-bulk-add-table-wrapper');
            wrapper.scrollTop(wrapper[0].scrollHeight);
        }
        
        // Remove row
        $(document).on('click', '.es-bulk-remove-row', function() {
            $(this).closest('tr').fadeOut(200, function() {
                $(this).remove();
                renumberRows();
            });
        });
        
        // Renumber rows
        function renumberRows() {
            $('#es-bulk-add-rows tr').each(function(index) {
                $(this).find('.es-row-number').text(index + 1);
            });
        }
        
        // Image Upload
        $(document).on('click', '.es-bulk-upload-btn', function(e) {
            e.preventDefault();
            currentImageCell = $(this).closest('.es-bulk-image-cell');
            
            if (mediaFrame) {
                mediaFrame.open();
                return;
            }
            
            mediaFrame = wp.media({
                title: '<?php printf(__('Select %s Image', 'ensemble'), $artist_singular); ?>',
                button: { text: '<?php _e('Use Image', 'ensemble'); ?>' },
                multiple: false,
                library: { type: 'image' }
            });
            
            mediaFrame.on('select', function() {
                var attachment = mediaFrame.state().get('selection').first().toJSON();
                var thumbUrl = attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
                
                currentImageCell.find('.es-bulk-image-id').val(attachment.id);
                currentImageCell.find('.es-bulk-image-preview').attr('src', thumbUrl).addClass('has-image');
                currentImageCell.find('.es-bulk-upload-btn').text('<?php _e('Change', 'ensemble'); ?>');
            });
            
            mediaFrame.open();
        });
        
        // Create All Artists
        $('#es-bulk-create-btn').on('click', function() {
            var rows = [];
            var hasError = false;
            
            // Collect data from all rows with names
            $('#es-bulk-add-rows tr').each(function() {
                var $row = $(this);
                var name = $row.find('.es-bulk-name').val().trim();
                
                if (name) {
                    rows.push({
                        row: $row,
                        name: name,
                        reference: $row.find('.es-bulk-reference').val().trim(),
                        social: $row.find('.es-bulk-social').val().trim(),
                        image_id: $row.find('.es-bulk-image-id').val()
                    });
                }
            });
            
            if (rows.length === 0) {
                alert('<?php _e('Please enter at least one artist name.', 'ensemble'); ?>');
                return;
            }
            
            // Show progress
            $('#es-bulk-progress').show();
            $('#es-bulk-create-btn').prop('disabled', true);
            
            var completed = 0;
            var errors = 0;
            
            // Process rows sequentially
            function processNextRow() {
                if (completed >= rows.length) {
                    // All done
                    $('#es-bulk-progress').hide();
                    $('#es-bulk-create-btn').prop('disabled', false);
                    
                    var message = '<?php _e('Created', 'ensemble'); ?> ' + (rows.length - errors) + ' <?php _e('artists', 'ensemble'); ?>';
                    if (errors > 0) {
                        message += ' (' + errors + ' <?php _e('errors', 'ensemble'); ?>)';
                    }
                    
                    alert(message);
                    
                    // Refresh artist list
                    if (typeof loadArtists === 'function') {
                        loadArtists();
                    } else {
                        location.reload();
                    }
                    
                    // Close modal
                    $('#es-bulk-quick-add-modal').fadeOut(200);
                    
                    // Clear rows
                    $('#es-bulk-add-rows').empty();
                    bulkRowCount = 0;
                    
                    return;
                }
                
                var rowData = rows[completed];
                $('#es-bulk-progress-text').text('<?php _e('Creating', 'ensemble'); ?> ' + (completed + 1) + '/' + rows.length + ': ' + rowData.name);
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'es_bulk_quick_add_artist',
                        nonce: '<?php echo wp_create_nonce('ensemble-wizard'); ?>',
                        name: rowData.name,
                        reference: rowData.reference,
                        social: rowData.social,
                        image_id: rowData.image_id
                    },
                    success: function(response) {
                        if (response.success) {
                            rowData.row.addClass('es-row-success');
                        } else {
                            rowData.row.addClass('es-row-error');
                            errors++;
                        }
                    },
                    error: function() {
                        rowData.row.addClass('es-row-error');
                        errors++;
                    },
                    complete: function() {
                        completed++;
                        processNextRow();
                    }
                });
            }
            
            processNextRow();
        });
        
    });
    </script>
    
    <!-- Bulk Action Modal (Assign Genre/Type) -->
    <div id="es-bulk-assign-modal" class="es-modal" style="display: none;">
        <div class="es-modal-content es-modal-small">
            <span class="es-modal-close">&times;</span>
            
            <h2 id="es-bulk-assign-title"><?php _e('Assign to Selected', 'ensemble'); ?></h2>
            
            <div class="es-form-row">
                <label id="es-bulk-assign-label"><?php _e('Select', 'ensemble'); ?></label>
                <select id="es-bulk-assign-value" style="width: 100%;">
                    <!-- Options werden per JS gefÃ¼llt -->
                </select>
            </div>
            
            <div class="es-form-actions" style="margin-top: 20px;">
                <button type="button" id="es-bulk-assign-confirm" class="button button-primary">
                    <?php _e('Apply', 'ensemble'); ?>
                </button>
                <button type="button" class="es-modal-close-btn button">
                    <?php _e('Cancel', 'ensemble'); ?>
                </button>
            </div>
        </div>
    </div>
    
    <style>
    /* Artist Toolbar Styles - Analog zu Event Wizard */
    .es-artist-toolbar {
        background: #1e1e1e;
        border-radius: 8px;
        padding: 12px 16px;
        margin-bottom: 20px;
    }
    
    .es-artist-toolbar .es-toolbar-row {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }
    
    .es-artist-toolbar .es-toolbar-main-row {
        min-height: 40px;
    }
    
    .es-artist-toolbar .es-filter-search {
        position: relative;
        flex: 0 0 250px;
    }
    
    .es-artist-toolbar .es-search-input {
        width: 100%;
        padding: 8px 12px 8px 36px;
        border: 1px solid #3c3c3c;
        border-radius: 6px;
        background: #2c2c2c;
        color: #fff;
        font-size: 13px;
    }
    
    .es-artist-toolbar .es-search-input:focus {
        border-color: #0073aa;
        outline: none;
        box-shadow: 0 0 0 1px #0073aa;
    }
    
    .es-artist-toolbar .es-search-input::placeholder {
        color: #888;
    }
    
    .es-artist-toolbar .es-search-icon {
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        color: #888;
    }
    
    .es-artist-toolbar .es-search-icon .es-icon {
        width: 16px;
        height: 16px;
    }
    
    .es-artist-toolbar .es-filter-toggle-btn {
        background: #2c2c2c;
        border: 1px solid #3c3c3c;
        color: #ccc;
        padding: 6px 10px;
        border-radius: 6px;
        position: relative;
    }
    
    .es-artist-toolbar .es-filter-toggle-btn:hover,
    .es-artist-toolbar .es-filter-toggle-btn.active {
        background: #3c3c3c;
        color: #fff;
        border-color: #0073aa;
    }
    
    .es-artist-toolbar .es-filter-badge {
        position: absolute;
        top: -5px;
        right: -5px;
        background: #0073aa;
        color: #fff;
        font-size: 10px;
        padding: 2px 5px;
        border-radius: 10px;
        min-width: 16px;
        text-align: center;
    }
    
    .es-artist-toolbar .es-toolbar-divider {
        width: 1px;
        height: 24px;
        background: #3c3c3c;
        margin: 0 4px;
    }
    
    .es-artist-toolbar .es-toolbar-spacer {
        flex: 1;
    }
    
    /* Bulk Actions Inline */
    .es-artist-toolbar .es-bulk-actions-inline {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .es-artist-toolbar .es-bulk-actions-inline select {
        background: #2c2c2c;
        border: 1px solid #3c3c3c;
        color: #ccc;
        padding: 6px 10px;
        border-radius: 6px;
        font-size: 12px;
        min-width: 130px;
    }
    
    .es-artist-toolbar .es-bulk-actions-inline select:focus {
        border-color: #0073aa;
        outline: none;
    }
    
    .es-artist-toolbar .es-bulk-actions-inline button {
        background: #2c2c2c;
        border: 1px solid #3c3c3c;
        color: #ccc;
        padding: 6px 8px;
        border-radius: 6px;
    }
    
    .es-artist-toolbar .es-bulk-actions-inline button:hover {
        background: #3c3c3c;
        color: #fff;
    }
    
    .es-artist-toolbar .es-bulk-selected-count {
        color: #0073aa;
        font-size: 12px;
        font-weight: 500;
        white-space: nowrap;
    }
    
    /* View Toggle */
    .es-artist-toolbar .es-view-toggle {
        display: flex;
        background: #2c2c2c;
        border-radius: 6px;
        overflow: hidden;
        border: 1px solid #3c3c3c;
    }
    
    .es-artist-toolbar .es-view-btn {
        background: transparent;
        border: none;
        color: #888;
        padding: 6px 10px;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .es-artist-toolbar .es-view-btn:hover {
        color: #fff;
        background: #3c3c3c;
    }
    
    .es-artist-toolbar .es-view-btn.active {
        background: #0073aa;
        color: #fff;
    }
    
    /* Item Count */
    .es-artist-toolbar .es-item-count {
        color: #888;
        font-size: 12px;
        white-space: nowrap;
        padding: 0 8px;
    }
    
    /* Quick Add & Create Buttons */
    .es-artist-toolbar #es-bulk-quick-add-btn {
        background: #2c2c2c;
        border: 1px solid #3c3c3c;
        color: #ccc;
        padding: 6px 12px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 13px;
    }
    
    .es-artist-toolbar #es-bulk-quick-add-btn:hover {
        background: #3c3c3c;
        color: #fff;
    }
    
    .es-artist-toolbar #es-bulk-quick-add-btn .es-icon {
        width: 14px;
        height: 14px;
    }
    
    .es-artist-toolbar #es-create-artist-btn {
        background: #0073aa;
        border: none;
        color: #fff;
        padding: 6px 14px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 13px;
    }
    
    .es-artist-toolbar #es-create-artist-btn:hover {
        background: #005a87;
    }
    
    /* Filter Panel */
    .es-artist-toolbar .es-filter-panel {
        display: none;
        margin-top: 12px;
        padding-top: 12px;
        border-top: 1px solid #3c3c3c;
        flex-wrap: wrap;
        gap: 12px;
        align-items: center;
    }
    
    .es-artist-toolbar .es-filter-panel select {
        background: #2c2c2c;
        border: 1px solid #3c3c3c;
        color: #fff;
        padding: 8px 12px;
        border-radius: 6px;
        font-size: 13px;
        min-width: 160px;
        cursor: pointer;
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23888' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 10px center;
        padding-right: 30px;
    }
    
    .es-artist-toolbar .es-filter-panel select:focus {
        border-color: #0073aa;
        outline: none;
        box-shadow: 0 0 0 1px #0073aa;
    }
    
    .es-artist-toolbar .es-filter-panel button {
        background: #2c2c2c;
        border: 1px solid #3c3c3c;
        color: #dc3232;
        padding: 6px 12px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    
    .es-artist-toolbar .es-filter-panel button:hover {
        background: #dc3232;
        color: #fff;
        border-color: #dc3232;
    }
    
    /* Modal Small */
    .es-modal-small {
        max-width: 400px !important;
    }
    
    /* Bulk Assign Modal explicit styling */
    #es-bulk-assign-modal .es-modal-content {
        background: #fff;
        color: #1e1e1e;
    }
    
    #es-bulk-assign-modal h2 {
        color: #1e1e1e;
        margin-bottom: 20px;
    }
    
    #es-bulk-assign-modal label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #1e1e1e;
    }
    
    #es-bulk-assign-modal select {
        background: #fff;
        color: #1e1e1e;
        border: 1px solid #ddd;
        padding: 8px 12px;
        border-radius: 4px;
    }
    
    /* Bulk Quick Add Modal - ensure text visible */
    #es-bulk-quick-add-modal .es-modal-content {
        background: #fff;
        color: #1e1e1e;
    }
    
    #es-bulk-quick-add-modal h2 {
        color: #1e1e1e;
    }
    
    #es-bulk-quick-add-modal .es-modal-subtitle {
        color: #666;
    }
    
    #es-bulk-quick-add-modal .es-bulk-add-table th {
        color: #1e1e1e;
    }
    
    #es-bulk-quick-add-modal .es-bulk-add-table input {
        background: #fff;
        color: #1e1e1e;
    }
    
    /* Checkbox styling in items */
    .es-item-card {
        position: relative;
    }
    
    .es-item-card .es-item-checkbox {
        position: absolute;
        top: 10px;
        left: 10px;
        width: 20px;
        height: 20px;
        z-index: 5;
        cursor: pointer;
        opacity: 0;
        transition: opacity 0.2s;
    }
    
    .es-item-card:hover .es-item-checkbox,
    .es-item-card .es-item-checkbox:checked {
        opacity: 1;
    }
    
    .es-item-card.es-selected {
        box-shadow: 0 0 0 2px #0073aa;
    }
    
    /* Responsive */
    @media (max-width: 900px) {
        .es-artist-toolbar .es-filter-search {
            flex: 1 1 200px;
        }
        
        .es-artist-toolbar .es-toolbar-spacer {
            display: none;
        }
    }
    
    @media (max-width: 600px) {
        .es-artist-toolbar .es-toolbar-main-row {
            flex-wrap: wrap;
        }
        
        .es-artist-toolbar .es-filter-search {
            flex: 1 1 100%;
            order: 1;
        }
        
        .es-artist-toolbar .es-bulk-actions-inline {
            order: 3;
            flex: 1 1 100%;
            margin-top: 10px;
        }
    }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        
        // =========================================
        // FILTER & BULK ACTIONS
        // =========================================
        
        var currentFilters = {
            search: '',
            genre: '',
            type: ''
        };
        var selectedArtistIds = [];
        var currentBulkAction = '';
        var allArtistsData = [];
        
        // Genres and Types data for modals (already in correct format from get_genres/get_artist_types)
        var genresData = <?php echo json_encode($genres); ?>;
        var typesData = <?php echo json_encode($artist_types); ?>;
        
        // =========================================
        // FILTER TOGGLE
        // =========================================
        
        var filterPanelOpen = false;
        var $filterPanel = null;
        var $filterBtn = null;
        
        // Wait for DOM to be fully ready
        setTimeout(function() {
            $filterPanel = $('#es-filter-panel');
            $filterBtn = $('#es-toggle-filters');
            
            $filterBtn.off('click').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                
                filterPanelOpen = !filterPanelOpen;
                
                if (filterPanelOpen) {
                    $filterPanel.css('display', 'flex');
                    $filterBtn.addClass('active');
                } else {
                    $filterPanel.css('display', 'none');
                    $filterBtn.removeClass('active');
                }
                
                return false;
            });
        }, 100);
        
        // Update filter badge
        function updateFilterBadge() {
            var activeFilters = 0;
            if (currentFilters.genre) activeFilters++;
            if (currentFilters.type) activeFilters++;
            
            var $badge = $('.es-filter-badge');
            if (activeFilters > 0) {
                $badge.text(activeFilters).show();
            } else {
                $badge.hide();
            }
        }
        
        // Show bulk actions bar when items exist
        function updateBulkActionsVisibility() {
            // Always show bulk actions in this layout
            $('#es-bulk-actions').css('visibility', $('.es-item-card').length > 0 ? 'visible' : 'hidden');
        }
        
        // Update selected count
        function updateSelectedCount() {
            selectedArtistIds = [];
            $('.es-item-checkbox:checked').each(function() {
                selectedArtistIds.push($(this).data('id'));
            });
            
            if (selectedArtistIds.length > 0) {
                $('#es-selected-count').text(selectedArtistIds.length + ' <?php _e('selected', 'ensemble'); ?>');
            } else {
                $('#es-selected-count').text('');
            }
        }
        
        // Individual checkbox change
        $(document).on('change', '.es-item-checkbox', function() {
            $(this).closest('.es-item-card').toggleClass('es-selected', $(this).prop('checked'));
            updateSelectedCount();
        });
        
        // Filter by Genre (use document-level binding)
        $(document).on('change', '#es-filter-genre', function() {
            currentFilters.genre = $(this).val();
            applyFilters();
            updateClearFiltersButton();
            updateFilterBadge();
        });
        
        // Filter by Type (use document-level binding)
        $(document).on('change', '#es-filter-type', function() {
            currentFilters.type = $(this).val();
            applyFilters();
            updateClearFiltersButton();
            updateFilterBadge();
        });
        
        // Search (on Enter key)
        var searchTimeout;
        $('#es-artist-search').on('keyup', function(e) {
            clearTimeout(searchTimeout);
            var $input = $(this);
            
            // Immediate search on Enter
            if (e.keyCode === 13) {
                currentFilters.search = $input.val().toLowerCase();
                applyFilters();
                return;
            }
            
            // Debounced search while typing
            searchTimeout = setTimeout(function() {
                currentFilters.search = $input.val().toLowerCase();
                applyFilters();
            }, 300);
        });
        
        // Clear Filters
        $('#es-clear-filters').on('click', function() {
            currentFilters = { search: '', genre: '', type: '' };
            $('#es-filter-genre').val('');
            $('#es-filter-type').val('');
            $('#es-artist-search').val('');
            applyFilters();
            updateClearFiltersButton();
            updateFilterBadge();
        });
        
        function updateClearFiltersButton() {
            if (currentFilters.genre || currentFilters.type || currentFilters.search) {
                $('#es-clear-filters').show();
            } else {
                $('#es-clear-filters').hide();
            }
        }
        
        // Apply filters (client-side filtering)
        function applyFilters() {
            var cards = $('.es-item-card');
            
            cards.each(function() {
                var $card = $(this);
                var show = true;
                
                // Search filter
                if (currentFilters.search) {
                    var name = $card.find('.es-item-title').text().toLowerCase();
                    if (name.indexOf(currentFilters.search) === -1) {
                        show = false;
                    }
                }
                
                // Genre filter - convert both to strings for comparison
                if (show && currentFilters.genre) {
                    var cardGenre = String($card.attr('data-genre-id') || '');
                    var filterGenre = String(currentFilters.genre);
                    if (cardGenre !== filterGenre) {
                        show = false;
                    }
                }
                
                // Type filter - convert both to strings for comparison
                if (show && currentFilters.type) {
                    var cardType = String($card.attr('data-type-id') || '');
                    var filterType = String(currentFilters.type);
                    if (cardType !== filterType) {
                        show = false;
                    }
                }
                
                if (show) {
                    $card.show();
                } else {
                    $card.hide();
                }
            });
            
            // Update count
            var visibleCount = $('.es-item-card:visible').length;
            var totalCount = $('.es-item-card').length;
            if (currentFilters.search || currentFilters.genre || currentFilters.type) {
                $('#es-artist-count').text(visibleCount + ' / ' + totalCount + ' <?php echo esc_js($artist_plural); ?>');
            } else {
                $('#es-artist-count').text(totalCount + ' <?php echo esc_js($artist_plural); ?>');
            }
        }
        
        // Apply Bulk Action
        $('#es-apply-bulk-action').on('click', function() {
            var action = $('#es-bulk-action-select').val();
            
            if (!action) {
                alert('<?php _e('Please select an action.', 'ensemble'); ?>');
                return;
            }
            
            if (selectedArtistIds.length === 0) {
                alert('<?php _e('Please select at least one artist.', 'ensemble'); ?>');
                return;
            }
            
            currentBulkAction = action;
            
            if (action === 'delete') {
                if (confirm('<?php _e('Are you sure you want to delete', 'ensemble'); ?> ' + selectedArtistIds.length + ' <?php echo esc_js($artist_singular); ?>(s)?')) {
                    executeBulkDelete();
                }
            } else if (action === 'assign_genre') {
                openBulkAssignModal('genre', '<?php _e('Assign Genre', 'ensemble'); ?>', '<?php _e('Genre', 'ensemble'); ?>', genresData);
            } else if (action === 'assign_type') {
                openBulkAssignModal('type', '<?php _e('Assign Type', 'ensemble'); ?>', '<?php _e('Type', 'ensemble'); ?>', typesData);
            } else if (action === 'remove_genre') {
                if (confirm('<?php _e('Remove genre from', 'ensemble'); ?> ' + selectedArtistIds.length + ' <?php echo esc_js($artist_singular); ?>(s)?')) {
                    executeBulkRemoveTaxonomy('genre');
                }
            } else if (action === 'remove_type') {
                if (confirm('<?php _e('Remove type from', 'ensemble'); ?> ' + selectedArtistIds.length + ' <?php echo esc_js($artist_singular); ?>(s)?')) {
                    executeBulkRemoveTaxonomy('type');
                }
            }
        });
        
        // Open Bulk Assign Modal
        function openBulkAssignModal(type, title, label, options) {
            $('#es-bulk-assign-title').text(title);
            $('#es-bulk-assign-label').text(label);
            
            var $select = $('#es-bulk-assign-value');
            $select.empty();
            $select.append('<option value=""><?php _e('Select...', 'ensemble'); ?></option>');
            
            options.forEach(function(opt) {
                $select.append('<option value="' + opt.id + '">' + opt.name + '</option>');
            });
            
            $('#es-bulk-assign-modal').data('assign-type', type);
            $('#es-bulk-assign-modal').fadeIn(200);
        }
        
        // Close Bulk Assign Modal
        $('#es-bulk-assign-modal .es-modal-close, #es-bulk-assign-modal .es-modal-close-btn').on('click', function() {
            $('#es-bulk-assign-modal').fadeOut(200);
        });
        
        // Confirm Bulk Assign
        $('#es-bulk-assign-confirm').on('click', function() {
            var termId = $('#es-bulk-assign-value').val();
            var type = $('#es-bulk-assign-modal').data('assign-type');
            
            if (!termId) {
                alert('<?php _e('Please select a value.', 'ensemble'); ?>');
                return;
            }
            
            executeBulkAssignTaxonomy(type, termId);
            $('#es-bulk-assign-modal').fadeOut(200);
        });
        
        // Execute Bulk Delete
        function executeBulkDelete() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'es_bulk_delete_artists',
                    nonce: ensembleAjax.nonce,
                    artist_ids: selectedArtistIds
                },
                beforeSend: function() {
                    $('#es-apply-bulk-action').prop('disabled', true).text('<?php _e('Processing...', 'ensemble'); ?>');
                },
                success: function(response) {
                    if (response.success) {
                        // Remove deleted items from DOM
                        selectedArtistIds.forEach(function(id) {
                            $('.es-item-card[data-artist-id="' + id + '"]').fadeOut(300, function() {
                                $(this).remove();
                                applyFilters();
                                updateBulkActionsVisibility();
                            });
                        });
                        selectedArtistIds = [];
                        updateSelectedCount();
                        alert(response.data.message);
                    } else {
                        alert(response.data.message || '<?php printf(__('Error deleting %s.', 'ensemble'), strtolower($artist_plural)); ?>');
                    }
                },
                error: function() {
                    alert('<?php printf(__('Error deleting %s.', 'ensemble'), strtolower($artist_plural)); ?>');
                },
                complete: function() {
                    $('#es-apply-bulk-action').prop('disabled', false).text('<?php _e('Apply', 'ensemble'); ?>');
                    $('#es-bulk-action-select').val('');
                }
            });
        }
        
        // Execute Bulk Assign Taxonomy
        function executeBulkAssignTaxonomy(type, termId) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'es_bulk_assign_artist_taxonomy',
                    nonce: ensembleAjax.nonce,
                    artist_ids: selectedArtistIds,
                    taxonomy_type: type,
                    term_id: termId
                },
                beforeSend: function() {
                    $('#es-apply-bulk-action').prop('disabled', true).text('<?php _e('Processing...', 'ensemble'); ?>');
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        // Reload to show updated data
                        if (typeof loadArtists === 'function') {
                            loadArtists();
                        } else {
                            location.reload();
                        }
                    } else {
                        alert(response.data.message || '<?php _e('Error assigning taxonomy.', 'ensemble'); ?>');
                    }
                },
                error: function() {
                    alert('<?php _e('Error assigning taxonomy.', 'ensemble'); ?>');
                },
                complete: function() {
                    $('#es-apply-bulk-action').prop('disabled', false).text('<?php _e('Apply', 'ensemble'); ?>');
                    $('#es-bulk-action-select').val('');
                }
            });
        }
        
        // Execute Bulk Remove Taxonomy
        function executeBulkRemoveTaxonomy(type) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'es_bulk_remove_artist_taxonomy',
                    nonce: ensembleAjax.nonce,
                    artist_ids: selectedArtistIds,
                    taxonomy_type: type
                },
                beforeSend: function() {
                    $('#es-apply-bulk-action').prop('disabled', true).text('<?php _e('Processing...', 'ensemble'); ?>');
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        // Reload to show updated data
                        if (typeof loadArtists === 'function') {
                            loadArtists();
                        } else {
                            location.reload();
                        }
                    } else {
                        alert(response.data.message || '<?php _e('Error removing taxonomy.', 'ensemble'); ?>');
                    }
                },
                error: function() {
                    alert('<?php _e('Error removing taxonomy.', 'ensemble'); ?>');
                },
                complete: function() {
                    $('#es-apply-bulk-action').prop('disabled', false).text('<?php _e('Apply', 'ensemble'); ?>');
                    $('#es-bulk-action-select').val('');
                }
            });
        }
        
        // Initial setup after artists are loaded
        $(document).ajaxComplete(function(event, xhr, settings) {
            if (settings.data && settings.data.indexOf('es_get_artists') !== -1) {
                setTimeout(function() {
                    updateBulkActionsVisibility();
                    applyFilters();
                }, 100);
            }
        });
        
        // ==========================================
        // COPY ARTIST
        // ==========================================
        
        $('#es-copy-artist-btn').on('click', function() {
            var artistId = $('#es-artist-id').val();
            if (!artistId) return;
            
            if (!confirm('<?php printf(__('Copy this %s?', 'ensemble'), strtolower($artist_singular)); ?>')) {
                return;
            }
            
            var $btn = $(this);
            var $icon = $btn.find('.dashicons');
            $btn.prop('disabled', true);
            $icon.removeClass('dashicons-admin-page').addClass('dashicons-update-alt es-spin');
            
            $.post(ajaxurl, {
                action: 'es_copy_artist',
                nonce: ensembleAjax.nonce,
                artist_id: artistId
            }, function(response) {
                if (response.success) {
                    // Reload list
                    if (typeof loadArtists === 'function') {
                        loadArtists();
                    } else {
                        location.reload();
                    }
                    // Close modal briefly, will reopen with new ID
                    $('#es-artist-modal').fadeOut(200);
                    setTimeout(function() {
                        // Trigger edit for the new copy
                        $('.es-item-card[data-artist-id="' + response.data.artist_id + '"]').trigger('click');
                    }, 600);
                } else {
                    alert(response.data.message || '<?php _e('Error copying artist', 'ensemble'); ?>');
                }
            }).always(function() {
                $btn.prop('disabled', false);
                $icon.removeClass('dashicons-update-alt es-spin').addClass('dashicons-admin-page');
            });
        });
        
        // Show/Hide Copy Button based on Edit/Create mode
        // Hook into modal open events
        $(document).on('click', '#es-create-artist-btn', function() {
            $('#es-copy-artist-btn').hide();
        });
        
        // When editing (artist loaded into form), show copy button
        $(document).ajaxComplete(function(event, xhr, settings) {
            if (settings.data && settings.data.indexOf('es_get_artist') !== -1 && settings.data.indexOf('es_get_artists') === -1) {
                setTimeout(function() {
                    if ($('#es-artist-id').val()) {
                        $('#es-copy-artist-btn').show();
                    }
                }, 100);
            }
        });
        
    });
    </script>
    
    <!-- Dynamic Labels for Modal -->
    <script>
    (function($) {
        // Store dynamic labels globally for external scripts
        window.esArtistLabels = {
            singular: '<?php echo esc_js($artist_singular); ?>',
            plural: '<?php echo esc_js($artist_plural); ?>',
            addNew: '<?php printf(esc_js(__('Add New %s', 'ensemble')), $artist_singular); ?>',
            edit: '<?php printf(esc_js(__('Edit %s', 'ensemble')), $artist_singular); ?>'
        };
    })(jQuery);
    </script>
    
</div>
