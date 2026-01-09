<?php
/**
 * Agenda Grid Template
 * 
 * Displays agenda in a room × time grid format
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
$rooms = !empty($agenda['rooms']) ? $agenda['rooms'] : array();
$active_day = isset($atts['day']) && is_numeric($atts['day']) ? intval($atts['day']) - 1 : 0;

// If no rooms defined, use timeline view instead
if (empty($rooms)) {
    include dirname(__FILE__) . '/agenda-timeline.php';
    return;
}

// Get all unique time slots for the active day
$sessions = !empty($days[$active_day]['sessions']) ? $days[$active_day]['sessions'] : array();
$time_slots = array();

foreach ($sessions as $session) {
    $start = $session['start'] ?? '';
    if ($start && !in_array($start, $time_slots)) {
        $time_slots[] = $start;
    }
}
sort($time_slots);

// Build session lookup by room and time
$session_grid = array();
foreach ($sessions as $session) {
    $room = $session['room'] ?? '';
    $start = $session['start'] ?? '';
    if ($room && $start) {
        $session_grid[$room][$start] = $session;
    }
}

$num_rooms = count($rooms);
$grid_cols = $num_rooms + 1; // +1 for time column
?>

<div class="es-agenda es-agenda-grid-view">
    
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
    
    <!-- Grid -->
    <div class="es-agenda-grid" style="grid-template-columns: 80px repeat(<?php echo $num_rooms; ?>, 1fr);">
        
        <!-- Header Row -->
        <div class="es-agenda-grid-cell is-header"></div>
        <?php foreach ($rooms as $room): ?>
        <div class="es-agenda-grid-cell is-header"><?php echo esc_html($room); ?></div>
        <?php endforeach; ?>
        
        <!-- Time Rows -->
        <?php foreach ($time_slots as $time): ?>
        <div class="es-agenda-grid-cell es-agenda-grid-time"><?php echo esc_html($time); ?></div>
        
        <?php foreach ($rooms as $room): 
            $session = isset($session_grid[$room][$time]) ? $session_grid[$room][$time] : null;
        ?>
        <div class="es-agenda-grid-cell">
            <?php if ($session): 
                $session_type = $session['type'] ?? 'talk';
                $session_types = ES_Agenda_Addon::$session_types;
                $type_info = $session_types[$session_type] ?? $session_types['custom'];
                $is_break = !empty($type_info['is_break']);
            ?>
            <div class="es-agenda-grid-session <?php echo $is_break ? 'is-break' : ''; ?>">
                <div class="es-agenda-grid-session-title"><?php echo esc_html($session['title'] ?? ''); ?></div>
                <?php if (!$is_break && !empty($session['speakers'])): ?>
                <div class="es-agenda-grid-session-speaker">
                    <?php 
                    $speaker_names = array();
                    foreach ($session['speakers'] as $speaker) {
                        $speaker_names[] = $speaker['name'] ?? '';
                    }
                    echo esc_html(implode(', ', array_filter($speaker_names)));
                    ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        
        <?php endforeach; ?>
    </div>
</div>

<?php if (count($days) > 1): ?>
<script>
(function() {
    var tabs = document.querySelectorAll('.es-agenda-day-tab');
    
    tabs.forEach(function(tab) {
        tab.addEventListener('click', function() {
            var dayIndex = this.getAttribute('data-day');
            // Reload page with day parameter
            var url = new URL(window.location.href);
            url.searchParams.set('agenda_day', parseInt(dayIndex) + 1);
            window.location.href = url.toString();
        });
    });
})();
</script>
<?php endif; ?>
