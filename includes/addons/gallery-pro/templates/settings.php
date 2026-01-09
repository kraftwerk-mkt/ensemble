<?php
/**
 * Gallery Pro - Settings Template
 * 
 * @package Ensemble
 * @subpackage Addons/Gallery Pro
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="es-addon-settings-content">
    
    <!-- Layout Settings -->
    <h3><?php _e('Layout Settings', 'ensemble'); ?></h3>
    
    <div class="es-form-group">
        <label><?php _e('Default Layout', 'ensemble'); ?></label>
        <div class="es-layout-selector">
            <?php foreach ($layouts as $key => $label) : ?>
                <label class="es-layout-option <?php echo ($settings['default_layout'] ?? 'grid') === $key ? 'active' : ''; ?>">
                    <input type="radio" 
                           name="default_layout" 
                           value="<?php echo esc_attr($key); ?>"
                           <?php checked($settings['default_layout'] ?? 'grid', $key); ?>>
                    <span class="es-layout-preview es-layout-preview-<?php echo esc_attr($key); ?>">
                        <?php echo $this->get_layout_icon($key); ?>
                    </span>
                    <span class="es-layout-label"><?php echo esc_html($label); ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="es-form-group">
        <label><?php _e('Default Columns', 'ensemble'); ?></label>
        <input type="number" 
               name="default_columns" 
               value="<?php echo esc_attr($settings['default_columns'] ?? 4); ?>" 
               min="2" 
               max="8"
               class="es-input-number">
        <p class="description"><?php _e('Number of columns for Grid and Masonry layout (2-8)', 'ensemble'); ?></p>
    </div>
    
    <div class="es-form-group">
        <label class="es-toggle">
            <input type="checkbox" 
                   name="show_captions" 
                   value="1" 
                   <?php checked($settings['show_captions'] ?? true, true); ?>>
            <span class="es-toggle-track"></span>
            <span class="es-toggle-label">
                <?php _e('Show captions', 'ensemble'); ?>
                <small><?php _e('Display title and description on images', 'ensemble'); ?></small>
            </span>
        </label>
    </div>
    
    <hr>
    
    <!-- Lightbox Settings -->
    <h3><?php _e('Lightbox Settings', 'ensemble'); ?></h3>
    
    <div class="es-form-group">
        <label><?php _e('Lightbox Theme', 'ensemble'); ?></label>
        <select name="lightbox_theme" class="es-select">
            <?php foreach ($lightbox_themes as $key => $label) : ?>
                <option value="<?php echo esc_attr($key); ?>" 
                        <?php selected($settings['lightbox_theme'] ?? 'dark', $key); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <div class="es-form-group">
        <label><?php _e('Transition Effect', 'ensemble'); ?></label>
        <select name="lightbox_effect" class="es-select">
            <option value="zoom" <?php selected($settings['lightbox_effect'] ?? 'zoom', 'zoom'); ?>>
                <?php _e('Zoom', 'ensemble'); ?>
            </option>
            <option value="fade" <?php selected($settings['lightbox_effect'] ?? 'zoom', 'fade'); ?>>
                <?php _e('Fade', 'ensemble'); ?>
            </option>
            <option value="slide" <?php selected($settings['lightbox_effect'] ?? 'zoom', 'slide'); ?>>
                <?php _e('Slide', 'ensemble'); ?>
            </option>
        </select>
    </div>
    
    <div class="es-form-group">
        <label class="es-toggle">
            <input type="checkbox" 
                   name="lightbox_loop" 
                   value="1" 
                   <?php checked($settings['lightbox_loop'] ?? true, true); ?>>
            <span class="es-toggle-track"></span>
            <span class="es-toggle-label">
                <?php _e('Enable infinite loop', 'ensemble'); ?>
                <small><?php _e('Jump back to first image after the last one', 'ensemble'); ?></small>
            </span>
        </label>
    </div>
    
    <div class="es-form-group">
        <label class="es-toggle">
            <input type="checkbox" 
                   name="lightbox_autoplay" 
                   value="1" 
                   <?php checked($settings['lightbox_autoplay'] ?? false, true); ?>>
            <span class="es-toggle-track"></span>
            <span class="es-toggle-label">
                <?php _e('Automatic slideshow', 'ensemble'); ?>
                <small><?php _e('Automatically cycle through images in lightbox', 'ensemble'); ?></small>
            </span>
        </label>
    </div>
    
    <hr>
    
    <!-- Carousel Settings -->
    <h3><?php _e('Carousel Settings', 'ensemble'); ?></h3>
    
    <div class="es-form-group">
        <label class="es-toggle">
            <input type="checkbox" 
                   name="enable_carousel" 
                   value="1" 
                   <?php checked($settings['enable_carousel'] ?? true, true); ?>>
            <span class="es-toggle-track"></span>
            <span class="es-toggle-label">
                <?php _e('Enable carousel layout', 'ensemble'); ?>
                <small><?php _e('Loads Swiper.js for carousel layout', 'ensemble'); ?></small>
            </span>
        </label>
    </div>
    
    <div class="es-carousel-settings" style="<?php echo ($settings['enable_carousel'] ?? true) ? '' : 'display:none;'; ?>">
        <div class="es-form-group">
            <label class="es-toggle">
                <input type="checkbox" 
                       name="carousel_autoplay" 
                       value="1" 
                       <?php checked($settings['carousel_autoplay'] ?? true, true); ?>>
                <span class="es-toggle-track"></span>
                <span class="es-toggle-label"><?php _e('Autoplay', 'ensemble'); ?></span>
            </label>
        </div>
        
        <div class="es-form-group">
            <label><?php _e('Autoplay delay (ms)', 'ensemble'); ?></label>
            <input type="number" 
                   name="carousel_delay" 
                   value="<?php echo esc_attr($settings['carousel_delay'] ?? 5000); ?>" 
                   min="1000" 
                   max="20000"
                   step="500"
                   class="es-input-number">
        </div>
        
        <div class="es-form-group">
            <label class="es-toggle">
                <input type="checkbox" 
                       name="carousel_loop" 
                       value="1" 
                       <?php checked($settings['carousel_loop'] ?? true, true); ?>>
                <span class="es-toggle-track"></span>
                <span class="es-toggle-label"><?php _e('Infinite loop', 'ensemble'); ?></span>
            </label>
        </div>
    </div>
    
    <hr>
    
    <!-- Video Settings -->
    <h3><?php _e('Video Settings', 'ensemble'); ?></h3>
    
    <div class="es-form-group">
        <label class="es-toggle">
            <input type="checkbox" 
                   name="enable_videos" 
                   value="1" 
                   <?php checked($settings['enable_videos'] ?? true, true); ?>>
            <span class="es-toggle-track"></span>
            <span class="es-toggle-label">
                <?php _e('Enable video support', 'ensemble'); ?>
                <small><?php _e('Embed YouTube and Vimeo videos in the gallery', 'ensemble'); ?></small>
            </span>
        </label>
    </div>
    
    <div class="es-form-group">
        <label class="es-toggle">
            <input type="checkbox" 
                   name="video_autoplay" 
                   value="1" 
                   <?php checked($settings['video_autoplay'] ?? false, true); ?>>
            <span class="es-toggle-track"></span>
            <span class="es-toggle-label">
                <?php _e('Autoplay videos', 'ensemble'); ?>
                <small><?php _e('Videos start automatically when opened in lightbox', 'ensemble'); ?></small>
            </span>
        </label>
    </div>
    
    <hr>
    
    <!-- Usage Info -->
    <h3><?php _e('Usage', 'ensemble'); ?></h3>
    
    <div class="es-info-box">
        <h4><?php _e('Automatic in event pages', 'ensemble'); ?></h4>
        <p><?php _e('The gallery is automatically displayed when events have a "gallery" field with images.', 'ensemble'); ?></p>
        
        <h4><?php _e('Shortcode', 'ensemble'); ?></h4>
        <code>[ensemble_gallery]</code> - <?php _e('Gallery of the current event', 'ensemble'); ?><br>
        <code>[ensemble_gallery event="123"]</code> - <?php _e('Gallery of a specific event', 'ensemble'); ?><br>
        <code>[ensemble_gallery layout="masonry" columns="3"]</code> - <?php _e('With layout options', 'ensemble'); ?><br>
        <code>[ensemble_gallery ids="1,2,3,4"]</code> - <?php _e('Specific attachment IDs', 'ensemble'); ?>
        
        <h4><?php _e('Shortcode Parameters', 'ensemble'); ?></h4>
        <table class="es-params-table">
            <tr><td><code>event</code></td><td><?php _e('Event ID', 'ensemble'); ?></td></tr>
            <tr><td><code>layout</code></td><td><?php _e('grid, masonry, carousel, justified, filmstrip', 'ensemble'); ?></td></tr>
            <tr><td><code>columns</code></td><td><?php _e('Number of columns (2-8)', 'ensemble'); ?></td></tr>
            <tr><td><code>captions</code></td><td><?php _e('true/false - Show captions', 'ensemble'); ?></td></tr>
            <tr><td><code>lightbox</code></td><td><?php _e('true/false - Enable lightbox', 'ensemble'); ?></td></tr>
            <tr><td><code>max</code></td><td><?php _e('Maximum number of images', 'ensemble'); ?></td></tr>
            <tr><td><code>ids</code></td><td><?php _e('Comma-separated attachment IDs', 'ensemble'); ?></td></tr>
        </table>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Toggle Carousel settings
    $('input[name="enable_carousel"]').on('change', function() {
        if ($(this).is(':checked')) {
            $('.es-carousel-settings').slideDown();
        } else {
            $('.es-carousel-settings').slideUp();
        }
    });
    
    // Layout selector active state
    $('.es-layout-option input').on('change', function() {
        $('.es-layout-option').removeClass('active');
        $(this).closest('.es-layout-option').addClass('active');
    });
});
</script>

<style>
.es-addon-settings-content {
    max-width: 900px;
}

.es-form-group {
    margin-bottom: 20px;
}

.es-form-group > label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
}

.es-input-number,
.es-select {
    width: 100%;
    max-width: 200px;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

/* Layout Selector */
.es-layout-selector {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.es-layout-option {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 15px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
    min-width: 100px;
}

.es-layout-option:hover {
    border-color: #9ca3af;
    background: #f9fafb;
}

.es-layout-option.active {
    border-color: #3b82f6;
    background: #eff6ff;
}

.es-layout-option input {
    display: none;
}

.es-layout-preview {
    width: 50px;
    height: 40px;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.es-layout-preview svg {
    width: 100%;
    height: 100%;
    color: #6b7280;
}

.es-layout-option.active .es-layout-preview svg {
    color: #3b82f6;
}

.es-layout-label {
    font-size: 13px;
    font-weight: 500;
}

/* Info Box */
.es-info-box {
    padding: 20px;
    background: #f0f6fc;
    border-left: 4px solid #0969da;
    margin-top: 20px;
    border-radius: 0 4px 4px 0;
}

.es-info-box h4 {
    margin: 15px 0 8px 0;
    font-size: 13px;
}

.es-info-box h4:first-child {
    margin-top: 0;
}

.es-info-box code {
    display: inline-block;
    margin: 4px 0;
    padding: 4px 8px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 3px;
    font-size: 12px;
}

.es-params-table {
    width: 100%;
    margin-top: 10px;
    border-collapse: collapse;
}

.es-params-table td {
    padding: 8px;
    border-bottom: 1px solid #e5e7eb;
}

.es-params-table td:first-child {
    width: 120px;
}

.es-carousel-settings {
    margin-left: 25px;
    padding-left: 15px;
    border-left: 2px solid #e5e7eb;
}

hr {
    margin: 25px 0;
    border: none;
    border-top: 1px solid #e5e7eb;
}
</style>

<?php
// Helper method for layout icons (would be in class normally)
if (!function_exists('ensemble_gallery_pro_get_layout_icon')) {
    function ensemble_gallery_pro_get_layout_icon($layout) {
        $icons = array(
            'grid' => '<svg viewBox="0 0 50 40"><rect x="2" y="2" width="14" height="11" rx="1" fill="currentColor"/><rect x="18" y="2" width="14" height="11" rx="1" fill="currentColor"/><rect x="34" y="2" width="14" height="11" rx="1" fill="currentColor"/><rect x="2" y="15" width="14" height="11" rx="1" fill="currentColor"/><rect x="18" y="15" width="14" height="11" rx="1" fill="currentColor"/><rect x="34" y="15" width="14" height="11" rx="1" fill="currentColor"/><rect x="2" y="28" width="14" height="11" rx="1" fill="currentColor"/><rect x="18" y="28" width="14" height="11" rx="1" fill="currentColor"/><rect x="34" y="28" width="14" height="11" rx="1" fill="currentColor"/></svg>',
            'masonry' => '<svg viewBox="0 0 50 40"><rect x="2" y="2" width="14" height="16" rx="1" fill="currentColor"/><rect x="18" y="2" width="14" height="10" rx="1" fill="currentColor"/><rect x="34" y="2" width="14" height="22" rx="1" fill="currentColor"/><rect x="2" y="20" width="14" height="18" rx="1" fill="currentColor"/><rect x="18" y="14" width="14" height="24" rx="1" fill="currentColor"/><rect x="34" y="26" width="14" height="12" rx="1" fill="currentColor"/></svg>',
            'carousel' => '<svg viewBox="0 0 50 40"><rect x="6" y="5" width="38" height="24" rx="2" fill="currentColor"/><circle cx="25" cy="35" r="2" fill="currentColor"/><circle cx="19" cy="35" r="1.5" fill="currentColor" opacity="0.5"/><circle cx="31" cy="35" r="1.5" fill="currentColor" opacity="0.5"/><path d="M3 17l4-4v8z" fill="currentColor" opacity="0.7"/><path d="M47 17l-4-4v8z" fill="currentColor" opacity="0.7"/></svg>',
            'justified' => '<svg viewBox="0 0 50 40"><rect x="2" y="2" width="20" height="11" rx="1" fill="currentColor"/><rect x="24" y="2" width="10" height="11" rx="1" fill="currentColor"/><rect x="36" y="2" width="12" height="11" rx="1" fill="currentColor"/><rect x="2" y="15" width="12" height="11" rx="1" fill="currentColor"/><rect x="16" y="15" width="18" height="11" rx="1" fill="currentColor"/><rect x="36" y="15" width="12" height="11" rx="1" fill="currentColor"/><rect x="2" y="28" width="15" height="10" rx="1" fill="currentColor"/><rect x="19" y="28" width="15" height="10" rx="1" fill="currentColor"/><rect x="36" y="28" width="12" height="10" rx="1" fill="currentColor"/></svg>',
            'filmstrip' => '<svg viewBox="0 0 50 40"><rect x="0" y="5" width="50" height="30" rx="1" fill="currentColor" opacity="0.2"/><rect x="4" y="10" width="12" height="20" rx="1" fill="currentColor"/><rect x="19" y="10" width="12" height="20" rx="1" fill="currentColor"/><rect x="34" y="10" width="12" height="20" rx="1" fill="currentColor"/><circle cx="7" cy="7" r="1.5" fill="currentColor"/><circle cx="13" cy="7" r="1.5" fill="currentColor"/><circle cx="25" cy="7" r="1.5" fill="currentColor"/><circle cx="40" cy="7" r="1.5" fill="currentColor"/><circle cx="7" cy="33" r="1.5" fill="currentColor"/><circle cx="13" cy="33" r="1.5" fill="currentColor"/><circle cx="25" cy="33" r="1.5" fill="currentColor"/><circle cx="40" cy="33" r="1.5" fill="currentColor"/></svg>',
        );
        
        return $icons[$layout] ?? '';
    }
}
?>
