<?php
/**
 * Ensemble Elementor Base Widget
 *
 * Abstract base class for all Ensemble Elementor widgets.
 * Provides common functionality, helper methods, and Style Tab integration.
 *
 * @package Ensemble
 * @since 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ES_Elementor_Base_Widget class.
 */
abstract class ES_Elementor_Base_Widget extends \Elementor\Widget_Base {

	/**
	 * Get widget categories.
	 *
	 * @return array Widget categories.
	 */
	public function get_categories() {
		return array( 'ensemble' );
	}

	/**
	 * Get widget keywords.
	 *
	 * @return array Widget keywords.
	 */
	public function get_keywords() {
		return array( 'ensemble', 'events' );
	}

	/**
	 * Get style dependencies.
	 *
	 * @return array Style dependencies.
	 */
	public function get_style_depends() {
		return array( 'ensemble-frontend' );
	}

	/**
	 * Register controls - called by child classes.
	 * Child classes should call parent::register_controls() at the end.
	 */
	protected function register_controls() {
		// Child classes add their controls first, then call parent
		// Style controls are added in _register_controls wrapper
	}

	/**
	 * Register all controls including style tab.
	 * This wraps the child's register_controls and adds style tab.
	 */
	protected function _register_controls() {
		// Let child register its controls first
		$this->register_controls();
		
		// Add style tab controls
		$this->register_style_controls();
	}

	/**
	 * Register Style Tab Controls.
	 * These are inherited by all Ensemble widgets.
	 */
	protected function register_style_controls() {
		// ===========================================
		// STYLE TAB: Global/Custom Toggle
		// ===========================================
		$this->start_controls_section(
			'style_mode_section',
			array(
				'label' => esc_html__( 'Style Mode', 'ensemble' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'style_mode',
			array(
				'label'       => esc_html__( 'Style Source', 'ensemble' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'default'     => 'global',
				'options'     => array(
					'global' => esc_html__( 'Global (Ensemble Designer)', 'ensemble' ),
					'custom' => esc_html__( 'Custom (Override)', 'ensemble' ),
				),
				'description' => esc_html__( 'Use global Designer settings or customize per widget.', 'ensemble' ),
			)
		);

		$this->add_control(
			'style_mode_info',
			array(
				'type'            => \Elementor\Controls_Manager::RAW_HTML,
				'raw'             => '<div style="background:#f0f6fc;padding:10px;border-radius:4px;font-size:12px;">'
					. '<strong>' . esc_html__( 'Global Mode:', 'ensemble' ) . '</strong> '
					. esc_html__( 'Uses colors and styles from Ensemble → Designer.', 'ensemble' )
					. '<br><br><strong>' . esc_html__( 'Custom Mode:', 'ensemble' ) . '</strong> '
					. esc_html__( 'Override styles for this widget only.', 'ensemble' )
					. '</div>',
				'content_classes' => 'elementor-panel-alert',
				'condition'       => array(
					'style_mode' => 'global',
				),
			)
		);

		$this->end_controls_section();

		// ===========================================
		// STYLE TAB: Colors
		// ===========================================
		$this->start_controls_section(
			'style_colors_section',
			array(
				'label'     => esc_html__( 'Colors', 'ensemble' ),
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => array(
					'style_mode' => 'custom',
				),
			)
		);

		$this->add_control(
			'custom_primary_color',
			array(
				'label'   => esc_html__( 'Primary Color', 'ensemble' ),
				'type'    => \Elementor\Controls_Manager::COLOR,
				'default' => '',
				'selectors' => array(
					'{{WRAPPER}} .ensemble-elementor-widget' => '--ensemble-primary: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'custom_secondary_color',
			array(
				'label'   => esc_html__( 'Secondary Color', 'ensemble' ),
				'type'    => \Elementor\Controls_Manager::COLOR,
				'default' => '',
				'selectors' => array(
					'{{WRAPPER}} .ensemble-elementor-widget' => '--ensemble-secondary: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'custom_text_color',
			array(
				'label'   => esc_html__( 'Text Color', 'ensemble' ),
				'type'    => \Elementor\Controls_Manager::COLOR,
				'default' => '',
				'selectors' => array(
					'{{WRAPPER}} .ensemble-elementor-widget' => '--ensemble-text: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'custom_text_secondary_color',
			array(
				'label'   => esc_html__( 'Text Secondary', 'ensemble' ),
				'type'    => \Elementor\Controls_Manager::COLOR,
				'default' => '',
				'selectors' => array(
					'{{WRAPPER}} .ensemble-elementor-widget' => '--ensemble-text-secondary: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'custom_background_color',
			array(
				'label'   => esc_html__( 'Background Color', 'ensemble' ),
				'type'    => \Elementor\Controls_Manager::COLOR,
				'default' => '',
				'selectors' => array(
					'{{WRAPPER}} .ensemble-elementor-widget' => '--ensemble-bg: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'custom_link_color',
			array(
				'label'   => esc_html__( 'Link Color', 'ensemble' ),
				'type'    => \Elementor\Controls_Manager::COLOR,
				'default' => '',
				'selectors' => array(
					'{{WRAPPER}} .ensemble-elementor-widget' => '--ensemble-link: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'custom_hover_color',
			array(
				'label'   => esc_html__( 'Hover Color', 'ensemble' ),
				'type'    => \Elementor\Controls_Manager::COLOR,
				'default' => '',
				'selectors' => array(
					'{{WRAPPER}} .ensemble-elementor-widget' => '--ensemble-hover: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();

		// ===========================================
		// STYLE TAB: Cards
		// ===========================================
		$this->start_controls_section(
			'style_cards_section',
			array(
				'label'     => esc_html__( 'Cards', 'ensemble' ),
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => array(
					'style_mode' => 'custom',
				),
			)
		);

		$this->add_control(
			'custom_card_background',
			array(
				'label'   => esc_html__( 'Card Background', 'ensemble' ),
				'type'    => \Elementor\Controls_Manager::COLOR,
				'default' => '',
				'selectors' => array(
					'{{WRAPPER}} .ensemble-elementor-widget' => '--ensemble-card-bg: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'custom_card_border_color',
			array(
				'label'   => esc_html__( 'Card Border Color', 'ensemble' ),
				'type'    => \Elementor\Controls_Manager::COLOR,
				'default' => '',
				'selectors' => array(
					'{{WRAPPER}} .ensemble-elementor-widget' => '--ensemble-card-border: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'custom_card_radius',
			array(
				'label'      => esc_html__( 'Card Border Radius', 'ensemble' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 50,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .ensemble-elementor-widget' => '--ensemble-card-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'custom_card_padding',
			array(
				'label'      => esc_html__( 'Card Padding', 'ensemble' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 60,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .ensemble-elementor-widget' => '--ensemble-card-padding: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'custom_card_shadow',
			array(
				'label'   => esc_html__( 'Card Shadow', 'ensemble' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => '',
				'options' => array(
					''       => esc_html__( 'Default', 'ensemble' ),
					'none'   => esc_html__( 'None', 'ensemble' ),
					'light'  => esc_html__( 'Light', 'ensemble' ),
					'medium' => esc_html__( 'Medium', 'ensemble' ),
					'heavy'  => esc_html__( 'Heavy', 'ensemble' ),
				),
			)
		);

		$this->add_control(
			'custom_card_hover',
			array(
				'label'   => esc_html__( 'Card Hover Effect', 'ensemble' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => '',
				'options' => array(
					''       => esc_html__( 'Default', 'ensemble' ),
					'none'   => esc_html__( 'None', 'ensemble' ),
					'lift'   => esc_html__( 'Lift', 'ensemble' ),
					'glow'   => esc_html__( 'Glow', 'ensemble' ),
					'border' => esc_html__( 'Border', 'ensemble' ),
				),
			)
		);

		$this->end_controls_section();

		// ===========================================
		// STYLE TAB: Typography
		// ===========================================
		$this->start_controls_section(
			'style_typography_section',
			array(
				'label'     => esc_html__( 'Typography', 'ensemble' ),
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => array(
					'style_mode' => 'custom',
				),
			)
		);

		$this->add_control(
			'custom_heading_size',
			array(
				'label'      => esc_html__( 'Heading Size', 'ensemble' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 12,
						'max' => 48,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .ensemble-elementor-widget' => '--ensemble-heading-size: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'custom_body_size',
			array(
				'label'      => esc_html__( 'Body Text Size', 'ensemble' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 12,
						'max' => 24,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .ensemble-elementor-widget' => '--ensemble-body-size: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'custom_small_size',
			array(
				'label'      => esc_html__( 'Small Text Size', 'ensemble' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 10,
						'max' => 18,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .ensemble-elementor-widget' => '--ensemble-small-size: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'custom_heading_weight',
			array(
				'label'   => esc_html__( 'Heading Weight', 'ensemble' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => '',
				'options' => array(
					''    => esc_html__( 'Default', 'ensemble' ),
					'400' => '400 (Normal)',
					'500' => '500 (Medium)',
					'600' => '600 (Semi-Bold)',
					'700' => '700 (Bold)',
					'800' => '800 (Extra Bold)',
				),
				'selectors' => array(
					'{{WRAPPER}} .ensemble-elementor-widget' => '--ensemble-heading-weight: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();

		// ===========================================
		// STYLE TAB: Buttons
		// ===========================================
		$this->start_controls_section(
			'style_buttons_section',
			array(
				'label'     => esc_html__( 'Buttons', 'ensemble' ),
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => array(
					'style_mode' => 'custom',
				),
			)
		);

		$this->add_control(
			'custom_button_bg',
			array(
				'label'   => esc_html__( 'Button Background', 'ensemble' ),
				'type'    => \Elementor\Controls_Manager::COLOR,
				'default' => '',
				'selectors' => array(
					'{{WRAPPER}} .ensemble-elementor-widget' => '--ensemble-button-bg: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'custom_button_text',
			array(
				'label'   => esc_html__( 'Button Text Color', 'ensemble' ),
				'type'    => \Elementor\Controls_Manager::COLOR,
				'default' => '',
				'selectors' => array(
					'{{WRAPPER}} .ensemble-elementor-widget' => '--ensemble-button-text: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'custom_button_hover_bg',
			array(
				'label'   => esc_html__( 'Button Hover Background', 'ensemble' ),
				'type'    => \Elementor\Controls_Manager::COLOR,
				'default' => '',
				'selectors' => array(
					'{{WRAPPER}} .ensemble-elementor-widget' => '--ensemble-button-hover-bg: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'custom_button_radius',
			array(
				'label'      => esc_html__( 'Button Border Radius', 'ensemble' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 50,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .ensemble-elementor-widget' => '--ensemble-button-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'custom_button_padding_h',
			array(
				'label'      => esc_html__( 'Button Horizontal Padding', 'ensemble' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 8,
						'max' => 60,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .ensemble-elementor-widget' => '--ensemble-button-padding-h: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'custom_button_padding_v',
			array(
				'label'      => esc_html__( 'Button Vertical Padding', 'ensemble' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 4,
						'max' => 30,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .ensemble-elementor-widget' => '--ensemble-button-padding-v: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		// ===========================================
		// STYLE TAB: Spacing
		// ===========================================
		$this->start_controls_section(
			'style_spacing_section',
			array(
				'label'     => esc_html__( 'Spacing', 'ensemble' ),
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => array(
					'style_mode' => 'custom',
				),
			)
		);

		$this->add_control(
			'custom_grid_gap',
			array(
				'label'      => esc_html__( 'Grid Gap', 'ensemble' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 60,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .ensemble-elementor-widget' => '--ensemble-grid-gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'custom_section_spacing',
			array(
				'label'      => esc_html__( 'Section Spacing', 'ensemble' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 100,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .ensemble-elementor-widget' => '--ensemble-section-spacing: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		// ===========================================
		// STYLE TAB: Image
		// ===========================================
		$this->start_controls_section(
			'style_image_section',
			array(
				'label'     => esc_html__( 'Images', 'ensemble' ),
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => array(
					'style_mode' => 'custom',
				),
			)
		);

		$this->add_control(
			'custom_image_height',
			array(
				'label'      => esc_html__( 'Image Height', 'ensemble' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 100,
						'max' => 500,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .ensemble-elementor-widget' => '--ensemble-image-height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'custom_image_radius',
			array(
				'label'      => esc_html__( 'Image Border Radius', 'ensemble' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 30,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .ensemble-elementor-widget' => '--ensemble-image-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'custom_image_fit',
			array(
				'label'   => esc_html__( 'Image Fit', 'ensemble' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => '',
				'options' => array(
					''        => esc_html__( 'Default', 'ensemble' ),
					'cover'   => esc_html__( 'Cover', 'ensemble' ),
					'contain' => esc_html__( 'Contain', 'ensemble' ),
					'fill'    => esc_html__( 'Fill', 'ensemble' ),
				),
				'selectors' => array(
					'{{WRAPPER}} .ensemble-elementor-widget img' => 'object-fit: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Add layout control.
	 *
	 * @param array $options Layout options.
	 * @param string $default Default value.
	 */
	protected function add_layout_control( $options, $default = 'grid' ) {
		$this->add_control(
			'layout',
			array(
				'label'   => esc_html__( 'Layout', 'ensemble' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => $default,
				'options' => $options,
			)
		);
	}

	/**
	 * Add columns control.
	 *
	 * @param int $default Default columns.
	 * @param int $min Minimum columns.
	 * @param int $max Maximum columns.
	 * @param array $condition Condition for showing this control.
	 */
	protected function add_columns_control( $default = 3, $min = 1, $max = 4, $condition = array() ) {
		$args = array(
			'label'   => esc_html__( 'Columns', 'ensemble' ),
			'type'    => \Elementor\Controls_Manager::NUMBER,
			'default' => $default,
			'min'     => $min,
			'max'     => $max,
		);

		if ( ! empty( $condition ) ) {
			$args['condition'] = $condition;
		}

		$this->add_control( 'columns', $args );
	}

	/**
	 * Add limit control.
	 *
	 * @param int $default Default limit.
	 * @param int $max Maximum limit.
	 */
	protected function add_limit_control( $default = 12, $max = 50 ) {
		$this->add_control(
			'limit',
			array(
				'label'   => esc_html__( 'Number of Items', 'ensemble' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'default' => $default,
				'min'     => 1,
				'max'     => $max,
			)
		);
	}

	/**
	 * Add order controls.
	 *
	 * @param array $orderby_options Order by options.
	 * @param string $default_orderby Default order by value.
	 * @param string $default_order Default order (ASC/DESC).
	 */
	protected function add_order_controls( $orderby_options, $default_orderby = 'title', $default_order = 'ASC' ) {
		$this->add_control(
			'orderby',
			array(
				'label'   => esc_html__( 'Order By', 'ensemble' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => $default_orderby,
				'options' => $orderby_options,
			)
		);

		$this->add_control(
			'order',
			array(
				'label'   => esc_html__( 'Order', 'ensemble' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => $default_order,
				'options' => array(
					'ASC'  => esc_html__( 'Ascending', 'ensemble' ),
					'DESC' => esc_html__( 'Descending', 'ensemble' ),
				),
			)
		);
	}

	/**
	 * Add toggle control.
	 *
	 * @param string $key Control key.
	 * @param string $label Control label.
	 * @param bool $default Default value.
	 * @param string $description Optional description.
	 * @param array $condition Optional condition.
	 */
	protected function add_toggle_control( $key, $label, $default = true, $description = '', $condition = array() ) {
		$args = array(
			'label'        => $label,
			'type'         => \Elementor\Controls_Manager::SWITCHER,
			'label_on'     => esc_html__( 'Yes', 'ensemble' ),
			'label_off'    => esc_html__( 'No', 'ensemble' ),
			'return_value' => 'yes',
			'default'      => $default ? 'yes' : '',
		);

		if ( ! empty( $description ) ) {
			$args['description'] = $description;
		}

		if ( ! empty( $condition ) ) {
			$args['condition'] = $condition;
		}

		$this->add_control( $key, $args );
	}

	/**
	 * Add slider controls section.
	 */
	protected function add_slider_controls_section() {
		$this->start_controls_section(
			'slider_section',
			array(
				'label'     => esc_html__( 'Slider Settings', 'ensemble' ),
				'condition' => array(
					'layout' => array( 'slider', 'hero', 'carousel' ),
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

		$this->add_toggle_control(
			'fullscreen',
			esc_html__( 'Fullscreen', 'ensemble' ),
			false,
			'',
			array( 'layout' => 'hero' )
		);

		$this->end_controls_section();
	}

	/**
	 * Build shortcode string from settings.
	 *
	 * @param string $shortcode_name Shortcode name.
	 * @param array $settings Widget settings.
	 * @param array $attribute_map Map of setting keys to shortcode attributes.
	 * @return string Shortcode string.
	 */
	protected function build_shortcode( $shortcode_name, $settings, $attribute_map ) {
		$shortcode_atts = array();

		foreach ( $attribute_map as $setting_key => $shortcode_key ) {
			if ( ! isset( $settings[ $setting_key ] ) ) {
				continue;
			}

			$value = $settings[ $setting_key ];

			// Convert Elementor switcher values to shortcode format
			if ( $value === 'yes' ) {
				$value = 'true';
			} elseif ( $value === '' && strpos( $setting_key, 'show' ) === 0 ) {
				$value = 'false';
			}

			// Skip empty values (except explicit false for show_ attributes)
			if ( $value === '' && strpos( $shortcode_key, 'show_' ) !== 0 ) {
				continue;
			}

			$shortcode_atts[ $shortcode_key ] = $value;
		}

		// Build shortcode string
		$shortcode_parts = array();
		foreach ( $shortcode_atts as $key => $value ) {
			if ( $value !== '' ) {
				$shortcode_parts[] = $key . '="' . esc_attr( $value ) . '"';
			}
		}

		return '[' . $shortcode_name . ' ' . implode( ' ', $shortcode_parts ) . ']';
	}

	/**
	 * Get taxonomy terms as options.
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @param string $all_label Label for "All" option.
	 * @return array Options array.
	 */
	protected function get_taxonomy_options( $taxonomy, $all_label = '' ) {
		$options = array();

		if ( ! empty( $all_label ) ) {
			$options[''] = $all_label;
		}

		$terms = get_terms( array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
		) );

		if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
			foreach ( $terms as $term ) {
				$options[ $term->slug ] = $term->name;
			}
		}

		return $options;
	}

	/**
	 * Generate custom CSS classes based on style settings.
	 *
	 * @param array $settings Widget settings.
	 * @return string Additional CSS classes.
	 */
	protected function get_style_classes( $settings ) {
		$classes = array();

		// Card shadow class
		if ( ! empty( $settings['custom_card_shadow'] ) ) {
			$classes[] = 'es-shadow-' . $settings['custom_card_shadow'];
		}

		// Card hover class
		if ( ! empty( $settings['custom_card_hover'] ) ) {
			$classes[] = 'es-hover-' . $settings['custom_card_hover'];
		}

		return implode( ' ', $classes );
	}

	/**
	 * Render the widget output.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		$shortcode = $this->get_shortcode( $settings );

		// Get additional classes
		$style_classes = $this->get_style_classes( $settings );
		$wrapper_class = 'ensemble-elementor-widget ensemble-elementor-' . esc_attr( $this->get_name() );
		
		if ( ! empty( $style_classes ) ) {
			$wrapper_class .= ' ' . $style_classes;
		}

		// Check style mode
		$style_mode = $settings['style_mode'] ?? 'global';
		if ( $style_mode === 'custom' ) {
			$wrapper_class .= ' es-custom-styles';
		}

		echo '<div class="' . esc_attr( $wrapper_class ) . '">';
		echo do_shortcode( $shortcode );
		echo '</div>';
	}

	/**
	 * Get the shortcode to render.
	 * Must be implemented by child classes.
	 *
	 * @param array $settings Widget settings.
	 * @return string Shortcode string.
	 */
	abstract protected function get_shortcode( $settings );
}
