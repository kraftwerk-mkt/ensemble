<?php
/**
 * Assets Loader
 * Loads correct CSS and JS based on active layout set
 * 
 * @package Ensemble
 */

if (!defined('ABSPATH')) exit;

class ES_Assets_Loader {
    
    /**
     * Initialize assets loading
     */
    public static function init() {
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_admin_assets'));
        
        // Add body class for layout mode (dark/light)
        add_filter('body_class', array(__CLASS__, 'add_layout_mode_class'));
    }
    
    /**
     * Add layout mode class to body
     * This enables dark mode CSS variables for dark layouts like Modern
     * 
     * @param array $classes Body classes
     * @return array Modified classes
     */
    public static function add_layout_mode_class($classes) {
        if (class_exists('ES_Layout_Sets')) {
            $mode = ES_Layout_Sets::get_active_mode();
            
            if ($mode === 'dark') {
                $classes[] = 'es-mode-dark';
            } else {
                $classes[] = 'es-mode-light';
            }
            
            // Also add active layout set as class
            $active_set = ES_Layout_Sets::get_active_set();
            $classes[] = 'es-layout-' . sanitize_html_class($active_set);
        }
        
        return $classes;
    }
    
    /**
     * Enqueue frontend assets
     * Note: CSS loading is handled by ES_Assets class
     * This only handles frontend JavaScript
     */
    public static function enqueue_frontend_assets() {
        
        // Get active layout set
        $active_set = ES_Layout_Sets::get_active_set();
        
        // Frontend JavaScript (if needed)
        wp_enqueue_script(
            'ensemble-frontend',
            ENSEMBLE_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            ENSEMBLE_VERSION,
            true
        );
        
        // Localize script with settings
        wp_localize_script('ensemble-frontend', 'ensembleSettings', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ensemble_frontend'),
            'activeSet' => $active_set,
        ));
    }
    
    /**
     * Enqueue admin assets
     */
    public static function enqueue_admin_assets($hook) {
        
        // Only load on Ensemble admin pages
        if (strpos($hook, 'ensemble') === false) {
            return;
        }
        
        // Admin CSS
        wp_enqueue_style(
            'ensemble-admin',
            ENSEMBLE_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            ENSEMBLE_VERSION
        );
        
        // Admin JavaScript
        wp_enqueue_script(
            'ensemble-admin',
            ENSEMBLE_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-color-picker'),
            ENSEMBLE_VERSION,
            true
        );
        
        // WordPress Color Picker
        wp_enqueue_style('wp-color-picker');
        
        // Localize admin script
        wp_localize_script('ensemble-admin', 'ensembleAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ensemble_admin'),
        ));
    }
    
    /**
     * Get inline CSS for design settings
     * (Optional: for design customizer)
     */
    public static function get_inline_css() {
        $css = '';
        
        // Get design settings (if implemented)
        $primary_color = get_option('ensemble_primary_color', '#667eea');
        $secondary_color = get_option('ensemble_secondary_color', '#764ba2');
        
        // Generate CSS variables
        $css .= ':root {';
        $css .= '--es-primary: ' . esc_attr($primary_color) . ';';
        $css .= '--es-secondary: ' . esc_attr($secondary_color) . ';';
        $css .= '}';
        
        return $css;
    }
    
    /**
     * Output inline CSS in head
     */
    public static function output_inline_css() {
        $css = self::get_inline_css();
        if ($css) {
            echo '<style id="ensemble-custom-css">' . $css . '</style>';
        }
    }
}

// Initialize
ES_Assets_Loader::init();
add_action('wp_head', array('ES_Assets_Loader', 'output_inline_css'), 99);
