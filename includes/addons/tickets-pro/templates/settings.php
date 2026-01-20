<?php
/**
 * Tickets Pro - Settings Template
 * 
 * Rendered in the addon settings modal
 * 
 * @package Ensemble
 * @subpackage Addons/TicketsPro
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$currencies = array(
    'EUR' => '€ Euro',
    'USD' => '$ US Dollar',
    'GBP' => '£ British Pound',
    'CHF' => 'CHF Swiss Franc',
);

$price_formats = array(
    'symbol_before' => '€19,99',
    'symbol_after'  => '19,99 €',
    'code_before'   => 'EUR 19,99',
);

$pages = get_pages();
?>

<div class="es-tickets-settings">
    
    <!-- Currency & Pricing -->
    <div class="es-settings-section">
        <h3><?php _e('Currency & Pricing', 'ensemble'); ?></h3>
        
        <div class="es-field-row">
            <div class="es-field es-field--half">
                <label><?php _e('Currency', 'ensemble'); ?></label>
                <select name="currency" class="es-select">
                    <?php foreach ($currencies as $code => $label): ?>
                    <option value="<?php echo esc_attr($code); ?>" <?php selected($settings['currency'] ?? 'EUR', $code); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="es-field es-field--half">
                <label><?php _e('Price Format', 'ensemble'); ?></label>
                <select name="price_format" class="es-select">
                    <?php foreach ($price_formats as $key => $example): ?>
                    <option value="<?php echo esc_attr($key); ?>" <?php selected($settings['price_format'] ?? 'symbol_before', $key); ?>>
                        <?php echo esc_html($example); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
    
    <!-- Tax Settings -->
    <div class="es-settings-section">
        <h3><?php _e('Tax Settings', 'ensemble'); ?></h3>
        
        <div class="es-setting-row">
            <div class="es-setting-info">
                <span class="es-setting-title"><?php _e('Enable Tax', 'ensemble'); ?></span>
                <span class="es-setting-desc"><?php _e('Calculate tax on ticket purchases', 'ensemble'); ?></span>
            </div>
            <label class="es-toggle">
                <input type="hidden" name="tax_enabled" value="0">
                <input type="checkbox" name="tax_enabled" value="1" <?php checked($settings['tax_enabled'] ?? false); ?>>
                <span class="es-toggle-slider"></span>
            </label>
        </div>
        
        <div class="es-tax-details" style="<?php echo empty($settings['tax_enabled']) ? 'display:none;' : ''; ?>">
            <div class="es-field-row">
                <div class="es-field es-field--half">
                    <label><?php _e('Tax Rate', 'ensemble'); ?></label>
                    <div class="es-input-group">
                        <input type="number" name="tax_rate" 
                               value="<?php echo esc_attr($settings['tax_rate'] ?? 19); ?>" 
                               min="0" max="100" step="0.01" class="es-input-small">
                        <span class="es-input-suffix">%</span>
                    </div>
                </div>
                
                <div class="es-field es-field--half es-field--align-end">
                    <label class="es-checkbox">
                        <input type="hidden" name="tax_included" value="0">
                        <input type="checkbox" name="tax_included" value="1" <?php checked($settings['tax_included'] ?? true); ?>>
                        <span><?php _e('Prices include tax', 'ensemble'); ?></span>
                    </label>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Checkout Settings -->
    <div class="es-settings-section es-settings-section--last">
        <h3><?php _e('Checkout', 'ensemble'); ?></h3>
        
        <div class="es-setting-row">
            <div class="es-setting-info">
                <span class="es-setting-title"><?php _e('Guest Checkout', 'ensemble'); ?></span>
                <span class="es-setting-desc"><?php _e('Allow purchases without login', 'ensemble'); ?></span>
            </div>
            <label class="es-toggle">
                <input type="hidden" name="guest_checkout" value="0">
                <input type="checkbox" name="guest_checkout" value="1" <?php checked($settings['guest_checkout'] ?? true); ?>>
                <span class="es-toggle-slider"></span>
            </label>
        </div>
        
        <div class="es-field-row">
            <div class="es-field es-field--half">
                <label><?php _e('Max Tickets per Order', 'ensemble'); ?></label>
                <input type="number" name="max_tickets_per_order" 
                       value="<?php echo esc_attr($settings['max_tickets_per_order'] ?? 10); ?>" 
                       min="1" max="100" class="es-input-small">
            </div>
            
            <div class="es-field es-field--half">
                <label><?php _e('Cart Hold Time', 'ensemble'); ?></label>
                <div class="es-input-group">
                    <input type="number" name="hold_time" 
                           value="<?php echo esc_attr($settings['hold_time'] ?? 15); ?>" 
                           min="5" max="60" class="es-input-small">
                    <span class="es-input-suffix"><?php _e('min', 'ensemble'); ?></span>
                </div>
            </div>
        </div>
        
        <div class="es-field">
            <label><?php _e('Terms & Conditions Page', 'ensemble'); ?></label>
            <select name="terms_page" class="es-select">
                <option value="0"><?php _e('— None —', 'ensemble'); ?></option>
                <?php foreach ($pages as $page): ?>
                <option value="<?php echo esc_attr($page->ID); ?>" <?php selected($settings['terms_page'] ?? 0, $page->ID); ?>>
                    <?php echo esc_html($page->post_title); ?>
                </option>
                <?php endforeach; ?>
            </select>
            <p class="es-hint"><?php _e('Customers must accept before checkout', 'ensemble'); ?></p>
        </div>
    </div>
    
</div>

<style>
.es-tickets-settings {
    padding: 0;
}

.es-settings-section {
    margin-bottom: var(--es-spacing-lg, 24px);
    padding-bottom: var(--es-spacing-lg, 24px);
    border-bottom: 1px solid var(--es-border, #404040);
}

.es-settings-section--last {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
}

.es-settings-section h3 {
    margin: 0 0 var(--es-spacing-md, 16px) 0;
    font-size: 14px;
    font-weight: 600;
    color: var(--es-text, #e0e0e0);
}

/* Field Layout */
.es-field {
    margin-bottom: var(--es-spacing-md, 16px);
}

.es-field:last-child {
    margin-bottom: 0;
}

.es-field > label {
    display: block;
    margin-bottom: var(--es-spacing-sm, 8px);
    font-size: 13px;
    font-weight: 500;
    color: var(--es-text, #e0e0e0);
}

.es-field-row {
    display: flex;
    gap: var(--es-spacing-md, 16px);
    margin-bottom: var(--es-spacing-md, 16px);
}

.es-field-row:last-child {
    margin-bottom: 0;
}

.es-field--half {
    flex: 1;
}

.es-field--align-end {
    display: flex;
    align-items: flex-end;
    padding-bottom: 4px;
}

/* Inputs */
.es-select,
.es-input-small {
    width: 100%;
    padding: 10px 12px;
    background: var(--es-background, #1e1e1e);
    border: 1px solid var(--es-border, #404040);
    border-radius: var(--es-radius, 6px);
    color: var(--es-text, #e0e0e0);
    font-size: 14px;
}

.es-select:focus,
.es-input-small:focus {
    border-color: var(--es-primary, #3582c4);
    outline: none;
}

.es-input-group {
    display: flex;
    align-items: center;
    gap: var(--es-spacing-sm, 8px);
}

.es-input-group .es-input-small {
    flex: 1;
    max-width: 100px;
}

.es-input-suffix {
    font-size: 14px;
    color: var(--es-text-secondary, #a0a0a0);
}

.es-hint {
    margin: 6px 0 0;
    font-size: 12px;
    color: var(--es-text-secondary, #a0a0a0);
}

/* Setting Rows (for toggles) */
.es-setting-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 12px 0;
    border-bottom: 1px solid var(--es-border, #404040);
}

.es-setting-row:last-child {
    border-bottom: none;
}

.es-setting-info {
    flex: 1;
    padding-right: var(--es-spacing-md, 16px);
}

.es-setting-title {
    display: block;
    font-size: 13px;
    font-weight: 500;
    color: var(--es-text, #e0e0e0);
    margin-bottom: 2px;
}

.es-setting-desc {
    display: block;
    font-size: 12px;
    color: var(--es-text-secondary, #a0a0a0);
}

/* Toggle Switch - unified pattern */
.es-toggle {
    position: relative;
    display: inline-block;
    width: 44px;
    height: 24px;
    flex-shrink: 0;
}

.es-toggle input {
    opacity: 0;
    width: 0;
    height: 0;
}

.es-toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: var(--es-border, #404040);
    border-radius: 12px;
    transition: var(--es-transition, all 0.2s ease);
}

.es-toggle-slider::before {
    content: '';
    position: absolute;
    height: 20px;
    width: 20px;
    left: 2px;
    bottom: 2px;
    background: white;
    border-radius: 50%;
    transition: var(--es-transition, all 0.2s ease);
}

.es-toggle input:checked + .es-toggle-slider {
    background: var(--es-primary, #3582c4);
}

.es-toggle input:checked + .es-toggle-slider::before {
    transform: translateX(20px);
}

/* Checkbox */
.es-checkbox {
    display: flex;
    align-items: center;
    gap: var(--es-spacing-sm, 8px);
    cursor: pointer;
    font-size: 13px;
    color: var(--es-text, #e0e0e0);
}

.es-checkbox input[type="checkbox"] {
    width: 16px;
    height: 16px;
    accent-color: var(--es-primary, #3582c4);
}

/* Tax Details */
.es-tax-details {
    margin-top: var(--es-spacing-md, 16px);
    padding: var(--es-spacing-md, 16px);
    background: var(--es-surface, #2c2c2c);
    border-radius: var(--es-radius-lg, 8px);
}

/* Responsive */
@media (max-width: 500px) {
    .es-field-row {
        flex-direction: column;
    }
    
    .es-field--align-end {
        padding-bottom: 0;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Toggle tax details visibility
    $('input[name="tax_enabled"]').on('change', function() {
        if ($(this).is(':checked')) {
            $('.es-tax-details').slideDown(200);
        } else {
            $('.es-tax-details').slideUp(200);
        }
    });
});
</script>
