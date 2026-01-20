<?php
/**
 * Layout Sets Tab - Complete Version
 * - Pre-built sets with activation
 * - Custom template editor with CodeMirror
 * - Template management (list, edit, delete)
 */

if (!defined('ABSPATH')) exit;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Activate pre-built set
    if (isset($_POST['es_activate_set']) && check_admin_referer('es_activate_set')) {
        $set_id = sanitize_text_field($_POST['set_id']);
        
        // Pro check for premium layouts
        $is_pro = function_exists('ensemble_is_pro') && ensemble_is_pro();
        $pro_layouts = array(); // Currently no Pro-only layouts
        
        if (in_array($set_id, $pro_layouts) && !$is_pro) {
            echo '<div class="notice notice-error"><p>' . __('This layout requires the Pro version.', 'ensemble') . '</p></div>';
        } else {
            // 1. Set the new layout
            update_option('ensemble_active_layout_set', $set_id);
            
            // 2. Apply the preset values to Designer settings
            if (class_exists('ES_Design_Settings')) {
                ES_Design_Settings::apply_layout_preset();
            }
            
            echo '<div class="notice notice-success"><p>' . sprintf(__('Layout "%s" activated! Designer settings have been updated to match this layout.', 'ensemble'), esc_html($set_id)) . '</p></div>';
        }
    }
    
    // Save custom template
    if (isset($_POST['es_save_custom_template']) && check_admin_referer('es_custom_template')) {
        $template_name = sanitize_text_field($_POST['template_name']);
        $template_html = $_POST['template_html']; // Allow HTML/PHP
        $template_css = sanitize_textarea_field($_POST['template_css']);
        $template_id = isset($_POST['template_id']) ? sanitize_text_field($_POST['template_id']) : '';
        
        $custom_templates = get_option('es_custom_templates', array());
        
        if (empty($template_id)) {
            // New template
            $template_id = 'custom_' . time();
        }
        
        $custom_templates[$template_id] = array(
            'name' => $template_name,
            'html' => $template_html,
            'css' => $template_css,
            'created' => current_time('mysql')
        );
        
        update_option('es_custom_templates', $custom_templates);
        echo '<div class="notice notice-success"><p>Custom template "' . esc_html($template_name) . '" saved!</p></div>';
    }
    
    // Delete custom template
    if (isset($_POST['es_delete_template']) && check_admin_referer('es_delete_template')) {
        $template_id = sanitize_text_field($_POST['template_id']);
        $custom_templates = get_option('es_custom_templates', array());
        
        if (isset($custom_templates[$template_id])) {
            unset($custom_templates[$template_id]);
            update_option('es_custom_templates', $custom_templates);
            echo '<div class="notice notice-success"><p>Template deleted!</p></div>';
        }
    }
    
    // Activate custom template
    if (isset($_POST['es_activate_custom']) && check_admin_referer('es_activate_custom')) {
        $template_id = sanitize_text_field($_POST['template_id']);
        update_option('ensemble_active_layout_set', $template_id);
        echo '<div class="notice notice-success"><p>Custom template activated!</p></div>';
    }
    
    // Set layout mode (light/dark)
    if (isset($_POST['es_set_layout_mode']) && check_admin_referer('es_set_layout_mode')) {
        $mode = sanitize_text_field($_POST['layout_mode']);
        $set_id = sanitize_text_field($_POST['set_id']);
        
        if (class_exists('ES_Layout_Sets') && ES_Layout_Sets::set_mode($mode, $set_id)) {
            echo '<div class="notice notice-success"><p>' . sprintf(__('Mode changed to %s.', 'ensemble'), $mode === 'dark' ? __('Dark', 'ensemble') : __('Light', 'ensemble')) . '</p></div>';
        }
    }
}

// Get data
$available_sets = class_exists('ES_Layout_Sets') ? ES_Layout_Sets::get_sets() : array();
$active_set = get_option('ensemble_active_layout_set', 'modern');
$custom_templates = get_option('es_custom_templates', array());
$is_pro = function_exists('ensemble_is_pro') && ensemble_is_pro();
?>

<div class="es-layout-sets-section">
    <h2>Layout Sets</h2>
    <p>Choose a pre-built layout set or create your own custom template.</p>
    
    <!-- Pre-built Sets Grid -->
    <div class="es-sets-grid">
        <?php foreach ($available_sets as $set_id => $set): 
            $requires_pro = isset($set['requires_pro']) && $set['requires_pro'];
            $is_locked = $requires_pro && !$is_pro;
        ?>
        <div class="es-set-card <?php echo ($active_set === $set_id) ? 'active' : ''; ?> <?php echo $is_locked ? 'es-pro-required' : ''; ?>">
            <h3>
                <?php echo esc_html($set['name']); ?>
                <?php if ($requires_pro): ?>
                    <span class="es-pro-badge"><span class="dashicons dashicons-star-filled"></span> PRO</span>
                <?php endif; ?>
            </h3>
            <p><?php echo esc_html($set['description']); ?></p>
            
            <?php if ($is_locked): ?>
                <div class="es-pro-upgrade-prompt" style="margin-top: 15px; padding: 10px; text-align: left;">
                    <p style="margin: 0 0 10px; font-size: 12px;">
                        <span class="dashicons dashicons-lock" style="color: #f59e0b;"></span>
                        <?php _e('This layout requires Pro.', 'ensemble'); ?>
                    </p>
                    <a href="<?php echo admin_url('admin.php?page=ensemble-settings&tab=license'); ?>" class="button button-small">
                        <?php _e('Upgrade', 'ensemble'); ?>
                    </a>
                </div>
            <?php elseif ($active_set === $set_id): ?>
                <span class="es-active-badge">✓ Active</span>
                
                <?php 
                // Mode Toggle for layouts that support it
                $supports_modes = !empty($set['supports_modes']);
                $current_mode = ES_Layout_Sets::get_active_mode();
                ?>
                
                <?php if ($supports_modes): ?>
                <div class="es-mode-toggle" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid rgba(0,0,0,0.1);">
                    <label style="display: block; margin-bottom: 8px; font-weight: 500; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; color: #666;">
                        <?php _e('Farbmodus', 'ensemble'); ?>
                    </label>
                    <form method="post" class="es-mode-form">
                        <?php wp_nonce_field('es_set_layout_mode'); ?>
                        <input type="hidden" name="set_id" value="<?php echo esc_attr($set_id); ?>">
                        <div class="es-mode-buttons" style="display: flex; gap: 8px;">
                            <button type="submit" name="es_set_layout_mode" 
                                    class="es-mode-btn <?php echo $current_mode === 'light' ? 'active' : ''; ?>"
                                    onclick="this.form.querySelector('[name=layout_mode]').value='light'">
                                <span class="dashicons dashicons-sun" style="font-size: 14px;"></span>
                                <?php _e('Hell', 'ensemble'); ?>
                            </button>
                            <button type="submit" name="es_set_layout_mode"
                                    class="es-mode-btn <?php echo $current_mode === 'dark' ? 'active' : ''; ?>"
                                    onclick="this.form.querySelector('[name=layout_mode]').value='dark'">
                                <span class="dashicons dashicons-moon" style="font-size: 14px;"></span>
                                <?php _e('Dunkel', 'ensemble'); ?>
                            </button>
                        </div>
                        <input type="hidden" name="layout_mode" value="<?php echo esc_attr($current_mode); ?>">
                    </form>
                </div>
                <?php else: ?>
                <div class="es-mode-info" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid rgba(0,0,0,0.1);">
                    <span style="font-size: 11px; color: #999;">
                        <span class="dashicons dashicons-<?php echo ($set['default_mode'] ?? 'light') === 'dark' ? 'moon' : 'sun'; ?>" style="font-size: 14px;"></span>
                        <?php echo ($set['default_mode'] ?? 'light') === 'dark' ? __('Nur Dark Mode', 'ensemble') : __('Nur Light Mode', 'ensemble'); ?>
                    </span>
                </div>
                <?php endif; ?>
                
            <?php else: ?>
                <form method="post" style="margin-top: 15px;">
                    <?php wp_nonce_field('es_activate_set'); ?>
                    <input type="hidden" name="set_id" value="<?php echo esc_attr($set_id); ?>">
                    <button type="submit" name="es_activate_set" class="button button-primary">
                        Activate
                    </button>
                </form>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Custom Templates Section - Pro Feature (Coming Soon) -->
    <div style="margin-top: 40px; padding-top: 40px; border-top: 2px solid #ddd;">
        <h3><?php _e('Custom Templates', 'ensemble'); ?></h3>
        
        <div class="es-pro-upgrade-prompt" style="margin-top: 20px; padding: 2rem;">
            <span class="dashicons dashicons-admin-customizer" style="font-size: 36px; width: 36px; height: 36px; color: #f59e0b; margin-bottom: 1rem; display: block;"></span>
            <span class="es-pro-badge" style="margin-left: 0; margin-bottom: 0.75rem; display: inline-flex;">
                <span class="dashicons dashicons-star-filled"></span> PRO
            </span>
            <h4><?php _e('Custom Template Editor', 'ensemble'); ?></h4>
            <p><?php _e('Create your own templates with full access to HTML, PHP and CSS. Coming soon for Pro users.', 'ensemble'); ?></p>
        </div>
    </div>
</div>

<style>
/* Mode Toggle Buttons */
.es-mode-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border: 2px solid #e0e0e0;
    border-radius: 6px;
    background: #fff;
    color: #666;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
}

.es-mode-btn:hover {
    border-color: #3582c4;
    color: #3582c4;
}

.es-mode-btn.active {
    border-color: #3582c4;
    background: #3582c4;
    color: #fff;
}

.es-mode-btn .dashicons {
    width: 14px;
    height: 14px;
}

.es-mode-info {
    font-size: 12px;
    color: #888;
}

.es-mode-info .dashicons {
    vertical-align: middle;
    margin-right: 4px;
}
</style>
