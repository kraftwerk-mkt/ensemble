<?php
/**
 * Single Staff Template
 * 
 * @package Ensemble
 * @subpackage Addons/Staff
 * @version 1.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

// Get staff data - use correct method
$staff_addon = ES_Addon_Manager::is_addon_active('staff') ? ES_Addon_Manager::get_active_addon('staff') : null;
$staff_manager = $staff_addon ? $staff_addon->get_staff_manager() : null;
$staff = $staff_manager ? $staff_manager->get_staff(get_the_ID()) : null;

if (!$staff) {
    get_template_part('404');
    return;
}

// Get labels
$staff_label = $staff_addon ? $staff_addon->get_staff_label(false) : __('Contact', 'ensemble');
?>

<div class="es-single-staff">
    <article class="es-single-staff__content" itemscope itemtype="https://schema.org/Person">
        
        <header class="es-single-staff__header">
            <div class="es-single-staff__header-inner">
                <?php if ($staff['featured_image_full']) : ?>
                    <div class="es-single-staff__image">
                        <img src="<?php echo esc_url($staff['featured_image_full']); ?>" 
                             alt="<?php echo esc_attr($staff['name']); ?>"
                             itemprop="image">
                    </div>
                <?php endif; ?>
                
                <div class="es-single-staff__intro">
                    <h1 class="es-single-staff__name" itemprop="name">
                        <?php echo esc_html($staff['name']); ?>
                    </h1>
                    
                    <?php if ($staff['position']) : ?>
                        <p class="es-single-staff__position" itemprop="jobTitle">
                            <?php echo esc_html($staff['position']); ?>
                        </p>
                    <?php endif; ?>
                    
                    <?php if ($staff['department']) : ?>
                        <p class="es-single-staff__department">
                            <?php echo esc_html($staff['department']); ?>
                        </p>
                    <?php endif; ?>
                    
                    <?php if ($staff['responsibility']) : ?>
                        <p class="es-single-staff__responsibility">
                            <strong><?php _e('Responsibility:', 'ensemble'); ?></strong>
                            <?php echo esc_html($staff['responsibility']); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </header>
        
        <div class="es-single-staff__body">
            <div class="es-single-staff__main">
                <?php if ($staff['description']) : ?>
                    <div class="es-single-staff__bio" itemprop="description">
                        <?php echo wp_kses_post(wpautop($staff['description'])); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($staff['abstract_enabled']) : ?>
                    <div class="es-single-staff__form-section">
                        <h2><?php _e('Submit Your Abstract', 'ensemble'); ?></h2>
                        <?php echo do_shortcode('[ensemble_contact_form staff_id="' . $staff['id'] . '"]'); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <aside class="es-single-staff__sidebar">
                <div class="es-single-staff__contact-card">
                    <h3><?php _e('Contact Information', 'ensemble'); ?></h3>
                    
                    <?php if ($staff['email']) : ?>
                        <div class="es-single-staff__contact-item">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
                                <path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                            </svg>
                            <div>
                                <span class="es-single-staff__contact-label"><?php _e('Email', 'ensemble'); ?></span>
                                <a href="mailto:<?php echo esc_attr($staff['email']); ?>" itemprop="email">
                                    <?php echo esc_html($staff['email']); ?>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($staff['phones'])) : ?>
                        <?php foreach ($staff['phones'] as $phone) : ?>
                            <div class="es-single-staff__contact-item">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
                                    <path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/>
                                </svg>
                                <div>
                                    <span class="es-single-staff__contact-label">
                                        <?php 
                                        $phone_types = array(
                                            'office' => __('Office', 'ensemble'),
                                            'mobile' => __('Mobile', 'ensemble'),
                                            'fax'    => __('Fax', 'ensemble'),
                                        );
                                        echo esc_html($phone_types[$phone['type']] ?? $phone['type']);
                                        ?>
                                    </span>
                                    <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $phone['number'])); ?>" itemprop="telephone">
                                        <?php echo esc_html($phone['number']); ?>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <?php if ($staff['office_hours']) : ?>
                        <div class="es-single-staff__contact-item">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
                                <path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/>
                            </svg>
                            <div>
                                <span class="es-single-staff__contact-label"><?php _e('Office Hours', 'ensemble'); ?></span>
                                <span><?php echo esc_html($staff['office_hours']); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($staff['website'] || $staff['linkedin'] || $staff['twitter']) : ?>
                    <div class="es-single-staff__social-card">
                        <h3><?php _e('Connect', 'ensemble'); ?></h3>
                        <div class="es-single-staff__social-links">
                            <?php if ($staff['website']) : ?>
                                <a href="<?php echo esc_url($staff['website']); ?>" 
                                   target="_blank" 
                                   rel="noopener noreferrer"
                                   class="es-single-staff__social-link">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
                                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/>
                                    </svg>
                                    <?php _e('Website', 'ensemble'); ?>
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($staff['linkedin']) : ?>
                                <a href="<?php echo esc_url($staff['linkedin']); ?>" 
                                   target="_blank" 
                                   rel="noopener noreferrer"
                                   class="es-single-staff__social-link">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
                                        <path d="M19 3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14m-.5 15.5v-5.3a3.26 3.26 0 0 0-3.26-3.26c-.85 0-1.84.52-2.32 1.3v-1.11h-2.79v8.37h2.79v-4.93c0-.77.62-1.4 1.39-1.4a1.4 1.4 0 0 1 1.4 1.4v4.93h2.79M6.88 8.56a1.68 1.68 0 0 0 1.68-1.68c0-.93-.75-1.69-1.68-1.69a1.69 1.69 0 0 0-1.69 1.69c0 .93.76 1.68 1.69 1.68m1.39 9.94v-8.37H5.5v8.37h2.77z"/>
                                    </svg>
                                    LinkedIn
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($staff['twitter']) : ?>
                                <a href="<?php echo esc_url($staff['twitter']); ?>" 
                                   target="_blank" 
                                   rel="noopener noreferrer"
                                   class="es-single-staff__social-link">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
                                        <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                                    </svg>
                                    X / Twitter
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </aside>
        </div>
        
    </article>
</div>

<?php get_footer(); ?>
