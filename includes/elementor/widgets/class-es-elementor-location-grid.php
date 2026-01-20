<?php
/**
 * Ensemble Elementor Location Grid Widget
 *
 * @package Ensemble
 * @since 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ES_Elementor_Location_Grid class.
 */
class ES_Elementor_Location_Grid extends ES_Elementor_Base_Widget {

	/**
	 * Get widget name.
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'ensemble-location-grid';
	}

	/**
	 * Get widget title.
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return esc_html__( 'Location Grid', 'ensemble' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'eicon-map-pin';
	}

	/**
	 * Get widget keywords.
	 *
	 * @return array Widget keywords.
	 */
	public function get_keywords() {
		return array( 'ensemble', 'locations', 'venues', 'places', 'grid' );
	}

	/**
	 * Register widget controls.
	 */
	protected function register_controls() {
		// Layout Section
		$this->start_controls_section(
			'layout_section',
			array(
				'label' => esc_html__( 'Layout', 'ensemble' ),
			)
		);

		$this->add_layout_control(
			array(
				'grid'   => esc_html__( 'Grid', 'ensemble' ),
				'list'   => esc_html__( 'List', 'ensemble' ),
				'cards'  => esc_html__( 'Cards', 'ensemble' ),
				'slider' => esc_html__( 'Slider', 'ensemble' ),
			),
			'grid'
		);

		$this->add_columns_control( 3, 2, 4, array(
			'layout' => array( 'grid', 'cards', 'slider' ),
		) );

		$this->add_limit_control( 12, 50 );

		$this->add_order_controls(
			array(
				'title'      => esc_html__( 'Title', 'ensemble' ),
				'date'       => esc_html__( 'Date', 'ensemble' ),
				'menu_order' => esc_html__( 'Menu Order', 'ensemble' ),
			),
			'title',
			'ASC'
		);

		$this->end_controls_section();

		// Filter Section
		$this->start_controls_section(
			'filter_section',
			array(
				'label' => esc_html__( 'Filter', 'ensemble' ),
			)
		);

		$this->add_control(
			'type',
			array(
				'label'   => esc_html__( 'Location Type', 'ensemble' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => '',
				'options' => $this->get_taxonomy_options( 'ensemble_location_type', esc_html__( 'All Types', 'ensemble' ) ),
			)
		);

		$this->end_controls_section();

		// Display Section
		$this->start_controls_section(
			'display_section',
			array(
				'label'     => esc_html__( 'Display', 'ensemble' ),
				'condition' => array(
					'layout' => array( 'grid', 'list' ),
				),
			)
		);

		$this->add_toggle_control( 'show_image', esc_html__( 'Show Image', 'ensemble' ), true );
		$this->add_toggle_control( 'show_name', esc_html__( 'Show Name', 'ensemble' ), true );

		$this->add_toggle_control(
			'show_type',
			esc_html__( 'Show Type', 'ensemble' ),
			true,
			esc_html__( 'Location type category', 'ensemble' )
		);

		$this->add_toggle_control(
			'show_address',
			esc_html__( 'Show Address', 'ensemble' ),
			true,
			esc_html__( 'Address and city', 'ensemble' )
		);

		$this->add_toggle_control(
			'show_capacity',
			esc_html__( 'Show Capacity', 'ensemble' ),
			false,
			esc_html__( 'Venue capacity', 'ensemble' )
		);

		$this->add_toggle_control(
			'show_events',
			esc_html__( 'Show Events Count', 'ensemble' ),
			false,
			esc_html__( 'Number of upcoming events', 'ensemble' )
		);

		$this->add_toggle_control( 'show_description', esc_html__( 'Show Description', 'ensemble' ), false );

		$this->add_toggle_control(
			'show_social',
			esc_html__( 'Show Social Links', 'ensemble' ),
			false,
			esc_html__( 'Website and social media', 'ensemble' )
		);

		$this->add_toggle_control( 'show_link', esc_html__( 'Show Link', 'ensemble' ), true );

		$this->add_control(
			'link_text',
			array(
				'label'     => esc_html__( 'Link Text', 'ensemble' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => 'View Location',
				'condition' => array(
					'show_link' => 'yes',
				),
			)
		);

		$this->end_controls_section();

		// Slider Section
		$this->start_controls_section(
			'slider_section',
			array(
				'label'     => esc_html__( 'Slider Settings', 'ensemble' ),
				'condition' => array(
					'layout' => 'slider',
				),
			)
		);

		$this->add_toggle_control( 'autoplay', esc_html__( 'Autoplay', 'ensemble' ), false );

		$this->add_control(
			'autoplay_speed',
			array(
				'label'     => esc_html__( 'Autoplay Speed (ms)', 'ensemble' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'default'   => 5000,
				'min'       => 1000,
				'max'       => 10000,
				'step'      => 500,
				'condition' => array(
					'autoplay' => 'yes',
				),
			)
		);

		$this->add_toggle_control( 'loop', esc_html__( 'Loop', 'ensemble' ), false );
		$this->add_toggle_control( 'dots', esc_html__( 'Dots', 'ensemble' ), true );
		$this->add_toggle_control( 'arrows', esc_html__( 'Arrows', 'ensemble' ), true );

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
			'layout'           => 'layout',
			'columns'          => 'columns',
			'limit'            => 'limit',
			'orderby'          => 'orderby',
			'order'            => 'order',
			'type'             => 'type',
			'show_image'       => 'show_image',
			'show_name'        => 'show_name',
			'show_type'        => 'show_type',
			'show_address'     => 'show_address',
			'show_capacity'    => 'show_capacity',
			'show_events'      => 'show_events',
			'show_description' => 'show_description',
			'show_social'      => 'show_social',
			'show_link'        => 'show_link',
			'link_text'        => 'link_text',
			'autoplay'         => 'autoplay',
			'autoplay_speed'   => 'autoplay_speed',
			'loop'             => 'loop',
			'dots'             => 'dots',
			'arrows'           => 'arrows',
		);

		return $this->build_shortcode( 'ensemble_locations', $settings, $attribute_map );
	}
}
