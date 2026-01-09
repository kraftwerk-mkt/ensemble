<?php
/**
 * Template: Kongress Single Speaker
 * 
 * Professional speaker profile page
 * Designed for long biographies and detailed information
 * 
 * @package Ensemble
 * @version 1.1.0
 */

if (!defined('ABSPATH')) exit;

get_header();

$artist_id = get_the_ID();

// Basic Info
$name = get_the_title();
$content = get_the_content();
$featured_image = get_the_post_thumbnail_url($artist_id, 'large');
$excerpt = get_the_excerpt();

// Professional Info (NEW meta keys)
$position = get_post_meta($artist_id, '_es_artist_position', true);
$company = get_post_meta($artist_id, '_es_artist_company', true);
$additional = get_post_meta($artist_id, '_es_artist_additional', true);
$website = get_post_meta($artist_id, 'artist_website', true);
$email = get_post_meta($artist_id, 'artist_email', true);
$references = get_post_meta($artist_id, 'artist_references', true);

// Social Links - aus Array extrahieren
$social_links_array = get_post_meta($artist_id, 'artist_social_links', true);
if (!is_array($social_links_array)) {
    $social_links_array = array();
}

// URLs nach Plattform zuordnen
$social_links = array();
foreach ($social_links_array as $url) {
    if (empty($url)) continue;
    $url = esc_url($url);
    
    if (stripos($url, 'linkedin.com') !== false) {
        $social_links['linkedin'] = $url;
    } elseif (stripos($url, 'twitter.com') !== false || stripos($url, 'x.com') !== false) {
        $social_links['twitter'] = $url;
    } elseif (stripos($url, 'xing.com') !== false) {
        $social_links['xing'] = $url;
    } elseif (stripos($url, 'facebook.com') !== false) {
        $social_links['facebook'] = $url;
    } elseif (stripos($url, 'instagram.com') !== false) {
        $social_links['instagram'] = $url;
    } elseif (stripos($url, 'youtube.com') !== false) {
        $social_links['youtube'] = $url;
    } elseif (stripos($url, 'tiktok.com') !== false) {
        $social_links['tiktok'] = $url;
    } elseif (stripos($url, 'github.com') !== false) {
        $social_links['github'] = $url;
    } elseif (stripos($url, 'researchgate.net') !== false) {
        $social_links['researchgate'] = $url;
    } else {
        // Unbekannte Links als "other" sammeln
        if (!isset($social_links['other'])) {
            $social_links['other'] = array();
        }
        $social_links['other'][] = $url;
    }
}

// Additional Fields (falls vorhanden)
$qualifications = get_post_meta($artist_id, 'artist_qualifications', true);
$expertise = get_post_meta($artist_id, 'artist_expertise', true);
$publications = get_post_meta($artist_id, 'artist_publications', true);

// Get events where this speaker appears
$speaker_events = get_posts(array(
    'post_type'      => ensemble_get_post_type(),
    'posts_per_page' => 10,
    'meta_query'     => array(
        array(
            'key'     => 'event_artist',
            'value'   => $artist_id,
            'compare' => 'LIKE',
        ),
    ),
));

// Genres/Tags
$genres = get_the_terms($artist_id, 'ensemble_genre');
$artist_types = get_the_terms($artist_id, 'ensemble_artist_type');
?>

<article class="es-kongress-single-speaker es-layout-kongress" id="es-speaker-<?php echo esc_attr($artist_id); ?>">

    <?php do_action('ensemble_before_single_artist', $artist_id); ?>

    <!-- HERO SECTION -->
    <section class="es-kongress-speaker-hero">
        
        <!-- Portrait -->
        <div class="es-kongress-speaker-portrait es-kongress-animate">
            <?php if ($featured_image): ?>
            <img src="<?php echo esc_url($featured_image); ?>" alt="<?php echo esc_attr($name); ?>">
            <?php else: ?>
            <div style="width: 100%; aspect-ratio: 3/4; background: var(--ensemble-placeholder-bg); border-radius: var(--ensemble-card-radius); display: flex; align-items: center; justify-content: center;">
                <svg viewBox="0 0 24 24" fill="none" stroke="var(--ensemble-placeholder-icon)" stroke-width="1" style="width: 80px; height: 80px;">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Header Info -->
        <div class="es-kongress-speaker-header es-kongress-animate es-kongress-animate-delay-1">
            
            <!-- Tags/Types -->
            <?php if ($artist_types && !is_wp_error($artist_types)): ?>
            <div style="margin-bottom: 16px;">
                <?php foreach ($artist_types as $type): ?>
                <span style="display: inline-block; padding: 4px 12px; background: var(--ensemble-surface); color: var(--ensemble-text-secondary); font-size: var(--ensemble-small-size); border-radius: 4px; margin-right: 8px; margin-bottom: 8px;">
                    <?php echo esc_html($type->name); ?>
                </span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <!-- Name -->
            <h1><?php echo esc_html($name); ?></h1>
            
            <!-- Role & Company -->
            <?php if ($position): ?>
            <div class="es-kongress-speaker-title-role"><?php echo esc_html($position); ?></div>
            <?php endif; ?>
            
            <?php if ($company): ?>
            <div class="es-kongress-speaker-company-name"><?php echo esc_html($company); ?></div>
            <?php endif; ?>
            
            <!-- Additional Info -->
            <?php if ($additional): ?>
            <div class="es-kongress-speaker-additional" style="font-style: italic; color: var(--kongress-copper); margin-top: 8px; margin-bottom: 16px;">
                <?php echo esc_html($additional); ?>
            </div>
            <?php endif; ?>
            
            <!-- Short Bio/Excerpt -->
            <?php if ($excerpt): ?>
            <p style="font-size: var(--ensemble-lg-size); color: var(--ensemble-text-secondary); margin-bottom: 24px; max-width: 600px;">
                <?php echo esc_html($excerpt); ?>
            </p>
            <?php endif; ?>
            
            <!-- References / Expertise Text -->
            <?php if ($references): ?>
            <div class="es-kongress-speaker-references" style="margin-bottom: 24px; padding: 16px 20px; background: var(--ensemble-surface); border-left: 3px solid var(--kongress-copper); border-radius: 0 var(--ensemble-card-radius) var(--ensemble-card-radius) 0;">
                <p style="font-size: var(--ensemble-base-size); color: var(--ensemble-text-secondary); margin: 0; line-height: 1.6;">
                    <?php echo wp_kses_post($references); ?>
                </p>
            </div>
            <?php endif; ?>
            
            <!-- Expertise Tags -->
            <?php if ($genres && !is_wp_error($genres)): ?>
            <div style="margin-bottom: 24px;">
                <span style="font-size: var(--ensemble-small-size); color: var(--ensemble-text-muted); text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 8px;">
                    <?php _e('Expertise', 'ensemble'); ?>
                </span>
                <?php foreach ($genres as $genre): ?>
                <span style="display: inline-block; padding: 6px 14px; background: var(--kongress-navy); color: #fff; font-size: var(--ensemble-small-size); border-radius: var(--ensemble-button-radius); margin-right: 8px; margin-bottom: 8px;">
                    <?php echo esc_html($genre->name); ?>
                </span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <!-- Social Links -->
            <?php if (!empty($social_links) || $website || $email): ?>
            <div class="es-kongress-speaker-social">
                <?php if (!empty($social_links['linkedin'])): ?>
                <a href="<?php echo esc_url($social_links['linkedin']); ?>" target="_blank" rel="noopener" title="LinkedIn">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/>
                        <rect x="2" y="9" width="4" height="12"/>
                        <circle cx="4" cy="4" r="2"/>
                    </svg>
                </a>
                <?php endif; ?>
                
                <?php if (!empty($social_links['twitter'])): ?>
                <a href="<?php echo esc_url($social_links['twitter']); ?>" target="_blank" rel="noopener" title="X/Twitter">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                    </svg>
                </a>
                <?php endif; ?>
                
                <?php if (!empty($social_links['xing'])): ?>
                <a href="<?php echo esc_url($social_links['xing']); ?>" target="_blank" rel="noopener" title="XING">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M18.188 0c-.517 0-.741.325-.927.66 0 0-7.455 13.224-7.702 13.657.015.024 4.919 9.023 4.919 9.023.17.308.436.66.967.66h3.454c.211 0 .375-.078.463-.22.089-.151.089-.346-.009-.536l-4.879-8.916c-.004-.006-.004-.016 0-.022L22.139.756c.095-.191.097-.387.006-.535C22.056.078 21.894 0 21.686 0h-3.498zM3.648 4.74c-.211 0-.385.074-.473.216-.09.149-.078.339.02.531l2.34 4.05c.004.01.004.016 0 .021L1.86 16.051c-.099.188-.093.381 0 .529.085.142.239.234.45.234h3.461c.518 0 .766-.348.945-.667l3.734-6.609-2.378-4.155c-.172-.315-.434-.659-.962-.659H3.648v.016z"/>
                    </svg>
                </a>
                <?php endif; ?>
                
                <?php if (!empty($social_links['facebook'])): ?>
                <a href="<?php echo esc_url($social_links['facebook']); ?>" target="_blank" rel="noopener" title="Facebook">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>
                    </svg>
                </a>
                <?php endif; ?>
                
                <?php if (!empty($social_links['instagram'])): ?>
                <a href="<?php echo esc_url($social_links['instagram']); ?>" target="_blank" rel="noopener" title="Instagram">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="2" width="20" height="20" rx="5" ry="5"/>
                        <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/>
                        <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/>
                    </svg>
                </a>
                <?php endif; ?>
                
                <?php if (!empty($social_links['youtube'])): ?>
                <a href="<?php echo esc_url($social_links['youtube']); ?>" target="_blank" rel="noopener" title="YouTube">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                    </svg>
                </a>
                <?php endif; ?>
                
                <?php if (!empty($social_links['tiktok'])): ?>
                <a href="<?php echo esc_url($social_links['tiktok']); ?>" target="_blank" rel="noopener" title="TikTok">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1-.1z"/>
                    </svg>
                </a>
                <?php endif; ?>
                
                <?php if (!empty($social_links['github'])): ?>
                <a href="<?php echo esc_url($social_links['github']); ?>" target="_blank" rel="noopener" title="GitHub">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                    </svg>
                </a>
                <?php endif; ?>
                
                <?php if (!empty($social_links['researchgate'])): ?>
                <a href="<?php echo esc_url($social_links['researchgate']); ?>" target="_blank" rel="noopener" title="ResearchGate">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19.586 0c-.818 0-1.508.19-2.073.565-.563.377-.97.936-1.213 1.68a3.193 3.193 0 0 0-.112.437 8.365 8.365 0 0 0-.078.53 9 9 0 0 0-.046.665c-.008.267-.007.566.004.896.024.703.065 1.382.124 2.04.06.658.136 1.299.228 1.925.092.626.2 1.238.323 1.836.124.599.263 1.183.418 1.755.155.572.324 1.13.507 1.673.183.544.38 1.072.59 1.586.21.515.433 1.014.667 1.498.234.483.48.95.739 1.4.258.45.527.884.807 1.3.28.417.57.816.87 1.198a14.256 14.256 0 0 0 1.989 2.07c.376.32.769.62 1.178.896.409.276.831.526 1.266.75a7.32 7.32 0 0 0 1.395.582c.485.16.98.269 1.487.326.506.058 1.022.064 1.546.018.524-.046 1.057-.142 1.598-.288.541-.146 1.09-.342 1.647-.588v-1.56c-.532.292-1.06.53-1.585.716-.525.185-1.046.32-1.561.403-.515.083-1.023.115-1.524.095a6.402 6.402 0 0 1-1.467-.184 6.62 6.62 0 0 1-1.388-.472 9.18 9.18 0 0 1-1.287-.724 12.143 12.143 0 0 1-1.166-.94c-.372-.337-.728-.7-1.066-1.088a16.045 16.045 0 0 1-.952-1.2 18.835 18.835 0 0 1-.82-1.294 22.81 22.81 0 0 1-.672-1.371c-.206-.47-.4-.953-.583-1.45a25.878 25.878 0 0 1-.48-1.514 31.136 31.136 0 0 1-.362-1.563 38.396 38.396 0 0 1-.228-1.596c-.058-.535-.1-1.075-.128-1.622a29.58 29.58 0 0 1-.014-1.633c.027-.545.071-1.081.132-1.608.061-.528.14-1.045.236-1.554.096-.509.21-1.01.341-1.502H24v18.016H0V0h19.586z"/>
                    </svg>
                </a>
                <?php endif; ?>
                
                <?php if ($website): ?>
                <a href="<?php echo esc_url($website); ?>" target="_blank" rel="noopener" title="Website">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="2" y1="12" x2="22" y2="12"/>
                        <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                    </svg>
                </a>
                <?php endif; ?>
                
                <?php if ($email): ?>
                <a href="mailto:<?php echo esc_attr($email); ?>" title="<?php _e('E-Mail', 'ensemble'); ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                        <polyline points="22,6 12,13 2,6"/>
                    </svg>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
        </div>
        
    </section>

    <!-- FULL BIOGRAPHY -->
    <?php if ($content): ?>
    <section class="es-kongress-content-wrapper">
        <div class="es-kongress-section es-kongress-animate">
            <div class="es-kongress-section-header">
                <div class="es-kongress-section-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                </div>
                <h2 class="es-kongress-section-title"><?php _e('Biografie', 'ensemble'); ?></h2>
            </div>
            
            <div class="es-kongress-speaker-bio">
                <?php echo wp_kses_post($content); ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- QUALIFICATIONS / PUBLICATIONS (if available) -->
    <?php if ($qualifications || $publications): ?>
    <section style="background: var(--ensemble-surface); padding: var(--ensemble-section-spacing) 0;">
        <div class="es-kongress-content-wrapper">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 48px;">
                
                <?php if ($qualifications): ?>
                <div class="es-kongress-animate">
                    <h3 style="font-family: var(--ensemble-font-heading); font-size: var(--ensemble-h3-size); color: var(--ensemble-text); margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid var(--kongress-copper);">
                        <?php _e('Qualifikationen', 'ensemble'); ?>
                    </h3>
                    <div style="color: var(--ensemble-text-secondary); line-height: 1.8;">
                        <?php echo wp_kses_post($qualifications); ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($publications): ?>
                <div class="es-kongress-animate es-kongress-animate-delay-1">
                    <h3 style="font-family: var(--ensemble-font-heading); font-size: var(--ensemble-h3-size); color: var(--ensemble-text); margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid var(--kongress-copper);">
                        <?php _e('Publikationen', 'ensemble'); ?>
                    </h3>
                    <div style="color: var(--ensemble-text-secondary); line-height: 1.8;">
                        <?php echo wp_kses_post($publications); ?>
                    </div>
                </div>
                <?php endif; ?>
                
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- SPEAKER SESSIONS / EVENTS -->
    <?php if (!empty($speaker_events)): ?>
    <section class="es-kongress-speaker-sessions">
        <div class="es-kongress-speaker-sessions-inner">
            <div class="es-kongress-section-header es-kongress-animate">
                <div class="es-kongress-section-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                </div>
                <h2 class="es-kongress-section-title"><?php _e('Sessions & Vorträge', 'ensemble'); ?></h2>
            </div>
            
            <div style="display: grid; gap: 24px; margin-top: 32px;">
                <?php foreach ($speaker_events as $event): 
                    $event_date = get_post_meta($event->ID, 'event_date', true);
                    if (!$event_date) {
                        $event_date = ensemble_get_field('event_date', $event->ID);
                    }
                    $event_time = get_post_meta($event->ID, 'event_time', true);
                    if (!$event_time) {
                        $event_time = ensemble_get_field('event_time', $event->ID);
                    }
                    
                    // Get artist time for this specific event
                    $artist_times = get_post_meta($event->ID, 'artist_times', true);
                    $session_time = '';
                    if (is_array($artist_times) && isset($artist_times[$artist_id])) {
                        $session_time = $artist_times[$artist_id];
                    }
                    
                    $formatted_date = $event_date ? date_i18n('j. F Y', strtotime($event_date)) : '';
                ?>
                <a href="<?php echo esc_url(get_permalink($event->ID)); ?>" class="es-kongress-session es-kongress-animate" style="text-decoration: none;">
                    <div class="es-kongress-session-header">
                        <h3 class="es-kongress-session-title"><?php echo esc_html($event->post_title); ?></h3>
                    </div>
                    <div style="display: flex; gap: 24px; margin-top: 12px; font-size: var(--ensemble-small-size); color: var(--ensemble-text-secondary);">
                        <?php if ($formatted_date): ?>
                        <span style="display: flex; align-items: center; gap: 6px;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width: 16px; height: 16px;">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                <line x1="16" y1="2" x2="16" y2="6"/>
                                <line x1="8" y1="2" x2="8" y2="6"/>
                                <line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                            <?php echo esc_html($formatted_date); ?>
                        </span>
                        <?php endif; ?>
                        
                        <?php if ($session_time): ?>
                        <span style="display: flex; align-items: center; gap: 6px;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width: 16px; height: 16px;">
                                <circle cx="12" cy="12" r="10"/>
                                <polyline points="12 6 12 12 16 14"/>
                            </svg>
                            <?php echo esc_html($session_time); ?> <?php _e('Uhr', 'ensemble'); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>
    
    <?php 
    // ADDON HOOK: Downloads, Materials (CV, etc.)
    if (class_exists('ES_Addon_Manager')) {
        ES_Addon_Manager::do_addon_hook('ensemble_after_artist_content', $artist_id);
    }
    ?>

    <!-- FOOTER -->
    <footer class="es-kongress-footer" style="padding: var(--ensemble-section-spacing) 0;">
        <div class="es-kongress-content-wrapper">
            <?php 
            // Hook: Social Share
            if (function_exists('ensemble_social_share')) {
                ensemble_social_share($artist_id);
            }
            
            // Hook: After Artist
            do_action('ensemble_after_single_artist', $artist_id);
            ?>
            
            <!-- Back to Event Link -->
            <div style="text-align: center; margin-top: 48px;">
                <a href="javascript:history.back()" class="es-kongress-btn es-kongress-btn-secondary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 16px; height: 16px;">
                        <line x1="19" y1="12" x2="5" y2="12"/>
                        <polyline points="12 19 5 12 12 5"/>
                    </svg>
                    <?php _e('Zurück', 'ensemble'); ?>
                </a>
            </div>
        </div>
    </footer>

</article>

<!-- Scroll Animation Script -->
<script>
(function() {
    var animateElements = document.querySelectorAll('.es-kongress-animate');
    var observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
            }
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });
    
    animateElements.forEach(function(el) {
        observer.observe(el);
    });
})();
</script>

<?php get_footer(); ?>
