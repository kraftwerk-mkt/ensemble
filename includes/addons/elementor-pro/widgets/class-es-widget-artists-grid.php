<?php
/**
 * Artists Grid Elementor Widget
 * 
 * Display artists in grid or list layout.
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
 * Class ES_Widget_Artists_Grid
 */
class ES_Widget_Artists_Grid extends ES_Elementor_Widget_Base {
    
    public function get_name() {
        return 'ensemble-artists-grid';
    }
    
    public function get_title() {
        return __('Artists Grid', 'ensemble');
    }
    
    public function get_icon() {
        return 'eicon-person';
    }
    
    protected $widget_category = 'ensemble-content';
    
    public function get_keywords() {
        return array('ensemble', 'artists', 'artists', 'bands', 'performers', 'grid', 'list');
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
                    'all'    => __('All Artists', 'ensemble'),
                    'manual' => __('Select manually', 'ensemble'),
                ),
            )
        );
        
        // Artists for manual selection
        $artists_for_selection = $this->get_post_options('ensemble_artist', false);
        $this->add_control(
            'artist_ids',
            array(
                'label'       => __('Select artists', 'ensemble'),
                'type'        => Controls_Manager::SELECT2,
                'multiple'    => true,
                'options'     => $artists_for_selection,
                'default'     => array(),
                'label_block' => true,
                'condition'   => array(
                    'source' => 'manual',
                ),
            )
        );
        
        // Genre taxonomy if exists
        $genres = $this->get_taxonomy_options('ensemble_genre');
        if (!empty($genres)) {
            $this->add_control(
                'genre',
                array(
                    'label'       => __('Genre', 'ensemble'),
                    'type'        => Controls_Manager::SELECT2,
                    'multiple'    => true,
                    'options'     => $genres,
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
                'default'   => 8,
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
        
        $this->add_layout_controls(4, 24);
        
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
                    'wide'   => __('Breit (16:9)', 'ensemble'),
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
                'default'      => '',
                'return_value' => 'yes',
            )
        );
        
        $this->add_control(
            'bio_length',
            array(
                'label'     => __('Bio length (words)', 'ensemble'),
                'type'      => Controls_Manager::NUMBER,
                'default'   => 20,
                'min'       => 5,
                'max'       => 100,
                'condition' => array(
                    'show_bio' => 'yes',
                ),
            )
        );
        
        $this->add_control(
            'show_social',
            array(
                'label'        => __('Show social links', 'ensemble'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => '',
                'return_value' => 'yes',
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
                'default'     => __('No artists found.', 'ensemble'),
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
                    '{{WRAPPER}} .ensemble-artist-card' => 'background-color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_group_control(
            Group_Control_Border::get_type(),
            array(
                'name'     => 'card_border',
                'selector' => '{{WRAPPER}} .ensemble-artist-card',
            )
        );
        
        $this->add_responsive_control(
            'card_border_radius',
            array(
                'label'      => __('Border Radius', 'ensemble'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%'),
                'selectors'  => array(
                    '{{WRAPPER}} .ensemble-artist-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );
        
        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            array(
                'name'     => 'card_shadow',
                'selector' => '{{WRAPPER}} .ensemble-artist-card',
            )
        );
        
        $this->add_responsive_control(
            'card_padding',
            array(
                'label'      => __('Padding', 'ensemble'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', 'em'),
                'selectors'  => array(
                    '{{WRAPPER}} .ensemble-artist-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
                'size_units' => array('px', '%'),
                'range'      => array(
                    'px' => array('min' => 80, 'max' => 400),
                    '%'  => array('min' => 50, 'max' => 100),
                ),
                'selectors'  => array(
                    '{{WRAPPER}} .ensemble-artist-image' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .ensemble-artist-image img' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ),
                'condition'  => array(
                    'image_style!' => 'wide',
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
                    '{{WRAPPER}} .ensemble-artist-image' => 'height: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .ensemble-artist-image img' => 'height: {{SIZE}}{{UNIT}};',
                ),
                'condition'  => array(
                    'image_style' => 'wide',
                ),
            )
        );
        
        $this->add_group_control(
            Group_Control_Border::get_type(),
            array(
                'name'     => 'image_border',
                'selector' => '{{WRAPPER}} .ensemble-artist-image',
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
                    '{{WRAPPER}} .ensemble-artist-name' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .ensemble-artist-name a' => 'color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_control(
            'name_hover_color',
            array(
                'label'     => __('Hover Farbe', 'ensemble'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .ensemble-artist-name a:hover' => 'color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name'     => 'name_typography',
                'selector' => '{{WRAPPER}} .ensemble-artist-name',
            )
        );
        
        $this->add_responsive_control(
            'name_spacing',
            array(
                'label'      => __('Abstand unten', 'ensemble'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => array('px'),
                'range'      => array('px' => array('min' => 0, 'max' => 40)),
                'selectors'  => array(
                    '{{WRAPPER}} .ensemble-artist-name' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ),
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
                    '{{WRAPPER}} .ensemble-artist-genre' => 'color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name'     => 'genre_typography',
                'selector' => '{{WRAPPER}} .ensemble-artist-genre',
            )
        );
        
        $this->end_controls_section();
        
        // Social Styles
        $this->start_controls_section(
            'section_style_social',
            array(
                'label'     => __('Social Links', 'ensemble'),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => array(
                    'show_social' => 'yes',
                ),
            )
        );
        
        $this->add_control(
            'social_color',
            array(
                'label'     => __('Icon Farbe', 'ensemble'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .ensemble-artist-social a' => 'color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_control(
            'social_hover_color',
            array(
                'label'     => __('Hover Farbe', 'ensemble'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .ensemble-artist-social a:hover' => 'color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_responsive_control(
            'social_size',
            array(
                'label'      => __('Icon size', 'ensemble'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => array('px'),
                'range'      => array('px' => array('min' => 12, 'max' => 40)),
                'selectors'  => array(
                    '{{WRAPPER}} .ensemble-artist-social a' => 'font-size: {{SIZE}}{{UNIT}};',
                ),
            )
        );
        
        $this->end_controls_section();
    }
    
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        // Build shortcode attributes - match ensemble_artists shortcode parameters
        $atts = array(
            'layout'      => !empty($settings['layout']) ? $settings['layout'] : 'grid',
            'show_image'  => $settings['show_image'] === 'yes' ? 'true' : 'false',
            'show_bio'    => $settings['show_bio'] === 'yes' ? 'true' : 'false',
            'show_events' => $settings['show_events_count'] === 'yes' ? 'true' : 'false',
            'show_link'   => 'true',
        );
        
        // Source - manual selection or automatic
        if ($settings['source'] === 'manual' && !empty($settings['artist'])) {
            // Manual selection - use IDs (if shortcode supports it)
            $atts['ids'] = implode(',', $settings['artist']);
        } else {
            $atts['limit'] = !empty($settings['limit']) ? $settings['limit'] : 8;
            $atts['orderby'] = !empty($settings['orderby']) ? $settings['orderby'] : 'title';
            $atts['order'] = !empty($settings['order']) ? $settings['order'] : 'ASC';
            
            // Genre/Category filter
            if (!empty($settings['genre']) && is_array($settings['genre'])) {
                $atts['category'] = implode(',', $settings['genre']);
            }
        }
        
        // Columns
        if (!empty($settings['columns'])) {
            $atts['columns'] = $settings['columns'];
        }
        
        echo '<div class="es-elementor-widget es-artists-grid-widget">';
        $this->render_shortcode('ensemble_artists', $atts);
        echo '</div>';
    }
}
