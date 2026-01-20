<?php
/**
 * Ensemble Elementor Countdown Widget
 *
 * @package Ensemble
 * @since 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ES_Elementor_Countdown class.
 */
class ES_Elementor_Countdown extends ES_Elementor_Base_Widget {

	/**
	 * Get widget name.
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'ensemble-countdown';
	}

	/**
	 * Get widget title.
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return esc_html__( 'Event Countdown', 'ensemble' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'eicon-countdown';
	}

	/**
	 * Get widget keywords.
	 *
	 * @return array Widget keywords.
	 */
	public function get_keywords() {
		return array( 'ensemble', 'countdown', 'timer', 'event' );
	}

	/**
	 * Register widget controls.
	 */
	protected function register_controls() {
		// Source Section
		$this->start_controls_section(
			'source_section',
			array(
				'label' => esc_html__( 'Countdown Source', 'ensemble' ),
			)
		);

		$this->add_control(
			'mode',
			array(
				'label'   => esc_html__( 'Countdown To', 'ensemble' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'event',
				'options' => array(
					'event' => esc_html__( 'Event', 'ensemble' ),
					'date'  => esc_html__( 'Custom Date', 'ensemble' ),
				),
			)
		);

		$this->add_control(
			'event_id',
			array(
				'label'       => esc_html__( 'Event ID', 'ensemble' ),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'default'     => '',
				'description' => esc_html__( 'Enter the ID of the event', 'ensemble' ),
				'condition'   => array(
					'mode' => 'event',
				),
			)
		);

		$this->add_control(
			'date',
			array(
				'label'     => esc_html__( 'Date', 'ensemble' ),
				'type'      => \Elementor\Controls_Manager::DATE_TIME,
				'default'   => '',
				'condition' => array(
					'mode' => 'date',
				),
			)
		);

		$this->add_control(
			'time',
			array(
				'label'       => esc_html__( 'Time', 'ensemble' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => '',
				'description' => esc_html__( 'Time in HH:MM format (optional)', 'ensemble' ),
				'condition'   => array(
					'mode' => 'date',
				),
			)
		);

		$this->add_control(
			'title',
			array(
				'label'       => esc_html__( 'Title', 'ensemble' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => '',
				'description' => esc_html__( 'Custom title for the countdown', 'ensemble' ),
				'condition'   => array(
					'mode' => 'date',
				),
			)
		);

		$this->end_controls_section();

		// Style Section
		$this->start_controls_section(
			'style_section',
			array(
				'label' => esc_html__( 'Style', 'ensemble' ),
			)
		);

		$this->add_control(
			'style',
			array(
				'label'   => esc_html__( 'Style', 'ensemble' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'default',
				'options' => array(
					'default' => esc_html__( 'Default', 'ensemble' ),
					'minimal' => esc_html__( 'Minimal', 'ensemble' ),
					'compact' => esc_html__( 'Compact', 'ensemble' ),
					'hero'    => esc_html__( 'Hero', 'ensemble' ),
				),
			)
		);

		$this->end_controls_section();

		// Display Section
		$this->start_controls_section(
			'display_section',
			array(
				'label' => esc_html__( 'Display Options', 'ensemble' ),
			)
		);

		$this->add_toggle_control( 'show_days', esc_html__( 'Show Days', 'ensemble' ), true );
		$this->add_toggle_control( 'show_hours', esc_html__( 'Show Hours', 'ensemble' ), true );
		$this->add_toggle_control( 'show_minutes', esc_html__( 'Show Minutes', 'ensemble' ), true );
		$this->add_toggle_control( 'show_seconds', esc_html__( 'Show Seconds', 'ensemble' ), true );
		$this->add_toggle_control( 'show_labels', esc_html__( 'Show Labels', 'ensemble' ), true );
		$this->add_toggle_control( 'show_title', esc_html__( 'Show Title', 'ensemble' ), true );
		$this->add_toggle_control( 'show_date', esc_html__( 'Show Date', 'ensemble' ), true );
		$this->add_toggle_control( 'show_link', esc_html__( 'Show Link', 'ensemble' ), true );

		$this->add_control(
			'link_text',
			array(
				'label'     => esc_html__( 'Link Text', 'ensemble' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => 'View Event',
				'condition' => array(
					'show_link' => 'yes',
				),
			)
		);

		$this->end_controls_section();

		// Expired Section
		$this->start_controls_section(
			'expired_section',
			array(
				'label' => esc_html__( 'Expired Options', 'ensemble' ),
			)
		);

		$this->add_control(
			'expired_text',
			array(
				'label'   => esc_html__( 'Expired Text', 'ensemble' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'Event has started!',
			)
		);

		$this->add_toggle_control( 'hide_expired', esc_html__( 'Hide When Expired', 'ensemble' ), false );

		$this->end_controls_section();
	}

	/**
	 * Get the shortcode to render.
	 *
	 * @param array $settings Widget settings.
	 * @return string Shortcode string.
	 */
	protected function get_shortcode( $settings ) {
		$shortcode_atts = array(
			'style'        => $settings['style'] ?? 'default',
			'show_days'    => ( $settings['show_days'] ?? '' ) === 'yes' ? 'true' : 'false',
			'show_hours'   => ( $settings['show_hours'] ?? '' ) === 'yes' ? 'true' : 'false',
			'show_minutes' => ( $settings['show_minutes'] ?? '' ) === 'yes' ? 'true' : 'false',
			'show_seconds' => ( $settings['show_seconds'] ?? '' ) === 'yes' ? 'true' : 'false',
			'show_labels'  => ( $settings['show_labels'] ?? '' ) === 'yes' ? 'true' : 'false',
			'show_title'   => ( $settings['show_title'] ?? '' ) === 'yes' ? 'true' : 'false',
			'show_date'    => ( $settings['show_date'] ?? '' ) === 'yes' ? 'true' : 'false',
			'show_link'    => ( $settings['show_link'] ?? '' ) === 'yes' ? 'true' : 'false',
			'link_text'    => $settings['link_text'] ?? 'View Event',
			'expired_text' => $settings['expired_text'] ?? 'Event has started!',
			'hide_expired' => ( $settings['hide_expired'] ?? '' ) === 'yes' ? 'true' : 'false',
		);

		// Add source (event or date)
		$mode = $settings['mode'] ?? 'event';
		if ( $mode === 'event' && ! empty( $settings['event_id'] ) ) {
			$shortcode_atts['event_id'] = $settings['event_id'];
		} elseif ( $mode === 'date' && ! empty( $settings['date'] ) ) {
			$shortcode_atts['date'] = $settings['date'];
			if ( ! empty( $settings['time'] ) ) {
				$shortcode_atts['time'] = $settings['time'];
			}
			if ( ! empty( $settings['title'] ) ) {
				$shortcode_atts['title'] = $settings['title'];
			}
		}

		// Build shortcode string
		$shortcode_parts = array();
		foreach ( $shortcode_atts as $key => $value ) {
			if ( $value !== '' ) {
				$shortcode_parts[] = $key . '="' . esc_attr( $value ) . '"';
			}
		}

		return '[ensemble_countdown ' . implode( ' ', $shortcode_parts ) . ']';
	}
}
