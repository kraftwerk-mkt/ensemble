<?php
/**
 * Asset Management
 * 
 * Handles loading of CSS and JavaScript files
 *
 * @package Ensemble
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class ES_Assets {
    
    /**
     * Enqueue admin styles
     */
    public function enqueue_admin_styles() {
        // Only load on Ensemble pages
        if (!$this->is_ensemble_page()) {
            return;
        }
        
        // Base admin styles
        wp_enqueue_style(
        'ensemble-admin-unified',
        ENSEMBLE_PLUGIN_URL . 'assets/css/admin-unified.css',
        array(),
        ENSEMBLE_VERSION
    );
    
    // (Wizard-spezifisch)
    wp_enqueue_style(
        'ensemble-admin',
        ENSEMBLE_PLUGIN_URL . 'assets/css/admin.css',
        array('ensemble-admin-unified'),  // ← Abhängigkeit!
        ENSEMBLE_VERSION
    );
        
        // Unified Toggle Component - Single Source of Truth for all toggles
        wp_enqueue_style(
            'ensemble-toggle-unified',
            ENSEMBLE_PLUGIN_URL . 'assets/css/toggle-unified.css',
            array('ensemble-admin-unified'),
            ENSEMBLE_VERSION,
            'all'
        );
        
        // Get current screen
        $screen = get_current_screen();
        
        // Load dashboard CSS on dashboard page
        if ($screen && strpos($screen->id, 'toplevel_page_ensemble') !== false) {
            wp_enqueue_style(
                'ensemble-dashboard',
                ENSEMBLE_PLUGIN_URL . 'assets/css/dashboard.css',
                array('ensemble-admin-unified'),
                ENSEMBLE_VERSION,
                'all'
            );
        }
        
        if ($screen && strpos($screen->id, 'ensemble-calendar') !== false) {
            wp_enqueue_style(
                'ensemble-calendar',
                ENSEMBLE_PLUGIN_URL . 'assets/css/calendar.css',
                array('ensemble-admin-unified'),
                ENSEMBLE_VERSION,
                'all'
            );
            
            // Drag & Drop CSS
            wp_enqueue_style(
                'ensemble-calendar-drag',
                ENSEMBLE_PLUGIN_URL . 'assets/css/calendar-drag.css',
                array('ensemble-calendar'),
                ENSEMBLE_VERSION,
                'all'
            );
        }
        
        // Load manager CSS on artist/location pages
        if ($screen && (strpos($screen->id, 'ensemble-artists') !== false || strpos($screen->id, 'ensemble-locations') !== false)) {
            wp_enqueue_style(
                'ensemble-manager',
                ENSEMBLE_PLUGIN_URL . 'assets/css/manager.css',
                array('ensemble-admin-unified'),
                ENSEMBLE_VERSION,
                'all'
            );
        }
        
        // Quick-Add Modal CSS (load on dashboard and wizard page)
        if ($screen && (strpos($screen->id, 'toplevel_page_ensemble') !== false || strpos($screen->id, 'ensemble-wizard') !== false)) {
            wp_enqueue_style(
                'ensemble-quick-add-modal',
                ENSEMBLE_PLUGIN_URL . 'assets/css/quick-add-modal.css',
                array('ensemble-admin-unified'),
                ENSEMBLE_VERSION,
                'all'
            );
        }
        
        // Import/Export CSS
        if ($screen && (strpos($screen->id, 'ensemble-import') !== false || strpos($screen->id, 'ensemble-export') !== false)) {
            wp_enqueue_style(
                'ensemble-import-export',
                ENSEMBLE_PLUGIN_URL . 'assets/css/import-export.css',
                array('ensemble-admin-unified'),
                ENSEMBLE_VERSION,
                'all'
            );
        }
        
        // Field Builder CSS
        if ($screen && strpos($screen->id, 'ensemble-field-builder') !== false) {
            wp_enqueue_style(
                'ensemble-field-builder',
                ENSEMBLE_PLUGIN_URL . 'assets/css/field-builder.css',
                array('ensemble-admin-unified'),
                ENSEMBLE_VERSION,
                'all'
            );
        }
        
        // Load theme
        $theme = get_option('ensemble_theme', 'dark');
        wp_enqueue_style(
            'ensemble-theme',
            ENSEMBLE_PLUGIN_URL . 'assets/css/theme-' . $theme . '.css',
            array('ensemble-admin-unified'),
            ENSEMBLE_VERSION,
            'all'
        );
        
        // Settings Dark Theme - Only on settings page for dark theme
        if ($screen && strpos($screen->id, 'ensemble-settings') !== false && $theme === 'dark') {
            wp_enqueue_style(
                'ensemble-settings-dark',
                ENSEMBLE_PLUGIN_URL . 'assets/css/settings-dark-theme.css',
                array('ensemble-theme'),
                ENSEMBLE_VERSION
            );
        }

        // Layout-Sets Tab CSS - Load on any Ensemble page
        if ($screen && strpos($screen->id, 'ensemble') !== false) {
            wp_enqueue_style(
                'ensemble-layout-sets',
                ENSEMBLE_PLUGIN_URL . 'assets/css/layout-sets.css',
                array('ensemble-admin-unified'),
                ENSEMBLE_VERSION
            );
        }
    }
    
    
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts() {
        // Only load on Ensemble pages
        if (!$this->is_ensemble_page()) {
            return;
        }
        
        wp_enqueue_media(); // For image upload
        
        // Get current screen
        $screen = get_current_screen();
        
        // ALWAYS load admin.js on Ensemble pages (needed for shared functionality)
        wp_enqueue_script(
            'ensemble-admin',
            ENSEMBLE_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'jquery-ui-sortable'),
            ENSEMBLE_VERSION,
            true
        );
        
        // IMPORT PAGE - add import-specific script
        if ($screen && strpos($screen->id, 'ensemble-import') !== false) {
            wp_enqueue_script(
                'ensemble-import-tab',
                ENSEMBLE_PLUGIN_URL . 'assets/js/import-tab.js',
                array('jquery', 'ensemble-admin'),
                ENSEMBLE_VERSION,
                true
            );
            
            wp_localize_script('ensemble-import-tab', 'ensembleImportData', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ensemble_import_nonce'),
            ));
        }
        
        // EXPORT PAGE - add export-specific script
        if ($screen && strpos($screen->id, 'ensemble-export') !== false) {
            wp_enqueue_script(
                'ensemble-export-tab',
                ENSEMBLE_PLUGIN_URL . 'assets/js/export-tab.js',
                array('jquery', 'ensemble-admin'),
                ENSEMBLE_VERSION,
                true
            );
            
            wp_localize_script('ensemble-export-tab', 'ensembleExportData', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ensemble_export_nonce'),
            ));
        }
        
        // Toggle Switches JS
        wp_enqueue_script(
            'ensemble-toggle-switches',
            ENSEMBLE_PLUGIN_URL . 'assets/js/toggle-switches.js',
            array('jquery', 'ensemble-admin'),
            ENSEMBLE_VERSION,
            true
        );
        
        // Load recurring events JS on wizard/dashboard page
        if ($screen && (strpos($screen->id, 'toplevel_page_ensemble') !== false || strpos($screen->id, 'ensemble-wizard') !== false)) {
            wp_enqueue_script(
                'ensemble-recurring',
                ENSEMBLE_PLUGIN_URL . 'assets/js/recurring.js',
                array('jquery', 'ensemble-admin'),
                ENSEMBLE_VERSION,
                true
            );
            
            // Quick-Add Modal JS
            wp_enqueue_script(
                'ensemble-quick-add-modal',
                ENSEMBLE_PLUGIN_URL . 'assets/js/quick-add-modal.js',
                array('jquery', 'ensemble-admin'),
                ENSEMBLE_VERSION,
                true
            );
        }
        
        // Load calendar JS on calendar page
        if ($screen && strpos($screen->id, 'ensemble-calendar') !== false) {
            wp_enqueue_script(
                'ensemble-calendar',
                ENSEMBLE_PLUGIN_URL . 'assets/js/calendar.js',
                array('jquery', 'ensemble-admin'),
                ENSEMBLE_VERSION,
                true
            );
            
            // Localization for drag & drop
            wp_localize_script('ensemble-calendar', 'ensembleL10n', array(
                'confirmMove' => __('Move this event?', 'ensemble'),
                'virtualEventNotice' => __('This is a recurring event. Moving it will create an exception for this date only.', 'ensemble'),
                'confirm' => __('Move Event', 'ensemble'),
                'cancel' => __('Cancel', 'ensemble'),
                'errorMoving' => __('Error moving event. Please try again.', 'ensemble'),
                'locale' => get_locale()
            ));
        }
        
        // Load artist manager JS
        if ($screen && strpos($screen->id, 'ensemble-artists') !== false) {
            // Load media manager first
            wp_enqueue_script(
                'ensemble-media-manager',
                ENSEMBLE_PLUGIN_URL . 'assets/js/media-manager.js',
                array('jquery', 'jquery-ui-sortable'),
                ENSEMBLE_VERSION,
                true
            );
            
            wp_enqueue_script(
                'ensemble-artist-manager',
                ENSEMBLE_PLUGIN_URL . 'assets/js/artist-manager.js',
                array('jquery', 'ensemble-admin', 'ensemble-media-manager'),
                ENSEMBLE_VERSION,
                true
            );
        }
        
        // Load location manager JS
        if ($screen && strpos($screen->id, 'ensemble-locations') !== false) {
            // Load media manager first
            wp_enqueue_script(
                'ensemble-media-manager',
                ENSEMBLE_PLUGIN_URL . 'assets/js/media-manager.js',
                array('jquery', 'jquery-ui-sortable'),
                ENSEMBLE_VERSION,
                true
            );
            
            wp_enqueue_script(
                'ensemble-location-manager',
                ENSEMBLE_PLUGIN_URL . 'assets/js/location-manager.js',
                array('jquery', 'ensemble-admin', 'ensemble-media-manager'),
                ENSEMBLE_VERSION,
                true
            );
        }
        
        if ($screen && (strpos($screen->id, 'ensemble-settings') !== false || 
                        strpos($screen->id, 'ensemble-frontend') !== false)) {
            wp_enqueue_script(
                'ensemble-layout-sets-editor',
                ENSEMBLE_PLUGIN_URL . 'assets/js/layout-sets-editor.js',
                array('jquery'),
                ENSEMBLE_VERSION,
                true
            );
        }
        
        // Load settings JS on settings page
        if ($screen && strpos($screen->id, 'ensemble-settings') !== false) {
            wp_enqueue_script(
                'ensemble-settings',
                ENSEMBLE_PLUGIN_URL . 'assets/js/settings.js',
                array('jquery'),
                ENSEMBLE_VERSION,
                true
            );
        }
        
        // Localize script with icons
        $icons = array(
            'calendar' => ES_Icons::get('calendar'),
            'location' => ES_Icons::get('location'),
            'artist' => ES_Icons::get('artist'),
            'band' => ES_Icons::get('band'),
            'sync' => ES_Icons::get('sync'),
            'duplicate' => ES_Icons::get('duplicate'),
            'edit' => ES_Icons::get('edit'),
            'trash' => ES_Icons::get('trash'),
            'plus' => ES_Icons::get('plus'),
            'search' => ES_Icons::get('search'),
            'dashboard' => ES_Icons::get('dashboard'),
            'event_grid' => ES_Icons::get('event_grid'),
            'category' => ES_Icons::get('category'),
        );
        
        wp_localize_script('ensemble-admin', 'ES_ICONS', $icons);
        
        // Get artists for Agenda speaker picker
        $artists_for_agenda = array();
        if (class_exists('ES_Wizard')) {
            $wizard = new ES_Wizard();
            $all_artists = $wizard->get_artists();
            if (!empty($all_artists)) {
                foreach ($all_artists as $artist) {
                    $artists_for_agenda[] = array(
                        'id' => isset($artist['id']) ? $artist['id'] : (isset($artist->ID) ? $artist->ID : 0),
                        'name' => isset($artist['name']) ? $artist['name'] : (isset($artist->post_title) ? $artist->post_title : ''),
                        'image' => isset($artist['image']) ? $artist['image'] : '',
                        'role' => isset($artist['role']) ? $artist['role'] : '',
                    );
                }
            }
        }
        
        wp_localize_script('ensemble-admin', 'ensembleAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('ensemble_nonce'),
            'wizard_nonce' => wp_create_nonce('ensemble-wizard'), // For Quick-Add Modal
            'wizardUrl' => admin_url('admin.php?page=ensemble'),
            'calendarUrl' => admin_url('admin.php?page=ensemble-calendar'),
            'pluginUrl' => ENSEMBLE_PLUGIN_URL,
            'strings' => array(
                'saving'        => __('Saving...', 'ensemble'),
                'saved'         => __('Event saved successfully!', 'ensemble'),
                'error'         => __('Error saving event. Please try again.', 'ensemble'),
                'deleteConfirm' => __('Are you sure you want to delete this event?', 'ensemble'),
                'unsavedChanges'=> __('You have unsaved changes. Do you want to leave?', 'ensemble'),
            ),
        ));
        
        // Wizard data for Agenda Add-on and other components
        wp_localize_script('ensemble-admin', 'esWizardData', array(
            'artists' => $artists_for_agenda,
            'event' => array(
                'agenda' => array('days' => array(), 'rooms' => array(), 'tracks' => array()),
            ),
        ));
    }
    
    /**
     * Enqueue frontend styles
     */
    public function enqueue_frontend_styles() {
        
        // =============================================
        // SELECTIVE LOADING (NEW - via ES_CSS_Loader)
        // =============================================
        if (class_exists('ES_CSS_Loader') && ES_CSS_Loader::is_enabled()) {
            // ES_CSS_Loader handles everything:
            // - Base CSS (auto-loaded)
            // - Shortcode CSS (on-demand via ES_CSS_Loader::enqueue())
            // - Layout CSS (auto-detected)
            
            // Only need to register slider styles here for manual enqueueing
            wp_register_style(
                'ensemble-slider',
                ENSEMBLE_PLUGIN_URL . 'assets/css/ensemble-slider.css',
                array('ensemble-components'),
                ENSEMBLE_VERSION,
                'all'
            );
            
            wp_register_script(
                'ensemble-slider',
                ENSEMBLE_PLUGIN_URL . 'assets/js/ensemble-slider.js',
                array(),
                ENSEMBLE_VERSION,
                true
            );
            
            return; // ES_CSS_Loader handles the rest
        }
        
        // =============================================
        // LEGACY LOADING (Fallback)
        // =============================================
        
        // 1. BASE CSS - Variablen & Utility Classes
        wp_enqueue_style(
            'ensemble-base',
            ENSEMBLE_PLUGIN_URL . 'assets/css/layouts/ensemble-base.css',
            array(),
            ENSEMBLE_VERSION,
            'all'
        );
        
        // 2. Shortcode CSS (Legacy - alle Styles)
        wp_enqueue_style(
            'ensemble-shortcodes',
            ENSEMBLE_PLUGIN_URL . 'assets/css/shortcodes.css',
            array('ensemble-base'),
            ENSEMBLE_VERSION,
            'all'
        );
        
        // 2.1 Additional Layout Styles
        wp_enqueue_style(
            'ensemble-layouts',
            ENSEMBLE_PLUGIN_URL . 'assets/css/ensemble-layouts.css',
            array('ensemble-shortcodes'),
            ENSEMBLE_VERSION,
            'all'
        );
        
        // 2.2 Slider Styles - conditionally enqueued
        wp_register_style(
            'ensemble-slider',
            ENSEMBLE_PLUGIN_URL . 'assets/css/ensemble-slider.css',
            array('ensemble-layouts'),
            ENSEMBLE_VERSION,
            'all'
        );
        
        wp_register_script(
            'ensemble-slider',
            ENSEMBLE_PLUGIN_URL . 'assets/js/ensemble-slider.js',
            array(),
            ENSEMBLE_VERSION,
            true
        );
        
        // 3. Layout-Set CSS - ZULETZT laden (höchste Priorität)
        if (class_exists('ES_Layout_Sets')) {
            $active_set = ES_Layout_Sets::get_active_set();
            $set_data = ES_Layout_Sets::get_set_data($active_set);
            
            if (!empty($set_data['path'])) {
                $style_path = $set_data['path'] . '/style.css';
                
                if (file_exists($style_path)) {
                    // Convert path to URL
                    if (defined('ENSEMBLE_PLUGIN_DIR') && strpos($style_path, ENSEMBLE_PLUGIN_DIR) === 0) {
                        $style_url = str_replace(ENSEMBLE_PLUGIN_DIR, ENSEMBLE_PLUGIN_URL, $style_path);
                    } else {
                        $style_url = str_replace(WP_CONTENT_DIR, WP_CONTENT_URL, $style_path);
                    }
                    
                    wp_enqueue_style(
                        'ensemble-layout-' . $active_set,
                        $style_url,
                        array('ensemble-base', 'ensemble-shortcodes'),
                        ENSEMBLE_VERSION,
                        'all'
                    );
                    
                    // Load layout-specific JS if exists
                    $js_path = $set_data['path'] . '/slider.js';
                    if (file_exists($js_path)) {
                        if (defined('ENSEMBLE_PLUGIN_DIR') && strpos($js_path, ENSEMBLE_PLUGIN_DIR) === 0) {
                            $js_url = str_replace(ENSEMBLE_PLUGIN_DIR, ENSEMBLE_PLUGIN_URL, $js_path);
                        } else {
                            $js_url = str_replace(WP_CONTENT_DIR, WP_CONTENT_URL, $js_path);
                        }
                        
                        wp_enqueue_script(
                            'ensemble-layout-' . $active_set . '-js',
                            $js_url,
                            array(),
                            ENSEMBLE_VERSION,
                            true
                        );
                    }
                }
            }
        }
    }
    
    /**
     * Enqueue frontend scripts
     */
    public function enqueue_frontend_scripts() {
        // Check if we're on a page with Ensemble shortcodes
        global $post;
        if (!is_a($post, 'WP_Post')) {
            return;
        }
        
        // Only load if shortcodes are present
        if (has_shortcode($post->post_content, 'ensemble_events') || 
            has_shortcode($post->post_content, 'ensemble_calendar') ||
            has_shortcode($post->post_content, 'ensemble_artists') ||
            has_shortcode($post->post_content, 'ensemble_locations')) {
            
            wp_enqueue_script(
                'ensemble-frontend-js',
                ENSEMBLE_PLUGIN_URL . 'assets/js/frontend.js',
                array('jquery'),
                ENSEMBLE_VERSION,
                true
            );
            
            // Localize script for AJAX
            wp_localize_script('ensemble-frontend-js', 'ensembleData', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ensemble_frontend_nonce'),
            ));
        }
    }
    
    /**
     * Check if current page is an Ensemble page
     * @return bool
     */
    private function is_ensemble_page() {
        $screen = get_current_screen();
        if (!$screen) {
            return false;
        }
        
        // Check if it's our admin page
        if (strpos($screen->id, 'ensemble') !== false) {
            return true;
        }
        
        // Check if it's our post types
        if (in_array($screen->post_type, array('ensemble_artist', 'ensemble_location'))) {
            return true;
        }
        
        return false;
    }
}