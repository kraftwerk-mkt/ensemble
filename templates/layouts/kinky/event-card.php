<?php
/**
 * Template: Kinky Event Card
 * Sensual dark design with elegant typography
 * 
 * Available variables from shortcode:
 * - $event (array) - Event data
 * - $shortcode_atts (array) - Display settings  
 * - $card_data_attributes (string) - Pre-built data attributes for filtering
 * - $card_index (int) - Position in grid (0-based)
 * 
 * @package Ensemble
 * @version 2.1.0
 */

if (!defined('ABSPATH')) exit;

// Event data
$event_id = $event['id'];
$title = $event['title'];
$permalink = $event['permalink'];
$featured_image = $event['featured_image'];
$start_date = $event['start_date'];
$start_time = $event['start_time'] ?? '';
$location = $event['location'] ?? '';
$price = $event['price'] ?? '';
$categories = $event['categories'] ?? array();
$status = $event['status'] ?? 'publish';

// Display settings
$show_image = !function_exists('ensemble_show_in_card') || ensemble_show_in_card('image');
$show_title = !function_exists('ensemble_show_in_card') || ensemble_show_in_card('title');
$show_date = !function_exists('ensemble_show_in_card') || ensemble_show_in_card('date');
$show_time = !function_exists('ensemble_show_in_card') || ensemble_show_in_card('time');
$show_location = !function_exists('ensemble_show_in_card') || ensemble_show_in_card('location');
$show_category = !function_exists('ensemble_show_in_card') || ensemble_show_in_card('category');
$show_price = !function_exists('ensemble_show_in_card') || ensemble_show_in_card('price');
$show_status = !function_exists('ensemble_show_in_card') || ensemble_show_in_card('status');

// Format date
$timestamp = $start_date ? strtotime($start_date) : false;

// Category / Genre
$category_name = '';
if (!empty($categories) && !is_wp_error($categories)) {
    $category_name = $categories[0]->name;
}

// Status mapping
$is_special = function_exists('ensemble_is_special_status') 
    ? ensemble_is_special_status($status) 
    : in_array($status, array('cancelled', 'postponed', 'soldout'));
$status_label = function_exists('ensemble_get_status_label') ? ensemble_get_status_label($status, true) : ucfirst($status);
?>

<article class="ensemble-event-card es-kinky-card <?php echo ($is_special && $show_status) ? 'es-status-' . esc_attr($status) : ''; ?>" <?php echo $card_data_attributes; ?>>
    <a href="<?php echo esc_url($permalink); ?>" class="es-kinky-link">
        
        <!-- Image with gradient overlay -->
        <?php if ($show_image): ?>
        <div class="es-kinky-image">
            <?php if ($featured_image): ?>
                <img src="<?php echo esc_url($featured_image); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy">
            <?php else: ?>
                <div class="es-kinky-placeholder"></div>
            <?php endif; ?>
            
            <div class="es-kinky-gradient"></div>
            
            <!-- Date Badge (elegant style) -->
            <?php if ($timestamp && $show_date): ?>
            <div class="es-kinky-date-badge">
                <span class="es-kinky-date-day"><?php echo date_i18n('d', $timestamp); ?></span>
                <span class="es-kinky-date-divider"></span>
                <span class="es-kinky-date-month"><?php echo date_i18n('M', $timestamp); ?></span>
            </div>
            <?php endif; ?>
            
            <!-- Status Badge -->
            <?php if ($is_special && $show_status): ?>
            <div class="es-kinky-status es-kinky-status-<?php echo esc_attr($status); ?>">
                <?php echo esc_html($status_label); ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Content -->
        <div class="es-kinky-content">
            
            <?php if ($category_name && $show_category): ?>
            <span class="es-kinky-genre"><?php echo esc_html($category_name); ?></span>
            <?php endif; ?>
            
            <?php if ($show_title): ?>
            <h3 class="es-kinky-title"><?php echo esc_html($title); ?></h3>
            <?php endif; ?>
            
            <?php if (($start_time && $show_time) || ($location && $show_location)): ?>
            <div class="es-kinky-meta">
                <?php if ($start_time && $show_time): ?>
                <span class="es-kinky-time"><?php echo esc_html($start_time); ?></span>
                <?php endif; ?>
                
                <?php if ($start_time && $show_time && $location && $show_location): ?>
                <span class="es-kinky-separator">·</span>
                <?php endif; ?>
                
                <?php if ($location && $show_location): ?>
                <span class="es-kinky-location"><?php echo esc_html($location); ?></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($price && !$is_special && $show_price): ?>
            <span class="es-kinky-price"><?php echo esc_html($price); ?></span>
            <?php endif; ?>
            
        </div>
        
    </a>
</article>
