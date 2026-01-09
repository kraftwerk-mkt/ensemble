<?php
/**
 * Social Sharing - Settings Template
 * 
 * @package Ensemble
 * @subpackage Addons/SocialSharing
 * @updated Color Style Option
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="es-addon-settings-form">
    
    <!-- Grundeinstellungen -->
    <div class="es-settings-section">
        <h3><?php _e('Grundeinstellungen', 'ensemble'); ?></h3>
        
        <div class="es-form-row">
            <label for="share-title"><?php _e('Headline', 'ensemble'); ?></label>
            <input type="text" 
                   id="share-title" 
                   name="title" 
                   value="<?php echo esc_attr($settings['title']); ?>" 
                   class="regular-text">
            <small class="es-field-help"><?php _e('Leave empty to hide the headline.', 'ensemble'); ?></small>
        </div>
        
        <div class="es-form-row">
            <label for="share-style"><?php _e('Darstellung', 'ensemble'); ?></label>
            <select id="share-style" name="style">
                <option value="icons" <?php selected($settings['style'], 'icons'); ?>>
                    <?php _e('Nur Icons', 'ensemble'); ?>
                </option>
                <option value="icons-text" <?php selected($settings['style'], 'icons-text'); ?>>
                    <?php _e('Icons mit Text', 'ensemble'); ?>
                </option>
                <option value="text" <?php selected($settings['style'], 'text'); ?>>
                    <?php _e('Nur Text', 'ensemble'); ?>
                </option>
            </select>
        </div>
        
        <div class="es-form-row">
            <label for="share-icon-style"><?php _e('Icon-Form', 'ensemble'); ?></label>
            <select id="share-icon-style" name="icon_style">
                <option value="rounded" <?php selected($settings['icon_style'], 'rounded'); ?>>
                    <?php _e('Abgerundet', 'ensemble'); ?>
                </option>
                <option value="square" <?php selected($settings['icon_style'], 'square'); ?>>
                    <?php _e('Eckig', 'ensemble'); ?>
                </option>
                <option value="circle" <?php selected($settings['icon_style'], 'circle'); ?>>
                    <?php _e('Rund', 'ensemble'); ?>
                </option>
            </select>
        </div>
        
        <div class="es-form-row">
            <label for="share-color-style"><?php _e('Farbstil', 'ensemble'); ?></label>
            <select id="share-color-style" name="color_style">
                <option value="brand" <?php selected($settings['color_style'], 'brand'); ?>>
                    <?php _e('Brand Colors (Facebook blue, WhatsApp green, ...)', 'ensemble'); ?>
                </option>
                <option value="theme" <?php selected($settings['color_style'], 'theme'); ?>>
                    <?php _e('Theme-Farben (einheitlich, aus Designer)', 'ensemble'); ?>
                </option>
                <option value="outline" <?php selected($settings['color_style'], 'outline'); ?>>
                    <?php _e('Outline (nur Rahmen, minimalistisch)', 'ensemble'); ?>
                </option>
                <option value="subtle" <?php selected($settings['color_style'], 'subtle'); ?>>
                    <?php _e('Subtle (muted colors)', 'ensemble'); ?>
                </option>
            </select>
            <small class="es-field-help"><?php _e('Markenfarben = Originalfarben der Plattformen. Theme-Farben = nutzt die Primary/Secondary-Farben aus dem Designer.', 'ensemble'); ?></small>
        </div>
    </div>
    
    <!-- Plattformen -->
    <div class="es-settings-section">
        <h3><?php _e('Plattformen', 'ensemble'); ?></h3>
        
        <div class="es-toggle-group">
            <label class="es-toggle es-toggle--reverse es-toggle--block">
                <input type="checkbox" name="show_facebook" value="1" <?php checked($settings['show_facebook']); ?>>
                <span class="es-toggle-track"></span>
                <span class="es-toggle-label"><?php _e('Facebook', 'ensemble'); ?></span>
            </label>
            
            <label class="es-toggle es-toggle--reverse es-toggle--block">
                <input type="checkbox" name="show_twitter" value="1" <?php checked($settings['show_twitter']); ?>>
                <span class="es-toggle-track"></span>
                <span class="es-toggle-label"><?php _e('X (Twitter)', 'ensemble'); ?></span>
            </label>
            
            <label class="es-toggle es-toggle--reverse es-toggle--block">
                <input type="checkbox" name="show_whatsapp" value="1" <?php checked($settings['show_whatsapp']); ?>>
                <span class="es-toggle-track"></span>
                <span class="es-toggle-label"><?php _e('WhatsApp', 'ensemble'); ?></span>
            </label>
            
            <label class="es-toggle es-toggle--reverse es-toggle--block">
                <input type="checkbox" name="show_telegram" value="1" <?php checked($settings['show_telegram']); ?>>
                <span class="es-toggle-track"></span>
                <span class="es-toggle-label"><?php _e('Telegram', 'ensemble'); ?></span>
            </label>
            
            <label class="es-toggle es-toggle--reverse es-toggle--block">
                <input type="checkbox" name="show_linkedin" value="1" <?php checked($settings['show_linkedin']); ?>>
                <span class="es-toggle-track"></span>
                <span class="es-toggle-label"><?php _e('LinkedIn', 'ensemble'); ?></span>
            </label>
            
            <label class="es-toggle es-toggle--reverse es-toggle--block">
                <input type="checkbox" name="show_email" value="1" <?php checked($settings['show_email']); ?>>
                <span class="es-toggle-track"></span>
                <span class="es-toggle-label"><?php _e('E-Mail', 'ensemble'); ?></span>
            </label>
            
            <label class="es-toggle es-toggle--reverse es-toggle--block">
                <input type="checkbox" name="show_copy_link" value="1" <?php checked($settings['show_copy_link']); ?>>
                <span class="es-toggle-track"></span>
                <span class="es-toggle-label"><?php _e('Link kopieren', 'ensemble'); ?></span>
            </label>
            
            <label class="es-toggle es-toggle--reverse es-toggle--block">
                <input type="checkbox" name="show_native_share" value="1" <?php checked($settings['show_native_share']); ?>>
                <span class="es-toggle-track"></span>
                <span class="es-toggle-label">
                    <?php _e('Natives Teilen (Mobile)', 'ensemble'); ?>
                    <small><?php _e('Shows a "More" button on smartphones that opens the native share menu (with Instagram, TikTok, etc.)', 'ensemble'); ?></small>
                </span>
            </label>
        </div>
    </div>
    
</div>
