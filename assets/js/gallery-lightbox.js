/**
 * Ensemble Gallery Lightbox
 * 
 * Simple lightbox for gallery images
 * 
 * @package Ensemble
 * @version 2.6.0
 */

(function() {
    'use strict';

    // Create lightbox elements
    function createLightbox() {
        if (document.getElementById('es-lightbox')) return;
        
        var lightbox = document.createElement('div');
        lightbox.id = 'es-lightbox';
        lightbox.className = 'es-lightbox';
        lightbox.innerHTML = `
            <button class="es-lightbox__close" aria-label="Close">&times;</button>
            <button class="es-lightbox__nav es-lightbox__nav--prev" aria-label="Previous">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="m15 18-6-6 6-6"/>
                </svg>
            </button>
            <button class="es-lightbox__nav es-lightbox__nav--next" aria-label="Next">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="m9 18 6-6-6 6"/>
                </svg>
            </button>
            <div class="es-lightbox__content">
                <img class="es-lightbox__image" src="" alt="">
                <div class="es-lightbox__caption"></div>
            </div>
            <div class="es-lightbox__counter"></div>
        `;
        
        document.body.appendChild(lightbox);
        return lightbox;
    }

    // Gallery state
    var currentGallery = null;
    var currentIndex = 0;
    var images = [];

    // Open lightbox
    function openLightbox(gallery, index) {
        var lightbox = document.getElementById('es-lightbox') || createLightbox();
        
        currentGallery = gallery;
        currentIndex = index;
        images = [];
        
        // Collect all images from this gallery
        var items = gallery.querySelectorAll('.es-gallery__item');
        items.forEach(function(item, i) {
            var link = item.querySelector('.es-gallery__link');
            if (link) {
                images.push({
                    src: link.href,
                    caption: link.getAttribute('data-caption') || ''
                });
            }
        });
        
        showImage(index);
        lightbox.classList.add('is-active');
        document.body.style.overflow = 'hidden';
    }

    // Close lightbox
    function closeLightbox() {
        var lightbox = document.getElementById('es-lightbox');
        if (lightbox) {
            lightbox.classList.remove('is-active');
            document.body.style.overflow = '';
        }
    }

    // Show specific image
    function showImage(index) {
        if (index < 0) index = images.length - 1;
        if (index >= images.length) index = 0;
        
        currentIndex = index;
        
        var lightbox = document.getElementById('es-lightbox');
        var img = lightbox.querySelector('.es-lightbox__image');
        var caption = lightbox.querySelector('.es-lightbox__caption');
        var counter = lightbox.querySelector('.es-lightbox__counter');
        
        img.src = images[index].src;
        caption.textContent = images[index].caption;
        counter.textContent = (index + 1) + ' / ' + images.length;
        
        // Show/hide caption
        caption.style.display = images[index].caption ? 'block' : 'none';
    }

    // Navigate
    function navigate(direction) {
        showImage(currentIndex + direction);
    }

    // Initialize
    function init() {
        // Create lightbox on page load
        createLightbox();
        
        // Click on gallery image
        document.addEventListener('click', function(e) {
            var link = e.target.closest('.es-gallery--lightbox .es-gallery__link');
            if (link) {
                e.preventDefault();
                var gallery = link.closest('.es-gallery');
                var item = link.closest('.es-gallery__item');
                var index = parseInt(item.getAttribute('data-index'), 10) || 0;
                openLightbox(gallery, index);
            }
            
            // Close button
            if (e.target.closest('.es-lightbox__close')) {
                closeLightbox();
            }
            
            // Navigate buttons
            if (e.target.closest('.es-lightbox__nav--prev')) {
                navigate(-1);
            }
            if (e.target.closest('.es-lightbox__nav--next')) {
                navigate(1);
            }
            
            // Click on backdrop
            if (e.target.classList.contains('es-lightbox')) {
                closeLightbox();
            }
        });
        
        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
            var lightbox = document.getElementById('es-lightbox');
            if (!lightbox || !lightbox.classList.contains('is-active')) return;
            
            if (e.key === 'Escape') {
                closeLightbox();
            } else if (e.key === 'ArrowLeft') {
                navigate(-1);
            } else if (e.key === 'ArrowRight') {
                navigate(1);
            }
        });
    }

    // Run on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
