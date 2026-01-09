<?php
/**
 * Ensemble Sponsors Add-on
 * 
 * Sponsoren-Management mit Carousel, Grid und Footer-Integration
 * 
 * @package Ensemble
 * @subpackage Addons
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class ES_Sponsors_Addon extends ES_Addon_Base {
    
    /**
     * Addon slug
     */
    protected $slug = 'sponsors';
    
    /**
     * Addon Name
     */
    protected $name = 'Sponsors';
    
    /**
     * Addon Version
     */
    protected $version = '1.0.0';
    
    /**
     * Sponsor Manager instance
     */
    private $sponsor_manager;
    
    /**
     * Initialize addon
     */
    protected function init() {
        if (empty($this->settings)) {
            $this->settings = $this->get_default_settings();
        } else {
            $this->settings = wp_parse_args($this->settings, $this->get_default_settings());
        }
        
        // Include sponsor manager
        require_once $this->get_addon_path() . 'includes/class-sponsor-manager.php';
        $this->sponsor_manager = new ES_Sponsor_Manager();
    }
    
    /**
     * Register hooks
     */
    protected function register_hooks() {
        // Register CPT and Taxonomy
        add_action('init', array($this, 'register_post_type'), 5);
        add_action('init', array($this, 'register_shortcodes'));
        
        // Admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'), 20);
        
        // AJAX handlers
        add_action('wp_ajax_es_get_sponsors', array($this, 'ajax_get_sponsors'));
        add_action('wp_ajax_es_get_sponsor', array($this, 'ajax_get_sponsor'));
        add_action('wp_ajax_es_save_sponsor', array($this, 'ajax_save_sponsor'));
        add_action('wp_ajax_es_delete_sponsor', array($this, 'ajax_delete_sponsor'));
        add_action('wp_ajax_es_get_main_sponsor', array($this, 'ajax_get_main_sponsor'));
        
        // Auto-display on events
        if ($this->get_setting('auto_display_events')) {
            $position = $this->get_setting('event_position', 'after_content');
            $hook = $this->get_hook_for_position($position);
            $this->register_template_hook($hook, array($this, 'render_event_sponsors'), 20);
        }
        
        // Footer display - Check if Ensemble Theme handles it
        if ($this->get_setting('show_in_footer')) {
            // Check if Ensemble Theme is active and handles sponsors via its own hook
            if (!$this->is_ensemble_theme_handling_sponsors()) {
                add_action('wp_footer', array($this, 'render_footer_sponsors'), 5);
            }
            // Note: When Ensemble Theme is active, the theme's et_footer_sponsors hook 
            // will handle the rendering via et_render_footer_sponsors() in functions.php
        }
        
        // Main Sponsor display hooks
        if ($this->get_setting('main_sponsor_enabled')) {
            $position = $this->get_setting('main_sponsor_position', 'header');
            
            // Register theme hooks for main sponsor
            if ($position === 'header' || $position === 'both') {
                add_action('et_main_sponsor', array($this, 'render_main_sponsor'), 10);
                // Fallback for non-Ensemble themes
                add_action('ensemble_main_sponsor', array($this, 'render_main_sponsor'), 10);
            }
            
            if ($position === 'sidebar' || $position === 'both') {
                // Theme sidebar hook
                add_action('et_sidebar_main_sponsor', array($this, 'render_main_sponsor_sidebar'), 10);
                // Plugin layout sidebar hook
                add_action('ensemble_main_sponsor_sidebar', array($this, 'render_main_sponsor_sidebar'), 10);
                // Register as widget area too
                add_action('widgets_init', array($this, 'register_main_sponsor_widget'));
            }
        }
        
        // Enqueue assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }
    
    /**
     * Check if Ensemble Theme is active and configured to handle sponsors
     */
    private function is_ensemble_theme_handling_sponsors() {
        // Check if current theme is Ensemble Theme
        $theme = wp_get_theme();
        $is_ensemble_theme = ($theme->get_template() === 'ensemble-theme' || $theme->get('Name') === 'Ensemble Theme');
        
        if (!$is_ensemble_theme) {
            return false;
        }
        
        // Check if theme is configured to show sponsors in footer
        return get_theme_mod('et_footer_sponsors', true);
    }
    
    /**
     * Get hook name for position
     */
    private function get_hook_for_position($position) {
        $hooks = array(
            'before_content' => 'ensemble_before_event_content',
            'after_content'  => 'ensemble_after_event_content',
            'after_artists'  => 'ensemble_after_event_artists',
            'footer'         => 'ensemble_event_footer',
        );
        return isset($hooks[$position]) ? $hooks[$position] : 'ensemble_after_event_content';
    }
    
    /**
     * Get default settings
     */
    public function get_default_settings() {
        return array(
            'enabled'              => true,
            // Display settings
            'logo_height'          => 60,
            'logo_height_mobile'   => 40,
            'logo_spacing'         => 24,
            'grayscale'            => false,
            'grayscale_hover'      => true,
            // Carousel settings
            'carousel_autoplay'    => true,
            'carousel_speed'       => 3000,
            'carousel_pause_hover' => true,
            'carousel_loop'        => true,
            // Event integration
            'auto_display_events'  => false,
            'event_position'       => 'after_content',
            'event_style'          => 'carousel',
            'event_title'          => __('Sponsors', 'ensemble'),
            'show_global_on_events'=> true,
            // Footer integration
            'show_in_footer'       => false,
            'footer_style'         => 'bar',
            'footer_title'         => __('Our Partners', 'ensemble'),
            'footer_categories'    => array(),
            // Main Sponsor settings
            'main_sponsor_enabled'   => false,
            'main_sponsor_position'  => 'header',  // header, sidebar, both
            'main_sponsor_height'    => 40,
            'main_sponsor_caption'   => __('Presented by', 'ensemble'),
            'main_sponsor_exclude_footer' => true,  // Exclude main sponsor from footer display
            'main_sponsor_footer_position' => 'right', // above, left, right
            'main_sponsor_footer_caption' => __('Main Sponsor', 'ensemble'),
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
     * Register sponsor post type
     */
    public function register_post_type() {
        // Sponsor CPT
        register_post_type('ensemble_sponsor', array(
            'labels' => array(
                'name'               => __('Sponsors', 'ensemble'),
                'singular_name'      => __('Sponsor', 'ensemble'),
                'add_new'            => __('Add New Sponsor', 'ensemble'),
                'add_new_item'       => __('Add New Sponsor', 'ensemble'),
                'edit_item'          => __('Edit Sponsor', 'ensemble'),
                'new_item'           => __('New Sponsor', 'ensemble'),
                'view_item'          => __('View Sponsor', 'ensemble'),
                'search_items'       => __('Search Sponsors', 'ensemble'),
                'not_found'          => __('No sponsors found', 'ensemble'),
                'not_found_in_trash' => __('No sponsors found in trash', 'ensemble'),
            ),
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => false,
            'show_in_menu'       => false,
            'show_in_rest'       => true,
            'supports'           => array('title', 'thumbnail'),
            'capability_type'    => 'post',
        ));
        
        // Sponsor Category Taxonomy
        register_taxonomy('ensemble_sponsor_category', array('ensemble_sponsor'), array(
            'labels' => array(
                'name'              => __('Sponsor Categories', 'ensemble'),
                'singular_name'     => __('Sponsor Category', 'ensemble'),
                'search_items'      => __('Search Categories', 'ensemble'),
                'all_items'         => __('All Categories', 'ensemble'),
                'edit_item'         => __('Edit Category', 'ensemble'),
                'update_item'       => __('Update Category', 'ensemble'),
                'add_new_item'      => __('Add New Category', 'ensemble'),
                'new_item_name'     => __('New Category Name', 'ensemble'),
                'menu_name'         => __('Categories', 'ensemble'),
            ),
            'hierarchical'      => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'rewrite'           => false,
        ));
    }
    
    /**
     * Register shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('ensemble_sponsors', array($this, 'sponsors_shortcode'));
        add_shortcode('ensemble_sponsor', array($this, 'single_sponsor_shortcode'));
        add_shortcode('ensemble_main_sponsor', array($this, 'main_sponsor_shortcode'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'ensemble',
            __('Sponsors', 'ensemble'),
            __('Sponsors', 'ensemble'),
            'manage_options',
            'ensemble-sponsors',
            array($this, 'render_admin_page')
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
        $int_fields = array('logo_height', 'logo_height_mobile', 'logo_spacing', 'carousel_speed', 'main_sponsor_height');
        foreach ($int_fields as $field) {
            $sanitized[$field] = isset($settings[$field]) ? absint($settings[$field]) : $defaults[$field];
        }
        
        // Text fields
        $text_fields = array('event_title', 'footer_title', 'main_sponsor_caption', 'main_sponsor_footer_caption');
        foreach ($text_fields as $field) {
            $sanitized[$field] = isset($settings[$field]) ? sanitize_text_field($settings[$field]) : $defaults[$field];
        }
        
        // Select fields
        $sanitized['event_position'] = isset($settings['event_position']) ? sanitize_key($settings['event_position']) : $defaults['event_position'];
        $sanitized['event_style'] = isset($settings['event_style']) ? sanitize_key($settings['event_style']) : $defaults['event_style'];
        $sanitized['footer_style'] = isset($settings['footer_style']) ? sanitize_key($settings['footer_style']) : $defaults['footer_style'];
        $sanitized['main_sponsor_position'] = isset($settings['main_sponsor_position']) ? sanitize_key($settings['main_sponsor_position']) : $defaults['main_sponsor_position'];
        $sanitized['main_sponsor_footer_position'] = isset($settings['main_sponsor_footer_position']) ? sanitize_key($settings['main_sponsor_footer_position']) : $defaults['main_sponsor_footer_position'];
        
        // Array fields
        $sanitized['footer_categories'] = isset($settings['footer_categories']) ? array_map('intval', (array) $settings['footer_categories']) : array();
        
        // Boolean fields
        $boolean_fields = array(
            'enabled', 'grayscale', 'grayscale_hover', 'carousel_autoplay', 
            'carousel_pause_hover', 'carousel_loop', 'auto_display_events', 
            'show_global_on_events', 'show_in_footer', 'main_sponsor_enabled',
            'main_sponsor_exclude_footer'
        );
        foreach ($boolean_fields as $field) {
            $sanitized[$field] = isset($settings[$field]) && ($settings[$field] === true || $settings[$field] === '1' || $settings[$field] === 'true');
        }
        
        return $sanitized;
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        if (!$this->is_active()) {
            return;
        }
        
        wp_enqueue_style(
            'es-sponsors',
            $this->get_addon_url() . 'assets/sponsors.css',
            array(),
            $this->version
        );
        
        wp_enqueue_script(
            'es-sponsors',
            $this->get_addon_url() . 'assets/sponsors.js',
            array(),
            $this->version,
            true
        );
        
        wp_localize_script('es-sponsors', 'esSponsors', array(
            'autoplay'    => $this->get_setting('carousel_autoplay'),
            'speed'       => $this->get_setting('carousel_speed'),
            'pauseHover'  => $this->get_setting('carousel_pause_hover'),
            'loop'        => $this->get_setting('carousel_loop'),
        ));
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'ensemble-sponsors') === false) {
            return;
        }
        
        wp_enqueue_media();
        
        // Load base manager styles
        wp_enqueue_style(
            'es-manager',
            ENSEMBLE_PLUGIN_URL . 'assets/css/manager.css',
            array(),
            ENSEMBLE_VERSION
        );
        
        wp_enqueue_style(
            'es-sponsors-admin',
            $this->get_addon_url() . 'assets/sponsors-admin.css',
            array('es-manager'),
            $this->version
        );
        
        wp_enqueue_script(
            'es-sponsors-admin',
            $this->get_addon_url() . 'assets/sponsors-admin.js',
            array('jquery'),
            $this->version,
            true
        );
        
        wp_localize_script('es-sponsors-admin', 'esSponsorsAdmin', array(
            'ajaxurl'        => admin_url('admin-ajax.php'),
            'asyncUploadUrl' => admin_url('async-upload.php'),
            'nonce'          => wp_create_nonce('ensemble_ajax'),
            'uploadNonce'    => wp_create_nonce('media-form'),
            'i18n'           => array(
                'confirmDelete' => __('Are you sure you want to delete this sponsor?', 'ensemble'),
                'saved'         => __('Sponsor saved successfully!', 'ensemble'),
                'deleted'       => __('Sponsor deleted.', 'ensemble'),
                'error'         => __('An error occurred.', 'ensemble'),
            ),
        ));
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        include $this->get_addon_path() . 'templates/admin-page.php';
    }
    
    /**
     * Render settings page
     */
    public function render_settings() {
        $settings = $this->get_settings();
        $categories = get_terms(array(
            'taxonomy'   => 'ensemble_sponsor_category',
            'hide_empty' => false,
        ));
        
        ob_start();
        include $this->get_addon_path() . 'templates/settings.php';
        return ob_get_clean();
    }
    
    /**
     * Sponsors Shortcode
     * 
     * [ensemble_sponsors]
     * [ensemble_sponsors style="carousel" category="main-sponsors"]
     * [ensemble_sponsors style="grid" columns="4" event="123"]
     * [ensemble_sponsors style="bar" height="40"]
     * [ensemble_sponsors exclude_main="true"]
     */
    public function sponsors_shortcode($atts) {
        $atts = shortcode_atts(array(
            'style'        => 'carousel',  // carousel, grid, bar, marquee
            'category'     => '',          // Sponsor category slug
            'event'        => '',          // Event ID (or 'current')
            'limit'        => -1,          // Max sponsors
            'columns'      => 4,           // For grid
            'height'       => '',          // Logo height (overrides settings)
            'grayscale'    => '',          // Override grayscale setting
            'title'        => '',          // Section title
            'class'        => '',          // Extra CSS class
            'orderby'      => 'menu_order',
            'order'        => 'ASC',
            'exclude_main' => '',          // Exclude main sponsor (true/false)
        ), $atts, 'ensemble_sponsors');
        
        // Get sponsors
        $sponsors = $this->get_sponsors_for_display($atts);
        
        if (empty($sponsors)) {
            return '';
        }
        
        // Exclude main sponsor if requested
        if ($atts['exclude_main'] !== '' && filter_var($atts['exclude_main'], FILTER_VALIDATE_BOOLEAN)) {
            $sponsors = array_filter($sponsors, function($sponsor) {
                return empty($sponsor['is_main']);
            });
            $sponsors = array_values($sponsors);
        }
        
        if (empty($sponsors)) {
            return '';
        }
        
        // Settings
        $settings = $this->get_settings();
        $height = !empty($atts['height']) ? absint($atts['height']) : $settings['logo_height'];
        $grayscale = $atts['grayscale'] !== '' ? filter_var($atts['grayscale'], FILTER_VALIDATE_BOOLEAN) : $settings['grayscale'];
        
        ob_start();
        include $this->get_addon_path() . 'templates/sponsors-display.php';
        return ob_get_clean();
    }
    
    /**
     * Single Sponsor Shortcode
     * 
     * [ensemble_sponsor id="123"]
     */
    public function single_sponsor_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id'     => 0,
            'height' => '',
            'link'   => 'true',
        ), $atts, 'ensemble_sponsor');
        
        $sponsor_id = absint($atts['id']);
        if (!$sponsor_id) {
            return '';
        }
        
        $sponsor = $this->sponsor_manager->get_sponsor($sponsor_id);
        if (!$sponsor) {
            return '';
        }
        
        $settings = $this->get_settings();
        $height = !empty($atts['height']) ? absint($atts['height']) : $settings['logo_height'];
        $show_link = filter_var($atts['link'], FILTER_VALIDATE_BOOLEAN);
        
        ob_start();
        include $this->get_addon_path() . 'templates/single-sponsor.php';
        return ob_get_clean();
    }
    
    /**
     * Main Sponsor Shortcode
     * 
     * [ensemble_main_sponsor]
     * [ensemble_main_sponsor height="50" caption="Presented by"]
     * [ensemble_main_sponsor style="sidebar"]
     */
    public function main_sponsor_shortcode($atts) {
        $atts = shortcode_atts(array(
            'height'  => 0,
            'caption' => '',
            'style'   => 'inline',  // inline, sidebar
        ), $atts, 'ensemble_main_sponsor');
        
        $sponsor = $this->get_main_sponsor();
        if (!$sponsor) {
            return '';
        }
        
        $settings = $this->get_settings();
        $height = !empty($atts['height']) ? absint($atts['height']) : $settings['main_sponsor_height'];
        
        // Use shortcode caption, then sponsor-specific caption, then global setting
        $caption = !empty($atts['caption']) 
            ? $atts['caption'] 
            : (!empty($sponsor['main_caption']) ? $sponsor['main_caption'] : $settings['main_sponsor_caption']);
        
        $logo_url = !empty($sponsor['logo_url_full']) ? $sponsor['logo_url_full'] : $sponsor['logo_url'];
        $has_link = !empty($sponsor['website']);
        $style_class = $atts['style'] === 'sidebar' ? 'es-main-sponsor--sidebar' : 'es-main-sponsor--inline';
        
        ob_start();
        ?>
        <div class="es-main-sponsor <?php echo esc_attr($style_class); ?>">
            <?php if ($caption): ?>
                <span class="es-main-sponsor__caption"><?php echo esc_html($caption); ?></span>
            <?php endif; ?>
            
            <div class="es-main-sponsor__logo">
                <?php if ($has_link): ?>
                    <a href="<?php echo esc_url($sponsor['website']); ?>" target="_blank" rel="noopener noreferrer" title="<?php echo esc_attr($sponsor['name']); ?>">
                <?php endif; ?>
                
                <?php if ($logo_url): ?>
                    <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($sponsor['name']); ?>" style="height: <?php echo esc_attr($height); ?>px;" loading="lazy">
                <?php else: ?>
                    <span class="es-main-sponsor__name"><?php echo esc_html($sponsor['name']); ?></span>
                <?php endif; ?>
                
                <?php if ($has_link): ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get sponsors for display
     */
    private function get_sponsors_for_display($atts) {
        $args = array(
            'orderby' => sanitize_key($atts['orderby']),
            'order'   => strtoupper($atts['order']) === 'DESC' ? 'DESC' : 'ASC',
        );
        
        if (!empty($atts['limit']) && $atts['limit'] > 0) {
            $args['posts_per_page'] = absint($atts['limit']);
        }
        
        // Filter by category
        if (!empty($atts['category'])) {
            $args['category'] = sanitize_text_field($atts['category']);
        }
        
        // Filter by event
        $event_id = 0;
        if (!empty($atts['event'])) {
            if ($atts['event'] === 'current') {
                $event_id = get_the_ID();
            } else {
                $event_id = absint($atts['event']);
            }
        }
        
        if ($event_id) {
            // Get event-specific sponsors
            $sponsors = $this->sponsor_manager->get_sponsors_for_event($event_id);
            
            // Also get global sponsors if enabled
            if ($this->get_setting('show_global_on_events')) {
                $global_sponsors = $this->sponsor_manager->get_global_sponsors();
                $sponsors = array_merge($sponsors, $global_sponsors);
                
                // Remove duplicates
                $unique = array();
                foreach ($sponsors as $sponsor) {
                    $unique[$sponsor['id']] = $sponsor;
                }
                $sponsors = array_values($unique);
            }
            
            return $sponsors;
        }
        
        return $this->sponsor_manager->get_sponsors($args);
    }
    
    /**
     * Render sponsors on event pages
     */
    public function render_event_sponsors($event_id, $context = array()) {
        $sponsors = $this->get_sponsors_for_display(array(
            'event'   => $event_id,
            'orderby' => 'menu_order',
            'order'   => 'ASC',
        ));
        
        if (empty($sponsors)) {
            return;
        }
        
        $settings = $this->get_settings();
        $atts = array(
            'style'   => $settings['event_style'],
            'title'   => $settings['event_title'],
            'columns' => 4,
        );
        $height = $settings['logo_height'];
        $grayscale = $settings['grayscale'];
        
        include $this->get_addon_path() . 'templates/sponsors-display.php';
    }
    
    /**
     * Render footer sponsors
     */
    public function render_footer_sponsors() {
        $settings = $this->get_settings();
        
        $args = array(
            'orderby' => 'menu_order',
            'order'   => 'ASC',
        );
        
        // Filter by categories if set
        if (!empty($settings['footer_categories'])) {
            $args['category_ids'] = $settings['footer_categories'];
        }
        
        $sponsors = $this->sponsor_manager->get_sponsors($args);
        
        // Exclude main sponsor from footer if option is enabled
        if (!empty($settings['main_sponsor_exclude_footer'])) {
            $sponsors = array_filter($sponsors, function($sponsor) {
                return empty($sponsor['is_main']);
            });
            $sponsors = array_values($sponsors); // Re-index array
        }
        
        if (empty($sponsors)) {
            return;
        }
        
        $atts = array(
            'style'   => $settings['footer_style'],
            'title'   => $settings['footer_title'],
            'columns' => 6,
        );
        $height = $settings['logo_height'];
        $grayscale = $settings['grayscale'];
        
        echo '<div class="es-sponsors-footer-wrapper">';
        include $this->get_addon_path() . 'templates/sponsors-display.php';
        echo '</div>';
    }
    
    // =========================================
    // AJAX HANDLERS
    // =========================================
    
    /**
     * AJAX: Get all sponsors
     */
    public function ajax_get_sponsors() {
        check_ajax_referer('ensemble_ajax', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $sponsors = $this->sponsor_manager->get_sponsors();
        wp_send_json_success($sponsors);
    }
    
    /**
     * AJAX: Get single sponsor
     */
    public function ajax_get_sponsor() {
        check_ajax_referer('ensemble_ajax', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $sponsor_id = isset($_POST['sponsor_id']) ? absint($_POST['sponsor_id']) : 0;
        if (!$sponsor_id) {
            wp_send_json_error(array('message' => 'Invalid sponsor ID'));
        }
        
        $sponsor = $this->sponsor_manager->get_sponsor($sponsor_id);
        if (!$sponsor) {
            wp_send_json_error(array('message' => 'Sponsor not found'));
        }
        
        wp_send_json_success($sponsor);
    }
    
    /**
     * AJAX: Save sponsor
     */
    public function ajax_save_sponsor() {
        check_ajax_referer('ensemble_ajax', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $data = array(
            'sponsor_id'   => isset($_POST['sponsor_id']) ? absint($_POST['sponsor_id']) : 0,
            'name'         => isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '',
            'website'      => isset($_POST['website']) ? esc_url_raw($_POST['website']) : '',
            'description'  => isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '',
            'logo_id'      => isset($_POST['logo_id']) ? absint($_POST['logo_id']) : 0,
            'categories'   => isset($_POST['categories']) ? array_map('intval', (array) $_POST['categories']) : array(),
            'events'       => isset($_POST['events']) ? array_map('intval', (array) $_POST['events']) : array(),
            'is_global'    => isset($_POST['is_global']) && $_POST['is_global'],
            'is_main'      => isset($_POST['is_main']) && $_POST['is_main'],
            'main_caption' => isset($_POST['main_caption']) ? sanitize_text_field($_POST['main_caption']) : '',
            'active_from'  => isset($_POST['active_from']) ? sanitize_text_field($_POST['active_from']) : '',
            'active_until' => isset($_POST['active_until']) ? sanitize_text_field($_POST['active_until']) : '',
            'menu_order'   => isset($_POST['menu_order']) ? absint($_POST['menu_order']) : 0,
        );
        
        $result = $this->sponsor_manager->save_sponsor($data);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success(array(
            'message'    => __('Sponsor saved successfully!', 'ensemble'),
            'sponsor_id' => $result,
        ));
    }
    
    /**
     * AJAX: Delete sponsor
     */
    public function ajax_delete_sponsor() {
        check_ajax_referer('ensemble_ajax', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $sponsor_id = isset($_POST['sponsor_id']) ? absint($_POST['sponsor_id']) : 0;
        if (!$sponsor_id) {
            wp_send_json_error(array('message' => 'Invalid sponsor ID'));
        }
        
        $result = $this->sponsor_manager->delete_sponsor($sponsor_id);
        
        if (!$result) {
            wp_send_json_error(array('message' => 'Failed to delete sponsor'));
        }
        
        wp_send_json_success(array('message' => __('Sponsor deleted.', 'ensemble')));
    }
    
    /**
     * Get sponsor manager
     */
    public function get_sponsor_manager() {
        return $this->sponsor_manager;
    }
    
    /**
     * Render single sponsor item (logo with link)
     * 
     * @param array $sponsor Sponsor data
     * @param int $height Logo height
     */
    public function render_sponsor_item($sponsor, $height = 60) {
        $has_link = !empty($sponsor['website']);
        $logo_url = !empty($sponsor['logo_url_full']) ? $sponsor['logo_url_full'] : $sponsor['logo_url'];
        
        if ($has_link) {
            echo '<a href="' . esc_url($sponsor['website']) . '" target="_blank" rel="noopener noreferrer" class="es-sponsor-link" title="' . esc_attr($sponsor['name']) . '">';
        }
        
        if ($logo_url) {
            echo '<img src="' . esc_url($logo_url) . '" alt="' . esc_attr($sponsor['name']) . '" class="es-sponsor-logo" style="height: ' . esc_attr($height) . 'px;" loading="lazy">';
        } else {
            echo '<span class="es-sponsor-name">' . esc_html($sponsor['name']) . '</span>';
        }
        
        if ($has_link) {
            echo '</a>';
        }
    }
    
    // =========================================
    // MAIN SPONSOR METHODS
    // =========================================
    
    /**
     * Get main sponsor data
     * 
     * @return array|false
     */
    public function get_main_sponsor() {
        return $this->sponsor_manager->get_main_sponsor();
    }
    
    /**
     * Render main sponsor in header
     */
    public function render_main_sponsor() {
        $sponsor = $this->get_main_sponsor();
        
        if (!$sponsor) {
            return;
        }
        
        $settings = $this->get_settings();
        $height = isset($settings['main_sponsor_height']) ? $settings['main_sponsor_height'] : 40;
        
        // Use sponsor-specific caption or fall back to global setting
        $caption = !empty($sponsor['main_caption']) 
            ? $sponsor['main_caption'] 
            : $settings['main_sponsor_caption'];
        
        $logo_url = !empty($sponsor['logo_url_full']) ? $sponsor['logo_url_full'] : $sponsor['logo_url'];
        $has_link = !empty($sponsor['website']);
        
        echo '<div class="es-main-sponsor es-main-sponsor--header">';
        
        if ($caption) {
            echo '<span class="es-main-sponsor__caption">' . esc_html($caption) . '</span>';
        }
        
        echo '<div class="es-main-sponsor__logo">';
        
        if ($has_link) {
            echo '<a href="' . esc_url($sponsor['website']) . '" target="_blank" rel="noopener noreferrer" title="' . esc_attr($sponsor['name']) . '">';
        }
        
        if ($logo_url) {
            echo '<img src="' . esc_url($logo_url) . '" alt="' . esc_attr($sponsor['name']) . '" style="height: ' . esc_attr($height) . 'px;" loading="lazy">';
        } else {
            echo '<span class="es-main-sponsor__name">' . esc_html($sponsor['name']) . '</span>';
        }
        
        if ($has_link) {
            echo '</a>';
        }
        
        echo '</div>';
        echo '</div>';
    }
    
    /**
     * Render main sponsor in sidebar
     */
    public function render_main_sponsor_sidebar() {
        $sponsor = $this->get_main_sponsor();
        
        if (!$sponsor) {
            return;
        }
        
        $settings = $this->get_settings();
        $height = isset($settings['main_sponsor_height']) ? $settings['main_sponsor_height'] : 40;
        
        // Use sponsor-specific caption or fall back to global setting
        $caption = !empty($sponsor['main_caption']) 
            ? $sponsor['main_caption'] 
            : $settings['main_sponsor_caption'];
        
        $logo_url = !empty($sponsor['logo_url_full']) ? $sponsor['logo_url_full'] : $sponsor['logo_url'];
        $has_link = !empty($sponsor['website']);
        
        echo '<div class="es-main-sponsor es-main-sponsor--sidebar widget">';
        
        if ($caption) {
            echo '<h4 class="es-main-sponsor__caption widget-title">' . esc_html($caption) . '</h4>';
        }
        
        echo '<div class="es-main-sponsor__logo">';
        
        if ($has_link) {
            echo '<a href="' . esc_url($sponsor['website']) . '" target="_blank" rel="noopener noreferrer" title="' . esc_attr($sponsor['name']) . '">';
        }
        
        if ($logo_url) {
            echo '<img src="' . esc_url($logo_url) . '" alt="' . esc_attr($sponsor['name']) . '" style="max-height: ' . esc_attr($height * 2) . 'px; width: auto; max-width: 100%;" loading="lazy">';
        } else {
            echo '<span class="es-main-sponsor__name">' . esc_html($sponsor['name']) . '</span>';
        }
        
        if ($has_link) {
            echo '</a>';
        }
        
        echo '</div>';
        echo '</div>';
    }
    
    /**
     * Register main sponsor widget area
     */
    public function register_main_sponsor_widget() {
        register_sidebar(array(
            'name'          => __('Main Sponsor', 'ensemble'),
            'id'            => 'es-main-sponsor-widget',
            'description'   => __('Displays the main sponsor logo', 'ensemble'),
            'before_widget' => '<div class="es-main-sponsor-widget">',
            'after_widget'  => '</div>',
            'before_title'  => '<h4 class="widget-title">',
            'after_title'   => '</h4>',
        ));
    }
    
    /**
     * AJAX: Get main sponsor
     */
    public function ajax_get_main_sponsor() {
        check_ajax_referer('ensemble_ajax', 'nonce');
        
        $sponsor = $this->get_main_sponsor();
        
        if (!$sponsor) {
            wp_send_json_success(array('sponsor' => null));
        }
        
        wp_send_json_success(array('sponsor' => $sponsor));
    }
}
