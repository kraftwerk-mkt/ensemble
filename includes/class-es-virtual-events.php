<?php
/**
 * Virtual Events Handler
 * 
 * Handles virtual event instances (not stored in DB)
 *
 * @package Ensemble
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class ES_Virtual_Events {
    
    private $engine;
    
    public function __construct() {
        $this->engine = new ES_Recurring_Engine();
    }
    
    /**
     * Get all events (real + virtual) for a date range
     * 
     * @param string $start_date Start date (Y-m-d)
     * @param string $end_date End date (Y-m-d)
     * @return array Combined array of real and virtual events
     */
    public function get_events_for_range($start_date, $end_date) {
        $events = array();
        
        // Get real events
        $real_events = $this->get_real_events($start_date, $end_date);
        
        // Get all recurring parent events
        $recurring_events = $this->get_recurring_parents();
        
        // Generate virtual instances
        foreach ($recurring_events as $parent) {
            $rules = $this->engine->get_rules($parent->ID);
            
            if (!$rules) {
                continue;
            }
            
            // Calculate months to generate
            $start = new DateTime($start_date);
            $end = new DateTime($end_date);
            $interval = $start->diff($end);
            $months = ($interval->y * 12) + $interval->m + 1;
            
            $instances = $this->engine->generate_instances($parent->ID, $rules, $months);
            
            // Filter instances to date range and convert to event objects
            foreach ($instances as $instance) {
                if ($instance['date'] >= $start_date && $instance['date'] <= $end_date) {
                    $virtual_event = $this->create_virtual_event($parent, $instance);
                    $events[] = $virtual_event;
                }
            }
        }
        
        // Merge with real events
        $events = array_merge($events, $real_events);
        
        // Sort by date
        usort($events, function($a, $b) {
            $date_a = is_object($a) ? $a->event_date : $a['date'];
            $date_b = is_object($b) ? $b->event_date : $b['date'];
            return strcmp($date_a, $date_b);
        });
        
        return $events;
    }
    
    /**
     * Get real (non-virtual) events from database
     */
    private function get_real_events($start_date, $end_date) {
        $date_key = ES_Meta_Keys::get('date');
        
        // Query 1: Single events with date in range
        $args_single = array(
            'post_type' => ensemble_get_post_type(),
            'posts_per_page' => -1,
            'meta_query' => array(
                'relation' => 'AND',
                // Exclude recurring parent events (they generate virtual instances)
                array(
                    'key' => '_es_is_recurring',
                    'compare' => 'NOT EXISTS',
                ),
                // Single events OR events without duration_type
                array(
                    'relation' => 'OR',
                    array(
                        'key' => ES_Meta_Keys::EVENT_DURATION_TYPE,
                        'compare' => 'NOT EXISTS',
                    ),
                    array(
                        'key' => ES_Meta_Keys::EVENT_DURATION_TYPE,
                        'value' => 'single',
                        'compare' => '=',
                    ),
                ),
                array(
                    'key' => $date_key,
                    'value' => array($start_date, $end_date),
                    'compare' => 'BETWEEN',
                    'type' => 'DATE',
                ),
            ),
        );
        
        // Query 2: Multi-day events that overlap with the range
        // (start_date <= range_end AND end_date >= range_start)
        $args_multi = array(
            'post_type' => ensemble_get_post_type(),
            'posts_per_page' => -1,
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => '_es_is_recurring',
                    'compare' => 'NOT EXISTS',
                ),
                array(
                    'key' => ES_Meta_Keys::EVENT_DURATION_TYPE,
                    'value' => 'multi_day',
                    'compare' => '=',
                ),
                array(
                    'key' => $date_key,
                    'value' => $end_date,
                    'compare' => '<=',
                    'type' => 'DATE',
                ),
                array(
                    'key' => ES_Meta_Keys::EVENT_DATE_END,
                    'value' => $start_date,
                    'compare' => '>=',
                    'type' => 'DATE',
                ),
            ),
        );
        
        // Query 3: Permanent events that started before or during the range
        $args_permanent = array(
            'post_type' => ensemble_get_post_type(),
            'posts_per_page' => -1,
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => '_es_is_recurring',
                    'compare' => 'NOT EXISTS',
                ),
                array(
                    'key' => ES_Meta_Keys::EVENT_DURATION_TYPE,
                    'value' => 'permanent',
                    'compare' => '=',
                ),
            ),
        );
        
        // Only filter by ensemble_category for 'post' type
        if (ensemble_get_post_type() === 'post') {
            $tax_query = array(
                array(
                    'taxonomy' => 'ensemble_category',
                    'operator' => 'EXISTS',
                ),
            );
            $args_single['tax_query'] = $tax_query;
            $args_multi['tax_query'] = $tax_query;
            $args_permanent['tax_query'] = $tax_query;
        }
        
        $events = array();
        
        // Process single events
        $query_single = new WP_Query($args_single);
        if ($query_single->have_posts()) {
            while ($query_single->have_posts()) {
                $query_single->the_post();
                $events[] = $this->format_event_object(get_the_ID());
            }
            wp_reset_postdata();
        }
        
        // Process multi-day events - expand to daily entries
        $query_multi = new WP_Query($args_multi);
        if ($query_multi->have_posts()) {
            while ($query_multi->have_posts()) {
                $query_multi->the_post();
                $event_id = get_the_ID();
                $multi_events = $this->expand_multi_day_event($event_id, $start_date, $end_date);
                $events = array_merge($events, $multi_events);
            }
            wp_reset_postdata();
        }
        
        // Process permanent events - show on first day of range only (or with special marker)
        $query_permanent = new WP_Query($args_permanent);
        if ($query_permanent->have_posts()) {
            while ($query_permanent->have_posts()) {
                $query_permanent->the_post();
                $event_id = get_the_ID();
                $event = $this->format_event_object($event_id);
                $event->is_permanent = true;
                $event->duration_type = 'permanent';
                // Show at first day of range if no specific date
                if (empty($event->event_date)) {
                    $event->event_date = $start_date;
                }
                $events[] = $event;
            }
            wp_reset_postdata();
        }
        
        return $events;
    }
    
    /**
     * Format a single event as object
     */
    private function format_event_object($event_id) {
        // Get and normalize date (supports multiple meta key formats)
        $raw_date = ensemble_get_event_meta($event_id, 'start_date');
        $event_date = $raw_date;
        
        // If date looks like Ymd (no separators), convert to Y-m-d
        if ($raw_date && preg_match('/^\d{8}$/', $raw_date)) {
            $event_date = substr($raw_date, 0, 4) . '-' . substr($raw_date, 4, 2) . '-' . substr($raw_date, 6, 2);
        }
        // If it's a timestamp, convert to Y-m-d
        elseif ($raw_date && is_numeric($raw_date) && strlen($raw_date) > 8) {
            $event_date = date('Y-m-d', $raw_date);
        }
        
        $duration_type = get_post_meta($event_id, ES_Meta_Keys::EVENT_DURATION_TYPE, true);
        
        return (object) array(
            'ID' => $event_id,
            'title' => get_the_title($event_id),
            'event_date' => $event_date,
            'event_time' => ensemble_get_event_meta($event_id, 'start_time'),
            'event_location' => ensemble_get_event_meta($event_id, 'location'),
            'event_artist' => ensemble_get_event_meta($event_id, 'artist'),
            'event_description' => ensemble_get_event_meta($event_id, 'description'),
            'is_virtual' => false,
            'is_recurring' => false,
            'is_multi_day' => false,
            'is_permanent' => false,
            'duration_type' => $duration_type ?: 'single',
            'multi_day_position' => null,
        );
    }
    
    /**
     * Expand a multi-day event into daily entries for calendar display
     */
    private function expand_multi_day_event($event_id, $range_start, $range_end) {
        $events = array();
        
        // Get event dates
        $raw_start = ensemble_get_event_meta($event_id, 'start_date');
        $event_start = $raw_start;
        if ($raw_start && preg_match('/^\d{8}$/', $raw_start)) {
            $event_start = substr($raw_start, 0, 4) . '-' . substr($raw_start, 4, 2) . '-' . substr($raw_start, 6, 2);
        }
        
        $event_end = get_post_meta($event_id, ES_Meta_Keys::EVENT_DATE_END, true);
        
        if (empty($event_start) || empty($event_end)) {
            // Fallback: treat as single event
            $events[] = $this->format_event_object($event_id);
            return $events;
        }
        
        // Calculate the visible range (intersection of event range and display range)
        $display_start = max($event_start, $range_start);
        $display_end = min($event_end, $range_end);
        
        // Generate an entry for each day
        $current = new DateTime($display_start);
        $end = new DateTime($display_end);
        $event_start_dt = new DateTime($event_start);
        $event_end_dt = new DateTime($event_end);
        
        while ($current <= $end) {
            $current_date = $current->format('Y-m-d');
            
            // Determine position in multi-day span
            $position = 'middle';
            if ($current_date === $event_start) {
                $position = 'start';
            } elseif ($current_date === $event_end) {
                $position = 'end';
            }
            
            // For single-day display in calendar, also check if it's both start and end of visible range
            if ($current_date === $display_start && $current_date === $display_end) {
                // Only one day visible
                if ($current_date === $event_start) {
                    $position = 'start';
                } elseif ($current_date === $event_end) {
                    $position = 'end';
                }
            }
            
            $event = (object) array(
                'ID' => $event_id,
                'title' => get_the_title($event_id),
                'event_date' => $current_date,
                'event_time' => ensemble_get_event_meta($event_id, 'start_time'),
                'event_location' => ensemble_get_event_meta($event_id, 'location'),
                'event_artist' => ensemble_get_event_meta($event_id, 'artist'),
                'event_description' => ensemble_get_event_meta($event_id, 'description'),
                'is_virtual' => false,
                'is_recurring' => false,
                'is_multi_day' => true,
                'is_permanent' => false,
                'duration_type' => 'multi_day',
                'multi_day_position' => $position,
                'multi_day_start' => $event_start,
                'multi_day_end' => $event_end,
            );
            
            $events[] = $event;
            $current->modify('+1 day');
        }
        
        return $events;
    }
    
    /**
     * Get all recurring parent events
     */
    private function get_recurring_parents() {
        $args = array(
            'post_type' => ensemble_get_post_type(),
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_es_is_recurring',
                    'value' => '1',
                    'compare' => '=',
                ),
            ),
        );
        
        // Only filter by ensemble_category for 'post' type
        if (ensemble_get_post_type() === 'post') {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'ensemble_category',
                    'operator' => 'EXISTS',
                ),
            );
        }
        
        $query = new WP_Query($args);
        $parents = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $parents[] = get_post();
            }
            wp_reset_postdata();
        }
        
        return $parents;
    }
    
    /**
     * Create a virtual event object from parent and instance data
     */
    private function create_virtual_event($parent, $instance) {
        // Get parent event data using unified meta key access
        $parent_data = array(
            'location' => ensemble_get_event_meta($parent->ID, 'location'),
            'artist' => ensemble_get_event_meta($parent->ID, 'artist'),
            'description' => ensemble_get_event_meta($parent->ID, 'description'),
            'price' => ensemble_get_event_meta($parent->ID, 'price'),
        );
        
        return (object) array(
            'ID' => 'virtual_' . $parent->ID . '_' . str_replace('-', '', $instance['date']),
            'parent_id' => $parent->ID,
            'title' => $parent->post_title,
            'event_date' => $instance['date'],
            'event_time' => $instance['time_start'],
            'event_time_start' => $instance['time_start'],
            'event_time_end' => $instance['time_end'],
            'event_location' => $parent_data['location'],
            'event_artist' => $parent_data['artist'],
            'event_description' => $parent_data['description'],
            'event_price' => $parent_data['price'],
            'is_virtual' => true,
            'is_recurring' => true,
        );
    }
    
    /**
     * Get a specific event (real or virtual)
     * 
     * @param mixed $event_id Event ID (int for real, string for virtual)
     * @return object|null Event object or null
     */
    public function get_event($event_id) {
        // Check if it's a virtual event ID
        if (is_string($event_id) && strpos($event_id, 'virtual_') === 0) {
            return $this->get_virtual_event($event_id);
        }
        
        // Get real event
        $post = get_post($event_id);
        if (!$post) {
            return null;
        }
        
        return (object) array(
            'ID' => $post->ID,
            'title' => $post->post_title,
            'event_date' => ensemble_get_event_meta($post->ID, 'start_date'),
            'event_time' => ensemble_get_event_meta($post->ID, 'start_time'),
            'event_location' => ensemble_get_event_meta($post->ID, 'location'),
            'event_artist' => ensemble_get_event_meta($post->ID, 'artist'),
            'event_description' => ensemble_get_event_meta($post->ID, 'description'),
            'event_price' => ensemble_get_event_meta($post->ID, 'price'),
            'is_virtual' => false,
            'is_recurring' => $this->engine->is_recurring($post->ID),
        );
    }
    
    /**
     * Get a virtual event by its ID
     * 
     * @param string $virtual_id Virtual event ID (format: virtual_{parent_id}_{date})
     * @return object|null Virtual event object or null
     */
    private function get_virtual_event($virtual_id) {
        // Parse virtual ID: virtual_{parent_id}_{date}
        preg_match('/^virtual_(\d+)_(\d{8})$/', $virtual_id, $matches);
        
        if (count($matches) !== 3) {
            return null;
        }
        
        $parent_id = intval($matches[1]);
        $date_str = $matches[2]; // YYYYMMDD
        $date = date('Y-m-d', strtotime($date_str));
        
        $parent = get_post($parent_id);
        if (!$parent) {
            return null;
        }
        
        $rules = $this->engine->get_rules($parent_id);
        if (!$rules) {
            return null;
        }
        
        // Generate instances and find the matching one
        $instances = $this->engine->generate_instances($parent_id, $rules, 12);
        
        foreach ($instances as $instance) {
            if ($instance['date'] === $date) {
                return $this->create_virtual_event($parent, $instance);
            }
        }
        
        return null;
    }
    
    /**
     * Convert virtual event to real event (create in DB)
     * 
     * @param string $virtual_id Virtual event ID
     * @param array $modifications Optional modifications to apply
     * @return int|WP_Error New event ID or error
     */
    public function convert_to_real($virtual_id, $modifications = array()) {
        $virtual_event = $this->get_virtual_event($virtual_id);
        
        if (!$virtual_event) {
            return new WP_Error('invalid_virtual_event', 'Virtual event not found');
        }
        
        // Create new post
        $post_data = array(
            'post_title' => !empty($modifications['title']) ? $modifications['title'] : $virtual_event->title,
            'post_type' => ensemble_get_post_type(),
            'post_status' => 'publish',
        );
        
        $new_id = wp_insert_post($post_data);
        
        if (is_wp_error($new_id)) {
            return $new_id;
        }
        
        // Copy/modify fields using field mapping
        if (function_exists('update_field')) {
            $fields_to_save = array(
                'event_date' => !empty($modifications['event_date']) ? $modifications['event_date'] : $virtual_event->event_date,
                'event_time' => !empty($modifications['event_time']) ? $modifications['event_time'] : $virtual_event->event_time,
                'event_time_end' => !empty($modifications['event_time_end']) ? $modifications['event_time_end'] : $virtual_event->event_time_end,
                'event_location' => !empty($modifications['event_location']) ? $modifications['event_location'] : $virtual_event->event_location,
                'event_artist' => !empty($modifications['event_artist']) ? $modifications['event_artist'] : $virtual_event->event_artist,
                'event_description' => !empty($modifications['event_description']) ? $modifications['event_description'] : $virtual_event->event_description,
                'event_price' => !empty($modifications['event_price']) ? $modifications['event_price'] : $virtual_event->event_price,
            );
            
            // Save all fields with mapping support
            foreach ($fields_to_save as $field_name => $field_value) {
                ensemble_update_field($field_name, $field_value, $new_id);
            }
        }
        
        // Mark as converted from virtual - store parent ID for restore functionality
        update_post_meta($new_id, '_es_recurring_parent', $virtual_event->parent_id);
        
        // Mark as exception in parent
        $this->engine->add_exception($virtual_event->parent_id, $virtual_event->event_date);
        
        return $new_id;
    }
    
    /**
     * Delete a virtual event (add as exception)
     * 
     * @param string $virtual_id Virtual event ID
     * @return bool Success
     */
    public function delete_virtual($virtual_id) {
        $virtual_event = $this->get_virtual_event($virtual_id);
        
        if (!$virtual_event) {
            return false;
        }
        
        return $this->engine->add_exception($virtual_event->parent_id, $virtual_event->event_date);
    }
}
