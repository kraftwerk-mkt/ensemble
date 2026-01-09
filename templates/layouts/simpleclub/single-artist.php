<?php
/**
 * Single Artist Template - SIMPLE CLUB LAYOUT
 * 
 * Clean club-style layout with:
 * - Large hero image with gradient overlay
 * - Name and genre prominently displayed
 * - Social links in header
 * - Bio and upcoming events
 *
 * @package Ensemble
 * @version 1.0.0
 */

if (!defined('ABSPATH')) exit;

get_header();

// Enqueue base CSS
wp_enqueue_style('ensemble-base', ENSEMBLE_PLUGIN_URL . 'assets/css/layouts/ensemble-base.css', array(), ENSEMBLE_VERSION);

// Enqueue Simple Club Layout CSS
wp_enqueue_style('ensemble-layout-simpleclub', ENSEMBLE_PLUGIN_URL . 'templates/layouts/simpleclub/style.css', array('ensemble-base'), ENSEMBLE_VERSION);

// Load Montserrat font
wp_enqueue_style('ensemble-simpleclub-font', 'https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap', array(), ENSEMBLE_VERSION);

$artist_id = get_the_ID();
$artist = es_load_artist_data($artist_id);
$upcoming_events = es_get_upcoming_events_by_artist($artist_id);

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

<div class="es-simpleclub-single es-simpleclub-artist-single">
    
    <?php if (function_exists('ensemble_before_artist')) ensemble_before_artist($artist_id); ?>
    
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
    
    <!-- Hero -->
    <header class="es-simpleclub-hero">
        <?php if (has_post_thumbnail()): ?>
        <div class="es-simpleclub-hero-media">
            <?php the_post_thumbnail('full'); ?>
            <div class="es-simpleclub-hero-overlay"></div>
        </div>
        <?php endif; ?>
        
        <div class="es-simpleclub-hero-content">
            
            <?php if (!empty($artist['genre'])): ?>
            <span class="es-simpleclub-hero-badge"><?php echo esc_html($artist['genre']); ?></span>
            <?php endif; ?>
            
            <h1 class="es-simpleclub-hero-title"><?php the_title(); ?></h1>
            
            <?php if (!empty($artist['origin'])): ?>
            <div class="es-simpleclub-hero-location">
                <?php echo esc_html($artist['origin']); ?>
            </div>
            <?php endif; ?>
            
            <!-- Social Links -->
            <?php if ($has_social): ?>
            <div class="es-simpleclub-hero-social">
                <?php if (!empty($social_links['spotify'])): ?>
                <a href="<?php echo esc_url($social_links['spotify']); ?>" target="_blank" rel="noopener" title="Spotify" class="es-simpleclub-social-btn">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.4 0 0 5.4 0 12s5.4 12 12 12 12-5.4 12-12S18.66 0 12 0zm5.521 17.34c-.24.359-.66.48-1.021.24-2.82-1.74-6.36-2.101-10.561-1.141-.418.122-.779-.179-.899-.539-.12-.421.18-.78.54-.9 4.56-1.021 8.52-.6 11.64 1.32.42.18.479.659.301 1.02zm1.44-3.3c-.301.42-.841.6-1.262.3-3.239-1.98-8.159-2.58-11.939-1.38-.479.12-1.02-.12-1.14-.6-.12-.48.12-1.021.6-1.141C9.6 9.9 15 10.561 18.72 12.84c.361.181.54.78.241 1.2zm.12-3.36C15.24 8.4 8.82 8.16 5.16 9.301c-.6.179-1.2-.181-1.38-.721-.18-.601.18-1.2.72-1.381 4.26-1.26 11.28-1.02 15.721 1.621.539.3.719 1.02.419 1.56-.299.421-1.02.599-1.559.3z"/></svg>
                </a>
                <?php endif; ?>
                <?php if (!empty($social_links['soundcloud'])): ?>
                <a href="<?php echo esc_url($social_links['soundcloud']); ?>" target="_blank" rel="noopener" title="SoundCloud" class="es-simpleclub-social-btn">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M1.175 12.225c-.051 0-.094.046-.101.1l-.233 2.154.233 2.105c.007.058.05.098.101.098.05 0 .09-.04.099-.098l.255-2.105-.27-2.154c-.009-.06-.052-.1-.102-.1m-.899.828c-.06 0-.091.037-.104.094L0 14.479l.165 1.308c.014.057.045.094.09.094s.089-.037.099-.094l.19-1.308-.19-1.334c-.01-.057-.045-.09-.09-.09m1.83-1.229c-.061 0-.12.045-.12.104l-.21 2.563.225 2.458c0 .06.045.104.106.104.061 0 .12-.044.12-.104l.24-2.458-.24-2.563c0-.06-.045-.104-.12-.104m.945-.089c-.075 0-.135.06-.15.135l-.193 2.64.21 2.544c.016.077.075.138.149.138.075 0 .135-.061.15-.138l.225-2.544-.225-2.64c-.016-.075-.075-.135-.15-.135m1.065.202c-.09 0-.149.075-.165.165l-.176 2.459.176 2.4c.016.09.075.164.165.164.09 0 .164-.074.164-.164l.21-2.4-.21-2.459c0-.09-.074-.165-.164-.165m1.064-.18c-.104 0-.18.09-.18.18l-.165 2.459.165 2.369c0 .104.076.18.18.18.104 0 .18-.076.18-.18l.195-2.369-.18-2.459c0-.09-.09-.18-.195-.18m1.06-.168c-.104 0-.195.09-.195.195l-.15 2.447.15 2.354c0 .12.09.21.195.21.12 0 .195-.09.21-.21l.165-2.354-.165-2.447c-.015-.105-.09-.195-.21-.195m1.064-.142c-.12 0-.21.09-.225.21l-.135 2.4.135 2.325c.015.12.105.21.225.21.119 0 .21-.09.224-.21l.15-2.325-.15-2.4c-.014-.12-.105-.21-.224-.21m1.065.12c-.135 0-.225.105-.225.225l-.12 2.265.12 2.31c0 .135.09.24.225.24.119 0 .224-.105.224-.24l.135-2.31-.135-2.265c0-.12-.089-.225-.224-.225m7.409.074c-.196 0-.359.03-.524.074-.166-1.935-1.783-3.449-3.749-3.449-.481 0-.945.09-1.365.251-.165.06-.21.12-.21.24v6.815c0 .12.09.224.21.24h5.638c1.425 0 2.58-1.155 2.58-2.58 0-1.44-1.155-2.595-2.58-2.595"/></svg>
                </a>
                <?php endif; ?>
                <?php if (!empty($social_links['youtube'])): ?>
                <a href="<?php echo esc_url($social_links['youtube']); ?>" target="_blank" rel="noopener" title="YouTube" class="es-simpleclub-social-btn">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                </a>
                <?php endif; ?>
                <?php if (!empty($social_links['instagram'])): ?>
                <a href="<?php echo esc_url($social_links['instagram']); ?>" target="_blank" rel="noopener" title="Instagram" class="es-simpleclub-social-btn">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C8.74 0 8.333.015 7.053.072 5.775.132 4.905.333 4.14.63c-.789.306-1.459.717-2.126 1.384S.935 3.35.63 4.14C.333 4.905.131 5.775.072 7.053.012 8.333 0 8.74 0 12s.015 3.667.072 4.947c.06 1.277.261 2.148.558 2.913.306.788.717 1.459 1.384 2.126.667.666 1.336 1.079 2.126 1.384.766.296 1.636.499 2.913.558C8.333 23.988 8.74 24 12 24s3.667-.015 4.947-.072c1.277-.06 2.148-.262 2.913-.558.788-.306 1.459-.718 2.126-1.384.666-.667 1.079-1.335 1.384-2.126.296-.765.499-1.636.558-2.913.06-1.28.072-1.687.072-4.947s-.015-3.667-.072-4.947c-.06-1.277-.262-2.149-.558-2.913-.306-.789-.718-1.459-1.384-2.126C21.319 1.347 20.651.935 19.86.63c-.765-.297-1.636-.499-2.913-.558C15.667.012 15.26 0 12 0zm0 2.16c3.203 0 3.585.016 4.85.071 1.17.055 1.805.249 2.227.415.562.217.96.477 1.382.896.419.42.679.819.896 1.381.164.422.36 1.057.413 2.227.057 1.266.07 1.646.07 4.85s-.015 3.585-.074 4.85c-.061 1.17-.256 1.805-.421 2.227-.224.562-.479.96-.899 1.382-.419.419-.824.679-1.38.896-.42.164-1.065.36-2.235.413-1.274.057-1.649.07-4.859.07-3.211 0-3.586-.015-4.859-.074-1.171-.061-1.816-.256-2.236-.421-.569-.224-.96-.479-1.379-.899-.421-.419-.69-.824-.9-1.38-.165-.42-.359-1.065-.42-2.235-.045-1.26-.061-1.649-.061-4.844 0-3.196.016-3.586.061-4.861.061-1.17.255-1.814.42-2.234.21-.57.479-.96.9-1.381.419-.419.81-.689 1.379-.898.42-.166 1.051-.361 2.221-.421 1.275-.045 1.65-.06 4.859-.06l.045.03zm0 3.678c-3.405 0-6.162 2.76-6.162 6.162 0 3.405 2.76 6.162 6.162 6.162 3.405 0 6.162-2.76 6.162-6.162 0-3.405-2.76-6.162-6.162-6.162zM12 16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm7.846-10.405c0 .795-.646 1.44-1.44 1.44-.795 0-1.44-.646-1.44-1.44 0-.794.646-1.439 1.44-1.439.793-.001 1.44.645 1.44 1.439z"/></svg>
                </a>
                <?php endif; ?>
                <?php if (!empty($social_links['facebook'])): ?>
                <a href="<?php echo esc_url($social_links['facebook']); ?>" target="_blank" rel="noopener" title="Facebook" class="es-simpleclub-social-btn">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                </a>
                <?php endif; ?>
                <?php if (!empty($social_links['mixcloud'])): ?>
                <a href="<?php echo esc_url($social_links['mixcloud']); ?>" target="_blank" rel="noopener" title="Mixcloud" class="es-simpleclub-social-btn">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M2.462 8.596A5.535 5.535 0 0 0 0 13.209a5.538 5.538 0 0 0 5.536 5.536c.327 0 .654-.03.975-.085a.15.15 0 0 0 .124-.148v-2.062a.15.15 0 0 0-.17-.148 3.244 3.244 0 0 1-3.634-3.22c0-1.79 1.455-3.244 3.244-3.244.692 0 1.352.218 1.905.63a.15.15 0 0 0 .234-.123V8.056a.15.15 0 0 0-.06-.12 5.5 5.5 0 0 0-5.692-.34zm7.627 1.04A4.24 4.24 0 0 0 6.33 13.87a4.24 4.24 0 0 0 4.235 4.235h.97a.15.15 0 0 0 .15-.15v-1.87a.15.15 0 0 0-.15-.149h-.97a1.941 1.941 0 0 1-1.938-1.936c0-1.067.87-1.936 1.938-1.936h.97a.15.15 0 0 0 .15-.15V9.786a.15.15 0 0 0-.15-.15h-.496zm5.38 0a4.24 4.24 0 0 0-3.76 4.234 4.24 4.24 0 0 0 4.235 4.235h.97a.15.15 0 0 0 .15-.15v-1.87a.15.15 0 0 0-.15-.149h-.97a1.941 1.941 0 0 1-1.938-1.936c0-1.067.87-1.936 1.938-1.936h.97a.15.15 0 0 0 .15-.15V9.786a.15.15 0 0 0-.15-.15h-.496zm5.85 0a.15.15 0 0 0-.149.15v8.12a.15.15 0 0 0 .15.149h1.83a.15.15 0 0 0 .15-.15v-8.12a.15.15 0 0 0-.15-.149h-1.83z"/></svg>
                </a>
                <?php endif; ?>
                <?php if (!empty($social_links['bandcamp'])): ?>
                <a href="<?php echo esc_url($social_links['bandcamp']); ?>" target="_blank" rel="noopener" title="Bandcamp" class="es-simpleclub-social-btn">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M0 18.75l7.437-13.5H24l-7.438 13.5H0z"/></svg>
                </a>
                <?php endif; ?>
                <?php if (!empty($social_links['website'])): ?>
                <a href="<?php echo esc_url($social_links['website']); ?>" target="_blank" rel="noopener" title="Website" class="es-simpleclub-social-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <?php if (function_exists('ensemble_after_artist_title')) ensemble_after_artist_title($artist_id); ?>
        </div>
    </header>
    
    <!-- Content -->
    <div class="es-simpleclub-body">
        <div class="es-simpleclub-container">
            
            <div class="es-simpleclub-layout-grid">
                
                <!-- Main -->
                <main class="es-simpleclub-main">
                    
                    <?php if (function_exists('ensemble_before_artist_content')) ensemble_before_artist_content($artist_id); ?>
                    
                    <!-- Artist Type -->
                    <?php if (!empty($artist['type'])): ?>
                    <div class="es-simpleclub-artist-type">
                        <?php echo esc_html($artist['type']); ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Bio -->
                    <?php if (get_the_content()): ?>
                    <section class="es-simpleclub-section es-simpleclub-description">
                        <h2 class="es-simpleclub-section-title"><?php _e('Bio', 'ensemble'); ?></h2>
                        <div class="es-simpleclub-prose">
                            <?php the_content(); ?>
                        </div>
                    </section>
                    <?php endif; ?>
                    
                    <?php if (function_exists('ensemble_after_artist_bio')) ensemble_after_artist_bio($artist_id); ?>
                    
                    <!-- References / Quote -->
                    <?php if (!empty($artist['references'])): ?>
                    <section class="es-simpleclub-section es-simpleclub-quote-section">
                        <blockquote class="es-simpleclub-quote">
                            <?php echo esc_html($artist['references']); ?>
                        </blockquote>
                    </section>
                    <?php endif; ?>
                    
                    <!-- Gallery (Addon Hook) -->
                    <?php 
                    if (function_exists('ensemble_has_addon_hook') && ensemble_has_addon_hook('artist_gallery')) {
                        echo '<section class="es-simpleclub-section es-simpleclub-gallery">';
                        echo '<h2 class="es-simpleclub-section-title">' . __('Gallery', 'ensemble') . '</h2>';
                        if (function_exists('ensemble_artist_gallery')) ensemble_artist_gallery($artist_id);
                        echo '</section>';
                    }
                    ?>
                    
                </main>
                
                <!-- Sidebar -->
                <aside class="es-simpleclub-sidebar">
                    
                    <!-- Upcoming Events -->
                    <?php if (!empty($upcoming_events)): ?>
                    <div class="es-simpleclub-sidebar-card es-simpleclub-upcoming-card">
                        <h3 class="es-simpleclub-card-label"><?php _e('Upcoming Events', 'ensemble'); ?></h3>
                        <div class="es-simpleclub-event-list">
                            <?php foreach ($upcoming_events as $event): 
                                $event_date = strtotime($event['date']);
                            ?>
                            <a href="<?php echo esc_url($event['permalink']); ?>" class="es-simpleclub-event-item">
                                <div class="es-simpleclub-event-date-badge">
                                    <span class="es-simpleclub-date-day"><?php echo date_i18n('j', $event_date); ?></span>
                                    <span class="es-simpleclub-date-month"><?php echo date_i18n('M', $event_date); ?></span>
                                </div>
                                <div class="es-simpleclub-event-details">
                                    <span class="es-simpleclub-event-name"><?php echo esc_html($event['title']); ?></span>
                                    <?php if (!empty($event['location'])): ?>
                                    <span class="es-simpleclub-event-venue"><?php echo esc_html($event['location']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (function_exists('ensemble_artist_sidebar')) ensemble_artist_sidebar($artist_id); ?>
                    
                </aside>
                
            </div>
            
        </div>
    </div>
    
    <?php endwhile; endif; ?>
    
    <?php if (function_exists('ensemble_after_artist')) ensemble_after_artist($artist_id); ?>
    
</div>

<?php get_footer(); ?>
