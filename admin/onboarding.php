<?php
/**
 * Ensemble Onboarding Template
 * 
 * Setup wizard for new installations to customize labels and settings
 *
 * @package Ensemble
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Check if already completed
$onboarding_completed = get_option('ensemble_onboarding_completed', false);
$can_skip = true; // Kann übersprungen werden
?>

<div class="wrap es-onboarding-wrap">
    <h1>
        <span class="dashicons dashicons-welcome-learn-more"></span>
        <?php _e('Welcome to Ensemble', 'ensemble'); ?>
    </h1>
    
    <div class="es-onboarding-container">
        
        <!-- Progress Timeline -->
        <div class="es-wizard-timeline">
            <div class="es-timeline-step active" data-step="1">
                <div class="es-timeline-step-number">1</div>
                <div class="es-timeline-step-label"><?php _e('Usage', 'ensemble'); ?></div>
            </div>
            <div class="es-timeline-connector"></div>
            <div class="es-timeline-step" data-step="2">
                <div class="es-timeline-step-number">2</div>
                <div class="es-timeline-step-label"><?php _e('Contributors', 'ensemble'); ?></div>
            </div>
            <div class="es-timeline-connector"></div>
            <div class="es-timeline-step" data-step="3">
                <div class="es-timeline-step-number">3</div>
                <div class="es-timeline-step-label"><?php _e('Experience', 'ensemble'); ?></div>
            </div>
            <div class="es-timeline-connector"></div>
            <div class="es-timeline-step" data-step="4">
                <div class="es-timeline-step-number">4</div>
                <div class="es-timeline-step-label"><?php _e('Fields', 'ensemble'); ?></div>
            </div>
        </div>
        
        <form id="es-onboarding-form" class="es-onboarding-form">
            
            <!-- Step 1: Usage Type -->
            <div class="es-onboarding-step active" data-step="1">
                <div class="es-step-content">
                    <div class="es-step-header">
                        <h2><?php _e('What is your main use case for Ensemble?', 'ensemble'); ?></h2>
                        <p class="es-step-description">
                            <?php _e('This selection helps us optimize the labels and templates for your needs.', 'ensemble'); ?>
                        </p>
                    </div>
                    
                    <div class="es-pills-grid">
                        <label class="es-pill-option">
                            <input type="radio" name="usage_type" value="clubs">
                            <span class="es-pill-content">
                                <span class="es-pill-icon"><?php ES_Icons::icon('dj'); ?></span>
                                <span class="es-pill-title"><?php _e('Clubs & Concerts', 'ensemble'); ?></span>
                                <span class="es-pill-subtitle"><?php _e('DJs, Bands, Live Acts', 'ensemble'); ?></span>
                            </span>
                        </label>
                        
                        <label class="es-pill-option">
                            <input type="radio" name="usage_type" value="theater">
                            <span class="es-pill-content">
                                <span class="es-pill-icon"><?php ES_Icons::icon('band'); ?></span>
                                <span class="es-pill-title"><?php _e('Theater & Culture', 'ensemble'); ?></span>
                                <span class="es-pill-subtitle"><?php _e('Ensembles, Performers', 'ensemble'); ?></span>
                            </span>
                        </label>
                        
                        <label class="es-pill-option">
                            <input type="radio" name="usage_type" value="church">
                            <span class="es-pill-content">
                                <span class="es-pill-icon"><?php ES_Icons::icon('priest'); ?></span>
                                <span class="es-pill-title"><?php _e('Church & Community', 'ensemble'); ?></span>
                                <span class="es-pill-subtitle"><?php _e('Pastors, Preachers', 'ensemble'); ?></span>
                            </span>
                        </label>
                        
                        <label class="es-pill-option">
                            <input type="radio" name="usage_type" value="fitness">
                            <span class="es-pill-content">
                                <span class="es-pill-icon"><?php ES_Icons::icon('trainer'); ?></span>
                                <span class="es-pill-title"><?php _e('Yoga & Fitness', 'ensemble'); ?></span>
                                <span class="es-pill-subtitle"><?php _e('Trainers, Instructors', 'ensemble'); ?></span>
                            </span>
                        </label>
                        
                        <label class="es-pill-option">
                            <input type="radio" name="usage_type" value="education">
                            <span class="es-pill-content">
                                <span class="es-pill-icon"><?php ES_Icons::icon('speaker'); ?></span>
                                <span class="es-pill-title"><?php _e('Workshops & Education', 'ensemble'); ?></span>
                                <span class="es-pill-subtitle"><?php _e('Instructors, Coaches', 'ensemble'); ?></span>
                            </span>
                        </label>
                        
                        <label class="es-pill-option">
                            <input type="radio" name="usage_type" value="kongress">
                            <span class="es-pill-content">
                                <span class="es-pill-icon"><span class="dashicons dashicons-businessman"></span></span>
                                <span class="es-pill-title"><?php _e('Conferences & Congresses', 'ensemble'); ?></span>
                                <span class="es-pill-subtitle"><?php _e('Speakers, Sessions, Rooms', 'ensemble'); ?></span>
                            </span>
                        </label>
                        
                        <label class="es-pill-option">
                            <input type="radio" name="usage_type" value="museum">
                            <span class="es-pill-content">
                                <span class="es-pill-icon"><span class="dashicons dashicons-art"></span></span>
                                <span class="es-pill-title"><?php _e('Museums & Galleries', 'ensemble'); ?></span>
                                <span class="es-pill-subtitle"><?php _e('Artists, Exhibitions', 'ensemble'); ?></span>
                            </span>
                        </label>
                        
                        <label class="es-pill-option">
                            <input type="radio" name="usage_type" value="sports">
                            <span class="es-pill-content">
                                <span class="es-pill-icon"><span class="dashicons dashicons-awards"></span></span>
                                <span class="es-pill-title"><?php _e('Sports & Competitions', 'ensemble'); ?></span>
                                <span class="es-pill-subtitle"><?php _e('Athletes, Teams, Matches', 'ensemble'); ?></span>
                            </span>
                        </label>
                        
                        <label class="es-pill-option">
                            <input type="radio" name="usage_type" value="public">
                            <span class="es-pill-content">
                                <span class="es-pill-icon"><?php ES_Icons::icon('host'); ?></span>
                                <span class="es-pill-title"><?php _e('Public Facilities', 'ensemble'); ?></span>
                                <span class="es-pill-subtitle"><?php _e('Guides, Moderators', 'ensemble'); ?></span>
                            </span>
                        </label>
                        
                        <label class="es-pill-option">
                            <input type="radio" name="usage_type" value="mixed">
                            <span class="es-pill-content">
                                <span class="es-pill-icon"><?php ES_Icons::icon('calendar_add'); ?></span>
                                <span class="es-pill-title"><?php _e('Other / Mixed Use', 'ensemble'); ?></span>
                                <span class="es-pill-subtitle"><?php _e('Various event types', 'ensemble'); ?></span>
                            </span>
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- Step 2: Contributor Label (populated dynamically) -->
            <div class="es-onboarding-step" data-step="2" style="display: none;">
                <div class="es-step-content">
                    <div class="es-step-header">
                        <h2><?php _e('What would you like to call your contributors?', 'ensemble'); ?></h2>
                        <p class="es-step-description">
                            <?php _e('We suggest suitable terms, but you can also choose a custom label.', 'ensemble'); ?>
                        </p>
                    </div>
                    
                    <div id="es-label-suggestions" class="es-pills-grid">
                        <!-- Populated via JS based on Step 1 -->
                    </div>
                    
                    <div class="es-custom-label-section">
                        <label class="es-toggle-label">
                            <input type="checkbox" id="es-use-custom-label">
                            <span><?php _e('Use fully custom labels', 'ensemble'); ?></span>
                        </label>
                        
                        <div id="es-custom-label-fields" style="display: none; margin-top: 15px;">
                            <!-- Artist/Contributor Labels -->
                            <div class="es-custom-label-group" style="margin-bottom: 20px;">
                                <h4 style="margin: 0 0 10px 0; font-size: 13px; color: var(--es-text-secondary);">
                                    <span class="dashicons dashicons-admin-users" style="font-size: 16px; width: 16px; height: 16px;"></span>
                                    <?php _e('Contributors (Artists/Speakers/etc.)', 'ensemble'); ?>
                                </h4>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                    <div class="es-form-row" style="margin: 0;">
                                        <label for="es-custom-label-singular"><?php _e('Singular', 'ensemble'); ?></label>
                                        <input type="text" id="es-custom-label-singular" name="artist_label_singular" placeholder="<?php _e('e.g. Speaker', 'ensemble'); ?>">
                                    </div>
                                    <div class="es-form-row" style="margin: 0;">
                                        <label for="es-custom-label-plural"><?php _e('Plural', 'ensemble'); ?></label>
                                        <input type="text" id="es-custom-label-plural" name="artist_label_plural" placeholder="<?php _e('e.g. Speakers', 'ensemble'); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Location Labels -->
                            <div class="es-custom-label-group" style="margin-bottom: 20px;">
                                <h4 style="margin: 0 0 10px 0; font-size: 13px; color: var(--es-text-secondary);">
                                    <span class="dashicons dashicons-location" style="font-size: 16px; width: 16px; height: 16px;"></span>
                                    <?php _e('Locations (Venues/Rooms/etc.)', 'ensemble'); ?>
                                </h4>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                    <div class="es-form-row" style="margin: 0;">
                                        <label for="es-custom-location-singular"><?php _e('Singular', 'ensemble'); ?></label>
                                        <input type="text" id="es-custom-location-singular" name="location_label_singular" placeholder="<?php _e('e.g. Room', 'ensemble'); ?>">
                                    </div>
                                    <div class="es-form-row" style="margin: 0;">
                                        <label for="es-custom-location-plural"><?php _e('Plural', 'ensemble'); ?></label>
                                        <input type="text" id="es-custom-location-plural" name="location_label_plural" placeholder="<?php _e('e.g. Rooms', 'ensemble'); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Event Labels -->
                            <div class="es-custom-label-group">
                                <h4 style="margin: 0 0 10px 0; font-size: 13px; color: var(--es-text-secondary);">
                                    <span class="dashicons dashicons-calendar-alt" style="font-size: 16px; width: 16px; height: 16px;"></span>
                                    <?php _e('Events (Sessions/Shows/etc.)', 'ensemble'); ?>
                                </h4>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                    <div class="es-form-row" style="margin: 0;">
                                        <label for="es-custom-event-singular"><?php _e('Singular', 'ensemble'); ?></label>
                                        <input type="text" id="es-custom-event-singular" name="event_label_singular" placeholder="<?php _e('e.g. Session', 'ensemble'); ?>">
                                    </div>
                                    <div class="es-form-row" style="margin: 0;">
                                        <label for="es-custom-event-plural"><?php _e('Plural', 'ensemble'); ?></label>
                                        <input type="text" id="es-custom-event-plural" name="event_label_plural" placeholder="<?php _e('e.g. Sessions', 'ensemble'); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Step 3: Experience Level -->
            <div class="es-onboarding-step" data-step="3" style="display: none;">
                <div class="es-step-content">
                    <div class="es-step-header">
                        <h2><?php _e('How familiar are you with WordPress & event plugins?', 'ensemble'); ?></h2>
                        <p class="es-step-description">
                            <?php _e('This helps us optimize the interface and help texts for your needs.', 'ensemble'); ?>
                        </p>
                    </div>
                    
                    <div class="es-pills-grid es-pills-vertical">
                        <label class="es-pill-option">
                            <input type="radio" name="experience_level" value="beginner">
                            <span class="es-pill-content">
                                <span class="es-pill-icon"><?php ES_Icons::icon('plus'); ?></span>
                                <span class="es-pill-title"><?php _e('New to WordPress & Events', 'ensemble'); ?></span>
                                <span class="es-pill-subtitle"><?php _e('I need help getting started', 'ensemble'); ?></span>
                            </span>
                        </label>
                        
                        <label class="es-pill-option">
                            <input type="radio" name="experience_level" value="intermediate">
                            <span class="es-pill-content">
                                <span class="es-pill-icon"><?php ES_Icons::icon('info'); ?></span>
                                <span class="es-pill-title"><?php _e('Some experience', 'ensemble'); ?></span>
                                <span class="es-pill-subtitle"><?php _e('I know my way around WordPress', 'ensemble'); ?></span>
                            </span>
                        </label>
                        
                        <label class="es-pill-option">
                            <input type="radio" name="experience_level" value="advanced">
                            <span class="es-pill-content">
                                <span class="es-pill-icon"><?php ES_Icons::icon('settings'); ?></span>
                                <span class="es-pill-title"><?php _e('Power User', 'ensemble'); ?></span>
                                <span class="es-pill-subtitle"><?php _e('I am a WordPress expert', 'ensemble'); ?></span>
                            </span>
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- Step 4: Custom Fields / Field Mapper -->
            <div class="es-onboarding-step" data-step="4" style="display: none;">
                <div class="es-step-content">
                    <div class="es-step-header">
                        <h2><?php _e('Do you use custom fields (ACF or meta fields)?', 'ensemble'); ?></h2>
                        <p class="es-step-description">
                            <?php _e('The Field Mapper allows you to connect your custom fields with Ensemble.', 'ensemble'); ?>
                        </p>
                    </div>
                    
                    <div class="es-pills-grid es-pills-vertical">
                        <label class="es-pill-option">
                            <input type="radio" name="has_custom_fields" value="yes">
                            <span class="es-pill-content">
                                <span class="es-pill-icon"><?php ES_Icons::icon('check'); ?></span>
                                <span class="es-pill-title"><?php _e('Yes, I want to set up the Field Mapper', 'ensemble'); ?></span>
                                <span class="es-pill-subtitle"><?php _e('You will be redirected to the Field Mapper', 'ensemble'); ?></span>
                            </span>
                        </label>
                        
                        <label class="es-pill-option">
                            <input type="radio" name="has_custom_fields" value="later">
                            <span class="es-pill-content">
                                <span class="es-pill-icon"><?php ES_Icons::icon('minus'); ?></span>
                                <span class="es-pill-title"><?php _e('No, not for now', 'ensemble'); ?></span>
                                <span class="es-pill-subtitle"><?php _e('Can be set up later in the Field Mapper', 'ensemble'); ?></span>
                            </span>
                        </label>
                    </div>
                    
                    <div class="es-notice es-notice-info" style="margin-top: 30px;">
                        <p>
                            <strong><?php _e('Tip:', 'ensemble'); ?></strong>
                            <?php _e('You can change all these settings anytime in the Ensemble settings.', 'ensemble'); ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Navigation Buttons -->
            <div class="es-onboarding-nav">
                <button type="button" class="button button-large es-onboarding-prev" style="visibility: hidden;">
                    <span class="dashicons dashicons-arrow-left-alt2"></span>
                    <?php _e('Back', 'ensemble'); ?>
                </button>
                
                <div class="es-nav-center">
                    <?php if ($can_skip): ?>
                    <button type="button" class="button button-link es-onboarding-skip">
                        <?php _e('Skip and set up later', 'ensemble'); ?>
                    </button>
                    <?php endif; ?>
                </div>
                
                <button type="button" class="button button-primary button-large es-onboarding-next">
                    <?php _e('Continue', 'ensemble'); ?>
                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                </button>
                
                <button type="submit" class="button button-primary button-large es-onboarding-finish" style="display: none;">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <?php _e('Complete Setup', 'ensemble'); ?>
                </button>
            </div>
            
        </form>
        
    </div>
</div>
