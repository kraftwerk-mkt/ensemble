<?php
/**
 * Ensemble Timetable Addon
 * 
 * Visual grid editor for complex conference schedules
 * Works alongside the existing Wizard - same data, different view
 *
 * @package Ensemble
 * @subpackage Addons
 * @since 2.9.0
 */

if (!defined('ABSPATH')) exit;

class ES_Timetable_Addon extends ES_Addon_Base {
    
    /**
     * Addon slug
     */
    protected $slug = 'timetable';
    
    /**
     * Addon name
     */
    protected $name = 'Timetable Editor';
    
    /**
     * Addon version
     */
    public $version = '1.0.0';
    
    /**
     * Time interval in minutes
     */
    private $time_interval = 30;
    
    /**
     * Default session duration
     */
    private $default_duration = 60;
    
    /**
     * Minimum time range to display (hours)
     */
    private $min_display_hours = 8;
    
    /**
     * Initialize addon (required by ES_Addon_Base)
     */
    protected function init() {
        // Nothing additional needed
    }
    
    /**
     * Register hooks (required by ES_Addon_Base)
     */
    protected function register_hooks() {
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'), 30);
        
        // Enqueue admin assets
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Add duration to merged agenda
        add_filter('ensemble_merged_agenda', array($this, 'add_duration_to_agenda'), 10, 2);
        
        // Register AJAX handlers
        $this->register_ajax_handlers();
    }
    
    /**
     * Register AJAX handlers (like other addons do)
     */
    private function register_ajax_handlers() {
        add_action('wp_ajax_es_timetable_load', array($this, 'ajax_load_timetable'));
        add_action('wp_ajax_es_timetable_save', array($this, 'ajax_save_timetable'));
        add_action('wp_ajax_es_timetable_update_session', array($this, 'ajax_update_session'));
        add_action('wp_ajax_es_timetable_search_speakers', array($this, 'ajax_search_speakers'));
        add_action('wp_ajax_es_timetable_add_speaker', array($this, 'ajax_add_speaker'));
        add_action('wp_ajax_es_timetable_remove_speaker', array($this, 'ajax_remove_speaker'));
        add_action('wp_ajax_es_timetable_add_break', array($this, 'ajax_add_break'));
        add_action('wp_ajax_es_timetable_update_break', array($this, 'ajax_update_break'));
        add_action('wp_ajax_es_timetable_remove_break', array($this, 'ajax_remove_break'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'ensemble',
            __('Timetable Editor', 'ensemble'),
            __('Timetable', 'ensemble'),
            'manage_options',
            'ensemble-timetable',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'ensemble-timetable') === false) {
            return;
        }
        
        // jQuery UI
        wp_enqueue_script('jquery-ui-draggable');
        wp_enqueue_script('jquery-ui-droppable');
        
        // CSS
        wp_enqueue_style(
            'es-timetable-admin',
            $this->get_addon_url() . 'assets/css/timetable-admin.css',
            array(),
            $this->version
        );
        
        // JS
        wp_enqueue_script(
            'es-timetable-admin',
            $this->get_addon_url() . 'assets/js/timetable-admin.js',
            array('jquery', 'jquery-ui-draggable', 'jquery-ui-droppable'),
            $this->version,
            true
        );
        
        // Localize script
        wp_localize_script('es-timetable-admin', 'esTimetable', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('es_timetable_nonce'),
            'timeInterval' => $this->time_interval,
            'defaultDuration' => $this->default_duration,
            'i18n' => array(
                'save' => __('Save', 'ensemble'),
                'saving' => __('Saving...', 'ensemble'),
                'saved' => __('Saved!', 'ensemble'),
                'error' => __('Error saving', 'ensemble'),
                'confirmDelete' => __('Are you sure?', 'ensemble'),
                'noEvent' => __('Please select an event first.', 'ensemble'),
            ),
        ));
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        $event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
        $events = $this->get_events_list();
        $timetable_data = $event_id ? $this->get_timetable_data($event_id) : null;
        
        include $this->get_addon_path() . 'templates/admin/timetable-editor.php';
    }
    
    /**
     * Get artist post type
     */
    private function get_artist_post_type() {
        if (function_exists('ensemble_get_artist_post_type')) {
            return ensemble_get_artist_post_type();
        }
        if (post_type_exists('ensemble_artist')) {
            return 'ensemble_artist';
        }
        if (post_type_exists('artist')) {
            return 'artist';
        }
        return 'ensemble_artist';
    }
    
    /**
     * Get list of events for selector
     */
    public function get_events_list() {
        $post_type = function_exists('ensemble_get_post_type') ? ensemble_get_post_type() : 'post';
        $date_key = function_exists('ensemble_get_date_meta_key') ? ensemble_get_date_meta_key() : 'es_event_start_date';
        
        $args = array(
            'post_type' => $post_type,
            'posts_per_page' => 100,
            'post_status' => array('publish', 'draft', 'pending'),
            'orderby' => 'meta_value',
            'meta_key' => $date_key,
            'order' => 'DESC',
        );
        
        $posts = get_posts($args);
        $events = array();
        
        foreach ($posts as $post) {
            $start_date = '';
            if (function_exists('ensemble_get_field')) {
                $start_date = ensemble_get_field('event_start_date', $post->ID);
            }
            if (!$start_date) {
                $start_date = get_post_meta($post->ID, $date_key, true);
            }
            
            // Check if event has timetable data
            $artist_ids = $this->get_event_artists($post->ID);
            $has_timetable = !empty($artist_ids);
            
            $events[] = array(
                'id' => $post->ID,
                'title' => $post->post_title,
                'date' => $start_date ? date_i18n('j M Y', strtotime($start_date)) : '',
                'status' => $post->post_status,
                'has_timetable' => $has_timetable,
                'speaker_count' => count($artist_ids),
            );
        }
        
        return $events;
    }
    
    /**
     * Get event artists
     */
    private function get_event_artists($event_id) {
        $artist_ids = array();
        
        // Try multiple meta keys for compatibility
        $meta_keys = array('event_artist', 'es_event_artist');
        
        foreach ($meta_keys as $key) {
            $artist_ids = get_post_meta($event_id, $key, true);
            if (!empty($artist_ids)) {
                break;
            }
        }
        
        // Also check ACF if available
        if (empty($artist_ids) && function_exists('get_field')) {
            $artist_ids = get_field('event_artist', $event_id, false);
        }
        
        if (!is_array($artist_ids)) {
            $artist_ids = !empty($artist_ids) ? array($artist_ids) : array();
        }
        
        return array_map('intval', array_filter($artist_ids));
    }
    
    /**
     * Get timetable data for an event
     */
    public function get_timetable_data($event_id) {
        // Get artist IDs
        $artist_ids = $this->get_event_artists($event_id);
        
        // Get artist meta with flexible key handling
        $artist_times = $this->get_meta_array($event_id, 'artist_times');
        $artist_venues = $this->get_meta_array($event_id, 'artist_venues');
        $artist_durations = $this->get_meta_array($event_id, 'artist_durations');
        $session_titles = $this->get_meta_array($event_id, 'artist_session_titles');
        
        // Get breaks
        $breaks = get_post_meta($event_id, '_agenda_breaks', true);
        $breaks = is_array($breaks) ? $breaks : array();
        
        // Get rooms from location
        $rooms = $this->get_event_rooms($event_id);
        $default_room = !empty($rooms[0]['name']) ? $rooms[0]['name'] : __('Main Room', 'ensemble');
        
        // Build sessions and unassigned arrays
        $sessions = array();
        $unassigned = array();
        $all_times = array();
        
        foreach ($artist_ids as $artist_id) {
            $artist = get_post($artist_id);
            if (!$artist) continue;
            
            $time = $this->get_from_array($artist_times, $artist_id);
            $venue = $this->get_from_array($artist_venues, $artist_id);
            $duration = $this->get_from_array($artist_durations, $artist_id);
            $title = $this->get_from_array($session_titles, $artist_id);
            
            $duration = $duration ? intval($duration) : $this->default_duration;
            
            // If has time but no venue, assign default room
            if (!empty($time) && empty($venue)) {
                $venue = $default_room;
            }
            
            $session_data = array(
                'artist_id' => $artist_id,
                'artist_name' => $artist->post_title,
                'artist_image' => get_the_post_thumbnail_url($artist_id, 'thumbnail'),
                'artist_role' => get_post_meta($artist_id, 'artist_role', true),
                'time' => $time,
                'venue' => $venue,
                'duration' => $duration,
                'session_title' => $title,
            );
            
            if (empty($time)) {
                $unassigned[] = $session_data;
            } else {
                $sessions[] = $session_data;
                $time_mins = $this->time_to_minutes($time);
                $all_times[] = $time_mins;
                $all_times[] = $time_mins + $duration;
            }
        }
        
        // Collect break times
        foreach ($breaks as $break) {
            if (!empty($break['time'])) {
                $break_start = $this->time_to_minutes($break['time']);
                $break_duration = isset($break['duration']) ? intval($break['duration']) : 30;
                $all_times[] = $break_start;
                $all_times[] = $break_start + $break_duration;
            }
        }
        
        // Calculate time range - MINIMUM 8 hours display
        $start_time = '08:00';
        $end_time = '18:00';
        
        if (!empty($all_times)) {
            $min_time = min($all_times);
            $max_time = max($all_times);
            
            // Round to hours with buffer
            $start_minutes = floor($min_time / 60) * 60 - 60;
            $end_minutes = ceil($max_time / 60) * 60 + 60;
            
            // Ensure minimum span
            $span = $end_minutes - $start_minutes;
            $min_span = $this->min_display_hours * 60;
            
            if ($span < $min_span) {
                $extra = ($min_span - $span) / 2;
                $start_minutes -= $extra;
                $end_minutes += $extra;
            }
            
            $start_minutes = max(0, $start_minutes);
            $end_minutes = min(1440, $end_minutes);
            
            $start_time = $this->minutes_to_time($start_minutes);
            $end_time = $this->minutes_to_time($end_minutes);
        }
        
        // Get event date
        $start_date = '';
        if (function_exists('ensemble_get_field')) {
            $start_date = ensemble_get_field('event_start_date', $event_id);
        }
        if (!$start_date) {
            $start_date = get_post_meta($event_id, 'es_event_start_date', true);
        }
        
        return array(
            'event_id' => $event_id,
            'event_title' => get_the_title($event_id),
            'start_date' => $start_date,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'rooms' => $rooms,
            'sessions' => $sessions,
            'breaks' => $breaks,
            'unassigned' => $unassigned,
            'time_interval' => $this->time_interval,
            'default_duration' => $this->default_duration,
        );
    }
    
    /**
     * Get meta array with flexible key handling
     */
    private function get_meta_array($post_id, $key) {
        $data = get_post_meta($post_id, $key, true);
        if (!is_array($data)) {
            return array();
        }
        
        // Normalize keys to both int and string
        $normalized = array();
        foreach ($data as $k => $v) {
            $normalized[intval($k)] = $v;
            $normalized[strval($k)] = $v;
        }
        return $normalized;
    }
    
    /**
     * Get value from array with flexible key
     */
    private function get_from_array($array, $key) {
        if (isset($array[intval($key)])) {
            return $array[intval($key)];
        }
        if (isset($array[strval($key)])) {
            return $array[strval($key)];
        }
        return '';
    }
    
    /**
     * Get rooms for an event from its location
     */
    public function get_event_rooms($event_id) {
        $location_id = 0;
        
        if (function_exists('ensemble_get_field')) {
            $location_id = ensemble_get_field('event_location', $event_id);
        }
        if (!$location_id) {
            $location_id = get_post_meta($event_id, 'event_location', true);
        }
        
        if (!$location_id) {
            return array(array('name' => __('Main Room', 'ensemble'), 'capacity' => 0));
        }
        
        $is_multivenue = get_post_meta($location_id, 'is_multivenue', true);
        $venues = get_post_meta($location_id, 'venues', true);
        
        if ($is_multivenue && is_array($venues) && !empty($venues)) {
            return $venues;
        }
        
        $location = get_post($location_id);
        return array(
            array('name' => $location ? $location->post_title : __('Main Room', 'ensemble'), 'capacity' => 0)
        );
    }
    
    /**
     * Time to minutes
     */
    public function time_to_minutes($time) {
        if (empty($time)) return 0;
        if (preg_match('/^(\d{1,2}):(\d{2})/', $time, $matches)) {
            return intval($matches[1]) * 60 + intval($matches[2]);
        }
        return 0;
    }
    
    /**
     * Minutes to time
     */
    public function minutes_to_time($minutes) {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return sprintf('%02d:%02d', $hours, $mins);
    }
    
    /**
     * Add duration to merged agenda
     */
    public function add_duration_to_agenda($items, $event_id) {
        $durations = $this->get_meta_array($event_id, 'artist_durations');
        
        foreach ($items as &$item) {
            if ($item['type'] === 'session' && isset($item['artist_id'])) {
                $dur = $this->get_from_array($durations, $item['artist_id']);
                $item['duration'] = $dur ? intval($dur) : $this->default_duration;
            }
        }
        
        return $items;
    }
    
    // =========================================================================
    // AJAX HANDLERS
    // =========================================================================
    
    /**
     * Verify nonce
     */
    private function verify_nonce() {
        return check_ajax_referer('es_timetable_nonce', 'nonce', false);
    }
    
    /**
     * Send JSON error
     */
    private function send_error($message, $code = 400, $data = array()) {
        wp_send_json_error(array('message' => $message, 'data' => $data), $code);
    }
    
    /**
     * Send JSON success
     */
    private function send_success($message = '', $data = array()) {
        wp_send_json_success(array('message' => $message, 'data' => $data));
    }
    
    /**
     * AJAX: Load timetable data
     */
    public function ajax_load_timetable() {
        if (!$this->verify_nonce()) {
            $this->send_error(__('Invalid security token.', 'ensemble'), 403);
        }
        
        $event_id = intval($_POST['event_id'] ?? 0);
        if (!$event_id) {
            $this->send_error(__('No event ID provided.', 'ensemble'), 400);
        }
        
        $data = $this->get_timetable_data($event_id);
        wp_send_json_success($data);
    }
    
    /**
     * AJAX: Save complete timetable
     */
    public function ajax_save_timetable() {
        if (!$this->verify_nonce()) {
            $this->send_error(__('Invalid security token.', 'ensemble'), 403);
        }
        
        $event_id = intval($_POST['event_id'] ?? 0);
        if (!$event_id || !current_user_can('edit_post', $event_id)) {
            $this->send_error(__('Permission denied.', 'ensemble'), 403);
        }
        
        $sessions = json_decode(stripslashes($_POST['sessions'] ?? '[]'), true) ?: array();
        $breaks = json_decode(stripslashes($_POST['breaks'] ?? '[]'), true) ?: array();
        
        // Save sessions
        $artist_times = array();
        $artist_venues = array();
        $artist_durations = array();
        $session_titles = array();
        
        foreach ($sessions as $session) {
            $aid = intval($session['artist_id']);
            if ($aid) {
                $artist_times[$aid] = sanitize_text_field($session['time'] ?? '');
                $artist_venues[$aid] = sanitize_text_field($session['venue'] ?? '');
                $artist_durations[$aid] = intval($session['duration'] ?? $this->default_duration);
                $session_titles[$aid] = sanitize_text_field($session['session_title'] ?? '');
            }
        }
        
        update_post_meta($event_id, 'artist_times', $artist_times);
        update_post_meta($event_id, 'artist_venues', $artist_venues);
        update_post_meta($event_id, 'artist_durations', $artist_durations);
        update_post_meta($event_id, 'artist_session_titles', $session_titles);
        
        // Save breaks
        if (function_exists('ensemble_save_agenda_breaks')) {
            ensemble_save_agenda_breaks($event_id, $breaks);
        } else {
            update_post_meta($event_id, '_agenda_breaks', $breaks);
        }
        
        // Clear cache
        if (class_exists('ES_Cache')) {
            ES_Cache::delete('agenda_' . $event_id);
        }
        
        $this->send_success(__('Timetable saved.', 'ensemble'));
    }
    
    /**
     * AJAX: Update single session
     */
    public function ajax_update_session() {
        if (!$this->verify_nonce()) {
            $this->send_error(__('Invalid security token.', 'ensemble'), 403);
            return;
        }
        
        $event_id = intval($_POST['event_id'] ?? 0);
        $artist_id = intval($_POST['artist_id'] ?? 0);
        
        if (!$event_id || !$artist_id) {
            $this->send_error(__('Missing required data.', 'ensemble'), 400);
            return;
        }
        
        if (!current_user_can('edit_post', $event_id)) {
            $this->send_error(__('Permission denied.', 'ensemble'), 403);
            return;
        }
        
        $time = sanitize_text_field($_POST['time'] ?? '');
        $venue = sanitize_text_field($_POST['venue'] ?? '');
        $duration = intval($_POST['duration'] ?? $this->default_duration);
        $session_title = sanitize_text_field($_POST['session_title'] ?? '');
        
        // Get current data
        $artist_times = get_post_meta($event_id, 'artist_times', true) ?: array();
        $artist_venues = get_post_meta($event_id, 'artist_venues', true) ?: array();
        $artist_durations = get_post_meta($event_id, 'artist_durations', true) ?: array();
        $session_titles = get_post_meta($event_id, 'artist_session_titles', true) ?: array();
        
        // Update
        if ($time) {
            $artist_times[$artist_id] = $time;
            $artist_venues[$artist_id] = $venue;
            $artist_durations[$artist_id] = $duration;
            if ($session_title) {
                $session_titles[$artist_id] = $session_title;
            }
        } else {
            // Unassign
            unset($artist_times[$artist_id], $artist_venues[$artist_id]);
        }
        
        // Save
        update_post_meta($event_id, 'artist_times', $artist_times);
        update_post_meta($event_id, 'artist_venues', $artist_venues);
        update_post_meta($event_id, 'artist_durations', $artist_durations);
        update_post_meta($event_id, 'artist_session_titles', $session_titles);
        
        // Clear cache
        if (class_exists('ES_Cache')) {
            ES_Cache::delete('agenda_' . $event_id);
        }
        
        // Return updated timetable
        $data = $this->get_timetable_data($event_id);
        wp_send_json_success($data);
    }
    
    /**
     * AJAX: Search speakers
     */
    public function ajax_search_speakers() {
        if (!$this->verify_nonce()) {
            $this->send_error(__('Invalid security token.', 'ensemble'), 403);
            return;
        }
        
        $search = sanitize_text_field($_POST['search'] ?? '');
        $event_id = intval($_POST['event_id'] ?? 0);
        
        $artist_post_type = $this->get_artist_post_type();
        $assigned = $event_id ? $this->get_event_artists($event_id) : array();
        
        $args = array(
            'post_type' => $artist_post_type,
            'posts_per_page' => 30,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC',
        );
        
        if (!empty($search)) {
            $args['s'] = $search;
        }
        
        $artists = get_posts($args);
        $results = array();
        
        foreach ($artists as $artist) {
            $results[] = array(
                'id' => $artist->ID,
                'name' => $artist->post_title,
                'image' => get_the_post_thumbnail_url($artist->ID, 'thumbnail'),
                'role' => get_post_meta($artist->ID, 'artist_role', true),
                'assigned' => in_array($artist->ID, $assigned),
            );
        }
        
        wp_send_json_success(array('speakers' => $results));
    }
    
    /**
     * AJAX: Add speaker to event
     */
    public function ajax_add_speaker() {
        if (!$this->verify_nonce()) {
            $this->send_error(__('Invalid security token.', 'ensemble'), 403);
            return;
        }
        
        $event_id = intval($_POST['event_id'] ?? 0);
        $speaker_id = intval($_POST['speaker_id'] ?? 0);
        
        if (!$event_id || !$speaker_id) {
            $this->send_error(__('Missing required data.', 'ensemble'), 400);
            return;
        }
        
        if (!current_user_can('edit_post', $event_id)) {
            $this->send_error(__('Permission denied.', 'ensemble'), 403);
            return;
        }
        
        // Get current artists - use direct meta read to ensure fresh data
        $artist_ids = get_post_meta($event_id, 'event_artist', true);
        if (!is_array($artist_ids)) {
            $artist_ids = !empty($artist_ids) ? array($artist_ids) : array();
        }
        $artist_ids = array_map('intval', $artist_ids);
        
        // Add if not already present
        if (!in_array($speaker_id, $artist_ids)) {
            $artist_ids[] = $speaker_id;
            
            // Update WordPress meta
            update_post_meta($event_id, 'event_artist', $artist_ids);
            
            // Also update ACF if available
            if (function_exists('update_field')) {
                update_field('event_artist', $artist_ids, $event_id);
            }
            
            // Clear any object cache
            wp_cache_delete($event_id, 'post_meta');
            clean_post_cache($event_id);
        }
        
        // Return updated timetable
        $data = $this->get_timetable_data($event_id);
        wp_send_json_success($data);
    }
    
    /**
     * AJAX: Remove speaker from event
     */
    public function ajax_remove_speaker() {
        if (!$this->verify_nonce()) {
            $this->send_error(__('Invalid security token.', 'ensemble'), 403);
        }
        
        $event_id = intval($_POST['event_id'] ?? 0);
        $speaker_id = intval($_POST['speaker_id'] ?? 0);
        
        if (!$event_id || !$speaker_id) {
            $this->send_error(__('Missing required data.', 'ensemble'), 400);
        }
        
        if (!current_user_can('edit_post', $event_id)) {
            $this->send_error(__('Permission denied.', 'ensemble'), 403);
        }
        
        // Remove from artist list
        $artist_ids = $this->get_event_artists($event_id);
        $artist_ids = array_values(array_filter($artist_ids, function($id) use ($speaker_id) {
            return intval($id) !== $speaker_id;
        }));
        
        update_post_meta($event_id, 'event_artist', $artist_ids);
        
        // Remove associated data
        $this->remove_artist_data($event_id, $speaker_id);
        
        if (function_exists('update_field')) {
            @update_field('event_artist', $artist_ids, $event_id);
        }
        
        // Return updated timetable
        $data = $this->get_timetable_data($event_id);
        wp_send_json_success($data);
    }
    
    /**
     * Remove artist data from all meta arrays
     */
    private function remove_artist_data($event_id, $artist_id) {
        $keys = array('artist_times', 'artist_venues', 'artist_durations', 'artist_session_titles');
        
        foreach ($keys as $key) {
            $data = get_post_meta($event_id, $key, true) ?: array();
            unset($data[$artist_id], $data[strval($artist_id)]);
            update_post_meta($event_id, $key, $data);
        }
    }
    
    /**
     * AJAX: Add break
     */
    public function ajax_add_break() {
        if (!$this->verify_nonce()) {
            $this->send_error(__('Invalid security token.', 'ensemble'), 403);
        }
        
        $event_id = intval($_POST['event_id'] ?? 0);
        
        if (!$event_id || !current_user_can('edit_post', $event_id)) {
            $this->send_error(__('Permission denied.', 'ensemble'), 403);
        }
        
        $break = array(
            'time' => sanitize_text_field($_POST['time'] ?? ''),
            'title' => sanitize_text_field($_POST['title'] ?? __('Break', 'ensemble')),
            'duration' => intval($_POST['duration'] ?? 30),
            'icon' => sanitize_key($_POST['icon'] ?? 'pause'),
        );
        
        if (empty($break['time'])) {
            $this->send_error(__('Time is required.', 'ensemble'), 400);
        }
        
        $breaks = get_post_meta($event_id, '_agenda_breaks', true) ?: array();
        $breaks[] = $break;
        
        if (function_exists('ensemble_save_agenda_breaks')) {
            ensemble_save_agenda_breaks($event_id, $breaks);
        } else {
            update_post_meta($event_id, '_agenda_breaks', $breaks);
        }
        
        // Return updated timetable
        $data = $this->get_timetable_data($event_id);
        wp_send_json_success($data);
    }
    
    /**
     * AJAX: Update break
     */
    public function ajax_update_break() {
        if (!$this->verify_nonce()) {
            $this->send_error(__('Invalid security token.', 'ensemble'), 403);
        }
        
        $event_id = intval($_POST['event_id'] ?? 0);
        $break_index = intval($_POST['break_index'] ?? -1);
        
        if (!$event_id || $break_index < 0 || !current_user_can('edit_post', $event_id)) {
            $this->send_error(__('Permission denied.', 'ensemble'), 403);
        }
        
        $breaks = get_post_meta($event_id, '_agenda_breaks', true) ?: array();
        
        if (!isset($breaks[$break_index])) {
            $this->send_error(__('Break not found.', 'ensemble'), 404);
        }
        
        $breaks[$break_index] = array(
            'time' => sanitize_text_field($_POST['time'] ?? $breaks[$break_index]['time']),
            'title' => sanitize_text_field($_POST['title'] ?? $breaks[$break_index]['title']),
            'duration' => intval($_POST['duration'] ?? $breaks[$break_index]['duration']),
            'icon' => sanitize_key($_POST['icon'] ?? $breaks[$break_index]['icon'] ?? 'pause'),
        );
        
        if (function_exists('ensemble_save_agenda_breaks')) {
            ensemble_save_agenda_breaks($event_id, $breaks);
        } else {
            update_post_meta($event_id, '_agenda_breaks', $breaks);
        }
        
        // Return updated timetable
        $data = $this->get_timetable_data($event_id);
        wp_send_json_success($data);
    }
    
    /**
     * AJAX: Remove break
     */
    public function ajax_remove_break() {
        if (!$this->verify_nonce()) {
            $this->send_error(__('Invalid security token.', 'ensemble'), 403);
        }
        
        $event_id = intval($_POST['event_id'] ?? 0);
        $break_index = intval($_POST['break_index'] ?? -1);
        
        if (!$event_id || $break_index < 0 || !current_user_can('edit_post', $event_id)) {
            $this->send_error(__('Permission denied.', 'ensemble'), 403);
        }
        
        $breaks = get_post_meta($event_id, '_agenda_breaks', true) ?: array();
        
        if (isset($breaks[$break_index])) {
            array_splice($breaks, $break_index, 1);
            
            if (function_exists('ensemble_save_agenda_breaks')) {
                ensemble_save_agenda_breaks($event_id, $breaks);
            } else {
                update_post_meta($event_id, '_agenda_breaks', $breaks);
            }
        }
        
        // Return updated timetable
        $data = $this->get_timetable_data($event_id);
        wp_send_json_success($data);
    }
}
