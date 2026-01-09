<?php
/**
 * Template: Kongress Event Card
 * 
 * @package Ensemble
 * @version 1.0.0
 */

if (!defined('ABSPATH')) exit;

// Get event data
$event_id = isset($event_id) ? $event_id : get_the_ID();
$title = get_the_title($event_id);
$permalink = get_permalink($event_id);
$image = get_the_post_thumbnail_url($event_id, 'medium_large');
$excerpt = get_the_excerpt($event_id);

// Date & Location
$event_date = ensemble_get_field('event_date', $event_id);
$formatted_date = $event_date ? date_i18n('j. F Y', strtotime($event_date)) : '';

$location_id = ensemble_get_field('event_location', $event_id);
$location_name = '';
if ($location_id) {
    $location_post = get_post($location_id);
    if ($location_post) {
        $location_name = $location_post->post_title;
    }
}

// Badge
$badge_label = '';
$badge_raw = get_post_meta($event_id, 'event_badge', true);
if ($badge_raw && function_exists('ensemble_get_badge_label')) {
    $badge_label = ensemble_get_badge_label($badge_raw, $event_id);
}

// Categories
$categories = get_the_terms($event_id, 'ensemble_category');
?>

<a href="<?php echo esc_url($permalink); ?>" class="es-kongress-event-card">
    <div class="es-kongress-event-image">
        <?php if ($image): ?>
        <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy">
        <?php else: ?>
        <div style="width: 100%; height: 100%; background: var(--ensemble-placeholder-bg); display: flex; align-items: center; justify-content: center;">
            <svg viewBox="0 0 24 24" fill="none" stroke="var(--ensemble-placeholder-icon)" stroke-width="1" style="width: 48px; height: 48px;">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                <line x1="16" y1="2" x2="16" y2="6"/>
                <line x1="8" y1="2" x2="8" y2="6"/>
                <line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
        </div>
        <?php endif; ?>
        
        <?php if ($badge_label): ?>
        <span class="es-kongress-event-badge"><?php echo esc_html($badge_label); ?></span>
        <?php endif; ?>
    </div>
    
    <div class="es-kongress-event-content">
        <?php if ($categories && !is_wp_error($categories)): ?>
        <div class="es-kongress-event-category">
            <?php echo esc_html($categories[0]->name); ?>
        </div>
        <?php endif; ?>
        
        <h3 class="es-kongress-event-title"><?php echo esc_html($title); ?></h3>
        
        <div class="es-kongress-event-meta">
            <?php if ($formatted_date): ?>
            <span class="es-kongress-event-meta-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width: 14px; height: 14px;">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
                <?php echo esc_html($formatted_date); ?>
            </span>
            <?php endif; ?>
            
            <?php if ($location_name): ?>
            <span class="es-kongress-event-meta-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width: 14px; height: 14px;">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                    <circle cx="12" cy="10" r="3"/>
                </svg>
                <?php echo esc_html($location_name); ?>
            </span>
            <?php endif; ?>
        </div>
    </div>
</a>

<style>
.es-kongress-event-card {
    display: block;
    background: var(--ensemble-card-bg);
    border: var(--ensemble-card-border-width) solid var(--ensemble-card-border);
    border-radius: var(--ensemble-card-radius);
    overflow: hidden;
    box-shadow: var(--ensemble-card-shadow);
    transition: all 0.3s ease;
    text-decoration: none;
    color: inherit;
}

.es-kongress-event-card:hover {
    transform: var(--ensemble-card-hover-transform);
    box-shadow: var(--ensemble-card-hover-shadow);
}

.es-kongress-event-image {
    position: relative;
    height: var(--ensemble-card-image-height);
    overflow: hidden;
}

.es-kongress-event-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.es-kongress-event-card:hover .es-kongress-event-image img {
    transform: scale(1.05);
}

.es-kongress-event-badge {
    position: absolute;
    top: 12px;
    left: 12px;
    padding: 4px 12px;
    background: var(--ensemble-secondary);
    color: #fff;
    font-size: var(--ensemble-xs-size);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-radius: 4px;
}

.es-kongress-event-content {
    padding: var(--ensemble-card-padding);
}

.es-kongress-event-category {
    font-size: var(--ensemble-xs-size);
    color: var(--ensemble-secondary);
    text-transform: uppercase;
    letter-spacing: 1px;
    font-weight: 600;
    margin-bottom: 8px;
}

.es-kongress-event-title {
    font-family: var(--ensemble-font-heading);
    font-size: var(--ensemble-lg-size);
    font-weight: var(--ensemble-heading-weight);
    color: var(--ensemble-text);
    margin: 0 0 12px 0;
    line-height: var(--ensemble-line-height-heading);
}

.es-kongress-event-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    font-size: var(--ensemble-small-size);
    color: var(--ensemble-text-secondary);
}

.es-kongress-event-meta-item {
    display: flex;
    align-items: center;
    gap: 6px;
}

.es-kongress-event-meta-item svg {
    color: var(--ensemble-primary);
}
</style>
