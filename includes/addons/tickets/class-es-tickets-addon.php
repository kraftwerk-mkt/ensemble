<?php
/**
 * Ensemble Tickets Add-on
 * 
 * Affiliate/Partner ticket integration for events
 * - Ticket provider management (Eventbrite, Eventim, TicketMaster, Reservix, Custom)
 * - Multiple ticket types per event
 * - Price display with currency formatting
 * - Availability status
 * - UTM tracking parameters
 * - Prepared for future Ticket Sales Add-on extension
 *
 * @package Ensemble
 * @subpackage Addons
 * @since 2.9.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class ES_Tickets_Addon extends ES_Addon_Base {
    
    /**
     * Add-on configuration
     */
    protected $slug = 'tickets';
    protected $name = 'Tickets';
    protected $version = '1.1.0';
    
    /**
     * Supported ticket providers
     * @var array
     */
    private $providers = array();
    
    /**
     * Ticket types
     * @var array
     */
    private $ticket_types = array();
    
    /**
     * Availability statuses
     * @var array
     */
    private $availability_statuses = array();
    
    /**
     * Initialize add-on
     */
    protected function init() {
        $this->init_providers();
        $this->init_ticket_types();
        $this->init_availability_statuses();
        $this->log('Tickets add-on initialized');
    }
    
    /**
     * Initialize providers
     */
    private function init_providers() {
        $this->providers = array(
            'eventbrite' => array(
                'name'     => 'Eventbrite',
                'icon'     => 'eventbrite',
                'base_url' => 'https://www.eventbrite.com/',
                'color'    => '#f05537',
            ),
            'eventim' => array(
                'name'     => 'Eventim',
                'icon'     => 'eventim',
                'base_url' => 'https://www.eventim.de/',
                'color'    => '#003d7c',
            ),
            'ticketmaster' => array(
                'name'     => 'Ticketmaster',
                'icon'     => 'ticketmaster',
                'base_url' => 'https://www.ticketmaster.com/',
                'color'    => '#026cdf',
            ),
            'reservix' => array(
                'name'     => 'Reservix',
                'icon'     => 'reservix',
                'base_url' => 'https://www.reservix.de/',
                'color'    => '#e30613',
            ),
            'tickets_io' => array(
                'name'     => 'tickets.io',
                'icon'     => 'tickets_io',
                'base_url' => 'https://tickets.io/',
                'color'    => '#00a8e8',
            ),
            'custom' => array(
                'name'     => __('Custom Provider', 'ensemble'),
                'icon'     => 'custom',
                'base_url' => '',
                'color'    => '#6b7280',
            ),
        );
        
        // Allow filtering providers
        $this->providers = apply_filters('ensemble_ticket_providers', $this->providers);
    }
    
    /**
     * Initialize ticket types
     */
    private function init_ticket_types() {
        $this->ticket_types = array(
            'standard'   => __('Standard', 'ensemble'),
            'vip'        => __('VIP', 'ensemble'),
            'early_bird' => __('Early Bird', 'ensemble'),
            'reduced'    => __('Reduced', 'ensemble'),
            'group'      => __('Gruppe', 'ensemble'),
            'student'    => __('Studenten', 'ensemble'),
            'child'      => __('Kinder', 'ensemble'),
            'family'     => __('Familie', 'ensemble'),
        );
        
        // Allow filtering ticket types
        $this->ticket_types = apply_filters('ensemble_ticket_types', $this->ticket_types);
    }
    
    /**
     * Initialize availability statuses
     */
    private function init_availability_statuses() {
        $this->availability_statuses = array(
            'available' => array(
                'label' => __('Available', 'ensemble'),
                'class' => 'es-status-available',
            ),
            'limited' => array(
                'label' => __('Limited', 'ensemble'),
                'class' => 'es-status-limited',
            ),
            'few_left' => array(
                'label' => __('Few Left', 'ensemble'),
                'class' => 'es-status-few-left',
            ),
            'presale' => array(
                'label' => __('Presale', 'ensemble'),
                'class' => 'es-status-presale',
            ),
            'sold_out' => array(
                'label' => __('Sold Out', 'ensemble'),
                'class' => 'es-status-sold-out',
            ),
            'cancelled' => array(
                'label' => __('Cancelled', 'ensemble'),
                'class' => 'es-status-cancelled',
            ),
        );
        
        // Allow filtering statuses
        $this->availability_statuses = apply_filters('ensemble_ticket_availability_statuses', $this->availability_statuses);
    }
    
    /**
     * Register hooks
     */
    protected function register_hooks() {
        // Frontend hooks
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        
        // Template hooks - use the ticket_area addon hook (rendered in single-event.php)
        $this->register_template_hook('ensemble_ticket_area', array($this, 'render_ticket_widget'), 10);
        
        // Admin hooks
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Metabox for event tickets
        add_action('add_meta_boxes', array($this, 'add_ticket_metabox'));
        add_action('save_post', array($this, 'save_ticket_data'), 10, 2);
        
        // AJAX handlers
        add_action('wp_ajax_es_save_event_tickets', array($this, 'ajax_save_tickets'));
        add_action('wp_ajax_es_get_event_tickets', array($this, 'ajax_get_tickets'));
        add_action('wp_ajax_es_get_global_tickets', array($this, 'ajax_get_global_tickets'));
        
        // Shortcodes
        add_shortcode('ensemble_tickets', array($this, 'shortcode_tickets'));
        add_shortcode('ensemble_global_tickets', array($this, 'shortcode_global_tickets'));
        
        // Track clicks (optional feature)
        add_action('wp_ajax_es_track_ticket_click', array($this, 'ajax_track_click'));
        add_action('wp_ajax_nopriv_es_track_ticket_click', array($this, 'ajax_track_click'));
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        $post_type = function_exists('ensemble_get_post_type') ? ensemble_get_post_type() : 'ensemble_event';
        $is_event_page = is_singular($post_type);
        
        // Also check for posts with ensemble_category
        if (!$is_event_page && is_singular('post')) {
            $terms = get_the_terms(get_the_ID(), 'ensemble_category');
            if ($terms && !is_wp_error($terms)) {
                $is_event_page = true;
            }
        }
        
        if (!$is_event_page && !$this->has_tickets_shortcode()) {
            return;
        }
        
        // Tickets CSS
        wp_enqueue_style(
            'ensemble-tickets',
            $this->get_addon_url() . 'assets/tickets.css',
            array(),
            $this->version
        );
        
        // Tickets JS
        wp_enqueue_script(
            'ensemble-tickets',
            $this->get_addon_url() . 'assets/tickets.js',
            array('jquery'),
            $this->version,
            true
        );
        
        wp_localize_script('ensemble-tickets', 'ensembleTickets', array(
            'ajaxUrl'       => admin_url('admin-ajax.php'),
            'nonce'         => wp_create_nonce('es_tickets_nonce'),
            'trackClicks'   => $this->get_setting('track_clicks', false),
            'newTab'        => $this->get_setting('open_new_tab', true),
            'i18n'          => array(
                'loading'   => __('Loading...', 'ensemble'),
                'error'     => __('Loading error', 'ensemble'),
            ),
        ));
    }
    
    /**
     * Check if page has tickets shortcode
     * 
     * @return bool
     */
    private function has_tickets_shortcode() {
        global $post;
        if (!$post) {
            return false;
        }
        return has_shortcode($post->post_content, 'ensemble_tickets') 
            || has_shortcode($post->post_content, 'ensemble_global_tickets');
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        $screen = get_current_screen();
        $post_type = function_exists('ensemble_get_post_type') ? ensemble_get_post_type() : 'ensemble_event';
        
        // Only on event edit pages, wizard, or add-on settings
        $is_edit_page = $screen && ($screen->post_type === $post_type || $screen->post_type === 'post');
        $is_addon_page = isset($_GET['page']) && $_GET['page'] === 'ensemble-addons';
        $is_wizard_page = isset($_GET['page']) && in_array($_GET['page'], array('ensemble', 'ensemble-wizard'));
        
        if (!$is_edit_page && !$is_addon_page && !$is_wizard_page) {
            return;
        }
        
        wp_enqueue_style(
            'ensemble-tickets-admin',
            $this->get_addon_url() . 'assets/tickets-admin.css',
            array(),
            $this->version
        );
        
        wp_enqueue_script(
            'ensemble-tickets-admin',
            $this->get_addon_url() . 'assets/tickets-admin.js',
            array('jquery', 'jquery-ui-sortable'),
            $this->version,
            true
        );
        
        wp_localize_script('ensemble-tickets-admin', 'ensembleTicketsAdmin', array(
            'ajaxUrl'   => admin_url('admin-ajax.php'),
            'nonce'     => wp_create_nonce('es_tickets_admin_nonce'),
            'providers' => $this->providers,
            'ticketTypes' => $this->ticket_types,
            'statuses'  => $this->availability_statuses,
            'currency'  => $this->get_setting('currency', 'EUR'),
            'globalTickets' => $this->get_global_tickets(),
            'strings'   => array(
                'addTicket'     => __('Add Ticket', 'ensemble'),
                'editTicket'    => __('Edit Ticket', 'ensemble'),
                'confirmDelete' => __('Really delete this ticket?', 'ensemble'),
                'confirmExclude'=> __('Exclude this global ticket from this event?', 'ensemble'),
                'noTickets'     => __('No tickets added yet.', 'ensemble'),
                'save'          => __('Save', 'ensemble'),
                'cancel'        => __('Cancel', 'ensemble'),
                'globalTicket'  => __('Global Ticket', 'ensemble'),
            ),
        ));
    }
    
    /**
     * Add ticket metabox
     */
    public function add_ticket_metabox() {
        $post_type = function_exists('ensemble_get_post_type') ? ensemble_get_post_type() : 'ensemble_event';
        
        add_meta_box(
            'ensemble_tickets',
            __('Tickets', 'ensemble'),
            array($this, 'render_ticket_metabox'),
            $post_type,
            'normal',
            'high'
        );
        
        // Also add to posts if using post mode
        if ($post_type === 'post') {
            return;
        }
        
        add_meta_box(
            'ensemble_tickets',
            __('Tickets', 'ensemble'),
            array($this, 'render_ticket_metabox'),
            'post',
            'normal',
            'high'
        );
    }
    
    /**
     * Render ticket metabox
     * 
     * @param WP_Post $post
     */
    public function render_ticket_metabox($post) {
        wp_nonce_field('es_tickets_metabox', 'es_tickets_nonce');
        
        $tickets = $this->get_event_tickets($post->ID);
        
        include $this->get_addon_path() . 'templates/metabox.php';
    }
    
    /**
     * Save ticket data
     * 
     * @param int $post_id
     * @param WP_Post $post
     */
    public function save_ticket_data($post_id, $post) {
        // Verify nonce
        if (!isset($_POST['es_tickets_nonce']) || !wp_verify_nonce($_POST['es_tickets_nonce'], 'es_tickets_metabox')) {
            return;
        }
        
        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save tickets
        if (isset($_POST['es_tickets'])) {
            $tickets = $this->sanitize_tickets($_POST['es_tickets']);
            update_post_meta($post_id, '_ensemble_tickets', $tickets);
        } else {
            delete_post_meta($post_id, '_ensemble_tickets');
        }
    }
    
    /**
     * Sanitize ticket data
     * 
     * @param array $tickets
     * @return array
     */
    private function sanitize_tickets($tickets) {
        if (!is_array($tickets)) {
            return array();
        }
        
        $sanitized = array();
        
        foreach ($tickets as $ticket) {
            $sanitized_ticket = array(
                'type'         => 'affiliate', // Reserved for future sales add-on
                'ticket_type'  => sanitize_text_field($ticket['ticket_type'] ?? 'standard'),
                'provider'     => sanitize_text_field($ticket['provider'] ?? 'custom'),
                'provider_name'=> sanitize_text_field($ticket['provider_name'] ?? ''),
                'title'        => sanitize_text_field($ticket['title'] ?? ''),
                'price'        => floatval($ticket['price'] ?? 0),
                'price_max'    => floatval($ticket['price_max'] ?? 0),
                'currency'     => sanitize_text_field($ticket['currency'] ?? $this->get_setting('currency', 'EUR')),
                'url'          => esc_url_raw($ticket['url'] ?? ''),
                'availability' => sanitize_text_field($ticket['availability'] ?? 'available'),
                'description'  => sanitize_textarea_field($ticket['description'] ?? ''),
                'sort_order'   => intval($ticket['sort_order'] ?? 0),
            );
            
            // Only add valid tickets
            if (!empty($sanitized_ticket['url']) || !empty($sanitized_ticket['title'])) {
                $sanitized[] = $sanitized_ticket;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Get event tickets
     * 
     * @param int $event_id
     * @return array
     */
    public function get_event_tickets($event_id) {
        // Get event-specific tickets
        $local_tickets = get_post_meta($event_id, '_ensemble_tickets', true);
        
        if (!is_array($local_tickets)) {
            $local_tickets = array();
        }
        
        // Mark local tickets
        foreach ($local_tickets as &$ticket) {
            $ticket['is_global'] = false;
        }
        
        // Get global tickets and merge them
        $global_tickets = $this->get_global_tickets();
        $excluded_global = get_post_meta($event_id, '_ensemble_excluded_global_tickets', true);
        
        if (!is_array($excluded_global)) {
            $excluded_global = array();
        }
        
        // Add non-excluded global tickets
        foreach ($global_tickets as $global_ticket) {
            $global_id = $global_ticket['id'] ?? '';
            
            // Skip if excluded for this event
            if (in_array($global_id, $excluded_global)) {
                continue;
            }
            
            // Mark as global and add to list
            $global_ticket['is_global'] = true;
            $local_tickets[] = $global_ticket;
        }
        
        // Sort by sort_order
        usort($local_tickets, function($a, $b) {
            return ($a['sort_order'] ?? 0) - ($b['sort_order'] ?? 0);
        });
        
        /**
         * Filter event tickets
         * 
         * @param array $tickets
         * @param int $event_id
         */
        return apply_filters('ensemble_event_tickets', $local_tickets, $event_id);
    }
    
    /**
     * Get global tickets from settings
     * 
     * @return array
     */
    public function get_global_tickets() {
        // Read directly from database to ensure we have latest data
        $settings = get_option('ensemble_addon_tickets_settings', array());
        $global_tickets = isset($settings['global_tickets']) ? $settings['global_tickets'] : array();
        
        if (!is_array($global_tickets)) {
            $global_tickets = array();
        }
        
        // Ensure each ticket has is_global flag
        foreach ($global_tickets as &$ticket) {
            $ticket['is_global'] = true;
        }
        
        return $global_tickets;
    }
    
    /**
     * AJAX: Get global tickets
     */
    public function ajax_get_global_tickets() {
        check_ajax_referer('es_tickets_admin_nonce', 'nonce');
        
        $global_tickets = $this->get_global_tickets();
        
        wp_send_json_success(array('tickets' => $global_tickets));
    }
    
    /**
     * Build ticket URL with tracking parameters
     * 
     * @param array $ticket
     * @param int $event_id
     * @return string
     */
    public function build_ticket_url($ticket, $event_id = 0) {
        $url = $ticket['url'] ?? '';
        
        if (empty($url)) {
            return '';
        }
        
        // Add UTM parameters if enabled
        if ($this->get_setting('add_utm_params', true)) {
            $utm_params = array(
                'utm_source'   => $this->get_setting('utm_source', 'ensemble'),
                'utm_medium'   => $this->get_setting('utm_medium', 'event_widget'),
                'utm_campaign' => $this->get_setting('utm_campaign', 'tickets'),
            );
            
            if ($event_id) {
                $utm_params['utm_content'] = 'event_' . $event_id;
            }
            
            // Allow filtering UTM params
            $utm_params = apply_filters('ensemble_ticket_utm_params', $utm_params, $ticket, $event_id);
            
            $url = add_query_arg($utm_params, $url);
        }
        
        /**
         * Filter ticket URL
         * 
         * @param string $url
         * @param array $ticket
         * @param int $event_id
         */
        return apply_filters('ensemble_ticket_url', $url, $ticket, $event_id);
    }
    
    /**
     * Format price display
     * 
     * @param float $price
     * @param string $currency
     * @param float $price_max Optional max price for ranges
     * @return string
     */
    public function format_price($price, $currency = 'EUR', $price_max = 0) {
        $price = floatval($price);
        $price_max = floatval($price_max);
        
        // Free ticket
        if ($price === 0.0 && $price_max === 0.0) {
            return __('Free', 'ensemble');
        }
        
        // Currency symbols
        $symbols = array(
            'EUR' => '€',
            'USD' => '$',
            'GBP' => '£',
            'CHF' => 'CHF',
        );
        
        $symbol = $symbols[$currency] ?? $currency;
        $format = $this->get_setting('price_format', 'symbol_before');
        
        // Format single price
        $formatted = number_format($price, 2, ',', '.');
        
        if ($format === 'symbol_before') {
            $formatted = $symbol . ' ' . $formatted;
        } else {
            $formatted = $formatted . ' ' . $symbol;
        }
        
        // Add max price for range
        if ($price_max > $price) {
            $max_formatted = number_format($price_max, 2, ',', '.');
            if ($format === 'symbol_before') {
                $formatted .= ' - ' . $symbol . ' ' . $max_formatted;
            } else {
                $formatted .= ' - ' . $max_formatted . ' ' . $symbol;
            }
        }
        
        /**
         * Filter price display
         * 
         * @param string $formatted
         * @param float $price
         * @param string $currency
         */
        return apply_filters('ensemble_ticket_price_display', $formatted, $price, $currency);
    }
    
    /**
     * Render ticket widget (frontend)
     * 
     * @param int $event_id
     * @param array $context Optional context from hook
     */
    public function render_ticket_widget($event_id = 0, $context = array()) {
        // Check display settings
        if (function_exists('ensemble_show_addon') && !ensemble_show_addon('tickets')) {
            return;
        }
        
        if (!$event_id) {
            $event_id = get_the_ID();
        }
        
        $tickets = $this->get_event_tickets($event_id);
        
        if (empty($tickets)) {
            return;
        }
        
        // Prepare settings for template
        $settings = array(
            'layout'           => 'list',
            'widget_title'     => $this->get_setting('widget_title', __('Tickets', 'ensemble')),
            'button_text'      => $this->get_setting('button_text', __('Tickets kaufen', 'ensemble')),
            'show_provider_logo' => (bool) $this->get_setting('show_provider_logo', true),
        );
        
        // Make addon instance available
        $addon = $this;
        
        /**
         * Before ticket widget
         * 
         * @param int $event_id
         * @param array $tickets
         */
        do_action('ensemble_before_tickets', $event_id, $tickets);
        
        include $this->get_addon_path() . 'templates/frontend-widget.php';
        
        /**
         * After ticket widget (internal, not the same as template hook)
         * 
         * @param int $event_id
         * @param array $tickets
         */
        do_action('ensemble_tickets_rendered', $event_id, $tickets);
    }
    
    /**
     * Render ticket sidebar widget
     * 
     * @param int $event_id
     */
    public function render_ticket_sidebar($event_id = 0) {
        if (!$event_id) {
            $event_id = get_the_ID();
        }
        
        $tickets = $this->get_event_tickets($event_id);
        
        if (empty($tickets)) {
            return;
        }
        
        // Use compact sidebar template
        include $this->get_addon_path() . 'templates/ticket-sidebar.php';
    }
    
    /**
     * Shortcode handler
     * 
     * @param array $atts
     * @return string
     */
    public function shortcode_tickets($atts) {
        $atts = shortcode_atts(array(
            'event_id' => get_the_ID(),
            'style'    => 'default', // default, compact, list
            'show_provider' => 'true',
            'show_status' => 'true',
        ), $atts, 'ensemble_tickets');
        
        $event_id = intval($atts['event_id']);
        $tickets = $this->get_event_tickets($event_id);
        
        if (empty($tickets)) {
            return '';
        }
        
        ob_start();
        
        $style = sanitize_text_field($atts['style']);
        $show_provider = filter_var($atts['show_provider'], FILTER_VALIDATE_BOOLEAN);
        $show_status = filter_var($atts['show_status'], FILTER_VALIDATE_BOOLEAN);
        
        include $this->get_addon_path() . 'templates/ticket-shortcode.php';
        
        return ob_get_clean();
    }
    
    /**
     * Shortcode handler for global tickets (without event context)
     * 
     * Usage: [ensemble_global_tickets]
     * 
     * @param array $atts
     * @return string
     */
    public function shortcode_global_tickets($atts) {
        $atts = shortcode_atts(array(
            'layout'       => 'list',    // list, grid, compact
            'style'        => 'default', // default, minimal, cards
            'title'        => '',        // Optional title above tickets
            'show_description' => 'true',
            'show_price'   => 'true',
            'show_status'  => 'true',
            'class'        => '',        // Additional CSS classes
        ), $atts, 'ensemble_global_tickets');
        
        $tickets = $this->get_global_tickets();
        
        if (empty($tickets)) {
            return '';
        }
        
        ob_start();
        
        $layout = sanitize_text_field($atts['layout']);
        $style = sanitize_text_field($atts['style']);
        $title = sanitize_text_field($atts['title']);
        $show_description = filter_var($atts['show_description'], FILTER_VALIDATE_BOOLEAN);
        $show_price = filter_var($atts['show_price'], FILTER_VALIDATE_BOOLEAN);
        $show_status = filter_var($atts['show_status'], FILTER_VALIDATE_BOOLEAN);
        $extra_class = sanitize_html_class($atts['class']);
        $addon = $this;
        
        include $this->get_addon_path() . 'templates/shortcode-global-tickets.php';
        
        return ob_get_clean();
    }
    
    /**
     * AJAX: Track ticket click
     */
    public function ajax_track_click() {
        check_ajax_referer('es_tickets_nonce', 'nonce');
        
        $event_id = intval($_POST['event_id'] ?? 0);
        $ticket_index = intval($_POST['ticket_index'] ?? 0);
        
        if (!$event_id) {
            wp_send_json_error();
        }
        
        // Get click stats
        $stats = get_post_meta($event_id, '_ensemble_ticket_clicks', true);
        if (!is_array($stats)) {
            $stats = array();
        }
        
        // Increment click count
        if (!isset($stats[$ticket_index])) {
            $stats[$ticket_index] = 0;
        }
        $stats[$ticket_index]++;
        
        update_post_meta($event_id, '_ensemble_ticket_clicks', $stats);
        
        /**
         * Ticket click tracked
         * 
         * @param int $event_id
         * @param int $ticket_index
         * @param array $stats
         */
        do_action('ensemble_ticket_click_tracked', $event_id, $ticket_index, $stats);
        
        wp_send_json_success();
    }
    
    /**
     * AJAX: Get tickets for event
     */
    public function ajax_get_tickets() {
        $event_id = intval($_GET['event_id'] ?? 0);
        
        if (!$event_id) {
            wp_send_json_error(array('message' => 'Invalid event ID'));
        }
        
        $tickets = $this->get_event_tickets($event_id);
        
        wp_send_json_success(array('tickets' => $tickets));
    }
    
    /**
     * Get providers
     * 
     * @return array
     */
    public function get_providers() {
        return $this->providers;
    }
    
    /**
     * Get ticket types
     * 
     * @return array
     */
    public function get_ticket_types() {
        return $this->ticket_types;
    }
    
    /**
     * Get availability statuses
     * 
     * @return array
     */
    public function get_availability_statuses() {
        return $this->availability_statuses;
    }
    
    /**
     * Render settings page
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
        // Handle global tickets JSON
        $global_tickets = array();
        if (!empty($settings['global_tickets_json'])) {
            $decoded = json_decode(stripslashes($settings['global_tickets_json']), true);
            if (is_array($decoded)) {
                $global_tickets = $this->sanitize_tickets_array($decoded);
            }
        }
        
        return array(
            'currency'        => sanitize_text_field($settings['currency'] ?? 'EUR'),
            'price_format'    => sanitize_text_field($settings['price_format'] ?? 'symbol_before'),
            'add_utm_params'  => !empty($settings['add_utm_params']),
            'utm_source'      => sanitize_text_field($settings['utm_source'] ?? 'ensemble'),
            'utm_medium'      => sanitize_text_field($settings['utm_medium'] ?? 'event_widget'),
            'utm_campaign'    => sanitize_text_field($settings['utm_campaign'] ?? 'tickets'),
            'track_clicks'    => !empty($settings['track_clicks']),
            'open_new_tab'    => isset($settings['open_new_tab']) ? !empty($settings['open_new_tab']) : true,
            'show_provider_logo' => isset($settings['show_provider_logo']) ? !empty($settings['show_provider_logo']) : true,
            'widget_title'    => sanitize_text_field($settings['widget_title'] ?? __('Tickets', 'ensemble')),
            'button_text'     => sanitize_text_field($settings['button_text'] ?? __('Buy Tickets', 'ensemble')),
            'global_tickets'  => $global_tickets,
        );
    }
    
    /**
     * Sanitize tickets array
     * 
     * @param array $tickets
     * @return array
     */
    private function sanitize_tickets_array($tickets) {
        $sanitized = array();
        
        foreach ($tickets as $ticket) {
            $sanitized[] = array(
                'id'          => sanitize_text_field($ticket['id'] ?? 'ticket_' . uniqid()),
                'provider'    => sanitize_text_field($ticket['provider'] ?? 'custom'),
                'name'        => sanitize_text_field($ticket['name'] ?? ''),
                'url'         => esc_url_raw($ticket['url'] ?? ''),
                'price'       => floatval($ticket['price'] ?? 0),
                'price_max'   => floatval($ticket['price_max'] ?? 0),
                'currency'    => sanitize_text_field($ticket['currency'] ?? 'EUR'),
                'availability' => sanitize_text_field($ticket['availability'] ?? 'available'),
                'custom_text' => sanitize_text_field($ticket['custom_text'] ?? ''),
                'sort_order'  => intval($ticket['sort_order'] ?? 0),
                'is_global'   => !empty($ticket['is_global']),
            );
        }
        
        return $sanitized;
    }
}
