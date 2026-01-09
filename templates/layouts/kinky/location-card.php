<?php
/**
 * Template: Kinky Location Card
 * Sensual dark design with elegant typography
 * 
 * @package Ensemble
 * @version 2.1.0
 */

if (!defined('ABSPATH')) exit;

// Location data
$location_id = $location['id'] ?? 0;
$name = $location['name'] ?? '';
$permalink = $location['permalink'] ?? '';
$featured_image = $location['featured_image'] ?? '';
$address = $location['address'] ?? '';
$city = $location['city'] ?? '';

// Display settings
$show_image = !function_exists('ensemble_show_in_card') || ensemble_show_in_card('image');
$show_address = !function_exists('ensemble_show_in_card') || ensemble_show_in_card('address');
?>

<article class="ensemble-location-card es-kinky-location-card">
    <a href="<?php echo esc_url($permalink); ?>" class="es-kinky-link">
        
        <?php if ($show_image): ?>
        <div class="es-kinky-location-image">
            <?php if ($featured_image): ?>
                <img src="<?php echo esc_url($featured_image); ?>" alt="<?php echo esc_attr($name); ?>" loading="lazy">
            <?php else: ?>
                <div class="es-kinky-placeholder"></div>
            <?php endif; ?>
            <div class="es-kinky-gradient"></div>
        </div>
        <?php endif; ?>
        
        <div class="es-kinky-location-content">
            <h3 class="es-kinky-location-name"><?php echo esc_html($name); ?></h3>
            
            <?php if (($address || $city) && $show_address): ?>
            <span class="es-kinky-location-address">
                <?php 
                $location_parts = array_filter(array($address, $city));
                echo esc_html(implode(', ', $location_parts));
                ?>
            </span>
            <?php endif; ?>
        </div>
        
    </a>
</article>
