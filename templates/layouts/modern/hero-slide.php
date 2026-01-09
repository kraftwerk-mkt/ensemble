<?php
/**
 * Modern Layout - Hero Slide Template
 * 
 * Clean, modern design (default):
 * Badge/Category → Title → Meta → Excerpt → Buttons
 * 
 * @package Ensemble
 * @layout Modern
 * 
 * Available variables:
 * - $event (array) - Event data
 * - $atts (array) - Shortcode attributes
 */

if (!defined('ABSPATH')) exit;

// Get badge text
$badge_text = '';
$is_badge = false;

if (!empty($event['badge_custom'])) {
    $badge_text = $event['badge_custom'];
    $is_badge = true;
} elseif (!empty($event['badge']) && $event['badge'] !== 'none' && $event['badge'] !== '' && $event['badge'] !== 'show_category') {
    $badge_labels = array(
        'sold_out' => __('Sold Out', 'ensemble'),
        'few_tickets' => __('Few Tickets', 'ensemble'),
        'free' => __('Free Entry', 'ensemble'),
        'new' => __('New', 'ensemble'),
        'premiere' => __('Premiere', 'ensemble'),
        'last_show' => __('Last Show', 'ensemble'),
        'special' => __('Special Event', 'ensemble'),
    );
    if (isset($badge_labels[$event['badge']])) {
        $badge_text = $badge_labels[$event['badge']];
        $is_badge = true;
    }
} elseif (!empty($event['badge']) && $event['badge'] === 'show_category' && !empty($event['categories'])) {
    $category = is_array($event['categories']) ? $event['categories'][0] : $event['categories'];
    $badge_text = is_object($category) ? $category->name : $category;
}

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
<div class="es-hero-slide<?php echo $has_video ? ' es-hero-slide--has-video' : ''; ?>">
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
        <?php if (!empty($badge_text)): ?>
            <span class="es-hero-slide__category<?php echo $is_badge ? ' es-hero-slide__badge' : ''; ?>">
                <?php echo esc_html($badge_text); ?>
            </span>
        <?php endif; ?>
        
        <?php if ($atts['show_date'] && !empty($event['start_date'])): ?>
            <div class="es-hero-slide__date-above">
                <?php echo esc_html(date_i18n('l, j. F Y', strtotime($event['start_date']))); ?>
            </div>
        <?php endif; ?>
        
        <h2 class="es-hero-slide__title">
            <a href="<?php echo esc_url($event['permalink']); ?>">
                <?php echo esc_html($event['title']); ?>
            </a>
        </h2>
        
        <?php if ($atts['show_location'] && !empty($event['location'])): ?>
            <div class="es-hero-slide__location-below">
                <?php echo esc_html($event['location']); ?>
            </div>
        <?php endif; ?>
        
        <div class="es-hero-slide__meta">
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
        
        <?php if ($atts['show_excerpt'] && !empty($event['excerpt'])): ?>
            <div class="es-hero-slide__excerpt">
                <?php echo wp_trim_words($event['excerpt'], 25, '...'); ?>
            </div>
        <?php endif; ?>
        
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
