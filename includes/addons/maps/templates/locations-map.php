<?php
/**
 * Maps Pro - Locations Overview Map Template
 * 
 * Displays all locations on an interactive map
 * 
 * @package Ensemble
 * @subpackage Addons/Maps Pro
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Ensure marker_thumbnails has a default value
$marker_thumbnails = isset($marker_thumbnails) ? $marker_thumbnails : true;

// Encode locations for JavaScript
$locations_json = wp_json_encode($locations);
?>
<div id="<?php echo esc_attr($map_id); ?>" 
     class="es-locations-map-wrapper <?php echo esc_attr($extra_class); ?>"
     data-clustering="<?php echo $clustering ? 'true' : 'false'; ?>"
     data-fullscreen="<?php echo $fullscreen ? 'true' : 'false'; ?>"
     data-geolocation="<?php echo $geolocation ? 'true' : 'false'; ?>"
     data-marker-thumbnails="<?php echo !empty($marker_thumbnails) ? 'true' : 'false'; ?>"
     data-style="<?php echo esc_attr($style); ?>">
    
    <?php if ($show_filter && (!empty($filters['cities']) || !empty($filters['categories']))): ?>
    <!-- Filter Bar -->
    <div class="es-map-toolbar">
        <div class="es-map-filters">
            <?php if (!empty($filters['cities'])): ?>
            <div class="es-map-filter-group">
                <select class="es-map-filter" data-filter="city">
                    <option value=""><?php _e('All Cities', 'ensemble'); ?></option>
                    <?php foreach ($filters['cities'] as $city): ?>
                        <option value="<?php echo esc_attr($city); ?>"><?php echo esc_html($city); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($filters['categories'])): ?>
            <div class="es-map-filter-group">
                <select class="es-map-filter" data-filter="category">
                    <option value=""><?php _e('Alle Kategorien', 'ensemble'); ?></option>
                    <?php foreach ($filters['categories'] as $cat): ?>
                        <option value="<?php echo esc_attr($cat); ?>"><?php echo esc_html($cat); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if ($search): ?>
        <div class="es-map-search">
            <input type="text" 
                   class="es-map-search-input" 
                   placeholder="<?php esc_attr_e('Location suchen...', 'ensemble'); ?>">
            <button type="button" class="es-map-search-clear" aria-label="<?php esc_attr_e('Clear search', 'ensemble'); ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 6L6 18M6 6l12 12"/>
                </svg>
            </button>
            <div class="es-map-search-results"></div>
        </div>
        <?php endif; ?>
        
        <div class="es-map-actions">
            <button type="button" class="es-map-btn es-map-btn-reset" title="<?php esc_attr_e('Show all', 'ensemble'); ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M12 8v8M8 12h8"/>
                </svg>
                <span><?php _e('Alle', 'ensemble'); ?></span>
            </button>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Map Container -->
    <div class="es-map-container" style="height: <?php echo esc_attr($height); ?>">
        <div class="es-map-canvas" id="<?php echo esc_attr($map_id); ?>-canvas"></div>
        
        <!-- Loading Overlay -->
        <div class="es-map-loading">
            <div class="es-map-loading-spinner"></div>
            <span><?php _e('Karte wird geladen...', 'ensemble'); ?></span>
        </div>
    </div>
    
    <!-- Location Info Sidebar (for selected location) -->
    <div class="es-map-sidebar" id="<?php echo esc_attr($map_id); ?>-sidebar">
        <button type="button" class="es-map-sidebar-close" aria-label="<?php esc_attr_e('Close', 'ensemble'); ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 6L6 18M6 6l12 12"/>
            </svg>
        </button>
        <div class="es-map-sidebar-content">
            <!-- Filled dynamically -->
        </div>
    </div>
    
    <!-- Stats Bar -->
    <div class="es-map-stats">
        <span class="es-map-stats-count">
            <?php printf(
                _n('%d Location', '%d Locations', count($locations), 'ensemble'),
                count($locations)
            ); ?>
        </span>
        <span class="es-map-stats-filtered" style="display: none;">
            <?php _e('gefiltert', 'ensemble'); ?>
        </span>
    </div>
</div>

<!-- Location Popup Template -->
<template id="<?php echo esc_attr($map_id); ?>-popup-template">
    <div class="es-map-popup">
        <div class="es-map-popup-image" data-image></div>
        <div class="es-map-popup-content">
            <h4 class="es-map-popup-title" data-title></h4>
            <p class="es-map-popup-address" data-address></p>
            <div class="es-map-popup-events" data-events></div>
            <div class="es-map-popup-actions">
                <a href="#" class="es-map-popup-link" data-url>
                    <?php _e('Location ansehen', 'ensemble'); ?>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M5 12h14M12 5l7 7-7 7"/>
                    </svg>
                </a>
                <button type="button" class="es-map-popup-route" data-route>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                    </svg>
                    <?php _e('Route', 'ensemble'); ?>
                </button>
            </div>
        </div>
    </div>
</template>

<script>
(function() {
    var retryCount = 0;
    var maxRetries = 50;
    
    function initMap() {
        retryCount++;
        
        // Check if main script is loaded
        if (typeof EnsembleMapsPro === 'undefined' || !EnsembleMapsPro.config) {
            if (retryCount < maxRetries) {
                setTimeout(initMap, 200);
            } else {
                console.error('EnsembleMapsPro failed to load');
                showError('Maps script not loaded');
            }
            return;
        }
        
        var provider = EnsembleMapsPro.config.provider || 'osm';
        
        // Check if required library is loaded
        if (provider === 'google') {
            if (typeof google === 'undefined' || typeof google.maps === 'undefined') {
                if (retryCount < maxRetries) {
                    setTimeout(initMap, 200);
                    return;
                } else {
                    showError('Google Maps API not loaded');
                    return;
                }
            }
        } else {
            if (typeof L === 'undefined') {
                if (retryCount < maxRetries) {
                    setTimeout(initMap, 200);
                    return;
                } else {
                    showError('Leaflet not loaded');
                    return;
                }
            }
        }
        
        var mapId = '<?php echo esc_js($map_id); ?>';
        var locations = <?php echo $locations_json; ?>;
        var styleOption = '<?php echo esc_js($style); ?>';
        
        console.log('Ensemble Maps: Init locations map, provider:', provider, 'locations:', locations.length, 'style:', styleOption);
        
        try {
            EnsembleMapsPro.initLocationsMap(mapId, locations, {
                clustering: <?php echo $clustering ? 'true' : 'false'; ?>,
                fullscreen: <?php echo $fullscreen ? 'true' : 'false'; ?>,
                geolocation: <?php echo $geolocation ? 'true' : 'false'; ?>,
                markerThumbnails: <?php echo $marker_thumbnails ? 'true' : 'false'; ?>,
                style: styleOption
            });
        } catch (e) {
            console.error('Map init error:', e);
            showError(e.message);
        }
    }
    
    function showError(msg) {
        var mapId = '<?php echo esc_js($map_id); ?>';
        var loading = document.querySelector('#' + mapId + ' .es-map-loading');
        var canvas = document.getElementById(mapId + '-canvas');
        if (loading) loading.classList.add('hidden');
        if (canvas) canvas.innerHTML = '<p class="es-map-error">' + msg + '</p>';
    }
    
    // Start initialization
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(initMap, 100);
        });
    } else {
        setTimeout(initMap, 100);
    }
})();
</script>
