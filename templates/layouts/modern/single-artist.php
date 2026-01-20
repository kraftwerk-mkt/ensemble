<?php
/**
 * Single Artist Template - Bristol City Festival
 * Urban festival style with all addon hooks
 *
 * @package Ensemble
 * @layout Bristol City Festival
 * @version 1.0.0
 */

if (!defined('ABSPATH')) exit;

get_header();

wp_enqueue_style('ensemble-base', ENSEMBLE_PLUGIN_URL . 'assets/css/layouts/ensemble-base.css', array(), ENSEMBLE_VERSION);

$artist_id = get_the_ID();
$artist = es_load_artist_data($artist_id);
$upcoming_events = es_get_upcoming_events_by_artist($artist_id);

$social_links = array(
    'website' => $artist['website'],
    'spotify' => $artist['spotify'],
    'soundcloud' => $artist['soundcloud'],
    'youtube' => $artist['youtube'],
    'instagram' => $artist['instagram'],
    'facebook' => $artist['facebook'],
    'twitter' => $artist['twitter'],
    'bandcamp' => $artist['bandcamp'],
    'mixcloud' => $artist['mixcloud'],
);
$has_social = !empty(array_filter($social_links));
?>

<div class="es-bristol es-bristol-single es-bristol-artist">
    
    <?php ensemble_before_artist($artist_id); ?>
    
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
    
    <!-- Hero -->
    <header class="es-bristol-single-hero es-bristol-single-hero-artist">
        <?php if (has_post_thumbnail()): ?>
        <div class="es-bristol-single-hero-media">
            <?php the_post_thumbnail('full'); ?>
            <div class="es-bristol-single-hero-overlay"></div>
        </div>
        <?php endif; ?>
        
        <div class="es-bristol-single-hero-content">
            
            <?php if ($artist['genre']): ?>
            <span class="es-bristol-single-hero-genre"><?php echo esc_html($artist['genre']); ?></span>
            <?php endif; ?>
            
            <h1 class="es-bristol-single-hero-title"><?php the_title(); ?></h1>
            
            <?php if ($artist['origin']): ?>
            <div class="es-bristol-single-hero-meta"><?php echo esc_html($artist['origin']); ?></div>
            <?php endif; ?>
            
            <!-- Social Links -->
            <?php if ($has_social): ?>
            <div class="es-bristol-single-hero-social">
                <?php if ($artist['spotify']): ?>
                <a href="<?php echo esc_url($artist['spotify']); ?>" target="_blank" rel="noopener" title="Spotify">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.4 0 0 5.4 0 12s5.4 12 12 12 12-5.4 12-12S18.66 0 12 0zm5.521 17.34c-.24.359-.66.48-1.021.24-2.82-1.74-6.36-2.101-10.561-1.141-.418.122-.779-.179-.899-.539-.12-.421.18-.78.54-.9 4.56-1.021 8.52-.6 11.64 1.32.42.18.479.659.301 1.02zm1.44-3.3c-.301.42-.841.6-1.262.3-3.239-1.98-8.159-2.58-11.939-1.38-.479.12-1.02-.12-1.14-.6-.12-.48.12-1.021.6-1.141C9.6 9.9 15 10.561 18.72 12.84c.361.181.54.78.241 1.2zm.12-3.36C15.24 8.4 8.82 8.16 5.16 9.301c-.6.179-1.2-.181-1.38-.721-.18-.601.18-1.2.72-1.381 4.26-1.26 11.28-1.02 15.721 1.621.539.3.719 1.02.419 1.56-.299.421-1.02.599-1.559.3z"/></svg>
                </a>
                <?php endif; ?>
                <?php if ($artist['soundcloud']): ?>
                <a href="<?php echo esc_url($artist['soundcloud']); ?>" target="_blank" rel="noopener" title="SoundCloud">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M1.175 12.225c-.051 0-.094.046-.101.1l-.233 2.154.233 2.105c.007.058.05.098.101.098.05 0 .09-.04.099-.098l.255-2.105-.27-2.154c-.009-.06-.052-.1-.102-.1m-.899.828c-.06 0-.091.037-.104.094L0 14.479l.165 1.308c.014.057.045.094.09.094s.089-.037.099-.094l.19-1.308-.19-1.334c-.01-.057-.045-.09-.09-.09m1.83-1.229c-.061 0-.12.045-.12.104l-.21 2.563.225 2.458c0 .06.045.104.106.104.061 0 .12-.044.12-.104l.24-2.458-.24-2.563c0-.06-.045-.104-.12-.104m.945-.089c-.075 0-.135.06-.15.135l-.193 2.64.21 2.544c.016.077.075.138.149.138.075 0 .135-.061.15-.138l.225-2.544-.225-2.64c-.016-.075-.075-.135-.15-.135m1.065.202c-.09 0-.149.075-.165.165l-.176 2.459.176 2.4c.016.09.075.164.165.164.09 0 .164-.074.164-.164l.21-2.4-.21-2.459c0-.09-.074-.165-.164-.165m1.064-.18c-.104 0-.18.09-.18.18l-.165 2.459.165 2.369c0 .104.076.18.18.18.104 0 .18-.076.18-.18l.195-2.369-.18-2.459c0-.09-.09-.18-.195-.18m1.06-.168c-.104 0-.195.09-.195.195l-.15 2.447.15 2.354c0 .12.09.21.195.21.12 0 .195-.09.21-.21l.165-2.354-.165-2.447c-.015-.105-.09-.195-.21-.195m1.064-.142c-.12 0-.21.09-.225.21l-.135 2.4.135 2.325c.015.12.105.21.225.21.119 0 .21-.09.224-.21l.15-2.325-.15-2.4c-.014-.12-.105-.21-.224-.21m1.065.12c-.135 0-.225.105-.225.225l-.12 2.265.12 2.31c0 .135.09.24.225.24.119 0 .224-.105.224-.24l.135-2.31-.135-2.265c0-.12-.089-.225-.224-.225m7.409.074c-.196 0-.359.03-.524.074-.166-1.935-1.783-3.449-3.749-3.449-.481 0-.945.09-1.365.251-.165.06-.21.12-.21.24v6.815c0 .12.09.224.21.24h5.638c1.425 0 2.58-1.155 2.58-2.58 0-1.44-1.155-2.595-2.58-2.595"/></svg>
                </a>
                <?php endif; ?>
                <?php if ($artist['youtube']): ?>
                <a href="<?php echo esc_url($artist['youtube']); ?>" target="_blank" rel="noopener" title="YouTube">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                </a>
                <?php endif; ?>
                <?php if ($artist['instagram']): ?>
                <a href="<?php echo esc_url($artist['instagram']); ?>" target="_blank" rel="noopener" title="Instagram">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C8.74 0 8.333.015 7.053.072 5.775.132 4.905.333 4.14.63c-.789.306-1.459.717-2.126 1.384S.935 3.35.63 4.14C.333 4.905.131 5.775.072 7.053.012 8.333 0 8.74 0 12s.015 3.667.072 4.947c.06 1.277.261 2.148.558 2.913.306.788.717 1.459 1.384 2.126.667.666 1.336 1.079 2.126 1.384.766.296 1.636.499 2.913.558C8.333 23.988 8.74 24 12 24s3.667-.015 4.947-.072c1.277-.06 2.148-.262 2.913-.558.788-.306 1.459-.718 2.126-1.384.666-.667 1.079-1.335 1.384-2.126.296-.765.499-1.636.558-2.913.06-1.28.072-1.687.072-4.947s-.015-3.667-.072-4.947c-.06-1.277-.262-2.149-.558-2.913-.306-.789-.718-1.459-1.384-2.126C21.319 1.347 20.651.935 19.86.63c-.765-.297-1.636-.499-2.913-.558C15.667.012 15.26 0 12 0zm0 2.16c3.203 0 3.585.016 4.85.071 1.17.055 1.805.249 2.227.415.562.217.96.477 1.382.896.419.42.679.819.896 1.381.164.422.36 1.057.413 2.227.057 1.266.07 1.646.07 4.85s-.015 3.585-.074 4.85c-.061 1.17-.256 1.805-.421 2.227-.224.562-.479.96-.899 1.382-.419.419-.824.679-1.38.896-.42.164-1.065.36-2.235.413-1.274.057-1.649.07-4.859.07-3.211 0-3.586-.015-4.859-.074-1.171-.061-1.816-.256-2.236-.421-.569-.224-.96-.479-1.379-.899-.421-.419-.69-.824-.9-1.38-.165-.42-.359-1.065-.42-2.235-.045-1.26-.061-1.649-.061-4.844 0-3.196.016-3.586.061-4.861.061-1.17.255-1.814.42-2.234.21-.57.479-.96.9-1.381.419-.419.81-.689 1.379-.898.42-.166 1.051-.361 2.221-.421 1.275-.045 1.65-.06 4.859-.06l.045.03zm0 3.678c-3.405 0-6.162 2.76-6.162 6.162 0 3.405 2.76 6.162 6.162 6.162 3.405 0 6.162-2.76 6.162-6.162 0-3.405-2.76-6.162-6.162-6.162zM12 16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm7.846-10.405c0 .795-.646 1.44-1.44 1.44-.795 0-1.44-.646-1.44-1.44 0-.794.646-1.439 1.44-1.439.793-.001 1.44.645 1.44 1.439z"/></svg>
                </a>
                <?php endif; ?>
                <?php if ($artist['facebook']): ?>
                <a href="<?php echo esc_url($artist['facebook']); ?>" target="_blank" rel="noopener" title="Facebook">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                </a>
                <?php endif; ?>
                <?php if ($artist['website']): ?>
                <a href="<?php echo esc_url($artist['website']); ?>" target="_blank" rel="noopener" title="Website">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <?php ensemble_after_artist_title($artist_id); ?>
        </div>
        
        <?php ensemble_artist_header($artist_id); ?>
    </header>
    
    <!-- Content -->
    <div class="es-bristol-body">
        <div class="es-bristol-container">
            
            <?php ensemble_artist_social_section($artist_id, $social_links); ?>
            
            <!-- Bio -->
            <?php if (get_the_content()): ?>
            <section class="es-bristol-section es-bristol-bio">
                <?php ensemble_before_artist_content($artist_id); ?>
                <h2 class="es-bristol-section-title"><?php _e('Biography', 'ensemble'); ?></h2>
                <div class="es-bristol-prose">
                    <?php the_content(); ?>
                </div>
                <?php ensemble_after_artist_bio($artist_id); ?>
            </section>
            <?php endif; ?>
            
            <?php ensemble_artist_meta($artist_id, array('genre' => $artist['genre'], 'references' => $artist['references'], 'origin' => $artist['origin'])); ?>
            
            <!-- References / Quote -->
            <?php if ($artist['references']): ?>
            <section class="es-bristol-section es-bristol-references">
                <blockquote class="es-bristol-quote">
                    <?php echo esc_html($artist['references']); ?>
                </blockquote>
            </section>
            <?php endif; ?>
            
            <!-- Gallery Hook -->
            <?php 
            if (ensemble_has_addon_hook('artist_gallery')) {
                echo '<section class="es-bristol-section es-bristol-gallery">';
                echo '<h2 class="es-bristol-section-title">' . __('Gallery', 'ensemble') . '</h2>';
                ensemble_artist_gallery($artist_id);
                echo '</section>';
            }
            ?>
            
            <!-- Events -->
            <?php if ($upcoming_events->have_posts()): ?>
            <section class="es-bristol-section es-bristol-events">
                <h2 class="es-bristol-section-title"><?php _e('Upcoming Shows', 'ensemble'); ?></h2>
                
                <?php ensemble_artist_events($artist_id); ?>
                
                <div class="es-bristol-event-list">
                    <?php while ($upcoming_events->have_posts()): $upcoming_events->the_post(); 
                        $evt = es_load_event_data(get_the_ID());
                        $evt_timestamp = strtotime($evt['date']);
                    ?>
                    <a href="<?php echo esc_url($evt['permalink']); ?>" class="es-bristol-event-row">
                        <time class="es-bristol-event-date">
                            <span class="es-bristol-event-day"><?php echo date_i18n('d', $evt_timestamp); ?></span>
                            <span class="es-bristol-event-month"><?php echo date_i18n('M', $evt_timestamp); ?></span>
                        </time>
                        <div class="es-bristol-event-info">
                            <h4><?php echo esc_html($evt['title']); ?></h4>
                            <?php if ($evt['location']): ?>
                                <span><?php echo esc_html($evt['location']['name']); ?></span>
                            <?php endif; ?>
                        </div>
                        <span class="es-bristol-event-arrow">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M5 12h14M12 5l7 7-7 7"/>
                            </svg>
                        </span>
                    </a>
                    <?php endwhile; ?>
                </div>
                
                <?php ensemble_after_artist_events($artist_id, $upcoming_events); ?>
            </section>
            <?php endif; wp_reset_postdata(); ?>
            
            <?php 
            if (ensemble_has_addon_hook('artist_sidebar')) {
                ensemble_artist_sidebar($artist_id);
            }
            ?>
            
            <?php ensemble_artist_footer($artist_id); ?>
            
        </div>
    </div>
    
    <?php endwhile; endif; ?>
    
    <?php ensemble_after_artist($artist_id, $artist); ?>
    
</div>

<!-- Theme Toggle -->
<button class="es-bristol-theme-toggle" onclick="toggleBristolTheme()" aria-label="Toggle theme">
    <svg class="icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="5"/>
        <line x1="12" y1="1" x2="12" y2="3"/>
        <line x1="12" y1="21" x2="12" y2="23"/>
        <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>
        <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
        <line x1="1" y1="12" x2="3" y2="12"/>
        <line x1="21" y1="12" x2="23" y2="12"/>
        <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/>
        <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
    </svg>
    <svg class="icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
    </svg>
</button>

<script>
function toggleBristolTheme() {
    const root = document.querySelector('.es-bristol');
    if (root) {
        root.classList.toggle('es-mode-light');
        localStorage.setItem('es-bristol-theme', root.classList.contains('es-mode-light') ? 'light' : 'dark');
    }
}
document.addEventListener('DOMContentLoaded', function() {
    const saved = localStorage.getItem('es-bristol-theme');
    if (saved === 'light') {
        document.querySelector('.es-bristol')?.classList.add('es-mode-light');
    }
});
</script>

<?php get_footer(); ?>
