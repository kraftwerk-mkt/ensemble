<?php
/**
 * Kongress Layout Configuration
 * 
 * Professional conference/congress design
 * Navy & Copper color scheme, elegant typography
 * Optimized for multi-day events with agenda/lineup
 * 
 * @package Ensemble
 * @version 1.0.0
 */

if (!defined('ABSPATH')) exit;

return array(
    'name'            => 'Kongress',
    'version'         => '1.0.0',
    'description'     => 'Professionelles Kongress- und Konferenz-Design. Navy & Kupfer Farbschema, elegante Typografie, optimiert für mehrtägige Events mit Agenda.',
    'author'          => 'Ensemble',
    'supports_modes'  => false,  // Kein Dark Mode - seriöser Light Mode
    'default_mode'    => 'light',
    'category'        => 'professional',
    'tags'            => array('kongress', 'konferenz', 'business', 'professional', 'agenda'),
    
    // Templates die dieses Layout bereitstellt
    'templates' => array(
        'event-card',
        'artist-card',
        'artist-card-full',
        'location-card',
        'single-event',
        'single-artist',
        'single-location',
        'hero-slide',
    ),
    
    // Layout-spezifische Features
    'features' => array(
        'agenda_view'       => true,   // Agenda-Darstellung für Sessions
        'speaker_grid'      => true,   // Speaker-Grid auf Event-Seite
        'session_cards'     => true,   // Session-Karten
        'glassmorphism'     => true,   // Glassmorphism-Effekte
        'scroll_animations' => true,   // Scroll-basierte Animationen
        'stats_counter'     => true,   // Animierte Statistik-Zähler
    ),
    
    // Empfohlene Designer-Einstellungen (werden in preset.php definiert)
    'recommended_settings' => array(
        'heading_font'   => 'Playfair Display',
        'body_font'      => 'Inter',
        'button_style'   => 'solid',
        'card_shadow'    => 'medium',
        'card_hover'     => 'lift',
    ),
);
