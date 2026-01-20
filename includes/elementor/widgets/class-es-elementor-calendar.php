<?php
/**
 * Ensemble Elementor Calendar Widget
 *
 * @package Ensemble
 * @since 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ES_Elementor_Calendar class.
 */
class ES_Elementor_Calendar extends ES_Elementor_Base_Widget {

	/**
	 * Get widget name.
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'ensemble-calendar';
	}

	/**
	 * Get widget title.
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return esc_html__( 'Event Calendar', 'ensemble' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'eicon-calendar';
	}

	/**
	 * Get widget keywords.
	 *
	 * @return array Widget keywords.
	 */
	public function get_keywords() {
		return array( 'ensemble', 'calendar', 'events', 'schedule', 'fullcalendar' );
	}

	/**
	 * Register widget controls.
	 */
	protected function register_controls() {
		// Calendar Settings Section
		$this->start_controls_section(
			'calendar_section',
			array(
				'label' => esc_html__( 'Calendar Settings', 'ensemble' ),
			)
		);

		$this->add_control(
			'view',
			array(
				'label'   => esc_html__( 'Default View', 'ensemble' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'month',
				'options' => array(
					'month' => esc_html__( 'Month Grid', 'ensemble' ),
					'week'  => esc_html__( 'Week Grid', 'ensemble' ),
					'day'   => esc_html__( 'Day View', 'ensemble' ),
					'list'  => esc_html__( 'List View', 'ensemble' ),
				),
			)
		);

		$this->add_control(
			'height',
			array(
				'label'   => esc_html__( 'Height', 'ensemble' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'auto',
				'options' => array(
					'auto' => esc_html__( 'Auto', 'ensemble' ),
					'400'  => '400px',
					'500'  => '500px',
					'600'  => '600px',
					'700'  => '700px',
					'800'  => '800px',
				),
			)
		);

		$this->add_control(
			'initial_date',
			array(
				'label'       => esc_html__( 'Initial Date', 'ensemble' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => '',
				'description' => esc_html__( 'Start date in YYYY-MM-DD format. Leave empty for today.', 'ensemble' ),
			)
		);

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
			'view'         => 'view',
			'height'       => 'height',
			'initial_date' => 'initial_date',
		);

		return $this->build_shortcode( 'ensemble_calendar', $settings, $attribute_map );
	}
}
