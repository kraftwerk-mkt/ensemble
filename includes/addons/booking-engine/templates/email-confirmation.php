<?php
/**
 * Booking Confirmation Email Template
 * 
 * Variables available:
 * @var object $booking  Booking object
 * @var WP_Post $event   Event post object
 * @var ES_Booking_Engine_Addon $addon Addon instance
 * 
 * @package Ensemble
 * @subpackage Addons/BookingEngine
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get event details
$event_date = get_post_meta($event->ID, 'event_date', true);
$event_time = get_post_meta($event->ID, 'event_time', true);
$location_name = '';
$location_address = '';

// Try to get location
$location_id = get_post_meta($event->ID, '_ensemble_location', true);
if ($location_id) {
    $location_post = get_post($location_id);
    if ($location_post) {
        $location_name = $location_post->post_title;
        $location_address = get_post_meta($location_id, '_es_location_address', true);
    }
}

// Brand colors (can be customized via filter)
$brand_color = apply_filters('ensemble_email_brand_color', '#3582c4');
$brand_color_light = apply_filters('ensemble_email_brand_color_light', '#e8f4fc');

// Cancel URL
$cancel_url = add_query_arg(array(
    'es_cancel' => 1,
    'code'      => $booking->confirmation_code,
    'token'     => $booking->cancel_token,
), home_url());

// Site info
$site_name = get_bloginfo('name');
$site_url = home_url();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html(sprintf(__('Your booking for %s', 'ensemble'), $event->post_title)); ?></title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f5f5f5;">
    
    <!-- Wrapper -->
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f5f5f5; padding: 40px 20px;">
        <tr>
            <td align="center">
                
                <!-- Container -->
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background-color: <?php echo esc_attr($brand_color); ?>; padding: 30px 40px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 24px; font-weight: 600;">
                                âœ“ <?php _e('Booking Confirmed', 'ensemble'); ?>
                            </h1>
                        </td>
                    </tr>
                    
                    <!-- Greeting -->
                    <tr>
                        <td style="padding: 40px 40px 20px;">
                            <p style="margin: 0; font-size: 18px; color: #1a1a1a;">
                                <?php printf(__('Hello %s,', 'ensemble'), esc_html($booking->customer_name)); ?>
                            </p>
                            <p style="margin: 15px 0 0; font-size: 16px; color: #666; line-height: 1.5;">
                                <?php printf(__('Your %s for <strong>%s</strong> has been confirmed.', 'ensemble'), 
                                    strtolower($addon->get_type_label($booking->booking_type)),
                                    esc_html($event->post_title)
                                ); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Confirmation Code Box -->
                    <tr>
                        <td style="padding: 0 40px;">
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: <?php echo esc_attr($brand_color_light); ?>; border-radius: 8px;">
                                <tr>
                                    <td style="padding: 25px; text-align: center;">
                                        <p style="margin: 0 0 8px; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; color: #666;">
                                            <?php _e('Your Confirmation Code', 'ensemble'); ?>
                                        </p>
                                        <p style="margin: 0; font-size: 32px; font-weight: 700; letter-spacing: 3px; color: <?php echo esc_attr($brand_color); ?>;">
                                            <?php echo esc_html($booking->confirmation_code); ?>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    <!-- Event Details -->
                    <tr>
                        <td style="padding: 30px 40px;">
                            <h3 style="margin: 0 0 20px; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; color: #999; border-bottom: 1px solid #eee; padding-bottom: 10px;">
                                <?php _e('Event Details', 'ensemble'); ?>
                            </h3>
                            
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="padding: 8px 0; color: #666; font-size: 14px; width: 120px;">
                                        <?php _e('Event', 'ensemble'); ?>
                                    </td>
                                    <td style="padding: 8px 0; color: #1a1a1a; font-size: 14px; font-weight: 500;">
                                        <?php echo esc_html($event->post_title); ?>
                                    </td>
                                </tr>
                                
                                <?php if ($event_date): ?>
                                <tr>
                                    <td style="padding: 8px 0; color: #666; font-size: 14px;">
                                        <?php _e('Date', 'ensemble'); ?>
                                    </td>
                                    <td style="padding: 8px 0; color: #1a1a1a; font-size: 14px; font-weight: 500;">
                                        <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($event_date))); ?>
                                        <?php if ($event_time): ?>
                                            <?php _e('at', 'ensemble'); ?> <?php echo esc_html($event_time); ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                
                                <?php if ($location_name): ?>
                                <tr>
                                    <td style="padding: 8px 0; color: #666; font-size: 14px;">
                                        <?php _e('Location', 'ensemble'); ?>
                                    </td>
                                    <td style="padding: 8px 0; color: #1a1a1a; font-size: 14px; font-weight: 500;">
                                        <?php echo esc_html($location_name); ?>
                                        <?php if ($location_address): ?>
                                            <br><span style="font-weight: 400; color: #666;"><?php echo esc_html($location_address); ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                
                                <tr>
                                    <td style="padding: 8px 0; color: #666; font-size: 14px;">
                                        <?php _e('Guests', 'ensemble'); ?>
                                    </td>
                                    <td style="padding: 8px 0; color: #1a1a1a; font-size: 14px; font-weight: 500;">
                                        <?php echo absint($booking->guests); ?> <?php echo $booking->guests == 1 ? __('person', 'ensemble') : __('people', 'ensemble'); ?>
                                    </td>
                                </tr>
                                
                                <?php if ($booking->element_label): ?>
                                <tr>
                                    <td style="padding: 8px 0; color: #666; font-size: 14px;">
                                        <?php _e('Reserved', 'ensemble'); ?>
                                    </td>
                                    <td style="padding: 8px 0; color: #1a1a1a; font-size: 14px; font-weight: 500;">
                                        <?php echo esc_html($booking->element_label); ?>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </td>
                    </tr>
                    
                    <!-- QR Code -->
                    <?php if ($booking->qr_code): ?>
                    <tr>
                        <td style="padding: 0 40px 30px; text-align: center;">
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f9f9f9; border-radius: 8px;">
                                <tr>
                                    <td style="padding: 25px; text-align: center;">
                                        <p style="margin: 0 0 15px; font-size: 14px; color: #666;">
                                            <?php _e('Show this QR code at check-in', 'ensemble'); ?>
                                        </p>
                                        <img src="<?php echo esc_url($booking->qr_code); ?>" 
                                             alt="QR Code" 
                                             width="150" 
                                             height="150" 
                                             style="display: inline-block;">
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <?php endif; ?>
                    
                    <!-- Actions -->
                    <tr>
                        <td style="padding: 0 40px 30px; text-align: center;">
                            <a href="<?php echo esc_url(get_permalink($event->ID)); ?>" 
                               style="display: inline-block; padding: 14px 28px; background-color: <?php echo esc_attr($brand_color); ?>; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 14px;">
                                <?php _e('View Event', 'ensemble'); ?>
                            </a>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f9f9f9; padding: 25px 40px; border-top: 1px solid #eee;">
                            <p style="margin: 0 0 10px; font-size: 13px; color: #666; text-align: center;">
                                <?php printf(__('Need to cancel? %sClick here%s', 'ensemble'), 
                                    '<a href="' . esc_url($cancel_url) . '" style="color: ' . esc_attr($brand_color) . ';">', 
                                    '</a>'
                                ); ?>
                            </p>
                            <p style="margin: 0; font-size: 12px; color: #999; text-align: center;">
                                <?php echo esc_html($site_name); ?> · <a href="<?php echo esc_url($site_url); ?>" style="color: #999;"><?php echo esc_url($site_url); ?></a>
                            </p>
                        </td>
                    </tr>
                    
                </table>
                
            </td>
        </tr>
    </table>
    
</body>
</html>
