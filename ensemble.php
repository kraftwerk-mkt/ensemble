<?php
/**
 * Plugin Name: Ensemble - Event Management Studio
 * Plugin URI: https://kraftwerk-mkt.com
 * Description: Professional event management with intelligent matching, calendar integration, and comprehensive admin tools. Built for clubs, festival organizers, and event professionals.
 * Version: 2.9.1
 * Author: Fabian
 * Author URI: https://kraftwerk-mkt.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ensemble
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * 
 * @package Ensemble
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ENSEMBLE_VERSION', '2.9.5');
define('ENSEMBLE_PLUGIN_FILE', __FILE__);
define('ENSEMBLE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ENSEMBLE_PLUGIN_PATH', plugin_dir_path(__FILE__)); // Alias for templates
define('ENSEMBLE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ENSEMBLE_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Ensemble Plugin Class
 */
final class ES_Plugin {
    
    /**
     * Plugin instance
     * @var ES_Plugin
     */
    private static $instance = null;
    
    /**
     * Loader instance
     * @var ES_Loader
     */
    public $loader;
    
    /**
     * Get plugin instance
     * @return ES_Plugin
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->define_hooks();
        
        // Load translations at init
        add_action('init', array($this, 'load_plugin_textdomain'));
        
        // Check ACF dependency
        add_action('admin_init', array($this, 'check_acf_dependency'));
        
        $this->loader->run();
    }
    
    /**
     * Load plugin text domain
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'ensemble',
            false,
            dirname(ENSEMBLE_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    /**
     * Check if ACF is active and show admin notice if not
     */
    public function check_acf_dependency() {
        if (!function_exists('acf')) {
            add_action('admin_notices', array($this, 'acf_missing_notice'));
        }
    }
    
    /**
     * Display ACF missing notice
     */
    public function acf_missing_notice() {
        $screen = get_current_screen();
        
        // Show on all Ensemble pages
        if ($screen && strpos($screen->id, 'ensemble') !== false) {
            ?>
            <div class="notice notice-error is-dismissible">
                <h2 style="margin-top: 10px;">⚠️ <?php _e('Advanced Custom Fields (ACF) Required', 'ensemble'); ?></h2>
                <p>
                    <strong><?php _e('The Ensemble Event Management Plugin requires Advanced Custom Fields (ACF) to be installed and activated.', 'ensemble'); ?></strong>
                </p>
                <p>
                    <?php _e('Without ACF, event data (dates, times, locations, artists, etc.) cannot be saved or displayed correctly.', 'ensemble'); ?>
                </p>
                <p>
                    <a href="<?php echo admin_url('plugin-install.php?s=advanced+custom+fields&tab=search&type=term'); ?>" 
                       class="button button-primary">
                        <?php _e('Install ACF Now', 'ensemble'); ?>
                    </a>
                    <a href="https://wordpress.org/plugins/advanced-custom-fields/" 
                       class="button button-secondary" 
                       target="_blank">
                        <?php _e('Learn More', 'ensemble'); ?>
                    </a>
                </p>
            </div>
            <?php
        }
    }
    
    /**
     * Load required dependencies
     */
    private function load_dependencies() {
        // ✅ NEW: Slider Component (v2.8.0)
        require_once ENSEMBLE_PLUGIN_DIR . 'includes/class-es-slider-renderer.php';

        // Helper Functions - ZUERST laden!
        require_once ENSEMBLE_PLUGIN_DIR . 'includes/ensemble-helpers.php';
        require_once ENSEMBLE_PLUGIN_DIR . 'includes/class-es-tooltip-helper.php';
        require_once ENSEMBLE_PLUGIN_DIR . 'includes/class-es-icons.php';
        require_once ENSEMBLE_PLUGIN_DIR . 'includes/class-es-error-handler.php';
        require_once ENSEMBLE_PLUGIN_DIR . 'includes/class-es-cache.php';
        require_once ENSEMBLE_PLUGIN_DIR . 'includes/class-es-meta-keys.php';
        require_once ENSEMBLE_PLUGIN_DIR . 'includes/class-es-shortcode-cache.php';
        
        // ✅ Template Hooks System (v3.0) - For Add-on Integration
        require_once ENSEMBLE_PLUGIN_DIR . 'includes/ensemble-template-hooks.php';
        require_once ENSEMBLE_PLUGIN_DIR . 'includes/ensemble-template-data.php';
        require_once ENSEMBLE_PLUGIN_DIR . 'includes/ensemble-agenda-helpers.php';
        
        // Core classes
        require_once ENSEMBLE_PLUGIN_DIR . 'includes/class-es-loader.php';
        require_once ENSEMBLE_PLUGIN_DIR . 'includes/class-es-post-types.php';
        require_once ENSEMBLE_PLUGIN_DIR . 'includes/class-es-assets.php';
        require_once ENSEMBLE_PLUGIN_DIR . 'includes/class-es-admin-menu.php';
        require_once ENSEMBLE_PLUGIN_DIR . 'includes/class-es-acf-installer.php';
        require_once ENSEMBLE_PLUGIN_DIR . 'includes/class-es-wizard.php';
        require_once ENSEMBLE_PLUGIN_DIR . 'includes/class-es-ajax-handler.php';
        require_once ENSEMBLE_PLUGIN_DIR . 'includes/class-es-calendar.php';
        require_once ENSEMBLE_PLUGIN_DIR . 'includes/class-es-artist-manager.php';
        require_once ENSEMBLE_PLUGIN_DIR . 'includes/class-es-location-manager.php';
        require_once ENSEMBLE_PLUGIN_DIR . 'includes/class-es-gallery-manager.php';
        require_once ENSEMBLE_PLUGIN_DIR . 'includes/class-es-gallery-ajax.php';
        
        // ✅ NEW: Meta Scanner for native field detection
        require_once ENSEMBLE_PLUGIN_DIR . 'includes/class-es-meta-scanner.php';
        
        // ✅ NEW: Field Builder for ACF UI wrapper
        require_once ENSEMBLE_PLUGIN_DIR . 'includes/class-es-field-builder.php';
        require_once ENSEMBLE_PLUGIN_DIR . 'includes/class-es-event-layouts.php';
        require_once ENSEMBLE_PLUGIN_DIR . 'includes/class-es-layout-manager.php';

        require_once ENSEMBLE_PLUGIN_DIR . 'includes/class-es-custom-templates.php';
        
        
        // ✅ NEW: Shortcodes Handler
        require_once ENSEMBLE_PLUGIN_DIR . 'includes/class-es-shortcodes.php';
        
        // ✅ NEW: Homepage Shortcodes (Hero, Event-Listen etc.)
        require_once ENSEMBLE_PLUGIN_DIR . 'includes/class-es-homepage-shortcodes.php';
        
        // ✅ NEW: Theme Bridge - Global CSS Variables for Theme Integration
        require_once ENSEMBLE_PLUGIN_DIR . 'includes/class-es-theme-bridge.php';
        
        // ✅ NEW: Layout-Set System (v2.1)
        require_once ENSEMBLE_PLUGIN_DIR . 'includes/class-es-layout-sets.php';
        require_once ENSEMBLE_PLUGIN_DIR . 'includes/class-es-template-loader.php';
        require_once ENSEMBLE_PLUGIN_DIR . 'includes/class-es-assets-loader.php';
        
        // ✅ NEW: Design System (v1.9.0)
        require_once ENSEMBLE_PLUGIN_DIR . 'includes/class-es-design-settings.php';
        require_once ENSEMBLE_PLUGIN_DIR . 'includes/class-es-design-templates.php';
        require_once ENSEMBLE_PLUGIN_DIR . 'includes/class-es-css-generator.php';
        
        // ✅ NEW: Typography / Font Manager (v2.7)
        require_once ENSEMBLE_PLUGIN_DIR . 'includes/class-es-font-manager.php';
        
        // ✅ NEW: Onboarding & Label System (v2.0)
        require_once ENSEMBLE_PLUGIN_DIR . 'includes/class-es-label-system.php';
        require_once ENSEMBLE_PLUGIN_DIR . 'includes/class-es-onboarding-handler.php';
        require_once ENSEMBLE_PLUGIN_DIR . 'includes/ensemble-dynamic-labels.php';

        
        // ✅ NEW: License Manager (v2.8)
        require_once ENSEMBLE_PLUGIN_DIR . 'includes/class-es-license-manager.php';
        
        // ✅ NEW: Add-on System (v2.0)
        require_once ENSEMBLE_PLUGIN_DIR . 'includes/addons/class-es-addon-base.php';
        require_once ENSEMBLE_PLUGIN_DIR . 'includes/addons/class-es-addon-manager.php';

        // Shortcodes CSS
        require_once ENSEMBLE_PLUGIN_DIR . 'includes/class-es-shortcode-styles.php';
        

        // Blocks
        require_once ENSEMBLE_PLUGIN_DIR . 'includes/class-es-blocks.php';
        require_once ENSEMBLE_PLUGIN_DIR . 'includes/shortcodes/class-es-location-shortcodes.php';

        
        // ✅ NEW: Elementor Integration (v3.0)
        if ( did_action( 'elementor/loaded' ) ) {
        require_once ENSEMBLE_PLUGIN_DIR . 'includes/elementor/class-es-elementor-loader.php';
        }

        // Load Add-ons
        if (file_exists(ENSEMBLE_PLUGIN_DIR . 'includes/addons/maps/class-es-maps-addon.php')) {
            require_once ENSEMBLE_PLUGIN_DIR . 'includes/addons/maps/class-es-maps-addon.php';
        }
        if (file_exists(ENSEMBLE_PLUGIN_DIR . 'includes/addons/tickets/class-es-tickets-addon.php')) {
            require_once ENSEMBLE_PLUGIN_DIR . 'includes/addons/tickets/class-es-tickets-addon.php';
        }
        if (file_exists(ENSEMBLE_PLUGIN_DIR . 'includes/addons/related-events/class-es-related-events-addon.php')) {
            require_once ENSEMBLE_PLUGIN_DIR . 'includes/addons/related-events/class-es-related-events-addon.php';
        }
        if (file_exists(ENSEMBLE_PLUGIN_DIR . 'includes/addons/social-sharing/class-es-social-sharing-addon.php')) {
            require_once ENSEMBLE_PLUGIN_DIR . 'includes/addons/social-sharing/class-es-social-sharing-addon.php';
        }
        if (file_exists(ENSEMBLE_PLUGIN_DIR . 'includes/addons/countdown/class-es-countdown-addon.php')) {
            require_once ENSEMBLE_PLUGIN_DIR . 'includes/addons/countdown/class-es-countdown-addon.php';
        }
        // Timetable Addon
        if (file_exists(ENSEMBLE_PLUGIN_DIR . 'includes/addons/timetable/class-es-timetable-addon.php')) {
        require_once ENSEMBLE_PLUGIN_DIR . 'includes/addons/timetable/class-es-timetable-addon.php';
        }
        if (file_exists(ENSEMBLE_PLUGIN_DIR . 'includes/addons/gallery-pro/class-es-gallery-pro-addon.php')) {
            require_once ENSEMBLE_PLUGIN_DIR . 'includes/addons/gallery-pro/class-es-gallery-pro-addon.php';
        }
        if (file_exists(ENSEMBLE_PLUGIN_DIR . 'includes/addons/reservations/class-es-reservations-addon.php')) {
            require_once ENSEMBLE_PLUGIN_DIR . 'includes/addons/reservations/class-es-reservations-addon.php';
        }
        
        // ✅ NEW: Catalog Add-on (v2.9)
        if (file_exists(ENSEMBLE_PLUGIN_DIR . 'includes/addons/catalog/class-es-catalog-addon.php')) {
            require_once ENSEMBLE_PLUGIN_DIR . 'includes/addons/catalog/class-es-catalog-addon.php';
        }
        
        
        // ✅ NEW: Tickets Pro Add-on
        if (file_exists(ENSEMBLE_PLUGIN_DIR . 'includes/addons/tickets-pro/class-es-tickets-pro-addon.php')) {
            require_once ENSEMBLE_PLUGIN_DIR . 'includes/addons/tickets-pro/class-es-tickets-pro-addon.php';
        }

        // ✅ NEW: Media Folders Pro Add-on
        if (file_exists(ENSEMBLE_PLUGIN_DIR . 'includes/addons/media-folders/class-es-media-folders-addon.php')) {
            require_once ENSEMBLE_PLUGIN_DIR . 'includes/addons/media-folders/class-es-media-folders-addon.php';
        }
        // Floor Plan
        if (file_exists(ENSEMBLE_PLUGIN_DIR . 'includes/addons/floor-plan/class-es-floor-plan-addon.php')) {
            require_once ENSEMBLE_PLUGIN_DIR . 'includes/addons/floor-plan/class-es-floor-plan-addon.php';
        }
        
        // ✅ NEW: Sponsors Add-on
        if (file_exists(ENSEMBLE_PLUGIN_DIR . 'includes/addons/sponsors/class-es-sponsors-addon.php')) {
            require_once ENSEMBLE_PLUGIN_DIR . 'includes/addons/sponsors/class-es-sponsors-addon.php';
        }
        
        // ✅ NEW: FAQ Add-on
        if (file_exists(ENSEMBLE_PLUGIN_DIR . 'includes/addons/faq/class-es-faq-addon.php')) {
            require_once ENSEMBLE_PLUGIN_DIR . 'includes/addons/faq/class-es-faq-addon.php';
        }
        
        // ✅ NEW: Agenda Add-on (Kongress/Conference)
        if (file_exists(ENSEMBLE_PLUGIN_DIR . 'includes/addons/agenda/class-es-agenda-addon.php')) {
            require_once ENSEMBLE_PLUGIN_DIR . 'includes/addons/agenda/class-es-agenda-addon.php';
        }
        
        // ✅ NEW: Downloads Add-on (Pro)
        if (file_exists(ENSEMBLE_PLUGIN_DIR . 'includes/addons/downloads/class-es-downloads-addon.php')) {
            require_once ENSEMBLE_PLUGIN_DIR . 'includes/addons/downloads/class-es-downloads-addon.php';
        }
        
        // ✅ NEW: Staff & Contacts Add-on
        if (file_exists(ENSEMBLE_PLUGIN_DIR . 'includes/addons/staff/class-es-staff-addon.php')) {
            require_once ENSEMBLE_PLUGIN_DIR . 'includes/addons/staff/class-es-staff-addon.php';
        }
        
        // ✅ NEW: Booking Engine Add-on (Central Booking System)
        if (file_exists(ENSEMBLE_PLUGIN_DIR . 'includes/addons/booking-engine/class-es-booking-engine-addon.php')) {
            require_once ENSEMBLE_PLUGIN_DIR . 'includes/addons/booking-engine/class-es-booking-engine-addon.php';
        }

        // U18 Authorization Addon
        if (file_exists(ENSEMBLE_PLUGIN_DIR . 'includes/addons/u18-authorization/class-es-u18-addon.php')) {
            require_once ENSEMBLE_PLUGIN_DIR . 'includes/addons/u18-authorization/class-es-u18-addon.php';
        }

        require_once ENSEMBLE_PLUGIN_DIR . 'includes/addons/visual-calendar/class-es-visual-calendar-addon.php';
        
        // Quick-Add Modal Handler
        require_once ENSEMBLE_PLUGIN_DIR . 'includes/class-quick-add-handler.php';
        
        // ✅ NEUES Import/Export System - Backend Handler
        require_once ENSEMBLE_PLUGIN_DIR . 'includes/import/class-import-handler.php';
        require_once ENSEMBLE_PLUGIN_DIR . 'includes/import/class-ical-parser.php';
        require_once ENSEMBLE_PLUGIN_DIR . 'includes/import/class-event-transformer.php';
        require_once ENSEMBLE_PLUGIN_DIR . 'includes/import/class-location-matcher.php';
        
        // Export handler wird von class-export-ajax.php geladen (nicht hier!)
        
        // Optional: Feed Generator nur wenn RSS-Feeds gebraucht werden
        if (file_exists(ENSEMBLE_PLUGIN_DIR . 'includes/export/class-feed-generator.php')) {
            require_once ENSEMBLE_PLUGIN_DIR . 'includes/export/class-feed-generator.php';
        }
                
        // Recurring events classes
        require_once ENSEMBLE_PLUGIN_DIR . 'includes/class-es-recurring-engine.php';
        require_once ENSEMBLE_PLUGIN_DIR . 'includes/class-es-virtual-events.php';
        require_once ENSEMBLE_PLUGIN_DIR . 'includes/class-es-recurring-ajax.php';
        
        // ❌ ALTES Import System - DEAKTIVIERT!
        // This would cause duplicate menu entries and UI rendering
        // if ( file_exists( ENSEMBLE_PLUGIN_DIR . 'includes/import/ensemble-calendar-import.php' ) ) {
        //     require_once ENSEMBLE_PLUGIN_DIR . 'includes/import/ensemble-calendar-import.php';
        // }
        
        // ❌ ALTE Export Settings - DEAKTIVIERT (falls es Menu registriert)
        // If this file contains add_submenu_page(), don't load
        // if ( file_exists( ENSEMBLE_PLUGIN_DIR . 'includes/export/class-export-settings.php' ) ) {
        //     require_once ENSEMBLE_PLUGIN_DIR . 'includes/export/class-export-settings.php';
        // }
        
        // Theme Detector
require_once ENSEMBLE_PLUGIN_DIR . 'includes/class-es-theme-detector.php';
        
        // ✅ NEUE AJAX Handler (nur im Admin)
        if (is_admin()) {
            require_once ENSEMBLE_PLUGIN_DIR . 'includes/import/class-import-ajax.php';
            require_once ENSEMBLE_PLUGIN_DIR . 'includes/class-export-ajax.php';
            require_once ENSEMBLE_PLUGIN_DIR . 'includes/class-es-calendar-ajax.php';
            require_once ENSEMBLE_PLUGIN_DIR . 'includes/class-es-id-picker-ajax.php';
            require_once ENSEMBLE_PLUGIN_DIR . 'includes/class-es-taxonomy-ajax.php';
        }
        
        $this->loader = new ES_Loader();
    }
    
    /**
     * Define plugin hooks
     */
    private function define_hooks() {
        // Initialize post types
        $post_types = new ES_Post_Types();
        $this->loader->add_action('init', $post_types, 'register_post_types');
        $this->loader->add_action('init', $post_types, 'register_taxonomies');
        
        // Initialize assets
        $assets = new ES_Assets();
        $this->loader->add_action('admin_enqueue_scripts', $assets, 'enqueue_admin_styles');
        $this->loader->add_action('admin_enqueue_scripts', $assets, 'enqueue_admin_scripts');
        $this->loader->add_action('wp_enqueue_scripts', $assets, 'enqueue_frontend_styles');
        $this->loader->add_action('wp_enqueue_scripts', $assets, 'enqueue_frontend_scripts');
        
        // Initialize admin menu
        $admin_menu = new ES_Admin_Menu();
        $this->loader->add_action('admin_menu', $admin_menu, 'register_menu');
        
        // Initialize Onboarding Handler - Register menu with priority 20 (after main menu)
        $this->loader->add_action('admin_menu', 'ES_Onboarding_Handler', 'register_admin_page', 20);
        $this->loader->add_action('admin_enqueue_scripts', 'ES_Onboarding_Handler', 'enqueue_assets');
        $this->loader->add_action('wp_ajax_ensemble_complete_onboarding', 'ES_Onboarding_Handler', 'ajax_complete_onboarding');
        $this->loader->add_action('admin_init', 'ES_Onboarding_Handler', 'maybe_redirect_to_onboarding');
        
        // Initialize Add-on Manager
        ES_Addon_Manager::instance();
        
        // Register Add-ons on init (after translations are loaded)
        add_action('init', array($this, 'register_addons'), 5);
        
        // Load active add-ons on init (after registration)
        add_action('init', array($this, 'load_active_addons'), 6);
        
        // Initialize ACF installer
        $acf_installer = new ES_ACF_Installer();
        $this->loader->add_action('admin_init', $acf_installer, 'check_and_install');
        
        // Initialize wizard
        $wizard = new ES_Wizard();
        $this->loader->add_action('admin_init', $wizard, 'init');
        
        // Initialize shortcodes
        new ES_Shortcodes();
        
        // Initialize template loader
        ES_Template_Loader::init();
        
        // Initialize assets loader (Layout-Set CSS)
        ES_Assets_Loader::init();
        
        // Initialize AJAX handler
        $ajax = new ES_AJAX_Handler();
        $this->loader->add_action('wp_ajax_es_save_event', $ajax, 'save_event');
        $this->loader->add_action('wp_ajax_es_delete_event', $ajax, 'delete_event');
        $this->loader->add_action('wp_ajax_es_copy_event', $ajax, 'copy_event');
        $this->loader->add_action('wp_ajax_es_get_event', $ajax, 'get_event');
        $this->loader->add_action('wp_ajax_es_get_events', $ajax, 'get_events');
        $this->loader->add_action('wp_ajax_es_search_events', $ajax, 'search_events');
        $this->loader->add_action('wp_ajax_es_filter_events', $ajax, 'filter_events');
        $this->loader->add_action('wp_ajax_es_autosave_event', $ajax, 'autosave_event');
        $this->loader->add_action('wp_ajax_es_check_location_conflict', $ajax, 'check_location_conflict');
        $this->loader->add_action('wp_ajax_es_bulk_event_action', $ajax, 'bulk_event_action');
        
        // Child Events AJAX handlers (Duration Type System)
        $this->loader->add_action('wp_ajax_es_get_child_events', $ajax, 'get_child_events');
        $this->loader->add_action('wp_ajax_es_unlink_child_event', $ajax, 'unlink_child_event');
        $this->loader->add_action('wp_ajax_es_link_child_event', $ajax, 'link_child_event');
        $this->loader->add_action('wp_ajax_es_get_linkable_events', $ajax, 'get_linkable_events');
        
        // Artist AJAX handlers
        $this->loader->add_action('wp_ajax_es_get_artists', $ajax, 'get_artists');
        $this->loader->add_action('wp_ajax_es_get_artist', $ajax, 'get_artist');
        $this->loader->add_action('wp_ajax_es_save_artist', $ajax, 'save_artist');
        $this->loader->add_action('wp_ajax_es_delete_artist', $ajax, 'delete_artist');
        $this->loader->add_action('wp_ajax_es_search_artists', $ajax, 'search_artists');
        $this->loader->add_action('wp_ajax_es_bulk_delete_artists', $ajax, 'bulk_delete_artists');
        $this->loader->add_action('wp_ajax_es_bulk_assign_artist_taxonomy', $ajax, 'bulk_assign_artist_taxonomy');
        $this->loader->add_action('wp_ajax_es_bulk_remove_artist_taxonomy', $ajax, 'bulk_remove_artist_taxonomy');
        $this->loader->add_action('wp_ajax_es_copy_artist', $ajax, 'copy_artist');
        
        // Location AJAX handlers
        $this->loader->add_action('wp_ajax_es_get_locations', $ajax, 'get_locations');
        $this->loader->add_action('wp_ajax_es_get_location', $ajax, 'get_location');
        $this->loader->add_action('wp_ajax_es_save_location', $ajax, 'save_location');
        $this->loader->add_action('wp_ajax_es_delete_location', $ajax, 'delete_location');
        $this->loader->add_action('wp_ajax_es_search_locations', $ajax, 'search_locations');
        $this->loader->add_action('wp_ajax_es_bulk_delete_locations', $ajax, 'bulk_delete_locations');
        $this->loader->add_action('wp_ajax_es_copy_location', $ajax, 'copy_location');
        
        // Gallery AJAX handlers
        $this->loader->add_action('wp_ajax_es_get_galleries', $ajax, 'get_galleries');
        $this->loader->add_action('wp_ajax_es_get_gallery', $ajax, 'get_gallery');
        $this->loader->add_action('wp_ajax_es_save_gallery', $ajax, 'save_gallery');
        $this->loader->add_action('wp_ajax_es_delete_gallery', $ajax, 'delete_gallery');
        $this->loader->add_action('wp_ajax_es_search_galleries', $ajax, 'search_galleries');
        $this->loader->add_action('wp_ajax_es_bulk_delete_galleries', $ajax, 'bulk_delete_galleries');
        
        // Wizard custom steps
        $this->loader->add_action('wp_ajax_es_get_custom_wizard_steps', $ajax, 'get_custom_wizard_steps');
        $this->loader->add_action('wp_ajax_es_get_field_group_fields', $ajax, 'get_field_group_fields');
        
        // Calendar AJAX handlers
        $this->loader->add_action('wp_ajax_ensemble_get_calendar_events', 'ES_Shortcodes', 'ajax_get_calendar_events');
        $this->loader->add_action('wp_ajax_nopriv_ensemble_get_calendar_events', 'ES_Shortcodes', 'ajax_get_calendar_events');
        
        // Recurring events AJAX handlers
        $recurring_ajax = new ES_Recurring_AJAX();
        $this->loader->add_action('wp_ajax_es_preview_recurring', $recurring_ajax, 'preview_instances');
        $this->loader->add_action('wp_ajax_es_get_recurring_rules', $recurring_ajax, 'get_rules');
        $this->loader->add_action('wp_ajax_es_save_recurring_rules', $recurring_ajax, 'save_rules');
        $this->loader->add_action('wp_ajax_es_add_recurring_exception', $recurring_ajax, 'add_exception');
        $this->loader->add_action('wp_ajax_es_remove_recurring_exception', $recurring_ajax, 'remove_exception');
        $this->loader->add_action('wp_ajax_es_get_recurring_exceptions', $recurring_ajax, 'get_exceptions');
        $this->loader->add_action('wp_ajax_es_convert_virtual_to_real', $recurring_ajax, 'convert_to_real');
        $this->loader->add_action('wp_ajax_es_delete_virtual_event', $recurring_ajax, 'delete_virtual');
        $this->loader->add_action('wp_ajax_es_restore_to_virtual', $recurring_ajax, 'restore_to_virtual');
        
        // Quick-Add Modal AJAX handlers
        // Note: Handler hooks are registered in class constructor
        
        // Elementor Integration
        // Kann mit define('ENSEMBLE_DISABLE_ELEMENTOR', true) in wp-config.php deaktiviert werden
        if (defined('ENSEMBLE_DISABLE_ELEMENTOR') && ENSEMBLE_DISABLE_ELEMENTOR) {
            // Elementor-Integration ist deaktiviert
            return;
        }
        
        // Elementor integration is handled by includes/elementor/class-es-elementor-loader.php
    }
    
    /**
     * Load active add-ons
     * Called on init hook after registration
     */
    public function load_active_addons() {
        ES_Addon_Manager::instance()->load_active_addons();
    }
    
    /**
     * Register Add-ons
     * Called on init hook so translations are available
     */
    public function register_addons() {
        // Maps Pro Add-on
        if (class_exists('ES_Maps_Addon')) {
            ES_Addon_Manager::register_addon('maps', array(
                'name'          => __('Maps Pro', 'ensemble'),
                'description'   => __('Advanced maps with 7 styles, locations overview, marker clustering, geolocation, routing, fullscreen mode and filters. Google Maps or OpenStreetMap.', 'ensemble'),
                'version'       => '2.0.0',
                'author'        => 'Fabian',
                'author_uri'    => 'https://kraftwerk-mkt.com',
                'requires_pro'  => true,
                'class'         => 'ES_Maps_Addon',
                'icon'          => 'dashicons-location-alt',
                'settings_page' => true,
                'has_frontend'  => true,
            ));
        }

        if (class_exists('ES_Floor_Plan_Addon')) {
            ES_Addon_Manager::register_addon('floor-plan', array(
                'name'          => __('Floor Plan Pro', 'ensemble'),
                'description'   => __('Interactive floor plan editor with drag & drop. Create venue layouts with tables, sections, and seating. Integrates with Booking Engine for seat selection.', 'ensemble'),
                'version'       => '1.0.0',
                'author'        => 'Fabian',
                'author_uri'    => 'https://kraftwerk-mkt.com',
                'requires_pro'  => true,
                'class'         => 'ES_Floor_Plan_Addon',
                'icon'          => 'dashicons-layout',
                'settings_page' => true,
                'has_frontend'  => true,
            ));
        }
        
        // Tickets Add-on
        if (class_exists('ES_Tickets_Addon')) {
            ES_Addon_Manager::register_addon('tickets', array(
                'name'          => __('Tickets', 'ensemble'),
                'description'   => __('Ticket links to external providers like Eventbrite, Eventim, TicketMaster. Price display, availability status and affiliate tracking.', 'ensemble'),
                'version'       => '1.0.0',
                'author'        => 'Fabian',
                'author_uri'    => 'https://kraftwerk-mkt.com',
                'requires_pro'  => false,
                'class'         => 'ES_Tickets_Addon',
                'icon'          => 'dashicons-tickets-alt',
                'settings_page' => true,
                'has_frontend'  => true,
            ));
        }
        
        // Related Events Add-on
        if (class_exists('ES_Related_Events_Addon')) {
            ES_Addon_Manager::register_addon('related-events', array(
                'name'          => __('Related Events', 'ensemble'),
                'description'   => __('Shows related events based on category, location or artist. Perfect for event discovery and user engagement.', 'ensemble'),
                'version'       => '1.0.0',
                'author'        => 'Fabian',
                'author_uri'    => 'https://kraftwerk-mkt.com',
                'requires_pro'  => true,
                'class'         => 'ES_Related_Events_Addon',
                'icon'          => 'dashicons-networking',
                'settings_page' => true,
                'has_frontend'  => true,
            ));
        }
        
        // Social Sharing Add-on
        if (class_exists('ES_Social_Sharing_Addon')) {
            ES_Addon_Manager::register_addon('social-sharing', array(
                'name'          => __('Social Sharing', 'ensemble'),
                'description'   => __('Share buttons for Facebook, X, WhatsApp, Telegram, email and more. Including native sharing for Instagram/TikTok on mobile.', 'ensemble'),
                'version'       => '1.0.0',
                'author'        => 'Fabian',
                'author_uri'    => 'https://kraftwerk-mkt.com',
                'requires_pro'  => false,
                'class'         => 'ES_Social_Sharing_Addon',
                'icon'          => 'dashicons-share',
                'settings_page' => true,
                'has_frontend'  => true,
            ));
        }
        
        // Countdown Add-on
        if (class_exists('ES_Countdown_Addon')) {
            ES_Addon_Manager::register_addon('countdown', array(
                'name'          => __('Countdown', 'ensemble'),
                'description'   => __('Countdown timer until event start. With different styles (boxes, minimal, flip, circles) and customizable units.', 'ensemble'),
                'version'       => '1.0.0',
                'author'        => 'Fabian',
                'author_uri'    => 'https://kraftwerk-mkt.com',
                'requires_pro'  => false,
                'class'         => 'ES_Countdown_Addon',
                'icon'          => 'dashicons-clock',
                'settings_page' => true,
                'has_frontend'  => true,
            ));
        }
        
        // Gallery Pro Add-on
        if (class_exists('ES_Gallery_Pro_Addon')) {
            ES_Addon_Manager::register_addon('gallery-pro', array(
                'name'          => __('Gallery Pro', 'ensemble'),
                'description'   => __('Advanced image gallery with 5 layouts (grid, masonry, carousel, justified, filmstrip), lightbox with touch support, video embed (YouTube/Vimeo) and captions.', 'ensemble'),
                'version'       => '1.0.0',
                'author'        => 'Fabian',
                'author_uri'    => 'https://kraftwerk-mkt.com',
                'requires_pro'  => true,
                'class'         => 'ES_Gallery_Pro_Addon',
                'icon'          => 'dashicons-format-gallery',
                'settings_page' => true,
                'has_frontend'  => true,
            ));
        }
        
        // Reservations Pro Add-on
        if (class_exists('ES_Reservations_Addon')) {
            ES_Addon_Manager::register_addon('reservations', array(
                'name'          => __('Reservations Pro', 'ensemble'),
                'description'   => __('Complete reservation system with guest list, table reservations, VIP lists, QR code check-in, email notifications and CSV export.', 'ensemble'),
                'version'       => '1.0.0',
                'author'        => 'Fabian',
                'author_uri'    => 'https://kraftwerk-mkt.com',
                'requires_pro'  => true,
                'class'         => 'ES_Reservations_Addon',
                'icon'          => 'dashicons-clipboard',
                'settings_page' => true,
                'has_frontend'  => true,
            ));
        }
        
        // ✅ NEW: Booking Engine Add-on (Central Booking System)
        if (class_exists('ES_Booking_Engine_Addon')) {
            ES_Addon_Manager::register_addon('booking-engine', array(
                'name'          => __('Booking Engine', 'ensemble'),
                'description'   => __('Central booking system for reservations and tickets. Unified database, QR codes, check-in, email notifications, coupons, passes and waitlist.', 'ensemble'),
                'version'       => '1.0.0',
                'author'        => 'Fabian',
                'author_uri'    => 'https://kraftwerk-mkt.com',
                'requires_pro'  => true,
                'class'         => 'ES_Booking_Engine_Addon',
                'icon'          => 'dashicons-tickets-alt',
                'settings_page' => true,
                'has_frontend'  => true,
            ));
        }
        
        // ✅ NEW: Catalog Add-on (v2.9)
        if (class_exists('ES_Catalog_Addon')) {
            ES_Addon_Manager::register_addon('catalog', array(
                'name'          => __('Catalog', 'ensemble'),
                'description'   => __('Flexible catalogs for menus, drink lists, merchandise, services, equipment rental and more. With categories, prices and attributes.', 'ensemble'),
                'version'       => '1.0.0',
                'author'        => 'Fabian',
                'author_uri'    => 'https://kraftwerk-mkt.com',
                'requires_pro'  => false,  // ← Geändert für Entwicklung
                'class'         => 'ES_Catalog_Addon',
                'icon'          => 'dashicons-list-view',
                'settings_page' => false,
                'has_frontend'  => true,
            ));
        }
        
        // ✅ NEW: Media Folders Pro Add-on
        if (class_exists('ES_Media_Folders_Addon')) {
            ES_Addon_Manager::register_addon('media-folders', array(
                'name'          => __('Media Folders Pro', 'ensemble'),
                'description'   => __('Organize your media library with folders. Auto-creates folders for Events, Artists and Locations. Drag & drop support, sidebar navigation, and automatic media assignment.', 'ensemble'),
                'version'       => '1.0.0',
                'author'        => 'Fabian',
                'author_uri'    => 'https://kraftwerk-mkt.com',
                'requires_pro'  => true,
                'class'         => 'ES_Media_Folders_Addon',
                'icon'          => 'dashicons-portfolio',
                'settings_page' => true,
                'has_frontend'  => false,
            ));
        }
        
        // ✅ NEW: Sponsors Add-on
        if (class_exists('ES_Sponsors_Addon')) {
            ES_Addon_Manager::register_addon('sponsors', array(
                'name'          => __('Sponsors', 'ensemble'),
                'description'   => __('Manage sponsors and partners. Display as carousel, grid, bar or marquee. Assign to events or show globally. Grayscale effects, auto-display on events, and footer integration.', 'ensemble'),
                'version'       => '1.0.0',
                'author'        => 'Fabian',
                'author_uri'    => 'https://kraftwerk-mkt.com',
                'requires_pro'  => true,
                'class'         => 'ES_Sponsors_Addon',
                'icon'          => 'dashicons-groups',
                'settings_page' => true,
                'has_frontend'  => true,
            ));
        }

        // Timetable Add-on
        if (class_exists('ES_Timetable_Addon')) {
            ES_Addon_Manager::register_addon('timetable', array(
                'name'          => __('Timetable Editor', 'ensemble'),
                'description'   => __('Visual grid editor for complex conference schedules. Drag & drop sessions, manage rooms, detect conflicts. Perfect for multi-track congresses.', 'ensemble'),
                'version'       => '1.0.0',
                'author'        => 'Fabian',
                'author_uri'    => 'https://kraftwerk-mkt.com',
                'requires_pro'  => true,
                'class'         => 'ES_Timetable_Addon',
                'icon'          => 'dashicons-schedule',
                'settings_page' => true,
                'has_frontend'  => false,
            ));
        }


        // Ticket Pro
        if (class_exists("ES_Tickets_Pro_Addon")) {
            ES_Addon_Manager::register_addon('tickets-pro', array(
                'name'          => __('Tickets Pro', 'ensemble'),
                'description'   => __('Bezahlte Tickets mit Payment Gateway Integration.', 'ensemble'),
                'version'       => '1.0.0',
                'author'        => 'Kraftwerk Marketing',
                'requires_pro'  => true,
                'dependencies'  => array('booking-engine'),
                'class'         => 'ES_Tickets_Pro_Addon',
                'icon'          => 'dashicons-tickets-alt',
                'settings_page' => true,
                'has_frontend'  => true,
            ));
        }

        // U18 Authorization (Muttizettel)
        if (class_exists('ES_U18_Addon')) {
            ES_Addon_Manager::register_addon('u18-authorization', array(
                'name'          => __('U18 Muttizettel', 'ensemble'),
                'description'   => __('Digitale Aufsichtsübertragung für Minderjährige (16-17 Jahre) nach JuSchG.', 'ensemble'),
                'version'       => '1.0.0',
                'author'        => 'Fabian',
                'author_uri'    => 'https://kraftwerk-mkt.com',
                'requires_pro'  => true,
                'class'         => 'ES_U18_Addon',
                'icon'          => 'dashicons-id-alt',
                'settings_page' => true,
                'has_frontend'  => true,
            ));
        }
        
        // ✅ NEW: FAQ Add-on
        if (class_exists('ES_FAQ_Addon')) {
            ES_Addon_Manager::register_addon('faq', array(
                'name'          => __('FAQ', 'ensemble'),
                'description'   => __('Häufig gestellte Fragen mit animierten Accordions. Kategorien, Suche, Filter und Google FAQ Schema. Perfekt integriert in alle Ensemble Layouts.', 'ensemble'),
                'version'       => '1.0.0',
                'author'        => 'Fabian',
                'author_uri'    => 'https://kraftwerk-mkt.com',
                'requires_pro'  => false,
                'class'         => 'ES_FAQ_Addon',
                'icon'          => 'dashicons-editor-help',
                'settings_page' => true,
                'has_frontend'  => true,
            ));
        }
        
        // ✅ NEW: Downloads Add-on
        if (class_exists('ES_Downloads_Addon')) {
            ES_Addon_Manager::register_addon('downloads', array(
                'name'          => __('Downloads', 'ensemble'),
                'description'   => __('Zentrales Download-Management für Konferenzen und Events. Präsentationen, CVs, Handouts, Videos und mehr - verknüpft mit Speakern und Sessions.', 'ensemble'),
                'version'       => '1.0.0',
                'author'        => 'Fabian',
                'author_uri'    => 'https://kraftwerk-mkt.com',
                'requires_pro'  => true,
                'class'         => 'ES_Downloads_Addon',
                'icon'          => 'dashicons-download',
                'settings_page' => true,
                'has_frontend'  => true,
            ));
        }
        
        // Staff & Contacts Addon - for conferences and events
        if (file_exists(ENSEMBLE_PLUGIN_DIR . 'includes/addons/staff/class-es-staff-addon.php')) {
            ES_Addon_Manager::register_addon('staff', array(
                'name'          => __('Staff & Contacts', 'ensemble'),
                'description'   => __('Manage contact persons and staff. With abstract upload, phone numbers, departments, and event/location linking.', 'ensemble'),
                'version'       => '1.0.0',
                'author'        => 'Fabian',
                'author_uri'    => 'https://kraftwerk-mkt.com',
                'requires_pro'  => false,
                'class'         => 'ES_Staff_Addon',
                'icon'          => 'dashicons-businessperson',
                'settings_page' => true,
                'has_frontend'  => true,
            ));
        }
        
        // Visual Calendar Pro Addon
        if (class_exists('ES_Visual_Calendar_Addon')) {
            ES_Addon_Manager::register_addon('visual-calendar', array(
                'name'          => __('Visual Calendar Pro', 'ensemble'),
                'description'   => __('Beautiful photo-based calendar grid with event images as backgrounds. Perfect for clubs, festivals, and visual-focused event displays.', 'ensemble'),
                'version'       => '1.0.0',
                'author'        => 'Fabian',
                'author_uri'    => 'https://kraftwerk-mkt.com',
                'requires_pro'  => true,
                'class'         => 'ES_Visual_Calendar_Addon',
                'icon'          => 'dashicons-format-gallery',
                'settings_page' => true,
                'has_frontend'  => true,
            ));
        }
    }
}

/**
 * Initialize the plugin
 */
function ensemble_plugin() {
    return ES_Plugin::instance();
}

// Start the plugin
ensemble_plugin();

/**
 * Add layout and mode classes to body
 */
add_filter('body_class', function($classes) {
    if (class_exists('ES_Layout_Sets')) {
        $active_set = ES_Layout_Sets::get_active_set();
        $active_mode = ES_Layout_Sets::get_active_mode();
        
        // Stage layout doesn't support dark mode - always use light
        if ($active_set === 'stage') {
            $active_mode = 'light';
        }
        
        // Add layout class
        $classes[] = 'es-layout-' . $active_set;
        
        // Add mode class
        $classes[] = 'es-mode-' . $active_mode;
    }
    return $classes;
});

/**
 * Add mode class to HTML element via inline script (for theme compatibility)
 */
add_action('wp_head', function() {
    if (class_exists('ES_Layout_Sets')) {
        $active_set = ES_Layout_Sets::get_active_set();
        $active_mode = ES_Layout_Sets::get_active_mode();
        
        // Stage layout doesn't support dark mode - always use light
        if ($active_set === 'stage') {
            $active_mode = 'light';
        }
        
        echo '<script>document.documentElement.classList.add("es-layout-' . esc_js($active_set) . '", "es-mode-' . esc_js($active_mode) . '");</script>';
    }
}, 1);

/**
 * Load layout CSS on all frontend pages
 */
add_action('wp_enqueue_scripts', function() {
    // Load base CSS on all pages
    wp_enqueue_style(
        'ensemble-base',
        ENSEMBLE_PLUGIN_URL . 'assets/css/layouts/ensemble-base.css',
        array(),
        ENSEMBLE_VERSION
    );
    
    // Load mode CSS on ALL frontend pages (for grids and singles)
    wp_enqueue_style(
        'ensemble-mode-fix',
        ENSEMBLE_PLUGIN_URL . 'assets/css/single-page-fix.css',
        array('ensemble-base'),
        ENSEMBLE_VERSION,
        'all'
    );
    
    // Load active layout CSS on single pages
    if (is_singular('ensemble_event') || is_singular('ensemble_artist') || is_singular('ensemble_location')) {
        if (class_exists('ES_Layout_Sets')) {
            $active_set = ES_Layout_Sets::get_active_set();
            $set_data = ES_Layout_Sets::get_set_data($active_set);
            
            if (!empty($set_data['path'])) {
                $style_path = $set_data['path'] . '/style.css';
                
                if (file_exists($style_path)) {
                    if (defined('ENSEMBLE_PLUGIN_DIR') && strpos($style_path, ENSEMBLE_PLUGIN_DIR) === 0) {
                        $style_url = str_replace(ENSEMBLE_PLUGIN_DIR, ENSEMBLE_PLUGIN_URL, $style_path);
                    } else {
                        $style_url = str_replace(WP_CONTENT_DIR, WP_CONTENT_URL, $style_path);
                    }
                    
                    wp_enqueue_style(
                        'ensemble-layout-' . $active_set . '-single',
                        $style_url,
                        array('ensemble-base'),
                        ENSEMBLE_VERSION
                    );
                }
            }
        }
    }
}, 999); // High priority to load last

/**
 * Helper functions for addons to access mode settings
 */

/**
 * Get the current display mode (light/dark)
 * 
 * @return string 'light' or 'dark'
 */
function ensemble_get_mode() {
    if (class_exists('ES_Layout_Sets')) {
        $active_set = ES_Layout_Sets::get_active_set();
        
        // Stage layout doesn't support dark mode
        if ($active_set === 'stage') {
            return 'light';
        }
        
        return ES_Layout_Sets::get_active_mode();
    }
    return 'light';
}

/**
 * Check if dark mode is active
 * 
 * @return bool
 */
function ensemble_is_dark_mode() {
    return ensemble_get_mode() === 'dark';
}

/**
 * Get mode-aware CSS class
 * 
 * @param string $base_class Base class name
 * @return string Class with mode suffix
 */
function ensemble_mode_class($base_class = '') {
    $mode = ensemble_get_mode();
    $classes = array('es-mode-' . $mode);
    
    if (!empty($base_class)) {
        $classes[] = $base_class;
    }
    
    return implode(' ', $classes);
}

/**
 * Output inline CSS variables for current mode
 * Addons can call this to get mode-aware colors
 */
function ensemble_output_mode_vars() {
    $mode = ensemble_get_mode();
    
    $active_set = '';
    if (class_exists('ES_Layout_Sets')) {
        $active_set = ES_Layout_Sets::get_active_set();
    }
    
    // Stage layout doesn't support dark mode - always use light
    if ($active_set === 'stage') {
        $mode = 'light';
    }
    
    // Stage layout has neutral gray instead of white
    $is_stage = ($active_set === 'stage');
    
    if ($mode === 'dark') {
        $bg = '#0a0a0a';
        $surface = '#1a1a1a';
        echo '<style>
            :root {
                /* Addon variables */
                --es-addon-bg: ' . $bg . ';
                --es-addon-surface: ' . $surface . ';
                --es-addon-text: #fafafa;
                --es-addon-text-secondary: #aaaaaa;
                --es-addon-border: #333333;
                
                /* Override ensemble design system */
                --ensemble-bg: ' . $bg . ';
                --ensemble-card-bg: ' . $surface . ';
                --ensemble-text: #fafafa;
                --ensemble-text-secondary: #aaaaaa;
                --ensemble-card-border: #333333;
            }
        </style>';
    } else {
        $bg = $is_stage ? '#e8e8e8' : '#f8f9fa';
        $surface = $is_stage ? '#f0f0f0' : '#ffffff';
        echo '<style>
            :root {
                /* Addon variables */
                --es-addon-bg: ' . $bg . ';
                --es-addon-surface: ' . $surface . ';
                --es-addon-text: #1a1a1a;
                --es-addon-text-secondary: #555555;
                --es-addon-border: #e0e0e0;
                
                /* Override ensemble design system */
                --ensemble-bg: ' . $bg . ';
                --ensemble-card-bg: ' . $surface . ';
                --ensemble-text: #1a1a1a;
                --ensemble-text-secondary: #555555;
                --ensemble-card-border: #e0e0e0;
            }
        </style>';
    }
}

// Hook to output mode vars in head
add_action('wp_head', 'ensemble_output_mode_vars', 5);


/**
 * Activation hook
 */
register_activation_hook(__FILE__, 'ensemble_activate');
function ensemble_activate() {
    // Set default options
    add_option('ensemble_version', ENSEMBLE_VERSION);
    add_option('ensemble_theme', 'dark');
    add_option('ensemble_acf_installed', false);
    
    // Set onboarding redirect flag
    ES_Onboarding_Handler::set_activation_redirect();
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

/**
 * Deactivation hook
 */
register_deactivation_hook(__FILE__, 'ensemble_deactivate');
function ensemble_deactivate() {
    // Flush rewrite rules
    flush_rewrite_rules();
}