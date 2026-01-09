<?php
/**
 * Field Builder - ACF UI Wrapper
 * Beautiful interface for managing ACF fields without leaving Ensemble
 *
 * @package Ensemble
 * @since 1.7.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class ES_Field_Builder {
    
    /**
     * Free version limits
     */
    const FREE_MAX_FIELDSETS = 3;
    const FREE_MAX_FIELDS_PER_SET = 10;
    
    /**
     * Check if user can create more fieldsets
     * 
     * @return bool|array True if allowed, or array with limit info
     */
    public static function can_create_fieldset() {
        if (function_exists('ensemble_is_pro') && ensemble_is_pro()) {
            return true;
        }
        
        $current_count = count(self::get_field_groups());
        
        if ($current_count >= self::FREE_MAX_FIELDSETS) {
            return array(
                'allowed' => false,
                'current' => $current_count,
                'limit' => self::FREE_MAX_FIELDSETS,
                'message' => sprintf(
                    __('Free version: Max %d fieldsets. Upgrade to Pro for unlimited fieldsets.', 'ensemble'),
                    self::FREE_MAX_FIELDSETS
                ),
            );
        }
        
        return true;
    }
    
    /**
     * Check if user can add more fields to a fieldset
     * 
     * @param string $group_key Field group key
     * @return bool|array True if allowed, or array with limit info
     */
    public static function can_add_field($group_key) {
        if (function_exists('ensemble_is_pro') && ensemble_is_pro()) {
            return true;
        }
        
        $group = self::get_field_group($group_key);
        if (!$group) {
            return true;
        }
        
        $current_count = count($group['fields']);
        
        if ($current_count >= self::FREE_MAX_FIELDS_PER_SET) {
            return array(
                'allowed' => false,
                'current' => $current_count,
                'limit' => self::FREE_MAX_FIELDS_PER_SET,
                'message' => sprintf(
                    __('Free version: Max %d fields per fieldset. Upgrade to Pro for unlimited fields.', 'ensemble'),
                    self::FREE_MAX_FIELDS_PER_SET
                ),
            );
        }
        
        return true;
    }
    
    /**
     * Check if templates are available
     * Free templates are always available, Pro templates require Pro license
     * 
     * @return bool True if any templates are available (always true now)
     */
    public static function templates_available() {
        // Templates are always available - individual templates may require Pro
        return true;
    }
    
    /**
     * Check if a specific template is available
     * 
     * @param string $template_id Template ID
     * @return bool
     */
    public static function is_template_available($template_id) {
        $template = self::get_template($template_id);
        
        if (!$template) {
            return false;
        }
        
        // Check if template requires Pro
        if (!empty($template['requires_pro'])) {
            return function_exists('ensemble_is_pro') && ensemble_is_pro();
        }
        
        // Free template - always available
        return true;
    }
    
    /**
     * Get limits info for display
     * 
     * @return array
     */
    public static function get_limits_info() {
        $is_pro = function_exists('ensemble_is_pro') && ensemble_is_pro();
        $groups = self::get_field_groups();
        
        return array(
            'is_pro' => $is_pro,
            'fieldsets' => array(
                'current' => count($groups),
                'limit' => $is_pro ? '∞' : self::FREE_MAX_FIELDSETS,
                'remaining' => $is_pro ? null : max(0, self::FREE_MAX_FIELDSETS - count($groups)),
            ),
            'fields_per_set' => array(
                'limit' => $is_pro ? '∞' : self::FREE_MAX_FIELDS_PER_SET,
            ),
            'templates' => true, // Templates always available, individual ones may require Pro
        );
    }
    
    /**
     * Get all field groups (excluding Ensemble core groups)
     * 
     * @return array Field groups
     */
    public static function get_field_groups() {
        if (!function_exists('acf_get_field_groups')) {
            return array();
        }
        
        $all_groups = acf_get_field_groups();
        $custom_groups = array();
        
        foreach ($all_groups as $group) {
            // Exclude Ensemble core groups
            if (!in_array($group['key'], array('group_ensemble_event', 'group_ensemble_artist', 'group_ensemble_location'))) {
                
                // Get fields count
                $fields = acf_get_fields($group['key']);
                $group['field_count'] = is_array($fields) ? count($fields) : 0;
                $group['fields'] = $fields ?: array();
                
                $custom_groups[] = $group;
            }
        }
        
        return $custom_groups;
    }
    
    /**
     * Get field group by key
     * 
     * @param string $group_key Field group key
     * @return array|false Field group or false
     */
    public static function get_field_group($group_key) {
        if (!function_exists('acf_get_field_group')) {
            return false;
        }
        
        $group = acf_get_field_group($group_key);
        
        if ($group) {
            $group['fields'] = acf_get_fields($group_key) ?: array();
        }
        
        return $group;
    }
    
    /**
     * Create new field group from template
     * 
     * @param string $template_id Template identifier
     * @return string|false Field group key or false on failure
     */
    public static function create_from_template($template_id) {
        // Check if this specific template is available
        if (!self::is_template_available($template_id)) {
            return false;
        }
        
        $template = self::get_template($template_id);
        
        if (!$template) {
            return false;
        }
        
        // Generate unique key
        $group_key = 'group_' . uniqid();
        
        // Create field group
        $group_config = array(
            'key' => $group_key,
            'title' => $template['title'],
            'fields' => array(),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => get_option('ensemble_post_type', 'post'),
                    ),
                ),
            ),
            'menu_order' => 0,
            'position' => 'normal',
            'style' => 'default',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
            'hide_on_screen' => '',
        );
        
        // Add fields from template
        foreach ($template['fields'] as $index => $field_template) {
            $field_key = 'field_' . uniqid();
            
            $field = array(
                'key' => $field_key,
                'label' => $field_template['label'],
                'name' => $field_template['name'],
                'type' => $field_template['type'],
                'instructions' => isset($field_template['instructions']) ? $field_template['instructions'] : '',
                'required' => isset($field_template['required']) ? $field_template['required'] : 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => isset($field_template['default_value']) ? $field_template['default_value'] : '',
            );
            
            // Add type-specific settings
            switch ($field_template['type']) {
                case 'number':
                    $field['min'] = isset($field_template['min']) ? $field_template['min'] : '';
                    $field['max'] = isset($field_template['max']) ? $field_template['max'] : '';
                    $field['step'] = isset($field_template['step']) ? $field_template['step'] : '';
                    break;
                    
                case 'textarea':
                    $field['rows'] = isset($field_template['rows']) ? $field_template['rows'] : 4;
                    break;
                    
                case 'select':
                    $field['choices'] = isset($field_template['choices']) ? $field_template['choices'] : array();
                    $field['allow_null'] = isset($field_template['allow_null']) ? $field_template['allow_null'] : 0;
                    $field['multiple'] = isset($field_template['multiple']) ? $field_template['multiple'] : 0;
                    break;
                    
                case 'image':
                    $field['return_format'] = 'id';
                    $field['preview_size'] = 'medium';
                    $field['library'] = 'all';
                    break;
            }
            
            $group_config['fields'][] = $field;
        }
        
        // Register field group with ACF
        if (function_exists('acf_add_local_field_group')) {
            acf_add_local_field_group($group_config);
            
            // Also save to database for persistence
            // We need to create it as a post in the database
            $post_id = wp_insert_post(array(
                'post_title' => $template['title'],
                'post_type' => 'acf-field-group',
                'post_status' => 'publish',
            ));
            
            if ($post_id && !is_wp_error($post_id)) {
                // Save field group data
                update_post_meta($post_id, 'rule', $group_config['location']);
                update_post_meta($post_id, 'position', $group_config['position']);
                update_post_meta($post_id, 'style', $group_config['style']);
                
                // Save each field
                foreach ($group_config['fields'] as $field) {
                    $field_post_id = wp_insert_post(array(
                        'post_title' => $field['label'],
                        'post_type' => 'acf-field',
                        'post_status' => 'publish',
                        'post_parent' => $post_id,
                    ));
                    
                    if ($field_post_id && !is_wp_error($field_post_id)) {
                        // Save field settings
                        foreach ($field as $key => $value) {
                            update_post_meta($field_post_id, $key, $value);
                        }
                    }
                }
            }
            
            return $group_key;
        }
        
        return false;
    }
    
    /**
     * Create custom field group from user input
     * 
     * @param string $title Group title
     * @param array $fields_data Field definitions
     * @return string|false Field group key or false on failure
     */
    public static function create_custom_group($title, $fields_data) {
        if (empty($title) || empty($fields_data)) {
            return false;
        }
        
        // Generate unique key
        $group_key = 'group_' . uniqid();
        
        // Create field group config
        $group_config = array(
            'key' => $group_key,
            'title' => $title,
            'fields' => array(),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => get_option('ensemble_post_type', 'post'),
                    ),
                ),
            ),
            'menu_order' => 0,
            'position' => 'normal',
            'style' => 'default',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
            'hide_on_screen' => '',
        );
        
        // Process fields
        foreach ($fields_data as $field_data) {
            if (empty($field_data['label']) || empty($field_data['name']) || empty($field_data['type'])) {
                continue;
            }
            
            $field_key = 'field_' . uniqid();
            
            $field = array(
                'key' => $field_key,
                'label' => sanitize_text_field($field_data['label']),
                'name' => sanitize_key($field_data['name']),
                'type' => sanitize_text_field($field_data['type']),
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => '',
            );
            
            // Add type-specific default settings
            switch ($field_data['type']) {
                case 'textarea':
                    $field['rows'] = 4;
                    break;
                    
                case 'wysiwyg':
                    $field['tabs'] = 'all';
                    $field['toolbar'] = 'full';
                    $field['media_upload'] = 1;
                    break;
                    
                case 'image':
                    $field['return_format'] = 'id';
                    $field['preview_size'] = 'medium';
                    $field['library'] = 'all';
                    break;
                    
                case 'file':
                    $field['return_format'] = 'id';
                    $field['library'] = 'all';
                    break;
                    
                case 'select':
                    $field['choices'] = array();
                    $field['allow_null'] = 0;
                    $field['multiple'] = 0;
                    break;
                    
                case 'true_false':
                    $field['message'] = '';
                    $field['default_value'] = 0;
                    break;
            }
            
            $group_config['fields'][] = $field;
        }
        
        // Must have at least one field
        if (empty($group_config['fields'])) {
            return false;
        }
        
        // Register field group with ACF
        if (function_exists('acf_add_local_field_group')) {
            acf_add_local_field_group($group_config);
            
            // Also save to database for persistence
            $post_id = wp_insert_post(array(
                'post_title' => $title,
                'post_type' => 'acf-field-group',
                'post_status' => 'publish',
            ));
            
            if ($post_id && !is_wp_error($post_id)) {
                // Save field group data
                update_post_meta($post_id, 'rule', $group_config['location']);
                update_post_meta($post_id, 'position', $group_config['position']);
                update_post_meta($post_id, 'style', $group_config['style']);
                
                // Save each field
                foreach ($group_config['fields'] as $field) {
                    $field_post_id = wp_insert_post(array(
                        'post_title' => $field['label'],
                        'post_type' => 'acf-field',
                        'post_status' => 'publish',
                        'post_parent' => $post_id,
                    ));
                    
                    if ($field_post_id && !is_wp_error($field_post_id)) {
                        // Save field settings
                        foreach ($field as $key => $value) {
                            update_post_meta($field_post_id, $key, $value);
                        }
                    }
                }
            }
            
            return $group_key;
        }
        
        return false;
    }
    
    /**
     * Delete field group
     * 
     * @param string $group_key Field group key
     * @return bool Success
     */
    public static function delete_field_group($group_key) {
        if (!function_exists('acf_delete_field_group')) {
            return false;
        }
        
        // Don't allow deletion of core Ensemble groups
        if (in_array($group_key, array('group_ensemble_event', 'group_ensemble_artist', 'group_ensemble_location'))) {
            return false;
        }
        
        return acf_delete_field_group($group_key);
    }
    
    /**
     * Get predefined field templates
     * 
     * @return array Templates
     */
    public static function get_templates() {
        // Note: Ticket template removed - now handled by Tickets Add-on
        return array(
            'venue' => array(
                'id' => 'venue',
                'title' => __('Venue Details', 'ensemble'),
                'icon' => 'location',
                'description' => __('Additional venue information like capacity, address, and facilities', 'ensemble'),
                'requires_pro' => true,
                'fields' => array(
                    array(
                        'label' => __('Venue Capacity', 'ensemble'),
                        'name' => 'venue_capacity',
                        'type' => 'number',
                        'instructions' => __('Maximum capacity of the venue', 'ensemble'),
                        'min' => 0,
                    ),
                    array(
                        'label' => __('Venue Address', 'ensemble'),
                        'name' => 'venue_address',
                        'type' => 'textarea',
                        'instructions' => __('Full address of the venue', 'ensemble'),
                        'rows' => 3,
                    ),
                    array(
                        'label' => __('Parking Information', 'ensemble'),
                        'name' => 'venue_parking',
                        'type' => 'textarea',
                        'instructions' => __('Information about parking facilities', 'ensemble'),
                        'rows' => 3,
                    ),
                    array(
                        'label' => __('Public Transport', 'ensemble'),
                        'name' => 'venue_transport',
                        'type' => 'textarea',
                        'instructions' => __('How to reach venue by public transport', 'ensemble'),
                        'rows' => 3,
                    ),
                    array(
                        'label' => __('Accessibility', 'ensemble'),
                        'name' => 'venue_accessibility',
                        'type' => 'textarea',
                        'instructions' => __('Accessibility information (wheelchair access, etc.)', 'ensemble'),
                        'rows' => 3,
                    ),
                ),
            ),
            
            'sponsor' => array(
                'id' => 'sponsor',
                'title' => __('Sponsorship Pack', 'ensemble'),
                'icon' => 'users',
                'description' => __('Fields for event sponsors and partners', 'ensemble'),
                'requires_pro' => true,
                'fields' => array(
                    array(
                        'label' => __('Sponsor Name', 'ensemble'),
                        'name' => 'sponsor_name',
                        'type' => 'text',
                        'instructions' => __('Name of the sponsor/partner', 'ensemble'),
                    ),
                    array(
                        'label' => __('Sponsor Logo', 'ensemble'),
                        'name' => 'sponsor_logo',
                        'type' => 'image',
                        'instructions' => __('Sponsor logo image', 'ensemble'),
                    ),
                    array(
                        'label' => __('Sponsor Website', 'ensemble'),
                        'name' => 'sponsor_website',
                        'type' => 'url',
                        'instructions' => __('Sponsor website URL', 'ensemble'),
                    ),
                    array(
                        'label' => __('Sponsor Level', 'ensemble'),
                        'name' => 'sponsor_level',
                        'type' => 'select',
                        'instructions' => __('Sponsorship tier', 'ensemble'),
                        'choices' => array(
                            'platinum' => __('Platinum', 'ensemble'),
                            'gold' => __('Gold', 'ensemble'),
                            'silver' => __('Silver', 'ensemble'),
                            'bronze' => __('Bronze', 'ensemble'),
                            'partner' => __('Partner', 'ensemble'),
                        ),
                    ),
                    array(
                        'label' => __('Sponsor Description', 'ensemble'),
                        'name' => 'sponsor_description',
                        'type' => 'textarea',
                        'instructions' => __('Brief description of the sponsor', 'ensemble'),
                        'rows' => 4,
                    ),
                ),
            ),
            
            'media' => array(
                'id' => 'media',
                'title' => __('Media & Press', 'ensemble'),
                'icon' => 'megaphone',
                'description' => __('Media contacts, press releases, and promotional materials', 'ensemble'),
                'requires_pro' => true,
                'fields' => array(
                    array(
                        'label' => __('Press Release', 'ensemble'),
                        'name' => 'press_release',
                        'type' => 'textarea',
                        'instructions' => __('Event press release text', 'ensemble'),
                        'rows' => 6,
                    ),
                    array(
                        'label' => __('Press Contact Name', 'ensemble'),
                        'name' => 'press_contact_name',
                        'type' => 'text',
                        'instructions' => __('Name of press contact person', 'ensemble'),
                    ),
                    array(
                        'label' => __('Press Contact Email', 'ensemble'),
                        'name' => 'press_contact_email',
                        'type' => 'email',
                        'instructions' => __('Email for press inquiries', 'ensemble'),
                    ),
                    array(
                        'label' => __('Press Kit Download', 'ensemble'),
                        'name' => 'press_kit',
                        'type' => 'file',
                        'instructions' => __('Downloadable press kit (PDF/ZIP)', 'ensemble'),
                    ),
                    array(
                        'label' => __('Promo Images', 'ensemble'),
                        'name' => 'promo_images',
                        'type' => 'gallery',
                        'instructions' => __('Promotional images for media use', 'ensemble'),
                    ),
                ),
            ),
            
            'social' => array(
                'id' => 'social',
                'title' => __('Social Media', 'ensemble'),
                'icon' => 'share',
                'description' => __('Social media links and hashtags for the event', 'ensemble'),
                'requires_pro' => true,
                'fields' => array(
                    array(
                        'label' => __('Event Hashtag', 'ensemble'),
                        'name' => 'social_hashtag',
                        'type' => 'text',
                        'instructions' => __('Primary event hashtag', 'ensemble'),
                    ),
                    array(
                        'label' => __('Facebook Event', 'ensemble'),
                        'name' => 'social_facebook',
                        'type' => 'url',
                        'instructions' => __('Facebook event page URL', 'ensemble'),
                    ),
                    array(
                        'label' => __('Instagram', 'ensemble'),
                        'name' => 'social_instagram',
                        'type' => 'text',
                        'instructions' => __('Instagram handle (without @)', 'ensemble'),
                    ),
                    array(
                        'label' => __('Twitter/X', 'ensemble'),
                        'name' => 'social_twitter',
                        'type' => 'text',
                        'instructions' => __('Twitter/X handle (without @)', 'ensemble'),
                    ),
                    array(
                        'label' => __('YouTube', 'ensemble'),
                        'name' => 'social_youtube',
                        'type' => 'url',
                        'instructions' => __('YouTube video/channel URL', 'ensemble'),
                    ),
                ),
            ),
        );
    }
    
    /**
     * Get single template by ID
     * 
     * @param string $template_id Template ID
     * @return array|false Template or false
     */
    public static function get_template($template_id) {
        $templates = self::get_templates();
        return isset($templates[$template_id]) ? $templates[$template_id] : false;
    }
    
    /**
     * Update custom field group
     * 
     * @param string $group_key Group key
     * @param string $title Group title
     * @param array $fields_data Field definitions
     * @return bool Success
     */
    public static function update_custom_group($group_key, $title, $fields_data) {
        if (empty($group_key) || empty($title) || empty($fields_data)) {
            return false;
        }
        
        if (!function_exists('acf_get_field_group')) {
            return false;
        }
        
        // Get existing group
        $group = acf_get_field_group($group_key);
        if (!$group) {
            return false;
        }
        
        // Update title
        $group['title'] = $title;
        
        // Get existing fields to delete old ones
        $existing_fields = acf_get_fields($group_key);
        $existing_field_keys = array();
        if ($existing_fields) {
            foreach ($existing_fields as $field) {
                $existing_field_keys[] = $field['key'];
            }
        }
        
        // Process new/updated fields
        $new_fields = array();
        foreach ($fields_data as $field_data) {
            if (empty($field_data['label']) || empty($field_data['name']) || empty($field_data['type'])) {
                continue;
            }
            
            // Use existing key if provided, otherwise generate new
            $field_key = !empty($field_data['key']) ? $field_data['key'] : 'field_' . uniqid();
            
            $field = array(
                'key' => $field_key,
                'label' => sanitize_text_field($field_data['label']),
                'name' => sanitize_key($field_data['name']),
                'type' => sanitize_text_field($field_data['type']),
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => '',
            );
            
            $new_fields[] = $field;
        }
        
        $group['fields'] = $new_fields;
        
        // Update via ACF
        if (function_exists('acf_update_field_group')) {
            acf_update_field_group($group);
            
            // Delete fields that are no longer in the group
            foreach ($existing_field_keys as $old_key) {
                $still_exists = false;
                foreach ($new_fields as $new_field) {
                    if ($new_field['key'] === $old_key) {
                        $still_exists = true;
                        break;
                    }
                }
                
                if (!$still_exists && function_exists('acf_delete_field')) {
                    acf_delete_field($old_key);
                }
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Assign field group to categories
     * 
     * @param string $group_key Field group key
     * @param array $category_ids Category IDs
     * @return bool Success
     */
    public static function assign_to_categories($group_key, $category_ids) {
        if (empty($category_ids)) {
            return false;
        }
        
        // Get current wizard config
        $wizard_config = get_option('ensemble_wizard_config', array());
        
        // Add field group to each category
        foreach ($category_ids as $category_id) {
            if (!isset($wizard_config[$category_id])) {
                $wizard_config[$category_id] = array('field_groups' => array());
            }
            
            // Add group key if not already present
            if (!in_array($group_key, $wizard_config[$category_id]['field_groups'])) {
                $wizard_config[$category_id]['field_groups'][] = $group_key;
            }
        }
        
        // Save updated config
        return update_option('ensemble_wizard_config', $wizard_config);
    }
    
    /**
     * Get field type icon
     * 
     * @param string $type Field type
     * @return string Icon
     */
    public static function get_field_type_icon($type) {
        $icons = array(
            'text' => '📝',
            'textarea' => '📄',
            'number' => '🔢',
            'email' => '📧',
            'url' => '🔗',
            'password' => '🔒',
            'image' => '🖼️',
            'file' => '📎',
            'wysiwyg' => '✏️',
            'oembed' => '🎬',
            'gallery' => '🖼️',
            'select' => '📋',
            'checkbox' => '☑️',
            'radio' => '🔘',
            'true_false' => '✓',
            'date_picker' => '📅',
            'time_picker' => '🕐',
            'color_picker' => '🎨',
            'message' => '💬',
            'tab' => '📑',
        );
        
        return isset($icons[$type]) ? $icons[$type] : '⚙️';
    }
}
