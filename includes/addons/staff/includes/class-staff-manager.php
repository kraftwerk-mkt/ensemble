<?php
/**
 * Staff Manager
 * 
 * Handles staff/contact CRUD operations
 *
 * @package Ensemble
 * @subpackage Addons/Staff
 * @since 2.7.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class ES_Staff_Manager {
    
    /**
     * Get all staff members
     * 
     * @param array $args Query arguments
     * @return array
     */
    public function get_all_staff($args = array()) {
        $defaults = array(
            'post_type'      => 'ensemble_staff',
            'posts_per_page' => -1,
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
            'post_status'    => 'publish',
        );
        
        $args = wp_parse_args($args, $defaults);
        
        // Handle department filter
        if (!empty($args['department'])) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'ensemble_department',
                    'field'    => is_numeric($args['department']) ? 'term_id' : 'slug',
                    'terms'    => $args['department'],
                ),
            );
            unset($args['department']);
        }
        
        // Handle search
        if (!empty($args['search'])) {
            $args['s'] = $args['search'];
            unset($args['search']);
        }
        
        $posts = get_posts($args);
        
        $staff = array();
        foreach ($posts as $post) {
            $staff[] = $this->format_staff($post);
        }
        
        return $staff;
    }
    
    /**
     * Get single staff member
     * 
     * @param int $staff_id
     * @return array|false
     */
    public function get_staff($staff_id) {
        $post = get_post($staff_id);
        
        if (!$post || $post->post_type !== 'ensemble_staff') {
            return false;
        }
        
        return $this->format_staff($post);
    }
    
    /**
     * Get staff by email
     * 
     * @param string $email
     * @return array|false
     */
    public function get_staff_by_email($email) {
        $args = array(
            'post_type'      => 'ensemble_staff',
            'posts_per_page' => 1,
            'post_status'    => 'publish',
            'meta_query'     => array(
                array(
                    'key'   => '_staff_email',
                    'value' => sanitize_email($email),
                ),
            ),
        );
        
        $posts = get_posts($args);
        
        if (empty($posts)) {
            return false;
        }
        
        return $this->format_staff($posts[0]);
    }
    
    /**
     * Get staff for event
     * 
     * @param int $event_id
     * @return array
     */
    public function get_staff_for_event($event_id) {
        $contact_ids = get_post_meta($event_id, '_es_event_contacts', true);
        
        if (empty($contact_ids) || !is_array($contact_ids)) {
            return array();
        }
        
        $staff = array();
        foreach ($contact_ids as $id) {
            $person = $this->get_staff($id);
            if ($person) {
                $staff[] = $person;
            }
        }
        
        return $staff;
    }
    
    /**
     * Get staff for location
     * 
     * @param int $location_id
     * @return array
     */
    public function get_staff_for_location($location_id) {
        $contact_ids = get_post_meta($location_id, '_es_location_contacts', true);
        
        if (empty($contact_ids) || !is_array($contact_ids)) {
            return array();
        }
        
        $staff = array();
        foreach ($contact_ids as $id) {
            $person = $this->get_staff($id);
            if ($person) {
                $staff[] = $person;
            }
        }
        
        return $staff;
    }
    
    /**
     * Format staff data
     * 
     * @param WP_Post $post
     * @return array
     */
    private function format_staff($post) {
        // Get departments
        $departments = wp_get_post_terms($post->ID, 'ensemble_department');
        $department_data = array();
        $department_names = array();
        
        if (!is_wp_error($departments) && !empty($departments)) {
            foreach ($departments as $dept) {
                $department_data[] = array(
                    'id'   => $dept->term_id,
                    'name' => $dept->name,
                    'slug' => $dept->slug,
                );
                $department_names[] = $dept->name;
            }
        }
        
        // Get phone numbers
        $phones = get_post_meta($post->ID, '_staff_phones', true);
        if (!is_array($phones)) {
            $phones = array();
        }
        
        // Get abstract settings
        $abstract_types = get_post_meta($post->ID, '_staff_abstract_types', true);
        if (!is_array($abstract_types) || empty($abstract_types)) {
            $abstract_types = array('pdf');
        }
        
        // Featured image
        $featured_image_id = get_post_thumbnail_id($post->ID);
        $featured_image = '';
        $featured_image_full = '';
        
        if ($featured_image_id) {
            $featured_image = wp_get_attachment_image_url($featured_image_id, 'medium');
            $featured_image_full = wp_get_attachment_image_url($featured_image_id, 'full');
        }
        
        return array(
            'id'                  => $post->ID,
            'name'                => $post->post_title,
            'description'         => $post->post_content,
            'excerpt'             => wp_trim_words($post->post_content, 30),
            'position'            => get_post_meta($post->ID, '_staff_position', true),
            'email'               => get_post_meta($post->ID, '_staff_email', true),
            'phones'              => $phones,
            'phone'               => !empty($phones) ? $phones[0]['number'] : '', // Primary phone for convenience
            'office_hours'        => get_post_meta($post->ID, '_staff_office_hours', true),
            'responsibility'      => get_post_meta($post->ID, '_staff_responsibility', true),
            'departments'         => $department_data,
            'department'          => implode(', ', $department_names),
            'featured_image_id'   => $featured_image_id,
            'featured_image'      => $featured_image,
            'featured_image_full' => $featured_image_full,
            'menu_order'          => $post->menu_order,
            // Abstract upload settings
            'abstract_enabled'    => get_post_meta($post->ID, '_staff_abstract_enabled', true) === '1',
            'abstract_types'      => $abstract_types,
            'abstract_max_size'   => absint(get_post_meta($post->ID, '_staff_abstract_max_size', true)) ?: 10,
            // Social/Web
            'website'             => get_post_meta($post->ID, '_staff_website', true),
            'linkedin'            => get_post_meta($post->ID, '_staff_linkedin', true),
            'twitter'             => get_post_meta($post->ID, '_staff_twitter', true),
            // Meta
            'permalink'           => get_permalink($post->ID),
            'created'             => $post->post_date,
            'modified'            => $post->post_modified,
        );
    }
    
    /**
     * Save staff member
     * 
     * @param array $data
     * @return int|WP_Error
     */
    public function save_staff($data) {
        $staff_id = isset($data['staff_id']) ? absint($data['staff_id']) : 0;
        $name = isset($data['name']) ? sanitize_text_field($data['name']) : '';
        
        if (empty($name)) {
            return new WP_Error('missing_name', __('Name is required', 'ensemble'));
        }
        
        // Prepare post data
        $post_data = array(
            'post_title'   => $name,
            'post_content' => isset($data['description']) ? wp_kses_post($data['description']) : '',
            'post_type'    => 'ensemble_staff',
            'post_status'  => 'publish',
            'menu_order'   => isset($data['menu_order']) ? absint($data['menu_order']) : 0,
        );
        
        // Update or create
        if ($staff_id) {
            $post_data['ID'] = $staff_id;
            $result = wp_update_post($post_data);
        } else {
            $result = wp_insert_post($post_data);
        }
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        $staff_id = $result;
        
        // Save meta fields
        if (isset($data['position'])) {
            update_post_meta($staff_id, '_staff_position', sanitize_text_field($data['position']));
        }
        
        if (isset($data['email'])) {
            update_post_meta($staff_id, '_staff_email', sanitize_email($data['email']));
        }
        
        if (isset($data['phone']) && is_array($data['phone'])) {
            update_post_meta($staff_id, '_staff_phones', $data['phone']);
        }
        
        if (isset($data['office_hours'])) {
            update_post_meta($staff_id, '_staff_office_hours', sanitize_text_field($data['office_hours']));
        }
        
        if (isset($data['responsibility'])) {
            update_post_meta($staff_id, '_staff_responsibility', sanitize_text_field($data['responsibility']));
        }
        
        // Abstract upload settings
        if (isset($data['abstract_enabled'])) {
            update_post_meta($staff_id, '_staff_abstract_enabled', $data['abstract_enabled'] ? '1' : '0');
        }
        
        if (isset($data['abstract_types']) && is_array($data['abstract_types'])) {
            update_post_meta($staff_id, '_staff_abstract_types', $data['abstract_types']);
        }
        
        if (isset($data['abstract_max_size'])) {
            update_post_meta($staff_id, '_staff_abstract_max_size', absint($data['abstract_max_size']));
        }
        
        // Social/Web links
        if (isset($data['website'])) {
            update_post_meta($staff_id, '_staff_website', esc_url_raw($data['website']));
        }
        
        if (isset($data['linkedin'])) {
            update_post_meta($staff_id, '_staff_linkedin', esc_url_raw($data['linkedin']));
        }
        
        if (isset($data['twitter'])) {
            update_post_meta($staff_id, '_staff_twitter', esc_url_raw($data['twitter']));
        }
        
        // Set featured image
        if (isset($data['featured_image_id']) && !empty($data['featured_image_id'])) {
            set_post_thumbnail($staff_id, absint($data['featured_image_id']));
        } elseif (isset($data['featured_image_id']) && $data['featured_image_id'] === 0) {
            delete_post_thumbnail($staff_id);
        }
        
        // Save departments
        if (isset($data['departments'])) {
            $department_ids = array_map('absint', array_filter((array) $data['departments']));
            wp_set_post_terms($staff_id, $department_ids, 'ensemble_department');
        }
        
        // Fire action for extensions
        do_action('ensemble_staff_saved', $staff_id, $data);
        
        return $staff_id;
    }
    
    /**
     * Delete staff member
     * 
     * @param int $staff_id
     * @return bool
     */
    public function delete_staff($staff_id) {
        // Remove from events
        $this->remove_staff_from_posts($staff_id, '_es_event_contacts');
        
        // Remove from locations
        $this->remove_staff_from_posts($staff_id, '_es_location_contacts');
        
        $result = wp_delete_post($staff_id, true);
        
        return !empty($result);
    }
    
    /**
     * Remove staff from posts meta
     * 
     * @param int $staff_id
     * @param string $meta_key
     */
    private function remove_staff_from_posts($staff_id, $meta_key) {
        global $wpdb;
        
        // Find all posts with this staff member
        $posts = $wpdb->get_col($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
             WHERE meta_key = %s AND meta_value LIKE %s",
            $meta_key,
            '%' . $wpdb->esc_like(serialize(strval($staff_id))) . '%'
        ));
        
        foreach ($posts as $post_id) {
            $contacts = get_post_meta($post_id, $meta_key, true);
            if (is_array($contacts)) {
                $contacts = array_filter($contacts, function($id) use ($staff_id) {
                    return absint($id) !== absint($staff_id);
                });
                update_post_meta($post_id, $meta_key, array_values($contacts));
            }
        }
    }
    
    /**
     * Bulk delete staff
     * 
     * @param array $staff_ids
     * @return array
     */
    public function bulk_delete($staff_ids) {
        $deleted = 0;
        $failed = 0;
        
        foreach ($staff_ids as $id) {
            if ($this->delete_staff($id)) {
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
    
    /**
     * Get all departments
     * 
     * @return array
     */
    public function get_departments() {
        $terms = get_terms(array(
            'taxonomy'   => 'ensemble_department',
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ));
        
        if (is_wp_error($terms)) {
            return array();
        }
        
        $departments = array();
        foreach ($terms as $term) {
            $departments[] = array(
                'id'    => $term->term_id,
                'name'  => $term->name,
                'slug'  => $term->slug,
                'count' => $term->count,
            );
        }
        
        return $departments;
    }
    
    /**
     * Get staff count
     * 
     * @return int
     */
    public function get_staff_count() {
        $count = wp_count_posts('ensemble_staff');
        return isset($count->publish) ? $count->publish : 0;
    }
    
    /**
     * Get staff count by department
     * 
     * @param int|string $department Term ID or slug
     * @return int
     */
    public function get_staff_count_by_department($department) {
        $args = array(
            'post_type'      => 'ensemble_staff',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'fields'         => 'ids',
            'tax_query'      => array(
                array(
                    'taxonomy' => 'ensemble_department',
                    'field'    => is_numeric($department) ? 'term_id' : 'slug',
                    'terms'    => $department,
                ),
            ),
        );
        
        return count(get_posts($args));
    }
}
