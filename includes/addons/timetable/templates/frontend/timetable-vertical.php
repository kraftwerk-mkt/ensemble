<?php
/**
 * Frontend Template: Festival Timetable - Vertical Layout
 * 
 * One complete table per day, side by side
 * Like Rock im Park / Wacken style
 *
 * @package Ensemble
 * @subpackage Addons/Timetable
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$extra_class = ! empty( $atts['class'] ) ? ' ' . esc_attr( $atts['class'] ) : '';

// Dimensions
$slot_height = intval( $atts['slot_height'] );
$total_hours = ceil( $total_minutes / 60 );

// Display options
$show = $display_options;

// Group events by day and location
$events_by_day_location = array();
foreach ( $data['events'] as $event ) {
    if ( ! $event['scheduled'] ) continue;
    $day = $event['date'];
    $loc = $event['location_id'];
    if ( ! isset( $events_by_day_location[ $day ] ) ) {
        $events_by_day_location[ $day ] = array();
    }
    if ( ! isset( $events_by_day_location[ $day ][ $loc ] ) ) {
        $events_by_day_location[ $day ][ $loc ] = array();
    }
    $events_by_day_location[ $day ][ $loc ][] = $event;
}

// Get days
$days = $data['days'];
$day_count = count( $days );
$stage_count = count( $data['locations'] );

// Check if "All Days" should be shown
$max_stages = isset( $show['max_stages_all_days'] ) ? intval( $show['max_stages_all_days'] ) : 4;
$show_all_days = ( $max_stages === 0 || $stage_count <= $max_stages );
$first_day = ! empty( $days[0]['date'] ) ? $days[0]['date'] : '';
?>

<div class="es-timetable-frontend es-timetable-vertical<?php echo $extra_class; ?>" 
     data-time-start="<?php echo esc_attr( $time_start ); ?>"
     data-time-end="<?php echo esc_attr( $time_end ); ?>"
     data-layout="vertical">

    <?php if ( $show['show_filter'] && ! empty( $days ) ) : ?>
    <!-- Day Filter - Centered -->
    <div class="es-timetable-filters">
        <div class="es-filters-inner">
            <?php if ( $show_all_days ) : ?>
            <button type="button" class="es-day-filter active" data-day="all">
                <?php esc_html_e( 'All Days', 'flavor' ); ?>
            </button>
            <?php endif; ?>
            <?php foreach ( $days as $index => $day ) : 
                $is_first = ( $index === 0 );
                $is_active = ( ! $show_all_days && $is_first );
            ?>
                <button type="button" class="es-day-filter<?php echo $is_active ? ' active' : ''; ?>" data-day="<?php echo esc_attr( $day['date'] ); ?>">
                    <span class="es-filter-day"><?php echo esc_html( $day['day_name'] ); ?></span>
                    <span class="es-filter-date"><?php echo esc_html( $day['label'] ); ?></span>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Tables Container - One table per day -->
    <div class="es-timetable-days-wrapper">
        
        <?php foreach ( $days as $index => $day_data ) : 
            $is_first = ( $index === 0 );
            // Hide non-first days if "All Days" is disabled
            $hidden_class = ( ! $show_all_days && ! $is_first ) ? ' es-hidden' : ''; 
            $hidden_style = ( ! $show_all_days && ! $is_first ) ? ' style="display:none;"' : '';
            $day_date = $day_data['date'];
            $day_events = isset( $events_by_day_location[ $day_date ] ) ? $events_by_day_location[ $day_date ] : array();
        ?>
        <div class="es-day-table<?php echo $hidden_class; ?>" data-day="<?php echo esc_attr( $day_date ); ?>"<?php echo $hidden_style; ?>>
            
            <!-- Day Title -->
            <div class="es-day-title">
                <span class="es-day-name"><?php echo esc_html( $day_data['day_name'] ); ?></span>
                <span class="es-day-date"><?php echo esc_html( $day_data['label'] ); ?></span>
            </div>

            <!-- Day Grid -->
            <div class="es-day-grid">
                
                <!-- Time Column -->
                <div class="es-time-column">
                    <?php 
                    $current = $time_start;
                    while ( $current < $time_end ) :
                        $hour = floor( $current / 60 ) % 24;
                    ?>
                        <div class="es-time-slot" style="height: <?php echo $slot_height; ?>px;">
                            <span><?php echo sprintf( '%02d:00', $hour ); ?></span>
                        </div>
                    <?php 
                        $current += 60;
                    endwhile; 
                    ?>
                </div>

                <!-- Stage Columns -->
                <div class="es-stages-row">
                    <?php foreach ( $data['locations'] as $location ) : 
                        $stage_color = $location['color'] ?? 'var(--ensemble-primary, #e94560)';
                        $stage_events = isset( $day_events[ $location['id'] ] ) ? $day_events[ $location['id'] ] : array();
                    ?>
                    <div class="es-stage-col">
                        
                        <!-- Stage Header -->
                        <div class="es-stage-header" style="--stage-color: <?php echo esc_attr( $stage_color ); ?>">
                            <span class="es-stage-name"><?php echo esc_html( $location['name'] ); ?></span>
                        </div>

                        <!-- Stage Events Area -->
                        <div class="es-stage-events" style="height: <?php echo $total_hours * $slot_height; ?>px;">
                            
                            <!-- Hour Grid Lines -->
                            <?php for ( $i = 0; $i < $total_hours; $i++ ) : ?>
                                <div class="es-hour-line" style="top: <?php echo $i * $slot_height; ?>px;"></div>
                            <?php endfor; ?>

                            <!-- Events -->
                            <?php foreach ( $stage_events as $event ) : 
                                $event_start = $this->time_to_minutes( $event['start_time'] );
                                if ( $event_start < $time_start ) {
                                    $event_start += 24 * 60;
                                }
                                $offset = $event_start - $time_start;
                                $duration = $event['duration'] ?? 60;
                                
                                $top = ( $offset / 60 ) * $slot_height;
                                $height = ( $duration / 60 ) * $slot_height;
                                $height = max( $height, 50 );
                                
                                $event_link = get_permalink( $event['id'] );
                                
                                // Get genre
                                $genre = '';
                                if ( $show['show_genre'] ) {
                                    $terms = get_the_terms( $event['id'], 'event_category' );
                                    if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
                                        $genre = $terms[0]->name;
                                    }
                                }

                                // Image handling
                                $has_image = $show['show_image'] && ! empty( $event['image'] ) && $show['image_position'] !== 'none';
                                $image_class = 'es-image-' . esc_attr( $show['image_position'] );
                                
                                // Background image style
                                $bg_style = '';
                                if ( $has_image && $show['image_position'] === 'background' ) {
                                    $bg_style = "background-image: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.7)), url('" . esc_url( $event['image'] ) . "');";
                                }

                                // Event color
                                $event_style = "--event-color: {$stage_color};";
                            ?>
                            <a href="<?php echo esc_url( $event_link ); ?>" 
                               class="es-event <?php echo $image_class; ?>"
                               style="top: <?php echo $top; ?>px; height: <?php echo $height; ?>px; <?php echo $event_style; ?> <?php echo $bg_style; ?>">
                                
                                <?php if ( $has_image && in_array( $show['image_position'], array( 'left', 'top' ) ) ) : ?>
                                    <div class="es-event-image">
                                        <img src="<?php echo esc_url( $event['image'] ); ?>" alt="" loading="lazy">
                                    </div>
                                <?php endif; ?>
                                
                                <div class="es-event-info">
                                    <?php if ( $show['show_time'] ) : ?>
                                        <span class="es-event-time"><?php echo esc_html( $event['start_time'] ); ?></span>
                                    <?php endif; ?>
                                    
                                    <?php if ( $show['show_title'] ) : ?>
                                        <span class="es-event-title"><?php echo esc_html( $event['title'] ); ?></span>
                                    <?php endif; ?>
                                    
                                    <?php if ( $show['show_artist'] && ! empty( $event['artist'] ) ) : ?>
                                        <span class="es-event-artist"><?php echo esc_html( $event['artist'] ); ?></span>
                                    <?php endif; ?>
                                    
                                    <?php if ( $show['show_genre'] && ! empty( $genre ) ) : ?>
                                        <span class="es-event-genre"><?php echo esc_html( $genre ); ?></span>
                                    <?php endif; ?>
                                </div>
                            </a>
                            <?php endforeach; ?>

                        </div><!-- .es-stage-events -->

                    </div><!-- .es-stage-col -->
                    <?php endforeach; ?>
                </div><!-- .es-stages-row -->

            </div><!-- .es-day-grid -->

        </div><!-- .es-day-table -->
        <?php endforeach; ?>

    </div><!-- .es-timetable-days-wrapper -->

    <?php if ( empty( $data['events'] ) ) : ?>
    <div class="es-timetable-empty">
        <p><?php esc_html_e( 'No events in this period.', 'flavor' ); ?></p>
    </div>
    <?php endif; ?>

</div>
