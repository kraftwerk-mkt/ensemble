<?php
/**
 * Field Builder Page
 * Beautiful UI for managing ACF fields
 *
 * @package Ensemble
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Handle template creation
if (isset($_POST['es_create_from_template']) && check_admin_referer('ensemble_create_template')) {
    $template_id = sanitize_text_field($_POST['template_id']);
    
    // Check if this specific template is available
    if (!ES_Field_Builder::is_template_available($template_id)) {
        echo '<div class="notice notice-error is-dismissible"><p>' . __('This template requires the Pro version.', 'ensemble') . '</p></div>';
    } else {
        $category_ids = isset($_POST['category_ids']) ? array_map('intval', $_POST['category_ids']) : array();
        
        $group_key = ES_Field_Builder::create_from_template($template_id);
        
        if ($group_key) {
            // Assign to categories if any selected
            if (!empty($category_ids)) {
                ES_Field_Builder::assign_to_categories($group_key, $category_ids);
                echo '<div class="notice notice-success is-dismissible"><p>' . __('Field group created and assigned to categories!', 'ensemble') . '</p></div>';
            } else {
                echo '<div class="notice notice-success is-dismissible"><p>' . __('Field group created successfully!', 'ensemble') . '</p></div>';
            }
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>' . __('Failed to create field group.', 'ensemble') . '</p></div>';
        }
    }
}

// Handle custom field group update
if (isset($_POST['es_update_custom_group']) && check_admin_referer('ensemble_edit_custom_group')) {
    $group_key = sanitize_text_field($_POST['group_key']);
    $group_title = sanitize_text_field($_POST['group_title']);
    $fields_data = isset($_POST['fields']) ? $_POST['fields'] : array();
    
    if (empty($group_key)) {
        echo '<div class="notice notice-error is-dismissible"><p>' . __('Group key is required.', 'ensemble') . '</p></div>';
    } elseif (empty($group_title)) {
        echo '<div class="notice notice-error is-dismissible"><p>' . __('Group name is required.', 'ensemble') . '</p></div>';
    } elseif (empty($fields_data)) {
        echo '<div class="notice notice-error is-dismissible"><p>' . __('At least one field is required.', 'ensemble') . '</p></div>';
    } else {
        $success = ES_Field_Builder::update_custom_group($group_key, $group_title, $fields_data);
        
        if ($success) {
            echo '<div class="notice notice-success is-dismissible"><p>' . __('Field group updated successfully!', 'ensemble') . '</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>' . __('Failed to update field group.', 'ensemble') . '</p></div>';
        }
    }
}

// Handle custom field group creation
if (isset($_POST['es_create_custom_group']) && check_admin_referer('ensemble_create_custom_group')) {
    // Check fieldset limit
    $can_create = ES_Field_Builder::can_create_fieldset();
    
    if ($can_create !== true) {
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($can_create['message']) . '</p></div>';
    } else {
        $group_title = sanitize_text_field($_POST['group_title']);
        $fields_data = isset($_POST['fields']) ? $_POST['fields'] : array();
        
        if (empty($group_title)) {
            echo '<div class="notice notice-error is-dismissible"><p>' . __('Group name is required.', 'ensemble') . '</p></div>';
        } elseif (empty($fields_data)) {
            echo '<div class="notice notice-error is-dismissible"><p>' . __('At least one field is required.', 'ensemble') . '</p></div>';
        } else {
            // Check field limit
            $is_pro = function_exists('ensemble_is_pro') && ensemble_is_pro();
            if (!$is_pro && count($fields_data) > ES_Field_Builder::FREE_MAX_FIELDS_PER_SET) {
                echo '<div class="notice notice-error is-dismissible"><p>' . sprintf(__('Free-Version: Max %d Felder pro Fieldset.', 'ensemble'), ES_Field_Builder::FREE_MAX_FIELDS_PER_SET) . '</p></div>';
            } else {
                $group_key = ES_Field_Builder::create_custom_group($group_title, $fields_data);
                
                if ($group_key) {
                    echo '<div class="notice notice-success is-dismissible"><p>' . __('Custom field group created successfully!', 'ensemble') . '</p></div>';
                } else {
                    echo '<div class="notice notice-error is-dismissible"><p>' . __('Failed to create custom field group.', 'ensemble') . '</p></div>';
                }
            }
        }
    }
}

// Handle field group deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['group']) && check_admin_referer('delete_field_group_' . $_GET['group'])) {
    $group_key = sanitize_text_field($_GET['group']);
    
    if (ES_Field_Builder::delete_field_group($group_key)) {
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Field group deleted successfully!', 'ensemble') . '</p></div>';
    } else {
        echo '<div class="notice notice-error is-dismissible"><p>' . __('Failed to delete field group.', 'ensemble') . '</p></div>';
    }
}

$field_groups = ES_Field_Builder::get_field_groups();
$templates = ES_Field_Builder::get_templates();

// Get categories for assignment
$wizard = new ES_Wizard();
$categories = $wizard->get_categories();

// Pro status and limits
$is_pro = function_exists('ensemble_is_pro') && ensemble_is_pro();
$limits_info = ES_Field_Builder::get_limits_info();
$can_create = ES_Field_Builder::can_create_fieldset();
$templates_available = ES_Field_Builder::templates_available();
?>

<div class="wrap es-field-builder-wrap">
    <h1>
        <?php _e('Field Builder', 'ensemble'); ?>
       
    </h1>
    
    <?php if (!$is_pro): ?>
    <!-- Free Version Limits Info -->
    <div class="es-limits-banner" style="background: var(--es-surface-secondary, #383838); border: 1px solid var(--es-border, #404040); border-radius: 8px; padding: 15px 20px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <strong style="color: var(--es-text, #e0e0e0);"><?php _e('Free Version', 'ensemble'); ?></strong>
            <span style="color: var(--es-text-secondary, #a0a0a0); margin-left: 10px;">
                <?php printf(
                    __('Fieldsets: %d/%d | Felder pro Set: max %d', 'ensemble'),
                    $limits_info['fieldsets']['current'],
                    $limits_info['fieldsets']['limit'],
                    $limits_info['fields_per_set']['limit']
                ); ?>
            </span>
        </div>
        <a href="<?php echo admin_url('admin.php?page=ensemble-settings&tab=license'); ?>" class="button" style="background: linear-gradient(135deg, #f59e0b, #d97706); border: none; color: #fff;">
            <?php _e('Upgrade for unlimited', 'ensemble'); ?>
        </a>
    </div>
    <?php endif; ?>
    
    <div class="es-field-builder-container">
        <div class="es-field-builder-tabs"> 
        <?php if ($templates_available): ?>
        <button type="button" class="page-title-action es-open-template-modal button-primary">
            <?php _e('+ From Template', 'ensemble'); ?>
        </button>
        <?php else: ?>
        <button type="button" class="page-title-action button-secondary" disabled title="<?php esc_attr_e('Templates require Pro', 'ensemble'); ?>" style="opacity: 0.6; cursor: not-allowed;">
            <?php _e('+ From Template', 'ensemble'); ?>
            <span class="es-pro-badge" style="margin-left: 5px; font-size: 9px; padding: 2px 5px;">PRO</span>
        </button>
        <?php endif; ?>
        
        <?php if ($can_create === true): ?>
        <button type="button" class="page-title-action es-open-custom-modal button-primary">
            <?php _e('+ Custom Field Group', 'ensemble'); ?>
        </button>
        <?php else: ?>
        <button type="button" class="page-title-action button-secondary" disabled title="<?php echo esc_attr($can_create['message']); ?>" style="opacity: 0.6; cursor: not-allowed;">
            <?php _e('+ Custom Field Group', 'ensemble'); ?>
        </button>
        <span style="color: #f59e0b; font-size: 12px; margin-left: 10px;">
            <?php echo esc_html($can_create['message']); ?>
        </span>
        <?php endif; ?>
        </div>
        <div class="es-field-builder-section">
    <p class="description" style="margin-bottom: 30px;">
        <?php _e('Manage your custom fields for events. Add pre-built templates or create your own field groups.', 'ensemble'); ?>
    </p>
    
    <?php if (empty($field_groups)): ?>
    
    <!-- Empty State -->
    <div class="es-empty-state">
        <div class="es-empty-state-icon"><?php ES_Icons::icon('settings'); ?></div>
        <h2><?php _e('No Custom Fields Yet', 'ensemble'); ?></h2>
        <p><?php _e('Get started by adding a field template or creating a custom field group.', 'ensemble'); ?></p>
        
        <div class="es-empty-state-actions">
            <button type="button" class="button button-primary button-hero es-open-template-modal">
                <?php _e('Browse Templates', 'ensemble'); ?>
            </button>
            <button type="button" class="button button-secondary button-hero es-open-custom-modal">
                <?php _e('Create Custom Fields', 'ensemble'); ?>
            </button>
        </div>
        
        <!-- Template Preview Cards -->
        <div class="es-template-preview-cards">
            <h3><?php _e('Popular Templates', 'ensemble'); ?></h3>
            <div class="es-template-grid">
                <?php foreach (array_slice($templates, 0, 3) as $template): ?>
                <div class="es-template-preview-card">
                    <div class="es-template-preview-icon"><?php ES_Icons::icon($template['icon']); ?></div>
                    <h4><?php echo esc_html($template['title']); ?></h4>
                    <p><?php echo esc_html($template['description']); ?></p>
                    <small><?php printf(__('%d fields', 'ensemble'), count($template['fields'])); ?></small>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        </div>
        </div>
    </div>
    
    <?php else: ?>
    
    <!-- Field Groups Grid -->
    <div class="es-field-groups-grid">
        
        <?php foreach ($field_groups as $group): ?>
        <div class="es-field-group-card">
            <div class="es-field-group-icon">
                <?php ES_Icons::icon('duplicate'); ?>
            </div>
            
            <div class="es-field-group-body">
                <div class="es-field-group-info">
                    <h3 class="es-field-group-title"><?php echo esc_html($group['title']); ?></h3>
                    
                    <div class="es-field-group-meta">
                        <div class="es-field-group-meta-item">
                            <span class="dashicons dashicons-admin-settings"></span>
                            <span><?php echo $group['field_count']; ?> <?php echo _n('field', 'fields', $group['field_count'], 'ensemble'); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="es-field-group-actions">
                    <button type="button" 
                            class="button es-edit-group es-edit-group-modal" 
                            data-group-id="<?php echo esc_attr($group['ID']); ?>"
                            data-group-key="<?php echo esc_attr($group['key']); ?>"
                            data-group-title="<?php echo esc_attr($group['title']); ?>">
                        <?php _e('Edit', 'ensemble'); ?>
                    </button>
                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=ensemble-field-builder&action=delete&group=' . $group['key']), 'delete_field_group_' . $group['key']); ?>" 
                       class="button es-delete-group"
                       onclick="return confirm('<?php _e('Are you sure?', 'ensemble'); ?>')">
                        <?php _e('Delete', 'ensemble'); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        
    </div>
    
    <?php endif; ?>
    
</div>

<!-- Template Modal -->
<div id="es-template-modal" class="es-modal" style="display: none;">
    <div class="es-modal-backdrop"></div>
    <div class="es-modal-content">
        <div class="es-modal-header">
            <h2><?php _e('Choose Field Template', 'ensemble'); ?></h2>
            <button type="button" class="es-modal-close">&times;</button>
        </div>
        
        <div class="es-modal-body">
            <p class="description">
                <?php _e('Select a pre-built field template to quickly add common field sets to your events.', 'ensemble'); ?>
            </p>
            
            <div class="es-template-gallery">
                <?php 
                $is_pro = function_exists('ensemble_is_pro') && ensemble_is_pro();
                foreach ($templates as $template): 
                    $requires_pro = !empty($template['requires_pro']);
                    $is_available = !$requires_pro || $is_pro;
                ?>
                <div class="es-template-card <?php echo !$is_available ? 'es-template-locked' : ''; ?>" data-template-id="<?php echo esc_attr($template['id']); ?>">
                    <?php if ($requires_pro): ?>
                    <span class="es-template-badge es-badge-pro"><?php _e('Pro', 'ensemble'); ?></span>
                    <?php else: ?>
                    <span class="es-template-badge es-badge-free"><?php _e('Free', 'ensemble'); ?></span>
                    <?php endif; ?>
                    
                    <div class="es-template-icon"><?php ES_Icons::icon($template['icon']); ?></div>
                    <h3><?php echo esc_html($template['title']); ?></h3>
                    <p><?php echo esc_html($template['description']); ?></p>
                    
                    <div class="es-template-fields-preview">
                        <strong><?php _e('Includes:', 'ensemble'); ?></strong>
                        <ul>
                            <?php foreach (array_slice($template['fields'], 0, 4) as $field): ?>
                            <li>
                                <?php echo ES_Field_Builder::get_field_type_icon($field['type']); ?>
                                <?php echo esc_html($field['label']); ?>
                                <small>(<?php echo esc_html($field['type']); ?>)</small>
                            </li>
                            <?php endforeach; ?>
                            <?php if (count($template['fields']) > 4): ?>
                            <li><small><?php printf(__('+ %d more', 'ensemble'), count($template['fields']) - 4); ?></small></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    
                    <?php if ($is_available): ?>
                    <form method="post" action="">
                        <?php wp_nonce_field('ensemble_create_template'); ?>
                        <input type="hidden" name="template_id" value="<?php echo esc_attr($template['id']); ?>">
                        
                        <?php if (!empty($categories)): ?>
                        <div style="margin-bottom: 15px; padding: 15px; background: #f8f9fa; border-radius: 6px;">
                            <label style="font-weight: 600; font-size: 13px; margin-bottom: 10px; display: block;">
                                <?php _e('Assign to Categories:', 'ensemble'); ?>
                            </label>
                            <div class="es-pill-group">
                                <?php foreach ($categories as $category): ?>
                                <label class="es-pill">
                                    <input type="checkbox" 
                                           name="category_ids[]" 
                                           value="<?php echo esc_attr($category['id']); ?>">
                                    <span><?php echo esc_html($category['name']); ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                            <p style="margin: 8px 0 0 0; font-size: 11px; color: #666;">
                                <?php _e('Leave empty to assign later in Settings', 'ensemble'); ?>
                            </p>
                        </div>
                        <?php endif; ?>
                        
                        <button type="submit" name="es_create_from_template" class="button button-primary button-large">
                            <?php _e('Add to Events', 'ensemble'); ?> →
                        </button>
                    </form>
                    <?php else: ?>
                    <div class="es-template-pro-notice">
                        <p><?php _e('Upgrade to Pro to use this template', 'ensemble'); ?></p>
                        <a href="<?php echo admin_url('admin.php?page=ensemble-settings&tab=license'); ?>" class="button">
                            <?php _e('Upgrade', 'ensemble'); ?>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Custom Field Group Modal -->
<div id="es-custom-group-modal" class="es-modal" style="display: none;">
    <div class="es-modal-backdrop"></div>
    <div class="es-modal-content es-modal-content-narrow">
        <div class="es-modal-header">
            <h2><?php _e('Create Custom Field Group', 'ensemble'); ?></h2>
            <button type="button" class="es-modal-close">&times;</button>
        </div>
        
        <div class="es-modal-body">
            <form method="post" action="" id="es-custom-group-form">
                <?php wp_nonce_field('ensemble_create_custom_group'); ?>
                
                <div class="es-form-group">
                    <label for="group_title">
                        <?php _e('Field Group Name', 'ensemble'); ?> <span class="required">*</span>
                    </label>
                    <input type="text" 
                           name="group_title" 
                           id="group_title" 
                           class="regular-text" 
                           placeholder="<?php _e('e.g., Festival Information', 'ensemble'); ?>"
                           required>
                    <p class="description">
                        <?php _e('Give your field group a descriptive name', 'ensemble'); ?>
                    </p>
                </div>
                
                <div class="es-form-group">
                    <label><?php _e('Fields', 'ensemble'); ?> <span class="required">*</span></label>
                    <div id="es-fields-container">
                        <!-- Fields will be added here dynamically -->
                    </div>
                    <button type="button" class="button button-secondary" id="es-add-field-btn">
                        ➕ <?php _e('Add Field', 'ensemble'); ?>
                    </button>
                </div>
                
                <div class="es-modal-actions">
                    <button type="submit" name="es_create_custom_group" class="button button-primary button-large">
                        <?php _e('Create Field Group', 'ensemble'); ?>
                    </button>
                    <button type="button" class="button button-secondary button-large es-modal-close">
                        <?php _e('Cancel', 'ensemble'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Field Group Modal -->
<div id="es-edit-group-modal" class="es-modal" style="display: none;">
    <div class="es-modal-backdrop"></div>
    <div class="es-modal-content es-modal-content-narrow">
        <div class="es-modal-header">
            <h2><?php _e('Edit Field Group', 'ensemble'); ?></h2>
            <button type="button" class="es-modal-close">&times;</button>
        </div>
        
        <div class="es-modal-body">
            <form method="post" action="" id="es-edit-group-form">
                <?php wp_nonce_field('ensemble_edit_custom_group'); ?>
                <input type="hidden" name="group_key" id="edit_group_key" value="">
                
                <div class="es-form-group">
                    <label for="edit_group_title">
                        <?php _e('Field Group Name', 'ensemble'); ?> <span class="required">*</span>
                    </label>
                    <input type="text" 
                           name="group_title" 
                           id="edit_group_title" 
                           class="regular-text" 
                           placeholder="<?php _e('e.g., Festival Information', 'ensemble'); ?>"
                           required>
                    <p class="description">
                        <?php _e('Give your field group a descriptive name', 'ensemble'); ?>
                    </p>
                </div>
                
                <div class="es-form-group">
                    <label><?php _e('Fields', 'ensemble'); ?> <span class="required">*</span></label>
                    <div id="es-edit-fields-container">
                        <!-- Fields will be loaded here -->
                    </div>
                    <button type="button" class="button button-secondary" id="es-add-edit-field-btn">
                        ➕ <?php _e('Add Field', 'ensemble'); ?>
                    </button>
                </div>
                
                <div class="es-modal-actions">
                    <button type="submit" name="es_update_custom_group" class="button button-primary button-large">
                        <?php _e('Save Changes', 'ensemble'); ?>
                    </button>
                    <button type="button" class="button button-secondary button-large es-modal-close">
                        <?php _e('Cancel', 'ensemble'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    let fieldCounter = 0;
    
    // Field type options
    const fieldTypes = {
        'text': '<?php _e('Text', 'ensemble'); ?>',
        'textarea': '<?php _e('Textarea', 'ensemble'); ?>',
        'number': '<?php _e('Number', 'ensemble'); ?>',
        'email': '<?php _e('Email', 'ensemble'); ?>',
        'url': '<?php _e('URL', 'ensemble'); ?>',
        'date_picker': '<?php _e('Date Picker', 'ensemble'); ?>',
        'time_picker': '<?php _e('Time Picker', 'ensemble'); ?>',
        'color_picker': '<?php _e('Color Picker', 'ensemble'); ?>',
        'image': '<?php _e('Image', 'ensemble'); ?>',
        'file': '<?php _e('File', 'ensemble'); ?>',
        'select': '<?php _e('Select', 'ensemble'); ?>',
        'checkbox': '<?php _e('Checkbox', 'ensemble'); ?>',
        'radio': '<?php _e('Radio', 'ensemble'); ?>',
        'true_false': '<?php _e('True/False', 'ensemble'); ?>',
        'wysiwyg': '<?php _e('WYSIWYG Editor', 'ensemble'); ?>'
    };
    
    // Add field function
    function addField() {
        fieldCounter++;
        
        let fieldTypeOptions = '';
        $.each(fieldTypes, function(value, label) {
            fieldTypeOptions += `<option value="${value}">${label}</option>`;
        });
        
        const fieldHTML = `
            <div class="es-field-row" data-field-index="${fieldCounter}">
                <div class="es-field-row-header">
                    <span class="es-field-number">${fieldCounter}</span>
                    <button type="button" class="es-remove-field-btn" title="<?php _e('Remove field', 'ensemble'); ?>">×</button>
                </div>
                <div class="es-field-row-content">
                    <div class="es-field-input-group">
                        <label><?php _e('Field Label', 'ensemble'); ?></label>
                        <input type="text" 
                               name="fields[${fieldCounter}][label]" 
                               placeholder="<?php _e('e.g., Stage Name', 'ensemble'); ?>"
                               class="field-label-input"
                               required>
                    </div>
                    <div class="es-field-input-group">
                        <label><?php _e('Field Name', 'ensemble'); ?></label>
                        <input type="text" 
                               name="fields[${fieldCounter}][name]" 
                               placeholder="<?php _e('e.g., stage_name', 'ensemble'); ?>"
                               class="field-name-input"
                               pattern="[a-z0-9_]+"
                               title="<?php _e('Lowercase letters, numbers and underscores only', 'ensemble'); ?>"
                               required>
                        <small class="description"><?php _e('Lowercase, no spaces', 'ensemble'); ?></small>
                    </div>
                    <div class="es-field-input-group">
                        <label><?php _e('Field Type', 'ensemble'); ?></label>
                        <select name="fields[${fieldCounter}][type]" required>
                            ${fieldTypeOptions}
                        </select>
                    </div>
                </div>
            </div>
        `;
        
        $('#es-fields-container').append(fieldHTML);
    }
    
    // Auto-generate field name from label
    $(document).on('input', '.field-label-input', function() {
        const $row = $(this).closest('.es-field-row');
        const $nameInput = $row.find('.field-name-input');
        
        // Only auto-fill if name field is empty
        if ($nameInput.val() === '') {
            const label = $(this).val();
            const name = label
                .toLowerCase()
                .replace(/[^a-z0-9\s]/g, '')
                .replace(/\s+/g, '_');
            $nameInput.val(name);
        }
    });
    
    // Add initial field
    addField();
    
    // Add field button
    $('#es-add-field-btn').on('click', function() {
        addField();
    });
    
    // Remove field button
    $(document).on('click', '.es-remove-field-btn', function() {
        $(this).closest('.es-field-row').fadeOut(200, function() {
            $(this).remove();
            
            // Renumber remaining fields
            $('.es-field-row').each(function(index) {
                $(this).find('.es-field-number').text(index + 1);
            });
        });
    });
    
    // Open custom modal
    $('.es-open-custom-modal').on('click', function() {
        $('#es-custom-group-modal').fadeIn(200);
        
        // Reset form
        $('#es-custom-group-form')[0].reset();
        $('#es-fields-container').empty();
        fieldCounter = 0;
        addField();
    });
    
    // Open template modal
    $('.es-open-template-modal').on('click', function() {
        $('#es-template-modal').fadeIn(200);
    });
    
    // Open edit field group modal
    $('.es-edit-group-modal').on('click', function() {
        const groupId = $(this).data('group-id');
        const groupKey = $(this).data('group-key');
        const groupTitle = $(this).data('group-title');
        
        // Set form values
        $('#edit_group_key').val(groupKey);
        $('#edit_group_title').val(groupTitle);
        
        // Clear and load fields
        $('#es-edit-fields-container').html('<div style="text-align: center; padding: 20px;"><span class="spinner is-active"></span></div>');
        
        // Load fields via AJAX
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'es_get_field_group_fields',
                group_key: groupKey,
                nonce: '<?php echo wp_create_nonce('es_get_fields'); ?>'
            },
            success: function(response) {
                if (response.success && response.data.fields) {
                    $('#es-edit-fields-container').empty();
                    editFieldCounter = 0;
                    
                    // Add each existing field
                    response.data.fields.forEach(function(field) {
                        addEditField(field);
                    });
                    
                    // Show modal
                    $('#es-edit-group-modal').fadeIn(200);
                } else {
                    alert('<?php _e('Failed to load fields', 'ensemble'); ?>');
                }
            },
            error: function() {
                alert('<?php _e('Failed to load fields', 'ensemble'); ?>');
            }
        });
    });
    
    // Add field to edit form
    let editFieldCounter = 0;
    function addEditField(fieldData = null) {
        editFieldCounter++;
        
        let fieldTypeOptions = '';
        $.each(fieldTypes, function(value, label) {
            const selected = fieldData && fieldData.type === value ? 'selected' : '';
            fieldTypeOptions += `<option value="${value}" ${selected}>${label}</option>`;
        });
        
        const labelValue = fieldData ? fieldData.label : '';
        const nameValue = fieldData ? fieldData.name : '';
        const fieldKey = fieldData ? fieldData.key : '';
        
        const fieldHTML = `
            <div class="es-field-row" data-field-index="${editFieldCounter}">
                <div class="es-field-row-header">
                    <span class="es-field-number">${editFieldCounter}</span>
                    <button type="button" class="es-remove-field-btn" title="<?php _e('Remove field', 'ensemble'); ?>">×</button>
                </div>
                <div class="es-field-row-content">
                    <input type="hidden" name="fields[${editFieldCounter}][key]" value="${fieldKey}">
                    <div class="es-field-input-group">
                        <label><?php _e('Field Label', 'ensemble'); ?></label>
                        <input type="text" 
                               name="fields[${editFieldCounter}][label]" 
                               value="${labelValue}"
                               placeholder="<?php _e('e.g., Stage Name', 'ensemble'); ?>"
                               class="field-label-input"
                               required>
                    </div>
                    <div class="es-field-input-group">
                        <label><?php _e('Field Name', 'ensemble'); ?></label>
                        <input type="text" 
                               name="fields[${editFieldCounter}][name]" 
                               value="${nameValue}"
                               placeholder="<?php _e('e.g., stage_name', 'ensemble'); ?>"
                               class="field-name-input"
                               pattern="[a-z0-9_]+"
                               title="<?php _e('Lowercase letters, numbers and underscores only', 'ensemble'); ?>"
                               required>
                        <small class="description"><?php _e('Lowercase, no spaces', 'ensemble'); ?></small>
                    </div>
                    <div class="es-field-input-group">
                        <label><?php _e('Field Type', 'ensemble'); ?></label>
                        <select name="fields[${editFieldCounter}][type]" required>
                            ${fieldTypeOptions}
                        </select>
                    </div>
                </div>
            </div>
        `;
        
        $('#es-edit-fields-container').append(fieldHTML);
    }
    
    // Add field button for edit
    $('#es-add-edit-field-btn').on('click', function() {
        addEditField();
    });
    
    // Close modals
    $('.es-modal-close, .es-modal-backdrop').on('click', function() {
        $('.es-modal').fadeOut(200);
    });
    
    // Prevent closing when clicking inside modal content
    $('.es-modal-content').on('click', function(e) {
        e.stopPropagation();
    });
    
    // ESC key to close modals
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            $('.es-modal').fadeOut(200);
        }
    });
});
</script>
