<?php
/**
 * Abstract Email Handler
 * 
 * Handles email notifications for abstract submissions
 *
 * @package Ensemble
 * @subpackage Addons/Staff
 * @since 2.8.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class ES_Abstract_Emails {
    
    /**
     * Settings reference
     * @var array
     */
    private $settings;
    
    /**
     * Constructor
     * 
     * @param array $settings Addon settings
     */
    public function __construct($settings = array()) {
        $this->settings = wp_parse_args($settings, array(
            'send_confirmation'    => true,
            'send_admin_copy'      => true,
            'admin_email'          => get_option('admin_email'),
            'email_from_name'      => get_bloginfo('name'),
            'email_from_address'   => get_option('admin_email'),
        ));
        
        // Hook into status changes
        add_action('ensemble_abstract_status_changed', array($this, 'send_status_notification'), 10, 3);
    }
    
    /**
     * Send submission notification to staff member
     * 
     * @param array $data Submission data
     * @param array $staff Staff member data
     * @return bool
     */
    public function send_staff_notification($data, $staff) {
        if (empty($staff['email'])) {
            return false;
        }
        
        $to = $staff['email'];
        $subject = sprintf(__('[New Submission] %s', 'ensemble'), $data['title']);
        
        $body = $this->get_email_template('staff-notification', array(
            'staff_name'     => $staff['name'],
            'submitter_name' => $data['name'],
            'submitter_email'=> $data['email'],
            'title'          => $data['title'],
            'message'        => $data['message'],
            'attachment_url' => $data['attachment_url'],
            'admin_url'      => admin_url('admin.php?page=ensemble-staff&tab=abstracts'),
        ));
        
        $headers = $this->get_email_headers($data['name'], $data['email']);
        
        return wp_mail($to, $subject, $body, $headers);
    }
    
    /**
     * Send confirmation email to submitter
     * 
     * @param array $data Submission data
     * @param array $staff Staff member data
     * @return bool
     */
    public function send_confirmation_email($data, $staff) {
        if (!$this->settings['send_confirmation']) {
            return true;
        }
        
        $to = $data['email'];
        $subject = sprintf(__('Submission Received: %s', 'ensemble'), $data['title']);
        
        $body = $this->get_email_template('submitter-confirmation', array(
            'submitter_name' => $data['name'],
            'title'          => $data['title'],
            'staff_name'     => $staff['name'],
            'site_name'      => get_bloginfo('name'),
        ));
        
        $headers = $this->get_email_headers();
        
        return wp_mail($to, $subject, $body, $headers);
    }
    
    /**
     * Send copy to admin
     * 
     * @param array $data Submission data
     * @param array $staff Staff member data
     * @return bool
     */
    public function send_admin_copy($data, $staff) {
        if (!$this->settings['send_admin_copy'] || empty($this->settings['admin_email'])) {
            return true;
        }
        
        // Don't send if admin email is same as staff email
        if ($this->settings['admin_email'] === $staff['email']) {
            return true;
        }
        
        $to = $this->settings['admin_email'];
        $subject = sprintf(__('[Copy] New Submission: %s', 'ensemble'), $data['title']);
        
        $body = $this->get_email_template('admin-copy', array(
            'staff_name'     => $staff['name'],
            'staff_email'    => $staff['email'],
            'submitter_name' => $data['name'],
            'submitter_email'=> $data['email'],
            'title'          => $data['title'],
            'message'        => $data['message'],
            'attachment_url' => $data['attachment_url'],
            'admin_url'      => admin_url('admin.php?page=ensemble-staff&tab=abstracts'),
        ));
        
        $headers = $this->get_email_headers();
        
        return wp_mail($to, $subject, $body, $headers);
    }
    
    /**
     * Send status change notification to submitter
     * 
     * @param int    $abstract_id
     * @param string $status
     * @param string $note
     */
    public function send_status_notification($abstract_id, $status, $note = '') {
        // Only notify on certain status changes
        $notify_statuses = array(
            ES_Abstract_Manager::STATUS_ACCEPTED,
            ES_Abstract_Manager::STATUS_REJECTED,
            ES_Abstract_Manager::STATUS_REVISION,
        );
        
        if (!in_array($status, $notify_statuses)) {
            return;
        }
        
        $abstract_manager = new ES_Abstract_Manager();
        $abstract = $abstract_manager->get_abstract($abstract_id);
        
        if (!$abstract) {
            return;
        }
        
        $to = $abstract['submitter_email'];
        $statuses = ES_Abstract_Manager::get_statuses();
        $status_label = $statuses[$status]['label'];
        
        $subject = sprintf(__('Update on Your Submission: %s', 'ensemble'), $abstract['title']);
        
        $body = $this->get_email_template('status-update', array(
            'submitter_name' => $abstract['submitter_name'],
            'title'          => $abstract['title'],
            'status'         => $status_label,
            'note'           => $note,
            'site_name'      => get_bloginfo('name'),
        ));
        
        $headers = $this->get_email_headers();
        
        wp_mail($to, $subject, $body, $headers);
    }
    
    /**
     * Get email headers
     * 
     * @param string $reply_name  Reply-to name
     * @param string $reply_email Reply-to email
     * @return array
     */
    private function get_email_headers($reply_name = '', $reply_email = '') {
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            sprintf('From: %s <%s>', $this->settings['email_from_name'], $this->settings['email_from_address']),
        );
        
        if (!empty($reply_email)) {
            $headers[] = sprintf('Reply-To: %s <%s>', $reply_name, $reply_email);
        }
        
        return $headers;
    }
    
    /**
     * Get email template
     * 
     * @param string $template Template name
     * @param array  $data     Template data
     * @return string
     */
    private function get_email_template($template, $data) {
        $data = wp_parse_args($data, array(
            'site_name' => get_bloginfo('name'),
            'site_url'  => home_url(),
        ));
        
        ob_start();
        
        switch ($template) {
            case 'staff-notification':
                $this->template_staff_notification($data);
                break;
                
            case 'submitter-confirmation':
                $this->template_submitter_confirmation($data);
                break;
                
            case 'admin-copy':
                $this->template_admin_copy($data);
                break;
                
            case 'status-update':
                $this->template_status_update($data);
                break;
        }
        
        return ob_get_clean();
    }
    
    /**
     * Template: Staff notification
     */
    private function template_staff_notification($data) {
        ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #2271b1; color: #fff; padding: 20px; border-radius: 8px 8px 0 0; }
        .content { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; border-top: none; }
        .field { margin-bottom: 15px; }
        .field-label { font-weight: 600; color: #666; font-size: 12px; text-transform: uppercase; }
        .field-value { margin-top: 5px; }
        .message-box { background: #fff; padding: 15px; border-left: 4px solid #2271b1; margin: 15px 0; }
        .button { display: inline-block; background: #2271b1; color: #fff; padding: 12px 24px; text-decoration: none; border-radius: 4px; margin-top: 15px; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="margin: 0; font-size: 20px;">New Abstract Submission</h1>
        </div>
        <div class="content">
            <p>Hello <?php echo esc_html($data['staff_name']); ?>,</p>
            <p>You have received a new abstract submission:</p>
            
            <div class="field">
                <div class="field-label">Title</div>
                <div class="field-value"><strong><?php echo esc_html($data['title']); ?></strong></div>
            </div>
            
            <div class="field">
                <div class="field-label">Submitted By</div>
                <div class="field-value"><?php echo esc_html($data['submitter_name']); ?> (<?php echo esc_html($data['submitter_email']); ?>)</div>
            </div>
            
            <?php if (!empty($data['message'])) : ?>
            <div class="field">
                <div class="field-label">Message</div>
                <div class="message-box"><?php echo nl2br(esc_html($data['message'])); ?></div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($data['attachment_url'])) : ?>
            <div class="field">
                <div class="field-label">Attachment</div>
                <div class="field-value"><a href="<?php echo esc_url($data['attachment_url']); ?>">Download File</a></div>
            </div>
            <?php endif; ?>
            
            <a href="<?php echo esc_url($data['admin_url']); ?>" class="button">View in Dashboard</a>
        </div>
        <div class="footer">
            This email was sent from <?php echo esc_html($data['site_name']); ?>
        </div>
    </div>
</body>
</html>
        <?php
    }
    
    /**
     * Template: Submitter confirmation
     */
    private function template_submitter_confirmation($data) {
        ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #00a32a; color: #fff; padding: 20px; border-radius: 8px 8px 0 0; text-align: center; }
        .header .icon { font-size: 40px; margin-bottom: 10px; }
        .content { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; border-top: none; }
        .highlight { background: #fff; padding: 15px; border-radius: 4px; margin: 15px 0; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="icon">✓</div>
            <h1 style="margin: 0; font-size: 20px;">Submission Received</h1>
        </div>
        <div class="content">
            <p>Dear <?php echo esc_html($data['submitter_name']); ?>,</p>
            
            <p>Thank you for your submission. We have successfully received your abstract:</p>
            
            <div class="highlight">
                <strong><?php echo esc_html($data['title']); ?></strong>
            </div>
            
            <p>Your submission has been forwarded to <strong><?php echo esc_html($data['staff_name']); ?></strong> for review. You will be notified once a decision has been made.</p>
            
            <p>If you have any questions, please don't hesitate to contact us.</p>
            
            <p>Best regards,<br><?php echo esc_html($data['site_name']); ?></p>
        </div>
        <div class="footer">
            This is an automated confirmation email from <?php echo esc_html($data['site_name']); ?>
        </div>
    </div>
</body>
</html>
        <?php
    }
    
    /**
     * Template: Admin copy
     */
    private function template_admin_copy($data) {
        ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #666; color: #fff; padding: 15px 20px; border-radius: 8px 8px 0 0; }
        .content { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; border-top: none; }
        .field { margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid #eee; }
        .field:last-child { border-bottom: none; }
        .field-label { font-weight: 600; color: #666; font-size: 11px; text-transform: uppercase; }
        .field-value { margin-top: 3px; }
        .button { display: inline-block; background: #2271b1; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 4px; margin-top: 15px; font-size: 14px; }
        .footer { text-align: center; padding: 15px; color: #999; font-size: 11px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="margin: 0; font-size: 16px;">📋 Admin Copy: New Abstract Submission</h1>
        </div>
        <div class="content">
            <div class="field">
                <div class="field-label">Assigned To</div>
                <div class="field-value"><?php echo esc_html($data['staff_name']); ?> (<?php echo esc_html($data['staff_email']); ?>)</div>
            </div>
            
            <div class="field">
                <div class="field-label">Title</div>
                <div class="field-value"><strong><?php echo esc_html($data['title']); ?></strong></div>
            </div>
            
            <div class="field">
                <div class="field-label">Submitter</div>
                <div class="field-value"><?php echo esc_html($data['submitter_name']); ?> (<?php echo esc_html($data['submitter_email']); ?>)</div>
            </div>
            
            <?php if (!empty($data['attachment_url'])) : ?>
            <div class="field">
                <div class="field-label">Attachment</div>
                <div class="field-value"><a href="<?php echo esc_url($data['attachment_url']); ?>">Download</a></div>
            </div>
            <?php endif; ?>
            
            <a href="<?php echo esc_url($data['admin_url']); ?>" class="button">Manage Abstracts</a>
        </div>
        <div class="footer">
            This is an admin notification from <?php echo esc_html($data['site_name']); ?>
        </div>
    </div>
</body>
</html>
        <?php
    }
    
    /**
     * Template: Status update
     */
    private function template_status_update($data) {
        $status_colors = array(
            'Accepted' => '#00a32a',
            'Rejected' => '#d63638',
            'Revision Requested' => '#9966cc',
        );
        $color = isset($status_colors[$data['status']]) ? $status_colors[$data['status']] : '#2271b1';
        ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: <?php echo esc_attr($color); ?>; color: #fff; padding: 20px; border-radius: 8px 8px 0 0; text-align: center; }
        .content { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; border-top: none; }
        .status-badge { display: inline-block; background: <?php echo esc_attr($color); ?>; color: #fff; padding: 5px 15px; border-radius: 20px; font-weight: 600; }
        .highlight { background: #fff; padding: 15px; border-radius: 4px; margin: 15px 0; }
        .note-box { background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 15px 0; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="margin: 0; font-size: 20px;">Submission Update</h1>
        </div>
        <div class="content">
            <p>Dear <?php echo esc_html($data['submitter_name']); ?>,</p>
            
            <p>There is an update regarding your submission:</p>
            
            <div class="highlight">
                <strong><?php echo esc_html($data['title']); ?></strong>
            </div>
            
            <p>Status: <span class="status-badge"><?php echo esc_html($data['status']); ?></span></p>
            
            <?php if (!empty($data['note'])) : ?>
            <div class="note-box">
                <strong>Note from reviewer:</strong><br>
                <?php echo nl2br(esc_html($data['note'])); ?>
            </div>
            <?php endif; ?>
            
            <?php if ($data['status'] === 'Accepted') : ?>
            <p>Congratulations! Your submission has been accepted.</p>
            <?php elseif ($data['status'] === 'Revision Requested') : ?>
            <p>Please review the feedback above and submit a revised version.</p>
            <?php endif; ?>
            
            <p>If you have any questions, please don't hesitate to contact us.</p>
            
            <p>Best regards,<br><?php echo esc_html($data['site_name']); ?></p>
        </div>
        <div class="footer">
            This email was sent from <?php echo esc_html($data['site_name']); ?>
        </div>
    </div>
</body>
</html>
        <?php
    }
}
