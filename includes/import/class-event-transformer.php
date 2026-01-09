<?php
/**
 * Event Transformer
 * Transforms iCal events to Ensemble event structure
 *
 * @package Ensemble
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Ensemble_Event_Transformer {

    /**
     * Transform iCal event to Ensemble format
     *
     * @param array $ical_event Parsed iCal event
     * @param int|null $matched_location_id Matched location post ID (or null)
     * @return array Ensemble event data
     */
    public function transform( $ical_event, $matched_location_id = null ) {
        $event_data = array(
            'post_type' => 'post',  // Use normal posts like the Wizard does
            'post_status' => 'publish',
            'post_title' => isset( $ical_event['summary'] ) ? $ical_event['summary'] : 'Untitled Event',
            'post_content' => isset( $ical_event['description'] ) ? $ical_event['description'] : '',
        );

        // Build meta data
        $meta = array();

        // Date/Time - Use ACF field names that Wizard expects
        if ( isset( $ical_event['dtstart'] ) ) {
            // Extract date (YYYY-MM-DD format for ACF date field)
            $date_obj = new DateTime( $ical_event['dtstart'] );
            $meta['event_date'] = $date_obj->format( 'Y-m-d' );  // ACF date field
            
            // Extract time (HH:MM:SS format for ACF time field)
            $meta['event_time'] = $date_obj->format( 'H:i:s' );  // ACF time field
        }

        if ( isset( $ical_event['dtend'] ) ) {
            $end_obj = new DateTime( $ical_event['dtend'] );
            $meta['event_time_end'] = $end_obj->format( 'H:i:s' );  // ACF time field
        }

        // Calculate duration if DTEND exists
        if ( isset( $ical_event['dtstart'] ) && isset( $ical_event['dtend'] ) ) {
            $start = strtotime( $ical_event['dtstart'] );
            $end = strtotime( $ical_event['dtend'] );
            $duration_minutes = ( $end - $start ) / 60;
            $meta['event_duration'] = max( 0, $duration_minutes );  // ACF field
        }

        // Location - Use ACF field name
        if ( $matched_location_id ) {
            $meta['event_location'] = $matched_location_id;  // ACF relationship field
        } elseif ( isset( $ical_event['location'] ) ) {
            // Store raw location string if no match found
            $meta['_ensemble_location_raw'] = $ical_event['location'];
        }

        // Price field (empty for imported events)
        $meta['event_price'] = '';

        // Description - already in post_content, but also store in ACF field
        if ( isset( $ical_event['description'] ) ) {
            $meta['event_description'] = $ical_event['description'];
        }

        // Store original iCal UID for reference (for duplicate detection)
        if ( isset( $ical_event['uid'] ) ) {
            $meta['_ensemble_ical_uid'] = $ical_event['uid'];
        }

        // Recurring event data
        if ( isset( $ical_event['rrule'] ) ) {
            $meta['_ensemble_is_recurring'] = '1';
            $meta['_ensemble_recurrence_pattern'] = $this->transform_rrule( $ical_event['rrule'] );
            
            // Exception dates (EXDATE)
            if ( isset( $ical_event['exdate'] ) && is_array( $ical_event['exdate'] ) ) {
                $meta['_ensemble_exception_dates'] = $ical_event['exdate'];
            }
        }

        $event_data['meta'] = $meta;

        return $event_data;
    }

    /**
     * Transform iCal RRULE to Ensemble recurrence pattern
     *
     * @param array $rrule Parsed RRULE
     * @return array Ensemble recurrence pattern
     */
    private function transform_rrule( $rrule ) {
        $pattern = array(
            'enabled' => true,
        );

        // Frequency mapping
        $freq_map = array(
            'DAILY' => 'daily',
            'WEEKLY' => 'weekly',
            'MONTHLY' => 'monthly',
            'YEARLY' => 'yearly',
        );

        if ( isset( $rrule['freq'] ) && isset( $freq_map[ $rrule['freq'] ] ) ) {
            $pattern['frequency'] = $freq_map[ $rrule['freq'] ];
        }

        // Interval (every X days/weeks/months)
        if ( isset( $rrule['interval'] ) ) {
            $pattern['interval'] = intval( $rrule['interval'] );
        } else {
            $pattern['interval'] = 1;
        }

        // COUNT (number of occurrences)
        if ( isset( $rrule['count'] ) ) {
            $pattern['count'] = intval( $rrule['count'] );
        }

        // UNTIL (end date)
        if ( isset( $rrule['until'] ) ) {
            $pattern['until'] = $this->parse_until_date( $rrule['until'] );
        }

        // BYDAY (for weekly/monthly - e.g., MO,WE,FR)
        if ( isset( $rrule['byday'] ) ) {
            $pattern['by_day'] = $this->parse_by_day( $rrule['byday'] );
        }

        // BYMONTHDAY (for monthly - e.g., 1,15)
        if ( isset( $rrule['bymonthday'] ) ) {
            $pattern['by_month_day'] = explode( ',', $rrule['bymonthday'] );
        }

        return $pattern;
    }

    /**
     * Parse UNTIL date from RRULE
     *
     * @param string $until UNTIL value
     * @return string MySQL date format
     */
    private function parse_until_date( $until ) {
        // Remove 'Z' if present
        $until = rtrim( $until, 'Z' );

        // Parse YYYYMMDD or YYYYMMDDTHHMMSS format
        if ( preg_match( '/^(\d{4})(\d{2})(\d{2})/', $until, $matches ) ) {
            return sprintf( '%s-%s-%s', $matches[1], $matches[2], $matches[3] );
        }

        return $until;
    }

    /**
     * Parse BYDAY value (e.g., MO,WE,FR)
     *
     * @param string $byday BYDAY value
     * @return array Array of day numbers (1=Monday, 7=Sunday)
     */
    private function parse_by_day( $byday ) {
        $day_map = array(
            'MO' => 1,
            'TU' => 2,
            'WE' => 3,
            'TH' => 4,
            'FR' => 5,
            'SA' => 6,
            'SU' => 7,
        );

        $days = explode( ',', strtoupper( $byday ) );
        $parsed_days = array();

        foreach ( $days as $day ) {
            // Remove any numeric prefix (e.g., -1MO, 2TU)
            $day = preg_replace( '/^[+-]?\d+/', '', $day );
            
            if ( isset( $day_map[ $day ] ) ) {
                $parsed_days[] = $day_map[ $day ];
            }
        }

        return $parsed_days;
    }

    /**
     * Batch transform multiple events
     *
     * @param array $ical_events Array of iCal events
     * @param array $location_matches Array of location matches (indexed by event index)
     * @return array Array of transformed events
     */
    public function batch_transform( $ical_events, $location_matches = array() ) {
        $transformed = array();

        foreach ( $ical_events as $index => $event ) {
            $location_id = isset( $location_matches[ $index ] ) ? $location_matches[ $index ] : null;
            $transformed[] = $this->transform( $event, $location_id );
        }

        return $transformed;
    }
}