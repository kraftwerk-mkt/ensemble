<?php
/**
 * Abstract Payment Gateway
 * 
 * Base class for all payment gateway implementations.
 * Provides a unified interface for payment processing.
 *
 * @package Ensemble
 * @subpackage Addons/TicketsPro/Gateways
 * @since 3.1.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

abstract class ES_Payment_Gateway {
    
    /**
     * Gateway ID (unique identifier)
     * @var string
     */
    protected $id = '';
    
    /**
     * Gateway title (for display)
     * @var string
     */
    protected $title = '';
    
    /**
     * Gateway description
     * @var string
     */
    protected $description = '';
    
    /**
     * Gateway icon URL
     * @var string
     */
    protected $icon = '';
    
    /**
     * Whether this gateway is enabled
     * @var bool
     */
    protected $enabled = false;
    
    /**
     * Whether this gateway supports test mode
     * @var bool
     */
    protected $supports_test_mode = true;
    
    /**
     * Whether we're in test/sandbox mode
     * @var bool
     */
    protected $test_mode = false;
    
    /**
     * Gateway settings
     * @var array
     */
    protected $settings = array();
    
    /**
     * Supported features
     * @var array
     */
    protected $supports = array(
        'payments',
        'refunds',
    );
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->load_settings();
        $this->init();
    }
    
    /**
     * Initialize gateway (override in child class)
     */
    protected function init() {
        // Override in child class
    }
    
    /**
     * Load gateway settings from database
     */
    protected function load_settings() {
        $all_settings = get_option('ensemble_payment_gateways', array());
        $this->settings = isset($all_settings[$this->id]) ? $all_settings[$this->id] : array();
        
        $this->enabled = !empty($this->settings['enabled']);
        $this->test_mode = !empty($this->settings['test_mode']);
    }
    
    /**
     * Save gateway settings
     * 
     * @param array $settings
     * @return bool
     */
    public function save_settings($settings) {
        $all_settings = get_option('ensemble_payment_gateways', array());
        $all_settings[$this->id] = $settings;
        return update_option('ensemble_payment_gateways', $all_settings);
    }
    
    /**
     * Get gateway ID
     * 
     * @return string
     */
    public function get_id() {
        return $this->id;
    }
    
    /**
     * Get gateway title
     * 
     * @return string
     */
    public function get_title() {
        return $this->title;
    }
    
    /**
     * Get gateway description
     * 
     * @return string
     */
    public function get_description() {
        return $this->description;
    }
    
    /**
     * Get checkout description (shown to customers during checkout)
     * 
     * @return string
     */
    public function get_checkout_description() {
        return $this->get_setting('checkout_description', '');
    }
    
    /**
     * Get gateway icon
     * 
     * @return string
     */
    public function get_icon() {
        return $this->icon;
    }
    
    /**
     * Check if gateway is enabled
     * 
     * @return bool
     */
    public function is_enabled() {
        return $this->enabled;
    }
    
    /**
     * Check if gateway is in test mode
     * 
     * @return bool
     */
    public function is_test_mode() {
        return $this->test_mode;
    }
    
    /**
     * Check if gateway supports a feature
     * 
     * @param string $feature
     * @return bool
     */
    public function supports($feature) {
        return in_array($feature, $this->supports, true);
    }
    
    /**
     * Get a setting value
     * 
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public function get_setting($key, $default = null) {
        return isset($this->settings[$key]) ? $this->settings[$key] : $default;
    }
    
    /**
     * Check if gateway is available for use
     * 
     * @return bool
     */
    public function is_available() {
        return $this->enabled && $this->is_configured();
    }
    
    /**
     * Check if gateway is properly configured
     * 
     * @return bool
     */
    abstract public function is_configured();
    
    /**
     * Get settings fields for admin
     * 
     * @return array
     */
    abstract public function get_settings_fields();
    
    /**
     * Test gateway connection
     * 
     * @return true|WP_Error
     */
    public function test_connection() {
        if (!$this->is_configured()) {
            return new WP_Error('not_configured', __('Gateway is not configured.', 'ensemble'));
        }
        return true;
    }
    
    /**
     * Create a payment
     * 
     * @param array $args {
     *     Payment arguments
     *     @type int    $booking_id   Booking ID
     *     @type float  $amount       Amount to charge
     *     @type string $currency     Currency code (EUR, USD, etc.)
     *     @type string $description  Payment description
     *     @type string $return_url   URL to redirect after payment
     *     @type string $cancel_url   URL to redirect if cancelled
     *     @type array  $metadata     Additional metadata
     * }
     * @return array|WP_Error {
     *     @type string $payment_id     Gateway payment ID
     *     @type string $status         Payment status
     *     @type string $redirect_url   URL to redirect user (if applicable)
     * }
     */
    abstract public function create_payment($args);
    
    /**
     * Capture a payment (for gateways that support authorization)
     * 
     * @param string $payment_id Gateway payment ID
     * @param float  $amount     Amount to capture (optional, full amount if not specified)
     * @return array|WP_Error
     */
    public function capture_payment($payment_id, $amount = null) {
        return new WP_Error('not_supported', __('This gateway does not support payment capture.', 'ensemble'));
    }
    
    /**
     * Process a refund
     * 
     * @param string $payment_id Gateway payment ID
     * @param float  $amount     Amount to refund (optional, full amount if not specified)
     * @param string $reason     Reason for refund
     * @return array|WP_Error
     */
    public function process_refund($payment_id, $amount = null, $reason = '') {
        return new WP_Error('not_supported', __('This gateway does not support refunds.', 'ensemble'));
    }
    
    /**
     * Get payment details from gateway
     * 
     * @param string $payment_id Gateway payment ID
     * @return array|WP_Error
     */
    abstract public function get_payment($payment_id);
    
    /**
     * Handle webhook/IPN callback
     * 
     * @return void
     */
    public function handle_webhook() {
        wp_die('Webhook handler not implemented', 'Webhook Error', array('response' => 501));
    }
    
    /**
     * Get webhook URL for this gateway
     * 
     * @return string
     */
    public function get_webhook_url() {
        return add_query_arg(array(
            'ensemble_payment_webhook' => $this->id,
        ), home_url('/'));
    }
    
    /**
     * Get return URL after successful payment
     * 
     * @param int $booking_id
     * @return string
     */
    protected function get_return_url($booking_id) {
        return add_query_arg(array(
            'ensemble_payment_return' => '1',
            'booking_id'              => $booking_id,
            'gateway'                 => $this->id,
        ), home_url('/'));
    }
    
    /**
     * Get cancel URL for cancelled payment
     * 
     * @param int $booking_id
     * @return string
     */
    protected function get_cancel_url($booking_id) {
        return add_query_arg(array(
            'ensemble_payment_cancel' => '1',
            'booking_id'              => $booking_id,
            'gateway'                 => $this->id,
        ), home_url('/'));
    }
    
    /**
     * Log a message
     * 
     * @param string $message
     * @param string $level (debug, info, warning, error)
     */
    protected function log($message, $level = 'info') {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf('[Ensemble Gateway %s] [%s] %s', $this->id, strtoupper($level), $message));
        }
    }
    
    /**
     * Format amount for gateway (most require cents/smallest unit)
     * 
     * @param float  $amount
     * @param string $currency
     * @return int
     */
    protected function format_amount($amount, $currency = 'EUR') {
        $zero_decimal = array('BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF');
        
        if (in_array(strtoupper($currency), $zero_decimal)) {
            return (int) $amount;
        }
        
        return (int) round($amount * 100);
    }
    
    /**
     * Parse amount from gateway (convert from cents to decimal)
     * 
     * @param int    $amount
     * @param string $currency
     * @return float
     */
    protected function parse_amount($amount, $currency = 'EUR') {
        $zero_decimal = array('BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF');
        
        if (in_array(strtoupper($currency), $zero_decimal)) {
            return (float) $amount;
        }
        
        return (float) ($amount / 100);
    }
}
