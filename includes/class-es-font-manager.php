<?php
/**
 * Ensemble Font Manager
 * 
 * Handles Google Fonts and Custom Font uploads
 * 
 * @package Ensemble
 * @since 2.7.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class ES_Font_Manager {
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * Curated fonts for free version
     */
    private $curated_fonts = array(
        // Sans-Serif
        'Inter' => array(
            'category' => 'sans-serif',
            'variants' => array('300', '400', '500', '600', '700'),
            'preview' => 'Modern & Clean'
        ),
        'Poppins' => array(
            'category' => 'sans-serif',
            'variants' => array('300', '400', '500', '600', '700'),
            'preview' => 'Geometric & Friendly'
        ),
        'Roboto' => array(
            'category' => 'sans-serif',
            'variants' => array('300', '400', '500', '700'),
            'preview' => 'Google Standard'
        ),
        'Open Sans' => array(
            'category' => 'sans-serif',
            'variants' => array('300', '400', '600', '700'),
            'preview' => 'Neutral & Readable'
        ),
        'Montserrat' => array(
            'category' => 'sans-serif',
            'variants' => array('300', '400', '500', '600', '700'),
            'preview' => 'Urban & Bold'
        ),
        'Lato' => array(
            'category' => 'sans-serif',
            'variants' => array('300', '400', '700'),
            'preview' => 'Warm & Stable'
        ),
        'Source Sans Pro' => array(
            'category' => 'sans-serif',
            'variants' => array('300', '400', '600', '700'),
            'preview' => 'Adobe Classic'
        ),
        'Nunito' => array(
            'category' => 'sans-serif',
            'variants' => array('300', '400', '600', '700'),
            'preview' => 'Rounded & Soft'
        ),
        'Raleway' => array(
            'category' => 'sans-serif',
            'variants' => array('300', '400', '500', '600', '700'),
            'preview' => 'Elegant Sans'
        ),
        'Work Sans' => array(
            'category' => 'sans-serif',
            'variants' => array('300', '400', '500', '600', '700'),
            'preview' => 'Optimized for Screens'
        ),
        'DM Sans' => array(
            'category' => 'sans-serif',
            'variants' => array('400', '500', '700'),
            'preview' => 'Low Contrast Geometric'
        ),
        'Manrope' => array(
            'category' => 'sans-serif',
            'variants' => array('300', '400', '500', '600', '700'),
            'preview' => 'Modern Variable'
        ),
        'Space Grotesk' => array(
            'category' => 'sans-serif',
            'variants' => array('300', '400', '500', '600', '700'),
            'preview' => 'Techy & Futuristic'
        ),
        'Outfit' => array(
            'category' => 'sans-serif',
            'variants' => array('300', '400', '500', '600', '700'),
            'preview' => 'Clean & Contemporary'
        ),
        
        // Serif
        'Playfair Display' => array(
            'category' => 'serif',
            'variants' => array('400', '500', '600', '700'),
            'preview' => 'Elegant Headlines'
        ),
        'Merriweather' => array(
            'category' => 'serif',
            'variants' => array('300', '400', '700'),
            'preview' => 'Screen-optimized Serif'
        ),
        'Lora' => array(
            'category' => 'serif',
            'variants' => array('400', '500', '600', '700'),
            'preview' => 'Contemporary Serif'
        ),
        'Source Serif Pro' => array(
            'category' => 'serif',
            'variants' => array('300', '400', '600', '700'),
            'preview' => 'Adobe Serif'
        ),
        'Crimson Pro' => array(
            'category' => 'serif',
            'variants' => array('300', '400', '500', '600', '700'),
            'preview' => 'Book Typography'
        ),
        'DM Serif Display' => array(
            'category' => 'serif',
            'variants' => array('400'),
            'preview' => 'Display Headlines'
        ),
        'Cormorant Garamond' => array(
            'category' => 'serif',
            'variants' => array('300', '400', '500', '600', '700'),
            'preview' => 'Classic Garamond'
        ),
        
        // Display
        'Oswald' => array(
            'category' => 'display',
            'variants' => array('300', '400', '500', '600', '700'),
            'preview' => 'Condensed Impact'
        ),
        'Bebas Neue' => array(
            'category' => 'display',
            'variants' => array('400'),
            'preview' => 'Bold Headlines'
        ),
        'Anton' => array(
            'category' => 'display',
            'variants' => array('400'),
            'preview' => 'Heavy Impact'
        ),
        'Abril Fatface' => array(
            'category' => 'display',
            'variants' => array('400'),
            'preview' => 'Elegant Display'
        ),
        'Righteous' => array(
            'category' => 'display',
            'variants' => array('400'),
            'preview' => 'Retro Fun'
        ),
        
        // Monospace
        'JetBrains Mono' => array(
            'category' => 'monospace',
            'variants' => array('300', '400', '500', '600', '700'),
            'preview' => 'Developer Favorite'
        ),
        'Fira Code' => array(
            'category' => 'monospace',
            'variants' => array('300', '400', '500', '600', '700'),
            'preview' => 'Coding Ligatures'
        ),
        'Source Code Pro' => array(
            'category' => 'monospace',
            'variants' => array('300', '400', '500', '600', '700'),
            'preview' => 'Adobe Mono'
        ),
    );
    
    /**
     * System fonts
     */
    private $system_fonts = array(
        'System Default' => array(
            'category' => 'system',
            'stack' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
            'preview' => 'Native OS Font'
        ),
        'System Serif' => array(
            'category' => 'system',
            'stack' => 'Georgia, "Times New Roman", Times, serif',
            'preview' => 'Native Serif'
        ),
        'System Mono' => array(
            'category' => 'system',
            'stack' => '"SF Mono", Monaco, Consolas, "Liberation Mono", "Courier New", monospace',
            'preview' => 'Native Monospace'
        ),
    );
    
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
        add_action('wp_enqueue_scripts', array($this, 'enqueue_fonts'), 5);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_fonts'));
        add_action('wp_ajax_es_search_google_fonts', array($this, 'ajax_search_fonts'));
        add_action('wp_ajax_es_upload_custom_font', array($this, 'ajax_upload_font'));
        add_action('wp_ajax_es_remove_custom_font', array($this, 'ajax_remove_font'));
    }
    
    /**
     * Get font settings
     */
    public function get_settings() {
        $defaults = array(
            'heading_font' => 'System Default',
            'heading_weight' => '700',
            'body_font' => 'System Default',
            'body_weight' => '400',
            'custom_fonts' => array(),
        );
        
        $settings = get_option('ensemble_typography', array());
        return wp_parse_args($settings, $defaults);
    }
    
    /**
     * Save settings
     */
    public function save_settings($settings) {
        $sanitized = array(
            'heading_font' => sanitize_text_field($settings['heading_font'] ?? 'System Default'),
            'heading_weight' => sanitize_text_field($settings['heading_weight'] ?? '700'),
            'body_font' => sanitize_text_field($settings['body_font'] ?? 'System Default'),
            'body_weight' => sanitize_text_field($settings['body_weight'] ?? '400'),
            'custom_fonts' => isset($settings['custom_fonts']) ? $this->sanitize_custom_fonts($settings['custom_fonts']) : array(),
        );
        
        update_option('ensemble_typography', $sanitized);
    }
    
    /**
     * Sanitize custom fonts array
     */
    private function sanitize_custom_fonts($fonts) {
        if (!is_array($fonts)) return array();
        
        $sanitized = array();
        foreach ($fonts as $font) {
            if (!empty($font['name']) && !empty($font['url'])) {
                $sanitized[] = array(
                    'name' => sanitize_text_field($font['name']),
                    'url' => esc_url_raw($font['url']),
                    'weight' => sanitize_text_field($font['weight'] ?? '400'),
                    'style' => sanitize_text_field($font['style'] ?? 'normal'),
                );
            }
        }
        return $sanitized;
    }
    
    /**
     * Get curated fonts (Free)
     */
    public function get_curated_fonts() {
        return $this->curated_fonts;
    }
    
    /**
     * Get system fonts
     */
    public function get_system_fonts() {
        return $this->system_fonts;
    }
    
    /**
     * Get all Google Fonts (Pro)
     */
    public function get_all_google_fonts() {
        // Check if Pro
        if (!$this->is_pro()) {
            return $this->curated_fonts;
        }
        
        // Load from JSON file
        $json_file = ENSEMBLE_PLUGIN_DIR . 'assets/data/google-fonts.json';
        
        if (!file_exists($json_file)) {
            return $this->curated_fonts;
        }
        
        $json = file_get_contents($json_file);
        $fonts = json_decode($json, true);
        
        return is_array($fonts) ? $fonts : $this->curated_fonts;
    }
    
    /**
     * Get custom fonts (Pro)
     */
    public function get_custom_fonts() {
        if (!$this->is_pro()) {
            return array();
        }
        
        $settings = $this->get_settings();
        return $settings['custom_fonts'] ?? array();
    }
    
    /**
     * Check if Pro
     */
    private function is_pro() {
        return function_exists('ensemble_is_pro') && ensemble_is_pro();
    }
    
    /**
     * Get font stack for CSS
     */
    public function get_font_stack($font_name) {
        // System fonts
        if (isset($this->system_fonts[$font_name])) {
            return $this->system_fonts[$font_name]['stack'];
        }
        
        // Custom fonts
        $custom_fonts = $this->get_custom_fonts();
        foreach ($custom_fonts as $font) {
            if ($font['name'] === $font_name) {
                return '"' . $font_name . '", sans-serif';
            }
        }
        
        // Google fonts
        return '"' . $font_name . '", sans-serif';
    }
    
    /**
     * Enqueue fonts on frontend
     */
    public function enqueue_fonts() {
        $settings = $this->get_settings();
        $fonts_to_load = array();
        
        // Also check Designer settings (ES_Design_Settings)
        if (class_exists('ES_Design_Settings')) {
            $designer_settings = ES_Design_Settings::get_mode_settings('light');
            if (!empty($designer_settings['heading_font'])) {
                $settings['heading_font'] = $designer_settings['heading_font'];
            }
            if (!empty($designer_settings['body_font'])) {
                $settings['body_font'] = $designer_settings['body_font'];
            }
            if (!empty($designer_settings['heading_weight'])) {
                $settings['heading_weight'] = $designer_settings['heading_weight'];
            }
            if (!empty($designer_settings['body_weight'])) {
                $settings['body_weight'] = $designer_settings['body_weight'];
            }
        }
        
        // Collect Google fonts to load
        foreach (array('heading_font', 'body_font') as $key) {
            $font = $settings[$key];
            $weight = $key === 'heading_font' ? $settings['heading_weight'] : $settings['body_weight'];
            
            // Skip system fonts
            if (isset($this->system_fonts[$font])) {
                continue;
            }
            
            // Skip empty or "System Default"
            if (empty($font) || $font === 'System Default') {
                continue;
            }
            
            // Check if it's a Google font
            $all_fonts = array_merge($this->curated_fonts, $this->get_all_google_fonts());
            if (isset($all_fonts[$font])) {
                if (!isset($fonts_to_load[$font])) {
                    $fonts_to_load[$font] = array();
                }
                $fonts_to_load[$font][] = (string)$weight;
                // Also add 400 for body text
                if ($weight !== '400' && $weight !== 400) {
                    $fonts_to_load[$font][] = '400';
                }
                // Add 600 and 700 for headings
                if ($key === 'heading_font') {
                    $fonts_to_load[$font][] = '600';
                    $fonts_to_load[$font][] = '700';
                }
            }
        }
        
        // Build Google Fonts URL
        if (!empty($fonts_to_load)) {
            $families = array();
            foreach ($fonts_to_load as $font => $weights) {
                $weights = array_unique($weights);
                sort($weights);
                $families[] = str_replace(' ', '+', $font) . ':wght@' . implode(';', $weights);
            }
            
            $url = 'https://fonts.googleapis.com/css2?family=' . implode('&family=', $families) . '&display=swap';
            wp_enqueue_style('ensemble-google-fonts', $url, array(), null);
        }
        
        // Enqueue custom fonts
        $custom_fonts = $this->get_custom_fonts();
        if (!empty($custom_fonts)) {
            $this->enqueue_custom_fonts($custom_fonts);
        }
        
        // Output CSS variables
        add_action('wp_head', array($this, 'output_font_css'), 5);
    }
    
    /**
     * Enqueue custom fonts
     */
    private function enqueue_custom_fonts($fonts) {
        $css = '';
        foreach ($fonts as $font) {
            $css .= sprintf(
                '@font-face { font-family: "%s"; src: url("%s") format("woff2"); font-weight: %s; font-style: %s; font-display: swap; }',
                esc_attr($font['name']),
                esc_url($font['url']),
                esc_attr($font['weight']),
                esc_attr($font['style'])
            );
        }
        
        if ($css) {
            wp_add_inline_style('ensemble-style', $css);
        }
    }
    
    /**
     * Output font CSS variables
     */
    public function output_font_css() {
        $settings = $this->get_settings();
        
        $heading_stack = $this->get_font_stack($settings['heading_font']);
        $body_stack = $this->get_font_stack($settings['body_font']);
        
        echo '<style id="ensemble-typography-vars">
:root {
    --ensemble-font-heading: ' . $heading_stack . ';
    --ensemble-font-body: ' . $body_stack . ';
    --ensemble-heading-weight: ' . esc_attr($settings['heading_weight']) . ';
    --ensemble-body-weight: ' . esc_attr($settings['body_weight']) . ';
}
</style>';
    }
    
    /**
     * Enqueue fonts in admin for preview
     */
    public function enqueue_admin_fonts($hook) {
        if (strpos($hook, 'ensemble') === false) {
            return;
        }
        
        // Load curated fonts for preview
        $families = array();
        foreach ($this->curated_fonts as $font => $data) {
            $weights = array_slice($data['variants'], 0, 3); // Load max 3 weights
            $families[] = str_replace(' ', '+', $font) . ':wght@' . implode(';', $weights);
        }
        
        $url = 'https://fonts.googleapis.com/css2?family=' . implode('&family=', $families) . '&display=swap';
        wp_enqueue_style('ensemble-admin-fonts', $url, array(), null);
    }
    
    /**
     * AJAX: Search Google Fonts (Pro)
     */
    public function ajax_search_fonts() {
        check_ajax_referer('ensemble_admin', 'nonce');
        
        if (!$this->is_pro()) {
            wp_send_json_error('Pro feature');
        }
        
        $search = sanitize_text_field($_POST['search'] ?? '');
        $category = sanitize_text_field($_POST['category'] ?? '');
        
        $all_fonts = $this->get_all_google_fonts();
        $results = array();
        
        foreach ($all_fonts as $name => $data) {
            // Filter by search
            if ($search && stripos($name, $search) === false) {
                continue;
            }
            
            // Filter by category
            if ($category && $data['category'] !== $category) {
                continue;
            }
            
            $results[$name] = $data;
            
            // Limit results
            if (count($results) >= 50) {
                break;
            }
        }
        
        wp_send_json_success($results);
    }
    
    /**
     * AJAX: Upload custom font (Pro)
     */
    public function ajax_upload_font() {
        check_ajax_referer('ensemble_admin', 'nonce');
        
        if (!$this->is_pro()) {
            wp_send_json_error('Pro feature');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        if (empty($_FILES['font_file'])) {
            wp_send_json_error('No file uploaded');
        }
        
        $file = $_FILES['font_file'];
        $allowed = array('woff', 'woff2', 'ttf', 'otf');
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed)) {
            wp_send_json_error('Invalid file type. Allowed: ' . implode(', ', $allowed));
        }
        
        // Upload to custom fonts directory
        $upload_dir = wp_upload_dir();
        $fonts_dir = $upload_dir['basedir'] . '/ensemble-fonts/';
        
        if (!file_exists($fonts_dir)) {
            wp_mkdir_p($fonts_dir);
        }
        
        $filename = sanitize_file_name($file['name']);
        $destination = $fonts_dir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $url = $upload_dir['baseurl'] . '/ensemble-fonts/' . $filename;
            $name = sanitize_text_field($_POST['font_name'] ?? pathinfo($filename, PATHINFO_FILENAME));
            $weight = sanitize_text_field($_POST['font_weight'] ?? '400');
            $style = sanitize_text_field($_POST['font_style'] ?? 'normal');
            
            // Add to settings
            $settings = $this->get_settings();
            $settings['custom_fonts'][] = array(
                'name' => $name,
                'url' => $url,
                'weight' => $weight,
                'style' => $style,
            );
            $this->save_settings($settings);
            
            wp_send_json_success(array(
                'name' => $name,
                'url' => $url,
            ));
        }
        
        wp_send_json_error('Upload failed');
    }
    
    /**
     * AJAX: Remove custom font (Pro)
     */
    public function ajax_remove_font() {
        check_ajax_referer('ensemble_admin', 'nonce');
        
        if (!$this->is_pro()) {
            wp_send_json_error('Pro feature');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        $index = intval($_POST['index'] ?? -1);
        
        if ($index < 0) {
            wp_send_json_error('Invalid index');
        }
        
        $settings = $this->get_settings();
        
        if (!isset($settings['custom_fonts'][$index])) {
            wp_send_json_error('Font not found');
        }
        
        // Remove font file
        $font = $settings['custom_fonts'][$index];
        if (!empty($font['url'])) {
            $upload_dir = wp_upload_dir();
            $file_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $font['url']);
            if (file_exists($file_path)) {
                @unlink($file_path);
            }
        }
        
        // Remove from settings
        array_splice($settings['custom_fonts'], $index, 1);
        $this->save_settings($settings);
        
        wp_send_json_success();
    }
    
    /**
     * Render typography settings page
     */
    public function render_settings_page() {
        $settings = $this->get_settings();
        $curated = $this->get_curated_fonts();
        $system = $this->get_system_fonts();
        $custom = $this->get_custom_fonts();
        $is_pro = $this->is_pro();
        
        // Group curated fonts by category
        $by_category = array(
            'system' => $system,
            'sans-serif' => array(),
            'serif' => array(),
            'display' => array(),
            'monospace' => array(),
        );
        
        foreach ($curated as $name => $data) {
            $cat = $data['category'];
            if (isset($by_category[$cat])) {
                $by_category[$cat][$name] = $data;
            }
        }
        
        include ENSEMBLE_PLUGIN_DIR . 'admin/views/typography-settings.php';
    }
}

// Initialize
ES_Font_Manager::instance();
