<?php
/**
 * Ensemble Add-ons Admin Page
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap es-addons-wrap">
    <h1><?php _e('Ensemble Add-ons', 'ensemble'); ?></h1>
    <div class="es-addons-container">
        <div class="es-addons-section">
    <?php if (!$is_pro): ?>
    <div class="notice notice-info">
        <p>
            <strong><?php _e('Pro-Version erforderlich', 'ensemble'); ?></strong><br>
            <?php _e('Most add-ons require Ensemble Pro. Upgrade for access to all features.', 'ensemble'); ?>
        </p>
    </div>
    <?php endif; ?>
    
    <div class="es-addons-grid">
        <?php if (empty($addons)): ?>
            <div class="es-addon-empty">
                <p><?php _e('No add-ons available.', 'ensemble'); ?></p>
            </div>
        <?php else: ?>
            <?php foreach ($addons as $addon): ?>
                <?php
                $is_active = $addon['active'];
                $requires_pro = $addon['requires_pro'];
                $can_activate = !$requires_pro || $is_pro;
                $has_settings = $addon['settings_page'] && $is_active;
                ?>
                
                <div class="es-addon-card <?php echo $is_active ? 'es-addon-active' : ''; ?> <?php echo !$can_activate ? 'es-addon-locked' : ''; ?>" 
                     data-addon-slug="<?php echo esc_attr($addon['slug']); ?>">
                    
                    <div class="es-addon-header">
                        <div class="es-addon-icon">
                            <span class="dashicons <?php echo esc_attr($addon['icon']); ?>"></span>
                        </div>
                        <div class="es-addon-meta">
                            <h3><?php echo esc_html($addon['name']); ?>
                                <?php if ($requires_pro): ?>
                                    <span class="es-addon-badge es-addon-badge-pro">PRO</span>
                                <?php endif; ?>
                            </h3>
                            <p class="es-addon-version">Version <?php echo esc_html($addon['version']); ?></p>
                        </div>
                    </div>
                    
                    <div class="es-addon-body">
                        <p class="es-addon-description"><?php echo esc_html($addon['description']); ?></p>
                        
                        <div class="es-addon-footer">
                            <?php if ($can_activate): ?>
                                <div class="es-addon-toggle-wrapper">
                                    <label class="es-toggle">
                                        <input type="checkbox" 
                                               class="es-addon-toggle-input" 
                                               <?php checked($is_active); ?>
                                               data-addon-slug="<?php echo esc_attr($addon['slug']); ?>">
                                        <span class="es-toggle-track"></span>
                                        <span class="es-toggle-label es-addon-toggle-label"><?php echo $is_active ? __('Aktiv', 'ensemble') : __('Inaktiv', 'ensemble'); ?></span>
                                    </label>
                                </div>
                                
                                <?php if ($has_settings): ?>
                                    <button type="button" 
                                            class="button button-secondary es-addon-settings-btn"
                                            data-addon-slug="<?php echo esc_attr($addon['slug']); ?>">
                                        <span class="dashicons dashicons-admin-generic"></span>
                                        <?php _e('Einstellungen', 'ensemble'); ?>
                                    </button>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="es-addon-locked-notice">
                                    <span class="dashicons dashicons-lock"></span>
                                    <?php _e('Pro-Version erforderlich', 'ensemble'); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                </div>
                
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    </div>
    </div>
</div>

<!-- Settings Modal -->
<div id="es-addon-settings-modal" class="es-modal" style="display:none;">
    <div class="es-modal-overlay"></div>
    <div class="es-modal-content">
        <div class="es-modal-header">
            <h2 id="es-addon-settings-title"><?php _e('Add-on Einstellungen', 'ensemble'); ?></h2>
            <button type="button" class="es-modal-close">
                <span class="dashicons dashicons-no"></span>
            </button>
        </div>
        <div class="es-modal-body" id="es-addon-settings-body">
            <!-- Settings loaded dynamically -->
        </div>
        <div class="es-modal-footer">
            <button type="button" class="button button-secondary es-modal-cancel">
                <?php _e('Cancel', 'ensemble'); ?>
            </button>
            <button type="button" class="button button-primary es-addon-save-settings">
                <?php _e('Einstellungen speichern', 'ensemble'); ?>
            </button>
        </div>
    </div>
</div>
