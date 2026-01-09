<?php
/**
 * Event Data Loader
 * 
 * Zentrale Datenladung für alle Event-Templates
 * Include diese Datei am Anfang jedes single-event.php Templates
 *
 * @package Ensemble
 * @version 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Event ID
$event_id = get_the_ID();

// ============================================================
// BASIC FIELDS - Direkte Meta-Abfrage
// ============================================================
$event_date = get_post_meta($event_id, 'event_date', true);
$event_time = get_post_meta($event_id, 'event_time', true);
$event_time_end = get_post_meta($event_id, 'event_time_end', true);
$price = get_post_meta($event_id, 'event_price', true);
$ticket_url = get_post_meta($event_id, 'event_ticket_url', true);
$description = get_post_meta($event_id, 'event_description', true);

// ============================================================
// LOCATION - Integer ID sicherstellen
// ============================================================
$location_id = get_post_meta($event_id, 'event_location', true);
$location_id = intval($location_id);
$location = null;
$location_name = '';
$location_address = '';
$location_city = '';

if ($location_id > 0) {
    $location = get_post($location_id);
    if ($location && $location->post_type === 'ensemble_location') {
        $location_name = $location->post_title;
        $location_address = get_post_meta($location_id, 'location_address', true);
        $location_city = get_post_meta($location_id, 'location_city', true);
    }
}

// ============================================================
// ARTISTS - Serialisiertes Array verarbeiten
// ============================================================
$artist_data = get_post_meta($event_id, 'event_artist', true);
$artist_ids = array();
$artists = array();
$artist_names = array(); // For simple comma-separated display

if (!empty($artist_data)) {
    // Unserialize if necessary
    if (is_string($artist_data)) {
        $artist_data = maybe_unserialize($artist_data);
    }
    
    // Zu Array konvertieren
    if (is_array($artist_data)) {
        $artist_ids = array_map('intval', array_filter($artist_data));
    } elseif (is_numeric($artist_data)) {
        $artist_ids = array(intval($artist_data));
    }
    
    // Artists laden
    if (!empty($artist_ids)) {
        foreach ($artist_ids as $artist_id) {
            $artist = get_post($artist_id);
            if ($artist && $artist->post_type === 'ensemble_artist') {
                $artists[] = array(
                    'id' => $artist_id,
                    'name' => $artist->post_title,
                    'url' => get_permalink($artist_id)
                );
                $artist_names[] = $artist->post_title;
            }
        }
    }
}

// ============================================================
// GENRES - Event + Artist Genres kombinieren
// ============================================================
$event_genres = get_the_terms($event_id, 'ensemble_genre');
$all_genres = array();

// Get saved genre order (from wizard)
$genre_order = get_post_meta($event_id, '_event_genre_order', true);

// Add Event Genres
if ($event_genres && !is_wp_error($event_genres)) {
    // Create lookup array by term_id
    $genres_by_id = array();
    foreach ($event_genres as $genre) {
        $genres_by_id[$genre->term_id] = $genre->name;
    }
    
    // If we have a saved order, use it
    if (!empty($genre_order) && is_array($genre_order)) {
        foreach ($genre_order as $genre_id) {
            $genre_id = intval($genre_id);
            if (isset($genres_by_id[$genre_id])) {
                $all_genres[$genre_id] = $genres_by_id[$genre_id];
            }
        }
        // Add any genres that might not be in the order (fallback)
        foreach ($genres_by_id as $id => $name) {
            if (!isset($all_genres[$id])) {
                $all_genres[$id] = $name;
            }
        }
    } else {
        // No saved order, use default
        $all_genres = $genres_by_id;
    }
}

// Add Artist Genres (if enabled)
$show_artist_genres = get_post_meta($event_id, '_es_show_artist_genres', true);
if ($show_artist_genres && !empty($artist_ids)) {
    foreach ($artist_ids as $artist_id) {
        $artist_genres = get_the_terms($artist_id, 'ensemble_genre');
        if ($artist_genres && !is_wp_error($artist_genres)) {
            foreach ($artist_genres as $genre) {
                // Duplikate vermeiden
                if (!isset($all_genres[$genre->term_id])) {
                    $all_genres[$genre->term_id] = $genre->name;
                }
            }
        }
    }
}

// ============================================================
// CATEGORIES
// ============================================================
$categories = get_the_terms($event_id, 'ensemble_category');

// ============================================================
// DATE & TIME FORMATTING
// ============================================================
$formatted_date = '';
$formatted_time = '';
$formatted_end_time = '';

if ($event_date) {
    $formatted_date = date_i18n(get_option('date_format'), strtotime($event_date));
}

if ($event_time) {
    $formatted_time = date_i18n(get_option('time_format'), strtotime($event_time));
}

if ($event_time_end) {
    $formatted_end_time = date_i18n(get_option('time_format'), strtotime($event_time_end));
}

// ============================================================
// RECURRING EVENT
// ============================================================
$is_recurring = get_post_meta($event_id, 'is_recurring', true);
$recurring_rules = get_post_meta($event_id, 'recurring_rules', true);

// ============================================================
// FEATURED IMAGE
// ============================================================
$featured_image = get_the_post_thumbnail_url($event_id, 'large');

// ============================================================
// STATUS
// ============================================================
$status = get_post_meta($event_id, 'event_status', true);

// ============================================================
// BACKWARD COMPATIBILITY VARIABLES
// ============================================================
// Damit alte Template-Variablen weiter funktionieren
$start_date = $event_date;
$start_time = $event_time;
$end_time = $event_time_end;
$tickets_url = $ticket_url;