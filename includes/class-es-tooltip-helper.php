<?php
/**
 * Ensemble Tooltip Helper System
 * 
 * Global system for contextual help tooltips
 *
 * @package Ensemble
 */

class ES_Tooltip_Helper {
    
    /**
     * Tooltip definitions
     * Central place for all tooltips in the plugin
     */
    private static $tooltips = [
        // Dashboard
        'dashboard_overview' => 'The dashboard shows an overview of all important events and statistics.',
        'dashboard_quick_actions' => 'Quick access to the most important functions for creating and managing.',
        
        // Wizard
        'wizard_basic_info' => 'Enter the basic information for your event here. The title is required.',
        'wizard_datetime' => 'Set the date and time for your event. You can also create recurring events.',
        'wizard_recurring' => 'Recurring events are automatically created as a series. You can adjust individual instances later.',
        'wizard_location' => 'Select a location or create a new one using the plus icon.',
        'wizard_artists' => 'Select one or more artists. Multiple selection is possible.',
        'wizard_categories' => 'Categories help with organization and can activate special wizard steps.',
        'wizard_custom_fields' => 'These fields are based on the selected category and your ACF settings.',
        
        // Taxonomies
        'taxonomy_categories' => 'Event categories organize your events and can trigger custom wizard steps.',
        'taxonomy_genres' => 'Genres help classify your artists by music style or type.',
        'taxonomy_location_types' => 'Location types categorize your venues (club, hall, outdoor, etc.).',
        'taxonomy_slug' => 'The slug is the URL-friendly version of the name. Automatically generated if left empty.',
        
        // Calendar
        'calendar_view' => 'The calendar shows all events in a monthly view. Click on an event for details.',
        'calendar_drag_drop' => 'You can move events via drag & drop. Recurring events are treated as a series.',
        'calendar_virtual_events' => 'Virtual events (from series) are marked with an icon.',
        
        // Artists
        'artist_manager' => 'Manage all artists, bands, or performers here.',
        'artist_genres' => 'Assign genres for better filtering and organization.',
        'artist_quick_add' => 'Quick-add allows you to quickly create new artists during event creation.',
        
        // Locations
        'location_manager' => 'Manage all venues and locations here.',
        'location_types' => 'Location types help categorize your venues.',
        'location_address' => 'The address is used for Google Maps and other integrations.',
        
        // Settings
        'settings_post_type' => 'Determines whether events are saved as standard posts or as a custom post type.',
        'settings_theme' => 'Choose between dark and light theme for the admin interface.',
        'settings_wizard_steps' => 'Configure custom ACF field groups that appear in the wizard for specific categories.',
        'settings_labels' => 'Customize the labels (e.g. "Artist" → "Pastor" for churches).',
        
        // Field Builder
        'field_builder_mapping' => 'Map your ACF fields to standard Ensemble fields for better integration.',
        'field_builder_auto_detect' => 'Auto-detection scans your ACF field groups and suggests mappings.',
        
        // Import/Export
        'import_ical' => 'Import events from iCal/ICS files. Supports all common calendar formats.',
        'import_mapping' => 'Map the fields from the import file to your Ensemble fields.',
        'export_format' => 'Choose the export format. iCal is compatible with all calendar apps.',
        
        // Recurring Events
        'recurring_pattern' => 'Define how often and at what interval the event should repeat.',
        'recurring_exceptions' => 'Exceptions allow you to skip or adjust individual dates.',
        'recurring_virtual' => 'Virtual events are placeholders from the series. You can convert them to real events.',
        
        // Frontend
        'frontend_designer' => 'Design the appearance of your events in the frontend without code.',
        'frontend_templates' => 'Choose from pre-made templates or create your own.',
        'frontend_shortcodes' => 'Use shortcodes to embed events, calendars, or lists on your pages.',
        
        // Onboarding
        'onboarding_usage_type' => 'Your selection determines the suggestions for categories and default settings.',
        'onboarding_labels' => 'Customize the terms for your industry (e.g. theater, church, fitness studio).',
        'onboarding_custom_fields' => 'ACF custom fields allow additional fields beyond the standard fields.',
        
        // Duration Types (Festival/Exhibition System)
        'duration_type' => 'Single: One-day events (concerts, lectures). Multi-Day: Events spanning multiple days (festivals, exhibitions). Permanent: Ongoing events without end date (permanent exhibitions).',
        'sub_events' => 'Enable sub-events for festivals or exhibitions: festival days, guided tours, vernissage, finissage, etc.',
    ];
    
    /**
     * Render a tooltip icon with help text
     * 
     * @param string $key Tooltip key from definitions
     * @param string $custom_text Optional custom text (overrides key)
     * @param string $position Tooltip position: top, right, bottom, left
     * @return string HTML for tooltip
     */
    public static function render($key, $custom_text = '', $position = 'top') {
        $text = !empty($custom_text) ? $custom_text : self::get_text($key);
        
        if (empty($text)) {
            return '';
        }
        
        $escaped_text = esc_attr($text);
        $position_class = 'es-tooltip-' . $position;
        
        return sprintf(
            '<span class="es-tooltip-wrapper %s" data-tooltip="%s">
                <span class="dashicons dashicons-editor-help es-tooltip-icon"></span>
            </span>',
            $position_class,
            $escaped_text
        );
    }
    
    /**
     * Get tooltip text by key
     * 
     * @param string $key Tooltip key
     * @return string Tooltip text or empty string
     */
    public static function get_text($key) {
        return isset(self::$tooltips[$key]) ? self::$tooltips[$key] : '';
    }
    
    /**
     * Add custom tooltip
     * 
     * @param string $key Unique key for tooltip
     * @param string $text Tooltip text
     */
    public static function add_tooltip($key, $text) {
        self::$tooltips[$key] = $text;
    }
    
    /**
     * Enqueue tooltip styles and scripts
     */
    public static function enqueue_assets() {
        // CSS is output inline for simplicity
        add_action('admin_head', [__CLASS__, 'print_styles']);
        add_action('admin_footer', [__CLASS__, 'print_scripts']);
    }
    
    /**
     * Print inline CSS for tooltips
     */
    public static function print_styles() {
        ?>
        <style>
        /* Tooltip System Styles */
        .es-tooltip-wrapper {
            position: relative;
            display: inline-flex;
            align-items: center;
            margin-left: 6px;
            vertical-align: middle;
        }
        
        .es-tooltip-icon {
            font-size: 16px !important;
            width: 16px !important;
            height: 16px !important;
            color: var(--es-info, #3582c4) !important;
            cursor: help;
            transition: all 0.2s ease;
        }
        
        .es-tooltip-icon:hover {
            color: var(--es-primary-hover, #2271b1) !important;
            transform: scale(1.1);
        }
        
        /* Tooltip Bubble */
        .es-tooltip-wrapper::before {
            content: attr(data-tooltip);
            position: absolute;
            padding: 8px 12px;
            background: var(--es-surface, #2c2c2c);
            color: var(--es-text, #e0e0e0);
            border: 2px solid var(--es-info, #3582c4);
            border-radius: 6px;
            font-size: 13px;
            line-height: 1.4;
            white-space: normal;
            max-width: 250px;
            width: max-content;
            z-index: 10000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.2s ease;
            pointer-events: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }
        
        /* Tooltip Arrow */
        .es-tooltip-wrapper::after {
            content: '';
            position: absolute;
            width: 0;
            height: 0;
            border: 6px solid transparent;
            z-index: 10001;
            opacity: 0;
            visibility: hidden;
            transition: all 0.2s ease;
        }
        
        /* Show tooltip on hover */
        .es-tooltip-wrapper:hover::before,
        .es-tooltip-wrapper:hover::after {
            opacity: 1;
            visibility: visible;
        }
        
        /* Position: Top (default) */
        .es-tooltip-wrapper.es-tooltip-top::before {
            bottom: calc(100% + 10px);
            left: 50%;
            transform: translateX(-50%);
        }
        
        .es-tooltip-wrapper.es-tooltip-top::after {
            bottom: calc(100% + 4px);
            left: 50%;
            transform: translateX(-50%);
            border-top-color: var(--es-info, #3582c4);
        }
        
        .es-tooltip-wrapper.es-tooltip-top:hover::before {
            bottom: calc(100% + 12px);
        }
        
        /* Position: Right */
        .es-tooltip-wrapper.es-tooltip-right::before {
            left: calc(100% + 10px);
            top: 50%;
            transform: translateY(-50%);
        }
        
        .es-tooltip-wrapper.es-tooltip-right::after {
            left: calc(100% + 4px);
            top: 50%;
            transform: translateY(-50%);
            border-right-color: var(--es-info, #3582c4);
        }
        
        .es-tooltip-wrapper.es-tooltip-right:hover::before {
            left: calc(100% + 12px);
        }
        
        /* Position: Bottom */
        .es-tooltip-wrapper.es-tooltip-bottom::before {
            top: calc(100% + 10px);
            left: 50%;
            transform: translateX(-50%);
        }
        
        .es-tooltip-wrapper.es-tooltip-bottom::after {
            top: calc(100% + 4px);
            left: 50%;
            transform: translateX(-50%);
            border-bottom-color: var(--es-info, #3582c4);
        }
        
        .es-tooltip-wrapper.es-tooltip-bottom:hover::before {
            top: calc(100% + 12px);
        }
        
        /* Position: Left */
        .es-tooltip-wrapper.es-tooltip-left::before {
            right: calc(100% + 10px);
            top: 50%;
            transform: translateY(-50%);
        }
        
        .es-tooltip-wrapper.es-tooltip-left::after {
            right: calc(100% + 4px);
            top: 50%;
            transform: translateY(-50%);
            border-left-color: var(--es-info, #3582c4);
        }
        
        .es-tooltip-wrapper.es-tooltip-left:hover::before {
            right: calc(100% + 12px);
        }
        
        /* Mobile: Always show on top, smaller text */
        @media screen and (max-width: 782px) {
            .es-tooltip-wrapper::before {
                max-width: 200px;
                font-size: 12px;
                padding: 6px 10px;
            }
            
            .es-tooltip-wrapper.es-tooltip-right::before,
            .es-tooltip-wrapper.es-tooltip-bottom::before,
            .es-tooltip-wrapper.es-tooltip-left::before {
                bottom: calc(100% + 10px);
                top: auto;
                left: 50%;
                right: auto;
                transform: translateX(-50%);
            }
            
            .es-tooltip-wrapper.es-tooltip-right::after,
            .es-tooltip-wrapper.es-tooltip-bottom::after,
            .es-tooltip-wrapper.es-tooltip-left::after {
                bottom: calc(100% + 4px);
                top: auto;
                left: 50%;
                right: auto;
                transform: translateX(-50%);
                border-color: transparent;
                border-top-color: var(--es-info, #3582c4);
            }
        }
        </style>
        <?php
    }
    
    /**
     * Print inline JavaScript for enhanced tooltip behavior
     */
    public static function print_scripts() {
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Optional: Close tooltip on click outside (mobile)
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.es-tooltip-wrapper').length) {
                    // Could add active class handling here if needed
                }
            });
            
            // Optional: Prevent tooltip from going off-screen
            $('.es-tooltip-wrapper').on('mouseenter', function() {
                var $tooltip = $(this);
                var $bubble = $tooltip.find('::before');
                
                // Could add position adjustment logic here
                // For now, CSS handles it well enough
            });
        });
        </script>
        <?php
    }
}

// Initialize tooltip system
add_action('admin_enqueue_scripts', ['ES_Tooltip_Helper', 'enqueue_assets']);