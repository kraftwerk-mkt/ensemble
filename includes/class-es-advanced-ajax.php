<?php
/**
 * Advanced Features AJAX Handler
 * 
 * Handles AJAX requests for:
 * - Exception management with reasons
 * - Event moving (drag & drop)
 * - iCal export
 *
 * @package Ensemble
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class ES_Advanced_AJAX {
    
    private $engine;
    private $virtual;
    
    public function __construct() {
        $this->engine = new ES_Recurring_Engine();
        $this->virtual = new ES_Virtual_Events();
    }
    
    /**
     * Get exceptions with reasons
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
        
        $exceptions = get_post_meta($event_id, '_es_recurring_exceptions_detailed', true);
        if (!$exceptions) {
            $exceptions = array();
        }
        
        wp_send_json_success(array('exceptions' => $exceptions));
    }
    
    /**
     * Add exception with reason
     */
    public function add_exception_with_reason() {
        check_ajax_referer('ensemble_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
        
        $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
        $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';
        $reason = isset($_POST['reason']) ? sanitize_text_field($_POST['reason']) : '';
        
        if (!$event_id || !$date) {
            wp_send_json_error(array('message' => 'Invalid parameters'));
            return;
        }
        
        // Add to simple exceptions list (for engine)
        $result = $this->engine->add_exception($event_id, $date);
        
        // Add to detailed exceptions (with reason)
        $exceptions = get_post_meta($event_id, '_es_recurring_exceptions_detailed', true);
        if (!is_array($exceptions)) {
            $exceptions = array();
        }
        
        $exceptions[$date] = $reason;
        update_post_meta($event_id, '_es_recurring_exceptions_detailed', $exceptions);
        
        if ($result) {
            wp_send_json_success(array('message' => 'Exception added successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to add exception'));
        }
    }
    
    /**
     * Move event to new date
     */
    public function move_event() {
        check_ajax_referer('ensemble_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
        
        $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
        $new_date = isset($_POST['new_date']) ? sanitize_text_field($_POST['new_date']) : '';
        
        if (!$event_id || !$new_date) {
            wp_send_json_error(array('message' => 'Invalid parameters'));
            return;
        }
        
        // Update event date using unified helper
        if (function_exists('ensemble_update_field')) {
            ensemble_update_field('event_date', $new_date, $event_id);
            wp_send_json_success(array('message' => 'Event moved successfully'));
        } elseif (function_exists('update_field')) {
            update_field('event_date', $new_date, $event_id);
            wp_send_json_success(array('message' => 'Event moved successfully'));
        } else {
            // Fallback to post meta with correct key
            $date_key = class_exists('ES_Meta_Keys') ? ES_Meta_Keys::get('date') : 'event_date';
            update_post_meta($event_id, $date_key, $new_date);
            wp_send_json_success(array('message' => 'Event moved successfully'));
        }
    }
    
    /**
     * Export events as iCal
     */
    public function export_ical() {
        check_ajax_referer('ensemble_nonce', 'nonce', false);
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'month';
        $date = isset($_GET['date']) ? sanitize_text_field($_GET['date']) : date('Y-m-d');
        
        // Get events for date range
        $calendar = new ES_Calendar();
        
        if ($view === 'month') {
            $date_obj = new DateTime($date);
            $events = $calendar->get_events_for_month($date_obj->format('Y'), $date_obj->format('n'));
        } elseif ($view === 'week') {
            $events = $calendar->get_events_for_week($date);
        } else {
            $events = $calendar->get_events_for_day($date);
        }
        
        // Generate iCal
        $ical = $this->generate_ical($events);
        
        // Send as download
        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="ensemble-events.ics"');
        echo $ical;
        exit;
    }
    
    /**
     * Generate iCal format
     */
    private function generate_ical($events) {
        $ical = "BEGIN:VCALENDAR\r\n";
        $ical .= "VERSION:2.0\r\n";
        $ical .= "PRODID:-//Ensemble//Event Calendar//EN\r\n";
        $ical .= "CALSCALE:GREGORIAN\r\n";
        
        foreach ($events as $event) {
            $ical .= "BEGIN:VEVENT\r\n";
            
            // UID
            $uid = is_string($event['id']) ? str_replace('virtual_', '', $event['id']) : $event['id'];
            $ical .= "UID:" . $uid . "@" . $_SERVER['HTTP_HOST'] . "\r\n";
            
            // Start date/time
            $start = new DateTime($event['date']);
            if (!empty($event['time'])) {
                $time_parts = explode(':', $event['time']);
                $start->setTime($time_parts[0], $time_parts[1]);
            }
            $ical .= "DTSTART:" . $start->format('Ymd\THis\Z') . "\r\n";
            
            // End date/time (add 2 hours if no end time)
            $end = clone $start;
            $end->modify('+2 hours');
            $ical .= "DTEND:" . $end->format('Ymd\THis\Z') . "\r\n";
            
            // Summary (title)
            $ical .= "SUMMARY:" . $this->escape_ical_text($event['title']) . "\r\n";
            
            // Location
            if (!empty($event['location'])) {
                $location_name = is_numeric($event['location']) ? get_the_title($event['location']) : $event['location'];
                $ical .= "LOCATION:" . $this->escape_ical_text($location_name) . "\r\n";
            }
            
            // Virtual event marker
            if ($event['is_virtual']) {
                $ical .= "CATEGORIES:Recurring\r\n";
            }
            
            $ical .= "END:VEVENT\r\n";
        }
        
        $ical .= "END:VCALENDAR\r\n";
        
        return $ical;
    }
    
    /**
     * Escape text for iCal format
     */
    private function escape_ical_text($text) {
        $text = str_replace(array("\\", ",", ";", "\n"), array("\\\\", "\\,", "\\;", "\\n"), $text);
        return $text;
    }
}
