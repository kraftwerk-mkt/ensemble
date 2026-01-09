<?php
/**
 * Single Gallery Template
 * 
 * Displays a single gallery page
 *
 * @package Ensemble
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

get_header();

// Get gallery data
$gallery_id = get_the_ID();
$manager = new ES_Gallery_Manager();
$gallery = $manager->get_gallery($gallery_id);

if (!$gallery) {
    get_template_part('404');
    return;
}

// Get active layout
$layout = class_exists('ES_Layout_Sets') ? ES_Layout_Sets::get_active_set() : 'classic';

// Enqueue gallery styles
wp_enqueue_style('ensemble-gallery', ENSEMBLE_PLUGIN_URL . 'assets/css/gallery.css', array(), ENSEMBLE_VERSION);
wp_enqueue_script('ensemble-gallery-lightbox', ENSEMBLE_PLUGIN_URL . 'assets/js/gallery-lightbox.js', array(), ENSEMBLE_VERSION, true);
?>

<div class="es-single-gallery es-layout-<?php echo esc_attr($layout); ?>">
    <div class="es-container">
        
        <!-- Gallery Header -->
        <header class="es-single-gallery__header">
            <h1 class="es-single-gallery__title"><?php echo esc_html($gallery['title']); ?></h1>
            
            <?php if (!empty($gallery['category'])): ?>
            <div class="es-single-gallery__meta">
                <span class="es-single-gallery__category"><?php echo esc_html($gallery['category']); ?></span>
                <span class="es-single-gallery__count"><?php echo esc_html($gallery['image_count']); ?> <?php _e('Images', 'ensemble'); ?></span>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($gallery['description'])): ?>
            <div class="es-single-gallery__description">
                <?php echo wp_kses_post($gallery['description']); ?>
            </div>
            <?php endif; ?>
            
            <?php 
            // Show linked content
            if ($gallery['linked_event'] || $gallery['linked_artist'] || $gallery['linked_location']):
            ?>
            <div class="es-single-gallery__links">
                <?php if ($gallery['linked_event']): ?>
                <a href="<?php echo esc_url($gallery['linked_event']['url']); ?>" class="es-single-gallery__link">
                    <span class="es-single-gallery__link-label"><?php echo esc_html(ES_Label_System::get_label('event', false)); ?>:</span>
                    <?php echo esc_html($gallery['linked_event']['title']); ?>
                </a>
                <?php endif; ?>
                
                <?php if ($gallery['linked_artist']): ?>
                <a href="<?php echo esc_url($gallery['linked_artist']['url']); ?>" class="es-single-gallery__link">
                    <span class="es-single-gallery__link-label"><?php echo esc_html(ES_Label_System::get_label('artist', false)); ?>:</span>
                    <?php echo esc_html($gallery['linked_artist']['title']); ?>
                </a>
                <?php endif; ?>
                
                <?php if ($gallery['linked_location']): ?>
                <a href="<?php echo esc_url($gallery['linked_location']['url']); ?>" class="es-single-gallery__link">
                    <span class="es-single-gallery__link-label"><?php echo esc_html(ES_Label_System::get_label('location', false)); ?>:</span>
                    <?php echo esc_html($gallery['linked_location']['title']); ?>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </header>
        
        <!-- Gallery Content -->
        <?php 
        echo do_shortcode('[ensemble_gallery id="' . $gallery_id . '"]');
        ?>
        
    </div>
</div>

<style>
/* Single Gallery Page Styles */
.es-single-gallery {
    padding: var(--ensemble-section-spacing, 60px) 0;
}

.es-single-gallery__header {
    margin-bottom: 40px;
    text-align: center;
}

.es-single-gallery__title {
    font-family: var(--ensemble-font-heading, inherit);
    font-size: var(--ensemble-h1-size, 48px);
    font-weight: var(--ensemble-heading-weight, 600);
    color: var(--ensemble-text, #1a1a1a);
    margin: 0 0 16px;
}

.es-single-gallery__meta {
    display: flex;
    justify-content: center;
    gap: 24px;
    font-size: var(--ensemble-body-size, 16px);
    color: var(--ensemble-text-secondary, #666666);
    margin-bottom: 16px;
}

.es-single-gallery__category {
    font-weight: 500;
}

.es-single-gallery__description {
    max-width: 720px;
    margin: 0 auto 24px;
    font-size: var(--ensemble-body-size, 16px);
    line-height: 1.7;
    color: var(--ensemble-text, #1a1a1a);
}

.es-single-gallery__links {
    display: flex;
    justify-content: center;
    gap: 16px;
    flex-wrap: wrap;
}

.es-single-gallery__link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    background: var(--ensemble-card-bg, #ffffff);
    border: 1px solid var(--ensemble-card-border, #e8e8e8);
    border-radius: var(--ensemble-button-radius, 8px);
    color: var(--ensemble-text, #1a1a1a);
    text-decoration: none;
    font-size: var(--ensemble-small-size, 14px);
    transition: all 0.2s ease;
}

.es-single-gallery__link:hover {
    background: var(--ensemble-primary, #0066cc);
    border-color: var(--ensemble-primary, #0066cc);
    color: #ffffff;
}

.es-single-gallery__link-label {
    color: var(--ensemble-text-secondary, #666666);
}

.es-single-gallery__link:hover .es-single-gallery__link-label {
    color: rgba(255, 255, 255, 0.8);
}

/* Dark mode */
.es-mode-dark .es-single-gallery__title {
    color: var(--ensemble-text, #f5f5f5);
}

.es-mode-dark .es-single-gallery__description {
    color: var(--ensemble-text, #f5f5f5);
}
</style>

<?php
get_footer();
