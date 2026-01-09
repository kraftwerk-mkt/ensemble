<?php
/**
 * Club Layout Configuration
 * Dark, bold nightclub style
 * 
 * @package Ensemble
 * @version 2.1.0
 */

if (!defined('ABSPATH')) exit;

return array(
    'name'        => 'Club',
    'description' => __('Dark nightclub style with date badges, status indicators, and horizontal artist/location cards', 'ensemble'),
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
        'horizontal_artists'   => true,
        'location_logo'        => true,
        'hero_ticket_button'   => true,
        'edge_to_edge_images'  => true,
    ),
);
