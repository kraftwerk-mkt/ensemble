<!-- Ensemble Maps - Embedded Map -->
<div class="es-map-container es-single-map" 
     data-lat="<?php echo esc_attr($latitude); ?>" 
     data-lng="<?php echo esc_attr($longitude); ?>"
     data-location-id="<?php echo esc_attr($location_id); ?>"
     data-location-name="<?php echo esc_attr($location_name); ?>">
    
    <div class="es-map-embed" id="es-map-<?php echo esc_attr($location_id); ?>" style="width: 100%; height: 400px;">
        <div class="es-map-loading">
            <div class="es-map-loading-spinner"></div>
            <p><?php _e('Karte wird geladen...', 'ensemble'); ?></p>
        </div>
    </div>
    
    <?php if (!empty($address)): ?>
    <div class="es-map-address">
        <span class="dashicons dashicons-location"></span>
        <span><?php echo esc_html($address); ?></span>
    </div>
    <?php endif; ?>
</div>
