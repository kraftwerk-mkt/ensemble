<?php
/**
 * Template: Pure Location Card (Minimal)
 * Clean image + name + city
 * 
 * @package Ensemble
 * @version 2.0.0
 */

if (!defined('ABSPATH')) exit;

$location_id = isset($location['id']) ? $location['id'] : 0;
$name = isset($location['name']) ? $location['name'] : (isset($location['title']) ? $location['title'] : '');
$permalink = isset($location['permalink']) ? $location['permalink'] : '#';
$image = isset($location['image']) ? $location['image'] : (isset($location['featured_image']) ? $location['featured_image'] : '');
$city = isset($location['city']) ? $location['city'] : '';
$address = isset($location['address']) ? $location['address'] : '';
?>

<article class="es-pure-card es-pure-location-card">
    <a href="<?php echo esc_url($permalink); ?>" class="es-pure-card-link">
        
        <!-- Image -->
        <div class="es-pure-card-image">
            <?php if ($image): ?>
                <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($name); ?>" loading="lazy">
            <?php else: ?>
                <div class="es-pure-placeholder">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                        <circle cx="12" cy="10" r="3"/>
                    </svg>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Body -->
        <div class="es-pure-card-body">
            <h3 class="es-pure-card-name"><?php echo esc_html($name); ?></h3>
            <?php if ($city): ?>
            <div class="es-pure-card-meta"><?php echo esc_html($city); ?></div>
            <?php endif; ?>
        </div>
        
    </a>
</article>
