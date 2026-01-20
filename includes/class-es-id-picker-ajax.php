<?php
/**
 * ID Picker AJAX Handler
 * 
 * Handles AJAX requests for the ID Picker feature
 *
 * @package Ensemble
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class ES_ID_Picker_AJAX {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_ensemble_get_picker_data', array($this, 'get_picker_data'));
    }
    
    /**
     * Get picker data for events, artists, or locations
     */
    public function get_picker_data() {
        // Verify nonce
        check_ajax_referer('ensemble_picker', 'nonce');
        
        // Get parameters
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        
        // Validate type
        if (!in_array($type, array('events', 'artists', 'locations', 'staff'))) {
            wp_send_json_error(__('Invalid type', 'ensemble'));
        }
        
        // Get data based on type
        switch ($type) {
            case 'events':
                $data = $this->get_events_data($search);
                break;
            case 'artists':
                $data = $this->get_artists_data($search);
                break;
            case 'locations':
                $data = $this->get_locations_data($search);
                break;
            case 'staff':
                $data = $this->get_staff_data($search);
                break;
        }
        
        wp_send_json_success($data);
    }
    
    /**
     * Get events data
     * 
     * @param string $search Search term
     * @return array Events data
     */
    private function get_events_data($search = '') {
        $args = array(
            'post_type' => ensemble_get_post_type(),
            'post_status' => 'publish',
            'posts_per_page' => 50,
            'orderby' => 'date',
            'order' => 'DESC',
        );
        
        if (!empty($search)) {
            $args['s'] = $search;
        }
        
        $query = new WP_Query($args);
        $events = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $event_id = get_the_ID();
                
                // Get event date for meta display
                $event_date = ensemble_get_event_meta($event_id, 'start_date');
                $event_time = ensemble_get_event_meta($event_id, 'start_time');
                
                $meta_text = '';
                if ($event_date) {
                    $meta_text = date_i18n(get_option('date_format'), strtotime($event_date));
                    if ($event_time) {
                        $meta_text .= ' ' . date_i18n(get_option('time_format'), strtotime($event_time));
                    }
                }
                
                $events[] = array(
                    'id' => $event_id,
                    'title' => get_the_title(),
                    'meta' => $meta_text,
                    'edit_url' => get_edit_post_link($event_id),
                );
            }
        }
        
        wp_reset_postdata();
        
        return $events;
    }
    
    /**
     * Get artists data
     * 
     * @param string $search Search term
     * @return array Artists data
     */
    private function get_artists_data($search = '') {
        $args = array(
            'post_type' => 'ensemble_artist',
            'post_status' => 'publish',
            'posts_per_page' => 50,
            'orderby' => 'title',
            'order' => 'ASC',
        );
        
        if (!empty($search)) {
            $args['s'] = $search;
        }
        
        $query = new WP_Query($args);
        $artists = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $artist_id = get_the_ID();
                
                // Get artist genre for meta display
                $genres = get_the_terms($artist_id, 'ensemble_genre');
                $meta_text = '';
                
                if ($genres && !is_wp_error($genres)) {
                    $genre_names = array();
                    foreach ($genres as $genre) {
                        $genre_names[] = $genre->name;
                    }
                    $meta_text = implode(', ', $genre_names);
                }
                
                $artists[] = array(
                    'id' => $artist_id,
                    'title' => get_the_title(),
                    'meta' => $meta_text,
                    'edit_url' => get_edit_post_link($artist_id),
                );
            }
        }
        
        wp_reset_postdata();
        
        return $artists;
    }
    
    /**
     * Get locations data
     * 
     * @param string $search Search term
     * @return array Locations data
     */
    private function get_locations_data($search = '') {
        $args = array(
            'post_type' => 'ensemble_location',
            'post_status' => 'publish',
            'posts_per_page' => 50,
            'orderby' => 'title',
            'order' => 'ASC',
        );
        
        if (!empty($search)) {
            $args['s'] = $search;
        }
        
        $query = new WP_Query($args);
        $locations = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $location_id = get_the_ID();
                
                // Get location type for meta display
                $types = get_the_terms($location_id, 'location_type');
                $meta_text = '';
                
                if ($types && !is_wp_error($types)) {
                    $type_names = array();
                    foreach ($types as $type) {
                        $type_names[] = $type->name;
                    }
                    $meta_text = implode(', ', $type_names);
                }
                
                // If no type, try to get city from meta
                if (empty($meta_text)) {
                    $city = get_post_meta($location_id, 'es_location_city', true);
                    if (empty($city)) {
                        $city = get_post_meta($location_id, 'location_city', true);
                    }
                    if (!empty($city)) {
                        $meta_text = $city;
                    }
                }
                
                $locations[] = array(
                    'id' => $location_id,
                    'title' => get_the_title(),
                    'meta' => $meta_text,
                    'edit_url' => get_edit_post_link($location_id),
                );
            }
        }
        
        wp_reset_postdata();
        
        return $locations;
    }
    
    /**
     * Get staff data
     * 
     * @param string $search Search term
     * @return array Staff data
     */
    private function get_staff_data($search = '') {
        // Check if staff post type exists
        if (!post_type_exists('ensemble_staff')) {
            return array();
        }
        
        $args = array(
            'post_type' => 'ensemble_staff',
            'post_status' => 'publish',
            'posts_per_page' => 50,
            'orderby' => 'title',
            'order' => 'ASC',
        );
        
        if (!empty($search)) {
            $args['s'] = $search;
        }
        
        $query = new WP_Query($args);
        $staff = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $staff_id = get_the_ID();
                
                // Get department for meta display
                $departments = get_the_terms($staff_id, 'ensemble_department');
                $meta_text = '';
                
                if ($departments && !is_wp_error($departments)) {
                    $dept_names = array();
                    foreach ($departments as $dept) {
                        $dept_names[] = $dept->name;
                    }
                    $meta_text = implode(', ', $dept_names);
                }
                
                // If no department, try to get position from meta
                if (empty($meta_text)) {
                    $position = get_post_meta($staff_id, '_es_staff_position', true);
                    if (!empty($position)) {
                        $meta_text = $position;
                    }
                }
                
                $staff[] = array(
                    'id' => $staff_id,
                    'title' => get_the_title(),
                    'meta' => $meta_text,
                    'edit_url' => admin_url('admin.php?page=ensemble-staff'),
                );
            }
        }
        
        wp_reset_postdata();
        
        return $staff;
    }
}

// Initialize
new ES_ID_Picker_AJAX();
