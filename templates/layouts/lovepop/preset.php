<?php
/**
 * Lovepop Layout Preset
 * 
 * Dark gradient background with magenta accents
 * Bold, impactful design with glowing borders
 * 
 * These values become the Designer defaults when this layout is active.
 * User can still customize everything via Designer.
 * 
 * @package Ensemble
 */

if (!defined('ABSPATH')) exit;

return array(
    // =====================
    // LIGHT MODE COLORS (used as base, mapped to dark_* for dark layouts)
    // =====================
    
    // Primary Colors
    'primary_color'     => '#e91e8c',
    'secondary_color'   => '#ff4da6',
    'hover_color'       => '#b8157a',
    
    // Background & Cards
    'background_color'  => '#0a0a0f',
    'card_background'   => '#141423',
    'card_border'       => 'rgba(233, 30, 140, 0.3)',
    
    // Text Colors
    'text_color'        => '#ffffff',
    'text_secondary'    => '#a0a0b0',
    'text_muted'        => '#666680',
    'link_color'        => '#e91e8c',
    
    // Surface & Dividers
    'surface_color'     => '#1a1a2e',
    'divider_color'     => 'rgba(255, 255, 255, 0.1)',
    
    // Overlay (Text über Bildern)
    'overlay_bg'                => 'rgba(10, 10, 15, 0.85)',
    'overlay_text'              => '#ffffff',
    'overlay_text_secondary'    => 'rgba(255, 255, 255, 0.8)',
    'overlay_text_muted'        => 'rgba(255, 255, 255, 0.5)',
    'overlay_border'            => 'rgba(233, 30, 140, 0.3)',
    
    // Placeholder (fehlende Bilder)
    'placeholder_bg'    => '#1a1a2e',
    'placeholder_icon'  => 'rgba(233, 30, 140, 0.5)',
    
    // Status Colors
    'status_cancelled'  => '#ef4444',
    'status_soldout'    => '#e91e8c',
    'status_postponed'  => '#f59e0b',
    
    // Gradients
    'gradient_start'    => 'rgba(10, 10, 15, 0.9)',
    'gradient_mid'      => 'rgba(10, 10, 15, 0.5)',
    'gradient_end'      => 'transparent',
    
    // Social
    'facebook_color'    => '#1877f2',
    
    // =====================
    // BUTTONS
    // =====================
    'button_bg'         => '#e91e8c',
    'button_text'       => '#ffffff',
    'button_hover_bg'   => '#b8157a',
    'button_hover_text' => '#ffffff',
    'button_radius'     => '8',
    'button_padding_v'  => '14',
    'button_padding_h'  => '28',
    'button_weight'     => '600',
    'button_font_size'  => '16',
    'button_border_width' => '0',
    'button_style'      => 'solid',
    
    // =====================
    // TYPOGRAPHY
    // =====================
    'heading_font'      => 'Montserrat',
    'body_font'         => 'Montserrat',
    'heading_weight'    => '800',
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
    'meta_size'         => '14',
    'lg_size'           => '18',
    'xl_size'           => '20',
    'price_size'        => '36',
    'hero_size'         => '72',
    
    // =====================
    // CARDS
    // =====================
    'card_radius'       => '12',
    'card_padding'      => '24',
    'card_border_width' => '1',
    'card_shadow'       => 'medium',
    'card_hover'        => 'glow',
    'card_image_height' => '220',
    
    // =====================
    // LAYOUT
    // =====================
    'container_width'   => '1200',
    'grid_columns'      => '3',
    'grid_gap'          => '24',
    'section_spacing'   => '48',
    
    // =====================
    // CALENDAR
    // =====================
    'calendar_header_bg'    => '#e91e8c',
    'calendar_header_text'  => '#ffffff',
    'calendar_cell_bg'      => '#141423',
    'calendar_cell_hover'   => '#1a1a2e',
    'calendar_today_bg'     => '#e91e8c',
    'calendar_today_text'   => '#ffffff',
    'calendar_event_bg'     => '#e91e8c',
    
    // =====================
    // FILTERS
    // =====================
    'filter_bg'         => '#141423',
);
