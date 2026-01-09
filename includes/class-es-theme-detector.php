<?php
/**
 * Ensemble Theme Detector
 * 
 * Detects active WordPress theme and extracts design values
 * 
 * @package Ensemble
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class ES_Theme_Detector {
    
    /**
     * Detected theme slug
     * @var string
     */
    private static $theme_slug = null;
    
    /**
     * Supported themes
     * @var array
     */
    private static $supported_themes = array(
        'oceanwp',
        'astra',
        'generatepress',
        'kadence',
        'blocksy',
        'neve',
    );
    
    /**
     * Detect active theme
     * 
     * @return string Theme slug or 'unknown'
     */
    public static function detect_theme() {
        if (self::$theme_slug !== null) {
            return self::$theme_slug;
        }
        
        $theme = wp_get_theme();
        $theme_name = strtolower($theme->get('Name'));
        $theme_template = strtolower($theme->get_template());
        
        // Check for known themes
        foreach (self::$supported_themes as $supported) {
            if (strpos($theme_name, $supported) !== false || strpos($theme_template, $supported) !== false) {
                self::$theme_slug = $supported;
                return $supported;
            }
        }
        
        // Check for FSE Block Themes (WordPress 5.9+)
        if (function_exists('wp_is_block_theme') && wp_is_block_theme()) {
            self::$theme_slug = 'block_theme';
            return 'block_theme';
        }
        
        self::$theme_slug = 'unknown';
        return 'unknown';
    }
    
    /**
     * Check if current theme is supported
     * 
     * @return bool
     */
    public static function is_theme_supported() {
        $theme = self::detect_theme();
        // Block themes are always supported via Global Styles API
        if ($theme === 'block_theme') {
            return true;
        }
        return in_array($theme, self::$supported_themes);
    }
    
    /**
     * Get theme display name
     * 
     * @return string
     */
    public static function get_theme_name() {
        $theme = wp_get_theme();
        return $theme->get('Name');
    }
    
    /**
     * Extract design values from active theme
     * 
     * @return array Design values
     */
    public static function get_theme_values() {
        $theme_slug = self::detect_theme();
        
        $method = 'get_' . $theme_slug . '_values';
        
        if (method_exists(__CLASS__, $method)) {
            return self::$method();
        }
        
        return self::get_fallback_values();
    }
    
    /**
     * OceanWP theme values
     * 
     * @return array
     */
    private static function get_oceanwp_values() {
        return array(
            'primary_color' => get_theme_mod('ocean_primary_color', '#13aff0'),
            'secondary_color' => get_theme_mod('ocean_hover_primary_color', '#0b7cac'),
            'background_color' => get_theme_mod('ocean_background_color', '#ffffff'),
            'text_color' => get_theme_mod('ocean_text_color', '#333333'),
            'heading_font' => get_theme_mod('ocean_headings_typography', array())['font-family'] ?? 'inherit',
            'body_font' => get_theme_mod('ocean_body_typography', array())['font-family'] ?? 'inherit',
            'button_bg' => get_theme_mod('ocean_primary_color', '#13aff0'),
            'button_text' => '#ffffff',
            'button_radius' => intval(get_theme_mod('ocean_buttons_border_radius', 3)),
            'card_radius' => 0, // OceanWP typically uses sharp corners
        );
    }
    
    /**
     * Astra theme values
     * 
     * @return array
     */
    private static function get_astra_values() {
        // Astra stores settings in options
        $astra_options = get_option('astra-settings', array());
        
        return array(
            'primary_color' => $astra_options['theme-color'] ?? '#0274be',
            'secondary_color' => $astra_options['link-h-color'] ?? '#3a3a3a',
            'background_color' => $astra_options['site-background']['background-color'] ?? '#ffffff',
            'text_color' => $astra_options['text-color'] ?? '#3a3a3a',
            'heading_font' => $astra_options['headings-font-family'] ?? 'inherit',
            'body_font' => $astra_options['body-font-family'] ?? 'inherit',
            'button_bg' => $astra_options['theme-color'] ?? '#0274be',
            'button_text' => '#ffffff',
            'button_radius' => intval($astra_options['button-radius'] ?? 2),
            'card_radius' => 0,
        );
    }
    
    /**
     * GeneratePress theme values
     * 
     * @return array
     */
    private static function get_generatepress_values() {
        return array(
            'primary_color' => get_theme_mod('generate_settings', array())['link_color'] ?? '#1e73be',
            'secondary_color' => get_theme_mod('generate_settings', array())['link_color_hover'] ?? '#000000',
            'background_color' => get_theme_mod('generate_settings', array())['background_color'] ?? '#ffffff',
            'text_color' => get_theme_mod('generate_settings', array())['text_color'] ?? '#3d3d3d',
            'heading_font' => get_theme_mod('generate_settings', array())['font_heading'] ?? 'inherit',
            'body_font' => get_theme_mod('generate_settings', array())['font_body'] ?? 'inherit',
            'button_bg' => get_theme_mod('generate_settings', array())['form_button_background_color'] ?? '#1e73be',
            'button_text' => get_theme_mod('generate_settings', array())['form_button_text_color'] ?? '#ffffff',
            'button_radius' => 3,
            'card_radius' => 0,
        );
    }
    
    /**
     * Kadence theme values
     * 
     * @return array
     */
    private static function get_kadence_values() {
        return array(
            'primary_color' => get_theme_mod('global_palette1', '#2B6CB0'),
            'secondary_color' => get_theme_mod('global_palette2', '#215387'),
            'background_color' => get_theme_mod('global_palette9', '#ffffff'),
            'text_color' => get_theme_mod('global_palette4', '#2D3748'),
            'heading_font' => get_theme_mod('heading_font', array())['family'] ?? 'inherit',
            'body_font' => get_theme_mod('base_font', array())['family'] ?? 'inherit',
            'button_bg' => get_theme_mod('global_palette1', '#2B6CB0'),
            'button_text' => '#ffffff',
            'button_radius' => intval(get_theme_mod('buttons_border_radius', 3)),
            'card_radius' => 8,
        );
    }
    
    /**
     * Blocksy theme values
     * 
     * @return array
     */
    private static function get_blocksy_values() {
        $palette = get_theme_mod('colorPalette', array());
        
        return array(
            'primary_color' => $palette['color1']['color'] ?? '#3366ff',
            'secondary_color' => $palette['color2']['color'] ?? '#222222',
            'background_color' => $palette['color8']['color'] ?? '#ffffff',
            'text_color' => $palette['color3']['color'] ?? '#54595f',
            'heading_font' => get_theme_mod('headingFont', array())['family'] ?? 'inherit',
            'body_font' => get_theme_mod('fontFamily', array())['family'] ?? 'inherit',
            'button_bg' => $palette['color1']['color'] ?? '#3366ff',
            'button_text' => '#ffffff',
            'button_radius' => intval(get_theme_mod('button_border_radius', 3)),
            'card_radius' => 7,
        );
    }
    
    /**
     * Neve theme values
     * 
     * @return array
     */
    private static function get_neve_values() {
        return array(
            'primary_color' => get_theme_mod('neve_button_color', array())['background'] ?? '#0366d6',
            'secondary_color' => get_theme_mod('neve_button_hover_color', array())['background'] ?? '#024aa8',
            'background_color' => get_theme_mod('background_color', '#ffffff'),
            'text_color' => get_theme_mod('neve_text_color', '#404248'),
            'heading_font' => get_theme_mod('neve_headings_font_family', 'inherit'),
            'body_font' => get_theme_mod('neve_body_font_family', 'inherit'),
            'button_bg' => get_theme_mod('neve_button_color', array())['background'] ?? '#0366d6',
            'button_text' => get_theme_mod('neve_button_color', array())['text'] ?? '#ffffff',
            'button_radius' => intval(get_theme_mod('neve_button_border_radius', array())['top'] ?? 3),
            'card_radius' => 0,
        );
    }
    
    /**
     * FSE Block Theme values (WordPress 5.9+)
     * Extracts colors from Global Styles / theme.json
     * 
     * @return array
     */
    private static function get_block_theme_values() {
        $values = self::get_fallback_values();
        
        // Try to get Global Styles data
        if (!function_exists('wp_get_global_settings')) {
            return $values;
        }
        
        $settings = wp_get_global_settings();
        
        // Extract color palette
        $palette = $settings['color']['palette']['theme'] ?? array();
        
        // Find primary color (usually first or named 'primary')
        foreach ($palette as $color) {
            $slug = strtolower($color['slug'] ?? '');
            $hex = $color['color'] ?? '';
            
            if (in_array($slug, array('primary', 'accent', 'brand', 'base'))) {
                $values['primary_color'] = $hex;
                $values['button_bg'] = $hex;
                break;
            }
        }
        
        // Find secondary color
        foreach ($palette as $color) {
            $slug = strtolower($color['slug'] ?? '');
            $hex = $color['color'] ?? '';
            
            if (in_array($slug, array('secondary', 'contrast', 'accent-2'))) {
                $values['secondary_color'] = $hex;
                break;
            }
        }
        
        // Find background color
        foreach ($palette as $color) {
            $slug = strtolower($color['slug'] ?? '');
            $hex = $color['color'] ?? '';
            
            if (in_array($slug, array('background', 'base', 'base-2', 'white'))) {
                $values['background_color'] = $hex;
                break;
            }
        }
        
        // Find text color
        foreach ($palette as $color) {
            $slug = strtolower($color['slug'] ?? '');
            $hex = $color['color'] ?? '';
            
            if (in_array($slug, array('foreground', 'contrast', 'text', 'body', 'black'))) {
                $values['text_color'] = $hex;
                break;
            }
        }
        
        // Extract typography
        $font_families = $settings['typography']['fontFamilies']['theme'] ?? array();
        
        foreach ($font_families as $font) {
            $slug = strtolower($font['slug'] ?? '');
            $family = $font['fontFamily'] ?? 'inherit';
            
            if (in_array($slug, array('heading', 'headings', 'display'))) {
                $values['heading_font'] = $family;
            } elseif (in_array($slug, array('body', 'text', 'base', 'system'))) {
                $values['body_font'] = $family;
            }
        }
        
        // Get border radius if available
        if (!empty($settings['custom']['button']['border']['radius'])) {
            $values['button_radius'] = intval($settings['custom']['button']['border']['radius']);
        }
        
        return $values;
    }
    
    /**
     * Fallback values when theme is not recognized
     * Try to extract some generic values
     * 
     * @return array
     */
    private static function get_fallback_values() {
        // Try generic WordPress Customizer values
        return array(
            'primary_color' => get_theme_mod('primary_color', '#667eea'),
            'secondary_color' => get_theme_mod('secondary_color', '#764ba2'),
            'background_color' => get_theme_mod('background_color', '#ffffff'),
            'text_color' => get_theme_mod('text_color', '#1a202c'),
            'heading_font' => 'inherit',
            'body_font' => 'inherit',
            'button_bg' => get_theme_mod('primary_color', '#667eea'),
            'button_text' => '#ffffff',
            'button_radius' => 4,
            'card_radius' => 8,
        );
    }
    
    /**
     * Get theme info for display
     * 
     * @return array
     */
    public static function get_theme_info() {
        $theme_slug = self::detect_theme();
        $theme_name = self::get_theme_name();
        $is_supported = self::is_theme_supported();
        
        $info = array(
            'slug' => $theme_slug,
            'name' => $theme_name,
            'supported' => $is_supported,
            'message' => '',
        );
        
        if ($is_supported) {
            $info['message'] = sprintf(
                __('Theme "%s" is supported. Colors and fonts can be imported automatically.', 'ensemble'),
                $theme_name
            );
        } else {
            $info['message'] = sprintf(
                __('Theme "%s" is not yet supported. Generic values will be used.', 'ensemble'),
                $theme_name
            );
        }
        
        return $info;
    }
    
    /**
     * Get preview of theme values
     * 
     * @return array
     */
    public static function get_theme_values_preview() {
        $values = self::get_theme_values();
        $theme_info = self::get_theme_info();
        
        return array(
            'theme' => $theme_info,
            'values' => $values,
        );
    }
}
