<?php
/**
 * Artist Manager
 * 
 * Handles artist management functionality
 *
 * @package Ensemble
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class ES_Artist_Manager {
    
    /**
     * Get all artists
     * @param array $args
     * @return array
     */
    public function get_artists($args = array()) {
        $defaults = array(
            'post_type'      => 'ensemble_artist',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'post_status'    => 'publish',
        );
        
        $args = wp_parse_args($args, $defaults);
        $posts = get_posts($args);
        
        $artists = array();
        foreach ($posts as $post) {
            $artists[] = $this->format_artist($post);
        }
        
        return $artists;
    }
    
    /**
     * Get single artist
     * @param int $artist_id
     * @return array|false
     */
    public function get_artist($artist_id) {
        $post = get_post($artist_id);
        if (!$post || $post->post_type !== 'ensemble_artist') {
            return false;
        }
        
        return $this->format_artist($post);
    }
    
    /**
     * Format artist data
     * @param WP_Post $post
     * @return array
     */
    private function format_artist($post) {
        // Get meta fields
        if (function_exists('get_field')) {
            $email = get_field('artist_email', $post->ID);
            $website = get_field('artist_website', $post->ID);
            $references = get_field('artist_references', $post->ID);
            $social_links = get_field('artist_social_links', $post->ID);
            $youtube = get_field('artist_youtube', $post->ID);
            $vimeo = get_field('artist_vimeo', $post->ID);
            $gallery_ids = get_field('artist_gallery', $post->ID);
        } else {
            $email = get_post_meta($post->ID, 'artist_email', true);
            $website = get_post_meta($post->ID, 'artist_website', true);
            $references = get_post_meta($post->ID, 'artist_references', true);
            $social_links = get_post_meta($post->ID, 'artist_social_links', true);
            $youtube = get_post_meta($post->ID, 'artist_youtube', true);
            $vimeo = get_post_meta($post->ID, 'artist_vimeo', true);
            $gallery_ids = get_post_meta($post->ID, 'artist_gallery', true);
        }
        
        // Get hero video fields
        $hero_video_url = get_post_meta($post->ID, '_artist_hero_video_url', true);
        $hero_video_autoplay = get_post_meta($post->ID, '_artist_hero_video_autoplay', true);
        $hero_video_loop = get_post_meta($post->ID, '_artist_hero_video_loop', true);
        $hero_video_controls = get_post_meta($post->ID, '_artist_hero_video_controls', true);
        
        // Get single layout preference
        $single_layout = get_post_meta($post->ID, '_artist_single_layout', true);
        
        // Get professional info fields (NEW)
        $position = get_post_meta($post->ID, '_es_artist_position', true);
        $company = get_post_meta($post->ID, '_es_artist_company', true);
        $additional = get_post_meta($post->ID, '_es_artist_additional', true);
        
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
        
        // Get genres from taxonomy
        $genres = wp_get_post_terms($post->ID, 'ensemble_genre');
        $genre_data = array();
        $genre_names = array();
        if (!is_wp_error($genres) && !empty($genres)) {
            foreach ($genres as $genre) {
                $genre_data[] = array(
                    'id' => $genre->term_id,
                    'name' => $genre->name,
                );
                $genre_names[] = $genre->name;
            }
        }
        
        // Get artist types from taxonomy
        $artist_types = wp_get_post_terms($post->ID, 'ensemble_artist_type');
        $type_data = array();
        $type_names = array();
        if (!is_wp_error($artist_types) && !empty($artist_types)) {
            foreach ($artist_types as $type) {
                $type_data[] = array(
                    'id' => $type->term_id,
                    'name' => $type->name,
                );
                $type_names[] = $type->name;
            }
        }
        
        // Count events
        $event_count = $this->get_artist_event_count($post->ID);
        
        // Get featured image ID
        $featured_image_id = get_post_thumbnail_id($post->ID);
        
        // Get downloads if addon is active
        $downloads = array();
        if (class_exists('ES_Addon_Manager') && ES_Addon_Manager::is_addon_active('downloads')) {
            $downloads_addon = ES_Addon_Manager::get_active_addon('downloads');
            if ($downloads_addon) {
                $download_manager = $downloads_addon->get_download_manager();
                $downloads = $download_manager->get_downloads(array('artist_id' => $post->ID));
            }
        }
        
        return array(
            'id'                    => $post->ID,
            'name'                  => $post->post_title,
            'description'           => $post->post_content,
            'email'                 => $email,
            'references'            => $references,
            'genres'                => $genre_data,
            'genre'                 => implode(', ', $genre_names), // For backward compatibility
            'artist_types'          => $type_data,
            'artist_type'           => implode(', ', $type_names), // For display
            'website'               => $website,
            'social_links'          => $social_links,
            'youtube'               => $youtube,
            'vimeo'                 => $vimeo,
            'featured_image'        => get_the_post_thumbnail_url($post->ID, 'medium'),
            'featured_image_id'     => $featured_image_id,
            'gallery'               => $gallery,
            'event_count'           => $event_count,
            'downloads'             => $downloads,
            'created'               => $post->post_date,
            'modified'              => $post->post_modified,
            // Hero video fields
            'hero_video_url'        => $hero_video_url,
            'hero_video_autoplay'   => $hero_video_autoplay === '1',
            'hero_video_loop'       => $hero_video_loop === '1',
            'hero_video_controls'   => $hero_video_controls === '1',
            'single_layout'         => $single_layout ?: 'default',
            // Professional info fields (NEW)
            'position'              => $position,
            'company'               => $company,
            'additional'            => $additional,
        );
    }
    
    /**
     * Get artist event count
     * @param int $artist_id
     * @return int
     */
    private function get_artist_event_count($artist_id) {
        global $wpdb;
        
        // Get the correct meta key for artist field
        $artist_key = class_exists('ES_Meta_Keys') ? ES_Meta_Keys::get('artist') : 'event_artist';
        
        // Search for serialized arrays (ACF stores as serialized)
        // Pattern matches: i:123; (integer in serialized array)
        // Or s:3:"123"; (string in serialized array)
        $count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT post_id) 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = %s 
            AND (
                meta_value LIKE %s
                OR meta_value LIKE %s
                OR meta_value = %s
            )
        ", 
            $artist_key,
            '%i:' . $artist_id . ';%',  // Serialized integer
            '%s:' . strlen($artist_id) . ':"' . $artist_id . '";%',  // Serialized string
            $artist_id  // Plain value
        ));
        
        return intval($count);
    }
    
    /**
     * Search artists
     * @param string $search
     * @return array
     */
    public function search_artists($search) {
        $args = array(
            's' => $search,
        );
        
        return $this->get_artists($args);
    }
    
    /**
     * Save artist
     * @param array $data
     * @return int|WP_Error
     */
    public function save_artist($data) {
        $artist_id = isset($data['artist_id']) ? intval($data['artist_id']) : 0;
        $name = isset($data['name']) ? sanitize_text_field($data['name']) : '';
        
        if (empty($name)) {
            return new WP_Error('missing_name', 'Artist name is required');
        }
        
        // Prepare post data
        $post_data = array(
            'post_title'   => $name,
            'post_content' => isset($data['description']) ? wp_kses_post($data['description']) : '',
            'post_type'    => 'ensemble_artist',
            'post_status'  => 'publish',
        );
        
        // Update or create
        if ($artist_id) {
            $post_data['ID'] = $artist_id;
            $result = wp_update_post($post_data);
        } else {
            $result = wp_insert_post($post_data);
        }
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        $artist_id = $result;
        
        // Save genres (taxonomy)
        if (isset($data['genre']) && is_array($data['genre'])) {
            $genre_ids = array_map('intval', $data['genre']);
            wp_set_post_terms($artist_id, $genre_ids, 'ensemble_genre');
        }
        
        // Save artist types (taxonomy)
        if (isset($data['artist_type']) && is_array($data['artist_type'])) {
            $type_ids = array_map('intval', $data['artist_type']);
            wp_set_post_terms($artist_id, $type_ids, 'ensemble_artist_type');
        }
        
        // Save ACF fields if available
        if (function_exists('update_field')) {
            if (isset($data['email'])) {
                update_field('artist_email', sanitize_email($data['email']), $artist_id);
            }
            
            if (isset($data['website'])) {
                update_field('artist_website', esc_url_raw($data['website']), $artist_id);
            }
            
            if (isset($data['references'])) {
                update_field('artist_references', sanitize_text_field($data['references']), $artist_id);
            }
            
            // Handle social links - save both array format AND individual fields
            if (isset($data['social_links']) && is_array($data['social_links'])) {
                $sanitized_links = array_map('esc_url_raw', $data['social_links']);
                update_field('artist_social_links', $sanitized_links, $artist_id);
                
                // ALSO save as individual fields for template compatibility
                foreach ($sanitized_links as $link) {
                    $url = esc_url_raw($link);
                    if (empty($url)) continue;
                    
                    // Detect platform and save to appropriate field
                    if (stripos($url, 'facebook.com') !== false) {
                        update_field('artist_facebook', $url, $artist_id);
                    } elseif (stripos($url, 'instagram.com') !== false) {
                        update_field('artist_instagram', $url, $artist_id);
                    } elseif (stripos($url, 'twitter.com') !== false || stripos($url, 'x.com') !== false) {
                        update_field('artist_twitter', $url, $artist_id);
                    } elseif (stripos($url, 'soundcloud.com') !== false) {
                        update_field('artist_soundcloud', $url, $artist_id);
                    } elseif (stripos($url, 'spotify.com') !== false) {
                        update_field('artist_spotify', $url, $artist_id);
                    }
                }
            }
            
            if (isset($data['youtube'])) {
                update_field('artist_youtube', esc_url_raw($data['youtube']), $artist_id);
            }
            if (isset($data['vimeo'])) {
                update_field('artist_vimeo', esc_url_raw($data['vimeo']), $artist_id);
            }
            if (isset($data['gallery_ids']) && is_array($data['gallery_ids'])) {
                $gallery_ids = array_map('intval', $data['gallery_ids']);
                update_field('artist_gallery', $gallery_ids, $artist_id);
            }
        } else {
            // Fallback to post meta
            if (isset($data['email'])) {
                update_post_meta($artist_id, 'artist_email', sanitize_email($data['email']));
            }
            
            if (isset($data['website'])) {
                update_post_meta($artist_id, 'artist_website', esc_url_raw($data['website']));
            }
            
            if (isset($data['references'])) {
                update_post_meta($artist_id, 'artist_references', sanitize_text_field($data['references']));
            }
            
            // Handle social links - save both array format AND individual fields
            if (isset($data['social_links']) && is_array($data['social_links'])) {
                $sanitized_links = array_map('esc_url_raw', $data['social_links']);
                update_post_meta($artist_id, 'artist_social_links', $sanitized_links);
                
                // ALSO save as individual fields for template compatibility
                foreach ($sanitized_links as $link) {
                    $url = esc_url_raw($link);
                    if (empty($url)) continue;
                    
                    // Detect platform and save to appropriate field
                    if (stripos($url, 'facebook.com') !== false) {
                        update_post_meta($artist_id, 'artist_facebook', $url);
                    } elseif (stripos($url, 'instagram.com') !== false) {
                        update_post_meta($artist_id, 'artist_instagram', $url);
                    } elseif (stripos($url, 'twitter.com') !== false || stripos($url, 'x.com') !== false) {
                        update_post_meta($artist_id, 'artist_twitter', $url);
                    } elseif (stripos($url, 'soundcloud.com') !== false) {
                        update_post_meta($artist_id, 'artist_soundcloud', $url);
                    } elseif (stripos($url, 'spotify.com') !== false) {
                        update_post_meta($artist_id, 'artist_spotify', $url);
                    }
                }
            }
            
            if (isset($data['youtube'])) {
                update_post_meta($artist_id, 'artist_youtube', esc_url_raw($data['youtube']));
            }
            if (isset($data['vimeo'])) {
                update_post_meta($artist_id, 'artist_vimeo', esc_url_raw($data['vimeo']));
            }
            if (isset($data['gallery_ids']) && is_array($data['gallery_ids'])) {
                $gallery_ids = array_map('intval', $data['gallery_ids']);
                update_post_meta($artist_id, 'artist_gallery', $gallery_ids);
            }
        }
        
        // Set featured image
        if (isset($data['featured_image_id']) && !empty($data['featured_image_id'])) {
            set_post_thumbnail($artist_id, intval($data['featured_image_id']));
        }
        
        // Save hero video fields
        if (isset($data['hero_video_url'])) {
            update_post_meta($artist_id, '_artist_hero_video_url', esc_url_raw($data['hero_video_url']));
        }
        if (isset($data['hero_video_autoplay'])) {
            update_post_meta($artist_id, '_artist_hero_video_autoplay', $data['hero_video_autoplay'] ? '1' : '0');
        }
        if (isset($data['hero_video_loop'])) {
            update_post_meta($artist_id, '_artist_hero_video_loop', $data['hero_video_loop'] ? '1' : '0');
        }
        if (isset($data['hero_video_controls'])) {
            update_post_meta($artist_id, '_artist_hero_video_controls', $data['hero_video_controls'] ? '1' : '0');
        }
        
        // Save single layout preference
        if (isset($data['single_layout'])) {
            update_post_meta($artist_id, '_artist_single_layout', sanitize_text_field($data['single_layout']));
        }
        
        // Save professional info fields (NEW)
        if (isset($data['position'])) {
            update_post_meta($artist_id, '_es_artist_position', sanitize_text_field($data['position']));
        }
        if (isset($data['company'])) {
            update_post_meta($artist_id, '_es_artist_company', sanitize_text_field($data['company']));
        }
        if (isset($data['additional'])) {
            update_post_meta($artist_id, '_es_artist_additional', sanitize_textarea_field($data['additional']));
        }
        
        // Update download links if Downloads addon is active
        if (isset($data['downloads_data']) && class_exists('ES_Addon_Manager') && ES_Addon_Manager::is_addon_active('downloads')) {
            $download_ids = json_decode(stripslashes($data['downloads_data']), true);
            if (is_array($download_ids)) {
                $downloads_addon = ES_Addon_Manager::get_active_addon('downloads');
                if ($downloads_addon) {
                    $download_manager = $downloads_addon->get_download_manager();
                    $download_manager->update_artist_downloads($artist_id, $download_ids);
                }
            }
        }
        
        return $artist_id;
    }
    
    /**
     * Delete artist
     * @param int $artist_id
     * @return bool
     */
    public function delete_artist($artist_id) {
        $result = wp_delete_post($artist_id, true);
        return !empty($result);
    }
    
    /**
     * Bulk delete artists
     * @param array $artist_ids
     * @return array
     */
    public function bulk_delete($artist_ids) {
        $deleted = 0;
        $failed = 0;
        
        foreach ($artist_ids as $id) {
            if ($this->delete_artist($id)) {
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
