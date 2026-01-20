<?php
/**
 * Ensemble U18 Authorization Add-on (Muttizettel)
 * 
 * Digital parental consent form for minors (16-17 years) attending events
 * Based on German youth protection law § 1 Abs. 1 Nr. 4 JuSchG
 *
 * @package Ensemble
 * @subpackage Addons
 * @since 3.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class ES_U18_Addon extends ES_Addon_Base {
    
    /**
     * Add-on configuration
     */
    protected $slug = 'u18-authorization';
    protected $name = 'U18 Muttizettel';
    protected $version = '1.0.0';
    
    /**
     * U18 Authorization handler instance
     */
    private $u18_handler;
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * Get instance
     * 
     * @return ES_U18_Addon
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
        // Load the U18 authorization handler
        $this->load_dependencies();
        
        $this->log('U18 Muttizettel add-on initialized');
    }
    
    /**
     * Load dependencies
     */
    private function load_dependencies() {
        $addon_path = $this->get_addon_path();
        
        // Load the main authorization class
        if (file_exists($addon_path . 'class-es-u18-authorization.php')) {
            require_once $addon_path . 'class-es-u18-authorization.php';
            $this->u18_handler = new ES_U18_Authorization($this);
        }
    }
    
    /**
     * Register hooks
     */
    protected function register_hooks() {
        // Admin hooks
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Frontend hooks
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        
        // Meta box for events
        add_action('add_meta_boxes', array($this, 'add_event_meta_box'));
        add_action('save_post', array($this, 'save_event_meta'));
        
        // Wizard integration
        add_action('ensemble_wizard_after_save', array($this, 'save_wizard_meta'), 10, 2);
        
        // Template hook - render U18 section after tickets
        add_action('ensemble_after_tickets', array($this, 'render_u18_section'), 20, 1);
        
        // Booking Engine integration tab (if Booking Engine is active)
        add_filter('ensemble_booking_admin_tabs', array($this, 'add_booking_tab'), 10, 2);
    }
    
    /**
     * Get U18 handler instance
     * 
     * @return ES_U18_Authorization|null
     */
    public function get_handler() {
        return $this->u18_handler;
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only load on relevant pages
        $screen = get_current_screen();
        if (!$screen) {
            return;
        }
        
        $post_type = ensemble_get_post_type();
        $is_event_page = $screen->post_type === $post_type;
        $is_addon_page = strpos($hook, 'ensemble') !== false;
        
        if (!$is_event_page && !$is_addon_page) {
            return;
        }
        
        wp_enqueue_style(
            'ensemble-u18-admin',
            $this->get_addon_url() . 'assets/u18-admin.css',
            array(),
            $this->version
        );
        
        wp_enqueue_script(
            'ensemble-u18-admin',
            $this->get_addon_url() . 'assets/u18-admin.js',
            array('jquery'),
            $this->version,
            true
        );
        
        wp_localize_script('ensemble-u18-admin', 'esU18Admin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('ensemble_u18_admin'),
            'i18n'    => array(
                'confirm_delete' => __('Are you sure you want to delete this authorization?', 'ensemble'),
                'confirm_approve' => __('Approve this U18 authorization?', 'ensemble'),
                'confirm_reject' => __('Reject this U18 authorization?', 'ensemble'),
                'processing' => __('Processing...', 'ensemble'),
                'error' => __('An error occurred. Please try again.', 'ensemble'),
            ),
        ));
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        if (!is_singular(ensemble_get_post_type())) {
            return;
        }
        
        $post_id = get_the_ID();
        $u18_enabled = get_post_meta($post_id, '_u18_enabled', true);
        
        if (!$u18_enabled) {
            return;
        }
        
        // Signature Pad library
        wp_enqueue_script(
            'signature-pad',
            'https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js',
            array(),
            '4.0.0',
            true
        );
        
        wp_enqueue_style(
            'ensemble-u18',
            $this->get_addon_url() . 'assets/u18.css',
            array(),
            $this->version
        );
        
        wp_enqueue_script(
            'ensemble-u18',
            $this->get_addon_url() . 'assets/u18.js',
            array('jquery', 'signature-pad'),
            $this->version,
            true
        );
        
        wp_localize_script('ensemble-u18', 'esU18', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('ensemble_u18'),
            'i18n'    => array(
                'step' => __('Step', 'ensemble'),
                'of' => __('of', 'ensemble'),
                'next' => __('Next', 'ensemble'),
                'back' => __('Back', 'ensemble'),
                'submit' => __('Submit', 'ensemble'),
                'required' => __('This field is required', 'ensemble'),
                'invalid_email' => __('Please enter a valid email address', 'ensemble'),
                'invalid_phone' => __('Please enter a valid phone number', 'ensemble'),
                'age_error_minor' => __('Person must be between 16 and 17 years old', 'ensemble'),
                'age_error_adult' => __('Accompanying person must be at least 18 years old', 'ensemble'),
                'signature_required' => __('Please sign in the signature field', 'ensemble'),
                'clear_signature' => __('Clear', 'ensemble'),
            ),
        ));
    }
    
    /**
     * Add event meta box
     */
    public function add_event_meta_box() {
        $post_type = ensemble_get_post_type();
        
        add_meta_box(
            'ensemble_u18_settings',
            __('U18 Authorization (Muttizettel)', 'ensemble'),
            array($this, 'render_meta_box'),
            $post_type,
            'side',
            'default'
        );
    }
    
    /**
     * Render meta box
     */
    public function render_meta_box($post) {
        wp_nonce_field('ensemble_u18_meta', 'ensemble_u18_nonce');
        
        $u18_enabled = get_post_meta($post->ID, '_u18_enabled', true);
        $require_id = get_post_meta($post->ID, '_u18_require_id', true);
        $auto_approve = get_post_meta($post->ID, '_u18_auto_approve', true);
        ?>
        <div class="es-u18-metabox">
            <p>
                <label>
                    <input type="checkbox" name="_u18_enabled" value="1" <?php checked($u18_enabled, '1'); ?>>
                    <?php _e('Enable U18 Authorization', 'ensemble'); ?>
                </label>
            </p>
            
            <div class="es-u18-options" style="<?php echo $u18_enabled ? '' : 'display:none;'; ?>">
                <p>
                    <label>
                        <input type="checkbox" name="_u18_require_id" value="1" <?php checked($require_id, '1'); ?>>
                        <?php _e('Require ID Upload', 'ensemble'); ?>
                    </label>
                </p>
                
                <p>
                    <label>
                        <input type="checkbox" name="_u18_auto_approve" value="1" <?php checked($auto_approve, '1'); ?>>
                        <?php _e('Auto-approve submissions', 'ensemble'); ?>
                    </label>
                </p>
            </div>
            
            <?php if ($this->u18_handler): ?>
            <div class="es-u18-stats">
                <?php 
                $stats = $this->u18_handler->get_stats($post->ID);
                if ($stats && $stats['total'] > 0):
                ?>
                <p class="description">
                    <strong><?php echo esc_html($stats['total']); ?></strong> <?php _e('submissions', 'ensemble'); ?>
                    (<?php echo esc_html($stats['approved']); ?> <?php _e('approved', 'ensemble'); ?>)
                </p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <script>
        jQuery(function($) {
            $('input[name="_u18_enabled"]').on('change', function() {
                $('.es-u18-options').toggle(this.checked);
            });
        });
        </script>
        <?php
    }
    
    /**
     * Save event meta
     */
    public function save_event_meta($post_id) {
        if (!isset($_POST['ensemble_u18_nonce'])) {
            return;
        }
        
        if (!wp_verify_nonce($_POST['ensemble_u18_nonce'], 'ensemble_u18_meta')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        $u18_enabled = isset($_POST['_u18_enabled']) ? '1' : '';
        $require_id = isset($_POST['_u18_require_id']) ? '1' : '';
        $auto_approve = isset($_POST['_u18_auto_approve']) ? '1' : '';
        
        update_post_meta($post_id, '_u18_enabled', $u18_enabled);
        update_post_meta($post_id, '_u18_require_id', $require_id);
        update_post_meta($post_id, '_u18_auto_approve', $auto_approve);
    }
    
    /**
     * Save wizard meta
     */
    public function save_wizard_meta($post_id, $data) {
        if (isset($data['u18_enabled'])) {
            update_post_meta($post_id, '_u18_enabled', $data['u18_enabled'] ? '1' : '');
        }
        if (isset($data['u18_require_id'])) {
            update_post_meta($post_id, '_u18_require_id', $data['u18_require_id'] ? '1' : '');
        }
        if (isset($data['u18_auto_approve'])) {
            update_post_meta($post_id, '_u18_auto_approve', $data['u18_auto_approve'] ? '1' : '');
        }
    }
    
    /**
     * Render U18 section on event pages
     */
    public function render_u18_section($post_id) {
        if (!$post_id) {
            $post_id = get_the_ID();
        }
        
        $u18_enabled = get_post_meta($post_id, '_u18_enabled', true);
        
        if (!$u18_enabled) {
            return;
        }
        
        // Load the form template
        $template = $this->get_addon_path() . 'templates/u18-form.php';
        
        if (file_exists($template)) {
            include $template;
        }
    }
    
    /**
     * Add tab to Booking Engine admin
     */
    public function add_booking_tab($tabs, $event_id) {
        $u18_enabled = get_post_meta($event_id, '_u18_enabled', true);
        
        if ($u18_enabled) {
            $tabs['u18'] = array(
                'label' => __('U18 Authorizations', 'ensemble'),
                'icon'  => 'dashicons-id-alt',
                'callback' => array($this, 'render_admin_tab'),
            );
        }
        
        return $tabs;
    }
    
    /**
     * Render admin tab content
     */
    public function render_admin_tab($event_id) {
        if (!$this->u18_handler) {
            echo '<p>' . __('U18 handler not initialized.', 'ensemble') . '</p>';
            return;
        }
        
        $template = $this->get_addon_path() . 'templates/u18-admin-tab.php';
        
        if (file_exists($template)) {
            $authorizations = $this->u18_handler->get_event_authorizations($event_id);
            $stats = $this->u18_handler->get_stats($event_id);
            
            include $template;
        }
    }
    
    /**
     * Render addon settings
     */
    public function render_settings() {
        $settings = $this->get_settings();
        ?>
        <div class="es-settings-section">
            <h3><?php _e('Default Settings', 'ensemble'); ?></h3>
            
            <div class="es-settings-row">
                <label for="es_u18_default_require_id">
                    <input type="checkbox" id="es_u18_default_require_id" name="default_require_id" value="1" <?php checked($settings['default_require_id'] ?? '', '1'); ?>>
                    <?php _e('Require ID upload by default', 'ensemble'); ?>
                </label>
            </div>
            
            <div class="es-settings-row">
                <label for="es_u18_default_auto_approve">
                    <input type="checkbox" id="es_u18_default_auto_approve" name="default_auto_approve" value="1" <?php checked($settings['default_auto_approve'] ?? '', '1'); ?>>
                    <?php _e('Auto-approve by default', 'ensemble'); ?>
                </label>
            </div>
        </div>
        
        <div class="es-settings-section">
            <h3><?php _e('Data Retention', 'ensemble'); ?></h3>
            
            <div class="es-settings-row">
                <label for="es_u18_retention_days">
                    <?php _e('Delete data after (days)', 'ensemble'); ?>
                </label>
                <input type="number" id="es_u18_retention_days" name="retention_days" 
                       value="<?php echo esc_attr($settings['retention_days'] ?? 30); ?>" 
                       min="7" max="365" class="small-text">
                <p class="description"><?php _e('U18 data will be automatically deleted this many days after the event.', 'ensemble'); ?></p>
            </div>
        </div>
        
        <div class="es-settings-section">
            <h3><?php _e('Notification Emails', 'ensemble'); ?></h3>
            
            <div class="es-settings-row">
                <label for="es_u18_admin_email">
                    <?php _e('Admin notification email', 'ensemble'); ?>
                </label>
                <input type="email" id="es_u18_admin_email" name="admin_email" 
                       value="<?php echo esc_attr($settings['admin_email'] ?? get_option('admin_email')); ?>" 
                       class="regular-text">
                <p class="description"><?php _e('Receives notifications for new U18 submissions.', 'ensemble'); ?></p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Sanitize settings
     */
    public function sanitize_settings($input) {
        return array(
            'default_require_id'  => isset($input['default_require_id']) ? '1' : '',
            'default_auto_approve' => isset($input['default_auto_approve']) ? '1' : '',
            'retention_days'      => absint($input['retention_days'] ?? 30),
            'admin_email'         => sanitize_email($input['admin_email'] ?? ''),
        );
    }
}
