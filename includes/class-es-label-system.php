<?php
/**
 * Ensemble Label System (Extended)
 * 
 * Manages dynamic labels for artists, locations, events based on onboarding choices.
 * Provides consistent terminology across the entire plugin.
 *
 * @package Ensemble
 * @since 2.0.0
 * @version 3.0.0 - Added kongress type, gallery labels, helper function, JS localization
 */

if (!defined('ABSPATH')) {
    exit;
}

class ES_Label_System {
    
    /**
     * Cached labels for performance
     * @var array|null
     */
    private static $label_cache = null;
    
    /**
     * Default label mappings per usage type
     * 
     * Each type defines labels for: artist, location, event, gallery
     * Both singular and plural forms
     */
    private static $default_labels = [
        
        // ══════════════════════════════════════════════════════════════
        // ENTERTAINMENT & CULTURE
        // ══════════════════════════════════════════════════════════════
        
        'clubs' => [
            'artist_singular'      => 'DJ / Artist',
            'artist_plural'        => 'DJs & Artists',
            'location_singular'    => 'Venue',
            'location_plural'      => 'Venues',
            'event_singular'       => 'Event',
            'event_plural'         => 'Events',
            'gallery_singular'     => 'Gallery',
            'gallery_plural'       => 'Galleries',
            'staff_singular'       => 'Team Member',
            'staff_plural'         => 'Team',
            'department_singular'  => 'Area',
            'department_plural'    => 'Areas',
            'event_types'          => ['Club Night', 'Concert', 'Festival', 'Party'],
            'icon'                 => 'dashicons-microphone',
        ],
        
        'theater' => [
            'artist_singular'      => 'Performer',
            'artist_plural'        => 'Ensemble',
            'location_singular'    => 'Stage',
            'location_plural'      => 'Stages',
            'event_singular'       => 'Performance',
            'event_plural'         => 'Performances',
            'gallery_singular'     => 'Production Photos',
            'gallery_plural'       => 'Production Photos',
            'staff_singular'       => 'Contact',
            'staff_plural'         => 'Contacts',
            'department_singular'  => 'Department',
            'department_plural'    => 'Departments',
            'event_types'          => ['Performance', 'Reading', 'Show', 'Premiere'],
            'icon'                 => 'dashicons-tickets-alt',
        ],
        
        'museum' => [
            'artist_singular'      => 'Artist',
            'artist_plural'        => 'Artists',
            'location_singular'    => 'Exhibition Space',
            'location_plural'      => 'Exhibition Spaces',
            'event_singular'       => 'Exhibition',
            'event_plural'         => 'Exhibitions',
            'gallery_singular'     => 'Artwork',
            'gallery_plural'       => 'Artworks',
            'staff_singular'       => 'Contact',
            'staff_plural'         => 'Contacts',
            'department_singular'  => 'Department',
            'department_plural'    => 'Departments',
            'event_types'          => ['Exhibition', 'Vernissage', 'Guided Tour', 'Workshop'],
            'icon'                 => 'dashicons-art',
        ],
        
        // ══════════════════════════════════════════════════════════════
        // PROFESSIONAL & BUSINESS
        // ══════════════════════════════════════════════════════════════
        
        'kongress' => [
            'artist_singular'      => 'Speaker',
            'artist_plural'        => 'Speakers',
            'location_singular'    => 'Room',
            'location_plural'      => 'Rooms',
            'event_singular'       => 'Session',
            'event_plural'         => 'Sessions',
            'gallery_singular'     => 'Photo',
            'gallery_plural'       => 'Photos',
            'staff_singular'       => 'Contact Person',
            'staff_plural'         => 'Contact Persons',
            'department_singular'  => 'Department',
            'department_plural'    => 'Departments',
            'event_types'          => ['Keynote', 'Workshop', 'Panel', 'Breakout Session', 'Networking'],
            'icon'                 => 'dashicons-businessman',
        ],
        
        'education' => [
            'artist_singular'      => 'Instructor',
            'artist_plural'        => 'Instructors',
            'location_singular'    => 'Room',
            'location_plural'      => 'Rooms',
            'event_singular'       => 'Course',
            'event_plural'         => 'Courses',
            'gallery_singular'     => 'Photo',
            'gallery_plural'       => 'Photos',
            'staff_singular'       => 'Contact',
            'staff_plural'         => 'Contacts',
            'department_singular'  => 'Department',
            'department_plural'    => 'Departments',
            'event_types'          => ['Workshop', 'Seminar', 'Training', 'Lecture'],
            'icon'                 => 'dashicons-welcome-learn-more',
        ],
        
        // ══════════════════════════════════════════════════════════════
        // SPORTS & FITNESS
        // ══════════════════════════════════════════════════════════════
        
        'fitness' => [
            'artist_singular'      => 'Trainer',
            'artist_plural'        => 'Trainers',
            'location_singular'    => 'Studio',
            'location_plural'      => 'Studios',
            'event_singular'       => 'Class',
            'event_plural'         => 'Classes',
            'gallery_singular'     => 'Photo',
            'gallery_plural'       => 'Photos',
            'staff_singular'       => 'Contact',
            'staff_plural'         => 'Contacts',
            'department_singular'  => 'Area',
            'department_plural'    => 'Areas',
            'event_types'          => ['Class', 'Workshop', 'Training', 'Session'],
            'icon'                 => 'dashicons-heart',
        ],
        
        'sports' => [
            'artist_singular'      => 'Athlete',
            'artist_plural'        => 'Athletes',
            'location_singular'    => 'Venue',
            'location_plural'      => 'Venues',
            'event_singular'       => 'Match',
            'event_plural'         => 'Matches',
            'gallery_singular'     => 'Photo',
            'gallery_plural'       => 'Photos',
            'staff_singular'       => 'Contact',
            'staff_plural'         => 'Contacts',
            'department_singular'  => 'Department',
            'department_plural'    => 'Departments',
            'event_types'          => ['Match', 'Tournament', 'Training', 'Championship'],
            'icon'                 => 'dashicons-awards',
        ],
        
        // ══════════════════════════════════════════════════════════════
        // COMMUNITY & RELIGIOUS
        // ══════════════════════════════════════════════════════════════
        
        'church' => [
            'artist_singular'      => 'Pastor',
            'artist_plural'        => 'Clergy',
            'location_singular'    => 'Church',
            'location_plural'      => 'Churches',
            'event_singular'       => 'Service',
            'event_plural'         => 'Services',
            'gallery_singular'     => 'Photo',
            'gallery_plural'       => 'Photos',
            'staff_singular'       => 'Contact',
            'staff_plural'         => 'Contacts',
            'department_singular'  => 'Ministry',
            'department_plural'    => 'Ministries',
            'event_types'          => ['Service', 'Devotion', 'Baptism', 'Wedding', 'Funeral'],
            'icon'                 => 'dashicons-admin-home',
        ],
        
        'public' => [
            'artist_singular'      => 'Guide',
            'artist_plural'        => 'Guides',
            'location_singular'    => 'Venue',
            'location_plural'      => 'Venues',
            'event_singular'       => 'Event',
            'event_plural'         => 'Events',
            'gallery_singular'     => 'Photo',
            'gallery_plural'       => 'Photos',
            'staff_singular'       => 'Contact',
            'staff_plural'         => 'Contacts',
            'department_singular'  => 'Department',
            'department_plural'    => 'Departments',
            'event_types'          => ['Tour', 'Event', 'Lecture', 'Exhibition'],
            'icon'                 => 'dashicons-groups',
        ],
        
        // ══════════════════════════════════════════════════════════════
        // GENERIC / FALLBACK
        // ══════════════════════════════════════════════════════════════
        
        'mixed' => [
            'artist_singular'      => 'Performer',
            'artist_plural'        => 'Performers',
            'location_singular'    => 'Location',
            'location_plural'      => 'Locations',
            'event_singular'       => 'Event',
            'event_plural'         => 'Events',
            'gallery_singular'     => 'Photo',
            'gallery_plural'       => 'Photos',
            'staff_singular'       => 'Contact',
            'staff_plural'         => 'Contacts',
            'department_singular'  => 'Department',
            'department_plural'    => 'Departments',
            'event_types'          => ['Event', 'Gathering', 'Appointment'],
            'icon'                 => 'dashicons-calendar-alt',
        ],
        
        'default' => [
            'artist_singular'      => 'Artist',
            'artist_plural'        => 'Artists',
            'location_singular'    => 'Location',
            'location_plural'      => 'Locations',
            'event_singular'       => 'Event',
            'event_plural'         => 'Events',
            'gallery_singular'     => 'Gallery',
            'gallery_plural'       => 'Galleries',
            'staff_singular'       => 'Contact',
            'staff_plural'         => 'Contacts',
            'department_singular'  => 'Department',
            'department_plural'    => 'Departments',
            'event_types'          => ['Event'],
            'icon'                 => 'dashicons-calendar',
        ],
    ];
    
    /**
     * Get label for a specific entity type
     * 
     * @param string $entity_type 'artist', 'location', 'event', 'gallery'
     * @param bool   $plural      Whether to return plural form
     * @return string The label (never empty or null)
     */
    public static function get_label($entity_type, $plural = false) {
        // Validate entity type
        if (empty($entity_type) || !is_string($entity_type)) {
            return 'Item';
        }
        
        // Normalize entity type
        $entity_type = strtolower(trim($entity_type));
        
        $suffix = $plural ? '_plural' : '_singular';
        $key = $entity_type . $suffix;
        
        // Check cache first
        if (self::$label_cache !== null && isset(self::$label_cache[$key])) {
            return self::$label_cache[$key];
        }
        
        // Check for saved custom label
        $custom_label = get_option('ensemble_label_' . $key, '');
        if (!empty($custom_label) && is_string($custom_label)) {
            self::cache_label($key, $custom_label);
            return $custom_label;
        }
        
        // Fall back to usage type defaults
        $usage_type = self::get_usage_type();
        
        $labels = isset(self::$default_labels[$usage_type]) 
            ? self::$default_labels[$usage_type] 
            : self::$default_labels['default'];
        
        // Return label or fallback
        if (isset($labels[$key]) && !empty($labels[$key])) {
            self::cache_label($key, $labels[$key]);
            return $labels[$key];
        }
        
        // Ultimate fallback: capitalize entity type
        $fallback = ucfirst($entity_type) . ($plural ? 's' : '');
        self::cache_label($key, $fallback);
        return $fallback;
    }
    
    /**
     * Cache a label for performance
     * 
     * @param string $key   Cache key
     * @param string $value Label value
     */
    private static function cache_label($key, $value) {
        if (self::$label_cache === null) {
            self::$label_cache = [];
        }
        self::$label_cache[$key] = $value;
    }
    
    /**
     * Clear label cache (call after settings change)
     */
    public static function clear_cache() {
        self::$label_cache = null;
    }
    
    /**
     * Get all labels for current configuration
     * 
     * @return array Associative array of all labels
     */
    public static function get_all_labels() {
        return [
            'artist_singular'      => self::get_label('artist', false),
            'artist_plural'        => self::get_label('artist', true),
            'location_singular'    => self::get_label('location', false),
            'location_plural'      => self::get_label('location', true),
            'event_singular'       => self::get_label('event', false),
            'event_plural'         => self::get_label('event', true),
            'gallery_singular'     => self::get_label('gallery', false),
            'gallery_plural'       => self::get_label('gallery', true),
            'staff_singular'       => self::get_label('staff', false),
            'staff_plural'         => self::get_label('staff', true),
            'department_singular'  => self::get_label('department', false),
            'department_plural'    => self::get_label('department', true),
        ];
    }
    
    /**
     * Get labels for JavaScript localization
     * 
     * @return array Labels formatted for wp_localize_script
     */
    public static function get_js_labels() {
        $labels = self::get_all_labels();
        
        // Add commonly used phrases
        $labels['add_new_artist']    = sprintf(__('Add %s', 'ensemble'), $labels['artist_singular']);
        $labels['add_new_location']  = sprintf(__('Add %s', 'ensemble'), $labels['location_singular']);
        $labels['add_new_event']     = sprintf(__('Add %s', 'ensemble'), $labels['event_singular']);
        $labels['search_artists']    = sprintf(__('Search %s...', 'ensemble'), strtolower($labels['artist_plural']));
        $labels['search_locations']  = sprintf(__('Search %s...', 'ensemble'), strtolower($labels['location_plural']));
        $labels['search_events']     = sprintf(__('Search %s...', 'ensemble'), strtolower($labels['event_plural']));
        $labels['loading_artists']   = sprintf(__('Loading %s...', 'ensemble'), strtolower($labels['artist_plural']));
        $labels['loading_locations'] = sprintf(__('Loading %s...', 'ensemble'), strtolower($labels['location_plural']));
        $labels['loading_events']    = sprintf(__('Loading %s...', 'ensemble'), strtolower($labels['event_plural']));
        $labels['no_artists']        = sprintf(__('No %s found', 'ensemble'), strtolower($labels['artist_plural']));
        $labels['no_locations']      = sprintf(__('No %s found', 'ensemble'), strtolower($labels['location_plural']));
        $labels['no_events']         = sprintf(__('No %s found', 'ensemble'), strtolower($labels['event_plural']));
        $labels['save_artist']       = sprintf(__('Save %s', 'ensemble'), $labels['artist_singular']);
        $labels['save_location']     = sprintf(__('Save %s', 'ensemble'), $labels['location_singular']);
        $labels['save_event']        = sprintf(__('Save %s', 'ensemble'), $labels['event_singular']);
        $labels['delete_artist']     = sprintf(__('Delete %s', 'ensemble'), $labels['artist_singular']);
        $labels['delete_location']   = sprintf(__('Delete %s', 'ensemble'), $labels['location_singular']);
        $labels['delete_event']      = sprintf(__('Delete %s', 'ensemble'), $labels['event_singular']);
        
        return $labels;
    }
    
    /**
     * Get icon for current usage type
     * 
     * @return string Dashicon class
     */
    public static function get_usage_icon() {
        $usage_type = self::get_usage_type();
        $labels = isset(self::$default_labels[$usage_type]) 
            ? self::$default_labels[$usage_type] 
            : self::$default_labels['default'];
        
        return isset($labels['icon']) ? $labels['icon'] : 'dashicons-calendar';
    }
    
    /**
     * Get WordPress Post Type labels for a specific entity
     * 
     * Use with register_post_type_args filter
     * 
     * @param string $entity_type 'artist', 'location', 'event'
     * @return array WordPress post type labels array
     */
    public static function get_post_type_labels($entity_type) {
        $singular = self::get_label($entity_type, false);
        $plural   = self::get_label($entity_type, true);
        $lower_s  = strtolower($singular);
        $lower_p  = strtolower($plural);
        
        return [
            'name'                  => $plural,
            'singular_name'         => $singular,
            'add_new'               => sprintf(__('Add New', 'ensemble')),
            'add_new_item'          => sprintf(__('Add New %s', 'ensemble'), $singular),
            'edit_item'             => sprintf(__('Edit %s', 'ensemble'), $singular),
            'new_item'              => sprintf(__('New %s', 'ensemble'), $singular),
            'view_item'             => sprintf(__('View %s', 'ensemble'), $singular),
            'view_items'            => sprintf(__('View %s', 'ensemble'), $plural),
            'search_items'          => sprintf(__('Search %s', 'ensemble'), $plural),
            'not_found'             => sprintf(__('No %s found', 'ensemble'), $lower_p),
            'not_found_in_trash'    => sprintf(__('No %s found in Trash', 'ensemble'), $lower_p),
            'parent_item_colon'     => sprintf(__('Parent %s:', 'ensemble'), $singular),
            'all_items'             => sprintf(__('All %s', 'ensemble'), $plural),
            'archives'              => sprintf(__('%s Archives', 'ensemble'), $singular),
            'attributes'            => sprintf(__('%s Attributes', 'ensemble'), $singular),
            'insert_into_item'      => sprintf(__('Insert into %s', 'ensemble'), $lower_s),
            'uploaded_to_this_item' => sprintf(__('Uploaded to this %s', 'ensemble'), $lower_s),
            'featured_image'        => __('Featured Image', 'ensemble'),
            'set_featured_image'    => __('Set featured image', 'ensemble'),
            'remove_featured_image' => __('Remove featured image', 'ensemble'),
            'use_featured_image'    => __('Use as featured image', 'ensemble'),
            'filter_items_list'     => sprintf(__('Filter %s list', 'ensemble'), $lower_p),
            'items_list_navigation' => sprintf(__('%s list navigation', 'ensemble'), $plural),
            'items_list'            => sprintf(__('%s list', 'ensemble'), $plural),
            'menu_name'             => $plural,
            'name_admin_bar'        => $singular,
        ];
    }
    
    /**
     * Save label configuration from onboarding
     * 
     * @param array $data Onboarding form data
     * @return bool Success
     */
    public static function save_onboarding_config($data) {
        // Clear cache before saving
        self::clear_cache();
        
        // Save usage type
        if (isset($data['usage_type'])) {
            update_option('ensemble_usage_type', sanitize_text_field($data['usage_type']));
        }
        
        // Save experience level
        if (isset($data['experience_level'])) {
            update_option('ensemble_experience_level', sanitize_text_field($data['experience_level']));
        }
        
        // Save artist labels
        if (!empty($data['artist_label_singular'])) {
            update_option('ensemble_label_artist_singular', sanitize_text_field($data['artist_label_singular']));
        }
        if (!empty($data['artist_label_plural'])) {
            update_option('ensemble_label_artist_plural', sanitize_text_field($data['artist_label_plural']));
        }
        
        // Save location labels (NEW)
        if (!empty($data['location_label_singular'])) {
            update_option('ensemble_label_location_singular', sanitize_text_field($data['location_label_singular']));
        }
        if (!empty($data['location_label_plural'])) {
            update_option('ensemble_label_location_plural', sanitize_text_field($data['location_label_plural']));
        }
        
        // Save event labels (NEW)
        if (!empty($data['event_label_singular'])) {
            update_option('ensemble_label_event_singular', sanitize_text_field($data['event_label_singular']));
        }
        if (!empty($data['event_label_plural'])) {
            update_option('ensemble_label_event_plural', sanitize_text_field($data['event_label_plural']));
        }
        
        // Save label source (custom, suggestion, or default)
        if (isset($data['label_source'])) {
            update_option('ensemble_label_source', sanitize_text_field($data['label_source']));
        }
        
        // Mark onboarding as completed
        update_option('ensemble_onboarding_completed', true);
        
        // Clear cache after saving
        self::clear_cache();
        
        return true;
    }
    
    /**
     * Save individual label (for settings page)
     * 
     * @param string $entity_type Entity type (artist, location, event, gallery)
     * @param string $singular    Singular label
     * @param string $plural      Plural label
     * @return bool Success
     */
    public static function save_labels($entity_type, $singular, $plural) {
        self::clear_cache();
        
        $entity_type = sanitize_key($entity_type);
        
        if (!empty($singular)) {
            update_option('ensemble_label_' . $entity_type . '_singular', sanitize_text_field($singular));
        }
        if (!empty($plural)) {
            update_option('ensemble_label_' . $entity_type . '_plural', sanitize_text_field($plural));
        }
        
        return true;
    }
    
    /**
     * Check if onboarding is completed
     * 
     * @return bool
     */
    public static function is_onboarding_completed() {
        return (bool) get_option('ensemble_onboarding_completed', false);
    }
    
    /**
     * Reset onboarding (for testing or re-configuration)
     */
    public static function reset_onboarding() {
        self::clear_cache();
        
        delete_option('ensemble_onboarding_completed');
        delete_option('ensemble_usage_type');
        delete_option('ensemble_experience_level');
        delete_option('ensemble_label_artist_singular');
        delete_option('ensemble_label_artist_plural');
        delete_option('ensemble_label_location_singular');
        delete_option('ensemble_label_location_plural');
        delete_option('ensemble_label_event_singular');
        delete_option('ensemble_label_event_plural');
        delete_option('ensemble_label_gallery_singular');
        delete_option('ensemble_label_gallery_plural');
        delete_option('ensemble_label_source');
    }
    
    /**
     * Get suggested event types based on usage type
     * 
     * @return array Event type suggestions
     */
    public static function get_event_type_suggestions() {
        $usage_type = self::get_usage_type();
        $labels = isset(self::$default_labels[$usage_type]) 
            ? self::$default_labels[$usage_type] 
            : self::$default_labels['default'];
        
        return isset($labels['event_types']) ? $labels['event_types'] : [];
    }
    
    /**
     * Get all available usage types
     * 
     * @return array Usage types with labels
     */
    public static function get_available_usage_types() {
        $types = [];
        
        foreach (self::$default_labels as $key => $labels) {
            if ($key === 'default') continue;
            
            $types[$key] = [
                'key'      => $key,
                'label'    => ucfirst($key),
                'icon'     => isset($labels['icon']) ? $labels['icon'] : 'dashicons-calendar',
                'artist'   => $labels['artist_singular'],
                'location' => $labels['location_singular'],
                'event'    => $labels['event_singular'],
            ];
        }
        
        return $types;
    }
    
    /**
     * Get usage type context
     * 
     * @return string Current usage type (never empty)
     */
    public static function get_usage_type() {
        $usage_type = get_option('ensemble_usage_type', 'default');
        return empty($usage_type) ? 'default' : $usage_type;
    }
    
    /**
     * Get experience level
     * 
     * @return string Current experience level (never empty)
     */
    public static function get_experience_level() {
        $level = get_option('ensemble_experience_level', 'intermediate');
        return empty($level) ? 'intermediate' : $level;
    }
    
    /**
     * Should show helper tooltips based on experience level
     * 
     * @return bool
     */
    public static function should_show_helpers() {
        $level = self::get_experience_level();
        return in_array($level, ['beginner', 'intermediate']);
    }
    
    /**
     * Get default labels for a specific usage type
     * 
     * Useful for preview in settings
     * 
     * @param string $usage_type Usage type key
     * @return array|null Labels array or null if not found
     */
    public static function get_defaults_for_type($usage_type) {
        return isset(self::$default_labels[$usage_type]) 
            ? self::$default_labels[$usage_type] 
            : null;
    }
}

// ══════════════════════════════════════════════════════════════════════════════
// HELPER FUNCTIONS
// ══════════════════════════════════════════════════════════════════════════════

if (!function_exists('ensemble_label')) {
    /**
     * Get a label for an entity type
     * 
     * Shorthand for ES_Label_System::get_label()
     * 
     * @param string $entity_type 'artist', 'location', 'event', 'gallery'
     * @param bool   $plural      Whether to return plural form
     * @return string The label
     * 
     * @example
     * echo ensemble_label('artist');          // "Speaker" (if kongress)
     * echo ensemble_label('artist', true);    // "Speakers"
     * echo ensemble_label('event');           // "Session"
     * echo ensemble_label('location', true);  // "Rooms"
     */
    function ensemble_label($entity_type, $plural = false) {
        return ES_Label_System::get_label($entity_type, $plural);
    }
}

if (!function_exists('ensemble_labels')) {
    /**
     * Get all labels at once
     * 
     * @return array All labels
     * 
     * @example
     * $labels = ensemble_labels();
     * echo $labels['artist_singular']; // "Speaker"
     */
    function ensemble_labels() {
        return ES_Label_System::get_all_labels();
    }
}

// ══════════════════════════════════════════════════════════════════════════════
// WORDPRESS HOOKS
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Localize labels for JavaScript
 * 
 * Hooked to admin_enqueue_scripts and wp_enqueue_scripts
 */
add_action('admin_enqueue_scripts', 'ensemble_localize_labels', 100);
add_action('wp_enqueue_scripts', 'ensemble_localize_labels', 100);

function ensemble_localize_labels() {
    // Only if ensemble scripts are enqueued
    if (wp_script_is('ensemble-admin', 'enqueued') || wp_script_is('ensemble-frontend', 'enqueued')) {
        wp_localize_script(
            wp_script_is('ensemble-admin', 'enqueued') ? 'ensemble-admin' : 'ensemble-frontend',
            'ensembleLabels',
            ES_Label_System::get_js_labels()
        );
    }
}

/**
 * Clear label cache when options are updated
 */
add_action('update_option', function($option_name) {
    if (strpos($option_name, 'ensemble_label_') === 0 || $option_name === 'ensemble_usage_type') {
        ES_Label_System::clear_cache();
    }
}, 10, 1);
