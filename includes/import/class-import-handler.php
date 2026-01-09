<?php
/**
 * Import Handler
 * Orchestrates the complete iCal import process
 *
 * @package Ensemble
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Ensemble_Import_Handler {

    private $parser;
    private $transformer;
    private $location_matcher;
    
    /**
     * Check if import is available
     * 
     * @return bool
     */
    public static function is_available() {
        return function_exists('ensemble_is_pro') && ensemble_is_pro();
    }

    public function __construct() {
        require_once 'class-ical-parser.php';
        require_once 'class-event-transformer.php';
        require_once 'class-location-matcher.php';

        $this->parser = new Ensemble_iCal_Parser();
        $this->transformer = new Ensemble_Event_Transformer();
        $this->location_matcher = new Ensemble_Location_Matcher();
    }

    /**
     * Parse and preview events (without importing)
     *
     * @param string $source_type 'url' or 'file'
     * @param mixed $source URL string or file array
     * @return array|WP_Error Preview data or error
     */
    public function preview( $source_type, $source ) {
        // Pro check
        if (!self::is_available()) {
            return new WP_Error('pro_required', __('Import requires the Pro version.', 'ensemble'));
        }
        
        // Parse events
        if ( $source_type === 'url' ) {
            $events = $this->parser->parse_from_url( $source );
        } elseif ( $source_type === 'file' ) {
            $events = $this->parser->parse_from_file( $source );
        } else {
            return new WP_Error( 'invalid_source', 'Invalid source type' );
        }

        if ( is_wp_error( $events ) ) {
            return $events;
        }

        // Match locations
        $location_strings = array_map( function( $event ) {
            return isset( $event['location'] ) ? $event['location'] : '';
        }, $events );

        $location_matches = $this->location_matcher->batch_match( $location_strings );

        // Build preview data
        $preview = array();
        $existing_count = 0;

        foreach ( $events as $index => $event ) {
            $location_match = $location_matches[ $index ];
            
            // Check if event already exists
            $existing_event_id = null;
            $existing_status = 'new';
            
            if ( isset( $event['uid'] ) && ! empty( $event['uid'] ) ) {
                $existing_event_id = $this->find_existing_event( $event['uid'] );
                
                if ( $existing_event_id ) {
                    $existing_status = 'exists';
                    $existing_count++;
                }
            }

            $preview[] = array(
                'title' => isset( $event['summary'] ) ? $event['summary'] : 'Untitled',
                'date' => isset( $event['dtstart'] ) ? $event['dtstart'] : '',
                'end_date' => isset( $event['dtend'] ) ? $event['dtend'] : '',
                'location_raw' => isset( $event['location'] ) ? $event['location'] : '',
                'location_match' => $location_match,
                'is_recurring' => isset( $event['rrule'] ),
                'status' => isset( $event['status'] ) ? $event['status'] : 'confirmed',
                'description' => isset( $event['description'] ) ? wp_trim_words( $event['description'], 20 ) : '',
                'uid' => isset( $event['uid'] ) ? $event['uid'] : '',
                'existing_status' => $existing_status,
                'existing_id' => $existing_event_id,
            );
        }

        return array(
            'events' => $preview,
            'total' => count( $preview ),
            'existing' => $existing_count,
            'new' => count( $preview ) - $existing_count,
            'source_type' => $source_type,
        );
    }

    /**
     * Import events to WordPress
     *
     * @param string $source_type 'url' or 'file'
     * @param mixed $source URL string or file array
     * @param array $options Import options:
     *   - 'update_mode' => 'skip' | 'update' | 'duplicate' (default: 'skip')
     *   - 'selected_uids' => array() (optional: only import events with these UIDs)
     * @return array|WP_Error Import result or error
     */
    public function import( $source_type, $source, $options = array() ) {
        // Parse events
        if ( $source_type === 'url' ) {
            $events = $this->parser->parse_from_url( $source );
        } elseif ( $source_type === 'file' ) {
            $events = $this->parser->parse_from_file( $source );
        } else {
            return new WP_Error( 'invalid_source', 'Invalid source type' );
        }

        if ( is_wp_error( $events ) ) {
            return $events;
        }

        // Match locations
        $location_strings = array_map( function( $event ) {
            return isset( $event['location'] ) ? $event['location'] : '';
        }, $events );

        $location_matches = $this->location_matcher->batch_match( $location_strings );

        // Build location ID map
        $location_id_map = array();
        foreach ( $location_matches as $index => $match ) {
            if ( $match['status'] === 'matched' && $match['location_id'] ) {
                $location_id_map[ $index ] = $match['location_id'];
            }
        }

        // Transform events
        $transformed_events = $this->transformer->batch_transform( $events, $location_id_map );

        // Default options
        $update_mode = isset( $options['update_mode'] ) ? $options['update_mode'] : 'skip';
        $selected_uids = isset( $options['selected_uids'] ) ? $options['selected_uids'] : array();

        // Import to WordPress
        $results = array(
            'success' => 0,
            'updated' => 0,
            'created' => 0,
            'skipped' => 0,
            'failed' => 0,
            'errors' => array(),
            'details' => array(),
        );

        foreach ( $transformed_events as $index => $event_data ) {
            $event_uid = isset( $event_data['meta']['_ensemble_ical_uid'] ) ? $event_data['meta']['_ensemble_ical_uid'] : null;
            $event_title = isset( $events[ $index ]['summary'] ) ? $events[ $index ]['summary'] : 'Unknown';
            
            // Check if we should import this event (if selected_uids is set)
            if ( ! empty( $selected_uids ) && $event_uid && ! in_array( $event_uid, $selected_uids ) ) {
                $results['skipped']++;
                $results['details'][] = array(
                    'event' => $event_title,
                    'action' => 'skipped',
                    'reason' => 'Not selected',
                );
                continue;
            }
            
            // Check if event already exists (by iCal UID)
            $existing_id = null;
            
            if ( $event_uid ) {
                $existing_id = $this->find_existing_event( $event_uid );
            }
            
            // Handle based on update_mode
            if ( $existing_id ) {
                if ( $update_mode === 'skip' ) {
                    // Skip existing events
                    $results['skipped']++;
                    $results['details'][] = array(
                        'event' => $event_title,
                        'action' => 'skipped',
                        'reason' => 'Already exists',
                        'post_id' => $existing_id,
                    );
                    continue;
                    
                } elseif ( $update_mode === 'update' ) {
                    // Update existing event
                    $event_id = $this->update_event( $existing_id, $event_data );
                    
                    if ( is_wp_error( $event_id ) ) {
                        $results['failed']++;
                        $results['errors'][] = array(
                            'event' => $event_title,
                            'error' => $event_id->get_error_message(),
                        );
                    } else {
                        $results['updated']++;
                        $results['success']++;
                        $results['details'][] = array(
                            'event' => $event_title,
                            'action' => 'updated',
                            'post_id' => $event_id,
                        );
                    }
                    
                } elseif ( $update_mode === 'duplicate' ) {
                    // Create duplicate (ignore UID for this one)
                    unset( $event_data['meta']['_ensemble_ical_uid'] );
                    $event_id = $this->create_event( $event_data );
                    
                    if ( is_wp_error( $event_id ) ) {
                        $results['failed']++;
                        $results['errors'][] = array(
                            'event' => $event_title,
                            'error' => $event_id->get_error_message(),
                        );
                    } else {
                        $results['created']++;
                        $results['success']++;
                        $results['details'][] = array(
                            'event' => $event_title,
                            'action' => 'created_duplicate',
                            'post_id' => $event_id,
                        );
                    }
                }
                
            } else {
                // Create new event
                $event_id = $this->create_event( $event_data );
                
                if ( is_wp_error( $event_id ) ) {
                    $results['failed']++;
                    $results['errors'][] = array(
                        'event' => $event_title,
                        'error' => $event_id->get_error_message(),
                    );
                } else {
                    $results['created']++;
                    $results['success']++;
                    $results['details'][] = array(
                        'event' => $event_title,
                        'action' => 'created',
                        'post_id' => $event_id,
                    );
                }
            }
        }

        return $results;
    }

    /**
     * Create new event post
     *
     * @param array $event_data Event data
     * @return int|WP_Error Post ID or error
     */
    private function create_event( $event_data ) {
        $meta = isset( $event_data['meta'] ) ? $event_data['meta'] : array();
        unset( $event_data['meta'] );

        $post_id = wp_insert_post( $event_data, true );

        if ( is_wp_error( $post_id ) ) {
            return $post_id;
        }

        // Add meta data
        foreach ( $meta as $key => $value ) {
            update_post_meta( $post_id, $key, $value );
        }
        
        // IMPORTANT: Add ensemble_category taxonomy so event appears in Wizard/Calendar
        // Check if a default category exists, if not create "Imported Events"
        $imported_cat = get_term_by( 'slug', 'imported-events', 'ensemble_category' );
        if ( ! $imported_cat ) {
            $imported_cat = wp_insert_term( 'Imported Events', 'ensemble_category', array(
                'slug' => 'imported-events',
            ) );
            if ( ! is_wp_error( $imported_cat ) ) {
                $cat_id = $imported_cat['term_id'];
            } else {
                $cat_id = null;
            }
        } else {
            $cat_id = $imported_cat->term_id;
        }
        
        if ( $cat_id ) {
            wp_set_post_terms( $post_id, array( $cat_id ), 'ensemble_category' );
        }

        return $post_id;
    }

    /**
     * Update existing event post
     *
     * @param int $post_id Existing post ID
     * @param array $event_data New event data
     * @return int|WP_Error Post ID or error
     */
    private function update_event( $post_id, $event_data ) {
        $event_data['ID'] = $post_id;
        
        $meta = isset( $event_data['meta'] ) ? $event_data['meta'] : array();
        unset( $event_data['meta'] );

        $result = wp_update_post( $event_data, true );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        // Update meta data
        foreach ( $meta as $key => $value ) {
            update_post_meta( $post_id, $key, $value );
        }

        return $post_id;
    }

    /**
     * Find existing event by iCal UID
     *
     * @param string $uid iCal UID
     * @return int|null Post ID or null if not found
     */
    private function find_existing_event( $uid ) {
        $query = new WP_Query( array(
            'post_type' => 'post',  // Events are stored as regular posts
            'posts_per_page' => 1,
            'post_status' => 'any',
            'meta_query' => array(
                array(
                    'key' => '_ensemble_ical_uid',
                    'value' => $uid,
                    'compare' => '=',
                ),
            ),
            'tax_query' => array(
                array(
                    'taxonomy' => 'ensemble_category',
                    'operator' => 'EXISTS',  // Must have at least one ensemble_category
                ),
            ),
            'fields' => 'ids',
        ) );

        return $query->have_posts() ? $query->posts[0] : null;
    }

    /**
     * Get parser instance
     */
    public function get_parser() {
        return $this->parser;
    }

    /**
     * Get transformer instance
     */
    public function get_transformer() {
        return $this->transformer;
    }

    /**
     * Get location matcher instance
     */
    public function get_location_matcher() {
        return $this->location_matcher;
    }
}