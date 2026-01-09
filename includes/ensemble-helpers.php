<?php
/**
 * Ensemble Helper Functions
 * VERSION: 1.6.0 with Smart-Sync for Location & Artist
 * 
 * Global helper functions for the plugin
 *
 * @package Ensemble
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get custom event fields (ACF fields)
 * 
 * @param int $event_id Event post ID
 * @return array Custom field data
 */
function ensemble_get_custom_event_fields($event_id) {
    // Get all post meta
    $all_meta = get_post_meta($event_id);
    
    // Define Ensemble core fields to exclude
    $exclude_fields = array(
        'ensemble_event_start',
        'ensemble_event_end',
        'ensemble_event_location',
        'ensemble_event_artists',
        'ensemble_event_organizer',
        'ensemble_event_price',
        'ensemble_event_registration',
        'ensemble_event_external_link',
        'ensemble_event_status',
        'ensemble_parent_event_id',
        'ensemble_recurrence_pattern',
        '_edit_last',
        '_edit_lock',
        '_thumbnail_id',
        '_wp_page_template'
    );
    
    $custom_fields = array();
    
    foreach ($all_meta as $key => $value) {
        // Skip WordPress internal fields
        if (substr($key, 0, 1) === '_') {
            continue;
        }
        
        // Skip Ensemble core fields
        if (in_array($key, $exclude_fields)) {
            continue;
        }
        
        // Get the first value (meta can have multiple values)
        $field_value = is_array($value) ? $value[0] : $value;
        
        // Skip empty values
        if (empty($field_value)) {
            continue;
        }
        
        // Try to get ACF field object for better label
        if (function_exists('get_field_object')) {
            $field_object = get_field_object($key, $event_id);
            if ($field_object) {
                $custom_fields[$field_object['label']] = $field_object['value'];
                continue;
            }
        }
        
        // Fallback: use key as label
        $custom_fields[$key] = $field_value;
    }
    
    return $custom_fields;
}

/**
 * Get the configured event post type
 * 
 * @return string Post type name
 */
function ensemble_get_post_type() {
    static $cached_post_type = null;
    
    if ( null === $cached_post_type ) {
        $cached_post_type = get_option( 'ensemble_post_type', 'post' );
    }
    
    return $cached_post_type;
}

/**
 * Get the configured theme
 * 
 * Uses static cache for performance.
 * 
 * @return string Theme name ('dark' or 'light')
 */
function ensemble_get_theme() {
    static $cached_theme = null;
    
    if ( null === $cached_theme ) {
        $cached_theme = get_option( 'ensemble_theme', 'dark' );
    }
    
    return $cached_theme;
}

/**
 * Check if a post is an Ensemble event
 * 
 * @param int|WP_Post $post Post ID or object
 * @return bool
 */
function ensemble_is_event($post = null) {
    if (!$post) {
        $post = get_post();
    }
    
    if (!$post) {
        return false;
    }
    
    $event_post_type = ensemble_get_post_type();
    
    // Check if post type matches
    if (get_post_type($post) !== $event_post_type) {
        return false;
    }
    
    // Check if post has ensemble category (for 'post' type)
    if ($event_post_type === 'post') {
        $terms = get_the_terms($post, 'ensemble_category');
        return !empty($terms) && !is_wp_error($terms);
    }
    
    // For custom post types, assume all posts are events
    return true;
}

/**
 * Get event query args with correct post type
 * 
 * @param array $args Additional query args
 * @return array Complete query args
 */
function ensemble_get_event_query_args($args = array()) {
    $defaults = array(
        'post_type' => ensemble_get_post_type(),
        'posts_per_page' => -1,
        'post_status' => 'publish',
    );
    
    // For 'post' type, add taxonomy filter
    if (ensemble_get_post_type() === 'post') {
        $defaults['tax_query'] = array(
            array(
                'taxonomy' => 'ensemble_category',
                'operator' => 'EXISTS',
            ),
        );
    }
    
    return wp_parse_args($args, $defaults);
}

/**
 * ========================================
 * META KEY DETECTION (CACHED)
 * ========================================
 */

/**
 * Get the date meta key used by events
 * 
 * Detects which meta key format is being used (ACF mapped, wizard, or legacy)
 * and caches the result for 24 hours using ES_Cache.
 * 
 * @since 2.9.0
 * @param bool $force_refresh Force detection even if cached.
 * @return string The meta key to use for date queries.
 */
function ensemble_get_date_meta_key( $force_refresh = false ) {
    // Static cache for same-request performance
    static $static_cache = null;
    
    if ( null !== $static_cache && ! $force_refresh ) {
        return $static_cache;
    }
    
    // Try ES_Cache (Transient) first
    if ( class_exists( 'ES_Cache' ) && ! $force_refresh ) {
        $cached = ES_Cache::get_meta_key( 'date' );
        if ( null !== $cached ) {
            $static_cache = $cached;
            return $cached;
        }
    }
    
    // Detect the meta key
    $date_key = ensemble_detect_date_meta_key();
    
    // Cache for 24 hours
    if ( class_exists( 'ES_Cache' ) ) {
        ES_Cache::set_meta_key( 'date', $date_key );
    }
    
    $static_cache = $date_key;
    return $date_key;
}

/**
 * Detect which date meta key format is being used
 * 
 * Checks in order: Field Mapping > Wizard format > Legacy format
 * 
 * @since 2.9.0
 * @return string The detected meta key.
 */
function ensemble_detect_date_meta_key() {
    // 1. Check if user has configured field mapping for start_date
    $mapped_field = ensemble_get_mapped_field( 'start_date' );
    if ( $mapped_field ) {
        return $mapped_field;
    }
    
    $post_type = ensemble_get_post_type();
    
    // 2. Check if any events use es_ prefixed keys (wizard format)
    $test_query = new WP_Query( array(
        'post_type'      => $post_type,
        'posts_per_page' => 1,
        'meta_key'       => 'es_event_start_date',
        'fields'         => 'ids',
        'no_found_rows'  => true,
    ) );
    
    if ( $test_query->have_posts() ) {
        wp_reset_postdata();
        return 'es_event_start_date';
    }
    
    // 3. Check for legacy keys
    $test_query = new WP_Query( array(
        'post_type'      => $post_type,
        'posts_per_page' => 1,
        'meta_key'       => 'event_date',
        'fields'         => 'ids',
        'no_found_rows'  => true,
    ) );
    
    if ( $test_query->have_posts() ) {
        wp_reset_postdata();
        return 'event_date';
    }
    
    wp_reset_postdata();
    
    // Default to wizard format
    return 'es_event_start_date';
}

/**
 * Get the time meta key used by events
 * 
 * @since 2.9.0
 * @param bool $force_refresh Force detection.
 * @return string The meta key to use for time queries.
 */
function ensemble_get_time_meta_key( $force_refresh = false ) {
    $date_key = ensemble_get_date_meta_key( $force_refresh );
    
    // Derive time key from date key format
    if ( 'es_event_start_date' === $date_key ) {
        return 'es_event_start_time';
    } elseif ( 'event_date' === $date_key ) {
        return 'event_time';
    }
    
    // For mapped fields, try to get time mapping
    $mapped_time = ensemble_get_mapped_field( 'start_time' );
    return $mapped_time ? $mapped_time : 'es_event_start_time';
}

/**
 * Get the location meta key used by events
 * 
 * @since 2.9.0
 * @return string The meta key to use for location queries.
 */
function ensemble_get_location_meta_key() {
    // Check field mapping first
    $mapped = ensemble_get_mapped_field( 'location' );
    if ( $mapped ) {
        return $mapped;
    }
    
    $date_key = ensemble_get_date_meta_key();
    
    // Derive from date key format
    if ( 'es_event_start_date' === $date_key ) {
        return 'es_event_location';
    }
    
    return 'event_location';
}

/**
 * Get the artist meta key used by events
 * 
 * @since 2.9.0
 * @return string The meta key to use for artist queries.
 */
function ensemble_get_artist_meta_key() {
    // Check field mapping first
    $mapped = ensemble_get_mapped_field( 'artist' );
    if ( $mapped ) {
        return $mapped;
    }
    
    $date_key = ensemble_get_date_meta_key();
    
    // Derive from date key format
    if ( 'es_event_start_date' === $date_key ) {
        return 'es_event_artist';
    }
    
    return 'event_artist';
}

/**
 * Clear meta key detection cache
 * 
 * Call this when field mappings or settings change.
 * 
 * @since 2.9.0
 * @return void
 */
function ensemble_clear_meta_key_cache() {
    if ( class_exists( 'ES_Cache' ) ) {
        ES_Cache::flush_group( ES_Cache::GROUP_META );
    }
    
    // Force refresh on next call
    ensemble_get_date_meta_key( true );
}

/**
 * ========================================
 * META KEY SHORTHAND FUNCTIONS
 * ========================================
 * Convenient wrappers for ES_Meta_Keys class
 */

/**
 * Get the correct meta key for a field
 * 
 * Shorthand for ES_Meta_Keys::get()
 * 
 * @since 2.9.4
 * @param string $field Standard field name (e.g., 'date', 'location', 'artist')
 * @return string The meta key to use
 * 
 * @example
 * $date = get_post_meta($event_id, ensemble_meta_key('date'), true);
 * $location_id = get_post_meta($event_id, ensemble_meta_key('location'), true);
 */
function ensemble_meta_key($field) {
    if (class_exists('ES_Meta_Keys')) {
        return ES_Meta_Keys::get($field);
    }
    
    // Fallback if class not loaded yet
    return 'event_' . $field;
}

/**
 * Get artist meta key
 * 
 * @since 2.9.4
 * @param string $field Artist field name
 * @return string The meta key
 */
function ensemble_artist_meta_key($field) {
    if (class_exists('ES_Meta_Keys')) {
        return ES_Meta_Keys::get_artist($field);
    }
    return 'artist_' . $field;
}

/**
 * Get location meta key
 * 
 * @since 2.9.4
 * @param string $field Location field name
 * @return string The meta key
 */
function ensemble_location_meta_key($field) {
    if (class_exists('ES_Meta_Keys')) {
        return ES_Meta_Keys::get_location($field);
    }
    return 'location_' . $field;
}

/**
 * ========================================
 * BATCH LOADING / QUERY OPTIMIZATION
 * ========================================
 * Functions to preload data and reduce database queries
 */

/**
 * Preload meta data for multiple events
 * 
 * Loads all post meta for the given event IDs into WordPress object cache.
 * After calling this, subsequent get_post_meta() calls are cache hits.
 * 
 * @since 2.9.0
 * @param array $event_ids Array of event post IDs.
 * @return void
 */
function ensemble_preload_events_meta( $event_ids ) {
    if ( empty( $event_ids ) || ! is_array( $event_ids ) ) {
        return;
    }
    
    // Filter to valid IDs
    $event_ids = array_filter( array_map( 'absint', $event_ids ) );
    
    if ( empty( $event_ids ) ) {
        return;
    }
    
    // Use WordPress built-in function to batch-load meta
    update_postmeta_cache( $event_ids );
    
    // Log in debug mode
    if ( function_exists( 'ensemble_log' ) ) {
        ensemble_log( 'Preloaded meta for events', array(
            'count' => count( $event_ids ),
        ) );
    }
}

/**
 * Preload meta data for multiple artists
 * 
 * @since 2.9.0
 * @param array $artist_ids Array of artist post IDs.
 * @return void
 */
function ensemble_preload_artists_meta( $artist_ids ) {
    if ( empty( $artist_ids ) || ! is_array( $artist_ids ) ) {
        return;
    }
    
    $artist_ids = array_filter( array_map( 'absint', $artist_ids ) );
    
    if ( ! empty( $artist_ids ) ) {
        update_postmeta_cache( $artist_ids );
    }
}

/**
 * Preload meta data for multiple locations
 * 
 * @since 2.9.0
 * @param array $location_ids Array of location post IDs.
 * @return void
 */
function ensemble_preload_locations_meta( $location_ids ) {
    if ( empty( $location_ids ) || ! is_array( $location_ids ) ) {
        return;
    }
    
    $location_ids = array_filter( array_map( 'absint', $location_ids ) );
    
    if ( ! empty( $location_ids ) ) {
        update_postmeta_cache( $location_ids );
    }
}

/**
 * Preload all related data for an event list
 * 
 * Call this before looping through events to preload:
 * - Event meta data
 * - Related artist meta data
 * - Related location meta data
 * - Thumbnails
 * 
 * @since 2.9.0
 * @param WP_Query|array $events WP_Query object or array of event post objects/IDs.
 * @return void
 */
function ensemble_preload_event_list_data( $events ) {
    // Convert WP_Query to array of IDs
    if ( $events instanceof WP_Query ) {
        $event_ids = wp_list_pluck( $events->posts, 'ID' );
    } elseif ( is_array( $events ) ) {
        $event_ids = array();
        foreach ( $events as $event ) {
            if ( is_object( $event ) && isset( $event->ID ) ) {
                $event_ids[] = $event->ID;
            } elseif ( is_numeric( $event ) ) {
                $event_ids[] = (int) $event;
            }
        }
    } else {
        return;
    }
    
    if ( empty( $event_ids ) ) {
        return;
    }
    
    // 1. Preload event meta
    ensemble_preload_events_meta( $event_ids );
    
    // 2. Preload thumbnails
    update_postmeta_cache( $event_ids ); // Already done, but ensures _thumbnail_id is loaded
    
    // 3. Collect and preload related artists
    $artist_ids = array();
    foreach ( $event_ids as $event_id ) {
        $artist = ensemble_get_event_meta( $event_id, 'artist' );
        if ( ! empty( $artist ) ) {
            if ( is_array( $artist ) ) {
                $artist_ids = array_merge( $artist_ids, $artist );
            } else {
                $artist_ids[] = $artist;
            }
        }
    }
    
    if ( ! empty( $artist_ids ) ) {
        $artist_ids = array_unique( array_filter( array_map( 'absint', $artist_ids ) ) );
        ensemble_preload_artists_meta( $artist_ids );
        
        // Preload artist thumbnails
        $artist_thumbnail_ids = array();
        foreach ( $artist_ids as $artist_id ) {
            $thumb_id = get_post_thumbnail_id( $artist_id );
            if ( $thumb_id ) {
                $artist_thumbnail_ids[] = $thumb_id;
            }
        }
        if ( ! empty( $artist_thumbnail_ids ) ) {
            update_postmeta_cache( $artist_thumbnail_ids );
        }
    }
    
    // 4. Collect and preload related locations
    $location_ids = array();
    foreach ( $event_ids as $event_id ) {
        $location = ensemble_get_event_meta( $event_id, 'location' );
        if ( ! empty( $location ) ) {
            $location_ids[] = $location;
        }
    }
    
    if ( ! empty( $location_ids ) ) {
        $location_ids = array_unique( array_filter( array_map( 'absint', $location_ids ) ) );
        ensemble_preload_locations_meta( $location_ids );
    }
    
    // 5. Preload event thumbnails
    $thumbnail_ids = array();
    foreach ( $event_ids as $event_id ) {
        $thumb_id = get_post_thumbnail_id( $event_id );
        if ( $thumb_id ) {
            $thumbnail_ids[] = $thumb_id;
        }
    }
    if ( ! empty( $thumbnail_ids ) ) {
        update_postmeta_cache( $thumbnail_ids );
    }
    
    // Log in debug mode
    if ( function_exists( 'ensemble_log' ) ) {
        ensemble_log( 'Preloaded event list data', array(
            'events'    => count( $event_ids ),
            'artists'   => count( $artist_ids ),
            'locations' => count( $location_ids ),
        ) );
    }
}

/**
 * Batch get event meta for multiple events
 * 
 * More efficient than calling ensemble_get_event_meta() in a loop.
 * Returns associative array keyed by event ID.
 * 
 * @since 2.9.0
 * @param array  $event_ids Array of event post IDs.
 * @param string $field     Field name to retrieve.
 * @return array Associative array of event_id => value.
 */
function ensemble_batch_get_event_meta( $event_ids, $field ) {
    if ( empty( $event_ids ) || ! is_array( $event_ids ) ) {
        return array();
    }
    
    // Preload all meta first
    ensemble_preload_events_meta( $event_ids );
    
    // Now get individual values (all from cache)
    $results = array();
    foreach ( $event_ids as $event_id ) {
        $results[ $event_id ] = ensemble_get_event_meta( $event_id, $field );
    }
    
    return $results;
}

/**
 * ========================================
 * FIELD MAPPING HELPER FUNCTIONS
 * ========================================
 * These functions handle the mapping between
 * Ensemble's standard fields and user's ACF fields
 */

/**
 * Get the field mapping configuration
 * 
 * Uses static cache to avoid repeated get_option() calls within the same request.
 * 
 * @param bool $force_refresh Force refresh from database.
 * @return array Field mapping array
 */
function ensemble_get_field_mapping( $force_refresh = false ) {
    static $cached_mapping = null;
    
    if ( null === $cached_mapping || $force_refresh ) {
        $cached_mapping = get_option( 'ensemble_field_mapping', array() );
    }
    
    return $cached_mapping;
}

/**
 * Clear the field mapping cache
 * 
 * Call this after updating field mappings.
 * 
 * @return void
 */
function ensemble_clear_field_mapping_cache() {
    // Force refresh on next call
    ensemble_get_field_mapping( true );
}

/**
 * Get the mapped ACF field name for a standard field
 * 
 * @param string $standard_field Standard Ensemble field name
 * @return string|false Mapped ACF field name or false if not mapped
 */
function ensemble_get_mapped_field($standard_field) {
    $mapping = ensemble_get_field_mapping();
    return isset($mapping[$standard_field]) ? $mapping[$standard_field] : false;
}

/**
 * Get event meta with Field Mapping support
 * Universal function for templates and frontend code
 * 
 * @param int $event_id Event ID
 * @param string $field Field name ('start_date', 'start_time', 'location', 'artist', etc.)
 * @return mixed Meta value
 */
function ensemble_get_event_meta($event_id, $field) {
    // 1. Try Field Mapping first
    $mapped_field = ensemble_get_mapped_field($field);
    
    if ($mapped_field && function_exists('get_field')) {
        $value = get_field($mapped_field, $event_id);
        if (!empty($value)) {
            // Handle ACF post object fields
            if (is_object($value) && isset($value->ID)) {
                return $value->ID;
            }
            // Handle ACF relationship fields
            if (is_array($value) && isset($value[0]->ID)) {
                return $value[0]->ID;
            }
            return $value;
        }
    }
    
    // 2. Try es_event_{field} format (wizard)
    $value = get_post_meta($event_id, 'es_event_' . $field, true);
    
    if (empty($value)) {
        // 3. Try legacy format
        $legacy_map = array(
            'start_date' => 'event_date',
            'start_time' => 'event_time',
            'end_date' => 'event_end_date',
            'end_time' => 'event_end_time',
            'location' => 'event_location',
            'artist' => 'event_artist',
            'price' => 'event_price',
            'ticket_url' => 'event_ticket_url',
            'description' => 'event_description',
        );
        
        $legacy_key = isset($legacy_map[$field]) ? $legacy_map[$field] : $field;
        $value = get_post_meta($event_id, $legacy_key, true);
        
        // Handle serialized artist data
        if ($field === 'artist' && is_string($value) && strpos($value, 'a:') === 0) {
            $artist_array = @unserialize($value);
            if (is_array($artist_array) && !empty($artist_array)) {
                $value = $artist_array[0];
            }
        }
    }
    
    return $value;
}

/**
 * Get the mapped ACF field name for a standard field
 * 
 * @param string $standard_field Standard Ensemble field name
 * @return string|false Mapped ACF field name or false if not mapped
 * @deprecated Use ensemble_get_event_meta() instead
 */
function ensemble_get_mapped_field_legacy($standard_field) {
    $mapping = ensemble_get_field_mapping();
    return isset($mapping[$standard_field]) ? $mapping[$standard_field] : false;
}

/**
 * ========================================
 * FORMAT CONVERSION HELPERS
 * ========================================
 * Universal format parsers for date, time, and location
 */

/**
 * Parse date from various formats to Y-m-d
 * 
 * Supports:
 * - 25.12.2025, 25.12., Fr. 11.12.2026
 * - 2025-12-25
 * - 12/25/2025
 * - timestamp
 * 
 * @param mixed $value Date value in any format
 * @return string|false Date in Y-m-d format or false
 */
function ensemble_parse_date($value) {
    if (empty($value)) {
        return false;
    }
    
    // Already in correct format
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        return $value;
    }
    
    // Remove weekday prefix (e.g., "Fr. ")
    $value = preg_replace('/^[A-Za-z]{2,3}\.\s*/', '', $value);
    
    // German format: 25.12.2026 or 25.12.
    if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{2,4})?$/', $value, $matches)) {
        $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
        $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
        $year = !empty($matches[3]) ? $matches[3] : date('Y');
        
        // Convert 2-digit year to 4-digit
        if (strlen($year) == 2) {
            $year = (int)$year < 50 ? '20' . $year : '19' . $year;
        }
        
        return $year . '-' . $month . '-' . $day;
    }
    
    // US format: 12/25/2025
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{2,4})$/', $value, $matches)) {
        $month = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
        $day = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
        $year = $matches[3];
        
        if (strlen($year) == 2) {
            $year = (int)$year < 50 ? '20' . $year : '19' . $year;
        }
        
        return $year . '-' . $month . '-' . $day;
    }
    
    // Try strtotime as fallback
    $timestamp = strtotime($value);
    if ($timestamp !== false) {
        return date('Y-m-d', $timestamp);
    }
    
    return false;
}

/**
 * Parse time from various formats to H:i
 * 
 * Supports:
 * - 18:00, 18:00 h, 18:00h
 * - 18.00, 18h00
 * - 8:00 PM, 8 PM
 * - 1800, 800
 * 
 * @param mixed $value Time value in any format
 * @return string|false Time in H:i format or false
 */
function ensemble_parse_time($value) {
    if (empty($value)) {
        return false;
    }
    
    // Remove whitespace and 'h' suffix
    $value = trim($value);
    $value = preg_replace('/\s*h\s*$/i', '', $value);
    
    // Already in correct format: 18:00
    if (preg_match('/^(\d{1,2}):(\d{2})$/', $value, $matches)) {
        return str_pad($matches[1], 2, '0', STR_PAD_LEFT) . ':' . $matches[2];
    }
    
    // Dot format: 18.00
    if (preg_match('/^(\d{1,2})\.(\d{2})$/', $value, $matches)) {
        return str_pad($matches[1], 2, '0', STR_PAD_LEFT) . ':' . $matches[2];
    }
    
    // h format: 18h00
    if (preg_match('/^(\d{1,2})h(\d{2})$/i', $value, $matches)) {
        return str_pad($matches[1], 2, '0', STR_PAD_LEFT) . ':' . $matches[2];
    }
    
    // 12-hour format: 8:00 PM, 8 PM
    if (preg_match('/^(\d{1,2}):?(\d{2})?\s*(AM|PM)$/i', $value, $matches)) {
        $hour = (int)$matches[1];
        $minute = !empty($matches[2]) ? $matches[2] : '00';
        $meridiem = strtoupper($matches[3]);
        
        if ($meridiem === 'PM' && $hour < 12) {
            $hour += 12;
        } elseif ($meridiem === 'AM' && $hour == 12) {
            $hour = 0;
        }
        
        return str_pad($hour, 2, '0', STR_PAD_LEFT) . ':' . $minute;
    }
    
    // 4-digit format: 1800, 0800
    if (preg_match('/^(\d{1,4})$/', $value)) {
        $padded = str_pad($value, 4, '0', STR_PAD_LEFT);
        $hour = substr($padded, 0, 2);
        $minute = substr($padded, 2, 2);
        return $hour . ':' . $minute;
    }
    
    return false;
}

/**
 * Parse location - handles both text and Post ID with Smart-Sync
 * 
 * @param mixed $value Location value (text or post ID)
 * @param bool $auto_create Whether to auto-create location if not found
 * @return mixed Post ID if found/created, or original value
 */
function ensemble_parse_location($value, $auto_create = true) {
    if (empty($value)) {
        return false;
    }
    
    // If it's already a number (Post ID), return it
    if (is_numeric($value)) {
        return (int)$value;
    }
    
    // If it's text, try to find matching location post by exact title
    global $wpdb;
    $location_id = $wpdb->get_var($wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts} 
        WHERE post_type = 'ensemble_location' 
        AND post_status = 'publish'
        AND post_title = %s
        LIMIT 1",
        $value
    ));
    
    if ($location_id) {
        return (int)$location_id;
    }
    
    // Try fuzzy search as fallback
    $locations = get_posts(array(
        'post_type' => 'ensemble_location',
        'posts_per_page' => 1,
        's' => $value,
        'post_status' => 'publish',
        'fields' => 'ids',
    ));
    
    if (!empty($locations)) {
        return $locations[0];
    }
    
    // Auto-create location if enabled and not found
    if ($auto_create) {
        $new_location_id = wp_insert_post(array(
            'post_title' => $value,
            'post_type' => 'ensemble_location',
            'post_status' => 'publish',
        ));
        
        if (!is_wp_error($new_location_id)) {
            return $new_location_id;
        }
    }
    
    // Return original value if no match found and auto-create disabled
    return $value;
}

/**
 * Parse artist - handles both text and Post ID with Smart-Sync
 * 
 * @param mixed $value Artist value (text, post ID, or array)
 * @param bool $auto_create Whether to auto-create artist if not found
 * @return mixed Post ID(s) if found/created, or original value
 */
function ensemble_parse_artist($value, $auto_create = true) {
    if (empty($value)) {
        return false;
    }
    
    // Handle array of artists
    if (is_array($value)) {
        $parsed_artists = array();
        foreach ($value as $artist) {
            $parsed = ensemble_parse_artist($artist, $auto_create);
            if ($parsed !== false) {
                $parsed_artists[] = $parsed;
            }
        }
        return !empty($parsed_artists) ? $parsed_artists : false;
    }
    
    // If it's already a number (Post ID), return it
    if (is_numeric($value)) {
        return (int)$value;
    }
    
    // If it's text, try to find matching artist post by exact title
    global $wpdb;
    $artist_id = $wpdb->get_var($wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts} 
        WHERE post_type = 'ensemble_artist' 
        AND post_status = 'publish'
        AND post_title = %s
        LIMIT 1",
        $value
    ));
    
    if ($artist_id) {
        return (int)$artist_id;
    }
    
    // Try fuzzy search as fallback
    $artists = get_posts(array(
        'post_type' => 'ensemble_artist',
        'posts_per_page' => 1,
        's' => $value,
        'post_status' => 'publish',
        'fields' => 'ids',
    ));
    
    if (!empty($artists)) {
        return $artists[0];
    }
    
    // Auto-create artist if enabled and not found
    if ($auto_create) {
        $new_artist_id = wp_insert_post(array(
            'post_title' => $value,
            'post_type' => 'ensemble_artist',
            'post_status' => 'publish',
        ));
        
        if (!is_wp_error($new_artist_id)) {
            return $new_artist_id;
        }
    }
    
    // Return original value if no match found and auto-create disabled
    return $value;
}

/**
 * Get field value with mapping support and format conversion
 * Reads from mapped ACF field if configured, otherwise from standard field
 * 
 * @param string $standard_field Standard Ensemble field name (e.g., 'event_date')
 * @param int $post_id Post ID
 * @return mixed Field value (converted to correct format)
 */
function ensemble_get_field($standard_field, $post_id = null) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    
    // Ensure post_id is an integer
    $post_id = intval($post_id);
    
    // Ensure standard_field is a string
    if (!is_string($standard_field) || empty($standard_field)) {
        return null;
    }
    
    // Normalize field name (event_date -> date, event_location -> location, etc.)
    $normalized_field = $standard_field;
    if (strpos($standard_field, 'event_') === 0) {
        $normalized_field = str_replace('event_', '', $standard_field);
        // Special cases
        $normalize_map = array(
            'date' => 'start_date',
            'time' => 'start_time',
            'time_end' => 'end_time',
        );
        if (isset($normalize_map[$normalized_field])) {
            $normalized_field = $normalize_map[$normalized_field];
        }
    }
    
    // Get the correct meta key using ES_Meta_Keys
    $meta_key = $standard_field; // Default fallback
    if (class_exists('ES_Meta_Keys')) {
        $detected_key = ES_Meta_Keys::get($normalized_field);
        if ($detected_key) {
            $meta_key = $detected_key;
        }
    }
    
    // Check if field is mapped to custom ACF field
    $mapped_field = ensemble_get_mapped_field($normalized_field);
    
    // Ensure mapped_field is a string (not an array)
    if (is_array($mapped_field)) {
        $mapped_field = !empty($mapped_field[0]) ? $mapped_field[0] : false;
    }
    
    $value = null;
    
    if ($mapped_field && is_string($mapped_field) && function_exists('get_field')) {
        // User has mapped this field - read from mapped ACF field
        $value = @get_field($mapped_field, $post_id);
    }
    
    // If no mapped value, try the detected meta key
    if (empty($value)) {
        if (function_exists('get_field')) {
            $value = @get_field($meta_key, $post_id);
        }
        
        // Fallback to post meta if ACF returns nothing
        if (empty($value)) {
            $value = get_post_meta($post_id, $meta_key, true);
        }
    }
    
    // Apply format conversion based on field type
    if (!empty($value)) {
        switch ($normalized_field) {
            case 'start_date':
            case 'date':
                $parsed = ensemble_parse_date($value);
                return $parsed !== false ? $parsed : $value;
                
            case 'start_time':
            case 'end_time':
            case 'time':
            case 'time_end':
                $parsed = ensemble_parse_time($value);
                return $parsed !== false ? $parsed : $value;
                
            case 'location':
                return ensemble_parse_location($value);
                
            case 'artist':
                return ensemble_parse_artist($value);
                
            default:
                return $value;
        }
    }
    
    return $value;
}

/**
 * Update field value with mapping support and reverse sync
 * Writes to both mapped ACF field and standard field for compatibility
 * 
 * @param string $standard_field Standard Ensemble field name (e.g., 'event_date')
 * @param mixed $value Field value
 * @param int $post_id Post ID
 * @return bool Success
 */
function ensemble_update_field($standard_field, $value, $post_id = null) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    
    // Normalize field name (event_date -> date, event_location -> location, etc.)
    $normalized_field = $standard_field;
    if (strpos($standard_field, 'event_') === 0) {
        $normalized_field = str_replace('event_', '', $standard_field);
        // Special cases
        $normalize_map = array(
            'date' => 'start_date',
            'time' => 'start_time',
            'time_end' => 'end_time',
        );
        if (isset($normalize_map[$normalized_field])) {
            $normalized_field = $normalize_map[$normalized_field];
        }
    }
    
    // Get the correct meta key using ES_Meta_Keys
    $meta_key = $standard_field; // Default fallback
    if (class_exists('ES_Meta_Keys')) {
        $detected_key = ES_Meta_Keys::get($normalized_field);
        if ($detected_key) {
            $meta_key = $detected_key;
        }
    }
    
    // Check if field is mapped to custom ACF field
    $mapped_field = ensemble_get_mapped_field($normalized_field);
    
    if ($mapped_field && function_exists('update_field')) {
        // Prepare value for mapped field (reverse conversion if needed)
        $mapped_value = $value;
        
        // For Location/Artist: If value is Post ID, get the title for text-based ACF fields
        if (in_array($normalized_field, array('location', 'event_location')) && is_numeric($value)) {
            $location_post = get_post($value);
            if ($location_post) {
                $mapped_value = $location_post->post_title;
            }
        } elseif (in_array($normalized_field, array('artist', 'event_artist'))) {
            // Handle single artist or array
            if (is_numeric($value)) {
                $artist_post = get_post($value);
                $mapped_value = $artist_post ? $artist_post->post_title : $value;
            } elseif (is_array($value)) {
                $mapped_value = array();
                foreach ($value as $artist_id) {
                    if (is_numeric($artist_id)) {
                        $artist_post = get_post($artist_id);
                        $mapped_value[] = $artist_post ? $artist_post->post_title : $artist_id;
                    } else {
                        $mapped_value[] = $artist_id;
                    }
                }
            }
        }
        
        // User has mapped this field - write to mapped ACF field
        update_field($mapped_field, $mapped_value, $post_id);
    }
    
    // Write to the correct meta key (determined by ES_Meta_Keys)
    if (function_exists('update_field')) {
        // ACF available - use update_field for proper field handling
        return update_field($meta_key, $value, $post_id);
    } else {
        // ACF not available - use post meta directly
        return update_post_meta($post_id, $meta_key, $value);
    }
}

/**
 * Get multiple fields with mapping support
 * 
 * @param array $fields Array of standard field names
 * @param int $post_id Post ID
 * @return array Associative array of field values
 */
function ensemble_get_fields($fields, $post_id = null) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    
    $values = array();
    foreach ($fields as $field) {
        $values[$field] = ensemble_get_field($field, $post_id);
    }
    
    return $values;
}

/**
 * Update multiple fields with mapping support
 * 
 * @param array $fields Associative array of field => value pairs
 * @param int $post_id Post ID
 * @return bool Success
 */
function ensemble_update_fields($fields, $post_id = null) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    
    $success = true;
    foreach ($fields as $field => $value) {
        if (!ensemble_update_field($field, $value, $post_id)) {
            $success = false;
        }
    }
    
    return $success;
}

/**
 * Check if a field is mapped
 * 
 * @param string $standard_field Standard Ensemble field name
 * @return bool True if field is mapped
 */
function ensemble_is_field_mapped($standard_field) {
    return (bool) ensemble_get_mapped_field($standard_field);
}

/**
 * ========================================
 * SMART SYNC HELPERS
 * ========================================
 */

/**
 * Get Location name from ID
 * 
 * @param int $location_id Location Post ID
 * @return string|false Location name or false
 */
function ensemble_get_location_name($location_id) {
    if (!is_numeric($location_id)) {
        return $location_id;
    }
    
    $location = get_post($location_id);
    return $location ? $location->post_title : false;
}

/**
 * Get Artist name(s) from ID(s)
 * 
 * @param int|array $artist_ids Artist Post ID or array of IDs
 * @return string|array|false Artist name(s) or false
 */
function ensemble_get_artist_names($artist_ids) {
    if (empty($artist_ids)) {
        return false;
    }
    
    if (is_array($artist_ids)) {
        $names = array();
        foreach ($artist_ids as $artist_id) {
            if (is_numeric($artist_id)) {
                $artist = get_post($artist_id);
                if ($artist) {
                    $names[] = $artist->post_title;
                }
            } else {
                $names[] = $artist_id;
            }
        }
        return !empty($names) ? $names : false;
    }
    
    if (is_numeric($artist_ids)) {
        $artist = get_post($artist_ids);
        return $artist ? $artist->post_title : false;
    }
    
    return $artist_ids;
}

/**
 * ========================================
 * EVENT STATUS HELPERS
 * ========================================
 * Unified status rendering for templates
 */

/**
 * Get event status labels (translated)
 * 
 * @return array Status labels keyed by status slug
 */
function ensemble_get_status_labels() {
    return array(
        'publish'   => __('Published', 'ensemble'),
        'scheduled' => __('Scheduled', 'ensemble'),
        'cancelled' => __('Cancelled', 'ensemble'),
        'postponed' => __('Postponed', 'ensemble'),
        'soldout'   => __('Sold Out', 'ensemble'),
        'draft'     => __('Draft', 'ensemble'),
    );
}

/**
 * Get formatted status label for an event
 * 
 * @param string $status Event status slug
 * @param bool $with_icon Include icon/symbol
 * @return string Formatted status label
 */
function ensemble_get_status_label($status, $with_icon = false) {
    $labels = ensemble_get_status_labels();
    $label = isset($labels[$status]) ? $labels[$status] : ucfirst($status);
    
    if ($with_icon) {
        $icons = array(
            'cancelled' => '✕',
            'postponed' => '⏸',
            'soldout'   => '●',
        );
        if (isset($icons[$status])) {
            $label = $icons[$status] . ' ' . $label;
        }
    }
    
    return $label;
}

/**
 * Render event status badge HTML
 * 
 * @param string $status Event status slug
 * @param string $style Badge style: 'badge', 'text', 'banner'
 * @return string HTML output
 */
function ensemble_render_status_badge($status, $style = 'badge') {
    // Don't show badge for normal published/scheduled events
    if (in_array($status, array('publish', 'scheduled', ''))) {
        return '';
    }
    
    $label = ensemble_get_status_label($status, true);
    $class = 'es-status-' . sanitize_html_class($status);
    
    switch ($style) {
        case 'banner':
            return sprintf(
                '<div class="es-status-banner %s">%s</div>',
                esc_attr($class),
                esc_html($label)
            );
        
        case 'text':
            return sprintf(
                '<span class="es-status-text %s">%s</span>',
                esc_attr($class),
                esc_html($label)
            );
        
        case 'badge':
        default:
            return sprintf(
                '<span class="es-status-badge %s">%s</span>',
                esc_attr($class),
                esc_html($label)
            );
    }
}

/**
 * Check if event is in a "special" status (cancelled, postponed, etc.)
 * 
 * @param string $status Event status slug
 * @return bool True if special status
 */
function ensemble_is_special_status($status) {
    return in_array($status, array('cancelled', 'postponed', 'soldout'));
}

/**
 * Debug: Show field mapping info
 * For troubleshooting - add to functions.php temporarily
 * 
 * Usage: ensemble_debug_field_mapping($post_id);
 */
function ensemble_debug_field_mapping($post_id = null) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    
    echo '<div style="background: #f0f0f0; padding: 20px; margin: 20px 0; border: 2px solid #333;">';
    echo '<h3>🔍 Field Mapping Debug - Post ID: ' . $post_id . '</h3>';
    
    $mapping = ensemble_get_field_mapping();
    echo '<h4>Current Mapping:</h4>';
    echo '<pre>' . print_r($mapping, true) . '</pre>';
    
    echo '<h4>Field Values:</h4>';
    $fields = array('event_date', 'event_time', 'event_time_end', 'event_location', 'event_artist', 'event_description', 'event_price');
    
    echo '<table style="width: 100%; border-collapse: collapse;">';
    echo '<tr style="background: #333; color: white;"><th>Field</th><th>Mapped To</th><th>Value (Raw)</th><th>Value (Parsed)</th></tr>';
    
    foreach ($fields as $field) {
        $mapped = ensemble_get_mapped_field($field);
        $raw_value = $mapped ? get_field($mapped, $post_id) : get_field($field, $post_id);
        $parsed_value = ensemble_get_field($field, $post_id);
        
        echo '<tr style="border-bottom: 1px solid #ccc;">';
        echo '<td style="padding: 8px;"><strong>' . $field . '</strong></td>';
        echo '<td style="padding: 8px;">' . ($mapped ? $mapped : '<em>not mapped</em>') . '</td>';
        echo '<td style="padding: 8px;"><code>' . print_r($raw_value, true) . '</code></td>';
        echo '<td style="padding: 8px;"><code>' . print_r($parsed_value, true) . '</code></td>';
        echo '</tr>';
    }
    
    echo '</table>';
    echo '</div>';
}

/**
 * ========================================
 * AUTO-SYNC: Locations & Artists
 * ========================================
 * Automatically creates ensemble_location and ensemble_artist posts
 * from text values in mapped fields and updates the event with the IDs.
 */

/**
 * Sync event relations (Location & Artists)
 * 
 * Reads location/artist from mapped fields, creates posts if needed,
 * and updates the event with the post IDs.
 * 
 * @param int $event_id Event Post ID
 * @param bool $force_sync Force sync even if already has valid IDs
 * @return array Array with 'location_synced' and 'artists_synced' counts
 */
function ensemble_sync_event_relations($event_id, $force_sync = false) {
    $result = array(
        'location_synced' => false,
        'artists_synced' => 0,
        'location_created' => false,
        'artists_created' => 0,
    );
    
    // Check if already synced (to avoid repeated syncs)
    $last_sync = get_post_meta($event_id, '_ensemble_relations_synced', true);
    if ($last_sync && !$force_sync) {
        // Already synced within last hour
        if (time() - intval($last_sync) < 3600) {
            return $result;
        }
    }
    
    // Get mapped field for location
    $location_mapped = ensemble_get_mapped_field('event_location');
    
    if ($location_mapped) {
        // Get raw value from mapped field
        $location_raw = function_exists('get_field') ? get_field($location_mapped, $event_id) : null;
        
        // DEBUG
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Ensemble Auto-Sync - Event $event_id - Location raw value: " . print_r($location_raw, true));
        }
        
        // Extract location name from various formats
        $location_name = null;
        
        if (is_object($location_raw) && isset($location_raw->ID)) {
            // ACF Post Object - get title
            $location_name = $location_raw->post_title;
        } elseif (is_array($location_raw) && !empty($location_raw)) {
            // ACF Relationship or array - get first item's title
            $first = reset($location_raw);
            if (is_object($first) && isset($first->ID)) {
                $location_name = $first->post_title;
            } elseif (is_string($first)) {
                $location_name = $first;
            }
        } elseif (is_string($location_raw) && !empty($location_raw) && !is_numeric($location_raw)) {
            // Plain text
            $location_name = trim($location_raw);
        }
        
        // If we have a location name, sync it
        if (!empty($location_name)) {
            // Check if location already exists
            $existing_location = get_posts(array(
                'post_type' => 'ensemble_location',
                'title' => $location_name,
                'posts_per_page' => 1,
                'post_status' => 'publish',
                'fields' => 'ids',
            ));
            
            $location_id = null;
            
            if (!empty($existing_location)) {
                $location_id = $existing_location[0];
            } else {
                // Create new location
                $location_id = wp_insert_post(array(
                    'post_title' => $location_name,
                    'post_type' => 'ensemble_location',
                    'post_status' => 'publish',
                ));
                
                if (!is_wp_error($location_id)) {
                    $result['location_created'] = true;
                    
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log("Ensemble Auto-Sync - Created new location: $location_name (ID: $location_id)");
                    }
                }
            }
            
            if ($location_id && !is_wp_error($location_id)) {
                // Update the event with the location ID
                update_post_meta($event_id, '_event_location', $location_id);
                
                // Also update the standard ACF field if it exists
                if (function_exists('update_field')) {
                    @update_field('event_location', $location_id, $event_id);
                }
                
                $result['location_synced'] = true;
            }
        }
    }
    
    // Get mapped field for artist
    $artist_mapped = ensemble_get_mapped_field('event_artist');
    
    if ($artist_mapped) {
        // Get raw value from mapped field
        $artist_raw = function_exists('get_field') ? get_field($artist_mapped, $event_id) : null;
        
        // DEBUG
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Ensemble Auto-Sync - Event $event_id - Artist raw value: " . print_r($artist_raw, true));
        }
        
        // Extract artist names from various formats
        $artist_names = array();
        
        if (is_object($artist_raw) && isset($artist_raw->ID)) {
            // Single ACF Post Object
            $artist_names[] = $artist_raw->post_title;
        } elseif (is_array($artist_raw)) {
            // Array of artists (could be Post Objects or strings)
            foreach ($artist_raw as $artist) {
                if (is_object($artist) && isset($artist->ID)) {
                    $artist_names[] = $artist->post_title;
                } elseif (is_string($artist) && !is_numeric($artist) && !empty($artist)) {
                    $artist_names[] = trim($artist);
                }
            }
        } elseif (is_string($artist_raw) && !is_numeric($artist_raw) && !empty($artist_raw)) {
            // Single text value - might be comma-separated
            if (strpos($artist_raw, ',') !== false) {
                $artist_names = array_map('trim', explode(',', $artist_raw));
            } else {
                $artist_names[] = trim($artist_raw);
            }
        }
        
        // Sync each artist
        $synced_ids = array();
        foreach ($artist_names as $artist_name) {
            if (empty($artist_name)) continue;
            
            // Check if artist already exists
            $existing_artist = get_posts(array(
                'post_type' => 'ensemble_artist',
                'title' => $artist_name,
                'posts_per_page' => 1,
                'post_status' => 'publish',
                'fields' => 'ids',
            ));
            
            $artist_id = null;
            
            if (!empty($existing_artist)) {
                $artist_id = $existing_artist[0];
            } else {
                // Create new artist
                $artist_id = wp_insert_post(array(
                    'post_title' => $artist_name,
                    'post_type' => 'ensemble_artist',
                    'post_status' => 'publish',
                ));
                
                if (!is_wp_error($artist_id)) {
                    $result['artists_created']++;
                    
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log("Ensemble Auto-Sync - Created new artist: $artist_name (ID: $artist_id)");
                    }
                }
            }
            
            if ($artist_id && !is_wp_error($artist_id)) {
                $synced_ids[] = $artist_id;
                $result['artists_synced']++;
            }
        }
        
        // Update the event with artist IDs
        if (!empty($synced_ids)) {
            update_post_meta($event_id, '_event_artist', $synced_ids);
            
            // Also update the standard ACF field if it exists
            if (function_exists('update_field')) {
                @update_field('event_artist', $synced_ids, $event_id);
            }
        }
    }
    
    // Mark as synced
    update_post_meta($event_id, '_ensemble_relations_synced', time());
    
    return $result;
}

/**
 * Bulk sync all events (for initial import or manual trigger)
 * 
 * @param array $args Optional WP_Query args to filter events
 * @return array Summary of synced items
 */
function ensemble_bulk_sync_relations($args = array()) {
    $defaults = array(
        'post_type' => ensemble_get_post_type(),
        'posts_per_page' => -1,
        'post_status' => array('publish', 'draft', 'future', 'pending'),
        'fields' => 'ids',
    );
    
    // For post type, only get posts with ensemble_category
    if (ensemble_get_post_type() === 'post') {
        $defaults['tax_query'] = array(
            array(
                'taxonomy' => 'ensemble_category',
                'operator' => 'EXISTS',
            ),
        );
    }
    
    $args = wp_parse_args($args, $defaults);
    $event_ids = get_posts($args);
    
    $summary = array(
        'events_processed' => 0,
        'locations_synced' => 0,
        'locations_created' => 0,
        'artists_synced' => 0,
        'artists_created' => 0,
    );
    
    foreach ($event_ids as $event_id) {
        $result = ensemble_sync_event_relations($event_id);
        
        $summary['events_processed']++;
        if ($result['location_synced']) $summary['locations_synced']++;
        if ($result['location_created']) $summary['locations_created']++;
        $summary['artists_synced'] += $result['artists_synced'];
        $summary['artists_created'] += $result['artists_created'];
    }
    
    return $summary;
}

/**
 * ============================================================================
 * LINK BEHAVIOR SETTINGS
 * Controls how Artists and Locations are linked in templates
 * ============================================================================
 */

/**
 * Get link settings
 * 
 * @return array Link behavior settings
 */
function ensemble_get_link_settings() {
    $link_artists = get_option('ensemble_link_artists', '1');
    $link_locations = get_option('ensemble_link_locations', '1');
    $location_link_new_tab = get_option('ensemble_location_link_new_tab', '1');
    
    return array(
        'link_artists' => !($link_artists === '0' || $link_artists === false),
        'link_locations' => !($link_locations === '0' || $link_locations === false),
        'location_link_target' => get_option('ensemble_location_link_target', 'post'),
        'location_link_new_tab' => !($location_link_new_tab === '0' || $location_link_new_tab === false),
    );
}

/**
 * Check if artists should be linked
 * 
 * @return bool
 */
function ensemble_should_link_artists() {
    $value = get_option('ensemble_link_artists', '1');
    // '0' or false means disabled, everything else means enabled
    return !($value === '0' || $value === false);
}

/**
 * Check if locations should be linked
 * 
 * @return bool
 */
function ensemble_should_link_locations() {
    $value = get_option('ensemble_link_locations', '1');
    return !($value === '0' || $value === false);
}

/**
 * Get the appropriate URL for an artist
 * Returns null if linking is disabled
 * Can return external website URL or social media if configured
 * 
 * @param int $artist_id Artist post ID
 * @return array|null Array with 'url', 'external', 'new_tab' keys, or null if no link
 */
function ensemble_get_artist_link($artist_id) {
    if (!ensemble_should_link_artists()) {
        return null;
    }
    
    $link_target = get_option('ensemble_artist_link_target', 'post');
    
    if ($link_target === 'website') {
        // First try website field
        $website = es_get_field($artist_id, 'website', 'artist');
        
        if (!empty($website)) {
            $new_tab_value = get_option('ensemble_artist_link_new_tab', '1');
            return array(
                'url' => esc_url($website),
                'external' => true,
                'new_tab' => !($new_tab_value === '0' || $new_tab_value === false),
            );
        }
        
        // Try social media links in order of priority
        $social_fields = array('instagram', 'facebook', 'soundcloud', 'spotify', 'youtube', 'twitter', 'bandcamp', 'mixcloud');
        foreach ($social_fields as $field) {
            $social_url = es_get_field($artist_id, $field, 'artist');
            if (!empty($social_url)) {
                $new_tab_value = get_option('ensemble_artist_link_new_tab', '1');
                return array(
                    'url' => esc_url($social_url),
                    'external' => true,
                    'new_tab' => !($new_tab_value === '0' || $new_tab_value === false),
                );
            }
        }
    }
    
    // Fallback to post permalink
    return array(
        'url' => get_permalink($artist_id),
        'external' => false,
        'new_tab' => false,
    );
}

/**
 * Get the appropriate URL for a location
 * Returns null if linking is disabled
 * Can return external website URL if configured
 * 
 * @param int $location_id Location post ID
 * @return array|null Array with 'url' and 'external' keys, or null if no link
 */
function ensemble_get_location_link($location_id) {
    if (!ensemble_should_link_locations()) {
        return null;
    }
    
    $link_target = get_option('ensemble_location_link_target', 'post');
    
    if ($link_target === 'website') {
        // Get the location_website field directly (from Location Manager)
        $website = es_get_field($location_id, 'website', 'location');
        
        if (!empty($website)) {
            $new_tab_value = get_option('ensemble_location_link_new_tab', '1');
            return array(
                'url' => esc_url($website),
                'external' => true,
                'new_tab' => !($new_tab_value === '0' || $new_tab_value === false),
            );
        }
    }
    
    // Fallback to post permalink
    return array(
        'url' => get_permalink($location_id),
        'external' => false,
        'new_tab' => false,
    );
}

/**
 * Render an artist link with proper attributes
 * 
 * @param int $artist_id Artist post ID
 * @param string $content Link content (typically artist name)
 * @param array $attrs Additional HTML attributes
 * @return string HTML link or plain text if no link
 */
function ensemble_artist_link($artist_id, $content, $attrs = array()) {
    $url = ensemble_get_artist_link($artist_id);
    
    if (!$url) {
        return esc_html($content);
    }
    
    $attr_string = '';
    foreach ($attrs as $key => $value) {
        $attr_string .= ' ' . esc_attr($key) . '="' . esc_attr($value) . '"';
    }
    
    return '<a href="' . esc_url($url) . '"' . $attr_string . '>' . esc_html($content) . '</a>';
}

/**
 * Render a location link with proper attributes
 * 
 * @param int $location_id Location post ID
 * @param string $content Link content (typically location name)
 * @param array $attrs Additional HTML attributes
 * @return string HTML link or plain text if no link
 */
function ensemble_location_link($location_id, $content, $attrs = array()) {
    $link = ensemble_get_location_link($location_id);
    
    if (!$link) {
        return esc_html($content);
    }
    
    // Add target="_blank" for external links
    if ($link['external'] && $link['new_tab']) {
        $attrs['target'] = '_blank';
        $attrs['rel'] = 'noopener noreferrer';
    }
    
    $attr_string = '';
    foreach ($attrs as $key => $value) {
        $attr_string .= ' ' . esc_attr($key) . '="' . esc_attr($value) . '"';
    }
    
    return '<a href="' . esc_url($link['url']) . '"' . $attr_string . '>' . esc_html($content) . '</a>';
}

// ============================================================================
// DISPLAY SETTINGS FUNCTIONS
// ============================================================================

/**
 * Get default display settings
 * 
 * @return array Default display settings structure
 */
function ensemble_get_default_display_settings() {
    return array(
        // Event Cards
        'cards' => array(
            'image'       => true,
            'title'       => true,
            'date'        => true,
            'time'        => true,
            'location'    => true,
            'category'    => true,
            'excerpt'     => false,
            'price'       => false,
            'status'      => true,
            'artists'     => false,
        ),
        
        // Single Event Page - Core Sections
        'single' => array(
            // Main sections
            'sections' => array(
                'meta'        => true,
                'description' => true,
                'artists'     => true,
                'location'    => true,
            ),
            
            // Section headers (can be hidden for cleaner look)
            'headers' => array(
                'artists'         => true,
                'location'        => true,
                'description'     => false,
                'additional_info' => true,
            ),
            
            // Individual meta items
            'meta_items' => array(
                'date'        => true,
                'time'        => true,
                'venue'       => true,
                'category'    => true,
                'price'       => true,
                'status'      => true,
            ),
        ),
        
        // Add-on sections (populated dynamically)
        'addons' => array(),
    );
}

/**
 * Get registered addon display options
 * 
 * @return array Addon display options
 */
function ensemble_get_addon_display_options() {
    $addon_options = array(
        'countdown' => array(
            'label'          => __('Countdown', 'ensemble'),
            'icon'           => 'clock',
            'default_show'   => true,
            'default_header' => false,
        ),
        'tickets' => array(
            'label'          => __('Tickets', 'ensemble'),
            'icon'           => 'tickets-alt',
            'default_show'   => true,
            'default_header' => true,
        ),
        'catalog' => array(
            'label'          => __('Catalog / Menu', 'ensemble'),
            'icon'           => 'list-view',
            'default_show'   => true,
            'default_header' => true,
        ),
        'maps' => array(
            'label'          => __('Map', 'ensemble'),
            'icon'           => 'location-alt',
            'default_show'   => true,
            'default_header' => false,
        ),
        'gallery' => array(
            'label'          => __('Gallery', 'ensemble'),
            'icon'           => 'format-gallery',
            'default_show'   => true,
            'default_header' => true,
        ),
        'social_sharing' => array(
            'label'          => __('Social Sharing', 'ensemble'),
            'icon'           => 'share',
            'default_show'   => true,
            'default_header' => false,
        ),
        'related_events' => array(
            'label'          => __('Related Events', 'ensemble'),
            'icon'           => 'grid-view',
            'default_show'   => true,
            'default_header' => true,
        ),
        'reservations' => array(
            'label'          => __('Reservations', 'ensemble'),
            'icon'           => 'clipboard',
            'default_show'   => true,
            'default_header' => true,
        ),
    );
    
    return apply_filters('ensemble_addon_display_options', $addon_options);
}

/**
 * Get current display settings (merged with defaults)
 * 
 * @return array Current display settings
 */
function ensemble_get_display_settings() {
    static $cached_settings = null;
    
    if ($cached_settings !== null) {
        return $cached_settings;
    }
    
    $defaults = ensemble_get_default_display_settings();
    $saved = get_option('ensemble_display_settings', array());
    
    // Deep merge
    $settings = ensemble_deep_merge($defaults, $saved);
    
    // Add addon settings
    $addon_options = ensemble_get_addon_display_options();
    $saved_addons = isset($saved['addons']) ? $saved['addons'] : array();
    
    foreach ($addon_options as $addon_key => $addon_config) {
        if (!isset($settings['addons'][$addon_key])) {
            $settings['addons'][$addon_key] = array(
                'show'   => $addon_config['default_show'],
                'header' => $addon_config['default_header'],
            );
        }
    }
    
    if (!empty($saved_addons)) {
        foreach ($saved_addons as $addon_key => $addon_settings) {
            if (isset($settings['addons'][$addon_key])) {
                $settings['addons'][$addon_key] = array_merge(
                    $settings['addons'][$addon_key],
                    $addon_settings
                );
            }
        }
    }
    
    $cached_settings = $settings;
    return $settings;
}

/**
 * Deep merge arrays
 */
function ensemble_deep_merge(array $array1, array $array2) {
    $merged = $array1;
    foreach ($array2 as $key => $value) {
        if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
            $merged[$key] = ensemble_deep_merge($merged[$key], $value);
        } else {
            $merged[$key] = $value;
        }
    }
    return $merged;
}

/**
 * Check if element should be shown in cards
 */
function ensemble_show_in_card($element) {
    $settings = ensemble_get_display_settings();
    return !empty($settings['cards'][$element]);
}

/**
 * Check if section should be shown on single page
 */
function ensemble_show_section($section) {
    $settings = ensemble_get_display_settings();
    return !empty($settings['single']['sections'][$section]);
}

/**
 * Check if section header should be shown
 */
function ensemble_show_header($section) {
    $settings = ensemble_get_display_settings();
    return !empty($settings['single']['headers'][$section]);
}

/**
 * Check if meta item should be shown
 */
function ensemble_show_meta($item) {
    $settings = ensemble_get_display_settings();
    return !empty($settings['single']['meta_items'][$item]);
}

/**
 * Check if lineup option should be shown
 * Returns true by default if setting is not set (backwards compatibility)
 */
function ensemble_show_lineup($option) {
    $settings = ensemble_get_display_settings();
    // Default to true if not set (backwards compatibility)
    if (!isset($settings['single']['lineup'][$option])) {
        return true;
    }
    return !empty($settings['single']['lineup'][$option]);
}

/**
 * Get location display mode
 * Returns: 'full', 'name_city', or 'name_only'
 */
function ensemble_get_location_display() {
    $settings = ensemble_get_display_settings();
    if (!isset($settings['single']['location_display'])) {
        return 'full'; // Default to full address
    }
    return $settings['single']['location_display'];
}

/**
 * Format location address based on display settings
 * 
 * @param array $location Location data array with keys: name, address, zip, city
 * @param string $display_mode Optional override for display mode
 * @return array Array with 'name' and 'address_line' keys
 */
function ensemble_format_location_address($location, $display_mode = null) {
    if (empty($location)) {
        return array('name' => '', 'address_line' => '');
    }
    
    $mode = $display_mode ?: ensemble_get_location_display();
    
    // Get display name (combined room/location name or just location name)
    $name = !empty($location['name']) ? $location['name'] : '';
    
    $address_line = '';
    
    switch ($mode) {
        case 'name_only':
            // No address line
            break;
            
        case 'name_city':
            // City only
            if (!empty($location['city'])) {
                $address_line = $location['city'];
            }
            break;
            
        case 'full':
        default:
            // Full address: Street, ZIP City
            $parts = array();
            
            // Street/Address
            if (!empty($location['address'])) {
                $parts[] = $location['address'];
            }
            
            // ZIP + City
            $city_part = '';
            if (!empty($location['zip'])) {
                $city_part = $location['zip'];
            }
            if (!empty($location['city'])) {
                $city_part .= ($city_part ? ' ' : '') . $location['city'];
            }
            if ($city_part) {
                $parts[] = $city_part;
            }
            
            $address_line = implode(', ', $parts);
            break;
    }
    
    return array(
        'name' => $name,
        'address_line' => $address_line,
    );
}

/**
 * Check if addon section should be shown
 */
function ensemble_show_addon($addon) {
    $settings = ensemble_get_display_settings();
    
    if (!ensemble_is_addon_active($addon)) {
        return false;
    }
    
    return !empty($settings['addons'][$addon]['show']);
}

/**
 * Check if addon header should be shown
 */
function ensemble_show_addon_header($addon) {
    $settings = ensemble_get_display_settings();
    
    if (!ensemble_show_addon($addon)) {
        return false;
    }
    
    return !empty($settings['addons'][$addon]['header']);
}

/**
 * Check if addon is active
 */
function ensemble_is_addon_active($addon) {
    $addon_map = array(
        'countdown'      => 'countdown',
        'tickets'        => 'tickets', 
        'maps'           => 'maps',
        'gallery'        => 'gallery-pro',
        'social_sharing' => 'social-sharing',
        'related_events' => 'related-events',
        'reservations'   => 'reservations',
    );
    
    $addon_slug = isset($addon_map[$addon]) ? $addon_map[$addon] : $addon;
    $active_addons = get_option('ensemble_active_addons', array());
    
    return in_array($addon_slug, $active_addons);
}

/**
 * Get SVG icon for social platform
 * 
 * @param string $platform Platform name (spotify, instagram, etc.)
 * @return string SVG markup
 */
function es_get_social_svg($platform) {
    $icons = array(
        'spotify' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.4 0 0 5.4 0 12s5.4 12 12 12 12-5.4 12-12S18.66 0 12 0zm5.521 17.34c-.24.359-.66.48-1.021.24-2.82-1.74-6.36-2.101-10.561-1.141-.418.122-.779-.179-.899-.539-.12-.421.18-.78.54-.9 4.56-1.021 8.52-.6 11.64 1.32.42.18.479.659.301 1.02zm1.44-3.3c-.301.42-.841.6-1.262.3-3.239-1.98-8.159-2.58-11.939-1.38-.479.12-1.02-.12-1.14-.6-.12-.48.12-1.021.6-1.141C9.6 9.9 15 10.561 18.72 12.84c.361.181.54.78.241 1.2zm.12-3.36C15.24 8.4 8.82 8.16 5.16 9.301c-.6.179-1.2-.181-1.38-.721-.18-.601.18-1.2.72-1.381 4.26-1.26 11.28-1.02 15.721 1.621.539.3.719 1.02.419 1.56-.299.421-1.02.599-1.559.3z"/></svg>',
        
        'soundcloud' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M1.175 12.225c-.051 0-.094.046-.101.1l-.233 2.154.233 2.105c.007.058.05.098.101.098.05 0 .09-.04.099-.098l.255-2.105-.27-2.154c-.009-.06-.052-.1-.102-.1m-.899.828c-.06 0-.091.037-.104.094L0 14.479l.165 1.308c.014.057.045.094.09.094s.089-.037.099-.094l.19-1.308-.19-1.334c-.01-.057-.045-.09-.09-.09m1.83-1.229c-.061 0-.12.045-.12.104l-.21 2.563.225 2.458c0 .06.045.104.106.104.061 0 .12-.044.12-.104l.24-2.458-.24-2.563c0-.06-.045-.104-.12-.104m.945-.089c-.075 0-.135.06-.15.135l-.193 2.64.21 2.544c.016.077.075.138.149.138.075 0 .135-.061.15-.138l.225-2.544-.225-2.64c-.016-.075-.075-.135-.15-.135m1.065.202c-.09 0-.149.075-.165.165l-.176 2.459.176 2.4c.016.09.075.164.165.164.09 0 .164-.074.164-.164l.21-2.4-.21-2.459c0-.09-.074-.165-.164-.165m7.409.074c-.196 0-.359.03-.524.074-.166-1.935-1.783-3.449-3.749-3.449-.481 0-.945.09-1.365.251-.165.06-.21.12-.21.24v6.815c0 .12.09.224.21.24h5.638c1.425 0 2.58-1.155 2.58-2.58 0-1.44-1.155-2.595-2.58-2.595"/></svg>',
        
        'youtube' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>',
        
        'instagram' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C8.74 0 8.333.015 7.053.072 5.775.132 4.905.333 4.14.63c-.789.306-1.459.717-2.126 1.384S.935 3.35.63 4.14C.333 4.905.131 5.775.072 7.053.012 8.333 0 8.74 0 12s.015 3.667.072 4.947c.06 1.277.261 2.148.558 2.913.306.788.717 1.459 1.384 2.126.667.666 1.336 1.079 2.126 1.384.766.296 1.636.499 2.913.558C8.333 23.988 8.74 24 12 24s3.667-.015 4.947-.072c1.277-.06 2.148-.262 2.913-.558.788-.306 1.459-.718 2.126-1.384.666-.667 1.079-1.335 1.384-2.126.296-.765.499-1.636.558-2.913.06-1.28.072-1.687.072-4.947s-.015-3.667-.072-4.947c-.06-1.277-.262-2.149-.558-2.913-.306-.789-.718-1.459-1.384-2.126C21.319 1.347 20.651.935 19.86.63c-.765-.297-1.636-.499-2.913-.558C15.667.012 15.26 0 12 0zm0 2.16c3.203 0 3.585.016 4.85.071 1.17.055 1.805.249 2.227.415.562.217.96.477 1.382.896.419.42.679.819.896 1.381.164.422.36 1.057.413 2.227.057 1.266.07 1.646.07 4.85s-.015 3.585-.074 4.85c-.061 1.17-.256 1.805-.421 2.227-.224.562-.479.96-.899 1.382-.419.419-.824.679-1.38.896-.42.164-1.065.36-2.235.413-1.274.057-1.649.07-4.859.07-3.211 0-3.586-.015-4.859-.074-1.171-.061-1.816-.256-2.236-.421-.569-.224-.96-.479-1.379-.899-.421-.419-.69-.824-.9-1.38-.165-.42-.359-1.065-.42-2.235-.045-1.26-.061-1.649-.061-4.844 0-3.196.016-3.586.061-4.861.061-1.17.255-1.814.42-2.234.21-.57.479-.96.9-1.381.419-.419.81-.689 1.379-.898.42-.166 1.051-.361 2.221-.421 1.275-.045 1.65-.06 4.859-.06l.045.03zm0 3.678c-3.405 0-6.162 2.76-6.162 6.162 0 3.405 2.76 6.162 6.162 6.162 3.405 0 6.162-2.76 6.162-6.162 0-3.405-2.76-6.162-6.162-6.162zM12 16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm7.846-10.405c0 .795-.646 1.44-1.44 1.44-.795 0-1.44-.646-1.44-1.44 0-.794.646-1.439 1.44-1.439.793-.001 1.44.645 1.44 1.439z"/></svg>',
        
        'facebook' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>',
        
        'twitter' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>',
        
        'bandcamp' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M0 18.75l7.437-13.5H24l-7.438 13.5H0z"/></svg>',
        
        'mixcloud' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M2.462 8.596l1.733 6.808h.29l1.733-6.808h2.02v8.345H6.927V9.62l-1.82 7.32H3.594l-1.82-7.32v7.32H.462V8.596h2zm9.903 0v6.596c0 .784-.306 1.09-1.09 1.09H8.47v-1.36h2.02c.202 0 .282-.08.282-.283V8.596h1.593zm1.23 0h6.225v1.41h-2.316v6.935h-1.593V10.007h-2.316v-1.41zm6.545 0h1.593v8.345H20.14V8.596z"/></svg>',
        
        'globe' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>',
        
        'website' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>',
    );
    
    return isset($icons[$platform]) ? $icons[$platform] : '';
}

/**
 * Render opening hours HTML
 * 
 * @param int $location_id Location ID
 * @param array $args Display arguments
 * @return string HTML output
 */
function es_render_opening_hours($location_id, $args = array()) {
    $defaults = array(
        'show_badge'      => true,
        'show_note'       => true,
        'compact'         => false,
        'wrapper_class'   => 'ensemble-opening-hours',
    );
    $args = wp_parse_args($args, $defaults);
    
    // Get opening hours data
    $has_opening_hours = get_post_meta($location_id, 'has_opening_hours', true);
    if (!$has_opening_hours) {
        return '';
    }
    
    $opening_hours = get_post_meta($location_id, 'opening_hours', true);
    $opening_hours_note = get_post_meta($location_id, 'opening_hours_note', true);
    
    if (empty($opening_hours) || !is_array($opening_hours)) {
        return '';
    }
    
    // Day labels
    $day_labels = array(
        'monday'    => __('Monday', 'ensemble'),
        'tuesday'   => __('Tuesday', 'ensemble'),
        'wednesday' => __('Wednesday', 'ensemble'),
        'thursday'  => __('Thursday', 'ensemble'),
        'friday'    => __('Friday', 'ensemble'),
        'saturday'  => __('Saturday', 'ensemble'),
        'sunday'    => __('Sunday', 'ensemble'),
    );
    
    // Short day labels for compact mode
    $day_labels_short = array(
        'monday'    => __('Mon', 'ensemble'),
        'tuesday'   => __('Tue', 'ensemble'),
        'wednesday' => __('Wed', 'ensemble'),
        'thursday'  => __('Thu', 'ensemble'),
        'friday'    => __('Fri', 'ensemble'),
        'saturday'  => __('Sat', 'ensemble'),
        'sunday'    => __('Sun', 'ensemble'),
    );
    
    $labels = $args['compact'] ? $day_labels_short : $day_labels;
    
    // Check if currently open
    $is_open = es_is_location_open_now($opening_hours);
    
    ob_start();
    ?>
    <div class="<?php echo esc_attr($args['wrapper_class']); ?>">
        <?php if ($args['show_badge'] && $is_open !== null): ?>
            <div class="ensemble-open-badge <?php echo $is_open ? 'is-open' : 'is-closed'; ?>">
                <span class="ensemble-badge-dot"></span>
                <?php echo $is_open ? __('Open Now', 'ensemble') : __('Closed', 'ensemble'); ?>
            </div>
        <?php endif; ?>
        
        <div class="ensemble-hours-grid">
            <?php foreach ($opening_hours as $day => $hours): ?>
                <?php if (!isset($labels[$day])) continue; ?>
                <div class="ensemble-hours-row <?php echo !empty($hours['closed']) ? 'is-closed' : ''; ?>">
                    <span class="ensemble-hours-day"><?php echo esc_html($labels[$day]); ?></span>
                    <span class="ensemble-hours-time">
                        <?php if (!empty($hours['closed'])): ?>
                            <?php _e('Closed', 'ensemble'); ?>
                        <?php elseif (!empty($hours['open']) && !empty($hours['close'])): ?>
                            <?php echo esc_html($hours['open']); ?> – <?php echo esc_html($hours['close']); ?>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if ($args['show_note'] && !empty($opening_hours_note)): ?>
            <div class="ensemble-hours-note">
                <?php echo esc_html($opening_hours_note); ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Check if location is currently open
 * 
 * @param array $opening_hours Opening hours data
 * @return bool|null True if open, false if closed, null if no data
 */
function es_is_location_open_now($opening_hours) {
    if (empty($opening_hours) || !is_array($opening_hours)) {
        return null;
    }
    
    // Get current day and time (use WordPress timezone)
    $current_time = current_time('timestamp');
    $current_day = strtolower(date('l', $current_time));
    $current_time_str = date('H:i', $current_time);
    
    // Check if current day exists in data
    if (!isset($opening_hours[$current_day])) {
        return null;
    }
    
    $today = $opening_hours[$current_day];
    
    // Check if closed
    if (!empty($today['closed'])) {
        return false;
    }
    
    // Check if times are set
    if (empty($today['open']) || empty($today['close'])) {
        return null;
    }
    
    $open_time = $today['open'];
    $close_time = $today['close'];
    
    // Handle times that go past midnight (e.g., 22:00 - 03:00)
    if ($close_time < $open_time) {
        // We're open if: current >= open OR current < close
        return ($current_time_str >= $open_time || $current_time_str < $close_time);
    }
    
    // Normal case: open and close on same day
    return ($current_time_str >= $open_time && $current_time_str < $close_time);
}

/**
 * ========================================
 * EVENT COUNT FUNCTIONS
 * ========================================
 */

/**
 * Get event count for an artist
 * 
 * Counts how many events are associated with a specific artist.
 * Supports all meta key formats (legacy, wizard, ACF).
 * Results are cached for performance.
 * 
 * @since 2.9.0
 * @param int  $artist_id     The artist post ID.
 * @param bool $upcoming_only Only count upcoming events (default true).
 * @param bool $use_cache     Use transient cache (default true).
 * @return int Number of events.
 */
function ensemble_get_artist_event_count($artist_id, $upcoming_only = true, $use_cache = true) {
    if (empty($artist_id)) {
        return 0;
    }
    
    $artist_id = absint($artist_id);
    $cache_key = 'ensemble_artist_events_' . $artist_id . ($upcoming_only ? '_upcoming' : '_all');
    
    // Check cache
    if ($use_cache) {
        $cached = get_transient($cache_key);
        if (false !== $cached) {
            return (int) $cached;
        }
    }
    
    // Build meta query for all possible meta key formats
    $meta_query = array(
        'relation' => 'OR',
        // Legacy format: event_artist (single ID or serialized array)
        array(
            'key'     => 'event_artist',
            'value'   => $artist_id,
            'compare' => '=',
        ),
        array(
            'key'     => 'event_artist',
            'value'   => sprintf(':"%d"', $artist_id),
            'compare' => 'LIKE',
        ),
        array(
            'key'     => 'event_artist',
            'value'   => sprintf('i:%d;', $artist_id),
            'compare' => 'LIKE',
        ),
        // Wizard format
        array(
            'key'     => 'es_event_artist',
            'value'   => $artist_id,
            'compare' => '=',
        ),
        array(
            'key'     => 'es_event_artist',
            'value'   => sprintf(':"%d"', $artist_id),
            'compare' => 'LIKE',
        ),
        // ACF format (post object stores ID directly)
        array(
            'key'     => '_event_artist',
            'value'   => $artist_id,
            'compare' => '=',
        ),
    );
    
    // Build date query for upcoming events
    $date_query = array();
    if ($upcoming_only) {
        $today = current_time('Y-m-d');
        
        // We need to check multiple date meta keys
        $meta_query[] = array(
            'relation' => 'OR',
            array(
                'key'     => 'event_date',
                'value'   => $today,
                'compare' => '>=',
                'type'    => 'DATE',
            ),
            array(
                'key'     => 'es_event_start_date',
                'value'   => $today,
                'compare' => '>=',
                'type'    => 'DATE',
            ),
            array(
                'key'     => '_event_date',
                'value'   => $today,
                'compare' => '>=',
                'type'    => 'DATE',
            ),
        );
    }
    
    $args = array(
        'post_type'      => ensemble_get_post_type(),
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_query'     => $meta_query,
        'no_found_rows'  => true,
    );
    
    $events = get_posts($args);
    $count = count($events);
    
    // Cache for 1 hour
    if ($use_cache) {
        set_transient($cache_key, $count, HOUR_IN_SECONDS);
    }
    
    return $count;
}

/**
 * Get event count for a location
 * 
 * Counts how many events are associated with a specific location.
 * Supports all meta key formats (legacy, wizard, ACF).
 * Results are cached for performance.
 * 
 * @since 2.9.0
 * @param int  $location_id   The location post ID.
 * @param bool $upcoming_only Only count upcoming events (default true).
 * @param bool $use_cache     Use transient cache (default true).
 * @return int Number of events.
 */
function ensemble_get_location_event_count($location_id, $upcoming_only = true, $use_cache = true) {
    if (empty($location_id)) {
        return 0;
    }
    
    $location_id = absint($location_id);
    $cache_key = 'ensemble_location_events_' . $location_id . ($upcoming_only ? '_upcoming' : '_all');
    
    // Check cache
    if ($use_cache) {
        $cached = get_transient($cache_key);
        if (false !== $cached) {
            return (int) $cached;
        }
    }
    
    // Build meta query for all possible meta key formats
    $meta_query = array(
        'relation' => 'OR',
        // Legacy format
        array(
            'key'     => 'event_location',
            'value'   => $location_id,
            'compare' => '=',
        ),
        // Wizard format
        array(
            'key'     => 'es_event_location',
            'value'   => $location_id,
            'compare' => '=',
        ),
        // ACF format
        array(
            'key'     => '_event_location',
            'value'   => $location_id,
            'compare' => '=',
        ),
    );
    
    // Build date meta query for upcoming events
    if ($upcoming_only) {
        $today = current_time('Y-m-d');
        
        $meta_query[] = array(
            'relation' => 'OR',
            array(
                'key'     => 'event_date',
                'value'   => $today,
                'compare' => '>=',
                'type'    => 'DATE',
            ),
            array(
                'key'     => 'es_event_start_date',
                'value'   => $today,
                'compare' => '>=',
                'type'    => 'DATE',
            ),
            array(
                'key'     => '_event_date',
                'value'   => $today,
                'compare' => '>=',
                'type'    => 'DATE',
            ),
        );
    }
    
    $args = array(
        'post_type'      => ensemble_get_post_type(),
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_query'     => $meta_query,
        'no_found_rows'  => true,
    );
    
    $events = get_posts($args);
    $count = count($events);
    
    // Cache for 1 hour
    if ($use_cache) {
        set_transient($cache_key, $count, HOUR_IN_SECONDS);
    }
    
    return $count;
}

/**
 * Clear artist event count cache
 * 
 * Call this when an event is saved/deleted to keep counts accurate.
 * 
 * @since 2.9.0
 * @param int $artist_id The artist post ID.
 * @return void
 */
function ensemble_clear_artist_event_count_cache($artist_id) {
    if (empty($artist_id)) {
        return;
    }
    
    $artist_id = absint($artist_id);
    delete_transient('ensemble_artist_events_' . $artist_id . '_upcoming');
    delete_transient('ensemble_artist_events_' . $artist_id . '_all');
}

/**
 * Clear location event count cache
 * 
 * Call this when an event is saved/deleted to keep counts accurate.
 * 
 * @since 2.9.0
 * @param int $location_id The location post ID.
 * @return void
 */
function ensemble_clear_location_event_count_cache($location_id) {
    if (empty($location_id)) {
        return;
    }
    
    $location_id = absint($location_id);
    delete_transient('ensemble_location_events_' . $location_id . '_upcoming');
    delete_transient('ensemble_location_events_' . $location_id . '_all');
}

/**
 * Auto-clear event count caches on event save
 * 
 * Hooked to save_post to automatically invalidate caches.
 * Uses the configured event post type.
 * 
 * @since 2.9.0
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post object.
 * @param bool    $update  Whether this is an update.
 * @return void
 */
function ensemble_clear_event_count_caches_on_save($post_id, $post = null, $update = false) {
    // Skip autosaves and revisions
    if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
        return;
    }
    
    // Only process for configured event post type
    $event_post_type = ensemble_get_post_type();
    if (get_post_type($post_id) !== $event_post_type) {
        return;
    }
    
    // Clear artist cache
    $artist_id = ensemble_get_event_meta($post_id, 'artist');
    if (!empty($artist_id)) {
        // Handle array of artists
        if (is_array($artist_id)) {
            foreach ($artist_id as $id) {
                ensemble_clear_artist_event_count_cache($id);
            }
        } else {
            ensemble_clear_artist_event_count_cache($artist_id);
        }
    }
    
    // Clear location cache
    $location_id = ensemble_get_event_meta($post_id, 'location');
    if (!empty($location_id)) {
        ensemble_clear_location_event_count_cache($location_id);
    }
    
    // Log in debug mode
    if (function_exists('ensemble_log')) {
        ensemble_log('Event count caches cleared', array(
            'event_id'    => $post_id,
            'artist_id'   => $artist_id,
            'location_id' => $location_id,
        ));
    }
}
add_action('save_post', 'ensemble_clear_event_count_caches_on_save', 20, 3);

/**
 * Get formatted event count display
 * 
 * Returns a human-readable string for event count.
 * 
 * @since 2.9.0
 * @param int    $count         The event count.
 * @param string $singular_text Text for singular (default: 'Event').
 * @param string $plural_text   Text for plural (default: 'Events').
 * @return string Formatted count string.
 */
function ensemble_format_event_count($count, $singular_text = null, $plural_text = null) {
    if ($singular_text === null) {
        $singular_text = __('Event', 'ensemble');
    }
    if ($plural_text === null) {
        $plural_text = __('Events', 'ensemble');
    }
    
    return sprintf(
        _n('%d ' . $singular_text, '%d ' . $plural_text, $count, 'ensemble'),
        $count
    );
}

/**
 * ========================================
 * FRONTEND PERFORMANCE OPTIMIZATIONS
 * ========================================
 */

/**
 * Add loading="lazy" to images
 * 
 * Filters post thumbnails and attachment images to add lazy loading.
 * WordPress 5.5+ has native lazy loading, this ensures consistency.
 * 
 * @since 2.9.0
 * @param string $html Image HTML.
 * @return string Modified HTML.
 */
function ensemble_add_lazy_loading( $html ) {
    // Skip if already has loading attribute
    if ( strpos( $html, 'loading=' ) !== false ) {
        return $html;
    }
    
    // Skip if this is an inline/base64 image
    if ( strpos( $html, 'data:image' ) !== false ) {
        return $html;
    }
    
    // Add loading="lazy"
    return str_replace( '<img', '<img loading="lazy"', $html );
}
add_filter( 'post_thumbnail_html', 'ensemble_add_lazy_loading', 15 );
add_filter( 'wp_get_attachment_image', 'ensemble_add_lazy_loading', 15 );

/**
 * Add decoding="async" to images
 * 
 * Allows the browser to decode images asynchronously.
 * 
 * @since 2.9.0
 * @param array $attr Image attributes.
 * @return array Modified attributes.
 */
function ensemble_add_async_decoding( $attr ) {
    if ( ! isset( $attr['decoding'] ) ) {
        $attr['decoding'] = 'async';
    }
    return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'ensemble_add_async_decoding', 10, 1 );

/**
 * Preload critical resources
 * 
 * Adds preload hints for fonts and critical CSS.
 * Call this in wp_head hook.
 * 
 * @since 2.9.0
 * @return void
 */
function ensemble_preload_resources() {
    // Only on frontend
    if ( is_admin() ) {
        return;
    }
    
    // Preload the active layout CSS
    if ( class_exists( 'ES_Layout_Sets' ) ) {
        $active_set = ES_Layout_Sets::get_active_set();
        $set_data = ES_Layout_Sets::get_set_data( $active_set );
        
        if ( ! empty( $set_data['path'] ) ) {
            $style_path = $set_data['path'] . '/style.css';
            
            if ( file_exists( $style_path ) ) {
                if ( defined( 'ENSEMBLE_PLUGIN_DIR' ) && strpos( $style_path, ENSEMBLE_PLUGIN_DIR ) === 0 ) {
                    $style_url = str_replace( ENSEMBLE_PLUGIN_DIR, ENSEMBLE_PLUGIN_URL, $style_path );
                } else {
                    $style_url = str_replace( WP_CONTENT_DIR, WP_CONTENT_URL, $style_path );
                }
                
                echo '<link rel="preload" href="' . esc_url( $style_url ) . '" as="style">' . "\n";
            }
        }
    }
    
    // Preload base CSS
    if ( defined( 'ENSEMBLE_PLUGIN_URL' ) ) {
        echo '<link rel="preload" href="' . esc_url( ENSEMBLE_PLUGIN_URL . 'assets/css/layouts/ensemble-base.css' ) . '" as="style">' . "\n";
    }
}
add_action( 'wp_head', 'ensemble_preload_resources', 1 );

/**
 * Add resource hints for external resources
 * 
 * @since 2.9.0
 * @param array  $hints          URLs to print for resource hints.
 * @param string $relation_type  The relation type (dns-prefetch, preconnect, etc).
 * @return array Modified hints.
 */
function ensemble_resource_hints( $hints, $relation_type ) {
    // DNS prefetch for common CDNs used by maps, fonts, etc.
    if ( 'dns-prefetch' === $relation_type ) {
        // Google Fonts (if used)
        $hints[] = '//fonts.googleapis.com';
        $hints[] = '//fonts.gstatic.com';
        
        // Google Maps (if maps addon is active)
        if ( class_exists( 'ES_Maps_Addon' ) ) {
            $hints[] = '//maps.googleapis.com';
        }
    }
    
    // Preconnect for critical external resources
    if ( 'preconnect' === $relation_type ) {
        $hints[] = array(
            'href' => 'https://fonts.gstatic.com',
            'crossorigin' => true,
        );
    }
    
    return $hints;
}
add_filter( 'wp_resource_hints', 'ensemble_resource_hints', 10, 2 );

/**
 * Optimize image srcset sizes attribute
 * 
 * @since 2.9.0
 * @param string $sizes         Sizes attribute value.
 * @param array  $size          Requested size array.
 * @param string $image_src     Image source URL.
 * @param array  $image_meta    Image metadata.
 * @param int    $attachment_id Attachment ID.
 * @return string Optimized sizes attribute.
 */
function ensemble_optimize_image_sizes( $sizes, $size, $image_src, $image_meta, $attachment_id ) {
    // For card images in grids, use more appropriate sizes
    // This helps the browser choose the right image size
    if ( is_array( $size ) && $size[0] <= 400 ) {
        return '(max-width: 576px) 100vw, (max-width: 768px) 50vw, 33vw';
    }
    
    return $sizes;
}
add_filter( 'wp_calculate_image_sizes', 'ensemble_optimize_image_sizes', 10, 5 );

// ============================================
// Duration Type System (Festival/Exhibition)
// ============================================

/**
 * Get the duration type of an event
 * 
 * @since 2.9.6
 * @param int $event_id Event ID
 * @return string Duration type: 'single', 'multi_day', or 'permanent'
 */
function ensemble_get_duration_type( $event_id ) {
    $duration_type = get_post_meta( $event_id, ES_Meta_Keys::EVENT_DURATION_TYPE, true );
    return ! empty( $duration_type ) ? $duration_type : 'single';
}

/**
 * Check if event is multi-day (festival, exhibition, etc.)
 * 
 * @since 2.9.6
 * @param int $event_id Event ID
 * @return bool
 */
function ensemble_is_multi_day_event( $event_id ) {
    return ensemble_get_duration_type( $event_id ) === 'multi_day';
}

/**
 * Check if event is permanent (ongoing exhibition, etc.)
 * 
 * @since 2.9.6
 * @param int $event_id Event ID
 * @return bool
 */
function ensemble_is_permanent_event( $event_id ) {
    return ensemble_get_duration_type( $event_id ) === 'permanent';
}

/**
 * Get the end date of a multi-day event
 * 
 * @since 2.9.6
 * @param int $event_id Event ID
 * @return string|null End date (Y-m-d format) or null
 */
function ensemble_get_event_end_date( $event_id ) {
    $end_date = get_post_meta( $event_id, ES_Meta_Keys::EVENT_DATE_END, true );
    return ! empty( $end_date ) ? $end_date : null;
}

/**
 * Check if event has child events (is a parent)
 * 
 * @since 2.9.6
 * @param int $event_id Event ID
 * @return bool
 */
function ensemble_event_has_children( $event_id ) {
    return get_post_meta( $event_id, ES_Meta_Keys::EVENT_HAS_CHILDREN, true ) === '1';
}

/**
 * Get the parent event ID of a child event
 * 
 * @since 2.9.6
 * @param int $event_id Event ID
 * @return int|null Parent event ID or null
 */
function ensemble_get_parent_event_id( $event_id ) {
    $parent_id = get_post_meta( $event_id, ES_Meta_Keys::EVENT_PARENT_ID, true );
    return ! empty( $parent_id ) ? intval( $parent_id ) : null;
}

/**
 * Get child events of a parent event
 * 
 * @since 2.9.6
 * @param int $parent_id Parent event ID
 * @param array $args Optional query arguments
 * @return WP_Post[] Array of child event posts
 */
function ensemble_get_child_events( $parent_id, $args = array() ) {
    $defaults = array(
        'post_type'      => ensemble_get_post_type(),
        'posts_per_page' => -1,
        'meta_query'     => array(
            array(
                'key'     => ES_Meta_Keys::EVENT_PARENT_ID,
                'value'   => $parent_id,
                'compare' => '=',
            ),
        ),
        'orderby'        => 'meta_value',
        'meta_key'       => ES_Meta_Keys::get( 'date' ),
        'order'          => 'ASC',
    );
    
    $args = wp_parse_args( $args, $defaults );
    return get_posts( $args );
}

/**
 * Format event date based on duration type
 * 
 * Handles different display formats:
 * - Single: "15. Juni 2025"
 * - Multi-day: "6. - 8. Juni 2025" or "1. März - 31. Mai 2025"
 * - Permanent: "Dauerausstellung" or "Seit 2020"
 * 
 * @since 2.9.6
 * @param int $event_id Event ID
 * @param string $format Date format (uses WordPress settings if empty)
 * @param bool $include_year Whether to include year
 * @return string Formatted date string
 */
function ensemble_format_event_date( $event_id, $format = '', $include_year = true ) {
    $duration_type = ensemble_get_duration_type( $event_id );
    $start_date    = ensemble_get_event_meta( $event_id, 'date' );
    $end_date      = ensemble_get_event_end_date( $event_id );
    
    // Default format from WordPress settings
    if ( empty( $format ) ) {
        $format = get_option( 'date_format', 'j. F Y' );
    }
    
    // Format without year for range comparison
    $format_no_year = preg_replace( '/[,\s]*Y|Y[,\s]*/i', '', $format );
    $format_no_year = trim( $format_no_year, ' ,-.' );
    
    switch ( $duration_type ) {
        case 'permanent':
            if ( ! empty( $start_date ) ) {
                $start_timestamp = strtotime( $start_date );
                $year = date_i18n( 'Y', $start_timestamp );
                return sprintf( __( 'Seit %s', 'ensemble' ), $year );
            }
            return __( 'Dauerausstellung', 'ensemble' );
            
        case 'multi_day':
            if ( empty( $start_date ) ) {
                return '';
            }
            
            $start_timestamp = strtotime( $start_date );
            
            if ( empty( $end_date ) ) {
                // Only start date available
                return date_i18n( $format, $start_timestamp );
            }
            
            $end_timestamp = strtotime( $end_date );
            
            // Check if same month and year
            $same_month = date( 'm-Y', $start_timestamp ) === date( 'm-Y', $end_timestamp );
            $same_year  = date( 'Y', $start_timestamp ) === date( 'Y', $end_timestamp );
            
            if ( $same_month ) {
                // Same month: "6. - 8. Juni 2025"
                $start_day = date_i18n( 'j.', $start_timestamp );
                $end_formatted = date_i18n( $format, $end_timestamp );
                return $start_day . ' – ' . $end_formatted;
            } elseif ( $same_year ) {
                // Same year: "1. März - 31. Mai 2025"
                $start_formatted = date_i18n( $format_no_year, $start_timestamp );
                $end_formatted = date_i18n( $format, $end_timestamp );
                return $start_formatted . ' – ' . $end_formatted;
            } else {
                // Different years: "15. Dez 2024 - 15. Jan 2025"
                $start_formatted = date_i18n( $format, $start_timestamp );
                $end_formatted = date_i18n( $format, $end_timestamp );
                return $start_formatted . ' – ' . $end_formatted;
            }
            
        case 'single':
        default:
            if ( empty( $start_date ) ) {
                return '';
            }
            return date_i18n( $format, strtotime( $start_date ) );
    }
}

/**
 * Get duration type label
 * 
 * @since 2.9.6
 * @param string $duration_type Duration type key
 * @return string Translated label
 */
function ensemble_get_duration_type_label( $duration_type ) {
    $labels = array(
        'single'    => __( 'Single Event', 'ensemble' ),
        'multi_day' => __( 'Multi-Day', 'ensemble' ),
        'permanent' => __( 'Permanent', 'ensemble' ),
    );
    
    return isset( $labels[ $duration_type ] ) ? $labels[ $duration_type ] : $labels['single'];
}

/**
 * Check if event is currently active (running)
 * 
 * For single events: true if event date is today
 * For multi-day: true if today is within date range
 * For permanent: always true (unless not started yet)
 * 
 * @since 2.9.6
 * @param int $event_id Event ID
 * @return bool
 */
function ensemble_is_event_active( $event_id ) {
    $duration_type = ensemble_get_duration_type( $event_id );
    $start_date    = ensemble_get_event_meta( $event_id, 'date' );
    $today         = date( 'Y-m-d' );
    
    if ( empty( $start_date ) ) {
        return $duration_type === 'permanent'; // Permanent without date is always active
    }
    
    switch ( $duration_type ) {
        case 'permanent':
            return $start_date <= $today;
            
        case 'multi_day':
            $end_date = ensemble_get_event_end_date( $event_id );
            if ( empty( $end_date ) ) {
                return $start_date <= $today;
            }
            return $start_date <= $today && $today <= $end_date;
            
        case 'single':
        default:
            return $start_date === $today;
    }
}

/**
 * Check if event is upcoming
 * 
 * @since 2.9.6
 * @param int $event_id Event ID
 * @return bool
 */
function ensemble_is_event_upcoming( $event_id ) {
    $duration_type = ensemble_get_duration_type( $event_id );
    $start_date    = ensemble_get_event_meta( $event_id, 'date' );
    $today         = date( 'Y-m-d' );
    
    if ( $duration_type === 'permanent' ) {
        return false; // Permanent events are not "upcoming"
    }
    
    if ( empty( $start_date ) ) {
        return false;
    }
    
    return $start_date > $today;
}

/**
 * Check if event is past
 * 
 * @since 2.9.6
 * @param int $event_id Event ID
 * @return bool
 */
function ensemble_is_event_past( $event_id ) {
    $duration_type = ensemble_get_duration_type( $event_id );
    $today         = date( 'Y-m-d' );
    
    if ( $duration_type === 'permanent' ) {
        return false; // Permanent events are never "past"
    }
    
    // For multi-day events, check end date
    if ( $duration_type === 'multi_day' ) {
        $end_date = ensemble_get_event_end_date( $event_id );
        if ( ! empty( $end_date ) ) {
            return $end_date < $today;
        }
    }
    
    // Check start date
    $start_date = ensemble_get_event_meta( $event_id, 'date' );
    if ( empty( $start_date ) ) {
        return false;
    }
    
    return $start_date < $today;
}

/**
 * Get events by duration type
 * 
 * @since 2.9.6
 * @param string $duration_type Duration type: 'single', 'multi_day', 'permanent', or 'all'
 * @param array $args Optional query arguments
 * @return WP_Post[] Array of event posts
 */
function ensemble_get_events_by_duration_type( $duration_type = 'all', $args = array() ) {
    $defaults = array(
        'post_type'      => ensemble_get_post_type(),
        'posts_per_page' => -1,
        'orderby'        => 'meta_value',
        'meta_key'       => ES_Meta_Keys::get( 'date' ),
        'order'          => 'ASC',
    );
    
    // Add duration type filter if not 'all'
    if ( $duration_type !== 'all' && in_array( $duration_type, array( 'single', 'multi_day', 'permanent' ) ) ) {
        $defaults['meta_query'] = array(
            array(
                'key'     => ES_Meta_Keys::EVENT_DURATION_TYPE,
                'value'   => $duration_type,
                'compare' => '=',
            ),
        );
    }
    
    $args = wp_parse_args( $args, $defaults );
    return get_posts( $args );
}

/**
 * Check if event has any tickets (local or global)
 * 
 * Use this in templates to check if ticket section should be shown.
 * Includes both event-specific tickets AND global tickets from addon settings.
 * 
 * @param int $event_id Event post ID
 * @return bool True if event has any tickets
 * @since 2.9.1
 */
function ensemble_has_tickets($event_id) {
    // Check local event tickets
    $local_tickets = get_post_meta($event_id, '_ensemble_tickets', true);
    if (is_array($local_tickets) && !empty($local_tickets)) {
        return true;
    }
    
    // Check global tickets from addon settings
    $addon_settings = get_option('ensemble_addon_tickets_settings', array());
    $global_tickets = isset($addon_settings['global_tickets']) ? $addon_settings['global_tickets'] : array();
    
    if (!is_array($global_tickets) || empty($global_tickets)) {
        return false;
    }
    
    // Check for excluded global tickets
    $excluded = get_post_meta($event_id, '_ensemble_excluded_global_tickets', true);
    if (!is_array($excluded)) {
        $excluded = array();
    }
    
    // Check if at least one global ticket is not excluded
    foreach ($global_tickets as $ticket) {
        $ticket_id = isset($ticket['id']) ? $ticket['id'] : '';
        if (!in_array($ticket_id, $excluded)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Get all tickets for an event (local + global)
 * 
 * Returns merged array of event-specific and global tickets.
 * Global tickets are marked with is_global = true.
 * 
 * @param int $event_id Event post ID
 * @return array Array of ticket data
 * @since 2.9.1
 */
function ensemble_get_all_tickets($event_id) {
    $tickets = array();
    
    // Get local event tickets
    $local_tickets = get_post_meta($event_id, '_ensemble_tickets', true);
    if (is_array($local_tickets)) {
        foreach ($local_tickets as $ticket) {
            $ticket['is_global'] = false;
            $tickets[] = $ticket;
        }
    }
    
    // Get global tickets
    $addon_settings = get_option('ensemble_addon_tickets_settings', array());
    $global_tickets = isset($addon_settings['global_tickets']) ? $addon_settings['global_tickets'] : array();
    
    if (is_array($global_tickets) && !empty($global_tickets)) {
        // Get excluded tickets for this event
        $excluded = get_post_meta($event_id, '_ensemble_excluded_global_tickets', true);
        if (!is_array($excluded)) {
            $excluded = array();
        }
        
        // Add non-excluded global tickets
        foreach ($global_tickets as $ticket) {
            $ticket_id = isset($ticket['id']) ? $ticket['id'] : '';
            if (!in_array($ticket_id, $excluded)) {
                $ticket['is_global'] = true;
                $tickets[] = $ticket;
            }
        }
    }
    
    // Sort by sort_order
    usort($tickets, function($a, $b) {
        return (isset($a['sort_order']) ? $a['sort_order'] : 0) - (isset($b['sort_order']) ? $b['sort_order'] : 0);
    });
    
    return $tickets;
}
