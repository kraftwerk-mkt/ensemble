<?php
/**
 * Ensemble Maps Pro Add-on
 * 
 * Advanced maps functionality for events and locations
 * - Overview maps with all locations
 * - Marker clustering for many locations
 * - Custom map styles (Dark, Light, Vintage, etc.)
 * - Custom marker icons per location type
 * - Rich popup info windows with location details
 * - Filter by category, city, event status
 * - Geolocation with distance display
 * - Fullscreen mode
 * - Location search on map
 * - Inline routing/directions
 *
 * @package Ensemble
 * @subpackage Addons
 * @since 2.0.0
 * @version 2.3.6
 * 
 * Changes in 2.3.6:
 * - Fixed Gutenberg block support (scripts now load when block is used)
 * - has_map_shortcode() now also checks for ensemble/locations-map block
 * 
 * Changes in 2.3.5:
 * - Fixed style not applying in locations map shortcode (used wrong setting key)
 * - Shortcode now correctly selects google_map_style or map_style based on provider
 * - Added debug logging for style
 * 
 * Changes in 2.3.4:
 * - Fixed getBounds error for L.layerGroup
 * - Fixed version number (was causing old JS to be cached)
 * - Added debug logging for style issues
 * 
 * Changes in 2.3.3:
 * - Map styles now work correctly (all OSM + Google styles)
 * 
 * Changes in 2.3.2:
 * - Settings toggles now work correctly (hidden fields fix)
 * 
 * Changes in 2.3.1:
 * - Added close button to popups
 * - Click on map closes popup
 * - Improved popup padding
 * 
 * Changes in 2.3.0:
 * - Thumbnail markers now work in Google Maps (using Custom Overlay)
 * - Route button opens Google Maps externally
 * - Single event maps have no popup
 * - Address retrieval supports multiple field names
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class ES_Maps_Addon extends ES_Addon_Base {
    
    /**
     * Add-on configuration
     */
    protected $slug = 'maps';
    protected $name = 'Maps Pro';
    protected $version = '2.3.6';
    
    /**
     * Map providers
     * @var array
     */
    private $providers = array(
        'google' => 'Google Maps',
        'osm'    => 'OpenStreetMap (Leaflet)',
    );
    
    /**
     * Map styles for Leaflet/OSM
     * @var array
     */
    private $map_styles = array(
        'default'    => array(
            'name' => 'Standard',
            'url'  => 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
            'attr' => '© OpenStreetMap contributors',
        ),
        'dark'       => array(
            'name' => 'Dark Mode',
            'url'  => 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png',
            'attr' => '© OpenStreetMap contributors © CARTO',
        ),
        'light'      => array(
            'name' => 'Light',
            'url'  => 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png',
            'attr' => '© OpenStreetMap contributors © CARTO',
        ),
        'voyager'    => array(
            'name' => 'Voyager',
            'url'  => 'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png',
            'attr' => '© OpenStreetMap contributors © CARTO',
        ),
        'watercolor' => array(
            'name' => 'Watercolor',
            'url'  => 'https://stamen-tiles.a.ssl.fastly.net/watercolor/{z}/{x}/{y}.jpg',
            'attr' => 'Map tiles by Stamen Design',
        ),
        'terrain'    => array(
            'name' => 'Terrain',
            'url'  => 'https://stamen-tiles.a.ssl.fastly.net/terrain/{z}/{x}/{y}{r}.png',
            'attr' => 'Map tiles by Stamen Design',
        ),
        'satellite'  => array(
            'name' => 'Satellite',
            'url'  => 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
            'attr' => 'Tiles © Esri',
        ),
    );
    
    /**
     * Marker icons
     * @var array
     */
    private $marker_icons = array(
        'default'    => array('icon' => '📍', 'color' => '#e74c3c'),
        'venue'      => array('icon' => '🏛️', 'color' => '#9b59b6'),
        'club'       => array('icon' => '🎵', 'color' => '#e91e63'),
        'stadium'    => array('icon' => '🏟️', 'color' => '#3498db'),
        'theater'    => array('icon' => '🎭', 'color' => '#e67e22'),
        'outdoor'    => array('icon' => '🌳', 'color' => '#27ae60'),
        'restaurant' => array('icon' => '🍽️', 'color' => '#f39c12'),
        'bar'        => array('icon' => '🍸', 'color' => '#8e44ad'),
        'hotel'      => array('icon' => '🏨', 'color' => '#1abc9c'),
        'museum'     => array('icon' => '🏛️', 'color' => '#34495e'),
    );
    
    /**
     * Initialize add-on
     */
    protected function init() {
        $this->log('Maps Pro add-on initialized');
    }
    
    /**
     * Register hooks
     */
    protected function register_hooks() {
        // Frontend hooks
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        
        // Template hooks - werden in Templates mit ES_Addon_Manager::do_addon_hook() aufgerufen
        // Hook: ensemble_after_location - zeigt Karte nach Location-Info
        $this->register_template_hook('ensemble_after_location', array($this, 'render_event_map'), 10);
        
        // Admin hooks
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Cache invalidation on event save (uses configured post type)
        add_action('save_post', array($this, 'invalidate_category_cache'), 10, 1);
        
        // AJAX handlers
        add_action('wp_ajax_es_geocode_address', array($this, 'ajax_geocode_address'));
        add_action('wp_ajax_es_get_all_locations', array($this, 'ajax_get_all_locations'));
        add_action('wp_ajax_nopriv_es_get_all_locations', array($this, 'ajax_get_all_locations'));
        add_action('wp_ajax_es_get_location_events', array($this, 'ajax_get_location_events'));
        add_action('wp_ajax_nopriv_es_get_location_events', array($this, 'ajax_get_location_events'));
        add_action('wp_ajax_es_get_directions', array($this, 'ajax_get_directions'));
        add_action('wp_ajax_nopriv_es_get_directions', array($this, 'ajax_get_directions'));
        
        // Shortcodes
        add_shortcode('ensemble_map', array($this, 'shortcode_map'));
        add_shortcode('ensemble_locations_map', array($this, 'shortcode_locations_map'));
        
        // REST API
        add_action('rest_api_init', array($this, 'register_rest_routes'));
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        // Check if we're on an event page (supports both custom post type and posts with ensemble_category)
        $post_type = function_exists('ensemble_get_post_type') ? ensemble_get_post_type() : 'ensemble_event';
        $is_event_page = is_singular($post_type);
        
        // Also check for posts with ensemble_category (if post type is 'post')
        if (!$is_event_page && is_singular('post')) {
            $terms = get_the_terms(get_the_ID(), 'ensemble_category');
            if ($terms && !is_wp_error($terms)) {
                $is_event_page = true;
            }
        }
        
        if (!$is_event_page && !$this->has_map_shortcode()) {
            return;
        }
        
        $provider = $this->get_setting('provider', 'osm');
        $map_style = $this->get_setting('map_style', 'default');
        
        if ($provider === 'google') {
            $api_key = $this->get_setting('google_api_key', '');
            if (!empty($api_key)) {
                wp_enqueue_script(
                    'google-maps',
                    "https://maps.googleapis.com/maps/api/js?key={$api_key}&libraries=places",
                    array(),
                    null,
                    true
                );
                
                // MarkerClusterer for Google Maps
                wp_enqueue_script(
                    'google-maps-clusterer',
                    'https://unpkg.com/@googlemaps/markerclusterer@2.0.0/dist/index.min.js',
                    array('google-maps'),
                    '2.0.0',
                    true
                );
            }
        } else {
            // OpenStreetMap / Leaflet
            wp_enqueue_style(
                'leaflet',
                'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
                array(),
                '1.9.4'
            );
            wp_enqueue_script(
                'leaflet',
                'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
                array(),
                '1.9.4',
                true
            );
            
            // Leaflet MarkerCluster
            wp_enqueue_style(
                'leaflet-markercluster',
                'https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css',
                array('leaflet'),
                '1.4.1'
            );
            wp_enqueue_style(
                'leaflet-markercluster-default',
                'https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css',
                array('leaflet-markercluster'),
                '1.4.1'
            );
            wp_enqueue_script(
                'leaflet-markercluster',
                'https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js',
                array('leaflet'),
                '1.4.1',
                true
            );
            
            // Leaflet Fullscreen
            wp_enqueue_style(
                'leaflet-fullscreen',
                'https://unpkg.com/leaflet.fullscreen@2.4.0/Control.FullScreen.css',
                array('leaflet'),
                '2.4.0'
            );
            wp_enqueue_script(
                'leaflet-fullscreen',
                'https://unpkg.com/leaflet.fullscreen@2.4.0/Control.FullScreen.js',
                array('leaflet'),
                '2.4.0',
                true
            );
            
            // Leaflet Locate (Geolocation)
            wp_enqueue_style(
                'leaflet-locate',
                'https://unpkg.com/leaflet.locatecontrol@0.79.0/dist/L.Control.Locate.min.css',
                array('leaflet'),
                '0.79.0'
            );
            wp_enqueue_script(
                'leaflet-locate',
                'https://unpkg.com/leaflet.locatecontrol@0.79.0/dist/L.Control.Locate.min.js',
                array('leaflet'),
                '0.79.0',
                true
            );
            
            // Leaflet Routing Machine (for directions)
            if ($this->get_setting('enable_routing', true)) {
                wp_enqueue_style(
                    'leaflet-routing',
                    'https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css',
                    array('leaflet'),
                    '3.2.12'
                );
                wp_enqueue_script(
                    'leaflet-routing',
                    'https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.min.js',
                    array('leaflet'),
                    '3.2.12',
                    true
                );
            }
        }
        
        // Maps Pro styles
        wp_enqueue_style(
            'ensemble-maps',
            $this->get_addon_url() . 'assets/maps.css',
            array(),
            $this->version
        );
        
        // Maps Pro script
        wp_enqueue_script(
            'ensemble-maps',
            $this->get_addon_url() . 'assets/maps.js',
            array('jquery', $provider === 'google' ? 'google-maps' : 'leaflet'),
            $this->version,
            true
        );
        
        // Get current map style config
        $style_config = isset($this->map_styles[$map_style]) ? $this->map_styles[$map_style] : $this->map_styles['default'];
        
        // Google Maps specific settings
        $google_map_style = $this->get_setting('google_map_style', 'default');
        
        // Debug logging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Ensemble Maps: Provider = ' . $provider);
            error_log('Ensemble Maps: OSM Style = ' . $map_style);
            error_log('Ensemble Maps: Google Style = ' . $google_map_style);
            error_log('Ensemble Maps: Final mapStyle = ' . ($provider === 'google' ? $google_map_style : $map_style));
        }
        
        wp_localize_script('ensemble-maps', 'ensembleMaps', array(
            'ajaxUrl'      => admin_url('admin-ajax.php'),
            'nonce'        => wp_create_nonce('ensemble_maps'),
            'provider'     => $provider,
            'defaultZoom'  => $this->get_setting('default_zoom', 15),
            'overviewZoom' => $this->get_setting('overview_zoom', 6),
            'defaultLat'   => $this->get_setting('default_lat', 51.1657),
            'defaultLng'   => $this->get_setting('default_lng', 10.4515),
            'mapStyle'     => $provider === 'google' ? $google_map_style : $map_style,
            'tileUrl'      => $style_config['url'],
            'tileAttr'     => $style_config['attr'],
            'clustering'   => $this->get_setting('enable_clustering', true),
            'fullscreen'   => $this->get_setting('enable_fullscreen', true),
            'geolocation'  => $this->get_setting('enable_geolocation', true),
            'routing'      => $this->get_setting('enable_routing', true),
            'streetView'   => $this->get_setting('google_street_view', true),
            'mapTypeControl' => $this->get_setting('google_map_type_control', true),
            'markerThumbnails' => $this->get_setting('enable_marker_thumbnails', true),
            'markerIcons'  => $this->marker_icons,
            'strings'      => array(
                'loading'        => __('Loading map...', 'ensemble'),
                'noCoordinates'  => __('No coordinates available', 'ensemble'),
                'route'          => __('Route planen', 'ensemble'),
                'myLocation'     => __('My Location', 'ensemble'),
                'distance'       => __('Entfernung', 'ensemble'),
                'directions'     => __('Wegbeschreibung', 'ensemble'),
                'close'          => __('Close', 'ensemble'),
                'fullscreen'     => __('Vollbild', 'ensemble'),
                'exitFullscreen' => __('Vollbild beenden', 'ensemble'),
                'searchLocation' => __('Location suchen...', 'ensemble'),
                'noResults'      => __('Keine Ergebnisse', 'ensemble'),
                'showAll'        => __('Show all', 'ensemble'),
                'filterBy'       => __('Filtern nach', 'ensemble'),
                'upcomingEvents' => __('Kommende Events', 'ensemble'),
                'nextEvent'      => __('Nächstes Event', 'ensemble'),
                'noEvents'       => __('Keine kommenden Events', 'ensemble'),
                'viewLocation'   => __('Location ansehen', 'ensemble'),
                'viewEvent'      => __('Event ansehen', 'ensemble'),
                'locating'       => __('Locating...', 'ensemble'),
                'locationError'  => __('Could not determine location', 'ensemble'),
                'km'             => __('km', 'ensemble'),
                'm'              => __('m', 'ensemble'),
            ),
        ));
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Nur auf Add-ons Seite und bei Location-Bearbeitung
        $screen = get_current_screen();
        
        if ($hook === 'ensemble_page_ensemble-addons' || 
            ($screen && $screen->id === 'ensemble_page_ensemble')) {
            
            wp_enqueue_script(
                'ensemble-maps-admin',
                $this->get_addon_url() . 'assets/maps-admin.js',
                array('jquery'),
                $this->version,
                true
            );
            
            wp_localize_script('ensemble-maps-admin', 'ensembleMapsAdmin', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('ensemble_maps'),
                'strings' => array(
                    'geocoding' => __('Geocodiere...', 'ensemble'),
                    'success'   => __('Koordinaten gefunden', 'ensemble'),
                    'error'     => __('Geocoding fehlgeschlagen', 'ensemble'),
                ),
            ));
        }
    }
    
    /**
     * Check if current page has map shortcode or block
     * 
     * @return bool
     */
    private function has_map_shortcode() {
        global $post;
        if (!$post) return false;
        
        // Check for shortcodes
        if (has_shortcode($post->post_content, 'ensemble_map') ||
            has_shortcode($post->post_content, 'ensemble_locations_map')) {
            return true;
        }
        
        // Check for Gutenberg blocks (v2.3.6)
        if (function_exists('has_block')) {
            if (has_block('ensemble/locations-map', $post) ||
                has_block('ensemble/map', $post)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Render event map
     * Called via template hook
     * 
     * @param int $event_id
     * @param array $location
     */
    public function render_event_map($event_id, $location = null) {
        // Check display settings
        if (function_exists('ensemble_show_addon') && !ensemble_show_addon('maps')) {
            return;
        }
        
        if (!$location || empty($location['id'])) {
            return;
        }
        
        $location_id = $location['id'];
        
        // Check if map should be shown
        $show_map = $this->get_location_field($location_id, 'show_map', true);
        
        // Handle verschiedene Werte - explizit auf false/0 prüfen
        // Default ist true (Karte anzeigen), nur bei explizit false/0 nicht anzeigen
        if ($show_map === false || $show_map === 0 || $show_map === '0' || $show_map === '') {
            return;
        }
        
        $map_type = $this->get_location_field($location_id, 'map_type', 'embedded');
        
        $lat = $this->get_location_field($location_id, 'latitude');
        $lng = $this->get_location_field($location_id, 'longitude');
        
        if (empty($lat) || empty($lng)) {
            return;
        }
        
        if ($map_type === 'link') {
            // Nur Route-Button anzeigen
            echo $this->load_template('route-button', array(
                'location_id'   => $location_id,
                'location_name' => $location['name'],
                'latitude'      => $lat,
                'longitude'     => $lng,
            ));
            return;
        }
        
        // Embedded map
        echo $this->load_template('map-embed', array(
            'location_id'   => $location_id,
            'location_name' => $location['name'],
            'latitude'      => $lat,
            'longitude'     => $lng,
            'address'       => $this->get_location_address_string($location),
        ));
    }
    
    /**
     * Render route button
     * Called via template hook
     * 
     * @param int $event_id
     * @param array $location
     */
    public function render_route_button($event_id, $location = null) {
        if (!$location || empty($location['id'])) {
            return;
        }
        
        $location_id = $location['id'];
        
        // Check if map should be shown
        $show_map = $this->get_location_field($location_id, 'show_map', true);
        if (!$show_map) {
            return;
        }
        
        $lat = $this->get_location_field($location_id, 'latitude');
        $lng = $this->get_location_field($location_id, 'longitude');
        
        if (empty($lat) || empty($lng)) {
            return;
        }
        
        $map_type = $this->get_location_field($location_id, 'map_type', 'embedded');
        
        // Get address from DB (not from passed array which may be incomplete)
        $address = $this->get_location_address_string_by_id($location_id);
        
        // Route-Button immer anzeigen
        echo $this->load_template('route-button', array(
            'location_id'   => $location_id,
            'location_name' => $location['name'],
            'latitude'      => $lat,
            'longitude'     => $lng,
            'address'       => $address,
            'show_embedded' => $map_type === 'embedded',
        ));
    }
    
    /**
     * Shortcode for overview maps
     * 
     * @param array $atts
     * @return string
     */
    public function shortcode_map($atts) {
        $atts = shortcode_atts(array(
            'locations' => '',
            'events'    => '',
            'category'  => '',
            'height'    => '400px',
        ), $atts, 'ensemble_map');
        
        $locations = $this->get_shortcode_locations($atts);
        
        if (empty($locations)) {
            return '<p>' . __('No locations found.', 'ensemble') . '</p>';
        }
        
        return $this->load_template('map-overview', array(
            'locations' => $locations,
            'height'    => $atts['height'],
        ));
    }
    
    /**
     * Get locations for shortcode
     * 
     * @param array $atts
     * @return array
     */
    private function get_shortcode_locations($atts) {
        $locations = array();
        
        // By location IDs
        if (!empty($atts['locations'])) {
            $location_ids = array_map('intval', explode(',', $atts['locations']));
            foreach ($location_ids as $location_id) {
                $location = $this->get_location_data($location_id);
                if ($location) {
                    $locations[] = $location;
                }
            }
        }
        
        // By event IDs
        if (!empty($atts['events'])) {
            $event_ids = array_map('intval', explode(',', $atts['events']));
            foreach ($event_ids as $event_id) {
                $location_id = get_post_meta($event_id, 'event_location', true);
                if ($location_id) {
                    $location = $this->get_location_data($location_id);
                    if ($location && !in_array($location, $locations)) {
                        $locations[] = $location;
                    }
                }
            }
        }
        
        // By category
        if (!empty($atts['category'])) {
            $category_locations = $this->get_locations_by_category($atts['category']);
            foreach ($category_locations as $location) {
                // Avoid duplicates
                $exists = false;
                foreach ($locations as $existing) {
                    if ($existing['id'] === $location['id']) {
                        $exists = true;
                        break;
                    }
                }
                if (!$exists) {
                    $locations[] = $location;
                }
            }
        }
        
        return $locations;
    }
    
    /**
     * Get locations by event category
     * 
     * Retrieves all unique locations from events in specified category/categories.
     * Supports category slugs, IDs, or comma-separated values.
     * Uses caching for performance optimization.
     * 
     * @since 2.9.0
     * @param string|int $category Category slug, ID, or comma-separated values.
     * @return array Array of location data arrays.
     */
    private function get_locations_by_category($category) {
        if (empty($category)) {
            return array();
        }
        
        // Generate cache key
        $cache_key = 'ensemble_map_locations_cat_' . md5($category);
        $cached = get_transient($cache_key);
        
        if (false !== $cached) {
            return $cached;
        }
        
        $locations = array();
        $location_ids = array();
        
        // Parse categories (support comma-separated)
        $categories = array_map('trim', explode(',', $category));
        
        // Build tax query
        $tax_query = array(
            'relation' => 'OR',
        );
        
        foreach ($categories as $cat) {
            if (is_numeric($cat)) {
                // Category ID
                $tax_query[] = array(
                    'taxonomy' => 'event_category',
                    'field'    => 'term_id',
                    'terms'    => intval($cat),
                );
            } else {
                // Category slug
                $tax_query[] = array(
                    'taxonomy' => 'event_category',
                    'field'    => 'slug',
                    'terms'    => sanitize_title($cat),
                );
            }
        }
        
        // Get configured event post type
        $event_post_type = function_exists('ensemble_get_post_type') ? ensemble_get_post_type() : 'post';
        
        // Query events in category
        $events = get_posts(array(
            'post_type'      => $event_post_type,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids', // Performance: only get IDs
            'tax_query'      => $tax_query,
            'no_found_rows'  => true, // Performance: skip pagination count
        ));
        
        if (empty($events)) {
            set_transient($cache_key, array(), HOUR_IN_SECONDS);
            return array();
        }
        
        // Extract unique locations from events
        foreach ($events as $event_id) {
            // Use helper function to support all meta key formats
            $location_id = function_exists('ensemble_get_event_meta') 
                ? ensemble_get_event_meta($event_id, 'location')
                : get_post_meta($event_id, 'event_location', true);
            
            // Skip if no location or already processed
            if (empty($location_id) || in_array($location_id, $location_ids, true)) {
                continue;
            }
            
            $location_data = $this->get_location_data($location_id);
            
            if ($location_data) {
                $locations[] = $location_data;
                $location_ids[] = $location_id;
            }
        }
        
        // Cache for 1 hour
        set_transient($cache_key, $locations, HOUR_IN_SECONDS);
        
        // Log in debug mode
        if (function_exists('ensemble_log')) {
            ensemble_log('Category locations retrieved', array(
                'category'   => $category,
                'events'     => count($events),
                'locations'  => count($locations),
            ));
        }
        
        return $locations;
    }
    
    /**
     * Get location data
     * 
     * @param int $location_id
     * @return array|false
     */
    private function get_location_data($location_id) {
        $lat = $this->get_location_field($location_id, 'latitude');
        $lng = $this->get_location_field($location_id, 'longitude');
        
        if (empty($lat) || empty($lng)) {
            return false;
        }
        
        return array(
            'id'        => $location_id,
            'name'      => get_the_title($location_id),
            'latitude'  => $lat,
            'longitude' => $lng,
            'address'   => $this->get_location_address_string_by_id($location_id),
        );
    }
    
    /**
     * Get location field (ACF or native)
     * 
     * @param int $location_id
     * @param string $field
     * @param mixed $default
     * @return mixed
     */
    private function get_location_field($location_id, $field, $default = '') {
        // Try ACF first
        if (function_exists('get_field')) {
            $value = get_field($field, $location_id);
            // For show_map field, false/0 are valid values, not "empty"
            if ($field === 'show_map') {
                if ($value !== null) {
                    return $value;
                }
            } elseif ($value !== false && $value !== '' && $value !== null) {
                return $value;
            }
        }
        
        // Fallback to post meta
        $value = get_post_meta($location_id, $field, true);
        
        // For show_map, empty string means not set, so use default
        if ($field === 'show_map' && $value === '') {
            return $default;
        }
        
        return ($value !== '') ? $value : $default;
    }
    
    /**
     * Get location address string from location array
     * 
     * @param array $location
     * @return string
     */
    private function get_location_address_string($location) {
        $parts = array();
        
        if (!empty($location['address'])) {
            $parts[] = $location['address'];
        }
        
        if (!empty($location['zip_code']) || !empty($location['city'])) {
            $city_parts = array_filter(array(
                !empty($location['zip_code']) ? $location['zip_code'] : '',
                !empty($location['city']) ? $location['city'] : ''
            ));
            if (!empty($city_parts)) {
                $parts[] = implode(' ', $city_parts);
            }
        }
        
        return implode(', ', $parts);
    }
    
    /**
     * Get location address string by ID
     * 
     * @param int $location_id
     * @return string
     */
    private function get_location_address_string_by_id($location_id) {
        // Try multiple possible field names for address
        $address = $this->get_location_field($location_id, 'address');
        if (empty($address)) {
            $address = $this->get_location_field($location_id, 'street');
        }
        if (empty($address)) {
            $address = $this->get_location_field($location_id, 'location_address');
        }
        
        // Try multiple possible field names for zip
        $zip = $this->get_location_field($location_id, 'zip_code');
        if (empty($zip)) {
            $zip = $this->get_location_field($location_id, 'postal_code');
        }
        if (empty($zip)) {
            $zip = $this->get_location_field($location_id, 'plz');
        }
        
        // Try multiple possible field names for city
        $city = $this->get_location_field($location_id, 'city');
        if (empty($city)) {
            $city = $this->get_location_field($location_id, 'location_city');
        }
        if (empty($city)) {
            $city = $this->get_location_field($location_id, 'ort');
        }
        
        $parts = array_filter(array($address, trim($zip . ' ' . $city)));
        return implode(', ', $parts);
    }
    
    /**
     * AJAX: Geocode address
     */
    public function ajax_geocode_address() {
        check_ajax_referer('ensemble_maps', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'ensemble')));
        }
        
        $address = isset($_POST['address']) ? sanitize_text_field($_POST['address']) : '';
        $zip = isset($_POST['zip']) ? sanitize_text_field($_POST['zip']) : '';
        $city = isset($_POST['city']) ? sanitize_text_field($_POST['city']) : '';
        
        $full_address = trim("{$address}, {$zip} {$city}");
        
        if (empty($full_address)) {
            wp_send_json_error(array('message' => __('Address required', 'ensemble')));
        }
        
        $coordinates = $this->geocode_address($full_address);
        
        if ($coordinates) {
            wp_send_json_success($coordinates);
        } else {
            wp_send_json_error(array('message' => __('Geocoding fehlgeschlagen', 'ensemble')));
        }
    }
    
    /**
     * Geocode address to coordinates
     * 
     * @param string $address
     * @return array|false Array with 'lat' and 'lng' or false
     */
    private function geocode_address($address) {
        $provider = $this->get_setting('provider', 'osm');
        
        if ($provider === 'google') {
            return $this->geocode_google($address);
        } else {
            return $this->geocode_nominatim($address);
        }
    }
    
    /**
     * Geocode using Google Maps API
     * 
     * @param string $address
     * @return array|false
     */
    private function geocode_google($address) {
        $api_key = $this->get_setting('google_api_key', '');
        
        if (empty($api_key)) {
            return false;
        }
        
        $url = 'https://maps.googleapis.com/maps/api/geocode/json?' . http_build_query(array(
            'address' => $address,
            'key'     => $api_key,
        ));
        
        $response = wp_remote_get($url);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($data['status'] === 'OK' && !empty($data['results'][0])) {
            $location = $data['results'][0]['geometry']['location'];
            return array(
                'lat' => $location['lat'],
                'lng' => $location['lng'],
            );
        }
        
        return false;
    }
    
    /**
     * Geocode using Nominatim (OpenStreetMap)
     * 
     * @param string $address
     * @return array|false
     */
    private function geocode_nominatim($address) {
        $url = 'https://nominatim.openstreetmap.org/search?' . http_build_query(array(
            'q'      => $address,
            'format' => 'json',
            'limit'  => 1,
        ));
        
        $response = wp_remote_get($url, array(
            'headers' => array(
                'User-Agent' => 'Ensemble WordPress Plugin',
            ),
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!empty($data[0])) {
            return array(
                'lat' => floatval($data[0]['lat']),
                'lng' => floatval($data[0]['lon']),
            );
        }
        
        return false;
    }
    
    /**
     * Render settings page
     * 
     * @return string
     */
    public function render_settings() {
        return $this->load_template('settings', array(
            'settings'  => $this->settings,
            'providers' => $this->providers,
        ));
    }
    
    /**
     * Sanitize settings
     * 
     * @param array $settings
     * @return array
     */
    public function sanitize_settings($settings) {
        $sanitized = array();
        
        // Helper function to parse boolean/checkbox values
        // Handles: true, false, "true", "false", "1", "0", 1, 0
        $parse_bool = function($value) {
            if (is_bool($value)) {
                return $value;
            }
            if ($value === '1' || $value === 1) {
                return true;
            }
            if ($value === '0' || $value === 0 || $value === '' || $value === null) {
                return false;
            }
            if (is_string($value)) {
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            }
            return (bool) $value;
        };
        
        // Provider
        $sanitized['provider'] = isset($settings['provider']) && 
            in_array($settings['provider'], array_keys($this->providers)) ? 
            $settings['provider'] : 'osm';
        
        // Google API Key
        $sanitized['google_api_key'] = isset($settings['google_api_key']) ? 
            sanitize_text_field($settings['google_api_key']) : '';
        
        // Google Maps Style (dark, light, retro, night, default)
        $google_styles = array('default', 'dark', 'light', 'retro', 'night');
        $sanitized['google_map_style'] = isset($settings['google_map_style']) && 
            in_array($settings['google_map_style'], $google_styles) ? 
            $settings['google_map_style'] : 'default';
        
        // Google-specific controls
        $sanitized['google_street_view'] = isset($settings['google_street_view']) ? 
            $parse_bool($settings['google_street_view']) : true;
        $sanitized['google_map_type_control'] = isset($settings['google_map_type_control']) ? 
            $parse_bool($settings['google_map_type_control']) : true;
        
        // Default coordinates and zoom
        $sanitized['default_zoom'] = isset($settings['default_zoom']) ? 
            intval($settings['default_zoom']) : 15;
        
        $sanitized['default_lat'] = isset($settings['default_lat']) ? 
            floatval($settings['default_lat']) : 51.1657;
        
        $sanitized['default_lng'] = isset($settings['default_lng']) ? 
            floatval($settings['default_lng']) : 10.4515;
        
        // Auto geocode
        $sanitized['auto_geocode'] = isset($settings['auto_geocode']) ? 
            $parse_bool($settings['auto_geocode']) : true;
        
        // OSM/Leaflet style
        $sanitized['map_style'] = isset($settings['map_style']) && 
            array_key_exists($settings['map_style'], $this->map_styles) ? 
            $settings['map_style'] : 'default';
        
        // Overview zoom
        $sanitized['overview_zoom'] = isset($settings['overview_zoom']) ? 
            intval($settings['overview_zoom']) : 6;
        
        // Feature toggles - use $parse_bool for correct "1"/"0" handling
        $sanitized['enable_clustering'] = isset($settings['enable_clustering']) ? 
            $parse_bool($settings['enable_clustering']) : true;
        
        $sanitized['enable_fullscreen'] = isset($settings['enable_fullscreen']) ? 
            $parse_bool($settings['enable_fullscreen']) : true;
        
        $sanitized['enable_geolocation'] = isset($settings['enable_geolocation']) ? 
            $parse_bool($settings['enable_geolocation']) : true;
        
        $sanitized['enable_routing'] = isset($settings['enable_routing']) ? 
            $parse_bool($settings['enable_routing']) : true;
        
        $sanitized['enable_search'] = isset($settings['enable_search']) ? 
            $parse_bool($settings['enable_search']) : true;
        
        $sanitized['enable_marker_thumbnails'] = isset($settings['enable_marker_thumbnails']) ? 
            $parse_bool($settings['enable_marker_thumbnails']) : true;
        
        $sanitized['show_upcoming_events'] = isset($settings['show_upcoming_events']) ? 
            $parse_bool($settings['show_upcoming_events']) : true;
        
        // Popup style
        $sanitized['popup_style'] = isset($settings['popup_style']) ? 
            sanitize_text_field($settings['popup_style']) : 'rich';
        
        return $sanitized;
    }
    
    /**
     * Shortcode: Locations Overview Map
     * 
     * @param array $atts
     * @return string
     */
    public function shortcode_locations_map($atts) {
        // Determine correct style based on provider
        $provider = $this->get_setting('provider', 'osm');
        $default_style = ($provider === 'google') 
            ? $this->get_setting('google_map_style', 'default')
            : $this->get_setting('map_style', 'default');
        
        $atts = shortcode_atts(array(
            'height'            => '500px',
            'style'             => $default_style,
            'clustering'        => $this->get_setting('enable_clustering', true),
            'fullscreen'        => $this->get_setting('enable_fullscreen', true),
            'geolocation'       => $this->get_setting('enable_geolocation', true),
            'search'            => $this->get_setting('enable_search', true),
            'marker_thumbnails' => $this->get_setting('enable_marker_thumbnails', true),
            'filter'            => 'true',
            'category'          => '',
            'city'              => '',
            'limit'             => 0,
            'class'             => '',
        ), $atts, 'ensemble_locations_map');
        
        // Get all locations with coordinates
        $locations = $this->get_all_locations_data(array(
            'category' => $atts['category'],
            'city'     => $atts['city'],
            'limit'    => intval($atts['limit']),
        ));
        
        if (empty($locations)) {
            return '<p class="es-map-no-locations">' . __('No locations found.', 'ensemble') . '</p>';
        }
        
        // Get filter options
        $filters = $this->get_location_filters($locations);
        
        return $this->load_template('locations-map', array(
            'locations'         => $locations,
            'filters'           => $filters,
            'map_id'            => 'es-locations-map-' . uniqid(),
            'height'            => $atts['height'],
            'style'             => $atts['style'],
            'clustering'        => filter_var($atts['clustering'], FILTER_VALIDATE_BOOLEAN),
            'fullscreen'        => filter_var($atts['fullscreen'], FILTER_VALIDATE_BOOLEAN),
            'geolocation'       => filter_var($atts['geolocation'], FILTER_VALIDATE_BOOLEAN),
            'search'            => filter_var($atts['search'], FILTER_VALIDATE_BOOLEAN),
            'marker_thumbnails' => filter_var($atts['marker_thumbnails'], FILTER_VALIDATE_BOOLEAN),
            'show_filter'       => filter_var($atts['filter'], FILTER_VALIDATE_BOOLEAN),
            'extra_class'       => sanitize_html_class($atts['class']),
        ));
    }
    
    /**
     * Get all locations with coordinates
     * 
     * @param array $args
     * @return array
     */
    public function get_all_locations_data($args = array()) {
        $defaults = array(
            'category' => '',
            'city'     => '',
            'limit'    => 0,
        );
        $args = wp_parse_args($args, $defaults);
        
        // Query locations
        $query_args = array(
            'post_type'      => 'ensemble_location',
            'post_status'    => 'publish',
            'posts_per_page' => $args['limit'] > 0 ? $args['limit'] : -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        );
        
        // Filter by city if specified
        if (!empty($args['city'])) {
            $query_args['meta_query'][] = array(
                'key'     => 'city',
                'value'   => $args['city'],
                'compare' => 'LIKE',
            );
        }
        
        // Filter by category if specified
        if (!empty($args['category'])) {
            $query_args['tax_query'][] = array(
                'taxonomy' => 'ensemble_location_category',
                'field'    => 'slug',
                'terms'    => explode(',', $args['category']),
            );
        }
        
        $locations_query = new WP_Query($query_args);
        $locations = array();
        
        if ($locations_query->have_posts()) {
            while ($locations_query->have_posts()) {
                $locations_query->the_post();
                $location_id = get_the_ID();
                
                $lat = $this->get_location_field($location_id, 'latitude');
                $lng = $this->get_location_field($location_id, 'longitude');
                
                // Skip locations without coordinates
                if (empty($lat) || empty($lng)) {
                    continue;
                }
                
                // Get location type/category
                $location_type = 'default';
                $categories = get_the_terms($location_id, 'ensemble_location_category');
                $category_names = array();
                if ($categories && !is_wp_error($categories)) {
                    $category_names = wp_list_pluck($categories, 'name');
                    $location_type = sanitize_title($categories[0]->name);
                    
                    // Map to known marker icons
                    if (!isset($this->marker_icons[$location_type])) {
                        $location_type = 'default';
                    }
                }
                
                // Get featured image
                $thumbnail = '';
                if (has_post_thumbnail($location_id)) {
                    $thumbnail = get_the_post_thumbnail_url($location_id, 'medium');
                }
                
                // Get city
                $city = $this->get_location_field($location_id, 'city');
                
                // Count upcoming events
                $upcoming_events = $this->get_location_upcoming_events_count($location_id);
                
                $locations[] = array(
                    'id'              => $location_id,
                    'name'            => get_the_title(),
                    'slug'            => get_post_field('post_name', $location_id),
                    'url'             => get_permalink($location_id),
                    'latitude'        => floatval($lat),
                    'longitude'       => floatval($lng),
                    'address'         => $this->get_location_address_string_by_id($location_id),
                    'city'            => $city,
                    'type'            => $location_type,
                    'categories'      => $category_names,
                    'thumbnail'       => $thumbnail,
                    'upcoming_events' => $upcoming_events,
                    'marker_icon'     => $this->marker_icons[$location_type] ?? $this->marker_icons['default'],
                );
            }
            wp_reset_postdata();
        }
        
        return $locations;
    }
    
    /**
     * Get upcoming events count for a location
     * 
     * @param int $location_id
     * @return int
     */
    private function get_location_upcoming_events_count($location_id) {
        $post_type = function_exists('ensemble_get_post_type') ? ensemble_get_post_type() : 'ensemble_event';
        
        $events = new WP_Query(array(
            'post_type'      => $post_type,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_query'     => array(
                'relation' => 'AND',
                array(
                    'key'     => '_event_location_id',
                    'value'   => $location_id,
                    'compare' => '=',
                ),
                array(
                    'key'     => '_event_start_date',
                    'value'   => current_time('Y-m-d'),
                    'compare' => '>=',
                    'type'    => 'DATE',
                ),
            ),
        ));
        
        return $events->found_posts;
    }
    
    /**
     * Get filter options from locations
     * 
     * @param array $locations
     * @return array
     */
    private function get_location_filters($locations) {
        $filters = array(
            'cities'     => array(),
            'categories' => array(),
        );
        
        foreach ($locations as $location) {
            // Cities
            if (!empty($location['city']) && !in_array($location['city'], $filters['cities'])) {
                $filters['cities'][] = $location['city'];
            }
            
            // Categories
            foreach ($location['categories'] as $cat) {
                if (!in_array($cat, $filters['categories'])) {
                    $filters['categories'][] = $cat;
                }
            }
        }
        
        sort($filters['cities']);
        sort($filters['categories']);
        
        return $filters;
    }
    
    /**
     * AJAX: Get all locations
     */
    public function ajax_get_all_locations() {
        check_ajax_referer('ensemble_maps', 'nonce');
        
        $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
        $city = isset($_POST['city']) ? sanitize_text_field($_POST['city']) : '';
        
        $locations = $this->get_all_locations_data(array(
            'category' => $category,
            'city'     => $city,
        ));
        
        wp_send_json_success(array('locations' => $locations));
    }
    
    /**
     * AJAX: Get upcoming events for a location
     */
    public function ajax_get_location_events() {
        check_ajax_referer('ensemble_maps', 'nonce');
        
        $location_id = isset($_POST['location_id']) ? intval($_POST['location_id']) : 0;
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 5;
        
        if (!$location_id) {
            wp_send_json_error(array('message' => __('Location ID erforderlich', 'ensemble')));
        }
        
        $post_type = function_exists('ensemble_get_post_type') ? ensemble_get_post_type() : 'post';
        
        // Get the correct meta key for location
        $location_meta_key = 'event_location';
        if (function_exists('ensemble_meta_key')) {
            $location_meta_key = ensemble_meta_key('location');
        }
        
        // Get the correct meta key for date
        $date_meta_key = function_exists('ensemble_get_date_meta_key') ? ensemble_get_date_meta_key() : 'event_date';
        
        // Build meta query with multiple possible location keys
        $meta_query = array(
            'relation' => 'AND',
            array(
                'relation' => 'OR',
                array(
                    'key'     => $location_meta_key,
                    'value'   => $location_id,
                    'compare' => '=',
                ),
                array(
                    'key'     => '_event_location_id',
                    'value'   => $location_id,
                    'compare' => '=',
                ),
                array(
                    'key'     => 'event_location',
                    'value'   => $location_id,
                    'compare' => '=',
                ),
            ),
        );
        
        // Add date filter for upcoming events
        $today = current_time('Y-m-d');
        $meta_query[] = array(
            'relation' => 'OR',
            array(
                'key'     => $date_meta_key,
                'value'   => $today,
                'compare' => '>=',
                'type'    => 'DATE',
            ),
            array(
                'key'     => '_event_start_date',
                'value'   => $today,
                'compare' => '>=',
                'type'    => 'DATE',
            ),
            array(
                'key'     => 'event_date',
                'value'   => $today,
                'compare' => '>=',
                'type'    => 'DATE',
            ),
        );
        
        $events_query = new WP_Query(array(
            'post_type'      => $post_type,
            'post_status'    => 'publish',
            'posts_per_page' => $limit,
            'orderby'        => 'meta_value',
            'order'          => 'ASC',
            'meta_key'       => $date_meta_key,
            'meta_query'     => $meta_query,
        ));
        
        $events = array();
        
        if ($events_query->have_posts()) {
            while ($events_query->have_posts()) {
                $events_query->the_post();
                $event_id = get_the_ID();
                
                // Try multiple date keys
                $start_date = get_post_meta($event_id, $date_meta_key, true);
                if (empty($start_date)) {
                    $start_date = get_post_meta($event_id, '_event_start_date', true);
                }
                if (empty($start_date)) {
                    $start_date = get_post_meta($event_id, 'event_date', true);
                }
                
                $start_time = get_post_meta($event_id, 'event_time', true);
                if (empty($start_time)) {
                    $start_time = get_post_meta($event_id, '_event_start_time', true);
                }
                
                $formatted = '';
                if (!empty($start_date)) {
                    $formatted = date_i18n(get_option('date_format'), strtotime($start_date));
                    if (!empty($start_time)) {
                        $formatted .= ' ' . $start_time;
                    }
                }
                
                $events[] = array(
                    'id'         => $event_id,
                    'title'      => get_the_title(),
                    'url'        => get_permalink($event_id),
                    'date'       => $start_date,
                    'time'       => $start_time,
                    'formatted'  => $formatted,
                );
            }
            wp_reset_postdata();
        }
        
        wp_send_json_success(array('events' => $events));
    }
    
    /**
     * AJAX: Get directions (proxy for OSRM)
     */
    public function ajax_get_directions() {
        check_ajax_referer('ensemble_maps', 'nonce');
        
        $from_lat = isset($_POST['from_lat']) ? floatval($_POST['from_lat']) : 0;
        $from_lng = isset($_POST['from_lng']) ? floatval($_POST['from_lng']) : 0;
        $to_lat = isset($_POST['to_lat']) ? floatval($_POST['to_lat']) : 0;
        $to_lng = isset($_POST['to_lng']) ? floatval($_POST['to_lng']) : 0;
        
        if (!$from_lat || !$from_lng || !$to_lat || !$to_lng) {
            wp_send_json_error(array('message' => __('Koordinaten erforderlich', 'ensemble')));
        }
        
        // Use OSRM for routing
        $url = sprintf(
            'https://router.project-osrm.org/route/v1/driving/%s,%s;%s,%s?overview=full&geometries=geojson&steps=true',
            $from_lng, $from_lat, $to_lng, $to_lat
        );
        
        $response = wp_remote_get($url, array(
            'timeout' => 10,
            'headers' => array(
                'User-Agent' => 'Ensemble WordPress Plugin',
            ),
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => __('Routing-Anfrage fehlgeschlagen', 'ensemble')));
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (empty($data['routes'])) {
            wp_send_json_error(array('message' => __('No route found', 'ensemble')));
        }
        
        $route = $data['routes'][0];
        
        wp_send_json_success(array(
            'distance' => round($route['distance'] / 1000, 1), // km
            'duration' => round($route['duration'] / 60), // minutes
            'geometry' => $route['geometry'],
            'steps'    => $route['legs'][0]['steps'] ?? array(),
        ));
    }
    
    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        register_rest_route('ensemble/v1', '/locations', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'rest_get_locations'),
            'permission_callback' => '__return_true',
        ));
        
        register_rest_route('ensemble/v1', '/locations/(?P<id>\d+)/events', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'rest_get_location_events'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'id' => array(
                    'required'          => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    },
                ),
            ),
        ));
    }
    
    /**
     * REST: Get all locations
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function rest_get_locations($request) {
        $locations = $this->get_all_locations_data(array(
            'category' => $request->get_param('category') ?? '',
            'city'     => $request->get_param('city') ?? '',
            'limit'    => $request->get_param('limit') ?? 0,
        ));
        
        return new WP_REST_Response($locations, 200);
    }
    
    /**
     * REST: Get location events
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function rest_get_location_events($request) {
        $location_id = $request->get_param('id');
        // Re-use the same logic as AJAX
        $_POST['location_id'] = $location_id;
        $_POST['limit'] = $request->get_param('limit') ?? 5;
        
        // Call the existing method but capture result
        $post_type = function_exists('ensemble_get_post_type') ? ensemble_get_post_type() : 'ensemble_event';
        
        $events_query = new WP_Query(array(
            'post_type'      => $post_type,
            'post_status'    => 'publish',
            'posts_per_page' => 5,
            'orderby'        => 'meta_value',
            'order'          => 'ASC',
            'meta_key'       => '_event_start_date',
            'meta_query'     => array(
                'relation' => 'AND',
                array(
                    'key'     => '_event_location_id',
                    'value'   => $location_id,
                    'compare' => '=',
                ),
                array(
                    'key'     => '_event_start_date',
                    'value'   => current_time('Y-m-d'),
                    'compare' => '>=',
                    'type'    => 'DATE',
                ),
            ),
        ));
        
        $events = array();
        
        if ($events_query->have_posts()) {
            while ($events_query->have_posts()) {
                $events_query->the_post();
                $event_id = get_the_ID();
                
                $start_date = get_post_meta($event_id, '_event_start_date', true);
                
                $events[] = array(
                    'id'        => $event_id,
                    'title'     => get_the_title(),
                    'url'       => get_permalink($event_id),
                    'date'      => $start_date,
                    'formatted' => date_i18n(get_option('date_format'), strtotime($start_date)),
                );
            }
            wp_reset_postdata();
        }
        
        return new WP_REST_Response($events, 200);
    }
    
    /**
     * Get map styles
     * 
     * @return array
     */
    public function get_map_styles() {
        return $this->map_styles;
    }
    
    /**
     * Get marker icons
     * 
     * @return array
     */
    public function get_marker_icons() {
        return $this->marker_icons;
    }
    
    /**
     * Invalidate category-based location cache
     * 
     * Called when an event is saved to ensure map data is fresh.
     * Clears all category location transients.
     * 
     * @since 2.9.0
     * @param int $post_id The saved event ID.
     * @return void
     */
    public function invalidate_category_cache($post_id) {
        // Skip autosaves and revisions
        if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
            return;
        }
        
        // Only process for configured event post type
        $event_post_type = function_exists('ensemble_get_post_type') ? ensemble_get_post_type() : 'post';
        if (get_post_type($post_id) !== $event_post_type) {
            return;
        }
        
        // Get event categories
        $categories = wp_get_post_terms($post_id, 'event_category', array('fields' => 'slugs'));
        
        if (empty($categories) || is_wp_error($categories)) {
            return;
        }
        
        // Delete cache for each category
        foreach ($categories as $slug) {
            $cache_key = 'ensemble_map_locations_cat_' . md5($slug);
            delete_transient($cache_key);
        }
        
        // Also delete cache for category IDs
        $category_ids = wp_get_post_terms($post_id, 'event_category', array('fields' => 'ids'));
        if (!is_wp_error($category_ids)) {
            foreach ($category_ids as $id) {
                $cache_key = 'ensemble_map_locations_cat_' . md5(strval($id));
                delete_transient($cache_key);
            }
        }
        
        // Log in debug mode
        if (function_exists('ensemble_log')) {
            ensemble_log('Map category cache invalidated', array(
                'event_id'   => $post_id,
                'categories' => $categories,
            ));
        }
    }
}
