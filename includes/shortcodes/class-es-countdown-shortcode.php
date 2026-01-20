<?php
/**
 * Ensemble Countdown Shortcode
 * 
 * Displays a countdown timer to an event or specific date.
 * Features live JavaScript countdown with days, hours, minutes, seconds.
 * 
 * @package Ensemble
 * @since 2.9.5
 */

if (!defined('ABSPATH')) {
    exit;
}

class ES_Countdown_Shortcode {
    
    /**
     * Track if assets are enqueued
     */
    private static $assets_enqueued = false;
    
    /**
     * Register shortcode
     */
    public function register_shortcodes() {
        add_shortcode('ensemble_countdown', array($this, 'countdown_shortcode'));
    }
    
    /**
     * Countdown shortcode
     * 
     * Usage:
     * [ensemble_countdown event_id="123"]
     * [ensemble_countdown date="2026-03-15" time="09:00" title="Conference Start"]
     * [ensemble_countdown date="2026-12-31 23:59:59" style="minimal"]
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function countdown_shortcode($atts) {
        // Load CSS module
        if (class_exists('ES_CSS_Loader')) {
            ES_CSS_Loader::enqueue('countdown');
        }
        
        $atts = shortcode_atts(array(
            // Event-based countdown
            'event_id'        => '',
            
            // Manual date countdown
            'date'            => '',
            'time'            => '',
            'title'           => '',
            
            // Display options
            'style'           => 'default',      // default, minimal, compact, hero
            'show_days'       => 'true',
            'show_hours'      => 'true',
            'show_minutes'    => 'true',
            'show_seconds'    => 'true',
            'show_labels'     => 'true',
            'show_title'      => 'true',
            'show_date'       => 'true',
            'show_link'       => 'true',
            'link_text'       => __('View Event', 'ensemble'),
            
            // Expired message
            'expired_text'    => __('Event has started!', 'ensemble'),
            'hide_expired'    => 'false',
            
            // Custom labels
            'label_days'      => __('Days', 'ensemble'),
            'label_hours'     => __('Hours', 'ensemble'),
            'label_minutes'   => __('Minutes', 'ensemble'),
            'label_seconds'   => __('Seconds', 'ensemble'),
            
            // Additional
            'class'           => '',
        ), $atts, 'ensemble_countdown');
        
        // Parse booleans
        $show_days = filter_var($atts['show_days'], FILTER_VALIDATE_BOOLEAN);
        $show_hours = filter_var($atts['show_hours'], FILTER_VALIDATE_BOOLEAN);
        $show_minutes = filter_var($atts['show_minutes'], FILTER_VALIDATE_BOOLEAN);
        $show_seconds = filter_var($atts['show_seconds'], FILTER_VALIDATE_BOOLEAN);
        $show_labels = filter_var($atts['show_labels'], FILTER_VALIDATE_BOOLEAN);
        $show_title = filter_var($atts['show_title'], FILTER_VALIDATE_BOOLEAN);
        $show_date = filter_var($atts['show_date'], FILTER_VALIDATE_BOOLEAN);
        $show_link = filter_var($atts['show_link'], FILTER_VALIDATE_BOOLEAN);
        $hide_expired = filter_var($atts['hide_expired'], FILTER_VALIDATE_BOOLEAN);
        
        // Get target date/time
        $target_datetime = null;
        $title = $atts['title'];
        $permalink = '';
        $formatted_date = '';
        
        // Event-based countdown
        if (!empty($atts['event_id'])) {
            $event_id = absint($atts['event_id']);
            $event = get_post($event_id);
            
            if (!$event || $event->post_status !== 'publish') {
                return '<!-- Ensemble Countdown: Event not found -->';
            }
            
            // Get event date
            $start_date = get_post_meta($event_id, 'es_event_start_date', true);
            if (empty($start_date)) {
                $start_date = get_post_meta($event_id, 'event_date', true);
            }
            if (empty($start_date)) {
                $start_date = get_post_meta($event_id, '_event_date', true);
            }
            
            if (empty($start_date)) {
                return '<!-- Ensemble Countdown: Event has no date -->';
            }
            
            // Get event time
            $start_time = get_post_meta($event_id, 'es_event_start_time', true);
            if (empty($start_time)) {
                $start_time = get_post_meta($event_id, 'event_time', true);
            }
            if (empty($start_time)) {
                $start_time = get_post_meta($event_id, '_event_time', true);
            }
            
            $target_datetime = $start_date;
            if (!empty($start_time)) {
                $target_datetime .= ' ' . $start_time;
            }
            
            // Use event title if not specified
            if (empty($title)) {
                $title = get_the_title($event_id);
            }
            
            $permalink = get_permalink($event_id);
            $formatted_date = date_i18n(get_option('date_format'), strtotime($start_date));
            if (!empty($start_time)) {
                $formatted_date .= ' ' . date_i18n(get_option('time_format'), strtotime($start_time));
            }
            
        // Manual date countdown
        } elseif (!empty($atts['date'])) {
            $target_datetime = $atts['date'];
            if (!empty($atts['time'])) {
                $target_datetime .= ' ' . $atts['time'];
            }
            $formatted_date = date_i18n(get_option('date_format'), strtotime($atts['date']));
            if (!empty($atts['time'])) {
                $formatted_date .= ' ' . date_i18n(get_option('time_format'), strtotime($atts['time']));
            }
        } else {
            return '<!-- Ensemble Countdown: No event_id or date specified -->';
        }
        
        // Parse target datetime
        $target_timestamp = strtotime($target_datetime);
        if (!$target_timestamp) {
            return '<!-- Ensemble Countdown: Invalid date format -->';
        }
        
        // Check if expired
        $now = current_time('timestamp');
        $is_expired = $target_timestamp <= $now;
        
        if ($is_expired && $hide_expired) {
            return '<!-- Ensemble Countdown: Event has passed -->';
        }
        
        // Enqueue assets
        $this->enqueue_assets();
        
        // Build classes
        $wrapper_classes = array(
            'es-countdown',
            'es-countdown--' . esc_attr($atts['style']),
        );
        if (!empty($atts['class'])) {
            $wrapper_classes[] = sanitize_html_class($atts['class']);
        }
        if ($is_expired) {
            $wrapper_classes[] = 'es-countdown--expired';
        }
        
        // Generate unique ID
        $countdown_id = 'es-countdown-' . uniqid();
        
        // Build output
        ob_start();
        ?>
        <div id="<?php echo esc_attr($countdown_id); ?>" 
             class="<?php echo esc_attr(implode(' ', $wrapper_classes)); ?>"
             data-target="<?php echo esc_attr($target_timestamp); ?>"
             data-expired-text="<?php echo esc_attr($atts['expired_text']); ?>"
             data-hide-expired="<?php echo $hide_expired ? 'true' : 'false'; ?>">
            
            <?php if ($show_title && !empty($title)): ?>
            <div class="es-countdown__title">
                <?php if ($show_link && !empty($permalink)): ?>
                    <a href="<?php echo esc_url($permalink); ?>"><?php echo esc_html($title); ?></a>
                <?php else: ?>
                    <?php echo esc_html($title); ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($show_date && !empty($formatted_date)): ?>
            <div class="es-countdown__date">
                <?php echo esc_html($formatted_date); ?>
            </div>
            <?php endif; ?>
            
            <?php if ($is_expired): ?>
            <div class="es-countdown__expired">
                <?php echo esc_html($atts['expired_text']); ?>
            </div>
            <?php else: ?>
            <div class="es-countdown__timer">
                <?php if ($show_days): ?>
                <div class="es-countdown__unit es-countdown__days">
                    <span class="es-countdown__value" data-unit="days">--</span>
                    <?php if ($show_labels): ?>
                    <span class="es-countdown__label"><?php echo esc_html($atts['label_days']); ?></span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($show_hours): ?>
                <div class="es-countdown__unit es-countdown__hours">
                    <span class="es-countdown__value" data-unit="hours">--</span>
                    <?php if ($show_labels): ?>
                    <span class="es-countdown__label"><?php echo esc_html($atts['label_hours']); ?></span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($show_minutes): ?>
                <div class="es-countdown__unit es-countdown__minutes">
                    <span class="es-countdown__value" data-unit="minutes">--</span>
                    <?php if ($show_labels): ?>
                    <span class="es-countdown__label"><?php echo esc_html($atts['label_minutes']); ?></span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($show_seconds): ?>
                <div class="es-countdown__unit es-countdown__seconds">
                    <span class="es-countdown__value" data-unit="seconds">--</span>
                    <?php if ($show_labels): ?>
                    <span class="es-countdown__label"><?php echo esc_html($atts['label_seconds']); ?></span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($show_link && !empty($permalink) && !$is_expired): ?>
            <div class="es-countdown__action">
                <a href="<?php echo esc_url($permalink); ?>" class="es-countdown__link">
                    <?php echo esc_html($atts['link_text']); ?>
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M5 12h14M12 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Enqueue countdown assets
     */
    private function enqueue_assets() {
        if (self::$assets_enqueued) {
            return;
        }
        
        // Enqueue CSS file
        wp_enqueue_style(
            'ensemble-countdown',
            ENSEMBLE_PLUGIN_URL . 'assets/css/ensemble-countdown.css',
            array(),
            ENSEMBLE_VERSION
        );
        
        // Inline JS in footer
        add_action('wp_footer', array($this, 'output_countdown_js'), 20);
        
        self::$assets_enqueued = true;
    }
    
    /**
     * Output countdown JavaScript
     */
    public function output_countdown_js() {
        ?>
        <script id="es-countdown-js">
        (function() {
            function initCountdowns() {
                var countdowns = document.querySelectorAll('.es-countdown[data-target]');
                
                countdowns.forEach(function(el) {
                    var target = parseInt(el.dataset.target, 10) * 1000;
                    var expiredText = el.dataset.expiredText || 'Event has started!';
                    var hideExpired = el.dataset.hideExpired === 'true';
                    
                    function updateCountdown() {
                        var now = Date.now();
                        var diff = target - now;
                        
                        if (diff <= 0) {
                            // Expired
                            el.classList.add('es-countdown--expired');
                            var timer = el.querySelector('.es-countdown__timer');
                            if (timer) {
                                if (hideExpired) {
                                    el.style.display = 'none';
                                } else {
                                    timer.innerHTML = '<div class="es-countdown__expired">' + expiredText + '</div>';
                                }
                            }
                            return false;
                        }
                        
                        var days = Math.floor(diff / (1000 * 60 * 60 * 24));
                        var hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                        var minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                        var seconds = Math.floor((diff % (1000 * 60)) / 1000);
                        
                        var daysEl = el.querySelector('[data-unit="days"]');
                        var hoursEl = el.querySelector('[data-unit="hours"]');
                        var minutesEl = el.querySelector('[data-unit="minutes"]');
                        var secondsEl = el.querySelector('[data-unit="seconds"]');
                        
                        if (daysEl) daysEl.textContent = days.toString().padStart(2, '0');
                        if (hoursEl) hoursEl.textContent = hours.toString().padStart(2, '0');
                        if (minutesEl) minutesEl.textContent = minutes.toString().padStart(2, '0');
                        if (secondsEl) secondsEl.textContent = seconds.toString().padStart(2, '0');
                        
                        return true;
                    }
                    
                    // Initial update
                    if (updateCountdown()) {
                        // Continue updating every second
                        setInterval(updateCountdown, 1000);
                    }
                });
            }
            
            // Init on DOM ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initCountdowns);
            } else {
                initCountdowns();
            }
        })();
        </script>
        <?php
    }
    
}
