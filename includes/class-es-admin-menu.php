<?php
/**
 * Admin Menu
 * 
 * Registers admin menu structure
 *
 * @package Ensemble
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class ES_Admin_Menu {
    
    /**
     * Register menu
     */
    public function register_menu() {
        // Main menu - Dashboard
        add_menu_page(
            __('Ensemble', 'ensemble'),
            __('Ensemble', 'ensemble'),
            'manage_options',
            'ensemble',
            array($this, 'render_dashboard_page'),
            'dashicons-tickets-alt',
            30
        );
        
        // Dashboard (same as main menu)
        add_submenu_page(
            'ensemble',
            __('Dashboard', 'ensemble'),
            __('Dashboard', 'ensemble'),
            'manage_options',
            'ensemble',
            array($this, 'render_dashboard_page')
        );
        
        // Event Wizard
        add_submenu_page(
            'ensemble',
            __('Event Wizard', 'ensemble'),
            __('Event Wizard', 'ensemble'),
            'manage_options',
            'ensemble-wizard',
            array($this, 'render_wizard_page')
        );
        
        // Calendar
        add_submenu_page(
            'ensemble',
            __('Calendar', 'ensemble'),
            __('Calendar', 'ensemble'),
            'manage_options',
            'ensemble-calendar',
            array($this, 'render_calendar_page')
        );
        
        // Artists - Dynamisches Label
        $artist_label = ES_Label_System::get_label('artist', true);
        add_submenu_page(
            'ensemble',
            $artist_label,
            $artist_label,
            'manage_options',
            'ensemble-artists',
            array($this, 'render_artists_page')
        );
        
        // Locations - Dynamisches Label
        $location_label = ES_Label_System::get_label('location', true);
        add_submenu_page(
            'ensemble',
            $location_label,
            $location_label,
            'manage_options',
            'ensemble-locations',
            array($this, 'render_locations_page')
        );
        
        // Galleries
        add_submenu_page(
            'ensemble',
            __('Galleries', 'ensemble'),
            __('Galleries', 'ensemble'),
            'manage_options',
            'ensemble-galleries',
            array($this, 'render_galleries_page')
        );
        
        // Taxonomies
        add_submenu_page(
            'ensemble',
            __('Taxonomies', 'ensemble'),
            __('Taxonomies', 'ensemble'),
            'manage_options',
            'ensemble-taxonomies',
            array($this, 'render_taxonomies_page')
        );
        
        // ✅ NEW: Field Builder
        add_submenu_page(
            'ensemble',
            __('Field Builder', 'ensemble'),
            __('Field Builder', 'ensemble'),
            'manage_options',
            'ensemble-field-builder',
            array($this, 'render_field_builder_page')
        );
        
        // Frontend (Shortcodes & Designer)
        add_submenu_page(
            'ensemble',
            __('Frontend', 'ensemble'),
            __('Frontend', 'ensemble'),
            'manage_options',
            'ensemble-frontend',
            array($this, 'render_frontend_page')
        );
        
        // Import Submenu Page
        add_submenu_page(
            'ensemble',                           // ✅ KORRIGIERT!
            __('Import Events', 'ensemble'),
            __('Import', 'ensemble'),
            'manage_options',
            'ensemble-import',
            array($this, 'render_import_page')
        );
        
        // Export Submenu Page
        add_submenu_page(
            'ensemble',                           // ✅ KORRIGIERT!
            __('Export Events', 'ensemble'),
            __('Export', 'ensemble'),
            'manage_options',
            'ensemble-export',
            array($this, 'render_export_page')
        );
        
        // Settings
        add_submenu_page(
            'ensemble',
            __('Settings', 'ensemble'),
            __('Settings', 'ensemble'),
            'manage_options',
            'ensemble-settings',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Render dashboard page
     */
    public function render_dashboard_page() {
        require_once ENSEMBLE_PLUGIN_DIR . 'admin/dashboard.php';
    }
    
    /**
     * Render wizard page
     */
    public function render_wizard_page() {
        require_once ENSEMBLE_PLUGIN_DIR . 'admin/wizard.php';
    }
    
    /**
     * Render calendar page
     */
    public function render_calendar_page() {
        require_once ENSEMBLE_PLUGIN_DIR . 'admin/calendar.php';
    }
    
    /**
     * Render artists page
     */
    public function render_artists_page() {
        require_once ENSEMBLE_PLUGIN_DIR . 'admin/artists.php';
    }
    
    /**
     * Render locations page
     */
    public function render_locations_page() {
        require_once ENSEMBLE_PLUGIN_DIR . 'admin/locations.php';
    }
    
    /**
     * Render galleries page
     */
    public function render_galleries_page() {
        require_once ENSEMBLE_PLUGIN_DIR . 'admin/galleries.php';
    }
    
    /**
     * Render taxonomies page
     */
    public function render_taxonomies_page() {
        require_once ENSEMBLE_PLUGIN_DIR . 'admin/taxonomies.php';
    }
    
    /**
     * Render field builder page
     */
    public function render_field_builder_page() {
        require_once ENSEMBLE_PLUGIN_DIR . 'admin/field-builder.php';
    }
    
    /**
     * Render frontend page
     */
    public function render_frontend_page() {
        require_once ENSEMBLE_PLUGIN_DIR . 'admin/frontend.php';
    }
    
    /**
     * Render import page
     */
    public function render_import_page() {
        require_once ENSEMBLE_PLUGIN_DIR . 'admin/import.php';
    }
    
    /**
     * Render export page
     */
    public function render_export_page() {
        require_once ENSEMBLE_PLUGIN_DIR . 'admin/export.php';
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        require_once ENSEMBLE_PLUGIN_DIR . 'admin/settings.php';
    }
}