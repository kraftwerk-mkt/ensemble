<?php
/**
 * Ensemble Countdown Add-on
 * 
 * Countdown-Timer bis zum Event-Start
 * 
 * @package Ensemble
 * @subpackage Addons
 */

if (!defined('ABSPATH')) {
    exit;
}

class ES_Countdown_Addon extends ES_Addon_Base {
    
    /**
     * Addon slug
     */
    protected $slug = 'countdown';
    
    /**
     * Addon Name
     */
    protected $name = 'Countdown';
    
    /**
     * Addon Version
     */
    protected $version = '1.0.0';
    
    /**
     * Initialize addon
     */
    protected function init() {
        if (empty($this->settings)) {
            $this->settings = $this->get_default_settings();
        } else {
            $this->settings = wp_parse_args($this->settings, $this->get_default_settings());
        }
    }
    
    /**
     * Register hooks
     */
    protected function register_hooks() {
        // Register addon hook - vor dem Event (optimal für Countdown)
        $this->register_template_hook('ensemble_before_event', array($this, 'render_countdown'), 10);
        
        // Enqueue assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
    }
    
    /**
     * Get default settings
     */
    public function get_default_settings() {
        return array(
            'enabled' => true,
            'show_days' => true,
            'show_hours' => true,
            'show_minutes' => true,
            'show_seconds' => true,
            'show_labels' => true,
            'hide_when_passed' => true,
            'show_event_started' => true,
            'event_started_text' => __('Event is running!', 'ensemble'),
            'event_passed_text' => __('Event beendet', 'ensemble'),
            'style' => 'boxes', // boxes, minimal, flip, circle
            'size' => 'medium', // small, medium, large
            'position' => 'header', // header, before-content, after-title
        );
    }
    
    /**
     * Check if addon is active
     */
    public function is_active() {
        return ES_Addon_Manager::is_addon_active($this->slug);
    }
    
    /**
     * Get settings
     */
    public function get_settings() {
        return wp_parse_args($this->settings, $this->get_default_settings());
    }
    
    /**
     * Sanitize settings
     */
    public function sanitize_settings($settings) {
        $defaults = $this->get_default_settings();
        $sanitized = array();
        
        // Text fields
        $sanitized['event_started_text'] = isset($settings['event_started_text']) 
            ? sanitize_text_field($settings['event_started_text']) 
            : $defaults['event_started_text'];
        $sanitized['event_passed_text'] = isset($settings['event_passed_text']) 
            ? sanitize_text_field($settings['event_passed_text']) 
            : $defaults['event_passed_text'];
        
        // Select fields
        $sanitized['style'] = isset($settings['style']) ? sanitize_key($settings['style']) : $defaults['style'];
        $sanitized['size'] = isset($settings['size']) ? sanitize_key($settings['size']) : $defaults['size'];
        $sanitized['position'] = isset($settings['position']) ? sanitize_key($settings['position']) : $defaults['position'];
        
        // Boolean fields
        $boolean_fields = array(
            'enabled', 'show_days', 'show_hours', 'show_minutes', 'show_seconds',
            'show_labels', 'hide_when_passed', 'show_event_started'
        );
        
        foreach ($boolean_fields as $field) {
            $sanitized[$field] = $this->sanitize_boolean($settings, $field, $defaults[$field]);
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize boolean value
     */
    private function sanitize_boolean($settings, $key, $default) {
        if (!isset($settings[$key])) {
            return $default;
        }
        
        $value = $settings[$key];
        
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
        
        if (!is_singular('ensemble_event') && !is_singular('post')) {
            return;
        }
        
        wp_enqueue_style(
            'es-countdown',
            $this->get_addon_url() . 'assets/countdown.css',
            array(),
            $this->version
        );
        
        wp_enqueue_script(
            'es-countdown',
            $this->get_addon_url() . 'assets/countdown.js',
            array('jquery'),
            $this->version,
            true
        );
        
        wp_localize_script('es-countdown', 'esCountdown', array(
            'labels' => array(
                'days' => __('Days', 'ensemble'),
                'hours' => __('Hours', 'ensemble'),
                'minutes' => __('Minutes', 'ensemble'),
                'seconds' => __('Seconds', 'ensemble'),
                'day' => __('Tag', 'ensemble'),
                'hour' => __('Stunde', 'ensemble'),
                'minute' => __('Minute', 'ensemble'),
                'second' => __('Sekunde', 'ensemble'),
            ),
        ));
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
     * Render countdown
     * 
     * @param int $event_id Event ID
     * @param array $context Context data
     */
    public function render_countdown($event_id, $context = array()) {
        // Check display settings
        if (function_exists('ensemble_show_addon') && !ensemble_show_addon('countdown')) {
            return;
        }
        
        $settings = $this->get_settings();
        
        // Get event datetime using plugin's field helper
        $event_datetime = $this->get_event_datetime($event_id);
        
        if (!$event_datetime) {
            return;
        }
        
        // Check if event has passed
        $now = current_time('timestamp');
        $event_timestamp = $event_datetime->getTimestamp();
        $has_passed = $now > $event_timestamp;
        
        // Get end time to check if event is running
        $end_datetime = $this->get_event_end_datetime($event_id);
        $is_running = false;
        
        if ($end_datetime) {
            $end_timestamp = $end_datetime->getTimestamp();
            $is_running = $now >= $event_timestamp && $now <= $end_timestamp;
        }
        
        // Hide if passed and setting is enabled
        if ($has_passed && !$is_running && $settings['hide_when_passed']) {
            return;
        }
        
        // Render template
        echo $this->load_template('countdown', array(
            'event_id' => $event_id,
            'event_timestamp' => $event_timestamp,
            'event_datetime' => $event_datetime->format('c'), // ISO 8601
            'has_passed' => $has_passed,
            'is_running' => $is_running,
            'settings' => $settings,
        ));
    }
    
    /**
     * Get event datetime
     */
    private function get_event_datetime($event_id) {
        $start_date = $this->get_field($event_id, 'event_date');
        $start_time = $this->get_field($event_id, 'event_time');
        
        if (empty($start_date)) {
            return null;
        }
        
        $datetime_string = $start_date;
        if (!empty($start_time)) {
            $datetime_string .= ' ' . $start_time;
        } else {
            $datetime_string .= ' 00:00:00';
        }
        
        try {
            $datetime = new DateTime($datetime_string, new DateTimeZone(wp_timezone_string()));
            return $datetime;
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Get field value - uses ES_Meta_Keys when available
     */
    private function get_field($event_id, $field) {
        // Normalize field name
        $normalized_field = str_replace(array('event_', 'es_', '_event_'), '', $field);
        
        // 1. Use ES_Meta_Keys if available (preferred)
        if (class_exists('ES_Meta_Keys')) {
            $meta_key = ES_Meta_Keys::get($normalized_field);
            if ($meta_key) {
                $val = get_post_meta($event_id, $meta_key, true);
                if (!empty($val)) return $val;
            }
        }
        
        // 2. Ensemble Helper (mit Field-Mapping)
        if (function_exists('ensemble_get_field')) {
            $val = ensemble_get_field($field, $event_id);
            if (!empty($val)) return $val;
        }
        
        // 3. ACF
        if (function_exists('get_field')) {
            $val = get_field($field, $event_id);
            if (!empty($val)) return $val;
        }
        
        // 4. Post Meta (verschiedene Prefixe) - Fallback
        foreach (array('', 'event_', 'es_event_', 'es_', '_', '_event_') as $prefix) {
            $val = get_post_meta($event_id, $prefix . $normalized_field, true);
            if (!empty($val)) return $val;
        }
        
        return null;
    }
    
    /**
     * Get event end datetime
     */
    private function get_event_end_datetime($event_id) {
        // Try end date/time fields
        $end_date = $this->get_field($event_id, 'event_end_date');
        $end_time = $this->get_field($event_id, 'event_time_end');
        
        // Fallback to start date if no end date
        if (empty($end_date)) {
            $end_date = $this->get_field($event_id, 'event_date');
        }
        
        if (empty($end_date)) {
            return null;
        }
        
        $datetime_string = $end_date;
        if (!empty($end_time)) {
            $datetime_string .= ' ' . $end_time;
        } else {
            $datetime_string .= ' 23:59:59';
        }
        
        try {
            $datetime = new DateTime($datetime_string, new DateTimeZone(wp_timezone_string()));
            return $datetime;
        } catch (Exception $e) {
            return null;
        }
    }
}
