<?php
/**
 * Lovepop Layout - Hero Slide Template
 * 
 * Colorful, playful style:
 * Date → Title → Buttons (more spacing)
 * 
 * @package Ensemble
 * @layout Lovepop
 * 
 * Available variables:
 * - $event (array) - Event data
 * - $atts (array) - Shortcode attributes
 */

if (!defined('ABSPATH')) exit;

// Format date
$date_display = '';
if (!empty($event['start_date'])) {
    $date_display = date_i18n('l, j. F Y', strtotime($event['start_date']));
}
?>
<div class="es-hero-slide es-hero-slide--lovepop">
    <?php if (!empty($event['featured_image'])): ?>
        <img src="<?php echo esc_url($event['featured_image']); ?>" 
             alt="<?php echo esc_attr($event['title']); ?>" 
             class="es-hero-slide__image"
             loading="lazy">
    <?php else: ?>
        <div class="es-hero-slide__image es-hero-slide__image--placeholder"></div>
    <?php endif; ?>
    
    <div class="es-hero-slide__content">
        <?php if (!empty($date_display)): ?>
            <div class="es-hero-slide__date">
                <?php echo esc_html($date_display); ?>
            </div>
        <?php endif; ?>
        
        <h2 class="es-hero-slide__title">
            <a href="<?php echo esc_url($event['permalink']); ?>">
                <?php echo esc_html($event['title']); ?>
            </a>
        </h2>
        
        <div class="es-hero-slide__actions">
            <?php if ($atts['show_button']): ?>
                <a href="<?php echo esc_url($event['permalink']); ?>" 
                   class="es-hero-slide__btn es-hero-slide__btn--primary">
                    <?php echo esc_html($atts['button_text']); ?>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
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
