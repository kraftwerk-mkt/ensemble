/**
 * Ensemble Sponsors Frontend
 * 
 * Handles carousel functionality
 * 
 * @package Ensemble
 * @subpackage Addons/Sponsors
 * @version 1.0.0
 */

(function() {
    'use strict';

    // Default settings (can be overridden by data attributes or esSponsors object)
    var defaults = {
        autoplay: true,
        speed: 3000,
        pauseHover: true,
        loop: true
    };

    // Get settings from localized script or use defaults
    var settings = typeof esSponsors !== 'undefined' ? esSponsors : defaults;

    /**
     * Initialize carousel
     */
    function initCarousel(carousel) {
        var track = carousel.querySelector('.es-sponsors__track');
        var slides = carousel.querySelectorAll('.es-sponsors__slide');
        var prevBtn = carousel.querySelector('.es-sponsors__nav--prev');
        var nextBtn = carousel.querySelector('.es-sponsors__nav--next');
        
        if (!track || slides.length === 0) return;

        var currentIndex = 0;
        var slideWidth = 0;
        var visibleSlides = 4;
        var maxIndex = 0;
        var autoplayInterval = null;
        var isPaused = false;

        // Get autoplay settings from data attributes
        var autoplay = carousel.dataset.autoplay !== 'false' && settings.autoplay;
        var speed = parseInt(carousel.dataset.speed, 10) || settings.speed;

        // Calculate dimensions
        function calculateDimensions() {
            if (slides.length === 0) return;
            
            slideWidth = slides[0].offsetWidth;
            var containerWidth = carousel.offsetWidth;
            
            // Calculate visible slides based on container width
            if (containerWidth < 480) {
                visibleSlides = 1;
            } else if (containerWidth < 768) {
                visibleSlides = 2;
            } else if (containerWidth < 1024) {
                visibleSlides = 3;
            } else {
                visibleSlides = 4;
            }
            
            maxIndex = Math.max(0, slides.length - visibleSlides);
            
            // Clamp current index
            if (currentIndex > maxIndex) {
                currentIndex = maxIndex;
                updatePosition(false);
            }
        }

        // Update track position
        function updatePosition(animate) {
            if (animate !== false) {
                track.style.transition = 'transform 0.5s ease';
            } else {
                track.style.transition = 'none';
            }
            track.style.transform = 'translateX(-' + (currentIndex * slideWidth) + 'px)';
        }

        // Go to slide
        function goToSlide(index) {
            if (settings.loop) {
                if (index > maxIndex) {
                    currentIndex = 0;
                } else if (index < 0) {
                    currentIndex = maxIndex;
                } else {
                    currentIndex = index;
                }
            } else {
                currentIndex = Math.max(0, Math.min(index, maxIndex));
            }
            updatePosition();
        }

        // Next slide
        function nextSlide() {
            goToSlide(currentIndex + 1);
        }

        // Previous slide
        function prevSlide() {
            goToSlide(currentIndex - 1);
        }

        // Start autoplay
        function startAutoplay() {
            if (!autoplay || autoplayInterval) return;
            
            autoplayInterval = setInterval(function() {
                if (!isPaused) {
                    nextSlide();
                }
            }, speed);
        }

        // Stop autoplay
        function stopAutoplay() {
            if (autoplayInterval) {
                clearInterval(autoplayInterval);
                autoplayInterval = null;
            }
        }

        // Event listeners
        if (prevBtn) {
            prevBtn.addEventListener('click', function() {
                prevSlide();
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', function() {
                nextSlide();
            });
        }

        // Pause on hover
        if (settings.pauseHover) {
            carousel.addEventListener('mouseenter', function() {
                isPaused = true;
            });
            
            carousel.addEventListener('mouseleave', function() {
                isPaused = false;
            });
        }

        // Touch support
        var touchStartX = 0;
        var touchEndX = 0;

        carousel.addEventListener('touchstart', function(e) {
            touchStartX = e.changedTouches[0].screenX;
            isPaused = true;
        }, { passive: true });

        carousel.addEventListener('touchend', function(e) {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
            isPaused = false;
        }, { passive: true });

        function handleSwipe() {
            var diff = touchStartX - touchEndX;
            if (Math.abs(diff) > 50) {
                if (diff > 0) {
                    nextSlide();
                } else {
                    prevSlide();
                }
            }
        }

        // Resize handler
        var resizeTimeout;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(function() {
                calculateDimensions();
                updatePosition(false);
            }, 100);
        });

        // Initialize
        calculateDimensions();
        startAutoplay();
    }

    /**
     * Initialize all carousels on page
     */
    function initAll() {
        var carousels = document.querySelectorAll('.es-sponsors__carousel');
        carousels.forEach(function(carousel) {
            initCarousel(carousel);
        });
    }

    // Run on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }

})();
