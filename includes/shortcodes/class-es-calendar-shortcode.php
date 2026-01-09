<?php
/**
 * Ensemble Calendar Shortcode
 *
 * Handles the calendar shortcode with FullCalendar integration
 * for displaying events in various calendar views.
 *
 * @package Ensemble
 * @subpackage Shortcodes
 * @since 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Calendar Shortcode class.
 *
 * @since 3.0.0
 */
class ES_Calendar_Shortcode extends ES_Shortcode_Base {

	/**
	 * Register calendar shortcode.
	 *
	 * @return void
	 */
	public function register_shortcodes() {
		add_shortcode( 'ensemble_calendar', array( $this, 'calendar_shortcode' ) );

		// Register AJAX handlers.
		add_action( 'wp_ajax_ensemble_get_calendar_events', array( __CLASS__, 'ajax_get_calendar_events' ) );
		add_action( 'wp_ajax_nopriv_ensemble_get_calendar_events', array( __CLASS__, 'ajax_get_calendar_events' ) );
	}

    public function calendar_shortcode($atts) {
        // Parse attributes
        $atts = shortcode_atts(array(
            'view' => 'dayGridMonth',       // dayGridMonth, timeGridWeek, timeGridDay, listMonth
            'height' => 'auto',              // auto or pixel value
            'initial_date' => '',            // Y-m-d format
        ), $atts, 'ensemble_calendar');
        
        // Sanitize and normalize view name
        $view = sanitize_text_field($atts['view']);
        
        // Map simple view names to FullCalendar view names
        $view_map = array(
            'month' => 'dayGridMonth',
            'week' => 'timeGridWeek',
            'day' => 'timeGridDay',
            'list' => 'listMonth',
        );
        
        // Map lowercase to correct camelCase
        $view_lowercase_map = array(
            'daygridmonth' => 'dayGridMonth',
            'timegridweek' => 'timeGridWeek',
            'timegridday' => 'timeGridDay',
            'listmonth' => 'listMonth',
            'listweek' => 'listWeek',
        );
        
        // Check if it's a simple name first
        if (isset($view_map[strtolower($view)])) {
            $view = $view_map[strtolower($view)];
        }
        // Or if it's already a FullCalendar view but lowercase
        elseif (isset($view_lowercase_map[strtolower($view)])) {
            $view = $view_lowercase_map[strtolower($view)];
        }
        // Otherwise keep as is but validate it's safe
        else {
            // Only allow known FullCalendar view types
            $valid_views = array('dayGridMonth', 'timeGridWeek', 'timeGridDay', 'listMonth', 'listWeek');
            if (!in_array($view, $valid_views)) {
                $view = 'dayGridMonth'; // Fallback to default
            }
        }
        
        $height = sanitize_text_field($atts['height']);
        $initial_date = sanitize_text_field($atts['initial_date']);
        
        // Generate unique ID for this calendar
        $calendar_id = 'ensemble-calendar-' . uniqid();
        
        // Enqueue FullCalendar
        $this->enqueue_fullcalendar();
        
        // Get events for initial display (next 3 months)
        $start_date = $initial_date ? $initial_date : date('Y-m-d');
        $end_date = date('Y-m-d', strtotime('+3 months', strtotime($start_date)));
        
        $events = $this->get_calendar_events($start_date, $end_date);
        $events_json = json_encode($events);
        
        ob_start();
        ?>
        
        <div class="ensemble-fullcalendar-wrapper">
            <div id="<?php echo esc_attr($calendar_id); ?>" class="ensemble-fullcalendar"></div>
        </div>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('<?php echo esc_js($calendar_id); ?>');
            
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: '<?php echo esc_js($view); ?>',
                <?php if ($initial_date): ?>
                initialDate: '<?php echo esc_js($initial_date); ?>',
                <?php endif; ?>
                height: '<?php echo esc_js($height); ?>',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
                },
                buttonText: {
                    today: '<?php _e('Today', 'ensemble'); ?>',
                    month: '<?php _e('Month', 'ensemble'); ?>',
                    week: '<?php _e('Week', 'ensemble'); ?>',
                    day: '<?php _e('Day', 'ensemble'); ?>',
                    list: '<?php _e('List', 'ensemble'); ?>'
                },
                locale: '<?php echo esc_js(substr(get_locale(), 0, 2)); ?>',
                firstDay: 1,
                navLinks: true,
                editable: false,
                dayMaxEvents: true,
                
                // Load events
                events: function(info, successCallback, failureCallback) {
                    // AJAX call to get events for the current view
                    jQuery.ajax({
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        type: 'POST',
                        data: {
                            action: 'ensemble_get_calendar_events',
                            start: info.startStr,
                            end: info.endStr,
                            nonce: '<?php echo wp_create_nonce('ensemble_calendar'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                successCallback(response.data);
                            } else {
                                failureCallback();
                            }
                        },
                        error: function() {
                            failureCallback();
                        }
                    });
                },
                
                eventClick: function(info) {
                    if (info.event.url) {
                        window.open(info.event.url, '_self');
                        info.jsEvent.preventDefault();
                    }
                },
                
                eventDidMount: function(info) {
                    // Add custom styling
                    if (info.event.extendedProps.eventType === 'recurring') {
                        info.el.classList.add('fc-event-recurring');
                    }
                }
            });
            
            calendar.render();
        });
        </script>
        
        <?php
        return ob_get_clean();
    }
    
    /**
     * Enqueue FullCalendar library with all necessary plugins
     */
    private function enqueue_fullcalendar() {
        // Ensure jQuery is loaded
        wp_enqueue_script('jquery');
        
        // FullCalendar with ALL plugins included (scheduler bundle)
        wp_enqueue_script(
            'fullcalendar',
            'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js',
            array('jquery'),
            '6.1.10',
            true
        );
        
        // Custom calendar styles with Designer Settings integration
        $calendar_css = $this->generate_calendar_css();
        wp_add_inline_style('ensemble-shortcodes', $calendar_css);
    }
    
    /**
     * Get events formatted for FullCalendar
     * 
     * @param string $start_date Y-m-d
     * @param string $end_date Y-m-d
     * @return array Events array
     */
    private function get_calendar_events($start_date, $end_date) {
        $events_array = array();
        $processed_multi_day = array(); // Track multi-day events to avoid duplicates
        
        // Use Virtual Events handler to get both real and recurring events
        if (class_exists('ES_Virtual_Events')) {
            $virtual_events = new ES_Virtual_Events();
            $all_events = $virtual_events->get_events_for_range($start_date, $end_date);
            
            foreach ($all_events as $event) {
                $event_id = is_object($event) ? $event->ID : $event['ID'];
                $is_virtual = is_object($event) ? ($event->is_virtual ?? false) : ($event['is_virtual'] ?? false);
                $is_multi_day = is_object($event) ? ($event->is_multi_day ?? false) : ($event['is_multi_day'] ?? false);
                $is_permanent = is_object($event) ? ($event->is_permanent ?? false) : ($event['is_permanent'] ?? false);
                $duration_type = is_object($event) ? ($event->duration_type ?? 'single') : ($event['duration_type'] ?? 'single');
                
                // For virtual events, get parent ID for metadata
                $meta_id = $event_id;
                if ($is_virtual && is_string($event_id) && strpos($event_id, 'virtual_') === 0) {
                    preg_match('/virtual_(\d+)_/', $event_id, $matches);
                    $meta_id = isset($matches[1]) ? intval($matches[1]) : $event_id;
                }
                
                // For multi-day events, only process once (FullCalendar handles the span)
                if ($is_multi_day) {
                    $real_id = is_numeric($event_id) ? $event_id : $meta_id;
                    if (isset($processed_multi_day[$real_id])) {
                        continue; // Skip duplicate daily entries
                    }
                    $processed_multi_day[$real_id] = true;
                }
                
                // Get event data from object or use helpers
                $event_date = is_object($event) ? $event->event_date : ($event['event_date'] ?? '');
                $event_time = is_object($event) ? ($event->event_time ?? '') : ($event['event_time'] ?? '');
                $event_time_end = is_object($event) ? ($event->event_time_end ?? '') : ($event['event_time_end'] ?? '');
                $event_title = is_object($event) ? $event->title : ($event['title'] ?? get_the_title($meta_id));
                $location_id = is_object($event) ? ($event->event_location ?? '') : ($event['event_location'] ?? '');
                $artist_id = is_object($event) ? ($event->event_artist ?? '') : ($event['event_artist'] ?? '');
                
                // For multi-day events, get the original start date
                $multi_day_start = is_object($event) ? ($event->multi_day_start ?? null) : ($event['multi_day_start'] ?? null);
                $multi_day_end = is_object($event) ? ($event->multi_day_end ?? null) : ($event['multi_day_end'] ?? null);
                
                if (empty($event_date) && empty($multi_day_start)) {
                    continue;
                }
                
                // Build event object for FullCalendar
                $calendar_event = array(
                    'id' => $event_id,
                    'title' => $event_title,
                    'url' => $is_virtual ? get_permalink($meta_id) : get_permalink(is_numeric($event_id) ? $event_id : $meta_id),
                );
                
                // Handle multi-day events
                if ($is_multi_day && $multi_day_start && $multi_day_end) {
                    // FullCalendar uses exclusive end date, so add 1 day
                    $end_plus_one = date('Y-m-d', strtotime($multi_day_end . ' +1 day'));
                    $calendar_event['start'] = $multi_day_start;
                    $calendar_event['end'] = $end_plus_one;
                    $calendar_event['allDay'] = true;
                    $calendar_event['classNames'] = array('fc-event-multi-day');
                } elseif ($is_permanent) {
                    // Permanent events show as ongoing
                    $calendar_event['start'] = $event_date;
                    $calendar_event['allDay'] = true;
                    $calendar_event['classNames'] = array('fc-event-permanent');
                } else {
                    // Single day event
                    $calendar_event['start'] = $event_date;
                    $calendar_event['allDay'] = empty($event_time);
                    
                    // Add time if available
                    if ($event_time) {
                        $calendar_event['start'] = $event_date . 'T' . $event_time;
                        if ($event_time_end) {
                            $calendar_event['end'] = $event_date . 'T' . $event_time_end;
                        }
                    }
                }
                
                // Add extended properties
                $calendar_event['extendedProps'] = array(
                    'is_recurring' => is_object($event) ? ($event->is_recurring ?? false) : ($event['is_recurring'] ?? false),
                    'is_virtual' => $is_virtual,
                    'is_multi_day' => $is_multi_day,
                    'is_permanent' => $is_permanent,
                    'duration_type' => $duration_type,
                );
                
                if ($location_id) {
                    $location = get_post($location_id);
                    if ($location) {
                        $calendar_event['extendedProps']['location'] = $location->post_title;
                    }
                }
                
                if ($artist_id) {
                    $artist = get_post($artist_id);
                    if ($artist) {
                        $calendar_event['extendedProps']['artist'] = $artist->post_title;
                    }
                }
                
                $events_array[] = $calendar_event;
            }
        } else {
            // Fallback to direct query if ES_Virtual_Events not available
            $events_array = $this->get_calendar_events_fallback($start_date, $end_date);
        }
        
        return $events_array;
    }
    
    /**
     * Fallback method for calendar events without ES_Virtual_Events
     */
    private function get_calendar_events_fallback($start_date, $end_date) {
        $events_array = array();
        
        // Get the correct date meta key
        $date_key = $this->get_date_meta_key();
        
        // Query events in date range
        $args = array(
            'post_type' => ensemble_get_post_type(),
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => $date_key,
                    'value' => array($start_date, $end_date),
                    'compare' => 'BETWEEN',
                    'type' => 'DATE',
                ),
            ),
        );
        
        $query = new WP_Query($args);
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $event_id = get_the_ID();
                
                $event_date = $this->get_event_meta($event_id, 'start_date');
                $start_time = $this->get_event_meta($event_id, 'start_time');
                $end_time = $this->get_event_meta($event_id, 'end_time');
                
                if (!$event_date) {
                    continue;
                }
                
                $event = array(
                    'id' => $event_id,
                    'title' => get_the_title(),
                    'start' => $event_date,
                    'url' => get_permalink(),
                    'allDay' => empty($start_time),
                );
                
                if ($start_time) {
                    $event['start'] = $event_date . 'T' . $start_time;
                    if ($end_time) {
                        $event['end'] = $event_date . 'T' . $end_time;
                    }
                }
                
                $events_array[] = $event;
            }
        }
        
        wp_reset_postdata();
        
        return $events_array;
    }
    
    /**
     * Generate Calendar CSS with Designer Settings
     * Integrates Design Settings into FullCalendar styling
     */
    private function generate_calendar_css() {
        // Get Design Settings if available
        $settings = array();
        if (class_exists('ES_Design_Settings')) {
            $settings = ES_Design_Settings::get_settings();
        }
        
        // Fallback values if Design Settings not available
        $primary = $settings['primary_color'] ?? '#667eea';
        $secondary = $settings['secondary_color'] ?? '#764ba2';
        $card_bg = $settings['card_background'] ?? '#ffffff';
        $text = $settings['text_color'] ?? '#1a202c';
        $border = $settings['border_color'] ?? '#e2e8f0';
        $card_radius = $settings['card_radius'] ?? 12;
        $button_radius = $settings['button_radius'] ?? 8;
        $font_body = $settings['body_font'] ?? 'inherit';
        $font_heading = $settings['heading_font'] ?? 'inherit';
        
        ob_start();
        ?>
        
        /* Ensemble Calendar - Designer Settings Integration */
        .ensemble-fullcalendar-wrapper {
            margin: 20px 0;
            background: <?php echo esc_attr($card_bg); ?>;
            padding: 20px;
            border-radius: <?php echo esc_attr($card_radius); ?>px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border: 1px solid <?php echo esc_attr($border); ?>;
        }
        
        .ensemble-fullcalendar {
            max-width: 100%;
        }
        
        /* FullCalendar Base Styling */
        .fc {
            font-family: '<?php echo esc_attr($font_body); ?>', sans-serif;
            color: <?php echo esc_attr($text); ?>;
        }
        
        /* Calendar Title */
        .fc .fc-toolbar-title {
            font-family: '<?php echo esc_attr($font_heading); ?>', sans-serif;
            color: <?php echo esc_attr($text); ?>;
        }
        
        /* Buttons */
        .fc .fc-button {
            background: <?php echo esc_attr($primary); ?>;
            border-color: <?php echo esc_attr($primary); ?>;
            color: #fff;
            text-transform: none;
            padding: 8px 16px;
            font-size: 14px;
            font-weight: 500;
            border-radius: <?php echo esc_attr($button_radius); ?>px;
            transition: all 0.2s ease;
        }
        
        .fc .fc-button:hover {
            background: <?php echo esc_attr($secondary); ?>;
            border-color: <?php echo esc_attr($secondary); ?>;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .fc .fc-button:active {
            transform: translateY(0);
        }
        
        .fc .fc-button-primary:disabled {
            background: #ccc;
            border-color: #ccc;
            opacity: 0.5;
        }
        
        .fc .fc-button-active {
            background: <?php echo esc_attr($secondary); ?>;
            border-color: <?php echo esc_attr($secondary); ?>;
        }
        
        /* Grid Lines */
        .fc-theme-standard .fc-scrollgrid {
            border-color: <?php echo esc_attr($border); ?>;
        }
        
        .fc-theme-standard td,
        .fc-theme-standard th {
            border-color: <?php echo esc_attr($border); ?>;
        }
        
        /* Header */
        .fc-col-header-cell {
            background: rgba(<?php 
                // Convert hex to rgba for subtle background
                $rgb = sscanf($primary, "#%02x%02x%02x");
                echo implode(',', $rgb);
            ?>, 0.05);
            font-weight: 600;
            padding: 12px 8px;
            color: <?php echo esc_attr($text); ?>;
        }
        
        /* Day Numbers */
        .fc-daygrid-day-number {
            padding: 8px;
            font-weight: 500;
            color: <?php echo esc_attr($text); ?>;
        }
        
        /* Today Highlight */
        .fc-day-today {
            background: rgba(<?php 
                $rgb = sscanf($primary, "#%02x%02x%02x");
                echo implode(',', $rgb);
            ?>, 0.1) !important;
        }
        
        .fc-day-today .fc-daygrid-day-number {
            background: <?php echo esc_attr($primary); ?>;
            color: #fff;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Events */
        .fc-event {
            background: <?php echo esc_attr($primary); ?>;
            border-color: <?php echo esc_attr($primary); ?>;
            border-radius: <?php echo max(3, $button_radius - 4); ?>px;
            padding: 4px 8px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .fc-event:hover {
            background: <?php echo esc_attr($secondary); ?>;
            border-color: <?php echo esc_attr($secondary); ?>;
            transform: scale(1.02);
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        
        .fc-event-recurring {
            border-left: 4px solid #f39c12 !important;
        }
        
        .fc-event-title {
            font-weight: 600;
            font-size: 13px;
        }
        
        .fc-daygrid-event {
            cursor: pointer;
        }
        
        /* List View Styling */
        .fc-list-event:hover td {
            background: rgba(<?php 
                $rgb = sscanf($primary, "#%02x%02x%02x");
                echo implode(',', $rgb);
            ?>, 0.05);
        }
        
        .fc-list-event-dot {
            border-color: <?php echo esc_attr($primary); ?>;
        }
        
        /* Week/Day View Time Grid */
        .fc-timegrid-slot {
            height: 3em;
        }
        
        .fc-timegrid-event {
            border-radius: <?php echo max(2, $button_radius - 6); ?>px;
        }
        
        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .ensemble-fullcalendar-wrapper {
                padding: 12px;
            }
            
            .fc .fc-button {
                padding: 6px 10px;
                font-size: 12px;
            }
            
            .fc .fc-toolbar-title {
                font-size: 18px;
            }
        }
        
        /* Multi-Day Events */
        .fc-event-multi-day {
            background: linear-gradient(90deg, <?php echo esc_attr($primary); ?> 0%, <?php echo esc_attr($secondary); ?> 100%);
            border: none;
            border-left: 4px solid <?php echo esc_attr($primary); ?>;
        }
        
        .fc-event-multi-day .fc-event-title {
            font-weight: 700;
        }
        
        /* Permanent Events */
        .fc-event-permanent {
            background: linear-gradient(90deg, #10b981 0%, #059669 100%);
            border: none;
            border-left: 4px solid #10b981;
        }
        
        <?php
        return ob_get_clean();
    }
    
    /**
     * AJAX handler for calendar events
     * Called by FullCalendar when view changes
     */
    public static function ajax_get_calendar_events() {
        // Verify nonce
        check_ajax_referer('ensemble_calendar', 'nonce');
        
        // Get parameters
        $start = isset($_POST['start']) ? sanitize_text_field($_POST['start']) : '';
        $end = isset($_POST['end']) ? sanitize_text_field($_POST['end']) : '';
        
        if (empty($start) || empty($end)) {
            wp_send_json_error('Invalid date range');
        }
        
        // Create instance to use non-static methods
        $shortcodes = new ES_Calendar_Shortcode();
        $events = $shortcodes->get_calendar_events($start, $end);
        
        wp_send_json_success($events);
    }
}
