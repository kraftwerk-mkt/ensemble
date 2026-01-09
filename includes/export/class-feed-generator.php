<?php
/**
 * Feed Generator
 * Generates public iCal feed URL for events
 *
 * @package Ensemble
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Ensemble_Feed_Generator {

    private $export_handler;

    public function __construct() {
        require_once dirname(dirname(__FILE__)) . '/class-export-handler.php';
        $this->export_handler = new Ensemble_Export_Handler();

        // Register feed endpoint
        add_action( 'init', array( $this, 'register_feed' ) );
        add_action( 'template_redirect', array( $this, 'handle_feed_request' ) );
    }

    /**
     * Register feed endpoint
     */
    public function register_feed() {
        // Add rewrite rule for feed
        add_rewrite_rule(
            '^ensemble/feed\.ics$',
            'index.php?ensemble_feed=1',
            'top'
        );

        // Add query var
        add_filter( 'query_vars', function( $vars ) {
            $vars[] = 'ensemble_feed';
            return $vars;
        } );

        // Flush rewrite rules if needed (only on activation)
        if ( get_option( 'ensemble_feed_flush_needed' ) ) {
            flush_rewrite_rules();
            delete_option( 'ensemble_feed_flush_needed' );
        }
    }

    /**
     * Handle feed request
     */
    public function handle_feed_request() {
        if ( ! get_query_var( 'ensemble_feed' ) ) {
            return;
        }

        // Check if feed is enabled
        $feed_enabled = get_option( 'ensemble_feed_enabled', false );
        if ( ! $feed_enabled ) {
            status_header( 404 );
            wp_die( 'Feed not available' );
        }

        // Get feed parameters from URL
        $args = array(
            'post_status' => 'publish', // Only public events
        );

        // Optional: Date range from URL params
        if ( isset( $_GET['from'] ) ) {
            $args['date_from'] = sanitize_text_field( $_GET['from'] );
        }

        if ( isset( $_GET['to'] ) ) {
            $args['date_to'] = sanitize_text_field( $_GET['to'] );
        }

        // Optional: Category filter
        if ( isset( $_GET['category'] ) ) {
            $args['category_id'] = intval( $_GET['category'] );
        }

        // Optional: Limit number of events
        if ( isset( $_GET['limit'] ) ) {
            $args['posts_per_page'] = intval( $_GET['limit'] );
        } else {
            // Default: Events for next 12 months
            if ( ! isset( $args['date_from'] ) ) {
                $args['date_from'] = current_time( 'Y-m-d' );
            }
            if ( ! isset( $args['date_to'] ) ) {
                $datetime = new DateTime( 'now', wp_timezone() );
                $datetime->modify( '+12 months' );
                $args['date_to'] = $datetime->format( 'Y-m-d' );
            }
        }

        // Generate iCal
        $ical = $this->export_handler->export_to_ical( $args );

        if ( is_wp_error( $ical ) ) {
            status_header( 500 );
            wp_die( 'Error generating feed' );
        }

        // Output iCal with correct headers
        header( 'Content-Type: text/calendar; charset=utf-8' );
        header( 'Content-Disposition: inline; filename="ensemble-feed.ics"' );
        header( 'Cache-Control: max-age=3600, must-revalidate' ); // Cache for 1 hour
        header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + 3600 ) . ' GMT' );

        echo $ical;
        exit;
    }

    /**
     * Get feed URL
     *
     * @return string Feed URL
     */
    public function get_feed_url() {
        return home_url( '/ensemble/feed.ics' );
    }

    /**
     * Enable feed
     */
    public function enable_feed() {
        update_option( 'ensemble_feed_enabled', true );
        update_option( 'ensemble_feed_flush_needed', true );
    }

    /**
     * Disable feed
     */
    public function disable_feed() {
        update_option( 'ensemble_feed_enabled', false );
    }

    /**
     * Check if feed is enabled
     *
     * @return bool
     */
    public function is_feed_enabled() {
        return (bool) get_option( 'ensemble_feed_enabled', false );
    }

    /**
     * Get feed settings
     *
     * @return array Feed settings
     */
    public function get_feed_settings() {
        return array(
            'enabled' => $this->is_feed_enabled(),
            'url' => $this->get_feed_url(),
            'instructions' => array(
                'google' => 'Google Calendar → Other calendars → + → From URL → Paste URL',
                'outlook' => 'Outlook → Calendar → Add calendar → Subscribe from web → Paste URL',
                'apple' => 'Calendar → File → New Calendar Subscription → Paste URL',
            ),
        );
    }
}
