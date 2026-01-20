<?php
/**
 * Tickets Pro - Frontend Ticket Form
 * 
 * Displays both paid tickets (with checkout) and external ticket links.
 * Used via shortcode [ensemble_ticket_form] or [es_tickets]
 *
 * @package Ensemble
 * @subpackage Addons/TicketsPro/Templates
 * @since 3.1.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Debug mode
$debug = defined('WP_DEBUG') && WP_DEBUG;

// Get event
$event = get_post($event_id);
$event_post_type = function_exists('ensemble_get_post_type') ? ensemble_get_post_type() : 'ensemble_event';

if (!$event || $event->post_type !== $event_post_type) {
    if ($debug) {
        echo '<!-- Tickets Pro: Invalid event (id=' . $event_id . ') or wrong post type (expected=' . $event_post_type . ', got=' . ($event ? $event->post_type : 'null') . ') -->';
    }
    return;
}

// Get paid tickets from database
$paid_tickets = array();
if (class_exists('ES_Ticket_Category')) {
    $paid_tickets = ES_Ticket_Category::get_by_event($event_id, true);
    if ($debug) {
        echo '<!-- Tickets Pro: ES_Ticket_Category found, query returned ' . count($paid_tickets) . ' tickets -->';
    }
} else {
    if ($debug) {
        echo '<!-- Tickets Pro: ES_Ticket_Category class not found! -->';
    }
}

// Get external tickets from post meta
$external_tickets = get_post_meta($event_id, '_external_tickets', true);
if (!is_array($external_tickets)) {
    $external_tickets = array();
}
if ($debug) {
    echo '<!-- Tickets Pro: External tickets from meta: ' . count($external_tickets) . ' -->';
}

// Check if we have any tickets to show
$has_paid = !empty($paid_tickets);
$has_external = !empty($external_tickets);

// If no tickets at all, show message
if (!$has_paid && !$has_external) {
    echo '<p class="es-no-tickets">' . esc_html__('No tickets available for this event.', 'ensemble') . '</p>';
    return;
}

// Get addon instance and settings
$addon = function_exists('ES_Tickets_Pro') ? ES_Tickets_Pro() : null;
$settings = get_option('ensemble_tickets_pro_settings', array());
$settings = wp_parse_args($settings, array(
    'currency'              => 'EUR',
    'guest_checkout'        => true,
    'max_tickets_per_order' => 10,
    'terms_page'            => 0,
));

// Get available payment gateways (only needed for paid tickets)
$gateways = array();
if ($has_paid && $addon) {
    $gateways = $addon->get_available_gateways();
}

// Currency symbols
$currency_symbols = array(
    'EUR' => '€',
    'USD' => '$',
    'GBP' => '£',
    'CHF' => 'CHF',
);
$currency_symbol = isset($currency_symbols[$settings['currency']]) ? $currency_symbols[$settings['currency']] : $settings['currency'];

// Check if user is logged in
$current_user = wp_get_current_user();
$is_logged_in = is_user_logged_in();

// Provider info for external tickets
$providers = array(
    'eventbrite' => array('name' => 'Eventbrite', 'color' => '#f05537', 'icon' => 'ticket'),
    'resident_advisor' => array('name' => 'Resident Advisor', 'color' => '#0a0a0a', 'icon' => 'ticket'),
    'eventim' => array('name' => 'Eventim', 'color' => '#003d7c', 'icon' => 'ticket'),
    'ticketmaster' => array('name' => 'Ticketmaster', 'color' => '#026cdf', 'icon' => 'ticket'),
    'dice' => array('name' => 'DICE', 'color' => '#000000', 'icon' => 'ticket'),
    'reservix' => array('name' => 'Reservix', 'color' => '#e30613', 'icon' => 'ticket'),
    'tickets_io' => array('name' => 'tickets.io', 'color' => '#00a8e8', 'icon' => 'ticket'),
    'custom' => array('name' => __('Tickets', 'ensemble'), 'color' => '#6b7280', 'icon' => 'ticket'),
);

// Status labels for external tickets
$status_labels = array(
    'available' => __('Available', 'ensemble'),
    'limited'   => __('Limited', 'ensemble'),
    'few_left'  => __('Few Tickets Left', 'ensemble'),
    'presale'   => __('Presale', 'ensemble'),
    'sold_out'  => __('Sold Out', 'ensemble'),
    'cancelled' => __('Cancelled', 'ensemble'),
);
?>

<div class="es-tickets-container" data-event-id="<?php echo esc_attr($event_id); ?>">
    
    <?php if ($has_external): ?>
    <!-- External Ticket Links -->
    <div class="es-external-tickets">
        <?php if ($has_paid): ?>
        <h3 class="es-tickets-section-title"><?php esc_html_e('Get Tickets', 'ensemble'); ?></h3>
        <?php endif; ?>
        
        <div class="es-external-tickets-list">
            <?php foreach ($external_tickets as $ticket): 
                $provider_key = $ticket['provider'] ?? 'custom';
                $provider = isset($providers[$provider_key]) ? $providers[$provider_key] : $providers['custom'];
                $status = $ticket['availability'] ?? 'available';
                $is_sold_out = $status === 'sold_out';
                $is_cancelled = $status === 'cancelled';
                $is_disabled = $is_sold_out || $is_cancelled;
                
                $name = !empty($ticket['name']) ? $ticket['name'] : $provider['name'];
                $price = floatval($ticket['price'] ?? 0);
                $price_max = floatval($ticket['price_max'] ?? 0);
            ?>
            <a href="<?php echo esc_url($ticket['url'] ?? '#'); ?>" 
               class="es-external-ticket <?php echo $is_disabled ? 'es-disabled' : ''; ?>"
               target="_blank"
               rel="noopener noreferrer"
               style="--provider-color: <?php echo esc_attr($provider['color']); ?>;"
               <?php echo $is_disabled ? 'onclick="return false;"' : ''; ?>>
                
                <div class="es-ext-ticket-info">
                    <span class="es-ext-ticket-name"><?php echo esc_html($name); ?></span>
                    
                    <?php if ($price > 0): ?>
                    <span class="es-ext-ticket-price">
                        <?php 
                        echo esc_html($currency_symbol . number_format($price, 2, ',', '.'));
                        if ($price_max > $price) {
                            echo ' - ' . esc_html($currency_symbol . number_format($price_max, 2, ',', '.'));
                        }
                        ?>
                    </span>
                    <?php endif; ?>
                    
                    <?php if ($status !== 'available' && isset($status_labels[$status])): ?>
                    <span class="es-ext-ticket-status es-status-<?php echo esc_attr($status); ?>">
                        <?php echo esc_html($status_labels[$status]); ?>
                    </span>
                    <?php endif; ?>
                </div>
                
                <span class="es-ext-ticket-provider">
                    <?php echo esc_html($provider['name']); ?>
                    <span class="dashicons dashicons-external"></span>
                </span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($has_paid): ?>
    <?php if (empty($gateways)): ?>
    <!-- No Payment Gateway configured -->
    <div class="es-tickets-notice">
        <span class="dashicons dashicons-info"></span>
        <?php esc_html_e('Online ticket sales are currently unavailable.', 'ensemble'); ?>
    </div>
    <?php else: ?>
    <!-- Paid Tickets with Checkout Form -->
    <div class="es-paid-tickets">
        <?php if ($has_external): ?>
        <h3 class="es-tickets-section-title"><?php esc_html_e('Or Buy Directly', 'ensemble'); ?></h3>
        <?php endif; ?>
        
        <form class="es-ticket-checkout-form" method="post">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('es_tickets_pro_frontend'); ?>">
            <input type="hidden" name="event_id" value="<?php echo esc_attr($event_id); ?>">
            <input type="hidden" name="total" value="0">
            
            <!-- Ticket Selection -->
            <div class="es-ticket-section">
                <h4 class="es-ticket-form-title"><?php esc_html_e('Select Tickets', 'ensemble'); ?></h4>
                
                <div class="es-ticket-list">
                    <?php foreach ($paid_tickets as $category): 
                        $available = $category->get_available_count();
                        $status = $category->get_availability_status();
                        $is_sold_out = $status === 'sold_out';
                        $is_limited = $status === 'limited';
                        $max_qty = $category->max_quantity;
                        
                        // Limit by available if capacity is set
                        if ($available !== null && $available < $max_qty) {
                            $max_qty = $available;
                        }
                        
                        // Limit by settings
                        if ($max_qty > $settings['max_tickets_per_order']) {
                            $max_qty = $settings['max_tickets_per_order'];
                        }
                    ?>
                    <div class="es-ticket-item <?php echo $is_sold_out ? 'es-sold-out' : ''; ?>" 
                         data-category-id="<?php echo esc_attr($category->id); ?>"
                         data-price="<?php echo esc_attr($category->price); ?>"
                         data-name="<?php echo esc_attr($category->name); ?>">
                        
                        <div class="es-ticket-info">
                            <span class="es-ticket-name"><?php echo esc_html($category->name); ?></span>
                            
                            <?php if ($category->description): ?>
                            <span class="es-ticket-description"><?php echo esc_html($category->description); ?></span>
                            <?php endif; ?>
                            
                            <span class="es-ticket-availability <?php echo $is_limited ? 'es-limited' : ''; ?> <?php echo $is_sold_out ? 'es-sold-out' : ''; ?>">
                                <?php if ($is_sold_out): ?>
                                    <?php esc_html_e('Sold Out', 'ensemble'); ?>
                                <?php elseif ($is_limited): ?>
                                    <?php printf(esc_html__('Only %d left!', 'ensemble'), $available); ?>
                                <?php elseif ($available !== null): ?>
                                    <?php printf(esc_html__('%d available', 'ensemble'), $available); ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        
                        <div class="es-ticket-price">
                            <?php echo esc_html($currency_symbol . number_format($category->price, 2, ',', '.')); ?>
                        </div>
                        
                        <?php if (!$is_sold_out): ?>
                        <div class="es-ticket-quantity">
                            <button type="button" class="es-qty-minus" aria-label="<?php esc_attr_e('Decrease quantity', 'ensemble'); ?>">−</button>
                            <input type="number" 
                                   class="es-ticket-qty-input" 
                                   name="tickets[<?php echo esc_attr($category->id); ?>]" 
                                   value="0" 
                                   min="0" 
                                   max="<?php echo esc_attr($max_qty); ?>"
                                   data-category-id="<?php echo esc_attr($category->id); ?>">
                            <button type="button" class="es-qty-plus" aria-label="<?php esc_attr_e('Increase quantity', 'ensemble'); ?>">+</button>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Order Summary (hidden until tickets selected) -->
            <div class="es-ticket-section es-order-summary" style="display: none;">
                <h4 class="es-ticket-form-title"><?php esc_html_e('Order Summary', 'ensemble'); ?></h4>
                <div class="es-order-items"></div>
                <div class="es-order-total">
                    <span><?php esc_html_e('Total:', 'ensemble'); ?></span>
                    <span class="es-total-amount"><?php echo esc_html($currency_symbol); ?>0,00</span>
                </div>
            </div>
            
            <!-- Customer Information (hidden until tickets selected) -->
            <div class="es-ticket-section es-customer-section" style="display: none;">
                <h4 class="es-ticket-form-title"><?php esc_html_e('Your Information', 'ensemble'); ?></h4>
                
                <div class="es-customer-fields">
                    <div class="es-field-row">
                        <label for="es-customer-name" class="es-field-label">
                            <?php esc_html_e('Full Name', 'ensemble'); ?> <span class="es-required">*</span>
                        </label>
                        <input type="text" 
                               id="es-customer-name" 
                               name="customer_name" 
                               class="es-field-input"
                               value="<?php echo $is_logged_in ? esc_attr($current_user->display_name) : ''; ?>"
                               required>
                    </div>
                    
                    <div class="es-field-row">
                        <label for="es-customer-email" class="es-field-label">
                            <?php esc_html_e('Email Address', 'ensemble'); ?> <span class="es-required">*</span>
                        </label>
                        <input type="email" 
                               id="es-customer-email" 
                               name="customer_email" 
                               class="es-field-input"
                               value="<?php echo $is_logged_in ? esc_attr($current_user->user_email) : ''; ?>"
                               required>
                    </div>
                    
                    <div class="es-field-row">
                        <label for="es-customer-phone" class="es-field-label">
                            <?php esc_html_e('Phone Number', 'ensemble'); ?>
                        </label>
                        <input type="tel" 
                               id="es-customer-phone" 
                               name="customer_phone" 
                               class="es-field-input">
                    </div>
                </div>
            </div>
            
            <!-- Payment Method (hidden until tickets selected) -->
            <?php if (count($gateways) > 0): ?>
            <div class="es-ticket-section es-payment-section" style="display: none;">
                <h4 class="es-ticket-form-title"><?php esc_html_e('Payment Method', 'ensemble'); ?></h4>
                
                <div class="es-payment-methods">
                    <?php 
                    $first = true;
                    foreach ($gateways as $gateway_id => $gateway): 
                    ?>
                    <label class="es-payment-option">
                        <input type="radio" name="payment_gateway" value="<?php echo esc_attr($gateway_id); ?>" <?php checked($first); ?>>
                        <span class="es-payment-label">
                            <?php if ($gateway->get_icon()): ?>
                            <img src="<?php echo esc_url($gateway->get_icon()); ?>" alt="" class="es-payment-icon">
                            <?php endif; ?>
                            <span><?php echo esc_html($gateway->get_title()); ?></span>
                        </span>
                    </label>
                    <?php 
                    $first = false;
                    endforeach; 
                    ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Terms & Submit (hidden until tickets selected) -->
            <div class="es-ticket-section es-submit-section" style="display: none;">
                <?php if ($settings['terms_page']): 
                    $terms_url = get_permalink($settings['terms_page']);
                ?>
                <label class="es-terms-checkbox">
                    <input type="checkbox" name="accept_terms" required>
                    <span>
                        <?php printf(
                            esc_html__('I accept the %sterms and conditions%s', 'ensemble'),
                            '<a href="' . esc_url($terms_url) . '" target="_blank">',
                            '</a>'
                        ); ?>
                    </span>
                </label>
                <?php endif; ?>
                
                <button type="submit" class="es-ticket-submit-btn" disabled>
                    <span class="es-btn-text"><?php esc_html_e('Continue to Payment', 'ensemble'); ?></span>
                    <span class="es-btn-loading" style="display: none;">
                        <span class="es-spinner"></span>
                        <?php esc_html_e('Processing...', 'ensemble'); ?>
                    </span>
                </button>
            </div>
        </form>
    </div>
    <?php endif; // has gateways ?>
    <?php endif; // has paid ?>
    
</div>

<style>
/* External Tickets Styles */
.es-external-tickets {
    margin-bottom: 30px;
}

.es-tickets-section-title {
    font-size: 18px;
    font-weight: 600;
    margin: 0 0 15px;
    color: var(--es-text, #1d2327);
}

.es-external-tickets-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.es-external-ticket {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    background: #fff;
    border: 2px solid var(--es-border, #e0e0e0);
    border-radius: 10px;
    text-decoration: none;
    color: var(--es-text, #1d2327);
    transition: all 0.2s ease;
    border-left: 4px solid var(--provider-color, #6b7280);
}

.es-external-ticket:hover:not(.es-disabled) {
    border-color: var(--provider-color, #6b7280);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.es-external-ticket.es-disabled {
    opacity: 0.6;
    cursor: not-allowed;
    background: var(--es-surface-secondary, #f6f7f7);
}

.es-ext-ticket-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.es-ext-ticket-name {
    font-weight: 600;
    font-size: 16px;
}

.es-ext-ticket-price {
    font-size: 14px;
    color: var(--es-text-secondary, #646970);
}

.es-ext-ticket-status {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
}

.es-ext-ticket-status.es-status-limited,
.es-ext-ticket-status.es-status-few_left {
    background: var(--es-warning-light, #fef3cd);
    color: var(--es-warning-dark, #856404);
}

.es-ext-ticket-status.es-status-sold_out,
.es-ext-ticket-status.es-status-cancelled {
    background: var(--es-error-light, #f8d7da);
    color: var(--es-error-dark, #721c24);
}

.es-ext-ticket-status.es-status-presale {
    background: var(--es-info-light, #cce5ff);
    color: var(--es-info-dark, #004085);
}

.es-ext-ticket-provider {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    color: var(--es-text-secondary, #646970);
}

.es-ext-ticket-provider .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

/* Paid Tickets Styles */
.es-paid-tickets {
    background: var(--es-surface, #fff);
    border: 1px solid var(--es-border, #e0e0e0);
    border-radius: 12px;
    padding: 24px;
}

.es-ticket-section {
    margin-bottom: 24px;
}

.es-ticket-section:last-child {
    margin-bottom: 0;
}

.es-ticket-form-title {
    font-size: 16px;
    font-weight: 600;
    margin: 0 0 16px;
    color: var(--es-text, #1d2327);
}

.es-ticket-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.es-ticket-item {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px;
    background: var(--es-surface-secondary, #f9f9f9);
    border-radius: 8px;
    border: 1px solid var(--es-border, #e0e0e0);
}

.es-ticket-item.es-sold-out {
    opacity: 0.6;
}

.es-ticket-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.es-ticket-name {
    font-weight: 600;
    color: var(--es-text, #1d2327);
}

.es-ticket-description {
    font-size: 13px;
    color: var(--es-text-secondary, #646970);
}

.es-ticket-availability {
    font-size: 12px;
    color: var(--es-text-muted, #a7aaad);
}

.es-ticket-availability.es-limited {
    color: var(--es-warning, #f59e0b);
}

.es-ticket-availability.es-sold-out {
    color: var(--es-error, #dc3545);
}

.es-ticket-price {
    font-weight: 700;
    font-size: 18px;
    color: var(--es-text, #1d2327);
    min-width: 80px;
    text-align: right;
}

.es-ticket-quantity {
    display: flex;
    align-items: center;
    gap: 0;
    border: 1px solid var(--es-border, #dcdcde);
    border-radius: 6px;
    overflow: hidden;
}

.es-qty-minus,
.es-qty-plus {
    width: 36px;
    height: 36px;
    border: none;
    background: var(--es-surface, #fff);
    cursor: pointer;
    font-size: 18px;
    color: var(--es-text, #1d2327);
    transition: background 0.2s;
}

.es-qty-minus:hover,
.es-qty-plus:hover {
    background: var(--es-surface-secondary, #f0f0f0);
}

.es-ticket-qty-input {
    width: 50px;
    height: 36px;
    border: none;
    border-left: 1px solid var(--es-border, #dcdcde);
    border-right: 1px solid var(--es-border, #dcdcde);
    text-align: center;
    font-size: 16px;
    font-weight: 600;
    -moz-appearance: textfield;
}

.es-ticket-qty-input::-webkit-outer-spin-button,
.es-ticket-qty-input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

/* Order Summary */
.es-order-summary {
    background: var(--es-surface-secondary, #f9f9f9);
    padding: 16px;
    border-radius: 8px;
}

.es-order-total {
    display: flex;
    justify-content: space-between;
    font-size: 18px;
    font-weight: 700;
    margin-top: 12px;
    padding-top: 12px;
    border-top: 2px solid var(--es-border, #e0e0e0);
}

/* Customer Fields */
.es-customer-fields {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.es-field-row {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.es-field-label {
    font-weight: 500;
    font-size: 14px;
    color: var(--es-text, #1d2327);
}

.es-field-input {
    padding: 10px 12px;
    border: 1px solid var(--es-border, #dcdcde);
    border-radius: 6px;
    font-size: 15px;
}

.es-field-input:focus {
    border-color: var(--es-primary, #2271b1);
    outline: none;
    box-shadow: 0 0 0 2px rgba(34, 113, 177, 0.2);
}

.es-required {
    color: var(--es-error, #dc3545);
}

/* Payment Methods */
.es-payment-methods {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.es-payment-option {
    display: flex;
    align-items: center;
    padding: 12px 16px;
    border: 2px solid var(--es-border, #e0e0e0);
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
}

.es-payment-option:hover {
    border-color: var(--es-primary, #2271b1);
}

.es-payment-option input[type="radio"] {
    margin-right: 12px;
}

.es-payment-option input[type="radio"]:checked + .es-payment-label {
    font-weight: 600;
}

.es-payment-label {
    display: flex;
    align-items: center;
    gap: 10px;
}

.es-payment-icon {
    height: 24px;
    width: auto;
}

/* Submit */
.es-terms-checkbox {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    margin-bottom: 16px;
    font-size: 14px;
}

.es-terms-checkbox input {
    margin-top: 2px;
}

.es-terms-checkbox a {
    color: var(--es-primary, #2271b1);
}

.es-ticket-submit-btn {
    width: 100%;
    padding: 14px 24px;
    background: var(--es-primary, #2271b1);
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}

.es-ticket-submit-btn:hover:not(:disabled) {
    background: var(--es-primary-dark, #135e96);
}

.es-ticket-submit-btn:disabled {
    background: var(--es-text-muted, #a7aaad);
    cursor: not-allowed;
}

.es-spinner {
    display: inline-block;
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255,255,255,0.3);
    border-top-color: #fff;
    border-radius: 50%;
    animation: es-spin 0.8s linear infinite;
    margin-right: 8px;
    vertical-align: middle;
}

@keyframes es-spin {
    to { transform: rotate(360deg); }
}

/* Notice */
.es-tickets-notice {
    padding: 16px;
    background: var(--es-info-light, #e7f3ff);
    border-radius: 8px;
    color: var(--es-info-dark, #0c5460);
    display: flex;
    align-items: center;
    gap: 10px;
}

.es-tickets-notice .dashicons {
    font-size: 20px;
    width: 20px;
    height: 20px;
}

/* Responsive */
@media (max-width: 600px) {
    .es-ticket-item {
        flex-wrap: wrap;
    }
    
    .es-ticket-info {
        width: 100%;
    }
    
    .es-ticket-price {
        order: 1;
    }
    
    .es-ticket-quantity {
        order: 2;
        margin-left: auto;
    }
}

/* Floor Plan integration - highlight animation */
@keyframes es-pulse-highlight {
    0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); }
    70% { box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
    100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
}

.es-ticket-item.es-highlight-pulse {
    animation: es-pulse-highlight 0.8s ease-out 2;
    border-color: var(--es-success, #10b981) !important;
    background: rgba(16, 185, 129, 0.1);
}

.es-ticket-item.es-from-floor-plan {
    border-left: 3px solid var(--es-success, #10b981);
}
</style>

<script>
jQuery(function($) {
    var $form = $('.es-ticket-checkout-form');
    
    // Prevent double initialization
    if ($form.data('es-initialized')) {
        console.log('Tickets Pro: Form already initialized, skipping');
        return;
    }
    $form.data('es-initialized', true);
    
    var $orderSummary = $form.find('.es-order-summary');
    var $customerSection = $form.find('.es-customer-section');
    var $paymentSection = $form.find('.es-payment-section');
    var $submitSection = $form.find('.es-submit-section');
    var $submitBtn = $form.find('.es-ticket-submit-btn');
    var currencySymbol = '<?php echo esc_js($currency_symbol); ?>';
    var ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
    
    // ===========================================
    // FLOOR PLAN INTEGRATION
    // ===========================================
    
    // Listen for floor plan ticket selection
    $(document).on('ensemble:floorplan:ticket-selected', function(e, data) {
        console.log('Tickets Pro: Received floor plan selection', data);
        
        if (!data.ticketCategory || !data.ticketCategory.id) {
            console.warn('Tickets Pro: No ticket category in floor plan selection');
            return;
        }
        
        var categoryId = data.ticketCategory.id;
        var $ticketItem = $form.find('.es-ticket-item[data-category-id="' + categoryId + '"]');
        
        if ($ticketItem.length) {
            // Mark as from floor plan
            $ticketItem.addClass('es-from-floor-plan');
            
            // Set quantity to 1 if currently 0
            var $qtyInput = $ticketItem.find('.es-ticket-qty-input');
            if ($qtyInput.length && parseInt($qtyInput.val()) === 0) {
                $qtyInput.val(1).trigger('change');
            }
            
            // Visual highlight
            $ticketItem.addClass('es-highlight-pulse');
            setTimeout(function() {
                $ticketItem.removeClass('es-highlight-pulse');
            }, 2000);
        }
    });
    
    // ===========================================
    // QUANTITY BUTTONS
    // ===========================================
    
    // Quantity buttons - use namespaced events and prevent double-binding
    $form.off('click.estickets').on('click.estickets', '.es-qty-minus', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var $input = $(this).siblings('.es-ticket-qty-input');
        var val = parseInt($input.val()) || 0;
        if (val > 0) {
            $input.val(val - 1).trigger('change');
        }
    });
    
    $form.off('click.esticketsplus').on('click.esticketsplus', '.es-qty-plus', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var $input = $(this).siblings('.es-ticket-qty-input');
        var val = parseInt($input.val()) || 0;
        var max = parseInt($input.attr('max')) || 10;
        if (val < max) {
            $input.val(val + 1).trigger('change');
        }
    });
    
    // Update on quantity change
    $form.off('change.estickets').on('change.estickets', '.es-ticket-qty-input', updateOrder);
    
    function updateOrder() {
        var total = 0;
        var items = [];
        var hasTickets = false;
        
        // Only look at tickets in THIS form
        $form.find('.es-ticket-item').each(function() {
            var $item = $(this);
            var qty = parseInt($item.find('.es-ticket-qty-input').val()) || 0;
            
            if (qty > 0) {
                hasTickets = true;
                var price = parseFloat($item.data('price')) || 0;
                var name = $item.data('name');
                var categoryId = $item.data('category-id');
                var subtotal = qty * price;
                total += subtotal;
                
                items.push({
                    name: name,
                    qty: qty,
                    price: price,
                    subtotal: subtotal,
                    categoryId: categoryId
                });
            }
        });
        
        // Update order summary
        var $items = $orderSummary.find('.es-order-items');
        $items.empty();
        
        items.forEach(function(item) {
            $items.append(
                '<div class="es-order-item" style="display: flex; justify-content: space-between; margin-bottom: 8px;">' +
                '<span>' + item.qty + 'x ' + item.name + '</span>' +
                '<span>' + currencySymbol + item.subtotal.toFixed(2).replace('.', ',') + '</span>' +
                '</div>'
            );
        });
        
        $orderSummary.find('.es-total-amount').text(currencySymbol + total.toFixed(2).replace('.', ','));
        $form.find('input[name="total"]').val(total);
        
        // Show/hide sections
        if (hasTickets) {
            $orderSummary.slideDown(200);
            $customerSection.slideDown(200);
            $paymentSection.slideDown(200);
            $submitSection.slideDown(200);
            $submitBtn.prop('disabled', false);
        } else {
            $orderSummary.slideUp(200);
            $customerSection.slideUp(200);
            $paymentSection.slideUp(200);
            $submitSection.slideUp(200);
            $submitBtn.prop('disabled', true);
        }
    }
    
    // Form submission - 2 step process
    $form.off('submit.estickets').on('submit.estickets', function(e) {
        e.preventDefault();
        
        // Prevent double-submit
        if ($form.data('submitting')) {
            console.log('Tickets Pro: Prevented double submit');
            return;
        }
        $form.data('submitting', true);
        
        var $btn = $submitBtn;
        var $btnText = $btn.find('.es-btn-text');
        var $btnLoading = $btn.find('.es-btn-loading');
        
        // Collect tickets in correct format - only from THIS form
        var tickets = [];
        $form.find('.es-ticket-item').each(function() {
            var $item = $(this);
            var qty = parseInt($item.find('.es-ticket-qty-input').val()) || 0;
            if (qty > 0) {
                tickets.push({
                    category_id: $item.data('category-id'),
                    quantity: qty
                });
            }
        });
        
        console.log('Tickets Pro: Collected tickets:', tickets);
        
        if (tickets.length === 0) {
            alert('<?php echo esc_js(__('Please select at least one ticket.', 'ensemble')); ?>');
            return;
        }
        
        // Get selected gateway
        var gateway = $form.find('input[name="payment_gateway"]:checked').val();
        console.log('Tickets Pro: Selected gateway:', gateway);
        
        if (!gateway) {
            alert('<?php echo esc_js(__('Please select a payment method.', 'ensemble')); ?>');
            return;
        }
        
        $btn.prop('disabled', true);
        $btnText.hide();
        $btnLoading.show();
        
        var requestData = {
            action: 'es_create_ticket_booking',
            nonce: $form.find('input[name="nonce"]').val(),
            event_id: $form.find('input[name="event_id"]').val(),
            tickets: tickets,
            customer_name: $form.find('#es-customer-name').val(),
            customer_email: $form.find('#es-customer-email').val(),
            customer_phone: $form.find('#es-customer-phone').val()
        };
        
        console.log('Tickets Pro: Step 1 - Creating booking with data:', requestData);
        
        // Step 1: Create booking
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: requestData,
            success: function(response) {
                console.log('Tickets Pro: Step 1 response:', response);
                if (response.success) {
                    console.log('Tickets Pro: Booking created, ID:', response.data.booking_id);
                    // Step 2: Process payment
                    processPayment(response.data.booking_id, gateway);
                } else {
                    console.error('Tickets Pro: Step 1 failed:', response);
                    alert(response.data.message || '<?php echo esc_js(__('Failed to create booking.', 'ensemble')); ?>');
                    resetButton();
                }
            },
            error: function(xhr, status, error) {
                console.error('Tickets Pro: Step 1 AJAX error:', status, error, xhr.responseText);
                alert('<?php echo esc_js(__('Connection error. Please try again.', 'ensemble')); ?>');
                resetButton();
            }
        });
    });
    
    function processPayment(bookingId, gateway) {
        var paymentData = {
            action: 'es_process_ticket_payment',
            nonce: $form.find('input[name="nonce"]').val(),
            booking_id: bookingId,
            gateway: gateway
        };
        
        console.log('Tickets Pro: Step 2 - Processing payment with data:', paymentData);
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: paymentData,
            success: function(response) {
                console.log('Tickets Pro: Step 2 response:', response);
                if (response.success && response.data.redirect_url) {
                    console.log('Tickets Pro: Redirecting to:', response.data.redirect_url);
                    window.location.href = response.data.redirect_url;
                } else if (response.success && response.data.message) {
                    // Free ticket or direct confirmation
                    alert(response.data.message);
                    window.location.reload();
                } else {
                    console.error('Tickets Pro: Step 2 failed:', response);
                    alert(response.data.message || '<?php echo esc_js(__('Payment processing failed.', 'ensemble')); ?>');
                    resetButton();
                }
            },
            error: function(xhr, status, error) {
                console.error('Tickets Pro: Step 2 AJAX error:', status, error, xhr.responseText);
                alert('<?php echo esc_js(__('Connection error during payment. Please check your booking status.', 'ensemble')); ?>');
                resetButton();
            }
        });
    }
    
    function resetButton() {
        var $btn = $submitBtn;
        $btn.prop('disabled', false);
        $btn.find('.es-btn-text').show();
        $btn.find('.es-btn-loading').hide();
        $form.data('submitting', false);
    }
});
</script>
