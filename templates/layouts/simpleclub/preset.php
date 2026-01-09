<?php
/**
 * Simple Club Layout Preset
 * 
 * Clean club-style layout with prominent location display
 * Based on Lovepop but simplified
 * 
 * @package Ensemble
 */

if (!defined('ABSPATH')) exit;

return array(
    // Dark gradient background
    'background_color'  => '#0a0a0f',
    'card_background'   => 'rgba(20, 20, 35, 0.8)',
    'card_border'       => 'rgba(233, 30, 140, 0.3)',
    
    // Light text for dark bg
    'text_color'        => '#ffffff',
    'text_secondary'    => '#a0a0b0',
    
    // Magenta accent colors
    'primary_color'     => '#e91e8c',
    'secondary_color'   => '#ff4da6',
    'hover_color'       => '#b8157a',
    
    // Bold typography - Montserrat
    'heading_font'      => 'Montserrat',
    'body_font'         => 'Montserrat',
    'heading_weight'    => '800',
    'body_weight'       => '400',
    
    // Slightly rounded corners
    'card_radius'       => '12',
    'button_radius'     => '8',
    
    // Button styling - Gradient magenta
    'button_bg'         => '#e91e8c',
    'button_text'       => '#ffffff',
    'button_hover_bg'   => '#b8157a',
    'button_hover_text' => '#ffffff',
    
    // Typography sizes
    'h1_size'           => '48',
    'h2_size'           => '28',
    'h3_size'           => '20',
    'body_size'         => '16',
    'line_height'       => '1.6',
    
    // Grid settings
    'grid_columns'      => '3',
    'grid_gap'          => '24',
    'card_padding'      => '25',
    'card_shadow'       => 'large',
    'card_hover'        => 'glow',
);
