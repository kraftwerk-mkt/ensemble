<?php
/**
 * Calendar Management
 * 
 * Handles calendar views and event display
 *
 * @package Ensemble
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class ES_Calendar {
    
    /**
     * Get events for a specific date range
     * @param string $start_date Y-m-d format
     * @param string $end_date Y-m-d format
     * @return array
     */
    public function get_events_for_range($start_date, $end_date) {
        // Use Virtual Events handler to get both real and virtual events
        $virtual_events = new ES_Virtual_Events();
        $events = $virtual_events->get_events_for_range($start_date, $end_date);
        
        // Format for calendar display
        $formatted_events = array();
        foreach ($events as $event) {
            // Use the actual ID from the object/array - virtual IDs are strings like "virtual_123_20251115"
            $event_id = is_object($event) ? $event->ID : $event['id'];
            
            // Get real post ID for metadata (strip virtual_ prefix if present)
            $real_id = $event_id;
            if (is_string($event_id) && strpos($event_id, 'virtual_') === 0) {
                preg_match('/virtual_(\d+)_/', $event_id, $matches);
                $real_id = isset($matches[1]) ? intval($matches[1]) : $event_id;
            }
            
            // Get categories and their colors
            $category_terms = wp_get_post_terms($real_id, 'ensemble_category');
            $category_ids = array();
            $category_color = '#3582c4'; // Default color
            foreach ($category_terms as $term) {
                $category_ids[] = $term->term_id;
                // Get color from first category
                if ($category_color === '#3582c4') {
                    $term_color = get_term_meta($term->term_id, 'ensemble_category_color', true);
                    if (!empty($term_color)) {
                        $category_color = $term_color;
                    }
                }
            }
            
            // Get location ID and resolve name
            $location_id = get_post_meta($real_id, 'event_location', true);
            $location_name = '';
            if (!empty($location_id)) {
                $location_post = get_post($location_id);
                if ($location_post) {
                    $location_name = $location_post->post_title;
                }
            }
            
            // Get artists
            $artist_ids = get_post_meta($real_id, 'event_artists', true);
            if (!is_array($artist_ids)) {
                $artist_ids = !empty($artist_ids) ? array($artist_ids) : array();
            }
            
            // Get description
            $description = is_object($event) ? get_the_excerpt($real_id) : '';
            
            // Get event status
            $event_status = get_post_meta($real_id, '_event_status', true);
            $post_status = get_post_status($real_id);
            if (empty($event_status)) {
                $event_status = ($post_status === 'draft') ? 'draft' : 'publish';
            }
            
            $formatted_events[] = array(
                'id' => $event_id, // Keep virtual ID format!
                'real_id' => $real_id,
                'title' => is_object($event) ? $event->title : $event['title'],
                'date' => is_object($event) ? $event->event_date : $event['date'],
                'time' => is_object($event) ? $event->event_time : $event['time'],
                'location' => $location_name, // Now contains the actual name
                'location_id' => $location_id,
                'is_virtual' => is_object($event) ? $event->is_virtual : ($event['is_virtual'] ?? false),
                'is_recurring' => is_object($event) ? $event->is_recurring : ($event['is_recurring'] ?? false),
                'is_multi_day' => is_object($event) ? ($event->is_multi_day ?? false) : ($event['is_multi_day'] ?? false),
                'is_permanent' => is_object($event) ? ($event->is_permanent ?? false) : ($event['is_permanent'] ?? false),
                'duration_type' => is_object($event) ? ($event->duration_type ?? 'single') : ($event['duration_type'] ?? 'single'),
                'multi_day_position' => is_object($event) ? ($event->multi_day_position ?? null) : ($event['multi_day_position'] ?? null),
                'multi_day_start' => is_object($event) ? ($event->multi_day_start ?? null) : ($event['multi_day_start'] ?? null),
                'multi_day_end' => is_object($event) ? ($event->multi_day_end ?? null) : ($event['multi_day_end'] ?? null),
                'categories' => $category_ids,
                'category_color' => $category_color,
                'artists' => $artist_ids,
                'description' => $description,
                'event_status' => $event_status,
            );
        }
        
        return $formatted_events;
    }
    
    /**
     * Get events for a specific month
     * @param int $year
     * @param int $month
     * @return array
     */
    public function get_events_for_month($year, $month) {
        $start_date = date('Y-m-d', mktime(0, 0, 0, $month, 1, $year));
        $end_date = date('Y-m-d', mktime(23, 59, 59, $month + 1, 0, $year));
        
        return $this->get_events_for_range($start_date, $end_date);
    }
    
    /**
     * Get events for a specific week
     * @param string $date Y-m-d format (any day in the week)
     * @return array
     */
    public function get_events_for_week($date) {
        $timestamp = strtotime($date);
        $day_of_week = date('N', $timestamp); // 1 (Monday) to 7 (Sunday)
        
        // Calculate Monday of this week
        $monday = strtotime("-" . ($day_of_week - 1) . " days", $timestamp);
        $start_date = date('Y-m-d', $monday);
        
        // Calculate Sunday of this week
        $sunday = strtotime("+6 days", $monday);
        $end_date = date('Y-m-d', $sunday);
        
        return $this->get_events_for_range($start_date, $end_date);
    }
    
    /**
     * Get events for a specific day
     * @param string $date Y-m-d format
     * @return array
     */
    public function get_events_for_day($date) {
        return $this->get_events_for_range($date, $date);
    }
    
    /**
     * Get calendar grid data for month view
     * @param int $year
     * @param int $month
     * @return array
     */
    public function get_month_grid($year, $month) {
        $first_day = mktime(0, 0, 0, $month, 1, $year);
        $days_in_month = date('t', $first_day);
        $day_of_week = date('N', $first_day); // 1 (Monday) to 7 (Sunday)
        
        $grid = array();
        
        // Calculate start of grid (might include days from previous month)
        $start_offset = $day_of_week - 1; // Days to show from previous month
        
        // Previous month days
        if ($start_offset > 0) {
            $prev_month = $month - 1;
            $prev_year = $year;
            if ($prev_month < 1) {
                $prev_month = 12;
                $prev_year--;
            }
            $prev_month_days = date('t', mktime(0, 0, 0, $prev_month, 1, $prev_year));
            
            for ($i = $start_offset; $i > 0; $i--) {
                $day = $prev_month_days - $i + 1;
                $grid[] = array(
                    'day' => $day,
                    'month' => $prev_month,
                    'year' => $prev_year,
                    'date' => sprintf('%04d-%02d-%02d', $prev_year, $prev_month, $day),
                    'is_current_month' => false,
                    'is_today' => false,
                );
            }
        }
        
        // Current month days
        $today = date('Y-m-d');
        for ($day = 1; $day <= $days_in_month; $day++) {
            $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
            $grid[] = array(
                'day' => $day,
                'month' => $month,
                'year' => $year,
                'date' => $date,
                'is_current_month' => true,
                'is_today' => ($date === $today),
            );
        }
        
        // Next month days to fill the grid (complete weeks)
        $total_cells = count($grid);
        $remaining = (7 - ($total_cells % 7)) % 7;
        
        if ($remaining > 0) {
            $next_month = $month + 1;
            $next_year = $year;
            if ($next_month > 12) {
                $next_month = 1;
                $next_year++;
            }
            
            for ($day = 1; $day <= $remaining; $day++) {
                $grid[] = array(
                    'day' => $day,
                    'month' => $next_month,
                    'year' => $next_year,
                    'date' => sprintf('%04d-%02d-%02d', $next_year, $next_month, $day),
                    'is_current_month' => false,
                    'is_today' => false,
                );
            }
        }
        
        return $grid;
    }
    
    /**
     * Get week grid data
     * @param string $date Y-m-d format (any day in the week)
     * @return array
     */
    public function get_week_grid($date) {
        $timestamp = strtotime($date);
        $day_of_week = date('N', $timestamp);
        
        // Calculate Monday of this week
        $monday = strtotime("-" . ($day_of_week - 1) . " days", $timestamp);
        
        $grid = array();
        $today = date('Y-m-d');
        
        for ($i = 0; $i < 7; $i++) {
            $day_timestamp = strtotime("+$i days", $monday);
            $day_date = date('Y-m-d', $day_timestamp);
            
            $grid[] = array(
                'day' => date('j', $day_timestamp),
                'month' => date('n', $day_timestamp),
                'year' => date('Y', $day_timestamp),
                'date' => $day_date,
                'day_name' => date('l', $day_timestamp),
                'is_today' => ($day_date === $today),
            );
        }
        
        return $grid;
    }
    
    /**
     * Get category color for event
     * @param array $event
     * @return string Hex color code
     */
    public function get_event_color($event) {
        // Check if we already have category_color from the formatted event
        if (!empty($event['category_color'])) {
            return $event['category_color'];
        }
        
        // Fallback: Try to get color from first category
        if (!empty($event['categories'])) {
            $cat_id = is_array($event['categories'][0]) ? $event['categories'][0]['id'] : $event['categories'][0];
            $color = get_term_meta($cat_id, 'ensemble_category_color', true);
            if (!empty($color)) {
                return $color;
            }
        }
        
        // Default color
        return '#3582c4';
    }
}
