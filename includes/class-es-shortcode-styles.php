<?php
/**
 * Ensemble Shortcode Styles Manager
 * 
 * Handles conditional loading of shortcode CSS files.
 * Only loads CSS that is actually needed on the current page.
 * 
 * @package Ensemble
 * @subpackage Shortcodes
 * @since 3.0.0
 * 
 * ARCHITECTURE:
 * - Base files (_base, _components, _responsive) always loaded
 * - Shortcode-specific CSS loaded conditionally via has_shortcode()
 * - All CSS uses --ensemble-* Designer variables
 * - See docs/CSS-ARCHITECTURE.md for variable reference
 */

if (!defined('ABSPATH')) {
    exit;
}

class ES_Shortcode_Styles {
    
    /**
     * Plugin version for cache busting
     */
    const VERSION = '3.0.0';
    
    /**
     * CSS directory relative to plugin
     */
    const CSS_DIR = 'assets/css/shortcodes/';
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * Tracks which shortcodes are present on the page
     */
    private $detected_shortcodes = array();
    
    /**
     * Whether styles have been enqueued
     */
    private $styles_enqueued = false;
    
    /**
     * Base CSS files (always loaded)
     */
    private static $base_files = array(
        'es-shortcodes-base'       => '_base.css',
        'es-shortcodes-components' => '_components.css',
        'es-shortcodes-responsive' => '_responsive.css',
    );
    
    /**
     * Shortcode to CSS file mapping
     * 
     * Format: 'shortcode_name' => array('handle' => 'css_file')
     */
    private static $shortcode_styles = array(
        // Events
        'ensemble_events' => array(
            'handle' => 'es-shortcodes-events',
            'file'   => 'events.css',
        ),
        'ensemble_upcoming' => array(
            'handle' => 'es-shortcodes-events',
            'file'   => 'events.css',
        ),
        'ensemble_featured' => array(
            'handle' => 'es-shortcodes-events',
            'file'   => 'events.css',
        ),
        'ensemble_events_grid' => array(
            'handle' => 'es-shortcodes-events',
            'file'   => 'events.css',
        ),
        'ensemble_events_list' => array(
            'handle' => 'es-shortcodes-events',
            'file'   => 'events.css',
        ),
        
        // Artists
        'ensemble_artists' => array(
            'handle' => 'es-shortcodes-artists',
            'file'   => 'artists.css',
        ),
        'ensemble_artists_grid' => array(
            'handle' => 'es-shortcodes-artists',
            'file'   => 'artists.css',
        ),
        'ensemble_artists_list' => array(
            'handle' => 'es-shortcodes-artists',
            'file'   => 'artists.css',
        ),
        
        // Locations
        'ensemble_locations' => array(
            'handle' => 'es-shortcodes-locations',
            'file'   => 'locations.css',
        ),
        'ensemble_locations_grid' => array(
            'handle' => 'es-shortcodes-locations',
            'file'   => 'locations.css',
        ),
        'ensemble_locations_list' => array(
            'handle' => 'es-shortcodes-locations',
            'file'   => 'locations.css',
        ),
        
        // Lineup / Timeline
        'ensemble_lineup' => array(
            'handle' => 'es-shortcodes-lineup',
            'file'   => 'lineup.css',
        ),
        'ensemble_timeline' => array(
            'handle' => 'es-shortcodes-lineup',
            'file'   => 'lineup.css',
        ),
        'ensemble_multivenue' => array(
            'handle' => 'es-shortcodes-lineup',
            'file'   => 'lineup.css',
        ),
    );
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Register all styles early
        add_action('wp_enqueue_scripts', array($this, 'register_styles'), 5);
        
        // Detect shortcodes in content
        add_filter('the_content', array($this, 'detect_shortcodes'), 1);
        add_filter('widget_text', array($this, 'detect_shortcodes'), 1);
        
        // Enqueue detected styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_detected_styles'), 20);
        
        // Single post type pages - always load relevant styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_single_styles'), 15);
        
        // Elementor support
        if (did_action('elementor/loaded')) {
            add_action('elementor/frontend/after_enqueue_styles', array($this, 'enqueue_elementor_styles'));
        }
    }
    
    /**
     * Get CSS URL
     */
    private function get_css_url($file) {
        return ENSEMBLE_PLUGIN_URL . self::CSS_DIR . $file;
    }
    
    /**
     * Get CSS path for file existence check
     */
    private function get_css_path($file) {
        return ENSEMBLE_PLUGIN_DIR . self::CSS_DIR . $file;
    }
    
    /**
     * Register all CSS files (but don't enqueue yet)
     */
    public function register_styles() {
        $version = defined('ENSEMBLE_VERSION') ? ENSEMBLE_VERSION : self::VERSION;
        
        // Register base files
        foreach (self::$base_files as $handle => $file) {
            if (file_exists($this->get_css_path($file))) {
                wp_register_style(
                    $handle,
                    $this->get_css_url($file),
                    array(),
                    $version
                );
            }
        }
        
        // Register shortcode-specific files
        $registered = array();
        foreach (self::$shortcode_styles as $shortcode => $config) {
            $handle = $config['handle'];
            
            // Avoid duplicate registrations
            if (in_array($handle, $registered)) {
                continue;
            }
            
            if (file_exists($this->get_css_path($config['file']))) {
                wp_register_style(
                    $handle,
                    $this->get_css_url($config['file']),
                    array('es-shortcodes-base', 'es-shortcodes-components'),
                    $version
                );
                $registered[] = $handle;
            }
        }
        
        // Register event-single (for single event pages)
        if (file_exists($this->get_css_path('event-single.css'))) {
            wp_register_style(
                'es-shortcodes-event-single',
                $this->get_css_url('event-single.css'),
                array('es-shortcodes-base', 'es-shortcodes-components'),
                $version
            );
        }
        
        // Register elementor styles
        if (file_exists($this->get_css_path('elementor.css'))) {
            wp_register_style(
                'es-shortcodes-elementor',
                $this->get_css_url('elementor.css'),
                array('es-shortcodes-base', 'es-shortcodes-components'),
                $version
            );
        }
    }
    
    /**
     * Detect shortcodes in content
     * 
     * @param string $content Post content
     * @return string Unmodified content
     */
    public function detect_shortcodes($content) {
        if (empty($content)) {
            return $content;
        }
        
        // Check each registered shortcode
        foreach (self::$shortcode_styles as $shortcode => $config) {
            if (has_shortcode($content, $shortcode)) {
                $this->detected_shortcodes[$shortcode] = $config;
            }
        }
        
        return $content;
    }
    
    /**
     * Enqueue base styles and detected shortcode styles
     */
    public function enqueue_detected_styles() {
        if ($this->styles_enqueued) {
            return;
        }
        
        // Always enqueue base styles if any Ensemble content is present
        if (!empty($this->detected_shortcodes) || $this->is_ensemble_page()) {
            $this->enqueue_base_styles();
            
            // Enqueue shortcode-specific styles
            $enqueued_handles = array();
            foreach ($this->detected_shortcodes as $shortcode => $config) {
                $handle = $config['handle'];
                
                // Avoid duplicate enqueues
                if (in_array($handle, $enqueued_handles)) {
                    continue;
                }
                
                wp_enqueue_style($handle);
                $enqueued_handles[] = $handle;
            }
            
            $this->styles_enqueued = true;
        }
    }
    
    /**
     * Enqueue base CSS files
     */
    private function enqueue_base_styles() {
        foreach (self::$base_files as $handle => $file) {
            wp_enqueue_style($handle);
        }
    }
    
    /**
     * Enqueue styles for single post type pages
     */
    public function enqueue_single_styles() {
        // Single Event
        if (is_singular('event') || is_singular('es_event')) {
            $this->enqueue_base_styles();
            wp_enqueue_style('es-shortcodes-event-single');
            wp_enqueue_style('es-shortcodes-events');
            $this->styles_enqueued = true;
        }
        
        // Single Artist
        if (is_singular('artist') || is_singular('es_artist')) {
            $this->enqueue_base_styles();
            wp_enqueue_style('es-shortcodes-artists');
            wp_enqueue_style('es-shortcodes-events'); // For related events
            $this->styles_enqueued = true;
        }
        
        // Single Location
        if (is_singular('location') || is_singular('es_location') || is_singular('venue')) {
            $this->enqueue_base_styles();
            wp_enqueue_style('es-shortcodes-locations');
            wp_enqueue_style('es-shortcodes-events'); // For related events
            $this->styles_enqueued = true;
        }
        
        // Archive pages
        if (is_post_type_archive(array('event', 'es_event', 'artist', 'es_artist', 'location', 'es_location', 'venue'))) {
            $this->enqueue_base_styles();
            wp_enqueue_style('es-shortcodes-events');
            wp_enqueue_style('es-shortcodes-artists');
            wp_enqueue_style('es-shortcodes-locations');
            $this->styles_enqueued = true;
        }
    }
    
    /**
     * Enqueue Elementor-specific styles
     */
    public function enqueue_elementor_styles() {
        if (class_exists('\Elementor\Plugin')) {
            $this->enqueue_base_styles();
            wp_enqueue_style('es-shortcodes-elementor');
        }
    }
    
    /**
     * Check if current page is an Ensemble page
     */
    private function is_ensemble_page() {
        // Check post types
        $ensemble_post_types = array(
            'event', 'es_event',
            'artist', 'es_artist', 
            'location', 'es_location', 'venue'
        );
        
        if (is_singular($ensemble_post_types) || is_post_type_archive($ensemble_post_types)) {
            return true;
        }
        
        // Check for Ensemble templates
        global $post;
        if ($post && is_object($post)) {
            $template = get_page_template_slug($post->ID);
            if (strpos($template, 'ensemble') !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Manually enqueue styles for a specific shortcode
     * 
     * Can be called from shortcode render functions:
     * ES_Shortcode_Styles::enqueue('ensemble_events');
     * 
     * @param string $shortcode Shortcode name
     */
    public static function enqueue($shortcode) {
        $instance = self::get_instance();
        
        // Enqueue base styles
        $instance->enqueue_base_styles();
        
        // Enqueue shortcode-specific style
        if (isset(self::$shortcode_styles[$shortcode])) {
            $handle = self::$shortcode_styles[$shortcode]['handle'];
            wp_enqueue_style($handle);
        }
    }
    
    /**
     * Enqueue all styles (fallback for complex pages)
     * 
     * Use sparingly - defeats the purpose of conditional loading
     */
    public static function enqueue_all() {
        $instance = self::get_instance();
        
        // Base
        $instance->enqueue_base_styles();
        
        // All shortcode styles
        $enqueued = array();
        foreach (self::$shortcode_styles as $config) {
            if (!in_array($config['handle'], $enqueued)) {
                wp_enqueue_style($config['handle']);
                $enqueued[] = $config['handle'];
            }
        }
        
        // Singles
        wp_enqueue_style('es-shortcodes-event-single');
        
        // Elementor
        wp_enqueue_style('es-shortcodes-elementor');
    }
    
    /**
     * Get list of all registered style handles
     * 
     * @return array Style handles
     */
    public static function get_registered_handles() {
        $handles = array_keys(self::$base_files);
        
        foreach (self::$shortcode_styles as $config) {
            if (!in_array($config['handle'], $handles)) {
                $handles[] = $config['handle'];
            }
        }
        
        $handles[] = 'es-shortcodes-event-single';
        $handles[] = 'es-shortcodes-elementor';
        
        return $handles;
    }
    
    /**
     * Add inline CSS (for dynamic Designer values)
     * 
     * @param string $handle Style handle to attach to
     * @param string $css CSS code
     */
    public static function add_inline_css($handle, $css) {
        wp_add_inline_style($handle, $css);
    }
    
    /**
     * Check if a specific style is enqueued
     * 
     * @param string $handle Style handle
     * @return bool
     */
    public static function is_enqueued($handle) {
        return wp_style_is($handle, 'enqueued');
    }
}

/**
 * Initialize the shortcode styles manager
 */
function es_init_shortcode_styles() {
    ES_Shortcode_Styles::get_instance();
}
add_action('init', 'es_init_shortcode_styles');

/**
 * Helper function for manual enqueue
 * 
 * @param string $shortcode Shortcode name
 */
function es_enqueue_shortcode_style($shortcode) {
    ES_Shortcode_Styles::enqueue($shortcode);
}
