<?php
/**
 * Public Booking Form Template
 * 
 * Variables available:
 * - $event_id (int) - Event ID
 * - $event (WP_Post) - Event post
 * - $type (string) - Booking type: 'reservation' or 'ticket'
 * - $settings (array) - Addon settings
 * - $addon (ES_Booking_Engine_Addon) - Addon instance
 * 
 * @package Ensemble
 * @subpackage Addons/BookingEngine
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get event details
$event_date = get_post_meta($event_id, 'event_date', true);
$event_time = get_post_meta($event_id, 'event_time', true);
$max_guests = $settings['max_guests_per_booking'] ?? 10;
$require_phone = !empty($settings['require_phone']);

// Check availability
$availability = $addon->check_availability($event_id, $type);
$is_available = $availability['available'];
$remaining = $availability['remaining'];

// Check for Floor Plan
$floor_plan_id = get_post_meta($event_id, '_booking_floor_plan_id', true);
$has_floor_plan = false;
$floor_plan = null;

// Debug
error_log('Booking Form - Event ID: ' . $event_id . ', Floor Plan ID: ' . $floor_plan_id);
error_log('Booking Form - ES_Floor_Plan_Addon exists: ' . (class_exists('ES_Floor_Plan_Addon') ? 'YES' : 'NO'));

if ($floor_plan_id && class_exists('ES_Floor_Plan_Addon')) {
    $fp_addon = ES_Floor_Plan_Addon::instance();
    $floor_plan = $fp_addon->get_floor_plan($floor_plan_id);
    $has_floor_plan = !empty($floor_plan);
    error_log('Booking Form - Floor Plan Data: ' . print_r($floor_plan, true));
    error_log('Booking Form - Has Floor Plan: ' . ($has_floor_plan ? 'YES' : 'NO'));
}

// Get booked elements if floor plan exists
$booked_elements = array();
if ($has_floor_plan) {
    $booked_elements = $addon->get_booked_elements($event_id, $floor_plan_id);
}

// Unique form ID for multiple forms on same page
$form_id = 'es-booking-form-' . $event_id;
?>
<div class="es-booking-widget <?php echo $has_floor_plan ? 'es-booking-with-floor-plan' : ''; ?>" 
     data-event-id="<?php echo esc_attr($event_id); ?>"
     data-has-floor-plan="<?php echo $has_floor_plan ? 'true' : 'false'; ?>">
    
    <?php if (!$is_available && empty($settings['enable_waitlist'])): ?>
        <!-- Sold Out -->
        <div class="es-booking-soldout">
            <span class="dashicons dashicons-warning"></span>
            <strong><?php _e('Fully Booked', 'ensemble'); ?></strong>
            <p><?php _e('Unfortunately this event is fully booked.', 'ensemble'); ?></p>
        </div>
    <?php else: ?>
        
        <?php if ($has_floor_plan): ?>
        <!-- Floor Plan Section -->
        <div class="es-booking-floor-plan-section">
            <div class="es-booking-floor-plan-header">
                <h4><?php _e('Select Your Spot', 'ensemble'); ?></h4>
                <p class="es-booking-floor-plan-hint">
                    <?php _e('Click on an available spot to select it', 'ensemble'); ?>
                </p>
            </div>
            
            <div class="es-booking-floor-plan-container" data-floor-plan-id="<?php echo esc_attr($floor_plan_id); ?>">
                <?php 
                // Render floor plan with booking mode
                $shortcode = sprintf(
                    '[ensemble_floor_plan id="%d" event_id="%d" bookable="true" class="es-booking-embedded-floor-plan"]',
                    $floor_plan_id,
                    $event_id
                );
                error_log('Booking Form - Executing shortcode: ' . $shortcode);
                $shortcode_output = do_shortcode($shortcode);
                error_log('Booking Form - Shortcode output length: ' . strlen($shortcode_output));
                if (strlen($shortcode_output) < 500) {
                    error_log('Booking Form - Shortcode output: ' . $shortcode_output);
                }
                echo $shortcode_output;
                ?>
            </div>
            
            <!-- Selected Element Display -->
            <div class="es-booking-selected-element" style="display: none;">
                <div class="es-booking-selected-info">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <span class="es-booking-selected-label"></span>
                </div>
                <button type="button" class="es-booking-change-selection">
                    <?php _e('Change', 'ensemble'); ?>
                </button>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Booking Form -->
        <form id="<?php echo esc_attr($form_id); ?>" class="es-booking-form" data-type="<?php echo esc_attr($type); ?>">
            <?php wp_nonce_field('ensemble_booking_public', 'booking_nonce'); ?>
            <input type="hidden" name="event_id" value="<?php echo esc_attr($event_id); ?>">
            <input type="hidden" name="booking_type" value="<?php echo esc_attr($type); ?>">
            
            <?php if ($has_floor_plan): ?>
            <!-- Floor Plan Hidden Fields -->
            <input type="hidden" name="floor_plan_id" value="">
            <input type="hidden" name="element_id" value="">
            <input type="hidden" name="element_label" value="">
            <input type="hidden" name="element_type" value="">
            <?php endif; ?>
            
            <!-- Event Info Header -->
            <div class="es-booking-header">
                <h4><?php echo esc_html($event->post_title); ?></h4>
                <?php if ($event_date): ?>
                <p class="es-booking-date">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($event_date))); ?>
                    <?php if ($event_time): ?>
                        <span class="es-booking-time"><?php echo esc_html($event_time); ?></span>
                    <?php endif; ?>
                </p>
                <?php endif; ?>
                
                <?php if (!$has_floor_plan && $remaining !== null && $remaining > 0): ?>
                <p class="es-booking-availability">
                    <span class="es-spots-remaining"><?php printf(__('%d spots remaining', 'ensemble'), $remaining); ?></span>
                </p>
                <?php endif; ?>
            </div>
            
            <?php if (!$is_available && !$has_floor_plan): ?>
                <!-- Waitlist Mode -->
                <div class="es-booking-waitlist-notice">
                    <span class="dashicons dashicons-info"></span>
                    <?php _e('This event is fully booked. Join the waitlist to be notified if a spot becomes available.', 'ensemble'); ?>
                </div>
                <input type="hidden" name="waitlist" value="1">
            <?php endif; ?>
            
            <?php if ($has_floor_plan): ?>
            <!-- Floor Plan Required Notice -->
            <div class="es-booking-floor-plan-notice" id="es-floor-plan-notice-<?php echo $event_id; ?>">
                <span class="dashicons dashicons-info-outline"></span>
                <?php _e('Please select a spot from the floor plan above to continue.', 'ensemble'); ?>
            </div>
            <?php endif; ?>
            
            <!-- Form Fields -->
            <div class="es-booking-fields" <?php echo $has_floor_plan ? 'style="display: none;"' : ''; ?>>
                
                <div class="es-field-row">
                    <label for="<?php echo esc_attr($form_id); ?>-name">
                        <?php _e('Name', 'ensemble'); ?> <span class="required">*</span>
                    </label>
                    <input type="text" 
                           id="<?php echo esc_attr($form_id); ?>-name" 
                           name="customer_name" 
                           required 
                           placeholder="<?php esc_attr_e('Your full name', 'ensemble'); ?>">
                </div>
                
                <div class="es-field-row">
                    <label for="<?php echo esc_attr($form_id); ?>-email">
                        <?php _e('Email', 'ensemble'); ?> <span class="required">*</span>
                    </label>
                    <input type="email" 
                           id="<?php echo esc_attr($form_id); ?>-email" 
                           name="customer_email" 
                           required 
                           placeholder="<?php esc_attr_e('your@email.com', 'ensemble'); ?>">
                </div>
                
                <div class="es-field-row">
                    <label for="<?php echo esc_attr($form_id); ?>-phone">
                        <?php _e('Phone', 'ensemble'); ?>
                        <?php if ($require_phone): ?><span class="required">*</span><?php endif; ?>
                    </label>
                    <input type="tel" 
                           id="<?php echo esc_attr($form_id); ?>-phone" 
                           name="customer_phone" 
                           <?php echo $require_phone ? 'required' : ''; ?>
                           placeholder="<?php esc_attr_e('+49 123 456789', 'ensemble'); ?>">
                </div>
                
                <?php if (!$has_floor_plan && $is_available): ?>
                <div class="es-field-row">
                    <label for="<?php echo esc_attr($form_id); ?>-guests">
                        <?php _e('Number of Guests', 'ensemble'); ?>
                    </label>
                    <select id="<?php echo esc_attr($form_id); ?>-guests" name="guests">
                        <?php 
                        $max_selectable = $remaining !== null ? min($max_guests, $remaining) : $max_guests;
                        for ($i = 1; $i <= $max_selectable; $i++): 
                        ?>
                        <option value="<?php echo $i; ?>">
                            <?php echo $i; ?> <?php echo $i === 1 ? __('person', 'ensemble') : __('people', 'ensemble'); ?>
                        </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <?php elseif ($has_floor_plan): ?>
                <!-- For floor plan, guests come from element capacity -->
                <div class="es-field-row es-floor-plan-guests-row">
                    <label for="<?php echo esc_attr($form_id); ?>-guests">
                        <?php _e('Number of Guests', 'ensemble'); ?>
                    </label>
                    <select id="<?php echo esc_attr($form_id); ?>-guests" name="guests">
                        <option value="1">1 <?php _e('person', 'ensemble'); ?></option>
                    </select>
                    <p class="es-field-hint"><?php _e('Available capacity depends on selected spot', 'ensemble'); ?></p>
                </div>
                <?php endif; ?>
                
                <div class="es-field-row">
                    <label for="<?php echo esc_attr($form_id); ?>-notes">
                        <?php _e('Notes', 'ensemble'); ?>
                        <span class="es-field-optional"><?php _e('(optional)', 'ensemble'); ?></span>
                    </label>
                    <textarea id="<?php echo esc_attr($form_id); ?>-notes" 
                              name="customer_notes" 
                              rows="3" 
                              placeholder="<?php esc_attr_e('Special requests, dietary requirements, etc.', 'ensemble'); ?>"></textarea>
                </div>
                
            </div>
            
            <!-- Privacy Notice -->
            <div class="es-booking-privacy" <?php echo $has_floor_plan ? 'style="display: none;"' : ''; ?>>
                <label class="es-checkbox-label">
                    <input type="checkbox" name="privacy_accepted" required>
                    <span>
                        <?php 
                        $privacy_url = get_privacy_policy_url();
                        if ($privacy_url) {
                            printf(
                                __('I accept the %sprivacy policy%s', 'ensemble'),
                                '<a href="' . esc_url($privacy_url) . '" target="_blank">',
                                '</a>'
                            );
                        } else {
                            _e('I accept the privacy policy', 'ensemble');
                        }
                        ?> <span class="required">*</span>
                    </span>
                </label>
            </div>
            
            <!-- Submit -->
            <div class="es-booking-submit" <?php echo $has_floor_plan ? 'style="display: none;"' : ''; ?>>
                <button type="submit" class="es-booking-btn" <?php echo $has_floor_plan ? 'disabled' : ''; ?>>
                    <?php if ($is_available || $has_floor_plan): ?>
                        <span class="dashicons dashicons-yes"></span>
                        <?php echo $type === 'ticket' ? __('Book Now', 'ensemble') : __('Reserve Now', 'ensemble'); ?>
                    <?php else: ?>
                        <span class="dashicons dashicons-list-view"></span>
                        <?php _e('Join Waitlist', 'ensemble'); ?>
                    <?php endif; ?>
                </button>
            </div>
            
            <!-- Messages -->
            <div class="es-booking-messages" style="display: none;">
                <div class="es-booking-success">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <strong><?php _e('Booking Confirmed!', 'ensemble'); ?></strong>
                    <p><?php _e('You will receive a confirmation email shortly.', 'ensemble'); ?></p>
                    <p class="es-confirmation-code"></p>
                </div>
                <div class="es-booking-error">
                    <span class="dashicons dashicons-warning"></span>
                    <strong><?php _e('Error', 'ensemble'); ?></strong>
                    <p class="es-error-message"></p>
                </div>
            </div>
            
        </form>
        
    <?php endif; ?>
    
</div>

<?php if ($has_floor_plan): ?>
<script type="application/json" class="es-booking-booked-elements">
<?php echo wp_json_encode($booked_elements); ?>
</script>
<?php endif; ?>
