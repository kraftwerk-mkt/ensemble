<?php
/**
 * Ensemble Event Layout Templates
 * 
 * Professional layout templates for event display
 * 
 * @package Ensemble
 */

class ES_Event_Layouts {
    
    /**
     * Get all available layouts
     * 
     * @return array Layout definitions
     */
    public static function get_layouts() {
        return [
            'classic' => [
                'name' => __('Classic', 'ensemble'),
                'description' => __('Traditional event layout with image on top, perfect for most use cases', 'ensemble'),
                'icon' => 'dashicons-format-image',
                'preview' => 'classic-preview.png',
                'structure' => 'full-width-image',
                'best_for' => __('General events, concerts, festivals', 'ensemble'),
            ],
            'modern' => [
                'name' => __('Modern Card', 'ensemble'),
                'description' => __('Clean card-based design with side image, great for modern websites', 'ensemble'),
                'icon' => 'dashicons-grid-view',
                'preview' => 'modern-preview.png',
                'structure' => 'side-by-side',
                'best_for' => __('Corporate events, conferences, workshops', 'ensemble'),
            ],
            'minimal' => [
                'name' => __('Minimal', 'ensemble'),
                'description' => __('Text-focused minimalist design, lets content shine', 'ensemble'),
                'icon' => 'dashicons-text',
                'preview' => 'minimal-preview.png',
                'structure' => 'text-primary',
                'best_for' => __('Theater, readings, lectures', 'ensemble'),
            ],
            'magazine' => [
                'name' => __('Magazine', 'ensemble'),
                'description' => __('Editorial style with prominent hero image and overlays', 'ensemble'),
                'icon' => 'dashicons-welcome-write-blog',
                'preview' => 'magazine-preview.png',
                'structure' => 'hero-overlay',
                'best_for' => __('Featured events, main attractions', 'ensemble'),
            ],
            'compact' => [
                'name' => __('Compact List', 'ensemble'),
                'description' => __('Space-efficient list view, perfect for event aggregation', 'ensemble'),
                'icon' => 'dashicons-list-view',
                'preview' => 'compact-preview.png',
                'structure' => 'list-compact',
                'best_for' => __('Event listings, archives, calendars', 'ensemble'),
            ],
        ];
    }
    
    /**
     * Get layout template HTML
     * 
     * @param string $layout_key Layout identifier
     * @param array $event Event data
     * @return string HTML output
     */
    public static function render_layout($layout_key, $event) {
        $layouts = self::get_layouts();
        
        if (!isset($layouts[$layout_key])) {
            $layout_key = 'classic'; // Fallback
        }
        
        $method = 'render_' . $layout_key;
        if (method_exists(__CLASS__, $method)) {
            return self::$method($event);
        }
        
        return self::render_classic($event);
    }
    
    /**
     * Classic Layout
     */
    private static function render_classic($event) {
        ob_start();
        ?>
        <article class="es-event es-layout-classic" id="event-<?php echo esc_attr($event['id']); ?>">
            
            <?php if (!empty($event['image'])): ?>
            <div class="es-event-image">
                <img src="<?php echo esc_url($event['image']); ?>" alt="<?php echo esc_attr($event['title']); ?>">
                <?php if (!empty($event['category'])): ?>
                <span class="es-event-category"><?php echo esc_html($event['category']); ?></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <div class="es-event-content">
                
                <div class="es-event-header">
                    <h2 class="es-event-title">
                        <a href="<?php echo esc_url($event['permalink']); ?>">
                            <?php echo esc_html($event['title']); ?>
                        </a>
                    </h2>
                </div>
                
                <div class="es-event-meta">
                    <?php if (!empty($event['date'])): ?>
                    <div class="es-meta-item es-meta-date">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <span><?php echo esc_html($event['date']); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($event['time'])): ?>
                    <div class="es-meta-item es-meta-time">
                        <span class="dashicons dashicons-clock"></span>
                        <span><?php echo esc_html($event['time']); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($event['location'])): ?>
                    <div class="es-meta-item es-meta-location">
                        <span class="dashicons dashicons-location"></span>
                        <span><?php echo esc_html($event['location']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($event['description'])): ?>
                <div class="es-event-description">
                    <?php echo wp_kses_post($event['description']); ?>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($event['artists'])): ?>
                <div class="es-event-artists">
                    <span class="es-artists-label"><?php _e('Artists:', 'ensemble'); ?></span>
                    <span class="es-artists-list"><?php echo esc_html(implode(', ', $event['artists'])); ?></span>
                </div>
                <?php endif; ?>
                
                <div class="es-event-footer">
                    <a href="<?php echo esc_url($event['permalink']); ?>" class="es-event-button">
                        <?php _e('View Details', 'ensemble'); ?>
                        <span class="dashicons dashicons-arrow-right-alt2"></span>
                    </a>
                    
                    <?php if (!empty($event['price'])): ?>
                    <span class="es-event-price"><?php echo esc_html($event['price']); ?></span>
                    <?php endif; ?>
                </div>
                
            </div>
            
        </article>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Modern Card Layout
     */
    private static function render_modern($event) {
        ob_start();
        ?>
        <article class="es-event es-layout-modern" id="event-<?php echo esc_attr($event['id']); ?>">
            
            <div class="es-event-card">
                
                <?php if (!empty($event['image'])): ?>
                <div class="es-event-image-side">
                    <img src="<?php echo esc_url($event['image']); ?>" alt="<?php echo esc_attr($event['title']); ?>">
                    <?php if (!empty($event['category'])): ?>
                    <span class="es-event-category-badge"><?php echo esc_html($event['category']); ?></span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <div class="es-event-content-side">
                    
                    <div class="es-event-date-block">
                        <?php if (!empty($event['date_day'])): ?>
                        <div class="es-date-day"><?php echo esc_html($event['date_day']); ?></div>
                        <div class="es-date-month"><?php echo esc_html($event['date_month']); ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="es-event-main">
                        <h2 class="es-event-title">
                            <a href="<?php echo esc_url($event['permalink']); ?>">
                                <?php echo esc_html($event['title']); ?>
                            </a>
                        </h2>
                        
                        <?php if (!empty($event['description'])): ?>
                        <div class="es-event-excerpt">
                            <?php echo wp_kses_post(wp_trim_words($event['description'], 25)); ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="es-event-meta-inline">
                            <?php if (!empty($event['time'])): ?>
                            <span class="es-meta-time">
                                <span class="dashicons dashicons-clock"></span>
                                <?php echo esc_html($event['time']); ?>
                            </span>
                            <?php endif; ?>
                            
                            <?php if (!empty($event['location'])): ?>
                            <span class="es-meta-location">
                                <span class="dashicons dashicons-location"></span>
                                <?php echo esc_html($event['location']); ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="es-event-actions">
                            <a href="<?php echo esc_url($event['permalink']); ?>" class="es-button-primary">
                                <?php _e('Details', 'ensemble'); ?>
                            </a>
                            <?php if (!empty($event['price'])): ?>
                            <span class="es-price-tag"><?php echo esc_html($event['price']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                </div>
                
            </div>
            
        </article>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Minimal Layout
     */
    private static function render_minimal($event) {
        ob_start();
        ?>
        <article class="es-event es-layout-minimal" id="event-<?php echo esc_attr($event['id']); ?>">
            
            <div class="es-minimal-container">
                
                <div class="es-minimal-header">
                    <?php if (!empty($event['category'])): ?>
                    <span class="es-category-tag"><?php echo esc_html($event['category']); ?></span>
                    <?php endif; ?>
                    
                    <h2 class="es-minimal-title">
                        <a href="<?php echo esc_url($event['permalink']); ?>">
                            <?php echo esc_html($event['title']); ?>
                        </a>
                    </h2>
                </div>
                
                <div class="es-minimal-meta">
                    <?php if (!empty($event['date'])): ?>
                    <span class="es-minimal-date"><?php echo esc_html($event['date']); ?></span>
                    <?php endif; ?>
                    
                    <?php if (!empty($event['time'])): ?>
                    <span class="es-separator">·</span>
                    <span class="es-minimal-time"><?php echo esc_html($event['time']); ?></span>
                    <?php endif; ?>
                    
                    <?php if (!empty($event['location'])): ?>
                    <span class="es-separator">·</span>
                    <span class="es-minimal-location"><?php echo esc_html($event['location']); ?></span>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($event['description'])): ?>
                <div class="es-minimal-description">
                    <?php echo wp_kses_post($event['description']); ?>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($event['artists'])): ?>
                <div class="es-minimal-artists">
                    <?php foreach ($event['artists'] as $artist): ?>
                    <span class="es-artist-tag"><?php echo esc_html($artist); ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <div class="es-minimal-footer">
                    <a href="<?php echo esc_url($event['permalink']); ?>" class="es-minimal-link">
                        <?php _e('Read more', 'ensemble'); ?> →
                    </a>
                    <?php if (!empty($event['price'])): ?>
                    <span class="es-minimal-price"><?php echo esc_html($event['price']); ?></span>
                    <?php endif; ?>
                </div>
                
            </div>
            
        </article>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Magazine Layout
     */
    private static function render_magazine($event) {
        ob_start();
        ?>
        <article class="es-event es-layout-magazine" id="event-<?php echo esc_attr($event['id']); ?>">
            
            <div class="es-magazine-hero" style="background-image: url('<?php echo esc_url($event['image'] ?? ''); ?>');">
                <div class="es-magazine-overlay">
                    
                    <div class="es-magazine-content">
                        
                        <?php if (!empty($event['category'])): ?>
                        <span class="es-magazine-category"><?php echo esc_html($event['category']); ?></span>
                        <?php endif; ?>
                        
                        <h2 class="es-magazine-title">
                            <a href="<?php echo esc_url($event['permalink']); ?>">
                                <?php echo esc_html($event['title']); ?>
                            </a>
                        </h2>
                        
                        <div class="es-magazine-meta">
                            <?php if (!empty($event['date'])): ?>
                            <span class="es-meta-item">
                                <span class="dashicons dashicons-calendar-alt"></span>
                                <?php echo esc_html($event['date']); ?>
                            </span>
                            <?php endif; ?>
                            
                            <?php if (!empty($event['time'])): ?>
                            <span class="es-meta-item">
                                <span class="dashicons dashicons-clock"></span>
                                <?php echo esc_html($event['time']); ?>
                            </span>
                            <?php endif; ?>
                            
                            <?php if (!empty($event['location'])): ?>
                            <span class="es-meta-item">
                                <span class="dashicons dashicons-location"></span>
                                <?php echo esc_html($event['location']); ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!empty($event['description'])): ?>
                        <div class="es-magazine-excerpt">
                            <?php echo wp_kses_post(wp_trim_words($event['description'], 30)); ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="es-magazine-footer">
                            <a href="<?php echo esc_url($event['permalink']); ?>" class="es-magazine-button">
                                <?php _e('Learn More', 'ensemble'); ?>
                                <span class="dashicons dashicons-arrow-right-alt2"></span>
                            </a>
                            
                            <?php if (!empty($event['price'])): ?>
                            <span class="es-magazine-price"><?php echo esc_html($event['price']); ?></span>
                            <?php endif; ?>
                        </div>
                        
                    </div>
                    
                </div>
            </div>
            
        </article>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Compact List Layout
     */
    private static function render_compact($event) {
        ob_start();
        ?>
        <article class="es-event es-layout-compact" id="event-<?php echo esc_attr($event['id']); ?>">
            
            <div class="es-compact-row">
                
                <div class="es-compact-date">
                    <?php if (!empty($event['date_day'])): ?>
                    <div class="es-date-number"><?php echo esc_html($event['date_day']); ?></div>
                    <div class="es-date-month-short"><?php echo esc_html($event['date_month_short']); ?></div>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($event['image'])): ?>
                <div class="es-compact-image">
                    <img src="<?php echo esc_url($event['image']); ?>" alt="<?php echo esc_attr($event['title']); ?>">
                </div>
                <?php endif; ?>
                
                <div class="es-compact-info">
                    <h3 class="es-compact-title">
                        <a href="<?php echo esc_url($event['permalink']); ?>">
                            <?php echo esc_html($event['title']); ?>
                        </a>
                    </h3>
                    
                    <div class="es-compact-meta">
                        <?php if (!empty($event['time'])): ?>
                        <span class="es-time"><?php echo esc_html($event['time']); ?></span>
                        <?php endif; ?>
                        
                        <?php if (!empty($event['location'])): ?>
                        <span class="es-separator">|</span>
                        <span class="es-location"><?php echo esc_html($event['location']); ?></span>
                        <?php endif; ?>
                        
                        <?php if (!empty($event['category'])): ?>
                        <span class="es-category"><?php echo esc_html($event['category']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="es-compact-actions">
                    <?php if (!empty($event['price'])): ?>
                    <span class="es-compact-price"><?php echo esc_html($event['price']); ?></span>
                    <?php endif; ?>
                    
                    <a href="<?php echo esc_url($event['permalink']); ?>" class="es-compact-link">
                        <span class="dashicons dashicons-arrow-right-alt2"></span>
                    </a>
                </div>
                
            </div>
            
        </article>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get layout stylesheet
     * 
     * @param string $layout_key Layout identifier
     * @return string CSS code
     */
    public static function get_layout_css($layout_key) {
        $method = 'get_css_' . $layout_key;
        if (method_exists(__CLASS__, $method)) {
            return self::$method();
        }
        return '';
    }
    
    /**
     * Classic Layout CSS
     */
    private static function get_css_classic() {
        return '
        .es-layout-classic {
            background: var(--es-card-bg, #fff);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .es-layout-classic:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.15);
        }
        
        .es-layout-classic .es-event-image {
            position: relative;
            padding-top: 56.25%; /* 16:9 */
            overflow: hidden;
        }
        
        .es-layout-classic .es-event-image img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .es-layout-classic .es-event-category {
            position: absolute;
            top: 16px;
            right: 16px;
            padding: 6px 16px;
            background: var(--es-primary, #3582c4);
            color: #fff;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }
        
        .es-layout-classic .es-event-content {
            padding: 24px;
        }
        
        .es-layout-classic .es-event-title {
            margin: 0 0 16px 0;
            font-size: 24px;
            font-weight: 700;
            line-height: 1.3;
        }
        
        .es-layout-classic .es-event-title a {
            color: var(--es-text, #333);
            text-decoration: none;
            transition: color 0.2s ease;
        }
        
        .es-layout-classic .es-event-title a:hover {
            color: var(--es-primary, #3582c4);
        }
        
        .es-layout-classic .es-event-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 16px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--es-border, #eee);
        }
        
        .es-layout-classic .es-meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
            color: var(--es-text-secondary, #666);
            font-size: 14px;
        }
        
        .es-layout-classic .es-event-description {
            margin-bottom: 16px;
            color: var(--es-text, #333);
            line-height: 1.6;
        }
        
        .es-layout-classic .es-event-artists {
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .es-layout-classic .es-artists-label {
            font-weight: 600;
            color: var(--es-text, #333);
        }
        
        .es-layout-classic .es-event-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 20px;
            border-top: 1px solid var(--es-border, #eee);
        }
        
        .es-layout-classic .es-event-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: var(--es-primary, #3582c4);
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            transition: background 0.2s ease;
        }
        
        .es-layout-classic .es-event-button:hover {
            background: var(--es-primary-hover, #2271b1);
        }
        
        .es-layout-classic .es-event-price {
            font-size: 20px;
            font-weight: 700;
            color: var(--es-primary, #3582c4);
        }
        ';
    }
    
    /**
     * Modern Layout CSS
     */
    private static function get_css_modern() {
        return '
        .es-layout-modern .es-event-card {
            display: flex;
            background: var(--es-card-bg, #fff);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }
        
        .es-layout-modern .es-event-card:hover {
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        }
        
        .es-layout-modern .es-event-image-side {
            flex: 0 0 300px;
            position: relative;
        }
        
        .es-layout-modern .es-event-image-side img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .es-layout-modern .es-event-category-badge {
            position: absolute;
            bottom: 16px;
            left: 16px;
            padding: 6px 12px;
            background: rgba(53, 130, 196, 0.9);
            color: #fff;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            backdrop-filter: blur(4px);
        }
        
        .es-layout-modern .es-event-content-side {
            flex: 1;
            display: flex;
            padding: 32px;
            gap: 24px;
        }
        
        .es-layout-modern .es-event-date-block {
            flex: 0 0 80px;
            text-align: center;
            padding: 16px;
            background: var(--es-primary-light, #e3f2fd);
            border-radius: 8px;
            height: fit-content;
        }
        
        .es-layout-modern .es-date-day {
            font-size: 36px;
            font-weight: 700;
            color: var(--es-primary, #3582c4);
            line-height: 1;
        }
        
        .es-layout-modern .es-date-month {
            font-size: 14px;
            font-weight: 600;
            color: var(--es-text-secondary, #666);
            text-transform: uppercase;
            margin-top: 4px;
        }
        
        .es-layout-modern .es-event-main {
            flex: 1;
        }
        
        .es-layout-modern .es-event-title {
            margin: 0 0 12px 0;
            font-size: 28px;
            font-weight: 700;
            line-height: 1.2;
        }
        
        .es-layout-modern .es-event-title a {
            color: var(--es-text, #333);
            text-decoration: none;
            transition: color 0.2s ease;
        }
        
        .es-layout-modern .es-event-title a:hover {
            color: var(--es-primary, #3582c4);
        }
        
        .es-layout-modern .es-event-excerpt {
            margin-bottom: 16px;
            color: var(--es-text-secondary, #666);
            line-height: 1.6;
        }
        
        .es-layout-modern .es-event-meta-inline {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            font-size: 14px;
            color: var(--es-text-secondary, #666);
        }
        
        .es-layout-modern .es-event-meta-inline .dashicons {
            font-size: 18px;
            width: 18px;
            height: 18px;
        }
        
        .es-layout-modern .es-event-actions {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .es-layout-modern .es-button-primary {
            padding: 10px 20px;
            background: var(--es-primary, #3582c4);
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
            transition: background 0.2s ease;
        }
        
        .es-layout-modern .es-button-primary:hover {
            background: var(--es-primary-hover, #2271b1);
        }
        
        .es-layout-modern .es-price-tag {
            padding: 8px 16px;
            background: var(--es-success-light, #e8f5e9);
            color: var(--es-success, #4caf50);
            border-radius: 6px;
            font-weight: 700;
        }
        
        @media (max-width: 768px) {
            .es-layout-modern .es-event-card {
                flex-direction: column;
            }
            
            .es-layout-modern .es-event-image-side {
                flex: 0 0 200px;
            }
            
            .es-layout-modern .es-event-content-side {
                flex-direction: column;
            }
        }
        ';
    }
    
    /**
     * Minimal Layout CSS
     */
    private static function get_css_minimal() {
        return '
        .es-layout-minimal {
            padding: 32px 0;
            border-bottom: 1px solid var(--es-border, #eee);
        }
        
        .es-layout-minimal:last-child {
            border-bottom: none;
        }
        
        .es-minimal-header {
            margin-bottom: 12px;
        }
        
        .es-minimal-header .es-category-tag {
            display: inline-block;
            padding: 4px 12px;
            background: var(--es-primary-light, #e3f2fd);
            color: var(--es-primary, #3582c4);
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        
        .es-minimal-title {
            margin: 0;
            font-size: 32px;
            font-weight: 700;
            line-height: 1.2;
        }
        
        .es-minimal-title a {
            color: var(--es-text, #333);
            text-decoration: none;
            transition: color 0.2s ease;
        }
        
        .es-minimal-title a:hover {
            color: var(--es-primary, #3582c4);
        }
        
        .es-minimal-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            color: var(--es-text-secondary, #666);
        }
        
        .es-minimal-meta .es-separator {
            color: var(--es-border, #ccc);
        }
        
        .es-minimal-description {
            margin-bottom: 20px;
            font-size: 16px;
            line-height: 1.7;
            color: var(--es-text, #333);
        }
        
        .es-minimal-artists {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 20px;
        }
        
        .es-minimal-artists .es-artist-tag {
            padding: 6px 12px;
            background: var(--es-surface, #f5f5f5);
            border-radius: 20px;
            font-size: 13px;
            color: var(--es-text-secondary, #666);
        }
        
        .es-minimal-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .es-minimal-link {
            color: var(--es-primary, #3582c4);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s ease;
        }
        
        .es-minimal-link:hover {
            color: var(--es-primary-hover, #2271b1);
        }
        
        .es-minimal-price {
            font-size: 18px;
            font-weight: 700;
            color: var(--es-text, #333);
        }
        ';
    }
    
    /**
     * Magazine Layout CSS
     */
    private static function get_css_magazine() {
        return '
        .es-layout-magazine {
            margin-bottom: 40px;
        }
        
        .es-magazine-hero {
            position: relative;
            min-height: 500px;
            background-size: cover;
            background-position: center;
            border-radius: 16px;
            overflow: hidden;
        }
        
        .es-magazine-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to bottom, rgba(0,0,0,0.3), rgba(0,0,0,0.8));
            display: flex;
            align-items: flex-end;
            padding: 48px;
        }
        
        .es-magazine-content {
            max-width: 800px;
            color: #fff;
        }
        
        .es-magazine-category {
            display: inline-block;
            padding: 8px 16px;
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 16px;
        }
        
        .es-magazine-title {
            margin: 0 0 16px 0;
            font-size: 48px;
            font-weight: 800;
            line-height: 1.1;
            text-shadow: 0 2px 8px rgba(0,0,0,0.5);
        }
        
        .es-magazine-title a {
            color: #fff;
            text-decoration: none;
            transition: opacity 0.2s ease;
        }
        
        .es-magazine-title a:hover {
            opacity: 0.9;
        }
        
        .es-magazine-meta {
            display: flex;
            gap: 24px;
            margin-bottom: 20px;
            font-size: 15px;
        }
        
        .es-magazine-meta .es-meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .es-magazine-meta .dashicons {
            font-size: 20px;
            width: 20px;
            height: 20px;
        }
        
        .es-magazine-excerpt {
            margin-bottom: 24px;
            font-size: 18px;
            line-height: 1.6;
            opacity: 0.95;
        }
        
        .es-magazine-footer {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .es-magazine-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 14px 28px;
            background: #fff;
            color: var(--es-text, #333);
            text-decoration: none;
            border-radius: 8px;
            font-weight: 700;
            font-size: 15px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .es-magazine-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.3);
        }
        
        .es-magazine-price {
            padding: 12px 24px;
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
            border-radius: 8px;
            font-size: 24px;
            font-weight: 700;
        }
        
        @media (max-width: 768px) {
            .es-magazine-hero {
                min-height: 400px;
            }
            
            .es-magazine-overlay {
                padding: 24px;
            }
            
            .es-magazine-title {
                font-size: 32px;
            }
            
            .es-magazine-meta {
                flex-wrap: wrap;
                gap: 12px;
            }
        }
        ';
    }
    
    /**
     * Compact Layout CSS
     */
    private static function get_css_compact() {
        return '
        .es-layout-compact {
            border-bottom: 1px solid var(--es-border, #eee);
        }
        
        .es-layout-compact:last-child {
            border-bottom: none;
        }
        
        .es-compact-row {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 20px 0;
            transition: background 0.2s ease;
        }
        
        .es-compact-row:hover {
            background: var(--es-surface-hover, #f9f9f9);
        }
        
        .es-compact-date {
            flex: 0 0 60px;
            text-align: center;
            padding: 12px;
            background: var(--es-primary-light, #e3f2fd);
            border-radius: 8px;
        }
        
        .es-date-number {
            font-size: 28px;
            font-weight: 700;
            color: var(--es-primary, #3582c4);
            line-height: 1;
        }
        
        .es-date-month-short {
            font-size: 11px;
            font-weight: 600;
            color: var(--es-text-secondary, #666);
            text-transform: uppercase;
            margin-top: 4px;
        }
        
        .es-compact-image {
            flex: 0 0 100px;
            height: 100px;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .es-compact-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .es-compact-info {
            flex: 1;
            min-width: 0;
        }
        
        .es-compact-title {
            margin: 0 0 8px 0;
            font-size: 18px;
            font-weight: 600;
            line-height: 1.3;
        }
        
        .es-compact-title a {
            color: var(--es-text, #333);
            text-decoration: none;
            transition: color 0.2s ease;
        }
        
        .es-compact-title a:hover {
            color: var(--es-primary, #3582c4);
        }
        
        .es-compact-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            font-size: 13px;
            color: var(--es-text-secondary, #666);
        }
        
        .es-compact-meta .es-separator {
            color: var(--es-border, #ccc);
        }
        
        .es-compact-meta .es-category {
            padding: 2px 8px;
            background: var(--es-surface, #f5f5f5);
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .es-compact-actions {
            flex: 0 0 auto;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .es-compact-price {
            font-size: 18px;
            font-weight: 700;
            color: var(--es-primary, #3582c4);
            white-space: nowrap;
        }
        
        .es-compact-link {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: var(--es-primary, #3582c4);
            color: #fff;
            border-radius: 50%;
            text-decoration: none;
            transition: transform 0.2s ease, background 0.2s ease;
        }
        
        .es-compact-link:hover {
            transform: scale(1.1);
            background: var(--es-primary-hover, #2271b1);
        }
        
        .es-compact-link .dashicons {
            font-size: 20px;
            width: 20px;
            height: 20px;
        }
        
        @media (max-width: 768px) {
            .es-compact-row {
                flex-wrap: wrap;
            }
            
            .es-compact-image {
                flex: 0 0 80px;
                height: 80px;
            }
            
            .es-compact-actions {
                flex: 0 0 100%;
                justify-content: space-between;
                padding-top: 12px;
                border-top: 1px solid var(--es-border, #eee);
            }
        }
        ';
    }
}