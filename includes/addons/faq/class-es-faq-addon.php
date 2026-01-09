<?php
/**
 * Ensemble FAQ Add-on
 * 
 * Frequently Asked Questions System für Ensemble
 * Mit animierten Accordions, Layout-Integration und Backend-Verwaltung
 * 
 * @package Ensemble
 * @subpackage Addons
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class ES_FAQ_Addon extends ES_Addon_Base {
    
    /**
     * Addon slug
     */
    protected $slug = 'faq';
    
    /**
     * Addon Name
     */
    protected $name = 'FAQ';
    
    /**
     * Addon Version
     */
    protected $version = '1.0.0';
    
    /**
     * Post Type Name
     */
    const POST_TYPE = 'ensemble_faq';
    
    /**
     * Taxonomy Name
     */
    const TAXONOMY = 'ensemble_faq_category';
    
    /**
     * Initialize addon
     */
    protected function init() {
        if (empty($this->settings)) {
            $this->settings = $this->get_default_settings();
        } else {
            $this->settings = wp_parse_args($this->settings, $this->get_default_settings());
        }
    }
    
    /**
     * Register hooks
     */
    protected function register_hooks() {
        // Register Custom Post Type & Taxonomy
        add_action('init', array($this, 'register_post_type'));
        add_action('init', array($this, 'register_taxonomy'));
        
        // Register Shortcode
        add_shortcode('ensemble_faq', array($this, 'render_shortcode'));
        
        // Enqueue assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Admin menu für FAQ Manager (eigene Seite)
        add_action('admin_menu', array($this, 'add_admin_menu'), 30);
        
        // AJAX handlers
        add_action('wp_ajax_es_faq_get_all', array($this, 'ajax_get_all'));
        add_action('wp_ajax_es_faq_save', array($this, 'ajax_save'));
        add_action('wp_ajax_es_faq_delete', array($this, 'ajax_delete'));
        add_action('wp_ajax_es_faq_create_category', array($this, 'ajax_create_category'));
        add_action('wp_ajax_es_faq_bulk_action', array($this, 'ajax_bulk_action'));
    }
    
    /**
     * Get default settings
     */
    public function get_default_settings() {
        return array(
            'enabled'              => true,
            'animation_speed'      => 300,
            'allow_multiple_open'  => false,
            'expand_first'         => false,
            'show_icon'            => true,
            'icon_position'        => 'right', // left, right
            'icon_type'            => 'chevron', // chevron, plus, arrow
            'layout_style'         => 'cards', // cards, minimal, bordered
            'show_category_filter' => true,
            'show_search'          => true,
            'items_per_page'       => 20,
            'schema_markup'        => true, // Google FAQ Schema
        );
    }
    
    /**
     * Check if addon is active
     */
    public function is_active() {
        return ES_Addon_Manager::is_addon_active($this->slug);
    }
    
    /**
     * Get settings
     */
    public function get_settings() {
        return wp_parse_args($this->settings, $this->get_default_settings());
    }
    
    /**
     * Sanitize settings
     */
    public function sanitize_settings($settings) {
        $defaults = $this->get_default_settings();
        $sanitized = array();
        
        // Integer fields
        $sanitized['animation_speed'] = isset($settings['animation_speed']) 
            ? absint($settings['animation_speed']) 
            : $defaults['animation_speed'];
        $sanitized['items_per_page'] = isset($settings['items_per_page']) 
            ? absint($settings['items_per_page']) 
            : $defaults['items_per_page'];
        
        // Select fields
        $sanitized['icon_position'] = isset($settings['icon_position']) 
            ? sanitize_key($settings['icon_position']) 
            : $defaults['icon_position'];
        $sanitized['icon_type'] = isset($settings['icon_type']) 
            ? sanitize_key($settings['icon_type']) 
            : $defaults['icon_type'];
        $sanitized['layout_style'] = isset($settings['layout_style']) 
            ? sanitize_key($settings['layout_style']) 
            : $defaults['layout_style'];
        
        // Boolean fields
        $boolean_fields = array(
            'enabled', 
            'allow_multiple_open', 
            'expand_first', 
            'show_icon',
            'show_category_filter',
            'show_search',
            'schema_markup'
        );
        
        foreach ($boolean_fields as $field) {
            $sanitized[$field] = $this->sanitize_boolean($settings, $field, $defaults[$field]);
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize boolean value
     */
    private function sanitize_boolean($settings, $key, $default) {
        if (!isset($settings[$key])) {
            return $default;
        }
        
        $value = $settings[$key];
        
        if ($value === 'true' || $value === '1' || $value === 1 || $value === true) {
            return true;
        }
        
        if ($value === 'false' || $value === '0' || $value === 0 || $value === false || $value === '') {
            return false;
        }
        
        return $default;
    }
    
    /**
     * Register Custom Post Type
     */
    public function register_post_type() {
        $labels = array(
            'name'                  => _x('FAQs', 'Post Type General Name', 'ensemble'),
            'singular_name'         => _x('FAQ', 'Post Type Singular Name', 'ensemble'),
            'menu_name'             => __('FAQs', 'ensemble'),
            'all_items'             => __('Alle FAQs', 'ensemble'),
            'add_new'               => __('Neue FAQ', 'ensemble'),
            'add_new_item'          => __('Neue FAQ hinzufügen', 'ensemble'),
            'edit_item'             => __('FAQ bearbeiten', 'ensemble'),
            'new_item'              => __('Neue FAQ', 'ensemble'),
            'view_item'             => __('FAQ ansehen', 'ensemble'),
            'search_items'          => __('FAQs durchsuchen', 'ensemble'),
            'not_found'             => __('Keine FAQs gefunden', 'ensemble'),
            'not_found_in_trash'    => __('Keine FAQs im Papierkorb', 'ensemble'),
        );
        
        $args = array(
            'label'               => __('FAQ', 'ensemble'),
            'labels'              => $labels,
            'supports'            => array('title', 'editor', 'thumbnail', 'page-attributes'),
            'hierarchical'        => false,
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => false, // Wird über Ensemble Admin verwaltet
            'show_in_admin_bar'   => true,
            'show_in_nav_menus'   => false,
            'can_export'          => true,
            'has_archive'         => false,
            'exclude_from_search' => true,
            'publicly_queryable'  => false,
            'capability_type'     => 'post',
            'show_in_rest'        => true,
            'menu_icon'           => 'dashicons-editor-help',
        );
        
        register_post_type(self::POST_TYPE, $args);
    }
    
    /**
     * Register Taxonomy
     */
    public function register_taxonomy() {
        $labels = array(
            'name'              => _x('FAQ Kategorien', 'taxonomy general name', 'ensemble'),
            'singular_name'     => _x('FAQ Kategorie', 'taxonomy singular name', 'ensemble'),
            'search_items'      => __('Kategorien durchsuchen', 'ensemble'),
            'all_items'         => __('Alle Kategorien', 'ensemble'),
            'parent_item'       => __('Übergeordnete Kategorie', 'ensemble'),
            'parent_item_colon' => __('Übergeordnete Kategorie:', 'ensemble'),
            'edit_item'         => __('Kategorie bearbeiten', 'ensemble'),
            'update_item'       => __('Kategorie aktualisieren', 'ensemble'),
            'add_new_item'      => __('Neue Kategorie hinzufügen', 'ensemble'),
            'new_item_name'     => __('Neuer Kategoriename', 'ensemble'),
            'menu_name'         => __('Kategorien', 'ensemble'),
        );
        
        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'faq-category'),
            'show_in_rest'      => true,
        );
        
        register_taxonomy(self::TAXONOMY, self::POST_TYPE, $args);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'ensemble',
            __('FAQ Manager', 'ensemble'),
            __('FAQs', 'ensemble'),
            'edit_posts',
            'ensemble-faq',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        include $this->get_addon_path() . 'templates/admin-manager.php';
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if (!$this->is_active()) {
            return;
        }
        
        // Only on our FAQ Manager page
        if ($hook !== 'ensemble_page_ensemble-faq') {
            return;
        }
        
        wp_enqueue_style(
            'es-faq-admin',
            $this->get_addon_url() . 'assets/faq-admin.css',
            array(),
            $this->version
        );
        
        wp_enqueue_script(
            'es-faq-admin',
            $this->get_addon_url() . 'assets/faq-admin.js',
            array('jquery'),
            $this->version,
            true
        );
        
        wp_localize_script('es-faq-admin', 'esFaqManager', array(
            'nonce'             => wp_create_nonce('es_faq_nonce'),
            'loading'           => __('FAQs werden geladen...', 'ensemble'),
            'saving'            => __('Speichern...', 'ensemble'),
            'save'              => __('FAQ speichern', 'ensemble'),
            'error'             => __('Ein Fehler ist aufgetreten', 'ensemble'),
            'requiredFields'    => __('Bitte fülle alle Pflichtfelder aus', 'ensemble'),
            'addTitle'          => __('Neue FAQ', 'ensemble'),
            'editTitle'         => __('FAQ bearbeiten', 'ensemble'),
            'emptyTitle'        => __('Noch keine FAQs', 'ensemble'),
            'emptyText'         => __('Erstelle deine erste FAQ um loszulegen.', 'ensemble'),
            'addFirst'          => __('Erste FAQ erstellen', 'ensemble'),
            'colQuestion'       => __('Frage', 'ensemble'),
            'colCategory'       => __('Kategorie', 'ensemble'),
            'colActions'        => __('Aktionen', 'ensemble'),
            'edit'              => __('Bearbeiten', 'ensemble'),
            'delete'            => __('Löschen', 'ensemble'),
            'selected'          => __('ausgewählt', 'ensemble'),
            'faqSingular'       => __('FAQ', 'ensemble'),
            'faqPlural'         => __('FAQs', 'ensemble'),
            'expandedHint'      => __('Standardmäßig geöffnet', 'ensemble'),
            'confirmBulkDelete' => __('Möchtest du die ausgewählten FAQs wirklich löschen?', 'ensemble'),
        ));
    }
    
    /**
     * AJAX: Get all FAQs
     */
    public function ajax_get_all() {
        check_ajax_referer('es_faq_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Keine Berechtigung', 'ensemble')));
        }
        
        $args = array(
            'post_type'      => self::POST_TYPE,
            'posts_per_page' => -1,
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
            'post_status'    => 'publish',
        );
        
        $query = new WP_Query($args);
        $faqs = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                
                $categories = wp_get_post_terms($post_id, self::TAXONOMY);
                $cat_data = array();
                
                if (!is_wp_error($categories)) {
                    foreach ($categories as $cat) {
                        $cat_data[] = array(
                            'term_id' => $cat->term_id,
                            'name'    => $cat->name,
                            'slug'    => $cat->slug,
                        );
                    }
                }
                
                $faqs[] = array(
                    'id'         => $post_id,
                    'question'   => get_the_title(),
                    'answer'     => apply_filters('the_content', get_the_content()),
                    'answer_raw' => get_the_content(),
                    'order'      => get_post_field('menu_order', $post_id),
                    'expanded'   => get_post_meta($post_id, '_es_faq_expanded', true) === '1',
                    'categories' => $cat_data,
                );
            }
            wp_reset_postdata();
        }
        
        wp_send_json_success(array('faqs' => $faqs));
    }
    
    /**
     * AJAX: Save FAQ
     */
    public function ajax_save() {
        check_ajax_referer('es_faq_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Keine Berechtigung', 'ensemble')));
        }
        
        $faq_id = isset($_POST['faq_id']) ? intval($_POST['faq_id']) : 0;
        $question = isset($_POST['question']) ? sanitize_text_field($_POST['question']) : '';
        $answer = isset($_POST['answer']) ? wp_kses_post($_POST['answer']) : '';
        $category = isset($_POST['category']) ? intval($_POST['category']) : 0;
        $menu_order = isset($_POST['menu_order']) ? intval($_POST['menu_order']) : 0;
        $expanded = isset($_POST['expanded']) && $_POST['expanded'] == 1;
        
        if (empty($question) || empty($answer)) {
            wp_send_json_error(array('message' => __('Frage und Antwort sind erforderlich', 'ensemble')));
        }
        
        $post_data = array(
            'post_title'   => $question,
            'post_content' => $answer,
            'post_type'    => self::POST_TYPE,
            'post_status'  => 'publish',
            'menu_order'   => $menu_order,
        );
        
        if ($faq_id > 0) {
            $post_data['ID'] = $faq_id;
            $result = wp_update_post($post_data);
            $message = __('FAQ aktualisiert', 'ensemble');
        } else {
            $result = wp_insert_post($post_data);
            $faq_id = $result;
            $message = __('FAQ erstellt', 'ensemble');
        }
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        // Update category
        if ($category > 0) {
            wp_set_post_terms($faq_id, array($category), self::TAXONOMY);
        } else {
            wp_set_post_terms($faq_id, array(), self::TAXONOMY);
        }
        
        // Update expanded meta
        update_post_meta($faq_id, '_es_faq_expanded', $expanded ? '1' : '');
        
        wp_send_json_success(array(
            'message' => $message,
            'faq_id'  => $faq_id,
        ));
    }
    
    /**
     * AJAX: Delete FAQ
     */
    public function ajax_delete() {
        check_ajax_referer('es_faq_nonce', 'nonce');
        
        if (!current_user_can('delete_posts')) {
            wp_send_json_error(array('message' => __('Keine Berechtigung', 'ensemble')));
        }
        
        $faq_id = isset($_POST['faq_id']) ? intval($_POST['faq_id']) : 0;
        
        if ($faq_id <= 0) {
            wp_send_json_error(array('message' => __('Ungültige FAQ ID', 'ensemble')));
        }
        
        $result = wp_delete_post($faq_id, true);
        
        if ($result) {
            wp_send_json_success(array('message' => __('FAQ gelöscht', 'ensemble')));
        } else {
            wp_send_json_error(array('message' => __('Fehler beim Löschen', 'ensemble')));
        }
    }
    
    /**
     * AJAX: Create category
     */
    public function ajax_create_category() {
        check_ajax_referer('es_faq_nonce', 'nonce');
        
        if (!current_user_can('manage_categories')) {
            wp_send_json_error(array('message' => __('Keine Berechtigung', 'ensemble')));
        }
        
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        
        if (empty($name)) {
            wp_send_json_error(array('message' => __('Name erforderlich', 'ensemble')));
        }
        
        $result = wp_insert_term($name, self::TAXONOMY);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        $term = get_term($result['term_id'], self::TAXONOMY);
        
        wp_send_json_success(array(
            'message'  => __('Kategorie erstellt', 'ensemble'),
            'category' => array(
                'term_id' => $term->term_id,
                'name'    => $term->name,
                'slug'    => $term->slug,
            ),
        ));
    }
    
    /**
     * AJAX: Bulk action
     */
    public function ajax_bulk_action() {
        check_ajax_referer('es_faq_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Keine Berechtigung', 'ensemble')));
        }
        
        $action = isset($_POST['bulk_action']) ? sanitize_key($_POST['bulk_action']) : '';
        $faq_ids = isset($_POST['faq_ids']) ? array_map('intval', $_POST['faq_ids']) : array();
        
        if (empty($action) || empty($faq_ids)) {
            wp_send_json_error(array('message' => __('Ungültige Aktion', 'ensemble')));
        }
        
        $count = 0;
        
        switch ($action) {
            case 'delete':
                if (!current_user_can('delete_posts')) {
                    wp_send_json_error(array('message' => __('Keine Berechtigung', 'ensemble')));
                }
                
                foreach ($faq_ids as $id) {
                    if (wp_delete_post($id, true)) {
                        $count++;
                    }
                }
                
                $message = sprintf(__('%d FAQ(s) gelöscht', 'ensemble'), $count);
                break;
                
            default:
                wp_send_json_error(array('message' => __('Unbekannte Aktion', 'ensemble')));
        }
        
        wp_send_json_success(array('message' => $message));
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        if (!$this->is_active()) {
            return;
        }
        
        wp_enqueue_style(
            'es-faq',
            $this->get_addon_url() . 'assets/faq.css',
            array(),
            $this->version
        );
        
        wp_enqueue_script(
            'es-faq',
            $this->get_addon_url() . 'assets/faq.js',
            array(),
            $this->version,
            true
        );
        
        $settings = $this->get_settings();
        
        wp_localize_script('es-faq', 'esFAQ', array(
            'animationSpeed'     => $settings['animation_speed'],
            'allowMultipleOpen'  => $settings['allow_multiple_open'],
            'searchPlaceholder'  => __('FAQs durchsuchen...', 'ensemble'),
            'noResults'          => __('Keine FAQs gefunden', 'ensemble'),
            'showAll'            => __('Alle anzeigen', 'ensemble'),
        ));
    }
    
    /**
     * Render settings page
     */
    public function render_settings() {
        $settings = $this->get_settings();
        
        ob_start();
        include $this->get_addon_path() . 'templates/settings.php';
        return ob_get_clean();
    }
    
    /**
     * Render shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function render_shortcode($atts = array()) {
        if (!$this->is_active()) {
            return '';
        }
        
        $settings = $this->get_settings();
        
        $atts = shortcode_atts(array(
            'category'          => '',        // Kategorie Slug oder ID
            'ids'               => '',        // Spezifische FAQ IDs
            'limit'             => $settings['items_per_page'],
            'orderby'           => 'menu_order',
            'order'             => 'ASC',
            'layout'            => $settings['layout_style'],
            'show_filter'       => $settings['show_category_filter'] ? 'true' : 'false',
            'show_search'       => $settings['show_search'] ? 'true' : 'false',
            'expand_first'      => $settings['expand_first'] ? 'true' : 'false',
            'icon_position'     => $settings['icon_position'],
            'icon_type'         => $settings['icon_type'],
            'class'             => '',
        ), $atts, 'ensemble_faq');
        
        // Convert string booleans
        $atts['show_filter'] = $atts['show_filter'] === 'true';
        $atts['show_search'] = $atts['show_search'] === 'true';
        $atts['expand_first'] = $atts['expand_first'] === 'true';
        
        // Get FAQs
        $faqs = $this->get_faqs($atts);
        
        if (empty($faqs)) {
            return '<p class="es-faq-empty">' . __('Keine FAQs vorhanden.', 'ensemble') . '</p>';
        }
        
        // Get categories for filter
        $categories = array();
        if ($atts['show_filter']) {
            $categories = $this->get_faq_categories($atts);
        }
        
        // Determine current layout
        $current_layout = $this->get_current_ensemble_layout();
        
        // Render template
        return $this->load_template('faq-shortcode', array(
            'faqs'           => $faqs,
            'categories'     => $categories,
            'atts'           => $atts,
            'settings'       => $settings,
            'current_layout' => $current_layout,
        ));
    }
    
    /**
     * Get FAQs
     * 
     * @param array $atts Attributes
     * @return array
     */
    private function get_faqs($atts) {
        $args = array(
            'post_type'      => self::POST_TYPE,
            'posts_per_page' => intval($atts['limit']),
            'orderby'        => $atts['orderby'],
            'order'          => $atts['order'],
            'post_status'    => 'publish',
        );
        
        // Spezifische IDs
        if (!empty($atts['ids'])) {
            $args['post__in'] = array_map('intval', explode(',', $atts['ids']));
            $args['orderby'] = 'post__in';
        }
        
        // Kategorie Filter
        if (!empty($atts['category'])) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => self::TAXONOMY,
                    'field'    => is_numeric($atts['category']) ? 'term_id' : 'slug',
                    'terms'    => $atts['category'],
                ),
            );
        }
        
        $query = new WP_Query($args);
        
        $faqs = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                
                $post_id = get_the_ID();
                
                $faqs[] = array(
                    'id'         => $post_id,
                    'question'   => get_the_title(),
                    'answer'     => apply_filters('the_content', get_the_content()),
                    'icon'       => get_post_meta($post_id, '_es_faq_icon', true),
                    'expanded'   => get_post_meta($post_id, '_es_faq_expanded', true) === '1',
                    'categories' => wp_get_post_terms($post_id, self::TAXONOMY, array('fields' => 'slugs')),
                );
            }
            wp_reset_postdata();
        }
        
        return $faqs;
    }
    
    /**
     * Get FAQ categories
     * 
     * @param array $atts
     * @return array
     */
    private function get_faq_categories($atts) {
        $args = array(
            'taxonomy'   => self::TAXONOMY,
            'hide_empty' => true,
            'orderby'    => 'name',
            'order'      => 'ASC',
        );
        
        $terms = get_terms($args);
        
        if (is_wp_error($terms)) {
            return array();
        }
        
        return $terms;
    }
    
    /**
     * Get current Ensemble layout
     * 
     * @return string
     */
    private function get_current_ensemble_layout() {
        // Check for Layout Set
        if (class_exists('ES_Layout_Sets')) {
            $layout_set = ES_Layout_Sets::get_active_set();
            if ($layout_set && isset($layout_set['layout'])) {
                return $layout_set['layout'];
            }
        }
        
        // Check for design setting
        $layout = get_option('ensemble_active_layout', '');
        if (!empty($layout)) {
            return $layout;
        }
        
        return 'modern'; // Default
    }
    
    /**
     * Generate Schema Markup for Google FAQ Rich Results
     * 
     * @param array $faqs
     * @return string
     */
    public function generate_schema_markup($faqs) {
        $settings = $this->get_settings();
        
        if (!$settings['schema_markup'] || empty($faqs)) {
            return '';
        }
        
        $schema = array(
            '@context'   => 'https://schema.org',
            '@type'      => 'FAQPage',
            'mainEntity' => array(),
        );
        
        foreach ($faqs as $faq) {
            $schema['mainEntity'][] = array(
                '@type'          => 'Question',
                'name'           => strip_tags($faq['question']),
                'acceptedAnswer' => array(
                    '@type' => 'Answer',
                    'text'  => strip_tags($faq['answer']),
                ),
            );
        }
        
        return '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
    }
}
