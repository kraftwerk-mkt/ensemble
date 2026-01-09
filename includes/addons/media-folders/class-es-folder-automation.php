<?php
/**
 * Ensemble Media Folders - Automation Handler
 * 
 * Handles automatic folder creation and media assignment
 *
 * @package Ensemble
 * @subpackage Addons/MediaFolders
 * @since 2.7.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class ES_Folder_Automation {
    
    /**
     * Parent addon instance
     * @var ES_Media_Folders_Addon
     */
    private $addon;
    
    /**
     * Post type to folder type mapping
     * @var array
     */
    private $type_map = array(
        'es_event'    => 'events',
        'es_artist'   => 'artists',
        'es_location' => 'locations',
    );
    
    /**
     * Constructor
     */
    public function __construct($addon) {
        $this->addon = $addon;
        $this->register_hooks();
    }
    
    /**
     * Register hooks
     */
    private function register_hooks() {
        // Auto-create folders on post save
        add_action('save_post_es_event', array($this, 'handle_event_save'), 20, 2);
        add_action('save_post_es_artist', array($this, 'handle_artist_save'), 20, 2);
        add_action('save_post_es_location', array($this, 'handle_location_save'), 20, 2);
        
        // Auto-assign media on upload from Ensemble context
        add_filter('wp_handle_upload', array($this, 'handle_upload'), 10, 2);
        add_action('add_attachment', array($this, 'assign_uploaded_media'), 20);
        
        // Handle post deletion
        add_action('before_delete_post', array($this, 'handle_post_delete'));
        
        // Handle featured image assignment
        add_action('set_post_thumbnail', array($this, 'handle_thumbnail_set'), 10, 3);
        
        // Handle gallery updates via Wizard
        add_action('ensemble_wizard_media_saved', array($this, 'handle_wizard_media_save'), 10, 3);
    }
    
    /**
     * Handle event save
     */
    public function handle_event_save($post_id, $post) {
        if (!$this->addon->get_setting('auto_events', true)) {
            return;
        }
        
        $this->create_folder_for_post($post_id, $post, 'events');
    }
    
    /**
     * Handle artist save
     */
    public function handle_artist_save($post_id, $post) {
        if (!$this->addon->get_setting('auto_artists', true)) {
            return;
        }
        
        $this->create_folder_for_post($post_id, $post, 'artists');
    }
    
    /**
     * Handle location save
     */
    public function handle_location_save($post_id, $post) {
        if (!$this->addon->get_setting('auto_locations', true)) {
            return;
        }
        
        $this->create_folder_for_post($post_id, $post, 'locations');
    }
    
    /**
     * Create folder for a post
     */
    private function create_folder_for_post($post_id, $post, $type) {
        // Skip auto-drafts and revisions
        if ($post->post_status === 'auto-draft' || wp_is_post_revision($post_id)) {
            return;
        }
        
        // Skip if no title yet
        if (empty($post->post_title) || $post->post_title === __('Auto Draft')) {
            return;
        }
        
        // Check if folder already exists for this post
        $existing_folder_id = get_post_meta($post_id, '_es_media_folder_id', true);
        
        if ($existing_folder_id) {
            $term = get_term($existing_folder_id, ES_Folder_Taxonomy::TAXONOMY);
            
            if ($term && !is_wp_error($term)) {
                // Update folder name if post title changed
                if ($term->name !== $post->post_title) {
                    wp_update_term($existing_folder_id, ES_Folder_Taxonomy::TAXONOMY, array(
                        'name' => $post->post_title,
                    ));
                }
                return;
            }
        }
        
        // Get parent folder
        $parent_id = $this->addon->taxonomy->get_parent_folder_id($type);
        
        if (!$parent_id) {
            return;
        }
        
        // Get color based on type
        $color = $this->addon->get_setting("color_{$type}", '#3582c4');
        
        // Create folder
        $folder_id = $this->addon->taxonomy->create_folder($post->post_title, $parent_id, array(
            '_folder_color'   => $color,
            '_folder_post_id' => $post_id,
            '_folder_type'    => 'auto',
        ));
        
        if (!is_wp_error($folder_id)) {
            update_post_meta($post_id, '_es_media_folder_id', $folder_id);
            
            // Auto-assign existing featured image and gallery
            $this->assign_post_media_to_folder($post_id, $folder_id);
        }
    }
    
    /**
     * Assign all media from a post to its folder
     */
    private function assign_post_media_to_folder($post_id, $folder_id) {
        // Featured image
        $thumbnail_id = get_post_thumbnail_id($post_id);
        if ($thumbnail_id) {
            $this->addon->taxonomy->assign_to_folder($thumbnail_id, $folder_id);
        }
        
        // Gallery images (if using Ensemble gallery meta)
        $gallery = get_post_meta($post_id, '_ensemble_gallery', true);
        if (!empty($gallery) && is_array($gallery)) {
            foreach ($gallery as $attachment_id) {
                $this->addon->taxonomy->assign_to_folder($attachment_id, $folder_id);
            }
        }
        
        // ACF gallery field
        if (function_exists('get_field')) {
            $acf_gallery = get_field('gallery', $post_id);
            if (!empty($acf_gallery) && is_array($acf_gallery)) {
                foreach ($acf_gallery as $image) {
                    $id = is_array($image) ? $image['ID'] : $image;
                    $this->addon->taxonomy->assign_to_folder($id, $folder_id);
                }
            }
        }
    }
    
    /**
     * Handle upload - store context for later assignment
     */
    public function handle_upload($upload, $context) {
        // Check if upload is from Ensemble context
        if (!empty($_POST['ensemble_context'])) {
            $context_data = json_decode(stripslashes($_POST['ensemble_context']), true);
            
            if (!empty($context_data)) {
                // Store context in a transient for the add_attachment hook
                set_transient('es_upload_context_' . md5($upload['file']), $context_data, 60);
            }
        }
        
        return $upload;
    }
    
    /**
     * Assign uploaded media to folder
     */
    public function assign_uploaded_media($attachment_id) {
        if (!$this->addon->get_setting('auto_assign_upload', true)) {
            return;
        }
        
        // Get the file path to find the context
        $file = get_attached_file($attachment_id);
        $context = get_transient('es_upload_context_' . md5($file));
        
        if (!$context) {
            // Try alternative: check POST data directly
            if (!empty($_POST['ensemble_context'])) {
                $context = json_decode(stripslashes($_POST['ensemble_context']), true);
            }
        }
        
        if (empty($context['post_id']) || empty($context['post_type'])) {
            return;
        }
        
        // Get folder for the post
        $folder_id = get_post_meta($context['post_id'], '_es_media_folder_id', true);
        
        if (!$folder_id) {
            // Try to create folder if it doesn't exist
            $post = get_post($context['post_id']);
            if ($post && isset($this->type_map[$post->post_type])) {
                $type = $this->type_map[$post->post_type];
                $this->create_folder_for_post($post->ID, $post, $type);
                $folder_id = get_post_meta($context['post_id'], '_es_media_folder_id', true);
            }
        }
        
        if ($folder_id) {
            $this->addon->taxonomy->assign_to_folder($attachment_id, $folder_id);
        }
        
        // Cleanup transient
        delete_transient('es_upload_context_' . md5($file));
    }
    
    /**
     * Handle featured image assignment
     */
    public function handle_thumbnail_set($post_id, $thumbnail_id, $previous_thumbnail_id) {
        if (!$this->addon->get_setting('auto_assign_upload', true)) {
            return;
        }
        
        $post = get_post($post_id);
        
        if (!$post || !isset($this->type_map[$post->post_type])) {
            return;
        }
        
        $folder_id = get_post_meta($post_id, '_es_media_folder_id', true);
        
        if ($folder_id && $thumbnail_id) {
            $this->addon->taxonomy->assign_to_folder($thumbnail_id, $folder_id);
        }
    }
    
    /**
     * Handle Wizard media save
     */
    public function handle_wizard_media_save($post_id, $field_key, $attachment_ids) {
        if (!$this->addon->get_setting('auto_assign_upload', true)) {
            return;
        }
        
        $folder_id = get_post_meta($post_id, '_es_media_folder_id', true);
        
        if (!$folder_id) {
            return;
        }
        
        if (!is_array($attachment_ids)) {
            $attachment_ids = array($attachment_ids);
        }
        
        foreach ($attachment_ids as $id) {
            if ($id) {
                $this->addon->taxonomy->assign_to_folder($id, $folder_id);
            }
        }
    }
    
    /**
     * Handle post deletion
     */
    public function handle_post_delete($post_id) {
        if (!$this->addon->get_setting('delete_folder_on_post_delete', false)) {
            return;
        }
        
        $post = get_post($post_id);
        
        if (!$post || !isset($this->type_map[$post->post_type])) {
            return;
        }
        
        $folder_id = get_post_meta($post_id, '_es_media_folder_id', true);
        
        if ($folder_id) {
            // Delete the folder (media will be moved to uncategorized)
            $this->addon->taxonomy->delete_folder($folder_id);
        }
    }
    
    /**
     * Bulk organize existing media
     */
    public function bulk_organize($type) {
        $post_type_map = array(
            'events'    => 'es_event',
            'artists'   => 'es_artist',
            'locations' => 'es_location',
        );
        
        if (!isset($post_type_map[$type])) {
            return new WP_Error('invalid_type', __('Invalid type.', 'ensemble'));
        }
        
        $post_type = $post_type_map[$type];
        
        // Get all posts of this type
        $posts = get_posts(array(
            'post_type'      => $post_type,
            'posts_per_page' => -1,
            'post_status'    => array('publish', 'draft', 'private'),
        ));
        
        $results = array(
            'folders_created' => 0,
            'media_assigned'  => 0,
            'posts_processed' => 0,
        );
        
        foreach ($posts as $post) {
            $results['posts_processed']++;
            
            // Create folder if doesn't exist
            $folder_id = get_post_meta($post->ID, '_es_media_folder_id', true);
            
            if (!$folder_id) {
                $parent_id = $this->addon->taxonomy->get_parent_folder_id($type);
                $color = $this->addon->get_setting("color_{$type}", '#3582c4');
                
                $folder_id = $this->addon->taxonomy->create_folder($post->post_title, $parent_id, array(
                    '_folder_color'   => $color,
                    '_folder_post_id' => $post->ID,
                    '_folder_type'    => 'auto',
                ));
                
                if (!is_wp_error($folder_id)) {
                    update_post_meta($post->ID, '_es_media_folder_id', $folder_id);
                    $results['folders_created']++;
                }
            }
            
            if ($folder_id && !is_wp_error($folder_id)) {
                // Assign all media
                $assigned = $this->bulk_assign_post_media($post->ID, $folder_id);
                $results['media_assigned'] += $assigned;
            }
        }
        
        $results['message'] = sprintf(
            __('%d posts processed, %d folders created, %d media files assigned.', 'ensemble'),
            $results['posts_processed'],
            $results['folders_created'],
            $results['media_assigned']
        );
        
        return $results;
    }
    
    /**
     * Bulk assign all media from a post to folder
     */
    private function bulk_assign_post_media($post_id, $folder_id) {
        $assigned = 0;
        
        // Featured image
        $thumbnail_id = get_post_thumbnail_id($post_id);
        if ($thumbnail_id) {
            $this->addon->taxonomy->assign_to_folder($thumbnail_id, $folder_id);
            $assigned++;
        }
        
        // Ensemble gallery
        $gallery = get_post_meta($post_id, '_ensemble_gallery', true);
        if (!empty($gallery) && is_array($gallery)) {
            foreach ($gallery as $attachment_id) {
                $this->addon->taxonomy->assign_to_folder($attachment_id, $folder_id);
                $assigned++;
            }
        }
        
        // ACF gallery
        if (function_exists('get_field')) {
            $acf_gallery = get_field('gallery', $post_id);
            if (!empty($acf_gallery) && is_array($acf_gallery)) {
                foreach ($acf_gallery as $image) {
                    $id = is_array($image) ? $image['ID'] : $image;
                    $this->addon->taxonomy->assign_to_folder($id, $folder_id);
                    $assigned++;
                }
            }
        }
        
        // Look for attachments where post_parent is this post
        $attached = get_children(array(
            'post_parent'    => $post_id,
            'post_type'      => 'attachment',
            'posts_per_page' => -1,
        ));
        
        foreach ($attached as $attachment) {
            $this->addon->taxonomy->assign_to_folder($attachment->ID, $folder_id);
            $assigned++;
        }
        
        return $assigned;
    }
}
