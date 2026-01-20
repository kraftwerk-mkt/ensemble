<?php
/**
 * Booking Engine Settings Template
 * 
 * @package Ensemble
 * @subpackage Addons/BookingEngine
 * 
 * Variables available:
 * @var array $settings Settings array (passed from render_settings())
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Ensure $settings has defaults
$settings = wp_parse_args($settings, array(
    'auto_confirm'               => true,
    'auto_send_email'            => true,
    'send_status_emails'         => true,
    'default_capacity'           => 100,
    'max_guests_per_booking'     => 10,
    'require_phone'              => false,
    'enable_waitlist'            => false,
    'waitlist_auto_process'      => true,
    'waitlist_expiry_hours'      => 24,
    'allow_cancellation'         => true,
    'cancellation_deadline_hours' => 24,
    'email_from_name'            => get_bloginfo('name'),
    'email_from_address'         => get_option('admin_email'),
    'admin_notification_email'   => get_option('admin_email'),
    'notify_admin_on_booking'    => false,
));
?>

<div class="es-booking-engine-settings">
    
    <div class="es-addon-settings-grid">
        
        <!-- General Settings -->
        <div class="es-addon-settings-section">
            <h4><?php _e('General Settings', 'ensemble'); ?></h4>
            
            <div class="es-toggle-group">
                <label class="es-toggle">
                    <input type="checkbox" 
                           name="auto_confirm" 
                           value="1" 
                           <?php checked($settings['auto_confirm']); ?>>
                    <span class="es-toggle-track"></span>
                    <span class="es-toggle-label"><?php _e('Auto-confirm bookings', 'ensemble'); ?></span>
                </label>
                
                <label class="es-toggle">
                    <input type="checkbox" 
                           name="auto_send_email" 
                           value="1" 
                           <?php checked($settings['auto_send_email']); ?>>
                    <span class="es-toggle-track"></span>
                    <span class="es-toggle-label"><?php _e('Send confirmation emails', 'ensemble'); ?></span>
                </label>
                
                <label class="es-toggle">
                    <input type="checkbox" 
                           name="send_status_emails" 
                           value="1" 
                           <?php checked($settings['send_status_emails']); ?>>
                    <span class="es-toggle-track"></span>
                    <span class="es-toggle-label"><?php _e('Send status change emails', 'ensemble'); ?></span>
                </label>
            </div>
        </div>
        
        <!-- Reservation Settings -->
        <div class="es-addon-settings-section">
            <h4><?php _e('Reservation Settings', 'ensemble'); ?></h4>
            
            <div class="es-addon-settings-row">
                <label class="es-addon-settings-label" for="be_default_capacity">
                    <?php _e('Default Capacity', 'ensemble'); ?>
                </label>
                <input type="number" 
                       name="default_capacity" 
                       id="be_default_capacity" 
                       class="es-addon-settings-input"
                       value="<?php echo esc_attr($settings['default_capacity']); ?>" 
                       min="0" 
                       style="width: 100px;">
                <span class="description"><?php _e('(0 = unlimited)', 'ensemble'); ?></span>
            </div>
            
            <div class="es-addon-settings-row">
                <label class="es-addon-settings-label" for="be_max_guests">
                    <?php _e('Max guests per booking', 'ensemble'); ?>
                </label>
                <input type="number" 
                       name="max_guests_per_booking" 
                       id="be_max_guests" 
                       class="es-addon-settings-input"
                       value="<?php echo esc_attr($settings['max_guests_per_booking']); ?>" 
                       min="1" 
                       max="100"
                       style="width: 100px;">
            </div>
            
            <div class="es-toggle-group">
                <label class="es-toggle">
                    <input type="checkbox" 
                           name="require_phone" 
                           value="1" 
                           <?php checked($settings['require_phone']); ?>>
                    <span class="es-toggle-track"></span>
                    <span class="es-toggle-label"><?php _e('Require phone number', 'ensemble'); ?></span>
                </label>
            </div>
        </div>
        
    </div>
    
    <div class="es-addon-settings-grid">
        
        <!-- Waitlist Settings -->
        <div class="es-addon-settings-section">
            <h4><?php _e('Waitlist Settings', 'ensemble'); ?></h4>
            
            <div class="es-toggle-group">
                <label class="es-toggle">
                    <input type="checkbox" 
                           name="enable_waitlist" 
                           value="1" 
                           <?php checked($settings['enable_waitlist']); ?>>
                    <span class="es-toggle-track"></span>
                    <span class="es-toggle-label"><?php _e('Enable waitlist', 'ensemble'); ?></span>
                </label>
                
                <label class="es-toggle">
                    <input type="checkbox" 
                           name="waitlist_auto_process" 
                           value="1" 
                           <?php checked($settings['waitlist_auto_process']); ?>>
                    <span class="es-toggle-track"></span>
                    <span class="es-toggle-label"><?php _e('Auto-notify waitlist', 'ensemble'); ?></span>
                </label>
            </div>
            
            <div class="es-addon-settings-row">
                <label class="es-addon-settings-label" for="be_waitlist_expiry">
                    <?php _e('Waitlist offer expires after', 'ensemble'); ?>
                </label>
                <input type="number" 
                       name="waitlist_expiry_hours" 
                       id="be_waitlist_expiry" 
                       class="es-addon-settings-input"
                       value="<?php echo esc_attr($settings['waitlist_expiry_hours']); ?>" 
                       min="1" 
                       max="168"
                       style="width: 80px;">
                <span class="description"><?php _e('hours', 'ensemble'); ?></span>
            </div>
        </div>
        
        <!-- Cancellation Settings -->
        <div class="es-addon-settings-section">
            <h4><?php _e('Cancellation Settings', 'ensemble'); ?></h4>
            
            <div class="es-toggle-group">
                <label class="es-toggle">
                    <input type="checkbox" 
                           name="allow_cancellation" 
                           value="1" 
                           <?php checked($settings['allow_cancellation']); ?>>
                    <span class="es-toggle-track"></span>
                    <span class="es-toggle-label"><?php _e('Allow customer cancellation', 'ensemble'); ?></span>
                </label>
            </div>
            
            <div class="es-addon-settings-row">
                <label class="es-addon-settings-label" for="be_cancel_deadline">
                    <?php _e('Cancellation deadline', 'ensemble'); ?>
                </label>
                <input type="number" 
                       name="cancellation_deadline_hours" 
                       id="be_cancel_deadline" 
                       class="es-addon-settings-input"
                       value="<?php echo esc_attr($settings['cancellation_deadline_hours']); ?>" 
                       min="0" 
                       max="168"
                       style="width: 80px;">
                <span class="description"><?php _e('hours before event (0 = anytime)', 'ensemble'); ?></span>
            </div>
        </div>
        
    </div>
    
    <!-- Email Settings -->
    <div class="es-addon-settings-section">
        <h4><?php _e('Email Settings', 'ensemble'); ?></h4>
        
        <div class="es-addon-settings-grid">
            <div>
                <div class="es-addon-settings-row">
                    <label class="es-addon-settings-label" for="be_email_from_name">
                        <?php _e('From Name', 'ensemble'); ?>
                    </label>
                    <input type="text" 
                           name="email_from_name" 
                           id="be_email_from_name" 
                           class="es-addon-settings-input"
                           value="<?php echo esc_attr($settings['email_from_name']); ?>"
                           placeholder="<?php echo esc_attr(get_bloginfo('name')); ?>">
                </div>
                
                <div class="es-addon-settings-row">
                    <label class="es-addon-settings-label" for="be_email_from_address">
                        <?php _e('From Email', 'ensemble'); ?>
                    </label>
                    <input type="email" 
                           name="email_from_address" 
                           id="be_email_from_address" 
                           class="es-addon-settings-input"
                           value="<?php echo esc_attr($settings['email_from_address']); ?>"
                           placeholder="<?php echo esc_attr(get_option('admin_email')); ?>">
                </div>
            </div>
            
            <div>
                <div class="es-addon-settings-row">
                    <label class="es-addon-settings-label" for="be_admin_email">
                        <?php _e('Admin notification email', 'ensemble'); ?>
                    </label>
                    <input type="email" 
                           name="admin_notification_email" 
                           id="be_admin_email" 
                           class="es-addon-settings-input"
                           value="<?php echo esc_attr($settings['admin_notification_email']); ?>"
                           placeholder="<?php echo esc_attr(get_option('admin_email')); ?>">
                </div>
                
                <div class="es-toggle-group">
                    <label class="es-toggle">
                        <input type="checkbox" 
                               name="notify_admin_on_booking" 
                               value="1" 
                               <?php checked($settings['notify_admin_on_booking']); ?>>
                        <span class="es-toggle-track"></span>
                        <span class="es-toggle-label"><?php _e('Notify admin on new booking', 'ensemble'); ?></span>
                    </label>
                </div>
            </div>
        </div>
    </div>
    
</div>
