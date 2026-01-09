<?php
/**
 * Single Location Elementor Widget
 * 
 * Display a single location/venue with various layouts.
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
 * Class ES_Widget_Single_Location
 */
class ES_Widget_Single_Location extends ES_Elementor_Widget_Base {
    
    public function get_name() {
        return 'ensemble-single-location';
    }
    
    public function get_title() {
        return __('Single Location', 'ensemble');
    }
    
    public function get_icon() {
        return 'eicon-map-pin';
    }
    
    protected $widget_category = 'ensemble-content';
    
    public function get_keywords() {
        return array('ensemble', 'location', 'venue', 'single', 'ort', 'veranstaltungsort');
    }
    
    protected function register_controls() {
        
        // ======================
        // CONTENT TAB
        // ======================
        
        // Location Selection Section
        $this->start_controls_section(
            'section_location',
            array(
                'label' => __('Select location', 'ensemble'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            )
        );
        
        $this->add_control(
            'source',
            array(
                'label'   => __('Quelle', 'ensemble'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'manual',
                'options' => array(
                    'manual'  => __('Select location', 'ensemble'),
                    'current' => __('Aktuelle Location (Template)', 'ensemble'),
                ),
            )
        );
        
        $this->add_location_control(false); // false = single selection mode
        
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
                'default' => 'card',
                'options' => array(
                    'card'    => __('Card', 'ensemble'),
                    'compact' => __('Kompakt', 'ensemble'),
                    'full'    => __('Full', 'ensemble'),
                ),
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
            'show_address',
            array(
                'label'        => __('Show address', 'ensemble'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
            )
        );
        
        $this->add_control(
            'show_description',
            array(
                'label'        => __('Show description', 'ensemble'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
            )
        );
        
        $this->add_control(
            'show_events',
            array(
                'label'        => __('Show upcoming events', 'ensemble'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
            )
        );
        
        $this->add_control(
            'show_contact',
            array(
                'label'        => __('Show contact details', 'ensemble'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => '',
                'return_value' => 'yes',
                'condition'    => array(
                    'layout' => 'full',
                ),
            )
        );
        
        $this->add_control(
            'show_map',
            array(
                'label'        => __('Show map link', 'ensemble'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
            )
        );
        
        $this->add_control(
            'show_link',
            array(
                'label'        => __('Show link button', 'ensemble'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
            )
        );
        
        $this->add_control(
            'link_text',
            array(
                'label'     => __('Button Text', 'ensemble'),
                'type'      => Controls_Manager::TEXT,
                'default'   => __('Location ansehen', 'ensemble'),
                'condition' => array(
                    'show_link' => 'yes',
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
                    '{{WRAPPER}} .ensemble-single-location' => 'background-color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_group_control(
            Group_Control_Border::get_type(),
            array(
                'name'     => 'card_border',
                'selector' => '{{WRAPPER}} .ensemble-single-location',
            )
        );
        
        $this->add_responsive_control(
            'card_border_radius',
            array(
                'label'      => __('Border Radius', 'ensemble'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%'),
                'selectors'  => array(
                    '{{WRAPPER}} .ensemble-single-location' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );
        
        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            array(
                'name'     => 'card_shadow',
                'selector' => '{{WRAPPER}} .ensemble-single-location',
            )
        );
        
        $this->add_responsive_control(
            'card_padding',
            array(
                'label'      => __('Padding', 'ensemble'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', 'em'),
                'selectors'  => array(
                    '{{WRAPPER}} .ensemble-single-location' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
                    '{{WRAPPER}} .ensemble-single-location .ensemble-location-image' => 'height: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .ensemble-single-location .ensemble-location-image img' => 'height: {{SIZE}}{{UNIT}}; object-fit: cover;',
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
                    '{{WRAPPER}} .ensemble-single-location .ensemble-location-image' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .ensemble-single-location .ensemble-location-image img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
                    '{{WRAPPER}} .ensemble-single-location .ensemble-location-name' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .ensemble-single-location .ensemble-location-name a' => 'color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name'     => 'name_typography',
                'selector' => '{{WRAPPER}} .ensemble-single-location .ensemble-location-name',
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
                    '{{WRAPPER}} .ensemble-single-location .ensemble-location-address' => 'color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_control(
            'address_icon_color',
            array(
                'label'     => __('Icon Farbe', 'ensemble'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .ensemble-single-location .ensemble-location-address .dashicons' => 'color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name'     => 'address_typography',
                'selector' => '{{WRAPPER}} .ensemble-single-location .ensemble-location-address',
            )
        );
        
        $this->end_controls_section();
    }
    
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        // Debug in Editor mode
        $is_editor = \Elementor\Plugin::$instance->editor->is_edit_mode();
        
        // Get location ID
        $location_id = 0;
        if (!empty($settings['source']) && $settings['source'] === 'current') {
            $location_id = get_the_ID();
        } elseif (!empty($settings['location'])) {
            $location_id = is_array($settings['location']) ? $settings['location'][0] : $settings['location'];
        }
        
        // Debug output in editor
        if ($is_editor && !$location_id) {
            echo '<div class="es-editor-notice" style="padding: 20px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 4px; margin: 10px 0;">';
            echo '<strong>Single Location Widget</strong><br>';
            echo 'Source: ' . esc_html($settings['source'] ?? 'nicht gesetzt') . '<br>';
            echo 'Location Setting: ' . esc_html(print_r($settings['location'] ?? 'leer', true)) . '<br>';
            echo 'Please select a location from the dropdown.';
            echo '</div>';
            return;
        }
        
        if (!$location_id) {
            if ($is_editor) {
                $this->render_editor_placeholder(__('Please select a location.', 'ensemble'));
            }
            return;
        }
        
        // Build shortcode attributes
        $atts = array(
            'id'               => $location_id,
            'layout'           => !empty($settings['layout']) ? $settings['layout'] : 'card',
            'show_image'       => (!empty($settings['show_image']) && $settings['show_image'] === 'yes') ? 'true' : 'false',
            'show_address'     => (!empty($settings['show_address']) && $settings['show_address'] === 'yes') ? 'true' : 'false',
            'show_description' => (!empty($settings['show_description']) && $settings['show_description'] === 'yes') ? 'true' : 'false',
            'show_events'      => (!empty($settings['show_events']) && $settings['show_events'] === 'yes') ? 'true' : 'false',
            'show_link'        => (!empty($settings['show_link']) && $settings['show_link'] === 'yes') ? 'true' : 'false',
        );
        
        if (!empty($settings['link_text'])) {
            $atts['link_text'] = $settings['link_text'];
        }
        
        // Debug: Show shortcode being generated
        if ($is_editor) {
            echo '<!-- Shortcode: ' . esc_html($this->build_shortcode('ensemble_location', $atts)) . ' -->';
        }
        
        echo '<div class="es-elementor-widget es-single-location-widget">';
        $this->render_shortcode('ensemble_location', $atts);
        echo '</div>';
    }
}
