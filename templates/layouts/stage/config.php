<?php
/**
 * Stage Layout Configuration
 * 
 * Clean, minimal design with light background
 * 
 * @package Ensemble
 * @version 2.0.0
 */

if (!defined('ABSPATH')) exit;

return array(
    'name' => 'Stage',
    'version' => '2.0.0',
    'author' => 'Ensemble',
    'description' => 'Clean, minimal design with sharp edges and bold typography',
    
    // Layout supports
    'supports' => array(
        'dark_mode' => false,
        'light_mode' => true,
        'sidebar' => false,
        'full_width' => true,
    ),
    
    // Default mode
    'default_mode' => 'light',
    
    // Card aspect ratio
    'card_aspect_ratio' => '3/4',
    
    // Hero height
    'hero_height' => '500px',
    
    // Typography
    'font_heading' => 'Oswald',
    'font_body' => 'Inter',
    
    // Colors
    'primary_color' => '#1a1a1a',
    'accent_color' => '#333333',
);
