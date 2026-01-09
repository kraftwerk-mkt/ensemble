<?php
/**
 * Post Types Registration
 * 
 * Registers custom post types and taxonomies for Ensemble
 *
 * @package Ensemble
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class ES_Post_Types {
    
    /**
     * Register custom post types
     */
    public function register_post_types() {
        // Get dynamic labels
        $artist_singular = ES_Label_System::get_label('artist', false);
        $artist_plural = ES_Label_System::get_label('artist', true);
        
        // Artists
        register_post_type('ensemble_artist', array(
            'labels' => array(
                'name'               => $artist_plural,
                'singular_name'      => $artist_singular,
                'add_new'            => sprintf(__('Add New %s', 'ensemble'), $artist_singular),
                'add_new_item'       => sprintf(__('Add New %s', 'ensemble'), $artist_singular),
                'edit_item'          => sprintf(__('Edit %s', 'ensemble'), $artist_singular),
                'new_item'           => sprintf(__('New %s', 'ensemble'), $artist_singular),
                'view_item'          => sprintf(__('View %s', 'ensemble'), $artist_singular),
                'search_items'       => sprintf(__('Search %s', 'ensemble'), $artist_plural),
                'not_found'          => sprintf(__('No %s found', 'ensemble'), strtolower($artist_plural)),
                'not_found_in_trash' => sprintf(__('No %s found in trash', 'ensemble'), strtolower($artist_plural)),
            ),
            'public'             => true,
            'has_archive'        => true,
            'show_in_menu'       => false, // We'll add it to our custom menu
            'show_ui'            => true,
            'show_in_rest'       => true,
            'supports'           => array('title', 'editor', 'thumbnail', 'custom-fields'),
            'rewrite'            => array('slug' => sanitize_title($artist_plural)),
            'menu_icon'          => 'dashicons-star-filled',
        ));
        
        // Get location labels  
        $location_singular = ES_Label_System::get_label('location', false);
        $location_plural = ES_Label_System::get_label('location', true);
        
        // Locations
        register_post_type('ensemble_location', array(
            'labels' => array(
                'name'               => $location_plural,
                'singular_name'      => $location_singular,
                'add_new'            => sprintf(__('Add New %s', 'ensemble'), $location_singular),
                'add_new_item'       => sprintf(__('Add New %s', 'ensemble'), $location_singular),
                'edit_item'          => sprintf(__('Edit %s', 'ensemble'), $location_singular),
                'new_item'           => sprintf(__('New %s', 'ensemble'), $location_singular),
                'view_item'          => sprintf(__('View %s', 'ensemble'), $location_singular),
                'search_items'       => sprintf(__('Search %s', 'ensemble'), $location_plural),
                'not_found'          => sprintf(__('No %s found', 'ensemble'), strtolower($location_plural)),
                'not_found_in_trash' => sprintf(__('No %s found in trash', 'ensemble'), strtolower($location_plural)),
            ),
            'public'             => true,
            'has_archive'        => true,
            'show_in_menu'       => false, // We'll add it to our custom menu
            'show_ui'            => true,
            'show_in_rest'       => true,
            'supports'           => array('title', 'editor', 'thumbnail', 'custom-fields'),
            'rewrite'            => array('slug' => sanitize_title($location_plural)),
            'menu_icon'          => 'dashicons-location',
        ));
        
        // Galleries
        register_post_type('ensemble_gallery', array(
            'labels' => array(
                'name'               => __('Galleries', 'ensemble'),
                'singular_name'      => __('Gallery', 'ensemble'),
                'add_new'            => __('Add New Gallery', 'ensemble'),
                'add_new_item'       => __('Add New Gallery', 'ensemble'),
                'edit_item'          => __('Edit Gallery', 'ensemble'),
                'new_item'           => __('New Gallery', 'ensemble'),
                'view_item'          => __('View Gallery', 'ensemble'),
                'search_items'       => __('Search Galleries', 'ensemble'),
                'not_found'          => __('No galleries found', 'ensemble'),
                'not_found_in_trash' => __('No galleries found in trash', 'ensemble'),
            ),
            'public'             => true,
            'has_archive'        => true,
            'show_in_menu'       => false,
            'show_ui'            => true,
            'show_in_rest'       => true,
            'supports'           => array('title', 'editor', 'thumbnail', 'custom-fields'),
            'rewrite'            => array('slug' => 'galleries'),
            'menu_icon'          => 'dashicons-format-gallery',
        ));
    }
    
    /**
     * Register taxonomies
     */
    public function register_taxonomies() {
        // Event Categories - ✅ NOW DYNAMIC!
        // This will work for ANY post type selected in settings
        register_taxonomy('ensemble_category', array(ensemble_get_post_type()), array(
            'labels' => array(
                'name'              => __('Event Categories', 'ensemble'),
                'singular_name'     => __('Event Category', 'ensemble'),
                'search_items'      => __('Search Categories', 'ensemble'),
                'all_items'         => __('All Categories', 'ensemble'),
                'parent_item'       => __('Parent Category', 'ensemble'),
                'parent_item_colon' => __('Parent Category:', 'ensemble'),
                'edit_item'         => __('Edit Category', 'ensemble'),
                'update_item'       => __('Update Category', 'ensemble'),
                'add_new_item'      => __('Add New Category', 'ensemble'),
                'new_item_name'     => __('New Category Name', 'ensemble'),
                'menu_name'         => __('Event Categories', 'ensemble'),
            ),
            'hierarchical'      => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'rewrite'           => array('slug' => 'event-category'),
        ));
        
        // Artist Genres
        register_taxonomy('ensemble_genre', array('ensemble_artist'), array(
            'labels' => array(
                'name'              => __('Genres', 'ensemble'),
                'singular_name'     => __('Genre', 'ensemble'),
                'search_items'      => __('Search Genres', 'ensemble'),
                'all_items'         => __('All Genres', 'ensemble'),
                'parent_item'       => __('Parent Genre', 'ensemble'),
                'parent_item_colon' => __('Parent Genre:', 'ensemble'),
                'edit_item'         => __('Edit Genre', 'ensemble'),
                'update_item'       => __('Update Genre', 'ensemble'),
                'add_new_item'      => __('Add New Genre', 'ensemble'),
                'new_item_name'     => __('New Genre Name', 'ensemble'),
                'menu_name'         => __('Genres', 'ensemble'),
            ),
            'hierarchical'      => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'rewrite'           => array('slug' => 'genre'),
        ));
        
        // Artist Types/Categories (e.g., DJ, Band, Singer, Doctor, Priest)
        register_taxonomy('ensemble_artist_type', array('ensemble_artist'), array(
            'labels' => array(
                'name'              => __('Artist Types', 'ensemble'),
                'singular_name'     => __('Artist Type', 'ensemble'),
                'search_items'      => __('Search Artist Types', 'ensemble'),
                'all_items'         => __('All Artist Types', 'ensemble'),
                'parent_item'       => __('Parent Artist Type', 'ensemble'),
                'parent_item_colon' => __('Parent Artist Type:', 'ensemble'),
                'edit_item'         => __('Edit Artist Type', 'ensemble'),
                'update_item'       => __('Update Artist Type', 'ensemble'),
                'add_new_item'      => __('Add New Artist Type', 'ensemble'),
                'new_item_name'     => __('New Artist Type Name', 'ensemble'),
                'menu_name'         => __('Artist Types', 'ensemble'),
            ),
            'hierarchical'      => true,
            'show_ui'           => true,
            'show_in_menu'      => false, // Managed via Taxonomies page
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'rewrite'           => array('slug' => 'artist-type'),
        ));
        
        // Location Types
        register_taxonomy('ensemble_location_type', array('ensemble_location'), array(
            'labels' => array(
                'name'              => __('Location Types', 'ensemble'),
                'singular_name'     => __('Location Type', 'ensemble'),
                'search_items'      => __('Search Location Types', 'ensemble'),
                'all_items'         => __('All Location Types', 'ensemble'),
                'parent_item'       => __('Parent Location Type', 'ensemble'),
                'parent_item_colon' => __('Parent Location Type:', 'ensemble'),
                'edit_item'         => __('Edit Location Type', 'ensemble'),
                'update_item'       => __('Update Location Type', 'ensemble'),
                'add_new_item'      => __('Add New Location Type', 'ensemble'),
                'new_item_name'     => __('New Location Type Name', 'ensemble'),
                'menu_name'         => __('Location Types', 'ensemble'),
            ),
            'hierarchical'      => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'rewrite'           => array('slug' => 'location-type'),
        ));
        
        // Gallery Categories
        register_taxonomy('ensemble_gallery_category', array('ensemble_gallery'), array(
            'labels' => array(
                'name'              => __('Gallery Categories', 'ensemble'),
                'singular_name'     => __('Gallery Category', 'ensemble'),
                'search_items'      => __('Search Gallery Categories', 'ensemble'),
                'all_items'         => __('All Gallery Categories', 'ensemble'),
                'parent_item'       => __('Parent Category', 'ensemble'),
                'parent_item_colon' => __('Parent Category:', 'ensemble'),
                'edit_item'         => __('Edit Category', 'ensemble'),
                'update_item'       => __('Update Category', 'ensemble'),
                'add_new_item'      => __('Add New Category', 'ensemble'),
                'new_item_name'     => __('New Category Name', 'ensemble'),
                'menu_name'         => __('Gallery Categories', 'ensemble'),
            ),
            'hierarchical'      => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'rewrite'           => array('slug' => 'gallery-category'),
        ));
    }
}