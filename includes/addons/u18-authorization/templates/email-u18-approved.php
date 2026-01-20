<?php
/**
 * U18 Authorization - Approval Email
 * 
 * Neutral design - Contains QR code for check-in
 * 
 * @package Ensemble
 * @subpackage Addons/Reservations Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get accent color from event/layout if available
$accent_color = '#333333';
$event_accent = get_post_meta($event->ID, '_ensemble_accent_color', true);
if ($event_accent) {
    $accent_color = $event_accent;
}

// Get site logo
$site_logo_url = '';
$custom_logo_id = get_theme_mod('custom_logo');
if ($custom_logo_id) {
    $site_logo_url = wp_get_attachment_image_url($custom_logo_id, 'medium');
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
            <?php if ($site_logo_url): ?>
                <img src="<?php echo esc_url($site_logo_url); ?>" alt="<?php echo esc_attr($site_name); ?>" style="max-height: 50px; max-width: 200px; margin-bottom: 15px;">
            <?php endif; ?>
            <h1 style="margin: 0; font-size: 20px; font-weight: 600; color: #111;">
                ✓ <?php _e('Aufsichtsübertragung genehmigt', 'ensemble'); ?>
            </h1>
        </div>
        
        <!-- Content Card -->
        <div style="background: #fff; border: 1px solid #e0e0e0; padding: 30px;">
            
            <p style="margin: 0 0 20px 0; color: #333;">
                <?php printf(__('Guten Tag %s %s,', 'ensemble'), esc_html($authorization->parent_firstname), esc_html($authorization->parent_lastname)); ?>
            </p>
            
            <p style="margin: 0 0 25px 0; color: #555;">
                <?php _e('Ihre Aufsichtsübertragung wurde geprüft und genehmigt.', 'ensemble'); ?>
            </p>
            
            <!-- QR Code Section -->
            <div style="background: #fafafa; border: 1px solid #e0e0e0; padding: 25px; text-align: center; margin-bottom: 25px;">
                <span style="display: block; font-size: 11px; color: #888; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px;">
                    <?php _e('QR-Code für den Einlass', 'ensemble'); ?>
                </span>
                <img src="<?php echo esc_url($qr_code_url); ?>" alt="QR Code" style="width: 160px; height: 160px; border: 1px solid #e0e0e0;">
                <div style="margin-top: 15px;">
                    <span style="font-family: 'SF Mono', Monaco, 'Courier New', monospace; font-size: 20px; font-weight: 600; color: #111; letter-spacing: 2px;">
                        <?php echo esc_html($authorization->authorization_code); ?>
                    </span>
                </div>
            </div>
            
            <!-- Event -->
            <div style="border-left: 3px solid <?php echo esc_attr($accent_color); ?>; padding-left: 15px; margin-bottom: 25px;">
                <span style="display: block; font-size: 11px; color: #888; text-transform: uppercase; margin-bottom: 5px;">
                    <?php _e('Veranstaltung', 'ensemble'); ?>
                </span>
                <span style="font-weight: 600; color: #111;">
                    <?php echo esc_html($event->post_title); ?>
                </span>
                <?php if ($event_date): ?>
                <span style="display: block; color: #666; font-size: 14px; margin-top: 3px;">
                    <?php echo esc_html(date_i18n('d.m.Y', strtotime($event_date))); ?>
                </span>
                <?php endif; ?>
            </div>
            
            <!-- Persons -->
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 25px;">
                <tr>
                    <td style="padding: 12px 15px; background: #fafafa; border: 1px solid #e0e0e0; vertical-align: top; width: 50%;">
                        <span style="display: block; font-size: 10px; color: #888; text-transform: uppercase; margin-bottom: 5px;">
                            <?php _e('Minderjährig', 'ensemble'); ?>
                        </span>
                        <span style="font-weight: 600; color: #111;">
                            <?php echo esc_html($authorization->minor_firstname . ' ' . $authorization->minor_lastname); ?>
                        </span>
                    </td>
                    <td style="padding: 12px 15px; background: #fafafa; border: 1px solid #e0e0e0; border-left: none; vertical-align: top; width: 50%;">
                        <span style="display: block; font-size: 10px; color: #888; text-transform: uppercase; margin-bottom: 5px;">
                            <?php _e('Begleitperson', 'ensemble'); ?>
                        </span>
                        <span style="font-weight: 600; color: #111;">
                            <?php echo esc_html($authorization->companion_firstname . ' ' . $authorization->companion_lastname); ?>
                        </span>
                    </td>
                </tr>
            </table>
            
            <!-- Checklist -->
            <div style="margin-bottom: 25px;">
                <span style="display: block; font-size: 11px; color: #888; text-transform: uppercase; margin-bottom: 10px;">
                    <?php _e('Checkliste Einlass', 'ensemble'); ?>
                </span>
                <ul style="margin: 0; padding: 0 0 0 18px; color: #555; font-size: 14px;">
                    <li style="margin-bottom: 6px;"><?php _e('QR-Code oder Ausdruck bereithalten', 'ensemble'); ?></li>
                    <li style="margin-bottom: 6px;"><?php _e('Personalausweise aller Personen', 'ensemble'); ?></li>
                    <li><?php _e('Gemeinsames Erscheinen von Begleitperson und Minderjährigem', 'ensemble'); ?></li>
                </ul>
            </div>
            
            <!-- PDF Link -->
            <?php if (!empty($pdf_url)): ?>
            <div style="text-align: center; padding-top: 15px; border-top: 1px solid #e0e0e0;">
                <a href="<?php echo esc_url($pdf_url); ?>" style="display: inline-block; background: #111; color: #fff; text-decoration: none; padding: 12px 25px; font-size: 14px; font-weight: 500;">
                    <?php _e('PDF herunterladen', 'ensemble'); ?>
                </a>
            </div>
            <?php endif; ?>
            
        </div>
        
        <!-- Footer -->
        <div style="text-align: center; padding: 25px 0; color: #999; font-size: 12px;">
            <p style="margin: 0;">
                <?php echo esc_html($site_name); ?>
            </p>
        </div>
        
    </div>
    
</body>
</html>
