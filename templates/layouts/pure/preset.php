<?php
/**
 * Pure Layout Preset
 * 
 * Ultra-minimal design with Light/Dark mode support
 * Clean typography, ghost buttons, thin lines
 * 
 * @package Ensemble
 */

if (!defined('ABSPATH')) exit;

return array(
    // =====================
    // LIGHT MODE COLORS
    // =====================
    
    // Primary Colors
    'primary_color'     => '#111111',
    'secondary_color'   => '#333333',
    'hover_color'       => '#000000',
    
    // Background & Cards
    'background_color'  => '#ffffff',
    'card_background'   => '#ffffff',
    'card_border'       => '#e8e8e8',
    
    // Text Colors
    'text_color'        => '#111111',
    'text_secondary'    => '#666666',
    'text_muted'        => '#999999',
    'link_color'        => '#111111',
    
    // Surface & Dividers
    'surface_color'     => '#ffffff',
    'divider_color'     => '#e8e8e8',
    
    // Overlay
    'overlay_bg'                => 'rgba(0, 0, 0, 0.9)',
    'overlay_text'              => '#ffffff',
    'overlay_text_secondary'    => 'rgba(255, 255, 255, 0.85)',
    'overlay_text_muted'        => 'rgba(255, 255, 255, 0.6)',
    'overlay_border'            => 'rgba(255, 255, 255, 0.2)',
    
    // Placeholder
    'placeholder_bg'    => '#f5f5f5',
    'placeholder_icon'  => '#cccccc',
    
    // Status Colors
    'status_cancelled'  => '#dc2626',
    'status_soldout'    => '#111111',
    'status_postponed'  => '#d97706',
    
    // Gradients
    'gradient_start'    => 'rgba(0, 0, 0, 0.8)',
    'gradient_mid'      => 'rgba(0, 0, 0, 0.4)',
    'gradient_end'      => 'transparent',
    
    // Social
    'facebook_color'    => '#1877f2',
    
    // =====================
    // DARK MODE COLORS
    // =====================
    'dark_primary_color'      => '#ffffff',
    'dark_secondary_color'    => '#e0e0e0',
    'dark_background_color'   => '#0a0a0a',
    'dark_text_color'         => '#ffffff',
    'dark_text_secondary'     => '#999999',
    'dark_text_muted'         => '#666666',
    'dark_card_background'    => '#111111',
    'dark_card_border'        => '#222222',
    'dark_hover_color'        => '#ffffff',
    'dark_link_color'         => '#ffffff',
    'dark_surface_color'      => '#111111',
    'dark_divider_color'      => '#222222',
    'dark_overlay_bg'         => 'rgba(0, 0, 0, 0.95)',
    'dark_overlay_text'       => '#ffffff',
    'dark_placeholder_bg'     => '#1a1a1a',
    'dark_placeholder_icon'   => '#444444',
    'dark_button_bg'          => 'transparent',
    'dark_button_text'        => '#ffffff',
    'dark_button_hover_bg'    => '#ffffff',
    'dark_button_hover_text'  => '#0a0a0a',
    
    // =====================
    // BUTTONS (Ghost Style)
    // =====================
    'button_bg'         => 'transparent',
    'button_text'       => '#111111',
    'button_hover_bg'   => '#111111',
    'button_hover_text' => '#ffffff',
    'button_radius'     => '0',
    'button_padding_v'  => '12',
    'button_padding_h'  => '24',
    'button_weight'     => '500',
    'button_font_size'  => '13',
    'button_border_width' => '1',
    'button_style'      => 'outline',
    
    // =====================
    // TYPOGRAPHY
    // =====================
    'heading_font'      => 'Inter',
    'body_font'         => 'Inter',
    'heading_weight'    => '500',
    'body_weight'       => '400',
    'line_height_heading' => '1.3',
    'line_height_body'  => '1.7',
    
    // Font Sizes
    'h1_size'           => '42',
    'h2_size'           => '24',
    'h3_size'           => '18',
    'body_size'         => '16',
    'small_size'        => '13',
    'xs_size'           => '11',
    'meta_size'         => '13',
    'lg_size'           => '18',
    'xl_size'           => '20',
    'price_size'        => '24',
    'hero_size'         => '48',
    
    // =====================
    // CARDS
    // =====================
    'card_radius'       => '0',
    'card_padding'      => '0',
    'card_border_width' => '1',
    'card_shadow'       => 'none',
    'card_hover'        => 'none',
    'card_image_height' => '200',
    
    // =====================
    // LAYOUT
    // =====================
    'container_width'   => '1200',
    'grid_columns'      => '4',
    'grid_gap'          => '1',
    'section_spacing'   => '64',
    
    // =====================
    // CALENDAR
    // =====================
    'calendar_header_bg'    => '#111111',
    'calendar_header_text'  => '#ffffff',
    'calendar_cell_bg'      => '#ffffff',
    'calendar_cell_hover'   => '#f5f5f5',
    'calendar_today_bg'     => '#111111',
    'calendar_today_text'   => '#ffffff',
    'calendar_event_bg'     => '#111111',
    
    // Dark Mode Calendar
    'dark_calendar_header_bg'   => '#ffffff',
    'dark_calendar_header_text' => '#0a0a0a',
    'dark_calendar_cell_bg'     => '#111111',
    'dark_calendar_cell_hover'  => '#1a1a1a',
    'dark_calendar_today_bg'    => '#ffffff',
    'dark_calendar_today_text'  => '#0a0a0a',
    'dark_calendar_event_bg'    => '#ffffff',
    
    // =====================
    // FILTERS
    // =====================
    'filter_bg'         => '#ffffff',
    'dark_filter_bg'    => '#111111',
);
