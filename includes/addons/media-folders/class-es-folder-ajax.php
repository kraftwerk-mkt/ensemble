<?php
/**
 * Ensemble Media Folders - AJAX Handler
 * 
 * Handles all AJAX requests for folder operations
 *
 * @package Ensemble
 * @subpackage Addons/MediaFolders
 * @since 2.7.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class ES_Folder_Ajax {
    
    /**
     * Parent addon instance
     * @var ES_Media_Folders_Addon
     */
    private $addon;
    
    /**
     * Constructor
     */
    public function __construct($addon) {
        $this->addon = $addon;
        $this->register_hooks();
    }
    
    /**
     * Register AJAX hooks
     */
    private function register_hooks() {
        // Folder operations
        add_action('wp_ajax_es_create_folder', array($this, 'create_folder'));
        add_action('wp_ajax_es_rename_folder', array($this, 'rename_folder'));
        add_action('wp_ajax_es_delete_folder', array($this, 'delete_folder'));
        add_action('wp_ajax_es_move_folder', array($this, 'move_folder'));
        add_action('wp_ajax_es_reorder_folders', array($this, 'reorder_folders'));
        
        // Media operations
        add_action('wp_ajax_es_move_to_folder', array($this, 'move_to_folder'));
        add_action('wp_ajax_es_get_folder_tree', array($this, 'get_folder_tree'));
        add_action('wp_ajax_es_get_folder_counts', array($this, 'get_folder_counts'));
        
        // Bulk operations
        add_action('wp_ajax_es_bulk_organize_media', array($this, 'bulk_organize_media'));
    }
    
    /**
     * Verify AJAX request
     */
    private function verify_request($capability = 'upload_files') {
        if (!check_ajax_referer('es_media_folders', 'nonce', false)) {
            return new WP_Error('invalid_nonce', __('Security check failed.', 'ensemble'));
        }
        
        if (!current_user_can($capability)) {
            return new WP_Error('no_permission', __('You do not have permission to perform this action.', 'ensemble'));
        }
        
        return true;
    }
    
    /**
     * Create folder
     */
    public function create_folder() {
        $verify = $this->verify_request('upload_files');
        if (is_wp_error($verify)) {
            wp_send_json_error($verify->get_error_message());
        }
        
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $parent_id = isset($_POST['parent_id']) ? absint($_POST['parent_id']) : 0;
        $color = isset($_POST['color']) ? sanitize_hex_color($_POST['color']) : '';
        
        if (empty($name)) {
            wp_send_json_error(__('Folder name is required.', 'ensemble'));
        }
        
        // Ensure taxonomy exists
        if (!taxonomy_exists(ES_Folder_Taxonomy::TAXONOMY)) {
            $this->addon->taxonomy->register_taxonomy();
        }
        
        if (!taxonomy_exists(ES_Folder_Taxonomy::TAXONOMY)) {
            wp_send_json_error(__('Media folder taxonomy is not registered. Please deactivate and reactivate the plugin.', 'ensemble'));
        }
        
        $meta = array(
            '_folder_color' => $color,
        );
        
        $result = $this->addon->taxonomy->create_folder($name, $parent_id, $meta);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        $term = get_term($result, ES_Folder_Taxonomy::TAXONOMY);
        
        if (!$term || is_wp_error($term)) {
            wp_send_json_error(__('Folder was created but could not be retrieved.', 'ensemble'));
        }
        
        wp_send_json_success(array(
            'folder_id' => $result,
            'name'      => $term->name,
            'slug'      => $term->slug,
            'parent_id' => $parent_id,
            'color'     => $color,
            'message'   => __('Folder created successfully.', 'ensemble'),
        ));
    }
    
    /**
     * Rename folder
     */
    public function rename_folder() {
        $verify = $this->verify_request('upload_files');
        if (is_wp_error($verify)) {
            wp_send_json_error($verify->get_error_message());
        }
        
        $folder_id = isset($_POST['folder_id']) ? absint($_POST['folder_id']) : 0;
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        
        if (!$folder_id || empty($name)) {
            wp_send_json_error(__('Folder ID and name are required.', 'ensemble'));
        }
        
        // Check if locked
        $locked = get_term_meta($folder_id, '_folder_locked', true);
        if ($locked) {
            wp_send_json_error(__('This folder cannot be renamed.', 'ensemble'));
        }
        
        $result = wp_update_term($folder_id, ES_Folder_Taxonomy::TAXONOMY, array(
            'name' => $name,
        ));
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success(array(
            'folder_id' => $folder_id,
            'name'      => $name,
            'message'   => __('Folder renamed successfully.', 'ensemble'),
        ));
    }
    
    /**
     * Delete folder
     */
    public function delete_folder() {
        $verify = $this->verify_request('upload_files');
        if (is_wp_error($verify)) {
            wp_send_json_error($verify->get_error_message());
        }
        
        $folder_id = isset($_POST['folder_id']) ? absint($_POST['folder_id']) : 0;
        
        if (!$folder_id) {
            wp_send_json_error(__('Folder ID is required.', 'ensemble'));
        }
        
        $result = $this->addon->taxonomy->delete_folder($folder_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success(array(
            'message' => __('Folder deleted successfully.', 'ensemble'),
        ));
    }
    
    /**
     * Move folder (change parent)
     */
    public function move_folder() {
        $verify = $this->verify_request('upload_files');
        if (is_wp_error($verify)) {
            wp_send_json_error($verify->get_error_message());
        }
        
        $folder_id = isset($_POST['folder_id']) ? absint($_POST['folder_id']) : 0;
        $new_parent_id = isset($_POST['new_parent_id']) ? absint($_POST['new_parent_id']) : 0;
        
        if (!$folder_id) {
            wp_send_json_error(__('Folder ID is required.', 'ensemble'));
        }
        
        // Prevent moving folder to itself or its children
        if ($folder_id === $new_parent_id) {
            wp_send_json_error(__('Cannot move folder to itself.', 'ensemble'));
        }
        
        // Check if new parent is a child of the folder being moved
        if ($new_parent_id) {
            $parent = get_term($new_parent_id, ES_Folder_Taxonomy::TAXONOMY);
            while ($parent && $parent->parent) {
                if ($parent->parent === $folder_id) {
                    wp_send_json_error(__('Cannot move folder into its own subfolder.', 'ensemble'));
                }
                $parent = get_term($parent->parent, ES_Folder_Taxonomy::TAXONOMY);
            }
        }
        
        // Update the folder's parent
        $result = wp_update_term($folder_id, ES_Folder_Taxonomy::TAXONOMY, array(
            'parent' => $new_parent_id
        ));
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success(array(
            'message' => __('Folder moved successfully.', 'ensemble'),
            'folder_id' => $folder_id,
            'new_parent_id' => $new_parent_id,
        ));
    }
    
    /**
     * Reorder folders
     */
    public function reorder_folders() {
        $verify = $this->verify_request('upload_files');
        if (is_wp_error($verify)) {
            wp_send_json_error($verify->get_error_message());
        }
        
        $order = isset($_POST['order']) ? $_POST['order'] : array();
        
        if (!is_array($order)) {
            wp_send_json_error(__('Invalid order data.', 'ensemble'));
        }
        
        foreach ($order as $index => $folder_id) {
            update_term_meta(absint($folder_id), '_folder_order', $index);
        }
        
        wp_send_json_success(array(
            'message' => __('Folder order updated.', 'ensemble'),
        ));
    }
    
    /**
     * Move media to folder
     */
    public function move_to_folder() {
        $verify = $this->verify_request('upload_files');
        if (is_wp_error($verify)) {
            wp_send_json_error($verify->get_error_message());
        }
        
        $attachment_ids = isset($_POST['attachment_ids']) ? $_POST['attachment_ids'] : array();
        $folder_id = isset($_POST['folder_id']) ? absint($_POST['folder_id']) : 0;
        
        if (!is_array($attachment_ids)) {
            $attachment_ids = array($attachment_ids);
        }
        
        $attachment_ids = array_map('absint', $attachment_ids);
        $attachment_ids = array_filter($attachment_ids);
        
        if (empty($attachment_ids)) {
            wp_send_json_error(__('No attachments selected.', 'ensemble'));
        }
        
        $moved = 0;
        
        foreach ($attachment_ids as $id) {
            if ($folder_id === 0) {
                // Remove from all folders (uncategorized)
                $this->addon->taxonomy->remove_from_folder($id);
            } else {
                // Move to specific folder
                $result = $this->addon->taxonomy->assign_to_folder($id, $folder_id);
                if (!is_wp_error($result)) {
                    $moved++;
                }
            }
        }
        
        wp_send_json_success(array(
            'moved'   => $moved,
            'message' => sprintf(__('%d items moved.', 'ensemble'), $moved),
        ));
    }
    
    /**
     * Get folder tree
     */
    public function get_folder_tree() {
        $verify = $this->verify_request('upload_files');
        if (is_wp_error($verify)) {
            wp_send_json_error($verify->get_error_message());
        }
        
        // Ensure taxonomy is registered
        if (!taxonomy_exists(ES_Folder_Taxonomy::TAXONOMY)) {
            $this->addon->taxonomy->register_taxonomy();
        }
        
        $hide_empty = $this->addon->get_setting('hide_empty', false);
        
        $tree = $this->addon->taxonomy->get_folder_tree(array(
            'hide_empty' => $hide_empty,
        ));
        
        $counts = $this->addon->taxonomy->get_folder_counts();
        
        wp_send_json_success(array(
            'tree'   => $tree,
            'counts' => $counts,
        ));
    }
    
    /**
     * Get folder counts
     */
    public function get_folder_counts() {
        $verify = $this->verify_request('upload_files');
        if (is_wp_error($verify)) {
            wp_send_json_error($verify->get_error_message());
        }
        
        $counts = $this->addon->taxonomy->get_folder_counts();
        
        wp_send_json_success(array(
            'counts' => $counts,
        ));
    }
    
    /**
     * Bulk organize media
     */
    public function bulk_organize_media() {
        $verify = $this->verify_request('manage_options');
        if (is_wp_error($verify)) {
            wp_send_json_error($verify->get_error_message());
        }
        
        $type = isset($_POST['type']) ? sanitize_key($_POST['type']) : '';
        
        if (!in_array($type, array('events', 'artists', 'locations'))) {
            wp_send_json_error(__('Invalid type.', 'ensemble'));
        }
        
        $results = $this->addon->automation->bulk_organize($type);
        
        if (is_wp_error($results)) {
            wp_send_json_error($results->get_error_message());
        }
        
        wp_send_json_success($results);
    }
}
