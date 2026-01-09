<?php
/**
 * Sponsors Admin Page
 * Following exact pattern from Artists/Locations managers
 *
 * @package Ensemble
 * @subpackage Addons/Sponsors
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get categories
$categories = get_terms(array(
    'taxonomy'   => 'ensemble_sponsor_category',
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

// Get labels
$event_label = ES_Label_System::get_label('event', false);
$event_label_plural = ES_Label_System::get_label('event', true);
?>

<div class="wrap es-manager-wrap es-sponsors-wrap">
    <h1><?php _e('Sponsors', 'ensemble'); ?> Manager</h1>
    
    <div class="es-manager-container">
        
        <!-- Toolbar -->
        <div class="es-manager-toolbar">
            <div class="es-toolbar-left">
                <div class="es-search-box">
                    <input type="text" id="es-sponsor-search" placeholder="<?php _e('Search sponsors...', 'ensemble'); ?>">
                    <button id="es-sponsor-search-btn" class="button"><?php _e('Search', 'ensemble'); ?></button>
                </div>
                
                <?php if (!empty($categories) && !is_wp_error($categories)): ?>
                <select id="es-sponsor-category-filter" class="es-filter-select">
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
                <a href="<?php echo admin_url('edit-tags.php?taxonomy=ensemble_sponsor_category&post_type=ensemble_sponsor'); ?>" 
                   class="button" target="_blank">
                    <?php _e('Manage Categories', 'ensemble'); ?>
                </a>
                <button id="es-create-sponsor-btn" class="button button-primary">
                    <?php _e('Add New Sponsor', 'ensemble'); ?>
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
        
        <!-- Sponsors List/Grid -->
        <div id="es-sponsors-container" class="es-items-container es-grid-view">
            <div class="es-loading"><?php _e('Loading sponsors...', 'ensemble'); ?></div>
        </div>
        
    </div>
    
    <!-- Sponsor Modal -->
    <div id="es-sponsor-modal" class="es-modal" style="display: none;">
        <div class="es-modal-content es-modal-large">
            <span class="es-modal-close">&times;</span>
            
            <h2 id="es-modal-title"><?php _e('Add New Sponsor', 'ensemble'); ?></h2>
            
            <form id="es-sponsor-form" class="es-manager-form">
                
                <input type="hidden" id="es-sponsor-id" name="sponsor_id" value="">
                
                <div class="es-form-grid">
                    
                    <!-- Left Column -->
                    <div class="es-form-column">
                        
                        <div class="es-form-section">
                            <h3><?php _e('Basic Information', 'ensemble'); ?></h3>
                            
                            <div class="es-form-row">
                                <label for="es-sponsor-name"><?php _e('Sponsor Name', 'ensemble'); ?> *</label>
                                <input type="text" id="es-sponsor-name" name="name" required placeholder="<?php _e('e.g. Acme Corporation', 'ensemble'); ?>">
                            </div>
                            
                            <div class="es-form-row">
                                <label for="es-sponsor-website"><?php _e('Website', 'ensemble'); ?></label>
                                <input type="url" id="es-sponsor-website" name="website" placeholder="https://...">
                            </div>
                            
                            <div class="es-form-row">
                                <label for="es-sponsor-description"><?php _e('Description', 'ensemble'); ?></label>
                                <textarea id="es-sponsor-description" name="description" rows="3" placeholder="<?php _e('Optional short description...', 'ensemble'); ?>"></textarea>
                            </div>
                            
                            <?php if (!empty($categories) && !is_wp_error($categories)): ?>
                            <div class="es-form-row">
                                <label><?php _e('Category', 'ensemble'); ?></label>
                                <div class="es-pill-group" id="es-sponsor-category-pills">
                                    <?php foreach ($categories as $cat): ?>
                                        <label class="es-pill">
                                            <input type="checkbox" name="categories[]" value="<?php echo esc_attr($cat->term_id); ?>">
                                            <span><?php echo esc_html($cat->name); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <p class="es-field-help">
                                    <a href="<?php echo admin_url('edit-tags.php?taxonomy=ensemble_sponsor_category&post_type=ensemble_sponsor'); ?>" target="_blank">
                                        <?php _e('+ Manage categories', 'ensemble'); ?>
                                    </a>
                                </p>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="es-form-section">
                            <h3><?php _e('Display Period', 'ensemble'); ?></h3>
                            <p class="es-field-help" style="margin-top: 0;">
                                <?php _e('Optional: Set a date range when this sponsor should be displayed.', 'ensemble'); ?>
                            </p>
                            
                            <div class="es-form-row es-form-row-inline">
                                <div>
                                    <label for="es-sponsor-active-from"><?php _e('Active From', 'ensemble'); ?></label>
                                    <input type="date" id="es-sponsor-active-from" name="active_from">
                                </div>
                                <div>
                                    <label for="es-sponsor-active-until"><?php _e('Active Until', 'ensemble'); ?></label>
                                    <input type="date" id="es-sponsor-active-until" name="active_until">
                                </div>
                            </div>
                        </div>
                        
                    </div>
                    
                    <!-- Right Column -->
                    <div class="es-form-column">
                        
                        <div class="es-form-section">
                            <h3><?php _e('Logo', 'ensemble'); ?></h3>
                            
                            <div class="es-form-row">
                                <div id="es-sponsor-logo-container" class="es-upload-zone">
                                    <div class="es-upload-placeholder" id="es-logo-placeholder">
                                        <span class="dashicons dashicons-format-image"></span>
                                        <p><?php _e('Drop logo here or click to select', 'ensemble'); ?></p>
                                        <small><?php _e('Recommended: PNG with transparent background', 'ensemble'); ?></small>
                                    </div>
                                    <div id="es-sponsor-logo-preview" class="es-upload-preview" style="display: none;">
                                        <img src="" alt="">
                                        <button type="button" class="es-remove-image" title="<?php _e('Remove', 'ensemble'); ?>">&times;</button>
                                    </div>
                                    <input type="hidden" id="es-sponsor-logo-id" name="logo_id" value="">
                                </div>
                            </div>
                        </div>
                        
                        <div class="es-form-section">
                            <h3><?php _e('Assignment', 'ensemble'); ?></h3>
                            
                            <div class="es-form-row">
                                <label class="es-checkbox-block es-checkbox-highlight">
                                    <input type="checkbox" id="es-sponsor-is-main" name="is_main" value="1">
                                    <span>
                                        <strong><?php _e('Main Sponsor', 'ensemble'); ?></strong>
                                        <span class="es-badge es-badge--warning" style="margin-left: 8px;">★</span><br>
                                        <small><?php _e('Display prominently in header/sidebar. Only one sponsor can be main sponsor.', 'ensemble'); ?></small>
                                    </span>
                                </label>
                            </div>
                            
                            <div class="es-form-row" id="es-sponsor-main-caption-row" style="display: none;">
                                <label for="es-sponsor-main-caption"><?php _e('Custom Caption', 'ensemble'); ?></label>
                                <input type="text" id="es-sponsor-main-caption" name="main_caption" placeholder="<?php esc_attr_e('e.g. Presented by, Powered by...', 'ensemble'); ?>">
                                <small class="es-field-help"><?php _e('Optional. Leave empty to use global caption from settings.', 'ensemble'); ?></small>
                            </div>
                            
                            <hr style="margin: 16px 0; border: none; border-top: 1px solid #ddd;">
                            
                            <div class="es-form-row">
                                <label class="es-checkbox-block">
                                    <input type="checkbox" id="es-sponsor-is-global" name="is_global" value="1">
                                    <span>
                                        <strong><?php _e('Global Sponsor', 'ensemble'); ?></strong><br>
                                        <small><?php _e('Display on all events automatically', 'ensemble'); ?></small>
                                    </span>
                                </label>
                            </div>
                            
                            <div class="es-form-row" id="es-sponsor-events-row">
                                <label><?php echo esc_html($event_label_plural); ?></label>
                                <select id="es-sponsor-events" name="events[]" multiple style="width: 100%; min-height: 120px;">
                                    <?php foreach ($events as $event): ?>
                                        <option value="<?php echo esc_attr($event->ID); ?>">
                                            <?php echo esc_html($event->post_title); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="es-field-help"><?php _e('Hold Ctrl/Cmd to select multiple. Leave empty if Global Sponsor is checked.', 'ensemble'); ?></small>
                            </div>
                            
                            <div class="es-form-row">
                                <label for="es-sponsor-order"><?php _e('Display Order', 'ensemble'); ?></label>
                                <input type="number" id="es-sponsor-order" name="menu_order" value="0" min="0" step="1">
                                <small class="es-field-help"><?php _e('Lower numbers appear first', 'ensemble'); ?></small>
                            </div>
                        </div>
                        
                        <div class="es-form-section" id="es-sponsor-shortcode-section" style="display: none;">
                            <h3><?php _e('Shortcode', 'ensemble'); ?></h3>
                            <div class="es-shortcode-box">
                                <code id="es-sponsor-shortcode"></code>
                                <button type="button" class="es-copy-btn" data-target="es-sponsor-shortcode">
                                    <span class="dashicons dashicons-admin-page"></span>
                                </button>
                            </div>
                        </div>
                        
                    </div>
                    
                </div>
                
                <div class="es-form-actions">
                    <button type="submit" class="button button-primary button-large">
                        <?php _e('Save Sponsor', 'ensemble'); ?>
                    </button>
                    <button type="button" class="es-modal-close-btn button button-large">
                        <?php _e('Cancel', 'ensemble'); ?>
                    </button>
                    <button type="button" id="es-delete-sponsor-btn" class="button button-link-delete" style="display: none;">
                        <?php _e('Delete Sponsor', 'ensemble'); ?>
                    </button>
                </div>
                
            </form>
            
        </div>
    </div>
    
</div>
