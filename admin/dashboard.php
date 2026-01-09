<?php
/**
 * Dashboard Page
 * Welcome screen with stats, quick actions, and recent events
 *
 * @package Ensemble
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get dynamic labels
$artist_singular = ES_Label_System::get_label('artist', false);
$artist_plural = ES_Label_System::get_label('artist', true);
$location_singular = ES_Label_System::get_label('location', false);
$location_plural = ES_Label_System::get_label('location', true);

// Get stats
$wizard = new ES_Wizard();
$events_count = count($wizard->get_events());
$artists_count = count($wizard->get_artists());
$locations_count = count($wizard->get_locations());
$categories = $wizard->get_categories();

// Get recent events (last 6)
$recent_events = $wizard->get_events(array(
    'posts_per_page' => 6,
    'orderby' => 'date',
    'order' => 'DESC'
));

// Check if field mapping is configured
$field_mapping = get_option('ensemble_field_mapping', array());
$field_mapping_configured = !empty($field_mapping);

// Get ACF field groups count
$field_groups = ES_Field_Builder::get_field_groups();
$field_groups_count = count($field_groups);
?>

<div class="wrap es-dashboard-wrap">
    <div class="es-dashboard-hero">
        <h1>
            <span class="es-hero-icon"><?php ES_Icons::icon('artist'); ?></span>
            <?php _e('Willkommen bei Ensemble', 'ensemble'); ?>
        </h1>
        <p class="es-hero-subtitle">
            <?php _e('Dein professionelles Event-Management-System', 'ensemble'); ?>
        </p>
        
        <div class="es-hero-actions">
            <a href="<?php echo admin_url('admin.php?page=ensemble-wizard'); ?>" class="button button-primary button-hero">
                <span class="dashicons dashicons-plus-alt"></span>
                <?php _e('Neues Event erstellen', 'ensemble'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=ensemble-calendar'); ?>" class="button button-secondary button-hero">
                <span class="dashicons dashicons-calendar-alt"></span>
                <?php _e('Zum Kalender', 'ensemble'); ?>
            </a>
        </div>
    </div>
    
    <?php if (!$field_mapping_configured): ?>
    <div class="es-dashboard-notice es-notice-warning">
        <div class="es-notice-icon"><?php ES_Icons::icon('warning'); ?></div>
        <div class="es-notice-content">
            <h3><?php _e('Field Mapping noch nicht konfiguriert', 'ensemble'); ?></h3>
            <p><?php _e('The default ACF fields are not yet mapped. Mapping allows Ensemble to work with any ACF field names.', 'ensemble'); ?></p>
            <a href="<?php echo admin_url('admin.php?page=ensemble-settings#field-mapping'); ?>" class="button button-primary">
                <?php _e('Jetzt konfigurieren', 'ensemble'); ?> →
            </a>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="es-dashboard-stats">
        <div class="es-stat-card es-stat-primary">
            <div class="es-stat-icon"><?php ES_Icons::icon('calendar'); ?></div>
            <div class="es-stat-content">
                <div class="es-stat-number"><?php echo number_format_i18n($events_count); ?></div>
                <div class="es-stat-label"><?php echo _n('Event', 'Events', $events_count, 'ensemble'); ?></div>
                <a href="<?php echo admin_url('admin.php?page=ensemble-wizard'); ?>" class="es-stat-link">
                    <?php _e('Verwalten', 'ensemble'); ?> →
                </a>
            </div>
        </div>
        
        <div class="es-stat-card">
            <div class="es-stat-icon"><?php ES_Icons::icon('artist'); ?></div>
            <div class="es-stat-content">
                <div class="es-stat-number"><?php echo number_format_i18n($artists_count); ?></div>
                <div class="es-stat-label"><?php echo ($artists_count === 1) ? $artist_singular : $artist_plural; ?></div>
                <a href="<?php echo admin_url('admin.php?page=ensemble-artists'); ?>" class="es-stat-link">
                    <?php _e('Verwalten', 'ensemble'); ?> →
                </a>
            </div>
        </div>
        
        <div class="es-stat-card">
            <div class="es-stat-icon"><?php ES_Icons::icon('location'); ?></div>
            <div class="es-stat-content">
                <div class="es-stat-number"><?php echo number_format_i18n($locations_count); ?></div>
                <div class="es-stat-label"><?php echo ($locations_count === 1) ? $location_singular : $location_plural; ?></div>
                <a href="<?php echo admin_url('admin.php?page=ensemble-locations'); ?>" class="es-stat-link">
                    <?php _e('Verwalten', 'ensemble'); ?> →
                </a>
            </div>
        </div>
        
        <div class="es-stat-card">
            <div class="es-stat-icon"><?php ES_Icons::icon('category'); ?></div>
            <div class="es-stat-content">
                <div class="es-stat-number"><?php echo number_format_i18n($field_groups_count); ?></div>
                <div class="es-stat-label"><?php echo _n('Field Group', 'Field Groups', $field_groups_count, 'ensemble'); ?></div>
                <a href="<?php echo admin_url('admin.php?page=ensemble-field-builder'); ?>" class="es-stat-link">
                    <?php _e('Verwalten', 'ensemble'); ?> →
                </a>
            </div>
        </div>
        
        <?php 
        // Tickets Add-on Stat Card
        if (class_exists('ES_Addon_Manager') && ES_Addon_Manager::is_addon_active('tickets')): 
            // Count events with tickets
            $events_with_tickets = 0;
            $all_events = $wizard->get_events(array('posts_per_page' => -1));
            foreach ($all_events as $event) {
                $tickets = get_post_meta($event['id'], '_ensemble_tickets', true);
                if (!empty($tickets) && is_array($tickets)) {
                    $events_with_tickets++;
                }
            }
        ?>
        <div class="es-stat-card es-stat-addon">
            <div class="es-stat-icon"><?php ES_Icons::icon('ticket'); ?></div>
            <div class="es-stat-content">
                <div class="es-stat-number"><?php echo number_format_i18n($events_with_tickets); ?></div>
                <div class="es-stat-label"><?php _e('Events mit Tickets', 'ensemble'); ?></div>
                <a href="<?php echo admin_url('admin.php?page=ensemble-addons'); ?>" class="es-stat-link">
                    <?php _e('Add-on Einstellungen', 'ensemble'); ?> →
                </a>
            </div>
        </div>
        <?php endif; ?>
        
        <?php 
        // Reservierungen Add-on Stat Card (falls vorhanden)
        if (class_exists('ES_Addon_Manager') && ES_Addon_Manager::is_addon_active('reservations')): 
        ?>
        <div class="es-stat-card es-stat-addon">
            <div class="es-stat-icon"><?php ES_Icons::icon('calendar_add'); ?></div>
            <div class="es-stat-content">
                <div class="es-stat-number">-</div>
                <div class="es-stat-label"><?php _e('Reservierungen', 'ensemble'); ?></div>
                <a href="<?php echo admin_url('admin.php?page=ensemble-addons'); ?>" class="es-stat-link">
                    <?php _e('Add-on Einstellungen', 'ensemble'); ?> →
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <?php if (!empty($recent_events)): ?>
    <div class="es-dashboard-section">
        <div class="es-section-header">
            <h2><?php _e('Letzte Events', 'ensemble'); ?></h2>
            <a href="<?php echo admin_url('admin.php?page=ensemble-wizard'); ?>" class="button button-secondary">
                <?php _e('Show all', 'ensemble'); ?> →
            </a>
        </div>
        
        <div class="es-recent-events">
            <?php foreach ($recent_events as $event): ?>
            <a href="<?php echo admin_url('admin.php?page=ensemble-wizard&edit=' . $event['id']); ?>" class="es-event-card es-event-card-link">
                <?php if ($event['featured_image']): ?>
                <div class="es-event-image" style="background-image: url('<?php echo esc_url($event['featured_image']); ?>')"></div>
                <?php else: ?>
                <div class="es-event-image es-event-image-placeholder">
                    <span class="es-event-icon"><?php ES_Icons::icon('artist'); ?></span>
                </div>
                <?php endif; ?>
                
                <div class="es-event-content">
                    <h3 class="es-event-title"><?php echo esc_html($event['title']); ?></h3>
                    
                    <div class="es-event-meta">
                        <?php if ($event['date']): ?>
                        <div class="es-event-meta-item">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <?php echo date_i18n(get_option('date_format'), strtotime($event['date'])); ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($event['time']): ?>
                        <div class="es-event-meta-item">
                            <span class="dashicons dashicons-clock"></span>
                            <?php echo esc_html($event['time']); ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($event['location_name']): ?>
                        <div class="es-event-meta-item">
                            <span class="dashicons dashicons-location"></span>
                            <?php echo esc_html($event['location_name']); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($event['categories'])): ?>
                    <div class="es-event-categories">
                        <?php foreach (array_slice($event['categories'], 0, 2) as $category): ?>
                        <span class="es-event-category"><?php echo esc_html($category['name']); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php else: ?>
    <div class="es-dashboard-empty">
        <div class="es-empty-icon"><?php ES_Icons::icon('calendar'); ?></div>
        <h3><?php _e('Noch keine Events', 'ensemble'); ?></h3>
        <p><?php _e('Erstelle dein erstes Event mit dem Event Wizard.', 'ensemble'); ?></p>
        <a href="<?php echo admin_url('admin.php?page=ensemble-wizard'); ?>" class="button button-primary button-large">
            <span class="dashicons dashicons-plus-alt"></span>
            <?php _e('Erstes Event erstellen', 'ensemble'); ?>
        </a>
    </div>
    <?php endif; ?>
    
    <div class="es-dashboard-section">
        <div class="es-section-header">
            <h2><?php _e('Schnellzugriff', 'ensemble'); ?></h2>
        </div>
        
        <div class="es-quick-links">
            <a href="<?php echo admin_url('admin.php?page=ensemble-wizard'); ?>" class="es-quick-link">
                <div class="es-quick-link-icon">✨</div>
                <div class="es-quick-link-content">
                    <h3><?php _e('Event Wizard', 'ensemble'); ?></h3>
                    <p><?php _e('Erstelle neue Events', 'ensemble'); ?></p>
                </div>
            </a>
            
            <a href="<?php echo admin_url('admin.php?page=ensemble-artists'); ?>" class="es-quick-link">
                <div class="es-quick-link-icon"><?php ES_Icons::icon('artist'); ?></div>
                <div class="es-quick-link-content">
                    <h3><?php echo esc_html($artist_plural); ?></h3>
                    <p><?php printf(__('Verwalte %s', 'ensemble'), $artist_plural); ?></p>
                </div>
            </a>
            
            <a href="<?php echo admin_url('admin.php?page=ensemble-locations'); ?>" class="es-quick-link">
                <div class="es-quick-link-icon"><?php ES_Icons::icon('location'); ?></div>
                <div class="es-quick-link-content">
                    <h3><?php echo esc_html($location_plural); ?></h3>
                    <p><?php printf(__('Verwalte %s', 'ensemble'), $location_plural); ?></p>
                </div>
            </a>
            
            <a href="<?php echo admin_url('admin.php?page=ensemble-calendar'); ?>" class="es-quick-link">
                <div class="es-quick-link-icon"><?php ES_Icons::icon('calendar'); ?></div>
                <div class="es-quick-link-content">
                    <h3><?php _e('Kalender', 'ensemble'); ?></h3>
                    <p><?php _e('Monatsansicht', 'ensemble'); ?></p>
                </div>
            </a>
            
            <a href="<?php echo admin_url('admin.php?page=ensemble-field-builder'); ?>" class="es-quick-link">
                <div class="es-quick-link-icon"><?php ES_Icons::icon('category'); ?></div>
                <div class="es-quick-link-content">
                    <h3><?php _e('Field Builder', 'ensemble'); ?></h3>
                    <p><?php _e('Custom Fields verwalten', 'ensemble'); ?></p>
                </div>
            </a>
            
            <a href="<?php echo admin_url('admin.php?page=ensemble-settings'); ?>" class="es-quick-link">
                <div class="es-quick-link-icon"><?php ES_Icons::icon('settings'); ?></div>
                <div class="es-quick-link-content">
                    <h3><?php _e('Einstellungen', 'ensemble'); ?></h3>
                    <p><?php _e('Konfiguration', 'ensemble'); ?></p>
                </div>
            </a>
        </div>
    </div>
</div>
