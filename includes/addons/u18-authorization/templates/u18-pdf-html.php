<?php
/**
 * U18 Authorization - PDF HTML Template
 * 
 * Neutral design with logo and digital signature support
 * 
 * @package Ensemble
 * @subpackage Addons/Reservations Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get event data with correct meta keys
$event_date = get_post_meta($event->ID, '_event_start_date', true);
if (empty($event_date)) {
    $event_date = get_post_meta($event->ID, 'es_event_start_date', true);
}
$event_time = get_post_meta($event->ID, '_event_start_time', true);
if (empty($event_time)) {
    $event_time = get_post_meta($event->ID, 'es_event_start_time', true);
}

// Get location
$location_name = '';
$location_id = get_post_meta($event->ID, '_event_location', true);
if ($location_id) {
    $location_post = get_post($location_id);
    if ($location_post) {
        $location_name = $location_post->post_title;
    }
}

// Calculate ages
$minor_age = (new DateTime($auth->minor_birthdate))->diff(new DateTime())->y;
$companion_age = (new DateTime($auth->companion_birthdate))->diff(new DateTime())->y;

// Get site logo
$site_logo = '';
$custom_logo_id = get_theme_mod('custom_logo');
if ($custom_logo_id) {
    $site_logo = wp_get_attachment_image_url($custom_logo_id, 'medium');
}

// Get accent color (optional)
$accent_color = '#333333';
$event_accent = get_post_meta($event->ID, '_ensemble_accent_color', true);
if ($event_accent) {
    $accent_color = $event_accent;
}

// QR Code URL
$checkin_url = add_query_arg(array('u18_check' => $auth->authorization_code), home_url('/'));
$qr_code_url = 'https://api.qrserver.com/v1/create-qr-code/?size=120x120&format=png&data=' . urlencode($checkin_url);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title><?php echo esc_html(sprintf(__('Aufsichtsübertragung - %s', 'ensemble'), $auth->authorization_code)); ?></title>
    <style>
        @page {
            size: A4;
            margin: 15mm;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.5;
            color: #333;
            background: #fff;
        }
        
        .document {
            max-width: 800px;
            margin: 0 auto;
        }
        
        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .logo-area {
            flex: 1;
        }
        
        .logo-area img {
            max-height: 50px;
            max-width: 150px;
        }
        
        .logo-area h1 {
            font-size: 18pt;
            font-weight: 600;
            color: #111;
            margin: 0;
        }
        
        .logo-area .subtitle {
            font-size: 9pt;
            color: #666;
            margin-top: 3px;
        }
        
        .code-area {
            text-align: right;
        }
        
        .auth-code {
            font-family: 'SF Mono', Monaco, 'Courier New', monospace;
            font-size: 16pt;
            font-weight: 700;
            color: #111;
            background: #f5f5f5;
            padding: 8px 15px;
            border: 1px solid #ddd;
            letter-spacing: 2px;
        }
        
        .legal-ref {
            font-size: 8pt;
            color: #888;
            margin-top: 8px;
        }
        
        /* Event Section */
        .event-box {
            background: #fafafa;
            border-left: 3px solid <?php echo esc_attr($accent_color); ?>;
            padding: 12px 15px;
            margin-bottom: 20px;
        }
        
        .event-box .label {
            font-size: 8pt;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .event-box .event-title {
            font-size: 13pt;
            font-weight: 600;
            color: #111;
            margin: 3px 0;
        }
        
        .event-box .event-details {
            font-size: 9pt;
            color: #555;
        }
        
        /* Person Sections */
        .section-title {
            font-size: 9pt;
            font-weight: 600;
            color: #111;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        
        .persons-grid {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .person-box {
            flex: 1;
            border: 1px solid #ddd;
            padding: 12px;
        }
        
        .person-box .label {
            font-size: 8pt;
            color: #888;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        
        .person-box .person-name {
            font-size: 11pt;
            font-weight: 600;
            color: #111;
            margin-bottom: 5px;
        }
        
        .person-box .person-details {
            font-size: 9pt;
            color: #555;
            line-height: 1.6;
        }
        
        .age-badge {
            display: inline-block;
            background: #f0f0f0;
            color: #333;
            padding: 1px 6px;
            font-size: 8pt;
            margin-left: 5px;
        }
        
        /* Legal Text */
        .legal-section {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 15px;
            font-size: 9pt;
            line-height: 1.6;
        }
        
        .legal-section h3 {
            font-size: 10pt;
            font-weight: 600;
            color: #111;
            margin-bottom: 10px;
        }
        
        .legal-section p {
            margin-bottom: 8px;
            color: #333;
        }
        
        /* Signatures */
        .signatures {
            display: flex;
            gap: 20px;
            margin: 20px 0;
        }
        
        .signature-box {
            flex: 1;
            text-align: center;
        }
        
        .signature-area {
            height: 60px;
            border-bottom: 1px solid #333;
            margin-bottom: 5px;
            display: flex;
            align-items: flex-end;
            justify-content: center;
        }
        
        .signature-area img {
            max-height: 55px;
            max-width: 100%;
        }
        
        .signature-label {
            font-size: 8pt;
            color: #666;
        }
        
        /* Warning Box */
        .warning-box {
            background: #f9f9f9;
            border: 1px solid #ddd;
            padding: 12px 15px;
            margin: 15px 0;
            font-size: 8pt;
        }
        
        .warning-box h4 {
            font-size: 9pt;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        
        .warning-box ul {
            margin: 0;
            padding-left: 15px;
            color: #555;
        }
        
        .warning-box li {
            margin-bottom: 4px;
        }
        
        /* QR Section */
        .qr-section {
            display: flex;
            align-items: center;
            gap: 15px;
            background: #fafafa;
            border: 1px solid #ddd;
            padding: 15px;
            margin: 20px 0;
        }
        
        .qr-code img {
            width: 100px;
            height: 100px;
        }
        
        .qr-info {
            flex: 1;
        }
        
        .qr-info .label {
            font-size: 8pt;
            color: #888;
            text-transform: uppercase;
        }
        
        .qr-info .code {
            font-family: 'SF Mono', Monaco, 'Courier New', monospace;
            font-size: 14pt;
            font-weight: 600;
            color: #111;
            letter-spacing: 2px;
        }
        
        .qr-info .hint {
            font-size: 8pt;
            color: #666;
            margin-top: 5px;
        }
        
        /* Footer */
        .footer {
            border-top: 1px solid #ddd;
            padding-top: 10px;
            margin-top: 20px;
            font-size: 8pt;
            color: #888;
            display: flex;
            justify-content: space-between;
        }
        
        /* Print Styles */
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="document">
        
        <!-- Header -->
        <div class="header">
            <div class="logo-area">
                <?php if ($site_logo): ?>
                    <img src="<?php echo esc_url($site_logo); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>">
                <?php endif; ?>
                <h1><?php _e('Aufsichtsübertragung', 'ensemble'); ?></h1>
                <div class="subtitle"><?php _e('gemäß § 1 Abs. 1 Nr. 4 JuSchG', 'ensemble'); ?></div>
            </div>
            <div class="code-area">
                <div class="auth-code"><?php echo esc_html($auth->authorization_code); ?></div>
                <div class="legal-ref"><?php _e('Referenznummer', 'ensemble'); ?></div>
            </div>
        </div>
        
        <!-- Event Info -->
        <div class="event-box">
            <div class="label"><?php _e('Veranstaltung', 'ensemble'); ?></div>
            <div class="event-title"><?php echo esc_html($event->post_title); ?></div>
            <div class="event-details">
                <?php if ($event_date): ?>
                    <?php echo esc_html(date_i18n('l, d.m.Y', strtotime($event_date))); ?>
                <?php endif; ?>
                <?php if ($event_time): ?>
                    · <?php echo esc_html($event_time); ?> Uhr
                <?php endif; ?>
                <?php if ($location_name): ?>
                    · <?php echo esc_html($location_name); ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Parent/Guardian -->
        <div class="section-title"><?php _e('Erziehungsberechtigter', 'ensemble'); ?></div>
        <div class="person-box" style="margin-bottom: 20px;">
            <div class="person-name"><?php echo esc_html($auth->parent_firstname . ' ' . $auth->parent_lastname); ?></div>
            <div class="person-details">
                <?php echo esc_html($auth->parent_street); ?>, <?php echo esc_html($auth->parent_zip . ' ' . $auth->parent_city); ?><br>
                Tel: <?php echo esc_html($auth->parent_phone); ?> · E-Mail: <?php echo esc_html($auth->parent_email); ?>
            </div>
        </div>
        
        <!-- Minor & Companion -->
        <div class="persons-grid">
            <div class="person-box">
                <div class="label"><?php _e('Minderjährige Person', 'ensemble'); ?></div>
                <div class="person-name">
                    <?php echo esc_html($auth->minor_firstname . ' ' . $auth->minor_lastname); ?>
                    <span class="age-badge"><?php echo $minor_age; ?> Jahre</span>
                </div>
                <div class="person-details">
                    Geb. <?php echo esc_html(date_i18n('d.m.Y', strtotime($auth->minor_birthdate))); ?><br>
                    <?php echo esc_html($auth->minor_street); ?><br>
                    <?php echo esc_html($auth->minor_zip . ' ' . $auth->minor_city); ?>
                </div>
            </div>
            <div class="person-box">
                <div class="label"><?php _e('Begleitperson (18+)', 'ensemble'); ?></div>
                <div class="person-name">
                    <?php echo esc_html($auth->companion_firstname . ' ' . $auth->companion_lastname); ?>
                    <span class="age-badge"><?php echo $companion_age; ?> Jahre</span>
                </div>
                <div class="person-details">
                    Geb. <?php echo esc_html(date_i18n('d.m.Y', strtotime($auth->companion_birthdate))); ?><br>
                    <?php echo esc_html($auth->companion_street); ?><br>
                    <?php echo esc_html($auth->companion_zip . ' ' . $auth->companion_city); ?>
                    <?php if ($auth->companion_phone): ?>
                        <br>Tel: <?php echo esc_html($auth->companion_phone); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Legal Declaration -->
        <div class="legal-section">
            <h3><?php _e('Erklärung des Erziehungsberechtigten', 'ensemble'); ?></h3>
            <p><?php _e('Ich übertrage hiermit die Aufsichtspflicht für mein Kind auf die oben genannte volljährige Begleitperson für den Besuch der genannten Veranstaltung. Ich kenne die Begleitperson und vertraue ihr. Es besteht ein Autoritätsverhältnis.', 'ensemble'); ?></p>
            <p><?php _e('Mir ist bekannt, dass alle Personen sich am Einlass mit gültigem Personalausweis ausweisen müssen.', 'ensemble'); ?></p>
        </div>
        
        <!-- Parent Signature -->
        <div class="signatures">
            <div class="signature-box">
                <div class="signature-area">
                    <?php if (!empty($auth->parent_signature)): ?>
                        <img src="<?php echo esc_attr($auth->parent_signature); ?>" alt="Unterschrift">
                    <?php endif; ?>
                </div>
                <div class="signature-label"><?php _e('Ort, Datum, Unterschrift Erziehungsberechtigter', 'ensemble'); ?></div>
            </div>
        </div>
        
        <!-- Companion Declaration -->
        <div class="legal-section">
            <h3><?php _e('Erklärung der Begleitperson', 'ensemble'); ?></h3>
            <p style="font-size: 8pt;"><?php _e('Ich bestätige, dass ich die Aufsichtspflicht für die oben genannte minderjährige Person während der gesamten Veranstaltung übernehme. Ich werde gemeinsam mit ihr erscheinen und die Veranstaltung verlassen. Ich sorge für die Einhaltung des Jugendschutzes.', 'ensemble'); ?></p>
        </div>
        
        <!-- Companion Signature -->
        <div class="signatures">
            <div class="signature-box">
                <div class="signature-area">
                    <?php if (!empty($auth->companion_signature)): ?>
                        <img src="<?php echo esc_attr($auth->companion_signature); ?>" alt="Unterschrift">
                    <?php endif; ?>
                </div>
                <div class="signature-label"><?php _e('Ort, Datum, Unterschrift Begleitperson', 'ensemble'); ?></div>
            </div>
        </div>
        
        <!-- Warning Box -->
        <div class="warning-box">
            <h4><?php _e('Hinweise', 'ensemble'); ?></h4>
            <ul>
                <li><?php _e('Alle Personen müssen einen gültigen Personalausweis vorzeigen.', 'ensemble'); ?></li>
                <li><?php _e('Die Begleitperson und der Minderjährige müssen gemeinsam erscheinen.', 'ensemble'); ?></li>
                <li><?php _e('Urkundenfälschung ist strafbar (§ 267 StGB).', 'ensemble'); ?></li>
            </ul>
        </div>
        
        <!-- QR Code Section -->
        <div class="qr-section">
            <div class="qr-code">
                <img src="<?php echo esc_url($qr_code_url); ?>" alt="QR Code">
            </div>
            <div class="qr-info">
                <div class="label"><?php _e('Check-in Code', 'ensemble'); ?></div>
                <div class="code"><?php echo esc_html($auth->authorization_code); ?></div>
                <div class="hint"><?php _e('QR-Code am Einlass scannen oder Code nennen', 'ensemble'); ?></div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <span><?php echo esc_html(get_bloginfo('name')); ?></span>
            <span><?php _e('Erstellt:', 'ensemble'); ?> <?php echo esc_html(date_i18n('d.m.Y H:i', strtotime($auth->created_at))); ?></span>
        </div>
        
        <!-- Print Button -->
        <div class="no-print" style="text-align: center; margin-top: 30px;">
            <button onclick="window.print()" style="padding: 12px 30px; font-size: 12pt; background: #333; color: #fff; border: none; cursor: pointer;">
                <?php _e('Drucken / Als PDF speichern', 'ensemble'); ?>
            </button>
        </div>
        
    </div>
</body>
</html>
