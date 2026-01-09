<?php
/**
 * FAQ Addon Settings Template
 * 
 * Uses Ensemble unified toggle component (es-toggle + es-toggle-track)
 * 
 * @package Ensemble
 * @subpackage Addons/FAQ
 * @version 1.0.0
 * 
 * Available variables:
 * @var array $settings Current addon settings
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="es-addon-settings-form">
    
    <!-- Anzeige-Einstellungen -->
    <div class="es-settings-section">
        <h3><?php _e('Anzeige', 'ensemble'); ?></h3>
        
        <div class="es-form-row">
            <label for="faq-layout-style"><?php _e('Layout Stil', 'ensemble'); ?></label>
            <select id="faq-layout-style" name="layout_style">
                <option value="cards" <?php selected($settings['layout_style'], 'cards'); ?>>
                    <?php _e('Cards (Standard)', 'ensemble'); ?>
                </option>
                <option value="minimal" <?php selected($settings['layout_style'], 'minimal'); ?>>
                    <?php _e('Minimal', 'ensemble'); ?>
                </option>
                <option value="bordered" <?php selected($settings['layout_style'], 'bordered'); ?>>
                    <?php _e('Bordered', 'ensemble'); ?>
                </option>
            </select>
        </div>
        
        <div class="es-form-row">
            <label for="faq-icon-position"><?php _e('Icon Position', 'ensemble'); ?></label>
            <select id="faq-icon-position" name="icon_position">
                <option value="right" <?php selected($settings['icon_position'], 'right'); ?>>
                    <?php _e('Rechts', 'ensemble'); ?>
                </option>
                <option value="left" <?php selected($settings['icon_position'], 'left'); ?>>
                    <?php _e('Links', 'ensemble'); ?>
                </option>
            </select>
        </div>
        
        <div class="es-form-row">
            <label for="faq-icon-type"><?php _e('Icon Typ', 'ensemble'); ?></label>
            <select id="faq-icon-type" name="icon_type">
                <option value="chevron" <?php selected($settings['icon_type'], 'chevron'); ?>>
                    <?php _e('Chevron (˅)', 'ensemble'); ?>
                </option>
                <option value="plus" <?php selected($settings['icon_type'], 'plus'); ?>>
                    <?php _e('Plus/Minus (+/-)', 'ensemble'); ?>
                </option>
                <option value="arrow" <?php selected($settings['icon_type'], 'arrow'); ?>>
                    <?php _e('Pfeil (→)', 'ensemble'); ?>
                </option>
            </select>
        </div>
        
        <div class="es-form-row">
            <label for="faq-items-per-page"><?php _e('FAQs pro Seite', 'ensemble'); ?></label>
            <input type="number" 
                   id="faq-items-per-page" 
                   name="items_per_page"
                   value="<?php echo esc_attr($settings['items_per_page']); ?>"
                   min="1" 
                   max="100"
                   class="small-text">
        </div>
    </div>
    
    <!-- Verhalten -->
    <div class="es-settings-section">
        <h3><?php _e('Verhalten', 'ensemble'); ?></h3>
        
        <div class="es-form-row">
            <label for="faq-animation-speed"><?php _e('Animations-Geschwindigkeit', 'ensemble'); ?></label>
            <input type="number" 
                   id="faq-animation-speed" 
                   name="animation_speed"
                   value="<?php echo esc_attr($settings['animation_speed']); ?>"
                   min="0" 
                   max="1000"
                   step="50"
                   class="small-text"> ms
            <small class="es-field-help"><?php _e('0 = keine Animation', 'ensemble'); ?></small>
        </div>
        
        <div class="es-toggle-group">
            <label class="es-toggle es-toggle--reverse es-toggle--block">
                <input type="checkbox" name="allow_multiple_open" value="1" <?php checked($settings['allow_multiple_open']); ?>>
                <span class="es-toggle-track"></span>
                <span class="es-toggle-label">
                    <?php _e('Mehrere gleichzeitig öffnen', 'ensemble'); ?>
                    <small><?php _e('Erlaubt das Öffnen mehrerer FAQs gleichzeitig', 'ensemble'); ?></small>
                </span>
            </label>
            
            <label class="es-toggle es-toggle--reverse es-toggle--block">
                <input type="checkbox" name="expand_first" value="1" <?php checked($settings['expand_first']); ?>>
                <span class="es-toggle-track"></span>
                <span class="es-toggle-label">
                    <?php _e('Erste FAQ automatisch öffnen', 'ensemble'); ?>
                    <small><?php _e('Die erste FAQ wird standardmäßig geöffnet angezeigt', 'ensemble'); ?></small>
                </span>
            </label>
            
            <label class="es-toggle es-toggle--reverse es-toggle--block">
                <input type="checkbox" name="show_icon" value="1" <?php checked($settings['show_icon']); ?>>
                <span class="es-toggle-track"></span>
                <span class="es-toggle-label">
                    <?php _e('FAQ-Icons anzeigen', 'ensemble'); ?>
                    <small><?php _e('Zeigt optionale Icons vor den Fragen (wenn gesetzt)', 'ensemble'); ?></small>
                </span>
            </label>
        </div>
    </div>
    
    <!-- Filter & Suche -->
    <div class="es-settings-section">
        <h3><?php _e('Filter & Suche', 'ensemble'); ?></h3>
        
        <div class="es-toggle-group">
            <label class="es-toggle es-toggle--reverse es-toggle--block">
                <input type="checkbox" name="show_category_filter" value="1" <?php checked($settings['show_category_filter']); ?>>
                <span class="es-toggle-track"></span>
                <span class="es-toggle-label">
                    <?php _e('Kategorie-Filter anzeigen', 'ensemble'); ?>
                    <small><?php _e('Zeigt Buttons zum Filtern nach Kategorien', 'ensemble'); ?></small>
                </span>
            </label>
            
            <label class="es-toggle es-toggle--reverse es-toggle--block">
                <input type="checkbox" name="show_search" value="1" <?php checked($settings['show_search']); ?>>
                <span class="es-toggle-track"></span>
                <span class="es-toggle-label">
                    <?php _e('Suchfeld anzeigen', 'ensemble'); ?>
                    <small><?php _e('Ermöglicht das Durchsuchen aller FAQs', 'ensemble'); ?></small>
                </span>
            </label>
        </div>
    </div>
    
    <!-- SEO -->
    <div class="es-settings-section">
        <h3><?php _e('SEO', 'ensemble'); ?></h3>
        
        <div class="es-toggle-group">
            <label class="es-toggle es-toggle--reverse es-toggle--block">
                <input type="checkbox" name="schema_markup" value="1" <?php checked($settings['schema_markup']); ?>>
                <span class="es-toggle-track"></span>
                <span class="es-toggle-label">
                    <?php _e('FAQ Schema Markup (Google Rich Results)', 'ensemble'); ?>
                    <small><?php _e('Fügt strukturierte Daten für FAQ-Rich-Results in Google hinzu', 'ensemble'); ?></small>
                </span>
            </label>
        </div>
    </div>
    
    <!-- Shortcode Info -->
    <div class="es-settings-section es-settings-info">
        <h3><?php _e('Shortcode Verwendung', 'ensemble'); ?></h3>
        
        <div class="es-code-block">
            <code>[ensemble_faq]</code>
            <span class="es-code-desc"><?php _e('Alle FAQs anzeigen', 'ensemble'); ?></span>
        </div>
        
        <div class="es-code-block">
            <code>[ensemble_faq category="allgemein"]</code>
            <span class="es-code-desc"><?php _e('Nur bestimmte Kategorie', 'ensemble'); ?></span>
        </div>
        
        <div class="es-code-block">
            <code>[ensemble_faq ids="12,45,67"]</code>
            <span class="es-code-desc"><?php _e('Spezifische FAQs (IDs)', 'ensemble'); ?></span>
        </div>
        
        <div class="es-code-block">
            <code>[ensemble_faq layout="minimal" show_search="false" limit="10"]</code>
            <span class="es-code-desc"><?php _e('Mit Optionen', 'ensemble'); ?></span>
        </div>
        
        <details class="es-shortcode-params">
            <summary><?php _e('Alle verfügbaren Parameter', 'ensemble'); ?></summary>
            <ul>
                <li><code>category</code> - <?php _e('Kategorie-Slug oder ID', 'ensemble'); ?></li>
                <li><code>ids</code> - <?php _e('Kommagetrennte FAQ-IDs', 'ensemble'); ?></li>
                <li><code>limit</code> - <?php _e('Maximale Anzahl', 'ensemble'); ?></li>
                <li><code>layout</code> - <?php _e('cards, minimal, bordered', 'ensemble'); ?></li>
                <li><code>show_filter</code> - <?php _e('true/false', 'ensemble'); ?></li>
                <li><code>show_search</code> - <?php _e('true/false', 'ensemble'); ?></li>
                <li><code>expand_first</code> - <?php _e('true/false', 'ensemble'); ?></li>
                <li><code>icon_position</code> - <?php _e('left, right', 'ensemble'); ?></li>
                <li><code>icon_type</code> - <?php _e('chevron, plus, arrow', 'ensemble'); ?></li>
                <li><code>orderby</code> - <?php _e('menu_order, title, date', 'ensemble'); ?></li>
                <li><code>order</code> - <?php _e('ASC, DESC', 'ensemble'); ?></li>
            </ul>
        </details>
    </div>
    
</div>

<style>
/* FAQ Settings specific styles */
.es-addon-settings-form .es-settings-section {
    margin-bottom: 24px;
    padding-bottom: 24px;
    border-bottom: 1px solid var(--es-border, #404040);
}

.es-addon-settings-form .es-settings-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.es-addon-settings-form .es-settings-section h3 {
    margin: 0 0 16px 0;
    font-size: 14px;
    font-weight: 600;
    color: var(--es-text, #e0e0e0);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.es-addon-settings-form .es-form-row {
    margin-bottom: 16px;
}

.es-addon-settings-form .es-form-row > label {
    display: block;
    margin-bottom: 6px;
    font-size: 13px;
    font-weight: 500;
    color: var(--es-text, #e0e0e0);
}

.es-addon-settings-form .es-form-row select,
.es-addon-settings-form .es-form-row input[type="number"],
.es-addon-settings-form .es-form-row input[type="text"] {
    background: var(--es-surface, #2c2c2c);
    border: 1px solid var(--es-border, #404040);
    color: var(--es-text, #e0e0e0);
    border-radius: 4px;
    padding: 8px 12px;
}

.es-addon-settings-form .es-form-row select:focus,
.es-addon-settings-form .es-form-row input:focus {
    border-color: var(--es-primary, #3582c4);
    outline: none;
}

.es-addon-settings-form .es-field-help {
    display: block;
    margin-top: 4px;
    font-size: 12px;
    color: var(--es-text-muted, #787878);
}

.es-addon-settings-form .es-toggle-group {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.es-addon-settings-form .es-toggle--block {
    display: flex;
    padding: 12px;
    background: var(--es-surface, #2c2c2c);
    border: 1px solid var(--es-border, #404040);
    border-radius: 6px;
    transition: border-color 0.2s;
}

.es-addon-settings-form .es-toggle--block:hover {
    border-color: var(--es-primary, #3582c4);
}

/* Shortcode Info Section */
.es-addon-settings-form .es-settings-info {
    background: var(--es-surface-secondary, #383838);
    border-radius: 6px;
    padding: 16px;
    border: none !important;
}

.es-addon-settings-form .es-code-block {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 10px;
    padding: 8px 12px;
    background: var(--es-background, #1e1e1e);
    border-radius: 4px;
}

.es-addon-settings-form .es-code-block code {
    font-family: 'Monaco', 'Consolas', monospace;
    font-size: 12px;
    color: var(--es-primary, #3582c4);
    background: none;
    padding: 0;
}

.es-addon-settings-form .es-code-desc {
    font-size: 12px;
    color: var(--es-text-secondary, #a0a0a0);
}

.es-addon-settings-form .es-shortcode-params {
    margin-top: 16px;
}

.es-addon-settings-form .es-shortcode-params summary {
    cursor: pointer;
    font-size: 13px;
    color: var(--es-primary, #3582c4);
}

.es-addon-settings-form .es-shortcode-params ul {
    margin: 12px 0 0 0;
    padding: 0;
    list-style: none;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 6px;
}

.es-addon-settings-form .es-shortcode-params li {
    font-size: 12px;
    color: var(--es-text-secondary, #a0a0a0);
}

.es-addon-settings-form .es-shortcode-params code {
    background: var(--es-background, #1e1e1e);
    padding: 1px 5px;
    border-radius: 3px;
    font-size: 11px;
    color: #4ade80;
}
</style>
