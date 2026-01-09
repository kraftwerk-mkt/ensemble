<?php
/**
 * Ensemble Media Folders Pro - Settings Template
 * 
 * @package Ensemble
 * @subpackage Addons/MediaFolders
 * @since 2.7.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$settings = $this->get_all_settings();
?>

<div class="es-media-folders-settings">
    
    <!-- Automation Section -->
    <div class="es-settings-section">
        <h4 style="margin: 0 0 16px 0; font-size: 14px; font-weight: 600; color: var(--ensemble-text, #e0e0e0); display: flex; align-items: center; gap: 8px;">
            <span class="dashicons dashicons-admin-generic"></span>
            <?php esc_html_e('Automation', 'ensemble'); ?>
        </h4>
        
        <div class="es-setting-row">
            <div class="es-setting-info">
                <span class="es-setting-title"><?php esc_html_e('Auto-create folders for Events', 'ensemble'); ?></span>
                <span class="es-setting-desc"><?php esc_html_e('Creates a folder automatically when a new Event is saved', 'ensemble'); ?></span>
            </div>
            <label class="es-toggle-wrapper">
                <input type="checkbox" name="auto_events" value="1" <?php checked($settings['auto_events']); ?>>
                <span class="es-toggle-switch"></span>
            </label>
        </div>
        
        <div class="es-setting-row">
            <div class="es-setting-info">
                <span class="es-setting-title"><?php esc_html_e('Auto-create folders for Artists', 'ensemble'); ?></span>
                <span class="es-setting-desc"><?php esc_html_e('Creates a folder automatically when a new Artist is saved', 'ensemble'); ?></span>
            </div>
            <label class="es-toggle-wrapper">
                <input type="checkbox" name="auto_artists" value="1" <?php checked($settings['auto_artists']); ?>>
                <span class="es-toggle-switch"></span>
            </label>
        </div>
        
        <div class="es-setting-row">
            <div class="es-setting-info">
                <span class="es-setting-title"><?php esc_html_e('Auto-create folders for Locations', 'ensemble'); ?></span>
                <span class="es-setting-desc"><?php esc_html_e('Creates a folder automatically when a new Location is saved', 'ensemble'); ?></span>
            </div>
            <label class="es-toggle-wrapper">
                <input type="checkbox" name="auto_locations" value="1" <?php checked($settings['auto_locations']); ?>>
                <span class="es-toggle-switch"></span>
            </label>
        </div>
        
        <div class="es-setting-row">
            <div class="es-setting-info">
                <span class="es-setting-title"><?php esc_html_e('Auto-assign media on upload', 'ensemble'); ?></span>
                <span class="es-setting-desc"><?php esc_html_e('Automatically assigns uploaded media to the correct folder when uploaded from Wizard', 'ensemble'); ?></span>
            </div>
            <label class="es-toggle-wrapper">
                <input type="checkbox" name="auto_assign_upload" value="1" <?php checked($settings['auto_assign_upload']); ?>>
                <span class="es-toggle-switch"></span>
            </label>
        </div>
        
        <div class="es-setting-row">
            <div class="es-setting-info">
                <span class="es-setting-title"><?php esc_html_e('Delete folder when post is deleted', 'ensemble'); ?></span>
                <span class="es-setting-desc">
                    <?php esc_html_e('Media files will be moved to "Uncategorized"', 'ensemble'); ?>
                    <br><strong style="color: #f59e0b;">⚠️ <?php esc_html_e('Use with caution', 'ensemble'); ?></strong>
                </span>
            </div>
            <label class="es-toggle-wrapper">
                <input type="checkbox" name="delete_folder_on_post_delete" value="1" <?php checked($settings['delete_folder_on_post_delete']); ?>>
                <span class="es-toggle-switch"></span>
            </label>
        </div>
    </div>
    
    <!-- Display Section -->
    <div class="es-settings-section" style="margin-top: 24px; padding-top: 24px; border-top: 1px solid var(--ensemble-card-border, #3a3a3a);">
        <h4 style="margin: 0 0 16px 0; font-size: 14px; font-weight: 600; color: var(--ensemble-text, #e0e0e0); display: flex; align-items: center; gap: 8px;">
            <span class="dashicons dashicons-art"></span>
            <?php esc_html_e('Display', 'ensemble'); ?>
        </h4>
        
        <p style="color: var(--ensemble-text-secondary, #9ca3af); font-size: 12px; margin: 0 0 12px 0;">
            <?php esc_html_e('Default colors for folder categories:', 'ensemble'); ?>
        </p>
        
        <div style="display: flex; flex-wrap: wrap; gap: 16px; margin-bottom: 16px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="font-size: 13px; color: var(--ensemble-text, #e0e0e0);"><?php esc_html_e('Events:', 'ensemble'); ?></span>
                <input type="color" name="color_events" value="<?php echo esc_attr($settings['color_events']); ?>" style="width: 32px; height: 32px; padding: 0; border: none; border-radius: 4px; cursor: pointer;">
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="font-size: 13px; color: var(--ensemble-text, #e0e0e0);"><?php esc_html_e('Artists:', 'ensemble'); ?></span>
                <input type="color" name="color_artists" value="<?php echo esc_attr($settings['color_artists']); ?>" style="width: 32px; height: 32px; padding: 0; border: none; border-radius: 4px; cursor: pointer;">
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="font-size: 13px; color: var(--ensemble-text, #e0e0e0);"><?php esc_html_e('Locations:', 'ensemble'); ?></span>
                <input type="color" name="color_locations" value="<?php echo esc_attr($settings['color_locations']); ?>" style="width: 32px; height: 32px; padding: 0; border: none; border-radius: 4px; cursor: pointer;">
            </div>
        </div>
        
        <div class="es-setting-row">
            <div class="es-setting-info">
                <span class="es-setting-title"><?php esc_html_e('Show folder count', 'ensemble'); ?></span>
                <span class="es-setting-desc"><?php esc_html_e('Display number of items in each folder', 'ensemble'); ?></span>
            </div>
            <label class="es-toggle-wrapper">
                <input type="checkbox" name="show_count" value="1" <?php checked($settings['show_count']); ?>>
                <span class="es-toggle-switch"></span>
            </label>
        </div>
        
        <div class="es-setting-row">
            <div class="es-setting-info">
                <span class="es-setting-title"><?php esc_html_e('Hide empty folders', 'ensemble'); ?></span>
                <span class="es-setting-desc"><?php esc_html_e('Only show folders that contain media', 'ensemble'); ?></span>
            </div>
            <label class="es-toggle-wrapper">
                <input type="checkbox" name="hide_empty" value="1" <?php checked($settings['hide_empty']); ?>>
                <span class="es-toggle-switch"></span>
            </label>
        </div>
    </div>
    
    <!-- Smart Folders Section -->
    <div class="es-settings-section" style="margin-top: 24px; padding-top: 24px; border-top: 1px solid var(--ensemble-card-border, #3a3a3a);">
        <h4 style="margin: 0 0 16px 0; font-size: 14px; font-weight: 600; color: var(--ensemble-text, #e0e0e0); display: flex; align-items: center; gap: 8px;">
            <span class="dashicons dashicons-filter"></span>
            <?php esc_html_e('Smart Folders', 'ensemble'); ?>
        </h4>
        
        <div class="es-setting-row">
            <div class="es-setting-info">
                <span class="es-setting-title"><?php esc_html_e('Enable Smart Folders', 'ensemble'); ?></span>
                <span class="es-setting-desc"><?php esc_html_e('Show smart filter folders in the sidebar', 'ensemble'); ?></span>
            </div>
            <label class="es-toggle-wrapper">
                <input type="checkbox" name="smart_folders_enabled" value="1" <?php checked($settings['smart_folders_enabled']); ?>>
                <span class="es-toggle-switch"></span>
            </label>
        </div>
        
        <div style="margin-left: 16px; padding-left: 16px; border-left: 2px solid var(--ensemble-card-border, #3a3a3a);">
            <div class="es-setting-row">
                <div class="es-setting-info">
                    <span class="es-setting-title">
                        <span class="dashicons dashicons-format-image" style="color: #e91e63; font-size: 16px; margin-right: 4px;"></span>
                        <?php esc_html_e('All Images', 'ensemble'); ?>
                    </span>
                </div>
                <label class="es-toggle-wrapper">
                    <input type="checkbox" name="smart_all_images" value="1" <?php checked($settings['smart_all_images']); ?>>
                    <span class="es-toggle-switch"></span>
                </label>
            </div>
            
            <div class="es-setting-row">
                <div class="es-setting-info">
                    <span class="es-setting-title">
                        <span class="dashicons dashicons-video-alt3" style="color: #ff5722; font-size: 16px; margin-right: 4px;"></span>
                        <?php esc_html_e('All Videos', 'ensemble'); ?>
                    </span>
                </div>
                <label class="es-toggle-wrapper">
                    <input type="checkbox" name="smart_all_videos" value="1" <?php checked($settings['smart_all_videos']); ?>>
                    <span class="es-toggle-switch"></span>
                </label>
            </div>
            
            <div class="es-setting-row">
                <div class="es-setting-info">
                    <span class="es-setting-title">
                        <span class="dashicons dashicons-media-document" style="color: #607d8b; font-size: 16px; margin-right: 4px;"></span>
                        <?php esc_html_e('Documents', 'ensemble'); ?>
                    </span>
                </div>
                <label class="es-toggle-wrapper">
                    <input type="checkbox" name="smart_all_documents" value="1" <?php checked($settings['smart_all_documents']); ?>>
                    <span class="es-toggle-switch"></span>
                </label>
            </div>
            
            <div class="es-setting-row">
                <div class="es-setting-info">
                    <span class="es-setting-title">
                        <span class="dashicons dashicons-calendar" style="color: #00bcd4; font-size: 16px; margin-right: 4px;"></span>
                        <?php esc_html_e('This Week', 'ensemble'); ?>
                    </span>
                    <span class="es-setting-desc"><?php esc_html_e('Media uploaded in the last 7 days', 'ensemble'); ?></span>
                </div>
                <label class="es-toggle-wrapper">
                    <input type="checkbox" name="smart_this_week" value="1" <?php checked($settings['smart_this_week']); ?>>
                    <span class="es-toggle-switch"></span>
                </label>
            </div>
            
            <div class="es-setting-row">
                <div class="es-setting-info">
                    <span class="es-setting-title">
                        <span class="dashicons dashicons-dismiss" style="color: #ff9800; font-size: 16px; margin-right: 4px;"></span>
                        <?php esc_html_e('Unattached', 'ensemble'); ?>
                    </span>
                    <span class="es-setting-desc"><?php esc_html_e('Media not attached to any post', 'ensemble'); ?></span>
                </div>
                <label class="es-toggle-wrapper">
                    <input type="checkbox" name="smart_unused" value="1" <?php checked($settings['smart_unused']); ?>>
                    <span class="es-toggle-switch"></span>
                </label>
            </div>
            
            <div class="es-setting-row">
                <div class="es-setting-info">
                    <span class="es-setting-title">
                        <span class="dashicons dashicons-database" style="color: #f44336; font-size: 16px; margin-right: 4px;"></span>
                        <?php esc_html_e('Large Files', 'ensemble'); ?>
                    </span>
                    <span class="es-setting-desc"><?php esc_html_e('Files larger than threshold', 'ensemble'); ?></span>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <input type="number" name="smart_large_threshold" value="<?php echo esc_attr($settings['smart_large_threshold']); ?>" min="1" max="100" style="width: 60px; padding: 6px 8px; background: var(--ensemble-input-bg, #1a1a1a); border: 1px solid var(--ensemble-input-border, #3a3a3a); border-radius: 4px; color: var(--ensemble-text, #e0e0e0); font-size: 13px;">
                    <span style="color: var(--ensemble-text-secondary, #9ca3af); font-size: 12px;">MB</span>
                    <label class="es-toggle-wrapper" style="margin-left: 8px;">
                        <input type="checkbox" name="smart_large_files" value="1" <?php checked($settings['smart_large_files']); ?>>
                        <span class="es-toggle-switch"></span>
                    </label>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bulk Organize Section -->
    <div class="es-settings-section" style="margin-top: 24px; padding-top: 24px; border-top: 1px solid var(--ensemble-card-border, #3a3a3a);">
        <h4 style="margin: 0 0 16px 0; font-size: 14px; font-weight: 600; color: var(--ensemble-text, #e0e0e0); display: flex; align-items: center; gap: 8px;">
            <span class="dashicons dashicons-portfolio"></span>
            <?php esc_html_e('Bulk Organize', 'ensemble'); ?>
        </h4>
        
        <p style="color: var(--ensemble-text-secondary, #9ca3af); font-size: 13px; margin: 0 0 16px 0;">
            <?php esc_html_e('Create folders for all existing posts and assign their media. This may take a while.', 'ensemble'); ?>
        </p>
        
        <div style="display: flex; flex-wrap: wrap; gap: 12px;">
            <button type="button" class="es-bulk-btn" data-type="events" style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; background: #3582c4; color: #fff; border: none; border-radius: 6px; font-size: 13px; cursor: pointer;">
                <span class="dashicons dashicons-calendar-alt" style="font-size: 16px;"></span>
                <?php esc_html_e('Organize Events', 'ensemble'); ?>
            </button>
            <button type="button" class="es-bulk-btn" data-type="artists" style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; background: #9b59b6; color: #fff; border: none; border-radius: 6px; font-size: 13px; cursor: pointer;">
                <span class="dashicons dashicons-admin-users" style="font-size: 16px;"></span>
                <?php esc_html_e('Organize Artists', 'ensemble'); ?>
            </button>
            <button type="button" class="es-bulk-btn" data-type="locations" style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; background: #27ae60; color: #fff; border: none; border-radius: 6px; font-size: 13px; cursor: pointer;">
                <span class="dashicons dashicons-location" style="font-size: 16px;"></span>
                <?php esc_html_e('Organize Locations', 'ensemble'); ?>
            </button>
        </div>
        
        <div class="es-bulk-status" style="display: none; margin-top: 12px; padding: 12px; background: var(--ensemble-hover, #2a2a2a); border-radius: 6px;">
            <span class="es-bulk-status-text" style="color: var(--ensemble-text, #e0e0e0); font-size: 13px;"></span>
        </div>
    </div>
    
</div>

<style>
.es-media-folders-settings .es-setting-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 12px 0;
    border-bottom: 1px solid var(--ensemble-card-border, #3a3a3a);
}

.es-media-folders-settings .es-setting-row:last-child {
    border-bottom: none;
}

.es-media-folders-settings .es-setting-info {
    flex: 1;
    padding-right: 16px;
}

.es-media-folders-settings .es-setting-title {
    display: block;
    font-size: 13px;
    font-weight: 500;
    color: var(--ensemble-text, #e0e0e0);
    margin-bottom: 2px;
}

.es-media-folders-settings .es-setting-desc {
    display: block;
    font-size: 12px;
    color: var(--ensemble-text-secondary, #9ca3af);
    line-height: 1.4;
}

.es-media-folders-settings .es-bulk-btn:hover {
    opacity: 0.9;
    transform: translateY(-1px);
}

.es-media-folders-settings .es-bulk-btn.loading {
    opacity: 0.7;
    cursor: wait;
}
</style>

<script>
(function($) {
    // Bulk organize buttons
    $('.es-bulk-btn').on('click', function() {
        const $btn = $(this);
        const type = $btn.data('type');
        const $status = $('.es-bulk-status');
        const $statusText = $('.es-bulk-status-text');
        
        if ($btn.hasClass('loading')) return;
        
        $btn.addClass('loading').prop('disabled', true);
        $status.show();
        $statusText.text('<?php echo esc_js(__('Processing...', 'ensemble')); ?>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'es_bulk_organize_media',
                nonce: '<?php echo wp_create_nonce('es_media_folders'); ?>',
                type: type
            },
            success: function(response) {
                if (response.success) {
                    $statusText.text(response.data.message);
                } else {
                    $statusText.text(response.data || '<?php echo esc_js(__('An error occurred', 'ensemble')); ?>');
                }
            },
            error: function() {
                $statusText.text('<?php echo esc_js(__('An error occurred', 'ensemble')); ?>');
            },
            complete: function() {
                $btn.removeClass('loading').prop('disabled', false);
            }
        });
    });
})(jQuery);
</script>
