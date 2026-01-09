<?php
/**
 * Frontend Management - Controller
 * 
 * Central controller for Shortcodes, Designer, Templates and ID Picker tabs
 * 
 * @package Ensemble
 * @since 2.9.3
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get current tab
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'shortcodes';
?>

<div class="wrap es-frontend-wrap">
    <h1>
        <span class="dashicons dashicons-admin-appearance"></span>
        <?php _e('Frontend', 'ensemble'); ?>
    </h1>
    
    <div class="es-frontend-container">
        
        <!-- Tab Navigation -->
        <div class="es-frontend-tabs">
            <button class="es-tab-btn <?php echo $current_tab === 'shortcodes' ? 'active' : ''; ?>" 
                    onclick="window.location.href='?page=ensemble-frontend&tab=shortcodes'">
                <span class="dashicons dashicons-shortcode"></span>
                <?php _e('Shortcodes', 'ensemble'); ?>
            </button>
            <button class="es-tab-btn <?php echo $current_tab === 'id-picker' ? 'active' : ''; ?>" 
                    onclick="window.location.href='?page=ensemble-frontend&tab=id-picker'">
                <span class="dashicons dashicons-search"></span>
                <?php _e('ID Picker', 'ensemble'); ?>
            </button>
            <button class="es-tab-btn <?php echo $current_tab === 'layout-sets' ? 'active' : ''; ?>" 
                    onclick="window.location.href='?page=ensemble-frontend&tab=layout-sets'">
                <span class="dashicons dashicons-layout"></span>
                <?php _e('Layout-Sets', 'ensemble'); ?>
            </button>
            <button class="es-tab-btn <?php echo $current_tab === 'designer' ? 'active' : ''; ?>" 
                    onclick="window.location.href='?page=ensemble-frontend&tab=designer'">
                <span class="dashicons dashicons-admin-customizer"></span>
                <?php _e('Designer', 'ensemble'); ?>
            </button>
            <button class="es-tab-btn <?php echo $current_tab === 'templates' ? 'active' : ''; ?>" 
                    onclick="window.location.href='?page=ensemble-frontend&tab=templates'">
                <span class="dashicons dashicons-admin-page"></span>
                <?php _e('Templates', 'ensemble'); ?>
            </button>
        </div>
        
        <!-- Tab Content -->
        <?php
        switch ($current_tab) {
            case 'shortcodes':
                include ENSEMBLE_PLUGIN_DIR . 'admin/tabs/shortcodes.php';
                break;
                
            case 'id-picker':
                include ENSEMBLE_PLUGIN_DIR . 'admin/tabs/id-picker.php';
                break;
                
            case 'layout-sets':
                include ENSEMBLE_PLUGIN_DIR . 'admin/tabs/layout-sets.php';
                break;
                
            case 'designer':
                include ENSEMBLE_PLUGIN_DIR . 'admin/tabs/designer.php';
                break;
                
            case 'templates':
                include ENSEMBLE_PLUGIN_DIR . 'admin/tabs/templates.php';
                break;
                
            default:
                include ENSEMBLE_PLUGIN_DIR . 'admin/tabs/shortcodes.php';
        }
        ?>
        
    </div>
</div>

<?php
// Include common admin styles
include ENSEMBLE_PLUGIN_DIR . 'admin/css/frontend-admin-styles.php';
?>
