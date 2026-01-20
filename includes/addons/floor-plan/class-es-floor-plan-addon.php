<?php
/**
 * Floor Plan Pro Addon
 *
 * Interactive floor plan editor with drag & drop functionality.
 * Integrates with Reservations and Tickets addons for seat selection.
 *
 * @package Ensemble
 * @subpackage Addons/FloorPlan
 * @since 3.1.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * ES_Floor_Plan_Addon Class
 * 
 * Extends ES_Addon_Base for proper addon registration and lifecycle management
 */
class ES_Floor_Plan_Addon extends ES_Addon_Base {

    /**
     * Add-on configuration (required by ES_Addon_Base)
     */
    protected $slug = 'floor-plan';
    protected $name = 'Floor Plan Pro';
    protected $version = '1.0.0';

    /**
     * Singleton instance
     *
     * @var ES_Floor_Plan_Addon|null
     */
    private static $instance = null;

    /**
     * Post type name
     *
     * @var string
     */
    public $post_type = 'ensemble_floor_plan';

    /**
     * Get singleton instance
     *
     * @return ES_Floor_Plan_Addon
     */
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize add-on (called by ES_Addon_Base constructor)
     */
    protected function init() {
        $this->define_constants();
        $this->log('Floor Plan Pro add-on initialized');
    }

    /**
     * Define addon constants
     */
    private function define_constants() {
        if (!defined('ES_FLOOR_PLAN_VERSION')) {
            define('ES_FLOOR_PLAN_VERSION', $this->version);
        }
        if (!defined('ES_FLOOR_PLAN_PATH')) {
            define('ES_FLOOR_PLAN_PATH', plugin_dir_path(__FILE__));
        }
        if (!defined('ES_FLOOR_PLAN_URL')) {
            define('ES_FLOOR_PLAN_URL', plugin_dir_url(__FILE__));
        }
    }

    /**
     * Register hooks (called by ES_Addon_Base constructor)
     */
    protected function register_hooks() {
        // Register post type
        add_action('init', array($this, 'register_post_type'));
        
        // Admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'), 25);
        
        // Admin assets
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Frontend assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        
        // AJAX handlers
        add_action('wp_ajax_es_save_floor_plan', array($this, 'ajax_save_floor_plan'));
        add_action('wp_ajax_es_get_floor_plan', array($this, 'ajax_get_floor_plan'));
        add_action('wp_ajax_es_delete_floor_plan', array($this, 'ajax_delete_floor_plan'));
        add_action('wp_ajax_es_get_floor_plans', array($this, 'ajax_get_floor_plans'));
        add_action('wp_ajax_es_duplicate_floor_plan', array($this, 'ajax_duplicate_floor_plan'));
        
        // Frontend AJAX (for booking status)
        add_action('wp_ajax_es_get_floor_plan_status', array($this, 'ajax_get_floor_plan_status'));
        add_action('wp_ajax_nopriv_es_get_floor_plan_status', array($this, 'ajax_get_floor_plan_status'));
        
        // Register shortcode
        add_shortcode('ensemble_floor_plan', array($this, 'render_shortcode'));
        
        // Integration hooks
        add_action('ensemble_location_after_details', array($this, 'render_location_floor_plans'), 20);
        add_filter('ensemble_reservation_form_fields', array($this, 'add_reservation_floor_plan_field'), 10, 2);
        
        // Addon hooks
        if (class_exists('ES_Addon_Manager')) {
            ES_Addon_Manager::do_addon_hook('ensemble_floor_plan_init', $this);
        }
    }

    /**
     * Register custom post type
     */
    public function register_post_type() {
        $labels = array(
            'name'               => __('Floor Plans', 'ensemble'),
            'singular_name'      => __('Floor Plan', 'ensemble'),
            'menu_name'          => __('Floor Plans', 'ensemble'),
            'add_new'            => __('Add New', 'ensemble'),
            'add_new_item'       => __('Add New Floor Plan', 'ensemble'),
            'edit_item'          => __('Edit Floor Plan', 'ensemble'),
            'new_item'           => __('New Floor Plan', 'ensemble'),
            'view_item'          => __('View Floor Plan', 'ensemble'),
            'search_items'       => __('Search Floor Plans', 'ensemble'),
            'not_found'          => __('No floor plans found', 'ensemble'),
            'not_found_in_trash' => __('No floor plans found in trash', 'ensemble'),
        );

        $args = array(
            'labels'              => $labels,
            'public'              => false,
            'publicly_queryable'  => false,
            'show_ui'             => false, // We use custom UI
            'show_in_menu'        => false,
            'query_var'           => false,
            'rewrite'             => false,
            'capability_type'     => 'post',
            'has_archive'         => false,
            'hierarchical'        => false,
            'supports'            => array('title'),
        );

        register_post_type($this->post_type, $args);
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'ensemble',
            __('Floor Plans', 'ensemble'),
            __('Floor Plans', 'ensemble'),
            'manage_options',
            'ensemble-floor-plans',
            array($this, 'render_admin_page')
        );
    }

    /**
     * Enqueue admin assets
     *
     * @param string $hook Current admin page hook
     */
    public function enqueue_admin_assets($hook) {
        // Only on our admin page
        if (strpos($hook, 'ensemble-floor-plans') === false) {
            return;
        }

        // Konva.js for canvas
        wp_enqueue_script(
            'konva',
            'https://unpkg.com/konva@9/konva.min.js',
            array(),
            '9.3.6',
            true
        );

        // Floor Plan Editor JS
        wp_enqueue_script(
            'es-floor-plan-editor',
            ES_FLOOR_PLAN_URL . 'assets/floor-plan-editor.js',
            array('jquery', 'konva', 'wp-util'),
            ES_FLOOR_PLAN_VERSION,
            true
        );

        // Floor Plan Editor CSS
        wp_enqueue_style(
            'es-floor-plan-editor',
            ES_FLOOR_PLAN_URL . 'assets/floor-plan-editor.css',
            array(),
            ES_FLOOR_PLAN_VERSION
        );

        // WordPress media uploader
        wp_enqueue_media();

        // Localize script
        wp_localize_script('es-floor-plan-editor', 'esFloorPlan', array(
            'ajaxUrl'      => admin_url('admin-ajax.php'),
            'nonce'        => wp_create_nonce('ensemble_nonce'),
            'pluginUrl'    => ES_FLOOR_PLAN_URL,
            'strings'      => array(
                'confirmDelete'    => __('Are you sure you want to delete this floor plan?', 'ensemble'),
                'confirmRemove'    => __('Are you sure you want to remove this element?', 'ensemble'),
                'saved'            => __('Floor plan saved successfully!', 'ensemble'),
                'error'            => __('An error occurred. Please try again.', 'ensemble'),
                'selectBackground' => __('Select Background Image', 'ensemble'),
                'useImage'         => __('Use this image', 'ensemble'),
                'untitled'         => __('Untitled Floor Plan', 'ensemble'),
                'table'            => __('Table', 'ensemble'),
                'section'          => __('Section', 'ensemble'),
                'stage'            => __('Stage', 'ensemble'),
                'bar'              => __('Bar', 'ensemble'),
                'entrance'         => __('Entrance', 'ensemble'),
                'lounge'           => __('Lounge', 'ensemble'),
                'dancefloor'       => __('Dancefloor', 'ensemble'),
                'custom'           => __('Custom', 'ensemble'),
            ),
            'elementTypes' => $this->get_element_types(),
            'defaultSections' => $this->get_default_sections(),
        ));
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        // Only enqueue if shortcode is present or on relevant pages
        global $post;
        if (!$post || !has_shortcode($post->post_content, 'ensemble_floor_plan')) {
            return;
        }

        // Konva.js for canvas rendering
        wp_enqueue_script(
            'konva',
            'https://unpkg.com/konva@9/konva.min.js',
            array(),
            '9.3.6',
            true
        );

        // Frontend JS
        wp_enqueue_script(
            'es-floor-plan-frontend',
            ES_FLOOR_PLAN_URL . 'assets/floor-plan-frontend.js',
            array('jquery', 'konva'),
            ES_FLOOR_PLAN_VERSION,
            true
        );

        // Frontend CSS
        wp_enqueue_style(
            'es-floor-plan-frontend',
            ES_FLOOR_PLAN_URL . 'assets/floor-plan-frontend.css',
            array(),
            ES_FLOOR_PLAN_VERSION
        );

        // Localize script
        wp_localize_script('es-floor-plan-frontend', 'esFloorPlanFrontend', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('ensemble_public_nonce'),
            'strings' => array(
                'available'    => __('Available', 'ensemble'),
                'reserved'     => __('Reserved', 'ensemble'),
                'soldOut'      => __('Sold Out', 'ensemble'),
                'selectSeats'  => __('Select number of seats', 'ensemble'),
                'reserveNow'   => __('Reserve Now', 'ensemble'),
                'perPerson'    => __('per person', 'ensemble'),
                'seats'        => __('seats', 'ensemble'),
            ),
        ));
    }

    /**
     * Get available element types
     *
     * @return array
     */
    public function get_element_types() {
        return array(
            'table' => array(
                'label'    => __('Table', 'ensemble'),
                'icon'     => 'dashicons-groups',
                'bookable' => true,
                'shapes'   => array('round', 'square', 'rectangle'),
                'defaults' => array(
                    'width'  => 60,
                    'height' => 60,
                    'seats'  => 8,
                    'shape'  => 'round',
                ),
            ),
            'section' => array(
                'label'    => __('Section/Area', 'ensemble'),
                'icon'     => 'dashicons-screenoptions',
                'bookable' => true,
                'shapes'   => array('rectangle', 'polygon'),
                'defaults' => array(
                    'width'    => 150,
                    'height'   => 100,
                    'capacity' => 50,
                ),
            ),
            'stage' => array(
                'label'    => __('Stage', 'ensemble'),
                'icon'     => 'dashicons-megaphone',
                'bookable' => false,
                'shapes'   => array('rectangle', 'semicircle'),
                'defaults' => array(
                    'width'  => 300,
                    'height' => 80,
                ),
            ),
            'bar' => array(
                'label'    => __('Bar/Counter', 'ensemble'),
                'icon'     => 'dashicons-coffee',
                'bookable' => false,
                'shapes'   => array('rectangle', 'l-shape'),
                'defaults' => array(
                    'width'  => 200,
                    'height' => 40,
                ),
            ),
            'entrance' => array(
                'label'    => __('Entrance', 'ensemble'),
                'icon'     => 'dashicons-admin-home',
                'bookable' => false,
                'shapes'   => array('rectangle'),
                'defaults' => array(
                    'width'  => 60,
                    'height' => 30,
                ),
            ),
            'lounge' => array(
                'label'    => __('Lounge', 'ensemble'),
                'icon'     => 'dashicons-businessman',
                'bookable' => true,
                'shapes'   => array('rectangle', 'round'),
                'defaults' => array(
                    'width'    => 120,
                    'height'   => 80,
                    'capacity' => 10,
                ),
            ),
            'dancefloor' => array(
                'label'    => __('Dancefloor', 'ensemble'),
                'icon'     => 'dashicons-format-audio',
                'bookable' => false,
                'shapes'   => array('rectangle', 'round'),
                'defaults' => array(
                    'width'  => 200,
                    'height' => 200,
                ),
            ),
            'amenity' => array(
                'label'    => __('Amenity', 'ensemble'),
                'icon'     => 'dashicons-admin-generic',
                'bookable' => false,
                'shapes'   => array('rectangle'),
                'defaults' => array(
                    'width'  => 40,
                    'height' => 40,
                ),
                'subtypes' => array(
                    'restroom' => __('Restroom', 'ensemble'),
                    'wardrobe' => __('Wardrobe', 'ensemble'),
                    'exit'     => __('Emergency Exit', 'ensemble'),
                ),
            ),
            'custom' => array(
                'label'    => __('Custom', 'ensemble'),
                'icon'     => 'dashicons-edit',
                'bookable' => false,
                'shapes'   => array('rectangle', 'round', 'polygon'),
                'defaults' => array(
                    'width'  => 80,
                    'height' => 80,
                ),
            ),
        );
    }

    /**
     * Get default sections
     *
     * @return array
     */
    public function get_default_sections() {
        return array(
            array(
                'id'            => 'vip',
                'name'          => __('VIP', 'ensemble'),
                'color'         => '#D4AF37',
                'default_price' => 150,
            ),
            array(
                'id'            => 'premium',
                'name'          => __('Premium', 'ensemble'),
                'color'         => '#8B5CF6',
                'default_price' => 100,
            ),
            array(
                'id'            => 'standard',
                'name'          => __('Standard', 'ensemble'),
                'color'         => '#3B82F6',
                'default_price' => 50,
            ),
            array(
                'id'            => 'standing',
                'name'          => __('Standing', 'ensemble'),
                'color'         => '#10B981',
                'default_price' => 25,
            ),
        );
    }

    /**
     * Render admin page
     */
    public function render_admin_page() {
        include ES_FLOOR_PLAN_PATH . 'templates/admin-floor-plans.php';
    }

    /**
     * AJAX: Save floor plan
     */
    public function ajax_save_floor_plan() {
        check_ajax_referer('ensemble_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'ensemble')));
            return;
        }

        $floor_plan_id = isset($_POST['floor_plan_id']) ? intval($_POST['floor_plan_id']) : 0;
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : __('Untitled Floor Plan', 'ensemble');
        
        // Prepare post data
        $post_data = array(
            'post_title'  => $title,
            'post_type'   => $this->post_type,
            'post_status' => 'publish',
        );

        if ($floor_plan_id > 0) {
            $post_data['ID'] = $floor_plan_id;
            $result = wp_update_post($post_data, true);
        } else {
            $result = wp_insert_post($post_data, true);
        }

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
            return;
        }

        $floor_plan_id = $result;

        // Save floor plan data
        $floor_plan_data = array(
            'canvas' => array(
                'width'      => isset($_POST['canvas_width']) ? intval($_POST['canvas_width']) : 1200,
                'height'     => isset($_POST['canvas_height']) ? intval($_POST['canvas_height']) : 800,
                'background' => isset($_POST['background']) ? esc_url_raw($_POST['background']) : '',
                'grid'       => isset($_POST['show_grid']) ? (bool) $_POST['show_grid'] : true,
                'grid_size'  => isset($_POST['grid_size']) ? intval($_POST['grid_size']) : 20,
            ),
            'sections' => array(),
            'elements' => array(),
        );

        // Parse sections
        if (isset($_POST['sections']) && !empty($_POST['sections'])) {
            $sections_json = stripslashes($_POST['sections']);
            $sections_decoded = json_decode($sections_json, true);
            if (is_array($sections_decoded)) {
                $floor_plan_data['sections'] = $this->sanitize_sections($sections_decoded);
            }
        }

        // Parse elements
        if (isset($_POST['elements']) && !empty($_POST['elements'])) {
            $elements_json = stripslashes($_POST['elements']);
            $elements_decoded = json_decode($elements_json, true);
            if (is_array($elements_decoded)) {
                $floor_plan_data['elements'] = $this->sanitize_elements($elements_decoded);
            }
        }

        // Save meta
        update_post_meta($floor_plan_id, '_floor_plan_data', $floor_plan_data);

        // Save linked location if provided
        if (isset($_POST['location_id'])) {
            $location_id = intval($_POST['location_id']);
            if ($location_id > 0) {
                update_post_meta($floor_plan_id, '_linked_location', $location_id);
            } else {
                delete_post_meta($floor_plan_id, '_linked_location');
            }
        }

        wp_send_json_success(array(
            'message'       => __('Floor plan saved successfully!', 'ensemble'),
            'floor_plan_id' => $floor_plan_id,
        ));
    }

    /**
     * Sanitize sections data
     *
     * @param array $sections Raw sections data
     * @return array Sanitized sections
     */
    private function sanitize_sections($sections) {
        $sanitized = array();
        
        foreach ($sections as $section) {
            $sanitized[] = array(
                'id'            => sanitize_key($section['id'] ?? uniqid('section_')),
                'name'          => sanitize_text_field($section['name'] ?? ''),
                'color'         => sanitize_hex_color($section['color'] ?? '#3B82F6'),
                'default_price' => floatval($section['default_price'] ?? 0),
            );
        }
        
        return $sanitized;
    }

    /**
     * Sanitize elements data
     *
     * @param array $elements Raw elements data
     * @return array Sanitized elements
     */
    private function sanitize_elements($elements) {
        $sanitized = array();
        $valid_types = array_keys($this->get_element_types());
        
        foreach ($elements as $element) {
            $type = sanitize_key($element['type'] ?? 'custom');
            if (!in_array($type, $valid_types)) {
                $type = 'custom';
            }
            
            $sanitized[] = array(
                'id'          => sanitize_key($element['id'] ?? uniqid('el_')),
                'type'        => $type,
                'x'           => floatval($element['x'] ?? 0),
                'y'           => floatval($element['y'] ?? 0),
                'width'       => floatval($element['width'] ?? 60),
                'height'      => floatval($element['height'] ?? 60),
                'rotation'    => floatval($element['rotation'] ?? 0),
                'shape'       => sanitize_key($element['shape'] ?? 'rectangle'),
                'label'       => sanitize_text_field($element['label'] ?? ''),
                'number'      => intval($element['number'] ?? 0),
                'seats'       => intval($element['seats'] ?? 0),
                'capacity'    => intval($element['capacity'] ?? 0),
                'section_id'  => sanitize_key($element['section_id'] ?? ''),
                'bookable'    => (bool) ($element['bookable'] ?? false),
                'price'       => floatval($element['price'] ?? 0),
                'accessible'  => (bool) ($element['accessible'] ?? false),
                'description' => sanitize_textarea_field($element['description'] ?? ''),
                'subtype'     => sanitize_key($element['subtype'] ?? ''),
            );
        }
        
        return $sanitized;
    }

    /**
     * AJAX: Get floor plan
     */
    public function ajax_get_floor_plan() {
        check_ajax_referer('ensemble_nonce', 'nonce');

        $floor_plan_id = isset($_POST['floor_plan_id']) ? intval($_POST['floor_plan_id']) : 0;

        if (!$floor_plan_id) {
            wp_send_json_error(array('message' => __('Invalid floor plan ID', 'ensemble')));
            return;
        }

        $floor_plan = $this->get_floor_plan($floor_plan_id);

        if (!$floor_plan) {
            wp_send_json_error(array('message' => __('Floor plan not found', 'ensemble')));
            return;
        }

        wp_send_json_success($floor_plan);
    }

    /**
     * Get floor plan data
     *
     * @param int $floor_plan_id Floor plan ID
     * @return array|false Floor plan data or false
     */
    public function get_floor_plan($floor_plan_id) {
        $post = get_post($floor_plan_id);

        if (!$post || $post->post_type !== $this->post_type) {
            return false;
        }

        $data = get_post_meta($floor_plan_id, '_floor_plan_data', true);
        $linked_location = get_post_meta($floor_plan_id, '_linked_location', true);

        // Ensure data is an array
        if (!is_array($data)) {
            $data = array();
        }

        // Default canvas values
        $default_canvas = array(
            'width'      => 1200,
            'height'     => 800,
            'background' => '',
            'grid'       => true,
            'grid_size'  => 20,
        );

        // Merge canvas with defaults
        $canvas = isset($data['canvas']) && is_array($data['canvas']) 
            ? array_merge($default_canvas, $data['canvas']) 
            : $default_canvas;

        // Get sections and ensure default_price exists
        $sections = isset($data['sections']) && is_array($data['sections']) 
            ? $data['sections'] 
            : $this->get_default_sections();
        
        foreach ($sections as &$section) {
            if (!isset($section['default_price'])) {
                $section['default_price'] = 0;
            }
        }
        unset($section);

        return array(
            'id'              => $floor_plan_id,
            'title'           => $post->post_title,
            'linked_location' => $linked_location ? intval($linked_location) : null,
            'canvas'          => $canvas,
            'sections'        => $sections,
            'elements'        => isset($data['elements']) && is_array($data['elements']) ? $data['elements'] : array(),
        );
    }

    /**
     * AJAX: Delete floor plan
     */
    public function ajax_delete_floor_plan() {
        check_ajax_referer('ensemble_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'ensemble')));
            return;
        }

        $floor_plan_id = isset($_POST['floor_plan_id']) ? intval($_POST['floor_plan_id']) : 0;

        if (!$floor_plan_id) {
            wp_send_json_error(array('message' => __('Invalid floor plan ID', 'ensemble')));
            return;
        }

        $result = wp_delete_post($floor_plan_id, true);

        if (!$result) {
            wp_send_json_error(array('message' => __('Could not delete floor plan', 'ensemble')));
            return;
        }

        wp_send_json_success(array('message' => __('Floor plan deleted successfully!', 'ensemble')));
    }

    /**
     * AJAX: Get all floor plans
     */
    public function ajax_get_floor_plans() {
        check_ajax_referer('ensemble_nonce', 'nonce');

        $args = array(
            'post_type'      => $this->post_type,
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        );

        // Filter by location if provided
        if (isset($_POST['location_id']) && intval($_POST['location_id']) > 0) {
            $args['meta_query'] = array(
                array(
                    'key'   => '_linked_location',
                    'value' => intval($_POST['location_id']),
                ),
            );
        }

        $posts = get_posts($args);
        $floor_plans = array();

        foreach ($posts as $post) {
            $data = get_post_meta($post->ID, '_floor_plan_data', true);
            $linked_location = get_post_meta($post->ID, '_linked_location', true);
            
            $element_count = isset($data['elements']) ? count($data['elements']) : 0;
            $bookable_count = 0;
            $total_capacity = 0;
            
            if (isset($data['elements'])) {
                foreach ($data['elements'] as $element) {
                    if (!empty($element['bookable'])) {
                        $bookable_count++;
                        $total_capacity += intval($element['seats'] ?? $element['capacity'] ?? 0);
                    }
                }
            }

            $floor_plans[] = array(
                'id'              => $post->ID,
                'title'           => $post->post_title,
                'linked_location' => $linked_location ? intval($linked_location) : null,
                'location_name'   => $linked_location ? get_the_title($linked_location) : '',
                'element_count'   => $element_count,
                'bookable_count'  => $bookable_count,
                'total_capacity'  => $total_capacity,
                'thumbnail'       => $data['canvas']['background'] ?? '',
                'modified'        => get_the_modified_date('Y-m-d H:i:s', $post->ID),
            );
        }

        wp_send_json_success($floor_plans);
    }

    /**
     * AJAX: Duplicate floor plan
     */
    public function ajax_duplicate_floor_plan() {
        check_ajax_referer('ensemble_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'ensemble')));
            return;
        }

        $floor_plan_id = isset($_POST['floor_plan_id']) ? intval($_POST['floor_plan_id']) : 0;

        if (!$floor_plan_id) {
            wp_send_json_error(array('message' => __('Invalid floor plan ID', 'ensemble')));
            return;
        }

        $original = get_post($floor_plan_id);
        if (!$original || $original->post_type !== $this->post_type) {
            wp_send_json_error(array('message' => __('Floor plan not found', 'ensemble')));
            return;
        }

        // Create duplicate
        $new_post = array(
            'post_title'  => sprintf(__('%s (Copy)', 'ensemble'), $original->post_title),
            'post_type'   => $this->post_type,
            'post_status' => 'publish',
        );

        $new_id = wp_insert_post($new_post);

        if (is_wp_error($new_id)) {
            wp_send_json_error(array('message' => $new_id->get_error_message()));
            return;
        }

        // Copy meta
        $floor_plan_data = get_post_meta($floor_plan_id, '_floor_plan_data', true);
        $linked_location = get_post_meta($floor_plan_id, '_linked_location', true);

        if ($floor_plan_data) {
            // Generate new IDs for elements
            if (isset($floor_plan_data['elements'])) {
                foreach ($floor_plan_data['elements'] as &$element) {
                    $element['id'] = uniqid('el_');
                }
            }
            update_post_meta($new_id, '_floor_plan_data', $floor_plan_data);
        }

        if ($linked_location) {
            update_post_meta($new_id, '_linked_location', $linked_location);
        }

        wp_send_json_success(array(
            'message'       => __('Floor plan duplicated successfully!', 'ensemble'),
            'floor_plan_id' => $new_id,
        ));
    }

    /**
     * AJAX: Get floor plan status (for frontend booking)
     */
    public function ajax_get_floor_plan_status() {
        error_log('Floor Plan Status AJAX - Start');
        
        // Verify nonce (public or authenticated)
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'ensemble_public_nonce') && 
            !wp_verify_nonce($_POST['nonce'] ?? '', 'ensemble_nonce')) {
            error_log('Floor Plan Status AJAX - Invalid nonce');
            wp_send_json_error(array('message' => __('Invalid security token', 'ensemble')));
            return;
        }

        $floor_plan_id = isset($_POST['floor_plan_id']) ? intval($_POST['floor_plan_id']) : 0;
        $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
        
        error_log('Floor Plan Status AJAX - Floor Plan ID: ' . $floor_plan_id . ', Event ID: ' . $event_id);

        if (!$floor_plan_id) {
            wp_send_json_error(array('message' => __('Invalid floor plan ID', 'ensemble')));
            return;
        }

        $floor_plan = $this->get_floor_plan($floor_plan_id);
        if (!$floor_plan) {
            error_log('Floor Plan Status AJAX - Floor plan not found');
            wp_send_json_error(array('message' => __('Floor plan not found', 'ensemble')));
            return;
        }
        
        error_log('Floor Plan Status AJAX - Floor plan loaded, elements: ' . count($floor_plan['elements'] ?? array()));

        // Get booking status for each element
        $element_status = array();
        
        error_log('Floor Plan Status AJAX - ES_Reservations_Addon exists: ' . (class_exists('ES_Reservations_Addon') ? 'YES' : 'NO'));
        error_log('Floor Plan Status AJAX - ES_Booking_Engine_Addon exists: ' . (class_exists('ES_Booking_Engine_Addon') ? 'YES' : 'NO'));
        
        // Try Booking Engine Addon first (has get_booked_elements_with_guests method)
        if ($event_id && class_exists('ES_Booking_Engine_Addon')) {
            try {
                $booking_addon = ES_Booking_Engine_Addon::instance();
                // get_booked_elements_with_guests returns array of element_id => total_guests
                $booked_guests = $booking_addon->get_booked_elements_with_guests($event_id, $floor_plan_id);
                
                error_log('Floor Plan Status AJAX - Booked guests: ' . print_r($booked_guests, true));
                error_log('Floor Plan Status AJAX - Processing ' . count($floor_plan['elements']) . ' elements');
                
                foreach ($floor_plan['elements'] as $element) {
                    if (empty($element['bookable'])) {
                        continue;
                    }
                    
                    $capacity = intval($element['seats'] ?? $element['capacity'] ?? 0);
                    $reserved = isset($booked_guests[$element['id']]) ? intval($booked_guests[$element['id']]) : 0;
                    
                    error_log('Floor Plan Status AJAX - Element ' . $element['id'] . ': capacity=' . $capacity . ', reserved=' . $reserved);
                    
                    $element_status[$element['id']] = array(
                        'capacity'  => $capacity,
                        'reserved'  => $reserved,
                        'available' => max(0, $capacity - $reserved),
                        'status'    => $reserved >= $capacity ? 'sold_out' : ($reserved > 0 ? 'partial' : 'available'),
                    );
                }
                
                error_log('Floor Plan Status AJAX - Booking Engine processing complete');
            } catch (Exception $e) {
                error_log('Floor Plan Status AJAX - Booking Engine error: ' . $e->getMessage());
            }
        } elseif ($event_id && class_exists('ES_Reservations_Addon')) {
            // Fallback to Reservations addon
            try {
                $reservations = ES_Reservations_Addon::instance()->get_event_reservations($event_id);
                
                foreach ($floor_plan['elements'] as $element) {
                    if (empty($element['bookable'])) {
                        continue;
                    }
                    
                    $reserved = 0;
                    $capacity = intval($element['seats'] ?? $element['capacity'] ?? 0);
                    
                    foreach ($reservations as $reservation) {
                        if (($reservation['floor_plan_element'] ?? '') === $element['id']) {
                            $reserved += intval($reservation['seats'] ?? 1);
                        }
                    }
                    
                    $element_status[$element['id']] = array(
                        'capacity'  => $capacity,
                        'reserved'  => $reserved,
                        'available' => max(0, $capacity - $reserved),
                        'status'    => $reserved >= $capacity ? 'sold_out' : ($reserved > 0 ? 'partial' : 'available'),
                    );
                }
            } catch (Exception $e) {
                error_log('Floor Plan Status AJAX - Reservations error: ' . $e->getMessage());
            }
        } else {
            // No booking addon - return all as available
            foreach ($floor_plan['elements'] as $element) {
                if (empty($element['bookable'])) {
                    continue;
                }
                
                $capacity = intval($element['seats'] ?? $element['capacity'] ?? 0);
                
                $element_status[$element['id']] = array(
                    'capacity'  => $capacity,
                    'reserved'  => 0,
                    'available' => $capacity,
                    'status'    => 'available',
                );
            }
        }
        
        error_log('Floor Plan Status AJAX - Sending response with ' . count($element_status) . ' elements');

        wp_send_json_success(array(
            'floor_plan'     => $floor_plan,
            'element_status' => $element_status,
        ));
    }

    /**
     * Render shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function render_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id'        => 0,
            'event_id'  => 0,
            'bookable'  => 'false',
            'mode'      => 'auto', // auto, display, reservation, ticket
            'sections'  => '',
            'height'    => 'auto',
            'class'     => '',
        ), $atts, 'ensemble_floor_plan');

        $floor_plan_id = intval($atts['id']);
        $event_id = intval($atts['event_id']);
        
        error_log('Floor Plan Shortcode - ID: ' . $floor_plan_id . ', Event ID: ' . $event_id);
        
        if (!$floor_plan_id) {
            error_log('Floor Plan Shortcode - ERROR: No floor plan ID');
            return '<p class="es-floor-plan-error">' . __('No floor plan specified.', 'ensemble') . '</p>';
        }

        $floor_plan = $this->get_floor_plan($floor_plan_id);
        
        error_log('Floor Plan Shortcode - Floor Plan Data exists: ' . ($floor_plan ? 'YES' : 'NO'));
        if ($floor_plan) {
            error_log('Floor Plan Shortcode - Elements count: ' . count($floor_plan['elements'] ?? array()));
        }
        
        if (!$floor_plan) {
            error_log('Floor Plan Shortcode - ERROR: Floor plan not found');
            return '<p class="es-floor-plan-error">' . __('Floor plan not found.', 'ensemble') . '</p>';
        }

        // Auto-detect mode from event's booking_mode
        $mode = $atts['mode'];
        if ($mode === 'auto' && $event_id) {
            $booking_mode = get_post_meta($event_id, '_booking_mode', true);
            if ($booking_mode === 'ticket') {
                $mode = 'ticket';
                $atts['bookable'] = 'true'; // Enable interaction
            } elseif ($booking_mode === 'reservation') {
                $mode = 'reservation';
                $atts['bookable'] = 'true';
            } else {
                $mode = 'display';
            }
        } elseif ($mode === 'auto') {
            $mode = $atts['bookable'] === 'true' ? 'reservation' : 'display';
        }
        
        $atts['mode'] = $mode;
        
        error_log('Floor Plan Shortcode - Mode: ' . $mode);

        // Check template path
        $template_path = ES_FLOOR_PLAN_PATH . 'templates/frontend-floor-plan.php';
        error_log('Floor Plan Shortcode - Template path: ' . $template_path);
        error_log('Floor Plan Shortcode - Template exists: ' . (file_exists($template_path) ? 'YES' : 'NO'));

        // Start output buffering
        ob_start();
        
        include $template_path;
        
        $output = ob_get_clean();
        error_log('Floor Plan Shortcode - Output length: ' . strlen($output));
        
        return $output;
    }

    /**
     * Render floor plans on location page
     *
     * @param int $location_id Location ID
     */
    public function render_location_floor_plans($location_id) {
        $floor_plans = get_posts(array(
            'post_type'      => $this->post_type,
            'posts_per_page' => -1,
            'meta_query'     => array(
                array(
                    'key'   => '_linked_location',
                    'value' => $location_id,
                ),
            ),
        ));

        if (empty($floor_plans)) {
            return;
        }

        echo '<div class="es-location-floor-plans">';
        echo '<h3>' . __('Floor Plans', 'ensemble') . '</h3>';
        
        foreach ($floor_plans as $fp) {
            echo do_shortcode('[ensemble_floor_plan id="' . $fp->ID . '"]');
        }
        
        echo '</div>';
    }

    /**
     * Add floor plan field to reservation form
     *
     * @param array $fields Form fields
     * @param int   $event_id Event ID
     * @return array Modified fields
     */
    public function add_reservation_floor_plan_field($fields, $event_id) {
        // Get event's location
        $location_id = get_post_meta($event_id, 'es_event_location', true);
        if (!$location_id) {
            return $fields;
        }

        // Get floor plans for this location
        $floor_plans = get_posts(array(
            'post_type'      => $this->post_type,
            'posts_per_page' => -1,
            'meta_query'     => array(
                array(
                    'key'   => '_linked_location',
                    'value' => $location_id,
                ),
            ),
        ));

        if (empty($floor_plans)) {
            return $fields;
        }

        // Build options from floor plan elements
        $options = array('' => __('Select area...', 'ensemble'));
        
        foreach ($floor_plans as $fp) {
            $data = get_post_meta($fp->ID, '_floor_plan_data', true);
            if (!isset($data['elements'])) {
                continue;
            }

            foreach ($data['elements'] as $element) {
                if (empty($element['bookable'])) {
                    continue;
                }

                $label = $element['label'] ?: ($element['number'] ? sprintf(__('Table %d', 'ensemble'), $element['number']) : ucfirst($element['type']));
                
                if (!empty($element['section_id']) && isset($data['sections'])) {
                    foreach ($data['sections'] as $section) {
                        if ($section['id'] === $element['section_id']) {
                            $label = $section['name'] . ' - ' . $label;
                            break;
                        }
                    }
                }

                $options[$fp->ID . ':' . $element['id']] = $label;
            }
        }

        // Add field
        $fields['floor_plan_element'] = array(
            'type'     => 'select',
            'label'    => __('Area/Table', 'ensemble'),
            'options'  => $options,
            'required' => false,
            'priority' => 25,
        );

        return $fields;
    }

    /**
     * Get addon URL
     *
     * @return string
     */
    public function get_addon_url() {
        return ES_FLOOR_PLAN_URL;
    }

    /**
     * Get addon path
     *
     * @return string
     */
    public function get_addon_path() {
        return ES_FLOOR_PLAN_PATH;
    }
    
    /**
     * Render settings page for addon modal
     * 
     * @return string
     */
    public function render_settings() {
        return $this->load_template('settings', array(
            'settings' => $this->settings,
        ));
    }
    
    /**
     * Sanitize settings
     * 
     * @param array $settings
     * @return array
     */
    public function sanitize_settings($settings) {
        $valid_labels = array('floor_plan', 'seating_plan', 'venue_map', 'room_layout', 'table_plan', 'area_overview', 'custom');
        
        return array(
            'label_style'  => isset($settings['label_style']) && in_array($settings['label_style'], $valid_labels) 
                ? $settings['label_style'] 
                : 'floor_plan',
            'custom_label' => isset($settings['custom_label']) 
                ? sanitize_text_field($settings['custom_label']) 
                : '',
        );
    }
}

/**
 * Initialize addon - DO NOT call directly, let ES_Addon_Manager handle initialization
 * 
 * @return ES_Floor_Plan_Addon
 */
function es_floor_plan_addon() {
    return ES_Floor_Plan_Addon::instance();
}

/**
 * Get the floor plan label based on settings
 * 
 * @return string The localized floor plan label
 */
function ensemble_get_floor_plan_label() {
    // Try addon settings first (from addon modal), then fallback
    $settings = get_option('ensemble_addon_floor-plan_settings', array());
    if (empty($settings)) {
        $settings = get_option('ensemble_floor_plan_settings', array());
    }
    
    $label_key = $settings['label_style'] ?? 'floor_plan';
    
    // If custom, return the custom value
    if ($label_key === 'custom' && !empty($settings['custom_label'])) {
        return $settings['custom_label'];
    }
    
    // Predefined labels
    $labels = array(
        'floor_plan'    => __('Floor Plan', 'ensemble'),
        'seating_plan'  => __('Seating Plan', 'ensemble'),
        'venue_map'     => __('Venue Map', 'ensemble'),
        'room_layout'   => __('Room Layout', 'ensemble'),
        'table_plan'    => __('Table Plan', 'ensemble'),
        'area_overview' => __('Area Overview', 'ensemble'),
    );
    
    return $labels[$label_key] ?? $labels['floor_plan'];
}

/**
 * Get the floor plan description/hint text based on settings
 * 
 * @return string The localized hint text
 */
function ensemble_get_floor_plan_hint() {
    // Try addon settings first (from addon modal), then fallback
    $settings = get_option('ensemble_addon_floor-plan_settings', array());
    if (empty($settings)) {
        $settings = get_option('ensemble_floor_plan_settings', array());
    }
    
    $label_key = $settings['label_style'] ?? 'floor_plan';
    
    // Predefined hints
    $hints = array(
        'floor_plan'    => __('Interactive floor plan available', 'ensemble'),
        'seating_plan'  => __('Interactive seating plan available', 'ensemble'),
        'venue_map'     => __('Interactive venue map available', 'ensemble'),
        'room_layout'   => __('Interactive room layout available', 'ensemble'),
        'table_plan'    => __('Interactive table plan available', 'ensemble'),
        'area_overview' => __('Interactive area overview available', 'ensemble'),
        'custom'        => __('Interactive plan available', 'ensemble'),
    );
    
    return $hints[$label_key] ?? $hints['floor_plan'];
}

// Note: Initialization is handled by ES_Addon_Manager when addon is activated
// Do not call es_floor_plan_addon() here - it will be called by the manager
