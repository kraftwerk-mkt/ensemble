<?php
/**
 * Gallery Pro - Filmstrip Layout Template
 * 
 * Supports images and videos (YouTube, Vimeo, Self-hosted)
 * Horizontal scrolling filmstrip layout
 * 
 * @package Ensemble
 * @subpackage Addons/Gallery Pro
 * @since 3.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>
<div id="<?php echo esc_attr($gallery_id); ?>" 
     class="es-gallery es-gallery-filmstrip <?php echo esc_attr($extra_class); ?>" 
     data-layout="filmstrip">
    
    <div class="es-filmstrip-wrapper">
        <button class="es-filmstrip-nav es-filmstrip-prev" aria-label="<?php esc_attr_e('Previous', 'ensemble'); ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M15 18l-6-6 6-6"/>
            </svg>
        </button>
        
        <div class="es-filmstrip-track">
            <div class="es-gallery-items es-filmstrip-items">
                <?php foreach ($items as $index => $item) : ?>
                    <?php 
                    $is_video = isset($item['type']) && $item['type'] === 'video';
                    $is_local_video = $is_video && isset($item['provider']) && $item['provider'] === 'local';
                    ?>
                    <div class="es-gallery-item es-filmstrip-item es-gallery-item-<?php echo esc_attr($item['type'] ?? 'image'); ?>">
                        <?php if ($lightbox) : ?>
                            <?php if ($is_video && !$is_local_video) : ?>
                                <a href="<?php echo esc_url($item['embed_url']); ?>" 
                                   class="es-gallery-link glightbox"
                                   data-gallery="<?php echo esc_attr($gallery_id); ?>"
                                   data-type="video"
                                   data-width="900px"
                                   data-height="506px"
                                   data-title="<?php echo esc_attr($item['title'] ?? ''); ?>">
                            <?php elseif ($is_local_video) : ?>
                                <a href="<?php echo esc_url($item['url']); ?>" 
                                   class="es-gallery-link glightbox"
                                   data-gallery="<?php echo esc_attr($gallery_id); ?>"
                                   data-type="video"
                                   data-title="<?php echo esc_attr($item['title'] ?? ''); ?>">
                            <?php else : ?>
                                <a href="<?php echo esc_url($item['full'] ?? $item['url']); ?>" 
                                   class="es-gallery-link glightbox"
                                   data-gallery="<?php echo esc_attr($gallery_id); ?>"
                                   data-type="image"
                                   data-title="<?php echo esc_attr($item['title'] ?? ''); ?>">
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <div class="es-gallery-image-wrapper">
                            <?php if ($is_video) : ?>
                                <?php if (!empty($item['thumb'])) : ?>
                                    <img src="<?php echo esc_url($item['thumb']); ?>" 
                                         alt="<?php echo esc_attr($item['title'] ?? __('Video', 'ensemble')); ?>"
                                         class="es-gallery-image">
                                <?php else : ?>
                                    <div class="es-gallery-video-placeholder">
                                        <svg viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M8 5v14l11-7z"/>
                                        </svg>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="es-gallery-video-overlay">
                                    <div class="es-gallery-play-button es-gallery-play-button-small">
                                        <svg viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M8 5v14l11-7z"/>
                                        </svg>
                                    </div>
                                </div>
                            <?php else : ?>
                                <img src="<?php echo esc_url($item['thumb'] ?? $item['url']); ?>" 
                                     alt="<?php echo esc_attr($item['alt'] ?? $item['title']); ?>"
                                     class="es-gallery-image">
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($lightbox) : ?>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <button class="es-filmstrip-nav es-filmstrip-next" aria-label="<?php esc_attr_e('Next', 'ensemble'); ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 18l6-6-6-6"/>
            </svg>
        </button>
    </div>
    
    <?php 
    $image_count = count(array_filter($items, function($item) { 
        return !isset($item['type']) || $item['type'] === 'image'; 
    }));
    $video_count = count(array_filter($items, function($item) { 
        return isset($item['type']) && $item['type'] === 'video'; 
    }));
    ?>
    <div class="es-gallery-counter">
        <?php if ($image_count > 0) : ?>
            <span class="es-gallery-count-images">
                <?php printf(_n('%d image', '%d images', $image_count, 'ensemble'), $image_count); ?>
            </span>
        <?php endif; ?>
        <?php if ($video_count > 0) : ?>
            <span class="es-gallery-count-videos">
                <?php printf(_n('%d video', '%d videos', $video_count, 'ensemble'), $video_count); ?>
            </span>
        <?php endif; ?>
    </div>
</div>
