<?php
/**
 * U18 Authorization Status Page
 * 
 * Public page shown when QR code is scanned
 * 
 * @package Ensemble
 * @subpackage Addons/Reservations Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get site info
$site_name = get_bloginfo('name');
$site_url = home_url('/');

// Authorization already loaded in handle_qr_checkin()
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php _e('Aufsichtsübertragung - Status', 'ensemble'); ?> | <?php echo esc_html($site_name); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .status-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
            max-width: 450px;
            width: 100%;
            overflow: hidden;
        }
        .status-header {
            padding: 30px;
            text-align: center;
        }
        .status-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 40px;
        }
        .status-icon.valid {
            background: #d1fae5;
        }
        .status-icon.invalid {
            background: #fee2e2;
        }
        .status-icon.pending {
            background: #fef3c7;
        }
        .status-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 8px;
            color: #1e293b;
        }
        .status-subtitle {
            font-size: 14px;
            color: #64748b;
        }
        .status-body {
            padding: 0 30px 30px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-size: 13px;
            color: #64748b;
        }
        .info-value {
            font-size: 14px;
            font-weight: 600;
            color: #1e293b;
            text-align: right;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-approved {
            background: #d1fae5;
            color: #065f46;
        }
        .badge-pending {
            background: #fef3c7;
            color: #92400e;
        }
        .badge-rejected {
            background: #fee2e2;
            color: #991b1b;
        }
        .badge-used {
            background: #dbeafe;
            color: #1e40af;
        }
        .status-footer {
            background: #f8fafc;
            padding: 20px 30px;
            text-align: center;
        }
        .status-footer a {
            color: #3b82f6;
            text-decoration: none;
            font-size: 14px;
        }
        .error-card {
            background: #fff;
            border-radius: 16px;
            padding: 40px;
            text-align: center;
            max-width: 400px;
        }
        .error-icon {
            font-size: 48px;
            margin-bottom: 20px;
        }
        .error-title {
            font-size: 20px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 10px;
        }
        .error-message {
            color: #64748b;
            font-size: 14px;
        }
    </style>
</head>
<body>

<?php if (!$auth): ?>
    <div class="error-card">
        <div class="error-icon">❌</div>
        <div class="error-title"><?php _e('Nicht gefunden', 'ensemble'); ?></div>
        <div class="error-message"><?php _e('Dieser Autorisierungscode ist ungültig oder wurde nicht gefunden.', 'ensemble'); ?></div>
    </div>
<?php else: 
    $event = get_post($auth->event_id);
    $event_date = get_post_meta($auth->event_id, '_event_start_date', true);
    if (empty($event_date)) {
        $event_date = get_post_meta($auth->event_id, 'es_event_start_date', true);
    }
    
    // Determine status display
    $status_icon = '⏳';
    $status_class = 'pending';
    $status_label = __('In Bearbeitung', 'ensemble');
    $badge_class = 'badge-pending';
    
    switch ($auth->status) {
        case 'approved':
            $status_icon = '✓';
            $status_class = 'valid';
            $status_label = __('Genehmigt', 'ensemble');
            $badge_class = 'badge-approved';
            break;
        case 'rejected':
            $status_icon = '✗';
            $status_class = 'invalid';
            $status_label = __('Abgelehnt', 'ensemble');
            $badge_class = 'badge-rejected';
            break;
        case 'used':
            $status_icon = '✓';
            $status_class = 'valid';
            $status_label = __('Eingecheckt', 'ensemble');
            $badge_class = 'badge-used';
            break;
        case 'submitted':
        case 'reviewed':
            $status_icon = '⏳';
            $status_class = 'pending';
            $status_label = __('In Prüfung', 'ensemble');
            $badge_class = 'badge-pending';
            break;
    }
?>
    <div class="status-card">
        <div class="status-header">
            <div class="status-icon <?php echo $status_class; ?>">
                <?php echo $status_icon; ?>
            </div>
            <div class="status-title"><?php _e('Aufsichtsübertragung', 'ensemble'); ?></div>
            <div class="status-subtitle"><?php _e('Status-Übersicht', 'ensemble'); ?></div>
        </div>
        
        <div class="status-body">
            <div class="info-row">
                <span class="info-label"><?php _e('Status', 'ensemble'); ?></span>
                <span class="info-value">
                    <span class="status-badge <?php echo $badge_class; ?>"><?php echo esc_html($status_label); ?></span>
                </span>
            </div>
            
            <div class="info-row">
                <span class="info-label"><?php _e('Code', 'ensemble'); ?></span>
                <span class="info-value"><?php echo esc_html($auth->authorization_code); ?></span>
            </div>
            
            <div class="info-row">
                <span class="info-label"><?php _e('Veranstaltung', 'ensemble'); ?></span>
                <span class="info-value"><?php echo esc_html($event ? $event->post_title : '-'); ?></span>
            </div>
            
            <?php if ($event_date): ?>
            <div class="info-row">
                <span class="info-label"><?php _e('Datum', 'ensemble'); ?></span>
                <span class="info-value"><?php echo date_i18n('d.m.Y', strtotime($event_date)); ?></span>
            </div>
            <?php endif; ?>
            
            <div class="info-row">
                <span class="info-label"><?php _e('Minderjährig', 'ensemble'); ?></span>
                <span class="info-value"><?php echo esc_html($auth->minor_firstname . ' ' . $auth->minor_lastname); ?></span>
            </div>
            
            <div class="info-row">
                <span class="info-label"><?php _e('Geburtsdatum', 'ensemble'); ?></span>
                <span class="info-value"><?php echo date_i18n('d.m.Y', strtotime($auth->minor_birthdate)); ?></span>
            </div>
            
            <div class="info-row">
                <span class="info-label"><?php _e('Begleitperson', 'ensemble'); ?></span>
                <span class="info-value"><?php echo esc_html($auth->companion_firstname . ' ' . $auth->companion_lastname); ?></span>
            </div>
            
            <?php if ($auth->checked_in_at): ?>
            <div class="info-row">
                <span class="info-label"><?php _e('Eingecheckt', 'ensemble'); ?></span>
                <span class="info-value"><?php echo date_i18n('d.m.Y H:i', strtotime($auth->checked_in_at)); ?></span>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="status-footer">
            <a href="<?php echo esc_url($site_url); ?>"><?php echo esc_html($site_name); ?></a>
        </div>
    </div>
<?php endif; ?>

</body>
</html>
