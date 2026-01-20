<?php
/**
 * Ensemble Elementor Upcoming Events Widget
 *
 * @package Ensemble
 * @since 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ES_Elementor_Upcoming_Events class.
 */
class ES_Elementor_Upcoming_Events extends ES_Elementor_Base_Widget {

	/**
	 * Get widget name.
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'ensemble-upcoming-events';
	}

	/**
	 * Get widget title.
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return esc_html__( 'Upcoming Events', 'ensemble' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'eicon-clock-o';
	}

	/**
	 * Get widget keywords.
	 *
	 * @return array Widget keywords.
	 */
	public function get_keywords() {
		return array( 'ensemble', 'upcoming', 'events', 'next', 'widget', 'sidebar' );
	}

	/**
	 * Register widget controls.
	 */
	protected function register_controls() {
		// Settings Section
		$this->start_controls_section(
			'settings_section',
			array(
				'label' => esc_html__( 'Settings', 'ensemble' ),
			)
		);

		$this->add_control(
			'limit',
			array(
				'label'   => esc_html__( 'Number of Events', 'ensemble' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'default' => 5,
				'min'     => 1,
				'max'     => 20,
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

		$this->add_toggle_control(
			'show_countdown',
			esc_html__( 'Show Countdown', 'ensemble' ),
			false,
			esc_html__( 'Display countdown timer to each event.', 'ensemble' )
		);

		$this->add_toggle_control( 'show_image', esc_html__( 'Show Image', 'ensemble' ), true );
		$this->add_toggle_control( 'show_location', esc_html__( 'Show Location', 'ensemble' ), true );
		$this->add_toggle_control( 'show_artist', esc_html__( 'Show Artist', 'ensemble' ), true );

		$this->end_controls_section();
	}

	/**
	 * Get the shortcode to render.
	 *
	 * @param array $settings Widget settings.
	 * @return string Shortcode string.
	 */
	protected function get_shortcode( $settings ) {
		$attribute_map = array(
			'limit'          => 'limit',
			'show_countdown' => 'show_countdown',
			'show_image'     => 'show_image',
			'show_location'  => 'show_location',
			'show_artist'    => 'show_artist',
		);

		return $this->build_shortcode( 'ensemble_upcoming_events', $settings, $attribute_map );
	}
}
