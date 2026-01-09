<?php
/**
 * U18 Authorization - Admin Notification Email
 * 
 * Neutral design for admin notifications
 * 
 * @package Ensemble
 * @subpackage Addons/Reservations Pro
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f5f5f5; line-height: 1.6; color: #333;">
    
    <div style="max-width: 560px; margin: 0 auto; padding: 40px 20px;">
        
        <!-- Header -->
        <div style="text-align: center; margin-bottom: 30px;">
            <h1 style="margin: 0; font-size: 20px; font-weight: 600; color: #111;">
                <?php _e('Neuer U18-Antrag', 'ensemble'); ?>
            </h1>
        </div>
        
        <!-- Content Card -->
        <div style="background: #fff; border: 1px solid #e0e0e0; padding: 30px;">
            
            <p style="margin: 0 0 20px 0; color: #555;">
                <?php _e('Ein neuer U18-Antrag wurde eingereicht und wartet auf Prüfung.', 'ensemble'); ?>
            </p>
            
            <!-- Event -->
            <div style="border-left: 3px solid #333; padding-left: 15px; margin-bottom: 20px;">
                <span style="display: block; font-size: 11px; color: #888; text-transform: uppercase; margin-bottom: 5px;">
                    <?php _e('Veranstaltung', 'ensemble'); ?>
                </span>
                <span style="font-weight: 600; color: #111;">
                    <?php echo esc_html($event->post_title); ?>
                </span>
            </div>
            
            <!-- Code -->
            <div style="background: #fafafa; border: 1px solid #e0e0e0; padding: 15px; text-align: center; margin-bottom: 20px;">
                <span style="font-family: 'SF Mono', Monaco, 'Courier New', monospace; font-size: 18px; font-weight: 600; color: #111; letter-spacing: 2px;">
                    <?php echo esc_html($authorization->authorization_code); ?>
                </span>
            </div>
            
            <!-- Persons -->
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 14px;">
                <tr>
                    <td style="padding: 10px 12px; background: #fafafa; border: 1px solid #e0e0e0;">
                        <span style="display: block; font-size: 10px; color: #888; text-transform: uppercase; margin-bottom: 3px;">
                            <?php _e('Minderjährig', 'ensemble'); ?>
                        </span>
                        <strong><?php echo esc_html($authorization->minor_firstname . ' ' . $authorization->minor_lastname); ?></strong>
                        <span style="color: #666; margin-left: 5px;">
                            <?php 
                            $age = (new DateTime($authorization->minor_birthdate))->diff(new DateTime())->y;
                            echo '(' . $age . ' J.)';
                            ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 10px 12px; background: #fafafa; border: 1px solid #e0e0e0; border-top: none;">
                        <span style="display: block; font-size: 10px; color: #888; text-transform: uppercase; margin-bottom: 3px;">
                            <?php _e('Begleitperson', 'ensemble'); ?>
                        </span>
                        <strong><?php echo esc_html($authorization->companion_firstname . ' ' . $authorization->companion_lastname); ?></strong>
                        <span style="color: #666; margin-left: 5px;">
                            <?php 
                            $companion_age = (new DateTime($authorization->companion_birthdate))->diff(new DateTime())->y;
                            echo '(' . $companion_age . ' J.)';
                            ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 10px 12px; background: #fafafa; border: 1px solid #e0e0e0; border-top: none;">
                        <span style="display: block; font-size: 10px; color: #888; text-transform: uppercase; margin-bottom: 3px;">
                            <?php _e('Erziehungsberechtigter', 'ensemble'); ?>
                        </span>
                        <strong><?php echo esc_html($authorization->parent_firstname . ' ' . $authorization->parent_lastname); ?></strong>
                        <span style="display: block; color: #666; font-size: 13px; margin-top: 3px;">
                            <?php echo esc_html($authorization->parent_phone); ?> · <?php echo esc_html($authorization->parent_email); ?>
                        </span>
                    </td>
                </tr>
            </table>
            
            <!-- ID Upload -->
            <?php if (!empty($authorization->id_upload_path)): ?>
            <div style="background: #fafafa; border: 1px solid #e0e0e0; padding: 10px 12px; margin-bottom: 20px; font-size: 13px; color: #555;">
                ✓ <?php _e('Ausweis hochgeladen', 'ensemble'); ?>
            </div>
            <?php else: ?>
            <div style="background: #fafafa; border: 1px solid #e0e0e0; padding: 10px 12px; margin-bottom: 20px; font-size: 13px; color: #888;">
                – <?php _e('Kein Ausweis hochgeladen', 'ensemble'); ?>
            </div>
            <?php endif; ?>
            
            <!-- Admin Link -->
            <div style="text-align: center;">
                <a href="<?php echo esc_url($admin_url); ?>" style="display: inline-block; background: #111; color: #fff; text-decoration: none; padding: 12px 25px; font-size: 14px; font-weight: 500;">
                    <?php _e('Antrag prüfen', 'ensemble'); ?>
                </a>
            </div>
            
        </div>
        
        <!-- Meta -->
        <div style="text-align: center; padding: 20px 0; color: #999; font-size: 11px;">
            <?php echo esc_html(date_i18n('d.m.Y H:i', strtotime($authorization->created_at))); ?> · IP: <?php echo esc_html($authorization->consent_ip); ?>
        </div>
        
    </div>
    
</body>
</html>
