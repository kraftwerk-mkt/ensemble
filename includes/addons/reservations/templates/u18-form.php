<?php
/**
 * U18 Authorization Form Template
 * 
 * Button + Modal for parental authorization (Muttizettel)
 * Uses design variables from active layout
 * 
 * @package Ensemble
 * @subpackage Addons/Reservations Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

$form_id = 'es-u18-form-' . $event_id;
$unique_id = uniqid('es-u18-');
$event = get_post($event_id);
$event_title = $event ? $event->post_title : '';
$event_date = get_post_meta($event_id, '_event_start_date', true);
if (empty($event_date)) {
    $event_date = get_post_meta($event_id, 'es_event_start_date', true);
}
$event_date_formatted = $event_date ? date_i18n('d.m.Y', strtotime($event_date)) : '';
?>

<!-- U18 Button (wie Reservierungen/Tickets) -->
<div class="es-u18-wrapper" id="<?php echo esc_attr($unique_id); ?>">
    <button type="button" class="es-u18-toggle" id="<?php echo $unique_id; ?>-toggle" data-modal="<?php echo $unique_id; ?>-modal">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
            <circle cx="9" cy="7" r="4"/>
            <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
        </svg>
        <span><?php _e('U18 Aufsichtsübertragung', 'ensemble'); ?></span>
    </button>
</div>

<!-- U18 Modal -->
<div class="es-u18-modal" id="<?php echo $unique_id; ?>-modal" aria-hidden="true">
    <div class="es-u18-modal-overlay"></div>
    <div class="es-u18-modal-container">
        
        <!-- Modal Header -->
        <div class="es-u18-modal-header">
            <div class="es-u18-modal-title">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
                <span><?php _e('Aufsichtsübertragung (U18)', 'ensemble'); ?></span>
            </div>
            <button type="button" class="es-u18-modal-close" aria-label="<?php esc_attr_e('Schließen', 'ensemble'); ?>">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        
        <!-- Modal Body -->
        <div class="es-u18-modal-body">
            
            <!-- Event Info -->
            <div class="es-u18-event-info">
                <strong><?php echo esc_html($event_title); ?></strong>
                <?php if ($event_date_formatted): ?>
                    <span class="es-u18-event-date"><?php echo esc_html($event_date_formatted); ?></span>
                <?php endif; ?>
            </div>
            
            <!-- Info Box -->
            <div class="es-u18-info-box">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="16" x2="12" y2="12"/>
                    <line x1="12" y1="8" x2="12.01" y2="8"/>
                </svg>
                <p><?php _e('Dieses Formular dient zur Übertragung der Aufsichtspflicht nach § 1 Abs. 1 Nr. 4 JuSchG für Minderjährige (16-17 Jahre).', 'ensemble'); ?></p>
            </div>
            
            <!-- Progress Steps -->
            <div class="es-u18-progress">
                <div class="es-u18-step active" data-step="1">
                    <span class="es-step-number">1</span>
                    <span class="es-step-label"><?php _e('Elternteil', 'ensemble'); ?></span>
                </div>
                <div class="es-u18-step" data-step="2">
                    <span class="es-step-number">2</span>
                    <span class="es-step-label"><?php _e('Minderjährig', 'ensemble'); ?></span>
                </div>
                <div class="es-u18-step" data-step="3">
                    <span class="es-step-number">3</span>
                    <span class="es-step-label"><?php _e('Begleitung', 'ensemble'); ?></span>
                </div>
                <div class="es-u18-step" data-step="4">
                    <span class="es-step-number">4</span>
                    <span class="es-step-label"><?php _e('Bestätigung', 'ensemble'); ?></span>
                </div>
            </div>
            
            <!-- Form -->
            <form id="<?php echo esc_attr($form_id); ?>" class="es-u18-form" enctype="multipart/form-data">
                <?php wp_nonce_field('ensemble_u18', 'u18_nonce'); ?>
                <input type="hidden" name="event_id" value="<?php echo esc_attr($event_id); ?>">
                
                <!-- Step 1: Erziehungsberechtigter -->
                <div class="es-u18-step-content active" data-step="1">
                    <h3 class="es-step-title"><?php _e('Erziehungsberechtigter (Elternteil)', 'ensemble'); ?></h3>
                    
                    <div class="es-form-row">
                        <div class="es-form-group es-form-half">
                            <label><?php _e('Nachname', 'ensemble'); ?> <span class="required">*</span></label>
                            <input type="text" name="parent_lastname" required>
                        </div>
                        <div class="es-form-group es-form-half">
                            <label><?php _e('Vorname', 'ensemble'); ?> <span class="required">*</span></label>
                            <input type="text" name="parent_firstname" required>
                        </div>
                    </div>
                    
                    <div class="es-form-group">
                        <label><?php _e('Straße & Hausnummer', 'ensemble'); ?> <span class="required">*</span></label>
                        <input type="text" name="parent_street" required>
                    </div>
                    
                    <div class="es-form-row">
                        <div class="es-form-group es-form-third">
                            <label><?php _e('PLZ', 'ensemble'); ?> <span class="required">*</span></label>
                            <input type="text" name="parent_zip" required pattern="[0-9]{5}">
                        </div>
                        <div class="es-form-group es-form-twothirds">
                            <label><?php _e('Wohnort', 'ensemble'); ?> <span class="required">*</span></label>
                            <input type="text" name="parent_city" required>
                        </div>
                    </div>
                    
                    <div class="es-form-row">
                        <div class="es-form-group es-form-half">
                            <label><?php _e('Telefon', 'ensemble'); ?> <span class="required">*</span></label>
                            <input type="tel" name="parent_phone" required placeholder="+49 123 456789">
                        </div>
                        <div class="es-form-group es-form-half">
                            <label><?php _e('E-Mail', 'ensemble'); ?> <span class="required">*</span></label>
                            <input type="email" name="parent_email" required>
                        </div>
                    </div>
                </div>
                
                <!-- Step 2: Minderjährige Person -->
                <div class="es-u18-step-content" data-step="2">
                    <h3 class="es-step-title"><?php _e('Minderjährige Person (16-17 Jahre)', 'ensemble'); ?></h3>
                    
                    <div class="es-form-row">
                        <div class="es-form-group es-form-half">
                            <label><?php _e('Nachname', 'ensemble'); ?> <span class="required">*</span></label>
                            <input type="text" name="minor_lastname" required>
                        </div>
                        <div class="es-form-group es-form-half">
                            <label><?php _e('Vorname', 'ensemble'); ?> <span class="required">*</span></label>
                            <input type="text" name="minor_firstname" required>
                        </div>
                    </div>
                    
                    <div class="es-form-group">
                        <label><?php _e('Geburtsdatum', 'ensemble'); ?> <span class="required">*</span></label>
                        <input type="date" name="minor_birthdate" required class="es-minor-birthdate">
                        <span class="es-age-display"></span>
                    </div>
                    
                    <div class="es-form-group">
                        <label><?php _e('Straße & Hausnummer', 'ensemble'); ?> <span class="required">*</span></label>
                        <input type="text" name="minor_street" required>
                    </div>
                    
                    <div class="es-form-row">
                        <div class="es-form-group es-form-third">
                            <label><?php _e('PLZ', 'ensemble'); ?> <span class="required">*</span></label>
                            <input type="text" name="minor_zip" required pattern="[0-9]{5}">
                        </div>
                        <div class="es-form-group es-form-twothirds">
                            <label><?php _e('Wohnort', 'ensemble'); ?> <span class="required">*</span></label>
                            <input type="text" name="minor_city" required>
                        </div>
                    </div>
                    
                    <button type="button" class="es-btn es-btn-secondary es-btn-sm es-copy-address">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                        </svg>
                        <?php _e('Adresse von Elternteil übernehmen', 'ensemble'); ?>
                    </button>
                </div>
                
                <!-- Step 3: Begleitperson -->
                <div class="es-u18-step-content" data-step="3">
                    <h3 class="es-step-title"><?php _e('Begleitperson (Volljährig, 18+)', 'ensemble'); ?></h3>
                    
                    <div class="es-form-row">
                        <div class="es-form-group es-form-half">
                            <label><?php _e('Nachname', 'ensemble'); ?> <span class="required">*</span></label>
                            <input type="text" name="companion_lastname" required>
                        </div>
                        <div class="es-form-group es-form-half">
                            <label><?php _e('Vorname', 'ensemble'); ?> <span class="required">*</span></label>
                            <input type="text" name="companion_firstname" required>
                        </div>
                    </div>
                    
                    <div class="es-form-group">
                        <label><?php _e('Geburtsdatum', 'ensemble'); ?> <span class="required">*</span></label>
                        <input type="date" name="companion_birthdate" required class="es-companion-birthdate">
                        <span class="es-age-display"></span>
                    </div>
                    
                    <div class="es-form-group">
                        <label><?php _e('Straße & Hausnummer', 'ensemble'); ?> <span class="required">*</span></label>
                        <input type="text" name="companion_street" required>
                    </div>
                    
                    <div class="es-form-row">
                        <div class="es-form-group es-form-third">
                            <label><?php _e('PLZ', 'ensemble'); ?> <span class="required">*</span></label>
                            <input type="text" name="companion_zip" required pattern="[0-9]{5}">
                        </div>
                        <div class="es-form-group es-form-twothirds">
                            <label><?php _e('Wohnort', 'ensemble'); ?> <span class="required">*</span></label>
                            <input type="text" name="companion_city" required>
                        </div>
                    </div>
                    
                    <div class="es-form-row">
                        <div class="es-form-group es-form-half">
                            <label><?php _e('Telefon', 'ensemble'); ?> <span class="required">*</span></label>
                            <input type="tel" name="companion_phone" required>
                        </div>
                        <div class="es-form-group es-form-half">
                            <label><?php _e('E-Mail', 'ensemble'); ?></label>
                            <input type="email" name="companion_email">
                        </div>
                    </div>
                </div>
                
                <!-- Step 4: Bestätigung -->
                <div class="es-u18-step-content" data-step="4">
                    <h3 class="es-step-title"><?php _e('Zusammenfassung & Bestätigung', 'ensemble'); ?></h3>
                    
                    <!-- Summary -->
                    <div class="es-u18-summary">
                        <div class="es-summary-section">
                            <h4><?php _e('Erziehungsberechtigter', 'ensemble'); ?></h4>
                            <p class="es-summary-parent"></p>
                        </div>
                        <div class="es-summary-section">
                            <h4><?php _e('Minderjährige Person', 'ensemble'); ?></h4>
                            <p class="es-summary-minor"></p>
                        </div>
                        <div class="es-summary-section">
                            <h4><?php _e('Begleitperson', 'ensemble'); ?></h4>
                            <p class="es-summary-companion"></p>
                        </div>
                    </div>
                    
                    <!-- Legal Text -->
                    <div class="es-u18-legal-text">
                        <p><?php _e('Gemäß § 1 Abs. 1 Nr. 4 des Jugendschutzgesetzes übertrage ich die Aufsichtspflicht für meine minderjährige Tochter / meinen minderjährigen Sohn auf die genannte volljährige Begleitperson.', 'ensemble'); ?></p>
                    </div>
                    
                    <!-- Digital Signatures -->
                    <div class="es-u18-signatures">
                        <div class="es-signature-box">
                            <label><?php _e('Unterschrift Erziehungsberechtigter', 'ensemble'); ?> <span class="required">*</span></label>
                            <div class="es-signature-pad-wrapper">
                                <canvas class="es-signature-pad" id="<?php echo $unique_id; ?>-parent-sig" data-target="parent_signature"></canvas>
                                <button type="button" class="es-signature-clear" title="<?php esc_attr_e('Löschen', 'ensemble'); ?>">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
                                    </svg>
                                </button>
                            </div>
                            <input type="hidden" name="parent_signature" class="es-signature-data">
                            <span class="es-field-hint"><?php _e('Mit Maus oder Finger unterschreiben', 'ensemble'); ?></span>
                        </div>
                        
                        <div class="es-signature-box">
                            <label><?php _e('Unterschrift Begleitperson', 'ensemble'); ?> <span class="required">*</span></label>
                            <div class="es-signature-pad-wrapper">
                                <canvas class="es-signature-pad" id="<?php echo $unique_id; ?>-companion-sig" data-target="companion_signature"></canvas>
                                <button type="button" class="es-signature-clear" title="<?php esc_attr_e('Löschen', 'ensemble'); ?>">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
                                    </svg>
                                </button>
                            </div>
                            <input type="hidden" name="companion_signature" class="es-signature-data">
                            <span class="es-field-hint"><?php _e('Mit Maus oder Finger unterschreiben', 'ensemble'); ?></span>
                        </div>
                    </div>
                    
                    <!-- Consents -->
                    <div class="es-u18-consents">
                        <label class="es-checkbox-label">
                            <input type="checkbox" name="consent_legal" required>
                            <span><?php _e('Ich bestätige die Richtigkeit der Angaben und erteile die Aufsichtsübertragung.', 'ensemble'); ?> <span class="required">*</span></span>
                        </label>
                        
                        <label class="es-checkbox-label">
                            <input type="checkbox" name="consent_privacy" required>
                            <span><?php printf(__('Ich habe die %sDatenschutzerklärung%s gelesen und stimme zu.', 'ensemble'), '<a href="' . esc_url(get_privacy_policy_url()) . '" target="_blank">', '</a>'); ?> <span class="required">*</span></span>
                        </label>
                        
                        <label class="es-checkbox-label">
                            <input type="checkbox" name="consent_id_check" required>
                            <span><?php _e('Alle Personen weisen sich am Einlass mit Personalausweis aus.', 'ensemble'); ?> <span class="required">*</span></span>
                        </label>
                    </div>
                    
                    <!-- Optional File Upload -->
                    <div class="es-form-group">
                        <label><?php _e('Ausweis-Kopie (optional)', 'ensemble'); ?></label>
                        <div class="es-file-upload">
                            <input type="file" name="id_upload" accept="image/*,.pdf">
                            <span class="es-file-upload-text"><?php _e('Datei auswählen', 'ensemble'); ?></span>
                            <span class="es-file-name"></span>
                        </div>
                        <span class="es-field-hint"><?php _e('JPG, PNG oder PDF (max. 10 MB)', 'ensemble'); ?></span>
                    </div>
                </div>
                
                <!-- Messages -->
                <div class="es-u18-message" style="display: none;"></div>
            </form>
            
            <!-- Success State -->
            <div class="es-u18-success" style="display: none;">
                <div class="es-success-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M9 12l2 2 4-4"/>
                    </svg>
                </div>
                <h3><?php _e('Antrag erfolgreich eingereicht!', 'ensemble'); ?></h3>
                <p class="es-success-message"></p>
                <div class="es-success-code">
                    <span class="es-code-label"><?php _e('Bestätigungscode:', 'ensemble'); ?></span>
                    <span class="es-code-value"></span>
                </div>
                <div class="es-success-info">
                    <p><?php _e('Sie erhalten eine E-Mail mit dem Formular zum Ausdrucken.', 'ensemble'); ?></p>
                </div>
            </div>
            
        </div>
        
        <!-- Modal Footer -->
        <div class="es-u18-modal-footer">
            <button type="button" class="es-btn es-btn-secondary es-u18-prev" style="display: none;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"/>
                </svg>
                <?php _e('Zurück', 'ensemble'); ?>
            </button>
            
            <button type="button" class="es-btn es-btn-primary es-u18-next">
                <?php _e('Weiter', 'ensemble'); ?>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9 18 15 12 9 6"/>
                </svg>
            </button>
            
            <button type="button" class="es-btn es-btn-primary es-u18-submit" style="display: none;">
                <span class="es-btn-text">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                    <?php _e('Antrag einreichen', 'ensemble'); ?>
                </span>
                <span class="es-btn-loading" style="display: none;">
                    <svg class="es-spinner" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" stroke-dasharray="30 70"/></svg>
                    <?php _e('Wird gesendet...', 'ensemble'); ?>
                </span>
            </button>
            
            <button type="button" class="es-btn es-btn-secondary es-u18-close-success" style="display: none;">
                <?php _e('Schließen', 'ensemble'); ?>
            </button>
        </div>
        
    </div>
</div>
