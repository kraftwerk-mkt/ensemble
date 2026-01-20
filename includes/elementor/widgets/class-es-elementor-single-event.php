<?php
/**
 * Ensemble Elementor Single Event Widget
 *
 * @package Ensemble
 * @since 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ES_Elementor_Single_Event class.
 */
class ES_Elementor_Single_Event extends ES_Elementor_Base_Widget {

	/**
	 * Get widget name.
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'ensemble-single-event';
	}

	/**
	 * Get widget title.
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return esc_html__( 'Single Event', 'ensemble' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'eicon-single-post';
	}

	/**
	 * Get widget keywords.
	 *
	 * @return array Widget keywords.
	 */
	public function get_keywords() {
		return array( 'ensemble', 'event', 'single', 'card' );
	}

	/**
	 * Register widget controls.
	 */
	protected function register_controls() {
		// Event Selection Section
		$this->start_controls_section(
			'event_section',
			array(
				'label' => esc_html__( 'Event Selection', 'ensemble' ),
			)
		);

		$this->add_control(
			'event_id',
			array(
				'label'       => esc_html__( 'Event ID', 'ensemble' ),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'default'     => '',
				'description' => esc_html__( 'Enter the ID of the event to display', 'ensemble' ),
			)
		);

		$this->end_controls_section();

		// Layout Section
		$this->start_controls_section(
			'layout_section',
			array(
				'label' => esc_html__( 'Layout', 'ensemble' ),
			)
		);

		$this->add_control(
			'layout',
			array(
				'label'   => esc_html__( 'Display Style', 'ensemble' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'card',
				'options' => array(
					'card'    => esc_html__( 'Card', 'ensemble' ),
					'compact' => esc_html__( 'Compact', 'ensemble' ),
					'full'    => esc_html__( 'Full', 'ensemble' ),
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

		$this->add_toggle_control( 'show_image', esc_html__( 'Show Image', 'ensemble' ), true );
		$this->add_toggle_control( 'show_date', esc_html__( 'Show Date', 'ensemble' ), true );
		$this->add_toggle_control( 'show_time', esc_html__( 'Show Time', 'ensemble' ), true );
		$this->add_toggle_control( 'show_location', esc_html__( 'Show Location', 'ensemble' ), true );
		$this->add_toggle_control( 'show_artist', esc_html__( 'Show Artist', 'ensemble' ), true );
		$this->add_toggle_control( 'show_excerpt', esc_html__( 'Show Excerpt', 'ensemble' ), true );

		$this->end_controls_section();

		// Link Section
		$this->start_controls_section(
			'link_section',
			array(
				'label' => esc_html__( 'Link Options', 'ensemble' ),
			)
		);

		$this->add_toggle_control( 'show_link', esc_html__( 'Show Link Button', 'ensemble' ), true );

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
	}

	/**
	 * Get the shortcode to render.
	 *
	 * @param array $settings Widget settings.
	 * @return string Shortcode string.
	 */
	protected function get_shortcode( $settings ) {
		// Check for event ID
		if ( empty( $settings['event_id'] ) ) {
			return '';
		}

		$shortcode_atts = array(
			'id'            => $settings['event_id'],
			'layout'        => $settings['layout'] ?? 'card',
			'show_image'    => ( $settings['show_image'] ?? '' ) === 'yes' ? 'true' : 'false',
			'show_date'     => ( $settings['show_date'] ?? '' ) === 'yes' ? 'true' : 'false',
			'show_time'     => ( $settings['show_time'] ?? '' ) === 'yes' ? 'true' : 'false',
			'show_location' => ( $settings['show_location'] ?? '' ) === 'yes' ? 'true' : 'false',
			'show_artist'   => ( $settings['show_artist'] ?? '' ) === 'yes' ? 'true' : 'false',
			'show_excerpt'  => ( $settings['show_excerpt'] ?? '' ) === 'yes' ? 'true' : 'false',
			'show_link'     => ( $settings['show_link'] ?? '' ) === 'yes' ? 'true' : 'false',
			'link_text'     => $settings['link_text'] ?? 'View Event',
		);

		// Build shortcode string
		$shortcode_parts = array();
		foreach ( $shortcode_atts as $key => $value ) {
			if ( $value !== '' ) {
				$shortcode_parts[] = $key . '="' . esc_attr( $value ) . '"';
			}
		}

		return '[ensemble_event ' . implode( ' ', $shortcode_parts ) . ']';
	}

	/**
	 * Render the widget output.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		if ( empty( $settings['event_id'] ) ) {
			echo '<div class="ensemble-elementor-widget ensemble-elementor-single-event">';
			echo '<p style="text-align:center;color:#666;padding:20px;">' . esc_html__( 'Please select an event', 'ensemble' ) . '</p>';
			echo '</div>';
			return;
		}

		parent::render();
	}
}
