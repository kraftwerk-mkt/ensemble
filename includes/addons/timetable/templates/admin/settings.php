<?php
/**
 * Timetable Addon Settings
 * Rendered in Addon Modal or standalone
 *
 * @package Ensemble
 * @subpackage Addons/Timetable
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// $settings and $locations come from render_settings()
$nonce = wp_create_nonce( 'es_timetable_settings_nonce' );
?>

<div class="es-addon-settings es-timetable-settings" data-nonce="<?php echo esc_attr( $nonce ); ?>">

    <!-- Layout Section -->
    <div class="es-settings-section">
        <h4>
            <span class="dashicons dashicons-layout"></span>
            <?php esc_html_e( 'Layout', 'flavor' ); ?>
        </h4>
        
        <div class="es-form-row">
            <label><?php esc_html_e( 'Default Layout', 'flavor' ); ?></label>
            <div class="es-radio-group">
                <label class="es-radio-card">
                    <input type="radio" name="default_layout" value="horizontal" 
                           <?php checked( $settings['default_layout'], 'horizontal' ); ?>>
                    <span class="es-radio-card-content">
                        <span class="es-radio-icon">⬌</span>
                        <span class="es-radio-label"><?php esc_html_e( 'Horizontal', 'flavor' ); ?></span>
                        <span class="es-radio-desc"><?php esc_html_e( 'Time top, Stages left', 'flavor' ); ?></span>
                    </span>
                </label>
                <label class="es-radio-card">
                    <input type="radio" name="default_layout" value="vertical" 
                           <?php checked( $settings['default_layout'], 'vertical' ); ?>>
                    <span class="es-radio-card-content">
                        <span class="es-radio-icon">⬍</span>
                        <span class="es-radio-label"><?php esc_html_e( 'Vertical', 'flavor' ); ?></span>
                        <span class="es-radio-desc"><?php esc_html_e( 'Stages top, Time left', 'flavor' ); ?></span>
                    </span>
                </label>
            </div>
        </div>
    </div>

    <!-- Locations Section -->
    <div class="es-settings-section">
        <h4>
            <span class="dashicons dashicons-location"></span>
            <?php esc_html_e( 'Default Locations/Stages', 'flavor' ); ?>
        </h4>
        
        <div class="es-form-row">
            <p class="es-description"><?php esc_html_e( 'No selection = show all stages', 'flavor' ); ?></p>
            
            <?php if ( ! empty( $locations ) ) : ?>
            <div class="es-checkbox-grid">
                <?php foreach ( $locations as $loc ) : ?>
                    <label class="es-checkbox-item">
                        <input type="checkbox" 
                               name="default_locations[]" 
                               value="<?php echo esc_attr( $loc['id'] ); ?>"
                               <?php checked( in_array( $loc['id'], $settings['default_locations'] ) ); ?>>
                        <span class="es-location-color" style="background: <?php echo esc_attr( $loc['color'] ); ?>"></span>
                        <span><?php echo esc_html( $loc['name'] ); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
            <?php else : ?>
                <p class="es-notice es-notice-warning">
                    <?php esc_html_e( 'No locations found.', 'flavor' ); ?>
                </p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Location Colors Section -->
    <?php if ( ! empty( $locations ) ) : ?>
    <div class="es-settings-section">
        <h4>
            <span class="dashicons dashicons-art"></span>
            <?php esc_html_e( 'Stage Colors', 'flavor' ); ?>
        </h4>
        
        <div class="es-form-row">
            <p class="es-description"><?php esc_html_e( 'Colors for each stage in the timetable', 'flavor' ); ?></p>
            
            <div class="es-color-grid">
                <?php 
                $saved_colors = isset( $settings['location_colors'] ) ? $settings['location_colors'] : array();
                $default_colors = array( '#e94560', '#3582c4', '#4caf50', '#f0b849', '#9c27b0', '#00bcd4', '#ff5722', '#607d8b', '#e91e63', '#8bc34a' );
                $i = 0;
                foreach ( $locations as $loc ) : 
                    $saved_color = isset( $saved_colors[ $loc['id'] ] ) ? $saved_colors[ $loc['id'] ] : '';
                    $default_color = $default_colors[ $i % count( $default_colors ) ];
                    $current_color = ! empty( $saved_color ) ? $saved_color : ( ! empty( $loc['color'] ) ? $loc['color'] : $default_color );
                    $i++;
                ?>
                    <div class="es-color-item">
                        <label>
                            <span class="es-color-name"><?php echo esc_html( $loc['name'] ); ?></span>
                            <input type="color" 
                                   name="location_colors[<?php echo esc_attr( $loc['id'] ); ?>]" 
                                   value="<?php echo esc_attr( $current_color ); ?>">
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Time Section -->
    <div class="es-settings-section">
        <h4>
            <span class="dashicons dashicons-clock"></span>
            <?php esc_html_e( 'Time Range', 'flavor' ); ?>
        </h4>
        
        <div class="es-form-row es-form-row-inline">
            <div class="es-form-field">
                <label><?php esc_html_e( 'Start', 'flavor' ); ?></label>
                <input type="time" name="default_start_time" 
                       value="<?php echo esc_attr( $settings['default_start_time'] ); ?>">
            </div>
            <div class="es-form-field">
                <label><?php esc_html_e( 'End', 'flavor' ); ?></label>
                <input type="time" name="default_end_time" 
                       value="<?php echo esc_attr( $settings['default_end_time'] ); ?>">
            </div>
        </div>
    </div>

    <!-- Display Options Section -->
    <div class="es-settings-section">
        <h4>
            <span class="dashicons dashicons-visibility"></span>
            <?php esc_html_e( 'Display Options', 'flavor' ); ?>
        </h4>
        
        <div class="es-form-row">
            <label><?php esc_html_e( 'Show in event block', 'flavor' ); ?></label>
            
            <div class="es-toggle-list">
                <label class="es-toggle-item">
                    <input type="checkbox" name="show_image" value="1"
                           <?php checked( $settings['show_image'] ); ?>>
                    <span class="es-toggle-switch"></span>
                    <span><?php esc_html_e( 'Image', 'flavor' ); ?></span>
                </label>
                
                <label class="es-toggle-item">
                    <input type="checkbox" name="show_title" value="1"
                           <?php checked( $settings['show_title'] ); ?>>
                    <span class="es-toggle-switch"></span>
                    <span><?php esc_html_e( 'Title', 'flavor' ); ?></span>
                </label>
                
                <label class="es-toggle-item">
                    <input type="checkbox" name="show_time" value="1"
                           <?php checked( $settings['show_time'] ); ?>>
                    <span class="es-toggle-switch"></span>
                    <span><?php esc_html_e( 'Time', 'flavor' ); ?></span>
                </label>
                
                <label class="es-toggle-item">
                    <input type="checkbox" name="show_artist" value="1"
                           <?php checked( $settings['show_artist'] ); ?>>
                    <span class="es-toggle-switch"></span>
                    <span><?php esc_html_e( 'Artist / Speaker', 'flavor' ); ?></span>
                </label>
                
                <label class="es-toggle-item">
                    <input type="checkbox" name="show_genre" value="1"
                           <?php checked( $settings['show_genre'] ); ?>>
                    <span class="es-toggle-switch"></span>
                    <span><?php esc_html_e( 'Genre / Category', 'flavor' ); ?></span>
                </label>
            </div>
        </div>

        <div class="es-form-row">
            <label><?php esc_html_e( 'Image Position', 'flavor' ); ?></label>
            <select name="image_position">
                <option value="left" <?php selected( $settings['image_position'], 'left' ); ?>>
                    <?php esc_html_e( 'Left', 'flavor' ); ?>
                </option>
                <option value="top" <?php selected( $settings['image_position'], 'top' ); ?>>
                    <?php esc_html_e( 'Top', 'flavor' ); ?>
                </option>
                <option value="background" <?php selected( $settings['image_position'], 'background' ); ?>>
                    <?php esc_html_e( 'Background', 'flavor' ); ?>
                </option>
                <option value="none" <?php selected( $settings['image_position'], 'none' ); ?>>
                    <?php esc_html_e( 'No Image', 'flavor' ); ?>
                </option>
            </select>
        </div>
    </div>

    <!-- Dimensions Section -->
    <div class="es-settings-section">
        <h4>
            <span class="dashicons dashicons-image-crop"></span>
            <?php esc_html_e( 'Dimensions', 'flavor' ); ?>
        </h4>
        
        <div class="es-form-row es-form-row-inline">
            <div class="es-form-field">
                <label><?php esc_html_e( 'Width/Hour (horizontal)', 'flavor' ); ?></label>
                <div class="es-input-with-unit">
                    <input type="number" name="slot_width" 
                           value="<?php echo esc_attr( $settings['slot_width'] ); ?>"
                           min="60" max="200" step="10">
                    <span class="es-unit">px</span>
                </div>
            </div>
            <div class="es-form-field">
                <label><?php esc_html_e( 'Height/Hour (vertical)', 'flavor' ); ?></label>
                <div class="es-input-with-unit">
                    <input type="number" name="slot_height" 
                           value="<?php echo esc_attr( $settings['slot_height'] ); ?>"
                           min="60" max="200" step="10">
                    <span class="es-unit">px</span>
                </div>
            </div>
        </div>
        
        <div class="es-form-row">
            <label><?php esc_html_e( '"All Days" only with max. stages', 'flavor' ); ?></label>
            <div class="es-input-with-unit">
                <input type="number" name="max_stages_all_days" 
                       value="<?php echo esc_attr( $settings['max_stages_all_days'] ?? 4 ); ?>"
                       min="0" max="20" step="1">
                <span class="es-unit">Stages</span>
            </div>
            <p class="es-description"><?php esc_html_e( 'With more stages, "All Days" is hidden and the first day is shown. 0 = always show.', 'flavor' ); ?></p>
        </div>
    </div>

    <!-- Save Button -->
    <div class="es-settings-actions">
        <button type="button" class="es-btn es-btn-primary" id="es-save-timetable-settings">
            <span class="dashicons dashicons-saved"></span>
            <?php esc_html_e( 'Save Settings', 'flavor' ); ?>
        </button>
        <span class="es-save-status"></span>
    </div>

    <!-- Shortcode Help -->
    <div class="es-settings-section es-settings-help">
        <h4>
            <span class="dashicons dashicons-editor-code"></span>
            <?php esc_html_e( 'Shortcode', 'flavor' ); ?>
        </h4>
        
        <div class="es-code-examples">
            <code>[ensemble_timetable]</code>
            <code>[ensemble_timetable layout="vertical" locations="12,15"]</code>
        </div>
    </div>

</div>

<style>
.es-timetable-settings {
    padding: 20px;
    max-width: 800px;
}

.es-timetable-settings .es-settings-section {
    margin-bottom: 24px;
    padding-bottom: 24px;
    border-bottom: 1px solid var(--es-border, #404040);
}

.es-timetable-settings .es-settings-section:last-child {
    border-bottom: none;
}

.es-timetable-settings h4 {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0 0 16px 0;
    font-size: 15px;
    font-weight: 600;
    color: var(--es-text, #e0e0e0);
}

.es-timetable-settings h4 .dashicons {
    color: var(--es-primary, #3582c4);
}

.es-timetable-settings .es-form-row {
    margin-bottom: 16px;
}

.es-timetable-settings .es-form-row:last-child {
    margin-bottom: 0;
}

.es-timetable-settings .es-form-row > label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: var(--es-text, #e0e0e0);
}

.es-timetable-settings .es-description {
    font-size: 13px;
    color: var(--es-text-secondary, #a0a0a0);
    margin: 0 0 12px 0;
}

/* Radio Cards */
.es-radio-group {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
}

.es-radio-card {
    flex: 1;
    min-width: 140px;
    cursor: pointer;
}

.es-radio-card input {
    display: none;
}

.es-radio-card-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    padding: 16px;
    background: var(--es-surface-secondary, #383838);
    border: 2px solid var(--es-border, #404040);
    border-radius: 8px;
    transition: all 0.2s ease;
    text-align: center;
}

.es-radio-card input:checked + .es-radio-card-content {
    border-color: var(--es-primary, #3582c4);
    background: rgba(53, 130, 196, 0.15);
}

.es-radio-icon {
    font-size: 24px;
}

.es-radio-label {
    font-weight: 600;
}

.es-radio-desc {
    font-size: 11px;
    color: var(--es-text-secondary, #a0a0a0);
}

/* Checkbox Grid */
.es-checkbox-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 8px;
}

.es-checkbox-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 12px;
    background: var(--es-surface-secondary, #383838);
    border: 1px solid var(--es-border, #404040);
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.es-checkbox-item:hover {
    border-color: var(--es-primary, #3582c4);
}

.es-location-color {
    width: 12px;
    height: 12px;
    border-radius: 3px;
    flex-shrink: 0;
}

/* Toggle List */
.es-toggle-list {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}

.es-toggle-item {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}

.es-toggle-item input {
    display: none;
}

.es-toggle-switch {
    width: 40px;
    height: 22px;
    background: var(--es-border, #404040);
    border-radius: 11px;
    position: relative;
    transition: background 0.2s ease;
}

.es-toggle-switch::after {
    content: '';
    width: 18px;
    height: 18px;
    background: #fff;
    border-radius: 50%;
    position: absolute;
    top: 2px;
    left: 2px;
    transition: transform 0.2s ease;
}

.es-toggle-item input:checked + .es-toggle-switch {
    background: var(--es-primary, #3582c4);
}

.es-toggle-item input:checked + .es-toggle-switch::after {
    transform: translateX(18px);
}

/* Form Row Inline */
.es-form-row-inline {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.es-form-row-inline .es-form-field {
    flex: 1;
    min-width: 150px;
}

.es-form-row-inline .es-form-field label {
    display: block;
    margin-bottom: 6px;
    font-size: 13px;
    color: var(--es-text-secondary, #a0a0a0);
}

/* Time Input */
.es-timetable-settings input[type="time"] {
    padding: 8px 12px;
    border: 1px solid var(--es-border, #404040);
    background: var(--es-surface, #2a2a2a);
    color: var(--es-text, #e0e0e0);
    border-radius: 6px;
    font-size: 14px;
}

/* Input with unit */
.es-input-with-unit {
    display: flex;
}

.es-input-with-unit input {
    width: 80px;
    border-radius: 6px 0 0 6px;
    border: 1px solid var(--es-border, #404040);
    background: var(--es-surface, #2a2a2a);
    color: var(--es-text, #e0e0e0);
    padding: 8px 10px;
}

.es-input-with-unit .es-unit {
    padding: 8px 12px;
    background: var(--es-surface-secondary, #383838);
    border: 1px solid var(--es-border, #404040);
    border-left: none;
    border-radius: 0 6px 6px 0;
    color: var(--es-text-secondary, #a0a0a0);
    font-size: 13px;
}

/* Select */
.es-timetable-settings select {
    width: 200px;
    padding: 8px 12px;
    border: 1px solid var(--es-border, #404040);
    background: var(--es-surface, #2a2a2a);
    color: var(--es-text, #e0e0e0);
    border-radius: 6px;
}

/* Actions */
.es-settings-actions {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 20px 0;
    border-top: 1px solid var(--es-border, #404040);
    margin-top: 10px;
}

.es-settings-actions .es-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: var(--es-primary, #3582c4);
    color: #fff;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
}

.es-settings-actions .es-btn:hover {
    background: var(--es-primary-dark, #2a6da8);
}

.es-save-status {
    font-size: 13px;
    color: var(--es-success, #4caf50);
}

/* Help Section */
.es-settings-help {
    background: rgba(53, 130, 196, 0.1);
    padding: 16px !important;
    border-radius: 8px;
    border: 1px solid var(--es-primary, #3582c4) !important;
    margin-top: 20px;
}

.es-code-examples {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.es-code-examples code {
    display: block;
    padding: 10px;
    background: var(--es-surface, #2a2a2a);
    border-radius: 4px;
    font-family: monospace;
    font-size: 12px;
    color: var(--es-text, #e0e0e0);
}

/* Color Grid */
.es-color-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 12px;
}

.es-color-item {
    display: flex;
    align-items: center;
    background: var(--es-surface-secondary, #383838);
    border: 1px solid var(--es-border, #404040);
    border-radius: 6px;
    overflow: hidden;
}

.es-color-item label {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 12px;
    width: 100%;
    cursor: pointer;
}

.es-color-name {
    flex: 1;
    font-size: 13px;
    color: var(--es-text, #e0e0e0);
}

.es-color-item input[type="color"] {
    width: 36px;
    height: 28px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    padding: 0;
    background: transparent;
}

.es-color-item input[type="color"]::-webkit-color-swatch-wrapper {
    padding: 0;
}

.es-color-item input[type="color"]::-webkit-color-swatch {
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 4px;
}

/* Notice */
.es-notice-warning {
    padding: 12px;
    background: rgba(240, 184, 73, 0.15);
    border-left: 4px solid var(--es-warning, #f0b849);
    border-radius: 4px;
    color: var(--es-warning, #f0b849);
}
</style>

<script>
jQuery(function($) {
    var $settings = $('.es-timetable-settings');
    var nonce = $settings.data('nonce');

    $('#es-save-timetable-settings').on('click', function() {
        var $btn = $(this);
        var $status = $('.es-save-status');
        
        // Collect form data
        var settings = {
            default_layout: $settings.find('input[name="default_layout"]:checked').val(),
            default_locations: [],
            location_colors: {},
            default_start_time: $settings.find('input[name="default_start_time"]').val(),
            default_end_time: $settings.find('input[name="default_end_time"]').val(),
            show_image: $settings.find('input[name="show_image"]').is(':checked') ? 1 : 0,
            show_title: $settings.find('input[name="show_title"]').is(':checked') ? 1 : 0,
            show_time: $settings.find('input[name="show_time"]').is(':checked') ? 1 : 0,
            show_artist: $settings.find('input[name="show_artist"]').is(':checked') ? 1 : 0,
            show_genre: $settings.find('input[name="show_genre"]').is(':checked') ? 1 : 0,
            image_position: $settings.find('select[name="image_position"]').val(),
            slot_width: $settings.find('input[name="slot_width"]').val(),
            slot_height: $settings.find('input[name="slot_height"]').val(),
            max_stages_all_days: $settings.find('input[name="max_stages_all_days"]').val()
        };

        // Get selected locations
        $settings.find('input[name="default_locations[]"]:checked').each(function() {
            settings.default_locations.push($(this).val());
        });

        // Get location colors
        $settings.find('input[name^="location_colors"]').each(function() {
            var name = $(this).attr('name');
            var match = name.match(/location_colors\[(\d+)\]/);
            if (match) {
                settings.location_colors[match[1]] = $(this).val();
            }
        });

        $btn.prop('disabled', true).find('.dashicons').removeClass('dashicons-saved').addClass('dashicons-update spin');
        $status.text('');

        $.post(ajaxurl, {
            action: 'es_save_timetable_settings',
            nonce: nonce,
            settings: settings
        }, function(response) {
            $btn.prop('disabled', false).find('.dashicons').removeClass('dashicons-update spin').addClass('dashicons-saved');
            
            if (response.success) {
                $status.text('✓ Saved').css('color', 'var(--es-success, #4caf50)');
            } else {
                $status.text('✗ Error').css('color', 'var(--es-error, #f44336)');
            }

            setTimeout(function() {
                $status.text('');
            }, 3000);
        });
    });
});
</script>
