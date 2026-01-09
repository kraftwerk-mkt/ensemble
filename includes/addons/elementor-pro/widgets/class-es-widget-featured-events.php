<?php
/**
 * Featured Events Elementor Widget
 * 
 * Showcase featured/highlighted events.
 * 
 * @package Ensemble
 * @subpackage Addons/ElementorPro/Widgets
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;

/**
 * Class ES_Widget_Featured_Events
 */
class ES_Widget_Featured_Events extends ES_Elementor_Widget_Base {
    
    public function get_name() {
        return 'ensemble-featured-events';
    }
    
    public function get_title() {
        return __('Featured Events', 'ensemble');
    }
    
    public function get_icon() {
        return 'eicon-star';
    }
    
    protected $widget_category = 'ensemble-events';
    
    public function get_keywords() {
        return array('ensemble', 'events', 'featured', 'highlight', 'hervorgehoben', 'star');
    }
    
    protected function register_controls() {
        
        // ======================
        // CONTENT TAB
        // ======================
        
        // Query Section
        $this->start_controls_section(
            'section_query',
            array(
                'label' => __('Query / Filter', 'ensemble'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            )
        );
        
        $this->add_control(
            'source',
            array(
                'label'   => __('Quelle', 'ensemble'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'featured',
                'options' => array(
                    'featured' => __('Nur Featured Events', 'ensemble'),
                    'manual'   => __('Select manually', 'ensemble'),
                ),
            )
        );
        
        $this->add_event_control(true); // Multiple selection
        
        $this->add_category_control('ensemble_category');
        $this->add_location_control();
        
        $this->add_control(
            'limit',
            array(
                'label'     => __('Anzahl', 'ensemble'),
                'type'      => Controls_Manager::NUMBER,
                'default'   => 3,
                'min'       => 1,
                'max'       => 12,
                'condition' => array(
                    'source' => 'featured',
                ),
            )
        );
        
        $this->end_controls_section();
        
        // Layout Section
        $this->start_controls_section(
            'section_layout',
            array(
                'label' => __('Layout', 'ensemble'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            )
        );
        
        $this->add_control(
            'layout',
            array(
                'label'   => __('Layout', 'ensemble'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'cards',
                'options' => array(
                    'cards'    => __('Karten', 'ensemble'),
                    'hero'     => __('Hero (Large)', 'ensemble'),
                    'slider'   => __('Slider', 'ensemble'),
                    'overlay'  => __('Overlay', 'ensemble'),
                ),
            )
        );
        
        $this->add_layout_controls(3, 30);
        
        $this->end_controls_section();
        
        // Display Options Section
        $this->start_controls_section(
            'section_display',
            array(
                'label' => __('Display Options', 'ensemble'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            )
        );
        
        $this->add_control(
            'show_badge',
            array(
                'label'        => __('Featured Badge', 'ensemble'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
            )
        );
        
        $this->add_control(
            'badge_text',
            array(
                'label'     => __('Badge Text', 'ensemble'),
                'type'      => Controls_Manager::TEXT,
                'default'   => __('Featured', 'ensemble'),
                'condition' => array(
                    'show_badge' => 'yes',
                ),
            )
        );
        
        $this->add_display_controls(array(
            'show_image'       => true,
            'show_date'        => true,
            'show_time'        => true,
            'show_location'    => true,
            'show_category'    => true,
            'show_price'       => true,
            'show_description' => true,
            'show_artists'     => false,
        ));
        
        $this->add_control(
            'excerpt_length',
            array(
                'label'     => __('Description length', 'ensemble'),
                'type'      => Controls_Manager::NUMBER,
                'default'   => 100,
                'min'       => 20,
                'max'       => 300,
                'condition' => array(
                    'show_description' => 'yes',
                ),
            )
        );
        
        $this->add_control(
            'show_button',
            array(
                'label'        => __('Show button', 'ensemble'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
            )
        );
        
        $this->add_control(
            'button_text',
            array(
                'label'     => __('Button Text', 'ensemble'),
                'type'      => Controls_Manager::TEXT,
                'default'   => __('Details ansehen', 'ensemble'),
                'condition' => array(
                    'show_button' => 'yes',
                ),
            )
        );
        
        $this->end_controls_section();
        
        // ======================
        // STYLE TAB
        // ======================
        
        // Card Styles
        $this->start_controls_section(
            'section_style_card',
            array(
                'label' => __('Card', 'ensemble'),
                'tab'   => Controls_Manager::TAB_STYLE,
            )
        );
        
        $this->add_control(
            'card_background',
            array(
                'label'     => __('Hintergrundfarbe', 'ensemble'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .ensemble-featured-card' => 'background-color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_group_control(
            Group_Control_Border::get_type(),
            array(
                'name'     => 'card_border',
                'selector' => '{{WRAPPER}} .ensemble-featured-card',
            )
        );
        
        $this->add_responsive_control(
            'card_border_radius',
            array(
                'label'      => __('Border Radius', 'ensemble'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%'),
                'selectors'  => array(
                    '{{WRAPPER}} .ensemble-featured-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );
        
        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            array(
                'name'     => 'card_shadow',
                'selector' => '{{WRAPPER}} .ensemble-featured-card',
            )
        );
        
        $this->end_controls_section();
        
        // Badge Styles
        $this->start_controls_section(
            'section_style_badge',
            array(
                'label'     => __('Featured Badge', 'ensemble'),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => array(
                    'show_badge' => 'yes',
                ),
            )
        );
        
        $this->add_control(
            'badge_background',
            array(
                'label'     => __('Hintergrundfarbe', 'ensemble'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#f59e0b',
                'selectors' => array(
                    '{{WRAPPER}} .ensemble-featured-badge' => 'background-color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_control(
            'badge_color',
            array(
                'label'     => __('Textfarbe', 'ensemble'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => array(
                    '{{WRAPPER}} .ensemble-featured-badge' => 'color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name'     => 'badge_typography',
                'selector' => '{{WRAPPER}} .ensemble-featured-badge',
            )
        );
        
        $this->add_responsive_control(
            'badge_padding',
            array(
                'label'      => __('Padding', 'ensemble'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', 'em'),
                'selectors'  => array(
                    '{{WRAPPER}} .ensemble-featured-badge' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );
        
        $this->end_controls_section();
        
        // Image Styles
        $this->start_controls_section(
            'section_style_image',
            array(
                'label'     => __('Bild', 'ensemble'),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => array(
                    'show_image' => 'yes',
                ),
            )
        );
        
        $this->add_responsive_control(
            'image_height',
            array(
                'label'      => __('Height', 'ensemble'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => array('px', 'vh'),
                'range'      => array(
                    'px' => array('min' => 150, 'max' => 600),
                    'vh' => array('min' => 20, 'max' => 80),
                ),
                'selectors'  => array(
                    '{{WRAPPER}} .ensemble-featured-image' => 'height: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .ensemble-featured-image img' => 'height: {{SIZE}}{{UNIT}}; object-fit: cover;',
                ),
            )
        );
        
        $this->end_controls_section();
        
        // Title Styles
        $this->start_controls_section(
            'section_style_title',
            array(
                'label' => __('Titel', 'ensemble'),
                'tab'   => Controls_Manager::TAB_STYLE,
            )
        );
        
        $this->add_control(
            'title_color',
            array(
                'label'     => __('Farbe', 'ensemble'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .ensemble-featured-title' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .ensemble-featured-title a' => 'color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name'     => 'title_typography',
                'selector' => '{{WRAPPER}} .ensemble-featured-title',
            )
        );
        
        $this->end_controls_section();
        
        // Button Styles
        $this->start_controls_section(
            'section_style_button',
            array(
                'label'     => __('Button', 'ensemble'),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => array(
                    'show_button' => 'yes',
                ),
            )
        );
        
        $this->add_control(
            'button_background',
            array(
                'label'     => __('Hintergrundfarbe', 'ensemble'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .ensemble-featured-button' => 'background-color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_control(
            'button_color',
            array(
                'label'     => __('Textfarbe', 'ensemble'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .ensemble-featured-button' => 'color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_control(
            'button_hover_background',
            array(
                'label'     => __('Hover Hintergrund', 'ensemble'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .ensemble-featured-button:hover' => 'background-color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name'     => 'button_typography',
                'selector' => '{{WRAPPER}} .ensemble-featured-button',
            )
        );
        
        $this->add_responsive_control(
            'button_padding',
            array(
                'label'      => __('Padding', 'ensemble'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', 'em'),
                'selectors'  => array(
                    '{{WRAPPER}} .ensemble-featured-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );
        
        $this->add_responsive_control(
            'button_border_radius',
            array(
                'label'      => __('Border Radius', 'ensemble'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => array('px'),
                'range'      => array('px' => array('min' => 0, 'max' => 30)),
                'selectors'  => array(
                    '{{WRAPPER}} .ensemble-featured-button' => 'border-radius: {{SIZE}}{{UNIT}};',
                ),
            )
        );
        
        $this->end_controls_section();
    }
    
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        // Build shortcode attributes - use ensemble_events_grid with featured="1"
        $atts = array(
            'layout'           => !empty($settings['layout']) ? $settings['layout'] : 'grid',
            'style'            => 'featured', // Use featured card style
            'show'             => 'upcoming',
            'featured'         => '1', // Key parameter for featured events
            'show_filters'     => '0',
            'show_search'      => '0',
            'show_image'       => $settings['show_image'] === 'yes' ? '1' : '0',
            'show_date'        => $settings['show_date'] === 'yes' ? '1' : '0',
            'show_time'        => $settings['show_time'] === 'yes' ? '1' : '0',
            'show_location'    => $settings['show_location'] === 'yes' ? '1' : '0',
            'show_category'    => $settings['show_category'] === 'yes' ? '1' : '0',
            'show_price'       => $settings['show_price'] === 'yes' ? '1' : '0',
            'show_description' => $settings['show_description'] === 'yes' ? '1' : '0',
        );
        
        // Source - manual selection or automatic
        if ($settings['source'] === 'manual' && !empty($settings['events'])) {
            $atts['ids'] = implode(',', $settings['events']);
            unset($atts['featured']); // Don't filter by featured if manual selection
        } else {
            $atts['limit'] = !empty($settings['limit']) ? $settings['limit'] : 3;
        }
        
        // Columns
        if (!empty($settings['columns'])) {
            $atts['columns'] = $settings['columns'];
        }
        
        // Gap
        if (!empty($settings['gap']['size'])) {
            $atts['gap'] = $settings['gap']['size'];
        }
        
        // Filters
        if (!empty($settings['category']) && is_array($settings['category'])) {
            $atts['category'] = implode(',', $settings['category']);
        }
        if (!empty($settings['location']) && is_array($settings['location'])) {
            $atts['location'] = implode(',', $settings['location']);
        }
        
        if (!empty($settings['excerpt_length'])) {
            $atts['excerpt_length'] = $settings['excerpt_length'];
        }
        
        echo '<div class="es-elementor-widget es-featured-events-widget">';
        $this->render_shortcode('ensemble_events_grid', $atts);
        echo '</div>';
    }
}
