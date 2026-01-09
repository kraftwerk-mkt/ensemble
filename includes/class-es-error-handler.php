<?php
/**
 * Ensemble Error Handler
 *
 * Zentrale Klasse für einheitliches Error Handling, Logging und Admin Notices.
 *
 * @package Ensemble
 * @since   2.9.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ES_Error_Handler
 *
 * Singleton-Klasse für zentrales Error Handling.
 *
 * Verwendung:
 *   ensemble_error()->debug( 'Message', array( 'context' => 'data' ) );
 *   ensemble_error()->ajax_error( 'Fehler', 400 );
 *   ensemble_error()->notice_success( 'Gespeichert!' );
 */
class ES_Error_Handler {

	/**
	 * Singleton instance
	 *
	 * @var ES_Error_Handler|null
	 */
	private static $instance = null;

	/**
	 * Log levels
	 */
	const LEVEL_DEBUG   = 'debug';
	const LEVEL_INFO    = 'info';
	const LEVEL_WARNING = 'warning';
	const LEVEL_ERROR   = 'error';

	/**
	 * Notice types
	 */
	const NOTICE_SUCCESS = 'success';
	const NOTICE_ERROR   = 'error';
	const NOTICE_WARNING = 'warning';
	const NOTICE_INFO    = 'info';

	/**
	 * Transient key for admin notices
	 *
	 * @var string
	 */
	private $notices_transient = 'ensemble_admin_notices';

	/**
	 * Get singleton instance
	 *
	 * @return ES_Error_Handler
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor for singleton
	 */
	private function __construct() {
		add_action( 'admin_notices', array( $this, 'display_admin_notices' ) );
		add_action( 'shutdown', array( $this, 'maybe_log_shutdown_errors' ) );
	}

	/**
	 * Prevent cloning
	 */
	private function __clone() {}

	/**
	 * Prevent unserialization
	 *
	 * @throws Exception Always throws exception.
	 */
	public function __wakeup() {
		throw new Exception( 'Cannot unserialize singleton' );
	}

	/*
	|--------------------------------------------------------------------------
	| Logging Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Log debug message (only when WP_DEBUG is enabled)
	 *
	 * @param string $message Log message.
	 * @param array  $context Additional context data.
	 * @return void
	 */
	public function debug( $message, $context = array() ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$this->log( $message, $context, self::LEVEL_DEBUG );
		}
	}

	/**
	 * Log info message
	 *
	 * @param string $message Log message.
	 * @param array  $context Additional context data.
	 * @return void
	 */
	public function info( $message, $context = array() ) {
		$this->log( $message, $context, self::LEVEL_INFO );
	}

	/**
	 * Log warning message
	 *
	 * @param string $message Log message.
	 * @param array  $context Additional context data.
	 * @return void
	 */
	public function warning( $message, $context = array() ) {
		$this->log( $message, $context, self::LEVEL_WARNING );
	}

	/**
	 * Log error message
	 *
	 * @param string $message Log message.
	 * @param array  $context Additional context data.
	 * @return void
	 */
	public function error( $message, $context = array() ) {
		$this->log( $message, $context, self::LEVEL_ERROR );
	}

	/**
	 * Core logging method
	 *
	 * @param string $message Log message.
	 * @param array  $context Additional context data.
	 * @param string $level   Log level.
	 * @return void
	 */
	private function log( $message, $context = array(), $level = self::LEVEL_INFO ) {
		// Only log if WP_DEBUG_LOG is enabled.
		if ( ! defined( 'WP_DEBUG_LOG' ) || ! WP_DEBUG_LOG ) {
			return;
		}

		$timestamp = current_time( 'Y-m-d H:i:s' );
		$level_upper = strtoupper( $level );

		// Format message.
		$log_message = sprintf(
			'[%s] ENSEMBLE [%s]: %s',
			$timestamp,
			$level_upper,
			$message
		);

		// Add context if provided.
		if ( ! empty( $context ) ) {
			$log_message .= ' | Context: ' . wp_json_encode( $context, JSON_UNESCAPED_UNICODE );
		}

		// Add backtrace for errors.
		if ( self::LEVEL_ERROR === $level && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$backtrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 5 );
			// Remove first two entries (this method and the public error method).
			array_shift( $backtrace );
			array_shift( $backtrace );

			if ( ! empty( $backtrace[0] ) ) {
				$caller = $backtrace[0];
				$log_message .= sprintf(
					' | Called from: %s:%d',
					isset( $caller['file'] ) ? basename( $caller['file'] ) : 'unknown',
					isset( $caller['line'] ) ? $caller['line'] : 0
				);
			}
		}

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( $log_message );
	}

	/*
	|--------------------------------------------------------------------------
	| AJAX Response Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Send standardized AJAX error response
	 *
	 * @param string $message Error message.
	 * @param int    $code    HTTP status code (default 400).
	 * @param array  $data    Additional data to include.
	 * @return void Exits script.
	 */
	public function ajax_error( $message, $code = 400, $data = array() ) {
		$response = array(
			'message' => $message,
			'code'    => $code,
		);

		if ( ! empty( $data ) ) {
			$response = array_merge( $response, $data );
		}

		// Log AJAX errors in debug mode.
		$this->debug( 'AJAX Error: ' . $message, array(
			'code' => $code,
			'data' => $data,
		) );

		wp_send_json_error( $response, $code );
	}

	/**
	 * Send standardized AJAX success response
	 *
	 * @param string $message Success message.
	 * @param array  $data    Additional data to include.
	 * @return void Exits script.
	 */
	public function ajax_success( $message = '', $data = array() ) {
		$response = array();

		if ( ! empty( $message ) ) {
			$response['message'] = $message;
		}

		if ( ! empty( $data ) ) {
			$response = array_merge( $response, $data );
		}

		wp_send_json_success( $response );
	}

	/**
	 * Verify AJAX nonce and capability
	 *
	 * @param string $nonce_action The nonce action name.
	 * @param string $capability   Required capability (default 'edit_posts').
	 * @param string $nonce_field  POST field containing nonce (default 'nonce').
	 * @return bool True if valid, sends error response if not.
	 */
	public function verify_ajax_request( $nonce_action = 'ensemble_ajax_nonce', $capability = 'edit_posts', $nonce_field = 'nonce' ) {
		// Check nonce.
		$nonce = isset( $_POST[ $nonce_field ] ) ? sanitize_text_field( wp_unslash( $_POST[ $nonce_field ] ) ) : '';

		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $nonce_action ) ) {
			$this->ajax_error(
				__( 'Sicherheitsüberprüfung fehlgeschlagen. Bitte Seite neu laden.', 'ensemble' ),
				403
			);
			return false; // Never reached, but for clarity.
		}

		// Check capability.
		if ( ! empty( $capability ) && ! current_user_can( $capability ) ) {
			$this->ajax_error(
				__( 'Sie haben keine Berechtigung für diese Aktion.', 'ensemble' ),
				403
			);
			return false; // Never reached, but for clarity.
		}

		return true;
	}

	/*
	|--------------------------------------------------------------------------
	| Admin Notice Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Add success notice
	 *
	 * @param string $message Notice message.
	 * @return void
	 */
	public function notice_success( $message ) {
		$this->add_notice( $message, self::NOTICE_SUCCESS );
	}

	/**
	 * Add error notice
	 *
	 * @param string $message Notice message.
	 * @return void
	 */
	public function notice_error( $message ) {
		$this->add_notice( $message, self::NOTICE_ERROR );
	}

	/**
	 * Add warning notice
	 *
	 * @param string $message Notice message.
	 * @return void
	 */
	public function notice_warning( $message ) {
		$this->add_notice( $message, self::NOTICE_WARNING );
	}

	/**
	 * Add info notice
	 *
	 * @param string $message Notice message.
	 * @return void
	 */
	public function notice_info( $message ) {
		$this->add_notice( $message, self::NOTICE_INFO );
	}

	/**
	 * Add notice to transient queue
	 *
	 * @param string $message Notice message.
	 * @param string $type    Notice type.
	 * @return void
	 */
	private function add_notice( $message, $type = self::NOTICE_INFO ) {
		$notices = get_transient( $this->notices_transient );

		if ( ! is_array( $notices ) ) {
			$notices = array();
		}

		$notices[] = array(
			'message' => $message,
			'type'    => $type,
		);

		set_transient( $this->notices_transient, $notices, 60 );
	}

	/**
	 * Display and clear admin notices
	 *
	 * @return void
	 */
	public function display_admin_notices() {
		$notices = get_transient( $this->notices_transient );

		if ( empty( $notices ) || ! is_array( $notices ) ) {
			return;
		}

		foreach ( $notices as $notice ) {
			$type    = isset( $notice['type'] ) ? $notice['type'] : self::NOTICE_INFO;
			$message = isset( $notice['message'] ) ? $notice['message'] : '';

			if ( empty( $message ) ) {
				continue;
			}

			// Map notice type to WordPress class.
			$class_map = array(
				self::NOTICE_SUCCESS => 'notice-success',
				self::NOTICE_ERROR   => 'notice-error',
				self::NOTICE_WARNING => 'notice-warning',
				self::NOTICE_INFO    => 'notice-info',
			);

			$notice_class = isset( $class_map[ $type ] ) ? $class_map[ $type ] : 'notice-info';

			printf(
				'<div class="notice %s is-dismissible"><p><strong>Ensemble:</strong> %s</p></div>',
				esc_attr( $notice_class ),
				esc_html( $message )
			);
		}

		// Clear notices after display.
		delete_transient( $this->notices_transient );
	}

	/*
	|--------------------------------------------------------------------------
	| Exception Handling
	|--------------------------------------------------------------------------
	*/

	/**
	 * Handle exception with logging and optional fallback
	 *
	 * @param Exception|Throwable $exception The exception to handle.
	 * @param mixed               $fallback  Fallback value to return.
	 * @return mixed The fallback value.
	 */
	public function handle_exception( $exception, $fallback = null ) {
		$this->error(
			$exception->getMessage(),
			array(
				'file'  => basename( $exception->getFile() ),
				'line'  => $exception->getLine(),
				'trace' => $this->get_simplified_trace( $exception ),
			)
		);

		return $fallback;
	}

	/**
	 * Get simplified stack trace
	 *
	 * @param Exception|Throwable $exception The exception.
	 * @return array Simplified trace.
	 */
	private function get_simplified_trace( $exception ) {
		$trace = array();
		$full_trace = $exception->getTrace();

		// Only get first 5 entries.
		$limited_trace = array_slice( $full_trace, 0, 5 );

		foreach ( $limited_trace as $entry ) {
			$trace[] = sprintf(
				'%s:%d %s%s%s()',
				isset( $entry['file'] ) ? basename( $entry['file'] ) : 'unknown',
				isset( $entry['line'] ) ? $entry['line'] : 0,
				isset( $entry['class'] ) ? $entry['class'] : '',
				isset( $entry['type'] ) ? $entry['type'] : '',
				isset( $entry['function'] ) ? $entry['function'] : 'unknown'
			);
		}

		return $trace;
	}

	/**
	 * Log fatal errors on shutdown
	 *
	 * @return void
	 */
	public function maybe_log_shutdown_errors() {
		$error = error_get_last();

		if ( null === $error ) {
			return;
		}

		// Only log fatal errors related to Ensemble.
		$fatal_types = array( E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR );

		if ( ! in_array( $error['type'], $fatal_types, true ) ) {
			return;
		}

		// Check if error is in Ensemble files.
		if ( false === strpos( $error['file'], 'ensemble' ) ) {
			return;
		}

		$this->error(
			'Fatal Error: ' . $error['message'],
			array(
				'file' => basename( $error['file'] ),
				'line' => $error['line'],
				'type' => $error['type'],
			)
		);
	}

	/*
	|--------------------------------------------------------------------------
	| Utility Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Check if debug mode is enabled
	 *
	 * @return bool
	 */
	public function is_debug_mode() {
		return defined( 'WP_DEBUG' ) && WP_DEBUG;
	}

	/**
	 * Get formatted error for display
	 *
	 * @param string $message Error message.
	 * @param string $context Optional context.
	 * @return string Formatted error message.
	 */
	public function format_error( $message, $context = '' ) {
		$formatted = __( 'Ensemble Fehler', 'ensemble' ) . ': ' . $message;

		if ( ! empty( $context ) && $this->is_debug_mode() ) {
			$formatted .= ' (' . $context . ')';
		}

		return $formatted;
	}
}

/*
|--------------------------------------------------------------------------
| Global Helper Functions
|--------------------------------------------------------------------------
*/

/**
 * Get Error Handler instance
 *
 * @return ES_Error_Handler
 */
function ensemble_error() {
	return ES_Error_Handler::get_instance();
}

/**
 * Shorthand for logging
 *
 * @param string $message Log message.
 * @param array  $context Additional context.
 * @param string $level   Log level (debug, info, warning, error).
 * @return void
 */
function ensemble_log( $message, $context = array(), $level = 'debug' ) {
	$handler = ensemble_error();

	switch ( $level ) {
		case 'info':
			$handler->info( $message, $context );
			break;
		case 'warning':
			$handler->warning( $message, $context );
			break;
		case 'error':
			$handler->error( $message, $context );
			break;
		case 'debug':
		default:
			$handler->debug( $message, $context );
			break;
	}
}
