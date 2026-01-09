<?php
/**
 * Simple Club Layout Configuration
 * 
 * @package Ensemble
 * @version 1.0.0
 */

if (!defined('ABSPATH')) exit;

return array(
    'name' => 'Simple Club',
    'version' => '1.0.0',
    'author' => 'Ensemble',
    'description' => 'Clean club-style layout with prominent location display and fading gallery',
    
    // Layout supports
    'supports' => array(
        'dark_mode' => true,
        'light_mode' => false,
        'sidebar' => true,
        'full_width' => true,
    ),
    
    // Default mode
    'default_mode' => 'dark',
    
    // Card aspect ratio
    'card_aspect_ratio' => '4/5',
    
    // Hero height
    'hero_height' => '500px',
    
    // Typography
    'font_heading' => 'Montserrat',
    'font_body' => 'Montserrat',
    
    // Colors
    'primary_color' => '#e91e8c',
    'accent_color' => '#ff4da6',
);
