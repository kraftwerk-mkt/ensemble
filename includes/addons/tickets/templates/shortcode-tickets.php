<?php
/**
 * Tickets Shortcode Template
 * 
 * @package Ensemble
 * @subpackage Addons/Tickets
 * 
 * Variables available:
 * @var array $tickets Array of ticket data
 * @var int $event_id Event ID
 * @var string $layout Layout style (list|grid|compact)
 * @var string $style Style variant
 * @var ES_Tickets_Addon $addon Addon instance
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

if (empty($tickets)) {
    return '';
}

$wrapper_classes = array(
    'es-tickets-widget',
    'es-tickets-shortcode',
    'es-tickets-' . sanitize_html_class($layout),
    'es-tickets-style-' . sanitize_html_class($style),
);
?>

<div class="<?php echo esc_attr(implode(' ', $wrapper_classes)); ?>">
    
    <?php if ($layout === 'grid'): ?>
    <div class="es-tickets-grid">
    <?php elseif ($layout === 'compact'): ?>
    <ul class="es-tickets-list es-tickets-compact">
    <?php else: ?>
    <ul class="es-tickets-list">
    <?php endif; ?>
    
        <?php foreach ($tickets as $ticket): 
            $type = $addon->get_ticket_types()[$ticket['type']] ?? array('name' => $ticket['type'], 'icon' => 'ticket');
            $status = $addon->get_availability_statuses()[$ticket['availability']] ?? array('name' => $ticket['availability']);
            $provider = $addon->get_providers()[$ticket['provider']] ?? array('name' => $ticket['provider']);
            
            $display_name = !empty($ticket['name']) ? $ticket['name'] : $type['name'];
            $ticket_url = $addon->build_ticket_url($ticket, $event_id);
            $is_available = $ticket['availability'] !== 'sold_out';
        ?>
        
        <?php if ($layout === 'grid'): ?>
        <div class="es-ticket-item">
        <?php else: ?>
        <li class="es-ticket-item">
        <?php endif; ?>
        
            <div class="es-ticket-info">
                <h4 class="es-ticket-name"><?php echo esc_html($display_name); ?></h4>
                <?php if (!empty($ticket['description']) && $layout !== 'compact'): ?>
                <p class="es-ticket-description"><?php echo esc_html($ticket['description']); ?></p>
                <?php endif; ?>
            </div>
            
            <div class="es-ticket-price">
                <span class="es-ticket-price-value">
                    <?php echo esc_html($addon->format_price($ticket['price'], $ticket['currency'], $ticket['price_max'] ?? 0)); ?>
                </span>
                <span class="es-ticket-availability es-ticket-availability--<?php echo esc_attr($ticket['availability']); ?>">
                    <?php echo esc_html($status['name']); ?>
                </span>
            </div>
            
            <?php if (!empty($ticket_url)): ?>
            <div class="es-ticket-action">
                <a href="<?php echo esc_url($ticket_url); ?>" 
                   class="es-ticket-button es-ticket-button--primary <?php echo !$is_available ? 'es-ticket-button--disabled' : ''; ?>"
                   target="_blank" 
                   rel="noopener"
                   data-ticket-id="<?php echo esc_attr($ticket['id']); ?>"
                   data-event-id="<?php echo esc_attr($event_id); ?>">
                    <?php echo $is_available ? esc_html__('Get Tickets', 'ensemble') : esc_html__('Sold Out', 'ensemble'); ?>
                </a>
            </div>
            <?php endif; ?>
            
        <?php if ($layout === 'grid'): ?>
        </div>
        <?php else: ?>
        </li>
        <?php endif; ?>
        
        <?php endforeach; ?>
    
    <?php if ($layout === 'grid'): ?>
    </div>
    <?php else: ?>
    </ul>
    <?php endif; ?>
    
</div>
