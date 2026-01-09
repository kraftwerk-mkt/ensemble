<?php
/**
 * Ensemble Shortcode Base Class
 *
 * Abstract base class providing shared functionality for all shortcode classes.
 * Contains helper methods for meta key detection, date formatting, template handling,
 * and asset enqueuing.
 *
 * @package Ensemble
 * @subpackage Shortcodes
 * @since 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract base class for Ensemble shortcodes.
 *
 * @since 3.0.0
 */
abstract class ES_Shortcode_Base {

	/**
	 * Cached date meta key.
	 *
	 * @var string|null
	 */
	protected static $date_key_cache = null;

	/**
	 * Register shortcodes - must be implemented by child classes.
	 *
	 * @return void
	 */
	abstract public function register_shortcodes();

	/**
	 * Detect which meta key format is being used and return the date meta key.
	 *
	 * Considers Field Mapping, es_ prefix, and legacy format.
	 *
	 * @return string The meta key to use for date queries.
	 */
	protected function get_date_meta_key() {
		if ( self::$date_key_cache !== null ) {
			return self::$date_key_cache;
		}

		// 1. Check if user has configured field mapping for start_date.
		$mapped_field = ensemble_get_mapped_field( 'start_date' );
		if ( $mapped_field ) {
			self::$date_key_cache = $mapped_field;
			return self::$date_key_cache;
		}

		// 2. Check if any events use es_ prefixed keys.
		$test_query = new WP_Query(
			array(
				'post_type'      => ensemble_get_post_type(),
				'posts_per_page' => 1,
				'meta_key'       => 'es_event_start_date',
				'fields'         => 'ids',
			)
		);

		if ( $test_query->have_posts() ) {
			self::$date_key_cache = 'es_event_start_date';
		} else {
			// 3. Check for legacy keys.
			$test_query = new WP_Query(
				array(
					'post_type'      => ensemble_get_post_type(),
					'posts_per_page' => 1,
					'meta_key'       => 'event_date',
					'fields'         => 'ids',
				)
			);

			if ( $test_query->have_posts() ) {
				self::$date_key_cache = 'event_date';
			} else {
				// Default to es_ format.
				self::$date_key_cache = 'es_event_start_date';
			}
		}

		wp_reset_postdata();
		return self::$date_key_cache;
	}

	/**
	 * Legacy function - kept for compatibility.
	 *
	 * @deprecated Use get_date_meta_key() instead.
	 * @return string Meta key prefix.
	 */
	protected function get_meta_key_prefix() {
		$date_key = $this->get_date_meta_key();

		if ( 'es_event_start_date' === $date_key ) {
			return 'es_';
		} elseif ( 'event_date' === $date_key ) {
			return '';
		}

		return 'es_';
	}

	/**
	 * Get event meta field with Field Mapping support.
	 *
	 * Priority:
	 * 1. Try Field Mapping (configured by user)
	 * 2. Try es_ prefixed keys (wizard format)
	 * 3. Try non-prefixed keys (legacy format)
	 *
	 * @param int    $event_id Event ID.
	 * @param string $field    Field name (e.g. 'start_date', 'location', 'artist').
	 * @return mixed Meta value.
	 */
	protected function get_event_meta( $event_id, $field ) {
		// 1. Check if user has configured field mapping.
		$mapped_field = ensemble_get_mapped_field( $field );

		if ( $mapped_field && function_exists( 'get_field' ) ) {
			// Use ACF to get the mapped field.
			$value = get_field( $mapped_field, $event_id );
			if ( ! empty( $value ) ) {
				// Handle ACF post object fields (returns WP_Post object).
				if ( is_object( $value ) && isset( $value->ID ) ) {
					return $value->ID;
				}
				// Handle ACF relationship/post object fields (returns array of posts).
				if ( is_array( $value ) && isset( $value[0]->ID ) ) {
					return $value[0]->ID;
				}
				return $value;
			}
		}

		// 2. Try es_event_{field} (wizard format).
		$value = get_post_meta( $event_id, 'es_event_' . $field, true );

		if ( empty( $value ) ) {
			// 3. Fallback to legacy format.
			$legacy_map = array(
				'start_date' => 'event_date',
				'start_time' => 'event_time',
				'end_date'   => 'event_end_date',
				'end_time'   => 'event_end_time',
				'location'   => 'event_location',
				'artist'     => 'event_artist',
				'price'      => 'event_price',
				'ticket_url' => 'event_ticket_url',
			);

			$legacy_key = isset( $legacy_map[ $field ] ) ? $legacy_map[ $field ] : 'event_' . $field;
			$value      = get_post_meta( $event_id, $legacy_key, true );

			// Handle serialized artist data.
			if ( 'artist' === $field && is_string( $value ) && strpos( $value, 'a:' ) === 0 ) {
				$artist_array = @unserialize( $value );
				if ( is_array( $artist_array ) && ! empty( $artist_array ) ) {
					$value = $artist_array[0];
				}
			}
		}

		return $value;
	}

	/**
	 * Get the correct meta key for a field (for use in WP_Query).
	 *
	 * Checks field mapping, wizard format, and legacy format.
	 *
	 * @param string $field Field name (e.g. 'location', 'artist').
	 * @return string The meta key to use in queries.
	 */
	protected function get_meta_key_for_field( $field ) {
		// 1. Check if user has configured field mapping.
		$mapped_field = ensemble_get_mapped_field( $field );
		if ( $mapped_field ) {
			// ACF fields typically use the field name directly.
			return $mapped_field;
		}

		// 2. Check which format has data - test with a sample event.
		$sample_event = get_posts(
			array(
				'post_type'      => ensemble_get_post_type(),
				'posts_per_page' => 1,
				'meta_query'     => array(
					'relation' => 'OR',
					array(
						'key'     => 'es_event_' . $field,
						'compare' => 'EXISTS',
					),
					array(
						'key'     => 'event_' . $field,
						'compare' => 'EXISTS',
					),
				),
				'fields'         => 'ids',
			)
		);

		if ( ! empty( $sample_event ) ) {
			$event_id = $sample_event[0];

			// Check wizard format first.
			$wizard_value = get_post_meta( $event_id, 'es_event_' . $field, true );
			if ( ! empty( $wizard_value ) ) {
				return 'es_event_' . $field;
			}

			// Check legacy format.
			$legacy_map = array(
				'location' => 'event_location',
				'artist'   => 'event_artist',
			);
			$legacy_key = isset( $legacy_map[ $field ] ) ? $legacy_map[ $field ] : 'event_' . $field;
			$legacy_value = get_post_meta( $event_id, $legacy_key, true );
			if ( ! empty( $legacy_value ) ) {
				return $legacy_key;
			}
		}

		// Default to wizard format.
		return 'es_event_' . $field;
	}

	/**
	 * Apply template if specified in shortcode.
	 *
	 * @param string $template Template name from shortcode attribute.
	 * @return void
	 */
	protected function apply_shortcode_template( $template ) {
		if ( empty( $template ) || ! class_exists( 'ES_Design_Settings' ) ) {
			return;
		}

		$current_template = ES_Design_Settings::get_active_template();

		// Only load if different from current.
		if ( $template !== $current_template ) {
			ES_Design_Settings::load_template( $template );

			// Regenerate CSS inline for this page.
			if ( class_exists( 'ES_CSS_Generator' ) ) {
				$custom_css = ES_CSS_Generator::generate();
				echo '<style id="ensemble-template-' . esc_attr( $template ) . '">' . $custom_css . '</style>';
			}
		}
	}

	/**
	 * Get effective template from shortcode attribute or URL parameter.
	 *
	 * Priority:
	 * 1. Explicit shortcode attribute
	 * 2. URL parameter es_layout
	 * 3. Empty (use default/current)
	 *
	 * @param string $shortcode_template Template from shortcode attribute.
	 * @return string Effective template to use.
	 */
	protected function get_effective_template( $shortcode_template = '' ) {
		// If explicit template in shortcode, use it.
		if ( ! empty( $shortcode_template ) ) {
			return sanitize_key( $shortcode_template );
		}

		// Check URL parameter for layout switcher.
		if ( isset( $_GET['es_layout'] ) && ! empty( $_GET['es_layout'] ) ) {
			$url_layout = sanitize_key( wp_unslash( $_GET['es_layout'] ) );
			$available  = ES_Utility_Shortcodes::get_available_layouts();
			if ( isset( $available[ $url_layout ] ) ) {
				return $url_layout;
			}
		}

		// Return empty to use default.
		return '';
	}

	/**
	 * Get event data array.
	 *
	 * @param int $event_id Event Post ID.
	 * @return array Event data.
	 */
	protected function get_event_data( $event_id ) {
		// Get event status.
		$event_status = get_post_meta( $event_id, '_event_status', true );
		if ( empty( $event_status ) ) {
			$event_status = get_post_status( $event_id ) === 'draft' ? 'draft' : 'publish';
		}

		return array(
			'start_date'  => $this->get_event_meta( $event_id, 'start_date' ),
			'start_time'  => $this->get_event_meta( $event_id, 'start_time' ),
			'end_date'    => $this->get_event_meta( $event_id, 'end_date' ),
			'end_time'    => $this->get_event_meta( $event_id, 'end_time' ),
			'location_id' => $this->get_event_meta( $event_id, 'location' ),
			'artist_id'   => $this->get_event_meta( $event_id, 'artist' ),
			'price'       => $this->get_event_meta( $event_id, 'price' ),
			'ticket_url'  => $this->get_event_meta( $event_id, 'ticket_url' ),
			'status'      => $event_status,
		);
	}

	/**
	 * Format date range.
	 *
	 * @param string $start Start date.
	 * @param string $end   Optional end date.
	 * @return string Formatted date range.
	 */
	protected function format_date( $start, $end = '' ) {
		if ( ! $start ) {
			return '';
		}

		$start_date = date_i18n( 'j. F Y', strtotime( $start ) );

		if ( $end && $end !== $start ) {
			$end_date = date_i18n( 'j. F Y', strtotime( $end ) );
			return $start_date . ' - ' . $end_date;
		}

		return $start_date;
	}

	/**
	 * Format date short.
	 *
	 * @param string $date Date string.
	 * @return string Formatted short date.
	 */
	protected function format_date_short( $date ) {
		if ( ! $date ) {
			return '';
		}
		return date_i18n( 'j. M Y', strtotime( $date ) );
	}

	/**
	 * Format time range.
	 *
	 * @param string $start Start time.
	 * @param string $end   Optional end time.
	 * @return string Formatted time range.
	 */
	protected function format_time( $start, $end = '' ) {
		if ( ! $start ) {
			return '';
		}

		if ( $end && $end !== $start ) {
			return $start . ' - ' . $end;
		}

		return $start;
	}

	/**
	 * Get active layout set.
	 *
	 * @return string Active layout set name.
	 */
	protected function get_active_layout() {
		return class_exists( 'ES_Layout_Sets' ) ? ES_Layout_Sets::get_active_set() : 'classic';
	}

	/**
	 * Locate and include a template file.
	 *
	 * @param string $template_name Template name (e.g. 'event-card.php').
	 * @param array  $args          Arguments to pass to template.
	 * @param string $layout        Optional specific layout.
	 * @return string|false Template path or false if not found.
	 */
	protected function locate_template( $template_name, $args = array(), $layout = '' ) {
		if ( empty( $layout ) ) {
			$layout = $this->get_active_layout();
		}

		if ( class_exists( 'ES_Template_Loader' ) ) {
			return ES_Template_Loader::locate_template( $template_name, $layout );
		}

		// Fallback: check templates directory.
		$template_path = ENSEMBLE_PLUGIN_DIR . 'templates/layouts/' . $layout . '/' . $template_name;
		if ( file_exists( $template_path ) ) {
			return $template_path;
		}

		return false;
	}

	/**
	 * Render a template file with variables.
	 *
	 * @param string $template_path Full path to template file.
	 * @param array  $args          Variables to extract for template.
	 * @return string Rendered output.
	 */
	protected function render_template( $template_path, $args = array() ) {
		if ( ! file_exists( $template_path ) ) {
			return '';
		}

		ob_start();
		extract( $args, EXTR_SKIP );
		include $template_path;
		return ob_get_clean();
	}

	/**
	 * Build CSS classes from array.
	 *
	 * @param array $classes Array of class names.
	 * @return string Space-separated class string.
	 */
	protected function build_classes( $classes ) {
		return implode( ' ', array_filter( array_map( 'sanitize_html_class', $classes ) ) );
	}

	/**
	 * Get thumbnail URL with fallback.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $size    Image size.
	 * @return string Image URL or placeholder.
	 */
	protected function get_thumbnail_url( $post_id, $size = 'large' ) {
		if ( has_post_thumbnail( $post_id ) ) {
			return get_the_post_thumbnail_url( $post_id, $size );
		}

		// Return placeholder or empty.
		return '';
	}

	/**
	 * Sanitize shortcode attributes.
	 *
	 * @param array $atts     Raw attributes.
	 * @param array $defaults Default values.
	 * @return array Sanitized attributes.
	 */
	protected function parse_atts( $atts, $defaults ) {
		$atts = shortcode_atts( $defaults, $atts );

		// Sanitize common attributes.
		if ( isset( $atts['id'] ) ) {
			$atts['id'] = absint( $atts['id'] );
		}
		if ( isset( $atts['limit'] ) ) {
			$atts['limit'] = absint( $atts['limit'] );
		}
		if ( isset( $atts['columns'] ) ) {
			$atts['columns'] = absint( $atts['columns'] );
		}

		return $atts;
	}

	/**
	 * Clear cached date key (for testing or when settings change).
	 *
	 * @return void
	 */
	public static function clear_cache() {
		self::$date_key_cache = null;
	}
}
