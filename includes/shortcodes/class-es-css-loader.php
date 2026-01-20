<?php
/**
 * Selective CSS Loader
 * 
 * Loads CSS files on-demand based on which shortcodes are used.
 * Reduces page weight by ~40-50% compared to loading all CSS at once.
 * 
 * Architecture:
 * - Base CSS (variables, components) loaded on every page (~28KB)
 * - Shortcode-specific CSS loaded only when that shortcode is used
 * - Layout CSS loaded last for highest specificity
 * 
 * Usage in shortcodes:
 *   ES_CSS_Loader::enqueue('events');  // Load events.css
 *   ES_CSS_Loader::enqueue('artists'); // Load artists.css
 *   ES_CSS_Loader::enqueue(['events', 'lineup']); // Multiple
 * 
 * @package Ensemble
 * @version 3.0.0
 */

if (!defined('ABSPATH')) exit;

class ES_CSS_Loader {
    
    /**
     * Track which modules are queued
     * @var array
     */
    private static $queued = array();
    
    /**
     * Track which modules are loaded
     * @var array
     */
    private static $loaded = array();
    
    /**
     * Whether selective loading is enabled
     * @var bool
     */
    private static $enabled = true;
    
    /**
     * CSS modules configuration
     */
    private static function get_modules() {
        $css_url = ENSEMBLE_PLUGIN_URL . 'assets/css/';
        $frontend_url = $css_url . 'frontend/';
        
        return array(
            // =============================================
            // BASE - Always loaded (~28KB total)
            // =============================================
            'base' => array(
                'url'  => $frontend_url . '_base.css',
                'deps' => array(),
                'auto' => true,
            ),
            
            'components' => array(
                'url'  => $frontend_url . '_components.css',
                'deps' => array('base'),
                'auto' => true,
            ),
            
            'responsive' => array(
                'url'  => $frontend_url . '_responsive.css',
                'deps' => array('base', 'components'),
                'auto' => true,
            ),
            
            // =============================================
            // SHORTCODE-SPECIFIC - Loaded on demand
            // =============================================
            'events' => array(
                'url'  => $frontend_url . 'events.css',
                'deps' => array('components'),
                'auto' => false,
            ),
            
            'artists' => array(
                'url'  => $frontend_url . 'artists.css',
                'deps' => array('components'),
                'auto' => false,
            ),
            
            'locations' => array(
                'url'  => $frontend_url . 'locations.css',
                'deps' => array('components'),
                'auto' => false,
            ),
            
            'event-single' => array(
                'url'  => $frontend_url . 'event-single.css',
                'deps' => array('components'),
                'auto' => false,
            ),
            
            'lineup' => array(
                'url'  => $frontend_url . 'lineup.css',
                'deps' => array('components'),
                'auto' => false,
            ),
            
            'calendar' => array(
                'url'  => $css_url . 'calendar.css',
                'deps' => array('base'),
                'auto' => false,
            ),
            
            'slider' => array(
                'url'  => $css_url . 'ensemble-slider.css',
                'deps' => array('components'),
                'auto' => false,
            ),
            
            'gallery' => array(
                'url'  => $css_url . 'gallery.css',
                'deps' => array('components'),
                'auto' => false,
            ),
            
            'countdown' => array(
                'url'  => $css_url . 'ensemble-countdown.css',
                'deps' => array('base'),
                'auto' => false,
            ),
            
            // Ensemble-Layouts (shared layout utilities)
            'layouts' => array(
                'url'  => $css_url . 'ensemble-layouts.css',
                'deps' => array('components'),
                'auto' => true, // Needed for all layouts
            ),
            
            // =============================================
            // LEGACY - Full shortcodes.css fallback
            // =============================================
            'legacy' => array(
                'url'  => $css_url . 'shortcodes.css',
                'deps' => array(),
                'auto' => false,
            ),
        );
    }
    
    /**
     * Initialize the loader
     */
    public static function init() {
        // Check if selective loading is enabled via filter
        self::$enabled = apply_filters('ensemble_selective_css_loading', true);
        
        // Register styles early
        add_action('wp_enqueue_scripts', array(__CLASS__, 'register_styles'), 5);
        
        // Enqueue base styles
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_base_styles'), 6);
        
        // Enqueue queued styles (runs after shortcodes are processed)
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_queued_styles'), 50);
        
        // Enqueue layout styles last (highest priority)
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_layout_styles'), 100);
        
        // Auto-detect single pages
        add_action('wp', array(__CLASS__, 'auto_detect_context'));
    }
    
    /**
     * Register all CSS files (don't enqueue yet)
     */
    public static function register_styles() {
        $modules = self::get_modules();
        
        foreach ($modules as $name => $config) {
            $handle = 'ensemble-' . $name;
            
            // Build dependency handles
            $deps = array();
            foreach ($config['deps'] as $dep) {
                $deps[] = 'ensemble-' . $dep;
            }
            
            wp_register_style(
                $handle,
                $config['url'],
                $deps,
                ENSEMBLE_VERSION,
                'all'
            );
        }
    }
    
    /**
     * Enqueue base styles (always loaded)
     */
    public static function enqueue_base_styles() {
        // If selective loading is disabled, use legacy
        if (!self::$enabled) {
            wp_enqueue_style('ensemble-legacy');
            self::$loaded['legacy'] = true;
            return;
        }
        
        $modules = self::get_modules();
        
        foreach ($modules as $name => $config) {
            if (!empty($config['auto'])) {
                wp_enqueue_style('ensemble-' . $name);
                self::$loaded[$name] = true;
            }
        }
    }
    
    /**
     * Auto-detect context and queue appropriate CSS
     */
    public static function auto_detect_context() {
        if (!self::$enabled) {
            return;
        }
        
        // Single Event page
        if (is_singular('event') || is_singular('ensemble_event')) {
            self::enqueue('event-single');
            self::enqueue('lineup');
            self::enqueue('gallery');
        }
        
        // Single Artist page
        if (is_singular('ensemble_artist')) {
            self::enqueue('artists');
            self::enqueue('events'); // Shows upcoming events
        }
        
        // Single Location page
        if (is_singular('ensemble_location')) {
            self::enqueue('locations');
            self::enqueue('events'); // Shows upcoming events
        }
        
        // Archive pages
        if (is_post_type_archive('event') || is_post_type_archive('ensemble_event')) {
            self::enqueue('events');
        }
        
        if (is_post_type_archive('ensemble_artist')) {
            self::enqueue('artists');
        }
        
        if (is_post_type_archive('ensemble_location')) {
            self::enqueue('locations');
        }
    }
    
    /**
     * Enqueue styles that were queued by shortcodes
     */
    public static function enqueue_queued_styles() {
        foreach (self::$queued as $module => $queued) {
            if ($queued && !isset(self::$loaded[$module])) {
                self::load_module($module);
            }
        }
    }
    
    /**
     * Enqueue active layout styles (last for highest specificity)
     */
    public static function enqueue_layout_styles() {
        if (!class_exists('ES_Layout_Sets')) {
            return;
        }
        
        $active_set = ES_Layout_Sets::get_active_set();
        $set_data = ES_Layout_Sets::get_set_data($active_set);
        
        if (empty($set_data['path'])) {
            return;
        }
        
        $style_path = $set_data['path'] . '/style.css';
        
        if (!file_exists($style_path)) {
            return;
        }
        
        // Convert path to URL
        if (defined('ENSEMBLE_PLUGIN_DIR') && strpos($style_path, ENSEMBLE_PLUGIN_DIR) === 0) {
            $style_url = str_replace(ENSEMBLE_PLUGIN_DIR, ENSEMBLE_PLUGIN_URL, $style_path);
        } else {
            $style_url = str_replace(WP_CONTENT_DIR, WP_CONTENT_URL, $style_path);
        }
        
        // Layout CSS depends on components
        wp_enqueue_style(
            'ensemble-layout-' . $active_set,
            $style_url,
            array('ensemble-components'),
            ENSEMBLE_VERSION,
            'all'
        );
    }
    
    /**
     * Queue a CSS module for loading
     * Called from shortcodes
     * 
     * @param string|array $modules Module name(s)
     */
    public static function enqueue($modules) {
        // Legacy mode? Skip
        if (!self::$enabled) {
            return;
        }
        
        if (!is_array($modules)) {
            $modules = array($modules);
        }
        
        foreach ($modules as $module) {
            // If already loaded, skip
            if (isset(self::$loaded[$module])) {
                continue;
            }
            
            // Queue for loading
            self::$queued[$module] = true;
            
            // If wp_enqueue_scripts already ran, load immediately
            if (did_action('wp_enqueue_scripts')) {
                self::load_module($module);
            }
        }
    }
    
    /**
     * Load a specific module and its dependencies
     * 
     * @param string $module Module name
     */
    private static function load_module($module) {
        if (isset(self::$loaded[$module])) {
            return;
        }
        
        $modules = self::get_modules();
        
        if (!isset($modules[$module])) {
            return;
        }
        
        $config = $modules[$module];
        
        // Load dependencies first
        foreach ($config['deps'] as $dep) {
            self::load_module($dep);
        }
        
        // Enqueue this module
        wp_enqueue_style('ensemble-' . $module);
        self::$loaded[$module] = true;
    }
    
    /**
     * Check if a module is loaded
     * 
     * @param string $module
     * @return bool
     */
    public static function is_loaded($module) {
        return isset(self::$loaded[$module]);
    }
    
    /**
     * Get all loaded modules (for debugging)
     * 
     * @return array
     */
    public static function get_loaded_modules() {
        return array_keys(self::$loaded);
    }
    
    /**
     * Use legacy loading (all CSS in one file)
     * For backward compatibility
     */
    public static function use_legacy() {
        self::$enabled = false;
        wp_enqueue_style('ensemble-legacy');
    }
    
    /**
     * Check if selective loading is enabled
     */
    public static function is_enabled() {
        return self::$enabled;
    }
    
    /**
     * Debug: Get loaded size estimate
     * 
     * @return string Human readable size
     */
    public static function get_loaded_size_estimate() {
        $sizes = array(
            'base' => 13,
            'components' => 15,
            'responsive' => 9,
            'layouts' => 10,
            'events' => 13,
            'artists' => 7,
            'locations' => 10,
            'event-single' => 14,
            'lineup' => 13,
            'calendar' => 31,
            'slider' => 30,
            'gallery' => 8,
            'countdown' => 9,
            'legacy' => 73,
        );
        
        $total = 0;
        foreach (self::$loaded as $module => $loaded) {
            if (isset($sizes[$module])) {
                $total += $sizes[$module];
            }
        }
        
        return $total . ' KB';
    }
}

// Initialize on plugins_loaded
add_action('plugins_loaded', array('ES_CSS_Loader', 'init'));
