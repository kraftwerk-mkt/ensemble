<?php
/**
 * Gallery AJAX Handlers
 * 
 * Add these methods to class-es-ajax-handler.php
 * or include this file and instantiate ES_Gallery_Ajax
 *
 * @package Ensemble
 * @since 3.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class ES_Gallery_Ajax {
    
    /**
     * Initialize AJAX handlers
     */
    public function __construct() {
        // Gallery CRUD
        add_action('wp_ajax_ensemble_get_gallery', array($this, 'get_gallery'));
        add_action('wp_ajax_ensemble_save_gallery', array($this, 'save_gallery'));
        add_action('wp_ajax_ensemble_delete_gallery', array($this, 'delete_gallery'));
        add_action('wp_ajax_ensemble_get_galleries', array($this, 'get_galleries'));
        add_action('wp_ajax_ensemble_bulk_delete_galleries', array($this, 'bulk_delete_galleries'));
        
        // Gallery linking (entfernt - nur für Kompatibilität)
        add_action('wp_ajax_ensemble_get_linked_galleries', array($this, 'get_linked_galleries'));
        add_action('wp_ajax_ensemble_link_gallery', array($this, 'link_gallery'));
        add_action('wp_ajax_ensemble_unlink_gallery', array($this, 'unlink_gallery'));
        
        // Video info
        add_action('wp_ajax_es_gallery_get_video_info', array($this, 'get_video_info'));
        add_action('wp_ajax_nopriv_es_gallery_get_video_info', array($this, 'get_video_info'));
    }
    
    /**
     * Get single gallery
     */
    public function get_gallery() {
        check_ajax_referer('ensemble_ajax', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Permission denied', 'ensemble')));
        }
        
        $gallery_id = isset($_POST['gallery_id']) ? intval($_POST['gallery_id']) : 0;
        
        if (!$gallery_id) {
            wp_send_json_error(array('message' => __('Gallery ID required', 'ensemble')));
        }
        
        $manager = new ES_Gallery_Manager();
        $gallery = $manager->get_gallery($gallery_id);
        
        if (!$gallery) {
            wp_send_json_error(array('message' => __('Gallery not found', 'ensemble')));
        }
        
        wp_send_json_success($gallery);
    }
    
    /**
     * Save gallery
     */
    public function save_gallery() {
        check_ajax_referer('ensemble_ajax', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Permission denied', 'ensemble')));
        }
        
        $data = array(
            'gallery_id'      => isset($_POST['gallery_id']) ? intval($_POST['gallery_id']) : 0,
            'title'           => isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '',
            'description'     => isset($_POST['description']) ? wp_kses_post($_POST['description']) : '',
            'categories'      => isset($_POST['categories']) ? array_map('intval', (array)$_POST['categories']) : array(),
            'layout'          => isset($_POST['layout']) ? sanitize_text_field($_POST['layout']) : 'grid',
            'columns'         => isset($_POST['columns']) ? absint($_POST['columns']) : 4,
            'lightbox'        => true,
            'linked_event'    => isset($_POST['linked_event']) ? intval($_POST['linked_event']) : 0,
            'linked_artist'   => isset($_POST['linked_artist']) ? intval($_POST['linked_artist']) : 0,
            'linked_location' => isset($_POST['linked_location']) ? intval($_POST['linked_location']) : 0,
            'image_ids'       => isset($_POST['image_ids']) ? array_map('intval', (array)$_POST['image_ids']) : array(),
            'videos'          => isset($_POST['videos']) ? $this->sanitize_videos($_POST['videos']) : array(),
        );
        
        $manager = new ES_Gallery_Manager();
        $result = $manager->save_gallery($data);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success(array(
            'gallery_id' => $result,
            'message'    => __('Gallery saved successfully', 'ensemble'),
        ));
    }
    
    /**
     * Delete gallery
     */
    public function delete_gallery() {
        check_ajax_referer('ensemble_ajax', 'nonce');
        
        if (!current_user_can('delete_posts')) {
            wp_send_json_error(array('message' => __('Permission denied', 'ensemble')));
        }
        
        $gallery_id = isset($_POST['gallery_id']) ? intval($_POST['gallery_id']) : 0;
        
        if (!$gallery_id) {
            wp_send_json_error(array('message' => __('Gallery ID required', 'ensemble')));
        }
        
        $manager = new ES_Gallery_Manager();
        $result = $manager->delete_gallery($gallery_id);
        
        if (!$result) {
            wp_send_json_error(array('message' => __('Could not delete gallery', 'ensemble')));
        }
        
        wp_send_json_success(array('message' => __('Gallery deleted', 'ensemble')));
    }
    
    /**
     * Bulk delete galleries
     */
    public function bulk_delete_galleries() {
        check_ajax_referer('ensemble_ajax', 'nonce');
        
        if (!current_user_can('delete_posts')) {
            wp_send_json_error(array('message' => __('Permission denied', 'ensemble')));
        }
        
        $gallery_ids = isset($_POST['gallery_ids']) ? array_map('intval', (array)$_POST['gallery_ids']) : array();
        
        if (empty($gallery_ids)) {
            wp_send_json_error(array('message' => __('No galleries selected', 'ensemble')));
        }
        
        $manager = new ES_Gallery_Manager();
        $deleted = 0;
        
        foreach ($gallery_ids as $id) {
            if ($manager->delete_gallery($id)) {
                $deleted++;
            }
        }
        
        wp_send_json_success(array(
            'message' => sprintf(__('%d galleries deleted', 'ensemble'), $deleted),
            'deleted' => $deleted,
        ));
    }
    
    /**
     * Get all galleries (with optional filters)
     */
    public function get_galleries() {
        check_ajax_referer('ensemble_ajax', 'nonce');
        
        $args = array();
        
        // Filter by entity
        if (!empty($_POST['event_id'])) {
            $args['meta_query'] = array(
                array(
                    'key'   => '_linked_event',
                    'value' => intval($_POST['event_id']),
                ),
            );
        } elseif (!empty($_POST['artist_id'])) {
            $args['meta_query'] = array(
                array(
                    'key'   => '_linked_artist',
                    'value' => intval($_POST['artist_id']),
                ),
            );
        } elseif (!empty($_POST['location_id'])) {
            $args['meta_query'] = array(
                array(
                    'key'   => '_linked_location',
                    'value' => intval($_POST['location_id']),
                ),
            );
        }
        
        $manager = new ES_Gallery_Manager();
        $galleries = $manager->get_galleries($args);
        
        wp_send_json_success($galleries);
    }
    
    /**
     * Get galleries linked to an entity
     */
    public function get_linked_galleries() {
        check_ajax_referer('ensemble_ajax', 'nonce');
        
        $entity_type = isset($_POST['entity_type']) ? sanitize_text_field($_POST['entity_type']) : '';
        $entity_id = isset($_POST['entity_id']) ? intval($_POST['entity_id']) : 0;
        
        if (!$entity_type || !$entity_id) {
            wp_send_json_error(array('message' => __('Entity type and ID required', 'ensemble')));
        }
        
        $manager = new ES_Gallery_Manager();
        $method = "get_galleries_by_{$entity_type}";
        
        if (!method_exists($manager, $method)) {
            wp_send_json_error(array('message' => __('Invalid entity type', 'ensemble')));
        }
        
        $galleries = $manager->$method($entity_id);
        
        wp_send_json_success($galleries);
    }
    
    /**
     * Link a gallery to an entity
     */
    public function link_gallery() {
        check_ajax_referer('ensemble_ajax', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Permission denied', 'ensemble')));
        }
        
        $gallery_id = isset($_POST['gallery_id']) ? intval($_POST['gallery_id']) : 0;
        $entity_type = isset($_POST['entity_type']) ? sanitize_text_field($_POST['entity_type']) : '';
        $entity_id = isset($_POST['entity_id']) ? intval($_POST['entity_id']) : 0;
        
        if (!$gallery_id || !$entity_type || !$entity_id) {
            wp_send_json_error(array('message' => __('Missing required fields', 'ensemble')));
        }
        
        $meta_key = "_linked_{$entity_type}";
        $result = update_post_meta($gallery_id, $meta_key, $entity_id);
        
        wp_send_json_success(array('message' => __('Gallery linked', 'ensemble')));
    }
    
    /**
     * Unlink a gallery from an entity
     */
    public function unlink_gallery() {
        check_ajax_referer('ensemble_ajax', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Permission denied', 'ensemble')));
        }
        
        $gallery_id = isset($_POST['gallery_id']) ? intval($_POST['gallery_id']) : 0;
        $entity_type = isset($_POST['entity_type']) ? sanitize_text_field($_POST['entity_type']) : '';
        
        if (!$gallery_id || !$entity_type) {
            wp_send_json_error(array('message' => __('Missing required fields', 'ensemble')));
        }
        
        $meta_key = "_linked_{$entity_type}";
        delete_post_meta($gallery_id, $meta_key);
        
        wp_send_json_success(array('message' => __('Gallery unlinked', 'ensemble')));
    }
    
    /**
     * Get video info from URL
     */
    public function get_video_info() {
        // Allow both logged in and guest users to validate URLs
        // but limit the action with nonce
        check_ajax_referer('ensemble_gallery_pro', 'nonce');
        
        $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
        
        if (empty($url)) {
            wp_send_json_error(array('message' => __('URL required', 'ensemble')));
        }
        
        $video_data = $this->parse_video_url($url);
        
        if (!$video_data) {
            wp_send_json_error(array('message' => __('Invalid or unsupported video URL', 'ensemble')));
        }
        
        $thumbnail = $this->get_video_thumbnail($video_data['provider'], $video_data['id']);
        
        wp_send_json_success(array(
            'provider'  => $video_data['provider'],
            'video_id'  => $video_data['id'],
            'embed_url' => $video_data['embed_url'],
            'thumbnail' => $thumbnail,
        ));
    }
    
    /**
     * Parse video URL
     * @param string $url
     * @return array|false
     */
    private function parse_video_url($url) {
        // Check for self-hosted video
        if (preg_match('/\.(mp4|webm|ogg|mov|m4v)$/i', parse_url($url, PHP_URL_PATH))) {
            return array(
                'provider'  => 'local',
                'id'        => md5($url),
                'embed_url' => $url,
            );
        }
        
        // YouTube patterns
        $youtube_patterns = array(
            '/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/',
            '/youtube\.com\/embed\/([a-zA-Z0-9_-]+)/',
            '/youtu\.be\/([a-zA-Z0-9_-]+)/',
            '/youtube\.com\/shorts\/([a-zA-Z0-9_-]+)/',
        );
        
        foreach ($youtube_patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return array(
                    'provider'  => 'youtube',
                    'id'        => $matches[1],
                    'embed_url' => "https://www.youtube.com/embed/{$matches[1]}",
                );
            }
        }
        
        // Vimeo patterns
        $vimeo_patterns = array(
            '/vimeo\.com\/([0-9]+)/',
            '/player\.vimeo\.com\/video\/([0-9]+)/',
        );
        
        foreach ($vimeo_patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return array(
                    'provider'  => 'vimeo',
                    'id'        => $matches[1],
                    'embed_url' => "https://player.vimeo.com/video/{$matches[1]}",
                );
            }
        }
        
        return false;
    }
    
    /**
     * Get video thumbnail
     * @param string $provider
     * @param string $video_id
     * @return string
     */
    private function get_video_thumbnail($provider, $video_id) {
        if ($provider === 'youtube') {
            return "https://img.youtube.com/vi/{$video_id}/hqdefault.jpg";
        }
        
        if ($provider === 'vimeo') {
            $cached = get_transient("es_vimeo_thumb_{$video_id}");
            if ($cached) {
                return $cached;
            }
            
            $response = wp_remote_get("https://vimeo.com/api/v2/video/{$video_id}.json");
            if (!is_wp_error($response)) {
                $body = json_decode(wp_remote_retrieve_body($response), true);
                if (!empty($body[0]['thumbnail_large'])) {
                    $thumb = $body[0]['thumbnail_large'];
                    set_transient("es_vimeo_thumb_{$video_id}", $thumb, DAY_IN_SECONDS);
                    return $thumb;
                }
            }
        }
        
        return '';
    }
    
    /**
     * Sanitize videos array
     * @param mixed $videos
     * @return array
     */
    private function sanitize_videos($videos) {
        if (is_string($videos)) {
            $videos = json_decode(stripslashes($videos), true);
        }
        
        if (!is_array($videos)) {
            return array();
        }
        
        $sanitized = array();
        
        foreach ($videos as $video) {
            if (!is_array($video) || empty($video['url'])) {
                continue;
            }
            
            $url = esc_url_raw($video['url']);
            if (!$url || !$this->parse_video_url($url)) {
                continue;
            }
            
            $sanitized[] = array(
                'url'       => $url,
                'title'     => isset($video['title']) ? sanitize_text_field($video['title']) : '',
                'provider'  => isset($video['provider']) ? sanitize_text_field($video['provider']) : '',
                'thumbnail' => isset($video['thumbnail']) ? esc_url_raw($video['thumbnail']) : '',
            );
        }
        
        return $sanitized;
    }
}

// Initialize
new ES_Gallery_Ajax();
