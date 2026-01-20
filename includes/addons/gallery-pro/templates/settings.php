<?php
/**
 * Gallery Pro - Settings Template
 * 
 * @package Ensemble
 * @subpackage Addons/Gallery Pro
 * @since 3.0.0
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
                <small><?php _e('Embed YouTube, Vimeo, and self-hosted videos in the gallery', 'ensemble'); ?></small>
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
                <small><?php _e('Automatically start video playback in lightbox', 'ensemble'); ?></small>
            </span>
        </label>
    </div>
    
    <hr>
    
    <!-- Usage Info -->
    <h3><?php _e('Usage', 'ensemble'); ?></h3>
    
    <div class="es-info-box">
        <h4><?php _e('Automatic Display', 'ensemble'); ?></h4>
        <p><?php _e('Galleries are automatically displayed on event, artist, and location pages when they have images or linked galleries.', 'ensemble'); ?></p>
        
        <h4><?php _e('Shortcode', 'ensemble'); ?></h4>
        <table class="es-shortcode-examples">
            <tr>
                <td><code>[ensemble_gallery]</code></td>
                <td><?php _e('Gallery of the current event/artist/location', 'ensemble'); ?></td>
            </tr>
            <tr>
                <td><code>[ensemble_gallery id="123"]</code></td>
                <td><?php _e('Specific gallery from Gallery Manager', 'ensemble'); ?></td>
            </tr>
            <tr>
                <td><code>[ensemble_gallery event="123"]</code></td>
                <td><?php _e('Gallery of a specific event', 'ensemble'); ?></td>
            </tr>
            <tr>
                <td><code>[ensemble_gallery artist="456"]</code></td>
                <td><?php _e('Gallery of a specific artist', 'ensemble'); ?></td>
            </tr>
            <tr>
                <td><code>[ensemble_gallery location="789"]</code></td>
                <td><?php _e('Gallery of a specific location', 'ensemble'); ?></td>
            </tr>
            <tr>
                <td><code>[ensemble_gallery layout="masonry" columns="3"]</code></td>
                <td><?php _e('With layout options', 'ensemble'); ?></td>
            </tr>
            <tr>
                <td><code>[ensemble_gallery ids="1,2,3,4"]</code></td>
                <td><?php _e('Specific attachment IDs', 'ensemble'); ?></td>
            </tr>
        </table>
        
        <h4><?php _e('Shortcode Parameters', 'ensemble'); ?></h4>
        <table class="es-params-table">
            <tr><td><code>id</code></td><td><?php _e('Gallery Manager ID', 'ensemble'); ?></td></tr>
            <tr><td><code>event</code></td><td><?php _e('Event ID', 'ensemble'); ?></td></tr>
            <tr><td><code>artist</code></td><td><?php _e('Artist ID', 'ensemble'); ?></td></tr>
            <tr><td><code>location</code></td><td><?php _e('Location ID', 'ensemble'); ?></td></tr>
            <tr><td><code>layout</code></td><td><?php _e('grid, masonry, carousel, filmstrip', 'ensemble'); ?></td></tr>
            <tr><td><code>columns</code></td><td><?php _e('Number of columns (2-8)', 'ensemble'); ?></td></tr>
            <tr><td><code>captions</code></td><td><?php _e('true/false - Show captions', 'ensemble'); ?></td></tr>
            <tr><td><code>lightbox</code></td><td><?php _e('true/false - Enable lightbox', 'ensemble'); ?></td></tr>
            <tr><td><code>max</code></td><td><?php _e('Maximum number of items', 'ensemble'); ?></td></tr>
            <tr><td><code>ids</code></td><td><?php _e('Comma-separated attachment IDs', 'ensemble'); ?></td></tr>
        </table>
        
        <h4><?php _e('Supported Video Sources', 'ensemble'); ?></h4>
        <ul class="es-video-sources">
            <li><strong>YouTube</strong> - youtube.com/watch?v=xxx, youtu.be/xxx</li>
            <li><strong>Vimeo</strong> - vimeo.com/123456789</li>
            <li><strong><?php _e('Self-hosted', 'ensemble'); ?></strong> - MP4, WebM, OGG, MOV</li>
        </ul>
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
    color: var(--es-addon-text, #1a1a1a);
}

.es-form-group .description {
    margin-top: 6px;
    font-size: 13px;
    color: var(--es-addon-text-secondary, #666);
}

.es-input-number,
.es-select {
    width: 100%;
    max-width: 200px;
    padding: 8px 12px;
    border: 1px solid var(--es-addon-border, #ddd);
    border-radius: 4px;
    background: var(--es-addon-surface, #fff);
    color: var(--es-addon-text, #1a1a1a);
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
    border: 2px solid var(--es-addon-border, #e5e7eb);
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
    min-width: 100px;
    background: var(--es-addon-surface, #fff);
}

.es-layout-option:hover {
    border-color: var(--es-addon-text-secondary, #9ca3af);
}

.es-layout-option.active {
    border-color: var(--ensemble-primary, #3b82f6);
    background: rgba(59, 130, 246, 0.1);
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
    color: var(--es-addon-text-secondary, #6b7280);
}

.es-layout-option.active .es-layout-preview svg {
    color: var(--ensemble-primary, #3b82f6);
}

.es-layout-label {
    font-size: 13px;
    font-weight: 500;
    color: var(--es-addon-text, #1a1a1a);
}

/* Toggle */
.es-toggle {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    cursor: pointer;
}

.es-toggle input {
    display: none;
}

.es-toggle-track {
    position: relative;
    width: 44px;
    height: 24px;
    background: var(--es-addon-border, #d1d5db);
    border-radius: 12px;
    transition: background 0.2s;
    flex-shrink: 0;
}

.es-toggle-track::after {
    content: '';
    position: absolute;
    top: 2px;
    left: 2px;
    width: 20px;
    height: 20px;
    background: white;
    border-radius: 50%;
    transition: transform 0.2s;
    box-shadow: 0 1px 3px rgba(0,0,0,0.2);
}

.es-toggle input:checked + .es-toggle-track {
    background: var(--ensemble-primary, #3b82f6);
}

.es-toggle input:checked + .es-toggle-track::after {
    transform: translateX(20px);
}

.es-toggle-label {
    display: flex;
    flex-direction: column;
    gap: 2px;
    color: var(--es-addon-text, #1a1a1a);
}

.es-toggle-label small {
    font-size: 12px;
    color: var(--es-addon-text-secondary, #6b7280);
    font-weight: normal;
}

/* Info Box */
.es-info-box {
    padding: 20px;
    background: var(--es-addon-surface, #f0f6fc);
    border-left: 4px solid var(--ensemble-primary, #0969da);
    margin-top: 20px;
    border-radius: 0 4px 4px 0;
}

.es-info-box h4 {
    margin: 15px 0 8px 0;
    font-size: 13px;
    color: var(--es-addon-text, #1a1a1a);
}

.es-info-box h4:first-child {
    margin-top: 0;
}

.es-info-box p {
    margin: 0 0 10px;
    color: var(--es-addon-text-secondary, #666);
}

.es-info-box code {
    display: inline-block;
    padding: 4px 8px;
    background: var(--es-addon-bg, #fff);
    border: 1px solid var(--es-addon-border, #ddd);
    border-radius: 3px;
    font-size: 12px;
}

.es-shortcode-examples {
    width: 100%;
    margin: 10px 0;
    border-collapse: collapse;
}

.es-shortcode-examples td {
    padding: 8px;
    border-bottom: 1px solid var(--es-addon-border, #e5e7eb);
    vertical-align: top;
}

.es-shortcode-examples td:first-child {
    width: 280px;
}

.es-params-table {
    width: 100%;
    margin-top: 10px;
    border-collapse: collapse;
}

.es-params-table td {
    padding: 8px;
    border-bottom: 1px solid var(--es-addon-border, #e5e7eb);
}

.es-params-table td:first-child {
    width: 120px;
}

.es-video-sources {
    margin: 10px 0;
    padding-left: 20px;
}

.es-video-sources li {
    margin-bottom: 6px;
    color: var(--es-addon-text-secondary, #666);
}

.es-carousel-settings {
    margin-left: 25px;
    padding-left: 15px;
    border-left: 2px solid var(--es-addon-border, #e5e7eb);
}

hr {
    margin: 25px 0;
    border: none;
    border-top: 1px solid var(--es-addon-border, #e5e7eb);
}
</style>
