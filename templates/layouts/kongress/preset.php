<?php
/**
 * Kongress Layout Preset
 * 
 * Navy & Copper color scheme for professional conferences
 * 
 * @package Ensemble
 * @version 1.0.0
 */

if (!defined('ABSPATH')) exit;

return array(
    // ===========================================
    // COLORS - Navy & Copper
    // ===========================================
    'primary_color'     => '#1B365D',  // Navy Blue
    'secondary_color'   => '#B87333',  // Copper
    'background_color'  => '#FFFFFF',  // Pure White
    'text_color'        => '#1a1a2e',  // Near Black
    'text_secondary'    => '#4a5568',  // Gray 600
    'text_muted'        => '#718096',  // Gray 500
    'card_background'   => '#FFFFFF',  // White
    'card_border'       => '#E2E8F0',  // Gray 200
    'hover_color'       => '#2D4A7C',  // Navy Light
    'link_color'        => '#1B365D',  // Navy
    
    // Surface & Dividers
    'surface_color'     => '#F8FAFC',  // Gray 50 - Subtle background
    'divider_color'     => '#E2E8F0',  // Gray 200
    
    // Overlay (für Bilder)
    'overlay_bg'              => 'rgba(27, 54, 93, 0.85)',  // Navy transparent
    'overlay_text'            => '#FFFFFF',
    'overlay_text_secondary'  => 'rgba(255, 255, 255, 0.9)',
    'overlay_text_muted'      => 'rgba(255, 255, 255, 0.7)',
    'overlay_border'          => 'rgba(184, 115, 51, 0.3)',  // Copper transparent
    
    // Placeholder
    'placeholder_bg'    => '#EDF2F7',
    'placeholder_icon'  => '#A0AEC0',
    
    // Status Colors
    'status_cancelled'  => '#DC2626',
    'status_soldout'    => '#1B365D',
    'status_postponed'  => '#B87333',
    
    // Gradients
    'gradient_start'    => 'rgba(27, 54, 93, 0.9)',   // Navy
    'gradient_mid'      => 'rgba(27, 54, 93, 0.5)',
    'gradient_end'      => 'transparent',
    
    // ===========================================
    // TYPOGRAPHY
    // ===========================================
    'heading_font'        => 'Playfair Display',  // Elegant Serif
    'body_font'           => 'Inter',              // Clean Sans
    'heading_weight'      => '700',
    'body_weight'         => '400',
    'line_height_heading' => '1.2',
    'line_height_body'    => '1.6',
    
    // Font Sizes
    'h1_size'     => 48,
    'h2_size'     => 36,
    'h3_size'     => 24,
    'body_size'   => 16,
    'small_size'  => 14,
    'xs_size'     => 12,
    'meta_size'   => 14,
    'lg_size'     => 18,
    'xl_size'     => 20,
    'price_size'  => 32,
    'hero_size'   => 64,
    
    // ===========================================
    // BUTTONS
    // ===========================================
    'button_bg'           => '#B87333',  // Copper
    'button_text'         => '#FFFFFF',
    'button_hover_bg'     => '#9A5F2A',  // Darker Copper
    'button_hover_text'   => '#FFFFFF',
    'button_radius'       => 4,
    'button_padding_v'    => 14,
    'button_padding_h'    => 28,
    'button_weight'       => '600',
    'button_style'        => 'solid',
    'button_font_size'    => 15,
    'button_border_width' => 2,
    
    // ===========================================
    // CARDS
    // ===========================================
    'card_radius'        => 8,
    'card_padding'       => 24,
    'card_image_height'  => 220,
    'card_border_width'  => 1,
    'card_shadow'        => 'medium',
    'card_hover'         => 'lift',
    
    // ===========================================
    // LAYOUT
    // ===========================================
    'container_width'    => 1200,
    'grid_columns'       => 3,
    'grid_gap'           => 32,
    'section_spacing'    => 80,
    
    // ===========================================
    // CALENDAR
    // ===========================================
    'calendar_header_bg'    => '#1B365D',
    'calendar_header_text'  => '#FFFFFF',
    'calendar_cell_bg'      => '#FFFFFF',
    'calendar_cell_hover'   => '#F8FAFC',
    'calendar_today_bg'     => '#B87333',
    'calendar_today_text'   => '#FFFFFF',
    'calendar_event_bg'     => '#1B365D',
    
    // ===========================================
    // FILTERS
    // ===========================================
    'filter_bg' => '#F8FAFC',
);
