<?php
/**
 * Template: Pure Event Card
 * Minimal event card with clean typography
 * 
 * @package Ensemble
 * @version 2.0.0
 */

if (!defined('ABSPATH')) exit;

// Event data
$event_id = isset($event['id']) ? $event['id'] : 0;
$title = isset($event['title']) ? $event['title'] : '';
$permalink = isset($event['permalink']) ? $event['permalink'] : '#';
$featured_image = isset($event['featured_image']) ? $event['featured_image'] : '';
$start_date = isset($event['start_date']) ? $event['start_date'] : '';
$start_time = isset($event['start_time']) ? $event['start_time'] : '';
$location = isset($event['location']) ? $event['location'] : '';
$status = isset($event['status']) ? $event['status'] : '';
$price = isset($event['price']) ? $event['price'] : '';

// Categories/Genres
$categories = isset($event['categories']) ? $event['categories'] : array();
if (empty($categories) && $event_id) {
    $categories = get_the_terms($event_id, 'ensemble_category');
}
$genre_names = array();
if ($categories && !is_wp_error($categories)) {
    $genre_names = wp_list_pluck($categories, 'name');
}

// Format date
$formatted_date = '';
if ($start_date) {
    $timestamp = strtotime($start_date);
    $formatted_date = date_i18n('j M Y', $timestamp);
}

// Status check
$is_special = in_array($status, array('cancelled', 'postponed', 'soldout'));
$status_label = '';
if ($status === 'cancelled') $status_label = __('Cancelled', 'ensemble');
if ($status === 'postponed') $status_label = __('Postponed', 'ensemble');
if ($status === 'soldout') $status_label = __('Sold Out', 'ensemble');
?>

<article class="es-pure-card es-pure-event-card">
    <a href="<?php echo esc_url($permalink); ?>" class="es-pure-card-link">
        
        <!-- Image -->
        <div class="es-pure-card-image">
            <?php if ($featured_image): ?>
                <img src="<?php echo esc_url($featured_image); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy">
            <?php else: ?>
                <div class="es-pure-placeholder">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                </div>
            <?php endif; ?>
            
            <?php if ($is_special && $status_label): ?>
            <span class="es-pure-card-badge"><?php echo esc_html($status_label); ?></span>
            <?php endif; ?>
        </div>
        
        <!-- Body -->
        <div class="es-pure-card-body">
            <?php if ($formatted_date): ?>
            <div class="es-pure-card-date"><?php echo esc_html($formatted_date); ?></div>
            <?php endif; ?>
            
            <h3 class="es-pure-card-title"><?php echo esc_html($title); ?></h3>
            
            <?php if ($location || $start_time || !empty($genre_names)): ?>
            <div class="es-pure-card-meta">
                <?php 
                $meta_parts = array();
                if ($start_time) $meta_parts[] = $start_time;
                if ($location) $meta_parts[] = $location;
                if (!empty($genre_names)) $meta_parts[] = implode(', ', array_slice($genre_names, 0, 2));
                echo esc_html(implode(' · ', $meta_parts));
                ?>
            </div>
            <?php endif; ?>
        </div>
        
    </a>
</article>
