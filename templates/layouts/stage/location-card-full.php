<?php
/**
 * Template: Stage Location Card - Full
 * Card with image, name, city, and button
 * Used for layout="cards"
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
$permalink = isset($location['permalink']) ? $location['permalink'] : '#';
$link_target = isset($location['link_target']) ? $location['link_target'] : '_self';

// Shortcode attributes
$show_link = isset($shortcode_atts['show_link']) ? filter_var($shortcode_atts['show_link'], FILTER_VALIDATE_BOOLEAN) : true;
$link_text = isset($shortcode_atts['link_text']) ? $shortcode_atts['link_text'] : __('View Location', 'ensemble');
?>

<article class="ensemble-location-card es-stage-card es-stage-card--full">
    
    <!-- Image -->
    <a href="<?php echo esc_url($permalink); ?>" class="es-stage-card-image-link" <?php echo $link_target === '_blank' ? 'target="_blank" rel="noopener"' : ''; ?>>
        <div class="es-stage-card-image">
            <?php if ($image): ?>
                <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($name); ?>" loading="lazy">
            <?php else: ?>
                <div class="es-stage-placeholder-bg es-stage-placeholder-location">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                        <circle cx="12" cy="10" r="3"/>
                    </svg>
                </div>
            <?php endif; ?>
        </div>
    </a>
    
    <!-- Content -->
    <div class="es-stage-card-body">
        
        <!-- Name -->
        <h3 class="es-stage-card-title">
            <a href="<?php echo esc_url($permalink); ?>" <?php echo $link_target === '_blank' ? 'target="_blank" rel="noopener"' : ''; ?>>
                <?php echo esc_html($name); ?>
            </a>
        </h3>
        
        <!-- City (klein) -->
        <?php if ($city): ?>
        <span class="es-stage-card-meta"><?php echo esc_html($city); ?></span>
        <?php endif; ?>
        
        <!-- Button -->
        <?php if ($show_link): ?>
        <a href="<?php echo esc_url($permalink); ?>" 
           class="es-stage-btn es-stage-btn-outline"
           <?php echo $link_target === '_blank' ? 'target="_blank" rel="noopener"' : ''; ?>>
            <?php echo esc_html($link_text); ?>
        </a>
        <?php endif; ?>
        
    </div>
    
</article>
