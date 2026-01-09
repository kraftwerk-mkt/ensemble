<?php
/**
 * Maps Pro - Settings Template
 * 
 * @package Ensemble
 * @subpackage Addons/Maps Pro
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Map styles for OpenStreetMap
$osm_styles = array(
    'default'    => 'Standard',
    'dark'       => 'Dark Mode',
    'light'      => 'Light',
    'voyager'    => 'Voyager',
    'watercolor' => 'Watercolor',
    'terrain'    => 'Terrain',
    'satellite'  => 'Satellite',
);

// Map styles for Google Maps
$google_styles = array(
    'default' => 'Standard',
    'dark'    => 'Dark Mode',
    'light'   => 'Light / Silver',
    'retro'   => 'Retro / Vintage',
    'night'   => 'Night Mode',
);

$current_provider = $settings['provider'] ?? 'osm';
?>
<div class="es-maps-settings">
    
    <!-- Provider Selection -->
    <div class="es-settings-section">
        <h3><?php _e('Map Provider', 'ensemble'); ?></h3>
        
        <div class="es-provider-options">
            <label class="es-provider-option <?php echo $current_provider === 'osm' ? 'active' : ''; ?>">
                <input type="radio" name="provider" value="osm" <?php checked($current_provider, 'osm'); ?>>
                <div class="es-provider-content">
                    <strong>OpenStreetMap (Leaflet)</strong>
                    <span class="es-badge es-badge--success"><?php _e('Free', 'ensemble'); ?></span>
                    <span class="es-badge es-badge--info"><?php _e('Recommended', 'ensemble'); ?></span>
                    <p><?php _e('Free maps without API key. Full Pro features.', 'ensemble'); ?></p>
                </div>
            </label>
            
            <label class="es-provider-option <?php echo $current_provider === 'google' ? 'active' : ''; ?>">
                <input type="radio" name="provider" value="google" <?php checked($current_provider, 'google'); ?>>
                <div class="es-provider-content">
                    <strong>Google Maps</strong>
                    <span class="es-badge es-badge--warning"><?php _e('API Key Required', 'ensemble'); ?></span>
                    <p><?php _e('Requires a Google Maps API key.', 'ensemble'); ?></p>
                </div>
            </label>
        </div>
    </div>
    
    <!-- Google Maps Settings (conditional) -->
    <div class="es-settings-section es-google-settings" style="<?php echo $current_provider === 'google' ? '' : 'display:none;'; ?>">
        <h3><?php _e('Google Maps Configuration', 'ensemble'); ?></h3>
        
        <div class="es-field">
            <label><?php _e('API Key', 'ensemble'); ?></label>
            <input type="text" 
                   name="google_api_key" 
                   value="<?php echo esc_attr($settings['google_api_key'] ?? ''); ?>" 
                   placeholder="AIzaSy..."
                   class="es-input-wide">
            <p class="es-hint">
                <?php printf(__('Get your API key from %s', 'ensemble'), '<a href="https://console.cloud.google.com/google/maps-apis" target="_blank">Google Cloud Console</a>'); ?>
            </p>
        </div>
        
        <div class="es-field">
            <label><?php _e('Map Style', 'ensemble'); ?></label>
            <div class="es-style-grid">
                <?php foreach ($google_styles as $key => $label): ?>
                    <label class="es-style-card <?php echo ($settings['google_map_style'] ?? 'default') === $key ? 'active' : ''; ?>">
                        <input type="radio" name="google_map_style" value="<?php echo esc_attr($key); ?>" <?php checked($settings['google_map_style'] ?? 'default', $key); ?>>
                        <span class="es-style-preview es-style--google-<?php echo esc_attr($key); ?>"></span>
                        <span class="es-style-name"><?php echo esc_html($label); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="es-field">
            <label class="es-checkbox">
                <input type="hidden" name="google_street_view" value="0">
                <input type="checkbox" name="google_street_view" value="1" <?php checked($settings['google_street_view'] ?? true, true); ?>>
                <span><?php _e('Enable Street View', 'ensemble'); ?></span>
            </label>
            <label class="es-checkbox">
                <input type="hidden" name="google_map_type_control" value="0">
                <input type="checkbox" name="google_map_type_control" value="1" <?php checked($settings['google_map_type_control'] ?? true, true); ?>>
                <span><?php _e('Show Map Type Control', 'ensemble'); ?></span>
            </label>
        </div>
    </div>
    
    <!-- OpenStreetMap Style (conditional) -->
    <div class="es-settings-section es-osm-settings" style="<?php echo $current_provider === 'osm' ? '' : 'display:none;'; ?>">
        <h3><?php _e('Map Style', 'ensemble'); ?> <span class="es-badge es-badge--pro">PRO</span></h3>
        
        <div class="es-field">
            <div class="es-style-grid">
                <?php foreach ($osm_styles as $key => $label): ?>
                    <label class="es-style-card <?php echo ($settings['map_style'] ?? 'default') === $key ? 'active' : ''; ?>">
                        <input type="radio" name="map_style" value="<?php echo esc_attr($key); ?>" <?php checked($settings['map_style'] ?? 'default', $key); ?>>
                        <span class="es-style-preview es-style--osm-<?php echo esc_attr($key); ?>"></span>
                        <span class="es-style-name"><?php echo esc_html($label); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- Zoom Settings -->
    <div class="es-settings-section">
        <h3><?php _e('Zoom Settings', 'ensemble'); ?></h3>
        
        <div class="es-field-row">
            <div class="es-field es-field--half">
                <label><?php _e('Single Location Zoom', 'ensemble'); ?></label>
                <input type="number" name="default_zoom" value="<?php echo esc_attr($settings['default_zoom'] ?? 15); ?>" min="1" max="20" class="es-input-small">
                <p class="es-hint"><?php _e('Recommended: 15', 'ensemble'); ?></p>
            </div>
            <div class="es-field es-field--half">
                <label><?php _e('Overview Map Zoom', 'ensemble'); ?></label>
                <input type="number" name="overview_zoom" value="<?php echo esc_attr($settings['overview_zoom'] ?? 6); ?>" min="1" max="20" class="es-input-small">
                <p class="es-hint"><?php _e('Recommended: 6', 'ensemble'); ?></p>
            </div>
        </div>
        
        <div class="es-field">
            <label><?php _e('Default Coordinates (Fallback)', 'ensemble'); ?></label>
            <div class="es-field-row">
                <div class="es-field--half">
                    <input type="number" name="default_lat" value="<?php echo esc_attr($settings['default_lat'] ?? 51.1657); ?>" step="any" placeholder="Latitude" class="es-input-small">
                </div>
                <div class="es-field--half">
                    <input type="number" name="default_lng" value="<?php echo esc_attr($settings['default_lng'] ?? 10.4515); ?>" step="any" placeholder="Longitude" class="es-input-small">
                </div>
            </div>
            <p class="es-hint"><?php _e('Map center when no coordinates available (default: Germany)', 'ensemble'); ?></p>
        </div>
    </div>
    
    <!-- Pro Features -->
    <div class="es-settings-section">
        <h3><?php _e('Features', 'ensemble'); ?></h3>
        
        <div class="es-setting-row">
            <div class="es-setting-info">
                <span class="es-setting-title"><?php _e('Marker Clustering', 'ensemble'); ?></span>
                <span class="es-setting-desc"><?php _e('Groups nearby markers into clusters', 'ensemble'); ?></span>
            </div>
            <label class="es-toggle-wrapper">
                <input type="hidden" name="enable_clustering" value="0">
                <input type="checkbox" name="enable_clustering" value="1" <?php checked($settings['enable_clustering'] ?? true, true); ?>>
                <span class="es-toggle-switch"></span>
            </label>
        </div>
        
        <div class="es-setting-row">
            <div class="es-setting-info">
                <span class="es-setting-title"><?php _e('Fullscreen Mode', 'ensemble'); ?></span>
                <span class="es-setting-desc"><?php _e('Enables fullscreen view of the map', 'ensemble'); ?></span>
            </div>
            <label class="es-toggle-wrapper">
                <input type="hidden" name="enable_fullscreen" value="0">
                <input type="checkbox" name="enable_fullscreen" value="1" <?php checked($settings['enable_fullscreen'] ?? true, true); ?>>
                <span class="es-toggle-switch"></span>
            </label>
        </div>
        
        <div class="es-setting-row">
            <div class="es-setting-info">
                <span class="es-setting-title"><?php _e('Geolocation', 'ensemble'); ?></span>
                <span class="es-setting-desc"><?php _e('"My Location" button with distance display', 'ensemble'); ?></span>
            </div>
            <label class="es-toggle-wrapper">
                <input type="hidden" name="enable_geolocation" value="0">
                <input type="checkbox" name="enable_geolocation" value="1" <?php checked($settings['enable_geolocation'] ?? true, true); ?>>
                <span class="es-toggle-switch"></span>
            </label>
        </div>
        
        <div class="es-setting-row">
            <div class="es-setting-info">
                <span class="es-setting-title"><?php _e('Route Planning', 'ensemble'); ?></span>
                <span class="es-setting-desc"><?php _e('Inline directions on the map', 'ensemble'); ?></span>
            </div>
            <label class="es-toggle-wrapper">
                <input type="hidden" name="enable_routing" value="0">
                <input type="checkbox" name="enable_routing" value="1" <?php checked($settings['enable_routing'] ?? true, true); ?>>
                <span class="es-toggle-switch"></span>
            </label>
        </div>
        
        <div class="es-setting-row">
            <div class="es-setting-info">
                <span class="es-setting-title"><?php _e('Location Search', 'ensemble'); ?></span>
                <span class="es-setting-desc"><?php _e('Search field on overview maps', 'ensemble'); ?></span>
            </div>
            <label class="es-toggle-wrapper">
                <input type="hidden" name="enable_search" value="0">
                <input type="checkbox" name="enable_search" value="1" <?php checked($settings['enable_search'] ?? true, true); ?>>
                <span class="es-toggle-switch"></span>
            </label>
        </div>
        
        <div class="es-setting-row">
            <div class="es-setting-info">
                <span class="es-setting-title"><?php _e('Marker Thumbnails', 'ensemble'); ?></span>
                <span class="es-setting-desc"><?php _e('Show location images in map markers (overview maps)', 'ensemble'); ?></span>
            </div>
            <label class="es-toggle-wrapper">
                <input type="hidden" name="enable_marker_thumbnails" value="0">
                <input type="checkbox" name="enable_marker_thumbnails" value="1" <?php checked($settings['enable_marker_thumbnails'] ?? true, true); ?>>
                <span class="es-toggle-switch"></span>
            </label>
        </div>
        
        <div class="es-setting-row">
            <div class="es-setting-info">
                <span class="es-setting-title"><?php _e('Events in Popup', 'ensemble'); ?></span>
                <span class="es-setting-desc"><?php _e('Shows upcoming events in location popups', 'ensemble'); ?></span>
            </div>
            <label class="es-toggle-wrapper">
                <input type="hidden" name="show_upcoming_events" value="0">
                <input type="checkbox" name="show_upcoming_events" value="1" <?php checked($settings['show_upcoming_events'] ?? true, true); ?>>
                <span class="es-toggle-switch"></span>
            </label>
        </div>
        
        <div class="es-setting-row">
            <div class="es-setting-info">
                <span class="es-setting-title"><?php _e('Auto Geocoding', 'ensemble'); ?></span>
                <span class="es-setting-desc"><?php _e('Automatically get coordinates from address', 'ensemble'); ?></span>
            </div>
            <label class="es-toggle-wrapper">
                <input type="hidden" name="auto_geocode" value="0">
                <input type="checkbox" name="auto_geocode" value="1" <?php checked($settings['auto_geocode'] ?? true, true); ?>>
                <span class="es-toggle-switch"></span>
            </label>
        </div>
    </div>
    
    <!-- Shortcodes -->
    <div class="es-settings-section">
        <h3><?php _e('Shortcodes', 'ensemble'); ?></h3>
        
        <div class="es-shortcode-info">
            <div class="es-shortcode-block">
                <h4><?php _e('All Locations Map', 'ensemble'); ?></h4>
                <code>[ensemble_locations_map]</code>
                <p><?php _e('Shows all locations on an interactive map', 'ensemble'); ?></p>
            </div>
            
            <div class="es-shortcode-block">
                <h4><?php _e('With Filters', 'ensemble'); ?></h4>
                <code>[ensemble_locations_map city="Berlin"]</code><br>
                <code>[ensemble_locations_map category="venue"]</code><br>
                <code>[ensemble_locations_map height="600px" style="dark"]</code>
            </div>
            
            <div class="es-shortcode-block">
                <h4><?php _e('Single Location/Event', 'ensemble'); ?></h4>
                <code>[ensemble_map location="123"]</code><br>
                <code>[ensemble_map event="456"]</code>
            </div>
        </div>
    </div>
    
</div>

<style>
/* Maps Pro Settings Styles */
.es-maps-settings {
    max-width: 800px;
}

.es-settings-section {
    margin-bottom: 30px;
    padding-bottom: 30px;
    border-bottom: 1px solid var(--ensemble-card-border, #3a3a3a);
}

.es-settings-section:last-child {
    border-bottom: none;
}

.es-settings-section h3 {
    margin: 0 0 20px 0;
    font-size: 16px;
    font-weight: 600;
    color: var(--ensemble-text, #e0e0e0);
}

/* Provider Options */
.es-provider-options {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.es-provider-option {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 16px;
    background: var(--ensemble-hover, #2a2a2a);
    border: 2px solid transparent;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
}

.es-provider-option:hover {
    background: var(--ensemble-active, #333);
}

.es-provider-option.active {
    border-color: var(--ensemble-primary, #3b82f6);
    background: rgba(59, 130, 246, 0.1);
}

.es-provider-option input[type="radio"] {
    margin-top: 3px;
}

.es-provider-content {
    flex: 1;
}

.es-provider-content strong {
    display: block;
    margin-bottom: 4px;
}

.es-provider-content p {
    margin: 8px 0 0;
    font-size: 13px;
    color: var(--ensemble-text-secondary, #9ca3af);
}

/* Badges */
.es-badge {
    display: inline-block;
    padding: 2px 8px;
    font-size: 11px;
    font-weight: 500;
    border-radius: 4px;
    margin-left: 6px;
    vertical-align: middle;
}

.es-badge--success { background: #065f46; color: #34d399; }
.es-badge--warning { background: #78350f; color: #fbbf24; }
.es-badge--info { background: #1e3a5f; color: #60a5fa; }
.es-badge--pro { background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; }

/* Fields */
.es-field {
    margin-bottom: 20px;
}

.es-field:last-child {
    margin-bottom: 0;
}

.es-field > label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: var(--ensemble-text, #e0e0e0);
}

.es-field-row {
    display: flex;
    gap: 16px;
}

.es-field--half {
    flex: 1;
}

.es-hint {
    margin: 6px 0 0;
    font-size: 12px;
    color: var(--ensemble-text-secondary, #9ca3af);
}

/* Inputs */
.es-input-wide {
    width: 100%;
    max-width: 400px;
    padding: 10px 12px;
    background: var(--ensemble-input-bg, #1a1a1a);
    border: 1px solid var(--ensemble-input-border, #3a3a3a);
    border-radius: 6px;
    color: var(--ensemble-text, #e0e0e0);
    font-size: 14px;
}

.es-input-small {
    width: 100%;
    max-width: 120px;
    padding: 8px 12px;
    background: var(--ensemble-input-bg, #1a1a1a);
    border: 1px solid var(--ensemble-input-border, #3a3a3a);
    border-radius: 6px;
    color: var(--ensemble-text, #e0e0e0);
    font-size: 14px;
}

/* Style Grid */
.es-style-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 12px;
}

.es-style-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 12px;
    background: var(--ensemble-hover, #2a2a2a);
    border: 2px solid transparent;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
}

.es-style-card:hover {
    background: var(--ensemble-active, #333);
}

.es-style-card.active {
    border-color: var(--ensemble-primary, #3b82f6);
}

.es-style-card input {
    display: none;
}

.es-style-preview {
    width: 100%;
    height: 50px;
    border-radius: 4px;
    margin-bottom: 8px;
}

.es-style-name {
    font-size: 12px;
    text-align: center;
}

/* Style Previews - OSM */
.es-style--osm-default { background: linear-gradient(135deg, #f0ebe3, #d4e5d2); }
.es-style--osm-dark { background: linear-gradient(135deg, #1a1a2e, #16213e); }
.es-style--osm-light { background: linear-gradient(135deg, #f5f5f5, #e8e8e8); }
.es-style--osm-voyager { background: linear-gradient(135deg, #fafafa, #e3f2fd); }
.es-style--osm-watercolor { background: linear-gradient(135deg, #a8e6cf, #88d8b0, #ffeaa7); }
.es-style--osm-terrain { background: linear-gradient(135deg, #d4c4a8, #a3b18a); }
.es-style--osm-satellite { background: linear-gradient(135deg, #2d5016, #1a3a0f, #0d1f07); }

/* Style Previews - Google */
.es-style--google-default { background: linear-gradient(135deg, #e8eaed, #f1f3f4, #d4e5d2); }
.es-style--google-dark { background: linear-gradient(135deg, #212121, #303030, #424242); }
.es-style--google-light { background: linear-gradient(135deg, #f5f5f5, #e0e0e0, #bdbdbd); }
.es-style--google-retro { background: linear-gradient(135deg, #ebe3cd, #dfd2ae, #b9d3c2); }
.es-style--google-night { background: linear-gradient(135deg, #242f3e, #38414e, #17263c); }

/* Checkbox */
.es-checkbox {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 10px;
    cursor: pointer;
}

.es-checkbox input {
    width: 16px;
    height: 16px;
}

/* Setting Rows (for toggles) */
.es-setting-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 12px 0;
    border-bottom: 1px solid var(--ensemble-card-border, #3a3a3a);
}

.es-setting-row:last-child {
    border-bottom: none;
}

.es-setting-info {
    flex: 1;
    padding-right: 16px;
}

.es-setting-title {
    display: block;
    font-size: 13px;
    font-weight: 500;
    color: var(--ensemble-text, #e0e0e0);
    margin-bottom: 2px;
}

.es-setting-desc {
    display: block;
    font-size: 12px;
    color: var(--ensemble-text-secondary, #9ca3af);
    line-height: 1.4;
}

/* Shortcode Info */
.es-shortcode-info {
    display: grid;
    gap: 16px;
}

.es-shortcode-block {
    padding: 16px;
    background: var(--ensemble-hover, #2a2a2a);
    border-radius: 8px;
}

.es-shortcode-block h4 {
    margin: 0 0 10px 0;
    font-size: 14px;
    font-weight: 600;
}

.es-shortcode-block code {
    display: inline-block;
    padding: 4px 8px;
    background: #1a1a1a;
    border: 1px solid #3a3a3a;
    border-radius: 4px;
    font-size: 12px;
    margin: 2px 0;
}

.es-shortcode-block p {
    margin: 8px 0 0;
    font-size: 13px;
    color: var(--ensemble-text-secondary, #9ca3af);
}

/* Responsive */
@media (max-width: 600px) {
    .es-field-row {
        flex-direction: column;
    }
    
    .es-style-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Toggle provider-specific settings
    $('input[name="provider"]').on('change', function() {
        var provider = $(this).val();
        
        // Update active class
        $('.es-provider-option').removeClass('active');
        $(this).closest('.es-provider-option').addClass('active');
        
        // Show/hide settings
        if (provider === 'google') {
            $('.es-google-settings').slideDown(200);
            $('.es-osm-settings').slideUp(200);
        } else {
            $('.es-google-settings').slideUp(200);
            $('.es-osm-settings').slideDown(200);
        }
    });
    
    // Style card selection
    $('.es-style-card input').on('change', function() {
        $(this).closest('.es-style-grid').find('.es-style-card').removeClass('active');
        $(this).closest('.es-style-card').addClass('active');
    });
});
</script>
