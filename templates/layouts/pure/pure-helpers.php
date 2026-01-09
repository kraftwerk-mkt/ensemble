<?php
/**
 * Pure Layout Helper Functions
 * 
 * @package Ensemble
 * @version 2.0.0
 */

if (!defined('ABSPATH')) exit;

/**
 * Output the Pure Mode toggle script (only once per page)
 */
function es_pure_mode_script() {
    static $output = false;
    
    if ($output) return;
    $output = true;
    
    ?>
    <script id="es-pure-mode-script">
    (function() {
        'use strict';
        
        var STORAGE_KEY = 'ensemble_pure_mode';
        
        function getSavedMode() {
            try { return localStorage.getItem(STORAGE_KEY) || 'light'; }
            catch (e) { return 'light'; }
        }
        
        function saveMode(mode) {
            try { localStorage.setItem(STORAGE_KEY, mode); }
            catch (e) {}
        }
        
        function applyMode(mode) {
            document.body.classList.remove('es-mode-light', 'es-mode-dark');
            document.body.classList.add('es-mode-' + mode);
            
            var containers = document.querySelectorAll(
                '.es-layout-pure, .es-pure-single-event, .es-pure-single-artist, .es-pure-single-location'
            );
            containers.forEach(function(el) {
                el.classList.remove('es-mode-light', 'es-mode-dark');
                el.classList.add('es-mode-' + mode);
            });
            
            // Update toggle icons
            var toggles = document.querySelectorAll('.es-mode-toggle');
            toggles.forEach(function(toggle) {
                var sun = toggle.querySelector('.es-icon-sun');
                var moon = toggle.querySelector('.es-icon-moon');
                if (sun && moon) {
                    sun.style.display = mode === 'dark' ? 'block' : 'none';
                    moon.style.display = mode === 'dark' ? 'none' : 'block';
                }
            });
        }
        
        function toggleMode() {
            var current = getSavedMode();
            var newMode = current === 'dark' ? 'light' : 'dark';
            saveMode(newMode);
            applyMode(newMode);
        }
        
        function createToggle() {
            var btn = document.createElement('button');
            btn.className = 'es-mode-toggle';
            btn.setAttribute('aria-label', 'Toggle dark/light mode');
            btn.innerHTML = '<svg class="es-icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg><svg class="es-icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>';
            btn.addEventListener('click', toggleMode);
            return btn;
        }
        
        function init() {
            var hasPure = document.querySelector('.es-layout-pure, .es-pure-single-event, .es-pure-single-artist, .es-pure-single-location');
            if (!hasPure) return;
            
            var savedMode = getSavedMode();
            applyMode(savedMode);
            
            if (!document.querySelector('.es-mode-toggle')) {
                document.body.appendChild(createToggle());
            }
        }
        
        // Apply immediately to prevent flash
        var saved = getSavedMode();
        document.documentElement.classList.add('es-mode-' + saved);
        
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }
        
        window.togglePureMode = toggleMode;
    })();
    </script>
    <?php
}

/**
 * Enqueue Pure mode script via WordPress
 */
function es_enqueue_pure_mode_script() {
    if (!class_exists('ES_Layout_Sets')) return;
    
    $active_set = ES_Layout_Sets::get_active_set();
    
    if ($active_set === 'pure') {
        add_action('wp_footer', 'es_pure_mode_script', 5);
    }
}
add_action('wp_enqueue_scripts', 'es_enqueue_pure_mode_script');
