<?php
/**
 * Event Wizard
 * 
 * Handles wizard functionality
 *
 * @package Ensemble
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class ES_Wizard {
    
    /**
     * Initialize wizard
     */
    public function init() {
        // Nothing to init yet, but we'll use this later
    }
    
    /**
     * Get all events
     * @return array
     */
    public function get_events($args = array()) {
        // Get mapped field name for date (or use default)
        $date_field = ensemble_get_mapped_field('event_date');
        
        // If mapped to ACF field (field_xxx), we need to use the field name, not key
        if ($date_field && strpos($date_field, 'field_') === 0 && function_exists('acf_get_field')) {
            $acf_field = acf_get_field($date_field);
            if ($acf_field && isset($acf_field['name'])) {
                $date_field = $acf_field['name'];
            }
        }
        
        // Fallback to default field name
        if (empty($date_field)) {
            $date_field = 'event_date';
        }
        
        $defaults = array(
            'post_type'      => ensemble_get_post_type(),
            'posts_per_page' => -1,
            'post_status'    => array('publish', 'draft', 'future', 'pending'),
            'orderby'        => 'date',
            'order'          => 'DESC',
        );
        
        // Only filter by ensemble_category for 'post' type
        // For custom post types, show ALL posts of that type
        if (ensemble_get_post_type() === 'post') {
            $defaults['tax_query'] = array(
                array(
                    'taxonomy' => 'ensemble_category',
                    'operator' => 'EXISTS',
                ),
            );
        }
        
        $args = wp_parse_args($args, $defaults);
        $posts = get_posts($args);
        
        $events = array();
        foreach ($posts as $post) {
            $events[] = $this->format_event($post);
        }
        
        // Sort by event date in PHP (more reliable with ACF field mapping)
        usort($events, function($a, $b) {
            $date_a = isset($a['event_date']) ? $a['event_date'] : '';
            $date_b = isset($b['event_date']) ? $b['event_date'] : '';
            return strcmp($date_a, $date_b);
        });
        
        return $events;
    }
    
    /**
     * Get single event
     * @param int $event_id
     * @return array|false
     */
    public function get_event($event_id) {
        $post = get_post($event_id);
        if (!$post) {
            return false;
        }
        
        return $this->format_event($post);
    }
    
    /**
     * Format event data
     * @param WP_Post $post
     * @return array
     */
    private function format_event($post) {
        // Auto-sync locations and artists from mapped fields
        // This creates ensemble_location/ensemble_artist posts if they don't exist
        if (function_exists('ensemble_sync_event_relations')) {
            ensemble_sync_event_relations($post->ID);
        }
        
        // Try to get event data using field mapping helper first
        $event_data = ensemble_get_fields(array(
            'event_description',
            'event_additional_info',
            'event_date',
            'event_time',
            'event_time_end',
            'event_location',
            'event_artist',
            'event_price',
            'event_ticket_url',
            'event_button_text',
            'event_external_url',
            'event_external_text',
        ), $post->ID);
        
        // Fallback: If ACF data is empty, try direct post meta
        foreach ($event_data as $key => $value) {
            if (empty($value)) {
                $meta_value = get_post_meta($post->ID, $key, true);
                if (!empty($meta_value)) {
                    $event_data[$key] = $meta_value;
                }
            }
        }
        
        // Check if this is a recurring event (parent)
        $is_recurring = (bool) get_post_meta($post->ID, '_es_is_recurring', true);
        
        // Get event status (defaults to post_status or 'publish')
        $event_status = get_post_meta($post->ID, '_event_status', true);
        if (empty($event_status)) {
            $event_status = $post->post_status === 'draft' ? 'draft' : 'publish';
        }
        
        // Get artist order
        $artist_order = get_post_meta($post->ID, '_artist_order', true);
        
        // Get agenda breaks for timeline/agenda view
        $agenda_breaks = get_post_meta($post->ID, '_agenda_breaks', true);
        
        // Get session titles (custom names for sessions in agenda)
        $session_titles = get_post_meta($post->ID, 'session_titles', true);
        
        // Get full agenda from Agenda Add-on (if active)
        $full_agenda = array('days' => array(), 'rooms' => array(), 'tracks' => array());
        if (function_exists('ensemble_get_agenda')) {
            $full_agenda = ensemble_get_agenda($post->ID);
        }
        
        $event = array(
            'id'          => $post->ID,
            'title'       => $post->post_title,
            'description' => $event_data['event_description'] ?? '',
            'additional_info' => $event_data['event_additional_info'] ?? '',
            'date'        => $event_data['event_date'] ?? '',
            'time'        => $event_data['event_time'] ?? '',
            'time_end'    => $event_data['event_time_end'] ?? '',
            'location_id' => $event_data['event_location'] ?? '',
            'artist_ids'  => $event_data['event_artist'] ?? array(),
            'artist_order' => $artist_order,
            'agenda_breaks' => is_array($agenda_breaks) ? $agenda_breaks : array(),
            'session_titles' => is_array($session_titles) ? $session_titles : array(),
            'agenda'      => $full_agenda,
            'price'       => $event_data['event_price'] ?? '',
            'ticket_url'  => $event_data['event_ticket_url'] ?? '',
            'button_text' => $event_data['event_button_text'] ?? '',
            'external_url' => $event_data['event_external_url'] ?? '',
            'external_text' => $event_data['event_external_text'] ?? '',
            'featured_image' => get_the_post_thumbnail_url($post->ID, 'medium'),
            'is_recurring' => $is_recurring,
            'event_status' => $event_status,
        );
        
        // Check if this event was converted from a virtual event
        $recurring_parent = get_post_meta($post->ID, '_es_recurring_parent', true);
        if ($recurring_parent) {
            $event['was_virtual'] = true;
            $event['parent_id'] = $recurring_parent;
        } else {
            $event['was_virtual'] = false;
        }
        
        // ====================================
        // Duration Type System (Festival/Exhibition)
        // ====================================
        
        // Get duration type (default: single)
        $duration_type = get_post_meta($post->ID, ES_Meta_Keys::EVENT_DURATION_TYPE, true);
        $event['duration_type'] = !empty($duration_type) ? $duration_type : 'single';
        
        // Get end date for multi-day events
        $date_end = get_post_meta($post->ID, ES_Meta_Keys::EVENT_DATE_END, true);
        $event['date_end'] = $date_end ?: '';
        
        // Check if this is a parent event (has children)
        $has_children = get_post_meta($post->ID, ES_Meta_Keys::EVENT_HAS_CHILDREN, true);
        $event['has_children'] = $has_children === '1';
        
        // Get parent event ID (if this is a child event)
        $parent_event_id = get_post_meta($post->ID, ES_Meta_Keys::EVENT_PARENT_ID, true);
        $event['parent_event_id'] = $parent_event_id ? intval($parent_event_id) : 0;
        
        // Get parent event info if this is a child
        if ($event['parent_event_id'] > 0) {
            $parent_post = get_post($event['parent_event_id']);
            if ($parent_post) {
                $event['parent_event_title'] = $parent_post->post_title;
            } else {
                $event['parent_event_title'] = '';
                $event['parent_event_id'] = 0; // Clear invalid parent
            }
        } else {
            $event['parent_event_title'] = '';
        }
        
        // Get child events count (if this is a parent)
        if ($event['has_children']) {
            $child_events = get_posts(array(
                'post_type'      => ensemble_get_post_type(),
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'meta_query'     => array(
                    array(
                        'key'     => ES_Meta_Keys::EVENT_PARENT_ID,
                        'value'   => $post->ID,
                        'compare' => '=',
                    ),
                ),
            ));
            $event['child_events_count'] = count($child_events);
            $event['child_event_ids'] = $child_events;
        } else {
            $event['child_events_count'] = 0;
            $event['child_event_ids'] = array();
        }
        
        // Get categories
        $categories = wp_get_post_terms($post->ID, 'ensemble_category');
        $event['categories'] = array();
        if (!is_wp_error($categories)) {
            foreach ($categories as $cat) {
                $event['categories'][] = array(
                    'id'   => $cat->term_id,
                    'name' => $cat->name,
                );
            }
        }
        
        // Get genres
        $genres = wp_get_post_terms($post->ID, 'ensemble_genre');
        $event['genres'] = array();
        $event['show_artist_genres'] = false;
        if (!is_wp_error($genres)) {
            foreach ($genres as $genre) {
                $event['genres'][] = array(
                    'id'   => $genre->term_id,
                    'name' => $genre->name,
                );
            }
        }
        // Check if artist genres should be shown
        $show_artist_genres = get_post_meta($post->ID, '_es_show_artist_genres', true);
        $event['show_artist_genres'] = ($show_artist_genres == '1');
        
        // Get tickets data (from Tickets addon)
        $tickets = get_post_meta($post->ID, '_ensemble_tickets', true);
        $event['tickets'] = is_array($tickets) ? $tickets : array();
        
        // Get location name
        if ($event['location_id']) {
            $location = get_post($event['location_id']);
            $event['location_name'] = $location ? $location->post_title : '';
        } else {
            $event['location_name'] = '';
        }
        
        // Get artist names
        $event['artist_names'] = array();
        if ($event['artist_ids']) {
            if (!is_array($event['artist_ids'])) {
                $event['artist_ids'] = array($event['artist_ids']);
            }
            foreach ($event['artist_ids'] as $artist_id) {
                $artist = get_post($artist_id);
                if ($artist) {
                    $event['artist_names'][] = $artist->post_title;
                }
            }
        }
        
        // Get venue (for multivenue locations)
        $event['venue'] = get_post_meta($post->ID, 'event_venue', true);
        
        // Get venue configuration (custom names, genres per venue)
        $venue_config = get_post_meta($post->ID, 'venue_config', true);
        $event['venue_config'] = is_array($venue_config) ? $venue_config : array();
        
        // Get price note (fine print) - use constant for consistency
        $event['price_note'] = get_post_meta($post->ID, ES_Meta_Keys::EVENT_PRICE_NOTE, true) ?: '';
        
        // Get badge settings - use constants for consistency
        $event['badge'] = get_post_meta($post->ID, ES_Meta_Keys::EVENT_BADGE, true) ?: '';
        $event['badge_custom'] = get_post_meta($post->ID, ES_Meta_Keys::EVENT_BADGE_CUSTOM, true) ?: '';
        
        // Get Facebook URL
        $event['facebook_url'] = get_post_meta($post->ID, 'event_facebook_url', true) ?: '';
        
        // Get additional info (if not already loaded from ACF) - use constant
        if (empty($event['additional_info'])) {
            $event['additional_info'] = get_post_meta($post->ID, ES_Meta_Keys::EVENT_ADDITIONAL_INFO, true) ?: '';
        }
        
        // Get external link (if not already loaded from ACF) - use constants
        if (empty($event['external_url'])) {
            $event['external_url'] = get_post_meta($post->ID, ES_Meta_Keys::EVENT_EXTERNAL_URL, true) ?: '';
        }
        if (empty($event['external_text'])) {
            $event['external_text'] = get_post_meta($post->ID, ES_Meta_Keys::EVENT_EXTERNAL_TEXT, true) ?: '';
        }
        
        // Get artist times
        $event['artist_times'] = get_post_meta($post->ID, 'artist_times', true);
        if (!is_array($event['artist_times'])) {
            $event['artist_times'] = array();
        }
        
        // Get artist venues
        $event['artist_venues'] = get_post_meta($post->ID, 'artist_venues', true);
        if (!is_array($event['artist_venues'])) {
            $event['artist_venues'] = array();
        }
        
        // Get artist session titles
        $event['artist_session_titles'] = get_post_meta($post->ID, 'artist_session_titles', true);
        if (!is_array($event['artist_session_titles'])) {
            $event['artist_session_titles'] = array();
        }
        
        // Get gallery images
        $gallery_ids = get_post_meta($post->ID, '_event_gallery', true);
        $event['gallery_ids'] = is_array($gallery_ids) ? $gallery_ids : array();
        
        // Get gallery with URLs for preview
        $event['gallery'] = array();
        if (!empty($event['gallery_ids'])) {
            foreach ($event['gallery_ids'] as $attachment_id) {
                $thumb_url = wp_get_attachment_image_url($attachment_id, 'thumbnail');
                if ($thumb_url) {
                    $event['gallery'][] = array(
                        'id'  => $attachment_id,
                        'url' => $thumb_url,
                    );
                }
            }
        }
        
        // Get hero video settings
        $event['hero_video_url'] = get_post_meta($post->ID, '_hero_video_url', true);
        $event['hero_video_autoplay'] = get_post_meta($post->ID, '_hero_video_autoplay', true) == '1';
        $event['hero_video_loop'] = get_post_meta($post->ID, '_hero_video_loop', true) == '1';
        $event['hero_video_controls'] = get_post_meta($post->ID, '_hero_video_controls', true) == '1';
        
        // Get custom ACF fields from wizard configuration
        $event['acf_fields'] = $this->get_custom_acf_fields($post->ID, $event['categories']);
        
        // Get reservation settings
        $event['reservation_enabled'] = get_post_meta($post->ID, '_reservation_enabled', true) == '1';
        $event['reservation_types'] = get_post_meta($post->ID, '_reservation_types', true) ?: array('guestlist');
        $event['reservation_capacity'] = get_post_meta($post->ID, '_reservation_capacity', true);
        $event['reservation_deadline_hours'] = get_post_meta($post->ID, '_reservation_deadline_hours', true) ?: 24;
        $event['reservation_auto_confirm'] = get_post_meta($post->ID, '_reservation_auto_confirm', true) == '1';
        
        // Get event contacts (from Staff addon)
        $contacts = get_post_meta($post->ID, '_es_event_contacts', true);
        $event['contacts'] = is_array($contacts) ? $contacts : array();
        
        /**
         * Filter event data before returning
         * 
         * Allows addons to add their own data to the event object.
         * 
         * @since 2.9.0
         * 
         * @param array   $event Event data array
         * @param WP_Post $post  Post object
         */
        $event = apply_filters('ensemble_wizard_event_data', $event, $post);
        
        return $event;
    }
    
    /**
     * Get custom ACF field values for this event
     * 
     * @param int $post_id
     * @param array $categories
     * @return array
     */
    private function get_custom_acf_fields($post_id, $categories) {
        $acf_fields = array();
        
        if (empty($categories) || !function_exists('get_field')) {
            return $acf_fields;
        }
        
        // Get wizard configuration
        $wizard_config = get_option('ensemble_wizard_config', array());
        
        // Get field groups for the first category
        $category_id = isset($categories[0]['id']) ? $categories[0]['id'] : 0;
        
        if (!isset($wizard_config[$category_id]) || empty($wizard_config[$category_id]['field_groups'])) {
            return $acf_fields;
        }
        
        $field_groups = $wizard_config[$category_id]['field_groups'];
        
        // Load all fields from assigned field groups
        foreach ($field_groups as $group_key) {
            if (!function_exists('acf_get_fields')) {
                continue;
            }
            
            $fields = acf_get_fields($group_key);
            if (!$fields) {
                continue;
            }
            
            foreach ($fields as $field) {
                // Skip structural fields
                $structural_types = array('tab', 'message', 'accordion');
                if (in_array($field['type'], $structural_types)) {
                    continue;
                }
                
                $value = get_field($field['key'], $post_id);
                $acf_fields[$field['key']] = $value;
            }
        }
        
        return $acf_fields;
    }
    
    /**
     * Get all locations for dropdown
     * @return array
     */
    public function get_locations() {
        $locations = get_posts(array(
            'post_type'      => 'ensemble_location',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ));
        
        $result = array();
        foreach ($locations as $location) {
            // Get Multivenue data - IMMER mit post_meta (keine ACF-Felder)
            $is_multivenue = (bool)get_post_meta($location->ID, 'is_multivenue', true);
            $venues = get_post_meta($location->ID, 'venues', true);
            
            $result[] = array(
                'id'            => $location->ID,
                'name'          => $location->post_title,
                'is_multivenue' => $is_multivenue,
                'venues'        => is_array($venues) ? $venues : array(),
            );
        }
        
        return $result;
    }
    
    /**
     * Get all artists for dropdown
    /**
     * Get all artists for dropdown
     * @return array
     */
    public function get_artists() {
        $artists = get_posts(array(
            'post_type'      => 'ensemble_artist',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ));
        
        $result = array();
        foreach ($artists as $artist) {
            // Get artist genres
            $genre_ids = array();
            $genres = get_the_terms($artist->ID, 'ensemble_genre');
            if ($genres && !is_wp_error($genres)) {
                foreach ($genres as $genre) {
                    $genre_ids[] = $genre->term_id;
                }
            }
            
            $result[] = array(
                'id'        => $artist->ID,
                'name'      => $artist->post_title,
                'image'     => get_the_post_thumbnail_url($artist->ID, 'thumbnail') ?: '',
                'genre_ids' => $genre_ids,
            );
        }
        
        return $result;
    }
    
    /**
     * Get all categories for dropdown
     * @return array
     */
    public function get_categories() {
        $terms = get_terms(array(
            'taxonomy'   => 'ensemble_category',
            'hide_empty' => false,
        ));
        
        $result = array();
        foreach ($terms as $term) {
            $result[] = array(
                'id'   => $term->term_id,
                'name' => $term->name,
            );
        }
        
        return $result;
    }
    
    /**
     * Get all genres for dropdown
     * @return array
     */
    public function get_genres() {
        $terms = get_terms(array(
            'taxonomy'   => 'ensemble_genre',
            'hide_empty' => false,
        ));
        
        $result = array();
        if (!is_wp_error($terms)) {
            foreach ($terms as $term) {
                $result[] = array(
                    'id'   => $term->term_id,
                    'name' => $term->name,
                );
            }
        }
        
        return $result;
    }
    
    /**
     * Get all location types for dropdown
     * @return array
     */
    public function get_location_types() {
        $terms = get_terms(array(
            'taxonomy'   => 'ensemble_location_type',
            'hide_empty' => false,
        ));
        
        $result = array();
        if (!is_wp_error($terms)) {
            foreach ($terms as $term) {
                $result[] = array(
                    'id'   => $term->term_id,
                    'name' => $term->name,
                );
            }
        }
        
        return $result;
    }
    
    /**
     * Get all artist types for dropdown
     * @return array
     */
    public function get_artist_types() {
        $terms = get_terms(array(
            'taxonomy'   => 'ensemble_artist_type',
            'hide_empty' => false,
        ));
        
        $result = array();
        if (!is_wp_error($terms)) {
            foreach ($terms as $term) {
                $result[] = array(
                    'id'   => $term->term_id,
                    'name' => $term->name,
                );
            }
        }
        
        return $result;
    }
}
