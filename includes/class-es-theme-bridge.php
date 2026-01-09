<?php
/**
 * Theme Bridge
 * 
 * Stellt sicher, dass Ensemble Design-Variablen auf JEDER Seite verfügbar sind,
 * nicht nur auf Seiten mit Ensemble-Content.
 * 
 * Das ermöglicht dem Theme (Blog, Archive, Info-Seiten) das gleiche Design
 * wie die Event-Seiten zu verwenden.
 * 
 * @package Ensemble
 * @version 2.1.0
 */

if (!defined('ABSPATH')) exit;

class ES_Theme_Bridge {
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // CSS-Variablen auf JEDER Frontend-Seite laden
        add_action('wp_enqueue_scripts', array($this, 'enqueue_global_styles'), 5);
        
        // Body-Klassen hinzufügen
        add_filter('body_class', array($this, 'add_body_classes'));
        
        // Fonts global laden
        add_action('wp_enqueue_scripts', array($this, 'enqueue_fonts'), 5);
    }
    
    /**
     * Enqueue global design styles on EVERY page
     */
    public function enqueue_global_styles() {
        // Generiere CSS-Variablen
        $css = $this->generate_global_css();
        
        // Registriere leeres Stylesheet als Anker
        wp_register_style('ensemble-global-vars', false);
        wp_enqueue_style('ensemble-global-vars');
        
        // Füge CSS-Variablen hinzu
        wp_add_inline_style('ensemble-global-vars', $css);
    }
    
    /**
     * Generate global CSS variables
     */
    private function generate_global_css() {
        // Design Settings holen
        $settings = array();
        
        if (class_exists('ES_Design_Settings')) {
            $settings = ES_Design_Settings::get_mode_settings('light');
        }
        
        // Layout-Set Preset holen (falls vorhanden)
        $preset = $this->get_active_preset();
        
        // Preset überschreibt Design Settings
        if (!empty($preset)) {
            $settings = array_merge($settings, $preset);
        }
        
        // Defaults für fehlende Werte
        $defaults = array(
            'primary_color'    => '#cc2222',
            'secondary_color'  => '#ff6633',
            'background_color' => '#0a0a0f',
            'text_color'       => '#ffffff',
            'text_secondary'   => '#8a8a9a',
            'card_background'  => '#0f0f14',
            'card_border'      => '#1a1a22',
            'hover_color'      => '#ff4444',
            'heading_font'     => 'Playfair Display',
            'body_font'        => 'Lato',
            'heading_weight'   => 400,
            'body_weight'      => 300,
            'h1_size'          => 52,
            'h2_size'          => 36,
            'h3_size'          => 26,
            'h4_size'          => 20,
            'body_size'        => 16,
            'small_size'       => 14,
            'button_bg'        => '#cc2222',
            'button_text'      => '#ffffff',
            'button_hover_bg'  => '#ff4444',
            'button_hover_text'=> '#ffffff',
            'button_radius'    => 0,
            'button_padding_v' => 14,
            'button_padding_h' => 32,
            'button_weight'    => 700,
            'card_radius'      => 0,
            'card_padding'     => 24,
            'card_gap'         => 16,
            'section_spacing'  => 64,
        );
        
        $settings = wp_parse_args($settings, $defaults);
        
        // CSS generieren
        $css = "
/* ========================================
   Ensemble Global Design Variables
   Active Layout: " . $this->get_active_layout() . "
   Generated for Theme Integration
   ======================================== */

:root {
    /* Colors */
    --ensemble-primary: {$settings['primary_color']};
    --ensemble-secondary: {$settings['secondary_color']};
    --ensemble-bg: {$settings['background_color']};
    --ensemble-background: {$settings['background_color']};
    --ensemble-text: {$settings['text_color']};
    --ensemble-text-secondary: {$settings['text_secondary']};
    --ensemble-card-bg: {$settings['card_background']};
    --ensemble-card-background: {$settings['card_background']};
    --ensemble-card-border: {$settings['card_border']};
    --ensemble-hover: {$settings['hover_color']};
    
    /* Typography - Fonts */
    --ensemble-font-heading: '{$settings['heading_font']}', Georgia, serif;
    --ensemble-font-body: '{$settings['body_font']}', -apple-system, sans-serif;
    
    /* Typography - Sizes */
    --ensemble-h1-size: {$settings['h1_size']}px;
    --ensemble-h2-size: {$settings['h2_size']}px;
    --ensemble-h3-size: {$settings['h3_size']}px;
    --ensemble-h4-size: {$settings['h4_size']}px;
    --ensemble-body-size: {$settings['body_size']}px;
    --ensemble-small-size: {$settings['small_size']}px;
    
    /* Typography - Weights */
    --ensemble-heading-weight: {$settings['heading_weight']};
    --ensemble-body-weight: {$settings['body_weight']};
    
    /* Buttons */
    --ensemble-button-bg: {$settings['button_bg']};
    --ensemble-button-text: {$settings['button_text']};
    --ensemble-button-hover-bg: {$settings['button_hover_bg']};
    --ensemble-button-hover-text: {$settings['button_hover_text']};
    --ensemble-button-radius: {$settings['button_radius']}px;
    --ensemble-button-padding-v: {$settings['button_padding_v']}px;
    --ensemble-button-padding-h: {$settings['button_padding_h']}px;
    --ensemble-button-weight: {$settings['button_weight']};
    
    /* Cards */
    --ensemble-card-radius: {$settings['card_radius']}px;
    --ensemble-card-padding: {$settings['card_padding']}px;
    
    /* Layout */
    --ensemble-card-gap: {$settings['card_gap']}px;
    --ensemble-section-spacing: {$settings['section_spacing']}px;
}

/* Body Base Styles - Applied globally */
body.es-layout-active {
    background-color: var(--ensemble-bg);
    color: var(--ensemble-text);
    font-family: var(--ensemble-font-body);
    font-size: var(--ensemble-body-size);
    font-weight: var(--ensemble-body-weight);
}
";
        
        return $css;
    }
    
    /**
     * Get active layout preset
     */
    private function get_active_preset() {
        if (!class_exists('ES_Layout_Sets')) {
            return array();
        }
        
        $active_set = ES_Layout_Sets::get_active_set();
        $preset_file = ENSEMBLE_PLUGIN_DIR . 'templates/layouts/' . $active_set . '/preset.php';
        
        if (file_exists($preset_file)) {
            return include $preset_file;
        }
        
        return array();
    }
    
    /**
     * Get active layout name
     */
    private function get_active_layout() {
        if (!class_exists('ES_Layout_Sets')) {
            return 'default';
        }
        
        return ES_Layout_Sets::get_active_set();
    }
    
    /**
     * Add body classes for layout integration
     */
    public function add_body_classes($classes) {
        // Markiere dass Ensemble aktiv ist
        $classes[] = 'es-layout-active';
        
        // Aktives Layout-Set
        $active_layout = $this->get_active_layout();
        $classes[] = 'es-layout-' . sanitize_html_class($active_layout);
        
        // Light/Dark Mode
        if (class_exists('ES_Layout_Sets')) {
            $mode = ES_Layout_Sets::get_active_mode();
            $classes[] = 'es-mode-' . $mode;
        }
        
        return $classes;
    }
    
    /**
     * Enqueue fonts globally
     */
    public function enqueue_fonts() {
        $settings = array();
        
        if (class_exists('ES_Design_Settings')) {
            $settings = ES_Design_Settings::get_mode_settings('light');
        }
        
        // Preset überschreibt
        $preset = $this->get_active_preset();
        if (!empty($preset)) {
            $settings = array_merge($settings, $preset);
        }
        
        $heading_font = $settings['heading_font'] ?? 'Playfair Display';
        $body_font = $settings['body_font'] ?? 'Lato';
        
        // Google Fonts URL bauen
        $fonts = array();
        
        // Heading Font
        $heading_weights = '400;500;600;700';
        if ($heading_font === 'Playfair Display') {
            $heading_weights = '400;500;600;700&display=swap';
            $fonts[] = 'Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500;1,600;1,700';
        } else {
            $fonts[] = str_replace(' ', '+', $heading_font) . ':wght@' . $heading_weights;
        }
        
        // Body Font
        if ($body_font !== $heading_font) {
            $body_weights = '300;400;600;700';
            $fonts[] = str_replace(' ', '+', $body_font) . ':wght@' . $body_weights;
        }
        
        if (!empty($fonts)) {
            $font_url = 'https://fonts.googleapis.com/css2?family=' . implode('&family=', $fonts) . '&display=swap';
            wp_enqueue_style('ensemble-global-fonts', $font_url, array(), null);
        }
    }
}

// Initialize
ES_Theme_Bridge::get_instance();
