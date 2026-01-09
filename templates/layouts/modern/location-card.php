<?php
/**
 * Template: Location Card - Noir Elegance
 * Full-width dark mode, content overlay on image
 * 
 * @package Ensemble
 * @version 3.1.0
 */

if (!defined('ABSPATH')) exit;

$location_id = $location['id'];
$title = $location['title'];
$permalink = $location['permalink'];
$featured_image = $location['featured_image'];
$city = $location['city'];
$capacity = $location['capacity'];
$event_count = $location['event_count'];
?>

<article class="es-noir-card es-noir-card-location">
    <a href="<?php echo esc_url($permalink); ?>" class="es-noir-card-inner">
        
        <div class="es-noir-card-media">
            <?php if ($featured_image): ?>
                <img src="<?php echo esc_url($featured_image); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy">
            <?php else: ?>
                <div class="es-noir-placeholder">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
                    </svg>
                </div>
            <?php endif; ?>
            <div class="es-noir-card-gradient"></div>
            
            <?php if ($capacity): ?>
                <span class="es-noir-badge"><?php echo number_format_i18n($capacity); ?></span>
            <?php endif; ?>
        </div>
        
        <div class="es-noir-card-content">
            <?php if ($city): ?>
                <span class="es-noir-location"><?php echo esc_html($city); ?></span>
            <?php endif; ?>
            
            <h3 class="es-noir-title"><?php echo esc_html($title); ?></h3>
            
            <?php if ($event_count): ?>
            <div class="es-noir-meta">
                <span><?php printf(_n('%s Event', '%s Events', $event_count, 'ensemble'), number_format_i18n($event_count)); ?></span>
            </div>
            <?php endif; ?>
        </div>
        
        <span class="es-noir-arrow">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                <path d="M5 12h14M12 5l7 7-7 7"/>
            </svg>
        </span>
        
    </a>
</article>
