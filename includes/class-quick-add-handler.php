<?php
/**
 * Ensemble Quick-Add AJAX Handler
 * Backend logic für Location & Artist Quick-Add
 * Version: 1.0
 */

if (!defined('ABSPATH')) exit;

class Ensemble_Quick_Add_Handler {
    
    public function __construct() {
        $this->register_hooks();
    }
    
    // ========================================
    // REGISTER AJAX HOOKS
    // ========================================
    
    private function register_hooks() {
        // Location Quick-Add
        add_action('wp_ajax_es_quick_add_location', array($this, 'ajax_add_location'));
        
        // Artist Quick-Add
        add_action('wp_ajax_es_quick_add_artist', array($this, 'ajax_add_artist'));
        
        // Artist Bulk Quick-Add
        add_action('wp_ajax_es_bulk_quick_add_artist', array($this, 'ajax_bulk_add_artist'));
        
        // Genre Quick-Add
        add_action('wp_ajax_es_quick_add_genre', array($this, 'ajax_add_genre'));
    }
    
    // ========================================
    // ADD LOCATION (AJAX)
    // ========================================
    
    public function ajax_add_location() {
        // Verify nonce
        check_ajax_referer('ensemble-wizard', 'nonce');
        
        // Check permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to create locations.', 'ensemble')
            ));
        }
        
        // Get and validate data
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
        $address = isset($_POST['address']) ? sanitize_text_field($_POST['address']) : '';
        
        if (empty($name)) {
            wp_send_json_error(array(
                'message' => __('Location name is required.', 'ensemble')
            ));
        }
        
        // Create location post
        $post_data = array(
            'post_title'   => $name,
            'post_content' => $description,
            'post_type'    => 'ensemble_location',
            'post_status'  => 'publish',
            'post_author'  => get_current_user_id(),
        );
        
        $location_id = wp_insert_post($post_data);
        
        if (is_wp_error($location_id)) {
            wp_send_json_error(array(
                'message' => __('Failed to create location.', 'ensemble') . ' ' . $location_id->get_error_message()
            ));
        }
        
        // Save address as meta if provided
        if (!empty($address)) {
            update_post_meta($location_id, 'location_address', $address);
        }
        
        // Save creation timestamp
        update_post_meta($location_id, '_es_created_via_quick_add', current_time('mysql'));
        
        // Return success
        wp_send_json_success(array(
            'id'          => $location_id,
            'name'        => $name,
            'description' => $description,
            'address'     => $address,
            'message'     => __('Location created successfully!', 'ensemble')
        ));
    }
    
    // ========================================
    // ADD ARTIST (AJAX)
    // ========================================
    
    public function ajax_add_artist() {
        // Verify nonce
        check_ajax_referer('ensemble-wizard', 'nonce');
        
        // Check permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to create artists.', 'ensemble')
            ));
        }
        
        // Get and validate data
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
        
        if (empty($name)) {
            wp_send_json_error(array(
                'message' => __('Artist name is required.', 'ensemble')
            ));
        }
        
        // Create artist post
        $post_data = array(
            'post_title'   => $name,
            'post_content' => $description,
            'post_type'    => 'ensemble_artist',
            'post_status'  => 'publish',
            'post_author'  => get_current_user_id(),
        );
        
        $artist_id = wp_insert_post($post_data);
        
        if (is_wp_error($artist_id)) {
            wp_send_json_error(array(
                'message' => __('Failed to create artist.', 'ensemble') . ' ' . $artist_id->get_error_message()
            ));
        }
        
        // Save creation timestamp
        update_post_meta($artist_id, '_es_created_via_quick_add', current_time('mysql'));
        
        // Return success
        wp_send_json_success(array(
            'id'          => $artist_id,
            'name'        => $name,
            'description' => $description,
            'message'     => __('Artist created successfully!', 'ensemble')
        ));
    }
    
    // ========================================
    // BULK ADD ARTIST (AJAX)
    // ========================================
    
    public function ajax_bulk_add_artist() {
        // Verify nonce
        check_ajax_referer('ensemble-wizard', 'nonce');
        
        // Check permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to create artists.', 'ensemble')
            ));
        }
        
        // Get and validate data
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $reference = isset($_POST['reference']) ? sanitize_textarea_field($_POST['reference']) : '';
        $social = isset($_POST['social']) ? esc_url_raw($_POST['social']) : '';
        $image_id = isset($_POST['image_id']) ? intval($_POST['image_id']) : 0;
        
        if (empty($name)) {
            wp_send_json_error(array(
                'message' => __('Artist name is required.', 'ensemble')
            ));
        }
        
        // Create artist post
        $post_data = array(
            'post_title'   => $name,
            'post_content' => '',
            'post_type'    => 'ensemble_artist',
            'post_status'  => 'publish',
            'post_author'  => get_current_user_id(),
        );
        
        $artist_id = wp_insert_post($post_data);
        
        if (is_wp_error($artist_id)) {
            wp_send_json_error(array(
                'message' => __('Failed to create artist.', 'ensemble') . ' ' . $artist_id->get_error_message()
            ));
        }
        
        // Set featured image
        if ($image_id > 0) {
            set_post_thumbnail($artist_id, $image_id);
        }
        
        // Save reference (quote)
        if (!empty($reference)) {
            if (function_exists('update_field')) {
                update_field('artist_references', $reference, $artist_id);
            } else {
                update_post_meta($artist_id, 'artist_references', $reference);
            }
        }
        
        // Save social media URL as array
        if (!empty($social)) {
            $social_links = array($social);
            if (function_exists('update_field')) {
                update_field('artist_social_links', $social_links, $artist_id);
            } else {
                update_post_meta($artist_id, 'artist_social_links', $social_links);
            }
        }
        
        // Save creation timestamp
        update_post_meta($artist_id, '_es_created_via_quick_add', current_time('mysql'));
        update_post_meta($artist_id, '_es_created_via_bulk_quick_add', current_time('mysql'));
        
        // Return success
        wp_send_json_success(array(
            'id'        => $artist_id,
            'name'      => $name,
            'reference' => $reference,
            'social'    => $social,
            'image_id'  => $image_id,
            'message'   => __('Artist created successfully!', 'ensemble')
        ));
    }
    
    // ========================================
    // ADD GENRE (AJAX)
    // ========================================
    
    public function ajax_add_genre() {
        // Verify nonce
        check_ajax_referer('ensemble-wizard', 'nonce');
        
        // Check permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to create genres.', 'ensemble')
            ));
        }
        
        // Get and validate data
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        
        if (empty($name)) {
            wp_send_json_error(array(
                'message' => __('Genre name is required.', 'ensemble')
            ));
        }
        
        // Check if genre already exists
        $existing = term_exists($name, 'ensemble_genre');
        if ($existing) {
            wp_send_json_error(array(
                'message' => __('A genre with this name already exists.', 'ensemble')
            ));
        }
        
        // Create genre term
        $result = wp_insert_term($name, 'ensemble_genre');
        
        if (is_wp_error($result)) {
            wp_send_json_error(array(
                'message' => $result->get_error_message()
            ));
        }
        
        $term_id = $result['term_id'];
        
        // Mark as created via quick-add
        update_term_meta($term_id, '_es_created_via_quick_add', current_time('mysql'));
        
        // Return success
        wp_send_json_success(array(
            'id'      => $term_id,
            'name'    => $name,
            'message' => __('Genre created successfully!', 'ensemble')
        ));
    }
    
    // ========================================
    // UTILITY: GET ALL LOCATIONS (FOR REFRESH)
    // ========================================
    
    public static function get_all_locations() {
        $locations = get_posts(array(
            'post_type'      => 'ensemble_location',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC'
        ));
        
        return $locations;
    }
    
    // ========================================
    // UTILITY: GET ALL ARTISTS (FOR REFRESH)
    // ========================================
    
    public static function get_all_artists() {
        $artists = get_posts(array(
            'post_type'      => 'ensemble_artist',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC'
        ));
        
        return $artists;
    }
}

// Initialize only after WordPress is fully loaded
if (is_admin()) {
    add_action('admin_init', function() {
        new Ensemble_Quick_Add_Handler();
    });
}