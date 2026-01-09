<?php
/**
 * Ensemble Agenda Helper Functions
 * 
 * Functions for managing agenda items including breaks/pauses
 * 
 * @package Ensemble
 * @since 2.9.0
 */

if (!defined('ABSPATH')) exit;

/**
 * Get merged agenda from artists and breaks
 * Combines speaker sessions with breaks and sorts by time
 * 
 * @param int $event_id
 * @return array Sorted array of agenda items
 */
function ensemble_get_merged_agenda($event_id) {
    $agenda_items = array();
    
    // Get artist data
    $artist_ids = ensemble_get_field('event_artist', $event_id);
    if (!is_array($artist_ids)) {
        $artist_ids = array();
    }
    
    // Get artist times and venues
    $artist_times = get_post_meta($event_id, 'artist_times', true);
    if (!is_array($artist_times)) $artist_times = array();
    
    $artist_venues = get_post_meta($event_id, 'artist_venues', true);
    if (!is_array($artist_venues)) $artist_venues = array();
    
    // Get session titles (optional - for custom session names like "Keynote: Future of AI")
    $session_titles = get_post_meta($event_id, 'artist_session_titles', true);
    if (!is_array($session_titles)) $session_titles = array();
    
    // Add artists to agenda
    foreach ($artist_ids as $artist_id) {
        $artist_id = intval($artist_id);
        if (!$artist_id) continue;
        
        $artist_post = get_post($artist_id);
        if (!$artist_post) continue;
        
        $time = isset($artist_times[$artist_id]) ? $artist_times[$artist_id] : '';
        
        // Skip artists without time (they appear in speaker grid but not timeline)
        if (empty($time)) continue;
        
        $agenda_items[] = array(
            'type'          => 'session',
            'time'          => $time,
            'time_sort'     => ensemble_time_to_minutes($time),
            'artist_id'     => $artist_id,
            'artist_name'   => $artist_post->post_title,
            'artist_image'  => get_the_post_thumbnail_url($artist_id, 'thumbnail'),
            'artist_role'   => get_post_meta($artist_id, 'artist_role', true),
            'session_title' => isset($session_titles[$artist_id]) ? $session_titles[$artist_id] : '',
            'venue'         => isset($artist_venues[$artist_id]) ? $artist_venues[$artist_id] : '',
        );
    }
    
    // Get breaks/pauses
    $breaks = get_post_meta($event_id, '_agenda_breaks', true);
    if (is_array($breaks)) {
        foreach ($breaks as $break) {
            if (empty($break['time'])) continue;
            
            $agenda_items[] = array(
                'type'      => 'break',
                'time'      => $break['time'],
                'time_sort' => ensemble_time_to_minutes($break['time']),
                'title'     => $break['title'] ?? __('Break', 'ensemble'),
                'duration'  => $break['duration'] ?? '',
                'icon'      => $break['icon'] ?? 'pause',
            );
        }
    }
    
    // Sort by time
    usort($agenda_items, function($a, $b) {
        return $a['time_sort'] - $b['time_sort'];
    });
    
    return apply_filters('ensemble_merged_agenda', $agenda_items, $event_id);
}

/**
 * Convert time string to minutes for sorting
 * 
 * @param string $time Time string (e.g., "14:30" or "2:30 PM")
 * @return int Minutes from midnight
 */
function ensemble_time_to_minutes($time) {
    if (empty($time)) return 0;
    
    // Handle 24h format (14:30)
    if (preg_match('/^(\d{1,2}):(\d{2})$/', $time, $matches)) {
        return intval($matches[1]) * 60 + intval($matches[2]);
    }
    
    // Handle 12h format (2:30 PM)
    if (preg_match('/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i', $time, $matches)) {
        $hours = intval($matches[1]);
        $minutes = intval($matches[2]);
        $meridiem = strtoupper($matches[3]);
        
        if ($meridiem === 'PM' && $hours < 12) {
            $hours += 12;
        } elseif ($meridiem === 'AM' && $hours === 12) {
            $hours = 0;
        }
        
        return $hours * 60 + $minutes;
    }
    
    return 0;
}

/**
 * Get SVG icon for agenda break type
 * 
 * @param string $icon_type Icon type (coffee, lunch, networking, etc.)
 * @return string SVG markup
 */
function ensemble_get_agenda_icon($icon_type = 'pause') {
    $icons = array(
        'coffee' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M17 8h1a4 4 0 1 1 0 8h-1"/>
            <path d="M3 8h14v9a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V8z"/>
            <line x1="6" y1="2" x2="6" y2="4"/>
            <line x1="10" y1="2" x2="10" y2="4"/>
            <line x1="14" y1="2" x2="14" y2="4"/>
        </svg>',
        
        'lunch' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/>
            <path d="M7 2v20"/>
            <path d="M21 15V2v0a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Zm0 0v7"/>
        </svg>',
        
        'networking' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
            <circle cx="9" cy="7" r="4"/>
            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
        </svg>',
        
        'workshop' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
            <line x1="8" y1="21" x2="16" y2="21"/>
            <line x1="12" y1="17" x2="12" y2="21"/>
            <line x1="6" y1="8" x2="10" y2="8"/>
            <line x1="6" y1="12" x2="14" y2="12"/>
        </svg>',
        
        'registration' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
            <rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>
            <path d="M9 14l2 2 4-4"/>
        </svg>',
        
        'panel' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            <line x1="8" y1="8" x2="16" y2="8"/>
            <line x1="8" y1="12" x2="14" y2="12"/>
        </svg>',
        
        'keynote' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/>
            <path d="M19 10v2a7 7 0 0 1-14 0v-2"/>
            <line x1="12" y1="19" x2="12" y2="23"/>
            <line x1="8" y1="23" x2="16" y2="23"/>
        </svg>',
        
        'pause' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/>
            <polyline points="12 6 12 12 16 14"/>
        </svg>',
    );
    
    $icon = isset($icons[$icon_type]) ? $icons[$icon_type] : $icons['pause'];
    
    return apply_filters('ensemble_agenda_icon', $icon, $icon_type);
}

/**
 * Get available break types for wizard
 * 
 * @return array
 */
function ensemble_get_break_types() {
    return apply_filters('ensemble_break_types', array(
        'coffee'       => __('Kaffeepause', 'ensemble'),
        'lunch'        => __('Mittagspause', 'ensemble'),
        'networking'   => __('Networking', 'ensemble'),
        'registration' => __('Registrierung', 'ensemble'),
        'workshop'     => __('Workshop', 'ensemble'),
        'panel'        => __('Panel-Diskussion', 'ensemble'),
        'keynote'      => __('Keynote', 'ensemble'),
        'pause'        => __('Break', 'ensemble'),
    ));
}

/**
 * Save agenda breaks from wizard
 * 
 * @param int $event_id
 * @param array $breaks
 * @return bool
 */
function ensemble_save_agenda_breaks($event_id, $breaks) {
    if (!is_array($breaks)) {
        return delete_post_meta($event_id, '_agenda_breaks');
    }
    
    $sanitized = array();
    
    foreach ($breaks as $break) {
        if (empty($break['time'])) continue;
        
        $sanitized[] = array(
            'time'     => sanitize_text_field($break['time']),
            'title'    => sanitize_text_field($break['title'] ?? __('Break', 'ensemble')),
            'duration' => intval($break['duration'] ?? 0),
            'icon'     => sanitize_key($break['icon'] ?? 'pause'),
        );
    }
    
    if (empty($sanitized)) {
        return delete_post_meta($event_id, '_agenda_breaks');
    }
    
    return update_post_meta($event_id, '_agenda_breaks', $sanitized);
}
