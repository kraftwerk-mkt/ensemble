<?php
/**
 * Visual Calendar - Main Grid Template
 * 
 * @package Ensemble
 * @subpackage Addons/VisualCalendar
 * 
 * Variables available (via load_template):
 * - $calendar_id (string) - Unique calendar ID
 * - $calendar_data (array) - Calendar data with grid and events
 * - $year (int) - Current year
 * - $month (int) - Current month
 * - $show_weekdays (bool) - Show weekday headers
 * - $show_navigation (bool) - Show month navigation
 * - $show_empty_days (bool) - Show empty day cells
 * - $color_scheme (string) - dark, light, auto
 * - $category (string) - Category filter
 * - $location (string) - Location filter
 * - $settings (array) - Add-on settings
 */

if (!defined('ABSPATH')) exit;
?>

<div id="<?php echo esc_attr($calendar_id); ?>" 
     class="es-visual-calendar es-vc-scheme-<?php echo esc_attr($color_scheme); ?>"
     data-year="<?php echo esc_attr($year); ?>"
     data-month="<?php echo esc_attr($month); ?>"
     data-category="<?php echo esc_attr($category); ?>"
     data-location="<?php echo esc_attr($location); ?>">
    
    <?php if ($show_navigation): ?>
    <!-- Navigation Header -->
    <div class="es-vc-header">
        <button type="button" class="es-vc-nav es-vc-prev" aria-label="<?php esc_attr_e('Previous Month', 'ensemble'); ?>">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M15 18l-6-6 6-6"/>
            </svg>
        </button>
        
        <h2 class="es-vc-title"><?php echo esc_html($calendar_data['month_name']); ?></h2>
        
        <button type="button" class="es-vc-nav es-vc-next" aria-label="<?php esc_attr_e('Next Month', 'ensemble'); ?>">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 18l6-6-6-6"/>
            </svg>
        </button>
    </div>
    <?php endif; ?>
    
    <?php if ($show_weekdays): ?>
    <!-- Weekday Headers -->
    <div class="es-vc-weekdays">
        <?php foreach ($calendar_data['weekdays'] as $weekday): ?>
        <div class="es-vc-weekday"><?php echo esc_html($weekday); ?></div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <!-- Calendar Grid -->
    <div class="es-vc-grid" data-loading="false">
        <?php 
        // Include cells template
        include dirname(__FILE__) . '/calendar-cells.php';
        ?>
    </div>
    
    <!-- Loading Overlay -->
    <div class="es-vc-loading">
        <div class="es-vc-spinner"></div>
    </div>
    
</div>
