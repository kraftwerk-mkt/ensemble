<?php
/**
 * Ensemble Social Sharing Add-on
 * 
 * Teilen-Buttons für Events (Facebook, X, WhatsApp, Telegram, E-Mail, Link kopieren)
 * Plus native Web Share API für Mobile
 * 
 * @package Ensemble
 * @subpackage Addons
 * @updated Color Style Option (brand/theme)
 */

if (!defined('ABSPATH')) {
    exit;
}

class ES_Social_Sharing_Addon extends ES_Addon_Base {
    
    /**
     * Addon slug
     */
    protected $slug = 'social-sharing';
    
    /**
     * Addon Name
     */
    protected $name = 'Social Sharing';
    
    /**
     * Addon Version
     */
    protected $version = '1.1.0';
    
    /**
     * Initialize addon
     */
    protected function init() {
        if (empty($this->settings)) {
            $this->settings = $this->get_default_settings();
        } else {
            $this->settings = wp_parse_args($this->settings, $this->get_default_settings());
        }
    }
    
    /**
     * Register hooks
     */
    protected function register_hooks() {
        // Register addon hook - nach dem Event-Content
        $this->register_template_hook('ensemble_after_event', array($this, 'render_share_buttons'), 5);
        
        // Enqueue assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
    }
    
    /**
     * Get default settings
     */
    public function get_default_settings() {
        return array(
            'enabled' => true,
            'title' => __('Teilen', 'ensemble'),
            'show_facebook' => true,
            'show_twitter' => true,
            'show_whatsapp' => true,
            'show_telegram' => true,
            'show_linkedin' => false,
            'show_email' => true,
            'show_copy_link' => true,
            'show_native_share' => true,
            'style' => 'icons',              // icons, icons-text, text
            'position' => 'after-content',   // after-content, floating
            'icon_style' => 'rounded',       // rounded, square, circle
            'color_style' => 'brand',        // brand, theme, outline
        );
    }
    
    /**
     * Check if addon is active
     */
    public function is_active() {
        return ES_Addon_Manager::is_addon_active($this->slug);
    }
    
    /**
     * Get settings
     */
    public function get_settings() {
        return wp_parse_args($this->settings, $this->get_default_settings());
    }
    
    /**
     * Sanitize settings
     */
    public function sanitize_settings($settings) {
        $defaults = $this->get_default_settings();
        $sanitized = array();
        
        // Text fields
        $sanitized['title'] = isset($settings['title']) ? sanitize_text_field($settings['title']) : $defaults['title'];
        
        // Select fields
        $sanitized['style'] = isset($settings['style']) ? sanitize_key($settings['style']) : $defaults['style'];
        $sanitized['position'] = isset($settings['position']) ? sanitize_key($settings['position']) : $defaults['position'];
        $sanitized['icon_style'] = isset($settings['icon_style']) ? sanitize_key($settings['icon_style']) : $defaults['icon_style'];
        $sanitized['color_style'] = isset($settings['color_style']) ? sanitize_key($settings['color_style']) : $defaults['color_style'];
        
        // Boolean fields
        $boolean_fields = array(
            'enabled', 'show_facebook', 'show_twitter', 'show_whatsapp', 
            'show_telegram', 'show_linkedin', 'show_email', 'show_copy_link', 'show_native_share'
        );
        
        foreach ($boolean_fields as $field) {
            $sanitized[$field] = $this->sanitize_boolean($settings, $field, $defaults[$field]);
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize boolean value
     */
    private function sanitize_boolean($settings, $key, $default) {
        if (!isset($settings[$key])) {
            return $default;
        }
        
        $value = $settings[$key];
        
        if ($value === 'true' || $value === '1' || $value === 1 || $value === true) {
            return true;
        }
        
        if ($value === 'false' || $value === '0' || $value === 0 || $value === false || $value === '') {
            return false;
        }
        
        return $default;
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        if (!$this->is_active()) {
            return;
        }
        
        if (!is_singular('ensemble_event') && !is_singular('post')) {
            return;
        }
        
        wp_enqueue_style(
            'es-social-sharing',
            $this->get_addon_url() . 'assets/social-sharing.css',
            array(),
            $this->version
        );
        
        wp_enqueue_script(
            'es-social-sharing',
            $this->get_addon_url() . 'assets/social-sharing.js',
            array('jquery'),
            $this->version,
            true
        );
        
        wp_localize_script('es-social-sharing', 'esSocialSharing', array(
            'copied' => __('Link kopiert!', 'ensemble'),
            'copyError' => __('Kopieren fehlgeschlagen', 'ensemble'),
            'shareTitle' => __('Teilen', 'ensemble'),
        ));
    }
    
    /**
     * Render settings page
     */
    public function render_settings() {
        $settings = $this->get_settings();
        
        ob_start();
        include $this->get_addon_path() . 'templates/settings.php';
        return ob_get_clean();
    }
    
    /**
     * Render share buttons
     * 
     * @param int $event_id Event ID
     * @param array $context Context data
     */
    public function render_share_buttons($event_id, $context = array()) {
        // Check display settings
        if (function_exists('ensemble_show_addon') && !ensemble_show_addon('social_sharing')) {
            return;
        }
        
        $settings = $this->get_settings();
        
        // Get share data
        $share_data = $this->get_share_data($event_id);
        
        // Render template
        echo $this->load_template('share-buttons', array(
            'event_id' => $event_id,
            'share_data' => $share_data,
            'settings' => $settings,
        ));
    }
    
    /**
     * Get share data for event
     */
    private function get_share_data($event_id) {
        $title = get_the_title($event_id);
        $url = get_permalink($event_id);
        $excerpt = get_the_excerpt($event_id);
        
        // Get event date
        $start_date = get_post_meta($event_id, '_event_start_date', true);
        $formatted_date = $start_date ? date_i18n(get_option('date_format'), strtotime($start_date)) : '';
        
        // Build share text
        $share_text = $title;
        if ($formatted_date) {
            $share_text .= ' - ' . $formatted_date;
        }
        
        // Get featured image for native share
        $image = get_the_post_thumbnail_url($event_id, 'large');
        
        return array(
            'title' => $title,
            'url' => $url,
            'text' => $share_text,
            'excerpt' => $excerpt,
            'image' => $image,
            'encoded_url' => rawurlencode($url),
            'encoded_title' => rawurlencode($title),
            'encoded_text' => rawurlencode($share_text),
        );
    }
    
    /**
     * Get share URLs for each platform
     */
    public function get_share_urls($share_data) {
        return array(
            'facebook' => 'https://www.facebook.com/sharer/sharer.php?u=' . $share_data['encoded_url'],
            'twitter' => 'https://twitter.com/intent/tweet?url=' . $share_data['encoded_url'] . '&text=' . $share_data['encoded_text'],
            'whatsapp' => 'https://wa.me/?text=' . $share_data['encoded_text'] . '%20' . $share_data['encoded_url'],
            'telegram' => 'https://t.me/share/url?url=' . $share_data['encoded_url'] . '&text=' . $share_data['encoded_text'],
            'linkedin' => 'https://www.linkedin.com/sharing/share-offsite/?url=' . $share_data['encoded_url'],
            'email' => 'mailto:?subject=' . $share_data['encoded_title'] . '&body=' . $share_data['encoded_text'] . '%0A%0A' . $share_data['encoded_url'],
        );
    }
}
