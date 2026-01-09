<?php
/**
 * Gallery Pro - Justified Layout Template
 * 
 * CSS flexbox-based justified gallery
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
     class="es-gallery es-gallery-justified <?php echo esc_attr($extra_class); ?>" 
     data-layout="justified">
    
    <div class="es-gallery-justified-container">
        <?php foreach ($items as $index => $item) : 
            // Calculate aspect ratio for justified layout
            $width = $item['width'] ?? 400;
            $height = $item['height'] ?? 300;
            $ratio = $width / max($height, 1);
            $flex_grow = max(1, min($ratio * 100, 300));
        ?>
            <div class="es-gallery-justified-item es-gallery-item-<?php echo esc_attr($item['type']); ?>" 
                 style="flex-grow: <?php echo esc_attr($flex_grow); ?>">
                
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
                
                <i style="padding-bottom: <?php echo esc_attr((1 / $ratio) * 100); ?>%"></i>
                
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
                    
                    <?php if ($show_captions && (!empty($item['title']) || !empty($item['caption']))) : ?>
                        <div class="es-gallery-caption es-gallery-caption-overlay">
                            <?php if (!empty($item['title'])) : ?>
                                <span class="es-gallery-caption-title"><?php echo esc_html($item['title']); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($item['caption'])) : ?>
                                <span class="es-gallery-caption-text"><?php echo esc_html($item['caption']); ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($lightbox) : ?>
                    </a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        
        <!-- Spacer items for last row -->
        <div class="es-gallery-justified-spacer" style="flex-grow: 999999"></div>
    </div>
</div>
