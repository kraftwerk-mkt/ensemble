<?php
/**
 * Ensemble License Manager
 * 
 * Handles Pro license validation and feature gating
 *
 * @package Ensemble
 * @since 2.8.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class ES_License_Manager {
    
    /**
     * Instance
     */
    private static $instance = null;
    
    /**
     * License status cache
     */
    private $is_pro = null;
    
    /**
     * License data
     */
    private $license_data = null;
    
    /**
     * Pro features list
     */
    private static $pro_features = array(
        'recurring_events',
        'import_export',
        'ical_import',
        'field_builder_templates',
        'field_builder_unlimited',
    );
    
    /**
     * Pro layouts list
     */
    private static $pro_layouts = array(
        'minimal',
        'magazine', 
        'compact',
    );
    
    /**
     * Pro addons list
     */
    private static $pro_addons = array(
        'related-events',
        'maps-pro',
        'gallery-pro',
        'ticketing',
        'reservations',
    );
    
    /**
     * Free limits
     */
    const FREE_MAX_FIELDSETS = 3;
    const FREE_MAX_FIELDS_PER_SET = 10;
    
    /**
     * Get instance
     */
    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->load_license_data();
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Add license settings page
        add_action('admin_init', array($this, 'register_settings'));
        
        // AJAX handlers
        add_action('wp_ajax_ensemble_activate_license', array($this, 'ajax_activate_license'));
        add_action('wp_ajax_ensemble_deactivate_license', array($this, 'ajax_deactivate_license'));
    }
    
    /**
     * Load license data from options
     */
    private function load_license_data() {
        $this->license_data = get_option('ensemble_license', array(
            'key' => '',
            'status' => 'inactive',
            'expires' => '',
            'customer_email' => '',
            'customer_name' => '',
        ));
    }
    
    /**
     * Check if Pro is active
     * 
     * @return bool
     */
    public function is_pro() {
        if ($this->is_pro !== null) {
            return $this->is_pro;
        }
        
        // Development mode - set ENSEMBLE_PRO_DEV in wp-config.php
        if (defined('ENSEMBLE_PRO_DEV') && ENSEMBLE_PRO_DEV === true) {
            $this->is_pro = true;
            return true;
        }
        
        // Check license status
        $this->is_pro = ($this->license_data['status'] === 'active');
        
        // Check expiration
        if ($this->is_pro && !empty($this->license_data['expires'])) {
            if ($this->license_data['expires'] !== 'lifetime') {
                $expires = strtotime($this->license_data['expires']);
                if ($expires && $expires < time()) {
                    $this->is_pro = false;
                }
            }
        }
        
        // Allow filter for development/testing
        $this->is_pro = apply_filters('ensemble_is_pro', $this->is_pro);
        
        return $this->is_pro;
    }
    
    /**
     * Check if a specific feature is available
     * 
     * @param string $feature Feature slug
     * @return bool
     */
    public function has_feature($feature) {
        // If it's a pro feature, check license
        if (in_array($feature, self::$pro_features)) {
            return $this->is_pro();
        }
        
        // Free feature
        return true;
    }
    
    /**
     * Check if a layout is available
     * 
     * @param string $layout Layout slug
     * @return bool
     */
    public function has_layout($layout) {
        if (in_array($layout, self::$pro_layouts)) {
            return $this->is_pro();
        }
        return true;
    }
    
    /**
     * Check if an addon is available
     * 
     * @param string $addon Addon slug
     * @return bool
     */
    public function has_addon($addon) {
        if (in_array($addon, self::$pro_addons)) {
            return $this->is_pro();
        }
        return true;
    }
    
    /**
     * Get license data
     * 
     * @return array
     */
    public function get_license_data() {
        return $this->license_data;
    }
    
    /**
     * Get license key (masked)
     * 
     * @return string
     */
    public function get_masked_key() {
        $key = $this->license_data['key'];
        if (empty($key)) {
            return '';
        }
        
        // Show first 4 and last 4 characters
        if (strlen($key) > 12) {
            return substr($key, 0, 4) . str_repeat('•', strlen($key) - 8) . substr($key, -4);
        }
        
        return str_repeat('•', strlen($key));
    }
    
    /**
     * Get pro features list
     * 
     * @return array
     */
    public static function get_pro_features() {
        return self::$pro_features;
    }
    
    /**
     * Get pro layouts list
     * 
     * @return array
     */
    public static function get_pro_layouts() {
        return self::$pro_layouts;
    }
    
    /**
     * Get pro addons list
     * 
     * @return array
     */
    public static function get_pro_addons() {
        return self::$pro_addons;
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('ensemble_license', 'ensemble_license', array(
            'sanitize_callback' => array($this, 'sanitize_license'),
        ));
    }
    
    /**
     * Sanitize license data
     */
    public function sanitize_license($input) {
        $sanitized = array(
            'key' => sanitize_text_field($input['key'] ?? ''),
            'status' => sanitize_key($input['status'] ?? 'inactive'),
            'expires' => sanitize_text_field($input['expires'] ?? ''),
            'customer_email' => sanitize_email($input['customer_email'] ?? ''),
            'customer_name' => sanitize_text_field($input['customer_name'] ?? ''),
        );
        
        return $sanitized;
    }
    
    /**
     * Activate license via AJAX
     */
    public function ajax_activate_license() {
        check_ajax_referer('ensemble_license_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'ensemble')));
        }
        
        $license_key = sanitize_text_field($_POST['license_key'] ?? '');
        
        if (empty($license_key)) {
            wp_send_json_error(array('message' => __('Please enter a license key.', 'ensemble')));
        }
        
        // Validate license key format (basic check)
        if (strlen($license_key) < 16) {
            wp_send_json_error(array('message' => __('Invalid license key format.', 'ensemble')));
        }
        
        // TODO: In production, validate against license server
        // For now, accept any valid-looking key for testing
        // In real implementation: $response = $this->validate_license_remote($license_key);
        
        // Simulate successful activation
        $license_data = array(
            'key' => $license_key,
            'status' => 'active',
            'expires' => 'lifetime', // or date like '2025-12-31'
            'customer_email' => get_option('admin_email'),
            'customer_name' => wp_get_current_user()->display_name,
        );
        
        update_option('ensemble_license', $license_data);
        $this->license_data = $license_data;
        $this->is_pro = true;
        
        wp_send_json_success(array(
            'message' => __('License successfully activated!', 'ensemble'),
            'license' => array(
                'status' => 'active',
                'expires' => $license_data['expires'],
                'masked_key' => $this->get_masked_key(),
            ),
        ));
    }
    
    /**
     * Deactivate license via AJAX
     */
    public function ajax_deactivate_license() {
        check_ajax_referer('ensemble_license_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'ensemble')));
        }
        
        // TODO: In production, deactivate on license server
        
        // Clear license data
        $license_data = array(
            'key' => '',
            'status' => 'inactive',
            'expires' => '',
            'customer_email' => '',
            'customer_name' => '',
        );
        
        update_option('ensemble_license', $license_data);
        $this->license_data = $license_data;
        $this->is_pro = false;
        
        wp_send_json_success(array(
            'message' => __('License deactivated.', 'ensemble'),
        ));
    }
    
    /**
     * Render license settings section
     */
    public function render_license_settings() {
        $is_pro = $this->is_pro();
        $license = $this->license_data;
        ?>
        <div class="es-license-section">
            <div class="es-license-status <?php echo $is_pro ? 'es-license-active' : 'es-license-inactive'; ?>">
                <div class="es-license-badge">
                    <?php if ($is_pro): ?>
                        <span class="dashicons dashicons-yes-alt"></span>
                        <span class="es-license-badge-text"><?php _e('Pro Aktiv', 'ensemble'); ?></span>
                    <?php else: ?>
                        <span class="dashicons dashicons-lock"></span>
                        <span class="es-license-badge-text"><?php _e('Free Version', 'ensemble'); ?></span>
                    <?php endif; ?>
                </div>
                
                <?php if ($is_pro && !empty($license['expires']) && $license['expires'] !== 'lifetime'): ?>
                    <div class="es-license-expires">
                        <?php printf(__('Valid until: %s', 'ensemble'), date_i18n(get_option('date_format'), strtotime($license['expires']))); ?>
                    </div>
                <?php elseif ($is_pro && $license['expires'] === 'lifetime'): ?>
                    <div class="es-license-expires es-license-lifetime">
                        <?php _e('Lifetime Lizenz', 'ensemble'); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="es-license-form">
                <?php if ($is_pro): ?>
                    <div class="es-license-key-display">
                        <span class="es-license-key-label"><?php _e('License Key:', 'ensemble'); ?></span>
                        <code class="es-license-key-masked"><?php echo esc_html($this->get_masked_key()); ?></code>
                    </div>
                    <button type="button" class="button es-license-deactivate" id="es-deactivate-license">
                        <?php _e('Lizenz deaktivieren', 'ensemble'); ?>
                    </button>
                <?php else: ?>
                    <div class="es-license-input-wrap">
                        <input type="text" 
                               id="es-license-key" 
                               class="es-license-input" 
                               placeholder="<?php esc_attr_e('Enter license key...', 'ensemble'); ?>"
                               autocomplete="off">
                        <button type="button" class="button button-primary es-license-activate" id="es-activate-license">
                            <?php _e('Aktivieren', 'ensemble'); ?>
                        </button>
                    </div>
                    <p class="es-license-help">
                        <?php _e('Enter your license key to unlock Pro features.', 'ensemble'); ?>
                        <a href="https://kraftwerk-mkt.com/ensemble-pro" target="_blank"><?php _e('Pro kaufen →', 'ensemble'); ?></a>
                    </p>
                <?php endif; ?>
            </div>
            
            <div class="es-license-message" id="es-license-message" style="display: none;"></div>
        </div>
        
        <?php wp_nonce_field('ensemble_license_nonce', 'ensemble_license_nonce'); ?>
        
        <script>
        jQuery(document).ready(function($) {
            // Activate license
            $('#es-activate-license').on('click', function() {
                var $btn = $(this);
                var key = $('#es-license-key').val().trim();
                
                if (!key) {
                    showMessage('error', '<?php _e('Please enter a license key.', 'ensemble'); ?>');
                    return;
                }
                
                $btn.prop('disabled', true).text('<?php _e('Aktiviere...', 'ensemble'); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ensemble_activate_license',
                        nonce: $('#ensemble_license_nonce').val(),
                        license_key: key
                    },
                    success: function(response) {
                        if (response.success) {
                            showMessage('success', response.data.message);
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        } else {
                            showMessage('error', response.data.message);
                            $btn.prop('disabled', false).text('<?php _e('Aktivieren', 'ensemble'); ?>');
                        }
                    },
                    error: function() {
                        showMessage('error', '<?php _e('Verbindungsfehler. Bitte versuche es erneut.', 'ensemble'); ?>');
                        $btn.prop('disabled', false).text('<?php _e('Aktivieren', 'ensemble'); ?>');
                    }
                });
            });
            
            // Deactivate license
            $('#es-deactivate-license').on('click', function() {
                if (!confirm('<?php _e('Lizenz wirklich deaktivieren?', 'ensemble'); ?>')) {
                    return;
                }
                
                var $btn = $(this);
                $btn.prop('disabled', true).text('<?php _e('Deaktiviere...', 'ensemble'); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ensemble_deactivate_license',
                        nonce: $('#ensemble_license_nonce').val()
                    },
                    success: function(response) {
                        if (response.success) {
                            showMessage('success', response.data.message);
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        } else {
                            showMessage('error', response.data.message);
                            $btn.prop('disabled', false).text('<?php _e('Lizenz deaktivieren', 'ensemble'); ?>');
                        }
                    }
                });
            });
            
            function showMessage(type, message) {
                var $msg = $('#es-license-message');
                $msg.removeClass('es-message-success es-message-error')
                    .addClass('es-message-' + type)
                    .text(message)
                    .fadeIn();
            }
        });
        </script>
        <?php
    }
}

/**
 * Global helper function to check Pro status
 * 
 * @return bool
 */
function ensemble_is_pro() {
    return ES_License_Manager::instance()->is_pro();
}

/**
 * Global helper to check feature availability
 * 
 * @param string $feature
 * @return bool
 */
function ensemble_has_feature($feature) {
    return ES_License_Manager::instance()->has_feature($feature);
}

/**
 * Global helper to check layout availability
 * 
 * @param string $layout
 * @return bool
 */
function ensemble_has_layout($layout) {
    return ES_License_Manager::instance()->has_layout($layout);
}

/**
 * Global helper to check addon availability
 * 
 * @param string $addon
 * @return bool
 */
function ensemble_has_addon($addon) {
    return ES_License_Manager::instance()->has_addon($addon);
}

// Initialize
ES_License_Manager::instance();
