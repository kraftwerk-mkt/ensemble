<?php
/**
 * Gallery Pro - Carousel Layout Template
 * 
 * Uses Swiper.js for carousel functionality
 * 
 * @package Ensemble
 * @subpackage Addons/Gallery Pro
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$swiper_id = $gallery_id . '-swiper';
?>
<div id="<?php echo esc_attr($gallery_id); ?>" 
     class="es-gallery es-gallery-carousel <?php echo esc_attr($extra_class); ?>" 
     data-layout="carousel">
    
    <!-- Main Swiper -->
    <div class="swiper es-gallery-swiper-main" id="<?php echo esc_attr($swiper_id); ?>">
        <div class="swiper-wrapper">
            <?php foreach ($items as $index => $item) : ?>
                <div class="swiper-slide es-gallery-slide es-gallery-item-<?php echo esc_attr($item['type']); ?>">
                    <?php if ($lightbox) : ?>
                        <a href="<?php echo esc_url($item['type'] === 'video' ? $item['embed_url'] : ($item['full'] ?? $item['url'])); ?>" 
                           class="es-gallery-link glightbox"
                           data-gallery="<?php echo esc_attr($gallery_id); ?>"
                           data-type="<?php echo $item['type'] === 'video' ? 'video' : 'image'; ?>"
                           <?php if ($item['type'] === 'video') : ?>
                               data-width="900px"
                               data-height="506px"
                           <?php endif; ?>
                           data-title="<?php echo esc_attr($item['title'] ?? ''); ?>"
                           data-description="<?php echo esc_attr($item['caption'] ?? ''); ?>">
                    <?php endif; ?>
                    
                    <div class="es-gallery-image-wrapper">
                        <?php if ($item['type'] === 'video') : ?>
                            <img src="<?php echo esc_url($item['thumb']); ?>" 
                                 alt="<?php echo esc_attr($item['title'] ?? __('Video', 'ensemble')); ?>"
                                 class="es-gallery-image"
                                 loading="lazy">
                            <div class="es-gallery-video-overlay es-gallery-video-overlay-large">
                                <svg class="es-gallery-play-icon" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M8 5v14l11-7z"/>
                                </svg>
                            </div>
                        <?php else : ?>
                            <img src="<?php echo esc_url($item['large'] ?? $item['url']); ?>" 
                                 alt="<?php echo esc_attr($item['alt'] ?? $item['title']); ?>"
                                 class="es-gallery-image"
                                 loading="lazy">
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($show_captions && (!empty($item['title']) || !empty($item['caption']))) : ?>
                        <div class="es-gallery-slide-caption">
                            <?php if (!empty($item['title'])) : ?>
                                <h4 class="es-gallery-caption-title"><?php echo esc_html($item['title']); ?></h4>
                            <?php endif; ?>
                            <?php if (!empty($item['caption'])) : ?>
                                <p class="es-gallery-caption-text"><?php echo esc_html($item['caption']); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($lightbox) : ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Navigation -->
        <div class="swiper-button-prev es-gallery-nav es-gallery-nav-prev"></div>
        <div class="swiper-button-next es-gallery-nav es-gallery-nav-next"></div>
        
        <!-- Pagination -->
        <div class="swiper-pagination es-gallery-pagination"></div>
    </div>
    
    <!-- Thumbnails -->
    <?php if (count($items) > 1) : ?>
        <div class="swiper es-gallery-swiper-thumbs" id="<?php echo esc_attr($swiper_id); ?>-thumbs">
            <div class="swiper-wrapper">
                <?php foreach ($items as $index => $item) : ?>
                    <div class="swiper-slide es-gallery-thumb">
                        <img src="<?php echo esc_url($item['thumb'] ?? $item['url']); ?>" 
                             alt="<?php echo esc_attr($item['alt'] ?? 'Thumbnail'); ?>"
                             loading="lazy">
                        <?php if ($item['type'] === 'video') : ?>
                            <div class="es-gallery-thumb-video">
                                <svg viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M8 5v14l11-7z"/>
                                </svg>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Counter -->
    <div class="es-gallery-slide-counter">
        <span class="es-gallery-current">1</span>
        <span class="es-gallery-separator">/</span>
        <span class="es-gallery-total"><?php echo count($items); ?></span>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof Swiper === 'undefined') return;
    
    var galleryId = '<?php echo esc_js($swiper_id); ?>';
    
    // Thumbnails Swiper
    var thumbsSwiper = new Swiper('#' + galleryId + '-thumbs', {
        spaceBetween: 10,
        slidesPerView: 4,
        freeMode: true,
        watchSlidesProgress: true,
        breakpoints: {
            640: { slidesPerView: 5 },
            768: { slidesPerView: 6 },
            1024: { slidesPerView: 8 }
        }
    });
    
    // Main Swiper
    var mainSwiper = new Swiper('#' + galleryId, {
        spaceBetween: 0,
        navigation: {
            nextEl: '#' + galleryId + ' .swiper-button-next',
            prevEl: '#' + galleryId + ' .swiper-button-prev',
        },
        pagination: {
            el: '#' + galleryId + ' .swiper-pagination',
            clickable: true,
        },
        thumbs: {
            swiper: thumbsSwiper,
        },
        autoplay: ensembleGalleryPro.carousel.autoplay ? {
            delay: ensembleGalleryPro.carousel.delay,
            disableOnInteraction: false,
        } : false,
        loop: ensembleGalleryPro.carousel.loop,
        on: {
            slideChange: function() {
                var counter = document.querySelector('#<?php echo esc_js($gallery_id); ?> .es-gallery-current');
                if (counter) {
                    counter.textContent = this.realIndex + 1;
                }
            }
        }
    });
});
</script>
