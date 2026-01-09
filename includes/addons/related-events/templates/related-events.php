<?php
/**
 * Related Events Template
 * 
 * @package Ensemble
 * @subpackage Addons/RelatedEvents
 * 
 * Variables available:
 * - $event_id (int) - Current event ID
 * - $related_events (array) - Related events (formatted arrays)
 * - $settings (array) - Addon settings
 */

if (!defined('ABSPATH')) {
    exit;
}

if (empty($related_events)) {
    if (!empty($settings['empty_message']) && empty($settings['hide_if_empty'])) {
        echo '<div class="es-related-empty">' . esc_html($settings['empty_message']) . '</div>';
    }
    return;
}

$show_header = !function_exists('ensemble_show_addon_header') || ensemble_show_addon_header('related_events');
$title = !empty($settings['title']) ? $settings['title'] : __('Related Events', 'ensemble');
$layout = isset($settings['layout']) ? $settings['layout'] : 'grid';
$columns = isset($settings['columns']) ? intval($settings['columns']) : 4;
$slides_visible = isset($settings['slides_visible']) ? intval($settings['slides_visible']) : 3;
$hover_mode = isset($settings['hover_mode']) ? $settings['hover_mode'] : 'reveal';

// Build grid classes
$grid_classes = array('es-related-events');
$grid_classes[] = 'es-related-layout-' . esc_attr($layout);
if ($layout === 'grid') {
    $grid_classes[] = 'es-related-cols-' . $columns;
}
if ($layout === 'slider') {
    $grid_classes[] = 'es-related-slides-' . $slides_visible;
}
// Add hover mode class
if ($hover_mode === 'inverted') {
    $grid_classes[] = 'es-related-hover-inverted';
}
?>

<div class="es-related-events-section">
    
    <?php if ($show_header): ?>
    <h2 class="es-section-title"><?php echo esc_html($title); ?></h2>
    <?php endif; ?>
    
    <div class="<?php echo esc_attr(implode(' ', $grid_classes)); ?>">
        <?php foreach ($related_events as $event): ?>
        <article class="es-related-event-card">
            <a href="<?php echo esc_url($event['url']); ?>">
                
                <?php if (!empty($settings['show_image']) && !empty($event['image'])): ?>
                <div class="es-related-event-image">
                    <img src="<?php echo esc_url($event['image']); ?>" alt="<?php echo esc_attr($event['title']); ?>">
                    
                    <?php if (!empty($settings['show_category']) && !empty($event['categories'])): ?>
                    <span class="es-related-event-category">
                        <?php echo esc_html($event['categories'][0]); ?>
                    </span>
                    <?php endif; ?>
                    
                    <!-- Hover Overlay with Title -->
                    <div class="es-related-event-overlay">
                        <h4 class="es-related-event-title"><?php echo esc_html($event['title']); ?></h4>
                        
                        <div class="es-related-event-meta">
                            <?php if (!empty($settings['show_date']) && !empty($event['date_formatted'])): ?>
                            <span class="es-related-event-date">
                                <?php echo esc_html($event['date_formatted']); ?>
                            </span>
                            <?php endif; ?>
                            
                            <?php if (!empty($settings['show_location']) && !empty($event['location'])): ?>
                            <span class="es-related-event-location">
                                <?php echo esc_html($event['location']); ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <!-- Fallback without image -->
                <div class="es-related-event-content">
                    <h4 class="es-related-event-title"><?php echo esc_html($event['title']); ?></h4>
                    
                    <div class="es-related-event-meta">
                        <?php if (!empty($settings['show_date']) && !empty($event['date_formatted'])): ?>
                        <span class="es-related-event-date">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <?php echo esc_html($event['date_formatted']); ?>
                            <?php if (!empty($event['time'])): ?>
                            <span class="es-related-event-time"><?php echo esc_html($event['time']); ?></span>
                            <?php endif; ?>
                        </span>
                        <?php endif; ?>
                        
                        <?php if (!empty($settings['show_location']) && !empty($event['location'])): ?>
                        <span class="es-related-event-location">
                            <span class="dashicons dashicons-location"></span>
                            <?php echo esc_html($event['location']); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
            </a>
        </article>
        <?php endforeach; ?>
    </div>
    
</div>
