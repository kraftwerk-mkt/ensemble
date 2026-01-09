<?php
/**
 * Ensemble Add-on Manager
 * 
 * Manages all add-ons for Ensemble Plugin
 * Handles registration, activation, licensing, and hook system
 *
 * @package Ensemble
 * @subpackage Addons
 * @since 2.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class ES_Addon_Manager {
    
    /**
     * Registered add-ons
     * @var array
     */
    private static $addons = array();
    
    /**
     * Active add-ons
     * @var array
     */
    private static $active_addons = array();
    
    /**
     * Add-on hooks
     * @var array
     */
    private static $addon_hooks = array();
    
    /**
     * Instance
     * @var ES_Addon_Manager
     */
    private static $instance = null;
    
    /**
     * Get instance
     * @return ES_Addon_Manager
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
        $this->init_hooks();
        // Note: load_active_addons() is called after addons are registered in ensemble.php
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('admin_menu', array($this, 'register_admin_page'), 25);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_ajax_es_toggle_addon', array($this, 'ajax_toggle_addon'));
        add_action('wp_ajax_es_save_addon_settings', array($this, 'ajax_save_addon_settings'));
        add_action('wp_ajax_es_get_addon_settings', array($this, 'ajax_get_addon_settings'));
    }
    
    /**
     * Register add-on
     * 
     * @param string $slug Add-on unique slug
     * @param array $args Add-on configuration
     * @return bool
     */
    public static function register_addon($slug, $args = array()) {
        $defaults = array(
            'name'          => '',
            'description'   => '',
            'version'       => '1.0.0',
            'author'        => 'Fabian',
            'author_uri'    => 'https://kraftwerk-mkt.com',
            'requires_pro'  => true,
            'class'         => '',
            'icon'          => 'dashicons-admin-plugins',
            'settings_page' => false,
            'has_frontend'  => false,
        );
        
        $addon = wp_parse_args($args, $defaults);
        $addon['slug'] = $slug;
        // Note: 'active' flag is set dynamically in get_addons()
        
        self::$addons[$slug] = $addon;
        
        return true;
    }
    
    /**
     * Get registered add-on
     * 
     * @param string $slug
     * @return array|false
     */
    public static function get_addon($slug) {
        return isset(self::$addons[$slug]) ? self::$addons[$slug] : false;
    }
    
    /**
     * Get all registered add-ons
     * 
     * @return array
     */
    public static function get_addons() {
        // Update active status dynamically
        foreach (self::$addons as $slug => &$addon) {
            $is_active = self::is_addon_active($slug);
            $addon['active'] = $is_active;
        }
        unset($addon); // Break reference
        
        return self::$addons;
    }
    
    /**
     * Check if add-on is active
     * 
     * @param string $slug
     * @return bool
     */
    public static function is_addon_active($slug) {
        $active = get_option('ensemble_active_addons', array());
        return in_array($slug, $active);
    }
    
    /**
     * Check if Pro version is active
     * 
     * @return bool
     */
    public static function is_pro_active() {
        // Use the License Manager
        if (function_exists('ensemble_is_pro')) {
            return ensemble_is_pro();
        }
        
        // Fallback
        return apply_filters('ensemble_is_pro_active', false);
    }
    
    /**
     * Activate add-on
     * 
     * @param string $slug
     * @return bool|WP_Error
     */
    public static function activate_addon($slug) {
        $addon = self::get_addon($slug);
        
        if (!$addon) {
            return new WP_Error('addon_not_found', __('Add-on nicht gefunden.', 'ensemble'));
        }
        
        
        // Check Pro requirement
        if ($addon['requires_pro'] && !self::is_pro_active()) {
            return new WP_Error('requires_pro', __('This add-on requires the Pro version.', 'ensemble'));
        }
        
        $active = get_option('ensemble_active_addons', array());
        
        if (!in_array($slug, $active)) {
            $active[] = $slug;
            $result = update_option('ensemble_active_addons', $active);
            
            // Trigger activation hook
            do_action('ensemble_addon_activated', $slug);
            do_action("ensemble_addon_activated_{$slug}");
            
            // Initialize addon if class exists
            if (!empty($addon['class'])) {
                if (class_exists($addon['class'])) {
                    self::$active_addons[$slug] = new $addon['class']();
                } else {
                }
            } else {
            }
        } else {
        }
        
        return true;
    }
    
    /**
     * Deactivate add-on
     * 
     * @param string $slug
     * @return bool
     */
    public static function deactivate_addon($slug) {
        $active = get_option('ensemble_active_addons', array());
        
        $key = array_search($slug, $active);
        
        if ($key !== false) {
            unset($active[$key]);
            
            // CRITICAL: Force delete option first to ensure clean state
            delete_option('ensemble_active_addons');
            
            // Now set new value
            $result = add_option('ensemble_active_addons', array_values($active));
            if (!$result) {
                // Option existed, use update instead
                $result = update_option('ensemble_active_addons', array_values($active));
            }
            
            // AGGRESSIVE cache clearing
            wp_cache_delete('ensemble_active_addons', 'options');
            wp_cache_delete('alloptions', 'options');
            wp_cache_flush();
            
            
            // Verify it was saved
            $verify = get_option('ensemble_active_addons', array());
            
            // Trigger deactivation hook
            do_action('ensemble_addon_deactivated', $slug);
            do_action("ensemble_addon_deactivated_{$slug}");
            
            // Remove from active instances
            if (isset(self::$active_addons[$slug])) {
                unset(self::$active_addons[$slug]);
            }
        } else {
        }
        
        return true;
    }
    
    /**
     * Load active add-ons
     */
    /**
     * Load active add-ons
     * Called after add-ons are registered
     */
    public function load_active_addons() {
        $active = get_option('ensemble_active_addons', array());
        
        foreach ($active as $slug) {
            $addon = self::get_addon($slug);
            if ($addon && !empty($addon['class']) && class_exists($addon['class'])) {
                self::$active_addons[$slug] = new $addon['class']();
            }
        }
    }
    
    /**
     * Get active add-on instance
     * 
     * @param string $slug
     * @return object|false
     */
    public static function get_active_addon($slug) {
        return isset(self::$active_addons[$slug]) ? self::$active_addons[$slug] : false;
    }
    
    /**
     * Register admin page
     */
    public function register_admin_page() {
        add_submenu_page(
            'ensemble',
            __('Add-ons', 'ensemble'),
            __('Add-ons', 'ensemble'),
            'manage_options',
            'ensemble-addons',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        $is_pro = self::is_pro_active();
        $addons = self::get_addons();
        
        include ENSEMBLE_PLUGIN_DIR . 'admin/addons.php';
    }
    
    /**
     * Enqueue admin assets
     * 
     * @param string $hook
     */
    public function enqueue_admin_assets($hook) {
        if ($hook !== 'ensemble_page_ensemble-addons') {
            return;
        }
        
        wp_enqueue_style(
            'ensemble-addons',
            ENSEMBLE_PLUGIN_URL . 'assets/css/addons.css',
            array(),
            ENSEMBLE_VERSION
        );
        
        wp_enqueue_script(
            'ensemble-addons',
            ENSEMBLE_PLUGIN_URL . 'assets/js/addons.js',
            array('jquery'),
            ENSEMBLE_VERSION,
            true
        );
        
        wp_localize_script('ensemble-addons', 'ensembleAddons', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('ensemble_addons'),
            'isPro'   => self::is_pro_active(),
            'strings' => array(
                'activating'   => __('Aktiviere...', 'ensemble'),
                'deactivating' => __('Deaktiviere...', 'ensemble'),
                'error'        => __('Error', 'ensemble'),
                'success'      => __('Success', 'ensemble'),
            ),
        ));
    }
    
    /**
     * AJAX: Toggle add-on
     */
    public function ajax_toggle_addon() {
        check_ajax_referer('ensemble_addons', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'ensemble')));
        }
        
        $slug = isset($_POST['slug']) ? sanitize_key($_POST['slug']) : '';
        
        // CRITICAL: Proper boolean conversion
        // JavaScript sends true/false as strings "1"/"0" or "true"/"false"
        $activate_raw = isset($_POST['activate']) ? $_POST['activate'] : false;
        
        if ($activate_raw === 'false' || $activate_raw === '0' || $activate_raw === 0 || $activate_raw === false) {
            $activate = false;
        } else {
            $activate = (bool)$activate_raw;
        }
        
        
        if (empty($slug)) {
            wp_send_json_error(array('message' => __('Invalid add-on slug.', 'ensemble')));
        }
        
        if ($activate) {
            $result = self::activate_addon($slug);
        } else {
            $result = self::deactivate_addon($slug);
        }
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        $is_now_active = self::is_addon_active($slug);
        
        wp_send_json_success(array(
            'message' => $activate ? 
                __('Add-on activated.', 'ensemble') : 
                __('Add-on deactivated.', 'ensemble'),
            'active' => $is_now_active,
        ));
    }
    
    /**
     * AJAX: Save add-on settings
     */
    public function ajax_save_addon_settings() {
        check_ajax_referer('ensemble_addons', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'ensemble')));
        }
        
        $slug = isset($_POST['slug']) ? sanitize_key($_POST['slug']) : '';
        $settings = isset($_POST['settings']) ? $_POST['settings'] : array();
        
        if (empty($slug)) {
            wp_send_json_error(array('message' => __('Invalid add-on slug.', 'ensemble')));
        }
        
        $addon_instance = self::get_active_addon($slug);
        
        if ($addon_instance && method_exists($addon_instance, 'sanitize_settings')) {
            $settings = $addon_instance->sanitize_settings($settings);
            $addon_instance->update_settings($settings);
        } else {
            update_option("ensemble_addon_{$slug}_settings", $settings);
        }
        
        wp_send_json_success(array('message' => __('Settings saved.', 'ensemble')));
    }
    
    /**
     * AJAX: Get add-on settings HTML
     */
    public function ajax_get_addon_settings() {
        check_ajax_referer('ensemble_addons', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'ensemble')));
        }
        
        $slug = isset($_POST['slug']) ? sanitize_key($_POST['slug']) : '';
        
        if (empty($slug)) {
            wp_send_json_error(array('message' => __('Invalid add-on slug.', 'ensemble')));
        }
        
        // Check if addon is active
        if (!self::is_addon_active($slug)) {
            wp_send_json_error(array('message' => __('Add-on is not activated.', 'ensemble')));
        }
        
        $addon_instance = self::get_active_addon($slug);
        
        if (!$addon_instance) {
            wp_send_json_error(array('message' => __('Add-on Instanz nicht gefunden.', 'ensemble')));
        }
        
        if (!method_exists($addon_instance, 'render_settings')) {
            wp_send_json_error(array('message' => __('Add-on has no settings.', 'ensemble')));
        }
        
        $html = $addon_instance->render_settings();
        
        if (empty($html)) {
            wp_send_json_error(array('message' => __('Settings-HTML ist leer.', 'ensemble')));
        }
        
        wp_send_json_success(array('html' => $html));
    }
    
    /**
     * Register add-on hook
     * 
     * @param string $hook_name Hook name
     * @param string $addon_slug Add-on slug
     * @param callable $callback Callback function
     * @param int $priority Priority
     */
    public static function register_hook($hook_name, $addon_slug, $callback, $priority = 10) {
        if (!isset(self::$addon_hooks[$hook_name])) {
            self::$addon_hooks[$hook_name] = array();
        }
        
        self::$addon_hooks[$hook_name][] = array(
            'addon'    => $addon_slug,
            'callback' => $callback,
            'priority' => $priority,
        );
        
        // Sort by priority
        usort(self::$addon_hooks[$hook_name], function($a, $b) {
            return $a['priority'] - $b['priority'];
        });
    }
    
    /**
     * Execute add-on hooks
     * Called from templates
     * 
     * @param string $hook_name Hook name
     * @param mixed ...$args Arguments to pass to callbacks
     */
    public static function do_addon_hook($hook_name, ...$args) {
        if (!isset(self::$addon_hooks[$hook_name])) {
            return;
        }
        
        foreach (self::$addon_hooks[$hook_name] as $hook) {
            // Only execute if add-on is active
            if (self::is_addon_active($hook['addon'])) {
                call_user_func_array($hook['callback'], $args);
            }
        }
    }
    
    /**
     * Check if hook has any registered callbacks
     * 
     * @param string $hook_name Hook name
     * @return bool
     */
    public static function has_hook($hook_name) {
        return isset(self::$addon_hooks[$hook_name]) && !empty(self::$addon_hooks[$hook_name]);
    }
}
