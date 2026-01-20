<?php
/**
 * Ensemble Booking Engine Add-on
 * 
 * Central booking system for reservations and tickets
 * Provides unified database, email system, QR codes, and check-in
 *
 * @package Ensemble
 * @subpackage Addons
 * @since 3.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class ES_Booking_Engine_Addon extends ES_Addon_Base {
    
    /**
     * Add-on configuration
     */
    protected $slug = 'booking-engine';
    protected $name = 'Booking Engine';
    protected $version = '1.0.0';
    
    /**
     * Database table names
     */
    private $table_bookings;
    private $table_categories;
    private $table_floor_plans;
    private $table_coupons;
    private $table_coupon_usage;
    private $table_passes;
    private $table_customer_passes;
    private $table_pass_usage;
    private $table_waitlist;
    
    /**
     * Booking statuses
     */
    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_CHECKED_IN = 'checked_in';
    const STATUS_NO_SHOW = 'no_show';
    
    /**
     * Booking types
     */
    const TYPE_RESERVATION = 'reservation';
    const TYPE_TICKET = 'ticket';
    
    /**
     * Payment statuses
     */
    const PAYMENT_NONE = 'none';
    const PAYMENT_PENDING = 'pending';
    const PAYMENT_PAID = 'paid';
    const PAYMENT_REFUNDED = 'refunded';
    const PAYMENT_FAILED = 'failed';
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * Get instance
     * 
     * @return ES_Booking_Engine_Addon
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize add-on
     */
    protected function init() {
        global $wpdb;
        
        // Set table names
        $this->table_bookings = $wpdb->prefix . 'ensemble_bookings';
        $this->table_categories = $wpdb->prefix . 'ensemble_ticket_categories';
        $this->table_floor_plans = $wpdb->prefix . 'ensemble_floor_plans';
        $this->table_coupons = $wpdb->prefix . 'ensemble_coupons';
        $this->table_coupon_usage = $wpdb->prefix . 'ensemble_coupon_usage';
        $this->table_passes = $wpdb->prefix . 'ensemble_passes';
        $this->table_customer_passes = $wpdb->prefix . 'ensemble_customer_passes';
        $this->table_pass_usage = $wpdb->prefix . 'ensemble_pass_usage';
        $this->table_waitlist = $wpdb->prefix . 'ensemble_waitlist';
        
        // Create tables
        $this->maybe_create_tables();
        
        // Load sub-classes
        $this->load_dependencies();
        
        $this->log('Booking Engine add-on initialized');
    }
    
    /**
     * Load dependencies
     * 
     * Note: Core functionality is integrated in this class.
     * Sub-classes (ES_Booking, ES_Booking_Email, ES_Booking_QR) are optional
     * and will be loaded if they exist for advanced customization.
     */
    private function load_dependencies() {
        $addon_path = $this->get_addon_path();
        
        // Optional: Model class for advanced OOP usage
        if (file_exists($addon_path . 'class-es-booking.php')) {
            require_once $addon_path . 'class-es-booking.php';
        }
        
        // Optional: Dedicated email manager for template customization
        if (file_exists($addon_path . 'class-es-booking-email.php')) {
            require_once $addon_path . 'class-es-booking-email.php';
        }
        
        // Optional: Local QR code generator (replaces external API)
        if (file_exists($addon_path . 'class-es-booking-qr.php')) {
            require_once $addon_path . 'class-es-booking-qr.php';
        }
    }
    
    /**
     * Register hooks
     */
    protected function register_hooks() {
        // Admin hooks
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('admin_menu', array($this, 'add_admin_menu'), 20);
        
        // Frontend hooks
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        
        // Shortcodes
        add_shortcode('ensemble_booking', array($this, 'shortcode_booking_form'));
        add_shortcode('es_booking', array($this, 'shortcode_booking_form')); // Alias
        add_shortcode('ensemble_booking_floor_plan', array($this, 'shortcode_booking_with_floor_plan'));
        
        // AJAX handlers - Admin
        add_action('wp_ajax_es_get_bookings', array($this, 'ajax_get_bookings'));
        add_action('wp_ajax_es_get_floor_plan_info', array($this, 'ajax_get_floor_plan_info'));
        add_action('wp_ajax_es_get_booking', array($this, 'ajax_get_booking'));
        add_action('wp_ajax_es_create_booking', array($this, 'ajax_create_booking'));
        add_action('wp_ajax_es_update_booking', array($this, 'ajax_update_booking'));
        add_action('wp_ajax_es_update_booking_status', array($this, 'ajax_update_booking_status'));
        add_action('wp_ajax_es_delete_booking', array($this, 'ajax_delete_booking'));
        add_action('wp_ajax_es_checkin_booking', array($this, 'ajax_checkin_booking'));
        add_action('wp_ajax_es_export_bookings', array($this, 'ajax_export_bookings'));
        add_action('wp_ajax_es_resend_booking_email', array($this, 'ajax_resend_email'));
        
        // AJAX handlers - Public
        add_action('wp_ajax_es_submit_booking', array($this, 'ajax_submit_booking'));
        add_action('wp_ajax_nopriv_es_submit_booking', array($this, 'ajax_submit_booking'));
        add_action('wp_ajax_es_check_booking_availability', array($this, 'ajax_check_availability'));
        add_action('wp_ajax_nopriv_es_check_booking_availability', array($this, 'ajax_check_availability'));
        add_action('wp_ajax_es_cancel_booking', array($this, 'ajax_cancel_booking'));
        add_action('wp_ajax_nopriv_es_cancel_booking', array($this, 'ajax_cancel_booking'));
        add_action('wp_ajax_es_join_waitlist', array($this, 'ajax_join_waitlist'));
        add_action('wp_ajax_nopriv_es_join_waitlist', array($this, 'ajax_join_waitlist'));
        
        // QR Check-in handler
        add_action('template_redirect', array($this, 'handle_qr_checkin'));
        
        // Cancellation page handler
        add_action('template_redirect', array($this, 'handle_cancellation_page'));
        
        // REST API
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        
        // Provide API for other addons
        add_filter('ensemble_booking_engine', array($this, 'get_api'));
        
        // Wizard integration
        // Wizard integration - Card in Step 4 (Tickets & Price)
        add_action('ensemble_wizard_tickets_cards', array($this, 'render_wizard_booking_card'));
        add_filter('ensemble_wizard_event_data', array($this, 'add_wizard_event_data'), 10, 2);
        add_action('ensemble_wizard_save_event', array($this, 'save_wizard_event_data'), 10, 2);
        add_action('wp_ajax_es_get_booking_stats', array($this, 'ajax_get_booking_stats'));
        add_action('ensemble_event_after_content', array($this, 'render_event_booking_section'), 20);
        add_action('ensemble_event_booking_section', array($this, 'render_event_booking_section'), 10);
        
        // AJAX handler for saving booking settings from wizard
        add_action('wp_ajax_es_save_booking_settings', array($this, 'ajax_save_booking_settings'));
        add_action('wp_ajax_es_get_booking_settings', array($this, 'ajax_get_booking_settings'));
        
        // Also hook into save_post for fallback (when wizard saves via standard post save)
        add_action('save_post', array($this, 'save_booking_settings_on_post_save'), 20, 2);
    }
    
    
    /**
     * Render booking card in wizard Step 4 (Tickets & Price)
     */
    public function render_wizard_booking_card($event_id = 0) {
        // Make event_id available to the template
        $GLOBALS['es_wizard_event_id'] = $event_id;
        include $this->get_addon_path() . 'templates/wizard-booking-card.php';
    }
    
    
    
    /**
     * Add booking data to wizard event data
     * 
     * @param array   $event
     * @param WP_Post $post
     * @return array
     */
    public function add_wizard_event_data($event, $post) {
        // Booking mode
        $event['booking_mode'] = get_post_meta($post->ID, '_booking_mode', true) ?: 'none';
        
        // Reservation settings
        $types = get_post_meta($post->ID, '_reservation_types', true);
        $event['reservation_types'] = is_array($types) ? $types : array('guestlist');
        $event['reservation_capacity'] = get_post_meta($post->ID, '_booking_capacity', true);
        $event['reservation_capacity_guestlist'] = get_post_meta($post->ID, '_reservation_capacity_guestlist', true);
        $event['reservation_capacity_table'] = get_post_meta($post->ID, '_reservation_capacity_table', true);
        $event['reservation_capacity_vip'] = get_post_meta($post->ID, '_reservation_capacity_vip', true);
        $event['reservation_max_guests'] = get_post_meta($post->ID, '_reservation_max_guests', true) ?: 10;
        $event['reservation_deadline_hours'] = get_post_meta($post->ID, '_reservation_deadline_hours', true) ?: 24;
        $event['reservation_auto_confirm'] = get_post_meta($post->ID, '_reservation_auto_confirm', true) == '1';
        $event['booking_floor_plan_id'] = get_post_meta($post->ID, '_booking_floor_plan_id', true) ?: '';
        $event['reservation_ticket_mode'] = get_post_meta($post->ID, '_reservation_ticket_mode', true) ?: 'none';
        
        return $event;
    }
    
    /**
     * Save booking data from wizard
     * 
     * @param int   $event_id
     * @param array $data
     */
    public function save_wizard_event_data($event_id, $data) {
        // Debug: Log incoming data
        error_log('Booking Engine save_wizard_event_data - Event ID: ' . $event_id);
        error_log('Booking Engine - booking_mode: ' . ($data['booking_mode'] ?? 'NOT SET'));
        error_log('Booking Engine - reservation_types: ' . print_r($data['reservation_types'] ?? 'NOT SET', true));
        error_log('Booking Engine - booking_floor_plan_id: ' . ($data['booking_floor_plan_id'] ?? 'NOT SET'));
        
        // Booking mode
        if (isset($data['booking_mode'])) {
            update_post_meta($event_id, '_booking_mode', sanitize_key($data['booking_mode']));
        }
        
        // Reservation types
        if (isset($data['reservation_types'])) {
            $types = is_array($data['reservation_types']) 
                ? array_map('sanitize_key', $data['reservation_types']) 
                : array('guestlist');
            update_post_meta($event_id, '_reservation_types', $types);
        }
        
        // Capacities
        if (isset($data['reservation_capacity'])) {
            update_post_meta($event_id, '_booking_capacity', absint($data['reservation_capacity']));
        }
        if (isset($data['reservation_capacity_guestlist'])) {
            update_post_meta($event_id, '_reservation_capacity_guestlist', absint($data['reservation_capacity_guestlist']));
        }
        if (isset($data['reservation_capacity_table'])) {
            update_post_meta($event_id, '_reservation_capacity_table', absint($data['reservation_capacity_table']));
        }
        if (isset($data['reservation_capacity_vip'])) {
            update_post_meta($event_id, '_reservation_capacity_vip', absint($data['reservation_capacity_vip']));
        }
        
        // Other settings
        if (isset($data['reservation_max_guests'])) {
            update_post_meta($event_id, '_reservation_max_guests', absint($data['reservation_max_guests']));
        }
        if (isset($data['reservation_deadline_hours'])) {
            update_post_meta($event_id, '_reservation_deadline_hours', absint($data['reservation_deadline_hours']));
        }
        if (isset($data['reservation_auto_confirm'])) {
            update_post_meta($event_id, '_reservation_auto_confirm', $data['reservation_auto_confirm'] ? '1' : '0');
        }
        // Floor Plan ID - accept both field names for compatibility
        if (isset($data['booking_floor_plan_id']) && $data['booking_floor_plan_id'] !== '') {
            update_post_meta($event_id, '_booking_floor_plan_id', absint($data['booking_floor_plan_id']));
        } elseif (isset($data['reservation_floor_plan_id']) && $data['reservation_floor_plan_id'] !== '') {
            update_post_meta($event_id, '_booking_floor_plan_id', absint($data['reservation_floor_plan_id']));
        } elseif (isset($data['booking_floor_plan_id']) || isset($data['reservation_floor_plan_id'])) {
            // Explicitly set to empty/delete if field exists but is empty
            delete_post_meta($event_id, '_booking_floor_plan_id');
        }
        if (isset($data['reservation_ticket_mode'])) {
            update_post_meta($event_id, '_reservation_ticket_mode', sanitize_key($data['reservation_ticket_mode']));
        }
    }
    
    /**
     * AJAX: Get booking stats for wizard
     */
    public function ajax_get_booking_stats() {
        check_ajax_referer('ensemble_wizard', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'ensemble')));
            return;
        }
        
        $event_id = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;
        
        if (!$event_id) {
            wp_send_json_error(array('message' => __('Invalid event ID', 'ensemble')));
            return;
        }
        
        global $wpdb;
        
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'checked_in' THEN 1 ELSE 0 END) as checked_in
            FROM {$this->get_table_bookings()}
            WHERE event_id = %d",
            $event_id
        ), ARRAY_A);
        
        wp_send_json_success($stats ?: array(
            'total'      => 0,
            'confirmed'  => 0,
            'pending'    => 0,
            'checked_in' => 0,
        ));
    }
    
    /**
     * AJAX: Save booking settings from wizard
     * 
     * This is a dedicated AJAX handler for saving booking engine settings
     * separately from the main wizard save process.
     */
    public function ajax_save_booking_settings() {
        // Accept both wizard nonce and general nonce
        $nonce_valid = wp_verify_nonce($_POST['nonce'] ?? '', 'ensemble_nonce') 
                    || wp_verify_nonce($_POST['nonce'] ?? '', 'ensemble_wizard');
        
        if (!$nonce_valid) {
            wp_send_json_error(array('message' => __('Invalid security token', 'ensemble')));
            return;
        }
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'ensemble')));
            return;
        }
        
        $event_id = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;
        
        if (!$event_id) {
            wp_send_json_error(array('message' => __('Invalid event ID', 'ensemble')));
            return;
        }
        
        // Log what we received
        error_log('Booking Engine ajax_save_booking_settings - Event ID: ' . $event_id);
        error_log('Booking Engine ajax_save_booking_settings - POST data: ' . print_r($_POST, true));
        
        // Save booking mode
        if (isset($_POST['booking_mode'])) {
            update_post_meta($event_id, '_booking_mode', sanitize_key($_POST['booking_mode']));
        }
        
        // Save reservation types
        if (isset($_POST['reservation_types'])) {
            $types = is_array($_POST['reservation_types']) 
                ? array_map('sanitize_key', $_POST['reservation_types']) 
                : array('guestlist');
            update_post_meta($event_id, '_reservation_types', $types);
        }
        
        // Save capacities
        if (isset($_POST['reservation_capacity'])) {
            update_post_meta($event_id, '_booking_capacity', absint($_POST['reservation_capacity']));
        }
        if (isset($_POST['reservation_capacity_guestlist'])) {
            update_post_meta($event_id, '_reservation_capacity_guestlist', absint($_POST['reservation_capacity_guestlist']));
        }
        if (isset($_POST['reservation_capacity_table'])) {
            update_post_meta($event_id, '_reservation_capacity_table', absint($_POST['reservation_capacity_table']));
        }
        if (isset($_POST['reservation_capacity_vip'])) {
            update_post_meta($event_id, '_reservation_capacity_vip', absint($_POST['reservation_capacity_vip']));
        }
        
        // Save other settings
        if (isset($_POST['reservation_max_guests'])) {
            update_post_meta($event_id, '_reservation_max_guests', absint($_POST['reservation_max_guests']));
        }
        if (isset($_POST['reservation_deadline_hours'])) {
            update_post_meta($event_id, '_reservation_deadline_hours', absint($_POST['reservation_deadline_hours']));
        }
        
        // Auto confirm (checkbox)
        $auto_confirm = isset($_POST['reservation_auto_confirm']) && $_POST['reservation_auto_confirm'];
        update_post_meta($event_id, '_reservation_auto_confirm', $auto_confirm ? '1' : '0');
        
        // Floor Plan ID
        if (isset($_POST['booking_floor_plan_id']) && $_POST['booking_floor_plan_id'] !== '') {
            update_post_meta($event_id, '_booking_floor_plan_id', absint($_POST['booking_floor_plan_id']));
            error_log('Booking Engine: Saved floor plan ID: ' . absint($_POST['booking_floor_plan_id']));
        } else {
            delete_post_meta($event_id, '_booking_floor_plan_id');
            error_log('Booking Engine: Deleted floor plan ID (empty value)');
        }
        
        // Ticket mode
        if (isset($_POST['reservation_ticket_mode'])) {
            update_post_meta($event_id, '_reservation_ticket_mode', sanitize_key($_POST['reservation_ticket_mode']));
        }
        
        wp_send_json_success(array(
            'message' => __('Booking settings saved', 'ensemble'),
            'event_id' => $event_id,
        ));
    }
    
    /**
     * Get booking settings via AJAX
     * 
     * Returns all booking settings for a given event ID.
     * Used by the wizard to populate the booking card.
     */
    public function ajax_get_booking_settings() {
        // Accept both wizard nonce and general nonce
        $nonce_valid = wp_verify_nonce($_POST['nonce'] ?? '', 'ensemble_nonce') 
                    || wp_verify_nonce($_POST['nonce'] ?? '', 'ensemble_wizard');
        
        if (!$nonce_valid) {
            wp_send_json_error(array('message' => __('Invalid security token', 'ensemble')));
            return;
        }
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'ensemble')));
            return;
        }
        
        $event_id = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;
        
        if (!$event_id) {
            wp_send_json_error(array('message' => __('Invalid event ID', 'ensemble')));
            return;
        }
        
        // Get all booking settings
        $types = get_post_meta($event_id, '_reservation_types', true);
        
        $data = array(
            'booking_mode'                   => get_post_meta($event_id, '_booking_mode', true) ?: 'none',
            'reservation_types'              => is_array($types) ? $types : array('guestlist'),
            'booking_floor_plan_id'          => get_post_meta($event_id, '_booking_floor_plan_id', true) ?: '',
            'reservation_capacity'           => get_post_meta($event_id, '_booking_capacity', true) ?: '',
            'reservation_capacity_guestlist' => get_post_meta($event_id, '_reservation_capacity_guestlist', true) ?: '',
            'reservation_capacity_table'     => get_post_meta($event_id, '_reservation_capacity_table', true) ?: '',
            'reservation_capacity_vip'       => get_post_meta($event_id, '_reservation_capacity_vip', true) ?: '',
            'reservation_max_guests'         => get_post_meta($event_id, '_reservation_max_guests', true) ?: 10,
            'reservation_deadline_hours'     => get_post_meta($event_id, '_reservation_deadline_hours', true) ?: 24,
            'reservation_auto_confirm'       => get_post_meta($event_id, '_reservation_auto_confirm', true) == '1',
            'reservation_ticket_mode'        => get_post_meta($event_id, '_reservation_ticket_mode', true) ?: 'none',
        );
        
        error_log('Booking Engine: get_booking_settings for event ' . $event_id . ' - ' . print_r($data, true));
        
        wp_send_json_success($data);
    }
    
    /**
     * Save booking settings when post is saved (fallback)
     * 
     * This hooks into save_post to catch booking data that might be 
     * submitted via the standard WordPress post save mechanism.
     * 
     * @param int     $post_id Post ID
     * @param WP_Post $post    Post object
     */
    public function save_booking_settings_on_post_save($post_id, $post) {
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
        
        // Check if booking data is in POST
        if (!isset($_POST['booking_mode'])) {
            return;
        }
        
        error_log('Booking Engine save_booking_settings_on_post_save - Event ID: ' . $post_id);
        
        // Save booking mode
        if (isset($_POST['booking_mode'])) {
            update_post_meta($post_id, '_booking_mode', sanitize_key($_POST['booking_mode']));
        }
        
        // Save reservation types
        if (isset($_POST['reservation_types'])) {
            $types = is_array($_POST['reservation_types']) 
                ? array_map('sanitize_key', $_POST['reservation_types']) 
                : array('guestlist');
            update_post_meta($post_id, '_reservation_types', $types);
        }
        
        // Save floor plan
        if (isset($_POST['booking_floor_plan_id']) && $_POST['booking_floor_plan_id'] !== '') {
            update_post_meta($post_id, '_booking_floor_plan_id', absint($_POST['booking_floor_plan_id']));
        } elseif (isset($_POST['booking_floor_plan_id'])) {
            delete_post_meta($post_id, '_booking_floor_plan_id');
        }
        
        // Save capacities
        $capacity_fields = array(
            'reservation_capacity' => '_booking_capacity',
            'reservation_capacity_guestlist' => '_reservation_capacity_guestlist',
            'reservation_capacity_table' => '_reservation_capacity_table',
            'reservation_capacity_vip' => '_reservation_capacity_vip',
            'reservation_max_guests' => '_reservation_max_guests',
            'reservation_deadline_hours' => '_reservation_deadline_hours',
        );
        
        foreach ($capacity_fields as $post_key => $meta_key) {
            if (isset($_POST[$post_key])) {
                update_post_meta($post_id, $meta_key, absint($_POST[$post_key]));
            }
        }
        
        // Auto confirm
        $auto_confirm = isset($_POST['reservation_auto_confirm']) && $_POST['reservation_auto_confirm'];
        update_post_meta($post_id, '_reservation_auto_confirm', $auto_confirm ? '1' : '0');
        
        // Ticket mode
        if (isset($_POST['reservation_ticket_mode'])) {
            update_post_meta($post_id, '_reservation_ticket_mode', sanitize_key($_POST['reservation_ticket_mode']));
        }
    }
    
    /**
     * Get API instance for other addons
     * 
     * @return ES_Booking_Engine_Addon
     */
    public function get_api() {
        return $this;
    }
    
    /**
     * Create database tables
     */
    private function maybe_create_tables() {
        global $wpdb;
        
        // Check if main table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->table_bookings}'") === $this->table_bookings;
        
        if ($table_exists) {
            return;
        }
        
        $charset_collate = $wpdb->get_charset_collate();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Bookings table
        $sql_bookings = "CREATE TABLE {$this->table_bookings} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            event_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned DEFAULT NULL,
            booking_type varchar(20) NOT NULL DEFAULT 'reservation',
            booking_subtype varchar(50) DEFAULT NULL,
            floor_plan_id bigint(20) unsigned DEFAULT NULL,
            element_id varchar(50) DEFAULT NULL,
            element_type varchar(30) DEFAULT NULL,
            element_label varchar(100) DEFAULT NULL,
            category_id bigint(20) unsigned DEFAULT NULL,
            category_name varchar(100) DEFAULT NULL,
            price decimal(10,2) DEFAULT 0.00,
            currency char(3) DEFAULT 'EUR',
            payment_status varchar(20) DEFAULT 'none',
            payment_method varchar(50) DEFAULT NULL,
            payment_id varchar(255) DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            quantity int(10) unsigned DEFAULT 1,
            guests int(10) unsigned DEFAULT 1,
            customer_name varchar(255) NOT NULL,
            customer_email varchar(255) NOT NULL,
            customer_phone varchar(50) DEFAULT '',
            guest_names text DEFAULT NULL,
            customer_notes text DEFAULT NULL,
            internal_notes text DEFAULT NULL,
            booking_data longtext DEFAULT NULL,
            confirmation_code varchar(32) NOT NULL,
            qr_code varchar(255) DEFAULT NULL,
            cancel_token varchar(64) DEFAULT NULL,
            cancel_token_expires datetime DEFAULT NULL,
            checked_in_at datetime DEFAULT NULL,
            checked_in_by bigint(20) unsigned DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY idx_event (event_id),
            KEY idx_status (status),
            KEY idx_type (booking_type),
            KEY idx_email (customer_email),
            KEY idx_confirmation (confirmation_code),
            KEY idx_payment (payment_status),
            KEY idx_floor_plan (floor_plan_id, element_id)
        ) $charset_collate;";
        
        dbDelta($sql_bookings);
        
        // Ticket categories table
        $sql_categories = "CREATE TABLE {$this->table_categories} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            event_id bigint(20) unsigned NOT NULL,
            name varchar(100) NOT NULL,
            description text DEFAULT NULL,
            price decimal(10,2) NOT NULL,
            currency char(3) DEFAULT 'EUR',
            capacity int(10) unsigned DEFAULT NULL,
            sold int(10) unsigned DEFAULT 0,
            floor_plan_id bigint(20) unsigned DEFAULT NULL,
            floor_plan_zone varchar(50) DEFAULT NULL,
            sale_start datetime DEFAULT NULL,
            sale_end datetime DEFAULT NULL,
            min_quantity int(10) unsigned DEFAULT 1,
            max_quantity int(10) unsigned DEFAULT 10,
            status varchar(20) DEFAULT 'active',
            sort_order int(11) DEFAULT 0,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY idx_event (event_id),
            KEY idx_status (status)
        ) $charset_collate;";
        
        dbDelta($sql_categories);
        
        // Floor plans table
        $sql_floor_plans = "CREATE TABLE {$this->table_floor_plans} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            location_id bigint(20) unsigned DEFAULT NULL,
            name varchar(100) NOT NULL,
            description text DEFAULT NULL,
            canvas_width int(10) unsigned DEFAULT 800,
            canvas_height int(10) unsigned DEFAULT 600,
            background_image varchar(255) DEFAULT NULL,
            background_color varchar(7) DEFAULT '#ffffff',
            elements longtext NOT NULL,
            zones text DEFAULT NULL,
            status varchar(20) DEFAULT 'draft',
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY idx_location (location_id),
            KEY idx_status (status)
        ) $charset_collate;";
        
        dbDelta($sql_floor_plans);
        
        // Coupons table
        $sql_coupons = "CREATE TABLE {$this->table_coupons} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            code varchar(50) NOT NULL,
            description varchar(255) DEFAULT NULL,
            discount_type varchar(20) NOT NULL,
            discount_value decimal(10,2) NOT NULL,
            valid_from datetime DEFAULT NULL,
            valid_until datetime DEFAULT NULL,
            scope varchar(20) DEFAULT 'universal',
            scope_ids text DEFAULT NULL,
            min_order_amount decimal(10,2) DEFAULT 0.00,
            max_discount decimal(10,2) DEFAULT NULL,
            usage_limit int(10) unsigned DEFAULT NULL,
            usage_limit_per_user int(10) unsigned DEFAULT 1,
            usage_count int(10) unsigned DEFAULT 0,
            combinable tinyint(1) DEFAULT 0,
            status varchar(20) DEFAULT 'active',
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY idx_code (code),
            KEY idx_status (status),
            KEY idx_valid (valid_from, valid_until)
        ) $charset_collate;";
        
        dbDelta($sql_coupons);
        
        // Coupon usage table
        $sql_coupon_usage = "CREATE TABLE {$this->table_coupon_usage} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            coupon_id bigint(20) unsigned NOT NULL,
            booking_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned DEFAULT NULL,
            user_email varchar(255) NOT NULL,
            discount_applied decimal(10,2) NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY idx_coupon (coupon_id),
            KEY idx_booking (booking_id),
            KEY idx_user (user_email)
        ) $charset_collate;";
        
        dbDelta($sql_coupon_usage);
        
        // Passes table
        $sql_passes = "CREATE TABLE {$this->table_passes} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            description text DEFAULT NULL,
            pass_type varchar(20) NOT NULL,
            price decimal(10,2) NOT NULL,
            currency char(3) DEFAULT 'EUR',
            punch_count int(10) unsigned DEFAULT NULL,
            duration_days int(10) unsigned DEFAULT NULL,
            recurring tinyint(1) DEFAULT 0,
            recurring_interval varchar(20) DEFAULT NULL,
            scope varchar(20) DEFAULT 'universal',
            scope_ids text DEFAULT NULL,
            max_uses_per_day int(10) unsigned DEFAULT NULL,
            status varchar(20) DEFAULT 'active',
            sort_order int(11) DEFAULT 0,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY idx_status (status),
            KEY idx_type (pass_type)
        ) $charset_collate;";
        
        dbDelta($sql_passes);
        
        // Customer passes table
        $sql_customer_passes = "CREATE TABLE {$this->table_customer_passes} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            pass_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned DEFAULT NULL,
            customer_email varchar(255) NOT NULL,
            customer_name varchar(255) NOT NULL,
            status varchar(20) DEFAULT 'active',
            punches_remaining int(10) unsigned DEFAULT NULL,
            punches_used int(10) unsigned DEFAULT 0,
            valid_from datetime NOT NULL,
            valid_until datetime DEFAULT NULL,
            subscription_id varchar(255) DEFAULT NULL,
            next_payment_date datetime DEFAULT NULL,
            payment_status varchar(20) DEFAULT 'paid',
            payment_method varchar(50) DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY idx_pass (pass_id),
            KEY idx_customer (customer_email),
            KEY idx_status (status),
            KEY idx_valid (valid_until)
        ) $charset_collate;";
        
        dbDelta($sql_customer_passes);
        
        // Pass usage table
        $sql_pass_usage = "CREATE TABLE {$this->table_pass_usage} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            customer_pass_id bigint(20) unsigned NOT NULL,
            booking_id bigint(20) unsigned NOT NULL,
            event_id bigint(20) unsigned NOT NULL,
            used_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY idx_pass (customer_pass_id),
            KEY idx_booking (booking_id)
        ) $charset_collate;";
        
        dbDelta($sql_pass_usage);
        
        // Waitlist table
        $sql_waitlist = "CREATE TABLE {$this->table_waitlist} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            event_id bigint(20) unsigned NOT NULL,
            category_id bigint(20) unsigned DEFAULT NULL,
            element_id varchar(50) DEFAULT NULL,
            customer_name varchar(255) NOT NULL,
            customer_email varchar(255) NOT NULL,
            customer_phone varchar(50) DEFAULT '',
            quantity int(10) unsigned DEFAULT 1,
            status varchar(20) DEFAULT 'waiting',
            notified_at datetime DEFAULT NULL,
            expires_at datetime DEFAULT NULL,
            booking_id bigint(20) unsigned DEFAULT NULL,
            position int(10) unsigned DEFAULT 0,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY idx_event (event_id),
            KEY idx_status (status),
            KEY idx_email (customer_email),
            KEY idx_position (event_id, position)
        ) $charset_collate;";
        
        dbDelta($sql_waitlist);
        
        // Run migrations for existing installations
        $this->run_table_migrations();
        
        $this->log('Booking Engine tables created');
    }
    
    /**
     * Run table migrations for existing installations
     */
    private function run_table_migrations() {
        global $wpdb;
        
        // Check if booking_data column exists in bookings table
        $column_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
             WHERE TABLE_SCHEMA = %s 
             AND TABLE_NAME = %s 
             AND COLUMN_NAME = 'booking_data'",
            DB_NAME,
            $this->table_bookings
        ));
        
        if (!$column_exists) {
            $wpdb->query("ALTER TABLE {$this->table_bookings} ADD COLUMN booking_data longtext DEFAULT NULL AFTER internal_notes");
            $this->log('Added booking_data column to bookings table');
        }
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Load on bookings page
        $load_on_bookings = strpos($hook, 'ensemble-bookings') !== false;
        
        // Load on wizard (main ensemble page)
        $load_on_wizard = $hook === 'toplevel_page_ensemble';
        
        if (!$load_on_bookings && !$load_on_wizard) {
            return;
        }
        
        // Ensure unified CSS is loaded first
        wp_enqueue_style(
            'ensemble-booking-engine-admin',
            $this->get_addon_url() . 'assets/booking-engine-admin.css',
            array('ensemble-admin-unified'),
            $this->version
        );
        
        wp_enqueue_script(
            'ensemble-booking-engine-admin',
            $this->get_addon_url() . 'assets/booking-engine-admin.js',
            array('jquery'),
            $this->version,
            true
        );
        
        wp_localize_script('ensemble-booking-engine-admin', 'ensembleBookingEngine', array(
            'ajaxUrl'  => admin_url('admin-ajax.php'),
            'adminUrl' => admin_url(),
            'nonce'    => wp_create_nonce('ensemble_booking_engine'),
            'strings'  => array(
                'confirmDelete'    => __('Really delete this booking?', 'ensemble'),
                'confirmCheckin'   => __('Confirm check-in?', 'ensemble'),
                'confirmCancel'    => __('Really cancel this booking?', 'ensemble'),
                'statusUpdated'    => __('Status updated', 'ensemble'),
                'deleted'          => __('Deleted', 'ensemble'),
                'checkedIn'        => __('Checked in!', 'ensemble'),
                'loading'          => __('Loading...', 'ensemble'),
                'error'            => __('An error occurred', 'ensemble'),
                'noBookings'       => __('No bookings found', 'ensemble'),
                // Action button titles
                'view'             => __('View', 'ensemble'),
                'confirm'          => __('Confirm', 'ensemble'),
                'checkin'          => __('Check In', 'ensemble'),
                'cancel'           => __('Cancel', 'ensemble'),
                'delete'           => __('Delete', 'ensemble'),
            ),
        ));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'ensemble',
            __('Bookings', 'ensemble'),
            __('Bookings', 'ensemble'),
            'edit_posts',
            'ensemble-bookings',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        // Load events for dropdown
        $event_post_type = 'ensemble_event';
        if (function_exists('ensemble_get_post_type')) {
            $event_post_type = ensemble_get_post_type();
        }
        
        $events = get_posts(array(
            'post_type'      => $event_post_type,
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'post_status'    => 'publish',
        ));
        
        echo $this->load_template('admin-page', array(
            'addon'  => $this,
            'events' => $events,
        ));
    }
    
    /**
 * Enqueue frontend assets
 */
public function enqueue_frontend_assets() {
    static $assets_loaded = false;
    
    // Prevent double-loading
    if ($assets_loaded) {
        return;
    }
    
    // Check if we should load
    global $post;
    $should_load = false;
    
    if (is_singular()) {
        // Check if shortcode is in content
        if ($post) {
            $shortcodes = array('ensemble_booking', 'es_booking', 'ensemble_booking_floor_plan');
            foreach ($shortcodes as $shortcode) {
                if (has_shortcode($post->post_content, $shortcode)) {
                    $should_load = true;
                    break;
                }
            }
        }
        
        // Load on single event pages
        $event_post_type = function_exists('ensemble_get_post_type') ? ensemble_get_post_type() : 'ensemble_event';
        if (is_singular($event_post_type)) {
            $should_load = true;
        }
    }
    
    // Allow forcing load via filter
    $should_load = apply_filters('ensemble_booking_load_assets', $should_load);
    
    if (!$should_load) {
        return;
    }
    
    // Main booking frontend styles
    wp_enqueue_style(
        'ensemble-booking-frontend',
        $this->get_addon_url() . 'assets/booking-engine-frontend.css',
        array(),
        $this->version
    );
    
    // Main booking frontend script
    wp_enqueue_script(
        'ensemble-booking-frontend',
        $this->get_addon_url() . 'assets/booking-engine-frontend.js',
        array('jquery'),
        $this->version,
        true
    );
    
    wp_localize_script('ensemble-booking-frontend', 'ensembleBookingFrontend', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('ensemble_booking_public'),
        'strings' => array(
            'error'            => __('An error occurred. Please try again.', 'ensemble'),
            'spotsRemaining'   => __('spots remaining', 'ensemble'),
            'fullyBooked'      => __('Fully booked', 'ensemble'),
            'confirmationCode' => __('Confirmation code', 'ensemble'),
            'selectSpot'       => __('Please select a spot from the floor plan', 'ensemble'),
            'person'           => __('person', 'ensemble'),
            'people'           => __('people', 'ensemble'),
            'table'            => __('Table', 'ensemble'),
            'seat'             => __('Seat', 'ensemble'),
            'mat'              => __('Mat', 'ensemble'),
            'standing'         => __('Standing', 'ensemble'),
            'booth'            => __('Booth', 'ensemble'),
            'area'             => __('Area', 'ensemble'),
            'lounge'           => __('Lounge', 'ensemble'),
        ),
    ));
    
    // Check if event has floor plan - use correct meta key!
    $floor_plan_id = 0;
    if ($post) {
        $event_post_type = function_exists('ensemble_get_post_type') ? ensemble_get_post_type() : 'ensemble_event';
        if ($post->post_type === $event_post_type) {
            // Use correct meta key (was: _reservation_floor_plan_id)
            $floor_plan_id = get_post_meta($post->ID, '_booking_floor_plan_id', true);
        }
    }
    
    // Auto-load floor plan assets if floor plan is configured
    if ($floor_plan_id) {
        $this->enqueue_floor_plan_assets();
    }
    
    $assets_loaded = true;
}







    
    /**
     * Shortcode: Booking Form
     * 
     * Usage: [ensemble_booking event_id="123" type="reservation"]
     * 
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function shortcode_booking_form($atts) {
        $atts = shortcode_atts(array(
            'event_id' => 0,
            'type'     => 'reservation', // reservation or ticket
            'class'    => '',
        ), $atts, 'ensemble_booking');
        
        $event_id = absint($atts['event_id']);
        
        // If no event_id provided, try to get from current post
        if (!$event_id) {
            global $post;
            $event_post_type = function_exists('ensemble_get_post_type') ? ensemble_get_post_type() : 'ensemble_event';
            if ($post && $post->post_type === $event_post_type) {
                $event_id = $post->ID;
            }
        }
        
        if (!$event_id) {
            return '<!-- Booking form: No event ID provided -->';
        }
        
        $event = get_post($event_id);
        if (!$event) {
            return '<!-- Booking form: Event not found -->';
        }
        
        // Force load assets
        $this->enqueue_frontend_assets();
        
        return $this->load_template('booking-form', array(
            'event_id' => $event_id,
            'event'    => $event,
            'type'     => sanitize_key($atts['type']),
            'class'    => sanitize_html_class($atts['class']),
            'settings' => $this->settings,
            'addon'    => $this,
        ));
    }

    /**
 * Render booking section for event layouts
 * 
 * Hook: ensemble_event_after_content, ensemble_event_booking_section
 * Can also be triggered manually in layout templates
 * 
 * @param int|null $event_id Optional event ID (uses global $post if not provided)
 */
public function render_event_booking_section($event_id = null) {
    if (!$event_id) {
        global $post;
        $event_post_type = function_exists('ensemble_get_post_type') ? ensemble_get_post_type() : 'ensemble_event';
        if (!$post || $post->post_type !== $event_post_type) {
            return;
        }
        $event_id = $post->ID;
    }
    
    // Check if booking is enabled for this event
    $booking_enabled = get_post_meta($event_id, '_booking_enabled', true);
    $booking_mode = get_post_meta($event_id, '_booking_mode', true);
    
    // Fallback: Check old meta keys for backward compatibility
    if (empty($booking_mode) && empty($booking_enabled)) {
        $has_reservation = get_post_meta($event_id, '_reservation_enabled', true);
        $has_ticket = get_post_meta($event_id, '_ticket_enabled', true);
        
        if ($has_reservation) {
            $booking_mode = 'reservation';
        } elseif ($has_ticket) {
            $booking_mode = 'ticket';
        }
    }
    
    // No booking configured
    if (empty($booking_mode) || $booking_mode === 'none') {
        return;
    }
    
    // Check for floor plan
    $floor_plan_id = get_post_meta($event_id, '_booking_floor_plan_id', true);
    $has_floor_plan = $floor_plan_id && class_exists('ES_Floor_Plan_Addon');
    
    // Determine booking type
    // Floor plan bookings are always reservations
    // Tickets are handled separately via Tickets Pro addon
    if ($has_floor_plan || $booking_mode === 'reservation') {
        $booking_type = 'reservation';
    } else {
        $booking_type = 'ticket';
    }
    
    // Force load assets
    $this->enqueue_frontend_assets();
    
    // Load floor plan assets if needed
    if ($has_floor_plan) {
        $this->enqueue_floor_plan_assets();
    }
    
    // Add wrapper with appropriate classes
    $wrapper_class = 'es-event-booking-section';
    if ($has_floor_plan) {
        $wrapper_class .= ' es-booking-with-floor-plan-layout';
    }
    
    echo '<div class="' . esc_attr($wrapper_class) . '">';
    
    // Allow custom heading via filter
    $section_title = apply_filters('ensemble_booking_section_title', '', $event_id, $booking_type);
    if ($section_title) {
        echo '<h3 class="es-booking-section-title">' . esc_html($section_title) . '</h3>';
    }
    
    // Render the booking form
    echo $this->load_template('booking-form', array(
        'event_id' => $event_id,
        'event'    => get_post($event_id),
        'type'     => $booking_type,
        'settings' => $this->settings,
        'addon'    => $this,
    ));
    
    echo '</div>';
}

/**
 * Shortcode: Booking Form with Floor Plan
 * 
 * Renders the booking form with integrated floor plan selection.
 * 
 * Usage: [ensemble_booking_floor_plan event_id="123"]
 *        [ensemble_booking_floor_plan] (uses current event)
 * 
 * @param array $atts Shortcode attributes
 * @return string HTML output
 */
public function shortcode_booking_with_floor_plan($atts) {
    $atts = shortcode_atts(array(
        'event_id' => 0,
        'id'       => 0, // Alias for event_id
        'type'     => '', // auto-detect if empty
        'class'    => '',
        'title'    => '',
    ), $atts, 'ensemble_booking_floor_plan');
    
    // Support both 'event_id' and 'id' as attribute
    $event_id = absint($atts['event_id']) ?: absint($atts['id']);
    
    // If no event_id provided, try to get from current post
    if (!$event_id) {
        global $post;
        $event_post_type = function_exists('ensemble_get_post_type') ? ensemble_get_post_type() : 'ensemble_event';
        if ($post && $post->post_type === $event_post_type) {
            $event_id = $post->ID;
        }
    }
    
    if (!$event_id) {
        return '<!-- Booking form: No event ID provided -->';
    }
    
    $event = get_post($event_id);
    if (!$event) {
        return '<!-- Booking form: Event not found -->';
    }
    
    // Get floor plan ID
    $floor_plan_id = get_post_meta($event_id, '_booking_floor_plan_id', true);
    
    // Debug output
    error_log('Booking Floor Plan Shortcode - Event ID: ' . $event_id . ', Floor Plan ID: ' . $floor_plan_id);
    
    if (!$floor_plan_id) {
        // Fallback to regular booking form
        error_log('Booking Floor Plan Shortcode - No floor plan ID, using regular form');
        return $this->shortcode_booking_form(array(
            'event_id' => $event_id,
            'type'     => $atts['type'] ?: 'reservation',
            'class'    => $atts['class'],
        ));
    }
    
    // Determine booking type
    $type = $atts['type'];
    if (!$type) {
        $booking_mode = get_post_meta($event_id, '_booking_mode', true);
        $type = ($booking_mode === 'ticket') ? 'ticket' : 'reservation';
    }
    
    // Force load assets
    $this->enqueue_frontend_assets();
    $this->enqueue_floor_plan_assets();
    
    // Build output
    ob_start();
    
    $wrapper_class = 'es-booking-floor-plan-wrapper';
    if ($atts['class']) {
        $wrapper_class .= ' ' . esc_attr($atts['class']);
    }
    
    echo '<div class="' . $wrapper_class . '">';
    
    if ($atts['title']) {
        echo '<h3 class="es-booking-floor-plan-title">' . esc_html($atts['title']) . '</h3>';
    }
    
    echo $this->load_template('booking-form', array(
        'event_id' => $event_id,
        'event'    => $event,
        'type'     => $type,
        'settings' => $this->settings,
        'addon'    => $this,
    ));
    
    echo '</div>';
    
    return ob_get_clean();
}


/**
 * Enqueue floor plan assets for frontend
 * Separate method for explicit loading
 */
public function enqueue_floor_plan_assets() {
    if (!class_exists('ES_Floor_Plan_Addon') || !defined('ES_FLOOR_PLAN_URL')) {
        return;
    }
    
    // Konva.js
    if (!wp_script_is('konva', 'enqueued')) {
        wp_enqueue_script(
            'konva',
            'https://unpkg.com/konva@9/konva.min.js',
            array(),
            '9.3.6',
            true
        );
    }
    
    // Floor Plan Frontend JS - use same handle as Floor Plan Addon for consistency
    if (!wp_script_is('es-floor-plan-frontend', 'enqueued')) {
        wp_enqueue_script(
            'es-floor-plan-frontend',
            ES_FLOOR_PLAN_URL . 'assets/floor-plan-frontend.js',
            array('jquery', 'konva'),
            defined('ES_FLOOR_PLAN_VERSION') ? ES_FLOOR_PLAN_VERSION : '1.0.0',
            true
        );
        
        wp_localize_script('es-floor-plan-frontend', 'esFloorPlanFrontend', array(
            'ajaxUrl'        => admin_url('admin-ajax.php'),
            'nonce'          => wp_create_nonce('ensemble_public_nonce'),
            'currencySymbol' => get_option('ensemble_currency_symbol', '€'),
            'strings'        => array(
                'seats'      => __('seats', 'ensemble'),
                'available'  => __('available', 'ensemble'),
                'soldOut'    => __('Sold Out', 'ensemble'),
                'perPerson'  => __('per person', 'ensemble'),
            ),
        ));
    }
    
    // Floor Plan Frontend CSS - use same handle as Floor Plan Addon
    if (!wp_style_is('es-floor-plan-frontend', 'enqueued')) {
        wp_enqueue_style(
            'es-floor-plan-frontend',
            ES_FLOOR_PLAN_URL . 'assets/floor-plan-frontend.css',
            array(),
            defined('ES_FLOOR_PLAN_VERSION') ? ES_FLOOR_PLAN_VERSION : '1.0.0'
        );
    }
}
    
    /**
     * Render settings page for addon manager
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
     * Sanitize settings
     * 
     * @param array $settings
     * @return array
     */
    public function sanitize_settings($settings) {
        return array(
            'auto_confirm'               => !empty($settings['auto_confirm']),
            'auto_send_email'            => !empty($settings['auto_send_email']),
            'send_status_emails'         => !empty($settings['send_status_emails']),
            'default_capacity'           => absint($settings['default_capacity'] ?? 100),
            'max_guests_per_booking'     => absint($settings['max_guests_per_booking'] ?? 10),
            'require_phone'              => !empty($settings['require_phone']),
            'enable_waitlist'            => !empty($settings['enable_waitlist']),
            'waitlist_auto_process'      => !empty($settings['waitlist_auto_process']),
            'waitlist_expiry_hours'      => absint($settings['waitlist_expiry_hours'] ?? 24),
            'allow_cancellation'         => !empty($settings['allow_cancellation']),
            'cancellation_deadline_hours' => absint($settings['cancellation_deadline_hours'] ?? 24),
            'email_from_name'            => sanitize_text_field($settings['email_from_name'] ?? ''),
            'email_from_address'         => sanitize_email($settings['email_from_address'] ?? ''),
            'admin_notification_email'   => sanitize_email($settings['admin_notification_email'] ?? ''),
            'notify_admin_on_booking'    => !empty($settings['notify_admin_on_booking']),
        );
    }
    
    // =========================================
    // PUBLIC API METHODS
    // =========================================
    
    /**
     * Create a booking
     * 
     * @param array $data Booking data
     * @return int|WP_Error Booking ID or error
     */
    public function create_booking($data) {
        global $wpdb;
        
        // Validate required fields
        $required = array('event_id', 'customer_name', 'customer_email', 'booking_type');
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return new WP_Error('missing_field', sprintf(__('Field %s is required', 'ensemble'), $field));
            }
        }
        
        // Generate confirmation code
        $confirmation_code = $this->generate_confirmation_code();
        
        // Generate cancel token
        $cancel_token = wp_generate_password(32, false);
        $cancel_expires = date('Y-m-d H:i:s', strtotime('+7 days'));
        
        // Prepare data
        $insert_data = array(
            'event_id'             => absint($data['event_id']),
            'user_id'              => isset($data['user_id']) ? absint($data['user_id']) : get_current_user_id(),
            'booking_type'         => sanitize_key($data['booking_type']),
            'booking_subtype'      => isset($data['booking_subtype']) ? sanitize_key($data['booking_subtype']) : null,
            'floor_plan_id'        => isset($data['floor_plan_id']) ? absint($data['floor_plan_id']) : null,
            'element_id'           => isset($data['element_id']) ? sanitize_text_field($data['element_id']) : null,
            'element_type'         => isset($data['element_type']) ? sanitize_key($data['element_type']) : null,
            'element_label'        => isset($data['element_label']) ? sanitize_text_field($data['element_label']) : null,
            'category_id'          => isset($data['category_id']) ? absint($data['category_id']) : null,
            'category_name'        => isset($data['category_name']) ? sanitize_text_field($data['category_name']) : null,
            'price'                => isset($data['price']) ? floatval($data['price']) : 0.00,
            'currency'             => isset($data['currency']) ? sanitize_text_field($data['currency']) : 'EUR',
            'payment_status'       => isset($data['payment_status']) ? sanitize_key($data['payment_status']) : self::PAYMENT_NONE,
            'payment_method'       => isset($data['payment_method']) ? sanitize_text_field($data['payment_method']) : null,
            'status'               => isset($data['status']) ? sanitize_key($data['status']) : self::STATUS_PENDING,
            'quantity'             => isset($data['quantity']) ? absint($data['quantity']) : 1,
            'guests'               => isset($data['guests']) ? absint($data['guests']) : 1,
            'customer_name'        => sanitize_text_field($data['customer_name']),
            'customer_email'       => sanitize_email($data['customer_email']),
            'customer_phone'       => isset($data['customer_phone']) ? sanitize_text_field($data['customer_phone']) : '',
            'guest_names'          => isset($data['guest_names']) ? wp_json_encode($data['guest_names']) : null,
            'customer_notes'       => isset($data['customer_notes']) ? sanitize_textarea_field($data['customer_notes']) : null,
            'internal_notes'       => isset($data['internal_notes']) ? sanitize_textarea_field($data['internal_notes']) : null,
            'confirmation_code'    => $confirmation_code,
            'cancel_token'         => $cancel_token,
            'cancel_token_expires' => $cancel_expires,
            'created_at'           => current_time('mysql'),
            'updated_at'           => current_time('mysql'),
        );
        
        // Insert
        $result = $wpdb->insert($this->table_bookings, $insert_data);
        
        if ($result === false) {
            return new WP_Error('db_error', $wpdb->last_error);
        }
        
        $booking_id = $wpdb->insert_id;
        
        // Generate QR code
        $qr_code = $this->generate_qr_code($booking_id, $confirmation_code);
        if ($qr_code) {
            $wpdb->update(
                $this->table_bookings,
                array('qr_code' => $qr_code),
                array('id' => $booking_id)
            );
        }
        
        // Send confirmation email
        if ($this->get_setting('auto_send_email', true)) {
            $this->send_confirmation_email($booking_id);
        }
        
        // Fire action for other addons
        do_action('ensemble_booking_created', $booking_id, $insert_data);
        
        return $booking_id;
    }
    
    /**
     * Get a booking by ID
     * 
     * @param int $booking_id
     * @return object|null
     */
    public function get_booking($booking_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_bookings} WHERE id = %d",
            $booking_id
        ));
    }
    
    /**
     * Get booking by confirmation code
     * 
     * @param string $code
     * @return object|null
     */
    public function get_booking_by_code($code) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_bookings} WHERE confirmation_code = %s",
            $code
        ));
    }
    
    /**
     * Get bookings for an event
     * 
     * @param int   $event_id
     * @param array $args Query arguments
     * @return array
     */
    public function get_bookings($event_id = 0, $args = array()) {
        global $wpdb;
        
        $defaults = array(
            'status'       => '',
            'booking_type' => '',
            'payment_status' => '',
            'search'       => '',
            'orderby'      => 'created_at',
            'order'        => 'DESC',
            'limit'        => 0,
            'offset'       => 0,
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = array('1=1');
        $params = array();
        
        if ($event_id) {
            $where[] = 'event_id = %d';
            $params[] = $event_id;
        }
        
        if (!empty($args['status'])) {
            $where[] = 'status = %s';
            $params[] = $args['status'];
        }
        
        if (!empty($args['booking_type'])) {
            $where[] = 'booking_type = %s';
            $params[] = $args['booking_type'];
        }
        
        if (!empty($args['payment_status'])) {
            $where[] = 'payment_status = %s';
            $params[] = $args['payment_status'];
        }
        
        if (!empty($args['search'])) {
            $where[] = '(customer_name LIKE %s OR customer_email LIKE %s OR confirmation_code LIKE %s)';
            $search = '%' . $wpdb->esc_like($args['search']) . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }
        
        $where_clause = implode(' AND ', $where);
        
        // Sanitize orderby
        $allowed_orderby = array('id', 'created_at', 'updated_at', 'customer_name', 'status');
        $orderby = in_array($args['orderby'], $allowed_orderby) ? $args['orderby'] : 'created_at';
        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';
        
        $sql = "SELECT * FROM {$this->table_bookings} WHERE {$where_clause} ORDER BY {$orderby} {$order}";
        
        if ($args['limit'] > 0) {
            $sql .= $wpdb->prepare(' LIMIT %d OFFSET %d', $args['limit'], $args['offset']);
        }
        
        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * Update booking status
     * 
     * @param int    $booking_id
     * @param string $status
     * @return bool
     */
    public function update_status($booking_id, $status) {
        global $wpdb;
        
        $old_booking = $this->get_booking($booking_id);
        if (!$old_booking) {
            return false;
        }
        
        $result = $wpdb->update(
            $this->table_bookings,
            array(
                'status'     => $status,
                'updated_at' => current_time('mysql'),
            ),
            array('id' => $booking_id)
        );
        
        if ($result !== false) {
            do_action('ensemble_booking_status_changed', $booking_id, $status, $old_booking->status);
            
            // Send status change email if enabled
            if ($this->get_setting('send_status_emails', true)) {
                $this->send_status_email($booking_id, $status);
            }
        }
        
        return $result !== false;
    }
    
    /**
     * Check in a booking
     * 
     * @param int $booking_id
     * @return bool
     */
    public function checkin($booking_id) {
        global $wpdb;
        
        $result = $wpdb->update(
            $this->table_bookings,
            array(
                'status'        => self::STATUS_CHECKED_IN,
                'checked_in_at' => current_time('mysql'),
                'checked_in_by' => get_current_user_id(),
                'updated_at'    => current_time('mysql'),
            ),
            array('id' => $booking_id)
        );
        
        if ($result !== false) {
            do_action('ensemble_booking_checked_in', $booking_id);
        }
        
        return $result !== false;
    }
    
    /**
     * Cancel a booking
     * 
     * @param int    $booking_id
     * @param string $reason
     * @return bool
     */
    public function cancel($booking_id, $reason = '') {
        global $wpdb;
        
        $booking = $this->get_booking($booking_id);
        if (!$booking) {
            return false;
        }
        
        $internal_notes = $booking->internal_notes;
        if ($reason) {
            $internal_notes .= "\n[" . current_time('mysql') . "] " . __('Cancelled:', 'ensemble') . ' ' . $reason;
        }
        
        $result = $wpdb->update(
            $this->table_bookings,
            array(
                'status'         => self::STATUS_CANCELLED,
                'internal_notes' => $internal_notes,
                'updated_at'     => current_time('mysql'),
            ),
            array('id' => $booking_id)
        );
        
        if ($result !== false) {
            do_action('ensemble_booking_cancelled', $booking_id, $reason);
            
            // Check waitlist
            $this->process_waitlist($booking->event_id, $booking->category_id, $booking->element_id);
        }
        
        return $result !== false;
    }
    
    /**
     * Delete a booking
     * 
     * @param int $booking_id
     * @return bool
     */
    public function delete($booking_id) {
        global $wpdb;
        
        $booking = $this->get_booking($booking_id);
        if (!$booking) {
            return false;
        }
        
        $result = $wpdb->delete(
            $this->table_bookings,
            array('id' => $booking_id)
        );
        
        if ($result !== false) {
            do_action('ensemble_booking_deleted', $booking_id, $booking);
        }
        
        return $result !== false;
    }
    
    /**
     * Get count of bookings for an event
     * 
     * @param int    $event_id
     * @param string $type
     * @param string $status
     * @return int
     */
    public function get_booking_count($event_id, $type = '', $status = '') {
        global $wpdb;
        
        $where = array('event_id = %d');
        $params = array($event_id);
        
        if ($type) {
            $where[] = 'booking_type = %s';
            $params[] = $type;
        }
        
        if ($status) {
            $where[] = 'status = %s';
            $params[] = $status;
        } else {
            // Exclude cancelled by default
            $where[] = "status != 'cancelled'";
        }
        
        $where_clause = implode(' AND ', $where);
        
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(guests) FROM {$this->table_bookings} WHERE {$where_clause}",
            $params
        ));
    }
    
    /**
     * Check availability
     * 
     * @param int    $event_id
     * @param string $type
     * @param int    $quantity
     * @param int    $category_id
     * @param string $element_id
     * @return array
     */
    public function check_availability($event_id, $type = 'reservation', $quantity = 1, $category_id = null, $element_id = null) {
        $available = true;
        $remaining = null;
        $message = '';
        
        // Check element availability (Floor Plan)
        if ($element_id) {
            $element_booked = $this->is_element_booked($event_id, $element_id);
            if ($element_booked) {
                return array(
                    'available' => false,
                    'remaining' => 0,
                    'message'   => __('This seat/table is already booked', 'ensemble'),
                );
            }
        }
        
        // Check category capacity
        if ($category_id) {
            $category = $this->get_category($category_id);
            if ($category && $category->capacity) {
                $remaining = $category->capacity - $category->sold;
                if ($remaining < $quantity) {
                    $available = false;
                    $message = sprintf(__('Only %d tickets remaining', 'ensemble'), $remaining);
                }
            }
        }
        
        // Check event capacity for reservations
        if ($type === 'reservation') {
            $capacity = get_post_meta($event_id, '_booking_capacity', true);
            if ($capacity) {
                $booked = $this->get_booking_count($event_id, 'reservation');
                $remaining = (int) $capacity - $booked;
                if ($remaining < $quantity) {
                    $available = false;
                    $message = $remaining > 0 
                        ? sprintf(__('Only %d spots remaining', 'ensemble'), $remaining)
                        : __('Fully booked', 'ensemble');
                }
            }
        }
        
        return array(
            'available' => $available,
            'remaining' => $remaining,
            'message'   => $message,
        );
    }
    
    /**
     * Check if element is booked
     * 
     * @param int    $event_id
     * @param string $element_id
     * @return bool
     */
    public function is_element_booked($event_id, $element_id) {
        global $wpdb;
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_bookings} 
            WHERE event_id = %d AND element_id = %s AND status NOT IN ('cancelled')",
            $event_id,
            $element_id
        ));
        
        return $count > 0;
    }
    
    /**
     * Get booked elements for an event
     * 
     * @param int $event_id
     * @param int $floor_plan_id
     * @return array
     */
    public function get_booked_elements($event_id, $floor_plan_id = null) {
        global $wpdb;
        
        $where = "event_id = %d AND element_id IS NOT NULL AND status NOT IN ('cancelled')";
        $params = array($event_id);
        
        if ($floor_plan_id) {
            $where .= " AND floor_plan_id = %d";
            $params[] = $floor_plan_id;
        }
        
        return $wpdb->get_col($wpdb->prepare(
            "SELECT element_id FROM {$this->table_bookings} WHERE {$where}",
            $params
        ));
    }
    
    /**
     * Get booked elements with guest counts
     * 
     * Returns array with element_id as key and total guests as value
     * 
     * @param int $event_id
     * @param int|null $floor_plan_id
     * @return array ['element_id' => total_guests]
     */
    public function get_booked_elements_with_guests($event_id, $floor_plan_id = null) {
        global $wpdb;
        
        $where = "event_id = %d AND element_id IS NOT NULL AND status NOT IN ('cancelled')";
        $params = array($event_id);
        
        if ($floor_plan_id) {
            $where .= " AND floor_plan_id = %d";
            $params[] = $floor_plan_id;
        }
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT element_id, SUM(guests) as total_guests 
             FROM {$this->table_bookings} 
             WHERE {$where} 
             GROUP BY element_id",
            $params
        ));
        
        $booked = array();
        foreach ($results as $row) {
            $booked[$row->element_id] = intval($row->total_guests);
        }
        
        return $booked;
    }
    
    // =========================================
    // TICKET CATEGORY METHODS
    // =========================================
    
    /**
     * Get ticket category
     * 
     * @param int $category_id
     * @return object|null
     */
    public function get_category($category_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_categories} WHERE id = %d",
            $category_id
        ));
    }
    
    /**
     * Get categories for an event
     * 
     * @param int $event_id
     * @return array
     */
    public function get_categories($event_id) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_categories} 
            WHERE event_id = %d AND status = 'active' 
            ORDER BY sort_order ASC, id ASC",
            $event_id
        ));
    }
    
    // =========================================
    // FLOOR PLAN METHODS
    // =========================================
    
    /**
     * Get floor plan
     * 
     * @param int $floor_plan_id
     * @return object|null
     */
    public function get_floor_plan($floor_plan_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_floor_plans} WHERE id = %d",
            $floor_plan_id
        ));
    }
    
    /**
     * Get floor plans for a location
     * 
     * @param int $location_id
     * @return array
     */
    public function get_floor_plans($location_id = null) {
        global $wpdb;
        
        if ($location_id) {
            return $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$this->table_floor_plans} 
                WHERE location_id = %d AND status = 'active' 
                ORDER BY name ASC",
                $location_id
            ));
        }
        
        return $wpdb->get_results(
            "SELECT * FROM {$this->table_floor_plans} 
            WHERE status = 'active' 
            ORDER BY name ASC"
        );
    }
    
    // =========================================
    // WAITLIST METHODS
    // =========================================
    
    /**
     * Add to waitlist
     * 
     * @param array $data
     * @return int|WP_Error
     */
    public function add_to_waitlist($data) {
        global $wpdb;
        
        // Get next position
        $position = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT MAX(position) FROM {$this->table_waitlist} WHERE event_id = %d",
            $data['event_id']
        )) + 1;
        
        $insert_data = array(
            'event_id'       => absint($data['event_id']),
            'category_id'    => isset($data['category_id']) ? absint($data['category_id']) : null,
            'element_id'     => isset($data['element_id']) ? sanitize_text_field($data['element_id']) : null,
            'customer_name'  => sanitize_text_field($data['customer_name']),
            'customer_email' => sanitize_email($data['customer_email']),
            'customer_phone' => isset($data['customer_phone']) ? sanitize_text_field($data['customer_phone']) : '',
            'quantity'       => isset($data['quantity']) ? absint($data['quantity']) : 1,
            'status'         => 'waiting',
            'position'       => $position,
            'created_at'     => current_time('mysql'),
            'updated_at'     => current_time('mysql'),
        );
        
        $result = $wpdb->insert($this->table_waitlist, $insert_data);
        
        if ($result === false) {
            return new WP_Error('db_error', $wpdb->last_error);
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Process waitlist when spot becomes available
     * 
     * @param int    $event_id
     * @param int    $category_id
     * @param string $element_id
     */
    private function process_waitlist($event_id, $category_id = null, $element_id = null) {
        global $wpdb;
        
        $auto_process = $this->get_setting('waitlist_auto_process', true);
        if (!$auto_process) {
            return;
        }
        
        $where = array('event_id = %d', "status = 'waiting'");
        $params = array($event_id);
        
        if ($category_id) {
            $where[] = 'category_id = %d';
            $params[] = $category_id;
        }
        
        if ($element_id) {
            $where[] = 'element_id = %s';
            $params[] = $element_id;
        }
        
        $where_clause = implode(' AND ', $where);
        
        // Get first person on waitlist
        $waitlist_entry = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_waitlist} WHERE {$where_clause} ORDER BY position ASC LIMIT 1",
            $params
        ));
        
        if ($waitlist_entry) {
            // Set expiry time (e.g., 24 hours to complete booking)
            $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
            
            $wpdb->update(
                $this->table_waitlist,
                array(
                    'status'      => 'notified',
                    'notified_at' => current_time('mysql'),
                    'expires_at'  => $expires,
                    'updated_at'  => current_time('mysql'),
                ),
                array('id' => $waitlist_entry->id)
            );
            
            // Send notification email
            $this->send_waitlist_notification($waitlist_entry->id);
        }
    }
    
    // =========================================
    // HELPER METHODS
    // =========================================
    
    /**
     * Generate confirmation code
     * 
     * @return string
     */
    private function generate_confirmation_code() {
        $prefix = strtoupper(substr(md5(site_url()), 0, 2));
        $code = $prefix . strtoupper(wp_generate_password(6, false));
        return $code;
    }
    
    /**
     * Generate QR code
     * 
     * @param int    $booking_id
     * @param string $confirmation_code
     * @return string|false URL to QR code image
     */
    private function generate_qr_code($booking_id, $confirmation_code) {
        // Check-in URL
        $checkin_url = add_query_arg(array(
            'es_checkin' => 1,
            'code'       => $confirmation_code,
        ), home_url());
        
        // Use external QR API (can be replaced with local generation)
        $qr_url = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($checkin_url);
        
        return $qr_url;
    }
    
    /**
     * Send confirmation email
     * 
     * @param int $booking_id
     * @return bool
     */
    public function send_confirmation_email($booking_id) {
        $booking = $this->get_booking($booking_id);
        if (!$booking) {
            return false;
        }
        
        $event = get_post($booking->event_id);
        if (!$event) {
            return false;
        }
        
        // Get email template
        $template = $this->load_template('email-confirmation', array(
            'booking' => $booking,
            'event'   => $event,
            'addon'   => $this,
        ));
        
        if (empty($template)) {
            $this->log('Email template not found: email-confirmation', 'error');
            return false;
        }
        
        $subject = sprintf(
            __('Your booking for %s is confirmed', 'ensemble'),
            $event->post_title
        );
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        return wp_mail($booking->customer_email, $subject, $template, $headers);
    }
    
    /**
     * Send status change email
     * 
     * @param int    $booking_id
     * @param string $new_status
     * @return bool
     */
    private function send_status_email($booking_id, $new_status) {
        $booking = $this->get_booking($booking_id);
        if (!$booking) {
            return false;
        }
        
        $event = get_post($booking->event_id);
        if (!$event) {
            return false;
        }
        
        $template = $this->load_template('email-status-change', array(
            'booking'    => $booking,
            'event'      => $event,
            'new_status' => $new_status,
            'addon'      => $this,
        ));
        
        if (empty($template)) {
            return false;
        }
        
        $subject = sprintf(
            __('Booking update for %s', 'ensemble'),
            $event->post_title
        );
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        return wp_mail($booking->customer_email, $subject, $template, $headers);
    }
    
    /**
     * Send waitlist notification
     * 
     * @param int $waitlist_id
     * @return bool
     */
    private function send_waitlist_notification($waitlist_id) {
        global $wpdb;
        
        $entry = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_waitlist} WHERE id = %d",
            $waitlist_id
        ));
        
        if (!$entry) {
            return false;
        }
        
        $event = get_post($entry->event_id);
        if (!$event) {
            return false;
        }
        
        $template = $this->load_template('email-waitlist-available', array(
            'entry' => $entry,
            'event' => $event,
            'addon' => $this,
        ));
        
        if (empty($template)) {
            return false;
        }
        
        $subject = sprintf(
            __('A spot is available for %s!', 'ensemble'),
            $event->post_title
        );
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        return wp_mail($entry->customer_email, $subject, $template, $headers);
    }
    
    /**
     * Get status label
     * 
     * @param string $status
     * @return string
     */
    public function get_status_label($status) {
        $labels = array(
            'pending'    => __('Pending', 'ensemble'),
            'confirmed'  => __('Confirmed', 'ensemble'),
            'cancelled'  => __('Cancelled', 'ensemble'),
            'checked_in' => __('Checked In', 'ensemble'),
            'no_show'    => __('No Show', 'ensemble'),
        );
        
        return isset($labels[$status]) ? $labels[$status] : $status;
    }
    
    /**
     * Get booking type label
     * 
     * @param string $type
     * @return string
     */
    public function get_type_label($type) {
        $labels = array(
            'reservation' => __('Reservation', 'ensemble'),
            'ticket'      => __('Ticket', 'ensemble'),
        );
        
        return isset($labels[$type]) ? $labels[$type] : $type;
    }
    
    /**
     * Get payment status label
     * 
     * @param string $status
     * @return string
     */
    public function get_payment_label($status) {
        $labels = array(
            'none'     => __('Free', 'ensemble'),
            'pending'  => __('Pending', 'ensemble'),
            'paid'     => __('Paid', 'ensemble'),
            'refunded' => __('Refunded', 'ensemble'),
            'failed'   => __('Failed', 'ensemble'),
        );
        
        return isset($labels[$status]) ? $labels[$status] : $status;
    }
    
    // =========================================
    // AJAX HANDLERS
    // =========================================
    
    /**
     * AJAX: Get bookings
     */
    public function ajax_get_bookings() {
        check_ajax_referer('ensemble_booking_engine', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'ensemble')));
            return;
        }
        
        $event_id = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;
        $args = array(
            'status'       => isset($_POST['status']) ? sanitize_key($_POST['status']) : '',
            'booking_type' => isset($_POST['booking_type']) ? sanitize_key($_POST['booking_type']) : '',
            'search'       => isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '',
            'limit'        => isset($_POST['limit']) ? absint($_POST['limit']) : 50,
            'offset'       => isset($_POST['offset']) ? absint($_POST['offset']) : 0,
        );
        
        $bookings = $this->get_bookings($event_id, $args);
        
        // Add event titles
        $events_cache = array();
        foreach ($bookings as &$booking) {
            if (!isset($events_cache[$booking->event_id])) {
                $event = get_post($booking->event_id);
                $events_cache[$booking->event_id] = $event ? $event->post_title : '';
            }
            $booking->event_title = $events_cache[$booking->event_id];
            $booking->status_label = $this->get_status_label($booking->status);
            $booking->type_label = $this->get_type_label($booking->booking_type);
            $booking->payment_label = $this->get_payment_label($booking->payment_status);
        }
        
        wp_send_json_success(array('bookings' => $bookings));
    }
    
    /**
     * AJAX: Get single booking
     */
    public function ajax_get_booking() {
        check_ajax_referer('ensemble_booking_engine', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'ensemble')));
            return;
        }
        
        $booking_id = isset($_POST['booking_id']) ? absint($_POST['booking_id']) : 0;
        
        if (!$booking_id) {
            wp_send_json_error(array('message' => __('Invalid booking ID', 'ensemble')));
            return;
        }
        
        $booking = $this->get_booking($booking_id);
        
        if (!$booking) {
            wp_send_json_error(array('message' => __('Booking not found', 'ensemble')));
            return;
        }
        
        // Add event data
        $event = get_post($booking->event_id);
        $booking->event_title = $event ? $event->post_title : '';
        $booking->event_date = get_post_meta($booking->event_id, 'event_date', true);
        $booking->status_label = $this->get_status_label($booking->status);
        $booking->type_label = $this->get_type_label($booking->booking_type);
        $booking->payment_label = $this->get_payment_label($booking->payment_status);
        
        wp_send_json_success(array('booking' => $booking));
    }
    
    /**
     * AJAX: Create booking (admin)
     */
    public function ajax_create_booking() {
        check_ajax_referer('ensemble_booking_engine', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'ensemble')));
            return;
        }
        
        $data = array(
            'event_id'        => isset($_POST['event_id']) ? absint($_POST['event_id']) : 0,
            'booking_type'    => isset($_POST['booking_type']) ? sanitize_key($_POST['booking_type']) : 'reservation',
            'booking_subtype' => isset($_POST['booking_subtype']) ? sanitize_key($_POST['booking_subtype']) : '',
            'customer_name'   => isset($_POST['customer_name']) ? sanitize_text_field($_POST['customer_name']) : '',
            'customer_email'  => isset($_POST['customer_email']) ? sanitize_email($_POST['customer_email']) : '',
            'customer_phone'  => isset($_POST['customer_phone']) ? sanitize_text_field($_POST['customer_phone']) : '',
            'guests'          => isset($_POST['guests']) ? absint($_POST['guests']) : 1,
            'status'          => isset($_POST['status']) ? sanitize_key($_POST['status']) : 'confirmed',
            'internal_notes'  => isset($_POST['internal_notes']) ? sanitize_textarea_field($_POST['internal_notes']) : '',
        );
        
        $result = $this->create_booking($data);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
            return;
        }
        
        wp_send_json_success(array(
            'message'    => __('Booking created', 'ensemble'),
            'booking_id' => $result,
        ));
    }
    
    /**
     * AJAX: Update booking status
     */
    public function ajax_update_booking_status() {
        check_ajax_referer('ensemble_booking_engine', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'ensemble')));
            return;
        }
        
        $booking_id = isset($_POST['booking_id']) ? absint($_POST['booking_id']) : 0;
        $status = isset($_POST['status']) ? sanitize_key($_POST['status']) : '';
        
        if (!$booking_id || !$status) {
            wp_send_json_error(array('message' => __('Invalid data', 'ensemble')));
            return;
        }
        
        $result = $this->update_status($booking_id, $status);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Status updated', 'ensemble'),
                'status'  => $status,
                'label'   => $this->get_status_label($status),
            ));
        } else {
            wp_send_json_error(array('message' => __('Update failed', 'ensemble')));
        }
    }
    
    /**
     * AJAX: Check in booking
     */
    public function ajax_checkin_booking() {
        check_ajax_referer('ensemble_booking_engine', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'ensemble')));
            return;
        }
        
        $booking_id = isset($_POST['booking_id']) ? absint($_POST['booking_id']) : 0;
        
        if (!$booking_id) {
            wp_send_json_error(array('message' => __('Invalid booking ID', 'ensemble')));
            return;
        }
        
        $result = $this->checkin($booking_id);
        
        if ($result) {
            wp_send_json_success(array(
                'message'       => __('Checked in!', 'ensemble'),
                'checked_in_at' => current_time('mysql'),
            ));
        } else {
            wp_send_json_error(array('message' => __('Check-in failed', 'ensemble')));
        }
    }
    
    /**
     * AJAX: Delete booking
     */
    public function ajax_delete_booking() {
        check_ajax_referer('ensemble_booking_engine', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'ensemble')));
            return;
        }
        
        $booking_id = isset($_POST['booking_id']) ? absint($_POST['booking_id']) : 0;
        
        if (!$booking_id) {
            wp_send_json_error(array('message' => __('Invalid booking ID', 'ensemble')));
            return;
        }
        
        $result = $this->delete($booking_id);
        
        if ($result) {
            wp_send_json_success(array('message' => __('Booking deleted', 'ensemble')));
        } else {
            wp_send_json_error(array('message' => __('Delete failed', 'ensemble')));
        }
    }
    
    /**
     * AJAX: Submit booking (public)
     */
    public function ajax_submit_booking() {
        check_ajax_referer('ensemble_booking_public', 'nonce');
        
        // Get booking_subtype from element_type if not directly provided
        $booking_subtype = '';
        if (isset($_POST['booking_subtype']) && !empty($_POST['booking_subtype'])) {
            $booking_subtype = sanitize_key($_POST['booking_subtype']);
        } elseif (isset($_POST['element_type']) && !empty($_POST['element_type'])) {
            // Map element_type to booking_subtype
            $element_type = sanitize_key($_POST['element_type']);
            $type_map = array(
                'table' => 'table',
                'seat' => 'table',
                'booth' => 'vip',
                'vip' => 'vip',
                'zone' => 'guestlist',
                'standing' => 'guestlist',
            );
            $booking_subtype = isset($type_map[$element_type]) ? $type_map[$element_type] : 'table';
        }
        
        $data = array(
            'event_id'        => isset($_POST['event_id']) ? absint($_POST['event_id']) : 0,
            'booking_type'    => isset($_POST['booking_type']) ? sanitize_key($_POST['booking_type']) : 'reservation',
            'booking_subtype' => $booking_subtype,
            'customer_name'   => isset($_POST['customer_name']) ? sanitize_text_field($_POST['customer_name']) : '',
            'customer_email'  => isset($_POST['customer_email']) ? sanitize_email($_POST['customer_email']) : '',
            'customer_phone'  => isset($_POST['customer_phone']) ? sanitize_text_field($_POST['customer_phone']) : '',
            'guests'          => isset($_POST['guests']) ? absint($_POST['guests']) : 1,
            'customer_notes'  => isset($_POST['customer_notes']) ? sanitize_textarea_field($_POST['customer_notes']) : '',
            'floor_plan_id'   => isset($_POST['floor_plan_id']) ? absint($_POST['floor_plan_id']) : null,
            'element_id'      => isset($_POST['element_id']) ? sanitize_text_field($_POST['element_id']) : null,
            'element_label'   => isset($_POST['element_label']) ? sanitize_text_field($_POST['element_label']) : null,
            'category_id'     => isset($_POST['category_id']) ? absint($_POST['category_id']) : null,
        );
        
        // Check availability first
        $availability = $this->check_availability(
            $data['event_id'],
            $data['booking_type'],
            $data['guests'],
            $data['category_id'],
            $data['element_id']
        );
        
        if (!$availability['available']) {
            wp_send_json_error(array('message' => $availability['message']));
            return;
        }
        
        // Set status based on settings
        $auto_confirm = $this->get_setting('auto_confirm', true);
        $data['status'] = $auto_confirm ? self::STATUS_CONFIRMED : self::STATUS_PENDING;
        
        $result = $this->create_booking($data);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
            return;
        }
        
        $booking = $this->get_booking($result);
        
        wp_send_json_success(array(
            'message'           => __('Booking successful!', 'ensemble'),
            'booking_id'        => $result,
            'confirmation_code' => $booking->confirmation_code,
        ));
    }
    
    /**
     * AJAX: Check availability (public)
     */
    public function ajax_check_availability() {
        check_ajax_referer('ensemble_booking_public', 'nonce');
        
        $event_id = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;
        $type = isset($_POST['booking_type']) ? sanitize_key($_POST['booking_type']) : 'reservation';
        $quantity = isset($_POST['quantity']) ? absint($_POST['quantity']) : 1;
        $category_id = isset($_POST['category_id']) ? absint($_POST['category_id']) : null;
        $element_id = isset($_POST['element_id']) ? sanitize_text_field($_POST['element_id']) : null;
        
        $result = $this->check_availability($event_id, $type, $quantity, $category_id, $element_id);
        
        wp_send_json_success($result);
    }
    
    /**
     * AJAX: Cancel booking (public)
     */
    public function ajax_cancel_booking() {
        check_ajax_referer('ensemble_booking_public', 'nonce');
        
        $confirmation_code = isset($_POST['confirmation_code']) ? sanitize_text_field($_POST['confirmation_code']) : '';
        $cancel_token = isset($_POST['cancel_token']) ? sanitize_text_field($_POST['cancel_token']) : '';
        
        if (!$confirmation_code || !$cancel_token) {
            wp_send_json_error(array('message' => __('Invalid cancellation request', 'ensemble')));
            return;
        }
        
        $booking = $this->get_booking_by_code($confirmation_code);
        
        if (!$booking) {
            wp_send_json_error(array('message' => __('Booking not found', 'ensemble')));
            return;
        }
        
        // Verify cancel token
        if ($booking->cancel_token !== $cancel_token) {
            wp_send_json_error(array('message' => __('Invalid cancellation link', 'ensemble')));
            return;
        }
        
        // Check if token expired
        if ($booking->cancel_token_expires && strtotime($booking->cancel_token_expires) < time()) {
            wp_send_json_error(array('message' => __('Cancellation link expired', 'ensemble')));
            return;
        }
        
        // Check if already cancelled
        if ($booking->status === 'cancelled') {
            wp_send_json_error(array('message' => __('Booking already cancelled', 'ensemble')));
            return;
        }
        
        $result = $this->cancel($booking->id, __('Cancelled by customer', 'ensemble'));
        
        if ($result) {
            wp_send_json_success(array('message' => __('Booking cancelled successfully', 'ensemble')));
        } else {
            wp_send_json_error(array('message' => __('Cancellation failed', 'ensemble')));
        }
    }
    
    /**
     * AJAX: Join waitlist (public)
     */
    public function ajax_join_waitlist() {
        check_ajax_referer('ensemble_booking_public', 'nonce');
        
        $data = array(
            'event_id'       => isset($_POST['event_id']) ? absint($_POST['event_id']) : 0,
            'customer_name'  => isset($_POST['customer_name']) ? sanitize_text_field($_POST['customer_name']) : '',
            'customer_email' => isset($_POST['customer_email']) ? sanitize_email($_POST['customer_email']) : '',
            'customer_phone' => isset($_POST['customer_phone']) ? sanitize_text_field($_POST['customer_phone']) : '',
            'quantity'       => isset($_POST['guests']) ? absint($_POST['guests']) : 1,
            'category_id'    => isset($_POST['category_id']) ? absint($_POST['category_id']) : null,
            'element_id'     => isset($_POST['element_id']) ? sanitize_text_field($_POST['element_id']) : null,
        );
        
        // Validate required fields
        if (empty($data['event_id']) || empty($data['customer_name']) || empty($data['customer_email'])) {
            wp_send_json_error(array('message' => __('Please fill in all required fields', 'ensemble')));
            return;
        }
        
        // Validate email
        if (!is_email($data['customer_email'])) {
            wp_send_json_error(array('message' => __('Please enter a valid email address', 'ensemble')));
            return;
        }
        
        // Check if already on waitlist
        global $wpdb;
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->get_table_waitlist()} 
            WHERE event_id = %d AND customer_email = %s AND status = 'waiting'",
            $data['event_id'],
            $data['customer_email']
        ));
        
        if ($existing) {
            wp_send_json_error(array('message' => __('You are already on the waitlist for this event', 'ensemble')));
            return;
        }
        
        $result = $this->add_to_waitlist($data);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
            return;
        }
        
        wp_send_json_success(array(
            'message' => __('You have been added to the waitlist! We will notify you when a spot becomes available.', 'ensemble'),
        ));
    }
    
    /**
     * AJAX: Update booking (admin)
     */
    public function ajax_update_booking() {
        check_ajax_referer('ensemble_booking_engine', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'ensemble')));
            return;
        }
        
        $booking_id = isset($_POST['booking_id']) ? absint($_POST['booking_id']) : 0;
        
        if (!$booking_id) {
            wp_send_json_error(array('message' => __('Invalid booking ID', 'ensemble')));
            return;
        }
        
        $booking = $this->get_booking($booking_id);
        if (!$booking) {
            wp_send_json_error(array('message' => __('Booking not found', 'ensemble')));
            return;
        }
        
        global $wpdb;
        
        $update_data = array(
            'updated_at' => current_time('mysql'),
        );
        
        // Update allowed fields
        $allowed_fields = array(
            'customer_name', 'customer_email', 'customer_phone',
            'guests', 'status', 'booking_subtype', 'element_label',
            'internal_notes', 'customer_notes'
        );
        
        foreach ($allowed_fields as $field) {
            if (isset($_POST[$field])) {
                switch ($field) {
                    case 'customer_email':
                        $update_data[$field] = sanitize_email($_POST[$field]);
                        break;
                    case 'guests':
                        $update_data[$field] = absint($_POST[$field]);
                        break;
                    case 'internal_notes':
                    case 'customer_notes':
                        $update_data[$field] = sanitize_textarea_field($_POST[$field]);
                        break;
                    default:
                        $update_data[$field] = sanitize_text_field($_POST[$field]);
                }
            }
        }
        
        $result = $wpdb->update(
            $this->table_bookings,
            $update_data,
            array('id' => $booking_id)
        );
        
        if ($result !== false) {
            wp_send_json_success(array(
                'message' => __('Booking updated', 'ensemble'),
            ));
        } else {
            wp_send_json_error(array('message' => __('Update failed', 'ensemble')));
        }
    }
    
    /**
     * AJAX: Export bookings
     */
    public function ajax_export_bookings() {
        check_ajax_referer('ensemble_booking_engine', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'ensemble')));
            return;
        }
        
        $format = isset($_POST['format']) ? sanitize_key($_POST['format']) : 'csv';
        $event_id = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;
        $status = isset($_POST['status']) ? sanitize_key($_POST['status']) : '';
        
        $args = array(
            'status' => $status,
            'limit'  => 0, // All
        );
        
        $bookings = $this->get_bookings($event_id, $args);
        
        if (empty($bookings)) {
            wp_send_json_error(array('message' => __('No bookings to export', 'ensemble')));
            return;
        }
        
        if ($format === 'csv') {
            $csv_data = $this->generate_csv($bookings, $event_id);
            
            wp_send_json_success(array(
                'format'   => 'csv',
                'filename' => 'bookings-' . date('Y-m-d') . '.csv',
                'data'     => $csv_data,
            ));
        } else {
            // PDF export - basic implementation
            wp_send_json_error(array('message' => __('PDF export coming soon', 'ensemble')));
        }
    }
    
    /**
     * Generate CSV data from bookings
     * 
     * @param array $bookings
     * @param int   $event_id
     * @return string
     */
    private function generate_csv($bookings, $event_id = 0) {
        $output = fopen('php://temp', 'r+');
        
        // Header row
        fputcsv($output, array(
            __('ID', 'ensemble'),
            __('Confirmation Code', 'ensemble'),
            __('Event', 'ensemble'),
            __('Type', 'ensemble'),
            __('Name', 'ensemble'),
            __('Email', 'ensemble'),
            __('Phone', 'ensemble'),
            __('Guests', 'ensemble'),
            __('Status', 'ensemble'),
            __('Created', 'ensemble'),
            __('Notes', 'ensemble'),
        ));
        
        // Cache event titles
        $event_titles = array();
        
        foreach ($bookings as $booking) {
            if (!isset($event_titles[$booking->event_id])) {
                $event = get_post($booking->event_id);
                $event_titles[$booking->event_id] = $event ? $event->post_title : '';
            }
            
            fputcsv($output, array(
                $booking->id,
                $booking->confirmation_code,
                $event_titles[$booking->event_id],
                $this->get_type_label($booking->booking_type),
                $booking->customer_name,
                $booking->customer_email,
                $booking->customer_phone,
                $booking->guests,
                $this->get_status_label($booking->status),
                $booking->created_at,
                $booking->customer_notes,
            ));
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
    
    /**
     * AJAX: Resend booking confirmation email
     */
    public function ajax_resend_email() {
        check_ajax_referer('ensemble_booking_engine', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'ensemble')));
            return;
        }
        
        $booking_id = isset($_POST['booking_id']) ? absint($_POST['booking_id']) : 0;
        
        if (!$booking_id) {
            wp_send_json_error(array('message' => __('Invalid booking ID', 'ensemble')));
            return;
        }
        
        $result = $this->send_confirmation_email($booking_id);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Confirmation email sent', 'ensemble'),
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to send email', 'ensemble')));
        }
    }
    
    /**
     * Handle QR code check-in
     */
    public function handle_qr_checkin() {
        if (!isset($_GET['es_checkin']) || !isset($_GET['code'])) {
            return;
        }
        
        $code = sanitize_text_field($_GET['code']);
        $booking = $this->get_booking_by_code($code);
        
        if (!$booking) {
            wp_die(__('Booking not found', 'ensemble'));
        }
        
        // Check if user can check in
        if (!current_user_can('edit_posts')) {
            // Redirect to login
            wp_redirect(wp_login_url(add_query_arg(array(
                'es_checkin' => 1,
                'code'       => $code,
            ), home_url())));
            exit;
        }
        
        // Perform check-in
        if ($booking->status !== 'checked_in') {
            $this->checkin($booking->id);
            $message = __('Successfully checked in!', 'ensemble');
        } else {
            $message = __('Already checked in', 'ensemble');
        }
        
        // Load check-in confirmation template
        echo $this->load_template('checkin-result', array(
            'booking' => $booking,
            'message' => $message,
            'event'   => get_post($booking->event_id),
        ));
        exit;
    }
    
    /**
     * Handle cancellation page
     */
    public function handle_cancellation_page() {
        if (!isset($_GET['es_cancel']) || !isset($_GET['code']) || !isset($_GET['token'])) {
            return;
        }
        
        $code = sanitize_text_field($_GET['code']);
        $token = sanitize_text_field($_GET['token']);
        
        $booking = $this->get_booking_by_code($code);
        
        echo $this->load_template('cancel-page', array(
            'booking' => $booking,
            'token'   => $token,
            'event'   => $booking ? get_post($booking->event_id) : null,
            'addon'   => $this,
        ));
        exit;
    }
    
    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        register_rest_route('ensemble/v1', '/bookings', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'rest_get_bookings'),
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            },
        ));
        
        register_rest_route('ensemble/v1', '/bookings/(?P<id>\d+)', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'rest_get_booking'),
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            },
        ));
        
        register_rest_route('ensemble/v1', '/bookings/(?P<id>\d+)/checkin', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'rest_checkin_booking'),
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            },
        ));
        
        register_rest_route('ensemble/v1', '/events/(?P<id>\d+)/availability', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'rest_get_availability'),
            'permission_callback' => '__return_true',
        ));
    }
    
    /**
     * REST: Get bookings
     */
    public function rest_get_bookings($request) {
        $event_id = $request->get_param('event_id');
        $bookings = $this->get_bookings($event_id);
        
        return rest_ensure_response($bookings);
    }
    
    /**
     * REST: Get single booking
     */
    public function rest_get_booking($request) {
        $booking = $this->get_booking($request['id']);
        
        if (!$booking) {
            return new WP_Error('not_found', __('Booking not found', 'ensemble'), array('status' => 404));
        }
        
        return rest_ensure_response($booking);
    }
    
    /**
     * REST: Check in booking
     */
    public function rest_checkin_booking($request) {
        $result = $this->checkin($request['id']);
        
        if ($result) {
            return rest_ensure_response(array('success' => true));
        }
        
        return new WP_Error('checkin_failed', __('Check-in failed', 'ensemble'), array('status' => 500));
    }
    
    /**
     * REST: Get availability
     */
    public function rest_get_availability($request) {
        $event_id = $request['id'];
        $type = $request->get_param('type') ?: 'reservation';
        
        $result = $this->check_availability($event_id, $type);
        
        return rest_ensure_response($result);
    }
    
    // =========================================
    // FLOOR PLAN INTEGRATION
    // =========================================
    
    /**
     * AJAX: Get floor plan info for wizard preview
     */
    public function ajax_get_floor_plan_info() {
        // Accept both wizard nonce and general nonce
        $nonce_valid = wp_verify_nonce($_POST['nonce'] ?? '', 'ensemble_nonce') 
                    || wp_verify_nonce($_POST['nonce'] ?? '', 'ensemble_wizard');
        
        if (!$nonce_valid) {
            wp_send_json_error(array('message' => __('Invalid security token', 'ensemble')));
            return;
        }
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'ensemble')));
            return;
        }
        
        $floor_plan_id = isset($_POST['floor_plan_id']) ? absint($_POST['floor_plan_id']) : 0;
        
        if (!$floor_plan_id) {
            wp_send_json_error(array('message' => __('Invalid floor plan ID', 'ensemble')));
            return;
        }
        
        // Get floor plan data
        $floor_plan = get_post($floor_plan_id);
        if (!$floor_plan) {
            wp_send_json_error(array('message' => __('Floor plan not found', 'ensemble')));
            return;
        }
        
        $floor_plan_data = get_post_meta($floor_plan_id, '_floor_plan_data', true);
        
        // Calculate stats
        $bookable_count = 0;
        $total_capacity = 0;
        
        if (!empty($floor_plan_data['elements'])) {
            foreach ($floor_plan_data['elements'] as $element) {
                if (!empty($element['bookable'])) {
                    $bookable_count++;
                    $total_capacity += intval($element['seats'] ?? $element['capacity'] ?? 0);
                }
            }
        }
        
        wp_send_json_success(array(
            'id'              => $floor_plan_id,
            'title'           => $floor_plan->post_title,
            'bookable_count'  => $bookable_count,
            'total_capacity'  => $total_capacity,
            'sections'        => $floor_plan_data['sections'] ?? array(),
        ));
    }
    
    /**
     * Get element booking status
     * 
     * @param int    $event_id
     * @param string $element_id
     * @param int    $element_capacity
     * @return array
     */
    public function get_element_status($event_id, $element_id, $element_capacity = 0) {
        global $wpdb;
        
        $booked = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(guests), 0) FROM {$this->table_bookings} 
            WHERE event_id = %d AND element_id = %s AND status NOT IN ('cancelled')",
            $event_id,
            $element_id
        ));
        
        $booked = intval($booked);
        $available = $element_capacity > 0 ? max(0, $element_capacity - $booked) : 0;
        
        $status = 'available';
        if ($element_capacity > 0) {
            if ($booked >= $element_capacity) {
                $status = 'sold_out';
            } elseif ($booked > 0) {
                $status = 'partial';
            }
        }
        
        return array(
            'capacity'  => $element_capacity,
            'booked'    => $booked,
            'available' => $available,
            'status'    => $status,
        );
    }
    
    /**
     * Get all element statuses for a floor plan and event
     * 
     * @param int $event_id
     * @param int $floor_plan_id
     * @return array
     */
    public function get_floor_plan_element_statuses($event_id, $floor_plan_id) {
        $statuses = array();
        
        // Get floor plan data
        $floor_plan_data = get_post_meta($floor_plan_id, '_floor_plan_data', true);
        if (empty($floor_plan_data['elements'])) {
            return $statuses;
        }
        
        foreach ($floor_plan_data['elements'] as $element) {
            if (empty($element['bookable'])) {
                continue;
            }
            
            $capacity = intval($element['seats'] ?? $element['capacity'] ?? 0);
            $statuses[$element['id']] = $this->get_element_status($event_id, $element['id'], $capacity);
        }
        
        return $statuses;
    }
    
    // =========================================
    // TABLE GETTERS
    // =========================================
    
    public function get_table_bookings() { return $this->table_bookings; }
    public function get_table_categories() { return $this->table_categories; }
    public function get_table_floor_plans() { return $this->table_floor_plans; }
    public function get_table_coupons() { return $this->table_coupons; }
    public function get_table_passes() { return $this->table_passes; }
    public function get_table_waitlist() { return $this->table_waitlist; }
}
