<?php
/**
 * Ensemble Design Settings Manager
 * 
 * Handles storage, retrieval, and management of frontend design configurations
 * 
 * @package Ensemble
 * @since 1.9.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class ES_Design_Settings {
    
    /**
     * Option name for storing design settings
     */
    const OPTION_NAME = 'ensemble_design_settings';
    
    /**
     * Current active template
     */
    const ACTIVE_TEMPLATE_OPTION = 'ensemble_active_template';
    
    /**
     * Default design configuration
     */
    private static $defaults = array(
        // Design Mode
        'design_mode' => 'custom', // 'custom' or 'theme'
        
        // =====================
        // LIGHT MODE COLORS
        // =====================
        'primary_color' => '#667eea',
        'secondary_color' => '#764ba2',
        'background_color' => '#ffffff',
        'text_color' => '#1a202c',
        'text_secondary' => '#718096',
        'text_muted' => '#a0aec0',
        'card_background' => '#ffffff',
        'card_border' => '#e2e8f0',
        'border_color' => '#e2e8f0',
        'hover_color' => '#5568d3',
        'link_color' => '#667eea',
        
        // Surface & Dividers
        'surface_color' => '#ffffff',
        'divider_color' => '#e2e8f0',
        
        // Overlay (Text Ã¼ber Bildern)
        'overlay_bg' => 'rgba(0, 0, 0, 0.7)',
        'overlay_text' => '#ffffff',
        'overlay_text_secondary' => 'rgba(255, 255, 255, 0.8)',
        'overlay_text_muted' => 'rgba(255, 255, 255, 0.6)',
        'overlay_border' => 'rgba(255, 255, 255, 0.2)',
        
        // Placeholder (fehlende Bilder)
        'placeholder_bg' => '#e2e8f0',
        'placeholder_icon' => '#a0aec0',
        
        // Status-Farben
        'status_cancelled' => '#dc2626',
        'status_soldout' => '#1a202c',
        'status_postponed' => '#d97706',
        
        // Gradients
        'gradient_start' => 'rgba(0, 0, 0, 0.8)',
        'gradient_mid' => 'rgba(0, 0, 0, 0.4)',
        'gradient_end' => 'transparent',
        
        // Social
        'facebook_color' => '#1877f2',
        
        // =====================
        // DARK MODE COLORS
        // =====================
        'dark_primary_color' => '#818cf8',
        'dark_secondary_color' => '#a78bfa',
        'dark_background_color' => '#0a0a0a',
        'dark_text_color' => '#ffffff',
        'dark_text_secondary' => '#e0e0e0',
        'dark_text_muted' => '#666666',
        'dark_card_background' => '#1a1a1a',
        'dark_card_border' => '#333333',
        'dark_border_color' => '#333333',
        'dark_hover_color' => '#6366f1',
        'dark_link_color' => '#818cf8',
        
        // Dark Surface & Dividers
        'dark_surface_color' => '#111111',
        'dark_divider_color' => '#333333',
        
        // Dark Overlay
        'dark_overlay_bg' => 'rgba(255, 255, 255, 0.9)',
        'dark_overlay_text' => '#111111',
        'dark_overlay_text_secondary' => 'rgba(0, 0, 0, 0.7)',
        'dark_overlay_text_muted' => 'rgba(0, 0, 0, 0.5)',
        'dark_overlay_border' => 'rgba(0, 0, 0, 0.2)',
        
        // Dark Placeholder
        'dark_placeholder_bg' => '#2a2a2a',
        'dark_placeholder_icon' => 'rgba(255, 255, 255, 0.3)',
        
        // Typography - Base Sizes
        'heading_font' => 'Inter',
        'body_font' => 'Inter',
        'h1_size' => 36,
        'h2_size' => 28,
        'h3_size' => 24,
        'body_size' => 16,
        'small_size' => 14,
        
        // Typography - Extended Sizes (for specific use cases)
        'xs_size' => 12,        // Labels, badges, fine print
        'meta_size' => 14,      // Meta information, secondary text
        'lg_size' => 18,        // Slightly larger body text
        'xl_size' => 20,        // Subtitles, emphasized text
        'price_size' => 32,     // Price displays
        'hero_size' => 72,      // Hero titles, large headlines
        
        // Typography - Weights & Line Heights
        'heading_weight' => 700,
        'body_weight' => 400,
        'line_height' => 1.6,
        'line_height_heading' => 1.2,
        'line_height_body' => 1.6,
        
        // Buttons
        'button_bg' => '#667eea',
        'button_text' => '#ffffff',
        'button_bg_hover' => '#5568d3',
        'button_hover_bg' => '#5568d3',
        'button_hover_text' => '#ffffff',
        'button_radius' => 8,
        'button_padding_v' => 12,
        'button_padding_h' => 24,
        'button_font_size' => 16,
        'button_weight' => 600,
        'button_border_width' => 2,
        'button_style' => 'solid', // solid, outline, outline, gradient
        'button_hover_effect' => 'scale', // scale, shadow, slide, none
        
        // Dark Mode Buttons
        'dark_button_bg' => '#818cf8',
        'dark_button_text' => '#0a0a0a',
        'dark_button_hover_bg' => '#6366f1',
        'dark_button_hover_text' => '#ffffff',
        
        // Event Cards
        'card_radius' => 12,
        'card_padding' => 25,
        'card_shadow' => 'medium', // none, light, medium, heavy
        'card_hover' => 'lift', // lift, glow, border, none
        'card_border_width' => 0,
        'card_image_height' => 200,
        'card_image_fit' => 'cover', // cover, contain
        
        // Layout
        'container_width' => 1200,
        'grid_columns' => 3,
        'grid_columns_tablet' => 2,
        'grid_columns_mobile' => 1,
        'grid_gap' => 24,
        'card_gap' => 24,
        'section_spacing' => 48,
        
        // Calendar
        'calendar_header_bg' => '#667eea',
        'calendar_header_text' => '#ffffff',
        'calendar_cell_bg' => '#ffffff',
        'calendar_cell_hover' => '#f7fafc',
        'calendar_today_bg' => '#667eea',
        'calendar_today_text' => '#ffffff',
        'calendar_event_bg' => '#667eea',
        
        // Dark Mode Calendar
        'dark_calendar_header_bg' => '#1a1a1a',
        'dark_calendar_header_text' => '#fafafa',
        'dark_calendar_cell_bg' => '#0a0a0a',
        'dark_calendar_cell_hover' => '#252525',
        'dark_calendar_today_bg' => '#818cf8',
        'dark_calendar_today_text' => '#0a0a0a',
        'dark_calendar_event_bg' => '#818cf8',
        
        // Filters
        'filter_bg' => '#f7fafc',
        'dark_filter_bg' => '#1a1a1a',
        'filter_position' => 'above',
        
        // =====================
        // EXTENDED - UI Components
        // =====================
        
        // Focus Ring (Accessibility)
        'focus_ring_color' => '#667eea',
        'focus_ring_width' => 3,
        'focus_ring_offset' => 2,
        'focus_ring_style' => 'solid', // solid, dashed, dotted
        'dark_focus_ring_color' => '#818cf8',
        
        // Form Inputs
        'input_bg' => '#ffffff',
        'input_text' => '#1a202c',
        'input_border' => '#e2e8f0',
        'input_border_width' => 1,
        'input_radius' => 6,
        'input_padding_v' => 10,
        'input_padding_h' => 14,
        'input_placeholder' => '#a0aec0',
        'input_focus_border' => '#667eea',
        'input_focus_shadow' => 'rgba(102, 126, 234, 0.25)',
        'input_error_border' => '#dc2626',
        'input_success_border' => '#10b981',
        'dark_input_bg' => '#1a1a1a',
        'dark_input_text' => '#ffffff',
        'dark_input_border' => '#333333',
        'dark_input_placeholder' => '#666666',
        'dark_input_focus_border' => '#818cf8',
        'dark_input_focus_shadow' => 'rgba(129, 140, 248, 0.25)',
        
        // Badges & Tags
        'badge_bg' => '#e2e8f0',
        'badge_text' => '#1a202c',
        'badge_radius' => 4,
        'badge_padding_v' => 4,
        'badge_padding_h' => 8,
        'badge_font_size' => 12,
        'badge_font_weight' => 600,
        'badge_primary_bg' => '#667eea',
        'badge_primary_text' => '#ffffff',
        'badge_success_bg' => '#10b981',
        'badge_success_text' => '#ffffff',
        'badge_warning_bg' => '#f59e0b',
        'badge_warning_text' => '#1a202c',
        'badge_error_bg' => '#dc2626',
        'badge_error_text' => '#ffffff',
        'dark_badge_bg' => '#333333',
        'dark_badge_text' => '#e0e0e0',
        
        // Tooltips
        'tooltip_bg' => '#1a202c',
        'tooltip_text' => '#ffffff',
        'tooltip_radius' => 6,
        'tooltip_padding_v' => 8,
        'tooltip_padding_h' => 12,
        'tooltip_font_size' => 13,
        'tooltip_shadow' => '0 4px 12px rgba(0, 0, 0, 0.15)',
        'tooltip_arrow_size' => 6,
        'dark_tooltip_bg' => '#fafafa',
        'dark_tooltip_text' => '#1a202c',
        
        // Scrollbar
        'scrollbar_width' => 8,
        'scrollbar_track' => '#f1f5f9',
        'scrollbar_thumb' => '#cbd5e1',
        'scrollbar_thumb_hover' => '#94a3b8',
        'scrollbar_radius' => 4,
        'dark_scrollbar_track' => '#1a1a1a',
        'dark_scrollbar_thumb' => '#404040',
        'dark_scrollbar_thumb_hover' => '#525252',
        
        // Loading States
        'loading_spinner_color' => '#667eea',
        'loading_spinner_size' => 40,
        'loading_overlay_bg' => 'rgba(255, 255, 255, 0.8)',
        'dark_loading_spinner_color' => '#818cf8',
        'dark_loading_overlay_bg' => 'rgba(0, 0, 0, 0.8)',
        
        // Skeleton Loading
        'skeleton_bg' => '#e2e8f0',
        'skeleton_highlight' => '#f8fafc',
        'skeleton_radius' => 4,
        'dark_skeleton_bg' => '#333333',
        'dark_skeleton_highlight' => '#404040',
        
        // Dropdown/Select
        'dropdown_bg' => '#ffffff',
        'dropdown_border' => '#e2e8f0',
        'dropdown_shadow' => '0 4px 16px rgba(0, 0, 0, 0.12)',
        'dropdown_radius' => 8,
        'dropdown_item_hover' => '#f7fafc',
        'dropdown_item_active' => '#667eea',
        'dropdown_item_active_text' => '#ffffff',
        'dark_dropdown_bg' => '#1a1a1a',
        'dark_dropdown_border' => '#333333',
        'dark_dropdown_shadow' => '0 4px 16px rgba(0, 0, 0, 0.4)',
        'dark_dropdown_item_hover' => '#252525',
        
        // Modals
        'modal_bg' => '#ffffff',
        'modal_border' => '#e2e8f0',
        'modal_radius' => 12,
        'modal_shadow' => '0 20px 60px rgba(0, 0, 0, 0.2)',
        'modal_backdrop' => 'rgba(0, 0, 0, 0.5)',
        'modal_header_border' => '#e2e8f0',
        'modal_footer_bg' => '#f7fafc',
        'dark_modal_bg' => '#1a1a1a',
        'dark_modal_border' => '#333333',
        'dark_modal_backdrop' => 'rgba(0, 0, 0, 0.7)',
        'dark_modal_header_border' => '#333333',
        'dark_modal_footer_bg' => '#111111',
        
        // Tables
        'table_header_bg' => '#f7fafc',
        'table_header_text' => '#1a202c',
        'table_row_bg' => '#ffffff',
        'table_row_alt_bg' => '#f7fafc',
        'table_row_hover' => '#edf2f7',
        'table_border' => '#e2e8f0',
        'dark_table_header_bg' => '#1a1a1a',
        'dark_table_header_text' => '#fafafa',
        'dark_table_row_bg' => '#0a0a0a',
        'dark_table_row_alt_bg' => '#111111',
        'dark_table_row_hover' => '#1a1a1a',
        'dark_table_border' => '#333333',
        
        // Pagination
        'pagination_bg' => '#ffffff',
        'pagination_text' => '#1a202c',
        'pagination_border' => '#e2e8f0',
        'pagination_hover_bg' => '#f7fafc',
        'pagination_active_bg' => '#667eea',
        'pagination_active_text' => '#ffffff',
        'pagination_radius' => 6,
        'dark_pagination_bg' => '#1a1a1a',
        'dark_pagination_text' => '#e0e0e0',
        'dark_pagination_border' => '#333333',
        'dark_pagination_hover_bg' => '#252525',
        'dark_pagination_active_bg' => '#818cf8',
    );
    
    /**
     * Initialize the class
     */
    public static function init() {
        // Set default template on first activation
        if (get_option(self::ACTIVE_TEMPLATE_OPTION) === false) {
            update_option(self::ACTIVE_TEMPLATE_OPTION, 'classic-blue');
        }
        
        // Set default settings if not exist
        if (get_option(self::OPTION_NAME) === false) {
            self::reset_to_defaults();
        }
    }
    
    /**
     * Get all design settings
     * 
     * @return array Design settings
     */
    public static function get_settings() {
        $settings = get_option(self::OPTION_NAME, array());
        $defaults = self::get_effective_defaults();
        return wp_parse_args($settings, $defaults);
    }
    
    /**
     * Get settings for a specific mode (light/dark)
     * Maps dark_* settings to standard keys when in dark mode
     * 
     * @param string $mode 'light' or 'dark'
     * @return array Mode-specific settings
     */
    public static function get_mode_settings($mode = 'light') {
        // Use effective settings to respect design_mode (theme vs custom)
        $settings = self::get_effective_settings();
        
        if ($mode !== 'dark') {
            return $settings;
        }
        
        // Map dark_* values to standard keys
        $dark_mappings = array(
            'primary_color' => 'dark_primary_color',
            'secondary_color' => 'dark_secondary_color',
            'background_color' => 'dark_background_color',
            'text_color' => 'dark_text_color',
            'text_secondary' => 'dark_text_secondary',
            'text_muted' => 'dark_text_muted',
            'card_background' => 'dark_card_background',
            'card_border' => 'dark_card_border',
            'border_color' => 'dark_border_color',
            'hover_color' => 'dark_hover_color',
            'link_color' => 'dark_link_color',
            'surface_color' => 'dark_surface_color',
            'divider_color' => 'dark_divider_color',
            'overlay_bg' => 'dark_overlay_bg',
            'overlay_text' => 'dark_overlay_text',
            'overlay_text_secondary' => 'dark_overlay_text_secondary',
            'overlay_text_muted' => 'dark_overlay_text_muted',
            'overlay_border' => 'dark_overlay_border',
            'placeholder_bg' => 'dark_placeholder_bg',
            'placeholder_icon' => 'dark_placeholder_icon',
            'button_bg' => 'dark_button_bg',
            'button_text' => 'dark_button_text',
            'button_hover_bg' => 'dark_button_hover_bg',
            'button_hover_text' => 'dark_button_hover_text',
            'calendar_header_bg' => 'dark_calendar_header_bg',
            'calendar_header_text' => 'dark_calendar_header_text',
            'calendar_cell_bg' => 'dark_calendar_cell_bg',
            'calendar_cell_hover' => 'dark_calendar_cell_hover',
            'calendar_today_bg' => 'dark_calendar_today_bg',
            'calendar_today_text' => 'dark_calendar_today_text',
            'calendar_event_bg' => 'dark_calendar_event_bg',
            'filter_bg' => 'dark_filter_bg',
        );
        
        $dark_settings = $settings;
        
        foreach ($dark_mappings as $standard_key => $dark_key) {
            if (isset($settings[$dark_key])) {
                $dark_settings[$standard_key] = $settings[$dark_key];
            }
        }
        
        return $dark_settings;
    }
    
    /**
     * Get the current active mode based on Layout Set
     * 
     * @return string 'light' or 'dark'
     */
    public static function get_current_mode() {
        if (class_exists('ES_Layout_Sets')) {
            return ES_Layout_Sets::get_active_mode();
        }
        return 'light';
    }
    
    /**
     * Get color keys that have dark mode variants
     * 
     * @return array List of color keys
     */
    public static function get_color_keys() {
        return array(
            'primary_color',
            'secondary_color', 
            'background_color',
            'text_color',
            'text_secondary',
            'card_background',
            'card_border',
            'hover_color',
            'button_bg',
            'button_text',
            'button_hover_bg',
            'button_hover_text',
            'calendar_header_bg',
            'calendar_header_text',
            'calendar_cell_bg',
            'calendar_cell_hover',
            'calendar_today_bg',
            'calendar_today_text',
            'calendar_event_bg',
            'filter_bg',
        );
    }
    
    /**
     * Get effective defaults based on active layout
     * Layout presets override base defaults
     * For dark-mode layouts, preset colors are also mapped to dark_* keys
     * 
     * @return array Effective default settings
     */
    public static function get_effective_defaults() {
        $defaults = self::$defaults;
        
        // Check for layout-specific preset
        if (class_exists('ES_Layout_Sets')) {
            $active_set = ES_Layout_Sets::get_active_set();
            $set_data = ES_Layout_Sets::get_set_data($active_set);
            
            if (!empty($set_data['path'])) {
                $preset_file = $set_data['path'] . '/preset.php';
                
                if (file_exists($preset_file)) {
                    $preset = include $preset_file;
                    
                    if (is_array($preset)) {
                        // Preset values override base defaults
                        $defaults = wp_parse_args($preset, $defaults);
                        
                        // For dark-mode layouts, also map preset colors to dark_* keys
                        $layout_mode = $set_data['default_mode'] ?? 'light';
                        
                        if ($layout_mode === 'dark') {
                            // Map standard keys to dark_* keys
                            $dark_mappings = array(
                                'primary_color' => 'dark_primary_color',
                                'secondary_color' => 'dark_secondary_color',
                                'background_color' => 'dark_background_color',
                                'text_color' => 'dark_text_color',
                                'text_secondary' => 'dark_text_secondary',
                                'card_background' => 'dark_card_background',
                                'card_border' => 'dark_card_border',
                                'hover_color' => 'dark_hover_color',
                                'button_bg' => 'dark_button_bg',
                                'button_text' => 'dark_button_text',
                                'button_hover_bg' => 'dark_button_hover_bg',
                                'button_hover_text' => 'dark_button_hover_text',
                            );
                            
                            foreach ($dark_mappings as $standard_key => $dark_key) {
                                // Only map if dark_* is NOT already defined in preset
                                // This prevents overwriting explicit dark values with light values
                                if (isset($preset[$standard_key]) && !isset($preset[$dark_key])) {
                                    $defaults[$dark_key] = $preset[$standard_key];
                                }
                            }
                        }
                    }
                }
            }
        }
        
        return $defaults;
    }
    
    /**
     * Get a specific setting
     * 
     * @param string $key Setting key
     * @param mixed $default Default value if not found
     * @return mixed Setting value
     */
    public static function get_setting($key, $default = null) {
        $settings = self::get_settings();
        
        if (isset($settings[$key])) {
            return $settings[$key];
        }
        
        return $default !== null ? $default : (isset(self::$defaults[$key]) ? self::$defaults[$key] : null);
    }
    
    /**
     * Update design settings
     * 
     * @param array $new_settings New settings to merge
     * @return bool Success
     */
    public static function update_settings($new_settings) {
        $current = self::get_settings();
        $updated = wp_parse_args($new_settings, $current);
        
        return update_option(self::OPTION_NAME, $updated);
    }
    
    /**
     * Save design settings (alias for update_settings)
     * 
     * @param array $new_settings New settings to save
     * @return bool Success
     */
    public static function save_settings($new_settings) {
        return self::update_settings($new_settings);
    }
    
    /**
     * Update a single setting
     * 
     * @param string $key Setting key
     * @param mixed $value Setting value
     * @return bool Success
     */
    public static function update_setting($key, $value) {
        return self::update_settings(array($key => $value));
    }
    
    /**
     * Reset settings to defaults
     * Deletes saved settings so effective defaults (incl. layout presets) are used
     * 
     * @return bool Success
     */
    public static function reset_to_defaults() {
        return delete_option(self::OPTION_NAME);
    }
    
    /**
     * Apply layout preset - saves preset values to DB
     * 
     * @return bool Success
     */
    public static function apply_layout_preset() {
        $defaults = self::get_effective_defaults();
        return update_option(self::OPTION_NAME, $defaults);
    }
    
    /**
     * Get the active template name
     * 
     * @return string Template name
     */
    public static function get_active_template() {
        return get_option(self::ACTIVE_TEMPLATE_OPTION, 'classic-blue');
    }
    
    /**
     * Set the active template
     * 
     * @param string $template_name Template name
     * @return bool Success
     */
    public static function set_active_template($template_name) {
        return update_option(self::ACTIVE_TEMPLATE_OPTION, $template_name);
    }
    
    /**
     * Load a template preset
     * 
     * @param string $template_name Template name
     * @return bool Success
     */
    public static function load_template($template_name) {
        $templates = ES_Design_Templates::get_all_templates();
        
        if (!isset($templates[$template_name])) {
            return false;
        }
        
        $template_settings = $templates[$template_name]['settings'];
        
        // Update settings
        self::update_settings($template_settings);
        
        // Set as active
        self::set_active_template($template_name);
        
        return true;
    }
    
    /**
     * Export current settings as JSON
     * 
     * @return string JSON encoded settings
     */
    public static function export_settings() {
        $settings = self::get_settings();
        return json_encode($settings, JSON_PRETTY_PRINT);
    }
    
    /**
     * Import settings from JSON
     * 
     * @param string $json JSON encoded settings
     * @return bool|WP_Error Success or error
     */
    public static function import_settings($json) {
        $settings = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('invalid_json', 'Invalid JSON format');
        }
        
        if (!is_array($settings)) {
            return new WP_Error('invalid_format', 'Settings must be an array');
        }
        
        return self::update_settings($settings);
    }
    
    /**
     * Get default settings
     * 
     * @return array Default settings
     */
    public static function get_defaults() {
        return self::$defaults;
    }
    
    /**
     * Get effective settings (considers design mode)
     * If design_mode is 'theme', returns defaults with theme values merged
     * If design_mode is 'custom', returns regular settings
     * 
     * @return array Effective design settings
     */
    public static function get_effective_settings() {
        $settings = self::get_settings();
        $design_mode = $settings['design_mode'] ?? 'custom';
        
        if ($design_mode === 'theme') {
            // Start with defaults (light colors, standard fonts)
            $effective = self::$defaults;
            
            // Keep design_mode
            $effective['design_mode'] = 'theme';
            
            // Get theme values
            $theme_values = ES_Theme_Detector::get_theme_values();
            
            // Merge theme values (they override defaults)
            $effective = array_merge($effective, $theme_values);
            
            // Also derive additional values from primary/secondary
            if (!empty($theme_values['primary_color'])) {
                $effective['button_bg'] = $theme_values['primary_color'];
                $effective['hover_color'] = $theme_values['primary_color'];
                $effective['calendar_header_bg'] = $theme_values['primary_color'];
                $effective['calendar_today_bg'] = $theme_values['primary_color'];
                $effective['calendar_event_bg'] = $theme_values['primary_color'];
            }
            
            if (!empty($theme_values['secondary_color'])) {
                $effective['button_hover_bg'] = $theme_values['secondary_color'];
                $effective['button_bg_hover'] = $theme_values['secondary_color'];
            }
            
            return $effective;
        }
        
        return $settings;
    }
    
    /**
     * Get design mode
     * 
     * @return string 'custom' or 'theme'
     */
    public static function get_design_mode() {
        return self::get_setting('design_mode', 'custom');
    }
    
    /**
     * Set design mode
     * 
     * @param string $mode 'custom' or 'theme'
     * @return bool Success
     */
    public static function set_design_mode($mode) {
        if (!in_array($mode, array('custom', 'theme'))) {
            return false;
        }
        
        return self::update_setting('design_mode', $mode);
    }
    
    /**
     * Get theme info and preview
     * 
     * @return array Theme info with preview values
     */
    public static function get_theme_preview() {
        return ES_Theme_Detector::get_theme_values_preview();
    }
    
    /**
     * AJAX: Get theme preview
     */
    public static function ajax_get_theme_preview() {
        check_ajax_referer('ensemble_designer', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $preview = self::get_theme_preview();
        wp_send_json_success($preview);
    }
    
    /**
     * AJAX: Set design mode
     */
    public static function ajax_set_design_mode() {
        check_ajax_referer('ensemble_designer', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $mode = isset($_POST['mode']) ? sanitize_key($_POST['mode']) : '';
        
        if (!in_array($mode, array('custom', 'theme'))) {
            wp_send_json_error(array('message' => 'Invalid mode'));
        }
        
        $success = self::set_design_mode($mode);
        
        if ($success) {
            $preview = ($mode === 'theme') ? self::get_theme_preview() : null;
            wp_send_json_success(array(
                'message' => 'Design mode updated',
                'mode' => $mode,
                'preview' => $preview,
            ));
        } else {
            wp_send_json_error(array('message' => 'Failed to update mode'));
        }
    }
    

    
    /**
     * AJAX: Export settings
     */
    public static function ajax_export_settings() {
        check_ajax_referer('es_export_design', '_wpnonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $json = self::export_settings();
        wp_send_json_success(array('json' => $json));
    }
    
    /**
     * AJAX: Import settings
     */
    public static function ajax_import_settings() {
        check_ajax_referer('es_import_design', '_wpnonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $json = isset($_POST['json']) ? stripslashes($_POST['json']) : '';
        
        if (empty($json)) {
            wp_send_json_error(array('message' => 'No data provided'));
        }
        
        $result = self::import_settings($json);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success(array('message' => 'Import successful'));
    }
    
    /**
     * AJAX handler for applying layout preset
     */
    public static function ajax_apply_layout_preset() {
        check_ajax_referer('ensemble_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }
        
        $result = self::apply_layout_preset();
        
        if ($result) {
            wp_send_json_success(array(
                'message' => 'Layout preset applied successfully',
                'settings' => self::get_settings()
            ));
        } else {
            wp_send_json_error(array('message' => 'Failed to apply preset'));
        }
    }
    
    /**
     * AJAX handler for resetting to defaults
     */
    public static function ajax_reset_to_defaults() {
        check_ajax_referer('ensemble_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }
        
        $result = self::reset_to_defaults();
        
        wp_send_json_success(array(
            'message' => 'Settings reset to layout defaults',
            'settings' => self::get_settings()
        ));
    }
}

// Initialize on plugins loaded
add_action('plugins_loaded', array('ES_Design_Settings', 'init'));

// Register AJAX handlers
add_action('wp_ajax_es_export_design_settings', array('ES_Design_Settings', 'ajax_export_settings'));
add_action('wp_ajax_es_import_design_settings', array('ES_Design_Settings', 'ajax_import_settings'));
add_action('wp_ajax_es_get_theme_preview', array('ES_Design_Settings', 'ajax_get_theme_preview'));
add_action('wp_ajax_es_set_design_mode', array('ES_Design_Settings', 'ajax_set_design_mode'));
add_action('wp_ajax_es_apply_layout_preset', array('ES_Design_Settings', 'ajax_apply_layout_preset'));
add_action('wp_ajax_es_reset_to_defaults', array('ES_Design_Settings', 'ajax_reset_to_defaults'));
