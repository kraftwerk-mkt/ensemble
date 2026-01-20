<?php
/**
 * Ensemble Timetable Addon
 * 
 * Dual-Mode Support:
 * - Single Event Mode (Conference): 1 Event → Multiple Speakers → Rooms
 * - Multi-Event Mode (Festival): Multiple Events → Stages → Timeline
 *
 * @package Ensemble
 * @subpackage Addons
 * @version 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ES_Timetable_Addon {

    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * Addon version
     */
    const VERSION = '2.0.0';

    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     * Public for ES_Addon_Manager compatibility
     */
    public function __construct() {
        // Prevent double initialization
        if ( null !== self::$instance ) {
            return;
        }
        self::$instance = $this;
        
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Admin menu
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ), 20 );
        
        // Enqueue scripts
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
        
        // Frontend assets
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
        
        // Shortcodes
        add_shortcode( 'ensemble_timetable', array( $this, 'shortcode_timetable' ) );
        add_shortcode( 'ensemble_agenda', array( $this, 'shortcode_agenda' ) );
        
        // AJAX handlers - Single Event Mode
        add_action( 'wp_ajax_es_load_timetable', array( $this, 'ajax_load_timetable' ) );
        add_action( 'wp_ajax_es_save_timetable_entry', array( $this, 'ajax_save_entry' ) );
        add_action( 'wp_ajax_es_delete_timetable_entry', array( $this, 'ajax_delete_entry' ) );
        
        // AJAX handlers - Multi Event Mode (Festival)
        add_action( 'wp_ajax_es_load_multi_timetable', array( $this, 'ajax_load_multi_timetable' ) );
        add_action( 'wp_ajax_es_update_event_schedule', array( $this, 'ajax_update_event_schedule' ) );
        add_action( 'wp_ajax_es_get_festival_locations', array( $this, 'ajax_get_locations' ) );
        
        // AJAX handler - Settings
        add_action( 'wp_ajax_es_save_timetable_settings', array( $this, 'ajax_save_settings' ) );
    }

    /**
     * AJAX: Save timetable settings
     */
    public function ajax_save_settings() {
        check_ajax_referer( 'es_timetable_settings_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permission denied' );
        }

        $input = isset( $_POST['settings'] ) ? $_POST['settings'] : array();
        $sanitized = $this->sanitize_settings( $input );
        
        update_option( 'es_timetable_settings', $sanitized );

        wp_send_json_success( array(
            'message' => __( 'Settings saved', 'flavor' ),
            'settings' => $sanitized,
        ) );
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'ensemble',
            __( 'Timetable', 'flavor' ),
            __( 'Timetable', 'flavor' ),
            'edit_posts',
            'ensemble-timetable',
            array( $this, 'render_admin_page' )
        );
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets( $hook ) {
        if ( strpos( $hook, 'ensemble-timetable' ) === false ) {
            return;
        }

        // jQuery UI for drag/drop/resize
        wp_enqueue_script( 'jquery-ui-draggable' );
        wp_enqueue_script( 'jquery-ui-droppable' );
        wp_enqueue_script( 'jquery-ui-resizable' );
        wp_enqueue_script( 'jquery-ui-sortable' );

        // Timetable CSS
        wp_enqueue_style(
            'es-timetable-admin',
            $this->get_addon_url() . 'assets/css/timetable-admin.css',
            array(),
            self::VERSION
        );

        // Unified Admin Styles
        wp_enqueue_style( 'es-admin-unified' );

        // Timetable JS
        wp_enqueue_script(
            'es-timetable-admin',
            $this->get_addon_url() . 'assets/js/timetable-admin.js',
            array( 'jquery', 'jquery-ui-draggable', 'jquery-ui-droppable', 'jquery-ui-resizable' ),
            self::VERSION,
            true
        );

        // Localize script
        wp_localize_script( 'es-timetable-admin', 'esTimetable', array(
            'ajax_url'       => admin_url( 'admin-ajax.php' ),
            'nonce'          => wp_create_nonce( 'es_timetable_nonce' ),
            'strings'        => array(
                'confirm_delete' => __( 'Really delete this entry?', 'flavor' ),
                'saving'         => __( 'Saving...', 'flavor' ),
                'saved'          => __( 'Saved!', 'flavor' ),
                'error'          => __( 'Error saving', 'flavor' ),
                'no_events'      => __( 'No events in this period', 'flavor' ),
                'unscheduled'    => __( 'Not scheduled', 'flavor' ),
            ),
            'pixel_per_min'  => 2, // 2px per minute = 120px per hour
        ) );
    }

    /**
     * Get addon URL
     */
    private function get_addon_url() {
        // Check if in plugin or theme
        if ( defined( 'FLAVOR_ADDON_PATH' ) ) {
            return FLAVOR_ADDON_URL . 'timetable/';
        }
        return plugin_dir_url( __FILE__ );
    }

    /**
     * Render admin page
     */
    public function render_admin_page() {
        include dirname( __FILE__ ) . '/templates/admin/timetable-editor.php';
    }

    // =========================================
    // MULTI-EVENT MODE (FESTIVAL) METHODS
    // =========================================

    /**
     * Get all locations/stages
     */
    public function get_all_locations() {
        $post_type = function_exists( 'ensemble_get_location_post_type' ) 
            ? ensemble_get_location_post_type() 
            : 'ensemble_location';

        $locations = get_posts( array(
            'post_type'      => $post_type,
            'posts_per_page' => -1,
            'orderby'        => 'menu_order title',
            'order'          => 'ASC',
            'post_status'    => 'publish',
        ) );

        $result = array();
        foreach ( $locations as $location ) {
            $color = get_post_meta( $location->ID, 'stage_color', true );
            if ( ! $color ) {
                $color = $this->generate_color( $location->ID );
            }

            $result[] = array(
                'id'    => $location->ID,
                'name'  => $location->post_title,
                'color' => $color,
            );
        }

        return $result;
    }

    /**
     * Generate consistent color from ID
     */
    private function generate_color( $id ) {
        $colors = array(
            '#e94560', '#3582c4', '#4caf50', '#f0b849', 
            '#9c27b0', '#00bcd4', '#ff5722', '#607d8b',
            '#8bc34a', '#673ab7', '#03a9f4', '#ff9800',
        );
        return $colors[ $id % count( $colors ) ];
    }

    /**
     * Get multi-event data for timeline
     */
    public function get_multi_event_data( $date_from = '', $date_to = '' ) {
        $post_type = function_exists( 'ensemble_get_post_type' ) 
            ? ensemble_get_post_type() 
            : 'ensemble_event';

        // Default: next 30 days
        if ( empty( $date_from ) ) {
            $date_from = date( 'Y-m-d' );
        }
        if ( empty( $date_to ) ) {
            $date_to = date( 'Y-m-d', strtotime( '+30 days' ) );
        }

        // Query events in date range
        $args = array(
            'post_type'      => $post_type,
            'posts_per_page' => -1,
            'post_status'    => array( 'publish', 'draft' ),
            'meta_query'     => array(
                'relation' => 'AND',
                array(
                    'relation' => 'OR',
                    array(
                        'key'     => 'es_event_start_date',
                        'value'   => array( $date_from, $date_to ),
                        'compare' => 'BETWEEN',
                        'type'    => 'DATE',
                    ),
                    array(
                        'key'     => 'event_start_date',
                        'value'   => array( $date_from, $date_to ),
                        'compare' => 'BETWEEN',
                        'type'    => 'DATE',
                    ),
                ),
            ),
            'orderby'        => 'meta_value',
            'meta_key'       => 'es_event_start_date',
            'order'          => 'ASC',
        );

        $events = get_posts( $args );
        $locations = $this->get_all_locations();
        $location_map = array();
        foreach ( $locations as $loc ) {
            $location_map[ $loc['id'] ] = $loc;
        }

        $result = array(
            'events'    => array(),
            'days'      => array(),
            'locations' => $locations,
            'date_from' => $date_from,
            'date_to'   => $date_to,
        );

        $days_seen = array();

        foreach ( $events as $event ) {
            $event_data = $this->get_event_timeline_data( $event, $location_map );
            $result['events'][] = $event_data;

            // Collect unique days
            if ( ! empty( $event_data['date'] ) && ! isset( $days_seen[ $event_data['date'] ] ) ) {
                $days_seen[ $event_data['date'] ] = array(
                    'date'     => $event_data['date'],
                    'label'    => date_i18n( 'd.m.Y', strtotime( $event_data['date'] ) ),
                    'day_name' => date_i18n( 'l', strtotime( $event_data['date'] ) ),
                );
            }
        }

        // Sort days
        ksort( $days_seen );
        $result['days'] = array_values( $days_seen );

        return $result;
    }

    /**
     * Get single event data for timeline
     */
    private function get_event_timeline_data( $event, $location_map ) {
        $id = $event->ID;

        // Get meta with fallbacks
        $date = get_post_meta( $id, 'es_event_start_date', true );
        if ( ! $date ) {
            $date = get_post_meta( $id, 'event_start_date', true );
        }

        $start_time = get_post_meta( $id, 'es_event_start_time', true );
        if ( ! $start_time ) {
            $start_time = get_post_meta( $id, 'event_start_time', true );
        }

        $end_time = get_post_meta( $id, 'es_event_end_time', true );
        if ( ! $end_time ) {
            $end_time = get_post_meta( $id, 'event_end_time', true );
        }

        $location_id = get_post_meta( $id, 'event_location', true );
        if ( ! $location_id ) {
            $location_id = get_post_meta( $id, 'es_event_location', true );
        }

        // Get artist (first one)
        $artist_ids = get_post_meta( $id, 'event_artist', true );
        $artist_name = '';
        if ( ! empty( $artist_ids ) ) {
            $artist_id = is_array( $artist_ids ) ? $artist_ids[0] : $artist_ids;
            $artist = get_post( $artist_id );
            if ( $artist ) {
                $artist_name = $artist->post_title;
            }
        }

        // Calculate duration
        $duration = 60; // default 1 hour
        if ( $start_time && $end_time ) {
            $start_mins = $this->time_to_minutes( $start_time );
            $end_mins = $this->time_to_minutes( $end_time );
            if ( $end_mins > $start_mins ) {
                $duration = $end_mins - $start_mins;
            }
        }

        // Location data
        $location_name = '';
        $location_color = '#666';
        if ( $location_id && isset( $location_map[ $location_id ] ) ) {
            $location_name = $location_map[ $location_id ]['name'];
            $location_color = $location_map[ $location_id ]['color'];
        }

        return array(
            'id'             => $id,
            'title'          => $event->post_title,
            'artist'         => $artist_name,
            'date'           => $date,
            'start_time'     => $start_time,
            'end_time'       => $end_time,
            'duration'       => $duration,
            'location_id'    => (int) $location_id,
            'location_name'  => $location_name,
            'location_color' => $location_color,
            'image'          => get_the_post_thumbnail_url( $id, 'thumbnail' ),
            'status'         => $event->post_status,
            'scheduled'      => ! empty( $date ) && ! empty( $start_time ) && ! empty( $location_id ),
            'edit_url'       => get_edit_post_link( $id, 'raw' ),
        );
    }

    /**
     * Convert time string to minutes
     */
    private function time_to_minutes( $time ) {
        if ( preg_match( '/(\d{1,2}):(\d{2})/', $time, $m ) ) {
            return (int) $m[1] * 60 + (int) $m[2];
        }
        return 0;
    }

    /**
     * Convert minutes to time string
     */
    private function minutes_to_time( $minutes ) {
        $hours = floor( $minutes / 60 );
        $mins = $minutes % 60;
        return sprintf( '%02d:%02d', $hours, $mins );
    }

    // =========================================
    // AJAX HANDLERS - MULTI EVENT MODE
    // =========================================

    /**
     * AJAX: Load multi-event timetable
     */
    public function ajax_load_multi_timetable() {
        check_ajax_referer( 'es_timetable_nonce', 'nonce' );

        $date_from = isset( $_POST['date_from'] ) ? sanitize_text_field( $_POST['date_from'] ) : '';
        $date_to = isset( $_POST['date_to'] ) ? sanitize_text_field( $_POST['date_to'] ) : '';

        $data = $this->get_multi_event_data( $date_from, $date_to );

        wp_send_json_success( $data );
    }

    /**
     * AJAX: Update event schedule (drag/drop/resize)
     */
    public function ajax_update_event_schedule() {
        check_ajax_referer( 'es_timetable_nonce', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( 'Permission denied' );
        }

        $event_id = isset( $_POST['event_id'] ) ? (int) $_POST['event_id'] : 0;
        if ( ! $event_id ) {
            wp_send_json_error( 'Invalid event ID' );
        }

        $date = isset( $_POST['date'] ) ? sanitize_text_field( $_POST['date'] ) : '';
        $start_time = isset( $_POST['start_time'] ) ? sanitize_text_field( $_POST['start_time'] ) : '';
        $end_time = isset( $_POST['end_time'] ) ? sanitize_text_field( $_POST['end_time'] ) : '';
        $location_id = isset( $_POST['location_id'] ) ? (int) $_POST['location_id'] : 0;
        $unschedule = isset( $_POST['unschedule'] ) && $_POST['unschedule'] === 'true';

        if ( $unschedule ) {
            // Clear scheduling
            delete_post_meta( $event_id, 'es_event_start_date' );
            delete_post_meta( $event_id, 'event_start_date' );
            delete_post_meta( $event_id, 'es_event_start_time' );
            delete_post_meta( $event_id, 'event_start_time' );
            delete_post_meta( $event_id, 'es_event_end_time' );
            delete_post_meta( $event_id, 'event_end_time' );
            delete_post_meta( $event_id, 'event_location' );
            delete_post_meta( $event_id, 'es_event_location' );
        } else {
            // Update meta (both formats for compatibility)
            if ( $date ) {
                update_post_meta( $event_id, 'es_event_start_date', $date );
                update_post_meta( $event_id, 'event_start_date', $date );
            }
            if ( $start_time ) {
                update_post_meta( $event_id, 'es_event_start_time', $start_time );
                update_post_meta( $event_id, 'event_start_time', $start_time );
            }
            if ( $end_time ) {
                update_post_meta( $event_id, 'es_event_end_time', $end_time );
                update_post_meta( $event_id, 'event_end_time', $end_time );
            }
            if ( $location_id ) {
                update_post_meta( $event_id, 'event_location', $location_id );
                update_post_meta( $event_id, 'es_event_location', $location_id );
            }
        }

        wp_send_json_success( array(
            'event_id' => $event_id,
            'message'  => $unschedule ? 'Event unscheduled' : 'Event updated',
        ) );
    }

    /**
     * AJAX: Get locations
     */
    public function ajax_get_locations() {
        check_ajax_referer( 'es_timetable_nonce', 'nonce' );
        
        $locations = $this->get_all_locations();
        wp_send_json_success( $locations );
    }

    // =========================================
    // SINGLE EVENT MODE (CONFERENCE) METHODS
    // =========================================

    /**
     * Get events for dropdown
     */
    public function get_events_for_select() {
        $post_type = function_exists( 'ensemble_get_post_type' ) 
            ? ensemble_get_post_type() 
            : 'ensemble_event';

        return get_posts( array(
            'post_type'      => $post_type,
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'post_status'    => array( 'publish', 'draft' ),
        ) );
    }

    /**
     * Get speakers/artists for an event
     */
    public function get_event_speakers( $event_id ) {
        $artist_ids = get_post_meta( $event_id, 'event_artist', true );
        if ( empty( $artist_ids ) ) {
            return array();
        }

        if ( ! is_array( $artist_ids ) ) {
            $artist_ids = array( $artist_ids );
        }

        $speakers = array();
        foreach ( $artist_ids as $id ) {
            $artist = get_post( $id );
            if ( $artist ) {
                $speakers[] = array(
                    'id'    => $id,
                    'name'  => $artist->post_title,
                    'image' => get_the_post_thumbnail_url( $id, 'thumbnail' ),
                );
            }
        }

        return $speakers;
    }

    /**
     * Get rooms/locations for an event
     */
    public function get_event_rooms( $event_id ) {
        return $this->get_all_locations();
    }

    /**
     * Get timetable entries for single event mode
     */
    public function get_timetable_entries( $event_id ) {
        $entries = get_post_meta( $event_id, '_es_timetable_entries', true );
        return is_array( $entries ) ? $entries : array();
    }

    /**
     * Save timetable entries
     */
    public function save_timetable_entries( $event_id, $entries ) {
        update_post_meta( $event_id, '_es_timetable_entries', $entries );
    }

    // =========================================
    // AJAX HANDLERS - SINGLE EVENT MODE
    // =========================================

    /**
     * AJAX: Load timetable for single event
     */
    public function ajax_load_timetable() {
        check_ajax_referer( 'es_timetable_nonce', 'nonce' );

        $event_id = isset( $_POST['event_id'] ) ? (int) $_POST['event_id'] : 0;
        if ( ! $event_id ) {
            wp_send_json_error( 'Invalid event ID' );
        }

        $data = array(
            'entries'  => $this->get_timetable_entries( $event_id ),
            'speakers' => $this->get_event_speakers( $event_id ),
            'rooms'    => $this->get_event_rooms( $event_id ),
        );

        wp_send_json_success( $data );
    }

    /**
     * AJAX: Save timetable entry
     */
    public function ajax_save_entry() {
        check_ajax_referer( 'es_timetable_nonce', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( 'Permission denied' );
        }

        $event_id = isset( $_POST['event_id'] ) ? (int) $_POST['event_id'] : 0;
        $entry = isset( $_POST['entry'] ) ? $_POST['entry'] : array();

        if ( ! $event_id || empty( $entry ) ) {
            wp_send_json_error( 'Invalid data' );
        }

        // Sanitize entry
        $clean_entry = array(
            'id'         => isset( $entry['id'] ) ? sanitize_text_field( $entry['id'] ) : uniqid(),
            'speaker_id' => isset( $entry['speaker_id'] ) ? (int) $entry['speaker_id'] : 0,
            'room_id'    => isset( $entry['room_id'] ) ? (int) $entry['room_id'] : 0,
            'start_time' => isset( $entry['start_time'] ) ? sanitize_text_field( $entry['start_time'] ) : '',
            'end_time'   => isset( $entry['end_time'] ) ? sanitize_text_field( $entry['end_time'] ) : '',
            'title'      => isset( $entry['title'] ) ? sanitize_text_field( $entry['title'] ) : '',
            'is_break'   => isset( $entry['is_break'] ) && $entry['is_break'],
        );

        $entries = $this->get_timetable_entries( $event_id );
        
        // Update or add
        $found = false;
        foreach ( $entries as $key => $existing ) {
            if ( $existing['id'] === $clean_entry['id'] ) {
                $entries[ $key ] = $clean_entry;
                $found = true;
                break;
            }
        }
        if ( ! $found ) {
            $entries[] = $clean_entry;
        }

        $this->save_timetable_entries( $event_id, $entries );

        wp_send_json_success( array(
            'entry'   => $clean_entry,
            'message' => 'Entry saved',
        ) );
    }

    /**
     * AJAX: Delete timetable entry
     */
    public function ajax_delete_entry() {
        check_ajax_referer( 'es_timetable_nonce', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( 'Permission denied' );
        }

        $event_id = isset( $_POST['event_id'] ) ? (int) $_POST['event_id'] : 0;
        $entry_id = isset( $_POST['entry_id'] ) ? sanitize_text_field( $_POST['entry_id'] ) : '';

        if ( ! $event_id || ! $entry_id ) {
            wp_send_json_error( 'Invalid data' );
        }

        $entries = $this->get_timetable_entries( $event_id );
        $entries = array_filter( $entries, function( $e ) use ( $entry_id ) {
            return $e['id'] !== $entry_id;
        } );

        $this->save_timetable_entries( $event_id, array_values( $entries ) );

        wp_send_json_success( 'Entry deleted' );
    }

    // =========================================
    // FRONTEND METHODS
    // =========================================

    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        // Only load if shortcode is used
        global $post;
        if ( ! is_a( $post, 'WP_Post' ) ) {
            return;
        }

        $has_timetable = has_shortcode( $post->post_content, 'ensemble_timetable' );
        $has_agenda = has_shortcode( $post->post_content, 'ensemble_agenda' );

        if ( ! $has_timetable && ! $has_agenda ) {
            return;
        }

        wp_enqueue_style(
            'es-timetable-frontend',
            $this->get_addon_url() . 'assets/css/timetable-frontend.css',
            array(),
            self::VERSION
        );

        if ( $has_timetable ) {
            wp_enqueue_script(
                'es-timetable-frontend',
                $this->get_addon_url() . 'assets/js/timetable-frontend.js',
                array( 'jquery' ),
                self::VERSION,
                true
            );
        }
    }

    /**
     * Shortcode: Festival Timetable
     * 
     * Usage: [ensemble_timetable days="3" start="12:00" end="02:00" locations="1,2,3" layout="vertical"]
     */
    public function shortcode_timetable( $atts ) {
        // Load settings as defaults
        $settings = $this->get_settings();
        
        // Default locations from settings
        $default_locations = ! empty( $settings['default_locations'] ) 
            ? implode( ',', $settings['default_locations'] ) 
            : '';

        $atts = shortcode_atts( array(
            // Date range
            'date_from'     => date( 'Y-m-d' ),
            'date_to'       => date( 'Y-m-d', strtotime( '+7 days' ) ),
            'days'          => '',
            
            // Time range
            'start'         => $settings['default_start_time'],
            'end'           => $settings['default_end_time'],
            
            // Locations
            'locations'     => $default_locations,
            
            // Layout
            'layout'        => $settings['default_layout'],  // horizontal or vertical
            'slot_width'    => $settings['slot_width'],      // px per hour (horizontal)
            'slot_height'   => $settings['slot_height'],     // px per hour (vertical)
            
            // Display options
            'show_filter'   => 'true',
            'show_image'    => $settings['show_image'] ? 'true' : 'false',
            'show_title'    => $settings['show_title'] ? 'true' : 'false',
            'show_time'     => $settings['show_time'] ? 'true' : 'false',
            'show_artist'   => $settings['show_artist'] ? 'true' : 'false',
            'show_genre'    => $settings['show_genre'] ? 'true' : 'false',
            'image_position'=> $settings['image_position'],
            
            // Extra
            'class'         => '',
        ), $atts, 'ensemble_timetable' );

        // Days override
        if ( ! empty( $atts['days'] ) ) {
            $atts['date_from'] = date( 'Y-m-d' );
            $atts['date_to'] = date( 'Y-m-d', strtotime( '+' . intval( $atts['days'] ) . ' days' ) );
        }

        // Get data
        $data = $this->get_multi_event_data( $atts['date_from'], $atts['date_to'] );

        // Filter locations if specified
        if ( ! empty( $atts['locations'] ) ) {
            $location_ids = array_map( 'intval', explode( ',', $atts['locations'] ) );
            $data['locations'] = array_filter( $data['locations'], function( $loc ) use ( $location_ids ) {
                return in_array( $loc['id'], $location_ids );
            } );
            $data['events'] = array_filter( $data['events'], function( $ev ) use ( $location_ids ) {
                return in_array( $ev['location_id'], $location_ids );
            } );
            // Re-index arrays
            $data['locations'] = array_values( $data['locations'] );
            $data['events'] = array_values( $data['events'] );
        }

        // Apply custom colors from settings to locations
        $location_colors = isset( $settings['location_colors'] ) ? $settings['location_colors'] : array();
        foreach ( $data['locations'] as &$loc ) {
            if ( isset( $location_colors[ $loc['id'] ] ) && ! empty( $location_colors[ $loc['id'] ] ) ) {
                $loc['color'] = $location_colors[ $loc['id'] ];
            }
        }
        unset( $loc );

        // Also update event colors based on location
        $location_color_map = array();
        foreach ( $data['locations'] as $loc ) {
            $location_color_map[ $loc['id'] ] = $loc['color'];
        }
        foreach ( $data['events'] as &$ev ) {
            if ( isset( $location_color_map[ $ev['location_id'] ] ) ) {
                $ev['location_color'] = $location_color_map[ $ev['location_id'] ];
            }
        }
        unset( $ev );

        // Parse time range
        $time_start = $this->time_to_minutes( $atts['start'] );
        $time_end = $this->time_to_minutes( $atts['end'] );
        if ( $time_end <= $time_start ) {
            $time_end += 24 * 60; // Next day
        }
        $total_minutes = $time_end - $time_start;

        // Convert string booleans
        $display_options = array(
            'show_filter'        => $atts['show_filter'] === 'true',
            'show_image'         => $atts['show_image'] === 'true',
            'show_title'         => $atts['show_title'] === 'true',
            'show_time'          => $atts['show_time'] === 'true',
            'show_artist'        => $atts['show_artist'] === 'true',
            'show_genre'         => $atts['show_genre'] === 'true',
            'image_position'     => $atts['image_position'],
            'max_stages_all_days'=> intval( $settings['max_stages_all_days'] ),
        );

        // Choose template based on layout
        $template = $atts['layout'] === 'vertical' 
            ? '/templates/frontend/timetable-vertical.php'
            : '/templates/frontend/timetable.php';

        ob_start();
        include dirname( __FILE__ ) . $template;
        return ob_get_clean();
    }

    /**
     * Shortcode: Conference Agenda
     * 
     * Usage: [ensemble_agenda event="123"]
     */
    public function shortcode_agenda( $atts ) {
        $atts = shortcode_atts( array(
            'event'      => '',      // Event ID (required)
            'show_room'  => 'true',  // Raum anzeigen
            'show_speaker' => 'true',// Speaker anzeigen
            'class'      => '',      // Extra CSS Klasse
        ), $atts, 'ensemble_agenda' );

        // Auto-detect if on single event
        if ( empty( $atts['event'] ) && is_singular() ) {
            $post_type = function_exists( 'ensemble_get_post_type' ) ? ensemble_get_post_type() : 'ensemble_event';
            if ( get_post_type() === $post_type ) {
                $atts['event'] = get_the_ID();
            }
        }

        if ( empty( $atts['event'] ) ) {
            return '<p class="es-timetable-error">' . __( 'No event specified.', 'flavor' ) . '</p>';
        }

        $event_id = intval( $atts['event'] );
        $entries = $this->get_timetable_entries( $event_id );
        $speakers = $this->get_event_speakers( $event_id );
        $rooms = $this->get_event_rooms( $event_id );

        // Sort by time
        usort( $entries, function( $a, $b ) {
            return strcmp( $a['start_time'] ?? '', $b['start_time'] ?? '' );
        } );

        // Create lookup maps
        $speaker_map = array();
        foreach ( $speakers as $s ) {
            $speaker_map[ $s['id'] ] = $s;
        }
        $room_map = array();
        foreach ( $rooms as $r ) {
            $room_map[ $r['id'] ] = $r;
        }

        ob_start();
        include dirname( __FILE__ ) . '/templates/frontend/agenda.php';
        return ob_get_clean();
    }

    // =========================================
    // ADDON SETTINGS (for Addon Modal)
    // =========================================

    /**
     * Get addon settings
     */
    public function get_settings() {
        $defaults = array(
            'default_locations'  => array(),      // Empty = all
            'location_colors'    => array(),      // Location ID => Color
            'default_layout'     => 'horizontal', // horizontal or vertical
            'default_start_time' => '12:00',
            'default_end_time'   => '02:00',
            'show_image'         => true,
            'show_title'         => true,
            'show_time'          => true,
            'show_artist'        => true,
            'show_genre'         => false,
            'show_description'   => false,
            'image_position'     => 'left',       // left, top, background
            'slot_height'        => 90,           // px für vertikales Layout
            'slot_width'         => 120,          // px pro Stunde
            'max_stages_all_days'=> 4,            // Alle Tage nur wenn <= X Stages
        );

        $saved = get_option( 'es_timetable_settings', array() );
        return wp_parse_args( $saved, $defaults );
    }

    /**
     * Render settings for addon modal
     */
    public function render_settings() {
        $settings = $this->get_settings();
        $locations = $this->get_all_locations();
        
        include dirname( __FILE__ ) . '/templates/admin/settings.php';
    }

    /**
     * Sanitize settings
     */
    public function sanitize_settings( $input ) {
        $sanitized = array();

        // Locations (array of IDs)
        if ( isset( $input['default_locations'] ) && is_array( $input['default_locations'] ) ) {
            $sanitized['default_locations'] = array_map( 'intval', $input['default_locations'] );
        } else {
            $sanitized['default_locations'] = array();
        }

        // Location colors (ID => hex color)
        if ( isset( $input['location_colors'] ) && is_array( $input['location_colors'] ) ) {
            $sanitized['location_colors'] = array();
            foreach ( $input['location_colors'] as $loc_id => $color ) {
                $sanitized['location_colors'][ intval( $loc_id ) ] = sanitize_hex_color( $color );
            }
        } else {
            $sanitized['location_colors'] = array();
        }

        // Layout
        $sanitized['default_layout'] = in_array( $input['default_layout'] ?? '', array( 'horizontal', 'vertical' ) ) 
            ? $input['default_layout'] 
            : 'horizontal';

        // Times
        $sanitized['default_start_time'] = sanitize_text_field( $input['default_start_time'] ?? '12:00' );
        $sanitized['default_end_time'] = sanitize_text_field( $input['default_end_time'] ?? '02:00' );

        // Display options (checkboxes)
        $sanitized['show_image'] = ! empty( $input['show_image'] );
        $sanitized['show_title'] = ! empty( $input['show_title'] );
        $sanitized['show_time'] = ! empty( $input['show_time'] );
        $sanitized['show_artist'] = ! empty( $input['show_artist'] );
        $sanitized['show_genre'] = ! empty( $input['show_genre'] );
        $sanitized['show_description'] = ! empty( $input['show_description'] );

        // Image position
        $sanitized['image_position'] = in_array( $input['image_position'] ?? '', array( 'left', 'top', 'background', 'none' ) )
            ? $input['image_position']
            : 'left';

        // Dimensions
        $sanitized['slot_height'] = max( 60, min( 200, intval( $input['slot_height'] ?? 90 ) ) );
        $sanitized['slot_width'] = max( 60, min( 200, intval( $input['slot_width'] ?? 120 ) ) );

        // Max stages for "All Days" view (0 = always show)
        $sanitized['max_stages_all_days'] = max( 0, min( 20, intval( $input['max_stages_all_days'] ?? 4 ) ) );

        return $sanitized;
    }
}

// Note: Addon is initialized by ES_Addon_Manager
