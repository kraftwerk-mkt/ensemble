<?php
/**
 * Visual Calendar - Day Cells Template
 * 
 * This template is used for both initial render and AJAX updates
 * 
 * @package Ensemble
 * @subpackage Addons/VisualCalendar
 * 
 * Variables available:
 * - $calendar_data (array) - Calendar data with grid and events
 * - $show_empty_days (bool) - Show empty day cells
 * - $settings (array) - Add-on settings
 */

if (!defined('ABSPATH')) exit;

$today = date('Y-m-d');

foreach ($calendar_data['grid'] as $cell):
    $date = $cell['date'];
    $day = $cell['day'];
    $events = $cell['events'];
    $event_count = $cell['event_count'];
    $is_current_month = $cell['is_current_month'];
    $is_today = $cell['is_today'];
    
    // Cell classes
    $cell_classes = array('es-vc-cell');
    if (!$is_current_month) $cell_classes[] = 'es-vc-other-month';
    if ($is_today) $cell_classes[] = 'es-vc-today';
    if ($event_count === 0) $cell_classes[] = 'es-vc-empty';
    if ($event_count === 1) $cell_classes[] = 'es-vc-single';
    if ($event_count >= 2) $cell_classes[] = 'es-vc-multiple';
    
    // Skip empty days in other months if setting is off
    if (!$show_empty_days && !$is_current_month && $event_count === 0) {
        continue;
    }
?>

<div class="<?php echo esc_attr(implode(' ', $cell_classes)); ?>" data-date="<?php echo esc_attr($date); ?>">
    
    <!-- Date Badge -->
    <div class="es-vc-date-badge">
        <span class="es-vc-day"><?php echo esc_html($day); ?></span>
    </div>
    
    <?php if ($event_count === 0): ?>
        <!-- Empty Day -->
        <div class="es-vc-no-events">
            <span><?php echo esc_html($settings['empty_day_text'] ?? __('No Events', 'ensemble')); ?></span>
        </div>
        
    <?php elseif ($event_count === 1): ?>
        <!-- Single Event -->
        <?php 
        $event = $events[0]; 
        $is_multi_day = !empty($event['is_multi_day']);
        $is_permanent = !empty($event['is_permanent']);
        $multi_day_position = $event['multi_day_position'] ?? null;
        $event_link_classes = 'es-vc-event-link';
        if ($is_multi_day) $event_link_classes .= ' es-vc-multi-day es-vc-multi-day-' . $multi_day_position;
        if ($is_permanent) $event_link_classes .= ' es-vc-permanent';
        ?>
        <a href="<?php echo esc_url($event['permalink']); ?>" class="<?php echo esc_attr($event_link_classes); ?>">
            <?php if ($event['thumbnail']): ?>
            <div class="es-vc-event-image" style="background-image: url('<?php echo esc_url($event['thumbnail']); ?>');">
            </div>
            <?php else: ?>
            <div class="es-vc-event-image es-vc-no-image" style="background-color: <?php echo esc_attr($event['category_color']); ?>;">
            </div>
            <?php endif; ?>
            
            <div class="es-vc-event-overlay">
                <?php if ($is_multi_day && $multi_day_position === 'start'): ?>
                <span class="es-vc-type-badge es-vc-multi-day-badge" title="<?php 
                    if (!empty($event['multi_day_start']) && !empty($event['multi_day_end'])) {
                        echo esc_attr(date_i18n('j. M', strtotime($event['multi_day_start'])) . ' - ' . date_i18n('j. M', strtotime($event['multi_day_end'])));
                    }
                ?>">
                    <span class="dashicons dashicons-calendar-alt"></span>
                </span>
                <?php elseif ($is_permanent): ?>
                <span class="es-vc-type-badge es-vc-permanent-badge">
                    <span class="dashicons dashicons-admin-home"></span>
                </span>
                <?php endif; ?>
                
                <?php if ($event['status'] === 'sold_out'): ?>
                <span class="es-vc-status-badge es-vc-sold-out"><?php _e('Sold Out', 'ensemble'); ?></span>
                <?php elseif ($event['status'] === 'cancelled'): ?>
                <span class="es-vc-status-badge es-vc-cancelled"><?php _e('Cancelled', 'ensemble'); ?></span>
                <?php endif; ?>
            </div>
            
            <div class="es-vc-event-info">
                <?php if ($settings['show_time'] && $event['time'] && (!$is_multi_day || $multi_day_position === 'start')): ?>
                <span class="es-vc-event-time"><?php echo esc_html($event['time']); ?></span>
                <?php endif; ?>
                <span class="es-vc-event-title"><?php echo esc_html($event['title']); ?></span>
                <?php if ($settings['show_location'] && $event['location']): ?>
                <span class="es-vc-event-location"><?php echo esc_html($event['location']); ?></span>
                <?php endif; ?>
            </div>
        </a>
        
    <?php else: ?>
        <!-- Multiple Events - Split View -->
        <div class="es-vc-multi-events" data-count="<?php echo esc_attr($event_count); ?>">
            
            <?php 
            // Show first event as main background
            $main_event = $events[0];
            $second_event = isset($events[1]) ? $events[1] : null;
            ?>
            
            <!-- Split Background -->
            <div class="es-vc-split-bg">
                <div class="es-vc-split-top">
                    <?php if ($main_event['thumbnail']): ?>
                    <div class="es-vc-event-image" style="background-image: url('<?php echo esc_url($main_event['thumbnail']); ?>');"></div>
                    <?php else: ?>
                    <div class="es-vc-event-image es-vc-no-image" style="background-color: <?php echo esc_attr($main_event['category_color']); ?>;"></div>
                    <?php endif; ?>
                </div>
                
                <?php if ($second_event): ?>
                <div class="es-vc-split-bottom">
                    <?php if ($second_event['thumbnail']): ?>
                    <div class="es-vc-event-image" style="background-image: url('<?php echo esc_url($second_event['thumbnail']); ?>');"></div>
                    <?php else: ?>
                    <div class="es-vc-event-image es-vc-no-image" style="background-color: <?php echo esc_attr($second_event['category_color']); ?>;"></div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Event Count Badge -->
            <?php if ($settings['show_event_count']): ?>
            <div class="es-vc-count-badge">
                <?php echo esc_html($event_count); ?> <?php _e('Events', 'ensemble'); ?>
            </div>
            <?php endif; ?>
            
            <!-- Events List (shown on hover/click) -->
            <div class="es-vc-events-dropdown">
                <?php foreach ($events as $event): ?>
                <a href="<?php echo esc_url($event['permalink']); ?>" class="es-vc-dropdown-event">
                    <?php if ($event['thumbnail']): ?>
                    <img src="<?php echo esc_url($event['thumbnail']); ?>" alt="" class="es-vc-dropdown-thumb">
                    <?php endif; ?>
                    <div class="es-vc-dropdown-info">
                        <span class="es-vc-dropdown-title"><?php echo esc_html($event['title']); ?></span>
                        <?php if ($event['time']): ?>
                        <span class="es-vc-dropdown-time"><?php echo esc_html($event['time']); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($event['status'] === 'sold_out'): ?>
                    <span class="es-vc-dropdown-status"><?php _e('Sold Out', 'ensemble'); ?></span>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>
            
        </div>
    <?php endif; ?>
    
</div>

<?php endforeach; ?>
