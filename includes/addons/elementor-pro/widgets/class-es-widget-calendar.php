<?php
/**
 * Calendar Elementor Widget
 * 
 * Displays an interactive event calendar.
 * 
 * @package Ensemble
 * @subpackage Elementor/Widgets
 * @since 3.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Safety check - only define if base class exists
if (!class_exists('ES_Elementor_Widget_Base')) {
    return;
}

use Elementor\Controls_Manager;

/**
 * Class ES_Widget_Calendar
 */
class ES_Widget_Calendar extends ES_Elementor_Widget_Base {
    
    /**
     * Widget slug
     */
    public function get_name() {
        return 'ensemble-calendar';
    }
    
    /**
     * Widget title
     */
    public function get_title() {
        return __('Event Calendar', 'ensemble');
    }
    
    /**
     * Widget icon
     */
    public function get_icon() {
        return 'eicon-calendar';
    }
    
    /**
     * Widget category
     */
    protected $widget_category = 'ensemble-events';
    
    /**
     * Widget keywords
     */
    public function get_keywords() {
        return array('ensemble', 'calendar', 'kalender', 'events', 'schedule', 'termine');
    }
    
    /**
     * Get script depends
     */
    public function get_script_depends() {
        return array('ensemble-calendar');
    }
    
    /**
     * Get style depends
     */
    public function get_style_depends() {
        return array('ensemble-calendar');
    }
    
    /**
     * Register controls
     */
    protected function register_controls() {
        
        // ======================
        // CONTENT TAB
        // ======================
        
        // General Section
        $this->start_controls_section(
            'section_general',
            array(
                'label' => __('General', 'ensemble'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            )
        );
        
        $this->add_control(
            'view',
            array(
                'label'   => __('Default View', 'ensemble'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'month',
                'options' => array(
                    'month' => __('Monat', 'ensemble'),
                    'week'  => __('Woche', 'ensemble'),
                    'day'   => __('Tag', 'ensemble'),
                    'list'  => __('Liste', 'ensemble'),
                ),
            )
        );
        
        $this->add_control(
            'first_day',
            array(
                'label'   => __('Erster Wochentag', 'ensemble'),
                'type'    => Controls_Manager::SELECT,
                'default' => '1',
                'options' => array(
                    '0' => __('Sonntag', 'ensemble'),
                    '1' => __('Montag', 'ensemble'),
                ),
            )
        );
        
        $this->add_control(
            'locale',
            array(
                'label'   => __('Sprache', 'ensemble'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'de',
                'options' => array(
                    'de'    => __('Deutsch', 'ensemble'),
                    'en'    => __('Englisch', 'ensemble'),
                    'fr'    => __('French', 'ensemble'),
                    'es'    => __('Spanisch', 'ensemble'),
                    'it'    => __('Italienisch', 'ensemble'),
                    'auto'  => __('Automatisch (WP)', 'ensemble'),
                ),
            )
        );
        
        $this->end_controls_section();
        
        // Filter Section
        $this->start_controls_section(
            'section_filter',
            array(
                'label' => __('Filter', 'ensemble'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            )
        );
        
        $this->add_category_control('ensemble_category');
        
        $this->add_location_control();
        
        $this->add_artist_control();
        
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
            'show_navigation',
            array(
                'label'        => __('Show navigation', 'ensemble'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
            )
        );
        
        $this->add_control(
            'show_view_switcher',
            array(
                'label'        => __('Show view switcher', 'ensemble'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
            )
        );
        
        $this->add_control(
            'show_today_button',
            array(
                'label'        => __('Show today button', 'ensemble'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
            )
        );
        
        $this->add_control(
            'show_weekends',
            array(
                'label'        => __('Show weekends', 'ensemble'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
            )
        );
        
        $this->add_control(
            'event_display',
            array(
                'label'   => __('Event-Anzeige', 'ensemble'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'auto',
                'options' => array(
                    'auto'       => __('Automatisch', 'ensemble'),
                    'block'      => __('Block', 'ensemble'),
                    'list-item'  => __('Liste', 'ensemble'),
                    'background' => __('Hintergrund', 'ensemble'),
                ),
            )
        );
        
        $this->add_control(
            'event_limit',
            array(
                'label'       => __('Max Events pro Tag', 'ensemble'),
                'type'        => Controls_Manager::NUMBER,
                'default'     => 3,
                'min'         => 1,
                'max'         => 10,
                'description' => __('Weitere Events werden als "+X mehr" angezeigt', 'ensemble'),
            )
        );
        
        $this->end_controls_section();
        
        // Popup Section
        $this->start_controls_section(
            'section_popup',
            array(
                'label' => __('Event-Popup', 'ensemble'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            )
        );
        
        $this->add_control(
            'popup_enabled',
            array(
                'label'        => __('Popup aktivieren', 'ensemble'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
                'description'  => __('Show event details in popup on click', 'ensemble'),
            )
        );
        
        $this->add_control(
            'popup_show_image',
            array(
                'label'        => __('Bild im Popup', 'ensemble'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
                'condition'    => array(
                    'popup_enabled' => 'yes',
                ),
            )
        );
        
        $this->add_control(
            'popup_show_description',
            array(
                'label'        => __('Beschreibung im Popup', 'ensemble'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
                'condition'    => array(
                    'popup_enabled' => 'yes',
                ),
            )
        );
        
        $this->add_control(
            'popup_show_location',
            array(
                'label'        => __('Location im Popup', 'ensemble'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
                'condition'    => array(
                    'popup_enabled' => 'yes',
                ),
            )
        );
        
        $this->end_controls_section();
        
        // ======================
        // STYLE TAB
        // ======================
        
        // Calendar Container
        $this->start_controls_section(
            'section_style_container',
            array(
                'label' => __('Calendar', 'ensemble'),
                'tab'   => Controls_Manager::TAB_STYLE,
            )
        );
        
        $this->add_control(
            'calendar_background',
            array(
                'label'     => __('Hintergrundfarbe', 'ensemble'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .es-calendar' => 'background-color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            array(
                'name'     => 'calendar_border',
                'selector' => '{{WRAPPER}} .es-calendar',
            )
        );
        
        $this->add_responsive_control(
            'calendar_border_radius',
            array(
                'label'      => __('Border Radius', 'ensemble'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%'),
                'selectors'  => array(
                    '{{WRAPPER}} .es-calendar' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );
        
        $this->add_responsive_control(
            'calendar_padding',
            array(
                'label'      => __('Padding', 'ensemble'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', 'em'),
                'selectors'  => array(
                    '{{WRAPPER}} .es-calendar' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );
        
        $this->end_controls_section();
        
        // Header Styles
        $this->start_controls_section(
            'section_style_header',
            array(
                'label' => __('Header', 'ensemble'),
                'tab'   => Controls_Manager::TAB_STYLE,
            )
        );
        
        $this->add_control(
            'header_background',
            array(
                'label'     => __('Hintergrundfarbe', 'ensemble'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .es-calendar-header' => 'background-color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_control(
            'header_title_color',
            array(
                'label'     => __('Titel Farbe', 'ensemble'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .es-calendar-title' => 'color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            array(
                'name'     => 'header_typography',
                'selector' => '{{WRAPPER}} .es-calendar-title',
            )
        );
        
        $this->end_controls_section();
        
        // Day Cells
        $this->start_controls_section(
            'section_style_days',
            array(
                'label' => __('Tage', 'ensemble'),
                'tab'   => Controls_Manager::TAB_STYLE,
            )
        );
        
        $this->add_control(
            'day_background',
            array(
                'label'     => __('Hintergrundfarbe', 'ensemble'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .es-calendar-day' => 'background-color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_control(
            'day_text_color',
            array(
                'label'     => __('Textfarbe', 'ensemble'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .es-calendar-day-number' => 'color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_control(
            'today_background',
            array(
                'label'     => __('Heute - Hintergrund', 'ensemble'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .es-calendar-day.is-today' => 'background-color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_control(
            'weekend_background',
            array(
                'label'     => __('Wochenende - Hintergrund', 'ensemble'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .es-calendar-day.is-weekend' => 'background-color: {{VALUE}};',
                ),
            )
        );
        
        $this->end_controls_section();
        
        // Event Styles
        $this->start_controls_section(
            'section_style_events',
            array(
                'label' => __('Events', 'ensemble'),
                'tab'   => Controls_Manager::TAB_STYLE,
            )
        );
        
        $this->add_control(
            'event_background',
            array(
                'label'     => __('Hintergrundfarbe', 'ensemble'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .es-calendar-event' => 'background-color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_control(
            'event_text_color',
            array(
                'label'     => __('Textfarbe', 'ensemble'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .es-calendar-event' => 'color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_control(
            'event_use_category_color',
            array(
                'label'        => __('Kategorie-Farbe verwenden', 'ensemble'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
                'description'  => __('Events werden in ihrer Kategorie-Farbe angezeigt', 'ensemble'),
            )
        );
        
        $this->add_responsive_control(
            'event_border_radius',
            array(
                'label'      => __('Border Radius', 'ensemble'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => array('px'),
                'range'      => array(
                    'px' => array(
                        'min' => 0,
                        'max' => 20,
                    ),
                ),
                'selectors' => array(
                    '{{WRAPPER}} .es-calendar-event' => 'border-radius: {{SIZE}}{{UNIT}};',
                ),
            )
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            array(
                'name'     => 'event_typography',
                'selector' => '{{WRAPPER}} .es-calendar-event',
            )
        );
        
        $this->end_controls_section();
    }
    
    /**
     * Render widget output
     */
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        // Build shortcode attributes
        $atts = array(
            'view'               => $settings['view'],
            'first_day'          => $settings['first_day'],
            'locale'             => $settings['locale'] === 'auto' ? '' : $settings['locale'],
            'show_navigation'    => $settings['show_navigation'] === 'yes' ? '1' : '0',
            'show_view_switcher' => $settings['show_view_switcher'] === 'yes' ? '1' : '0',
            'show_today'         => $settings['show_today_button'] === 'yes' ? '1' : '0',
            'show_weekends'      => $settings['show_weekends'] === 'yes' ? '1' : '0',
            'event_display'      => $settings['event_display'],
            'event_limit'        => $settings['event_limit'],
            'popup'              => $settings['popup_enabled'] === 'yes' ? '1' : '0',
        );
        
        // Filter
        if (!empty($settings['category'])) {
            $atts['category'] = is_array($settings['category']) ? implode(',', $settings['category']) : $settings['category'];
        }
        
        if (!empty($settings['location'])) {
            $atts['location'] = is_array($settings['location']) ? implode(',', $settings['location']) : $settings['location'];
        }
        
        if (!empty($settings['artist'])) {
            $atts['artist'] = is_array($settings['artist']) ? implode(',', $settings['artist']) : $settings['artist'];
        }
        
        // Category color
        if ($settings['event_use_category_color'] === 'yes') {
            $atts['use_category_color'] = '1';
        }
        
        // Wrapper for editor identification
        echo '<div class="es-elementor-widget es-calendar-widget">';
        
        // Render shortcode
        $this->render_shortcode('ensemble_calendar', $atts);
        
        echo '</div>';
    }
    
    /**
     * Render plain content (for static export)
     */
    public function render_plain_content() {
        echo '[ensemble_calendar]';
    }
}
