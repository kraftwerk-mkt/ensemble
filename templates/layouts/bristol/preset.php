<?php
/**
 * Bristol City Festival - Layout Preset
 * Editorial Style - Bold & Modern
 * 
 * @package Ensemble
 * @layout Bristol City Festival
 * @version 2.0.0
 */

return array(
    'name' => 'Bristol City Festival',
    'slug' => 'bristol',
    'description' => __('Bold editorial design with edge-to-edge grids, dramatic hover effects, and asymmetric layouts. Perfect for urban festivals, concerts, and cultural events.', 'ensemble'),
    'preview' => 'preview.jpg',
    'version' => '2.0.0',
    
    // Features
    'features' => array(
        'dark_mode' => true,
        'light_mode' => true,
        'theme_toggle' => true,
        'edge_to_edge_grid' => true,
        'asymmetric_layouts' => true,
        'geometric_accents' => true,
    ),
    
    // Typography
    'fonts' => array(
        'heading' => 'Space Grotesk',
        'body' => 'Inter',
    ),
    
    // Colors
    'colors' => array(
        'primary' => '#ff5722',
        'accent' => '#00e5ff',
        'background' => '#0a0a0f',
        'surface' => '#18181f',
        'text' => '#ffffff',
    ),
    
    // Templates
    'templates' => array(
        'single-event' => 'single-event.php',
        'single-artist' => 'single-artist.php',
        'single-location' => 'single-location.php',
        'event-card' => 'event-card.php',
        'artist-card' => 'artist-card.php',
        'location-card' => 'location-card.php',
        'hero-slide' => 'hero-slide.php',
    ),
    
    // Assets
    'styles' => array(
        'style.css',
    ),
);
