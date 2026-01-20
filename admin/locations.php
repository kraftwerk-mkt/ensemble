<?php
/**
 * Location Manager Template
 * 
 * Redesigned with clear sections similar to Event Wizard
 *
 * @package Ensemble
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$manager = new ES_Location_Manager();
$wizard = new ES_Wizard();
$location_types = $wizard->get_location_types();

// Get dynamic labels
$location_singular = ES_Label_System::get_label('location', false);
$location_plural = ES_Label_System::get_label('location', true);

// Check if Maps Add-on is active
$maps_addon_active = ensemble_is_addon_active('maps');
?>

<style>
/* Modal Header with Actions */
#es-location-modal .es-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
}

#es-location-modal .es-modal-header-actions {
    display: flex;
    gap: 8px;
    align-items: center;
}

#es-location-modal .es-modal-header-actions .button {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 6px 12px;
}

#es-location-modal .es-modal-header-actions .dashicons {
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

<div class="wrap es-manager-wrap es-locations-wrap">
    <h1><?php echo esc_html($location_plural); ?> Manager</h1>
    
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
        
        <!-- Toolbar -->
        <div class="es-manager-toolbar">
            <div class="es-toolbar-left">
                <div class="es-search-box">
                    <input type="text" id="es-location-search" placeholder="<?php printf(__('Search %s...', 'ensemble'), strtolower($location_plural)); ?>">
                    <button id="es-location-search-btn" class="button"><?php _e('Search', 'ensemble'); ?></button>
                </div>
            </div>
            
            <div class="es-toolbar-right">
                <button id="es-bulk-delete-btn" class="button" style="display: none;">
                    <?php _e('Delete Selected', 'ensemble'); ?>
                </button>
                <button id="es-create-location-btn" class="button button-primary">
                    <?php printf(__('Add New %s', 'ensemble'), $location_singular); ?>
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
        
        <!-- Locations List/Grid -->
        <div id="es-locations-container" class="es-items-container">
            <div class="es-loading"><?php printf(__('Loading %s...', 'ensemble'), strtolower($location_plural)); ?></div>
        </div>
        
    </div>
    
    <!-- Location Modal -->
    <div id="es-location-modal" class="es-modal" style="display: none;">
        <div class="es-modal-content es-modal-large es-modal-scrollable">
            <span class="es-modal-close">&times;</span>
            
            <div class="es-modal-header">
                <h2 id="es-modal-title"><?php printf(__('Add New %s', 'ensemble'), $location_singular); ?></h2>
                <div class="es-modal-header-actions">
                    <button type="button" id="es-copy-location-btn" class="button" style="display: none;" title="<?php printf(esc_attr__('Copy %s', 'ensemble'), $location_singular); ?>">
                        <span class="dashicons dashicons-admin-page"></span>
                        <?php _e('Copy', 'ensemble'); ?>
                    </button>
                </div>
            </div>
            
            <form id="es-location-form" class="es-manager-form es-location-form-redesign">
                
                <input type="hidden" id="es-location-id" name="location_id" value="">
                
                <div class="es-form-sections">
                    
                    <!-- ============================================
                         SECTION 1: Basic Information
                         ============================================ -->
                    <div class="es-form-card">
                        <div class="es-form-card-header">
                            <div class="es-form-card-icon">
                                <span class="dashicons dashicons-location"></span>
                            </div>
                            <div class="es-form-card-title">
                                <h3><?php _e('Basic Information', 'ensemble'); ?></h3>
                                <p class="es-form-card-desc"><?php printf(__('Name, description and type of this %s', 'ensemble'), strtolower($location_singular)); ?></p>
                            </div>
                        </div>
                        
                        <div class="es-form-card-body">
                            <div class="es-form-row">
                                <label for="es-location-name"><?php printf(__('%s Name', 'ensemble'), $location_singular); ?> *</label>
                                <input type="text" id="es-location-name" name="name" required placeholder="<?php printf(__('e.g. %s Name...', 'ensemble'), $location_singular); ?>">
                            </div>
                            
                            <div class="es-form-row">
                                <label for="es-location-description"><?php _e('Description', 'ensemble'); ?></label>
                                <div class="es-wysiwyg-wrap">
                                    <?php 
                                    wp_editor('', 'es-location-description', array(
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
                            
                            <?php if (!empty($location_types)): ?>
                            <div class="es-form-row">
                                <label><?php printf(__('%s Type', 'ensemble'), $location_singular); ?></label>
                                <div class="es-pill-group" id="es-location-type-pills">
                                    <?php foreach ($location_types as $type): ?>
                                        <label class="es-pill">
                                            <input type="checkbox" name="location_type[]" value="<?php echo esc_attr($type['id']); ?>">
                                            <span><?php echo esc_html($type['name']); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <a href="<?php echo admin_url('admin.php?page=ensemble-taxonomies&tab=location_types'); ?>" 
                                   class="es-pill-add" target="_blank">
                                    <span class="dashicons dashicons-plus-alt2"></span>
                                    <?php _e('Manage Types', 'ensemble'); ?>
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- ============================================
                         SECTION 2: Multivenue / Rooms
                         ============================================ -->
                    <div class="es-form-card es-form-card-collapsible">
                        <div class="es-form-card-header es-form-card-header-toggle">
                            <div class="es-form-card-icon">
                                <span class="dashicons dashicons-building"></span>
                            </div>
                            <div class="es-form-card-title">
                                <h3><?php _e('Rooms / Stages', 'ensemble'); ?></h3>
                                <p class="es-form-card-desc"><?php _e('Define multiple rooms or stages within this location', 'ensemble'); ?></p>
                            </div>
                            <label class="es-toggle es-toggle--header">
                                <input type="checkbox" id="es-location-multivenue" name="is_multivenue" value="1">
                                <span class="es-toggle-track"></span>
                            </label>
                        </div>
                        
                        <div class="es-form-card-body es-collapsible-content" id="es-venues-section" style="display: none;">
                            <div id="es-venues-list">
                                <!-- Venues werden hier dynamisch hinzugefÃ¼gt -->
                            </div>
                            
                            <button type="button" id="es-add-venue-btn" class="button">
                                <span class="dashicons dashicons-plus-alt2"></span>
                                <?php _e('Add Room / Stage', 'ensemble'); ?>
                            </button>
                        </div>
                    </div>
                    
                    <!-- ============================================
                         SECTION 3: Address & Location
                         ============================================ -->
                    <div class="es-form-card">
                        <div class="es-form-card-header">
                            <div class="es-form-card-icon">
                                <span class="dashicons dashicons-location-alt"></span>
                            </div>
                            <div class="es-form-card-title">
                                <h3><?php _e('Address', 'ensemble'); ?></h3>
                                <p class="es-form-card-desc"><?php _e('Physical address and capacity information', 'ensemble'); ?></p>
                            </div>
                        </div>
                        
                        <div class="es-form-card-body">
                            <div class="es-form-row">
                                <label for="es-location-address"><?php _e('Street Address', 'ensemble'); ?></label>
                                <textarea id="es-location-address" name="address" rows="2" placeholder="<?php _e('Street name and number...', 'ensemble'); ?>"></textarea>
                            </div>
                            
                            <div class="es-form-row-inline">
                                <div class="es-form-row es-form-row-flex-2">
                                    <label for="es-location-city"><?php _e('City', 'ensemble'); ?></label>
                                    <input type="text" id="es-location-city" name="city" placeholder="<?php _e('City name', 'ensemble'); ?>">
                                </div>
                                
                                <div class="es-form-row es-form-row-flex-1">
                                    <label for="es-location-capacity"><?php _e('Capacity', 'ensemble'); ?></label>
                                    <input type="number" id="es-location-capacity" name="capacity" min="0" placeholder="0">
                                </div>
                            </div>
                            
                            <?php if ($maps_addon_active): ?>
                            <!-- Maps Add-on Fields -->
                            <div class="es-form-row">
                                <label for="es-location-zip"><?php _e('ZIP / Postal Code', 'ensemble'); ?></label>
                                <input type="text" id="es-location-zip" name="zip_code" placeholder="12345">
                            </div>
                            
                            <div class="es-form-row">
                                <label for="es-location-additional-info"><?php _e('Additional Address Info', 'ensemble'); ?></label>
                                <input type="text" id="es-location-additional-info" name="additional_info" placeholder="<?php _e('e.g. 2nd Floor, Building B', 'ensemble'); ?>">
                            </div>
                            
                            <div class="es-form-subsection">
                                <h4><?php _e('Map Coordinates', 'ensemble'); ?></h4>
                                <p class="es-form-subsection-desc"><?php _e('For precise map display (optional)', 'ensemble'); ?></p>
                                
                                <div class="es-form-row-inline">
                                    <div class="es-form-row">
                                        <label for="es-location-latitude"><?php _e('Latitude', 'ensemble'); ?></label>
                                        <input type="number" step="any" id="es-location-latitude" name="latitude" placeholder="52.5200">
                                    </div>
                                    <div class="es-form-row">
                                        <label for="es-location-longitude"><?php _e('Longitude', 'ensemble'); ?></label>
                                        <input type="number" step="any" id="es-location-longitude" name="longitude" placeholder="13.4050">
                                    </div>
                                </div>
                                
                                <div class="es-form-row-inline es-form-row-options">
                                    <label class="es-toggle es-toggle--inline-label">
                                        <input type="checkbox" id="es-location-show-map" name="show_map" value="1" checked>
                                        <span class="es-toggle-track"></span>
                                        <span><?php _e('Show map in events', 'ensemble'); ?></span>
                                    </label>
                                    
                                    <div class="es-form-row es-form-row-compact">
                                        <label for="es-location-map-type"><?php _e('Map Display', 'ensemble'); ?></label>
                                        <select id="es-location-map-type" name="map_type">
                                            <option value="embedded"><?php _e('Embedded Map', 'ensemble'); ?></option>
                                            <option value="link"><?php _e('Link only', 'ensemble'); ?></option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="es-form-row" id="es-location-map-link-container" style="display: none;">
                                <a href="#" id="es-location-map-link" target="_blank" class="button">
                                    <span class="dashicons dashicons-location"></span>
                                    <?php _e('View on Google Maps', 'ensemble'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- ============================================
                         SECTION 4: Opening Hours
                         ============================================ -->
                    <div class="es-form-card es-form-card-collapsible">
                        <div class="es-form-card-header es-form-card-header-toggle">
                            <div class="es-form-card-icon">
                                <span class="dashicons dashicons-clock"></span>
                            </div>
                            <div class="es-form-card-title">
                                <h3><?php _e('Opening Hours', 'ensemble'); ?></h3>
                                <p class="es-form-card-desc"><?php _e('Regular opening hours for this location', 'ensemble'); ?></p>
                            </div>
                            <label class="es-toggle es-toggle--header">
                                <input type="checkbox" id="es-location-has-opening-hours" name="has_opening_hours" value="1">
                                <span class="es-toggle-track"></span>
                            </label>
                        </div>
                        
                        <div class="es-form-card-body es-collapsible-content" id="es-opening-hours-container" style="display: none;">
                            <div class="es-opening-hours-grid">
                                <?php
                                $days = array(
                                    'monday'    => __('Monday', 'ensemble'),
                                    'tuesday'   => __('Tuesday', 'ensemble'),
                                    'wednesday' => __('Wednesday', 'ensemble'),
                                    'thursday'  => __('Thursday', 'ensemble'),
                                    'friday'    => __('Friday', 'ensemble'),
                                    'saturday'  => __('Saturday', 'ensemble'),
                                    'sunday'    => __('Sunday', 'ensemble'),
                                );
                                foreach ($days as $day_key => $day_label): ?>
                                <div class="es-opening-hours-row" data-day="<?php echo $day_key; ?>">
                                    <div class="es-opening-hours-day">
                                        <span class="es-day-name"><?php echo $day_label; ?></span>
                                    </div>
                                    <div class="es-opening-hours-times">
                                        <input type="time" 
                                               class="es-time-input es-time-open" 
                                               name="opening_hours[<?php echo $day_key; ?>][open]" 
                                               placeholder="09:00">
                                        <span class="es-time-separator">â€“</span>
                                        <input type="time" 
                                               class="es-time-input es-time-close" 
                                               name="opening_hours[<?php echo $day_key; ?>][close]" 
                                               placeholder="18:00">
                                    </div>
                                    <div class="es-opening-hours-closed">
                                        <label class="es-toggle es-toggle--small">
                                            <input type="checkbox" 
                                                   class="es-closed-toggle" 
                                                   name="opening_hours[<?php echo $day_key; ?>][closed]" 
                                                   value="1">
                                            <span class="es-toggle-track"></span>
                                            <span class="es-toggle-label-text"><?php _e('Closed', 'ensemble'); ?></span>
                                        </label>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="es-opening-hours-actions">
                                <button type="button" class="button button-small" id="es-copy-monday-to-all">
                                    <span class="dashicons dashicons-admin-page"></span>
                                    <?php _e('Copy Monday to all', 'ensemble'); ?>
                                </button>
                                <button type="button" class="button button-small" id="es-copy-monday-to-weekdays">
                                    <span class="dashicons dashicons-calendar-alt"></span>
                                    <?php _e('Weekdays only', 'ensemble'); ?>
                                </button>
                            </div>
                            
                            <div class="es-form-row">
                                <label for="es-opening-hours-note"><?php _e('Note', 'ensemble'); ?></label>
                                <input type="text" 
                                       id="es-opening-hours-note" 
                                       name="opening_hours_note" 
                                       placeholder="<?php esc_attr_e('e.g. By appointment, Seasonal hours, etc.', 'ensemble'); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <!-- ============================================
                         SECTION 5: Media (Photos & Videos)
                         ============================================ -->
                    <div class="es-form-card">
                        <div class="es-form-card-header">
                            <div class="es-form-card-icon">
                                <span class="dashicons dashicons-format-image"></span>
                            </div>
                            <div class="es-form-card-title">
                                <h3><?php _e('Photos & Videos', 'ensemble'); ?></h3>
                                <p class="es-form-card-desc"><?php _e('Images and video links for this location', 'ensemble'); ?></p>
                            </div>
                        </div>
                        
                        <div class="es-form-card-body">
                            <div class="es-form-row-inline es-media-uploads">
                                <div class="es-form-row es-form-row-flex-1">
                                    <label><?php _e('Featured Photo', 'ensemble'); ?></label>
                                    <div id="es-location-image-container" class="es-image-upload-box">
                                        <button type="button" id="es-upload-location-image-btn" class="es-upload-btn">
                                            <span class="dashicons dashicons-format-image"></span>
                                            <span><?php _e('Select Photo', 'ensemble'); ?></span>
                                        </button>
                                        <div id="es-location-image-preview"></div>
                                        <input type="hidden" id="es-location-image-id" name="featured_image_id" value="">
                                    </div>
                                </div>
                                
                                <div class="es-form-row es-form-row-flex-1">
                                    <label><?php _e('Gallery', 'ensemble'); ?></label>
                                    <div id="es-location-gallery-container" class="es-gallery-upload-box">
                                        <button type="button" id="es-add-gallery-image-btn" class="es-upload-btn">
                                            <span class="dashicons dashicons-images-alt2"></span>
                                            <span><?php _e('Add Gallery Images', 'ensemble'); ?></span>
                                        </button>
                                        <div id="es-location-gallery-preview"></div>
                                        <input type="hidden" id="es-location-gallery-ids" name="gallery_ids" value="">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="es-form-subsection">
                                <h4><?php _e('Video Links', 'ensemble'); ?></h4>
                                
                                <div class="es-form-row-inline">
                                    <div class="es-form-row">
                                        <label for="es-location-youtube">
                                            <span class="dashicons dashicons-youtube" style="color: #FF0000;"></span>
                                            <?php _e('YouTube', 'ensemble'); ?>
                                        </label>
                                        <input type="url" id="es-location-youtube" name="youtube" placeholder="https://youtube.com/watch?v=...">
                                    </div>
                                    
                                    <div class="es-form-row">
                                        <label for="es-location-vimeo">
                                            <span class="dashicons dashicons-vimeo" style="color: #1AB7EA;"></span>
                                            <?php _e('Vimeo', 'ensemble'); ?>
                                        </label>
                                        <input type="url" id="es-location-vimeo" name="vimeo" placeholder="https://vimeo.com/...">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- ============================================
                         SECTION 5.5: Downloads (if addon active)
                         ============================================ -->
                    <?php if (class_exists('ES_Addon_Manager') && ES_Addon_Manager::is_addon_active('downloads')): ?>
                    <div class="es-form-card">
                        <div class="es-form-card-header">
                            <div class="es-form-card-icon">
                                <span class="dashicons dashicons-download"></span>
                            </div>
                            <div class="es-form-card-title">
                                <h3><?php _e('Downloads', 'ensemble'); ?></h3>
                                <p class="es-form-card-desc"><?php _e('Floor plans, tech specs, brochures and other materials', 'ensemble'); ?></p>
                            </div>
                        </div>
                        
                        <div class="es-form-card-body">
                            <input type="hidden" id="es-location-downloads-data" name="downloads_data" value="[]">
                            
                            <div id="es-location-downloads-list" class="es-downloads-wizard-list"></div>
                            
                            <div id="es-location-downloads-empty" class="es-downloads-wizard-empty">
                                <p><?php _e('No downloads linked', 'ensemble'); ?></p>
                                <p class="description"><?php _e('Add floor plans, tech specs, brochures or other materials', 'ensemble'); ?></p>
                            </div>
                            
                            <div class="es-downloads-wizard-actions">
                                <button type="button" id="es-location-link-download-btn" class="button">
                                    <span class="dashicons dashicons-admin-links"></span>
                                    <?php _e('Link existing download', 'ensemble'); ?>
                                </button>
                                <a href="<?php echo admin_url('admin.php?page=ensemble-downloads'); ?>" target="_blank" class="button">
                                    <span class="dashicons dashicons-external"></span>
                                    <?php _e('Manage Downloads', 'ensemble'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- ============================================
                         SECTION 6: Contact & Social Media
                         ============================================ -->
                    <div class="es-form-card">
                        <div class="es-form-card-header">
                            <div class="es-form-card-icon">
                                <span class="dashicons dashicons-share"></span>
                            </div>
                            <div class="es-form-card-title">
                                <h3><?php _e('Contact & Social Media', 'ensemble'); ?></h3>
                                <p class="es-form-card-desc"><?php _e('Website and social media profiles', 'ensemble'); ?></p>
                            </div>
                        </div>
                        
                        <div class="es-form-card-body">
                            <div class="es-form-row">
                                <label for="es-location-website">
                                    <span class="dashicons dashicons-admin-site-alt3"></span>
                                    <?php _e('Website', 'ensemble'); ?>
                                </label>
                                <input type="url" id="es-location-website" name="website" placeholder="https://www.example.com">
                            </div>
                            
                            <div class="es-form-row">
                                <label><?php _e('Social Media Links', 'ensemble'); ?></label>
                                <div id="es-location-social-links" class="es-social-links-container">
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
                         SECTION 7: Statistics (nur bei Edit)
                         ============================================ -->
                    <div class="es-form-card es-form-card-stats" id="es-location-stats" style="display: none;">
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
                                    <span class="es-stat-value" id="es-location-event-count">0</span>
                                    <span class="es-stat-label"><?php _e('Events', 'ensemble'); ?></span>
                                </div>
                                <div class="es-stat-item">
                                    <span class="es-stat-value" id="es-location-created">-</span>
                                    <span class="es-stat-label"><?php _e('Created', 'ensemble'); ?></span>
                                </div>
                                <div class="es-stat-item">
                                    <span class="es-stat-value" id="es-location-modified">-</span>
                                    <span class="es-stat-label"><?php _e('Modified', 'ensemble'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php 
                    /**
                     * Hook: ensemble_location_form_cards
                     * 
                     * Allows addons to add form cards to the location edit form
                     * 
                     * @since 2.8.0
                     */
                    do_action('ensemble_location_form_cards');
                    ?>
                    
                </div>
                
                <!-- Form Actions (sticky footer) -->
                <div class="es-form-actions es-form-actions-sticky">
                    <div class="es-form-actions-left">
                        <button type="button" id="es-delete-location-btn" class="button button-link-delete" style="display: none;">
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
                            <?php printf(__('Save %s', 'ensemble'), $location_singular); ?>
                        </button>
                    </div>
                </div>
                
            </form>
            
        </div>
    </div>
    
    <!-- Success/Error Messages -->
    <div id="es-message" class="es-message" style="display: none;"></div>
    
    <!-- Dynamic Labels for Modal -->
    <script>
    (function($) {
        // Store dynamic labels globally for external scripts
        window.esLocationLabels = {
            singular: '<?php echo esc_js($location_singular); ?>',
            plural: '<?php echo esc_js($location_plural); ?>',
            addNew: '<?php printf(esc_js(__('Add New %s', 'ensemble')), $location_singular); ?>',
            edit: '<?php printf(esc_js(__('Edit %s', 'ensemble')), $location_singular); ?>'
        };
        
        // ==========================================
        // COPY LOCATION
        // ==========================================
        
        $(document).ready(function() {
            $('#es-copy-location-btn').on('click', function() {
                var locationId = $('#es-location-id').val();
                if (!locationId) return;
                
                if (!confirm('<?php printf(__('Copy this %s?', 'ensemble'), strtolower($location_singular)); ?>')) {
                    return;
                }
                
                var $btn = $(this);
                var $icon = $btn.find('.dashicons');
                $btn.prop('disabled', true);
                $icon.removeClass('dashicons-admin-page').addClass('dashicons-update-alt es-spin');
                
                $.post(ajaxurl, {
                    action: 'es_copy_location',
                    nonce: ensembleAjax.nonce,
                    location_id: locationId
                }, function(response) {
                    if (response.success) {
                        // Reload list
                        if (typeof loadLocations === 'function') {
                            loadLocations();
                        } else {
                            location.reload();
                        }
                        // Close modal
                        $('#es-location-modal').fadeOut(200);
                        setTimeout(function() {
                            // Trigger edit for the new copy
                            $('.es-item-card[data-location-id="' + response.data.location_id + '"]').trigger('click');
                        }, 600);
                    } else {
                        alert(response.data.message || '<?php _e('Error copying location', 'ensemble'); ?>');
                    }
                }).always(function() {
                    $btn.prop('disabled', false);
                    $icon.removeClass('dashicons-update-alt es-spin').addClass('dashicons-admin-page');
                });
            });
            
            // Show/Hide Copy Button based on Edit/Create mode
            $(document).on('click', '#es-create-location-btn', function() {
                $('#es-copy-location-btn').hide();
            });
            
            // When editing (location loaded into form), show copy button
            $(document).ajaxComplete(function(event, xhr, settings) {
                if (settings.data && settings.data.indexOf('es_get_location') !== -1 && settings.data.indexOf('es_get_locations') === -1) {
                    setTimeout(function() {
                        if ($('#es-location-id').val()) {
                            $('#es-copy-location-btn').show();
                        }
                    }, 100);
                }
            });
        });
    })(jQuery);
    </script>
    
</div>
