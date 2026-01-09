<?php
/**
 * Kinky Layout Configuration
 * Dark, fiery, sensual design
 * Inspired by "Kinky Pleasures" event aesthetic
 * 
 * @package Ensemble
 * @version 2.1.0
 */

if (!defined('ABSPATH')) exit;

return array(
    'name'        => 'Kinky',
    'description' => __('Dark fiery design with red/orange accents on near-black background. Bold condensed uppercase headings. Perfect for fetish, burlesque, and adult nightlife events.', 'ensemble'),
    'version'     => '2.1.0',
    'author'      => 'Ensemble',
    'supports'    => array('events', 'artists', 'locations', 'single-event'),
    'mode'        => 'dark',
    'preview'     => 'preview.png',
    
    // Templates provided by this layout
    'templates'   => array(
        'event-card'     => 'event-card.php',
        'artist-card'    => 'artist-card.php',
        'location-card'  => 'location-card.php',
        'single-event'   => 'single-event.php',
    ),
    
    // Designer sections this layout customizes
    'sections'    => array(
        'hero'        => true,
        'description' => true,
        'lineup'      => true,
        'katalog'     => true,
        'location'    => true,
        'sidebar'     => true,
        'related'     => true,
        'share'       => true,
    ),
    
    // Features
    'features'    => array(
        'date_badge'           => true,
        'status_badge'         => true,
        'horizontal_artists'   => false,
        'location_logo'        => true,
        'hero_ticket_button'   => true,
        'edge_to_edge_images'  => true,
        'elegant_typography'   => true,
        'fire_glow_effects'    => true,
    ),
);
