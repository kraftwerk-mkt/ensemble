<?php
/**
 * Tickets Pro Addon
 *
 * Paid ticket sales with payment gateway integration.
 * Extends Booking Engine with payment processing capabilities.
 *
 * @package Ensemble
 * @subpackage Addons/TicketsPro
 * @since 3.1.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * ES_Tickets_Pro_Addon Class
 */
class ES_Tickets_Pro_Addon extends ES_Addon_Base {

    /**
     * Add-on configuration
     */
    protected $slug = 'tickets-pro';
    protected $name = 'Tickets Pro';
    protected $version = '1.0.0';

    /**
     * Singleton instance
     *
     * @var ES_Tickets_Pro_Addon|null
     */
    private static $instance = null;

    /**
     * Payment gateway manager
     *
     * @var ES_Payment_Gateway_Manager|null
     */
    public $gateways = null;

    /**
     * Get singleton instance
     *
     * @return ES_Tickets_Pro_Addon
     */
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize add-on (called by ES_Addon_Base)
     */
    protected function init() {
        // Check dependencies
        if (!$this->check_dependencies()) {
            return;
        }
        
        // Load dependencies
        $this->load_dependencies();
        
        // Create tables
        $this->maybe_create_tables();
        
        $this->log('Tickets Pro add-on initialized');
    }

    /**
     * Check required dependencies
     *
     * @return bool
     */
    private function check_dependencies() {
        // Check if Booking Engine is active
        if (!class_exists('ES_Booking_Engine_Addon')) {
            add_action('admin_notices', array($this, 'notice_missing_booking_engine'));
            return false;
        }
        
        return true;
    }

    /**
     * Admin notice: Missing Booking Engine
     */
    public function notice_missing_booking_engine() {
        ?>
        <div class="notice notice-error">
            <p>
                <strong><?php _e('Ensemble Tickets Pro', 'ensemble'); ?>:</strong>
                <?php _e('This addon requires the Booking Engine addon to be active.', 'ensemble'); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Load required files
     */
    private function load_dependencies() {
        $path = $this->get_addon_path();
        
        // Payment Gateway System
        if (file_exists($path . 'class-es-payment-gateway.php')) {
            require_once $path . 'class-es-payment-gateway.php';
        }
        if (file_exists($path . 'class-es-payment-gateway-manager.php')) {
            require_once $path . 'class-es-payment-gateway-manager.php';
        }
        
        // Ticket Category Management
        if (file_exists($path . 'class-es-ticket-category.php')) {
            require_once $path . 'class-es-ticket-category.php';
        }
        
        // Initialize gateway manager
        if (class_exists('ES_Payment_Gateway_Manager')) {
            $this->gateways = ES_Payment_Gateway_Manager::instance();
        }
    }

    /**
     * Register hooks (called by ES_Addon_Base)
     */
    protected function register_hooks() {
        // Admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'), 26);
        
        // Admin assets
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Frontend assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        
        // AJAX handlers - Admin
        add_action('wp_ajax_es_save_payment_gateway', array($this, 'ajax_save_gateway_settings'));
        add_action('wp_ajax_es_test_payment_gateway', array($this, 'ajax_test_gateway'));
        add_action('wp_ajax_es_save_ticket_category', array($this, 'ajax_save_ticket_category'));
        add_action('wp_ajax_es_save_ticket_category_central', array($this, 'ajax_save_ticket_category_central'));
        add_action('wp_ajax_es_delete_ticket_category', array($this, 'ajax_delete_ticket_category'));
        add_action('wp_ajax_es_get_ticket_categories', array($this, 'ajax_get_ticket_categories'));
        add_action('wp_ajax_es_get_ticket_category', array($this, 'ajax_get_ticket_category'));
        add_action('wp_ajax_es_duplicate_ticket_category', array($this, 'ajax_duplicate_ticket_category'));
        add_action('wp_ajax_es_save_tickets_pro_settings', array($this, 'ajax_save_settings'));
        add_action('wp_ajax_es_get_ticket_template', array($this, 'ajax_get_ticket_template'));
        add_action('wp_ajax_es_save_ticket_template', array($this, 'ajax_save_ticket_template'));
        add_action('wp_ajax_es_delete_ticket_template', array($this, 'ajax_delete_ticket_template'));
        add_action('wp_ajax_es_duplicate_ticket_template', array($this, 'ajax_duplicate_ticket_template'));
        add_action('wp_ajax_es_get_ticket_templates', array($this, 'ajax_get_ticket_templates'));
        add_action('wp_ajax_es_import_ticket_templates', array($this, 'ajax_import_ticket_templates'));
        
        // AJAX handlers - Frontend
        add_action('wp_ajax_es_process_ticket_payment', array($this, 'ajax_process_payment'));
        add_action('wp_ajax_nopriv_es_process_ticket_payment', array($this, 'ajax_process_payment'));
        add_action('wp_ajax_es_get_ticket_availability', array($this, 'ajax_get_availability'));
        add_action('wp_ajax_nopriv_es_get_ticket_availability', array($this, 'ajax_get_availability'));
        add_action('wp_ajax_es_create_ticket_booking', array($this, 'ajax_create_ticket_booking'));
        add_action('wp_ajax_nopriv_es_create_ticket_booking', array($this, 'ajax_create_ticket_booking'));
        
        // Payment handlers
        add_action('ensemble_payment_return', array($this, 'handle_payment_return'), 10, 2);
        add_action('ensemble_payment_cancelled', array($this, 'handle_payment_cancelled'), 10, 2);
        
        // Filter return URLs
        add_filter('ensemble_payment_return_url', array($this, 'get_payment_return_url'), 10, 3);
        add_filter('ensemble_payment_cancel_url', array($this, 'get_payment_cancel_url'), 10, 3);
        
        // Booking Engine integration
        add_action('ensemble_wizard_tickets_cards', array($this, 'render_wizard_ticket_categories'), 20);
        add_filter('ensemble_wizard_event_data', array($this, 'add_wizard_event_data'), 20, 2);
        add_action('ensemble_wizard_save_event', array($this, 'save_wizard_event_data'), 20, 2);
        
        // Direct save via save_post hook (more reliable than wizard action)
        add_action('save_post', array($this, 'save_tickets_on_post_save'), 25, 2);
        
        // Shortcodes
        add_shortcode('ensemble_ticket_form', array($this, 'shortcode_ticket_form'));
        add_shortcode('es_tickets', array($this, 'shortcode_ticket_form'));
        
        // REST API
        add_action('rest_api_init', array($this, 'register_rest_routes'));
    }

    /**
     * Create database tables
     */
    private function maybe_create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Ticket categories table
        $table_categories = $wpdb->prefix . 'ensemble_ticket_categories';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_categories'") !== $table_categories) {
            $sql_categories = "CREATE TABLE $table_categories (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                event_id bigint(20) unsigned NOT NULL,
                name varchar(255) NOT NULL,
                description text DEFAULT NULL,
                price decimal(10,2) NOT NULL DEFAULT 0.00,
                currency varchar(3) NOT NULL DEFAULT 'EUR',
                capacity int(11) DEFAULT NULL,
                sold int(11) NOT NULL DEFAULT 0,
                floor_plan_id bigint(20) unsigned DEFAULT NULL,
                floor_plan_zone varchar(100) DEFAULT NULL,
                floor_plan_element_id varchar(100) DEFAULT NULL,
                sale_start datetime DEFAULT NULL,
                sale_end datetime DEFAULT NULL,
                min_quantity int(11) NOT NULL DEFAULT 1,
                max_quantity int(11) NOT NULL DEFAULT 10,
                status varchar(20) NOT NULL DEFAULT 'active',
                sort_order int(11) NOT NULL DEFAULT 0,
                source varchar(20) NOT NULL DEFAULT 'manual',
                woo_product_id bigint(20) unsigned DEFAULT NULL,
                ticket_type varchar(20) NOT NULL DEFAULT 'paid',
                provider varchar(50) DEFAULT NULL,
                external_url text DEFAULT NULL,
                button_text varchar(100) DEFAULT NULL,
                availability_status varchar(20) NOT NULL DEFAULT 'available',
                price_max decimal(10,2) DEFAULT NULL,
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY (id),
                KEY idx_event (event_id),
                KEY idx_status (status),
                KEY idx_floor_plan (floor_plan_id),
                KEY idx_ticket_type (ticket_type)
            ) $charset_collate;";
            
            dbDelta($sql_categories);
        } else {
            // Upgrade existing table with new columns
            $this->maybe_upgrade_ticket_categories_table();
        }
        
        // Payment logs table
        $table_logs = $wpdb->prefix . 'ensemble_payment_logs';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_logs'") !== $table_logs) {
            $sql_logs = "CREATE TABLE $table_logs (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                booking_id bigint(20) unsigned NOT NULL,
                gateway_id varchar(50) NOT NULL,
                event varchar(50) NOT NULL,
                data longtext DEFAULT NULL,
                ip_address varchar(45) DEFAULT NULL,
                user_agent varchar(255) DEFAULT NULL,
                created_at datetime NOT NULL,
                PRIMARY KEY (id),
                KEY idx_booking (booking_id),
                KEY idx_gateway (gateway_id),
                KEY idx_event (event),
                KEY idx_created (created_at)
            ) $charset_collate;";
            
            dbDelta($sql_logs);
        }
    }
    
    /**
     * Upgrade ticket categories table with new columns
     * @since 3.2.0
     */
    private function maybe_upgrade_ticket_categories_table() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ensemble_ticket_categories';
        
        // Check and add missing columns
        $columns_to_add = array(
            'floor_plan_element_id' => "VARCHAR(100) DEFAULT NULL AFTER floor_plan_zone",
            'source'                => "VARCHAR(20) NOT NULL DEFAULT 'manual' AFTER sort_order",
            'woo_product_id'        => "BIGINT(20) UNSIGNED DEFAULT NULL AFTER source",
            'ticket_type'           => "VARCHAR(20) NOT NULL DEFAULT 'paid' AFTER woo_product_id",
            'provider'              => "VARCHAR(50) DEFAULT NULL AFTER ticket_type",
            'external_url'          => "TEXT DEFAULT NULL AFTER provider",
            'button_text'           => "VARCHAR(100) DEFAULT NULL AFTER external_url",
            'availability_status'   => "VARCHAR(20) NOT NULL DEFAULT 'available' AFTER button_text",
            'price_max'             => "DECIMAL(10,2) DEFAULT NULL AFTER availability_status",
        );
        
        foreach ($columns_to_add as $column => $definition) {
            $column_exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                 WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
                DB_NAME, $table, $column
            ));
            
            if (!$column_exists) {
                $wpdb->query("ALTER TABLE $table ADD COLUMN $column $definition");
            }
        }
        
        // Add index for ticket_type if not exists
        $index_exists = $wpdb->get_var("SHOW INDEX FROM $table WHERE Key_name = 'idx_ticket_type'");
        if (!$index_exists) {
            $wpdb->query("ALTER TABLE $table ADD INDEX idx_ticket_type (ticket_type)");
        }
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Add submenu under Ensemble
        add_submenu_page(
            'ensemble',
            __('Tickets Pro', 'ensemble'),
            __('Tickets Pro', 'ensemble'),
            'manage_options',
            'ensemble-tickets-pro',
            array($this, 'render_admin_page')
        );
    }

    /**
     * Enqueue admin assets
     *
     * @param string $hook
     */
    public function enqueue_admin_assets($hook) {
        // Only on our admin page
        if (strpos($hook, 'ensemble-tickets-pro') === false) {
            return;
        }
        
        $url = $this->get_addon_url();
        
        // Enqueue unified admin CSS (from main plugin)
        if (function_exists('ensemble') && method_exists(ensemble(), 'get_url')) {
            wp_enqueue_style('ensemble-admin-unified');
        }
        
        // Tickets Pro Admin CSS
        wp_enqueue_style(
            'es-tickets-pro-admin',
            $url . 'tickets-pro-admin.css',
            array('ensemble-admin-unified'),
            $this->version
        );
        
        // Admin JS
        wp_enqueue_script(
            'es-tickets-pro-admin',
            $url . 'tickets-pro-admin.js',
            array('jquery'),
            $this->version,
            true
        );
        
        wp_localize_script('es-tickets-pro-admin', 'esTicketsPro', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('es_tickets_pro'),
            'i18n'    => array(
                'saving'          => __('Saving...', 'ensemble'),
                'saved'           => __('Settings saved.', 'ensemble'),
                'testing'         => __('Testing...', 'ensemble'),
                'testSuccess'     => __('Connection successful!', 'ensemble'),
                'testFailed'      => __('Connection failed:', 'ensemble'),
                'confirmDelete'   => __('Are you sure you want to delete this category?', 'ensemble'),
                'copied'          => __('Copied!', 'ensemble'),
            ),
        ));
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        // Only enqueue when needed
        if (!$this->should_load_frontend_assets()) {
            return;
        }
        
        $url = $this->get_addon_url();
        
        wp_enqueue_style(
            'es-tickets-pro-frontend',
            $url . 'tickets-pro-frontend.css',
            array(),
            $this->version
        );
        
        wp_enqueue_script(
            'es-tickets-pro-frontend',
            $url . 'tickets-pro-frontend.js',
            array('jquery'),
            $this->version,
            true
        );
        
        wp_localize_script('es-tickets-pro-frontend', 'esTicketsProFrontend', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('es_tickets_pro_frontend'),
            'i18n'    => array(
                'processing'   => __('Processing...', 'ensemble'),
                'redirecting'  => __('Redirecting to payment...', 'ensemble'),
                'selectTicket' => __('Please select at least one ticket.', 'ensemble'),
                'soldOut'      => __('Sold Out', 'ensemble'),
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
        
        // Check for shortcode
        if ($post && has_shortcode($post->post_content, 'ensemble_ticket_form')) {
            return true;
        }
        if ($post && has_shortcode($post->post_content, 'es_tickets')) {
            return true;
        }
        
        // Check if event single with ticket mode
        if (is_singular('ensemble_event')) {
            $ticket_mode = get_post_meta($post->ID, '_ticket_mode', true);
            return $ticket_mode === 'paid';
        }
        
        return false;
    }

    /**
     * Render admin page
     */
    public function render_admin_page() {
        $tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'overview';
        
        // Load main admin page from templates folder
        $template = $this->get_addon_path() . 'templates/admin-page.php';
        
        if (file_exists($template)) {
            include $template;
        }
    }

    /**
     * AJAX: Save gateway settings
     */
    public function ajax_save_gateway_settings() {
        $gateway_id = isset($_POST['gateway_id']) ? sanitize_key($_POST['gateway_id']) : '';
        
        if (!wp_verify_nonce($_POST['gateway_nonce'] ?? '', 'ensemble_save_gateway_' . $gateway_id)) {
            wp_send_json_error(array('message' => __('Security check failed.', 'ensemble')));
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized.', 'ensemble')));
            return;
        }
        
        $gateway = $this->gateways->get_gateway($gateway_id);
        
        if (!$gateway) {
            wp_send_json_error(array('message' => __('Invalid gateway.', 'ensemble')));
            return;
        }
        
        // Get and sanitize settings
        $settings = isset($_POST['gateway_settings']) ? $_POST['gateway_settings'] : array();
        $fields = $gateway->get_settings_fields();
        $sanitized = array();
        
        foreach ($fields as $key => $field) {
            if (isset($settings[$key])) {
                switch ($field['type']) {
                    case 'checkbox':
                        $sanitized[$key] = (bool) $settings[$key];
                        break;
                    case 'number':
                        $sanitized[$key] = floatval($settings[$key]);
                        break;
                    case 'email':
                        $sanitized[$key] = sanitize_email($settings[$key]);
                        break;
                    default:
                        $sanitized[$key] = sanitize_text_field($settings[$key]);
                }
            } else {
                if ($field['type'] === 'checkbox') {
                    $sanitized[$key] = false;
                }
            }
        }
        
        $gateway->save_settings($sanitized);
        
        wp_send_json_success(array('message' => __('Settings saved.', 'ensemble')));
    }

    /**
     * AJAX: Test gateway connection
     */
    public function ajax_test_gateway() {
        check_ajax_referer('es_tickets_pro', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized.', 'ensemble')));
            return;
        }
        
        $gateway_id = isset($_POST['gateway']) ? sanitize_key($_POST['gateway']) : '';
        $gateway = $this->gateways->get_gateway($gateway_id);
        
        if (!$gateway) {
            wp_send_json_error(array('message' => __('Invalid gateway.', 'ensemble')));
            return;
        }
        
        if (!$gateway->is_configured()) {
            wp_send_json_error(array('message' => __('Gateway not configured.', 'ensemble')));
            return;
        }
        
        // Gateway-specific connection test
        $test_result = $gateway->test_connection();
        
        if (is_wp_error($test_result)) {
            wp_send_json_error(array('message' => $test_result->get_error_message()));
            return;
        }
        
        wp_send_json_success(array('message' => __('Connection successful.', 'ensemble')));
    }

    /**
     * AJAX: Save settings
     */
    public function ajax_save_settings() {
        check_ajax_referer('ensemble_tickets_pro_settings', 'settings_nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized.', 'ensemble')));
            return;
        }
        
        $settings = array(
            'currency'              => sanitize_text_field($_POST['currency'] ?? 'EUR'),
            'price_format'          => sanitize_key($_POST['price_format'] ?? 'symbol_before'),
            'tax_enabled'           => !empty($_POST['tax_enabled']),
            'tax_rate'              => floatval($_POST['tax_rate'] ?? 19),
            'tax_included'          => !empty($_POST['tax_included']),
            'guest_checkout'        => !empty($_POST['guest_checkout']),
            'max_tickets_per_order' => absint($_POST['max_tickets_per_order'] ?? 10),
            'hold_time'             => absint($_POST['hold_time'] ?? 15),
            'terms_page'            => absint($_POST['terms_page'] ?? 0),
        );
        
        update_option('ensemble_tickets_pro_settings', $settings);
        
        wp_send_json_success(array('message' => __('Settings saved.', 'ensemble')));
    }

    /**
     * AJAX: Save ticket category
     */
    public function ajax_save_ticket_category() {
        check_ajax_referer('es_tickets_pro', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Unauthorized.', 'ensemble')));
            return;
        }
        
        $event_id = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;
        $category_id = isset($_POST['category_id']) ? absint($_POST['category_id']) : 0;
        
        if (!$event_id) {
            wp_send_json_error(array('message' => __('Invalid event.', 'ensemble')));
            return;
        }
        
        // Get or create category
        if ($category_id) {
            $category = ES_Ticket_Category::get($category_id);
            if (!$category || $category->event_id !== $event_id) {
                wp_send_json_error(array('message' => __('Category not found.', 'ensemble')));
                return;
            }
        } else {
            $category = new ES_Ticket_Category();
            $category->event_id = $event_id;
        }
        
        // Update category data
        $category->name = sanitize_text_field($_POST['name'] ?? '');
        $category->description = sanitize_text_field($_POST['description'] ?? '');
        $category->price = floatval($_POST['price'] ?? 0);
        $category->capacity = !empty($_POST['capacity']) ? absint($_POST['capacity']) : null;
        $category->min_quantity = absint($_POST['min_quantity'] ?? 1);
        $category->max_quantity = absint($_POST['max_quantity'] ?? 10);
        $category->floor_plan_id = !empty($_POST['floor_plan_id']) ? absint($_POST['floor_plan_id']) : null;
        $category->floor_plan_zone = sanitize_text_field($_POST['floor_plan_zone'] ?? '');
        $category->floor_plan_element_id = !empty($_POST['floor_plan_element_id']) ? sanitize_text_field($_POST['floor_plan_element_id']) : null;
        $category->sale_start = !empty($_POST['sale_start']) ? sanitize_text_field($_POST['sale_start']) : null;
        $category->sale_end = !empty($_POST['sale_end']) ? sanitize_text_field($_POST['sale_end']) : null;
        $category->status = sanitize_key($_POST['status'] ?? 'active');
        $category->sort_order = absint($_POST['sort_order'] ?? 0);
        
        // Get currency from settings
        $settings = get_option('ensemble_tickets_pro_settings', array());
        $category->currency = $settings['currency'] ?? 'EUR';
        
        // Validate
        if (empty($category->name)) {
            wp_send_json_error(array('message' => __('Category name is required.', 'ensemble')));
            return;
        }
        
        if ($category->price < 0) {
            wp_send_json_error(array('message' => __('Price must be a positive number.', 'ensemble')));
            return;
        }
        
        // Save
        $result = $category->save();
        
        if (!$result) {
            wp_send_json_error(array('message' => __('Failed to save category.', 'ensemble')));
            return;
        }
        
        wp_send_json_success(array(
            'message'  => __('Category saved.', 'ensemble'),
            'category' => $category->to_array(),
        ));
    }

    /**
     * AJAX: Delete ticket category
     */
    public function ajax_delete_ticket_category() {
        check_ajax_referer('es_tickets_pro', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Unauthorized.', 'ensemble')));
            return;
        }
        
        $category_id = isset($_POST['category_id']) ? absint($_POST['category_id']) : 0;
        
        if (!$category_id) {
            wp_send_json_error(array('message' => __('Invalid category.', 'ensemble')));
            return;
        }
        
        $category = ES_Ticket_Category::get($category_id);
        
        if (!$category) {
            wp_send_json_error(array('message' => __('Category not found.', 'ensemble')));
            return;
        }
        
        // Check if category has sold tickets
        if ($category->sold > 0) {
            wp_send_json_error(array(
                'message' => sprintf(
                    __('Cannot delete category with %d sold tickets. Deactivate instead.', 'ensemble'),
                    $category->sold
                )
            ));
            return;
        }
        
        if (!$category->delete()) {
            wp_send_json_error(array('message' => __('Failed to delete category.', 'ensemble')));
            return;
        }
        
        wp_send_json_success(array('message' => __('Category deleted.', 'ensemble')));
    }

    /**
     * AJAX: Get single ticket category
     */
    public function ajax_get_ticket_category() {
        check_ajax_referer('es_tickets_pro', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Unauthorized.', 'ensemble')));
            return;
        }
        
        $category_id = isset($_POST['category_id']) ? absint($_POST['category_id']) : 0;
        
        if (!$category_id) {
            wp_send_json_error(array('message' => __('Invalid category.', 'ensemble')));
            return;
        }
        
        $category = ES_Ticket_Category::get($category_id);
        
        if (!$category) {
            wp_send_json_error(array('message' => __('Category not found.', 'ensemble')));
            return;
        }
        
        wp_send_json_success($category->to_array());
    }

    /**
     * AJAX: Duplicate ticket category
     */
    public function ajax_duplicate_ticket_category() {
        check_ajax_referer('es_tickets_pro', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Unauthorized.', 'ensemble')));
            return;
        }
        
        $category_id = isset($_POST['category_id']) ? absint($_POST['category_id']) : 0;
        
        if (!$category_id) {
            wp_send_json_error(array('message' => __('Invalid category.', 'ensemble')));
            return;
        }
        
        $original = ES_Ticket_Category::get($category_id);
        
        if (!$original) {
            wp_send_json_error(array('message' => __('Category not found.', 'ensemble')));
            return;
        }
        
        // Create duplicate
        $duplicate = new ES_Ticket_Category();
        $duplicate->event_id = $original->event_id;
        $duplicate->name = $original->name . ' ' . __('(Copy)', 'ensemble');
        $duplicate->description = $original->description;
        $duplicate->price = $original->price;
        $duplicate->currency = $original->currency;
        $duplicate->capacity = $original->capacity;
        $duplicate->sold = 0; // Reset sold count
        $duplicate->min_quantity = $original->min_quantity;
        $duplicate->max_quantity = $original->max_quantity;
        $duplicate->floor_plan_id = $original->floor_plan_id;
        $duplicate->floor_plan_element_id = null; // Don't duplicate floor plan element link
        $duplicate->floor_plan_zone = $original->floor_plan_zone;
        $duplicate->sale_start = $original->sale_start;
        $duplicate->sale_end = $original->sale_end;
        $duplicate->status = $original->status;
        $duplicate->source = 'manual';
        $duplicate->sort_order = $original->sort_order + 1;
        
        if (!$duplicate->save()) {
            wp_send_json_error(array('message' => __('Failed to duplicate category.', 'ensemble')));
            return;
        }
        
        wp_send_json_success(array(
            'message'  => __('Category duplicated.', 'ensemble'),
            'category' => $duplicate->to_array(),
        ));
    }

    /**
     * AJAX: Save ticket category from central admin page
     */
    public function ajax_save_ticket_category_central() {
        check_ajax_referer('es_tickets_pro', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Unauthorized.', 'ensemble')));
            return;
        }
        
        $event_id = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;
        $category_id = isset($_POST['category_id']) ? absint($_POST['category_id']) : 0;
        
        if (!$event_id) {
            wp_send_json_error(array('message' => __('Please select an event.', 'ensemble')));
            return;
        }
        
        // Get or create category
        if ($category_id) {
            $category = ES_Ticket_Category::get($category_id);
            if (!$category) {
                wp_send_json_error(array('message' => __('Category not found.', 'ensemble')));
                return;
            }
        } else {
            $category = new ES_Ticket_Category();
            $category->event_id = $event_id;
            $category->source = 'manual';
        }
        
        // Update category data
        $category->event_id = $event_id;
        $category->name = sanitize_text_field($_POST['name'] ?? '');
        $category->description = sanitize_text_field($_POST['description'] ?? '');
        $category->price = floatval($_POST['price'] ?? 0);
        $category->capacity = !empty($_POST['capacity']) ? absint($_POST['capacity']) : null;
        $category->min_quantity = absint($_POST['min_quantity'] ?? 1);
        $category->max_quantity = absint($_POST['max_quantity'] ?? 10);
        $category->floor_plan_zone = sanitize_text_field($_POST['floor_plan_zone'] ?? '');
        $category->sale_start = !empty($_POST['sale_start']) ? sanitize_text_field(str_replace('T', ' ', $_POST['sale_start'])) . ':00' : null;
        $category->sale_end = !empty($_POST['sale_end']) ? sanitize_text_field(str_replace('T', ' ', $_POST['sale_end'])) . ':00' : null;
        $category->status = !empty($_POST['status']) ? 'active' : 'inactive';
        
        // Get currency from settings
        $settings = get_option('ensemble_tickets_pro_settings', array());
        $category->currency = $settings['currency'] ?? 'EUR';
        
        // Validate
        if (empty($category->name)) {
            wp_send_json_error(array('message' => __('Category name is required.', 'ensemble')));
            return;
        }
        
        if ($category->price < 0) {
            wp_send_json_error(array('message' => __('Price must be a positive number.', 'ensemble')));
            return;
        }
        
        // Save
        $result = $category->save();
        
        if (!$result) {
            wp_send_json_error(array('message' => __('Failed to save category.', 'ensemble')));
            return;
        }
        
        wp_send_json_success(array(
            'message'  => __('Category saved.', 'ensemble'),
            'category' => $category->to_array(),
        ));
    }

    /**
     * AJAX: Get ticket categories for event
     */
    public function ajax_get_ticket_categories() {
        $event_id = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;
        
        if (!$event_id) {
            wp_send_json_error(array('message' => __('Invalid event.', 'ensemble')));
            return;
        }
        
        // Get all categories (including inactive for admin)
        $include_inactive = current_user_can('edit_posts');
        $categories = ES_Ticket_Category::get_by_event($event_id, !$include_inactive);
        
        $result = array_map(function($cat) {
            return $cat->to_array();
        }, $categories);
        
        wp_send_json_success(array('categories' => $result));
    }

    /**
     * AJAX: Create ticket booking
     */
    public function ajax_create_ticket_booking() {
        error_log('Tickets Pro: ajax_create_ticket_booking called');
        error_log('Tickets Pro: POST data: ' . print_r($_POST, true));
        
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'es_tickets_pro_frontend')) {
            error_log('Tickets Pro: Nonce verification failed');
            wp_send_json_error(array('message' => __('Security check failed.', 'ensemble')));
            return;
        }
        
        error_log('Tickets Pro: Nonce OK');
        
        $event_id = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;
        $tickets = isset($_POST['tickets']) ? $_POST['tickets'] : array();
        $customer_name = sanitize_text_field($_POST['customer_name'] ?? '');
        $customer_email = sanitize_email($_POST['customer_email'] ?? '');
        $customer_phone = sanitize_text_field($_POST['customer_phone'] ?? '');
        
        error_log('Tickets Pro: event_id=' . $event_id . ', tickets=' . print_r($tickets, true));
        
        if (!$event_id || empty($tickets)) {
            wp_send_json_error(array('message' => __('Invalid request.', 'ensemble')));
            return;
        }
        
        if (!$customer_email) {
            wp_send_json_error(array('message' => __('Email is required.', 'ensemble')));
            return;
        }
        
        // Calculate total and validate
        $total = 0;
        $items = array();
        
        foreach ($tickets as $ticket) {
            $category_id = absint($ticket['category_id'] ?? 0);
            $quantity = absint($ticket['quantity'] ?? 0);
            
            if (!$category_id || !$quantity) continue;
            
            $category = ES_Ticket_Category::get($category_id);
            if (!$category) continue;
            
            // Check availability
            $available = $category->get_available_count();
            if ($available !== null && $quantity > $available) {
                wp_send_json_error(array(
                    'message' => sprintf(__('Only %d tickets available for %s.', 'ensemble'), $available, $category->name)
                ));
                return;
            }
            
            $subtotal = $category->price * $quantity;
            $total += $subtotal;
            
            $items[] = array(
                'category_id' => $category_id,
                'category_name' => $category->name,
                'quantity' => $quantity,
                'price' => $category->price,
                'subtotal' => $subtotal,
            );
        }
        
        if (empty($items)) {
            wp_send_json_error(array('message' => __('No valid tickets selected.', 'ensemble')));
            return;
        }
        
        // Create booking
        global $wpdb;
        $table = $wpdb->prefix . 'ensemble_bookings';
        
        $confirmation_code = strtoupper(wp_generate_password(8, false));
        
        $booking_data = array(
            'event_id'          => $event_id,
            'booking_type'      => 'ticket',
            'customer_name'     => $customer_name,
            'customer_email'    => $customer_email,
            'customer_phone'    => $customer_phone,
            'category_id'       => $items[0]['category_id'],
            'category_name'     => $items[0]['category_name'],
            'quantity'          => array_sum(array_column($items, 'quantity')),
            'price'             => $total,
            'currency'          => get_option('ensemble_tickets_pro_settings')['currency'] ?? 'EUR',
            'status'            => 'pending',
            'payment_status'    => 'pending',
            'confirmation_code' => $confirmation_code,
            'booking_data'      => wp_json_encode($items),
            'created_at'        => current_time('mysql'),
            'updated_at'        => current_time('mysql'),
        );
        
        $wpdb->insert($table, $booking_data);
        $booking_id = $wpdb->insert_id;
        
        if (!$booking_id) {
            wp_send_json_error(array('message' => __('Failed to create booking.', 'ensemble')));
            return;
        }
        
        wp_send_json_success(array(
            'booking_id'        => $booking_id,
            'confirmation_code' => $confirmation_code,
            'total'             => $total,
        ));
    }

    /**
     * AJAX: Process ticket payment
     */
    public function ajax_process_payment() {
        error_log('Tickets Pro: ajax_process_payment called');
        error_log('Tickets Pro: POST data: ' . print_r($_POST, true));
        
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'es_tickets_pro_frontend')) {
            error_log('Tickets Pro: Payment nonce verification failed');
            wp_send_json_error(array('message' => __('Security check failed.', 'ensemble')));
            return;
        }
        
        error_log('Tickets Pro: Payment nonce OK');
        
        $booking_id = isset($_POST['booking_id']) ? absint($_POST['booking_id']) : 0;
        $gateway_id = isset($_POST['gateway']) ? sanitize_key($_POST['gateway']) : '';
        
        error_log('Tickets Pro: booking_id=' . $booking_id . ', gateway_id=' . $gateway_id);
        
        if (!$booking_id || !$gateway_id) {
            wp_send_json_error(array('message' => __('Invalid request.', 'ensemble')));
            return;
        }
        
        // Get booking from Booking Engine
        global $wpdb;
        $table = $wpdb->prefix . 'ensemble_bookings';
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $booking_id
        ), ARRAY_A);
        
        if (!$booking) {
            wp_send_json_error(array('message' => __('Booking not found.', 'ensemble')));
            return;
        }
        
        if ($booking['payment_status'] === 'paid') {
            wp_send_json_error(array('message' => __('This booking has already been paid.', 'ensemble')));
            return;
        }
        
        // Get event for description
        $event = get_post($booking['event_id']);
        $description = $event ? $event->post_title : __('Event Ticket', 'ensemble');
        
        if (!empty($booking['category_name'])) {
            $description .= ' - ' . $booking['category_name'];
        }
        
        // Process payment through gateway
        error_log('Tickets Pro: Calling gateway->process_payment for gateway: ' . $gateway_id);
        error_log('Tickets Pro: Payment data: amount=' . floatval($booking['price']) . ', currency=' . ($booking['currency'] ?: 'EUR'));
        
        $result = $this->gateways->process_payment($gateway_id, array(
            'booking_id'  => $booking_id,
            'amount'      => floatval($booking['price']),
            'currency'    => $booking['currency'] ?: 'EUR',
            'description' => $description,
            'metadata'    => array(
                'booking_id'     => $booking_id,
                'event_id'       => $booking['event_id'],
                'customer_name'  => $booking['customer_name'],
                'customer_email' => $booking['customer_email'],
            ),
        ));
        
        error_log('Tickets Pro: Gateway result: ' . print_r($result, true));
        
        if (is_wp_error($result)) {
            error_log('Tickets Pro: Gateway returned error: ' . $result->get_error_message());
            wp_send_json_error(array('message' => $result->get_error_message()));
            return;
        }
        
        // Update booking with payment info
        $wpdb->update($table, array(
            'payment_method' => $gateway_id,
            'payment_id'     => $result['payment_id'],
            'payment_status' => 'pending',
            'updated_at'     => current_time('mysql'),
        ), array('id' => $booking_id));
        
        wp_send_json_success(array(
            'redirect_url' => $result['redirect_url'],
            'payment_id'   => $result['payment_id'],
        ));
    }

    /**
     * Handle payment return
     *
     * @param int    $booking_id
     * @param string $gateway_id
     */
    public function handle_payment_return($booking_id, $gateway_id) {
        $gateway = $this->gateways->get_gateway($gateway_id);
        
        if (!$gateway) {
            return;
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'ensemble_bookings';
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $booking_id
        ), ARRAY_A);
        
        if (!$booking || empty($booking['payment_id'])) {
            return;
        }
        
        // Capture payment (for PayPal)
        if ($gateway->get_id() === 'paypal') {
            $result = $gateway->capture_payment($booking['payment_id']);
            
            if (!is_wp_error($result) && $result['status'] === 'completed') {
                $wpdb->update($table, array(
                    'payment_status' => 'paid',
                    'status'         => 'confirmed',
                    'payment_id'     => $result['capture_id'] ?? $booking['payment_id'],
                    'updated_at'     => current_time('mysql'),
                ), array('id' => $booking_id));
                
                // Update sold count
                if (!empty($booking['booking_data'])) {
                    $items = json_decode($booking['booking_data'], true);
                    if (is_array($items)) {
                        foreach ($items as $item) {
                            $category = ES_Ticket_Category::get($item['category_id']);
                            if ($category) {
                                $category->increment_sold($item['quantity']);
                            }
                        }
                    }
                }
                
                // Trigger confirmation
                do_action('ensemble_booking_confirmed', $booking_id);
                do_action('ensemble_ticket_paid', $booking_id);
            } else {
                $wpdb->update($table, array(
                    'payment_status' => 'failed',
                    'updated_at'     => current_time('mysql'),
                ), array('id' => $booking_id));
            }
        }
    }

    /**
     * Handle payment cancelled
     *
     * @param int    $booking_id
     * @param string $gateway_id
     */
    public function handle_payment_cancelled($booking_id, $gateway_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'ensemble_bookings';
        
        $wpdb->update($table, array(
            'payment_status' => 'cancelled',
            'updated_at'     => current_time('mysql'),
        ), array('id' => $booking_id));
    }

    /**
     * Get payment return URL
     *
     * @param string $url
     * @param int    $booking_id
     * @param string $gateway_id
     * @return string
     */
    public function get_payment_return_url($url, $booking_id, $gateway_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'ensemble_bookings';
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $booking_id
        ), ARRAY_A);
        
        if (!$booking) {
            return home_url('/');
        }
        
        $event_url = get_permalink($booking['event_id']);
        
        return add_query_arg(array(
            'ticket_status'  => 'success',
            'confirmation'   => $booking['confirmation_code'],
        ), $event_url);
    }

    /**
     * Get payment cancel URL
     *
     * @param string $url
     * @param int    $booking_id
     * @param string $gateway_id
     * @return string
     */
    public function get_payment_cancel_url($url, $booking_id, $gateway_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'ensemble_bookings';
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $booking_id
        ), ARRAY_A);
        
        if (!$booking) {
            return home_url('/');
        }
        
        return add_query_arg(array(
            'ticket_status' => 'cancelled',
        ), get_permalink($booking['event_id']));
    }

    /**
     * Render ticket categories in wizard
     */
    public function render_wizard_ticket_categories() {
        // Template is in same directory, not in templates/ subfolder
        $template = $this->get_addon_path() . 'wizard-ticket-categories.php';
        if (file_exists($template)) {
            include $template;
        } else {
            include $this->get_addon_path() . 'templates/wizard-ticket-categories.php';
        }
    }

    /**
     * Add wizard event data
     * 
     * Loads both paid tickets (from DB) and external tickets (from post meta)
     * and returns them in unified format for the wizard JS.
     *
     * @param array   $event
     * @param WP_Post $post
     * @return array
     */
    public function add_wizard_event_data($event, $post) {
        $event['ticket_mode'] = get_post_meta($post->ID, '_ticket_mode', true) ?: 'none';
        
        $tickets_unified = array();
        
        // Load paid tickets from database
        if (class_exists('ES_Ticket_Category')) {
            $categories = ES_Ticket_Category::get_by_event($post->ID, false);
            foreach ($categories as $cat) {
                $ticket_data = $cat->to_array();
                $ticket_data['ticket_type'] = 'paid';
                $ticket_data['id'] = 'paid_' . $cat->id; // Prefix for JS identification
                $ticket_data['db_id'] = $cat->id; // Keep original DB ID
                $tickets_unified[] = $ticket_data;
            }
        }
        
        // Load external tickets from post meta
        $external_tickets = get_post_meta($post->ID, '_external_tickets', true);
        if (!empty($external_tickets) && is_array($external_tickets)) {
            foreach ($external_tickets as $ext) {
                $ext['ticket_type'] = 'external';
                $tickets_unified[] = $ext;
            }
        }
        
        // Sort by sort_order if available
        usort($tickets_unified, function($a, $b) {
            $order_a = isset($a['sort_order']) ? intval($a['sort_order']) : 999;
            $order_b = isset($b['sort_order']) ? intval($b['sort_order']) : 999;
            return $order_a - $order_b;
        });
        
        $event['tickets_unified'] = $tickets_unified;
        
        // Legacy support: also provide ticket_categories for old code
        $event['ticket_categories'] = array_filter($tickets_unified, function($t) {
            return isset($t['ticket_type']) && $t['ticket_type'] === 'paid';
        });
        $event['ticket_categories'] = array_values($event['ticket_categories']);
        
        return $event;
    }

    /**
     * Save wizard event data
     * 
     * Processes 'tickets_unified_json' from wizard form.
     * - Paid tickets: Saved to ensemble_ticket_categories table
     * - External tickets: Saved as post meta '_external_tickets'
     *
     * @param int   $event_id
     * @param array $data
     */
    public function save_wizard_event_data($event_id, $data) {
        // Save ticket mode
        if (isset($data['ticket_mode'])) {
            update_post_meta($event_id, '_ticket_mode', sanitize_key($data['ticket_mode']));
        }
        
        // Process unified tickets JSON (new format from wizard)
        if (isset($data['tickets_unified_json']) && !empty($data['tickets_unified_json'])) {
            $tickets_unified = json_decode(stripslashes($data['tickets_unified_json']), true);
            
            if (is_array($tickets_unified)) {
                $paid_tickets = array();
                $external_tickets = array();
                
                foreach ($tickets_unified as $index => $ticket) {
                    $ticket['sort_order'] = $index;
                    
                    if (isset($ticket['ticket_type']) && $ticket['ticket_type'] === 'external') {
                        $external_tickets[] = $this->sanitize_external_ticket($ticket);
                    } else {
                        // Default to paid if not specified
                        $paid_tickets[] = $ticket;
                    }
                }
                
                // Save paid tickets to database
                if (!empty($paid_tickets)) {
                    $this->save_ticket_categories($event_id, $paid_tickets);
                } else {
                    // No paid tickets - delete all existing
                    $this->delete_all_ticket_categories($event_id);
                }
                
                // Save external tickets as post meta
                update_post_meta($event_id, '_external_tickets', $external_tickets);
            }
        }
        // Legacy support: process ticket_categories directly
        elseif (isset($data['ticket_categories']) && is_array($data['ticket_categories'])) {
            $this->save_ticket_categories($event_id, $data['ticket_categories']);
        }
    }

    /**
     * Sanitize external ticket data
     *
     * @param array $ticket
     * @return array
     */
    private function sanitize_external_ticket($ticket) {
        return array(
            'id'           => sanitize_text_field($ticket['id'] ?? 'ext_' . uniqid()),
            'ticket_type'  => 'external',
            'provider'     => sanitize_key($ticket['provider'] ?? 'custom'),
            'name'         => sanitize_text_field($ticket['name'] ?? ''),
            'url'          => esc_url_raw($ticket['url'] ?? ''),
            'price'        => floatval($ticket['price'] ?? 0),
            'price_max'    => floatval($ticket['price_max'] ?? 0),
            'availability' => sanitize_key($ticket['availability'] ?? 'available'),
            'sort_order'   => intval($ticket['sort_order'] ?? 0),
        );
    }

    /**
     * Delete all ticket categories for an event
     * 
     * Only deletes categories with no sold tickets.
     *
     * @param int $event_id
     */
    private function delete_all_ticket_categories($event_id) {
        if (!class_exists('ES_Ticket_Category')) {
            return;
        }
        
        $existing = ES_Ticket_Category::get_by_event($event_id, false);
        foreach ($existing as $category) {
            if ($category->sold === 0) {
                $category->delete();
            }
        }
    }

    /**
     * Save tickets directly from POST data on post save
     * 
     * This is more reliable than the wizard action because it reads
     * directly from $_POST instead of relying on data being passed through.
     *
     * @param int     $post_id
     * @param WP_Post $post
     */
    public function save_tickets_on_post_save($post_id, $post) {
        // Only for event post type
        $event_post_type = function_exists('ensemble_get_post_type') ? ensemble_get_post_type() : 'ensemble_event';
        if ($post->post_type !== $event_post_type) {
            return;
        }
        
        // Skip autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Skip revisions
        if (wp_is_post_revision($post_id)) {
            return;
        }
        
        // Check capabilities
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Check if tickets data is in POST
        if (!isset($_POST['tickets_unified_json']) || empty($_POST['tickets_unified_json'])) {
            return;
        }
        
        error_log('Tickets Pro save_tickets_on_post_save - Event ID: ' . $post_id);
        error_log('Tickets Pro save_tickets_on_post_save - Raw JSON: ' . substr($_POST['tickets_unified_json'], 0, 500));
        
        $tickets_unified = json_decode(stripslashes($_POST['tickets_unified_json']), true);
        
        if (!is_array($tickets_unified)) {
            error_log('Tickets Pro save_tickets_on_post_save - JSON decode failed');
            return;
        }
        
        error_log('Tickets Pro save_tickets_on_post_save - Decoded tickets count: ' . count($tickets_unified));
        
        $paid_tickets = array();
        $external_tickets = array();
        
        foreach ($tickets_unified as $index => $ticket) {
            $ticket['sort_order'] = $index;
            
            if (isset($ticket['ticket_type']) && $ticket['ticket_type'] === 'external') {
                $external_tickets[] = $this->sanitize_external_ticket($ticket);
            } else {
                // Default to paid if not specified
                $paid_tickets[] = $ticket;
            }
        }
        
        error_log('Tickets Pro save_tickets_on_post_save - Paid: ' . count($paid_tickets) . ', External: ' . count($external_tickets));
        
        // Save paid tickets to database
        if (!empty($paid_tickets)) {
            $this->save_ticket_categories($post_id, $paid_tickets);
        } else {
            // No paid tickets - delete all existing
            $this->delete_all_ticket_categories($post_id);
        }
        
        // Save external tickets as post meta
        update_post_meta($post_id, '_external_tickets', $external_tickets);
        
        error_log('Tickets Pro save_tickets_on_post_save - Save complete');
    }
    
    /**
     * Save ticket categories from wizard data
     *
     * @param int   $event_id
     * @param array $categories_data
     */
    private function save_ticket_categories($event_id, $categories_data) {
        if (!class_exists('ES_Ticket_Category')) {
            return;
        }
        
        // Get existing category IDs for this event
        $existing = ES_Ticket_Category::get_by_event($event_id, false);
        $existing_ids = array_map(function($cat) {
            return $cat->id;
        }, $existing);
        
        $updated_ids = array();
        $settings = get_option('ensemble_tickets_pro_settings', array());
        $currency = $settings['currency'] ?? 'EUR';
        
        foreach ($categories_data as $index => $cat_data) {
            // Check for db_id (new unified format) or numeric id (old format)
            $category_id = 0;
            if (isset($cat_data['db_id'])) {
                $category_id = absint($cat_data['db_id']);
            } elseif (isset($cat_data['id']) && is_numeric($cat_data['id'])) {
                $category_id = absint($cat_data['id']);
            }
            
            // Get or create category
            if ($category_id && in_array($category_id, $existing_ids)) {
                $category = ES_Ticket_Category::get($category_id);
            } else {
                $category = new ES_Ticket_Category();
                $category->event_id = $event_id;
            }
            
            // Update category data
            $category->name = sanitize_text_field($cat_data['name'] ?? '');
            $category->description = sanitize_text_field($cat_data['description'] ?? '');
            $category->price = floatval($cat_data['price'] ?? 0);
            $category->currency = $currency;
            $category->capacity = !empty($cat_data['capacity']) ? absint($cat_data['capacity']) : null;
            $category->min_quantity = absint($cat_data['min_quantity'] ?? 1);
            $category->max_quantity = absint($cat_data['max_quantity'] ?? 10);
            $category->floor_plan_id = !empty($cat_data['floor_plan_id']) ? absint($cat_data['floor_plan_id']) : null;
            $category->floor_plan_zone = sanitize_text_field($cat_data['floor_plan_zone'] ?? '');
            $category->floor_plan_element_id = !empty($cat_data['floor_plan_element_id']) ? sanitize_text_field($cat_data['floor_plan_element_id']) : null;
            $category->status = sanitize_key($cat_data['status'] ?? 'active');
            $category->sort_order = isset($cat_data['sort_order']) ? intval($cat_data['sort_order']) : $index;
            
            if (!empty($category->name)) {
                $category->save();
                $updated_ids[] = $category->id;
            }
        }
        
        // Delete categories that were removed (only if they have no sold tickets)
        foreach ($existing_ids as $old_id) {
            if (!in_array($old_id, $updated_ids)) {
                $category = ES_Ticket_Category::get($old_id);
                if ($category && $category->sold === 0) {
                    $category->delete();
                }
            }
        }
    }

    /**
     * Shortcode: Ticket form
     *
     * @param array $atts
     * @return string
     */
    public function shortcode_ticket_form($atts) {
        $atts = shortcode_atts(array(
            'event_id' => 0,
        ), $atts);
        
        $event_id = absint($atts['event_id']);
        
        if (!$event_id && is_singular('ensemble_event')) {
            $event_id = get_the_ID();
        }
        
        if (!$event_id) {
            return '';
        }
        
        ob_start();
        // Template is in same directory, not in templates/ subfolder
        $template = $this->get_addon_path() . 'ticket-form.php';
        if (file_exists($template)) {
            include $template;
        } else {
            include $this->get_addon_path() . 'templates/ticket-form.php';
        }
        return ob_get_clean();
    }

    /**
     * AJAX: Get ticket availability
     */
    public function ajax_get_availability() {
        $event_id = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;
        
        if (!$event_id) {
            wp_send_json_error(array('message' => __('Invalid event.', 'ensemble')));
            return;
        }
        
        $categories = ES_Ticket_Category::get_by_event($event_id);
        $availability = array();
        
        foreach ($categories as $cat) {
            $availability[$cat->id] = array(
                'available' => $cat->get_available_count(),
                'status'    => $cat->get_availability_status(),
            );
        }
        
        wp_send_json_success($availability);
    }

    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        register_rest_route('ensemble/v1', '/tickets/categories/(?P<event_id>\d+)', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'rest_get_categories'),
            'permission_callback' => '__return_true',
        ));
        
        register_rest_route('ensemble/v1', '/tickets/availability/(?P<event_id>\d+)', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'rest_get_availability'),
            'permission_callback' => '__return_true',
        ));
    }

    /**
     * REST: Get ticket categories
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function rest_get_categories($request) {
        $event_id = $request->get_param('event_id');
        $categories = ES_Ticket_Category::get_by_event($event_id);
        
        return rest_ensure_response(array_map(function($cat) {
            return $cat->to_array();
        }, $categories));
    }

    /**
     * REST: Get availability
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function rest_get_availability($request) {
        $event_id = $request->get_param('event_id');
        $categories = ES_Ticket_Category::get_by_event($event_id);
        
        $result = array();
        foreach ($categories as $cat) {
            $result[$cat->id] = array(
                'available' => $cat->get_available_count(),
                'status'    => $cat->get_availability_status(),
            );
        }
        
        return rest_ensure_response($result);
    }

    /**
     * Check if payment gateways are available
     *
     * @return bool
     */
    public function has_gateways() {
        return $this->gateways && $this->gateways->has_available_gateway();
    }

    /**
     * Get available gateways
     *
     * @return ES_Payment_Gateway[]
     */
    public function get_available_gateways() {
        return $this->gateways ? $this->gateways->get_available_gateways() : array();
    }

    // ============================================
    // TICKET TEMPLATE AJAX HANDLERS
    // ============================================

    /**
     * AJAX: Get a single ticket template
     */
    public function ajax_get_ticket_template() {
        check_ajax_referer('es_tickets_pro', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized.', 'ensemble')));
            return;
        }
        
        $template_id = isset($_POST['template_id']) ? absint($_POST['template_id']) : 0;
        
        if (!$template_id) {
            wp_send_json_error(array('message' => __('Invalid template ID.', 'ensemble')));
            return;
        }
        
        $template = ES_Ticket_Category::get($template_id);
        
        if (!$template || !$template->is_template()) {
            wp_send_json_error(array('message' => __('Template not found.', 'ensemble')));
            return;
        }
        
        wp_send_json_success($template->to_array());
    }

    /**
     * AJAX: Save a ticket template
     */
    public function ajax_save_ticket_template() {
        // Accept both nonce names (paid and external forms have different names)
        $nonce_valid = wp_verify_nonce($_POST['template_nonce'] ?? '', 'es_save_ticket_template') ||
                       wp_verify_nonce($_POST['external_template_nonce'] ?? '', 'es_save_ticket_template');
        
        if (!$nonce_valid) {
            wp_send_json_error(array('message' => __('Security check failed.', 'ensemble')));
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized.', 'ensemble')));
            return;
        }
        
        $template_id = isset($_POST['template_id']) ? absint($_POST['template_id']) : 0;
        
        // Get or create template
        if ($template_id) {
            $template = ES_Ticket_Category::get($template_id);
            if (!$template || !$template->is_template()) {
                wp_send_json_error(array('message' => __('Template not found.', 'ensemble')));
                return;
            }
        } else {
            $template = new ES_Ticket_Category();
            $template->event_id = 0; // Mark as template
        }
        
        // Get currency from settings
        $settings = get_option('ensemble_tickets_pro_settings', array());
        $currency = $settings['currency'] ?? 'EUR';
        
        // Determine ticket type
        $ticket_type = sanitize_key($_POST['ticket_type'] ?? 'paid');
        $template->ticket_type = in_array($ticket_type, array('paid', 'external')) ? $ticket_type : 'paid';
        
        // Update template data (common fields)
        $template->name = sanitize_text_field($_POST['name'] ?? '');
        $template->description = sanitize_textarea_field($_POST['description'] ?? '');
        $template->price = floatval($_POST['price'] ?? 0);
        $template->currency = $currency;
        $template->status = isset($_POST['status']) ? 'active' : 'inactive';
        
        // Type-specific fields
        if ($template->ticket_type === 'external') {
            // External ticket fields
            $template->provider = sanitize_key($_POST['provider'] ?? 'custom');
            $template->external_url = esc_url_raw($_POST['external_url'] ?? '');
            $template->button_text = sanitize_text_field($_POST['button_text'] ?? '');
            $template->availability_status = sanitize_key($_POST['availability_status'] ?? 'available');
            $template->price_max = !empty($_POST['price_max']) ? floatval($_POST['price_max']) : null;
            
            // External tickets don't need these fields
            $template->capacity = null;
            $template->min_quantity = 1;
            $template->max_quantity = 10;
        } else {
            // Paid ticket fields
            $template->capacity = !empty($_POST['capacity']) ? absint($_POST['capacity']) : null;
            $template->min_quantity = absint($_POST['min_quantity'] ?? 1);
            $template->max_quantity = absint($_POST['max_quantity'] ?? 10);
            
            // Clear external fields
            $template->provider = null;
            $template->external_url = null;
            $template->button_text = null;
            $template->availability_status = 'available';
            $template->price_max = null;
        }
        
        // Validate
        if (empty($template->name)) {
            wp_send_json_error(array('message' => __('Template name is required.', 'ensemble')));
            return;
        }
        
        // Save
        if ($template->save()) {
            wp_send_json_success(array(
                'message' => __('Template saved.', 'ensemble'),
                'template' => $template->to_array(),
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to save template.', 'ensemble')));
        }
    }

    /**
     * AJAX: Delete a ticket template
     */
    public function ajax_delete_ticket_template() {
        check_ajax_referer('es_tickets_pro', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized.', 'ensemble')));
            return;
        }
        
        $template_id = isset($_POST['template_id']) ? absint($_POST['template_id']) : 0;
        
        if (!$template_id) {
            wp_send_json_error(array('message' => __('Invalid template ID.', 'ensemble')));
            return;
        }
        
        $template = ES_Ticket_Category::get($template_id);
        
        if (!$template || !$template->is_template()) {
            wp_send_json_error(array('message' => __('Template not found.', 'ensemble')));
            return;
        }
        
        if ($template->delete()) {
            wp_send_json_success(array('message' => __('Template deleted.', 'ensemble')));
        } else {
            wp_send_json_error(array('message' => __('Failed to delete template.', 'ensemble')));
        }
    }

    /**
     * AJAX: Duplicate a ticket template
     */
    public function ajax_duplicate_ticket_template() {
        check_ajax_referer('es_tickets_pro', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized.', 'ensemble')));
            return;
        }
        
        $template_id = isset($_POST['template_id']) ? absint($_POST['template_id']) : 0;
        
        if (!$template_id) {
            wp_send_json_error(array('message' => __('Invalid template ID.', 'ensemble')));
            return;
        }
        
        $template = ES_Ticket_Category::get($template_id);
        
        if (!$template || !$template->is_template()) {
            wp_send_json_error(array('message' => __('Template not found.', 'ensemble')));
            return;
        }
        
        $copy = $template->duplicate();
        
        if ($copy) {
            wp_send_json_success(array(
                'message' => __('Template duplicated.', 'ensemble'),
                'template' => $copy->to_array(),
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to duplicate template.', 'ensemble')));
        }
    }

    /**
     * AJAX: Get all ticket templates for import
     */
    public function ajax_get_ticket_templates() {
        check_ajax_referer('es_tickets_pro', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Unauthorized.', 'ensemble')));
            return;
        }
        
        $templates = ES_Ticket_Category::get_templates(true); // Only active
        
        $result = array();
        foreach ($templates as $template) {
            $result[] = $template->to_array();
        }
        
        wp_send_json_success($result);
    }

    /**
     * AJAX: Import ticket templates to an event
     */
    public function ajax_import_ticket_templates() {
        check_ajax_referer('es_tickets_pro', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Unauthorized.', 'ensemble')));
            return;
        }
        
        $event_id = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;
        $template_ids = isset($_POST['template_ids']) ? array_map('absint', (array) $_POST['template_ids']) : array();
        
        if (!$event_id) {
            wp_send_json_error(array('message' => __('Invalid event ID.', 'ensemble')));
            return;
        }
        
        if (empty($template_ids)) {
            wp_send_json_error(array('message' => __('No templates selected.', 'ensemble')));
            return;
        }
        
        $created = ES_Ticket_Category::import_templates_to_event($event_id, $template_ids);
        
        if (!empty($created)) {
            $result = array();
            foreach ($created as $category) {
                $result[] = $category->to_array();
            }
            
            wp_send_json_success(array(
                'message' => sprintf(__('%d template(s) imported.', 'ensemble'), count($created)),
                'categories' => $result,
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to import templates.', 'ensemble')));
        }
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
        // Helper function to parse boolean/checkbox values
        $parse_bool = function($value) {
            if (is_bool($value)) return $value;
            if ($value === '1' || $value === 1) return true;
            if ($value === '0' || $value === 0 || $value === '' || $value === null) return false;
            if (is_string($value)) return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            return (bool) $value;
        };
        
        $valid_currencies = array('EUR', 'USD', 'GBP', 'CHF');
        $valid_formats = array('symbol_before', 'symbol_after', 'code_before');
        
        return array(
            'currency'              => isset($settings['currency']) && in_array($settings['currency'], $valid_currencies) 
                ? $settings['currency'] : 'EUR',
            'price_format'          => isset($settings['price_format']) && in_array($settings['price_format'], $valid_formats) 
                ? $settings['price_format'] : 'symbol_before',
            'tax_enabled'           => $parse_bool($settings['tax_enabled'] ?? false),
            'tax_rate'              => isset($settings['tax_rate']) ? floatval($settings['tax_rate']) : 19,
            'tax_included'          => $parse_bool($settings['tax_included'] ?? true),
            'guest_checkout'        => $parse_bool($settings['guest_checkout'] ?? true),
            'max_tickets_per_order' => isset($settings['max_tickets_per_order']) ? absint($settings['max_tickets_per_order']) : 10,
            'hold_time'             => isset($settings['hold_time']) ? absint($settings['hold_time']) : 15,
            'terms_page'            => isset($settings['terms_page']) ? absint($settings['terms_page']) : 0,
        );
    }

} // End class ES_Tickets_Pro_Addon

/**
 * Get Tickets Pro addon instance
 *
 * @return ES_Tickets_Pro_Addon
 */
function ES_Tickets_Pro() {
    return ES_Tickets_Pro_Addon::instance();
}

