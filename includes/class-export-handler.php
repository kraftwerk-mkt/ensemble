<?php
/**
 * Export Handler
 * Exports Ensemble events to iCal format
 *
 * @package Ensemble
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Ensemble_Export_Handler {

    /**
     * Export events to iCal format
     *
     * @param array $args Export arguments
     * @return string iCal formatted string
     */
    public function export_to_ical( $args = array() ) {
        // Default arguments
        $defaults = array(
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'date_from' => null,
            'date_to' => null,
            'location_id' => null,
            'category_id' => null,
            'event_ids' => array(), // Specific events to export
        );

        $args = wp_parse_args( $args, $defaults );

        // Get events
        $events = $this->get_events_for_export( $args );

        if ( empty( $events ) ) {
            return new WP_Error( 'no_events', 'No events found for export' );
        }

        // Generate iCal string
        $ical = $this->generate_ical( $events );

        return $ical;
    }

    /**
     * Get events for export
     *
     * @param array $args Query arguments
     * @return array Events
     */
    private function get_events_for_export( $args ) {
        $query_args = array(
            'post_type' => ensemble_get_post_type(),
            'post_status' => $args['post_status'],
            'posts_per_page' => $args['posts_per_page'],
            'tax_query' => array(
                array(
                    'taxonomy' => 'ensemble_category',
                    'operator' => 'EXISTS',
                ),
            ),
        );

        // Specific event IDs
        if ( ! empty( $args['event_ids'] ) ) {
            $query_args['post__in'] = $args['event_ids'];
        }

        // Date range filter
        if ( $args['date_from'] || $args['date_to'] ) {
            $query_args['meta_query'] = array(
                'relation' => 'AND',
            );

            if ( $args['date_from'] ) {
                $query_args['meta_query'][] = array(
                    'key' => 'event_date',
                    'value' => $args['date_from'],
                    'compare' => '>=',
                    'type' => 'DATE',
                );
            }

            if ( $args['date_to'] ) {
                $query_args['meta_query'][] = array(
                    'key' => 'event_date',
                    'value' => $args['date_to'],
                    'compare' => '<=',
                    'type' => 'DATE',
                );
            }
        }

        // Category filter
        if ( $args['category_id'] ) {
            $query_args['tax_query'][] = array(
                'taxonomy' => 'ensemble_category',
                'field' => 'term_id',
                'terms' => $args['category_id'],
            );
        }

        $query = new WP_Query( $query_args );

        return $query->posts;
    }

    /**
     * Generate iCal format from events
     *
     * @param array $events WP_Post objects
     * @return string iCal formatted string
     */
    private function generate_ical( $events ) {
        $ical = "BEGIN:VCALENDAR\r\n";
        $ical .= "VERSION:2.0\r\n";
        $ical .= "PRODID:-//Ensemble Events//NONSGML v1.0//EN\r\n";
        $ical .= "CALSCALE:GREGORIAN\r\n";
        $ical .= "METHOD:PUBLISH\r\n";
        $ical .= "X-WR-CALNAME:" . $this->escape_ical_value( get_bloginfo( 'name' ) . ' Events' ) . "\r\n";
        $ical .= "X-WR-TIMEZONE:" . wp_timezone_string() . "\r\n";

        foreach ( $events as $event ) {
            $ical .= $this->generate_vevent( $event );
        }

        $ical .= "END:VCALENDAR\r\n";

        return $ical;
    }

    /**
     * Generate VEVENT for a single event
     *
     * @param WP_Post $event Event post
     * @return string VEVENT string
     */
    private function generate_vevent( $event ) {
        $vevent = "BEGIN:VEVENT\r\n";

        // UID - Use original iCal UID if available, otherwise generate
        $uid = get_post_meta( $event->ID, '_ensemble_ical_uid', true );
        if ( ! $uid ) {
            $uid = $event->ID . '@' . parse_url( home_url(), PHP_URL_HOST );
        }
        $vevent .= "UID:" . $this->escape_ical_value( $uid ) . "\r\n";

        // DTSTAMP - Creation/modification timestamp
        $dtstamp = get_post_modified_time( 'Ymd\THis\Z', true, $event );
        $vevent .= "DTSTAMP:" . $dtstamp . "\r\n";

        // DTSTART - Event start date/time
        $event_date = get_post_meta( $event->ID, 'event_date', true );
        $event_time = get_post_meta( $event->ID, 'event_time', true );
        
        if ( $event_date ) {
            if ( $event_time ) {
                // Date with time
                $dtstart = $this->format_datetime( $event_date, $event_time );
                $vevent .= "DTSTART:" . $dtstart . "\r\n";
            } else {
                // All-day event
                $dtstart = $this->format_date( $event_date );
                $vevent .= "DTSTART;VALUE=DATE:" . $dtstart . "\r\n";
            }

            // DTEND - Event end date/time
            $event_time_end = get_post_meta( $event->ID, 'event_time_end', true );
            $duration = get_post_meta( $event->ID, 'event_duration', true );
            
            if ( $event_time && $event_time_end ) {
                // Explicit end time
                $dtend = $this->format_datetime( $event_date, $event_time_end );
                $vevent .= "DTEND:" . $dtend . "\r\n";
            } elseif ( $event_time && $duration ) {
                // Calculate end time from duration
                $datetime = new DateTime( $event_date . ' ' . $event_time, wp_timezone() );
                $datetime->modify( '+' . intval( $duration ) . ' minutes' );
                $vevent .= "DTEND:" . $datetime->format( 'Ymd\THis\Z' ) . "\r\n";
            } elseif ( ! $event_time ) {
                // All-day event - end is next day
                $datetime = new DateTime( $event_date );
                $datetime->modify( '+1 day' );
                $vevent .= "DTEND;VALUE=DATE:" . $datetime->format( 'Ymd' ) . "\r\n";
            }
        }

        // SUMMARY - Event title
        $vevent .= "SUMMARY:" . $this->escape_ical_value( $event->post_title ) . "\r\n";

        // DESCRIPTION - Event description
        if ( ! empty( $event->post_content ) ) {
            $description = wp_strip_all_tags( $event->post_content );
            $vevent .= "DESCRIPTION:" . $this->escape_ical_value( $description ) . "\r\n";
        }

        // LOCATION - Event location
        $location_id = get_post_meta( $event->ID, 'event_location', true );
        if ( $location_id ) {
            $location = get_post( $location_id );
            if ( $location ) {
                $vevent .= "LOCATION:" . $this->escape_ical_value( $location->post_title ) . "\r\n";
            }
        }

        // URL - Link to event
        $vevent .= "URL:" . get_permalink( $event->ID ) . "\r\n";

        // STATUS - Event status (default: CONFIRMED)
        $vevent .= "STATUS:CONFIRMED\r\n";

        // CATEGORIES - Event categories
        $categories = wp_get_post_terms( $event->ID, 'ensemble_category', array( 'fields' => 'names' ) );
        if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
            $vevent .= "CATEGORIES:" . $this->escape_ical_value( implode( ',', $categories ) ) . "\r\n";
        }

        // Recurring events
        if ( get_post_meta( $event->ID, '_ensemble_is_recurring', true ) ) {
            $pattern = get_post_meta( $event->ID, '_ensemble_recurrence_pattern', true );
            if ( $pattern && is_array( $pattern ) ) {
                $rrule = $this->generate_rrule( $pattern );
                if ( $rrule ) {
                    $vevent .= "RRULE:" . $rrule . "\r\n";
                }

                // Exception dates
                $exdates = get_post_meta( $event->ID, '_ensemble_exception_dates', true );
                if ( ! empty( $exdates ) && is_array( $exdates ) ) {
                    foreach ( $exdates as $exdate ) {
                        $vevent .= "EXDATE:" . $this->format_datetime( $exdate, $event_time ) . "\r\n";
                    }
                }
            }
        }

        $vevent .= "END:VEVENT\r\n";

        return $vevent;
    }

    /**
     * Generate RRULE from Ensemble recurrence pattern
     *
     * @param array $pattern Recurrence pattern
     * @return string RRULE string
     */
    private function generate_rrule( $pattern ) {
        if ( empty( $pattern['frequency'] ) ) {
            return '';
        }

        $rrule = 'FREQ=' . strtoupper( $pattern['frequency'] );

        // Interval
        if ( isset( $pattern['interval'] ) && $pattern['interval'] > 1 ) {
            $rrule .= ';INTERVAL=' . $pattern['interval'];
        }

        // Count
        if ( isset( $pattern['count'] ) && $pattern['count'] > 0 ) {
            $rrule .= ';COUNT=' . $pattern['count'];
        }

        // Until
        if ( isset( $pattern['until'] ) && ! empty( $pattern['until'] ) ) {
            $until = $this->format_date( $pattern['until'] );
            $rrule .= ';UNTIL=' . $until;
        }

        // By day (for weekly/monthly)
        if ( isset( $pattern['by_day'] ) && ! empty( $pattern['by_day'] ) ) {
            $days = array( 1 => 'MO', 2 => 'TU', 3 => 'WE', 4 => 'TH', 5 => 'FR', 6 => 'SA', 7 => 'SU' );
            $byday = array();
            foreach ( $pattern['by_day'] as $day ) {
                if ( isset( $days[ $day ] ) ) {
                    $byday[] = $days[ $day ];
                }
            }
            if ( ! empty( $byday ) ) {
                $rrule .= ';BYDAY=' . implode( ',', $byday );
            }
        }

        // By month day
        if ( isset( $pattern['by_month_day'] ) && ! empty( $pattern['by_month_day'] ) ) {
            $rrule .= ';BYMONTHDAY=' . implode( ',', $pattern['by_month_day'] );
        }

        return $rrule;
    }

    /**
     * Format date for iCal (YYYYMMDD)
     *
     * @param string $date Date string
     * @return string Formatted date
     */
    private function format_date( $date ) {
        $datetime = new DateTime( $date );
        return $datetime->format( 'Ymd' );
    }

    /**
     * Format datetime for iCal (YYYYMMDDTHHMMSSZ)
     *
     * @param string $date Date string
     * @param string $time Time string
     * @return string Formatted datetime
     */
    private function format_datetime( $date, $time ) {
        $datetime = new DateTime( $date . ' ' . $time, wp_timezone() );
        return $datetime->format( 'Ymd\THis\Z' );
    }

    /**
     * Escape special characters for iCal values
     *
     * @param string $value Value to escape
     * @return string Escaped value
     */
    private function escape_ical_value( $value ) {
        $value = str_replace( '\\', '\\\\', $value );
        $value = str_replace( ',', '\\,', $value );
        $value = str_replace( ';', '\\;', $value );
        $value = str_replace( "\n", '\\n', $value );
        $value = str_replace( "\r", '', $value );
        
        return $value;
    }

    /**
     * Download iCal file
     *
     * @param string $ical iCal content
     * @param string $filename Filename for download
     */
    public function download_ical( $ical, $filename = 'ensemble-events.ics' ) {
        header( 'Content-Type: text/calendar; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Cache-Control: no-cache, must-revalidate' );
        header( 'Expires: Sat, 26 Jul 1997 05:00:00 GMT' );
        
        echo $ical;
        exit;
    }
}
