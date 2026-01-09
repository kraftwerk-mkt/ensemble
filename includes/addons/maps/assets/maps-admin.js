/**
 * Ensemble Maps Add-on - Admin Scripts
 * Handles geocoding in location modal
 */

(function($) {
    'use strict';
    
    const EnsembleMapsAdmin = {
        
        /**
         * Initialize
         */
        init: function() {
            this.bindGeocodeButton();
        },
        
        /**
         * Bind geocode button in location modal
         */
        bindGeocodeButton: function() {
            // Add geocode button after location fields are rendered
            $(document).on('ensemble:location_modal_loaded', function() {
                EnsembleMapsAdmin.addGeocodeButton();
            });
            
            // Also check on page load
            setTimeout(function() {
                EnsembleMapsAdmin.addGeocodeButton();
            }, 500);
        },
        
        /**
         * Add geocode button to location modal
         */
        addGeocodeButton: function() {
            const $latField = $('input[name="latitude"]');
            const $lngField = $('input[name="longitude"]');
            
            // Only add if fields exist and button doesn't exist yet
            if ($latField.length && !$('#es-geocode-btn').length) {
                const $geocodeBtn = $('<button>')
                    .attr({
                        'type': 'button',
                        'id': 'es-geocode-btn',
                        'class': 'button button-secondary'
                    })
                    .html('<span class="dashicons dashicons-location"></span> ' + 
                          'Koordinaten aus Adresse ermitteln')
                    .css({
                        'margin-top': '10px',
                        'display': 'flex',
                        'align-items': 'center',
                        'gap': '5px'
                    });
                
                // Insert after longitude field
                $lngField.after($geocodeBtn);
                
                // Bind click handler
                $geocodeBtn.on('click', function(e) {
                    e.preventDefault();
                    EnsembleMapsAdmin.geocodeAddress();
                });
            }
        },
        
        /**
         * Geocode address from form fields
         */
        geocodeAddress: function() {
            const $btn = $('#es-geocode-btn');
            const $latField = $('input[name="latitude"]');
            const $lngField = $('input[name="longitude"]');
            const $addressField = $('input[name="address"]');
            const $zipField = $('input[name="zip_code"]');
            const $cityField = $('input[name="city"]');
            
            const address = $addressField.val() || '';
            const zip = $zipField.val() || '';
            const city = $cityField.val() || '';
            
            if (!address && !zip && !city) {
                alert('Bitte fülle mindestens ein Adressfeld aus.');
                return;
            }
            
            // Disable button and show loading
            $btn.prop('disabled', true)
                .html('<span class="spinner is-active" style="float:none;margin:0 5px 0 0;"></span> ' + 
                      ensembleMapsAdmin.strings.geocoding);
            
            $.ajax({
                url: ensembleMapsAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'es_geocode_address',
                    nonce: ensembleMapsAdmin.nonce,
                    address: address,
                    zip: zip,
                    city: city
                },
                success: function(response) {
                    if (response.success) {
                        $latField.val(response.data.lat);
                        $lngField.val(response.data.lng);
                        
                        // Show success message
                        EnsembleMapsAdmin.showNotice(
                            ensembleMapsAdmin.strings.success + 
                            ': ' + response.data.lat + ', ' + response.data.lng,
                            'success'
                        );
                    } else {
                        EnsembleMapsAdmin.showNotice(
                            response.data.message || ensembleMapsAdmin.strings.error,
                            'error'
                        );
                    }
                },
                error: function() {
                    EnsembleMapsAdmin.showNotice(
                        ensembleMapsAdmin.strings.error,
                        'error'
                    );
                },
                complete: function() {
                    // Re-enable button
                    $btn.prop('disabled', false)
                        .html('<span class="dashicons dashicons-location"></span> ' + 
                              'Koordinaten aus Adresse ermitteln');
                }
            });
        },
        
        /**
         * Show notice message
         */
        showNotice: function(message, type) {
            const $notice = $('<div>')
                .addClass('notice notice-' + type + ' is-dismissible')
                .html('<p>' + message + '</p>')
                .css({
                    'margin': '10px 0',
                    'padding': '10px 15px'
                });
            
            $('#es-geocode-btn').after($notice);
            
            // Auto-remove after 5 seconds
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        EnsembleMapsAdmin.init();
    });
    
})(jQuery);
