<?php
/**
 * Layout Sets Manager
 * 
 * Manages layout sets for consistent frontend display
 * 
 * @package Ensemble
 */

if (!defined('ABSPATH')) exit;

class ES_Layout_Sets {
    
    /**
     * Pro layouts (require license)
     */
    const PRO_LAYOUTS = array();
    
    /**
     * Check if a layout is available
     * 
     * @param string $layout_slug Layout slug
     * @return bool
     */
    public static function is_layout_available($layout_slug) {
        if (!in_array($layout_slug, self::PRO_LAYOUTS)) {
            return true; // Free layout
        }
        
        return function_exists('ensemble_is_pro') && ensemble_is_pro();
    }
    
    /**
     * Get all available layout sets
     * 
     * @return array Layout sets configuration
     */
    public static function get_sets() {
        $plugin_url = defined('ENSEMBLE_PLUGIN_URL') ? ENSEMBLE_PLUGIN_URL : plugin_dir_url(dirname(__FILE__));
        $is_pro = function_exists('ensemble_is_pro') && ensemble_is_pro();
        
        $sets = array(
            'modern' => array(
                'name' => __('Modern', 'ensemble'),
                'description' => __('Clean, contemporary design with a minimalist aesthetic. Versatile for any event type.', 'ensemble'),
                'preview_image' => '',
                'style' => __('Contemporary', 'ensemble'),
                'best_for' => __('Design-focused events', 'ensemble'),
                'requires_pro' => false,
                'supports_modes' => false,
                'default_mode' => 'dark',
                'features' => array(
                    __('Side-by-side layout', 'ensemble'),
                    __('Gradient accents', 'ensemble'),
                    __('Sticky sidebar navigation', 'ensemble'),
                    __('Perfect for festivals & clubs', 'ensemble'),
                ),
                'layouts' => array(
                    'event_card' => 'modern',
                    'artist_card' => 'modern',
                    'location_card' => 'modern',
                    'calendar_event' => 'modern_teaser',
                    'event_single' => 'modern_single',
                    'artist_single' => 'modern_single',
                    'location_single' => 'modern_single',
                ),
            ),
            
            'club' => array(
                'name' => __('Club', 'ensemble'),
                'description' => __('Dark, bold nightclub style with date badges and status indicators. Perfect for electronic music, festivals, and nightlife events.', 'ensemble'),
                'preview_image' => '',
                'style' => __('Dark / Nightlife', 'ensemble'),
                'best_for' => __('Clubs, festivals, DJ events', 'ensemble'),
                'requires_pro' => false,
                'supports_modes' => false,
                'default_mode' => 'dark',
                'features' => array(
                    __('Dark theme optimized', 'ensemble'),
                    __('Date badge overlays', 'ensemble'),
                    __('Status badges (Sold Out, Presale)', 'ensemble'),
                    __('Perfect for nightlife & electronic music', 'ensemble'),
                ),
                'layouts' => array(
                    'event_card' => 'club',
                    'artist_card' => 'club',
                    'location_card' => 'club',
                    'calendar_event' => 'club_teaser',
                    'event_single' => 'club_single',
                    'artist_single' => 'club_single',
                    'location_single' => 'club_single',
                ),
            ),
            
            'lovepop' => array(
                'name' => __('Lovepop', 'ensemble'),
                'description' => __('Dark gradient background with glowing magenta accents. Bold, impactful design with large hero header.', 'ensemble'),
                'preview_image' => '',
                'style' => __('Dark / Bold', 'ensemble'),
                'best_for' => __('Festivals, parties, nightlife', 'ensemble'),
                'requires_pro' => false,
                'supports_modes' => false,
                'default_mode' => 'dark',
                'features' => array(
                    __('Dark gradient background', 'ensemble'),
                    __('Glowing magenta borders', 'ensemble'),
                    __('Large hero with overlay', 'ensemble'),
                    __('Bold uppercase typography', 'ensemble'),
                ),
                'layouts' => array(
                    'event_card' => 'lovepop',
                    'artist_card' => 'lovepop',
                    'location_card' => 'lovepop',
                    'calendar_event' => 'lovepop_teaser',
                    'event_single' => 'lovepop_single',
                    'artist_single' => 'lovepop_single',
                    'location_single' => 'lovepop_single',
                ),
            ),
            
            'stage' => array(
                'name' => __('Stage', 'ensemble'),
                'description' => __('Clean, minimal design with light background, sharp edges and bold Oswald typography. Perfect for professional event pages.', 'ensemble'),
                'preview_image' => '',
                'style' => __('Light / Minimal', 'ensemble'),
                'best_for' => __('Theaters, venues, professional events', 'ensemble'),
                'requires_pro' => false,
                'supports_modes' => false,
                'default_mode' => 'light',
                'features' => array(
                    __('Light, clean background', 'ensemble'),
                    __('Sharp edges (no border-radius)', 'ensemble'),
                    __('Bold Oswald typography', 'ensemble'),
                    __('Minimal, professional design', 'ensemble'),
                ),
                'layouts' => array(
                    'event_card' => 'stage',
                    'artist_card' => 'stage',
                    'location_card' => 'stage',
                    'calendar_event' => 'stage_teaser',
                    'event_single' => 'stage_single',
                    'artist_single' => 'stage_single',
                    'location_single' => 'stage_single',
                ),
            ),
            
            'pure' => array(
                'name' => __('Pure', 'ensemble'),
                'description' => __('Ultra-minimal design with clean Inter typography, ghost buttons, and thin lines. Dark/Light mode toggle included.', 'ensemble'),
                'preview_image' => '',
                'style' => __('Minimal / Adaptive', 'ensemble'),
                'best_for' => __('Modern galleries, editorial, design-focused sites', 'ensemble'),
                'requires_pro' => false,
                'supports_modes' => true,
                'default_mode' => 'light',
                'features' => array(
                    __('Dark/Light mode toggle', 'ensemble'),
                    __('Ghost button style', 'ensemble'),
                    __('Clean Inter typography', 'ensemble'),
                    __('Thin divider lines', 'ensemble'),
                    __('Maximum whitespace', 'ensemble'),
                ),
                'layouts' => array(
                    'event_card' => 'pure',
                    'artist_card' => 'pure',
                    'location_card' => 'pure',
                    'calendar_event' => 'pure_teaser',
                    'event_single' => 'pure_single',
                    'artist_single' => 'pure_single',
                    'location_single' => 'pure_single',
                ),
            ),
            
            'kinky' => array(
                'name' => __('Kinky', 'ensemble'),
                'description' => __('Dark fiery design with red/orange accents on near-black background. Elegant italic headings with bold uppercase details. Perfect for fetish, burlesque, and adult nightlife.', 'ensemble'),
                'preview_image' => '',
                'style' => __('Dark / Fiery', 'ensemble'),
                'best_for' => __('Fetish, burlesque, adult nightlife', 'ensemble'),
                'requires_pro' => false,
                'supports_modes' => false,
                'default_mode' => 'dark',
                'features' => array(
                    __('Fire-red/orange accent colors', 'ensemble'),
                    __('Near-black background with subtle blue', 'ensemble'),
                    __('Elegant Playfair Display italic headings', 'ensemble'),
                    __('Bold uppercase labels & details', 'ensemble'),
                    __('Glow effects on hover', 'ensemble'),
                ),
                'layouts' => array(
                    'event_card' => 'kinky',
                    'artist_card' => 'kinky',
                    'location_card' => 'kinky',
                    'calendar_event' => 'kinky_teaser',
                    'event_single' => 'kinky_single',
                    'artist_single' => 'kinky_single',
                    'location_single' => 'kinky_single',
                ),
            ),
            
            'simpleclub' => array(
                'name' => __('Simple Club', 'ensemble'),
                'description' => __('Clean club-style layout with prominent location display and fading gallery. Dark gradient background with glowing magenta accents.', 'ensemble'),
                'preview_image' => '',
                'style' => __('Dark / Club', 'ensemble'),
                'best_for' => __('Clubs, electronic music, nightlife', 'ensemble'),
                'requires_pro' => false,
                'supports_modes' => false,
                'default_mode' => 'dark',
                'features' => array(
                    __('Dark gradient background', 'ensemble'),
                    __('Glowing magenta accents', 'ensemble'),
                    __('Prominent location display', 'ensemble'),
                    __('Fading gallery with lightbox', 'ensemble'),
                    __('Bold Montserrat typography', 'ensemble'),
                ),
                'layouts' => array(
                    'event_card' => 'simpleclub',
                    'artist_card' => 'simpleclub',
                    'location_card' => 'simpleclub',
                    'calendar_event' => 'simpleclub_teaser',
                    'event_single' => 'simpleclub_single',
                    'artist_single' => 'simpleclub_single',
                    'location_single' => 'simpleclub_single',
                ),
            ),
            
            'kongress' => array(
                'name' => __('Kongress', 'ensemble'),
                'description' => __('Professional conference/congress design with Navy & Copper color scheme. Elegant typography, agenda/timeline view, optimized for multi-day events with speakers.', 'ensemble'),
                'preview_image' => '',
                'style' => __('Light / Professional', 'ensemble'),
                'best_for' => __('Conferences, congresses, business events', 'ensemble'),
                'requires_pro' => false,
                'supports_modes' => false,
                'default_mode' => 'light',
                'features' => array(
                    __('Navy & Copper color scheme', 'ensemble'),
                    __('Elegant Playfair Display headings', 'ensemble'),
                    __('Agenda/Timeline with breaks', 'ensemble'),
                    __('Speaker grid & detailed profiles', 'ensemble'),
                    __('Animated statistics counter', 'ensemble'),
                    __('Scroll animations', 'ensemble'),
                ),
                'layouts' => array(
                    'event_card' => 'kongress',
                    'artist_card' => 'kongress',
                    'location_card' => 'kongress',
                    'calendar_event' => 'kongress_teaser',
                    'event_single' => 'kongress_single',
                    'artist_single' => 'kongress_single',
                    'location_single' => 'kongress_single',
                ),
            ),
        );
        
        // Allow filtering for Template Park or custom sets
        return apply_filters('ensemble_layout_sets', $sets);
    }
    
    /**
     * Get active layout set
     * 
     * @return string Active set key
     */
    public static function get_active_set() {
        // Check for temporary override (used by Layout Switcher)
        if (self::$temp_active_set !== null) {
            return self::$temp_active_set;
        }
        
        // Check URL parameter for layout switcher
        if (isset($_GET['es_layout']) && !empty($_GET['es_layout'])) {
            $url_layout = sanitize_key($_GET['es_layout']);
            $sets = self::get_sets();
            if (isset($sets[$url_layout])) {
                return $url_layout;
            }
        }
        
        return get_option('ensemble_active_layout_set', 'modern');
    }
    
    /**
     * Temporary active set override (not saved to DB)
     * @var string|null
     */
    private static $temp_active_set = null;
    
    /**
     * Temporarily override active set (for current request only)
     * 
     * @param string|null $set_key Set key or null to clear
     */
    public static function set_temp_active_set($set_key) {
        if ($set_key === null) {
            self::$temp_active_set = null;
            return;
        }
        
        $sets = self::get_sets();
        if (isset($sets[$set_key])) {
            self::$temp_active_set = $set_key;
        }
    }
    
    /**
     * Set active layout set
     * 
     * @param string $set_key Set key to activate
     * @return bool Success
     */
    public static function set_active_set($set_key) {
        $sets = self::get_sets();
        
        if (!isset($sets[$set_key])) {
            return false;
        }
        
        return update_option('ensemble_active_layout_set', $set_key);
    }
    
    /**
     * Get active mode (light/dark) for current layout
     * 
     * @return string 'light' or 'dark'
     */
    public static function get_active_mode() {
        $active_set = self::get_active_set();
        $sets = self::get_sets();
        
        if (!isset($sets[$active_set])) {
            return 'light';
        }
        
        $set = $sets[$active_set];
        
        // If layout doesn't support modes, return its default
        if (empty($set['supports_modes'])) {
            return $set['default_mode'] ?? 'light';
        }
        
        // Get saved mode or default
        $saved_mode = get_option('ensemble_layout_mode_' . $active_set, '');
        
        if (empty($saved_mode)) {
            return $set['default_mode'] ?? 'light';
        }
        
        return $saved_mode;
    }
    
    /**
     * Set mode for a layout
     * 
     * @param string $mode 'light' or 'dark'
     * @param string $set_key Optional specific set, defaults to active
     * @return bool Success
     */
    public static function set_mode($mode, $set_key = null) {
        if (!in_array($mode, array('light', 'dark'))) {
            return false;
        }
        
        if ($set_key === null) {
            $set_key = self::get_active_set();
        }
        
        $sets = self::get_sets();
        
        // Check if layout supports modes
        if (isset($sets[$set_key]) && empty($sets[$set_key]['supports_modes'])) {
            return false; // Can't change mode for this layout
        }
        
        return update_option('ensemble_layout_mode_' . $set_key, $mode);
    }
    
    /**
     * Check if current layout supports mode switching
     * 
     * @param string $set_key Optional specific set
     * @return bool
     */
    public static function supports_modes($set_key = null) {
        if ($set_key === null) {
            $set_key = self::get_active_set();
        }
        
        $sets = self::get_sets();
        
        if (!isset($sets[$set_key])) {
            return false;
        }
        
        return !empty($sets[$set_key]['supports_modes']);
    }
    
    /**
     * Get layout for specific context
     * 
     * @param string $context Context (event_card, event_single, etc.)
     * @return string Layout key
     */
    public static function get_layout_for_context($context) {
        $active_set = self::get_active_set();
        $sets = self::get_sets();
        
        if (!isset($sets[$active_set])) {
            $active_set = 'modern'; // Fallback
        }
        
        $set = $sets[$active_set];
        
        if (isset($set['layouts'][$context])) {
            return $set['layouts'][$context];
        }
        
        // Fallback zu Classic
        $classic = $sets['modern'];
        return isset($classic['layouts'][$context]) ? $classic['layouts'][$context] : 'modern';
    }
    
    
    /**
     * Check if a set is active
     * 
     * @param string $set_key Set key to check
     * @return bool Is active
     */
    public static function is_set_active($set_key) {
        return self::get_active_set() === $set_key;
    }
    
    /**
 * Get detailed data for a specific layout set
 * 
 * @param string $set_id
 * @return array|null
 */
public static function get_set_data($set_id) {
    $sets = self::get_sets();
    
    if (!isset($sets[$set_id])) {
        return null;
    }
    
    $set = $sets[$set_id];
    
    // Add path if not present
    if (!isset($set['path'])) {
        // Check if it's a custom template
        if (strpos($set_id, 'custom_') === 0) {
            $slug = str_replace('custom_', '', $set_id);
            $upload_dir = wp_upload_dir();
            $set['path'] = $upload_dir['basedir'] . '/ensemble-templates/custom-' . $slug;
        } else {
            // Built-in set
            $set['path'] = ENSEMBLE_PLUGIN_DIR . 'templates/layouts/' . $set_id;
        }
    }
    
    return $set;
}



}
