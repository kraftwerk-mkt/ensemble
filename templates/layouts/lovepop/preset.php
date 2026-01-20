<?php
/**
 * Lovepop Layout Preset
 * 
 * Deep Purple & Magenta - Vibrant pop design
 * Neon glow effects, pill buttons, playful animations
 * 
 * @package Ensemble
 * @layout Lovepop
 */

if (!defined('ABSPATH')) exit;

return array(
    // =====================
    // COLORS - Aubergine & Magenta
    // =====================
    
    // Primary Colors
    'primary_color'     => '#e040fb',  // Vibrant Magenta
    'secondary_color'   => '#aa00ff',  // Deep Purple
    'hover_color'       => '#ea80fc',  // Light Magenta
    
    // Background & Cards
    'background_color'  => '#120a18',  // Deep Aubergine
    'card_background'   => '#1e1228',  // Purple-tinted
    'card_border'       => '#3d2a4d',  // Subtle purple border
    
    // Text Colors
    'text_color'        => '#ffffff',
    'text_secondary'    => '#e0e0e0',
    'text_muted'        => '#9e9e9e',
    'link_color'        => '#e040fb',  // Magenta links
    
    // Surface & Dividers
    'surface_color'     => '#1e1228',
    'divider_color'     => '#3d2a4d',
    
    // Overlay
    'overlay_bg'                => 'rgba(18, 10, 24, 0.95)',
    'overlay_text'              => '#ffffff',
    'overlay_text_secondary'    => 'rgba(255, 255, 255, 0.85)',
    'overlay_text_muted'        => 'rgba(255, 255, 255, 0.6)',
    'overlay_border'            => 'rgba(224, 64, 251, 0.4)',
    
    // Placeholder
    'placeholder_bg'    => '#1e1228',
    'placeholder_icon'  => '#3d2a4d',
    
    // Status Colors
    'status_cancelled'  => '#ff5252',
    'status_soldout'    => '#9e9e9e',
    'status_postponed'  => '#ffab40',
    
    // Gradients
    'gradient_start'    => 'rgba(18, 10, 24, 0.9)',
    'gradient_mid'      => 'rgba(18, 10, 24, 0.5)',
    'gradient_end'      => 'transparent',
    
    // =====================
    // DARK MODE (same as base - this IS a dark layout)
    // =====================
    'dark_primary_color'      => '#e040fb',
    'dark_secondary_color'    => '#aa00ff',
    'dark_background_color'   => '#120a18',
    'dark_text_color'         => '#ffffff',
    'dark_text_secondary'     => '#e0e0e0',
    'dark_text_muted'         => '#9e9e9e',
    'dark_card_background'    => '#1e1228',
    'dark_card_border'        => 'rgba(224, 64, 251, 0.25)',
    'dark_hover_color'        => '#ea80fc',
    'dark_link_color'         => '#e040fb',
    'dark_surface_color'      => '#1e1228',
    'dark_divider_color'      => '#3d2a4d',
    
    // =====================
    // BUTTONS - Pill Shape, Neon
    // =====================
    'button_bg'         => '#e040fb',
    'button_text'       => '#120a18',  // Dark text on magenta
    'button_hover_bg'   => '#ea80fc',
    'button_hover_text' => '#120a18',
    'button_radius'     => '50',       // Pill shape!
    'button_padding_v'  => '14',
    'button_padding_h'  => '32',
    'button_weight'     => '600',
    'button_font_size'  => '14',
    'button_border_width' => '0',
    'button_style'      => 'solid',
    
    'dark_button_bg'          => '#e040fb',
    'dark_button_text'        => '#120a18',
    'dark_button_hover_bg'    => '#ea80fc',
    'dark_button_hover_text'  => '#120a18',
    
    // =====================
    // TYPOGRAPHY
    // =====================
    'heading_font'      => 'Montserrat',
    'body_font'         => 'Montserrat',
    'heading_weight'    => '700',
    'body_weight'       => '400',
    'line_height_heading' => '1.2',
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
    // CARDS - Extra Round, Neon Glow
    // =====================
    'card_radius'       => '20',       // Extra round, playful
    'card_padding'      => '25',
    'card_border_width' => '1',
    'card_shadow'       => '0 8px 24px rgba(18, 10, 24, 0.6), 0 0 0 1px rgba(224, 64, 251, 0.15)',
    'card_hover_transform' => 'translateY(-10px) rotate(-0.5deg)',
    'card_hover_shadow' => '0 25px 50px rgba(18, 10, 24, 0.7), 0 0 60px rgba(224, 64, 251, 0.35), 0 0 0 2px rgba(224, 64, 251, 0.5)',
    'card_image_height' => '260',
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
    'calendar_header_bg'    => '#e040fb',
    'calendar_header_text'  => '#120a18',
    'calendar_cell_bg'      => '#1e1228',
    'calendar_cell_hover'   => '#2a1a38',
    'calendar_today_bg'     => '#e040fb',
    'calendar_today_text'   => '#120a18',
    'calendar_event_bg'     => '#e040fb',
    
    'dark_calendar_header_bg'   => '#e040fb',
    'dark_calendar_header_text' => '#120a18',
    'dark_calendar_cell_bg'     => '#1e1228',
    'dark_calendar_cell_hover'  => '#2a1a38',
    'dark_calendar_today_bg'    => '#e040fb',
    'dark_calendar_today_text'  => '#120a18',
    'dark_calendar_event_bg'    => '#e040fb',
    
    // =====================
    // FILTERS
    // =====================
    'filter_bg'         => '#1e1228',
    'dark_filter_bg'    => '#1e1228',
);
