<?php
/**
 * Catalog Manager Admin Page
 */
if (!defined('ABSPATH')) exit;

$locations = get_posts(array('post_type' => 'ensemble_location', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC'));
$event_post_type = function_exists('ensemble_get_post_type') ? ensemble_get_post_type() : 'ensemble_event';
$events = get_posts(array('post_type' => $event_post_type, 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC'));
$catalog_types = $this->get_catalog_types();
?>
<div class="wrap es-catalog-wrap">
    <div class="es-admin-header">
        <h1><?php ES_Icons::icon('grid'); ?> <?php _e('Catalogs', 'ensemble'); ?></h1>
        <p class="es-admin-description"><?php _e('Menus, drink lists, merchandise and more for locations and events.', 'ensemble'); ?></p>
    </div>
    
    <div class="es-catalog-container">
        <!-- List Panel -->
        <div class="es-catalog-list-panel" id="es-catalog-list-panel">
            <div class="es-panel-header">
                <h2><?php _e('All Catalogs', 'ensemble'); ?></h2>
                <button type="button" class="button button-primary" id="es-new-catalog-btn">
                    <?php ES_Icons::icon('plus'); ?> <?php _e('New Catalog', 'ensemble'); ?>
                </button>
            </div>
            <div class="es-catalog-filters">
                <select id="es-filter-location">
                    <option value=""><?php _e('All Locations', 'ensemble'); ?></option>
                    <?php foreach ($locations as $loc): ?>
                        <option value="<?php echo $loc->ID; ?>"><?php echo esc_html($loc->post_title); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="es-catalog-grid" id="es-catalog-grid">
                <div class="es-loading"><?php _e('Loading...', 'ensemble'); ?></div>
            </div>
        </div>
        
        <!-- Editor Panel -->
        <div class="es-catalog-editor-panel" id="es-catalog-editor-panel" style="display: none;">
            <div class="es-panel-header">
                <button type="button" class="es-back-btn" id="es-back-to-list">
                    <?php ES_Icons::icon('chevron-left'); ?> <?php _e('Back', 'ensemble'); ?>
                </button>
                <h2 id="es-editor-title"><?php _e('Edit Catalog', 'ensemble'); ?></h2>
                <div class="es-editor-actions">
                    <button type="button" class="button" id="es-export-csv-btn" title="CSV Export"><?php ES_Icons::icon('download'); ?></button>
                    <button type="button" class="button" id="es-import-csv-btn" title="CSV Import"><?php ES_Icons::icon('upload'); ?></button>
                    <button type="button" class="button button-link-delete" id="es-delete-catalog-btn"><?php ES_Icons::icon('trash'); ?></button>
                </div>
            </div>
            
            <div class="es-editor-content">
                <div class="es-catalog-settings">
                    <div class="es-form-row">
                        <label for="es-catalog-title"><?php _e('Name', 'ensemble'); ?> *</label>
                        <input type="text" id="es-catalog-title" placeholder="<?php esc_attr_e('e.g. Main Menu, Happy Hour', 'ensemble'); ?>">
                    </div>
                    <div class="es-form-row">
                        <label for="es-catalog-type"><?php _e('Type', 'ensemble'); ?></label>
                        <select id="es-catalog-type">
                            <?php foreach ($catalog_types as $key => $data): ?>
                                <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($data['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="es-form-row es-form-row-half">
                        <div>
                            <label for="es-catalog-location"><?php _e('Location', 'ensemble'); ?></label>
                            <select id="es-catalog-location">
                                <option value=""><?php _e('None', 'ensemble'); ?></option>
                                <?php foreach ($locations as $loc): ?>
                                    <option value="<?php echo $loc->ID; ?>"><?php echo esc_html($loc->post_title); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e('Catalog will be displayed on the location page', 'ensemble'); ?></p>
                        </div>
                        <div>
                            <label for="es-catalog-event"><?php _e('Event', 'ensemble'); ?></label>
                            <select id="es-catalog-event">
                                <option value=""><?php _e('None (location only)', 'ensemble'); ?></option>
                                <?php foreach ($events as $evt): ?>
                                    <option value="<?php echo $evt->ID; ?>"><?php echo esc_html($evt->post_title); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e('Optional: Show catalog only for this event (e.g. Happy Hour)', 'ensemble'); ?></p>
                        </div>
                    </div>
                    <input type="hidden" id="es-catalog-id" value="0">
                </div>
                
                <div class="es-catalog-content">
                    <div class="es-categories-panel">
                        <div class="es-panel-subheader">
                            <h3><?php _e('Categories', 'ensemble'); ?></h3>
                            <button type="button" class="es-icon-btn" id="es-add-category-btn" title="<?php esc_attr_e('New Category', 'ensemble'); ?>">
                                <?php ES_Icons::icon('plus'); ?>
                            </button>
                        </div>
                        <div class="es-categories-list" id="es-categories-list"></div>
                    </div>
                    
                    <div class="es-items-panel">
                        <div class="es-panel-subheader">
                            <h3 id="es-items-category-title"><?php _e('Items', 'ensemble'); ?></h3>
                            <button type="button" class="button button-primary" id="es-add-item-btn" disabled>
                                <?php ES_Icons::icon('plus'); ?> <?php _e('New Item', 'ensemble'); ?>
                            </button>
                        </div>
                        <div class="es-items-list" id="es-items-list">
                            <div class="es-empty-state">
                                <?php ES_Icons::icon('grid'); ?>
                                <p><?php _e('Select a category', 'ensemble'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="es-editor-footer">
                <button type="button" class="button button-large" id="es-cancel-catalog-btn"><?php _e('Cancel', 'ensemble'); ?></button>
                <button type="button" class="button button-primary button-large" id="es-save-catalog-btn">
                    <?php ES_Icons::icon('check'); ?> <?php _e('Save Catalog', 'ensemble'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- New Catalog Modal -->
<div class="es-modal" id="es-new-catalog-modal" style="display: none;">
    <div class="es-modal-backdrop"></div>
    <div class="es-modal-content es-modal-lg">
        <div class="es-modal-header">
            <h3><?php _e('New Catalog', 'ensemble'); ?></h3>
            <button type="button" class="es-modal-close">&times;</button>
        </div>
        <div class="es-modal-body">
            <p class="es-modal-description"><?php _e('Select the catalog type:', 'ensemble'); ?></p>
            <div class="es-catalog-type-grid">
                <?php foreach ($catalog_types as $key => $data): ?>
                    <div class="es-catalog-type-card" data-type="<?php echo esc_attr($key); ?>">
                        <div class="es-type-icon"><?php ES_Icons::icon($data['icon']); ?></div>
                        <h4><?php echo esc_html($data['name']); ?></h4>
                        <p><?php echo esc_html($data['description']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Category Modal -->
<div class="es-modal" id="es-category-modal" style="display: none;">
    <div class="es-modal-backdrop"></div>
    <div class="es-modal-content">
        <div class="es-modal-header">
            <h3 id="es-category-modal-title"><?php _e('Category', 'ensemble'); ?></h3>
            <button type="button" class="es-modal-close">&times;</button>
        </div>
        <div class="es-modal-body">
            <div class="es-form-row">
                <label for="es-category-name"><?php _e('Name', 'ensemble'); ?> *</label>
                <input type="text" id="es-category-name">
            </div>
            <div class="es-form-row">
                <label for="es-category-color"><?php _e('Color', 'ensemble'); ?></label>
                <input type="color" id="es-category-color" value="#3582c4">
            </div>
            <input type="hidden" id="es-category-id" value="0">
        </div>
        <div class="es-modal-footer">
            <button type="button" class="button es-modal-cancel"><?php _e('Cancel', 'ensemble'); ?></button>
            <button type="button" class="button button-primary" id="es-save-category-btn"><?php _e('Save', 'ensemble'); ?></button>
        </div>
    </div>
</div>

<!-- Item Modal -->
<div class="es-modal" id="es-item-modal" style="display: none;">
    <div class="es-modal-backdrop"></div>
    <div class="es-modal-content es-modal-lg">
        <div class="es-modal-header">
            <h3 id="es-item-modal-title"><?php _e('Item', 'ensemble'); ?></h3>
            <button type="button" class="es-modal-close">&times;</button>
        </div>
        <div class="es-modal-body">
            <div class="es-form-row">
                <label for="es-item-title"><?php _e('Name', 'ensemble'); ?> *</label>
                <input type="text" id="es-item-title">
            </div>
            <div class="es-form-row">
                <label for="es-item-description"><?php _e('Description', 'ensemble'); ?></label>
                <textarea id="es-item-description" rows="2"></textarea>
            </div>
            <div class="es-item-attributes" id="es-item-attributes"></div>
            <div class="es-form-row">
                <label><?php _e('Image', 'ensemble'); ?></label>
                <div class="es-image-upload">
                    <div class="es-image-preview" id="es-item-image-preview" style="display: none;">
                        <img src="" alt=""><button type="button" class="es-remove-image">&times;</button>
                    </div>
                    <button type="button" class="button" id="es-select-image-btn"><?php ES_Icons::icon('image'); ?> <?php _e('Select Image', 'ensemble'); ?></button>
                </div>
                <input type="hidden" id="es-item-image-id" value="0">
            </div>
            <input type="hidden" id="es-item-id" value="0">
            <input type="hidden" id="es-item-category-id" value="0">
        </div>
        <div class="es-modal-footer">
            <button type="button" class="button es-modal-cancel"><?php _e('Cancel', 'ensemble'); ?></button>
            <button type="button" class="button button-primary" id="es-save-item-btn"><?php _e('Save', 'ensemble'); ?></button>
        </div>
    </div>
</div>

<!-- Import Modal -->
<div class="es-modal" id="es-import-modal" style="display: none;">
    <div class="es-modal-backdrop"></div>
    <div class="es-modal-content">
        <div class="es-modal-header">
            <h3><?php _e('CSV Import', 'ensemble'); ?></h3>
            <button type="button" class="es-modal-close">&times;</button>
        </div>
        <div class="es-modal-body">
            <p><?php _e('CSV columns: Name, Description, Category, Price', 'ensemble'); ?></p>
            <div class="es-form-row">
                <input type="file" id="es-csv-file" accept=".csv">
            </div>
        </div>
        <div class="es-modal-footer">
            <button type="button" class="button es-modal-cancel"><?php _e('Cancel', 'ensemble'); ?></button>
            <button type="button" class="button button-primary" id="es-start-import-btn"><?php _e('Import', 'ensemble'); ?></button>
        </div>
    </div>
</div>
