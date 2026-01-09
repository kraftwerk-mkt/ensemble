<?php
/**
 * Ensemble Layout Manager
 * 
 * Central management system for all layouts including Template Park integration
 * 
 * @package Ensemble
 */

class ES_Layout_Manager {
    
    /**
     * Layout sources
     */
    const SOURCE_CORE = 'core';
    const SOURCE_TEMPLATE_PARK = 'template_park';
    const SOURCE_CUSTOM = 'custom';
    
    /**
     * Get all available layouts from all sources
     * 
     * @return array All layouts grouped by source
     */
    public static function get_all_layouts() {
        $layouts = [];
        
        // Core Layouts (always available)
        $layouts[self::SOURCE_CORE] = self::get_core_layouts();
        
        // Template Park Layouts (if installed)
        if (self::is_template_park_active()) {
            $layouts[self::SOURCE_TEMPLATE_PARK] = self::get_template_park_layouts();
        }
        
        // Custom Layouts (user uploaded)
        $layouts[self::SOURCE_CUSTOM] = self::get_custom_layouts();
        
        return $layouts;
    }
    
    /**
     * Get flattened list of all layouts for shortcode use
     * 
     * @return array Layout key => Layout data
     */
    public static function get_layouts_flat() {
        $all_layouts = self::get_all_layouts();
        $flat = [];
        
        foreach ($all_layouts as $source => $layouts) {
            foreach ($layouts as $key => $layout) {
                // Prefix with source to avoid conflicts
                $flat_key = $source === self::SOURCE_CORE ? $key : $source . '_' . $key;
                $layout['source'] = $source;
                $layout['original_key'] = $key;
                $flat[$flat_key] = $layout;
            }
        }
        
        return $flat;
    }
    
    /**
     * Get core layouts (5 basis layouts)
     * 
     * @return array Core layouts
     */
    private static function get_core_layouts() {
        return ES_Event_Layouts::get_layouts();
    }
    
    /**
     * Check if Template Park is installed and active
     * 
     * @return bool
     */
    public static function is_template_park_active() {
        // Check if Template Park plugin/add-on is active
        return class_exists('ES_Template_Park') || 
               function_exists('ensemble_template_park_init') ||
               defined('ENSEMBLE_TEMPLATE_PARK_VERSION');
    }
    
    /**
     * Get Template Park layouts
     * 
     * @return array Template Park layouts or empty array
     */
    private static function get_template_park_layouts() {
        if (!self::is_template_park_active()) {
            return [];
        }
        
        // Hook for Template Park to provide layouts
        $layouts = apply_filters('ensemble_template_park_layouts', []);
        
        // Fallback if Template Park class exists
        if (empty($layouts) && class_exists('ES_Template_Park')) {
            $layouts = ES_Template_Park::get_layouts();
        }
        
        return $layouts;
    }
    
    /**
     * Get custom user layouts
     * 
     * @return array Custom layouts
     */
    private static function get_custom_layouts() {
        $custom_layouts = get_option('ensemble_custom_layouts', []);
        
        // Allow plugins to add custom layouts
        return apply_filters('ensemble_custom_layouts', $custom_layouts);
    }
    
    /**
     * Render a layout
     * 
     * @param string $layout_key Full layout key (with or without source prefix)
     * @param array $event Event data
     * @return string HTML output
     */
    public static function render_layout($layout_key, $event) {
        // Parse layout key to determine source
        $parsed = self::parse_layout_key($layout_key);
        $source = $parsed['source'];
        $key = $parsed['key'];
        
        // Render based on source
        switch ($source) {
            case self::SOURCE_CORE:
                return ES_Event_Layouts::render_layout($key, $event);
                
            case self::SOURCE_TEMPLATE_PARK:
                if (self::is_template_park_active() && class_exists('ES_Template_Park')) {
                    return ES_Template_Park::render_layout($key, $event);
                }
                break;
                
            case self::SOURCE_CUSTOM:
                return self::render_custom_layout($key, $event);
        }
        
        // Fallback to classic
        return ES_Event_Layouts::render_layout('classic', $event);
    }
    
    /**
     * Get CSS for a layout
     * 
     * @param string $layout_key Layout key
     * @return string CSS code
     */
    public static function get_layout_css($layout_key) {
        $parsed = self::parse_layout_key($layout_key);
        $source = $parsed['source'];
        $key = $parsed['key'];
        
        switch ($source) {
            case self::SOURCE_CORE:
                return ES_Event_Layouts::get_layout_css($key);
                
            case self::SOURCE_TEMPLATE_PARK:
                if (self::is_template_park_active() && class_exists('ES_Template_Park')) {
                    return ES_Template_Park::get_layout_css($key);
                }
                break;
                
            case self::SOURCE_CUSTOM:
                return self::get_custom_layout_css($key);
        }
        
        return '';
    }
    
    /**
     * Parse layout key to determine source and actual key
     * 
     * @param string $layout_key Layout key (e.g., "classic" or "template_park_premium_card")
     * @return array ['source' => string, 'key' => string]
     */
    private static function parse_layout_key($layout_key) {
        // Check for prefixes
        if (strpos($layout_key, 'template_park_') === 0) {
            return [
                'source' => self::SOURCE_TEMPLATE_PARK,
                'key' => str_replace('template_park_', '', $layout_key)
            ];
        }
        
        if (strpos($layout_key, 'custom_') === 0) {
            return [
                'source' => self::SOURCE_CUSTOM,
                'key' => str_replace('custom_', '', $layout_key)
            ];
        }
        
        // Check if it's a core layout
        $core_layouts = self::get_core_layouts();
        if (isset($core_layouts[$layout_key])) {
            return [
                'source' => self::SOURCE_CORE,
                'key' => $layout_key
            ];
        }
        
        // Check Template Park without prefix (for backwards compat)
        if (self::is_template_park_active()) {
            $tp_layouts = self::get_template_park_layouts();
            if (isset($tp_layouts[$layout_key])) {
                return [
                    'source' => self::SOURCE_TEMPLATE_PARK,
                    'key' => $layout_key
                ];
            }
        }
        
        // Check custom without prefix
        $custom_layouts = self::get_custom_layouts();
        if (isset($custom_layouts[$layout_key])) {
            return [
                'source' => self::SOURCE_CUSTOM,
                'key' => $layout_key
            ];
        }
        
        // Default to core
        return [
            'source' => self::SOURCE_CORE,
            'key' => $layout_key
        ];
    }
    
    /**
     * Render custom layout
     * 
     * @param string $key Custom layout key
     * @param array $event Event data
     * @return string HTML
     */
    private static function render_custom_layout($key, $event) {
        $custom_layouts = self::get_custom_layouts();
        
        if (!isset($custom_layouts[$key])) {
            return ES_Event_Layouts::render_layout('classic', $event);
        }
        
        $layout = $custom_layouts[$key];
        
        // Custom layouts can be stored as templates or file paths
        if (isset($layout['template'])) {
            // Template string
            return self::process_template($layout['template'], $event);
        } elseif (isset($layout['file'])) {
            // Template file
            return self::load_template_file($layout['file'], $event);
        }
        
        return '';
    }
    
    /**
     * Get custom layout CSS
     * 
     * @param string $key Custom layout key
     * @return string CSS
     */
    private static function get_custom_layout_css($key) {
        $custom_layouts = self::get_custom_layouts();
        
        if (!isset($custom_layouts[$key]['css'])) {
            return '';
        }
        
        return $custom_layouts[$key]['css'];
    }
    
    /**
     * Process template string with event data
     * 
     * @param string $template Template string
     * @param array $event Event data
     * @return string Processed HTML
     */
    private static function process_template($template, $event) {
        // Simple variable replacement
        foreach ($event as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $template = str_replace('{{' . $key . '}}', esc_html($value), $template);
            }
        }
        
        return $template;
    }
    
    /**
     * Load template from file
     * 
     * @param string $file Template file path
     * @param array $event Event data
     * @return string HTML
     */
    private static function load_template_file($file, $event) {
        if (!file_exists($file)) {
            return '';
        }
        
        ob_start();
        include $file;
        return ob_get_clean();
    }
    
    /**
     * Register a custom layout
     * 
     * @param string $key Unique layout key
     * @param array $layout_data Layout configuration
     * @return bool Success
     */
    public static function register_custom_layout($key, $layout_data) {
        $custom_layouts = get_option('ensemble_custom_layouts', []);
        $custom_layouts[$key] = $layout_data;
        return update_option('ensemble_custom_layouts', $custom_layouts);
    }
    
    /**
     * Unregister a custom layout
     * 
     * @param string $key Layout key
     * @return bool Success
     */
    public static function unregister_custom_layout($key) {
        $custom_layouts = get_option('ensemble_custom_layouts', []);
        unset($custom_layouts[$key]);
        return update_option('ensemble_custom_layouts', $custom_layouts);
    }
    
    /**
     * Get layout metadata
     * 
     * @param string $layout_key Layout key
     * @return array Layout metadata
     */
    public static function get_layout_info($layout_key) {
        $all_layouts = self::get_layouts_flat();
        
        if (!isset($all_layouts[$layout_key])) {
            return null;
        }
        
        return $all_layouts[$layout_key];
    }
    
    /**
     * Check if layout requires pro/template park
     * 
     * @param string $layout_key Layout key
     * @return bool
     */
    public static function is_pro_layout($layout_key) {
        $info = self::get_layout_info($layout_key);
        
        if (!$info) {
            return false;
        }
        
        return $info['source'] === self::SOURCE_TEMPLATE_PARK;
    }
    
    /**
     * Get upgrade URL for Template Park
     * 
     * @return string URL
     */
    public static function get_template_park_upgrade_url() {
        return apply_filters(
            'ensemble_template_park_upgrade_url', 
            'https://kraftwerk-mkt.com/ensemble-template-park'
        );
    }
}