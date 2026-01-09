/**
 * Ensemble Quick-Add Modal
 * JavaScript für Location & Artist Quick-Add Funktionalität
 * Version: 1.0
 */

(function($) {
    'use strict';

    // ========================================
    // MODAL MANAGER CLASS
    // ========================================
    
    class EnsembleQuickAddModal {
        constructor() {
            this.currentType = null; // 'location' or 'artist'
            this.onSuccess = null;
            this.init();
        }

        init() {
            this.createModalHTML();
            this.bindEvents();
        }

        // ========================================
        // CREATE MODAL HTML
        // ========================================
        
        createModalHTML() {
            const modalHTML = `
                <div id="es-quick-modal-overlay" class="es-quick-modal-overlay">
                    <div class="es-quick-modal">
                        <div class="es-quick-modal-header">
                            <h2 id="es-quick-modal-title">Add New</h2>
                            <button type="button" class="es-quick-modal-close" aria-label="Close">
                                <span class="dashicons dashicons-no-alt"></span>
                            </button>
                        </div>
                        
                        <div class="es-quick-modal-body">
                            <div id="es-quick-error" class="es-quick-error"></div>
                            
                            <form id="es-quick-form">
                                <div class="es-quick-form-group">
                                    <label for="es-quick-name">
                                        Name <span class="required">*</span>
                                    </label>
                                    <input 
                                        type="text" 
                                        id="es-quick-name" 
                                        name="name" 
                                        required
                                        autocomplete="off"
                                    />
                                    <p class="es-quick-form-hint">Enter the name (required)</p>
                                </div>
                                
                                <div id="es-quick-description-group" class="es-quick-form-group" style="display: none;">
                                    <label for="es-quick-description">
                                        Description
                                    </label>
                                    <textarea 
                                        id="es-quick-description" 
                                        name="description"
                                        rows="3"
                                    ></textarea>
                                    <p class="es-quick-form-hint">Optional short description</p>
                                </div>
                                
                                <div id="es-quick-address-group" class="es-quick-form-group" style="display: none;">
                                    <label for="es-quick-address">
                                        Address
                                    </label>
                                    <input 
                                        type="text" 
                                        id="es-quick-address" 
                                        name="address"
                                        autocomplete="off"
                                    />
                                    <p class="es-quick-form-hint">Optional address</p>
                                </div>
                            </form>
                        </div>
                        
                        <div class="es-quick-modal-footer">
                            <button type="button" class="button es-quick-cancel">Cancel</button>
                            <button type="button" class="button button-primary es-quick-save">
                                Create
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(modalHTML);
        }

        // ========================================
        // BIND EVENTS
        // ========================================
        
        bindEvents() {
            const self = this;
            
            // Close button
            $(document).on('click', '.es-quick-modal-close, .es-quick-cancel', function() {
                self.close();
            });
            
            // Click outside to close
            $(document).on('click', '#es-quick-modal-overlay', function(e) {
                if (e.target === this) {
                    self.close();
                }
            });
            
            // ESC key to close
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && $('#es-quick-modal-overlay').hasClass('active')) {
                    self.close();
                }
            });
            
            // Save button
            $(document).on('click', '.es-quick-save', function() {
                self.save();
            });
            
            // Enter key in form
            $(document).on('keypress', '#es-quick-form input', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    self.save();
                }
            });
            
            // Trigger links (Add New Location/Artist)
            $(document).on('click', '[data-es-quick-add]', function(e) {
                e.preventDefault();
                const type = $(this).data('es-quick-add');
                self.open(type);
            });
        }

        // ========================================
        // OPEN MODAL
        // ========================================
        
        open(type, successCallback = null) {
            this.currentType = type;
            this.onSuccess = successCallback;
            
            // Set title based on type
            let title = 'Add New';
            if (type === 'location') {
                title = 'Add New Location';
            } else if (type === 'artist') {
                title = 'Add New Artist';
            } else if (type === 'genre') {
                title = 'Add New Genre';
            }
            $('#es-quick-modal-title').text(title);
            
            // Show/hide fields based on type
            if (type === 'location') {
                $('#es-quick-description-group').show();
                $('#es-quick-address-group').show();
            } else if (type === 'genre') {
                // Genre only needs name
                $('#es-quick-description-group').hide();
                $('#es-quick-address-group').hide();
            } else {
                $('#es-quick-description-group').show();
                $('#es-quick-address-group').hide();
            }
            
            // Reset form
            this.reset();
            
            // Show modal
            $('#es-quick-modal-overlay').addClass('active');
            
            // Focus on name input
            setTimeout(() => {
                $('#es-quick-name').focus();
            }, 100);
        }

        // ========================================
        // CLOSE MODAL
        // ========================================
        
        close() {
            $('#es-quick-modal-overlay').removeClass('active');
            this.reset();
        }

        // ========================================
        // RESET FORM
        // ========================================
        
        reset() {
            $('#es-quick-form')[0].reset();
            $('#es-quick-error').removeClass('active').text('');
            $('.es-quick-modal').removeClass('loading');
        }

        // ========================================
        // SHOW ERROR
        // ========================================
        
        showError(message) {
            $('#es-quick-error').text(message).addClass('active');
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                $('#es-quick-error').removeClass('active');
            }, 5000);
        }

        // ========================================
        // SAVE (AJAX)
        // ========================================
        
        save() {
            const self = this;
            
            // Get form data
            const name = $('#es-quick-name').val().trim();
            const description = $('#es-quick-description').val().trim();
            const address = $('#es-quick-address').val().trim();
            
            // Validate
            if (!name) {
                this.showError('Please enter a name');
                $('#es-quick-name').focus();
                return;
            }
            
            // Show loading
            $('.es-quick-modal').addClass('loading');
            $('.es-quick-save').prop('disabled', true);
            
            // Prepare data
            const data = {
                action: 'es_quick_add_' + this.currentType,
                nonce: ensembleAjax.wizard_nonce,
                name: name,
                description: description
            };
            
            if (this.currentType === 'location' && address) {
                data.address = address;
            }
            
            console.log('📤 AJAX Request:', data);
            console.log('📍 AJAX URL:', ensembleAjax.ajaxurl);
            console.log('🔐 Nonce:', ensembleAjax.wizard_nonce);
            
            // AJAX Request
            $.ajax({
                url: ensembleAjax.ajaxurl,
                type: 'POST',
                data: data,
                success: function(response) {
                    console.log('📥 AJAX Response:', response);
                    
                    // Check if response is valid
                    if (typeof response !== 'object') {
                        self.showError('Invalid server response: ' + response);
                        $('.es-quick-modal').removeClass('loading');
                        $('.es-quick-save').prop('disabled', false);
                        return;
                    }
                    
                    if (response.success) {
                        // Success!
                        self.handleSuccess(response.data);
                    } else {
                        // Error from server
                        const errorMsg = (response.data && response.data.message) 
                            ? response.data.message 
                            : 'An error occurred. Please try again.';
                        self.showError(errorMsg);
                        $('.es-quick-modal').removeClass('loading');
                        $('.es-quick-save').prop('disabled', false);
                    }
                },
                error: function(xhr, status, error) {
                    self.showError('Network error: ' + error);
                    $('.es-quick-modal').removeClass('loading');
                    $('.es-quick-save').prop('disabled', false);
                }
            });
        }

        // ========================================
        // HANDLE SUCCESS
        // ========================================
        
        handleSuccess(data) {
            const self = this;
            
            // Add new pill to wizard
            this.addPillToWizard(data);
            
            // Call success callback if provided
            if (typeof this.onSuccess === 'function') {
                this.onSuccess(data);
            }
            
            // Show success message briefly
            let successMsg = 'Created successfully!';
            if (this.currentType === 'location') {
                successMsg = 'Location created successfully!';
            } else if (this.currentType === 'artist') {
                successMsg = 'Artist created successfully!';
            } else if (this.currentType === 'genre') {
                successMsg = 'Genre created successfully!';
            }
            
            // Optional: Show toast notification (if you have one)
            this.showSuccessToast(successMsg);
            
            // Close modal after short delay
            setTimeout(() => {
                self.close();
            }, 300);
        }

        // ========================================
        // ADD PILL TO WIZARD
        // ========================================
        
        addPillToWizard(data) {
            let containerId, inputName, inputType;
            
            if (this.currentType === 'location') {
                containerId = '#es-location-pills';
                inputName = 'event_location';
                inputType = 'radio';
            } else if (this.currentType === 'artist') {
                containerId = '#es-artist-selection';
                inputName = 'event_artist[]';
                inputType = 'checkbox';
            } else if (this.currentType === 'genre') {
                containerId = '#es-genre-pills';
                inputName = 'event_genres[]';
                inputType = 'checkbox';
            }
            
            let newPill;
            
            if (this.currentType === 'artist') {
                // Artist uses new pill structure
                newPill = $(`
                    <div class="es-artist-pill" data-artist-id="${data.id}">
                        <input type="checkbox" 
                               name="event_artist[]" 
                               value="${data.id}"
                               class="es-artist-checkbox"
                               checked>
                        <span class="es-artist-pill-label">
                            <span class="es-artist-pill-name">${data.name}</span>
                            <span class="es-artist-pill-indicators">
                                <span class="es-indicator es-indicator-time" title="Zeit eingestellt">T</span>
                                <span class="es-indicator es-indicator-venue" title="Raum eingestellt">R</span>
                            </span>
                        </span>
                        <button type="button" class="es-artist-pill-edit" title="Zeit & Raum bearbeiten">
                            <span class="dashicons dashicons-edit-page"></span>
                        </button>
                        <input type="hidden" class="es-artist-time" name="artist_time[${data.id}]" value="">
                        <input type="hidden" class="es-artist-venue" name="artist_venue[${data.id}]" value="">
                    </div>
                `);
            } else {
                // Location and Genre use standard pill structure
                newPill = $(`
                    <label class="es-pill">
                        <input 
                            type="${inputType}" 
                            name="${inputName}" 
                            value="${data.id}"
                            ${this.currentType === 'location' ? 'checked' : 'checked'}
                        />
                        <span>${data.name}</span>
                    </label>
                `);
            }
            
            // Add to container
            $(containerId).append(newPill);
            
            // For location, uncheck all others (radio behavior)
            if (this.currentType === 'location') {
                $(containerId).find('input[type="radio"]').not(newPill.find('input')).prop('checked', false);
                $(containerId).find('.es-pill').removeClass('checked');
                newPill.addClass('checked');
            }
            
            // Highlight animation
            newPill.css('animation', 'es-highlight 0.6s');
            
            setTimeout(() => {
                newPill.css('animation', '');
            }, 600);
        }

        // ========================================
        // SHOW SUCCESS TOAST (OPTIONAL)
        // ========================================
        
        showSuccessToast(message) {
            // If you have a global toast/notification system, use it
            // Otherwise, simple console log
            console.log('✅ ' + message);
            
            // Optional: Create simple toast
            const toast = $(`
                <div class="es-quick-toast" style="
                    position: fixed;
                    top: 32px;
                    right: 32px;
                    background: #46b450;
                    color: white;
                    padding: 12px 20px;
                    border-radius: 4px;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                    z-index: 1000000;
                    animation: es-slideInRight 0.3s;
                ">
                    <span class="dashicons dashicons-yes" style="margin-right: 8px;"></span>
                    ${message}
                </div>
            `);
            
            $('body').append(toast);
            
            setTimeout(() => {
                toast.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 2000);
        }
    }

    // ========================================
    // INITIALIZE ON DOCUMENT READY
    // ========================================
    
    $(document).ready(function() {
        console.log('🔍 Quick-Add Modal: Document ready');
        console.log('🔍 Wizard container found:', $('.es-wizard-container').length);
        console.log('🔍 ensembleAjax object:', typeof ensembleAjax !== 'undefined' ? 'exists' : 'MISSING!');
        console.log('🔍 wizard_nonce:', typeof ensembleAjax !== 'undefined' && ensembleAjax.wizard_nonce ? 'exists' : 'MISSING!');
        
        // Only initialize on wizard page
        if ($('.es-wizard-container').length > 0) {
            window.ensembleQuickAddModal = new EnsembleQuickAddModal();
            
            console.log('✅ Ensemble Quick-Add Modal initialized');
        } else {
            console.log('⚠️ Wizard container not found - Quick-Add Modal not initialized');
        }
    });

})(jQuery);

// ========================================
// HIGHLIGHT ANIMATION CSS
// ========================================

const style = document.createElement('style');
style.textContent = `
    @keyframes es-highlight {
        0%, 100% {
            transform: scale(1);
            box-shadow: 0 0 0 rgba(34, 113, 177, 0);
        }
        50% {
            transform: scale(1.05);
            box-shadow: 0 0 20px rgba(34, 113, 177, 0.5);
        }
    }
    
    @keyframes es-slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
`;
document.head.appendChild(style);