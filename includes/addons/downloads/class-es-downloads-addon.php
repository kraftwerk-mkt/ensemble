<?php
/**
 * Ensemble Downloads Add-on
 * 
 * Zentrales Download-Management für Konferenzen und Events
 * Unterstützt Präsentationen, CVs, Handouts, Videos und mehr
 * 
 * @package Ensemble
 * @subpackage Addons
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class ES_Downloads_Addon extends ES_Addon_Base {
    
    /**
     * Addon slug
     */
    protected $slug = 'downloads';
    
    /**
     * Addon Name
     */
    protected $name = 'Downloads';
    
    /**
     * Addon Version
     */
    protected $version = '1.0.0';
    
    /**
     * Download Manager instance
     */
    private $download_manager;
    
    /**
     * Default download types
     */
    private $default_types = array();
    
    /**
     * Initialize addon
     */
    protected function init() {
        if (empty($this->settings)) {
            $this->settings = $this->get_default_settings();
        } else {
            $this->settings = wp_parse_args($this->settings, $this->get_default_settings());
        }
        
        // Define default download types
        $this->default_types = array(
            'presentation' => array(
                'label'       => __('Presentation / Slides', 'ensemble'),
                'icon'        => 'dashicons-slides',
                'color'       => '#e67e22',
                'extensions'  => array('pptx', 'ppt', 'key', 'pdf'),
            ),
            'cv' => array(
                'label'       => __('CV / Resume', 'ensemble'),
                'icon'        => 'dashicons-id-alt',
                'color'       => '#3498db',
                'extensions'  => array('pdf', 'docx', 'doc'),
            ),
            'handout' => array(
                'label'       => __('Handout / Worksheet', 'ensemble'),
                'icon'        => 'dashicons-media-document',
                'color'       => '#2ecc71',
                'extensions'  => array('pdf', 'docx', 'doc', 'xlsx', 'xls'),
            ),
            'video' => array(
                'label'       => __('Video Recording', 'ensemble'),
                'icon'        => 'dashicons-video-alt3',
                'color'       => '#e74c3c',
                'extensions'  => array('mp4', 'webm', 'mov', 'avi'),
            ),
            'photo' => array(
                'label'       => __('Press Photos', 'ensemble'),
                'icon'        => 'dashicons-camera',
                'color'       => '#9b59b6',
                'extensions'  => array('jpg', 'jpeg', 'png', 'webp', 'zip'),
            ),
            'package' => array(
                'label'       => __('Material Package', 'ensemble'),
                'icon'        => 'dashicons-archive',
                'color'       => '#1abc9c',
                'extensions'  => array('zip', 'rar', '7z'),
            ),
            'other' => array(
                'label'       => __('Other', 'ensemble'),
                'icon'        => 'dashicons-download',
                'color'       => '#95a5a6',
                'extensions'  => array('*'),
            ),
        );
        
        // Include download manager
        require_once $this->get_addon_path() . 'includes/class-download-manager.php';
        $this->download_manager = new ES_Download_Manager();
    }
    
    /**
     * Register hooks
     */
    protected function register_hooks() {
        // Register CPT and Taxonomy
        add_action('init', array($this, 'register_post_type'), 5);
        add_action('init', array($this, 'register_taxonomy'), 5);
        add_action('init', array($this, 'register_shortcodes'));
        
        // Admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'), 20);
        
        // AJAX handlers
        add_action('wp_ajax_es_get_downloads', array($this, 'ajax_get_downloads'));
        add_action('wp_ajax_es_get_download', array($this, 'ajax_get_download'));
        add_action('wp_ajax_es_save_download', array($this, 'ajax_save_download'));
        add_action('wp_ajax_es_delete_download', array($this, 'ajax_delete_download'));
        add_action('wp_ajax_es_track_download', array($this, 'ajax_track_download'));
        add_action('wp_ajax_nopriv_es_track_download', array($this, 'ajax_track_download'));
        add_action('wp_ajax_ensemble_search_posts', array($this, 'ajax_search_posts'));
        add_action('wp_ajax_es_bulk_download_zip', array($this, 'ajax_bulk_download_zip'));
        add_action('wp_ajax_es_bulk_upload_file', array($this, 'ajax_bulk_upload_file'));
        
        // Auto-display on events/artists/locations
        if ($this->get_setting('auto_display_events')) {
            $position = $this->get_setting('event_position', 'after_content');
            $hook = $this->get_hook_for_position($position, 'event');
            $this->register_template_hook($hook, array($this, 'render_event_downloads'), 25);
        }
        
        if ($this->get_setting('auto_display_artists')) {
            $position = $this->get_setting('artist_position', 'after_content');
            $hook = $this->get_hook_for_position($position, 'artist');
            $this->register_template_hook($hook, array($this, 'render_artist_downloads'), 25);
        }
        
        if ($this->get_setting('auto_display_locations')) {
            $position = $this->get_setting('location_position', 'after_content');
            $hook = $this->get_hook_for_position($position, 'location');
            $this->register_template_hook($hook, array($this, 'render_location_downloads'), 25);
        }
        
        // Enqueue assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Download handler
        add_action('init', array($this, 'handle_download_request'));
    }
    
    /**
     * Get default settings
     */
    public function get_default_settings() {
        return array(
            'enabled'               => true,
            // Display settings
            'default_style'         => 'grid', // grid, list, compact
            'show_file_size'        => true,
            'show_download_count'   => false,
            'show_file_type'        => true,
            'show_date'             => true,
            // Access control
            'require_login'         => false,
            'allowed_roles'         => array(),
            // Event integration
            'auto_display_events'   => true,
            'event_position'        => 'after_content',
            'event_title'           => __('Downloads & Material', 'ensemble'),
            // Artist/Speaker integration
            'auto_display_artists'  => true,
            'artist_position'       => 'after_content',
            'artist_title'          => __('Downloads', 'ensemble'),
            // Location/Venue integration
            'auto_display_locations' => true,
            'location_position'      => 'after_content',
            'location_title'         => __('Downloads', 'ensemble'),
            // Time-based availability
            'enable_scheduling'     => true,
            // Tracking
            'track_downloads'       => true,
            // Custom types
            'custom_types'          => array(),
            // Type colors (empty = use defaults)
            'type_colors'           => array(),
        );
    }
    
    /**
     * Get setting value
     */
    public function get_setting($key, $default = null) {
        $settings = $this->get_settings();
        if (isset($settings[$key])) {
            return $settings[$key];
        }
        $defaults = $this->get_default_settings();
        return isset($defaults[$key]) ? $defaults[$key] : $default;
    }
    
    /**
     * Get hook name for position
     */
    private function get_hook_for_position($position, $context = 'event') {
        $hooks = array(
            'event' => array(
                'before_content' => 'ensemble_before_event',
                'after_content'  => 'ensemble_after_description',
                'after_artists'  => 'ensemble_after_location',
                'footer'         => 'ensemble_after_event',
            ),
            'artist' => array(
                'before_content' => 'ensemble_before_artist',
                'after_content'  => 'ensemble_after_artist_content',
                'after_events'   => 'ensemble_after_artist_events',
                'footer'         => 'ensemble_after_artist',
            ),
            'location' => array(
                'before_content' => 'ensemble_before_location',
                'after_content'  => 'ensemble_after_location_content',
                'after_events'   => 'ensemble_after_location_events',
                'footer'         => 'ensemble_after_location',
            ),
        );
        
        $context_hooks = isset($hooks[$context]) ? $hooks[$context] : $hooks['event'];
        return isset($context_hooks[$position]) ? $context_hooks[$position] : $context_hooks['after_content'];
    }
    
    /**
     * Register download post type
     */
    public function register_post_type() {
        register_post_type('ensemble_download', array(
            'labels' => array(
                'name'               => __('Downloads', 'ensemble'),
                'singular_name'      => __('Download', 'ensemble'),
                'add_new'            => __('Add New', 'ensemble'),
                'add_new_item'       => __('Add New Download', 'ensemble'),
                'edit_item'          => __('Edit Download', 'ensemble'),
                'new_item'           => __('New Download', 'ensemble'),
                'view_item'          => __('View Download', 'ensemble'),
                'search_items'       => __('Search Downloads', 'ensemble'),
                'not_found'          => __('No downloads found', 'ensemble'),
                'not_found_in_trash' => __('No downloads found in trash', 'ensemble'),
            ),
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => false, // We use custom admin
            'show_in_menu'       => false,
            'query_var'          => false,
            'rewrite'            => false,
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array('title'),
        ));
    }
    
    /**
     * Register download type taxonomy
     */
    public function register_taxonomy() {
        // Check if already registered
        if (taxonomy_exists('ensemble_download_type')) {
            return;
        }
        
        register_taxonomy('ensemble_download_type', 'ensemble_download', array(
            'labels' => array(
                'name'              => __('Download-Typen', 'ensemble'),
                'singular_name'     => __('Download-Typ', 'ensemble'),
                'search_items'      => __('Typen durchsuchen', 'ensemble'),
                'all_items'         => __('Alle Typen', 'ensemble'),
                'edit_item'         => __('Typ bearbeiten', 'ensemble'),
                'update_item'       => __('Typ aktualisieren', 'ensemble'),
                'add_new_item'      => __('Neuen Typ hinzufügen', 'ensemble'),
                'new_item_name'     => __('Neuer Typ-Name', 'ensemble'),
                'menu_name'         => __('Download-Typen', 'ensemble'),
            ),
            'hierarchical'      => false,
            'show_ui'           => false,
            'show_admin_column' => false,
            'query_var'         => false,
            'rewrite'           => false,
        ));
        
        // Ensure default types exist
        $this->ensure_default_types();
    }
    
    /**
     * Ensure taxonomy is registered (call before saving)
     */
    public function ensure_taxonomy_registered() {
        if (!taxonomy_exists('ensemble_download_type')) {
            $this->register_taxonomy();
        }
    }
    
    /**
     * Ensure default download types exist
     */
    private function ensure_default_types() {
        foreach ($this->default_types as $slug => $type) {
            // Check if term exists by slug
            $term = get_term_by('slug', $slug, 'ensemble_download_type');
            
            if (!$term) {
                // Create term with proper label and slug
                $result = wp_insert_term(
                    $type['label'], 
                    'ensemble_download_type', 
                    array(
                        'slug' => $slug,
                        'description' => isset($type['description']) ? $type['description'] : '',
                    )
                );
                
                if (is_wp_error($result)) {
                    error_log('Ensemble Downloads: Failed to create type term "' . $slug . '": ' . $result->get_error_message());
                }
            }
        }
    }
    
    /**
     * Get all download types
     */
    public function get_download_types() {
        $types = $this->default_types;
        
        // Merge custom types from settings
        $custom_types = $this->get_setting('custom_types', array());
        if (!empty($custom_types)) {
            $types = array_merge($types, $custom_types);
        }
        
        // Apply saved colors from settings
        $saved_colors = $this->get_setting('type_colors', array());
        if (!empty($saved_colors)) {
            foreach ($saved_colors as $slug => $color) {
                if (isset($types[$slug]) && !empty($color)) {
                    $types[$slug]['color'] = $color;
                }
            }
        }
        
        return apply_filters('ensemble_download_types', $types);
    }
    
    /**
     * Get download type info
     */
    public function get_type_info($type_slug) {
        $types = $this->get_download_types();
        return isset($types[$type_slug]) ? $types[$type_slug] : $types['other'];
    }
    
    /**
     * Register shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('ensemble_downloads', array($this, 'shortcode_downloads'));
        add_shortcode('ensemble_downloads_archive', array($this, 'shortcode_downloads_archive'));
    }
    
    /**
     * Downloads shortcode
     * 
     * [ensemble_downloads]
     * [ensemble_downloads speaker="123"]
     * [ensemble_downloads event="456"]
     * [ensemble_downloads type="presentation"]
     * [ensemble_downloads style="grid" columns="3"]
     */
    public function shortcode_downloads($atts) {
        $atts = shortcode_atts(array(
            'speaker'    => '',
            'artist'     => '', // Alias for speaker
            'event'      => '',
            'location'   => '',
            'type'       => '',
            'style'      => $this->get_setting('default_style', 'grid'),
            'columns'    => 3,
            'limit'      => -1,
            'title'      => '',
            'show_empty' => 'no',
            'orderby'    => 'date',
            'order'      => 'DESC',
            'class'      => '',
        ), $atts, 'ensemble_downloads');
        
        // Artist alias
        if (empty($atts['speaker']) && !empty($atts['artist'])) {
            $atts['speaker'] = $atts['artist'];
        }
        
        // Build query args
        $args = array(
            'limit'   => intval($atts['limit']),
            'orderby' => $atts['orderby'],
            'order'   => $atts['order'],
        );
        
        if (!empty($atts['speaker'])) {
            $args['artist_id'] = intval($atts['speaker']);
        }
        
        if (!empty($atts['event'])) {
            $args['event_id'] = intval($atts['event']);
        }
        
        if (!empty($atts['location'])) {
            $args['location_id'] = intval($atts['location']);
        }
        
        if (!empty($atts['type'])) {
            $args['type'] = $atts['type'];
        }
        
        // Get downloads
        $downloads = $this->download_manager->get_downloads($args);
        
        // Check if empty
        if (empty($downloads) && $atts['show_empty'] !== 'yes') {
            return '';
        }
        
        // Load template
        return $this->load_template('downloads-' . $atts['style'], array(
            'downloads' => $downloads,
            'atts'      => $atts,
            'addon'     => $this,
        ));
    }
    
    /**
     * Downloads Archive shortcode - grouped display
     * 
     * [ensemble_downloads_archive]
     * [ensemble_downloads_archive group_by="event"]
     * [ensemble_downloads_archive group_by="speaker" style="accordion"]
     * [ensemble_downloads_archive group_by="type" style="tabs"]
     * [ensemble_downloads_archive group_by="location" events="123,456"]
     */
    public function shortcode_downloads_archive($atts) {
        $atts = shortcode_atts(array(
            'group_by'      => 'event',      // event, speaker, location, type
            'style'         => 'accordion',  // accordion, tabs, list, grid
            'events'        => '',           // Filter by event IDs
            'speakers'      => '',           // Filter by speaker IDs
            'locations'     => '',           // Filter by location IDs
            'types'         => '',           // Filter by type slugs
            'show_empty'    => 'no',         // Show empty groups
            'show_count'    => 'yes',        // Show download count per group
            'expanded'      => 'first',      // first, all, none
            'columns'       => 3,            // For grid style within groups
            'title'         => '',           // Archive title
            'orderby'       => 'title',      // Group ordering
            'order'         => 'ASC',
            'download_order'=> 'date',       // Downloads within group ordering
            'class'         => '',
        ), $atts, 'ensemble_downloads_archive');
        
        // Get all downloads first
        $all_downloads = $this->download_manager->get_downloads(array(
            'limit' => -1,
        ));
        
        if (empty($all_downloads)) {
            if ($atts['show_empty'] !== 'yes') {
                return '';
            }
        }
        
        // Build groups based on group_by
        $groups = $this->build_download_groups($all_downloads, $atts);
        
        // Filter groups if specific IDs provided
        $groups = $this->filter_groups($groups, $atts);
        
        // Remove empty groups if not showing
        if ($atts['show_empty'] !== 'yes') {
            $groups = array_filter($groups, function($group) {
                return !empty($group['downloads']);
            });
        }
        
        // Sort groups
        $groups = $this->sort_groups($groups, $atts['orderby'], $atts['order']);
        
        if (empty($groups) && $atts['show_empty'] !== 'yes') {
            return '';
        }
        
        // Load template
        return $this->load_template('downloads-archive', array(
            'groups'    => $groups,
            'atts'      => $atts,
            'addon'     => $this,
        ));
    }
    
    /**
     * Build download groups
     */
    private function build_download_groups($downloads, $atts) {
        $groups = array();
        $group_by = $atts['group_by'];
        
        foreach ($downloads as $download) {
            switch ($group_by) {
                case 'event':
                    if (!empty($download['events'])) {
                        foreach ($download['events'] as $event) {
                            $key = 'event_' . $event['id'];
                            if (!isset($groups[$key])) {
                                $groups[$key] = array(
                                    'id'        => $event['id'],
                                    'title'     => $event['title'],
                                    'type'      => 'event',
                                    'permalink' => get_permalink($event['id']),
                                    'downloads' => array(),
                                );
                            }
                            $groups[$key]['downloads'][] = $download;
                        }
                    } else {
                        // Ungrouped downloads
                        if (!isset($groups['ungrouped'])) {
                            $groups['ungrouped'] = array(
                                'id'        => 0,
                                'title'     => __('Other Downloads', 'ensemble'),
                                'type'      => 'ungrouped',
                                'permalink' => '',
                                'downloads' => array(),
                            );
                        }
                        $groups['ungrouped']['downloads'][] = $download;
                    }
                    break;
                    
                case 'speaker':
                case 'artist':
                    if (!empty($download['artists'])) {
                        foreach ($download['artists'] as $artist) {
                            $key = 'artist_' . $artist['id'];
                            if (!isset($groups[$key])) {
                                $groups[$key] = array(
                                    'id'        => $artist['id'],
                                    'title'     => $artist['title'],
                                    'type'      => 'speaker',
                                    'permalink' => get_permalink($artist['id']),
                                    'thumbnail' => get_the_post_thumbnail_url($artist['id'], 'thumbnail'),
                                    'downloads' => array(),
                                );
                            }
                            $groups[$key]['downloads'][] = $download;
                        }
                    } else {
                        if (!isset($groups['ungrouped'])) {
                            $groups['ungrouped'] = array(
                                'id'        => 0,
                                'title'     => __('General Downloads', 'ensemble'),
                                'type'      => 'ungrouped',
                                'permalink' => '',
                                'downloads' => array(),
                            );
                        }
                        $groups['ungrouped']['downloads'][] = $download;
                    }
                    break;
                    
                case 'location':
                    if (!empty($download['locations'])) {
                        foreach ($download['locations'] as $location) {
                            $key = 'location_' . $location['id'];
                            if (!isset($groups[$key])) {
                                $groups[$key] = array(
                                    'id'        => $location['id'],
                                    'title'     => $location['title'],
                                    'type'      => 'location',
                                    'permalink' => get_permalink($location['id']),
                                    'downloads' => array(),
                                );
                            }
                            $groups[$key]['downloads'][] = $download;
                        }
                    } else {
                        if (!isset($groups['ungrouped'])) {
                            $groups['ungrouped'] = array(
                                'id'        => 0,
                                'title'     => __('Other Downloads', 'ensemble'),
                                'type'      => 'ungrouped',
                                'permalink' => '',
                                'downloads' => array(),
                            );
                        }
                        $groups['ungrouped']['downloads'][] = $download;
                    }
                    break;
                    
                case 'type':
                    $type_slug = $download['type_slug'] ?: 'other';
                    $type_info = $this->get_type_info($type_slug);
                    $key = 'type_' . $type_slug;
                    
                    if (!isset($groups[$key])) {
                        $groups[$key] = array(
                            'id'        => $type_slug,
                            'title'     => $type_info['label'],
                            'type'      => 'download_type',
                            'icon'      => $type_info['icon'],
                            'color'     => $type_info['color'],
                            'permalink' => '',
                            'downloads' => array(),
                        );
                    }
                    $groups[$key]['downloads'][] = $download;
                    break;
            }
        }
        
        return $groups;
    }
    
    /**
     * Filter groups by specific IDs
     */
    private function filter_groups($groups, $atts) {
        $group_by = $atts['group_by'];
        
        // Check if specific filter is applied
        $filter_ids = array();
        
        switch ($group_by) {
            case 'event':
                if (!empty($atts['events'])) {
                    $filter_ids = array_map('absint', explode(',', $atts['events']));
                }
                break;
            case 'speaker':
            case 'artist':
                if (!empty($atts['speakers'])) {
                    $filter_ids = array_map('absint', explode(',', $atts['speakers']));
                }
                break;
            case 'location':
                if (!empty($atts['locations'])) {
                    $filter_ids = array_map('absint', explode(',', $atts['locations']));
                }
                break;
            case 'type':
                if (!empty($atts['types'])) {
                    $filter_ids = array_map('trim', explode(',', $atts['types']));
                }
                break;
        }
        
        if (empty($filter_ids)) {
            return $groups;
        }
        
        // Filter groups
        return array_filter($groups, function($group) use ($filter_ids, $group_by) {
            if ($group['type'] === 'ungrouped') {
                return false; // Always hide ungrouped when filtering
            }
            
            if ($group_by === 'type') {
                return in_array($group['id'], $filter_ids);
            }
            
            return in_array($group['id'], $filter_ids);
        });
    }
    
    /**
     * Sort groups
     */
    private function sort_groups($groups, $orderby, $order) {
        $order_multiplier = ($order === 'DESC') ? -1 : 1;
        
        uasort($groups, function($a, $b) use ($orderby, $order_multiplier) {
            // Keep ungrouped at the end
            if ($a['type'] === 'ungrouped') return 1;
            if ($b['type'] === 'ungrouped') return -1;
            
            switch ($orderby) {
                case 'count':
                    return (count($a['downloads']) - count($b['downloads'])) * $order_multiplier;
                case 'title':
                default:
                    return strcmp($a['title'], $b['title']) * $order_multiplier;
            }
        });
        
        return $groups;
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'ensemble',
            __('Downloads', 'ensemble'),
            __('Downloads', 'ensemble'),
            'manage_options',
            'ensemble-downloads',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        // Get download types for the form
        $download_types = $this->get_download_types();
        
        // Get linked post types
        $event_post_type = ensemble_get_post_type();
        $artist_post_type = 'ensemble_artist';
        $location_post_type = 'ensemble_location';
        
        include $this->get_addon_path() . 'templates/admin-downloads.php';
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        if (!$this->should_load_assets()) {
            return;
        }
        
        wp_enqueue_style(
            'es-downloads',
            $this->get_addon_url() . 'assets/css/downloads.css',
            array(),
            $this->version
        );
        
        wp_enqueue_script(
            'es-downloads',
            $this->get_addon_url() . 'assets/js/downloads-frontend.js',
            array('jquery'),
            $this->version,
            true
        );
        
        wp_localize_script('es-downloads', 'es_downloads', array(
            'ajax_url'       => admin_url('admin-ajax.php'),
            'nonce'          => wp_create_nonce('ensemble_ajax'),
            'track_enabled'  => $this->get_setting('track_downloads'),
            'require_login'  => $this->get_setting('require_login'),
            'is_logged_in'   => is_user_logged_in(),
            'login_url'      => wp_login_url(get_permalink()),
            'i18n' => array(
                'login_required' => __('Please log in to download this file.', 'ensemble'),
                'download_error' => __('Download error. Please try again.', 'ensemble'),
            ),
        ));
    }
    
    /**
     * Check if assets should be loaded
     */
    private function should_load_assets() {
        global $post;
        
        // Always load on ensemble pages
        if (function_exists('ensemble_is_ensemble_page') && ensemble_is_ensemble_page()) {
            return true;
        }
        
        // Check for shortcode
        if ($post && has_shortcode($post->post_content, 'ensemble_downloads')) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Load on Downloads admin page
        $is_downloads_page = strpos($hook, 'ensemble-downloads') !== false;
        
        // Load on Wizard page for integration
        $is_wizard_page = isset($_GET['page']) && in_array($_GET['page'], array('ensemble', 'ensemble-wizard'));
        
        // Load on Artist Manager page
        $is_artist_page = isset($_GET['page']) && $_GET['page'] === 'ensemble-artists';
        
        // Load on Location Manager page
        $is_location_page = isset($_GET['page']) && $_GET['page'] === 'ensemble-locations';
        
        if (!$is_downloads_page && !$is_wizard_page && !$is_artist_page && !$is_location_page) {
            return;
        }
        
        wp_enqueue_media();
        
        // Ensure admin-unified.css is loaded
        wp_enqueue_style('ensemble-admin-unified');
        
        wp_enqueue_style(
            'es-downloads-admin',
            $this->get_addon_url() . 'assets/css/downloads-admin.css',
            array('ensemble-admin-unified'),
            $this->version
        );
        
        // Load Select2 if not already loaded
        if (!wp_script_is('select2', 'enqueued')) {
            wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');
            wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array('jquery'), '4.1.0', true);
        }
        
        wp_enqueue_script(
            'es-downloads-admin',
            $this->get_addon_url() . 'assets/js/downloads-admin.js',
            array('jquery', 'wp-util', 'select2'),
            $this->version,
            true
        );
        
        wp_localize_script('es-downloads-admin', 'es_downloads_admin', array(
            'ajax_url'       => admin_url('admin-ajax.php'),
            'nonce'          => wp_create_nonce('ensemble_ajax'),
            'download_types' => $this->get_download_types(),
            'is_wizard'      => $is_wizard_page,
            'is_artist'      => $is_artist_page,
            'is_location'    => $is_location_page,
            'i18n' => array(
                'confirm_delete'  => __('Are you sure you want to delete this download?', 'ensemble'),
                'save_success'    => __('Download saved successfully!', 'ensemble'),
                'delete_success'  => __('Download deleted.', 'ensemble'),
                'error'           => __('An error occurred.', 'ensemble'),
                'select_file'     => __('Select File', 'ensemble'),
                'change_file'     => __('Change File', 'ensemble'),
                'no_file'         => __('No file selected', 'ensemble'),
            ),
        ));
    }
    
    /**
     * Handle direct download requests
     */
    public function handle_download_request() {
        if (!isset($_GET['es_download']) || empty($_GET['es_download'])) {
            return;
        }
        
        $download_id = absint($_GET['es_download']);
        if (!$download_id) {
            return;
        }
        
        // Check access
        if (!$this->check_download_access($download_id)) {
            wp_die(__('You do not have access to this file.', 'ensemble'), __('Access Denied', 'ensemble'), array('response' => 403));
        }
        
        // Get download
        $download = $this->download_manager->get_download($download_id);
        if (!$download) {
            wp_die(__('Download not found.', 'ensemble'), __('Not Found', 'ensemble'), array('response' => 404));
        }
        
        // Check availability
        if (!$this->download_manager->is_download_available($download_id)) {
            wp_die(__('This download is currently not available.', 'ensemble'), __('Not Available', 'ensemble'), array('response' => 403));
        }
        
        // Track download
        if ($this->get_setting('track_downloads')) {
            $this->download_manager->increment_download_count($download_id);
        }
        
        // Get file path
        $file_id = $download['file_id'];
        $file_path = get_attached_file($file_id);
        
        if (!$file_path || !file_exists($file_path)) {
            wp_die(__('Datei nicht gefunden.', 'ensemble'), __('Nicht gefunden', 'ensemble'), array('response' => 404));
        }
        
        // Serve file
        $filename = basename($file_path);
        $mime_type = mime_content_type($file_path);
        
        header('Content-Type: ' . $mime_type);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($file_path));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        readfile($file_path);
        exit;
    }
    
    /**
     * Check download access
     */
    public function check_download_access($download_id) {
        // Public access
        if (!$this->get_setting('require_login')) {
            return true;
        }
        
        // Must be logged in
        if (!is_user_logged_in()) {
            return false;
        }
        
        // Check role restrictions
        $allowed_roles = $this->get_setting('allowed_roles', array());
        if (!empty($allowed_roles)) {
            $user = wp_get_current_user();
            $user_roles = $user->roles;
            
            if (empty(array_intersect($user_roles, $allowed_roles))) {
                return false;
            }
        }
        
        return apply_filters('ensemble_download_access', true, $download_id, get_current_user_id());
    }
    
    /**
     * AJAX: Get downloads
     */
    public function ajax_get_downloads() {
        check_ajax_referer('ensemble_ajax', 'nonce');
        
        $args = array();
        
        if (!empty($_POST['artist_id'])) {
            $args['artist_id'] = absint($_POST['artist_id']);
        }
        
        if (!empty($_POST['event_id'])) {
            $args['event_id'] = absint($_POST['event_id']);
        }
        
        if (!empty($_POST['type'])) {
            $args['type'] = sanitize_text_field($_POST['type']);
        }
        
        if (!empty($_POST['search'])) {
            $args['search'] = sanitize_text_field($_POST['search']);
        }
        
        $downloads = $this->download_manager->get_downloads($args);
        
        wp_send_json_success(array(
            'downloads' => $downloads,
            'total'     => count($downloads),
        ));
    }
    
    /**
     * AJAX: Get single download
     */
    public function ajax_get_download() {
        check_ajax_referer('ensemble_ajax', 'nonce');
        
        $download_id = isset($_POST['download_id']) ? absint($_POST['download_id']) : 0;
        if (!$download_id) {
            wp_send_json_error(array('message' => __('Invalid download ID', 'ensemble')));
        }
        
        $download = $this->download_manager->get_download($download_id);
        
        if (!$download) {
            wp_send_json_error(array('message' => __('Download not found', 'ensemble')));
        }
        
        wp_send_json_success(array('download' => $download));
    }
    
    /**
     * AJAX: Save download
     */
    public function ajax_save_download() {
        check_ajax_referer('ensemble_ajax', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'ensemble')));
        }
        
        // Ensure taxonomy is registered before saving
        $this->ensure_taxonomy_registered();
        
        $data = array(
            'download_id'    => isset($_POST['download_id']) ? absint($_POST['download_id']) : 0,
            'title'          => isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '',
            'description'    => isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '',
            'file_id'        => isset($_POST['file_id']) ? absint($_POST['file_id']) : 0,
            'type'           => isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'other',
            'artists'        => isset($_POST['artists']) ? array_map('absint', (array) $_POST['artists']) : array(),
            'events'         => isset($_POST['events']) ? array_map('absint', (array) $_POST['events']) : array(),
            'locations'      => isset($_POST['locations']) ? array_map('absint', (array) $_POST['locations']) : array(),
            'available_from' => isset($_POST['available_from']) ? sanitize_text_field($_POST['available_from']) : '',
            'available_until'=> isset($_POST['available_until']) ? sanitize_text_field($_POST['available_until']) : '',
            'require_login'  => isset($_POST['require_login']) && $_POST['require_login'],
            'menu_order'     => isset($_POST['menu_order']) ? absint($_POST['menu_order']) : 0,
        );
        
        $result = $this->download_manager->save_download($data);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success(array(
            'message'     => __('Download saved successfully!', 'ensemble'),
            'download_id' => $result,
        ));
    }
    
    /**
     * AJAX: Delete download
     */
    public function ajax_delete_download() {
        check_ajax_referer('ensemble_ajax', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'ensemble')));
        }
        
        $download_id = isset($_POST['download_id']) ? absint($_POST['download_id']) : 0;
        if (!$download_id) {
            wp_send_json_error(array('message' => __('Invalid download ID', 'ensemble')));
        }
        
        $result = $this->download_manager->delete_download($download_id);
        
        if (!$result) {
            wp_send_json_error(array('message' => __('Delete failed', 'ensemble')));
        }
        
        wp_send_json_success(array('message' => __('Download deleted.', 'ensemble')));
    }
    
    /**
     * AJAX: Track download
     */
    public function ajax_track_download() {
        check_ajax_referer('ensemble_ajax', 'nonce');
        
        if (!$this->get_setting('track_downloads')) {
            wp_send_json_success();
            return;
        }
        
        $download_id = isset($_POST['download_id']) ? absint($_POST['download_id']) : 0;
        if ($download_id) {
            $this->download_manager->increment_download_count($download_id);
        }
        
        wp_send_json_success();
    }
    
    /**
     * AJAX: Bulk download as ZIP
     */
    public function ajax_bulk_download_zip() {
        // Allow both GET and POST
        $nonce = isset($_REQUEST['nonce']) ? $_REQUEST['nonce'] : '';
        
        if (!wp_verify_nonce($nonce, 'ensemble_ajax')) {
            wp_die(__('Security check failed', 'ensemble'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'ensemble'));
        }
        
        // Get filter parameters
        $args = array();
        
        if (!empty($_REQUEST['search'])) {
            $args['search'] = sanitize_text_field($_REQUEST['search']);
        }
        
        if (!empty($_REQUEST['type'])) {
            $args['type'] = sanitize_text_field($_REQUEST['type']);
        }
        
        if (!empty($_REQUEST['event_id'])) {
            $args['event_id'] = absint($_REQUEST['event_id']);
        }
        
        if (!empty($_REQUEST['artist_id'])) {
            $args['artist_id'] = absint($_REQUEST['artist_id']);
        }
        
        if (!empty($_REQUEST['location_id'])) {
            $args['location_id'] = absint($_REQUEST['location_id']);
        }
        
        // Get downloads
        $downloads = $this->download_manager->get_downloads($args);
        
        if (empty($downloads)) {
            wp_die(__('No downloads found', 'ensemble'));
        }
        
        // Create ZIP
        $zip = new ZipArchive();
        $upload_dir = wp_upload_dir();
        $zip_filename = 'ensemble-downloads-' . date('Y-m-d-His') . '.zip';
        $zip_path = $upload_dir['basedir'] . '/' . $zip_filename;
        
        if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            wp_die(__('Could not create ZIP file', 'ensemble'));
        }
        
        $files_added = 0;
        
        foreach ($downloads as $download) {
            $file_id = isset($download['file_id']) ? $download['file_id'] : 0;
            
            if (!$file_id) {
                continue;
            }
            
            $file_path = get_attached_file($file_id);
            
            if (!$file_path || !file_exists($file_path)) {
                continue;
            }
            
            // Create filename with type prefix for organization
            $type_slug = isset($download['type_slug']) ? $download['type_slug'] : 'other';
            $file_extension = pathinfo($file_path, PATHINFO_EXTENSION);
            $safe_title = sanitize_file_name($download['title']);
            $zip_entry_name = ucfirst($type_slug) . '/' . $safe_title . '.' . $file_extension;
            
            // Handle duplicates
            $counter = 1;
            $original_name = $zip_entry_name;
            while ($zip->locateName($zip_entry_name) !== false) {
                $zip_entry_name = str_replace('.' . $file_extension, '-' . $counter . '.' . $file_extension, $original_name);
                $counter++;
            }
            
            $zip->addFile($file_path, $zip_entry_name);
            $files_added++;
        }
        
        $zip->close();
        
        if ($files_added === 0) {
            @unlink($zip_path);
            wp_die(__('No files could be added to ZIP', 'ensemble'));
        }
        
        // Send ZIP file
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zip_filename . '"');
        header('Content-Length: ' . filesize($zip_path));
        header('Pragma: no-cache');
        header('Expires: 0');
        
        readfile($zip_path);
        
        // Clean up
        @unlink($zip_path);
        
        exit;
    }
    
    /**
     * AJAX: Bulk upload single file
     * Handles file upload and download creation in one request
     */
    public function ajax_bulk_upload_file() {
        check_ajax_referer('ensemble_ajax', 'nonce');
        
        if (!current_user_can('upload_files')) {
            wp_send_json_error(array('message' => __('Permission denied', 'ensemble')));
        }
        
        // Ensure taxonomy is registered before saving
        $this->ensure_taxonomy_registered();
        
        // Check if file was uploaded
        if (empty($_FILES['file'])) {
            wp_send_json_error(array('message' => __('No file uploaded', 'ensemble')));
        }
        
        // Get file data
        $file = $_FILES['file'];
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error_messages = array(
                UPLOAD_ERR_INI_SIZE   => __('File exceeds upload_max_filesize', 'ensemble'),
                UPLOAD_ERR_FORM_SIZE  => __('File exceeds MAX_FILE_SIZE', 'ensemble'),
                UPLOAD_ERR_PARTIAL    => __('File was only partially uploaded', 'ensemble'),
                UPLOAD_ERR_NO_FILE    => __('No file was uploaded', 'ensemble'),
                UPLOAD_ERR_NO_TMP_DIR => __('Missing temporary folder', 'ensemble'),
                UPLOAD_ERR_CANT_WRITE => __('Failed to write file to disk', 'ensemble'),
                UPLOAD_ERR_EXTENSION  => __('Upload stopped by extension', 'ensemble'),
            );
            $message = isset($error_messages[$file['error']]) ? $error_messages[$file['error']] : __('Unknown upload error', 'ensemble');
            wp_send_json_error(array('message' => $message));
        }
        
        // Include required files for media handling
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        // Handle the upload
        $upload = wp_handle_upload($file, array('test_form' => false));
        
        if (isset($upload['error'])) {
            wp_send_json_error(array('message' => $upload['error']));
        }
        
        // Create attachment
        $attachment = array(
            'post_mime_type' => $upload['type'],
            'post_title'     => sanitize_file_name(pathinfo($file['name'], PATHINFO_FILENAME)),
            'post_content'   => '',
            'post_status'    => 'inherit',
        );
        
        $attachment_id = wp_insert_attachment($attachment, $upload['file']);
        
        if (is_wp_error($attachment_id)) {
            wp_send_json_error(array('message' => $attachment_id->get_error_message()));
        }
        
        // Generate attachment metadata
        $attachment_data = wp_generate_attachment_metadata($attachment_id, $upload['file']);
        wp_update_attachment_metadata($attachment_id, $attachment_data);
        
        // Now create the download entry
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : $attachment['post_title'];
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'other';
        
        // Parse JSON arrays
        $events = array();
        $artists = array();
        $locations = array();
        
        if (!empty($_POST['events'])) {
            $events = json_decode(stripslashes($_POST['events']), true);
            if (!is_array($events)) $events = array();
        }
        
        if (!empty($_POST['artists'])) {
            $artists = json_decode(stripslashes($_POST['artists']), true);
            if (!is_array($artists)) $artists = array();
        }
        
        if (!empty($_POST['locations'])) {
            $locations = json_decode(stripslashes($_POST['locations']), true);
            if (!is_array($locations)) $locations = array();
        }
        
        // Create download
        $download_data = array(
            'title'       => $title,
            'description' => '',
            'type'        => $type,
            'file_id'     => $attachment_id,
            'events'      => array_map('absint', $events),
            'artists'     => array_map('absint', $artists),
            'locations'   => array_map('absint', $locations),
            'menu_order'  => 0,
        );
        
        $download_id = $this->download_manager->save_download($download_data);
        
        if (!$download_id) {
            wp_send_json_error(array('message' => __('Failed to create download entry', 'ensemble')));
        }
        
        wp_send_json_success(array(
            'message'     => __('File uploaded successfully', 'ensemble'),
            'download_id' => $download_id,
            'file_id'     => $attachment_id,
        ));
    }
    
    /**
     * AJAX: Search posts for Select2
     * Used in admin modal for linking downloads to events/artists/locations
     */
    public function ajax_search_posts() {
        check_ajax_referer('ensemble_ajax', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Permission denied', 'ensemble')));
        }
        
        // Support both GET and POST, and both 'q' and 'search' parameters
        $search = '';
        if (isset($_REQUEST['q'])) {
            $search = sanitize_text_field($_REQUEST['q']);
        } elseif (isset($_REQUEST['search'])) {
            $search = sanitize_text_field($_REQUEST['search']);
        } elseif (isset($_REQUEST['term'])) {
            $search = sanitize_text_field($_REQUEST['term']);
        }
        
        $post_type = isset($_REQUEST['post_type']) ? sanitize_text_field($_REQUEST['post_type']) : 'post';
        $page = isset($_REQUEST['page']) ? absint($_REQUEST['page']) : 1;
        $per_page = 20;
        
        // Validate post type
        $allowed_types = array('post', 'page', 'ensemble_artist', 'ensemble_location');
        $event_post_type = ensemble_get_post_type();
        $allowed_types[] = $event_post_type;
        
        if (!in_array($post_type, $allowed_types)) {
            wp_send_json_error(array('message' => __('Invalid post type', 'ensemble')));
        }
        
        $args = array(
            'post_type'      => $post_type,
            'post_status'    => 'publish',
            'posts_per_page' => $per_page,
            'paged'          => $page,
            'orderby'        => 'title',
            'order'          => 'ASC',
        );
        
        if (!empty($search)) {
            $args['s'] = $search;
        }
        
        $query = new WP_Query($args);
        $results = array();
        
        if ($query->have_posts()) {
            foreach ($query->posts as $post) {
                $results[] = array(
                    'id'   => $post->ID,
                    'text' => $post->post_title,
                );
            }
        }
        
        // Return in Select2 expected format
        wp_send_json(array(
            'results'    => $results,
            'pagination' => array(
                'more' => ($page * $per_page) < $query->found_posts,
            ),
        ));
    }
    
    /**
     * Render downloads on event single page
     */
    public function render_event_downloads($event_id = null) {
        if (!$event_id) {
            $event_id = get_the_ID();
        }
        
        $downloads = $this->download_manager->get_downloads(array(
            'event_id' => $event_id,
        ));
        
        if (empty($downloads)) {
            return;
        }
        
        $title = $this->get_setting('event_title', __('Downloads & Material', 'ensemble'));
        $style = $this->get_setting('default_style', 'grid');
        
        echo '<div class="es-downloads-section es-downloads-event-section">';
        
        if ($title) {
            echo '<h3 class="es-downloads-section__title">' . esc_html($title) . '</h3>';
        }
        
        echo $this->load_template('downloads-' . $style, array(
            'downloads' => $downloads,
            'atts'      => array('style' => $style, 'columns' => 3),
            'addon'     => $this,
        ));
        
        echo '</div>';
    }
    
    /**
     * Render downloads on artist single page
     */
    public function render_artist_downloads($artist_id = null) {
        if (!$artist_id) {
            $artist_id = get_the_ID();
        }
        
        $downloads = $this->download_manager->get_downloads(array(
            'artist_id' => $artist_id,
        ));
        
        if (empty($downloads)) {
            return;
        }
        
        $title = $this->get_setting('artist_title', __('Downloads', 'ensemble'));
        $style = $this->get_setting('default_style', 'grid');
        
        echo '<div class="es-downloads-section es-downloads-artist-section">';
        
        if ($title) {
            echo '<h3 class="es-downloads-section__title">' . esc_html($title) . '</h3>';
        }
        
        echo $this->load_template('downloads-' . $style, array(
            'downloads' => $downloads,
            'atts'      => array('style' => $style, 'columns' => 3),
            'addon'     => $this,
        ));
        
        echo '</div>';
    }
    
    /**
     * Render downloads on location single page
     */
    public function render_location_downloads($location_id = null) {
        if (!$location_id) {
            $location_id = get_the_ID();
        }
        
        $downloads = $this->download_manager->get_downloads(array(
            'location_id' => $location_id,
        ));
        
        if (empty($downloads)) {
            return;
        }
        
        $title = $this->get_setting('location_title', __('Downloads', 'ensemble'));
        $style = $this->get_setting('default_style', 'grid');
        
        echo '<div class="es-downloads-section es-downloads-location-section">';
        
        if ($title) {
            echo '<h3 class="es-downloads-section__title">' . esc_html($title) . '</h3>';
        }
        
        echo $this->load_template('downloads-' . $style, array(
            'downloads' => $downloads,
            'atts'      => array('style' => $style, 'columns' => 3),
            'addon'     => $this,
        ));
        
        echo '</div>';
    }
    
    /**
     * Render settings page
     */
    public function render_settings() {
        ob_start();
        include $this->get_addon_path() . 'templates/settings.php';
        return ob_get_clean();
    }
    
    /**
     * Sanitize settings
     */
    public function sanitize_settings($settings) {
        $sanitized = array();
        
        $sanitized['enabled'] = !empty($settings['enabled']);
        $sanitized['default_style'] = isset($settings['default_style']) ? sanitize_text_field($settings['default_style']) : 'grid';
        $sanitized['show_file_size'] = !empty($settings['show_file_size']);
        $sanitized['show_download_count'] = !empty($settings['show_download_count']);
        $sanitized['show_file_type'] = !empty($settings['show_file_type']);
        $sanitized['show_date'] = !empty($settings['show_date']);
        $sanitized['require_login'] = !empty($settings['require_login']);
        $sanitized['allowed_roles'] = isset($settings['allowed_roles']) ? array_map('sanitize_text_field', (array) $settings['allowed_roles']) : array();
        $sanitized['auto_display_events'] = !empty($settings['auto_display_events']);
        $sanitized['event_position'] = isset($settings['event_position']) ? sanitize_text_field($settings['event_position']) : 'after_content';
        $sanitized['event_title'] = isset($settings['event_title']) ? sanitize_text_field($settings['event_title']) : '';
        $sanitized['auto_display_artists'] = !empty($settings['auto_display_artists']);
        $sanitized['artist_position'] = isset($settings['artist_position']) ? sanitize_text_field($settings['artist_position']) : 'after_content';
        $sanitized['artist_title'] = isset($settings['artist_title']) ? sanitize_text_field($settings['artist_title']) : '';
        $sanitized['enable_scheduling'] = !empty($settings['enable_scheduling']);
        $sanitized['track_downloads'] = !empty($settings['track_downloads']);
        
        return $sanitized;
    }
    
    /**
     * Get download manager
     */
    public function get_download_manager() {
        return $this->download_manager;
    }
    
    /**
     * Format file size
     */
    public function format_file_size($bytes) {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' Bytes';
        }
    }
    
    /**
     * Get file extension icon class
     */
    public function get_file_icon($extension) {
        $icons = array(
            'pdf'  => 'dashicons-pdf',
            'doc'  => 'dashicons-media-document',
            'docx' => 'dashicons-media-document',
            'xls'  => 'dashicons-media-spreadsheet',
            'xlsx' => 'dashicons-media-spreadsheet',
            'ppt'  => 'dashicons-slides',
            'pptx' => 'dashicons-slides',
            'zip'  => 'dashicons-archive',
            'rar'  => 'dashicons-archive',
            'mp4'  => 'dashicons-video-alt3',
            'webm' => 'dashicons-video-alt3',
            'mov'  => 'dashicons-video-alt3',
            'mp3'  => 'dashicons-format-audio',
            'wav'  => 'dashicons-format-audio',
            'jpg'  => 'dashicons-format-image',
            'jpeg' => 'dashicons-format-image',
            'png'  => 'dashicons-format-image',
            'gif'  => 'dashicons-format-image',
            'webp' => 'dashicons-format-image',
        );
        
        $extension = strtolower($extension);
        return isset($icons[$extension]) ? $icons[$extension] : 'dashicons-download';
    }
}
