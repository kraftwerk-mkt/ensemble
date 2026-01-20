<?php
/**
 * Visual Calendar Pro Add-on
 * 
 * Beautiful photo-based calendar grid for events
 * Inspired by top nightclub and festival websites
 * 
 * @package Ensemble
 * @subpackage Addons/VisualCalendar
 * @version 1.0.0
 */

if (!defined('ABSPATH')) exit;

class ES_Visual_Calendar_Addon extends ES_Addon_Base {
    
    /**
     * Add-on slug
     * @var string
     */
    protected $slug = 'visual-calendar';
    
    /**
     * Add-on name
     * @var string
     */
    protected $name = 'Visual Calendar Pro';
    
    /**
     * Add-on version
     * @var string
     */
    protected $version = '1.0.0';
    
    /**
     * Default settings
     * @var array
     */
    protected $default_settings = array(
        'show_empty_days' => true,
        'empty_day_text' => 'No Events',
        'image_size' => 'medium_large',
        'aspect_ratio' => '1/1',
        'show_time' => true,
        'show_location' => false,
        'show_event_count' => true,
        'max_events_per_day' => 3,
        'hover_effect' => 'zoom',
        'date_badge_style' => 'overlay',
        'color_scheme' => 'dark',
    );
    
    /**
     * Initialize add-on
     */
    protected function init() {
        // Merge defaults with saved settings
        $this->settings = wp_parse_args($this->settings, $this->default_settings);
    }
    
    /**
     * Register hooks
     */
    protected function register_hooks() {
        // Register shortcodes
        add_shortcode('ensemble_visual_calendar', array($this, 'render_shortcode'));
        add_shortcode('ensemble_photo_calendar', array($this, 'render_shortcode')); // Alias
        add_shortcode('ensemble_calendar_grid', array($this, 'render_shortcode')); // Alias
        
        // Enqueue assets
        add_action('wp_enqueue_scripts', array($this, 'register_assets'));
        
        // AJAX handlers
        add_action('wp_ajax_es_visual_calendar_events', array($this, 'ajax_get_events'));
        add_action('wp_ajax_nopriv_es_visual_calendar_events', array($this, 'ajax_get_events'));
    }
    
    /**
     * Register assets
     */
    public function register_assets() {
        wp_register_style(
            'ensemble-visual-calendar',
            $this->get_addon_url() . 'assets/visual-calendar.css',
            array(),
            $this->version
        );
        
        wp_register_script(
            'ensemble-visual-calendar',
            $this->get_addon_url() . 'assets/visual-calendar.js',
            array('jquery'),
            $this->version,
            true
        );
        
        wp_localize_script('ensemble-visual-calendar', 'esVisualCalendar', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('es_visual_calendar'),
            'strings' => array(
                'noEvents' => __('No Events', 'ensemble'),
                'events' => __('Events', 'ensemble'),
                'loading' => __('Loading...', 'ensemble'),
            ),
            'locale' => get_locale(),
            'firstDay' => get_option('start_of_week', 1),
        ));
    }
    
    /**
     * Render shortcode
     */
    public function render_shortcode($atts) {
        $atts = shortcode_atts(array(
            'year' => date('Y'),
            'month' => date('n'),
            'columns' => 7,
            'show_weekdays' => 'true',
            'show_navigation' => 'true',
            'show_empty_days' => $this->get_setting('show_empty_days', true) ? 'true' : 'false',
            'aspect_ratio' => $this->get_setting('aspect_ratio', '1/1'),
            'color_scheme' => $this->get_setting('color_scheme', 'dark'),
            'category' => '',
            'location' => '',
        ), $atts, 'ensemble_visual_calendar');
        
        // Enqueue assets
        wp_enqueue_style('ensemble-visual-calendar');
        wp_enqueue_script('ensemble-visual-calendar');
        
        // Parse attributes
        $year = intval($atts['year']);
        $month = intval($atts['month']);
        $show_weekdays = filter_var($atts['show_weekdays'], FILTER_VALIDATE_BOOLEAN);
        $show_navigation = filter_var($atts['show_navigation'], FILTER_VALIDATE_BOOLEAN);
        $show_empty_days = filter_var($atts['show_empty_days'], FILTER_VALIDATE_BOOLEAN);
        $color_scheme = sanitize_key($atts['color_scheme']);
        $category = sanitize_text_field($atts['category']);
        $location = sanitize_text_field($atts['location']);
        
        // Get calendar data
        $calendar_data = $this->get_calendar_data($year, $month, $category, $location);
        
        // Generate unique ID
        $calendar_id = 'es-visual-calendar-' . uniqid();
        
        // Settings for template
        $settings = $this->settings;
        
        return $this->load_template('calendar-grid', array(
            'calendar_id' => $calendar_id,
            'calendar_data' => $calendar_data,
            'year' => $year,
            'month' => $month,
            'show_weekdays' => $show_weekdays,
            'show_navigation' => $show_navigation,
            'show_empty_days' => $show_empty_days,
            'color_scheme' => $color_scheme,
            'category' => $category,
            'location' => $location,
            'settings' => $settings,
        ));
    }
    
    /**
     * Get calendar data for a month
     */
    public function get_calendar_data($year, $month, $category = '', $location = '') {
        $calendar = new ES_Calendar();
        
        // Get month grid
        $grid = $calendar->get_month_grid($year, $month);
        
        // Get events for month (with buffer for edge days)
        $start_date = date('Y-m-d', mktime(0, 0, 0, $month - 1, 1, $year));
        $end_date = date('Y-m-d', mktime(0, 0, 0, $month + 2, 0, $year));
        
        $events = $this->get_events_for_range($start_date, $end_date, $category, $location);
        
        // Group events by date
        $events_by_date = array();
        foreach ($events as $event) {
            $date = $event['date'];
            if (!isset($events_by_date[$date])) {
                $events_by_date[$date] = array();
            }
            $events_by_date[$date][] = $event;
        }
        
        // Attach events to grid
        foreach ($grid as &$cell) {
            $cell['events'] = isset($events_by_date[$cell['date']]) ? $events_by_date[$cell['date']] : array();
            $cell['event_count'] = count($cell['events']);
        }
        
        return array(
            'year' => $year,
            'month' => $month,
            'month_name' => date_i18n('F Y', mktime(0, 0, 0, $month, 1, $year)),
            'grid' => $grid,
            'weekdays' => $this->get_weekday_names(),
        );
    }
    
    /**
     * Get events for date range with thumbnails
     * Includes both real and recurring (virtual) events
     */
    private function get_events_for_range($start_date, $end_date, $category = '', $location = '') {
        $events = array();
        
        // Use Virtual Events handler to get both real and recurring events
        if (class_exists('ES_Virtual_Events')) {
            $virtual_events = new ES_Virtual_Events();
            $all_events = $virtual_events->get_events_for_range($start_date, $end_date);
            
            foreach ($all_events as $event_obj) {
                $event_id = is_object($event_obj) ? $event_obj->ID : $event_obj['ID'];
                $is_virtual = is_object($event_obj) ? ($event_obj->is_virtual ?? false) : ($event_obj['is_virtual'] ?? false);
                
                // For virtual events, get parent ID for metadata
                $meta_id = $event_id;
                if ($is_virtual && is_string($event_id) && strpos($event_id, 'virtual_') === 0) {
                    preg_match('/virtual_(\d+)_/', $event_id, $matches);
                    $meta_id = isset($matches[1]) ? intval($matches[1]) : $event_id;
                }
                
                // Get event data
                $event_date = is_object($event_obj) ? $event_obj->event_date : ($event_obj['event_date'] ?? '');
                $event_time = is_object($event_obj) ? ($event_obj->event_time ?? '') : ($event_obj['event_time'] ?? '');
                $event_title = is_object($event_obj) ? $event_obj->title : ($event_obj['title'] ?? get_the_title($meta_id));
                $location_id = is_object($event_obj) ? ($event_obj->event_location ?? '') : ($event_obj['event_location'] ?? '');
                
                // Apply category filter
                if (!empty($category)) {
                    $category_terms = wp_get_post_terms($meta_id, 'ensemble_category');
                    $has_category = false;
                    if (!empty($category_terms) && !is_wp_error($category_terms)) {
                        foreach ($category_terms as $term) {
                            if ((is_numeric($category) && $term->term_id == $category) || $term->slug === $category) {
                                $has_category = true;
                                break;
                            }
                        }
                    }
                    if (!$has_category) {
                        continue;
                    }
                }
                
                // Apply location filter
                if (!empty($location) && $location_id != $location) {
                    continue;
                }
                
                // Get thumbnail
                $image_size = $this->get_setting('image_size', 'medium_large');
                $thumbnail = get_the_post_thumbnail_url($meta_id, $image_size);
                if (!$thumbnail) {
                    $thumbnail = '';
                }
                
                // Get location name
                $location_name = $location_id ? get_the_title($location_id) : '';
                
                // Get category color
                $category_terms = wp_get_post_terms($meta_id, 'ensemble_category');
                $category_color = '';
                $category_name = '';
                if (!empty($category_terms) && !is_wp_error($category_terms)) {
                    $category_name = $category_terms[0]->name;
                    $category_color = get_term_meta($category_terms[0]->term_id, 'ensemble_category_color', true);
                }
                
                // Get status
                $status = get_post_meta($meta_id, '_event_status', true);
                
                // Get multi-day info
                $is_multi_day = is_object($event_obj) ? ($event_obj->is_multi_day ?? false) : ($event_obj['is_multi_day'] ?? false);
                $is_permanent = is_object($event_obj) ? ($event_obj->is_permanent ?? false) : ($event_obj['is_permanent'] ?? false);
                $duration_type = is_object($event_obj) ? ($event_obj->duration_type ?? 'single') : ($event_obj['duration_type'] ?? 'single');
                $multi_day_position = is_object($event_obj) ? ($event_obj->multi_day_position ?? null) : ($event_obj['multi_day_position'] ?? null);
                $multi_day_start = is_object($event_obj) ? ($event_obj->multi_day_start ?? null) : ($event_obj['multi_day_start'] ?? null);
                $multi_day_end = is_object($event_obj) ? ($event_obj->multi_day_end ?? null) : ($event_obj['multi_day_end'] ?? null);
                
                $events[] = array(
                    'id' => $event_id,
                    'title' => $event_title,
                    'permalink' => get_permalink($meta_id),
                    'date' => $event_date,
                    'time' => $event_time,
                    'thumbnail' => $thumbnail,
                    'location' => $location_name,
                    'category' => $category_name,
                    'category_color' => $category_color ?: '#3582c4',
                    'status' => $status,
                    'is_recurring' => is_object($event_obj) ? ($event_obj->is_recurring ?? false) : ($event_obj['is_recurring'] ?? false),
                    'is_virtual' => $is_virtual,
                    'is_multi_day' => $is_multi_day,
                    'is_permanent' => $is_permanent,
                    'duration_type' => $duration_type,
                    'multi_day_position' => $multi_day_position,
                    'multi_day_start' => $multi_day_start,
                    'multi_day_end' => $multi_day_end,
                );
            }
        } else {
            // Fallback to direct query if ES_Virtual_Events not available
            $events = $this->get_events_fallback($start_date, $end_date, $category, $location);
        }
        
        return $events;
    }
    
    /**
     * Fallback method for getting events without ES_Virtual_Events
     */
    private function get_events_fallback($start_date, $end_date, $category = '', $location = '') {
        $args = array(
            'post_type' => ensemble_get_post_type(),
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => class_exists('ES_Meta_Keys') ? ES_Meta_Keys::get('date') : 'event_date',
                    'value' => array($start_date, $end_date),
                    'compare' => 'BETWEEN',
                    'type' => 'DATE',
                ),
            ),
        );
        
        if (!empty($category)) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'ensemble_category',
                    'field' => is_numeric($category) ? 'term_id' : 'slug',
                    'terms' => $category,
                ),
            );
        }
        
        if (!empty($location)) {
            $location_key = class_exists('ES_Meta_Keys') ? ES_Meta_Keys::get('location') : 'event_location';
            $args['meta_query'][] = array(
                'key' => $location_key,
                'value' => $location,
            );
        }
        
        $query = new WP_Query($args);
        $events = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $event_id = get_the_ID();
                
                $event_date = function_exists('ensemble_get_event_meta') 
                    ? ensemble_get_event_meta($event_id, 'start_date')
                    : get_post_meta($event_id, 'event_date', true);
                
                $event_time = function_exists('ensemble_get_event_meta')
                    ? ensemble_get_event_meta($event_id, 'start_time')
                    : get_post_meta($event_id, 'event_time', true);
                
                $image_size = $this->get_setting('image_size', 'medium_large');
                $thumbnail = get_the_post_thumbnail_url($event_id, $image_size);
                
                $location_id = function_exists('ensemble_get_event_meta')
                    ? ensemble_get_event_meta($event_id, 'location')
                    : get_post_meta($event_id, 'event_location', true);
                $location_name = $location_id ? get_the_title($location_id) : '';
                
                $category_terms = wp_get_post_terms($event_id, 'ensemble_category');
                $category_color = '';
                $category_name = '';
                if (!empty($category_terms) && !is_wp_error($category_terms)) {
                    $category_name = $category_terms[0]->name;
                    $category_color = get_term_meta($category_terms[0]->term_id, 'ensemble_category_color', true);
                }
                
                $status = get_post_meta($event_id, '_event_status', true);
                
                $events[] = array(
                    'id' => $event_id,
                    'title' => get_the_title(),
                    'permalink' => get_permalink(),
                    'date' => $event_date,
                    'time' => $event_time,
                    'thumbnail' => $thumbnail ?: '',
                    'location' => $location_name,
                    'category' => $category_name,
                    'category_color' => $category_color ?: '#3582c4',
                    'status' => $status,
                );
            }
        }
        
        wp_reset_postdata();
        
        return $events;
    }
    
    /**
     * Get weekday names
     */
    private function get_weekday_names() {
        $weekdays = array();
        $start = get_option('start_of_week', 1);
        
        for ($i = 0; $i < 7; $i++) {
            $day = ($start + $i) % 7;
            $weekdays[] = date_i18n('D', strtotime("Sunday +{$day} days"));
        }
        
        return $weekdays;
    }
    
    /**
     * AJAX handler for getting events
     */
    public function ajax_get_events() {
        check_ajax_referer('es_visual_calendar', 'nonce');
        
        $year = isset($_POST['year']) ? intval($_POST['year']) : date('Y');
        $month = isset($_POST['month']) ? intval($_POST['month']) : date('n');
        $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
        $location = isset($_POST['location']) ? sanitize_text_field($_POST['location']) : '';
        
        $data = $this->get_calendar_data($year, $month, $category, $location);
        
        // Render grid HTML
        $html = $this->load_template('calendar-cells', array(
            'calendar_data' => $data,
            'show_empty_days' => true,
            'settings' => $this->settings,
        ));
        
        wp_send_json_success(array(
            'html' => $html,
            'month_name' => $data['month_name'],
            'year' => $data['year'],
            'month' => $data['month'],
        ));
    }
    
    /**
     * Render settings page
     * Called by Addon Manager
     * 
     * @return string
     */
    public function render_settings() {
        $settings = $this->settings;
        return $this->load_template('settings', array('settings' => $settings));
    }
    
    /**
     * Sanitize settings
     * 
     * @param array $input
     * @return array
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        // Boolean conversion helper - JS sends true/false as actual booleans or strings
        $to_bool = function($val) {
            if (is_bool($val)) return $val;
            if ($val === 'true' || $val === '1' || $val === 1) return true;
            if ($val === 'false' || $val === '0' || $val === 0 || $val === '') return false;
            return (bool) $val;
        };
        
        $sanitized['show_empty_days'] = $to_bool($input['show_empty_days'] ?? true);
        $sanitized['empty_day_text'] = sanitize_text_field($input['empty_day_text'] ?? 'No Events');
        $sanitized['image_size'] = sanitize_key($input['image_size'] ?? 'medium_large');
        $sanitized['aspect_ratio'] = sanitize_text_field($input['aspect_ratio'] ?? '1/1');
        $sanitized['show_time'] = $to_bool($input['show_time'] ?? true);
        $sanitized['show_location'] = $to_bool($input['show_location'] ?? false);
        $sanitized['show_event_count'] = $to_bool($input['show_event_count'] ?? true);
        $sanitized['max_events_per_day'] = intval($input['max_events_per_day'] ?? 3);
        $sanitized['hover_effect'] = sanitize_key($input['hover_effect'] ?? 'zoom');
        $sanitized['date_badge_style'] = sanitize_key($input['date_badge_style'] ?? 'overlay');
        $sanitized['color_scheme'] = sanitize_key($input['color_scheme'] ?? 'dark');
        
        return $sanitized;
    }
}