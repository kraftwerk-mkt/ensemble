<?php
/**
 * Template Loader
 * Loads correct templates based on active layout set
 * 
 * @package Ensemble
 */

if (!defined('ABSPATH')) exit;

class ES_Template_Loader {
    
    /**
     * Initialize template loader
     */
    public static function init() {
        // Hook into template hierarchy
        add_filter('single_template', array(__CLASS__, 'load_single_template'), 999);
        add_filter('archive_template', array(__CLASS__, 'load_archive_template'), 999);
    }
    
    /**
     * Load single template based on active set
     */
    public static function load_single_template($template) {
        global $post;
        
        // Get configured post type
        $event_post_type = ensemble_get_post_type();
        
        // Check if it's an Ensemble custom post type (always use Ensemble templates)
        $ensemble_types = array('ensemble_event', 'ensemble_artist', 'ensemble_location', 'ensemble_gallery');
        
        if (in_array($post->post_type, $ensemble_types)) {
            // It's a dedicated Ensemble post type - proceed with template loading
        } elseif ($post->post_type === $event_post_type && $event_post_type === 'post') {
            // Using 'post' as event type - only treat as event if it has ensemble_category
            $terms = get_the_terms($post->ID, 'ensemble_category');
            if (!$terms || is_wp_error($terms)) {
                // No ensemble_category = regular blog post, use theme template
                return $template;
            }
        } elseif ($post->post_type === $event_post_type) {
            // Custom post type configured as event type - proceed
        } else {
            // Not an Ensemble-related post type
            return $template;
        }
        
        // Get active set
        $active_set = ES_Layout_Sets::get_active_set();
        
        // Determine template file based on post type
        $template_file = '';
        
        // If it's the configured event post type, use event template
        if ($post->post_type === $event_post_type || $post->post_type === 'ensemble_event') {
            $template_file = 'single-event.php';
        } elseif ($post->post_type === 'ensemble_artist') {
            // Check for custom layout preference
            $artist_layout = get_post_meta($post->ID, '_artist_single_layout', true);
            
            if ($artist_layout === 'featured' || $artist_layout === 'featuredslider') {
                // Use a wrapper template that outputs the featured shortcode
                $featured_template = ENSEMBLE_PLUGIN_DIR . 'templates/single-artist-featured-wrapper.php';
                if (file_exists($featured_template)) {
                    return $featured_template;
                }
            }
            
            $template_file = 'single-artist.php';
        } elseif ($post->post_type === 'ensemble_location') {
            $template_file = 'single-location.php';
        } elseif ($post->post_type === 'ensemble_gallery') {
            $template_file = 'single-gallery.php';
        }
        
        if (!$template_file) {
            return $template;
        }
        
        // Build template paths (in order of priority)
        $template_paths = array(
            // 1. Theme override for specific set
            get_stylesheet_directory() . '/ensemble/layouts/' . $active_set . '/' . $template_file,
            get_template_directory() . '/ensemble/layouts/' . $active_set . '/' . $template_file,
            
            // 2. Plugin set template
            ENSEMBLE_PLUGIN_DIR . 'templates/layouts/' . $active_set . '/' . $template_file,
            
            // 3. Fallback to modern set (default)
            ENSEMBLE_PLUGIN_DIR . 'templates/layouts/modern/' . $template_file,
            
            // 4. Theme default ensemble template
            get_stylesheet_directory() . '/ensemble/' . $template_file,
            get_template_directory() . '/ensemble/' . $template_file,
            
            // 5. Plugin default template (if exists)
            ENSEMBLE_PLUGIN_DIR . 'templates/' . $template_file,
        );
        
        // Find first existing template
        foreach ($template_paths as $path) {
            if (file_exists($path)) {
                // Add debug info in HTML comment
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    add_action('wp_footer', function() use ($path, $active_set, $event_post_type) {
                        echo '<!-- Ensemble Template: ' . basename($path) . ' -->';
                        echo '<!-- Active Set: ' . $active_set . ' -->';
                        echo '<!-- Post Type: ' . $event_post_type . ' -->';
                    });
                }
                return $path;
            }
        }
        
        // Ultimate fallback - return original template
        return $template;
    }
    
    /**
     * Load archive template (optional - for future use)
     */
    public static function load_archive_template($template) {
        // Could be used for archive pages
        return $template;
    }
    
    /**
     * Locate template file
     * Helper function for other components with caching
     */
    public static function locate_template($template_name, $set = null) {
        
        if (!$set) {
            $set = ES_Layout_Sets::get_active_set();
        }
        
        // Check cache first
        $cache_key = 'ensemble_template_' . $set . '_' . md5($template_name);
        $cached_path = wp_cache_get($cache_key, 'ensemble');
        
        if ($cached_path !== false && file_exists($cached_path)) {
            return $cached_path;
        }
        
        $template_paths = array(
            get_stylesheet_directory() . '/ensemble/layouts/' . $set . '/' . $template_name,
            get_template_directory() . '/ensemble/layouts/' . $set . '/' . $template_name,
            ENSEMBLE_PLUGIN_DIR . 'templates/layouts/' . $set . '/' . $template_name,
            ENSEMBLE_PLUGIN_DIR . 'templates/layouts/modern/' . $template_name,
            get_stylesheet_directory() . '/ensemble/' . $template_name,
            get_template_directory() . '/ensemble/' . $template_name,
            ENSEMBLE_PLUGIN_DIR . 'templates/' . $template_name,
        );
        
        foreach ($template_paths as $path) {
            if (file_exists($path)) {
                // Cache the found template path
                wp_cache_set($cache_key, $path, 'ensemble', 3600); // Cache for 1 hour
                return $path;
            }
        }
        
        return false;
    }
    
    /**
     * Clear template cache
     * Useful after template changes or plugin updates
     */
    public static function clear_template_cache() {
        wp_cache_flush();
    }
}
