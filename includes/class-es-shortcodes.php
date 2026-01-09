<?php
/**
 * Ensemble Shortcodes Loader
 *
 * Main shortcodes class that loads all shortcode sub-classes.
 * This is the refactored version of the original monolithic ES_Shortcodes class.
 *
 * The class is split into:
 * - ES_Shortcode_Base: Abstract base class with shared methods
 * - ES_Event_Shortcodes: Event-related shortcodes
 * - ES_Artist_Shortcodes: Artist-related shortcodes
 * - ES_Location_Shortcodes: Location-related shortcodes
 * - ES_Calendar_Shortcode: Calendar shortcode
 * - ES_Utility_Shortcodes: Gallery, demo, and layout switcher
 * - ES_Shortcodes_Assets: Style and script enqueuing
 *
 * @package Ensemble
 * @subpackage Shortcodes
 * @since 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main Shortcodes class.
 *
 * This class serves as a facade/loader for all shortcode functionality.
 * It maintains backward compatibility with code that references ES_Shortcodes.
 *
 * @since 3.0.0
 */
class ES_Shortcodes {

	/**
	 * Singleton instance.
	 *
	 * @var ES_Shortcodes|null
	 */
	private static $instance = null;

	/**
	 * Event shortcodes instance.
	 *
	 * @var ES_Event_Shortcodes|null
	 */
	private $events = null;

	/**
	 * Artist shortcodes instance.
	 *
	 * @var ES_Artist_Shortcodes|null
	 */
	private $artists = null;

	/**
	 * Location shortcodes instance.
	 *
	 * @var ES_Location_Shortcodes|null
	 */
	private $locations = null;

	/**
	 * Calendar shortcode instance.
	 *
	 * @var ES_Calendar_Shortcode|null
	 */
	private $calendar = null;

	/**
	 * Utility shortcodes instance.
	 *
	 * @var ES_Utility_Shortcodes|null
	 */
	private $utilities = null;

	/**
	 * Assets handler instance.
	 *
	 * @var ES_Shortcodes_Assets|null
	 */
	private $assets = null;

	/**
	 * Get singleton instance.
	 *
	 * @return ES_Shortcodes
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * Loads all shortcode classes and initializes them.
	 */
	public function __construct() {
		$this->load_dependencies();
		$this->init_shortcodes();
	}

	/**
	 * Load required files.
	 *
	 * @return void
	 */
	private function load_dependencies() {
		$shortcodes_dir = ENSEMBLE_PLUGIN_DIR . 'includes/shortcodes/';

		require_once $shortcodes_dir . 'class-es-shortcode-base.php';
		require_once $shortcodes_dir . 'class-es-utility-shortcodes.php';  // Before others (provides get_available_layouts)
		require_once $shortcodes_dir . 'class-es-event-shortcodes.php';
		require_once $shortcodes_dir . 'class-es-artist-shortcodes.php';
		require_once $shortcodes_dir . 'class-es-location-shortcodes.php';
		require_once $shortcodes_dir . 'class-es-calendar-shortcode.php';
		require_once $shortcodes_dir . 'class-es-shortcodes-assets.php';
	}

	/**
	 * Initialize all shortcode handlers.
	 *
	 * @return void
	 */
	private function init_shortcodes() {
		// Create instances.
		$this->events    = new ES_Event_Shortcodes();
		$this->artists   = new ES_Artist_Shortcodes();
		$this->locations = new ES_Location_Shortcodes();
		$this->calendar  = new ES_Calendar_Shortcode();
		$this->utilities = new ES_Utility_Shortcodes();
		$this->assets    = new ES_Shortcodes_Assets();

		// Register all shortcodes on init.
		add_action( 'init', array( $this, 'register_shortcodes' ) );

		// Initialize assets.
		$this->assets->init();
	}

	/**
	 * Register all shortcodes.
	 *
	 * @return void
	 */
	public function register_shortcodes() {
		$this->events->register_shortcodes();
		$this->artists->register_shortcodes();
		$this->locations->register_shortcodes();
		$this->calendar->register_shortcodes();
		$this->utilities->register_shortcodes();
	}

	/**
	 * Get event shortcodes handler.
	 *
	 * @return ES_Event_Shortcodes
	 */
	public function events() {
		return $this->events;
	}

	/**
	 * Get artist shortcodes handler.
	 *
	 * @return ES_Artist_Shortcodes
	 */
	public function artists() {
		return $this->artists;
	}

	/**
	 * Get location shortcodes handler.
	 *
	 * @return ES_Location_Shortcodes
	 */
	public function locations() {
		return $this->locations;
	}

	/**
	 * Get calendar shortcode handler.
	 *
	 * @return ES_Calendar_Shortcode
	 */
	public function calendar() {
		return $this->calendar;
	}

	/**
	 * Get utility shortcodes handler.
	 *
	 * @return ES_Utility_Shortcodes
	 */
	public function utilities() {
		return $this->utilities;
	}

	/**
	 * Enqueue styles - for backward compatibility.
	 *
	 * @deprecated Use ES_Shortcodes_Assets::enqueue_styles() directly.
	 * @return void
	 */
	public function enqueue_styles() {
		$this->assets->enqueue_styles();
	}

	/**
	 * Get available layouts - static method for backward compatibility.
	 *
	 * @return array Available layouts.
	 */
	public static function get_available_layouts() {
		return ES_Utility_Shortcodes::get_available_layouts();
	}

	/**
	 * Get current demo layout - static method for backward compatibility.
	 *
	 * @param string $default Default layout.
	 * @return string Current layout slug.
	 */
	public static function get_current_demo_layout( $default = 'lovepop' ) {
		return ES_Utility_Shortcodes::get_current_demo_layout( $default );
	}

	/**
	 * AJAX handler for calendar events - static method for backward compatibility.
	 *
	 * @return void
	 */
	public static function ajax_get_calendar_events() {
		ES_Calendar_Shortcode::ajax_get_calendar_events();
	}

	// =========================================================================
	// Backward Compatibility Methods
	// The following methods are delegated to the appropriate sub-class
	// to maintain backward compatibility with existing code.
	// =========================================================================

	/**
	 * Single event shortcode - backward compatibility.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function single_event_shortcode( $atts ) {
		return $this->events->single_event_shortcode( $atts );
	}

	/**
	 * Events grid shortcode - backward compatibility.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function events_grid_shortcode( $atts ) {
		return $this->events->events_grid_shortcode( $atts );
	}

	/**
	 * Artists list shortcode - backward compatibility.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function artists_list_shortcode( $atts ) {
		return $this->artists->artists_list_shortcode( $atts );
	}

	/**
	 * Locations list shortcode - backward compatibility.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function locations_list_shortcode( $atts ) {
		return $this->locations->locations_list_shortcode( $atts );
	}

	/**
	 * Calendar shortcode - backward compatibility.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function calendar_shortcode( $atts ) {
		return $this->calendar->calendar_shortcode( $atts );
	}

	/**
	 * Gallery shortcode - backward compatibility.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function gallery_shortcode( $atts ) {
		return $this->utilities->gallery_shortcode( $atts );
	}
}
