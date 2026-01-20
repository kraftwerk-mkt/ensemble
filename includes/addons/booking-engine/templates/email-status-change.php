<?php
/**
 * Booking Status Change Email Template
 * 
 * @package Ensemble
 * @subpackage Addons/BookingEngine
 */

if (!defined('ABSPATH')) {
    exit;
}

$event_date = get_post_meta($event->ID, 'event_date', true);
$site_name = get_bloginfo('name');
$site_url = home_url();

// Status specific content
$status_messages = array(
    'confirmed'  => __('Your booking has been confirmed!', 'ensemble'),
    'cancelled'  => __('Your booking has been cancelled.', 'ensemble'),
    'checked_in' => __('You have been checked in!', 'ensemble'),
    'pending'    => __('Your booking is pending approval.', 'ensemble'),
    'no_show'    => __('You were marked as a no-show.', 'ensemble'),
);

$status_colors = array(
    'confirmed'  => '#10b981',
    'cancelled'  => '#ef4444',
    'checked_in' => '#8b5cf6',
    'pending'    => '#f59e0b',
    'no_show'    => '#6b7280',
);

$status_message = $status_messages[$new_status] ?? __('Your booking status has been updated.', 'ensemble');
$status_color = $status_colors[$new_status] ?? '#3b82f6';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php _e('Booking Update', 'ensemble'); ?></title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f5f5f5;">
    <div style="width: 100%; padding: 40px 0; background-color: #f5f5f5;">
        <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);">
            
            <!-- Header -->
            <div style="background: <?php echo $status_color; ?>; padding: 30px; text-align: center;">
                <h1 style="margin: 0; color: #ffffff; font-size: 24px;">
                    <?php _e('Booking Update', 'ensemble'); ?>
                </h1>
            </div>
            
            <!-- Content -->
            <div style="padding: 30px;">
                <p style="font-size: 18px; margin-bottom: 20px;">
                    <?php printf(__('Hello %s,', 'ensemble'), esc_html($booking->customer_name)); ?>
                </p>
                
                <p style="font-size: 16px; margin-bottom: 25px;">
                    <?php echo esc_html($status_message); ?>
                </p>
                
                <!-- Status Badge -->
                <div style="text-align: center; margin: 25px 0;">
                    <span style="display: inline-block; padding: 10px 20px; background: <?php echo $status_color; ?>; color: #fff; border-radius: 20px; font-weight: 600; text-transform: uppercase; font-size: 14px;">
                        <?php echo esc_html(ucfirst(str_replace('_', ' ', $new_status))); ?>
                    </span>
                </div>
                
                <!-- Booking Details -->
                <div style="background-color: #f8f9fa; border-radius: 8px; padding: 20px; margin: 25px 0;">
                    <h3 style="margin: 0 0 15px; font-size: 16px; color: #333;">
                        <?php echo esc_html($event->post_title); ?>
                    </h3>
                    
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="padding: 8px 0; color: #666; font-size: 14px;"><?php _e('Code', 'ensemble'); ?></td>
                            <td style="padding: 8px 0; text-align: right; font-weight: 600; font-family: monospace; color: #3582c4;">
                                <?php echo esc_html($booking->confirmation_code); ?>
                            </td>
                        </tr>
                        <?php if ($event_date): ?>
                        <tr>
                            <td style="padding: 8px 0; color: #666; font-size: 14px; border-top: 1px solid #eee;"><?php _e('Date', 'ensemble'); ?></td>
                            <td style="padding: 8px 0; text-align: right; font-weight: 500; border-top: 1px solid #eee;">
                                <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($event_date))); ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <td style="padding: 8px 0; color: #666; font-size: 14px; border-top: 1px solid #eee;"><?php _e('Guests', 'ensemble'); ?></td>
                            <td style="padding: 8px 0; text-align: right; font-weight: 500; border-top: 1px solid #eee;">
                                <?php echo esc_html($booking->guests); ?>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <?php if ($new_status === 'cancelled'): ?>
                <p style="font-size: 14px; color: #666; margin-top: 20px;">
                    <?php _e('If you did not request this cancellation, please contact us immediately.', 'ensemble'); ?>
                </p>
                <?php endif; ?>
                
            </div>
            
            <!-- Footer -->
            <div style="padding: 20px 30px; background-color: #f8f9fa; text-align: center; font-size: 13px; color: #999;">
                <p style="margin: 0;">
                    <?php printf(__('This email was sent by %s', 'ensemble'), '<a href="' . esc_url($site_url) . '" style="color: #3582c4; text-decoration: none;">' . esc_html($site_name) . '</a>'); ?>
                </p>
            </div>
            
        </div>
    </div>
</body>
</html>
