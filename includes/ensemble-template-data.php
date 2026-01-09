<?php
/**
 * Ensemble Template Data Loader
 * 
 * Shared data loading functionality for all layout templates
 * Include this file in templates to get consistent data access
 *
 * @package Ensemble
 * @version 3.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

if (!function_exists('es_get_field')) {
    /**
     * Get field value with multiple fallback strategies
     * 
     * @param int $post_id Post ID
     * @param string $field_name Field name
     * @param string $type Post type hint (event, location, artist)
     * @return mixed
     */
    function es_get_field($post_id, $field_name, $type = '') {
        // Ensure we have valid parameters
        if (empty($post_id) || !is_numeric($post_id)) {
            $post_id = get_the_ID();
        }
        $post_id = intval($post_id);
        
        // Ensure field_name is a string
        if (!is_string($field_name) || empty($field_name)) {
            return null;
        }
        
        $value = null;
        
        // 1. Try direct post_meta first (most reliable)
        $prefixes = array('', $type . '_', 'es_' . $type . '_', 'es_', '_', 'event_', 'location_', 'artist_');
        foreach ($prefixes as $prefix) {
            $meta_key = $prefix . $field_name;
            $value = get_post_meta($post_id, $meta_key, true);
            if (!empty($value)) return $value;
        }
        
        // 2. Try ACF get_field (with error suppression)
        if (function_exists('get_field')) {
            try {
                // Suppress errors for ACF calls
                $value = @get_field($field_name, $post_id);
                if (!empty($value)) return $value;
            } catch (Exception $e) {
                // ACF error - continue with fallbacks
            }
        }
        
        // 3. Try ensemble_get_field if available (with error suppression)
        if (function_exists('ensemble_get_field')) {
            try {
                $value = @ensemble_get_field($field_name, $post_id);
                if (!empty($value)) return $value;
            } catch (Exception $e) {
                // Continue
            }
        }
        
        return $value;
    }
}

if (!function_exists('es_load_event_data')) {
    /**
     * Load all event data
     * 
     * @param int $event_id Event ID
     * @return array Event data array
     */
    function es_load_event_data($event_id) {
        $data = array(
            'id' => $event_id,
            'title' => get_the_title($event_id),
            'permalink' => get_permalink($event_id),
            'featured_image' => get_the_post_thumbnail_url($event_id, 'large'),
        );
        
        // Helper to ensure string values
        $to_string = function($value) {
            if (is_array($value)) {
                return implode(', ', array_filter(array_map(function($v) {
                    return is_scalar($v) ? (string)$v : '';
                }, $value)));
            }
            return is_scalar($value) ? (string)$value : '';
        };
        
        // Basic fields - ensure strings
        $data['date'] = $to_string(es_get_field($event_id, 'event_date', 'event') ?: es_get_field($event_id, 'date', 'event'));
        $data['time'] = $to_string(es_get_field($event_id, 'event_time', 'event') ?: es_get_field($event_id, 'time', 'event'));
        $data['time_end'] = $to_string(es_get_field($event_id, 'event_time_end', 'event') ?: es_get_field($event_id, 'time_end', 'event'));
        
        // Also load start_date/end_date (used by wizard and multi-day events)
        $data['start_date'] = $to_string(es_get_field($event_id, 'es_event_start_date', 'event') ?: es_get_field($event_id, 'event_start_date', 'event') ?: $data['date']);
        $data['end_date'] = $to_string(es_get_field($event_id, 'es_event_end_date', 'event') ?: es_get_field($event_id, 'event_end_date', 'event'));
        $data['start_time'] = $to_string(es_get_field($event_id, 'es_event_start_time', 'event') ?: es_get_field($event_id, 'event_start_time', 'event') ?: $data['time']);
        $data['end_time'] = $to_string(es_get_field($event_id, 'es_event_end_time', 'event') ?: es_get_field($event_id, 'event_end_time', 'event') ?: $data['time_end']);
        
        $data['price'] = $to_string(es_get_field($event_id, 'event_price', 'event') ?: es_get_field($event_id, 'price', 'event'));
        $data['price_note'] = $to_string(es_get_field($event_id, 'event_price_note', 'event') ?: es_get_field($event_id, 'price_note', 'event') ?: get_post_meta($event_id, ES_Meta_Keys::EVENT_PRICE_NOTE, true));
        $data['ticket_url'] = $to_string(es_get_field($event_id, 'event_ticket_url', 'event') ?: es_get_field($event_id, 'ticket_url', 'event'));
        $data['button_text'] = $to_string(es_get_field($event_id, 'event_button_text', 'event') ?: es_get_field($event_id, 'button_text', 'event')) ?: __('Get Tickets', 'ensemble');
        $data['description'] = es_get_field($event_id, 'event_description', 'event') ?: es_get_field($event_id, 'description', 'event');
        if (is_array($data['description'])) {
            $data['description'] = '';
        }
        $data['venue'] = $to_string(es_get_field($event_id, 'event_venue', 'event') ?: es_get_field($event_id, 'venue', 'event'));
        
        // Venue configuration (custom names, genres per room)
        $data['venue_config'] = get_post_meta($event_id, 'venue_config', true) ?: array();
        
        // Show artist genres option
        $show_artist_genres = get_post_meta($event_id, '_es_show_artist_genres', true);
        $data['show_artist_genres'] = ($show_artist_genres === '1');
        
        // Badge settings - use constants for consistency
        $badge = get_post_meta($event_id, ES_Meta_Keys::EVENT_BADGE, true);
        $badge_custom = get_post_meta($event_id, ES_Meta_Keys::EVENT_BADGE_CUSTOM, true);
        $data['badge'] = !empty($badge_custom) ? $badge_custom : $badge;
        $data['badge_raw'] = ($badge === 'show_category') ? 'category' : $badge; // For CSS class
        $data['badge_custom'] = $badge_custom;
        
        // Get badge label based on value
        $badge_labels = array(
            'sold_out' => __('Sold Out', 'ensemble'),
            'few_tickets' => __('Few Tickets Left', 'ensemble'),
            'free' => __('Free Entry', 'ensemble'),
            'new' => __('New', 'ensemble'),
            'premiere' => __('Premiere', 'ensemble'),
            'last_show' => __('Last Show', 'ensemble'),
            'special' => __('Special Event', 'ensemble'),
        );
        
        // Special case: show_category displays the first category as badge
        if ($badge === 'show_category' && !empty($data['categories'])) {
            $first_cat = is_array($data['categories']) ? $data['categories'][0] : $data['categories'];
            $data['badge_label'] = is_object($first_cat) ? $first_cat->name : $first_cat;
        } else {
            $data['badge_label'] = isset($badge_labels[$badge]) ? $badge_labels[$badge] : $badge_custom;
        }
        
        // Facebook URL - try multiple field names
        $facebook_url = '';
        $facebook_keys = array('event_facebook_url', 'event_facebook_event', 'event_facebook', 'facebook_url', 'facebook_event', 'facebook');
        foreach ($facebook_keys as $key) {
            $value = es_get_field($event_id, $key, 'event');
            if (empty($value)) {
                $value = get_post_meta($event_id, $key, true);
            }
            if (!empty($value)) {
                $facebook_url = $to_string($value);
                break;
            }
        }
        $data['facebook_url'] = $facebook_url;
        
        // Additional Info (directions, entry requirements, etc.) - use constant
        $data['additional_info'] = es_get_field($event_id, 'event_additional_info', 'event') ?: get_post_meta($event_id, ES_Meta_Keys::EVENT_ADDITIONAL_INFO, true);
        if (is_array($data['additional_info'])) {
            $data['additional_info'] = '';
        }
        
        // External Link - use constants
        $data['external_url'] = $to_string(es_get_field($event_id, 'event_external_url', 'event') ?: get_post_meta($event_id, ES_Meta_Keys::EVENT_EXTERNAL_URL, true));
        $data['external_text'] = $to_string(es_get_field($event_id, 'event_external_text', 'event') ?: get_post_meta($event_id, ES_Meta_Keys::EVENT_EXTERNAL_TEXT, true)) ?: __('More Info', 'ensemble');
        
        // Status - use constant
        $status = get_post_meta($event_id, ES_Meta_Keys::EVENT_STATUS, true);
        $data['status'] = is_string($status) && !empty($status) ? $status : 'publish';
        
        // Location
        $location_id = es_get_field($event_id, 'event_location', 'event') ?: es_get_field($event_id, 'location', 'event');
        if (is_array($location_id)) {
            $location_id = !empty($location_id[0]) ? (is_object($location_id[0]) ? $location_id[0]->ID : $location_id[0]) : null;
        }
        $data['location_id'] = intval($location_id);
        $data['location'] = $data['location_id'] ? es_load_location_data($data['location_id']) : null;
        
        // Add room/venue to location display name if selected
        if ($data['location'] && !empty($data['venue'])) {
            $data['location']['room'] = $data['venue'];
            $data['location']['display_name'] = $data['location']['name'] . ' – ' . $data['venue'];
        } elseif ($data['location']) {
            $data['location']['room'] = '';
            $data['location']['display_name'] = $data['location']['name'];
        }
        
        // Artists
        $data['artists'] = es_load_event_artists($event_id);
        
        // Categories
        $data['categories'] = get_the_terms($event_id, 'ensemble_category');
        if (is_wp_error($data['categories'])) $data['categories'] = array();
        
        // Genres - respect saved order from wizard
        $raw_genres = get_the_terms($event_id, 'ensemble_genre');
        $data['genres'] = array();
        
        if ($raw_genres && !is_wp_error($raw_genres)) {
            // Get saved genre order
            $genre_order = get_post_meta($event_id, '_event_genre_order', true);
            
            if (!empty($genre_order) && is_array($genre_order)) {
                // Create lookup by term_id
                $genres_by_id = array();
                foreach ($raw_genres as $genre) {
                    $genres_by_id[$genre->term_id] = $genre;
                }
                
                // Sort by saved order
                foreach ($genre_order as $genre_id) {
                    $genre_id = intval($genre_id);
                    if (isset($genres_by_id[$genre_id])) {
                        $data['genres'][] = $genres_by_id[$genre_id];
                        unset($genres_by_id[$genre_id]);
                    }
                }
                
                // Add any remaining genres not in order
                foreach ($genres_by_id as $genre) {
                    $data['genres'][] = $genre;
                }
            } else {
                // No saved order, use as-is
                $data['genres'] = $raw_genres;
            }
        }
        
        // Recurring
        $data['is_recurring'] = !empty(get_post_meta($event_id, '_recurring_pattern', true));
        
        // Gallery - ensure it's always an array or empty
        $gallery_data = es_get_field($event_id, 'event_gallery', 'event') ?: es_get_field($event_id, 'gallery', 'event');
        if (!is_array($gallery_data)) {
            // Maybe it's a serialized string or comma-separated IDs
            if (is_string($gallery_data) && !empty($gallery_data)) {
                $gallery_data = maybe_unserialize($gallery_data);
                if (!is_array($gallery_data)) {
                    // Try comma-separated
                    $gallery_data = array_filter(array_map('intval', explode(',', $gallery_data)));
                }
            } else {
                $gallery_data = array();
            }
        }
        $data['gallery'] = $gallery_data;
        
        // Format date/time - ALWAYS set keys to avoid undefined errors
        $data['formatted_date'] = '';
        $data['formatted_time'] = '';
        $data['formatted_time_end'] = '';
        
        if (!empty($data['date'])) {
            $data['formatted_date'] = date_i18n(get_option('date_format'), strtotime($data['date']));
        }
        if (!empty($data['time'])) {
            $data['formatted_time'] = date_i18n(get_option('time_format'), strtotime($data['time']));
        }
        if (!empty($data['time_end'])) {
            $data['formatted_time_end'] = date_i18n(get_option('time_format'), strtotime($data['time_end']));
        }
        
        // ============================================================================
        // MULTI-DAY / PARENT-CHILD EVENT SUPPORT
        // ============================================================================
        
        // Check if this event has children (is a parent/master event)
        $has_children = get_post_meta($event_id, ES_Meta_Keys::EVENT_HAS_CHILDREN, true);
        $data['has_children'] = ($has_children === '1' || $has_children === true);
        $data['child_event_ids'] = array();
        $data['child_events'] = array();
        
        // Load child events if this is a parent
        if ($data['has_children']) {
            $child_query = get_posts(array(
                'post_type' => ensemble_get_post_type(),
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'meta_key' => ES_Meta_Keys::EVENT_PARENT_ID,
                'meta_value' => $event_id,
                'orderby' => 'meta_value',
                'meta_key' => ES_Meta_Keys::EVENT_CHILD_ORDER,
                'order' => 'ASC',
                'fields' => 'ids',
            ));
            
            // Fallback: If no results with child_order, try ordering by start_date
            if (empty($child_query)) {
                $child_query = get_posts(array(
                    'post_type' => ensemble_get_post_type(),
                    'post_status' => 'publish',
                    'posts_per_page' => -1,
                    'meta_query' => array(
                        array(
                            'key' => ES_Meta_Keys::EVENT_PARENT_ID,
                            'value' => $event_id,
                            'compare' => '=',
                        ),
                    ),
                    'meta_key' => 'es_event_start_date',
                    'orderby' => 'meta_value',
                    'order' => 'ASC',
                    'fields' => 'ids',
                ));
            }
            
            $data['child_event_ids'] = $child_query;
        }
        
        // Check if this event is a child (has a parent)
        $parent_id = get_post_meta($event_id, ES_Meta_Keys::EVENT_PARENT_ID, true);
        $data['parent_event_id'] = $parent_id ? intval($parent_id) : 0;
        $data['is_child_event'] = ($data['parent_event_id'] > 0);
        
        // Load parent event info if this is a child
        if ($data['is_child_event']) {
            $parent_post = get_post($data['parent_event_id']);
            if ($parent_post) {
                $data['parent_event_title'] = $parent_post->post_title;
                $data['parent_event_permalink'] = get_permalink($parent_post->ID);
            } else {
                $data['parent_event_title'] = '';
                $data['parent_event_permalink'] = '';
                $data['parent_event_id'] = 0;
                $data['is_child_event'] = false;
            }
        } else {
            $data['parent_event_title'] = '';
            $data['parent_event_permalink'] = '';
        }
        
        // Calculate duration in days for multi-day events
        $data['duration_days'] = 1;
        if (!empty($data['start_date']) && !empty($data['end_date']) && $data['end_date'] !== $data['start_date']) {
            $start_ts = strtotime($data['start_date']);
            $end_ts = strtotime($data['end_date']);
            if ($start_ts && $end_ts) {
                $data['duration_days'] = floor(($end_ts - $start_ts) / 86400) + 1;
            }
        }
        
        return $data;
    }
}

if (!function_exists('es_load_location_data')) {
    /**
     * Load all location data
     * 
     * @param int $location_id Location ID
     * @return array Location data array
     */
    function es_load_location_data($location_id) {
        $location = get_post($location_id);
        if (!$location) return null;
        
        // Helper to ensure string values
        $to_string = function($value) {
            if (is_array($value)) {
                return implode(', ', array_filter(array_map(function($v) {
                    return is_scalar($v) ? (string)$v : '';
                }, $value)));
            }
            return is_scalar($value) ? (string)$value : '';
        };
        
        // Get link settings
        $link_data = function_exists('ensemble_get_location_link') 
            ? ensemble_get_location_link($location_id) 
            : array('url' => get_permalink($location_id), 'external' => false, 'new_tab' => false);
        
        $link_enabled = function_exists('ensemble_should_link_locations') 
            ? ensemble_should_link_locations() 
            : true;
        
        return array(
            'id' => $location_id,
            'name' => $location->post_title,
            'title' => $location->post_title,
            'url' => get_permalink($location_id), // Always the internal permalink
            'permalink' => get_permalink($location_id),
            // New link settings
            'link_url' => $link_data ? $link_data['url'] : null,
            'link_enabled' => $link_enabled,
            'link_external' => $link_data ? $link_data['external'] : false,
            'link_new_tab' => $link_data ? $link_data['new_tab'] : false,
            // Content
            'description' => $location->post_content,
            'image' => get_the_post_thumbnail_url($location_id, 'large'),
            'address' => $to_string(es_get_field($location_id, 'address', 'location')),
            'city' => $to_string(es_get_field($location_id, 'city', 'location')),
            'state' => $to_string(es_get_field($location_id, 'state', 'location')),
            'zip' => $to_string(es_get_field($location_id, 'zip_code', 'location')),
            'country' => $to_string(es_get_field($location_id, 'country', 'location')),
            'phone' => $to_string(es_get_field($location_id, 'phone', 'location')),
            'email' => $to_string(es_get_field($location_id, 'email', 'location')),
            'website' => $to_string(es_get_field($location_id, 'website', 'location')),
            'capacity' => $to_string(es_get_field($location_id, 'capacity', 'location')),
            'opening_hours' => es_get_field($location_id, 'opening_hours', 'location'), // Keep as-is, might be array
            'additional_info' => get_post_meta($location_id, 'location_additional_info', true),
        );
    }
}

if (!function_exists('es_load_artist_data')) {
    /**
     * Load all artist data
     * 
     * @param int $artist_id Artist ID
     * @return array Artist data array
     */
    function es_load_artist_data($artist_id) {
        $artist = get_post($artist_id);
        if (!$artist) return null;
        
        // Helper to ensure string values
        $to_string = function($value) {
            if (is_array($value)) {
                return implode(', ', array_filter(array_map(function($v) {
                    return is_scalar($v) ? (string)$v : '';
                }, $value)));
            }
            return is_scalar($value) ? (string)$value : '';
        };
        
        // Get genre
        $genre = es_get_field($artist_id, 'genre', 'artist');
        if (is_array($genre)) {
            $genre = implode(', ', array_filter(array_map(function($v) {
                return is_scalar($v) ? (string)$v : '';
            }, $genre)));
        }
        if (empty($genre)) {
            $genres = wp_get_post_terms($artist_id, 'ensemble_genre');
            if (!is_wp_error($genres) && !empty($genres)) {
                $genre_names = array();
                foreach ($genres as $genre_term) {
                    $genre_names[] = $genre_term->name;
                }
                $genre = implode(', ', $genre_names);
            }
        }
        
        // Get artist type
        $artist_type = '';
        $artist_types = wp_get_post_terms($artist_id, 'ensemble_artist_type');
        if (!is_wp_error($artist_types) && !empty($artist_types)) {
            $type_names = array();
            foreach ($artist_types as $type_term) {
                $type_names[] = $type_term->name;
            }
            $artist_type = implode(', ', $type_names);
        }
        
        // Get link settings
        $link_enabled = function_exists('ensemble_should_link_artists') 
            ? ensemble_should_link_artists() 
            : true;
        
        // Get the correct link URL based on settings
        $link_data = function_exists('ensemble_get_artist_link') 
            ? ensemble_get_artist_link($artist_id) 
            : array('url' => get_permalink($artist_id), 'external' => false, 'new_tab' => false);
        
        // Get social links array and parse into individual platforms
        $social_links_array = es_get_field($artist_id, 'social_links', 'artist');
        if (!is_array($social_links_array)) {
            $social_links_array = array();
        }
        
        // Parse social links URLs to extract platforms
        $parsed_social = array(
            'facebook' => '',
            'instagram' => '',
            'twitter' => '',
            'soundcloud' => '',
            'spotify' => '',
            'youtube' => '',
            'tiktok' => '',
            'bandcamp' => '',
            'mixcloud' => '',
            'linkedin' => '',
            'vimeo' => '',
            'pinterest' => '',
            'twitch' => '',
            'telegram' => '',
        );
        
        foreach ($social_links_array as $url) {
            if (empty($url) || !is_string($url)) continue;
            $url_lower = strtolower($url);
            
            if (strpos($url_lower, 'facebook.com') !== false || strpos($url_lower, 'fb.com') !== false) {
                $parsed_social['facebook'] = $url;
            } elseif (strpos($url_lower, 'instagram.com') !== false) {
                $parsed_social['instagram'] = $url;
            } elseif (strpos($url_lower, 'twitter.com') !== false || strpos($url_lower, 'x.com') !== false) {
                $parsed_social['twitter'] = $url;
            } elseif (strpos($url_lower, 'soundcloud.com') !== false) {
                $parsed_social['soundcloud'] = $url;
            } elseif (strpos($url_lower, 'spotify.com') !== false) {
                $parsed_social['spotify'] = $url;
            } elseif (strpos($url_lower, 'youtube.com') !== false || strpos($url_lower, 'youtu.be') !== false) {
                // Only use if no dedicated youtube field
                if (empty(es_get_field($artist_id, 'youtube', 'artist'))) {
                    $parsed_social['youtube'] = $url;
                }
            } elseif (strpos($url_lower, 'tiktok.com') !== false) {
                $parsed_social['tiktok'] = $url;
            } elseif (strpos($url_lower, 'bandcamp.com') !== false) {
                $parsed_social['bandcamp'] = $url;
            } elseif (strpos($url_lower, 'mixcloud.com') !== false) {
                $parsed_social['mixcloud'] = $url;
            } elseif (strpos($url_lower, 'linkedin.com') !== false) {
                $parsed_social['linkedin'] = $url;
            } elseif (strpos($url_lower, 'vimeo.com') !== false) {
                // Only use if no dedicated vimeo field
                if (empty(es_get_field($artist_id, 'vimeo', 'artist'))) {
                    $parsed_social['vimeo'] = $url;
                }
            } elseif (strpos($url_lower, 'pinterest.com') !== false) {
                $parsed_social['pinterest'] = $url;
            } elseif (strpos($url_lower, 'twitch.tv') !== false) {
                $parsed_social['twitch'] = $url;
            } elseif (strpos($url_lower, 't.me') !== false || strpos($url_lower, 'telegram') !== false) {
                $parsed_social['telegram'] = $url;
            }
        }
        
        // Get dedicated fields (override parsed if exists)
        $youtube_field = $to_string(es_get_field($artist_id, 'youtube', 'artist'));
        $vimeo_field = $to_string(es_get_field($artist_id, 'vimeo', 'artist'));
        
        return array(
            'id' => $artist_id,
            'name' => $artist->post_title,
            'title' => $artist->post_title,
            'url' => get_permalink($artist_id), // Always the internal permalink
            'permalink' => get_permalink($artist_id),
            // New link settings
            'link_url' => $link_enabled && $link_data ? $link_data['url'] : null,
            'link_enabled' => $link_enabled,
            'link_external' => $link_data ? ($link_data['external'] ?? false) : false,
            'link_new_tab' => $link_data ? ($link_data['new_tab'] ?? false) : false,
            // Content
            'description' => $artist->post_content,
            'bio' => $artist->post_content,
            'image' => get_the_post_thumbnail_url($artist_id, 'large'),
            'genre' => $to_string($genre),
            'artist_type' => $to_string($artist_type),
            'references' => $to_string(es_get_field($artist_id, 'references', 'artist')),
            'origin' => $to_string(es_get_field($artist_id, 'origin', 'artist')),
            'booking_email' => $to_string(es_get_field($artist_id, 'email', 'artist')),
            // Social links - from parsed array + dedicated fields
            'website' => $to_string(es_get_field($artist_id, 'website', 'artist')),
            'facebook' => $parsed_social['facebook'],
            'instagram' => $parsed_social['instagram'],
            'twitter' => $parsed_social['twitter'],
            'soundcloud' => $parsed_social['soundcloud'],
            'spotify' => $parsed_social['spotify'],
            'youtube' => !empty($youtube_field) ? $youtube_field : $parsed_social['youtube'],
            'vimeo' => !empty($vimeo_field) ? $vimeo_field : $parsed_social['vimeo'],
            'tiktok' => $parsed_social['tiktok'],
            'bandcamp' => $parsed_social['bandcamp'],
            'mixcloud' => $parsed_social['mixcloud'],
            'linkedin' => $parsed_social['linkedin'],
            'pinterest' => $parsed_social['pinterest'],
            'twitch' => $parsed_social['twitch'],
            'telegram' => $parsed_social['telegram'],
            // Raw social links array for custom use
            'social_links' => $social_links_array,
        );
    }
}

if (!function_exists('es_load_event_artists')) {
    /**
     * Load artists for an event with times and venues
     * 
     * @param int $event_id Event ID
     * @return array Artists array
     */
    function es_load_event_artists($event_id) {
        $artist_data = es_get_field($event_id, 'event_artist', 'event') ?: es_get_field($event_id, 'artist', 'event');
        
        $artist_ids = array();
        
        // Extract artist IDs
        if (!empty($artist_data)) {
            if (is_string($artist_data)) {
                $artist_data = maybe_unserialize($artist_data);
            }
            
            if (is_array($artist_data)) {
                foreach ($artist_data as $item) {
                    if (is_object($item) && isset($item->ID)) {
                        $artist_ids[] = $item->ID;
                    } elseif (is_numeric($item)) {
                        $artist_ids[] = intval($item);
                    }
                }
            } elseif (is_numeric($artist_data)) {
                $artist_ids[] = intval($artist_data);
            }
        }
        
        // Get artist order
        $artist_order = get_post_meta($event_id, '_artist_order', true);
        if (!empty($artist_order)) {
            $order_array = array_map('intval', explode(',', $artist_order));
            $ordered_ids = array();
            foreach ($order_array as $oid) {
                if (in_array($oid, $artist_ids)) {
                    $ordered_ids[] = $oid;
                }
            }
            foreach ($artist_ids as $aid) {
                if (!in_array($aid, $ordered_ids)) {
                    $ordered_ids[] = $aid;
                }
            }
            $artist_ids = $ordered_ids;
        }
        
        // Get times and venues
        $artist_times = get_post_meta($event_id, 'artist_times', true);
        if (!is_array($artist_times)) $artist_times = array();
        
        $artist_venues = get_post_meta($event_id, 'artist_venues', true);
        if (!is_array($artist_venues)) $artist_venues = array();
        
        $artist_session_titles = get_post_meta($event_id, 'artist_session_titles', true);
        if (!is_array($artist_session_titles)) $artist_session_titles = array();
        
        // Normalize array keys (handle string/int mismatch from JSON storage)
        $times_normalized = array();
        foreach ($artist_times as $key => $value) {
            $times_normalized[intval($key)] = $value;
        }
        
        $venues_normalized = array();
        foreach ($artist_venues as $key => $value) {
            $venues_normalized[intval($key)] = $value;
        }
        
        $session_titles_normalized = array();
        foreach ($artist_session_titles as $key => $value) {
            $session_titles_normalized[intval($key)] = $value;
        }
        
        // Load full artist data
        $artists = array();
        foreach ($artist_ids as $artist_id) {
            $artist = es_load_artist_data($artist_id);
            if ($artist) {
                $aid = intval($artist_id);
                $artist['time'] = isset($times_normalized[$aid]) ? $times_normalized[$aid] : '';
                $artist['venue'] = isset($venues_normalized[$aid]) ? $venues_normalized[$aid] : '';
                $artist['session_title'] = isset($session_titles_normalized[$aid]) ? $session_titles_normalized[$aid] : '';
                if ($artist['time']) {
                    $artist['time_formatted'] = date_i18n(get_option('time_format'), strtotime($artist['time']));
                }
                $artists[] = $artist;
            }
        }
        
        return $artists;
    }
}

if (!function_exists('es_get_upcoming_events_at_location')) {
    /**
     * Get upcoming events at a location
     * 
     * @param int $location_id Location ID
     * @param int $limit Number of events
     * @return WP_Query
     */
    function es_get_upcoming_events_at_location($location_id, $limit = 10) {
        return new WP_Query(array(
            'post_type' => ensemble_get_post_type(),
            'posts_per_page' => $limit,
            'post_status' => 'publish',
            'meta_query' => array(
                'relation' => 'OR',
                array('key' => 'es_event_location', 'value' => $location_id, 'compare' => '='),
                array('key' => 'event_location', 'value' => $location_id, 'compare' => '='),
                array('key' => '_event_location', 'value' => $location_id, 'compare' => '='),
            ),
            'orderby' => 'meta_value',
            'meta_key' => 'es_event_start_date',
            'order' => 'ASC'
        ));
    }
}

if (!function_exists('es_get_upcoming_events_by_artist')) {
    /**
     * Get upcoming events by an artist
     * 
     * @param int $artist_id Artist ID
     * @param int $limit Number of events
     * @return WP_Query
     */
    function es_get_upcoming_events_by_artist($artist_id, $limit = 10) {
        return new WP_Query(array(
            'post_type' => ensemble_get_post_type(),
            'posts_per_page' => $limit,
            'post_status' => 'publish',
            'meta_query' => array(
                'relation' => 'OR',
                array('key' => 'es_event_artist', 'value' => $artist_id, 'compare' => 'LIKE'),
                array('key' => 'event_artist', 'value' => $artist_id, 'compare' => 'LIKE'),
                array('key' => 'es_event_artist', 'value' => '"' . $artist_id . '"', 'compare' => 'LIKE'),
                array('key' => 'event_artist', 'value' => '"' . $artist_id . '"', 'compare' => 'LIKE'),
            ),
            'orderby' => 'meta_value',
            'meta_key' => 'es_event_start_date',
            'order' => 'ASC'
        ));
    }
}

// Ensure hooks file is loaded
if (!function_exists('ensemble_before_event')) {
    $hooks_file = dirname(__FILE__) . '/ensemble-template-hooks.php';
    if (file_exists($hooks_file)) {
        require_once $hooks_file;
    }
}

if (!function_exists('es_group_artists_by_venue')) {
    /**
     * Group artists by their assigned venue/room
     * 
     * Returns an array with:
     * - 'has_venues' => bool (true if at least one artist has a venue assigned)
     * - 'groups' => array of venue_name => artists
     * 
     * @param array $artists Artists array from es_load_event_artists
     * @return array
     */
    function es_group_artists_by_venue($artists) {
        $result = array(
            'has_venues' => false,
            'groups' => array()
        );
        
        if (empty($artists)) {
            return $result;
        }
        
        // Check if any artist has a venue assigned
        $has_any_venue = false;
        foreach ($artists as $artist) {
            if (!empty($artist['venue'])) {
                $has_any_venue = true;
                break;
            }
        }
        
        $result['has_venues'] = $has_any_venue;
        
        if ($has_any_venue) {
            // Group by venue
            foreach ($artists as $artist) {
                $venue_name = !empty($artist['venue']) ? $artist['venue'] : __('Other', 'ensemble');
                if (!isset($result['groups'][$venue_name])) {
                    $result['groups'][$venue_name] = array();
                }
                $result['groups'][$venue_name][] = $artist;
            }
        } else {
            // No venues - single group with empty key
            $result['groups'][''] = $artists;
        }
        
        return $result;
    }
}
