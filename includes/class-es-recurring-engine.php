<?php
/**
 * Recurring Engine
 * 
 * Core logic for generating recurring event instances
 *
 * @package Ensemble
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class ES_Recurring_Engine {
    
    /**
     * Check if recurring events are available
     * 
     * @return bool
     */
    public static function is_available() {
        return function_exists('ensemble_is_pro') && ensemble_is_pro();
    }
    
    /**
     * Generate virtual event instances for a recurring event
     * 
     * @param int $parent_id Parent event post ID
     * @param array $rules Recurring rules
     * @param int $months Number of months to generate (default: 12)
     * @return array Array of virtual event instances
     */
    public function generate_instances($parent_id, $rules, $months = 12) {
        // Pro check
        if (!self::is_available()) {
            return array();
        }
        
        if (empty($rules) || !isset($rules['pattern'])) {
            return array();
        }
        
        $instances = array();
        $start_date = new DateTime($rules['start_date']);
        
        // Determine end date
        if (isset($rules['end_type']) && $rules['end_type'] === 'date' && !empty($rules['end_date'])) {
            $end_date = new DateTime($rules['end_date']);
        } else {
            $end_date = clone $start_date;
            $end_date->modify("+{$months} months");
        }
        
        // Get exceptions
        $exceptions = $this->get_exceptions($parent_id);
        
        // Generate based on pattern
        switch ($rules['pattern']) {
            case 'daily':
                $instances = $this->generate_daily($start_date, $end_date, $rules, $exceptions);
                break;
            case 'weekly':
                $instances = $this->generate_weekly($start_date, $end_date, $rules, $exceptions);
                break;
            case 'monthly':
                $instances = $this->generate_monthly($start_date, $end_date, $rules, $exceptions);
                break;
            case 'custom':
                $instances = $this->generate_custom($start_date, $end_date, $rules, $exceptions);
                break;
        }
        
        // Limit by count if specified
        if (isset($rules['end_type']) && $rules['end_type'] === 'count' && !empty($rules['end_count'])) {
            $instances = array_slice($instances, 0, intval($rules['end_count']));
        }
        
        // Add parent ID to each instance
        foreach ($instances as &$instance) {
            $instance['parent_id'] = $parent_id;
            $instance['is_virtual'] = true;
        }
        
        return $instances;
    }
    
    /**
     * Generate daily recurring instances
     */
    private function generate_daily($start_date, $end_date, $rules, $exceptions) {
        $instances = array();
        $interval = isset($rules['interval']) ? max(1, intval($rules['interval'])) : 1;
        $current = clone $start_date;
        
        while ($current <= $end_date) {
            $date_str = $current->format('Y-m-d');
            
            // Check if this date is an exception
            if (!in_array($date_str, $exceptions)) {
                $instances[] = array(
                    'date' => $date_str,
                    'time_start' => $rules['time_start'] ?? '',
                    'time_end' => $rules['time_end'] ?? '',
                );
            }
            
            $current->modify("+{$interval} days");
        }
        
        return $instances;
    }
    
    /**
     * Generate weekly recurring instances
     * 
     * IMPORTANT: The start_date is ALWAYS the first instance.
     * Additional instances are generated based on the selected weekdays.
     */
    private function generate_weekly($start_date, $end_date, $rules, $exceptions) {
        $instances = array();
        $interval = isset($rules['interval']) ? max(1, intval($rules['interval'])) : 1;
        
        // Get the weekday of the start date (1=Monday, 7=Sunday)
        $start_weekday = intval($start_date->format('N'));
        
        // Get selected weekdays, default to the start date's weekday
        $weekdays = isset($rules['weekdays']) ? (array) $rules['weekdays'] : array($start_weekday);
        
        // Make sure weekdays are integers and sorted
        $weekdays = array_map('intval', $weekdays);
        sort($weekdays);
        
        // ALWAYS include the start date as the first instance
        $start_date_str = $start_date->format('Y-m-d');
        if (!in_array($start_date_str, $exceptions)) {
            $instances[] = array(
                'date' => $start_date_str,
                'time_start' => $rules['time_start'] ?? '',
                'time_end' => $rules['time_end'] ?? '',
            );
        }
        
        // Now calculate subsequent instances
        // Start from the Monday of the start_date's week
        $current_week_monday = clone $start_date;
        $current_week_monday->modify('monday this week');
        
        // We need to check the rest of the first week for any other selected weekdays
        // that come AFTER the start date
        foreach ($weekdays as $weekday) {
            // Skip if this weekday is on or before the start date in the first week
            if ($weekday <= $start_weekday) {
                continue;
            }
            
            $day = clone $current_week_monday;
            $day->modify('+' . ($weekday - 1) . ' days');
            
            if ($day <= $end_date) {
                $date_str = $day->format('Y-m-d');
                
                // Don't add duplicates and check exceptions
                if ($date_str !== $start_date_str && !in_array($date_str, $exceptions)) {
                    $instances[] = array(
                        'date' => $date_str,
                        'time_start' => $rules['time_start'] ?? '',
                        'time_end' => $rules['time_end'] ?? '',
                    );
                }
            }
        }
        
        // Move to the next week(s) based on interval
        $current_week_monday->modify("+{$interval} weeks");
        
        // Generate instances for subsequent weeks
        while ($current_week_monday <= $end_date) {
            foreach ($weekdays as $weekday) {
                $day = clone $current_week_monday;
                $day->modify('+' . ($weekday - 1) . ' days');
                
                if ($day <= $end_date) {
                    $date_str = $day->format('Y-m-d');
                    
                    if (!in_array($date_str, $exceptions)) {
                        $instances[] = array(
                            'date' => $date_str,
                            'time_start' => $rules['time_start'] ?? '',
                            'time_end' => $rules['time_end'] ?? '',
                        );
                    }
                }
            }
            
            $current_week_monday->modify("+{$interval} weeks");
        }
        
        // Sort by date and remove any duplicates
        usort($instances, function($a, $b) {
            return strcmp($a['date'], $b['date']);
        });
        
        // Remove duplicates (keep first occurrence)
        $seen_dates = array();
        $unique_instances = array();
        foreach ($instances as $instance) {
            if (!in_array($instance['date'], $seen_dates)) {
                $seen_dates[] = $instance['date'];
                $unique_instances[] = $instance;
            }
        }
        
        return $unique_instances;
    }
    
    /**
     * Generate monthly recurring instances
     * Creates one occurrence per month on the same day of month as the start date
     */
    private function generate_monthly($start_date, $end_date, $rules, $exceptions) {
        $instances = array();
        $interval = isset($rules['interval']) ? max(1, intval($rules['interval'])) : 1;
        
        // Get the day of month from the start date
        $day_of_month = intval($start_date->format('d'));
        
        // Start from the start date
        $current = clone $start_date;
        
        while ($current <= $end_date) {
            $date_str = $current->format('Y-m-d');
            
            // Check if this date is an exception
            if (!in_array($date_str, $exceptions)) {
                $instances[] = array(
                    'date' => $date_str,
                    'time_start' => $rules['time_start'] ?? '',
                    'time_end' => $rules['time_end'] ?? '',
                );
            }
            
            // Move to the same day in the next month (according to interval)
            // Use a more robust approach that handles month-end dates properly
            $current_year = intval($current->format('Y'));
            $current_month = intval($current->format('m'));
            
            // Calculate the target month
            $target_month = $current_month + $interval;
            $target_year = $current_year;
            
            // Handle year overflow
            while ($target_month > 12) {
                $target_month -= 12;
                $target_year++;
            }
            
            // Create the next date, handling months with fewer days
            // If the day doesn't exist in target month (e.g., 31st in February),
            // use the last day of that month
            $last_day_of_target_month = intval(date('t', mktime(0, 0, 0, $target_month, 1, $target_year)));
            $target_day = min($day_of_month, $last_day_of_target_month);
            
            $current = new DateTime("{$target_year}-{$target_month}-{$target_day}");
        }
        
        return $instances;
    }
    
    /**
     * Generate custom recurring instances
     * Custom = specific dates provided by user
     */
    private function generate_custom($start_date, $end_date, $rules, $exceptions) {
        $instances = array();
        
        if (!isset($rules['custom_dates']) || !is_array($rules['custom_dates'])) {
            return $instances;
        }
        
        // Always include the start_date as first instance if not in list
        $start_date_str = $start_date->format('Y-m-d');
        $custom_dates = $rules['custom_dates'];
        
        // Add start_date if not already in the custom dates list
        if (!in_array($start_date_str, $custom_dates)) {
            array_unshift($custom_dates, $start_date_str);
        }
        
        // For custom dates, we use ALL dates provided by the user
        foreach ($custom_dates as $date_str) {
            if (!in_array($date_str, $exceptions)) {
                $instances[] = array(
                    'date' => $date_str,
                    'time_start' => $rules['time_start'] ?? '',
                    'time_end' => $rules['time_end'] ?? '',
                );
            }
        }
        
        // Sort by date
        usort($instances, function($a, $b) {
            return strcmp($a['date'], $b['date']);
        });
        
        return $instances;
    }
    
    /**
     * Get exceptions for a recurring event
     * 
     * @param int $parent_id Parent event post ID
     * @return array Array of exception dates (Y-m-d format)
     */
    public function get_exceptions($parent_id) {
        $exceptions = get_post_meta($parent_id, '_es_recurring_exceptions', true);
        
        if (empty($exceptions) || !is_array($exceptions)) {
            return array();
        }
        
        return $exceptions;
    }
    
    /**
     * Add an exception (skip a date)
     * 
     * @param int $parent_id Parent event post ID
     * @param string $date Date to skip (Y-m-d format)
     * @return bool Success
     */
    public function add_exception($parent_id, $date) {
        $exceptions = $this->get_exceptions($parent_id);
        
        if (!in_array($date, $exceptions)) {
            $exceptions[] = $date;
            return update_post_meta($parent_id, '_es_recurring_exceptions', $exceptions);
        }
        
        return true;
    }
    
    /**
     * Remove an exception (restore a date)
     * 
     * @param int $parent_id Parent event post ID
     * @param string $date Date to restore (Y-m-d format)
     * @return bool Success
     */
    public function remove_exception($parent_id, $date) {
        $exceptions = $this->get_exceptions($parent_id);
        $key = array_search($date, $exceptions);
        
        if ($key !== false) {
            unset($exceptions[$key]);
            $exceptions = array_values($exceptions); // Re-index
            return update_post_meta($parent_id, '_es_recurring_exceptions', $exceptions);
        }
        
        return true;
    }
    
    /**
     * Get recurring rules for an event
     * 
     * @param int $event_id Event post ID
     * @return array|null Recurring rules or null
     */
    public function get_rules($event_id) {
        $is_recurring = get_post_meta($event_id, '_es_is_recurring', true);
        
        if (!$is_recurring) {
            return null;
        }
        
        $rules = get_post_meta($event_id, '_es_recurring_rules', true);
        
        if (empty($rules)) {
            return null;
        }
        
        // Ensure it's an array
        if (is_string($rules)) {
            $rules = json_decode($rules, true);
        }
        
        return $rules;
    }
    
    /**
     * Save recurring rules for an event
     * 
     * @param int $event_id Event post ID
     * @param array $rules Recurring rules
     * @return bool Success
     */
    public function save_rules($event_id, $rules) {
        // Mark as recurring
        update_post_meta($event_id, '_es_is_recurring', 1);
        
        // Save rules
        return update_post_meta($event_id, '_es_recurring_rules', $rules);
    }
    
    /**
     * Remove recurring rules (make event non-recurring)
     * 
     * @param int $event_id Event post ID
     * @return bool Success
     */
    public function remove_rules($event_id) {
        delete_post_meta($event_id, '_es_is_recurring');
        delete_post_meta($event_id, '_es_recurring_rules');
        delete_post_meta($event_id, '_es_recurring_exceptions');
        
        return true;
    }
    
    /**
     * Check if an event is recurring
     * 
     * @param int $event_id Event post ID
     * @return bool
     */
    public function is_recurring($event_id) {
        return (bool) get_post_meta($event_id, '_es_is_recurring', true);
    }
    
    /**
     * Get parent event ID for a virtual instance
     * 
     * @param int $event_id Event post ID
     * @return int|null Parent ID or null
     */
    public function get_parent_id($event_id) {
        $parent_id = get_post_meta($event_id, '_es_recurring_parent', true);
        
        return $parent_id ? intval($parent_id) : null;
    }
}
