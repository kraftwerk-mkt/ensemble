/**
 * Ensemble Theme JavaScript
 * 
 * Handles navigation, search, and interactions
 */

(function() {
    'use strict';
    
    // DOM ready
    document.addEventListener('DOMContentLoaded', function() {
        initMobileMenu();
        initSearch();
        initStickyHeader();
        initBackToTop();
        initSmoothScroll();
    });
    
    /**
     * Mobile Menu Toggle
     */
    function initMobileMenu() {
        const toggles = document.querySelectorAll('.et-menu-toggle, .et-menu-toggle--minimal');
        const mobileMenus = document.querySelectorAll('.et-header__mobile-menu');
        const overlays = document.querySelectorAll('.et-header__overlay');
        const closeButtons = document.querySelectorAll('.et-header__overlay-close');
        
        toggles.forEach(function(toggle) {
            toggle.addEventListener('click', function() {
                const isExpanded = this.getAttribute('aria-expanded') === 'true';
                this.setAttribute('aria-expanded', !isExpanded);
                this.classList.toggle('et-menu-toggle--open');
                
                // Toggle mobile menu
                mobileMenus.forEach(function(menu) {
                    menu.hidden = isExpanded;
                });
                
                // Toggle overlay (for minimal header)
                overlays.forEach(function(overlay) {
                    overlay.hidden = isExpanded;
                    if (!isExpanded) {
                        document.body.style.overflow = 'hidden';
                    } else {
                        document.body.style.overflow = '';
                    }
                });
            });
        });
        
        // Close buttons for overlay
        closeButtons.forEach(function(btn) {
            btn.addEventListener('click', function() {
                overlays.forEach(function(overlay) {
                    overlay.hidden = true;
                });
                toggles.forEach(function(toggle) {
                    toggle.setAttribute('aria-expanded', 'false');
                    toggle.classList.remove('et-menu-toggle--open');
                });
                document.body.style.overflow = '';
            });
        });
        
        // Close on escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                mobileMenus.forEach(function(menu) {
                    menu.hidden = true;
                });
                overlays.forEach(function(overlay) {
                    overlay.hidden = true;
                });
                toggles.forEach(function(toggle) {
                    toggle.setAttribute('aria-expanded', 'false');
                    toggle.classList.remove('et-menu-toggle--open');
                });
                document.body.style.overflow = '';
            }
        });
    }
    
    /**
     * Search Toggle
     */
    function initSearch() {
        const searchToggles = document.querySelectorAll('.et-search-toggle');
        const searchOverlays = document.querySelectorAll('.et-search-overlay');
        const searchCloses = document.querySelectorAll('.et-search-close, .et-search-overlay__close');
        
        searchToggles.forEach(function(toggle) {
            toggle.addEventListener('click', function() {
                searchOverlays.forEach(function(overlay) {
                    overlay.hidden = !overlay.hidden;
                    
                    if (!overlay.hidden) {
                        const input = overlay.querySelector('input[type="search"]');
                        if (input) {
                            setTimeout(function() {
                                input.focus();
                            }, 100);
                        }
                        document.body.style.overflow = 'hidden';
                    } else {
                        document.body.style.overflow = '';
                    }
                });
            });
        });
        
        searchCloses.forEach(function(close) {
            close.addEventListener('click', function() {
                searchOverlays.forEach(function(overlay) {
                    overlay.hidden = true;
                });
                document.body.style.overflow = '';
            });
        });
        
        // Close on escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                searchOverlays.forEach(function(overlay) {
                    overlay.hidden = true;
                });
                document.body.style.overflow = '';
            }
        });
        
        // Close on click outside
        searchOverlays.forEach(function(overlay) {
            overlay.addEventListener('click', function(e) {
                if (e.target === overlay) {
                    overlay.hidden = true;
                    document.body.style.overflow = '';
                }
            });
        });
    }
    
    /**
     * Sticky Header Behavior
     */
    function initStickyHeader() {
        const header = document.querySelector('.et-header');
        if (!header || !document.body.classList.contains('et-header-sticky')) return;
        
        let lastScroll = 0;
        let ticking = false;
        
        window.addEventListener('scroll', function() {
            if (!ticking) {
                window.requestAnimationFrame(function() {
                    handleScroll();
                    ticking = false;
                });
                ticking = true;
            }
        });
        
        function handleScroll() {
            const currentScroll = window.pageYOffset;
            
            // Add scrolled class after 50px
            if (currentScroll > 50) {
                header.classList.add('et-header--scrolled');
            } else {
                header.classList.remove('et-header--scrolled');
            }
            
            // Hide/show on scroll direction
            if (currentScroll > lastScroll && currentScroll > 200) {
                header.classList.add('et-header--hidden');
            } else {
                header.classList.remove('et-header--hidden');
            }
            
            lastScroll = currentScroll;
        }
    }
    
    /**
     * Back to Top Button
     */
    function initBackToTop() {
        const backToTop = document.querySelector('.et-footer__back-to-top');
        if (!backToTop) return;
        
        backToTop.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }
    
    /**
     * Smooth Scroll for Anchor Links
     */
    function initSmoothScroll() {
        document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
            anchor.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                if (href === '#') return;
                
                const target = document.querySelector(href);
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    }
    
})();
