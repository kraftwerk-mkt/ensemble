<?php
/**
 * Ensemble Media Folders - Taxonomy Handler
 * 
 * Registers and manages the es_media_folder taxonomy
 *
 * @package Ensemble
 * @subpackage Addons/MediaFolders
 * @since 2.7.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class ES_Folder_Taxonomy {
    
    /**
     * Parent addon instance
     * @var ES_Media_Folders_Addon
     */
    private $addon;
    
    /**
     * Taxonomy name
     * @var string
     */
    const TAXONOMY = 'es_media_folder';
    
    /**
     * Constructor
     */
    public function __construct($addon) {
        $this->addon = $addon;
        
        // Register taxonomy IMMEDIATELY - not via hook
        // Because this class is instantiated during 'init' hook (priority 6),
        // adding another 'init' action would be too late
        $this->register_taxonomy();
        
        $this->register_hooks();
    }
    
    /**
     * Register hooks
     */
    private function register_hooks() {
        // Setup parent folders on admin_init
        add_action('admin_init', array($this, 'setup_parent_folders'));
    }
    
    /**
     * Register the media folder taxonomy
     */
    public function register_taxonomy() {
        // Don't register twice
        if (taxonomy_exists(self::TAXONOMY)) {
            return;
        }
        $labels = array(
            'name'              => __('Media Folders', 'ensemble'),
            'singular_name'     => __('Media Folder', 'ensemble'),
            'search_items'      => __('Search Folders', 'ensemble'),
            'all_items'         => __('All Folders', 'ensemble'),
            'parent_item'       => __('Parent Folder', 'ensemble'),
            'parent_item_colon' => __('Parent Folder:', 'ensemble'),
            'edit_item'         => __('Edit Folder', 'ensemble'),
            'update_item'       => __('Update Folder', 'ensemble'),
            'add_new_item'      => __('Add New Folder', 'ensemble'),
            'new_item_name'     => __('New Folder Name', 'ensemble'),
            'menu_name'         => __('Folders', 'ensemble'),
        );
        
        $args = array(
            'labels'            => $labels,
            'hierarchical'      => true,
            'public'            => false,
            'show_ui'           => false, // We use our own UI
            'show_admin_column' => true,
            'show_in_nav_menus' => false,
            'show_tagcloud'     => false,
            'show_in_rest'      => true,
            'query_var'         => true,
            'rewrite'           => false,
        );
        
        register_taxonomy(self::TAXONOMY, 'attachment', $args);
    }
    
    /**
     * Setup parent folders (Events, Artists, Locations)
     */
    public function setup_parent_folders() {
        // Only run once or when needed
        if (get_option('es_media_folders_setup_done')) {
            return;
        }
        
        $parent_folders = array(
            'events' => array(
                'name'  => __('Events', 'ensemble'),
                'slug'  => 'es-events',
                'icon'  => 'dashicons-calendar-alt',
                'color' => $this->addon->get_setting('color_events', '#3582c4'),
                'order' => 1,
            ),
            'artists' => array(
                'name'  => __('Artists', 'ensemble'),
                'slug'  => 'es-artists',
                'icon'  => 'dashicons-admin-users',
                'color' => $this->addon->get_setting('color_artists', '#9b59b6'),
                'order' => 2,
            ),
            'locations' => array(
                'name'  => __('Locations', 'ensemble'),
                'slug'  => 'es-locations',
                'icon'  => 'dashicons-location',
                'color' => $this->addon->get_setting('color_locations', '#27ae60'),
                'order' => 3,
            ),
        );
        
        foreach ($parent_folders as $type => $folder) {
            $this->create_parent_folder($folder);
        }
        
        update_option('es_media_folders_setup_done', true);
    }
    
    /**
     * Create a parent folder
     */
    private function create_parent_folder($folder) {
        $existing = term_exists($folder['slug'], self::TAXONOMY);
        
        if ($existing) {
            // Update meta if needed
            update_term_meta($existing['term_id'], '_folder_icon', $folder['icon']);
            update_term_meta($existing['term_id'], '_folder_color', $folder['color']);
            update_term_meta($existing['term_id'], '_folder_order', $folder['order']);
            update_term_meta($existing['term_id'], '_folder_type', 'parent');
            update_term_meta($existing['term_id'], '_folder_locked', true);
            return $existing['term_id'];
        }
        
        $result = wp_insert_term($folder['name'], self::TAXONOMY, array(
            'slug' => $folder['slug'],
        ));
        
        if (!is_wp_error($result)) {
            update_term_meta($result['term_id'], '_folder_icon', $folder['icon']);
            update_term_meta($result['term_id'], '_folder_color', $folder['color']);
            update_term_meta($result['term_id'], '_folder_order', $folder['order']);
            update_term_meta($result['term_id'], '_folder_type', 'parent');
            update_term_meta($result['term_id'], '_folder_locked', true);
            return $result['term_id'];
        }
        
        return false;
    }
    
    /**
     * Get parent folder ID by type
     */
    public function get_parent_folder_id($type) {
        $slug = 'es-' . $type;
        $term = get_term_by('slug', $slug, self::TAXONOMY);
        
        if ($term) {
            return $term->term_id;
        }
        
        // Create if doesn't exist
        $folders = array(
            'events' => array(
                'name'  => __('Events', 'ensemble'),
                'slug'  => 'es-events',
                'icon'  => 'dashicons-calendar-alt',
                'color' => $this->addon->get_setting('color_events', '#3582c4'),
                'order' => 1,
            ),
            'artists' => array(
                'name'  => __('Artists', 'ensemble'),
                'slug'  => 'es-artists',
                'icon'  => 'dashicons-admin-users',
                'color' => $this->addon->get_setting('color_artists', '#9b59b6'),
                'order' => 2,
            ),
            'locations' => array(
                'name'  => __('Locations', 'ensemble'),
                'slug'  => 'es-locations',
                'icon'  => 'dashicons-location',
                'color' => $this->addon->get_setting('color_locations', '#27ae60'),
                'order' => 3,
            ),
        );
        
        if (isset($folders[$type])) {
            return $this->create_parent_folder($folders[$type]);
        }
        
        return false;
    }
    
    /**
     * Create a child folder
     */
    public function create_folder($name, $parent_id = 0, $meta = array()) {
        // Ensure taxonomy is registered
        if (!taxonomy_exists(self::TAXONOMY)) {
            $this->register_taxonomy();
        }
        
        $slug = sanitize_title($name);
        
        // Check if exists
        $existing = get_term_by('slug', $slug, self::TAXONOMY);
        if ($existing && $existing->parent == $parent_id) {
            return $existing->term_id;
        }
        
        $result = wp_insert_term($name, self::TAXONOMY, array(
            'parent' => $parent_id,
        ));
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        $term_id = $result['term_id'];
        
        // Set meta
        $defaults = array(
            '_folder_color' => '',
            '_folder_icon'  => 'dashicons-portfolio',
            '_folder_type'  => 'child',
        );
        
        $meta = wp_parse_args($meta, $defaults);
        
        foreach ($meta as $key => $value) {
            update_term_meta($term_id, $key, $value);
        }
        
        return $term_id;
    }
    
    /**
     * Delete a folder
     */
    public function delete_folder($term_id, $reassign_to = 0) {
        // Check if folder is locked
        $locked = get_term_meta($term_id, '_folder_locked', true);
        if ($locked) {
            return new WP_Error('folder_locked', __('This folder cannot be deleted.', 'ensemble'));
        }
        
        // Get all media in this folder
        $attachments = get_posts(array(
            'post_type'      => 'attachment',
            'posts_per_page' => -1,
            'tax_query'      => array(
                array(
                    'taxonomy' => self::TAXONOMY,
                    'field'    => 'term_id',
                    'terms'    => $term_id,
                ),
            ),
        ));
        
        // Reassign media
        foreach ($attachments as $attachment) {
            wp_remove_object_terms($attachment->ID, $term_id, self::TAXONOMY);
            
            if ($reassign_to > 0) {
                wp_set_object_terms($attachment->ID, $reassign_to, self::TAXONOMY, true);
            }
        }
        
        // Delete the term
        return wp_delete_term($term_id, self::TAXONOMY);
    }
    
    /**
     * Get folder tree
     */
    public function get_folder_tree($args = array()) {
        // Ensure taxonomy is registered
        if (!taxonomy_exists(self::TAXONOMY)) {
            $this->register_taxonomy();
        }
        
        $defaults = array(
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        );
        
        $args = wp_parse_args($args, $defaults);
        $args['taxonomy'] = self::TAXONOMY;
        
        $terms = get_terms($args);
        
        if (is_wp_error($terms) || empty($terms)) {
            return array();
        }
        
        // Build tree
        $tree = array();
        $lookup = array();
        
        foreach ($terms as $term) {
            $term->icon  = get_term_meta($term->term_id, '_folder_icon', true) ?: 'dashicons-portfolio';
            $term->color = get_term_meta($term->term_id, '_folder_color', true) ?: '';
            $term->type  = get_term_meta($term->term_id, '_folder_type', true) ?: 'child';
            $term->locked = (bool) get_term_meta($term->term_id, '_folder_locked', true);
            $term->children = array();
            
            $lookup[$term->term_id] = $term;
        }
        
        foreach ($lookup as $term) {
            if ($term->parent == 0) {
                $tree[] = $term;
            } else if (isset($lookup[$term->parent])) {
                $lookup[$term->parent]->children[] = $term;
            }
        }
        
        // Sort by order meta, fallback to name
        $sort_func = function($a, $b) {
            $order_a = get_term_meta($a->term_id, '_folder_order', true) ?: 99;
            $order_b = get_term_meta($b->term_id, '_folder_order', true) ?: 99;
            if ($order_a == $order_b) {
                return strcasecmp($a->name, $b->name);
            }
            return $order_a - $order_b;
        };
        
        usort($tree, $sort_func);
        
        // Also sort children recursively
        foreach ($lookup as $term) {
            if (!empty($term->children)) {
                usort($term->children, $sort_func);
            }
        }
        
        return $tree;
    }
    
    /**
     * Get folder counts
     */
    public function get_folder_counts() {
        global $wpdb;
        
        $taxonomy = self::TAXONOMY;
        
        $results = $wpdb->get_results("
            SELECT t.term_id, COUNT(tr.object_id) as count
            FROM {$wpdb->terms} t
            INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
            LEFT JOIN {$wpdb->term_relationships} tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
            WHERE tt.taxonomy = '{$taxonomy}'
            GROUP BY t.term_id
        ");
        
        $counts = array();
        foreach ($results as $row) {
            $counts[$row->term_id] = (int) $row->count;
        }
        
        // Count uncategorized
        $uncategorized = $wpdb->get_var("
            SELECT COUNT(*)
            FROM {$wpdb->posts} p
            WHERE p.post_type = 'attachment'
            AND p.ID NOT IN (
                SELECT tr.object_id
                FROM {$wpdb->term_relationships} tr
                INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                WHERE tt.taxonomy = '{$taxonomy}'
            )
        ");
        
        $counts['uncategorized'] = (int) $uncategorized;
        
        return $counts;
    }
    
    /**
     * Assign media to folder
     */
    public function assign_to_folder($attachment_id, $folder_id) {
        return wp_set_object_terms($attachment_id, array($folder_id), self::TAXONOMY);
    }
    
    /**
     * Remove media from folder
     */
    public function remove_from_folder($attachment_id, $folder_id = null) {
        if ($folder_id) {
            return wp_remove_object_terms($attachment_id, $folder_id, self::TAXONOMY);
        }
        
        return wp_set_object_terms($attachment_id, array(), self::TAXONOMY);
    }
    
    /**
     * Get folder for attachment
     */
    public function get_attachment_folder($attachment_id) {
        $terms = wp_get_object_terms($attachment_id, self::TAXONOMY);
        
        if (is_wp_error($terms) || empty($terms)) {
            return null;
        }
        
        return $terms[0];
    }
}
