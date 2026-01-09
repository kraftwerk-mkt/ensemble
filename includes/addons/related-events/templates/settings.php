<?php
/**
 * Related Events - Settings Template
 * 
 * @package Ensemble
 * @subpackage Addons/RelatedEvents
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="es-addon-settings-form">
    
    <!-- Relation Type -->
    <div class="es-settings-section">
        <h3><?php _e('Relation Type', 'ensemble'); ?></h3>
        <p class="description"><?php _e('How should related events be determined?', 'ensemble'); ?></p>
        
        <div class="es-form-row">
            <label for="related-relation-type"><?php _e('Find related by', 'ensemble'); ?></label>
            <select id="related-relation-type" name="relation_type">
                <option value="category" <?php selected($settings['relation_type'], 'category'); ?>>
                    <?php _e('Same Category', 'ensemble'); ?>
                </option>
                <option value="location" <?php selected($settings['relation_type'], 'location'); ?>>
                    <?php _e('Same Location', 'ensemble'); ?>
                </option>
                <option value="artist" <?php selected($settings['relation_type'], 'artist'); ?>>
                    <?php _e('Same Artist', 'ensemble'); ?>
                </option>
                <option value="mixed" <?php selected($settings['relation_type'], 'mixed'); ?>>
                    <?php _e('Mixed (Category first, then Location)', 'ensemble'); ?>
                </option>
            </select>
            <p class="description">
                <?php _e('Category: Shows events from the same category. Location: Shows events at the same venue. Artist: Shows events with the same artist.', 'ensemble'); ?>
            </p>
        </div>
    </div>
    
    <!-- Display Options -->
    <div class="es-settings-section">
        <h3><?php _e('Display Options', 'ensemble'); ?></h3>
        
        <div class="es-form-row">
            <label for="related-count"><?php _e('Number of Events', 'ensemble'); ?></label>
            <input type="number" 
                   id="related-count" 
                   name="count" 
                   value="<?php echo esc_attr($settings['count']); ?>" 
                   min="1" 
                   max="12"
                   class="small-text">
        </div>
        
        <div class="es-form-row">
            <label for="related-layout"><?php _e('Layout', 'ensemble'); ?></label>
            <select id="related-layout" name="layout">
                <option value="grid" <?php selected($settings['layout'], 'grid'); ?>>
                    <?php _e('Grid', 'ensemble'); ?>
                </option>
                <option value="list" <?php selected($settings['layout'], 'list'); ?>>
                    <?php _e('List', 'ensemble'); ?>
                </option>
                <option value="slider" <?php selected($settings['layout'], 'slider'); ?>>
                    <?php _e('Slider', 'ensemble'); ?>
                </option>
            </select>
        </div>
        
        <div class="es-form-row">
            <label for="related-columns"><?php _e('Columns (Grid)', 'ensemble'); ?></label>
            <select id="related-columns" name="columns">
                <option value="2" <?php selected($settings['columns'], 2); ?>>2</option>
                <option value="3" <?php selected($settings['columns'], 3); ?>>3</option>
                <option value="4" <?php selected($settings['columns'], 4); ?>>4</option>
                <option value="5" <?php selected($settings['columns'], 5); ?>>5</option>
                <option value="6" <?php selected($settings['columns'], 6); ?>>6</option>
            </select>
        </div>
        
        <div class="es-form-row">
            <label for="related-slides-visible"><?php _e('Visible Slides (Slider)', 'ensemble'); ?></label>
            <select id="related-slides-visible" name="slides_visible">
                <option value="2" <?php selected(isset($settings['slides_visible']) ? $settings['slides_visible'] : 3, 2); ?>>2</option>
                <option value="3" <?php selected(isset($settings['slides_visible']) ? $settings['slides_visible'] : 3, 3); ?>>3</option>
                <option value="4" <?php selected(isset($settings['slides_visible']) ? $settings['slides_visible'] : 3, 4); ?>>4</option>
                <option value="5" <?php selected(isset($settings['slides_visible']) ? $settings['slides_visible'] : 3, 5); ?>>5</option>
            </select>
        </div>
        
        <div class="es-form-row">
            <label for="related-hover-mode"><?php _e('Hover Effect', 'ensemble'); ?></label>
            <select id="related-hover-mode" name="hover_mode">
                <option value="reveal" <?php selected(isset($settings['hover_mode']) ? $settings['hover_mode'] : 'reveal', 'reveal'); ?>>
                    <?php _e('Reveal on Hover (Default)', 'ensemble'); ?>
                </option>
                <option value="inverted" <?php selected(isset($settings['hover_mode']) ? $settings['hover_mode'] : 'reveal', 'inverted'); ?>>
                    <?php _e('Always Visible, Hide on Hover', 'ensemble'); ?>
                </option>
            </select>
            <p class="description">
                <?php _e('Reveal: Shows event info on hover. Inverted: Shows info always, hides on hover to reveal full image.', 'ensemble'); ?>
            </p>
        </div>
    </div>
    
    <!-- Content Elements -->
    <div class="es-settings-section">
        <h3><?php _e('Show/Hide Elements', 'ensemble'); ?></h3>
        
        <div class="es-toggle-group">
            <label class="es-toggle es-toggle--reverse es-toggle--block">
                <input type="checkbox" name="show_image" value="1" <?php checked($settings['show_image']); ?>>
                <span class="es-toggle-track"></span>
                <span class="es-toggle-label"><?php _e('Show image', 'ensemble'); ?></span>
            </label>
            
            <label class="es-toggle es-toggle--reverse es-toggle--block">
                <input type="checkbox" name="show_date" value="1" <?php checked($settings['show_date']); ?>>
                <span class="es-toggle-track"></span>
                <span class="es-toggle-label"><?php _e('Show date', 'ensemble'); ?></span>
            </label>
            
            <label class="es-toggle es-toggle--reverse es-toggle--block">
                <input type="checkbox" name="show_location" value="1" <?php checked($settings['show_location']); ?>>
                <span class="es-toggle-track"></span>
                <span class="es-toggle-label"><?php _e('Show location', 'ensemble'); ?></span>
            </label>
            
            <label class="es-toggle es-toggle--reverse es-toggle--block">
                <input type="checkbox" name="show_category" value="1" <?php checked($settings['show_category']); ?>>
                <span class="es-toggle-track"></span>
                <span class="es-toggle-label"><?php _e('Show category', 'ensemble'); ?></span>
            </label>
        </div>
    </div>
    
    <!-- Behavior -->
    <div class="es-settings-section">
        <h3><?php _e('Behavior', 'ensemble'); ?></h3>
        
        <div class="es-toggle-group">
            <label class="es-toggle es-toggle--reverse es-toggle--block">
                <input type="checkbox" name="show_past_events" value="1" <?php checked($settings['show_past_events']); ?>>
                <span class="es-toggle-track"></span>
                <span class="es-toggle-label">
                    <?php _e('Include past events', 'ensemble'); ?>
                    <small><?php _e('By default only upcoming events are shown.', 'ensemble'); ?></small>
                </span>
            </label>
            
            <label class="es-toggle es-toggle--reverse es-toggle--block">
                <input type="checkbox" name="hide_if_empty" value="1" <?php checked($settings['hide_if_empty']); ?>>
                <span class="es-toggle-track"></span>
                <span class="es-toggle-label">
                    <?php _e('Hide section if no results', 'ensemble'); ?>
                    <small><?php _e('Do not show the "Related Events" section when no matching events are found.', 'ensemble'); ?></small>
                </span>
            </label>
        </div>
    </div>
    
    <!-- Text Labels -->
    <div class="es-settings-section">
        <h3><?php _e('Labels', 'ensemble'); ?></h3>
        
        <div class="es-form-row">
            <label for="related-title"><?php _e('Section Title', 'ensemble'); ?></label>
            <input type="text" 
                   id="related-title" 
                   name="title" 
                   value="<?php echo esc_attr($settings['title']); ?>" 
                   class="regular-text"
                   placeholder="<?php _e('Similar Events', 'ensemble'); ?>">
        </div>
        
        <div class="es-form-row">
            <label for="related-empty-message"><?php _e('Empty Message', 'ensemble'); ?></label>
            <input type="text" 
                   id="related-empty-message" 
                   name="empty_message" 
                   value="<?php echo esc_attr($settings['empty_message']); ?>" 
                   class="regular-text"
                   placeholder="<?php _e('No similar events found.', 'ensemble'); ?>">
            <p class="description"><?php _e('Shown when no related events are found (if not hidden).', 'ensemble'); ?></p>
        </div>
    </div>
    
</div>
