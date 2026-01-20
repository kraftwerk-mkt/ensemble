<?php
/**
 * Import Events Template
 * 
 * USES: admin-unified.css for all styles
 *
 * @package Ensemble
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$is_pro = function_exists('ensemble_is_pro') && ensemble_is_pro();
?>

<div class="wrap es-manager-wrap es-import-wrap">
    <h1><?php _e('Import Events', 'ensemble'); ?></h1>
    
    <?php if (!$is_pro): ?>
    <!-- Pro Required Gate -->
    <div class="es-pro-gate">
        <div class="es-pro-gate-icon">
            <span class="dashicons dashicons-lock"></span>
        </div>
        <h2><?php _e('Pro Feature', 'ensemble'); ?></h2>
        <p>
            <?php _e('Der Import von Events aus iCal-Feeds und Dateien ist ein Pro-Feature. Upgrade um diese Funktion freizuschalten.', 'ensemble'); ?>
        </p>
        <a href="<?php echo admin_url('admin.php?page=ensemble-settings&tab=license'); ?>" class="button button-primary button-hero es-btn-upgrade">
            <?php _e('Upgrade auf Pro', 'ensemble'); ?>
        </a>
    </div>
    <?php else: ?>
    
    <div class="es-manager-container">
        
        <!-- Import Steps -->
        <div class="es-import-steps">
            <div class="es-step-indicator">
                <div class="es-step active" data-step="1">
                    <span class="es-step-number">1</span>
                    <span class="es-step-label"><?php _e('Source', 'ensemble'); ?></span>
                </div>
                <div class="es-step" data-step="2">
                    <span class="es-step-number">2</span>
                    <span class="es-step-label"><?php _e('Preview', 'ensemble'); ?></span>
                </div>
                <div class="es-step" data-step="3">
                    <span class="es-step-number">3</span>
                    <span class="es-step-label"><?php _e('Import', 'ensemble'); ?></span>
                </div>
            </div>
        </div>
        
        <!-- Step 1: Choose Source -->
        <div id="es-import-step-1" class="es-import-step active">
            <div class="es-form-section">
                <h2><?php _e('Select Import Source', 'ensemble'); ?></h2>
                
                <div class="es-source-selector">
                    <label class="es-source-option">
                        <input type="radio" name="import_source" value="url" checked>
                        <div class="es-source-card">
                            <span class="dashicons dashicons-admin-links"></span>
                            <h3><?php _e('iCal URL', 'ensemble'); ?></h3>
                            <p><?php _e('Import from an online calendar feed', 'ensemble'); ?></p>
                        </div>
                    </label>
                    
                    <label class="es-source-option">
                        <input type="radio" name="import_source" value="file">
                        <div class="es-source-card">
                            <span class="dashicons dashicons-upload"></span>
                            <h3><?php _e('Upload File', 'ensemble'); ?></h3>
                            <p><?php _e('Upload an .ics calendar file', 'ensemble'); ?></p>
                        </div>
                    </label>
                </div>
                
                <!-- URL Input -->
                <div id="es-url-input" class="es-source-input">
                    <div class="es-form-row">
                        <label for="es-ical-url"><?php _e('iCal Feed URL', 'ensemble'); ?></label>
                        <input type="url" id="es-ical-url" placeholder="https://example.com/calendar.ics">
                        <span class="es-field-help"><?php _e('Enter the URL of an iCal (.ics) feed', 'ensemble'); ?></span>
                    </div>
                </div>
                
                <!-- File Upload -->
                <div id="es-file-input" class="es-source-input" style="display: none;">
                    <div class="es-form-row">
                        <label for="es-ical-file"><?php _e('Select .ics File', 'ensemble'); ?></label>
                        <input type="file" id="es-ical-file" accept=".ics,.ical">
                        <span class="es-field-help"><?php _e('Upload an iCal (.ics) file from your computer', 'ensemble'); ?></span>
                    </div>
                </div>
                
                <div class="es-form-actions">
                    <button type="button" id="es-preview-btn" class="button button-primary button-large">
                        <?php _e('Preview Events', 'ensemble'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Step 2: Preview & Select Events -->
        <div id="es-import-step-2" class="es-import-step" style="display: none;">
            <div class="es-form-section">
                <h2><?php _e('Preview & Select Events', 'ensemble'); ?></h2>
                
                <!-- Update Mode Selection -->
                <div class="es-form-row">
                    <label><?php _e('Handling Existing Events', 'ensemble'); ?></label>
                    <div class="es-pill-group">
                        <label class="es-pill">
                            <input type="radio" name="update_mode" value="skip" checked>
                            <span><?php _e('Skip Existing', 'ensemble'); ?></span>
                        </label>
                        <label class="es-pill">
                            <input type="radio" name="update_mode" value="update">
                            <span><?php _e('Update Existing', 'ensemble'); ?></span>
                        </label>
                        <label class="es-pill">
                            <input type="radio" name="update_mode" value="duplicate">
                            <span><?php _e('Create Duplicates', 'ensemble'); ?></span>
                        </label>
                    </div>
                </div>
                
                <!-- Summary Stats -->
                <div class="es-import-summary">
                    <div class="es-summary-stat">
                        <span class="es-stat-label"><?php _e('Total Events:', 'ensemble'); ?></span>
                        <span class="es-stat-value" id="es-total-events">0</span>
                    </div>
                    <div class="es-summary-stat">
                        <span class="es-stat-label"><?php _e('New:', 'ensemble'); ?></span>
                        <span class="es-stat-value es-stat-new" id="es-new-events">0</span>
                    </div>
                    <div class="es-summary-stat">
                        <span class="es-stat-label"><?php _e('Existing:', 'ensemble'); ?></span>
                        <span class="es-stat-value es-stat-existing" id="es-existing-events">0</span>
                    </div>
                </div>
                
                <!-- Event Selection Toolbar -->
                <div class="es-manager-toolbar">
                    <div class="es-toolbar-left">
                        <button type="button" id="es-select-all-btn" class="button">
                            <?php _e('Select All', 'ensemble'); ?>
                        </button>
                        <button type="button" id="es-deselect-all-btn" class="button">
                            <?php _e('Deselect All', 'ensemble'); ?>
                        </button>
                    </div>
                    
                    <div class="es-toolbar-right">
                        <span id="es-selected-count">0 <?php _e('selected', 'ensemble'); ?></span>
                    </div>
                </div>
                
                <!-- Events List -->
                <div id="es-events-preview-container" class="es-items-container">
                    <div class="es-loading"><?php _e('Loading preview...', 'ensemble'); ?></div>
                </div>
                
                <div class="es-form-actions">
                    <button type="button" id="es-back-to-source-btn" class="button button-large">
                        <?php _e('Back', 'ensemble'); ?>
                    </button>
                    <button type="button" id="es-import-selected-btn" class="button button-primary button-large">
                        <?php _e('Import Selected Events', 'ensemble'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Step 3: Import Results -->
        <div id="es-import-step-3" class="es-import-step" style="display: none;">
            <div class="es-form-section">
                <h2><?php _e('Import Complete', 'ensemble'); ?></h2>
                
                <!-- Results Summary -->
                <div class="es-import-results">
                    <div class="es-result-stat es-result-success">
                        <span class="es-result-icon dashicons dashicons-yes-alt"></span>
                        <div>
                            <span class="es-result-count" id="es-result-created">0</span>
                            <span class="es-result-label"><?php _e('Created', 'ensemble'); ?></span>
                        </div>
                    </div>
                    <div class="es-result-stat es-result-updated">
                        <span class="es-result-icon dashicons dashicons-update"></span>
                        <div>
                            <span class="es-result-count" id="es-result-updated">0</span>
                            <span class="es-result-label"><?php _e('Updated', 'ensemble'); ?></span>
                        </div>
                    </div>
                    <div class="es-result-stat es-result-skipped">
                        <span class="es-result-icon dashicons dashicons-dismiss"></span>
                        <div>
                            <span class="es-result-count" id="es-result-skipped">0</span>
                            <span class="es-result-label"><?php _e('Skipped', 'ensemble'); ?></span>
                        </div>
                    </div>
                    <div class="es-result-stat es-result-failed">
                        <span class="es-result-icon dashicons dashicons-warning"></span>
                        <div>
                            <span class="es-result-count" id="es-result-failed">0</span>
                            <span class="es-result-label"><?php _e('Failed', 'ensemble'); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Detailed Results -->
                <div id="es-import-details" class="es-import-details"></div>
                
                <div class="es-form-actions">
                    <button type="button" id="es-import-another-btn" class="button button-large">
                        <?php _e('Import More Events', 'ensemble'); ?>
                    </button>
                    <a href="<?php echo admin_url('admin.php?page=ensemble-calendar'); ?>" class="button button-primary button-large">
                        <?php _e('View Calendar', 'ensemble'); ?>
                    </a>
                </div>
            </div>
        </div>
        
    </div>
    
    <!-- Success/Error Messages -->
    <div id="es-message" class="es-message" style="display: none;"></div>
    
<?php endif; // End Pro check ?>
</div>
