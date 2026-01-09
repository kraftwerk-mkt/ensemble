<?php
/**
 * Ensemble Reservations Pro Add-on
 * 
 * Complete reservation and guest list management for events
 * - Guest lists with capacity limits
 * - Table reservations with seating plans
 * - VIP lists with priority handling
 * - Check-in system with QR codes
 * - Email notifications
 * - Export to CSV/PDF
 *
 * @package Ensemble
 * @subpackage Addons
 * @since 2.8.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class ES_Reservations_Addon extends ES_Addon_Base {
    
    /**
     * Add-on configuration
     */
    protected $slug = 'reservations';
    protected $name = 'Reservations Pro';
    protected $version = '1.0.0';
    
    /**
     * Database table name
     */
    private $table_name;
    
    /**
     * Reservation statuses
     */
    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_CHECKED_IN = 'checked_in';
    const STATUS_NO_SHOW = 'no_show';
    
    /**
     * Reservation types
     */
    const TYPE_GUESTLIST = 'guestlist';
    const TYPE_TABLE = 'table';
    const TYPE_VIP = 'vip';
    
    /**
     * Initialize add-on
     */
    protected function init() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'ensemble_reservations';
        
        // Create database table on first run
        $this->maybe_create_table();
        
        $this->log('Reservations Pro add-on initialized');
    }
    
    /**
     * Register hooks
     */
    protected function register_hooks() {
        // Frontend hooks
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        
        // Template hook - Reservation form after tickets
        // Only ONE registration needed - the hook system handles both variants
        add_action('ensemble_after_tickets', array($this, 'render_reservation_section'), 10, 1);
        
        // Admin hooks
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('admin_menu', array($this, 'add_admin_menu'), 30);
        
        // AJAX handlers - Public
        add_action('wp_ajax_es_submit_reservation', array($this, 'ajax_submit_reservation'));
        add_action('wp_ajax_nopriv_es_submit_reservation', array($this, 'ajax_submit_reservation'));
        add_action('wp_ajax_es_check_availability', array($this, 'ajax_check_availability'));
        add_action('wp_ajax_nopriv_es_check_availability', array($this, 'ajax_check_availability'));
        
        // AJAX handlers - Admin
        add_action('wp_ajax_es_update_reservation_status', array($this, 'ajax_update_status'));
        add_action('wp_ajax_es_delete_reservation', array($this, 'ajax_delete_reservation'));
        add_action('wp_ajax_es_checkin_reservation', array($this, 'ajax_checkin'));
        add_action('wp_ajax_es_export_reservations', array($this, 'ajax_export'));
        add_action('wp_ajax_es_get_event_reservations', array($this, 'ajax_get_reservations'));
        add_action('wp_ajax_es_resend_confirmation', array($this, 'ajax_resend_confirmation'));
        
        // QR Code Check-in Handler (public URL)
        add_action('template_redirect', array($this, 'handle_qr_checkin'));
        
        // Shortcodes
        add_shortcode('ensemble_reservation_form', array($this, 'shortcode_reservation_form'));
        add_shortcode('ensemble_guestlist', array($this, 'shortcode_guestlist'));
        
        // REST API
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        
        // Meta box for events
        add_action('add_meta_boxes', array($this, 'add_event_meta_box'));
        add_action('save_post', array($this, 'save_event_meta'));
        
        // Cron for reminders
        add_action('ensemble_send_reservation_reminders', array($this, 'send_reminders'));
        if (!wp_next_scheduled('ensemble_send_reservation_reminders')) {
            wp_schedule_event(time(), 'daily', 'ensemble_send_reservation_reminders');
        }
    }
    
    /**
     * Create database table
     */
    private function maybe_create_table() {
        global $wpdb;
        
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'") === $this->table_name;
        
        if ($table_exists) {
            return;
        }
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$this->table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            event_id bigint(20) unsigned NOT NULL,
            type varchar(20) NOT NULL DEFAULT 'guestlist',
            status varchar(20) NOT NULL DEFAULT 'pending',
            name varchar(255) NOT NULL,
            email varchar(255) NOT NULL,
            phone varchar(50) DEFAULT '',
            guests int(11) NOT NULL DEFAULT 1,
            table_number varchar(20) DEFAULT '',
            notes text DEFAULT '',
            internal_notes text DEFAULT '',
            confirmation_code varchar(32) NOT NULL,
            qr_code varchar(255) DEFAULT '',
            checked_in_at datetime DEFAULT NULL,
            checked_in_by bigint(20) unsigned DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY event_id (event_id),
            KEY status (status),
            KEY type (type),
            KEY email (email),
            KEY confirmation_code (confirmation_code)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        $this->log('Reservations table created');
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        $post_type = function_exists('ensemble_get_post_type') ? ensemble_get_post_type() : 'ensemble_event';
        
        if (!is_singular($post_type) && !$this->has_reservation_shortcode()) {
            return;
        }
        
        wp_enqueue_style(
            'ensemble-reservations',
            $this->get_addon_url() . 'assets/reservations.css',
            array(),
            $this->version
        );
        
        wp_enqueue_script(
            'ensemble-reservations',
            $this->get_addon_url() . 'assets/reservations.js',
            array('jquery'),
            $this->version,
            true
        );
        
        wp_localize_script('ensemble-reservations', 'ensembleReservations', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('ensemble_reservations'),
            'strings' => array(
                'submitting'      => __('Submitting...', 'ensemble'),
                'success'         => __('Reservation successful!', 'ensemble'),
                'error'           => __('An error occurred.', 'ensemble'),
                'required'        => __('Please fill in all required fields.', 'ensemble'),
                'invalidEmail'    => __('Please enter a valid email address.', 'ensemble'),
                'capacityReached' => __('Unfortunately, maximum capacity has been reached.', 'ensemble'),
                'confirmCancel'   => __('Really cancel reservation?', 'ensemble'),
            ),
        ));
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only on our admin pages or event edit
        if (strpos($hook, 'ensemble-reservations') === false && 
            strpos($hook, 'ensemble_page_ensemble') === false &&
            $hook !== 'post.php' && $hook !== 'post-new.php') {
            return;
        }
        
        // Make sure admin-unified.css is loaded first
        wp_enqueue_style(
            'ensemble-reservations-admin',
            $this->get_addon_url() . 'assets/reservations-admin.css',
            array('ensemble-admin-unified'), // Dependency on unified CSS
            $this->version
        );
        
        wp_enqueue_script(
            'ensemble-reservations-admin',
            $this->get_addon_url() . 'assets/reservations-admin.js',
            array('jquery'),
            $this->version,
            true
        );
        
        wp_localize_script('ensemble-reservations-admin', 'ensembleReservationsAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('ensemble_reservations_admin'),
            'strings' => array(
                'confirmDelete'   => __('Really delete reservation?', 'ensemble'),
                'confirmCheckin'  => __('Confirm check-in?', 'ensemble'),
                'statusUpdated'   => __('Status aktualisiert', 'ensemble'),
                'deleted'         => __('Deleted', 'ensemble'),
                'checkedIn'       => __('Eingecheckt!', 'ensemble'),
                'exportStarted'   => __('Export wird erstellt...', 'ensemble'),
            ),
        ));
    }
    
    /**
     * Check for reservation shortcode
     */
    private function has_reservation_shortcode() {
        global $post;
        if (!$post) return false;
        
        return has_shortcode($post->post_content, 'ensemble_reservation_form') ||
               has_shortcode($post->post_content, 'ensemble_guestlist');
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'ensemble',
            __('Reservations', 'ensemble'),
            __('Reservations', 'ensemble'),
            'edit_posts',
            'ensemble-reservations',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * Add meta box to events
     */
    public function add_event_meta_box() {
        $post_type = function_exists('ensemble_get_post_type') ? ensemble_get_post_type() : 'ensemble_event';
        
        add_meta_box(
            'ensemble_reservation_settings',
            __('Reservations', 'ensemble'),
            array($this, 'render_event_meta_box'),
            $post_type,
            'side',
            'default'
        );
    }
    
    /**
     * Render event meta box
     */
    public function render_event_meta_box($post) {
        $enabled = get_post_meta($post->ID, '_reservation_enabled', true);
        $types = get_post_meta($post->ID, '_reservation_types', true) ?: array('guestlist');
        $capacity = get_post_meta($post->ID, '_reservation_capacity', true) ?: '';
        $deadline_hours = get_post_meta($post->ID, '_reservation_deadline_hours', true) ?: 24;
        $auto_confirm = get_post_meta($post->ID, '_reservation_auto_confirm', true);
        
        wp_nonce_field('ensemble_reservation_meta', 'ensemble_reservation_nonce');
        ?>
        <div class="es-reservation-meta">
            <p>
                <label>
                    <input type="checkbox" name="reservation_enabled" value="1" <?php checked($enabled, '1'); ?>>
                    <?php _e('Enable reservations', 'ensemble'); ?>
                </label>
            </p>
            
            <div class="es-reservation-options" style="<?php echo $enabled ? '' : 'display:none;'; ?>">
                <p>
                    <label><strong><?php _e('Reservation types', 'ensemble'); ?></strong></label><br>
                    <label>
                        <input type="checkbox" name="reservation_types[]" value="guestlist" <?php checked(in_array('guestlist', (array)$types)); ?>>
                        <?php _e('Guest List', 'ensemble'); ?>
                    </label><br>
                    <label>
                        <input type="checkbox" name="reservation_types[]" value="table" <?php checked(in_array('table', (array)$types)); ?>>
                        <?php _e('Table Reservation', 'ensemble'); ?>
                    </label><br>
                    <label>
                        <input type="checkbox" name="reservation_types[]" value="vip" <?php checked(in_array('vip', (array)$types)); ?>>
                        <?php _e('VIP List', 'ensemble'); ?>
                    </label>
                </p>
                
                <p>
                    <label><strong><?php _e('Max. Capacity', 'ensemble'); ?></strong></label><br>
                    <input type="number" name="reservation_capacity" value="<?php echo esc_attr($capacity); ?>" 
                           min="0" style="width: 80px;" placeholder="∞">
                    <span class="description"><?php _e('Empty = unlimited', 'ensemble'); ?></span>
                </p>
                
                <p>
                    <label><strong><?php _e('Registration deadline', 'ensemble'); ?></strong></label><br>
                    <input type="number" name="reservation_deadline_hours" value="<?php echo esc_attr($deadline_hours); ?>" 
                           min="0" style="width: 80px;"> <?php _e('hours before event', 'ensemble'); ?>
                </p>
                
                <p>
                    <label>
                        <input type="checkbox" name="reservation_auto_confirm" value="1" <?php checked($auto_confirm, '1'); ?>>
                        <?php _e('Auto-confirm', 'ensemble'); ?>
                    </label>
                </p>
                
                <?php 
                $count = $this->get_reservation_count($post->ID);
                if ($count > 0):
                ?>
                <p class="es-reservation-count">
                    <strong><?php printf(__('%d reservation(s)', 'ensemble'), $count); ?></strong>
                    <a href="<?php echo admin_url('admin.php?page=ensemble-reservations&event_id=' . $post->ID); ?>">
                        <?php _e('Anzeigen →', 'ensemble'); ?>
                    </a>
                </p>
                <?php endif; ?>
            </div>
        </div>
        
        <script>
        jQuery(function($) {
            $('input[name="reservation_enabled"]').on('change', function() {
                $('.es-reservation-options').toggle(this.checked);
            });
        });
        </script>
        <?php
    }
    
    /**
     * Save event meta
     */
    public function save_event_meta($post_id) {
        if (!isset($_POST['ensemble_reservation_nonce']) || 
            !wp_verify_nonce($_POST['ensemble_reservation_nonce'], 'ensemble_reservation_meta')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        update_post_meta($post_id, '_reservation_enabled', isset($_POST['reservation_enabled']) ? '1' : '0');
        update_post_meta($post_id, '_reservation_types', isset($_POST['reservation_types']) ? array_map('sanitize_key', $_POST['reservation_types']) : array());
        update_post_meta($post_id, '_reservation_capacity', isset($_POST['reservation_capacity']) ? absint($_POST['reservation_capacity']) : '');
        update_post_meta($post_id, '_reservation_deadline_hours', isset($_POST['reservation_deadline_hours']) ? absint($_POST['reservation_deadline_hours']) : 24);
        update_post_meta($post_id, '_reservation_auto_confirm', isset($_POST['reservation_auto_confirm']) ? '1' : '0');
    }
    
    /**
     * Render reservation section in event template
     */
    public function render_reservation_section($event_id) {
        // Check display settings
        if (function_exists('ensemble_show_addon') && !ensemble_show_addon('reservations')) {
            return;
        }
        
        $enabled = get_post_meta($event_id, '_reservation_enabled', true);
        
        if (!$enabled) {
            return;
        }
        
        // Check deadline
        if (!$this->is_reservation_open($event_id)) {
            echo '<div class="es-reservation-closed">';
            echo '<p>' . __('Registration deadline for this event has passed.', 'ensemble') . '</p>';
            echo '</div>';
            return;
        }
        
        // Check capacity
        $capacity = get_post_meta($event_id, '_reservation_capacity', true);
        $current_count = $this->get_guest_count($event_id);
        
        if ($capacity && $current_count >= $capacity) {
            echo '<div class="es-reservation-full">';
            echo '<p>' . __('This event is unfortunately sold out.', 'ensemble') . '</p>';
            echo '</div>';
            return;
        }
        
        // Get available types
        $types = get_post_meta($event_id, '_reservation_types', true) ?: array('guestlist');
        
        echo $this->load_template('reservation-form', array(
            'event_id'   => $event_id,
            'types'      => $types,
            'capacity'   => $capacity,
            'remaining'  => $capacity ? ($capacity - $current_count) : null,
        ));
    }
    
    /**
     * Check if reservations are open
     */
    private function is_reservation_open($event_id) {
        $deadline_hours = get_post_meta($event_id, '_reservation_deadline_hours', true) ?: 24;
        $event_date = get_post_meta($event_id, '_event_start_date', true);
        $event_time = get_post_meta($event_id, '_event_start_time', true) ?: '00:00';
        
        if (!$event_date) {
            return true; // No date set, allow reservations
        }
        
        $event_timestamp = strtotime($event_date . ' ' . $event_time);
        $deadline_timestamp = $event_timestamp - ($deadline_hours * 3600);
        
        return time() < $deadline_timestamp;
    }
    
    /**
     * Get total guest count for event
     */
    private function get_guest_count($event_id, $status = null) {
        global $wpdb;
        
        $where = "event_id = %d AND status != 'cancelled'";
        $params = array($event_id);
        
        if ($status) {
            $where .= " AND status = %s";
            $params[] = $status;
        }
        
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(guests), 0) FROM {$this->table_name} WHERE $where",
            $params
        ));
    }
    
    /**
     * Get reservation count for event
     */
    private function get_reservation_count($event_id, $status = null) {
        global $wpdb;
        
        $where = "event_id = %d";
        $params = array($event_id);
        
        if ($status) {
            $where .= " AND status = %s";
            $params[] = $status;
        }
        
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE $where",
            $params
        ));
    }
    
    /**
     * AJAX: Submit reservation
     */
    public function ajax_submit_reservation() {
        check_ajax_referer('ensemble_reservations', 'nonce');
        
        $event_id = isset($_POST['event_id']) ? absint($_POST['event_id']) : 0;
        $type = isset($_POST['type']) ? sanitize_key($_POST['type']) : 'guestlist';
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
        $guests = isset($_POST['guests']) ? absint($_POST['guests']) : 1;
        $table_number = isset($_POST['table_number']) ? sanitize_text_field($_POST['table_number']) : '';
        $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';
        
        // Validation
        if (!$event_id || !$name || !$email) {
            wp_send_json_error(array('message' => __('Please fill in all required fields.', 'ensemble')));
        }
        
        if (!is_email($email)) {
            wp_send_json_error(array('message' => __('Please enter a valid email address.', 'ensemble')));
        }
        
        // Check if reservations are enabled
        if (!get_post_meta($event_id, '_reservation_enabled', true)) {
            wp_send_json_error(array('message' => __('Reservations are not enabled for this event.', 'ensemble')));
        }
        
        // Check deadline
        if (!$this->is_reservation_open($event_id)) {
            wp_send_json_error(array('message' => __('Die Anmeldefrist ist abgelaufen.', 'ensemble')));
        }
        
        // Check capacity
        $capacity = get_post_meta($event_id, '_reservation_capacity', true);
        if ($capacity) {
            $current_count = $this->get_guest_count($event_id);
            if (($current_count + $guests) > $capacity) {
                wp_send_json_error(array('message' => __('Not enough seats available.', 'ensemble')));
            }
        }
        
        // Check for duplicate
        if ($this->has_existing_reservation($event_id, $email)) {
            wp_send_json_error(array('message' => __('You already have a reservation for this event.', 'ensemble')));
        }
        
        // Determine initial status
        $auto_confirm = get_post_meta($event_id, '_reservation_auto_confirm', true);
        $status = $auto_confirm ? self::STATUS_CONFIRMED : self::STATUS_PENDING;
        
        // Generate confirmation code
        $confirmation_code = $this->generate_confirmation_code();
        
        // Insert reservation
        global $wpdb;
        
        $result = $wpdb->insert(
            $this->table_name,
            array(
                'event_id'          => $event_id,
                'type'              => $type,
                'status'            => $status,
                'name'              => $name,
                'email'             => $email,
                'phone'             => $phone,
                'guests'            => $guests,
                'table_number'      => $table_number,
                'notes'             => $notes,
                'confirmation_code' => $confirmation_code,
                'created_at'        => current_time('mysql'),
                'updated_at'        => current_time('mysql'),
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s')
        );
        
        if (!$result) {
            wp_send_json_error(array('message' => __('Error saving. Please try again.', 'ensemble')));
        }
        
        $reservation_id = $wpdb->insert_id;
        
        // Generate QR code URL
        $qr_code = $this->generate_qr_code_url($confirmation_code);
        $wpdb->update(
            $this->table_name,
            array('qr_code' => $qr_code),
            array('id' => $reservation_id),
            array('%s'),
            array('%d')
        );
        
        // Send confirmation email
        $this->send_confirmation_email($reservation_id);
        
        // Send admin notification
        $this->send_admin_notification($reservation_id);
        
        wp_send_json_success(array(
            'message'           => $status === self::STATUS_CONFIRMED 
                ? __('Your reservation has been confirmed! Check your email.', 'ensemble')
                : __('Your reservation has been submitted and is being reviewed.', 'ensemble'),
            'reservation_id'    => $reservation_id,
            'confirmation_code' => $confirmation_code,
            'status'            => $status,
        ));
    }
    
    /**
     * Check for existing reservation
     */
    private function has_existing_reservation($event_id, $email) {
        global $wpdb;
        
        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE event_id = %d AND email = %s AND status != 'cancelled'",
            $event_id,
            $email
        ));
    }
    
    /**
     * Generate confirmation code
     */
    private function generate_confirmation_code() {
        return strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
    }
    
    /**
     * Generate QR code URL
     */
    private function generate_qr_code_url($code) {
        // Create a public check-in URL (no admin access required)
        $checkin_url = add_query_arg(array(
            'es_checkin' => $code,
        ), home_url('/'));
        
        return 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($checkin_url);
    }
    
    /**
     * Handle QR code check-in via URL parameter
     * URL format: https://yoursite.com/?es_checkin=CODE123
     */
    public function handle_qr_checkin() {
        if (!isset($_GET['es_checkin']) || empty($_GET['es_checkin'])) {
            return;
        }
        
        $code = sanitize_text_field($_GET['es_checkin']);
        
        // Get reservation by code
        global $wpdb;
        $reservation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE confirmation_code = %s",
            $code
        ));
        
        // Load check-in result template
        $this->load_checkin_page($reservation, $code);
        exit;
    }
    
    /**
     * Load check-in result page
     */
    private function load_checkin_page($reservation, $code) {
        $result = array(
            'success' => false,
            'message' => '',
            'reservation' => null,
            'event' => null,
        );
        
        if (!$reservation) {
            $result['message'] = __('Reservation not found. Please check the code.', 'ensemble');
        } elseif ($reservation->status === 'cancelled') {
            $result['message'] = __('This reservation has been cancelled.', 'ensemble');
        } elseif ($reservation->status === 'checked_in') {
            // Already checked in - show info
            $result['success'] = true;
            $result['already_checked_in'] = true;
            $result['message'] = sprintf(
                __('Bereits eingecheckt am %s', 'ensemble'),
                date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($reservation->checked_in_at))
            );
            $result['reservation'] = $reservation;
            $result['event'] = get_post($reservation->event_id);
        } else {
            // Perform check-in
            global $wpdb;
            $updated = $wpdb->update(
                $this->table_name,
                array(
                    'status' => 'checked_in',
                    'checked_in_at' => current_time('mysql'),
                ),
                array('id' => $reservation->id),
                array('%s', '%s'),
                array('%d')
            );
            
            if ($updated !== false) {
                $result['success'] = true;
                $result['message'] = __('Check-in erfolgreich!', 'ensemble');
                $result['reservation'] = $this->get_reservation($reservation->id);
                $result['event'] = get_post($reservation->event_id);
            } else {
                $result['message'] = __('Check-in error. Please try again.', 'ensemble');
            }
        }
        
        // Output the check-in page
        $this->render_checkin_page($result);
    }
    
    /**
     * Render check-in result page
     */
    private function render_checkin_page($result) {
        ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $result['success'] ? __('Check-in Successful', 'ensemble') : __('Check-in Error', 'ensemble'); ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: #fff;
        }
        .checkin-card {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            max-width: 400px;
            width: 100%;
            text-align: center;
            box-shadow: 0 25px 50px rgba(0,0,0,0.3);
        }
        .checkin-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .checkin-icon.success { background: #10b981; }
        .checkin-icon.error { background: #ef4444; }
        .checkin-icon.warning { background: #f59e0b; }
        .checkin-icon svg {
            width: 40px;
            height: 40px;
            stroke: #fff;
            stroke-width: 3;
            fill: none;
        }
        h1 {
            font-size: 24px;
            margin-bottom: 10px;
            font-weight: 600;
        }
        .message {
            color: rgba(255,255,255,0.8);
            margin-bottom: 30px;
            font-size: 16px;
        }
        .details {
            background: rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 20px;
            text-align: left;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .detail-row:last-child { border-bottom: none; }
        .detail-label {
            color: rgba(255,255,255,0.6);
            font-size: 14px;
        }
        .detail-value {
            font-weight: 600;
            font-size: 14px;
        }
        .guest-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            background: #3b82f6;
            border-radius: 50%;
            font-weight: 700;
        }
        .type-badge {
            display: inline-block;
            padding: 4px 12px;
            background: rgba(255,255,255,0.2);
            border-radius: 20px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .type-badge.vip {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }
    </style>
</head>
<body>
    <div class="checkin-card">
        <?php if ($result['success']): ?>
            <div class="checkin-icon <?php echo isset($result['already_checked_in']) ? 'warning' : 'success'; ?>">
                <?php if (isset($result['already_checked_in'])): ?>
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
                <?php else: ?>
                    <svg viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg>
                <?php endif; ?>
            </div>
            <h1><?php echo isset($result['already_checked_in']) ? __('Bereits eingecheckt', 'ensemble') : __('Willkommen!', 'ensemble'); ?></h1>
            <p class="message"><?php echo esc_html($result['message']); ?></p>
            
            <?php if ($result['reservation'] && $result['event']): ?>
            <div class="details">
                <div class="detail-row">
                    <span class="detail-label"><?php _e('Name', 'ensemble'); ?></span>
                    <span class="detail-value"><?php echo esc_html($result['reservation']->name); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label"><?php _e('Event', 'ensemble'); ?></span>
                    <span class="detail-value"><?php echo esc_html($result['event']->post_title); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label"><?php _e('Guests', 'ensemble'); ?></span>
                    <span class="detail-value"><span class="guest-count"><?php echo intval($result['reservation']->guests); ?></span></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label"><?php _e('Typ', 'ensemble'); ?></span>
                    <span class="detail-value">
                        <span class="type-badge <?php echo $result['reservation']->type === 'vip' ? 'vip' : ''; ?>">
                            <?php 
                            echo $result['reservation']->type === 'guestlist' ? __('Guest list', 'ensemble') : 
                                 ($result['reservation']->type === 'table' ? __('Tisch', 'ensemble') : __('VIP', 'ensemble')); 
                            ?>
                        </span>
                    </span>
                </div>
                <?php if ($result['reservation']->table_number): ?>
                <div class="detail-row">
                    <span class="detail-label"><?php _e('Tisch', 'ensemble'); ?></span>
                    <span class="detail-value">#<?php echo esc_html($result['reservation']->table_number); ?></span>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="checkin-icon error">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6M9 9l6 6"/></svg>
            </div>
            <h1><?php _e('Check-in fehlgeschlagen', 'ensemble'); ?></h1>
            <p class="message"><?php echo esc_html($result['message']); ?></p>
        <?php endif; ?>
    </div>
</body>
</html>
        <?php
    }
    
    /**
     * Send confirmation email
     */
    private function send_confirmation_email($reservation_id) {
        $reservation = $this->get_reservation($reservation_id);
        if (!$reservation) return false;
        
        $event = get_post($reservation->event_id);
        if (!$event) return false;
        
        $event_date = get_post_meta($reservation->event_id, '_event_start_date', true);
        $event_time = get_post_meta($reservation->event_id, '_event_start_time', true);
        
        $subject = sprintf(
            __('Reservation %s: %s', 'ensemble'),
            $reservation->status === self::STATUS_CONFIRMED ? __('confirmed', 'ensemble') : __('received', 'ensemble'),
            $event->post_title
        );
        
        $message = $this->load_template('email-confirmation', array(
            'reservation' => $reservation,
            'event'       => $event,
            'event_date'  => $event_date,
            'event_time'  => $event_time,
        ), true);
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        return wp_mail($reservation->email, $subject, $message, $headers);
    }
    
    /**
     * Send admin notification
     */
    private function send_admin_notification($reservation_id) {
        if (!$this->get_setting('admin_notifications', true)) {
            return;
        }
        
        $reservation = $this->get_reservation($reservation_id);
        if (!$reservation) return;
        
        $event = get_post($reservation->event_id);
        if (!$event) return;
        
        $admin_email = $this->get_setting('notification_email', get_option('admin_email'));
        
        $subject = sprintf(__('New Reservation: %s', 'ensemble'), $event->post_title);
        
        $message = sprintf(
            __("New reservation received:\n\nEvent: %s\nName: %s\nE-Mail: %s\nPersonen: %d\nTyp: %s\n\nZur Verwaltung: %s", 'ensemble'),
            $event->post_title,
            $reservation->name,
            $reservation->email,
            $reservation->guests,
            $this->get_type_label($reservation->type),
            admin_url('admin.php?page=ensemble-reservations&event_id=' . $reservation->event_id)
        );
        
        wp_mail($admin_email, $subject, $message);
    }
    
    /**
     * Get single reservation
     */
    public function get_reservation($reservation_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $reservation_id
        ));
    }
    
    /**
     * Get reservation by confirmation code
     */
    public function get_reservation_by_code($code) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE confirmation_code = %s",
            $code
        ));
    }
    
    /**
     * Get reservations for event
     */
    public function get_event_reservations($event_id, $args = array()) {
        global $wpdb;
        
        $defaults = array(
            'status'  => '',
            'type'    => '',
            'search'  => '',
            'orderby' => 'created_at',
            'order'   => 'DESC',
            'limit'   => 0,
            'offset'  => 0,
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = "event_id = %d";
        $params = array($event_id);
        
        if ($args['status']) {
            $where .= " AND status = %s";
            $params[] = $args['status'];
        }
        
        if ($args['type']) {
            $where .= " AND type = %s";
            $params[] = $args['type'];
        }
        
        if ($args['search']) {
            $where .= " AND (name LIKE %s OR email LIKE %s)";
            $search = '%' . $wpdb->esc_like($args['search']) . '%';
            $params[] = $search;
            $params[] = $search;
        }
        
        $orderby = in_array($args['orderby'], array('name', 'created_at', 'status', 'guests')) 
            ? $args['orderby'] : 'created_at';
        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';
        
        $sql = "SELECT * FROM {$this->table_name} WHERE $where ORDER BY $orderby $order";
        
        if ($args['limit'] > 0) {
            $sql .= $wpdb->prepare(" LIMIT %d OFFSET %d", $args['limit'], $args['offset']);
        }
        
        return $wpdb->get_results($wpdb->prepare($sql, $params));
    }
    
    /**
     * AJAX: Update reservation status
     */
    public function ajax_update_status() {
        check_ajax_referer('ensemble_reservations_admin', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'ensemble')));
        }
        
        $reservation_id = isset($_POST['reservation_id']) ? absint($_POST['reservation_id']) : 0;
        $status = isset($_POST['status']) ? sanitize_key($_POST['status']) : '';
        
        if (!$reservation_id || !in_array($status, array(self::STATUS_PENDING, self::STATUS_CONFIRMED, self::STATUS_CANCELLED))) {
            wp_send_json_error(array('message' => __('Invalid parameters.', 'ensemble')));
        }
        
        global $wpdb;
        
        $old_reservation = $this->get_reservation($reservation_id);
        
        $result = $wpdb->update(
            $this->table_name,
            array(
                'status'     => $status,
                'updated_at' => current_time('mysql'),
            ),
            array('id' => $reservation_id),
            array('%s', '%s'),
            array('%d')
        );
        
        if ($result === false) {
            wp_send_json_error(array('message' => __('Error updating.', 'ensemble')));
        }
        
        // Send status change email if status changed
        if ($old_reservation && $old_reservation->status !== $status) {
            $this->send_status_change_email($reservation_id, $status);
        }
        
        wp_send_json_success(array(
            'message' => __('Status aktualisiert.', 'ensemble'),
            'status'  => $status,
        ));
    }
    
    /**
     * Send status change email
     */
    private function send_status_change_email($reservation_id, $new_status) {
        $reservation = $this->get_reservation($reservation_id);
        if (!$reservation) return;
        
        $event = get_post($reservation->event_id);
        if (!$event) return;
        
        $status_labels = array(
            self::STATUS_CONFIRMED => __('confirmed', 'ensemble'),
            self::STATUS_CANCELLED => __('storniert', 'ensemble'),
        );
        
        if (!isset($status_labels[$new_status])) {
            return; // Don't send email for pending status
        }
        
        $subject = sprintf(
            __('Reservation %s: %s', 'ensemble'),
            $status_labels[$new_status],
            $event->post_title
        );
        
        $message = $this->load_template('email-status-change', array(
            'reservation' => $reservation,
            'event'       => $event,
            'new_status'  => $new_status,
            'status_label' => $status_labels[$new_status],
        ), true);
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        wp_mail($reservation->email, $subject, $message, $headers);
    }
    
    /**
     * AJAX: Check-in reservation
     */
    public function ajax_checkin() {
        // Can be called via QR code scan (no nonce) or admin panel
        $code = isset($_REQUEST['code']) ? sanitize_text_field($_REQUEST['code']) : '';
        $reservation_id = isset($_POST['reservation_id']) ? absint($_POST['reservation_id']) : 0;
        
        if ($reservation_id && isset($_POST['nonce'])) {
            check_ajax_referer('ensemble_reservations_admin', 'nonce');
            $reservation = $this->get_reservation($reservation_id);
        } elseif ($code) {
            $reservation = $this->get_reservation_by_code($code);
        } else {
            wp_send_json_error(array('message' => __('Invalid code.', 'ensemble')));
        }
        
        if (!$reservation) {
            wp_send_json_error(array('message' => __('Reservation not found.', 'ensemble')));
        }
        
        if ($reservation->status === self::STATUS_CHECKED_IN) {
            wp_send_json_error(array(
                'message' => sprintf(
                    __('Bereits eingecheckt am %s', 'ensemble'),
                    date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($reservation->checked_in_at))
                ),
            ));
        }
        
        if ($reservation->status === self::STATUS_CANCELLED) {
            wp_send_json_error(array('message' => __('This reservation has been cancelled.', 'ensemble')));
        }
        
        global $wpdb;
        
        $wpdb->update(
            $this->table_name,
            array(
                'status'        => self::STATUS_CHECKED_IN,
                'checked_in_at' => current_time('mysql'),
                'checked_in_by' => get_current_user_id(),
                'updated_at'    => current_time('mysql'),
            ),
            array('id' => $reservation->id),
            array('%s', '%s', '%d', '%s'),
            array('%d')
        );
        
        wp_send_json_success(array(
            'message' => sprintf(
                __('✓ %s checked in! (%d persons)', 'ensemble'),
                $reservation->name,
                $reservation->guests
            ),
            'reservation' => array(
                'id'     => $reservation->id,
                'name'   => $reservation->name,
                'guests' => $reservation->guests,
                'type'   => $reservation->type,
            ),
        ));
    }
    
    /**
     * AJAX: Delete reservation
     */
    public function ajax_delete_reservation() {
        check_ajax_referer('ensemble_reservations_admin', 'nonce');
        
        if (!current_user_can('delete_posts')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'ensemble')));
        }
        
        $reservation_id = isset($_POST['reservation_id']) ? absint($_POST['reservation_id']) : 0;
        
        if (!$reservation_id) {
            wp_send_json_error(array('message' => __('Invalid reservation.', 'ensemble')));
        }
        
        global $wpdb;
        
        $result = $wpdb->delete(
            $this->table_name,
            array('id' => $reservation_id),
            array('%d')
        );
        
        if ($result === false) {
            wp_send_json_error(array('message' => __('Error deleting.', 'ensemble')));
        }
        
        wp_send_json_success(array('message' => __('Reservation deleted.', 'ensemble')));
    }
    
    /**
     * AJAX: Export reservations
     */
    public function ajax_export() {
        check_ajax_referer('ensemble_reservations_admin', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die(__('Permission denied.', 'ensemble'));
        }
        
        $event_id = isset($_GET['event_id']) ? absint($_GET['event_id']) : 0;
        $format = isset($_GET['format']) ? sanitize_key($_GET['format']) : 'csv';
        
        if (!$event_id) {
            wp_die(__('Event nicht gefunden.', 'ensemble'));
        }
        
        $reservations = $this->get_event_reservations($event_id);
        $event = get_post($event_id);
        
        if ($format === 'csv') {
            $this->export_csv($reservations, $event);
        } else {
            $this->export_pdf($reservations, $event);
        }
        
        exit;
    }
    
    /**
     * Export to CSV
     */
    private function export_csv($reservations, $event) {
        $filename = sanitize_title($event->post_title) . '-gaesteliste-' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // UTF-8 BOM for Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Header row
        fputcsv($output, array(
            __('Name', 'ensemble'),
            __('E-Mail', 'ensemble'),
            __('Telefon', 'ensemble'),
            __('Personen', 'ensemble'),
            __('Typ', 'ensemble'),
            __('Status', 'ensemble'),
            __('Tisch', 'ensemble'),
            __('Notizen', 'ensemble'),
            __('Code', 'ensemble'),
            __('Erstellt', 'ensemble'),
        ), ';');
        
        foreach ($reservations as $res) {
            fputcsv($output, array(
                $res->name,
                $res->email,
                $res->phone,
                $res->guests,
                $this->get_type_label($res->type),
                $this->get_status_label($res->status),
                $res->table_number,
                $res->notes,
                $res->confirmation_code,
                $res->created_at,
            ), ';');
        }
        
        fclose($output);
    }
    
    /**
     * Get type label
     */
    public function get_type_label($type) {
        $types = array(
            self::TYPE_GUESTLIST => __('Guest list', 'ensemble'),
            self::TYPE_TABLE     => __('Table Reservation', 'ensemble'),
            self::TYPE_VIP       => __('VIP', 'ensemble'),
        );
        
        return $types[$type] ?? $type;
    }
    
    /**
     * Get status label
     */
    public function get_status_label($status) {
        $statuses = array(
            self::STATUS_PENDING    => __('Pending', 'ensemble'),
            self::STATUS_CONFIRMED  => __('Confirmed', 'ensemble'),
            self::STATUS_CANCELLED  => __('Cancelled', 'ensemble'),
            self::STATUS_CHECKED_IN => __('Checked In', 'ensemble'),
            self::STATUS_NO_SHOW    => __('No Show', 'ensemble'),
        );
        
        return $statuses[$status] ?? $status;
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        echo $this->load_template('admin-page', array(
            'addon' => $this,
        ));
    }
    
    /**
     * Shortcode: Reservation form
     */
    public function shortcode_reservation_form($atts) {
        $atts = shortcode_atts(array(
            'event'  => 0,
            'type'   => '',
            'button' => __('Reserve', 'ensemble'),
        ), $atts, 'ensemble_reservation_form');
        
        $event_id = $atts['event'] ?: get_the_ID();
        
        if (!get_post_meta($event_id, '_reservation_enabled', true)) {
            return '';
        }
        
        $types = $atts['type'] ? array($atts['type']) : (get_post_meta($event_id, '_reservation_types', true) ?: array('guestlist'));
        
        return $this->load_template('reservation-form', array(
            'event_id'    => $event_id,
            'types'       => $types,
            'button_text' => $atts['button'],
            'shortcode'   => true,
        ));
    }
    
    /**
     * Shortcode: Public guestlist
     */
    public function shortcode_guestlist($atts) {
        $atts = shortcode_atts(array(
            'event'       => 0,
            'show_count'  => 'true',
            'show_names'  => 'false',
        ), $atts, 'ensemble_guestlist');
        
        $event_id = $atts['event'] ?: get_the_ID();
        
        $reservations = $this->get_event_reservations($event_id, array(
            'status' => self::STATUS_CONFIRMED,
        ));
        
        $total_guests = array_sum(array_column($reservations, 'guests'));
        
        return $this->load_template('public-guestlist', array(
            'event_id'     => $event_id,
            'reservations' => $reservations,
            'total_guests' => $total_guests,
            'show_count'   => filter_var($atts['show_count'], FILTER_VALIDATE_BOOLEAN),
            'show_names'   => filter_var($atts['show_names'], FILTER_VALIDATE_BOOLEAN),
        ));
    }
    
    /**
     * Register REST routes
     */
    public function register_rest_routes() {
        register_rest_route('ensemble/v1', '/reservations/(?P<event_id>\d+)', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'rest_get_reservations'),
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            },
        ));
        
        register_rest_route('ensemble/v1', '/reservations/checkin/(?P<code>[A-Z0-9]+)', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'rest_checkin'),
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            },
        ));
    }
    
    /**
     * REST: Get reservations
     */
    public function rest_get_reservations($request) {
        $event_id = $request->get_param('event_id');
        $reservations = $this->get_event_reservations($event_id);
        
        return new WP_REST_Response($reservations, 200);
    }
    
    /**
     * REST: Check-in
     */
    public function rest_checkin($request) {
        $code = $request->get_param('code');
        $_REQUEST['code'] = $code;
        
        // Reuse AJAX logic
        ob_start();
        $this->ajax_checkin();
        $response = ob_get_clean();
        
        return new WP_REST_Response(json_decode($response), 200);
    }
    
    /**
     * Render settings
     */
    public function render_settings() {
        return $this->load_template('settings', array(
            'settings' => $this->settings,
        ));
    }
    
    /**
     * Sanitize settings
     */
    public function sanitize_settings($settings) {
        return array(
            'admin_notifications' => isset($settings['admin_notifications']) ? (bool)$settings['admin_notifications'] : true,
            'notification_email'  => isset($settings['notification_email']) ? sanitize_email($settings['notification_email']) : '',
            'send_reminders'      => isset($settings['send_reminders']) ? (bool)$settings['send_reminders'] : false,
            'reminder_hours'      => isset($settings['reminder_hours']) ? absint($settings['reminder_hours']) : 24,
            'default_capacity'    => isset($settings['default_capacity']) ? absint($settings['default_capacity']) : 0,
            'require_phone'       => isset($settings['require_phone']) ? (bool)$settings['require_phone'] : false,
        );
    }
    
    /**
     * Send reminder emails
     */
    public function send_reminders() {
        if (!$this->get_setting('send_reminders', false)) {
            return;
        }
        
        $reminder_hours = $this->get_setting('reminder_hours', 24);
        
        // Find events happening in X hours
        global $wpdb;
        
        $post_type = function_exists('ensemble_get_post_type') ? ensemble_get_post_type() : 'ensemble_event';
        $target_date = date('Y-m-d', strtotime('+' . $reminder_hours . ' hours'));
        
        $events = get_posts(array(
            'post_type'      => $post_type,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => array(
                array(
                    'key'     => '_event_start_date',
                    'value'   => $target_date,
                    'compare' => '=',
                ),
            ),
        ));
        
        foreach ($events as $event) {
            $reservations = $this->get_event_reservations($event->ID, array(
                'status' => self::STATUS_CONFIRMED,
            ));
            
            foreach ($reservations as $reservation) {
                $this->send_reminder_email($reservation, $event);
            }
        }
    }
    
    /**
     * Send reminder email
     */
    private function send_reminder_email($reservation, $event) {
        $subject = sprintf(__('Reminder: %s tomorrow!', 'ensemble'), $event->post_title);
        
        $message = $this->load_template('email-reminder', array(
            'reservation' => $reservation,
            'event'       => $event,
        ), true);
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        wp_mail($reservation->email, $subject, $message, $headers);
    }
}
