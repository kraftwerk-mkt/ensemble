<?php
/**
 * Settings Page Template
 * UPDATED WITH POST TYPE SELECTION!
 *
 * @package Ensemble
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Handle form submission
if (isset($_POST['es_save_settings']) && check_admin_referer('ensemble_settings')) {
    // Save theme
    $theme = isset($_POST['ensemble_theme']) ? sanitize_text_field($_POST['ensemble_theme']) : 'dark';
    update_option('ensemble_theme', $theme);
    
    // ✅ Save post type
    if (isset($_POST['ensemble_post_type'])) {
        $post_type = sanitize_text_field($_POST['ensemble_post_type']);
        if (post_type_exists($post_type)) {
            update_option('ensemble_post_type', $post_type);
        }
    }
    
    echo '<div class="notice notice-success is-dismissible"><p>' . __('Settings saved!', 'ensemble') . '</p></div>';
}

// Handle label settings
if (isset($_POST['es_save_labels']) && check_admin_referer('ensemble_labels')) {
    // Save usage type
    if (isset($_POST['usage_type'])) {
        update_option('ensemble_usage_type', sanitize_text_field($_POST['usage_type']));
    }
    
    // Save artist labels
    if (isset($_POST['artist_label_singular'])) {
        update_option('ensemble_label_artist_singular', sanitize_text_field($_POST['artist_label_singular']));
    }
    if (isset($_POST['artist_label_plural'])) {
        update_option('ensemble_label_artist_plural', sanitize_text_field($_POST['artist_label_plural']));
    }
    
    // Save location labels
    if (isset($_POST['location_label_singular'])) {
        update_option('ensemble_label_location_singular', sanitize_text_field($_POST['location_label_singular']));
    }
    if (isset($_POST['location_label_plural'])) {
        update_option('ensemble_label_location_plural', sanitize_text_field($_POST['location_label_plural']));
    }
    
    // Flush rewrite rules to update slugs
    flush_rewrite_rules();
    
    echo '<div class="notice notice-success is-dismissible"><p>' . __('Labels saved! Please reload the page to see changes.', 'ensemble') . '</p></div>';
}

// Handle field mapping configuration
if (isset($_POST['es_save_field_mapping']) && check_admin_referer('ensemble_field_mapping')) {
    $field_mapping = isset($_POST['field_mapping']) ? $_POST['field_mapping'] : array();
    
    // Sanitize mapping
    $sanitized_mapping = array();
    foreach ($field_mapping as $standard_field => $acf_field) {
        $standard_field = sanitize_text_field($standard_field);
        $acf_field = sanitize_text_field($acf_field);
        
        // Only save if ACF field is selected
        if (!empty($acf_field)) {
            $sanitized_mapping[$standard_field] = $acf_field;
        }
    }
    
    update_option('ensemble_field_mapping', $sanitized_mapping);
    
    echo '<div class="notice notice-success is-dismissible"><p>' . __('Field mapping saved!', 'ensemble') . '</p></div>';
}

// Handle wizard fields configuration (which standard fields to show)
if (isset($_POST['es_save_wizard_fields']) && check_admin_referer('ensemble_wizard_fields')) {
    // Hidden field confirms form was submitted (even if all checkboxes unchecked)
    $wizard_fields = isset($_POST['wizard_fields']) ? $_POST['wizard_fields'] : array();
    
    // Define all available optional fields
    $available_fields = array('time', 'time_end', 'location', 'artist', 'description', 'price', 'ticket_url', 'button_text');
    
    // Sanitize - only keep valid field names
    $sanitized_fields = array();
    foreach ($wizard_fields as $field) {
        $field = sanitize_text_field($field);
        if (in_array($field, $available_fields)) {
            $sanitized_fields[] = $field;
        }
    }
    
    // Save the array (even if empty - means all fields disabled)
    update_option('ensemble_wizard_fields', $sanitized_fields);
    
    // Also save a flag that config has been set (to distinguish from fresh install)
    update_option('ensemble_wizard_fields_configured', true);
    
    echo '<div class="notice notice-success is-dismissible"><p>' . __('Wizard fields configuration saved!', 'ensemble') . '</p></div>';
}

// Handle wizard steps configuration
if (isset($_POST['es_save_wizard_steps']) && check_admin_referer('ensemble_wizard_steps')) {
    $wizard_config = isset($_POST['wizard_config']) ? $_POST['wizard_config'] : array();
    
    // Sanitize configuration
    $sanitized_config = array();
    foreach ($wizard_config as $category_id => $config) {
        $category_id = intval($category_id);
        $sanitized_config[$category_id] = array(
            'field_groups' => isset($config['field_groups']) ? array_map('sanitize_text_field', $config['field_groups']) : array(),
        );
    }
    
    update_option('ensemble_wizard_config', $sanitized_config);
    
    echo '<div class="notice notice-success is-dismissible"><p>' . __('Wizard steps configuration saved!', 'ensemble') . '</p></div>';
}

// Handle link behavior settings
if (isset($_POST['es_save_link_settings']) && check_admin_referer('ensemble_link_settings')) {
    // Artists linking - use '1' and '0' strings to avoid WordPress false/empty issues
    $link_artists = isset($_POST['link_artists']) ? '1' : '0';
    update_option('ensemble_link_artists', $link_artists);
    
    // Artist link target (post or website)
    $artist_link_target = isset($_POST['artist_link_target']) ? sanitize_text_field($_POST['artist_link_target']) : 'post';
    update_option('ensemble_artist_link_target', $artist_link_target);
    
    // Open external artist links in new tab
    $artist_link_new_tab = isset($_POST['artist_link_new_tab']) ? '1' : '0';
    update_option('ensemble_artist_link_new_tab', $artist_link_new_tab);
    
    // Locations linking
    $link_locations = isset($_POST['link_locations']) ? '1' : '0';
    update_option('ensemble_link_locations', $link_locations);
    
    // Location link target (post or website)
    $location_link_target = isset($_POST['location_link_target']) ? sanitize_text_field($_POST['location_link_target']) : 'post';
    update_option('ensemble_location_link_target', $location_link_target);
    
    // Open external links in new tab
    $location_link_new_tab = isset($_POST['location_link_new_tab']) ? '1' : '0';
    update_option('ensemble_location_link_new_tab', $location_link_new_tab);
    
    echo '<div class="notice notice-success is-dismissible"><p>' . __('Link settings saved!', 'ensemble') . '</p></div>';
}

// Handle display settings
if (isset($_POST['es_save_display_settings']) && check_admin_referer('ensemble_display_settings')) {
    $display_settings = array(
        'cards' => array(
            'image'    => isset($_POST['display']['cards']['image']) ? true : false,
            'title'    => isset($_POST['display']['cards']['title']) ? true : false,
            'date'     => isset($_POST['display']['cards']['date']) ? true : false,
            'time'     => isset($_POST['display']['cards']['time']) ? true : false,
            'location' => isset($_POST['display']['cards']['location']) ? true : false,
            'category' => isset($_POST['display']['cards']['category']) ? true : false,
            'excerpt'  => isset($_POST['display']['cards']['excerpt']) ? true : false,
            'price'    => isset($_POST['display']['cards']['price']) ? true : false,
            'status'   => isset($_POST['display']['cards']['status']) ? true : false,
            'artists'  => isset($_POST['display']['cards']['artists']) ? true : false,
        ),
        'single' => array(
            'sections' => array(
                'meta'        => isset($_POST['display']['single']['sections']['meta']) ? true : false,
                'description' => isset($_POST['display']['single']['sections']['description']) ? true : false,
                'artists'     => isset($_POST['display']['single']['sections']['artists']) ? true : false,
                'location'    => isset($_POST['display']['single']['sections']['location']) ? true : false,
            ),
            'headers' => array(
                'artists'         => isset($_POST['display']['single']['headers']['artists']) ? true : false,
                'location'        => isset($_POST['display']['single']['headers']['location']) ? true : false,
                'description'     => isset($_POST['display']['single']['headers']['description']) ? true : false,
                'additional_info' => isset($_POST['display']['single']['headers']['additional_info']) ? true : false,
            ),
            'meta_items' => array(
                'date'     => isset($_POST['display']['single']['meta_items']['date']) ? true : false,
                'time'     => isset($_POST['display']['single']['meta_items']['time']) ? true : false,
                'venue'    => isset($_POST['display']['single']['meta_items']['venue']) ? true : false,
                'category' => isset($_POST['display']['single']['meta_items']['category']) ? true : false,
                'price'    => isset($_POST['display']['single']['meta_items']['price']) ? true : false,
                'status'   => isset($_POST['display']['single']['meta_items']['status']) ? true : false,
            ),
            'lineup' => array(
                'artist_image'      => isset($_POST['display']['single']['lineup']['artist_image']) ? true : false,
                'artist_genre'      => isset($_POST['display']['single']['lineup']['artist_genre']) ? true : false,
                'artist_references' => isset($_POST['display']['single']['lineup']['artist_references']) ? true : false,
                'artist_time'       => isset($_POST['display']['single']['lineup']['artist_time']) ? true : false,
            ),
            'location_display' => isset($_POST['display']['single']['location_display']) ? sanitize_key($_POST['display']['single']['location_display']) : 'full',
        ),
        'addons' => array(),
    );
    
    // Process addon settings
    $addon_options = function_exists('ensemble_get_addon_display_options') ? ensemble_get_addon_display_options() : array();
    foreach ($addon_options as $addon_key => $addon_config) {
        $display_settings['addons'][$addon_key] = array(
            'show'   => isset($_POST['display']['addons'][$addon_key]['show']) ? true : false,
            'header' => isset($_POST['display']['addons'][$addon_key]['header']) ? true : false,
        );
    }
    
    update_option('ensemble_display_settings', $display_settings);
    
    echo '<div class="notice notice-success is-dismissible"><p>' . __('Display settings saved!', 'ensemble') . '</p></div>';
}

// Handle typography settings
if (isset($_POST['save_typography']) && check_admin_referer('ensemble_save_typography', 'ensemble_typography_nonce')) {
    if (class_exists('ES_Font_Manager')) {
        $typography = isset($_POST['typography']) ? $_POST['typography'] : array();
        ES_Font_Manager::instance()->save_settings($typography);
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Typography settings saved!', 'ensemble') . '</p></div>';
    }
}

$current_theme = get_option('ensemble_theme', 'dark');
$current_post_type = get_option('ensemble_post_type', 'post');
$acf_installed = get_option('ensemble_acf_installed', false);
$acf_available = function_exists('acf_add_local_field_group');
$wizard_config = get_option('ensemble_wizard_config', array());
$field_mapping = get_option('ensemble_field_mapping', array());

// Wizard fields configuration - which standard fields to show
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

// Get all event categories
$wizard = new ES_Wizard();
$categories = $wizard->get_categories();

// Get all ACF field groups
$field_groups = array();
if (function_exists('acf_get_field_groups')) {
    $all_groups = acf_get_field_groups();
    foreach ($all_groups as $group) {
        // Exclude the built-in Ensemble field groups
        if (!in_array($group['key'], array('group_ensemble_event', 'group_ensemble_artist', 'group_ensemble_location'))) {
            $field_groups[] = $group;
        }
    }
}

// Get ALL ACF fields for field mapping
$all_acf_fields = array();
if (function_exists('acf_get_field_groups')) {
    $all_groups = acf_get_field_groups();
    foreach ($all_groups as $group) {
        $fields = acf_get_fields($group['key']);
        if ($fields) {
            foreach ($fields as $field) {
                // Skip structural fields
                $structural_types = array('tab', 'message', 'accordion', 'group', 'repeater', 'clone');
                if (!in_array($field['type'], $structural_types)) {
                    $all_acf_fields[] = array(
                        'key' => $field['key'],
                        'name' => $field['name'],
                        'label' => $field['label'],
                        'type' => $field['type'],
                        'group' => $group['title'],
                    );
                }
            }
        }
    }
}

// ✅ NEW: Get ALL available fields (ACF + Native Meta)
$all_available_fields = ES_Meta_Scanner::get_all_available_fields($current_post_type);

// Get current tab
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
?>

<div class="wrap es-settings-wrap">
   
    <h1><?php _e('Ensemble Settings', 'ensemble'); ?></h1>
     <div class="es-settings-container">
    <!-- Tab Navigation -->
    <div class="es-settings-tabs">
    <h2 class="nav-tab-wrapper">
        <a href="?page=ensemble-settings&tab=general" class="nav-tab <?php echo $current_tab === 'general' ? 'nav-tab-active' : ''; ?>">
            <?php _e('General', 'ensemble'); ?>
        </a>
        <a href="?page=ensemble-settings&tab=labels" class="nav-tab <?php echo $current_tab === 'labels' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Labels & Terms', 'ensemble'); ?>
        </a>
        <a href="?page=ensemble-settings&tab=display" class="nav-tab <?php echo $current_tab === 'display' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Display', 'ensemble'); ?>
        </a>
        <a href="?page=ensemble-settings&tab=custom-fonts" class="nav-tab <?php echo $current_tab === 'custom-fonts' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Custom Fonts', 'ensemble'); ?>
            <?php if (function_exists('ensemble_is_pro') && ensemble_is_pro()): ?>
                <span class="es-pro-badge" style="margin-left: 5px; font-size: 9px; padding: 2px 5px;">PRO</span>
            <?php endif; ?>
        </a>
        <a href="?page=ensemble-settings&tab=links" class="nav-tab <?php echo $current_tab === 'links' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Link Behavior', 'ensemble'); ?>
        </a>
        <a href="?page=ensemble-settings&tab=field-mapping" class="nav-tab <?php echo $current_tab === 'field-mapping' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Field Mapping', 'ensemble'); ?>
        </a>
        <a href="?page=ensemble-settings&tab=wizard-steps" class="nav-tab <?php echo $current_tab === 'wizard-steps' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Wizard Steps', 'ensemble'); ?>
        </a>
        <a href="?page=ensemble-settings&tab=license" class="nav-tab <?php echo $current_tab === 'license' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Lizenz', 'ensemble'); ?>
            <?php if (function_exists('ensemble_is_pro') && ensemble_is_pro()): ?>
                <span class="es-pro-badge" style="margin-left: 5px; font-size: 9px; padding: 2px 5px;">PRO</span>
            <?php endif; ?>
        </a>
    </h2>
    </div>
    <div class="es-settings-section">
    
    <!-- License Tab -->
    <?php if ($current_tab === 'license'): ?>
    
        <h3><?php _e('Pro Lizenz', 'ensemble'); ?></h3>
        <p class="description" style="margin-bottom: 1.5rem;">
            <?php _e('Aktiviere deine Pro-Lizenz um alle Premium-Features freizuschalten.', 'ensemble'); ?>
        </p>
        
        <?php 
        if (class_exists('ES_License_Manager')) {
            ES_License_Manager::instance()->render_license_settings();
        }
        ?>
        
        <div class="es-pro-features-overview" style="margin-top: 2rem;">
            <h4><?php _e('Pro Features', 'ensemble'); ?></h4>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-top: 1rem;">
                
                <div class="es-feature-card" style="background: var(--es-surface-secondary); padding: 1rem; border-radius: 8px; border: 1px solid var(--es-border);">
                    <h5 style="margin: 0 0 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                        <span class="dashicons dashicons-update" style="color: var(--es-primary);"></span>
                        <?php _e('Recurring Events', 'ensemble'); ?>
                    </h5>
                    <p style="margin: 0; font-size: 0.85rem; color: var(--es-text-secondary);">
                        <?php _e('Wiederkehrende Events mit flexiblen Mustern.', 'ensemble'); ?>
                    </p>
                </div>
                
                <div class="es-feature-card" style="background: var(--es-surface-secondary); padding: 1rem; border-radius: 8px; border: 1px solid var(--es-border);">
                    <h5 style="margin: 0 0 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                        <span class="dashicons dashicons-download" style="color: var(--es-primary);"></span>
                        <?php _e('Import/Export', 'ensemble'); ?>
                    </h5>
                    <p style="margin: 0; font-size: 0.85rem; color: var(--es-text-secondary);">
                        <?php _e('Events importieren und exportieren inkl. iCal.', 'ensemble'); ?>
                    </p>
                </div>
                
                <div class="es-feature-card" style="background: var(--es-surface-secondary); padding: 1rem; border-radius: 8px; border: 1px solid var(--es-border);">
                    <h5 style="margin: 0 0 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                        <span class="dashicons dashicons-networking" style="color: var(--es-primary);"></span>
                        <?php _e('Related Events', 'ensemble'); ?>
                    </h5>
                    <p style="margin: 0; font-size: 0.85rem; color: var(--es-text-secondary);">
                        <?php _e('Verwandte Events automatisch anzeigen.', 'ensemble'); ?>
                    </p>
                </div>
                
                <div class="es-feature-card" style="background: var(--es-surface-secondary); padding: 1rem; border-radius: 8px; border: 1px solid var(--es-border);">
                    <h5 style="margin: 0 0 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                        <span class="dashicons dashicons-layout" style="color: var(--es-primary);"></span>
                        <?php _e('Premium Layouts', 'ensemble'); ?>
                    </h5>
                    <p style="margin: 0; font-size: 0.85rem; color: var(--es-text-secondary);">
                        <?php _e('Minimal, Magazine & Compact Templates.', 'ensemble'); ?>
                    </p>
                </div>
                
            </div>
            
            <?php if (!function_exists('ensemble_is_pro') || !ensemble_is_pro()): ?>
            <div class="es-pro-upgrade-prompt" style="margin-top: 1.5rem;">
                <h4><?php _e('Upgrade auf Pro', 'ensemble'); ?></h4>
                <p><?php _e('Schalte alle Premium-Features frei und erhalte Priority-Support.', 'ensemble'); ?></p>
                <a href="https://kraftwerk-mkt.com/ensemble-pro" target="_blank" class="button button-primary">
                    <?php _e('Pro kaufen', 'ensemble'); ?>
                </a>
            </div>
            <?php endif; ?>
        </div>
    
    <?php endif; ?>
    
    <!-- General Tab -->
    <?php if ($current_tab === 'general'): ?>
    
    <form method="post" action="">
        <?php wp_nonce_field('ensemble_settings'); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="ensemble_theme"><?php _e('Admin Theme', 'ensemble'); ?></label>
                </th>
                <td>
                    <select name="ensemble_theme" id="ensemble_theme">
                        <option value="dark" <?php selected($current_theme, 'dark'); ?>><?php _e('Dark', 'ensemble'); ?></option>
                        <option value="light" <?php selected($current_theme, 'light'); ?>><?php _e('Light', 'ensemble'); ?></option>
                    </select>
                    <p class="description"><?php _e('Choose your preferred admin interface theme', 'ensemble'); ?></p>
                </td>
            </tr>
            
            <!-- ✅ NEW: Post Type Selection -->
            <tr>
                <th scope="row">
                    <label for="ensemble_post_type"><?php _e('Event Post Type', 'ensemble'); ?></label>
                </th>
                <td>
                    <?php
                    // Get all public post types
                    $post_types = get_post_types(array('public' => true), 'objects');
                    ?>
                    <select name="ensemble_post_type" id="ensemble_post_type">
                        <?php foreach ($post_types as $pt): ?>
                            <?php if (!in_array($pt->name, array('attachment', 'revision', 'nav_menu_item'))): ?>
                                <option value="<?php echo esc_attr($pt->name); ?>" <?php selected($current_post_type, $pt->name); ?>>
                                    <?php echo esc_html($pt->label . ' (' . $pt->name . ')'); ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">
                        <?php _e('Select which Post Type to use for events. Default is Posts.', 'ensemble'); ?>
                        <br>
                        <strong><?php _e('Warning:', 'ensemble'); ?></strong>
                        <?php _e('Make sure your ACF fields are assigned to the selected Post Type.', 'ensemble'); ?>
                    </p>
                    
                    <?php
                    // Show current event count
                    $event_count = wp_count_posts($current_post_type);
                    $total = isset($event_count->publish) ? $event_count->publish : 0;
                    ?>
                    <p class="description">
                        💡 <?php printf(__('Currently using <strong>%s</strong> with <strong>%d</strong> published posts', 'ensemble'), $current_post_type, $total); ?>
                    </p>
                </td>
            </tr>
        </table>
        
        <h2><?php _e('Taxonomy Management', 'ensemble'); ?></h2>
        
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Event Categories', 'ensemble'); ?></th>
                <td>
                    <a href="<?php echo admin_url('admin.php?page=ensemble-taxonomies&tab=categories'); ?>" class="button">
                        <?php _e('Manage Event Categories', 'ensemble'); ?>
                    </a>
                    <p class="description"><?php _e('Organize your events by categories (Concert, Festival, Workshop, etc.)', 'ensemble'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Artist Genres', 'ensemble'); ?></th>
                <td>
                    <a href="<?php echo admin_url('admin.php?page=ensemble-taxonomies&tab=genres'); ?>" class="button">
                        <?php _e('Manage Genres', 'ensemble'); ?>
                    </a>
                    <p class="description"><?php _e('Classify artists by music genre (Rock, Jazz, Electronic, etc.)', 'ensemble'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Location Types', 'ensemble'); ?></th>
                <td>
                    <a href="<?php echo admin_url('admin.php?page=ensemble-taxonomies&tab=location-types'); ?>" class="button">
                        <?php _e('Manage Location Types', 'ensemble'); ?>
                    </a>
                    <p class="description"><?php _e('Categorize venues by type (Club, Concert Hall, Outdoor Stage, etc.)', 'ensemble'); ?></p>
                </td>
            </tr>
        </table>
        
        <h2><?php _e('System Status', 'ensemble'); ?></h2>
        
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Plugin Version', 'ensemble'); ?></th>
                <td><?php echo ENSEMBLE_VERSION; ?></td>
            </tr>
            <tr>
                <th scope="row"><?php _e('ACF Available', 'ensemble'); ?></th>
                <td>
                    <?php if ($acf_available): ?>
                        <span class="es-status-ok">✓ <?php _e('Yes', 'ensemble'); ?></span>
                    <?php else: ?>
                        <span class="es-status-warning">✗ <?php _e('No', 'ensemble'); ?></span>
                        <p class="description">
                            <?php _e('Advanced Custom Fields (ACF) plugin is required for full functionality.', 'ensemble'); ?>
                            <a href="https://wordpress.org/plugins/advanced-custom-fields/" target="_blank">
                                <?php _e('Install ACF', 'ensemble'); ?>
                            </a>
                        </p>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('ACF Fields Installed', 'ensemble'); ?></th>
                <td>
                    <?php if ($acf_installed): ?>
                        <span class="es-status-ok">✓ <?php _e('Yes', 'ensemble'); ?></span>
                    <?php else: ?>
                        <span class="es-status-warning">✗ <?php _e('No', 'ensemble'); ?></span>
                        <?php if ($acf_available): ?>
                            <p class="description"><?php _e('ACF fields will be installed automatically.', 'ensemble'); ?></p>
                        <?php endif; ?>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <input type="submit" name="es_save_settings" class="button button-primary" value="<?php _e('Save Settings', 'ensemble'); ?>">
        </p>
    </form>
    
    <?php endif; ?>
    
    <!-- Labels & Terms Tab -->
    <?php if ($current_tab === 'labels'): ?>
    
    <form method="post" action="">
        <?php wp_nonce_field('ensemble_labels'); ?>
        
        <div class="es-settings-container">
            
            <h2><?php _e('Customize Labels & Terminology', 'ensemble'); ?></h2>
            
            <p class="description" style="margin-bottom: 30px;">
                <?php _e('Customize how Ensemble refers to artists, locations, and events throughout the plugin. This helps adapt the plugin to your specific use case (clubs, churches, yoga studios, etc.).', 'ensemble'); ?>
            </p>
            
            <table class="form-table">
                <tbody>
                    
                    <!-- Usage Type -->
                    <tr>
                        <th scope="row">
                            <label><?php _e('Usage Type', 'ensemble'); ?></label>
                        </th>
                        <td>
                            <?php
                            $usage_type = get_option('ensemble_usage_type', 'default');
                            $usage_types = array(
                                'clubs' => __('Clubs & Konzerte', 'ensemble'),
                                'theater' => __('Theater & Kultur', 'ensemble'),
                                'church' => __('Kirche & Gemeinde', 'ensemble'),
                                'fitness' => __('Yoga & Fitness', 'ensemble'),
                                'education' => __('Workshops & Bildung', 'ensemble'),
                                'public' => __('Public facilities', 'ensemble'),
                                'mixed' => __('Sonstiges / Mischbetrieb', 'ensemble'),
                            );
                            ?>
                            <select name="usage_type" id="usage_type">
                                <?php foreach ($usage_types as $key => $label): ?>
                                    <option value="<?php echo esc_attr($key); ?>" <?php selected($usage_type, $key); ?>>
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">
                                <?php _e('This determines the default labels and terminology used throughout the plugin.', 'ensemble'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Artist Labels -->
                    <tr>
                        <th scope="row">
                            <label><?php _e('Artist / Performer Labels', 'ensemble'); ?></label>
                        </th>
                        <td>
                            <?php
                            $artist_singular = ES_Label_System::get_label('artist', false);
                            $artist_plural = ES_Label_System::get_label('artist', true);
                            ?>
                            <div style="margin-bottom: 15px;">
                                <label for="artist_label_singular" style="display: block; margin-bottom: 5px;">
                                    <?php _e('Singular', 'ensemble'); ?>
                                </label>
                                <input type="text" name="artist_label_singular" id="artist_label_singular" 
                                       value="<?php echo esc_attr($artist_singular); ?>" 
                                       class="regular-text" 
                                       placeholder="<?php _e('z.B. DJ, Trainer, Pfarrer', 'ensemble'); ?>">
                            </div>
                            <div>
                                <label for="artist_label_plural" style="display: block; margin-bottom: 5px;">
                                    <?php _e('Plural', 'ensemble'); ?>
                                </label>
                                <input type="text" name="artist_label_plural" id="artist_label_plural" 
                                       value="<?php echo esc_attr($artist_plural); ?>" 
                                       class="regular-text" 
                                       placeholder="<?php _e('z.B. DJs, Trainer:innen, Geistliche', 'ensemble'); ?>">
                            </div>
                            <p class="description">
                                <?php _e('How do you refer to the people performing at your events?', 'ensemble'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Location Labels -->
                    <tr>
                        <th scope="row">
                            <label><?php _e('Location / Venue Labels', 'ensemble'); ?></label>
                        </th>
                        <td>
                            <?php
                            $location_singular = ES_Label_System::get_label('location', false);
                            $location_plural = ES_Label_System::get_label('location', true);
                            ?>
                            <div style="margin-bottom: 15px;">
                                <label for="location_label_singular" style="display: block; margin-bottom: 5px;">
                                    <?php _e('Singular', 'ensemble'); ?>
                                </label>
                                <input type="text" name="location_label_singular" id="location_label_singular" 
                                       value="<?php echo esc_attr($location_singular); ?>" 
                                       class="regular-text" 
                                       placeholder="<?php _e('z.B. Venue, Studio, Kirche', 'ensemble'); ?>">
                            </div>
                            <div>
                                <label for="location_label_plural" style="display: block; margin-bottom: 5px;">
                                    <?php _e('Plural', 'ensemble'); ?>
                                </label>
                                <input type="text" name="location_label_plural" id="location_label_plural" 
                                       value="<?php echo esc_attr($location_plural); ?>" 
                                       class="regular-text" 
                                       placeholder="<?php _e('z.B. Venues, Studios, Kirchen', 'ensemble'); ?>">
                            </div>
                            <p class="description">
                                <?php _e('How do you refer to the places where events happen?', 'ensemble'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Reset to Onboarding -->
                    <tr>
                        <th scope="row">
                            <label><?php _e('Reset Configuration', 'ensemble'); ?></label>
                        </th>
                        <td>
                            <?php
                            $reset_url = wp_nonce_url(
                                admin_url('admin.php?page=ensemble-onboarding&reset=1'),
                                'ensemble_reset_onboarding'
                            );
                            ?>
                            <a href="<?php echo esc_url($reset_url); ?>" 
                               class="button button-secondary"
                               onclick="return confirm('<?php _e('This will reset all label configurations and restart the onboarding wizard. Continue?', 'ensemble'); ?>');">
                                <?php _e('Re-run Onboarding', 'ensemble'); ?>
                            </a>
                            <p class="description">
                                <?php _e('Start the setup wizard again to reconfigure all labels and settings.', 'ensemble'); ?>
                            </p>
                        </td>
                    </tr>
                    
                </tbody>
            </table>
            
        </div>
        
        <p class="submit">
            <input type="submit" name="es_save_labels" class="button button-primary" value="<?php _e('Save Label Settings', 'ensemble'); ?>">
        </p>
        
        <div class="es-notice es-notice-info" style="max-width: 800px;">
            <p>
                <strong><?php _e('Important:', 'ensemble'); ?></strong>
                <?php _e('After changing labels, please reload the page to see the changes reflected in the WordPress admin menu and throughout the plugin.', 'ensemble'); ?>
            </p>
        </div>
    </form>
    
    <?php endif; ?>
    
    
    <!-- Display Settings Tab -->
    <?php if ($current_tab === 'display'): 
        $display = function_exists('ensemble_get_display_settings') ? ensemble_get_display_settings() : array();
        $addon_options = function_exists('ensemble_get_addon_display_options') ? ensemble_get_addon_display_options() : array();
        $active_addons = get_option('ensemble_active_addons', array());
    ?>
    
    <form method="post" action="">
        <?php wp_nonce_field('ensemble_display_settings'); ?>
        
        <div class="es-settings-card">
            <h2><?php _e('Display Settings', 'ensemble'); ?></h2>
            <p class="description">
                <?php _e('Control which elements are displayed in event cards and on single event pages. You can also hide section headers for a cleaner look.', 'ensemble'); ?>
            </p>
            
            <!-- EVENT CARDS -->
            <div class="es-fields-section" style="margin-top: 30px;">
                <h3 class="es-section-title">
                    <span class="dashicons dashicons-grid-view" style="margin-right: 8px;"></span>
                    <?php _e('Event Cards', 'ensemble'); ?>
                </h3>
                <p class="description" style="margin-bottom: 16px; opacity: 0.7;">
                    <?php _e('Choose which elements to display in event cards (list/grid views).', 'ensemble'); ?>
                </p>
                
                <div class="es-fields-list">
                    <?php
                    $card_elements = array(
                        'image'    => array('label' => __('Featured Image', 'ensemble'), 'icon' => 'format-image'),
                        'title'    => array('label' => __('Title', 'ensemble'), 'icon' => 'heading'),
                        'date'     => array('label' => __('Date', 'ensemble'), 'icon' => 'calendar'),
                        'time'     => array('label' => __('Time', 'ensemble'), 'icon' => 'clock'),
                        'location' => array('label' => __('Location', 'ensemble'), 'icon' => 'location'),
                        'category' => array('label' => __('Category', 'ensemble'), 'icon' => 'category'),
                        'excerpt'  => array('label' => __('Excerpt', 'ensemble'), 'icon' => 'text'),
                        'price'    => array('label' => __('Price', 'ensemble'), 'icon' => 'tickets-alt'),
                        'status'   => array('label' => __('Status Badge', 'ensemble'), 'icon' => 'flag'),
                        'artists'  => array('label' => __('Artists Preview', 'ensemble'), 'icon' => 'groups'),
                    );
                    
                    foreach ($card_elements as $key => $element):
                        $checked = !empty($display['cards'][$key]);
                    ?>
                    <div class="es-field-toggle-item <?php echo $checked ? 'es-field-enabled' : ''; ?>">
                        <div class="es-field-info">
                            <span class="dashicons dashicons-<?php echo esc_attr($element['icon']); ?>" style="opacity: 0.7;"></span>
                            <span class="es-field-name"><?php echo esc_html($element['label']); ?></span>
                        </div>
                        <label class="es-toggle-switch">
                            <input type="checkbox" 
                                   name="display[cards][<?php echo esc_attr($key); ?>]" 
                                   value="1" 
                                   <?php checked($checked); ?>>
                            <span class="es-toggle-slider"></span>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- SINGLE EVENT PAGE -->
            <div class="es-fields-section" style="margin-top: 30px;">
                <h3 class="es-section-title">
                    <span class="dashicons dashicons-media-default" style="margin-right: 8px;"></span>
                    <?php _e('Single Event Page', 'ensemble'); ?>
                </h3>
                
                <!-- Main Sections -->
                <h4 style="font-size: 13px; margin: 20px 0 12px; opacity: 0.6; text-transform: uppercase; letter-spacing: 0.5px;">
                    <?php _e('Main Sections', 'ensemble'); ?>
                </h4>
                <div class="es-fields-list">
                    <?php
                    $sections = array(
                        'meta'        => array('label' => __('Event Meta (Date, Location, etc.)', 'ensemble'), 'icon' => 'info'),
                        'description' => array('label' => __('Description', 'ensemble'), 'icon' => 'text'),
                        'artists'     => array('label' => __('Artists / Lineup', 'ensemble'), 'icon' => 'groups'),
                        'location'    => array('label' => __('Location Details', 'ensemble'), 'icon' => 'location'),
                    );
                    
                    foreach ($sections as $key => $section):
                        $checked = !empty($display['single']['sections'][$key]);
                    ?>
                    <div class="es-field-toggle-item <?php echo $checked ? 'es-field-enabled' : ''; ?>">
                        <div class="es-field-info">
                            <span class="dashicons dashicons-<?php echo esc_attr($section['icon']); ?>" style="opacity: 0.7;"></span>
                            <span class="es-field-name"><?php echo esc_html($section['label']); ?></span>
                        </div>
                        <label class="es-toggle-switch">
                            <input type="checkbox" 
                                   name="display[single][sections][<?php echo esc_attr($key); ?>]" 
                                   value="1" 
                                   <?php checked($checked); ?>>
                            <span class="es-toggle-slider"></span>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Section Headers -->
                <h4 style="font-size: 13px; margin: 30px 0 12px; opacity: 0.6; text-transform: uppercase; letter-spacing: 0.5px;">
                    <span class="dashicons dashicons-heading" style="margin-right: 6px; font-size: 14px;"></span>
                    <?php _e('Section Headers', 'ensemble'); ?>
                    <span style="font-weight: normal; text-transform: none; font-size: 12px; opacity: 0.7; margin-left: 8px;">
                        — <?php _e('Disable for a cleaner look', 'ensemble'); ?>
                    </span>
                </h4>
                <div class="es-fields-list">
                    <?php
                    $headers = array(
                        'artists'         => __('Show "Artists" Header', 'ensemble'),
                        'location'        => __('Show "Location" Header', 'ensemble'),
                        'description'     => __('Show "Description" Header', 'ensemble'),
                        'additional_info' => __('Show "Additional Info" Header', 'ensemble'),
                    );
                    
                    foreach ($headers as $key => $label):
                        $checked = !empty($display['single']['headers'][$key]);
                    ?>
                    <div class="es-field-toggle-item <?php echo $checked ? 'es-field-enabled' : ''; ?>">
                        <div class="es-field-info">
                            <span class="dashicons dashicons-heading" style="opacity: 0.5;"></span>
                            <span class="es-field-name"><?php echo esc_html($label); ?></span>
                        </div>
                        <label class="es-toggle-switch">
                            <input type="checkbox" 
                                   name="display[single][headers][<?php echo esc_attr($key); ?>]" 
                                   value="1" 
                                   <?php checked($checked); ?>>
                            <span class="es-toggle-slider"></span>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- LineUp Options -->
                <h4 style="font-size: 13px; margin: 30px 0 12px; opacity: 0.6; text-transform: uppercase; letter-spacing: 0.5px;">
                    <span class="dashicons dashicons-groups" style="margin-right: 6px; font-size: 14px;"></span>
                    <?php _e('LineUp Options', 'ensemble'); ?>
                </h4>
                <div class="es-fields-list">
                    <?php
                    $lineup_options = array(
                        'artist_image'      => array('label' => __('Artist Picture', 'ensemble'), 'icon' => 'format-image'),
                        'artist_genre'      => array('label' => __('Artist Genre', 'ensemble'), 'icon' => 'tag'),
                        'artist_references' => array('label' => __('Artist References', 'ensemble'), 'icon' => 'info-outline'),
                        'artist_time'       => array('label' => __('Artist Time', 'ensemble'), 'icon' => 'clock'),
                    );
                    
                    foreach ($lineup_options as $key => $option):
                        $checked = !isset($display['single']['lineup'][$key]) || !empty($display['single']['lineup'][$key]);
                    ?>
                    <div class="es-field-toggle-item <?php echo $checked ? 'es-field-enabled' : ''; ?>">
                        <div class="es-field-info">
                            <span class="dashicons dashicons-<?php echo esc_attr($option['icon']); ?>" style="opacity: 0.7;"></span>
                            <span class="es-field-name"><?php echo esc_html($option['label']); ?></span>
                        </div>
                        <label class="es-toggle-switch">
                            <input type="checkbox" 
                                   name="display[single][lineup][<?php echo esc_attr($key); ?>]" 
                                   value="1" 
                                   <?php checked($checked); ?>>
                            <span class="es-toggle-slider"></span>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Meta Items -->
                <h4 style="font-size: 13px; margin: 30px 0 12px; opacity: 0.6; text-transform: uppercase; letter-spacing: 0.5px;">
                    <?php _e('Meta Items (Event Header)', 'ensemble'); ?>
                </h4>
                <div class="es-fields-list">
                    <?php
                    $meta_items = array(
                        'date'     => array('label' => __('Date', 'ensemble'), 'icon' => 'calendar'),
                        'time'     => array('label' => __('Time', 'ensemble'), 'icon' => 'clock'),
                        'venue'    => array('label' => __('Venue Name', 'ensemble'), 'icon' => 'building'),
                        'category' => array('label' => __('Category', 'ensemble'), 'icon' => 'category'),
                        'price'    => array('label' => __('Price', 'ensemble'), 'icon' => 'tickets-alt'),
                        'status'   => array('label' => __('Status', 'ensemble'), 'icon' => 'flag'),
                    );
                    
                    foreach ($meta_items as $key => $item):
                        $checked = !empty($display['single']['meta_items'][$key]);
                    ?>
                    <div class="es-field-toggle-item <?php echo $checked ? 'es-field-enabled' : ''; ?>">
                        <div class="es-field-info">
                            <span class="dashicons dashicons-<?php echo esc_attr($item['icon']); ?>" style="opacity: 0.7;"></span>
                            <span class="es-field-name"><?php echo esc_html($item['label']); ?></span>
                        </div>
                        <label class="es-toggle-switch">
                            <input type="checkbox" 
                                   name="display[single][meta_items][<?php echo esc_attr($key); ?>]" 
                                   value="1" 
                                   <?php checked($checked); ?>>
                            <span class="es-toggle-slider"></span>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Location Display Options -->
                <h4 style="font-size: 13px; margin: 30px 0 12px; opacity: 0.6; text-transform: uppercase; letter-spacing: 0.5px;">
                    <span class="dashicons dashicons-location" style="margin-right: 6px; font-size: 14px;"></span>
                    <?php _e('Location Display', 'ensemble'); ?>
                </h4>
                <div class="es-fields-list">
                    <?php 
                    $location_display = isset($display['single']['location_display']) ? $display['single']['location_display'] : 'full';
                    $location_options = array(
                        'full'      => __('Full Address (Name, Street, ZIP + City)', 'ensemble'),
                        'name_city' => __('Name and City only', 'ensemble'),
                        'name_only' => __('Name only', 'ensemble'),
                    );
                    ?>
                    <div class="es-field-toggle-item es-field-enabled" style="flex-direction: column; align-items: flex-start; gap: 12px;">
                        <?php foreach ($location_options as $value => $label): ?>
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; width: 100%;">
                            <input type="radio" 
                                   name="display[single][location_display]" 
                                   value="<?php echo esc_attr($value); ?>"
                                   <?php checked($location_display, $value); ?>
                                   style="margin: 0;">
                            <span><?php echo esc_html($label); ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- ADD-ON SECTIONS -->
            <?php if (!empty($addon_options)): ?>
            <div class="es-fields-section" style="margin-top: 30px;">
                <h3 class="es-section-title">
                    <span class="dashicons dashicons-admin-plugins" style="margin-right: 8px;"></span>
                    <?php _e('Add-on Sections', 'ensemble'); ?>
                </h3>
                <p class="description" style="margin-bottom: 16px; opacity: 0.7;">
                    <?php _e('Control visibility and headers for add-on sections. Inactive add-ons are grayed out.', 'ensemble'); ?>
                </p>
                
                <div class="es-fields-list">
                    <?php 
                    $addon_map = array(
                        'countdown'      => 'countdown',
                        'tickets'        => 'tickets', 
                        'catalog'        => 'catalog',
                        'maps'           => 'maps',
                        'gallery'        => 'gallery-pro',
                        'social_sharing' => 'social-sharing',
                        'related_events' => 'related-events',
                        'reservations'   => 'reservations',
                    );
                    
                    foreach ($addon_options as $addon_key => $addon_config):
                        $addon_slug = isset($addon_map[$addon_key]) ? $addon_map[$addon_key] : $addon_key;
                        $is_active = in_array($addon_slug, $active_addons);
                        $show_checked = !empty($display['addons'][$addon_key]['show']);
                        $header_checked = !empty($display['addons'][$addon_key]['header']);
                    ?>
                    <div class="es-field-toggle-item es-addon-row <?php echo !$is_active ? 'es-field-disabled' : ($show_checked ? 'es-field-enabled' : ''); ?>">
                        <div class="es-field-info" style="min-width: 180px;">
                            <span class="dashicons dashicons-<?php echo esc_attr($addon_config['icon']); ?>" style="opacity: 0.7;"></span>
                            <span class="es-field-name">
                                <?php echo esc_html($addon_config['label']); ?>
                                <?php if (!$is_active): ?>
                                <small style="opacity: 0.5; font-size: 11px;">(<?php _e('inactive', 'ensemble'); ?>)</small>
                                <?php endif; ?>
                            </span>
                        </div>
                        
                        <div style="display: flex; align-items: center; gap: 24px;">
                            <div class="es-addon-toggle-group">
                                <span style="font-size: 12px; opacity: 0.6; margin-right: 8px;"><?php _e('Show', 'ensemble'); ?></span>
                                <label class="es-toggle-switch">
                                    <input type="checkbox" 
                                           name="display[addons][<?php echo esc_attr($addon_key); ?>][show]" 
                                           value="1" 
                                           <?php checked($show_checked); ?>
                                           <?php disabled(!$is_active); ?>>
                                    <span class="es-toggle-slider"></span>
                                </label>
                            </div>
                            
                            <div class="es-addon-toggle-group">
                                <span style="font-size: 12px; opacity: 0.6; margin-right: 8px;"><?php _e('Header', 'ensemble'); ?></span>
                                <label class="es-toggle-switch">
                                    <input type="checkbox" 
                                           name="display[addons][<?php echo esc_attr($addon_key); ?>][header]" 
                                           value="1" 
                                           <?php checked($header_checked); ?>
                                           <?php disabled(!$is_active); ?>>
                                    <span class="es-toggle-slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--es-border, rgba(255,255,255,0.08));">
                <button type="submit" name="es_save_display_settings" class="button button-primary button-large">
                    <span class="dashicons dashicons-yes" style="margin-top: 4px;"></span>
                    <?php _e('Save Display Settings', 'ensemble'); ?>
                </button>
            </div>
        </div>
    </form>
    
    <?php endif; ?>
    
    
    <!-- Custom Fonts Tab -->
    <?php if ($current_tab === 'custom-fonts'): 
        $is_pro = function_exists('ensemble_is_pro') && ensemble_is_pro();
        $font_manager = class_exists('ES_Font_Manager') ? ES_Font_Manager::instance() : null;
        $custom_fonts = ($is_pro && $font_manager) ? $font_manager->get_custom_fonts() : array();
    ?>
    
    <div class="es-settings-intro">
        <h3><?php _e('Custom Fonts', 'ensemble'); ?></h3>
        <p class="description">
            <?php _e('Upload your own fonts to use in the Designer. Uploaded fonts will appear in the font dropdowns.', 'ensemble'); ?>
        </p>
    </div>
    
    <?php if ($is_pro): ?>
    
    <div class="es-custom-fonts-card">
        
        <h4>
            <span class="dashicons dashicons-text"></span>
            <?php _e('Uploaded Fonts', 'ensemble'); ?>
        </h4>
        
        <?php if (!empty($custom_fonts)): ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 40%;"><?php _e('Font Name', 'ensemble'); ?></th>
                    <th style="width: 30%;"><?php _e('Preview', 'ensemble'); ?></th>
                    <th style="width: 15%;"><?php _e('Weight', 'ensemble'); ?></th>
                    <th style="width: 15%;"><?php _e('Actions', 'ensemble'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($custom_fonts as $index => $font): ?>
                <tr>
                    <td><strong><?php echo esc_html($font['name']); ?></strong></td>
                    <td style="font-family: '<?php echo esc_attr($font['name']); ?>', sans-serif; font-size: 18px;">
                        Aa Bb Cc 123
                    </td>
                    <td><?php echo esc_html($font['weight']); ?></td>
                    <td>
                        <button type="button" class="button button-small es-delete-font" data-index="<?php echo $index; ?>" data-name="<?php echo esc_attr($font['name']); ?>">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="es-no-fonts-message">
            <?php _e('No custom fonts uploaded yet. Upload your first font below.', 'ensemble'); ?>
        </div>
        <?php endif; ?>
        
        <div class="es-upload-section">
            <h4>
                <span class="dashicons dashicons-upload"></span>
                <?php _e('Upload New Font', 'ensemble'); ?>
            </h4>
            <p class="description">
                <?php _e('Supported formats: .woff2, .woff, .ttf, .otf (WOFF2 recommended for best performance)', 'ensemble'); ?>
            </p>
            
            <form id="es-font-upload-form" enctype="multipart/form-data">
                <div class="es-upload-form-row">
                    <div class="es-upload-field">
                        <label><?php _e('Font File', 'ensemble'); ?></label>
                        <input type="file" name="font_file" id="es-font-file-input" accept=".woff,.woff2,.ttf,.otf" required>
                    </div>
                    <div class="es-upload-field">
                        <label><?php _e('Font Name', 'ensemble'); ?></label>
                        <input type="text" name="font_name" id="es-font-name-input" placeholder="<?php esc_attr_e('e.g. My Brand Font', 'ensemble'); ?>" required>
                    </div>
                    <div class="es-upload-field">
                        <label><?php _e('Weight', 'ensemble'); ?></label>
                        <select name="font_weight" id="es-font-weight-input">
                            <option value="300"><?php _e('Light (300)', 'ensemble'); ?></option>
                            <option value="400" selected><?php _e('Regular (400)', 'ensemble'); ?></option>
                            <option value="500"><?php _e('Medium (500)', 'ensemble'); ?></option>
                            <option value="600"><?php _e('Semi-Bold (600)', 'ensemble'); ?></option>
                            <option value="700"><?php _e('Bold (700)', 'ensemble'); ?></option>
                        </select>
                    </div>
                    <div class="es-upload-field es-upload-submit">
                        <button type="submit" class="button button-primary">
                            <span class="dashicons dashicons-upload"></span>
                            <?php _e('Upload Font', 'ensemble'); ?>
                        </button>
                    </div>
                </div>
            </form>
            <div id="es-upload-status"></div>
        </div>
        
        <div class="es-font-tips">
            <strong><?php _e('Tips:', 'ensemble'); ?></strong>
            <ul>
                <li><?php _e('Use WOFF2 format for best browser support and file size', 'ensemble'); ?></li>
                <li><?php _e('Upload multiple weights of the same font family separately', 'ensemble'); ?></li>
                <li><?php _e('After upload, the font appears in Designer → Typography', 'ensemble'); ?></li>
            </ul>
        </div>
        
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Upload form
        $('#es-font-upload-form').on('submit', function(e) {
            e.preventDefault();
            
            var formData = new FormData();
            formData.append('action', 'es_upload_custom_font');
            formData.append('nonce', '<?php echo wp_create_nonce("ensemble_admin"); ?>');
            formData.append('font_file', $('#es-font-file-input')[0].files[0]);
            formData.append('font_name', $('#es-font-name-input').val());
            formData.append('font_weight', $('#es-font-weight-input').val());
            
            $('#es-upload-status').html('<span style="color: #666;"><span class="spinner is-active" style="float: none; margin: 0 5px 0 0;"></span><?php _e("Uploading...", "ensemble"); ?></span>').show();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        $('#es-upload-status').html('<span style="color: #46b450;">✓ <?php _e("Font uploaded successfully!", "ensemble"); ?></span>');
                        setTimeout(function() { location.reload(); }, 1000);
                    } else {
                        $('#es-upload-status').html('<span style="color: #dc3232;">✗ ' + (response.data || '<?php _e("Upload failed", "ensemble"); ?>') + '</span>');
                    }
                },
                error: function() {
                    $('#es-upload-status').html('<span style="color: #dc3232;">✗ <?php _e("Upload failed", "ensemble"); ?></span>');
                }
            });
        });
        
        // Auto-fill font name from file
        $('#es-font-file-input').on('change', function() {
            var fileName = this.files[0]?.name || '';
            var fontName = fileName.replace(/\.[^/.]+$/, '').replace(/[-_]/g, ' ');
            if (fontName && !$('#es-font-name-input').val()) {
                $('#es-font-name-input').val(fontName);
            }
        });
        
        // Delete font
        $('.es-delete-font').on('click', function() {
            var index = $(this).data('index');
            var name = $(this).data('name');
            
            if (!confirm('<?php _e("Delete font", "ensemble"); ?> "' + name + '"?')) return;
            
            $.post(ajaxurl, {
                action: 'es_remove_custom_font',
                nonce: '<?php echo wp_create_nonce("ensemble_admin"); ?>',
                index: index
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data || '<?php _e("Delete failed", "ensemble"); ?>');
                }
            });
        });
    });
    </script>
    
    <?php else: ?>
    
    <!-- Pro Feature Promo -->
    <div class="es-pro-promo">
        <span class="dashicons dashicons-lock"></span>
        <h3><?php _e('Custom Fonts - Pro Feature', 'ensemble'); ?></h3>
        <p>
            <?php _e('Upload and use your own brand fonts in Ensemble. Supports WOFF2, WOFF, TTF, and OTF formats.', 'ensemble'); ?>
        </p>
        <a href="<?php echo admin_url('admin.php?page=ensemble-settings&tab=license'); ?>" class="button button-primary button-hero">
            <?php _e('Upgrade to Pro', 'ensemble'); ?>
        </a>
    </div>
    
    <?php endif; ?>
    
    <?php endif; ?>
    
    
    <!-- Link Behavior Tab -->
    <?php if ($current_tab === 'links'): 
        // Normalize to '1' or '0' - '0' and false mean disabled, everything else means enabled
        $raw = get_option('ensemble_link_artists', '1');
        $link_artists = ($raw === '0' || $raw === false) ? '0' : '1';
        
        $artist_link_target = get_option('ensemble_artist_link_target', 'post');
        
        $raw = get_option('ensemble_artist_link_new_tab', '1');
        $artist_link_new_tab = ($raw === '0' || $raw === false) ? '0' : '1';
        
        $raw = get_option('ensemble_link_locations', '1');
        $link_locations = ($raw === '0' || $raw === false) ? '0' : '1';
        
        $location_link_target = get_option('ensemble_location_link_target', 'post');
        
        $raw = get_option('ensemble_location_link_new_tab', '1');
        $location_link_new_tab = ($raw === '0' || $raw === false) ? '0' : '1';
        
        // Get labels
        $artist_label = get_option('ensemble_label_artist_singular', __('Artist', 'ensemble'));
        $artist_label_plural = get_option('ensemble_label_artist_plural', __('Artists', 'ensemble'));
        $location_label = get_option('ensemble_label_location_singular', __('Location', 'ensemble'));
        $location_label_plural = get_option('ensemble_label_location_plural', __('Locations', 'ensemble'));
    ?>
    
    <form method="post" action="">
        <?php wp_nonce_field('ensemble_link_settings'); ?>
        
        <div class="es-settings-card">
            <h2><?php _e('Link Behavior in Event Detail Pages', 'ensemble'); ?></h2>
            <p class="description" style="margin-bottom: 30px;">
                <?php _e('Control how Artists and Locations are linked when displayed on event pages. You can disable linking entirely or configure where links should point to.', 'ensemble'); ?>
            </p>
            
            <div class="es-form-grid" style="display: grid; gap: 30px; max-width: 700px;">
                
                <!-- Artists Linking -->
                <div class="es-form-row">
                    <div class="es-form-icon">
                        <?php echo ES_Icons::get('microphone'); ?>
                    </div>
                    <div class="es-form-content">
                        <label class="es-toggle es-toggle--reverse es-toggle--block">
                            <input type="checkbox" 
                                   name="link_artists" 
                                   value="1" 
                                   <?php checked($link_artists, '1'); ?>
                                   id="link_artists_toggle">
                            <span class="es-toggle-track"></span>
                            <span class="es-toggle-label">
                                <?php printf(__('Link %s to their detail pages', 'ensemble'), $artist_label_plural); ?>
                                <small><?php printf(__('When enabled, %s names on event pages will link to their individual pages or websites.', 'ensemble'), strtolower($artist_label)); ?></small>
                            </span>
                        </label>
                    </div>
                </div>
                
                <!-- Artist Link Target (only visible when artists linking is enabled) -->
                <div class="es-form-row es-artist-link-options" id="artist_link_options" style="margin-left: 40px; padding-left: 20px; border-left: 3px solid var(--ensemble-primary, #667eea);">
                    <div class="es-form-content">
                        <label style="display: block; margin-bottom: 12px; font-weight: 500;">
                            <?php _e('Artist Link Target', 'ensemble'); ?>
                        </label>
                        
                        <div class="es-radio-group" style="display: flex; flex-direction: column; gap: 12px;">
                            <label class="es-radio-label" style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer;">
                                <input type="radio" 
                                       name="artist_link_target" 
                                       value="post" 
                                       <?php checked($artist_link_target, 'post'); ?>
                                       style="margin-top: 3px;">
                                <span>
                                    <strong><?php printf(__('%s Detail Page', 'ensemble'), $artist_label); ?></strong>
                                    <span class="description" style="display: block; color: #666; font-size: 13px;">
                                        <?php printf(__('Links to the internal %s page within your website.', 'ensemble'), strtolower($artist_label)); ?>
                                    </span>
                                </span>
                            </label>
                            
                            <label class="es-radio-label" style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer;">
                                <input type="radio" 
                                       name="artist_link_target" 
                                       value="website" 
                                       <?php checked($artist_link_target, 'website'); ?>
                                       style="margin-top: 3px;">
                                <span>
                                    <strong><?php printf(__('%s Website (External)', 'ensemble'), $artist_label); ?></strong>
                                    <span class="description" style="display: block; color: #666; font-size: 13px;">
                                        <?php printf(__('Links to the %s\'s website or first social media link if available, otherwise falls back to detail page.', 'ensemble'), strtolower($artist_label)); ?>
                                    </span>
                                </span>
                            </label>
                        </div>
                        
                        <div class="es-external-link-options" id="artist_external_link_options" style="margin-top: 16px; padding: 12px; background: var(--ensemble-card-bg, #f7fafc); border-radius: 8px;">
                            <label class="es-toggle">
                                <input type="checkbox" 
                                       name="artist_link_new_tab" 
                                       value="1" 
                                       <?php checked($artist_link_new_tab, '1'); ?>>
                                <span class="es-toggle-track"></span>
                                <span class="es-toggle-label"><?php _e('Open external links in new tab', 'ensemble'); ?></span>
                            </label>
                        </div>
                        
                        <div class="es-notice es-notice-info" style="margin-top: 16px;">
                            <p style="margin: 0;">
                                <strong><?php _e('Tip:', 'ensemble'); ?></strong>
                                <?php printf(__('The plugin looks for "Website" field first, then social media links (Instagram, Facebook, etc.) in the %s Manager.', 'ensemble'), $artist_label); ?>
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Locations Linking -->
                <div class="es-form-row">
                    <div class="es-form-icon">
                        <?php echo ES_Icons::get('location'); ?>
                    </div>
                    <div class="es-form-content">
                        <label class="es-toggle es-toggle--reverse es-toggle--block">
                            <input type="checkbox" 
                                   name="link_locations" 
                                   value="1" 
                                   <?php checked($link_locations, '1'); ?>
                                   id="link_locations_toggle">
                            <span class="es-toggle-track"></span>
                            <span class="es-toggle-label">
                                <?php printf(__('Link %s to their detail pages', 'ensemble'), $location_label_plural); ?>
                                <small><?php printf(__('When enabled, %s names on event pages will be clickable links.', 'ensemble'), strtolower($location_label)); ?></small>
                            </span>
                        </label>
                    </div>
                </div>
                
                <!-- Location Link Target (only visible when locations linking is enabled) -->
                <div class="es-form-row es-location-link-options" id="location_link_options" style="margin-left: 40px; padding-left: 20px; border-left: 3px solid var(--ensemble-primary, #667eea);">
                    <div class="es-form-content">
                        <label style="display: block; margin-bottom: 12px; font-weight: 500;">
                            <?php _e('Location Link Target', 'ensemble'); ?>
                        </label>
                        
                        <div class="es-radio-group" style="display: flex; flex-direction: column; gap: 12px;">
                            <label class="es-radio-label" style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer;">
                                <input type="radio" 
                                       name="location_link_target" 
                                       value="post" 
                                       <?php checked($location_link_target, 'post'); ?>
                                       style="margin-top: 3px;">
                                <span>
                                    <strong><?php printf(__('%s Detail Page', 'ensemble'), $location_label); ?></strong>
                                    <span class="description" style="display: block; color: #666; font-size: 13px;">
                                        <?php printf(__('Links to the internal %s page within your website.', 'ensemble'), strtolower($location_label)); ?>
                                    </span>
                                </span>
                            </label>
                            
                            <label class="es-radio-label" style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer;">
                                <input type="radio" 
                                       name="location_link_target" 
                                       value="website" 
                                       <?php checked($location_link_target, 'website'); ?>
                                       style="margin-top: 3px;">
                                <span>
                                    <strong><?php printf(__('%s Website (External)', 'ensemble'), $location_label); ?></strong>
                                    <span class="description" style="display: block; color: #666; font-size: 13px;">
                                        <?php printf(__('Links to the %s\'s own website if available, otherwise falls back to detail page.', 'ensemble'), strtolower($location_label)); ?>
                                    </span>
                                </span>
                            </label>
                        </div>
                        
                        <div class="es-external-link-options" id="location_external_link_options" style="margin-top: 16px; padding: 12px; background: var(--ensemble-card-bg, #f7fafc); border-radius: 8px;">
                            <label class="es-toggle">
                                <input type="checkbox" 
                                       name="location_link_new_tab" 
                                       value="1" 
                                       <?php checked($location_link_new_tab, '1'); ?>>
                                <span class="es-toggle-track"></span>
                                <span class="es-toggle-label"><?php _e('Open external links in new tab', 'ensemble'); ?></span>
                            </label>
                        </div>
                        
                        <div class="es-notice es-notice-info" style="margin-top: 16px;">
                            <p style="margin: 0;">
                                <strong><?php _e('Tip:', 'ensemble'); ?></strong>
                                <?php printf(__('Make sure your %s have a "Website" field filled in. The plugin looks for fields named: %s', 'ensemble'), strtolower($location_label_plural), '<code>website</code>, <code>location_website</code>'); ?>
                            </p>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
        
        <p class="submit" style="margin-top: 30px;">
            <input type="submit" name="es_save_link_settings" class="button button-primary" value="<?php _e('Save Link Settings', 'ensemble'); ?>">
        </p>
    </form>
    
    <script>
    jQuery(document).ready(function($) {
        // Artist link options toggle
        function toggleArtistOptions() {
            var isEnabled = $('#link_artists_toggle').is(':checked');
            $('#artist_link_options').toggle(isEnabled);
        }
        
        function toggleArtistExternalOptions() {
            var isExternal = $('input[name="artist_link_target"]:checked').val() === 'website';
            $('#artist_external_link_options').toggle(isExternal);
        }
        
        // Location link options toggle
        function toggleLocationOptions() {
            var isEnabled = $('#link_locations_toggle').is(':checked');
            $('#location_link_options').toggle(isEnabled);
        }
        
        function toggleLocationExternalOptions() {
            var isExternal = $('input[name="location_link_target"]:checked').val() === 'website';
            $('#location_external_link_options').toggle(isExternal);
        }
        
        // Initial state
        toggleArtistOptions();
        toggleArtistExternalOptions();
        toggleLocationOptions();
        toggleLocationExternalOptions();
        
        // On change
        $('#link_artists_toggle').on('change', toggleArtistOptions);
        $('input[name="artist_link_target"]').on('change', toggleArtistExternalOptions);
        $('#link_locations_toggle').on('change', toggleLocationOptions);
        $('input[name="location_link_target"]').on('change', toggleLocationExternalOptions);
    });
    </script>
    
    <?php endif; ?>
    
    <!-- Field Mapping Tab -->
    <?php if ($current_tab === 'field-mapping'): ?>
    
    <form method="post" action="" id="es-field-mapping-form">
        <?php wp_nonce_field('ensemble_field_mapping'); ?>
        
        <div class="es-field-mapping-container">
            
            <h2><?php _e('Map Fields to Ensemble Standard Fields', 'ensemble'); ?></h2>
            
            <p class="description" style="margin-bottom: 30px;">
                <?php _e('Map your custom ACF fields or native meta fields to Ensemble\'s standard fields. This ensures the Event Wizard works correctly with your field configuration.', 'ensemble'); ?>
                <br>
                <strong><?php _e('Important:', 'ensemble'); ?></strong> <?php _e('Leave empty to use Ensemble\'s default field names.', 'ensemble'); ?>
            </p>
            
            <?php if (empty($all_available_fields)): ?>
                <div class="notice notice-warning inline">
                    <p>
                        <?php _e('No custom fields or meta fields found in this post type.', 'ensemble'); ?>
                        <?php if ($acf_available): ?>
                            <a href="<?php echo admin_url('edit.php?post_type=acf-field-group'); ?>" target="_blank">
                                <?php _e('Create ACF Fields', 'ensemble'); ?>
                            </a>
                        <?php endif; ?>
                    </p>
                </div>
            <?php else: ?>
            
            <?php
            // Define standard Ensemble fields
            $standard_fields = array(
                // === EVENT FIELDS (Core) ===
                'event_date' => array(
                    'label' => __('Event Date', 'ensemble'),
                    'description' => __('The main event date', 'ensemble'),
                    'icon' => ES_Icons::get('calendar'),
                    'category' => 'event_core',
                ),
                'event_time' => array(
                    'label' => __('Start Time', 'ensemble'),
                    'description' => __('Event start time', 'ensemble'),
                    'icon' => ES_Icons::get('clock'),
                    'category' => 'event_core',
                ),
                'event_time_end' => array(
                    'label' => __('End Time', 'ensemble'),
                    'description' => __('Event end time', 'ensemble'),
                    'icon' => ES_Icons::get('clock'),
                    'category' => 'event_core',
                ),
                'event_end_date' => array(
                    'label' => __('End Date', 'ensemble'),
                    'description' => __('Event end date (for multi-day events)', 'ensemble'),
                    'icon' => ES_Icons::get('calendar'),
                    'category' => 'event_core',
                ),
                'event_description' => array(
                    'label' => __('Description', 'ensemble'),
                    'description' => __('Event description/details', 'ensemble'),
                    'icon' => ES_Icons::get('edit'),
                    'category' => 'event_core',
                ),
                'event_location' => array(
                    'label' => __('Location', 'ensemble'),
                    'description' => __('Event location/venue (Post ID)', 'ensemble'),
                    'icon' => ES_Icons::get('location'),
                    'category' => 'event_core',
                ),
                'event_artist' => array(
                    'label' => __('Artists/Performers', 'ensemble'),
                    'description' => __('Artists or performers (Post ID or array of IDs)', 'ensemble'),
                    'icon' => ES_Icons::get('artist'),
                    'category' => 'event_core',
                ),
                'event_price' => array(
                    'label' => __('Price', 'ensemble'),
                    'description' => __('Event price/cost', 'ensemble'),
                    'icon' => ES_Icons::get('ticket'),
                    'category' => 'event_core',
                ),
                'event_ticket_url' => array(
                    'label' => __('Ticket URL', 'ensemble'),
                    'description' => __('Link to ticket sales or registration', 'ensemble'),
                    'icon' => ES_Icons::get('ticket'),
                    'category' => 'event_core',
                ),
                
                // === LOCATION FIELDS ===
                'location_address' => array(
                    'label' => __('Location Address', 'ensemble'),
                    'description' => __('Street address of the venue', 'ensemble'),
                    'icon' => ES_Icons::get('location'),
                    'category' => 'location',
                ),
                'location_city' => array(
                    'label' => __('Location City', 'ensemble'),
                    'description' => __('City of the venue', 'ensemble'),
                    'icon' => ES_Icons::get('map'),
                    'category' => 'location',
                ),
                'location_state' => array(
                    'label' => __('Location State/Region', 'ensemble'),
                    'description' => __('State or region', 'ensemble'),
                    'icon' => ES_Icons::get('map'),
                    'category' => 'location',
                ),
                'location_zip' => array(
                    'label' => __('Location ZIP/Postal Code', 'ensemble'),
                    'description' => __('Postal code', 'ensemble'),
                    'icon' => ES_Icons::get('map_pin'),
                    'category' => 'location',
                ),
                'location_country' => array(
                    'label' => __('Location Country', 'ensemble'),
                    'description' => __('Country', 'ensemble'),
                    'icon' => ES_Icons::get('map'),
                    'category' => 'location',
                ),
                'location_phone' => array(
                    'label' => __('Location Phone', 'ensemble'),
                    'description' => __('Venue phone number', 'ensemble'),
                    'icon' => ES_Icons::get('info'),
                    'category' => 'location',
                ),
                'location_email' => array(
                    'label' => __('Location Email', 'ensemble'),
                    'description' => __('Venue email address', 'ensemble'),
                    'icon' => ES_Icons::get('info'),
                    'category' => 'location',
                ),
                'location_website' => array(
                    'label' => __('Location Website', 'ensemble'),
                    'description' => __('Venue website URL', 'ensemble'),
                    'icon' => ES_Icons::get('info'),
                    'category' => 'location',
                ),
                'location_capacity' => array(
                    'label' => __('Location Capacity', 'ensemble'),
                    'description' => __('Maximum capacity/seats', 'ensemble'),
                    'icon' => ES_Icons::get('capacity'),
                    'category' => 'location',
                ),
                
                // === ARTIST FIELDS ===
                'artist_genre' => array(
                    'label' => __('Artist Genre', 'ensemble'),
                    'description' => __('Music genre or style', 'ensemble'),
                    'icon' => ES_Icons::get('tag'),
                    'category' => 'artist',
                ),
                'artist_website' => array(
                    'label' => __('Artist Website', 'ensemble'),
                    'description' => __('Artist official website', 'ensemble'),
                    'icon' => ES_Icons::get('info'),
                    'category' => 'artist',
                ),
                'artist_facebook' => array(
                    'label' => __('Artist Facebook', 'ensemble'),
                    'description' => __('Facebook profile URL', 'ensemble'),
                    'icon' => ES_Icons::get('info'),
                    'category' => 'artist',
                ),
                'artist_instagram' => array(
                    'label' => __('Artist Instagram', 'ensemble'),
                    'description' => __('Instagram profile URL', 'ensemble'),
                    'icon' => ES_Icons::get('info'),
                    'category' => 'artist',
                ),
                'artist_twitter' => array(
                    'label' => __('Artist Twitter/X', 'ensemble'),
                    'description' => __('Twitter/X profile URL', 'ensemble'),
                    'icon' => ES_Icons::get('info'),
                    'category' => 'artist',
                ),
                'artist_spotify' => array(
                    'label' => __('Artist Spotify', 'ensemble'),
                    'description' => __('Spotify artist URL', 'ensemble'),
                    'icon' => ES_Icons::get('info'),
                    'category' => 'artist',
                ),
                'artist_soundcloud' => array(
                    'label' => __('Artist SoundCloud', 'ensemble'),
                    'description' => __('SoundCloud profile URL', 'ensemble'),
                    'icon' => ES_Icons::get('info'),
                    'category' => 'artist',
                ),
                'artist_references' => array(
                    'label' => __('Artist References', 'ensemble'),
                    'description' => __('Artist bio or references', 'ensemble'),
                    'icon' => ES_Icons::get('edit'),
                    'category' => 'artist',
                ),
            );
            
            // Group fields by category for better organization
            $field_categories = array(
                'event_core' => __('Event Fields (Core - Required)', 'ensemble'),
                'location' => __('Location/Venue Fields', 'ensemble'),
                'artist' => __('Artist/Performer Fields', 'ensemble'),
            );
            ?>
            
            <div class="es-field-mapping-categories">
                <?php foreach ($field_categories as $cat_key => $cat_label): ?>
                <div class="es-field-category">
                    <h3 class="es-category-title"><?php echo esc_html($cat_label); ?></h3>
                    <div class="es-field-mapping-grid">
                        <?php foreach ($standard_fields as $field_key => $field_info): ?>
                            <?php if ($field_info['category'] === $cat_key): ?>
                            <?php
                            $current_mapping = isset($field_mapping[$field_key]) ? $field_mapping[$field_key] : '';
                            
                            // Find similar ACF fields for smart suggestions
                            $similar_fields = array();
                            $search_terms = array(
                                'event_date' => array('date', 'datum', 'day', 'tag'),
                                'event_time' => array('time', 'zeit', 'start', 'begin', 'anfang'),
                                'event_time_end' => array('end', 'ende', 'finish', 'close'),
                                'event_end_date' => array('end', 'ende', 'finish', 'last'),
                                'event_description' => array('description', 'beschreibung', 'desc', 'text', 'content', 'inhalt'),
                                'event_location' => array('location', 'ort', 'venue', 'place', 'platz'),
                                'event_artist' => array('artist', 'künstler', 'performer', 'band', 'musician'),
                                'event_price' => array('price', 'preis', 'cost', 'kosten', 'fee'),
                                'event_ticket_url' => array('ticket', 'url', 'link', 'booking'),
                                'location_address' => array('address', 'adresse', 'street', 'straße'),
                                'location_city' => array('city', 'stadt', 'ort'),
                                'location_zip' => array('zip', 'postal', 'plz', 'postleitzahl'),
                                'artist_genre' => array('genre', 'style', 'stil', 'musik'),
                                'artist_website' => array('website', 'web', 'url', 'homepage'),
                            );
                            
                            $terms = isset($search_terms[$field_key]) ? $search_terms[$field_key] : array();
                            
                            foreach ($all_available_fields as $available_field) {
                                $field_name_lower = strtolower($available_field['name']);
                                $field_label_lower = strtolower($available_field['label']);
                                
                                $similarity_score = 0;
                                foreach ($terms as $term) {
                                    if (strpos($field_name_lower, $term) !== false) {
                                        $similarity_score += 10;
                                    }
                                    if (strpos($field_label_lower, $term) !== false) {
                                        $similarity_score += 5;
                                    }
                                }
                                
                                if ($similarity_score > 0) {
                                    $similar_fields[] = array(
                                        'field' => $available_field,
                                        'score' => $similarity_score,
                                    );
                                }
                            }
                            
                            // Sort by similarity score
                            usort($similar_fields, function($a, $b) {
                                return $b['score'] - $a['score'];
                            });
                            
                            // Limit to top 10
                            $similar_fields = array_slice($similar_fields, 0, 10);
                            ?>
                
                <div class="es-field-mapping-row">
                    <div class="es-standard-field">
                        <div class="es-field-icon"><?php echo $field_info['icon']; ?></div>
                        <div class="es-field-info">
                            <strong><?php echo esc_html($field_info['label']); ?></strong>
                            <small><?php echo esc_html($field_info['description']); ?></small>
                            <code class="es-field-key">_<?php echo esc_html($field_key); ?></code>
                        </div>
                    </div>
                    
                    <div class="es-mapping-arrow">→</div>
                    
                    <div class="es-acf-field-selector">
                        <input type="hidden" 
                               name="field_mapping[<?php echo esc_attr($field_key); ?>]" 
                               id="mapping_<?php echo esc_attr($field_key); ?>"
                               value="<?php echo esc_attr($current_mapping); ?>">
                        
                        <div class="es-field-search-wrapper">
                            <input type="text" 
                                   class="es-field-search" 
                                   placeholder="<?php _e('Search fields...', 'ensemble'); ?>"
                                   data-target="<?php echo esc_attr($field_key); ?>">
                            
                            <?php if ($current_mapping): ?>
                            <?php
                            $mapped_field = null;
                            foreach ($all_available_fields as $available_field) {
                                if ($available_field['key'] === $current_mapping || $available_field['name'] === $current_mapping) {
                                    $mapped_field = $available_field;
                                    break;
                                }
                            }
                            ?>
                            <?php if ($mapped_field): ?>
                            <div class="es-selected-field">
                                <span class="es-field-pill es-field-selected" data-field-key="<?php echo esc_attr($mapped_field['key']); ?>">
                                    <strong><?php echo esc_html($mapped_field['label']); ?></strong>
                                    <small>
                                        <?php echo esc_html($mapped_field['name']); ?> (<?php echo esc_html($mapped_field['type']); ?>)
                                        <?php if (isset($mapped_field['source'])): ?>
                                            · <span class="es-field-source"><?php echo esc_html($mapped_field['source']); ?></span>
                                        <?php endif; ?>
                                    </small>
                                    <span class="es-remove-field" title="<?php _e('Remove mapping', 'ensemble'); ?>">×</span>
                                </span>
                            </div>
                            <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        
                        <div class="es-field-pills" data-target="<?php echo esc_attr($field_key); ?>">
                            <?php if (!empty($similar_fields)): ?>
                                <?php foreach ($similar_fields as $similar): ?>
                                <?php $field = $similar['field']; ?>
                                <span class="es-field-pill" data-field-key="<?php echo esc_attr($field['key']); ?>">
                                    <strong><?php echo esc_html($field['label']); ?></strong>
                                    <small>
                                        <?php echo esc_html($field['name']); ?> (<?php echo esc_html($field['type']); ?>)
                                        <?php if (isset($field['source'])): ?>
                                            · <span class="es-field-source es-source-<?php echo esc_attr($field['source']); ?>"><?php echo esc_html($field['source']); ?></span>
                                        <?php endif; ?>
                                        <?php if (isset($field['group'])): ?>
                                            · <?php echo esc_html($field['group']); ?>
                                        <?php endif; ?>
                                    </small>
                                </span>
                                <?php endforeach; ?>
                                
                                <?php if (count($all_available_fields) > 10): ?>
                                <button type="button" class="es-show-all-fields button button-small" data-target="<?php echo esc_attr($field_key); ?>">
                                    ⊕ <?php printf(__('Show all %d fields', 'ensemble'), count($all_available_fields)); ?>
                                </button>
                                <?php endif; ?>
                            <?php else: ?>
                                <p class="es-no-matches"><em><?php _e('No similar fields found. Click "Show all fields" to browse all available fields.', 'ensemble'); ?></em></p>
                                <button type="button" class="es-show-all-fields button button-small" data-target="<?php echo esc_attr($field_key); ?>">
                                    ⊕ <?php printf(__('Show all %d fields', 'ensemble'), count($all_available_fields)); ?>
                                </button>
                            <?php endif; ?>
                            
                            <div class="es-all-fields-container" style="display: none;">
                                <?php foreach ($all_available_fields as $field): ?>
                                <span class="es-field-pill" data-field-key="<?php echo esc_attr($field['key']); ?>">
                                    <strong><?php echo esc_html($field['label']); ?></strong>
                                    <small>
                                        <?php echo esc_html($field['name']); ?> (<?php echo esc_html($field['type']); ?>)
                                        <?php if (isset($field['source'])): ?>
                                            · <span class="es-field-source es-source-<?php echo esc_attr($field['source']); ?>"><?php echo esc_html($field['source']); ?></span>
                                        <?php endif; ?>
                                        <?php if (isset($field['group'])): ?>
                                            · <?php echo esc_html($field['group']); ?>
                                        <?php endif; ?>
                                    </small>
                                </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php endif; ?>
                <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php endif; ?>
            
        </div>
        
        <p class="submit">
            <input type="submit" name="es_save_field_mapping" class="button button-primary" value="<?php _e('Save Field Mapping', 'ensemble'); ?>">
        </p>
    </form>
    
    <?php endif; ?>
    
    <!-- Wizard Steps Tab -->
    <?php if ($current_tab === 'wizard-steps'): ?>
    
    <!-- Section 1: Standard Wizard Fields -->
    <form method="post" action="">
        <?php wp_nonce_field('ensemble_wizard_fields'); ?>
        
        <div class="es-wizard-fields-config">
            <h2><?php _e('Standard Wizard Fields', 'ensemble'); ?></h2>
            <p class="description" style="margin-bottom: 30px;">
                <?php _e('Choose which standard fields to show in the Event Wizard. Core fields (Title, Category, Date) are always required. Disable fields you don\'t need or if you prefer to use your own ACF configuration.', 'ensemble'); ?>
            </p>
            
            <div class="es-fields-toggle-grid">
                <?php
                // Define all wizard fields with their properties
                $all_wizard_fields = array(
                    // Core fields (always enabled, not toggleable)
                    'title' => array(
                        'label' => __('Event Title', 'ensemble'),
                        'description' => __('The name of the event', 'ensemble'),
                        'icon' => 'edit',
                        'core' => true,
                    ),
                    'category' => array(
                        'label' => __('Event Category', 'ensemble'),
                        'description' => __('Event type/category', 'ensemble'),
                        'icon' => 'folder',
                        'core' => true,
                    ),
                    'date' => array(
                        'label' => __('Event Date', 'ensemble'),
                        'description' => __('When the event takes place', 'ensemble'),
                        'icon' => 'calendar',
                        'core' => true,
                    ),
                    // Optional fields
                    'time' => array(
                        'label' => __('Start Time', 'ensemble'),
                        'description' => __('Event start time', 'ensemble'),
                        'icon' => 'clock',
                        'core' => false,
                    ),
                    'time_end' => array(
                        'label' => __('End Time', 'ensemble'),
                        'description' => __('Event end time', 'ensemble'),
                        'icon' => 'clock',
                        'core' => false,
                    ),
                    'description' => array(
                        'label' => __('Description', 'ensemble'),
                        'description' => __('Event details and description', 'ensemble'),
                        'icon' => 'edit',
                        'core' => false,
                    ),
                    'location' => array(
                        'label' => __('Location', 'ensemble'),
                        'description' => __('Where the event takes place', 'ensemble'),
                        'icon' => 'location',
                        'core' => false,
                    ),
                    'artist' => array(
                        'label' => __('Artists/Performers', 'ensemble'),
                        'description' => __('Who is performing', 'ensemble'),
                        'icon' => 'artist',
                        'core' => false,
                    ),
                    'price' => array(
                        'label' => __('Price', 'ensemble'),
                        'description' => __('Ticket price information', 'ensemble'),
                        'icon' => 'ticket',
                        'core' => false,
                    ),
                    'ticket_url' => array(
                        'label' => __('Ticket URL', 'ensemble'),
                        'description' => __('Link to ticket purchase', 'ensemble'),
                        'icon' => 'link',
                        'core' => false,
                    ),
                    'button_text' => array(
                        'label' => __('Button Text', 'ensemble'),
                        'description' => __('Custom text for ticket button', 'ensemble'),
                        'icon' => 'edit',
                        'core' => false,
                    ),
                    'additional_info' => array(
                        'label' => __('Additional Info', 'ensemble'),
                        'description' => __('Directions, entry requirements, parking, etc.', 'ensemble'),
                        'icon' => 'info',
                        'core' => false,
                    ),
                    'external_link' => array(
                        'label' => __('External Link', 'ensemble'),
                        'description' => __('Link to external page with custom button text', 'ensemble'),
                        'icon' => 'link',
                        'core' => false,
                    ),
                );
                ?>
                
                <!-- Core Fields (Always Enabled) -->
                <div class="es-fields-section">
                    <h3 class="es-section-title"><?php _e('Core Fields (Required)', 'ensemble'); ?></h3>
                    <div class="es-fields-list">
                        <?php foreach ($all_wizard_fields as $field_key => $field_info): ?>
                            <?php if ($field_info['core']): ?>
                            <div class="es-field-toggle-item es-field-core">
                                <div class="es-field-info">
                                    <span class="es-field-icon"><?php ES_Icons::icon($field_info['icon']); ?></span>
                                    <div class="es-field-text">
                                        <strong><?php echo esc_html($field_info['label']); ?></strong>
                                        <small><?php echo esc_html($field_info['description']); ?></small>
                                    </div>
                                </div>
                                <div class="es-field-toggle">
                                    <span class="es-always-on"><?php _e('Always On', 'ensemble'); ?></span>
                                </div>
                            </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Optional Fields -->
                <div class="es-fields-section">
                    <h3 class="es-section-title"><?php _e('Optional Fields', 'ensemble'); ?></h3>
                    <div class="es-fields-list">
                        <?php foreach ($all_wizard_fields as $field_key => $field_info): ?>
                            <?php if (!$field_info['core']): ?>
                            <?php $is_enabled = in_array($field_key, $wizard_fields); ?>
                            <div class="es-field-toggle-item <?php echo $is_enabled ? 'es-field-enabled' : 'es-field-disabled'; ?>">
                                <div class="es-field-info">
                                    <span class="es-field-icon"><?php ES_Icons::icon($field_info['icon']); ?></span>
                                    <div class="es-field-text">
                                        <strong><?php echo esc_html($field_info['label']); ?></strong>
                                        <small><?php echo esc_html($field_info['description']); ?></small>
                                    </div>
                                </div>
                                <div class="es-field-toggle">
                                    <label class="es-toggle-switch">
                                        <input type="checkbox" 
                                               name="wizard_fields[]" 
                                               value="<?php echo esc_attr($field_key); ?>"
                                               <?php checked($is_enabled); ?>>
                                        <span class="es-toggle-slider"></span>
                                    </label>
                                </div>
                            </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <p class="submit">
                <input type="submit" name="es_save_wizard_fields" class="button button-primary" value="<?php _e('Save Field Configuration', 'ensemble'); ?>">
            </p>
        </div>
    </form>
    
    <hr style="margin: 40px 0; border-color: rgba(255,255,255,0.1);">
    
    <!-- Section 2: Custom ACF Field Groups per Category -->
    <form method="post" action="">
        <?php wp_nonce_field('ensemble_wizard_steps'); ?>
        
        <div class="es-wizard-steps-config">
            
            <?php if (empty($categories)): ?>
                <div class="notice notice-warning inline">
                    <p>
                        <?php _e('No event categories found.', 'ensemble'); ?>
                        <a href="<?php echo admin_url('edit-tags.php?taxonomy=ensemble_category&post_type=' . $current_post_type); ?>">
                            <?php _e('Create categories first', 'ensemble'); ?>
                        </a>
                    </p>
                </div>
            <?php endif; ?>
            
            <?php if (empty($field_groups)): ?>
                <div class="notice notice-info inline">
                    <p>
                        <?php _e('No custom ACF field groups found. You can create custom field groups in ACF and assign them to event categories here.', 'ensemble'); ?>
                    </p>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($categories)): ?>
            
            <h2><?php _e('Configure Custom Steps per Category', 'ensemble'); ?></h2>
            <p class="description" style="margin-bottom: 30px;">
                <?php _e('Assign ACF field groups to event categories. These will appear as additional steps in the Event Wizard when the category is selected.', 'ensemble'); ?>
            </p>
            
            <div class="es-wizard-config-container">
                <?php foreach ($categories as $category): ?>
                <?php 
                $category_id = $category['id'];
                $assigned_groups = isset($wizard_config[$category_id]['field_groups']) ? $wizard_config[$category_id]['field_groups'] : array();
                ?>
                <div class="es-category-config-card">
                    <h3 class="es-category-title"><?php echo esc_html($category['name']); ?></h3>
                    
                    <?php if (!empty($field_groups)): ?>
                    <div class="es-field-groups-pills es-sortable" data-category="<?php echo esc_attr($category_id); ?>">
                        <?php 
                        // Sort assigned groups first, then unassigned
                        $sorted_groups = array();
                        
                        // Add assigned groups in order
                        foreach ($assigned_groups as $assigned_key) {
                            foreach ($field_groups as $group) {
                                if ($group['key'] === $assigned_key) {
                                    $sorted_groups[] = $group;
                                    break;
                                }
                            }
                        }
                        
                        // Add unassigned groups
                        foreach ($field_groups as $group) {
                            if (!in_array($group['key'], $assigned_groups)) {
                                $sorted_groups[] = $group;
                            }
                        }
                        
                        foreach ($sorted_groups as $index => $group): 
                        ?>
                        <label class="es-pill es-sortable-pill" data-group-key="<?php echo esc_attr($group['key']); ?>">
                            <span class="es-drag-handle" title="Drag to reorder">⋮⋮</span>
                            <input type="checkbox" 
                                   name="wizard_config[<?php echo esc_attr($category_id); ?>][field_groups][]" 
                                   value="<?php echo esc_attr($group['key']); ?>"
                                   <?php checked(in_array($group['key'], $assigned_groups)); ?>>
                            <span><?php echo esc_html($group['title']); ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <p class="description" style="margin-top: 10px; font-size: 12px;">
                        <?php _e('Check to enable, drag to reorder. Order determines appearance in wizard.', 'ensemble'); ?>
                    </p>
                    <?php else: ?>
                    <p><em><?php _e('No custom field groups available', 'ensemble'); ?></em></p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php endif; ?>
            
        </div>
        
        <p class="submit">
            <input type="submit" name="es_save_wizard_steps" class="button button-primary" value="<?php _e('Save Wizard Steps', 'ensemble'); ?>">
        </p>
    </form>
    
    <?php endif; ?>
    </div>
    </div>
</div>
