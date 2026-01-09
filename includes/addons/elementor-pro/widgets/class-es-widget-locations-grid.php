<?php
/**
 * Locations Grid Elementor Widget
 * 
 * Display locations/venues in grid or list layout.
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
 * Class ES_Widget_Locations_Grid
 */
class ES_Widget_Locations_Grid extends ES_Elementor_Widget_Base {
    
    public function get_name() {
        return 'ensemble-locations-grid';
    }
    
    public function get_title() {
        return __('Locations Grid', 'ensemble');
    }
    
    public function get_icon() {
        return 'eicon-map-pin';
    }
    
    protected $widget_category = 'ensemble-content';
    
    public function get_keywords() {
        return array('ensemble', 'locations', 'venues', 'orte', 'grid', 'list', 'map');
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
                'default' => 'all',
                'options' => array(
                    'all'    => __('All Locations', 'ensemble'),
                    'manual' => __('Select manually', 'ensemble'),
                ),
            )
        );
        
        $this->add_location_control();
        
        // Location type taxonomy if exists
        $types = $this->get_taxonomy_options('ensemble_location_type');
        if (!empty($types)) {
            $this->add_control(
                'location_type',
                array(
                    'label'       => __('Typ', 'ensemble'),
                    'type'        => Controls_Manager::SELECT2,
                    'multiple'    => true,
                    'options'     => $types,
                    'default'     => array(),
                    'label_block' => true,
                    'condition'   => array(
                        'source' => 'all',
                    ),
                )
            );
        }
        
        $this->add_control(
            'limit',
            array(
                'label'     => __('Anzahl', 'ensemble'),
                'type'      => Controls_Manager::NUMBER,
                'default'   => 6,
                'min'       => 1,
                'max'       => 50,
                'condition' => array(
                    'source' => 'all',
                ),
            )
        );
        
        $this->add_control(
            'orderby',
            array(
                'label'     => __('Sortierung', 'ensemble'),
                'type'      => Controls_Manager::SELECT,
                'default'   => 'title',
                'options'   => array(
                    'title'      => __('Name', 'ensemble'),
                    'date'       => __('Datum', 'ensemble'),
                    'menu_order' => __('Menu order', 'ensemble'),
                    'rand'       => __('Random', 'ensemble'),
                ),
                'condition' => array(
                    'source' => 'all',
                ),
            )
        );
        
        $this->add_control(
            'order',
            array(
                'label'     => __('Reihenfolge', 'ensemble'),
                'type'      => Controls_Manager::SELECT,
                'default'   => 'ASC',
                'options'   => array(
                    'ASC'  => __('Aufsteigend', 'ensemble'),
                    'DESC' => __('Absteigend', 'ensemble'),
                ),
                'condition' => array(
                    'source' => 'all',
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
                'default' => 'grid',
                'options' => array(
                    'grid'    => __('Grid', 'ensemble'),
                    'list'    => __('Liste', 'ensemble'),
                    'cards'   => __('Karten', 'ensemble'),
                    'compact' => __('Kompakt', 'ensemble'),
                ),
            )
        );
        
        $this->add_layout_controls(3, 24);
        
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
            'show_address',
            array(
                'label'        => __('Show address', 'ensemble'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
            )
        );
        
        $this->add_control(
            'show_city',
            array(
                'label'        => __('Show city', 'ensemble'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
            )
        );
        
        $this->add_control(
            'show_phone',
            array(
                'label'        => __('Show phone', 'ensemble'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => '',
                'return_value' => 'yes',
            )
        );
        
        $this->add_control(
            'show_email',
            array(
                'label'        => __('Show email', 'ensemble'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => '',
                'return_value' => 'yes',
            )
        );
        
        $this->add_control(
            'show_website',
            array(
                'label'        => __('Show website', 'ensemble'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => '',
                'return_value' => 'yes',
            )
        );
        
        $this->add_control(
            'show_description',
            array(
                'label'        => __('Show description', 'ensemble'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => '',
                'return_value' => 'yes',
            )
        );
        
        $this->add_control(
            'description_length',
            array(
                'label'     => __('Description length (words)', 'ensemble'),
                'type'      => Controls_Manager::NUMBER,
                'default'   => 20,
                'min'       => 5,
                'max'       => 100,
                'condition' => array(
                    'show_description' => 'yes',
                ),
            )
        );
        
        $this->add_control(
            'show_events_count',
            array(
                'label'        => __('Show event count', 'ensemble'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => '',
                'return_value' => 'yes',
            )
        );
        
        $this->add_control(
            'show_map_link',
            array(
                'label'        => __('Show map link', 'ensemble'),
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
                'default'     => __('No locations found.', 'ensemble'),
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
                    '{{WRAPPER}} .ensemble-location-card' => 'background-color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_group_control(
            Group_Control_Border::get_type(),
            array(
                'name'     => 'card_border',
                'selector' => '{{WRAPPER}} .ensemble-location-card',
            )
        );
        
        $this->add_responsive_control(
            'card_border_radius',
            array(
                'label'      => __('Border Radius', 'ensemble'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%'),
                'selectors'  => array(
                    '{{WRAPPER}} .ensemble-location-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );
        
        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            array(
                'name'     => 'card_shadow',
                'selector' => '{{WRAPPER}} .ensemble-location-card',
            )
        );
        
        $this->add_responsive_control(
            'card_padding',
            array(
                'label'      => __('Padding', 'ensemble'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', 'em'),
                'selectors'  => array(
                    '{{WRAPPER}} .ensemble-location-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
                'size_units' => array('px'),
                'range'      => array(
                    'px' => array('min' => 100, 'max' => 400),
                ),
                'selectors'  => array(
                    '{{WRAPPER}} .ensemble-location-image' => 'height: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .ensemble-location-image img' => 'height: {{SIZE}}{{UNIT}}; object-fit: cover;',
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
                    '{{WRAPPER}} .ensemble-location-image' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .ensemble-location-image img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );
        
        $this->end_controls_section();
        
        // Name Styles
        $this->start_controls_section(
            'section_style_name',
            array(
                'label' => __('Name', 'ensemble'),
                'tab'   => Controls_Manager::TAB_STYLE,
            )
        );
        
        $this->add_control(
            'name_color',
            array(
                'label'     => __('Farbe', 'ensemble'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .ensemble-location-name' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .ensemble-location-name a' => 'color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_control(
            'name_hover_color',
            array(
                'label'     => __('Hover Farbe', 'ensemble'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .ensemble-location-name a:hover' => 'color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name'     => 'name_typography',
                'selector' => '{{WRAPPER}} .ensemble-location-name',
            )
        );
        
        $this->end_controls_section();
        
        // Address Styles
        $this->start_controls_section(
            'section_style_address',
            array(
                'label'     => __('Adresse', 'ensemble'),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => array(
                    'show_address' => 'yes',
                ),
            )
        );
        
        $this->add_control(
            'address_color',
            array(
                'label'     => __('Farbe', 'ensemble'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .ensemble-location-address' => 'color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_control(
            'address_icon_color',
            array(
                'label'     => __('Icon Farbe', 'ensemble'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .ensemble-location-address .dashicons' => 'color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name'     => 'address_typography',
                'selector' => '{{WRAPPER}} .ensemble-location-address',
            )
        );
        
        $this->end_controls_section();
        
        // Map Link Styles
        $this->start_controls_section(
            'section_style_map_link',
            array(
                'label'     => __('Karten-Link', 'ensemble'),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => array(
                    'show_map_link' => 'yes',
                ),
            )
        );
        
        $this->add_control(
            'map_link_color',
            array(
                'label'     => __('Farbe', 'ensemble'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .ensemble-location-map-link' => 'color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_control(
            'map_link_hover_color',
            array(
                'label'     => __('Hover Farbe', 'ensemble'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .ensemble-location-map-link:hover' => 'color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name'     => 'map_link_typography',
                'selector' => '{{WRAPPER}} .ensemble-location-map-link',
            )
        );
        
        $this->end_controls_section();
    }
    
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        // Build shortcode attributes - match ensemble_locations shortcode parameters
        $atts = array(
            'layout'           => !empty($settings['layout']) ? $settings['layout'] : 'grid',
            'show_image'       => $settings['show_image'] === 'yes' ? 'true' : 'false',
            'show_address'     => $settings['show_address'] === 'yes' ? 'true' : 'false',
            'show_description' => $settings['show_description'] === 'yes' ? 'true' : 'false',
            'show_events'      => $settings['show_events_count'] === 'yes' ? 'true' : 'false',
            'show_link'        => 'true',
        );
        
        // Source - manual selection or automatic
        if ($settings['source'] === 'manual' && !empty($settings['location'])) {
            // Manual selection - use IDs (if shortcode supports it)
            $atts['ids'] = implode(',', $settings['location']);
        } else {
            $atts['limit'] = !empty($settings['limit']) ? $settings['limit'] : 6;
            $atts['orderby'] = !empty($settings['orderby']) ? $settings['orderby'] : 'title';
            $atts['order'] = !empty($settings['order']) ? $settings['order'] : 'ASC';
            
            // Type filter
            if (!empty($settings['location_type']) && is_array($settings['location_type'])) {
                $atts['type'] = implode(',', $settings['location_type']);
            }
        }
        
        // Columns
        if (!empty($settings['columns'])) {
            $atts['columns'] = $settings['columns'];
        }
        
        echo '<div class="es-elementor-widget es-locations-grid-widget">';
        $this->render_shortcode('ensemble_locations', $atts);
        echo '</div>';
    }
}
