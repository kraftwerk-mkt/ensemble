<?php
/**
 * Location Manager
 * 
 * Handles location management functionality
 *
 * @package Ensemble
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class ES_Location_Manager {
    
    /**
     * Get all locations
     * @param array $args
     * @return array
     */
    public function get_locations($args = array()) {
        $defaults = array(
            'post_type'      => 'ensemble_location',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'post_status'    => 'publish',
        );
        
        $args = wp_parse_args($args, $defaults);
        $posts = get_posts($args);
        
        $locations = array();
        foreach ($posts as $post) {
            $locations[] = $this->format_location($post);
        }
        
        return $locations;
    }
    
    /**
     * Get single location
     * @param int $location_id
     * @return array|false
     */
    public function get_location($location_id) {
        $post = get_post($location_id);
        if (!$post || $post->post_type !== 'ensemble_location') {
            return false;
        }
        
        return $this->format_location($post);
    }
    
    /**
     * Format location data
     * @param WP_Post $post
     * @return array
     */
    private function format_location($post) {
        // Get meta fields
        if (function_exists('get_field')) {
            $address = get_field('location_address', $post->ID);
            $city = get_field('location_city', $post->ID);
            $capacity = get_field('location_capacity', $post->ID);
            $website = get_field('location_website', $post->ID);
            $social_links = get_field('location_social_links', $post->ID);
            $youtube = get_field('location_youtube', $post->ID);
            $vimeo = get_field('location_vimeo', $post->ID);
            $gallery_ids = get_field('location_gallery', $post->ID);
            
            // Maps Add-on fields
            $zip_code = get_field('zip_code', $post->ID);
            $latitude = get_field('latitude', $post->ID);
            $longitude = get_field('longitude', $post->ID);
            $show_map = get_field('show_map', $post->ID);
            $map_type = get_field('map_type', $post->ID);
        } else {
            $address = get_post_meta($post->ID, 'location_address', true);
            $city = get_post_meta($post->ID, 'location_city', true);
            $capacity = get_post_meta($post->ID, 'location_capacity', true);
            $website = get_post_meta($post->ID, 'location_website', true);
            $social_links = get_post_meta($post->ID, 'location_social_links', true);
            $youtube = get_post_meta($post->ID, 'location_youtube', true);
            $vimeo = get_post_meta($post->ID, 'location_vimeo', true);
            $gallery_ids = get_post_meta($post->ID, 'location_gallery', true);
            
            // Maps Add-on fields
            $zip_code = get_post_meta($post->ID, 'zip_code', true);
            $latitude = get_post_meta($post->ID, 'latitude', true);
            $longitude = get_post_meta($post->ID, 'longitude', true);
            $show_map = get_post_meta($post->ID, 'show_map', true);
            $map_type = get_post_meta($post->ID, 'map_type', true);
        }
        
        // Additional info - always use post_meta (not ACF field)
        $additional_info = get_post_meta($post->ID, 'location_additional_info', true);
        
        // Multivenue fields - IMMER mit post_meta lesen (keine ACF-Felder)
        $is_multivenue = get_post_meta($post->ID, 'is_multivenue', true);
        $venues = get_post_meta($post->ID, 'venues', true);
        
        // Opening Hours fields
        $has_opening_hours = get_post_meta($post->ID, 'has_opening_hours', true);
        $opening_hours = get_post_meta($post->ID, 'opening_hours', true);
        $opening_hours_note = get_post_meta($post->ID, 'opening_hours_note', true);
        
        // Format social links
        if (!is_array($social_links)) {
            $social_links = array();
        }
        
        // Format gallery
        $gallery = array();
        if (is_array($gallery_ids) && !empty($gallery_ids)) {
            foreach ($gallery_ids as $img_id) {
                $img_url = wp_get_attachment_image_url($img_id, 'medium');
                if ($img_url) {
                    $gallery[] = array(
                        'id' => $img_id,
                        'url' => $img_url
                    );
                }
            }
        }
        
        // Get location types from taxonomy
        $location_types = wp_get_post_terms($post->ID, 'ensemble_location_type');
        $type_data = array();
        $type_names = array();
        if (!is_wp_error($location_types) && !empty($location_types)) {
            foreach ($location_types as $type) {
                $type_data[] = array(
                    'id' => $type->term_id,
                    'name' => $type->name,
                );
                $type_names[] = $type->name;
            }
        }
        
        // Count events
        $event_count = $this->get_location_event_count($post->ID);
        
        // Generate Google Maps link
        $maps_link = '';
        if ($address && $city) {
            $maps_query = urlencode($address . ', ' . $city);
            $maps_link = 'https://www.google.com/maps/search/?api=1&query=' . $maps_query;
        }
        
        // Get featured image ID
        $featured_image_id = get_post_thumbnail_id($post->ID);
        
        // Get downloads if addon is active
        $downloads = array();
        if (class_exists('ES_Addon_Manager') && ES_Addon_Manager::is_addon_active('downloads')) {
            $downloads_addon = ES_Addon_Manager::get_active_addon('downloads');
            if ($downloads_addon) {
                $download_manager = $downloads_addon->get_download_manager();
                $downloads = $download_manager->get_downloads(array('location_id' => $post->ID));
            }
        }
        
        return array(
            'id'                => $post->ID,
            'name'              => $post->post_title,
            'description'       => $post->post_content,
            'additional_info'   => $additional_info,
            'location_types'    => $type_data,
            'location_type'     => implode(', ', $type_names), // For display
            'address'           => $address,
            'city'              => $city,
            'capacity'          => $capacity,
            'website'           => $website,
            'social_links'      => $social_links,
            'youtube'           => $youtube,
            'vimeo'             => $vimeo,
            'featured_image'    => get_the_post_thumbnail_url($post->ID, 'medium'),
            'featured_image_id' => $featured_image_id,
            'gallery'           => $gallery,
            'event_count'       => $event_count,
            'downloads'         => $downloads,
            'maps_link'         => $maps_link,
            'created'           => $post->post_date,
            'modified'          => $post->post_modified,
            // Maps Add-on fields
            'zip_code'          => $zip_code,
            'latitude'          => $latitude,
            'longitude'         => $longitude,
            'show_map'          => ($show_map === '' || $show_map === null) ? true : (bool)intval($show_map),
            'map_type'          => $map_type ?: 'embedded',
            // Multivenue fields
            'is_multivenue'     => (bool)$is_multivenue,
            'venues'            => is_array($venues) ? $venues : array(),
            // Opening Hours fields
            'has_opening_hours'   => (bool)$has_opening_hours,
            'opening_hours'       => is_array($opening_hours) ? $opening_hours : array(),
            'opening_hours_note'  => $opening_hours_note ?: '',
        );
    }
    
    /**
     * Get location event count
     * @param int $location_id
     * @return int
     */
    private function get_location_event_count($location_id) {
        global $wpdb;
        
        $count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = 'event_location' 
            AND meta_value = %d
        ", $location_id));
        
        return intval($count);
    }
    
    /**
     * Search locations
     * @param string $search
     * @return array
     */
    public function search_locations($search) {
        $args = array(
            's' => $search,
        );
        
        return $this->get_locations($args);
    }
    
    /**
     * Save location
     * @param array $data
     * @return int|WP_Error
     */
    public function save_location($data) {
        $location_id = isset($data['location_id']) ? intval($data['location_id']) : 0;
        $name = isset($data['name']) ? sanitize_text_field($data['name']) : '';
        
        if (empty($name)) {
            return new WP_Error('missing_name', 'Location name is required');
        }
        
        // Prepare post data
        $post_data = array(
            'post_title'   => $name,
            'post_content' => isset($data['description']) ? wp_kses_post($data['description']) : '',
            'post_type'    => 'ensemble_location',
            'post_status'  => 'publish',
        );
        
        // Update or create
        if ($location_id) {
            $post_data['ID'] = $location_id;
            $result = wp_update_post($post_data);
        } else {
            $result = wp_insert_post($post_data);
        }
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        $location_id = $result;
        
        // Save location types (taxonomy)
        if (isset($data['location_type']) && is_array($data['location_type'])) {
            $type_ids = array_map('intval', $data['location_type']);
            wp_set_post_terms($location_id, $type_ids, 'ensemble_location_type');
        }
        
        // Save ACF fields if available
        if (function_exists('update_field')) {
            if (isset($data['address'])) {
                update_field('location_address', sanitize_textarea_field($data['address']), $location_id);
            }
            if (isset($data['city'])) {
                update_field('location_city', sanitize_text_field($data['city']), $location_id);
            }
            if (isset($data['capacity'])) {
                update_field('location_capacity', intval($data['capacity']), $location_id);
            }
            if (isset($data['website'])) {
                update_field('location_website', esc_url_raw($data['website']), $location_id);
            }
            if (isset($data['social_links']) && is_array($data['social_links'])) {
                $sanitized_links = array_map('esc_url_raw', $data['social_links']);
                update_field('location_social_links', $sanitized_links, $location_id);
            }
            if (isset($data['youtube'])) {
                update_field('location_youtube', esc_url_raw($data['youtube']), $location_id);
            }
            if (isset($data['vimeo'])) {
                update_field('location_vimeo', esc_url_raw($data['vimeo']), $location_id);
            }
            if (isset($data['gallery_ids']) && is_array($data['gallery_ids'])) {
                $gallery_ids = array_map('intval', $data['gallery_ids']);
                update_field('location_gallery', $gallery_ids, $location_id);
            }
            
            // Maps Add-on fields
            if (isset($data['zip_code'])) {
                update_field('zip_code', sanitize_text_field($data['zip_code']), $location_id);
            }
            if (isset($data['additional_info'])) {
                update_post_meta($location_id, 'location_additional_info', sanitize_textarea_field($data['additional_info']));
            }
            if (isset($data['latitude'])) {
                update_field('latitude', floatval($data['latitude']), $location_id);
            }
            if (isset($data['longitude'])) {
                update_field('longitude', floatval($data['longitude']), $location_id);
            }
            if (isset($data['show_map'])) {
                // intval() to properly handle "0" string
                update_field('show_map', intval($data['show_map']), $location_id);
            }
            if (isset($data['map_type'])) {
                update_field('map_type', sanitize_text_field($data['map_type']), $location_id);
            }
        } else {
            // Fallback to post meta
            if (isset($data['address'])) {
                update_post_meta($location_id, 'location_address', sanitize_textarea_field($data['address']));
            }
            if (isset($data['city'])) {
                update_post_meta($location_id, 'location_city', sanitize_text_field($data['city']));
            }
            if (isset($data['capacity'])) {
                update_post_meta($location_id, 'location_capacity', intval($data['capacity']));
            }
            if (isset($data['website'])) {
                update_post_meta($location_id, 'location_website', esc_url_raw($data['website']));
            }
            if (isset($data['social_links']) && is_array($data['social_links'])) {
                $sanitized_links = array_map('esc_url_raw', $data['social_links']);
                update_post_meta($location_id, 'location_social_links', $sanitized_links);
            }
            if (isset($data['youtube'])) {
                update_post_meta($location_id, 'location_youtube', esc_url_raw($data['youtube']));
            }
            if (isset($data['vimeo'])) {
                update_post_meta($location_id, 'location_vimeo', esc_url_raw($data['vimeo']));
            }
            if (isset($data['gallery_ids']) && is_array($data['gallery_ids'])) {
                $gallery_ids = array_map('intval', $data['gallery_ids']);
                update_post_meta($location_id, 'location_gallery', $gallery_ids);
            }
            
            // Maps Add-on fields
            if (isset($data['zip_code'])) {
                update_post_meta($location_id, 'zip_code', sanitize_text_field($data['zip_code']));
            }
            if (isset($data['additional_info'])) {
                update_post_meta($location_id, 'location_additional_info', sanitize_textarea_field($data['additional_info']));
            }
            if (isset($data['latitude'])) {
                update_post_meta($location_id, 'latitude', floatval($data['latitude']));
            }
            if (isset($data['longitude'])) {
                update_post_meta($location_id, 'longitude', floatval($data['longitude']));
            }
            if (isset($data['show_map'])) {
                // Handle string "false" from JS properly
                $show_map_value = $data['show_map'];
                if ($show_map_value === 'false' || $show_map_value === '0' || $show_map_value === 0 || $show_map_value === false) {
                    $show_map_value = 0;
                } else {
                    $show_map_value = 1;
                }
                update_post_meta($location_id, 'show_map', $show_map_value);
            }
            if (isset($data['map_type'])) {
                update_post_meta($location_id, 'map_type', sanitize_text_field($data['map_type']));
            }
        }
        
        // Multivenue fields - IMMER mit post_meta speichern (keine ACF-Felder)
        if (isset($data['is_multivenue'])) {
            update_post_meta($location_id, 'is_multivenue', (bool)$data['is_multivenue'] ? 1 : 0);
        }
        if (isset($data['venues']) && is_array($data['venues'])) {
            $sanitized_venues = array();
            foreach ($data['venues'] as $venue) {
                if (!empty($venue['name'])) {
                    $sanitized_venues[] = array(
                        'name' => sanitize_text_field($venue['name']),
                        'capacity' => isset($venue['capacity']) ? intval($venue['capacity']) : 0,
                    );
                }
            }
            update_post_meta($location_id, 'venues', $sanitized_venues);
        }
        
        // Opening Hours fields - IMMER mit post_meta speichern
        if (isset($data['has_opening_hours'])) {
            update_post_meta($location_id, 'has_opening_hours', (bool)$data['has_opening_hours'] ? 1 : 0);
        }
        if (isset($data['opening_hours']) && is_array($data['opening_hours'])) {
            $sanitized_hours = array();
            $days = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday');
            foreach ($days as $day) {
                if (isset($data['opening_hours'][$day])) {
                    $sanitized_hours[$day] = array(
                        'open'   => sanitize_text_field($data['opening_hours'][$day]['open'] ?? ''),
                        'close'  => sanitize_text_field($data['opening_hours'][$day]['close'] ?? ''),
                        'closed' => !empty($data['opening_hours'][$day]['closed']),
                    );
                }
            }
            update_post_meta($location_id, 'opening_hours', $sanitized_hours);
        }
        if (isset($data['opening_hours_note'])) {
            update_post_meta($location_id, 'opening_hours_note', sanitize_text_field($data['opening_hours_note']));
        }
        
        // Set featured image
        if (isset($data['featured_image_id']) && !empty($data['featured_image_id'])) {
            set_post_thumbnail($location_id, intval($data['featured_image_id']));
        }
        
        // Update download links if Downloads addon is active
        if (isset($data['downloads_data']) && class_exists('ES_Addon_Manager') && ES_Addon_Manager::is_addon_active('downloads')) {
            $download_ids = json_decode(stripslashes($data['downloads_data']), true);
            if (is_array($download_ids)) {
                $downloads_addon = ES_Addon_Manager::get_active_addon('downloads');
                if ($downloads_addon) {
                    $download_manager = $downloads_addon->get_download_manager();
                    $download_manager->update_location_downloads($location_id, $download_ids);
                }
            }
        }
        
        return $location_id;
    }
    
    /**
     * Delete location
     * @param int $location_id
     * @return bool
     */
    public function delete_location($location_id) {
        $result = wp_delete_post($location_id, true);
        return !empty($result);
    }
    
    /**
     * Bulk delete locations
     * @param array $location_ids
     * @return array
     */
    public function bulk_delete($location_ids) {
        $deleted = 0;
        $failed = 0;
        
        foreach ($location_ids as $id) {
            if ($this->delete_location($id)) {
                $deleted++;
            } else {
                $failed++;
            }
        }
        
        return array(
            'deleted' => $deleted,
            'failed'  => $failed,
        );
    }
}
