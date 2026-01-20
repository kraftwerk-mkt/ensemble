<?php
/**
 * Abstract Manager
 * 
 * Handles abstract submissions as Custom Post Type with status tracking
 *
 * @package Ensemble
 * @subpackage Addons/Staff
 * @since 2.8.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class ES_Abstract_Manager {
    
    /**
     * Post type name
     */
    const POST_TYPE = 'ensemble_abstract';
    
    /**
     * Status constants
     */
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_IN_REVIEW = 'in_review';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_REJECTED = 'rejected';
    const STATUS_REVISION = 'revision_requested';
    
    /**
     * Initialize
     */
    public function __construct() {
        add_action('init', array($this, 'register_post_type'), 5);
        add_action('init', array($this, 'register_post_statuses'), 5);
    }
    
    /**
     * Register Abstract post type
     */
    public function register_post_type() {
        register_post_type(self::POST_TYPE, array(
            'labels' => array(
                'name'               => __('Abstracts', 'ensemble'),
                'singular_name'      => __('Abstract', 'ensemble'),
                'add_new'            => __('Add New', 'ensemble'),
                'add_new_item'       => __('Add New Abstract', 'ensemble'),
                'edit_item'          => __('Edit Abstract', 'ensemble'),
                'new_item'           => __('New Abstract', 'ensemble'),
                'view_item'          => __('View Abstract', 'ensemble'),
                'search_items'       => __('Search Abstracts', 'ensemble'),
                'not_found'          => __('No abstracts found', 'ensemble'),
                'not_found_in_trash' => __('No abstracts found in trash', 'ensemble'),
            ),
            'public'              => false,
            'publicly_queryable'  => false,
            'show_ui'             => true,
            'show_in_menu'        => false, // We'll add it under Staff menu
            'show_in_rest'        => true,
            'supports'            => array('title', 'editor'),
            'capability_type'     => 'post',
            'map_meta_cap'        => true,
        ));
    }
    
    /**
     * Register custom post statuses
     */
    public function register_post_statuses() {
        register_post_status(self::STATUS_SUBMITTED, array(
            'label'                     => __('Submitted', 'ensemble'),
            'public'                    => false,
            'internal'                  => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop('Submitted <span class="count">(%s)</span>', 'Submitted <span class="count">(%s)</span>', 'ensemble'),
        ));
        
        register_post_status(self::STATUS_IN_REVIEW, array(
            'label'                     => __('In Review', 'ensemble'),
            'public'                    => false,
            'internal'                  => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop('In Review <span class="count">(%s)</span>', 'In Review <span class="count">(%s)</span>', 'ensemble'),
        ));
        
        register_post_status(self::STATUS_ACCEPTED, array(
            'label'                     => __('Accepted', 'ensemble'),
            'public'                    => false,
            'internal'                  => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop('Accepted <span class="count">(%s)</span>', 'Accepted <span class="count">(%s)</span>', 'ensemble'),
        ));
        
        register_post_status(self::STATUS_REJECTED, array(
            'label'                     => __('Rejected', 'ensemble'),
            'public'                    => false,
            'internal'                  => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop('Rejected <span class="count">(%s)</span>', 'Rejected <span class="count">(%s)</span>', 'ensemble'),
        ));
        
        register_post_status(self::STATUS_REVISION, array(
            'label'                     => __('Revision Requested', 'ensemble'),
            'public'                    => false,
            'internal'                  => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop('Revision Requested <span class="count">(%s)</span>', 'Revision Requested <span class="count">(%s)</span>', 'ensemble'),
        ));
    }
    
    /**
     * Get all statuses
     * 
     * @return array
     */
    public static function get_statuses() {
        return array(
            self::STATUS_SUBMITTED => array(
                'label' => __('Submitted', 'ensemble'),
                'color' => '#2271b1',
                'icon'  => 'inbox',
            ),
            self::STATUS_IN_REVIEW => array(
                'label' => __('In Review', 'ensemble'),
                'color' => '#dba617',
                'icon'  => 'visibility',
            ),
            self::STATUS_ACCEPTED => array(
                'label' => __('Accepted', 'ensemble'),
                'color' => '#00a32a',
                'icon'  => 'yes-alt',
            ),
            self::STATUS_REJECTED => array(
                'label' => __('Rejected', 'ensemble'),
                'color' => '#d63638',
                'icon'  => 'dismiss',
            ),
            self::STATUS_REVISION => array(
                'label' => __('Revision Requested', 'ensemble'),
                'color' => '#9966cc',
                'icon'  => 'edit',
            ),
        );
    }
    
    /**
     * Create new abstract submission
     * 
     * @param array $data Submission data
     * @return int|WP_Error Abstract ID or error
     */
    public function create_submission($data) {
        $required = array('staff_id', 'name', 'email', 'title');
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return new WP_Error('missing_field', sprintf(__('Missing required field: %s', 'ensemble'), $field));
            }
        }
        
        // Create the abstract post
        $abstract_id = wp_insert_post(array(
            'post_type'    => self::POST_TYPE,
            'post_title'   => sanitize_text_field($data['title']),
            'post_content' => isset($data['message']) ? wp_kses_post($data['message']) : '',
            'post_status'  => self::STATUS_SUBMITTED,
        ));
        
        if (is_wp_error($abstract_id)) {
            return $abstract_id;
        }
        
        // Save meta data
        update_post_meta($abstract_id, '_abstract_staff_id', absint($data['staff_id']));
        update_post_meta($abstract_id, '_abstract_submitter_name', sanitize_text_field($data['name']));
        update_post_meta($abstract_id, '_abstract_submitter_email', sanitize_email($data['email']));
        update_post_meta($abstract_id, '_abstract_submission_date', current_time('mysql'));
        update_post_meta($abstract_id, '_abstract_ip_address', $this->get_client_ip());
        
        if (!empty($data['attachment_id'])) {
            update_post_meta($abstract_id, '_abstract_attachment_id', absint($data['attachment_id']));
        }
        
        if (!empty($data['attachment_url'])) {
            update_post_meta($abstract_id, '_abstract_attachment_url', esc_url_raw($data['attachment_url']));
        }
        
        // Log the initial status
        $this->add_status_log($abstract_id, self::STATUS_SUBMITTED, __('Abstract submitted', 'ensemble'));
        
        return $abstract_id;
    }
    
    /**
     * Get abstract by ID
     * 
     * @param int $abstract_id
     * @return array|false
     */
    public function get_abstract($abstract_id) {
        $post = get_post($abstract_id);
        
        if (!$post || $post->post_type !== self::POST_TYPE) {
            return false;
        }
        
        return $this->format_abstract($post);
    }
    
    /**
     * Get all abstracts
     * 
     * @param array $args Query arguments
     * @return array
     */
    public function get_abstracts($args = array()) {
        $defaults = array(
            'post_type'      => self::POST_TYPE,
            'posts_per_page' => 20,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'post_status'    => 'any',
        );
        
        $args = wp_parse_args($args, $defaults);
        
        // Filter by staff
        if (!empty($args['staff_id'])) {
            $args['meta_query'][] = array(
                'key'   => '_abstract_staff_id',
                'value' => absint($args['staff_id']),
            );
            unset($args['staff_id']);
        }
        
        // Filter by status
        if (!empty($args['status'])) {
            $args['post_status'] = $args['status'];
            unset($args['status']);
        }
        
        // Search
        if (!empty($args['search'])) {
            $args['s'] = $args['search'];
            unset($args['search']);
        }
        
        $posts = get_posts($args);
        
        $abstracts = array();
        foreach ($posts as $post) {
            $abstracts[] = $this->format_abstract($post);
        }
        
        return $abstracts;
    }
    
    /**
     * Format abstract data
     * 
     * @param WP_Post $post
     * @return array
     */
    private function format_abstract($post) {
        $staff_id = get_post_meta($post->ID, '_abstract_staff_id', true);
        $attachment_id = get_post_meta($post->ID, '_abstract_attachment_id', true);
        
        // Get staff info
        $staff_name = '';
        if ($staff_id) {
            $staff_post = get_post($staff_id);
            if ($staff_post) {
                $staff_name = $staff_post->post_title;
            }
        }
        
        // Get attachment info
        $attachment_url = get_post_meta($post->ID, '_abstract_attachment_url', true);
        $attachment_name = '';
        if ($attachment_url) {
            $attachment_name = basename($attachment_url);
        }
        
        $statuses = self::get_statuses();
        $status_info = isset($statuses[$post->post_status]) ? $statuses[$post->post_status] : $statuses[self::STATUS_SUBMITTED];
        
        return array(
            'id'               => $post->ID,
            'title'            => $post->post_title,
            'message'          => $post->post_content,
            'status'           => $post->post_status,
            'status_label'     => $status_info['label'],
            'status_color'     => $status_info['color'],
            'staff_id'         => $staff_id,
            'staff_name'       => $staff_name,
            'submitter_name'   => get_post_meta($post->ID, '_abstract_submitter_name', true),
            'submitter_email'  => get_post_meta($post->ID, '_abstract_submitter_email', true),
            'submission_date'  => get_post_meta($post->ID, '_abstract_submission_date', true),
            'attachment_id'    => $attachment_id,
            'attachment_url'   => $attachment_url,
            'attachment_name'  => $attachment_name,
            'ip_address'       => get_post_meta($post->ID, '_abstract_ip_address', true),
            'admin_notes'      => get_post_meta($post->ID, '_abstract_admin_notes', true),
            'status_log'       => get_post_meta($post->ID, '_abstract_status_log', true) ?: array(),
            'created'          => $post->post_date,
            'modified'         => $post->post_modified,
        );
    }
    
    /**
     * Update abstract status
     * 
     * @param int    $abstract_id
     * @param string $status
     * @param string $note Optional note
     * @return bool
     */
    public function update_status($abstract_id, $status, $note = '') {
        $statuses = self::get_statuses();
        
        if (!isset($statuses[$status])) {
            return false;
        }
        
        $result = wp_update_post(array(
            'ID'          => $abstract_id,
            'post_status' => $status,
        ));
        
        if (is_wp_error($result)) {
            return false;
        }
        
        // Log the status change
        $this->add_status_log($abstract_id, $status, $note);
        
        // Fire action for notifications
        do_action('ensemble_abstract_status_changed', $abstract_id, $status, $note);
        
        return true;
    }
    
    /**
     * Add entry to status log
     * 
     * @param int    $abstract_id
     * @param string $status
     * @param string $note
     */
    private function add_status_log($abstract_id, $status, $note = '') {
        $log = get_post_meta($abstract_id, '_abstract_status_log', true) ?: array();
        
        $log[] = array(
            'status'    => $status,
            'note'      => $note,
            'user_id'   => get_current_user_id(),
            'timestamp' => current_time('mysql'),
        );
        
        update_post_meta($abstract_id, '_abstract_status_log', $log);
    }
    
    /**
     * Update admin notes
     * 
     * @param int    $abstract_id
     * @param string $notes
     * @return bool
     */
    public function update_notes($abstract_id, $notes) {
        return update_post_meta($abstract_id, '_abstract_admin_notes', wp_kses_post($notes));
    }
    
    /**
     * Delete abstract
     * 
     * @param int $abstract_id
     * @return bool
     */
    public function delete_abstract($abstract_id) {
        // Delete attachment if exists
        $attachment_id = get_post_meta($abstract_id, '_abstract_attachment_id', true);
        if ($attachment_id) {
            wp_delete_attachment($attachment_id, true);
        }
        
        return wp_delete_post($abstract_id, true) !== false;
    }
    
    /**
     * Get counts by status
     * 
     * @param int $staff_id Optional - filter by staff
     * @return array
     */
    public function get_counts($staff_id = 0) {
        global $wpdb;
        
        $where = "post_type = '" . self::POST_TYPE . "'";
        
        if ($staff_id) {
            $where .= $wpdb->prepare(
                " AND ID IN (SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_abstract_staff_id' AND meta_value = %d)",
                $staff_id
            );
        }
        
        $results = $wpdb->get_results(
            "SELECT post_status, COUNT(*) as count 
             FROM {$wpdb->posts} 
             WHERE {$where}
             GROUP BY post_status"
        );
        
        $counts = array(
            'total' => 0,
        );
        
        foreach (self::get_statuses() as $status => $info) {
            $counts[$status] = 0;
        }
        
        foreach ($results as $row) {
            if (isset($counts[$row->post_status])) {
                $counts[$row->post_status] = (int) $row->count;
            }
            $counts['total'] += (int) $row->count;
        }
        
        return $counts;
    }
    
    /**
     * Get client IP address
     * 
     * @return string
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                return sanitize_text_field(trim($ip));
            }
        }
        
        return '';
    }
}
