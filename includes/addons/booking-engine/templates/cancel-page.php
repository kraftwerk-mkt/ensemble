<?php
/**
 * Booking Cancellation Page Template
 * 
 * Variables available:
 * @var object|null $booking Booking object or null if not found
 * @var string $token Cancel token from URL
 * @var WP_Post|null $event Event post object
 * @var ES_Booking_Engine_Addon $addon Addon instance
 * 
 * @package Ensemble
 * @subpackage Addons/BookingEngine
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get site info
$site_name = get_bloginfo('name');
$home_url = home_url();

// Determine state
$error = null;
$can_cancel = false;

if (!$booking) {
    $error = __('Booking not found', 'ensemble');
} elseif ($booking->cancel_token !== $token) {
    $error = __('Invalid cancellation link', 'ensemble');
} elseif ($booking->cancel_token_expires && strtotime($booking->cancel_token_expires) < time()) {
    $error = __('This cancellation link has expired', 'ensemble');
} elseif ($booking->status === 'cancelled') {
    $error = __('This booking has already been cancelled', 'ensemble');
} elseif ($booking->status === 'checked_in') {
    $error = __('This booking has already been checked in and cannot be cancelled', 'ensemble');
} else {
    $can_cancel = true;
}

// Get event details if available
$event_date = $event ? get_post_meta($event->ID, 'event_date', true) : '';
$event_time = $event ? get_post_meta($event->ID, 'event_time', true) : '';

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html(sprintf(__('Cancel Booking - %s', 'ensemble'), $site_name)); ?></title>
    <?php wp_head(); ?>
    <style>
        :root {
            --cancel-primary: #3582c4;
            --cancel-danger: #ef4444;
            --cancel-warning: #f59e0b;
            --cancel-success: #10b981;
            --cancel-bg: #f5f5f5;
            --cancel-card: #ffffff;
            --cancel-text: #1a1a1a;
            --cancel-text-secondary: #666666;
            --cancel-border: #e0e0e0;
        }
        
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--cancel-bg);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .es-cancel-container {
            max-width: 500px;
            width: 100%;
            padding: 20px;
        }
        
        .es-cancel-card {
            background: var(--cancel-card);
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .es-cancel-header {
            padding: 30px;
            text-align: center;
            border-bottom: 1px solid var(--cancel-border);
        }
        
        .es-cancel-header h1 {
            margin: 0;
            font-size: 22px;
            font-weight: 600;
            color: var(--cancel-text);
        }
        
        .es-cancel-header p {
            margin: 10px 0 0;
            color: var(--cancel-text-secondary);
            font-size: 15px;
        }
        
        .es-cancel-body {
            padding: 30px;
        }
        
        .es-cancel-error {
            text-align: center;
            padding: 20px;
        }
        
        .es-cancel-error .dashicons {
            font-size: 48px;
            width: 48px;
            height: 48px;
            color: var(--cancel-danger);
            margin-bottom: 15px;
        }
        
        .es-cancel-error h2 {
            margin: 0 0 10px;
            font-size: 18px;
            color: var(--cancel-text);
        }
        
        .es-cancel-error p {
            margin: 0;
            color: var(--cancel-text-secondary);
        }
        
        .es-cancel-details {
            background: #f9f9f9;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .es-cancel-detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid var(--cancel-border);
        }
        
        .es-cancel-detail-row:last-child {
            border-bottom: none;
        }
        
        .es-cancel-detail-label {
            color: var(--cancel-text-secondary);
            font-size: 14px;
        }
        
        .es-cancel-detail-value {
            color: var(--cancel-text);
            font-weight: 500;
            font-size: 14px;
            text-align: right;
        }
        
        .es-cancel-warning {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 15px;
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.3);
            border-radius: 8px;
            margin-bottom: 25px;
        }
        
        .es-cancel-warning .dashicons {
            color: var(--cancel-warning);
            flex-shrink: 0;
        }
        
        .es-cancel-warning p {
            margin: 0;
            font-size: 14px;
            color: #92400e;
        }
        
        .es-cancel-actions {
            display: flex;
            gap: 12px;
        }
        
        .es-cancel-btn {
            flex: 1;
            padding: 14px 20px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            text-align: center;
        }
        
        .es-cancel-btn-back {
            background: #f0f0f0;
            color: var(--cancel-text);
        }
        
        .es-cancel-btn-back:hover {
            background: #e5e5e5;
        }
        
        .es-cancel-btn-confirm {
            background: var(--cancel-danger);
            color: #ffffff;
        }
        
        .es-cancel-btn-confirm:hover {
            background: #dc2626;
        }
        
        .es-cancel-btn-confirm:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        /* Success State */
        .es-cancel-success {
            text-align: center;
            padding: 20px;
        }
        
        .es-cancel-success .dashicons {
            font-size: 64px;
            width: 64px;
            height: 64px;
            color: var(--cancel-success);
            margin-bottom: 20px;
        }
        
        .es-cancel-success h2 {
            margin: 0 0 10px;
            font-size: 20px;
            color: var(--cancel-text);
        }
        
        .es-cancel-success p {
            margin: 0 0 25px;
            color: var(--cancel-text-secondary);
        }
        
        /* Loading */
        .es-cancel-loading {
            display: none;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .es-cancel-loading.active {
            display: flex;
        }
        
        .es-cancel-spinner {
            width: 20px;
            height: 20px;
            border: 2px solid #f0f0f0;
            border-top-color: var(--cancel-danger);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Responsive */
        @media (max-width: 480px) {
            .es-cancel-container {
                padding: 15px;
            }
            
            .es-cancel-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    
    <div class="es-cancel-container">
        <div class="es-cancel-card">
            
            <div class="es-cancel-header">
                <h1><?php echo esc_html($site_name); ?></h1>
                <p><?php _e('Booking Cancellation', 'ensemble'); ?></p>
            </div>
            
            <div class="es-cancel-body">
                
                <?php if ($error): ?>
                    <!-- Error State -->
                    <div class="es-cancel-error">
                        <span class="dashicons dashicons-warning"></span>
                        <h2><?php _e('Unable to Cancel', 'ensemble'); ?></h2>
                        <p><?php echo esc_html($error); ?></p>
                    </div>
                    <div class="es-cancel-actions">
                        <a href="<?php echo esc_url($home_url); ?>" class="es-cancel-btn es-cancel-btn-back">
                            <?php _e('Return Home', 'ensemble'); ?>
                        </a>
                    </div>
                    
                <?php elseif ($can_cancel): ?>
                    <!-- Cancel Form -->
                    <div id="cancel-form">
                        <div class="es-cancel-details">
                            <div class="es-cancel-detail-row">
                                <span class="es-cancel-detail-label"><?php _e('Confirmation Code', 'ensemble'); ?></span>
                                <span class="es-cancel-detail-value"><?php echo esc_html($booking->confirmation_code); ?></span>
                            </div>
                            <?php if ($event): ?>
                            <div class="es-cancel-detail-row">
                                <span class="es-cancel-detail-label"><?php _e('Event', 'ensemble'); ?></span>
                                <span class="es-cancel-detail-value"><?php echo esc_html($event->post_title); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if ($event_date): ?>
                            <div class="es-cancel-detail-row">
                                <span class="es-cancel-detail-label"><?php _e('Date', 'ensemble'); ?></span>
                                <span class="es-cancel-detail-value">
                                    <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($event_date))); ?>
                                    <?php if ($event_time): ?> · <?php echo esc_html($event_time); ?><?php endif; ?>
                                </span>
                            </div>
                            <?php endif; ?>
                            <div class="es-cancel-detail-row">
                                <span class="es-cancel-detail-label"><?php _e('Guest', 'ensemble'); ?></span>
                                <span class="es-cancel-detail-value"><?php echo esc_html($booking->customer_name); ?></span>
                            </div>
                            <div class="es-cancel-detail-row">
                                <span class="es-cancel-detail-label"><?php _e('Guests', 'ensemble'); ?></span>
                                <span class="es-cancel-detail-value"><?php echo absint($booking->guests); ?> <?php echo $booking->guests == 1 ? __('person', 'ensemble') : __('people', 'ensemble'); ?></span>
                            </div>
                        </div>
                        
                        <div class="es-cancel-warning">
                            <span class="dashicons dashicons-warning"></span>
                            <p><?php _e('Are you sure you want to cancel this booking? This action cannot be undone.', 'ensemble'); ?></p>
                        </div>
                        
                        <div class="es-cancel-actions">
                            <a href="<?php echo esc_url($home_url); ?>" class="es-cancel-btn es-cancel-btn-back">
                                <?php _e('Keep Booking', 'ensemble'); ?>
                            </a>
                            <button type="button" id="confirm-cancel" class="es-cancel-btn es-cancel-btn-confirm">
                                <?php _e('Cancel Booking', 'ensemble'); ?>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Loading State -->
                    <div class="es-cancel-loading" id="cancel-loading">
                        <div class="es-cancel-spinner"></div>
                        <span><?php _e('Cancelling...', 'ensemble'); ?></span>
                    </div>
                    
                    <!-- Success State (hidden initially) -->
                    <div class="es-cancel-success" id="cancel-success" style="display: none;">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <h2><?php _e('Booking Cancelled', 'ensemble'); ?></h2>
                        <p><?php _e('Your booking has been successfully cancelled. You will receive a confirmation email shortly.', 'ensemble'); ?></p>
                        <a href="<?php echo esc_url($home_url); ?>" class="es-cancel-btn es-cancel-btn-back">
                            <?php _e('Return Home', 'ensemble'); ?>
                        </a>
                    </div>
                    
                    <script>
                    document.getElementById('confirm-cancel').addEventListener('click', function() {
                        var btn = this;
                        var form = document.getElementById('cancel-form');
                        var loading = document.getElementById('cancel-loading');
                        var success = document.getElementById('cancel-success');
                        
                        btn.disabled = true;
                        form.style.display = 'none';
                        loading.classList.add('active');
                        
                        var xhr = new XMLHttpRequest();
                        xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>', true);
                        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                        
                        xhr.onload = function() {
                            loading.classList.remove('active');
                            
                            if (xhr.status === 200) {
                                var response = JSON.parse(xhr.responseText);
                                if (response.success) {
                                    success.style.display = 'block';
                                } else {
                                    form.style.display = 'block';
                                    btn.disabled = false;
                                    alert(response.data.message || '<?php _e('Cancellation failed', 'ensemble'); ?>');
                                }
                            } else {
                                form.style.display = 'block';
                                btn.disabled = false;
                                alert('<?php _e('An error occurred. Please try again.', 'ensemble'); ?>');
                            }
                        };
                        
                        xhr.onerror = function() {
                            loading.classList.remove('active');
                            form.style.display = 'block';
                            btn.disabled = false;
                            alert('<?php _e('An error occurred. Please try again.', 'ensemble'); ?>');
                        };
                        
                        xhr.send(
                            'action=es_cancel_booking' +
                            '&nonce=<?php echo wp_create_nonce('ensemble_booking_public'); ?>' +
                            '&confirmation_code=<?php echo esc_js($booking->confirmation_code); ?>' +
                            '&cancel_token=<?php echo esc_js($token); ?>'
                        );
                    });
                    </script>
                    
                <?php endif; ?>
                
            </div>
            
        </div>
    </div>
    
    <?php wp_footer(); ?>
</body>
</html>
