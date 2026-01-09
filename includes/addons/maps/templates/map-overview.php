<!-- Ensemble Maps - Overview Map -->
<div class="es-map-overview" 
     data-locations='<?php echo esc_attr(json_encode($locations)); ?>'
     style="height: <?php echo esc_attr($height); ?>">
    
    <div class="es-map-overview-embed" id="es-map-overview">
        <div class="es-map-loading">
            <span class="spinner is-active"></span>
            <p><?php _e('Karte wird geladen...', 'ensemble'); ?></p>
        </div>
    </div>
    
    <div class="es-map-legend">
        <?php foreach ($locations as $location): ?>
        <div class="es-map-legend-item" data-location-id="<?php echo esc_attr($location['id']); ?>">
            <span class="es-map-marker"></span>
            <span class="es-map-location-name"><?php echo esc_html($location['name']); ?></span>
        </div>
        <?php endforeach; ?>
    </div>
</div>
