<?php
/**
 * Club Layout - Hero Slide Template
 * 
 * Dark, nightlife style:
 * Title → Meta (Date, Time, Location, Price)
 * 
 * @package Ensemble
 * @layout Club
 * 
 * Available variables:
 * - $event (array) - Event data
 * - $atts (array) - Shortcode attributes
 */

if (!defined('ABSPATH')) exit;

// Check for video
$has_video = !empty($event['hero_video_url']);
$video_embed_url = '';
$video_type = '';

if ($has_video) {
    $video_url = $event['hero_video_url'];
    $autoplay = isset($event['hero_video_autoplay']) && $event['hero_video_autoplay'] ? '1' : '0';
    $loop = isset($event['hero_video_loop']) && $event['hero_video_loop'] ? '1' : '0';
    $controls = isset($event['hero_video_controls']) && $event['hero_video_controls'] ? '1' : '0';
    
    if (preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $video_url, $matches)) {
        $video_type = 'youtube';
        $params = array(
            'autoplay' => $autoplay,
            'mute' => '1',
            'loop' => $loop,
            'controls' => $controls,
            'playlist' => $matches[1],
            'rel' => '0',
            'modestbranding' => '1',
        );
        $video_embed_url = 'https://www.youtube.com/embed/' . $matches[1] . '?' . http_build_query($params);
    } elseif (preg_match('/vimeo\.com\/(?:video\/)?(\d+)/', $video_url, $matches)) {
        $video_type = 'vimeo';
        $params = array(
            'autoplay' => $autoplay,
            'muted' => '1',
            'loop' => $loop,
            'controls' => $controls,
        );
        $video_embed_url = 'https://player.vimeo.com/video/' . $matches[1] . '?' . http_build_query($params);
    } elseif (preg_match('/\.(mp4|webm)$/i', $video_url)) {
        $video_type = 'direct';
    }
}
?>
<div class="es-hero-slide es-hero-slide--club<?php echo $has_video ? ' es-hero-slide--has-video' : ''; ?>">
    <?php if ($has_video && $video_type === 'direct'): ?>
        <video class="es-hero-slide__video"
               <?php echo $event['hero_video_autoplay'] ? 'autoplay' : ''; ?>
               <?php echo $event['hero_video_loop'] ? 'loop' : ''; ?>
               <?php echo $event['hero_video_controls'] ? 'controls' : ''; ?>
               muted playsinline>
            <source src="<?php echo esc_url($event['hero_video_url']); ?>" type="video/<?php echo pathinfo($event['hero_video_url'], PATHINFO_EXTENSION); ?>">
        </video>
        <?php if (!empty($event['featured_image'])): ?>
            <img src="<?php echo esc_url($event['featured_image']); ?>" alt="" class="es-hero-slide__image es-hero-slide__image--fallback" loading="lazy">
        <?php endif; ?>
    <?php elseif ($has_video && $video_embed_url): ?>
        <div class="es-hero-slide__video-wrapper">
            <iframe src="<?php echo esc_url($video_embed_url); ?>" class="es-hero-slide__video-iframe" frameborder="0" allow="autoplay; fullscreen" allowfullscreen></iframe>
        </div>
        <?php if (!empty($event['featured_image'])): ?>
            <img src="<?php echo esc_url($event['featured_image']); ?>" alt="" class="es-hero-slide__image es-hero-slide__image--fallback" loading="lazy">
        <?php endif; ?>
    <?php elseif (!empty($event['featured_image'])): ?>
        <img src="<?php echo esc_url($event['featured_image']); ?>" alt="<?php echo esc_attr($event['title']); ?>" class="es-hero-slide__image" loading="lazy">
    <?php else: ?>
        <div class="es-hero-slide__image es-hero-slide__image--placeholder"></div>
    <?php endif; ?>
    
    <div class="es-hero-slide__content">
        <h2 class="es-hero-slide__title">
            <a href="<?php echo esc_url($event['permalink']); ?>">
                <?php echo esc_html($event['title']); ?>
            </a>
        </h2>
        
        <div class="es-hero-slide__meta">
            <?php if (!empty($event['start_date'])): ?>
                <span class="es-hero-slide__meta-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($event['start_date']))); ?>
                </span>
            <?php endif; ?>
            
            <?php if (!empty($event['start_time'])): ?>
                <span class="es-hero-slide__meta-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    <?php echo esc_html($event['start_time']); ?>
                </span>
            <?php endif; ?>
            
            <?php if (!empty($event['location'])): ?>
                <span class="es-hero-slide__meta-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                        <circle cx="12" cy="10" r="3"></circle>
                    </svg>
                    <?php echo esc_html($event['location']); ?>
                </span>
            <?php endif; ?>
            
            <?php if (!empty($event['price'])): ?>
                <span class="es-hero-slide__meta-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="1" x2="12" y2="23"></line>
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                    </svg>
                    <?php echo esc_html($event['price']); ?>
                </span>
            <?php endif; ?>
        </div>
        
        <div class="es-hero-slide__actions">
            <?php if ($atts['show_button']): ?>
                <a href="<?php echo esc_url($event['permalink']); ?>" 
                   class="es-hero-slide__btn es-hero-slide__btn--primary">
                    <?php echo esc_html($atts['button_text']); ?>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </a>
            <?php endif; ?>
            
            <?php if ($atts['show_ticket_button'] && !empty($event['ticket_url'])): ?>
                <a href="<?php echo esc_url($event['ticket_url']); ?>" 
                   class="es-hero-slide__btn es-hero-slide__btn--secondary"
                   target="_blank" rel="noopener noreferrer">
                    <?php echo esc_html($atts['ticket_button_text']); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>
