<?php
/**
 * Event Wizard - Booking Card
 * 
 * Displayed in Step 4 (Tickets & Price) to configure booking settings
 * Uses existing Wizard CSS patterns (es-status-pills, es-checkbox, etc.)
 * Includes Floor Plan integration
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

// Get available floor plans WITH their linked location
$floor_plans = array();
$floor_plans_by_location = array(); // Indexed by location_id for quick lookup
$floor_plans_data = array(); // For JavaScript

if (class_exists('ES_Floor_Plan_Addon')) {
    $fp_posts = get_posts(array(
        'post_type'      => 'ensemble_floor_plan',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'post_status'    => 'publish',
    ));
    
    foreach ($fp_posts as $fp) {
        $floor_plans[] = $fp;
        
        $linked_location = get_post_meta($fp->ID, '_linked_location', true);
        $fp_data = get_post_meta($fp->ID, '_floor_plan_data', true);
        
        // Count bookable elements
        $bookable_count = 0;
        $total_capacity = 0;
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
                    $total_capacity += intval($element['seats'] ?? $element['capacity'] ?? 0);
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
        
        $plan_info = array(
            'id'              => $fp->ID,
            'name'            => $fp->post_title,
            'location_id'     => $linked_location ? intval($linked_location) : null,
            'bookable_count'  => $bookable_count,
            'total_capacity'  => $total_capacity,
            'elements'        => $elements,
        );
        
        $floor_plans_data[$fp->ID] = $plan_info;
        
        // Index by location for quick lookup
        if ($linked_location) {
            $location_id = intval($linked_location);
            if (!isset($floor_plans_by_location[$location_id])) {
                $floor_plans_by_location[$location_id] = array();
            }
            $floor_plans_by_location[$location_id][] = $plan_info;
        }
    }
}
?>

<!-- Booking Settings Card -->
<style>
/* Booking Mode Pills - Visual Feedback */
.es-booking-mode-pills {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.es-booking-mode-pills .es-status-pill {
    display: inline-flex;
    align-items: center;
    cursor: pointer;
    margin: 0;
}

.es-booking-mode-pills .es-status-pill input[type="radio"] {
    display: none;
}

.es-booking-mode-pills .es-status-pill span {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 16px;
    border: 2px solid var(--es-border, #dcdcde);
    border-radius: 8px;
    background: var(--es-surface, #fff);
    color: var(--es-text-secondary, #646970);
    font-weight: 500;
    transition: all 0.2s ease;
}

.es-booking-mode-pills .es-status-pill span .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
}

.es-booking-mode-pills .es-status-pill:hover span {
    border-color: var(--es-primary, #2271b1);
    color: var(--es-text, #1d2327);
}

.es-booking-mode-pills .es-status-pill input[type="radio"]:checked + span {
    border-color: var(--es-primary, #2271b1);
    background: var(--es-primary-light, #f0f6fc);
    color: var(--es-primary, #2271b1);
}

/* Disabled state */
.es-booking-mode-pills .es-status-pill input[type="radio"][value="none"]:checked + span {
    border-color: var(--es-border, #dcdcde);
    background: var(--es-surface-secondary, #f6f7f7);
    color: var(--es-text-muted, #a7aaad);
}
</style>

<div class="es-form-card">
    <div class="es-form-card-header">
        <div class="es-form-card-icon">
            <span class="dashicons dashicons-tickets-alt"></span>
        </div>
        <div class="es-form-card-title">
            <h3><?php _e('Booking Settings', 'ensemble'); ?></h3>
            <p class="es-form-card-desc"><?php _e('Configure reservations and ticket options', 'ensemble'); ?></p>
        </div>
    </div>
    <div class="es-form-card-body">
        
        <!-- Booking Mode (using es-status-pills pattern) -->
        <div class="es-form-row">
            <label><?php _e('Booking Mode', 'ensemble'); ?></label>
            <div class="es-status-pills es-booking-mode-pills">
                <label class="es-status-pill">
                    <input type="radio" name="booking_mode" value="none" checked>
                    <span><span class="dashicons dashicons-no"></span> <?php _e('Disabled', 'ensemble'); ?></span>
                </label>
                <label class="es-status-pill">
                    <input type="radio" name="booking_mode" value="reservation">
                    <span><span class="dashicons dashicons-groups"></span> <?php echo esc_html($labels['reservation_plural']); ?></span>
                </label>
                <label class="es-status-pill">
                    <input type="radio" name="booking_mode" value="ticket">
                    <span><span class="dashicons dashicons-tickets"></span> <?php _e('Tickets', 'ensemble'); ?></span>
                </label>
            </div>
        </div>
        
        <!-- Reservation Options (shown when mode is reservation or both) -->
        <div id="es-booking-reservation-options" style="display: none; margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--es-border, #e0e0e0);">
            
            <!-- Reservation Types -->
            <div class="es-form-row">
                <label><?php _e('Reservation Types', 'ensemble'); ?></label>
                <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                    <label class="es-checkbox">
                        <input type="checkbox" name="reservation_types[]" value="guestlist" checked>
                        <span class="es-checkbox-box"></span>
                        <span class="es-checkbox-label"><?php _e('Guest List', 'ensemble'); ?></span>
                    </label>
                    <label class="es-checkbox">
                        <input type="checkbox" name="reservation_types[]" value="table">
                        <span class="es-checkbox-box"></span>
                        <span class="es-checkbox-label"><?php _e('Table Reservation', 'ensemble'); ?></span>
                    </label>
                    <label class="es-checkbox">
                        <input type="checkbox" name="reservation_types[]" value="vip">
                        <span class="es-checkbox-box"></span>
                        <span class="es-checkbox-label"><?php _e('VIP', 'ensemble'); ?></span>
                    </label>
                </div>
            </div>
            
            <?php if (!empty($floor_plans)): ?>
            <!-- Floor Plan (automatic from Location) -->
            <div class="es-form-row es-floor-plan-row" style="margin-top: 15px;">
                <label><?php _e('Floor Plan', 'ensemble'); ?></label>
                
                <!-- Floor Plan detected from Location -->
                <div id="es-floor-plan-detected" style="display: none;">
                    <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid #10b981; border-radius: 8px; padding: 12px;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span class="dashicons dashicons-yes-alt" style="color: #10b981; font-size: 20px; width: 20px; height: 20px;"></span>
                            <div>
                                <strong id="es-floor-plan-detected-name"></strong>
                                <span style="display: block; font-size: 12px; color: #666;">
                                    <?php _e('Floor plan active â€“ guests can select seats visually', 'ensemble'); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" id="es-booking-floor-plan" name="booking_floor_plan_id" value="">
                </div>
                
                <!-- No Floor Plan for this Location -->
                <div id="es-floor-plan-not-found" style="display: none;">
                    <div style="background: var(--es-bg-secondary, #f5f5f5); border-radius: 8px; padding: 12px;">
                        <div style="display: flex; align-items: center; gap: 10px; color: var(--es-text-secondary, #666);">
                            <span class="dashicons dashicons-info-outline"></span>
                            <span><?php _e('No floor plan linked to this location.', 'ensemble'); ?></span>
                            <a href="<?php echo admin_url('admin.php?page=ensemble-floor-plans'); ?>" target="_blank" style="margin-left: auto; font-size: 13px;">
                                <?php _e('Create one', 'ensemble'); ?> â†’
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Capacities -->
            <div class="es-form-row" style="margin-top: 15px;">
                <label><?php _e('Capacities', 'ensemble'); ?></label>
                <div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end;">
                    <div id="es-cap-guestlist" style="display: none;">
                        <small style="display: block; color: var(--es-text-secondary, #666); margin-bottom: 4px;"><?php _e('Guest List', 'ensemble'); ?></small>
                        <input type="number" name="reservation_capacity_guestlist" min="0" placeholder="∞" style="width: 80px;">
                    </div>
                    <div id="es-cap-table" style="display: none;">
                        <small style="display: block; color: var(--es-text-secondary, #666); margin-bottom: 4px;"><?php _e('Tables', 'ensemble'); ?></small>
                        <input type="number" name="reservation_capacity_table" min="0" placeholder="∞" style="width: 80px;">
                    </div>
                    <div id="es-cap-vip" style="display: none;">
                        <small style="display: block; color: var(--es-text-secondary, #666); margin-bottom: 4px;"><?php _e('VIP', 'ensemble'); ?></small>
                        <input type="number" name="reservation_capacity_vip" min="0" placeholder="∞" style="width: 80px;">
                    </div>
                    <div style="border-left: 2px solid var(--es-border, #ddd); padding-left: 15px;">
                        <small style="display: block; color: var(--es-text-secondary, #666); margin-bottom: 4px;"><?php _e('Total', 'ensemble'); ?></small>
                        <input type="number" name="reservation_capacity" min="0" placeholder="∞" style="width: 80px;">
                    </div>
                </div>
                <p class="description" style="margin-top: 8px;"><?php _e('Leave empty or 0 for unlimited. If floor plan selected, capacity is managed per element.', 'ensemble'); ?></p>
            </div>
            
            <!-- Max Guests & Deadline -->
            <div style="display: flex; gap: 30px; flex-wrap: wrap; margin-top: 15px;">
                <div class="es-form-row" style="flex: 1; min-width: 150px;">
                    <label for="es-res-max-guests"><?php _e('Max Guests per Booking', 'ensemble'); ?></label>
                    <input type="number" id="es-res-max-guests" name="reservation_max_guests" min="1" max="100" value="10" style="width: 100px;">
                </div>
                <div class="es-form-row" style="flex: 1; min-width: 200px;">
                    <label for="es-res-deadline"><?php _e('Booking Deadline', 'ensemble'); ?></label>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <input type="number" id="es-res-deadline" name="reservation_deadline_hours" min="0" value="24" style="width: 80px;">
                        <span style="color: var(--es-text-secondary, #666);"><?php _e('hours before event', 'ensemble'); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Auto Confirm -->
            <div class="es-form-row" style="margin-top: 15px;">
                <label class="es-checkbox">
                    <input type="checkbox" name="reservation_auto_confirm" value="1">
                    <span class="es-checkbox-box"></span>
                    <span class="es-checkbox-label"><?php _e('Auto-confirm reservations (no manual approval needed)', 'ensemble'); ?></span>
                </label>
            </div>
            
        </div>
        
        <?php 
        // Hide "Ticket Mode" section when Tickets Pro is active
        // Tickets Pro provides its own comprehensive paid ticket management
        $tickets_pro_active = class_exists('ES_Tickets_Pro_Addon');
        if (!$tickets_pro_active): 
        ?>
        <!-- Ticket Options (shown when mode is ticket or both, hidden when Tickets Pro is active) -->
        <div id="es-booking-ticket-options" style="display: none; margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--es-border, #e0e0e0);">
            
            <div class="es-form-row">
                <label><?php _e('Ticket Mode', 'ensemble'); ?></label>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <label class="es-radio-label">
                        <input type="radio" name="reservation_ticket_mode" value="none" checked>
                        <?php _e('No tickets (info only)', 'ensemble'); ?>
                    </label>
                    <label class="es-radio-label">
                        <input type="radio" name="reservation_ticket_mode" value="affiliate">
                        <?php _e('External ticket link (use Ticket URL field above)', 'ensemble'); ?>
                    </label>
                </div>
            </div>
            
        </div>
        <?php endif; ?>
        
        <!-- Booking Stats (shown for existing events with bookings enabled) -->
        <div id="es-booking-stats-row" style="display: none; margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--es-border, #e0e0e0);">
            <div style="display: flex; align-items: center; gap: 24px; flex-wrap: wrap;">
                <div style="text-align: center;">
                    <span id="es-booking-stat-total" style="display: block; font-size: 24px; font-weight: 600;">0</span>
                    <small style="color: var(--es-text-secondary, #666);"><?php _e('Total', 'ensemble'); ?></small>
                </div>
                <div style="text-align: center;">
                    <span id="es-booking-stat-confirmed" style="display: block; font-size: 24px; font-weight: 600; color: var(--es-success, #10b981);">0</span>
                    <small style="color: var(--es-text-secondary, #666);"><?php _e('Confirmed', 'ensemble'); ?></small>
                </div>
                <div style="text-align: center;">
                    <span id="es-booking-stat-pending" style="display: block; font-size: 24px; font-weight: 600; color: var(--es-warning, #f59e0b);">0</span>
                    <small style="color: var(--es-text-secondary, #666);"><?php _e('Pending', 'ensemble'); ?></small>
                </div>
                <div style="text-align: center;">
                    <span id="es-booking-stat-checkedin" style="display: block; font-size: 24px; font-weight: 600; color: var(--es-info, #3582c4);">0</span>
                    <small style="color: var(--es-text-secondary, #666);"><?php _e('Checked In', 'ensemble'); ?></small>
                </div>
                <a href="#" id="es-booking-manage-link" target="_blank" style="margin-left: auto; display: flex; align-items: center; gap: 4px; color: var(--es-primary, #3582c4); text-decoration: none;">
                    <span class="dashicons dashicons-external" style="font-size: 16px; width: 16px; height: 16px;"></span>
                    <?php _e('Manage Bookings', 'ensemble'); ?>
                </a>
            </div>
        </div>
        
    </div>
</div>
<script>
// Initialize booking card immediately after template is rendered
jQuery(function($) {
    
    // Floor Plan data by location (from PHP)
    var floorPlansByLocation = <?php echo json_encode($floor_plans_by_location); ?>;
    var currentFloorPlan = null;
    
    // =====================================
    // FLOOR PLAN LOCATION DETECTION
    // =====================================
    
    function checkLocationFloorPlan() {
        var locationId = null;
        
        // Try to get location from wizard
        if (typeof ensembleWizard !== 'undefined') {
            if (ensembleWizard.vm && ensembleWizard.vm.event) {
                locationId = ensembleWizard.vm.event.location;
            } else if (ensembleWizard.currentEvent) {
                locationId = ensembleWizard.currentEvent.location;
            }
        }
        
        // Also check selected pill
        if (!locationId) {
            var $selectedLocation = $('input[name="event_location"]:checked');
            if ($selectedLocation.length) {
                locationId = $selectedLocation.val();
            }
        }
        
        var $detected = $('#es-floor-plan-detected');
        var $notFound = $('#es-floor-plan-not-found');
        
        if (locationId && floorPlansByLocation[locationId] && floorPlansByLocation[locationId].length > 0) {
            // Floor plan found for this location
            currentFloorPlan = floorPlansByLocation[locationId][0];
            
            $('#es-floor-plan-detected-name').text(currentFloorPlan.name);
            $('#es-booking-floor-plan').val(currentFloorPlan.id);
            
            $detected.show();
            $notFound.hide();
            
        } else if (locationId) {
            // Location selected but no floor plan
            currentFloorPlan = null;
            $('#es-booking-floor-plan').val('');
            
            $detected.hide();
            $notFound.show();
        } else {
            // No location selected
            currentFloorPlan = null;
            $detected.hide();
            $notFound.hide();
        }
    }
    
    // Watch for location changes
    $('input[name="event_location"]').on('change', function() {
        checkLocationFloorPlan();
    });
    
    // Also watch via Vue if available
    function setupVueWatcher() {
        if (typeof ensembleWizard !== 'undefined' && ensembleWizard.vm) {
            ensembleWizard.vm.$watch('event.location', function() {
                checkLocationFloorPlan();
            });
        }
    }
    setTimeout(setupVueWatcher, 500);
    
    // Initial check
    setTimeout(checkLocationFloorPlan, 100);
    
    // =====================================
    // BOOKING MODE
    // =====================================
    
    $('input[name="booking_mode"]').on('change', function() {
        var mode = $(this).val();
        
        if (mode === 'reservation') {
            $('#es-booking-reservation-options').slideDown(200);
            updateCapacityFields();
            checkLocationFloorPlan();
        } else {
            $('#es-booking-reservation-options').slideUp(200);
        }
        
        // Trigger Tickets Pro card visibility update
        $(document).trigger('change', '[name="booking_mode"]');
    });
    
    // Reservation types change handler
    $('input[name="reservation_types[]"]').on('change', function() {
        updateCapacityFields();
        
        // Auto-show floor plan row when table is selected
        var tableChecked = $('input[name="reservation_types[]"][value="table"]').is(':checked');
        $('.es-floor-plan-row').toggle(tableChecked);
    });
    
    function updateCapacityFields() {
        var types = [];
        $('input[name="reservation_types[]"]:checked').each(function() {
            types.push($(this).val());
        });
        
        $('#es-cap-guestlist').toggle(types.indexOf('guestlist') !== -1);
        $('#es-cap-table').toggle(types.indexOf('table') !== -1);
        $('#es-cap-vip').toggle(types.indexOf('vip') !== -1);
    }
    
    // Initial update
    updateCapacityFields();
    
    // Show floor plan row only if table type is checked
    var tableChecked = $('input[name="reservation_types[]"][value="table"]').is(':checked');
    $('.es-floor-plan-row').toggle(tableChecked);
});
</script>
