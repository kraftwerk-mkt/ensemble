<?php
/**
 * Template: Club Location Card
 * Dark nightclub style location card
 * Vertical layout like event cards: Image top, info bottom
 * 
 * @package Ensemble
 * @version 2.1.0
 */

if (!defined('ABSPATH')) exit;

$location_id = get_the_ID();
$name = get_the_title();
$permalink = get_permalink();
$featured_image = get_the_post_thumbnail_url($location_id, 'medium_large');
$city = get_post_meta($location_id, '_location_city', true);
$address = get_post_meta($location_id, '_location_address', true);
$description = get_post_meta($location_id, '_location_description', true);
if (!$description) {
    $description = get_the_excerpt();
}

// Get upcoming event count for this location
$upcoming_count = 0;
if (function_exists('ensemble_get_location_events')) {
    $events = ensemble_get_location_events($location_id, array('upcoming' => true, 'limit' => 99));
    $upcoming_count = count($events);
}
?>

<article class="ensemble-event-card es-club-card es-club-location-card">
    <a href="<?php echo esc_url($permalink); ?>" class="es-club-link">
        
        <!-- Image - Full Width, No Padding -->
        <div class="es-club-image">
            <?php if ($featured_image): ?>
                <img src="<?php echo esc_url($featured_image); ?>" alt="<?php echo esc_attr($name); ?>" loading="lazy">
            <?php else: ?>
                <div class="es-club-placeholder">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                        <circle cx="12" cy="10" r="3"/>
                    </svg>
                </div>
            <?php endif; ?>
            
            <!-- Event Count Badge (on image) -->
            <?php if ($upcoming_count > 0): ?>
            <div class="es-club-count-badge">
                <span class="es-club-count-number"><?php echo $upcoming_count; ?></span>
                <span class="es-club-count-label"><?php echo $upcoming_count === 1 ? __('Event', 'ensemble') : __('Events', 'ensemble'); ?></span>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Content - Has Padding -->
        <div class="es-club-content">
            
            <?php if ($city): ?>
            <span class="es-club-genre"><?php echo esc_html($city); ?></span>
            <?php endif; ?>
            
            <h3 class="es-club-title"><?php echo esc_html($name); ?></h3>
            
            <?php if ($address): ?>
            <div class="es-club-meta">
                <span class="es-club-address"><?php echo esc_html($address); ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($description): ?>
            <p class="es-club-excerpt"><?php echo esc_html(wp_trim_words($description, 15)); ?></p>
            <?php endif; ?>
            
        </div>
        
    </a>
</article>
