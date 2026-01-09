/**
 * Ensemble Add-ons Admin Scripts
 */

(function($) {
    'use strict';
    
    const EnsembleAddons = {
        
        currentAddonSlug: null,
        
        /**
         * Initialize
         */
        init: function() {
            this.bindToggleSwitch();
            this.bindSettingsButton();
            this.bindModalClose();
            this.bindSaveSettings();
        },
        
        /**
         * Bind toggle switch
         */
        bindToggleSwitch: function() {
            // Flag to prevent initial change events
            let isInitializing = true;
            
            // After page load, allow change events
            setTimeout(function() {
                isInitializing = false;
            }, 500);
            
            $(document).on('change', '.es-addon-toggle-input', function() {
                // Ignore change events during page initialization
                if (isInitializing) {
                    console.log('[Addons] Ignoring change event during initialization');
                    return;
                }
                
                const $toggle = $(this);
                const $card = $toggle.closest('.es-addon-card');
                const slug = $toggle.data('addon-slug');
                const activate = $toggle.prop('checked');
                
                console.log('[Addons] Toggle changed:', slug, 'activate:', activate);
                
                // Disable toggle during request
                $toggle.prop('disabled', true);
                
                $.ajax({
                    url: ensembleAddons.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'es_toggle_addon',
                        nonce: ensembleAddons.nonce,
                        slug: slug,
                        activate: activate
                    },
                    success: function(response) {
                        console.log('[Addons] Toggle response:', response);
                        
                        if (response.success) {
                            // Update card state
                            if (activate) {
                                $card.addClass('es-addon-active');
                                $card.find('.es-addon-toggle-label').text('Aktiv');
                                
                                // Add settings button if addon has settings
                                const addon = EnsembleAddons.findAddon(slug);
                                if (addon && addon.settings_page) {
                                    EnsembleAddons.addSettingsButton($card, slug);
                                }
                            } else {
                                $card.removeClass('es-addon-active');
                                $card.find('.es-addon-toggle-label').text('Inaktiv');
                                $card.find('.es-addon-settings-btn').remove();
                            }
                            
                            // Show success notice
                            EnsembleAddons.showNotice(response.data.message, 'success');
                            
                            // Reload page after 1.5 seconds if activated (to load addon assets)
                            if (activate) {
                                setTimeout(function() {
                                    console.log('[Addons] Reloading page...');
                                    location.reload();
                                }, 1500);
                            } else {
                                // When deactivating, just reload after 1 second to update UI
                                setTimeout(function() {
                                    console.log('[Addons] Reloading page...');
                                    location.reload();
                                }, 1000);
                            }
                        } else {
                            // Revert toggle
                            $toggle.prop('checked', !activate);
                            EnsembleAddons.showNotice(response.data.message, 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('[Addons] Toggle AJAX error:', error);
                        $toggle.prop('checked', !activate);
                        EnsembleAddons.showNotice(ensembleAddons.strings.error, 'error');
                    },
                    complete: function() {
                        $toggle.prop('disabled', false);
                    }
                });
            });
        },
        
        /**
         * Bind settings button
         */
        bindSettingsButton: function() {
            $(document).on('click', '.es-addon-settings-btn', function(e) {
                e.preventDefault();
                
                const slug = $(this).data('addon-slug');
                EnsembleAddons.openSettings(slug);
            });
        },
        
        /**
         * Open settings modal
         */
        openSettings: function(slug) {
            this.currentAddonSlug = slug;
            
            const addon = this.findAddon(slug);
            if (!addon) {
                console.error('[Addons] Addon not found:', slug);
                return;
            }
            
            const $modal = $('#es-addon-settings-modal');
            const $title = $('#es-addon-settings-title');
            const $body = $('#es-addon-settings-body');
            
            $title.text(addon.name + ' - Einstellungen');
            $body.html('<div style="text-align:center;padding:40px;"><span class="spinner is-active" style="float:none;"></span><p>Lade Einstellungen...</p></div>');
            
            $modal.fadeIn(200);
            
            console.log('[Addons] Loading settings for:', slug);
            
            // Load settings via AJAX
            $.ajax({
                url: ensembleAddons.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'es_get_addon_settings',
                    nonce: ensembleAddons.nonce,
                    slug: slug
                },
                success: function(response) {
                    console.log('[Addons] Settings response:', response);
                    if (response.success && response.data.html) {
                        $body.html(response.data.html);
                    } else {
                        $body.html('<p>Keine Einstellungen verfügbar.</p>');
                        if (response.data && response.data.message) {
                            console.error('[Addons] Error:', response.data.message);
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.error('[Addons] AJAX error:', error);
                    $body.html('<p>Fehler beim Laden der Einstellungen.</p>');
                }
            });
        },
        
        /**
         * Get addon instance (if available in window)
         */
        getAddonInstance: function(slug) {
            // This would need to be exposed by PHP
            // For now, we'll load settings HTML from addon directly
            return null;
        },
        
        /**
         * Find addon by slug
         */
        findAddon: function(slug) {
            let found = null;
            $('.es-addon-card').each(function() {
                if ($(this).data('addon-slug') === slug) {
                    const $card = $(this);
                    found = {
                        slug: slug,
                        name: $card.find('.es-addon-meta h3').first().text().trim(),
                        settings_page: $card.find('.es-addon-settings-btn').length > 0
                    };
                    return false;
                }
            });
            return found;
        },
        
        /**
         * Add settings button to card
         */
        addSettingsButton: function($card, slug) {
            if ($card.find('.es-addon-settings-btn').length) return;
            
            const $btn = $('<button>')
                .attr({
                    'type': 'button',
                    'class': 'button button-secondary es-addon-settings-btn',
                    'data-addon-slug': slug
                })
                .html('<span class="dashicons dashicons-admin-generic"></span> Einstellungen');
            
            $card.find('.es-addon-footer').append($btn);
        },
        
        /**
         * Bind modal close
         */
        bindModalClose: function() {
            $(document).on('click', '.es-modal-close, .es-modal-cancel, .es-modal-overlay', function(e) {
                e.preventDefault();
                $('#es-addon-settings-modal').fadeOut(200);
            });
            
            // ESC key
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && $('#es-addon-settings-modal').is(':visible')) {
                    $('#es-addon-settings-modal').fadeOut(200);
                }
            });
        },
        
        /**
         * Bind save settings
         */
        bindSaveSettings: function() {
            $(document).on('click', '.es-addon-save-settings', function(e) {
                e.preventDefault();
                
                if (!EnsembleAddons.currentAddonSlug) return;
                
                const $btn = $(this);
                const $body = $('#es-addon-settings-body');
                
                // Collect settings
                const settings = {};
                $body.find('input, select, textarea').each(function() {
                    const $input = $(this);
                    const name = $input.attr('name');
                    
                    if (!name) return;
                    
                    if ($input.attr('type') === 'checkbox') {
                        // Send "1" for checked, "0" for unchecked (PHP-friendly)
                        settings[name] = $input.prop('checked') ? '1' : '0';
                    } else if ($input.attr('type') === 'radio') {
                        if ($input.prop('checked')) {
                            settings[name] = $input.val();
                        }
                    } else {
                        settings[name] = $input.val();
                    }
                });
                
                // Disable button
                $btn.prop('disabled', true).text(ensembleAddons.strings.activating);
                
                $.ajax({
                    url: ensembleAddons.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'es_save_addon_settings',
                        nonce: ensembleAddons.nonce,
                        slug: EnsembleAddons.currentAddonSlug,
                        settings: settings
                    },
                    success: function(response) {
                        if (response.success) {
                            EnsembleAddons.showNotice(response.data.message, 'success');
                            setTimeout(function() {
                                $('#es-addon-settings-modal').fadeOut(200);
                            }, 500);
                        } else {
                            EnsembleAddons.showNotice(response.data.message, 'error');
                        }
                    },
                    error: function() {
                        EnsembleAddons.showNotice(ensembleAddons.strings.error, 'error');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text('Einstellungen speichern');
                    }
                });
            });
        },
        
        /**
         * Show notice
         */
        showNotice: function(message, type) {
            const $notice = $('<div>')
                .addClass('notice notice-' + type + ' is-dismissible')
                .html('<p>' + message + '</p>')
                .css({
                    'position': 'fixed',
                    'top': '32px',
                    'right': '20px',
                    'z-index': '999999',
                    'min-width': '300px',
                    'box-shadow': '0 4px 12px rgba(0,0,0,0.15)'
                });
            
            $('body').append($notice);
            
            // Auto-remove after 3 seconds
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 3000);
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        EnsembleAddons.init();
    });
    
})(jQuery);
