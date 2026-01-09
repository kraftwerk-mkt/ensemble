<?php
/**
 * Tickets Frontend Widget Template
 * 
 * @package Ensemble
 * @subpackage Addons/Tickets
 * 
 * Variables available:
 * @var array $tickets Array of ticket data
 * @var int $event_id Event ID
 * @var array $settings Display settings
 * @var ES_Tickets_Addon $addon Addon instance
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

if (empty($tickets)) {
    return;
}

$layout = isset($settings['layout']) ? $settings['layout'] : 'list';
$layout_class = 'es-tickets-' . sanitize_html_class($layout);
$widget_title = isset($settings['widget_title']) ? $settings['widget_title'] : __('Tickets', 'ensemble');
$button_text = isset($settings['button_text']) ? $settings['button_text'] : __('Buy Tickets', 'ensemble');
$show_provider = isset($settings['show_provider_logo']) ? $settings['show_provider_logo'] : true;
?>

<?php
// Check header display setting
$show_header = !function_exists('ensemble_show_addon_header') || ensemble_show_addon_header('tickets');
?>

<div class="es-tickets-widget <?php echo esc_attr($layout_class); ?>">
    <?php if ($show_header): ?>
    <div class="es-tickets-header">
        <span class="es-tickets-header-icon">
            <?php if (class_exists('ES_Icons')): ?>
                <?php ES_Icons::icon('ticket'); ?>
            <?php endif; ?>
        </span>
        <h3 class="es-tickets-title"><?php echo esc_html($widget_title); ?></h3>
    </div>
    <?php endif; ?>
    
    <ul class="es-tickets-list">
        <?php foreach ($tickets as $ticket): 
            $provider = $addon->get_providers()[$ticket['provider']] ?? array('name' => $ticket['provider']);
            $statuses = $addon->get_availability_statuses();
            $status = $statuses[$ticket['availability']] ?? array('label' => $ticket['availability']);
            $status_label = isset($status['label']) ? $status['label'] : (isset($status['name']) ? $status['name'] : $ticket['availability']);
            
            $display_name = !empty($ticket['name']) ? $ticket['name'] : $provider['name'];
            $ticket_url = $addon->build_ticket_url($ticket, $event_id);
            $is_available = !in_array($ticket['availability'], array('sold_out', 'cancelled'));
            $is_free = floatval($ticket['price']) <= 0 && (empty($ticket['price_max']) || floatval($ticket['price_max']) <= 0);
            $is_global = !empty($ticket['is_global']);
            $custom_text = !empty($ticket['custom_text']) ? $ticket['custom_text'] : '';
            $show_badge = !in_array($ticket['availability'], array('available'));
        ?>
        
        <li class="es-ticket-item <?php echo esc_attr($status['class'] ?? ''); ?>">
            <div class="es-ticket-info">
                <h4 class="es-ticket-name">
                    <?php echo esc_html($display_name); ?>
                    <?php if ($is_global): ?>
                    <span class="es-ticket-global-badge" title="<?php esc_attr_e('Global Ticket', 'ensemble'); ?>"></span>
                    <?php endif; ?>
                </h4>
                
                <?php if ($custom_text): ?>
                <span class="es-ticket-custom-text"><?php echo esc_html($custom_text); ?></span>
                <?php endif; ?>
                
                <?php if ($show_provider && $ticket['provider'] !== 'custom'): ?>
                <span class="es-ticket-provider">
                    via <?php echo esc_html($provider['name']); ?>
                </span>
                <?php endif; ?>
            </div>
            
            <div class="es-ticket-price">
                <span class="es-ticket-price-value <?php echo $is_free ? 'es-ticket-price-free' : ''; ?>">
                    <?php echo esc_html($addon->format_price($ticket['price'], $ticket['currency'] ?? 'EUR', $ticket['price_max'] ?? 0)); ?>
                </span>
                
                <?php if ($show_badge): ?>
                <span class="es-ticket-availability es-ticket-availability--<?php echo esc_attr($ticket['availability']); ?>">
                    <?php echo esc_html($status_label); ?>
                </span>
                <?php endif; ?>
            </div>
            
            <div class="es-ticket-action">
                <?php if (!empty($ticket_url)): ?>
                <a href="<?php echo esc_url($ticket_url); ?>" 
                   class="es-ticket-button <?php echo !$is_available ? 'es-ticket-button--disabled' : ''; ?>"
                   <?php echo $is_available ? 'target="_blank" rel="noopener"' : ''; ?>
                   data-ticket-id="<?php echo esc_attr($ticket['id'] ?? ''); ?>"
                   data-event-id="<?php echo esc_attr($event_id); ?>"
                   <?php echo !$is_available ? 'aria-disabled="true" onclick="return false;"' : ''; ?>>
                    <?php if (class_exists('ES_Icons')): ?>
                        <?php ES_Icons::icon('external'); ?>
                    <?php endif; ?>
                    <?php 
                    if ($ticket['availability'] === 'sold_out') {
                        _e('Sold Out', 'ensemble');
                    } elseif ($ticket['availability'] === 'cancelled') {
                        _e('Cancelled', 'ensemble');
                    } else {
                        echo esc_html($button_text);
                    }
                    ?>
                </a>
                <?php endif; ?>
            </div>
        </li>
        
        <?php endforeach; ?>
    </ul>
</div>
