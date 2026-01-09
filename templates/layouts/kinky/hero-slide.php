<?php
/**
 * Template: Kinky Hero Slide
 * For use in sliders and featured sections
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
$status = $event['status'] ?? 'publish';
$excerpt = $event['excerpt'] ?? '';

// Format date
$timestamp = $start_date ? strtotime($start_date) : false;

$is_special = function_exists('ensemble_is_special_status') 
    ? ensemble_is_special_status($status) 
    : in_array($status, array('cancelled', 'postponed', 'soldout'));
?>

<div class="es-kinky-hero-slide" data-event-id="<?php echo esc_attr($event_id); ?>">
    
    <!-- Background Image -->
    <div class="es-kinky-hero-slide-bg">
        <?php if ($featured_image): ?>
            <img src="<?php echo esc_url($featured_image); ?>" alt="<?php echo esc_attr($title); ?>">
        <?php else: ?>
            <div class="es-kinky-placeholder"></div>
        <?php endif; ?>
        <div class="es-kinky-hero-slide-overlay"></div>
    </div>
    
    <!-- Content -->
    <div class="es-kinky-hero-slide-content">
        
        <?php if ($timestamp): ?>
        <div class="es-kinky-hero-slide-date">
            <?php echo date_i18n('l, j. F Y', $timestamp); ?>
        </div>
        <?php endif; ?>
        
        <h2 class="es-kinky-hero-slide-title">
            <a href="<?php echo esc_url($permalink); ?>"><?php echo esc_html($title); ?></a>
        </h2>
        
        <?php if ($location): ?>
        <div class="es-kinky-hero-slide-location">
            <?php echo esc_html($location); ?>
        </div>
        <?php endif; ?>
        
        <?php if ($excerpt): ?>
        <p class="es-kinky-hero-slide-excerpt"><?php echo esc_html($excerpt); ?></p>
        <?php endif; ?>
        
        <?php if (!$is_special): ?>
        <a href="<?php echo esc_url($permalink); ?>" class="es-kinky-button">
            <?php _e('Details', 'ensemble'); ?>
        </a>
        <?php endif; ?>
        
        <?php if ($is_special): ?>
        <span class="es-kinky-status es-kinky-status-<?php echo esc_attr($status); ?>">
            <?php echo esc_html(function_exists('ensemble_get_status_label') ? ensemble_get_status_label($status) : ucfirst($status)); ?>
        </span>
        <?php endif; ?>
        
    </div>
    
</div>

<style>
.es-kinky-hero-slide {
    position: relative;
    min-height: 70vh;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.es-kinky-hero-slide-bg {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 1;
}

.es-kinky-hero-slide-bg img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.es-kinky-hero-slide-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(
        180deg,
        rgba(13, 10, 12, 0.4) 0%,
        rgba(13, 10, 12, 0.6) 50%,
        rgba(13, 10, 12, 0.9) 100%
    );
}

.es-kinky-hero-slide-content {
    position: relative;
    z-index: 2;
    text-align: center;
    max-width: 800px;
    padding: 40px 24px;
}

.es-kinky-hero-slide-date {
    font-family: var(--kinky-font-body, 'Lato', sans-serif);
    font-size: 13px;
    font-weight: 600;
    letter-spacing: 0.2em;
    text-transform: uppercase;
    color: var(--kinky-secondary, #c45c7a);
    margin-bottom: 16px;
}

.es-kinky-hero-slide-title {
    font-family: var(--kinky-font-heading, 'Cinzel', serif);
    font-size: clamp(28px, 5vw, 48px);
    font-weight: 600;
    line-height: 1.2;
    margin: 0 0 16px;
}

.es-kinky-hero-slide-title a {
    color: var(--kinky-text, #f5f0f2);
    text-decoration: none;
    transition: color 0.3s ease;
}

.es-kinky-hero-slide-title a:hover {
    color: var(--kinky-secondary, #c45c7a);
}

.es-kinky-hero-slide-location {
    font-family: var(--kinky-font-body, 'Lato', sans-serif);
    font-size: 16px;
    font-weight: 300;
    color: var(--kinky-text-muted, #a89ca3);
    margin-bottom: 20px;
}

.es-kinky-hero-slide-excerpt {
    font-family: var(--kinky-font-body, 'Lato', sans-serif);
    font-size: 15px;
    font-weight: 300;
    line-height: 1.6;
    color: var(--kinky-text-muted, #a89ca3);
    margin: 0 0 24px;
}
</style>
