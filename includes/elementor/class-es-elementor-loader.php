<?php
/**
 * Ensemble Elementor Widgets Loader
 *
 * Registers Elementor widgets that wrap existing shortcodes.
 * Mirrors the Gutenberg blocks for feature parity.
 *
 * @package Ensemble
 * @since 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ES_Elementor_Loader class.
 */
class ES_Elementor_Loader {

	/**
	 * Singleton instance.
	 *
	 * @var ES_Elementor_Loader|null
	 */
	private static $instance = null;

	/**
	 * Minimum Elementor Version.
	 *
	 * @var string
	 */
	const MINIMUM_ELEMENTOR_VERSION = '3.0.0';

	/**
	 * Get singleton instance.
	 *
	 * @return ES_Elementor_Loader
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	/**
	 * Initialize the integration.
	 */
	public function init() {
		// Check if Elementor is installed and activated
		if ( ! did_action( 'elementor/loaded' ) ) {
			return;
		}

		// Check for required Elementor version
		if ( ! version_compare( ELEMENTOR_VERSION, self::MINIMUM_ELEMENTOR_VERSION, '>=' ) ) {
			add_action( 'admin_notices', array( $this, 'admin_notice_minimum_elementor_version' ) );
			return;
		}

		// Register widgets
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );

		// Register category
		add_action( 'elementor/elements/categories_registered', array( $this, 'register_category' ) );

		// Enqueue editor styles
		add_action( 'elementor/editor/after_enqueue_styles', array( $this, 'editor_styles' ) );

		// Enqueue preview styles
		add_action( 'elementor/preview/enqueue_styles', array( $this, 'preview_styles' ) );

		// Enqueue frontend styles
		add_action( 'elementor/frontend/after_enqueue_styles', array( $this, 'frontend_styles' ) );
	}

	/**
	 * Admin notice for minimum Elementor version.
	 */
	public function admin_notice_minimum_elementor_version() {
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}

		$message = sprintf(
			/* translators: 1: Plugin name 2: Elementor 3: Required Elementor version */
			esc_html__( '"%1$s" requires "%2$s" version %3$s or greater.', 'ensemble' ),
			'<strong>Ensemble Elementor Widgets</strong>',
			'<strong>Elementor</strong>',
			self::MINIMUM_ELEMENTOR_VERSION
		);

		printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message );
	}

	/**
	 * Register Ensemble widget category.
	 *
	 * @param \Elementor\Elements_Manager $elements_manager Elements manager instance.
	 */
	public function register_category( $elements_manager ) {
		$elements_manager->add_category(
			'ensemble',
			array(
				'title' => esc_html__( 'Ensemble', 'ensemble' ),
				'icon'  => 'eicon-calendar',
			)
		);
	}

	/**
	 * Register widgets.
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Widgets manager instance.
	 */
	public function register_widgets( $widgets_manager ) {
		// Include base widget
		require_once ENSEMBLE_PLUGIN_DIR . 'includes/elementor/class-es-elementor-base-widget.php';

		// Include and register all widgets
		$widgets = array(
			'event-grid'       => 'ES_Elementor_Event_Grid',
			'artist-grid'      => 'ES_Elementor_Artist_Grid',
			'location-grid'    => 'ES_Elementor_Location_Grid',
			'calendar'         => 'ES_Elementor_Calendar',
			'countdown'        => 'ES_Elementor_Countdown',
			'upcoming-events'  => 'ES_Elementor_Upcoming_Events',
			'single-event'     => 'ES_Elementor_Single_Event',
		);

		foreach ( $widgets as $file => $class ) {
			$file_path = ENSEMBLE_PLUGIN_DIR . 'includes/elementor/widgets/class-es-elementor-' . $file . '.php';
			if ( file_exists( $file_path ) ) {
				require_once $file_path;
				if ( class_exists( $class ) ) {
					$widgets_manager->register( new $class() );
				}
			}
		}
	}

	/**
	 * Enqueue editor styles.
	 */
	public function editor_styles() {
		// Load Elementor widget styles
		wp_enqueue_style(
			'ensemble-elementor-widgets',
			ENSEMBLE_PLUGIN_URL . 'includes/elementor/assets/css/elementor-widgets.css',
			array(),
			ENSEMBLE_VERSION
		);

		// Add custom icon for category
		wp_add_inline_style( 'elementor-editor', '
			.elementor-element .icon .eicon-ensemble {
				font-family: eicons;
			}
			.elementor-element .icon .eicon-ensemble:before {
				content: "\e90c";
			}
			/* Style mode info box */
			.elementor-control-style_mode_info .elementor-control-content {
				margin-top: 10px;
			}
		' );
	}

	/**
	 * Enqueue preview styles.
	 */
	public function preview_styles() {
		// Load frontend CSS for accurate preview
		if ( file_exists( ENSEMBLE_PLUGIN_DIR . 'assets/css/ensemble-frontend.css' ) ) {
			wp_enqueue_style(
				'ensemble-frontend',
				ENSEMBLE_PLUGIN_URL . 'assets/css/ensemble-frontend.css',
				array(),
				ENSEMBLE_VERSION
			);
		}

		// Load Elementor widget styles
		wp_enqueue_style(
			'ensemble-elementor-widgets',
			ENSEMBLE_PLUGIN_URL . 'includes/elementor/assets/css/elementor-widgets.css',
			array( 'ensemble-frontend' ),
			ENSEMBLE_VERSION
		);

		// Load active Layout Set CSS
		if ( class_exists( 'ES_Layout_Sets' ) ) {
			$active_set = ES_Layout_Sets::get_active_set();
			$layout_css = ENSEMBLE_PLUGIN_DIR . 'layouts/' . $active_set . '/style.css';
			if ( file_exists( $layout_css ) ) {
				wp_enqueue_style(
					'ensemble-layout-' . $active_set,
					ENSEMBLE_PLUGIN_URL . 'layouts/' . $active_set . '/style.css',
					array(),
					ENSEMBLE_VERSION
				);
			}
		}

		// Load Designer CSS variables
		if ( class_exists( 'ES_Design_Settings' ) ) {
			$this->enqueue_designer_variables();
		}
	}

	/**
	 * Enqueue frontend styles.
	 */
	public function frontend_styles() {
		// Only load if we have Elementor content
		if ( ! \Elementor\Plugin::$instance->preview->is_preview_mode() ) {
			// Load Elementor widget styles
			wp_enqueue_style(
				'ensemble-elementor-widgets',
				ENSEMBLE_PLUGIN_URL . 'includes/elementor/assets/css/elementor-widgets.css',
				array( 'ensemble-frontend' ),
				ENSEMBLE_VERSION
			);
		}
	}

	/**
	 * Enqueue Designer CSS variables.
	 */
	private function enqueue_designer_variables() {
		if ( ! class_exists( 'ES_Design_Settings' ) ) {
			return;
		}

		$settings = ES_Design_Settings::get_effective_settings();
		
		$css = ':root {';
		
		// Map settings to CSS variables
		$variable_map = array(
			'primary_color'     => '--ensemble-primary',
			'secondary_color'   => '--ensemble-secondary',
			'text_color'        => '--ensemble-text',
			'text_secondary'    => '--ensemble-text-secondary',
			'background_color'  => '--ensemble-bg',
			'card_background'   => '--ensemble-card-bg',
			'card_border'       => '--ensemble-card-border',
			'card_radius'       => '--ensemble-card-radius',
			'card_padding'      => '--ensemble-card-padding',
			'button_bg'         => '--ensemble-button-bg',
			'button_text'       => '--ensemble-button-text',
			'button_hover_bg'   => '--ensemble-button-hover-bg',
			'button_radius'     => '--ensemble-button-radius',
			'grid_gap'          => '--ensemble-grid-gap',
			'card_image_height' => '--ensemble-image-height',
			'h3_size'           => '--ensemble-heading-size',
			'body_size'         => '--ensemble-body-size',
			'small_size'        => '--ensemble-small-size',
			'heading_weight'    => '--ensemble-heading-weight',
		);

		foreach ( $variable_map as $setting => $variable ) {
			if ( isset( $settings[ $setting ] ) && $settings[ $setting ] !== '' ) {
				$value = $settings[ $setting ];
				
				// Add unit for numeric values
				if ( is_numeric( $value ) ) {
					if ( strpos( $setting, 'size' ) !== false || strpos( $setting, 'height' ) !== false ) {
						$value .= 'px';
					} elseif ( strpos( $setting, 'radius' ) !== false || strpos( $setting, 'padding' ) !== false || strpos( $setting, 'gap' ) !== false ) {
						$value .= 'px';
					}
				}
				
				$css .= $variable . ': ' . $value . ';';
			}
		}

		// Add RGB version of primary color for glow effects
		if ( ! empty( $settings['primary_color'] ) ) {
			$hex = ltrim( $settings['primary_color'], '#' );
			if ( strlen( $hex ) === 6 ) {
				$r = hexdec( substr( $hex, 0, 2 ) );
				$g = hexdec( substr( $hex, 2, 2 ) );
				$b = hexdec( substr( $hex, 4, 2 ) );
				$css .= '--ensemble-primary-rgb: ' . $r . ', ' . $g . ', ' . $b . ';';
			}
		}

		$css .= '}';

		wp_add_inline_style( 'ensemble-elementor-widgets', $css );
	}
}

// Initialize
ES_Elementor_Loader::instance();
