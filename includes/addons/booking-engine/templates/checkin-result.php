<?php
/**
 * Check-in Result Template
 * 
 * Displayed after QR code scan for check-in
 * 
 * @package Ensemble
 * @subpackage Addons/BookingEngine
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$site_name = get_bloginfo('name');
$is_success = strpos($message, 'Successfully') !== false || strpos($message, 'Erfolgreich') !== false;
$event_title = $event ? $event->post_title : '';
$event_date = $event ? get_post_meta($event->ID, 'event_date', true) : '';
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php _e('Check-in', 'ensemble'); ?> - <?php echo esc_html($site_name); ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: <?php echo $is_success ? '#10b981' : '#f59e0b'; ?>;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .checkin-container {
            max-width: 400px;
            width: 100%;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
            text-align: center;
        }
        .checkin-icon {
            padding: 40px 20px 20px;
        }
        .checkin-icon .icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
        }
        .icon-success {
            background: #d1fae5;
            color: #10b981;
        }
        .icon-warning {
            background: #fef3c7;
            color: #f59e0b;
        }
        .checkin-body {
            padding: 0 30px 30px;
        }
        .checkin-body h1 {
            font-size: 28px;
            margin-bottom: 10px;
            color: #1f2937;
        }
        .checkin-body .message {
            font-size: 18px;
            color: #6b7280;
            margin-bottom: 25px;
        }
        .booking-card {
            background: #f9fafb;
            border-radius: 12px;
            padding: 20px;
            text-align: left;
        }
        .booking-card h3 {
            font-size: 16px;
            color: #374151;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e5e7eb;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
        }
        .info-label {
            color: #6b7280;
            font-size: 14px;
        }
        .info-value {
            font-weight: 600;
            color: #1f2937;
            font-size: 14px;
        }
        .confirmation-code {
            font-family: 'SF Mono', Monaco, monospace;
            color: #3b82f6;
            letter-spacing: 1px;
        }
        .guest-count {
            font-size: 24px;
            color: #10b981;
        }
        .checkin-footer {
            padding: 20px;
            background: #f9fafb;
            border-top: 1px solid #e5e7eb;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #3b82f6;
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: background 0.2s;
        }
        .btn:hover {
            background: #2563eb;
        }
        .timestamp {
            margin-top: 15px;
            font-size: 12px;
            color: #9ca3af;
        }
    </style>
</head>
<body>
    <div class="checkin-container">
        <div class="checkin-icon">
            <span class="icon <?php echo $is_success ? 'icon-success' : 'icon-warning'; ?>">
                <?php echo $is_success ? '✓' : '!'; ?>
            </span>
        </div>
        
        <div class="checkin-body">
            <h1><?php echo $is_success ? __('Checked In!', 'ensemble') : __('Already Checked In', 'ensemble'); ?></h1>
            <p class="message"><?php echo esc_html($message); ?></p>
            
            <?php if ($booking): ?>
            <div class="booking-card">
                <h3><?php echo esc_html($event_title); ?></h3>
                
                <div class="info-row">
                    <span class="info-label"><?php _e('Code', 'ensemble'); ?></span>
                    <span class="info-value confirmation-code"><?php echo esc_html($booking->confirmation_code); ?></span>
                </div>
                
                <div class="info-row">
                    <span class="info-label"><?php _e('Name', 'ensemble'); ?></span>
                    <span class="info-value"><?php echo esc_html($booking->customer_name); ?></span>
                </div>
                
                <div class="info-row">
                    <span class="info-label"><?php _e('Guests', 'ensemble'); ?></span>
                    <span class="info-value guest-count"><?php echo esc_html($booking->guests); ?></span>
                </div>
                
                <?php if ($event_date): ?>
                <div class="info-row">
                    <span class="info-label"><?php _e('Date', 'ensemble'); ?></span>
                    <span class="info-value"><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($event_date))); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($booking->element_label): ?>
                <div class="info-row">
                    <span class="info-label"><?php _e('Seat/Table', 'ensemble'); ?></span>
                    <span class="info-value"><?php echo esc_html($booking->element_label); ?></span>
                </div>
                <?php endif; ?>
            </div>
            
            <p class="timestamp">
                <?php 
                if ($booking->checked_in_at) {
                    printf(__('Checked in at %s', 'ensemble'), date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($booking->checked_in_at)));
                }
                ?>
            </p>
            <?php endif; ?>
        </div>
        
        <div class="checkin-footer">
            <a href="<?php echo esc_url(admin_url('admin.php?page=ensemble-bookings')); ?>" class="btn">
                <?php _e('View All Bookings', 'ensemble'); ?>
            </a>
        </div>
    </div>
</body>
</html>
