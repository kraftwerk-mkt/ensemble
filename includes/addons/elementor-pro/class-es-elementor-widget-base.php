<?php
/**
 * Base Widget Class for Ensemble Elementor Widgets
 * 
 * Provides common functionality for all Ensemble widgets.
 * Uses Ensemble's central CSS system for styling.
 * 
 * @package Ensemble
 * @subpackage Elementor
 * @since 3.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Safety check - only define this class if Elementor Widget_Base exists
if (!class_exists('\Elementor\Widget_Base')) {
    return;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

/**
 * Class ES_Elementor_Widget_Base
 * 
 * Abstract base class for all Ensemble widgets
 */
abstract class ES_Elementor_Widget_Base extends Widget_Base {
    
    /**
     * Widget category
     */
    protected $widget_category = 'ensemble';
    
    /**
     * Widget icon
     */
    protected $widget_icon = 'eicon-calendar';
    
    /**
     * Has pro features
     */
    protected $has_pro = false;
    
    /**
     * Required add-on (if any)
     */
    protected $required_addon = null;
    
    /**
     * Get widget categories
     */
    public function get_categories() {
        return array($this->widget_category);
    }
    
    /**
     * Get widget icon
     */
    public function get_icon() {
        return $this->widget_icon;
    }
    
    /**
     * Get widget keywords
     */
    public function get_keywords() {
        return array('ensemble', 'event', 'calendar');
    }
    
    /**
     * Get help URL
     */
    public function get_help_url() {
        return 'https://docs.ensemble-plugin.com/elementor-widgets/';
    }
    
    /**
     * Check if required add-on is active
     */
    protected function is_addon_active() {
        if (empty($this->required_addon)) {
            return true;
        }
        
        if (!class_exists('ES_Addon_Manager')) {
            return false;
        }
        
        return ES_Addon_Manager::instance()->is_addon_active($this->required_addon);
    }
    
    // =========================================================================
    // SHARED CONTROL GROUPS - FUNCTIONAL ONLY
    // Styling is handled by Ensemble's central CSS system (Designer)
    // =========================================================================
    
    /**
     * Add layout controls (columns, gap)
     * These override CSS variables for this specific widget instance
     */
    protected function add_layout_controls($default_columns = 3, $default_gap = 20) {
        $this->add_responsive_control(
            'columns',
            array(
                'label'          => __('Spalten', 'ensemble'),
                'type'           => Controls_Manager::SELECT,
                'default'        => $default_columns,
                'tablet_default' => 2,
                'mobile_default' => 1,
                'options'        => array(
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '5' => '5',
                    '6' => '6',
                ),
                'selectors' => array(
                    // Correct selectors matching actual shortcode HTML
                    '{{WRAPPER}} .ensemble-events-grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr) !important;',
                    '{{WRAPPER}} .ensemble-artists-grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr) !important;',
                    '{{WRAPPER}} .ensemble-locations-grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr) !important;',
                ),
            )
        );
        
        $this->add_responsive_control(
            'gap',
            array(
                'label'      => __('Abstand', 'ensemble'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => array('px', 'em'),
                'range'      => array(
                    'px' => array(
                        'min' => 0,
                        'max' => 100,
                    ),
                ),
                'default' => array(
                    'size' => $default_gap,
                    'unit' => 'px',
                ),
                'selectors' => array(
                    // Correct selectors matching actual shortcode HTML
                    '{{WRAPPER}} .ensemble-events-grid' => 'gap: {{SIZE}}{{UNIT}} !important;',
                    '{{WRAPPER}} .ensemble-artists-grid' => 'gap: {{SIZE}}{{UNIT}} !important;',
                    '{{WRAPPER}} .ensemble-locations-grid' => 'gap: {{SIZE}}{{UNIT}} !important;',
                ),
            )
        );
    }
    
    /**
     * Add limit/pagination controls
     */
    protected function add_query_controls($default_limit = 6) {
        $this->add_control(
            'limit',
            array(
                'label'   => __('Anzahl', 'ensemble'),
                'type'    => Controls_Manager::NUMBER,
                'default' => $default_limit,
                'min'     => 1,
                'max'     => 100,
            )
        );
        
        $this->add_control(
            'offset',
            array(
                'label'   => __('Offset', 'ensemble'),
                'type'    => Controls_Manager::NUMBER,
                'default' => 0,
                'min'     => 0,
            )
        );
        
        $this->add_control(
            'orderby',
            array(
                'label'   => __('Sortierung', 'ensemble'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'date',
                'options' => array(
                    'date'       => __('Datum', 'ensemble'),
                    'title'      => __('Titel', 'ensemble'),
                    'menu_order' => __('Menu order', 'ensemble'),
                    'rand'       => __('Random', 'ensemble'),
                ),
            )
        );
        
        $this->add_control(
            'order',
            array(
                'label'   => __('Reihenfolge', 'ensemble'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'ASC',
                'options' => array(
                    'ASC'  => __('Aufsteigend', 'ensemble'),
                    'DESC' => __('Absteigend', 'ensemble'),
                ),
            )
        );
    }
    
    /**
     * Add category filter control
     */
    protected function add_category_control($taxonomy = 'ensemble_category') {
        $categories = $this->get_taxonomy_options($taxonomy);
        
        $this->add_control(
            'category',
            array(
                'label'       => __('Kategorie', 'ensemble'),
                'type'        => Controls_Manager::SELECT2,
                'multiple'    => true,
                'options'     => $categories,
                'default'     => array(),
                'label_block' => true,
            )
        );
    }
    
    /**
     * Add location filter control
     * 
     * @param bool $for_filter If true, used as filter (multiple). If false, used for single selection.
     * @param bool $add_source_condition If true, adds condition for source='manual'
     */
    protected function add_location_control($for_filter = true, $add_source_condition = true) {
        $locations = $this->get_post_options('ensemble_location', $for_filter);
        
        $config = array(
            'label'       => __('Location', 'ensemble'),
            'type'        => Controls_Manager::SELECT2,
            'multiple'    => $for_filter,
            'options'     => $locations,
            'default'     => $for_filter ? array() : '',
            'label_block' => true,
        );
        
        // Add condition for manual source selection
        if ($add_source_condition && !$for_filter) {
            $config['condition'] = array('source' => 'manual');
        }
        
        $this->add_control('location', $config);
    }
    
    /**
     * Add artist filter control
     * 
     * @param bool $for_filter If true, used as filter (multiple). If false, used for single selection.
     * @param bool $add_source_condition If true, adds condition for source='manual'
     */
    protected function add_artist_control($for_filter = true, $add_source_condition = true) {
        $artists = $this->get_post_options('ensemble_artist', $for_filter);
        
        $config = array(
            'label'       => __('Artist', 'ensemble'),
            'type'        => Controls_Manager::SELECT2,
            'multiple'    => $for_filter,
            'options'     => $artists,
            'default'     => $for_filter ? array() : '',
            'label_block' => true,
        );
        
        // Add condition for manual source selection
        if ($add_source_condition && !$for_filter) {
            $config['condition'] = array('source' => 'manual');
        }
        
        $this->add_control('artist', $config);
    }
    
    /**
     * Add event selector control
     */
    protected function add_event_control($multiple = false) {
        // Use dynamic post type from settings
        $post_type = function_exists('ensemble_get_post_type') ? ensemble_get_post_type() : 'ensemble_event';
        $events = $this->get_post_options($post_type, false); // No "All" option for selection
        
        $this->add_control(
            'events',
            array(
                'label'       => $multiple ? __('Events', 'ensemble') : __('Event', 'ensemble'),
                'type'        => Controls_Manager::SELECT2,
                'multiple'    => $multiple,
                'options'     => $events,
                'default'     => $multiple ? array() : '',
                'label_block' => true,
                'condition'   => array(
                    'source' => 'manual',
                ),
            )
        );
    }
    
    /**
     * Add display toggle controls (show/hide elements)
     */
    protected function add_display_controls($defaults = array()) {
        $controls = array(
            'show_image'       => __('Show image', 'ensemble'),
            'show_date'        => __('Show date', 'ensemble'),
            'show_time'        => __('Show time', 'ensemble'),
            'show_location'    => __('Show location', 'ensemble'),
            'show_category'    => __('Show category', 'ensemble'),
            'show_price'       => __('Show price', 'ensemble'),
            'show_description' => __('Show description', 'ensemble'),
            'show_artists'     => __('Show artists', 'ensemble'),
            'show_button'      => __('Show button', 'ensemble'),
        );
        
        foreach ($controls as $key => $label) {
            if (!isset($defaults[$key])) {
                continue;
            }
            
            $this->add_control(
                $key,
                array(
                    'label'        => $label,
                    'type'         => Controls_Manager::SWITCHER,
                    'default'      => $defaults[$key] ? 'yes' : '',
                    'return_value' => 'yes',
                )
            );
        }
    }
    
    /**
     * Add info notice about central CSS system
     */
    protected function add_design_system_notice() {
        $this->add_control(
            'design_notice',
            array(
                'type' => Controls_Manager::RAW_HTML,
                'raw'  => sprintf(
                    '<div style="background: #f0f0f1; padding: 12px; border-radius: 4px; border-left: 4px solid #667eea;">
                        <strong>%s</strong><br>%s
                        <a href="%s" target="_blank" style="color: #667eea;">%s →</a>
                    </div>',
                    __('Ensemble Design System', 'ensemble'),
                    __('Styling is controlled centrally via the Ensemble Designer.', 'ensemble'),
                    admin_url('admin.php?page=ensemble-frontend'),
                    __('Open Designer', 'ensemble')
                ),
            )
        );
    }
    
    // =========================================================================
    // OPTIONAL STYLE OVERRIDES
    // Only for specific per-widget overrides, not general styling
    // =========================================================================
    
    /**
     * Add card style controls - MINIMAL version
     * Only allows overriding specific aspects if needed
     */
    protected function add_card_style_controls() {
        // Notice about Design System
        $this->add_design_system_notice();
        
        // Optional background override
        $this->add_control(
            'card_bg_override',
            array(
                'label'     => __('Override background', 'ensemble'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .ensemble-event-card' => 'background-color: {{VALUE}} !important;',
                    '{{WRAPPER}} .ensemble-artist-card' => 'background-color: {{VALUE}} !important;',
                    '{{WRAPPER}} .ensemble-location-card' => 'background-color: {{VALUE}} !important;',
                ),
            )
        );
        
        // Optional border radius override
        $this->add_responsive_control(
            'card_radius_override',
            array(
                'label'      => __('Override border radius', 'ensemble'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => array('px'),
                'range'      => array(
                    'px' => array(
                        'min' => 0,
                        'max' => 50,
                    ),
                ),
                'selectors' => array(
                    '{{WRAPPER}} .ensemble-event-card' => 'border-radius: {{SIZE}}{{UNIT}} !important;',
                    '{{WRAPPER}} .ensemble-artist-card' => 'border-radius: {{SIZE}}{{UNIT}} !important;',
                    '{{WRAPPER}} .ensemble-location-card' => 'border-radius: {{SIZE}}{{UNIT}} !important;',
                ),
            )
        );
    }
    
    /**
     * Add title style controls - MINIMAL version
     */
    protected function add_title_style_controls($selector = '{{WRAPPER}} .ensemble-event-title') {
        $this->add_control(
            'title_color_override',
            array(
                'label'     => __('Override title color', 'ensemble'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    $selector => 'color: {{VALUE}} !important;',
                    $selector . ' a' => 'color: {{VALUE}} !important;',
                ),
            )
        );
    }
    
    /**
     * Add image style controls - MINIMAL version
     */
    protected function add_image_style_controls($selector = '{{WRAPPER}} .ensemble-event-image') {
        $this->add_responsive_control(
            'image_height_override',
            array(
                'label'      => __('Override image height', 'ensemble'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => array('px', 'vh'),
                'range'      => array(
                    'px' => array(
                        'min' => 100,
                        'max' => 600,
                    ),
                ),
                'selectors' => array(
                    $selector => 'height: {{SIZE}}{{UNIT}} !important;',
                    $selector . ' img' => 'height: {{SIZE}}{{UNIT}} !important; object-fit: cover;',
                ),
            )
        );
    }
    
    // =========================================================================
    // HELPER METHODS
    // =========================================================================
    
    /**
     * Get taxonomy options for select control
     */
    protected function get_taxonomy_options($taxonomy, $add_all_option = true) {
        $options = array();
        
        // Add "All" option
        if ($add_all_option) {
            $options[''] = __('All', 'ensemble');
        }
        
        // Check if taxonomy exists
        if (!taxonomy_exists($taxonomy)) {
            return $options;
        }
        
        $terms = get_terms(array(
            'taxonomy'   => $taxonomy,
            'hide_empty' => false,
        ));
        
        if (!is_wp_error($terms) && !empty($terms)) {
            foreach ($terms as $term) {
                $options[$term->slug] = $term->name;
            }
        }
        
        return $options;
    }
    
    /**
     * Get post options for select control
     */
    protected function get_post_options($post_type, $add_all_option = true) {
        $options = array();
        
        // Add "All" option
        if ($add_all_option) {
            $options[''] = __('All', 'ensemble');
        }
        
        // Check if post type exists
        if (!post_type_exists($post_type)) {
            return $options;
        }
        
        $posts = get_posts(array(
            'post_type'      => $post_type,
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC',
        ));
        
        if (!empty($posts)) {
            foreach ($posts as $post) {
                $options[$post->ID] = $post->post_title;
            }
        }
        
        return $options;
    }
    
    /**
     * Convert settings to shortcode attributes
     */
    protected function settings_to_shortcode_atts($settings, $mapping = array()) {
        $atts = array();
        
        foreach ($mapping as $setting_key => $attr_key) {
            if (isset($settings[$setting_key])) {
                $value = $settings[$setting_key];
                
                // Handle arrays (SELECT2 multiple)
                if (is_array($value)) {
                    $value = implode(',', $value);
                }
                
                // Handle yes/no switches
                if ($value === 'yes') {
                    $value = '1';
                } elseif ($value === '') {
                    $value = '0';
                }
                
                // Handle responsive values
                if (is_array($value) && isset($value['size'])) {
                    $value = $value['size'];
                }
                
                if ($value !== '' && $value !== null) {
                    $atts[$attr_key] = $value;
                }
            }
        }
        
        return $atts;
    }
    
    /**
     * Build shortcode string from attributes
     */
    protected function build_shortcode($tag, $atts = array()) {
        $shortcode = '[' . $tag;
        
        foreach ($atts as $key => $value) {
            if ($value !== '' && $value !== null) {
                $shortcode .= ' ' . $key . '="' . esc_attr($value) . '"';
            }
        }
        
        $shortcode .= ']';
        
        return $shortcode;
    }
    
    /**
     * Render shortcode
     */
    protected function render_shortcode($tag, $atts = array()) {
        $shortcode = $this->build_shortcode($tag, $atts);
        echo do_shortcode($shortcode);
    }
    
    /**
     * Render placeholder in editor when no content
     */
    protected function render_editor_placeholder($message = '') {
        if (!\Elementor\Plugin::$instance->editor->is_edit_mode()) {
            return;
        }
        
        if (empty($message)) {
            $message = __('No content found. Please check settings.', 'ensemble');
        }
        
        echo '<div class="es-elementor-placeholder">';
        echo '<div class="es-elementor-placeholder-icon">';
        echo '<i class="' . esc_attr($this->get_icon()) . '"></i>';
        echo '</div>';
        echo '<div class="es-elementor-placeholder-text">' . esc_html($message) . '</div>';
        echo '</div>';
    }
}
