<?php
/**
 * Tickets Pro Admin - Settings Tab
 * 
 * General addon settings
 *
 * @package Ensemble
 * @subpackage Addons/TicketsPro/Templates
 * @since 3.1.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$settings = get_option('ensemble_tickets_pro_settings', array());
$settings = wp_parse_args($settings, array(
    'currency'              => 'EUR',
    'price_format'          => 'symbol_before',
    'tax_enabled'           => false,
    'tax_rate'              => 19,
    'tax_included'          => true,
    'require_login'         => false,
    'guest_checkout'        => true,
    'max_tickets_per_order' => 10,
    'hold_time'             => 15,
    'terms_page'            => 0,
));

$currencies = array(
    'EUR' => __('Euro (€)', 'ensemble'),
    'USD' => __('US Dollar ($)', 'ensemble'),
    'GBP' => __('British Pound (£)', 'ensemble'),
    'CHF' => __('Swiss Franc (CHF)', 'ensemble'),
);

$pages = get_pages();
?>

<form method="post" class="es-settings-form">
    <?php wp_nonce_field('ensemble_tickets_pro_settings', 'settings_nonce'); ?>
    
    <h3><?php _e('Currency & Pricing', 'ensemble'); ?></h3>
    
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="currency"><?php _e('Currency', 'ensemble'); ?></label>
            </th>
            <td>
                <select name="currency" id="currency">
                    <?php foreach ($currencies as $code => $label): ?>
                    <option value="<?php echo esc_attr($code); ?>" <?php selected($settings['currency'], $code); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="price_format"><?php _e('Price Format', 'ensemble'); ?></label>
            </th>
            <td>
                <select name="price_format" id="price_format">
                    <option value="symbol_before" <?php selected($settings['price_format'], 'symbol_before'); ?>>
                        €19,99
                    </option>
                    <option value="symbol_after" <?php selected($settings['price_format'], 'symbol_after'); ?>>
                        19,99 €
                    </option>
                    <option value="code_before" <?php selected($settings['price_format'], 'code_before'); ?>>
                        EUR 19,99
                    </option>
                </select>
            </td>
        </tr>
    </table>
    
    <h3><?php _e('Tax Settings', 'ensemble'); ?></h3>
    
    <table class="form-table">
        <tr>
            <th scope="row"><?php _e('Enable Tax', 'ensemble'); ?></th>
            <td>
                <label class="es-toggle">
                    <input type="checkbox" name="tax_enabled" value="1" <?php checked($settings['tax_enabled']); ?>>
                    <span class="es-toggle-track"></span>
                    <span class="es-toggle-label"><?php _e('Calculate tax on tickets', 'ensemble'); ?></span>
                </label>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="tax_rate"><?php _e('Tax Rate', 'ensemble'); ?></label>
            </th>
            <td>
                <input type="number" name="tax_rate" id="tax_rate" 
                       value="<?php echo esc_attr($settings['tax_rate']); ?>" 
                       min="0" max="100" step="0.01" class="small-text">
                <span>%</span>
            </td>
        </tr>
        
        <tr>
            <th scope="row"><?php _e('Tax Display', 'ensemble'); ?></th>
            <td>
                <label class="es-toggle">
                    <input type="checkbox" name="tax_included" value="1" <?php checked($settings['tax_included']); ?>>
                    <span class="es-toggle-track"></span>
                    <span class="es-toggle-label"><?php _e('Prices include tax', 'ensemble'); ?></span>
                </label>
            </td>
        </tr>
    </table>
    
    <h3><?php _e('Checkout Settings', 'ensemble'); ?></h3>
    
    <table class="form-table">
        <tr>
            <th scope="row"><?php _e('Guest Checkout', 'ensemble'); ?></th>
            <td>
                <label class="es-toggle">
                    <input type="checkbox" name="guest_checkout" value="1" <?php checked($settings['guest_checkout']); ?>>
                    <span class="es-toggle-track"></span>
                    <span class="es-toggle-label"><?php _e('Allow checkout without login', 'ensemble'); ?></span>
                </label>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="max_tickets_per_order"><?php _e('Max Tickets per Order', 'ensemble'); ?></label>
            </th>
            <td>
                <input type="number" name="max_tickets_per_order" id="max_tickets_per_order" 
                       value="<?php echo esc_attr($settings['max_tickets_per_order']); ?>" 
                       min="1" max="100" class="small-text">
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="hold_time"><?php _e('Cart Hold Time', 'ensemble'); ?></label>
            </th>
            <td>
                <input type="number" name="hold_time" id="hold_time" 
                       value="<?php echo esc_attr($settings['hold_time']); ?>" 
                       min="5" max="60" class="small-text">
                <span><?php _e('minutes', 'ensemble'); ?></span>
                <p class="description"><?php _e('How long tickets are held during checkout.', 'ensemble'); ?></p>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="terms_page"><?php _e('Terms & Conditions Page', 'ensemble'); ?></label>
            </th>
            <td>
                <select name="terms_page" id="terms_page">
                    <option value="0"><?php _e('â€” Select â€”', 'ensemble'); ?></option>
                    <?php foreach ($pages as $page): ?>
                    <option value="<?php echo esc_attr($page->ID); ?>" <?php selected($settings['terms_page'], $page->ID); ?>>
                        <?php echo esc_html($page->post_title); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <p class="description"><?php _e('Customers must accept terms before checkout.', 'ensemble'); ?></p>
            </td>
        </tr>
    </table>
    
    <p class="submit">
        <button type="submit" class="button button-primary">
            <?php _e('Save Settings', 'ensemble'); ?>
        </button>
    </p>
</form>

<script>
jQuery(function($) {
    $('.es-settings-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $btn = $form.find('.button-primary');
        
        $btn.prop('disabled', true).text(esTicketsPro.i18n.saving);
        
        $.ajax({
            url: esTicketsPro.ajaxurl,
            type: 'POST',
            data: $form.serialize() + '&action=es_save_tickets_pro_settings',
            success: function(response) {
                if (response.success) {
                    $form.before('<div class="notice notice-success is-dismissible"><p>' + esTicketsPro.i18n.saved + '</p></div>');
                } else {
                    $form.before('<div class="notice notice-error is-dismissible"><p>' + response.data.message + '</p></div>');
                }
            },
            complete: function() {
                $btn.prop('disabled', false).text('<?php _e('Save Settings', 'ensemble'); ?>');
            }
        });
    });
});
</script>
