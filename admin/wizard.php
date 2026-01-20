<?php
/**
 * Event Wizard Template
 *
 * @package Ensemble
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$wizard = new ES_Wizard();
$locations = $wizard->get_locations();
$artists = $wizard->get_artists();
$categories = $wizard->get_categories();
$genres = $wizard->get_genres();

// Dynamic Labels
$event_singular = ensemble_label('event');
$event_plural = ensemble_label('event', true);
$location_singular = ensemble_label('location');
$location_plural = ensemble_label('location', true);
$artist_singular = ensemble_label('artist');
$artist_plural = ensemble_label('artist', true);

// Get wizard fields configuration
$default_wizard_fields = array('time', 'time_end', 'location', 'artist', 'description', 'additional_info', 'price', 'ticket_url', 'button_text', 'external_link');

// Check if configuration was ever saved
$wizard_fields_configured = get_option('ensemble_wizard_fields_configured', false);

if ($wizard_fields_configured) {
    // Config was saved - use saved value (even if empty array)
    $wizard_fields = get_option('ensemble_wizard_fields', array());
} else {
    // Fresh install - use defaults
    $wizard_fields = $default_wizard_fields;
}

// Helper function to check if a field is enabled
if (!function_exists('es_field_enabled')) {
    function es_field_enabled($field) {
        global $wizard_fields;
        
        // If wizard_fields is not set, check from option directly
        if (!isset($wizard_fields)) {
            $configured = get_option('ensemble_wizard_fields_configured', false);
            if (!$configured) {
                // Not configured yet - all fields enabled
                return true;
            }
            $wizard_fields = get_option('ensemble_wizard_fields', array());
        }
        
        // Check if field is in the enabled list
        return is_array($wizard_fields) && in_array($field, $wizard_fields);
    }
}
?>

<div class="wrap es-wizard-wrap">
    <h1><?php printf(__('%s Wizard', 'ensemble'), $event_singular); ?></h1>
    
    <?php if (!function_exists('acf')): ?>
    <div class="notice notice-error" style="margin: 20px 0; padding: 15px; border-left: 4px solid #dc3232;">
        <h2 style="margin-top: 0;"><?php ES_Icons::icon('warning'); ?> <?php _e('Advanced Custom Fields (ACF) Required', 'ensemble'); ?></h2>
        <p style="font-size: 14px; margin: 10px 0;">
            <strong><?php _e('The Ensemble Event Management Plugin requires Advanced Custom Fields (ACF) to be installed and activated.', 'ensemble'); ?></strong>
        </p>
        <p style="font-size: 14px; margin: 10px 0;">
            <?php _e('Without ACF, event data (date, time, location, artists, etc.) cannot be saved or displayed correctly.', 'ensemble'); ?>
        </p>
        <p style="margin: 15px 0 10px 0;">
            <a href="<?php echo admin_url('plugin-install.php?s=advanced+custom+fields&tab=search&type=term'); ?>" 
               class="button button-primary button-large">
                <?php _e('Install ACF Now', 'ensemble'); ?>
            </a>
            <a href="https://wordpress.org/plugins/advanced-custom-fields/" 
               class="button button-secondary button-large" 
               target="_blank">
                <?php _e('Learn More About ACF', 'ensemble'); ?>
            </a>
        </p>
    </div>
    <?php endif; ?>
    
    <div class="es-wizard-container">
        
        <!-- Events List View -->
        <div class="es-tab-content es-events-tab-content" data-content="events">
            <!-- Compact Toolbar -->
            <div class="es-wizard-toolbar">
                <!-- Main Row: Search, Filter Toggle, Bulk Actions, View Toggle, Sort, Create -->
                <div class="es-toolbar-row es-toolbar-main-row">
                    <div class="es-filter-search">
                        <input type="text" 
                               id="es-event-search" 
                               class="es-search-input" 
                               placeholder="<?php printf(__('Search %s...', 'ensemble'), strtolower($event_plural)); ?>">
                        <span class="es-search-icon"><?php ES_Icons::icon('search'); ?></span>
                    </div>
                    
                    <!-- Filter Toggle Button -->
                    <button id="es-toggle-filters" class="button es-filter-toggle-btn" title="<?php _e('Show/Hide Filters', 'ensemble'); ?>">
                        <span class="dashicons dashicons-filter"></span>
                        <span class="es-filter-badge" style="display:none;">0</span>
                    </button>
                    
                    <span class="es-toolbar-divider"></span>
                    
                    <!-- Inline Bulk Actions -->
                    <div class="es-bulk-actions-inline" id="es-bulk-actions">
                        <span class="es-bulk-selected-count"></span>
                        <select id="es-bulk-action-select" title="<?php _e('Bulk Actions', 'ensemble'); ?>">
                            <option value=""><?php _e('Bulk Actions', 'ensemble'); ?></option>
                            <option value="publish"><?php _e('Publish', 'ensemble'); ?></option>
                            <option value="draft"><?php _e('Set to Draft', 'ensemble'); ?></option>
                            <option value="cancel"><?php _e('Cancel', 'ensemble'); ?></option>
                            <option value="postpone"><?php _e('Postpone', 'ensemble'); ?></option>
                            <option value="trash"><?php _e('Trash', 'ensemble'); ?></option>
                            <option value="delete"><?php _e('Delete', 'ensemble'); ?></option>
                        </select>
                        <button id="es-bulk-apply" class="button" title="<?php _e('Apply Bulk Action', 'ensemble'); ?>">
                            <span class="dashicons dashicons-yes"></span>
                        </button>
                    </div>
                    
                    <div class="es-toolbar-spacer"></div>
                    
                    <!-- View Toggle -->
                    <div class="es-view-toggle">
                        <button class="es-view-btn active" data-view="grid" title="<?php _e('Grid View', 'ensemble'); ?>">
                            <span class="dashicons dashicons-grid-view"></span>
                        </button>
                        <button class="es-view-btn" data-view="list" title="<?php _e('List View', 'ensemble'); ?>">
                            <span class="dashicons dashicons-list-view"></span>
                        </button>
                    </div>
                    
                    <!-- Sort Toggle -->
                    <button id="es-sort-events" class="button es-sort-btn" data-order="asc" title="<?php printf(__('Sort by %s date', 'ensemble'), strtolower($event_singular)); ?>">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <span class="es-sort-arrow">↑</span>
                    </button>
                    
                    <button id="es-create-new-btn" class="button button-primary es-create-event-btn">
                        <span class="dashicons dashicons-plus-alt2"></span>
                        <?php printf(__('Create New %s', 'ensemble'), $event_singular); ?>
                    </button>
                </div>
                
                <!-- Collapsible Filter Panel -->
                <div class="es-toolbar-row es-filter-panel" id="es-filter-panel" style="display:none;">
                    <select id="es-filter-category" class="es-filter-select" data-filter="category">
                        <option value=""><?php _e('All Categories', 'ensemble'); ?></option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo esc_attr($category['id']); ?>">
                                <?php echo esc_html($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <select id="es-filter-location" class="es-filter-select" data-filter="location">
                        <option value=""><?php printf(__('All %s', 'ensemble'), $location_plural); ?></option>
                        <?php foreach ($locations as $location): ?>
                            <option value="<?php echo esc_attr($location['id']); ?>">
                                <?php echo esc_html($location['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <select id="es-filter-date" class="es-filter-select" data-filter="date">
                        <option value=""><?php _e('All Dates', 'ensemble'); ?></option>
                        <option value="upcoming"><?php _e('Upcoming', 'ensemble'); ?></option>
                        <option value="past"><?php printf(__('Past %s', 'ensemble'), $event_plural); ?></option>
                        <option value="today"><?php _e('Today', 'ensemble'); ?></option>
                        <option value="this_week"><?php _e('This Week', 'ensemble'); ?></option>
                        <option value="this_month"><?php _e('This Month', 'ensemble'); ?></option>
                    </select>
                    
                    <select id="es-filter-status" class="es-filter-select" data-filter="status">
                        <option value=""><?php _e('All Status', 'ensemble'); ?></option>
                        <option value="publish"><?php _e('Published', 'ensemble'); ?></option>
                        <option value="draft"><?php _e('Draft', 'ensemble'); ?></option>
                        <option value="preview"><?php _e('Preview', 'ensemble'); ?></option>
                        <option value="cancelled"><?php _e('Cancelled', 'ensemble'); ?></option>
                        <option value="postponed"><?php _e('Postponed', 'ensemble'); ?></option>
                    </select>
                    
                    <button id="es-clear-filters" class="button" style="display:none;">
                        <span class="dashicons dashicons-dismiss"></span>
                        <?php _e('Clear', 'ensemble'); ?>
                    </button>
                </div>
            </div>
            
            <div id="es-events-list" class="es-events-grid">
                <div class="es-loading"><?php printf(__('Loading %s...', 'ensemble'), strtolower($event_plural)); ?></div>
            </div>
        </div>
        
        <!-- Event Wizard Form -->
        <div class="es-tab-content" data-content="wizard" style="display: none;">
            
            <div class="es-wizard-form-container">
                
                <!-- Wizard Timeline Navigation -->
                <div class="es-wizard-timeline">
                    <div class="es-timeline-step active" data-step="1">
                        <div class="es-timeline-step-number">1</div>
                        <div class="es-timeline-step-label"><?php _e('Basic Info', 'ensemble'); ?></div>
                    </div>
                    <div class="es-timeline-connector"></div>
                    <div class="es-timeline-step" data-step="2">
                        <div class="es-timeline-step-number">2</div>
                        <div class="es-timeline-step-label"><?php _e('Date & Time', 'ensemble'); ?></div>
                    </div>
                    <div class="es-timeline-connector"></div>
                    <div class="es-timeline-step" data-step="3">
                        <div class="es-timeline-step-number">3</div>
                        <div class="es-timeline-step-label"><?php printf(__('%s & %s', 'ensemble'), $artist_plural, $location_singular); ?></div>
                    </div>
                    <div class="es-timeline-connector"></div>
                    <div class="es-timeline-step" data-step="4">
                        <div class="es-timeline-step-number">4</div>
                        <div class="es-timeline-step-label"><?php _e('Tickets & Price', 'ensemble'); ?></div>
                    </div>
                    <div class="es-timeline-connector"></div>
                    <div class="es-timeline-step" data-step="5">
                        <div class="es-timeline-step-number">5</div>
                        <div class="es-timeline-step-label"><?php _e('Media', 'ensemble'); ?></div>
                    </div>
                    <div class="es-timeline-connector"></div>
                    <div class="es-timeline-step" data-step="6">
                        <div class="es-timeline-step-number">6</div>
                        <div class="es-timeline-step-label"><?php _e('Additional', 'ensemble'); ?></div>
                    </div>
                </div>
                
                <form id="es-event-form" class="es-event-form">
                    
                    <input type="hidden" id="es-event-id" name="event_id" value="">
                    <input type="hidden" id="es-current-step" name="current_step" value="1">
                    
                    <div class="es-form-section" data-step="1" style="display: block;">
                        
                        <!-- Basic Info Card -->
                        <div class="es-form-card">
                            <div class="es-form-card-header">
                                <div class="es-form-card-icon">
                                    <span class="dashicons dashicons-info-outline"></span>
                                </div>
                                <div class="es-form-card-title">
                                    <h3><?php _e('Basic Information', 'ensemble'); ?></h3>
                                    <p class="es-form-card-desc"><?php _e('Title, badge and publication status', 'ensemble'); ?></p>
                                </div>
                            </div>
                            <div class="es-form-card-body">
                                <div class="es-form-row">
                                    <label for="es-event-title">
                                        <?php printf(__('%s Title', 'ensemble'), $event_singular); ?> *
                                        <?php echo ES_Tooltip_Helper::render('', 'The title appears on the website and in all listings.', 'right'); ?>
                                    </label>
                                    <input type="text" id="es-event-title" name="title" required>
                                </div>
                                
                                <div class="es-form-row">
                                    <label for="es-event-badge"><?php printf(__('%s Badge', 'ensemble'), $event_singular); ?></label>
                                    <div class="es-badge-row" style="display: flex; gap: 12px; align-items: flex-start;">
                                        <select id="es-event-badge" name="event_badge" class="es-select" style="flex: 1; max-width: 250px;">
                                            <option value=""><?php _e('-- No Badge --', 'ensemble'); ?></option>
                                            <option value="show_category"><?php _e('Show Category', 'ensemble'); ?></option>
                                            <option value="sold_out"><?php _e('Sold Out', 'ensemble'); ?></option>
                                            <option value="few_tickets"><?php _e('Few Tickets Left', 'ensemble'); ?></option>
                                            <option value="free"><?php _e('Free Entry', 'ensemble'); ?></option>
                                            <option value="new"><?php _e('New', 'ensemble'); ?></option>
                                            <option value="premiere"><?php _e('Premiere', 'ensemble'); ?></option>
                                            <option value="last_show"><?php _e('Last Show', 'ensemble'); ?></option>
                                            <option value="special"><?php printf(__('Special %s', 'ensemble'), $event_singular); ?></option>
                                        </select>
                                        <input type="text" 
                                               id="es-event-badge-custom" 
                                               name="event_badge_custom" 
                                               placeholder="<?php _e('or custom text...', 'ensemble'); ?>"
                                               style="flex: 1; max-width: 200px;">
                                    </div>
                                    <p class="description"><?php _e('Displayed as a badge on the event card. Custom text overrides selection.', 'ensemble'); ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Status Card -->
                        <div class="es-form-card">
                            <div class="es-form-card-header">
                                <div class="es-form-card-icon">
                                    <span class="dashicons dashicons-flag"></span>
                                </div>
                                <div class="es-form-card-title">
                                    <h3><?php _e('Publication Status', 'ensemble'); ?></h3>
                                    <p class="es-form-card-desc"><?php _e('Control event visibility', 'ensemble'); ?></p>
                                </div>
                            </div>
                            <div class="es-form-card-body">
                                <div class="es-status-pills">
                                    <label class="es-status-pill es-status-draft">
                                        <input type="radio" name="event_status" value="draft">
                                        <span><span class="dashicons dashicons-edit"></span> <?php _e('Draft', 'ensemble'); ?></span>
                                    </label>
                                    <label class="es-status-pill es-status-published">
                                        <input type="radio" name="event_status" value="publish" checked>
                                        <span><span class="dashicons dashicons-yes-alt"></span> <?php _e('Published', 'ensemble'); ?></span>
                                    </label>
                                    <label class="es-status-pill es-status-cancelled">
                                        <input type="radio" name="event_status" value="cancelled">
                                        <span><span class="dashicons dashicons-dismiss"></span> <?php _e('Cancelled', 'ensemble'); ?></span>
                                    </label>
                                    <label class="es-status-pill es-status-postponed">
                                        <input type="radio" name="event_status" value="postponed">
                                        <span><span class="dashicons dashicons-clock"></span> <?php _e('Postponed', 'ensemble'); ?></span>
                                    </label>
                                    <label class="es-status-pill es-status-preview">
                                        <input type="radio" name="event_status" value="preview">
                                        <span><span class="dashicons dashicons-visibility"></span> <?php _e('Preview', 'ensemble'); ?></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (es_field_enabled('description')): ?>
                        <!-- Description Card -->
                        <div class="es-form-card">
                            <div class="es-form-card-header">
                                <div class="es-form-card-icon">
                                    <span class="dashicons dashicons-editor-paragraph"></span>
                                </div>
                                <div class="es-form-card-title">
                                    <h3><?php _e('Description', 'ensemble'); ?></h3>
                                    <p class="es-form-card-desc"><?php _e('Short description for the event overview', 'ensemble'); ?></p>
                                </div>
                            </div>
                            <div class="es-form-card-body">
                                <div class="es-wysiwyg-wrap">
                                    <?php 
                                    wp_editor('', 'es-event-description', array(
                                        'textarea_name' => 'event_description',
                                        'textarea_rows' => 8,
                                        'media_buttons' => false,
                                        'teeny' => true,
                                        'quicktags' => array('buttons' => 'strong,em,link,ul,ol,li'),
                                        'tinymce' => array(
                                            'toolbar1' => 'bold,italic,underline,strikethrough,|,bullist,numlist,|,link,unlink,|,removeformat',
                                            'toolbar2' => '',
                                            'statusbar' => false,
                                            'resize' => false,
                                        ),
                                    ));
                                    ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Categories Card -->
                        <div class="es-form-card">
                            <div class="es-form-card-header">
                                <div class="es-form-card-icon">
                                    <span class="dashicons dashicons-category"></span>
                                </div>
                                <div class="es-form-card-title">
                                    <h3><?php _e('Event Category', 'ensemble'); ?></h3>
                                    <p class="es-form-card-desc"><?php _e('Categorize your event', 'ensemble'); ?></p>
                                </div>
                            </div>
                            <div class="es-form-card-body">
                                <?php if (!empty($categories)): ?>
                                    <div class="es-pill-group" id="es-category-pills">
                                        <?php foreach ($categories as $category): ?>
                                            <label class="es-pill">
                                                <input type="checkbox" name="categories[]" value="<?php echo esc_attr($category['id']); ?>">
                                                <span><?php echo esc_html($category['name']); ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                    <a href="<?php echo admin_url('admin.php?page=ensemble-taxonomies&tab=categories'); ?>" 
                                       class="es-pill-add"
                                       target="_blank"
                                       title="<?php _e('Manage Categories', 'ensemble'); ?>">
                                        <span class="dashicons dashicons-plus-alt2"></span>
                                        <?php _e('Manage Categories', 'ensemble'); ?>
                                    </a>
                                <?php else: ?>
                                    <p class="es-empty-message">
                                        <?php _e('No categories available.', 'ensemble'); ?> 
                                        <a href="<?php echo admin_url('admin.php?page=ensemble-taxonomies&tab=categories'); ?>" target="_blank">
                                            <?php _e('Create categories', 'ensemble'); ?>
                                        </a>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="es-form-section" data-step="2" style="display: none;">
                        
                        <!-- Event Type Card -->
                        <div class="es-form-card">
                            <div class="es-form-card-header">
                                <div class="es-form-card-icon">
                                    <span class="dashicons dashicons-calendar-alt"></span>
                                </div>
                                <div class="es-form-card-title">
                                    <h3><?php printf(__('%s Type', 'ensemble'), $event_singular); ?></h3>
                                    <p class="es-form-card-desc"><?php printf(__('Single %s, multi-day or permanent exhibition', 'ensemble'), strtolower($event_singular)); ?></p>
                                </div>
                            </div>
                            <div class="es-form-card-body">
                                <div class="es-duration-type-options">
                                    <label class="es-duration-option es-duration-option-single">
                                        <input type="radio" name="duration_type" value="single" checked>
                                        <span class="es-duration-icon">
                                            <span class="dashicons dashicons-calendar"></span>
                                        </span>
                                        <span class="es-duration-label"><?php _e('Single', 'ensemble'); ?></span>
                                    </label>
                                    <label class="es-duration-option es-duration-option-multi">
                                        <input type="radio" name="duration_type" value="multi_day">
                                        <span class="es-duration-icon">
                                            <span class="dashicons dashicons-calendar-alt"></span>
                                        </span>
                                        <span class="es-duration-label"><?php _e('Multi-Day', 'ensemble'); ?></span>
                                    </label>
                                    <label class="es-duration-option es-duration-option-permanent">
                                        <input type="radio" name="duration_type" value="permanent">
                                        <span class="es-duration-icon">
                                            <span class="dashicons dashicons-admin-home"></span>
                                        </span>
                                        <span class="es-duration-label"><?php _e('Permanent', 'ensemble'); ?></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Date & Time Card -->
                        <div class="es-form-card">
                            <div class="es-form-card-header">
                                <div class="es-form-card-icon">
                                    <span class="dashicons dashicons-clock"></span>
                                </div>
                                <div class="es-form-card-title">
                                    <h3><?php _e('Date & Time', 'ensemble'); ?></h3>
                                    <p class="es-form-card-desc"><?php printf(__('When does the %s take place?', 'ensemble'), strtolower($event_singular)); ?></p>
                                </div>
                            </div>
                            <div class="es-form-card-body">
                                <div class="es-form-row-group">
                            <!-- Single Event Date -->
                            <div class="es-form-row es-date-single-row">
                                <label for="es-event-date"><?php _e('Date', 'ensemble'); ?> *</label>
                                <input type="date" id="es-event-date" name="event_date" required>
                            </div>
                            
                            <!-- Multi-Day Date Range -->
                            <div class="es-form-row es-date-range-row" style="display: none;">
                                <label><?php _e('Date Range', 'ensemble'); ?> *</label>
                                <div class="es-date-range-inputs">
                                    <input type="date" id="es-event-date-start" name="event_date_start" placeholder="<?php _e('Start', 'ensemble'); ?>">
                                    <span class="es-date-range-separator">–</span>
                                    <input type="date" id="es-event-date-end" name="event_date_end" placeholder="<?php _e('End', 'ensemble'); ?>">
                                </div>
                            </div>
                            
                            <!-- Permanent Start Date -->
                            <div class="es-form-row es-date-permanent-row" style="display: none;">
                                <label for="es-event-date-permanent"><?php _e('Since / Opening Date', 'ensemble'); ?></label>
                                <input type="date" id="es-event-date-permanent" name="event_date_permanent">
                            </div>
                            
                            <?php if (es_field_enabled('time')): ?>
                            <div class="es-form-row es-time-row">
                                <label for="es-event-time"><?php _e('Start Time', 'ensemble'); ?></label>
                                <input type="time" id="es-event-time" name="event_time">
                            </div>
                            <?php endif; ?>
                            
                            <?php if (es_field_enabled('time_end')): ?>
                            <div class="es-form-row es-time-row">
                                <label for="es-event-time-end"><?php _e('End Time', 'ensemble'); ?></label>
                                <input type="time" id="es-event-time-end" name="event_time_end">
                            </div>
                            <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Sub-Events Card (for multi-day/permanent) -->
                        <div class="es-form-card es-sub-events-section" style="display: none;">
                            <div class="es-form-card-header">
                                <div class="es-form-card-icon">
                                    <span class="dashicons dashicons-networking"></span>
                                </div>
                                <div class="es-form-card-title">
                                    <h3><?php _e('Sub-Events', 'ensemble'); ?></h3>
                                    <p class="es-form-card-desc"><?php _e('Manage linked sub-events for festivals or exhibitions', 'ensemble'); ?></p>
                                </div>
                            </div>
                            <div class="es-form-card-body">
                                <div class="es-form-row">
                                    <label class="es-toggle">
                                        <input type="checkbox" id="es-has-children" name="has_children" value="1">
                                        <span class="es-toggle-track"></span>
                                        <span class="es-toggle-label">
                                            <?php _e('Has Sub-Events', 'ensemble'); ?>
                                            <?php echo ES_Tooltip_Helper::render('sub_events', '', 'right'); ?>
                                        </span>
                                    </label>
                                </div>
                            
                            <!-- Child Events List (populated via JS when editing existing parent) -->
                            <div id="es-child-events-list" class="es-child-events-list" style="display: none;">
                                <h4>
                                    <?php _e('Linked Sub-Events', 'ensemble'); ?>
                                    <span id="es-child-events-count" class="es-count-badge">0</span>
                                </h4>
                                <div id="es-child-events-items" class="es-child-events-items">
                                    <!-- Populated via JavaScript -->
                                </div>
                                <div class="es-child-event-actions-row">
                                    <a href="#" id="es-add-child-event" class="es-add-child-link">
                                        <span class="dashicons dashicons-plus-alt2"></span>
                                        <?php _e('Create Sub-Event', 'ensemble'); ?>
                                    </a>
                                    <a href="#" id="es-link-existing-event" class="es-link-event-link">
                                        <span class="dashicons dashicons-admin-links"></span>
                                        <?php _e('Link Existing Event', 'ensemble'); ?>
                                    </a>
                                </div>
                                
                                <!-- Link Existing Event Modal -->
                                <div id="es-link-event-modal" class="es-modal" style="display: none;">
                                    <div class="es-modal-content es-modal-small">
                                        <div class="es-modal-header">
                                            <h3><?php _e('Link Existing Event', 'ensemble'); ?></h3>
                                            <button type="button" class="es-modal-close">&times;</button>
                                        </div>
                                        <div class="es-modal-body">
                                            <div class="es-form-row">
                                                <label for="es-link-event-search"><?php _e('Filter Events', 'ensemble'); ?></label>
                                                <input type="text" id="es-link-event-search" placeholder="<?php esc_attr_e('Filter by name or date...', 'ensemble'); ?>">
                                            </div>
                                            <div id="es-link-event-results" class="es-search-results">
                                                <!-- Populated via JavaScript -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div id="es-children-info" class="es-info-box" style="display: none;">
                                <span class="dashicons dashicons-info"></span>
                                <p><?php _e('After saving, you can create sub-events and link them to this parent.', 'ensemble'); ?></p>
                            </div>
                            </div>
                        </div>
                        
                        <!-- Parent Event Card (for child events) -->
                        <div class="es-form-card es-parent-event-section" style="display: none;">
                            <div class="es-form-card-header">
                                <div class="es-form-card-icon">
                                    <span class="dashicons dashicons-admin-links"></span>
                                </div>
                                <div class="es-form-card-title">
                                    <h3><?php _e('Parent Event', 'ensemble'); ?></h3>
                                    <p class="es-form-card-desc"><?php _e('Link as sub-event of a festival or exhibition', 'ensemble'); ?></p>
                                </div>
                            </div>
                            <div class="es-form-card-body">
                                <div class="es-form-row">
                                    <label for="es-parent-event"><?php _e('Parent Event', 'ensemble'); ?></label>
                                    <select id="es-parent-event" name="parent_event_id">
                                        <option value=""><?php _e('— None (Standalone Event) —', 'ensemble'); ?></option>
                                        <?php
                                        // Get all parent events (multi_day or permanent with has_children)
                                        $parent_events = get_posts(array(
                                            'post_type'      => ensemble_get_post_type(),
                                            'posts_per_page' => -1,
                                            'meta_query'     => array(
                                                'relation' => 'AND',
                                                array(
                                                    'key'     => '_es_duration_type',
                                                    'value'   => array('multi_day', 'permanent'),
                                                    'compare' => 'IN',
                                                ),
                                                array(
                                                    'key'     => '_es_has_children',
                                                    'value'   => '1',
                                                    'compare' => '=',
                                                ),
                                            ),
                                            'orderby' => 'title',
                                            'order'   => 'ASC',
                                        ));
                                        
                                        foreach ($parent_events as $parent) :
                                            $duration_type = get_post_meta($parent->ID, '_es_duration_type', true);
                                            $type_label = ($duration_type === 'permanent') ? __('Permanent', 'ensemble') : __('Multi-Day', 'ensemble');
                                        ?>
                                            <option value="<?php echo esc_attr($parent->ID); ?>">
                                                <?php echo esc_html($parent->post_title); ?> (<?php echo esc_html($type_label); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Recurring Events Card -->
                        <?php 
                        $recurring_available = class_exists('ES_Recurring_Engine') && ES_Recurring_Engine::is_available();
                        ?>
                        <div class="es-form-card es-recurring-section <?php echo !$recurring_available ? 'es-pro-locked-section' : ''; ?>">
                            <div class="es-form-card-header">
                                <div class="es-form-card-icon">
                                    <span class="dashicons dashicons-update"></span>
                                </div>
                                <div class="es-form-card-title">
                                    <h3><?php _e('Recurring Event', 'ensemble'); ?></h3>
                                    <p class="es-form-card-desc"><?php _e('Create multiple event instances based on a pattern', 'ensemble'); ?></p>
                                </div>
                            </div>
                            <div class="es-form-card-body">
                                <div class="es-form-row">
                                    <label class="es-toggle">
                                        <input type="checkbox" id="es-recurring-toggle" name="is_recurring" value="1" <?php echo !$recurring_available ? 'disabled' : ''; ?>>
                                        <span class="es-toggle-track"></span>
                                        <span class="es-toggle-label">
                                            <?php _e('Enable Recurring', 'ensemble'); ?>
                                            <?php if (!$recurring_available): ?>
                                                <span class="es-pro-badge"><span class="dashicons dashicons-star-filled"></span> PRO</span>
                                            <?php endif; ?>
                                        </span>
                                    </label>
                                    <?php if (!$recurring_available): ?>
                                    <p class="es-field-help" style="color: #f59e0b; margin-top: 8px;">
                                        <?php _e('Recurring events require Pro.', 'ensemble'); ?>
                                        <a href="<?php echo admin_url('admin.php?page=ensemble-settings&tab=license'); ?>"><?php _e('Upgrade', 'ensemble'); ?></a>
                                    </p>
                                    <?php endif; ?>
                                </label>
                            </div>
                            
                            <?php if ($recurring_available): ?>
                            <div id="es-recurring-options" class="es-recurring-options" style="display: none;">
                                
                                <div class="es-form-row">
                                    <label for="es-recurring-pattern"><?php _e('Repeat Pattern', 'ensemble'); ?></label>
                                    <select id="es-recurring-pattern" name="recurring_pattern">
                                        <option value="daily"><?php _e('Daily', 'ensemble'); ?></option>
                                        <option value="weekly" selected><?php _e('Weekly', 'ensemble'); ?></option>
                                        <option value="monthly"><?php _e('Monthly', 'ensemble'); ?></option>
                                        <option value="custom"><?php _e('Custom Dates', 'ensemble'); ?></option>
                                    </select>
                                </div>
                                
                                <!-- Daily Pattern Options -->
                                <div id="es-recurring-daily" class="es-recurring-pattern-options" style="display: none;">
                                    <div class="es-form-row">
                                        <label for="es-recurring-daily-interval"><?php _e('Repeat every', 'ensemble'); ?></label>
                                        <div class="es-input-group">
                                            <input type="number" id="es-recurring-daily-interval" name="recurring_interval" value="1" min="1" max="365">
                                            <span><?php _e('day(s)', 'ensemble'); ?></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Weekly Pattern Options -->
                                <div id="es-recurring-weekly" class="es-recurring-pattern-options">
                                    <div class="es-form-row">
                                        <label for="es-recurring-weekly-interval"><?php _e('Repeat every', 'ensemble'); ?></label>
                                        <div class="es-input-group">
                                            <input type="number" id="es-recurring-weekly-interval" name="recurring_interval" value="1" min="1" max="52">
                                            <span><?php _e('week(s)', 'ensemble'); ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="es-form-row">
                                        <label><?php _e('On these days', 'ensemble'); ?></label>
                                        <div class="es-weekday-selector">
                                            <label><input type="checkbox" name="recurring_weekdays[]" value="1"> <?php _e('Mon', 'ensemble'); ?></label>
                                            <label><input type="checkbox" name="recurring_weekdays[]" value="2"> <?php _e('Tue', 'ensemble'); ?></label>
                                            <label><input type="checkbox" name="recurring_weekdays[]" value="3"> <?php _e('Wed', 'ensemble'); ?></label>
                                            <label><input type="checkbox" name="recurring_weekdays[]" value="4"> <?php _e('Thu', 'ensemble'); ?></label>
                                            <label><input type="checkbox" name="recurring_weekdays[]" value="5"> <?php _e('Fri', 'ensemble'); ?></label>
                                            <label><input type="checkbox" name="recurring_weekdays[]" value="6"> <?php _e('Sat', 'ensemble'); ?></label>
                                            <label><input type="checkbox" name="recurring_weekdays[]" value="7"> <?php _e('Sun', 'ensemble'); ?></label>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Monthly Pattern Options -->
                                <div id="es-recurring-monthly" class="es-recurring-pattern-options" style="display: none;">
                                    <div class="es-form-row">
                                        <label for="es-recurring-monthly-interval"><?php _e('Repeat every', 'ensemble'); ?></label>
                                        <div class="es-input-group">
                                            <input type="number" id="es-recurring-monthly-interval" name="recurring_interval" value="1" min="1" max="12">
                                            <span><?php _e('month(s)', 'ensemble'); ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="es-form-row">
                                        <label><?php _e('On the same day of month', 'ensemble'); ?></label>
                                        <div class="es-info-box">
                                            <p><?php _e('The event will repeat on the same day of each month as the start date.', 'ensemble'); ?></p>
                                            <p><small><?php _e('Example: If the event starts on November 14th, it will repeat on the 14th of each month.', 'ensemble'); ?></small></p>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Custom Pattern Options -->
                                <div id="es-recurring-custom" class="es-recurring-pattern-options" style="display: none;">
                                    <div class="es-form-row">
                                        <label for="es-recurring-custom-dates"><?php _e('Select Dates', 'ensemble'); ?></label>
                                        <textarea id="es-recurring-custom-dates" name="recurring_custom_dates" rows="4" placeholder="<?php _e('Enter dates (one per line, YYYY-MM-DD format)', 'ensemble'); ?>"></textarea>
                                        <small class="es-field-help"><?php _e('One date per line in YYYY-MM-DD format', 'ensemble'); ?></small>
                                    </div>
                                </div>
                                
                                <!-- End Options - moved here for better UX flow -->
                                <div class="es-form-row">
                                    <label><?php _e('Ends', 'ensemble'); ?></label>
                                    <div class="es-recurring-end-options">
                                        <label class="es-radio-label">
                                            <input type="radio" name="recurring_end_type" value="never" checked>
                                            <?php _e('Never', 'ensemble'); ?>
                                        </label>
                                        <label class="es-radio-label">
                                            <input type="radio" name="recurring_end_type" value="date">
                                            <?php _e('On date', 'ensemble'); ?>
                                            <input type="date" id="es-recurring-end-date" name="recurring_end_date" disabled>
                                        </label>
                                        <label class="es-radio-label">
                                            <input type="radio" name="recurring_end_type" value="count">
                                            <?php _e('After', 'ensemble'); ?>
                                            <input type="number" id="es-recurring-end-count" name="recurring_end_count" value="10" min="1" max="100" disabled>
                                            <?php _e('occurrences', 'ensemble'); ?>
                                        </label>
                                    </div>
                                </div>
                                
                                <!-- Preview Button -->
                                <div class="es-form-row">
                                    <button type="button" id="es-recurring-preview-btn" class="button">
                                        <?php _e('Preview Dates', 'ensemble'); ?>
                                    </button>
                                </div>
                                
                                <!-- Preview List -->
                                <div id="es-recurring-preview" class="es-recurring-preview" style="display: none;">
                                    <h4><?php _e('Upcoming Event Dates', 'ensemble'); ?> <span id="es-recurring-preview-count"></span></h4>
                                    <div id="es-recurring-preview-list" class="es-recurring-preview-list"></div>
                                </div>
                                
                            </div>
                            <?php endif; // End recurring_available ?>
                            </div>
                        </div>
                        
                    </div>
                    
                    <div class="es-form-section" data-step="3" style="display: none;">
                        
                        <?php if (es_field_enabled('artist')): ?>
                        <!-- Artists Card -->
                        <div class="es-form-card">
                            <div class="es-form-card-header">
                                <div class="es-form-card-icon">
                                    <span class="dashicons dashicons-groups"></span>
                                </div>
                                <div class="es-form-card-title">
                                    <h3><?php _e('Artists / Performers', 'ensemble'); ?></h3>
                                    <p class="es-form-card-desc"><?php _e('Select artists and set their performance time', 'ensemble'); ?></p>
                                </div>
                            </div>
                            <div class="es-form-card-body">
                                <div class="es-artist-pills" id="es-artist-selection">
                                <?php foreach ($artists as $artist): 
                                    $genre_ids_json = !empty($artist['genre_ids']) ? json_encode($artist['genre_ids']) : '[]';
                                ?>
                                    <div class="es-artist-pill" 
                                         data-artist-id="<?php echo esc_attr($artist['id']); ?>"
                                         data-artist-genres="<?php echo esc_attr($genre_ids_json); ?>">
                                        <input type="checkbox" 
                                               name="event_artist[]" 
                                               value="<?php echo esc_attr($artist['id']); ?>"
                                               class="es-artist-checkbox">
                                        <span class="es-artist-pill-label">
                                            <?php if (!empty($artist['image'])): ?>
                                                <img src="<?php echo esc_url($artist['image']); ?>" alt="" class="es-artist-pill-image">
                                            <?php endif; ?>
                                            <span class="es-artist-pill-name"><?php echo esc_html($artist['name']); ?></span>
                                            <span class="es-artist-pill-indicators">
                                                <span class="es-indicator es-indicator-time" title="<?php _e('Time set', 'ensemble'); ?>">T</span>
                                                <span class="es-indicator es-indicator-venue" title="<?php _e('Room set', 'ensemble'); ?>">R</span>
                                            </span>
                                        </span>
                                        <button type="button" class="es-artist-pill-edit" title="<?php _e('Edit time & room', 'ensemble'); ?>">
                                            <span class="dashicons dashicons-edit-page"></span>
                                        </button>
                                        <!-- Hidden fields for time/venue/session title -->
                                        <input type="hidden" class="es-artist-time" name="artist_time[<?php echo esc_attr($artist['id']); ?>]" value="">
                                        <input type="hidden" class="es-artist-venue" name="artist_venue[<?php echo esc_attr($artist['id']); ?>]" value="">
                                        <input type="hidden" class="es-artist-session-title" name="artist_session_title[<?php echo esc_attr($artist['id']); ?>]" value="">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <!-- Artist Popover (hidden, used as template) -->
                            <div id="es-artist-popover" class="es-artist-popover" style="display: none;">
                                <div class="es-popover-header">
                                    <span class="es-popover-title"></span>
                                    <button type="button" class="es-popover-close">&times;</button>
                                </div>
                                <div class="es-popover-body">
                                    <div class="es-popover-field">
                                        <label><?php _e('Session Title', 'ensemble'); ?> <small style="color: var(--es-text-muted, #888);">(<?php _e('optional', 'ensemble'); ?>)</small></label>
                                        <input type="text" id="es-popover-session-title" class="es-popover-session-title" placeholder="<?php _e('e.g. Keynote: The Future of AI', 'ensemble'); ?>" style="width: 100%;">
                                        <p class="es-field-help" style="margin-top: 5px; font-size: 11px;"><?php _e('Custom title shown in timeline instead of artist name', 'ensemble'); ?></p>
                                    </div>
                                    <div class="es-popover-field">
                                        <label><?php _e('Performance Time', 'ensemble'); ?></label>
                                        <input type="time" id="es-popover-time" class="es-popover-time">
                                    </div>
                                    <div class="es-popover-field es-popover-venue-field" style="display: none;">
                                        <label><?php _e('Room/Stage', 'ensemble'); ?></label>
                                        <select id="es-popover-venue" class="es-popover-venue">
                                            <option value=""><?php _e('-- Select Room --', 'ensemble'); ?></option>
                                        </select>
                                    </div>
                                </div>
                                <div class="es-popover-footer">
                                    <button type="button" class="es-popover-remove button-link-delete"><?php _e('Remove', 'ensemble'); ?></button>
                                    <button type="button" class="es-popover-ok button button-primary"><?php _e('OK', 'ensemble'); ?></button>
                                </div>
                            </div>
                            
                            <a href="#" 
                               class="es-pill-add" 
                               data-es-quick-add="artist"
                               title="<?php printf(__('Add New %s', 'ensemble'), $artist_singular); ?>">
                                <span class="dashicons dashicons-plus-alt2"></span>
                                <?php printf(__('Add New %s', 'ensemble'), $artist_singular); ?>
                            </a>
                            </div>
                        </div>
                        
                        <!-- Timeline / Breaks Card (for conference layout) -->
                        <div class="es-form-card es-timeline-breaks">
                            <div class="es-form-card-header">
                                <div class="es-form-card-icon" style="background: #B87333;">
                                    <span class="dashicons dashicons-coffee"></span>
                                </div>
                                <div class="es-form-card-title">
                                    <h3><?php _e('Timeline / Breaks', 'ensemble'); ?> <span class="es-badge-new" style="font-size: 10px; padding: 2px 6px; background: #B87333; color: white; border-radius: 10px; margin-left: 8px;">Conference</span></h3>
                                    <p class="es-form-card-desc"><?php _e('Add breaks, lunch, networking for a complete timeline', 'ensemble'); ?></p>
                                </div>
                            </div>
                            <div class="es-form-card-body">
                                <!-- Hidden field for breaks data -->
                                <input type="hidden" id="es-agenda-breaks" name="agenda_breaks" value="">
                                
                                <!-- Breaks List -->
                                <div id="es-breaks-list" class="es-breaks-list" style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 15px;">
                                    <!-- Breaks rendered by JS -->
                                </div>
                                
                                <!-- Add Break Button -->
                                <button type="button" id="es-add-break" class="button" style="display: inline-flex; align-items: center; gap: 6px;">
                                    <span class="dashicons dashicons-coffee"></span>
                                    <?php _e('Add Break / Pause', 'ensemble'); ?>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Break Editor Popup (outside of card) -->
                        <div id="es-break-editor" class="es-break-editor" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: var(--es-surface, #1e1e1e); border: 1px solid var(--es-border, #333); border-radius: 12px; padding: 24px; z-index: 100001; width: 400px; max-width: 90vw; box-shadow: 0 20px 60px rgba(0,0,0,0.5);">
                            <div class="es-break-editor-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                                <h4 style="margin: 0; color: var(--es-text-primary, #fff);"><?php _e('Add Break', 'ensemble'); ?></h4>
                                <button type="button" class="es-break-editor-close" style="background: none; border: none; font-size: 24px; color: var(--es-text-muted, #888); cursor: pointer;">&times;</button>
                            </div>
                            <div class="es-break-editor-body">
                                <div class="es-form-row" style="margin-bottom: 15px;">
                                    <label for="es-break-type" style="display: block; margin-bottom: 5px; color: var(--es-text-primary, #fff);"><?php _e('Type', 'ensemble'); ?></label>
                                    <select id="es-break-type" style="width: 100%; padding: 8px; border-radius: 6px; border: 1px solid var(--es-border, #333); background: var(--es-surface-secondary, #2a2a2a); color: var(--es-text-primary, #fff);">
                                        <option value="coffee"><?php _e('☕ Coffee Break', 'ensemble'); ?></option>
                                        <option value="lunch"><?php _e('🍽️ Lunch', 'ensemble'); ?></option>
                                        <option value="networking"><?php _e('🤝 Networking', 'ensemble'); ?></option>
                                        <option value="registration"><?php _e('📋 Registration', 'ensemble'); ?></option>
                                        <option value="workshop"><?php _e('🛠️ Workshop', 'ensemble'); ?></option>
                                        <option value="panel"><?php _e('👥 Panel Discussion', 'ensemble'); ?></option>
                                        <option value="pause"><?php _e('⏸️ Pause', 'ensemble'); ?></option>
                                    </select>
                                </div>
                                <div class="es-form-row" style="margin-bottom: 15px;">
                                    <label for="es-break-title" style="display: block; margin-bottom: 5px; color: var(--es-text-primary, #fff);"><?php _e('Title', 'ensemble'); ?></label>
                                    <input type="text" id="es-break-title" placeholder="<?php _e('e.g. Lunch Break', 'ensemble'); ?>" style="width: 100%; padding: 8px; border-radius: 6px; border: 1px solid var(--es-border, #333); background: var(--es-surface-secondary, #2a2a2a); color: var(--es-text-primary, #fff);">
                                </div>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                                    <div class="es-form-row">
                                        <label for="es-break-time" style="display: block; margin-bottom: 5px; color: var(--es-text-primary, #fff);"><?php _e('Time', 'ensemble'); ?></label>
                                        <input type="time" id="es-break-time" style="width: 100%; padding: 8px; border-radius: 6px; border: 1px solid var(--es-border, #333); background: var(--es-surface-secondary, #2a2a2a); color: var(--es-text-primary, #fff);">
                                    </div>
                                    <div class="es-form-row">
                                        <label for="es-break-duration" style="display: block; margin-bottom: 5px; color: var(--es-text-primary, #fff);"><?php _e('Duration (min)', 'ensemble'); ?></label>
                                        <input type="number" id="es-break-duration" value="30" min="5" step="5" style="width: 100%; padding: 8px; border-radius: 6px; border: 1px solid var(--es-border, #333); background: var(--es-surface-secondary, #2a2a2a); color: var(--es-text-primary, #fff);">
                                    </div>
                                </div>
                            </div>
                            <div class="es-break-editor-footer" style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                                <button type="button" class="es-break-editor-cancel button"><?php _e('Cancel', 'ensemble'); ?></button>
                                <button type="button" class="es-break-editor-save button button-primary"><?php _e('Add Break', 'ensemble'); ?></button>
                            </div>
                        </div>
                        <div id="es-break-editor-overlay" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); z-index: 100000;"></div>
                        <?php endif; ?>
                        
                        <?php if (es_field_enabled('location')): ?>
                        <!-- Location Card -->
                        <div class="es-form-card">
                            <div class="es-form-card-header">
                                <div class="es-form-card-icon">
                                    <span class="dashicons dashicons-location"></span>
                                </div>
                                <div class="es-form-card-title">
                                    <h3><?php echo esc_html($location_singular); ?></h3>
                                    <p class="es-form-card-desc"><?php printf(__('Where does the %s take place?', 'ensemble'), strtolower($event_singular)); ?></p>
                                </div>
                            </div>
                            <div class="es-form-card-body">
                                <div class="es-pill-group" id="es-location-pills">
                                    <?php foreach ($locations as $location): 
                                        $is_multivenue = !empty($location['is_multivenue']);
                                        $venues_json = $is_multivenue && !empty($location['venues']) ? esc_attr(json_encode($location['venues'])) : '';
                                    ?>
                                        <label class="es-pill" data-multivenue="<?php echo $is_multivenue ? '1' : '0'; ?>" data-venues="<?php echo $venues_json; ?>">
                                            <input type="radio" name="event_location" value="<?php echo esc_attr($location['id']); ?>">
                                            <span><?php echo esc_html($location['name']); ?><?php if ($is_multivenue): ?> <small class="es-multivenue-badge">🏛️</small><?php endif; ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <a href="#" 
                                   class="es-pill-add" 
                                   data-es-quick-add="location"
                                   title="<?php printf(__('Add New %s', 'ensemble'), $location_singular); ?>">
                                    <span class="dashicons dashicons-plus-alt2"></span>
                                    <?php printf(__('Add New %s', 'ensemble'), $location_singular); ?>
                                </a>
                                
                                <!-- Venue Selection (nur sichtbar bei Multivenue) -->
                                <div class="es-form-row es-venue-selection" id="es-venue-selection" style="display: none; margin-top: 20px;">
                                    <label><?php _e('Rooms / Stages', 'ensemble'); ?></label>
                                    
                                    <div class="es-venue-auto-hint" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(16, 185, 129, 0.05) 100%); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 8px; padding: 12px 15px; margin-bottom: 15px;">
                                        <p style="margin: 0; display: flex; align-items: center; gap: 8px; color: var(--es-text-primary, #fff);">
                                            <span class="dashicons dashicons-yes-alt" style="color: #10b981;"></span>
                                            <strong><?php _e('Tip:', 'ensemble'); ?></strong>
                                            <?php _e('Assign rooms to artists above (click Edit icon) – genres will be adopted automatically!', 'ensemble'); ?>
                                        </p>
                                    </div>
                                    
                                    <div class="es-venue-toggles" id="es-venue-toggles">
                                        <!-- Venues werden hier per JS eingefügt -->
                                    </div>
                                    
                                    <div class="es-venue-actions" style="margin-top: 12px; display: flex; gap: 10px; align-items: center;">
                                        <button type="button" id="es-sync-venue-genres" class="button button-small" title="<?php _e('Automatically enables the genres of assigned artists for each room', 'ensemble'); ?>">
                                            <span class="dashicons dashicons-update" style="margin-top: 3px;"></span>
                                            <?php _e('Adopt genres from artists', 'ensemble'); ?>
                                        </button>
                                        <span class="es-field-help" style="margin: 0;"><?php _e('or select genres manually', 'ensemble'); ?></span>
                                    </div>
                                    
                                    <input type="hidden" id="es-event-venue" name="event_venue" value="">
                                    <input type="hidden" id="es-venue-config" name="venue_config" value="{}">
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Genres Card -->
                        <div class="es-form-card">
                            <div class="es-form-card-header">
                                <div class="es-form-card-icon">
                                    <span class="dashicons dashicons-tag"></span>
                                </div>
                                <div class="es-form-card-title">
                                    <h3><?php _e('Genres', 'ensemble'); ?></h3>
                                    <p class="es-form-card-desc"><?php printf(__('Tag the %s with music or event genres', 'ensemble'), strtolower($event_singular)); ?></p>
                                </div>
                            </div>
                            <div class="es-form-card-body es-global-genres" id="es-global-genres">
                                <?php if (!empty($genres)): ?>
                                    <div class="es-pill-group" id="es-genre-pills">
                                        <?php foreach ($genres as $genre): ?>
                                            <label class="es-pill">
                                                <input type="checkbox" name="event_genres[]" value="<?php echo esc_attr($genre['id']); ?>">
                                                <span><?php echo esc_html($genre['name']); ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                    <a href="#" 
                                       class="es-pill-add" 
                                       data-es-quick-add="genre"
                                       title="<?php _e('Add New Genre', 'ensemble'); ?>">
                                        <span class="dashicons dashicons-plus-alt2"></span>
                                        <?php _e('Add New Genre', 'ensemble'); ?>
                                    </a>
                                    
                                    <!-- Show Artist Genres Option -->
                                    <div class="es-form-row es-artist-genres-option" id="es-artist-genres-option" style="margin-top: 15px;">
                                        <label class="es-toggle">
                                            <input type="checkbox" id="es-show-artist-genres" name="show_artist_genres" value="1">
                                            <span class="es-toggle-track"></span>
                                            <span class="es-toggle-label">
                                                <?php _e('Auch Genres der Artists anzeigen', 'ensemble'); ?>
                                            </span>
                                        </label>
                                    </div>
                                <?php else: ?>
                                    <p class="es-empty-message">
                                        <?php _e('No genres available.', 'ensemble'); ?> 
                                        <a href="<?php echo admin_url('admin.php?page=ensemble-taxonomies&tab=genres'); ?>" target="_blank">
                                            <?php _e('Create genres', 'ensemble'); ?>
                                        </a>
                                    </p>
                                    <a href="#" 
                                       class="es-pill-add" 
                                       data-es-quick-add="genre"
                                       title="<?php _e('Add New Genre', 'ensemble'); ?>">
                                        <span class="dashicons dashicons-plus-alt2"></span>
                                        <?php _e('Add New Genre', 'ensemble'); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- STEP 4: Tickets & Price -->
                    <div class="es-form-section" data-step="4" style="display: none;">
                        
                        <?php 
                        // Check if Tickets Pro is active - if so, hide the basic tickets card
                        // as Tickets Pro provides its own card via the hook
                        // DEBUG: NEW VERSION 2025-01-16 WITH EXTERNAL TICKETS CORE
                        // IMPORTANT: Use is_addon_active(), not class_exists()!
                        $tickets_pro_active = class_exists('ES_Addon_Manager') && ES_Addon_Manager::is_addon_active('tickets-pro');
                        ?>
                        <!-- DEBUG WIZARD V2: tickets_pro_active = <?php echo $tickets_pro_active ? 'TRUE' : 'FALSE'; ?> -->
                        <!-- DEBUG WIZARD V2: ticket_url enabled = <?php echo es_field_enabled('ticket_url') ? 'TRUE' : 'FALSE'; ?> -->
                        <?php
                        // Only show basic Tickets Card if Tickets Pro is NOT active
                        if (!$tickets_pro_active): 
                        ?>
                        <!-- External Tickets Card (Core - hidden when Tickets Pro is active) -->
                        <div class="es-form-card">
                            <div class="es-form-card-header">
                                <div class="es-form-card-icon">
                                    <?php ES_Icons::icon('ticket'); ?>
                                </div>
                                <div class="es-form-card-title">
                                    <h3><?php _e('External Tickets', 'ensemble'); ?></h3>
                                    <p class="es-form-card-desc"><?php _e('Link to external ticket provider', 'ensemble'); ?></p>
                                </div>
                            </div>
                            <div class="es-form-card-body">
                                <!-- Simple External Ticket Fields (Core) -->
                                <?php if (es_field_enabled('ticket_url')): ?>
                                <div class="es-form-row">
                                    <label for="es-event-ticket-url"><?php _e('Ticket URL', 'ensemble'); ?></label>
                                    <input type="url" 
                                           id="es-event-ticket-url" 
                                           name="event_ticket_url" 
                                           placeholder="<?php _e('https://www.eventbrite.com/e/...', 'ensemble'); ?>">
                                    <p class="description">
                                        <?php _e('Link to ticket sales on external platforms (Eventbrite, RA, Eventim, etc.)', 'ensemble'); ?>
                                    </p>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (es_field_enabled('button_text')): ?>
                                <div class="es-form-row">
                                    <label for="es-event-button-text"><?php _e('Button Text', 'ensemble'); ?></label>
                                    <input type="text" 
                                           id="es-event-button-text" 
                                           name="event_button_text" 
                                           placeholder="<?php _e('Buy Tickets', 'ensemble'); ?>">
                                    <p class="description"><?php _e('Text displayed on the ticket button', 'ensemble'); ?></p>
                                </div>
                                <?php endif; ?>
                                
                                <p class="description" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid var(--es-border, #e2e8f0); color: var(--es-text-muted, #888);">
                                    <span class="dashicons dashicons-info-outline" style="font-size: 14px; width: 14px; height: 14px; vertical-align: middle;"></span>
                                    <?php _e('For advanced ticket management with multiple categories, payment processing, and reservations, activate the Tickets Pro add-on.', 'ensemble'); ?>
                                </p>
                            </div>
                        </div>
                        <?php endif; // End !$tickets_pro_active ?>
                        
                        <!-- Pricing Card -->
                        <?php if (es_field_enabled('price')): ?>
                        <div class="es-form-card">
                            <div class="es-form-card-header">
                                <div class="es-form-card-icon">
                                    <span class="dashicons dashicons-money-alt"></span>
                                </div>
                                <div class="es-form-card-title">
                                    <h3><?php _e('Pricing', 'ensemble'); ?></h3>
                                    <p class="es-form-card-desc"><?php _e('Set ticket prices and notes', 'ensemble'); ?></p>
                                </div>
                            </div>
                            <div class="es-form-card-body">
                                <div class="es-form-row">
                                    <label for="es-event-price"><?php _e('Price', 'ensemble'); ?></label>
                                    <input type="text" id="es-event-price" name="event_price" placeholder="<?php _e('e.g., â‚¬10 or Free', 'ensemble'); ?>">
                                </div>
                                
                                <div class="es-form-row">
                                    <label for="es-event-price-note"><?php _e('Price Note (Fine Print)', 'ensemble'); ?></label>
                                    <input type="text" id="es-event-price-note" name="event_price_note" placeholder="<?php _e('e.g., "incl. 1 drink" or "at the door â‚¬15"', 'ensemble'); ?>">
                                    <p class="description"><?php _e('Additional info displayed below the price', 'ensemble'); ?></p>
                                   </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php 
                        /**
                         * Hook: ensemble_wizard_tickets_cards
                         * 
                         * Allows addons to add cards to the Tickets & Price step.
                         * Used by Booking Engine, Tickets Pro, etc.
                         * 
                         * @since 2.9.x
                         */
                        do_action('ensemble_wizard_tickets_cards');
                        ?>
                    </div>
                    
                    <!-- STEP 5: Media -->
                    <div class="es-form-section" data-step="5" style="display: none;">
                        
                        <!-- Featured Image Card -->
                        <div class="es-form-card">
                            <div class="es-form-card-header">
                                <div class="es-form-card-icon">
                                    <span class="dashicons dashicons-format-image"></span>
                                </div>
                                <div class="es-form-card-title">
                                    <h3><?php _e('Featured Image', 'ensemble'); ?></h3>
                                    <p class="es-form-card-desc"><?php printf(__('Main image for %s cards and detail pages', 'ensemble'), strtolower($event_singular)); ?></p>
                                </div>
                            </div>
                            <div class="es-form-card-body">
                                <div id="es-featured-image-container" class="es-media-dropzone" data-type="featured">
                                    <div id="es-featured-image-preview" class="es-media-preview"></div>
                                    <div class="es-dropzone-content">
                                        <span class="dashicons dashicons-cloud-upload"></span>
                                        <p><?php _e('Drag & drop an image here', 'ensemble'); ?></p>
                                        <p class="es-dropzone-or"><?php _e('or', 'ensemble'); ?></p>
                                        <button type="button" id="es-upload-image-btn" class="button">
                                            <?php _e('Select from Media Library', 'ensemble'); ?>
                                        </button>
                                    </div>
                                    <input type="hidden" id="es-featured-image-id" name="featured_image_id" value="">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Hero Video Card -->
                        <div class="es-form-card">
                            <div class="es-form-card-header">
                                <div class="es-form-card-icon">
                                    <span class="dashicons dashicons-video-alt3"></span>
                                </div>
                                <div class="es-form-card-title">
                                    <h3><?php _e('Hero Video', 'ensemble'); ?></h3>
                                    <p class="es-form-card-desc"><?php _e('Optional video background for Hero layout', 'ensemble'); ?></p>
                                </div>
                            </div>
                            <div class="es-form-card-body">
                                <div class="es-form-row">
                                    <label for="es-hero-video-url"><?php _e('Video URL', 'ensemble'); ?></label>
                                    <input type="url" 
                                           id="es-hero-video-url" 
                                           name="hero_video_url" 
                                           placeholder="<?php _e('https://youtube.com/watch?v=... or https://vimeo.com/...', 'ensemble'); ?>">
                                    <p class="description"><?php _e('YouTube, Vimeo, or direct MP4 URL', 'ensemble'); ?></p>
                                </div>
                                <div class="es-video-options" style="display: flex; gap: 20px; flex-wrap: wrap;">
                                    <label class="es-checkbox">
                                        <input type="checkbox" name="hero_video_autoplay" value="1" checked>
                                        <span class="es-checkbox-box"></span>
                                        <span class="es-checkbox-label"><?php _e('Autoplay (muted)', 'ensemble'); ?></span>
                                    </label>
                                    <label class="es-checkbox">
                                        <input type="checkbox" name="hero_video_loop" value="1" checked>
                                        <span class="es-checkbox-box"></span>
                                        <span class="es-checkbox-label"><?php _e('Loop', 'ensemble'); ?></span>
                                    </label>
                                    <label class="es-checkbox">
                                        <input type="checkbox" name="hero_video_controls" value="1">
                                        <span class="es-checkbox-box"></span>
                                        <span class="es-checkbox-label"><?php _e('Show controls', 'ensemble'); ?></span>
                                    </label>
                                </div>
                                
                                <!-- Video Preview -->
                                <div id="es-hero-video-preview" class="es-video-preview" style="margin-top: 15px; display: none;">
                                    <div class="es-video-preview-container" style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: var(--es-radius); background: #000;">
                                        <iframe id="es-video-preview-frame" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0;"></iframe>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Event Gallery Card -->
                        <div class="es-form-card">
                            <div class="es-form-card-header">
                                <div class="es-form-card-icon">
                                    <span class="dashicons dashicons-images-alt2"></span>
                                </div>
                                <div class="es-form-card-title">
                                    <h3><?php _e('Event Gallery', 'ensemble'); ?></h3>
                                    <p class="es-form-card-desc"><?php _e('Additional images for the event', 'ensemble'); ?></p>
                                </div>
                            </div>
                            <div class="es-form-card-body">
                                <!-- Artist Manager Pattern: Button außerhalb, immer sichtbar -->
                                <div id="es-gallery-container" class="es-gallery-upload-box">
                                    <button type="button" id="es-upload-gallery-btn" class="es-upload-btn">
                                        <span class="dashicons dashicons-images-alt2"></span>
                                        <span><?php _e('Add Gallery Images', 'ensemble'); ?></span>
                                    </button>
                                    <div id="es-gallery-preview" class="es-gallery-preview"></div>
                                    <input type="hidden" id="es-gallery-ids" name="gallery_ids" value="">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Downloads Card (Add-on) -->
                        <?php 
                        $downloads_addon_active = class_exists('ES_Addon_Manager') && ES_Addon_Manager::is_addon_active('downloads');
                        if ($downloads_addon_active): 
                        ?>
                        <div class="es-form-card">
                            <div class="es-form-card-header">
                                <div class="es-form-card-icon">
                                    <span class="dashicons dashicons-download"></span>
                                </div>
                                <div class="es-form-card-title">
                                    <h3><?php _e('Downloads', 'ensemble'); ?></h3>
                                    <p class="es-form-card-desc"><?php _e('Presentations, handouts, materials', 'ensemble'); ?></p>
                                </div>
                            </div>
                            <div class="es-form-card-body">
                                <div class="es-downloads-wizard-section">
                                    <p class="description" style="margin-top: 0; margin-bottom: 15px;">
                                        <span class="es-badge-addon" style="font-size: 10px; padding: 2px 8px; background: var(--es-primary); color: white; border-radius: 10px; margin-right: 8px;">Add-on</span>
                                        <?php _e('Link files to this event', 'ensemble'); ?>
                                    </p>
                                    
                                    <input type="hidden" id="es-downloads-data" name="downloads_data" value="[]">
                                    
                                    <div class="es-downloads-wizard-list" id="es-downloads-wizard-list">
                                        <!-- Downloads werden hier per JS eingefügt -->
                                    </div>
                                    
                                    <div class="es-downloads-wizard-empty" id="es-downloads-wizard-empty">
                                        <div class="es-empty-icon">
                                            <span class="dashicons dashicons-download"></span>
                                        </div>
                                        <p><?php _e('No downloads linked', 'ensemble'); ?></p>
                                        <p class="description"><?php _e('Add presentations, handouts or other materials', 'ensemble'); ?></p>
                                    </div>
                                    
                                    <div class="es-downloads-wizard-actions" style="display: flex; gap: 10px; margin-top: 15px;">
                                        <button type="button" class="button" id="es-link-download-btn">
                                            <span class="dashicons dashicons-admin-links" style="margin-top: 3px;"></span>
                                            <?php _e('Link existing download', 'ensemble'); ?>
                                        </button>
                                        <button type="button" class="button button-primary" id="es-add-download-wizard-btn">
                                            <span class="dashicons dashicons-external" style="margin-top: 3px;"></span>
                                            <?php _e('Manage Downloads', 'ensemble'); ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- STEP 6: Additional Info -->
                    <div class="es-form-section" data-step="6" style="display: none;">
                        
                        <!-- Additional Information Card -->
                        <?php if (es_field_enabled('additional_info')): ?>
                        <div class="es-form-card">
                            <div class="es-form-card-header">
                                <div class="es-form-card-icon">
                                    <span class="dashicons dashicons-editor-paragraph"></span>
                                </div>
                                <div class="es-form-card-title">
                                    <h3><?php _e('Event Details', 'ensemble'); ?></h3>
                                    <p class="es-form-card-desc"><?php _e('Additional information like directions, parking, etc.', 'ensemble'); ?></p>
                                </div>
                            </div>
                            <div class="es-form-card-body">
                                <div class="es-wysiwyg-wrap">
                                    <?php 
                                    wp_editor('', 'es-event-additional-info', array(
                                        'textarea_name' => 'event_additional_info',
                                        'textarea_rows' => 6,
                                        'media_buttons' => false,
                                        'teeny' => true,
                                        'quicktags' => array('buttons' => 'strong,em,link,ul,ol,li'),
                                        'tinymce' => array(
                                            'toolbar1' => 'bold,italic,underline,|,bullist,numlist,|,link,unlink,|,removeformat',
                                            'toolbar2' => '',
                                            'statusbar' => false,
                                            'resize' => false,
                                        ),
                                    ));
                                    ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- External Link Card -->
                        <?php if (es_field_enabled('external_link')): ?>
                        <div class="es-form-card">
                            <div class="es-form-card-header">
                                <div class="es-form-card-icon">
                                    <span class="dashicons dashicons-external"></span>
                                </div>
                                <div class="es-form-card-title">
                                    <h3><?php _e('External Link', 'ensemble'); ?></h3>
                                    <p class="es-form-card-desc"><?php _e('Link to more information outside your site', 'ensemble'); ?></p>
                                </div>
                            </div>
                            <div class="es-form-card-body">
                                <div class="es-form-row">
                                    <label for="es-event-external-url"><?php _e('URL', 'ensemble'); ?></label>
                                    <input type="url" 
                                           id="es-event-external-url" 
                                           name="event_external_url" 
                                           placeholder="<?php _e('https://example.com/more-info', 'ensemble'); ?>">
                                    <p class="description"><?php _e('Link to an external page (e.g., artist website, venue info)', 'ensemble'); ?></p>
                                </div>
                                
                                <div class="es-form-row">
                                    <label for="es-event-external-text"><?php _e('Button Text', 'ensemble'); ?></label>
                                    <input type="text" 
                                           id="es-event-external-text" 
                                           name="event_external_text" 
                                           placeholder="<?php _e('e.g., "More Info" or "Visit Website"', 'ensemble'); ?>">
                                    <p class="description"><?php _e('Text displayed on the external link button', 'ensemble'); ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php 
                        // Reservierungen - nur anzeigen wenn Addon aktiv
                        $reservations_active = class_exists('ES_Addon_Manager') && ES_Addon_Manager::is_addon_active('reservations');
                        if ($reservations_active): 
                        ?>
                        <!-- Reservations Card -->
                        <div class="es-form-card">
                            <div class="es-form-card-header">
                                <div class="es-form-card-icon">
                                    <span class="dashicons dashicons-clipboard"></span>
                                </div>
                                <div class="es-form-card-title">
                                    <h3><?php _e('Reservierungen', 'ensemble'); ?> <span class="es-badge-new" style="font-size: 10px; padding: 2px 6px; background: var(--es-success); color: white; border-radius: 10px; margin-left: 8px;">PRO</span></h3>
                                    <p class="es-form-card-desc"><?php _e('Enable guestlist and table reservations', 'ensemble'); ?></p>
                                </div>
                            </div>
                            <div class="es-form-card-body">
                                <div class="es-form-row">
                                    <label class="es-toggle">
                                        <input type="checkbox" id="es-reservation-enabled" name="reservation_enabled" value="1">
                                        <span class="es-toggle-track"></span>
                                        <span class="es-toggle-label"><?php _e('Enable reservations for this event', 'ensemble'); ?></span>
                                    </label>
                                </div>
                                
                                <div id="es-reservation-options" class="es-conditional-section" style="display: none; margin-top: 15px; padding: 15px; background: var(--es-surface-secondary); border-radius: var(--es-radius); border: 1px solid var(--es-border);">
                                    
                                    <div class="es-form-row">
                                        <label><strong><?php _e('Reservierungstypen & Kontingente', 'ensemble'); ?></strong></label>
                                        <p class="description" style="margin-bottom: 12px;"><?php _e('Aktiviere die gewünschten Typen und setze optionale Kontingente.', 'ensemble'); ?></p>
                                        
                                        <!-- Guestlist -->
                                        <div class="es-reservation-type-row" style="display: flex; align-items: center; gap: 12px; padding: 12px; background: var(--es-surface-tertiary, rgba(255,255,255,0.05)); border-radius: 8px; margin-bottom: 8px;">
                                            <label class="es-checkbox" style="flex: 0 0 160px;">
                                                <input type="checkbox" name="reservation_types[]" value="guestlist" class="es-type-toggle" data-type="guestlist" checked>
                                                <span class="es-checkbox-box"></span>
                                                <span class="es-checkbox-label"><span class="dashicons dashicons-groups" style="font-size: 16px; width: 16px; height: 16px; margin-right: 4px;"></span><?php _e('Guestlist', 'ensemble'); ?></span>
                                            </label>
                                            <div class="es-capacity-field" data-for="guestlist" style="display: flex; align-items: center; gap: 8px;">
                                                <input type="number" name="reservation_capacity_guestlist" id="es-capacity-guestlist" min="0" placeholder="∞" style="width: 80px;" title="<?php _e('Kontingent für Guestlist', 'ensemble'); ?>">
                                                <span class="es-capacity-label" style="font-size: 12px; color: var(--es-text-muted);"><?php _e('Plätze', 'ensemble'); ?></span>
                                            </div>
                                        </div>
                                        
                                        <!-- VIP -->
                                        <div class="es-reservation-type-row" style="display: flex; align-items: center; gap: 12px; padding: 12px; background: var(--es-surface-tertiary, rgba(255,255,255,0.05)); border-radius: 8px; margin-bottom: 8px;">
                                            <label class="es-checkbox" style="flex: 0 0 160px;">
                                                <input type="checkbox" name="reservation_types[]" value="vip" class="es-type-toggle" data-type="vip">
                                                <span class="es-checkbox-box"></span>
                                                <span class="es-checkbox-label"><span class="dashicons dashicons-star-filled" style="font-size: 16px; width: 16px; height: 16px; margin-right: 4px; color: var(--es-warning);"></span><?php _e('VIP-Liste', 'ensemble'); ?></span>
                                            </label>
                                            <div class="es-capacity-field" data-for="vip" style="display: none; align-items: center; gap: 8px;">
                                                <input type="number" name="reservation_capacity_vip" id="es-capacity-vip" min="0" placeholder="∞" style="width: 80px;" title="<?php _e('Kontingent für VIP', 'ensemble'); ?>">
                                                <span class="es-capacity-label" style="font-size: 12px; color: var(--es-text-muted);"><?php _e('Plätze', 'ensemble'); ?></span>
                                            </div>
                                        </div>
                                        
                                        <!-- Table -->
                                        <?php 
                                        $floorplan_active = class_exists('ES_Addon_Manager') && ES_Addon_Manager::is_addon_active('floorplan');
                                        ?>
                                        <div class="es-reservation-type-row" style="display: flex; align-items: center; gap: 12px; padding: 12px; background: var(--es-surface-tertiary, rgba(255,255,255,0.05)); border-radius: 8px; margin-bottom: 8px; <?php echo !$floorplan_active ? 'opacity: 0.5;' : ''; ?>">
                                            <label class="es-checkbox" style="flex: 0 0 160px;">
                                                <input type="checkbox" name="reservation_types[]" value="table" class="es-type-toggle" data-type="table" <?php echo !$floorplan_active ? 'disabled' : ''; ?>>
                                                <span class="es-checkbox-box"></span>
                                                <span class="es-checkbox-label"><span class="dashicons dashicons-networking" style="font-size: 16px; width: 16px; height: 16px; margin-right: 4px;"></span><?php _e('Tischreservierung', 'ensemble'); ?></span>
                                            </label>
                                            <?php if ($floorplan_active): ?>
                                            <div class="es-capacity-field" data-for="table" style="display: none; align-items: center; gap: 8px;">
                                                <input type="number" name="reservation_capacity_table" id="es-capacity-table" min="0" placeholder="∞" style="width: 80px;" title="<?php _e('Kontingent für Tische', 'ensemble'); ?>">
                                                <span class="es-capacity-label" style="font-size: 12px; color: var(--es-text-muted);"><?php _e('Tische', 'ensemble'); ?></span>
                                            </div>
                                            <?php else: ?>
                                            <span style="font-size: 11px; color: var(--es-text-muted);"><?php _e('Benötigt Floor Plan Addon', 'ensemble'); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="es-form-row" style="margin-top: 15px;">
                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                            <div>
                                                <label for="es-reservation-deadline"><?php _e('Anmeldeschluss', 'ensemble'); ?></label>
                                                <div style="display: flex; align-items: center; gap: 8px;">
                                                    <input type="number" id="es-reservation-deadline" name="reservation_deadline_hours" value="24" min="0" style="width: 80px;">
                                                    <span><?php _e('Stunden vor Event', 'ensemble'); ?></span>
                                                </div>
                                            </div>
                                            <div>
                                                <label for="es-max-guests"><?php _e('Max. Gäste pro Buchung', 'ensemble'); ?></label>
                                                <input type="number" id="es-max-guests" name="reservation_max_guests" min="1" max="50" value="10" style="width: 80px;">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="es-form-row" style="margin-top: 15px;">
                                        <label class="es-toggle">
                                            <input type="checkbox" name="reservation_auto_confirm" value="1" checked>
                                            <span class="es-toggle-track"></span>
                                            <span class="es-toggle-label">
                                                <?php _e('Auto-confirm reservations', 'ensemble'); ?>
                                                <small><?php _e('When disabled, reservations must be confirmed manually.', 'ensemble'); ?></small>
                                            </span>
                                        </label>
                                    </div>
                                    
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php 
                        /**
                         * Hook: ensemble_wizard_form_cards
                         * 
                         * Allows addons to add their own form cards to the wizard.
                         * Cards should follow the es-form-card structure.
                         * 
                         * @since 2.9.0
                         */
                        do_action('ensemble_wizard_form_cards');
                        ?>
                    </div>
                    
                    <div class="es-form-actions">
                        <!-- Navigation (Icons) -->
                        <div class="es-wizard-nav-buttons">
                            <button type="button" class="es-wizard-prev es-icon-btn" style="display: none;" title="<?php esc_attr_e('Back', 'ensemble'); ?>">
                                <?php ES_Icons::icon('chevron-left'); ?>
                            </button>
                            <button type="button" class="es-wizard-next es-icon-btn" title="<?php esc_attr_e('Next', 'ensemble'); ?>">
                                <?php ES_Icons::icon('chevron-right'); ?>
                            </button>
                        </div>
                        
                        <!-- Save Buttons (always visible) -->
                        <div class="es-save-buttons">
                            <button type="button" id="es-save-btn" class="button button-secondary button-large">
                                <?php _e('Save', 'ensemble'); ?>
                            </button>
                            <button type="submit" class="button button-primary button-large">
                                <?php ES_Icons::icon('check'); ?>
                                <?php _e('Save & Close', 'ensemble'); ?>
                            </button>
                        </div>
                        
                        <!-- Action Icons -->
                        <div class="es-action-buttons">
                            <button type="button" id="es-copy-event-btn" class="es-icon-btn" style="display: none;" title="<?php printf(esc_attr__('Copy %s', 'ensemble'), strtolower($event_singular)); ?>">
                                <?php ES_Icons::icon('copy'); ?>
                            </button>
                            <button type="button" id="es-delete-event-btn" class="es-icon-btn es-icon-btn-danger" style="display: none;" title="<?php printf(esc_attr__('Delete %s', 'ensemble'), strtolower($event_singular)); ?>">
                                <?php ES_Icons::icon('trash'); ?>
                            </button>
                            <button type="button" id="es-cancel-btn" class="es-icon-btn" title="<?php esc_attr_e('Close', 'ensemble'); ?>">
                                <?php ES_Icons::icon('x'); ?>
                            </button>
                        </div>
                    </div>
                    
                </form>
                
            </div>
            
        </div>
        
    </div>
    
    <!-- Success/Error Messages -->
    <div id="es-message" class="es-message" style="display: none;"></div>
    
</div>

<?php // Wizard L10n Strings ?>
<script>
var esWizardL10n = {
    customVenueName: '<?php echo esc_js(__('Custom name for this event...', 'ensemble')); ?>',
    venueDescription: '<?php echo esc_js(__('Description/Info for this room...', 'ensemble')); ?>',
    genresForRoom: '<?php echo esc_js(__('Genres for this room', 'ensemble')); ?>',
    selectRoom: '<?php echo esc_js(__('Select room', 'ensemble')); ?>',
    // Dynamic Labels
    eventSingular: '<?php echo esc_js($event_singular); ?>',
    eventPlural: '<?php echo esc_js($event_plural); ?>',
    locationSingular: '<?php echo esc_js($location_singular); ?>',
    locationPlural: '<?php echo esc_js($location_plural); ?>',
    artistSingular: '<?php echo esc_js($artist_singular); ?>',
    artistPlural: '<?php echo esc_js($artist_plural); ?>',
    // Composed strings
    loadingEvents: '<?php printf(esc_js(__('Loading %s...', 'ensemble')), strtolower($event_plural)); ?>',
    noEventsFound: '<?php printf(esc_js(__('No %s found', 'ensemble')), strtolower($event_plural)); ?>',
    deleteConfirm: '<?php printf(esc_js(__('Delete this %s?', 'ensemble')), strtolower($event_singular)); ?>',
    saveEvent: '<?php printf(esc_js(__('Save %s', 'ensemble')), $event_singular); ?>',
    createEvent: '<?php printf(esc_js(__('Create %s', 'ensemble')), $event_singular); ?>'
};
</script>

<?php // Reservierungs-Toggle JavaScript ?>
<script>
jQuery(function($) {
    // Toggle Reservation Options
    $('#es-reservation-enabled').on('change', function() {
        if ($(this).is(':checked')) {
            $('#es-reservation-options').slideDown(200);
        } else {
            $('#es-reservation-options').slideUp(200);
        }
    });
    
    // Beim Laden prüfen
    if ($('#es-reservation-enabled').is(':checked')) {
        $('#es-reservation-options').show();
    }
    
    // Toggle Capacity Fields based on Type Selection
    $('.es-type-toggle').on('change', function() {
        var type = $(this).data('type');
        var $capacityField = $('.es-capacity-field[data-for="' + type + '"]');
        
        if ($(this).is(':checked')) {
            $capacityField.css('display', 'flex');
        } else {
            $capacityField.hide();
            $capacityField.find('input').val(''); // Clear value when unchecked
        }
    });
    
    // Initial state for capacity fields
    $('.es-type-toggle').each(function() {
        var type = $(this).data('type');
        var $capacityField = $('.es-capacity-field[data-for="' + type + '"]');
        
        if ($(this).is(':checked')) {
            $capacityField.css('display', 'flex');
        } else {
            $capacityField.hide();
        }
    });
    
    // ====================================
    // BREAKS / TIMELINE FUNCTIONALITY
    // ====================================
    
    var breaks = [];
    
    function renderBreaks() {
        var $list = $('#es-breaks-list');
        $list.empty();
        
        if (breaks.length === 0) {
            $list.html('<p style="color: var(--es-text-muted, #888); font-style: italic; margin: 0;"><?php echo esc_js(__('No breaks added yet. Click "Add Break" to create timeline entries.', 'ensemble')); ?></p>');
            return;
        }
        
        // Sort by time
        breaks.sort(function(a, b) {
            return (a.time || '').localeCompare(b.time || '');
        });
        
        var typeIcons = {
            'coffee': '☕',
            'lunch': '🍽️',
            'networking': '🤝',
            'registration': '📋',
            'workshop': '🛠️',
            'panel': '👥',
            'pause': '⏸️'
        };
        
        breaks.forEach(function(brk, index) {
            var icon = typeIcons[brk.type] || '⏸️';
            var html = '<div class="es-break-item" data-index="' + index + '" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; background: var(--es-surface-secondary, #2a2a2a); border-radius: 8px; border-left: 4px solid #B87333;">' +
                '<span class="es-break-icon" style="font-size: 20px;">' + icon + '</span>' +
                '<div class="es-break-info" style="flex: 1;">' +
                    '<div class="es-break-title" style="font-weight: 600; color: var(--es-text-primary, #fff);">' + (brk.title || brk.type) + '</div>' +
                    '<div class="es-break-meta" style="font-size: 12px; color: var(--es-text-muted, #888);">' +
                        (brk.time || '--:--') + ' • ' + (brk.duration || 30) + ' <?php echo esc_js(__('min', 'ensemble')); ?>' +
                    '</div>' +
                '</div>' +
                '<button type="button" class="es-break-remove" data-index="' + index + '" style="background: none; border: none; color: var(--es-text-muted, #888); cursor: pointer; padding: 4px;" title="<?php echo esc_js(__('Remove', 'ensemble')); ?>">' +
                    '<span class="dashicons dashicons-no-alt"></span>' +
                '</button>' +
            '</div>';
            $list.append(html);
        });
        
        // Update hidden field
        $('#es-agenda-breaks').val(JSON.stringify(breaks));
    }
    
    // Open break editor
    $('#es-add-break').on('click', function() {
        $('#es-break-type').val('coffee');
        $('#es-break-title').val('');
        $('#es-break-time').val('');
        $('#es-break-duration').val('30');
        $('#es-break-editor, #es-break-editor-overlay').show();
    });
    
    // Close break editor
    $('.es-break-editor-close, .es-break-editor-cancel, #es-break-editor-overlay').on('click', function() {
        $('#es-break-editor, #es-break-editor-overlay').hide();
    });
    
    // Save break
    $('.es-break-editor-save').on('click', function() {
        var brk = {
            type: $('#es-break-type').val(),
            title: $('#es-break-title').val() || $('#es-break-type option:selected').text().replace(/^[^\s]+\s/, ''),
            time: $('#es-break-time').val(),
            duration: $('#es-break-duration').val() || 30,
            icon: $('#es-break-type').val()
        };
        
        breaks.push(brk);
        renderBreaks();
        $('#es-break-editor, #es-break-editor-overlay').hide();
    });
    
    // Remove break
    $(document).on('click', '.es-break-remove', function() {
        var index = $(this).data('index');
        breaks.splice(index, 1);
        renderBreaks();
    });
    
    // Load breaks from event data
    $(document).on('ensemble_event_loaded', function(e, eventData) {
        if (eventData && eventData.agenda_breaks) {
            breaks = eventData.agenda_breaks;
            renderBreaks();
        } else {
            breaks = [];
            renderBreaks();
        }
    });
    
    // Initial render
    renderBreaks();
});
</script>

<?php 
// Agenda Add-on Event-Daten Script (nur wenn Add-on aktiv)
$agenda_active = function_exists('ES_Agenda') && ES_Agenda()->is_active();
if ($agenda_active): 
?>
<script>
// Agenda Data wird vom Admin-JS dynamisch geladen
jQuery(document).on('ensemble_event_loaded', function(e, eventData) {
    if (eventData && eventData.agenda && typeof EnsembleAgenda !== 'undefined') {
        EnsembleAgenda.Editor.agenda = eventData.agenda;
        EnsembleAgenda.Editor.render();
    }
});
</script>
<?php endif; ?>