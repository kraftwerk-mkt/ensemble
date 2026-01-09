<?php
/**
 * Staff Card Template (Single Person)
 * 
 * @package Ensemble
 * @subpackage Addons/Staff
 * 
 * Variables:
 * @var array  $staff   Staff member data
 * @var string $layout  Layout type (card, full, compact)
 */

if (!defined('ABSPATH')) {
    exit;
}

$layout = isset($layout) ? $layout : 'card';
?>

<?php if ($layout === 'compact') : ?>
    
    <div class="es-staff-compact" itemscope itemtype="https://schema.org/Person">
        <?php if ($staff['featured_image']) : ?>
            <img src="<?php echo esc_url($staff['featured_image']); ?>" 
                 alt="<?php echo esc_attr($staff['name']); ?>"
                 class="es-staff-compact__image"
                 itemprop="image">
        <?php endif; ?>
        <div class="es-staff-compact__info">
            <strong itemprop="name"><?php echo esc_html($staff['name']); ?></strong>
            <?php if ($staff['position']) : ?>
                <span itemprop="jobTitle"><?php echo esc_html($staff['position']); ?></span>
            <?php endif; ?>
        </div>
    </div>
    
<?php elseif ($layout === 'full') : ?>
    
    <div class="es-staff-full" itemscope itemtype="https://schema.org/Person">
        <div class="es-staff-full__header">
            <?php if ($staff['featured_image']) : ?>
                <div class="es-staff-full__image">
                    <img src="<?php echo esc_url($staff['featured_image_full'] ?: $staff['featured_image']); ?>" 
                         alt="<?php echo esc_attr($staff['name']); ?>"
                         itemprop="image">
                </div>
            <?php endif; ?>
            
            <div class="es-staff-full__intro">
                <h3 class="es-staff-full__name" itemprop="name">
                    <a href="<?php echo esc_url($staff['permalink']); ?>">
                        <?php echo esc_html($staff['name']); ?>
                    </a>
                </h3>
                
                <?php if ($staff['position']) : ?>
                    <p class="es-staff-full__position" itemprop="jobTitle">
                        <?php echo esc_html($staff['position']); ?>
                    </p>
                <?php endif; ?>
                
                <?php if ($staff['department']) : ?>
                    <p class="es-staff-full__department">
                        <?php echo esc_html($staff['department']); ?>
                    </p>
                <?php endif; ?>
                
                <div class="es-staff-full__contact">
                    <?php if ($staff['email']) : ?>
                        <a href="mailto:<?php echo esc_attr($staff['email']); ?>" itemprop="email">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16">
                                <path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                            </svg>
                            <?php echo esc_html($staff['email']); ?>
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($staff['phone']) : ?>
                        <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $staff['phone'])); ?>" itemprop="telephone">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16">
                                <path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/>
                            </svg>
                            <?php echo esc_html($staff['phone']); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <?php if ($staff['description']) : ?>
            <div class="es-staff-full__bio" itemprop="description">
                <?php echo wp_kses_post(wpautop($staff['description'])); ?>
            </div>
        <?php endif; ?>
    </div>
    
<?php else : // Default card layout ?>
    
    <div class="es-staff-card-single" itemscope itemtype="https://schema.org/Person">
        <?php if ($staff['featured_image']) : ?>
            <div class="es-staff-card-single__image">
                <a href="<?php echo esc_url($staff['permalink']); ?>">
                    <img src="<?php echo esc_url($staff['featured_image']); ?>" 
                         alt="<?php echo esc_attr($staff['name']); ?>"
                         itemprop="image">
                </a>
            </div>
        <?php else : ?>
            <div class="es-staff-card-single__image es-staff-card-single__image--placeholder">
                <a href="<?php echo esc_url($staff['permalink']); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                    </svg>
                </a>
            </div>
        <?php endif; ?>
        
        <div class="es-staff-card-single__content">
            <h3 class="es-staff-card-single__name" itemprop="name">
                <a href="<?php echo esc_url($staff['permalink']); ?>">
                    <?php echo esc_html($staff['name']); ?>
                </a>
            </h3>
            
            <?php if ($staff['position']) : ?>
                <p class="es-staff-card-single__position" itemprop="jobTitle">
                    <?php echo esc_html($staff['position']); ?>
                </p>
            <?php endif; ?>
            
            <?php if ($staff['department']) : ?>
                <p class="es-staff-card-single__department">
                    <?php echo esc_html($staff['department']); ?>
                </p>
            <?php endif; ?>
            
            <div class="es-staff-card-single__contact">
                <?php if ($staff['email']) : ?>
                    <a href="mailto:<?php echo esc_attr($staff['email']); ?>" itemprop="email" title="<?php esc_attr_e('Email', 'ensemble'); ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="18" height="18">
                            <path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                        </svg>
                    </a>
                <?php endif; ?>
                
                <?php if ($staff['phone']) : ?>
                    <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $staff['phone'])); ?>" itemprop="telephone" title="<?php esc_attr_e('Call', 'ensemble'); ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="18" height="18">
                            <path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/>
                        </svg>
                    </a>
                <?php endif; ?>
                
                <?php if ($staff['website']) : ?>
                    <a href="<?php echo esc_url($staff['website']); ?>" target="_blank" rel="noopener noreferrer" title="<?php esc_attr_e('Website', 'ensemble'); ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="18" height="18">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/>
                        </svg>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
<?php endif; ?>
