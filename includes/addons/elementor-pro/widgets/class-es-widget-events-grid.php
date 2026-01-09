<?php
/**
 * Events Grid Elementor Widget
 * 
 * Full-featured widget with comprehensive styling options.
 * 
 * @package Ensemble
 * @subpackage Elementor/Widgets
 * @since 3.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('ES_Elementor_Widget_Base')) {
    return;
}

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;

/**
 * Class ES_Widget_Events_Grid
 */
class ES_Widget_Events_Grid extends ES_Elementor_Widget_Base {
    
    public function get_name() {
        return 'ensemble-events-grid';
    }
    
    public function get_title() {
        return __('Events Grid', 'ensemble');
    }
    
    public function get_icon() {
        return 'eicon-gallery-grid';
    }
    
    protected $widget_category = 'ensemble-events';
    
    public function get_keywords() {
        return array('ensemble', 'events', 'grid', 'list', 'calendar', 'veranstaltungen');
    }
    
    public function get_style_depends() {
        return array('ensemble-frontend', 'ensemble-shortcodes');
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
                'default' => 'upcoming',
                'options' => array(
                    'upcoming' => __('Kommende Events', 'ensemble'),
                    'past'     => __('Vergangene Events', 'ensemble'),
                    'all'      => __('All Events', 'ensemble'),
                    'featured' => __('Nur Featured', 'ensemble'),
                ),
            )
        );
        
        $this->add_category_control('ensemble_category');
        $this->add_location_control();
        $this->add_artist_control();
        $this->add_query_controls(6);
        
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
                'default' => 'grid',
                'options' => array(
                    'grid'    => __('Grid', 'ensemble'),
                    'list'    => __('Liste', 'ensemble'),
                    'masonry' => __('Masonry', 'ensemble'),
                ),
            )
        );
        
        $this->add_layout_controls(3, 20);
        
        $this->add_control(
            'card_style',
            array(
                'label'   => __('Card Style', 'ensemble'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'default',
                'options' => array(
                    'default'  => __('Standard', 'ensemble'),
                    'compact'  => __('Kompakt', 'ensemble'),
                    'minimal'  => __('Minimal', 'ensemble'),
                    'overlay'  => __('Overlay', 'ensemble'),
                    'featured' => __('Featured', 'ensemble'),
                ),
            )
        );
        
        $this->add_control(
            'show_filters',
            array(
                'label'        => __('Show filters', 'ensemble'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
            )
        );
        
        $this->add_control(
            'show_search',
            array(
                'label'        => __('Show search', 'ensemble'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
            )
        );
        
        $this->end_controls_section();
        
        // Display Options Section
        $this->start_controls_section(
            'section_display',
            array(
                'label' => __('Display Options', 'ensemble'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            )
        );
        
        $this->add_display_controls(array(
            'show_image'       => true,
            'show_date'        => true,
            'show_time'        => true,
            'show_location'    => true,
            'show_category'    => true,
            'show_price'       => true,
            'show_description' => false,
            'show_artists'     => false,
        ));
        
        $this->add_control(
            'excerpt_length',
            array(
                'label'     => __('Description length', 'ensemble'),
                'type'      => Controls_Manager::NUMBER,
                'default'   => 100,
                'min'       => 20,
                'max'       => 500,
                'condition' => array(
                    'show_description' => 'yes',
                ),
            )
        );
        
        $this->end_controls_section();
        
        // Empty State Section
        $this->start_controls_section(
            'section_empty',
            array(
                'label' => __('Leerer Zustand', 'ensemble'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            )
        );
        
        $this->add_control(
            'empty_message',
            array(
                'label'       => __('Nachricht wenn leer', 'ensemble'),
                'type'        => Controls_Manager::TEXT,
                'default'     => __('No events found.', 'ensemble'),
                'label_block' => true,
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
                    '{{WRAPPER}} .ensemble-event-card' => 'background-color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_group_control(
            Group_Control_Border::get_type(),
            array(
                'name'     => 'card_border',
                'selector' => '{{WRAPPER}} .ensemble-event-card',
            )
        );
        
        $this->add_responsive_control(
            'card_border_radius',
            array(
                'label'      => __('Border Radius', 'ensemble'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%'),
                'selectors'  => array(
                    '{{WRAPPER}} .ensemble-event-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );
        
        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            array(
                'name'     => 'card_shadow',
                'selector' => '{{WRAPPER}} .ensemble-event-card',
            )
        );
        
        $this->add_responsive_control(
            'card_padding',
            array(
                'label'      => __('Padding', 'ensemble'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', 'em', '%'),
                'selectors'  => array(
                    '{{WRAPPER}} .ensemble-event-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
                    'px' => array('min' => 100, 'max' => 500),
                    'vh' => array('min' => 10, 'max' => 50),
                ),
                'selectors' => array(
                    '{{WRAPPER}} .ensemble-event-image' => 'height: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .ensemble-event-image img' => 'height: {{SIZE}}{{UNIT}}; object-fit: cover;',
                ),
            )
        );
        
        $this->add_responsive_control(
            'image_border_radius',
            array(
                'label'      => __('Border Radius', 'ensemble'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%'),
                'selectors'  => array(
                    '{{WRAPPER}} .ensemble-event-image' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .ensemble-event-image img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
                    '{{WRAPPER}} .ensemble-event-title' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .ensemble-event-title a' => 'color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_control(
            'title_hover_color',
            array(
                'label'     => __('Hover Farbe', 'ensemble'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .ensemble-event-title a:hover' => 'color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name'     => 'title_typography',
                'selector' => '{{WRAPPER}} .ensemble-event-title',
            )
        );
        
        $this->add_responsive_control(
            'title_spacing',
            array(
                'label'      => __('Abstand unten', 'ensemble'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => array('px', 'em'),
                'range'      => array('px' => array('min' => 0, 'max' => 50)),
                'selectors'  => array(
                    '{{WRAPPER}} .ensemble-event-title' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ),
            )
        );
        
        $this->end_controls_section();
        
        // Meta Styles
        $this->start_controls_section(
            'section_style_meta',
            array(
                'label' => __('Meta (Datum, Location, etc.)', 'ensemble'),
                'tab'   => Controls_Manager::TAB_STYLE,
            )
        );
        
        $this->add_control(
            'meta_color',
            array(
                'label'     => __('Textfarbe', 'ensemble'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .ensemble-event-meta' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .ensemble-event-meta span' => 'color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_control(
            'meta_icon_color',
            array(
                'label'     => __('Icon Farbe', 'ensemble'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .ensemble-event-meta .dashicons' => 'color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name'     => 'meta_typography',
                'selector' => '{{WRAPPER}} .ensemble-event-meta',
            )
        );
        
        $this->end_controls_section();
        
        // Date Badge Styles
        $this->start_controls_section(
            'section_style_date',
            array(
                'label'     => __('Datum Badge', 'ensemble'),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => array(
                    'show_date' => 'yes',
                ),
            )
        );
        
        $this->add_control(
            'date_background',
            array(
                'label'     => __('Hintergrundfarbe', 'ensemble'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .ensemble-event-date' => 'background-color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_control(
            'date_color',
            array(
                'label'     => __('Textfarbe', 'ensemble'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .ensemble-event-date' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .ensemble-event-date .date-day' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .ensemble-event-date .date-month' => 'color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_responsive_control(
            'date_padding',
            array(
                'label'      => __('Padding', 'ensemble'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', 'em'),
                'selectors'  => array(
                    '{{WRAPPER}} .ensemble-event-date' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );
        
        $this->add_responsive_control(
            'date_border_radius',
            array(
                'label'      => __('Border Radius', 'ensemble'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => array('px'),
                'range'      => array('px' => array('min' => 0, 'max' => 30)),
                'selectors'  => array(
                    '{{WRAPPER}} .ensemble-event-date' => 'border-radius: {{SIZE}}{{UNIT}};',
                ),
            )
        );
        
        $this->end_controls_section();
        
        // Category Badge Styles
        $this->start_controls_section(
            'section_style_category',
            array(
                'label'     => __('Kategorie Badge', 'ensemble'),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => array(
                    'show_category' => 'yes',
                ),
            )
        );
        
        $this->add_control(
            'category_background',
            array(
                'label'     => __('Hintergrundfarbe', 'ensemble'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .ensemble-event-category-badge' => 'background-color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_control(
            'category_color',
            array(
                'label'     => __('Textfarbe', 'ensemble'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .ensemble-event-category-badge' => 'color: {{VALUE}};',
                ),
            )
        );
        
        $this->end_controls_section();
    }
    
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        // Build shortcode attributes - mapping widget settings to shortcode params
        $atts = array();
        
        // Layout
        $atts['layout'] = $settings['layout'];
        $atts['style'] = $settings['card_style'];
        
        // Columns
        if (!empty($settings['columns'])) {
            $atts['columns'] = $settings['columns'];
        }
        
        // Gap
        if (!empty($settings['gap']['size'])) {
            $atts['gap'] = $settings['gap']['size'];
        }
        
        // Query
        if (!empty($settings['limit'])) {
            $atts['limit'] = $settings['limit'];
        }
        if (!empty($settings['offset'])) {
            $atts['offset'] = $settings['offset'];
        }
        if (!empty($settings['orderby'])) {
            $atts['orderby'] = $settings['orderby'];
        }
        if (!empty($settings['order'])) {
            $atts['order'] = $settings['order'];
        }
        
        // Source
        switch ($settings['source']) {
            case 'upcoming':
                $atts['show'] = 'upcoming';
                break;
            case 'past':
                $atts['show'] = 'past';
                break;
            case 'all':
                $atts['show'] = 'all';
                break;
            case 'featured':
                $atts['show'] = 'upcoming';
                $atts['featured'] = '1';
                break;
        }
        
        // Filters
        if (!empty($settings['category']) && is_array($settings['category'])) {
            $atts['category'] = implode(',', $settings['category']);
        }
        if (!empty($settings['location']) && is_array($settings['location'])) {
            $atts['location'] = implode(',', $settings['location']);
        }
        if (!empty($settings['artist']) && is_array($settings['artist'])) {
            $atts['artist'] = implode(',', $settings['artist']);
        }
        
        // UI
        $atts['show_filters'] = $settings['show_filters'] === 'yes' ? 'true' : 'false';
        $atts['show_search'] = $settings['show_search'] === 'yes' ? 'true' : 'false';
        
        // Display options
        $atts['show_image'] = $settings['show_image'] === 'yes' ? '1' : '0';
        $atts['show_date'] = $settings['show_date'] === 'yes' ? '1' : '0';
        $atts['show_time'] = $settings['show_time'] === 'yes' ? '1' : '0';
        $atts['show_location'] = $settings['show_location'] === 'yes' ? '1' : '0';
        $atts['show_category'] = $settings['show_category'] === 'yes' ? '1' : '0';
        $atts['show_price'] = $settings['show_price'] === 'yes' ? '1' : '0';
        $atts['show_description'] = $settings['show_description'] === 'yes' ? '1' : '0';
        $atts['show_artists'] = $settings['show_artists'] === 'yes' ? '1' : '0';
        
        if (!empty($settings['excerpt_length'])) {
            $atts['excerpt_length'] = $settings['excerpt_length'];
        }
        
        if (!empty($settings['empty_message'])) {
            $atts['empty_message'] = $settings['empty_message'];
        }
        
        // Wrapper
        echo '<div class="es-elementor-widget es-events-grid-widget">';
        $this->render_shortcode('ensemble_events_grid', $atts);
        echo '</div>';
    }
    
    public function render_plain_content() {
        echo '[ensemble_events_grid]';
    }
}
