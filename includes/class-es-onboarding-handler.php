<?php
/**
 * Ensemble Onboarding Handler
 * 
 * Handles AJAX requests and admin page setup for onboarding
 *
 * @package Ensemble
 */

class ES_Onboarding_Handler {
    
    /**
     * Register onboarding admin page
     */
    public static function register_admin_page() {
        // Always register the page (needed for reset functionality from settings)
        // But only show in menu if not completed
        
        if (!ES_Label_System::is_onboarding_completed()) {
            // Add as hidden parent page
            add_menu_page(
                __('Ensemble Setup', 'ensemble'),
                __('Ensemble Setup', 'ensemble'),
                'manage_options',
                'ensemble-onboarding',
                [__CLASS__, 'render_onboarding_page'],
                'dashicons-welcome-learn-more',
                3 // High priority
            );
            
            // Also add as submenu under Ensemble for easy access
            add_submenu_page(
                'ensemble',
                __('Setup Wizard', 'ensemble'),
                __('Setup Wizard', 'ensemble'),
                'manage_options',
                'ensemble-onboarding',
                [__CLASS__, 'render_onboarding_page']
            );
        } else {
            // Register page without menu entry (for reset access)
            add_submenu_page(
                null, // No parent = hidden from menu
                __('Ensemble Setup', 'ensemble'),
                __('Ensemble Setup', 'ensemble'),
                'manage_options',
                'ensemble-onboarding',
                [__CLASS__, 'render_onboarding_page']
            );
        }
    }
    
    /**
     * Render onboarding page
     */
    public static function render_onboarding_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to access this page.', 'ensemble'));
        }
        
        include ENSEMBLE_PLUGIN_DIR . 'admin/onboarding.php';
    }
    
    /**
     * Enqueue assets for onboarding page
     */
    public static function enqueue_assets($hook) {
        // Multiple checks to ensure assets load
        $is_onboarding = false;
        
        // Check 1: Hook contains 'ensemble-onboarding'
        if (!empty($hook) && strpos($hook, 'ensemble-onboarding') !== false) {
            $is_onboarding = true;
        }
        
        // Check 2: $_GET parameter
        if (isset($_GET['page']) && $_GET['page'] === 'ensemble-onboarding') {
            $is_onboarding = true;
        }
        
        // Check 3: Current screen
        $screen = get_current_screen();
        if ($screen && !empty($screen->id) && strpos($screen->id, 'ensemble-onboarding') !== false) {
            $is_onboarding = true;
        }
        
        if (!$is_onboarding) {
            return;
        }
        
        // CSS
        wp_enqueue_style(
            'ensemble-admin-unified',
            ENSEMBLE_PLUGIN_URL . 'assets/css/admin-unified.css',
            [],
            ENSEMBLE_VERSION
        );
        
        wp_enqueue_style(
            'ensemble-buttons-unified',
            ENSEMBLE_PLUGIN_URL . 'assets/css/ensemble-buttons-unified.css',
            ['ensemble-admin-unified'],
            ENSEMBLE_VERSION
        );
        
        wp_enqueue_style(
            'ensemble-onboarding',
            ENSEMBLE_PLUGIN_URL . 'assets/css/onboarding.css',
            ['ensemble-admin-unified', 'ensemble-buttons-unified'],
            ENSEMBLE_VERSION
        );
        
        // JavaScript
        wp_enqueue_script(
            'ensemble-onboarding',
            ENSEMBLE_PLUGIN_URL . 'assets/js/onboarding.js',
            ['jquery'],
            ENSEMBLE_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('ensemble-onboarding', 'ensembleOnboardingData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ensemble_onboarding'),
            'dashboardUrl' => admin_url('admin.php?page=ensemble'),
            'fieldMapperUrl' => admin_url('admin.php?page=ensemble-field-builder'),
        ]);
    }
    
    /**
     * Handle AJAX onboarding completion
     */
    public static function ajax_complete_onboarding() {
        // Verify nonce
        if (!check_ajax_referer('ensemble_onboarding', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed.', 'ensemble')]);
            return;
        }
        
        // Verify permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'ensemble')]);
            return;
        }
        
        // Collect and sanitize data
        $data = [
            'usage_type' => isset($_POST['usage_type']) ? sanitize_text_field($_POST['usage_type']) : '',
            'experience_level' => isset($_POST['experience_level']) ? sanitize_text_field($_POST['experience_level']) : '',
            'has_custom_fields' => isset($_POST['has_custom_fields']) ? sanitize_text_field($_POST['has_custom_fields']) : '',
            'artist_label_singular' => isset($_POST['artist_label_singular']) ? sanitize_text_field($_POST['artist_label_singular']) : '',
            'artist_label_plural' => isset($_POST['artist_label_plural']) ? sanitize_text_field($_POST['artist_label_plural']) : '',
            'location_label_singular' => isset($_POST['location_label_singular']) ? sanitize_text_field($_POST['location_label_singular']) : '',
            'location_label_plural' => isset($_POST['location_label_plural']) ? sanitize_text_field($_POST['location_label_plural']) : '',
            'event_label_singular' => isset($_POST['event_label_singular']) ? sanitize_text_field($_POST['event_label_singular']) : '',
            'event_label_plural' => isset($_POST['event_label_plural']) ? sanitize_text_field($_POST['event_label_plural']) : '',
            'label_source' => isset($_POST['label_source']) ? sanitize_text_field($_POST['label_source']) : 'default',
        ];
        
        // Validate required fields
        if (empty($data['usage_type']) || empty($data['experience_level'])) {
            wp_send_json_error(['message' => __('Please fill in all required fields.', 'ensemble')]);
            return;
        }
        
        // Save configuration
        $success = ES_Label_System::save_onboarding_config($data);
        
        if ($success) {
            // Create default categories based on usage type
            self::create_default_categories($data['usage_type']);
            
            wp_send_json_success([
                'message' => __('Onboarding completed successfully!', 'ensemble'),
                'redirect' => $data['has_custom_fields'] === 'yes' 
                    ? admin_url('admin.php?page=ensemble-field-builder')
                    : admin_url('admin.php?page=ensemble')
            ]);
        } else {
            wp_send_json_error(['message' => __('Error saving configuration.', 'ensemble')]);
        }
    }
    
    /**
     * Create default categories based on usage type
     */
    private static function create_default_categories($usage_type) {
        $category_suggestions = [
            'clubs' => ['Techno', 'House', 'Hip Hop', 'Live'],
            'theater' => ['Drama', 'Musical', 'Comedy', 'Reading'],
            'church' => ['Service', 'Baptism', 'Wedding', 'Memorial'],
            'fitness' => ['Yoga', 'Pilates', 'HIIT', 'Meditation'],
            'education' => ['Workshop', 'Seminar', 'Training', 'Lecture'],
            'kongress' => ['Keynote', 'Panel', 'Workshop', 'Networking'],
            'museum' => ['Exhibition', 'Vernissage', 'Tour', 'Workshop'],
            'sports' => ['Match', 'Tournament', 'Training', 'Competition'],
            'public' => ['Tour', 'Exhibition', 'Lecture', 'Festival'],
            'mixed' => ['Event', 'Workshop', 'Concert', 'Exhibition'],
        ];
        
        $categories = isset($category_suggestions[$usage_type]) 
            ? $category_suggestions[$usage_type] 
            : ['Event'];
        
        foreach ($categories as $category) {
            // Check if category already exists
            if (!term_exists($category, 'ensemble_category')) {
                wp_insert_term($category, 'ensemble_category');
            }
        }
    }
    
    /**
     * Maybe redirect to onboarding on first activation
     */
    public static function maybe_redirect_to_onboarding() {
        // Handle reset parameter EARLY (before any output)
        if (isset($_GET['page']) && $_GET['page'] === 'ensemble-onboarding' && isset($_GET['reset']) && $_GET['reset'] == '1') {
            // Check user capabilities
            if (!current_user_can('manage_options')) {
                return;
            }
            
            // Verify nonce
            if (isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'ensemble_reset_onboarding')) {
                ES_Label_System::reset_onboarding();
                
                // Redirect to clean URL without reset parameter
                wp_safe_redirect(admin_url('admin.php?page=ensemble-onboarding'));
                exit;
            }
        }
        
        // Check if this is a redirect flag
        if (get_transient('ensemble_onboarding_redirect')) {
            delete_transient('ensemble_onboarding_redirect');
            
            // Only redirect if not already completed and not doing AJAX
            if (!ES_Label_System::is_onboarding_completed() && !wp_doing_ajax()) {
                wp_safe_redirect(admin_url('admin.php?page=ensemble-onboarding'));
                exit;
            }
        }
    }
    
    /**
     * Set redirect flag on plugin activation
     * (Call this from the main plugin activation hook)
     */
    public static function set_activation_redirect() {
        // Only set redirect if onboarding not completed
        if (!ES_Label_System::is_onboarding_completed()) {
            set_transient('ensemble_onboarding_redirect', true, 30);
        }
    }
}