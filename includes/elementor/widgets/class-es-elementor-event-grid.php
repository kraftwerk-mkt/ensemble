<?php
/**
 * Ensemble Elementor Event Grid Widget
 *
 * @package Ensemble
 * @since 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ES_Elementor_Event_Grid class.
 */
class ES_Elementor_Event_Grid extends ES_Elementor_Base_Widget {

	/**
	 * Get widget name.
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'ensemble-event-grid';
	}

	/**
	 * Get widget title.
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return esc_html__( 'Event Grid', 'ensemble' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'eicon-gallery-grid';
	}

	/**
	 * Get widget keywords.
	 *
	 * @return array Widget keywords.
	 */
	public function get_keywords() {
		return array( 'ensemble', 'events', 'grid', 'list', 'calendar', 'slider' );
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
				'grid'     => esc_html__( 'Grid', 'ensemble' ),
				'list'     => esc_html__( 'List', 'ensemble' ),
				'masonry'  => esc_html__( 'Masonry', 'ensemble' ),
				'slider'   => esc_html__( 'Slider', 'ensemble' ),
				'hero'     => esc_html__( 'Hero', 'ensemble' ),
				'carousel' => esc_html__( 'Carousel', 'ensemble' ),
			),
			'grid'
		);

		$this->add_control(
			'style',
			array(
				'label'   => esc_html__( 'Card Style', 'ensemble' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'default',
				'options' => array(
					'default'  => esc_html__( 'Default', 'ensemble' ),
					'minimal'  => esc_html__( 'Minimal', 'ensemble' ),
					'overlay'  => esc_html__( 'Overlay', 'ensemble' ),
					'compact'  => esc_html__( 'Compact', 'ensemble' ),
					'featured' => esc_html__( 'Featured', 'ensemble' ),
				),
			)
		);

		$this->add_columns_control( 3, 1, 4, array(
			'layout' => array( 'grid', 'masonry' ),
		) );

		$this->end_controls_section();

		// Slider Section
		$this->add_slider_controls_section();

		// Query Section
		$this->start_controls_section(
			'query_section',
			array(
				'label' => esc_html__( 'Query', 'ensemble' ),
			)
		);

		$this->add_limit_control( 12, 50 );

		$this->add_control(
			'offset',
			array(
				'label'   => esc_html__( 'Offset', 'ensemble' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'default' => 0,
				'min'     => 0,
				'max'     => 20,
			)
		);

		$this->add_control(
			'show',
			array(
				'label'   => esc_html__( 'Show', 'ensemble' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'upcoming',
				'options' => array(
					'upcoming' => esc_html__( 'Upcoming', 'ensemble' ),
					'past'     => esc_html__( 'Past', 'ensemble' ),
					'all'      => esc_html__( 'All', 'ensemble' ),
				),
			)
		);

		$this->add_order_controls(
			array(
				'event_date' => esc_html__( 'Event Date', 'ensemble' ),
				'title'      => esc_html__( 'Title', 'ensemble' ),
				'date'       => esc_html__( 'Published Date', 'ensemble' ),
			),
			'event_date',
			'ASC'
		);

		$this->add_control(
			'featured',
			array(
				'label'   => esc_html__( 'Featured Only', 'ensemble' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => '',
				'options' => array(
					''     => esc_html__( 'No', 'ensemble' ),
					'true' => esc_html__( 'Yes', 'ensemble' ),
				),
			)
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
			'category',
			array(
				'label'       => esc_html__( 'Category', 'ensemble' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'default'     => '',
				'options'     => $this->get_taxonomy_options( 'ensemble_category', esc_html__( 'All Categories', 'ensemble' ) ),
			)
		);

		$this->add_control(
			'location',
			array(
				'label'       => esc_html__( 'Location ID', 'ensemble' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => '',
				'description' => esc_html__( 'Filter by location post ID', 'ensemble' ),
			)
		);

		$this->add_control(
			'artist',
			array(
				'label'       => esc_html__( 'Artist ID', 'ensemble' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => '',
				'description' => esc_html__( 'Filter by artist post ID', 'ensemble' ),
			)
		);

		$this->end_controls_section();

		// Display Section
		$this->start_controls_section(
			'display_section',
			array(
				'label' => esc_html__( 'Display', 'ensemble' ),
			)
		);

		$this->add_toggle_control( 'show_image', esc_html__( 'Show Image', 'ensemble' ), true );
		$this->add_toggle_control( 'show_date', esc_html__( 'Show Date', 'ensemble' ), true );
		$this->add_toggle_control( 'show_time', esc_html__( 'Show Time', 'ensemble' ), true );
		$this->add_toggle_control( 'show_location', esc_html__( 'Show Location', 'ensemble' ), true );
		$this->add_toggle_control( 'show_category', esc_html__( 'Show Category', 'ensemble' ), true );
		$this->add_toggle_control( 'show_price', esc_html__( 'Show Price', 'ensemble' ), true );
		$this->add_toggle_control( 'show_desc', esc_html__( 'Show Description', 'ensemble' ), false );
		$this->add_toggle_control( 'show_artists', esc_html__( 'Show Artists', 'ensemble' ), false );

		$this->add_toggle_control(
			'show_filter',
			esc_html__( 'Show Filter', 'ensemble' ),
			false,
			esc_html__( 'Category filter dropdown', 'ensemble' )
		);

		$this->add_toggle_control(
			'show_search',
			esc_html__( 'Show Search', 'ensemble' ),
			false,
			esc_html__( 'Search input field', 'ensemble' )
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
			'layout'         => 'layout',
			'columns'        => 'columns',
			'style'          => 'style',
			'limit'          => 'limit',
			'offset'         => 'offset',
			'orderby'        => 'orderby',
			'order'          => 'order',
			'show'           => 'show',
			'featured'       => 'featured',
			'category'       => 'category',
			'location'       => 'location',
			'artist'         => 'artist',
			'show_image'     => 'show_image',
			'show_date'      => 'show_date',
			'show_time'      => 'show_time',
			'show_location'  => 'show_location',
			'show_category'  => 'show_category',
			'show_price'     => 'show_price',
			'show_desc'      => 'show_desc',
			'show_artists'   => 'show_artists',
			'show_filter'    => 'show_filter',
			'show_search'    => 'show_search',
			'autoplay'       => 'autoplay',
			'autoplay_speed' => 'autoplay_speed',
			'loop'           => 'loop',
			'dots'           => 'dots',
			'arrows'         => 'arrows',
			'fullscreen'     => 'fullscreen',
		);

		return $this->build_shortcode( 'ensemble_events', $settings, $attribute_map );
	}
}
