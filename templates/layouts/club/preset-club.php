<?php
/**
 * Club Layout Preset
 * 
 * Elegant Anthracite & Gold - Sophisticated dark design
 * Sharp edges, dramatic hover effects, warm gold accents
 * 
 * @package Ensemble
 * @layout Club
 */

if (!defined('ABSPATH')) exit;

return array(
    // =====================
    // COLORS - Anthracite & Gold
    // =====================
    
    // Primary Colors
    'primary_color'     => '#d4a574',  // Warm Gold
    'secondary_color'   => '#b8956a',  // Darker Gold
    'hover_color'       => '#e8be8c',  // Light Gold
    
    // Background & Cards
    'background_color'  => '#121214',  // Warm Anthracite
    'card_background'   => '#1a1a1e',  // Slightly lighter
    'card_border'       => '#2a2a30',  // Subtle border
    
    // Text Colors
    'text_color'        => '#ffffff',
    'text_secondary'    => '#999999',
    'text_muted'        => '#666666',
    'link_color'        => '#d4a574',  // Gold links
    
    // Surface & Dividers
    'surface_color'     => '#1a1a1e',
    'divider_color'     => '#2a2a30',
    
    // Overlay
    'overlay_bg'                => 'rgba(18, 18, 20, 0.95)',
    'overlay_text'              => '#ffffff',
    'overlay_text_secondary'    => 'rgba(255, 255, 255, 0.8)',
    'overlay_text_muted'        => 'rgba(255, 255, 255, 0.5)',
    'overlay_border'            => 'rgba(212, 165, 116, 0.3)',
    
    // Placeholder
    'placeholder_bg'    => '#1a1a1e',
    'placeholder_icon'  => '#3a3a40',
    
    // Status Colors
    'status_cancelled'  => '#ef4444',
    'status_soldout'    => '#6b7280',
    'status_postponed'  => '#f59e0b',
    
    // Gradients
    'gradient_start'    => 'rgba(18, 18, 20, 0.9)',
    'gradient_mid'      => 'rgba(18, 18, 20, 0.5)',
    'gradient_end'      => 'transparent',
    
    // =====================
    // DARK MODE (same as base - this IS a dark layout)
    // =====================
    'dark_primary_color'      => '#d4a574',
    'dark_secondary_color'    => '#b8956a',
    'dark_background_color'   => '#121214',
    'dark_text_color'         => '#ffffff',
    'dark_text_secondary'     => '#999999',
    'dark_text_muted'         => '#666666',
    'dark_card_background'    => '#1a1a1e',
    'dark_card_border'        => '#2a2a30',
    'dark_hover_color'        => '#e8be8c',
    'dark_link_color'         => '#d4a574',
    'dark_surface_color'      => '#1a1a1e',
    'dark_divider_color'      => '#2a2a30',
    
    // =====================
    // BUTTONS - Sharp, Solid Gold
    // =====================
    'button_bg'         => '#d4a574',
    'button_text'       => '#121214',  // Dark text on gold
    'button_hover_bg'   => '#e8be8c',
    'button_hover_text' => '#121214',
    'button_radius'     => '0',        // Sharp edges!
    'button_padding_v'  => '16',
    'button_padding_h'  => '32',
    'button_weight'     => '600',
    'button_font_size'  => '14',
    'button_border_width' => '0',
    'button_style'      => 'solid',
    
    'dark_button_bg'          => '#d4a574',
    'dark_button_text'        => '#121214',
    'dark_button_hover_bg'    => '#e8be8c',
    'dark_button_hover_text'  => '#121214',
    
    // =====================
    // TYPOGRAPHY
    // =====================
    'heading_font'      => 'Inter',
    'body_font'         => 'Inter',
    'heading_weight'    => '700',
    'body_weight'       => '400',
    'line_height_heading' => '1.2',
    'line_height_body'  => '1.6',
    
    // Font Sizes
    'h1_size'           => '48',
    'h2_size'           => '32',
    'h3_size'           => '24',
    'body_size'         => '16',
    'small_size'        => '14',
    'xs_size'           => '12',
    'meta_size'         => '13',
    'lg_size'           => '18',
    'xl_size'           => '20',
    'price_size'        => '28',
    'hero_size'         => '56',
    
    // =====================
    // CARDS - Sharp, Dramatic
    // =====================
    'card_radius'       => '0',        // Sharp edges!
    'card_padding'      => '20',
    'card_border_width' => '0',
    'card_shadow'       => '0 2px 8px rgba(0, 0, 0, 0.4)',
    'card_hover_transform' => 'translateY(-12px) scale(1.03)',
    'card_hover_shadow' => '0 30px 60px rgba(0, 0, 0, 0.6), 0 0 40px rgba(212, 165, 116, 0.25)',
    'card_image_height' => '280',
    'card_gap'          => '2',
    
    // =====================
    // LAYOUT
    // =====================
    'container_width'   => '1400',
    'grid_columns'      => '4',
    'grid_gap'          => '2',
    'section_spacing'   => '48',
    
    // =====================
    // CALENDAR
    // =====================
    'calendar_header_bg'    => '#d4a574',
    'calendar_header_text'  => '#121214',
    'calendar_cell_bg'      => '#1a1a1e',
    'calendar_cell_hover'   => '#242428',
    'calendar_today_bg'     => '#d4a574',
    'calendar_today_text'   => '#121214',
    'calendar_event_bg'     => '#d4a574',
    
    'dark_calendar_header_bg'   => '#d4a574',
    'dark_calendar_header_text' => '#121214',
    'dark_calendar_cell_bg'     => '#1a1a1e',
    'dark_calendar_cell_hover'  => '#242428',
    'dark_calendar_today_bg'    => '#d4a574',
    'dark_calendar_today_text'  => '#121214',
    'dark_calendar_event_bg'    => '#d4a574',
    
    // =====================
    // FILTERS
    // =====================
    'filter_bg'         => '#1a1a1e',
    'dark_filter_bg'    => '#1a1a1e',
);
