<?php
/**
 * Template: Simple Club Event Card
 * Fullscreen overlay style with gradient, hover reveals artists
 * Shows location under title, larger text sizes
 * 
 * Available variables from shortcode:
 * - $event (array) - Event data including 'artists' array
 * - $shortcode_atts (array) - Display settings
 * - $card_data_attributes (string) - Pre-built data attributes for filtering
 * 
 * IMPORTANT: Always use "ensemble-event-card" as base class for filtering to work!
 * 
 * @package Ensemble
 * @version 1.0.0
 */

if (!defined('ABSPATH')) exit;

// Event data
$event_id = $event['id'];
$title = $event['title'];
$permalink = $event['permalink'];
$featured_image = $event['featured_image'];
$start_date = $event['start_date'];
$start_time = $event['start_time'];
$location = $event['location'];
$price = $event['price'];
$status = $event['status'] ?? 'publish';
$artists = isset($event['artists']) ? $event['artists'] : array();

$timestamp = $start_date ? strtotime($start_date) : false;
$is_special = function_exists('ensemble_is_special_status') 
    ? ensemble_is_special_status($status) 
    : in_array($status, array('cancelled', 'postponed', 'soldout'));

// Display settings helpers
$show_image = !function_exists('ensemble_show_in_card') || ensemble_show_in_card('image');
$show_title = !function_exists('ensemble_show_in_card') || ensemble_show_in_card('title');
$show_date = !function_exists('ensemble_show_in_card') || ensemble_show_in_card('date');
$show_status = !function_exists('ensemble_show_in_card') || ensemble_show_in_card('status');

// Get location name
$location_name = '';
if (!empty($location)) {
    if (is_array($location)) {
        $location_name = !empty($location['display_name']) ? $location['display_name'] : ($location['name'] ?? '');
    } elseif (is_string($location)) {
        $location_name = $location;
    }
}
?>

<article class="ensemble-event-card es-simpleclub-card es-simpleclub-card--overlay <?php echo ($is_special && $show_status) ? 'es-status-' . esc_attr($status) : ''; ?>" <?php echo $card_data_attributes; ?>>
    <a href="<?php echo esc_url($permalink); ?>" class="es-simpleclub-card-link">
        
        <!-- Background Image -->
        <div class="es-simpleclub-card-bg">
            <?php if ($featured_image): ?>
                <img src="<?php echo esc_url($featured_image); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy">
            <?php else: ?>
                <div class="es-simpleclub-placeholder-bg"></div>
            <?php endif; ?>
            <div class="es-simpleclub-card-gradient"></div>
        </div>
        
        <!-- Status Badge -->
        <?php if ($is_special && $show_status): ?>
        <span class="es-simpleclub-card-status"><?php echo esc_html(function_exists('ensemble_get_status_label') ? ensemble_get_status_label($status, true) : ucfirst($status)); ?></span>
        <?php endif; ?>
        
        <!-- Content Overlay -->
        <div class="es-simpleclub-card-overlay">
            
            <!-- Date (visible by default, hidden on hover) -->
            <?php if ($timestamp && $show_date): ?>
            <div class="es-simpleclub-card-date">
                <span class="es-simpleclub-card-day"><?php echo date_i18n('j', $timestamp); ?></span>
                <span class="es-simpleclub-card-month"><?php echo date_i18n('M', $timestamp); ?></span>
            </div>
            <?php endif; ?>
            
            <!-- Artists (hidden by default, visible on hover) - LARGER -->
            <?php if (!empty($artists)): ?>
            <div class="es-simpleclub-card-artists">
                <?php 
                $artist_display = array_slice($artists, 0, 3); // Max 3 artists
                echo esc_html(implode(' · ', $artist_display));
                if (count($artists) > 3) {
                    echo ' <span class="es-more">+' . (count($artists) - 3) . '</span>';
                }
                ?>
            </div>
            <?php endif; ?>
            
            <!-- Title - LARGER -->
            <?php if ($show_title): ?>
            <h3 class="es-simpleclub-card-title"><?php echo esc_html($title); ?></h3>
            <?php endif; ?>
            
            <!-- Location - SAME SIZE as title -->
            <?php if ($location_name): ?>
            <div class="es-simpleclub-card-location">
                <span class="es-simpleclub-card-location-name"><?php echo esc_html($location_name); ?></span>
            </div>
            <?php endif; ?>
            
        </div>
        
    </a>
</article>
