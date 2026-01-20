<?php
/**
 * Ensemble Gutenberg Blocks
 *
 * Registers Gutenberg blocks that wrap existing shortcodes.
 * Uses server-side rendering for live preview in the editor.
 *
 * @package Ensemble
 * @since 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ES_Blocks class.
 */
class ES_Blocks {

	/**
	 * Singleton instance.
	 *
	 * @var ES_Blocks|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return ES_Blocks
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
		add_action( 'init', array( $this, 'register_blocks' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
	}

	/**
	 * Register all blocks.
	 */
	public function register_blocks() {
		// Register block category
		add_filter( 'block_categories_all', array( $this, 'register_block_category' ), 10, 2 );

		$this->register_event_grid_block();
		$this->register_artist_grid_block();
		$this->register_location_grid_block();
		$this->register_countdown_block();
		$this->register_single_event_block();
		$this->register_calendar_block();
		$this->register_locations_map_block();
		$this->register_upcoming_events_block();
		$this->register_staff_grid_block();
		$this->register_reservation_blocks();
	}

	/**
	 * Check if Staff addon is active.
	 * 
	 * @return bool
	 */
	private function is_staff_addon_active() {
		// Method 1: Check via Addon Manager
		if ( class_exists( 'ES_Addon_Manager' ) && method_exists( 'ES_Addon_Manager', 'is_addon_active' ) ) {
			if ( ES_Addon_Manager::is_addon_active( 'staff' ) ) {
				return true;
			}
		}
		
		// Method 2: Check if Staff addon class exists
		if ( class_exists( 'ES_Staff_Addon' ) ) {
			return true;
		}
		
		// Method 3: Check if shortcode exists
		if ( shortcode_exists( 'ensemble_staff' ) ) {
			return true;
		}
		
		// Method 4: Check if post type exists
		if ( post_type_exists( 'ensemble_staff' ) ) {
			return true;
		}
		
		return false;
	}

	/**
	 * Register Ensemble block category.
	 */
	public function register_block_category( $categories, $post ) {
		return array_merge(
			array(
				array(
					'slug'  => 'ensemble',
					'title' => __( 'Ensemble', 'ensemble' ),
					'icon'  => 'calendar-alt',
				),
			),
			$categories
		);
	}

	/**
	 * Enqueue editor assets.
	 */
	public function enqueue_editor_assets() {
		// Load frontend CSS in editor for accurate preview
		if ( file_exists( ENSEMBLE_PLUGIN_DIR . 'assets/css/ensemble-frontend.css' ) ) {
			wp_enqueue_style(
				'ensemble-frontend-editor',
				ENSEMBLE_PLUGIN_URL . 'assets/css/ensemble-frontend.css',
				array(),
				ENSEMBLE_VERSION
			);
		}

		// Load Countdown CSS in editor
		if ( file_exists( ENSEMBLE_PLUGIN_DIR . 'assets/css/ensemble-countdown.css' ) ) {
			wp_enqueue_style(
				'ensemble-countdown-editor',
				ENSEMBLE_PLUGIN_URL . 'assets/css/ensemble-countdown.css',
				array(),
				ENSEMBLE_VERSION
			);
		}

		// Load active Layout Set CSS
		if ( class_exists( 'ES_Layout_Sets' ) ) {
			$active_set = ES_Layout_Sets::get_active_set();
			$layout_css = ENSEMBLE_PLUGIN_DIR . 'layouts/' . $active_set . '/style.css';
			if ( file_exists( $layout_css ) ) {
				wp_enqueue_style(
					'ensemble-layout-' . $active_set . '-editor',
					ENSEMBLE_PLUGIN_URL . 'layouts/' . $active_set . '/style.css',
					array(),
					ENSEMBLE_VERSION
				);
			}
		}

		// Disable links in editor preview - CRITICAL!
		wp_add_inline_style( 'ensemble-frontend-editor', '
			/* Disable ALL clicks inside blocks */
			.block-editor-block-list__layout [data-type="ensemble/event-grid"],
			.block-editor-block-list__layout [data-type="ensemble/artist-grid"],
			.block-editor-block-list__layout [data-type="ensemble/location-grid"],
			.block-editor-block-list__layout [data-type="ensemble/countdown"],
			.block-editor-block-list__layout [data-type="ensemble/single-event"],
			.block-editor-block-list__layout [data-type="ensemble/calendar"],
			.block-editor-block-list__layout [data-type="ensemble/locations-map"],
			.block-editor-block-list__layout [data-type="ensemble/upcoming-events"],
			.block-editor-block-list__layout [data-type="ensemble/staff-grid"],
			.block-editor-block-list__layout [data-type="ensemble/reservation-form"],
			.block-editor-block-list__layout [data-type="ensemble/guestlist"],
			.block-editor-block-list__layout [data-type="ensemble/availability"] {
				position: relative;
			}
			.block-editor-block-list__layout [data-type="ensemble/event-grid"] .components-server-side-render,
			.block-editor-block-list__layout [data-type="ensemble/artist-grid"] .components-server-side-render,
			.block-editor-block-list__layout [data-type="ensemble/location-grid"] .components-server-side-render,
			.block-editor-block-list__layout [data-type="ensemble/countdown"] .components-server-side-render,
			.block-editor-block-list__layout [data-type="ensemble/single-event"] .components-server-side-render,
			.block-editor-block-list__layout [data-type="ensemble/calendar"] .components-server-side-render,
			.block-editor-block-list__layout [data-type="ensemble/locations-map"] .components-server-side-render,
			.block-editor-block-list__layout [data-type="ensemble/upcoming-events"] .components-server-side-render,
			.block-editor-block-list__layout [data-type="ensemble/staff-grid"] .components-server-side-render,
			.block-editor-block-list__layout [data-type="ensemble/reservation-form"] .components-server-side-render,
			.block-editor-block-list__layout [data-type="ensemble/guestlist"] .components-server-side-render,
			.block-editor-block-list__layout [data-type="ensemble/availability"] .components-server-side-render {
				pointer-events: none !important;
			}
			/* Allow clicking the block wrapper itself */
			.block-editor-block-list__layout [data-type^="ensemble/"] > .block-editor-block-list__block-edit {
				pointer-events: auto !important;
			}
			/* Fallback for all links inside ensemble blocks */
			.editor-styles-wrapper .es-kongress-speaker-card,
			.editor-styles-wrapper .es-kongress-session-card,
			.editor-styles-wrapper .es-kongress-location-card,
			.editor-styles-wrapper .ensemble-artist-card,
			.editor-styles-wrapper .ensemble-event-card,
			.editor-styles-wrapper .ensemble-location-card,
			.editor-styles-wrapper .es-countdown,
			.editor-styles-wrapper .ensemble-single-event,
			.editor-styles-wrapper .es-staff-grid,
			.editor-styles-wrapper .es-staff-list,
			.editor-styles-wrapper .es-staff-cards,
			.editor-styles-wrapper .ensemble-calendar,
			.editor-styles-wrapper .ensemble-map,
			.editor-styles-wrapper .ensemble-upcoming-events,
			.editor-styles-wrapper .es-reservation-form,
			.editor-styles-wrapper .es-guestlist,
			.editor-styles-wrapper .es-availability-block,
			.editor-styles-wrapper [class*="es-kongress-"] a,
			.editor-styles-wrapper [class*="ensemble-"] a,
			.editor-styles-wrapper [class*="es-staff-"] a,
			.editor-styles-wrapper [class*="es-reservation-"] a,
			.editor-styles-wrapper .es-countdown a {
				pointer-events: none !important;
				cursor: default !important;
			}
		' );
	}

	/**
	 * Register Event Grid Block.
	 */
	private function register_event_grid_block() {
		// Register script
		wp_register_script(
			'ensemble-event-grid-editor',
			ENSEMBLE_PLUGIN_URL . 'includes/blocks/event-grid/index.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-data', 'wp-server-side-render' ),
			ENSEMBLE_VERSION,
			true
		);

			register_block_type( 'ensemble/event-grid', array(
			'editor_script'   => 'ensemble-event-grid-editor',
			'render_callback' => array( $this, 'render_event_grid_block' ),
			'attributes'      => array(
				'layout'        => array( 'type' => 'string', 'default' => 'grid' ),
				'columns'       => array( 'type' => 'number', 'default' => 3 ),
				'style'         => array( 'type' => 'string', 'default' => 'default' ),
				'limit'         => array( 'type' => 'number', 'default' => 12 ),
				'offset'        => array( 'type' => 'number', 'default' => 0 ),
				'orderby'       => array( 'type' => 'string', 'default' => 'event_date' ),
				'order'         => array( 'type' => 'string', 'default' => 'ASC' ),
				'show'          => array( 'type' => 'string', 'default' => 'upcoming' ),
				'featured'      => array( 'type' => 'string', 'default' => '' ),
				'category'      => array( 'type' => 'string', 'default' => '' ),
				'location'      => array( 'type' => 'string', 'default' => '' ),
				'artist'        => array( 'type' => 'string', 'default' => '' ),
				// Display options
				'showImage'     => array( 'type' => 'boolean', 'default' => true ),
				'showTitle'     => array( 'type' => 'boolean', 'default' => true ),
				'showDate'      => array( 'type' => 'boolean', 'default' => true ),
				'showTime'      => array( 'type' => 'boolean', 'default' => true ),
				'showLocation'  => array( 'type' => 'boolean', 'default' => true ),
				'showCategory'  => array( 'type' => 'boolean', 'default' => true ),
				'showPrice'     => array( 'type' => 'boolean', 'default' => true ),
				'showDesc'      => array( 'type' => 'boolean', 'default' => false ),
				'showArtists'   => array( 'type' => 'boolean', 'default' => false ),
				// Filter/Search options
				'showFilter'    => array( 'type' => 'boolean', 'default' => false ),
				'showSearch'    => array( 'type' => 'boolean', 'default' => false ),
				// Slider options
				'autoplay'      => array( 'type' => 'boolean', 'default' => false ),
				'autoplaySpeed' => array( 'type' => 'number', 'default' => 5000 ),
				'loop'          => array( 'type' => 'boolean', 'default' => false ),
				'dots'          => array( 'type' => 'boolean', 'default' => true ),
				'arrows'        => array( 'type' => 'boolean', 'default' => true ),
				'fullscreen'    => array( 'type' => 'boolean', 'default' => false ),
			),
		) );
	}

	/**
	 * Render Event Grid Block.
	 */
	public function render_event_grid_block( $attributes ) {
		$shortcode_atts = array(
			'layout'         => $attributes['layout'] ?? 'grid',
			'columns'        => $attributes['columns'] ?? 3,
			'style'          => $attributes['style'] ?? 'default',
			'limit'          => $attributes['limit'] ?? 12,
			'offset'         => $attributes['offset'] ?? 0,
			'orderby'        => $attributes['orderby'] ?? 'event_date',
			'order'          => $attributes['order'] ?? 'ASC',
			'show'           => $attributes['show'] ?? 'upcoming',
			'featured'       => $attributes['featured'] ?? '',
			'category'       => $attributes['category'] ?? '',
			'location'       => $attributes['location'] ?? '',
			'artist'         => $attributes['artist'] ?? '',
			'show_image'     => ( $attributes['showImage'] ?? true ) ? 'true' : 'false',
			'show_title'     => ( $attributes['showTitle'] ?? true ) ? 'true' : 'false',
			'show_date'      => ( $attributes['showDate'] ?? true ) ? 'true' : 'false',
			'show_time'      => ( $attributes['showTime'] ?? true ) ? 'true' : 'false',
			'show_location'  => ( $attributes['showLocation'] ?? true ) ? 'true' : 'false',
			'show_category'  => ( $attributes['showCategory'] ?? true ) ? 'true' : 'false',
			'show_price'     => ( $attributes['showPrice'] ?? true ) ? 'true' : 'false',
			'show_description' => ( $attributes['showDesc'] ?? false ) ? 'true' : 'false',
			'show_artists'   => ( $attributes['showArtists'] ?? false ) ? 'true' : 'false',
			// Filter/Search options
			'show_filters'   => ( $attributes['showFilter'] ?? false ) ? 'true' : 'false',
			'show_search'    => ( $attributes['showSearch'] ?? false ) ? 'true' : 'false',
			// Slider options
			'autoplay'       => ( $attributes['autoplay'] ?? false ) ? 'true' : 'false',
			'autoplay_speed' => $attributes['autoplaySpeed'] ?? 5000,
			'loop'           => ( $attributes['loop'] ?? false ) ? 'true' : 'false',
			'dots'           => ( $attributes['dots'] ?? true ) ? 'true' : 'false',
			'arrows'         => ( $attributes['arrows'] ?? true ) ? 'true' : 'false',
			'fullscreen'     => ( $attributes['fullscreen'] ?? false ) ? 'true' : 'false',
		);

		// Build shortcode string
		$shortcode_parts = array();
		foreach ( $shortcode_atts as $key => $value ) {
			if ( $value !== '' ) {
				$shortcode_parts[] = $key . '="' . esc_attr( $value ) . '"';
			}
		}

		$shortcode = '[ensemble_events ' . implode( ' ', $shortcode_parts ) . ']';

		return '<div class="wp-block-ensemble-event-grid">' . do_shortcode( $shortcode ) . '</div>';
	}

	/**
	 * Register Artist Grid Block.
	 */
	private function register_artist_grid_block() {
		wp_register_script(
			'ensemble-artist-grid-editor',
			ENSEMBLE_PLUGIN_URL . 'includes/blocks/artist-grid/index.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-data', 'wp-server-side-render' ),
			ENSEMBLE_VERSION,
			true
		);

		register_block_type( 'ensemble/artist-grid', array(
			'editor_script'   => 'ensemble-artist-grid-editor',
			'render_callback' => array( $this, 'render_artist_grid_block' ),
			'attributes'      => array(
				'layout'        => array( 'type' => 'string', 'default' => 'grid' ),
				'columns'       => array( 'type' => 'number', 'default' => 3 ),
				'style'         => array( 'type' => 'string', 'default' => 'default' ),
				'limit'         => array( 'type' => 'number', 'default' => 12 ),
				'orderby'       => array( 'type' => 'string', 'default' => 'title' ),
				'order'         => array( 'type' => 'string', 'default' => 'ASC' ),
				'genre'         => array( 'type' => 'string', 'default' => '' ),
				'type'          => array( 'type' => 'string', 'default' => '' ),
				// Display options
				'showImage'     => array( 'type' => 'boolean', 'default' => true ),
				'showName'      => array( 'type' => 'boolean', 'default' => true ),
				'showPosition'  => array( 'type' => 'boolean', 'default' => true ),
				'showCompany'   => array( 'type' => 'boolean', 'default' => true ),
				'showGenre'     => array( 'type' => 'boolean', 'default' => false ),
				'showType'      => array( 'type' => 'boolean', 'default' => false ),
				'showBio'       => array( 'type' => 'boolean', 'default' => true ),
				'showEvents'    => array( 'type' => 'boolean', 'default' => false ),
				'showSocial'    => array( 'type' => 'boolean', 'default' => false ),
				'showLink'      => array( 'type' => 'boolean', 'default' => true ),
				'linkText'      => array( 'type' => 'string', 'default' => 'View Profile' ),
				// Slider options
				'autoplay'      => array( 'type' => 'boolean', 'default' => false ),
				'autoplaySpeed' => array( 'type' => 'number', 'default' => 5000 ),
				'loop'          => array( 'type' => 'boolean', 'default' => false ),
				'dots'          => array( 'type' => 'boolean', 'default' => true ),
				'arrows'        => array( 'type' => 'boolean', 'default' => true ),
			),
		) );
	}

	/**
	 * Render Artist Grid Block.
	 */
	public function render_artist_grid_block( $attributes ) {
		$shortcode_atts = array(
			'layout'         => $attributes['layout'] ?? 'grid',
			'columns'        => $attributes['columns'] ?? 3,
			'style'          => $attributes['style'] ?? 'default',
			'limit'          => $attributes['limit'] ?? 12,
			'orderby'        => $attributes['orderby'] ?? 'title',
			'order'          => $attributes['order'] ?? 'ASC',
			'genre'          => $attributes['genre'] ?? '',
			'type'           => $attributes['type'] ?? '',
			// Display options - all fields
			'show_image'     => ( $attributes['showImage'] ?? true ) ? 'true' : 'false',
			'show_name'      => ( $attributes['showName'] ?? true ) ? 'true' : 'false',
			'show_position'  => ( $attributes['showPosition'] ?? true ) ? 'true' : 'false',
			'show_company'   => ( $attributes['showCompany'] ?? true ) ? 'true' : 'false',
			'show_genre'     => ( $attributes['showGenre'] ?? false ) ? 'true' : 'false',
			'show_type'      => ( $attributes['showType'] ?? false ) ? 'true' : 'false',
			'show_bio'       => ( $attributes['showBio'] ?? true ) ? 'true' : 'false',
			'show_events'    => ( $attributes['showEvents'] ?? false ) ? 'true' : 'false',
			'show_social'    => ( $attributes['showSocial'] ?? false ) ? 'true' : 'false',
			'show_link'      => ( $attributes['showLink'] ?? true ) ? 'true' : 'false',
			'link_text'      => $attributes['linkText'] ?? 'View Profile',
			// Slider options
			'autoplay'       => ( $attributes['autoplay'] ?? false ) ? 'true' : 'false',
			'autoplay_speed' => $attributes['autoplaySpeed'] ?? 5000,
			'loop'           => ( $attributes['loop'] ?? false ) ? 'true' : 'false',
			'dots'           => ( $attributes['dots'] ?? true ) ? 'true' : 'false',
			'arrows'         => ( $attributes['arrows'] ?? true ) ? 'true' : 'false',
		);

		// Build shortcode string
		$shortcode_parts = array();
		foreach ( $shortcode_atts as $key => $value ) {
			if ( $value !== '' ) {
				$shortcode_parts[] = $key . '="' . esc_attr( $value ) . '"';
			}
		}

		$shortcode = '[ensemble_artists ' . implode( ' ', $shortcode_parts ) . ']';

		return '<div class="wp-block-ensemble-artist-grid">' . do_shortcode( $shortcode ) . '</div>';
	}

	/**
	 * Register Location Grid Block.
	 */
	private function register_location_grid_block() {
		// Register script
		wp_register_script(
			'ensemble-location-grid-editor',
			ENSEMBLE_PLUGIN_URL . 'includes/blocks/location-grid/index.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-data', 'wp-server-side-render' ),
			ENSEMBLE_VERSION,
			true
		);

		register_block_type( 'ensemble/location-grid', array(
			'editor_script'   => 'ensemble-location-grid-editor',
			'render_callback' => array( $this, 'render_location_grid_block' ),
			'attributes'      => array(
				'layout'          => array( 'type' => 'string', 'default' => 'grid' ),
				'columns'         => array( 'type' => 'number', 'default' => 3 ),
				'limit'           => array( 'type' => 'number', 'default' => 12 ),
				'orderby'         => array( 'type' => 'string', 'default' => 'title' ),
				'order'           => array( 'type' => 'string', 'default' => 'ASC' ),
				'type'            => array( 'type' => 'string', 'default' => '' ),
				// Display options
				'showImage'       => array( 'type' => 'boolean', 'default' => true ),
				'showName'        => array( 'type' => 'boolean', 'default' => true ),
				'showType'        => array( 'type' => 'boolean', 'default' => true ),
				'showAddress'     => array( 'type' => 'boolean', 'default' => true ),
				'showCapacity'    => array( 'type' => 'boolean', 'default' => false ),
				'showEvents'      => array( 'type' => 'boolean', 'default' => false ),
				'showDescription' => array( 'type' => 'boolean', 'default' => false ),
				'showSocial'      => array( 'type' => 'boolean', 'default' => false ),
				'showLink'        => array( 'type' => 'boolean', 'default' => true ),
				'linkText'        => array( 'type' => 'string', 'default' => 'View Location' ),
				// Slider options
				'autoplay'        => array( 'type' => 'boolean', 'default' => false ),
				'autoplaySpeed'   => array( 'type' => 'number', 'default' => 5000 ),
				'loop'            => array( 'type' => 'boolean', 'default' => false ),
				'dots'            => array( 'type' => 'boolean', 'default' => true ),
				'arrows'          => array( 'type' => 'boolean', 'default' => true ),
			),
		) );
	}

	/**
	 * Render Location Grid Block.
	 */
	public function render_location_grid_block( $attributes ) {
		$shortcode_atts = array(
			'layout'           => $attributes['layout'] ?? 'grid',
			'columns'          => $attributes['columns'] ?? 3,
			'limit'            => $attributes['limit'] ?? 12,
			'orderby'          => $attributes['orderby'] ?? 'title',
			'order'            => $attributes['order'] ?? 'ASC',
			'type'             => $attributes['type'] ?? '',
			// Display options - all fields
			'show_image'       => ( $attributes['showImage'] ?? true ) ? 'true' : 'false',
			'show_name'        => ( $attributes['showName'] ?? true ) ? 'true' : 'false',
			'show_type'        => ( $attributes['showType'] ?? true ) ? 'true' : 'false',
			'show_address'     => ( $attributes['showAddress'] ?? true ) ? 'true' : 'false',
			'show_capacity'    => ( $attributes['showCapacity'] ?? false ) ? 'true' : 'false',
			'show_events'      => ( $attributes['showEvents'] ?? false ) ? 'true' : 'false',
			'show_description' => ( $attributes['showDescription'] ?? false ) ? 'true' : 'false',
			'show_social'      => ( $attributes['showSocial'] ?? false ) ? 'true' : 'false',
			'show_link'        => ( $attributes['showLink'] ?? true ) ? 'true' : 'false',
			'link_text'        => $attributes['linkText'] ?? 'View Location',
			// Slider options
			'autoplay'         => ( $attributes['autoplay'] ?? false ) ? 'true' : 'false',
			'autoplay_speed'   => $attributes['autoplaySpeed'] ?? 5000,
			'loop'             => ( $attributes['loop'] ?? false ) ? 'true' : 'false',
			'dots'             => ( $attributes['dots'] ?? true ) ? 'true' : 'false',
			'arrows'           => ( $attributes['arrows'] ?? true ) ? 'true' : 'false',
		);

		// Build shortcode string
		$shortcode_parts = array();
		foreach ( $shortcode_atts as $key => $value ) {
			if ( $value !== '' ) {
				$shortcode_parts[] = $key . '="' . esc_attr( $value ) . '"';
			}
		}

		$shortcode = '[ensemble_locations ' . implode( ' ', $shortcode_parts ) . ']';

		return '<div class="wp-block-ensemble-location-grid">' . do_shortcode( $shortcode ) . '</div>';
	}

	/**
	 * Register Countdown Block.
	 */
	private function register_countdown_block() {
		// Register script
		wp_register_script(
			'ensemble-countdown-editor',
			ENSEMBLE_PLUGIN_URL . 'includes/blocks/countdown/index.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-server-side-render' ),
			ENSEMBLE_VERSION,
			true
		);

		register_block_type( 'ensemble/countdown', array(
			'editor_script'   => 'ensemble-countdown-editor',
			'render_callback' => array( $this, 'render_countdown_block' ),
			'attributes'      => array(
				'mode'         => array( 'type' => 'string', 'default' => 'event' ),
				'eventId'      => array( 'type' => 'number', 'default' => 0 ),
				'date'         => array( 'type' => 'string', 'default' => '' ),
				'time'         => array( 'type' => 'string', 'default' => '' ),
				'title'        => array( 'type' => 'string', 'default' => '' ),
				'style'        => array( 'type' => 'string', 'default' => 'default' ),
				'showDays'     => array( 'type' => 'boolean', 'default' => true ),
				'showHours'    => array( 'type' => 'boolean', 'default' => true ),
				'showMinutes'  => array( 'type' => 'boolean', 'default' => true ),
				'showSeconds'  => array( 'type' => 'boolean', 'default' => true ),
				'showLabels'   => array( 'type' => 'boolean', 'default' => true ),
				'showTitle'    => array( 'type' => 'boolean', 'default' => true ),
				'showDate'     => array( 'type' => 'boolean', 'default' => true ),
				'showLink'     => array( 'type' => 'boolean', 'default' => true ),
				'linkText'     => array( 'type' => 'string', 'default' => 'View Event' ),
				'expiredText'  => array( 'type' => 'string', 'default' => 'Event has started!' ),
				'hideExpired'  => array( 'type' => 'boolean', 'default' => false ),
			),
		) );
	}

	/**
	 * Render Countdown Block.
	 */
	public function render_countdown_block( $attributes ) {
		$shortcode_atts = array(
			'style'        => $attributes['style'] ?? 'default',
			'show_days'    => ( $attributes['showDays'] ?? true ) ? 'true' : 'false',
			'show_hours'   => ( $attributes['showHours'] ?? true ) ? 'true' : 'false',
			'show_minutes' => ( $attributes['showMinutes'] ?? true ) ? 'true' : 'false',
			'show_seconds' => ( $attributes['showSeconds'] ?? true ) ? 'true' : 'false',
			'show_labels'  => ( $attributes['showLabels'] ?? true ) ? 'true' : 'false',
			'show_title'   => ( $attributes['showTitle'] ?? true ) ? 'true' : 'false',
			'show_date'    => ( $attributes['showDate'] ?? true ) ? 'true' : 'false',
			'show_link'    => ( $attributes['showLink'] ?? true ) ? 'true' : 'false',
			'link_text'    => $attributes['linkText'] ?? 'View Event',
			'expired_text' => $attributes['expiredText'] ?? 'Event has started!',
			'hide_expired' => ( $attributes['hideExpired'] ?? false ) ? 'true' : 'false',
		);

		// Add source (event or date)
		$mode = $attributes['mode'] ?? 'event';
		if ( $mode === 'event' && ! empty( $attributes['eventId'] ) ) {
			$shortcode_atts['event_id'] = $attributes['eventId'];
		} elseif ( $mode === 'date' && ! empty( $attributes['date'] ) ) {
			$shortcode_atts['date'] = $attributes['date'];
			if ( ! empty( $attributes['time'] ) ) {
				$shortcode_atts['time'] = $attributes['time'];
			}
			if ( ! empty( $attributes['title'] ) ) {
				$shortcode_atts['title'] = $attributes['title'];
			}
		} else {
			// No valid source
			return '<div class="wp-block-ensemble-countdown"><p style="text-align:center;color:#666;">Please configure countdown source</p></div>';
		}

		// Build shortcode string
		$shortcode_parts = array();
		foreach ( $shortcode_atts as $key => $value ) {
			if ( $value !== '' ) {
				$shortcode_parts[] = $key . '="' . esc_attr( $value ) . '"';
			}
		}

		$shortcode = '[ensemble_countdown ' . implode( ' ', $shortcode_parts ) . ']';

		return '<div class="wp-block-ensemble-countdown">' . do_shortcode( $shortcode ) . '</div>';
	}

	/**
	 * Register Single Event Block.
	 */
	private function register_single_event_block() {
		// Register script
		wp_register_script(
			'ensemble-single-event-editor',
			ENSEMBLE_PLUGIN_URL . 'includes/blocks/single-event/index.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-server-side-render' ),
			ENSEMBLE_VERSION,
			true
		);

		register_block_type( 'ensemble/single-event', array(
			'editor_script'   => 'ensemble-single-event-editor',
			'render_callback' => array( $this, 'render_single_event_block' ),
			'attributes'      => array(
				'eventId'      => array( 'type' => 'number', 'default' => 0 ),
				'layout'       => array( 'type' => 'string', 'default' => 'card' ),
				'showImage'    => array( 'type' => 'boolean', 'default' => true ),
				'showDate'     => array( 'type' => 'boolean', 'default' => true ),
				'showTime'     => array( 'type' => 'boolean', 'default' => true ),
				'showLocation' => array( 'type' => 'boolean', 'default' => true ),
				'showArtist'   => array( 'type' => 'boolean', 'default' => true ),
				'showExcerpt'  => array( 'type' => 'boolean', 'default' => true ),
				'showLink'     => array( 'type' => 'boolean', 'default' => true ),
				'linkText'     => array( 'type' => 'string', 'default' => 'View Event' ),
			),
		) );
	}

	/**
	 * Render Single Event Block.
	 */
	public function render_single_event_block( $attributes ) {
		// Check for event ID
		if ( empty( $attributes['eventId'] ) ) {
			return '<div class="wp-block-ensemble-single-event"><p style="text-align:center;color:#666;">Please select an event</p></div>';
		}

		$shortcode_atts = array(
			'id'            => $attributes['eventId'],
			'layout'        => $attributes['layout'] ?? 'card',
			'show_image'    => ( $attributes['showImage'] ?? true ) ? 'true' : 'false',
			'show_date'     => ( $attributes['showDate'] ?? true ) ? 'true' : 'false',
			'show_time'     => ( $attributes['showTime'] ?? true ) ? 'true' : 'false',
			'show_location' => ( $attributes['showLocation'] ?? true ) ? 'true' : 'false',
			'show_artist'   => ( $attributes['showArtist'] ?? true ) ? 'true' : 'false',
			'show_excerpt'  => ( $attributes['showExcerpt'] ?? true ) ? 'true' : 'false',
			'show_link'     => ( $attributes['showLink'] ?? true ) ? 'true' : 'false',
			'link_text'     => $attributes['linkText'] ?? 'View Event',
		);

		// Build shortcode string
		$shortcode_parts = array();
		foreach ( $shortcode_atts as $key => $value ) {
			if ( $value !== '' ) {
				$shortcode_parts[] = $key . '="' . esc_attr( $value ) . '"';
			}
		}

		$shortcode = '[ensemble_event ' . implode( ' ', $shortcode_parts ) . ']';

		return '<div class="wp-block-ensemble-single-event">' . do_shortcode( $shortcode ) . '</div>';
	}

	/**
	 * Register Calendar Block.
	 */
	private function register_calendar_block() {
		wp_register_script(
			'ensemble-calendar-editor',
			ENSEMBLE_PLUGIN_URL . 'includes/blocks/calendar/index.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-server-side-render' ),
			ENSEMBLE_VERSION,
			true
		);

		register_block_type( 'ensemble/calendar', array(
			'editor_script'   => 'ensemble-calendar-editor',
			'render_callback' => array( $this, 'render_calendar_block' ),
			'attributes'      => array(
				'view'        => array( 'type' => 'string', 'default' => 'month' ),
				'height'      => array( 'type' => 'string', 'default' => 'auto' ),
				'initialDate' => array( 'type' => 'string', 'default' => '' ),
			),
		) );
	}

	/**
	 * Render Calendar Block.
	 */
	public function render_calendar_block( $attributes ) {
		$shortcode_atts = array(
			'view'         => $attributes['view'] ?? 'month',
			'height'       => $attributes['height'] ?? 'auto',
			'initial_date' => $attributes['initialDate'] ?? '',
		);

		// Build shortcode string
		$shortcode_parts = array();
		foreach ( $shortcode_atts as $key => $value ) {
			if ( $value !== '' ) {
				$shortcode_parts[] = $key . '="' . esc_attr( $value ) . '"';
			}
		}

		$shortcode = '[ensemble_calendar ' . implode( ' ', $shortcode_parts ) . ']';

		return '<div class="wp-block-ensemble-calendar">' . do_shortcode( $shortcode ) . '</div>';
	}

	/**
	 * Register Locations Map Block.
	 */
	private function register_locations_map_block() {
		wp_register_script(
			'ensemble-locations-map-editor',
			ENSEMBLE_PLUGIN_URL . 'includes/blocks/locations-map/index.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-server-side-render' ),
			ENSEMBLE_VERSION,
			true
		);

		register_block_type( 'ensemble/locations-map', array(
			'editor_script'   => 'ensemble-locations-map-editor',
			'render_callback' => array( $this, 'render_locations_map_block' ),
			'attributes'      => array(
				'height'           => array( 'type' => 'string', 'default' => '500px' ),
				'style'            => array( 'type' => 'string', 'default' => 'default' ),
				'clustering'       => array( 'type' => 'boolean', 'default' => true ),
				'fullscreen'       => array( 'type' => 'boolean', 'default' => true ),
				'geolocation'      => array( 'type' => 'boolean', 'default' => true ),
				'search'           => array( 'type' => 'boolean', 'default' => true ),
				'markerThumbnails' => array( 'type' => 'boolean', 'default' => true ),
				'filter'           => array( 'type' => 'boolean', 'default' => true ),
				'category'         => array( 'type' => 'string', 'default' => '' ),
				'city'             => array( 'type' => 'string', 'default' => '' ),
				'limit'            => array( 'type' => 'number', 'default' => 0 ),
			),
		) );
	}

	/**
	 * Render Locations Map Block.
	 */
	public function render_locations_map_block( $attributes ) {
		// Ensure Maps addon scripts are loaded
		// Pass true to force loading (skip page checks since we know we need the scripts)
		if ( class_exists( 'ES_Addon_Manager' ) ) {
			$maps_addon = ES_Addon_Manager::get_active_addon( 'maps' );
			if ( $maps_addon && method_exists( $maps_addon, 'enqueue_frontend_assets' ) ) {
				$maps_addon->enqueue_frontend_assets( true );
			}
		}

		$shortcode_atts = array(
			'height'            => $attributes['height'] ?? '500px',
			'style'             => $attributes['style'] ?? 'default',
			'clustering'        => ( $attributes['clustering'] ?? true ) ? 'true' : 'false',
			'fullscreen'        => ( $attributes['fullscreen'] ?? true ) ? 'true' : 'false',
			'geolocation'       => ( $attributes['geolocation'] ?? true ) ? 'true' : 'false',
			'search'            => ( $attributes['search'] ?? true ) ? 'true' : 'false',
			'marker_thumbnails' => ( $attributes['markerThumbnails'] ?? true ) ? 'true' : 'false',
			'filter'            => ( $attributes['filter'] ?? true ) ? 'true' : 'false',
			'category'          => $attributes['category'] ?? '',
			'city'              => $attributes['city'] ?? '',
			'limit'             => $attributes['limit'] ?? 0,
		);

		// Build shortcode string
		$shortcode_parts = array();
		foreach ( $shortcode_atts as $key => $value ) {
			if ( $value !== '' && $value !== 0 ) {
				$shortcode_parts[] = $key . '="' . esc_attr( $value ) . '"';
			}
		}

		$shortcode = '[ensemble_locations_map ' . implode( ' ', $shortcode_parts ) . ']';

		return '<div class="wp-block-ensemble-locations-map">' . do_shortcode( $shortcode ) . '</div>';
	}

	/**
	 * Register Upcoming Events Block.
	 */
	private function register_upcoming_events_block() {
		wp_register_script(
			'ensemble-upcoming-events-editor',
			ENSEMBLE_PLUGIN_URL . 'includes/blocks/upcoming-events/index.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-server-side-render' ),
			ENSEMBLE_VERSION,
			true
		);

		register_block_type( 'ensemble/upcoming-events', array(
			'editor_script'   => 'ensemble-upcoming-events-editor',
			'render_callback' => array( $this, 'render_upcoming_events_block' ),
			'attributes'      => array(
				'limit'         => array( 'type' => 'number', 'default' => 5 ),
				'showCountdown' => array( 'type' => 'boolean', 'default' => false ),
				'showImage'     => array( 'type' => 'boolean', 'default' => true ),
				'showLocation'  => array( 'type' => 'boolean', 'default' => true ),
				'showArtist'    => array( 'type' => 'boolean', 'default' => true ),
			),
		) );
	}

	/**
	 * Render Upcoming Events Block.
	 */
	public function render_upcoming_events_block( $attributes ) {
		$shortcode_atts = array(
			'limit'          => $attributes['limit'] ?? 5,
			'show_countdown' => ( $attributes['showCountdown'] ?? false ) ? 'true' : 'false',
			'show_image'     => ( $attributes['showImage'] ?? true ) ? 'true' : 'false',
			'show_location'  => ( $attributes['showLocation'] ?? true ) ? 'true' : 'false',
			'show_artist'    => ( $attributes['showArtist'] ?? true ) ? 'true' : 'false',
		);

		// Build shortcode string
		$shortcode_parts = array();
		foreach ( $shortcode_atts as $key => $value ) {
			if ( $value !== '' ) {
				$shortcode_parts[] = $key . '="' . esc_attr( $value ) . '"';
			}
		}

		$shortcode = '[ensemble_upcoming_events ' . implode( ' ', $shortcode_parts ) . ']';

		return '<div class="wp-block-ensemble-upcoming-events">' . do_shortcode( $shortcode ) . '</div>';
	}

	/**
	 * Register Staff Grid Block.
	 * 
	 * Only called when Staff addon is active.
	 */
	private function register_staff_grid_block() {
		wp_register_script(
			'ensemble-staff-grid-editor',
			ENSEMBLE_PLUGIN_URL . 'includes/blocks/staff/index.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-data', 'wp-server-side-render' ),
			ENSEMBLE_VERSION,
			true
		);

		// Load Staff addon CSS in editor
		if ( class_exists( 'ES_Addon_Manager' ) && ES_Addon_Manager::is_addon_active( 'staff' ) ) {
			$addon = ES_Addon_Manager::get_active_addon( 'staff' );
			if ( $addon && method_exists( $addon, 'get_addon_url' ) ) {
				wp_enqueue_style(
					'ensemble-staff-editor',
					$addon->get_addon_url() . 'assets/staff.css',
					array(),
					ENSEMBLE_VERSION
				);
			}
		}

		register_block_type( 'ensemble/staff-grid', array(
			'editor_script'   => 'ensemble-staff-grid-editor',
			'render_callback' => array( $this, 'render_staff_grid_block' ),
			'attributes'      => array(
				'layout'             => array( 'type' => 'string', 'default' => 'grid' ),
				'columns'            => array( 'type' => 'number', 'default' => 3 ),
				'limit'              => array( 'type' => 'number', 'default' => -1 ),
				'orderby'            => array( 'type' => 'string', 'default' => 'menu_order' ),
				'order'              => array( 'type' => 'string', 'default' => 'ASC' ),
				'department'         => array( 'type' => 'string', 'default' => '' ),
				'ids'                => array( 'type' => 'string', 'default' => '' ),
				// Display options
				'showImage'          => array( 'type' => 'boolean', 'default' => true ),
				'showEmail'          => array( 'type' => 'boolean', 'default' => true ),
				'showPhone'          => array( 'type' => 'boolean', 'default' => true ),
				'showPosition'       => array( 'type' => 'boolean', 'default' => true ),
				'showDepartment'     => array( 'type' => 'boolean', 'default' => true ),
				'showOfficeHours'    => array( 'type' => 'boolean', 'default' => false ),
				'showSocial'         => array( 'type' => 'boolean', 'default' => false ),
				'showResponsibility' => array( 'type' => 'boolean', 'default' => false ),
				'showExcerpt'        => array( 'type' => 'boolean', 'default' => false ),
			),
		) );
	}

	/**
	 * Render Staff Grid Block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string Rendered HTML.
	 */
	public function render_staff_grid_block( $attributes ) {
		// Check if Staff addon is active
		if ( ! class_exists( 'ES_Addon_Manager' ) || ! ES_Addon_Manager::is_addon_active( 'staff' ) ) {
			return '<div class="wp-block-ensemble-staff-grid"><p style="text-align:center;color:#666;">' . __( 'Staff addon is not active.', 'ensemble' ) . '</p></div>';
		}

		$shortcode_atts = array(
			'layout'              => $attributes['layout'] ?? 'grid',
			'columns'             => $attributes['columns'] ?? 3,
			'limit'               => $attributes['limit'] ?? -1,
			'orderby'             => $attributes['orderby'] ?? 'menu_order',
			'order'               => $attributes['order'] ?? 'ASC',
			'department'          => $attributes['department'] ?? '',
			'ids'                 => $attributes['ids'] ?? '',
			// Display options
			'show_image'          => ( $attributes['showImage'] ?? true ) ? 'yes' : 'no',
			'show_email'          => ( $attributes['showEmail'] ?? true ) ? 'yes' : 'no',
			'show_phone'          => ( $attributes['showPhone'] ?? true ) ? 'yes' : 'no',
			'show_position'       => ( $attributes['showPosition'] ?? true ) ? 'yes' : 'no',
			'show_department'     => ( $attributes['showDepartment'] ?? true ) ? 'yes' : 'no',
			'show_office_hours'   => ( $attributes['showOfficeHours'] ?? false ) ? 'yes' : 'no',
			'show_social'         => ( $attributes['showSocial'] ?? false ) ? 'yes' : 'no',
			'show_responsibility' => ( $attributes['showResponsibility'] ?? false ) ? 'yes' : 'no',
			'show_excerpt'        => ( $attributes['showExcerpt'] ?? false ) ? 'yes' : 'no',
		);

		// Build shortcode string
		$shortcode_parts = array();
		foreach ( $shortcode_atts as $key => $value ) {
			if ( $value !== '' ) {
				$shortcode_parts[] = $key . '="' . esc_attr( $value ) . '"';
			}
		}

		$shortcode = '[ensemble_staff ' . implode( ' ', $shortcode_parts ) . ']';

		return '<div class="wp-block-ensemble-staff-grid">' . do_shortcode( $shortcode ) . '</div>';
	}

	// =========================================================================
	// RESERVATIONS BLOCKS
	// =========================================================================

	/**
	 * Check if Reservations addon is active.
	 * 
	 * @return bool
	 */
	private function is_reservations_addon_active() {
		if ( class_exists( 'ES_Addon_Manager' ) && method_exists( 'ES_Addon_Manager', 'is_addon_active' ) ) {
			return ES_Addon_Manager::is_addon_active( 'reservations' );
		}
		return class_exists( 'ES_Reservations_Addon' );
	}

	/**
	 * Register Reservation Blocks.
	 */
	private function register_reservation_blocks() {
		if ( ! $this->is_reservations_addon_active() ) {
			return;
		}

		wp_register_script(
			'ensemble-reservations-editor',
			ENSEMBLE_PLUGIN_URL . 'includes/blocks/reservations/index.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-server-side-render' ),
			ENSEMBLE_VERSION,
			true
		);

		// Load Reservations CSS in editor
		if ( class_exists( 'ES_Addon_Manager' ) ) {
			$addon = ES_Addon_Manager::get_active_addon( 'reservations' );
			if ( $addon && method_exists( $addon, 'get_url' ) ) {
				wp_enqueue_style(
					'ensemble-reservations-editor',
					$addon->get_url() . 'assets/reservations.css',
					array(),
					ENSEMBLE_VERSION
				);
			}
		}

		// Events for selector
		$events = $this->get_reservation_events();
		wp_localize_script( 'ensemble-reservations-editor', 'ensembleReservationsBlocks', array(
			'events' => $events,
		) );

		// Block: Reservation Form
		register_block_type( 'ensemble/reservation-form', array(
			'editor_script'   => 'ensemble-reservations-editor',
			'render_callback' => array( $this, 'render_reservation_form_block' ),
			'attributes'      => array(
				'eventId'    => array( 'type' => 'number', 'default' => 0 ),
				'buttonText' => array( 'type' => 'string', 'default' => '' ),
			),
		) );

		// Block: Guest List
		register_block_type( 'ensemble/guestlist', array(
			'editor_script'   => 'ensemble-reservations-editor',
			'render_callback' => array( $this, 'render_guestlist_block' ),
			'attributes'      => array(
				'eventId'       => array( 'type' => 'number', 'default' => 0 ),
				'limit'         => array( 'type' => 'number', 'default' => 0 ),
				'showCheckedIn' => array( 'type' => 'boolean', 'default' => false ),
				'showType'      => array( 'type' => 'boolean', 'default' => true ),
			),
		) );

		// Block: Availability
		register_block_type( 'ensemble/availability', array(
			'editor_script'   => 'ensemble-reservations-editor',
			'render_callback' => array( $this, 'render_availability_block' ),
			'attributes'      => array(
				'eventId'     => array( 'type' => 'number', 'default' => 0 ),
				'style'       => array( 'type' => 'string', 'default' => 'badge' ),
				'showNumbers' => array( 'type' => 'boolean', 'default' => true ),
			),
		) );
	}

	/**
	 * Get events with reservations enabled.
	 * 
	 * @return array
	 */
	private function get_reservation_events() {
		$post_type = function_exists( 'ensemble_get_post_type' ) ? ensemble_get_post_type() : 'post';
		
		$events = get_posts( array(
			'post_type'      => $post_type,
			'posts_per_page' => 100,
			'post_status'    => 'publish',
			'meta_query'     => array( 
				array( 
					'key'     => '_reservation_enabled', 
					'value'   => array( '1', 'true', 'yes' ),
					'compare' => 'IN'
				) 
			),
			'orderby'        => 'date',
			'order'          => 'DESC',
		) );

		$options = array();
		foreach ( $events as $event ) {
			$date = get_post_meta( $event->ID, '_event_start_date', true );
			$options[] = array(
				'value' => $event->ID,
				'label' => $event->post_title . ( $date ? ' (' . date_i18n( 'd.m.Y', strtotime( $date ) ) . ')' : '' ),
			);
		}
		return $options;
	}

	/**
	 * Render Reservation Form Block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string Rendered HTML.
	 */
	public function render_reservation_form_block( $attributes ) {
		if ( ! $this->is_reservations_addon_active() ) {
			return '<div class="wp-block-ensemble-reservation-form"><p style="text-align:center;color:#666;">' . __( 'Reservations addon is not active.', 'ensemble' ) . '</p></div>';
		}

		$event_id = $attributes['eventId'] ?? 0;
		if ( ! $event_id ) {
			$event_id = get_the_ID();
		}

		if ( ! get_post_meta( $event_id, '_reservation_enabled', true ) ) {
			if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
				return '<div class="es-block-placeholder" style="padding:40px;text-align:center;background:#f9f9f9;border:2px dashed #ddd;border-radius:8px;">
					<span class="dashicons dashicons-clipboard" style="font-size:48px;opacity:0.5;display:block;margin-bottom:10px;"></span>
					<strong>' . __( 'Reservation Form', 'ensemble' ) . '</strong><br>
					<span style="color:#666;">' . __( 'No event selected or reservations not enabled.', 'ensemble' ) . '</span>
				</div>';
			}
			return '';
		}

		$shortcode = '[ensemble_reservation_form event="' . $event_id . '"';
		if ( ! empty( $attributes['buttonText'] ) ) {
			$shortcode .= ' button="' . esc_attr( $attributes['buttonText'] ) . '"';
		}
		$shortcode .= ']';

		return '<div class="wp-block-ensemble-reservation-form">' . do_shortcode( $shortcode ) . '</div>';
	}

	/**
	 * Render Guestlist Block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string Rendered HTML.
	 */
	public function render_guestlist_block( $attributes ) {
		if ( ! $this->is_reservations_addon_active() ) {
			return '';
		}

		if ( ! is_user_logged_in() || ! current_user_can( 'edit_posts' ) ) {
			if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
				return '<div class="es-block-placeholder" style="padding:40px;text-align:center;background:#f9f9f9;border:2px dashed #ddd;border-radius:8px;">
					<span class="dashicons dashicons-groups" style="font-size:48px;opacity:0.5;display:block;margin-bottom:10px;"></span>
					<strong>' . __( 'Guest List', 'ensemble' ) . '</strong><br>
					<span style="color:#666;">' . __( 'Only visible to authorized users.', 'ensemble' ) . '</span>
				</div>';
			}
			return '';
		}

		$event_id = $attributes['eventId'] ?? 0;
		if ( ! $event_id ) {
			$event_id = get_the_ID();
		}

		$shortcode = '[ensemble_guestlist event="' . $event_id . '"';
		if ( ( $attributes['limit'] ?? 0 ) > 0 ) {
			$shortcode .= ' limit="' . $attributes['limit'] . '"';
		}
		$shortcode .= ' show_checkin="' . ( ( $attributes['showCheckedIn'] ?? false ) ? 'yes' : 'no' ) . '"';
		$shortcode .= ' show_type="' . ( ( $attributes['showType'] ?? true ) ? 'yes' : 'no' ) . '"';
		$shortcode .= ']';

		return '<div class="wp-block-ensemble-guestlist">' . do_shortcode( $shortcode ) . '</div>';
	}

	/**
	 * Render Availability Block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string Rendered HTML.
	 */
	public function render_availability_block( $attributes ) {
		if ( ! $this->is_reservations_addon_active() ) {
			return '';
		}

		$event_id = $attributes['eventId'] ?? 0;
		if ( ! $event_id ) {
			$event_id = get_the_ID();
		}

		if ( ! get_post_meta( $event_id, '_reservation_enabled', true ) ) {
			if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
				return '<div class="es-block-placeholder" style="padding:40px;text-align:center;background:#f9f9f9;border:2px dashed #ddd;border-radius:8px;">
					<span class="dashicons dashicons-chart-bar" style="font-size:48px;opacity:0.5;display:block;margin-bottom:10px;"></span>
					<strong>' . __( 'Availability', 'ensemble' ) . '</strong><br>
					<span style="color:#666;">' . __( 'Reservations not enabled for this event.', 'ensemble' ) . '</span>
				</div>';
			}
			return '';
		}

		$addon = class_exists( 'ES_Addon_Manager' ) ? ES_Addon_Manager::get_active_addon( 'reservations' ) : null;
		$types = get_post_meta( $event_id, '_reservation_types', true ) ?: array( 'guestlist' );
		$labels = array( 
			'guestlist' => __( 'Guest List', 'ensemble' ), 
			'vip'       => __( 'VIP', 'ensemble' ), 
			'table'     => __( 'Table', 'ensemble' ) 
		);

		$html = '<div class="wp-block-ensemble-availability es-availability-block">';
		foreach ( $types as $type ) {
			$capacity = $addon && method_exists( $addon, 'get_type_capacity' ) ? $addon->get_type_capacity( $event_id, $type ) : null;
			$remaining = $addon && method_exists( $addon, 'get_type_remaining' ) ? $addon->get_type_remaining( $event_id, $type ) : null;
			$label = isset( $labels[ $type ] ) ? $labels[ $type ] : $type;

			$html .= '<div class="es-availability-type" style="display:inline-block;margin-right:15px;">';
			$html .= '<span class="es-availability-label">' . esc_html( $label ) . ': </span>';
			
			if ( $capacity !== null && ( $attributes['showNumbers'] ?? true ) ) {
				$class = $remaining < 10 ? 'es-availability-low' : '';
				$style = $remaining < 10 ? 'background:#ff6b6b;color:#fff;' : 'background:#51cf66;color:#fff;';
				$html .= '<span class="es-availability-badge ' . $class . '" style="' . $style . 'padding:2px 8px;border-radius:3px;font-size:12px;">' . $remaining . ' / ' . $capacity . '</span>';
			} elseif ( $remaining === null ) {
				$html .= '<span class="es-availability-badge" style="background:#51cf66;color:#fff;padding:2px 8px;border-radius:3px;font-size:12px;">' . __( 'Available', 'ensemble' ) . '</span>';
			} elseif ( $remaining <= 0 ) {
				$html .= '<span class="es-availability-badge es-availability-soldout" style="background:#ff6b6b;color:#fff;padding:2px 8px;border-radius:3px;font-size:12px;">' . __( 'Sold Out', 'ensemble' ) . '</span>';
			}
			$html .= '</div>';
		}
		$html .= '</div>';

		return $html;
	}
}

// Initialize
ES_Blocks::instance();
