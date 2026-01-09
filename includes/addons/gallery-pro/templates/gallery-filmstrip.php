<?php
/**
 * Gallery Pro - Filmstrip Layout Template
 * 
 * Horizontal scrolling filmstrip style gallery
 * 
 * @package Ensemble
 * @subpackage Addons/Gallery Pro
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>
<div id="<?php echo esc_attr($gallery_id); ?>" 
     class="es-gallery es-gallery-filmstrip <?php echo esc_attr($extra_class); ?>" 
     data-layout="filmstrip">
    
    <div class="es-gallery-filmstrip-wrapper">
        <!-- Left scroll button -->
        <button type="button" class="es-gallery-filmstrip-nav es-gallery-filmstrip-prev" aria-label="<?php esc_attr_e('Previous images', 'ensemble'); ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M15 18l-6-6 6-6"/>
            </svg>
        </button>
        
        <div class="es-gallery-filmstrip-container">
            <div class="es-gallery-filmstrip-track">
                <?php foreach ($items as $index => $item) : ?>
                    <div class="es-gallery-filmstrip-item es-gallery-item-<?php echo esc_attr($item['type']); ?>">
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
                                <div class="es-gallery-video-overlay">
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
                            
                            <?php if ($lightbox) : ?>
                                <div class="es-gallery-overlay">
                                    <svg class="es-gallery-zoom-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="11" cy="11" r="8"/>
                                        <path d="m21 21-4.35-4.35"/>
                                        <path d="M11 8v6"/>
                                        <path d="M8 11h6"/>
                                    </svg>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Frame number like real film -->
                            <span class="es-gallery-filmstrip-frame"><?php echo str_pad($index + 1, 2, '0', STR_PAD_LEFT); ?></span>
                        </div>
                        
                        <?php if ($show_captions && !empty($item['title'])) : ?>
                            <div class="es-gallery-filmstrip-caption">
                                <?php echo esc_html($item['title']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($lightbox) : ?>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Right scroll button -->
        <button type="button" class="es-gallery-filmstrip-nav es-gallery-filmstrip-next" aria-label="<?php esc_attr_e('Next images', 'ensemble'); ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 18l6-6-6-6"/>
            </svg>
        </button>
    </div>
    
    <!-- Film perforations decoration -->
    <div class="es-gallery-filmstrip-perforations es-gallery-filmstrip-perforations-top"></div>
    <div class="es-gallery-filmstrip-perforations es-gallery-filmstrip-perforations-bottom"></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var gallery = document.getElementById('<?php echo esc_js($gallery_id); ?>');
    if (!gallery) return;
    
    var container = gallery.querySelector('.es-gallery-filmstrip-container');
    var track = gallery.querySelector('.es-gallery-filmstrip-track');
    var prevBtn = gallery.querySelector('.es-gallery-filmstrip-prev');
    var nextBtn = gallery.querySelector('.es-gallery-filmstrip-next');
    
    var scrollAmount = 300;
    
    function updateNavVisibility() {
        prevBtn.style.opacity = container.scrollLeft <= 0 ? '0.3' : '1';
        nextBtn.style.opacity = container.scrollLeft >= track.scrollWidth - container.clientWidth - 10 ? '0.3' : '1';
    }
    
    prevBtn.addEventListener('click', function() {
        container.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
    });
    
    nextBtn.addEventListener('click', function() {
        container.scrollBy({ left: scrollAmount, behavior: 'smooth' });
    });
    
    container.addEventListener('scroll', updateNavVisibility);
    updateNavVisibility();
    
    // Touch/drag scrolling
    var isDown = false;
    var startX;
    var scrollLeft;
    
    container.addEventListener('mousedown', function(e) {
        isDown = true;
        startX = e.pageX - container.offsetLeft;
        scrollLeft = container.scrollLeft;
    });
    
    container.addEventListener('mouseleave', function() { isDown = false; });
    container.addEventListener('mouseup', function() { isDown = false; });
    
    container.addEventListener('mousemove', function(e) {
        if (!isDown) return;
        e.preventDefault();
        var x = e.pageX - container.offsetLeft;
        var walk = (x - startX) * 2;
        container.scrollLeft = scrollLeft - walk;
    });
});
</script>
