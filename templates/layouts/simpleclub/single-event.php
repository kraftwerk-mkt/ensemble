<?php
/**
 * Single Event Template - SIMPLE CLUB LAYOUT
 * 
 * Clean club-style layout with:
 * - Location prominently displayed in header
 * - Description, Genres, Artists, Additional Info in main content
 * - Action buttons (Tickets, External Link, Route) in a row
 * - Fading gallery in sidebar with lightbox
 *
 * @package Ensemble
 * @version 1.0.0
 */

if (!defined('ABSPATH')) exit;

get_header();

// Enqueue base CSS
wp_enqueue_style('ensemble-base', ENSEMBLE_PLUGIN_URL . 'assets/css/layouts/ensemble-base.css', array(), ENSEMBLE_VERSION);
wp_enqueue_style('ensemble-shortcodes', ENSEMBLE_PLUGIN_URL . 'assets/css/shortcodes.css', array('ensemble-base'), ENSEMBLE_VERSION);

// Enqueue Simple Club Layout CSS
wp_enqueue_style('ensemble-layout-simpleclub', ENSEMBLE_PLUGIN_URL . 'templates/layouts/simpleclub/style.css', array('ensemble-base', 'ensemble-shortcodes'), ENSEMBLE_VERSION);

// Load Montserrat font
wp_enqueue_style('ensemble-simpleclub-font', 'https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap', array(), ENSEMBLE_VERSION);

$event_id = get_the_ID();
$event = es_load_event_data($event_id);
$is_cancelled = ($event['status'] === 'cancelled');

// Get location data
$location_name = '';
$location_address = '';
if (!empty($event['location'])) {
    $loc = $event['location'];
    $location_name = !empty($loc['display_name']) ? $loc['display_name'] : ($loc['name'] ?? '');
    
    // Format address for route planning
    $address_parts = array();
    if (!empty($loc['street'])) $address_parts[] = $loc['street'];
    if (!empty($loc['city'])) $address_parts[] = $loc['city'];
    if (!empty($loc['postal_code'])) $address_parts[] = $loc['postal_code'];
    $location_address = implode(', ', $address_parts);
}
?>

<div class="es-simpleclub-single es-simpleclub-event">
    
    <?php ensemble_before_event($event_id); ?>
    
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
            
            <?php if ($event['status'] && $event['status'] !== 'publish'): ?>
            <span class="es-simpleclub-hero-status es-status-<?php echo esc_attr($event['status']); ?>">
                <?php echo esc_html(function_exists('ensemble_get_status_label') ? ensemble_get_status_label($event['status']) : ucfirst($event['status'])); ?>
            </span>
            <?php endif; ?>
            
            <?php if (!empty($event['badge_label'])): ?>
            <span class="es-simpleclub-hero-badge es-badge-<?php echo esc_attr($event['badge_raw'] ?: 'custom'); ?>">
                <?php echo esc_html($event['badge_label']); ?>
            </span>
            <?php endif; ?>
            
            <?php if ($event['date']): ?>
            <time class="es-simpleclub-hero-date" datetime="<?php echo esc_attr($event['date']); ?>">
                <?php echo date_i18n('l, j. F Y', strtotime($event['date'])); ?>
                <?php if ($event['formatted_time']): ?>
                    <span class="es-simpleclub-hero-time"><?php echo esc_html($event['formatted_time']); ?></span>
                <?php endif; ?>
            </time>
            <?php endif; ?>
            
            <h1 class="es-simpleclub-hero-title"><?php the_title(); ?></h1>
            
            <!-- Location - Large under title, same style -->
            <?php if ($location_name): ?>
            <div class="es-simpleclub-hero-location">
                <?php echo esc_html($location_name); ?>
            </div>
            <?php endif; ?>
            
            <?php ensemble_after_title($event_id); ?>
        </div>
        
        <?php ensemble_event_header($event_id); ?>
    </header>
    
    <!-- Content -->
    <div class="es-simpleclub-body">
        <div class="es-simpleclub-container">
            
            <div class="es-simpleclub-layout-grid">
                
                <!-- Main -->
                <main class="es-simpleclub-main">
                    
                    <?php ensemble_before_content($event_id); ?>
                    
                    <!-- Description -->
                    <?php if (($event['description'] || get_the_content()) && (!function_exists('ensemble_show_section') || ensemble_show_section('description'))): ?>
                    <section class="es-simpleclub-section es-simpleclub-description">
                        <?php if (function_exists('ensemble_show_header') && ensemble_show_header('description')): ?>
                        <h2 class="es-simpleclub-section-title"><?php _e('About', 'ensemble'); ?></h2>
                        <?php endif; ?>
                        <div class="es-simpleclub-prose">
                            <?php 
                            if (!empty($event['description'])) {
                                echo wpautop($event['description']);
                            } else {
                                the_content();
                            }
                            ?>
                        </div>
                    </section>
                    <?php endif; ?>
                    
                    <!-- Artists -->
                    <?php if (!empty($event['artists']) && (!function_exists('ensemble_show_section') || ensemble_show_section('artists'))): ?>
                    <section class="es-simpleclub-section es-simpleclub-artists">
                        <?php if (!function_exists('ensemble_show_header') || ensemble_show_header('artists')): ?>
                        <h2 class="es-simpleclub-section-title"><?php _e('Line-Up', 'ensemble'); ?></h2>
                        <?php endif; ?>
                        
                        <?php 
                        // Group artists by venue
                        $artist_groups = es_group_artists_by_venue($event['artists']);
                        $venue_config = !empty($event['venue_config']) ? $event['venue_config'] : array();
                        ?>
                        
                        <?php 
                        // Show event genres as meta if no venues are used
                        if (!$artist_groups['has_venues'] && !empty($event['genres'])): 
                        ?>
                        <div class="es-simpleclub-genre-meta">
                            <?php foreach ($event['genres'] as $genre): ?>
                            <span class="es-simpleclub-genre-tag"><?php echo esc_html(is_object($genre) ? $genre->name : $genre); ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($artist_groups['has_venues']): ?>
                        <!-- Artists grouped by Room/Stage -->
                        <?php foreach ($artist_groups['groups'] as $venue_name => $venue_artists): 
                            // Get custom venue name from config if available
                            $display_venue_name = $venue_name;
                            $venue_genres = array();
                            if (isset($venue_config[$venue_name])) {
                                if (!empty($venue_config[$venue_name]['customName'])) {
                                    $display_venue_name = $venue_config[$venue_name]['customName'];
                                }
                                if (!empty($venue_config[$venue_name]['genres'])) {
                                    // Get genre names from IDs
                                    foreach ($venue_config[$venue_name]['genres'] as $genre_id) {
                                        $genre_term = get_term($genre_id, 'ensemble_genre');
                                        if ($genre_term && !is_wp_error($genre_term)) {
                                            $venue_genres[] = $genre_term->name;
                                        }
                                    }
                                }
                            }
                        ?>
                        <div class="es-simpleclub-venue-group">
                            <div class="es-simpleclub-venue-header">
                                <h3 class="es-simpleclub-venue-title">
                                    <?php echo esc_html($display_venue_name); ?>
                                </h3>
                                <?php if (!empty($venue_genres)): ?>
                                <div class="es-simpleclub-venue-genres">
                                    <?php foreach ($venue_genres as $genre): ?>
                                    <span class="es-simpleclub-venue-genre"><?php echo esc_html($genre); ?></span>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="es-simpleclub-artist-grid">
                                <?php foreach ($venue_artists as $artist): 
                                    $artist_has_link = !empty($artist['link_enabled']) && !empty($artist['link_url']);
                                    $artist_target = (!empty($artist['link_external']) && !empty($artist['link_new_tab'])) ? ' target="_blank" rel="noopener noreferrer"' : '';
                                    $artist_tag = $artist_has_link ? 'a' : 'div';
                                    $artist_href = $artist_has_link ? ' href="' . esc_url($artist['link_url']) . '"' . $artist_target : '';
                                ?>
                                <<?php echo $artist_tag; ?><?php echo $artist_href; ?> class="es-simpleclub-artist-item">
                                    <?php if (!function_exists('ensemble_show_lineup') || ensemble_show_lineup('artist_image')): ?>
                                    <div class="es-simpleclub-artist-img">
                                        <?php if (!empty($artist['image'])): ?>
                                            <img src="<?php echo esc_url($artist['image']); ?>" alt="<?php echo esc_attr($artist['name']); ?>">
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                    <div class="es-simpleclub-artist-info">
                                        <h4><?php echo esc_html($artist['name']); ?></h4>
                                        <?php if (!empty($artist['genre']) && !empty($event['show_artist_genres'])): ?>
                                            <span class="es-simpleclub-artist-genre"><?php echo esc_html($artist['genre']); ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($artist['references'])): ?>
                                            <span class="es-simpleclub-artist-references"><?php echo esc_html($artist['references']); ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($artist['time_formatted']) && (!function_exists('ensemble_show_lineup') || ensemble_show_lineup('artist_time'))): ?>
                                            <span class="es-simpleclub-artist-time"><?php echo esc_html($artist['time_formatted']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </<?php echo $artist_tag; ?>>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <?php else: ?>
                        <!-- Simple artist grid -->
                        <div class="es-simpleclub-artist-grid">
                            <?php foreach ($event['artists'] as $artist): 
                                $artist_has_link = !empty($artist['link_enabled']) && !empty($artist['link_url']);
                                $artist_target = (!empty($artist['link_external']) && !empty($artist['link_new_tab'])) ? ' target="_blank" rel="noopener noreferrer"' : '';
                                $artist_tag = $artist_has_link ? 'a' : 'div';
                                $artist_href = $artist_has_link ? ' href="' . esc_url($artist['link_url']) . '"' . $artist_target : '';
                            ?>
                            <<?php echo $artist_tag; ?><?php echo $artist_href; ?> class="es-simpleclub-artist-item">
                                <?php if (!function_exists('ensemble_show_lineup') || ensemble_show_lineup('artist_image')): ?>
                                <div class="es-simpleclub-artist-img">
                                    <?php if (!empty($artist['image'])): ?>
                                        <img src="<?php echo esc_url($artist['image']); ?>" alt="<?php echo esc_attr($artist['name']); ?>">
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                                <div class="es-simpleclub-artist-info">
                                    <h4><?php echo esc_html($artist['name']); ?></h4>
                                    <?php if (!empty($artist['genre']) && !empty($event['show_artist_genres'])): ?>
                                        <span class="es-simpleclub-artist-genre"><?php echo esc_html($artist['genre']); ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($artist['references'])): ?>
                                        <span class="es-simpleclub-artist-references"><?php echo esc_html($artist['references']); ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($artist['time_formatted']) && (!function_exists('ensemble_show_lineup') || ensemble_show_lineup('artist_time'))): ?>
                                        <span class="es-simpleclub-artist-time"><?php echo esc_html($artist['time_formatted']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </<?php echo $artist_tag; ?>>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php ensemble_artist_section($event_id, $event['artists']); ?>
                    </section>
                    <?php endif; ?>
                    
                    <?php ensemble_after_description($event_id); ?>
                    
                    <!-- Additional Information -->
                    <?php if (!empty($event['additional_info'])): ?>
                    <section class="es-simpleclub-section es-simpleclub-additional-info">
                        <?php if (!function_exists('ensemble_show_header') || ensemble_show_header('additional_info')): ?>
                        <h2 class="es-simpleclub-section-title"><?php _e('Additional Information', 'ensemble'); ?></h2>
                        <?php endif; ?>
                        <div class="es-simpleclub-prose es-simpleclub-info-content">
                            <?php echo wpautop($event['additional_info']); ?>
                        </div>
                    </section>
                    <?php endif; ?>
                    
                    <!-- Action Buttons Row: Tickets - External Link - Route -->
                    <section class="es-simpleclub-section es-simpleclub-actions">
                        <div class="es-simpleclub-action-buttons">
                            
                            <!-- Tickets Button -->
                            <?php if ($event['ticket_url'] && !$is_cancelled): ?>
                            <a href="<?php echo esc_url($event['ticket_url']); ?>" class="es-simpleclub-btn es-simpleclub-btn-primary" target="_blank" rel="noopener">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M2 9a3 3 0 0 1 3 3v.5a1.5 1.5 0 0 0 3 0V12a3 3 0 0 1 3-3h6a3 3 0 0 1 3 3v.5a1.5 1.5 0 0 0 3 0V12a3 3 0 0 1 3 3v6a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V9Z"/>
                                    <path d="M2 9V5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v4"/>
                                </svg>
                                <?php echo esc_html($event['button_text'] ?: __('Tickets', 'ensemble')); ?>
                                <?php if ($event['price']): ?>
                                <span class="es-simpleclub-btn-price"><?php echo esc_html($event['price']); ?></span>
                                <?php endif; ?>
                            </a>
                            <?php endif; ?>
                            
                            <!-- External Link Button -->
                            <?php if (!empty($event['external_url'])): ?>
                            <a href="<?php echo esc_url($event['external_url']); ?>" class="es-simpleclub-btn es-simpleclub-btn-secondary" target="_blank" rel="noopener">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                                    <polyline points="15 3 21 3 21 9"/>
                                    <line x1="10" y1="14" x2="21" y2="3"/>
                                </svg>
                                <?php echo esc_html($event['external_text'] ?: __('More Info', 'ensemble')); ?>
                            </a>
                            <?php endif; ?>
                            
                            <!-- Route Button -->
                            <?php if ($location_address): ?>
                            <a href="https://www.google.com/maps/dir/?api=1&destination=<?php echo urlencode($location_address); ?>" class="es-simpleclub-btn es-simpleclub-btn-secondary" target="_blank" rel="noopener">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polygon points="3 11 22 2 13 21 11 13 3 11"/>
                                </svg>
                                <?php _e('Route planen', 'ensemble'); ?>
                            </a>
                            <?php endif; ?>
                            
                        </div>
                    </section>
                    
                    <!-- Share -->
                    <?php 
                    ensemble_social_share($event_id, array(
                        'title' => $event['title'],
                        'permalink' => $event['permalink'],
                        'featured_image' => $event['featured_image'],
                    ));
                    ?>
                    
                    <?php ensemble_event_footer($event_id); ?>
                    
                </main>
                
                <!-- Sidebar -->
                <aside class="es-simpleclub-aside">
                    
                    <!-- Fading Gallery with Lightbox -->
                    <?php 
                    $gallery_images = !empty($event['gallery']) ? $event['gallery'] : array();
                    // Filter out invalid entries and convert IDs to URLs
                    $valid_gallery = array();
                    foreach ($gallery_images as $image) {
                        if (is_array($image) && !empty($image['url'])) {
                            // Already has URL - use full size
                            $valid_gallery[] = array(
                                'url' => $image['url'],
                                'full' => $image['url'],
                                'alt' => $image['alt'] ?? ''
                            );
                        } elseif (is_array($image) && !empty($image['ID'])) {
                            // Has ID, get full URL
                            $img_full = wp_get_attachment_image_src($image['ID'], 'full');
                            if ($img_full) {
                                $valid_gallery[] = array(
                                    'url' => $img_full[0],
                                    'full' => $img_full[0],
                                    'alt' => get_post_meta($image['ID'], '_wp_attachment_image_alt', true)
                                );
                            }
                        } elseif (is_numeric($image) && $image > 0) {
                            // Just an ID - get full size
                            $img_full = wp_get_attachment_image_src($image, 'full');
                            if ($img_full) {
                                $valid_gallery[] = array(
                                    'url' => $img_full[0],
                                    'full' => $img_full[0],
                                    'alt' => get_post_meta($image, '_wp_attachment_image_alt', true)
                                );
                            }
                        } elseif (is_string($image) && filter_var($image, FILTER_VALIDATE_URL)) {
                            // Direct URL string
                            $valid_gallery[] = array(
                                'url' => $image,
                                'full' => $image,
                                'alt' => ''
                            );
                        }
                    }
                    
                    if (!empty($valid_gallery)):
                    ?>
                    <div class="es-simpleclub-gallery-card">
                        <div class="es-simpleclub-gallery-fader" data-gallery-count="<?php echo count($valid_gallery); ?>">
                            <?php foreach ($valid_gallery as $index => $img): ?>
                            <a href="<?php echo esc_url($img['full']); ?>" 
                               class="es-simpleclub-gallery-slide <?php echo $index === 0 ? 'active' : ''; ?>" 
                               data-lightbox="gallery-<?php echo $event_id; ?>"
                               data-index="<?php echo $index; ?>">
                                <img src="<?php echo esc_url($img['url']); ?>" alt="<?php echo esc_attr($img['alt']); ?>">
                            </a>
                            <?php endforeach; ?>
                            
                            <?php if (count($valid_gallery) > 1): ?>
                            <div class="es-simpleclub-gallery-dots">
                                <?php for ($i = 0; $i < count($valid_gallery); $i++): ?>
                                <span class="es-simpleclub-gallery-dot <?php echo $i === 0 ? 'active' : ''; ?>" data-index="<?php echo $i; ?>"></span>
                                <?php endfor; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Reservation Area -->
                    <?php 
                    ob_start();
                    ensemble_event_sidebar($event_id);
                    $reservation_content = ob_get_clean();
                    if (!empty(trim($reservation_content))):
                    ?>
                    <div class="es-simpleclub-reservation-card">
                        <?php echo $reservation_content; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Info Card -->
                    <div class="es-simpleclub-info-card">
                        
                        <!-- Location -->
                        <?php if ($event['location']): 
                            $loc = $event['location'];
                            $loc_has_link = !empty($loc['link_enabled']) && !empty($loc['link_url']);
                            $loc_target = (!empty($loc['link_external']) && !empty($loc['link_new_tab'])) ? ' target="_blank" rel="noopener noreferrer"' : '';
                            $loc_formatted = function_exists('ensemble_format_location_address') 
                                ? ensemble_format_location_address($loc) 
                                : array('name' => (!empty($loc['display_name']) ? $loc['display_name'] : $loc['name']), 'address_line' => $loc['city'] ?? '');
                        ?>
                        <div class="es-simpleclub-info-row">
                            <span class="es-simpleclub-info-label"><?php _e('Location', 'ensemble'); ?></span>
                            <?php if ($loc_has_link): ?>
                            <a href="<?php echo esc_url($loc['link_url']); ?>" class="es-simpleclub-info-value es-simpleclub-info-link"<?php echo $loc_target; ?>>
                                <?php echo esc_html($loc_formatted['name']); ?>
                            </a>
                            <?php else: ?>
                            <span class="es-simpleclub-info-value"><?php echo esc_html($loc_formatted['name']); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($loc_formatted['address_line'])): ?>
                            <span class="es-simpleclub-info-sub"><?php echo esc_html($loc_formatted['address_line']); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php ensemble_after_location($event_id, $event['location'] ?: array()); ?>
                        
                        <!-- Price (if no ticket button) -->
                        <?php if ($event['price'] && !$event['ticket_url'] && (!function_exists('ensemble_show_meta') || ensemble_show_meta('price'))): ?>
                        <div class="es-simpleclub-info-row">
                            <span class="es-simpleclub-info-label"><?php _e('Eintritt', 'ensemble'); ?></span>
                            <span class="es-simpleclub-info-value es-simpleclub-info-price"><?php echo esc_html($event['price']); ?></span>
                            <?php if (!empty($event['price_note'])): ?>
                            <span class="es-simpleclub-info-sub"><?php echo esc_html($event['price_note']); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Facebook Event -->
                        <?php if (!empty($event['facebook_url'])): ?>
                        <div class="es-simpleclub-info-row es-simpleclub-social-row">
                            <a href="<?php echo esc_url($event['facebook_url']); ?>" class="es-simpleclub-facebook-link" target="_blank" rel="noopener noreferrer">
                                <span class="dashicons dashicons-facebook-alt"></span>
                                <?php _e('Facebook Event', 'ensemble'); ?>
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <?php ensemble_event_meta($event_id, $event); ?>
                        
                        <?php ensemble_after_tickets($event_id); ?>
                        
                    </div>
                    
                </aside>
                
            </div>
            
            <!-- Related Events -->
            <?php 
            ensemble_related_events($event_id, array(
                'categories' => $event['categories'],
                'location_id' => $event['location_id'],
                'artist_ids' => !empty($event['artists']) ? array_column($event['artists'], 'id') : array(),
            ));
            ?>
            
        </div>
    </div>
    
    <?php endwhile; endif; ?>
    
    <?php 
    ensemble_after_event($event_id, array(
        'title' => $event['title'],
        'permalink' => $event['permalink'],
        'featured_image' => $event['featured_image'],
    ));
    ?>
    
</div>

<!-- Lightbox -->
<div class="es-simpleclub-lightbox" id="es-simpleclub-lightbox">
    <button class="es-simpleclub-lightbox-close">&times;</button>
    <button class="es-simpleclub-lightbox-prev">&lsaquo;</button>
    <button class="es-simpleclub-lightbox-next">&rsaquo;</button>
    <div class="es-simpleclub-lightbox-content">
        <img src="" alt="">
    </div>
</div>

<script>
(function() {
    // Gallery Fader
    const fader = document.querySelector('.es-simpleclub-gallery-fader');
    if (fader && parseInt(fader.dataset.galleryCount) > 1) {
        const slides = fader.querySelectorAll('.es-simpleclub-gallery-slide');
        const dots = fader.querySelectorAll('.es-simpleclub-gallery-dot');
        let current = 0;
        const total = slides.length;
        
        function showSlide(index) {
            slides.forEach((s, i) => {
                s.classList.toggle('active', i === index);
            });
            dots.forEach((d, i) => {
                d.classList.toggle('active', i === index);
            });
        }
        
        // Auto-fade
        setInterval(function() {
            current = (current + 1) % total;
            showSlide(current);
        }, 4000);
        
        // Click on dots
        dots.forEach((dot, i) => {
            dot.addEventListener('click', function() {
                current = i;
                showSlide(current);
            });
        });
    }
    
    // Lightbox
    const lightbox = document.getElementById('es-simpleclub-lightbox');
    const lightboxImg = lightbox ? lightbox.querySelector('img') : null;
    const galleryLinks = document.querySelectorAll('.es-simpleclub-gallery-slide');
    let lightboxImages = [];
    let lightboxIndex = 0;
    
    galleryLinks.forEach((link, index) => {
        lightboxImages.push(link.href);
        link.addEventListener('click', function(e) {
            e.preventDefault();
            lightboxIndex = index;
            openLightbox(link.href);
        });
    });
    
    function openLightbox(src) {
        if (lightbox && lightboxImg) {
            lightboxImg.src = src;
            lightbox.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }
    
    function closeLightbox() {
        if (lightbox) {
            lightbox.classList.remove('active');
            document.body.style.overflow = '';
        }
    }
    
    function nextImage() {
        lightboxIndex = (lightboxIndex + 1) % lightboxImages.length;
        lightboxImg.src = lightboxImages[lightboxIndex];
    }
    
    function prevImage() {
        lightboxIndex = (lightboxIndex - 1 + lightboxImages.length) % lightboxImages.length;
        lightboxImg.src = lightboxImages[lightboxIndex];
    }
    
    if (lightbox) {
        lightbox.querySelector('.es-simpleclub-lightbox-close').addEventListener('click', closeLightbox);
        lightbox.querySelector('.es-simpleclub-lightbox-prev').addEventListener('click', prevImage);
        lightbox.querySelector('.es-simpleclub-lightbox-next').addEventListener('click', nextImage);
        lightbox.addEventListener('click', function(e) {
            if (e.target === lightbox) closeLightbox();
        });
        
        document.addEventListener('keydown', function(e) {
            if (!lightbox.classList.contains('active')) return;
            if (e.key === 'Escape') closeLightbox();
            if (e.key === 'ArrowRight') nextImage();
            if (e.key === 'ArrowLeft') prevImage();
        });
    }
})();
</script>

<?php get_footer(); ?>
