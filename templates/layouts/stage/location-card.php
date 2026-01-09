<?php
/**
 * Template: Stage Location Card (Grid/Slider)
 * Image only, name + next event appears on hover
 * 
 * @package Ensemble
 * @version 2.0.0
 */

if (!defined('ABSPATH')) exit;

// Support both $location array and direct variables
$location_id = isset($location['id']) ? $location['id'] : ($location_id ?? 0);
$name = isset($location['name']) ? $location['name'] : (isset($location['title']) ? $location['title'] : '');
$image = isset($location['image']) ? $location['image'] : (isset($location['featured_image']) ? $location['featured_image'] : '');
$permalink = isset($location['permalink']) ? $location['permalink'] : '#';
$link_target = isset($location['link_target']) ? $location['link_target'] : '_self';

// Next event data (if available)
$next_event = isset($location['next_event']) ? $location['next_event'] : null;
$next_event_title = '';
$next_event_date = '';

if ($next_event) {
    $next_event_title = is_array($next_event) ? ($next_event['title'] ?? '') : '';
    $next_event_date = is_array($next_event) ? ($next_event['date_formatted'] ?? '') : '';
}
?>

<article class="ensemble-location-card es-stage-card es-stage-card--minimal">
    <a href="<?php echo esc_url($permalink); ?>" class="es-stage-card-link" <?php echo $link_target === '_blank' ? 'target="_blank" rel="noopener"' : ''; ?>>
        
        <!-- Background Image -->
        <div class="es-stage-card-bg">
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
            <div class="es-stage-card-gradient es-stage-gradient-hover"></div>
        </div>
        
        <!-- Content (hidden, appears on hover) -->
        <div class="es-stage-card-overlay es-stage-overlay-hover">
            <h3 class="es-stage-card-name"><?php echo esc_html($name); ?></h3>
            
            <?php if ($next_event_title || $next_event_date): ?>
            <div class="es-stage-card-next-event">
                <?php if ($next_event_date): ?>
                <span class="es-stage-next-date"><?php echo esc_html($next_event_date); ?></span>
                <?php endif; ?>
                <?php if ($next_event_title): ?>
                <span class="es-stage-next-title"><?php echo esc_html($next_event_title); ?></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        
    </a>
</article>
