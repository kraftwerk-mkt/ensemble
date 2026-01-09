/**
 * Ensemble Slider Component
 * 
 * Lightweight, vanilla JS slider for Events, Artists, and Locations
 * Uses CSS scroll-snap for smooth, performant scrolling
 * 
 * @package Ensemble
 * @version 2.8.0
 */

(function() {
    'use strict';

    // ========================================
    // CONFIGURATION
    // ========================================
    
    const CONFIG = {
        selectors: {
            slider: '.es-slider',
            hero: '.es-hero-slider',
            track: '.es-slider__track',
            slide: '.es-slider__slide',
            prevBtn: '.es-slider__prev',
            nextBtn: '.es-slider__next',
            dots: '.es-slider__dots',
            dot: '.es-slider__dot',
            counter: '.es-slider__counter'
        },
        classes: {
            initialized: 'es-slider--initialized',
            dragging: 'es-slider--dragging',
            activeSlide: 'es-slider__slide--active',
            activeDot: 'es-slider__dot--active',
            disabled: 'es-slider__btn--disabled'
        },
        defaults: {
            autoplay: false,
            autoplaySpeed: 5000,
            loop: false,
            dots: true,
            arrows: true,
            slidesToShow: 3,
            slidesToScroll: 1,
            gap: 24,
            touchThreshold: 50,
            responsive: [
                { breakpoint: 1024, slidesToShow: 2 },
                { breakpoint: 640, slidesToShow: 1 }
            ]
        }
    };

    // ========================================
    // SLIDER CLASS
    // ========================================
    
    class EnsembleSlider {
        constructor(element, options = {}) {
            this.container = element;
            this.options = { ...CONFIG.defaults, ...options };
            
            // Parse data attributes
            this.parseDataAttributes();
            
            // Elements
            this.track = null;
            this.slides = [];
            this.prevBtn = null;
            this.nextBtn = null;
            this.dotsContainer = null;
            this.counterEl = null;
            
            // State
            this.currentIndex = 0;
            this.isDragging = false;
            this.startX = 0;
            this.scrollLeft = 0;
            this.autoplayTimer = null;
            this.slidesToShow = this.options.slidesToShow;
            
            this.init();
        }
        
        parseDataAttributes() {
            const data = this.container.dataset;
            
            if (data.autoplay !== undefined) this.options.autoplay = data.autoplay === 'true';
            if (data.autoplaySpeed) this.options.autoplaySpeed = parseInt(data.autoplaySpeed);
            if (data.loop !== undefined) this.options.loop = data.loop === 'true';
            if (data.dots !== undefined) this.options.dots = data.dots === 'true';
            if (data.arrows !== undefined) this.options.arrows = data.arrows === 'true';
            if (data.slidesToShow) this.options.slidesToShow = parseInt(data.slidesToShow);
            if (data.slidesToScroll) this.options.slidesToScroll = parseInt(data.slidesToScroll);
            if (data.gap) this.options.gap = parseInt(data.gap);
        }
        
        init() {
            // Prevent double initialization
            if (this.container.classList.contains(CONFIG.classes.initialized)) return;
            
            this.track = this.container.querySelector(CONFIG.selectors.track);
            if (!this.track) {
                // If no track, the container itself is the track
                this.wrapContent();
            }
            
            this.slides = Array.from(this.track.querySelectorAll(CONFIG.selectors.slide));
            if (this.slides.length === 0) return;
            
            // Reset scroll position to start
            this.track.scrollLeft = 0;
            
            this.setupArrows();
            this.setupDots();
            this.setupCounter();
            this.setupResponsive();
            this.bindEvents();
            this.updateUI();
            
            // Force scroll to first slide after a brief delay
            setTimeout(() => {
                this.track.scrollLeft = 0;
            }, 50);
            
            if (this.options.autoplay) {
                this.startAutoplay();
            }
            
            this.container.classList.add(CONFIG.classes.initialized);
        }
        
        wrapContent() {
            // Create track wrapper
            this.track = document.createElement('div');
            this.track.className = 'es-slider__track';
            
            // Move children into track
            while (this.container.firstChild) {
                const child = this.container.firstChild;
                if (child.nodeType === 1) {
                    child.classList.add('es-slider__slide');
                }
                this.track.appendChild(child);
            }
            
            this.container.appendChild(this.track);
        }
        
        setupArrows() {
            if (!this.options.arrows) return;
            
            // Check if arrows exist
            this.prevBtn = this.container.querySelector(CONFIG.selectors.prevBtn);
            this.nextBtn = this.container.querySelector(CONFIG.selectors.nextBtn);
            
            // Create if not exists
            if (!this.prevBtn) {
                this.prevBtn = document.createElement('button');
                this.prevBtn.className = 'es-slider__prev es-slider__arrow';
                this.prevBtn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg>';
                this.prevBtn.setAttribute('aria-label', 'Previous');
                this.container.appendChild(this.prevBtn);
            }
            
            if (!this.nextBtn) {
                this.nextBtn = document.createElement('button');
                this.nextBtn.className = 'es-slider__next es-slider__arrow';
                this.nextBtn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>';
                this.nextBtn.setAttribute('aria-label', 'Next');
                this.container.appendChild(this.nextBtn);
            }
        }
        
        setupDots() {
            if (!this.options.dots || this.slides.length <= this.slidesToShow) return;
            
            this.dotsContainer = this.container.querySelector(CONFIG.selectors.dots);
            
            if (!this.dotsContainer) {
                this.dotsContainer = document.createElement('div');
                this.dotsContainer.className = 'es-slider__dots';
                this.container.appendChild(this.dotsContainer);
            }
            
            // Calculate number of dots
            const dotCount = Math.ceil((this.slides.length - this.slidesToShow + 1) / this.options.slidesToScroll);
            
            this.dotsContainer.innerHTML = '';
            for (let i = 0; i < dotCount; i++) {
                const dot = document.createElement('button');
                dot.className = 'es-slider__dot';
                dot.setAttribute('aria-label', `Go to slide ${i + 1}`);
                dot.dataset.index = i;
                this.dotsContainer.appendChild(dot);
            }
        }
        
        setupCounter() {
            this.counterEl = this.container.querySelector(CONFIG.selectors.counter);
            // Counter is optional - used mainly for hero sliders
        }
        
        setupResponsive() {
            this.updateSlidesToShow();
            
            // Debounced resize handler
            let resizeTimer;
            window.addEventListener('resize', () => {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(() => {
                    this.updateSlidesToShow();
                    this.updateUI();
                }, 100);
            });
        }
        
        updateSlidesToShow() {
            const width = window.innerWidth;
            let slidesToShow = this.options.slidesToShow;
            
            // Sort responsive breakpoints descending
            const breakpoints = [...this.options.responsive].sort((a, b) => b.breakpoint - a.breakpoint);
            
            for (const bp of breakpoints) {
                if (width <= bp.breakpoint) {
                    slidesToShow = bp.slidesToShow;
                }
            }
            
            this.slidesToShow = slidesToShow;
            
            // Update CSS custom property for slide width
            const slideWidth = `calc((100% - ${this.options.gap * (this.slidesToShow - 1)}px) / ${this.slidesToShow})`;
            this.track.style.setProperty('--slide-width', slideWidth);
            this.track.style.setProperty('--slide-gap', `${this.options.gap}px`);
        }
        
        bindEvents() {
            // Arrow clicks
            if (this.prevBtn) {
                this.prevBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.prev();
                });
            }
            
            if (this.nextBtn) {
                this.nextBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.next();
                });
            }
            
            // Dot clicks
            if (this.dotsContainer) {
                this.dotsContainer.addEventListener('click', (e) => {
                    const dot = e.target.closest(CONFIG.selectors.dot);
                    if (dot) {
                        const index = parseInt(dot.dataset.index) * this.options.slidesToScroll;
                        this.goTo(index);
                    }
                });
            }
            
            // Touch/Mouse drag
            this.track.addEventListener('mousedown', (e) => this.startDrag(e));
            this.track.addEventListener('touchstart', (e) => this.startDrag(e), { passive: true });
            
            document.addEventListener('mousemove', (e) => this.drag(e));
            document.addEventListener('touchmove', (e) => this.drag(e), { passive: true });
            
            document.addEventListener('mouseup', () => this.endDrag());
            document.addEventListener('touchend', () => this.endDrag());
            
            // Scroll snap detection
            this.track.addEventListener('scroll', () => this.onScroll());
            
            // Keyboard navigation
            this.container.addEventListener('keydown', (e) => {
                if (e.key === 'ArrowLeft') this.prev();
                if (e.key === 'ArrowRight') this.next();
            });
            
            // Pause autoplay on hover
            if (this.options.autoplay) {
                this.container.addEventListener('mouseenter', () => this.stopAutoplay());
                this.container.addEventListener('mouseleave', () => this.startAutoplay());
            }
        }
        
        startDrag(e) {
            this.isDragging = true;
            this.container.classList.add(CONFIG.classes.dragging);
            this.startX = e.type === 'touchstart' ? e.touches[0].pageX : e.pageX;
            this.scrollLeft = this.track.scrollLeft;
            this.stopAutoplay();
        }
        
        drag(e) {
            if (!this.isDragging) return;
            
            const x = e.type === 'touchmove' ? e.touches[0].pageX : e.pageX;
            const walk = (x - this.startX);
            this.track.scrollLeft = this.scrollLeft - walk;
        }
        
        endDrag() {
            if (!this.isDragging) return;
            
            this.isDragging = false;
            this.container.classList.remove(CONFIG.classes.dragging);
            
            // Snap to nearest slide
            this.snapToNearest();
            
            if (this.options.autoplay) {
                this.startAutoplay();
            }
        }
        
        snapToNearest() {
            const slideWidth = this.slides[0].offsetWidth + this.options.gap;
            const nearestIndex = Math.round(this.track.scrollLeft / slideWidth);
            this.goTo(nearestIndex);
        }
        
        onScroll() {
            // Update current index based on scroll position
            const slideWidth = this.slides[0].offsetWidth + this.options.gap;
            const newIndex = Math.round(this.track.scrollLeft / slideWidth);
            
            if (newIndex !== this.currentIndex) {
                this.currentIndex = Math.max(0, Math.min(newIndex, this.slides.length - this.slidesToShow));
                this.updateUI();
            }
        }
        
        prev() {
            const newIndex = this.currentIndex - this.options.slidesToScroll;
            
            if (newIndex < 0 && this.options.loop) {
                this.goTo(this.slides.length - this.slidesToShow);
            } else {
                this.goTo(Math.max(0, newIndex));
            }
        }
        
        next() {
            const maxIndex = this.slides.length - this.slidesToShow;
            const newIndex = this.currentIndex + this.options.slidesToScroll;
            
            if (newIndex > maxIndex && this.options.loop) {
                this.goTo(0);
            } else {
                this.goTo(Math.min(maxIndex, newIndex));
            }
        }
        
        goTo(index) {
            this.currentIndex = Math.max(0, Math.min(index, this.slides.length - this.slidesToShow));
            
            const slideWidth = this.slides[0].offsetWidth + this.options.gap;
            const scrollPosition = this.currentIndex * slideWidth;
            
            this.track.scrollTo({
                left: scrollPosition,
                behavior: 'smooth'
            });
            
            this.updateUI();
        }
        
        updateUI() {
            // Update arrows
            if (this.prevBtn) {
                const atStart = this.currentIndex === 0;
                this.prevBtn.disabled = atStart && !this.options.loop;
                this.prevBtn.classList.toggle(CONFIG.classes.disabled, atStart && !this.options.loop);
            }
            
            if (this.nextBtn) {
                const atEnd = this.currentIndex >= this.slides.length - this.slidesToShow;
                this.nextBtn.disabled = atEnd && !this.options.loop;
                this.nextBtn.classList.toggle(CONFIG.classes.disabled, atEnd && !this.options.loop);
            }
            
            // Update dots
            if (this.dotsContainer) {
                const dotIndex = Math.floor(this.currentIndex / this.options.slidesToScroll);
                const dots = this.dotsContainer.querySelectorAll(CONFIG.selectors.dot);
                dots.forEach((dot, i) => {
                    dot.classList.toggle(CONFIG.classes.activeDot, i === dotIndex);
                });
            }
            
            // Update counter
            if (this.counterEl) {
                const current = this.currentIndex + 1;
                const total = this.slides.length - this.slidesToShow + 1;
                this.counterEl.textContent = `${current} / ${total}`;
            }
            
            // Update slide active states
            this.slides.forEach((slide, i) => {
                const isActive = i >= this.currentIndex && i < this.currentIndex + this.slidesToShow;
                slide.classList.toggle(CONFIG.classes.activeSlide, isActive);
            });
        }
        
        startAutoplay() {
            if (!this.options.autoplay) return;
            
            this.stopAutoplay();
            this.autoplayTimer = setInterval(() => {
                this.next();
            }, this.options.autoplaySpeed);
        }
        
        stopAutoplay() {
            if (this.autoplayTimer) {
                clearInterval(this.autoplayTimer);
                this.autoplayTimer = null;
            }
        }
        
        destroy() {
            this.stopAutoplay();
            this.container.classList.remove(CONFIG.classes.initialized);
            // Remove event listeners would go here
        }
    }

    // ========================================
    // HERO SLIDER CLASS (extends base)
    // ========================================
    
    class EnsembleHeroSlider extends EnsembleSlider {
        constructor(element, options = {}) {
            const heroDefaults = {
                slidesToShow: 1,
                slidesToScroll: 1,
                autoplay: true,
                autoplaySpeed: 6000,
                loop: true,
                dots: true,
                arrows: true,
                responsive: [] // Hero is always 1 slide
            };
            
            super(element, { ...heroDefaults, ...options });
        }
        
        setupArrows() {
            super.setupArrows();
            
            // Hero arrows are larger and positioned differently
            if (this.prevBtn) this.prevBtn.classList.add('es-hero-slider__arrow');
            if (this.nextBtn) this.nextBtn.classList.add('es-hero-slider__arrow');
        }
        
        setupDots() {
            if (!this.options.dots) return;
            
            this.dotsContainer = this.container.querySelector(CONFIG.selectors.dots);
            
            if (!this.dotsContainer) {
                this.dotsContainer = document.createElement('div');
                this.dotsContainer.className = 'es-slider__dots es-hero-slider__dots';
                this.container.appendChild(this.dotsContainer);
            }
            
            this.dotsContainer.innerHTML = '';
            for (let i = 0; i < this.slides.length; i++) {
                const dot = document.createElement('button');
                dot.className = 'es-slider__dot es-hero-slider__dot';
                dot.setAttribute('aria-label', `Go to slide ${i + 1}`);
                dot.dataset.index = i;
                this.dotsContainer.appendChild(dot);
            }
        }
        
        updateUI() {
            super.updateUI();
            
            // Add progress indicator for autoplay
            if (this.options.autoplay && this.dotsContainer) {
                const activeDot = this.dotsContainer.querySelector(`.${CONFIG.classes.activeDot}`);
                if (activeDot) {
                    activeDot.style.setProperty('--autoplay-duration', `${this.options.autoplaySpeed}ms`);
                }
            }
        }
    }

    // ========================================
    // INITIALIZATION
    // ========================================
    
    function initSliders() {
        // Regular sliders
        document.querySelectorAll(CONFIG.selectors.slider).forEach(el => {
            if (!el.classList.contains(CONFIG.classes.initialized)) {
                new EnsembleSlider(el);
            }
        });
        
        // Hero sliders
        document.querySelectorAll(CONFIG.selectors.hero).forEach(el => {
            if (!el.classList.contains(CONFIG.classes.initialized)) {
                new EnsembleHeroSlider(el);
            }
        });
    }
    
    // Auto-init on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSliders);
    } else {
        initSliders();
    }
    
    // Re-init after AJAX (for compatibility with WordPress themes)
    if (typeof jQuery !== 'undefined') {
        jQuery(document).on('ajaxComplete', function() {
            setTimeout(initSliders, 100);
        });
    }
    
    // Expose to global scope for manual initialization
    window.EnsembleSlider = EnsembleSlider;
    window.EnsembleHeroSlider = EnsembleHeroSlider;
    window.initEnsembleSliders = initSliders;
    
})();
