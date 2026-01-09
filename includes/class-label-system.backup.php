<?php
/**
 * Ensemble Label System
 * 
 * Manages dynamic labels for artists, locations, events based on onboarding choices
 *
 * @package Ensemble
 */

class ES_Label_System {
    
    /**
     * Default label mappings
     */
    private static $default_labels = [
        'clubs' => [
            'artist_singular' => 'DJ / Artist',
            'artist_plural' => 'DJs & Artists',
            'location_singular' => 'Venue',
            'location_plural' => 'Venues',
            'event_singular' => 'Event',
            'event_plural' => 'Events',
            'event_types' => ['Club Night', 'Concert', 'Festival', 'Party']
        ],
        'theater' => [
            'artist_singular' => 'Performer',
            'artist_plural' => 'Ensemble',
            'location_singular' => 'Stage',
            'location_plural' => 'Stages',
            'event_singular' => 'Performance',
            'event_plural' => 'Performances',
            'event_types' => ['Performance', 'Reading', 'Show', 'Premiere']
        ],
        'church' => [
            'artist_singular' => 'Pastor',
            'artist_plural' => 'Clergy',
            'location_singular' => 'Church',
            'location_plural' => 'Churches',
            'event_singular' => 'Service',
            'event_plural' => 'Services',
            'event_types' => ['Service', 'Devotion', 'Baptism', 'Wedding', 'Funeral']
        ],
        'fitness' => [
            'artist_singular' => 'Trainer',
            'artist_plural' => 'Trainers',
            'location_singular' => 'Studio',
            'location_plural' => 'Studios',
            'event_singular' => 'Class',
            'event_plural' => 'Classes',
            'event_types' => ['Class', 'Workshop', 'Training', 'Session']
        ],
        'education' => [
            'artist_singular' => 'Instructor',
            'artist_plural' => 'Instructors',
            'location_singular' => 'Room',
            'location_plural' => 'Rooms',
            'event_singular' => 'Workshop',
            'event_plural' => 'Workshops',
            'event_types' => ['Workshop', 'Seminar', 'Training', 'Course']
        ],
        'public' => [
            'artist_singular' => 'Guide',
            'artist_plural' => 'Guides',
            'location_singular' => 'Venue',
            'location_plural' => 'Venues',
            'event_singular' => 'Event',
            'event_plural' => 'Events',
            'event_types' => ['Tour', 'Event', 'Lecture', 'Exhibition']
        ],
        'mixed' => [
            'artist_singular' => 'Performer',
            'artist_plural' => 'Performers',
            'location_singular' => 'Location',
            'location_plural' => 'Locations',
            'event_singular' => 'Event',
            'event_plural' => 'Events',
            'event_types' => ['Event', 'Gathering', 'Appointment']
        ],
        'default' => [
            'artist_singular' => 'Artist',
            'artist_plural' => 'Artists',
            'location_singular' => 'Location',
            'location_plural' => 'Locations',
            'event_singular' => 'Event',
            'event_plural' => 'Events',
            'event_types' => ['Event']
        ]
    ];
    
    /**
     * Get label for a specific entity type
     * 
     * @param string $entity_type 'artist', 'location', 'event'
     * @param bool $plural Whether to return plural form
     * @return string The label (never empty or null)
     */
    public static function get_label($entity_type, $plural = false) {
        // Ensure entity_type is valid
        if (empty($entity_type)) {
            return 'Item';
        }
        
        $suffix = $plural ? '_plural' : '_singular';
        $key = $entity_type . $suffix;
        
        // Check for saved custom label
        $custom_label = get_option('ensemble_label_' . $key, '');
        if (!empty($custom_label) && is_string($custom_label)) {
            return $custom_label;
        }
        
        // Fall back to usage type defaults
        $usage_type = get_option('ensemble_usage_type', 'default');
        if (empty($usage_type)) {
            $usage_type = 'default';
        }
        
        $labels = isset(self::$default_labels[$usage_type]) 
            ? self::$default_labels[$usage_type] 
            : self::$default_labels['default'];
        
        // Return label or fallback
        if (isset($labels[$key]) && !empty($labels[$key])) {
            return $labels[$key];
        }
        
        // Ultimate fallback
        return ucfirst($entity_type);
    }
    
    /**
     * Get all labels for current configuration
     * 
     * @return array Associative array of all labels
     */
    public static function get_all_labels() {
        return [
            'artist_singular' => self::get_label('artist', false),
            'artist_plural' => self::get_label('artist', true),
            'location_singular' => self::get_label('location', false),
            'location_plural' => self::get_label('location', true),
            'event_singular' => self::get_label('event', false),
            'event_plural' => self::get_label('event', true),
            'gallery_singular' => self::get_label('gallery', false),
            'gallery_plural' => self::get_label('gallery', true),
        ];
    }
    
    /**
     * Save label configuration from onboarding
     * 
     * @param array $data Onboarding form data
     * @return bool Success
     */
    public static function save_onboarding_config($data) {
        // Save usage type
        if (isset($data['usage_type'])) {
            update_option('ensemble_usage_type', sanitize_text_field($data['usage_type']));
        }
        
        // Save experience level
        if (isset($data['experience_level'])) {
            update_option('ensemble_experience_level', sanitize_text_field($data['experience_level']));
        }
        
        // Save artist labels
        if (isset($data['artist_label_singular']) && isset($data['artist_label_plural'])) {
            update_option('ensemble_label_artist_singular', sanitize_text_field($data['artist_label_singular']));
            update_option('ensemble_label_artist_plural', sanitize_text_field($data['artist_label_plural']));
        }
        
        // Save label source (custom, suggestion, or default)
        if (isset($data['label_source'])) {
            update_option('ensemble_label_source', sanitize_text_field($data['label_source']));
        }
        
        // Mark onboarding as completed
        update_option('ensemble_onboarding_completed', true);
        
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
        delete_option('ensemble_onboarding_completed');
        delete_option('ensemble_usage_type');
        delete_option('ensemble_experience_level');
        delete_option('ensemble_label_artist_singular');
        delete_option('ensemble_label_artist_plural');
        delete_option('ensemble_label_location_singular');
        delete_option('ensemble_label_location_plural');
        delete_option('ensemble_label_event_singular');
        delete_option('ensemble_label_event_plural');
        delete_option('ensemble_label_source');
    }
    
    /**
     * Get suggested event types based on usage type
     * 
     * @return array Event type suggestions
     */
    public static function get_event_type_suggestions() {
        $usage_type = get_option('ensemble_usage_type', 'default');
        $labels = isset(self::$default_labels[$usage_type]) 
            ? self::$default_labels[$usage_type] 
            : self::$default_labels['default'];
        
        return isset($labels['event_types']) ? $labels['event_types'] : [];
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
}
