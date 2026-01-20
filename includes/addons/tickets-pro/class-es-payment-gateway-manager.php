<?php
/**
 * Payment Gateway Manager
 * 
 * Registry and manager for payment gateways.
 * Handles gateway registration, activation, and routing.
 *
 * @package Ensemble
 * @subpackage Addons/TicketsPro/Gateways
 * @since 3.1.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class ES_Payment_Gateway_Manager {
    
    /**
     * Singleton instance
     * @var ES_Payment_Gateway_Manager
     */
    private static $instance = null;
    
    /**
     * Registered gateways
     * @var array
     */
    private $gateways = array();
    
    /**
     * Gateway instances (lazy loaded)
     * @var array
     */
    private $gateway_instances = array();
    
    /**
     * Get singleton instance
     * 
     * @return ES_Payment_Gateway_Manager
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init();
    }
    
    /**
     * Initialize manager
     */
    private function init() {
        // Load gateway classes
        $this->load_gateways();
        
        // Check if init hook already fired (late initialization)
        if (did_action('init')) {
            // Init already happened, register immediately
            $this->register_builtin_gateways();
            $this->register_third_party_gateways();
        } else {
            // Register built-in gateways on init
            add_action('init', array($this, 'register_builtin_gateways'), 5);
            
            // Allow third-party gateways
            add_action('init', array($this, 'register_third_party_gateways'), 10);
        }
        
        // Handle webhooks
        add_action('init', array($this, 'handle_webhooks'), 20);
        
        // Handle return/cancel URLs
        add_action('template_redirect', array($this, 'handle_payment_return'));
        add_action('template_redirect', array($this, 'handle_payment_cancel'));
    }
    
    /**
     * Load gateway class files
     * 
     * Gateway files are in the gateways/ subdirectory
     */
    private function load_gateways() {
        // Gateway files are in gateways/ subdirectory
        $gateway_dir = plugin_dir_path(__FILE__) . 'gateways/';
        
        // Built-in gateways
        $builtin = array(
            'paypal'  => 'class-es-gateway-paypal.php',
            'stripe'  => 'class-es-gateway-stripe.php',
        );
        
        foreach ($builtin as $id => $file) {
            $file_path = $gateway_dir . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
    }
    
    /**
     * Register built-in gateways
     */
    public function register_builtin_gateways() {
        // PayPal
        if (class_exists('ES_Gateway_PayPal')) {
            $this->register_gateway('paypal', 'ES_Gateway_PayPal');
        }
        
        // Stripe
        if (class_exists('ES_Gateway_Stripe')) {
            $this->register_gateway('stripe', 'ES_Gateway_Stripe');
        }
    }
    
    /**
     * Register third-party gateways via filter
     */
    public function register_third_party_gateways() {
        /**
         * Filter to register custom payment gateways
         * 
         * @param array $gateways Array of gateway_id => class_name
         */
        $custom_gateways = apply_filters('ensemble_payment_gateways', array());
        
        foreach ($custom_gateways as $id => $class) {
            if (class_exists($class)) {
                $this->register_gateway($id, $class);
            }
        }
    }
    
    /**
     * Register a gateway
     * 
     * @param string $id    Gateway ID
     * @param string $class Gateway class name
     */
    public function register_gateway($id, $class) {
        $this->gateways[$id] = $class;
    }
    
    /**
     * Get a gateway instance
     * 
     * @param string $id Gateway ID
     * @return ES_Payment_Gateway|null
     */
    public function get_gateway($id) {
        if (!isset($this->gateways[$id])) {
            return null;
        }
        
        if (!isset($this->gateway_instances[$id])) {
            $class = $this->gateways[$id];
            $this->gateway_instances[$id] = new $class();
        }
        
        return $this->gateway_instances[$id];
    }
    
    /**
     * Get all registered gateways
     * 
     * @param bool $enabled_only Only return enabled gateways
     * @return ES_Payment_Gateway[]
     */
    public function get_gateways($enabled_only = false) {
        $result = array();
        
        foreach (array_keys($this->gateways) as $id) {
            $gateway = $this->get_gateway($id);
            
            if ($gateway && (!$enabled_only || $gateway->is_enabled())) {
                $result[$id] = $gateway;
            }
        }
        
        return $result;
    }
    
    /**
     * Get available gateways (enabled and configured)
     * 
     * @return ES_Payment_Gateway[]
     */
    public function get_available_gateways() {
        $result = array();
        
        foreach ($this->get_gateways(true) as $id => $gateway) {
            if ($gateway->is_available()) {
                $result[$id] = $gateway;
            }
        }
        
        return $result;
    }
    
    /**
     * Check if any gateway is available
     * 
     * @return bool
     */
    public function has_available_gateway() {
        return count($this->get_available_gateways()) > 0;
    }
    
    /**
     * Get default gateway
     * 
     * @return ES_Payment_Gateway|null
     */
    public function get_default_gateway() {
        $default_id = get_option('ensemble_default_payment_gateway', '');
        
        if ($default_id && isset($this->gateways[$default_id])) {
            $gateway = $this->get_gateway($default_id);
            if ($gateway && $gateway->is_available()) {
                return $gateway;
            }
        }
        
        // Fall back to first available gateway
        $available = $this->get_available_gateways();
        return !empty($available) ? reset($available) : null;
    }
    
    /**
     * Process a payment through a gateway
     * 
     * @param string $gateway_id Gateway ID
     * @param array  $args       Payment arguments
     * @return array|WP_Error
     */
    public function process_payment($gateway_id, $args) {
        $gateway = $this->get_gateway($gateway_id);
        
        if (!$gateway) {
            return new WP_Error('invalid_gateway', __('Invalid payment gateway.', 'ensemble'));
        }
        
        if (!$gateway->is_available()) {
            return new WP_Error('gateway_unavailable', __('This payment gateway is not available.', 'ensemble'));
        }
        
        // Log payment attempt
        $this->log_transaction($args['booking_id'], $gateway_id, 'payment_initiated', $args);
        
        // Process payment
        $result = $gateway->create_payment($args);
        
        if (is_wp_error($result)) {
            $this->log_transaction($args['booking_id'], $gateway_id, 'payment_failed', array(
                'error' => $result->get_error_message(),
            ));
        } else {
            $this->log_transaction($args['booking_id'], $gateway_id, 'payment_created', $result);
        }
        
        return $result;
    }
    
    /**
     * Process a refund through a gateway
     * 
     * @param string $gateway_id Gateway ID
     * @param string $payment_id Payment ID
     * @param float  $amount     Refund amount
     * @param string $reason     Refund reason
     * @return array|WP_Error
     */
    public function process_refund($gateway_id, $payment_id, $amount = null, $reason = '') {
        $gateway = $this->get_gateway($gateway_id);
        
        if (!$gateway) {
            return new WP_Error('invalid_gateway', __('Invalid payment gateway.', 'ensemble'));
        }
        
        if (!$gateway->supports('refunds')) {
            return new WP_Error('refunds_not_supported', __('This gateway does not support refunds.', 'ensemble'));
        }
        
        return $gateway->process_refund($payment_id, $amount, $reason);
    }
    
    /**
     * Handle webhook requests
     */
    public function handle_webhooks() {
        if (!isset($_GET['ensemble_payment_webhook'])) {
            return;
        }
        
        $gateway_id = sanitize_key($_GET['ensemble_payment_webhook']);
        $gateway = $this->get_gateway($gateway_id);
        
        if ($gateway) {
            $gateway->handle_webhook();
            exit;
        }
        
        wp_die('Invalid gateway', 'Webhook Error', array('response' => 400));
    }
    
    /**
     * Handle payment return (success)
     */
    public function handle_payment_return() {
        if (!isset($_GET['ensemble_payment_return'])) {
            return;
        }
        
        $booking_id = isset($_GET['booking_id']) ? absint($_GET['booking_id']) : 0;
        $gateway_id = isset($_GET['gateway']) ? sanitize_key($_GET['gateway']) : '';
        
        if (!$booking_id || !$gateway_id) {
            return;
        }
        
        /**
         * Action fired when payment return is processed
         */
        do_action('ensemble_payment_return', $booking_id, $gateway_id);
        
        // Redirect to confirmation
        $redirect_url = apply_filters('ensemble_payment_return_url', '', $booking_id, $gateway_id);
        
        if ($redirect_url) {
            wp_redirect($redirect_url);
            exit;
        }
    }
    
    /**
     * Handle payment cancel
     */
    public function handle_payment_cancel() {
        if (!isset($_GET['ensemble_payment_cancel'])) {
            return;
        }
        
        $booking_id = isset($_GET['booking_id']) ? absint($_GET['booking_id']) : 0;
        $gateway_id = isset($_GET['gateway']) ? sanitize_key($_GET['gateway']) : '';
        
        if (!$booking_id || !$gateway_id) {
            return;
        }
        
        /**
         * Action fired when payment is cancelled
         */
        do_action('ensemble_payment_cancelled', $booking_id, $gateway_id);
        
        // Redirect
        $redirect_url = apply_filters('ensemble_payment_cancel_url', '', $booking_id, $gateway_id);
        
        if ($redirect_url) {
            wp_redirect($redirect_url);
            exit;
        }
    }
    
    /**
     * Log a transaction event
     * 
     * @param int    $booking_id
     * @param string $gateway_id
     * @param string $event
     * @param array  $data
     */
    public function log_transaction($booking_id, $gateway_id, $event, $data = array()) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ensemble_payment_logs';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
            return;
        }
        
        $wpdb->insert($table, array(
            'booking_id'  => $booking_id,
            'gateway_id'  => $gateway_id,
            'event'       => $event,
            'data'        => wp_json_encode($data),
            'ip_address'  => $this->get_client_ip(),
            'user_agent'  => isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : '',
            'created_at'  => current_time('mysql'),
        ), array('%d', '%s', '%s', '%s', '%s', '%s', '%s'));
    }
    
    /**
     * Get client IP address
     * 
     * @return string
     */
    private function get_client_ip() {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        return sanitize_text_field($ip);
    }
}

/**
 * Get the payment gateway manager instance
 * 
 * @return ES_Payment_Gateway_Manager
 */
function ES_Payment_Gateways() {
    return ES_Payment_Gateway_Manager::instance();
}
