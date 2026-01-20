<?php
/**
 * PayPal Payment Gateway
 * 
 * Implements PayPal Checkout (REST API v2)
 *
 * @package Ensemble
 * @subpackage Addons/TicketsPro/Gateways
 * @since 3.1.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class ES_Gateway_PayPal extends ES_Payment_Gateway {
    
    /**
     * API endpoints
     */
    const SANDBOX_API = 'https://api-m.sandbox.paypal.com';
    const LIVE_API    = 'https://api-m.paypal.com';
    
    /**
     * Access token cache
     * @var array
     */
    private $access_token = null;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->id          = 'paypal';
        $this->title       = __('PayPal', 'ensemble');
        $this->description = __('Pay securely with PayPal.', 'ensemble');
        $this->icon        = 'https://www.paypalobjects.com/webstatic/mktg/logo/pp_cc_mark_37x23.jpg';
        
        $this->supports = array(
            'payments',
            'refunds',
        );
        
        parent::__construct();
    }
    
    /**
     * Check if gateway is configured
     * 
     * @return bool
     */
    public function is_configured() {
        $client_id = $this->get_client_id();
        $secret    = $this->get_client_secret();
        
        return !empty($client_id) && !empty($secret);
    }
    
    /**
     * Test gateway connection
     * 
     * @return true|WP_Error
     */
    public function test_connection() {
        $token = $this->get_access_token();
        
        if (is_wp_error($token)) {
            return $token;
        }
        
        return true;
    }
    
    /**
     * Get API base URL
     * 
     * @return string
     */
    private function get_api_url() {
        return $this->test_mode ? self::SANDBOX_API : self::LIVE_API;
    }
    
    /**
     * Get client ID
     * 
     * @return string
     */
    private function get_client_id() {
        $key = $this->test_mode ? 'sandbox_client_id' : 'live_client_id';
        return $this->get_setting($key, '');
    }
    
    /**
     * Get client secret
     * 
     * @return string
     */
    private function get_client_secret() {
        $key = $this->test_mode ? 'sandbox_client_secret' : 'live_client_secret';
        return $this->get_setting($key, '');
    }
    
    /**
     * Get settings fields for admin
     * 
     * @return array
     */
    public function get_settings_fields() {
        return array(
            'enabled' => array(
                'title'   => __('Enable/Disable', 'ensemble'),
                'type'    => 'checkbox',
                'label'   => __('Enable PayPal', 'ensemble'),
                'default' => false,
            ),
            'test_mode' => array(
                'title'       => __('Test Mode', 'ensemble'),
                'type'        => 'checkbox',
                'label'       => __('Enable Sandbox Mode', 'ensemble'),
                'description' => __('Use PayPal sandbox for testing.', 'ensemble'),
                'default'     => true,
            ),
            'sandbox_client_id' => array(
                'title'       => __('Sandbox Client ID', 'ensemble'),
                'type'        => 'text',
                'description' => __('Get your sandbox credentials from the PayPal Developer Dashboard.', 'ensemble'),
                'default'     => '',
            ),
            'sandbox_client_secret' => array(
                'title'       => __('Sandbox Client Secret', 'ensemble'),
                'type'        => 'password',
                'default'     => '',
            ),
            'live_client_id' => array(
                'title'       => __('Live Client ID', 'ensemble'),
                'type'        => 'text',
                'description' => __('Get your live credentials from the PayPal Developer Dashboard.', 'ensemble'),
                'default'     => '',
            ),
            'live_client_secret' => array(
                'title'       => __('Live Client Secret', 'ensemble'),
                'type'        => 'password',
                'default'     => '',
            ),
            'brand_name' => array(
                'title'       => __('Brand Name', 'ensemble'),
                'type'        => 'text',
                'description' => __('Name shown on PayPal checkout page.', 'ensemble'),
                'default'     => get_bloginfo('name'),
            ),
        );
    }
    
    /**
     * Get access token
     * 
     * @return string|WP_Error
     */
    private function get_access_token() {
        if ($this->access_token && $this->access_token['expires'] > time()) {
            return $this->access_token['token'];
        }
        
        $client_id = $this->get_client_id();
        $secret    = $this->get_client_secret();
        
        if (empty($client_id) || empty($secret)) {
            return new WP_Error('missing_credentials', __('PayPal credentials not configured.', 'ensemble'));
        }
        
        $response = wp_remote_post($this->get_api_url() . '/v1/oauth2/token', array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($client_id . ':' . $secret),
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ),
            'body' => array(
                'grant_type' => 'client_credentials',
            ),
            'timeout' => 30,
        ));
        
        if (is_wp_error($response)) {
            $this->log('Failed to get access token: ' . $response->get_error_message(), 'error');
            return $response;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (empty($body['access_token'])) {
            $error_msg = isset($body['error_description']) ? $body['error_description'] : __('Failed to authenticate with PayPal.', 'ensemble');
            $this->log('Auth error: ' . $error_msg, 'error');
            return new WP_Error('auth_error', $error_msg);
        }
        
        $this->access_token = array(
            'token'   => $body['access_token'],
            'expires' => time() + intval($body['expires_in']) - 60,
        );
        
        return $this->access_token['token'];
    }
    
    /**
     * Make API request
     * 
     * @param string $endpoint
     * @param array  $args
     * @param string $method
     * @return array|WP_Error
     */
    private function api_request($endpoint, $args = array(), $method = 'POST') {
        $token = $this->get_access_token();
        
        if (is_wp_error($token)) {
            return $token;
        }
        
        $url = $this->get_api_url() . $endpoint;
        
        $request_args = array(
            'method'  => $method,
            'headers' => array(
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
            ),
            'timeout' => 30,
        );
        
        if (!empty($args) && in_array($method, array('POST', 'PATCH', 'PUT'))) {
            $request_args['body'] = wp_json_encode($args);
        }
        
        $this->log(sprintf('API Request: %s %s', $method, $endpoint), 'debug');
        
        $response = wp_remote_request($url, $request_args);
        
        if (is_wp_error($response)) {
            $this->log('API Error: ' . $response->get_error_message(), 'error');
            return $response;
        }
        
        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($code >= 400) {
            $error_msg = isset($body['message']) ? $body['message'] : __('PayPal API error.', 'ensemble');
            if (isset($body['details'][0]['description'])) {
                $error_msg .= ' ' . $body['details'][0]['description'];
            }
            $this->log('API Error ' . $code . ': ' . $error_msg, 'error');
            return new WP_Error('api_error', $error_msg, array('code' => $code, 'response' => $body));
        }
        
        return $body;
    }
    
    /**
     * Create a payment
     * 
     * @param array $args Payment arguments
     * @return array|WP_Error
     */
    public function create_payment($args) {
        $defaults = array(
            'booking_id'  => 0,
            'amount'      => 0,
            'currency'    => 'EUR',
            'description' => '',
            'return_url'  => '',
            'cancel_url'  => '',
            'metadata'    => array(),
        );
        
        $args = wp_parse_args($args, $defaults);
        
        if (empty($args['booking_id'])) {
            return new WP_Error('missing_booking_id', __('Booking ID is required.', 'ensemble'));
        }
        
        if ($args['amount'] <= 0) {
            return new WP_Error('invalid_amount', __('Invalid payment amount.', 'ensemble'));
        }
        
        // Build return URLs
        $return_url = !empty($args['return_url']) ? $args['return_url'] : $this->get_return_url($args['booking_id']);
        $cancel_url = !empty($args['cancel_url']) ? $args['cancel_url'] : $this->get_cancel_url($args['booking_id']);
        
        // Build order data
        $order_data = array(
            'intent'         => 'CAPTURE',
            'purchase_units' => array(
                array(
                    'reference_id' => 'booking_' . $args['booking_id'],
                    'description'  => substr($args['description'], 0, 127),
                    'custom_id'    => strval($args['booking_id']),
                    'amount'       => array(
                        'currency_code' => strtoupper($args['currency']),
                        'value'         => number_format($args['amount'], 2, '.', ''),
                    ),
                ),
            ),
            'application_context' => array(
                'brand_name'          => $this->get_setting('brand_name', get_bloginfo('name')),
                'locale'              => str_replace('_', '-', get_locale()),
                'landing_page'        => 'LOGIN',
                'shipping_preference' => 'NO_SHIPPING',
                'user_action'         => 'PAY_NOW',
                'return_url'          => $return_url,
                'cancel_url'          => $cancel_url,
            ),
        );
        
        $this->log('Creating order for booking ' . $args['booking_id'], 'info');
        
        $response = $this->api_request('/v2/checkout/orders', $order_data);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        if (empty($response['id'])) {
            return new WP_Error('order_creation_failed', __('Failed to create PayPal order.', 'ensemble'));
        }
        
        // Find approval link
        $approve_url = '';
        if (!empty($response['links'])) {
            foreach ($response['links'] as $link) {
                if ($link['rel'] === 'approve') {
                    $approve_url = $link['href'];
                    break;
                }
            }
        }
        
        $this->log('Order created: ' . $response['id'], 'info');
        
        return array(
            'payment_id'   => $response['id'],
            'status'       => strtolower($response['status']),
            'redirect_url' => $approve_url,
            'raw_response' => $response,
        );
    }
    
    /**
     * Capture a payment
     * 
     * @param string $payment_id PayPal Order ID
     * @param float  $amount     Not used for PayPal
     * @return array|WP_Error
     */
    public function capture_payment($payment_id, $amount = null) {
        $this->log('Capturing payment: ' . $payment_id, 'info');
        
        $response = $this->api_request('/v2/checkout/orders/' . $payment_id . '/capture', array());
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        if ($response['status'] !== 'COMPLETED') {
            return new WP_Error('capture_failed', __('Payment capture failed.', 'ensemble'));
        }
        
        // Get capture ID
        $capture_id = '';
        if (!empty($response['purchase_units'][0]['payments']['captures'][0]['id'])) {
            $capture_id = $response['purchase_units'][0]['payments']['captures'][0]['id'];
        }
        
        $this->log('Payment captured: ' . $capture_id, 'info');
        
        return array(
            'payment_id'   => $payment_id,
            'capture_id'   => $capture_id,
            'status'       => 'completed',
            'raw_response' => $response,
        );
    }
    
    /**
     * Process a refund
     * 
     * @param string $payment_id Capture ID
     * @param float  $amount     Refund amount
     * @param string $reason     Refund reason
     * @return array|WP_Error
     */
    public function process_refund($payment_id, $amount = null, $reason = '') {
        $this->log('Processing refund for: ' . $payment_id, 'info');
        
        $refund_data = array();
        
        if ($amount !== null) {
            $payment = $this->get_payment_by_capture($payment_id);
            $currency = 'EUR';
            
            if (!is_wp_error($payment) && isset($payment['currency'])) {
                $currency = $payment['currency'];
            }
            
            $refund_data['amount'] = array(
                'currency_code' => $currency,
                'value'         => number_format($amount, 2, '.', ''),
            );
        }
        
        if (!empty($reason)) {
            $refund_data['note_to_payer'] = substr($reason, 0, 255);
        }
        
        $response = $this->api_request('/v2/payments/captures/' . $payment_id . '/refund', $refund_data);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $this->log('Refund processed: ' . $response['id'], 'info');
        
        return array(
            'refund_id'    => $response['id'],
            'status'       => strtolower($response['status']),
            'amount'       => isset($response['amount']['value']) ? floatval($response['amount']['value']) : null,
            'raw_response' => $response,
        );
    }
    
    /**
     * Get payment details
     * 
     * @param string $payment_id PayPal Order ID
     * @return array|WP_Error
     */
    public function get_payment($payment_id) {
        $response = $this->api_request('/v2/checkout/orders/' . $payment_id, array(), 'GET');
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $amount = 0;
        $currency = 'EUR';
        $booking_id = 0;
        
        if (!empty($response['purchase_units'][0])) {
            $pu = $response['purchase_units'][0];
            $amount = isset($pu['amount']['value']) ? floatval($pu['amount']['value']) : 0;
            $currency = isset($pu['amount']['currency_code']) ? $pu['amount']['currency_code'] : 'EUR';
            $booking_id = isset($pu['custom_id']) ? intval($pu['custom_id']) : 0;
        }
        
        $capture_id = '';
        if ($response['status'] === 'COMPLETED' && !empty($response['purchase_units'][0]['payments']['captures'][0]['id'])) {
            $capture_id = $response['purchase_units'][0]['payments']['captures'][0]['id'];
        }
        
        return array(
            'payment_id'   => $payment_id,
            'capture_id'   => $capture_id,
            'status'       => strtolower($response['status']),
            'amount'       => $amount,
            'currency'     => $currency,
            'booking_id'   => $booking_id,
            'payer'        => isset($response['payer']) ? $response['payer'] : null,
            'raw_response' => $response,
        );
    }
    
    /**
     * Get payment by capture ID
     * 
     * @param string $capture_id
     * @return array|WP_Error
     */
    private function get_payment_by_capture($capture_id) {
        $response = $this->api_request('/v2/payments/captures/' . $capture_id, array(), 'GET');
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        return array(
            'capture_id' => $capture_id,
            'status'     => strtolower($response['status']),
            'amount'     => isset($response['amount']['value']) ? floatval($response['amount']['value']) : 0,
            'currency'   => isset($response['amount']['currency_code']) ? $response['amount']['currency_code'] : 'EUR',
        );
    }
    
    /**
     * Handle webhook/IPN
     */
    public function handle_webhook() {
        $this->log('Webhook received', 'info');
        
        $raw_input = file_get_contents('php://input');
        $webhook_data = json_decode($raw_input, true);
        
        if (empty($webhook_data)) {
            $this->log('Invalid webhook data', 'error');
            wp_die('Invalid webhook data', 'Webhook Error', array('response' => 400));
        }
        
        $event_type = isset($webhook_data['event_type']) ? $webhook_data['event_type'] : '';
        $resource = isset($webhook_data['resource']) ? $webhook_data['resource'] : array();
        
        $this->log('Webhook event: ' . $event_type, 'info');
        
        switch ($event_type) {
            case 'PAYMENT.CAPTURE.COMPLETED':
                $this->handle_capture_completed($resource);
                break;
                
            case 'PAYMENT.CAPTURE.REFUNDED':
                $this->handle_capture_refunded($resource);
                break;
                
            case 'PAYMENT.CAPTURE.DENIED':
            case 'PAYMENT.CAPTURE.DECLINED':
                $this->handle_capture_failed($resource);
                break;
        }
        
        status_header(200);
        echo 'OK';
        exit;
    }
    
    /**
     * Handle capture completed webhook
     * 
     * @param array $resource
     */
    private function handle_capture_completed($resource) {
        $booking_id = 0;
        
        if (!empty($resource['custom_id'])) {
            $booking_id = intval($resource['custom_id']);
        }
        
        if (!$booking_id) {
            $this->log('No booking ID in webhook', 'warning');
            return;
        }
        
        /**
         * Action fired when PayPal payment is completed
         */
        do_action('ensemble_paypal_payment_completed', $booking_id, $resource);
        
        $this->update_booking_payment_status($booking_id, 'paid', array(
            'payment_id'     => isset($resource['id']) ? $resource['id'] : '',
            'payment_method' => 'paypal',
        ));
    }
    
    /**
     * Handle capture refunded webhook
     * 
     * @param array $resource
     */
    private function handle_capture_refunded($resource) {
        /**
         * Action fired when PayPal refund is processed
         */
        do_action('ensemble_paypal_payment_refunded', $resource);
    }
    
    /**
     * Handle capture failed webhook
     * 
     * @param array $resource
     */
    private function handle_capture_failed($resource) {
        $booking_id = 0;
        
        if (!empty($resource['custom_id'])) {
            $booking_id = intval($resource['custom_id']);
        }
        
        if (!$booking_id) {
            return;
        }
        
        /**
         * Action fired when PayPal payment fails
         */
        do_action('ensemble_paypal_payment_failed', $booking_id, $resource);
        
        $this->update_booking_payment_status($booking_id, 'failed');
    }
    
    /**
     * Update booking payment status
     * 
     * @param int    $booking_id
     * @param string $status
     * @param array  $data
     */
    private function update_booking_payment_status($booking_id, $status, $data = array()) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ensemble_bookings';
        
        $update = array(
            'payment_status' => $status,
            'updated_at'     => current_time('mysql'),
        );
        
        if (!empty($data['payment_id'])) {
            $update['payment_id'] = $data['payment_id'];
        }
        
        if (!empty($data['payment_method'])) {
            $update['payment_method'] = $data['payment_method'];
        }
        
        $wpdb->update($table, $update, array('id' => $booking_id));
        
        if ($status === 'paid') {
            $wpdb->update($table, array('status' => 'confirmed'), array('id' => $booking_id));
            
            /**
             * Action fired when booking is paid
             */
            do_action('ensemble_booking_paid', $booking_id);
        }
    }
}
