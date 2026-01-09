<?php
/**
 * Ensemble Calendar AJAX Handler
 * Handles drag & drop event moving
 *
 * @package Ensemble
 * @since 1.6.0
 */

if (!defined('ABSPATH')) exit;

class ES_Calendar_Ajax {
    
    /**
     * Initialize
     */
    public static function init() {
        add_action('wp_ajax_es_move_event', array(__CLASS__, 'move_event'));
        add_action('wp_ajax_es_get_mini_calendar', array(__CLASS__, 'get_mini_calendar'));
    }
    
    /**
     * Move event to new date via drag & drop
     */
    public static function move_event() {
        check_ajax_referer('ensemble_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Permission denied', 'ensemble')));
        }
        
        $event_id = sanitize_text_field($_POST['event_id']);
        $new_date = sanitize_text_field($_POST['new_date']);
        $new_time = isset($_POST['new_time']) ? sanitize_text_field($_POST['new_time']) : null;
        
        // Check if virtual event
        $is_virtual = strpos($event_id, 'virtual_') === 0;
        
        if ($is_virtual) {
            $result = self::convert_virtual_event($event_id, $new_date, $new_time);
        } else {
            $result = self::update_real_event($event_id, $new_date, $new_time);
        }
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Convert virtual event to real event exception
     */
    private static function convert_virtual_event($virtual_id, $new_date, $new_time) {
        // Parse: virtual_{parent_id}_{YYYYMMDD}
        $parts = explode('_', $virtual_id);
        
        if (count($parts) !== 3) {
            return array(
                'success' => false,
                'message' => __('Invalid virtual event ID', 'ensemble') . ' (Format: ' . $virtual_id . ')'
            );
        }
        
        $parent_id = intval($parts[1]);
        $original_date = $parts[2]; // YYYYMMDD
        
        // Convert to Y-m-d
        $original_date_formatted = substr($original_date, 0, 4) . '-' . 
                                   substr($original_date, 4, 2) . '-' . 
                                   substr($original_date, 6, 2);
        
        // Get parent event
        $parent = get_post($parent_id);
        
        // Events are stored as regular WordPress posts, not custom post type!
        if (!$parent || $parent->post_type !== 'post') {
            return array(
                'success' => false,
                'message' => __('Parent event not found', 'ensemble') . ' (ID: ' . $parent_id . ', Type: ' . ($parent ? $parent->post_type : 'none') . ')'
            );
        }
        
        // Create new exception event
        $new_event_data = array(
            'post_title' => $parent->post_title,
            'post_content' => $parent->post_content,
            'post_status' => 'publish',
            'post_type' => 'post',  // Events are regular posts!
        );
        
        $new_event_id = wp_insert_post($new_event_data);
        
        if (is_wp_error($new_event_id)) {
            return array(
                'success' => false,
                'message' => __('Failed to create exception event', 'ensemble')
            );
        }
        
        // Copy ensemble_category taxonomy (IMPORTANT - events need this!)
        $parent_categories = wp_get_post_terms($parent_id, 'ensemble_category', array('fields' => 'ids'));
        if (!is_wp_error($parent_categories) && !empty($parent_categories)) {
            wp_set_post_terms($new_event_id, $parent_categories, 'ensemble_category');
        }
        
        // Copy all ACF fields from parent using ensemble helpers
        $fields_to_copy = array(
            'event_location',
            'event_artist',
            'event_description',
            'event_price',
            'event_time_end',
        );
        
        foreach ($fields_to_copy as $field) {
            $value = ensemble_get_field($field, $parent_id);
            if ($value !== null && $value !== '') {
                ensemble_update_field($field, $value, $new_event_id);
            }
        }
        
        // Set new date and time using ensemble helpers
        ensemble_update_field('event_date', $new_date, $new_event_id);
        
        if ($new_time) {
            ensemble_update_field('event_time', $new_time, $new_event_id);
            ensemble_update_field('event_time_start', $new_time, $new_event_id);
        } else {
            ensemble_update_field('event_time', '', $new_event_id);
            ensemble_update_field('event_time_start', '', $new_event_id);
        }
        
        // Mark as converted from virtual - store parent ID for restore functionality
        // Use the same meta key as class-es-virtual-events.php line 323
        update_post_meta($new_event_id, '_es_recurring_parent', $parent_id);
        
        // Add exception to parent using recurring engine
        $recurring_engine = new ES_Recurring_Engine();
        $recurring_engine->add_exception($parent_id, $original_date_formatted);
        
        return array(
            'success' => true,
            'new_event_id' => $new_event_id,
            'was_virtual' => true,
            'message' => __('Event moved and converted to exception', 'ensemble')
        );
    }
    
    /**
     * Update real event date/time
     */
    private static function update_real_event($event_id, $new_date, $new_time) {
        // Convert to integer for real events
        $event_id = intval($event_id);
        
        $event = get_post($event_id);
        
        // Events are stored as regular WordPress posts, not custom post type!
        if (!$event || $event->post_type !== 'post') {
            return array(
                'success' => false,
                'message' => __('Event not found', 'ensemble') . ' (ID: ' . $event_id . ', Type: ' . ($event ? $event->post_type : 'none') . ')'
            );
        }
        
        // Update date using ensemble helper (handles ACF field mapping)
        ensemble_update_field('event_date', $new_date, $event_id);
        
        // Update time
        if ($new_time) {
            ensemble_update_field('event_time', $new_time, $event_id);
            ensemble_update_field('event_time_start', $new_time, $event_id);
        } else {
            // All day event - clear time fields
            ensemble_update_field('event_time', '', $event_id);
            ensemble_update_field('event_time_start', '', $event_id);
        }
        
        return array(
            'success' => true,
            'event_id' => $event_id,
            'was_virtual' => false,
            'message' => __('Event moved successfully', 'ensemble')
        );
    }
    
    /**
     * Get mini calendar HTML for a specific month
     */
    public static function get_mini_calendar() {
        check_ajax_referer('ensemble_nonce', 'nonce');
        
        $year = isset($_POST['year']) ? intval($_POST['year']) : date('Y');
        $month = isset($_POST['month']) ? intval($_POST['month']) : date('n');
        
        $calendar = new ES_Calendar();
        $grid = $calendar->get_month_grid($year, $month);
        
        // Get events for this month
        $events = $calendar->get_events_for_month($year, $month);
        $events_by_date = array();
        foreach ($events as $event) {
            $events_by_date[$event['date']] = true;
        }
        
        // Get selected date from current URL
        $selected_date = isset($_GET['date']) ? sanitize_text_field($_GET['date']) : date('Y-m-d');
        $today = date('Y-m-d');
        
        // Generate HTML
        $html = '';
        foreach ($grid as $cell) {
            $class = 'es-mini-day';
            if (!$cell['is_current_month']) $class .= ' es-other-month';
            if ($cell['is_today']) $class .= ' es-today';
            if ($cell['date'] === $selected_date) $class .= ' es-selected';
            if (isset($events_by_date[$cell['date']])) $class .= ' es-has-events';
            
            $html .= sprintf(
                '<span class="%s" data-date="%s">%d</span>',
                esc_attr($class),
                esc_attr($cell['date']),
                $cell['day']
            );
        }
        
        // Month label
        $timestamp = mktime(0, 0, 0, $month, 1, $year);
        $label = date_i18n('F Y', $timestamp);
        
        wp_send_json_success(array(
            'html' => $html,
            'label' => $label,
            'year' => $year,
            'month' => $month
        ));
    }
}

// Initialize
ES_Calendar_Ajax::init();
