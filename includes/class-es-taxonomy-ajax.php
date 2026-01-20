<?php
/**
 * Ensemble Taxonomy AJAX Handler
 * 
 * Handles AJAX requests for taxonomy management
 *
 * @package Ensemble
 */

class ES_Taxonomy_Ajax {
    
    /**
     * Initialize hooks
     */
    public static function init() {
        add_action('wp_ajax_ensemble_save_term', [__CLASS__, 'ajax_save_term']);
        add_action('wp_ajax_ensemble_delete_term', [__CLASS__, 'ajax_delete_term']);
    }
    
    /**
     * Handle save term AJAX request
     */
    public static function ajax_save_term() {
        // Verify nonce
        if (!check_ajax_referer('ensemble_taxonomy', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed.', 'ensemble')]);
            return;
        }
        
        // Verify permissions
        if (!current_user_can('manage_categories')) {
            wp_send_json_error(['message' => __('No permission.', 'ensemble')]);
            return;
        }
        
        // Get and sanitize data
        $term_id = isset($_POST['term_id']) ? intval($_POST['term_id']) : 0;
        $taxonomy = isset($_POST['taxonomy']) ? sanitize_text_field($_POST['taxonomy']) : '';
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $slug = isset($_POST['slug']) ? sanitize_title($_POST['slug']) : '';
        $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
        $color = isset($_POST['color']) ? sanitize_hex_color($_POST['color']) : '';
        
        // Validate
        if (empty($name)) {
            wp_send_json_error(['message' => __('Name is required.', 'ensemble')]);
            return;
        }
        
        if (!in_array($taxonomy, ['ensemble_category', 'ensemble_genre', 'ensemble_artist_type', 'ensemble_location_type', 'ensemble_department'])) {
            wp_send_json_error(['message' => __('Invalid taxonomy.', 'ensemble')]);
            return;
        }
        
        // Prepare args
        $args = [
            'name' => $name,
            'description' => $description,
        ];
        
        // Only set slug if provided
        if (!empty($slug)) {
            $args['slug'] = $slug;
        }
        
        // Update or insert
        if ($term_id > 0) {
            // Update existing term
            $result = wp_update_term($term_id, $taxonomy, $args);
            
            if (is_wp_error($result)) {
                wp_send_json_error(['message' => $result->get_error_message()]);
                return;
            }
            
            // Save color for categories
            if ($taxonomy === 'ensemble_category' && !empty($color)) {
                update_term_meta($result['term_id'], 'ensemble_category_color', $color);
            }
            
            wp_send_json_success([
                'message' => __('Term updated successfully.', 'ensemble'),
                'term_id' => $result['term_id']
            ]);
        } else {
            // Insert new term
            $result = wp_insert_term($name, $taxonomy, $args);
            
            if (is_wp_error($result)) {
                wp_send_json_error(['message' => $result->get_error_message()]);
                return;
            }
            
            // Save color for categories
            if ($taxonomy === 'ensemble_category' && !empty($color)) {
                update_term_meta($result['term_id'], 'ensemble_category_color', $color);
            }
            
            wp_send_json_success([
                'message' => __('Term created successfully.', 'ensemble'),
                'term_id' => $result['term_id']
            ]);
        }
    }
    
    /**
     * Handle delete term AJAX request
     */
    public static function ajax_delete_term() {
        // Verify nonce
        if (!check_ajax_referer('ensemble_taxonomy', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed.', 'ensemble')]);
            return;
        }
        
        // Verify permissions
        if (!current_user_can('manage_categories')) {
            wp_send_json_error(['message' => __('No permission.', 'ensemble')]);
            return;
        }
        
        // Get and sanitize data
        $term_id = isset($_POST['term_id']) ? intval($_POST['term_id']) : 0;
        $taxonomy = isset($_POST['taxonomy']) ? sanitize_text_field($_POST['taxonomy']) : '';
        
        // Validate
        if ($term_id <= 0) {
            wp_send_json_error(['message' => __('Invalid term ID.', 'ensemble')]);
            return;
        }
        
        if (!in_array($taxonomy, ['ensemble_category', 'ensemble_genre', 'ensemble_artist_type', 'ensemble_location_type', 'ensemble_department'])) {
            wp_send_json_error(['message' => __('Invalid taxonomy.', 'ensemble')]);
            return;
        }
        
        // Delete term
        $result = wp_delete_term($term_id, $taxonomy);
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
            return;
        }
        
        if ($result === false) {
            wp_send_json_error(['message' => __('Term could not be deleted.', 'ensemble')]);
            return;
        }
        
        wp_send_json_success([
            'message' => __('Term deleted successfully.', 'ensemble')
        ]);
    }
}

// Initialize
ES_Taxonomy_Ajax::init();
