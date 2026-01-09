<?php
/**
 * Lineup Elementor Widget
 * 
 * Display event lineup/timetable with artists.
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
 * Class ES_Widget_Lineup
 */
class ES_Widget_Lineup extends ES_Elementor_Widget_Base {
    
    public function get_name() {
        return 'ensemble-lineup';
    }
    
    public function get_title() {
        return __('Event Lineup', 'ensemble');
    }
    
    public function get_icon() {
        return 'eicon-bullet-list';
    }
    
    protected $widget_category = 'ensemble-events';
    
    public function get_keywords() {
        return array('ensemble', 'lineup', 'timetable', 'schedule', 'artists', 'programm');
    }
    
    protected function register_controls() {
        
        // ======================
        // CONTENT TAB
        // ======================
        
        // Event Selection Section
        $this->start_controls_section(
            'section_event',
            array(
                'label' => __('Select event', 'ensemble'),
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
                    'manual'  => __('Select event', 'ensemble'),
                    'current' => __('Aktuelles Event (Template)', 'ensemble'),
                ),
            )
        );
        
        $this->add_event_control(false);
        
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
                    'list' => __('Liste', 'ensemble'),
                    'grid' => __('Grid', 'ensemble'),
                ),
            )
        );
        
        $this->add_control(
            'columns',
            array(
                'label'     => __('Spalten', 'ensemble'),
                'type'      => Controls_Manager::SELECT,
                'default'   => '2',
                'options'   => array(
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                ),
                'condition' => array(
                    'layout' => 'grid',
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
            'show_header',
            array(
                'label'        => __('Show header', 'ensemble'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
            )
        );
        
        $this->add_control(
            'header_text',
            array(
                'label'       => __('Header Text', 'ensemble'),
                'type'        => Controls_Manager::TEXT,
                'default'     => __('Lineup', 'ensemble'),
                'label_block' => true,
                'condition'   => array(
                    'show_header' => 'yes',
                ),
            )
        );
        
        $this->add_control(
            'show_image',
            array(
                'label'        => __('Show artist images', 'ensemble'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
            )
        );
        
        $this->add_control(
            'show_times',
            array(
                'label'        => __('Show times', 'ensemble'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => '',
                'return_value' => 'yes',
            )
        );
        
        $this->add_control(
            'show_genre',
            array(
                'label'        => __('Show genre', 'ensemble'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
            )
        );
        
        $this->add_control(
            'show_bio',
            array(
                'label'        => __('Show bio', 'ensemble'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => '',
                'return_value' => 'yes',
            )
        );
        
        $this->add_control(
            'link_to_artist',
            array(
                'label'        => __('Link zu Artist-Seite', 'ensemble'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
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
                'default'     => __('No lineup information available.', 'ensemble'),
                'label_block' => true,
            )
        );
        
        $this->end_controls_section();
        
        // ======================
        // STYLE TAB
        // ======================
        
        // Header Styles
        $this->start_controls_section(
            'section_style_header',
            array(
                'label'     => __('Header', 'ensemble'),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => array(
                    'show_header' => 'yes',
                ),
            )
        );
        
        $this->add_control(
            'header_color',
            array(
                'label'     => __('Farbe', 'ensemble'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .ensemble-lineup-header h3' => 'color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name'     => 'header_typography',
                'selector' => '{{WRAPPER}} .ensemble-lineup-header h3',
            )
        );
        
        $this->add_responsive_control(
            'header_spacing',
            array(
                'label'      => __('Abstand unten', 'ensemble'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => array('px'),
                'range'      => array('px' => array('min' => 0, 'max' => 60)),
                'selectors'  => array(
                    '{{WRAPPER}} .ensemble-lineup-header' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ),
            )
        );
        
        $this->end_controls_section();
        
        // Item Styles
        $this->start_controls_section(
            'section_style_item',
            array(
                'label' => __('Lineup Item', 'ensemble'),
                'tab'   => Controls_Manager::TAB_STYLE,
            )
        );
        
        $this->add_control(
            'item_background',
            array(
                'label'     => __('Hintergrundfarbe', 'ensemble'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .ensemble-lineup-item' => 'background-color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_group_control(
            Group_Control_Border::get_type(),
            array(
                'name'     => 'item_border',
                'selector' => '{{WRAPPER}} .ensemble-lineup-item',
            )
        );
        
        $this->add_responsive_control(
            'item_border_radius',
            array(
                'label'      => __('Border Radius', 'ensemble'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array('px'),
                'selectors'  => array(
                    '{{WRAPPER}} .ensemble-lineup-item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
                    '{{WRAPPER}} .ensemble-lineup-item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );
        
        $this->add_responsive_control(
            'item_spacing',
            array(
                'label'      => __('Abstand zwischen Items', 'ensemble'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => array('px'),
                'range'      => array('px' => array('min' => 0, 'max' => 40)),
                'selectors'  => array(
                    '{{WRAPPER}} .ensemble-lineup-item + .ensemble-lineup-item' => 'margin-top: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .ensemble-layout-grid .ensemble-lineup-item' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ),
            )
        );
        
        $this->end_controls_section();
        
        // Image Styles
        $this->start_controls_section(
            'section_style_image',
            array(
                'label'     => __('Artist-Bild', 'ensemble'),
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
                'default'    => array('size' => 80, 'unit' => 'px'),
                'selectors'  => array(
                    '{{WRAPPER}} .ensemble-lineup-image' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .ensemble-lineup-image img' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
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
                    'px' => array('min' => 0, 'max' => 100),
                    '%'  => array('min' => 0, 'max' => 50),
                ),
                'selectors'  => array(
                    '{{WRAPPER}} .ensemble-lineup-image' => 'border-radius: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .ensemble-lineup-image img' => 'border-radius: {{SIZE}}{{UNIT}};',
                ),
            )
        );
        
        $this->end_controls_section();
        
        // Name Styles
        $this->start_controls_section(
            'section_style_name',
            array(
                'label' => __('Artist-Name', 'ensemble'),
                'tab'   => Controls_Manager::TAB_STYLE,
            )
        );
        
        $this->add_control(
            'name_color',
            array(
                'label'     => __('Farbe', 'ensemble'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .ensemble-lineup-name' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .ensemble-lineup-name a' => 'color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_control(
            'name_hover_color',
            array(
                'label'     => __('Hover Farbe', 'ensemble'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .ensemble-lineup-name a:hover' => 'color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name'     => 'name_typography',
                'selector' => '{{WRAPPER}} .ensemble-lineup-name',
            )
        );
        
        $this->end_controls_section();
        
        // Time Styles
        $this->start_controls_section(
            'section_style_time',
            array(
                'label'     => __('Uhrzeit', 'ensemble'),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => array(
                    'show_times' => 'yes',
                ),
            )
        );
        
        $this->add_control(
            'time_color',
            array(
                'label'     => __('Farbe', 'ensemble'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .ensemble-lineup-time' => 'color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name'     => 'time_typography',
                'selector' => '{{WRAPPER}} .ensemble-lineup-time',
            )
        );
        
        $this->end_controls_section();
        
        // Genre Styles
        $this->start_controls_section(
            'section_style_genre',
            array(
                'label'     => __('Genre', 'ensemble'),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => array(
                    'show_genre' => 'yes',
                ),
            )
        );
        
        $this->add_control(
            'genre_color',
            array(
                'label'     => __('Farbe', 'ensemble'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .ensemble-lineup-genre' => 'color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name'     => 'genre_typography',
                'selector' => '{{WRAPPER}} .ensemble-lineup-genre',
            )
        );
        
        $this->end_controls_section();
    }
    
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        // Get event ID
        $event_id = 0;
        if ($settings['source'] === 'current') {
            $event_id = get_the_ID();
        } elseif (!empty($settings['events'])) {
            $event_id = is_array($settings['events']) ? $settings['events'][0] : $settings['events'];
        }
        
        if (!$event_id) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                $this->render_editor_placeholder(__('Please select an event.', 'ensemble'));
            }
            return;
        }
        
        // Build shortcode attributes
        $atts = array(
            'event_id'   => $event_id,
            'layout'     => !empty($settings['layout']) ? $settings['layout'] : 'list',
            'show_times' => $settings['show_times'] === 'yes' ? 'true' : 'false',
            'show_genre' => $settings['show_genre'] === 'yes' ? 'true' : 'false',
            'show_bio'   => $settings['show_bio'] === 'yes' ? 'true' : 'false',
        );
        
        echo '<div class="es-elementor-widget es-lineup-widget">';
        
        // Custom header if enabled
        if ($settings['show_header'] === 'yes' && !empty($settings['header_text'])) {
            echo '<div class="ensemble-lineup-header"><h3>' . esc_html($settings['header_text']) . '</h3></div>';
        }
        
        $this->render_shortcode('ensemble_lineup', $atts);
        echo '</div>';
    }
}
