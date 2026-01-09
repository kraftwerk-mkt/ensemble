<?php
/**
 * Global Tickets Shortcode Template
 * 
 * Displays all global tickets configured in Addon settings (without event context)
 * Uses the same styling as the frontend-widget for consistency.
 * 
 * @package Ensemble
 * @subpackage Addons/Tickets
 * 
 * Variables available:
 * @var array $tickets Array of global ticket data
 * @var string $layout Layout style (list|grid|compact)
 * @var string $style Style variant (default|minimal|cards)
 * @var string $title Optional title
 * @var bool $show_description Whether to show descriptions
 * @var bool $show_price Whether to show prices
 * @var bool $show_status Whether to show availability status
 * @var string $extra_class Additional CSS classes
 * @var ES_Tickets_Addon $addon Addon instance
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

if (empty($tickets)) {
    return;
}

$layout_class = 'es-tickets-' . sanitize_html_class($layout);
$widget_title = !empty($title) ? $title : __('Tickets', 'ensemble');
$button_text = __('Buy Tickets', 'ensemble');
?>

<div class="es-tickets-widget es-tickets-global <?php echo esc_attr($layout_class); ?> <?php echo esc_attr($extra_class); ?>">
    <?php if (!empty($title)): ?>
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
            $ticket_url = !empty($ticket['url']) ? $ticket['url'] : '';
            $is_available = !in_array($ticket['availability'], array('sold_out', 'cancelled'));
            $is_free = floatval($ticket['price']) <= 0 && (empty($ticket['price_max']) || floatval($ticket['price_max']) <= 0);
            $custom_text = !empty($ticket['custom_text']) ? $ticket['custom_text'] : '';
            $show_badge = !in_array($ticket['availability'], array('available'));
        ?>
        
        <li class="es-ticket-item <?php echo esc_attr($status['class'] ?? ''); ?>">
            <div class="es-ticket-info">
                <h4 class="es-ticket-name">
                    <?php echo esc_html($display_name); ?>
                    <span class="es-ticket-global-badge" title="<?php esc_attr_e('Global Ticket', 'ensemble'); ?>"></span>
                </h4>
                
                <?php if ($show_description && !empty($ticket['description'])): ?>
                <span class="es-ticket-description"><?php echo esc_html($ticket['description']); ?></span>
                <?php endif; ?>
                
                <?php if ($custom_text): ?>
                <span class="es-ticket-custom-text"><?php echo esc_html($custom_text); ?></span>
                <?php endif; ?>
                
                <?php if ($ticket['provider'] !== 'custom'): ?>
                <span class="es-ticket-provider">
                    via <?php echo esc_html($provider['name']); ?>
                </span>
                <?php endif; ?>
            </div>
            
            <?php if ($show_price || $show_status): ?>
            <div class="es-ticket-price">
                <?php if ($show_price): ?>
                <span class="es-ticket-price-value <?php echo $is_free ? 'es-ticket-price-free' : ''; ?>">
                    <?php echo esc_html($addon->format_price($ticket['price'], $ticket['currency'] ?? 'EUR', $ticket['price_max'] ?? 0)); ?>
                </span>
                <?php endif; ?>
                
                <?php if ($show_status && $show_badge): ?>
                <span class="es-ticket-availability es-ticket-availability--<?php echo esc_attr($ticket['availability']); ?>">
                    <?php echo esc_html($status_label); ?>
                </span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <div class="es-ticket-action">
                <?php if (!empty($ticket_url)): ?>
                <a href="<?php echo esc_url($ticket_url); ?>" 
                   class="es-ticket-button <?php echo !$is_available ? 'es-ticket-button--disabled' : ''; ?>"
                   <?php echo $is_available ? 'target="_blank" rel="noopener"' : ''; ?>
                   data-ticket-id="<?php echo esc_attr($ticket['id'] ?? ''); ?>"
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
