<?php
/**
 * Tickets Admin Meta Box Template
 * 
 * @package Ensemble
 * @subpackage Addons/Tickets
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$tickets_json = !empty($tickets) ? json_encode($tickets) : '[]';
?>

<div class="es-tickets-admin">
    <input type="hidden" name="ensemble_tickets" id="ensemble_tickets_data" value="<?php echo esc_attr($tickets_json); ?>">
    
    <?php if (empty($tickets)): ?>
    <div class="es-tickets-empty-state">
        <div class="es-tickets-empty-state-icon">
            <?php if (class_exists('ES_Icons')): ?>
                <?php ES_Icons::icon('ticket'); ?>
            <?php endif; ?>
        </div>
        <p><?php _e('No tickets configured. Click "Add Ticket" to create one.', 'ensemble'); ?></p>
    </div>
    <?php endif; ?>
    
    <ul class="es-tickets-admin-list" <?php echo empty($tickets) ? 'style="display:none;"' : ''; ?>>
        <?php foreach ($tickets as $ticket): 
            $addon_instance = ES_Addon_Manager::get_active_addon('tickets');
            $type = $addon_instance ? ($addon_instance->get_ticket_types()[$ticket['type']] ?? array('name' => $ticket['type'])) : array('name' => $ticket['type']);
            $status = $addon_instance ? ($addon_instance->get_availability_statuses()[$ticket['availability']] ?? array('name' => $ticket['availability'])) : array('name' => $ticket['availability']);
            $provider = $addon_instance ? ($addon_instance->get_providers()[$ticket['provider']] ?? array('name' => $ticket['provider'])) : array('name' => $ticket['provider']);
            
            $display_name = !empty($ticket['name']) ? $ticket['name'] : $type['name'];
            $price_display = $ticket['price'] > 0 ? number_format($ticket['price'], 2, ',', '.') . ' ' . ($ticket['currency'] ?? 'EUR') : __('Free', 'ensemble');
            if (!empty($ticket['price_max']) && $ticket['price_max'] > $ticket['price']) {
                $price_display = number_format($ticket['price'], 2, ',', '.') . ' - ' . number_format($ticket['price_max'], 2, ',', '.') . ' ' . ($ticket['currency'] ?? 'EUR');
            }
        ?>
        <li class="es-tickets-admin-item" data-ticket-id="<?php echo esc_attr($ticket['id']); ?>">
            <div class="es-ticket-drag-handle">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="8" y1="6" x2="8" y2="6.01"/>
                    <line x1="8" y1="12" x2="8" y2="12.01"/>
                    <line x1="8" y1="18" x2="8" y2="18.01"/>
                    <line x1="16" y1="6" x2="16" y2="6.01"/>
                    <line x1="16" y1="12" x2="16" y2="12.01"/>
                    <line x1="16" y1="18" x2="16" y2="18.01"/>
                </svg>
            </div>
            
            <div class="es-ticket-admin-info">
                <h4 class="es-ticket-admin-name">
                    <?php echo esc_html($display_name); ?>
                    <span class="es-ticket-admin-type"><?php echo esc_html($type['name']); ?></span>
                </h4>
                <div class="es-ticket-admin-meta">
                    <span class="es-ticket-admin-meta-item">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                            <polyline points="15 3 21 3 21 9"/>
                            <line x1="10" y1="14" x2="21" y2="3"/>
                        </svg>
                        <?php echo esc_html($provider['name']); ?>
                    </span>
                    <span class="es-ticket-admin-status es-ticket-admin-status--<?php echo esc_attr($ticket['availability']); ?>">
                        <?php echo esc_html($status['name']); ?>
                    </span>
                </div>
            </div>
            
            <div class="es-ticket-admin-price"><?php echo esc_html($price_display); ?></div>
            
            <div class="es-ticket-admin-actions">
                <button type="button" class="es-ticket-admin-action es-ticket-admin-action--edit" title="<?php esc_attr_e('Edit', 'ensemble'); ?>">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 20h9"/>
                        <path d="M16.5 3.5l3 3L7 19l-4 1 1-4z"/>
                    </svg>
                </button>
                <button type="button" class="es-ticket-admin-action es-ticket-admin-action--delete" title="<?php esc_attr_e('Delete', 'ensemble'); ?>">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 7h16"/>
                        <path d="M10 4h4"/>
                        <path d="M6 7l1 13h10l1-13"/>
                        <line x1="10" y1="11" x2="10" y2="17"/>
                        <line x1="14" y1="11" x2="14" y2="17"/>
                    </svg>
                </button>
            </div>
        </li>
        <?php endforeach; ?>
    </ul>
    
    <div class="es-tickets-admin-footer">
        <button type="button" class="es-add-ticket-button">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"/>
                <line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            <?php _e('Add Ticket', 'ensemble'); ?>
        </button>
    </div>
</div>
