<?php
/**
 * Bristol Layout Helper Functions
 * 
 * @package Ensemble
 * @layout Bristol City Festival
 * @version 2.0.0
 */

if (!defined('ABSPATH')) exit;

/**
 * Enqueue Bristol mode script via WordPress
 * This automatically loads the script when Bristol layout is active
 */
function es_enqueue_bristol_mode_script() {
    if (!class_exists('ES_Layout_Sets')) return;
    
    $active_set = ES_Layout_Sets::get_active_set();
    
    if ($active_set === 'bristol') {
        add_action('wp_footer', 'es_bristol_mode_script', 5);
    }
}
add_action('wp_enqueue_scripts', 'es_enqueue_bristol_mode_script');

/**
 * Output the Bristol Mode toggle script (only once per page)
 */
function es_bristol_mode_script() {
    static $output = false;
    
    if ($output) return;
    $output = true;
    
    ?>
    <script id="es-bristol-mode-script">
    (function() {
        'use strict';
        
        var STORAGE_KEY = 'ensemble_bristol_mode';
        var MODE_DARK = 'dark';
        var MODE_LIGHT = 'light';
        
        // Bristol selectors
        var SELECTORS = '.es-bristol, .es-bristol-card, .es-layout-bristol, ' +
            '.ensemble-events-grid-wrapper.es-layout-bristol, ' +
            '.ensemble-artists-wrapper.es-layout-bristol, ' +
            '.ensemble-locations-wrapper.es-layout-bristol';
        
        function getSavedMode() {
            try { return localStorage.getItem(STORAGE_KEY) || MODE_DARK; }
            catch (e) { return MODE_DARK; }
        }
        
        function saveMode(mode) {
            try { localStorage.setItem(STORAGE_KEY, mode); }
            catch (e) {}
        }
        
        function applyMode(mode) {
            document.body.classList.remove('es-mode-light', 'es-mode-dark');
            document.body.classList.add('es-mode-' + mode);
            
            var containers = document.querySelectorAll(SELECTORS);
            containers.forEach(function(el) {
                el.classList.remove('es-mode-light', 'es-mode-dark');
                el.classList.add('es-mode-' + mode);
            });
            
            // Update toggle icons
            var toggles = document.querySelectorAll('.es-bristol-theme-toggle');
            toggles.forEach(function(toggle) {
                var sun = toggle.querySelector('.icon-sun');
                var moon = toggle.querySelector('.icon-moon');
                if (sun && moon) {
                    sun.style.display = mode === MODE_DARK ? 'block' : 'none';
                    moon.style.display = mode === MODE_DARK ? 'none' : 'block';
                }
            });
        }
        
        function toggleMode() {
            var current = getSavedMode();
            var newMode = current === MODE_DARK ? MODE_LIGHT : MODE_DARK;
            saveMode(newMode);
            applyMode(newMode);
        }
        
        function createToggle() {
            var btn = document.createElement('button');
            btn.className = 'es-bristol-theme-toggle';
            btn.setAttribute('aria-label', 'Toggle dark/light mode');
            btn.innerHTML = '<svg class="icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg><svg class="icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>';
            btn.addEventListener('click', toggleMode);
            return btn;
        }
        
        function init() {
            var hasBristol = document.querySelector(SELECTORS);
            if (!hasBristol) return;
            
            var savedMode = getSavedMode();
            applyMode(savedMode);
            
            if (!document.querySelector('.es-bristol-theme-toggle')) {
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
        
        window.toggleBristolMode = toggleMode;
    })();
    </script>
    <?php
}
