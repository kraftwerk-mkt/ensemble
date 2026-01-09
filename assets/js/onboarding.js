/**
 * Ensemble Onboarding JavaScript
 * Handles step navigation, label suggestions, and form submission
 * 
 * @package Ensemble
 */

(function($) {
    'use strict';
    
    // Label Mappings based on usage type
    // Each type has suggestions for: artist, location, event
    const labelMappings = {
        clubs: {
            suggestions: [
                { singular: 'DJ', plural: 'DJs', icon: ES_ICONS.artist },
                { singular: 'Artist', plural: 'Artists', icon: ES_ICONS.artist },
                { singular: 'Band', plural: 'Bands', icon: ES_ICONS.band },
                { singular: 'Live Act', plural: 'Live Acts', icon: ES_ICONS.artist }
            ],
            default_singular: 'DJ',
            default_plural: 'DJs',
            location: { singular: 'Venue', plural: 'Venues' },
            event: { singular: 'Event', plural: 'Events' },
            event_types: ['Club Night', 'Concert', 'Festival', 'Party']
        },
        theater: {
            suggestions: [
                { singular: 'Performer', plural: 'Ensemble', icon: ES_ICONS.artist },
                { singular: 'Actor', plural: 'Actors', icon: ES_ICONS.artist },
                { singular: 'Speaker', plural: 'Speakers', icon: ES_ICONS.speaker },
                { singular: 'Artist', plural: 'Artists', icon: ES_ICONS.category }
            ],
            default_singular: 'Performer',
            default_plural: 'Ensemble',
            location: { singular: 'Stage', plural: 'Stages' },
            event: { singular: 'Show', plural: 'Shows' },
            event_types: ['Performance', 'Reading', 'Show', 'Premiere']
        },
        church: {
            suggestions: [
                { singular: 'Pastor', plural: 'Clergy', icon: ES_ICONS.priest },
                { singular: 'Preacher', plural: 'Preachers', icon: ES_ICONS.priest },
                { singular: 'Liturgist', plural: 'Liturgists', icon: ES_ICONS.priest },
                { singular: 'Minister', plural: 'Ministers', icon: ES_ICONS.priest }
            ],
            default_singular: 'Pastor',
            default_plural: 'Clergy',
            location: { singular: 'Church', plural: 'Churches' },
            event: { singular: 'Service', plural: 'Services' },
            event_types: ['Service', 'Worship', 'Baptism', 'Wedding', 'Funeral']
        },
        fitness: {
            suggestions: [
                { singular: 'Trainer', plural: 'Trainers', icon: ES_ICONS.trainer },
                { singular: 'Yoga Teacher', plural: 'Yoga Teachers', icon: ES_ICONS.trainer },
                { singular: 'Coach', plural: 'Coaches', icon: ES_ICONS.trainer },
                { singular: 'Instructor', plural: 'Instructors', icon: ES_ICONS.trainer }
            ],
            default_singular: 'Trainer',
            default_plural: 'Trainers',
            location: { singular: 'Studio', plural: 'Studios' },
            event: { singular: 'Class', plural: 'Classes' },
            event_types: ['Class', 'Workshop', 'Training', 'Session']
        },
        education: {
            suggestions: [
                { singular: 'Instructor', plural: 'Instructors', icon: ES_ICONS.speaker },
                { singular: 'Speaker', plural: 'Speakers', icon: ES_ICONS.dashboard },
                { singular: 'Coach', plural: 'Coaches', icon: ES_ICONS.dashboard },
                { singular: 'Expert', plural: 'Experts', icon: ES_ICONS.dashboard }
            ],
            default_singular: 'Instructor',
            default_plural: 'Instructors',
            location: { singular: 'Classroom', plural: 'Classrooms' },
            event: { singular: 'Workshop', plural: 'Workshops' },
            event_types: ['Workshop', 'Seminar', 'Training', 'Course']
        },
        kongress: {
            suggestions: [
                { singular: 'Speaker', plural: 'Speakers', icon: '<span class="dashicons dashicons-businessman"></span>' },
                { singular: 'Keynote Speaker', plural: 'Keynote Speakers', icon: '<span class="dashicons dashicons-star-filled"></span>' },
                { singular: 'Panelist', plural: 'Panelists', icon: '<span class="dashicons dashicons-groups"></span>' },
                { singular: 'Presenter', plural: 'Presenters', icon: '<span class="dashicons dashicons-media-interactive"></span>' }
            ],
            default_singular: 'Speaker',
            default_plural: 'Speakers',
            location: { singular: 'Room', plural: 'Rooms' },
            event: { singular: 'Session', plural: 'Sessions' },
            event_types: ['Session', 'Keynote', 'Panel', 'Workshop', 'Networking']
        },
        museum: {
            suggestions: [
                { singular: 'Artist', plural: 'Artists', icon: '<span class="dashicons dashicons-art"></span>' },
                { singular: 'Curator', plural: 'Curators', icon: '<span class="dashicons dashicons-welcome-view-site"></span>' },
                { singular: 'Guide', plural: 'Guides', icon: '<span class="dashicons dashicons-location-alt"></span>' },
                { singular: 'Creator', plural: 'Creators', icon: '<span class="dashicons dashicons-lightbulb"></span>' }
            ],
            default_singular: 'Artist',
            default_plural: 'Artists',
            location: { singular: 'Gallery', plural: 'Galleries' },
            event: { singular: 'Exhibition', plural: 'Exhibitions' },
            event_types: ['Exhibition', 'Vernissage', 'Tour', 'Workshop']
        },
        sports: {
            suggestions: [
                { singular: 'Athlete', plural: 'Athletes', icon: '<span class="dashicons dashicons-universal-access"></span>' },
                { singular: 'Player', plural: 'Players', icon: '<span class="dashicons dashicons-admin-users"></span>' },
                { singular: 'Team', plural: 'Teams', icon: '<span class="dashicons dashicons-groups"></span>' },
                { singular: 'Competitor', plural: 'Competitors', icon: '<span class="dashicons dashicons-awards"></span>' }
            ],
            default_singular: 'Athlete',
            default_plural: 'Athletes',
            location: { singular: 'Venue', plural: 'Venues' },
            event: { singular: 'Match', plural: 'Matches' },
            event_types: ['Match', 'Game', 'Tournament', 'Competition']
        },
        public: {
            suggestions: [
                { singular: 'Guide', plural: 'Guides', icon: '<span class="dashicons dashicons-location-alt"></span>' },
                { singular: 'Moderator', plural: 'Moderators', icon: '<span class="dashicons dashicons-microphone"></span>' },
                { singular: 'Speaker', plural: 'Speakers', icon: '<span class="dashicons dashicons-businessman"></span>' },
                { singular: 'Organizer', plural: 'Organizers', icon: '<span class="dashicons dashicons-building"></span>' }
            ],
            default_singular: 'Guide',
            default_plural: 'Guides',
            location: { singular: 'Location', plural: 'Locations' },
            event: { singular: 'Event', plural: 'Events' },
            event_types: ['Tour', 'Event', 'Lecture', 'Exhibition']
        },
        mixed: {
            suggestions: [
                { singular: 'Contributor', plural: 'Contributors', icon: ES_ICONS.event_grid },
                { singular: 'Performer', plural: 'Performers', icon: '<span class="dashicons dashicons-star-filled"></span>' },
                { singular: 'Participant', plural: 'Participants', icon: '<span class="dashicons dashicons-groups"></span>' },
                { singular: 'Person', plural: 'People', icon: '<span class="dashicons dashicons-admin-users"></span>' }
            ],
            default_singular: 'Contributor',
            default_plural: 'Contributors',
            location: { singular: 'Location', plural: 'Locations' },
            event: { singular: 'Event', plural: 'Events' },
            event_types: ['Event', 'Occasion', 'Appointment']
        }
    };
    
    let currentStep = 1;
    const totalSteps = 4;
    let selectedUsageType = null;
    let goingBackwards = false;
    
    /**
     * Initialize Onboarding
     */
    function init() {
        bindEvents();
        updateStepDisplay();
    }
    
    /**
     * Bind all event listeners
     */
    function bindEvents() {
        // Navigation buttons
        $('.es-onboarding-next').on('click', function(e) {
            e.preventDefault();
            handleNext();
        });
        
        $('.es-onboarding-prev').on('click', function(e) {
            e.preventDefault();
            handlePrevious();
        });
        
        $('.es-onboarding-skip').on('click', function(e) {
            e.preventDefault();
            handleSkip();
        });
        
        $('.es-onboarding-finish').on('click', function(e) {
            e.preventDefault();
            handleFinish();
        });
        
        // Usage type selection (Step 1)
        $('input[name="usage_type"]').on('change', function() {
            selectedUsageType = $(this).val();
            generateLabelSuggestions(selectedUsageType);
        });
        
        // Custom label toggle
        $('#es-use-custom-label').on('change', function() {
            $('#es-custom-label-fields').slideToggle(200);
            
            // Deselect radio buttons when custom is enabled
            if ($(this).is(':checked')) {
                $('input[name="label_suggestion"]').prop('checked', false);
            }
        });
        
        // Label suggestion selection (deselect custom when selecting suggestion)
        $(document).on('change', 'input[name="label_suggestion"]', function() {
            if ($(this).is(':checked')) {
                $('#es-use-custom-label').prop('checked', false);
                $('#es-custom-label-fields').slideUp(200);
            }
        });
        
        // Timeline step click navigation
        $('.es-timeline-step').on('click', function() {
            const targetStep = parseInt($(this).data('step'));
            if (targetStep < currentStep || $(this).hasClass('completed')) {
                goingBackwards = targetStep < currentStep;
                navigateToStep(targetStep);
            }
        });
        
        // Form submission - Prevent default
        $('#es-onboarding-form').on('submit', function(e) {
            e.preventDefault();
            return false;
        });
    }
    
    /**
     * Generate label suggestions based on usage type
     */
    function generateLabelSuggestions(usageType) {
        const mapping = labelMappings[usageType];
        if (!mapping) return;
        
        const $container = $('#es-label-suggestions');
        $container.empty();
        
        // Show what Location and Event will be called
        const $preview = $(`
            <div class="es-label-preview" style="margin-bottom: 20px; padding: 15px; background: var(--es-surface-secondary); border-radius: 8px; border-left: 3px solid var(--es-primary);">
                <p style="margin: 0 0 8px 0; font-size: 13px; color: var(--es-text-secondary);">
                    <strong>Based on your selection:</strong>
                </p>
                <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                    <span><span class="dashicons dashicons-location" style="font-size: 14px; width: 14px; height: 14px; margin-right: 4px;"></span><strong>Location:</strong> ${mapping.location.singular} / ${mapping.location.plural}</span>
                    <span><span class="dashicons dashicons-calendar-alt" style="font-size: 14px; width: 14px; height: 14px; margin-right: 4px;"></span><strong>Event:</strong> ${mapping.event.singular} / ${mapping.event.plural}</span>
                </div>
            </div>
        `);
        $container.append($preview);
        
        // Artist/Contributor suggestions
        const $artistHeader = $('<h4 style="margin: 0 0 15px 0; font-size: 14px; color: var(--es-text);">Choose a term for your contributors:</h4>');
        $container.append($artistHeader);
        
        const $pillsContainer = $('<div class="es-pills-grid-inner" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px;"></div>');
        
        mapping.suggestions.forEach((suggestion, index) => {
            const isDefault = index === 0;
            const $pill = $(`
                <label class="es-pill-option es-pill-option-small">
                    <input type="radio" name="label_suggestion" value="${index}" ${isDefault ? 'checked' : ''} 
                           data-singular="${suggestion.singular}" 
                           data-plural="${suggestion.plural}">
                    <span class="es-pill-content" style="padding: 15px; flex-direction: row; gap: 12px; text-align: left;">
                        <span class="es-pill-icon" style="font-size: 24px; margin: 0;">${suggestion.icon}</span>
                        <span style="display: flex; flex-direction: column;">
                            <span class="es-pill-title" style="font-size: 14px;">${suggestion.singular}</span>
                            <span class="es-pill-subtitle" style="font-size: 11px;">Plural: ${suggestion.plural}</span>
                        </span>
                    </span>
                </label>
            `);
            $pillsContainer.append($pill);
        });
        
        $container.append($pillsContainer);
        
        // Store location/event labels for form submission
        $container.data('location-singular', mapping.location.singular);
        $container.data('location-plural', mapping.location.plural);
        $container.data('event-singular', mapping.event.singular);
        $container.data('event-plural', mapping.event.plural);
    }
    
    /**
     * Handle Next button
     */
    function handleNext() {
        if (!validateCurrentStep()) {
            return;
        }
        
        goingBackwards = false;
        
        if (currentStep < totalSteps) {
            currentStep++;
            navigateToStep(currentStep);
        }
    }
    
    /**
     * Handle Previous button
     */
    function handlePrevious() {
        goingBackwards = true;
        
        if (currentStep > 1) {
            currentStep--;
            navigateToStep(currentStep);
        }
    }
    
    /**
     * Navigate to specific step
     */
    function navigateToStep(step) {
        currentStep = step;
        
        // Hide all steps
        $('.es-onboarding-step').removeClass('active backwards');
        
        // Show target step with animation direction
        const $targetStep = $(`.es-onboarding-step[data-step="${step}"]`);
        $targetStep.addClass('active');
        if (goingBackwards) {
            $targetStep.addClass('backwards');
        }
        
        updateStepDisplay();
        
        // Scroll to top
        $('.es-onboarding-container').animate({ scrollTop: 0 }, 300);
    }
    
    /**
     * Update step display (timeline, buttons)
     */
    function updateStepDisplay() {
        // Update timeline
        $('.es-timeline-step').each(function() {
            const step = parseInt($(this).data('step'));
            $(this).removeClass('active completed');
            
            if (step === currentStep) {
                $(this).addClass('active');
            } else if (step < currentStep) {
                $(this).addClass('completed');
            }
        });
        
        // Update connectors
        $('.es-timeline-connector').each(function(index) {
            $(this).removeClass('active');
            if (index < currentStep - 1) {
                $(this).addClass('active');
            }
        });
        
        // Update navigation buttons
        if (currentStep === 1) {
            $('.es-onboarding-prev').css('visibility', 'hidden');
        } else {
            $('.es-onboarding-prev').css('visibility', 'visible');
        }
        
        if (currentStep === totalSteps) {
            $('.es-onboarding-next').hide();
            $('.es-onboarding-finish').show();
        } else {
            $('.es-onboarding-next').show();
            $('.es-onboarding-finish').hide();
        }
    }
    
    /**
     * Validate current step
     */
    function validateCurrentStep() {
        let isValid = true;
        let errorMessage = '';
        
        if (currentStep === 1) {
            // Validate usage type selection
            if (!$('input[name="usage_type"]:checked').length) {
                errorMessage = 'Please select a usage type.';
                isValid = false;
            }
        } else if (currentStep === 2) {
            // Validate label selection or custom input
            const customLabelChecked = $('#es-use-custom-label').is(':checked');
            const suggestionSelected = $('input[name="label_suggestion"]:checked').length > 0;
            
            if (customLabelChecked) {
                const customSingular = $('#es-custom-label-singular').val().trim();
                const customPlural = $('#es-custom-label-plural').val().trim();
                
                if (!customSingular || !customPlural) {
                    errorMessage = 'Please enter both singular and plural.';
                    isValid = false;
                }
            } else if (!suggestionSelected) {
                errorMessage = 'Please select a label option or use a custom term.';
                isValid = false;
            }
        } else if (currentStep === 3) {
            // Validate experience level
            if (!$('input[name="experience_level"]:checked').length) {
                errorMessage = 'Please select your experience level.';
                isValid = false;
            }
        } else if (currentStep === 4) {
            // Validate custom fields choice
            if (!$('input[name="has_custom_fields"]:checked').length) {
                errorMessage = 'Please select an option.';
                isValid = false;
            }
        }
        
        if (!isValid) {
            alert(errorMessage);
        }
        
        return isValid;
    }
    
    /**
     * Handle Skip button
     */
    function handleSkip() {
        if (confirm('Do you really want to skip the setup? You can configure it later in the settings.')) {
            window.location.href = ensembleOnboardingData.dashboardUrl;
        }
    }
    
    /**
     * Handle Finish button
     */
    function handleFinish() {
        if (!validateCurrentStep()) {
            return;
        }
        
        // Collect all form data
        const formData = collectFormData();
        
        // Show loading state
        const $finishBtn = $('.es-onboarding-finish');
        const originalText = $finishBtn.html();
        $finishBtn.prop('disabled', true).html('<span class="dashicons dashicons-update-alt" style="animation: rotation 2s infinite linear;"></span> Wird eingerichtet...');
        
        // Submit via AJAX
        $.ajax({
            url: ensembleOnboardingData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ensemble_complete_onboarding',
                nonce: ensembleOnboardingData.nonce,
                ...formData
            },
            success: function(response) {
                if (response.success) {
                    // Redirect based on custom fields choice
                    if (formData.has_custom_fields === 'yes') {
                        window.location.href = ensembleOnboardingData.fieldMapperUrl;
                    } else {
                        window.location.href = ensembleOnboardingData.dashboardUrl;
                    }
                } else {
                    alert('Fehler beim Speichern: ' + (response.data.message || 'Unbekannter Fehler'));
                    $finishBtn.prop('disabled', false).html(originalText);
                }
            },
            error: function() {
                alert('Fehler beim Speichern der Einstellungen. Bitte versuche es erneut.');
                $finishBtn.prop('disabled', false).html(originalText);
            }
        });
    }
    
    /**
     * Collect all form data
     */
    function collectFormData() {
        const data = {
            usage_type: $('input[name="usage_type"]:checked').val(),
            experience_level: $('input[name="experience_level"]:checked').val(),
            has_custom_fields: $('input[name="has_custom_fields"]:checked').val()
        };
        
        const mapping = labelMappings[data.usage_type];
        
        // Get artist label configuration
        if ($('#es-use-custom-label').is(':checked')) {
            data.artist_label_singular = $('#es-custom-label-singular').val().trim();
            data.artist_label_plural = $('#es-custom-label-plural').val().trim();
            data.label_source = 'custom';
            
            // Custom location/event labels if provided
            const customLocSingular = $('#es-custom-location-singular').val();
            const customLocPlural = $('#es-custom-location-plural').val();
            const customEvtSingular = $('#es-custom-event-singular').val();
            const customEvtPlural = $('#es-custom-event-plural').val();
            
            if (customLocSingular && customLocPlural) {
                data.location_label_singular = customLocSingular.trim();
                data.location_label_plural = customLocPlural.trim();
            } else if (mapping) {
                data.location_label_singular = mapping.location.singular;
                data.location_label_plural = mapping.location.plural;
            }
            
            if (customEvtSingular && customEvtPlural) {
                data.event_label_singular = customEvtSingular.trim();
                data.event_label_plural = customEvtPlural.trim();
            } else if (mapping) {
                data.event_label_singular = mapping.event.singular;
                data.event_label_plural = mapping.event.plural;
            }
        } else {
            const $selectedSuggestion = $('input[name="label_suggestion"]:checked');
            if ($selectedSuggestion.length) {
                data.artist_label_singular = $selectedSuggestion.data('singular');
                data.artist_label_plural = $selectedSuggestion.data('plural');
                data.label_source = 'suggestion';
            } else if (mapping) {
                // Fallback to default from usage type
                data.artist_label_singular = mapping.default_singular;
                data.artist_label_plural = mapping.default_plural;
                data.label_source = 'default';
            }
            
            // Get location/event labels from mapping
            if (mapping) {
                data.location_label_singular = mapping.location.singular;
                data.location_label_plural = mapping.location.plural;
                data.event_label_singular = mapping.event.singular;
                data.event_label_plural = mapping.event.plural;
            }
        }
        
        return data;
    }
    
    // Initialize on document ready
    $(document).ready(function() {
        // Add rotation animation for loading spinner
        if (!document.getElementById('es-onboarding-animations')) {
            const style = document.createElement('style');
            style.id = 'es-onboarding-animations';
            style.textContent = `
                @keyframes rotation {
                    from { transform: rotate(0deg); }
                    to { transform: rotate(360deg); }
                }
            `;
            document.head.appendChild(style);
        }
        
        init();
    });
    
})(jQuery);
