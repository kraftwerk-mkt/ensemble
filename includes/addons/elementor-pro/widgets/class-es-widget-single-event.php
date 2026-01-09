<?php
/**
 * Single Event Elementor Widget
 * 
 * Display a single event with various layouts.
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
 * Class ES_Widget_Single_Event
 */
class ES_Widget_Single_Event extends ES_Elementor_Widget_Base {
    
    public function get_name() {
        return 'ensemble-single-event';
    }
    
    public function get_title() {
        return __('Single Event', 'ensemble');
    }
    
    public function get_icon() {
        return 'eicon-single-post';
    }
    
    protected $widget_category = 'ensemble-events';
    
    public function get_keywords() {
        return array('ensemble', 'event', 'single', 'einzeln', 'veranstaltung');
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
            'show_artist',
            array(
                'label'        => __('Show artist', 'ensemble'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
            )
        );
        
        $this->add_control(
            'show_excerpt',
            array(
                'label'        => __('Show description', 'ensemble'),
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
                'default'   => __('Details ansehen', 'ensemble'),
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
                    '{{WRAPPER}} .ensemble-single-event' => 'background-color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_group_control(
            Group_Control_Border::get_type(),
            array(
                'name'     => 'card_border',
                'selector' => '{{WRAPPER}} .ensemble-single-event',
            )
        );
        
        $this->add_responsive_control(
            'card_border_radius',
            array(
                'label'      => __('Border Radius', 'ensemble'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%'),
                'selectors'  => array(
                    '{{WRAPPER}} .ensemble-single-event' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );
        
        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            array(
                'name'     => 'card_shadow',
                'selector' => '{{WRAPPER}} .ensemble-single-event',
            )
        );
        
        $this->add_responsive_control(
            'card_padding',
            array(
                'label'      => __('Padding', 'ensemble'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', 'em'),
                'selectors'  => array(
                    '{{WRAPPER}} .ensemble-single-event' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
                    '{{WRAPPER}} .ensemble-single-event .ensemble-event-title' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .ensemble-single-event .ensemble-event-title a' => 'color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name'     => 'title_typography',
                'selector' => '{{WRAPPER}} .ensemble-single-event .ensemble-event-title',
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
                    'show_link' => 'yes',
                ),
            )
        );
        
        $this->add_control(
            'button_background',
            array(
                'label'     => __('Hintergrundfarbe', 'ensemble'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .ensemble-single-event .ensemble-event-link' => 'background-color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_control(
            'button_color',
            array(
                'label'     => __('Textfarbe', 'ensemble'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .ensemble-single-event .ensemble-event-link' => 'color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_control(
            'button_hover_background',
            array(
                'label'     => __('Hover Hintergrund', 'ensemble'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .ensemble-single-event .ensemble-event-link:hover' => 'background-color: {{VALUE}};',
                ),
            )
        );
        
        $this->end_controls_section();
    }
    
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        // Debug in Editor mode
        $is_editor = \Elementor\Plugin::$instance->editor->is_edit_mode();
        
        // Get event ID
        $event_id = 0;
        if (!empty($settings['source']) && $settings['source'] === 'current') {
            $event_id = get_the_ID();
        } elseif (!empty($settings['events'])) {
            $event_id = is_array($settings['events']) ? $settings['events'][0] : $settings['events'];
        }
        
        // Debug output in editor
        if ($is_editor && !$event_id) {
            echo '<div class="es-editor-notice" style="padding: 20px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 4px; margin: 10px 0;">';
            echo '<strong>Single Event Widget</strong><br>';
            echo 'Source: ' . esc_html($settings['source'] ?? 'nicht gesetzt') . '<br>';
            echo 'Events Setting: ' . esc_html(print_r($settings['events'] ?? 'leer', true)) . '<br>';
            echo 'Please select an event from the dropdown.';
            echo '</div>';
            return;
        }
        
        if (!$event_id) {
            if ($is_editor) {
                $this->render_editor_placeholder(__('Please select an event.', 'ensemble'));
            }
            return;
        }
        
        // Build shortcode attributes
        $atts = array(
            'id'            => $event_id,
            'layout'        => !empty($settings['layout']) ? $settings['layout'] : 'card',
            'show_image'    => (!empty($settings['show_image']) && $settings['show_image'] === 'yes') ? 'true' : 'false',
            'show_date'     => (!empty($settings['show_date']) && $settings['show_date'] === 'yes') ? 'true' : 'false',
            'show_time'     => (!empty($settings['show_time']) && $settings['show_time'] === 'yes') ? 'true' : 'false',
            'show_location' => (!empty($settings['show_location']) && $settings['show_location'] === 'yes') ? 'true' : 'false',
            'show_artist'   => (!empty($settings['show_artist']) && $settings['show_artist'] === 'yes') ? 'true' : 'false',
            'show_excerpt'  => (!empty($settings['show_excerpt']) && $settings['show_excerpt'] === 'yes') ? 'true' : 'false',
            'show_link'     => (!empty($settings['show_link']) && $settings['show_link'] === 'yes') ? 'true' : 'false',
        );
        
        if (!empty($settings['link_text'])) {
            $atts['link_text'] = $settings['link_text'];
        }
        
        // Debug: Show shortcode being generated
        if ($is_editor) {
            echo '<!-- Shortcode: ' . esc_html($this->build_shortcode('ensemble_event', $atts)) . ' -->';
        }
        
        echo '<div class="es-elementor-widget es-single-event-widget">';
        $this->render_shortcode('ensemble_event', $atts);
        echo '</div>';
    }
}
