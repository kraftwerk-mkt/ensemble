<?php
/**
 * Single Artist Elementor Widget
 * 
 * Display a single artist with various layouts.
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
 * Class ES_Widget_Single_Artist
 */
class ES_Widget_Single_Artist extends ES_Elementor_Widget_Base {
    
    public function get_name() {
        return 'ensemble-single-artist';
    }
    
    public function get_title() {
        return __('Single Artist', 'ensemble');
    }
    
    public function get_icon() {
        return 'eicon-user-circle-o';
    }
    
    protected $widget_category = 'ensemble-content';
    
    public function get_keywords() {
        return array('ensemble', 'artist', 'single', 'artist', 'band', 'performer');
    }
    
    protected function register_controls() {
        
        // ======================
        // CONTENT TAB
        // ======================
        
        // Artist Selection Section
        $this->start_controls_section(
            'section_artist',
            array(
                'label' => __('Select artist', 'ensemble'),
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
                    'manual'  => __('Select artist', 'ensemble'),
                    'current' => __('Aktueller Artist (Template)', 'ensemble'),
                ),
            )
        );
        
        $this->add_artist_control(false); // false = single selection mode
        
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
            'image_style',
            array(
                'label'     => __('Bild Stil', 'ensemble'),
                'type'      => Controls_Manager::SELECT,
                'default'   => 'square',
                'options'   => array(
                    'square' => __('Quadratisch', 'ensemble'),
                    'circle' => __('Rund', 'ensemble'),
                ),
                'condition' => array(
                    'show_image' => 'yes',
                ),
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
            'show_social',
            array(
                'label'        => __('Show social links', 'ensemble'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => '',
                'return_value' => 'yes',
                'condition'    => array(
                    'layout' => 'full',
                ),
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
                'default'   => __('Profil ansehen', 'ensemble'),
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
                    '{{WRAPPER}} .ensemble-single-artist' => 'background-color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_group_control(
            Group_Control_Border::get_type(),
            array(
                'name'     => 'card_border',
                'selector' => '{{WRAPPER}} .ensemble-single-artist',
            )
        );
        
        $this->add_responsive_control(
            'card_border_radius',
            array(
                'label'      => __('Border Radius', 'ensemble'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%'),
                'selectors'  => array(
                    '{{WRAPPER}} .ensemble-single-artist' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );
        
        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            array(
                'name'     => 'card_shadow',
                'selector' => '{{WRAPPER}} .ensemble-single-artist',
            )
        );
        
        $this->add_responsive_control(
            'card_padding',
            array(
                'label'      => __('Padding', 'ensemble'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', 'em'),
                'selectors'  => array(
                    '{{WRAPPER}} .ensemble-single-artist' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
                    'px' => array('min' => 80, 'max' => 400),
                ),
                'selectors'  => array(
                    '{{WRAPPER}} .ensemble-single-artist .ensemble-artist-image' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .ensemble-single-artist .ensemble-artist-image img' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ),
            )
        );
        
        $this->add_group_control(
            Group_Control_Border::get_type(),
            array(
                'name'     => 'image_border',
                'selector' => '{{WRAPPER}} .ensemble-single-artist .ensemble-artist-image',
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
                    '{{WRAPPER}} .ensemble-single-artist .ensemble-artist-name' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .ensemble-single-artist .ensemble-artist-name a' => 'color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name'     => 'name_typography',
                'selector' => '{{WRAPPER}} .ensemble-single-artist .ensemble-artist-name',
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
                    '{{WRAPPER}} .ensemble-single-artist .ensemble-artist-genre' => 'color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name'     => 'genre_typography',
                'selector' => '{{WRAPPER}} .ensemble-single-artist .ensemble-artist-genre',
            )
        );
        
        $this->end_controls_section();
    }
    
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        // Debug in Editor mode
        $is_editor = \Elementor\Plugin::$instance->editor->is_edit_mode();
        
        // Get artist ID
        $artist_id = 0;
        if (!empty($settings['source']) && $settings['source'] === 'current') {
            $artist_id = get_the_ID();
        } elseif (!empty($settings['artist'])) {
            $artist_id = is_array($settings['artist']) ? $settings['artist'][0] : $settings['artist'];
        }
        
        // Debug output in editor
        if ($is_editor && !$artist_id) {
            echo '<div class="es-editor-notice" style="padding: 20px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 4px; margin: 10px 0;">';
            echo '<strong>Single Artist Widget</strong><br>';
            echo 'Source: ' . esc_html($settings['source'] ?? 'nicht gesetzt') . '<br>';
            echo 'Artist Setting: ' . esc_html(print_r($settings['artist'] ?? 'leer', true)) . '<br>';
            echo 'Please select an artist from the dropdown.';
            echo '</div>';
            return;
        }
        
        if (!$artist_id) {
            if ($is_editor) {
                $this->render_editor_placeholder(__('Please select an artist.', 'ensemble'));
            }
            return;
        }
        
        // Build shortcode attributes
        $atts = array(
            'id'          => $artist_id,
            'layout'      => !empty($settings['layout']) ? $settings['layout'] : 'card',
            'show_image'  => (!empty($settings['show_image']) && $settings['show_image'] === 'yes') ? 'true' : 'false',
            'show_genre'  => (!empty($settings['show_genre']) && $settings['show_genre'] === 'yes') ? 'true' : 'false',
            'show_bio'    => (!empty($settings['show_bio']) && $settings['show_bio'] === 'yes') ? 'true' : 'false',
            'show_events' => (!empty($settings['show_events']) && $settings['show_events'] === 'yes') ? 'true' : 'false',
            'show_link'   => (!empty($settings['show_link']) && $settings['show_link'] === 'yes') ? 'true' : 'false',
        );
        
        if (!empty($settings['link_text'])) {
            $atts['link_text'] = $settings['link_text'];
        }
        
        // Add wrapper class for image style
        $wrapper_class = 'es-elementor-widget es-single-artist-widget';
        if (!empty($settings['show_image']) && $settings['show_image'] === 'yes' && !empty($settings['image_style']) && $settings['image_style'] === 'circle') {
            $wrapper_class .= ' es-artist-image-circle';
        }
        
        // Debug: Show shortcode being generated
        if ($is_editor) {
            echo '<!-- Shortcode: ' . esc_html($this->build_shortcode('ensemble_artist', $atts)) . ' -->';
        }
        
        echo '<div class="' . esc_attr($wrapper_class) . '">';
        $this->render_shortcode('ensemble_artist', $atts);
        echo '</div>';
    }
}
