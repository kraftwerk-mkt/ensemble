<?php
/**
 * Agenda Timeline Template
 * 
 * Displays agenda in a vertical timeline format
 * 
 * @package Ensemble
 * @subpackage Addons/Agenda
 * @since 1.0.0
 * 
 * Variables available:
 * - $event_id (int)
 * - $agenda (array)
 * - $atts (array) - shortcode attributes
 */

if (!defined('ABSPATH')) exit;

// Check if we have days
if (empty($agenda['days'])) {
    return;
}

$days = $agenda['days'];
$active_day = isset($atts['day']) && is_numeric($atts['day']) ? intval($atts['day']) - 1 : 0;

// Helper function to get icon
if (!function_exists('ensemble_get_agenda_icon')) {
    function ensemble_get_agenda_icon($type) {
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/></svg>';
    }
}
?>

<div class="es-agenda es-agenda-timeline-view">
    
    <?php if (count($days) > 1): ?>
    <!-- Day Tabs -->
    <div class="es-agenda-day-tabs" role="tablist">
        <?php foreach ($days as $index => $day): 
            $date = !empty($day['date']) ? strtotime($day['date']) : false;
            $is_active = $index === $active_day;
        ?>
        <button 
            class="es-agenda-day-tab <?php echo $is_active ? 'is-active' : ''; ?>" 
            data-day="<?php echo $index; ?>"
            role="tab"
            aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>"
        >
            <?php if ($date): ?>
            <span class="es-agenda-day-tab-day"><?php echo date_i18n('D', $date); ?></span>
            <span class="es-agenda-day-tab-date"><?php echo date_i18n('d', $date); ?></span>
            <span class="es-agenda-day-tab-month"><?php echo date_i18n('M', $date); ?></span>
            <?php else: ?>
            <span class="es-agenda-day-tab-date"><?php printf(__('Tag %d', 'ensemble'), $index + 1); ?></span>
            <?php endif; ?>
        </button>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <!-- Day Contents -->
    <?php foreach ($days as $day_index => $day): 
        $is_active = $day_index === $active_day;
        $date = !empty($day['date']) ? strtotime($day['date']) : false;
        $sessions = !empty($day['sessions']) ? $day['sessions'] : array();
        
        // Sort sessions by start time
        usort($sessions, function($a, $b) {
            return strcmp($a['start'] ?? '', $b['start'] ?? '');
        });
    ?>
    <div 
        class="es-agenda-day-content <?php echo $is_active ? 'is-active' : ''; ?>" 
        data-day="<?php echo $day_index; ?>"
        role="tabpanel"
    >
        <?php if (count($days) === 1 && $date): ?>
        <div class="es-agenda-day-header">
            <h3 class="es-agenda-day-title"><?php echo date_i18n('l', $date); ?></h3>
            <div class="es-agenda-day-date"><?php echo date_i18n(get_option('date_format'), $date); ?></div>
        </div>
        <?php endif; ?>
        
        <div class="es-agenda-timeline">
            <?php if (!empty($sessions)): ?>
                <?php foreach ($sessions as $session): 
                    $session_type = $session['type'] ?? 'talk';
                    $session_types = ES_Agenda_Addon::$session_types;
                    $type_info = $session_types[$session_type] ?? $session_types['custom'];
                    $is_break = !empty($type_info['is_break']);
                ?>
                <div class="es-agenda-session <?php echo $is_break ? 'is-break' : ''; ?>">
                    
                    <div class="es-agenda-session-time">
                        <?php echo esc_html($session['start'] ?? ''); ?>
                        <?php if (!empty($session['end'])): ?>
                        – <?php echo esc_html($session['end']); ?>
                        <?php endif; ?>
                    </div>
                    
                    <div class="es-agenda-session-dot"></div>
                    
                    <div class="es-agenda-session-card">
                        <div class="es-agenda-session-header">
                            <div class="es-agenda-session-icon">
                                <?php echo ensemble_get_agenda_icon($type_info['icon'] ?? 'pause'); ?>
                            </div>
                            <div class="es-agenda-session-info">
                                <div class="es-agenda-session-type"><?php echo esc_html($type_info['label']); ?></div>
                                <h4 class="es-agenda-session-title"><?php echo esc_html($session['title'] ?? ''); ?></h4>
                                <?php if (!empty($session['room'])): ?>
                                <span class="es-agenda-session-room">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                        <circle cx="12" cy="10" r="3"/>
                                    </svg>
                                    <?php echo esc_html($session['room']); ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($session['description'])): ?>
                        <div class="es-agenda-session-description">
                            <?php echo wp_kses_post($session['description']); ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!$is_break && !empty($session['speakers'])): ?>
                        <div class="es-agenda-session-speakers">
                            <?php foreach ($session['speakers'] as $speaker): 
                                $speaker_id = $speaker['id'] ?? 0;
                                $speaker_post = $speaker_id ? get_post($speaker_id) : null;
                                $speaker_name = $speaker['name'] ?? ($speaker_post ? $speaker_post->post_title : '');
                                $speaker_image = $speaker['image'] ?? ($speaker_id ? get_the_post_thumbnail_url($speaker_id, 'thumbnail') : '');
                                $speaker_role = $speaker['role_title'] ?? get_post_meta($speaker_id, 'artist_role', true);
                                $speaker_url = $speaker_id ? get_permalink($speaker_id) : '';
                            ?>
                            <a href="<?php echo esc_url($speaker_url); ?>" class="es-agenda-speaker">
                                <?php if ($speaker_image): ?>
                                <img src="<?php echo esc_url($speaker_image); ?>" alt="<?php echo esc_attr($speaker_name); ?>" class="es-agenda-speaker-image">
                                <?php endif; ?>
                                <div class="es-agenda-speaker-info">
                                    <span class="es-agenda-speaker-name"><?php echo esc_html($speaker_name); ?></span>
                                    <?php if ($speaker_role): ?>
                                    <span class="es-agenda-speaker-role"><?php echo esc_html($speaker_role); ?></span>
                                    <?php endif; ?>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($session['materials'])): ?>
                        <div class="es-agenda-session-materials">
                            <?php foreach ($session['materials'] as $material): ?>
                            <a href="<?php echo esc_url($material['url']); ?>" class="es-agenda-material-link" target="_blank">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                    <polyline points="7 10 12 15 17 10"/>
                                    <line x1="12" y1="15" x2="12" y2="3"/>
                                </svg>
                                <?php echo esc_html($material['title'] ?? __('Download', 'ensemble')); ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($session['livestream'])): ?>
                        <div class="es-agenda-session-livestream">
                            <a href="<?php echo esc_url($session['livestream']); ?>" class="es-agenda-livestream-link" target="_blank">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polygon points="5 3 19 12 5 21 5 3"/>
                                </svg>
                                <?php _e('Livestream', 'ensemble'); ?>
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
            <div class="es-agenda-empty">
                <p><?php _e('Noch keine Sessions für diesen Tag.', 'ensemble'); ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php if (count($days) > 1): ?>
<script>
(function() {
    var tabs = document.querySelectorAll('.es-agenda-day-tab');
    var panels = document.querySelectorAll('.es-agenda-day-content');
    
    tabs.forEach(function(tab) {
        tab.addEventListener('click', function() {
            var dayIndex = this.getAttribute('data-day');
            
            // Update tabs
            tabs.forEach(function(t) {
                t.classList.remove('is-active');
                t.setAttribute('aria-selected', 'false');
            });
            this.classList.add('is-active');
            this.setAttribute('aria-selected', 'true');
            
            // Update panels
            panels.forEach(function(panel) {
                if (panel.getAttribute('data-day') === dayIndex) {
                    panel.classList.add('is-active');
                } else {
                    panel.classList.remove('is-active');
                }
            });
        });
    });
})();
</script>
<?php endif; ?>
