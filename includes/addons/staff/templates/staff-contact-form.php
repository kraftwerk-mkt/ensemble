<?php
/**
 * Staff Contact Form Template (Abstract Submission)
 * 
 * @package Ensemble
 * @subpackage Addons/Staff
 * 
 * Variables:
 * @var array $staff  Staff member data
 * @var array $atts   Shortcode attributes
 */

if (!defined('ABSPATH')) {
    exit;
}

$form_id = 'es-contact-form-' . $staff['id'];
$max_size_mb = $staff['abstract_max_size'];
$accepted_types = $staff['abstract_types'];

// Build accept attribute
$accept_map = array(
    'pdf' => '.pdf',
    'doc' => '.doc,.docx',
    'ppt' => '.ppt,.pptx',
);
$accept_attr = array();
foreach ($accepted_types as $type) {
    if (isset($accept_map[$type])) {
        $accept_attr[] = $accept_map[$type];
    }
}
$accept_string = implode(',', $accept_attr);

// Human-readable file types
$type_names = array(
    'pdf' => 'PDF',
    'doc' => 'Word',
    'ppt' => 'PowerPoint',
);
$readable_types = array();
foreach ($accepted_types as $type) {
    if (isset($type_names[$type])) {
        $readable_types[] = $type_names[$type];
    }
}
?>

<div class="es-contact-form-wrapper" id="<?php echo esc_attr($form_id); ?>-wrapper">
    <?php if (!empty($atts['title'])) : ?>
        <h3 class="es-contact-form__title"><?php echo esc_html($atts['title']); ?></h3>
    <?php endif; ?>
    
    <?php if (!empty($atts['description'])) : ?>
        <p class="es-contact-form__description"><?php echo esc_html($atts['description']); ?></p>
    <?php endif; ?>
    
    <div class="es-contact-form__recipient">
        <?php if ($staff['featured_image']) : ?>
            <img src="<?php echo esc_url($staff['featured_image']); ?>" 
                 alt="<?php echo esc_attr($staff['name']); ?>"
                 class="es-contact-form__recipient-image">
        <?php endif; ?>
        <div class="es-contact-form__recipient-info">
            <strong><?php echo esc_html($staff['name']); ?></strong>
            <?php if ($staff['position']) : ?>
                <span><?php echo esc_html($staff['position']); ?></span>
            <?php endif; ?>
            <?php if ($staff['responsibility']) : ?>
                <span><?php echo esc_html($staff['responsibility']); ?></span>
            <?php endif; ?>
        </div>
    </div>
    
    <form class="es-contact-form" id="<?php echo esc_attr($form_id); ?>" enctype="multipart/form-data">
        <input type="hidden" name="staff_id" value="<?php echo esc_attr($staff['id']); ?>">
        
        <div class="es-contact-form__row es-contact-form__row--2col">
            <div class="es-contact-form__field">
                <label for="<?php echo esc_attr($form_id); ?>-name">
                    <?php _e('Your Name', 'ensemble'); ?> <span class="required">*</span>
                </label>
                <input type="text" 
                       id="<?php echo esc_attr($form_id); ?>-name" 
                       name="submitter_name" 
                       required>
            </div>
            
            <div class="es-contact-form__field">
                <label for="<?php echo esc_attr($form_id); ?>-email">
                    <?php _e('Your Email', 'ensemble'); ?> <span class="required">*</span>
                </label>
                <input type="email" 
                       id="<?php echo esc_attr($form_id); ?>-email" 
                       name="submitter_email" 
                       required>
            </div>
        </div>
        
        <div class="es-contact-form__field">
            <label for="<?php echo esc_attr($form_id); ?>-title">
                <?php _e('Title / Subject', 'ensemble'); ?> <span class="required">*</span>
            </label>
            <input type="text" 
                   id="<?php echo esc_attr($form_id); ?>-title" 
                   name="abstract_title" 
                   required>
        </div>
        
        <div class="es-contact-form__field">
            <label for="<?php echo esc_attr($form_id); ?>-message">
                <?php _e('Message / Abstract', 'ensemble'); ?>
            </label>
            <textarea id="<?php echo esc_attr($form_id); ?>-message" 
                      name="abstract_message" 
                      rows="5"></textarea>
        </div>
        
        <div class="es-contact-form__field">
            <label for="<?php echo esc_attr($form_id); ?>-file">
                <?php _e('File Upload', 'ensemble'); ?>
            </label>
            <div class="es-contact-form__file-wrapper">
                <input type="file" 
                       id="<?php echo esc_attr($form_id); ?>-file" 
                       name="abstract_file"
                       accept="<?php echo esc_attr($accept_string); ?>"
                       class="es-contact-form__file-input">
                <div class="es-contact-form__file-display">
                    <span class="es-contact-form__file-placeholder">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="24" height="24">
                            <path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm4 18H6V4h7v5h5v11z"/>
                        </svg>
                        <?php _e('Choose file or drag here', 'ensemble'); ?>
                    </span>
                    <span class="es-contact-form__file-name"></span>
                </div>
            </div>
            <p class="es-contact-form__file-info">
                <?php 
                printf(
                    __('Accepted formats: %1$s. Maximum size: %2$d MB.', 'ensemble'),
                    implode(', ', $readable_types),
                    $max_size_mb
                ); 
                ?>
            </p>
        </div>
        
        <div class="es-contact-form__field es-contact-form__field--privacy">
            <label class="es-contact-form__checkbox">
                <input type="checkbox" name="privacy_accepted" required>
                <span><?php 
                    printf(
                        __('I agree to the %sprivacy policy%s and consent to the processing of my data.', 'ensemble'),
                        '<a href="' . esc_url(get_privacy_policy_url()) . '" target="_blank">',
                        '</a>'
                    );
                ?></span>
            </label>
        </div>
        
        <div class="es-contact-form__submit">
            <button type="submit" class="es-btn es-btn--primary">
                <span class="es-btn__text"><?php _e('Submit', 'ensemble'); ?></span>
                <span class="es-btn__loading">
                    <svg class="es-spinner" viewBox="0 0 24 24" width="20" height="20">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" stroke-linecap="round" stroke-dasharray="30 70"/>
                    </svg>
                    <?php _e('Submitting...', 'ensemble'); ?>
                </span>
            </button>
        </div>
        
        <div class="es-contact-form__messages">
            <div class="es-contact-form__success" style="display: none;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="24" height="24">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                </svg>
                <span></span>
            </div>
            <div class="es-contact-form__error" style="display: none;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="24" height="24">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                </svg>
                <span></span>
            </div>
        </div>
    </form>
</div>
