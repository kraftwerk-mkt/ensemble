<?php
/**
 * Simple Club Layout Preset
 * 
 * Modern Navy & Cyan - Tech-inspired clean design
 * Glassmorphism effects, rounded corners, electric blue accents
 * 
 * @package Ensemble
 * @layout Simple Club
 */

if (!defined('ABSPATH')) exit;

return array(
    // =====================
    // COLORS - Navy & Electric Cyan
    // =====================
    
    // Primary Colors
    'primary_color'     => '#58a6ff',  // Electric Cyan
    'secondary_color'   => '#388bfd',  // Darker Cyan
    'hover_color'       => '#79c0ff',  // Light Cyan
    
    // Background & Cards (GitHub Dark inspired)
    'background_color'  => '#0d1117',  // Deep Navy
    'card_background'   => '#161b22',  // Card Navy
    'card_border'       => '#30363d',  // Subtle border
    
    // Text Colors
    'text_color'        => '#e6edf3',  // Soft white
    'text_secondary'    => '#8b949e',
    'text_muted'        => '#6e7681',
    'link_color'        => '#58a6ff',  // Cyan links
    
    // Surface & Dividers
    'surface_color'     => '#161b22',
    'divider_color'     => '#30363d',
    
    // Overlay
    'overlay_bg'                => 'rgba(13, 17, 23, 0.95)',
    'overlay_text'              => '#e6edf3',
    'overlay_text_secondary'    => 'rgba(230, 237, 243, 0.8)',
    'overlay_text_muted'        => 'rgba(230, 237, 243, 0.5)',
    'overlay_border'            => 'rgba(88, 166, 255, 0.3)',
    
    // Placeholder
    'placeholder_bg'    => '#161b22',
    'placeholder_icon'  => '#30363d',
    
    // Status Colors
    'status_cancelled'  => '#f85149',
    'status_soldout'    => '#8b949e',
    'status_postponed'  => '#d29922',
    
    // Gradients
    'gradient_start'    => 'rgba(13, 17, 23, 0.9)',
    'gradient_mid'      => 'rgba(13, 17, 23, 0.5)',
    'gradient_end'      => 'transparent',
    
    // =====================
    // DARK MODE (same as base - this IS a dark layout)
    // =====================
    'dark_primary_color'      => '#58a6ff',
    'dark_secondary_color'    => '#388bfd',
    'dark_background_color'   => '#0d1117',
    'dark_text_color'         => '#e6edf3',
    'dark_text_secondary'     => '#8b949e',
    'dark_text_muted'         => '#6e7681',
    'dark_card_background'    => '#161b22',
    'dark_card_border'        => '#30363d',
    'dark_hover_color'        => '#79c0ff',
    'dark_link_color'         => '#58a6ff',
    'dark_surface_color'      => '#161b22',
    'dark_divider_color'      => '#30363d',
    
    // =====================
    // BUTTONS - Modern, Compact
    // =====================
    'button_bg'         => '#58a6ff',
    'button_text'       => '#0d1117',  // Dark text on cyan
    'button_hover_bg'   => '#79c0ff',
    'button_hover_text' => '#0d1117',
    'button_radius'     => '6',        // Moderate rounding
    'button_padding_v'  => '12',
    'button_padding_h'  => '20',
    'button_weight'     => '600',
    'button_font_size'  => '14',
    'button_border_width' => '0',
    'button_style'      => 'solid',
    
    'dark_button_bg'          => '#58a6ff',
    'dark_button_text'        => '#0d1117',
    'dark_button_hover_bg'    => '#79c0ff',
    'dark_button_hover_text'  => '#0d1117',
    
    // =====================
    // TYPOGRAPHY
    // =====================
    'heading_font'      => 'Inter',
    'body_font'         => 'Inter',
    'heading_weight'    => '600',
    'body_weight'       => '400',
    'line_height_heading' => '1.25',
    'line_height_body'  => '1.6',
    
    // Font Sizes
    'h1_size'           => '36',
    'h2_size'           => '28',
    'h3_size'           => '24',
    'body_size'         => '16',
    'small_size'        => '14',
    'xs_size'           => '12',
    'meta_size'         => '14',
    'lg_size'           => '18',
    'xl_size'           => '20',
    'price_size'        => '32',
    'hero_size'         => '72',
    
    // =====================
    // CARDS - Glassmorphism, Rounded
    // =====================
    'card_radius'       => '16',       // Large rounding
    'card_padding'      => '25',
    'card_border_width' => '1',
    'card_shadow'       => '0 8px 32px rgba(0, 0, 0, 0.3), 0 0 0 1px rgba(48, 54, 61, 0.5)',
    'card_hover_transform' => 'translateY(-6px)',
    'card_hover_shadow' => '0 20px 50px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(88, 166, 255, 0.4), 0 0 30px rgba(88, 166, 255, 0.1)',
    'card_image_height' => '240',
    'card_gap'          => '24',
    
    // =====================
    // LAYOUT
    // =====================
    'container_width'   => '1280',
    'grid_columns'      => '3',
    'grid_gap'          => '24',
    'section_spacing'   => '48',
    
    // =====================
    // CALENDAR
    // =====================
    'calendar_header_bg'    => '#161b22',
    'calendar_header_text'  => '#e6edf3',
    'calendar_cell_bg'      => '#0d1117',
    'calendar_cell_hover'   => '#161b22',
    'calendar_today_bg'     => '#58a6ff',
    'calendar_today_text'   => '#0d1117',
    'calendar_event_bg'     => '#58a6ff',
    
    'dark_calendar_header_bg'   => '#161b22',
    'dark_calendar_header_text' => '#e6edf3',
    'dark_calendar_cell_bg'     => '#0d1117',
    'dark_calendar_cell_hover'  => '#161b22',
    'dark_calendar_today_bg'    => '#58a6ff',
    'dark_calendar_today_text'  => '#0d1117',
    'dark_calendar_event_bg'    => '#58a6ff',
    
    // =====================
    // FILTERS
    // =====================
    'filter_bg'         => '#161b22',
    'dark_filter_bg'    => '#161b22',
);
