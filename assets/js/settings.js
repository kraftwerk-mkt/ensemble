/**
 * Ensemble Settings JavaScript
 * 
 * @package Ensemble
 */

(function($) {
    'use strict';
    
    // Initialize
    $(document).ready(function() {
        initSortable();
        initCheckboxHandling();
        initFieldMapping();
        initUsageTypeDropdown();
    });
    
    /**
     * Initialize Usage Type Dropdown
     * Auto-fills label fields when usage type changes
     */
    function initUsageTypeDropdown() {
        // Define default labels for each usage type
        const defaultLabels = {
            'clubs': {
                'artist_singular': 'DJ / Artist',
                'artist_plural': 'DJs & Artists',
                'location_singular': 'Venue',
                'location_plural': 'Venues'
            },
            'theater': {
                'artist_singular': 'Mitwirkende:r',
                'artist_plural': 'Ensemble',
                'location_singular': 'Spielstätte',
                'location_plural': 'Spielstätten'
            },
            'church': {
                'artist_singular': 'Pfarrer:in',
                'artist_plural': 'Geistliche',
                'location_singular': 'Kirche',
                'location_plural': 'Kirchen'
            },
            'fitness': {
                'artist_singular': 'Trainer:in',
                'artist_plural': 'Trainer:innen',
                'location_singular': 'Studio',
                'location_plural': 'Studios'
            },
            'education': {
                'artist_singular': 'Dozent:in',
                'artist_plural': 'Dozent:innen',
                'location_singular': 'Raum',
                'location_plural': 'Räume'
            },
            'public': {
                'artist_singular': 'Guide',
                'artist_plural': 'Guides',
                'location_singular': 'Ort',
                'location_plural': 'Orte'
            },
            'mixed': {
                'artist_singular': 'Mitwirkende:r',
                'artist_plural': 'Mitwirkende',
                'location_singular': 'Location',
                'location_plural': 'Locations'
            }
        };
        
        // Handle usage type change
        $('#usage_type').on('change', function() {
            const selectedType = $(this).val();
            
            // Only auto-fill if we have defaults for this type
            if (defaultLabels[selectedType]) {
                const labels = defaultLabels[selectedType];
                
                // Fill artist labels
                $('#artist_label_singular').val(labels.artist_singular);
                $('#artist_label_plural').val(labels.artist_plural);
                
                // Fill location labels
                $('#location_label_singular').val(labels.location_singular);
                $('#location_label_plural').val(labels.location_plural);
                
                // Visual feedback - highlight changed fields
                $('#artist_label_singular, #artist_label_plural, #location_label_singular, #location_label_plural')
                    .css('background-color', '#fff3cd')
                    .animate({ backgroundColor: '#ffffff' }, 1000);
            }
        });
    }
    
    /**
     * Initialize Field Mapping functionality
     */
    function initFieldMapping() {
        // Field search
        $('.es-field-search').on('input', function() {
            const searchTerm = $(this).val().toLowerCase();
            const targetField = $(this).data('target');
            const $pillsContainer = $('.es-field-pills[data-target="' + targetField + '"]');
            
            $pillsContainer.find('.es-field-pill').each(function() {
                const $pill = $(this);
                const label = $pill.find('strong').text().toLowerCase();
                const name = $pill.find('small').text().toLowerCase();
                
                if (label.includes(searchTerm) || name.includes(searchTerm)) {
                    $pill.removeClass('es-field-hidden');
                } else {
                    $pill.addClass('es-field-hidden');
                }
            });
        });
        
        // Field pill click - select field
        $(document).on('click', '.es-field-pill:not(.es-field-selected)', function() {
            const $pill = $(this);
            const fieldKey = $pill.data('field-key');
            const $container = $pill.closest('.es-acf-field-selector');
            const $hiddenInput = $container.find('input[type="hidden"]');
            const $searchWrapper = $container.find('.es-field-search-wrapper');
            
            // Set value
            $hiddenInput.val(fieldKey);
            
            // Clone pill and mark as selected
            const $selectedPill = $pill.clone();
            $selectedPill.addClass('es-field-selected');
            $selectedPill.append('<span class="es-remove-field">×</span>');
            
            // Remove existing selection if any
            $searchWrapper.find('.es-selected-field').remove();
            
            // Add selected pill
            $searchWrapper.append('<div class="es-selected-field"></div>');
            $searchWrapper.find('.es-selected-field').append($selectedPill);
            
            // Clear search
            $container.find('.es-field-search').val('');
            $container.find('.es-field-pill').removeClass('es-field-hidden');
        });
        
        // Remove field mapping
        $(document).on('click', '.es-remove-field', function(e) {
            e.stopPropagation();
            const $container = $(this).closest('.es-acf-field-selector');
            const $hiddenInput = $container.find('input[type="hidden"]');
            
            // Clear value
            $hiddenInput.val('');
            
            // Remove selected pill
            $(this).closest('.es-selected-field').remove();
        });
        
        // Show all fields button
        $(document).on('click', '.es-show-all-fields', function() {
            const $button = $(this);
            const targetField = $button.data('target');
            const $container = $('.es-field-pills[data-target="' + targetField + '"]');
            const $allFieldsContainer = $container.find('.es-all-fields-container');
            
            if ($allFieldsContainer.is(':visible')) {
                $allFieldsContainer.slideUp(200);
                $button.html('⊕ ' + $button.data('show-text'));
            } else {
                $allFieldsContainer.slideDown(200);
                const originalText = $button.html();
                $button.data('show-text', originalText);
                $button.html('⊖ Hide all fields');
            }
        });
    }
    
    /**
     * Initialize checkbox handling for pills
     */
    function initCheckboxHandling() {
        // Set initial checked state
        $('.es-sortable-pill').each(function() {
            const $pill = $(this);
            const $checkbox = $pill.find('input[type="checkbox"]');
            
            if ($checkbox.is(':checked')) {
                $pill.addClass('checked');
            }
        });
        
        // Handle checkbox changes
        $(document).on('change', '.es-sortable-pill input[type="checkbox"]', function() {
            const $checkbox = $(this);
            const $pill = $checkbox.closest('.es-sortable-pill');
            
            if ($checkbox.is(':checked')) {
                $pill.addClass('checked');
            } else {
                $pill.removeClass('checked');
            }
        });
        
        // Handle pill clicks (toggle checkbox)
        $(document).on('click', '.es-sortable-pill > span:last-child', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const $span = $(this);
            const $pill = $span.closest('.es-sortable-pill');
            const $checkbox = $pill.find('input[type="checkbox"]');
            
            // Toggle checkbox
            $checkbox.prop('checked', !$checkbox.is(':checked')).trigger('change');
        });
        
        // Prevent drag when clicking on text
        $(document).on('mousedown', '.es-sortable-pill > span:last-child', function(e) {
            e.stopPropagation();
        });
    }
    
    /**
     * Initialize sortable for field group ordering
     */
    function initSortable() {
        if (typeof Sortable === 'undefined') {
            // Load SortableJS from CDN if not available
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js';
            script.onload = function() {
                setupSortable();
            };
            document.head.appendChild(script);
        } else {
            setupSortable();
        }
    }
    
    /**
     * Setup sortable instances
     */
    function setupSortable() {
        $('.es-sortable').each(function() {
            const el = this;
            
            new Sortable(el, {
                animation: 150,
                handle: '.es-drag-handle',
                ghostClass: 'sortable-ghost',
                dragClass: 'sortable-drag',
                onEnd: function(evt) {
                    // Update order in form
                    updateFieldOrder($(el));
                }
            });
        });
    }
    
    /**
     * Update field order after drag
     */
    function updateFieldOrder($container) {
        const categoryId = $container.data('category');
        const $items = $container.find('.es-sortable-pill');
        
        // Reorder checkbox inputs to match visual order
        $items.each(function(index) {
            const $item = $(this);
            const $checkbox = $item.find('input[type="checkbox"]');
            
            // Move checkbox to maintain order in form data
            $container.append($item);
        });
    }
    
    /**
     * Initialize wizard field toggles
     */
    function initWizardFieldToggles() {
        // Update visual state when toggle changes
        $(document).on('change', '.es-field-toggle-item .es-toggle-switch input', function() {
            const $item = $(this).closest('.es-field-toggle-item');
            
            if ($(this).is(':checked')) {
                $item.removeClass('es-field-disabled').addClass('es-field-enabled');
            } else {
                $item.removeClass('es-field-enabled').addClass('es-field-disabled');
            }
        });
    }
    
    // Initialize on DOM ready
    initWizardFieldToggles();
    
})(jQuery);