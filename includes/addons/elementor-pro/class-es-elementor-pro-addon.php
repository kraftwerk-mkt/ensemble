<?php
/**
 * Elementor Pro Add-on
 * 
 * Provides Elementor widgets for Events Grid, Calendar, Artists, Locations
 * with comprehensive styling options.
 * 
 * @package Ensemble
 * @subpackage Addons/ElementorPro
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ES_Elementor_Pro_Addon
 */
class ES_Elementor_Pro_Addon extends ES_Addon_Base {
    
    /**
     * Addon slug
     */
    const SLUG = 'elementor-pro';
    
    /**
     * Minimum Elementor Version
     */
    const MINIMUM_ELEMENTOR_VERSION = '3.0.0';
    
    /**
     * Instance
     */
    private static $instance = null;
    
    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->slug = 'elementor-pro';
        parent::__construct();
    }
    
    /**
     * Check if Elementor is active and compatible
     */
    private function is_elementor_active() {
        // Check if Elementor is installed and activated
        if (!did_action('elementor/loaded')) {
            return false;
        }
        
        // Check Elementor version
        if (!version_compare(ELEMENTOR_VERSION, self::MINIMUM_ELEMENTOR_VERSION, '>=')) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Initialize the addon (called by parent constructor)
     */
    protected function init() {
        // Only init if Elementor is active
        if (!$this->is_elementor_active()) {
            return;
        }
        
        // Register widget category
        add_action('elementor/elements/categories_registered', array($this, 'register_widget_category'));
        
        // Register widgets
        add_action('elementor/widgets/register', array($this, 'register_widgets'));
        
        // Register dynamic tags (if needed)
        add_action('elementor/dynamic_tags/register', array($this, 'register_dynamic_tags'));
        
        // Enqueue editor styles
        add_action('elementor/editor/after_enqueue_styles', array($this, 'enqueue_editor_styles'));
        
        // Enqueue frontend styles
        add_action('elementor/frontend/after_enqueue_styles', array($this, 'enqueue_frontend_styles'));
    }
    
    /**
     * Register hooks (required by ES_Addon_Base)
     */
    protected function register_hooks() {
        // Show admin notices if Elementor is missing
        if (!did_action('elementor/loaded')) {
            add_action('admin_notices', array($this, 'admin_notice_missing_elementor'));
        } elseif (defined('ELEMENTOR_VERSION') && !version_compare(ELEMENTOR_VERSION, self::MINIMUM_ELEMENTOR_VERSION, '>=')) {
            add_action('admin_notices', array($this, 'admin_notice_minimum_elementor_version'));
        }
    }
    
    /**
     * Register widget category
     */
    public function register_widget_category($elements_manager) {
        $elements_manager->add_category(
            'ensemble-events',
            array(
                'title' => __('Ensemble Events', 'ensemble'),
                'icon'  => 'fa fa-calendar',
            )
        );
        
        $elements_manager->add_category(
            'ensemble-content',
            array(
                'title' => __('Ensemble Content', 'ensemble'),
                'icon'  => 'fa fa-th-large',
            )
        );
    }
    
    /**
     * Register widgets
     */
    public function register_widgets($widgets_manager) {
        // Load base class first
        $base_file = $this->get_addon_path() . 'class-es-elementor-widget-base.php';
        if (file_exists($base_file)) {
            require_once $base_file;
        }
        
        // Widget files - map slug to file and class
        $widgets = array(
            // Events Widgets
            'events-grid'     => array(
                'file'  => 'class-es-widget-events-grid.php',
                'class' => 'ES_Widget_Events_Grid',
            ),
            'calendar'        => array(
                'file'  => 'class-es-widget-calendar.php',
                'class' => 'ES_Widget_Calendar',
            ),
            'upcoming-events' => array(
                'file'  => 'class-es-widget-upcoming-events.php',
                'class' => 'ES_Widget_Upcoming_Events',
            ),
            'featured-events' => array(
                'file'  => 'class-es-widget-featured-events.php',
                'class' => 'ES_Widget_Featured_Events',
            ),
            'single-event'    => array(
                'file'  => 'class-es-widget-single-event.php',
                'class' => 'ES_Widget_Single_Event',
            ),
            'lineup'          => array(
                'file'  => 'class-es-widget-lineup.php',
                'class' => 'ES_Widget_Lineup',
            ),
            // Content Widgets
            'artists-grid'    => array(
                'file'  => 'class-es-widget-artists-grid.php',
                'class' => 'ES_Widget_Artists_Grid',
            ),
            'single-artist'   => array(
                'file'  => 'class-es-widget-single-artist.php',
                'class' => 'ES_Widget_Single_Artist',
            ),
            'locations-grid'  => array(
                'file'  => 'class-es-widget-locations-grid.php',
                'class' => 'ES_Widget_Locations_Grid',
            ),
            'single-location' => array(
                'file'  => 'class-es-widget-single-location.php',
                'class' => 'ES_Widget_Single_Location',
            ),
        );
        
        foreach ($widgets as $widget_slug => $widget_data) {
            $file_path = $this->get_addon_path() . 'widgets/' . $widget_data['file'];
            
            if (file_exists($file_path)) {
                require_once $file_path;
                
                $class_name = $widget_data['class'];
                
                if (class_exists($class_name)) {
                    $widgets_manager->register(new $class_name());
                }
            }
        }
    }
    
    /**
     * Register dynamic tags
     */
    public function register_dynamic_tags($dynamic_tags_manager) {
        // Future: Add dynamic tags for event data
        // $dynamic_tags_manager->register(new ES_Event_Date_Tag());
    }
    
    /**
     * Enqueue editor styles
     */
    public function enqueue_editor_styles() {
        $version = defined('ENSEMBLE_VERSION') ? ENSEMBLE_VERSION : '1.0.0';
        
        wp_enqueue_style(
            'ensemble-elementor-editor',
            $this->get_addon_url() . 'assets/editor.css',
            array(),
            $version
        );
    }
    
    /**
     * Enqueue frontend styles
     */
    public function enqueue_frontend_styles() {
        $version = defined('ENSEMBLE_VERSION') ? ENSEMBLE_VERSION : '1.0.0';
        
        // Main frontend styles are loaded by the core plugin
        // Only load additional Elementor-specific styles if needed
        if (file_exists($this->get_addon_path() . 'assets/elementor.css')) {
            wp_enqueue_style(
                'ensemble-elementor-widgets',
                $this->get_addon_url() . 'assets/elementor.css',
                array('ensemble-frontend'),
                $version
            );
        }
    }
    
    /**
     * Get addon path
     */
    protected function get_addon_path() {
        return plugin_dir_path(__FILE__);
    }
    
    /**
     * Get addon URL
     */
    protected function get_addon_url() {
        return plugin_dir_url(__FILE__);
    }
    
    /**
     * Admin notice: Missing Elementor
     */
    public function admin_notice_missing_elementor() {
        if (!current_user_can('activate_plugins')) {
            return;
        }
        
        $message = sprintf(
            /* translators: 1: Plugin name 2: Elementor */
            esc_html__('"%1$s" requires "%2$s" to work.', 'ensemble'),
            '<strong>Ensemble Elementor Pro</strong>',
            '<strong>Elementor</strong>'
        );
        
        printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message);
    }
    
    /**
     * Admin notice: Minimum Elementor version
     */
    public function admin_notice_minimum_elementor_version() {
        if (!current_user_can('activate_plugins')) {
            return;
        }
        
        $message = sprintf(
            /* translators: 1: Plugin name 2: Elementor 3: Required version */
            esc_html__('"%1$s" requires "%2$s" version %3$s or higher.', 'ensemble'),
            '<strong>Ensemble Elementor Pro</strong>',
            '<strong>Elementor</strong>',
            self::MINIMUM_ELEMENTOR_VERSION
        );
        
        printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message);
    }
    
    /**
     * Get settings fields (for addon settings page)
     */
    public function get_settings_fields() {
        return array(
            array(
                'id'          => 'enable_grid_widget',
                'label'       => __('Events Grid Widget', 'ensemble'),
                'type'        => 'toggle',
                'default'     => true,
                'description' => __('Enables the Events Grid Widget for Elementor.', 'ensemble'),
            ),
            array(
                'id'          => 'enable_calendar_widget',
                'label'       => __('Calendar Widget', 'ensemble'),
                'type'        => 'toggle',
                'default'     => true,
                'description' => __('Enables the Calendar Widget for Elementor.', 'ensemble'),
            ),
        );
    }
    
    /**
     * Render settings page
     */
    public function render_settings() {
        $settings = $this->get_settings();
        ?>
        <div class="es-addon-settings-content">
            <h3><?php _e('Elementor Widgets', 'ensemble'); ?></h3>
            <p class="description">
                <?php _e('Select which widgets should be available in Elementor.', 'ensemble'); ?>
            </p>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Events Grid', 'ensemble'); ?></th>
                    <td>
                        <label class="es-toggle">
                            <input type="checkbox" name="enable_grid_widget" value="1" 
                                <?php checked(!empty($settings['enable_grid_widget']), true); ?>>
                            <span class="es-toggle-slider"></span>
                        </label>
                        <p class="description"><?php _e('Zeigt Events in Grid, Liste oder Masonry Layout.', 'ensemble'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Kalender', 'ensemble'); ?></th>
                    <td>
                        <label class="es-toggle">
                            <input type="checkbox" name="enable_calendar_widget" value="1" 
                                <?php checked(!empty($settings['enable_calendar_widget']), true); ?>>
                            <span class="es-toggle-slider"></span>
                        </label>
                        <p class="description"><?php _e('Interaktiver Kalender mit Monats-, Wochen- und Tagesansicht.', 'ensemble'); ?></p>
                    </td>
                </tr>
            </table>
            
            <div class="es-addon-info" style="margin-top: 20px; padding: 15px; background: #f0f0f1; border-radius: 4px;">
                <h4 style="margin-top: 0;"><?php _e('Available Widgets', 'ensemble'); ?></h4>
                <ul style="margin: 0; padding-left: 20px;">
                    <li><strong>Events Grid</strong> – <?php _e('Grid, Liste, Masonry mit Filtern und Suche', 'ensemble'); ?></li>
                    <li><strong>Event Calendar</strong> – <?php _e('FullCalendar mit Drag & Drop (Admin)', 'ensemble'); ?></li>
                </ul>
                <p style="margin-bottom: 0; margin-top: 10px;">
                    <em><?php _e('More widgets (Artists, Locations, Single Event) coming in future updates.', 'ensemble'); ?></em>
                </p>
            </div>
        </div>
        <?php
    }
}
