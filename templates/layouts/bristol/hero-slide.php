<?php
/**
 * Hero Slide Template - Bristol Editorial Style
 * Full-width with side content
 * 
 * @package Ensemble
 * @layout Bristol City Festival
 */
if (!defined('ABSPATH')) exit;

$event_id = get_the_ID();
$event = es_load_event_data($event_id);
$permalink = get_permalink($event_id);

// Date formatting
$date_display = '';
if (!empty($event['date'])) {
    $timestamp = strtotime($event['date']);
    $date_display = date_i18n('j. F Y', $timestamp);
}

// Category
$category = !empty($event['categories']) ? $event['categories'][0]->name : '';

// Location
$location_name = !empty($event['location']['name']) ? $event['location']['name'] : '';
?>

<div class="es-bristol-hero-slide">
    <!-- Background Image -->
    <div class="es-bristol-hero-slide-media">
        <?php if (has_post_thumbnail()): ?>
            <?php the_post_thumbnail('full'); ?>
        <?php endif; ?>
    </div>
    
    <!-- Gradient Overlay (side) -->
    <div class="es-bristol-hero-slide-overlay"></div>
    
    <!-- Content -->
    <div class="es-bristol-hero-slide-content">
        <?php if ($category): ?>
        <span class="es-bristol-hero-slide-badge"><?php echo esc_html($category); ?></span>
        <?php endif; ?>
        
        <h2 class="es-bristol-hero-slide-title">
            <a href="<?php echo esc_url($permalink); ?>"><?php the_title(); ?></a>
        </h2>
        
        <div class="es-bristol-hero-slide-meta">
            <?php if ($date_display): ?>
            <span><?php echo esc_html($date_display); ?></span>
            <?php endif; ?>
            <?php if ($location_name): ?>
            <span> — <?php echo esc_html($location_name); ?></span>
            <?php endif; ?>
        </div>
        
        <div class="es-bristol-hero-slide-actions">
            <a href="<?php echo esc_url($permalink); ?>" class="es-bristol-btn es-bristol-btn-primary">
                <?php _e('Details ansehen', 'ensemble'); ?>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </a>
            <?php if (!empty($event['ticket_link'])): ?>
            <a href="<?php echo esc_url($event['ticket_link']); ?>" class="es-bristol-btn es-bristol-btn-outline" target="_blank">
                <?php _e('Tickets', 'ensemble'); ?>
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>
