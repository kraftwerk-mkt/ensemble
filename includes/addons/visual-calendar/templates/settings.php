<?php
/**
 * Visual Calendar Pro - Settings Template
 * 
 * @package Ensemble
 * @subpackage Addons/VisualCalendar
 * 
 * Variables available:
 * - $settings (array) - Current settings (passed via load_template)
 */

if (!defined('ABSPATH')) exit;
?>

<div class="es-addon-settings-content">
        
    <!-- Display Options -->
    <div class="es-settings-section">
        <h3><?php _e('Display Options', 'ensemble'); ?></h3>
        
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Color Scheme', 'ensemble'); ?></th>
                <td>
                    <select name="color_scheme">
                        <option value="dark" <?php selected($settings['color_scheme'] ?? 'dark', 'dark'); ?>><?php _e('Dark', 'ensemble'); ?></option>
                        <option value="light" <?php selected($settings['color_scheme'] ?? 'dark', 'light'); ?>><?php _e('Light', 'ensemble'); ?></option>
                        <option value="auto" <?php selected($settings['color_scheme'] ?? 'dark', 'auto'); ?>><?php _e('Auto (System)', 'ensemble'); ?></option>
                    </select>
                    <p class="description"><?php _e('Choose the default color scheme for the calendar.', 'ensemble'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('Cell Aspect Ratio', 'ensemble'); ?></th>
                <td>
                    <select name="aspect_ratio">
                        <option value="1/1" <?php selected($settings['aspect_ratio'] ?? '1/1', '1/1'); ?>><?php _e('Square (1:1)', 'ensemble'); ?></option>
                        <option value="4/3" <?php selected($settings['aspect_ratio'] ?? '1/1', '4/3'); ?>><?php _e('Landscape (4:3)', 'ensemble'); ?></option>
                        <option value="3/4" <?php selected($settings['aspect_ratio'] ?? '1/1', '3/4'); ?>><?php _e('Portrait (3:4)', 'ensemble'); ?></option>
                        <option value="16/9" <?php selected($settings['aspect_ratio'] ?? '1/1', '16/9'); ?>><?php _e('Wide (16:9)', 'ensemble'); ?></option>
                    </select>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('Image Size', 'ensemble'); ?></th>
                <td>
                    <select name="image_size">
                        <option value="thumbnail" <?php selected($settings['image_size'] ?? 'medium_large', 'thumbnail'); ?>><?php _e('Thumbnail', 'ensemble'); ?></option>
                        <option value="medium" <?php selected($settings['image_size'] ?? 'medium_large', 'medium'); ?>><?php _e('Medium', 'ensemble'); ?></option>
                        <option value="medium_large" <?php selected($settings['image_size'] ?? 'medium_large', 'medium_large'); ?>><?php _e('Medium Large', 'ensemble'); ?></option>
                        <option value="large" <?php selected($settings['image_size'] ?? 'medium_large', 'large'); ?>><?php _e('Large', 'ensemble'); ?></option>
                    </select>
                    <p class="description"><?php _e('Image size to use for event thumbnails. Larger sizes look better but load slower.', 'ensemble'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('Hover Effect', 'ensemble'); ?></th>
                <td>
                    <select name="hover_effect">
                        <option value="zoom" <?php selected($settings['hover_effect'] ?? 'zoom', 'zoom'); ?>><?php _e('Zoom', 'ensemble'); ?></option>
                        <option value="lift" <?php selected($settings['hover_effect'] ?? 'zoom', 'lift'); ?>><?php _e('Lift', 'ensemble'); ?></option>
                        <option value="none" <?php selected($settings['hover_effect'] ?? 'zoom', 'none'); ?>><?php _e('None', 'ensemble'); ?></option>
                    </select>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('Date Badge Style', 'ensemble'); ?></th>
                <td>
                    <select name="date_badge_style">
                        <option value="overlay" <?php selected($settings['date_badge_style'] ?? 'overlay', 'overlay'); ?>><?php _e('Overlay (on image)', 'ensemble'); ?></option>
                        <option value="corner" <?php selected($settings['date_badge_style'] ?? 'overlay', 'corner'); ?>><?php _e('Corner Badge', 'ensemble'); ?></option>
                        <option value="minimal" <?php selected($settings['date_badge_style'] ?? 'overlay', 'minimal'); ?>><?php _e('Minimal', 'ensemble'); ?></option>
                    </select>
                </td>
            </tr>
        </table>
    </div>
    
    <!-- Content Options -->
    <div class="es-settings-section">
        <h3><?php _e('Content Options', 'ensemble'); ?></h3>
        
        <div class="es-toggle-group" style="margin-top: 16px;">
            <label class="es-toggle es-toggle--reverse es-toggle--block">
                <input type="checkbox" name="show_time" value="1" <?php checked($settings['show_time'] ?? true); ?>>
                <span class="es-toggle-track"></span>
                <span class="es-toggle-label">
                    <?php _e('Show Time', 'ensemble'); ?>
                    <small><?php _e('Display event start time on calendar cells', 'ensemble'); ?></small>
                </span>
            </label>
            
            <label class="es-toggle es-toggle--reverse es-toggle--block">
                <input type="checkbox" name="show_location" value="1" <?php checked($settings['show_location'] ?? false); ?>>
                <span class="es-toggle-track"></span>
                <span class="es-toggle-label">
                    <?php _e('Show Location', 'ensemble'); ?>
                    <small><?php _e('Display location name on calendar cells', 'ensemble'); ?></small>
                </span>
            </label>
            
            <label class="es-toggle es-toggle--reverse es-toggle--block">
                <input type="checkbox" name="show_event_count" value="1" <?php checked($settings['show_event_count'] ?? true); ?>>
                <span class="es-toggle-track"></span>
                <span class="es-toggle-label">
                    <?php _e('Show Event Count', 'ensemble'); ?>
                    <small><?php _e('Show "X Events" badge when multiple events on same day', 'ensemble'); ?></small>
                </span>
            </label>
            
            <label class="es-toggle es-toggle--reverse es-toggle--block">
                <input type="checkbox" name="show_empty_days" value="1" <?php checked($settings['show_empty_days'] ?? true); ?>>
                <span class="es-toggle-track"></span>
                <span class="es-toggle-label">
                    <?php _e('Show Empty Days', 'ensemble'); ?>
                    <small><?php _e('Show cells for days without events', 'ensemble'); ?></small>
                </span>
            </label>
        </div>
        
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Empty Day Text', 'ensemble'); ?></th>
                <td>
                    <input type="text" name="empty_day_text" value="<?php echo esc_attr($settings['empty_day_text'] ?? 'No Events'); ?>" class="regular-text">
                    <p class="description"><?php _e('Text to display on days without events.', 'ensemble'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('Max Events Per Day', 'ensemble'); ?></th>
                <td>
                    <input type="number" name="max_events_per_day" value="<?php echo esc_attr($settings['max_events_per_day'] ?? 3); ?>" min="1" max="10" class="small-text">
                    <p class="description"><?php _e('Maximum number of events to show in the dropdown for days with multiple events.', 'ensemble'); ?></p>
                </td>
            </tr>
        </table>
    </div>
    
    <!-- Shortcode Reference -->
    <div class="es-settings-section">
        <h3><?php _e('Shortcode Usage', 'ensemble'); ?></h3>
        
        <div class="es-shortcode-examples">
            <p><strong><?php _e('Basic Usage:', 'ensemble'); ?></strong></p>
            <code>[ensemble_visual_calendar]</code>
            
            <p style="margin-top: 15px;"><strong><?php _e('With Options:', 'ensemble'); ?></strong></p>
            <code>[ensemble_visual_calendar color_scheme="dark" show_navigation="true"]</code>
            
            <p style="margin-top: 15px;"><strong><?php _e('Filter by Category:', 'ensemble'); ?></strong></p>
            <code>[ensemble_visual_calendar category="concerts"]</code>
            
            <p style="margin-top: 15px;"><strong><?php _e('Specific Month:', 'ensemble'); ?></strong></p>
            <code>[ensemble_visual_calendar year="2025" month="12"]</code>
            
            <p style="margin-top: 15px;"><strong><?php _e('Alternative Shortcode Names:', 'ensemble'); ?></strong></p>
            <code>[ensemble_photo_calendar]</code>
            <code>[ensemble_calendar_grid]</code>
        </div>
        
        <h4 style="margin-top: 20px;"><?php _e('Available Attributes', 'ensemble'); ?></h4>
        <table class="widefat" style="max-width: 600px;">
            <thead>
                <tr>
                    <th><?php _e('Attribute', 'ensemble'); ?></th>
                    <th><?php _e('Default', 'ensemble'); ?></th>
                    <th><?php _e('Description', 'ensemble'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>year</code></td>
                    <td><?php echo date('Y'); ?></td>
                    <td><?php _e('Starting year', 'ensemble'); ?></td>
                </tr>
                <tr>
                    <td><code>month</code></td>
                    <td><?php echo date('n'); ?></td>
                    <td><?php _e('Starting month (1-12)', 'ensemble'); ?></td>
                </tr>
                <tr>
                    <td><code>color_scheme</code></td>
                    <td>dark</td>
                    <td><?php _e('dark, light, or auto', 'ensemble'); ?></td>
                </tr>
                <tr>
                    <td><code>show_weekdays</code></td>
                    <td>true</td>
                    <td><?php _e('Show weekday headers', 'ensemble'); ?></td>
                </tr>
                <tr>
                    <td><code>show_navigation</code></td>
                    <td>true</td>
                    <td><?php _e('Show month navigation', 'ensemble'); ?></td>
                </tr>
                <tr>
                    <td><code>show_empty_days</code></td>
                    <td>true</td>
                    <td><?php _e('Show empty day cells', 'ensemble'); ?></td>
                </tr>
                <tr>
                    <td><code>category</code></td>
                    <td></td>
                    <td><?php _e('Filter by category slug or ID', 'ensemble'); ?></td>
                </tr>
                <tr>
                    <td><code>location</code></td>
                    <td></td>
                    <td><?php _e('Filter by location ID', 'ensemble'); ?></td>
                </tr>
            </tbody>
        </table>
    </div>
        
</div>

<style>
.es-addon-settings-content .es-settings-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
}

.es-addon-settings-content .es-settings-section:first-child {
    margin-top: 0;
}

.es-addon-settings-content .es-settings-section h3 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.es-addon-settings-content .es-shortcode-examples code {
    display: block;
    padding: 10px 15px;
    background: #f5f5f5;
    border-radius: 4px;
    font-size: 13px;
    margin-bottom: 5px;
}
</style>
