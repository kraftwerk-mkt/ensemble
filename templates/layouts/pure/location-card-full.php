<?php
/**
 * Template: Pure Location Card Full
 * Image + name + address + ghost button
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

<article class="es-pure-card es-pure-location-card es-pure-card-full">
    
    <!-- Image -->
    <a href="<?php echo esc_url($permalink); ?>" class="es-pure-card-image">
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
    </a>
    
    <!-- Body -->
    <div class="es-pure-card-body">
        <h3 class="es-pure-card-name"><?php echo esc_html($name); ?></h3>
        
        <?php if ($city || $address): ?>
        <div class="es-pure-card-meta">
            <?php 
            if ($address) {
                echo esc_html($address);
                if ($city) echo ', ';
            }
            if ($city) echo esc_html($city);
            ?>
        </div>
        <?php endif; ?>
        
        <a href="<?php echo esc_url($permalink); ?>" class="es-pure-btn es-pure-btn-ghost">
            <?php _e('View Location', 'ensemble'); ?>
        </a>
    </div>
    
</article>
