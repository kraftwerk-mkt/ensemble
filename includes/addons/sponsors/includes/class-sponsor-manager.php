<?php
/**
 * Sponsor Manager
 * 
 * Handles sponsor CRUD operations
 *
 * @package Ensemble
 * @subpackage Addons/Sponsors
 */

if (!defined('ABSPATH')) {
    exit;
}

class ES_Sponsor_Manager {
    
    /**
     * Get all sponsors
     * 
     * @param array $args Query arguments
     * @return array
     */
    public function get_sponsors($args = array()) {
        $defaults = array(
            'post_type'      => 'ensemble_sponsor',
            'posts_per_page' => -1,
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
            'post_status'    => 'publish',
        );
        
        $args = wp_parse_args($args, $defaults);
        
        // Handle category filter
        if (!empty($args['category'])) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'ensemble_sponsor_category',
                    'field'    => 'slug',
                    'terms'    => $args['category'],
                ),
            );
            unset($args['category']);
        }
        
        // Handle category IDs filter
        if (!empty($args['category_ids'])) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'ensemble_sponsor_category',
                    'field'    => 'term_id',
                    'terms'    => $args['category_ids'],
                ),
            );
            unset($args['category_ids']);
        }
        
        // Handle active date filter
        $now = current_time('Y-m-d');
        $meta_query = array('relation' => 'AND');
        
        // Active from
        $meta_query[] = array(
            'relation' => 'OR',
            array(
                'key'     => '_sponsor_active_from',
                'compare' => 'NOT EXISTS',
            ),
            array(
                'key'     => '_sponsor_active_from',
                'value'   => '',
                'compare' => '=',
            ),
            array(
                'key'     => '_sponsor_active_from',
                'value'   => $now,
                'compare' => '<=',
                'type'    => 'DATE',
            ),
        );
        
        // Active until
        $meta_query[] = array(
            'relation' => 'OR',
            array(
                'key'     => '_sponsor_active_until',
                'compare' => 'NOT EXISTS',
            ),
            array(
                'key'     => '_sponsor_active_until',
                'value'   => '',
                'compare' => '=',
            ),
            array(
                'key'     => '_sponsor_active_until',
                'value'   => $now,
                'compare' => '>=',
                'type'    => 'DATE',
            ),
        );
        
        $args['meta_query'] = $meta_query;
        
        $posts = get_posts($args);
        
        $sponsors = array();
        foreach ($posts as $post) {
            $sponsors[] = $this->format_sponsor($post);
        }
        
        return $sponsors;
    }
    
    /**
     * Get global sponsors (not tied to specific events)
     * 
     * @return array
     */
    public function get_global_sponsors() {
        $args = array(
            'post_type'      => 'ensemble_sponsor',
            'posts_per_page' => -1,
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
            'post_status'    => 'publish',
            'meta_query'     => array(
                array(
                    'key'     => '_sponsor_is_global',
                    'value'   => '1',
                    'compare' => '=',
                ),
            ),
        );
        
        $posts = get_posts($args);
        
        $sponsors = array();
        foreach ($posts as $post) {
            $sponsors[] = $this->format_sponsor($post);
        }
        
        return $sponsors;
    }
    
    /**
     * Get sponsors for a specific event
     * 
     * @param int $event_id
     * @return array
     */
    public function get_sponsors_for_event($event_id) {
        $args = array(
            'post_type'      => 'ensemble_sponsor',
            'posts_per_page' => -1,
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
            'post_status'    => 'publish',
            'meta_query'     => array(
                array(
                    'key'     => '_sponsor_events',
                    'value'   => serialize(strval($event_id)),
                    'compare' => 'LIKE',
                ),
            ),
        );
        
        $posts = get_posts($args);
        
        $sponsors = array();
        foreach ($posts as $post) {
            $sponsors[] = $this->format_sponsor($post);
        }
        
        return $sponsors;
    }
    
    /**
     * Get single sponsor
     * 
     * @param int $sponsor_id
     * @return array|false
     */
    public function get_sponsor($sponsor_id) {
        $post = get_post($sponsor_id);
        if (!$post || $post->post_type !== 'ensemble_sponsor') {
            return false;
        }
        
        return $this->format_sponsor($post);
    }
    
    /**
     * Get main sponsor
     * Returns the sponsor marked as main sponsor (only one allowed)
     * 
     * @return array|false
     */
    public function get_main_sponsor() {
        $args = array(
            'post_type'      => 'ensemble_sponsor',
            'posts_per_page' => 1,
            'post_status'    => 'publish',
            'meta_query'     => array(
                array(
                    'key'     => '_sponsor_is_main',
                    'value'   => '1',
                    'compare' => '=',
                ),
            ),
        );
        
        // Apply date filters
        $now = current_time('Y-m-d');
        $args['meta_query']['relation'] = 'AND';
        
        $args['meta_query'][] = array(
            'relation' => 'OR',
            array(
                'key'     => '_sponsor_active_from',
                'compare' => 'NOT EXISTS',
            ),
            array(
                'key'     => '_sponsor_active_from',
                'value'   => '',
                'compare' => '=',
            ),
            array(
                'key'     => '_sponsor_active_from',
                'value'   => $now,
                'compare' => '<=',
                'type'    => 'DATE',
            ),
        );
        
        $args['meta_query'][] = array(
            'relation' => 'OR',
            array(
                'key'     => '_sponsor_active_until',
                'compare' => 'NOT EXISTS',
            ),
            array(
                'key'     => '_sponsor_active_until',
                'value'   => '',
                'compare' => '=',
            ),
            array(
                'key'     => '_sponsor_active_until',
                'value'   => $now,
                'compare' => '>=',
                'type'    => 'DATE',
            ),
        );
        
        $posts = get_posts($args);
        
        if (empty($posts)) {
            return false;
        }
        
        return $this->format_sponsor($posts[0]);
    }
    
    /**
     * Set main sponsor
     * Ensures only one sponsor is marked as main
     * 
     * @param int $sponsor_id
     * @return bool
     */
    public function set_main_sponsor($sponsor_id) {
        // First, remove main flag from all other sponsors
        $current_main = get_posts(array(
            'post_type'      => 'ensemble_sponsor',
            'posts_per_page' => -1,
            'meta_query'     => array(
                array(
                    'key'     => '_sponsor_is_main',
                    'value'   => '1',
                    'compare' => '=',
                ),
            ),
            'fields' => 'ids',
        ));
        
        foreach ($current_main as $id) {
            if ($id != $sponsor_id) {
                update_post_meta($id, '_sponsor_is_main', '0');
            }
        }
        
        // Set the new main sponsor
        return update_post_meta($sponsor_id, '_sponsor_is_main', '1');
    }
    
    /**
     * Format sponsor data
     * 
     * @param WP_Post $post
     * @return array
     */
    private function format_sponsor($post) {
        // Get logo
        $logo_id = get_post_meta($post->ID, '_sponsor_logo_id', true);
        $logo_url = '';
        $logo_url_full = '';
        if ($logo_id) {
            $logo_url = wp_get_attachment_image_url($logo_id, 'medium');
            $logo_url_full = wp_get_attachment_image_url($logo_id, 'full');
        }
        
        // Get categories
        $categories = wp_get_post_terms($post->ID, 'ensemble_sponsor_category');
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
        
        // Get linked events
        $event_ids = get_post_meta($post->ID, '_sponsor_events', true);
        if (!is_array($event_ids)) {
            $event_ids = array();
        }
        
        $events = array();
        foreach ($event_ids as $event_id) {
            $event_post = get_post($event_id);
            if ($event_post) {
                $events[] = array(
                    'id'    => $event_post->ID,
                    'title' => $event_post->post_title,
                );
            }
        }
        
        return array(
            'id'             => $post->ID,
            'name'           => $post->post_title,
            'description'    => get_post_meta($post->ID, '_sponsor_description', true),
            'website'        => get_post_meta($post->ID, '_sponsor_website', true),
            'logo_id'        => $logo_id,
            'logo_url'       => $logo_url,
            'logo_url_full'  => $logo_url_full,
            'categories'     => $category_data,
            'category'       => implode(', ', $category_names),
            'events'         => $events,
            'event_ids'      => $event_ids,
            'is_global'      => get_post_meta($post->ID, '_sponsor_is_global', true) === '1',
            'is_main'        => get_post_meta($post->ID, '_sponsor_is_main', true) === '1',
            'main_caption'   => get_post_meta($post->ID, '_sponsor_main_caption', true),
            'active_from'    => get_post_meta($post->ID, '_sponsor_active_from', true),
            'active_until'   => get_post_meta($post->ID, '_sponsor_active_until', true),
            'menu_order'     => $post->menu_order,
            'created'        => $post->post_date,
            'modified'       => $post->post_modified,
        );
    }
    
    /**
     * Save sponsor
     * 
     * @param array $data
     * @return int|WP_Error
     */
    public function save_sponsor($data) {
        $sponsor_id = isset($data['sponsor_id']) ? absint($data['sponsor_id']) : 0;
        $name = isset($data['name']) ? sanitize_text_field($data['name']) : '';
        
        if (empty($name)) {
            return new WP_Error('missing_name', __('Sponsor name is required', 'ensemble'));
        }
        
        // Prepare post data
        $post_data = array(
            'post_title'  => $name,
            'post_type'   => 'ensemble_sponsor',
            'post_status' => 'publish',
            'menu_order'  => isset($data['menu_order']) ? absint($data['menu_order']) : 0,
        );
        
        // Update or create
        if ($sponsor_id) {
            $post_data['ID'] = $sponsor_id;
            $result = wp_update_post($post_data);
        } else {
            $result = wp_insert_post($post_data);
        }
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        $sponsor_id = $result;
        
        // Save meta fields
        if (isset($data['website'])) {
            update_post_meta($sponsor_id, '_sponsor_website', esc_url_raw($data['website']));
        }
        
        if (isset($data['description'])) {
            update_post_meta($sponsor_id, '_sponsor_description', sanitize_textarea_field($data['description']));
        }
        
        if (isset($data['logo_id'])) {
            update_post_meta($sponsor_id, '_sponsor_logo_id', absint($data['logo_id']));
            // Also set as featured image
            if ($data['logo_id']) {
                set_post_thumbnail($sponsor_id, absint($data['logo_id']));
            }
        }
        
        if (isset($data['is_global'])) {
            update_post_meta($sponsor_id, '_sponsor_is_global', $data['is_global'] ? '1' : '0');
        }
        
        // Handle main sponsor (only one allowed)
        if (isset($data['is_main']) && $data['is_main']) {
            $this->set_main_sponsor($sponsor_id);
        } else if (isset($data['is_main'])) {
            update_post_meta($sponsor_id, '_sponsor_is_main', '0');
        }
        
        // Main sponsor caption
        if (isset($data['main_caption'])) {
            update_post_meta($sponsor_id, '_sponsor_main_caption', sanitize_text_field($data['main_caption']));
        }
        
        if (isset($data['events'])) {
            $event_ids = array_map('absint', array_filter((array) $data['events']));
            update_post_meta($sponsor_id, '_sponsor_events', $event_ids);
        }
        
        if (isset($data['active_from'])) {
            update_post_meta($sponsor_id, '_sponsor_active_from', sanitize_text_field($data['active_from']));
        }
        
        if (isset($data['active_until'])) {
            update_post_meta($sponsor_id, '_sponsor_active_until', sanitize_text_field($data['active_until']));
        }
        
        // Save categories
        if (isset($data['categories'])) {
            $category_ids = array_map('absint', array_filter((array) $data['categories']));
            wp_set_post_terms($sponsor_id, $category_ids, 'ensemble_sponsor_category');
        }
        
        return $sponsor_id;
    }
    
    /**
     * Delete sponsor
     * 
     * @param int $sponsor_id
     * @return bool
     */
    public function delete_sponsor($sponsor_id) {
        $result = wp_delete_post($sponsor_id, true);
        return !empty($result);
    }
    
    /**
     * Get sponsor count
     * 
     * @return int
     */
    public function get_sponsor_count() {
        $count = wp_count_posts('ensemble_sponsor');
        return isset($count->publish) ? $count->publish : 0;
    }
}
