<?php
/**
 * Bristol Layout Configuration
 * 
 * Bold editorial design for urban festivals
 * Dark/Light mode support
 * 
 * @package Ensemble
 * @layout Bristol City Festival
 * @version 2.0.0
 */

if (!defined('ABSPATH')) exit;

return array(
    'name' => 'Bristol City Festival',
    'version' => '2.0.0',
    'description' => 'Bold editorial design with edge-to-edge grids, dramatic hover effects, and asymmetric layouts. Dark/Light mode toggle.',
    'author' => 'Ensemble',
    'supports_modes' => true,
    'default_mode' => 'dark',
    'templates' => array(
        'event-card',
        'artist-card',
        'location-card',
        'single-event',
        'single-artist',
        'single-location',
        'hero-slide',
    ),
    'assets' => array(
        'styles' => array('style.css'),
        'scripts' => array('bristol-mode.js'),
    ),
);
