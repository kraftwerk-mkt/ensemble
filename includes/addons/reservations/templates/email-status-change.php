<?php
/**
 * Reservations Pro - Status Change Email Template
 * 
 * @package Ensemble
 * @subpackage Addons/Reservations Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

$site_name = get_bloginfo('name');
$is_cancelled = $new_status === 'cancelled';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f5f5f5;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f5f5f5; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background: <?php echo $is_cancelled ? '#ef4444' : '#10b981'; ?>; padding: 30px 40px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 24px; font-weight: 600;">
                                <?php printf(__('Reservation %s', 'ensemble'), $status_label); ?>
                            </h1>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px;">
                            <p style="margin: 0 0 20px; font-size: 16px; color: #374151;">
                                <?php printf(__('Hallo %s,', 'ensemble'), esc_html($reservation->name)); ?>
                            </p>
                            
                            <p style="margin: 0 0 30px; font-size: 16px; color: #374151; line-height: 1.6;">
                                <?php if ($is_cancelled): ?>
                                    <?php _e('Deine Reservierung wurde leider storniert.', 'ensemble'); ?>
                                <?php else: ?>
                                    <?php _e('Your reservation has been confirmed! We look forward to your visit.', 'ensemble'); ?>
                                <?php endif; ?>
                            </p>
                            
                            <!-- Event Info -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f9fafb; border-radius: 8px; margin-bottom: 30px;">
                                <tr>
                                    <td style="padding: 25px;">
                                        <h2 style="margin: 0 0 10px; font-size: 18px; color: #1f2937;">
                                            <?php echo esc_html($event->post_title); ?>
                                        </h2>
                                        <p style="margin: 0; font-size: 14px; color: #6b7280;">
                                            <?php _e('Code:', 'ensemble'); ?> <strong><?php echo esc_html($reservation->confirmation_code); ?></strong>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            
                            <?php if (!$is_cancelled): ?>
                            <p style="margin: 0; font-size: 14px; color: #6b7280; text-align: center;">
                                <?php _e('Show your confirmation code at the entrance.', 'ensemble'); ?>
                            </p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f9fafb; padding: 25px 40px; text-align: center; border-top: 1px solid #e5e7eb;">
                            <p style="margin: 0; font-size: 13px; color: #9ca3af;">
                                <?php echo esc_html($site_name); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
