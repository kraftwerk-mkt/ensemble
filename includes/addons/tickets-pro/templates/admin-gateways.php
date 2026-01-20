<?php
/**
 * Tickets Pro Admin - Payment Gateways Tab
 * 
 * Configure payment gateways
 *
 * @package Ensemble
 * @subpackage Addons/TicketsPro/Templates
 * @since 3.1.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$addon = ES_Tickets_Pro();
$gateways = $addon->gateways->get_gateways();
$active_gateway = isset($_GET['gateway']) ? sanitize_key($_GET['gateway']) : '';

// If no gateway specified, show overview
if (empty($active_gateway)) {
    $active_gateway = 'overview';
}
?>

<div class="es-gateway-settings">
    
    <!-- Gateway Tabs -->
    <nav class="es-gateway-tabs">
        <a href="<?php echo esc_url(add_query_arg(array('tab' => 'gateways', 'gateway' => 'overview'))); ?>" 
           class="es-gateway-tab <?php echo $active_gateway === 'overview' ? 'es-active' : ''; ?>">
            <span class="dashicons dashicons-admin-settings"></span>
            <?php _e('Overview', 'ensemble'); ?>
        </a>
        
        <?php foreach ($gateways as $id => $gateway): ?>
        <a href="<?php echo esc_url(add_query_arg(array('tab' => 'gateways', 'gateway' => $id))); ?>" 
           class="es-gateway-tab <?php echo $active_gateway === $id ? 'es-active' : ''; ?>">
            <?php if ($gateway->get_icon()): ?>
            <img src="<?php echo esc_url($gateway->get_icon()); ?>" alt="" class="es-gateway-tab-icon">
            <?php else: ?>
            <span class="dashicons dashicons-money-alt"></span>
            <?php endif; ?>
            <?php echo esc_html($gateway->get_title()); ?>
            <?php if ($gateway->is_enabled()): ?>
            <span class="es-gateway-status"></span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </nav>
    
    <!-- Content -->
    <div class="es-gateway-content">
        
        <?php if ($active_gateway === 'overview'): ?>
        
        <!-- Overview -->
        <h3><?php _e('Payment Gateways Overview', 'ensemble'); ?></h3>
        <p class="description">
            <?php _e('Configure payment gateways to accept payments for tickets. At least one gateway must be enabled and configured.', 'ensemble'); ?>
        </p>
        
        <table class="es-admin-table" style="margin-top: 20px;">
            <thead>
                <tr>
                    <th><?php _e('Gateway', 'ensemble'); ?></th>
                    <th><?php _e('Status', 'ensemble'); ?></th>
                    <th><?php _e('Mode', 'ensemble'); ?></th>
                    <th><?php _e('Actions', 'ensemble'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($gateways as $id => $gateway): ?>
                <tr>
                    <td>
                        <?php if ($gateway->get_icon()): ?>
                        <img src="<?php echo esc_url($gateway->get_icon()); ?>" alt="" style="width: 24px; height: auto; vertical-align: middle; margin-right: 8px;">
                        <?php endif; ?>
                        <strong><?php echo esc_html($gateway->get_title()); ?></strong>
                    </td>
                    <td>
                        <?php if ($gateway->is_enabled()): ?>
                            <?php if ($gateway->is_configured()): ?>
                            <span class="es-badge es-badge-success"><?php _e('Active', 'ensemble'); ?></span>
                            <?php else: ?>
                            <span class="es-badge es-badge-warning"><?php _e('Not Configured', 'ensemble'); ?></span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="es-badge es-badge-default"><?php _e('Disabled', 'ensemble'); ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($gateway->is_test_mode()): ?>
                        <span class="es-badge es-badge-info"><?php _e('Test', 'ensemble'); ?></span>
                        <?php else: ?>
                        <span class="es-badge es-badge-default"><?php _e('Live', 'ensemble'); ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="<?php echo esc_url(add_query_arg(array('tab' => 'gateways', 'gateway' => $id))); ?>" class="button button-small">
                            <?php _e('Configure', 'ensemble'); ?>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php else: ?>
        
        <!-- Individual Gateway Settings -->
        <?php 
        $gateway = $addon->gateways->get_gateway($active_gateway);
        
        if ($gateway):
            $fields = $gateway->get_settings_fields();
            $settings = get_option('ensemble_payment_gateways', array());
            $gateway_settings = isset($settings[$active_gateway]) ? $settings[$active_gateway] : array();
        ?>
        
        <div class="es-gateway-header">
            <?php if ($gateway->get_icon()): ?>
            <img src="<?php echo esc_url($gateway->get_icon()); ?>" alt="" class="es-gateway-header-icon">
            <?php endif; ?>
            <div>
                <h3><?php echo esc_html($gateway->get_title()); ?></h3>
                <p class="description"><?php echo esc_html($gateway->get_description()); ?></p>
            </div>
        </div>
        
        <form method="post" class="es-gateway-form" data-gateway="<?php echo esc_attr($active_gateway); ?>">
            <?php wp_nonce_field('ensemble_save_gateway_' . $active_gateway, 'gateway_nonce'); ?>
            <input type="hidden" name="gateway_id" value="<?php echo esc_attr($active_gateway); ?>">
            
            <table class="form-table">
                <?php foreach ($fields as $key => $field): 
                    $value = isset($gateway_settings[$key]) ? $gateway_settings[$key] : (isset($field['default']) ? $field['default'] : '');
                ?>
                <tr>
                    <th scope="row">
                        <label for="gateway_<?php echo esc_attr($key); ?>">
                            <?php echo esc_html($field['title']); ?>
                        </label>
                    </th>
                    <td>
                        <?php if ($field['type'] === 'checkbox'): ?>
                        <label class="es-toggle">
                            <input type="checkbox" 
                                   id="gateway_<?php echo esc_attr($key); ?>"
                                   name="gateway_settings[<?php echo esc_attr($key); ?>]" 
                                   value="1" 
                                   <?php checked($value); ?>>
                            <span class="es-toggle-track"></span>
                            <?php if (!empty($field['label'])): ?>
                            <span class="es-toggle-label"><?php echo esc_html($field['label']); ?></span>
                            <?php endif; ?>
                        </label>
                        
                        <?php elseif ($field['type'] === 'password'): ?>
                        <input type="password" 
                               id="gateway_<?php echo esc_attr($key); ?>"
                               name="gateway_settings[<?php echo esc_attr($key); ?>]" 
                               value="<?php echo esc_attr($value); ?>"
                               class="regular-text"
                               autocomplete="new-password">
                        
                        <?php else: ?>
                        <input type="<?php echo esc_attr($field['type']); ?>" 
                               id="gateway_<?php echo esc_attr($key); ?>"
                               name="gateway_settings[<?php echo esc_attr($key); ?>]" 
                               value="<?php echo esc_attr($value); ?>"
                               class="regular-text">
                        <?php endif; ?>
                        
                        <?php if (!empty($field['description'])): ?>
                        <p class="description"><?php echo esc_html($field['description']); ?></p>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            
            <!-- Webhook URL -->
            <div class="es-info-box">
                <h4>
                    <span class="dashicons dashicons-admin-links"></span>
                    <?php _e('Webhook URL', 'ensemble'); ?>
                </h4>
                <p><?php _e('Configure this URL in your payment gateway dashboard to receive payment notifications:', 'ensemble'); ?></p>
                <code><?php echo esc_html($gateway->get_webhook_url()); ?></code>
                <button type="button" class="button button-small es-copy-btn" data-copy="<?php echo esc_attr($gateway->get_webhook_url()); ?>">
                    <span class="dashicons dashicons-clipboard"></span>
                    <?php _e('Copy', 'ensemble'); ?>
                </button>
            </div>
            
            <p class="submit">
                <button type="submit" class="button button-primary">
                    <?php _e('Save Settings', 'ensemble'); ?>
                </button>
                
                <?php if ($gateway->is_enabled() && $gateway->is_configured()): ?>
                <button type="button" class="button es-test-gateway" data-gateway="<?php echo esc_attr($active_gateway); ?>">
                    <?php _e('Test Connection', 'ensemble'); ?>
                </button>
                <?php endif; ?>
            </p>
        </form>
        
        <?php else: ?>
        <p><?php _e('Gateway not found.', 'ensemble'); ?></p>
        <?php endif; ?>
        
        <?php endif; ?>
        
    </div>
    
</div>

<script>
jQuery(function($) {
    // Copy button
    $('.es-copy-btn').on('click', function() {
        var text = $(this).data('copy');
        navigator.clipboard.writeText(text).then(function() {
            alert(esTicketsPro.i18n.copied);
        });
    });
    
    // Test gateway
    $('.es-test-gateway').on('click', function() {
        var $btn = $(this);
        var gateway = $btn.data('gateway');
        
        $btn.prop('disabled', true).text(esTicketsPro.i18n.testing);
        
        $.ajax({
            url: esTicketsPro.ajaxurl,
            type: 'POST',
            data: {
                action: 'es_test_payment_gateway',
                gateway: gateway,
                nonce: esTicketsPro.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(esTicketsPro.i18n.testSuccess);
                } else {
                    alert(esTicketsPro.i18n.testFailed + ' ' + response.data.message);
                }
            },
            error: function() {
                alert(esTicketsPro.i18n.testFailed);
            },
            complete: function() {
                $btn.prop('disabled', false).text('<?php _e('Test Connection', 'ensemble'); ?>');
            }
        });
    });
    
    // Save gateway settings
    $('.es-gateway-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $btn = $form.find('.button-primary');
        
        $btn.prop('disabled', true).text(esTicketsPro.i18n.saving);
        
        $.ajax({
            url: esTicketsPro.ajaxurl,
            type: 'POST',
            data: $form.serialize() + '&action=es_save_payment_gateway',
            success: function(response) {
                if (response.success) {
                    $form.before('<div class="notice notice-success is-dismissible"><p>' + esTicketsPro.i18n.saved + '</p></div>');
                } else {
                    $form.before('<div class="notice notice-error is-dismissible"><p>' + response.data.message + '</p></div>');
                }
            },
            error: function() {
                $form.before('<div class="notice notice-error is-dismissible"><p>Error saving settings.</p></div>');
            },
            complete: function() {
                $btn.prop('disabled', false).text('<?php _e('Save Settings', 'ensemble'); ?>');
            }
        });
    });
});
</script>
