<?php
/**
 * Download Manager
 * 
 * Handles download CRUD operations
 *
 * @package Ensemble
 * @subpackage Addons/Downloads
 */

if (!defined('ABSPATH')) {
    exit;
}

class ES_Download_Manager {
    
    /**
     * Get all downloads
     * 
     * @param array $args Query arguments
     * @return array
     */
    public function get_downloads($args = array()) {
        $defaults = array(
            'post_type'      => 'ensemble_download',
            'posts_per_page' => -1,
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
            'post_status'    => 'publish',
        );
        
        $query_args = wp_parse_args($args, $defaults);
        
        // Handle limit
        if (isset($args['limit']) && $args['limit'] > 0) {
            $query_args['posts_per_page'] = intval($args['limit']);
            unset($query_args['limit']);
        }
        
        // Meta query for relationships
        // We need to handle both legacy integer storage and new string storage
        // Integer serialization: i:123;
        // String serialization: s:3:"123";
        $meta_query = array('relation' => 'AND');
        
        // Filter by artist
        if (!empty($args['artist_id'])) {
            $artist_id = absint($args['artist_id']);
            $meta_query[] = array(
                'key'     => '_download_artists',
                'compare' => 'EXISTS',
            );
            // We'll filter results after query for accuracy
            $query_args['_filter_artist_id'] = $artist_id;
            unset($query_args['artist_id']);
        }
        
        // Filter by event
        if (!empty($args['event_id'])) {
            $event_id = absint($args['event_id']);
            $meta_query[] = array(
                'key'     => '_download_events',
                'compare' => 'EXISTS',
            );
            $query_args['_filter_event_id'] = $event_id;
            unset($query_args['event_id']);
        }
        
        // Filter by location
        if (!empty($args['location_id'])) {
            $location_id = absint($args['location_id']);
            $meta_query[] = array(
                'key'     => '_download_locations',
                'compare' => 'EXISTS',
            );
            $query_args['_filter_location_id'] = $location_id;
            unset($query_args['location_id']);
        }
        
        // Filter by type (taxonomy)
        if (!empty($args['type'])) {
            $query_args['tax_query'] = array(
                array(
                    'taxonomy' => 'ensemble_download_type',
                    'field'    => 'slug',
                    'terms'    => $args['type'],
                ),
            );
            unset($query_args['type']);
        }
        
        // Search
        if (!empty($args['search'])) {
            $query_args['s'] = $args['search'];
            unset($query_args['search']);
        }
        
        // Apply meta query if we have conditions
        if (count($meta_query) > 1) {
            $query_args['meta_query'] = $meta_query;
        }
        
        // Extract filter IDs before query
        $filter_artist_id = isset($query_args['_filter_artist_id']) ? $query_args['_filter_artist_id'] : null;
        $filter_event_id = isset($query_args['_filter_event_id']) ? $query_args['_filter_event_id'] : null;
        $filter_location_id = isset($query_args['_filter_location_id']) ? $query_args['_filter_location_id'] : null;
        unset($query_args['_filter_artist_id'], $query_args['_filter_event_id'], $query_args['_filter_location_id']);
        
        $posts = get_posts($query_args);
        
        $downloads = array();
        foreach ($posts as $post) {
            // Apply relationship filters (handles both int and string storage)
            if ($filter_artist_id !== null) {
                $artist_ids = get_post_meta($post->ID, '_download_artists', true);
                if (!$this->array_contains_id($artist_ids, $filter_artist_id)) {
                    continue;
                }
            }
            
            if ($filter_event_id !== null) {
                $event_ids = get_post_meta($post->ID, '_download_events', true);
                if (!$this->array_contains_id($event_ids, $filter_event_id)) {
                    continue;
                }
            }
            
            if ($filter_location_id !== null) {
                $location_ids = get_post_meta($post->ID, '_download_locations', true);
                if (!$this->array_contains_id($location_ids, $filter_location_id)) {
                    continue;
                }
            }
            
            $download = $this->format_download($post);
            
            // Check availability (time-based)
            if ($this->is_download_available($post->ID)) {
                $downloads[] = $download;
            }
        }
        
        return $downloads;
    }
    
    /**
     * Check if array contains ID (handles both int and string storage)
     * 
     * @param mixed $array
     * @param int $id
     * @return bool
     */
    private function array_contains_id($array, $id) {
        if (!is_array($array) || empty($array)) {
            return false;
        }
        
        $id = absint($id);
        foreach ($array as $item) {
            if (absint($item) === $id) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Get downloads for artist/speaker
     * 
     * @param int $artist_id
     * @return array
     */
    public function get_downloads_for_artist($artist_id) {
        return $this->get_downloads(array('artist_id' => $artist_id));
    }
    
    /**
     * Get downloads for event/session
     * 
     * @param int $event_id
     * @return array
     */
    public function get_downloads_for_event($event_id) {
        return $this->get_downloads(array('event_id' => $event_id));
    }
    
    /**
     * Get downloads for location
     * 
     * @param int $location_id
     * @return array
     */
    public function get_downloads_for_location($location_id) {
        return $this->get_downloads(array('location_id' => $location_id));
    }
    
    /**
     * Get single download
     * 
     * @param int $download_id
     * @return array|false
     */
    public function get_download($download_id) {
        $post = get_post($download_id);
        if (!$post || $post->post_type !== 'ensemble_download') {
            return false;
        }
        
        return $this->format_download($post);
    }
    
    /**
     * Check if download is available (time-based)
     * 
     * @param int $download_id
     * @return bool
     */
    public function is_download_available($download_id) {
        $now = current_time('Y-m-d H:i:s');
        
        $available_from = get_post_meta($download_id, '_download_available_from', true);
        $available_until = get_post_meta($download_id, '_download_available_until', true);
        
        // Check from date
        if (!empty($available_from) && $now < $available_from) {
            return false;
        }
        
        // Check until date
        if (!empty($available_until) && $now > $available_until) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Format download data
     * 
     * @param WP_Post $post
     * @return array
     */
    private function format_download($post) {
        // Get file info
        $file_id = get_post_meta($post->ID, '_download_file_id', true);
        $file_url = '';
        $file_name = '';
        $file_size = 0;
        $file_extension = '';
        $file_mime_type = '';
        
        if ($file_id) {
            $file_url = wp_get_attachment_url($file_id);
            $file_path = get_attached_file($file_id);
            
            if ($file_path && file_exists($file_path)) {
                $file_name = basename($file_path);
                $file_size = filesize($file_path);
                $file_extension = pathinfo($file_path, PATHINFO_EXTENSION);
                $file_mime_type = mime_content_type($file_path);
            }
        }
        
        // Ensure taxonomy is registered before querying
        if (!taxonomy_exists('ensemble_download_type')) {
            if (class_exists('ES_Addon_Manager') && ES_Addon_Manager::is_addon_active('downloads')) {
                $addon = ES_Addon_Manager::get_active_addon('downloads');
                if ($addon && method_exists($addon, 'ensure_taxonomy_registered')) {
                    $addon->ensure_taxonomy_registered();
                }
            }
        }
        
        // Get download type
        $types = wp_get_post_terms($post->ID, 'ensemble_download_type');
        $type_data = null;
        $type_slug = 'other';
        
        // Debug log
        if (defined('WP_DEBUG') && WP_DEBUG) {
            if (is_wp_error($types)) {
                error_log('Ensemble Downloads: Error getting terms for post #' . $post->ID . ': ' . $types->get_error_message());
            } else {
                error_log('Ensemble Downloads: Got ' . count($types) . ' terms for post #' . $post->ID . ': ' . print_r(wp_list_pluck($types, 'slug'), true));
            }
        }
        
        if (!is_wp_error($types) && !empty($types)) {
            $type = $types[0];
            $type_slug = $type->slug;
            
            // Get additional type info (color, icon) from addon
            $type_info = array();
            if (class_exists('ES_Addon_Manager') && ES_Addon_Manager::is_addon_active('downloads')) {
                $addon = ES_Addon_Manager::get_active_addon('downloads');
                if ($addon) {
                    $type_info = $addon->get_type_info($type_slug);
                }
            }
            
            $type_data = array(
                'id'    => $type->term_id,
                'name'  => $type->name,
                'label' => $type->name, // Alias for consistency
                'slug'  => $type->slug,
                'color' => isset($type_info['color']) ? $type_info['color'] : '#95a5a6',
                'icon'  => isset($type_info['icon']) ? $type_info['icon'] : 'dashicons-download',
            );
        }
        
        // Get linked artists
        $artist_ids = get_post_meta($post->ID, '_download_artists', true);
        if (!is_array($artist_ids)) {
            $artist_ids = array();
        }
        
        $artists = array();
        $artist_post_type = function_exists('ensemble_get_artist_post_type') ? ensemble_get_artist_post_type() : 'ensemble_artist';
        
        foreach ($artist_ids as $artist_id) {
            $artist_post = get_post($artist_id);
            if ($artist_post && $artist_post->post_type === $artist_post_type) {
                $artists[] = array(
                    'id'    => $artist_post->ID,
                    'title' => $artist_post->post_title,
                    'url'   => get_permalink($artist_post->ID),
                );
            }
        }
        
        // Get linked events
        $event_ids = get_post_meta($post->ID, '_download_events', true);
        if (!is_array($event_ids)) {
            $event_ids = array();
        }
        
        $events = array();
        $event_post_type = function_exists('ensemble_get_post_type') ? ensemble_get_post_type() : 'post';
        
        foreach ($event_ids as $event_id) {
            $event_post = get_post($event_id);
            if ($event_post && $event_post->post_type === $event_post_type) {
                $events[] = array(
                    'id'    => $event_post->ID,
                    'title' => $event_post->post_title,
                    'url'   => get_permalink($event_post->ID),
                );
            }
        }
        
        // Get linked locations
        $location_ids = get_post_meta($post->ID, '_download_locations', true);
        if (!is_array($location_ids)) {
            $location_ids = array();
        }
        
        $locations = array();
        $location_post_type = function_exists('ensemble_get_location_post_type') ? ensemble_get_location_post_type() : 'ensemble_location';
        
        foreach ($location_ids as $location_id) {
            $location_post = get_post($location_id);
            if ($location_post && $location_post->post_type === $location_post_type) {
                $locations[] = array(
                    'id'    => $location_post->ID,
                    'title' => $location_post->post_title,
                    'url'   => get_permalink($location_post->ID),
                );
            }
        }
        
        return array(
            'id'              => $post->ID,
            'title'           => $post->post_title,
            'description'     => get_post_meta($post->ID, '_download_description', true),
            'file_id'         => $file_id,
            'file_url'        => $file_url,
            'file_name'       => $file_name,
            'file_size'       => $file_size,
            'file_extension'  => $file_extension,
            'file_mime_type'  => $file_mime_type,
            'type'            => $type_data,
            'type_slug'       => $type_slug,
            'artists'         => $artists,
            'artist_ids'      => $artist_ids,
            'events'          => $events,
            'event_ids'       => $event_ids,
            'locations'       => $locations,
            'location_ids'    => $location_ids,
            'download_count'  => intval(get_post_meta($post->ID, '_download_count', true)),
            'available_from'  => get_post_meta($post->ID, '_download_available_from', true),
            'available_until' => get_post_meta($post->ID, '_download_available_until', true),
            'require_login'   => get_post_meta($post->ID, '_download_require_login', true) === '1',
            'menu_order'      => $post->menu_order,
            'created'         => $post->post_date,
            'modified'        => $post->post_modified,
            'download_url'    => add_query_arg('es_download', $post->ID, home_url('/')),
        );
    }
    
    /**
     * Save download
     * 
     * @param array $data
     * @return int|WP_Error
     */
    public function save_download($data) {
        $download_id = isset($data['download_id']) ? absint($data['download_id']) : 0;
        $title = isset($data['title']) ? sanitize_text_field($data['title']) : '';
        
        if (empty($title)) {
            return new WP_Error('missing_title', __('Titel ist erforderlich', 'ensemble'));
        }
        
        // Check for file
        if (empty($data['file_id'])) {
            return new WP_Error('missing_file', __('Bitte wählen Sie eine Datei aus', 'ensemble'));
        }
        
        // Prepare post data
        $post_data = array(
            'post_title'  => $title,
            'post_type'   => 'ensemble_download',
            'post_status' => 'publish',
            'menu_order'  => isset($data['menu_order']) ? absint($data['menu_order']) : 0,
        );
        
        // Update or create
        if ($download_id) {
            $post_data['ID'] = $download_id;
            $result = wp_update_post($post_data);
        } else {
            $result = wp_insert_post($post_data);
        }
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        $download_id = $result;
        
        // Save meta fields
        if (isset($data['description'])) {
            update_post_meta($download_id, '_download_description', sanitize_textarea_field($data['description']));
        }
        
        if (isset($data['file_id'])) {
            update_post_meta($download_id, '_download_file_id', absint($data['file_id']));
        }
        
        // Save type (taxonomy)
        if (isset($data['type'])) {
            $type_slug = sanitize_text_field($data['type']);
            
            // Debug log
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Ensemble Downloads: Saving type "' . $type_slug . '" for download #' . $download_id);
            }
            
            // Get or create term
            $term = get_term_by('slug', $type_slug, 'ensemble_download_type');
            if ($term) {
                $result = wp_set_post_terms($download_id, array($term->term_id), 'ensemble_download_type');
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Ensemble Downloads: Set term result: ' . print_r($result, true));
                }
            } else {
                // Create new term if it doesn't exist
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Ensemble Downloads: Term "' . $type_slug . '" not found, creating new');
                }
                $new_term = wp_insert_term($type_slug, 'ensemble_download_type', array('slug' => $type_slug));
                if (!is_wp_error($new_term)) {
                    wp_set_post_terms($download_id, array($new_term['term_id']), 'ensemble_download_type');
                } else {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('Ensemble Downloads: Failed to create term: ' . $new_term->get_error_message());
                    }
                }
            }
        }
        
        // Save relationships - store as strings for consistent LIKE search
        if (isset($data['artists'])) {
            $artist_ids = array_map('strval', array_filter(array_map('absint', (array) $data['artists'])));
            update_post_meta($download_id, '_download_artists', $artist_ids);
        }
        
        if (isset($data['events'])) {
            $event_ids = array_map('strval', array_filter(array_map('absint', (array) $data['events'])));
            update_post_meta($download_id, '_download_events', $event_ids);
        }
        
        if (isset($data['locations'])) {
            $location_ids = array_map('strval', array_filter(array_map('absint', (array) $data['locations'])));
            update_post_meta($download_id, '_download_locations', $location_ids);
        }
        
        // Save availability dates
        if (isset($data['available_from'])) {
            update_post_meta($download_id, '_download_available_from', sanitize_text_field($data['available_from']));
        }
        
        if (isset($data['available_until'])) {
            update_post_meta($download_id, '_download_available_until', sanitize_text_field($data['available_until']));
        }
        
        // Save access control
        if (isset($data['require_login'])) {
            update_post_meta($download_id, '_download_require_login', $data['require_login'] ? '1' : '0');
        }
        
        return $download_id;
    }
    
    /**
     * Delete download
     * 
     * @param int $download_id
     * @return bool
     */
    public function delete_download($download_id) {
        $result = wp_delete_post($download_id, true);
        return !empty($result);
    }
    
    /**
     * Increment download count
     * 
     * @param int $download_id
     * @return int New count
     */
    public function increment_download_count($download_id) {
        $count = intval(get_post_meta($download_id, '_download_count', true));
        $count++;
        update_post_meta($download_id, '_download_count', $count);
        return $count;
    }
    
    /**
     * Get download count
     * 
     * @return int
     */
    public function get_download_count() {
        $count = wp_count_posts('ensemble_download');
        return isset($count->publish) ? $count->publish : 0;
    }
    
    /**
     * Get downloads by type
     * 
     * @param string $type_slug
     * @return array
     */
    public function get_downloads_by_type($type_slug) {
        return $this->get_downloads(array('type' => $type_slug));
    }
    
    /**
     * Get total download count (stats)
     * 
     * @return int
     */
    public function get_total_download_count() {
        global $wpdb;
        
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(CAST(meta_value AS UNSIGNED)) 
             FROM {$wpdb->postmeta} pm
             INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
             WHERE pm.meta_key = %s
             AND p.post_type = %s
             AND p.post_status = 'publish'",
            '_download_count',
            'ensemble_download'
        ));
        
        return intval($total);
    }
    
    /**
     * Update downloads linked to an event
     * 
     * @param int $event_id
     * @param array $download_ids
     */
    public function update_event_downloads($event_id, $download_ids) {
        $this->update_content_downloads('events', $event_id, $download_ids);
    }
    
    /**
     * Update downloads linked to an artist
     * 
     * @param int $artist_id
     * @param array $download_ids
     */
    public function update_artist_downloads($artist_id, $download_ids) {
        $this->update_content_downloads('artists', $artist_id, $download_ids);
    }
    
    /**
     * Update downloads linked to a location
     * 
     * @param int $location_id
     * @param array $download_ids
     */
    public function update_location_downloads($location_id, $download_ids) {
        $this->update_content_downloads('locations', $location_id, $download_ids);
    }
    
    /**
     * Update content downloads relationship
     * 
     * @param string $content_type 'events', 'artists', or 'locations'
     * @param int $content_id
     * @param array $download_ids
     */
    private function update_content_downloads($content_type, $content_id, $download_ids) {
        $meta_key = '_download_' . $content_type;
        $content_id_str = strval(absint($content_id));
        $content_id_int = absint($content_id);
        
        // Get ALL downloads (bypass availability filter)
        $all_download_posts = get_posts(array(
            'post_type'      => 'ensemble_download',
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'fields'         => 'ids',
        ));
        
        // First, remove this content from all downloads
        foreach ($all_download_posts as $download_post_id) {
            $current_ids = get_post_meta($download_post_id, $meta_key, true);
            if (!is_array($current_ids) || empty($current_ids)) {
                continue;
            }
            
            // Check if content_id exists (handle both int and string)
            $found = false;
            $new_ids = array();
            foreach ($current_ids as $id) {
                if (absint($id) === $content_id_int) {
                    $found = true;
                } else {
                    $new_ids[] = strval($id);
                }
            }
            
            if ($found) {
                update_post_meta($download_post_id, $meta_key, array_values($new_ids));
            }
        }
        
        // Now add this content to the specified downloads
        foreach ($download_ids as $download_id) {
            $download_id = absint($download_id);
            if ($download_id <= 0) continue;
            
            $current_ids = get_post_meta($download_id, $meta_key, true);
            if (!is_array($current_ids)) {
                $current_ids = array();
            }
            
            // Check if already exists
            $exists = false;
            foreach ($current_ids as $id) {
                if (absint($id) === $content_id_int) {
                    $exists = true;
                    break;
                }
            }
            
            if (!$exists) {
                $current_ids[] = $content_id_str;
                update_post_meta($download_id, $meta_key, $current_ids);
            }
        }
    }
}
