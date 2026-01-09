<?php
/**
 * Single Artist Template - STAGE LAYOUT
 * Bold, theatrical design with sharp edges
 * 
 * @package Ensemble
 * @version 2.4.0
 */

if (!defined('ABSPATH')) exit;

get_header();

// Load styles
wp_enqueue_style('ensemble-base', ENSEMBLE_PLUGIN_URL . 'assets/css/layouts/ensemble-base.css', array(), ENSEMBLE_VERSION);
wp_enqueue_style('ensemble-layout-stage', ENSEMBLE_PLUGIN_URL . 'templates/layouts/stage/style.css', array('ensemble-base'), ENSEMBLE_VERSION);
wp_enqueue_style('ensemble-stage-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Oswald:wght@400;500;600;700&display=swap', array(), ENSEMBLE_VERSION);

$artist_id = get_the_ID();
$artist = function_exists('es_load_artist_data') ? es_load_artist_data($artist_id) : array();
$upcoming_events = function_exists('es_get_upcoming_events_by_artist') ? es_get_upcoming_events_by_artist($artist_id) : array();

// Social links
$social_links = array(
    'website' => !empty($artist['website']) ? $artist['website'] : '',
    'spotify' => !empty($artist['spotify']) ? $artist['spotify'] : '',
    'soundcloud' => !empty($artist['soundcloud']) ? $artist['soundcloud'] : '',
    'youtube' => !empty($artist['youtube']) ? $artist['youtube'] : '',
    'instagram' => !empty($artist['instagram']) ? $artist['instagram'] : '',
    'facebook' => !empty($artist['facebook']) ? $artist['facebook'] : '',
    'twitter' => !empty($artist['twitter']) ? $artist['twitter'] : '',
    'bandcamp' => !empty($artist['bandcamp']) ? $artist['bandcamp'] : '',
    'mixcloud' => !empty($artist['mixcloud']) ? $artist['mixcloud'] : '',
);
$has_social = !empty(array_filter($social_links));
?>

<div class="ensemble-single-artist-wrapper es-layout-stage es-stage-layout">
    
    <?php if (function_exists('ensemble_before_artist')) ensemble_before_artist($artist_id); ?>
    
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
    
    <article class="es-artist-stage">
        
        <!-- HERO -->
        <header class="es-stage-hero" style="height: 450px;">
            <?php if (has_post_thumbnail()): ?>
            <div class="es-stage-hero-image">
                <?php the_post_thumbnail('full'); ?>
            </div>
            <?php endif; ?>
            
            <div class="es-stage-hero-content">
                <?php if (!empty($artist['genre'])): ?>
                <div class="es-stage-hero-tag"><?php echo esc_html($artist['genre']); ?></div>
                <?php endif; ?>
                
                <h1 class="es-stage-hero-title"><?php the_title(); ?></h1>
                
                <?php if (!empty($artist['origin'])): ?>
                <div class="es-stage-hero-date">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="opacity: 0.8;">
                        <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                    </svg>
                    <span><?php echo esc_html($artist['origin']); ?></span>
                </div>
                <?php endif; ?>
                
                <!-- Social Links in Hero -->
                <?php if ($has_social): ?>
                <div class="es-stage-hero-social">
                    <?php if (!empty($social_links['spotify'])): ?>
                    <a href="<?php echo esc_url($social_links['spotify']); ?>" target="_blank" rel="noopener" title="Spotify" class="es-stage-social-btn">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.4 0 0 5.4 0 12s5.4 12 12 12 12-5.4 12-12S18.66 0 12 0zm5.521 17.34c-.24.359-.66.48-1.021.24-2.82-1.74-6.36-2.101-10.561-1.141-.418.122-.779-.179-.899-.539-.12-.421.18-.78.54-.9 4.56-1.021 8.52-.6 11.64 1.32.42.18.479.659.301 1.02zm1.44-3.3c-.301.42-.841.6-1.262.3-3.239-1.98-8.159-2.58-11.939-1.38-.479.12-1.02-.12-1.14-.6-.12-.48.12-1.021.6-1.141C9.6 9.9 15 10.561 18.72 12.84c.361.181.54.78.241 1.2zm.12-3.36C15.24 8.4 8.82 8.16 5.16 9.301c-.6.179-1.2-.181-1.38-.721-.18-.601.18-1.2.72-1.381 4.26-1.26 11.28-1.02 15.721 1.621.539.3.719 1.02.419 1.56-.299.421-1.02.599-1.559.3z"/></svg>
                    </a>
                    <?php endif; ?>
                    <?php if (!empty($social_links['soundcloud'])): ?>
                    <a href="<?php echo esc_url($social_links['soundcloud']); ?>" target="_blank" rel="noopener" title="SoundCloud" class="es-stage-social-btn">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M1.175 12.225c-.051 0-.094.046-.101.1l-.233 2.154.233 2.105c.007.058.05.098.101.098.05 0 .09-.04.099-.098l.255-2.105-.27-2.154c-.009-.06-.052-.1-.102-.1m-.899.828c-.06 0-.091.037-.104.094L0 14.479l.165 1.308c.014.057.045.094.09.094s.089-.037.099-.094l.19-1.308-.19-1.334c-.01-.057-.045-.09-.09-.09m1.83-1.229c-.061 0-.12.045-.12.104l-.21 2.563.225 2.458c0 .06.045.104.106.104.061 0 .12-.044.12-.104l.24-2.458-.24-2.563c0-.06-.045-.104-.12-.104m.945-.089c-.075 0-.135.06-.15.135l-.193 2.64.21 2.544c.016.077.075.138.149.138.075 0 .135-.061.15-.138l.225-2.544-.225-2.64c-.016-.075-.075-.135-.15-.135m1.065.202c-.09 0-.149.075-.165.165l-.176 2.459.176 2.4c.016.09.075.164.165.164.09 0 .164-.074.164-.164l.21-2.4-.21-2.459c0-.09-.074-.165-.164-.165m1.064-.18c-.104 0-.18.09-.18.18l-.165 2.459.165 2.369c0 .104.076.18.18.18.104 0 .18-.076.18-.18l.195-2.369-.18-2.459c0-.09-.09-.18-.195-.18m1.06-.168c-.104 0-.195.09-.195.195l-.15 2.447.15 2.354c0 .12.09.21.195.21.12 0 .195-.09.21-.21l.165-2.354-.165-2.447c-.015-.105-.09-.195-.21-.195m1.064-.142c-.12 0-.21.09-.225.21l-.135 2.4.135 2.325c.015.12.105.21.225.21.119 0 .21-.09.224-.21l.15-2.325-.15-2.4c-.014-.12-.105-.21-.224-.21m1.065.12c-.135 0-.225.105-.225.225l-.12 2.265.12 2.31c0 .135.09.24.225.24.119 0 .224-.105.224-.24l.135-2.31-.135-2.265c0-.12-.089-.225-.224-.225m7.409.074c-.196 0-.359.03-.524.074-.166-1.935-1.783-3.449-3.749-3.449-.481 0-.945.09-1.365.251-.165.06-.21.12-.21.24v6.815c0 .12.09.224.21.24h5.638c1.425 0 2.58-1.155 2.58-2.58 0-1.44-1.155-2.595-2.58-2.595"/></svg>
                    </a>
                    <?php endif; ?>
                    <?php if (!empty($social_links['youtube'])): ?>
                    <a href="<?php echo esc_url($social_links['youtube']); ?>" target="_blank" rel="noopener" title="YouTube" class="es-stage-social-btn">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                    </a>
                    <?php endif; ?>
                    <?php if (!empty($social_links['instagram'])): ?>
                    <a href="<?php echo esc_url($social_links['instagram']); ?>" target="_blank" rel="noopener" title="Instagram" class="es-stage-social-btn">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C8.74 0 8.333.015 7.053.072 5.775.132 4.905.333 4.14.63c-.789.306-1.459.717-2.126 1.384S.935 3.35.63 4.14C.333 4.905.131 5.775.072 7.053.012 8.333 0 8.74 0 12s.015 3.667.072 4.947c.06 1.277.261 2.148.558 2.913.306.788.717 1.459 1.384 2.126.667.666 1.336 1.079 2.126 1.384.766.296 1.636.499 2.913.558C8.333 23.988 8.74 24 12 24s3.667-.015 4.947-.072c1.277-.06 2.148-.262 2.913-.558.788-.306 1.459-.718 2.126-1.384.666-.667 1.079-1.335 1.384-2.126.296-.765.499-1.636.558-2.913.06-1.28.072-1.687.072-4.947s-.015-3.667-.072-4.947c-.06-1.277-.262-2.149-.558-2.913-.306-.789-.718-1.459-1.384-2.126C21.319 1.347 20.651.935 19.86.63c-.765-.297-1.636-.499-2.913-.558C15.667.012 15.26 0 12 0zm0 2.16c3.203 0 3.585.016 4.85.071 1.17.055 1.805.249 2.227.415.562.217.96.477 1.382.896.419.42.679.819.896 1.381.164.422.36 1.057.413 2.227.057 1.266.07 1.646.07 4.85s-.015 3.585-.074 4.85c-.061 1.17-.256 1.805-.421 2.227-.224.562-.479.96-.899 1.382-.419.419-.824.679-1.38.896-.42.164-1.065.36-2.235.413-1.274.057-1.649.07-4.859.07-3.211 0-3.586-.015-4.859-.074-1.171-.061-1.816-.256-2.236-.421-.569-.224-.96-.479-1.379-.899-.421-.419-.69-.824-.9-1.38-.165-.42-.359-1.065-.42-2.235-.045-1.26-.061-1.649-.061-4.844 0-3.196.016-3.586.061-4.861.061-1.17.255-1.814.42-2.234.21-.57.479-.96.9-1.381.419-.419.81-.689 1.379-.898.42-.166 1.051-.361 2.221-.421 1.275-.045 1.65-.06 4.859-.06l.045.03zm0 3.678c-3.405 0-6.162 2.76-6.162 6.162 0 3.405 2.76 6.162 6.162 6.162 3.405 0 6.162-2.76 6.162-6.162 0-3.405-2.76-6.162-6.162-6.162zM12 16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm7.846-10.405c0 .795-.646 1.44-1.44 1.44-.795 0-1.44-.646-1.44-1.44 0-.794.646-1.439 1.44-1.439.793-.001 1.44.645 1.44 1.439z"/></svg>
                    </a>
                    <?php endif; ?>
                    <?php if (!empty($social_links['facebook'])): ?>
                    <a href="<?php echo esc_url($social_links['facebook']); ?>" target="_blank" rel="noopener" title="Facebook" class="es-stage-social-btn">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    </a>
                    <?php endif; ?>
                    <?php if (!empty($social_links['twitter'])): ?>
                    <a href="<?php echo esc_url($social_links['twitter']); ?>" target="_blank" rel="noopener" title="X/Twitter" class="es-stage-social-btn">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                    </a>
                    <?php endif; ?>
                    <?php if (!empty($social_links['bandcamp'])): ?>
                    <a href="<?php echo esc_url($social_links['bandcamp']); ?>" target="_blank" rel="noopener" title="Bandcamp" class="es-stage-social-btn">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M0 18.75l7.437-13.5H24l-7.438 13.5H0z"/></svg>
                    </a>
                    <?php endif; ?>
                    <?php if (!empty($social_links['mixcloud'])): ?>
                    <a href="<?php echo esc_url($social_links['mixcloud']); ?>" target="_blank" rel="noopener" title="Mixcloud" class="es-stage-social-btn">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20.74 17.96c-.23 0-.46-.1-.62-.27-.15-.18-.22-.4-.2-.64.04-.54.43-.94.92-.94h.04c.23 0 .46.1.62.27.15.18.22.4.2.64-.04.54-.43.94-.92.94h-.04m-3.96 0c-.5 0-.92-.4-.96-.94-.02-.24.05-.46.2-.64.16-.17.4-.27.62-.27h.04c.5 0 .88.4.92.94.02.24-.05.46-.2.64-.16.17-.4.27-.62.27h-.04m-3.22.27c-.5 0-.91-.43-.91-.96v-5.4c0-.53.41-.96.91-.96s.91.43.91.96v5.4c0 .53-.41.96-.91.96m-3.96 0c-.5 0-.91-.43-.91-.96v-8.54c0-.53.41-.96.91-.96s.91.43.91.96v8.54c0 .53-.41.96-.91.96m-3.96 0c-.5 0-.91-.43-.91-.96V12.6c0-.53.41-.96.91-.96s.91.43.91.96v4.67c0 .53-.41.96-.91.96m-3.97 0c-.5 0-.91-.43-.91-.96v-3.13c0-.53.41-.96.91-.96s.91.43.91.96v3.13c0 .53-.41.96-.91.96"/></svg>
                    </a>
                    <?php endif; ?>
                    <?php if (!empty($social_links['website'])): ?>
                    <a href="<?php echo esc_url($social_links['website']); ?>" target="_blank" rel="noopener" title="Website" class="es-stage-social-btn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </header>
        
        <?php if (function_exists('ensemble_after_artist_title')) ensemble_after_artist_title($artist_id); ?>
        
        <!-- CONTENT -->
        <div class="es-stage-content-wrapper">
            <div class="es-stage-main">
                
                <!-- Artist Type Badge -->
                <?php 
                $artist_types = get_the_terms($artist_id, 'ensemble_artist_type');
                if ($artist_types && !is_wp_error($artist_types)): 
                ?>
                <div class="es-stage-type-badges">
                    <?php foreach ($artist_types as $type): ?>
                    <span class="es-stage-type-badge"><?php echo esc_html($type->name); ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <?php if (get_the_content()): ?>
                <section class="es-stage-section">
                    <div class="es-stage-section-header">
                        <h2><?php _e('About', 'ensemble'); ?></h2>
                    </div>
                    <div class="es-stage-description">
                        <?php the_content(); ?>
                    </div>
                </section>
                <?php endif; ?>
                
                <!-- Quote/References -->
                <?php if (!empty($artist['references'])): ?>
                <section class="es-stage-section es-stage-quote-section">
                    <blockquote class="es-stage-quote">
                        <svg class="es-stage-quote-icon" width="32" height="32" viewBox="0 0 24 24" fill="currentColor" opacity="0.3">
                            <path d="M6 17h3l2-4V7H5v6h3zm8 0h3l2-4V7h-6v6h3z"/>
                        </svg>
                        <p><?php echo esc_html($artist['references']); ?></p>
                    </blockquote>
                </section>
                <?php endif; ?>
                
                <!-- Gallery Section -->
                <?php if (function_exists('ensemble_has_addon_hook') && ensemble_has_addon_hook('artist_gallery')): ?>
                <section class="es-stage-section es-stage-gallery-section">
                    <div class="es-stage-section-header">
                        <h2><?php _e('Gallery', 'ensemble'); ?></h2>
                    </div>
                    <div class="es-stage-gallery-wrapper">
                        <?php if (function_exists('ensemble_artist_gallery')) ensemble_artist_gallery($artist_id); ?>
                    </div>
                </section>
                <?php endif; ?>
                
                <!-- Upcoming Events -->
                <?php if (!empty($upcoming_events)): ?>
                <section class="es-stage-section">
                    <div class="es-stage-section-header">
                        <h2><?php _e('Upcoming Events', 'ensemble'); ?></h2>
                    </div>
                    <div class="es-stage-related-grid">
                        <?php foreach ($upcoming_events as $event): ?>
                        <div class="es-stage-card es-stage-card-dark">
                            <a href="<?php echo esc_url($event['permalink']); ?>" class="es-stage-card-inner">
                                <?php if (!empty($event['image'])): ?>
                                <div class="es-stage-card-image">
                                    <img src="<?php echo esc_url($event['image']); ?>" alt="<?php echo esc_attr($event['title']); ?>">
                                </div>
                                <?php endif; ?>
                                <div class="es-stage-card-content">
                                    <time class="es-stage-date">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" style="opacity: 0.6;">
                                            <path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V9h14v11zM9 11H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2z"/>
                                        </svg>
                                        <span><?php echo esc_html($event['date_formatted']); ?></span>
                                    </time>
                                    <h3 class="es-stage-title"><?php echo esc_html($event['title']); ?></h3>
                                    <?php if (!empty($event['location'])): ?>
                                    <div class="es-stage-location">
                                        <?php echo esc_html($event['location']); ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>
                
            </div>
            
            <!-- SIDEBAR -->
            <aside class="es-stage-sidebar">
                <div class="es-stage-sidebar-card">
                    <div class="es-stage-meta-list">
                        
                        <?php if (!empty($artist['genre'])): ?>
                        <div class="es-stage-meta-row">
                            <div class="es-stage-meta-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/>
                                </svg>
                            </div>
                            <div class="es-stage-meta-content">
                                <div class="es-stage-meta-label"><?php _e('Genre', 'ensemble'); ?></div>
                                <div class="es-stage-meta-value"><?php echo esc_html($artist['genre']); ?></div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($artist['origin'])): ?>
                        <div class="es-stage-meta-row">
                            <div class="es-stage-meta-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                                </svg>
                            </div>
                            <div class="es-stage-meta-content">
                                <div class="es-stage-meta-label"><?php _e('Origin', 'ensemble'); ?></div>
                                <div class="es-stage-meta-value"><?php echo esc_html($artist['origin']); ?></div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($artist['booking_email'])): ?>
                        <div class="es-stage-meta-row">
                            <div class="es-stage-meta-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                                </svg>
                            </div>
                            <div class="es-stage-meta-content">
                                <div class="es-stage-meta-label"><?php _e('Booking', 'ensemble'); ?></div>
                                <div class="es-stage-meta-value">
                                    <a href="mailto:<?php echo esc_attr($artist['booking_email']); ?>" style="color: var(--stage-primary);">
                                        <?php echo esc_html($artist['booking_email']); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($artist['website'])): ?>
                        <div class="es-stage-meta-row">
                            <div class="es-stage-meta-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                                </svg>
                            </div>
                            <div class="es-stage-meta-content">
                                <div class="es-stage-meta-label"><?php _e('Website', 'ensemble'); ?></div>
                                <div class="es-stage-meta-value">
                                    <a href="<?php echo esc_url($artist['website']); ?>" target="_blank" rel="noopener" style="color: var(--stage-primary);">
                                        <?php echo esc_html(preg_replace('#^https?://#', '', $artist['website'])); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                    </div>
                </div>
                
                <?php if (function_exists('ensemble_artist_sidebar')) ensemble_artist_sidebar($artist_id); ?>
            </aside>
        </div>
        
    </article>
    
    <?php endwhile; endif; ?>
    
    <?php if (function_exists('ensemble_after_artist')) ensemble_after_artist($artist_id); ?>
    
</div>

<style>
/* Stage Single Artist Additions */
.es-stage-hero-social {
    display: flex;
    gap: 8px;
    margin-top: 20px;
    flex-wrap: wrap;
    justify-content: center;
}

.es-stage-social-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.3);
    color: #fff;
    transition: all 0.2s ease;
}

.es-stage-social-btn:hover {
    background: #fff;
    color: var(--stage-primary);
    transform: translateY(-2px);
}

.es-stage-social-btn svg {
    width: 18px;
    height: 18px;
}

.es-stage-type-badges {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 24px;
}

.es-stage-type-badge {
    display: inline-block;
    padding: 6px 16px;
    background: var(--stage-primary);
    color: var(--stage-btn-text);
    font-family: var(--stage-font-heading);
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.es-stage-quote-section {
    margin: 32px 0;
}

.es-stage-quote {
    position: relative;
    padding: 24px 32px;
    background: var(--stage-surface);
    border-left: 4px solid var(--stage-primary);
    margin: 0;
}

.es-stage-quote-icon {
    position: absolute;
    top: 16px;
    left: 20px;
    color: var(--stage-primary);
}

.es-stage-quote p {
    margin: 0;
    padding-left: 40px;
    font-size: 18px;
    font-style: italic;
    line-height: 1.6;
    color: var(--stage-text);
}

.es-stage-gallery-section .es-stage-gallery-wrapper {
    margin-top: 16px;
}

.es-stage-meta-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    background: var(--stage-primary);
    color: var(--stage-btn-text);
    flex-shrink: 0;
}

.es-stage-meta-icon svg {
    width: 18px;
    height: 18px;
}

.es-stage-meta-row {
    display: flex;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid var(--stage-border);
}

.es-stage-meta-row:last-child {
    border-bottom: none;
}

.es-stage-card-dark {
    background: var(--stage-overlay-bg);
    color: var(--stage-overlay-text);
}

.es-stage-card-dark .es-stage-date,
.es-stage-card-dark .es-stage-location {
    color: var(--stage-overlay-text-secondary);
}

@media (max-width: 768px) {
    .es-stage-hero-social {
        gap: 6px;
    }
    
    .es-stage-social-btn {
        width: 36px;
        height: 36px;
    }
    
    .es-stage-quote p {
        font-size: 16px;
        padding-left: 30px;
    }
}
</style>

<?php get_footer(); ?>
