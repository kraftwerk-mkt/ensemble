<?php
/**
 * Ensemble Design Templates
 * 
 * Pre-configured design templates with different styles
 * 
 * @package Ensemble
 * @since 1.9.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class ES_Design_Templates {
    
    /**
     * Get all available templates
     * 
     * @return array Templates
     */
    public static function get_all_templates() {
        $templates = array(
            'classic-blue' => self::get_classic_blue(),
            'purple-gradient' => self::get_purple_gradient(),
            'dark-mode' => self::get_dark_mode(),
            'fresh-green' => self::get_fresh_green(),
            'minimal-white' => self::get_minimal_white(),
        );
        
        // Ensure all templates have complete settings with defaults
        $defaults = ES_Design_Settings::get_defaults();
        foreach ($templates as $key => $template) {
            if (isset($template['settings'])) {
                $templates[$key]['settings'] = wp_parse_args($template['settings'], $defaults);
            }
        }
        
        return $templates;
    }
    
    /**
     * Get template names for dropdown
     * 
     * @return array Template names
     */
    public static function get_template_names() {
        return array(
            'classic-blue' => __('Classic Blue', 'ensemble'),
            'purple-gradient' => __('Purple Gradient', 'ensemble'),
            'dark-mode' => __('Dark Mode', 'ensemble'),
            'fresh-green' => __('Fresh Green', 'ensemble'),
            'minimal-white' => __('Minimal White', 'ensemble'),
        );
    }
    
    /**
     * Classic Blue Template
     */
    private static function get_classic_blue() {
        return array(
            'name' => __('Classic Blue', 'ensemble'),
            'description' => __('Professional blue design with clean lines', 'ensemble'),
            'preview_image' => ENSEMBLE_PLUGIN_URL . 'assets/images/templates/classic-blue.svg',
            'settings' => array(
                // Colors
                'primary_color' => '#2563eb',
                'secondary_color' => '#1e40af',
                'background_color' => '#ffffff',
                'text_color' => '#1e293b',
                'text_secondary' => '#64748b',
                'card_background' => '#ffffff',
                'card_border' => '#e2e8f0',
                'hover_color' => '#1d4ed8',
                
                // Typography
                'heading_font' => 'Inter',
                'body_font' => 'Inter',
                'h1_size' => '36',
                'h2_size' => '28',
                'h3_size' => '24',
                'body_size' => '16',
                'small_size' => '14',
                'heading_weight' => '700',
                'body_weight' => '400',
                'line_height_heading' => '1.2',
                'line_height_body' => '1.6',
                
                // Buttons
                'button_bg' => '#2563eb',
                'button_text' => '#ffffff',
                'button_hover_bg' => '#1d4ed8',
                'button_hover_text' => '#ffffff',
                'button_radius' => '8',
                'button_padding_v' => '12',
                'button_padding_h' => '24',
                'button_weight' => '600',
                'button_style' => 'solid',
                'button_hover_effect' => 'scale',
                
                // Event Cards
                'card_radius' => '12',
                'card_padding' => '24',
                'card_shadow' => 'medium',
                'card_hover' => 'lift',
                'card_image_height' => '200',
                'card_image_fit' => 'cover',
                
                // Layout
                'container_width' => '1200',
                'grid_columns' => '3',
                'grid_columns_tablet' => '2',
                'grid_columns_mobile' => '1',
                'card_gap' => '24',
                'section_spacing' => '48',
                
                // Calendar
                'calendar_header_bg' => '#2563eb',
                'calendar_header_text' => '#ffffff',
                'calendar_cell_bg' => '#ffffff',
                'calendar_cell_hover' => '#f1f5f9',
                'calendar_today_bg' => '#2563eb',
                'calendar_today_text' => '#ffffff',
                'calendar_event_bg' => '#1e40af',
                
                // Filters
                'filter_position' => 'above',
                'filter_style' => 'dropdowns',
                'filter_bg' => '#f8fafc',
                'enable_ajax' => true,
                'show_search' => true,
                'show_date_filter' => true,
                'show_location_filter' => true,
                'show_artist_filter' => true,
            )
        );
    }
    
    /**
     * Purple Gradient Template
     */
    private static function get_purple_gradient() {
        return array(
            'name' => __('Purple Gradient', 'ensemble'),
            'description' => __('Modern gradient design with vibrant colors', 'ensemble'),
            'preview_image' => ENSEMBLE_PLUGIN_URL . 'assets/images/templates/purple-gradient.svg',
            'settings' => array(
                // Colors
                'primary_color' => '#667eea',
                'secondary_color' => '#764ba2',
                'background_color' => '#ffffff',
                'text_color' => '#1a202c',
                'text_secondary' => '#718096',
                'card_background' => '#ffffff',
                'card_border' => '#e9d5ff',
                'hover_color' => '#5568d3',
                
                // Typography
                'heading_font' => 'Poppins',
                'body_font' => 'Inter',
                'h1_size' => '38',
                'h2_size' => '30',
                'h3_size' => '24',
                'body_size' => '16',
                'small_size' => '14',
                'heading_weight' => '700',
                'body_weight' => '400',
                'line_height_heading' => '1.2',
                'line_height_body' => '1.6',
                
                // Buttons
                'button_bg' => '#667eea',
                'button_text' => '#ffffff',
                'button_hover_bg' => '#764ba2',
                'button_hover_text' => '#ffffff',
                'button_radius' => '25',
                'button_padding_v' => '14',
                'button_padding_h' => '28',
                'button_weight' => '600',
                'button_style' => 'gradient',
                'button_hover_effect' => 'scale',
                
                // Event Cards
                'card_radius' => '16',
                'card_padding' => '28',
                'card_shadow' => 'heavy',
                'card_hover' => 'glow',
                'card_image_height' => '220',
                'card_image_fit' => 'cover',
                
                // Layout
                'container_width' => '1200',
                'grid_columns' => '3',
                'grid_columns_tablet' => '2',
                'grid_columns_mobile' => '1',
                'card_gap' => '28',
                'section_spacing' => '56',
                
                // Calendar
                'calendar_header_bg' => '#667eea',
                'calendar_header_text' => '#ffffff',
                'calendar_cell_bg' => '#ffffff',
                'calendar_cell_hover' => '#faf5ff',
                'calendar_today_bg' => '#764ba2',
                'calendar_today_text' => '#ffffff',
                'calendar_event_bg' => '#667eea',
                
                // Filters
                'filter_position' => 'above',
                'filter_style' => 'buttons',
                'filter_bg' => '#faf5ff',
                'enable_ajax' => true,
                'show_search' => true,
                'show_date_filter' => true,
                'show_location_filter' => true,
                'show_artist_filter' => true,
            )
        );
    }
    
    /**
     * Dark Mode Template
     */
    private static function get_dark_mode() {
        return array(
            'name' => __('Dark Mode', 'ensemble'),
            'description' => __('Sleek dark design for modern websites', 'ensemble'),
            'preview_image' => ENSEMBLE_PLUGIN_URL . 'assets/images/templates/dark-mode.svg',
            'settings' => array(
                // Colors
                'primary_color' => '#3b82f6',
                'secondary_color' => '#8b5cf6',
                'background_color' => '#0f172a',
                'text_color' => '#f1f5f9',
                'text_secondary' => '#94a3b8',
                'card_background' => '#1e293b',
                'card_border' => '#334155',
                'hover_color' => '#2563eb',
                
                // Typography
                'heading_font' => 'Inter',
                'body_font' => 'Inter',
                'h1_size' => '36',
                'h2_size' => '28',
                'h3_size' => '24',
                'body_size' => '16',
                'small_size' => '14',
                'heading_weight' => '700',
                'body_weight' => '400',
                'line_height_heading' => '1.2',
                'line_height_body' => '1.6',
                
                // Buttons
                'button_bg' => '#3b82f6',
                'button_text' => '#ffffff',
                'button_hover_bg' => '#2563eb',
                'button_hover_text' => '#ffffff',
                'button_radius' => '8',
                'button_padding_v' => '12',
                'button_padding_h' => '24',
                'button_weight' => '600',
                'button_style' => 'solid',
                'button_hover_effect' => 'shadow',
                
                // Event Cards
                'card_radius' => '12',
                'card_padding' => '24',
                'card_shadow' => 'heavy',
                'card_hover' => 'border',
                'card_image_height' => '200',
                'card_image_fit' => 'cover',
                
                // Layout
                'container_width' => '1200',
                'grid_columns' => '3',
                'grid_columns_tablet' => '2',
                'grid_columns_mobile' => '1',
                'card_gap' => '24',
                'section_spacing' => '48',
                
                // Calendar
                'calendar_header_bg' => '#1e293b',
                'calendar_header_text' => '#f1f5f9',
                'calendar_cell_bg' => '#0f172a',
                'calendar_cell_hover' => '#1e293b',
                'calendar_today_bg' => '#3b82f6',
                'calendar_today_text' => '#ffffff',
                'calendar_event_bg' => '#8b5cf6',
                
                // Filters
                'filter_position' => 'above',
                'filter_style' => 'dropdowns',
                'filter_bg' => '#1e293b',
                'enable_ajax' => true,
                'show_search' => true,
                'show_date_filter' => true,
                'show_location_filter' => true,
                'show_artist_filter' => true,
            )
        );
    }
    
    /**
     * Fresh Green Template
     */
    private static function get_fresh_green() {
        return array(
            'name' => __('Fresh Green', 'ensemble'),
            'description' => __('Natural green design with eco-friendly feel', 'ensemble'),
            'preview_image' => ENSEMBLE_PLUGIN_URL . 'assets/images/templates/fresh-green.svg',
            'settings' => array(
                // Colors
                'primary_color' => '#10b981',
                'secondary_color' => '#059669',
                'background_color' => '#ffffff',
                'text_color' => '#1f2937',
                'text_secondary' => '#6b7280',
                'card_background' => '#ffffff',
                'card_border' => '#d1fae5',
                'hover_color' => '#059669',
                
                // Typography
                'heading_font' => 'Montserrat',
                'body_font' => 'Open Sans',
                'h1_size' => '36',
                'h2_size' => '28',
                'h3_size' => '24',
                'body_size' => '16',
                'small_size' => '14',
                'heading_weight' => '700',
                'body_weight' => '400',
                'line_height_heading' => '1.3',
                'line_height_body' => '1.7',
                
                // Buttons
                'button_bg' => '#10b981',
                'button_text' => '#ffffff',
                'button_hover_bg' => '#059669',
                'button_hover_text' => '#ffffff',
                'button_radius' => '6',
                'button_padding_v' => '12',
                'button_padding_h' => '24',
                'button_weight' => '600',
                'button_style' => 'solid',
                'button_hover_effect' => 'scale',
                
                // Event Cards
                'card_radius' => '10',
                'card_padding' => '24',
                'card_shadow' => 'light',
                'card_hover' => 'lift',
                'card_image_height' => '200',
                'card_image_fit' => 'cover',
                
                // Layout
                'container_width' => '1200',
                'grid_columns' => '3',
                'grid_columns_tablet' => '2',
                'grid_columns_mobile' => '1',
                'card_gap' => '24',
                'section_spacing' => '48',
                
                // Calendar
                'calendar_header_bg' => '#10b981',
                'calendar_header_text' => '#ffffff',
                'calendar_cell_bg' => '#ffffff',
                'calendar_cell_hover' => '#ecfdf5',
                'calendar_today_bg' => '#10b981',
                'calendar_today_text' => '#ffffff',
                'calendar_event_bg' => '#059669',
                
                // Filters
                'filter_position' => 'above',
                'filter_style' => 'dropdowns',
                'filter_bg' => '#f0fdf4',
                'enable_ajax' => true,
                'show_search' => true,
                'show_date_filter' => true,
                'show_location_filter' => true,
                'show_artist_filter' => true,
            )
        );
    }
    
    /**
     * Minimal White Template
     */
    private static function get_minimal_white() {
        return array(
            'name' => __('Minimal White', 'ensemble'),
            'description' => __('Clean minimal design with lots of whitespace', 'ensemble'),
            'preview_image' => ENSEMBLE_PLUGIN_URL . 'assets/images/templates/minimal-white.svg',
            'settings' => array(
                // Colors
                'primary_color' => '#111827',
                'secondary_color' => '#4b5563',
                'background_color' => '#ffffff',
                'text_color' => '#111827',
                'text_secondary' => '#6b7280',
                'card_background' => '#ffffff',
                'card_border' => '#f3f4f6',
                'hover_color' => '#374151',
                
                // Typography
                'heading_font' => 'Playfair Display',
                'body_font' => 'Source Sans Pro',
                'h1_size' => '42',
                'h2_size' => '32',
                'h3_size' => '26',
                'body_size' => '17',
                'small_size' => '15',
                'heading_weight' => '700',
                'body_weight' => '400',
                'line_height_heading' => '1.2',
                'line_height_body' => '1.8',
                
                // Buttons
                'button_bg' => '#111827',
                'button_text' => '#ffffff',
                'button_hover_bg' => '#374151',
                'button_hover_text' => '#ffffff',
                'button_radius' => '4',
                'button_padding_v' => '14',
                'button_padding_h' => '32',
                'button_weight' => '500',
                'button_style' => 'outline',
                'button_hover_effect' => 'none',
                
                // Event Cards
                'card_radius' => '0',
                'card_padding' => '32',
                'card_shadow' => 'none',
                'card_hover' => 'border',
                'card_image_height' => '240',
                'card_image_fit' => 'cover',
                
                // Layout
                'container_width' => '1100',
                'grid_columns' => '2',
                'grid_columns_tablet' => '2',
                'grid_columns_mobile' => '1',
                'card_gap' => '48',
                'section_spacing' => '80',
                
                // Calendar
                'calendar_header_bg' => '#111827',
                'calendar_header_text' => '#ffffff',
                'calendar_cell_bg' => '#ffffff',
                'calendar_cell_hover' => '#f9fafb',
                'calendar_today_bg' => '#111827',
                'calendar_today_text' => '#ffffff',
                'calendar_event_bg' => '#4b5563',
                
                // Filters
                'filter_position' => 'above',
                'filter_style' => 'dropdowns',
                'filter_bg' => '#ffffff',
                'enable_ajax' => true,
                'show_search' => true,
                'show_date_filter' => true,
                'show_location_filter' => true,
                'show_artist_filter' => true,
            )
        );
    }
}
