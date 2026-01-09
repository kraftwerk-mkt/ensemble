<?php
/**
 * Reservations Pro - Settings Template
 * 
 * Modern Section/Toggle Design Pattern
 * 
 * @package Ensemble
 * @subpackage Addons/Reservations Pro
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="es-reservations-settings">
    
    <!-- Email Notifications Section -->
    <div class="es-settings-section">
        <h3><?php _e('Email Notifications', 'ensemble'); ?></h3>
        
        <div class="es-setting-row">
            <div class="es-setting-info">
                <span class="es-setting-title"><?php _e('Admin Notifications', 'ensemble'); ?></span>
                <span class="es-setting-desc"><?php _e('Receive email notifications for new reservations', 'ensemble'); ?></span>
            </div>
            <label class="es-toggle-wrapper">
                <input type="checkbox" name="admin_notifications" value="1" 
                       <?php checked($settings['admin_notifications'] ?? true, true); ?>>
                <span class="es-toggle-switch"></span>
            </label>
        </div>
        
        <div class="es-setting-row">
            <div class="es-setting-info">
                <span class="es-setting-title"><?php _e('Notification Email', 'ensemble'); ?></span>
                <span class="es-setting-desc"><?php _e('Email address for reservation notifications', 'ensemble'); ?></span>
            </div>
            <div class="es-setting-control">
                <input type="email" name="notification_email" 
                       value="<?php echo esc_attr($settings['notification_email'] ?? get_option('admin_email')); ?>"
                       class="es-input-wide" 
                       placeholder="<?php echo esc_attr(get_option('admin_email')); ?>">
            </div>
        </div>
        
        <div class="es-setting-row">
            <div class="es-setting-info">
                <span class="es-setting-title"><?php _e('Guest Confirmation', 'ensemble'); ?></span>
                <span class="es-setting-desc"><?php _e('Send confirmation email to guests after booking', 'ensemble'); ?></span>
            </div>
            <label class="es-toggle-wrapper">
                <input type="checkbox" name="guest_confirmation" value="1" 
                       <?php checked($settings['guest_confirmation'] ?? true, true); ?>>
                <span class="es-toggle-switch"></span>
            </label>
        </div>
    </div>
    
    <!-- Reminders Section -->
    <div class="es-settings-section">
        <h3><?php _e('Reminders', 'ensemble'); ?></h3>
        
        <div class="es-setting-row">
            <div class="es-setting-info">
                <span class="es-setting-title"><?php _e('Send Reminder Emails', 'ensemble'); ?></span>
                <span class="es-setting-desc"><?php _e('Automatically remind guests before the event', 'ensemble'); ?></span>
            </div>
            <label class="es-toggle-wrapper">
                <input type="checkbox" name="send_reminders" value="1" 
                       <?php checked($settings['send_reminders'] ?? false, true); ?>>
                <span class="es-toggle-switch"></span>
            </label>
        </div>
        
        <div class="es-setting-row">
            <div class="es-setting-info">
                <span class="es-setting-title"><?php _e('Reminder Timing', 'ensemble'); ?></span>
                <span class="es-setting-desc"><?php _e('When to send the reminder before the event', 'ensemble'); ?></span>
            </div>
            <div class="es-setting-control">
                <div class="es-input-group">
                    <input type="number" name="reminder_hours" 
                           value="<?php echo esc_attr($settings['reminder_hours'] ?? 24); ?>"
                           min="1" max="168" class="es-input-small">
                    <span class="es-input-suffix"><?php _e('hours before', 'ensemble'); ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Default Settings Section -->
    <div class="es-settings-section">
        <h3><?php _e('Default Settings', 'ensemble'); ?></h3>
        
        <div class="es-setting-row">
            <div class="es-setting-info">
                <span class="es-setting-title"><?php _e('Default Capacity', 'ensemble'); ?></span>
                <span class="es-setting-desc"><?php _e('Maximum guests per event (0 = unlimited)', 'ensemble'); ?></span>
            </div>
            <div class="es-setting-control">
                <div class="es-input-group">
                    <input type="number" name="default_capacity" 
                           value="<?php echo esc_attr($settings['default_capacity'] ?? 0); ?>"
                           min="0" class="es-input-small" placeholder="0">
                    <span class="es-input-suffix"><?php _e('guests', 'ensemble'); ?></span>
                </div>
            </div>
        </div>
        
        <div class="es-setting-row">
            <div class="es-setting-info">
                <span class="es-setting-title"><?php _e('Max Guests per Reservation', 'ensemble'); ?></span>
                <span class="es-setting-desc"><?php _e('Maximum number of guests per single reservation', 'ensemble'); ?></span>
            </div>
            <div class="es-setting-control">
                <div class="es-input-group">
                    <input type="number" name="max_guests_per_reservation" 
                           value="<?php echo esc_attr($settings['max_guests_per_reservation'] ?? 10); ?>"
                           min="1" max="50" class="es-input-small">
                    <span class="es-input-suffix"><?php _e('guests', 'ensemble'); ?></span>
                </div>
            </div>
        </div>
        
        <div class="es-setting-row">
            <div class="es-setting-info">
                <span class="es-setting-title"><?php _e('Phone Number Required', 'ensemble'); ?></span>
                <span class="es-setting-desc"><?php _e('Require phone number for reservations', 'ensemble'); ?></span>
            </div>
            <label class="es-toggle-wrapper">
                <input type="checkbox" name="require_phone" value="1" 
                       <?php checked($settings['require_phone'] ?? false, true); ?>>
                <span class="es-toggle-switch"></span>
            </label>
        </div>
        
        <div class="es-setting-row">
            <div class="es-setting-info">
                <span class="es-setting-title"><?php _e('Auto-Confirm Reservations', 'ensemble'); ?></span>
                <span class="es-setting-desc"><?php _e('Automatically confirm new reservations', 'ensemble'); ?></span>
            </div>
            <label class="es-toggle-wrapper">
                <input type="checkbox" name="auto_confirm" value="1" 
                       <?php checked($settings['auto_confirm'] ?? true, true); ?>>
                <span class="es-toggle-switch"></span>
            </label>
        </div>
    </div>
    
    <!-- Reservation Types Section -->
    <div class="es-settings-section">
        <h3><?php _e('Reservation Types', 'ensemble'); ?></h3>
        
        <div class="es-setting-row">
            <div class="es-setting-info">
                <span class="es-setting-title"><?php _e('Enable Guestlist', 'ensemble'); ?></span>
                <span class="es-setting-desc"><?php _e('Standard guestlist reservations', 'ensemble'); ?></span>
            </div>
            <label class="es-toggle-wrapper">
                <input type="checkbox" name="enable_guestlist" value="1" 
                       <?php checked($settings['enable_guestlist'] ?? true, true); ?>>
                <span class="es-toggle-switch"></span>
            </label>
        </div>
        
        <div class="es-setting-row">
            <div class="es-setting-info">
                <span class="es-setting-title"><?php _e('Enable Table Reservations', 'ensemble'); ?></span>
                <span class="es-setting-desc"><?php _e('Allow guests to reserve tables', 'ensemble'); ?></span>
            </div>
            <label class="es-toggle-wrapper">
                <input type="checkbox" name="enable_table" value="1" 
                       <?php checked($settings['enable_table'] ?? true, true); ?>>
                <span class="es-toggle-switch"></span>
            </label>
        </div>
        
        <div class="es-setting-row">
            <div class="es-setting-info">
                <span class="es-setting-title"><?php _e('Enable VIP Reservations', 'ensemble'); ?></span>
                <span class="es-setting-desc"><?php _e('Special VIP booking option', 'ensemble'); ?></span>
            </div>
            <label class="es-toggle-wrapper">
                <input type="checkbox" name="enable_vip" value="1" 
                       <?php checked($settings['enable_vip'] ?? true, true); ?>>
                <span class="es-toggle-switch"></span>
            </label>
        </div>
    </div>
    
    <!-- Form Customization Section -->
    <div class="es-settings-section">
        <h3><?php _e('Form Customization', 'ensemble'); ?></h3>
        
        <div class="es-setting-row">
            <div class="es-setting-info">
                <span class="es-setting-title"><?php _e('Submit Button Text', 'ensemble'); ?></span>
                <span class="es-setting-desc"><?php _e('Custom text for the reservation button', 'ensemble'); ?></span>
            </div>
            <div class="es-setting-control">
                <input type="text" name="button_text" 
                       value="<?php echo esc_attr($settings['button_text'] ?? __('Reserve Now', 'ensemble')); ?>"
                       class="es-input-wide" 
                       placeholder="<?php esc_attr_e('Reserve Now', 'ensemble'); ?>">
            </div>
        </div>
        
        <div class="es-setting-row">
            <div class="es-setting-info">
                <span class="es-setting-title"><?php _e('Success Message', 'ensemble'); ?></span>
                <span class="es-setting-desc"><?php _e('Message shown after successful reservation', 'ensemble'); ?></span>
            </div>
            <div class="es-setting-control">
                <textarea name="success_message" rows="2" class="es-input-wide"
                          placeholder="<?php esc_attr_e('Thank you! Your reservation has been confirmed.', 'ensemble'); ?>"><?php echo esc_textarea($settings['success_message'] ?? ''); ?></textarea>
            </div>
        </div>
        
        <div class="es-setting-row">
            <div class="es-setting-info">
                <span class="es-setting-title"><?php _e('Privacy Notice', 'ensemble'); ?></span>
                <span class="es-setting-desc"><?php _e('Display privacy checkbox with custom text', 'ensemble'); ?></span>
            </div>
            <label class="es-toggle-wrapper">
                <input type="checkbox" name="show_privacy" value="1" 
                       <?php checked($settings['show_privacy'] ?? true, true); ?>>
                <span class="es-toggle-switch"></span>
            </label>
        </div>
    </div>
    
    <!-- Shortcodes Section -->
    <div class="es-settings-section es-settings-info-section">
        <h3><?php _e('Shortcodes', 'ensemble'); ?></h3>
        
        <div class="es-shortcode-grid">
            <div class="es-shortcode-block">
                <h4><?php _e('Reservation Form', 'ensemble'); ?></h4>
                <div class="es-shortcode-codes">
                    <code>[ensemble_reservation_form]</code>
                    <code>[ensemble_reservation_form event="123"]</code>
                    <code>[ensemble_reservation_form type="vip" button="Reserve VIP"]</code>
                </div>
                <p class="es-shortcode-desc"><?php _e('Display the reservation form on any page', 'ensemble'); ?></p>
            </div>
            
            <div class="es-shortcode-block">
                <h4><?php _e('Guest Counter', 'ensemble'); ?></h4>
                <div class="es-shortcode-codes">
                    <code>[ensemble_guestlist]</code>
                    <code>[ensemble_guestlist event="123" show_count="true"]</code>
                </div>
                <p class="es-shortcode-desc"><?php _e('Show public guest count or list', 'ensemble'); ?></p>
            </div>
        </div>
    </div>
    
</div>

<style>
/* Reservations Settings Styles */
.es-reservations-settings {
    max-width: 800px;
}

.es-reservations-settings .es-settings-section {
    margin-bottom: 32px;
    padding-bottom: 32px;
    border-bottom: 1px solid var(--es-border, #404040);
}

.es-reservations-settings .es-settings-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.es-reservations-settings .es-settings-section h3 {
    margin: 0 0 20px 0;
    font-size: 15px;
    font-weight: 600;
    color: var(--es-text, #e0e0e0);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Setting Rows */
.es-reservations-settings .es-setting-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 16px 0;
    border-bottom: 1px solid var(--es-border-light, #3a3a3a);
}

.es-reservations-settings .es-setting-row:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.es-reservations-settings .es-setting-row:first-of-type {
    padding-top: 0;
}

.es-reservations-settings .es-setting-info {
    flex: 1;
    padding-right: 24px;
}

.es-reservations-settings .es-setting-title {
    display: block;
    font-size: 14px;
    font-weight: 500;
    color: var(--es-text, #e0e0e0);
    margin-bottom: 4px;
}

.es-reservations-settings .es-setting-desc {
    display: block;
    font-size: 13px;
    color: var(--es-text-secondary, #9ca3af);
    line-height: 1.4;
}

.es-reservations-settings .es-setting-control {
    flex-shrink: 0;
}

/* Toggle Switch */
.es-reservations-settings .es-toggle-wrapper {
    position: relative;
    display: inline-block;
    cursor: pointer;
}

.es-reservations-settings .es-toggle-wrapper input {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}

.es-reservations-settings .es-toggle-switch {
    display: block;
    width: 48px;
    height: 26px;
    background: var(--es-surface-secondary, #3a3a3a);
    border-radius: 13px;
    position: relative;
    transition: background 0.2s;
}

.es-reservations-settings .es-toggle-switch::after {
    content: '';
    position: absolute;
    top: 3px;
    left: 3px;
    width: 20px;
    height: 20px;
    background: white;
    border-radius: 50%;
    transition: transform 0.2s;
}

.es-reservations-settings .es-toggle-wrapper input:checked + .es-toggle-switch {
    background: var(--es-primary, #6366f1);
}

.es-reservations-settings .es-toggle-wrapper input:checked + .es-toggle-switch::after {
    transform: translateX(22px);
}

/* Input Styles */
.es-reservations-settings .es-input-wide {
    width: 100%;
    min-width: 280px;
    padding: 10px 14px;
    background: var(--es-surface, #1e1e1e);
    border: 1px solid var(--es-border, #404040);
    border-radius: 6px;
    color: var(--es-text, #e0e0e0);
    font-size: 14px;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.es-reservations-settings .es-input-wide:focus {
    outline: none;
    border-color: var(--es-primary, #6366f1);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.es-reservations-settings .es-input-small {
    width: 80px;
    padding: 8px 12px;
    background: var(--es-surface, #1e1e1e);
    border: 1px solid var(--es-border, #404040);
    border-radius: 6px;
    color: var(--es-text, #e0e0e0);
    font-size: 14px;
    text-align: center;
}

.es-reservations-settings .es-input-small:focus {
    outline: none;
    border-color: var(--es-primary, #6366f1);
}

.es-reservations-settings textarea.es-input-wide {
    resize: vertical;
    min-height: 60px;
}

/* Input Group */
.es-reservations-settings .es-input-group {
    display: flex;
    align-items: center;
    gap: 10px;
}

.es-reservations-settings .es-input-suffix {
    font-size: 13px;
    color: var(--es-text-secondary, #9ca3af);
    white-space: nowrap;
}

/* Info Section */
.es-reservations-settings .es-settings-info-section {
    background: var(--es-surface-secondary, #2a2a2a);
    border-radius: 8px;
    padding: 20px 24px;
    border: none !important;
}

/* Shortcode Grid */
.es-reservations-settings .es-shortcode-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
}

.es-reservations-settings .es-shortcode-block {
    padding: 16px;
    background: var(--es-surface, #1e1e1e);
    border-radius: 8px;
}

.es-reservations-settings .es-shortcode-block h4 {
    margin: 0 0 12px 0;
    font-size: 14px;
    font-weight: 600;
    color: var(--es-text, #e0e0e0);
}

.es-reservations-settings .es-shortcode-codes {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.es-reservations-settings .es-shortcode-codes code {
    display: block;
    padding: 8px 12px;
    background: var(--es-background, #141414);
    border: 1px solid var(--es-border, #404040);
    border-radius: 4px;
    font-family: 'Monaco', 'Consolas', monospace;
    font-size: 12px;
    color: var(--es-primary, #6366f1);
}

.es-reservations-settings .es-shortcode-desc {
    margin: 12px 0 0;
    font-size: 12px;
    color: var(--es-text-secondary, #9ca3af);
}

/* Responsive */
@media (max-width: 600px) {
    .es-reservations-settings .es-setting-row {
        flex-direction: column;
        gap: 12px;
    }
    
    .es-reservations-settings .es-setting-info {
        padding-right: 0;
    }
    
    .es-reservations-settings .es-input-wide {
        min-width: 100%;
    }
    
    .es-reservations-settings .es-shortcode-grid {
        grid-template-columns: 1fr;
    }
}
</style>
