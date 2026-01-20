/**
 * Ensemble U18 Authorization Form
 * 
 * Modal-based multi-step form for parental authorization
 */

(function($) {
    'use strict';
    
    // Initialize all U18 forms on the page
    $(document).ready(function() {
        $('.es-u18-wrapper').each(function() {
            new EnsembleU18Form($(this));
        });
    });
    
    function EnsembleU18Form($wrapper) {
        this.$wrapper = $wrapper;
        this.uniqueId = $wrapper.attr('id');
        this.$modal = $('#' + this.uniqueId + '-modal');
        this.$form = this.$modal.find('.es-u18-form');
        this.currentStep = 1;
        this.totalSteps = 4;
        this.signaturePads = {};
        
        this.init();
    }
    
    EnsembleU18Form.prototype = {
        
        init: function() {
            this.bindEvents();
            // Signature pads werden erst beim Öffnen initialisiert
            this.signaturePadsInitialized = false;
        },
        
        bindEvents: function() {
            var self = this;
            
            // Open modal
            this.$wrapper.find('.es-u18-toggle').on('click', function() {
                self.openModal();
            });
            
            // Close modal
            this.$modal.find('.es-u18-modal-close, .es-u18-modal-overlay').on('click', function() {
                self.closeModal();
            });
            
            // Close on success
            this.$modal.find('.es-u18-close-success').on('click', function() {
                self.closeModal();
            });
            
            // Navigation
            this.$modal.find('.es-u18-next').on('click', function() {
                self.nextStep();
            });
            
            this.$modal.find('.es-u18-prev').on('click', function() {
                self.prevStep();
            });
            
            // Submit
            this.$modal.find('.es-u18-submit').on('click', function() {
                self.submitForm();
            });
            
            // Copy address
            this.$form.find('.es-copy-address').on('click', function() {
                self.copyParentAddress();
            });
            
            // Age calculation
            this.$form.find('.es-minor-birthdate, .es-companion-birthdate').on('change', function() {
                self.calculateAge($(this));
            });
            
            // File upload display
            this.$form.find('input[type="file"]').on('change', function() {
                var fileName = $(this).val().split('\\').pop();
                $(this).siblings('.es-file-name').text(fileName);
            });
            
            // Close on escape
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && self.$modal.attr('aria-hidden') === 'false') {
                    self.closeModal();
                }
            });
            
            // Signature clear buttons
            this.$modal.find('.es-signature-clear').on('click', function() {
                var $canvas = $(this).siblings('.es-signature-pad');
                self.clearSignature($canvas[0]);
            });
        },
        
        initSignaturePads: function() {
            var self = this;
            
            this.$modal.find('.es-signature-pad').each(function() {
                var canvas = this;
                var $canvas = $(canvas);
                var target = $canvas.data('target');
                
                // Store reference
                self.signaturePads[target] = {
                    canvas: canvas,
                    ctx: canvas.getContext('2d'),
                    isDrawing: false,
                    isEmpty: true
                };
                
                // Set canvas size
                self.resizeCanvas(canvas);
                
                // Bind drawing events
                self.bindSignatureEvents(canvas, target);
            });
            
            // Resize on window resize
            $(window).on('resize', function() {
                self.$modal.find('.es-signature-pad').each(function() {
                    self.resizeCanvas(this);
                });
            });
        },
        
        resizeCanvas: function(canvas) {
            var $canvas = $(canvas);
            var wrapper = $canvas.parent()[0];
            var ratio = window.devicePixelRatio || 1;
            var target = $canvas.data('target');
            var pad = this.signaturePads[target];
            
            // Speichere ob leer war
            var wasEmpty = pad ? pad.isEmpty : true;
            
            // Setze Canvas-Größe
            var width = wrapper.offsetWidth || 300;
            canvas.width = width * ratio;
            canvas.height = 120 * ratio;
            canvas.style.width = width + 'px';
            canvas.style.height = '120px';
            
            // Konfiguriere Context neu
            var ctx = canvas.getContext('2d');
            ctx.setTransform(1, 0, 0, 1, 0, 0); // Reset transform
            ctx.scale(ratio, ratio);
            ctx.strokeStyle = '#333';
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            
            // Update pad reference
            if (pad) {
                pad.ctx = ctx;
                pad.isEmpty = wasEmpty;
            }
        },
        
        bindSignatureEvents: function(canvas, target) {
            var self = this;
            var pad = this.signaturePads[target];
            
            function getPos(e) {
                var rect = canvas.getBoundingClientRect();
                var clientX, clientY;
                
                if (e.touches && e.touches.length > 0) {
                    clientX = e.touches[0].clientX;
                    clientY = e.touches[0].clientY;
                } else {
                    clientX = e.clientX;
                    clientY = e.clientY;
                }
                
                return {
                    x: clientX - rect.left,
                    y: clientY - rect.top
                };
            }
            
            function startDrawing(e) {
                e.preventDefault();
                pad.isDrawing = true;
                pad.isEmpty = false;
                var pos = getPos(e);
                var ctx = pad.ctx; // Immer aktuellen ctx holen
                ctx.beginPath();
                ctx.moveTo(pos.x, pos.y);
            }
            
            function draw(e) {
                if (!pad.isDrawing) return;
                e.preventDefault();
                var pos = getPos(e);
                var ctx = pad.ctx; // Immer aktuellen ctx holen
                ctx.lineTo(pos.x, pos.y);
                ctx.stroke();
            }
            
            function stopDrawing() {
                if (pad.isDrawing) {
                    pad.isDrawing = false;
                    self.saveSignature(canvas, target);
                }
            }
            
            // Mouse events
            canvas.addEventListener('mousedown', startDrawing);
            canvas.addEventListener('mousemove', draw);
            canvas.addEventListener('mouseup', stopDrawing);
            canvas.addEventListener('mouseleave', stopDrawing);
            
            // Touch events
            canvas.addEventListener('touchstart', startDrawing, { passive: false });
            canvas.addEventListener('touchmove', draw, { passive: false });
            canvas.addEventListener('touchend', stopDrawing);
        },
        
        saveSignature: function(canvas, target) {
            var dataUrl = canvas.toDataURL('image/png');
            this.$form.find('input[name="' + target + '"]').val(dataUrl);
        },
        
        clearSignature: function(canvas) {
            var $canvas = $(canvas);
            var target = $canvas.data('target');
            var pad = this.signaturePads[target];
            
            if (!pad) return; // Noch nicht initialisiert
            
            // Clear canvas
            var ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            pad.isEmpty = true;
            
            // Clear hidden input
            this.$form.find('input[name="' + target + '"]').val('');
            
            // Remove error state
            $canvas.closest('.es-signature-box').removeClass('es-error');
        },
        
        validateSignatures: function() {
            var valid = true;
            var self = this;
            
            if (!this.signaturePadsInitialized) {
                return false; // Noch nicht bereit
            }
            
            this.$modal.find('.es-signature-pad').each(function() {
                var $canvas = $(this);
                var target = $canvas.data('target');
                var pad = self.signaturePads[target];
                var $box = $canvas.closest('.es-signature-box');
                
                if (!pad || pad.isEmpty) {
                    $box.addClass('es-error');
                    valid = false;
                } else {
                    $box.removeClass('es-error');
                }
            });
            
            return valid;
        },
        
        openModal: function() {
            var self = this;
            this.$modal.attr('aria-hidden', 'false');
            $('body').addClass('es-u18-modal-open');
            
            // Signature Pads erst initialisieren wenn Modal sichtbar ist
            setTimeout(function() {
                if (!self.signaturePadsInitialized) {
                    self.initSignaturePads();
                    self.signaturePadsInitialized = true;
                }
                self.resetForm();
            }, 50);
        },
        
        closeModal: function() {
            this.$modal.attr('aria-hidden', 'true');
            $('body').removeClass('es-u18-modal-open');
        },
        
        resetForm: function() {
            var self = this;
            this.currentStep = 1;
            this.$form[0].reset();
            this.$form.find('.es-age-display').removeClass('valid invalid').text('');
            this.$form.find('.es-file-name').text('');
            this.$form.find('.es-u18-message').hide();
            this.$modal.find('.es-u18-success').hide();
            this.$form.show();
            
            // Clear signature pads (nur wenn initialisiert)
            if (this.signaturePadsInitialized) {
                this.$modal.find('.es-signature-pad').each(function() {
                    self.clearSignature(this);
                });
            }
            
            this.updateStepDisplay();
        },
        
        nextStep: function() {
            var self = this;
            
            // Validate current step
            if (!this.validateStep(this.currentStep)) {
                return;
            }
            
            if (this.currentStep < this.totalSteps) {
                this.currentStep++;
                this.updateStepDisplay();
                
                // Update summary on step 4
                if (this.currentStep === 4) {
                    this.updateSummary();
                    
                    // Resize signature canvases when they become visible
                    setTimeout(function() {
                        self.$modal.find('.es-signature-pad').each(function() {
                            self.resizeCanvas(this);
                        });
                    }, 50);
                }
            }
        },
        
        prevStep: function() {
            if (this.currentStep > 1) {
                this.currentStep--;
                this.updateStepDisplay();
            }
        },
        
        updateStepDisplay: function() {
            var self = this;
            
            // Update progress
            this.$modal.find('.es-u18-step').each(function() {
                var step = $(this).data('step');
                $(this).removeClass('active completed');
                if (step < self.currentStep) {
                    $(this).addClass('completed');
                } else if (step === self.currentStep) {
                    $(this).addClass('active');
                }
            });
            
            // Update content
            this.$form.find('.es-u18-step-content').removeClass('active');
            this.$form.find('.es-u18-step-content[data-step="' + this.currentStep + '"]').addClass('active');
            
            // Update buttons
            this.$modal.find('.es-u18-prev').toggle(this.currentStep > 1);
            this.$modal.find('.es-u18-next').toggle(this.currentStep < this.totalSteps);
            this.$modal.find('.es-u18-submit').toggle(this.currentStep === this.totalSteps);
        },
        
        validateStep: function(step) {
            var self = this;
            var $stepContent = this.$form.find('.es-u18-step-content[data-step="' + step + '"]');
            var isValid = true;
            
            // Remove previous error states
            $stepContent.find('.error').removeClass('error');
            
            // Check required fields
            $stepContent.find('input[required], textarea[required]').each(function() {
                if (!$(this).val()) {
                    $(this).addClass('error');
                    isValid = false;
                }
            });
            
            // Step 2: Check minor age (16-17)
            if (step === 2) {
                var minorAge = this.getAge(this.$form.find('[name="minor_birthdate"]').val());
                if (minorAge < 16 || minorAge >= 18) {
                    this.$form.find('[name="minor_birthdate"]').addClass('error');
                    this.showMessage(ensembleU18.strings.invalidAge + ' (16-17)', 'error');
                    isValid = false;
                }
            }
            
            // Step 3: Check companion age (18+)
            if (step === 3) {
                var companionAge = this.getAge(this.$form.find('[name="companion_birthdate"]').val());
                if (companionAge < 18) {
                    this.$form.find('[name="companion_birthdate"]').addClass('error');
                    this.showMessage(ensembleU18.strings.invalidAge + ' (18+)', 'error');
                    isValid = false;
                }
            }
            
            // Step 4: Check consents and signatures
            if (step === 4) {
                $stepContent.find('input[type="checkbox"][required]').each(function() {
                    if (!$(this).is(':checked')) {
                        $(this).addClass('error');
                        isValid = false;
                    }
                });
                
                // Validate signatures
                if (!this.validateSignatures()) {
                    this.showMessage(ensembleU18.strings.signatureRequired || 'Bitte unterschreiben Sie beide Felder.', 'error');
                    isValid = false;
                }
            }
            
            if (!isValid && step !== 2 && step !== 3) {
                this.showMessage(ensembleU18.strings.required, 'error');
            }
            
            return isValid;
        },
        
        getAge: function(birthdate) {
            if (!birthdate) return 0;
            var today = new Date();
            var birth = new Date(birthdate);
            var age = today.getFullYear() - birth.getFullYear();
            var m = today.getMonth() - birth.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) {
                age--;
            }
            return age;
        },
        
        calculateAge: function($input) {
            var age = this.getAge($input.val());
            var $display = $input.siblings('.es-age-display');
            var isMinor = $input.hasClass('es-minor-birthdate');
            
            if (age > 0) {
                $display.text(age + ' Jahre');
                
                if (isMinor) {
                    // Minor must be 16-17
                    $display.toggleClass('valid', age >= 16 && age < 18);
                    $display.toggleClass('invalid', age < 16 || age >= 18);
                } else {
                    // Companion must be 18+
                    $display.toggleClass('valid', age >= 18);
                    $display.toggleClass('invalid', age < 18);
                }
            } else {
                $display.text('').removeClass('valid invalid');
            }
        },
        
        copyParentAddress: function() {
            this.$form.find('[name="minor_street"]').val(this.$form.find('[name="parent_street"]').val());
            this.$form.find('[name="minor_zip"]').val(this.$form.find('[name="parent_zip"]').val());
            this.$form.find('[name="minor_city"]').val(this.$form.find('[name="parent_city"]').val());
        },
        
        updateSummary: function() {
            var $form = this.$form;
            
            // Parent summary
            var parent = $form.find('[name="parent_firstname"]').val() + ' ' + $form.find('[name="parent_lastname"]').val();
            parent += '<br>' + $form.find('[name="parent_street"]').val();
            parent += '<br>' + $form.find('[name="parent_zip"]').val() + ' ' + $form.find('[name="parent_city"]').val();
            parent += '<br>Tel: ' + $form.find('[name="parent_phone"]').val();
            parent += '<br>E-Mail: ' + $form.find('[name="parent_email"]').val();
            this.$modal.find('.es-summary-parent').html(parent);
            
            // Minor summary
            var minor = $form.find('[name="minor_firstname"]').val() + ' ' + $form.find('[name="minor_lastname"]').val();
            minor += '<br>Geb.: ' + this.formatDate($form.find('[name="minor_birthdate"]').val());
            minor += ' (' + this.getAge($form.find('[name="minor_birthdate"]').val()) + ' Jahre)';
            minor += '<br>' + $form.find('[name="minor_street"]').val();
            minor += '<br>' + $form.find('[name="minor_zip"]').val() + ' ' + $form.find('[name="minor_city"]').val();
            this.$modal.find('.es-summary-minor').html(minor);
            
            // Companion summary
            var companion = $form.find('[name="companion_firstname"]').val() + ' ' + $form.find('[name="companion_lastname"]').val();
            companion += '<br>Geb.: ' + this.formatDate($form.find('[name="companion_birthdate"]').val());
            companion += ' (' + this.getAge($form.find('[name="companion_birthdate"]').val()) + ' Jahre)';
            companion += '<br>' + $form.find('[name="companion_street"]').val();
            companion += '<br>' + $form.find('[name="companion_zip"]').val() + ' ' + $form.find('[name="companion_city"]').val();
            companion += '<br>Tel: ' + $form.find('[name="companion_phone"]').val();
            var companionEmail = $form.find('[name="companion_email"]').val();
            if (companionEmail) {
                companion += '<br>E-Mail: ' + companionEmail;
            }
            this.$modal.find('.es-summary-companion').html(companion);
        },
        
        formatDate: function(dateStr) {
            if (!dateStr) return '';
            var parts = dateStr.split('-');
            return parts[2] + '.' + parts[1] + '.' + parts[0];
        },
        
        submitForm: function() {
            var self = this;
            
            // Final validation
            if (!this.validateStep(4)) {
                return;
            }
            
            var $btn = this.$modal.find('.es-u18-submit');
            var $btnText = $btn.find('.es-btn-text');
            var $btnLoading = $btn.find('.es-btn-loading');
            
            // Loading state
            $btn.prop('disabled', true);
            $btnText.hide();
            $btnLoading.show();
            
            // Prepare form data
            var formData = new FormData(this.$form[0]);
            formData.append('action', 'es_submit_u18_authorization');
            
            $.ajax({
                url: ensembleU18.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        self.showSuccess(response.data);
                    } else {
                        self.showMessage(response.data.message || ensembleU18.strings.error, 'error');
                    }
                },
                error: function() {
                    self.showMessage(ensembleU18.strings.error, 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false);
                    $btnText.show();
                    $btnLoading.hide();
                }
            });
        },
        
        showSuccess: function(data) {
            this.$form.hide();
            this.$modal.find('.es-u18-next, .es-u18-prev, .es-u18-submit').hide();
            this.$modal.find('.es-u18-close-success').show();
            
            var $success = this.$modal.find('.es-u18-success');
            $success.find('.es-success-message').text(data.message || '');
            $success.find('.es-code-value').text(data.code || '');
            $success.show();
        },
        
        showMessage: function(message, type) {
            var $msg = this.$form.find('.es-u18-message');
            $msg.removeClass('es-message-error es-message-success')
                .addClass('es-message-' + type)
                .text(message)
                .show();
            
            // Auto-hide after 5 seconds
            setTimeout(function() {
                $msg.fadeOut();
            }, 5000);
        }
    };
    
})(jQuery);
