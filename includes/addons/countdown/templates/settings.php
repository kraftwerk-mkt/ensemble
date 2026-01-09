<?php
/**
 * Countdown - Settings Template
 * 
 * @package Ensemble
 * @subpackage Addons/Countdown
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="es-addon-settings-form">
    
    <!-- Display Options -->
    <div class="es-settings-section">
        <h3><?php _e('Display Units', 'ensemble'); ?></h3>
        
        <div class="es-toggle-group">
            <label class="es-toggle es-toggle--reverse es-toggle--block">
                <input type="checkbox" name="show_days" value="1" <?php checked($settings['show_days']); ?>>
                <span class="es-toggle-track"></span>
                <span class="es-toggle-label"><?php _e('Show days', 'ensemble'); ?></span>
            </label>
            
            <label class="es-toggle es-toggle--reverse es-toggle--block">
                <input type="checkbox" name="show_hours" value="1" <?php checked($settings['show_hours']); ?>>
                <span class="es-toggle-track"></span>
                <span class="es-toggle-label"><?php _e('Show hours', 'ensemble'); ?></span>
            </label>
            
            <label class="es-toggle es-toggle--reverse es-toggle--block">
                <input type="checkbox" name="show_minutes" value="1" <?php checked($settings['show_minutes']); ?>>
                <span class="es-toggle-track"></span>
                <span class="es-toggle-label"><?php _e('Show minutes', 'ensemble'); ?></span>
            </label>
            
            <label class="es-toggle es-toggle--reverse es-toggle--block">
                <input type="checkbox" name="show_seconds" value="1" <?php checked($settings['show_seconds']); ?>>
                <span class="es-toggle-track"></span>
                <span class="es-toggle-label"><?php _e('Show seconds', 'ensemble'); ?></span>
            </label>
            
            <label class="es-toggle es-toggle--reverse es-toggle--block">
                <input type="checkbox" name="show_labels" value="1" <?php checked($settings['show_labels']); ?>>
                <span class="es-toggle-track"></span>
                <span class="es-toggle-label">
                    <?php _e('Show labels', 'ensemble'); ?>
                    <small><?php _e('Show "Days", "Hours", etc. below the numbers.', 'ensemble'); ?></small>
                </span>
            </label>
        </div>
    </div>
    
    <!-- Behavior -->
    <div class="es-settings-section">
        <h3><?php _e('Behavior', 'ensemble'); ?></h3>
        
        <div class="es-toggle-group">
            <label class="es-toggle es-toggle--reverse es-toggle--block">
                <input type="checkbox" name="hide_when_passed" value="1" <?php checked($settings['hide_when_passed']); ?>>
                <span class="es-toggle-track"></span>
                <span class="es-toggle-label"><?php _e('Hide when event is over', 'ensemble'); ?></span>
            </label>
            
            <label class="es-toggle es-toggle--reverse es-toggle--block">
                <input type="checkbox" name="show_event_started" value="1" <?php checked($settings['show_event_started']); ?>>
                <span class="es-toggle-track"></span>
                <span class="es-toggle-label">
                    <?php _e('Show "Event is live!"', 'ensemble'); ?>
                    <small><?php _e('Shows a status when the event is currently running.', 'ensemble'); ?></small>
                </span>
            </label>
        </div>
        
        <div class="es-form-row">
            <label for="countdown-started-text"><?php _e('Text "Event is live"', 'ensemble'); ?></label>
            <input type="text" 
                   id="countdown-started-text" 
                   name="event_started_text" 
                   value="<?php echo esc_attr($settings['event_started_text']); ?>" 
                   class="regular-text">
        </div>
        
        <div class="es-form-row">
            <label for="countdown-passed-text"><?php _e('Text "Event ended"', 'ensemble'); ?></label>
            <input type="text" 
                   id="countdown-passed-text" 
                   name="event_passed_text" 
                   value="<?php echo esc_attr($settings['event_passed_text']); ?>" 
                   class="regular-text">
        </div>
    </div>
    
    <!-- Design -->
    <div class="es-settings-section">
        <h3><?php _e('Design', 'ensemble'); ?></h3>
        
        <div class="es-form-row">
            <label for="countdown-style"><?php _e('Style', 'ensemble'); ?></label>
            <select id="countdown-style" name="style">
                <option value="boxes" <?php selected($settings['style'], 'boxes'); ?>>
                    <?php _e('Boxes', 'ensemble'); ?>
                </option>
                <option value="minimal" <?php selected($settings['style'], 'minimal'); ?>>
                    <?php _e('Minimal', 'ensemble'); ?>
                </option>
                <option value="flip" <?php selected($settings['style'], 'flip'); ?>>
                    <?php _e('Flip Cards', 'ensemble'); ?>
                </option>
                <option value="circle" <?php selected($settings['style'], 'circle'); ?>>
                    <?php _e('Circles', 'ensemble'); ?>
                </option>
            </select>
        </div>
        
        <div class="es-form-row">
            <label for="countdown-size"><?php _e('Size', 'ensemble'); ?></label>
            <select id="countdown-size" name="size">
                <option value="small" <?php selected($settings['size'], 'small'); ?>>
                    <?php _e('Small', 'ensemble'); ?>
                </option>
                <option value="medium" <?php selected($settings['size'], 'medium'); ?>>
                    <?php _e('Medium', 'ensemble'); ?>
                </option>
                <option value="large" <?php selected($settings['size'], 'large'); ?>>
                    <?php _e('Large', 'ensemble'); ?>
                </option>
            </select>
        </div>
    </div>
    
</div>
