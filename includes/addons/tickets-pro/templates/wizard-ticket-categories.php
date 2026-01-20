<?php
/**
 * Event Wizard - Unified Tickets Card
 * 
 * Combines Paid Tickets (with payment) and External Tickets (affiliate links)
 * into a single, unified interface.
 * 
 * Ticket Types:
 * - paid: Self-hosted ticket sales with payment gateway
 * - external: Links to external providers (Eventbrite, RA, etc.)
 *
 * @package Ensemble
 * @subpackage Addons/TicketsPro/Templates
 * @since 3.1.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get addon instance
$addon = ES_Tickets_Pro();
$has_gateways = $addon->has_gateways();

// Get currency settings
$settings = get_option('ensemble_tickets_pro_settings', array());
$currency = isset($settings['currency']) ? $settings['currency'] : 'EUR';
$currency_symbols = array('EUR' => '€', 'USD' => '$', 'GBP' => '£', 'CHF' => 'CHF');
$currency_symbol = isset($currency_symbols[$currency]) ? $currency_symbols[$currency] : $currency;

// Get available templates for paid tickets
$templates = ES_Ticket_Category::get_templates(true);
$has_templates = !empty($templates);

// External ticket providers
$providers = array(
    'eventbrite' => array(
        'name'  => 'Eventbrite',
        'color' => '#f05537',
    ),
    'resident_advisor' => array(
        'name'  => 'Resident Advisor',
        'color' => '#0a0a0a',
    ),
    'eventim' => array(
        'name'  => 'Eventim',
        'color' => '#003d7c',
    ),
    'ticketmaster' => array(
        'name'  => 'Ticketmaster',
        'color' => '#026cdf',
    ),
    'dice' => array(
        'name'  => 'DICE',
        'color' => '#000000',
    ),
    'reservix' => array(
        'name'  => 'Reservix',
        'color' => '#e30613',
    ),
    'tickets_io' => array(
        'name'  => 'tickets.io',
        'color' => '#00a8e8',
    ),
    'custom' => array(
        'name'  => __('Custom / Other', 'ensemble'),
        'color' => '#6b7280',
    ),
);
$providers = apply_filters('ensemble_ticket_providers', $providers);

// Availability statuses for external tickets
$statuses = array(
    'available' => __('Available', 'ensemble'),
    'limited'   => __('Limited', 'ensemble'),
    'few_left'  => __('Few Tickets Left', 'ensemble'),
    'presale'   => __('Presale', 'ensemble'),
    'sold_out'  => __('Sold Out', 'ensemble'),
    'cancelled' => __('Cancelled', 'ensemble'),
);

// Get available floor plans WITH their linked location (for import feature)
$floor_plans_data_tickets = array();
$floor_plans_by_location_tickets = array();
$floor_plans_available_tickets = false;

if (class_exists('ES_Floor_Plan_Addon')) {
    $floor_plans_posts = get_posts(array(
        'post_type'      => 'ensemble_floor_plan',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'post_status'    => 'publish',
    ));
    
    if (!empty($floor_plans_posts)) {
        $floor_plans_available_tickets = true;
        foreach ($floor_plans_posts as $fp) {
            $linked_location = get_post_meta($fp->ID, '_linked_location', true);
            $fp_data = get_post_meta($fp->ID, '_floor_plan_data', true);
            
            // Count bookable elements with prices
            $bookable_count = 0;
            $elements = array();
            if (!empty($fp_data['elements'])) {
                foreach ($fp_data['elements'] as $element) {
                    // Handle both boolean and string values for 'bookable'
                    $is_bookable = false;
                    if (isset($element['bookable'])) {
                        $bookable_val = $element['bookable'];
                        $is_bookable = ($bookable_val === true || $bookable_val === 'true' || $bookable_val === '1' || $bookable_val === 1);
                    }
                    
                    if ($is_bookable) {
                        $bookable_count++;
                        $elements[] = array(
                            'id'       => $element['id'],
                            'type'     => $element['type'],
                            'label'    => $element['label'] ?? '',
                            'number'   => $element['number'] ?? 0,
                            'seats'    => $element['seats'] ?? $element['capacity'] ?? 0,
                            'price'    => $element['price'] ?? 0,
                            'section'  => $element['section_id'] ?? '',
                        );
                    }
                }
            }
            
            $plan_data = array(
                'id'              => $fp->ID,
                'name'            => $fp->post_title,
                'location_id'     => $linked_location ? intval($linked_location) : null,
                'bookable_count'  => $bookable_count,
                'elements'        => $elements,
            );
            
            $floor_plans_data_tickets[] = $plan_data;
            
            // Index by location for quick lookup
            if ($linked_location) {
                $location_id = intval($linked_location);
                if (!isset($floor_plans_by_location_tickets[$location_id])) {
                    $floor_plans_by_location_tickets[$location_id] = array();
                }
                $floor_plans_by_location_tickets[$location_id][] = $plan_data;
            }
        }
    }
}
?>

<!-- Unified Tickets Card -->
<div class="es-form-card es-tickets-unified-card" id="es-tickets-unified-card" style="display: none;">
    <div class="es-form-card-header">
        <div class="es-form-card-icon">
            <span class="dashicons dashicons-tickets-alt"></span>
        </div>
        <div class="es-form-card-title">
            <h3><?php _e('Tickets', 'ensemble'); ?></h3>
            <p class="es-form-card-desc"><?php _e('Sell tickets directly or link to external providers', 'ensemble'); ?></p>
        </div>
    </div>
    <div class="es-form-card-body">
        
        <!-- Action Buttons -->
        <div class="es-ticket-buttons">
            <button type="button" class="button button-primary es-add-ticket-btn" data-type="paid">
                <span class="dashicons dashicons-plus-alt2"></span>
                <?php _e('Add Ticket', 'ensemble'); ?>
            </button>
            
            <?php if ($has_templates): ?>
            <button type="button" class="button es-import-templates-btn">
                <span class="dashicons dashicons-download"></span>
                <?php _e('Import Templates', 'ensemble'); ?>
            </button>
            <?php endif; ?>
            
            <!-- Floor Plan Import Button (shows when location has floor plan) -->
            <button type="button" 
                    class="button es-import-floorplan-btn" 
                    id="es-import-floorplan-tickets-btn"
                    style="display: none;"
                    title="<?php _e('Create ticket categories from floor plan elements', 'ensemble'); ?>">
                <span class="dashicons dashicons-layout"></span>
                <?php _e('Create Tickets from Floor Plan', 'ensemble'); ?>
            </button>
        </div>
        
        <!-- Floor Plan Info (shows when location has floor plan) -->
        <div class="es-floor-plan-tickets-info" id="es-floor-plan-tickets-info" style="display: none;">
            <div class="es-floor-plan-detected-small">
                <span class="dashicons dashicons-layout"></span>
                <span class="es-floor-plan-name"></span>
                <span class="es-floor-plan-count"></span>
            </div>
        </div>
        
        <?php if (!$has_gateways): ?>
        <!-- No Gateway Info -->
        <div class="es-ticket-info-notice" style="margin-top: 15px;">
            <span class="dashicons dashicons-info"></span>
            <span><?php _e('For paid ticket sales, configure a', 'ensemble'); ?> 
                <a href="<?php echo admin_url('admin.php?page=ensemble-tickets-pro&tab=gateways'); ?>" target="_blank"><?php _e('payment gateway', 'ensemble'); ?></a>.
            </span>
        </div>
        <?php endif; ?>
        
        <!-- Tickets List -->
        <div class="es-tickets-unified-list" id="es-tickets-unified-list"></div>
        
        <!-- Empty State -->
        <div class="es-tickets-unified-empty" id="es-tickets-unified-empty">
            <span class="dashicons dashicons-tickets-alt"></span>
            <p><?php _e('No tickets yet', 'ensemble'); ?></p>
            <p class="es-empty-hint"><?php _e('Add paid tickets or external links to ticket providers.', 'ensemble'); ?></p>
        </div>
        
        <!-- Hidden fields to store data -->
        <input type="hidden" id="es-tickets-unified-data" name="tickets_unified_json" value="">
        
    </div>
</div>

<!-- Add/Edit Ticket Modal -->
<div id="es-ticket-modal" class="es-modal" style="display: none;">
    <div class="es-modal-content es-modal-large">
        <div class="es-modal-header">
            <h3 class="es-modal-title"><?php _e('Add Ticket', 'ensemble'); ?></h3>
            <button type="button" class="es-modal-close">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        <div class="es-modal-body">
            
            <!-- Ticket Type Selector -->
            <div class="es-ticket-type-selector">
                <label class="es-ticket-type-option es-ticket-type-paid active" data-type="paid">
                    <span class="es-type-icon">
                        <span class="dashicons dashicons-money-alt"></span>
                    </span>
                    <span class="es-type-label">
                        <strong><?php _e('Paid Ticket', 'ensemble'); ?></strong>
                        <small><?php _e('Sell directly with payment', 'ensemble'); ?></small>
                    </span>
                </label>
                <label class="es-ticket-type-option es-ticket-type-external" data-type="external">
                    <span class="es-type-icon">
                        <span class="dashicons dashicons-external"></span>
                    </span>
                    <span class="es-type-label">
                        <strong><?php _e('External Link', 'ensemble'); ?></strong>
                        <small><?php _e('Link to Eventbrite, RA, etc.', 'ensemble'); ?></small>
                    </span>
                </label>
            </div>
            
            <!-- Paid Ticket Fields -->
            <div class="es-ticket-fields es-ticket-fields-paid">
                <div class="es-form-row">
                    <label class="es-form-label"><?php _e('Name', 'ensemble'); ?> *</label>
                    <input type="text" id="es-ticket-name" class="es-form-input" placeholder="<?php esc_attr_e('e.g., Early Bird, VIP, Standard', 'ensemble'); ?>">
                </div>
                
                <div class="es-form-row">
                    <label class="es-form-label"><?php _e('Description', 'ensemble'); ?></label>
                    <input type="text" id="es-ticket-description" class="es-form-input" placeholder="<?php esc_attr_e('e.g., Includes welcome drink', 'ensemble'); ?>">
                </div>
                
                <div class="es-form-row-group">
                    <div class="es-form-row">
                        <label class="es-form-label"><?php _e('Price', 'ensemble'); ?> *</label>
                        <div class="es-input-group">
                            <span class="es-input-prefix"><?php echo esc_html($currency_symbol); ?></span>
                            <input type="number" id="es-ticket-price" class="es-form-input" min="0" step="0.01" value="0">
                        </div>
                    </div>
                    
                    <div class="es-form-row">
                        <label class="es-form-label"><?php _e('Capacity', 'ensemble'); ?></label>
                        <input type="number" id="es-ticket-capacity" class="es-form-input" min="0" placeholder="<?php esc_attr_e('Unlimited', 'ensemble'); ?>">
                    </div>
                </div>
                
                <div class="es-form-row-group">
                    <div class="es-form-row">
                        <label class="es-form-label"><?php _e('Min. per Order', 'ensemble'); ?></label>
                        <input type="number" id="es-ticket-min" class="es-form-input" min="1" value="1">
                    </div>
                    
                    <div class="es-form-row">
                        <label class="es-form-label"><?php _e('Max. per Order', 'ensemble'); ?></label>
                        <input type="number" id="es-ticket-max" class="es-form-input" min="1" value="10">
                    </div>
                </div>
            </div>
            
            <!-- External Link Fields -->
            <div class="es-ticket-fields es-ticket-fields-external" style="display: none;">
                <div class="es-form-row">
                    <label class="es-form-label"><?php _e('Provider', 'ensemble'); ?></label>
                    <select id="es-ticket-provider" class="es-form-input">
                        <?php foreach ($providers as $key => $provider): ?>
                        <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($provider['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="es-form-row">
                    <label class="es-form-label"><?php _e('Name', 'ensemble'); ?></label>
                    <input type="text" id="es-ticket-ext-name" class="es-form-input" placeholder="<?php esc_attr_e('e.g., Early Bird (optional)', 'ensemble'); ?>">
                </div>
                
                <div class="es-form-row">
                    <label class="es-form-label"><?php _e('Ticket URL', 'ensemble'); ?> *</label>
                    <input type="url" id="es-ticket-url" class="es-form-input" placeholder="https://www.eventbrite.com/e/...">
                </div>
                
                <div class="es-form-row-group">
                    <div class="es-form-row">
                        <label class="es-form-label"><?php _e('Price (from)', 'ensemble'); ?></label>
                        <div class="es-input-group">
                            <span class="es-input-prefix"><?php echo esc_html($currency_symbol); ?></span>
                            <input type="number" id="es-ticket-ext-price" class="es-form-input" min="0" step="0.01" placeholder="0">
                        </div>
                    </div>
                    
                    <div class="es-form-row">
                        <label class="es-form-label"><?php _e('Price (to)', 'ensemble'); ?></label>
                        <div class="es-input-group">
                            <span class="es-input-prefix"><?php echo esc_html($currency_symbol); ?></span>
                            <input type="number" id="es-ticket-ext-price-max" class="es-form-input" min="0" step="0.01" placeholder="0">
                        </div>
                    </div>
                </div>
                
                <div class="es-form-row">
                    <label class="es-form-label"><?php _e('Availability', 'ensemble'); ?></label>
                    <select id="es-ticket-status" class="es-form-input">
                        <?php foreach ($statuses as $key => $label): ?>
                        <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <input type="hidden" id="es-ticket-index" value="-1">
            <input type="hidden" id="es-ticket-type" value="paid">
        </div>
        <div class="es-modal-footer">
            <button type="button" class="button es-modal-cancel"><?php _e('Cancel', 'ensemble'); ?></button>
            <button type="button" class="button button-primary es-ticket-save"><?php _e('Add Ticket', 'ensemble'); ?></button>
        </div>
    </div>
</div>

<!-- Import Templates Modal -->
<?php if ($has_templates): ?>
<div id="es-import-templates-modal" class="es-modal" style="display: none;">
    <div class="es-modal-content">
        <div class="es-modal-header">
            <h3 class="es-modal-title"><?php _e('Import Templates', 'ensemble'); ?></h3>
            <button type="button" class="es-modal-close es-import-close">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        <div class="es-modal-body">
            <p class="description"><?php _e('Select templates to import as paid tickets:', 'ensemble'); ?></p>
            
            <div class="es-import-actions">
                <button type="button" class="button-link es-select-all"><?php _e('Select All', 'ensemble'); ?></button>
                <span>|</span>
                <button type="button" class="button-link es-deselect-all"><?php _e('Deselect All', 'ensemble'); ?></button>
            </div>
            
            <div class="es-import-list">
                <?php foreach ($templates as $template): ?>
                <label class="es-import-item">
                    <input type="checkbox" name="import_templates[]" value="<?php echo esc_attr($template->id); ?>" 
                           data-name="<?php echo esc_attr($template->name); ?>"
                           data-price="<?php echo esc_attr($template->price); ?>"
                           data-description="<?php echo esc_attr($template->description); ?>"
                           data-capacity="<?php echo esc_attr($template->capacity); ?>">
                    <span class="es-import-info">
                        <strong><?php echo esc_html($template->name); ?></strong>
                        <span class="es-import-price"><?php echo esc_html($currency_symbol . number_format($template->price, 2, ',', '.')); ?></span>
                        <?php if ($template->description): ?>
                        <span class="es-import-desc"><?php echo esc_html($template->description); ?></span>
                        <?php endif; ?>
                    </span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="es-modal-footer">
            <button type="button" class="button es-import-close"><?php _e('Cancel', 'ensemble'); ?></button>
            <button type="button" class="button button-primary es-import-confirm"><?php _e('Import Selected', 'ensemble'); ?></button>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
/* Unified Tickets Card */
#es-tickets-unified-card {
    display: none;
}

/* Ticket Buttons */
.es-ticket-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.es-ticket-buttons .button {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.es-ticket-buttons .button .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

/* Info Notice */
.es-ticket-info-notice {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 12px;
    background: var(--es-info-bg, #e7f3ff);
    border-radius: 4px;
    font-size: 13px;
    color: var(--es-info, #0073aa);
}

.es-ticket-info-notice .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

.es-ticket-info-notice a {
    color: inherit;
    text-decoration: underline;
}

/* Tickets List */
.es-tickets-unified-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-top: 15px;
}

.es-tickets-unified-list:empty {
    display: none;
}

.es-tickets-unified-list:empty + .es-tickets-unified-empty {
    display: flex;
}

/* Ticket Item */
.es-ticket-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 15px;
    background: var(--es-surface-secondary, #f6f7f7);
    border: 1px solid var(--es-border, #dcdcde);
    border-radius: 6px;
    transition: all 0.2s ease;
}

.es-ticket-item:hover {
    border-color: var(--es-primary, #2271b1);
}

.es-ticket-item-icon {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    flex-shrink: 0;
}

.es-ticket-item-icon.es-type-paid {
    background: var(--es-success-bg, #edfaef);
    color: var(--es-success, #00a32a);
}

.es-ticket-item-icon.es-type-external {
    background: var(--es-info-bg, #e7f3ff);
    color: var(--es-info, #0073aa);
}

.es-ticket-item-icon .dashicons {
    font-size: 20px;
    width: 20px;
    height: 20px;
}

.es-ticket-item-info {
    flex: 1;
    min-width: 0;
}

.es-ticket-item-name {
    font-weight: 600;
    color: var(--es-text, #1d2327);
    margin: 0 0 2px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.es-ticket-item-badge {
    font-size: 10px;
    font-weight: 500;
    padding: 2px 6px;
    border-radius: 3px;
    text-transform: uppercase;
}

.es-ticket-item-badge.es-badge-paid {
    background: var(--es-success-bg, #edfaef);
    color: var(--es-success, #00a32a);
}

.es-ticket-item-badge.es-badge-external {
    background: var(--es-info-bg, #e7f3ff);
    color: var(--es-info, #0073aa);
}

.es-ticket-item-meta {
    font-size: 12px;
    color: var(--es-text-muted, #646970);
    display: flex;
    align-items: center;
    gap: 8px;
}

.es-ticket-item-price {
    font-weight: 600;
    font-size: 15px;
    color: var(--es-text, #1d2327);
    padding: 0 15px;
    white-space: nowrap;
}

.es-ticket-item-actions {
    display: flex;
    gap: 6px;
}

.es-ticket-item-actions .button {
    padding: 4px 8px;
    min-height: auto;
}

/* Status Badge */
.es-status-badge {
    font-size: 11px;
    padding: 2px 6px;
    border-radius: 3px;
    font-weight: 500;
}

.es-status-available { background: #edfaef; color: #00a32a; }
.es-status-limited { background: #fcf0e3; color: #996800; }
.es-status-few_left { background: #fcf0e3; color: #996800; }
.es-status-presale { background: #e7f3ff; color: #0073aa; }
.es-status-sold_out { background: #fce4e4; color: #cc1818; }
.es-status-cancelled { background: #f0f0f1; color: #646970; }

/* Empty State */
.es-tickets-unified-empty {
    display: none;
    flex-direction: column;
    align-items: center;
    padding: 30px 20px;
    background: var(--es-surface-secondary, #f6f7f7);
    border: 2px dashed var(--es-border, #dcdcde);
    border-radius: 6px;
    text-align: center;
    margin-top: 15px;
}

.es-tickets-unified-empty .dashicons {
    font-size: 40px;
    width: 40px;
    height: 40px;
    color: var(--es-text-muted, #a7aaad);
}

.es-tickets-unified-empty p {
    margin: 10px 0 0;
    color: var(--es-text-secondary, #646970);
}

.es-tickets-unified-empty .es-empty-hint {
    font-size: 13px;
    color: var(--es-text-muted, #a7aaad);
}

/* Modal */
.es-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0, 0, 0, 0.6);
}

.es-modal-content {
    background: var(--es-surface, #fff);
    border-radius: 8px;
    width: 90%;
    max-width: 480px;
    max-height: 90vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}

.es-modal-large {
    max-width: 540px;
}

.es-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    border-bottom: 1px solid var(--es-border, #dcdcde);
}

.es-modal-header h3 {
    margin: 0;
    font-size: 16px;
}

.es-modal-close {
    background: none;
    border: none;
    cursor: pointer;
    padding: 4px;
    color: var(--es-text-secondary, #646970);
}

.es-modal-close:hover {
    color: var(--es-text, #1d2327);
}

.es-modal-body {
    padding: 20px;
    overflow-y: auto;
}

.es-modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 16px 20px;
    border-top: 1px solid var(--es-border, #dcdcde);
    background: var(--es-surface-secondary, #f6f7f7);
}

/* Ticket Type Selector */
.es-ticket-type-selector {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-bottom: 20px;
}

.es-ticket-type-option {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 15px;
    border: 2px solid var(--es-border, #dcdcde);
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.es-ticket-type-option:hover {
    border-color: var(--es-primary, #2271b1);
}

.es-ticket-type-option.active {
    border-color: var(--es-primary, #2271b1);
    background: var(--es-primary-bg, #f0f6fc);
}

.es-type-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    background: var(--es-surface-secondary, #f6f7f7);
}

.es-ticket-type-option.active .es-type-icon {
    background: var(--es-primary, #2271b1);
    color: #fff;
}

.es-type-icon .dashicons {
    font-size: 20px;
    width: 20px;
    height: 20px;
}

.es-type-label {
    display: flex;
    flex-direction: column;
}

.es-type-label strong {
    font-size: 14px;
    color: var(--es-text, #1d2327);
}

.es-type-label small {
    font-size: 12px;
    color: var(--es-text-muted, #646970);
}

/* Form Rows */
.es-form-row {
    margin-bottom: 16px;
}

.es-form-row:last-child {
    margin-bottom: 0;
}

.es-form-label {
    display: block;
    font-weight: 500;
    margin-bottom: 6px;
    color: var(--es-text, #1d2327);
}

.es-form-input {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid var(--es-border, #dcdcde);
    border-radius: 4px;
    font-size: 14px;
}

.es-form-input:focus {
    border-color: var(--es-primary, #2271b1);
    outline: none;
    box-shadow: 0 0 0 1px var(--es-primary, #2271b1);
}

.es-form-row-group {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.es-input-group {
    display: flex;
}

.es-input-prefix {
    display: flex;
    align-items: center;
    padding: 0 12px;
    background: var(--es-surface-secondary, #f6f7f7);
    border: 1px solid var(--es-border, #dcdcde);
    border-right: none;
    border-radius: 4px 0 0 4px;
    color: var(--es-text-secondary, #646970);
    font-weight: 500;
}

.es-input-group .es-form-input {
    border-radius: 0 4px 4px 0;
}

/* Import Modal */
.es-import-actions {
    display: flex;
    gap: 10px;
    margin: 10px 0;
}

.es-import-list {
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid var(--es-border, #dcdcde);
    border-radius: 4px;
}

.es-import-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px 15px;
    border-bottom: 1px solid var(--es-border, #dcdcde);
    cursor: pointer;
}

.es-import-item:last-child {
    border-bottom: none;
}

.es-import-item:hover {
    background: var(--es-surface-secondary, #f6f7f7);
}

.es-import-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.es-import-price {
    font-size: 13px;
    color: var(--es-success, #00a32a);
    font-weight: 500;
}

.es-import-desc {
    font-size: 12px;
    color: var(--es-text-muted, #a7aaad);
}

/* Responsive */
@media (max-width: 600px) {
    .es-ticket-type-selector {
        grid-template-columns: 1fr;
    }
    
    .es-form-row-group {
        grid-template-columns: 1fr;
    }
    
    .es-ticket-item {
        flex-wrap: wrap;
    }
    
    .es-ticket-item-price {
        width: 100%;
        padding: 10px 0 0;
        text-align: left;
    }
    
    .es-ticket-item-actions {
        width: 100%;
        justify-content: flex-end;
    }
}
</style>

<script>
(function($) {
    'use strict';
    
    var tickets = [];
    var editingIndex = -1;
    var currentType = 'paid';
    var currencySymbol = '<?php echo esc_js($currency_symbol); ?>';
    
    var providers = <?php echo json_encode($providers); ?>;
    var statuses = <?php echo json_encode($statuses); ?>;
    
    // =====================================
    // INITIALIZATION
    // =====================================
    
    $(document).ready(function() {
        bindEvents();
        updateCardVisibility();
    });
    
    function bindEvents() {
        // Add ticket button
        $(document).on('click', '.es-add-ticket-btn', openModal);
        
        // Type selector
        $(document).on('click', '.es-ticket-type-option', function(e) {
            e.preventDefault();
            selectType($(this).data('type'));
        });
        
        // Edit ticket
        $(document).on('click', '.es-ticket-edit', function(e) {
            e.preventDefault();
            e.stopPropagation();
            editingIndex = $(this).closest('.es-ticket-item').data('index');
            openModal();
        });
        
        // Delete ticket
        $(document).on('click', '.es-ticket-delete', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var index = $(this).closest('.es-ticket-item').data('index');
            deleteTicket(index);
        });
        
        // Modal controls
        $(document).on('click', '#es-ticket-modal .es-modal-close, #es-ticket-modal .es-modal-cancel', closeModal);
        $(document).on('click', '.es-ticket-save', saveTicket);
        
        // Import templates
        $(document).on('click', '.es-import-templates-btn', function() {
            $('#es-import-templates-modal').fadeIn(200);
        });
        $(document).on('click', '.es-import-close', function() {
            $('#es-import-templates-modal').fadeOut(200);
        });
        $(document).on('click', '.es-select-all', function() {
            $('#es-import-templates-modal input[type="checkbox"]').prop('checked', true);
        });
        $(document).on('click', '.es-deselect-all', function() {
            $('#es-import-templates-modal input[type="checkbox"]').prop('checked', false);
        });
        $(document).on('click', '.es-import-confirm', importTemplates);
        
        // ESC to close
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
                $('#es-import-templates-modal').fadeOut(200);
            }
        });
        
        // Watch booking mode changes
        $(document).on('change', '[name="booking_mode"]', function() {
            setTimeout(updateCardVisibility, 100);
        });
        
        // Event loaded
        $(document).on('ensemble_event_loaded', function(e, eventData) {
            if (eventData && eventData.tickets_unified) {
                tickets = eventData.tickets_unified;
            } else if (eventData && eventData.ticket_categories) {
                // Migration from old format
                tickets = eventData.ticket_categories.map(function(cat) {
                    return Object.assign({}, cat, { ticket_type: 'paid' });
                });
            } else {
                tickets = [];
            }
            render();
            setTimeout(updateCardVisibility, 100);
        });
        
        // Event reset
        $(document).on('ensemble_event_reset', function() {
            tickets = [];
            render();
        });
    }
    
    // =====================================
    // VISIBILITY
    // =====================================
    
    function updateCardVisibility() {
        var bookingMode = '';
        
        // Get booking mode from radio buttons
        var $checkedMode = $('input[name="booking_mode"]:checked');
        if ($checkedMode.length) {
            bookingMode = $checkedMode.val();
        }
        
        // Fallback to ensembleWizard.event
        if (!bookingMode && window.ensembleWizard && window.ensembleWizard.event) {
            bookingMode = window.ensembleWizard.event.booking_mode;
        }
        
        var $card = $('#es-tickets-unified-card');
        
        // Show only when booking mode is 'ticket' or 'both'
        if (bookingMode === 'ticket' || bookingMode === 'both') {
            $card.slideDown(200);
            render();
        } else {
            $card.slideUp(200);
        }
    }
    
    // =====================================
    // MODAL
    // =====================================
    
    function openModal() {
        resetForm();
        
        if (editingIndex >= 0 && tickets[editingIndex]) {
            var ticket = tickets[editingIndex];
            currentType = ticket.ticket_type || 'paid';
            selectType(currentType);
            populateForm(ticket);
            $('.es-modal-title').text('<?php echo esc_js(__('Edit Ticket', 'ensemble')); ?>');
            $('.es-ticket-save').text('<?php echo esc_js(__('Update', 'ensemble')); ?>');
        } else {
            editingIndex = -1;
            currentType = 'paid';
            selectType('paid');
            $('.es-modal-title').text('<?php echo esc_js(__('Add Ticket', 'ensemble')); ?>');
            $('.es-ticket-save').text('<?php echo esc_js(__('Add Ticket', 'ensemble')); ?>');
        }
        
        $('#es-ticket-modal').fadeIn(200);
        setTimeout(function() {
            if (currentType === 'paid') {
                $('#es-ticket-name').focus();
            } else {
                $('#es-ticket-url').focus();
            }
        }, 250);
    }
    
    function closeModal() {
        $('#es-ticket-modal').fadeOut(200);
        editingIndex = -1;
    }
    
    function selectType(type) {
        currentType = type;
        $('#es-ticket-type').val(type);
        
        $('.es-ticket-type-option').removeClass('active');
        $('.es-ticket-type-option[data-type="' + type + '"]').addClass('active');
        
        if (type === 'paid') {
            $('.es-ticket-fields-paid').show();
            $('.es-ticket-fields-external').hide();
        } else {
            $('.es-ticket-fields-paid').hide();
            $('.es-ticket-fields-external').show();
        }
    }
    
    function resetForm() {
        $('#es-ticket-name').val('');
        $('#es-ticket-description').val('');
        $('#es-ticket-price').val(0);
        $('#es-ticket-capacity').val('');
        $('#es-ticket-min').val(1);
        $('#es-ticket-max').val(10);
        
        $('#es-ticket-provider').val('eventbrite');
        $('#es-ticket-ext-name').val('');
        $('#es-ticket-url').val('');
        $('#es-ticket-ext-price').val('');
        $('#es-ticket-ext-price-max').val('');
        $('#es-ticket-status').val('available');
        
        $('#es-ticket-index').val(-1);
    }
    
    function populateForm(ticket) {
        if (ticket.ticket_type === 'paid') {
            $('#es-ticket-name').val(ticket.name || '');
            $('#es-ticket-description').val(ticket.description || '');
            $('#es-ticket-price').val(ticket.price || 0);
            $('#es-ticket-capacity').val(ticket.capacity || '');
            $('#es-ticket-min').val(ticket.min_quantity || 1);
            $('#es-ticket-max').val(ticket.max_quantity || 10);
        } else {
            $('#es-ticket-provider').val(ticket.provider || 'custom');
            $('#es-ticket-ext-name').val(ticket.name || '');
            $('#es-ticket-url').val(ticket.url || '');
            $('#es-ticket-ext-price').val(ticket.price || '');
            $('#es-ticket-ext-price-max').val(ticket.price_max || '');
            $('#es-ticket-status').val(ticket.availability || 'available');
        }
    }
    
    // =====================================
    // SAVE / DELETE
    // =====================================
    
    function saveTicket() {
        var ticket = {
            ticket_type: currentType
        };
        
        if (currentType === 'paid') {
            var name = $('#es-ticket-name').val().trim();
            if (!name) {
                alert('<?php echo esc_js(__('Please enter a ticket name.', 'ensemble')); ?>');
                $('#es-ticket-name').focus();
                return;
            }
            
            ticket.name = name;
            ticket.description = $('#es-ticket-description').val().trim();
            ticket.price = parseFloat($('#es-ticket-price').val()) || 0;
            ticket.capacity = $('#es-ticket-capacity').val() ? parseInt($('#es-ticket-capacity').val()) : null;
            ticket.min_quantity = parseInt($('#es-ticket-min').val()) || 1;
            ticket.max_quantity = parseInt($('#es-ticket-max').val()) || 10;
            ticket.status = 'active';
        } else {
            var url = $('#es-ticket-url').val().trim();
            if (!url) {
                alert('<?php echo esc_js(__('Please enter a ticket URL.', 'ensemble')); ?>');
                $('#es-ticket-url').focus();
                return;
            }
            
            ticket.provider = $('#es-ticket-provider').val();
            ticket.name = $('#es-ticket-ext-name').val().trim();
            ticket.url = url;
            ticket.price = parseFloat($('#es-ticket-ext-price').val()) || 0;
            ticket.price_max = parseFloat($('#es-ticket-ext-price-max').val()) || 0;
            ticket.availability = $('#es-ticket-status').val();
        }
        
        if (editingIndex >= 0 && tickets[editingIndex]) {
            ticket.id = tickets[editingIndex].id;
            tickets[editingIndex] = ticket;
        } else {
            ticket.id = currentType + '_' + Date.now();
            tickets.push(ticket);
        }
        
        saveData();
        render();
        closeModal();
    }
    
    function deleteTicket(index) {
        if (!confirm('<?php echo esc_js(__('Delete this ticket?', 'ensemble')); ?>')) {
            return;
        }
        tickets.splice(index, 1);
        saveData();
        render();
    }
    
    function importTemplates() {
        var $checked = $('#es-import-templates-modal input[type="checkbox"]:checked');
        
        if ($checked.length === 0) {
            alert('<?php echo esc_js(__('Please select at least one template.', 'ensemble')); ?>');
            return;
        }
        
        $checked.each(function() {
            tickets.push({
                id: 'paid_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5),
                ticket_type: 'paid',
                name: $(this).data('name'),
                description: $(this).data('description') || '',
                price: parseFloat($(this).data('price')) || 0,
                capacity: $(this).data('capacity') || null,
                min_quantity: 1,
                max_quantity: 10,
                status: 'active'
            });
        });
        
        saveData();
        render();
        $('#es-import-templates-modal').fadeOut(200);
        $('#es-import-templates-modal input[type="checkbox"]').prop('checked', false);
    }
    
    // =====================================
    // DATA
    // =====================================
    
    function saveData() {
        $('#es-tickets-unified-data').val(JSON.stringify(tickets));
    }
    
    // =====================================
    // RENDER
    // =====================================
    
    function render() {
        var $list = $('#es-tickets-unified-list');
        var $empty = $('#es-tickets-unified-empty');
        
        $list.empty();
        
        if (tickets.length === 0) {
            $empty.css('display', 'flex');
            return;
        }
        
        $empty.hide();
        
        tickets.forEach(function(ticket, index) {
            var isPaid = ticket.ticket_type === 'paid';
            var iconClass = isPaid ? 'es-type-paid' : 'es-type-external';
            var icon = isPaid ? 'money-alt' : 'external';
            var badgeClass = isPaid ? 'es-badge-paid' : 'es-badge-external';
            var badgeText = isPaid ? '<?php echo esc_js(__('Paid', 'ensemble')); ?>' : '<?php echo esc_js(__('External', 'ensemble')); ?>';
            
            var name = ticket.name;
            if (!name && !isPaid && ticket.provider && providers[ticket.provider]) {
                name = providers[ticket.provider].name;
            }
            
            var meta = '';
            if (isPaid) {
                var cap = ticket.capacity ? ticket.capacity + ' <?php echo esc_js(__('available', 'ensemble')); ?>' : '<?php echo esc_js(__('Unlimited', 'ensemble')); ?>';
                meta = '<span>' + cap + '</span>';
            } else {
                var providerName = (ticket.provider && providers[ticket.provider]) ? providers[ticket.provider].name : 'Link';
                var statusLabel = (ticket.availability && statuses[ticket.availability]) ? statuses[ticket.availability] : '';
                meta = '<span>via ' + escapeHtml(providerName) + '</span>';
                if (statusLabel && ticket.availability !== 'available') {
                    meta += '<span class="es-status-badge es-status-' + ticket.availability + '">' + escapeHtml(statusLabel) + '</span>';
                }
            }
            
            var price = formatPrice(ticket.price, ticket.price_max);
            
            var html = '<div class="es-ticket-item" data-index="' + index + '">' +
                '<div class="es-ticket-item-icon ' + iconClass + '">' +
                    '<span class="dashicons dashicons-' + icon + '"></span>' +
                '</div>' +
                '<div class="es-ticket-item-info">' +
                    '<div class="es-ticket-item-name">' +
                        escapeHtml(name || '<?php echo esc_js(__('Unnamed Ticket', 'ensemble')); ?>') +
                        '<span class="es-ticket-item-badge ' + badgeClass + '">' + badgeText + '</span>' +
                    '</div>' +
                    '<div class="es-ticket-item-meta">' + meta + '</div>' +
                '</div>' +
                '<div class="es-ticket-item-price">' + price + '</div>' +
                '<div class="es-ticket-item-actions">' +
                    '<button type="button" class="button button-small es-ticket-edit"><span class="dashicons dashicons-edit"></span></button>' +
                    '<button type="button" class="button button-small es-ticket-delete"><span class="dashicons dashicons-trash"></span></button>' +
                '</div>' +
            '</div>';
            
            $list.append(html);
        });
    }
    
    function formatPrice(price, priceMax) {
        price = parseFloat(price) || 0;
        priceMax = parseFloat(priceMax) || 0;
        
        if (price <= 0 && priceMax <= 0) {
            return '<?php echo esc_js(__('Free', 'ensemble')); ?>';
        }
        
        var formatted = currencySymbol + price.toFixed(2).replace('.', ',');
        
        if (priceMax > price) {
            formatted += ' - ' + currencySymbol + priceMax.toFixed(2).replace('.', ',');
        }
        
        return formatted;
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // =====================================
    // FLOOR PLAN INTEGRATION
    // =====================================
    
    var floorPlansByLocation = <?php echo json_encode($floor_plans_by_location_tickets); ?>;
    var currentLocationFloorPlan = null;
    
    function checkLocationFloorPlan() {
        // Get current location from wizard (multiple methods for robustness)
        var locationId = null;
        
        // Method 1: Via Vue.js
        if (typeof ensembleWizard !== 'undefined') {
            if (ensembleWizard.vm && ensembleWizard.vm.event) {
                locationId = ensembleWizard.vm.event.location;
            } else if (ensembleWizard.currentEvent) {
                locationId = ensembleWizard.currentEvent.location;
            }
        }
        
        // Method 2: Via selected radio button (fallback)
        if (!locationId) {
            var $selectedLocation = $('input[name="event_location"]:checked');
            if ($selectedLocation.length) {
                locationId = $selectedLocation.val();
            }
        }
        
        // Debug
        console.log('Ticket Floor Plan Check - Location ID:', locationId);
        console.log('Available Floor Plans by Location:', floorPlansByLocation);
        
        var $btn = $('#es-import-floorplan-tickets-btn');
        var $info = $('#es-floor-plan-tickets-info');
        
        if (locationId && floorPlansByLocation[locationId] && floorPlansByLocation[locationId].length > 0) {
            currentLocationFloorPlan = floorPlansByLocation[locationId][0];
            
            console.log('Found Floor Plan:', currentLocationFloorPlan);
            
            // Show button and info
            $btn.show();
            $info.show();
            $info.find('.es-floor-plan-name').text(currentLocationFloorPlan.name);
            $info.find('.es-floor-plan-count').text('(' + currentLocationFloorPlan.bookable_count + ' <?php echo esc_js(__('bookable elements', 'ensemble')); ?>)');
        } else {
            currentLocationFloorPlan = null;
            $btn.hide();
            $info.hide();
        }
    }
    
    // Watch for location changes
    function setupLocationWatcher() {
        if (typeof ensembleWizard !== 'undefined' && ensembleWizard.vm) {
            ensembleWizard.vm.$watch('event.location', function() {
                checkLocationFloorPlan();
            }, { immediate: true });
        }
        
        // Also watch via jQuery for radio changes
        $('input[name="event_location"]').on('change', function() {
            checkLocationFloorPlan();
        });
        
        // Initial check after delay
        setTimeout(checkLocationFloorPlan, 500);
    }
    
    // Import from Floor Plan button click
    $('#es-import-floorplan-tickets-btn').on('click', function() {
        console.log('Button clicked, currentLocationFloorPlan:', currentLocationFloorPlan);
        
        if (!currentLocationFloorPlan) {
            alert('<?php echo esc_js(__('No floor plan found for this location.', 'ensemble')); ?>');
            return;
        }
        
        if (!currentLocationFloorPlan.elements || currentLocationFloorPlan.elements.length === 0) {
            alert('<?php echo esc_js(__('No bookable elements in this floor plan. Please mark elements as "bookable" in the Floor Plan editor.', 'ensemble')); ?>');
            return;
        }
        
        showFloorPlanImportModal();
    });
    
    function showFloorPlanImportModal() {
        var elements = currentLocationFloorPlan.elements;
        
        // Build modal content
        var html = '<div class="es-fp-import-modal-overlay">' +
            '<div class="es-fp-import-modal">' +
                '<div class="es-fp-import-header">' +
                    '<h3><span class="dashicons dashicons-layout"></span> <?php echo esc_js(__('Create Tickets from Floor Plan', 'ensemble')); ?></h3>' +
                    '<button type="button" class="es-fp-import-close"><span class="dashicons dashicons-no-alt"></span></button>' +
                '</div>' +
                '<div class="es-fp-import-body">' +
                    '<p><?php echo esc_js(__('Select elements to create as ticket categories:', 'ensemble')); ?></p>' +
                    '<div class="es-fp-import-list">';
        
        elements.forEach(function(el) {
            var name = el.label || (el.type.charAt(0).toUpperCase() + el.type.slice(1) + ' ' + el.number);
            html += '<div class="es-fp-import-item" data-id="' + el.id + '">' +
                '<label class="es-fp-import-check"><input type="checkbox" checked></label>' +
                '<div class="es-fp-import-info">' +
                    '<strong>' + escapeHtml(name) + '</strong>' +
                    '<span>' + el.type + ' · ' + (el.seats || 1) + ' <?php echo esc_js(__('seats', 'ensemble')); ?></span>' +
                '</div>' +
                '<div class="es-fp-import-price">' +
                    '<input type="number" value="' + (el.price || 0) + '" min="0" step="0.01" placeholder="<?php echo esc_js(__('Price', 'ensemble')); ?>">' +
                '</div>' +
            '</div>';
        });
        
        html += '</div></div>' +
                '<div class="es-fp-import-footer">' +
                    '<label class="es-fp-import-selectall"><input type="checkbox" checked> <?php echo esc_js(__('Select All', 'ensemble')); ?></label>' +
                    '<div class="es-fp-import-actions">' +
                        '<button type="button" class="button es-fp-import-cancel"><?php echo esc_js(__('Cancel', 'ensemble')); ?></button>' +
                        '<button type="button" class="button button-primary es-fp-import-confirm"><?php echo esc_js(__('Create Tickets', 'ensemble')); ?></button>' +
                    '</div>' +
                '</div>' +
            '</div>' +
        '</div>';
        
        $('body').append(html);
        
        // Event handlers
        $('.es-fp-import-close, .es-fp-import-cancel, .es-fp-import-modal-overlay').on('click', function(e) {
            if (e.target === this) {
                $('.es-fp-import-modal-overlay').remove();
            }
        });
        
        $('.es-fp-import-selectall input').on('change', function() {
            $('.es-fp-import-item input[type="checkbox"]').prop('checked', this.checked);
        });
        
        $('.es-fp-import-confirm').on('click', function() {
            importFloorPlanTickets();
        });
    }
    
    function importFloorPlanTickets() {
        var imported = 0;
        
        $('.es-fp-import-item').each(function() {
            var $item = $(this);
            if (!$item.find('input[type="checkbox"]').is(':checked')) {
                return;
            }
            
            var elementId = $item.data('id');
            var element = currentLocationFloorPlan.elements.find(function(el) { return el.id === elementId; });
            if (!element) return;
            
            var price = parseFloat($item.find('.es-fp-import-price input').val()) || 0;
            var name = element.label || (element.type.charAt(0).toUpperCase() + element.type.slice(1) + ' ' + element.number);
            
            // Create new ticket
            var newTicket = {
                id: 'fp_' + elementId + '_' + Date.now(),
                ticket_type: 'paid',
                name: name,
                price: price,
                capacity: element.seats || 1,
                floor_plan_id: currentLocationFloorPlan.id,
                floor_plan_element_id: elementId,
                source: 'floor_plan'
            };
            
            tickets.push(newTicket);
            imported++;
        });
        
        if (imported > 0) {
            saveData();
            render();
        }
        
        $('.es-fp-import-modal-overlay').remove();
        
        if (imported > 0) {
            alert(imported + ' <?php echo esc_js(__('ticket(s) created from floor plan.', 'ensemble')); ?>');
        }
    }
    
    // Initialize on load
    setTimeout(setupLocationWatcher, 500);
    
})(jQuery);
</script>

<!-- Floor Plan Import Modal Styles -->
<style>
.es-floor-plan-tickets-info {
    display: flex;
    align-items: center;
    padding: 8px 12px;
    background: var(--es-info-light, rgba(53, 130, 196, 0.1));
    border-radius: var(--es-radius-sm, 4px);
    margin-top: 12px;
    font-size: 13px;
}

.es-floor-plan-detected-small {
    display: flex;
    align-items: center;
    gap: 8px;
}

.es-floor-plan-detected-small .dashicons {
    color: var(--es-info, #3582c4);
}

.es-floor-plan-count {
    color: var(--es-text-secondary, #666);
}

.es-fp-import-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 100001;
}

.es-fp-import-modal {
    background: #fff;
    border-radius: 12px;
    width: 90%;
    max-width: 550px;
    max-height: 80vh;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.es-fp-import-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    border-bottom: 1px solid #e0e0e0;
}

.es-fp-import-header h3 {
    margin: 0;
    font-size: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.es-fp-import-close {
    background: none;
    border: none;
    cursor: pointer;
    padding: 4px;
}

.es-fp-import-body {
    padding: 20px;
    max-height: 45vh;
    overflow-y: auto;
}

.es-fp-import-body > p {
    margin: 0 0 16px;
    color: #666;
}

.es-fp-import-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.es-fp-import-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    background: #f8f9fa;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
}

.es-fp-import-info {
    flex: 1;
}

.es-fp-import-info strong {
    display: block;
    font-size: 14px;
}

.es-fp-import-info span {
    font-size: 12px;
    color: #666;
}

.es-fp-import-price input {
    width: 90px;
    padding: 6px 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.es-fp-import-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 20px;
    background: #f8f9fa;
    border-top: 1px solid #e0e0e0;
}

.es-fp-import-selectall {
    display: flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    font-size: 13px;
}

.es-fp-import-actions {
    display: flex;
    gap: 8px;
}
</style>
