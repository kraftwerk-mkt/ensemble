<?php
/**
 * iCal Parser
 * Parses .ics files and iCal URLs
 *
 * @package Ensemble
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Ensemble_iCal_Parser {

    /**
     * Parse iCal from URL
     *
     * @param string $url iCal URL
     * @return array|WP_Error Parsed events or error
     */
    public function parse_from_url( $url ) {
        $response = wp_remote_get( $url, array(
            'timeout' => 30,
            'sslverify' => true,
        ) );

        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'fetch_failed', 'Failed to fetch iCal URL: ' . $response->get_error_message() );
        }

        $body = wp_remote_retrieve_body( $response );
        
        if ( empty( $body ) ) {
            return new WP_Error( 'empty_response', 'Empty response from iCal URL' );
        }

        return $this->parse_ical_string( $body );
    }

    /**
     * Parse iCal from uploaded file
     *
     * @param array $file Uploaded file array from $_FILES
     * @return array|WP_Error Parsed events or error
     */
    public function parse_from_file( $file ) {
        if ( ! isset( $file['tmp_name'] ) || ! file_exists( $file['tmp_name'] ) ) {
            return new WP_Error( 'invalid_file', 'Invalid file upload' );
        }

        // Check file extension
        $filename = isset( $file['name'] ) ? $file['name'] : '';
        if ( ! preg_match( '/\.ics$/i', $filename ) ) {
            return new WP_Error( 'invalid_format', 'File must be .ics format' );
        }

        $content = file_get_contents( $file['tmp_name'] );
        
        if ( false === $content ) {
            return new WP_Error( 'read_failed', 'Failed to read file content' );
        }

        return $this->parse_ical_string( $content );
    }

    /**
     * Parse iCal string content
     *
     * @param string $ical_string iCal content
     * @return array|WP_Error Array of parsed events or error
     */
    private function parse_ical_string( $ical_string ) {
        // Normalize line breaks
        $ical_string = str_replace( array( "\r\n", "\r" ), "\n", $ical_string );
        
        // Unfold lines (iCal spec: lines starting with space/tab are continuations)
        $ical_string = preg_replace( "/\n[ \t]/", "", $ical_string );

        $lines = explode( "\n", $ical_string );
        $events = array();
        $current_event = null;
        $in_event = false;

        foreach ( $lines as $line ) {
            $line = trim( $line );

            if ( empty( $line ) ) {
                continue;
            }

            // Start of event
            if ( $line === 'BEGIN:VEVENT' ) {
                $in_event = true;
                $current_event = array();
                continue;
            }

            // End of event
            if ( $line === 'END:VEVENT' ) {
                if ( $current_event ) {
                    $events[] = $current_event;
                }
                $current_event = null;
                $in_event = false;
                continue;
            }

            // Parse event properties
            if ( $in_event && $current_event !== null ) {
                $this->parse_line_into_event( $line, $current_event );
            }
        }

        if ( empty( $events ) ) {
            return new WP_Error( 'no_events', 'No events found in iCal data' );
        }

        return $events;
    }

    /**
     * Parse a single iCal line into event array
     *
     * @param string $line iCal line
     * @param array &$event Event array (passed by reference)
     */
    private function parse_line_into_event( $line, &$event ) {
        // Split on first colon (property:value)
        $parts = explode( ':', $line, 2 );
        
        if ( count( $parts ) !== 2 ) {
            return;
        }

        list( $property, $value ) = $parts;

        // Handle parameters (e.g., DTSTART;TZID=America/New_York:20240101T120000)
        $prop_parts = explode( ';', $property, 2 );
        $prop_name = $prop_parts[0];
        $prop_params = isset( $prop_parts[1] ) ? $prop_parts[1] : '';

        // Unescape special characters
        $value = $this->unescape_ical_value( $value );

        switch ( $prop_name ) {
            case 'UID':
                $event['uid'] = $value;
                break;

            case 'SUMMARY':
                $event['summary'] = $value;
                break;

            case 'DESCRIPTION':
                $event['description'] = $value;
                break;

            case 'LOCATION':
                $event['location'] = $value;
                break;

            case 'DTSTART':
                $event['dtstart'] = $this->parse_datetime( $value, $prop_params );
                break;

            case 'DTEND':
                $event['dtend'] = $this->parse_datetime( $value, $prop_params );
                break;

            case 'DURATION':
                $event['duration'] = $value;
                break;

            case 'RRULE':
                $event['rrule'] = $this->parse_rrule( $value );
                break;

            case 'EXDATE':
                if ( ! isset( $event['exdate'] ) ) {
                    $event['exdate'] = array();
                }
                $event['exdate'][] = $this->parse_datetime( $value, $prop_params );
                break;

            case 'STATUS':
                $event['status'] = strtolower( $value );
                break;

            case 'URL':
                $event['url'] = $value;
                break;
        }
    }

    /**
     * Parse iCal datetime value
     *
     * @param string $value DateTime value
     * @param string $params Property parameters
     * @return string MySQL datetime format
     */
    private function parse_datetime( $value, $params = '' ) {
        // Check if it's a date-only value (no time)
        if ( strlen( $value ) === 8 ) {
            // YYYYMMDD format
            $year = substr( $value, 0, 4 );
            $month = substr( $value, 4, 2 );
            $day = substr( $value, 6, 2 );
            return "$year-$month-$day 00:00:00";
        }

        // Remove 'Z' (UTC indicator) if present
        $value = rtrim( $value, 'Z' );

        // Parse YYYYMMDDTHHMMSS format
        if ( preg_match( '/^(\d{4})(\d{2})(\d{2})T(\d{2})(\d{2})(\d{2})$/', $value, $matches ) ) {
            return sprintf(
                '%s-%s-%s %s:%s:%s',
                $matches[1], // year
                $matches[2], // month
                $matches[3], // day
                $matches[4], // hour
                $matches[5], // minute
                $matches[6]  // second
            );
        }

        // Fallback: return as-is
        return $value;
    }

    /**
     * Parse RRULE (recurrence rule)
     *
     * @param string $rrule RRULE value
     * @return array Parsed recurrence data
     */
    private function parse_rrule( $rrule ) {
        $parts = explode( ';', $rrule );
        $rule = array();

        foreach ( $parts as $part ) {
            $kv = explode( '=', $part, 2 );
            if ( count( $kv ) === 2 ) {
                $rule[ strtolower( $kv[0] ) ] = $kv[1];
            }
        }

        return $rule;
    }

    /**
     * Unescape iCal special characters
     *
     * @param string $value iCal value
     * @return string Unescaped value
     */
    private function unescape_ical_value( $value ) {
        $replacements = array(
            '\\n' => "\n",
            '\\N' => "\n",
            '\\,' => ',',
            '\\;' => ';',
            '\\\\' => '\\',
        );

        return str_replace( array_keys( $replacements ), array_values( $replacements ), $value );
    }
}
