<?php
/**
 * Reservations Pro - Confirmation Email Template
 * 
 * @package Ensemble
 * @subpackage Addons/Reservations Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

$site_name = get_bloginfo('name');
$is_confirmed = $reservation->status === 'confirmed';
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
                        <td style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); padding: 30px 40px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 24px; font-weight: 600;">
                                <?php echo $is_confirmed ? '✓ ' . __('Reservation confirmed', 'ensemble') : __('Reservation received', 'ensemble'); ?>
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
                                <?php if ($is_confirmed): ?>
                                    <?php _e('Your reservation has been confirmed. We look forward to your visit!', 'ensemble'); ?>
                                <?php else: ?>
                                    <?php _e('Your reservation has been received and is being reviewed. You will receive a confirmation by email.', 'ensemble'); ?>
                                <?php endif; ?>
                            </p>
                            
                            <!-- Event Details -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f9fafb; border-radius: 8px; margin-bottom: 30px;">
                                <tr>
                                    <td style="padding: 25px;">
                                        <h2 style="margin: 0 0 15px; font-size: 18px; color: #1f2937;">
                                            <?php echo esc_html($event->post_title); ?>
                                        </h2>
                                        <p style="margin: 0 0 8px; font-size: 14px; color: #6b7280;">
                                            <?php echo date_i18n(get_option('date_format'), strtotime($event_date)); ?>
                                            <?php if ($event_time): ?>
                                                &bull; <?php echo esc_html($event_time); ?> <?php _e('Uhr', 'ensemble'); ?>
                                            <?php endif; ?>
                                        </p>
                                        <p style="margin: 0; font-size: 14px; color: #6b7280;">
                                            <?php printf(_n('%d Person', '%d Personen', $reservation->guests, 'ensemble'), $reservation->guests); ?>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Confirmation Code -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #eff6ff; border: 2px dashed #3b82f6; border-radius: 8px; margin-bottom: 30px;">
                                <tr>
                                    <td style="padding: 25px; text-align: center;">
                                        <p style="margin: 0 0 8px; font-size: 12px; color: #6b7280; text-transform: uppercase; letter-spacing: 1px;">
                                            <?php _e('Your Confirmation Code', 'ensemble'); ?>
                                        </p>
                                        <p style="margin: 0; font-size: 32px; font-weight: 700; color: #1d4ed8; letter-spacing: 4px; font-family: monospace;">
                                            <?php echo esc_html($reservation->confirmation_code); ?>
                                        </p>
                                        <p style="margin: 15px 0 0; font-size: 13px; color: #6b7280;">
                                            <?php _e('Zeige diesen Code beim Einlass vor', 'ensemble'); ?>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            
                            <?php if ($reservation->qr_code): ?>
                            <!-- QR Code -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 30px;">
                                <tr>
                                    <td align="center">
                                        <img src="<?php echo esc_url($reservation->qr_code); ?>" 
                                             alt="QR Code" 
                                             width="150" 
                                             height="150" 
                                             style="border-radius: 8px;">
                                        <p style="margin: 10px 0 0; font-size: 12px; color: #9ca3af;">
                                            <?php _e('QR Code for quick check-in', 'ensemble'); ?>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            <?php endif; ?>
                            
                            <!-- Reservation Details -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="border-top: 1px solid #e5e7eb; padding-top: 20px;">
                                <tr>
                                    <td style="padding-top: 20px;">
                                        <h3 style="margin: 0 0 15px; font-size: 14px; color: #6b7280; text-transform: uppercase;">
                                            <?php _e('Reservierungsdetails', 'ensemble'); ?>
                                        </h3>
                                        <table width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding: 8px 0; font-size: 14px; color: #6b7280; width: 120px;"><?php _e('Name:', 'ensemble'); ?></td>
                                                <td style="padding: 8px 0; font-size: 14px; color: #374151;"><?php echo esc_html($reservation->name); ?></td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 8px 0; font-size: 14px; color: #6b7280;"><?php _e('E-Mail:', 'ensemble'); ?></td>
                                                <td style="padding: 8px 0; font-size: 14px; color: #374151;"><?php echo esc_html($reservation->email); ?></td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 8px 0; font-size: 14px; color: #6b7280;"><?php _e('Typ:', 'ensemble'); ?></td>
                                                <td style="padding: 8px 0; font-size: 14px; color: #374151;">
                                                    <?php 
                                                    $types = array(
                                                        'guestlist' => __('Guest list', 'ensemble'),
                                                        'table'     => __('Table Reservation', 'ensemble'),
                                                        'vip'       => __('VIP', 'ensemble'),
                                                    );
                                                    echo $types[$reservation->type] ?? $reservation->type;
                                                    ?>
                                                </td>
                                            </tr>
                                            <?php if ($reservation->table_number): ?>
                                            <tr>
                                                <td style="padding: 8px 0; font-size: 14px; color: #6b7280;"><?php _e('Tisch:', 'ensemble'); ?></td>
                                                <td style="padding: 8px 0; font-size: 14px; color: #374151;"><?php echo esc_html($reservation->table_number); ?></td>
                                            </tr>
                                            <?php endif; ?>
                                            <?php if ($reservation->notes): ?>
                                            <tr>
                                                <td style="padding: 8px 0; font-size: 14px; color: #6b7280; vertical-align: top;"><?php _e('Notizen:', 'ensemble'); ?></td>
                                                <td style="padding: 8px 0; font-size: 14px; color: #374151;"><?php echo nl2br(esc_html($reservation->notes)); ?></td>
                                            </tr>
                                            <?php endif; ?>
                                        </table>
                                    </td>
                                </tr>
                            </table>
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
