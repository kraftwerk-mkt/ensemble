<?php
/**
 * Frontend Template: Festival Timetable - Horizontal Layout
 * 
 * Days side by side, Time as rows (left)
 * Like Rock am Ring / Wacken style
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
$events_by_day = array();
foreach ( $data['events'] as $event ) {
    if ( ! $event['scheduled'] ) continue;
    $day = $event['date'];
    if ( ! isset( $events_by_day[ $day ] ) ) {
        $events_by_day[ $day ] = array();
    }
    $events_by_day[ $day ][] = $event;
}

// Get unique days from data
$days = $data['days'];
$day_count = count( $days );
$stage_count = count( $data['locations'] );

// Check if "All Days" should be shown
$max_stages = isset( $show['max_stages_all_days'] ) ? intval( $show['max_stages_all_days'] ) : 4;
$show_all_days = ( $max_stages === 0 || $stage_count <= $max_stages );
$first_day = ! empty( $days[0]['date'] ) ? $days[0]['date'] : '';
?>

<div class="es-timetable-frontend es-timetable-horizontal<?php echo $extra_class; ?>" 
     data-time-start="<?php echo esc_attr( $time_start ); ?>"
     data-time-end="<?php echo esc_attr( $time_end ); ?>"
     data-layout="horizontal">

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

    <!-- Timetable Grid -->
    <div class="es-timetable-grid">
        
        <!-- Time Column (Left) -->
        <div class="es-time-column">
            <div class="es-time-header"></div>
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

        <!-- Day Columns -->
        <div class="es-days-container">
            <?php foreach ( $days as $index => $day_data ) : 
                $is_first = ( $index === 0 );
                $hidden_class = ( ! $show_all_days && ! $is_first ) ? ' es-hidden' : ''; 
                $hidden_style = ( ! $show_all_days && ! $is_first ) ? ' style="display:none;"' : '';
                $day_date = $day_data['date'];
                $day_events = isset( $events_by_day[ $day_date ] ) ? $events_by_day[ $day_date ] : array();
                
                // Group by location for this day
                $events_by_location = array();
                foreach ( $day_events as $ev ) {
                    $loc_id = $ev['location_id'];
                    if ( ! isset( $events_by_location[ $loc_id ] ) ) {
                        $events_by_location[ $loc_id ] = array();
                    }
                    $events_by_location[ $loc_id ][] = $ev;
                }
            ?>
            <div class="es-day-column<?php echo $hidden_class; ?>" data-day="<?php echo esc_attr( $day_date ); ?>"<?php echo $hidden_style; ?>>
                
                <!-- Day Header -->
                <div class="es-day-header">
                    <span class="es-day-name"><?php echo esc_html( $day_data['day_name'] ); ?></span>
                    <span class="es-day-date"><?php echo esc_html( $day_data['label'] ); ?></span>
                </div>

                <!-- Day Content - Location Lanes -->
                <div class="es-day-content" style="height: <?php echo $total_hours * $slot_height; ?>px;">
                    
                    <!-- Hour Grid Lines -->
                    <?php for ( $i = 0; $i < $total_hours; $i++ ) : ?>
                        <div class="es-hour-line" style="top: <?php echo $i * $slot_height; ?>px;"></div>
                    <?php endfor; ?>

                    <!-- Location Lanes -->
                    <?php foreach ( $data['locations'] as $index => $location ) : 
                        $stage_color = $location['color'] ?? '';
                        $loc_events = isset( $events_by_location[ $location['id'] ] ) ? $events_by_location[ $location['id'] ] : array();
                        $lane_count = count( $data['locations'] );
                        $lane_width = 100 / $lane_count;
                        $lane_left = $index * $lane_width;
                    ?>
                    <div class="es-location-lane" 
                         data-location="<?php echo esc_attr( $location['id'] ); ?>"
                         style="left: <?php echo $lane_left; ?>%; width: <?php echo $lane_width; ?>%;">
                        
                        <?php foreach ( $loc_events as $event ) : 
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

                            // Event color - use stage color or custom
                            $event_style = '';
                            if ( ! empty( $stage_color ) ) {
                                $event_style = "--event-color: {$stage_color};";
                            }
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
                    </div>
                    <?php endforeach; ?>

                </div><!-- .es-day-content -->

            </div><!-- .es-day-column -->
            <?php endforeach; ?>
        </div><!-- .es-days-container -->

    </div><!-- .es-timetable-grid -->

    <!-- Location Legend -->
    <?php if ( ! empty( $data['locations'] ) ) : ?>
    <div class="es-location-legend">
        <?php foreach ( $data['locations'] as $location ) : 
            $color = $location['color'] ?? 'var(--ensemble-primary, #e94560)';
        ?>
            <div class="es-legend-item">
                <span class="es-legend-color" style="background: <?php echo esc_attr( $color ); ?>"></span>
                <span class="es-legend-name"><?php echo esc_html( $location['name'] ); ?></span>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ( empty( $data['events'] ) ) : ?>
    <div class="es-timetable-empty">
        <p><?php esc_html_e( 'No events in this period.', 'flavor' ); ?></p>
    </div>
    <?php endif; ?>

</div>
