<?php
/**
 * Export Events Template
 *
 * @package Ensemble
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$is_pro = function_exists('ensemble_is_pro') && ensemble_is_pro();
?>

<div class="wrap es-manager-wrap es-export-wrap">
    <h1><?php _e('Export Events', 'ensemble'); ?></h1>
    
    <?php if (!$is_pro): ?>
    <!-- Pro Required Gate -->
    <div class="es-pro-gate" style="background: var(--es-surface, #2c2c2c); border: 1px solid var(--es-border, #404040); border-radius: 12px; padding: 40px; text-align: center; margin: 20px 0;">
        <div style="font-size: 48px; margin-bottom: 20px;">🔒</div>
        <h2 style="margin: 0 0 10px; color: var(--es-text, #e0e0e0);"><?php _e('Pro Feature', 'ensemble'); ?></h2>
        <p style="color: var(--es-text-secondary, #a0a0a0); max-width: 400px; margin: 0 auto 20px;">
            <?php _e('Der Export von Events als iCal, JSON oder CSV ist ein Pro-Feature. Upgrade um diese Funktion freizuschalten.', 'ensemble'); ?>
        </p>
        <a href="<?php echo admin_url('admin.php?page=ensemble-settings&tab=license'); ?>" class="button button-primary button-hero" style="background: linear-gradient(135deg, #f59e0b, #d97706); border: none;">
            <?php _e('Upgrade auf Pro', 'ensemble'); ?>
        </a>
    </div>
    <?php else: ?>
    
    <div class="es-manager-container">
        
        <div class="es-form-section">
            <h2><?php _e('Export Settings', 'ensemble'); ?></h2>
            
            <!-- Date Range Filter -->
            <div class="es-form-row">
                <label><?php _e('Date Range', 'ensemble'); ?></label>
                <div class="es-pill-group">
                    <label class="es-pill">
                        <input type="radio" name="date_filter" value="all" checked>
                        <span><?php _e('All Events', 'ensemble'); ?></span>
                    </label>
                    <label class="es-pill">
                        <input type="radio" name="date_filter" value="upcoming">
                        <span><?php _e('Upcoming Only', 'ensemble'); ?></span>
                    </label>
                    <label class="es-pill">
                        <input type="radio" name="date_filter" value="custom">
                        <span><?php _e('Custom Range', 'ensemble'); ?></span>
                    </label>
                </div>
            </div>
            
            <!-- Custom Date Range -->
            <div id="es-custom-date-range" class="es-form-row" style="display: none;">
                <div class="es-form-row-group">
                    <div>
                        <label for="es-date-from"><?php _e('From Date', 'ensemble'); ?></label>
                        <input type="date" id="es-date-from" name="date_from">
                    </div>
                    <div>
                        <label for="es-date-to"><?php _e('To Date', 'ensemble'); ?></label>
                        <input type="date" id="es-date-to" name="date_to">
                    </div>
                </div>
            </div>
            
            <!-- Category Filter -->
            <div class="es-form-row">
                <label for="es-category-filter"><?php _e('Filter by Category', 'ensemble'); ?></label>
                <select id="es-category-filter" name="category_id">
                    <option value=""><?php _e('All Categories', 'ensemble'); ?></option>
                    <?php
                    $categories = get_terms(array(
                        'taxonomy' => 'ensemble_category',
                        'hide_empty' => false,
                    ));
                    foreach ($categories as $cat) {
                        echo '<option value="' . esc_attr($cat->term_id) . '">' . esc_html($cat->name) . '</option>';
                    }
                    ?>
                </select>
            </div>
            
            <!-- Location Filter -->
            <div class="es-form-row">
                <label for="es-location-filter"><?php _e('Filter by Location', 'ensemble'); ?></label>
                <select id="es-location-filter" name="location_id">
                    <option value=""><?php _e('All Locations', 'ensemble'); ?></option>
                    <?php
                    $locations = get_posts(array(
                        'post_type' => 'ensemble_location',
                        'posts_per_page' => -1,
                        'orderby' => 'title',
                        'order' => 'ASC',
                    ));
                    foreach ($locations as $location) {
                        echo '<option value="' . esc_attr($location->ID) . '">' . esc_html($location->post_title) . '</option>';
                    }
                    ?>
                </select>
            </div>
            
            <!-- Export Format -->
            <div class="es-form-row">
                <label><?php _e('Export Format', 'ensemble'); ?></label>
                <div class="es-pill-group">
                    <label class="es-pill">
                        <input type="radio" name="export_format" value="ical" checked>
                        <span><?php _e('iCal (.ics)', 'ensemble'); ?></span>
                    </label>
                </div>
                <span class="es-field-help"><?php _e('Standard calendar format compatible with Google Calendar, Apple Calendar, Outlook, etc.', 'ensemble'); ?></span>
            </div>
            
            <!-- Preview Section -->
            <div class="es-export-preview">
                <h3><?php _e('Export Preview', 'ensemble'); ?></h3>
                <div class="es-preview-stats">
                    <div class="es-stat-item">
                        <span class="es-stat-label"><?php _e('Events to export:', 'ensemble'); ?></span>
                        <span class="es-stat-value" id="es-export-count">0</span>
                    </div>
                </div>
                <button type="button" id="es-preview-export-btn" class="button">
                    <?php _e('Preview Events', 'ensemble'); ?>
                </button>
                
                <!-- Preview List -->
                <div id="es-export-preview-list" class="es-export-preview-list" style="display: none;">
                    <div class="es-loading"><?php _e('Loading preview...', 'ensemble'); ?></div>
                </div>
            </div>
            
            <div class="es-form-actions">
                <button type="button" id="es-export-btn" class="button button-primary button-large" disabled>
                    <span class="dashicons dashicons-download"></span>
                    <?php _e('Download Export', 'ensemble'); ?>
                </button>
            </div>
        </div>
        
    </div>
    
    <!-- Success/Error Messages -->
    <div id="es-message" class="es-message" style="display: none;"></div>
    
<?php endif; // End Pro check ?>
</div>
