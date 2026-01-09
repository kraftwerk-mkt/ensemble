<?php
/**
 * Ensemble Related Events Add-on
 * 
 * Zeigt verwandte Events basierend auf Kategorie, Location oder Artist
 * 
 * @package Ensemble
 * @subpackage Addons
 */

if (!defined('ABSPATH')) {
    exit;
}

class ES_Related_Events_Addon extends ES_Addon_Base {
    
    /**
     * Addon slug
     */
    protected $slug = 'related-events';
    
    /**
     * Addon Name
     */
    protected $name = 'Related Events';
    
    /**
     * Addon Version
     */
    protected $version = '1.1.0';
    
    /**
     * Initialize addon (abstract method implementation)
     */
    protected function init() {
        // Load default settings if not set
        if (empty($this->settings)) {
            $this->settings = $this->get_default_settings();
        } else {
            $this->settings = wp_parse_args($this->settings, $this->get_default_settings());
        }
    }
    
    /**
     * Register hooks (abstract method implementation)
     */
    protected function register_hooks() {
        // Register addon hook
        $this->register_template_hook('ensemble_related_events', array($this, 'render_related_events'), 10);
        
        // Enqueue assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
    }
    
    /**
     * Get default settings
     */
    public function get_default_settings() {
        return array(
            'enabled' => true,
            'count' => 4,
            'relation_type' => 'category', // category, location, artist, mixed
            'show_past_events' => false,
            'layout' => 'grid', // grid, list, slider
            'columns' => 4,
            'slides_visible' => 3,
            'hover_mode' => 'reveal', // reveal, inverted
            'show_image' => true,
            'show_date' => true,
            'show_location' => true,
            'show_category' => false,
            'title' => __('Similar Events', 'ensemble'),
            'empty_message' => __('No similar events found.', 'ensemble'),
            'hide_if_empty' => true,
        );
    }
    
    /**
     * Check if addon is active
     */
    public function is_active() {
        return ES_Addon_Manager::is_addon_active($this->slug);
    }
    
    /**
     * Get settings (override to merge with defaults)
     */
    public function get_settings() {
        return wp_parse_args($this->settings, $this->get_default_settings());
    }
    
    /**
     * Sanitize settings before saving
     * 
     * @param array $settings Raw settings from form
     * @return array Sanitized settings
     */
    public function sanitize_settings($settings) {
        $defaults = $this->get_default_settings();
        $sanitized = array();
        
        // Text fields
        $sanitized['title'] = isset($settings['title']) ? sanitize_text_field($settings['title']) : $defaults['title'];
        $sanitized['empty_message'] = isset($settings['empty_message']) ? sanitize_text_field($settings['empty_message']) : $defaults['empty_message'];
        
        // Number fields
        $sanitized['count'] = isset($settings['count']) ? intval($settings['count']) : $defaults['count'];
        $sanitized['columns'] = isset($settings['columns']) ? intval($settings['columns']) : $defaults['columns'];
        $sanitized['slides_visible'] = isset($settings['slides_visible']) ? intval($settings['slides_visible']) : $defaults['slides_visible'];
        
        // Select fields
        $sanitized['relation_type'] = isset($settings['relation_type']) ? sanitize_key($settings['relation_type']) : $defaults['relation_type'];
        $sanitized['layout'] = isset($settings['layout']) ? sanitize_key($settings['layout']) : $defaults['layout'];
        $sanitized['hover_mode'] = isset($settings['hover_mode']) ? sanitize_key($settings['hover_mode']) : $defaults['hover_mode'];
        
        // Boolean/Checkbox fields - handle string 'true'/'false' from JS
        $sanitized['show_past_events'] = $this->sanitize_boolean($settings, 'show_past_events', $defaults['show_past_events']);
        $sanitized['show_image'] = $this->sanitize_boolean($settings, 'show_image', $defaults['show_image']);
        $sanitized['show_date'] = $this->sanitize_boolean($settings, 'show_date', $defaults['show_date']);
        $sanitized['show_location'] = $this->sanitize_boolean($settings, 'show_location', $defaults['show_location']);
        $sanitized['show_category'] = $this->sanitize_boolean($settings, 'show_category', $defaults['show_category']);
        $sanitized['hide_if_empty'] = $this->sanitize_boolean($settings, 'hide_if_empty', $defaults['hide_if_empty']);
        $sanitized['enabled'] = $this->sanitize_boolean($settings, 'enabled', $defaults['enabled']);
        
        return $sanitized;
    }
    
    /**
     * Sanitize boolean value from settings
     * Handles: true, false, 'true', 'false', 1, 0, '1', '0'
     */
    private function sanitize_boolean($settings, $key, $default) {
        if (!isset($settings[$key])) {
            return $default;
        }
        
        $value = $settings[$key];
        
        // Handle string representations
        if ($value === 'true' || $value === '1' || $value === 1 || $value === true) {
            return true;
        }
        
        if ($value === 'false' || $value === '0' || $value === 0 || $value === false || $value === '') {
            return false;
        }
        
        return $default;
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        if (!$this->is_active()) {
            return;
        }
        
        // Check if on event page
        if (!is_singular('ensemble_event') && !is_singular('post')) {
            return;
        }
        
        wp_enqueue_style(
            'es-related-events',
            $this->get_addon_url() . 'assets/related-events.css',
            array(),
            $this->version
        );
    }
    
    /**
     * Render settings page
     */
    public function render_settings() {
        $settings = $this->get_settings();
        
        ob_start();
        include $this->get_addon_path() . 'templates/settings.php';
        return ob_get_clean();
    }
    
    /**
     * Render related events
     * 
     * @param int $event_id Current event ID
     * @param array $context Context data (categories, location_id, artist_ids)
     */
    public function render_related_events($event_id, $context = array()) {
        // Check display settings
        if (function_exists('ensemble_show_addon') && !ensemble_show_addon('related_events')) {
            return;
        }
        
        $settings = $this->get_settings();
        
        // Auto-populate context if empty
        if (empty($context)) {
            $context = $this->build_context_from_event($event_id);
        }
        
        // Get related events
        $related_events = $this->get_related_events($event_id, $context, $settings);
        
        // Hide if empty and setting enabled
        if (empty($related_events) && $settings['hide_if_empty']) {
            return;
        }
        
        // Render template
        echo $this->load_template('related-events', array(
            'event_id' => $event_id,
            'related_events' => $related_events,
            'settings' => $settings,
        ));
    }
    
    /**
     * Build context data from event
     * 
     * @param int $event_id
     * @return array
     */
    private function build_context_from_event($event_id) {
        $context = array(
            'categories' => array(),
            'location_id' => null,
            'artist_ids' => array(),
        );
        
        // Get categories
        $categories = wp_get_post_terms($event_id, 'ensemble_category');
        if (!is_wp_error($categories) && !empty($categories)) {
            $context['categories'] = $categories;
        } else {
            // Try standard category
            $categories = wp_get_post_terms($event_id, 'category');
            if (!is_wp_error($categories) && !empty($categories)) {
                $context['categories'] = $categories;
            }
        }
        
        // Get location
        if (function_exists('es_get_field')) {
            $location_id = es_get_field($event_id, 'location', 'event');
        } else {
            $location_key = class_exists('ES_Meta_Keys') ? ES_Meta_Keys::get('location') : 'event_location';
            $location_id = get_post_meta($event_id, $location_key, true);
        }
        if ($location_id) {
            $context['location_id'] = intval($location_id);
        }
        
        // Get artists
        if (function_exists('es_get_field')) {
            $artists = es_get_field($event_id, 'artist', 'event');
        } else {
            $artist_key = class_exists('ES_Meta_Keys') ? ES_Meta_Keys::get('artist') : 'event_artist';
            $artists = get_post_meta($event_id, $artist_key, true);
        }
        if (!empty($artists)) {
            if (is_array($artists)) {
                $context['artist_ids'] = array_map('intval', $artists);
            } else {
                $context['artist_ids'] = array(intval($artists));
            }
        }
        
        return $context;
    }
    
    /**
     * Get related events
     * 
     * @param int $event_id Current event ID
     * @param array $context Context data
     * @param array $settings Addon settings
     * @return array Related events
     */
    private function get_related_events($event_id, $context, $settings) {
        // Determine post type from current event
        $current_post_type = get_post_type($event_id);
        
        $args = array(
            'post_type' => $current_post_type,
            'posts_per_page' => intval($settings['count']),
            'post__not_in' => array($event_id),
            'post_status' => 'publish',
        );
        
        // Exclude preview/cancelled/postponed events - only show published
        $status_filter = array(
            'relation' => 'OR',
            array(
                'key' => '_event_status',
                'value' => array('preview', 'cancelled', 'postponed'),
                'compare' => 'NOT IN',
            ),
            array(
                'key' => '_event_status',
                'value' => 'publish',
                'compare' => '=',
            ),
            array(
                'key' => '_event_status',
                'compare' => 'NOT EXISTS',
            ),
        );
        
        $has_filter = false;
        
        // Build relation query based on type
        switch ($settings['relation_type']) {
            case 'category':
                if (!empty($context['categories']) && is_array($context['categories'])) {
                    $first_cat = reset($context['categories']);
                    if (is_object($first_cat) && isset($first_cat->term_id)) {
                        $taxonomy = isset($first_cat->taxonomy) ? $first_cat->taxonomy : 'ensemble_category';
                        $args['tax_query'] = array(
                            array(
                                'taxonomy' => $taxonomy,
                                'field' => 'term_id',
                                'terms' => array($first_cat->term_id),
                            ),
                        );
                        $has_filter = true;
                    }
                }
                break;
                
            case 'location':
                if (!empty($context['location_id'])) {
                    $location_key = class_exists('ES_Meta_Keys') ? ES_Meta_Keys::get('location') : 'event_location';
                    $args['meta_query'] = array(
                        array(
                            'key' => $location_key,
                            'value' => intval($context['location_id']),
                            'compare' => '=',
                        ),
                    );
                    $has_filter = true;
                }
                break;
                
            case 'artist':
                if (!empty($context['artist_ids'])) {
                    $artist_key = class_exists('ES_Meta_Keys') ? ES_Meta_Keys::get('artist') : 'event_artist';
                    // Artists are stored as serialized arrays, need LIKE comparison
                    $artist_queries = array('relation' => 'OR');
                    foreach ($context['artist_ids'] as $artist_id) {
                        // Match serialized integer format
                        $artist_queries[] = array(
                            'key' => $artist_key,
                            'value' => 'i:' . $artist_id . ';',
                            'compare' => 'LIKE',
                        );
                        // Match serialized string format
                        $artist_queries[] = array(
                            'key' => $artist_key,
                            'value' => '"' . $artist_id . '"',
                            'compare' => 'LIKE',
                        );
                    }
                    $args['meta_query'] = $artist_queries;
                    $has_filter = true;
                }
                break;
                
            case 'mixed':
            default:
                // Try category first
                if (!empty($context['categories']) && is_array($context['categories'])) {
                    $first_cat = reset($context['categories']);
                    if (is_object($first_cat) && isset($first_cat->term_id)) {
                        $taxonomy = isset($first_cat->taxonomy) ? $first_cat->taxonomy : 'ensemble_category';
                        $args['tax_query'] = array(
                            array(
                                'taxonomy' => $taxonomy,
                                'field' => 'term_id',
                                'terms' => array($first_cat->term_id),
                            ),
                        );
                        $has_filter = true;
                    }
                }
                
                // If no category, try location
                if (!$has_filter && !empty($context['location_id'])) {
                    $location_key = class_exists('ES_Meta_Keys') ? ES_Meta_Keys::get('location') : 'event_location';
                    $args['meta_query'] = array(
                        array(
                            'key' => $location_key,
                            'value' => intval($context['location_id']),
                            'compare' => '=',
                        ),
                    );
                    $has_filter = true;
                }
                break;
        }
        
        // Only future events unless setting allows past
        if (empty($settings['show_past_events'])) {
            $date_key = class_exists('ES_Meta_Keys') ? ES_Meta_Keys::get('date') : 'event_date';
            $date_query = array(
                array(
                    'key' => $date_key,
                    'value' => current_time('Y-m-d'),
                    'compare' => '>=',
                    'type' => 'DATE',
                ),
            );
            
            if (isset($args['meta_query'])) {
                // Combine existing meta_query with date filter
                $args['meta_query'] = array(
                    'relation' => 'AND',
                    $args['meta_query'],
                    $date_query,
                );
            } else {
                $args['meta_query'] = $date_query;
            }
            
            // Order by date
            $args['orderby'] = 'meta_value';
            $args['meta_key'] = $date_key;
            $args['order'] = 'ASC';
        }
        
        // Always add status filter to exclude preview/cancelled/postponed
        if (isset($args['meta_query'])) {
            $args['meta_query'] = array(
                'relation' => 'AND',
                $args['meta_query'],
                $status_filter,
            );
        } else {
            $args['meta_query'] = $status_filter;
        }
        
        $query = new WP_Query($args);
        $events = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $events[] = $this->format_event(get_the_ID());
            }
            wp_reset_postdata();
        }
        
        // Fallback: if no related events found and no filter was set, get latest events
        if (empty($events) && !$has_filter) {
            $fallback_args = array(
                'post_type' => $current_post_type,
                'posts_per_page' => intval($settings['count']),
                'post__not_in' => array($event_id),
                'post_status' => 'publish',
                'orderby' => 'date',
                'order' => 'DESC',
                'meta_query' => $status_filter,
            );
            
            $fallback_query = new WP_Query($fallback_args);
            
            if ($fallback_query->have_posts()) {
                while ($fallback_query->have_posts()) {
                    $fallback_query->the_post();
                    $events[] = $this->format_event(get_the_ID());
                }
                wp_reset_postdata();
            }
        }
        
        return $events;
    }
    
    /**
     * Format event data for display
     */
    private function format_event($post_id) {
        // Get date key from ES_Meta_Keys (handles both legacy and wizard format)
        $date_key = class_exists('ES_Meta_Keys') ? ES_Meta_Keys::get('date') : 'event_date';
        $time_key = class_exists('ES_Meta_Keys') ? ES_Meta_Keys::get('time') : 'event_time';
        $location_key = class_exists('ES_Meta_Keys') ? ES_Meta_Keys::get('location') : 'event_location';
        
        $start_date = get_post_meta($post_id, $date_key, true);
        $start_time = get_post_meta($post_id, $time_key, true);
        $location_id = get_post_meta($post_id, $location_key, true);
        
        $location_name = '';
        if ($location_id) {
            $location = get_post($location_id);
            if ($location) {
                $location_name = $location->post_title;
            }
        }
        
        // Try multiple taxonomies
        $categories = wp_get_post_terms($post_id, 'ensemble_category', array('fields' => 'names'));
        if (empty($categories) || is_wp_error($categories)) {
            $categories = wp_get_post_terms($post_id, 'category', array('fields' => 'names'));
        }
        
        // Format time if needed
        $time_formatted = '';
        if ($start_time) {
            if (strpos($start_time, ':') !== false) {
                $time_formatted = date_i18n(get_option('time_format'), strtotime('2000-01-01 ' . $start_time));
            } else {
                $time_formatted = $start_time;
            }
        }
        
        return array(
            'id' => $post_id,
            'title' => get_the_title($post_id),
            'url' => get_permalink($post_id),
            'image' => get_the_post_thumbnail_url($post_id, 'large'),
            'date' => $start_date,
            'time' => $time_formatted,
            'date_formatted' => $start_date ? date_i18n(get_option('date_format'), strtotime($start_date)) : '',
            'location' => $location_name,
            'categories' => is_array($categories) ? $categories : array(),
            'excerpt' => get_the_excerpt($post_id),
        );
    }
}
