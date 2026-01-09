<?php
/**
 * Ensemble U18 Authorization System
 * 
 * Digital "Muttizettel" / "Partyzettel" nach § 1 Abs. 1 Nr. 4 JuSchG
 * Ermöglicht die digitale Übertragung der Aufsichtspflicht für Minderjährige (16-17 Jahre)
 *
 * @package Ensemble
 * @subpackage Addons/Reservations Pro
 * @since 2.8.35
 */

if (!defined('ABSPATH')) {
    exit;
}

class ES_U18_Authorization {
    
    /**
     * Database table name
     */
    private $table_name;
    
    /**
     * Parent addon instance
     */
    private $parent_addon;
    
    /**
     * Statuses
     */
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_REVIEWED = 'reviewed';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_USED = 'used';
    const STATUS_EXPIRED = 'expired';
    
    /**
     * Constructor
     */
    public function __construct($parent_addon) {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'ensemble_u18_authorizations';
        $this->parent_addon = $parent_addon;
        
        $this->maybe_create_table();
        $this->register_hooks();
    }
    
    /**
     * Register hooks
     */
    private function register_hooks() {
        // AJAX handlers - Public
        add_action('wp_ajax_es_submit_u18_authorization', array($this, 'ajax_submit'));
        add_action('wp_ajax_nopriv_es_submit_u18_authorization', array($this, 'ajax_submit'));
        add_action('wp_ajax_es_validate_u18_step', array($this, 'ajax_validate_step'));
        add_action('wp_ajax_nopriv_es_validate_u18_step', array($this, 'ajax_validate_step'));
        
        // AJAX handlers - Admin
        add_action('wp_ajax_es_update_u18_status', array($this, 'ajax_update_status'));
        add_action('wp_ajax_es_delete_u18_authorization', array($this, 'ajax_delete'));
        add_action('wp_ajax_es_get_u18_authorizations', array($this, 'ajax_get_authorizations'));
        add_action('wp_ajax_es_u18_checkin', array($this, 'ajax_checkin'));
        add_action('wp_ajax_es_download_u18_pdf', array($this, 'ajax_download_pdf'));
        add_action('wp_ajax_nopriv_es_download_u18_pdf', array($this, 'ajax_download_pdf'));
        add_action('wp_ajax_es_resend_u18_emails', array($this, 'ajax_resend_emails'));
        add_action('wp_ajax_es_view_u18_id', array($this, 'ajax_view_id_upload'));
        
        // QR Code Check-in Handler
        add_action('template_redirect', array($this, 'handle_qr_checkin'));
        
        // Shortcode
        add_shortcode('ensemble_u18_form', array($this, 'shortcode_form'));
        
        // DSGVO Auto-Löschung
        add_action('ensemble_u18_cleanup', array($this, 'cleanup_expired'));
        if (!wp_next_scheduled('ensemble_u18_cleanup')) {
            wp_schedule_event(time(), 'daily', 'ensemble_u18_cleanup');
        }
        
        // REST API
        add_action('rest_api_init', array($this, 'register_rest_routes'));
    }
    
    /**
     * Create database table
     */
    private function maybe_create_table() {
        global $wpdb;
        
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'") === $this->table_name;
        
        if ($table_exists) {
            // Prüfe ob Signature-Spalten existieren, wenn nicht hinzufügen
            $this->maybe_add_signature_columns();
            return;
        }
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$this->table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            event_id bigint(20) unsigned NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'submitted',
            
            /* Erziehungsberechtigter (Parent/Guardian) */
            parent_lastname varchar(100) NOT NULL,
            parent_firstname varchar(100) NOT NULL,
            parent_street varchar(255) NOT NULL,
            parent_city varchar(100) NOT NULL,
            parent_zip varchar(20) NOT NULL,
            parent_phone varchar(50) NOT NULL,
            parent_email varchar(255) NOT NULL,
            
            /* Minderjährige Person (Minor) */
            minor_lastname varchar(100) NOT NULL,
            minor_firstname varchar(100) NOT NULL,
            minor_birthdate date NOT NULL,
            minor_street varchar(255) NOT NULL,
            minor_city varchar(100) NOT NULL,
            minor_zip varchar(20) NOT NULL,
            
            /* Begleitperson (Accompanying Adult) */
            companion_lastname varchar(100) NOT NULL,
            companion_firstname varchar(100) NOT NULL,
            companion_birthdate date NOT NULL,
            companion_street varchar(255) NOT NULL,
            companion_city varchar(100) NOT NULL,
            companion_zip varchar(20) NOT NULL,
            companion_phone varchar(50) DEFAULT '',
            companion_email varchar(255) DEFAULT '',
            
            /* ID Upload (encrypted path or reference) */
            id_upload_path varchar(500) DEFAULT '',
            id_upload_hash varchar(64) DEFAULT '',
            
            /* Digital Signatures (Base64 encoded) */
            parent_signature longtext DEFAULT NULL,
            companion_signature longtext DEFAULT NULL,
            
            /* Authorization Details */
            authorization_code varchar(32) NOT NULL,
            qr_code_data text DEFAULT '',
            consent_timestamp datetime NOT NULL,
            consent_ip varchar(45) NOT NULL,
            
            /* Check-in */
            checked_in_at datetime DEFAULT NULL,
            checked_in_by bigint(20) unsigned DEFAULT NULL,
            checkout_at datetime DEFAULT NULL,
            
            /* Metadata */
            notes text DEFAULT '',
            admin_notes text DEFAULT '',
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            expires_at datetime NOT NULL,
            deleted_at datetime DEFAULT NULL,
            
            PRIMARY KEY (id),
            KEY event_id (event_id),
            KEY status (status),
            KEY authorization_code (authorization_code),
            KEY minor_birthdate (minor_birthdate),
            KEY expires_at (expires_at),
            KEY parent_email (parent_email),
            KEY companion_email (companion_email)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Add signature columns if they don't exist (migration)
     */
    private function maybe_add_signature_columns() {
        global $wpdb;
        
        // Check if parent_signature column exists
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$this->table_name} LIKE 'parent_signature'");
        
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE {$this->table_name} ADD COLUMN parent_signature LONGTEXT DEFAULT NULL AFTER id_upload_hash");
            $wpdb->query("ALTER TABLE {$this->table_name} ADD COLUMN companion_signature LONGTEXT DEFAULT NULL AFTER parent_signature");
        }
    }
    
    /**
     * Get addon URL for assets
     */
    private function get_addon_url() {
        return plugin_dir_url(__FILE__);
    }
    
    /**
     * AJAX: Submit U18 Authorization
     */
    public function ajax_submit() {
        // Verify nonce - check both possible field names
        $nonce = $_POST['u18_nonce'] ?? $_POST['nonce'] ?? '';
        if (!wp_verify_nonce($nonce, 'ensemble_u18')) {
            wp_send_json_error(array('message' => __('Sicherheitsfehler. Bitte Seite neu laden.', 'ensemble')));
        }
        
        // Validate required fields
        $required_fields = array(
            'event_id', 
            'parent_lastname', 'parent_firstname', 'parent_street', 'parent_city', 'parent_zip', 'parent_phone', 'parent_email',
            'minor_lastname', 'minor_firstname', 'minor_birthdate', 'minor_street', 'minor_city', 'minor_zip',
            'companion_lastname', 'companion_firstname', 'companion_birthdate', 'companion_street', 'companion_city', 'companion_zip'
        );
        
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                wp_send_json_error(array(
                    'message' => sprintf(__('Pflichtfeld "%s" fehlt.', 'ensemble'), $field),
                    'field' => $field
                ));
            }
        }
        
        // Validate minor's age (must be 16-17)
        $minor_birthdate = sanitize_text_field($_POST['minor_birthdate']);
        $minor_age = $this->calculate_age($minor_birthdate);
        
        if ($minor_age < 16 || $minor_age >= 18) {
            wp_send_json_error(array(
                'message' => __('Die minderjährige Person muss zwischen 16 und 17 Jahren alt sein.', 'ensemble'),
                'field' => 'minor_birthdate'
            ));
        }
        
        // Validate companion's age (must be 18+)
        $companion_birthdate = sanitize_text_field($_POST['companion_birthdate']);
        $companion_age = $this->calculate_age($companion_birthdate);
        
        if ($companion_age < 18) {
            wp_send_json_error(array(
                'message' => __('Die Begleitperson muss mindestens 18 Jahre alt sein.', 'ensemble'),
                'field' => 'companion_birthdate'
            ));
        }
        
        // Validate email
        $parent_email = sanitize_email($_POST['parent_email']);
        if (!is_email($parent_email)) {
            wp_send_json_error(array(
                'message' => __('Bitte geben Sie eine gültige E-Mail-Adresse ein.', 'ensemble'),
                'field' => 'parent_email'
            ));
        }
        
        // Validate event exists and has U18 enabled
        $event_id = absint($_POST['event_id']);
        $u18_enabled = get_post_meta($event_id, '_u18_authorization_enabled', true);
        
        if (!$u18_enabled) {
            wp_send_json_error(array('message' => __('U18-Formulare sind für dieses Event nicht aktiviert.', 'ensemble')));
        }
        
        // Check for duplicate submission
        global $wpdb;
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->table_name} 
             WHERE event_id = %d 
             AND minor_firstname = %s 
             AND minor_lastname = %s 
             AND minor_birthdate = %s
             AND status NOT IN ('rejected', 'expired')
             AND deleted_at IS NULL",
            $event_id,
            sanitize_text_field($_POST['minor_firstname']),
            sanitize_text_field($_POST['minor_lastname']),
            $minor_birthdate
        ));
        
        if ($existing) {
            wp_send_json_error(array('message' => __('Für diese Person wurde bereits ein Antrag eingereicht.', 'ensemble')));
        }
        
        // Handle ID upload if present
        $id_upload_path = '';
        $id_upload_hash = '';
        
        if (!empty($_FILES['id_upload']) && $_FILES['id_upload']['error'] === UPLOAD_ERR_OK) {
            $upload_result = $this->handle_id_upload($_FILES['id_upload']);
            
            if (is_wp_error($upload_result)) {
                wp_send_json_error(array('message' => $upload_result->get_error_message()));
            }
            
            $id_upload_path = $upload_result['path'];
            $id_upload_hash = $upload_result['hash'];
        }
        
        // Generate authorization code
        $authorization_code = $this->generate_authorization_code();
        
        // Get event date for expiration
        $event_date = get_post_meta($event_id, '_event_start_date', true);
        if (empty($event_date)) {
            $event_date = get_post_meta($event_id, 'es_event_start_date', true);
        }
        
        // Expires 7 days after event (DSGVO compliance)
        $expires_at = $event_date 
            ? date('Y-m-d H:i:s', strtotime($event_date . ' +7 days'))
            : date('Y-m-d H:i:s', strtotime('+30 days'));
        
        // Insert record
        $now = current_time('mysql');
        
        $data = array(
            'event_id' => $event_id,
            'status' => self::STATUS_SUBMITTED,
            
            'parent_lastname' => sanitize_text_field($_POST['parent_lastname']),
            'parent_firstname' => sanitize_text_field($_POST['parent_firstname']),
            'parent_street' => sanitize_text_field($_POST['parent_street']),
            'parent_city' => sanitize_text_field($_POST['parent_city']),
            'parent_zip' => sanitize_text_field($_POST['parent_zip']),
            'parent_phone' => sanitize_text_field($_POST['parent_phone']),
            'parent_email' => $parent_email,
            
            'minor_lastname' => sanitize_text_field($_POST['minor_lastname']),
            'minor_firstname' => sanitize_text_field($_POST['minor_firstname']),
            'minor_birthdate' => $minor_birthdate,
            'minor_street' => sanitize_text_field($_POST['minor_street']),
            'minor_city' => sanitize_text_field($_POST['minor_city']),
            'minor_zip' => sanitize_text_field($_POST['minor_zip']),
            
            'companion_lastname' => sanitize_text_field($_POST['companion_lastname']),
            'companion_firstname' => sanitize_text_field($_POST['companion_firstname']),
            'companion_birthdate' => sanitize_text_field($_POST['companion_birthdate']),
            'companion_street' => sanitize_text_field($_POST['companion_street']),
            'companion_city' => sanitize_text_field($_POST['companion_city']),
            'companion_zip' => sanitize_text_field($_POST['companion_zip']),
            'companion_phone' => sanitize_text_field($_POST['companion_phone'] ?? ''),
            'companion_email' => sanitize_email($_POST['companion_email'] ?? ''),
            
            'id_upload_path' => $id_upload_path,
            'id_upload_hash' => $id_upload_hash,
            
            'parent_signature' => $this->sanitize_signature($_POST['parent_signature'] ?? ''),
            'companion_signature' => $this->sanitize_signature($_POST['companion_signature'] ?? ''),
            
            'authorization_code' => $authorization_code,
            'qr_code_data' => $this->generate_qr_data($authorization_code, $event_id),
            'consent_timestamp' => $now,
            'consent_ip' => $this->get_client_ip(),
            
            'notes' => sanitize_textarea_field($_POST['notes'] ?? ''),
            'created_at' => $now,
            'updated_at' => $now,
            'expires_at' => $expires_at,
        );
        
        $result = $wpdb->insert($this->table_name, $data);
        
        if (!$result) {
            wp_send_json_error(array('message' => __('Datenbankfehler. Bitte versuchen Sie es erneut.', 'ensemble')));
        }
        
        $authorization_id = $wpdb->insert_id;
        
        // Send emails
        $this->send_confirmation_emails($authorization_id);
        
        // Send admin notification
        $this->send_admin_notification($authorization_id);
        
        wp_send_json_success(array(
            'message' => __('Antrag erfolgreich eingereicht! Sie erhalten in Kürze eine Bestätigung per E-Mail.', 'ensemble'),
            'authorization_code' => $authorization_code,
            'id' => $authorization_id,
        ));
    }
    
    /**
     * Calculate age from birthdate
     */
    private function calculate_age($birthdate) {
        $birth = new DateTime($birthdate);
        $today = new DateTime();
        $age = $today->diff($birth)->y;
        return $age;
    }
    
    /**
     * Generate unique authorization code
     */
    private function generate_authorization_code() {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // Avoid confusing chars
        $code = '';
        for ($i = 0; $i < 8; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $code;
    }
    
    /**
     * Generate QR code data
     */
    private function generate_qr_data($code, $event_id) {
        $check_url = add_query_arg(array(
            'u18_check' => $code,
            'event' => $event_id,
        ), home_url('/'));
        
        return json_encode(array(
            'type' => 'ensemble_u18',
            'code' => $code,
            'event_id' => $event_id,
            'url' => $check_url,
        ));
    }
    
    /**
     * Get client IP
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR');
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = explode(',', $_SERVER[$key])[0];
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return '0.0.0.0';
    }
    
    /**
     * Sanitize signature data (Base64 PNG)
     */
    private function sanitize_signature($signature) {
        if (empty($signature)) {
            return '';
        }
        
        // Must be a valid data URL for PNG image
        if (strpos($signature, 'data:image/png;base64,') !== 0) {
            return '';
        }
        
        // Extract base64 part
        $base64 = str_replace('data:image/png;base64,', '', $signature);
        
        // Validate base64
        $decoded = base64_decode($base64, true);
        if ($decoded === false) {
            return '';
        }
        
        // Check if it's a valid PNG (starts with PNG magic bytes)
        if (substr($decoded, 0, 8) !== "\x89PNG\r\n\x1a\n") {
            return '';
        }
        
        // Limit size (max 500KB)
        if (strlen($decoded) > 500000) {
            return '';
        }
        
        return $signature;
    }
    
    /**
     * Handle ID upload
     */
    private function handle_id_upload($file) {
        // Validate file type
        $allowed_types = array('image/jpeg', 'image/png', 'image/gif', 'application/pdf');
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mime_type, $allowed_types)) {
            return new WP_Error('invalid_type', __('Ungültiger Dateityp. Erlaubt sind: JPG, PNG, GIF, PDF', 'ensemble'));
        }
        
        // Max file size: 10MB
        if ($file['size'] > 10 * 1024 * 1024) {
            return new WP_Error('file_too_large', __('Datei zu groß. Maximum: 10MB', 'ensemble'));
        }
        
        // Create secure upload directory
        $upload_dir = wp_upload_dir();
        $u18_dir = $upload_dir['basedir'] . '/ensemble-u18/' . date('Y/m');
        
        if (!file_exists($u18_dir)) {
            wp_mkdir_p($u18_dir);
            
            // Add .htaccess for security
            $htaccess = $upload_dir['basedir'] . '/ensemble-u18/.htaccess';
            if (!file_exists($htaccess)) {
                file_put_contents($htaccess, "Order deny,allow\nDeny from all");
            }
            
            // Add index.php
            $index = $upload_dir['basedir'] . '/ensemble-u18/index.php';
            if (!file_exists($index)) {
                file_put_contents($index, '<?php // Silence is golden');
            }
        }
        
        // Generate secure filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = wp_generate_password(32, false) . '.' . $extension;
        $filepath = $u18_dir . '/' . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            return new WP_Error('upload_failed', __('Datei-Upload fehlgeschlagen.', 'ensemble'));
        }
        
        // Generate hash for verification
        $hash = hash_file('sha256', $filepath);
        
        return array(
            'path' => str_replace($upload_dir['basedir'], '', $filepath),
            'hash' => $hash,
        );
    }
    
    /**
     * Send confirmation emails
     */
    public function send_confirmation_emails($authorization_id) {
        $auth = $this->get_authorization($authorization_id);
        if (!$auth) return false;
        
        $event = get_post($auth->event_id);
        $event_date = get_post_meta($auth->event_id, '_event_start_date', true);
        if (empty($event_date)) {
            $event_date = get_post_meta($auth->event_id, 'es_event_start_date', true);
        }
        
        // PDF Download URL with permanent token (no expiration)
        $pdf_url = add_query_arg(array(
            'action' => 'es_download_u18_pdf',
            'code' => $auth->authorization_code,
            'token' => $this->generate_pdf_token($auth->authorization_code),
        ), admin_url('admin-ajax.php'));
        
        // QR Code Check-in URL
        $checkin_url = add_query_arg(array(
            'u18_check' => $auth->authorization_code,
        ), home_url('/'));
        
        // QR Code Image URL (using qrserver.com - free and reliable)
        $qr_code_url = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&format=png&data=' . urlencode($checkin_url);
        
        // Email to parent
        $parent_subject = sprintf(__('Aufsichtsübertragung für %s - Bestätigung', 'ensemble'), $event->post_title);
        $parent_message = $this->get_email_template('confirmation', array(
            'authorization' => $auth,
            'event' => $event,
            'event_date' => $event_date,
            'pdf_url' => $pdf_url,
            'qr_code_url' => $qr_code_url,
            'checkin_url' => $checkin_url,
            'site_name' => get_bloginfo('name'),
            'site_url' => home_url(),
        ));
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        wp_mail($auth->parent_email, $parent_subject, $parent_message, $headers);
        
        // Email to companion (if email provided)
        if (!empty($auth->companion_email) && is_email($auth->companion_email)) {
            $companion_subject = sprintf(__('Aufsichtsübertragung für %s - Information', 'ensemble'), $event->post_title);
            $companion_message = $this->get_email_template('companion', array(
                'authorization' => $auth,
                'event' => $event,
                'event_date' => $event_date,
                'qr_code_url' => $qr_code_url,
                'checkin_url' => $checkin_url,
                'site_name' => get_bloginfo('name'),
                'site_url' => home_url(),
            ));
            
            wp_mail($auth->companion_email, $companion_subject, $companion_message, $headers);
        }
        
        return true;
    }
    
    /**
     * Send admin notification
     */
    private function send_admin_notification($authorization_id) {
        $auth = $this->get_authorization($authorization_id);
        if (!$auth) return false;
        
        $event = get_post($auth->event_id);
        
        // Get notification email
        $settings = get_option('ensemble_addon_reservations', array());
        $admin_email = $settings['notification_email'] ?? get_option('admin_email');
        
        $subject = sprintf(__('[Ensemble] Neuer U18-Antrag für %s', 'ensemble'), $event->post_title);
        
        $message = $this->get_email_template('admin-notification', array(
            'auth' => $auth,
            'event' => $event,
            'admin_url' => admin_url('admin.php?page=ensemble-reservations&tab=u18&event_id=' . $auth->event_id),
        ));
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        wp_mail($admin_email, $subject, $message, $headers);
        
        return true;
    }
    
    /**
     * Get email template
     */
    private function get_email_template($template, $data) {
        extract($data);
        
        ob_start();
        $template_path = dirname(__FILE__) . '/templates/email-u18-' . $template . '.php';
        
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            // Fallback template
            echo $this->get_fallback_email_template($template, $data);
        }
        
        return ob_get_clean();
    }
    
    /**
     * Fallback email template
     */
    private function get_fallback_email_template($template, $data) {
        $auth = $data['auth'];
        $event = $data['event'];
        
        $html = '<html><body style="font-family: Arial, sans-serif; line-height: 1.6;">';
        
        if ($template === 'parent-confirmation') {
            $html .= '<h2>' . __('Aufsichtsübertragung - Bestätigung', 'ensemble') . '</h2>';
            $html .= '<p>' . sprintf(__('Sehr geehrte/r %s %s,', 'ensemble'), $auth->parent_firstname, $auth->parent_lastname) . '</p>';
            $html .= '<p>' . __('Ihr Antrag auf Aufsichtsübertragung wurde erfolgreich eingereicht.', 'ensemble') . '</p>';
            $html .= '<div style="background: #f5f5f5; padding: 15px; margin: 20px 0; border-radius: 5px;">';
            $html .= '<strong>' . __('Event:', 'ensemble') . '</strong> ' . esc_html($event->post_title) . '<br>';
            $html .= '<strong>' . __('Minderjährige Person:', 'ensemble') . '</strong> ' . esc_html($auth->minor_firstname . ' ' . $auth->minor_lastname) . '<br>';
            $html .= '<strong>' . __('Begleitperson:', 'ensemble') . '</strong> ' . esc_html($auth->companion_firstname . ' ' . $auth->companion_lastname) . '<br>';
            $html .= '<strong>' . __('Code:', 'ensemble') . '</strong> ' . esc_html($auth->authorization_code);
            $html .= '</div>';
            
            if (!empty($data['pdf_url'])) {
                $html .= '<p><a href="' . esc_url($data['pdf_url']) . '" style="background: #0073aa; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block;">' . __('PDF herunterladen', 'ensemble') . '</a></p>';
            }
            
            $html .= '<p style="color: #666; font-size: 12px;">' . __('Bitte bringen Sie das Formular ausgedruckt zur Veranstaltung mit. Alle Personen müssen sich mit gültigem Personalausweis ausweisen können.', 'ensemble') . '</p>';
            
        } elseif ($template === 'companion-notification') {
            $html .= '<h2>' . __('Aufsichtsübertragung - Information', 'ensemble') . '</h2>';
            $html .= '<p>' . sprintf(__('Sehr geehrte/r %s %s,', 'ensemble'), $auth->companion_firstname, $auth->companion_lastname) . '</p>';
            $html .= '<p>' . sprintf(__('Sie wurden als Begleitperson für %s %s benannt.', 'ensemble'), $auth->minor_firstname, $auth->minor_lastname) . '</p>';
            $html .= '<div style="background: #fff3cd; padding: 15px; margin: 20px 0; border-radius: 5px; border-left: 4px solid #ffc107;">';
            $html .= '<strong>' . __('Ihre Pflichten als Begleitperson:', 'ensemble') . '</strong><br>';
            $html .= __('• Sie sind für die Aufsicht der minderjährigen Person verantwortlich<br>• Sie müssen gemeinsam mit der minderjährigen Person erscheinen und gehen<br>• Sie müssen sich mit gültigem Personalausweis ausweisen können', 'ensemble');
            $html .= '</div>';
            
        } elseif ($template === 'admin-notification') {
            $html .= '<h2>' . __('Neuer U18-Antrag eingegangen', 'ensemble') . '</h2>';
            $html .= '<p>' . sprintf(__('Ein neuer U18-Antrag für das Event "%s" wurde eingereicht.', 'ensemble'), esc_html($event->post_title)) . '</p>';
            $html .= '<div style="background: #f5f5f5; padding: 15px; margin: 20px 0; border-radius: 5px;">';
            $html .= '<strong>' . __('Elternteil:', 'ensemble') . '</strong> ' . esc_html($auth->parent_firstname . ' ' . $auth->parent_lastname) . '<br>';
            $html .= '<strong>' . __('Minderjährig:', 'ensemble') . '</strong> ' . esc_html($auth->minor_firstname . ' ' . $auth->minor_lastname) . ' (' . esc_html($auth->minor_birthdate) . ')<br>';
            $html .= '<strong>' . __('Begleitung:', 'ensemble') . '</strong> ' . esc_html($auth->companion_firstname . ' ' . $auth->companion_lastname);
            $html .= '</div>';
            
            if (!empty($data['admin_url'])) {
                $html .= '<p><a href="' . esc_url($data['admin_url']) . '" style="background: #0073aa; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block;">' . __('Im Admin prüfen', 'ensemble') . '</a></p>';
            }
        }
        
        $html .= '</body></html>';
        
        return $html;
    }
    
    /**
     * Get authorization by ID
     */
    public function get_authorization($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d AND deleted_at IS NULL",
            $id
        ));
    }
    
    /**
     * Get authorization by code
     */
    public function get_authorization_by_code($code) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE authorization_code = %s AND deleted_at IS NULL",
            $code
        ));
    }
    
    /**
     * Get authorizations for event
     */
    public function get_event_authorizations($event_id, $args = array()) {
        global $wpdb;
        
        $where = array("event_id = %d", "deleted_at IS NULL");
        $values = array($event_id);
        
        if (!empty($args['status'])) {
            $where[] = "status = %s";
            $values[] = $args['status'];
        }
        
        if (!empty($args['search'])) {
            $search = '%' . $wpdb->esc_like($args['search']) . '%';
            $where[] = "(minor_firstname LIKE %s OR minor_lastname LIKE %s OR companion_firstname LIKE %s OR companion_lastname LIKE %s OR authorization_code LIKE %s)";
            $values = array_merge($values, array($search, $search, $search, $search, $search));
        }
        
        $where_sql = implode(' AND ', $where);
        $order = $args['orderby'] ?? 'created_at';
        $order_dir = $args['order'] ?? 'DESC';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE {$where_sql} ORDER BY {$order} {$order_dir}",
            ...$values
        ));
    }
    
    /**
     * Count authorizations for event
     */
    public function get_authorization_count($event_id, $status = '') {
        global $wpdb;
        
        if ($status) {
            return $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} WHERE event_id = %d AND status = %s AND deleted_at IS NULL",
                $event_id, $status
            ));
        }
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE event_id = %d AND deleted_at IS NULL",
            $event_id
        ));
    }
    
    /**
     * AJAX: Update status
     */
    public function ajax_update_status() {
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Keine Berechtigung.', 'ensemble')));
        }
        
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'ensemble_u18_admin')) {
            wp_send_json_error(array('message' => __('Sicherheitsfehler.', 'ensemble')));
        }
        
        $id = absint($_POST['id'] ?? 0);
        $status = sanitize_key($_POST['status'] ?? '');
        
        $valid_statuses = array(self::STATUS_SUBMITTED, self::STATUS_REVIEWED, self::STATUS_APPROVED, self::STATUS_REJECTED);
        
        if (!in_array($status, $valid_statuses)) {
            wp_send_json_error(array('message' => __('Ungültiger Status.', 'ensemble')));
        }
        
        global $wpdb;
        $result = $wpdb->update(
            $this->table_name,
            array(
                'status' => $status,
                'updated_at' => current_time('mysql'),
            ),
            array('id' => $id),
            array('%s', '%s'),
            array('%d')
        );
        
        if ($result === false) {
            wp_send_json_error(array('message' => __('Datenbankfehler.', 'ensemble')));
        }
        
        // Send status update email if approved
        if ($status === self::STATUS_APPROVED) {
            $this->send_approval_email($id);
        }
        
        wp_send_json_success(array(
            'message' => __('Status aktualisiert.', 'ensemble'),
            'status' => $status,
            'label' => $this->get_status_label($status),
        ));
    }
    
    /**
     * Send approval email
     */
    private function send_approval_email($authorization_id) {
        $auth = $this->get_authorization($authorization_id);
        if (!$auth) return false;
        
        $event = get_post($auth->event_id);
        $event_date = get_post_meta($auth->event_id, '_event_start_date', true);
        if (empty($event_date)) {
            $event_date = get_post_meta($auth->event_id, 'es_event_start_date', true);
        }
        
        // PDF Download URL with permanent token
        $pdf_url = add_query_arg(array(
            'action' => 'es_download_u18_pdf',
            'code' => $auth->authorization_code,
            'token' => $this->generate_pdf_token($auth->authorization_code),
        ), admin_url('admin-ajax.php'));
        
        // QR Code Check-in URL
        $checkin_url = add_query_arg(array(
            'u18_check' => $auth->authorization_code,
        ), home_url('/'));
        
        // QR Code Image URL (using qrserver.com - free and reliable)
        $qr_code_url = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&format=png&data=' . urlencode($checkin_url);
        
        $subject = sprintf(__('Aufsichtsübertragung für %s - GENEHMIGT ✓', 'ensemble'), $event->post_title);
        
        // Use template if available
        $message = $this->get_email_template('approved', array(
            'authorization' => $auth,
            'event' => $event,
            'event_date' => $event_date,
            'pdf_url' => $pdf_url,
            'qr_code_url' => $qr_code_url,
            'checkin_url' => $checkin_url,
            'site_name' => get_bloginfo('name'),
            'site_url' => home_url(),
        ));
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        wp_mail($auth->parent_email, $subject, $message, $headers);
        
        return true;
    }
    
    /**
     * AJAX: Check-in
     */
    public function ajax_checkin() {
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Keine Berechtigung.', 'ensemble')));
        }
        
        $code = sanitize_text_field($_REQUEST['code'] ?? '');
        
        if (empty($code)) {
            wp_send_json_error(array('message' => __('Kein Code angegeben.', 'ensemble')));
        }
        
        $auth = $this->get_authorization_by_code($code);
        
        if (!$auth) {
            wp_send_json_error(array('message' => __('Code nicht gefunden.', 'ensemble')));
        }
        
        if ($auth->status === self::STATUS_REJECTED) {
            wp_send_json_error(array('message' => __('Dieser Antrag wurde abgelehnt.', 'ensemble')));
        }
        
        if ($auth->status === self::STATUS_USED) {
            wp_send_json_error(array('message' => __('Bereits eingecheckt!', 'ensemble')));
        }
        
        if ($auth->status !== self::STATUS_APPROVED) {
            wp_send_json_error(array('message' => __('Antrag noch nicht genehmigt.', 'ensemble')));
        }
        
        global $wpdb;
        $wpdb->update(
            $this->table_name,
            array(
                'status' => self::STATUS_USED,
                'checked_in_at' => current_time('mysql'),
                'checked_in_by' => get_current_user_id(),
                'updated_at' => current_time('mysql'),
            ),
            array('id' => $auth->id)
        );
        
        wp_send_json_success(array(
            'message' => __('Check-in erfolgreich!', 'ensemble'),
            'minor' => $auth->minor_firstname . ' ' . $auth->minor_lastname,
            'companion' => $auth->companion_firstname . ' ' . $auth->companion_lastname,
            'minor_birthdate' => date_i18n('d.m.Y', strtotime($auth->minor_birthdate)),
        ));
    }
    
    /**
     * Handle QR check-in from URL
     */
    public function handle_qr_checkin() {
        if (empty($_GET['u18_check'])) {
            return;
        }
        
        $code = sanitize_text_field($_GET['u18_check']);
        $auth = $this->get_authorization_by_code($code);
        
        // If admin user, redirect to admin page
        if (current_user_can('edit_posts')) {
            wp_redirect(admin_url('admin.php?page=ensemble-reservations&tab=u18&checkin=' . $code));
            exit;
        }
        
        // For public, show status page
        include dirname(__FILE__) . '/templates/u18-status-page.php';
        exit;
    }
    
    /**
     * AJAX: Download PDF
     */
    public function ajax_download_pdf() {
        $code = sanitize_text_field($_GET['code'] ?? '');
        $token = sanitize_text_field($_GET['token'] ?? '');
        
        // Support both old nonce and new token method
        $nonce = $_GET['nonce'] ?? '';
        $valid_nonce = $nonce && wp_verify_nonce($nonce, 'u18_pdf_' . $code);
        $valid_token = $token && $this->verify_pdf_token($code, $token);
        
        // Admin users can always download
        $is_admin = current_user_can('manage_options');
        
        if (!$valid_nonce && !$valid_token && !$is_admin) {
            wp_die(__('Ungültiger oder abgelaufener Link. Bitte fordern Sie einen neuen Link an.', 'ensemble'));
        }
        
        $auth = $this->get_authorization_by_code($code);
        
        if (!$auth) {
            wp_die(__('Antrag nicht gefunden.', 'ensemble'));
        }
        
        // Generate PDF
        $this->generate_pdf($auth);
    }
    
    /**
     * Generate permanent PDF token
     */
    public function generate_pdf_token($code) {
        return hash('sha256', $code . AUTH_SALT . 'u18_pdf_permanent');
    }
    
    /**
     * Verify PDF token
     */
    private function verify_pdf_token($code, $token) {
        return hash_equals($this->generate_pdf_token($code), $token);
    }
    
    /**
     * AJAX: View uploaded ID image
     */
    public function ajax_view_id_upload() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Keine Berechtigung.', 'ensemble')));
        }
        
        $id = absint($_GET['id'] ?? 0);
        $nonce = $_GET['nonce'] ?? '';
        
        if (!wp_verify_nonce($nonce, 'ensemble_u18_admin')) {
            wp_send_json_error(array('message' => __('Ungültiger Nonce.', 'ensemble')));
        }
        
        $auth = $this->get_authorization($id);
        
        if (!$auth || empty($auth->id_upload_path)) {
            wp_send_json_error(array('message' => __('Keine Datei gefunden.', 'ensemble')));
        }
        
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['basedir'] . $auth->id_upload_path;
        
        if (!file_exists($file_path)) {
            wp_send_json_error(array('message' => __('Datei nicht gefunden.', 'ensemble')));
        }
        
        // Get mime type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file_path);
        finfo_close($finfo);
        
        // Output file
        header('Content-Type: ' . $mime_type);
        header('Content-Length: ' . filesize($file_path));
        header('Content-Disposition: inline; filename="ausweis-' . $auth->authorization_code . '"');
        readfile($file_path);
        exit;
    }
    
    /**
     * Generate PDF document
     */
    public function generate_pdf($auth) {
        // Check if TCPDF is available, otherwise use simple HTML-to-PDF
        if (!class_exists('TCPDF')) {
            // Try to include TCPDF from common locations
            $tcpdf_paths = array(
                WP_PLUGIN_DIR . '/tcpdf/tcpdf.php',
                ABSPATH . 'vendor/tecnickcom/tcpdf/tcpdf.php',
                dirname(__FILE__) . '/lib/tcpdf/tcpdf.php',
            );
            
            foreach ($tcpdf_paths as $path) {
                if (file_exists($path)) {
                    require_once $path;
                    break;
                }
            }
        }
        
        $event = get_post($auth->event_id);
        $event_date = get_post_meta($auth->event_id, '_event_start_date', true);
        if (empty($event_date)) {
            $event_date = get_post_meta($auth->event_id, 'es_event_start_date', true);
        }
        
        $location = '';
        $location_id = get_post_meta($auth->event_id, '_event_location', true);
        if ($location_id) {
            $location_post = get_post($location_id);
            if ($location_post) {
                $location = $location_post->post_title;
            }
        }
        
        if (class_exists('TCPDF')) {
            $this->generate_pdf_tcpdf($auth, $event, $event_date, $location);
        } else {
            $this->generate_pdf_html($auth, $event, $event_date, $location);
        }
    }
    
    /**
     * Generate PDF using HTML fallback
     */
    private function generate_pdf_html($auth, $event, $event_date, $location) {
        // Send as HTML that can be printed/saved as PDF
        header('Content-Type: text/html; charset=utf-8');
        
        include dirname(__FILE__) . '/templates/u18-pdf-html.php';
        exit;
    }
    
    /**
     * Generate PDF using TCPDF
     */
    private function generate_pdf_tcpdf($auth, $event, $event_date, $location) {
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        
        $pdf->SetCreator('Ensemble');
        $pdf->SetAuthor('Ensemble Event Management');
        $pdf->SetTitle(__('Aufsichtsübertragung', 'ensemble'));
        
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        $pdf->AddPage();
        
        // Include template
        ob_start();
        include dirname(__FILE__) . '/templates/u18-pdf-content.php';
        $html = ob_get_clean();
        
        $pdf->writeHTML($html, true, false, true, false, '');
        
        $filename = 'aufsichtsuebertragung-' . $auth->authorization_code . '.pdf';
        $pdf->Output($filename, 'D');
        exit;
    }
    
    /**
     * Shortcode: U18 Form
     */
    public function shortcode_form($atts) {
        $atts = shortcode_atts(array(
            'event' => 0,
        ), $atts, 'ensemble_u18_form');
        
        $event_id = $atts['event'] ?: get_the_ID();
        
        // Check if U18 is enabled for this event
        $u18_enabled = get_post_meta($event_id, '_u18_authorization_enabled', true);
        
        if (!$u18_enabled) {
            return '';
        }
        
        // Enqueue assets
        wp_enqueue_style('ensemble-u18', $this->get_addon_url() . 'assets/u18.css', array(), '1.0.0');
        wp_enqueue_script('ensemble-u18', $this->get_addon_url() . 'assets/u18.js', array('jquery'), '1.0.0', true);
        
        wp_localize_script('ensemble-u18', 'ensembleU18', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ensemble_u18'),
            'strings' => array(
                'submitting' => __('Wird gesendet...', 'ensemble'),
                'success' => __('Erfolgreich eingereicht!', 'ensemble'),
                'error' => __('Ein Fehler ist aufgetreten.', 'ensemble'),
                'required' => __('Bitte füllen Sie alle Pflichtfelder aus.', 'ensemble'),
                'invalidAge' => __('Ungültiges Alter.', 'ensemble'),
            ),
        ));
        
        ob_start();
        include dirname(__FILE__) . '/templates/u18-form.php';
        return ob_get_clean();
    }
    
    /**
     * Render form in reservation section
     */
    public function render_form_section($event_id) {
        $u18_enabled = get_post_meta($event_id, '_u18_authorization_enabled', true);
        
        if (!$u18_enabled) {
            return;
        }
        
        // Enqueue assets
        wp_enqueue_style('ensemble-u18', $this->get_addon_url() . 'assets/u18.css', array(), '1.0.0');
        wp_enqueue_script('ensemble-u18', $this->get_addon_url() . 'assets/u18.js', array('jquery'), '1.0.0', true);
        
        wp_localize_script('ensemble-u18', 'ensembleU18', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ensemble_u18'),
            'strings' => array(
                'submitting' => __('Wird gesendet...', 'ensemble'),
                'success' => __('Erfolgreich eingereicht!', 'ensemble'),
                'error' => __('Ein Fehler ist aufgetreten.', 'ensemble'),
                'required' => __('Bitte füllen Sie alle Pflichtfelder aus.', 'ensemble'),
                'invalidAge' => __('Ungültiges Alter.', 'ensemble'),
            ),
        ));
        
        include dirname(__FILE__) . '/templates/u18-form.php';
    }
    
    /**
     * Render admin tab content
     */
    public function render_admin_tab($event_id) {
        $authorizations = $this->get_event_authorizations($event_id);
        $stats = $this->get_stats($event_id);
        
        include dirname(__FILE__) . '/templates/u18-admin-tab.php';
    }
    
    /**
     * Get statistics for event
     */
    public function get_stats($event_id) {
        global $wpdb;
        
        $stats = array(
            'total' => 0,
            'submitted' => 0,
            'reviewed' => 0,
            'approved' => 0,
            'rejected' => 0,
            'used' => 0,
        );
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT status, COUNT(*) as count FROM {$this->table_name} 
             WHERE event_id = %d AND deleted_at IS NULL 
             GROUP BY status",
            $event_id
        ));
        
        foreach ($results as $row) {
            $stats[$row->status] = (int) $row->count;
            $stats['total'] += (int) $row->count;
        }
        
        return $stats;
    }
    
    /**
     * Get status label
     */
    public function get_status_label($status) {
        $labels = array(
            self::STATUS_SUBMITTED => __('Eingereicht', 'ensemble'),
            self::STATUS_REVIEWED => __('In Prüfung', 'ensemble'),
            self::STATUS_APPROVED => __('Genehmigt', 'ensemble'),
            self::STATUS_REJECTED => __('Abgelehnt', 'ensemble'),
            self::STATUS_USED => __('Eingecheckt', 'ensemble'),
            self::STATUS_EXPIRED => __('Abgelaufen', 'ensemble'),
        );
        
        return $labels[$status] ?? $status;
    }
    
    /**
     * Get status color
     */
    public function get_status_color($status) {
        $colors = array(
            self::STATUS_SUBMITTED => '#6c757d',
            self::STATUS_REVIEWED => '#ffc107',
            self::STATUS_APPROVED => '#28a745',
            self::STATUS_REJECTED => '#dc3545',
            self::STATUS_USED => '#17a2b8',
            self::STATUS_EXPIRED => '#6c757d',
        );
        
        return $colors[$status] ?? '#6c757d';
    }
    
    /**
     * DSGVO: Cleanup expired records
     */
    public function cleanup_expired() {
        global $wpdb;
        
        // Get retention days from settings (default: 7 days after event)
        $settings = get_option('ensemble_addon_reservations', array());
        $retention_days = $settings['u18_retention_days'] ?? 7;
        
        // Soft delete expired records
        $wpdb->query($wpdb->prepare(
            "UPDATE {$this->table_name} 
             SET deleted_at = %s, status = 'expired'
             WHERE expires_at < %s AND deleted_at IS NULL",
            current_time('mysql'),
            current_time('mysql')
        ));
        
        // Hard delete records older than 30 days after soft delete
        $hard_delete_after = 30;
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$this->table_name} 
             WHERE deleted_at IS NOT NULL 
             AND deleted_at < DATE_SUB(%s, INTERVAL %d DAY)",
            current_time('mysql'),
            $hard_delete_after
        ));
        
        // Delete associated ID uploads
        $this->cleanup_id_uploads();
    }
    
    /**
     * Cleanup orphaned ID uploads
     */
    private function cleanup_id_uploads() {
        global $wpdb;
        
        $upload_dir = wp_upload_dir();
        $u18_dir = $upload_dir['basedir'] . '/ensemble-u18';
        
        if (!is_dir($u18_dir)) {
            return;
        }
        
        // Get all valid paths from database
        $valid_paths = $wpdb->get_col(
            "SELECT id_upload_path FROM {$this->table_name} WHERE id_upload_path != ''"
        );
        
        // Iterate through upload directories
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($u18_dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $relative_path = str_replace($upload_dir['basedir'], '', $file->getPathname());
                
                // If file not in database, delete it
                if (!in_array($relative_path, $valid_paths)) {
                    // Only delete files older than 30 days
                    if (time() - $file->getMTime() > 30 * 24 * 60 * 60) {
                        @unlink($file->getPathname());
                    }
                }
            }
        }
    }
    
    /**
     * Register REST routes
     */
    public function register_rest_routes() {
        register_rest_route('ensemble/v1', '/u18/check/(?P<code>[A-Z0-9]+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'rest_check_authorization'),
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            },
        ));
        
        register_rest_route('ensemble/v1', '/u18/checkin/(?P<code>[A-Z0-9]+)', array(
            'methods' => 'POST',
            'callback' => array($this, 'rest_checkin'),
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            },
        ));
    }
    
    /**
     * REST: Check authorization
     */
    public function rest_check_authorization($request) {
        $code = $request->get_param('code');
        $auth = $this->get_authorization_by_code($code);
        
        if (!$auth) {
            return new WP_REST_Response(array('error' => 'not_found'), 404);
        }
        
        return new WP_REST_Response(array(
            'status' => $auth->status,
            'status_label' => $this->get_status_label($auth->status),
            'minor' => $auth->minor_firstname . ' ' . $auth->minor_lastname,
            'minor_birthdate' => $auth->minor_birthdate,
            'companion' => $auth->companion_firstname . ' ' . $auth->companion_lastname,
            'event_id' => $auth->event_id,
        ), 200);
    }
    
    /**
     * REST: Check-in
     */
    public function rest_checkin($request) {
        $_REQUEST['code'] = $request->get_param('code');
        
        ob_start();
        $this->ajax_checkin();
        $response = ob_get_clean();
        
        return new WP_REST_Response(json_decode($response), 200);
    }
    
    /**
     * AJAX: Delete authorization
     */
    public function ajax_delete() {
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Keine Berechtigung.', 'ensemble')));
        }
        
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'ensemble_u18_admin')) {
            wp_send_json_error(array('message' => __('Sicherheitsfehler.', 'ensemble')));
        }
        
        $id = absint($_POST['id'] ?? 0);
        
        global $wpdb;
        
        // Soft delete
        $result = $wpdb->update(
            $this->table_name,
            array(
                'deleted_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ),
            array('id' => $id)
        );
        
        if ($result === false) {
            wp_send_json_error(array('message' => __('Datenbankfehler.', 'ensemble')));
        }
        
        wp_send_json_success(array('message' => __('Gelöscht.', 'ensemble')));
    }
    
    /**
     * AJAX: Resend emails
     */
    public function ajax_resend_emails() {
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Keine Berechtigung.', 'ensemble')));
        }
        
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'ensemble_u18_admin')) {
            wp_send_json_error(array('message' => __('Sicherheitsfehler.', 'ensemble')));
        }
        
        $id = absint($_POST['id'] ?? 0);
        
        $result = $this->send_confirmation_emails($id);
        
        if ($result) {
            wp_send_json_success(array('message' => __('E-Mails erneut gesendet.', 'ensemble')));
        } else {
            wp_send_json_error(array('message' => __('Fehler beim Senden.', 'ensemble')));
        }
    }
    
    /**
     * AJAX: Get authorizations for event
     */
    public function ajax_get_authorizations() {
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Keine Berechtigung.', 'ensemble')));
        }
        
        $event_id = absint($_POST['event_id'] ?? 0);
        $status = sanitize_key($_POST['status'] ?? '');
        $search = sanitize_text_field($_POST['search'] ?? '');
        
        $authorizations = $this->get_event_authorizations($event_id, array(
            'status' => $status,
            'search' => $search,
        ));
        
        wp_send_json_success(array(
            'authorizations' => $authorizations,
            'stats' => $this->get_stats($event_id),
        ));
    }
    
    /**
     * AJAX: Validate step
     */
    public function ajax_validate_step() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'ensemble_u18')) {
            wp_send_json_error(array('message' => __('Sicherheitsfehler.', 'ensemble')));
        }
        
        $step = absint($_POST['step'] ?? 1);
        $data = $_POST['data'] ?? array();
        
        $errors = array();
        
        switch ($step) {
            case 1: // Parent data
                if (empty($data['parent_lastname'])) $errors['parent_lastname'] = __('Nachname erforderlich', 'ensemble');
                if (empty($data['parent_firstname'])) $errors['parent_firstname'] = __('Vorname erforderlich', 'ensemble');
                if (empty($data['parent_email']) || !is_email($data['parent_email'])) $errors['parent_email'] = __('Gültige E-Mail erforderlich', 'ensemble');
                if (empty($data['parent_phone'])) $errors['parent_phone'] = __('Telefon erforderlich', 'ensemble');
                break;
                
            case 2: // Minor data
                if (empty($data['minor_lastname'])) $errors['minor_lastname'] = __('Nachname erforderlich', 'ensemble');
                if (empty($data['minor_firstname'])) $errors['minor_firstname'] = __('Vorname erforderlich', 'ensemble');
                if (empty($data['minor_birthdate'])) {
                    $errors['minor_birthdate'] = __('Geburtsdatum erforderlich', 'ensemble');
                } else {
                    $age = $this->calculate_age($data['minor_birthdate']);
                    if ($age < 16 || $age >= 18) {
                        $errors['minor_birthdate'] = __('Person muss zwischen 16 und 17 Jahre alt sein', 'ensemble');
                    }
                }
                break;
                
            case 3: // Companion data
                if (empty($data['companion_lastname'])) $errors['companion_lastname'] = __('Nachname erforderlich', 'ensemble');
                if (empty($data['companion_firstname'])) $errors['companion_firstname'] = __('Vorname erforderlich', 'ensemble');
                if (empty($data['companion_birthdate'])) {
                    $errors['companion_birthdate'] = __('Geburtsdatum erforderlich', 'ensemble');
                } else {
                    $age = $this->calculate_age($data['companion_birthdate']);
                    if ($age < 18) {
                        $errors['companion_birthdate'] = __('Begleitperson muss mindestens 18 Jahre alt sein', 'ensemble');
                    }
                }
                break;
        }
        
        if (!empty($errors)) {
            wp_send_json_error(array('errors' => $errors));
        }
        
        wp_send_json_success(array('message' => 'OK'));
    }
}
