<!-- Ensemble Maps - Route Button -->
<?php
// Build destination - prefer address over coordinates
$destination = '';
if (!empty($address)) {
    $destination = urlencode($address);
} elseif (!empty($latitude) && !empty($longitude)) {
    $destination = $latitude . ',' . $longitude;
}
?>
<?php if (!empty($destination)): ?>
<div class="es-map-route-button">
    <a href="https://www.google.com/maps/dir/?api=1&destination=<?php echo esc_attr($destination); ?>" 
       target="_blank" 
       rel="noopener noreferrer"
       class="es-button es-button-route">
        <span class="dashicons dashicons-location-alt"></span>
        <span><?php _e('Route planen', 'ensemble'); ?></span>
    </a>
</div>
<?php endif; ?>
