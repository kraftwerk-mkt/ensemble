<?php
/**
 * Meta Keys Constants
 * 
 * Central definition of all meta keys used by Ensemble.
 * Supports multiple formats: Legacy, Wizard (es_), and custom ACF mappings.
 * 
 * USAGE:
 * Instead of hardcoding meta keys like 'event_date' or 'es_event_start_date',
 * use ES_Meta_Keys::get('date') which returns the correct key for the installation.
 * 
 * @package Ensemble
 * @since 2.9.4
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ES_Meta_Keys
 * 
 * Provides centralized meta key management with support for:
 * - Legacy format: event_date, event_location, event_artist
 * - Wizard format: es_event_start_date, es_event_location, es_event_artist
 * - Custom ACF mappings via Field Mapping settings
 */
class ES_Meta_Keys {
    
    /**
     * Format constants
     */
    const FORMAT_LEGACY = 'legacy';
    const FORMAT_WIZARD = 'wizard';
    const FORMAT_CUSTOM = 'custom';
    
    /**
     * ========================================
     * EVENT META KEYS
     * ========================================
     */
    
    // Date & Time
    const EVENT_DATE_LEGACY       = 'event_date';
    const EVENT_DATE_WIZARD       = 'es_event_start_date';
    const EVENT_TIME_LEGACY       = 'event_time';
    const EVENT_TIME_WIZARD       = 'es_event_start_time';
    const EVENT_END_DATE_LEGACY   = 'event_end_date';
    const EVENT_END_DATE_WIZARD   = 'es_event_end_date';
    const EVENT_END_TIME_LEGACY   = 'event_end_time';
    const EVENT_END_TIME_WIZARD   = 'es_event_end_time';
    
    // Relationships
    const EVENT_LOCATION_LEGACY   = 'event_location';
    const EVENT_LOCATION_WIZARD   = 'es_event_location';
    const EVENT_ARTIST_LEGACY     = 'event_artist';
    const EVENT_ARTIST_WIZARD     = 'es_event_artist';
    
    // Content
    const EVENT_DESCRIPTION_LEGACY = 'event_description';
    const EVENT_DESCRIPTION_WIZARD = 'es_event_description';
    const EVENT_PRICE_LEGACY      = 'event_price';
    const EVENT_PRICE_WIZARD      = 'es_event_price';
    const EVENT_PRICE_NOTE        = 'event_price_note';
    const EVENT_TICKET_URL_LEGACY = 'event_ticket_url';
    const EVENT_TICKET_URL_WIZARD = 'es_event_ticket_url';
    
    // External Links
    const EVENT_EXTERNAL_URL      = 'event_external_url';
    const EVENT_EXTERNAL_TEXT     = 'event_external_text';
    const EVENT_BUTTON_TEXT       = 'event_button_text';
    
    // Status & Display
    const EVENT_STATUS            = '_event_status';
    const EVENT_BADGE             = 'event_badge';
    const EVENT_BADGE_CUSTOM      = 'event_badge_custom';
    const EVENT_ADDITIONAL_INFO   = 'event_additional_info';
    
    // Recurring Events
    const EVENT_RECURRING_PARENT  = '_es_recurring_parent';
    const EVENT_RECURRING_PATTERN = '_es_recurring_pattern';
    
    // Duration Types (Festival/Exhibition/Multi-Day System)
    const EVENT_DURATION_TYPE     = '_es_duration_type';      // single, multi_day, permanent
    const EVENT_DATE_END          = '_es_date_end';           // End date for multi_day events
    const EVENT_HAS_CHILDREN      = '_es_has_children';       // true if this is a parent (festival/exhibition)
    const EVENT_PARENT_ID         = '_es_parent_event';       // Parent event ID for child events (festival days)
    const EVENT_CHILD_ORDER       = '_es_child_order';        // Order of child events
    
    // Reservations
    const RESERVATION_ENABLED     = '_reservation_enabled';
    const RESERVATION_CAPACITY    = '_reservation_capacity';
    const RESERVATION_TYPES       = '_reservation_types';
    const RESERVATION_DEADLINE    = '_reservation_deadline_hours';
    const RESERVATION_AUTO_CONFIRM = '_reservation_auto_confirm';
    
    /**
     * ========================================
     * ARTIST META KEYS
     * ========================================
     */
    
    const ARTIST_WEBSITE          = 'artist_website';
    const ARTIST_GENRE            = 'artist_genre';
    const ARTIST_GENRE_WIZARD     = 'es_artist_genre';
    const ARTIST_GALLERY          = 'artist_gallery';
    const ARTIST_TYPE             = '_artist_type';
    const ARTIST_HERO_VIDEO       = '_artist_hero_video_url';
    
    // Social Media
    const ARTIST_FACEBOOK         = 'artist_facebook';
    const ARTIST_INSTAGRAM        = 'artist_instagram';
    const ARTIST_TWITTER          = 'artist_twitter';
    const ARTIST_YOUTUBE          = 'artist_youtube';
    const ARTIST_SPOTIFY          = 'artist_spotify';
    const ARTIST_SOUNDCLOUD       = 'artist_soundcloud';
    
    /**
     * ========================================
     * LOCATION META KEYS
     * ========================================
     */
    
    const LOCATION_ADDRESS        = 'location_address';
    const LOCATION_ADDRESS_WIZARD = 'es_location_address';
    const LOCATION_CITY           = 'location_city';
    const LOCATION_CITY_WIZARD    = 'es_location_city';
    const LOCATION_ZIP            = 'location_zip';
    const LOCATION_COUNTRY        = 'location_country';
    const LOCATION_WEBSITE        = 'location_website';
    const LOCATION_GALLERY        = 'location_gallery';
    const LOCATION_TYPE           = '_location_type';
    
    // Coordinates
    const LOCATION_LAT            = 'location_lat';
    const LOCATION_LNG            = 'location_lng';
    const LOCATION_LAT_WIZARD     = 'es_location_lat';
    const LOCATION_LNG_WIZARD     = 'es_location_lng';
    
    /**
     * ========================================
     * INTERNAL META KEYS
     * ========================================
     */
    
    const MEDIA_FOLDER_ID         = '_es_media_folder_id';
    const CATALOG_TYPE            = '_catalog_type';
    
    /**
     * Detected format cache
     * 
     * @var string|null
     */
    private static $detected_format = null;
    
    /**
     * Field mapping for standard field names to actual meta keys
     * 
     * @var array
     */
    private static $field_map = array(
        // Standard name => [legacy_key, wizard_key]
        'date'          => array(self::EVENT_DATE_LEGACY, self::EVENT_DATE_WIZARD),
        'start_date'    => array(self::EVENT_DATE_LEGACY, self::EVENT_DATE_WIZARD),
        'time'          => array(self::EVENT_TIME_LEGACY, self::EVENT_TIME_WIZARD),
        'start_time'    => array(self::EVENT_TIME_LEGACY, self::EVENT_TIME_WIZARD),
        'end_date'      => array(self::EVENT_END_DATE_LEGACY, self::EVENT_END_DATE_WIZARD),
        'end_time'      => array(self::EVENT_END_TIME_LEGACY, self::EVENT_END_TIME_WIZARD),
        'location'      => array(self::EVENT_LOCATION_LEGACY, self::EVENT_LOCATION_WIZARD),
        'artist'        => array(self::EVENT_ARTIST_LEGACY, self::EVENT_ARTIST_WIZARD),
        'description'   => array(self::EVENT_DESCRIPTION_LEGACY, self::EVENT_DESCRIPTION_WIZARD),
        'price'         => array(self::EVENT_PRICE_LEGACY, self::EVENT_PRICE_WIZARD),
        'ticket_url'    => array(self::EVENT_TICKET_URL_LEGACY, self::EVENT_TICKET_URL_WIZARD),
    );
    
    /**
     * Duration type constants
     */
    const DURATION_SINGLE    = 'single';      // Single day event (default)
    const DURATION_MULTI_DAY = 'multi_day';   // Multi-day event (festival, exhibition)
    const DURATION_PERMANENT = 'permanent';   // Permanent (ongoing exhibition)
    
    /**
     * Get the correct meta key for a field
     * 
     * This is the main method to use. It returns the correct meta key
     * based on the installation's format (legacy, wizard, or custom mapping).
     * 
     * @param string $field Standard field name (e.g., 'date', 'location', 'artist')
     * @return string The meta key to use
     * 
     * @example
     * $date_key = ES_Meta_Keys::get('date');
     * $date = get_post_meta($event_id, $date_key, true);
     */
    public static function get($field) {
        // 1. Check for custom ACF mapping first
        if (function_exists('ensemble_get_mapped_field')) {
            $mapped = ensemble_get_mapped_field($field);
            if ($mapped) {
                return $mapped;
            }
        }
        
        // 2. Get based on detected format
        $format = self::get_format();
        
        if (isset(self::$field_map[$field])) {
            $keys = self::$field_map[$field];
            return ($format === self::FORMAT_WIZARD) ? $keys[1] : $keys[0];
        }
        
        // 3. For unknown fields, try format-based prefixing
        if ($format === self::FORMAT_WIZARD) {
            return 'es_event_' . $field;
        }
        
        return 'event_' . $field;
    }
    
    /**
     * Get meta key for a specific format
     * 
     * Use when you need a specific format regardless of detection.
     * 
     * @param string $field Standard field name
     * @param string $format FORMAT_LEGACY or FORMAT_WIZARD
     * @return string The meta key
     */
    public static function get_for_format($field, $format) {
        if (isset(self::$field_map[$field])) {
            $keys = self::$field_map[$field];
            return ($format === self::FORMAT_WIZARD) ? $keys[1] : $keys[0];
        }
        
        if ($format === self::FORMAT_WIZARD) {
            return 'es_event_' . $field;
        }
        
        return 'event_' . $field;
    }
    
    /**
     * Get all possible keys for a field
     * 
     * Useful for queries that need to check multiple formats.
     * 
     * @param string $field Standard field name
     * @return array Array of possible meta keys
     */
    public static function get_all_keys($field) {
        $keys = array();
        
        // Custom mapping
        if (function_exists('ensemble_get_mapped_field')) {
            $mapped = ensemble_get_mapped_field($field);
            if ($mapped) {
                $keys[] = $mapped;
            }
        }
        
        // Both standard formats
        if (isset(self::$field_map[$field])) {
            $keys = array_merge($keys, self::$field_map[$field]);
        }
        
        return array_unique($keys);
    }
    
    /**
     * Get the detected format for this installation
     * 
     * @param bool $force_refresh Force re-detection
     * @return string FORMAT_LEGACY, FORMAT_WIZARD, or FORMAT_CUSTOM
     */
    public static function get_format($force_refresh = false) {
        if (self::$detected_format !== null && !$force_refresh) {
            return self::$detected_format;
        }
        
        // Check if custom mapping is configured
        if (function_exists('ensemble_get_field_mapping')) {
            $mapping = ensemble_get_field_mapping();
            if (!empty($mapping) && !empty($mapping['start_date'])) {
                self::$detected_format = self::FORMAT_CUSTOM;
                return self::FORMAT_CUSTOM;
            }
        }
        
        // Use existing detection from helpers
        if (function_exists('ensemble_get_date_meta_key')) {
            $date_key = ensemble_get_date_meta_key();
            
            if (strpos($date_key, 'es_') === 0) {
                self::$detected_format = self::FORMAT_WIZARD;
            } else {
                self::$detected_format = self::FORMAT_LEGACY;
            }
            
            return self::$detected_format;
        }
        
        // Default to wizard format for new installations
        self::$detected_format = self::FORMAT_WIZARD;
        return self::FORMAT_WIZARD;
    }
    
    /**
     * Check if installation uses wizard format
     * 
     * @return bool
     */
    public static function is_wizard_format() {
        return self::get_format() === self::FORMAT_WIZARD;
    }
    
    /**
     * Check if installation uses legacy format
     * 
     * @return bool
     */
    public static function is_legacy_format() {
        return self::get_format() === self::FORMAT_LEGACY;
    }
    
    /**
     * Check if installation uses custom ACF mapping
     * 
     * @return bool
     */
    public static function is_custom_mapping() {
        return self::get_format() === self::FORMAT_CUSTOM;
    }
    
    /**
     * Get artist meta key
     * 
     * @param string $field Artist field name (e.g., 'genre', 'website')
     * @return string The meta key
     */
    public static function get_artist($field) {
        $wizard_fields = array(
            'genre' => self::ARTIST_GENRE_WIZARD,
        );
        
        $legacy_fields = array(
            'genre'      => self::ARTIST_GENRE,
            'website'    => self::ARTIST_WEBSITE,
            'gallery'    => self::ARTIST_GALLERY,
            'facebook'   => self::ARTIST_FACEBOOK,
            'instagram'  => self::ARTIST_INSTAGRAM,
            'twitter'    => self::ARTIST_TWITTER,
            'youtube'    => self::ARTIST_YOUTUBE,
            'spotify'    => self::ARTIST_SPOTIFY,
            'soundcloud' => self::ARTIST_SOUNDCLOUD,
        );
        
        $format = self::get_format();
        
        if ($format === self::FORMAT_WIZARD && isset($wizard_fields[$field])) {
            return $wizard_fields[$field];
        }
        
        if (isset($legacy_fields[$field])) {
            return $legacy_fields[$field];
        }
        
        return 'artist_' . $field;
    }
    
    /**
     * Get location meta key
     * 
     * @param string $field Location field name (e.g., 'address', 'city')
     * @return string The meta key
     */
    public static function get_location($field) {
        $wizard_fields = array(
            'address' => self::LOCATION_ADDRESS_WIZARD,
            'city'    => self::LOCATION_CITY_WIZARD,
            'lat'     => self::LOCATION_LAT_WIZARD,
            'lng'     => self::LOCATION_LNG_WIZARD,
        );
        
        $legacy_fields = array(
            'address' => self::LOCATION_ADDRESS,
            'city'    => self::LOCATION_CITY,
            'zip'     => self::LOCATION_ZIP,
            'country' => self::LOCATION_COUNTRY,
            'website' => self::LOCATION_WEBSITE,
            'gallery' => self::LOCATION_GALLERY,
            'lat'     => self::LOCATION_LAT,
            'lng'     => self::LOCATION_LNG,
        );
        
        $format = self::get_format();
        
        if ($format === self::FORMAT_WIZARD && isset($wizard_fields[$field])) {
            return $wizard_fields[$field];
        }
        
        if (isset($legacy_fields[$field])) {
            return $legacy_fields[$field];
        }
        
        return 'location_' . $field;
    }
    
    /**
     * Clear format detection cache
     * 
     * Call when settings change.
     */
    public static function clear_cache() {
        self::$detected_format = null;
    }
    
    /**
     * Debug: Get all defined constants
     * 
     * @return array
     */
    public static function get_all_constants() {
        $reflection = new ReflectionClass(__CLASS__);
        return $reflection->getConstants();
    }
    
    /**
     * Debug: Get current configuration
     * 
     * @return array
     */
    public static function get_debug_info() {
        return array(
            'detected_format' => self::get_format(),
            'is_wizard'       => self::is_wizard_format(),
            'is_legacy'       => self::is_legacy_format(),
            'is_custom'       => self::is_custom_mapping(),
            'date_key'        => self::get('date'),
            'location_key'    => self::get('location'),
            'artist_key'      => self::get('artist'),
        );
    }
}
