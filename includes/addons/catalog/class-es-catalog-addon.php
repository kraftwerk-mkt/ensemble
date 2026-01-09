<?php
/**
 * Ensemble Catalog Add-on
 * 
 * Flexible catalog system for locations and events
 * - Menus / Food & Drinks
 * - Merchandise
 * - Services & Packages
 * - Equipment Rental
 * - Room Rental
 * - Custom catalogs
 *
 * @package Ensemble
 * @subpackage Addons
 * @since 2.9.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class ES_Catalog_Addon extends ES_Addon_Base {
    
    protected $slug = 'catalog';
    protected $name = 'Catalog';
    protected $version = '1.0.0';
    
    const CPT_CATALOG = 'es_catalog';
    const CPT_ITEM = 'es_catalog_item';
    const TAXONOMY_CATEGORY = 'es_catalog_category';
    
    private $catalog_types = array();
    
    protected function init() {
        $this->define_catalog_types();
        $this->log('Catalog add-on initialized');
    }
    
    /**
     * Define available catalog types
     */
    private function define_catalog_types() {
        $this->catalog_types = array(
            'menu' => array(
                'name' => __('Menu', 'ensemble'),
                'icon' => 'utensils',
                'description' => __('Restaurant, Catering, Bistro', 'ensemble'),
                'default_categories' => array('Starters', 'Main Courses', 'Desserts', 'Sides'),
                'item_attributes' => array(
                    'price' => array('type' => 'currency', 'label' => __('Price', 'ensemble'), 'required' => true),
                    'description' => array('type' => 'textarea', 'label' => __('Description', 'ensemble')),
                    'portion_size' => array('type' => 'text', 'label' => __('Portion size', 'ensemble')),
                    'allergens' => array('type' => 'text', 'label' => __('Allergens', 'ensemble'), 'placeholder' => 'A, C, G, L'),
                    'vegetarian' => array('type' => 'toggle', 'label' => __('Vegetarian', 'ensemble')),
                    'vegan' => array('type' => 'toggle', 'label' => __('Vegan', 'ensemble')),
                    'gluten_free' => array('type' => 'toggle', 'label' => __('Gluten-free', 'ensemble')),
                    'spicy' => array('type' => 'select', 'label' => __('Spiciness', 'ensemble'), 'options' => array('' => 'None', '1' => 'Mild', '2' => 'Medium', '3' => 'Hot')),
                    'new' => array('type' => 'toggle', 'label' => __('New', 'ensemble')),
                    'highlight' => array('type' => 'toggle', 'label' => __('Recommendation', 'ensemble')),
                ),
            ),
            'drinks' => array(
                'name' => __('Drinks menu', 'ensemble'),
                'icon' => 'glass',
                'description' => __('Bar, Club, Restaurant', 'ensemble'),
                'default_categories' => array('Beers', 'Wines', 'Cocktails', 'Long Drinks', 'Spirits', 'Soft Drinks', 'Hot Drinks'),
                'item_attributes' => array(
                    'price' => array('type' => 'currency', 'label' => __('Price', 'ensemble'), 'required' => true),
                    'description' => array('type' => 'textarea', 'label' => __('Description', 'ensemble')),
                    'volume' => array('type' => 'text', 'label' => __('Volume', 'ensemble'), 'placeholder' => '0,5L'),
                    'alcohol' => array('type' => 'text', 'label' => __('Alcohol %', 'ensemble')),
                    'allergens' => array('type' => 'text', 'label' => __('Allergens', 'ensemble')),
                    'non_alcoholic' => array('type' => 'toggle', 'label' => __('Non-alcoholic', 'ensemble')),
                    'new' => array('type' => 'toggle', 'label' => __('New', 'ensemble')),
                    'highlight' => array('type' => 'toggle', 'label' => __('Recommendation', 'ensemble')),
                ),
            ),
            'merchandise' => array(
                'name' => __('Merchandise', 'ensemble'),
                'icon' => 'shirt',
                'description' => __('Concerts, Festivals, Clubs', 'ensemble'),
                'default_categories' => array('T-Shirts', 'Hoodies', 'Poster', 'Vinyl / CDs', 'Accessoires'),
                'item_attributes' => array(
                    'price' => array('type' => 'currency', 'label' => __('Price', 'ensemble'), 'required' => true),
                    'description' => array('type' => 'textarea', 'label' => __('Description', 'ensemble')),
                    'sizes' => array('type' => 'checkboxes', 'label' => __('Sizes', 'ensemble'), 'options' => array('XS' => 'XS', 'S' => 'S', 'M' => 'M', 'L' => 'L', 'XL' => 'XL', 'XXL' => 'XXL')),
                    'colors' => array('type' => 'text', 'label' => __('Colors', 'ensemble')),
                    'material' => array('type' => 'text', 'label' => __('Material', 'ensemble')),
                    'stock' => array('type' => 'number', 'label' => __('Stock', 'ensemble')),
                    'new' => array('type' => 'toggle', 'label' => __('New', 'ensemble')),
                    'sale' => array('type' => 'toggle', 'label' => __('Sale', 'ensemble')),
                    'limited' => array('type' => 'toggle', 'label' => __('Limited Edition', 'ensemble')),
                ),
            ),
            'services' => array(
                'name' => __('Services & Packages', 'ensemble'),
                'icon' => 'package',
                'description' => __('VIP packages, extras', 'ensemble'),
                'default_categories' => array('VIP Packages', 'Catering', 'Technical', 'Staff'),
                'item_attributes' => array(
                    'price' => array('type' => 'currency', 'label' => __('Price', 'ensemble'), 'required' => true),
                    'price_type' => array('type' => 'select', 'label' => __('Price Type', 'ensemble'), 'options' => array('fixed' => 'Fixed price', 'per_person' => 'Per Person', 'per_hour' => 'Per Hour', 'on_request' => 'On Request')),
                    'description' => array('type' => 'textarea', 'label' => __('Description', 'ensemble')),
                    'includes' => array('type' => 'textarea', 'label' => __('Included services', 'ensemble')),
                    'min_persons' => array('type' => 'number', 'label' => __('Min. Persons', 'ensemble')),
                    'max_persons' => array('type' => 'number', 'label' => __('Max. Persons', 'ensemble')),
                    'highlight' => array('type' => 'toggle', 'label' => __('Recommendation', 'ensemble')),
                    'popular' => array('type' => 'toggle', 'label' => __('Popular', 'ensemble')),
                ),
            ),
            'equipment' => array(
                'name' => __('Equipment', 'ensemble'),
                'icon' => 'speaker',
                'description' => __('Equipment rental, PA, lighting', 'ensemble'),
                'default_categories' => array('PA Systems', 'Lighting', 'Stage', 'DJ Equipment', 'Decoration'),
                'item_attributes' => array(
                    'price' => array('type' => 'currency', 'label' => __('Price', 'ensemble'), 'required' => true),
                    'price_type' => array('type' => 'select', 'label' => __('Price Type', 'ensemble'), 'options' => array('per_day' => 'Per Day', 'per_weekend' => 'Per Weekend', 'on_request' => 'On Request')),
                    'description' => array('type' => 'textarea', 'label' => __('Description', 'ensemble')),
                    'specs' => array('type' => 'textarea', 'label' => __('Technical Specs', 'ensemble')),
                    'quantity' => array('type' => 'number', 'label' => __('Available', 'ensemble')),
                    'deposit' => array('type' => 'currency', 'label' => __('Deposit', 'ensemble')),
                    'delivery' => array('type' => 'toggle', 'label' => __('Delivery available', 'ensemble')),
                ),
            ),
            'rooms' => array(
                'name' => __('Rooms', 'ensemble'),
                'icon' => 'home',
                'description' => __('Room rental, locations', 'ensemble'),
                'default_categories' => array('Event Spaces', 'Conference Rooms', 'Outdoor Areas'),
                'item_attributes' => array(
                    'price' => array('type' => 'currency', 'label' => __('Price', 'ensemble'), 'required' => true),
                    'price_type' => array('type' => 'select', 'label' => __('Price Type', 'ensemble'), 'options' => array('per_hour' => 'Per Hour', 'per_day' => 'Per Day', 'per_evening' => 'Per Evening', 'on_request' => 'On Request')),
                    'description' => array('type' => 'textarea', 'label' => __('Description', 'ensemble')),
                    'capacity_standing' => array('type' => 'number', 'label' => __('Capacity (standing)', 'ensemble')),
                    'capacity_seated' => array('type' => 'number', 'label' => __('Capacity (seated)', 'ensemble')),
                    'area' => array('type' => 'text', 'label' => __('Area (m²)', 'ensemble')),
                    'amenities' => array('type' => 'checkboxes', 'label' => __('Amenities', 'ensemble'), 'options' => array('projector' => 'Projector', 'sound' => 'Sound system', 'wifi' => 'WiFi', 'kitchen' => 'Kitchen', 'bar' => 'Bar', 'stage' => 'Stage', 'parking' => 'Parking', 'accessible' => 'Accessible')),
                ),
            ),
            'courses' => array(
                'name' => __('Courses & Workshops', 'ensemble'),
                'icon' => 'graduation',
                'description' => __('Workshops, training', 'ensemble'),
                'default_categories' => array('Beginner', 'Advanced', 'Masterclass'),
                'item_attributes' => array(
                    'price' => array('type' => 'currency', 'label' => __('Price', 'ensemble'), 'required' => true),
                    'price_type' => array('type' => 'select', 'label' => __('Price type', 'ensemble'), 'options' => array('per_person' => 'Per person', 'per_group' => 'Per group')),
                    'description' => array('type' => 'textarea', 'label' => __('Description', 'ensemble')),
                    'duration' => array('type' => 'text', 'label' => __('Duration', 'ensemble')),
                    'level' => array('type' => 'select', 'label' => __('Level', 'ensemble'), 'options' => array('beginner' => 'Beginner', 'intermediate' => 'Intermediate', 'advanced' => 'Expert', 'all' => 'All levels')),
                    'max_participants' => array('type' => 'number', 'label' => __('Max. participants', 'ensemble')),
                    'included' => array('type' => 'textarea', 'label' => __('Included in price', 'ensemble')),
                ),
            ),
            'sponsoring' => array(
                'name' => __('Sponsoring packages', 'ensemble'),
                'icon' => 'award',
                'description' => __('Festivals, clubs', 'ensemble'),
                'default_categories' => array('Main sponsors', 'Partners', 'Supporters'),
                'item_attributes' => array(
                    'price' => array('type' => 'currency', 'label' => __('Price', 'ensemble'), 'required' => true),
                    'description' => array('type' => 'textarea', 'label' => __('Description', 'ensemble')),
                    'benefits' => array('type' => 'textarea', 'label' => __('Benefits', 'ensemble')),
                    'visibility' => array('type' => 'checkboxes', 'label' => __('Visibility', 'ensemble'), 'options' => array('logo_stage' => 'Logo on stage', 'logo_print' => 'Logo on print materials', 'logo_website' => 'Logo on website', 'tickets' => 'Free tickets', 'vip' => 'VIP access')),
                    'tickets_included' => array('type' => 'number', 'label' => __('Incl. tickets', 'ensemble')),
                    'exclusive' => array('type' => 'toggle', 'label' => __('Exclusive', 'ensemble')),
                ),
            ),
            'custom' => array(
                'name' => __('Benutzerdefiniert', 'ensemble'),
                'icon' => 'grid',
                'description' => __('Custom Categories', 'ensemble'),
                'default_categories' => array(),
                'item_attributes' => array(
                    'price' => array('type' => 'currency', 'label' => __('Price', 'ensemble')),
                    'description' => array('type' => 'textarea', 'label' => __('Description', 'ensemble')),
                ),
            ),
        );
        
        $this->catalog_types = apply_filters('ensemble_catalog_types', $this->catalog_types);
    }
    
    public function get_catalog_types() {
        return $this->catalog_types;
    }
    
    public function get_catalog_type($type) {
        return $this->catalog_types[$type] ?? $this->catalog_types['custom'];
    }
    
    protected function register_hooks() {
        add_action('init', array($this, 'register_post_types'));
        add_action('init', array($this, 'register_taxonomies'));
        add_action('admin_menu', array($this, 'add_admin_menu'), 25);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        
        // ✅ Template Hooks - Automatische Anzeige in Templates
        $this->register_template_hook('event_catalog', array($this, 'render_event_catalog'), 10);
        $this->register_template_hook('location_catalog', array($this, 'render_location_catalog'), 10);
        
        // AJAX - Catalog
        add_action('wp_ajax_es_catalog_save', array($this, 'ajax_save_catalog'));
        add_action('wp_ajax_es_catalog_delete', array($this, 'ajax_delete_catalog'));
        add_action('wp_ajax_es_catalog_get', array($this, 'ajax_get_catalog'));
        add_action('wp_ajax_es_catalog_list', array($this, 'ajax_list_catalogs'));
        
        // AJAX - Items
        add_action('wp_ajax_es_catalog_item_save', array($this, 'ajax_save_item'));
        add_action('wp_ajax_es_catalog_item_delete', array($this, 'ajax_delete_item'));
        add_action('wp_ajax_es_catalog_item_reorder', array($this, 'ajax_reorder_items'));
        
        // AJAX - Categories
        add_action('wp_ajax_es_catalog_category_save', array($this, 'ajax_save_category'));
        add_action('wp_ajax_es_catalog_category_delete', array($this, 'ajax_delete_category'));
        add_action('wp_ajax_es_catalog_category_reorder', array($this, 'ajax_reorder_categories'));
        
        // CSV Import/Export
        add_action('wp_ajax_es_catalog_import_csv', array($this, 'ajax_import_csv'));
        add_action('wp_ajax_es_catalog_export_csv', array($this, 'ajax_export_csv'));
        
        // Shortcode
        add_shortcode('ensemble_catalog', array($this, 'shortcode_catalog'));
    }
    
    // ========================================
    // TEMPLATE HOOK RENDERERS
    // ========================================
    
    /**
     * Render catalog in event template
     * Called via ensemble_event_catalog hook
     * 
     * @param int $event_id Event ID
     * @param int $location_id Optional location ID for fallback
     */
    public function render_event_catalog($event_id, $location_id = 0) {
        // 1. Search catalog directly on event
        $catalogs = get_posts(array(
            'post_type' => self::CPT_CATALOG,
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_catalog_event',
                    'value' => $event_id,
                    'compare' => '=',
                ),
            ),
        ));
        
        // 2. Fallback: Location's catalog
        if (empty($catalogs) && $location_id > 0) {
            $catalogs = get_posts(array(
                'post_type' => self::CPT_CATALOG,
                'posts_per_page' => -1,
                'meta_query' => array(
                    array(
                        'key' => '_catalog_location',
                        'value' => $location_id,
                        'compare' => '=',
                    ),
                ),
            ));
        }
        
        if (empty($catalogs)) {
            return;
        }
        
        // Frontend Assets laden
        wp_enqueue_style('ensemble-catalog', $this->get_addon_url() . 'assets/catalog-frontend.css', array(), $this->version);
        
        foreach ($catalogs as $catalog) {
            $this->render_catalog_output($catalog->ID);
        }
    }
    
    /**
     * Render catalog in location template
     * Called via ensemble_location_catalog hook
     * 
     * @param int $location_id Location ID
     */
    public function render_location_catalog($location_id) {
        $catalogs = get_posts(array(
            'post_type' => self::CPT_CATALOG,
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_catalog_location',
                    'value' => $location_id,
                    'compare' => '=',
                ),
            ),
        ));
        
        if (empty($catalogs)) {
            return;
        }
        
        // Frontend Assets laden
        wp_enqueue_style('ensemble-catalog', $this->get_addon_url() . 'assets/catalog-frontend.css', array(), $this->version);
        
        foreach ($catalogs as $catalog) {
            $this->render_catalog_output($catalog->ID);
        }
    }
    
    /**
     * Render single catalog output
     * 
     * @param int $catalog_id Catalog ID
     */
    private function render_catalog_output($catalog_id) {
        $catalog = get_post($catalog_id);
        if (!$catalog) return;
        
        $type = get_post_meta($catalog_id, '_catalog_type', true);
        $type_config = $this->get_catalog_type($type);
        $categories = $this->get_catalog_categories($catalog_id);
        $items = $this->get_catalog_items_data($catalog_id);
        
        if (empty($items)) return;
        
        // Group by category
        $by_cat = array();
        foreach ($items as $item) {
            $cid = $item['category_id'] ?: 0;
            if (!isset($by_cat[$cid])) $by_cat[$cid] = array();
            $by_cat[$cid][] = $item;
        }
        
        // Shortcode-Attribute für Template
        $atts = array(
            'layout' => 'list',
            'show_prices' => 'true',
            'show_images' => 'false',
            'show_filter' => 'false',
            'columns' => 1,
        );
        
        include $this->get_addon_path() . 'templates/catalog-display.php';
    }
    
    public function register_post_types() {
        register_post_type(self::CPT_CATALOG, array(
            'labels' => array('name' => __('Catalogs', 'ensemble'), 'singular_name' => __('Catalog', 'ensemble')),
            'public' => false,
            'show_ui' => false,
            'supports' => array('title'),
        ));
        
        register_post_type(self::CPT_ITEM, array(
            'labels' => array('name' => __('Catalog entries', 'ensemble')),
            'public' => false,
            'show_ui' => false,
            'supports' => array('title', 'thumbnail'),
        ));
    }
    
    public function register_taxonomies() {
        register_taxonomy(self::TAXONOMY_CATEGORY, self::CPT_ITEM, array(
            'labels' => array('name' => __('Catalog Categories', 'ensemble')),
            'public' => false,
            'hierarchical' => true,
        ));
    }
    
    public function add_admin_menu() {
        add_submenu_page(
            'ensemble',
            __('Catalogs', 'ensemble'),
            __('Catalogs', 'ensemble'),
            'edit_posts',
            'ensemble-catalog',
            array($this, 'render_admin_page')
        );
    }
    
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'ensemble-catalog') === false) {
            return;
        }
        
        wp_enqueue_media();
        
        wp_enqueue_style('ensemble-catalog-admin', $this->get_addon_url() . 'assets/catalog-admin.css', array('ensemble-admin-unified'), $this->version);
        wp_enqueue_script('ensemble-catalog-admin', $this->get_addon_url() . 'assets/catalog-admin.js', array('jquery', 'jquery-ui-sortable'), $this->version, true);
        
        wp_localize_script('ensemble-catalog-admin', 'ensembleCatalog', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ensemble_catalog'),
            'catalogTypes' => $this->catalog_types,
            'strings' => array(
                'confirmDelete' => __('Really delete?', 'ensemble'),
                'confirmDeleteCatalog' => __('Delete catalog and all entries?', 'ensemble'),
                'saving' => __('Saving...', 'ensemble'),
                'saved' => __('Saved!', 'ensemble'),
                'error' => __('An error occurred', 'ensemble'),
                'noItems' => __('No entries yet', 'ensemble'),
                'uploadImage' => __('Select image', 'ensemble'),
                'removeImage' => __('Remove image', 'ensemble'),
            ),
        ));
    }
    
    public function enqueue_frontend_assets() {
        global $post;
        if (!$post || !has_shortcode($post->post_content, 'ensemble_catalog')) {
            return;
        }
        
        wp_enqueue_style('ensemble-catalog', $this->get_addon_url() . 'assets/catalog-frontend.css', array(), $this->version);
        wp_enqueue_script('ensemble-catalog', $this->get_addon_url() . 'assets/catalog-frontend.js', array('jquery'), $this->version, true);
    }
    
    public function render_admin_page() {
        include $this->get_addon_path() . 'admin/catalog-manager.php';
    }
    
    // ========================================
    // AJAX HANDLERS
    // ========================================
    
    public function ajax_save_catalog() {
        check_ajax_referer('ensemble_catalog', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Permission denied', 'ensemble'));
        }
        
        $catalog_id = intval($_POST['catalog_id'] ?? 0);
        $title = sanitize_text_field($_POST['title'] ?? '');
        $type = sanitize_key($_POST['type'] ?? 'custom');
        $location_id = intval($_POST['location_id'] ?? 0);
        $event_id = intval($_POST['event_id'] ?? 0);
        $settings = $_POST['settings'] ?? array();
        
        if (empty($title)) {
            wp_send_json_error(__('Titel ist erforderlich', 'ensemble'));
        }
        
        $post_data = array(
            'post_title' => $title,
            'post_type' => self::CPT_CATALOG,
            'post_status' => 'publish',
        );
        
        if ($catalog_id > 0) {
            $post_data['ID'] = $catalog_id;
            $catalog_id = wp_update_post($post_data);
        } else {
            $catalog_id = wp_insert_post($post_data);
            
            // Create default categories for new catalog
            $type_config = $this->get_catalog_type($type);
            $order = 0;
            $catalog_id_str = strval($catalog_id); // Als String für Meta-Speicherung
            
            foreach ($type_config['default_categories'] as $cat_name) {
                // Check if term already exists
                $existing = term_exists($cat_name, self::TAXONOMY_CATEGORY);
                
                if ($existing) {
                    // Term exists - use existing one but add new Catalog-ID
                    $term_id = is_array($existing) ? $existing['term_id'] : $existing;
                    // Check if this term is already assigned to a catalog
                    $existing_catalog = get_term_meta($term_id, '_catalog_id', true);
                    if ($existing_catalog) {
                        // Create new term with unique name
                        $unique_name = $cat_name . ' (' . $catalog_id . ')';
                        $term = wp_insert_term($unique_name, self::TAXONOMY_CATEGORY);
                        if (!is_wp_error($term)) {
                            // Set displayed name to the original
                            wp_update_term($term['term_id'], self::TAXONOMY_CATEGORY, array('name' => $cat_name));
                            add_term_meta($term['term_id'], '_catalog_id', $catalog_id_str);
                            add_term_meta($term['term_id'], '_category_order', $order++);
                        }
                    } else {
                        // Term exists but without catalog - assign it
                        add_term_meta($term_id, '_catalog_id', $catalog_id_str);
                        add_term_meta($term_id, '_category_order', $order++);
                    }
                } else {
                    // Create new term
                    $term = wp_insert_term($cat_name, self::TAXONOMY_CATEGORY);
                    if (!is_wp_error($term)) {
                        add_term_meta($term['term_id'], '_catalog_id', $catalog_id_str);
                        add_term_meta($term['term_id'], '_category_order', $order++);
                    }
                }
            }
        }
        
        if (is_wp_error($catalog_id)) {
            wp_send_json_error($catalog_id->get_error_message());
        }
        
        update_post_meta($catalog_id, '_catalog_type', $type);
        update_post_meta($catalog_id, '_catalog_location', $location_id);
        update_post_meta($catalog_id, '_catalog_event', $event_id);
        update_post_meta($catalog_id, '_catalog_settings', $settings);
        
        wp_send_json_success(array('catalog_id' => $catalog_id, 'message' => __('Catalog saved', 'ensemble')));
    }
    
    public function ajax_delete_catalog() {
        check_ajax_referer('ensemble_catalog', 'nonce');
        
        if (!current_user_can('delete_posts')) {
            wp_send_json_error(__('Permission denied', 'ensemble'));
        }
        
        $catalog_id = intval($_POST['catalog_id'] ?? 0);
        
        // Delete items
        $items = get_posts(array('post_type' => self::CPT_ITEM, 'post_parent' => $catalog_id, 'posts_per_page' => -1, 'fields' => 'ids'));
        foreach ($items as $item_id) {
            wp_delete_post($item_id, true);
        }
        
        // Delete categories
        $categories = $this->get_catalog_categories($catalog_id);
        foreach ($categories as $cat) {
            wp_delete_term($cat->term_id, self::TAXONOMY_CATEGORY);
        }
        
        wp_delete_post($catalog_id, true);
        wp_send_json_success(array('message' => __('Catalog deleted', 'ensemble')));
    }
    
    public function ajax_get_catalog() {
        check_ajax_referer('ensemble_catalog', 'nonce');
        
        $catalog_id = intval($_POST['catalog_id'] ?? 0);
        $catalog = get_post($catalog_id);
        
        if (!$catalog || $catalog->post_type !== self::CPT_CATALOG) {
            wp_send_json_error(__('Catalog not found', 'ensemble'));
        }
        
        $type = get_post_meta($catalog_id, '_catalog_type', true);
        
        wp_send_json_success(array(
            'id' => $catalog->ID,
            'title' => $catalog->post_title,
            'type' => $type,
            'location_id' => get_post_meta($catalog_id, '_catalog_location', true),
            'event_id' => get_post_meta($catalog_id, '_catalog_event', true),
            'settings' => get_post_meta($catalog_id, '_catalog_settings', true) ?: array(),
            'categories' => $this->get_catalog_categories_data($catalog_id),
            'items' => $this->get_catalog_items_data($catalog_id),
        ));
    }
    
    public function ajax_list_catalogs() {
        check_ajax_referer('ensemble_catalog', 'nonce');
        
        $args = array('post_type' => self::CPT_CATALOG, 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC');
        
        $location_id = intval($_POST['location_id'] ?? 0);
        $event_id = intval($_POST['event_id'] ?? 0);
        
        if ($location_id > 0) {
            $args['meta_query'][] = array('key' => '_catalog_location', 'value' => $location_id);
        }
        if ($event_id > 0) {
            $args['meta_query'][] = array('key' => '_catalog_event', 'value' => $event_id);
        }
        
        $catalogs = get_posts($args);
        $data = array();
        
        foreach ($catalogs as $catalog) {
            $type = get_post_meta($catalog->ID, '_catalog_type', true);
            $type_config = $this->get_catalog_type($type);
            $item_count = $this->get_item_count($catalog->ID);
            
            $data[] = array(
                'id' => $catalog->ID,
                'title' => $catalog->post_title,
                'type' => $type,
                'type_name' => $type_config['name'],
                'type_icon' => $type_config['icon'],
                'location_id' => get_post_meta($catalog->ID, '_catalog_location', true),
                'event_id' => get_post_meta($catalog->ID, '_catalog_event', true),
                'item_count' => $item_count,
            );
        }
        
        wp_send_json_success($data);
    }
    
    public function ajax_save_item() {
        check_ajax_referer('ensemble_catalog', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Permission denied', 'ensemble'));
        }
        
        $item_id = intval($_POST['item_id'] ?? 0);
        $catalog_id = intval($_POST['catalog_id'] ?? 0);
        $category_id = intval($_POST['category_id'] ?? 0);
        $title = sanitize_text_field($_POST['title'] ?? '');
        $description = wp_kses_post($_POST['description'] ?? '');
        $attributes = $_POST['attributes'] ?? array();
        $order = intval($_POST['order'] ?? 0);
        $image_id = intval($_POST['image_id'] ?? 0);
        
        if (empty($title)) {
            wp_send_json_error(__('Titel ist erforderlich', 'ensemble'));
        }
        
        $post_data = array(
            'post_title' => $title,
            'post_content' => $description,
            'post_type' => self::CPT_ITEM,
            'post_status' => 'publish',
            'post_parent' => $catalog_id,
            'menu_order' => $order,
        );
        
        if ($item_id > 0) {
            $post_data['ID'] = $item_id;
            $item_id = wp_update_post($post_data);
        } else {
            $item_id = wp_insert_post($post_data);
        }
        
        if (is_wp_error($item_id)) {
            wp_send_json_error($item_id->get_error_message());
        }
        
        // Sanitize and save attributes
        $clean_attrs = array();
        foreach ($attributes as $key => $value) {
            $key = sanitize_key($key);
            $clean_attrs[$key] = is_array($value) ? array_map('sanitize_text_field', $value) : sanitize_text_field($value);
        }
        update_post_meta($item_id, '_item_attributes', $clean_attrs);
        
        // Category
        if ($category_id > 0) {
            wp_set_object_terms($item_id, array($category_id), self::TAXONOMY_CATEGORY);
        }
        
        // Image
        if ($image_id > 0) {
            set_post_thumbnail($item_id, $image_id);
        } else {
            delete_post_thumbnail($item_id);
        }
        
        wp_send_json_success(array('item_id' => $item_id, 'message' => __('Item saved', 'ensemble')));
    }
    
    public function ajax_delete_item() {
        check_ajax_referer('ensemble_catalog', 'nonce');
        
        if (!current_user_can('delete_posts')) {
            wp_send_json_error(__('Permission denied', 'ensemble'));
        }
        
        $item_id = intval($_POST['item_id'] ?? 0);
        wp_delete_post($item_id, true);
        wp_send_json_success(array('message' => __('Entry deleted', 'ensemble')));
    }
    
    public function ajax_reorder_items() {
        check_ajax_referer('ensemble_catalog', 'nonce');
        
        $order = $_POST['order'] ?? array();
        foreach ($order as $position => $item_id) {
            wp_update_post(array('ID' => intval($item_id), 'menu_order' => $position));
        }
        
        wp_send_json_success();
    }
    
    public function ajax_save_category() {
        check_ajax_referer('ensemble_catalog', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Permission denied', 'ensemble'));
        }
        
        $category_id = intval($_POST['category_id'] ?? 0);
        $catalog_id = intval($_POST['catalog_id'] ?? 0);
        $catalog_id_str = strval($catalog_id); // Als String für Meta
        $name = sanitize_text_field($_POST['name'] ?? '');
        $color = sanitize_hex_color($_POST['color'] ?? '');
        
        if (empty($name)) {
            wp_send_json_error(__('Name ist erforderlich', 'ensemble'));
        }
        
        if ($category_id > 0) {
            wp_update_term($category_id, self::TAXONOMY_CATEGORY, array('name' => $name));
        } else {
            // Check if term with this name already exists
            $existing = term_exists($name, self::TAXONOMY_CATEGORY);
            if ($existing) {
                // Erstelle mit eindeutigem Slug
                $term = wp_insert_term($name, self::TAXONOMY_CATEGORY, array(
                    'slug' => sanitize_title($name . '-' . $catalog_id)
                ));
            } else {
                $term = wp_insert_term($name, self::TAXONOMY_CATEGORY);
            }
            
            if (!is_wp_error($term)) {
                $category_id = $term['term_id'];
                add_term_meta($category_id, '_catalog_id', $catalog_id_str);
                $categories = $this->get_catalog_categories($catalog_id);
                add_term_meta($category_id, '_category_order', count($categories));
            } else {
                wp_send_json_error($term->get_error_message());
            }
        }
        
        if ($color) {
            update_term_meta($category_id, '_category_color', $color);
        }
        
        wp_send_json_success(array('category_id' => $category_id, 'message' => __('Category saved', 'ensemble')));
    }
    
    public function ajax_delete_category() {
        check_ajax_referer('ensemble_catalog', 'nonce');
        
        if (!current_user_can('delete_posts')) {
            wp_send_json_error(__('Permission denied', 'ensemble'));
        }
        
        $category_id = intval($_POST['category_id'] ?? 0);
        wp_delete_term($category_id, self::TAXONOMY_CATEGORY);
        wp_send_json_success(array('message' => __('Category deleted', 'ensemble')));
    }
    
    public function ajax_reorder_categories() {
        check_ajax_referer('ensemble_catalog', 'nonce');
        
        $order = $_POST['order'] ?? array();
        foreach ($order as $position => $category_id) {
            update_term_meta(intval($category_id), '_category_order', $position);
        }
        
        wp_send_json_success();
    }
    
    public function ajax_import_csv() {
        check_ajax_referer('ensemble_catalog', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Permission denied', 'ensemble'));
        }
        
        $catalog_id = intval($_POST['catalog_id'] ?? 0);
        
        if (!isset($_FILES['csv_file'])) {
            wp_send_json_error(__('No file', 'ensemble'));
        }
        
        $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
        $first_line = fgets($handle);
        rewind($handle);
        $delimiter = strpos($first_line, ';') !== false ? ';' : ',';
        
        $header = array_map('strtolower', array_map('trim', fgetcsv($handle, 0, $delimiter)));
        
        $col_map = array(
            'name' => array('name', 'titel', 'title', 'gericht', 'produkt'),
            'description' => array('description', 'beschreibung'),
            'price' => array('price', 'preis', 'preis (eur)'),
            'category' => array('category', 'kategorie'),
        );
        
        $cols = array();
        foreach ($col_map as $field => $aliases) {
            foreach ($aliases as $alias) {
                $idx = array_search($alias, $header);
                if ($idx !== false) { $cols[$field] = $idx; break; }
            }
        }
        
        if (!isset($cols['name'])) {
            fclose($handle);
            wp_send_json_error(__('Name-Spalte nicht gefunden', 'ensemble'));
        }
        
        $imported = 0;
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (empty($row[$cols['name']])) continue;
            
            $cat_id = 0;
            if (isset($cols['category']) && !empty($row[$cols['category']])) {
                $cat_id = $this->get_or_create_category($catalog_id, trim($row[$cols['category']]));
            }
            
            $attrs = array();
            if (isset($cols['price'])) {
                $price = str_replace(',', '.', preg_replace('/[^0-9.,]/', '', $row[$cols['price']]));
                $attrs['price'] = floatval($price);
            }
            
            $item_id = wp_insert_post(array(
                'post_title' => $row[$cols['name']],
                'post_content' => isset($cols['description']) ? $row[$cols['description']] : '',
                'post_type' => self::CPT_ITEM,
                'post_status' => 'publish',
                'post_parent' => $catalog_id,
            ));
            
            if (!is_wp_error($item_id)) {
                update_post_meta($item_id, '_item_attributes', $attrs);
                if ($cat_id > 0) wp_set_object_terms($item_id, array($cat_id), self::TAXONOMY_CATEGORY);
                $imported++;
            }
        }
        
        fclose($handle);
        wp_send_json_success(array('imported' => $imported, 'message' => sprintf(__('%d entries imported', 'ensemble'), $imported)));
    }
    
    public function ajax_export_csv() {
        check_ajax_referer('ensemble_catalog', 'nonce');
        
        $catalog_id = intval($_GET['catalog_id'] ?? 0);
        $catalog = get_post($catalog_id);
        
        if (!$catalog) wp_die('Not found');
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . sanitize_file_name($catalog->post_title) . '.csv"');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($output, array('Name', 'Description', 'Category', 'Price'), ';');
        
        $items = get_posts(array('post_type' => self::CPT_ITEM, 'post_parent' => $catalog_id, 'posts_per_page' => -1, 'orderby' => 'menu_order', 'order' => 'ASC'));
        
        foreach ($items as $item) {
            $attrs = get_post_meta($item->ID, '_item_attributes', true) ?: array();
            $cats = wp_get_object_terms($item->ID, self::TAXONOMY_CATEGORY, array('fields' => 'names'));
            fputcsv($output, array($item->post_title, $item->post_content, $cats[0] ?? '', $attrs['price'] ?? ''), ';');
        }
        
        fclose($output);
        exit;
    }
    
    // ========================================
    // HELPER METHODS
    // ========================================
    
    public function get_catalog_categories($catalog_id) {
        // Stelle sicher, dass catalog_id ein String ist für den Meta-Vergleich
        $catalog_id = strval($catalog_id);
        
        $terms = get_terms(array(
            'taxonomy' => self::TAXONOMY_CATEGORY,
            'hide_empty' => false,
            'meta_query' => array(
                array(
                    'key' => '_catalog_id',
                    'value' => $catalog_id,
                    'compare' => '=',
                ),
            ),
        ));
        
        if (is_wp_error($terms) || empty($terms)) return array();
        
        usort($terms, function($a, $b) {
            $oa = (int) get_term_meta($a->term_id, '_category_order', true);
            $ob = (int) get_term_meta($b->term_id, '_category_order', true);
            return $oa - $ob;
        });
        
        return $terms;
    }
    
    private function get_catalog_categories_data($catalog_id) {
        $categories = $this->get_catalog_categories($catalog_id);
        $data = array();
        
        foreach ($categories as $cat) {
            $data[] = array(
                'id' => $cat->term_id,
                'name' => $cat->name,
                'order' => (int) get_term_meta($cat->term_id, '_category_order', true),
                'color' => get_term_meta($cat->term_id, '_category_color', true),
            );
        }
        
        return $data;
    }
    
    private function get_catalog_items_data($catalog_id) {
        $items = get_posts(array(
            'post_type' => self::CPT_ITEM,
            'post_parent' => $catalog_id,
            'posts_per_page' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC',
        ));
        
        $data = array();
        foreach ($items as $item) {
            $cats = wp_get_object_terms($item->ID, self::TAXONOMY_CATEGORY, array('fields' => 'ids'));
            $data[] = array(
                'id' => $item->ID,
                'title' => $item->post_title,
                'description' => $item->post_content,
                'category_id' => $cats[0] ?? 0,
                'order' => $item->menu_order,
                'image' => get_the_post_thumbnail_url($item->ID, 'medium'),
                'attributes' => get_post_meta($item->ID, '_item_attributes', true) ?: array(),
            );
        }
        
        return $data;
    }
    
    private function get_item_count($catalog_id) {
        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s AND post_parent = %d AND post_status = 'publish'",
            self::CPT_ITEM, $catalog_id
        ));
    }
    
    private function get_or_create_category($catalog_id, $name) {
        $categories = $this->get_catalog_categories($catalog_id);
        foreach ($categories as $cat) {
            if (strtolower($cat->name) === strtolower($name)) return $cat->term_id;
        }
        
        $term = wp_insert_term($name, self::TAXONOMY_CATEGORY);
        if (is_wp_error($term)) return 0;
        
        add_term_meta($term['term_id'], '_catalog_id', $catalog_id);
        add_term_meta($term['term_id'], '_category_order', count($categories));
        
        return $term['term_id'];
    }
    
    // ========================================
    // SHORTCODE
    // ========================================
    
    public function shortcode_catalog($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
            'location' => 0,
            'event' => 0,
            'layout' => 'list',
            'show_prices' => 'true',
            'show_images' => 'false',
            'show_filter' => 'false',
            'columns' => 1,
        ), $atts);
        
        $catalog_id = intval($atts['id']);
        
        if ($catalog_id <= 0 && intval($atts['location']) > 0) {
            $cats = get_posts(array('post_type' => self::CPT_CATALOG, 'posts_per_page' => 1, 'meta_key' => '_catalog_location', 'meta_value' => intval($atts['location'])));
            if ($cats) $catalog_id = $cats[0]->ID;
        }
        
        if ($catalog_id <= 0 && intval($atts['event']) > 0) {
            $cats = get_posts(array('post_type' => self::CPT_CATALOG, 'posts_per_page' => 1, 'meta_key' => '_catalog_event', 'meta_value' => intval($atts['event'])));
            if ($cats) $catalog_id = $cats[0]->ID;
        }
        
        if ($catalog_id <= 0) return '';
        
        $catalog = get_post($catalog_id);
        if (!$catalog) return '';
        
        $type = get_post_meta($catalog_id, '_catalog_type', true);
        $type_config = $this->get_catalog_type($type);
        $categories = $this->get_catalog_categories($catalog_id);
        $items = $this->get_catalog_items_data($catalog_id);
        
        // Group by category
        $by_cat = array();
        foreach ($items as $item) {
            $cid = $item['category_id'] ?: 0;
            if (!isset($by_cat[$cid])) $by_cat[$cid] = array();
            $by_cat[$cid][] = $item;
        }
        
        ob_start();
        include $this->get_addon_path() . 'templates/catalog-display.php';
        return ob_get_clean();
    }
    
    public function render_settings() {
        return '';
    }
    
    public function sanitize_settings($settings) {
        return $settings;
    }
}
