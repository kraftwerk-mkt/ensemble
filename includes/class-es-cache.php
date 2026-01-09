<?php
/**
 * Ensemble Cache System
 *
 * Zentrale Caching-Klasse als Wrapper für WordPress Transients.
 * Bietet gruppen-basiertes Caching, einfache Invalidierung und Debug-Logging.
 *
 * @package Ensemble
 * @since   2.9.0
 * @version 2.9.1
 * 
 * Changes in 2.9.1:
 * - Added wp_trash_post hook for cache invalidation
 * - Added untrash_post hook for cache invalidation  
 * - Added transition_post_status hook for status changes
 * - Added admin bar button for manual cache flush
 * - Centralized invalidation logic
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ES_Cache
 *
 * Statische Utility-Klasse für zentrales Caching.
 *
 * Verwendung:
 *   ES_Cache::get( 'my_key' );
 *   ES_Cache::set( 'my_key', $data, HOUR_IN_SECONDS );
 *   ES_Cache::delete( 'my_key' );
 *   ES_Cache::flush_group( 'events' );
 */
class ES_Cache {

	/**
	 * Cache prefix for all keys
	 *
	 * @var string
	 */
	const PREFIX = 'ensemble_';

	/**
	 * Default cache expiration (1 hour)
	 *
	 * @var int
	 */
	const DEFAULT_EXPIRATION = HOUR_IN_SECONDS;

	/**
	 * Cache groups registry
	 * Stores which keys belong to which group
	 *
	 * @var string
	 */
	const GROUPS_OPTION = 'ensemble_cache_groups';

	/**
	 * Cache statistics for debugging
	 *
	 * @var array
	 */
	private static $stats = array(
		'hits'   => 0,
		'misses' => 0,
		'sets'   => 0,
		'deletes' => 0,
	);

	/**
	 * Get a cached value
	 *
	 * @param string $key     Cache key (without prefix).
	 * @param mixed  $default Default value if not found.
	 * @return mixed Cached value or default.
	 */
	public static function get( $key, $default = null ) {
		$full_key = self::build_key( $key );
		$value = get_transient( $full_key );

		if ( false === $value ) {
			self::$stats['misses']++;
			self::log( 'Cache miss', array( 'key' => $key ) );
			return $default;
		}

		self::$stats['hits']++;
		self::log( 'Cache hit', array( 'key' => $key ) );
		return $value;
	}

	/**
	 * Set a cached value
	 *
	 * @param string $key        Cache key (without prefix).
	 * @param mixed  $value      Value to cache.
	 * @param int    $expiration Expiration time in seconds (default: 1 hour).
	 * @param string $group      Optional cache group for bulk invalidation.
	 * @return bool True if set successfully.
	 */
	public static function set( $key, $value, $expiration = null, $group = '' ) {
		if ( null === $expiration ) {
			$expiration = self::DEFAULT_EXPIRATION;
		}

		$full_key = self::build_key( $key );
		$result = set_transient( $full_key, $value, $expiration );

		if ( $result ) {
			self::$stats['sets']++;
			self::log( 'Cache set', array(
				'key'        => $key,
				'expiration' => $expiration,
				'group'      => $group,
			) );

			// Register in group if specified
			if ( ! empty( $group ) ) {
				self::add_to_group( $key, $group );
			}
		}

		return $result;
	}

	/**
	 * Delete a cached value
	 *
	 * @param string $key Cache key (without prefix).
	 * @return bool True if deleted successfully.
	 */
	public static function delete( $key ) {
		$full_key = self::build_key( $key );
		$result = delete_transient( $full_key );

		if ( $result ) {
			self::$stats['deletes']++;
			self::log( 'Cache delete', array( 'key' => $key ) );
			self::remove_from_groups( $key );
		}

		return $result;
	}

	/**
	 * Check if a key exists in cache
	 *
	 * @param string $key Cache key (without prefix).
	 * @return bool True if exists.
	 */
	public static function exists( $key ) {
		$full_key = self::build_key( $key );
		return false !== get_transient( $full_key );
	}

	/**
	 * Get or set a cached value (convenience method)
	 *
	 * If the key doesn't exist, the callback is executed and the result is cached.
	 *
	 * @param string   $key        Cache key (without prefix).
	 * @param callable $callback   Function to generate value if not cached.
	 * @param int      $expiration Expiration time in seconds.
	 * @param string   $group      Optional cache group.
	 * @return mixed Cached or generated value.
	 */
	public static function remember( $key, $callback, $expiration = null, $group = '' ) {
		$value = self::get( $key );

		if ( null !== $value ) {
			return $value;
		}

		$value = call_user_func( $callback );

		if ( null !== $value ) {
			self::set( $key, $value, $expiration, $group );
		}

		return $value;
	}

	/**
	 * Flush all keys in a group
	 *
	 * @param string $group Group name.
	 * @return int Number of keys deleted.
	 */
	public static function flush_group( $group ) {
		$groups = get_option( self::GROUPS_OPTION, array() );

		if ( empty( $groups[ $group ] ) ) {
			return 0;
		}

		$count = 0;
		foreach ( $groups[ $group ] as $key ) {
			if ( self::delete( $key ) ) {
				$count++;
			}
		}

		// Clear group registry
		unset( $groups[ $group ] );
		update_option( self::GROUPS_OPTION, $groups, false );

		self::log( 'Cache group flushed', array(
			'group' => $group,
			'count' => $count,
		) );

		return $count;
	}

	/**
	 * Flush all Ensemble caches
	 *
	 * @return int Number of keys deleted.
	 */
	public static function flush_all() {
		global $wpdb;

		// Delete all transients with our prefix
		$prefix = '_transient_' . self::PREFIX;
		$timeout_prefix = '_transient_timeout_' . self::PREFIX;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$count = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$prefix . '%',
				$timeout_prefix . '%'
			)
		);

		// Clear groups registry
		delete_option( self::GROUPS_OPTION );

		self::log( 'All caches flushed', array( 'count' => $count ) );

		return $count;
	}

	/**
	 * Get cache statistics
	 *
	 * @return array Statistics array.
	 */
	public static function get_stats() {
		$stats = self::$stats;
		$stats['ratio'] = ( $stats['hits'] + $stats['misses'] ) > 0
			? round( $stats['hits'] / ( $stats['hits'] + $stats['misses'] ) * 100, 1 )
			: 0;
		return $stats;
	}

	/**
	 * Reset statistics
	 *
	 * @return void
	 */
	public static function reset_stats() {
		self::$stats = array(
			'hits'    => 0,
			'misses'  => 0,
			'sets'    => 0,
			'deletes' => 0,
		);
	}

	/*
	|--------------------------------------------------------------------------
	| Predefined Cache Groups & Keys
	|--------------------------------------------------------------------------
	*/

	/**
	 * Cache groups constants
	 */
	const GROUP_EVENTS    = 'events';
	const GROUP_ARTISTS   = 'artists';
	const GROUP_LOCATIONS = 'locations';
	const GROUP_QUERIES   = 'queries';
	const GROUP_META      = 'meta';
	const GROUP_SHORTCODE = 'shortcode';

	/**
	 * Get events list cache
	 *
	 * @param string $context Cache context/identifier.
	 * @return mixed|null Cached events or null.
	 */
	public static function get_events( $context ) {
		return self::get( 'events_' . md5( $context ) );
	}

	/**
	 * Set events list cache
	 *
	 * @param string $context Cache context/identifier.
	 * @param mixed  $events  Events data.
	 * @param int    $expiration Expiration in seconds.
	 * @return bool Success.
	 */
	public static function set_events( $context, $events, $expiration = null ) {
		return self::set( 'events_' . md5( $context ), $events, $expiration, self::GROUP_EVENTS );
	}

	/**
	 * Get artist data cache
	 *
	 * @param int $artist_id Artist post ID.
	 * @return mixed|null Cached data or null.
	 */
	public static function get_artist( $artist_id ) {
		return self::get( 'artist_' . $artist_id );
	}

	/**
	 * Set artist data cache
	 *
	 * @param int   $artist_id Artist post ID.
	 * @param mixed $data      Artist data.
	 * @param int   $expiration Expiration in seconds.
	 * @return bool Success.
	 */
	public static function set_artist( $artist_id, $data, $expiration = null ) {
		return self::set( 'artist_' . $artist_id, $data, $expiration, self::GROUP_ARTISTS );
	}

	/**
	 * Get location data cache
	 *
	 * @param int $location_id Location post ID.
	 * @return mixed|null Cached data or null.
	 */
	public static function get_location( $location_id ) {
		return self::get( 'location_' . $location_id );
	}

	/**
	 * Set location data cache
	 *
	 * @param int   $location_id Location post ID.
	 * @param mixed $data        Location data.
	 * @param int   $expiration  Expiration in seconds.
	 * @return bool Success.
	 */
	public static function set_location( $location_id, $data, $expiration = null ) {
		return self::set( 'location_' . $location_id, $data, $expiration, self::GROUP_LOCATIONS );
	}

	/**
	 * Get meta key cache (for detected meta keys)
	 *
	 * @param string $key Meta key identifier.
	 * @return mixed|null Cached meta key or null.
	 */
	public static function get_meta_key( $key ) {
		return self::get( 'meta_key_' . $key );
	}

	/**
	 * Set meta key cache
	 *
	 * @param string $key   Meta key identifier.
	 * @param mixed  $value Detected meta key value.
	 * @return bool Success.
	 */
	public static function set_meta_key( $key, $value ) {
		// Meta keys are stable, cache for 24 hours
		return self::set( 'meta_key_' . $key, $value, DAY_IN_SECONDS, self::GROUP_META );
	}

	/**
	 * Get shortcode output cache
	 *
	 * @param string $shortcode Shortcode name.
	 * @param array  $atts      Shortcode attributes.
	 * @return string|null Cached HTML or null.
	 */
	public static function get_shortcode( $shortcode, $atts = array() ) {
		$key = self::build_shortcode_key( $shortcode, $atts );
		return self::get( $key );
	}

	/**
	 * Set shortcode output cache
	 *
	 * @param string $shortcode  Shortcode name.
	 * @param array  $atts       Shortcode attributes.
	 * @param string $output     Rendered HTML output.
	 * @param int    $expiration Expiration in seconds.
	 * @return bool Success.
	 */
	public static function set_shortcode( $shortcode, $atts, $output, $expiration = null ) {
		$key = self::build_shortcode_key( $shortcode, $atts );
		return self::set( $key, $output, $expiration, self::GROUP_SHORTCODE );
	}

	/**
	 * Build shortcode cache key
	 *
	 * @param string $shortcode Shortcode name.
	 * @param array  $atts      Attributes.
	 * @return string Cache key.
	 */
	private static function build_shortcode_key( $shortcode, $atts ) {
		// Sort attributes for consistent key
		if ( is_array( $atts ) ) {
			ksort( $atts );
		}
		return 'sc_' . $shortcode . '_' . md5( wp_json_encode( $atts ) );
	}

	/*
	|--------------------------------------------------------------------------
	| Cache Invalidation Helpers
	|--------------------------------------------------------------------------
	*/

	/**
	 * Invalidate caches when an event is saved
	 *
	 * @param int $post_id Event post ID.
	 * @return void
	 */
	public static function invalidate_event( $post_id ) {
		// Log invalidation
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Ensemble Cache: Invalidating event ' . $post_id );
		}
		
		// Flush event-related caches
		self::flush_group( self::GROUP_EVENTS );
		self::flush_group( self::GROUP_SHORTCODE );

		// Get related artist and location
		if ( function_exists( 'ensemble_get_event_meta' ) ) {
			$artist_id = ensemble_get_event_meta( $post_id, 'artist' );
			$location_id = ensemble_get_event_meta( $post_id, 'location' );

			if ( $artist_id ) {
				self::delete( 'artist_' . $artist_id );
				self::delete( 'artist_events_' . $artist_id . '_upcoming' );
				self::delete( 'artist_events_' . $artist_id . '_all' );
			}

			if ( $location_id ) {
				self::delete( 'location_' . $location_id );
				self::delete( 'location_events_' . $location_id . '_upcoming' );
				self::delete( 'location_events_' . $location_id . '_all' );
			}
		}

		self::log( 'Event caches invalidated', array( 'post_id' => $post_id ) );
	}

	/**
	 * Invalidate caches when an artist is saved
	 *
	 * @param int $post_id Artist post ID.
	 * @return void
	 */
	public static function invalidate_artist( $post_id ) {
		// Log invalidation
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Ensemble Cache: Invalidating artist ' . $post_id );
		}
		
		self::delete( 'artist_' . $post_id );
		self::flush_group( self::GROUP_ARTISTS );
		self::flush_group( self::GROUP_SHORTCODE );

		self::log( 'Artist caches invalidated', array( 'post_id' => $post_id ) );
	}

	/**
	 * Invalidate caches when a location is saved
	 *
	 * @param int $post_id Location post ID.
	 * @return void
	 */
	public static function invalidate_location( $post_id ) {
		// Log invalidation
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Ensemble Cache: Invalidating location ' . $post_id );
		}
		
		self::delete( 'location_' . $post_id );
		self::flush_group( self::GROUP_LOCATIONS );
		self::flush_group( self::GROUP_SHORTCODE );

		self::log( 'Location caches invalidated', array( 'post_id' => $post_id ) );
	}

	/*
	|--------------------------------------------------------------------------
	| Internal Helper Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Build full cache key with prefix
	 *
	 * @param string $key Raw key.
	 * @return string Prefixed key.
	 */
	private static function build_key( $key ) {
		return self::PREFIX . $key;
	}

	/**
	 * Add a key to a group
	 *
	 * @param string $key   Cache key.
	 * @param string $group Group name.
	 * @return void
	 */
	private static function add_to_group( $key, $group ) {
		$groups = get_option( self::GROUPS_OPTION, array() );

		if ( ! isset( $groups[ $group ] ) ) {
			$groups[ $group ] = array();
		}

		if ( ! in_array( $key, $groups[ $group ], true ) ) {
			$groups[ $group ][] = $key;
			update_option( self::GROUPS_OPTION, $groups, false );
		}
	}

	/**
	 * Remove a key from all groups
	 *
	 * @param string $key Cache key.
	 * @return void
	 */
	private static function remove_from_groups( $key ) {
		$groups = get_option( self::GROUPS_OPTION, array() );
		$changed = false;

		foreach ( $groups as $group => $keys ) {
			$index = array_search( $key, $keys, true );
			if ( false !== $index ) {
				unset( $groups[ $group ][ $index ] );
				$groups[ $group ] = array_values( $groups[ $group ] );
				$changed = true;
			}
		}

		if ( $changed ) {
			update_option( self::GROUPS_OPTION, $groups, false );
		}
	}

	/**
	 * Log cache operations (debug mode only)
	 *
	 * @param string $message Log message.
	 * @param array  $context Additional context.
	 * @return void
	 */
	private static function log( $message, $context = array() ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'ENSEMBLE_DEBUG_CACHE' ) && ENSEMBLE_DEBUG_CACHE ) {
			if ( function_exists( 'ensemble_log' ) ) {
				ensemble_log( 'Cache: ' . $message, $context, 'debug' );
			}
		}
	}
}

/*
|--------------------------------------------------------------------------
| Cache Invalidation Hooks
|--------------------------------------------------------------------------
*/

/**
 * Hook into save_post to invalidate caches
 */
add_action( 'save_post', 'ensemble_cache_invalidate_on_save', 99, 2 );

/**
 * Invalidate caches when posts are saved
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post object.
 * @return void
 */
function ensemble_cache_invalidate_on_save( $post_id, $post ) {
	// Skip autosaves and revisions
	if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
		return;
	}

	ensemble_cache_invalidate_by_post_type( $post_id );
}

/**
 * Hook into post deletion
 */
add_action( 'before_delete_post', 'ensemble_cache_invalidate_on_delete' );

/**
 * Invalidate caches when posts are deleted
 *
 * @param int $post_id Post ID.
 * @return void
 */
function ensemble_cache_invalidate_on_delete( $post_id ) {
	ensemble_cache_invalidate_by_post_type( $post_id );
}

/**
 * Hook into post trash
 */
add_action( 'wp_trash_post', 'ensemble_cache_invalidate_on_trash' );

/**
 * Invalidate caches when posts are trashed
 *
 * @param int $post_id Post ID.
 * @return void
 */
function ensemble_cache_invalidate_on_trash( $post_id ) {
	ensemble_cache_invalidate_by_post_type( $post_id );
}

/**
 * Hook into post untrash
 */
add_action( 'untrash_post', 'ensemble_cache_invalidate_on_untrash' );

/**
 * Invalidate caches when posts are restored from trash
 *
 * @param int $post_id Post ID.
 * @return void
 */
function ensemble_cache_invalidate_on_untrash( $post_id ) {
	ensemble_cache_invalidate_by_post_type( $post_id );
}

/**
 * Hook into post status transitions
 */
add_action( 'transition_post_status', 'ensemble_cache_invalidate_on_status_change', 10, 3 );

/**
 * Invalidate caches when post status changes
 *
 * @param string  $new_status New post status.
 * @param string  $old_status Old post status.
 * @param WP_Post $post       Post object.
 * @return void
 */
function ensemble_cache_invalidate_on_status_change( $new_status, $old_status, $post ) {
	// Only if status actually changed
	if ( $new_status === $old_status ) {
		return;
	}
	
	// Only for relevant status changes
	$relevant_statuses = array( 'publish', 'draft', 'pending', 'private', 'trash' );
	if ( ! in_array( $new_status, $relevant_statuses, true ) && ! in_array( $old_status, $relevant_statuses, true ) ) {
		return;
	}
	
	ensemble_cache_invalidate_by_post_type( $post->ID );
}

/**
 * Central function to invalidate cache based on post type
 *
 * @param int $post_id Post ID.
 * @return void
 */
function ensemble_cache_invalidate_by_post_type( $post_id ) {
	$post_type = get_post_type( $post_id );
	$event_post_type = function_exists( 'ensemble_get_post_type' ) ? ensemble_get_post_type() : 'post';

	// Log which hook triggered this
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		$current_action = current_action();
		error_log( "Ensemble Cache: Hook '{$current_action}' fired for post {$post_id} (type: {$post_type})" );
	}

	if ( $post_type === $event_post_type ) {
		ES_Cache::invalidate_event( $post_id );
	} elseif ( 'ensemble_artist' === $post_type ) {
		ES_Cache::invalidate_artist( $post_id );
	} elseif ( 'ensemble_location' === $post_type ) {
		ES_Cache::invalidate_location( $post_id );
	}
}

/*
|--------------------------------------------------------------------------
| Global Helper Functions
|--------------------------------------------------------------------------
*/

/**
 * Get cached value or compute and store
 *
 * Shorthand for ES_Cache::remember()
 *
 * @param string   $key        Cache key.
 * @param callable $callback   Function to compute value.
 * @param int      $expiration Expiration in seconds.
 * @param string   $group      Cache group.
 * @return mixed Cached or computed value.
 */
function ensemble_cache_remember( $key, $callback, $expiration = HOUR_IN_SECONDS, $group = '' ) {
	return ES_Cache::remember( $key, $callback, $expiration, $group );
}

/**
 * Clear all Ensemble caches
 *
 * @return int Number of caches cleared.
 */
function ensemble_cache_flush() {
	return ES_Cache::flush_all();
}

/*
|--------------------------------------------------------------------------
| Admin Cache Management
|--------------------------------------------------------------------------
*/

/**
 * Add cache flush button to admin bar
 */
add_action( 'admin_bar_menu', 'ensemble_add_cache_flush_button', 999 );

/**
 * Add cache flush button to admin bar
 *
 * @param WP_Admin_Bar $admin_bar Admin bar instance.
 * @return void
 */
function ensemble_add_cache_flush_button( $admin_bar ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	
	$admin_bar->add_node( array(
		'id'    => 'ensemble-flush-cache',
		'title' => '<span class="ab-icon dashicons dashicons-database-remove" style="font-family: dashicons; font-size: 20px; margin-top: 2px;"></span> ' . __( 'Flush Ensemble Cache', 'ensemble' ),
		'href'  => wp_nonce_url( admin_url( 'admin-post.php?action=ensemble_flush_cache' ), 'ensemble_flush_cache' ),
		'meta'  => array(
			'title' => __( 'Clear all Ensemble caches', 'ensemble' ),
		),
	) );
}

/**
 * Handle cache flush action
 */
add_action( 'admin_post_ensemble_flush_cache', 'ensemble_handle_cache_flush' );

/**
 * Process cache flush request
 *
 * @return void
 */
function ensemble_handle_cache_flush() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( __( 'Permission denied', 'ensemble' ) );
	}
	
	if ( ! wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'ensemble_flush_cache' ) ) {
		wp_die( __( 'Security check failed', 'ensemble' ) );
	}
	
	$count = ES_Cache::flush_all();
	
	// Also flush shortcode cache
	if ( class_exists( 'ES_Shortcode_Cache' ) ) {
		ES_Shortcode_Cache::flush_all();
	}
	
	// Add admin notice
	set_transient( 'ensemble_cache_flushed', $count, 30 );
	
	// Redirect back
	$redirect = wp_get_referer() ? wp_get_referer() : admin_url();
	wp_safe_redirect( $redirect );
	exit;
}

/**
 * Show cache flush notice
 */
add_action( 'admin_notices', 'ensemble_show_cache_flush_notice' );

/**
 * Display cache flush success notice
 *
 * @return void
 */
function ensemble_show_cache_flush_notice() {
	$count = get_transient( 'ensemble_cache_flushed' );
	
	if ( false !== $count ) {
		delete_transient( 'ensemble_cache_flushed' );
		printf(
			'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
			sprintf( __( 'Ensemble cache cleared successfully. %d items removed.', 'ensemble' ), intval( $count ) )
		);
	}
}
