<?php
/**
 * Event Wizard - Booking Tab
 * 
 * Displayed in the Event Wizard to configure booking settings per event
 * 
 * @package Ensemble
 * @subpackage Addons/BookingEngine
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get dynamic labels
$labels = function_exists('ensemble_get_dynamic_labels') ? ensemble_get_dynamic_labels() : array(
    'reservation_singular' => __('Reservation', 'ensemble'),
    'reservation_plural'   => __('Reservations', 'ensemble'),
);

// Get available floor plans
$floor_plans_data = array();
$floor_plans_available = false;

if (class_exists('ES_Floor_Plan_Addon')) {
    $floor_plans_posts = get_posts(array(
        'post_type'      => 'ensemble_floor_plan',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'post_status'    => 'publish',
    ));
    
    if (!empty($floor_plans_posts)) {
        $floor_plans_available = true;
        foreach ($floor_plans_posts as $fp) {
            $floor_plans_data[] = array(
                'id'   => $fp->ID,
                'name' => $fp->post_title,
            );
        }
    }
}
?>

<!-- Initialize Floor Plans Data for Vue.js -->
<script>
(function() {
    // Wait for ensembleWizard to be available
    function initFloorPlans() {
        if (typeof ensembleWizard !== 'undefined' && ensembleWizard.vm) {
            // Add floor plans to Vue instance
            ensembleWizard.vm.floorPlans = <?php echo json_encode($floor_plans_data); ?>;
            ensembleWizard.vm.floorPlansAvailable = <?php echo $floor_plans_available ? 'true' : 'false'; ?>;
            
            // Ensure booking_floor_plan_id exists in event data
            if (typeof ensembleWizard.vm.event.booking_floor_plan_id === 'undefined') {
                ensembleWizard.vm.$set(ensembleWizard.vm.event, 'booking_floor_plan_id', '');
            }
            
            // Ensure reservation_types is an array
            if (!Array.isArray(ensembleWizard.vm.event.reservation_types)) {
                ensembleWizard.vm.$set(ensembleWizard.vm.event, 'reservation_types', ['guestlist']);
            }
        } else if (typeof ensembleWizard !== 'undefined') {
            // Add to localize data if vm not ready yet
            ensembleWizard.floorPlans = <?php echo json_encode($floor_plans_data); ?>;
            ensembleWizard.floorPlansAvailable = <?php echo $floor_plans_available ? 'true' : 'false'; ?>;
        }
    }
    
    // Try immediately
    initFloorPlans();
    
    // Also try after DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFloorPlans);
    }
    
    // And after a short delay for Vue initialization
    setTimeout(initFloorPlans, 100);
    setTimeout(initFloorPlans, 500);
})();
</script>

<div class="es-wizard-tab-content" id="wizard-booking-content">
    
    <!-- Booking Mode Selection -->
    <div class="es-wizard-section">
        <div class="es-wizard-section-header">
            <h3>
                <span class="dashicons dashicons-tickets-alt"></span>
                <?php _e('Booking Mode', 'ensemble'); ?>
            </h3>
        </div>
        
        <div class="es-booking-mode-grid">
            <label class="es-booking-mode-option">
                <input type="radio" name="booking_mode" value="none" v-model="event.booking_mode">
                <div class="es-mode-card">
                    <span class="dashicons dashicons-no"></span>
                    <strong><?php _e('Disabled', 'ensemble'); ?></strong>
                    <p><?php _e('No reservations or tickets', 'ensemble'); ?></p>
                </div>
            </label>
            
            <label class="es-booking-mode-option">
                <input type="radio" name="booking_mode" value="reservation" v-model="event.booking_mode">
                <div class="es-mode-card">
                    <span class="dashicons dashicons-groups"></span>
                    <strong><?php echo esc_html($labels['reservation_plural']); ?></strong>
                    <p><?php _e('Guest list, table reservations', 'ensemble'); ?></p>
                </div>
            </label>
            
            <label class="es-booking-mode-option">
                <input type="radio" name="booking_mode" value="ticket" v-model="event.booking_mode">
                <div class="es-mode-card">
                    <span class="dashicons dashicons-tickets"></span>
                    <strong><?php _e('Tickets', 'ensemble'); ?></strong>
                    <p><?php _e('Paid ticket sales', 'ensemble'); ?></p>
                </div>
            </label>
            
            <label class="es-booking-mode-option">
                <input type="radio" name="booking_mode" value="both" v-model="event.booking_mode">
                <div class="es-mode-card">
                    <span class="dashicons dashicons-admin-multisite"></span>
                    <strong><?php _e('Both', 'ensemble'); ?></strong>
                    <p><?php _e('Reservations and tickets', 'ensemble'); ?></p>
                </div>
            </label>
        </div>
    </div>
    
    <!-- Reservation Settings -->
    <div class="es-wizard-section" v-if="event.booking_mode === 'reservation' || event.booking_mode === 'both'">
        <div class="es-wizard-section-header">
            <h3>
                <span class="dashicons dashicons-groups"></span>
                <?php echo esc_html($labels['reservation_plural']); ?>
            </h3>
        </div>
        
        <div class="es-wizard-form-grid">
            
            <!-- Reservation Types -->
            <div class="es-form-row es-form-row-full">
                <label class="es-form-label"><?php _e('Available Types', 'ensemble'); ?></label>
                <div class="es-checkbox-group">
                    <label class="es-checkbox-inline">
                        <input type="checkbox" value="guestlist" v-model="event.reservation_types">
                        <span><?php _e('Guest List', 'ensemble'); ?></span>
                    </label>
                    <label class="es-checkbox-inline">
                        <input type="checkbox" value="table" v-model="event.reservation_types">
                        <span><?php _e('Table Reservation', 'ensemble'); ?></span>
                    </label>
                    <label class="es-checkbox-inline">
                        <input type="checkbox" value="vip" v-model="event.reservation_types">
                        <span><?php _e('VIP', 'ensemble'); ?></span>
                    </label>
                </div>
            </div>
            
            <!-- Capacity per Type -->
            <div class="es-form-row" v-if="event.reservation_types && event.reservation_types.includes('guestlist')">
                <label class="es-form-label" for="res_cap_guestlist">
                    <?php _e('Guest List Capacity', 'ensemble'); ?>
                </label>
                <input type="number" 
                       id="res_cap_guestlist" 
                       v-model="event.reservation_capacity_guestlist" 
                       min="0" 
                       placeholder="<?php esc_attr_e('Unlimited', 'ensemble'); ?>"
                       class="es-form-input es-input-sm">
            </div>
            
            <div class="es-form-row" v-if="event.reservation_types && event.reservation_types.includes('table')">
                <label class="es-form-label" for="res_cap_table">
                    <?php _e('Table Capacity', 'ensemble'); ?>
                </label>
                <input type="number" 
                       id="res_cap_table" 
                       v-model="event.reservation_capacity_table" 
                       min="0" 
                       placeholder="<?php esc_attr_e('Unlimited', 'ensemble'); ?>"
                       class="es-form-input es-input-sm">
            </div>
            
            <div class="es-form-row" v-if="event.reservation_types && event.reservation_types.includes('vip')">
                <label class="es-form-label" for="res_cap_vip">
                    <?php _e('VIP Capacity', 'ensemble'); ?>
                </label>
                <input type="number" 
                       id="res_cap_vip" 
                       v-model="event.reservation_capacity_vip" 
                       min="0" 
                       placeholder="<?php esc_attr_e('Unlimited', 'ensemble'); ?>"
                       class="es-form-input es-input-sm">
            </div>
            
            <!-- Total Capacity -->
            <div class="es-form-row">
                <label class="es-form-label" for="res_capacity">
                    <?php _e('Total Capacity', 'ensemble'); ?>
                </label>
                <input type="number" 
                       id="res_capacity" 
                       v-model="event.reservation_capacity" 
                       min="0" 
                       placeholder="<?php esc_attr_e('Unlimited', 'ensemble'); ?>"
                       class="es-form-input es-input-sm">
                <p class="es-form-hint"><?php _e('0 or empty = unlimited', 'ensemble'); ?></p>
            </div>
            
            <!-- Max Guests per Booking -->
            <div class="es-form-row">
                <label class="es-form-label" for="res_max_guests">
                    <?php _e('Max Guests per Booking', 'ensemble'); ?>
                </label>
                <input type="number" 
                       id="res_max_guests" 
                       v-model="event.reservation_max_guests" 
                       min="1" 
                       max="100"
                       placeholder="10"
                       class="es-form-input es-input-sm">
            </div>
            
            <!-- Deadline -->
            <div class="es-form-row">
                <label class="es-form-label" for="res_deadline">
                    <?php _e('Booking Deadline', 'ensemble'); ?>
                </label>
                <div class="es-input-group">
                    <input type="number" 
                           id="res_deadline" 
                           v-model="event.reservation_deadline_hours" 
                           min="0" 
                           placeholder="24"
                           class="es-form-input es-input-sm">
                    <span class="es-input-suffix"><?php _e('hours before event', 'ensemble'); ?></span>
                </div>
            </div>
            
            <!-- Auto Confirm -->
            <div class="es-form-row es-form-row-full">
                <label class="es-toggle">
                    <input type="checkbox" v-model="event.reservation_auto_confirm">
                    <span class="es-toggle-track"></span>
                    <span class="es-toggle-label"><?php _e('Auto-confirm reservations', 'ensemble'); ?></span>
                </label>
            </div>
            
        </div>
    </div>
    
    <!-- Ticket Settings (for future Tickets Pro addon) -->
    <div class="es-wizard-section" v-if="event.booking_mode === 'ticket' || event.booking_mode === 'both'">
        <div class="es-wizard-section-header">
            <h3>
                <span class="dashicons dashicons-tickets"></span>
                <?php _e('Ticket Settings', 'ensemble'); ?>
            </h3>
        </div>
        
        <div class="es-wizard-info-box">
            <span class="dashicons dashicons-info"></span>
            <div>
                <strong><?php _e('Coming Soon: Tickets Pro', 'ensemble'); ?></strong>
                <p><?php _e('Full ticket sales with payment processing will be available in the Tickets Pro addon.', 'ensemble'); ?></p>
            </div>
        </div>
        
        <!-- Ticket Mode Selection -->
        <div class="es-form-row es-form-row-full">
            <label class="es-form-label"><?php _e('Ticket Mode', 'ensemble'); ?></label>
            <div class="es-radio-group">
                <label class="es-radio-inline">
                    <input type="radio" value="none" v-model="event.reservation_ticket_mode">
                    <span><?php _e('No Tickets', 'ensemble'); ?></span>
                </label>
                <label class="es-radio-inline">
                    <input type="radio" value="affiliate" v-model="event.reservation_ticket_mode">
                    <span><?php _e('Affiliate Links', 'ensemble'); ?></span>
                </label>
                <label class="es-radio-inline" title="<?php esc_attr_e('Requires Tickets Pro addon', 'ensemble'); ?>" style="opacity: 0.5;">
                    <input type="radio" value="self" disabled>
                    <span><?php _e('Self-Hosted (Pro)', 'ensemble'); ?></span>
                </label>
            </div>
        </div>
    </div>
    
    <!-- Floor Plan Selection (if Floor Plan Pro is active) -->
    <div class="es-wizard-section" v-if="floorPlansAvailable && (event.booking_mode === 'reservation' || event.booking_mode === 'both')">
        <div class="es-wizard-section-header">
            <h3>
                <span class="dashicons dashicons-layout"></span>
                <?php _e('Floor Plan', 'ensemble'); ?>
            </h3>
        </div>
        
        <div class="es-form-row">
            <label class="es-form-label" for="res_floor_plan">
                <?php _e('Select Floor Plan', 'ensemble'); ?>
            </label>
            <select id="res_floor_plan" v-model="event.booking_floor_plan_id" class="es-form-select">
                <option value=""><?php _e('No Floor Plan', 'ensemble'); ?></option>
                <option v-for="plan in floorPlans" :value="plan.id">{{ plan.name }}</option>
            </select>
        </div>
    </div>
    
    <!-- Booking Summary / Stats -->
    <div class="es-wizard-section" v-if="event.id && (event.booking_mode === 'reservation' || event.booking_mode === 'both')">
        <div class="es-wizard-section-header">
            <h3>
                <span class="dashicons dashicons-chart-bar"></span>
                <?php _e('Current Bookings', 'ensemble'); ?>
            </h3>
        </div>
        
        <div class="es-booking-stats-mini">
            <div class="es-stat-mini">
                <span class="es-stat-mini-value">{{ bookingStats.total || 0 }}</span>
                <span class="es-stat-mini-label"><?php _e('Total', 'ensemble'); ?></span>
            </div>
            <div class="es-stat-mini">
                <span class="es-stat-mini-value es-success">{{ bookingStats.confirmed || 0 }}</span>
                <span class="es-stat-mini-label"><?php _e('Confirmed', 'ensemble'); ?></span>
            </div>
            <div class="es-stat-mini">
                <span class="es-stat-mini-value es-warning">{{ bookingStats.pending || 0 }}</span>
                <span class="es-stat-mini-label"><?php _e('Pending', 'ensemble'); ?></span>
            </div>
            <div class="es-stat-mini">
                <span class="es-stat-mini-value es-info">{{ bookingStats.checked_in || 0 }}</span>
                <span class="es-stat-mini-label"><?php _e('Checked In', 'ensemble'); ?></span>
            </div>
        </div>
        
        <a :href="'<?php echo admin_url('admin.php?page=ensemble-bookings&event_id='); ?>' + event.id" 
           class="button button-secondary" 
           target="_blank">
            <span class="dashicons dashicons-external"></span>
            <?php _e('Manage Bookings', 'ensemble'); ?>
        </a>
    </div>
    
</div>

<style>
/* Booking Mode Grid */
.es-booking-mode-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: var(--es-spacing-md, 16px);
}

.es-booking-mode-option {
    cursor: pointer;
}

.es-booking-mode-option input {
    display: none;
}

.es-mode-card {
    padding: var(--es-spacing-lg, 20px);
    background: var(--es-surface-secondary, #f8f9fa);
    border: 2px solid var(--es-border, #e0e0e0);
    border-radius: var(--es-radius-lg, 12px);
    text-align: center;
    transition: var(--es-transition, all 0.2s ease);
}

.es-booking-mode-option input:checked + .es-mode-card {
    background: var(--es-primary-light, rgba(53, 130, 196, 0.1));
    border-color: var(--es-primary, #3582c4);
}

.es-mode-card .dashicons {
    font-size: 32px;
    width: 32px;
    height: 32px;
    color: var(--es-text-muted, #888);
    margin-bottom: 8px;
}

.es-booking-mode-option input:checked + .es-mode-card .dashicons {
    color: var(--es-primary, #3582c4);
}

.es-mode-card strong {
    display: block;
    font-size: 14px;
    color: var(--es-text, #1a1a1a);
    margin-bottom: 4px;
}

.es-mode-card p {
    margin: 0;
    font-size: 12px;
    color: var(--es-text-secondary, #666);
}

/* Checkbox Group */
.es-checkbox-group {
    display: flex;
    flex-wrap: wrap;
    gap: var(--es-spacing-md, 16px);
}

.es-checkbox-inline,
.es-radio-inline {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}

.es-checkbox-inline input,
.es-radio-inline input {
    margin: 0;
}

/* Input Group */
.es-input-group {
    display: flex;
    align-items: center;
    gap: 8px;
}

.es-input-suffix {
    font-size: 13px;
    color: var(--es-text-secondary, #666);
}

/* Info Box */
.es-wizard-info-box {
    display: flex;
    align-items: flex-start;
    gap: var(--es-spacing-md, 16px);
    padding: var(--es-spacing-md, 16px);
    background: var(--es-info-light, rgba(53, 130, 196, 0.1));
    border-radius: var(--es-radius, 8px);
    margin-bottom: var(--es-spacing-lg, 24px);
}

.es-wizard-info-box .dashicons {
    color: var(--es-info, #3582c4);
    flex-shrink: 0;
}

.es-wizard-info-box strong {
    display: block;
    margin-bottom: 4px;
}

.es-wizard-info-box p {
    margin: 0;
    font-size: 13px;
    color: var(--es-text-secondary, #666);
}

/* Mini Stats */
.es-booking-stats-mini {
    display: flex;
    gap: var(--es-spacing-lg, 24px);
    margin-bottom: var(--es-spacing-lg, 24px);
}

.es-stat-mini {
    text-align: center;
}

.es-stat-mini-value {
    display: block;
    font-size: 24px;
    font-weight: 600;
    color: var(--es-text, #1a1a1a);
}

.es-stat-mini-value.es-success { color: var(--es-success, #10b981); }
.es-stat-mini-value.es-warning { color: var(--es-warning, #f59e0b); }
.es-stat-mini-value.es-info { color: var(--es-info, #3582c4); }

.es-stat-mini-label {
    font-size: 12px;
    color: var(--es-text-secondary, #666);
}
</style>
