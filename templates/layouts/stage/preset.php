<?php
/**
 * Stage Layout Preset
 * 
 * Clean, minimal design with light background
 * Sharp edges, bold typography
 * 
 * @package Ensemble
 */

if (!defined('ABSPATH')) exit;

return array(
    // =====================
    // COLORS
    // =====================
    
    // Primary Colors
    'primary_color'     => '#1a1a1a',
    'secondary_color'   => '#333333',
    'hover_color'       => '#333333',
    
    // Background & Cards
    'background_color'  => '#f5f5f5',
    'card_background'   => '#ffffff',
    'card_border'       => '#e0e0e0',
    
    // Text Colors
    'text_color'        => '#1a1a1a',
    'text_secondary'    => '#666666',
    'text_muted'        => '#999999',
    'link_color'        => '#1a1a1a',
    
    // Surface & Dividers
    'surface_color'     => '#ffffff',
    'divider_color'     => '#e0e0e0',
    
    // Overlay (Text über Bildern)
    'overlay_bg'                => 'rgba(26, 26, 26, 0.85)',
    'overlay_text'              => '#ffffff',
    'overlay_text_secondary'    => 'rgba(255, 255, 255, 0.8)',
    'overlay_text_muted'        => 'rgba(255, 255, 255, 0.6)',
    'overlay_border'            => 'rgba(255, 255, 255, 0.3)',
    
    // Placeholder
    'placeholder_bg'    => '#e5e5e5',
    'placeholder_icon'  => '#999999',
    
    // Status Colors
    'status_cancelled'  => '#dc2626',
    'status_soldout'    => '#1a1a1a',
    'status_postponed'  => '#d97706',
    
    // Gradients
    'gradient_start'    => 'rgba(0, 0, 0, 0.85)',
    'gradient_mid'      => 'rgba(0, 0, 0, 0.4)',
    'gradient_end'      => 'transparent',
    
    // Social
    'facebook_color'    => '#1877f2',
    
    // =====================
    // BUTTONS
    // =====================
    'button_bg'         => '#1a1a1a',
    'button_text'       => '#ffffff',
    'button_hover_bg'   => '#333333',
    'button_hover_text' => '#ffffff',
    'button_radius'     => '0',
    'button_padding_v'  => '14',
    'button_padding_h'  => '28',
    'button_weight'     => '600',
    'button_font_size'  => '13',
    'button_border_width' => '0',
    'button_style'      => 'solid',
    
    // =====================
    // TYPOGRAPHY
    // =====================
    'heading_font'      => 'Oswald',
    'body_font'         => 'Inter',
    'heading_weight'    => '700',
    'body_weight'       => '400',
    'line_height_heading' => '1.1',
    'line_height_body'  => '1.6',
    
    // Font Sizes
    'h1_size'           => '48',
    'h2_size'           => '28',
    'h3_size'           => '20',
    'body_size'         => '16',
    'small_size'        => '14',
    'xs_size'           => '12',
    'meta_size'         => '13',
    'lg_size'           => '18',
    'xl_size'           => '20',
    'price_size'        => '28',
    'hero_size'         => '56',
    
    // =====================
    // CARDS
    // =====================
    'card_radius'       => '0',
    'card_padding'      => '0',
    'card_border_width' => '0',
    'card_shadow'       => 'none',
    'card_hover'        => 'lift',
    'card_image_height' => '200',
    
    // =====================
    // LAYOUT
    // =====================
    'container_width'   => '1200',
    'grid_columns'      => '4',
    'grid_gap'          => '4',
    'section_spacing'   => '48',
    
    // =====================
    // CALENDAR
    // =====================
    'calendar_header_bg'    => '#1a1a1a',
    'calendar_header_text'  => '#ffffff',
    'calendar_cell_bg'      => '#ffffff',
    'calendar_cell_hover'   => '#f5f5f5',
    'calendar_today_bg'     => '#1a1a1a',
    'calendar_today_text'   => '#ffffff',
    'calendar_event_bg'     => '#1a1a1a',
    
    // =====================
    // FILTERS
    // =====================
    'filter_bg'         => '#ffffff',
);
