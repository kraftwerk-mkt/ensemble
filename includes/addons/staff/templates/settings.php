<?php
/**
 * Staff Settings Template
 * 
 * @package Ensemble
 * @subpackage Addons/Staff
 * 
 * Variables available:
 * @var array $settings Settings array (passed from render_settings())
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get addon instance
$addon = null;
if (class_exists('ES_Addon_Manager') && ES_Addon_Manager::is_addon_active('staff')) {
    $addon = ES_Addon_Manager::get_active_addon('staff');
}

// Get labels
$staff_singular = $addon ? $addon->get_staff_label(false) : __('Contact', 'ensemble');
$staff_plural = $addon ? $addon->get_staff_label(true) : __('Contacts', 'ensemble');
$dept_singular = $addon ? $addon->get_department_label(false) : __('Department', 'ensemble');
$dept_plural = $addon ? $addon->get_department_label(true) : __('Departments', 'ensemble');

// Ensure $settings has defaults
$settings = wp_parse_args($settings, array(
    'default_layout'        => 'grid',
    'default_columns'       => 3,
    'show_image'            => true,
    'show_email'            => true,
    'show_phone'            => true,
    'show_position'         => true,
    'show_department'       => true,
    'show_responsibility'   => false,
    'show_office_hours'     => false,
    'show_social_links'     => false,
    'show_excerpt'          => false,
    'auto_display_events'   => false,
    'event_position'        => 'after_content',
    'event_title'           => __('Contact Persons', 'ensemble'),
    'auto_display_locations'=> false,
    'location_position'     => 'after_content',
    'location_title'        => __('Contact Persons', 'ensemble'),
    // Email notification settings
    'send_confirmation'     => true,
    'send_admin_copy'       => true,
    'admin_email'           => get_option('admin_email'),
));
?>

<div class="es-staff-settings">
    
    <!-- Display Settings -->
    <div class="es-settings-section">
        <h4 class="es-settings-section__title"><?php _e('Display Settings', 'ensemble'); ?></h4>
        
        <div class="es-settings-row">
            <div class="es-settings-field es-settings-field--half">
                <label class="es-settings-field__label" for="staff_default_layout">
                    <?php _e('Default Layout', 'ensemble'); ?>
                </label>
                <select name="default_layout" id="staff_default_layout" class="es-settings-field__select">
                    <option value="grid" <?php selected($settings['default_layout'], 'grid'); ?>><?php _e('Grid', 'ensemble'); ?></option>
                    <option value="list" <?php selected($settings['default_layout'], 'list'); ?>><?php _e('List', 'ensemble'); ?></option>
                    <option value="cards" <?php selected($settings['default_layout'], 'cards'); ?>><?php _e('Cards', 'ensemble'); ?></option>
                </select>
            </div>
            
            <div class="es-settings-field es-settings-field--half">
                <label class="es-settings-field__label" for="staff_default_columns">
                    <?php _e('Default Columns', 'ensemble'); ?>
                </label>
                <select name="default_columns" id="staff_default_columns" class="es-settings-field__select">
                    <option value="2" <?php selected($settings['default_columns'], 2); ?>>2</option>
                    <option value="3" <?php selected($settings['default_columns'], 3); ?>>3</option>
                    <option value="4" <?php selected($settings['default_columns'], 4); ?>>4</option>
                </select>
            </div>
        </div>
        
        <div class="es-settings-toggles">
            <label class="es-settings-toggle">
                <span class="es-settings-toggle__switch">
                    <input type="checkbox" name="show_image" value="1" <?php checked(!isset($settings['show_image']) || $settings['show_image']); ?>>
                    <span class="es-settings-toggle__track"></span>
                </span>
                <span class="es-settings-toggle__label"><?php _e('Show photo/image', 'ensemble'); ?></span>
            </label>
            
            <label class="es-settings-toggle">
                <span class="es-settings-toggle__switch">
                    <input type="checkbox" name="show_email" value="1" <?php checked($settings['show_email']); ?>>
                    <span class="es-settings-toggle__track"></span>
                </span>
                <span class="es-settings-toggle__label"><?php _e('Show email address', 'ensemble'); ?></span>
            </label>
            
            <label class="es-settings-toggle">
                <span class="es-settings-toggle__switch">
                    <input type="checkbox" name="show_phone" value="1" <?php checked($settings['show_phone']); ?>>
                    <span class="es-settings-toggle__track"></span>
                </span>
                <span class="es-settings-toggle__label"><?php _e('Show phone number', 'ensemble'); ?></span>
            </label>
            
            <label class="es-settings-toggle">
                <span class="es-settings-toggle__switch">
                    <input type="checkbox" name="show_position" value="1" <?php checked($settings['show_position']); ?>>
                    <span class="es-settings-toggle__track"></span>
                </span>
                <span class="es-settings-toggle__label"><?php _e('Show position/title', 'ensemble'); ?></span>
            </label>
            
            <label class="es-settings-toggle">
                <span class="es-settings-toggle__switch">
                    <input type="checkbox" name="show_department" value="1" <?php checked($settings['show_department']); ?>>
                    <span class="es-settings-toggle__track"></span>
                </span>
                <span class="es-settings-toggle__label"><?php printf(__('Show %s', 'ensemble'), strtolower($dept_singular)); ?></span>
            </label>
            
            <label class="es-settings-toggle">
                <span class="es-settings-toggle__switch">
                    <input type="checkbox" name="show_responsibility" value="1" <?php checked(!empty($settings['show_responsibility'])); ?>>
                    <span class="es-settings-toggle__track"></span>
                </span>
                <span class="es-settings-toggle__label"><?php _e('Show responsibility', 'ensemble'); ?></span>
            </label>
            
            <label class="es-settings-toggle">
                <span class="es-settings-toggle__switch">
                    <input type="checkbox" name="show_office_hours" value="1" <?php checked(!empty($settings['show_office_hours'])); ?>>
                    <span class="es-settings-toggle__track"></span>
                </span>
                <span class="es-settings-toggle__label"><?php _e('Show office hours', 'ensemble'); ?></span>
            </label>
            
            <label class="es-settings-toggle">
                <span class="es-settings-toggle__switch">
                    <input type="checkbox" name="show_social_links" value="1" <?php checked(!empty($settings['show_social_links'])); ?>>
                    <span class="es-settings-toggle__track"></span>
                </span>
                <span class="es-settings-toggle__label"><?php _e('Show social media links', 'ensemble'); ?></span>
            </label>
            
            <label class="es-settings-toggle">
                <span class="es-settings-toggle__switch">
                    <input type="checkbox" name="show_excerpt" value="1" <?php checked(!empty($settings['show_excerpt'])); ?>>
                    <span class="es-settings-toggle__track"></span>
                </span>
                <span class="es-settings-toggle__label"><?php _e('Show bio excerpt', 'ensemble'); ?></span>
            </label>
        </div>
    </div>
    
    <!-- Event Integration -->
    <div class="es-settings-section">
        <h4 class="es-settings-section__title"><?php _e('Event Integration', 'ensemble'); ?></h4>
        
        <div class="es-settings-toggles">
            <label class="es-settings-toggle">
                <span class="es-settings-toggle__switch">
                    <input type="checkbox" name="auto_display_events" value="1" id="staff_auto_events" <?php checked($settings['auto_display_events']); ?>>
                    <span class="es-settings-toggle__track"></span>
                </span>
                <span class="es-settings-toggle__label"><?php printf(__('Automatically show %s on event pages', 'ensemble'), strtolower($staff_plural)); ?></span>
            </label>
        </div>
        
        <div class="es-settings-subsection" id="staff_event_options" style="<?php echo $settings['auto_display_events'] ? '' : 'opacity: 0.5; pointer-events: none;'; ?>">
            <div class="es-settings-row">
                <div class="es-settings-field es-settings-field--half">
                    <label class="es-settings-field__label" for="staff_event_title"><?php _e('Section Title', 'ensemble'); ?></label>
                    <input type="text" 
                           name="event_title" 
                           id="staff_event_title" 
                           class="es-settings-field__input"
                           value="<?php echo esc_attr($settings['event_title']); ?>"
                           placeholder="<?php _e('Contact Persons', 'ensemble'); ?>">
                </div>
                
                <div class="es-settings-field es-settings-field--half">
                    <label class="es-settings-field__label" for="staff_event_position"><?php _e('Position', 'ensemble'); ?></label>
                    <select name="event_position" id="staff_event_position" class="es-settings-field__select">
                        <option value="before_content" <?php selected($settings['event_position'], 'before_content'); ?>><?php _e('Before content', 'ensemble'); ?></option>
                        <option value="after_content" <?php selected($settings['event_position'], 'after_content'); ?>><?php _e('After content', 'ensemble'); ?></option>
                        <option value="after_artists" <?php selected($settings['event_position'], 'after_artists'); ?>><?php _e('After speakers/artists', 'ensemble'); ?></option>
                        <option value="footer" <?php selected($settings['event_position'], 'footer'); ?>><?php _e('Footer area', 'ensemble'); ?></option>
                    </select>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Location Integration -->
    <div class="es-settings-section">
        <h4 class="es-settings-section__title"><?php _e('Location Integration', 'ensemble'); ?></h4>
        
        <div class="es-settings-toggles">
            <label class="es-settings-toggle">
                <span class="es-settings-toggle__switch">
                    <input type="checkbox" name="auto_display_locations" value="1" id="staff_auto_locations" <?php checked($settings['auto_display_locations']); ?>>
                    <span class="es-settings-toggle__track"></span>
                </span>
                <span class="es-settings-toggle__label"><?php printf(__('Automatically show %s on location pages', 'ensemble'), strtolower($staff_plural)); ?></span>
            </label>
        </div>
        
        <div class="es-settings-subsection" id="staff_location_options" style="<?php echo $settings['auto_display_locations'] ? '' : 'opacity: 0.5; pointer-events: none;'; ?>">
            <div class="es-settings-row">
                <div class="es-settings-field es-settings-field--half">
                    <label class="es-settings-field__label" for="staff_location_title"><?php _e('Section Title', 'ensemble'); ?></label>
                    <input type="text" 
                           name="location_title" 
                           id="staff_location_title" 
                           class="es-settings-field__input"
                           value="<?php echo esc_attr($settings['location_title']); ?>"
                           placeholder="<?php _e('Contact Persons', 'ensemble'); ?>">
                </div>
                
                <div class="es-settings-field es-settings-field--half">
                    <label class="es-settings-field__label" for="staff_location_position"><?php _e('Position', 'ensemble'); ?></label>
                    <select name="location_position" id="staff_location_position" class="es-settings-field__select">
                        <option value="before_content" <?php selected($settings['location_position'], 'before_content'); ?>><?php _e('Before content', 'ensemble'); ?></option>
                        <option value="after_content" <?php selected($settings['location_position'], 'after_content'); ?>><?php _e('After content', 'ensemble'); ?></option>
                        <option value="sidebar" <?php selected($settings['location_position'], 'sidebar'); ?>><?php _e('Sidebar', 'ensemble'); ?></option>
                    </select>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Email Notifications -->
    <div class="es-settings-section">
        <h4 class="es-settings-section__title"><?php _e('Email Notifications', 'ensemble'); ?></h4>
        <p class="es-settings-section__desc"><?php _e('Configure email notifications for abstract submissions.', 'ensemble'); ?></p>
        
        <div class="es-settings-row">
            <div class="es-settings-field">
                <label class="es-checkbox-field">
                    <input type="checkbox" 
                           name="send_confirmation" 
                           id="staff_send_confirmation"
                           value="1" 
                           <?php checked($settings['send_confirmation'] ?? true); ?>>
                    <span><?php _e('Send confirmation email to submitter', 'ensemble'); ?></span>
                </label>
                <span class="es-settings-field__hint"><?php _e('Automatically send a confirmation when someone submits an abstract.', 'ensemble'); ?></span>
            </div>
        </div>
        
        <div class="es-settings-row">
            <div class="es-settings-field">
                <label class="es-checkbox-field">
                    <input type="checkbox" 
                           name="send_admin_copy" 
                           id="staff_send_admin_copy"
                           value="1" 
                           <?php checked($settings['send_admin_copy'] ?? true); ?>>
                    <span><?php _e('Send copy to admin', 'ensemble'); ?></span>
                </label>
                <span class="es-settings-field__hint"><?php _e('Send a copy of all abstract submissions to the admin email.', 'ensemble'); ?></span>
            </div>
        </div>
        
        <div class="es-settings-row">
            <div class="es-settings-field">
                <label class="es-settings-field__label" for="staff_admin_email">
                    <?php _e('Admin Email', 'ensemble'); ?>
                </label>
                <input type="email" 
                       name="admin_email" 
                       id="staff_admin_email"
                       value="<?php echo esc_attr($settings['admin_email'] ?? get_option('admin_email')); ?>"
                       class="es-settings-field__input"
                       placeholder="<?php echo esc_attr(get_option('admin_email')); ?>">
                <span class="es-settings-field__hint"><?php _e('Email address for admin notifications. Leave empty to use site admin email.', 'ensemble'); ?></span>
            </div>
        </div>
        
        <div class="es-settings-row">
            <div class="es-settings-field">
                <a href="<?php echo esc_url(admin_url('admin.php?page=ensemble-abstracts')); ?>" class="button">
                    <span class="dashicons dashicons-media-document" style="line-height: 1.4;"></span>
                    <?php _e('View Abstract Submissions', 'ensemble'); ?>
                </a>
            </div>
        </div>
    </div>
    
    <!-- Shortcode Reference -->
    <div class="es-settings-section">
        <h4 class="es-settings-section__title"><?php _e('Shortcode Reference', 'ensemble'); ?></h4>
        <p class="es-settings-section__desc"><?php printf(__('Use these shortcodes to display %s anywhere on your site.', 'ensemble'), strtolower($staff_plural)); ?></p>
        
        <!-- Staff Grid/List -->
        <div class="es-shortcode-block">
            <h5 class="es-shortcode-block__title">
                <span class="dashicons dashicons-grid-view"></span>
                <?php printf(__('%s Grid / List', 'ensemble'), $staff_singular); ?>
            </h5>
            <code class="es-shortcode-block__code">[ensemble_staff]</code>
            
            <details class="es-shortcode-details">
                <summary><?php _e('View all parameters', 'ensemble'); ?></summary>
                <div class="es-shortcode-params">
                    <table>
                        <thead>
                            <tr>
                                <th><?php _e('Parameter', 'ensemble'); ?></th>
                                <th><?php _e('Default', 'ensemble'); ?></th>
                                <th><?php _e('Description', 'ensemble'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>layout</code></td>
                                <td><code>grid</code></td>
                                <td><?php _e('Display style: grid, list, or cards', 'ensemble'); ?></td>
                            </tr>
                            <tr>
                                <td><code>columns</code></td>
                                <td><code>3</code></td>
                                <td><?php _e('Number of columns (2-4)', 'ensemble'); ?></td>
                            </tr>
                            <tr>
                                <td><code>department</code></td>
                                <td><code></code></td>
                                <td><?php printf(__('Filter by %s (slug or ID)', 'ensemble'), strtolower($dept_singular)); ?></td>
                            </tr>
                            <tr>
                                <td><code>ids</code></td>
                                <td><code></code></td>
                                <td><?php printf(__('Show specific %s (comma-separated IDs)', 'ensemble'), strtolower($staff_plural)); ?></td>
                            </tr>
                            <tr>
                                <td><code>limit</code></td>
                                <td><code>-1</code></td>
                                <td><?php _e('Maximum number to show (-1 = all)', 'ensemble'); ?></td>
                            </tr>
                            <tr>
                                <td><code>orderby</code></td>
                                <td><code>menu_order</code></td>
                                <td><?php _e('Sort by: menu_order, title, date, rand', 'ensemble'); ?></td>
                            </tr>
                            <tr>
                                <td><code>order</code></td>
                                <td><code>ASC</code></td>
                                <td><?php _e('Sort direction: ASC or DESC', 'ensemble'); ?></td>
                            </tr>
                            <tr>
                                <td><code>show_email</code></td>
                                <td><code>yes</code></td>
                                <td><?php _e('Show email: yes or no', 'ensemble'); ?></td>
                            </tr>
                            <tr>
                                <td><code>show_phone</code></td>
                                <td><code>yes</code></td>
                                <td><?php _e('Show phone: yes or no', 'ensemble'); ?></td>
                            </tr>
                            <tr>
                                <td><code>show_position</code></td>
                                <td><code>yes</code></td>
                                <td><?php _e('Show position: yes or no', 'ensemble'); ?></td>
                            </tr>
                            <tr>
                                <td><code>show_office_hours</code></td>
                                <td><code>no</code></td>
                                <td><?php _e('Show office hours: yes or no', 'ensemble'); ?></td>
                            </tr>
                            <tr>
                                <td><code>show_social</code></td>
                                <td><code>no</code></td>
                                <td><?php _e('Show social media links: yes or no', 'ensemble'); ?></td>
                            </tr>
                            <tr>
                                <td><code>show_image</code></td>
                                <td><code>yes</code></td>
                                <td><?php _e('Show photo/image: yes or no', 'ensemble'); ?></td>
                            </tr>
                            <tr>
                                <td><code>show_responsibility</code></td>
                                <td><code>no</code></td>
                                <td><?php _e('Show responsibility text: yes or no', 'ensemble'); ?></td>
                            </tr>
                            <tr>
                                <td><code>show_excerpt</code></td>
                                <td><code>no</code></td>
                                <td><?php _e('Show bio excerpt: yes or no', 'ensemble'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </details>
            
            <div class="es-shortcode-examples">
                <span class="es-shortcode-examples__title"><?php _e('Examples:', 'ensemble'); ?></span>
                <div class="es-shortcode-ref__item">
                    <code>[ensemble_staff layout="cards" columns="4"]</code>
                    <span><?php _e('4-column card layout', 'ensemble'); ?></span>
                </div>
                <div class="es-shortcode-ref__item">
                    <code>[ensemble_staff department="marketing" limit="6"]</code>
                    <span><?php printf(__('6 %s from Marketing', 'ensemble'), strtolower($staff_plural)); ?></span>
                </div>
            </div>
        </div>
        
        <!-- Single Staff -->
        <div class="es-shortcode-block">
            <h5 class="es-shortcode-block__title">
                <span class="dashicons dashicons-id"></span>
                <?php printf(__('Single %s', 'ensemble'), $staff_singular); ?>
            </h5>
            <code class="es-shortcode-block__code">[ensemble_staff_single id="123"]</code>
            
            <details class="es-shortcode-details">
                <summary><?php _e('View all parameters', 'ensemble'); ?></summary>
                <div class="es-shortcode-params">
                    <table>
                        <thead>
                            <tr>
                                <th><?php _e('Parameter', 'ensemble'); ?></th>
                                <th><?php _e('Default', 'ensemble'); ?></th>
                                <th><?php _e('Description', 'ensemble'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>id</code></td>
                                <td><code></code></td>
                                <td><?php printf(__('%s ID (required)', 'ensemble'), $staff_singular); ?></td>
                            </tr>
                            <tr>
                                <td><code>layout</code></td>
                                <td><code>card</code></td>
                                <td><?php _e('Display style: card, full, or compact', 'ensemble'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </details>
        </div>
        
        <!-- Event Contacts -->
        <div class="es-shortcode-block">
            <h5 class="es-shortcode-block__title">
                <span class="dashicons dashicons-calendar-alt"></span>
                <?php _e('Event Contacts', 'ensemble'); ?>
            </h5>
            <code class="es-shortcode-block__code">[ensemble_event_contacts]</code>
            
            <details class="es-shortcode-details">
                <summary><?php _e('View all parameters', 'ensemble'); ?></summary>
                <div class="es-shortcode-params">
                    <table>
                        <thead>
                            <tr>
                                <th><?php _e('Parameter', 'ensemble'); ?></th>
                                <th><?php _e('Default', 'ensemble'); ?></th>
                                <th><?php _e('Description', 'ensemble'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>event_id</code></td>
                                <td><code></code></td>
                                <td><?php _e('Event ID (auto-detected on event pages)', 'ensemble'); ?></td>
                            </tr>
                            <tr>
                                <td><code>layout</code></td>
                                <td><code>inline</code></td>
                                <td><?php _e('Display style: inline, grid, or list', 'ensemble'); ?></td>
                            </tr>
                            <tr>
                                <td><code>title</code></td>
                                <td><code></code></td>
                                <td><?php _e('Custom section title', 'ensemble'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </details>
            
            <p class="es-shortcode-hint"><?php _e('Use on event pages to display assigned contacts. On other pages, specify event_id.', 'ensemble'); ?></p>
        </div>
        
        <!-- Contact Form -->
        <div class="es-shortcode-block">
            <h5 class="es-shortcode-block__title">
                <span class="dashicons dashicons-upload"></span>
                <?php _e('File Submission Form', 'ensemble'); ?>
            </h5>
            <code class="es-shortcode-block__code">[ensemble_contact_form staff_id="123"]</code>
            
            <details class="es-shortcode-details">
                <summary><?php _e('View all parameters', 'ensemble'); ?></summary>
                <div class="es-shortcode-params">
                    <table>
                        <thead>
                            <tr>
                                <th><?php _e('Parameter', 'ensemble'); ?></th>
                                <th><?php _e('Default', 'ensemble'); ?></th>
                                <th><?php _e('Description', 'ensemble'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>staff_id</code></td>
                                <td><code></code></td>
                                <td><?php printf(__('%s ID (required)', 'ensemble'), $staff_singular); ?></td>
                            </tr>
                            <tr>
                                <td><code>title</code></td>
                                <td><code></code></td>
                                <td><?php _e('Custom form title', 'ensemble'); ?></td>
                            </tr>
                            <tr>
                                <td><code>description</code></td>
                                <td><code></code></td>
                                <td><?php _e('Custom description text', 'ensemble'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </details>
            
            <div class="es-shortcode-note">
                <span class="dashicons dashicons-info"></span>
                <?php printf(__('The %s must have "Accept File Submissions" enabled for the form to display.', 'ensemble'), strtolower($staff_singular)); ?>
            </div>
        </div>
        
    </div>
    
</div>

<style>
/* Staff Settings Styles - Matches Downloads Settings Pattern */
.es-staff-settings {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.es-settings-section {
    background: var(--es-surface-secondary, #252525);
    border: 1px solid var(--es-border, #333);
    border-radius: var(--es-radius, 6px);
    padding: 20px;
}

.es-settings-section--info {
    background: transparent;
    border-style: dashed;
}

.es-settings-section__title {
    margin: 0 0 16px;
    font-size: 14px;
    font-weight: 600;
    color: var(--es-text, #e0e0e0);
}

.es-settings-section__desc {
    margin: -8px 0 16px;
    font-size: 13px;
    color: var(--es-text-muted, #888);
}

/* Settings Row */
.es-settings-row {
    display: flex;
    gap: 16px;
}

.es-settings-field {
    margin-bottom: 16px;
}

.es-settings-field:last-child {
    margin-bottom: 0;
}

.es-settings-field--half {
    flex: 1;
}

.es-settings-field__label {
    display: block;
    margin-bottom: 6px;
    font-size: 13px;
    font-weight: 500;
    color: var(--es-text, #e0e0e0);
}

.es-settings-field__input,
.es-settings-field__select {
    width: 100%;
    padding: 8px 12px;
    background: var(--es-surface, #1e1e1e);
    border: 1px solid var(--es-border, #333);
    border-radius: 4px;
    color: var(--es-text, #e0e0e0);
    font-size: 13px;
}

.es-settings-field__input:focus,
.es-settings-field__select:focus {
    border-color: var(--es-primary, #3b82f6);
    outline: none;
}

/* Toggle Switches - Matching Downloads Pattern */
.es-settings-toggles {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.es-settings-toggle {
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
}

.es-settings-toggle__switch {
    position: relative;
    flex-shrink: 0;
}

.es-settings-toggle__switch input {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}

.es-settings-toggle__track {
    display: block;
    width: 40px;
    height: 22px;
    background: var(--es-surface, #1e1e1e);
    border: 1px solid var(--es-border, #333);
    border-radius: 11px;
    position: relative;
    transition: all 0.2s ease;
}

.es-settings-toggle__track::after {
    content: '';
    position: absolute;
    top: 2px;
    left: 2px;
    width: 16px;
    height: 16px;
    background: var(--es-text-muted, #888);
    border-radius: 50%;
    transition: all 0.2s ease;
}

.es-settings-toggle__switch input:checked + .es-settings-toggle__track {
    background: var(--es-primary, #3b82f6);
    border-color: var(--es-primary, #3b82f6);
}

.es-settings-toggle__switch input:checked + .es-settings-toggle__track::after {
    left: 20px;
    background: #fff;
}

.es-settings-toggle__label {
    font-size: 13px;
    color: var(--es-text, #e0e0e0);
}

/* Subsections */
.es-settings-subsection {
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid var(--es-border, #333);
    transition: opacity 0.2s ease;
}

/* Hints */
.es-settings-hint {
    margin: 12px 0 0;
    font-size: 12px;
    color: var(--es-text-muted, #888);
    line-height: 1.5;
}

/* Shortcode Reference */
.es-shortcode-block {
    background: var(--es-surface-secondary, #252525);
    border: 1px solid var(--es-border, #333);
    border-radius: 6px;
    padding: 16px;
    margin-bottom: 16px;
}

.es-shortcode-block:last-child {
    margin-bottom: 0;
}

.es-shortcode-block__title {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0 0 12px;
    font-size: 13px;
    font-weight: 600;
    color: var(--es-text, #e0e0e0);
}

.es-shortcode-block__title .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
    color: var(--es-primary, #3b82f6);
}

.es-shortcode-block__code {
    display: block;
    padding: 10px 14px;
    background: var(--es-surface, #1e1e1e);
    border-radius: 4px;
    font-size: 13px;
    color: var(--es-primary, #3b82f6);
    font-family: monospace;
    margin-bottom: 12px;
}

.es-shortcode-details {
    margin-top: 12px;
    border-top: 1px solid var(--es-border, #333);
    padding-top: 12px;
}

.es-shortcode-details summary {
    cursor: pointer;
    font-size: 12px;
    color: var(--es-primary, #3b82f6);
    padding: 4px 0;
    user-select: none;
}

.es-shortcode-details summary:hover {
    text-decoration: underline;
}

.es-shortcode-params {
    margin-top: 12px;
    overflow-x: auto;
}

.es-shortcode-params table {
    width: 100%;
    border-collapse: collapse;
    font-size: 11px;
    min-width: 450px;
}

.es-shortcode-params th,
.es-shortcode-params td {
    padding: 6px 10px;
    text-align: left;
    border-bottom: 1px solid var(--es-border, #333);
}

.es-shortcode-params th {
    background: var(--es-surface, #1e1e1e);
    font-weight: 600;
    color: var(--es-text, #e0e0e0);
    white-space: nowrap;
}

.es-shortcode-params td {
    color: var(--es-text-muted, #888);
}

.es-shortcode-params td:first-child {
    white-space: nowrap;
}

.es-shortcode-params code {
    background: var(--es-surface, #1e1e1e);
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 10px;
    color: var(--es-primary, #3b82f6);
}

.es-shortcode-examples {
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin-top: 12px;
}

.es-shortcode-examples__title {
    font-size: 11px;
    font-weight: 600;
    color: var(--es-text-muted, #888);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.es-shortcode-ref__item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 6px 10px;
    background: var(--es-surface, #1e1e1e);
    border-radius: 4px;
}

.es-shortcode-ref__item code {
    background: transparent;
    padding: 0;
    font-size: 11px;
    color: var(--es-primary, #3b82f6);
    white-space: nowrap;
    flex-shrink: 0;
}

.es-shortcode-ref__item span {
    font-size: 11px;
    color: var(--es-text-muted, #888);
}

.es-shortcode-hint {
    margin: 12px 0 0;
    font-size: 12px;
    color: var(--es-text-muted, #888);
    font-style: italic;
}

.es-shortcode-note {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    margin-top: 12px;
    padding: 10px 12px;
    background: rgba(59, 130, 246, 0.1);
    border-radius: 6px;
    font-size: 12px;
    color: var(--es-text-muted, #888);
}

.es-shortcode-note .dashicons {
    color: var(--es-primary, #3b82f6);
    font-size: 16px;
    width: 16px;
    height: 16px;
    flex-shrink: 0;
    margin-top: 1px;
}

/* Responsive */
@media (max-width: 600px) {
    .es-settings-row {
        flex-direction: column;
    }
    
    .es-settings-field--half {
        flex: 1;
    }
}
</style>

<script>
jQuery(function($) {
    // Toggle event options
    $('#staff_auto_events').on('change', function() {
        $('#staff_event_options').css({
            'opacity': this.checked ? '1' : '0.5',
            'pointer-events': this.checked ? 'auto' : 'none'
        });
    });
    
    // Toggle location options
    $('#staff_auto_locations').on('change', function() {
        $('#staff_location_options').css({
            'opacity': this.checked ? '1' : '0.5',
            'pointer-events': this.checked ? 'auto' : 'none'
        });
    });
});
</script>
