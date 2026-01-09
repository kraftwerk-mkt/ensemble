<?php
/**
 * Template: Kongress Location Card
 * 
 * @package Ensemble
 * @version 1.0.0
 */

if (!defined('ABSPATH')) exit;

$location_id = isset($location_id) ? $location_id : get_the_ID();
$name = get_the_title($location_id);
$permalink = get_permalink($location_id);
$image = get_the_post_thumbnail_url($location_id, 'medium');
$address = get_post_meta($location_id, 'location_address', true);
$city = get_post_meta($location_id, 'location_city', true);

// Location types
$location_types = get_the_terms($location_id, 'ensemble_location_type');
?>

<a href="<?php echo esc_url($permalink); ?>" class="es-kongress-location-card">
    <div class="es-kongress-location-card-image">
        <?php if ($image): ?>
        <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($name); ?>" loading="lazy">
        <?php else: ?>
        <div style="width: 100%; height: 100%; background: var(--ensemble-placeholder-bg); display: flex; align-items: center; justify-content: center;">
            <svg viewBox="0 0 24 24" fill="none" stroke="var(--ensemble-placeholder-icon)" stroke-width="1" style="width: 48px; height: 48px;">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                <circle cx="12" cy="10" r="3"/>
            </svg>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="es-kongress-location-card-content">
        <?php if ($location_types && !is_wp_error($location_types)): ?>
        <div class="es-kongress-location-type">
            <?php echo esc_html($location_types[0]->name); ?>
        </div>
        <?php endif; ?>
        
        <h3 class="es-kongress-location-name"><?php echo esc_html($name); ?></h3>
        
        <?php if ($address || $city): ?>
        <div class="es-kongress-location-address">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width: 14px; height: 14px; flex-shrink: 0;">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                <circle cx="12" cy="10" r="3"/>
            </svg>
            <span>
                <?php 
                if ($address) echo esc_html($address);
                if ($address && $city) echo ', ';
                if ($city) echo esc_html($city);
                ?>
            </span>
        </div>
        <?php endif; ?>
    </div>
</a>

<style>
.es-kongress-location-card {
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

.es-kongress-location-card:hover {
    transform: var(--ensemble-card-hover-transform);
    box-shadow: var(--ensemble-card-hover-shadow);
}

.es-kongress-location-card-image {
    height: 180px;
    overflow: hidden;
}

.es-kongress-location-card-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.es-kongress-location-card:hover .es-kongress-location-card-image img {
    transform: scale(1.05);
}

.es-kongress-location-card-content {
    padding: var(--ensemble-card-padding);
}

.es-kongress-location-type {
    font-size: var(--ensemble-xs-size);
    color: var(--ensemble-secondary);
    text-transform: uppercase;
    letter-spacing: 1px;
    font-weight: 600;
    margin-bottom: 8px;
}

.es-kongress-location-name {
    font-family: var(--ensemble-font-heading);
    font-size: var(--ensemble-lg-size);
    font-weight: var(--ensemble-heading-weight);
    color: var(--ensemble-text);
    margin: 0 0 12px 0;
}

.es-kongress-location-address {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    font-size: var(--ensemble-small-size);
    color: var(--ensemble-text-secondary);
    line-height: 1.4;
}

.es-kongress-location-address svg {
    margin-top: 2px;
    color: var(--ensemble-primary);
}
</style>
