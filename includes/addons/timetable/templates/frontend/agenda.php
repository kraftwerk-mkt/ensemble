<?php
/**
 * Frontend Template: Conference Agenda
 * 
 * Available variables:
 * - $atts: Shortcode attributes
 * - $event_id: Event ID
 * - $entries: Agenda entries
 * - $speaker_map: Speaker lookup
 * - $room_map: Room lookup
 *
 * @package Ensemble
 * @subpackage Addons/Timetable
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$show_room = $atts['show_room'] === 'true';
$show_speaker = $atts['show_speaker'] === 'true';
$extra_class = ! empty( $atts['class'] ) ? ' ' . esc_attr( $atts['class'] ) : '';
?>

<div class="es-agenda-frontend<?php echo $extra_class; ?>">

    <?php if ( empty( $entries ) ) : ?>
        <div class="es-agenda-empty">
            <p><?php esc_html_e( 'No agenda entries found.', 'flavor' ); ?></p>
        </div>
    <?php else : ?>

        <div class="es-agenda-list">
            <?php foreach ( $entries as $entry ) : 
                $is_break = ! empty( $entry['is_break'] );
                $speaker = isset( $speaker_map[ $entry['speaker_id'] ] ) ? $speaker_map[ $entry['speaker_id'] ] : null;
                $room = isset( $room_map[ $entry['room_id'] ] ) ? $room_map[ $entry['room_id'] ] : null;
                
                // Speaker link
                $speaker_link = $speaker ? get_permalink( $speaker['id'] ) : '';
            ?>
            <div class="es-agenda-entry<?php echo $is_break ? ' is-break' : ''; ?>">
                
                <!-- Time Column -->
                <div class="es-entry-time">
                    <span class="es-time-start"><?php echo esc_html( $entry['start_time'] ?? '--:--' ); ?></span>
                    <span class="es-time-divider">–</span>
                    <span class="es-time-end"><?php echo esc_html( $entry['end_time'] ?? '--:--' ); ?></span>
                </div>

                <!-- Content Column -->
                <div class="es-entry-main">
                    
                    <?php if ( $is_break ) : ?>
                        <!-- Break Entry -->
                        <div class="es-entry-break">
                            <span class="es-break-icon">☕</span>
                            <span class="es-break-title"><?php echo esc_html( $entry['title'] ?: __( 'Break', 'flavor' ) ); ?></span>
                        </div>
                    <?php else : ?>
                        <!-- Regular Entry -->
                        <?php if ( ! empty( $entry['title'] ) ) : ?>
                            <h4 class="es-entry-title"><?php echo esc_html( $entry['title'] ); ?></h4>
                        <?php endif; ?>

                        <?php if ( $show_speaker && $speaker ) : ?>
                            <div class="es-entry-speaker">
                                <?php if ( ! empty( $speaker['image'] ) ) : ?>
                                    <img src="<?php echo esc_url( $speaker['image'] ); ?>" alt="" class="es-speaker-avatar">
                                <?php endif; ?>
                                <?php if ( $speaker_link ) : ?>
                                    <a href="<?php echo esc_url( $speaker_link ); ?>" class="es-speaker-name">
                                        <?php echo esc_html( $speaker['name'] ); ?>
                                    </a>
                                <?php else : ?>
                                    <span class="es-speaker-name"><?php echo esc_html( $speaker['name'] ); ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ( $show_room && $room ) : ?>
                            <div class="es-entry-room">
                                <span class="es-room-icon">📍</span>
                                <span class="es-room-name"><?php echo esc_html( $room['name'] ); ?></span>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                </div>

            </div>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>

</div>
