<?php
/**
 * Gallery Manager
 * 
 * Handles gallery management functionality
 *
 * @package Ensemble
 * @since 2.0.0
 * @updated 3.0.0 - Added video support
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class ES_Gallery_Manager {
    
    /**
     * Get all galleries
     * @param array $args
     * @return array
     */
    public function get_galleries($args = array()) {
        $defaults = array(
            'post_type'      => 'ensemble_gallery',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'post_status'    => 'publish',
        );
        
        $args = wp_parse_args($args, $defaults);
        $posts = get_posts($args);
        
        $galleries = array();
        foreach ($posts as $post) {
            $galleries[] = $this->format_gallery($post);
        }
        
        return $galleries;
    }
    
    /**
     * Get single gallery
     * @param int $gallery_id
     * @return array|false
     */
    public function get_gallery($gallery_id) {
        $post = get_post($gallery_id);
        if (!$post || $post->post_type !== 'ensemble_gallery') {
            return false;
        }
        
        return $this->format_gallery($post);
    }
    
    /**
     * Format gallery data
     * @param WP_Post $post
     * @return array
     */
    private function format_gallery($post) {
        // Get gallery images
        $image_ids = get_post_meta($post->ID, '_gallery_images', true);
        if (!is_array($image_ids)) {
            $image_ids = array();
        }
        
        // Format images with URLs
        $images = array();
        foreach ($image_ids as $img_id) {
            $img_url = wp_get_attachment_image_url($img_id, 'medium');
            $img_url_large = wp_get_attachment_image_url($img_id, 'large');
            $img_url_full = wp_get_attachment_image_url($img_id, 'full');
            if ($img_url) {
                $images[] = array(
                    'id'    => $img_id,
                    'url'   => $img_url,
                    'large' => $img_url_large,
                    'full'  => $img_url_full,
                    'alt'   => get_post_meta($img_id, '_wp_attachment_image_alt', true),
                    'caption' => wp_get_attachment_caption($img_id),
                );
            }
        }
        
        // Get videos (NEU in 3.0)
        $videos = get_post_meta($post->ID, '_gallery_videos', true);
        if (!is_array($videos)) {
            $videos = array();
        }
        
        // Get categories
        $categories = wp_get_post_terms($post->ID, 'ensemble_gallery_category');
        $category_data = array();
        $category_names = array();
        if (!is_wp_error($categories) && !empty($categories)) {
            foreach ($categories as $cat) {
                $category_data[] = array(
                    'id'   => $cat->term_id,
                    'name' => $cat->name,
                    'slug' => $cat->slug,
                );
                $category_names[] = $cat->name;
            }
        }
        
        // Get linked event
        $linked_event_id = get_post_meta($post->ID, '_linked_event', true);
        $linked_event = null;
        if ($linked_event_id) {
            $event_post = get_post($linked_event_id);
            if ($event_post) {
                $linked_event = array(
                    'id'    => $event_post->ID,
                    'title' => $event_post->post_title,
                    'url'   => get_permalink($event_post->ID),
                );
            }
        }
        
        // Get linked artist
        $linked_artist_id = get_post_meta($post->ID, '_linked_artist', true);
        $linked_artist = null;
        if ($linked_artist_id) {
            $artist_post = get_post($linked_artist_id);
            if ($artist_post) {
                $linked_artist = array(
                    'id'    => $artist_post->ID,
                    'title' => $artist_post->post_title,
                    'url'   => get_permalink($artist_post->ID),
                );
            }
        }
        
        // Get linked location
        $linked_location_id = get_post_meta($post->ID, '_linked_location', true);
        $linked_location = null;
        if ($linked_location_id) {
            $location_post = get_post($linked_location_id);
            if ($location_post) {
                $linked_location = array(
                    'id'    => $location_post->ID,
                    'title' => $location_post->post_title,
                    'url'   => get_permalink($location_post->ID),
                );
            }
        }
        
        // Get layout settings
        $layout = get_post_meta($post->ID, '_gallery_layout', true) ?: 'grid';
        $columns = get_post_meta($post->ID, '_gallery_columns', true) ?: 3;
        $lightbox = get_post_meta($post->ID, '_gallery_lightbox', true) !== '0';
        
        return array(
            'id'              => $post->ID,
            'title'           => $post->post_title,
            'description'     => $post->post_content,
            'slug'            => $post->post_name,
            'images'          => $images,
            'image_count'     => count($images),
            'videos'          => $videos,
            'video_count'     => count($videos),
            'total_count'     => count($images) + count($videos),
            'categories'      => $category_data,
            'category'        => implode(', ', $category_names),
            'linked_event'    => $linked_event,
            'linked_artist'   => $linked_artist,
            'linked_location' => $linked_location,
            'layout'          => $layout,
            'columns'         => intval($columns),
            'lightbox'        => $lightbox,
            'featured_image'  => get_the_post_thumbnail_url($post->ID, 'medium'),
            'featured_image_id' => get_post_thumbnail_id($post->ID),
            'created'         => $post->post_date,
            'modified'        => $post->post_modified,
        );
    }
    
    /**
     * Get gallery images only
     * @param int $gallery_id
     * @return array
     */
    public function get_gallery_images($gallery_id) {
        $gallery = $this->get_gallery($gallery_id);
        if (!$gallery) {
            return array();
        }
        return $gallery['images'];
    }
    
    /**
     * Get gallery videos only
     * @param int $gallery_id
     * @return array
     */
    public function get_gallery_videos($gallery_id) {
        $gallery = $this->get_gallery($gallery_id);
        if (!$gallery) {
            return array();
        }
        return $gallery['videos'];
    }
    
    /**
     * Search galleries
     * @param string $search
     * @return array
     */
    public function search_galleries($search) {
        $args = array(
            's' => $search,
        );
        
        return $this->get_galleries($args);
    }
    
    /**
     * Get galleries by event
     * @param int $event_id
     * @return array
     */
    public function get_galleries_by_event($event_id) {
        $args = array(
            'meta_query' => array(
                array(
                    'key'   => '_linked_event',
                    'value' => $event_id,
                ),
            ),
        );
        
        return $this->get_galleries($args);
    }
    
    /**
     * Get galleries by artist
     * @param int $artist_id
     * @return array
     */
    public function get_galleries_by_artist($artist_id) {
        $args = array(
            'meta_query' => array(
                array(
                    'key'   => '_linked_artist',
                    'value' => $artist_id,
                ),
            ),
        );
        
        return $this->get_galleries($args);
    }
    
    /**
     * Get galleries by location
     * @param int $location_id
     * @return array
     */
    public function get_galleries_by_location($location_id) {
        $args = array(
            'meta_query' => array(
                array(
                    'key'   => '_linked_location',
                    'value' => $location_id,
                ),
            ),
        );
        
        return $this->get_galleries($args);
    }
    
    /**
     * Save gallery
     * @param array $data
     * @return int|WP_Error
     */
    public function save_gallery($data) {
        $gallery_id = isset($data['gallery_id']) ? intval($data['gallery_id']) : 0;
        $title = isset($data['title']) ? sanitize_text_field($data['title']) : '';
        
        if (empty($title)) {
            return new WP_Error('missing_title', __('Gallery title is required', 'ensemble'));
        }
        
        // Prepare post data
        $post_data = array(
            'post_title'   => $title,
            'post_content' => isset($data['description']) ? wp_kses_post($data['description']) : '',
            'post_type'    => 'ensemble_gallery',
            'post_status'  => 'publish',
        );
        
        // Update or create
        if ($gallery_id) {
            $post_data['ID'] = $gallery_id;
            $result = wp_update_post($post_data);
        } else {
            $result = wp_insert_post($post_data);
        }
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        $gallery_id = $result;
        
        // Save categories
        if (isset($data['categories']) && is_array($data['categories'])) {
            $category_ids = array_map('intval', $data['categories']);
            wp_set_post_terms($gallery_id, $category_ids, 'ensemble_gallery_category');
        }
        
        // Save gallery images
        if (isset($data['image_ids']) && is_array($data['image_ids'])) {
            $image_ids = array_map('intval', array_filter($data['image_ids']));
            update_post_meta($gallery_id, '_gallery_images', $image_ids);
        }
        
        // Save gallery videos (NEU in 3.0)
        if (isset($data['videos']) && is_array($data['videos'])) {
            $videos = array();
            foreach ($data['videos'] as $video) {
                if (!empty($video['url'])) {
                    $videos[] = array(
                        'url'       => esc_url_raw($video['url']),
                        'title'     => isset($video['title']) ? sanitize_text_field($video['title']) : '',
                        'provider'  => isset($video['provider']) ? sanitize_text_field($video['provider']) : 'local',
                        'thumbnail' => isset($video['thumbnail']) ? esc_url_raw($video['thumbnail']) : '',
                    );
                }
            }
            update_post_meta($gallery_id, '_gallery_videos', $videos);
        }
        
        // Save linked event
        if (isset($data['linked_event'])) {
            if (!empty($data['linked_event'])) {
                update_post_meta($gallery_id, '_linked_event', intval($data['linked_event']));
            } else {
                delete_post_meta($gallery_id, '_linked_event');
            }
        }
        
        // Save linked artist
        if (isset($data['linked_artist'])) {
            if (!empty($data['linked_artist'])) {
                update_post_meta($gallery_id, '_linked_artist', intval($data['linked_artist']));
            } else {
                delete_post_meta($gallery_id, '_linked_artist');
            }
        }
        
        // Save linked location
        if (isset($data['linked_location'])) {
            if (!empty($data['linked_location'])) {
                update_post_meta($gallery_id, '_linked_location', intval($data['linked_location']));
            } else {
                delete_post_meta($gallery_id, '_linked_location');
            }
        }
        
        // Save layout settings
        if (isset($data['layout'])) {
            update_post_meta($gallery_id, '_gallery_layout', sanitize_text_field($data['layout']));
        }
        
        if (isset($data['columns'])) {
            update_post_meta($gallery_id, '_gallery_columns', absint($data['columns']));
        }
        
        if (isset($data['lightbox'])) {
            update_post_meta($gallery_id, '_gallery_lightbox', $data['lightbox'] ? '1' : '0');
        }
        
        // Set featured image (first gallery image as cover)
        if (isset($data['featured_image_id']) && !empty($data['featured_image_id'])) {
            set_post_thumbnail($gallery_id, intval($data['featured_image_id']));
        } elseif (isset($data['image_ids']) && !empty($data['image_ids'])) {
            // Auto-set first image as featured
            set_post_thumbnail($gallery_id, intval($data['image_ids'][0]));
        }
        
        return $gallery_id;
    }
    
    /**
     * Delete gallery
     * @param int $gallery_id
     * @return bool
     */
    public function delete_gallery($gallery_id) {
        $result = wp_delete_post($gallery_id, true);
        return !empty($result);
    }
    
    /**
     * Bulk delete galleries
     * @param array $gallery_ids
     * @return array
     */
    public function bulk_delete($gallery_ids) {
        $deleted = 0;
        $failed = 0;
        
        foreach ($gallery_ids as $id) {
            if ($this->delete_gallery($id)) {
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
