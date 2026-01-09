<?php
/**
 * Pure Layout Configuration
 * 
 * Ultra-minimal, clean design with Dark/Light mode support
 * 
 * @package Ensemble
 * @version 2.0.0
 */

if (!defined('ABSPATH')) exit;

return array(
    'name' => 'Pure',
    'version' => '2.0.0',
    'description' => 'Ultra-minimal design. Clean typography, ghost buttons, thin lines. Dark/Light mode toggle.',
    'author' => 'Ensemble',
    'supports_modes' => true,
    'default_mode' => 'light',
    'templates' => array(
        'event-card',
        'artist-card',
        'artist-card-full',
        'location-card',
        'location-card-full',
        'single-event',
        'single-artist',
        'single-location',
    ),
);
