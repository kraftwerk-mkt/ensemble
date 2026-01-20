<?php
/**
 * Ensemble Elementor Artist Grid Widget
 *
 * @package Ensemble
 * @since 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ES_Elementor_Artist_Grid class.
 */
class ES_Elementor_Artist_Grid extends ES_Elementor_Base_Widget {

	/**
	 * Get widget name.
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'ensemble-artist-grid';
	}

	/**
	 * Get widget title.
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return esc_html__( 'Artist Grid', 'ensemble' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'eicon-person';
	}

	/**
	 * Get widget keywords.
	 *
	 * @return array Widget keywords.
	 */
	public function get_keywords() {
		return array( 'ensemble', 'artists', 'speakers', 'grid', 'team' );
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
				'cards'    => esc_html__( 'Cards', 'ensemble' ),
				'compact'  => esc_html__( 'Compact', 'ensemble' ),
				'slider'   => esc_html__( 'Slider', 'ensemble' ),
				'featured' => esc_html__( 'Featured', 'ensemble' ),
			),
			'grid'
		);

		$this->add_columns_control( 3, 2, 4 );

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
			'genre',
			array(
				'label'   => esc_html__( 'Genre', 'ensemble' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => '',
				'options' => $this->get_taxonomy_options( 'ensemble_genre', esc_html__( 'All Genres', 'ensemble' ) ),
			)
		);

		$this->add_control(
			'type',
			array(
				'label'   => esc_html__( 'Type', 'ensemble' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => '',
				'options' => $this->get_taxonomy_options( 'ensemble_artist_type', esc_html__( 'All Types', 'ensemble' ) ),
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
			'show_position',
			esc_html__( 'Show Position', 'ensemble' ),
			true,
			esc_html__( 'Job title or role', 'ensemble' )
		);

		$this->add_toggle_control( 'show_company', esc_html__( 'Show Company', 'ensemble' ), true );
		$this->add_toggle_control( 'show_genre', esc_html__( 'Show Genre', 'ensemble' ), false );

		$this->add_toggle_control(
			'show_type',
			esc_html__( 'Show Type', 'ensemble' ),
			false,
			esc_html__( 'Artist type category', 'ensemble' )
		);

		$this->add_toggle_control( 'show_bio', esc_html__( 'Show Bio', 'ensemble' ), true );

		$this->add_toggle_control(
			'show_events',
			esc_html__( 'Show Events Count', 'ensemble' ),
			false,
			esc_html__( 'Number of upcoming sessions', 'ensemble' )
		);

		$this->add_toggle_control( 'show_social', esc_html__( 'Show Social Links', 'ensemble' ), false );
		$this->add_toggle_control( 'show_link', esc_html__( 'Show Link', 'ensemble' ), true );

		$this->add_control(
			'link_text',
			array(
				'label'     => esc_html__( 'Link Text', 'ensemble' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => 'View Profile',
				'condition' => array(
					'show_link' => 'yes',
				),
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
			'layout'         => 'layout',
			'columns'        => 'columns',
			'limit'          => 'limit',
			'orderby'        => 'orderby',
			'order'          => 'order',
			'genre'          => 'genre',
			'type'           => 'type',
			'show_image'     => 'show_image',
			'show_name'      => 'show_name',
			'show_position'  => 'show_position',
			'show_company'   => 'show_company',
			'show_genre'     => 'show_genre',
			'show_type'      => 'show_type',
			'show_bio'       => 'show_bio',
			'show_events'    => 'show_events',
			'show_social'    => 'show_social',
			'show_link'      => 'show_link',
			'link_text'      => 'link_text',
			'autoplay'       => 'autoplay',
			'autoplay_speed' => 'autoplay_speed',
			'loop'           => 'loop',
			'dots'           => 'dots',
			'arrows'         => 'arrows',
		);

		return $this->build_shortcode( 'ensemble_artists', $settings, $attribute_map );
	}
}
