<?php
/**
 * Downloads Manager Admin Template
 *
 * @package Ensemble
 * @subpackage Addons/Downloads
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get download types
$download_types = isset($download_types) ? $download_types : array();

// Get dynamic labels
$event_label = ES_Label_System::get_label('event', false);
$event_label_plural = ES_Label_System::get_label('event', true);
$artist_label = ES_Label_System::get_label('artist', false);
$artist_label_plural = ES_Label_System::get_label('artist', true);
$location_label = ES_Label_System::get_label('location', false);
$location_label_plural = ES_Label_System::get_label('location', true);
?>

<div class="wrap es-manager-wrap es-downloads-wrap">
    <h1><?php _e('Downloads Manager', 'ensemble'); ?></h1>
    
    <div class="es-manager-container">
        
        <!-- Toolbar -->
        <div class="es-wizard-toolbar es-downloads-toolbar">
            <div class="es-toolbar-row es-toolbar-main-row">
                <div class="es-filter-search">
                    <input type="text" 
                           id="es-download-search" 
                           class="es-search-input" 
                           placeholder="<?php _e('Search downloads...', 'ensemble'); ?>">
                    <span class="es-search-icon"><?php ES_Icons::icon('search'); ?></span>
                </div>
                
                <select id="es-download-type-filter" class="es-filter-select">
                    <option value=""><?php _e('All Types', 'ensemble'); ?></option>
                    <?php foreach ($download_types as $slug => $type): ?>
                    <option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html($type['label']); ?></option>
                    <?php endforeach; ?>
                </select>
                
                <div class="es-toolbar-spacer"></div>
                
                <!-- Stats -->
                <div class="es-toolbar-stats">
                    <span class="es-stat">
                        <span class="es-stat-value" id="es-download-count">0</span>
                        <span class="es-stat-label"><?php _e('Downloads', 'ensemble'); ?></span>
                    </span>
                    <span class="es-stat">
                        <span class="es-stat-value" id="es-total-downloads">0</span>
                        <span class="es-stat-label"><?php _e('Total Downloads', 'ensemble'); ?></span>
                    </span>
                </div>
                
                <button id="es-bulk-upload" class="button">
                    <span class="dashicons dashicons-upload"></span>
                    <?php _e('Bulk Upload', 'ensemble'); ?>
                </button>
                
                <button id="es-bulk-download" class="button" style="display: none;">
                    <span class="dashicons dashicons-download"></span>
                    <?php _e('Download ZIP', 'ensemble'); ?>
                </button>
                
                <button id="es-add-download" class="button button-primary">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <?php _e('Add Download', 'ensemble'); ?>
                </button>
            </div>
        </div>
        
        <!-- Downloads Table -->
        <div class="es-table-container">
            <table class="es-downloads-table widefat">
                <thead>
                    <tr>
                        <th class="column-title"><?php _e('Title', 'ensemble'); ?></th>
                        <th class="column-type"><?php _e('Type', 'ensemble'); ?></th>
                        <th class="column-file"><?php _e('File', 'ensemble'); ?></th>
                        <th class="column-downloads"><?php _e('Downloads', 'ensemble'); ?></th>
                        <th class="column-linked"><?php _e('Linked to', 'ensemble'); ?></th>
                        <th class="column-actions"><?php _e('Actions', 'ensemble'); ?></th>
                    </tr>
                </thead>
                <tbody id="es-downloads-list">
                    <tr class="es-loading">
                        <td colspan="6">
                            <span class="spinner is-active"></span>
                            <?php _e('Loading downloads...', 'ensemble'); ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Empty State -->
        <div id="es-downloads-empty" class="es-empty-state" style="display: none;">
            <div class="es-empty-icon">
                <span class="dashicons dashicons-download"></span>
            </div>
            <h3><?php _e('No downloads yet', 'ensemble'); ?></h3>
            <p><?php _e('Create your first download to share files with your visitors.', 'ensemble'); ?></p>
            <button id="es-add-download-empty" class="button button-primary button-hero">
                <span class="dashicons dashicons-plus-alt2"></span>
                <?php _e('Add Download', 'ensemble'); ?>
            </button>
        </div>
        
    </div>
    
    <!-- Download Modal -->
    <div id="es-download-modal" class="es-modal" style="display: none;">
        <div class="es-modal-overlay"></div>
        <div class="es-modal-content es-modal-large es-modal-scrollable">
            <span class="es-modal-close">&times;</span>
            
            <div class="es-modal-header">
                <h2 id="es-modal-title"><?php _e('Add New Download', 'ensemble'); ?></h2>
            </div>
            
            <form id="es-download-form" class="es-manager-form">
                <input type="hidden" id="es-download-id" name="download_id" value="">
                
                <div class="es-form-sections">
                    
                    <!-- Basic Information -->
                    <div class="es-form-card">
                        <div class="es-form-card-header">
                            <div class="es-form-card-icon">
                                <span class="dashicons dashicons-download"></span>
                            </div>
                            <div class="es-form-card-title">
                                <h3><?php _e('Basic Information', 'ensemble'); ?></h3>
                                <p class="es-form-card-desc"><?php _e('Title, type and description', 'ensemble'); ?></p>
                            </div>
                        </div>
                        
                        <div class="es-form-card-body">
                            <div class="es-form-row">
                                <label for="es-download-title"><?php _e('Title', 'ensemble'); ?> *</label>
                                <input type="text" id="es-download-title" name="title" required 
                                       placeholder="<?php _e('e.g. Conference Presentation', 'ensemble'); ?>">
                            </div>
                            
                            <div class="es-form-row">
                                <label for="es-download-type"><?php _e('Type', 'ensemble'); ?></label>
                                <select id="es-download-type" name="type">
                                    <?php foreach ($download_types as $slug => $type): ?>
                                    <option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html($type['label']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="es-form-row">
                                <label for="es-download-description"><?php _e('Description', 'ensemble'); ?></label>
                                <textarea id="es-download-description" name="description" rows="3" 
                                          placeholder="<?php _e('Brief description of the download...', 'ensemble'); ?>"></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- File Upload -->
                    <div class="es-form-card">
                        <div class="es-form-card-header">
                            <div class="es-form-card-icon">
                                <span class="dashicons dashicons-media-default"></span>
                            </div>
                            <div class="es-form-card-title">
                                <h3><?php _e('File', 'ensemble'); ?></h3>
                                <p class="es-form-card-desc"><?php _e('Select the file to offer for download', 'ensemble'); ?></p>
                            </div>
                        </div>
                        
                        <div class="es-form-card-body">
                            <div class="es-file-upload">
                                <input type="hidden" id="es-download-file-id" name="file_id" value="">
                                <div id="es-file-preview" class="es-file-preview">
                                    <span class="es-file-placeholder"><?php _e('No file selected', 'ensemble'); ?></span>
                                </div>
                                <div class="es-file-actions">
                                    <button type="button" id="es-select-file" class="button">
                                        <span class="dashicons dashicons-upload"></span>
                                        <?php _e('Select File', 'ensemble'); ?>
                                    </button>
                                    <button type="button" id="es-remove-file" class="button es-button-danger" style="display: none;">
                                        <span class="dashicons dashicons-trash"></span>
                                        <?php _e('Remove', 'ensemble'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Linked Content -->
                    <div class="es-form-card">
                        <div class="es-form-card-header">
                            <div class="es-form-card-icon">
                                <span class="dashicons dashicons-admin-links"></span>
                            </div>
                            <div class="es-form-card-title">
                                <h3><?php _e('Link to Content', 'ensemble'); ?></h3>
                                <p class="es-form-card-desc"><?php printf(__('Associate this download with %s, %s or %s', 'ensemble'), strtolower($event_label_plural), strtolower($artist_label_plural), strtolower($location_label_plural)); ?></p>
                            </div>
                        </div>
                        
                        <div class="es-form-card-body">
                            <div class="es-form-row">
                                <label for="es-download-events"><?php echo esc_html($event_label_plural); ?></label>
                                <select id="es-download-events" name="events[]" multiple class="es-select2" 
                                        data-post-type="<?php echo esc_attr($event_post_type); ?>"
                                        data-placeholder="<?php printf(__('Select %s...', 'ensemble'), strtolower($event_label_plural)); ?>">
                                </select>
                            </div>
                            
                            <div class="es-form-row">
                                <label for="es-download-artists"><?php echo esc_html($artist_label_plural); ?></label>
                                <select id="es-download-artists" name="artists[]" multiple class="es-select2"
                                        data-post-type="<?php echo esc_attr($artist_post_type); ?>"
                                        data-placeholder="<?php printf(__('Select %s...', 'ensemble'), strtolower($artist_label_plural)); ?>">
                                </select>
                            </div>
                            
                            <div class="es-form-row">
                                <label for="es-download-locations"><?php echo esc_html($location_label_plural); ?></label>
                                <select id="es-download-locations" name="locations[]" multiple class="es-select2"
                                        data-post-type="<?php echo esc_attr($location_post_type); ?>"
                                        data-placeholder="<?php printf(__('Select %s...', 'ensemble'), strtolower($location_label_plural)); ?>">
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Availability Settings -->
                    <div class="es-form-card">
                        <div class="es-form-card-header">
                            <div class="es-form-card-icon">
                                <span class="dashicons dashicons-clock"></span>
                            </div>
                            <div class="es-form-card-title">
                                <h3><?php _e('Availability', 'ensemble'); ?></h3>
                                <p class="es-form-card-desc"><?php _e('Schedule when this download is available', 'ensemble'); ?></p>
                            </div>
                        </div>
                        
                        <div class="es-form-card-body">
                            <div class="es-form-row es-form-row--half">
                                <div>
                                    <label for="es-download-from"><?php _e('Available From', 'ensemble'); ?></label>
                                    <input type="datetime-local" id="es-download-from" name="available_from">
                                </div>
                                <div>
                                    <label for="es-download-until"><?php _e('Available Until', 'ensemble'); ?></label>
                                    <input type="datetime-local" id="es-download-until" name="available_until">
                                </div>
                            </div>
                            
                            <div class="es-form-row">
                                <label class="es-toggle-label">
                                    <input type="checkbox" id="es-download-login" name="require_login" value="1">
                                    <span class="es-toggle-switch"></span>
                                    <span class="es-toggle-text"><?php _e('Require login to download', 'ensemble'); ?></span>
                                </label>
                            </div>
                            
                            <div class="es-form-row">
                                <label for="es-download-order"><?php _e('Sort Order', 'ensemble'); ?></label>
                                <input type="number" id="es-download-order" name="menu_order" value="0" min="0" 
                                       placeholder="0" style="width: 100px;">
                                <p class="description"><?php _e('Lower numbers appear first', 'ensemble'); ?></p>
                            </div>
                        </div>
                    </div>
                    
                </div>
                
                <div class="es-modal-footer">
                    <button type="button" class="es-modal-close-btn button button-large">
                        <?php _e('Cancel', 'ensemble'); ?>
                    </button>
                    <button type="submit" id="es-save-download" class="button button-primary button-large">
                        <?php _e('Save Download', 'ensemble'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Bulk Upload Modal -->
    <div id="es-bulk-upload-modal" class="es-modal" style="display: none;">
        <div class="es-modal-overlay"></div>
        <div class="es-modal-content es-modal-large es-modal-scrollable">
            <span class="es-modal-close">&times;</span>
            
            <div class="es-modal-header">
                <h2><?php _e('Bulk Upload', 'ensemble'); ?></h2>
            </div>
            
            <div class="es-bulk-upload-content">
                
                <!-- Step 1: Assignment -->
                <div class="es-bulk-step es-bulk-step-assign">
                    <h3><?php _e('1. Assign to (optional)', 'ensemble'); ?></h3>
                    <div class="es-bulk-assign-grid">
                        <div class="es-form-field">
                            <label><?php echo esc_html($event_label_plural); ?></label>
                            <select id="es-bulk-events" class="es-select2-ajax" multiple
                                    data-ajax-action="ensemble_search_posts"
                                    data-post-type="<?php echo esc_attr(ensemble_get_post_type()); ?>"
                                    data-placeholder="<?php printf(__('Select %s...', 'ensemble'), $event_label_plural); ?>">
                            </select>
                        </div>
                        
                        <div class="es-form-field">
                            <label><?php echo esc_html($artist_label_plural); ?></label>
                            <select id="es-bulk-artists" class="es-select2-ajax" multiple
                                    data-ajax-action="ensemble_search_posts"
                                    data-post-type="ensemble_artist"
                                    data-placeholder="<?php printf(__('Select %s...', 'ensemble'), $artist_label_plural); ?>">
                            </select>
                        </div>
                        
                        <div class="es-form-field">
                            <label><?php echo esc_html($location_label_plural); ?></label>
                            <select id="es-bulk-locations" class="es-select2-ajax" multiple
                                    data-ajax-action="ensemble_search_posts"
                                    data-post-type="ensemble_location"
                                    data-placeholder="<?php printf(__('Select %s...', 'ensemble'), $location_label_plural); ?>">
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Step 2: Upload Zone -->
                <div class="es-bulk-step es-bulk-step-upload">
                    <h3><?php _e('2. Select Files', 'ensemble'); ?></h3>
                    <div id="es-bulk-dropzone" class="es-bulk-dropzone">
                        <div class="es-dropzone-content">
                            <span class="dashicons dashicons-upload"></span>
                            <p><?php _e('Drag & drop files here', 'ensemble'); ?></p>
                            <p class="description"><?php _e('or', 'ensemble'); ?></p>
                            <button type="button" id="es-bulk-select-files" class="button">
                                <?php _e('Select Files', 'ensemble'); ?>
                            </button>
                            <input type="file" id="es-bulk-file-input" multiple style="display: none;">
                        </div>
                    </div>
                </div>
                
                <!-- Step 3: File List -->
                <div class="es-bulk-step es-bulk-step-files" style="display: none;">
                    <h3><?php _e('3. Review & Edit', 'ensemble'); ?></h3>
                    <div class="es-bulk-files-header">
                        <span><?php _e('File', 'ensemble'); ?></span>
                        <span><?php _e('Title', 'ensemble'); ?></span>
                        <span><?php _e('Type', 'ensemble'); ?></span>
                        <span></span>
                    </div>
                    <div id="es-bulk-files-list" class="es-bulk-files-list">
                        <!-- Files will be added here dynamically -->
                    </div>
                </div>
                
                <!-- Progress -->
                <div id="es-bulk-progress" class="es-bulk-progress" style="display: none;">
                    <div class="es-progress-bar">
                        <div class="es-progress-fill" style="width: 0%;"></div>
                    </div>
                    <p class="es-progress-text"><?php _e('Uploading...', 'ensemble'); ?> <span id="es-bulk-progress-count">0/0</span></p>
                </div>
                
                <!-- Actions -->
                <div class="es-modal-actions">
                    <button type="button" class="button es-modal-cancel"><?php _e('Cancel', 'ensemble'); ?></button>
                    <button type="button" id="es-bulk-upload-submit" class="button button-primary" disabled>
                        <span class="dashicons dashicons-upload"></span>
                        <?php _e('Upload All', 'ensemble'); ?>
                    </button>
                </div>
                
            </div>
        </div>
    </div>
    
</div>

<!-- Templates -->
<script type="text/html" id="tmpl-es-download-row">
    <tr data-id="{{ data.id }}">
        <td class="column-title">
            <strong>{{ data.title }}</strong>
            <# if (data.description) { #>
            <p class="description">{{ data.description }}</p>
            <# } #>
        </td>
        <td class="column-type">
            <# if (data.type) { #>
            <span class="es-type-badge" style="--type-color: {{ data.type.color }}">
                {{ data.type.label }}
            </span>
            <# } #>
        </td>
        <td class="column-file">
            <div class="es-file-info">
                <span class="dashicons {{ data.file_icon }}"></span>
                <span class="es-file-name">{{ data.file_name }}</span>
                <span class="es-file-size">{{ data.file_size_formatted }}</span>
            </div>
        </td>
        <td class="column-downloads">
            <span class="es-download-count">{{ data.download_count || 0 }}</span>
        </td>
        <td class="column-linked">
            <# if (data.events && data.events.length) { #>
            <span class="es-linked-badge es-linked-events">{{ data.events.length }} <?php echo esc_js($event_label_plural); ?></span>
            <# } #>
            <# if (data.artists && data.artists.length) { #>
            <span class="es-linked-badge es-linked-artists">{{ data.artists.length }} <?php echo esc_js($artist_label_plural); ?></span>
            <# } #>
            <# if (data.locations && data.locations.length) { #>
            <span class="es-linked-badge es-linked-locations">{{ data.locations.length }} <?php echo esc_js($location_label_plural); ?></span>
            <# } #>
        </td>
        <td class="column-actions">
            <button class="button es-edit-download" title="<?php _e('Edit', 'ensemble'); ?>">
                <span class="dashicons dashicons-edit"></span>
            </button>
            <button class="button es-delete-download es-button-danger" data-id="{{ data.id }}" title="<?php _e('Delete', 'ensemble'); ?>">
                <span class="dashicons dashicons-trash"></span>
            </button>
        </td>
    </tr>
</script>

<script type="text/html" id="tmpl-es-downloads-empty">
    <tr class="es-no-items">
        <td colspan="6">
            <div class="es-empty-inline">
                <span class="dashicons dashicons-download"></span>
                <span><?php _e('No downloads found', 'ensemble'); ?></span>
            </div>
        </td>
    </tr>
</script>
