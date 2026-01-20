<?php
/**
 * Tickets Pro Admin - Overview Tab
 * 
 * Dashboard overview with stats and quick actions
 * Uses unified admin CSS classes for consistent styling
 *
 * @package Ensemble
 * @subpackage Addons/TicketsPro/Templates
 * @since 3.1.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Get stats
$bookings_table = $wpdb->prefix . 'ensemble_bookings';
$categories_table = $wpdb->prefix . 'ensemble_ticket_categories';

$total_revenue = $wpdb->get_var(
    "SELECT SUM(price) FROM $bookings_table WHERE booking_type = 'ticket' AND payment_status = 'paid'"
) ?: 0;

$total_tickets = $wpdb->get_var(
    "SELECT COUNT(*) FROM $bookings_table WHERE booking_type = 'ticket' AND payment_status = 'paid'"
) ?: 0;

$pending_payments = $wpdb->get_var(
    "SELECT COUNT(*) FROM $bookings_table WHERE booking_type = 'ticket' AND payment_status = 'pending'"
) ?: 0;

$active_categories = $wpdb->get_var(
    "SELECT COUNT(*) FROM $categories_table WHERE status = 'active'"
) ?: 0;

// Get available gateways
$addon = ES_Tickets_Pro();
$available_gateways = $addon->get_available_gateways();
$all_gateways = $addon->gateways ? $addon->gateways->get_gateways() : array();
?>

<!-- Stats Row -->
<div class="es-stats-row">
    
    <!-- Revenue Card -->
    <div class="es-stat-card">
        <span class="es-stat-icon" style="background: var(--es-success);">
            <span class="dashicons dashicons-chart-bar"></span>
        </span>
        <div class="es-stat-content">
            <h3>€<?php echo number_format($total_revenue, 2, ',', '.'); ?></h3>
            <p><?php _e('Total Revenue', 'ensemble'); ?></p>
        </div>
    </div>
    
    <!-- Tickets Sold Card -->
    <div class="es-stat-card">
        <span class="es-stat-icon" style="background: var(--es-primary);">
            <span class="dashicons dashicons-tickets-alt"></span>
        </span>
        <div class="es-stat-content">
            <h3><?php echo number_format($total_tickets); ?></h3>
            <p><?php _e('Tickets Sold', 'ensemble'); ?></p>
        </div>
    </div>
    
    <!-- Pending Payments Card -->
    <div class="es-stat-card">
        <span class="es-stat-icon" style="background: var(--es-warning);">
            <span class="dashicons dashicons-clock"></span>
        </span>
        <div class="es-stat-content">
            <h3><?php echo number_format($pending_payments); ?></h3>
            <p><?php _e('Pending Payments', 'ensemble'); ?></p>
        </div>
    </div>
    
    <!-- Active Categories Card -->
    <div class="es-stat-card">
        <span class="es-stat-icon" style="background: var(--es-info);">
            <span class="dashicons dashicons-category"></span>
        </span>
        <div class="es-stat-content">
            <h3><?php echo number_format($active_categories); ?></h3>
            <p><?php _e('Ticket Templates', 'ensemble'); ?></p>
        </div>
    </div>
    
</div>

<!-- Payment Gateways Status -->
<div class="es-manager-container" style="margin-bottom: 20px;">
    <div class="es-section-header" style="padding: 15px 20px; margin: 0; border-bottom: 1px solid var(--es-border);">
        <h3 class="es-section-title" style="margin: 0;"><?php _e('Payment Gateways', 'ensemble'); ?></h3>
    </div>
    
    <?php if (empty($all_gateways)): ?>
    <div style="padding: 30px; text-align: center; color: var(--es-text-secondary);">
        <span class="dashicons dashicons-warning" style="font-size: 32px; margin-bottom: 10px; display: block; color: var(--es-warning);"></span>
        <p><?php _e('No payment gateways available.', 'ensemble'); ?></p>
    </div>
    <?php elseif (empty($available_gateways)): ?>
    <div style="padding: 20px;">
        <div class="es-notice es-notice-warning" style="background: var(--es-warning-light); border-left: 4px solid var(--es-warning); padding: 15px; border-radius: 4px;">
            <p style="margin: 0 0 10px;">
                <strong><?php _e('No Payment Gateway Configured', 'ensemble'); ?></strong><br>
                <?php _e('Configure at least one payment gateway to start selling tickets.', 'ensemble'); ?>
            </p>
            <a href="<?php echo esc_url(add_query_arg('tab', 'gateways')); ?>" class="button button-primary">
                <?php _e('Configure Gateways', 'ensemble'); ?>
            </a>
        </div>
    </div>
    <?php else: ?>
    <div class="es-table-container">
        <table class="es-data-table">
            <thead>
                <tr>
                    <th><?php _e('Gateway', 'ensemble'); ?></th>
                    <th><?php _e('Status', 'ensemble'); ?></th>
                    <th><?php _e('Mode', 'ensemble'); ?></th>
                    <th><?php _e('Actions', 'ensemble'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($all_gateways as $id => $gateway): ?>
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
                            <span class="es-status es-status-success"><?php _e('Active', 'ensemble'); ?></span>
                            <?php else: ?>
                            <span class="es-status es-status-warning"><?php _e('Not Configured', 'ensemble'); ?></span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="es-status es-status-default"><?php _e('Disabled', 'ensemble'); ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($gateway->is_test_mode()): ?>
                        <span class="es-status es-status-info"><?php _e('Test', 'ensemble'); ?></span>
                        <?php else: ?>
                        <span class="es-status es-status-default"><?php _e('Live', 'ensemble'); ?></span>
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
    </div>
    <?php endif; ?>
</div>

<!-- Quick Links -->
<div class="es-manager-container">
    <div class="es-section-header" style="padding: 15px 20px; margin: 0; border-bottom: 1px solid var(--es-border);">
        <h3 class="es-section-title" style="margin: 0;"><?php _e('Quick Links', 'ensemble'); ?></h3>
    </div>
    <div style="padding: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
        <a href="<?php echo admin_url('admin.php?page=ensemble-bookings'); ?>" class="button">
            <span class="dashicons dashicons-list-view" style="vertical-align: middle; margin-right: 5px;"></span>
            <?php _e('View All Bookings', 'ensemble'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=ensemble-events'); ?>" class="button">
            <span class="dashicons dashicons-calendar-alt" style="vertical-align: middle; margin-right: 5px;"></span>
            <?php _e('Manage Events', 'ensemble'); ?>
        </a>
        <a href="<?php echo esc_url(add_query_arg('tab', 'gateways')); ?>" class="button">
            <span class="dashicons dashicons-money-alt" style="vertical-align: middle; margin-right: 5px;"></span>
            <?php _e('Gateway Settings', 'ensemble'); ?>
        </a>
        <a href="<?php echo esc_url(add_query_arg('tab', 'categories')); ?>" class="button">
            <span class="dashicons dashicons-tickets-alt" style="vertical-align: middle; margin-right: 5px;"></span>
            <?php _e('Ticket Templates', 'ensemble'); ?>
        </a>
    </div>
</div>
