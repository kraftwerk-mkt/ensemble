/**
 * Ensemble Theme Customizer Preview
 * 
 * Live preview updates for customizer settings
 * 
 * @package Ensemble_Theme
 * @version 2.0.0
 */

(function($) {
    'use strict';
    
    // Site title
    wp.customize('blogname', function(value) {
        value.bind(function(to) {
            $('.et-site-title').text(to);
        });
    });
    
    // Site description
    wp.customize('blogdescription', function(value) {
        value.bind(function(to) {
            $('.et-site-description').text(to);
        });
    });
    
})(jQuery);
