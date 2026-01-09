<?php
/**
 * Dynamic Post Type Labels
 * 
 * Makes WordPress admin labels dynamic based on Label System settings.
 * Include this file in ensemble.php or add the filter directly.
 *
 * @package Ensemble
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Filter post type arguments to use dynamic labels
 * 
 * This makes the WordPress admin sidebar, edit screens, and all
 * post type related UI use the labels from the Label System.
 */
add_filter('register_post_type_args', 'ensemble_dynamic_post_type_labels', 10, 2);

function ensemble_dynamic_post_type_labels($args, $post_type) {
    
    // Only process Ensemble post types
    $ensemble_post_types = [
        'es_artist'        => 'artist',
        'ensemble_artist'  => 'artist',
        'es_location'      => 'location',
        'ensemble_location'=> 'location',
        'es_gallery'       => 'gallery',
        'ensemble_gallery' => 'gallery',
    ];
    
    if (!isset($ensemble_post_types[$post_type])) {
        return $args;
    }
    
    // Check if Label System is available
    if (!class_exists('ES_Label_System')) {
        return $args;
    }
    
    $entity_type = $ensemble_post_types[$post_type];
    
    // Get dynamic labels
    $args['labels'] = ES_Label_System::get_post_type_labels($entity_type);
    $args['label']  = ES_Label_System::get_label($entity_type, true);
    
    // Update menu icon for artist based on usage type
    if ($entity_type === 'artist') {
        $usage_type = ES_Label_System::get_usage_type();
        
        $icons = [
            'kongress'  => 'dashicons-businessman',
            'education' => 'dashicons-welcome-learn-more',
            'fitness'   => 'dashicons-heart',
            'sports'    => 'dashicons-awards',
            'church'    => 'dashicons-admin-home',
            'theater'   => 'dashicons-tickets-alt',
            'museum'    => 'dashicons-art',
            'clubs'     => 'dashicons-microphone',
        ];
        
        if (isset($icons[$usage_type])) {
            $args['menu_icon'] = $icons[$usage_type];
        }
    }
    
    return $args;
}

/**
 * Filter taxonomy labels (for ensemble_category, ensemble_genre, etc.)
 */
add_filter('register_taxonomy_args', 'ensemble_dynamic_taxonomy_labels', 10, 2);

function ensemble_dynamic_taxonomy_labels($args, $taxonomy) {
    
    // Event Categories - could be "Session Types" for kongress
    if ($taxonomy === 'ensemble_category' || $taxonomy === 'es_category') {
        if (class_exists('ES_Label_System')) {
            $event_label = ES_Label_System::get_label('event', false);
            
            $args['labels'] = [
                'name'              => sprintf(__('%s Categories', 'ensemble'), $event_label),
                'singular_name'     => sprintf(__('%s Category', 'ensemble'), $event_label),
                'search_items'      => __('Search Categories', 'ensemble'),
                'all_items'         => __('All Categories', 'ensemble'),
                'edit_item'         => __('Edit Category', 'ensemble'),
                'update_item'       => __('Update Category', 'ensemble'),
                'add_new_item'      => __('Add New Category', 'ensemble'),
                'new_item_name'     => __('New Category Name', 'ensemble'),
                'menu_name'         => __('Categories', 'ensemble'),
            ];
        }
    }
    
    // Artist Genres - could be "Expertise" for kongress
    if ($taxonomy === 'ensemble_genre' || $taxonomy === 'es_genre') {
        if (class_exists('ES_Label_System')) {
            $artist_label = ES_Label_System::get_label('artist', false);
            $usage_type = ES_Label_System::get_usage_type();
            
            // Different taxonomy name based on context
            $genre_name = __('Genres', 'ensemble');
            $genre_singular = __('Genre', 'ensemble');
            
            if ($usage_type === 'kongress') {
                $genre_name = __('Expertise', 'ensemble');
                $genre_singular = __('Expertise', 'ensemble');
            } elseif ($usage_type === 'education') {
                $genre_name = __('Subjects', 'ensemble');
                $genre_singular = __('Subject', 'ensemble');
            } elseif ($usage_type === 'fitness') {
                $genre_name = __('Specializations', 'ensemble');
                $genre_singular = __('Specialization', 'ensemble');
            }
            
            $args['labels'] = [
                'name'              => $genre_name,
                'singular_name'     => $genre_singular,
                'search_items'      => sprintf(__('Search %s', 'ensemble'), $genre_name),
                'all_items'         => sprintf(__('All %s', 'ensemble'), $genre_name),
                'edit_item'         => sprintf(__('Edit %s', 'ensemble'), $genre_singular),
                'update_item'       => sprintf(__('Update %s', 'ensemble'), $genre_singular),
                'add_new_item'      => sprintf(__('Add New %s', 'ensemble'), $genre_singular),
                'new_item_name'     => sprintf(__('New %s Name', 'ensemble'), $genre_singular),
                'menu_name'         => $genre_name,
            ];
        }
    }
    
    return $args;
}

/**
 * Update admin menu labels after registration
 * 
 * This catches any menu items that weren't updated by the post type filter
 */
add_action('admin_menu', 'ensemble_update_admin_menu_labels', 999);

function ensemble_update_admin_menu_labels() {
    global $menu, $submenu;
    
    if (!class_exists('ES_Label_System')) {
        return;
    }
    
    $artist_plural = ES_Label_System::get_label('artist', true);
    $location_plural = ES_Label_System::get_label('location', true);
    
    // Update main menu items if they exist as separate entries
    if (is_array($menu)) {
        foreach ($menu as $key => $item) {
            if (isset($item[2])) {
                if ($item[2] === 'edit.php?post_type=es_artist' || $item[2] === 'edit.php?post_type=ensemble_artist') {
                    $menu[$key][0] = $artist_plural;
                }
                if ($item[2] === 'edit.php?post_type=es_location' || $item[2] === 'edit.php?post_type=ensemble_location') {
                    $menu[$key][0] = $location_plural;
                }
            }
        }
    }
}
