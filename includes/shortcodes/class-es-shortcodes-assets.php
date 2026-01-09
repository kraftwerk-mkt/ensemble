<?php
/**
 * Ensemble Shortcodes Assets
 *
 * Handles enqueuing of styles and scripts for shortcodes.
 *
 * @package Ensemble
 * @subpackage Shortcodes
 * @since 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shortcodes Assets class.
 *
 * @since 3.0.0
 */
class ES_Shortcodes_Assets {

	/**
	 * Initialize assets hooks.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
	}

	/**
	 * Enqueue frontend styles.
	 *
	 * @return void
	 */
	public function enqueue_styles() {
		// Only enqueue if shortcode is used.
		global $post;

		$shortcodes = array(
			'ensemble_event',
			'ensemble_artists',
			'ensemble_artist',
			'ensemble_locations',
			'ensemble_location',
			'ensemble_upcoming_events',
			'ensemble_lineup',
			'ensemble_featured_events',
			'ensemble_events_grid',
			'ensemble_events',
			'ensemble_calendar',
			'ensemble_gallery',
			'ensemble_layout_switcher',
			'ensemble_demo',
			'ensemble_preview_events',
		);

		$has_shortcode = false;

		if ( is_a( $post, 'WP_Post' ) ) {
			foreach ( $shortcodes as $shortcode ) {
				if ( has_shortcode( $post->post_content, $shortcode ) ) {
					$has_shortcode = true;
					break;
				}
			}
		}

		if ( $has_shortcode ) {
			wp_enqueue_style(
				'ensemble-shortcodes',
				plugins_url( 'assets/css/shortcodes.css', dirname( dirname( __FILE__ ) ) ),
				array(),
				ENSEMBLE_VERSION
			);

			// Load gallery CSS if gallery shortcode is used.
			if ( has_shortcode( $post->post_content, 'ensemble_gallery' ) ) {
				wp_enqueue_style(
					'ensemble-gallery',
					plugins_url( 'assets/css/gallery.css', dirname( dirname( __FILE__ ) ) ),
					array( 'ensemble-shortcodes' ),
					ENSEMBLE_VERSION
				);

				wp_enqueue_script(
					'ensemble-gallery-lightbox',
					plugins_url( 'assets/js/gallery-lightbox.js', dirname( dirname( __FILE__ ) ) ),
					array(),
					ENSEMBLE_VERSION,
					true
				);
			}

			// Load layout-specific CSS based on active layout set.
			$this->enqueue_layout_styles();
		}
	}

	/**
	 * Enqueue layout-specific styles based on active layout set.
	 *
	 * @return void
	 */
	private function enqueue_layout_styles() {
		// Get active layout (considers URL parameter).
		$active_layout = class_exists( 'ES_Layout_Sets' ) ? ES_Layout_Sets::get_active_set() : 'classic';

		// Check if layout has a style.css file.
		$layout_css_path = ENSEMBLE_PLUGIN_DIR . 'templates/layouts/' . $active_layout . '/style.css';
		$layout_css_url  = ENSEMBLE_PLUGIN_URL . 'templates/layouts/' . $active_layout . '/style.css';

		if ( file_exists( $layout_css_path ) ) {
			wp_enqueue_style(
				'ensemble-layout-' . $active_layout,
				$layout_css_url,
				array( 'ensemble-shortcodes' ),
				ENSEMBLE_VERSION
			);
		}

		// Also load base CSS if it exists.
		$base_css_path = ENSEMBLE_PLUGIN_DIR . 'assets/css/layouts/ensemble-base.css';
		if ( file_exists( $base_css_path ) ) {
			wp_enqueue_style(
				'ensemble-base',
				ENSEMBLE_PLUGIN_URL . 'assets/css/layouts/ensemble-base.css',
				array(),
				ENSEMBLE_VERSION
			);
		}

		// Pure layout: Add mode toggle script.
		if ( 'pure' === $active_layout ) {
			add_action( 'wp_footer', array( $this, 'output_pure_mode_script' ), 99 );
		}
	}

	/**
	 * Output Pure mode toggle script (for grid/list pages).
	 *
	 * @return void
	 */
	public function output_pure_mode_script() {
		static $output = false;
		if ( $output ) {
			return;
		}
		if ( defined( 'ES_PURE_MODE_SCRIPT_LOADED' ) ) {
			return;
		}
		$output = true;
		?>
		<script id="es-pure-mode-script">
		(function(){var k='ensemble_pure_mode';function g(){try{return localStorage.getItem(k)||'light'}catch(e){return'light'}}function s(m){try{localStorage.setItem(k,m)}catch(e){}}function a(m){document.body.classList.remove('es-mode-light','es-mode-dark');document.body.classList.add('es-mode-'+m);document.querySelectorAll('.es-layout-pure,.es-pure-single-event,.es-pure-single-artist,.es-pure-single-location,.ensemble-events-grid-wrapper.es-layout-pure,.ensemble-artists-wrapper.es-layout-pure,.ensemble-locations-wrapper.es-layout-pure').forEach(function(el){el.classList.remove('es-mode-light','es-mode-dark');el.classList.add('es-mode-'+m)});document.querySelectorAll('.es-mode-toggle').forEach(function(t){var sun=t.querySelector('.es-icon-sun'),moon=t.querySelector('.es-icon-moon');if(sun&&moon){sun.style.display=m==='dark'?'block':'none';moon.style.display=m==='dark'?'none':'block'}})}function t(){var c=g(),n=c==='dark'?'light':'dark';s(n);a(n)}function c(){var b=document.createElement('button');b.className='es-mode-toggle';b.innerHTML='<svg class="es-icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg><svg class="es-icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>';b.onclick=t;return b}function i(){if(!document.querySelector('.es-layout-pure,.es-pure-single-event,.es-pure-single-artist,.es-pure-single-location'))return;a(g());if(!document.querySelector('.es-mode-toggle'))document.body.appendChild(c())}document.documentElement.classList.add('es-mode-'+g());if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',i);else i();window.togglePureMode=t})();
		</script>
		<?php
	}
}
