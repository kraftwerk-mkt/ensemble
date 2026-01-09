<?php
/**
 * Timetable Editor Admin Template
 *
 * @package Ensemble
 * @since 2.9.0
 * 
 * @var int $event_id
 * @var array $events
 * @var array|null $timetable_data
 */

if (!defined('ABSPATH')) exit;

// Helper functions
function es_tt_time_to_minutes($time) {
    if (empty($time)) return 0;
    if (preg_match('/^(\d{1,2}):(\d{2})/', $time, $matches)) {
        return intval($matches[1]) * 60 + intval($matches[2]);
    }
    return 0;
}

function es_tt_minutes_to_time($minutes) {
    return sprintf('%02d:%02d', floor($minutes / 60), $minutes % 60);
}

// Separate events with/without timetable
$events_with_timetable = array();
$events_without_timetable = array();
foreach ($events as $evt) {
    if ($evt['has_timetable']) {
        $events_with_timetable[] = $evt;
    } else {
        $events_without_timetable[] = $evt;
    }
}
?>
<div class="wrap es-timetable-wrap">
    <h1 class="es-timetable-title">
        <span class="dashicons dashicons-schedule"></span>
        <?php _e('Timetable Editor', 'ensemble'); ?>
    </h1>
    
    <!-- Event Selector - Only show when event is selected -->
    <?php if ($event_id && $timetable_data): ?>
    <div class="es-timetable-header">
        <div class="es-timetable-event-select">
            <label for="es-event-select"><?php _e('Select Event:', 'ensemble'); ?></label>
            <select id="es-event-select" class="es-event-dropdown">
                <option value=""><?php _e('-- Choose an event --', 'ensemble'); ?></option>
                <?php if (!empty($events_with_timetable)): ?>
                    <optgroup label="<?php esc_attr_e('With Timetable', 'ensemble'); ?>">
                        <?php foreach ($events_with_timetable as $evt): ?>
                            <option value="<?php echo esc_attr($evt['id']); ?>" <?php selected($event_id, $evt['id']); ?>>
                                <?php echo esc_html($evt['title']); ?>
                                <?php if ($evt['date']): ?>(<?php echo esc_html($evt['date']); ?>)<?php endif; ?>
                                - <?php echo $evt['speaker_count']; ?> speakers
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endif; ?>
                <?php if (!empty($events_without_timetable)): ?>
                    <optgroup label="<?php esc_attr_e('Without Timetable', 'ensemble'); ?>">
                        <?php foreach ($events_without_timetable as $evt): ?>
                            <option value="<?php echo esc_attr($evt['id']); ?>" <?php selected($event_id, $evt['id']); ?>>
                                <?php echo esc_html($evt['title']); ?>
                                <?php if ($evt['date']): ?>(<?php echo esc_html($evt['date']); ?>)<?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endif; ?>
            </select>
        </div>
        
        <div class="es-timetable-actions">
            <button type="button" id="es-add-speaker-btn" class="button">
                <span class="dashicons dashicons-plus-alt2"></span>
                <?php _e('Add Speaker', 'ensemble'); ?>
            </button>
            <button type="button" id="es-add-break-btn" class="button">
                <span class="dashicons dashicons-coffee"></span>
                <?php _e('Add Break', 'ensemble'); ?>
            </button>
            <button type="button" id="es-save-timetable" class="button button-primary">
                <span class="dashicons dashicons-saved"></span>
                <?php _e('Save Timetable', 'ensemble'); ?>
            </button>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (!$event_id): ?>
    <!-- Events List (like Wizard) -->
    <div class="es-timetable-events-section">
        <div class="es-events-toolbar">
            <div class="es-toolbar-left">
                <h3><?php _e('Select an Event to Edit', 'ensemble'); ?></h3>
                <span class="es-events-count"><?php echo count($events); ?> <?php _e('Events', 'ensemble'); ?></span>
            </div>
            <div class="es-toolbar-right">
                <div class="es-filter-group">
                    <select id="es-filter-timetable" class="es-filter-select">
                        <option value=""><?php _e('All Events', 'ensemble'); ?></option>
                        <option value="with"><?php _e('With Timetable', 'ensemble'); ?></option>
                        <option value="without"><?php _e('Without Timetable', 'ensemble'); ?></option>
                    </select>
                </div>
            </div>
        </div>
        
        <div class="es-timetable-events-grid">
            <?php foreach ($events as $evt): ?>
            <a href="?page=ensemble-timetable&event_id=<?php echo $evt['id']; ?>" 
               class="es-timetable-event-card <?php echo $evt['has_timetable'] ? 'has-timetable' : 'no-timetable'; ?>"
               data-has-timetable="<?php echo $evt['has_timetable'] ? 'with' : 'without'; ?>">
                <div class="es-event-card-status">
                    <?php if ($evt['has_timetable']): ?>
                        <span class="es-status-badge es-status-complete">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php echo $evt['speaker_count']; ?> Speakers
                        </span>
                    <?php else: ?>
                        <span class="es-status-badge es-status-empty">
                            <span class="dashicons dashicons-minus"></span>
                            <?php _e('No Timetable', 'ensemble'); ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="es-event-card-content">
                    <h4 class="es-event-card-title"><?php echo esc_html($evt['title']); ?></h4>
                    <div class="es-event-card-meta">
                        <?php if ($evt['date']): ?>
                            <span class="es-meta-date">
                                <span class="dashicons dashicons-calendar-alt"></span>
                                <?php echo esc_html($evt['date']); ?>
                            </span>
                        <?php endif; ?>
                        <span class="es-meta-status es-status-<?php echo esc_attr($evt['status']); ?>">
                            <?php echo ucfirst($evt['status']); ?>
                        </span>
                    </div>
                </div>
                <div class="es-event-card-arrow">
                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                </div>
            </a>
            <?php endforeach; ?>
            
            <?php if (empty($events)): ?>
            <div class="es-no-events">
                <span class="dashicons dashicons-calendar-alt"></span>
                <p><?php _e('No events found. Create events in the Event Wizard first.', 'ensemble'); ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php elseif ($timetable_data): 
        $room_count = count($timetable_data['rooms']);
        $start_minutes = es_tt_time_to_minutes($timetable_data['start_time']);
        $end_minutes = es_tt_time_to_minutes($timetable_data['end_time']);
        $interval = intval($timetable_data['time_interval']);
        $slot_height = 40;
        
        // Pre-calculate positions
        $session_positions = array();
        foreach ($timetable_data['sessions'] as $index => $session) {
            if (empty($session['time'])) continue;
            $session_start = es_tt_time_to_minutes($session['time']);
            $duration = !empty($session['duration']) ? intval($session['duration']) : 60;
            $offset_minutes = $session_start - $start_minutes;
            $session_positions[$index] = array(
                'top' => ($offset_minutes / $interval) * $slot_height,
                'height' => max(($duration / $interval) * $slot_height, 60),
                'end_time' => es_tt_minutes_to_time($session_start + $duration),
            );
        }
        
        $break_positions = array();
        foreach ($timetable_data['breaks'] as $index => $break) {
            if (empty($break['time'])) continue;
            $break_start = es_tt_time_to_minutes($break['time']);
            $duration = !empty($break['duration']) ? intval($break['duration']) : 30;
            $offset_minutes = $break_start - $start_minutes;
            $break_positions[$index] = array(
                'top' => ($offset_minutes / $interval) * $slot_height,
                'height' => max(($duration / $interval) * $slot_height, 30),
            );
        }
    ?>
    <!-- Timetable Grid -->
    <div class="es-timetable-container" 
         data-event-id="<?php echo esc_attr($event_id); ?>"
         data-start-time="<?php echo esc_attr($timetable_data['start_time']); ?>"
         data-end-time="<?php echo esc_attr($timetable_data['end_time']); ?>"
         data-interval="<?php echo esc_attr($interval); ?>">
        
        <!-- Info Bar -->
        <div class="es-timetable-info">
            <div class="es-timetable-event-info">
                <strong><?php echo esc_html($timetable_data['event_title']); ?></strong>
                <?php if ($timetable_data['start_date']): ?>
                    <span class="es-timetable-date">
                        <?php echo esc_html(date_i18n('l, j F Y', strtotime($timetable_data['start_date']))); ?>
                    </span>
                <?php endif; ?>
            </div>
            <div class="es-timetable-stats">
                <span class="es-stat">
                    <span class="dashicons dashicons-groups"></span>
                    <span class="es-stat-speakers"><?php echo count($timetable_data['sessions']) + count($timetable_data['unassigned']); ?></span> <?php _e('Speakers', 'ensemble'); ?>
                </span>
                <span class="es-stat">
                    <span class="dashicons dashicons-megaphone"></span>
                    <span class="es-stat-sessions"><?php echo count($timetable_data['sessions']); ?></span> <?php _e('Sessions', 'ensemble'); ?>
                </span>
                <span class="es-stat">
                    <span class="dashicons dashicons-location"></span>
                    <?php echo $room_count; ?> <?php _e('Rooms', 'ensemble'); ?>
                </span>
            </div>
        </div>
        
        <!-- The Grid -->
        <div class="es-timetable-grid-wrapper">
            <div class="es-timetable-grid" id="es-timetable-grid">
                <!-- Grid Header -->
                <div class="es-grid-header" style="grid-template-columns: 80px repeat(<?php echo $room_count; ?>, 1fr);">
                    <div class="es-grid-time-header"><?php _e('Time', 'ensemble'); ?></div>
                    <?php foreach ($timetable_data['rooms'] as $room): ?>
                        <div class="es-grid-room-header" data-room="<?php echo esc_attr($room['name']); ?>">
                            <?php echo esc_html($room['name']); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Grid Body -->
                <div class="es-grid-body" id="es-grid-body">
                    <?php for ($time = $start_minutes; $time < $end_minutes; $time += $interval):
                        $time_string = es_tt_minutes_to_time($time);
                        $is_hour = ($time % 60 === 0);
                    ?>
                        <div class="es-grid-row <?php echo $is_hour ? 'is-hour' : ''; ?>" 
                             data-time="<?php echo esc_attr($time_string); ?>"
                             style="grid-template-columns: 80px repeat(<?php echo $room_count; ?>, 1fr);">
                            <div class="es-grid-time">
                                <?php if ($is_hour): ?>
                                    <span><?php echo esc_html($time_string); ?></span>
                                <?php endif; ?>
                            </div>
                            <?php foreach ($timetable_data['rooms'] as $room): ?>
                                <div class="es-grid-cell" 
                                     data-room="<?php echo esc_attr($room['name']); ?>" 
                                     data-time="<?php echo esc_attr($time_string); ?>">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endfor; ?>
                    
                    <!-- Sessions -->
                    <?php 
                    $break_icons = array('coffee' => '☕', 'lunch' => '🍽️', 'networking' => '🤝', 'registration' => '📋', 'pause' => '⏸️', 'discussion' => '💬');
                    
                    foreach ($timetable_data['sessions'] as $index => $session): 
                        if (!isset($session_positions[$index])) continue;
                        $pos = $session_positions[$index];
                        $title = !empty($session['session_title']) ? $session['session_title'] : $session['artist_name'];
                        
                        $room_index = 0;
                        foreach ($timetable_data['rooms'] as $ri => $r) {
                            if ($r['name'] === $session['venue']) { $room_index = $ri; break; }
                        }
                        
                        $left = 'calc(80px + ' . $room_index . ' * (100% - 80px) / ' . $room_count . ' + 4px)';
                        $width = 'calc((100% - 80px) / ' . $room_count . ' - 8px)';
                    ?>
                        <div class="es-session-block" 
                             data-artist-id="<?php echo esc_attr($session['artist_id']); ?>"
                             data-duration="<?php echo esc_attr($session['duration']); ?>"
                             data-room="<?php echo esc_attr($session['venue']); ?>"
                             style="position: absolute; top: <?php echo $pos['top']; ?>px; height: <?php echo $pos['height']; ?>px; left: <?php echo $left; ?>; width: <?php echo $width; ?>; z-index: 10;">
                            <div class="es-session-time"><?php echo esc_html($session['time']); ?> - <?php echo esc_html($pos['end_time']); ?></div>
                            <div class="es-session-title"><?php echo esc_html($title); ?></div>
                            <div class="es-session-speaker">
                                <?php if ($session['artist_image']): ?>
                                    <img src="<?php echo esc_url($session['artist_image']); ?>" alt="">
                                <?php endif; ?>
                                <span><?php echo esc_html($session['artist_name']); ?></span>
                            </div>
                            <div class="es-session-actions">
                                <button type="button" class="es-edit-session" title="Edit"><span class="dashicons dashicons-edit"></span></button>
                                <button type="button" class="es-remove-session" title="Remove"><span class="dashicons dashicons-trash"></span></button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <!-- Breaks -->
                    <?php foreach ($timetable_data['breaks'] as $index => $break): 
                        if (!isset($break_positions[$index])) continue;
                        $pos = $break_positions[$index];
                        $icon = isset($break_icons[$break['icon'] ?? 'pause']) ? $break_icons[$break['icon'] ?? 'pause'] : '⏸️';
                    ?>
                        <div class="es-break-block" 
                             data-break-index="<?php echo $index; ?>"
                             style="position: absolute; top: <?php echo $pos['top']; ?>px; height: <?php echo $pos['height']; ?>px; left: 80px; right: 0; z-index: 5;">
                            <span class="es-break-icon"><?php echo $icon; ?></span>
                            <span class="es-break-title"><?php echo esc_html($break['title']); ?></span>
                            <span class="es-break-time"><?php echo esc_html($break['time']); ?></span>
                            <span class="es-break-duration"><?php echo intval($break['duration']); ?> min</span>
                            <button type="button" class="es-break-remove" title="Remove">×</button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Unassigned Speakers -->
        <div class="es-timetable-unassigned" id="es-unassigned-speakers">
            <h3>
                <span class="dashicons dashicons-groups"></span>
                <?php _e('Unassigned Speakers', 'ensemble'); ?>
                <span class="es-count">(<?php echo count($timetable_data['unassigned']); ?>)</span>
            </h3>
            <p class="es-hint"><?php _e('Drag speakers to the grid above, or drop sessions here to unassign.', 'ensemble'); ?></p>
            <div class="es-unassigned-list" id="es-unassigned-list">
                <?php if (empty($timetable_data['unassigned'])): ?>
                    <p class="es-no-unassigned"><?php _e('All speakers have been assigned.', 'ensemble'); ?></p>
                <?php else: ?>
                    <?php foreach ($timetable_data['unassigned'] as $speaker): ?>
                        <div class="es-speaker-card es-draggable" 
                             data-artist-id="<?php echo esc_attr($speaker['artist_id']); ?>"
                             data-duration="<?php echo esc_attr($speaker['duration'] ?? 60); ?>">
                            <?php if ($speaker['artist_image']): ?>
                                <img src="<?php echo esc_url($speaker['artist_image']); ?>" alt="" class="es-speaker-image">
                            <?php else: ?>
                                <div class="es-speaker-image es-no-image"><span class="dashicons dashicons-admin-users"></span></div>
                            <?php endif; ?>
                            <div class="es-speaker-info">
                                <div class="es-speaker-name"><?php echo esc_html($speaker['artist_name']); ?></div>
                                <?php if (!empty($speaker['artist_role'])): ?>
                                    <div class="es-speaker-role"><?php echo esc_html($speaker['artist_role']); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="es-speaker-actions">
                                <button type="button" class="es-edit-speaker" title="Edit"><span class="dashicons dashicons-edit"></span></button>
                                <button type="button" class="es-remove-speaker" title="Remove"><span class="dashicons dashicons-trash"></span></button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Timetable data for JS -->
    <script type="application/json" id="es-timetable-data"><?php echo wp_json_encode($timetable_data); ?></script>
    <?php endif; ?>
</div>

<!-- Session Edit Modal -->
<div id="es-session-modal" class="es-modal" style="display: none;">
    <div class="es-modal-overlay" onclick="jQuery(this).closest('.es-modal').hide();"></div>
    <div class="es-modal-content">
        <div class="es-modal-header">
            <h2><?php _e('Edit Session', 'ensemble'); ?></h2>
            <button type="button" class="es-modal-close" title="<?php esc_attr_e('Close', 'ensemble'); ?>" onclick="jQuery('.es-modal').hide(); return false;">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        <div class="es-modal-body">
            <input type="hidden" id="es-session-artist-id">
            
            <div class="es-form-row">
                <label><?php _e('Speaker', 'ensemble'); ?></label>
                <div id="es-session-speaker-display" class="es-speaker-display"></div>
            </div>
            
            <div class="es-form-row">
                <label for="es-session-title"><?php _e('Session Title (optional)', 'ensemble'); ?></label>
                <input type="text" id="es-session-title" placeholder="<?php esc_attr_e('e.g., Keynote: The Future of AI', 'ensemble'); ?>">
            </div>
            
            <div class="es-form-grid">
                <div class="es-form-row">
                    <label for="es-session-time"><?php _e('Start Time', 'ensemble'); ?></label>
                    <input type="time" id="es-session-time">
                </div>
                <div class="es-form-row">
                    <label for="es-session-duration"><?php _e('Duration (min)', 'ensemble'); ?></label>
                    <input type="number" id="es-session-duration" value="60" min="15" max="480" step="15">
                </div>
            </div>
            
            <div class="es-form-row">
                <label for="es-session-room"><?php _e('Room', 'ensemble'); ?></label>
                <select id="es-session-room"></select>
            </div>
        </div>
        <div class="es-modal-footer">
            <button type="button" class="es-modal-unassign button"><?php _e('Unassign', 'ensemble'); ?></button>
            <button type="button" class="es-modal-cancel button"><?php _e('Cancel', 'ensemble'); ?></button>
            <button type="button" class="es-modal-save button button-primary"><?php _e('Save', 'ensemble'); ?></button>
        </div>
    </div>
</div>

<!-- Add Speaker Modal -->
<div id="es-add-speaker-modal" class="es-modal" style="display: none;">
    <div class="es-modal-overlay" onclick="jQuery(this).closest('.es-modal').hide();"></div>
    <div class="es-modal-content">
        <div class="es-modal-header">
            <h2><?php _e('Add Speaker', 'ensemble'); ?></h2>
            <button type="button" class="es-modal-close" title="<?php esc_attr_e('Close', 'ensemble'); ?>" onclick="jQuery('.es-modal').hide(); return false;">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        <div class="es-modal-body">
            <div class="es-form-row">
                <input type="text" id="es-speaker-search" placeholder="<?php esc_attr_e('Search speakers...', 'ensemble'); ?>">
            </div>
            <div class="es-speaker-results" id="es-speaker-results"></div>
        </div>
    </div>
</div>

<!-- Break Modal -->
<div id="es-break-modal" class="es-modal" style="display: none;">
    <div class="es-modal-overlay" onclick="jQuery(this).closest('.es-modal').hide();"></div>
    <div class="es-modal-content">
        <div class="es-modal-header">
            <h2><?php _e('Add Break', 'ensemble'); ?></h2>
            <button type="button" class="es-modal-close" title="<?php esc_attr_e('Close', 'ensemble'); ?>" onclick="jQuery('.es-modal').hide(); return false;">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        <div class="es-modal-body">
            <input type="hidden" id="es-break-index" value="-1">
            
            <div class="es-form-row">
                <label for="es-break-type"><?php _e('Type', 'ensemble'); ?></label>
                <select id="es-break-type">
                    <option value="registration">📋 <?php _e('Registration', 'ensemble'); ?></option>
                    <option value="coffee">☕ <?php _e('Coffee Break', 'ensemble'); ?></option>
                    <option value="lunch">🍽️ <?php _e('Lunch', 'ensemble'); ?></option>
                    <option value="networking">🤝 <?php _e('Networking', 'ensemble'); ?></option>
                    <option value="discussion">💬 <?php _e('Discussion', 'ensemble'); ?></option>
                    <option value="pause">⏸️ <?php _e('Pause', 'ensemble'); ?></option>
                </select>
            </div>
            
            <div class="es-form-row">
                <label for="es-break-title-input"><?php _e('Title', 'ensemble'); ?></label>
                <input type="text" id="es-break-title-input" placeholder="<?php esc_attr_e('e.g., Coffee Break', 'ensemble'); ?>">
            </div>
            
            <div class="es-form-grid">
                <div class="es-form-row">
                    <label for="es-break-time"><?php _e('Time', 'ensemble'); ?></label>
                    <input type="time" id="es-break-time">
                </div>
                <div class="es-form-row">
                    <label for="es-break-duration-input"><?php _e('Duration (min)', 'ensemble'); ?></label>
                    <input type="number" id="es-break-duration-input" value="30" min="5" max="120" step="5">
                </div>
            </div>
        </div>
        <div class="es-modal-footer">
            <button type="button" class="es-break-delete button button-link-delete"><?php _e('Delete', 'ensemble'); ?></button>
            <button type="button" class="es-modal-cancel button"><?php _e('Cancel', 'ensemble'); ?></button>
            <button type="button" class="es-modal-save button button-primary"><?php _e('Add Break', 'ensemble'); ?></button>
        </div>
    </div>
</div>
