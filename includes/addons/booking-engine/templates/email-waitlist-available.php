<?php
/**
 * Waitlist Spot Available Email Template
 * 
 * Sent when a spot becomes available for someone on the waitlist
 * 
 * @package Ensemble
 * @subpackage Addons/BookingEngine
 */

if (!defined('ABSPATH')) {
    exit;
}

$event_date = get_post_meta($event->ID, 'event_date', true);
$event_time = get_post_meta($event->ID, 'event_time', true);
$site_name = get_bloginfo('name');
$site_url = home_url();

// Calculate expiry
$expiry_time = $entry->expires_at ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($entry->expires_at)) : '';

// Book now URL (links to event page)
$book_url = get_permalink($event->ID);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php _e('A Spot is Available!', 'ensemble'); ?></title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f5f5f5;">
    <div style="width: 100%; padding: 40px 0; background-color: #f5f5f5;">
        <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);">
            
            <!-- Header -->
            <div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 30px; text-align: center;">
                <h1 style="margin: 0; color: #ffffff; font-size: 24px;">
                    🎉 <?php _e('Good News!', 'ensemble'); ?>
                </h1>
                <p style="margin: 10px 0 0; color: rgba(255,255,255,0.9); font-size: 16px;">
                    <?php _e('A spot is now available', 'ensemble'); ?>
                </p>
            </div>
            
            <!-- Content -->
            <div style="padding: 30px;">
                <p style="font-size: 18px; margin-bottom: 20px;">
                    <?php printf(__('Hello %s,', 'ensemble'), esc_html($entry->customer_name)); ?>
                </p>
                
                <p style="font-size: 16px; margin-bottom: 20px; line-height: 1.6;">
                    <?php _e('Great news! A spot has become available for the event you were waiting for. You can now complete your booking.', 'ensemble'); ?>
                </p>
                
                <!-- Event Card -->
                <div style="background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border-radius: 12px; padding: 25px; margin: 25px 0; border: 1px solid #bae6fd;">
                    <h2 style="margin: 0 0 15px; font-size: 20px; color: #0369a1;">
                        <?php echo esc_html($event->post_title); ?>
                    </h2>
                    
                    <?php if ($event_date): ?>
                    <p style="margin: 0 0 10px; color: #0c4a6e; font-size: 15px;">
                        <strong>📅 <?php _e('Date:', 'ensemble'); ?></strong>
                        <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($event_date))); ?>
                        <?php if ($event_time): ?>
                            <strong style="margin-left: 15px;">🕐</strong> <?php echo esc_html($event_time); ?>
                        <?php endif; ?>
                    </p>
                    <?php endif; ?>
                    
                    <p style="margin: 0; color: #0c4a6e; font-size: 15px;">
                        <strong>👥 <?php _e('Requested:', 'ensemble'); ?></strong>
                        <?php echo esc_html($entry->quantity); ?> <?php echo $entry->quantity == 1 ? __('spot', 'ensemble') : __('spots', 'ensemble'); ?>
                    </p>
                </div>
                
                <!-- Urgency Warning -->
                <?php if ($expiry_time): ?>
                <div style="background-color: #fef3c7; border: 1px solid #fbbf24; border-radius: 8px; padding: 15px; margin: 20px 0;">
                    <p style="margin: 0; color: #92400e; font-size: 14px;">
                        <strong>⏰ <?php _e('Act Fast!', 'ensemble'); ?></strong><br>
                        <?php printf(__('This offer expires on %s. After that, the spot will be offered to the next person on the waitlist.', 'ensemble'), '<strong>' . $expiry_time . '</strong>'); ?>
                    </p>
                </div>
                <?php endif; ?>
                
                <!-- CTA Button -->
                <div style="text-align: center; margin: 30px 0;">
                    <a href="<?php echo esc_url($book_url); ?>" 
                       style="display: inline-block; padding: 16px 40px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #ffffff; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 18px; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);">
                        <?php _e('Book Now', 'ensemble'); ?> →
                    </a>
                </div>
                
                <p style="font-size: 14px; color: #666; text-align: center; margin-top: 20px;">
                    <?php _e("If you're no longer interested, simply ignore this email and your spot will be offered to someone else.", 'ensemble'); ?>
                </p>
                
            </div>
            
            <!-- Footer -->
            <div style="padding: 20px 30px; background-color: #f8f9fa; text-align: center; font-size: 13px; color: #999;">
                <p style="margin: 0;">
                    <?php _e('You received this email because you joined the waitlist for this event.', 'ensemble'); ?>
                </p>
                <p style="margin: 10px 0 0;">
                    <a href="<?php echo esc_url($site_url); ?>" style="color: #3582c4; text-decoration: none;"><?php echo esc_html($site_name); ?></a>
                </p>
            </div>
            
        </div>
    </div>
</body>
</html>
