<?php
/**
 * Taxonomies Manager Template
 *
 * @package Ensemble
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get current post type from configuration
$current_post_type = ensemble_get_post_type();

// Get taxonomies
$categories = get_terms(array(
    'taxonomy' => 'ensemble_category',
    'hide_empty' => false,
));

$genres = get_terms(array(
    'taxonomy' => 'ensemble_genre',
    'hide_empty' => false,
));

$location_types = get_terms(array(
    'taxonomy' => 'ensemble_location_type',
    'hide_empty' => false,
));

$artist_types = get_terms(array(
    'taxonomy' => 'ensemble_artist_type',
    'hide_empty' => false,
));

// Staff Departments (if Staff addon is active)
$staff_departments = array();
if (taxonomy_exists('ensemble_department')) {
    $staff_departments = get_terms(array(
        'taxonomy' => 'ensemble_department',
        'hide_empty' => false,
    ));
}

// Get current tab
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'categories';
?>

<div class="wrap es-taxonomies-wrap">
    <h1>
        <span class="dashicons dashicons-tag"></span>
        <?php _e('Taxonomies', 'ensemble'); ?>
    </h1>
    
    <div class="es-taxonomies-container">
        
        <!-- Tab Navigation -->
        <div class="es-taxonomy-tabs">
            <button class="es-tab-btn <?php echo $current_tab === 'categories' ? 'active' : ''; ?>" 
                    onclick="window.location.href='?page=ensemble-taxonomies&tab=categories'">
                <span class="dashicons dashicons-category"></span>
                <?php _e('Event Categories', 'ensemble'); ?>
            </button>
            <button class="es-tab-btn <?php echo $current_tab === 'genres' ? 'active' : ''; ?>" 
                    onclick="window.location.href='?page=ensemble-taxonomies&tab=genres'">
                <span class="dashicons dashicons-format-audio"></span>
                <?php _e('Artist Genres', 'ensemble'); ?>
            </button>
            <button class="es-tab-btn <?php echo $current_tab === 'artist-types' ? 'active' : ''; ?>" 
                    onclick="window.location.href='?page=ensemble-taxonomies&tab=artist-types'">
                <span class="dashicons dashicons-groups"></span>
                <?php _e('Artist Types', 'ensemble'); ?>
            </button>
            <button class="es-tab-btn <?php echo $current_tab === 'location-types' ? 'active' : ''; ?>" 
                    onclick="window.location.href='?page=ensemble-taxonomies&tab=location-types'">
                <span class="dashicons dashicons-location"></span>
                <?php _e('Location Types', 'ensemble'); ?>
            </button>
            <?php if (taxonomy_exists('ensemble_department')): ?>
            <button class="es-tab-btn <?php echo $current_tab === 'departments' ? 'active' : ''; ?>" 
                    onclick="window.location.href='?page=ensemble-taxonomies&tab=departments'">
                <span class="dashicons dashicons-groups"></span>
                <?php echo esc_html(ES_Label_System::get_label('department', true)); ?>
            </button>
            <?php endif; ?>
        </div>
        
        <!-- Event Categories Tab -->
        <?php if ($current_tab === 'categories'): ?>
        
        <div class="es-taxonomy-section">
            <div class="es-taxonomy-header">
                <div class="es-header-text">
                    <h2><?php _e('Event Categories', 'ensemble'); ?></h2>
                    <p class="es-description">
                        <?php _e('Categories organize events and can trigger custom wizard steps in the Event Wizard.', 'ensemble'); ?>
                    </p>
                </div>
                <button class="button button-primary es-taxonomy-add-btn" 
                        data-taxonomy="ensemble_category"
                        data-post-type="<?php echo esc_attr($current_post_type); ?>">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php _e('Add Category', 'ensemble'); ?>
                </button>
            </div>
            
            <?php if (!is_wp_error($categories) && !empty($categories)): ?>
            
            <div class="es-taxonomy-pills">
                <?php foreach ($categories as $category): 
                    $category_color = get_term_meta($category->term_id, 'ensemble_category_color', true);
                    if (empty($category_color)) $category_color = '#3582c4';
                ?>
                <div class="es-taxonomy-pill" style="--pill-color: <?php echo esc_attr($category_color); ?>">
                    <div class="es-pill-color-indicator" style="background: <?php echo esc_attr($category_color); ?>"></div>
                    <div class="es-pill-header">
                        <h3><?php echo esc_html($category->name); ?></h3>
                        <span class="es-pill-count"><?php echo esc_html($category->count); ?> <?php _e('Events', 'ensemble'); ?></span>
                    </div>
                    
                    <?php if (!empty($category->slug)): ?>
                    <div class="es-pill-meta">
                        <span class="es-meta-label"><?php _e('Slug:', 'ensemble'); ?></span>
                        <span class="es-meta-value"><?php echo esc_html($category->slug); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($category->description)): ?>
                    <div class="es-pill-description">
                        <?php echo esc_html($category->description); ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="es-pill-actions">
                        <button class="button button-small es-taxonomy-edit-btn" 
                                data-term-id="<?php echo esc_attr($category->term_id); ?>"
                                data-term-name="<?php echo esc_attr($category->name); ?>"
                                data-term-slug="<?php echo esc_attr($category->slug); ?>"
                                data-term-description="<?php echo esc_attr($category->description); ?>"
                                data-term-color="<?php echo esc_attr($category_color); ?>"
                                data-taxonomy="ensemble_category"
                                data-post-type="<?php echo esc_attr($current_post_type); ?>">
                            <?php ES_Icons::icon('edit'); ?>
                            <?php _e('Edit', 'ensemble'); ?>
                        </button>
                        <button class="es-icon-btn es-icon-btn-danger es-taxonomy-delete-btn" 
                                data-term-id="<?php echo esc_attr($category->term_id); ?>"
                                data-term-name="<?php echo esc_attr($category->name); ?>"
                                data-taxonomy="ensemble_category"
                                title="<?php esc_attr_e('Delete', 'ensemble'); ?>">
                            <?php ES_Icons::icon('trash'); ?>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php else: ?>
            
            <div class="es-taxonomy-empty">
                <span class="dashicons dashicons-category"></span>
                <h3><?php _e('No Categories Found', 'ensemble'); ?></h3>
                <p><?php _e('Create your first event category to start organizing your events.', 'ensemble'); ?></p>
                <button class="button button-primary button-large es-taxonomy-add-btn" 
                        data-taxonomy="ensemble_category"
                        data-post-type="<?php echo esc_attr($current_post_type); ?>">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php _e('Create First Category', 'ensemble'); ?>
                </button>
            </div>
            
            <?php endif; ?>
            
            <div class="es-taxonomy-info-box">
                <span class="dashicons dashicons-info"></span>
                <div>
                    <strong><?php _e('Using Categories in the Event Wizard', 'ensemble'); ?></strong>
                    <p>
                        <?php _e('Configure custom ACF field groups to display based on category selection in', 'ensemble'); ?>
                        <a href="<?php echo admin_url('admin.php?page=ensemble-settings&tab=wizard-steps'); ?>">
                            <?php _e('Settings → Wizard Steps', 'ensemble'); ?>
                        </a>.
                    </p>
                </div>
            </div>
        </div>
        
        <?php endif; ?>
        
        <!-- Artist Genres Tab -->
        <?php if ($current_tab === 'genres'): ?>
        
        <div class="es-taxonomy-section">
            <div class="es-taxonomy-header">
                <div class="es-header-text">
                    <h2><?php _e('Artist Genres', 'ensemble'); ?></h2>
                    <p class="es-description">
                        <?php _e('Categorize artists by musical style or performance type.', 'ensemble'); ?>
                    </p>
                </div>
                <button class="button button-primary es-taxonomy-add-btn" 
                        data-taxonomy="ensemble_genre"
                        data-post-type="ensemble_artist">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php _e('Add Genre', 'ensemble'); ?>
                </button>
            </div>
            
            <?php if (!is_wp_error($genres) && !empty($genres)): ?>
            
            <div class="es-taxonomy-pills">
                <?php foreach ($genres as $genre): ?>
                <div class="es-taxonomy-pill">
                    <div class="es-pill-header">
                        <h3><?php echo esc_html($genre->name); ?></h3>
                        <span class="es-pill-count"><?php echo esc_html($genre->count); ?> <?php _e('Artists', 'ensemble'); ?></span>
                    </div>
                    
                    <?php if (!empty($genre->slug)): ?>
                    <div class="es-pill-meta">
                        <span class="es-meta-label"><?php _e('Slug:', 'ensemble'); ?></span>
                        <span class="es-meta-value"><?php echo esc_html($genre->slug); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($genre->description)): ?>
                    <div class="es-pill-description">
                        <?php echo esc_html($genre->description); ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="es-pill-actions">
                        <button class="button button-small es-taxonomy-edit-btn" 
                                data-term-id="<?php echo esc_attr($genre->term_id); ?>"
                                data-term-name="<?php echo esc_attr($genre->name); ?>"
                                data-term-slug="<?php echo esc_attr($genre->slug); ?>"
                                data-term-description="<?php echo esc_attr($genre->description); ?>"
                                data-taxonomy="ensemble_genre"
                                data-post-type="ensemble_artist">
                            <?php ES_Icons::icon('edit'); ?>
                            <?php _e('Edit', 'ensemble'); ?>
                        </button>
                        <button class="es-icon-btn es-icon-btn-danger es-taxonomy-delete-btn" 
                                data-term-id="<?php echo esc_attr($genre->term_id); ?>"
                                data-term-name="<?php echo esc_attr($genre->name); ?>"
                                data-taxonomy="ensemble_genre"
                                title="<?php esc_attr_e('Delete', 'ensemble'); ?>">
                            <?php ES_Icons::icon('trash'); ?>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php else: ?>
            
            <div class="es-taxonomy-empty">
                <span class="dashicons dashicons-format-audio"></span>
                <h3><?php _e('No Genres Found', 'ensemble'); ?></h3>
                <p><?php _e('Create your first genre to categorize your artists.', 'ensemble'); ?></p>
                <button class="button button-primary button-large es-taxonomy-add-btn" 
                        data-taxonomy="ensemble_genre"
                        data-post-type="ensemble_artist">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php _e('Create First Genre', 'ensemble'); ?>
                </button>
            </div>
            
            <?php endif; ?>
        </div>
        
        <?php endif; ?>
        
        <!-- Artist Types Tab -->
        <?php if ($current_tab === 'artist-types'): ?>
        
        <div class="es-taxonomy-section">
            <div class="es-taxonomy-header">
                <div class="es-header-text">
                    <h2><?php _e('Artist Types', 'ensemble'); ?></h2>
                    <p class="es-description">
                        <?php _e('Categorize artists by type (e.g., DJ, Band, Singer, Speaker, Trainer).', 'ensemble'); ?>
                    </p>
                </div>
                <button class="button button-primary es-taxonomy-add-btn" 
                        data-taxonomy="ensemble_artist_type"
                        data-post-type="ensemble_artist">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php _e('Add Artist Type', 'ensemble'); ?>
                </button>
            </div>
            
            <?php if (!is_wp_error($artist_types) && !empty($artist_types)): ?>
            
            <div class="es-taxonomy-pills">
                <?php foreach ($artist_types as $artist_type): ?>
                <div class="es-taxonomy-pill">
                    <div class="es-pill-header">
                        <h3><?php echo esc_html($artist_type->name); ?></h3>
                        <span class="es-pill-count"><?php echo esc_html($artist_type->count); ?> <?php _e('Artists', 'ensemble'); ?></span>
                    </div>
                    
                    <?php if (!empty($artist_type->slug)): ?>
                    <div class="es-pill-meta">
                        <span class="es-meta-label"><?php _e('Slug:', 'ensemble'); ?></span>
                        <span class="es-meta-value"><?php echo esc_html($artist_type->slug); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($artist_type->description)): ?>
                    <div class="es-pill-description">
                        <?php echo esc_html($artist_type->description); ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="es-pill-actions">
                        <button class="button button-small es-taxonomy-edit-btn" 
                                data-term-id="<?php echo esc_attr($artist_type->term_id); ?>"
                                data-term-name="<?php echo esc_attr($artist_type->name); ?>"
                                data-term-slug="<?php echo esc_attr($artist_type->slug); ?>"
                                data-term-description="<?php echo esc_attr($artist_type->description); ?>"
                                data-taxonomy="ensemble_artist_type"
                                data-post-type="ensemble_artist">
                            <?php ES_Icons::icon('edit'); ?>
                            <?php _e('Edit', 'ensemble'); ?>
                        </button>
                        <button class="es-icon-btn es-icon-btn-danger es-taxonomy-delete-btn" 
                                data-term-id="<?php echo esc_attr($artist_type->term_id); ?>"
                                data-term-name="<?php echo esc_attr($artist_type->name); ?>"
                                data-taxonomy="ensemble_artist_type"
                                title="<?php esc_attr_e('Delete', 'ensemble'); ?>">
                            <?php ES_Icons::icon('trash'); ?>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php else: ?>
            
            <div class="es-taxonomy-empty">
                <span class="dashicons dashicons-groups"></span>
                <h3><?php _e('No Artist Types Found', 'ensemble'); ?></h3>
                <p><?php _e('Create artist types to categorize your artists (e.g., DJ, Band, Singer, Speaker).', 'ensemble'); ?></p>
                <button class="button button-primary button-large es-taxonomy-add-btn" 
                        data-taxonomy="ensemble_artist_type"
                        data-post-type="ensemble_artist">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php _e('Create First Artist Type', 'ensemble'); ?>
                </button>
            </div>
            
            <?php endif; ?>
        </div>
        
        <?php endif; ?>
        
        <!-- Location Types Tab -->
        <?php if ($current_tab === 'location-types'): ?>
        
        <div class="es-taxonomy-section">
            <div class="es-taxonomy-header">
                <div class="es-header-text">
                    <h2><?php _e('Location Types', 'ensemble'); ?></h2>
                    <p class="es-description">
                        <?php _e('Categorize venues by type (e.g., Concert Hall, Theater, Stadium).', 'ensemble'); ?>
                    </p>
                </div>
                <button class="button button-primary es-taxonomy-add-btn" 
                        data-taxonomy="ensemble_location_type"
                        data-post-type="ensemble_location">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php _e('Add Location Type', 'ensemble'); ?>
                </button>
            </div>
            
            <?php if (!is_wp_error($location_types) && !empty($location_types)): ?>
            
            <div class="es-taxonomy-pills">
                <?php foreach ($location_types as $location_type): ?>
                <div class="es-taxonomy-pill">
                    <div class="es-pill-header">
                        <h3><?php echo esc_html($location_type->name); ?></h3>
                        <span class="es-pill-count"><?php echo esc_html($location_type->count); ?> <?php _e('Locations', 'ensemble'); ?></span>
                    </div>
                    
                    <?php if (!empty($location_type->slug)): ?>
                    <div class="es-pill-meta">
                        <span class="es-meta-label"><?php _e('Slug:', 'ensemble'); ?></span>
                        <span class="es-meta-value"><?php echo esc_html($location_type->slug); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($location_type->description)): ?>
                    <div class="es-pill-description">
                        <?php echo esc_html($location_type->description); ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="es-pill-actions">
                        <button class="button button-small es-taxonomy-edit-btn" 
                                data-term-id="<?php echo esc_attr($location_type->term_id); ?>"
                                data-term-name="<?php echo esc_attr($location_type->name); ?>"
                                data-term-slug="<?php echo esc_attr($location_type->slug); ?>"
                                data-term-description="<?php echo esc_attr($location_type->description); ?>"
                                data-taxonomy="ensemble_location_type"
                                data-post-type="ensemble_location">
                            <?php ES_Icons::icon('edit'); ?>
                            <?php _e('Edit', 'ensemble'); ?>
                        </button>
                        <button class="es-icon-btn es-icon-btn-danger es-taxonomy-delete-btn" 
                                data-term-id="<?php echo esc_attr($location_type->term_id); ?>"
                                data-term-name="<?php echo esc_attr($location_type->name); ?>"
                                data-taxonomy="ensemble_location_type"
                                title="<?php esc_attr_e('Delete', 'ensemble'); ?>">
                            <?php ES_Icons::icon('trash'); ?>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php else: ?>
            
            <div class="es-taxonomy-empty">
                <span class="dashicons dashicons-location"></span>
                <h3><?php _e('No Location Types Found', 'ensemble'); ?></h3>
                <p><?php _e('Create your first location type to categorize your venues.', 'ensemble'); ?></p>
                <button class="button button-primary button-large es-taxonomy-add-btn" 
                        data-taxonomy="ensemble_location_type"
                        data-post-type="ensemble_location">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php _e('Create First Location Type', 'ensemble'); ?>
                </button>
            </div>
            
            <?php endif; ?>
        </div>
        
        <?php endif; ?>
        
        <!-- Staff Departments Tab -->
        <?php if ($current_tab === 'departments' && taxonomy_exists('ensemble_department')): 
            $dept_singular = ES_Label_System::get_label('department', false);
            $dept_plural = ES_Label_System::get_label('department', true);
            $staff_singular = ES_Label_System::get_label('staff', false);
            $staff_plural = ES_Label_System::get_label('staff', true);
        ?>
        
        <div class="es-taxonomy-section">
            <div class="es-taxonomy-header">
                <div class="es-header-text">
                    <h2><?php echo esc_html($dept_plural); ?></h2>
                    <p class="es-description">
                        <?php printf(__('Organize %s by %s (e.g., Organization, Registration, Press).', 'ensemble'), strtolower($staff_plural), strtolower($dept_singular)); ?>
                    </p>
                </div>
                <button class="button button-primary es-taxonomy-add-btn" 
                        data-taxonomy="ensemble_department"
                        data-post-type="ensemble_staff">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php printf(__('Add %s', 'ensemble'), $dept_singular); ?>
                </button>
            </div>
            
            <?php if (!is_wp_error($staff_departments) && !empty($staff_departments)): ?>
            
            <div class="es-taxonomy-pills">
                <?php foreach ($staff_departments as $department): ?>
                <div class="es-taxonomy-pill">
                    <div class="es-pill-header">
                        <h3><?php echo esc_html($department->name); ?></h3>
                        <span class="es-pill-count"><?php echo esc_html($department->count); ?> <?php echo esc_html($staff_plural); ?></span>
                    </div>
                    
                    <?php if (!empty($department->slug)): ?>
                    <div class="es-pill-meta">
                        <span class="es-meta-label"><?php _e('Slug:', 'ensemble'); ?></span>
                        <span class="es-meta-value"><?php echo esc_html($department->slug); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($department->description)): ?>
                    <div class="es-pill-description">
                        <?php echo esc_html($department->description); ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="es-pill-actions">
                        <button class="button button-small es-taxonomy-edit-btn" 
                                data-term-id="<?php echo esc_attr($department->term_id); ?>"
                                data-term-name="<?php echo esc_attr($department->name); ?>"
                                data-term-slug="<?php echo esc_attr($department->slug); ?>"
                                data-term-description="<?php echo esc_attr($department->description); ?>"
                                data-taxonomy="ensemble_department"
                                data-post-type="ensemble_staff">
                            <?php ES_Icons::icon('edit'); ?>
                            <?php _e('Edit', 'ensemble'); ?>
                        </button>
                        <button class="es-icon-btn es-icon-btn-danger es-taxonomy-delete-btn" 
                                data-term-id="<?php echo esc_attr($department->term_id); ?>"
                                data-term-name="<?php echo esc_attr($department->name); ?>"
                                data-taxonomy="ensemble_department"
                                title="<?php esc_attr_e('Delete', 'ensemble'); ?>">
                            <?php ES_Icons::icon('trash'); ?>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php else: ?>
            
            <div class="es-taxonomy-empty">
                <span class="dashicons dashicons-groups"></span>
                <h3><?php printf(__('No %s Found', 'ensemble'), $dept_plural); ?></h3>
                <p><?php printf(__('Create your first %s to organize your %s.', 'ensemble'), strtolower($dept_singular), strtolower($staff_plural)); ?></p>
                <button class="button button-primary button-large es-taxonomy-add-btn" 
                        data-taxonomy="ensemble_department"
                        data-post-type="ensemble_staff">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php printf(__('Create First %s', 'ensemble'), $dept_singular); ?>
                </button>
            </div>
            
            <?php endif; ?>
        </div>
        
        <?php endif; ?>
        
    </div>
</div>

<style>
/* Taxonomies Page Styles */
.es-taxonomies-wrap {
    background: var(--es-background, #1e1e1e);
    min-height: 100vh;
    margin-left: -20px;
    margin-right: -20px;
    padding: 20px;
}

.es-taxonomies-container {
    max-width: 1400px;
    margin: 0 auto;
}

/* Tabs */
.es-taxonomy-tabs {
    display: flex;
    gap: 12px;
    margin-bottom: 30px;
    padding: 20px;
    background: var(--es-surface, #2c2c2c);
    border-radius: 12px;
    border: 2px solid var(--es-border, #3c3c3c);
}

.es-tab-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    background: transparent;
    border: 2px solid var(--es-border, #3c3c3c);
    border-radius: 8px;
    color: var(--es-text-secondary, #a0a0a0);
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
}

.es-tab-btn:hover {
    background: var(--es-surface-hover, #404040);
    border-color: var(--es-primary, #3582c4);
    color: var(--es-text, #e0e0e0);
}

.es-tab-btn.active {
    background: var(--es-primary, #3582c4);
    border-color: var(--es-primary, #3582c4);
    color: #fff;
    box-shadow: 0 4px 12px rgba(53, 130, 196, 0.3);
}

.es-tab-btn .dashicons {
    font-size: 20px;
    width: 20px;
    height: 20px;
}

/* Taxonomy Section */
.es-taxonomy-section {
    background: var(--es-surface, #2c2c2c);
    border: 2px solid var(--es-border, #3c3c3c);
    border-radius: 12px;
    padding: 30px;
}

/* Header */
.es-taxonomy-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid var(--es-border, #3c3c3c);
}

.es-header-text h2 {
    margin: 0 0 8px 0;
    font-size: 24px;
    font-weight: 600;
    color: var(--es-text, #e0e0e0);
}

.es-description {
    margin: 0;
    color: var(--es-text-secondary, #a0a0a0);
    font-size: 14px;
}

/* Pills Grid */
.es-taxonomy-pills {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.es-taxonomy-pill {
    background: var(--es-surface-secondary, #383838);
    border: 2px solid var(--es-border, #3c3c3c);
    border-radius: 12px;
    padding: 20px;
    transition: all 0.2s ease;
    position: relative;
    overflow: hidden;
}

.es-taxonomy-pill:hover {
    border-color: var(--es-primary, #3582c4);
    box-shadow: 0 4px 12px rgba(53, 130, 196, 0.2);
    transform: translateY(-2px);
}

/* Category Color Indicator */
.es-pill-color-indicator {
    position: absolute;
    top: 0;
    left: 0;
    width: 6px;
    height: 100%;
    border-radius: 12px 0 0 12px;
}

.es-pill-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.es-pill-header h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
    color: var(--es-text, #e0e0e0);
}

.es-pill-count {
    padding: 4px 12px;
    background: var(--es-primary, #3582c4);
    color: #fff;
    font-size: 12px;
    font-weight: 500;
    border-radius: 12px;
}

.es-pill-meta {
    display: flex;
    gap: 8px;
    margin-bottom: 8px;
    font-size: 13px;
}

.es-meta-label {
    color: var(--es-text-secondary, #a0a0a0);
    font-weight: 500;
}

.es-meta-value {
    color: var(--es-text, #e0e0e0);
    font-family: monospace;
}

.es-pill-description {
    margin-bottom: 16px;
    padding: 12px;
    background: var(--es-surface, #2c2c2c);
    border-radius: 6px;
    color: var(--es-text-secondary, #a0a0a0);
    font-size: 13px;
    line-height: 1.5;
}

.es-pill-actions {
    display: flex;
    gap: 8px;
    padding-top: 16px;
    border-top: 1px solid var(--es-border, #3c3c3c);
}

.es-pill-actions .button {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    font-size: 13px;
}

/* Empty State */
.es-taxonomy-empty {
    text-align: center;
    padding: 60px 20px;
}

.es-taxonomy-empty .dashicons {
    font-size: 64px;
    width: 64px;
    height: 64px;
    color: var(--es-text-secondary, #a0a0a0);
    margin-bottom: 20px;
}

.es-taxonomy-empty h3 {
    margin: 0 0 12px 0;
    font-size: 20px;
    color: var(--es-text, #e0e0e0);
}

.es-taxonomy-empty p {
    margin: 0 0 24px 0;
    color: var(--es-text-secondary, #a0a0a0);
}

/* Info Box */
.es-taxonomy-info-box {
    display: flex;
    gap: 16px;
    padding: 20px;
    background: var(--es-surface-secondary, #383838);
    border: 2px solid var(--es-info, #3582c4);
    border-radius: 8px;
}

.es-taxonomy-info-box .dashicons {
    flex-shrink: 0;
    font-size: 24px;
    width: 24px;
    height: 24px;
    color: var(--es-info, #3582c4);
}

.es-taxonomy-info-box strong {
    display: block;
    margin-bottom: 8px;
    color: var(--es-text, #e0e0e0);
    font-size: 14px;
}

.es-taxonomy-info-box p {
    margin: 0;
    color: var(--es-text-secondary, #a0a0a0);
    font-size: 13px;
    line-height: 1.5;
}

.es-taxonomy-info-box a {
    color: var(--es-primary, #3582c4);
    text-decoration: none;
}

.es-taxonomy-info-box a:hover {
    text-decoration: underline;
}

/* Responsive */
@media screen and (max-width: 782px) {
    .es-taxonomies-wrap {
        margin-left: -10px;
        margin-right: -10px;
        padding: 10px;
    }
    
    .es-taxonomy-tabs {
        flex-direction: column;
        padding: 10px;
    }
    
    .es-tab-btn {
        margin-right: 0;
        margin-bottom: 8px;
        justify-content: center;
    }
    
    .es-taxonomy-section {
        padding: 20px;
    }
    
    .es-taxonomy-header {
        flex-direction: column;
    }
    
    .es-taxonomy-header .button {
        width: 100%;
        text-align: center;
    }
    
    .es-taxonomy-pills {
        grid-template-columns: 1fr;
    }
}
</style>

<!-- Taxonomy Edit/Add Modal -->
<div id="es-taxonomy-modal" class="es-modal" style="display: none;">
    <div class="es-modal-overlay"></div>
    <div class="es-modal-content">
        <div class="es-modal-header">
            <h2 id="es-modal-title"><?php _e('Add Term', 'ensemble'); ?></h2>
            <button class="es-modal-close" id="es-modal-close">×</button>
        </div>
        <div class="es-modal-body">
            <form id="es-taxonomy-form">
                <input type="hidden" id="es-term-id" name="term_id" value="">
                <input type="hidden" id="es-taxonomy" name="taxonomy" value="">
                <input type="hidden" id="es-post-type" name="post_type" value="">
                
                <div class="es-form-row">
                    <label for="es-term-name"><?php _e('Name', 'ensemble'); ?> *</label>
                    <input type="text" id="es-term-name" name="name" required>
                    <p class="description"><?php _e('The name is how it appears on your site.', 'ensemble'); ?></p>
                </div>
                
                <div class="es-form-row">
                    <label for="es-term-slug"><?php _e('Slug', 'ensemble'); ?></label>
                    <input type="text" id="es-term-slug" name="slug">
                    <p class="description"><?php _e('The "slug" is the URL-friendly version of the name. Leave blank to auto-generate.', 'ensemble'); ?></p>
                </div>
                
                <div class="es-form-row es-color-row" id="es-color-row" style="display: none;">
                    <label for="es-term-color"><?php _e('Color', 'ensemble'); ?></label>
                    <div class="es-color-input-wrapper">
                        <input type="color" id="es-term-color" name="color" value="#3582c4">
                        <input type="text" id="es-term-color-hex" name="color_hex" value="#3582c4" pattern="^#[0-9A-Fa-f]{6}$" maxlength="7">
                        <div class="es-color-presets">
                            <button type="button" class="es-color-preset" data-color="#3582c4" style="background: #3582c4;" title="Blue"></button>
                            <button type="button" class="es-color-preset" data-color="#e74c3c" style="background: #e74c3c;" title="Red"></button>
                            <button type="button" class="es-color-preset" data-color="#27ae60" style="background: #27ae60;" title="Green"></button>
                            <button type="button" class="es-color-preset" data-color="#f39c12" style="background: #f39c12;" title="Orange"></button>
                            <button type="button" class="es-color-preset" data-color="#9b59b6" style="background: #9b59b6;" title="Purple"></button>
                            <button type="button" class="es-color-preset" data-color="#1abc9c" style="background: #1abc9c;" title="Teal"></button>
                            <button type="button" class="es-color-preset" data-color="#e91e63" style="background: #e91e63;" title="Pink"></button>
                            <button type="button" class="es-color-preset" data-color="#607d8b" style="background: #607d8b;" title="Gray"></button>
                        </div>
                    </div>
                    <p class="description"><?php _e('Color for this category in the calendar view.', 'ensemble'); ?></p>
                </div>
                
                <div class="es-form-row">
                    <label for="es-term-description"><?php _e('Description', 'ensemble'); ?></label>
                    <textarea id="es-term-description" name="description" rows="4"></textarea>
                    <p class="description"><?php _e('Optional description for this term.', 'ensemble'); ?></p>
                </div>
                
                <div class="es-modal-actions">
                    <button type="button" class="button" id="es-modal-cancel"><?php _e('Cancel', 'ensemble'); ?></button>
                    <button type="submit" class="button button-primary" id="es-modal-save">
                        <span class="dashicons dashicons-yes"></span>
                        <?php _e('Save', 'ensemble'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Modal Styles */
.es-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 100000;
}

.es-modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(4px);
}

.es-modal-content {
    position: relative;
    width: 90%;
    max-width: 600px;
    margin: 5vh auto;
    background: var(--es-surface, #2c2c2c);
    border-radius: 12px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.es-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px;
    background: var(--es-background, #1e1e1e);
    border-bottom: 2px solid var(--es-border, #3c3c3c);
}

.es-modal-header h2 {
    margin: 0;
    font-size: 20px;
    font-weight: 600;
    color: var(--es-text, #e0e0e0);
}

.es-modal-close {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: transparent;
    border: 2px solid var(--es-border, #3c3c3c);
    border-radius: 8px;
    font-size: 28px;
    line-height: 1;
    color: var(--es-text-secondary, #a0a0a0);
    cursor: pointer;
    transition: all 0.2s ease;
}

.es-modal-close:hover {
    background: var(--es-danger, #dc2626);
    border-color: var(--es-danger, #dc2626);
    color: white;
    transform: rotate(90deg);
}

.es-modal-body {
    padding: 24px;
    max-height: calc(90vh - 160px);
    overflow-y: auto;
}

.es-modal-body .es-form-row {
    margin-bottom: 20px;
}

.es-modal-body .es-form-row:last-child {
    margin-bottom: 0;
}

.es-modal-body label {
    display: block;
    margin-bottom: 8px;
    color: var(--es-text, #e0e0e0);
    font-weight: 500;
    font-size: 14px;
}

.es-modal-body input[type="text"],
.es-modal-body textarea {
    width: 100%;
    padding: 10px 12px;
    background: var(--es-surface-secondary, #383838);
    border: 2px solid var(--es-border, #3c3c3c);
    border-radius: 6px;
    color: var(--es-text, #e0e0e0);
    font-size: 14px;
    transition: all 0.2s ease;
}

.es-modal-body input[type="text"]:focus,
.es-modal-body textarea:focus {
    outline: none;
    border-color: var(--es-primary, #3582c4);
    box-shadow: 0 0 0 3px rgba(53, 130, 196, 0.1);
}

.es-modal-body .description {
    margin: 6px 0 0 0;
    color: var(--es-text-secondary, #a0a0a0);
    font-size: 12px;
}

/* Color Input Styles */
.es-color-input-wrapper {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.es-color-input-wrapper input[type="color"] {
    width: 50px;
    height: 40px;
    padding: 2px;
    border: 2px solid var(--es-border, #3c3c3c);
    border-radius: 6px;
    background: var(--es-surface-secondary, #383838);
    cursor: pointer;
}

.es-color-input-wrapper input[type="color"]::-webkit-color-swatch-wrapper {
    padding: 2px;
}

.es-color-input-wrapper input[type="color"]::-webkit-color-swatch {
    border-radius: 4px;
    border: none;
}

.es-color-input-wrapper input[type="text"] {
    width: 90px !important;
    font-family: monospace;
    text-transform: uppercase;
}

.es-color-presets {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}

.es-color-preset {
    width: 28px;
    height: 28px;
    border-radius: 4px;
    border: 2px solid transparent;
    cursor: pointer;
    transition: all 0.2s;
    padding: 0;
}

.es-color-preset:hover {
    transform: scale(1.15);
    border-color: var(--es-text, #e0e0e0);
}

.es-color-preset.active {
    border-color: var(--es-text, #e0e0e0);
    box-shadow: 0 0 0 2px var(--es-primary, #3582c4);
}

.es-modal-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    margin-top: 24px;
    padding-top: 20px;
    border-top: 2px solid var(--es-border, #3c3c3c);
}

.es-modal-actions .button {
    padding: 10px 20px;
}

/* Loading State */
.es-loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10;
}

.es-loading-spinner {
    width: 40px;
    height: 40px;
    border: 4px solid var(--es-border, #3c3c3c);
    border-top-color: var(--es-primary, #3582c4);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>

<script>
jQuery(document).ready(function($) {
    const modal = $('#es-taxonomy-modal');
    const form = $('#es-taxonomy-form');
    const modalTitle = $('#es-modal-title');
    const colorRow = $('#es-color-row');
    
    // Color picker sync
    $('#es-term-color').on('input change', function() {
        $('#es-term-color-hex').val($(this).val().toUpperCase());
    });
    
    $('#es-term-color-hex').on('input change', function() {
        let val = $(this).val();
        if (!val.startsWith('#')) val = '#' + val;
        if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
            $('#es-term-color').val(val);
        }
    });
    
    // Color presets
    $('.es-color-preset').on('click', function() {
        const color = $(this).data('color');
        $('#es-term-color').val(color);
        $('#es-term-color-hex').val(color.toUpperCase());
        $('.es-color-preset').removeClass('active');
        $(this).addClass('active');
    });
    
    // Open Add modal
    $('.es-taxonomy-add-btn').on('click', function() {
        const taxonomy = $(this).data('taxonomy');
        const postType = $(this).data('post-type');
        
        // Set modal title
        let title = '<?php _e('Add Term', 'ensemble'); ?>';
        if (taxonomy === 'ensemble_category') {
            title = '<?php _e('Add Category', 'ensemble'); ?>';
        } else if (taxonomy === 'ensemble_genre') {
            title = '<?php _e('Add Genre', 'ensemble'); ?>';
        } else if (taxonomy === 'ensemble_location_type') {
            title = '<?php _e('Add Location Type', 'ensemble'); ?>';
        }
        
        modalTitle.text(title);
        
        // Reset form
        form[0].reset();
        $('#es-term-id').val('');
        $('#es-taxonomy').val(taxonomy);
        $('#es-post-type').val(postType);
        
        // Show/hide color field based on taxonomy
        if (taxonomy === 'ensemble_category') {
            colorRow.show();
            $('#es-term-color').val('#3582c4');
            $('#es-term-color-hex').val('#3582C4');
            $('.es-color-preset').removeClass('active');
        } else {
            colorRow.hide();
        }
        
        // Show modal
        modal.fadeIn(200);
        $('body').css('overflow', 'hidden');
        $('#es-term-name').focus();
    });
    
    // Open Edit modal
    $('.es-taxonomy-edit-btn').on('click', function() {
        const taxonomy = $(this).data('taxonomy');
        const postType = $(this).data('post-type');
        const termId = $(this).data('term-id');
        const termName = $(this).data('term-name');
        const termSlug = $(this).data('term-slug');
        const termDescription = $(this).data('term-description');
        const termColor = $(this).data('term-color') || '#3582c4';
        
        // Set modal title
        let title = '<?php _e('Edit Term', 'ensemble'); ?>';
        if (taxonomy === 'ensemble_category') {
            title = '<?php _e('Edit Category', 'ensemble'); ?>';
        } else if (taxonomy === 'ensemble_genre') {
            title = '<?php _e('Edit Genre', 'ensemble'); ?>';
        } else if (taxonomy === 'ensemble_location_type') {
            title = '<?php _e('Edit Location Type', 'ensemble'); ?>';
        }
        
        modalTitle.text(title);
        
        // Fill form
        $('#es-term-id').val(termId);
        $('#es-taxonomy').val(taxonomy);
        $('#es-post-type').val(postType);
        $('#es-term-name').val(termName);
        $('#es-term-slug').val(termSlug);
        $('#es-term-description').val(termDescription);
        
        // Show/hide and fill color field
        if (taxonomy === 'ensemble_category') {
            colorRow.show();
            $('#es-term-color').val(termColor);
            $('#es-term-color-hex').val(termColor.toUpperCase());
            $('.es-color-preset').removeClass('active');
            $(`.es-color-preset[data-color="${termColor}"]`).addClass('active');
        } else {
            colorRow.hide();
        }
        
        // Show modal
        modal.fadeIn(200);
        $('body').css('overflow', 'hidden');
        $('#es-term-name').focus();
    });
    
    // Handle Delete
    $('.es-taxonomy-delete-btn').on('click', function() {
        const termId = $(this).data('term-id');
        const termName = $(this).data('term-name');
        const taxonomy = $(this).data('taxonomy');
        
        if (!confirm('<?php _e('Are you sure you want to delete', 'ensemble'); ?> "' + termName + '"?')) {
            return;
        }
        
        // Show loading
        const $btn = $(this);
        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update-alt" style="animation: spin 1s linear infinite;"></span>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'ensemble_delete_term',
                nonce: '<?php echo wp_create_nonce('ensemble_taxonomy'); ?>',
                term_id: termId,
                taxonomy: taxonomy
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || '<?php _e('Error deleting term', 'ensemble'); ?>');
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-trash"></span> <?php _e('Delete', 'ensemble'); ?>');
                }
            },
            error: function() {
                alert('<?php _e('Error deleting term', 'ensemble'); ?>');
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-trash"></span> <?php _e('Delete', 'ensemble'); ?>');
            }
        });
    });
    
    // Close modal
    function closeModal() {
        modal.fadeOut(200);
        form[0].reset();
        $('body').css('overflow', 'auto');
    }
    
    $('#es-modal-close, #es-modal-cancel, .es-modal-overlay').on('click', closeModal);
    
    // Close on escape key
    $(document).on('keyup', function(e) {
        if (e.key === 'Escape' && modal.is(':visible')) {
            closeModal();
        }
    });
    
    // Handle form submission
    form.on('submit', function(e) {
        e.preventDefault();
        
        const termId = $('#es-term-id').val();
        const taxonomy = $('#es-taxonomy').val();
        const postType = $('#es-post-type').val();
        const name = $('#es-term-name').val();
        const slug = $('#es-term-slug').val();
        const description = $('#es-term-description').val();
        const color = taxonomy === 'ensemble_category' ? $('#es-term-color').val() : '';
        
        if (!name) {
            alert('<?php _e('Please enter a name', 'ensemble'); ?>');
            return;
        }
        
        // Show loading
        const $saveBtn = $('#es-modal-save');
        const originalText = $saveBtn.html();
        $saveBtn.prop('disabled', true).html('<span class="dashicons dashicons-update-alt" style="animation: spin 1s linear infinite;"></span> <?php _e('Saving...', 'ensemble'); ?>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'ensemble_save_term',
                nonce: '<?php echo wp_create_nonce('ensemble_taxonomy'); ?>',
                term_id: termId,
                taxonomy: taxonomy,
                post_type: postType,
                name: name,
                slug: slug,
                description: description,
                color: color
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || '<?php _e('Error saving term', 'ensemble'); ?>');
                    $saveBtn.prop('disabled', false).html(originalText);
                }
            },
            error: function() {
                alert('<?php _e('Error saving term', 'ensemble'); ?>');
                $saveBtn.prop('disabled', false).html(originalText);
            }
        });
    });
});
</script>
