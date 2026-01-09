<?php
/**
 * Export AJAX Handler
 * Handles AJAX requests for export functionality
 *
 * @package Ensemble
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Ensemble_Export_Ajax {

    private $handler;

    public function __construct() {
        require_once dirname( __FILE__ ) . '/class-export-handler.php';
        $this->handler = new Ensemble_Export_Handler();
        
        $this->init();
    }

    /**
     * Initialize AJAX hooks
     */
    public function init() {
        add_action( 'wp_ajax_ensemble_export_count', array( $this, 'ajax_count' ) );
        add_action( 'wp_ajax_ensemble_export_preview', array( $this, 'ajax_preview' ) );
        add_action( 'wp_ajax_ensemble_export_events', array( $this, 'ajax_export' ) );
    }

    /**
     * Get count of events to export
     */
    public function ajax_count() {
        // Verify nonce
        if ( ! check_ajax_referer( 'ensemble_export_nonce', 'nonce', false ) ) {
            wp_send_json_error( 'Invalid security token' );
            return;
        }

        // Check permissions
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Insufficient permissions' );
            return;
        }

        $args = $this->get_export_args();
        $count = $this->get_event_count( $args );

        wp_send_json_success( array( 'count' => $count ) );
    }

    /**
     * Get preview of events
     */
    public function ajax_preview() {
        // Verify nonce
        if ( ! check_ajax_referer( 'ensemble_export_nonce', 'nonce', false ) ) {
            wp_send_json_error( 'Invalid security token' );
            return;
        }

        // Check permissions
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Insufficient permissions' );
            return;
        }

        $args = $this->get_export_args();
        $events = $this->get_events_preview( $args );

        wp_send_json_success( array( 'events' => $events ) );
    }

    /**
     * Export events to iCal
     */
    public function ajax_export() {
        // Verify nonce
        if ( ! check_ajax_referer( 'ensemble_export_nonce', 'nonce', false ) ) {
            wp_send_json_error( 'Invalid security token' );
            return;
        }

        // Check permissions
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Insufficient permissions' );
            return;
        }

        $args = $this->get_export_args();
        
        // Generate iCal
        $ical = $this->handler->export_to_ical( $args );

        if ( is_wp_error( $ical ) ) {
            wp_send_json_error( $ical->get_error_message() );
            return;
        }

        // Save to temporary file
        $upload_dir = wp_upload_dir();
        $filename = 'ensemble-export-' . date( 'Y-m-d' ) . '.ics';
        $filepath = $upload_dir['path'] . '/' . $filename;
        
        file_put_contents( $filepath, $ical );

        // Generate download URL
        $download_url = add_query_arg(
            array(
                'action' => 'ensemble_download_export',
                'file' => $filename,
                'nonce' => wp_create_nonce( 'ensemble_download_export_' . $filename ),
            ),
            admin_url( 'admin-ajax.php' )
        );

        wp_send_json_success( array(
            'download_url' => $download_url,
            'filename' => $filename,
        ) );
    }

    /**
     * Get export arguments from request
     */
    private function get_export_args() {
        $args = array(
            'post_status' => 'publish',
            'posts_per_page' => -1,
        );

        // Date filter
        $date_filter = isset( $_POST['date_filter'] ) ? sanitize_text_field( $_POST['date_filter'] ) : 'all';
        
        if ( $date_filter === 'upcoming' ) {
            $args['date_from'] = date( 'Y-m-d' );
        } elseif ( $date_filter === 'custom' ) {
            if ( ! empty( $_POST['date_from'] ) ) {
                $args['date_from'] = sanitize_text_field( $_POST['date_from'] );
            }
            if ( ! empty( $_POST['date_to'] ) ) {
                $args['date_to'] = sanitize_text_field( $_POST['date_to'] );
            }
        }

        // Category filter
        if ( ! empty( $_POST['category_id'] ) ) {
            $args['category_id'] = intval( $_POST['category_id'] );
        }

        // Location filter
        if ( ! empty( $_POST['location_id'] ) ) {
            $args['location_id'] = intval( $_POST['location_id'] );
        }

        return $args;
    }

    /**
     * Get count of events
     */
    private function get_event_count( $args ) {
        $query_args = $this->build_query_args( $args );
        $query_args['fields'] = 'ids';
        
        $query = new WP_Query( $query_args );
        return $query->found_posts;
    }

    /**
     * Get events preview
     */
    private function get_events_preview( $args ) {
        $query_args = $this->build_query_args( $args );
        $query_args['posts_per_page'] = 50; // Limit preview
        
        $query = new WP_Query( $query_args );
        $events = array();

        foreach ( $query->posts as $post ) {
            $events[] = array(
                'title' => $post->post_title,
                'date' => get_post_meta( $post->ID, 'event_date', true ),
            );
        }

        return $events;
    }

    /**
     * Build WP_Query arguments
     */
    private function build_query_args( $args ) {
        $query_args = array(
            'post_type' => 'post',
            'post_status' => isset( $args['post_status'] ) ? $args['post_status'] : 'publish',
            'posts_per_page' => isset( $args['posts_per_page'] ) ? $args['posts_per_page'] : -1,
            'tax_query' => array(
                array(
                    'taxonomy' => 'ensemble_category',
                    'operator' => 'EXISTS',
                ),
            ),
        );

        // Date range
        if ( isset( $args['date_from'] ) || isset( $args['date_to'] ) ) {
            $query_args['meta_query'] = array( 'relation' => 'AND' );

            if ( isset( $args['date_from'] ) ) {
                $query_args['meta_query'][] = array(
                    'key' => 'event_date',
                    'value' => $args['date_from'],
                    'compare' => '>=',
                    'type' => 'DATE',
                );
            }

            if ( isset( $args['date_to'] ) ) {
                $query_args['meta_query'][] = array(
                    'key' => 'event_date',
                    'value' => $args['date_to'],
                    'compare' => '<=',
                    'type' => 'DATE',
                );
            }
        }

        // Category filter
        if ( isset( $args['category_id'] ) ) {
            $query_args['tax_query'][] = array(
                'taxonomy' => 'ensemble_category',
                'field' => 'term_id',
                'terms' => $args['category_id'],
            );
        }

        // Location filter
        if ( isset( $args['location_id'] ) ) {
            if ( ! isset( $query_args['meta_query'] ) ) {
                $query_args['meta_query'] = array( 'relation' => 'AND' );
            }
            
            $query_args['meta_query'][] = array(
                'key' => 'event_location',
                'value' => $args['location_id'],
                'compare' => '=',
            );
        }

        return $query_args;
    }
}

// Initialize
new Ensemble_Export_Ajax();

// Handle file download
add_action( 'wp_ajax_ensemble_download_export', function() {
    $filename = isset( $_GET['file'] ) ? sanitize_file_name( $_GET['file'] ) : '';
    $nonce = isset( $_GET['nonce'] ) ? sanitize_text_field( $_GET['nonce'] ) : '';

    if ( ! wp_verify_nonce( $nonce, 'ensemble_download_export_' . $filename ) ) {
        wp_die( 'Invalid security token' );
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Insufficient permissions' );
    }

    $upload_dir = wp_upload_dir();
    $filepath = $upload_dir['path'] . '/' . $filename;

    if ( ! file_exists( $filepath ) ) {
        wp_die( 'File not found' );
    }

    header( 'Content-Type: text/calendar; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
    header( 'Cache-Control: no-cache, must-revalidate' );
    header( 'Expires: Sat, 26 Jul 1997 05:00:00 GMT' );
    
    readfile( $filepath );
    
    // Delete temp file
    unlink( $filepath );
    
    exit;
} );
