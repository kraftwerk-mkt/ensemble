<?php
/**
 * FAQ Manager Admin Template
 * 
 * Ensemble-styled FAQ management with list view
 *
 * @package Ensemble
 * @subpackage Addons/FAQ
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get categories
$categories = get_terms(array(
    'taxonomy'   => ES_FAQ_Addon::TAXONOMY,
    'hide_empty' => false,
));

if (is_wp_error($categories)) {
    $categories = array();
}
?>

<div class="wrap es-manager-wrap es-faq-manager-wrap">
    <h1>
        <span class="dashicons dashicons-editor-help"></span>
        <?php _e('FAQ Manager', 'ensemble'); ?>
    </h1>
    
    <div class="es-manager-container">
        
        <!-- Toolbar -->
        <div class="es-wizard-toolbar es-faq-toolbar">
            <div class="es-toolbar-row es-toolbar-main-row">
                
                <!-- Search -->
                <div class="es-filter-search">
                    <input type="text" 
                           id="es-faq-search" 
                           class="es-search-input" 
                           placeholder="<?php _e('FAQs durchsuchen...', 'ensemble'); ?>">
                    <span class="es-search-icon">
                        <span class="dashicons dashicons-search"></span>
                    </span>
                </div>
                
                <!-- Category Filter -->
                <select id="es-faq-category-filter" class="es-filter-select">
                    <option value=""><?php _e('Alle Kategorien', 'ensemble'); ?></option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo esc_attr($cat->term_id); ?>">
                        <?php echo esc_html($cat->name); ?> (<?php echo $cat->count; ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
                
                <span class="es-toolbar-divider"></span>
                
                <!-- Bulk Actions -->
                <div class="es-bulk-actions-inline" id="es-bulk-actions" style="display: none;">
                    <span class="es-bulk-selected-count" id="es-faq-selected-count"></span>
                    <select id="es-faq-bulk-action">
                        <option value=""><?php _e('Bulk Actions', 'ensemble'); ?></option>
                        <option value="delete"><?php _e('Löschen', 'ensemble'); ?></option>
                        <option value="assign_category"><?php _e('Kategorie zuweisen', 'ensemble'); ?></option>
                    </select>
                    <button type="button" id="es-faq-apply-bulk" class="button">
                        <span class="dashicons dashicons-yes"></span>
                    </button>
                </div>
                
                <div class="es-toolbar-spacer"></div>
                
                <!-- Count -->
                <span id="es-faq-count" class="es-item-count"></span>
                
                <!-- Add New Button -->
                <button type="button" id="es-add-faq-btn" class="button button-primary">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <?php _e('Neue FAQ', 'ensemble'); ?>
                </button>
            </div>
        </div>
        
        <!-- FAQ List -->
        <div id="es-faq-list" class="es-faq-list">
            <div class="es-loading">
                <span class="spinner is-active"></span>
                <?php _e('FAQs werden geladen...', 'ensemble'); ?>
            </div>
        </div>
        
    </div>
</div>

<!-- FAQ Modal -->
<div id="es-faq-modal" class="es-modal" style="display: none;">
    <div class="es-modal-overlay"></div>
    <div class="es-modal-content es-modal-medium">
        <div class="es-modal-header">
            <h2 id="es-faq-modal-title"><?php _e('Neue FAQ', 'ensemble'); ?></h2>
            <button type="button" class="es-modal-close">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        
        <form id="es-faq-form" class="es-modal-body">
            <input type="hidden" id="es-faq-id" name="faq_id" value="">
            <?php wp_nonce_field('es_faq_nonce', 'es_faq_nonce'); ?>
            
            <!-- Question -->
            <div class="es-form-group">
                <label for="es-faq-question" class="es-form-label">
                    <?php _e('Frage', 'ensemble'); ?> <span class="required">*</span>
                </label>
                <input type="text" 
                       id="es-faq-question" 
                       name="question" 
                       class="es-form-input widefat" 
                       required
                       placeholder="<?php _e('z.B. Wie kann ich mein Passwort zurücksetzen?', 'ensemble'); ?>">
            </div>
            
            <!-- Answer -->
            <div class="es-form-group">
                <label for="es-faq-answer" class="es-form-label">
                    <?php _e('Antwort', 'ensemble'); ?> <span class="required">*</span>
                </label>
                <textarea id="es-faq-answer" 
                          name="answer" 
                          class="es-form-textarea widefat" 
                          rows="6" 
                          required
                          placeholder="<?php _e('Schreibe hier die ausführliche Antwort...', 'ensemble'); ?>"></textarea>
                <p class="es-form-help"><?php _e('HTML ist erlaubt für Formatierung', 'ensemble'); ?></p>
            </div>
            
            <!-- Category -->
            <div class="es-form-group">
                <label for="es-faq-category" class="es-form-label">
                    <?php _e('Kategorie', 'ensemble'); ?>
                </label>
                <select id="es-faq-category" name="category" class="es-form-select widefat">
                    <option value=""><?php _e('Keine Kategorie', 'ensemble'); ?></option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo esc_attr($cat->term_id); ?>">
                        <?php echo esc_html($cat->name); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" id="es-add-category-btn" class="button button-small es-add-category-btn">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <?php _e('Neue Kategorie', 'ensemble'); ?>
                </button>
            </div>
            
            <!-- Order -->
            <div class="es-form-group es-form-group-inline">
                <div class="es-form-field">
                    <label for="es-faq-order" class="es-form-label">
                        <?php _e('Reihenfolge', 'ensemble'); ?>
                    </label>
                    <input type="number" 
                           id="es-faq-order" 
                           name="menu_order" 
                           class="es-form-input small-text" 
                           value="0"
                           min="0">
                </div>
                
                <div class="es-form-field">
                    <label class="es-toggle">
                        <input type="checkbox" id="es-faq-expanded" name="expanded" value="1">
                        <span class="es-toggle-track"></span>
                        <span class="es-toggle-label"><?php _e('Standardmäßig geöffnet', 'ensemble'); ?></span>
                    </label>
                </div>
            </div>
            
        </form>
        
        <div class="es-modal-footer">
            <button type="button" class="button es-modal-cancel">
                <?php _e('Abbrechen', 'ensemble'); ?>
            </button>
            <button type="button" id="es-faq-save" class="button button-primary">
                <span class="dashicons dashicons-saved"></span>
                <?php _e('FAQ speichern', 'ensemble'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Category Modal -->
<div id="es-category-modal" class="es-modal" style="display: none;">
    <div class="es-modal-overlay"></div>
    <div class="es-modal-content es-modal-small">
        <div class="es-modal-header">
            <h2><?php _e('Neue Kategorie', 'ensemble'); ?></h2>
            <button type="button" class="es-modal-close">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        
        <form id="es-category-form" class="es-modal-body">
            <?php wp_nonce_field('es_faq_category_nonce', 'es_faq_category_nonce'); ?>
            
            <div class="es-form-group">
                <label for="es-category-name" class="es-form-label">
                    <?php _e('Kategoriename', 'ensemble'); ?> <span class="required">*</span>
                </label>
                <input type="text" 
                       id="es-category-name" 
                       name="category_name" 
                       class="es-form-input widefat" 
                       required>
            </div>
        </form>
        
        <div class="es-modal-footer">
            <button type="button" class="button es-modal-cancel">
                <?php _e('Abbrechen', 'ensemble'); ?>
            </button>
            <button type="button" id="es-category-save" class="button button-primary">
                <?php _e('Erstellen', 'ensemble'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="es-delete-modal" class="es-modal" style="display: none;">
    <div class="es-modal-overlay"></div>
    <div class="es-modal-content es-modal-small es-modal-danger">
        <div class="es-modal-header">
            <h2><?php _e('FAQ löschen?', 'ensemble'); ?></h2>
            <button type="button" class="es-modal-close">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        
        <div class="es-modal-body">
            <p><?php _e('Bist du sicher, dass du diese FAQ löschen möchtest? Diese Aktion kann nicht rückgängig gemacht werden.', 'ensemble'); ?></p>
            <p class="es-delete-faq-title"></p>
        </div>
        
        <div class="es-modal-footer">
            <button type="button" class="button es-modal-cancel">
                <?php _e('Abbrechen', 'ensemble'); ?>
            </button>
            <button type="button" id="es-confirm-delete" class="button button-danger">
                <span class="dashicons dashicons-trash"></span>
                <?php _e('Löschen', 'ensemble'); ?>
            </button>
        </div>
    </div>
</div>
