/**
 * Bristol Layout - Global Dark/Light Mode
 * 
 * Handles mode switching across all pages
 * Persists choice in localStorage
 * 
 * @package Ensemble
 * @layout Bristol City Festival
 * @version 2.0.0
 */

(function() {
    'use strict';
    
    const STORAGE_KEY = 'ensemble_bristol_mode';
    const MODE_LIGHT = 'light';
    const MODE_DARK = 'dark';
    
    // Bristol selectors (cards, grids, and single pages)
    const BRISTOL_SELECTORS = [
        '.es-bristol',
        '.es-bristol-card',
        '.es-layout-bristol',
        '.ensemble-events-grid-wrapper.es-layout-bristol',
        '.ensemble-artists-wrapper.es-layout-bristol',
        '.ensemble-locations-wrapper.es-layout-bristol'
    ].join(', ');
    
    /**
     * Get saved mode from localStorage
     */
    function getSavedMode() {
        try {
            return localStorage.getItem(STORAGE_KEY) || MODE_DARK; // Bristol defaults to dark
        } catch (e) {
            return MODE_DARK;
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
     * Apply mode to all Bristol elements
     */
    function applyMode(mode) {
        // Apply to body
        document.body.classList.remove('es-mode-light', 'es-mode-dark');
        document.body.classList.add('es-mode-' + mode);
        
        // Apply to all Bristol containers
        const containers = document.querySelectorAll(BRISTOL_SELECTORS);
        
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
        const toggles = document.querySelectorAll('.es-bristol-theme-toggle');
        toggles.forEach(function(toggle) {
            const sunIcon = toggle.querySelector('.icon-sun');
            const moonIcon = toggle.querySelector('.icon-moon');
            
            if (sunIcon && moonIcon) {
                if (mode === MODE_DARK) {
                    // Dark mode: show sun icon (to switch to light)
                    sunIcon.style.display = 'block';
                    moonIcon.style.display = 'none';
                } else {
                    // Light mode: show moon icon (to switch to dark)
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
        button.className = 'es-bristol-theme-toggle';
        button.setAttribute('aria-label', 'Toggle dark/light mode');
        button.innerHTML = `
            <svg class="icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
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
            <svg class="icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
            </svg>
        `;
        button.addEventListener('click', toggleMode);
        return button;
    }
    
    /**
     * Initialize on DOM ready
     */
    function init() {
        // Check if Bristol layout is active anywhere on the page
        const hasBristol = document.querySelector(BRISTOL_SELECTORS);
        
        if (!hasBristol) return;
        
        // Apply saved mode immediately
        const savedMode = getSavedMode();
        applyMode(savedMode);
        
        // Add toggle button if not already present
        if (!document.querySelector('.es-bristol-theme-toggle')) {
            document.body.appendChild(createToggleButton());
            updateToggleIcon(savedMode);
        }
    }
    
    // Apply mode immediately to prevent flash (before DOM ready)
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
    window.toggleBristolMode = toggleMode;
    window.setBristolMode = function(mode) {
        if (mode === MODE_LIGHT || mode === MODE_DARK) {
            saveMode(mode);
            applyMode(mode);
        }
    };
    
})();
