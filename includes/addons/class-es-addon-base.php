<?php
/**
 * Ensemble Add-on Base Class
 * 
 * Abstract base class for all Ensemble add-ons
 * Provides common functionality, settings handling, and template loading
 *
 * @package Ensemble
 * @subpackage Addons
 * @since 2.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

abstract class ES_Addon_Base {
    
    /**
     * Add-on slug
     * @var string
     */
    protected $slug = '';
    
    /**
     * Add-on name
     * @var string
     */
    protected $name = '';
    
    /**
     * Add-on version
     * @var string
     */
    protected $version = '1.0.0';
    
    /**
     * Add-on settings
     * @var array
     */
    protected $settings = array();
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->load_settings();
        $this->init();
        $this->register_hooks();
    }
    
    /**
     * Initialize add-on
     * Override in child class
     */
    abstract protected function init();
    
    /**
     * Register hooks
     * Override in child class
     */
    abstract protected function register_hooks();
    
    /**
     * Load settings
     */
    protected function load_settings() {
        $this->settings = get_option("ensemble_addon_{$this->slug}_settings", array());
    }
    
    /**
     * Get setting
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function get_setting($key, $default = '') {
        return isset($this->settings[$key]) ? $this->settings[$key] : $default;
    }
    
    /**
     * Update setting
     * 
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    protected function update_setting($key, $value) {
        $this->settings[$key] = $value;
        return update_option("ensemble_addon_{$this->slug}_settings", $this->settings);
    }
    
    /**
     * Get all settings
     * 
     * @return array
     */
    public function get_settings() {
        return $this->settings;
    }
    
    /**
     * Update all settings
     * 
     * @param array $settings
     * @return bool
     */
    public function update_settings($settings) {
        $this->settings = $settings;
        return update_option("ensemble_addon_{$this->slug}_settings", $this->settings);
    }
    
    /**
     * Render settings page
     * Override in child class
     * 
     * @return string
     */
    public function render_settings() {
        return '<p>' . __('No settings available.', 'ensemble') . '</p>';
    }
    
    /**
     * Sanitize settings
     * Override in child class
     * 
     * @param array $settings
     * @return array
     */
    public function sanitize_settings($settings) {
        return $settings;
    }
    
    /**
     * Register template hook
     * 
     * @param string $hook_name
     * @param callable $callback
     * @param int $priority
     */
    protected function register_template_hook($hook_name, $callback, $priority = 10) {
        ES_Addon_Manager::register_hook($hook_name, $this->slug, $callback, $priority);
    }
    
    /**
     * Load template
     * 
     * @param string $template_name
     * @param array $args
     * @return string
     */
    protected function load_template($template_name, $args = array()) {
        $template_path = $this->get_addon_path() . "templates/{$template_name}.php";
        
        // Debug logging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->log("Loading template: {$template_name} from {$template_path}");
        }
        
        if (!file_exists($template_path)) {
            // Log error if template not found
            $this->log("Template not found: {$template_path}", 'error');
            
            // Try fallback templates for gallery layouts
            if (strpos($template_name, 'gallery-') === 0 && $template_name !== 'gallery-grid') {
                $this->log("Falling back to grid template", 'warning');
                $fallback_path = $this->get_addon_path() . "templates/gallery-grid.php";
                if (file_exists($fallback_path)) {
                    $template_path = $fallback_path;
                } else {
                    return '<!-- Template not found: ' . esc_html($template_name) . ' -->';
                }
            } else {
                return '';
            }
        }
        
        // Extract args to variables
        extract($args);
        
        ob_start();
        include $template_path;
        $output = ob_get_clean();
        
        // Verify output was generated
        if (empty($output) && defined('WP_DEBUG') && WP_DEBUG) {
            $this->log("Template {$template_name} produced empty output", 'warning');
        }
        
        return $output;
    }
    
    /**
     * Get addon directory path
     * 
     * @return string
     */
    protected function get_addon_path() {
        return ENSEMBLE_PLUGIN_DIR . "includes/addons/{$this->slug}/";
    }
    
    /**
     * Get addon URL
     * 
     * @return string
     */
    protected function get_addon_url() {
        return ENSEMBLE_PLUGIN_URL . "includes/addons/{$this->slug}/";
    }
    
    /**
     * Log message
     * 
     * @param string $message
     * @param string $level
     */
    protected function log($message, $level = 'info') {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Ensemble {$this->name}] [{$level}] {$message}");
        }
    }
}
