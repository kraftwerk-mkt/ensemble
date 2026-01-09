<?php
/**
 * Recurring Events AJAX Handler
 * 
 * Handles AJAX requests for recurring events
 *
 * @package Ensemble
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class ES_Recurring_AJAX {
    
    private $engine;
    private $virtual;
    
    public function __construct() {
        $this->engine = new ES_Recurring_Engine();
        $this->virtual = new ES_Virtual_Events();
    }
    
    /**
     * Preview recurring instances
     * 
     * Generate and return preview of recurring event instances
     */
    public function preview_instances() {
        check_ajax_referer('ensemble_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
        
        $rules = isset($_POST['rules']) ? $_POST['rules'] : array();
        
        if (empty($rules)) {
            wp_send_json_error(array('message' => 'No rules provided'));
            return;
        }
        
        // Sanitize rules
        $rules = $this->sanitize_rules($rules);
        
        // Generate preview
        // Calculate how many months we need based on the rules
        $months = 12; // Default to 12 months
        
        // If "After X occurrences" is selected, calculate required months
        if (isset($rules['end_type']) && $rules['end_type'] === 'count' && isset($rules['end_count'])) {
            $count = intval($rules['end_count']);
            $interval = isset($rules['interval']) ? intval($rules['interval']) : 1;
            
            // Calculate months needed based on pattern
            switch ($rules['pattern']) {
                case 'daily':
                    $months = max(3, ceil($count * $interval / 30)); // Rough estimate
                    break;
                case 'weekly':
                    $weekdays = isset($rules['weekdays']) ? count($rules['weekdays']) : 1;
                    $months = max(3, ceil($count * $interval / (4 * $weekdays))); // 4 weeks per month
                    break;
                case 'monthly':
                    $months = max(3, $count * $interval);
                    break;
                case 'custom':
                    $months = 12; // Custom dates define their own range
                    break;
            }
            
            // Cap at 24 months for performance
            $months = min($months, 24);
        } else if (isset($rules['end_type']) && $rules['end_type'] === 'date' && isset($rules['end_date'])) {
            // If "On date" is selected, calculate months until that date
            $start_date = new DateTime($rules['start_date']);
            $end_date_obj = new DateTime($rules['end_date']);
            $interval_obj = $start_date->diff($end_date_obj);
            $months = max(3, $interval_obj->m + ($interval_obj->y * 12));
            $months = min($months, 24);
        } else {
            // "Never" - show 12 months
            $months = 12;
        }
        
        $instances = $this->engine->generate_instances(0, $rules, $months);
        
        // Format instances for display
        $formatted = array();
        foreach ($instances as $instance) {
            $date = new DateTime($instance['date']);
            $formatted[] = array(
                'date' => $instance['date'],
                'formatted_date' => $date->format('D, M j, Y'),
                'time_start' => $instance['time_start'],
                'time_end' => $instance['time_end'],
            );
        }
        
        wp_send_json_success(array(
            'instances' => $formatted,
            'count' => count($formatted),
            'months' => $months,
        ));
    }
    
    /**
     * Get recurring rules for an event
     */
    public function get_rules() {
        check_ajax_referer('ensemble_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
        
        $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
        
        if (!$event_id) {
            wp_send_json_error(array('message' => 'Invalid event ID'));
            return;
        }
        
        $rules = $this->engine->get_rules($event_id);
        $exceptions = $this->engine->get_exceptions($event_id);
        
        wp_send_json_success(array(
            'rules' => $rules,
            'exceptions' => $exceptions,
            'is_recurring' => $this->engine->is_recurring($event_id),
        ));
    }
    
    /**
     * Save recurring rules for an event
     */
    public function save_rules() {
        check_ajax_referer('ensemble_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
        
        $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
        $rules = isset($_POST['rules']) ? $_POST['rules'] : array();
        
        if (!$event_id) {
            wp_send_json_error(array('message' => 'Invalid event ID'));
            return;
        }
        
        if (empty($rules)) {
            // Remove recurring rules
            $this->engine->remove_rules($event_id);
            wp_send_json_success(array('message' => 'Recurring rules removed'));
            return;
        }
        
        // Sanitize rules
        $rules = $this->sanitize_rules($rules);
        
        // Save rules
        $result = $this->engine->save_rules($event_id, $rules);
        
        if ($result) {
            wp_send_json_success(array('message' => 'Recurring rules saved successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to save recurring rules'));
        }
    }
    
    /**
     * Add an exception (skip a date)
     */
    public function add_exception() {
        check_ajax_referer('ensemble_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
        
        $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
        $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';
        
        if (!$event_id || !$date) {
            wp_send_json_error(array('message' => 'Invalid parameters'));
            return;
        }
        
        $result = $this->engine->add_exception($event_id, $date);
        
        if ($result) {
            wp_send_json_success(array('message' => 'Date excluded successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to add exception'));
        }
    }
    
    /**
     * Remove an exception (restore a date)
     */
    public function remove_exception() {
        check_ajax_referer('ensemble_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
        
        $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
        $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';
        
        if (!$event_id || !$date) {
            wp_send_json_error(array('message' => 'Invalid parameters'));
            return;
        }
        
        $result = $this->engine->remove_exception($event_id, $date);
        
        if ($result) {
            wp_send_json_success(array('message' => 'Date restored successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to remove exception'));
        }
    }
    
    /**
     * Get exceptions for an event
     */
    public function get_exceptions() {
        check_ajax_referer('ensemble_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
        
        $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
        
        if (!$event_id) {
            wp_send_json_error(array('message' => 'Invalid event ID'));
            return;
        }
        
        $exceptions = $this->engine->get_exceptions($event_id);
        
        wp_send_json_success(array('exceptions' => $exceptions));
    }
    
    /**
     * Convert virtual event to real event
     */
    public function convert_to_real() {
        check_ajax_referer('ensemble_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
        
        $virtual_id = isset($_POST['virtual_id']) ? sanitize_text_field($_POST['virtual_id']) : '';
        $modifications = isset($_POST['modifications']) ? $_POST['modifications'] : array();
        
        if (!$virtual_id) {
            wp_send_json_error(array('message' => 'Invalid virtual event ID'));
            return;
        }
        
        $result = $this->virtual->convert_to_real($virtual_id, $modifications);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        } else {
            wp_send_json_success(array(
                'message' => 'Virtual event converted to real event',
                'event_id' => $result,
            ));
        }
    }
    
    /**
     * Delete virtual event (add as exception)
     */
    public function delete_virtual() {
        check_ajax_referer('ensemble_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
        
        $virtual_id = isset($_POST['virtual_id']) ? sanitize_text_field($_POST['virtual_id']) : '';
        
        if (!$virtual_id) {
            wp_send_json_error(array('message' => 'Invalid virtual event ID'));
            return;
        }
        
        $result = $this->virtual->delete_virtual($virtual_id);
        
        if ($result) {
            wp_send_json_success(array('message' => 'Virtual event deleted successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to delete virtual event'));
        }
    }
    
    /**
     * Restore real event back to virtual
     */
    public function restore_to_virtual() {
        check_ajax_referer('ensemble_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
        
        $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
        
        if (!$event_id) {
            wp_send_json_error(array('message' => 'Invalid event ID'));
            return;
        }
        
        // Get parent ID and date
        $parent_id = get_post_meta($event_id, '_es_recurring_parent', true);
        $event_date = get_field('event_date', $event_id);
        
        if (!$parent_id || !$event_date) {
            wp_send_json_error(array('message' => 'This event is not a converted virtual event'));
            return;
        }
        
        // Remove exception from parent
        $this->engine->remove_exception($parent_id, $event_date);
        
        // Delete the real event
        wp_delete_post($event_id, true);
        
        wp_send_json_success(array('message' => 'Event restored to recurring series'));
    }
    
    /**
     * Sanitize recurring rules
     */
    private function sanitize_rules($rules) {
        $sanitized = array();
        
        // Pattern
        $sanitized['pattern'] = isset($rules['pattern']) ? sanitize_text_field($rules['pattern']) : 'weekly';
        
        // Start date
        $sanitized['start_date'] = isset($rules['start_date']) ? sanitize_text_field($rules['start_date']) : date('Y-m-d');
        
        // Time
        $sanitized['time_start'] = isset($rules['time_start']) ? sanitize_text_field($rules['time_start']) : '';
        $sanitized['time_end'] = isset($rules['time_end']) ? sanitize_text_field($rules['time_end']) : '';
        
        // Interval
        $sanitized['interval'] = isset($rules['interval']) ? max(1, intval($rules['interval'])) : 1;
        
        // Weekdays (for weekly pattern)
        if (isset($rules['weekdays']) && is_array($rules['weekdays'])) {
            $sanitized['weekdays'] = array_map('intval', $rules['weekdays']);
        }
        
        // Day of month (for monthly pattern)
        if (isset($rules['day_of_month'])) {
            $sanitized['day_of_month'] = max(1, min(31, intval($rules['day_of_month'])));
        }
        
        // Custom dates (for custom pattern)
        if (isset($rules['custom_dates']) && is_array($rules['custom_dates'])) {
            $sanitized['custom_dates'] = array_map('sanitize_text_field', $rules['custom_dates']);
        }
        
        // End options - CRITICAL: These were missing!
        if (isset($rules['end_type'])) {
            $sanitized['end_type'] = sanitize_text_field($rules['end_type']);
        }
        
        if (isset($rules['end_date'])) {
            $sanitized['end_date'] = sanitize_text_field($rules['end_date']);
        }
        
        if (isset($rules['end_count'])) {
            $sanitized['end_count'] = max(1, intval($rules['end_count']));
        }
        
        return $sanitized;
    }
}
