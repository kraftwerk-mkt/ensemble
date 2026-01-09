/**
 * Pure Layout - Global Dark/Light Mode
 * 
 * Handles mode switching across all pages
 * Persists choice in localStorage
 * 
 * @package Ensemble
 * @version 2.0.0
 */

(function() {
    'use strict';
    
    const STORAGE_KEY = 'ensemble_pure_mode';
    const MODE_LIGHT = 'light';
    const MODE_DARK = 'dark';
    
    /**
     * Get saved mode from localStorage
     */
    function getSavedMode() {
        try {
            return localStorage.getItem(STORAGE_KEY) || MODE_LIGHT;
        } catch (e) {
            return MODE_LIGHT;
        }
    }
    
    /**
     * Save mode to localStorage
     */
    function saveMode(mode) {
        try {
            localStorage.setItem(STORAGE_KEY, mode);
        } catch (e) {
            // localStorage not available
        }
    }
    
    /**
     * Apply mode to all Pure elements
     */
    function applyMode(mode) {
        // Apply to body
        document.body.classList.remove('es-mode-light', 'es-mode-dark');
        document.body.classList.add('es-mode-' + mode);
        
        // Apply to all Pure containers
        const containers = document.querySelectorAll(
            '.es-layout-pure, .es-pure-single-event, .es-pure-single-artist, .es-pure-single-location, ' +
            '.ensemble-events-grid-wrapper.es-layout-pure, .ensemble-artists-wrapper.es-layout-pure, ' +
            '.ensemble-locations-wrapper.es-layout-pure'
        );
        
        containers.forEach(function(el) {
            el.classList.remove('es-mode-light', 'es-mode-dark');
            el.classList.add('es-mode-' + mode);
        });
        
        // Update toggle button icon
        updateToggleIcon(mode);
    }
    
    /**
     * Update toggle button icon
     */
    function updateToggleIcon(mode) {
        const toggles = document.querySelectorAll('.es-mode-toggle');
        toggles.forEach(function(toggle) {
            const sunIcon = toggle.querySelector('.es-icon-sun');
            const moonIcon = toggle.querySelector('.es-icon-moon');
            
            if (sunIcon && moonIcon) {
                if (mode === MODE_DARK) {
                    sunIcon.style.display = 'block';
                    moonIcon.style.display = 'none';
                } else {
                    sunIcon.style.display = 'none';
                    moonIcon.style.display = 'block';
                }
            }
        });
    }
    
    /**
     * Toggle between modes
     */
    function toggleMode() {
        const currentMode = getSavedMode();
        const newMode = currentMode === MODE_DARK ? MODE_LIGHT : MODE_DARK;
        
        saveMode(newMode);
        applyMode(newMode);
    }
    
    /**
     * Create toggle button HTML
     */
    function createToggleButton() {
        const button = document.createElement('button');
        button.className = 'es-mode-toggle';
        button.setAttribute('aria-label', 'Toggle dark/light mode');
        button.innerHTML = `
            <svg class="es-icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
            </svg>
            <svg class="es-icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <circle cx="12" cy="12" r="5"/>
                <line x1="12" y1="1" x2="12" y2="3"/>
                <line x1="12" y1="21" x2="12" y2="23"/>
                <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>
                <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
                <line x1="1" y1="12" x2="3" y2="12"/>
                <line x1="21" y1="12" x2="23" y2="12"/>
                <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/>
                <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
            </svg>
        `;
        button.addEventListener('click', toggleMode);
        return button;
    }
    
    /**
     * Initialize on DOM ready
     */
    function init() {
        // Check if Pure layout is active
        const hasPure = document.querySelector(
            '.es-layout-pure, .es-pure-single-event, .es-pure-single-artist, .es-pure-single-location'
        );
        
        if (!hasPure) return;
        
        // Apply saved mode immediately
        const savedMode = getSavedMode();
        applyMode(savedMode);
        
        // Add toggle button if not already present
        if (!document.querySelector('.es-mode-toggle')) {
            document.body.appendChild(createToggleButton());
            updateToggleIcon(savedMode);
        }
    }
    
    // Apply mode immediately to prevent flash
    (function() {
        const savedMode = getSavedMode();
        document.documentElement.classList.add('es-mode-' + savedMode);
    })();
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    // Expose toggle function globally
    window.togglePureMode = toggleMode;
    window.setPureMode = function(mode) {
        if (mode === MODE_LIGHT || mode === MODE_DARK) {
            saveMode(mode);
            applyMode(mode);
        }
    };
    
})();
