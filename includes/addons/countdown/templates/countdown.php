<?php
/**
 * Countdown - Timer Template
 * 
 * @package Ensemble
 * @subpackage Addons/Countdown
 * 
 * Variables available:
 * - $event_id (int) - Event ID
 * - $event_timestamp (int) - Unix timestamp
 * - $event_datetime (string) - ISO 8601 datetime
 * - $has_passed (bool) - Event has passed
 * - $is_running (bool) - Event is currently running
 * - $settings (array) - Addon settings
 */

if (!defined('ABSPATH')) {
    exit;
}

// Build CSS classes
$wrapper_classes = array('es-countdown-wrapper');
$wrapper_classes[] = 'es-countdown-style-' . esc_attr($settings['style']);
$wrapper_classes[] = 'es-countdown-size-' . esc_attr($settings['size']);

if ($has_passed && !$is_running) {
    $wrapper_classes[] = 'es-countdown-passed';
}

if ($is_running) {
    $wrapper_classes[] = 'es-countdown-running';
}
?>

<div class="<?php echo esc_attr(implode(' ', $wrapper_classes)); ?>" 
     data-countdown="<?php echo esc_attr($event_datetime); ?>"
     data-timestamp="<?php echo esc_attr($event_timestamp); ?>"
     data-hide-passed="<?php echo $settings['hide_when_passed'] ? 'true' : 'false'; ?>"
     data-show-running="<?php echo $settings['show_event_started'] ? 'true' : 'false'; ?>"
     data-running-text="<?php echo esc_attr($settings['event_started_text']); ?>"
     data-passed-text="<?php echo esc_attr($settings['event_passed_text']); ?>">
    
    <?php if ($is_running && $settings['show_event_started']): ?>
        
        <div class="es-countdown-status es-countdown-status-running">
            <span class="es-countdown-pulse"></span>
            <span class="es-countdown-status-text"><?php echo esc_html($settings['event_started_text']); ?></span>
        </div>
        
    <?php elseif ($has_passed && !$settings['hide_when_passed']): ?>
        
        <div class="es-countdown-status es-countdown-status-passed">
            <span class="es-countdown-status-text"><?php echo esc_html($settings['event_passed_text']); ?></span>
        </div>
        
    <?php else: ?>
        
        <div class="es-countdown-timer">
            
            <?php if ($settings['show_days']): ?>
            <div class="es-countdown-unit es-countdown-days">
                <span class="es-countdown-value" data-unit="days">--</span>
                <?php if ($settings['show_labels']): ?>
                <span class="es-countdown-label"><?php _e('Tage', 'ensemble'); ?></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($settings['show_hours']): ?>
            <div class="es-countdown-unit es-countdown-hours">
                <span class="es-countdown-value" data-unit="hours">--</span>
                <?php if ($settings['show_labels']): ?>
                <span class="es-countdown-label"><?php _e('Stunden', 'ensemble'); ?></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($settings['show_minutes']): ?>
            <div class="es-countdown-unit es-countdown-minutes">
                <span class="es-countdown-value" data-unit="minutes">--</span>
                <?php if ($settings['show_labels']): ?>
                <span class="es-countdown-label"><?php _e('Minuten', 'ensemble'); ?></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($settings['show_seconds']): ?>
            <div class="es-countdown-unit es-countdown-seconds">
                <span class="es-countdown-value" data-unit="seconds">--</span>
                <?php if ($settings['show_labels']): ?>
                <span class="es-countdown-label"><?php _e('Sekunden', 'ensemble'); ?></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
        </div>
        
    <?php endif; ?>
    
</div>
