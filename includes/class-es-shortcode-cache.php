<?php
/**
 * Ensemble Shortcode Cache
 *
 * Optionales Caching für Shortcode-Output.
 * Kann per Shortcode-Attribut oder global aktiviert werden.
 *
 * @package Ensemble
 * @since   2.9.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ES_Shortcode_Cache
 *
 * Wrapper für gecachte Shortcode-Ausgabe.
 */
class ES_Shortcode_Cache {

	/**
	 * Singleton instance
	 *
	 * @var ES_Shortcode_Cache
	 */
	private static $instance = null;

	/**
	 * Cache-fähige Shortcodes mit Standard-TTL
	 *
	 * @var array
	 */
	private $cacheable_shortcodes = array(
		'ensemble_upcoming_events' => 300,   // 5 Minuten (ändert sich täglich)
		'ensemble_events_grid'     => 300,   // 5 Minuten
		'ensemble_featured_events' => 300,   // 5 Minuten
		'ensemble_artists'         => 3600,  // 1 Stunde (ändert sich selten)
		'ensemble_locations'       => 3600,  // 1 Stunde
		'ensemble_lineup'          => 600,   // 10 Minuten
		'ensemble_calendar'        => 300,   // 5 Minuten
	);

	/**
	 * Ob Caching global aktiviert ist
	 *
	 * @var bool
	 */
	private $enabled = true;

	/**
	 * Get singleton instance
	 *
	 * @return ES_Shortcode_Cache
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		// Caching deaktivieren für eingeloggte Admins (Vorschau-Modus)
		if ( is_admin() || ( is_user_logged_in() && current_user_can( 'edit_posts' ) ) ) {
			$this->enabled = false;
		}

		// Caching deaktivieren per Konstante
		if ( defined( 'ENSEMBLE_DISABLE_SHORTCODE_CACHE' ) && ENSEMBLE_DISABLE_SHORTCODE_CACHE ) {
			$this->enabled = false;
		}

		// Filter für Shortcode-Output registrieren
		if ( $this->enabled ) {
			$this->register_filters();
		}
	}

	/**
	 * Register pre/post filters für Shortcodes
	 */
	private function register_filters() {
		// Pre-Filter: Versuche gecachten Output zu laden
		add_filter( 'pre_do_shortcode_tag', array( $this, 'maybe_serve_cached' ), 10, 4 );

		// Post-Filter: Cache den Output
		add_filter( 'do_shortcode_tag', array( $this, 'maybe_cache_output' ), 10, 4 );
	}

	/**
	 * Versuche gecachten Output zu servieren
	 *
	 * @param mixed  $output   Current output (false to continue).
	 * @param string $tag      Shortcode tag.
	 * @param array  $atts     Shortcode attributes.
	 * @param array  $m        Regex match array.
	 * @return mixed Cached output or false to continue.
	 */
	public function maybe_serve_cached( $output, $tag, $atts, $m ) {
		// Nur für unsere Shortcodes
		if ( ! $this->is_cacheable( $tag, $atts ) ) {
			return $output;
		}

		// Cache-Key generieren
		$cache_key = $this->generate_cache_key( $tag, $atts );

		// Versuche aus Cache zu laden
		if ( class_exists( 'ES_Cache' ) ) {
			$cached = ES_Cache::get( $cache_key );
			if ( null !== $cached && is_string( $cached ) ) {
				// Cache-Hit - HTML-Kommentar für Debug
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					$cached = "<!-- ES Cache Hit: {$tag} -->\n" . $cached;
				}
				return $cached;
			}
		}

		return $output;
	}

	/**
	 * Cache den Shortcode-Output
	 *
	 * @param string $output   Shortcode output.
	 * @param string $tag      Shortcode tag.
	 * @param array  $atts     Shortcode attributes.
	 * @param array  $m        Regex match array.
	 * @return string Original output (unverändert).
	 */
	public function maybe_cache_output( $output, $tag, $atts, $m ) {
		// Nur für unsere Shortcodes
		if ( ! $this->is_cacheable( $tag, $atts ) ) {
			return $output;
		}

		// Leere Outputs nicht cachen
		if ( empty( $output ) ) {
			return $output;
		}

		// TTL bestimmen
		$ttl = $this->get_ttl( $tag, $atts );

		// Cache-Key generieren
		$cache_key = $this->generate_cache_key( $tag, $atts );

		// In Cache speichern
		if ( class_exists( 'ES_Cache' ) ) {
			ES_Cache::set( $cache_key, $output, $ttl, ES_Cache::GROUP_SHORTCODE );

			// Debug-Kommentar
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				$output = "<!-- ES Cache Set: {$tag} (TTL: {$ttl}s) -->\n" . $output;
			}
		}

		return $output;
	}

	/**
	 * Prüfe ob Shortcode cache-fähig ist
	 *
	 * @param string $tag  Shortcode tag.
	 * @param array  $atts Attributes.
	 * @return bool
	 */
	private function is_cacheable( $tag, $atts ) {
		// Muss ein Ensemble Shortcode sein
		if ( strpos( $tag, 'ensemble_' ) !== 0 ) {
			return false;
		}

		// Explizit deaktiviert per Attribut?
		if ( isset( $atts['cache'] ) && in_array( $atts['cache'], array( 'false', '0', 'no' ), true ) ) {
			return false;
		}

		// Explizit aktiviert per Attribut? (überschreibt alles)
		if ( isset( $atts['cache'] ) && in_array( $atts['cache'], array( 'true', '1', 'yes' ), true ) ) {
			return true;
		}

		// Prüfe ob in der Liste der cache-fähigen Shortcodes
		return isset( $this->cacheable_shortcodes[ $tag ] );
	}

	/**
	 * Bestimme TTL für Shortcode
	 *
	 * @param string $tag  Shortcode tag.
	 * @param array  $atts Attributes.
	 * @return int TTL in Sekunden.
	 */
	private function get_ttl( $tag, $atts ) {
		// Expliziter TTL per Attribut?
		if ( isset( $atts['cache_ttl'] ) ) {
			return absint( $atts['cache_ttl'] );
		}

		// Standard-TTL für diesen Shortcode
		if ( isset( $this->cacheable_shortcodes[ $tag ] ) ) {
			return $this->cacheable_shortcodes[ $tag ];
		}

		// Fallback: 5 Minuten
		return 300;
	}

	/**
	 * Generiere eindeutigen Cache-Key
	 *
	 * @param string $tag  Shortcode tag.
	 * @param array  $atts Attributes.
	 * @return string Cache key.
	 */
	private function generate_cache_key( $tag, $atts ) {
		// Sortiere Attribute für konsistenten Key
		if ( is_array( $atts ) ) {
			ksort( $atts );
		} else {
			$atts = array();
		}

		// Entferne cache-spezifische Attribute aus dem Key
		unset( $atts['cache'], $atts['cache_ttl'] );

		// Füge Kontext hinzu der den Output beeinflusst
		$context = array(
			'tag'       => $tag,
			'atts'      => $atts,
			'post_id'   => get_the_ID(),
			'date'      => date( 'Y-m-d' ), // Tägliche Invalidierung für Datums-abhängige Shortcodes
			'lang'      => get_locale(),
		);

		return 'sc_' . md5( wp_json_encode( $context ) );
	}

	/**
	 * Füge Shortcode zur Cache-Liste hinzu
	 *
	 * @param string $tag Shortcode tag.
	 * @param int    $ttl TTL in Sekunden.
	 */
	public function add_cacheable_shortcode( $tag, $ttl = 300 ) {
		$this->cacheable_shortcodes[ $tag ] = $ttl;
	}

	/**
	 * Entferne Shortcode aus Cache-Liste
	 *
	 * @param string $tag Shortcode tag.
	 */
	public function remove_cacheable_shortcode( $tag ) {
		unset( $this->cacheable_shortcodes[ $tag ] );
	}

	/**
	 * Cache aktivieren/deaktivieren
	 *
	 * @param bool $enabled Aktiviert?
	 */
	public function set_enabled( $enabled ) {
		$this->enabled = (bool) $enabled;
	}

	/**
	 * Prüfe ob Cache aktiviert
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return $this->enabled;
	}

	/**
	 * Flush alle Shortcode-Caches
	 *
	 * @return int Anzahl gelöschter Caches.
	 */
	public static function flush_all() {
		if ( class_exists( 'ES_Cache' ) ) {
			return ES_Cache::flush_group( ES_Cache::GROUP_SHORTCODE );
		}
		return 0;
	}
}

// Initialisiere Shortcode Cache
add_action( 'init', function() {
	ES_Shortcode_Cache::instance();
}, 5 );

/**
 * Helper: Flush Shortcode Cache
 *
 * @return int Anzahl gelöschter Caches.
 */
function ensemble_flush_shortcode_cache() {
	return ES_Shortcode_Cache::flush_all();
}
