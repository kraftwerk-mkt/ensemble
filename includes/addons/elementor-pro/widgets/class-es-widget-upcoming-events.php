<?php
/**
 * Upcoming Events Elementor Widget
 * 
 * Compact list of upcoming events.
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
 * Class ES_Widget_Upcoming_Events
 */
class ES_Widget_Upcoming_Events extends ES_Elementor_Widget_Base {
    
    public function get_name() {
        return 'ensemble-upcoming-events';
    }
    
    public function get_title() {
        return __('Upcoming Events', 'ensemble');
    }
    
    public function get_icon() {
        return 'eicon-post-list';
    }
    
    protected $widget_category = 'ensemble-events';
    
    public function get_keywords() {
        return array('ensemble', 'events', 'upcoming', 'list', 'kommende', 'veranstaltungen');
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
        
        $this->add_category_control('ensemble_category');
        $this->add_location_control();
        $this->add_artist_control();
        
        $this->add_control(
            'limit',
            array(
                'label'   => __('Anzahl', 'ensemble'),
                'type'    => Controls_Manager::NUMBER,
                'default' => 5,
                'min'     => 1,
                'max'     => 20,
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
                'default' => 'list',
                'options' => array(
                    'list'    => __('Liste', 'ensemble'),
                    'compact' => __('Kompakt', 'ensemble'),
                    'minimal' => __('Minimal', 'ensemble'),
                ),
            )
        );
        
        $this->add_control(
            'show_divider',
            array(
                'label'        => __('Trennlinien', 'ensemble'),
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
        
        $this->add_control(
            'show_image',
            array(
                'label'        => __('Show image', 'ensemble'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
            )
        );
        
        $this->add_control(
            'show_date',
            array(
                'label'        => __('Show date', 'ensemble'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
            )
        );
        
        $this->add_control(
            'show_time',
            array(
                'label'        => __('Show time', 'ensemble'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
            )
        );
        
        $this->add_control(
            'show_location',
            array(
                'label'        => __('Show location', 'ensemble'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
            )
        );
        
        $this->add_control(
            'link_target',
            array(
                'label'   => __('Open link in', 'ensemble'),
                'type'    => Controls_Manager::SELECT,
                'default' => '_self',
                'options' => array(
                    '_self'  => __('Gleiches Fenster', 'ensemble'),
                    '_blank' => __('New window', 'ensemble'),
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
                'default'     => __('No upcoming events.', 'ensemble'),
                'label_block' => true,
            )
        );
        
        $this->end_controls_section();
        
        // ======================
        // STYLE TAB
        // ======================
        
        // Item Styles
        $this->start_controls_section(
            'section_style_item',
            array(
                'label' => __('Event Item', 'ensemble'),
                'tab'   => Controls_Manager::TAB_STYLE,
            )
        );
        
        $this->add_control(
            'item_background',
            array(
                'label'     => __('Hintergrundfarbe', 'ensemble'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .ensemble-upcoming-item' => 'background-color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_responsive_control(
            'item_padding',
            array(
                'label'      => __('Padding', 'ensemble'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', 'em'),
                'selectors'  => array(
                    '{{WRAPPER}} .ensemble-upcoming-item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );
        
        $this->add_control(
            'divider_color',
            array(
                'label'     => __('Trennlinien Farbe', 'ensemble'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .ensemble-upcoming-item' => 'border-bottom-color: {{VALUE}};',
                ),
                'condition' => array(
                    'show_divider' => 'yes',
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
            'image_size',
            array(
                'label'      => __('Size', 'ensemble'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => array('px'),
                'range'      => array(
                    'px' => array('min' => 40, 'max' => 150),
                ),
                'default'    => array('size' => 60, 'unit' => 'px'),
                'selectors'  => array(
                    '{{WRAPPER}} .ensemble-upcoming-image' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .ensemble-upcoming-image img' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ),
            )
        );
        
        $this->add_responsive_control(
            'image_border_radius',
            array(
                'label'      => __('Border Radius', 'ensemble'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => array('px', '%'),
                'range'      => array(
                    'px' => array('min' => 0, 'max' => 50),
                    '%'  => array('min' => 0, 'max' => 50),
                ),
                'selectors'  => array(
                    '{{WRAPPER}} .ensemble-upcoming-image' => 'border-radius: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .ensemble-upcoming-image img' => 'border-radius: {{SIZE}}{{UNIT}};',
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
                    '{{WRAPPER}} .ensemble-upcoming-title' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .ensemble-upcoming-title a' => 'color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_control(
            'title_hover_color',
            array(
                'label'     => __('Hover Farbe', 'ensemble'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .ensemble-upcoming-title a:hover' => 'color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name'     => 'title_typography',
                'selector' => '{{WRAPPER}} .ensemble-upcoming-title',
            )
        );
        
        $this->end_controls_section();
        
        // Date Styles
        $this->start_controls_section(
            'section_style_date',
            array(
                'label'     => __('Datum', 'ensemble'),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => array(
                    'show_date' => 'yes',
                ),
            )
        );
        
        $this->add_control(
            'date_style',
            array(
                'label'   => __('Datum Stil', 'ensemble'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'badge',
                'options' => array(
                    'badge'  => __('Badge', 'ensemble'),
                    'inline' => __('Inline', 'ensemble'),
                ),
            )
        );
        
        $this->add_control(
            'date_background',
            array(
                'label'     => __('Hintergrundfarbe', 'ensemble'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .ensemble-upcoming-date' => 'background-color: {{VALUE}};',
                ),
                'condition' => array(
                    'date_style' => 'badge',
                ),
            )
        );
        
        $this->add_control(
            'date_color',
            array(
                'label'     => __('Textfarbe', 'ensemble'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .ensemble-upcoming-date' => 'color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name'     => 'date_typography',
                'selector' => '{{WRAPPER}} .ensemble-upcoming-date',
            )
        );
        
        $this->end_controls_section();
        
        // Meta Styles
        $this->start_controls_section(
            'section_style_meta',
            array(
                'label' => __('Meta (Zeit, Location)', 'ensemble'),
                'tab'   => Controls_Manager::TAB_STYLE,
            )
        );
        
        $this->add_control(
            'meta_color',
            array(
                'label'     => __('Farbe', 'ensemble'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .ensemble-upcoming-meta' => 'color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name'     => 'meta_typography',
                'selector' => '{{WRAPPER}} .ensemble-upcoming-meta',
            )
        );
        
        $this->end_controls_section();
    }
    
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        // Build shortcode attributes - use ensemble_events_grid with show="upcoming"
        $atts = array(
            'layout'        => !empty($settings['layout']) ? $settings['layout'] : 'list',
            'limit'         => !empty($settings['limit']) ? $settings['limit'] : 5,
            'show'          => 'upcoming', // Key parameter for upcoming events
            'show_filters'  => '0', // No filters for this compact widget
            'show_search'   => '0',
            'show_image'    => $settings['show_image'] === 'yes' ? '1' : '0',
            'show_date'     => $settings['show_date'] === 'yes' ? '1' : '0',
            'show_time'     => $settings['show_time'] === 'yes' ? '1' : '0',
            'show_location' => $settings['show_location'] === 'yes' ? '1' : '0',
            'show_category' => '0',
            'show_price'    => '0',
            'show_description' => '0',
        );
        
        if (!empty($settings['offset'])) {
            $atts['offset'] = $settings['offset'];
        }
        
        if (!empty($settings['category']) && is_array($settings['category'])) {
            $atts['category'] = implode(',', $settings['category']);
        }
        if (!empty($settings['location']) && is_array($settings['location'])) {
            $atts['location'] = implode(',', $settings['location']);
        }
        if (!empty($settings['artist']) && is_array($settings['artist'])) {
            $atts['artist'] = implode(',', $settings['artist']);
        }
        
        if (!empty($settings['empty_message'])) {
            $atts['empty_message'] = $settings['empty_message'];
        }
        
        echo '<div class="es-elementor-widget es-upcoming-events-widget">';
        $this->render_shortcode('ensemble_events_grid', $atts);
        echo '</div>';
    }
}
