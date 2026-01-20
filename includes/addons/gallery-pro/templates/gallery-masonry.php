<?php
/**
 * Gallery Pro - Masonry Layout Template
 * 
 * Supports images and videos (YouTube, Vimeo, Self-hosted)
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
     class="es-gallery es-gallery-masonry <?php echo esc_attr($extra_class); ?>" 
     data-layout="masonry"
     data-columns="<?php echo esc_attr($columns); ?>">
    
    <div class="es-gallery-items es-masonry-grid" style="--es-gallery-columns: <?php echo esc_attr($columns); ?>">
        <?php foreach ($items as $index => $item) : ?>
            <?php 
            $is_video = isset($item['type']) && $item['type'] === 'video';
            $is_local_video = $is_video && isset($item['provider']) && $item['provider'] === 'local';
            ?>
            <div class="es-gallery-item es-masonry-item es-gallery-item-<?php echo esc_attr($item['type'] ?? 'image'); ?>">
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
                                 class="es-gallery-image"
                                 loading="lazy">
                        <?php else : ?>
                            <div class="es-gallery-video-placeholder">
                                <svg viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M8 5v14l11-7z"/>
                                </svg>
                            </div>
                        <?php endif; ?>
                        
                        <div class="es-gallery-video-overlay">
                            <div class="es-gallery-play-button">
                                <svg viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M8 5v14l11-7z"/>
                                </svg>
                            </div>
                            <?php if (isset($item['provider']) && $item['provider'] !== 'local') : ?>
                                <span class="es-gallery-video-provider es-provider-<?php echo esc_attr($item['provider']); ?>">
                                    <?php echo esc_html(ucfirst($item['provider'])); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php else : ?>
                        <img src="<?php echo esc_url($item['large'] ?? $item['url']); ?>" 
                             alt="<?php echo esc_attr($item['alt'] ?? $item['title']); ?>"
                             class="es-gallery-image"
                             loading="lazy">
                        
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
                    <?php endif; ?>
                </div>
                
                <?php if ($show_captions && (!empty($item['title']) || !empty($item['caption']))) : ?>
                    <div class="es-gallery-caption">
                        <?php if (!empty($item['title'])) : ?>
                            <span class="es-gallery-caption-title"><?php echo esc_html($item['title']); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($item['caption'])) : ?>
                            <span class="es-gallery-caption-text"><?php echo esc_html($item['caption']); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($lightbox) : ?>
                    </a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
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
