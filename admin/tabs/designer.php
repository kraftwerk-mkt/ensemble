<?php
/**
 * Designer Tab
 * 
 * Visual design customization for colors, typography, layout and buttons
 * 
 * @package Ensemble
 * @since 2.9.3
 */

if (!defined('ABSPATH')) exit;
?>

        <div class="es-designer-section">
            
            <div class="es-section-intro">
                <h2><?php _e('Frontend Design', 'ensemble'); ?></h2>
                <p class="es-description">
                    <?php _e('Choose from pre-designed templates or customize the appearance of your events, calendar, and filters.', 'ensemble'); ?>
                </p>
            </div>
            
            <?php
            // Theme Mode Toggle - nur anzeigen wenn Klassen existieren
            if (class_exists('ES_Theme_Detector') && method_exists('ES_Design_Settings', 'get_design_mode')):
                $current_mode = ES_Design_Settings::get_design_mode();
                $theme_info = ES_Theme_Detector::get_theme_info();
                $theme_values = ES_Theme_Detector::get_theme_values();
                $is_custom_mode = ($current_mode === 'custom');
            ?>
            
            <style>
            /* Design Mode Card */
            .es-design-mode-card {
                margin-bottom: 30px;
            }
            .es-design-mode-card .es-card-body {
                padding: 24px;
            }
            
            /* Theme Info Row */
            .es-dm-theme-info {
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 16px;
                background: #27272a;
                border-radius: 8px;
                margin-bottom: 20px;
            }
            .es-dm-theme-info .dashicons {
                font-size: 24px;
                width: 24px;
                height: 24px;
                color: #a1a1aa;
            }
            .es-dm-theme-name {
                color: #fafafa;
                font-weight: 600;
                margin-right: 8px;
            }
            .es-dm-badge {
                display: inline-block;
                padding: 3px 10px;
                border-radius: 12px;
                font-size: 11px;
                font-weight: 600;
            }
            .es-dm-badge-success {
                background: rgba(34, 197, 94, 0.2);
                color: #22c55e;
            }
            .es-dm-badge-warning {
                background: rgba(234, 179, 8, 0.2);
                color: #eab308;
            }
            
            /* Toggle Row */
            .es-dm-toggle-row {
                display: flex;
                align-items: center;
                gap: 16px;
                padding: 20px;
                background: #18181b;
                border-radius: 8px;
                border: 1px solid #3f3f46;
                margin-bottom: 20px;
            }
            
            /* Toggle Switch */
            .es-dm-toggle {
                position: relative;
                width: 52px;
                height: 28px;
                flex-shrink: 0;
            }
            .es-dm-toggle input {
                opacity: 0;
                width: 0;
                height: 0;
            }
            .es-dm-toggle-slider {
                position: absolute;
                cursor: pointer;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: #52525b;
                border-radius: 28px;
                transition: 0.3s;
            }
            .es-dm-toggle-slider:before {
                position: absolute;
                content: "";
                height: 20px;
                width: 20px;
                left: 4px;
                bottom: 4px;
                background-color: white;
                border-radius: 50%;
                transition: 0.3s;
                box-shadow: 0 2px 4px rgba(0,0,0,0.3);
            }
            .es-dm-toggle input:checked + .es-dm-toggle-slider {
                background-color: #22c55e;
            }
            .es-dm-toggle input:checked + .es-dm-toggle-slider:before {
                transform: translateX(24px);
            }
            
            /* Toggle Label */
            .es-dm-toggle-label {
                font-size: 15px;
                font-weight: 600;
                color: #fafafa;
            }
            .es-dm-toggle-hint {
                font-size: 13px;
                color: #a1a1aa;
                margin-left: auto;
            }
            
            /* Theme Values */
            .es-dm-values {
                padding: 20px;
                background: #27272a;
                border-radius: 8px;
            }
            .es-dm-values h4 {
                margin: 0 0 16px 0;
                font-size: 13px;
                font-weight: 600;
                color: #a1a1aa;
                text-transform: uppercase;
            }
            .es-dm-values-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
                gap: 12px;
            }
            .es-dm-value {
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 12px;
                background: #18181b;
                border-radius: 6px;
                border: 1px solid #3f3f46;
            }
            .es-dm-color-swatch {
                width: 36px;
                height: 36px;
                border-radius: 6px;
                border: 2px solid #3f3f46;
                flex-shrink: 0;
            }
            .es-dm-font-swatch {
                width: 36px;
                height: 36px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: #3f3f46;
                border-radius: 6px;
                font-weight: 600;
                font-size: 14px;
                color: #fafafa;
                flex-shrink: 0;
            }
            .es-dm-value-info .es-dm-value-label {
                font-size: 11px;
                color: #71717a;
                display: block;
                margin-bottom: 2px;
            }
            .es-dm-value-info code {
                font-size: 12px;
                color: #fafafa;
                background: none;
                padding: 0;
            }
            
            @media (max-width: 768px) {
                .es-dm-toggle-row {
                    flex-wrap: wrap;
                }
                .es-dm-toggle-hint {
                    width: 100%;
                    margin-left: 68px;
                    margin-top: 8px;
                }
                .es-dm-values-grid {
                    grid-template-columns: 1fr;
                }
            }
            </style>
            
            <!-- Design Mode Card -->
            <div class="es-card es-design-mode-card">
                <div class="es-card-header">
                    <h3><?php _e('Design Modus', 'ensemble'); ?></h3>
                </div>
                <div class="es-card-body">
                    
                    <!-- Theme Info -->
                    <div class="es-dm-theme-info">
                        <span class="dashicons dashicons-admin-appearance"></span>
                        <span><?php _e('Aktives Theme:', 'ensemble'); ?></span>
                        <span class="es-dm-theme-name"><?php echo esc_html($theme_info['name']); ?></span>
                        <?php if ($theme_info['supported']): ?>
                            <span class="es-dm-badge es-dm-badge-success"><?php _e('Supported', 'ensemble'); ?></span>
                        <?php else: ?>
                            <span class="es-dm-badge es-dm-badge-warning"><?php _e('Generische Werte', 'ensemble'); ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Toggle Row -->
                    <div class="es-dm-toggle-row">
                        <label class="es-dm-toggle">
                            <input type="checkbox" 
                                   id="es-design-mode-toggle"
                                   <?php checked($is_custom_mode); ?>
                                   data-nonce="<?php echo wp_create_nonce('ensemble_designer'); ?>">
                            <span class="es-dm-toggle-slider"></span>
                        </label>
                        <span class="es-dm-toggle-label" id="es-dm-label">
                            <?php echo $is_custom_mode ? __('Use custom settings', 'ensemble') : __('Use theme settings', 'ensemble'); ?>
                        </span>
                        <span class="es-dm-toggle-hint" id="es-dm-hint">
                            <?php echo $is_custom_mode ? __('Designer is active', 'ensemble') : __('Colors from theme', 'ensemble'); ?>
                        </span>
                    </div>
                    
                    <!-- Theme Values Preview (nur im Theme-Modus) -->
                    <div id="es-theme-values-preview" class="es-dm-values" style="display: <?php echo !$is_custom_mode ? 'block' : 'none'; ?>;">
                        <h4><?php _e('Values inherited from theme:', 'ensemble'); ?></h4>
                        <div class="es-dm-values-grid">
                            <div class="es-dm-value">
                                <span class="es-dm-color-swatch" style="background: <?php echo esc_attr($theme_values['primary_color']); ?>"></span>
                                <div class="es-dm-value-info">
                                    <span class="es-dm-value-label"><?php _e('Primary Color', 'ensemble'); ?></span>
                                    <code><?php echo esc_html($theme_values['primary_color']); ?></code>
                                </div>
                            </div>
                            <div class="es-dm-value">
                                <span class="es-dm-color-swatch" style="background: <?php echo esc_attr($theme_values['secondary_color']); ?>"></span>
                                <div class="es-dm-value-info">
                                    <span class="es-dm-value-label"><?php _e('Secondary Color', 'ensemble'); ?></span>
                                    <code><?php echo esc_html($theme_values['secondary_color']); ?></code>
                                </div>
                            </div>
                            <div class="es-dm-value">
                                <span class="es-dm-font-swatch">Aa</span>
                                <div class="es-dm-value-info">
                                    <span class="es-dm-value-label"><?php _e('Headings', 'ensemble'); ?></span>
                                    <code><?php echo esc_html($theme_values['heading_font']); ?></code>
                                </div>
                            </div>
                            <div class="es-dm-value">
                                <span class="es-dm-font-swatch">Aa</span>
                                <div class="es-dm-value-info">
                                    <span class="es-dm-value-label"><?php _e('Body Text', 'ensemble'); ?></span>
                                    <code><?php echo esc_html($theme_values['body_font']); ?></code>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>
            
            <script>
            jQuery(document).ready(function($) {
                $('#es-design-mode-toggle').on('change', function() {
                    var $toggle = $(this);
                    var isCustomMode = $toggle.prop('checked');
                    var nonce = $toggle.data('nonce');
                    var mode = isCustomMode ? 'custom' : 'theme';
                    
                    // Disable toggle
                    $toggle.prop('disabled', true);
                    
                    // Update labels
                    if (isCustomMode) {
                        $('#es-dm-label').text('Use custom settings');
                        $('#es-dm-hint').text('Designer is active');
                        $('#es-theme-values-preview').slideUp(300);
                        $('#es-custom-design-settings').slideDown(300);
                    } else {
                        $('#es-dm-label').text('Use theme settings');
                        $('#es-dm-hint').text('Colors from theme');
                        $('#es-theme-values-preview').slideDown(300);
                        $('#es-custom-design-settings').slideUp(300);
                    }
                    
                    // Save via AJAX
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'es_set_design_mode',
                            mode: mode,
                            nonce: nonce
                        },
                        success: function(response) {
                            $toggle.prop('disabled', false);
                            if (response.success) {
                                // Reload to regenerate CSS
                                setTimeout(function() {
                                    location.reload();
                                }, 800);
                            } else {
                                $toggle.prop('checked', !isCustomMode);
                                alert('Error saving');
                            }
                        },
                        error: function() {
                            $toggle.prop('disabled', false);
                            $toggle.prop('checked', !isCustomMode);
                            alert('Verbindungsfehler');
                        }
                    });
                });
            });
            </script>
            
            <?php else: 
                // Fallback wenn Theme Mode nicht verfÃ¼gbar - immer Custom Mode
                $is_custom_mode = true;
            ?>
            <?php endif; // Ende Theme Mode Check ?>
            
            <!-- Custom Design Settings - nur im Custom Mode sichtbar -->
            <?php 
            $show_custom_settings = true;
            if (class_exists('ES_Theme_Detector') && method_exists('ES_Design_Settings', 'get_design_mode')) {
                $current_design_mode = ES_Design_Settings::get_design_mode();
                $show_custom_settings = ($current_design_mode !== 'theme');
                // Debug
                // echo "<!-- Design Mode: $current_design_mode, Show Custom: " . ($show_custom_settings ? 'YES' : 'NO') . " -->";
            }
            ?>
            <div id="es-custom-design-settings" style="display: <?php echo $show_custom_settings ? 'block' : 'none'; ?>;">
            
            
            <?php
            // Get current settings (includes layout preset defaults)
            $settings = ES_Design_Settings::get_settings();
            
            // Get active layout info
            $active_layout = 'modern';
            $layout_name = 'Modern';
            if (class_exists('ES_Layout_Sets')) {
                $active_layout = ES_Layout_Sets::get_active_set();
                $set_data = ES_Layout_Sets::get_set_data($active_layout);
                $layout_name = $set_data['name'] ?? ucfirst($active_layout);
            }
            
            // Handle reset to layout defaults
            if (isset($_POST['es_reset_to_layout']) && check_admin_referer('es_design_action', 'es_design_nonce')) {
                ES_Design_Settings::reset_to_defaults();
                echo '<div class="notice notice-success is-dismissible"><p>' . __('Settings reset to layout defaults!', 'ensemble') . '</p></div>';
                $settings = ES_Design_Settings::get_settings(); // Reload
            }
            ?>
            
            <!-- Active Layout Info -->
            <div class="es-card">
                <div class="es-card-header">
                    <h3><?php _e('Design Customization', 'ensemble'); ?></h3>
                    <p class="es-card-description">
                        <?php printf(__('Active Layout: %s', 'ensemble'), '<strong>' . esc_html($layout_name) . '</strong>'); ?>
                        â€” <?php _e('Customize the values below or reset to layout defaults.', 'ensemble'); ?>
                    </p>
                </div>
                <div class="es-card-body" style="padding: 15px 20px;">
                    <form method="post" style="display: inline;">
                        <?php wp_nonce_field('es_design_action', 'es_design_nonce'); ?>
                        <button type="submit" name="es_reset_to_layout" class="button" onclick="return confirm('<?php esc_attr_e('Reset all design settings to layout defaults?', 'ensemble'); ?>');">
                            <span class="dashicons dashicons-image-rotate" style="margin-top: 3px;"></span>
                            <?php _e('Reset to Layout Defaults', 'ensemble'); ?>
                        </button>
                    </form>
                    <p class="description" style="margin-top: 10px; margin-bottom: 0;">
                        <?php _e('To change the layout, go to the Layout Sets section above.', 'ensemble'); ?>
                    </p>
                </div>
            </div>
            
            <?php
            // Handle custom settings save
            if (isset($_POST['es_save_custom_design']) && check_admin_referer('es_design_action', 'es_design_nonce')) {
                $custom_settings = array(
                    // Light Mode Colors
                    'primary_color' => sanitize_hex_color($_POST['primary_color']),
                    'secondary_color' => sanitize_hex_color($_POST['secondary_color']),
                    'background_color' => sanitize_hex_color($_POST['background_color']),
                    'card_background' => sanitize_hex_color($_POST['card_background']),
                    'text_color' => sanitize_hex_color($_POST['text_color']),
                    'text_secondary' => sanitize_hex_color($_POST['text_secondary']),
                    'button_bg' => sanitize_hex_color($_POST['button_bg']),
                    'button_text' => sanitize_hex_color($_POST['button_text']),
                    'button_hover_bg' => sanitize_hex_color($_POST['button_hover_bg']),
                    'card_border' => sanitize_hex_color($_POST['card_border']),
                    
                    // Dark Mode Colors
                    'dark_primary_color' => sanitize_hex_color($_POST['dark_primary_color']),
                    'dark_secondary_color' => sanitize_hex_color($_POST['dark_secondary_color']),
                    'dark_background_color' => sanitize_hex_color($_POST['dark_background_color']),
                    'dark_card_background' => sanitize_hex_color($_POST['dark_card_background']),
                    'dark_text_color' => sanitize_hex_color($_POST['dark_text_color']),
                    'dark_text_secondary' => sanitize_hex_color($_POST['dark_text_secondary']),
                    'dark_button_bg' => sanitize_hex_color($_POST['dark_button_bg']),
                    'dark_button_text' => sanitize_hex_color($_POST['dark_button_text']),
                    'dark_button_hover_bg' => sanitize_hex_color($_POST['dark_button_hover_bg']),
                    'dark_card_border' => sanitize_hex_color($_POST['dark_card_border']),
                    
                    // Typography
                    'heading_font' => sanitize_text_field($_POST['heading_font']),
                    'body_font' => sanitize_text_field($_POST['body_font']),
                    'h1_size' => intval($_POST['h1_size']),
                    'h2_size' => intval($_POST['h2_size']),
                    'h3_size' => intval($_POST['h3_size']),
                    'body_size' => intval($_POST['body_size']),
                    'line_height' => floatval($_POST['line_height']),
                    
                    // Typography - Extended Sizes
                    'xs_size' => intval($_POST['xs_size'] ?? 12),
                    'meta_size' => intval($_POST['meta_size'] ?? 14),
                    'lg_size' => intval($_POST['lg_size'] ?? 18),
                    'xl_size' => intval($_POST['xl_size'] ?? 20),
                    'price_size' => intval($_POST['price_size'] ?? 32),
                    'hero_size' => intval($_POST['hero_size'] ?? 72),
                    
                    // Layout
                    'grid_columns' => intval($_POST['grid_columns']),
                    'grid_gap' => intval($_POST['grid_gap']),
                    'card_radius' => intval($_POST['card_radius']),
                    'card_padding' => intval($_POST['card_padding']),
                    'card_shadow' => sanitize_text_field($_POST['card_shadow']),
                    'card_hover' => sanitize_text_field($_POST['card_hover']),
                    'card_border_width' => intval($_POST['card_border_width']),
                    
                    // Buttons
                    'button_radius' => intval($_POST['button_radius']),
                    'button_padding_v' => intval($_POST['button_padding_v']),
                    'button_padding_h' => intval($_POST['button_padding_h']),
                    'button_font_size' => intval($_POST['button_font_size']),
                    'button_weight' => intval($_POST['button_weight']),
                    'button_border_width' => intval($_POST['button_border_width']),
                    
                    // Advanced - Surface & Links
                    'surface_color' => sanitize_hex_color($_POST['surface_color'] ?? ''),
                    'divider_color' => sanitize_hex_color($_POST['divider_color'] ?? ''),
                    'link_color' => sanitize_hex_color($_POST['link_color'] ?? ''),
                    'text_muted' => sanitize_hex_color($_POST['text_muted'] ?? ''),
                    
                    // Advanced - Overlay
                    'overlay_bg' => sanitize_text_field($_POST['overlay_bg'] ?? ''),
                    'overlay_text' => sanitize_hex_color($_POST['overlay_text'] ?? ''),
                    'overlay_text_secondary' => sanitize_text_field($_POST['overlay_text_secondary'] ?? ''),
                    'overlay_border' => sanitize_text_field($_POST['overlay_border'] ?? ''),
                    
                    // Advanced - Placeholder
                    'placeholder_bg' => sanitize_hex_color($_POST['placeholder_bg'] ?? ''),
                    'placeholder_icon' => sanitize_hex_color($_POST['placeholder_icon'] ?? ''),
                    
                    // Advanced - Status
                    'status_cancelled' => sanitize_hex_color($_POST['status_cancelled'] ?? ''),
                    'status_soldout' => sanitize_hex_color($_POST['status_soldout'] ?? ''),
                    'status_postponed' => sanitize_hex_color($_POST['status_postponed'] ?? ''),
                    
                    // Advanced - Gradients
                    'gradient_start' => sanitize_text_field($_POST['gradient_start'] ?? ''),
                    'gradient_mid' => sanitize_text_field($_POST['gradient_mid'] ?? ''),
                    'gradient_end' => sanitize_text_field($_POST['gradient_end'] ?? ''),
                    
                    // Advanced - Social
                    'facebook_color' => sanitize_hex_color($_POST['facebook_color'] ?? ''),
                    
                    // Advanced Dark Mode
                    'dark_surface_color' => sanitize_hex_color($_POST['dark_surface_color'] ?? ''),
                    'dark_divider_color' => sanitize_hex_color($_POST['dark_divider_color'] ?? ''),
                    'dark_link_color' => sanitize_hex_color($_POST['dark_link_color'] ?? ''),
                    'dark_text_muted' => sanitize_hex_color($_POST['dark_text_muted'] ?? ''),
                    'dark_placeholder_bg' => sanitize_hex_color($_POST['dark_placeholder_bg'] ?? ''),
                    
                    // =====================
                    // EXTENDED - UI Components
                    // =====================
                    
                    // Focus Ring (Accessibility)
                    'focus_ring_color' => sanitize_hex_color($_POST['focus_ring_color'] ?? ''),
                    'focus_ring_width' => intval($_POST['focus_ring_width'] ?? 3),
                    'focus_ring_offset' => intval($_POST['focus_ring_offset'] ?? 2),
                    'dark_focus_ring_color' => sanitize_hex_color($_POST['dark_focus_ring_color'] ?? ''),
                    
                    // Form Inputs
                    'input_bg' => sanitize_hex_color($_POST['input_bg'] ?? ''),
                    'input_text' => sanitize_hex_color($_POST['input_text'] ?? ''),
                    'input_border' => sanitize_hex_color($_POST['input_border'] ?? ''),
                    'input_radius' => intval($_POST['input_radius'] ?? 6),
                    'input_placeholder' => sanitize_hex_color($_POST['input_placeholder'] ?? ''),
                    'input_focus_border' => sanitize_hex_color($_POST['input_focus_border'] ?? ''),
                    'input_error_border' => sanitize_hex_color($_POST['input_error_border'] ?? ''),
                    'input_success_border' => sanitize_hex_color($_POST['input_success_border'] ?? ''),
                    'dark_input_bg' => sanitize_hex_color($_POST['dark_input_bg'] ?? ''),
                    'dark_input_text' => sanitize_hex_color($_POST['dark_input_text'] ?? ''),
                    'dark_input_border' => sanitize_hex_color($_POST['dark_input_border'] ?? ''),
                    'dark_input_focus_border' => sanitize_hex_color($_POST['dark_input_focus_border'] ?? ''),
                    
                    // Badges & Tags
                    'badge_bg' => sanitize_hex_color($_POST['badge_bg'] ?? ''),
                    'badge_text' => sanitize_hex_color($_POST['badge_text'] ?? ''),
                    'badge_radius' => intval($_POST['badge_radius'] ?? 4),
                    'badge_font_size' => intval($_POST['badge_font_size'] ?? 12),
                    'badge_primary_bg' => sanitize_hex_color($_POST['badge_primary_bg'] ?? ''),
                    'badge_success_bg' => sanitize_hex_color($_POST['badge_success_bg'] ?? ''),
                    'badge_warning_bg' => sanitize_hex_color($_POST['badge_warning_bg'] ?? ''),
                    'badge_error_bg' => sanitize_hex_color($_POST['badge_error_bg'] ?? ''),
                    
                    // Tooltips
                    'tooltip_bg' => sanitize_hex_color($_POST['tooltip_bg'] ?? ''),
                    'tooltip_text' => sanitize_hex_color($_POST['tooltip_text'] ?? ''),
                    'tooltip_radius' => intval($_POST['tooltip_radius'] ?? 6),
                    'tooltip_font_size' => intval($_POST['tooltip_font_size'] ?? 13),
                    
                    // Scrollbar
                    'scrollbar_width' => intval($_POST['scrollbar_width'] ?? 8),
                    'scrollbar_track' => sanitize_hex_color($_POST['scrollbar_track'] ?? ''),
                    'scrollbar_thumb' => sanitize_hex_color($_POST['scrollbar_thumb'] ?? ''),
                    'scrollbar_thumb_hover' => sanitize_hex_color($_POST['scrollbar_thumb_hover'] ?? ''),
                    'dark_scrollbar_track' => sanitize_hex_color($_POST['dark_scrollbar_track'] ?? ''),
                    'dark_scrollbar_thumb' => sanitize_hex_color($_POST['dark_scrollbar_thumb'] ?? ''),
                    
                    // Loading States
                    'loading_spinner_color' => sanitize_hex_color($_POST['loading_spinner_color'] ?? ''),
                    'loading_spinner_size' => intval($_POST['loading_spinner_size'] ?? 40),
                    'loading_overlay_bg' => sanitize_text_field($_POST['loading_overlay_bg'] ?? ''),
                    'dark_loading_spinner_color' => sanitize_hex_color($_POST['dark_loading_spinner_color'] ?? ''),
                    
                    // Skeleton Loading
                    'skeleton_bg' => sanitize_hex_color($_POST['skeleton_bg'] ?? ''),
                    'skeleton_highlight' => sanitize_hex_color($_POST['skeleton_highlight'] ?? ''),
                    'dark_skeleton_bg' => sanitize_hex_color($_POST['dark_skeleton_bg'] ?? ''),
                    'dark_skeleton_highlight' => sanitize_hex_color($_POST['dark_skeleton_highlight'] ?? ''),
                    
                    // Dropdowns
                    'dropdown_bg' => sanitize_hex_color($_POST['dropdown_bg'] ?? ''),
                    'dropdown_border' => sanitize_hex_color($_POST['dropdown_border'] ?? ''),
                    'dropdown_radius' => intval($_POST['dropdown_radius'] ?? 8),
                    'dropdown_item_hover' => sanitize_hex_color($_POST['dropdown_item_hover'] ?? ''),
                    'dropdown_item_active' => sanitize_hex_color($_POST['dropdown_item_active'] ?? ''),
                    'dropdown_item_active_text' => sanitize_hex_color($_POST['dropdown_item_active_text'] ?? ''),
                    
                    // Modals
                    'modal_bg' => sanitize_hex_color($_POST['modal_bg'] ?? ''),
                    'modal_border' => sanitize_hex_color($_POST['modal_border'] ?? ''),
                    'modal_radius' => intval($_POST['modal_radius'] ?? 12),
                    'modal_backdrop' => sanitize_text_field($_POST['modal_backdrop'] ?? ''),
                    'modal_header_border' => sanitize_hex_color($_POST['modal_header_border'] ?? ''),
                    'modal_footer_bg' => sanitize_hex_color($_POST['modal_footer_bg'] ?? ''),
                    
                    // Tables
                    'table_header_bg' => sanitize_hex_color($_POST['table_header_bg'] ?? ''),
                    'table_header_text' => sanitize_hex_color($_POST['table_header_text'] ?? ''),
                    'table_row_bg' => sanitize_hex_color($_POST['table_row_bg'] ?? ''),
                    'table_row_alt_bg' => sanitize_hex_color($_POST['table_row_alt_bg'] ?? ''),
                    'table_row_hover' => sanitize_hex_color($_POST['table_row_hover'] ?? ''),
                    'table_border' => sanitize_hex_color($_POST['table_border'] ?? ''),
                    
                    // Pagination
                    'pagination_bg' => sanitize_hex_color($_POST['pagination_bg'] ?? ''),
                    'pagination_text' => sanitize_hex_color($_POST['pagination_text'] ?? ''),
                    'pagination_hover_bg' => sanitize_hex_color($_POST['pagination_hover_bg'] ?? ''),
                    'pagination_active_bg' => sanitize_hex_color($_POST['pagination_active_bg'] ?? ''),
                    'pagination_active_text' => sanitize_hex_color($_POST['pagination_active_text'] ?? ''),
                    'pagination_radius' => intval($_POST['pagination_radius'] ?? 6),
                );
                
                ES_Design_Settings::save_settings($custom_settings);
                echo '<div class="notice notice-success is-dismissible"><p>' . __('Design saved for Light & Dark Mode!', 'ensemble') . '</p></div>';
                $settings = ES_Design_Settings::get_settings(); // Reload
            }
            
            // Handle reset to layout defaults
            if (isset($_POST['es_reset_to_layout']) && check_admin_referer('es_design_action', 'es_design_nonce')) {
                ES_Design_Settings::apply_layout_preset();
                echo '<div class="notice notice-success is-dismissible"><p>' . __('Designer wurde auf die Standardwerte des Layouts zurÃ¼ckgesetzt!', 'ensemble') . '</p></div>';
                $settings = ES_Design_Settings::get_settings(); // Reload
            }
            ?>
            
            <!-- Design Editor -->
            <div class="es-card">
                <div class="es-card-body">
                    
                    <form method="post">
                        <?php wp_nonce_field('es_design_action', 'es_design_nonce'); ?>
                        
                        <!-- Designer Tabs -->
                        <div class="es-designer-tabs">
                            <button type="button" class="es-designer-tab active" data-tab="colors">
                                <span class="dashicons dashicons-art"></span>
                                <?php _e('Colors', 'ensemble'); ?>
                            </button>
                            <button type="button" class="es-designer-tab" data-tab="typography">
                                <span class="dashicons dashicons-editor-textcolor"></span>
                                <?php _e('Typography', 'ensemble'); ?>
                            </button>
                            <button type="button" class="es-designer-tab" data-tab="layout">
                                <span class="dashicons dashicons-layout"></span>
                                <?php _e('Layout', 'ensemble'); ?>
                            </button>
                            <button type="button" class="es-designer-tab" data-tab="buttons">
                                <span class="dashicons dashicons-button"></span>
                                <?php _e('Buttons', 'ensemble'); ?>
                            </button>
                            <button type="button" class="es-designer-tab" data-tab="advanced">
                                <span class="dashicons dashicons-admin-generic"></span>
                                <?php _e('Erweitert', 'ensemble'); ?>
                            </button>
                            <button type="button" class="es-designer-tab" data-tab="extended">
                                <span class="dashicons dashicons-admin-settings"></span>
                                <?php _e('Extended', 'ensemble'); ?>
                            </button>
                        </div>
                        
                        <!-- Colors Tab -->
                        <div class="es-designer-content active" data-content="colors">
                            
                            <?php 
                            // Check if layout supports both modes
                            $supports_modes = true;
                            if (class_exists('ES_Layout_Sets')) {
                                $supports_modes = ES_Layout_Sets::supports_modes($active_layout);
                            }
                            ?>
                            
                            <?php if ($supports_modes): ?>
                            <!-- Light/Dark Mode Toggle - Only for layouts that support both -->
                            <div class="es-mode-toggle-bar">
                                <span class="es-mode-toggle-label"><?php _e('Edit colors for:', 'ensemble'); ?></span>
                                <div class="es-mode-toggle-buttons">
                                    <button type="button" class="es-mode-btn active" data-mode="light">
                                        <span class="dashicons dashicons-admin-appearance"></span>
                                        <?php _e('Light Mode', 'ensemble'); ?>
                                    </button>
                                    <button type="button" class="es-mode-btn" data-mode="dark">
                                        <span class="dashicons dashicons-editor-code"></span>
                                        <?php _e('Dark Mode', 'ensemble'); ?>
                                    </button>
                                </div>
                                <span class="es-mode-hint">
                                    <?php _e('Layout-Sets can switch between modes', 'ensemble'); ?>
                                </span>
                            </div>
                            <?php else: 
                                // Get the default mode for this layout
                                $default_mode = 'dark';
                                if (class_exists('ES_Layout_Sets')) {
                                    $set_data = ES_Layout_Sets::get_set_data($active_layout);
                                    $default_mode = $set_data['default_mode'] ?? 'dark';
                                }
                            ?>
                            <!-- Single Mode Info -->
                            <div class="es-mode-toggle-bar es-single-mode">
                                <span class="es-mode-toggle-label">
                                    <?php 
                                    if ($default_mode === 'dark') {
                                        _e('Dark Mode Layout', 'ensemble');
                                    } else {
                                        _e('Light Mode Layout', 'ensemble');
                                    }
                                    ?>
                                </span>
                                <span class="es-mode-hint">
                                    <?php _e('This layout uses a fixed color scheme', 'ensemble'); ?>
                                </span>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Light Mode Colors (or Single Mode Colors) -->
                            <div class="es-mode-colors <?php echo !$supports_modes ? 'es-single-mode-colors' : ''; ?>" data-mode-content="light" <?php echo !$supports_modes ? 'style="display: block;"' : ''; ?>>
                                <?php if (!$supports_modes): ?>
                                <p class="es-color-section-hint" style="margin: 0 0 15px; color: #9ca3af; font-size: 13px;">
                                    <?php _e('Adjust colors for this dark theme layout:', 'ensemble'); ?>
                                </p>
                                <?php endif; ?>
                                <div class="es-designer-grid">
                                    
                                    <div class="es-designer-field">
                                        <label><?php _e('Primary Color', 'ensemble'); ?></label>
                                        <input type="color" name="primary_color" value="<?php echo esc_attr($settings['primary_color']); ?>" class="es-color-input">
                                        <input type="text" value="<?php echo esc_attr($settings['primary_color']); ?>" class="es-color-text" readonly>
                                    </div>
                                    
                                    <div class="es-designer-field">
                                        <label><?php _e('Secondary Color', 'ensemble'); ?></label>
                                        <input type="color" name="secondary_color" value="<?php echo esc_attr($settings['secondary_color']); ?>" class="es-color-input">
                                        <input type="text" value="<?php echo esc_attr($settings['secondary_color']); ?>" class="es-color-text" readonly>
                                    </div>
                                    
                                    <div class="es-designer-field">
                                        <label><?php _e('Background', 'ensemble'); ?></label>
                                        <input type="color" name="background_color" value="<?php echo esc_attr($settings['background_color']); ?>" class="es-color-input">
                                        <input type="text" value="<?php echo esc_attr($settings['background_color']); ?>" class="es-color-text" readonly>
                                    </div>
                                    
                                    <div class="es-designer-field">
                                        <label><?php _e('Card Background', 'ensemble'); ?></label>
                                        <input type="color" name="card_background" value="<?php echo esc_attr($settings['card_background']); ?>" class="es-color-input">
                                        <input type="text" value="<?php echo esc_attr($settings['card_background']); ?>" class="es-color-text" readonly>
                                    </div>
                                    
                                    <div class="es-designer-field">
                                        <label><?php _e('Text Color', 'ensemble'); ?></label>
                                        <input type="color" name="text_color" value="<?php echo esc_attr($settings['text_color']); ?>" class="es-color-input">
                                        <input type="text" value="<?php echo esc_attr($settings['text_color']); ?>" class="es-color-text" readonly>
                                    </div>
                                    
                                    <div class="es-designer-field">
                                        <label><?php _e('Secondary Text', 'ensemble'); ?></label>
                                        <input type="color" name="text_secondary" value="<?php echo esc_attr($settings['text_secondary']); ?>" class="es-color-input">
                                        <input type="text" value="<?php echo esc_attr($settings['text_secondary']); ?>" class="es-color-text" readonly>
                                    </div>
                                    
                                    <div class="es-designer-field">
                                        <label><?php _e('Button Background', 'ensemble'); ?></label>
                                        <input type="color" name="button_bg" value="<?php echo esc_attr($settings['button_bg']); ?>" class="es-color-input">
                                        <input type="text" value="<?php echo esc_attr($settings['button_bg']); ?>" class="es-color-text" readonly>
                                    </div>
                                    
                                    <div class="es-designer-field">
                                        <label><?php _e('Button Text', 'ensemble'); ?></label>
                                        <input type="color" name="button_text" value="<?php echo esc_attr($settings['button_text']); ?>" class="es-color-input">
                                        <input type="text" value="<?php echo esc_attr($settings['button_text']); ?>" class="es-color-text" readonly>
                                    </div>
                                    
                                    <div class="es-designer-field">
                                        <label><?php _e('Button Hover', 'ensemble'); ?></label>
                                        <input type="color" name="button_hover_bg" value="<?php echo esc_attr($settings['button_hover_bg']); ?>" class="es-color-input">
                                        <input type="text" value="<?php echo esc_attr($settings['button_hover_bg']); ?>" class="es-color-text" readonly>
                                    </div>
                                    
                                    <div class="es-designer-field">
                                        <label><?php _e('Border Color', 'ensemble'); ?></label>
                                        <input type="color" name="card_border" value="<?php echo esc_attr($settings['card_border']); ?>" class="es-color-input">
                                        <input type="text" value="<?php echo esc_attr($settings['card_border']); ?>" class="es-color-text" readonly>
                                    </div>
                                    
                                </div>
                            </div>
                            
                            <!-- Dark Mode Colors - Only for layouts that support both modes -->
                            <?php if ($supports_modes): ?>
                            <div class="es-mode-colors" data-mode-content="dark" style="display: none;">
                                <div class="es-designer-grid">
                                    
                                    <div class="es-designer-field">
                                        <label><?php _e('Primary Color', 'ensemble'); ?></label>
                                        <input type="color" name="dark_primary_color" value="<?php echo esc_attr($settings['dark_primary_color'] ?? '#818cf8'); ?>" class="es-color-input">
                                        <input type="text" value="<?php echo esc_attr($settings['dark_primary_color'] ?? '#818cf8'); ?>" class="es-color-text" readonly>
                                    </div>
                                    
                                    <div class="es-designer-field">
                                        <label><?php _e('Secondary Color', 'ensemble'); ?></label>
                                        <input type="color" name="dark_secondary_color" value="<?php echo esc_attr($settings['dark_secondary_color'] ?? '#a78bfa'); ?>" class="es-color-input">
                                        <input type="text" value="<?php echo esc_attr($settings['dark_secondary_color'] ?? '#a78bfa'); ?>" class="es-color-text" readonly>
                                    </div>
                                    
                                    <div class="es-designer-field">
                                        <label><?php _e('Background', 'ensemble'); ?></label>
                                        <input type="color" name="dark_background_color" value="<?php echo esc_attr($settings['dark_background_color'] ?? '#0a0a0a'); ?>" class="es-color-input">
                                        <input type="text" value="<?php echo esc_attr($settings['dark_background_color'] ?? '#0a0a0a'); ?>" class="es-color-text" readonly>
                                    </div>
                                    
                                    <div class="es-designer-field">
                                        <label><?php _e('Card Background', 'ensemble'); ?></label>
                                        <input type="color" name="dark_card_background" value="<?php echo esc_attr($settings['dark_card_background'] ?? '#1a1a1a'); ?>" class="es-color-input">
                                        <input type="text" value="<?php echo esc_attr($settings['dark_card_background'] ?? '#1a1a1a'); ?>" class="es-color-text" readonly>
                                    </div>
                                    
                                    <div class="es-designer-field">
                                        <label><?php _e('Text Color', 'ensemble'); ?></label>
                                        <input type="color" name="dark_text_color" value="<?php echo esc_attr($settings['dark_text_color'] ?? '#fafafa'); ?>" class="es-color-input">
                                        <input type="text" value="<?php echo esc_attr($settings['dark_text_color'] ?? '#fafafa'); ?>" class="es-color-text" readonly>
                                    </div>
                                    
                                    <div class="es-designer-field">
                                        <label><?php _e('Secondary Text', 'ensemble'); ?></label>
                                        <input type="color" name="dark_text_secondary" value="<?php echo esc_attr($settings['dark_text_secondary'] ?? '#a1a1aa'); ?>" class="es-color-input">
                                        <input type="text" value="<?php echo esc_attr($settings['dark_text_secondary'] ?? '#a1a1aa'); ?>" class="es-color-text" readonly>
                                    </div>
                                    
                                    <div class="es-designer-field">
                                        <label><?php _e('Button Background', 'ensemble'); ?></label>
                                        <input type="color" name="dark_button_bg" value="<?php echo esc_attr($settings['dark_button_bg'] ?? '#818cf8'); ?>" class="es-color-input">
                                        <input type="text" value="<?php echo esc_attr($settings['dark_button_bg'] ?? '#818cf8'); ?>" class="es-color-text" readonly>
                                    </div>
                                    
                                    <div class="es-designer-field">
                                        <label><?php _e('Button Text', 'ensemble'); ?></label>
                                        <input type="color" name="dark_button_text" value="<?php echo esc_attr($settings['dark_button_text'] ?? '#0a0a0a'); ?>" class="es-color-input">
                                        <input type="text" value="<?php echo esc_attr($settings['dark_button_text'] ?? '#0a0a0a'); ?>" class="es-color-text" readonly>
                                    </div>
                                    
                                    <div class="es-designer-field">
                                        <label><?php _e('Button Hover', 'ensemble'); ?></label>
                                        <input type="color" name="dark_button_hover_bg" value="<?php echo esc_attr($settings['dark_button_hover_bg'] ?? '#6366f1'); ?>" class="es-color-input">
                                        <input type="text" value="<?php echo esc_attr($settings['dark_button_hover_bg'] ?? '#6366f1'); ?>" class="es-color-text" readonly>
                                    </div>
                                    
                                    <div class="es-designer-field">
                                        <label><?php _e('Border Color', 'ensemble'); ?></label>
                                        <input type="color" name="dark_card_border" value="<?php echo esc_attr($settings['dark_card_border'] ?? '#333333'); ?>" class="es-color-input">
                                        <input type="text" value="<?php echo esc_attr($settings['dark_card_border'] ?? '#333333'); ?>" class="es-color-text" readonly>
                                    </div>
                                    
                                </div>
                            </div>
                            <?php endif; ?>
                            
                        </div>
                        
                        <!-- Typography Tab -->
                        <div class="es-designer-content" data-content="typography">
                            <div class="es-designer-grid">
                                
                                <?php 
                                // Get fonts from Font Manager
                                $font_manager = class_exists('ES_Font_Manager') ? ES_Font_Manager::instance() : null;
                                $curated_fonts = $font_manager ? $font_manager->get_curated_fonts() : array();
                                $system_fonts = $font_manager ? $font_manager->get_system_fonts() : array();
                                $is_pro = function_exists('ensemble_is_pro') && ensemble_is_pro();
                                $all_google_fonts = ($is_pro && $font_manager) ? $font_manager->get_all_google_fonts() : array();
                                $custom_fonts = ($is_pro && $font_manager) ? $font_manager->get_custom_fonts() : array();
                                
                                // Build font options for select
                                $available_fonts = $is_pro ? array_merge($curated_fonts, $all_google_fonts) : $curated_fonts;
                                
                                // Group by category
                                $font_groups = array(
                                    'system' => array('label' => __('System', 'ensemble'), 'fonts' => $system_fonts),
                                    'sans-serif' => array('label' => __('Sans-Serif', 'ensemble'), 'fonts' => array()),
                                    'serif' => array('label' => __('Serif', 'ensemble'), 'fonts' => array()),
                                    'display' => array('label' => __('Display', 'ensemble'), 'fonts' => array()),
                                    'monospace' => array('label' => __('Monospace', 'ensemble'), 'fonts' => array()),
                                );
                                
                                foreach ($available_fonts as $name => $data) {
                                    $cat = $data['category'] ?? 'sans-serif';
                                    if (isset($font_groups[$cat])) {
                                        $font_groups[$cat]['fonts'][$name] = $data;
                                    }
                                }
                                
                                // Add custom fonts if any
                                if (!empty($custom_fonts)) {
                                    $font_groups['custom'] = array('label' => __('Custom Fonts', 'ensemble'), 'fonts' => array());
                                    foreach ($custom_fonts as $font) {
                                        $font_groups['custom']['fonts'][$font['name']] = array('category' => 'custom');
                                    }
                                }
                                ?>
                                
                                <div class="es-designer-field es-font-picker-field">
                                    <label><?php _e('Heading Font', 'ensemble'); ?></label>
                                    <div class="es-font-picker" data-target="heading_font">
                                        <div class="es-font-dropdown-trigger" tabindex="0">
                                            <span class="es-selected-font-name" style="font-family: '<?php echo esc_attr($settings['heading_font']); ?>', sans-serif;"><?php echo esc_html($settings['heading_font']); ?></span>
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <polyline points="6 9 12 15 18 9"></polyline>
                                            </svg>
                                        </div>
                                        <div class="es-font-dropdown">
                                            <input type="text" class="es-font-search" placeholder="<?php _e('Search fonts...', 'ensemble'); ?>">
                                            <div class="es-font-list">
                                                <?php foreach ($font_groups as $group_key => $group): ?>
                                                    <?php if (empty($group['fonts'])) continue; ?>
                                                    <div class="es-font-group">
                                                        <div class="es-font-group-label"><?php echo esc_html($group['label']); ?></div>
                                                        <?php foreach ($group['fonts'] as $font_name => $font_data): ?>
                                                        <div class="es-font-option <?php echo $settings['heading_font'] === $font_name ? 'selected' : ''; ?>" 
                                                             data-font="<?php echo esc_attr($font_name); ?>"
                                                             data-category="<?php echo esc_attr($font_data['category'] ?? 'sans-serif'); ?>">
                                                            <span class="es-font-option-name" style="font-family: '<?php echo esc_attr($font_name); ?>', sans-serif;">
                                                                <?php echo esc_html($font_name); ?>
                                                            </span>
                                                            <span class="es-font-option-sample" style="font-family: '<?php echo esc_attr($font_name); ?>', sans-serif;">
                                                                Aa
                                                            </span>
                                                        </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <input type="hidden" name="heading_font" value="<?php echo esc_attr($settings['heading_font']); ?>">
                                    </div>
                                    <?php if (!$is_pro): ?>
                                    <small class="es-field-hint"><?php _e('More fonts with Pro', 'ensemble'); ?></small>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="es-designer-field es-font-picker-field">
                                    <label><?php _e('Body Font', 'ensemble'); ?></label>
                                    <div class="es-font-picker" data-target="body_font">
                                        <div class="es-font-dropdown-trigger" tabindex="0">
                                            <span class="es-selected-font-name" style="font-family: '<?php echo esc_attr($settings['body_font']); ?>', sans-serif;"><?php echo esc_html($settings['body_font']); ?></span>
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <polyline points="6 9 12 15 18 9"></polyline>
                                            </svg>
                                        </div>
                                        <div class="es-font-dropdown">
                                            <input type="text" class="es-font-search" placeholder="<?php _e('Search fonts...', 'ensemble'); ?>">
                                            <div class="es-font-list">
                                                <?php foreach ($font_groups as $group_key => $group): ?>
                                                    <?php if (empty($group['fonts'])) continue; ?>
                                                    <?php if ($group_key === 'display') continue; ?>
                                                    <div class="es-font-group">
                                                        <div class="es-font-group-label"><?php echo esc_html($group['label']); ?></div>
                                                        <?php foreach ($group['fonts'] as $font_name => $font_data): ?>
                                                        <div class="es-font-option <?php echo $settings['body_font'] === $font_name ? 'selected' : ''; ?>" 
                                                             data-font="<?php echo esc_attr($font_name); ?>"
                                                             data-category="<?php echo esc_attr($font_data['category'] ?? 'sans-serif'); ?>">
                                                            <span class="es-font-option-name" style="font-family: '<?php echo esc_attr($font_name); ?>', sans-serif;">
                                                                <?php echo esc_html($font_name); ?>
                                                            </span>
                                                            <span class="es-font-option-sample" style="font-family: '<?php echo esc_attr($font_name); ?>', sans-serif;">
                                                                Aa
                                                            </span>
                                                        </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <input type="hidden" name="body_font" value="<?php echo esc_attr($settings['body_font']); ?>">
                                    </div>
                                </div>
                                
                                <div class="es-designer-field">
                                    <label><?php _e('H1 Size (px)', 'ensemble'); ?></label>
                                    <input type="number" name="h1_size" value="<?php echo esc_attr($settings['h1_size']); ?>" min="20" max="72" class="es-number-input">
                                </div>
                                
                                <div class="es-designer-field">
                                    <label><?php _e('H2 Size (px)', 'ensemble'); ?></label>
                                    <input type="number" name="h2_size" value="<?php echo esc_attr($settings['h2_size']); ?>" min="18" max="56" class="es-number-input">
                                </div>
                                
                                <div class="es-designer-field">
                                    <label><?php _e('H3 Size (px)', 'ensemble'); ?></label>
                                    <input type="number" name="h3_size" value="<?php echo esc_attr($settings['h3_size']); ?>" min="16" max="48" class="es-number-input">
                                </div>
                                
                                <div class="es-designer-field">
                                    <label><?php _e('Body Size (px)', 'ensemble'); ?></label>
                                    <input type="number" name="body_size" value="<?php echo esc_attr($settings['body_size']); ?>" min="12" max="24" class="es-number-input">
                                </div>
                                
                                <div class="es-designer-field">
                                    <label><?php _e('Line Height', 'ensemble'); ?></label>
                                    <input type="number" name="line_height" value="<?php echo esc_attr($settings['line_height']); ?>" min="1" max="3" step="0.1" class="es-number-input">
                                </div>
                                
                            </div>
                            
                            <!-- Extended Typography Section -->
                            <div class="es-designer-section-divider">
                                <span class="dashicons dashicons-arrow-down-alt2"></span>
                                <?php _e('Extended Sizes', 'ensemble'); ?>
                                <small style="opacity: 0.7; margin-left: 8px;"><?php _e('For specific elements', 'ensemble'); ?></small>
                            </div>
                            
                            <div class="es-designer-grid">
                                
                                <div class="es-designer-field">
                                    <label><?php _e('XS Size (px)', 'ensemble'); ?></label>
                                    <input type="number" name="xs_size" value="<?php echo esc_attr($settings['xs_size'] ?? 12); ?>" min="8" max="14" class="es-number-input">
                                    <small class="es-field-hint"><?php _e('Labels, badges', 'ensemble'); ?></small>
                                </div>
                                
                                <div class="es-designer-field">
                                    <label><?php _e('Meta Size (px)', 'ensemble'); ?></label>
                                    <input type="number" name="meta_size" value="<?php echo esc_attr($settings['meta_size'] ?? 14); ?>" min="10" max="18" class="es-number-input">
                                    <small class="es-field-hint"><?php _e('Meta info, dates', 'ensemble'); ?></small>
                                </div>
                                
                                <div class="es-designer-field">
                                    <label><?php _e('Large Size (px)', 'ensemble'); ?></label>
                                    <input type="number" name="lg_size" value="<?php echo esc_attr($settings['lg_size'] ?? 18); ?>" min="14" max="24" class="es-number-input">
                                    <small class="es-field-hint"><?php _e('Larger body text', 'ensemble'); ?></small>
                                </div>
                                
                                <div class="es-designer-field">
                                    <label><?php _e('XL Size (px)', 'ensemble'); ?></label>
                                    <input type="number" name="xl_size" value="<?php echo esc_attr($settings['xl_size'] ?? 20); ?>" min="16" max="28" class="es-number-input">
                                    <small class="es-field-hint"><?php _e('Subtitles, emphasis', 'ensemble'); ?></small>
                                </div>
                                
                                <div class="es-designer-field">
                                    <label><?php _e('Price Size (px)', 'ensemble'); ?></label>
                                    <input type="number" name="price_size" value="<?php echo esc_attr($settings['price_size'] ?? 32); ?>" min="20" max="48" class="es-number-input">
                                    <small class="es-field-hint"><?php _e('Price displays', 'ensemble'); ?></small>
                                </div>
                                
                                <div class="es-designer-field">
                                    <label><?php _e('Hero Size (px)', 'ensemble'); ?></label>
                                    <input type="number" name="hero_size" value="<?php echo esc_attr($settings['hero_size'] ?? 72); ?>" min="36" max="120" class="es-number-input">
                                    <small class="es-field-hint"><?php _e('Hero headlines', 'ensemble'); ?></small>
                                </div>
                                
                            </div>
                        </div>
                        
                        <!-- Layout Tab -->
                        <div class="es-designer-content" data-content="layout">
                            <div class="es-designer-grid">
                                
                                <div class="es-designer-field">
                                    <label><?php _e('Grid Gap (px)', 'ensemble'); ?></label>
                                    <input type="number" name="grid_gap" value="<?php echo esc_attr($settings['grid_gap']); ?>" min="0" max="100" class="es-number-input">
                                </div>
                                
                                <div class="es-designer-field">
                                    <label><?php _e('Card Radius (px)', 'ensemble'); ?></label>
                                    <input type="number" name="card_radius" value="<?php echo esc_attr($settings['card_radius']); ?>" min="0" max="50" class="es-number-input">
                                </div>
                                
                                <div class="es-designer-field">
                                    <label><?php _e('Card Padding (px)', 'ensemble'); ?></label>
                                    <input type="number" name="card_padding" value="<?php echo esc_attr($settings['card_padding']); ?>" min="0" max="100" class="es-number-input">
                                </div>
                                
                                <div class="es-designer-field">
                                    <label><?php _e('Card Shadow', 'ensemble'); ?></label>
                                    <select name="card_shadow" class="es-select-input">
                                        <option value="none" <?php selected($settings['card_shadow'], 'none'); ?>><?php _e('None', 'ensemble'); ?></option>
                                        <option value="small" <?php selected($settings['card_shadow'], 'small'); ?>><?php _e('Small', 'ensemble'); ?></option>
                                        <option value="medium" <?php selected($settings['card_shadow'], 'medium'); ?>><?php _e('Medium', 'ensemble'); ?></option>
                                        <option value="large" <?php selected($settings['card_shadow'], 'large'); ?>><?php _e('Large', 'ensemble'); ?></option>
                                    </select>
                                </div>
                                
                                <div class="es-designer-field">
                                    <label><?php _e('Card Hover Effect', 'ensemble'); ?></label>
                                    <select name="card_hover" class="es-select-input">
                                        <option value="none" <?php selected($settings['card_hover'], 'none'); ?>><?php _e('None', 'ensemble'); ?></option>
                                        <option value="lift" <?php selected($settings['card_hover'], 'lift'); ?>><?php _e('Lift', 'ensemble'); ?></option>
                                        <option value="glow" <?php selected($settings['card_hover'], 'glow'); ?>><?php _e('Glow', 'ensemble'); ?></option>
                                        <option value="scale" <?php selected($settings['card_hover'], 'scale'); ?>><?php _e('Scale', 'ensemble'); ?></option>
                                    </select>
                                </div>
                                
                                <div class="es-designer-field">
                                    <label><?php _e('Card Border Width (px)', 'ensemble'); ?></label>
                                    <input type="number" name="card_border_width" value="<?php echo esc_attr($settings['card_border_width']); ?>" min="0" max="10" class="es-number-input">
                                </div>
                                
                            </div>
                        </div>
                        
                        <!-- Buttons Tab -->
                        <div class="es-designer-content" data-content="buttons">
                            <div class="es-designer-grid">
                                
                                <div class="es-designer-field">
                                    <label><?php _e('Button Radius (px)', 'ensemble'); ?></label>
                                    <input type="number" name="button_radius" value="<?php echo esc_attr($settings['button_radius']); ?>" min="0" max="50" class="es-number-input">
                                </div>
                                
                                <div class="es-designer-field">
                                    <label><?php _e('Button Padding Vertical (px)', 'ensemble'); ?></label>
                                    <input type="number" name="button_padding_v" value="<?php echo esc_attr($settings['button_padding_v']); ?>" min="0" max="50" class="es-number-input">
                                </div>
                                
                                <div class="es-designer-field">
                                    <label><?php _e('Button Padding Horizontal (px)', 'ensemble'); ?></label>
                                    <input type="number" name="button_padding_h" value="<?php echo esc_attr($settings['button_padding_h']); ?>" min="0" max="100" class="es-number-input">
                                </div>
                                
                                <div class="es-designer-field">
                                    <label><?php _e('Button Font Size (px)', 'ensemble'); ?></label>
                                    <input type="number" name="button_font_size" value="<?php echo esc_attr($settings['button_font_size']); ?>" min="10" max="32" class="es-number-input">
                                </div>
                                
                                <div class="es-designer-field">
                                    <label><?php _e('Button Font Weight', 'ensemble'); ?></label>
                                    <select name="button_weight" class="es-select-input">
                                        <option value="400" <?php selected($settings['button_weight'], 400); ?>>400 (Normal)</option>
                                        <option value="500" <?php selected($settings['button_weight'], 500); ?>>500 (Medium)</option>
                                        <option value="600" <?php selected($settings['button_weight'], 600); ?>>600 (Semi Bold)</option>
                                        <option value="700" <?php selected($settings['button_weight'], 700); ?>>700 (Bold)</option>
                                    </select>
                                </div>
                                
                                <div class="es-designer-field">
                                    <label><?php _e('Button Border Width (px)', 'ensemble'); ?></label>
                                    <input type="number" name="button_border_width" value="<?php echo esc_attr($settings['button_border_width']); ?>" min="0" max="10" class="es-number-input">
                                </div>
                                
                            </div>
                        </div>
                        
                        <!-- Advanced Tab -->
                        <div class="es-designer-content" data-content="advanced">
                            
                            <!-- Surface & Links -->
                            <div class="es-designer-section-title">
                                <span class="dashicons dashicons-admin-appearance"></span>
                                <?php _e('OberflÃ¤chen & Links', 'ensemble'); ?>
                            </div>
                            <div class="es-designer-grid">
                                <div class="es-designer-field">
                                    <label><?php _e('OberflÃ¤che', 'ensemble'); ?></label>
                                    <input type="color" name="surface_color" value="<?php echo esc_attr($settings['surface_color'] ?? '#ffffff'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['surface_color'] ?? '#ffffff'); ?>" class="es-color-text" readonly>
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Trennlinien', 'ensemble'); ?></label>
                                    <input type="color" name="divider_color" value="<?php echo esc_attr($settings['divider_color'] ?? '#e2e8f0'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['divider_color'] ?? '#e2e8f0'); ?>" class="es-color-text" readonly>
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Link-Farbe', 'ensemble'); ?></label>
                                    <input type="color" name="link_color" value="<?php echo esc_attr($settings['link_color'] ?? $settings['primary_color']); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['link_color'] ?? $settings['primary_color']); ?>" class="es-color-text" readonly>
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('GedÃ¤mpfter Text', 'ensemble'); ?></label>
                                    <input type="color" name="text_muted" value="<?php echo esc_attr($settings['text_muted'] ?? '#a0aec0'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['text_muted'] ?? '#a0aec0'); ?>" class="es-color-text" readonly>
                                </div>
                            </div>
                            
                            <!-- Overlay Colors -->
                            <div class="es-designer-section-title" style="margin-top: 30px;">
                                <span class="dashicons dashicons-format-image"></span>
                                <?php _e('Overlay (Text Ã¼ber Bildern)', 'ensemble'); ?>
                            </div>
                            <p class="description" style="margin: -10px 0 15px; color: #666;">
                                <?php _e('Farben fÃ¼r Text-Overlays auf Bildern und Cards mit Hintergrundbildern.', 'ensemble'); ?>
                            </p>
                            <div class="es-designer-grid">
                                <div class="es-designer-field">
                                    <label><?php _e('Overlay Hintergrund', 'ensemble'); ?></label>
                                    <input type="text" name="overlay_bg" value="<?php echo esc_attr($settings['overlay_bg'] ?? 'rgba(0, 0, 0, 0.7)'); ?>" class="es-text-input" placeholder="rgba(0, 0, 0, 0.7)">
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Overlay Text', 'ensemble'); ?></label>
                                    <input type="color" name="overlay_text" value="<?php echo esc_attr($settings['overlay_text'] ?? '#ffffff'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['overlay_text'] ?? '#ffffff'); ?>" class="es-color-text" readonly>
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Overlay SekundÃ¤rtext', 'ensemble'); ?></label>
                                    <input type="text" name="overlay_text_secondary" value="<?php echo esc_attr($settings['overlay_text_secondary'] ?? 'rgba(255, 255, 255, 0.8)'); ?>" class="es-text-input" placeholder="rgba(255, 255, 255, 0.8)">
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Overlay Rahmen', 'ensemble'); ?></label>
                                    <input type="text" name="overlay_border" value="<?php echo esc_attr($settings['overlay_border'] ?? 'rgba(255, 255, 255, 0.2)'); ?>" class="es-text-input" placeholder="rgba(255, 255, 255, 0.2)">
                                </div>
                            </div>
                            
                            <!-- Placeholder Colors -->
                            <div class="es-designer-section-title" style="margin-top: 30px;">
                                <span class="dashicons dashicons-format-gallery"></span>
                                <?php _e('Platzhalter (fehlende Bilder)', 'ensemble'); ?>
                            </div>
                            <div class="es-designer-grid">
                                <div class="es-designer-field">
                                    <label><?php _e('Platzhalter Hintergrund', 'ensemble'); ?></label>
                                    <input type="color" name="placeholder_bg" value="<?php echo esc_attr($settings['placeholder_bg'] ?? '#e2e8f0'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['placeholder_bg'] ?? '#e2e8f0'); ?>" class="es-color-text" readonly>
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Platzhalter Icon', 'ensemble'); ?></label>
                                    <input type="color" name="placeholder_icon" value="<?php echo esc_attr($settings['placeholder_icon'] ?? '#a0aec0'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['placeholder_icon'] ?? '#a0aec0'); ?>" class="es-color-text" readonly>
                                </div>
                            </div>
                            
                            <!-- Status Colors -->
                            <div class="es-designer-section-title" style="margin-top: 30px;">
                                <span class="dashicons dashicons-tag"></span>
                                <?php _e('Status-Farben', 'ensemble'); ?>
                            </div>
                            <div class="es-designer-grid">
                                <div class="es-designer-field">
                                    <label><?php _e('Abgesagt', 'ensemble'); ?></label>
                                    <input type="color" name="status_cancelled" value="<?php echo esc_attr($settings['status_cancelled'] ?? '#dc2626'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['status_cancelled'] ?? '#dc2626'); ?>" class="es-color-text" readonly>
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Ausverkauft', 'ensemble'); ?></label>
                                    <input type="color" name="status_soldout" value="<?php echo esc_attr($settings['status_soldout'] ?? '#1a202c'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['status_soldout'] ?? '#1a202c'); ?>" class="es-color-text" readonly>
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Verschoben', 'ensemble'); ?></label>
                                    <input type="color" name="status_postponed" value="<?php echo esc_attr($settings['status_postponed'] ?? '#d97706'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['status_postponed'] ?? '#d97706'); ?>" class="es-color-text" readonly>
                                </div>
                            </div>
                            
                            <!-- Gradient Colors -->
                            <div class="es-designer-section-title" style="margin-top: 30px;">
                                <span class="dashicons dashicons-image-filter"></span>
                                <?php _e('Verlauf (Card-Overlay)', 'ensemble'); ?>
                            </div>
                            <p class="description" style="margin: -10px 0 15px; color: #666;">
                                <?php _e('Der Verlauf Ã¼ber Card-Bildern fÃ¼r bessere Lesbarkeit.', 'ensemble'); ?>
                            </p>
                            <div class="es-designer-grid">
                                <div class="es-designer-field">
                                    <label><?php _e('Verlauf Start (unten)', 'ensemble'); ?></label>
                                    <input type="text" name="gradient_start" value="<?php echo esc_attr($settings['gradient_start'] ?? 'rgba(0, 0, 0, 0.8)'); ?>" class="es-text-input" placeholder="rgba(0, 0, 0, 0.8)">
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Verlauf Mitte', 'ensemble'); ?></label>
                                    <input type="text" name="gradient_mid" value="<?php echo esc_attr($settings['gradient_mid'] ?? 'rgba(0, 0, 0, 0.4)'); ?>" class="es-text-input" placeholder="rgba(0, 0, 0, 0.4)">
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Verlauf Ende (oben)', 'ensemble'); ?></label>
                                    <input type="text" name="gradient_end" value="<?php echo esc_attr($settings['gradient_end'] ?? 'transparent'); ?>" class="es-text-input" placeholder="transparent">
                                </div>
                            </div>
                            
                            <!-- Social Colors -->
                            <div class="es-designer-section-title" style="margin-top: 30px;">
                                <span class="dashicons dashicons-share"></span>
                                <?php _e('Social Media', 'ensemble'); ?>
                            </div>
                            <div class="es-designer-grid">
                                <div class="es-designer-field">
                                    <label><?php _e('Facebook', 'ensemble'); ?></label>
                                    <input type="color" name="facebook_color" value="<?php echo esc_attr($settings['facebook_color'] ?? '#1877f2'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['facebook_color'] ?? '#1877f2'); ?>" class="es-color-text" readonly>
                                </div>
                            </div>
                            
                            <?php if ($supports_modes): ?>
                            <!-- Dark Mode Advanced -->
                            <div class="es-designer-section-title" style="margin-top: 40px; padding-top: 20px; border-top: 2px solid #e0e0e0;">
                                <span class="dashicons dashicons-moon"></span>
                                <?php _e('Dark Mode - Erweiterte Farben', 'ensemble'); ?>
                            </div>
                            <div class="es-designer-grid">
                                <div class="es-designer-field">
                                    <label><?php _e('OberflÃ¤che (Dark)', 'ensemble'); ?></label>
                                    <input type="color" name="dark_surface_color" value="<?php echo esc_attr($settings['dark_surface_color'] ?? '#111111'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['dark_surface_color'] ?? '#111111'); ?>" class="es-color-text" readonly>
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Trennlinien (Dark)', 'ensemble'); ?></label>
                                    <input type="color" name="dark_divider_color" value="<?php echo esc_attr($settings['dark_divider_color'] ?? '#333333'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['dark_divider_color'] ?? '#333333'); ?>" class="es-color-text" readonly>
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Link-Farbe (Dark)', 'ensemble'); ?></label>
                                    <input type="color" name="dark_link_color" value="<?php echo esc_attr($settings['dark_link_color'] ?? '#818cf8'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['dark_link_color'] ?? '#818cf8'); ?>" class="es-color-text" readonly>
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('GedÃ¤mpfter Text (Dark)', 'ensemble'); ?></label>
                                    <input type="color" name="dark_text_muted" value="<?php echo esc_attr($settings['dark_text_muted'] ?? '#666666'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['dark_text_muted'] ?? '#666666'); ?>" class="es-color-text" readonly>
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Platzhalter BG (Dark)', 'ensemble'); ?></label>
                                    <input type="color" name="dark_placeholder_bg" value="<?php echo esc_attr($settings['dark_placeholder_bg'] ?? '#2a2a2a'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['dark_placeholder_bg'] ?? '#2a2a2a'); ?>" class="es-color-text" readonly>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                        </div>
                        
                        <!-- Extended Tab - UI Components -->
                        <div class="es-designer-content" data-content="extended">
                            
                            <p class="es-tab-intro" style="margin: 0 0 25px; color: #9ca3af; font-size: 14px; line-height: 1.6;">
                                <?php _e('Extended UI component styles for forms, badges, tooltips, and other interface elements. These ensure visual consistency across the entire plugin.', 'ensemble'); ?>
                            </p>
                            
                            <!-- Focus Ring (Accessibility) -->
                            <div class="es-designer-section-title">
                                <span class="dashicons dashicons-visibility"></span>
                                <?php _e('Focus Ring (Accessibility)', 'ensemble'); ?>
                            </div>
                            <p class="description" style="margin: -10px 0 15px; color: #666;">
                                <?php _e('Visual feedback for keyboard navigation. Important for accessibility compliance.', 'ensemble'); ?>
                            </p>
                            <div class="es-designer-grid">
                                <div class="es-designer-field">
                                    <label><?php _e('Focus Color', 'ensemble'); ?></label>
                                    <input type="color" name="focus_ring_color" value="<?php echo esc_attr($settings['focus_ring_color'] ?? '#667eea'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['focus_ring_color'] ?? '#667eea'); ?>" class="es-color-text" readonly>
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Focus Width (px)', 'ensemble'); ?></label>
                                    <input type="number" name="focus_ring_width" value="<?php echo esc_attr($settings['focus_ring_width'] ?? 3); ?>" min="1" max="6" class="es-number-input">
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Focus Offset (px)', 'ensemble'); ?></label>
                                    <input type="number" name="focus_ring_offset" value="<?php echo esc_attr($settings['focus_ring_offset'] ?? 2); ?>" min="0" max="6" class="es-number-input">
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Focus Color (Dark)', 'ensemble'); ?></label>
                                    <input type="color" name="dark_focus_ring_color" value="<?php echo esc_attr($settings['dark_focus_ring_color'] ?? '#818cf8'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['dark_focus_ring_color'] ?? '#818cf8'); ?>" class="es-color-text" readonly>
                                </div>
                            </div>
                            
                            <!-- Form Inputs -->
                            <div class="es-designer-section-title" style="margin-top: 30px;">
                                <span class="dashicons dashicons-editor-textcolor"></span>
                                <?php _e('Form Inputs', 'ensemble'); ?>
                            </div>
                            <div class="es-designer-grid">
                                <div class="es-designer-field">
                                    <label><?php _e('Input Background', 'ensemble'); ?></label>
                                    <input type="color" name="input_bg" value="<?php echo esc_attr($settings['input_bg'] ?? '#ffffff'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['input_bg'] ?? '#ffffff'); ?>" class="es-color-text" readonly>
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Input Text', 'ensemble'); ?></label>
                                    <input type="color" name="input_text" value="<?php echo esc_attr($settings['input_text'] ?? '#1a202c'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['input_text'] ?? '#1a202c'); ?>" class="es-color-text" readonly>
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Input Border', 'ensemble'); ?></label>
                                    <input type="color" name="input_border" value="<?php echo esc_attr($settings['input_border'] ?? '#e2e8f0'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['input_border'] ?? '#e2e8f0'); ?>" class="es-color-text" readonly>
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Input Radius (px)', 'ensemble'); ?></label>
                                    <input type="number" name="input_radius" value="<?php echo esc_attr($settings['input_radius'] ?? 6); ?>" min="0" max="20" class="es-number-input">
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Placeholder Color', 'ensemble'); ?></label>
                                    <input type="color" name="input_placeholder" value="<?php echo esc_attr($settings['input_placeholder'] ?? '#a0aec0'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['input_placeholder'] ?? '#a0aec0'); ?>" class="es-color-text" readonly>
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Focus Border', 'ensemble'); ?></label>
                                    <input type="color" name="input_focus_border" value="<?php echo esc_attr($settings['input_focus_border'] ?? '#667eea'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['input_focus_border'] ?? '#667eea'); ?>" class="es-color-text" readonly>
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Error Border', 'ensemble'); ?></label>
                                    <input type="color" name="input_error_border" value="<?php echo esc_attr($settings['input_error_border'] ?? '#dc2626'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['input_error_border'] ?? '#dc2626'); ?>" class="es-color-text" readonly>
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Success Border', 'ensemble'); ?></label>
                                    <input type="color" name="input_success_border" value="<?php echo esc_attr($settings['input_success_border'] ?? '#10b981'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['input_success_border'] ?? '#10b981'); ?>" class="es-color-text" readonly>
                                </div>
                            </div>
                            
                            <!-- Dark Mode Inputs -->
                            <div class="es-designer-section-divider">
                                <span class="dashicons dashicons-arrow-down-alt2"></span>
                                <?php _e('Dark Mode Inputs', 'ensemble'); ?>
                            </div>
                            <div class="es-designer-grid">
                                <div class="es-designer-field">
                                    <label><?php _e('Input BG (Dark)', 'ensemble'); ?></label>
                                    <input type="color" name="dark_input_bg" value="<?php echo esc_attr($settings['dark_input_bg'] ?? '#1a1a1a'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['dark_input_bg'] ?? '#1a1a1a'); ?>" class="es-color-text" readonly>
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Input Text (Dark)', 'ensemble'); ?></label>
                                    <input type="color" name="dark_input_text" value="<?php echo esc_attr($settings['dark_input_text'] ?? '#ffffff'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['dark_input_text'] ?? '#ffffff'); ?>" class="es-color-text" readonly>
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Input Border (Dark)', 'ensemble'); ?></label>
                                    <input type="color" name="dark_input_border" value="<?php echo esc_attr($settings['dark_input_border'] ?? '#333333'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['dark_input_border'] ?? '#333333'); ?>" class="es-color-text" readonly>
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Focus Border (Dark)', 'ensemble'); ?></label>
                                    <input type="color" name="dark_input_focus_border" value="<?php echo esc_attr($settings['dark_input_focus_border'] ?? '#818cf8'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['dark_input_focus_border'] ?? '#818cf8'); ?>" class="es-color-text" readonly>
                                </div>
                            </div>
                            
                            <!-- Badges & Tags -->
                            <div class="es-designer-section-title" style="margin-top: 30px;">
                                <span class="dashicons dashicons-tag"></span>
                                <?php _e('Badges & Tags', 'ensemble'); ?>
                            </div>
                            <div class="es-designer-grid">
                                <div class="es-designer-field">
                                    <label><?php _e('Default Badge BG', 'ensemble'); ?></label>
                                    <input type="color" name="badge_bg" value="<?php echo esc_attr($settings['badge_bg'] ?? '#e2e8f0'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['badge_bg'] ?? '#e2e8f0'); ?>" class="es-color-text" readonly>
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Default Badge Text', 'ensemble'); ?></label>
                                    <input type="color" name="badge_text" value="<?php echo esc_attr($settings['badge_text'] ?? '#1a202c'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['badge_text'] ?? '#1a202c'); ?>" class="es-color-text" readonly>
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Badge Radius (px)', 'ensemble'); ?></label>
                                    <input type="number" name="badge_radius" value="<?php echo esc_attr($settings['badge_radius'] ?? 4); ?>" min="0" max="20" class="es-number-input">
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Badge Font Size (px)', 'ensemble'); ?></label>
                                    <input type="number" name="badge_font_size" value="<?php echo esc_attr($settings['badge_font_size'] ?? 12); ?>" min="10" max="16" class="es-number-input">
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Primary Badge BG', 'ensemble'); ?></label>
                                    <input type="color" name="badge_primary_bg" value="<?php echo esc_attr($settings['badge_primary_bg'] ?? '#667eea'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['badge_primary_bg'] ?? '#667eea'); ?>" class="es-color-text" readonly>
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Success Badge BG', 'ensemble'); ?></label>
                                    <input type="color" name="badge_success_bg" value="<?php echo esc_attr($settings['badge_success_bg'] ?? '#10b981'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['badge_success_bg'] ?? '#10b981'); ?>" class="es-color-text" readonly>
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Warning Badge BG', 'ensemble'); ?></label>
                                    <input type="color" name="badge_warning_bg" value="<?php echo esc_attr($settings['badge_warning_bg'] ?? '#f59e0b'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['badge_warning_bg'] ?? '#f59e0b'); ?>" class="es-color-text" readonly>
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Error Badge BG', 'ensemble'); ?></label>
                                    <input type="color" name="badge_error_bg" value="<?php echo esc_attr($settings['badge_error_bg'] ?? '#dc2626'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['badge_error_bg'] ?? '#dc2626'); ?>" class="es-color-text" readonly>
                                </div>
                            </div>
                            
                            <!-- Tooltips -->
                            <div class="es-designer-section-title" style="margin-top: 30px;">
                                <span class="dashicons dashicons-info-outline"></span>
                                <?php _e('Tooltips', 'ensemble'); ?>
                            </div>
                            <div class="es-designer-grid">
                                <div class="es-designer-field">
                                    <label><?php _e('Tooltip Background', 'ensemble'); ?></label>
                                    <input type="color" name="tooltip_bg" value="<?php echo esc_attr($settings['tooltip_bg'] ?? '#1a202c'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['tooltip_bg'] ?? '#1a202c'); ?>" class="es-color-text" readonly>
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Tooltip Text', 'ensemble'); ?></label>
                                    <input type="color" name="tooltip_text" value="<?php echo esc_attr($settings['tooltip_text'] ?? '#ffffff'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['tooltip_text'] ?? '#ffffff'); ?>" class="es-color-text" readonly>
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Tooltip Radius (px)', 'ensemble'); ?></label>
                                    <input type="number" name="tooltip_radius" value="<?php echo esc_attr($settings['tooltip_radius'] ?? 6); ?>" min="0" max="16" class="es-number-input">
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Tooltip Font Size (px)', 'ensemble'); ?></label>
                                    <input type="number" name="tooltip_font_size" value="<?php echo esc_attr($settings['tooltip_font_size'] ?? 13); ?>" min="11" max="16" class="es-number-input">
                                </div>
                            </div>
                            
                            <!-- Scrollbar -->
                            <div class="es-designer-section-title" style="margin-top: 30px;">
                                <span class="dashicons dashicons-menu"></span>
                                <?php _e('Scrollbar', 'ensemble'); ?>
                            </div>
                            <div class="es-designer-grid">
                                <div class="es-designer-field">
                                    <label><?php _e('Scrollbar Width (px)', 'ensemble'); ?></label>
                                    <input type="number" name="scrollbar_width" value="<?php echo esc_attr($settings['scrollbar_width'] ?? 8); ?>" min="4" max="16" class="es-number-input">
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Track Color', 'ensemble'); ?></label>
                                    <input type="color" name="scrollbar_track" value="<?php echo esc_attr($settings['scrollbar_track'] ?? '#f1f5f9'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['scrollbar_track'] ?? '#f1f5f9'); ?>" class="es-color-text" readonly>
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Thumb Color', 'ensemble'); ?></label>
                                    <input type="color" name="scrollbar_thumb" value="<?php echo esc_attr($settings['scrollbar_thumb'] ?? '#cbd5e1'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['scrollbar_thumb'] ?? '#cbd5e1'); ?>" class="es-color-text" readonly>
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Thumb Hover', 'ensemble'); ?></label>
                                    <input type="color" name="scrollbar_thumb_hover" value="<?php echo esc_attr($settings['scrollbar_thumb_hover'] ?? '#94a3b8'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['scrollbar_thumb_hover'] ?? '#94a3b8'); ?>" class="es-color-text" readonly>
                                </div>
                            </div>
                            
                            <!-- Dark Mode Scrollbar -->
                            <div class="es-designer-section-divider">
                                <span class="dashicons dashicons-arrow-down-alt2"></span>
                                <?php _e('Dark Mode Scrollbar', 'ensemble'); ?>
                            </div>
                            <div class="es-designer-grid">
                                <div class="es-designer-field">
                                    <label><?php _e('Track (Dark)', 'ensemble'); ?></label>
                                    <input type="color" name="dark_scrollbar_track" value="<?php echo esc_attr($settings['dark_scrollbar_track'] ?? '#1a1a1a'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['dark_scrollbar_track'] ?? '#1a1a1a'); ?>" class="es-color-text" readonly>
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Thumb (Dark)', 'ensemble'); ?></label>
                                    <input type="color" name="dark_scrollbar_thumb" value="<?php echo esc_attr($settings['dark_scrollbar_thumb'] ?? '#404040'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['dark_scrollbar_thumb'] ?? '#404040'); ?>" class="es-color-text" readonly>
                                </div>
                            </div>
                            
                            <!-- Loading States -->
                            <div class="es-designer-section-title" style="margin-top: 30px;">
                                <span class="dashicons dashicons-update"></span>
                                <?php _e('Loading States', 'ensemble'); ?>
                            </div>
                            <div class="es-designer-grid">
                                <div class="es-designer-field">
                                    <label><?php _e('Spinner Color', 'ensemble'); ?></label>
                                    <input type="color" name="loading_spinner_color" value="<?php echo esc_attr($settings['loading_spinner_color'] ?? '#667eea'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['loading_spinner_color'] ?? '#667eea'); ?>" class="es-color-text" readonly>
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Spinner Size (px)', 'ensemble'); ?></label>
                                    <input type="number" name="loading_spinner_size" value="<?php echo esc_attr($settings['loading_spinner_size'] ?? 40); ?>" min="20" max="80" class="es-number-input">
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Overlay BG', 'ensemble'); ?></label>
                                    <input type="text" name="loading_overlay_bg" value="<?php echo esc_attr($settings['loading_overlay_bg'] ?? 'rgba(255, 255, 255, 0.8)'); ?>" class="es-text-input" placeholder="rgba(255, 255, 255, 0.8)">
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Spinner (Dark)', 'ensemble'); ?></label>
                                    <input type="color" name="dark_loading_spinner_color" value="<?php echo esc_attr($settings['dark_loading_spinner_color'] ?? '#818cf8'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['dark_loading_spinner_color'] ?? '#818cf8'); ?>" class="es-color-text" readonly>
                                </div>
                            </div>
                            
                            <!-- Skeleton Loading -->
                            <div class="es-designer-section-divider">
                                <span class="dashicons dashicons-arrow-down-alt2"></span>
                                <?php _e('Skeleton Loading', 'ensemble'); ?>
                            </div>
                            <div class="es-designer-grid">
                                <div class="es-designer-field">
                                    <label><?php _e('Skeleton BG', 'ensemble'); ?></label>
                                    <input type="color" name="skeleton_bg" value="<?php echo esc_attr($settings['skeleton_bg'] ?? '#e2e8f0'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['skeleton_bg'] ?? '#e2e8f0'); ?>" class="es-color-text" readonly>
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Skeleton Highlight', 'ensemble'); ?></label>
                                    <input type="color" name="skeleton_highlight" value="<?php echo esc_attr($settings['skeleton_highlight'] ?? '#f8fafc'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['skeleton_highlight'] ?? '#f8fafc'); ?>" class="es-color-text" readonly>
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Skeleton BG (Dark)', 'ensemble'); ?></label>
                                    <input type="color" name="dark_skeleton_bg" value="<?php echo esc_attr($settings['dark_skeleton_bg'] ?? '#333333'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['dark_skeleton_bg'] ?? '#333333'); ?>" class="es-color-text" readonly>
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Skeleton Highlight (Dark)', 'ensemble'); ?></label>
                                    <input type="color" name="dark_skeleton_highlight" value="<?php echo esc_attr($settings['dark_skeleton_highlight'] ?? '#404040'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['dark_skeleton_highlight'] ?? '#404040'); ?>" class="es-color-text" readonly>
                                </div>
                            </div>
                            
                            <!-- Dropdowns -->
                            <div class="es-designer-section-title" style="margin-top: 30px;">
                                <span class="dashicons dashicons-arrow-down-alt2"></span>
                                <?php _e('Dropdowns & Selects', 'ensemble'); ?>
                            </div>
                            <div class="es-designer-grid">
                                <div class="es-designer-field">
                                    <label><?php _e('Dropdown BG', 'ensemble'); ?></label>
                                    <input type="color" name="dropdown_bg" value="<?php echo esc_attr($settings['dropdown_bg'] ?? '#ffffff'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['dropdown_bg'] ?? '#ffffff'); ?>" class="es-color-text" readonly>
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Dropdown Border', 'ensemble'); ?></label>
                                    <input type="color" name="dropdown_border" value="<?php echo esc_attr($settings['dropdown_border'] ?? '#e2e8f0'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['dropdown_border'] ?? '#e2e8f0'); ?>" class="es-color-text" readonly>
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Dropdown Radius (px)', 'ensemble'); ?></label>
                                    <input type="number" name="dropdown_radius" value="<?php echo esc_attr($settings['dropdown_radius'] ?? 8); ?>" min="0" max="20" class="es-number-input">
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Item Hover', 'ensemble'); ?></label>
                                    <input type="color" name="dropdown_item_hover" value="<?php echo esc_attr($settings['dropdown_item_hover'] ?? '#f7fafc'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['dropdown_item_hover'] ?? '#f7fafc'); ?>" class="es-color-text" readonly>
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Active Item BG', 'ensemble'); ?></label>
                                    <input type="color" name="dropdown_item_active" value="<?php echo esc_attr($settings['dropdown_item_active'] ?? '#667eea'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['dropdown_item_active'] ?? '#667eea'); ?>" class="es-color-text" readonly>
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Active Item Text', 'ensemble'); ?></label>
                                    <input type="color" name="dropdown_item_active_text" value="<?php echo esc_attr($settings['dropdown_item_active_text'] ?? '#ffffff'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['dropdown_item_active_text'] ?? '#ffffff'); ?>" class="es-color-text" readonly>
                                </div>
                            </div>
                            
                            <!-- Modals -->
                            <div class="es-designer-section-title" style="margin-top: 30px;">
                                <span class="dashicons dashicons-editor-expand"></span>
                                <?php _e('Modals & Dialogs', 'ensemble'); ?>
                            </div>
                            <div class="es-designer-grid">
                                <div class="es-designer-field">
                                    <label><?php _e('Modal Background', 'ensemble'); ?></label>
                                    <input type="color" name="modal_bg" value="<?php echo esc_attr($settings['modal_bg'] ?? '#ffffff'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['modal_bg'] ?? '#ffffff'); ?>" class="es-color-text" readonly>
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Modal Border', 'ensemble'); ?></label>
                                    <input type="color" name="modal_border" value="<?php echo esc_attr($settings['modal_border'] ?? '#e2e8f0'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['modal_border'] ?? '#e2e8f0'); ?>" class="es-color-text" readonly>
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Modal Radius (px)', 'ensemble'); ?></label>
                                    <input type="number" name="modal_radius" value="<?php echo esc_attr($settings['modal_radius'] ?? 12); ?>" min="0" max="32" class="es-number-input">
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Backdrop', 'ensemble'); ?></label>
                                    <input type="text" name="modal_backdrop" value="<?php echo esc_attr($settings['modal_backdrop'] ?? 'rgba(0, 0, 0, 0.5)'); ?>" class="es-text-input" placeholder="rgba(0, 0, 0, 0.5)">
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Header Border', 'ensemble'); ?></label>
                                    <input type="color" name="modal_header_border" value="<?php echo esc_attr($settings['modal_header_border'] ?? '#e2e8f0'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['modal_header_border'] ?? '#e2e8f0'); ?>" class="es-color-text" readonly>
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Footer BG', 'ensemble'); ?></label>
                                    <input type="color" name="modal_footer_bg" value="<?php echo esc_attr($settings['modal_footer_bg'] ?? '#f7fafc'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['modal_footer_bg'] ?? '#f7fafc'); ?>" class="es-color-text" readonly>
                                </div>
                            </div>
                            
                            <!-- Tables -->
                            <div class="es-designer-section-title" style="margin-top: 30px;">
                                <span class="dashicons dashicons-editor-table"></span>
                                <?php _e('Tables', 'ensemble'); ?>
                            </div>
                            <div class="es-designer-grid">
                                <div class="es-designer-field">
                                    <label><?php _e('Header BG', 'ensemble'); ?></label>
                                    <input type="color" name="table_header_bg" value="<?php echo esc_attr($settings['table_header_bg'] ?? '#f7fafc'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['table_header_bg'] ?? '#f7fafc'); ?>" class="es-color-text" readonly>
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Header Text', 'ensemble'); ?></label>
                                    <input type="color" name="table_header_text" value="<?php echo esc_attr($settings['table_header_text'] ?? '#1a202c'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['table_header_text'] ?? '#1a202c'); ?>" class="es-color-text" readonly>
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Row BG', 'ensemble'); ?></label>
                                    <input type="color" name="table_row_bg" value="<?php echo esc_attr($settings['table_row_bg'] ?? '#ffffff'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['table_row_bg'] ?? '#ffffff'); ?>" class="es-color-text" readonly>
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Alt Row BG', 'ensemble'); ?></label>
                                    <input type="color" name="table_row_alt_bg" value="<?php echo esc_attr($settings['table_row_alt_bg'] ?? '#f7fafc'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['table_row_alt_bg'] ?? '#f7fafc'); ?>" class="es-color-text" readonly>
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Row Hover', 'ensemble'); ?></label>
                                    <input type="color" name="table_row_hover" value="<?php echo esc_attr($settings['table_row_hover'] ?? '#edf2f7'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['table_row_hover'] ?? '#edf2f7'); ?>" class="es-color-text" readonly>
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Border', 'ensemble'); ?></label>
                                    <input type="color" name="table_border" value="<?php echo esc_attr($settings['table_border'] ?? '#e2e8f0'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['table_border'] ?? '#e2e8f0'); ?>" class="es-color-text" readonly>
                                </div>
                            </div>
                            
                            <!-- Pagination -->
                            <div class="es-designer-section-title" style="margin-top: 30px;">
                                <span class="dashicons dashicons-arrow-right-alt"></span>
                                <?php _e('Pagination', 'ensemble'); ?>
                            </div>
                            <div class="es-designer-grid">
                                <div class="es-designer-field">
                                    <label><?php _e('Pagination BG', 'ensemble'); ?></label>
                                    <input type="color" name="pagination_bg" value="<?php echo esc_attr($settings['pagination_bg'] ?? '#ffffff'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['pagination_bg'] ?? '#ffffff'); ?>" class="es-color-text" readonly>
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Pagination Text', 'ensemble'); ?></label>
                                    <input type="color" name="pagination_text" value="<?php echo esc_attr($settings['pagination_text'] ?? '#1a202c'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['pagination_text'] ?? '#1a202c'); ?>" class="es-color-text" readonly>
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Hover BG', 'ensemble'); ?></label>
                                    <input type="color" name="pagination_hover_bg" value="<?php echo esc_attr($settings['pagination_hover_bg'] ?? '#f7fafc'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['pagination_hover_bg'] ?? '#f7fafc'); ?>" class="es-color-text" readonly>
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Active BG', 'ensemble'); ?></label>
                                    <input type="color" name="pagination_active_bg" value="<?php echo esc_attr($settings['pagination_active_bg'] ?? '#667eea'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['pagination_active_bg'] ?? '#667eea'); ?>" class="es-color-text" readonly>
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Active Text', 'ensemble'); ?></label>
                                    <input type="color" name="pagination_active_text" value="<?php echo esc_attr($settings['pagination_active_text'] ?? '#ffffff'); ?>" class="es-color-input">
                                    <input type="text" value="<?php echo esc_attr($settings['pagination_active_text'] ?? '#ffffff'); ?>" class="es-color-text" readonly>
                                </div>
                                <div class="es-designer-field">
                                    <label><?php _e('Radius (px)', 'ensemble'); ?></label>
                                    <input type="number" name="pagination_radius" value="<?php echo esc_attr($settings['pagination_radius'] ?? 6); ?>" min="0" max="20" class="es-number-input">
                                </div>
                            </div>
                            
                        </div>
                        
                        <div class="es-designer-actions">
                            <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                                <button type="submit" name="es_save_custom_design" class="button button-primary button-large">
                                    <span class="dashicons dashicons-saved"></span>
                                    <?php _e('Save Custom Design', 'ensemble'); ?>
                                </button>
                                <button type="submit" name="es_reset_to_layout" class="button button-secondary" onclick="return confirm('<?php esc_attr_e('Alle Designer-Einstellungen auf die Standardwerte des aktuellen Layouts zurÃ¼cksetzen?', 'ensemble'); ?>');">
                                    <span class="dashicons dashicons-image-rotate"></span>
                                    <?php _e('Auf Layout-Standard zurÃ¼cksetzen', 'ensemble'); ?>
                                </button>
                            </div>
                            <p class="description" style="margin-top: 12px;">
                                <?php _e('Dein individuelles Design wird gespeichert und auf alle Shortcodes angewendet.', 'ensemble'); ?>
                            </p>
                        </div>
                        
                    </form>
                    
                </div>
            </div>
            
            <!-- Current Settings Overview -->
            <div class="es-card">
                <div class="es-card-header">
                    <h3><?php _e('Current Design Settings', 'ensemble'); ?></h3>
                    <p class="es-card-description"><?php _e('Overview of your active design configuration', 'ensemble'); ?></p>
                </div>
                <div class="es-card-body">
                    
                    <div class="es-settings-grid">
                        
                        <!-- Colors -->
                        <div class="es-settings-group">
                            <h4><span class="dashicons dashicons-art"></span> <?php _e('Colors', 'ensemble'); ?></h4>
                            <div class="es-color-list">
                                <div class="es-color-item">
                                    <span class="es-color-dot" style="background-color: <?php echo esc_attr($settings['primary_color']); ?>"></span>
                                    <span class="es-color-label"><?php _e('Primary', 'ensemble'); ?></span>
                                    <code><?php echo esc_html($settings['primary_color']); ?></code>
                                </div>
                                <div class="es-color-item">
                                    <span class="es-color-dot" style="background-color: <?php echo esc_attr($settings['secondary_color']); ?>"></span>
                                    <span class="es-color-label"><?php _e('Secondary', 'ensemble'); ?></span>
                                    <code><?php echo esc_html($settings['secondary_color']); ?></code>
                                </div>
                                <div class="es-color-item">
                                    <span class="es-color-dot" style="background-color: <?php echo esc_attr($settings['card_background']); ?>; border: 1px solid #ddd;"></span>
                                    <span class="es-color-label"><?php _e('Card Background', 'ensemble'); ?></span>
                                    <code><?php echo esc_html($settings['card_background']); ?></code>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Typography -->
                        <div class="es-settings-group">
                            <h4><span class="dashicons dashicons-editor-textcolor"></span> <?php _e('Typography', 'ensemble'); ?></h4>
                            <div class="es-setting-list">
                                <div class="es-setting-item">
                                    <span class="es-setting-label"><?php _e('Heading Font', 'ensemble'); ?></span>
                                    <span class="es-setting-value"><?php echo esc_html($settings['heading_font']); ?></span>
                                </div>
                                <div class="es-setting-item">
                                    <span class="es-setting-label"><?php _e('Body Font', 'ensemble'); ?></span>
                                    <span class="es-setting-value"><?php echo esc_html($settings['body_font']); ?></span>
                                </div>
                                <div class="es-setting-item">
                                    <span class="es-setting-label"><?php _e('H1 Size', 'ensemble'); ?></span>
                                    <span class="es-setting-value"><?php echo esc_html($settings['h1_size']); ?>px</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Layout -->
                        <div class="es-settings-group">
                            <h4><span class="dashicons dashicons-layout"></span> <?php _e('Layout', 'ensemble'); ?></h4>
                            <div class="es-setting-list">
                                <div class="es-setting-item">
                                    <span class="es-setting-label"><?php _e('Card Radius', 'ensemble'); ?></span>
                                    <span class="es-setting-value"><?php echo esc_html($settings['card_radius']); ?>px</span>
                                </div>
                                <div class="es-setting-item">
                                    <span class="es-setting-label"><?php _e('Card Hover', 'ensemble'); ?></span>
                                    <span class="es-setting-value"><?php echo esc_html($settings['card_hover']); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Buttons -->
                        <div class="es-settings-group">
                            <h4><span class="dashicons dashicons-button"></span> <?php _e('Buttons', 'ensemble'); ?></h4>
                            <div class="es-setting-list">
                                <div class="es-setting-item">
                                    <span class="es-setting-label"><?php _e('Radius', 'ensemble'); ?></span>
                                    <span class="es-setting-value"><?php echo esc_html($settings['button_radius']); ?>px</span>
                                </div>
                                <div class="es-setting-item">
                                    <span class="es-color-dot" style="background-color: <?php echo esc_attr($settings['button_bg']); ?>"></span>
                                    <code><?php echo esc_html($settings['button_bg']); ?></code>
                                </div>
                            </div>
                        </div>
                        
                    </div>
                </div>
            </div>
            
            <!-- Usage Instructions -->
            <div class="es-card">
                <div class="es-card-header">
                    <h3>
                        <span class="dashicons dashicons-info"></span>
                        <?php _e('Wie funktioniert das?', 'ensemble'); ?>
                    </h3>
                </div>
                <div class="es-card-body">
                    <div class="es-info-simple">
                        <p><strong><?php _e('1. Choose template:', 'ensemble'); ?></strong> <?php _e('Select a pre-made design template above.', 'ensemble'); ?></p>
                        <p><strong><?php _e('2. Automatic Application:', 'ensemble'); ?></strong> <?php _e('The template is automatically applied to all your shortcodes. You don\'t need to change anything in the shortcode!', 'ensemble'); ?></p>
                        <p><strong><?php _e('3. Customization (coming soon):', 'ensemble'); ?></strong> <?php _e('Soon you will be able to customize each individual value like colors, fonts, spacing etc.', 'ensemble'); ?></p>
                    </div>
                </div>
            </div>
            
        </div>
        </div><!-- End #es-custom-design-settings -->
<script>
jQuery(document).ready(function($) {
    // Designer tab switching
    $('.es-designer-tab').on('click', function() {
        const tab = $(this).data('tab');
        
        $('.es-designer-tab').removeClass('active');
        $(this).addClass('active');
        
        $('.es-designer-content').removeClass('active');
        $('.es-designer-content[data-content="' + tab + '"]').addClass('active');
    });
    
    // Light/Dark Mode Toggle
    $('.es-mode-btn').on('click', function() {
        const mode = $(this).data('mode');
        
        // Update buttons
        $('.es-mode-btn').removeClass('active');
        $(this).addClass('active');
        
        // Show/hide color panels
        $('.es-mode-colors').hide();
        $('.es-mode-colors[data-mode-content="' + mode + '"]').fadeIn(200);
    });
    
    // Sync color picker with text input
    $('.es-color-input').on('input', function() {
        const color = $(this).val();
        $(this).siblings('.es-color-text').val(color);
    });
    
    // Form change warning
    let formChanged = false;
    let isSubmitting = false;
    
    $('.es-designer-form input, .es-designer-form select').on('change', function() {
        formChanged = true;
    });
    
    $(window).on('beforeunload', function() {
        if (formChanged && !isSubmitting) {
            return 'You have unsaved changes. Are you sure you want to leave?';
        }
    });
    
    $('.es-designer-form').on('submit', function() {
        isSubmitting = true;
        formChanged = false;
    });
    
    // =====================================================
    // FONT PICKER - Custom Dropdown with Preview
    // =====================================================
    
    // Track loaded fonts to avoid duplicate loading
    var loadedFonts = {};
    
    // Load Google Font for preview
    function loadGoogleFont(fontName) {
        if (loadedFonts[fontName] || fontName === 'System Default') {
            return;
        }
        
        loadedFonts[fontName] = true;
        
        var link = document.createElement('link');
        link.href = 'https://fonts.googleapis.com/css2?family=' + fontName.replace(/ /g, '+') + ':wght@400;500;600;700&display=swap';
        link.rel = 'stylesheet';
        document.head.appendChild(link);
    }
    
    // Load initial fonts
    $('.es-font-picker').each(function() {
        var currentFont = $(this).find('input[type="hidden"]').val();
        if (currentFont) {
            loadGoogleFont(currentFont);
        }
    });
    
    // Open/Close dropdown
    $('.es-font-dropdown-trigger').on('click', function(e) {
        e.stopPropagation();
        var $picker = $(this).closest('.es-font-picker');
        var wasOpen = $picker.hasClass('open');
        
        // Close all other pickers
        $('.es-font-picker').removeClass('open');
        
        if (!wasOpen) {
            $picker.addClass('open');
            $picker.find('.es-font-search').focus();
            
            // Load fonts for visible options (lazy loading)
            $picker.find('.es-font-option:visible').each(function() {
                loadGoogleFont($(this).data('font'));
            });
        }
    });
    
    // Close on outside click
    $(document).on('click', function() {
        $('.es-font-picker').removeClass('open');
    });
    
    // Prevent dropdown close when clicking inside
    $('.es-font-dropdown').on('click', function(e) {
        e.stopPropagation();
    });
    
    // Search fonts
    $('.es-font-search').on('input', function() {
        var query = $(this).val().toLowerCase();
        var $picker = $(this).closest('.es-font-picker');
        
        $picker.find('.es-font-option').each(function() {
            var fontName = $(this).data('font').toLowerCase();
            if (fontName.indexOf(query) !== -1) {
                $(this).removeClass('hidden');
                loadGoogleFont($(this).data('font'));
            } else {
                $(this).addClass('hidden');
            }
        });
        
        // Hide empty groups
        $picker.find('.es-font-group').each(function() {
            var visibleOptions = $(this).find('.es-font-option:not(.hidden)').length;
            $(this).toggleClass('hidden', visibleOptions === 0);
        });
    });
    
    // Select font
    $('.es-font-option').on('click', function() {
        var $picker = $(this).closest('.es-font-picker');
        var fontName = $(this).data('font');
        
        // Update selection
        $picker.find('.es-font-option').removeClass('selected');
        $(this).addClass('selected');
        
        // Update trigger text with font style
        $picker.find('.es-selected-font-name')
            .text(fontName)
            .css('font-family', "'" + fontName + "', sans-serif");
        
        // Update hidden input
        $picker.find('input[type="hidden"]').val(fontName).trigger('change');
        
        // Close dropdown
        $picker.removeClass('open');
        
        // Mark form as changed
        formChanged = true;
    });
    
    // Keyboard navigation
    $('.es-font-dropdown-trigger').on('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            $(this).click();
        }
    });
    
    // Load fonts on hover (for better UX)
    $('.es-font-option').on('mouseenter', function() {
        loadGoogleFont($(this).data('font'));
    });
});
</script>

<style>
/* Layout Selector Styles */
.es-layout-selector-card {
    margin-bottom: 30px;
}

.es-layout-section {
    margin-bottom: 40px;
}

.es-section-header {
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid var(--es-border, #3c3c3c);
}

.es-section-header h4 {
    margin: 0 0 8px 0;
    font-size: 18px;
    font-weight: 600;
    color: var(--es-text, #e0e0e0);
    display: flex;
    align-items: center;
    gap: 8px;
}

.es-badge-free {
    padding: 4px 8px;
    background: var(--es-success, #4caf50);
    color: #fff;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
}

.es-layout-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
}

.es-layout-card {
    position: relative;
    display: flex;
    flex-direction: column;
    background: var(--es-surface-secondary, #383838);
    border: 2px solid var(--es-border, #3c3c3c);
    border-radius: 12px;
    padding: 20px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.es-layout-card input[type="radio"] {
    position: absolute;
    opacity: 0;
}

.es-layout-card:hover {
    border-color: var(--es-primary, #3582c4);
    transform: translateY(-2px);
}

.es-layout-card.active {
    border-color: var(--es-primary, #3582c4);
    box-shadow: 0 0 0 3px rgba(53, 130, 196, 0.2);
}

.es-layout-preview {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 120px;
    background: var(--es-surface, #2c2c2c);
    border-radius: 8px;
    margin-bottom: 16px;
    position: relative;
}

.es-layout-preview .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    color: var(--es-primary, #3582c4);
}

.es-active-badge {
    position: absolute;
    top: 8px;
    right: 8px;
    padding: 4px 8px;
    background: var(--es-success, #4caf50);
    color: #fff;
    border-radius: 4px;
    font-size: 10px;
    font-weight: 700;
}

.es-layout-info h4 {
    margin: 0 0 8px 0;
    font-size: 16px;
    font-weight: 600;
    color: var(--es-text, #e0e0e0);
}

.es-layout-description {
    margin: 0 0 12px 0;
    font-size: 13px;
    line-height: 1.5;
    color: var(--es-text-secondary, #a0a0a0);
}

.es-layout-meta {
    font-size: 12px;
    color: var(--es-text-secondary, #a0a0a0);
}

.es-meta-item {
    display: flex;
    align-items: center;
    gap: 4px;
}

/* Upgrade Section */
.es-upgrade-box {
    display: flex;
    gap: 30px;
    padding: 40px;
    background: linear-gradient(135deg, rgba(240, 147, 251, 0.1) 0%, rgba(245, 87, 108, 0.1) 100%);
    border: 2px solid rgba(240, 147, 251, 0.3);
    border-radius: 12px;
}

.es-upgrade-icon .dashicons {
    font-size: 80px;
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.es-upgrade-content h3 {
    margin: 0 0 12px 0;
    font-size: 24px;
    font-weight: 700;
}

.es-upgrade-features {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    margin: 20px 0;
}

.es-feature {
    display: flex;
    align-items: center;
    gap: 8px;
}

.es-feature .dashicons {
    color: var(--es-success, #4caf50);
}

.es-upgrade-button {
    padding: 14px 28px !important;
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%) !important;
    border: none !important;
}

.es-form-actions {
    margin-top: 30px;
    padding-top: 30px;
    border-top: 2px solid var(--es-border, #3c3c3c);
    text-align: center;
}

.es-help-text {
    margin: 16px 0 0 0;
    font-size: 13px;
    color: var(--es-text-secondary, #a0a0a0);
}
</style>
