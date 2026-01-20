<?php
/**
 * Floor Plan Manager Admin Template
 *
 * @package Ensemble
 * @subpackage Addons/FloorPlan
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get locations for dropdown
$locations = array();
if (class_exists('ES_Location_Manager')) {
    $manager = new ES_Location_Manager();
    $locations = $manager->get_locations();
}
?>

<div class="wrap es-manager-wrap es-floor-plans-wrap">
    <h1><?php _e('Floor Plans', 'ensemble'); ?></h1>
    
    <div class="es-manager-container">
        
        <!-- Toolbar -->
        <div class="es-manager-toolbar">
            <div class="es-toolbar-left">
                <div class="es-search-box">
                    <input type="text" id="es-floor-plan-search" placeholder="<?php _e('Search floor plans...', 'ensemble'); ?>">
                    <button id="es-floor-plan-search-btn" class="button"><?php _e('Search', 'ensemble'); ?></button>
                </div>
                
                <select id="es-floor-plan-location-filter" class="es-filter-select">
                    <option value=""><?php _e('All Locations', 'ensemble'); ?></option>
                    <?php foreach ($locations as $location): ?>
                    <option value="<?php echo esc_attr($location['id']); ?>">
                        <?php echo esc_html($location['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="es-toolbar-right">
                <button id="es-bulk-delete-btn" class="button" style="display: none;">
                    <?php _e('Delete Selected', 'ensemble'); ?>
                </button>
                <button id="es-create-floor-plan-btn" class="button button-primary">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <?php _e('Create Floor Plan', 'ensemble'); ?>
                </button>
            </div>
        </div>
        
        <!-- View Toggle -->
        <div class="es-view-toggle">
            <button class="es-view-btn active" data-view="grid">
                <span class="dashicons dashicons-grid-view"></span>
                <?php _e('Grid', 'ensemble'); ?>
            </button>
            <button class="es-view-btn" data-view="list">
                <span class="dashicons dashicons-list-view"></span>
                <?php _e('List', 'ensemble'); ?>
            </button>
        </div>
        
        <!-- Floor Plans Container -->
        <div id="es-floor-plans-container" class="es-items-container">
            <div class="es-loading"><?php _e('Loading floor plans...', 'ensemble'); ?></div>
        </div>
        
    </div>
    
    <!-- Floor Plan Editor Modal -->
    <div id="es-floor-plan-modal" class="es-modal es-modal-fullscreen" style="display: none;">
        <div class="es-modal-content es-modal-fullscreen-content">
            <div class="es-modal-header es-floor-plan-header">
                <div class="es-header-left">
                    <button type="button" class="es-back-btn" id="es-floor-plan-back">
                        <span class="dashicons dashicons-arrow-left-alt"></span>
                        <?php _e('Back', 'ensemble'); ?>
                    </button>
                    <input type="text" id="es-floor-plan-title" class="es-inline-title" placeholder="<?php _e('Floor Plan Name', 'ensemble'); ?>">
                </div>
                <div class="es-header-right">
                    <button type="button" class="button" id="es-floor-plan-preview">
                        <span class="dashicons dashicons-visibility"></span>
                        <?php _e('Preview', 'ensemble'); ?>
                    </button>
                    <button type="button" class="button button-primary" id="es-floor-plan-save">
                        <span class="dashicons dashicons-cloud-saved"></span>
                        <?php _e('Save', 'ensemble'); ?>
                    </button>
                </div>
            </div>
            
            <div class="es-floor-plan-editor">
                
                <!-- Left Sidebar: Elements -->
                <div class="es-floor-plan-sidebar es-floor-plan-sidebar-left">
                    
                    <!-- Elements Panel -->
                    <div class="es-sidebar-panel es-sidebar-panel-elements">
                        <div class="es-sidebar-panel-header">
                            <h3><span class="dashicons dashicons-screenoptions"></span> <?php _e('Elements', 'ensemble'); ?></h3>
                        </div>
                        <div class="es-sidebar-panel-body">
                            <div class="es-element-palette">
                                <div class="es-element-item" data-type="table" draggable="true">
                                    <span class="es-element-icon"><span class="dashicons dashicons-groups"></span></span>
                                    <span class="es-element-label"><?php _e('Table', 'ensemble'); ?></span>
                                </div>
                                <div class="es-element-item" data-type="section" draggable="true">
                                    <span class="es-element-icon"><span class="dashicons dashicons-screenoptions"></span></span>
                                    <span class="es-element-label"><?php _e('Section', 'ensemble'); ?></span>
                                </div>
                                <div class="es-element-item" data-type="stage" draggable="true">
                                    <span class="es-element-icon"><span class="dashicons dashicons-megaphone"></span></span>
                                    <span class="es-element-label"><?php _e('Stage', 'ensemble'); ?></span>
                                </div>
                                <div class="es-element-item" data-type="bar" draggable="true">
                                    <span class="es-element-icon"><span class="dashicons dashicons-coffee"></span></span>
                                    <span class="es-element-label"><?php _e('Bar', 'ensemble'); ?></span>
                                </div>
                                <div class="es-element-item" data-type="entrance" draggable="true">
                                    <span class="es-element-icon"><span class="dashicons dashicons-admin-home"></span></span>
                                    <span class="es-element-label"><?php _e('Entrance', 'ensemble'); ?></span>
                                </div>
                                <div class="es-element-item" data-type="lounge" draggable="true">
                                    <span class="es-element-icon"><span class="dashicons dashicons-businessman"></span></span>
                                    <span class="es-element-label"><?php _e('Lounge', 'ensemble'); ?></span>
                                </div>
                                <div class="es-element-item" data-type="dancefloor" draggable="true">
                                    <span class="es-element-icon"><span class="dashicons dashicons-format-audio"></span></span>
                                    <span class="es-element-label"><?php _e('Dancefloor', 'ensemble'); ?></span>
                                </div>
                                <div class="es-element-item" data-type="amenity" draggable="true">
                                    <span class="es-element-icon"><span class="dashicons dashicons-admin-generic"></span></span>
                                    <span class="es-element-label"><?php _e('Amenity', 'ensemble'); ?></span>
                                </div>
                                <div class="es-element-item" data-type="custom" draggable="true">
                                    <span class="es-element-icon"><span class="dashicons dashicons-edit"></span></span>
                                    <span class="es-element-label"><?php _e('Custom', 'ensemble'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Canvas Settings Panel -->
                    <div class="es-sidebar-panel es-sidebar-panel-canvas">
                        <div class="es-sidebar-panel-header">
                            <h3><span class="dashicons dashicons-art"></span> <?php _e('Canvas', 'ensemble'); ?></h3>
                        </div>
                        <div class="es-sidebar-panel-body">
                            <div class="es-form-row es-form-row-compact">
                                <label for="es-canvas-width"><?php _e('Width', 'ensemble'); ?></label>
                                <input type="number" id="es-canvas-width" value="1200" min="400" max="3000" step="50">
                            </div>
                            <div class="es-form-row es-form-row-compact">
                                <label for="es-canvas-height"><?php _e('Height', 'ensemble'); ?></label>
                                <input type="number" id="es-canvas-height" value="800" min="400" max="2000" step="50">
                            </div>
                            <div class="es-form-row es-form-row-compact">
                                <label>
                                    <input type="checkbox" id="es-show-grid" checked>
                                    <?php _e('Show Grid', 'ensemble'); ?>
                                </label>
                            </div>
                            <div class="es-form-row es-form-row-compact">
                                <label for="es-grid-size"><?php _e('Grid Size', 'ensemble'); ?></label>
                                <input type="number" id="es-grid-size" value="20" min="10" max="50" step="5">
                            </div>
                            <div class="es-form-row">
                                <label><?php _e('Background Image', 'ensemble'); ?></label>
                                <div class="es-background-preview" id="es-background-preview">
                                    <span class="es-no-image"><?php _e('No image', 'ensemble'); ?></span>
                                </div>
                                <input type="hidden" id="es-background-url" value="">
                                <div class="es-background-actions">
                                    <button type="button" class="button" id="es-select-background">
                                        <?php _e('Select Image', 'ensemble'); ?>
                                    </button>
                                    <button type="button" class="button" id="es-remove-background" style="display: none;">
                                        <?php _e('Remove', 'ensemble'); ?>
                                    </button>
                                </div>
                            </div>
                            <div class="es-form-row es-form-row-compact">
                                <label for="es-linked-location"><?php _e('Link to Location', 'ensemble'); ?></label>
                                <select id="es-linked-location">
                                    <option value=""><?php _e('No location', 'ensemble'); ?></option>
                                    <?php foreach ($locations as $location): ?>
                                    <option value="<?php echo esc_attr($location['id']); ?>">
                                        <?php echo esc_html($location['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                </div>
                
                <!-- Canvas Area -->
                <div class="es-floor-plan-canvas-wrapper">
                    <div class="es-canvas-toolbar">
                        <div class="es-canvas-zoom">
                            <button type="button" class="es-zoom-btn" id="es-zoom-out" title="<?php _e('Zoom Out', 'ensemble'); ?>">
                                <span class="dashicons dashicons-minus"></span>
                            </button>
                            <span class="es-zoom-level" id="es-zoom-level">100%</span>
                            <button type="button" class="es-zoom-btn" id="es-zoom-in" title="<?php _e('Zoom In', 'ensemble'); ?>">
                                <span class="dashicons dashicons-plus"></span>
                            </button>
                            <button type="button" class="es-zoom-btn" id="es-zoom-fit" title="<?php _e('Fit to View', 'ensemble'); ?>">
                                <span class="dashicons dashicons-fullscreen-alt"></span>
                            </button>
                        </div>
                        <div class="es-canvas-actions">
                            <button type="button" class="es-canvas-action" id="es-undo" title="<?php _e('Undo', 'ensemble'); ?>" disabled>
                                <span class="dashicons dashicons-undo"></span>
                            </button>
                            <button type="button" class="es-canvas-action" id="es-redo" title="<?php _e('Redo', 'ensemble'); ?>" disabled>
                                <span class="dashicons dashicons-redo"></span>
                            </button>
                            <span class="es-toolbar-separator"></span>
                            <button type="button" class="es-canvas-action" id="es-select-all" title="<?php _e('Select All', 'ensemble'); ?>">
                                <span class="dashicons dashicons-screenoptions"></span>
                            </button>
                            <button type="button" class="es-canvas-action" id="es-delete-selected" title="<?php _e('Delete Selected', 'ensemble'); ?>" disabled>
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                        </div>
                    </div>
                    <div class="es-canvas-container" id="es-canvas-container">
                        <!-- Konva canvas will be rendered here -->
                    </div>
                </div>
                
                <!-- Right Sidebar: Properties & Sections -->
                <div class="es-floor-plan-sidebar es-floor-plan-sidebar-right">
                    
                    <!-- Element Properties Panel (shown when element selected) -->
                    <div class="es-sidebar-panel es-sidebar-panel-properties" id="es-properties-panel" style="display: none;">
                        <div class="es-sidebar-panel-header">
                            <h3><span class="dashicons dashicons-admin-settings"></span> <?php _e('Properties', 'ensemble'); ?></h3>
                        </div>
                        <div class="es-sidebar-panel-body">
                            <input type="hidden" id="es-prop-element-id">
                            
                            <div class="es-form-row es-form-row-compact">
                                <label for="es-prop-label"><?php _e('Label', 'ensemble'); ?></label>
                                <input type="text" id="es-prop-label" placeholder="<?php _e('e.g., VIP Table 1', 'ensemble'); ?>">
                            </div>
                            
                            <div class="es-form-row es-form-row-compact es-prop-number-row">
                                <label for="es-prop-number"><?php _e('Number', 'ensemble'); ?></label>
                                <input type="number" id="es-prop-number" min="0" step="1">
                            </div>
                            
                            <div class="es-form-row es-form-row-compact es-prop-shape-row">
                                <label for="es-prop-shape"><?php _e('Shape', 'ensemble'); ?></label>
                                <select id="es-prop-shape">
                                    <option value="round"><?php _e('Round', 'ensemble'); ?></option>
                                    <option value="square"><?php _e('Square', 'ensemble'); ?></option>
                                    <option value="rectangle"><?php _e('Rectangle', 'ensemble'); ?></option>
                                </select>
                            </div>
                            
                            <div class="es-form-row-group">
                                <div class="es-form-row es-form-row-compact es-form-row-half">
                                    <label for="es-prop-width"><?php _e('Width', 'ensemble'); ?></label>
                                    <input type="number" id="es-prop-width" min="20" step="5">
                                </div>
                                <div class="es-form-row es-form-row-compact es-form-row-half">
                                    <label for="es-prop-height"><?php _e('Height', 'ensemble'); ?></label>
                                    <input type="number" id="es-prop-height" min="20" step="5">
                                </div>
                            </div>
                            
                            <div class="es-form-row es-form-row-compact">
                                <label for="es-prop-rotation"><?php _e('Rotation', 'ensemble'); ?></label>
                                <input type="number" id="es-prop-rotation" min="0" max="360" step="15" value="0">
                            </div>
                            
                            <hr class="es-sidebar-divider">
                            
                            <div class="es-form-row es-form-row-compact es-prop-bookable-row">
                                <label>
                                    <input type="checkbox" id="es-prop-bookable">
                                    <?php _e('Bookable', 'ensemble'); ?>
                                </label>
                            </div>
                            
                            <div class="es-bookable-options" id="es-bookable-options" style="display: none;">
                                <div class="es-form-row es-form-row-compact es-prop-seats-row">
                                    <label for="es-prop-seats"><?php _e('Seats / Capacity', 'ensemble'); ?></label>
                                    <input type="number" id="es-prop-seats" min="1" step="1" value="8">
                                </div>
                                
                                <div class="es-form-row es-form-row-compact">
                                    <label for="es-prop-section"><?php _e('Section', 'ensemble'); ?></label>
                                    <select id="es-prop-section">
                                        <option value=""><?php _e('No section', 'ensemble'); ?></option>
                                    </select>
                                </div>
                                
                                <div class="es-form-row es-form-row-compact">
                                    <label for="es-prop-price"><?php _e('Price', 'ensemble'); ?></label>
                                    <input type="number" id="es-prop-price" min="0" step="0.01">
                                </div>
                                
                                <div class="es-form-row es-form-row-compact">
                                    <label>
                                        <input type="checkbox" id="es-prop-accessible">
                                        <?php _e('Wheelchair Accessible', 'ensemble'); ?>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="es-form-row es-form-row-compact">
                                <label for="es-prop-description"><?php _e('Description', 'ensemble'); ?></label>
                                <textarea id="es-prop-description" rows="2" placeholder="<?php _e('Additional info...', 'ensemble'); ?>"></textarea>
                            </div>
                            
                            <div class="es-properties-actions">
                                <button type="button" class="button" id="es-prop-duplicate">
                                    <span class="dashicons dashicons-admin-page"></span>
                                    <?php _e('Duplicate', 'ensemble'); ?>
                                </button>
                                <button type="button" class="button es-button-danger" id="es-prop-delete">
                                    <span class="dashicons dashicons-trash"></span>
                                    <?php _e('Delete', 'ensemble'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sections Panel -->
                    <div class="es-sidebar-panel es-sidebar-panel-sections">
                        <div class="es-sidebar-panel-header">
                            <h3><span class="dashicons dashicons-category"></span> <?php _e('Sections', 'ensemble'); ?></h3>
                            <button type="button" class="es-icon-btn" id="es-add-section" title="<?php _e('Add Section', 'ensemble'); ?>">
                                <span class="dashicons dashicons-plus-alt2"></span>
                            </button>
                        </div>
                        <div class="es-sidebar-panel-body">
                            <div class="es-sections-list" id="es-sections-list">
                                <!-- Sections will be added here dynamically -->
                            </div>
                        </div>
                    </div>
                    
                    <!-- Elements List Panel -->
                    <div class="es-sidebar-panel es-sidebar-panel-elements-list">
                        <div class="es-sidebar-panel-header">
                            <h3><span class="dashicons dashicons-list-view"></span> <?php _e('Elements', 'ensemble'); ?></h3>
                            <span class="es-element-count" id="es-element-count">0</span>
                        </div>
                        <div class="es-sidebar-panel-body">
                            <div class="es-elements-list" id="es-elements-list">
                                <!-- Elements will be listed here -->
                            </div>
                        </div>
                    </div>
                    
                </div>
                
            </div>
            
            <input type="hidden" id="es-floor-plan-id" value="">
        </div>
    </div>
    
    <!-- Section Edit Modal -->
    <div id="es-section-modal" class="es-modal" style="display: none;">
        <div class="es-modal-content es-modal-small">
            <span class="es-modal-close">&times;</span>
            
            <div class="es-modal-header">
                <h2 id="es-section-modal-title"><?php _e('Add Section', 'ensemble'); ?></h2>
            </div>
            
            <div class="es-modal-body">
                <input type="hidden" id="es-section-id">
                
                <div class="es-form-row">
                    <label for="es-section-name"><?php _e('Section Name', 'ensemble'); ?> *</label>
                    <input type="text" id="es-section-name" required placeholder="<?php _e('e.g., VIP Area', 'ensemble'); ?>">
                </div>
                
                <div class="es-form-row">
                    <label for="es-section-color"><?php _e('Color', 'ensemble'); ?></label>
                    <input type="color" id="es-section-color" value="#3B82F6">
                </div>
                
                <div class="es-form-row">
                    <label for="es-section-price"><?php _e('Default Price', 'ensemble'); ?></label>
                    <input type="number" id="es-section-price" min="0" step="0.01" placeholder="0.00">
                </div>
            </div>
            
            <div class="es-modal-footer">
                <button type="button" class="button" id="es-section-cancel"><?php _e('Cancel', 'ensemble'); ?></button>
                <button type="button" class="button button-primary" id="es-section-save"><?php _e('Save Section', 'ensemble'); ?></button>
            </div>
        </div>
    </div>
    
</div>
