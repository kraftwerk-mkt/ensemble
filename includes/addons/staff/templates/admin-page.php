<?php
/**
 * Staff Manager Template
 * 
 * Follows Artist Manager pattern for consistent admin UI
 * Uses admin-unified.css (no custom CSS)
 *
 * @package Ensemble
 * @subpackage Addons/Staff
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$manager = $this->get_staff_manager();
$departments = $manager->get_departments();

// Get dynamic labels
$staff_singular = $this->get_staff_label(false);
$staff_plural = $this->get_staff_label(true);
$dept_singular = $this->get_department_label(false);
$dept_plural = $this->get_department_label(true);
?>

<style>
/* Staff Modal Scrollable Fix */
#es-staff-modal.es-modal-scrollable .es-modal-content {
    display: flex;
    flex-direction: column;
    max-height: calc(100vh - 40px);
    padding: 0;
    margin: 0;
    overflow: hidden;
}

#es-staff-modal .es-modal-header {
    flex-shrink: 0;
    padding: 20px 24px;
    border-bottom: 1px solid var(--es-border, #3c434a);
    background: var(--es-card-bg, #23262b);
}

#es-staff-modal .es-modal-header h2 {
    margin: 0;
    font-size: 1.5rem;
}

#es-staff-modal .es-modal-close {
    position: absolute;
    top: 20px;
    right: 24px;
}

#es-staff-modal .es-staff-form {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-height: 0;
    overflow: hidden;
}

#es-staff-modal .es-form-sections {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    padding: 24px;
    display: flex;
    flex-direction: column;
    gap: 20px;
    min-height: 0;
}

#es-staff-modal .es-modal-actions {
    flex-shrink: 0;
    padding: 16px 24px;
    border-top: 1px solid var(--es-border, #3c434a);
    background: var(--es-card-bg, #23262b);
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

/* Custom scrollbar */
#es-staff-modal .es-form-sections::-webkit-scrollbar {
    width: 8px;
}

#es-staff-modal .es-form-sections::-webkit-scrollbar-track {
    background: var(--es-background, #1a1a2e);
    border-radius: 4px;
}

#es-staff-modal .es-form-sections::-webkit-scrollbar-thumb {
    background: var(--es-border, #3c434a);
    border-radius: 4px;
}

#es-staff-modal .es-form-sections::-webkit-scrollbar-thumb:hover {
    background: var(--es-text-muted, #888);
}

/* Spin Animation for Copy Button */
.es-spin {
    animation: es-spin 1s linear infinite;
}

@keyframes es-spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>

<div class="wrap es-manager-wrap es-staff-wrap">
    <h1><?php echo esc_html($staff_plural); ?> Manager</h1>
    
    <div class="es-manager-container">
        
        <!-- Compact Toolbar (analog to Artist Manager) -->
        <div class="es-wizard-toolbar es-staff-toolbar">
            <!-- Main Row: Search, Filter Toggle, Bulk Actions, View Toggle, Create -->
            <div class="es-toolbar-row es-toolbar-main-row">
                <div class="es-filter-search">
                    <input type="text" 
                           id="es-staff-search" 
                           class="es-search-input" 
                           placeholder="<?php printf(__('Search %s...', 'ensemble'), strtolower($staff_plural)); ?>">
                    <span class="es-search-icon"><?php ES_Icons::icon('search'); ?></span>
                </div>
                
                <!-- Filter Toggle Button -->
                <button type="button" id="es-toggle-filters" class="button es-filter-toggle-btn" title="<?php _e('Show/Hide Filters', 'ensemble'); ?>">
                    <span class="dashicons dashicons-filter"></span>
                    <span class="es-filter-badge" style="display:none;">0</span>
                </button>
                
                <span class="es-toolbar-divider"></span>
                
                <!-- Inline Bulk Actions -->
                <div class="es-bulk-actions-inline" id="es-bulk-actions">
                    <span class="es-bulk-selected-count" id="es-selected-count"></span>
                    <select id="es-bulk-action-select" title="<?php _e('Bulk Actions', 'ensemble'); ?>">
                        <option value=""><?php _e('Bulk Actions', 'ensemble'); ?></option>
                        <option value="delete"><?php _e('Delete', 'ensemble'); ?></option>
                        <option value="assign_department"><?php printf(__('Assign %s', 'ensemble'), $dept_singular); ?></option>
                        <option value="remove_department"><?php printf(__('Remove %s', 'ensemble'), $dept_singular); ?></option>
                    </select>
                    <button id="es-apply-bulk-action" class="button" title="<?php _e('Apply Bulk Action', 'ensemble'); ?>">
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
                
                <!-- Staff Count -->
                <span id="es-staff-count" class="es-item-count"></span>
                
                <button id="es-create-staff-btn" class="button button-primary">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <?php printf(__('Add %s', 'ensemble'), $staff_singular); ?>
                </button>
            </div>
            
            <!-- Collapsible Filter Panel -->
            <div class="es-toolbar-row es-filter-panel" id="es-filter-panel" style="display:none;">
                <select id="es-filter-department" class="es-filter-select" data-filter="department">
                    <option value=""><?php printf(__('All %s', 'ensemble'), $dept_plural); ?></option>
                    <?php foreach ($departments as $dept): ?>
                    <option value="<?php echo esc_attr($dept['id']); ?>"><?php echo esc_html($dept['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                
                <select id="es-filter-abstract" class="es-filter-select" data-filter="abstract">
                    <option value=""><?php _e('All Submission Status', 'ensemble'); ?></option>
                    <option value="enabled"><?php _e('Accepts Submissions', 'ensemble'); ?></option>
                    <option value="disabled"><?php _e('No Submissions', 'ensemble'); ?></option>
                </select>
                
                <button type="button" id="es-clear-filters" class="button" style="display:none;">
                    <span class="dashicons dashicons-dismiss"></span>
                    <?php _e('Clear', 'ensemble'); ?>
                </button>
            </div>
        </div>
        
        <!-- Staff List/Grid -->
        <div id="es-staff-container" class="es-items-container">
            <div class="es-loading"><?php printf(__('Loading %s...', 'ensemble'), strtolower($staff_plural)); ?></div>
        </div>
        
    </div>
    
    <!-- Staff Modal -->
    <div id="es-staff-modal" class="es-modal es-modal-scrollable" style="display: none;">
        <div class="es-modal-content es-modal-large">
            <span class="es-modal-close">&times;</span>
            
            <div class="es-modal-header">
                <h2 id="es-modal-title"><?php printf(__('Add New %s', 'ensemble'), $staff_singular); ?></h2>
            </div>
            
            <form id="es-staff-form" class="es-manager-form es-staff-form">
                
                <input type="hidden" id="es-staff-id" name="staff_id" value="">
                
                <div class="es-form-sections">
                    
                    <!-- Section 1: Basic Information -->
                    <div class="es-form-card">
                        <div class="es-form-card-header">
                            <div class="es-form-card-icon">
                                <span class="dashicons dashicons-businessperson"></span>
                            </div>
                            <div class="es-form-card-title">
                                <h3><?php _e('Basic Information', 'ensemble'); ?></h3>
                                <p class="es-form-card-desc"><?php _e('Name, position, department and photo', 'ensemble'); ?></p>
                            </div>
                        </div>
                        
                        <div class="es-form-card-body">
                            <div class="es-form-row">
                                <label for="es-staff-name"><?php _e('Name', 'ensemble'); ?> *</label>
                                <input type="text" id="es-staff-name" name="name" required placeholder="<?php _e('e.g. John Smith', 'ensemble'); ?>">
                            </div>
                            
                            <div class="es-form-row es-form-row-2">
                                <div class="es-form-field">
                                    <label for="es-staff-position"><?php _e('Position / Title', 'ensemble'); ?></label>
                                    <input type="text" id="es-staff-position" name="position" placeholder="<?php _e('e.g. Program Director', 'ensemble'); ?>">
                                </div>
                                <div class="es-form-field">
                                    <label for="es-staff-responsibility"><?php _e('Responsibility', 'ensemble'); ?></label>
                                    <input type="text" id="es-staff-responsibility" name="responsibility" placeholder="<?php _e('e.g. Abstract Submissions', 'ensemble'); ?>">
                                </div>
                            </div>
                            
                            <?php if (!empty($departments)): ?>
                            <div class="es-form-row">
                                <label for="es-staff-department"><?php echo esc_html($dept_plural); ?></label>
                                <select id="es-staff-department" name="departments[]" multiple class="es-select-multiple">
                                    <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo esc_attr($dept['id']); ?>"><?php echo esc_html($dept['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="es-field-hint">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=ensemble-taxonomies&tab=departments')); ?>" target="_blank">
                                        <?php printf(__('Manage %s', 'ensemble'), $dept_plural); ?>
                                    </a>
                                </p>
                            </div>
                            <?php endif; ?>
                            
                            <div class="es-form-row">
                                <label><?php _e('Photo', 'ensemble'); ?></label>
                                <div class="es-image-upload-field">
                                    <input type="hidden" id="es-staff-image" name="featured_image_id" value="">
                                    <div class="es-image-preview" id="es-staff-image-preview">
                                        <span class="dashicons dashicons-businessperson"></span>
                                    </div>
                                    <div class="es-image-actions">
                                        <button type="button" class="button es-select-image-btn"><?php _e('Select Image', 'ensemble'); ?></button>
                                        <button type="button" class="button es-remove-image-btn" style="display:none;"><?php _e('Remove', 'ensemble'); ?></button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="es-form-row">
                                <label for="es-staff-description"><?php _e('Bio / Description', 'ensemble'); ?></label>
                                <textarea id="es-staff-description" name="description" rows="4" placeholder="<?php _e('Short biography or description...', 'ensemble'); ?>"></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Section 2: Contact Information -->
                    <div class="es-form-card">
                        <div class="es-form-card-header">
                            <div class="es-form-card-icon">
                                <span class="dashicons dashicons-email"></span>
                            </div>
                            <div class="es-form-card-title">
                                <h3><?php _e('Contact Information', 'ensemble'); ?></h3>
                                <p class="es-form-card-desc"><?php _e('Email, phone numbers and office hours', 'ensemble'); ?></p>
                            </div>
                        </div>
                        
                        <div class="es-form-card-body">
                            <div class="es-form-row">
                                <label for="es-staff-email"><?php _e('Email Address', 'ensemble'); ?></label>
                                <input type="email" id="es-staff-email" name="email" placeholder="name@example.com">
                            </div>
                            
                            <div class="es-form-row">
                                <label><?php _e('Phone Numbers', 'ensemble'); ?></label>
                                <div class="es-repeater" id="es-phone-repeater">
                                    <div class="es-repeater-item">
                                        <select name="phone[0][type]" class="es-phone-type">
                                            <option value="office"><?php _e('Office', 'ensemble'); ?></option>
                                            <option value="mobile"><?php _e('Mobile', 'ensemble'); ?></option>
                                            <option value="fax"><?php _e('Fax', 'ensemble'); ?></option>
                                        </select>
                                        <input type="text" name="phone[0][number]" class="es-phone-number" placeholder="+1 234 567890">
                                        <button type="button" class="button es-repeater-remove">
                                            <span class="dashicons dashicons-minus"></span>
                                        </button>
                                    </div>
                                </div>
                                <button type="button" class="button es-repeater-add" data-target="es-phone-repeater">
                                    <span class="dashicons dashicons-plus-alt2"></span>
                                    <?php _e('Add Phone', 'ensemble'); ?>
                                </button>
                            </div>
                            
                            <div class="es-form-row">
                                <label for="es-staff-hours"><?php _e('Office Hours', 'ensemble'); ?></label>
                                <input type="text" id="es-staff-hours" name="office_hours" placeholder="<?php _e('e.g. Mon-Fri 9:00-17:00', 'ensemble'); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Section 3: Web & Social -->
                    <div class="es-form-card">
                        <div class="es-form-card-header">
                            <div class="es-form-card-icon">
                                <span class="dashicons dashicons-share"></span>
                            </div>
                            <div class="es-form-card-title">
                                <h3><?php _e('Web & Social', 'ensemble'); ?></h3>
                                <p class="es-form-card-desc"><?php _e('Website and social media links', 'ensemble'); ?></p>
                            </div>
                        </div>
                        
                        <div class="es-form-card-body">
                            <div class="es-form-row">
                                <label for="es-staff-website"><?php _e('Website', 'ensemble'); ?></label>
                                <input type="url" id="es-staff-website" name="website" placeholder="https://">
                            </div>
                            
                            <div class="es-form-row es-form-row-2">
                                <div class="es-form-field">
                                    <label for="es-staff-linkedin">LinkedIn</label>
                                    <input type="url" id="es-staff-linkedin" name="linkedin" placeholder="https://linkedin.com/in/...">
                                </div>
                                <div class="es-form-field">
                                    <label for="es-staff-twitter">Twitter / X</label>
                                    <input type="url" id="es-staff-twitter" name="twitter" placeholder="https://twitter.com/...">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Section 4: Submission Settings -->
                    <div class="es-form-card">
                        <div class="es-form-card-header">
                            <div class="es-form-card-icon">
                                <span class="dashicons dashicons-upload"></span>
                            </div>
                            <div class="es-form-card-title">
                                <h3><?php _e('File Submissions', 'ensemble'); ?></h3>
                                <p class="es-form-card-desc"><?php _e('Allow visitors to submit abstracts or documents', 'ensemble'); ?></p>
                            </div>
                        </div>
                        
                        <div class="es-form-card-body">
                            <div class="es-form-row">
                                <label class="es-toggle-label">
                                    <input type="checkbox" id="es-staff-abstract-enabled" name="abstract_enabled" value="1">
                                    <span class="es-toggle-switch"></span>
                                    <span class="es-toggle-label-text"><?php _e('Accept File Submissions', 'ensemble'); ?></span>
                                </label>
                                <p class="es-field-hint" style="margin-top: 8px;"><?php _e('Enable to allow visitors to submit files via a contact form.', 'ensemble'); ?></p>
                            </div>
                            
                            <div class="es-abstract-settings" style="display:none; margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--es-border, #3c434a);">
                                <div class="es-form-row">
                                    <label class="es-form-label"><?php _e('Accepted File Types', 'ensemble'); ?></label>
                                    <div class="es-checkbox-group" style="display: flex; flex-wrap: wrap; gap: 16px; margin-top: 8px;">
                                        <label class="es-checkbox-label" style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                            <input type="checkbox" name="abstract_types[]" value="pdf" checked style="width: 16px; height: 16px; margin: 0;">
                                            <span>PDF</span>
                                        </label>
                                        <label class="es-checkbox-label" style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                            <input type="checkbox" name="abstract_types[]" value="doc" style="width: 16px; height: 16px; margin: 0;">
                                            <span>Word (DOC/DOCX)</span>
                                        </label>
                                        <label class="es-checkbox-label" style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                            <input type="checkbox" name="abstract_types[]" value="ppt" style="width: 16px; height: 16px; margin: 0;">
                                            <span>PowerPoint (PPT/PPTX)</span>
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="es-form-row" style="margin-top: 16px;">
                                    <label for="es-staff-max-size" class="es-form-label"><?php _e('Maximum File Size (MB)', 'ensemble'); ?></label>
                                    <input type="number" id="es-staff-max-size" name="abstract_max_size" value="10" min="1" max="100" style="width: 100px; margin-top: 6px;">
                                </div>
                                
                                <div class="es-info-box" style="margin-top: 20px; display: flex; gap: 12px; padding: 16px; background: rgba(59, 130, 246, 0.1); border-radius: 6px; border-left: 3px solid var(--es-primary, #3b82f6);">
                                    <span class="dashicons dashicons-info" style="color: var(--es-primary, #3b82f6);"></span>
                                    <div>
                                        <strong style="display: block; margin-bottom: 4px;"><?php _e('Shortcode', 'ensemble'); ?></strong>
                                        <p style="margin: 0 0 8px; color: var(--es-text-muted, #9ca3af); font-size: 13px;"><?php _e('Use this shortcode to display the submission form:', 'ensemble'); ?></p>
                                        <code id="es-abstract-shortcode" style="display: inline-block; padding: 6px 12px; background: var(--es-surface, #1e1e1e); border-radius: 4px; font-size: 13px; color: var(--es-primary, #3b82f6);">[ensemble_contact_form staff_id="0"]</code>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Section 5: Display Order -->
                    <div class="es-form-card es-form-card-compact">
                        <div class="es-form-card-body">
                            <div class="es-form-row es-form-row-inline">
                                <label for="es-staff-order"><?php _e('Sort Order', 'ensemble'); ?></label>
                                <input type="number" id="es-staff-order" name="menu_order" value="0" min="0" style="width: 80px;">
                                <span class="es-field-hint"><?php _e('Lower numbers appear first', 'ensemble'); ?></span>
                            </div>
                        </div>
                    </div>
                    
                </div>
                
                <!-- Form Actions -->
                <div class="es-modal-actions">
                    <button type="button" class="button es-modal-cancel"><?php _e('Cancel', 'ensemble'); ?></button>
                    <button type="submit" class="button button-primary es-modal-save">
                        <span class="es-btn-text"><?php _e('Save', 'ensemble'); ?></span>
                        <span class="es-btn-loading" style="display:none;">
                            <span class="spinner is-active"></span>
                            <?php _e('Saving...', 'ensemble'); ?>
                        </span>
                    </button>
                </div>
                
            </form>
        </div>
    </div>
    
    <!-- Bulk Assign Modal -->
    <div id="es-bulk-assign-modal" class="es-modal es-modal-small" style="display:none;">
        <div class="es-modal-content">
            <span class="es-modal-close">&times;</span>
            <h3 id="es-bulk-assign-title"><?php _e('Assign', 'ensemble'); ?></h3>
            <select id="es-bulk-assign-value" style="width: 100%; margin: 15px 0;">
                <option value=""><?php _e('Select...', 'ensemble'); ?></option>
            </select>
            <div class="es-modal-buttons">
                <button class="button es-modal-close-btn"><?php _e('Cancel', 'ensemble'); ?></button>
                <button id="es-bulk-assign-confirm" class="button button-primary"><?php _e('Apply', 'ensemble'); ?></button>
            </div>
        </div>
    </div>
    
</div>

<script>
jQuery(document).ready(function($) {
    
    var selectedStaffIds = [];
    var currentView = 'grid';
    var allStaff = [];
    
    // Dynamic Labels
    var labels = {
        singular: '<?php echo esc_js($staff_singular); ?>',
        plural: '<?php echo esc_js($staff_plural); ?>',
        department: '<?php echo esc_js($dept_singular); ?>',
        departments: '<?php echo esc_js($dept_plural); ?>',
        addNew: '<?php printf(esc_js(__('Add New %s', 'ensemble')), $staff_singular); ?>',
        edit: '<?php printf(esc_js(__('Edit %s', 'ensemble')), $staff_singular); ?>'
    };
    
    // Departments data
    var departmentsData = <?php echo json_encode($departments); ?>;
    
    // Load staff on init
    loadStaff();
    
    // ==========================================
    // LOAD STAFF
    // ==========================================
    
    function loadStaff() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'es_get_staff_list',
                nonce: '<?php echo wp_create_nonce('ensemble_staff_nonce'); ?>'
            },
            beforeSend: function() {
                $('#es-staff-container').html('<div class="es-loading"><?php printf(__('Loading %s...', 'ensemble'), strtolower($staff_plural)); ?></div>');
            },
            success: function(response) {
                if (response.success) {
                    allStaff = response.data.staff;
                    renderStaff(allStaff);
                    updateStaffCount(allStaff.length);
                }
            }
        });
    }
    
    function renderStaff(staff) {
        var $container = $('#es-staff-container');
        
        if (staff.length === 0) {
            $container.html(
                '<div class="es-empty-state">' +
                    '<span class="dashicons dashicons-businessperson"></span>' +
                    '<h3><?php printf(__('No %s found', 'ensemble'), strtolower($staff_plural)); ?></h3>' +
                    '<p><?php printf(__('Create your first %s to get started.', 'ensemble'), strtolower($staff_singular)); ?></p>' +
                    '<button class="button button-primary" id="es-create-first-staff">' +
                        '<span class="dashicons dashicons-plus-alt2"></span> ' +
                        '<?php printf(__('Add %s', 'ensemble'), $staff_singular); ?>' +
                    '</button>' +
                '</div>'
            );
            return;
        }
        
        // Set view class on container (same as artist manager)
        $container.removeClass('es-grid-view es-list-view').addClass('es-' + currentView + '-view');
        
        var html = '';
        staff.forEach(function(person) {
            html += buildStaffCard(person);
        });
        
        $container.html(html);
    }
    
    function buildStaffCard(person) {
        // Image or placeholder (same structure as artist manager)
        var imageHtml = person.featured_image 
            ? '<img src="' + person.featured_image + '" alt="' + escapeHtml(person.name) + '" class="es-item-image">'
            : '<div class="es-item-image no-image"><span class="dashicons dashicons-businessperson"></span></div>';
        
        // Department display
        var deptDisplay = person.department || '';
        
        return '<div class="es-item-card" data-staff-id="' + person.id + '">' +
            '<input type="checkbox" class="es-item-checkbox" data-id="' + person.id + '">' +
            imageHtml +
            '<div class="es-item-body">' +
                '<div class="es-item-info">' +
                    '<h3 class="es-item-title">' + escapeHtml(person.name) + '</h3>' +
                    '<div class="es-item-meta">' +
                        (person.position ? '<div class="es-item-meta-item"><span class="dashicons dashicons-nametag"></span><span>' + escapeHtml(person.position) + '</span></div>' : '') +
                        (deptDisplay ? '<div class="es-item-meta-item"><span class="dashicons dashicons-groups"></span><span>' + escapeHtml(deptDisplay) + '</span></div>' : '') +
                        (person.email ? '<div class="es-item-meta-item"><span class="dashicons dashicons-email"></span><span>' + escapeHtml(person.email) + '</span></div>' : '') +
                        (person.abstract_enabled ? '<div class="es-item-meta-item"><span class="dashicons dashicons-upload"></span><span><?php _e('Accepts Submissions', 'ensemble'); ?></span></div>' : '') +
                    '</div>' +
                '</div>' +
                '<div class="es-item-actions">' +
                    '<button class="button es-edit-staff" data-id="' + person.id + '">' +
                        '<span class="dashicons dashicons-edit"></span> <?php _e('Edit', 'ensemble'); ?>' +
                    '</button>' +
                    '<button class="es-icon-btn es-copy-staff" data-id="' + person.id + '" title="<?php _e('Copy', 'ensemble'); ?>">' +
                        '<span class="dashicons dashicons-admin-page"></span>' +
                    '</button>' +
                    '<button class="es-icon-btn es-icon-btn-danger es-delete-staff" data-id="' + person.id + '" title="<?php _e('Delete', 'ensemble'); ?>">' +
                        '<span class="dashicons dashicons-trash"></span>' +
                    '</button>' +
                '</div>' +
            '</div>' +
        '</div>';
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    function updateStaffCount(count) {
        $('#es-staff-count').text(count + ' ' + (count === 1 ? labels.singular : labels.plural));
    }
    
    // ==========================================
    // CREATE / EDIT STAFF
    // ==========================================
    
    $(document).on('click', '#es-create-staff-btn, #es-create-first-staff', function(e) {
        e.preventDefault();
        resetForm();
        $('#es-modal-title').text(labels.addNew);
        $('#es-staff-modal').fadeIn(200);
    });
    
    $(document).on('click', '.es-edit-staff', function(e) {
        e.preventDefault();
        var staffId = $(this).data('id');
        loadStaffForEdit(staffId);
    });
    
    function loadStaffForEdit(staffId) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'es_get_staff',
                nonce: '<?php echo wp_create_nonce('ensemble_staff_nonce'); ?>',
                staff_id: staffId
            },
            success: function(response) {
                if (response.success) {
                    populateForm(response.data);
                    $('#es-modal-title').text(labels.edit);
                    $('#es-staff-modal').fadeIn(200);
                }
            }
        });
    }
    
    function populateForm(staff) {
        resetForm();
        
        $('#es-staff-id').val(staff.id);
        $('#es-staff-name').val(staff.name);
        $('#es-staff-position').val(staff.position);
        $('#es-staff-responsibility').val(staff.responsibility);
        $('#es-staff-email').val(staff.email);
        $('#es-staff-hours').val(staff.office_hours);
        $('#es-staff-description').val(staff.description);
        $('#es-staff-order').val(staff.menu_order);
        $('#es-staff-website').val(staff.website);
        $('#es-staff-linkedin').val(staff.linkedin);
        $('#es-staff-twitter').val(staff.twitter);
        
        if (staff.featured_image_id) {
            $('#es-staff-image').val(staff.featured_image_id);
            $('#es-staff-image-preview').html('<img src="' + staff.featured_image + '" alt="">');
            $('.es-remove-image-btn').show();
        }
        
        if (staff.departments && staff.departments.length) {
            var deptIds = staff.departments.map(function(d) { return d.id.toString(); });
            $('#es-staff-department').val(deptIds);
        }
        
        if (staff.phones && staff.phones.length) {
            var $repeater = $('#es-phone-repeater');
            $repeater.empty();
            staff.phones.forEach(function(phone, i) {
                $repeater.append(getPhoneRowHtml(i, phone.type, phone.number));
            });
        }
        
        if (staff.abstract_enabled) {
            $('#es-staff-abstract-enabled').prop('checked', true);
            $('.es-abstract-settings').show();
        }
        
        if (staff.abstract_types) {
            $('input[name="abstract_types[]"]').each(function() {
                $(this).prop('checked', staff.abstract_types.includes($(this).val()));
            });
        }
        
        $('#es-staff-max-size').val(staff.abstract_max_size || 10);
        $('#es-abstract-shortcode').text('[ensemble_contact_form staff_id="' + staff.id + '"]');
    }
    
    function resetForm() {
        $('#es-staff-form')[0].reset();
        $('#es-staff-id').val('');
        $('#es-staff-image').val('');
        $('#es-staff-image-preview').html('<span class="dashicons dashicons-businessperson"></span>');
        $('.es-remove-image-btn').hide();
        $('#es-staff-department').val([]);
        $('#es-phone-repeater').html(getPhoneRowHtml(0));
        $('.es-abstract-settings').hide();
        $('#es-abstract-shortcode').text('[ensemble_contact_form staff_id="0"]');
    }
    
    function getPhoneRowHtml(index, type, number) {
        type = type || 'office';
        number = number || '';
        return '<div class="es-repeater-item">' +
            '<select name="phone[' + index + '][type]" class="es-phone-type">' +
                '<option value="office"' + (type === 'office' ? ' selected' : '') + '><?php _e('Office', 'ensemble'); ?></option>' +
                '<option value="mobile"' + (type === 'mobile' ? ' selected' : '') + '><?php _e('Mobile', 'ensemble'); ?></option>' +
                '<option value="fax"' + (type === 'fax' ? ' selected' : '') + '><?php _e('Fax', 'ensemble'); ?></option>' +
            '</select>' +
            '<input type="text" name="phone[' + index + '][number]" class="es-phone-number" value="' + escapeHtml(number) + '" placeholder="+1 234 567890">' +
            '<button type="button" class="button es-repeater-remove"><span class="dashicons dashicons-minus"></span></button>' +
        '</div>';
    }
    
    $('#es-staff-form').on('submit', function(e) {
        e.preventDefault();
        
        var $btn = $('.es-modal-save');
        $btn.find('.es-btn-text').hide();
        $btn.find('.es-btn-loading').show();
        $btn.prop('disabled', true);
        
        var formData = new FormData(this);
        formData.append('action', 'es_save_staff');
        formData.append('nonce', '<?php echo wp_create_nonce('ensemble_staff_nonce'); ?>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#es-staff-modal').fadeOut(200);
                    loadStaff();
                } else {
                    alert(response.data.message || '<?php _e('Error saving', 'ensemble'); ?>');
                }
            },
            complete: function() {
                $btn.find('.es-btn-text').show();
                $btn.find('.es-btn-loading').hide();
                $btn.prop('disabled', false);
            }
        });
    });
    
    // ==========================================
    // DELETE
    // ==========================================
    
    $(document).on('click', '.es-delete-staff', function(e) {
        e.preventDefault();
        if (!confirm('<?php _e('Are you sure you want to delete this contact?', 'ensemble'); ?>')) return;
        
        var staffId = $(this).data('id');
        var $card = $(this).closest('.es-item-card');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'es_delete_staff',
                nonce: '<?php echo wp_create_nonce('ensemble_staff_nonce'); ?>',
                staff_id: staffId
            },
            beforeSend: function() { $card.css('opacity', '0.5'); },
            success: function(response) {
                if (response.success) {
                    $card.fadeOut(300, function() { $(this).remove(); loadStaff(); });
                } else {
                    $card.css('opacity', '1');
                }
            }
        });
    });
    
    // ==========================================
    // COPY STAFF
    // ==========================================
    
    $(document).on('click', '.es-copy-staff', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        if (!confirm('<?php printf(__('Copy this %s?', 'ensemble'), strtolower($staff_singular)); ?>')) return;
        
        var staffId = $(this).data('id');
        var $btn = $(this);
        var $icon = $btn.find('.dashicons');
        
        $btn.prop('disabled', true);
        $icon.removeClass('dashicons-admin-page').addClass('dashicons-update-alt es-spin');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'es_copy_staff',
                nonce: '<?php echo wp_create_nonce('ensemble_staff_nonce'); ?>',
                staff_id: staffId
            },
            success: function(response) {
                if (response.success) {
                    loadStaff();
                    // Open the copy for editing after list reloads
                    setTimeout(function() {
                        loadStaffForEdit(response.data.staff_id);
                    }, 500);
                } else {
                    alert(response.data.message || '<?php _e('Error copying contact', 'ensemble'); ?>');
                }
            },
            error: function() {
                alert('<?php _e('Error copying contact', 'ensemble'); ?>');
            },
            complete: function() {
                $btn.prop('disabled', false);
                $icon.removeClass('dashicons-update-alt es-spin').addClass('dashicons-admin-page');
            }
        });
    });
    
    // ==========================================
    // MODAL
    // ==========================================
    
    $('.es-modal-close, .es-modal-cancel, .es-modal-close-btn').on('click', function() {
        $(this).closest('.es-modal').fadeOut(200);
    });
    
    $(document).on('keyup', function(e) { if (e.key === 'Escape') $('.es-modal').fadeOut(200); });
    
    // ==========================================
    // IMAGE UPLOAD
    // ==========================================
    
    var mediaUploader;
    $(document).on('click', '.es-select-image-btn', function(e) {
        e.preventDefault();
        if (mediaUploader) { mediaUploader.open(); return; }
        
        mediaUploader = wp.media({
            title: '<?php _e('Select Image', 'ensemble'); ?>',
            button: { text: '<?php _e('Use this image', 'ensemble'); ?>' },
            multiple: false, library: { type: 'image' }
        });
        
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            var imageUrl = attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;
            $('#es-staff-image').val(attachment.id);
            $('#es-staff-image-preview').html('<img src="' + imageUrl + '" alt="">');
            $('.es-remove-image-btn').show();
        });
        mediaUploader.open();
    });
    
    $(document).on('click', '.es-remove-image-btn', function(e) {
        e.preventDefault();
        $('#es-staff-image').val('');
        $('#es-staff-image-preview').html('<span class="dashicons dashicons-businessperson"></span>');
        $(this).hide();
    });
    
    // ==========================================
    // REPEATER
    // ==========================================
    
    $(document).on('click', '.es-repeater-add', function() {
        var $repeater = $('#' + $(this).data('target'));
        $repeater.append(getPhoneRowHtml($repeater.find('.es-repeater-item').length));
    });
    
    $(document).on('click', '.es-repeater-remove', function() {
        var $repeater = $(this).closest('.es-repeater');
        if ($repeater.find('.es-repeater-item').length > 1) {
            $(this).closest('.es-repeater-item').remove();
        } else {
            $(this).siblings('input').val('');
        }
    });
    
    // ==========================================
    // ABSTRACT TOGGLE
    // ==========================================
    
    $('#es-staff-abstract-enabled').on('change', function() {
        $('.es-abstract-settings').slideToggle(200);
    });
    
    // ==========================================
    // VIEW TOGGLE
    // ==========================================
    
    $('.es-view-btn').on('click', function() {
        $('.es-view-btn').removeClass('active');
        $(this).addClass('active');
        currentView = $(this).data('view');
        // Re-render with new view
        renderStaff(allStaff);
    });
    
    // ==========================================
    // FILTERS
    // ==========================================
    
    $('#es-toggle-filters').on('click', function() {
        $('#es-filter-panel').slideToggle(200);
        $(this).toggleClass('active');
    });
    
    var searchTimeout;
    $('#es-staff-search').on('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(applyFilters, 300);
    });
    
    $('#es-filter-department, #es-filter-abstract').on('change', applyFilters);
    
    $('#es-clear-filters').on('click', function() {
        $('#es-staff-search').val('');
        $('#es-filter-department, #es-filter-abstract').val('');
        $(this).hide();
        applyFilters();
    });
    
    function applyFilters() {
        var search = $('#es-staff-search').val().toLowerCase();
        var department = $('#es-filter-department').val();
        var abstractFilter = $('#es-filter-abstract').val();
        
        $('#es-clear-filters').toggle(!!(search || department || abstractFilter));
        
        var filtered = allStaff.filter(function(person) {
            if (search && !((person.name || '').toLowerCase().includes(search) || 
                (person.position || '').toLowerCase().includes(search) ||
                (person.email || '').toLowerCase().includes(search))) return false;
            
            if (department && (!person.departments || !person.departments.some(function(d) {
                return d.id.toString() === department;
            }))) return false;
            
            if (abstractFilter === 'enabled' && !person.abstract_enabled) return false;
            if (abstractFilter === 'disabled' && person.abstract_enabled) return false;
            
            return true;
        });
        
        renderStaff(filtered);
        updateStaffCount(filtered.length);
    }
    
    // ==========================================
    // BULK ACTIONS
    // ==========================================
    
    $(document).on('change', '.es-item-checkbox', function(e) {
        e.stopPropagation();
        var id = $(this).data('id').toString();
        if ($(this).is(':checked')) {
            if (!selectedStaffIds.includes(id)) selectedStaffIds.push(id);
            $(this).closest('.es-item-card').addClass('selected');
        } else {
            selectedStaffIds = selectedStaffIds.filter(function(i) { return i !== id; });
            $(this).closest('.es-item-card').removeClass('selected');
        }
        updateSelectedCount();
    });
    
    // Click on card to edit (except checkbox)
    $(document).on('click', '.es-item-card', function(e) {
        if (!$(e.target).is('.es-item-checkbox') && !$(e.target).closest('.es-item-actions').length) {
            var staffId = $(this).data('staff-id');
            loadStaffForEdit(staffId);
        }
    });
    
    function updateSelectedCount() {
        var count = selectedStaffIds.length;
        $('#es-selected-count').text(count > 0 ? count + ' <?php _e('selected', 'ensemble'); ?>' : '');
        $('#es-bulk-actions').toggleClass('has-selection', count > 0);
    }
    
    $('#es-apply-bulk-action').on('click', function() {
        var action = $('#es-bulk-action-select').val();
        if (!action || !selectedStaffIds.length) return;
        
        if (action === 'delete') {
            if (confirm('<?php _e('Delete selected contacts?', 'ensemble'); ?>')) executeBulkDelete();
        } else if (action === 'assign_department') {
            openBulkAssignModal();
        } else if (action === 'remove_department') {
            executeBulkRemoveDepartment();
        }
    });
    
    function executeBulkDelete() {
        $.post(ajaxurl, {
            action: 'es_bulk_delete_staff',
            nonce: '<?php echo wp_create_nonce('ensemble_staff_nonce'); ?>',
            staff_ids: selectedStaffIds
        }, function() {
            selectedStaffIds = [];
            updateSelectedCount();
            loadStaff();
        });
    }
    
    function openBulkAssignModal() {
        var $select = $('#es-bulk-assign-value').empty().append('<option value=""><?php _e('Select...', 'ensemble'); ?></option>');
        departmentsData.forEach(function(d) { $select.append('<option value="' + d.id + '">' + d.name + '</option>'); });
        $('#es-bulk-assign-title').text('<?php printf(__('Assign %s', 'ensemble'), $dept_singular); ?>');
        $('#es-bulk-assign-modal').fadeIn(200);
    }
    
    $('#es-bulk-assign-confirm').on('click', function() {
        var termId = $('#es-bulk-assign-value').val();
        if (!termId) return;
        $.post(ajaxurl, {
            action: 'es_bulk_assign_staff_department',
            nonce: '<?php echo wp_create_nonce('ensemble_staff_nonce'); ?>',
            staff_ids: selectedStaffIds,
            term_id: termId
        }, function() {
            $('#es-bulk-assign-modal').fadeOut(200);
            selectedStaffIds = [];
            updateSelectedCount();
            loadStaff();
        });
    });
    
    function executeBulkRemoveDepartment() {
        $.post(ajaxurl, {
            action: 'es_bulk_remove_staff_department',
            nonce: '<?php echo wp_create_nonce('ensemble_staff_nonce'); ?>',
            staff_ids: selectedStaffIds
        }, function() {
            selectedStaffIds = [];
            updateSelectedCount();
            loadStaff();
        });
    }
    
});
</script>
