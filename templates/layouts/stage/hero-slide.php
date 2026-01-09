<?php
/**
 * Stage Layout - Hero Slide Template
 * 
 * Theatrical, dramatic style with generous whitespace:
 * Badge/Category → Title → Meta (uppercase date) → Excerpt → Buttons
 * 
 * @package Ensemble
 * @layout Stage
 * 
 * Available variables:
 * - $event (array) - Event data
 * - $atts (array) - Shortcode attributes
 */

if (!defined('ABSPATH')) exit;

// Get badge text
$badge_text = '';
$is_badge = false;

if (!empty($event['badge_custom'])) {
    $badge_text = $event['badge_custom'];
    $is_badge = true;
} elseif (!empty($event['badge']) && $event['badge'] !== 'none' && $event['badge'] !== '' && $event['badge'] !== 'show_category') {
    $badge_labels = array(
        'sold_out' => __('Sold Out', 'ensemble'),
        'few_tickets' => __('Few Tickets', 'ensemble'),
        'free' => __('Free Entry', 'ensemble'),
        'new' => __('New', 'ensemble'),
        'premiere' => __('Premiere', 'ensemble'),
        'last_show' => __('Last Show', 'ensemble'),
        'special' => __('Special Event', 'ensemble'),
    );
    if (isset($badge_labels[$event['badge']])) {
        $badge_text = $badge_labels[$event['badge']];
        $is_badge = true;
    }
} elseif (!empty($event['badge']) && $event['badge'] === 'show_category' && !empty($event['categories'])) {
    $category = is_array($event['categories']) ? $event['categories'][0] : $event['categories'];
    $badge_text = is_object($category) ? $category->name : $category;
}

// Format date theatrically - uppercase style
$date_display = '';
if (!empty($event['start_date'])) {
    $date_display = strtoupper(date_i18n('l, j. F Y', strtotime($event['start_date'])));
}
?>
<div class="es-hero-slide es-hero-slide--stage">
    <?php if (!empty($event['featured_image'])): ?>
        <img src="<?php echo esc_url($event['featured_image']); ?>" 
             alt="<?php echo esc_attr($event['title']); ?>" 
             class="es-hero-slide__image"
             loading="lazy">
    <?php else: ?>
        <div class="es-hero-slide__image es-hero-slide__image--placeholder"></div>
    <?php endif; ?>
    
    <div class="es-hero-slide__content">
        <?php if (!empty($badge_text)): ?>
            <span class="es-hero-slide__category<?php echo $is_badge ? ' es-hero-slide__badge' : ''; ?>">
                <?php echo esc_html($badge_text); ?>
            </span>
        <?php endif; ?>
        
        <h2 class="es-hero-slide__title">
            <a href="<?php echo esc_url($event['permalink']); ?>">
                <?php echo esc_html($event['title']); ?>
            </a>
        </h2>
        
        <div class="es-hero-slide__meta">
            <?php if (!empty($date_display)): ?>
                <span class="es-hero-slide__meta-item es-hero-slide__meta-item--date">
                    <?php echo esc_html($date_display); ?>
                </span>
            <?php endif; ?>
            
            <?php if ($atts['show_time'] && !empty($event['start_time'])): ?>
                <span class="es-hero-slide__meta-item es-hero-slide__meta-item--time">
                    <?php echo esc_html($event['start_time']); ?> Uhr
                </span>
            <?php endif; ?>
            
            <?php if ($atts['show_location'] && !empty($event['location'])): ?>
                <span class="es-hero-slide__meta-item es-hero-slide__meta-item--location">
                    <?php echo esc_html($event['location']); ?>
                </span>
            <?php endif; ?>
        </div>
        
        <?php if ($atts['show_excerpt'] && !empty($event['excerpt'])): ?>
            <div class="es-hero-slide__excerpt">
                <?php echo wp_trim_words($event['excerpt'], 25, '...'); ?>
            </div>
        <?php endif; ?>
        
        <div class="es-hero-slide__actions">
            <?php if ($atts['show_button']): ?>
                <a href="<?php echo esc_url($event['permalink']); ?>" 
                   class="es-hero-slide__btn es-hero-slide__btn--primary">
                    <?php echo esc_html($atts['button_text']); ?>
                </a>
            <?php endif; ?>
            
            <?php if ($atts['show_ticket_button'] && !empty($event['ticket_url'])): ?>
                <a href="<?php echo esc_url($event['ticket_url']); ?>" 
                   class="es-hero-slide__btn es-hero-slide__btn--secondary"
                   target="_blank" rel="noopener noreferrer">
                    <?php echo esc_html($atts['ticket_button_text']); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>
