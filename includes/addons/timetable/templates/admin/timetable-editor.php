<?php
/**
 * Timetable Editor Template
 * 
 * Dual-Mode Interface:
 * - Festival Timeline: Multiple events across stages/days
 * - Conference Agenda: Single event with speakers in rooms
 * - Settings: Configure defaults
 *
 * @package Ensemble
 * @subpackage Addons/Timetable
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$addon = ES_Timetable_Addon::get_instance();
$events = $addon->get_events_for_select();
$locations = $addon->get_all_locations();
$settings = $addon->get_settings();
?>

<div class="es-timetable-wrap">
    <h1>
        <span class="dashicons dashicons-schedule"></span>
        <?php esc_html_e( 'Timetable Editor', 'flavor' ); ?>
    </h1>

    <div class="es-timetable-container">
        
        <!-- Mode Switcher Tabs -->
        <div class="es-tabs">
            <button type="button" class="es-tab active" data-mode="festival">
                <span class="dashicons dashicons-calendar-alt"></span>
                <?php esc_html_e( 'Festival Timeline', 'flavor' ); ?>
            </button>
            <button type="button" class="es-tab" data-mode="conference">
                <span class="dashicons dashicons-groups"></span>
                <?php esc_html_e( 'Conference Agenda', 'flavor' ); ?>
            </button>
            <button type="button" class="es-tab" data-mode="settings">
                <span class="dashicons dashicons-admin-settings"></span>
                <?php esc_html_e( 'Settings', 'flavor' ); ?>
            </button>
        </div>

        <!-- ========================================
             MULTI-EVENT MODE (FESTIVAL TIMELINE)
             ======================================== -->
        <div class="es-timetable-mode es-mode-festival active" id="es-mode-festival">
            
            <!-- Toolbar -->
            <div class="es-toolbar">
                <div class="es-toolbar-left">
                    <label><?php esc_html_e( 'Date Range:', 'flavor' ); ?></label>
                    <input type="date" id="es-date-from" value="<?php echo esc_attr( date( 'Y-m-d' ) ); ?>">
                    <span>–</span>
                    <input type="date" id="es-date-to" value="<?php echo esc_attr( date( 'Y-m-d', strtotime( '+7 days' ) ) ); ?>">
                    <button type="button" class="es-btn es-btn-primary" id="es-load-timeline">
                        <span class="dashicons dashicons-update"></span>
                        <?php esc_html_e( 'Load', 'flavor' ); ?>
                    </button>
                </div>
                <div class="es-toolbar-right">
                    <label><?php esc_html_e( 'Time of Day:', 'flavor' ); ?></label>
                    <select id="es-time-start">
                        <?php for ( $h = 0; $h <= 23; $h++ ) : ?>
                            <option value="<?php echo $h; ?>" <?php selected( $h, 10 ); ?>>
                                <?php echo sprintf( '%02d:00', $h ); ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                    <span>–</span>
                    <select id="es-time-end">
                        <?php for ( $h = 1; $h <= 28; $h++ ) : ?>
                            <option value="<?php echo $h; ?>" <?php selected( $h, 26 ); ?>>
                                <?php echo sprintf( '%02d:00', $h % 24 ); ?>
                                <?php echo $h >= 24 ? '+1' : ''; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>

            <!-- Day Tabs -->
            <div class="es-day-tabs" id="es-day-tabs">
                <button type="button" class="es-day-tab active" data-day="all">
                    <?php esc_html_e( 'All Days', 'flavor' ); ?>
                </button>
                <!-- Days will be populated via JS -->
            </div>

            <!-- Main Timeline Layout -->
            <div class="es-timeline-layout">
                
                <!-- Sidebar: Unscheduled Events -->
                <div class="es-timeline-sidebar">
                    <div class="es-sidebar-header">
                        <h3>
                            <span class="dashicons dashicons-clock"></span>
                            <?php esc_html_e( 'Not Scheduled', 'flavor' ); ?>
                        </h3>
                        <span class="es-badge" id="es-unscheduled-count">0</span>
                    </div>
                    <div class="es-sidebar-search">
                        <input type="text" id="es-event-search" placeholder="<?php esc_attr_e( 'Search events...', 'flavor' ); ?>">
                    </div>
                    <div class="es-unscheduled-list" id="es-unscheduled-list">
                        <!-- Unscheduled events via JS -->
                    </div>
                </div>

                <!-- Timeline Grid -->
                <div class="es-timeline-wrapper">
                    <div class="es-timeline-header" id="es-timeline-header">
                        <!-- Time slots via JS -->
                    </div>
                    <div class="es-timeline-grid" id="es-timeline-grid">
                        <!-- Stage rows with events via JS -->
                    </div>
                </div>

            </div>

            <!-- Loading Overlay -->
            <div class="es-loading-overlay" id="es-festival-loading">
                <span class="spinner is-active"></span>
                <span><?php esc_html_e( 'Loading timeline...', 'flavor' ); ?></span>
            </div>

        </div>

        <!-- ========================================
             SINGLE-EVENT MODE (CONFERENCE AGENDA)
             ======================================== -->
        <div class="es-timetable-mode es-mode-conference" id="es-mode-conference">
            
            <!-- Event Selector -->
            <div class="es-toolbar">
                <div class="es-toolbar-left">
                    <label for="es-event-select"><?php esc_html_e( 'Select Event:', 'flavor' ); ?></label>
                    <select id="es-event-select" class="es-select-lg">
                        <option value=""><?php esc_html_e( '– Select Event –', 'flavor' ); ?></option>
                        <?php foreach ( $events as $event ) : ?>
                            <option value="<?php echo esc_attr( $event->ID ); ?>">
                                <?php echo esc_html( $event->post_title ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="es-toolbar-right">
                    <button type="button" class="es-btn es-btn-success" id="es-add-entry" disabled>
                        <span class="dashicons dashicons-plus-alt2"></span>
                        <?php esc_html_e( 'Add Entry', 'flavor' ); ?>
                    </button>
                    <button type="button" class="es-btn es-btn-secondary" id="es-add-break" disabled>
                        <span class="dashicons dashicons-coffee"></span>
                        <?php esc_html_e( 'Break', 'flavor' ); ?>
                    </button>
                </div>
            </div>

            <!-- Conference Grid -->
            <div class="es-conference-grid" id="es-conference-grid">
                <div class="es-empty-state">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <p><?php esc_html_e( 'Select an event to edit the agenda.', 'flavor' ); ?></p>
                </div>
            </div>

            <!-- Loading -->
            <div class="es-loading-overlay" id="es-conference-loading">
                <span class="spinner is-active"></span>
            </div>

        </div>

        <!-- ========================================
             SETTINGS MODE
             ======================================== -->
        <div class="es-timetable-mode es-mode-settings" id="es-mode-settings">
            <?php 
            // Render settings form
            $addon->render_settings();
            ?>
        </div>

    </div><!-- .es-timetable-container -->

</div><!-- .es-timetable-wrap -->

<!-- ========================================
     MODALS
     ======================================== -->

<!-- Entry Edit Modal (Conference Mode) -->
<div class="es-modal" id="es-entry-modal">
    <div class="es-modal-content">
        <div class="es-modal-header">
            <h3 id="es-modal-title"><?php esc_html_e( 'Edit Entry', 'flavor' ); ?></h3>
            <button type="button" class="es-modal-close" data-dismiss="modal">&times;</button>
        </div>
        <div class="es-modal-body">
            <input type="hidden" id="es-entry-id" value="">
            <input type="hidden" id="es-entry-is-break" value="0">
            
            <div class="es-form-row" id="es-speaker-row">
                <label for="es-entry-speaker"><?php esc_html_e( 'Speaker', 'flavor' ); ?></label>
                <select id="es-entry-speaker">
                    <option value=""><?php esc_html_e( '– Select –', 'flavor' ); ?></option>
                </select>
            </div>

            <div class="es-form-row" id="es-room-row">
                <label for="es-entry-room"><?php esc_html_e( 'Room', 'flavor' ); ?></label>
                <select id="es-entry-room">
                    <option value=""><?php esc_html_e( '– Select –', 'flavor' ); ?></option>
                    <?php foreach ( $locations as $loc ) : ?>
                        <option value="<?php echo esc_attr( $loc['id'] ); ?>">
                            <?php echo esc_html( $loc['name'] ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="es-form-row">
                <label for="es-entry-title"><?php esc_html_e( 'Title / Description', 'flavor' ); ?></label>
                <input type="text" id="es-entry-title" placeholder="<?php esc_attr_e( 'e.g. Keynote, Coffee Break...', 'flavor' ); ?>">
            </div>

            <div class="es-form-row es-form-row-inline">
                <div>
                    <label for="es-entry-start"><?php esc_html_e( 'Start', 'flavor' ); ?></label>
                    <input type="time" id="es-entry-start" value="09:00">
                </div>
                <div>
                    <label for="es-entry-end"><?php esc_html_e( 'End', 'flavor' ); ?></label>
                    <input type="time" id="es-entry-end" value="10:00">
                </div>
            </div>
        </div>
        <div class="es-modal-footer">
            <button type="button" class="es-btn es-btn-danger es-btn-delete" id="es-delete-entry" style="display:none;">
                <span class="dashicons dashicons-trash"></span>
                <?php esc_html_e( 'Delete', 'flavor' ); ?>
            </button>
            <button type="button" class="es-btn es-btn-secondary" data-dismiss="modal">
                <?php esc_html_e( 'Cancel', 'flavor' ); ?>
            </button>
            <button type="button" class="es-btn es-btn-primary" id="es-save-entry">
                <span class="dashicons dashicons-saved"></span>
                <?php esc_html_e( 'Save', 'flavor' ); ?>
            </button>
        </div>
    </div>
</div>

<!-- Event Quick Edit Modal (Festival Mode) -->
<div class="es-modal" id="es-event-modal">
    <div class="es-modal-content es-modal-sm">
        <div class="es-modal-header">
            <h3><?php esc_html_e( 'Edit Event', 'flavor' ); ?></h3>
            <button type="button" class="es-modal-close" data-dismiss="modal">&times;</button>
        </div>
        <div class="es-modal-body">
            <div class="es-event-preview" id="es-event-preview">
                <!-- Event preview via JS -->
            </div>
            <input type="hidden" id="es-edit-event-id" value="">
            
            <div class="es-form-row">
                <label for="es-edit-location"><?php esc_html_e( 'Stage / Location', 'flavor' ); ?></label>
                <select id="es-edit-location">
                    <?php foreach ( $locations as $loc ) : ?>
                        <option value="<?php echo esc_attr( $loc['id'] ); ?>">
                            <?php echo esc_html( $loc['name'] ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="es-form-row">
                <label for="es-edit-date"><?php esc_html_e( 'Date', 'flavor' ); ?></label>
                <input type="date" id="es-edit-date">
            </div>

            <div class="es-form-row es-form-row-inline">
                <div>
                    <label for="es-edit-start"><?php esc_html_e( 'Start', 'flavor' ); ?></label>
                    <input type="time" id="es-edit-start">
                </div>
                <div>
                    <label for="es-edit-end"><?php esc_html_e( 'End', 'flavor' ); ?></label>
                    <input type="time" id="es-edit-end">
                </div>
            </div>
        </div>
        <div class="es-modal-footer">
            <a href="#" class="es-btn es-btn-link" id="es-edit-full" target="_blank">
                <span class="dashicons dashicons-edit"></span>
                <?php esc_html_e( 'Full Edit', 'flavor' ); ?>
            </a>
            <button type="button" class="es-btn es-btn-secondary" data-dismiss="modal">
                <?php esc_html_e( 'Cancel', 'flavor' ); ?>
            </button>
            <button type="button" class="es-btn es-btn-primary" id="es-save-event-schedule">
                <?php esc_html_e( 'Save', 'flavor' ); ?>
            </button>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div class="es-toast" id="es-toast">
    <span class="es-toast-message"></span>
</div>
