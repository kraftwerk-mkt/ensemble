<?php
/**
 * Reservations Pro - Reservation Form Template
 * 
 * @package Ensemble
 * @subpackage Addons/Reservations Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

$form_id = 'es-reservation-form-' . $event_id;
$show_type_selector = count($types) > 1;
$has_table = in_array('table', $types);
$is_shortcode = isset($shortcode) && $shortcode;
$unique_id = uniqid('es-res-');
$show_header = !function_exists('ensemble_show_addon_header') || ensemble_show_addon_header('reservations');
?>
<div class="es-reservation-wrapper <?php echo $is_shortcode ? 'es-reservation-shortcode' : ''; ?>">
    <?php if (!$is_shortcode): ?>
    <div class="es-section es-reservation-section">
        <?php if ($show_header): ?>
        <div class="es-reservation-header">
            <h2 class="es-section-title">
                <span class="dashicons dashicons-clipboard"></span>
                <?php _e('Reservierung', 'ensemble'); ?>
            </h2>
            
            <?php if (isset($remaining) && $remaining !== null): ?>
            <span class="es-availability-badge <?php echo $remaining < 10 ? 'es-availability-low' : ''; ?>">
                <?php printf(__('%d seats remaining', 'ensemble'), $remaining); ?>
            </span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Toggle Button -->
        <button type="button" class="es-btn es-btn-primary es-reservation-toggle es-action-btn" id="<?php echo $unique_id; ?>-toggle">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                <rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>
                <path d="M9 14l2 2 4-4"/>
            </svg>
            <?php _e('Jetzt reservieren', 'ensemble'); ?>
        </button>
    <?php endif; ?>
    
    <!-- Collapsible Form Container -->
    <div class="es-reservation-form-container" id="<?php echo $unique_id; ?>-container" style="display: none;">
        
        <form id="<?php echo esc_attr($form_id); ?>" class="es-reservation-form" data-event-id="<?php echo esc_attr($event_id); ?>">
            <?php wp_nonce_field('ensemble_reservations', 'reservation_nonce'); ?>
            <input type="hidden" name="event_id" value="<?php echo esc_attr($event_id); ?>">
            
            <?php if ($show_type_selector): ?>
            <div class="es-form-group es-reservation-types">
                <label><?php _e('Art der Reservierung', 'ensemble'); ?></label>
                <div class="es-type-buttons">
                    <?php foreach ($types as $index => $type): ?>
                    <label class="es-type-button <?php echo $index === 0 ? 'active' : ''; ?>">
                        <input type="radio" name="type" value="<?php echo esc_attr($type); ?>" <?php checked($index, 0); ?>>
                        <span class="es-type-icon">
                            <?php if ($type === 'guestlist'): ?>
                                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            <?php elseif ($type === 'table'): ?>
                                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="8" rx="1"/><path d="M5 12v8M19 12v8M8 12v4M16 12v4"/></svg>
                            <?php else: ?>
                                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                            <?php endif; ?>
                        </span>
                        <span class="es-type-label">
                            <?php 
                            echo $type === 'guestlist' ? __('Guest list', 'ensemble') : 
                                 ($type === 'table' ? __('Tisch', 'ensemble') : __('VIP', 'ensemble')); 
                            ?>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php else: ?>
            <input type="hidden" name="type" value="<?php echo esc_attr($types[0]); ?>">
            <?php endif; ?>
            
            <div class="es-form-row">
                <div class="es-form-group es-form-half">
                    <label for="<?php echo $form_id; ?>-name"><?php _e('Name', 'ensemble'); ?> <span class="required">*</span></label>
                    <input type="text" id="<?php echo $form_id; ?>-name" name="name" required 
                           placeholder="<?php esc_attr_e('Your full name', 'ensemble'); ?>">
                </div>
                
                <div class="es-form-group es-form-half">
                    <label for="<?php echo $form_id; ?>-email"><?php _e('E-Mail', 'ensemble'); ?> <span class="required">*</span></label>
                    <input type="email" id="<?php echo $form_id; ?>-email" name="email" required
                           placeholder="<?php esc_attr_e('deine@email.de', 'ensemble'); ?>">
                </div>
            </div>
            
            <div class="es-form-row">
                <div class="es-form-group es-form-half">
                    <label for="<?php echo $form_id; ?>-phone"><?php _e('Telefon', 'ensemble'); ?></label>
                    <input type="tel" id="<?php echo $form_id; ?>-phone" name="phone"
                           placeholder="<?php esc_attr_e('+49 123 456789', 'ensemble'); ?>">
                </div>
                
                <div class="es-form-group es-form-half">
                    <label for="<?php echo $form_id; ?>-guests"><?php _e('Anzahl Personen', 'ensemble'); ?> <span class="required">*</span></label>
                    <div class="es-guests-selector">
                        <button type="button" class="es-guests-btn es-guests-minus" aria-label="<?php esc_attr_e('Weniger', 'ensemble'); ?>">−</button>
                        <input type="number" id="<?php echo $form_id; ?>-guests" name="guests" value="1" min="1" max="20" required>
                        <button type="button" class="es-guests-btn es-guests-plus" aria-label="<?php esc_attr_e('Mehr', 'ensemble'); ?>">+</button>
                    </div>
                </div>
            </div>
            
            <?php if ($has_table): ?>
            <div class="es-form-group es-table-field" style="display: none;">
                <label for="<?php echo $form_id; ?>-table"><?php _e('Preferred Table', 'ensemble'); ?></label>
                <select id="<?php echo $form_id; ?>-table" name="table_number">
                    <option value=""><?php _e('Any / Based on Availability', 'ensemble'); ?></option>
                    <?php for ($i = 1; $i <= 20; $i++): ?>
                    <option value="<?php echo $i; ?>"><?php printf(__('Tisch %d', 'ensemble'), $i); ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <?php endif; ?>
            
            <div class="es-form-group">
                <label for="<?php echo $form_id; ?>-notes"><?php _e('Anmerkungen', 'ensemble'); ?></label>
                <textarea id="<?php echo $form_id; ?>-notes" name="notes" rows="3"
                          placeholder="<?php esc_attr_e('Special requests, allergies, etc.', 'ensemble'); ?>"></textarea>
            </div>
            
            <div class="es-form-group es-privacy-notice">
                <label>
                    <input type="checkbox" name="privacy" required>
                    <?php printf(
                        __('I accept the %sPrivacy Policy%s', 'ensemble'),
                        '<a href="' . esc_url(get_privacy_policy_url()) . '" target="_blank">',
                        '</a>'
                    ); ?>
                    <span class="required">*</span>
                </label>
            </div>
            
            <div class="es-form-actions">
                <button type="submit" class="es-btn es-btn-primary es-btn-reserve">
                    <span class="es-btn-text"><?php echo isset($button_text) ? esc_html($button_text) : __('Submit reservation', 'ensemble'); ?></span>
                    <span class="es-btn-loading" style="display: none;">
                        <svg class="es-spinner" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" stroke-dasharray="30 70"/></svg>
                        <?php _e('Wird gesendet...', 'ensemble'); ?>
                    </span>
                </button>
                <button type="button" class="es-btn es-btn-secondary es-reservation-cancel">
                    <?php _e('Cancel', 'ensemble'); ?>
                </button>
            </div>
            
            <div class="es-reservation-message" style="display: none;"></div>
        </form>
        
    </div>
    
    <!-- Success State (shown after successful submission) -->
    <div class="es-reservation-success" id="<?php echo $unique_id; ?>-success" style="display: none;">
        <div class="es-success-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <path d="M9 12l2 2 4-4"/>
            </svg>
        </div>
        <h3><?php _e('Reservierung erfolgreich!', 'ensemble'); ?></h3>
        <p class="es-success-message"></p>
        <p class="es-confirmation-code"></p>
        <button type="button" class="es-btn es-btn-secondary es-reservation-another">
            <?php _e('Weitere Reservierung', 'ensemble'); ?>
        </button>
    </div>
    
    <?php if (!$is_shortcode): ?>
    </div>
    <?php endif; ?>
</div>

<script>
jQuery(function($) {
    var $wrapper = $('#<?php echo $unique_id; ?>-toggle').closest('.es-reservation-wrapper');
    var $toggle = $('#<?php echo $unique_id; ?>-toggle');
    var $container = $('#<?php echo $unique_id; ?>-container');
    var $success = $('#<?php echo $unique_id; ?>-success');
    var $form = $('#<?php echo esc_js($form_id); ?>');
    
    // Toggle form visibility
    $toggle.on('click', function() {
        $(this).slideUp(200);
        $container.slideDown(300);
    });
    
    // Cancel button
    $wrapper.find('.es-reservation-cancel').on('click', function() {
        $container.slideUp(200);
        $toggle.slideDown(300);
    });
    
    // Another reservation button
    $wrapper.find('.es-reservation-another').on('click', function() {
        $success.slideUp(200);
        $form[0].reset();
        $form.find('input[name="guests"]').val(1);
        $container.slideDown(300);
    });
    
    // Type selector
    $form.find('.es-type-button').on('click', function() {
        $form.find('.es-type-button').removeClass('active');
        $(this).addClass('active');
        $(this).find('input').prop('checked', true);
        
        // Show/hide table field
        var type = $(this).find('input').val();
        $form.find('.es-table-field').toggle(type === 'table');
    });
    
    // Guest counter
    $form.find('.es-guests-minus').on('click', function() {
        var $input = $form.find('input[name="guests"]');
        var val = parseInt($input.val()) || 1;
        if (val > 1) $input.val(val - 1);
    });
    
    $form.find('.es-guests-plus').on('click', function() {
        var $input = $form.find('input[name="guests"]');
        var val = parseInt($input.val()) || 1;
        var max = parseInt($input.attr('max')) || 20;
        if (val < max) $input.val(val + 1);
    });
    
    // Form submission
    $form.on('submit', function(e) {
        e.preventDefault();
        
        var $btn = $form.find('.es-btn-reserve');
        var $btnText = $btn.find('.es-btn-text');
        var $btnLoading = $btn.find('.es-btn-loading');
        var $message = $form.find('.es-reservation-message');
        
        // Validate
        if (!$form.find('input[name="privacy"]').is(':checked')) {
            showMessage('error', '<?php echo esc_js(__('Please accept the privacy policy.', 'ensemble')); ?>');
            return;
        }
        
        // Disable button
        $btn.prop('disabled', true);
        $btnText.hide();
        $btnLoading.show();
        $message.hide();
        
        // Submit
        $.ajax({
            url: ensembleReservations.ajaxUrl,
            type: 'POST',
            data: {
                action: 'es_submit_reservation',
                nonce: ensembleReservations.nonce,
                event_id: $form.find('input[name="event_id"]').val(),
                type: $form.find('input[name="type"]:checked').val() || $form.find('input[name="type"]').val(),
                name: $form.find('input[name="name"]').val(),
                email: $form.find('input[name="email"]').val(),
                phone: $form.find('input[name="phone"]').val(),
                guests: $form.find('input[name="guests"]').val(),
                table_number: $form.find('select[name="table_number"]').val(),
                notes: $form.find('textarea[name="notes"]').val()
            },
            success: function(response) {
                if (response.success) {
                    // Show success state
                    $container.slideUp(200);
                    $success.find('.es-success-message').text(response.data.message);
                    
                    if (response.data.confirmation_code) {
                        $success.find('.es-confirmation-code').html(
                            '<?php echo esc_js(__('Your confirmation code:', 'ensemble')); ?> <strong>' + response.data.confirmation_code + '</strong>'
                        );
                    }
                    
                    $success.slideDown(300);
                } else {
                    showMessage('error', response.data.message);
                }
            },
            error: function() {
                showMessage('error', ensembleReservations.strings.error);
            },
            complete: function() {
                $btn.prop('disabled', false);
                $btnText.show();
                $btnLoading.hide();
            }
        });
    });
    
    function showMessage(type, message) {
        var $message = $form.find('.es-reservation-message');
        $message.removeClass('es-message-success es-message-error')
                .addClass('es-message-' + type)
                .html(message)
                .fadeIn();
    }
});
</script>