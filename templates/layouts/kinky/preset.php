<?php
/**
 * Kinky Layout Preset
 * 
 * Dark, fiery, sensual design
 * Fire-red/orange on near-black background
 * 
 * NOTE: This layout currently uses hardcoded --kinky-* CSS variables
 * instead of --ensemble-* variables. Designer changes will have limited effect
 * until the layout CSS is updated to use ensemble variables.
 * 
 * @package Ensemble
 */

if (!defined('ABSPATH')) exit;

return array(
    // =====================
    // COLORS
    // =====================
    
    // Primary Colors
    'primary_color'     => '#cc2222',
    'secondary_color'   => '#ff6633',
    'hover_color'       => '#ff4444',
    
    // Background & Cards
    'background_color'  => '#0a0a0f',
    'card_background'   => '#0f0f14',
    'card_border'       => '#1a1a22',
    
    // Text Colors
    'text_color'        => '#ffffff',
    'text_secondary'    => '#cccccc',
    'text_muted'        => '#888888',
    'link_color'        => '#ff6633',
    
    // Surface & Dividers
    'surface_color'     => '#12121a',
    'divider_color'     => '#2a2a35',
    
    // Overlay
    'overlay_bg'                => 'rgba(10, 10, 15, 0.95)',
    'overlay_text'              => '#ffffff',
    'overlay_text_secondary'    => 'rgba(255, 255, 255, 0.8)',
    'overlay_text_muted'        => 'rgba(255, 255, 255, 0.5)',
    'overlay_border'            => 'rgba(204, 34, 34, 0.4)',
    
    // Placeholder
    'placeholder_bg'    => '#1a1a22',
    'placeholder_icon'  => 'rgba(204, 34, 34, 0.5)',
    
    // Status Colors
    'status_cancelled'  => '#ef4444',
    'status_soldout'    => '#cc2222',
    'status_postponed'  => '#ff6633',
    
    // Gradients
    'gradient_start'    => 'rgba(10, 10, 15, 0.95)',
    'gradient_mid'      => 'rgba(10, 10, 15, 0.5)',
    'gradient_end'      => 'transparent',
    
    // Social
    'facebook_color'    => '#1877f2',
    
    // =====================
    // BUTTONS
    // =====================
    'button_bg'         => '#cc2222',
    'button_text'       => '#ffffff',
    'button_hover_bg'   => '#ff4444',
    'button_hover_text' => '#ffffff',
    'button_radius'     => '0',
    'button_padding_v'  => '16',
    'button_padding_h'  => '32',
    'button_weight'     => '700',
    'button_font_size'  => '14',
    'button_border_width' => '0',
    'button_style'      => 'solid',
    
    // =====================
    // TYPOGRAPHY
    // =====================
    'heading_font'      => 'Cinzel',
    'body_font'         => 'Lato',
    'heading_weight'    => '700',
    'body_weight'       => '400',
    'line_height_heading' => '1.2',
    'line_height_body'  => '1.7',
    
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
    'price_size'        => '32',
    'hero_size'         => '64',
    
    // =====================
    // CARDS
    // =====================
    'card_radius'       => '0',
    'card_padding'      => '24',
    'card_border_width' => '1',
    'card_shadow'       => 'medium',
    'card_hover'        => 'glow',
    'card_image_height' => '240',
    
    // =====================
    // LAYOUT
    // =====================
    'container_width'   => '1200',
    'grid_columns'      => '3',
    'grid_gap'          => '16',
    'section_spacing'   => '64',
    
    // =====================
    // CALENDAR
    // =====================
    'calendar_header_bg'    => '#cc2222',
    'calendar_header_text'  => '#ffffff',
    'calendar_cell_bg'      => '#0f0f14',
    'calendar_cell_hover'   => '#1a1a22',
    'calendar_today_bg'     => '#cc2222',
    'calendar_today_text'   => '#ffffff',
    'calendar_event_bg'     => '#cc2222',
    
    // =====================
    // FILTERS
    // =====================
    'filter_bg'         => '#0f0f14',
);
