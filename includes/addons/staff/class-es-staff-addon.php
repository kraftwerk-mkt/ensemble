<?php
/**
 * Ensemble Staff Add-on
 * 
 * Manages staff/contact persons for events, locations, and general contact pages.
 * Integrates with Dynamic Labels for industry-specific terminology.
 *
 * @package Ensemble
 * @subpackage Addons/Staff
 * @since 2.7.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class ES_Staff_Addon extends ES_Addon_Base {
    
    /**
     * Add-on slug
     * @var string
     */
    protected $slug = 'staff';
    
    /**
     * Add-on name
     * @var string
     */
    protected $name = 'Staff & Contacts';
    
    /**
     * Add-on version
     * @var string
     */
    protected $version = '1.0.0';
    
    /**
     * Staff Manager instance
     * @var ES_Staff_Manager
     */
    private $staff_manager;
    
    /**
     * Initialize add-on
     */
    protected function init() {
        // Load dependencies
        require_once $this->get_addon_path() . 'includes/class-staff-manager.php';
        
        $this->staff_manager = new ES_Staff_Manager();
    }
    
    /**
     * Register hooks
     */
    protected function register_hooks() {
        // Post type and taxonomy registration
        add_action('init', array($this, 'register_post_type'), 5);
        add_action('init', array($this, 'register_taxonomies'), 5);
        
        // Admin
        add_action('admin_menu', array($this, 'add_admin_menu'), 25);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Frontend
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        
        // AJAX handlers
        add_action('wp_ajax_es_save_staff', array($this, 'ajax_save_staff'));
        add_action('wp_ajax_es_delete_staff', array($this, 'ajax_delete_staff'));
        add_action('wp_ajax_es_get_staff', array($this, 'ajax_get_staff'));
        add_action('wp_ajax_es_get_staff_list', array($this, 'ajax_get_staff_list'));
        add_action('wp_ajax_es_bulk_delete_staff', array($this, 'ajax_bulk_delete_staff'));
        add_action('wp_ajax_es_search_staff', array($this, 'ajax_search_staff'));
        add_action('wp_ajax_es_bulk_assign_staff_department', array($this, 'ajax_bulk_assign_department'));
        add_action('wp_ajax_es_bulk_remove_staff_department', array($this, 'ajax_bulk_remove_department'));
        
        // Abstract upload AJAX (public)
        add_action('wp_ajax_es_submit_abstract', array($this, 'ajax_submit_abstract'));
        add_action('wp_ajax_nopriv_es_submit_abstract', array($this, 'ajax_submit_abstract'));
        
        // Shortcodes
        add_shortcode('ensemble_staff', array($this, 'shortcode_staff'));
        add_shortcode('ensemble_contact_form', array($this, 'shortcode_contact_form'));
        add_shortcode('ensemble_staff_single', array($this, 'shortcode_staff_single'));
        add_shortcode('ensemble_event_contacts', array($this, 'shortcode_event_contacts'));
        
        // Template hooks
        add_action('ensemble_event_footer', array($this, 'render_event_contacts'), 20);
        add_action('ensemble_event_contacts', array($this, 'render_event_contacts'), 10);
        add_action('ensemble_location_contacts', array($this, 'render_location_contacts'), 10);
        
        // Wizard integration
        add_action('ensemble_wizard_form_cards', array($this, 'render_wizard_contacts_card'));
        add_filter('ensemble_wizard_event_data', array($this, 'add_wizard_event_data'), 10, 2);
        add_action('wp_ajax_es_save_event_contacts', array($this, 'ajax_save_event_contacts'));
        
        // Location Manager integration
        add_action('ensemble_location_form_cards', array($this, 'render_location_contacts_card'));
        
        // Meta box for Events and Locations
        add_action('add_meta_boxes', array($this, 'add_contact_meta_boxes'));
        add_action('save_post', array($this, 'save_contact_meta'), 10, 2);
        
        // Template override
        add_filter('single_template', array($this, 'load_single_template'));
    }
    
    /**
     * Register Staff post type
     */
    public function register_post_type() {
        $singular = $this->get_staff_label(false);
        $plural = $this->get_staff_label(true);
        
        register_post_type('ensemble_staff', array(
            'labels' => array(
                'name'               => $plural,
                'singular_name'      => $singular,
                'add_new'            => sprintf(__('Add New %s', 'ensemble'), $singular),
                'add_new_item'       => sprintf(__('Add New %s', 'ensemble'), $singular),
                'edit_item'          => sprintf(__('Edit %s', 'ensemble'), $singular),
                'new_item'           => sprintf(__('New %s', 'ensemble'), $singular),
                'view_item'          => sprintf(__('View %s', 'ensemble'), $singular),
                'search_items'       => sprintf(__('Search %s', 'ensemble'), $plural),
                'not_found'          => sprintf(__('No %s found', 'ensemble'), strtolower($plural)),
                'not_found_in_trash' => sprintf(__('No %s found in trash', 'ensemble'), strtolower($plural)),
            ),
            'public'             => true,
            'has_archive'        => true,
            'show_in_menu'       => false,
            'show_ui'            => true,
            'show_in_rest'       => true,
            'supports'           => array('title', 'editor', 'thumbnail', 'custom-fields'),
            'rewrite'            => array('slug' => sanitize_title($plural)),
            'menu_icon'          => 'dashicons-businessperson',
        ));
    }
    
    /**
     * Register taxonomies
     */
    public function register_taxonomies() {
        $dept_singular = $this->get_department_label(false);
        $dept_plural = $this->get_department_label(true);
        
        // Departments/Categories
        register_taxonomy('ensemble_department', array('ensemble_staff'), array(
            'labels' => array(
                'name'              => $dept_plural,
                'singular_name'     => $dept_singular,
                'search_items'      => sprintf(__('Search %s', 'ensemble'), $dept_plural),
                'all_items'         => sprintf(__('All %s', 'ensemble'), $dept_plural),
                'parent_item'       => sprintf(__('Parent %s', 'ensemble'), $dept_singular),
                'parent_item_colon' => sprintf(__('Parent %s:', 'ensemble'), $dept_singular),
                'edit_item'         => sprintf(__('Edit %s', 'ensemble'), $dept_singular),
                'update_item'       => sprintf(__('Update %s', 'ensemble'), $dept_singular),
                'add_new_item'      => sprintf(__('Add New %s', 'ensemble'), $dept_singular),
                'new_item_name'     => sprintf(__('New %s Name', 'ensemble'), $dept_singular),
                'menu_name'         => $dept_plural,
            ),
            'hierarchical'      => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'rewrite'           => array('slug' => 'department'),
        ));
    }
    
    /**
     * Get staff label based on usage type
     * 
     * @param bool $plural
     * @return string
     */
    public function get_staff_label($plural = false) {
        if (class_exists('ES_Label_System')) {
            return ES_Label_System::get_label('staff', $plural);
        }
        return $plural ? __('Contacts', 'ensemble') : __('Contact', 'ensemble');
    }
    
    /**
     * Get department label based on usage type
     * 
     * @param bool $plural
     * @return string
     */
    public function get_department_label($plural = false) {
        if (class_exists('ES_Label_System')) {
            return ES_Label_System::get_label('department', $plural);
        }
        return $plural ? __('Departments', 'ensemble') : __('Department', 'ensemble');
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        $singular = $this->get_staff_label(false);
        $plural = $this->get_staff_label(true);
        
        add_submenu_page(
            'ensemble',
            $plural,
            $plural,
            'manage_options',
            'ensemble-staff',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * Enqueue admin assets
     * 
     * Uses admin-unified.css and manager.css (loaded by core patterns)
     */
    public function enqueue_admin_assets($hook) {
        // Only on Staff page or Event/Location editors
        if (strpos($hook, 'ensemble-staff') === false && 
            !in_array(get_post_type(), array(ensemble_get_post_type(), 'ensemble_location'))) {
            return;
        }
        
        // Load manager.css for grid/list view (same as artist/location pages)
        if (strpos($hook, 'ensemble-staff') !== false) {
            wp_enqueue_style(
                'ensemble-manager',
                ENSEMBLE_PLUGIN_URL . 'assets/css/manager.css',
                array('ensemble-admin-unified'),
                ENSEMBLE_VERSION,
                'all'
            );
        }
        
        // Media uploader for image selection
        wp_enqueue_media();
        
        // Localize for meta boxes (Event/Location contact picker)
        wp_localize_script('jquery', 'ensembleStaff', array(
            'ajaxUrl'     => admin_url('admin-ajax.php'),
            'nonce'       => wp_create_nonce('ensemble_staff_nonce'),
            'labels'      => array(
                'singular'   => $this->get_staff_label(false),
                'plural'     => $this->get_staff_label(true),
                'department' => $this->get_department_label(false),
                'departments' => $this->get_department_label(true),
            ),
        ));
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        if (!$this->should_load_frontend_assets()) {
            return;
        }
        
        wp_enqueue_style(
            'ensemble-staff',
            $this->get_addon_url() . 'assets/staff.css',
            array(),
            $this->version
        );
        
        wp_enqueue_script(
            'ensemble-staff',
            $this->get_addon_url() . 'assets/staff.js',
            array('jquery'),
            $this->version,
            true
        );
        
        wp_localize_script('ensemble-staff', 'ensembleStaffFrontend', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('ensemble_staff_public_nonce'),
            'strings' => array(
                'uploading'    => __('Uploading...', 'ensemble'),
                'success'      => __('Thank you! Your submission has been received.', 'ensemble'),
                'error'        => __('An error occurred. Please try again.', 'ensemble'),
                'invalidFile'  => __('Invalid file type. Please upload a PDF or Word document.', 'ensemble'),
                'fileTooLarge' => __('File is too large. Maximum size is %s MB.', 'ensemble'),
            ),
        ));
    }
    
    /**
     * Check if frontend assets should be loaded
     * 
     * @return bool
     */
    private function should_load_frontend_assets() {
        global $post;
        
        // Always load on staff archive/single
        if (is_post_type_archive('ensemble_staff') || is_singular('ensemble_staff')) {
            return true;
        }
        
        // Check for shortcodes
        if ($post && (
            has_shortcode($post->post_content, 'ensemble_staff') ||
            has_shortcode($post->post_content, 'ensemble_contact_form') ||
            has_shortcode($post->post_content, 'ensemble_staff_single')
        )) {
            return true;
        }
        
        // Load on event/location singles
        if (is_singular(ensemble_get_post_type()) || is_singular('ensemble_location')) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        include $this->get_addon_path() . 'templates/admin-page.php';
    }
    
    /**
     * Get Staff Manager
     * 
     * @return ES_Staff_Manager
     */
    public function get_staff_manager() {
        return $this->staff_manager;
    }
    
    // =========================================================================
    // AJAX Handlers
    // =========================================================================
    
    /**
     * AJAX: Save staff
     */
    public function ajax_save_staff() {
        check_ajax_referer('ensemble_staff_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'ensemble')));
        }
        
        $data = array(
            'staff_id'           => isset($_POST['staff_id']) ? absint($_POST['staff_id']) : 0,
            'name'               => isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '',
            'position'           => isset($_POST['position']) ? sanitize_text_field($_POST['position']) : '',
            'email'              => isset($_POST['email']) ? sanitize_email($_POST['email']) : '',
            'phone'              => isset($_POST['phone']) ? $this->sanitize_phones($_POST['phone']) : array(),
            'description'        => isset($_POST['description']) ? wp_kses_post($_POST['description']) : '',
            'office_hours'       => isset($_POST['office_hours']) ? sanitize_text_field($_POST['office_hours']) : '',
            'responsibility'     => isset($_POST['responsibility']) ? sanitize_text_field($_POST['responsibility']) : '',
            'featured_image_id'  => isset($_POST['featured_image_id']) ? absint($_POST['featured_image_id']) : 0,
            'departments'        => isset($_POST['departments']) ? array_map('absint', (array) $_POST['departments']) : array(),
            'menu_order'         => isset($_POST['menu_order']) ? absint($_POST['menu_order']) : 0,
            // Abstract upload settings
            'abstract_enabled'   => isset($_POST['abstract_enabled']) ? (bool) $_POST['abstract_enabled'] : false,
            'abstract_types'     => isset($_POST['abstract_types']) ? array_map('sanitize_text_field', (array) $_POST['abstract_types']) : array('pdf'),
            'abstract_max_size'  => isset($_POST['abstract_max_size']) ? absint($_POST['abstract_max_size']) : 10,
            // Social links
            'website'            => isset($_POST['website']) ? esc_url_raw($_POST['website']) : '',
            'linkedin'           => isset($_POST['linkedin']) ? esc_url_raw($_POST['linkedin']) : '',
            'twitter'            => isset($_POST['twitter']) ? esc_url_raw($_POST['twitter']) : '',
        );
        
        $result = $this->staff_manager->save_staff($data);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success(array(
            'message'  => __('Contact saved successfully', 'ensemble'),
            'staff_id' => $result,
            'staff'    => $this->staff_manager->get_staff($result),
        ));
    }
    
    /**
     * Sanitize phone numbers array
     * 
     * @param array|string $phones
     * @return array
     */
    private function sanitize_phones($phones) {
        if (!is_array($phones)) {
            $phones = array($phones);
        }
        
        $sanitized = array();
        foreach ($phones as $phone) {
            if (is_array($phone)) {
                $sanitized[] = array(
                    'type'   => isset($phone['type']) ? sanitize_text_field($phone['type']) : 'office',
                    'number' => isset($phone['number']) ? sanitize_text_field($phone['number']) : '',
                );
            } else {
                $sanitized[] = array(
                    'type'   => 'office',
                    'number' => sanitize_text_field($phone),
                );
            }
        }
        
        return array_filter($sanitized, function($p) {
            return !empty($p['number']);
        });
    }
    
    /**
     * AJAX: Delete staff
     */
    public function ajax_delete_staff() {
        check_ajax_referer('ensemble_staff_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'ensemble')));
        }
        
        $staff_id = isset($_POST['staff_id']) ? absint($_POST['staff_id']) : 0;
        
        if (!$staff_id) {
            wp_send_json_error(array('message' => __('Invalid staff ID', 'ensemble')));
        }
        
        $result = $this->staff_manager->delete_staff($staff_id);
        
        if (!$result) {
            wp_send_json_error(array('message' => __('Failed to delete contact', 'ensemble')));
        }
        
        wp_send_json_success(array('message' => __('Contact deleted', 'ensemble')));
    }
    
    /**
     * AJAX: Get single staff
     */
    public function ajax_get_staff() {
        check_ajax_referer('ensemble_staff_nonce', 'nonce');
        
        $staff_id = isset($_POST['staff_id']) ? absint($_POST['staff_id']) : 0;
        
        if (!$staff_id) {
            wp_send_json_error(array('message' => __('Invalid staff ID', 'ensemble')));
        }
        
        $staff = $this->staff_manager->get_staff($staff_id);
        
        if (!$staff) {
            wp_send_json_error(array('message' => __('Contact not found', 'ensemble')));
        }
        
        wp_send_json_success($staff);
    }
    
    /**
     * AJAX: Get staff list
     */
    public function ajax_get_staff_list() {
        check_ajax_referer('ensemble_staff_nonce', 'nonce');
        
        $args = array();
        
        if (!empty($_POST['department'])) {
            $args['department'] = sanitize_text_field($_POST['department']);
        }
        
        if (!empty($_POST['search'])) {
            $args['search'] = sanitize_text_field($_POST['search']);
        }
        
        $staff = $this->staff_manager->get_all_staff($args);
        $departments = $this->staff_manager->get_departments();
        
        wp_send_json_success(array(
            'staff'       => $staff,
            'departments' => $departments,
            'total'       => count($staff),
        ));
    }
    
    /**
     * AJAX: Bulk delete staff
     */
    public function ajax_bulk_delete_staff() {
        check_ajax_referer('ensemble_staff_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'ensemble')));
        }
        
        $staff_ids = isset($_POST['staff_ids']) ? array_map('absint', (array) $_POST['staff_ids']) : array();
        
        if (empty($staff_ids)) {
            wp_send_json_error(array('message' => __('No contacts selected', 'ensemble')));
        }
        
        $result = $this->staff_manager->bulk_delete($staff_ids);
        
        wp_send_json_success(array(
            'message' => sprintf(__('%d contacts deleted', 'ensemble'), $result['deleted']),
            'deleted' => $result['deleted'],
            'failed'  => $result['failed'],
        ));
    }
    
    /**
     * AJAX: Search staff (for picker)
     */
    public function ajax_search_staff() {
        check_ajax_referer('ensemble_staff_nonce', 'nonce');
        
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        
        $staff = $this->staff_manager->get_all_staff(array(
            'search' => $search,
            'posts_per_page' => 20,
        ));
        
        $results = array();
        foreach ($staff as $person) {
            $results[] = array(
                'id'       => $person['id'],
                'text'     => $person['name'],
                'position' => $person['position'],
                'image'    => $person['featured_image'],
            );
        }
        
        wp_send_json_success($results);
    }
    
    /**
     * AJAX: Bulk assign department
     */
    public function ajax_bulk_assign_department() {
        check_ajax_referer('ensemble_staff_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'ensemble')));
        }
        
        $staff_ids = isset($_POST['staff_ids']) ? array_map('absint', (array) $_POST['staff_ids']) : array();
        $term_id = isset($_POST['term_id']) ? absint($_POST['term_id']) : 0;
        
        if (empty($staff_ids) || !$term_id) {
            wp_send_json_error(array('message' => __('Invalid parameters', 'ensemble')));
        }
        
        $updated = 0;
        foreach ($staff_ids as $staff_id) {
            $result = wp_set_post_terms($staff_id, array($term_id), 'ensemble_department', true);
            if (!is_wp_error($result)) {
                $updated++;
            }
        }
        
        wp_send_json_success(array(
            'message' => sprintf(__('%d contacts updated', 'ensemble'), $updated),
            'updated' => $updated,
        ));
    }
    
    /**
     * AJAX: Bulk remove department
     */
    public function ajax_bulk_remove_department() {
        check_ajax_referer('ensemble_staff_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'ensemble')));
        }
        
        $staff_ids = isset($_POST['staff_ids']) ? array_map('absint', (array) $_POST['staff_ids']) : array();
        
        if (empty($staff_ids)) {
            wp_send_json_error(array('message' => __('No contacts selected', 'ensemble')));
        }
        
        $updated = 0;
        foreach ($staff_ids as $staff_id) {
            $result = wp_set_post_terms($staff_id, array(), 'ensemble_department');
            if (!is_wp_error($result)) {
                $updated++;
            }
        }
        
        wp_send_json_success(array(
            'message' => sprintf(__('%d contacts updated', 'ensemble'), $updated),
            'updated' => $updated,
        ));
    }
    
    /**
     * AJAX: Submit abstract
     */
    public function ajax_submit_abstract() {
        check_ajax_referer('ensemble_staff_public_nonce', 'nonce');
        
        $staff_id = isset($_POST['staff_id']) ? absint($_POST['staff_id']) : 0;
        
        if (!$staff_id) {
            wp_send_json_error(array('message' => __('Invalid recipient', 'ensemble')));
        }
        
        $staff = $this->staff_manager->get_staff($staff_id);
        
        if (!$staff || !$staff['abstract_enabled']) {
            wp_send_json_error(array('message' => __('Submissions are not accepted for this contact', 'ensemble')));
        }
        
        // Validate required fields
        $name = isset($_POST['submitter_name']) ? sanitize_text_field($_POST['submitter_name']) : '';
        $email = isset($_POST['submitter_email']) ? sanitize_email($_POST['submitter_email']) : '';
        $title = isset($_POST['abstract_title']) ? sanitize_text_field($_POST['abstract_title']) : '';
        $message = isset($_POST['abstract_message']) ? sanitize_textarea_field($_POST['abstract_message']) : '';
        
        if (empty($name) || empty($email) || empty($title)) {
            wp_send_json_error(array('message' => __('Please fill in all required fields', 'ensemble')));
        }
        
        // Handle file upload
        $attachment_url = '';
        if (!empty($_FILES['abstract_file'])) {
            $allowed_types = $staff['abstract_types'];
            $max_size = $staff['abstract_max_size'] * 1024 * 1024; // Convert to bytes
            
            $file = $_FILES['abstract_file'];
            
            // Check file size
            if ($file['size'] > $max_size) {
                wp_send_json_error(array(
                    'message' => sprintf(__('File is too large. Maximum size is %d MB.', 'ensemble'), $staff['abstract_max_size'])
                ));
            }
            
            // Check file type
            $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $type_map = array(
                'pdf' => array('pdf'),
                'doc' => array('doc', 'docx'),
                'ppt' => array('ppt', 'pptx'),
            );
            
            $allowed_extensions = array();
            foreach ($allowed_types as $type) {
                if (isset($type_map[$type])) {
                    $allowed_extensions = array_merge($allowed_extensions, $type_map[$type]);
                }
            }
            
            if (!in_array($file_ext, $allowed_extensions)) {
                wp_send_json_error(array('message' => __('Invalid file type', 'ensemble')));
            }
            
            // Upload file
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            
            $upload = wp_handle_upload($file, array('test_form' => false));
            
            if (isset($upload['error'])) {
                wp_send_json_error(array('message' => $upload['error']));
            }
            
            $attachment_url = $upload['url'];
        }
        
        // Send email notification
        $to = $staff['email'];
        $subject = sprintf(__('[Abstract Submission] %s', 'ensemble'), $title);
        
        $body = sprintf(__("New abstract submission received:\n\n", 'ensemble'));
        $body .= sprintf(__("Name: %s\n", 'ensemble'), $name);
        $body .= sprintf(__("Email: %s\n", 'ensemble'), $email);
        $body .= sprintf(__("Title: %s\n\n", 'ensemble'), $title);
        
        if (!empty($message)) {
            $body .= sprintf(__("Message:\n%s\n\n", 'ensemble'), $message);
        }
        
        if (!empty($attachment_url)) {
            $body .= sprintf(__("Attachment: %s\n", 'ensemble'), $attachment_url);
        }
        
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        $headers[] = sprintf('Reply-To: %s <%s>', $name, $email);
        
        $sent = wp_mail($to, $subject, $body, $headers);
        
        if (!$sent) {
            wp_send_json_error(array('message' => __('Failed to send submission. Please try again.', 'ensemble')));
        }
        
        // Fire action for extensions
        do_action('ensemble_abstract_submitted', array(
            'staff_id'   => $staff_id,
            'name'       => $name,
            'email'      => $email,
            'title'      => $title,
            'message'    => $message,
            'attachment' => $attachment_url,
        ));
        
        wp_send_json_success(array(
            'message' => __('Thank you! Your submission has been received.', 'ensemble'),
        ));
    }
    
    // =========================================================================
    // Shortcodes
    // =========================================================================
    
    /**
     * Shortcode: Staff list/grid
     * 
     * @param array $atts
     * @return string
     */
    public function shortcode_staff($atts) {
        // Get saved settings as defaults
        $settings = $this->get_settings();
        
        $atts = shortcode_atts(array(
            'layout'            => !empty($settings['default_layout']) ? $settings['default_layout'] : 'grid',
            'columns'           => !empty($settings['default_columns']) ? $settings['default_columns'] : 3,
            'department'        => '',
            'ids'               => '',
            'limit'             => -1,
            'orderby'           => 'menu_order',
            'order'             => 'ASC',
            'show_email'        => !empty($settings['show_email']) ? 'yes' : 'no',
            'show_phone'        => !empty($settings['show_phone']) ? 'yes' : 'no',
            'show_position'     => !empty($settings['show_position']) ? 'yes' : 'no',
            'show_department'   => !empty($settings['show_department']) ? 'yes' : 'no',
            'show_office_hours' => !empty($settings['show_office_hours']) ? 'yes' : 'no',
            'show_social'       => !empty($settings['show_social_links']) ? 'yes' : 'no',
        ), $atts, 'ensemble_staff');
        
        $args = array(
            'posts_per_page' => intval($atts['limit']),
            'orderby'        => $atts['orderby'],
            'order'          => $atts['order'],
        );
        
        if (!empty($atts['department'])) {
            $args['department'] = $atts['department'];
        }
        
        if (!empty($atts['ids'])) {
            $args['post__in'] = array_map('absint', explode(',', $atts['ids']));
            $args['orderby'] = 'post__in';
        }
        
        $staff = $this->staff_manager->get_all_staff($args);
        
        if (empty($staff)) {
            return '';
        }
        
        return $this->load_template('staff-' . $atts['layout'], array(
            'staff'   => $staff,
            'atts'    => $atts,
            'columns' => intval($atts['columns']),
        ));
    }
    
    /**
     * Shortcode: Contact form
     * 
     * @param array $atts
     * @return string
     */
    public function shortcode_contact_form($atts) {
        $atts = shortcode_atts(array(
            'staff_id'    => 0,
            'title'       => '',
            'description' => '',
        ), $atts, 'ensemble_contact_form');
        
        $staff_id = absint($atts['staff_id']);
        
        if (!$staff_id) {
            return '';
        }
        
        $staff = $this->staff_manager->get_staff($staff_id);
        
        if (!$staff || !$staff['abstract_enabled']) {
            return '';
        }
        
        return $this->load_template('staff-contact-form', array(
            'staff' => $staff,
            'atts'  => $atts,
        ));
    }
    
    /**
     * Shortcode: Single staff display
     * 
     * @param array $atts
     * @return string
     */
    public function shortcode_staff_single($atts) {
        $atts = shortcode_atts(array(
            'id'     => 0,
            'layout' => 'card', // card, full, compact
        ), $atts, 'ensemble_staff_single');
        
        $staff_id = absint($atts['id']);
        
        if (!$staff_id) {
            return '';
        }
        
        $staff = $this->staff_manager->get_staff($staff_id);
        
        if (!$staff) {
            return '';
        }
        
        return $this->load_template('staff-card', array(
            'staff'  => $staff,
            'layout' => $atts['layout'],
        ));
    }
    
    // =========================================================================
    // Template Hooks & Meta Boxes
    // =========================================================================
    
    /**
     * Render event contacts in footer
     */
    public function render_event_contacts() {
        global $post;
        
        if (!$post) {
            return;
        }
        
        $contact_ids = get_post_meta($post->ID, '_es_event_contacts', true);
        
        if (empty($contact_ids) || !is_array($contact_ids)) {
            return;
        }
        
        $staff = array();
        foreach ($contact_ids as $id) {
            $person = $this->staff_manager->get_staff($id);
            if ($person) {
                $staff[] = $person;
            }
        }
        
        if (empty($staff)) {
            return;
        }
        
        echo $this->load_template('event-footer-contacts', array(
            'staff' => $staff,
            'title' => $this->get_staff_label(true),
        ));
    }
    
    /**
     * Render location contacts
     */
    public function render_location_contacts() {
        global $post;
        
        if (!$post) {
            return;
        }
        
        $contact_ids = get_post_meta($post->ID, '_es_location_contacts', true);
        
        if (empty($contact_ids) || !is_array($contact_ids)) {
            return;
        }
        
        $staff = array();
        foreach ($contact_ids as $id) {
            $person = $this->staff_manager->get_staff($id);
            if ($person) {
                $staff[] = $person;
            }
        }
        
        if (empty($staff)) {
            return;
        }
        
        echo $this->load_template('location-contacts', array(
            'staff' => $staff,
            'title' => $this->get_staff_label(true),
        ));
    }
    
    /**
     * Add meta boxes for contact selection
     */
    public function add_contact_meta_boxes() {
        // For Events
        add_meta_box(
            'ensemble_event_contacts',
            $this->get_staff_label(true),
            array($this, 'render_contacts_meta_box'),
            ensemble_get_post_type(),
            'side',
            'default',
            array('type' => 'event')
        );
        
        // For Locations
        add_meta_box(
            'ensemble_location_contacts',
            $this->get_staff_label(true),
            array($this, 'render_contacts_meta_box'),
            'ensemble_location',
            'side',
            'default',
            array('type' => 'location')
        );
    }
    
    /**
     * Render contacts meta box
     * 
     * @param WP_Post $post
     * @param array $metabox
     */
    public function render_contacts_meta_box($post, $metabox) {
        $type = $metabox['args']['type'];
        $meta_key = '_es_' . $type . '_contacts';
        $selected_ids = get_post_meta($post->ID, $meta_key, true);
        
        if (!is_array($selected_ids)) {
            $selected_ids = array();
        }
        
        // Get all staff for selection
        $all_staff = $this->staff_manager->get_all_staff();
        
        wp_nonce_field('ensemble_contacts_meta', 'ensemble_contacts_nonce');
        ?>
        <div class="es-contacts-picker">
            <select name="<?php echo esc_attr($meta_key); ?>[]" 
                    multiple="multiple" 
                    class="es-contact-select"
                    style="width: 100%;"
                    data-placeholder="<?php esc_attr_e('Select contacts...', 'ensemble'); ?>">
                <?php foreach ($all_staff as $person) : ?>
                    <option value="<?php echo esc_attr($person['id']); ?>"
                            <?php selected(in_array($person['id'], $selected_ids)); ?>>
                        <?php echo esc_html($person['name']); ?>
                        <?php if ($person['position']) : ?>
                            (<?php echo esc_html($person['position']); ?>)
                        <?php endif; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="description">
                <?php printf(
                    __('Select %s to display on this %s.', 'ensemble'),
                    strtolower($this->get_staff_label(true)),
                    $type === 'event' ? strtolower(ES_Label_System::get_label('event', false)) : __('location', 'ensemble')
                ); ?>
            </p>
        </div>
        <?php
    }
    
    /**
     * Save contact meta
     * 
     * @param int $post_id
     * @param WP_Post $post
     */
    public function save_contact_meta($post_id, $post) {
        if (!isset($_POST['ensemble_contacts_nonce']) || 
            !wp_verify_nonce($_POST['ensemble_contacts_nonce'], 'ensemble_contacts_meta')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save event contacts
        if ($post->post_type === ensemble_get_post_type()) {
            $contacts = isset($_POST['_es_event_contacts']) ? array_map('absint', $_POST['_es_event_contacts']) : array();
            update_post_meta($post_id, '_es_event_contacts', $contacts);
        }
        
        // Save location contacts
        if ($post->post_type === 'ensemble_location') {
            $contacts = isset($_POST['_es_location_contacts']) ? array_map('absint', $_POST['_es_location_contacts']) : array();
            update_post_meta($post_id, '_es_location_contacts', $contacts);
        }
    }
    
    /**
     * Load single template
     * 
     * @param string $template
     * @return string
     */
    public function load_single_template($template) {
        global $post;
        
        if ($post && $post->post_type === 'ensemble_staff') {
            $custom_template = $this->get_addon_path() . 'templates/single-staff.php';
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }
        
        return $template;
    }
    
    // =========================================================================
    // Settings
    // =========================================================================
    
    /**
     * Render settings page
     * 
     * @return string
     */
    public function render_settings() {
        $settings = $this->get_settings();
        ob_start();
        include $this->get_addon_path() . 'templates/settings.php';
        return ob_get_clean();
    }
    
    /**
     * Get default settings
     * 
     * @return array
     */
    public function get_default_settings() {
        return array(
            'show_in_event_footer'    => true,
            'show_in_location'        => true,
            'default_layout'          => 'grid',
            'default_columns'         => 3,
            'show_email'              => true,
            'show_phone'              => true,
            'show_position'           => true,
            'show_department'         => true,
            'show_office_hours'       => true,
            'show_social_links'       => true,
            'enable_abstract_upload'  => true,
            'abstract_allowed_types'  => array('pdf', 'doc'),
            'abstract_max_size'       => 10, // MB
            'auto_display_events'     => false,
            'event_position'          => 'after_content',
            'event_title'             => __('Contact Persons', 'ensemble'),
            'auto_display_locations'  => false,
            'location_position'       => 'after_content',
            'location_title'          => __('Contact Persons', 'ensemble'),
        );
    }
    
    /**
     * Load a template file with variables
     * 
     * @param string $template Template name (without .php)
     * @param array  $vars     Variables to extract
     * @return string
     */
    protected function load_template($template, $vars = array()) {
        $template_file = $this->get_addon_path() . 'templates/' . $template . '.php';
        
        // Allow theme override
        $theme_template = locate_template('ensemble/staff/' . $template . '.php');
        if ($theme_template) {
            $template_file = $theme_template;
        }
        
        if (!file_exists($template_file)) {
            return '';
        }
        
        // Extract variables
        if (!empty($vars)) {
            extract($vars);
        }
        
        ob_start();
        include $template_file;
        return ob_get_clean();
    }
    
    // =========================================================================
    // Wizard Integration
    // =========================================================================
    
    /**
     * Render contacts card in Event Wizard
     */
    public function render_wizard_contacts_card() {
        $all_staff = $this->staff_manager->get_all_staff();
        
        if (empty($all_staff)) {
            return;
        }
        
        $staff_label = $this->get_staff_label(true);
        ?>
        <!-- Contacts Card (Staff Addon) -->
        <div class="es-form-card" id="es-contacts-card">
            <div class="es-form-card-header">
                <div class="es-form-card-icon">
                    <span class="dashicons dashicons-businessperson"></span>
                </div>
                <div class="es-form-card-title">
                    <h3><?php echo esc_html($staff_label); ?></h3>
                    <p class="es-form-card-desc"><?php _e('Assign contact persons for this event', 'ensemble'); ?></p>
                </div>
            </div>
            <div class="es-form-card-body">
                <div class="es-form-row">
                    <div class="es-contact-pills" id="es-contact-selection">
                        <?php foreach ($all_staff as $person) : ?>
                        <div class="es-contact-pill" data-contact-id="<?php echo esc_attr($person['id']); ?>">
                            <input type="checkbox" 
                                   name="event_contacts[]" 
                                   value="<?php echo esc_attr($person['id']); ?>"
                                   class="es-contact-checkbox">
                            <span class="es-contact-pill-label">
                                <?php if (!empty($person['featured_image'])) : ?>
                                    <img src="<?php echo esc_url($person['featured_image']); ?>" alt="" class="es-contact-pill-image">
                                <?php else : ?>
                                    <span class="es-contact-pill-placeholder"><span class="dashicons dashicons-businessperson"></span></span>
                                <?php endif; ?>
                                <span class="es-contact-pill-name"><?php echo esc_html($person['name']); ?></span>
                                <?php if (!empty($person['position'])) : ?>
                                    <span class="es-contact-pill-position"><?php echo esc_html($person['position']); ?></span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <p class="description" style="margin-top: 10px;">
                        <?php printf(
                            __('Select %s to display on this event. <a href="%s" target="_blank">Manage %s</a>', 'ensemble'),
                            strtolower($staff_label),
                            admin_url('admin.php?page=ensemble-staff'),
                            $staff_label
                        ); ?>
                    </p>
                </div>
            </div>
        </div>
        
        <style>
        .es-contact-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .es-contact-pill {
            display: flex;
            align-items: center;
            background: var(--es-surface-secondary, #383838);
            border: 1px solid var(--es-border, #404040);
            border-radius: 20px;
            padding: 4px 12px 4px 4px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .es-contact-pill:hover {
            border-color: var(--es-primary, #3582c4);
        }
        .es-contact-pill.selected {
            background: var(--es-primary-light, rgba(53, 130, 196, 0.15));
            border-color: var(--es-primary, #3582c4);
        }
        .es-contact-pill input[type="checkbox"] {
            display: none;
        }
        .es-contact-pill-label {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .es-contact-pill-image,
        .es-contact-pill-placeholder {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            object-fit: cover;
        }
        .es-contact-pill-placeholder {
            background: var(--es-surface, #2c2c2c);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .es-contact-pill-placeholder .dashicons {
            font-size: 16px;
            width: 16px;
            height: 16px;
            color: var(--es-text-muted, #787878);
        }
        .es-contact-pill-name {
            font-weight: 500;
            color: var(--es-text, #e0e0e0);
        }
        .es-contact-pill-position {
            font-size: 11px;
            color: var(--es-text-secondary, #a0a0a0);
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Toggle contact selection on click
            $('#es-contact-selection').on('click', '.es-contact-pill', function(e) {
                e.preventDefault();
                var $pill = $(this);
                var $checkbox = $pill.find('.es-contact-checkbox');
                var isChecked = $checkbox.prop('checked');
                
                // Toggle
                $checkbox.prop('checked', !isChecked);
                $pill.toggleClass('selected', !isChecked);
            });
            
            // Reset contacts when form is reset (new event)
            $(document).on('ensemble_form_reset', function() {
                $('#es-contact-selection .es-contact-pill').removeClass('selected');
                $('#es-contact-selection .es-contact-checkbox').prop('checked', false);
            });
            
            // Load contacts when editing event
            $(document).on('ensemble_event_loaded', function(e, eventData) {
                // Reset all first
                $('#es-contact-selection .es-contact-pill').removeClass('selected');
                $('#es-contact-selection .es-contact-checkbox').prop('checked', false);
                
                // Set selected contacts
                if (eventData && eventData.contacts && Array.isArray(eventData.contacts)) {
                    eventData.contacts.forEach(function(contactId) {
                        var $pill = $('#es-contact-selection .es-contact-pill[data-contact-id="' + contactId + '"]');
                        if ($pill.length) {
                            $pill.addClass('selected');
                            $pill.find('.es-contact-checkbox').prop('checked', true);
                        }
                    });
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Add contacts to wizard event data
     * 
     * @param array   $event_data
     * @param WP_Post $post
     * @return array
     */
    public function add_wizard_event_data($event_data, $post) {
        $contacts = get_post_meta($post->ID, '_es_event_contacts', true);
        $event_data['contacts'] = is_array($contacts) ? $contacts : array();
        
        return $event_data;
    }
    
    /**
     * Render contacts card for Location Manager form
     * 
     * @since 2.8.0
     */
    public function render_location_contacts_card() {
        $all_staff = $this->staff_manager->get_all_staff(array(
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ));
        
        if (empty($all_staff)) {
            return;
        }
        
        $staff_label = $this->get_staff_label(true);
        ?>
        <!-- Contacts Card (Staff Addon) -->
        <div class="es-form-card" id="es-location-contacts-card">
            <div class="es-form-card-header">
                <div class="es-form-card-icon">
                    <span class="dashicons dashicons-businessperson"></span>
                </div>
                <div class="es-form-card-title">
                    <h3><?php echo esc_html($staff_label); ?></h3>
                    <p class="es-form-card-desc"><?php _e('Assign contact persons for this location', 'ensemble'); ?></p>
                </div>
            </div>
            <div class="es-form-card-body">
                <div class="es-form-row">
                    <div class="es-contact-pills" id="es-location-contact-selection">
                        <?php foreach ($all_staff as $person) : ?>
                        <div class="es-contact-pill" data-contact-id="<?php echo esc_attr($person['id']); ?>">
                            <input type="checkbox" 
                                   name="location_contacts[]" 
                                   value="<?php echo esc_attr($person['id']); ?>"
                                   class="es-contact-checkbox">
                            <span class="es-contact-pill-label">
                                <?php if (!empty($person['featured_image'])) : ?>
                                    <img src="<?php echo esc_url($person['featured_image']); ?>" alt="" class="es-contact-pill-image">
                                <?php else : ?>
                                    <span class="es-contact-pill-placeholder"><span class="dashicons dashicons-businessperson"></span></span>
                                <?php endif; ?>
                                <span class="es-contact-pill-name"><?php echo esc_html($person['name']); ?></span>
                                <?php if (!empty($person['position'])) : ?>
                                    <span class="es-contact-pill-position"><?php echo esc_html($person['position']); ?></span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <p class="description" style="margin-top: 10px;">
                        <?php printf(
                            __('Select %s to display on this location. <a href="%s" target="_blank">Manage %s</a>', 'ensemble'),
                            strtolower($staff_label),
                            admin_url('admin.php?page=ensemble-staff'),
                            $staff_label
                        ); ?>
                    </p>
                </div>
            </div>
        </div>
        
        <style>
        #es-location-contact-selection {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        #es-location-contact-selection .es-contact-pill {
            display: flex;
            align-items: center;
            background: var(--es-surface-secondary, #383838);
            border: 1px solid var(--es-border, #404040);
            border-radius: 20px;
            padding: 4px 12px 4px 4px;
            cursor: pointer;
            transition: all 0.2s;
        }
        #es-location-contact-selection .es-contact-pill:hover {
            border-color: var(--es-primary, #3582c4);
        }
        #es-location-contact-selection .es-contact-pill.selected {
            background: var(--es-primary-light, rgba(53, 130, 196, 0.15));
            border-color: var(--es-primary, #3582c4);
        }
        #es-location-contact-selection .es-contact-pill input[type="checkbox"] {
            display: none;
        }
        #es-location-contact-selection .es-contact-pill-label {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        #es-location-contact-selection .es-contact-pill-image,
        #es-location-contact-selection .es-contact-pill-placeholder {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            object-fit: cover;
        }
        #es-location-contact-selection .es-contact-pill-placeholder {
            background: var(--es-surface, #2c2c2c);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        #es-location-contact-selection .es-contact-pill-placeholder .dashicons {
            font-size: 16px;
            width: 16px;
            height: 16px;
            color: var(--es-text-muted, #787878);
        }
        #es-location-contact-selection .es-contact-pill-name {
            font-weight: 500;
            color: var(--es-text, #e0e0e0);
        }
        #es-location-contact-selection .es-contact-pill-position {
            font-size: 11px;
            color: var(--es-text-secondary, #a0a0a0);
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Toggle contact selection on click
            $('#es-location-contact-selection').on('click', '.es-contact-pill', function(e) {
                e.preventDefault();
                var $pill = $(this);
                var $checkbox = $pill.find('.es-contact-checkbox');
                var isChecked = $checkbox.prop('checked');
                
                // Toggle
                $checkbox.prop('checked', !isChecked);
                $pill.toggleClass('selected', !isChecked);
            });
        });
        </script>
        <?php
    }
    
    /**
     * AJAX: Save event contacts (called from wizard save)
     */
    public function ajax_save_event_contacts() {
        check_ajax_referer('ensemble-wizard', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Permission denied', 'ensemble')));
        }
        
        $event_id = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;
        $contacts = isset($_POST['contacts']) ? array_map('absint', (array) $_POST['contacts']) : array();
        
        if ($event_id) {
            update_post_meta($event_id, '_es_event_contacts', $contacts);
        }
        
        wp_send_json_success();
    }
    
    // =========================================================================
    // Additional Shortcodes
    // =========================================================================
    
    /**
     * Shortcode: Display contacts for current/specific event
     * 
     * Usage: [ensemble_event_contacts] or [ensemble_event_contacts event_id="123"]
     * 
     * @param array $atts
     * @return string
     */
    public function shortcode_event_contacts($atts) {
        $atts = shortcode_atts(array(
            'event_id' => 0,
            'layout'   => 'inline', // inline, grid, list
            'title'    => '',
        ), $atts, 'ensemble_event_contacts');
        
        // Get event ID
        $event_id = absint($atts['event_id']);
        if (!$event_id) {
            global $post;
            if ($post) {
                $event_id = $post->ID;
            }
        }
        
        if (!$event_id) {
            return '';
        }
        
        // Get contacts
        $contact_ids = get_post_meta($event_id, '_es_event_contacts', true);
        
        if (empty($contact_ids) || !is_array($contact_ids)) {
            return '';
        }
        
        $staff = array();
        foreach ($contact_ids as $id) {
            $person = $this->staff_manager->get_staff($id);
            if ($person) {
                $staff[] = $person;
            }
        }
        
        if (empty($staff)) {
            return '';
        }
        
        // Determine title
        $title = !empty($atts['title']) ? $atts['title'] : $this->get_staff_label(true);
        
        // Use appropriate template based on layout
        $template = 'event-footer-contacts';
        if ($atts['layout'] === 'grid') {
            $template = 'staff-grid';
        } elseif ($atts['layout'] === 'list') {
            $template = 'staff-list';
        }
        
        return $this->load_template($template, array(
            'staff' => $staff,
            'title' => $title,
            'atts'  => $atts,
        ));
    }
}
