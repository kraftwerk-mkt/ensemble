<?php
/**
 * Template: Modern/Noir Elegance Event Card
 * Full-width dark mode, content overlay on image
 * 
 * Available variables from shortcode:
 * - $event (array) - Event data
 * - $shortcode_atts (array) - Display settings
 * - $card_data_attributes (string) - Pre-built data attributes for filtering
 * 
 * IMPORTANT: Always use "ensemble-event-card" as base class for filtering to work!
 * 
 * @package Ensemble
 * @version 3.2.1
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

$timestamp = $start_date ? strtotime($start_date) : false;
$is_special = function_exists('ensemble_is_special_status') 
    ? ensemble_is_special_status($status) 
    : in_array($status, array('cancelled', 'postponed', 'soldout'));

// Display settings helpers
$show_image = !function_exists('ensemble_show_in_card') || ensemble_show_in_card('image');
$show_title = !function_exists('ensemble_show_in_card') || ensemble_show_in_card('title');
$show_date = !function_exists('ensemble_show_in_card') || ensemble_show_in_card('date');
$show_time = !function_exists('ensemble_show_in_card') || ensemble_show_in_card('time');
$show_location = !function_exists('ensemble_show_in_card') || ensemble_show_in_card('location');
$show_price = !function_exists('ensemble_show_in_card') || ensemble_show_in_card('price');
$show_status = !function_exists('ensemble_show_in_card') || ensemble_show_in_card('status');
?>

<article class="ensemble-event-card es-noir-card <?php echo ($is_special && $show_status) ? 'es-status-' . esc_attr($status) : ''; ?>" <?php echo $card_data_attributes; ?>>
    <a href="<?php echo esc_url($permalink); ?>" class="es-noir-card-inner">
        
        <?php if ($show_image): ?>
        <div class="es-noir-card-media">
            <?php if ($featured_image): ?>
                <img src="<?php echo esc_url($featured_image); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy">
            <?php else: ?>
                <div class="es-noir-placeholder">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                </div>
            <?php endif; ?>
            <div class="es-noir-card-gradient"></div>
            
            <?php if ($is_special && $show_status): ?>
                <span class="es-noir-status"><?php echo esc_html(function_exists('ensemble_get_status_label') ? ensemble_get_status_label($status, true) : $status); ?></span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <div class="es-noir-card-content">
            <?php if ($timestamp && $show_date): ?>
            <time class="es-noir-date" datetime="<?php echo date('Y-m-d', $timestamp); ?>">
                <span class="es-noir-date-weekday"><?php echo date_i18n('l', $timestamp); ?></span>
                <span class="es-noir-date-full"><?php echo date_i18n('j. F Y', $timestamp); ?></span>
            </time>
            <?php endif; ?>
            
            <?php if ($show_title): ?>
            <h3 class="es-noir-title"><?php echo esc_html($title); ?></h3>
            <?php endif; ?>
            
            <?php if (($start_time && $show_time) || ($location && $show_location)): ?>
            <div class="es-noir-meta">
                <?php if ($start_time && $show_time): ?>
                    <span><?php echo esc_html($start_time); ?></span>
                <?php endif; ?>
                <?php if ($start_time && $show_time && $location && $show_location): ?>
                    <span class="es-noir-meta-sep">—</span>
                <?php endif; ?>
                <?php if ($location && $show_location): ?>
                    <span><?php echo esc_html($location); ?></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($price && $show_price): ?>
                <span class="es-noir-price"><?php echo esc_html($price); ?></span>
            <?php endif; ?>
        </div>
        
        <span class="es-noir-arrow">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                <path d="M5 12h14M12 5l7 7-7 7"/>
            </svg>
        </span>
        
    </a>
</article>
