<?php
/**
 * Template: Lovepop Location Card
 * Fullscreen overlay style - Name visible by default, City on hover
 * Uses same CSS classes as Event Card for consistent hover behavior
 * 
 * @package Ensemble
 * @version 2.0.0
 */

if (!defined('ABSPATH')) exit;

// Support both $location array and direct variables
$location_id = isset($location['id']) ? $location['id'] : ($location_id ?? 0);
$name = isset($location['name']) ? $location['name'] : (isset($location['title']) ? $location['title'] : '');
$image = isset($location['image']) ? $location['image'] : (isset($location['featured_image']) ? $location['featured_image'] : '');
$city = isset($location['city']) ? $location['city'] : '';
$address = isset($location['address']) ? $location['address'] : '';
$permalink = isset($location['permalink']) ? $location['permalink'] : '#';
$link_target = isset($location['link_target']) ? $location['link_target'] : '_self';
?>

<article class="ensemble-location-card es-lovepop-card es-lovepop-card--overlay es-lovepop-location-card">
    <a href="<?php echo esc_url($permalink); ?>" class="es-lovepop-card-link" <?php echo $link_target === '_blank' ? 'target="_blank" rel="noopener"' : ''; ?>>
        
        <!-- Background Image -->
        <div class="es-lovepop-card-bg">
            <?php if ($image): ?>
                <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($name); ?>" loading="lazy">
            <?php else: ?>
                <div class="es-lovepop-placeholder-bg es-lovepop-placeholder-location">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                        <circle cx="12" cy="10" r="3"/>
                    </svg>
                </div>
            <?php endif; ?>
            <div class="es-lovepop-card-gradient"></div>
        </div>
        
        <!-- Content Overlay -->
        <div class="es-lovepop-card-overlay es-lovepop-location-overlay">
            
            <!-- Name (visible by default, fades on hover) - uses card-date class for animation -->
            <div class="es-lovepop-card-date es-lovepop-location-name-wrap">
                <span class="es-lovepop-location-name"><?php echo esc_html($name); ?></span>
            </div>
            
            <!-- City (hidden by default, appears on hover) - uses card-artists class for animation -->
            <?php if ($city): ?>
            <div class="es-lovepop-card-artists es-lovepop-location-city-wrap">
                <?php echo esc_html($city); ?>
            </div>
            <?php endif; ?>
            
        </div>
        
    </a>
</article>
