<?php
/**
 * Sponsors Addon Settings
 * Using correct toggle pattern from other addons
 *
 * @package Ensemble
 * @subpackage Addons/Sponsors
 */

if (!defined('ABSPATH')) {
    exit;
}

$categories = isset($categories) ? $categories : array();
?>

<div class="es-sponsors-settings">
    
    <!-- Logo Display Settings -->
    <div class="es-settings-section">
        <h3><?php _e('Logo Display', 'ensemble'); ?></h3>
        
        <div class="es-setting-row">
            <div class="es-setting-info">
                <span class="es-setting-title"><?php _e('Logo Height (Desktop)', 'ensemble'); ?></span>
                <span class="es-setting-desc"><?php _e('Height in pixels for sponsor logos', 'ensemble'); ?></span>
            </div>
            <div class="es-setting-control">
                <input type="number" 
                       name="logo_height" 
                       value="<?php echo esc_attr($settings['logo_height']); ?>" 
                       min="20" 
                       max="200"
                       class="es-input-small">
                <span class="es-unit">px</span>
            </div>
        </div>
        
        <div class="es-setting-row">
            <div class="es-setting-info">
                <span class="es-setting-title"><?php _e('Logo Height (Mobile)', 'ensemble'); ?></span>
                <span class="es-setting-desc"><?php _e('Smaller height for mobile devices', 'ensemble'); ?></span>
            </div>
            <div class="es-setting-control">
                <input type="number" 
                       name="logo_height_mobile" 
                       value="<?php echo esc_attr($settings['logo_height_mobile']); ?>" 
                       min="20" 
                       max="100"
                       class="es-input-small">
                <span class="es-unit">px</span>
            </div>
        </div>
        
        <div class="es-setting-row">
            <div class="es-setting-info">
                <span class="es-setting-title"><?php _e('Spacing Between Logos', 'ensemble'); ?></span>
                <span class="es-setting-desc"><?php _e('Gap between sponsor logos', 'ensemble'); ?></span>
            </div>
            <div class="es-setting-control">
                <input type="number" 
                       name="logo_spacing" 
                       value="<?php echo esc_attr($settings['logo_spacing']); ?>" 
                       min="8" 
                       max="80"
                       class="es-input-small">
                <span class="es-unit">px</span>
            </div>
        </div>
        
        <div class="es-setting-row">
            <div class="es-setting-info">
                <span class="es-setting-title"><?php _e('Grayscale Effect', 'ensemble'); ?></span>
                <span class="es-setting-desc"><?php _e('Display logos in grayscale', 'ensemble'); ?></span>
            </div>
            <label class="es-toggle-wrapper">
                <input type="checkbox" name="grayscale" value="1" <?php checked($settings['grayscale']); ?>>
                <span class="es-toggle-switch"></span>
            </label>
        </div>
        
        <div class="es-setting-row">
            <div class="es-setting-info">
                <span class="es-setting-title"><?php _e('Color on Hover', 'ensemble'); ?></span>
                <span class="es-setting-desc"><?php _e('Show original colors on hover', 'ensemble'); ?></span>
            </div>
            <label class="es-toggle-wrapper">
                <input type="checkbox" name="grayscale_hover" value="1" <?php checked($settings['grayscale_hover']); ?>>
                <span class="es-toggle-switch"></span>
            </label>
        </div>
    </div>
    
    <!-- Carousel Settings -->
    <div class="es-settings-section">
        <h3><?php _e('Carousel Settings', 'ensemble'); ?></h3>
        
        <div class="es-setting-row">
            <div class="es-setting-info">
                <span class="es-setting-title"><?php _e('Autoplay', 'ensemble'); ?></span>
                <span class="es-setting-desc"><?php _e('Automatically scroll through sponsors', 'ensemble'); ?></span>
            </div>
            <label class="es-toggle-wrapper">
                <input type="checkbox" name="carousel_autoplay" value="1" <?php checked($settings['carousel_autoplay']); ?>>
                <span class="es-toggle-switch"></span>
            </label>
        </div>
        
        <div class="es-setting-row">
            <div class="es-setting-info">
                <span class="es-setting-title"><?php _e('Autoplay Speed', 'ensemble'); ?></span>
                <span class="es-setting-desc"><?php _e('Time between slides in milliseconds', 'ensemble'); ?></span>
            </div>
            <div class="es-setting-control">
                <input type="number" 
                       name="carousel_speed" 
                       value="<?php echo esc_attr($settings['carousel_speed']); ?>" 
                       min="1000" 
                       max="10000"
                       step="500"
                       class="es-input-small">
                <span class="es-unit">ms</span>
            </div>
        </div>
        
        <div class="es-setting-row">
            <div class="es-setting-info">
                <span class="es-setting-title"><?php _e('Pause on Hover', 'ensemble'); ?></span>
                <span class="es-setting-desc"><?php _e('Stop autoplay when mouse hovers', 'ensemble'); ?></span>
            </div>
            <label class="es-toggle-wrapper">
                <input type="checkbox" name="carousel_pause_hover" value="1" <?php checked($settings['carousel_pause_hover']); ?>>
                <span class="es-toggle-switch"></span>
            </label>
        </div>
        
        <div class="es-setting-row">
            <div class="es-setting-info">
                <span class="es-setting-title"><?php _e('Infinite Loop', 'ensemble'); ?></span>
                <span class="es-setting-desc"><?php _e('Loop back to start after last slide', 'ensemble'); ?></span>
            </div>
            <label class="es-toggle-wrapper">
                <input type="checkbox" name="carousel_loop" value="1" <?php checked($settings['carousel_loop']); ?>>
                <span class="es-toggle-switch"></span>
            </label>
        </div>
    </div>
    
    <!-- Event Integration -->
    <div class="es-settings-section">
        <h3><?php _e('Event Integration', 'ensemble'); ?></h3>
        
        <div class="es-setting-row">
            <div class="es-setting-info">
                <span class="es-setting-title"><?php _e('Auto-Display on Events', 'ensemble'); ?></span>
                <span class="es-setting-desc"><?php _e('Automatically show sponsors on single event pages', 'ensemble'); ?></span>
            </div>
            <label class="es-toggle-wrapper">
                <input type="checkbox" name="auto_display_events" value="1" <?php checked($settings['auto_display_events']); ?>>
                <span class="es-toggle-switch"></span>
            </label>
        </div>
        
        <div class="es-setting-row">
            <div class="es-setting-info">
                <span class="es-setting-title"><?php _e('Position on Event Page', 'ensemble'); ?></span>
                <span class="es-setting-desc"><?php _e('Where to display sponsors on event pages', 'ensemble'); ?></span>
            </div>
            <div class="es-setting-control">
                <select name="event_position" class="es-select">
                    <option value="before_content" <?php selected($settings['event_position'], 'before_content'); ?>>
                        <?php _e('Before Content', 'ensemble'); ?>
                    </option>
                    <option value="after_content" <?php selected($settings['event_position'], 'after_content'); ?>>
                        <?php _e('After Content', 'ensemble'); ?>
                    </option>
                    <option value="after_artists" <?php selected($settings['event_position'], 'after_artists'); ?>>
                        <?php _e('After Artists/Lineup', 'ensemble'); ?>
                    </option>
                    <option value="footer" <?php selected($settings['event_position'], 'footer'); ?>>
                        <?php _e('Event Footer', 'ensemble'); ?>
                    </option>
                </select>
            </div>
        </div>
        
        <div class="es-setting-row">
            <div class="es-setting-info">
                <span class="es-setting-title"><?php _e('Display Style', 'ensemble'); ?></span>
                <span class="es-setting-desc"><?php _e('How sponsors are displayed on events', 'ensemble'); ?></span>
            </div>
            <div class="es-setting-control">
                <select name="event_style" class="es-select">
                    <option value="carousel" <?php selected($settings['event_style'], 'carousel'); ?>>
                        <?php _e('Carousel', 'ensemble'); ?>
                    </option>
                    <option value="grid" <?php selected($settings['event_style'], 'grid'); ?>>
                        <?php _e('Grid', 'ensemble'); ?>
                    </option>
                    <option value="bar" <?php selected($settings['event_style'], 'bar'); ?>>
                        <?php _e('Logo Bar', 'ensemble'); ?>
                    </option>
                    <option value="marquee" <?php selected($settings['event_style'], 'marquee'); ?>>
                        <?php _e('Scrolling Marquee', 'ensemble'); ?>
                    </option>
                </select>
            </div>
        </div>
        
        <div class="es-setting-row">
            <div class="es-setting-info">
                <span class="es-setting-title"><?php _e('Section Title', 'ensemble'); ?></span>
                <span class="es-setting-desc"><?php _e('Title shown above sponsors', 'ensemble'); ?></span>
            </div>
            <div class="es-setting-control">
                <input type="text" 
                       name="event_title" 
                       value="<?php echo esc_attr($settings['event_title']); ?>" 
                       placeholder="<?php _e('Sponsors', 'ensemble'); ?>"
                       class="es-input-wide">
            </div>
        </div>
        
        <div class="es-setting-row">
            <div class="es-setting-info">
                <span class="es-setting-title"><?php _e('Include Global Sponsors', 'ensemble'); ?></span>
                <span class="es-setting-desc"><?php _e('Also show global sponsors alongside event-specific ones', 'ensemble'); ?></span>
            </div>
            <label class="es-toggle-wrapper">
                <input type="checkbox" name="show_global_on_events" value="1" <?php checked($settings['show_global_on_events']); ?>>
                <span class="es-toggle-switch"></span>
            </label>
        </div>
    </div>
    
    <!-- Footer Integration -->
    <div class="es-settings-section">
        <h3><?php _e('Footer Integration', 'ensemble'); ?></h3>
        
        <div class="es-setting-row">
            <div class="es-setting-info">
                <span class="es-setting-title"><?php _e('Show in Footer', 'ensemble'); ?></span>
                <span class="es-setting-desc"><?php _e('Display a sponsor bar in the site footer', 'ensemble'); ?></span>
            </div>
            <label class="es-toggle-wrapper">
                <input type="checkbox" name="show_in_footer" value="1" <?php checked($settings['show_in_footer']); ?>>
                <span class="es-toggle-switch"></span>
            </label>
        </div>
        
        <div class="es-setting-row">
            <div class="es-setting-info">
                <span class="es-setting-title"><?php _e('Footer Style', 'ensemble'); ?></span>
                <span class="es-setting-desc"><?php _e('Display style for footer sponsors', 'ensemble'); ?></span>
            </div>
            <div class="es-setting-control">
                <select name="footer_style" class="es-select">
                    <option value="bar" <?php selected($settings['footer_style'], 'bar'); ?>>
                        <?php _e('Logo Bar', 'ensemble'); ?>
                    </option>
                    <option value="carousel" <?php selected($settings['footer_style'], 'carousel'); ?>>
                        <?php _e('Carousel', 'ensemble'); ?>
                    </option>
                    <option value="marquee" <?php selected($settings['footer_style'], 'marquee'); ?>>
                        <?php _e('Scrolling Marquee', 'ensemble'); ?>
                    </option>
                </select>
            </div>
        </div>
        
        <div class="es-setting-row">
            <div class="es-setting-info">
                <span class="es-setting-title"><?php _e('Footer Title', 'ensemble'); ?></span>
                <span class="es-setting-desc"><?php _e('Title shown in footer section', 'ensemble'); ?></span>
            </div>
            <div class="es-setting-control">
                <input type="text" 
                       name="footer_title" 
                       value="<?php echo esc_attr($settings['footer_title']); ?>" 
                       placeholder="<?php _e('Our Partners', 'ensemble'); ?>"
                       class="es-input-wide">
            </div>
        </div>
        
        <?php if (!empty($categories) && !is_wp_error($categories)): ?>
        <div class="es-setting-row">
            <div class="es-setting-info">
                <span class="es-setting-title"><?php _e('Footer Categories', 'ensemble'); ?></span>
                <span class="es-setting-desc"><?php _e('Only show sponsors from these categories', 'ensemble'); ?></span>
            </div>
            <div class="es-setting-control">
                <div class="es-checkbox-list">
                    <?php foreach ($categories as $cat): ?>
                    <label class="es-checkbox-item">
                        <input type="checkbox" 
                               name="footer_categories[]" 
                               value="<?php echo esc_attr($cat->term_id); ?>"
                               <?php checked(in_array($cat->term_id, (array) $settings['footer_categories'])); ?>>
                        <span><?php echo esc_html($cat->name); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
                <small class="es-hint"><?php _e('Leave empty to show all sponsors', 'ensemble'); ?></small>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Main Sponsor Integration -->
    <div class="es-settings-section">
        <h3><?php _e('Main Sponsor', 'ensemble'); ?> <span class="es-badge es-badge--warning" style="font-size: 11px; vertical-align: middle;">★</span></h3>
        <p class="es-section-desc" style="margin-top: 0; opacity: 0.7;">
            <?php _e('Display a prominent main sponsor logo in the header or sidebar area.', 'ensemble'); ?>
        </p>
        
        <div class="es-setting-row">
            <div class="es-setting-info">
                <span class="es-setting-title"><?php _e('Enable Main Sponsor', 'ensemble'); ?></span>
                <span class="es-setting-desc"><?php _e('Show the main sponsor prominently on the site', 'ensemble'); ?></span>
            </div>
            <label class="es-toggle-wrapper">
                <input type="checkbox" name="main_sponsor_enabled" value="1" <?php checked(!empty($settings['main_sponsor_enabled'])); ?>>
                <span class="es-toggle-switch"></span>
            </label>
        </div>
        
        <div class="es-setting-row">
            <div class="es-setting-info">
                <span class="es-setting-title"><?php _e('Display Position', 'ensemble'); ?></span>
                <span class="es-setting-desc"><?php _e('Where to show the main sponsor', 'ensemble'); ?></span>
            </div>
            <div class="es-setting-control">
                <select name="main_sponsor_position" class="es-select">
                    <option value="header" <?php selected(isset($settings['main_sponsor_position']) ? $settings['main_sponsor_position'] : 'header', 'header'); ?>>
                        <?php _e('Header (next to navigation)', 'ensemble'); ?>
                    </option>
                    <option value="sidebar" <?php selected(isset($settings['main_sponsor_position']) ? $settings['main_sponsor_position'] : 'header', 'sidebar'); ?>>
                        <?php _e('Sidebar (widget area)', 'ensemble'); ?>
                    </option>
                    <option value="both" <?php selected(isset($settings['main_sponsor_position']) ? $settings['main_sponsor_position'] : 'header', 'both'); ?>>
                        <?php _e('Both (header and sidebar)', 'ensemble'); ?>
                    </option>
                </select>
            </div>
        </div>
        
        <div class="es-setting-row">
            <div class="es-setting-info">
                <span class="es-setting-title"><?php _e('Logo Height', 'ensemble'); ?></span>
                <span class="es-setting-desc"><?php _e('Height of the main sponsor logo in header', 'ensemble'); ?></span>
            </div>
            <div class="es-setting-control">
                <input type="number" 
                       name="main_sponsor_height" 
                       value="<?php echo esc_attr(isset($settings['main_sponsor_height']) ? $settings['main_sponsor_height'] : 40); ?>" 
                       min="20" 
                       max="80"
                       class="es-input-small">
                <span class="es-unit">px</span>
            </div>
        </div>
        
        <div class="es-setting-row">
            <div class="es-setting-info">
                <span class="es-setting-title"><?php _e('Default Caption', 'ensemble'); ?></span>
                <span class="es-setting-desc"><?php _e('Text shown above the main sponsor logo (e.g. "Presented by")', 'ensemble'); ?></span>
            </div>
            <div class="es-setting-control">
                <input type="text" 
                       name="main_sponsor_caption" 
                       value="<?php echo esc_attr(isset($settings['main_sponsor_caption']) ? $settings['main_sponsor_caption'] : __('Presented by', 'ensemble')); ?>" 
                       placeholder="<?php _e('Presented by', 'ensemble'); ?>"
                       class="es-input-wide">
            </div>
        </div>
        
        <div class="es-setting-row">
            <div class="es-setting-info">
                <span class="es-setting-title"><?php _e('Exclude from Footer', 'ensemble'); ?></span>
                <span class="es-setting-desc"><?php _e('Do not show main sponsor in the footer sponsor bar', 'ensemble'); ?></span>
            </div>
            <label class="es-toggle-wrapper">
                <input type="checkbox" name="main_sponsor_exclude_footer" value="1" <?php checked(!empty($settings['main_sponsor_exclude_footer'])); ?>>
                <span class="es-toggle-switch"></span>
            </label>
        </div>
        
        <div class="es-setting-row">
            <div class="es-setting-info">
                <span class="es-setting-title"><?php _e('Footer Main Sponsor Position', 'ensemble'); ?></span>
                <span class="es-setting-desc"><?php _e('When using Header Partner Logo in Theme Customizer, the Main Sponsor moves to the footer', 'ensemble'); ?></span>
            </div>
            <div class="es-setting-control">
                <select name="main_sponsor_footer_position" class="es-select">
                    <option value="above" <?php selected(isset($settings['main_sponsor_footer_position']) ? $settings['main_sponsor_footer_position'] : 'above', 'above'); ?>>
                        <?php _e('Above sponsors bar', 'ensemble'); ?>
                    </option>
                    <option value="left" <?php selected(isset($settings['main_sponsor_footer_position']) ? $settings['main_sponsor_footer_position'] : 'above', 'left'); ?>>
                        <?php _e('Left of sponsors bar', 'ensemble'); ?>
                    </option>
                    <option value="right" <?php selected(isset($settings['main_sponsor_footer_position']) ? $settings['main_sponsor_footer_position'] : 'above', 'right'); ?>>
                        <?php _e('Right of sponsors bar', 'ensemble'); ?>
                    </option>
                </select>
            </div>
        </div>
        
        <div class="es-setting-row">
            <div class="es-setting-info">
                <span class="es-setting-title"><?php _e('Footer Caption', 'ensemble'); ?></span>
                <span class="es-setting-desc"><?php _e('Text above main sponsor when displayed in footer', 'ensemble'); ?></span>
            </div>
            <div class="es-setting-control">
                <input type="text" 
                       name="main_sponsor_footer_caption" 
                       value="<?php echo esc_attr(isset($settings['main_sponsor_footer_caption']) ? $settings['main_sponsor_footer_caption'] : __('Main Sponsor', 'ensemble')); ?>" 
                       placeholder="<?php _e('Main Sponsor', 'ensemble'); ?>"
                       class="es-input-wide">
            </div>
        </div>
        
        <div class="es-info-box" style="margin-top: 16px; padding: 12px 16px; background: #f8f9fa; border-radius: 6px; border-left: 3px solid var(--es-primary, #2271b1);">
            <strong><?php _e('How to use:', 'ensemble'); ?></strong>
            <ol style="margin: 8px 0 0 20px; padding: 0;">
                <li><?php _e('Create or edit a sponsor and check "Main Sponsor"', 'ensemble'); ?></li>
                <li><?php _e('Only one sponsor can be main sponsor at a time', 'ensemble'); ?></li>
                <li><?php _e('Use the Ensemble Theme or add the hook manually:', 'ensemble'); ?> <code>&lt;?php do_action('et_main_sponsor'); ?&gt;</code></li>
            </ol>
        </div>
    </div>
    
    <!-- Shortcode Reference -->
    <div class="es-settings-section">
        <h3><?php _e('Shortcodes', 'ensemble'); ?></h3>
        
        <div class="es-shortcode-info">
            <div class="es-shortcode-block">
                <code>[ensemble_sponsors]</code>
                <span><?php _e('Display all active sponsors', 'ensemble'); ?></span>
            </div>
            <div class="es-shortcode-block">
                <code>[ensemble_sponsors style="carousel"]</code>
                <span><?php _e('Carousel (default)', 'ensemble'); ?></span>
            </div>
            <div class="es-shortcode-block">
                <code>[ensemble_sponsors style="grid" columns="4"]</code>
                <span><?php _e('Grid with 4 columns', 'ensemble'); ?></span>
            </div>
            <div class="es-shortcode-block">
                <code>[ensemble_sponsors style="bar"]</code>
                <span><?php _e('Simple logo bar', 'ensemble'); ?></span>
            </div>
            <div class="es-shortcode-block">
                <code>[ensemble_sponsors style="marquee"]</code>
                <span><?php _e('Auto-scrolling marquee', 'ensemble'); ?></span>
            </div>
            <div class="es-shortcode-block">
                <code>[ensemble_sponsors category="gold"]</code>
                <span><?php _e('Filter by category', 'ensemble'); ?></span>
            </div>
            <div class="es-shortcode-block">
                <code>[ensemble_sponsors event="current"]</code>
                <span><?php _e('Sponsors for current event', 'ensemble'); ?></span>
            </div>
            <div class="es-shortcode-block">
                <code>[ensemble_sponsors exclude_main="true"]</code>
                <span><?php _e('All sponsors except main sponsor', 'ensemble'); ?></span>
            </div>
            <div class="es-shortcode-block">
                <code>[ensemble_sponsor id="123"]</code>
                <span><?php _e('Single sponsor', 'ensemble'); ?></span>
            </div>
            <div class="es-shortcode-block">
                <code>[ensemble_main_sponsor]</code>
                <span><?php _e('Display main sponsor', 'ensemble'); ?></span>
            </div>
            <div class="es-shortcode-block">
                <code>[ensemble_main_sponsor caption="Powered by"]</code>
                <span><?php _e('Main sponsor with custom caption', 'ensemble'); ?></span>
            </div>
        </div>
    </div>
    
</div>
