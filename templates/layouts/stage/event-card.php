<?php
/**
 * Template: Stage Event Card
 * Fullbleed image with date + title visible, artists appear on hover
 * 
 * @package Ensemble
 * @version 2.0.0
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
$status = $event['status'] ?? 'publish';
$artists = isset($event['artists']) ? $event['artists'] : array();

$timestamp = $start_date ? strtotime($start_date) : false;
$is_special = function_exists('ensemble_is_special_status') 
    ? ensemble_is_special_status($status) 
    : in_array($status, array('cancelled', 'postponed', 'soldout'));

// Display settings
$show_image = !function_exists('ensemble_show_in_card') || ensemble_show_in_card('image');
$show_title = !function_exists('ensemble_show_in_card') || ensemble_show_in_card('title');
$show_date = !function_exists('ensemble_show_in_card') || ensemble_show_in_card('date');
$show_status = !function_exists('ensemble_show_in_card') || ensemble_show_in_card('status');
?>

<article class="ensemble-event-card es-stage-card es-stage-card--event <?php echo ($is_special && $show_status) ? 'es-status-' . esc_attr($status) : ''; ?>" <?php echo $card_data_attributes; ?>>
    <a href="<?php echo esc_url($permalink); ?>" class="es-stage-card-link">
        
        <!-- Background Image -->
        <div class="es-stage-card-bg">
            <?php if ($featured_image): ?>
                <img src="<?php echo esc_url($featured_image); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy">
            <?php else: ?>
                <div class="es-stage-placeholder-bg"></div>
            <?php endif; ?>
            <div class="es-stage-card-gradient"></div>
        </div>
        
        <!-- Status Badge -->
        <?php if ($is_special && $show_status): ?>
        <span class="es-stage-card-badge es-stage-badge-status"><?php echo esc_html(function_exists('ensemble_get_status_label') ? ensemble_get_status_label($status, true) : ucfirst($status)); ?></span>
        <?php endif; ?>
        
        <!-- Content Overlay -->
        <div class="es-stage-card-overlay">
            
            <!-- Date Badge (always visible) -->
            <?php if ($timestamp && $show_date): ?>
            <div class="es-stage-card-date-badge">
                <span class="es-stage-card-day"><?php echo date_i18n('j', $timestamp); ?></span>
                <span class="es-stage-card-month"><?php echo date_i18n('M', $timestamp); ?></span>
            </div>
            <?php endif; ?>
            
            <!-- Title (always visible) -->
            <?php if ($show_title): ?>
            <h3 class="es-stage-card-title"><?php echo esc_html($title); ?></h3>
            <?php endif; ?>
            
            <!-- Artists (hidden, appears on hover) -->
            <?php if (!empty($artists)): ?>
            <div class="es-stage-card-artists">
                <?php 
                $artist_names = array_slice(array_map(function($a) {
                    return is_array($a) ? ($a['name'] ?? $a['title'] ?? '') : $a;
                }, $artists), 0, 3);
                echo esc_html(implode(' · ', array_filter($artist_names)));
                if (count($artists) > 3) {
                    echo ' <span class="es-stage-more">+' . (count($artists) - 3) . '</span>';
                }
                ?>
            </div>
            <?php endif; ?>
            
        </div>
        
    </a>
</article>
