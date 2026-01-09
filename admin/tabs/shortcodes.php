<?php
/**
 * Shortcodes Tab
 * 
 * Shows all available shortcodes with examples and copy functionality
 * 
 * @package Ensemble
 * @since 2.9.3
 */

if (!defined('ABSPATH')) exit;
?>

        <div class="es-shortcodes-section">
            
            <div class="es-section-intro">
                <h2><?php _e('Available Shortcodes', 'ensemble'); ?></h2>
                <p class="es-description">
                    <?php _e('Copy and paste these shortcodes into any page, post, or widget area to display your events, artists, and locations.', 'ensemble'); ?>
                </p>
            </div>
            
            <div class="es-shortcode-grid">
                
                <!-- Calendar Shortcode -->
                <div class="es-shortcode-card">
                    <div class="es-card-header">
                        <span class="es-card-icon">
                            <span class="dashicons dashicons-calendar-alt"></span>
                        </span>
                        <h3><?php _e('Calendar View', 'ensemble'); ?></h3>
                    </div>
                    
                    <div class="es-card-body">
                        <p class="es-card-description">
                            <?php _e('Display events in an interactive calendar with month, week, and day views.', 'ensemble'); ?>
                        </p>
                        
                        <div class="es-shortcode-box">
                            <code id="shortcode-calendar">[ensemble_calendar]</code>
                            <button class="es-copy-btn" data-target="shortcode-calendar">
                                <span class="dashicons dashicons-admin-page"></span>
                            </button>
                        </div>
                        
                        <div class="es-parameters">
                            <h4><?php _e('Parameters:', 'ensemble'); ?></h4>
                            <ul>
                                <li>
                                    <code>view</code>
                                    <span class="es-param-type">string</span>
                                    <span class="es-param-desc"><?php _e('Default view (month, week, day)', 'ensemble'); ?></span>
                                    <span class="es-param-default">Default: month</span>
                                </li>
                                <li>
                                    <code>category</code>
                                    <span class="es-param-type">int</span>
                                    <span class="es-param-desc"><?php _e('Filter by category ID', 'ensemble'); ?></span>
                                    <span class="es-param-default">Default: all</span>
                                </li>
                                <li>
                                    <code>height</code>
                                    <span class="es-param-type">int</span>
                                    <span class="es-param-desc"><?php _e('Calendar height in pixels', 'ensemble'); ?></span>
                                    <span class="es-param-default">Default: 600</span>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="es-examples">
                            <h4><?php _e('Examples:', 'ensemble'); ?></h4>
                            <div class="es-example-code">
                                <code>[ensemble_calendar view="week"]</code>
                                <button class="es-copy-btn-small" onclick="copyToClipboard('[ensemble_calendar view=&quot;week&quot;]')">
                                    <span class="dashicons dashicons-admin-page"></span>
                                </button>
                            </div>
                            <div class="es-example-code">
                                <code>[ensemble_calendar category="5" height="800"]</code>
                                <button class="es-copy-btn-small" onclick="copyToClipboard('[ensemble_calendar category=&quot;5&quot; height=&quot;800&quot;]')">
                                    <span class="dashicons dashicons-admin-page"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                   
                </div>
                
                <!-- Events List Shortcode -->
                <div class="es-shortcode-card">
                    <div class="es-card-header">
                        <span class="es-card-icon">
                            <span class="dashicons dashicons-list-view"></span>
                        </span>
                        <h3><?php _e('Events List', 'ensemble'); ?></h3>
                        <span class="es-badge es-badge-new">v2.8</span>
                    </div>
                    
                    <div class="es-card-body">
                        <p class="es-card-description">
                            <?php _e('Display events in various layouts including grid, slider, and fullscreen hero.', 'ensemble'); ?>
                        </p>
                        
                        <div class="es-shortcode-box">
                            <code id="shortcode-events">[ensemble_events]</code>
                            <button class="es-copy-btn" data-target="shortcode-events">
                                <span class="dashicons dashicons-admin-page"></span>
                            </button>
                        </div>
                        
                        <div class="es-parameters">
                            <h4><?php _e('Layout Options:', 'ensemble'); ?></h4>
                            <ul class="es-layout-options">
                                <li>
                                    <code>layout="grid"</code>
                                    <span class="es-param-desc"><?php _e('Standard responsive grid', 'ensemble'); ?></span>
                                </li>
                                <li>
                                    <code>layout="slider"</code>
                                    <span class="es-param-desc"><?php _e('Horizontal carousel with arrows', 'ensemble'); ?></span>
                                </li>
                                <li>
                                    <code>layout="hero"</code>
                                    <span class="es-param-desc"><?php _e('Fullscreen hero slider with overlay', 'ensemble'); ?></span>
                                </li>
                                <li>
                                    <code>layout="list"</code>
                                    <span class="es-param-desc"><?php _e('Compact list view', 'ensemble'); ?></span>
                                </li>
                            </ul>
                            
                            <h4><?php _e('General Parameters:', 'ensemble'); ?></h4>
                            <ul>
                                <li>
                                    <code>limit</code>
                                    <span class="es-param-type">int</span>
                                    <span class="es-param-desc"><?php _e('Number of events to show', 'ensemble'); ?></span>
                                    <span class="es-param-default">Default: 12</span>
                                </li>
                                <li>
                                    <code>columns</code>
                                    <span class="es-param-type">int</span>
                                    <span class="es-param-desc"><?php _e('Columns for grid / slides visible', 'ensemble'); ?></span>
                                    <span class="es-param-default">Default: 3</span>
                                </li>
                                <li>
                                    <code>show</code>
                                    <span class="es-param-type">string</span>
                                    <span class="es-param-desc"><?php _e('Filter: upcoming, past, all', 'ensemble'); ?></span>
                                    <span class="es-param-default">Default: upcoming</span>
                                </li>
                                <li>
                                    <code>category</code>
                                    <span class="es-param-type">string</span>
                                    <span class="es-param-desc"><?php _e('Filter by category slug', 'ensemble'); ?></span>
                                    <span class="es-param-default">Default: all</span>
                                </li>
                            </ul>
                            
                            <h4><?php _e('Slider Options:', 'ensemble'); ?></h4>
                            <ul>
                                <li>
                                    <code>autoplay</code>
                                    <span class="es-param-type">bool</span>
                                    <span class="es-param-desc"><?php _e('Auto-advance slides', 'ensemble'); ?></span>
                                    <span class="es-param-default">Default: false (true for hero)</span>
                                </li>
                                <li>
                                    <code>autoplay_speed</code>
                                    <span class="es-param-type">int</span>
                                    <span class="es-param-desc"><?php _e('Interval in milliseconds', 'ensemble'); ?></span>
                                    <span class="es-param-default">Default: 5000</span>
                                </li>
                                <li>
                                    <code>loop</code>
                                    <span class="es-param-type">bool</span>
                                    <span class="es-param-desc"><?php _e('Endless loop', 'ensemble'); ?></span>
                                    <span class="es-param-default">Default: false (true for hero)</span>
                                </li>
                                <li>
                                    <code>dots</code>
                                    <span class="es-param-type">bool</span>
                                    <span class="es-param-desc"><?php _e('Show dot navigation', 'ensemble'); ?></span>
                                    <span class="es-param-default">Default: true</span>
                                </li>
                                <li>
                                    <code>arrows</code>
                                    <span class="es-param-type">bool</span>
                                    <span class="es-param-desc"><?php _e('Show arrow navigation', 'ensemble'); ?></span>
                                    <span class="es-param-default">Default: true</span>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="es-examples">
                            <h4><?php _e('Examples:', 'ensemble'); ?></h4>
                            <div class="es-example-code">
                                <code>[ensemble_events layout="grid" columns="3" limit="12"]</code>
                                <button class="es-copy-btn-small" onclick="copyToClipboard('[ensemble_events layout=&quot;grid&quot; columns=&quot;3&quot; limit=&quot;12&quot;]')">
                                    <span class="dashicons dashicons-admin-page"></span>
                                </button>
                            </div>
                            <div class="es-example-code">
                                <code>[ensemble_events layout="hero" limit="5" featured="1" autoplay="true"]</code>
                                <button class="es-copy-btn-small" onclick="copyToClipboard('[ensemble_events layout=&quot;hero&quot; limit=&quot;5&quot; featured=&quot;1&quot; autoplay=&quot;true&quot;]')">
                                    <span class="dashicons dashicons-admin-page"></span>
                                </button>
                            </div>
                            <div class="es-example-code">
                                <code>[ensemble_events layout="slider" columns="4" arrows="true" dots="true"]</code>
                                <button class="es-copy-btn-small" onclick="copyToClipboard('[ensemble_events layout=&quot;slider&quot; columns=&quot;4&quot; arrows=&quot;true&quot; dots=&quot;true&quot;]')">
                                    <span class="dashicons dashicons-admin-page"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                   
                </div>
                
                <!-- Single Event Shortcode -->
                <div class="es-shortcode-card">
                    <div class="es-card-header">
                        <span class="es-card-icon">
                            <span class="dashicons dashicons-tickets-alt"></span>
                        </span>
                        <h3><?php _e('Single Event', 'ensemble'); ?></h3>
                    </div>
                    
                    <div class="es-card-body">
                        <p class="es-card-description">
                            <?php _e('Display a single event with all details, dates, location, and artist information.', 'ensemble'); ?>
                        </p>
                        
                        <div class="es-shortcode-box">
                            <code id="shortcode-event">[ensemble_event id="123"]</code>
                            <button class="es-copy-btn" data-target="shortcode-event">
                                <span class="dashicons dashicons-admin-page"></span>
                            </button>
                        </div>
                        
                        <div class="es-parameters">
                            <h4><?php _e('Parameters:', 'ensemble'); ?></h4>
                            <ul>
                                <li>
                                    <code>id</code>
                                    <span class="es-param-type">int</span>
                                    <span class="es-param-desc"><?php _e('Event post ID', 'ensemble'); ?></span>
                                    <span class="es-param-default">Required</span>
                                </li>
                                <li>
                                    <code>show_artist</code>
                                    <span class="es-param-type">bool</span>
                                    <span class="es-param-desc"><?php _e('Show artist information', 'ensemble'); ?></span>
                                    <span class="es-param-default">Default: true</span>
                                </li>
                                <li>
                                    <code>show_location</code>
                                    <span class="es-param-type">bool</span>
                                    <span class="es-param-desc"><?php _e('Show location information', 'ensemble'); ?></span>
                                    <span class="es-param-default">Default: true</span>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="es-examples">
                            <h4><?php _e('Examples:', 'ensemble'); ?></h4>
                            <div class="es-example-code">
                                <code>[ensemble_event id="123"]</code>
                                <button class="es-copy-btn-small" onclick="copyToClipboard('[ensemble_event id=&quot;123&quot;]')">
                                    <span class="dashicons dashicons-admin-page"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    
                </div>
                
                <!-- Artists List Shortcode -->
                <div class="es-shortcode-card">
                    <div class="es-card-header">
                        <span class="es-card-icon">
                            <span class="dashicons dashicons-star-filled"></span>
                        </span>
                        <h3><?php _e('Artists List', 'ensemble'); ?></h3>
                        <span class="es-badge es-badge-new">v2.8</span>
                    </div>
                    
                    <div class="es-card-body">
                        <p class="es-card-description">
                            <?php _e('Display artists in various layouts - from compact avatars to large portrait cards.', 'ensemble'); ?>
                        </p>
                        
                        <div class="es-shortcode-box">
                            <code id="shortcode-artists">[ensemble_artists]</code>
                            <button class="es-copy-btn" data-target="shortcode-artists">
                                <span class="dashicons dashicons-admin-page"></span>
                            </button>
                        </div>
                        
                        <div class="es-parameters">
                            <h4><?php _e('Layout Options:', 'ensemble'); ?></h4>
                            <ul class="es-layout-options">
                                <li>
                                    <code>layout="grid"</code>
                                    <span class="es-param-desc"><?php _e('Standard responsive grid', 'ensemble'); ?></span>
                                </li>
                                <li>
                                    <code>layout="slider"</code>
                                    <span class="es-param-desc"><?php _e('Horizontal carousel with arrows', 'ensemble'); ?></span>
                                </li>
                                <li>
                                    <code>layout="cards"</code>
                                    <span class="es-param-desc"><?php _e('Large portrait cards with bio', 'ensemble'); ?></span>
                                </li>
                                <li>
                                    <code>layout="compact"</code>
                                    <span class="es-param-desc"><?php _e('Small avatars + name (perfect for sidebar)', 'ensemble'); ?></span>
                                </li>
                                <li>
                                    <code>layout="list"</code>
                                    <span class="es-param-desc"><?php _e('Detailed list view', 'ensemble'); ?></span>
                                </li>
                            </ul>
                            
                            <h4><?php _e('General Parameters:', 'ensemble'); ?></h4>
                            <ul>
                                <li>
                                    <code>limit</code>
                                    <span class="es-param-type">int</span>
                                    <span class="es-param-desc"><?php _e('Number of artists to show', 'ensemble'); ?></span>
                                    <span class="es-param-default">Default: 12</span>
                                </li>
                                <li>
                                    <code>columns</code>
                                    <span class="es-param-type">int</span>
                                    <span class="es-param-desc"><?php _e('Columns (2-4)', 'ensemble'); ?></span>
                                    <span class="es-param-default">Default: 3</span>
                                </li>
                                <li>
                                    <code>category</code>
                                    <span class="es-param-type">string</span>
                                    <span class="es-param-desc"><?php _e('Filter by category slug', 'ensemble'); ?></span>
                                    <span class="es-param-default">Default: all</span>
                                </li>
                                <li>
                                    <code>show_bio</code>
                                    <span class="es-param-type">bool</span>
                                    <span class="es-param-desc"><?php _e('Show artist biography', 'ensemble'); ?></span>
                                    <span class="es-param-default">Default: true</span>
                                </li>
                            </ul>
                            
                            <h4><?php _e('Slider Options:', 'ensemble'); ?></h4>
                            <ul>
                                <li><code>autoplay</code>, <code>loop</code>, <code>dots</code>, <code>arrows</code>, <code>gap</code></li>
                            </ul>
                        </div>
                        
                        <div class="es-examples">
                            <h4><?php _e('Examples:', 'ensemble'); ?></h4>
                            <div class="es-example-code">
                                <code>[ensemble_artists layout="grid" columns="4"]</code>
                                <button class="es-copy-btn-small" onclick="copyToClipboard('[ensemble_artists layout=&quot;grid&quot; columns=&quot;4&quot;]')">
                                    <span class="dashicons dashicons-admin-page"></span>
                                </button>
                            </div>
                            <div class="es-example-code">
                                <code>[ensemble_artists layout="slider" columns="4" arrows="true"]</code>
                                <button class="es-copy-btn-small" onclick="copyToClipboard('[ensemble_artists layout=&quot;slider&quot; columns=&quot;4&quot; arrows=&quot;true&quot;]')">
                                    <span class="dashicons dashicons-admin-page"></span>
                                </button>
                            </div>
                            <div class="es-example-code">
                                <code>[ensemble_artists layout="compact" columns="1" limit="6"]</code>
                                <button class="es-copy-btn-small" onclick="copyToClipboard('[ensemble_artists layout=&quot;compact&quot; columns=&quot;1&quot; limit=&quot;6&quot;]')">
                                    <span class="dashicons dashicons-admin-page"></span>
                                </button>
                            </div>
                            <div class="es-example-code">
                                <code>[ensemble_artists layout="cards" columns="3" show_bio="true"]</code>
                                <button class="es-copy-btn-small" onclick="copyToClipboard('[ensemble_artists layout=&quot;cards&quot; columns=&quot;3&quot; show_bio=&quot;true&quot;]')">
                                    <span class="dashicons dashicons-admin-page"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                   
                </div>
                
                <!-- Locations List Shortcode -->
                <div class="es-shortcode-card">
                    <div class="es-card-header">
                        <span class="es-card-icon">
                            <span class="dashicons dashicons-location"></span>
                        </span>
                        <h3><?php _e('Locations List', 'ensemble'); ?></h3>
                        <span class="es-badge es-badge-new">v2.8</span>
                    </div>
                    
                    <div class="es-card-body">
                        <p class="es-card-description">
                            <?php _e('Display venues and locations in grid, slider, or list format.', 'ensemble'); ?>
                        </p>
                        
                        <div class="es-shortcode-box">
                            <code id="shortcode-locations">[ensemble_locations]</code>
                            <button class="es-copy-btn" data-target="shortcode-locations">
                                <span class="dashicons dashicons-admin-page"></span>
                            </button>
                        </div>
                        
                        <div class="es-parameters">
                            <h4><?php _e('Layout Options:', 'ensemble'); ?></h4>
                            <ul class="es-layout-options">
                                <li>
                                    <code>layout="grid"</code>
                                    <span class="es-param-desc"><?php _e('Standard responsive grid', 'ensemble'); ?></span>
                                </li>
                                <li>
                                    <code>layout="slider"</code>
                                    <span class="es-param-desc"><?php _e('Horizontal carousel with arrows', 'ensemble'); ?></span>
                                </li>
                                <li>
                                    <code>layout="cards"</code>
                                    <span class="es-param-desc"><?php _e('Large cards with venue type badge', 'ensemble'); ?></span>
                                </li>
                                <li>
                                    <code>layout="list"</code>
                                    <span class="es-param-desc"><?php _e('Compact address list', 'ensemble'); ?></span>
                                </li>
                            </ul>
                            
                            <h4><?php _e('General Parameters:', 'ensemble'); ?></h4>
                            <ul>
                                <li>
                                    <code>limit</code>
                                    <span class="es-param-type">int</span>
                                    <span class="es-param-desc"><?php _e('Number of locations to show', 'ensemble'); ?></span>
                                    <span class="es-param-default">Default: 12</span>
                                </li>
                                <li>
                                    <code>columns</code>
                                    <span class="es-param-type">int</span>
                                    <span class="es-param-desc"><?php _e('Columns (2-4)', 'ensemble'); ?></span>
                                    <span class="es-param-default">Default: 3</span>
                                </li>
                                <li>
                                    <code>type</code>
                                    <span class="es-param-type">string</span>
                                    <span class="es-param-desc"><?php _e('Filter by location type slug', 'ensemble'); ?></span>
                                    <span class="es-param-default">Default: all</span>
                                </li>
                                <li>
                                    <code>show_address</code>
                                    <span class="es-param-type">bool</span>
                                    <span class="es-param-desc"><?php _e('Show address', 'ensemble'); ?></span>
                                    <span class="es-param-default">Default: true</span>
                                </li>
                            </ul>
                            
                            <h4><?php _e('Slider Options:', 'ensemble'); ?></h4>
                            <ul>
                                <li><code>autoplay</code>, <code>loop</code>, <code>dots</code>, <code>arrows</code>, <code>gap</code></li>
                            </ul>
                        </div>
                        
                        <div class="es-examples">
                            <h4><?php _e('Examples:', 'ensemble'); ?></h4>
                            <div class="es-example-code">
                                <code>[ensemble_locations layout="grid" columns="3"]</code>
                                <button class="es-copy-btn-small" onclick="copyToClipboard('[ensemble_locations layout=&quot;grid&quot; columns=&quot;3&quot;]')">
                                    <span class="dashicons dashicons-admin-page"></span>
                                </button>
                            </div>
                            <div class="es-example-code">
                                <code>[ensemble_locations layout="slider" columns="4" arrows="true"]</code>
                                <button class="es-copy-btn-small" onclick="copyToClipboard('[ensemble_locations layout=&quot;slider&quot; columns=&quot;4&quot; arrows=&quot;true&quot;]')">
                                    <span class="dashicons dashicons-admin-page"></span>
                                </button>
                            </div>
                            <div class="es-example-code">
                                <code>[ensemble_locations layout="cards" limit="6" show_address="true"]</code>
                                <button class="es-copy-btn-small" onclick="copyToClipboard('[ensemble_locations layout=&quot;cards&quot; limit=&quot;6&quot; show_address=&quot;true&quot;]')">
                                    <span class="dashicons dashicons-admin-page"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    
                </div>
                <!-- Upcoming Events Shortcode -->
                <div class="es-shortcode-card">
                    <div class="es-card-header">
                        <span class="es-card-icon">
                            <span class="dashicons dashicons-megaphone"></span>
                        </span>
                        <h3><?php _e('Upcoming Events', 'ensemble'); ?></h3>
                    </div>
                    
                    <div class="es-card-body">
                        <p class="es-card-description">
                            <?php _e('Display a compact list of upcoming events - perfect for sidebars and widgets.', 'ensemble'); ?>
                        </p>
                        
                        <div class="es-shortcode-box">
                            <code id="shortcode-upcoming">[ensemble_upcoming_events limit="5"]</code>
                            <button class="es-copy-btn" data-target="shortcode-upcoming">
                                <span class="dashicons dashicons-admin-page"></span>
                            </button>
                        </div>
                        
                        <div class="es-parameters">
                            <h4><?php _e('Parameters:', 'ensemble'); ?></h4>
                            <ul>
                                <li>
                                    <code>limit</code>
                                    <span class="es-param-type">int</span>
                                    <span class="es-param-desc"><?php _e('Number of events', 'ensemble'); ?></span>
                                    <span class="es-param-default">Default: 5</span>
                                </li>
                                <li>
                                    <code>show_countdown</code>
                                    <span class="es-param-type">bool</span>
                                    <span class="es-param-desc"><?php _e('Show countdown', 'ensemble'); ?></span>
                                    <span class="es-param-default">Default: false</span>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="es-examples">
                            <h4><?php _e('Examples:', 'ensemble'); ?></h4>
                            <div class="es-example-code">
                                <code>[ensemble_upcoming_events limit="3" show_countdown="true"]</code>
                                <button class="es-copy-btn-small" onclick="copyToClipboard('[ensemble_upcoming_events limit=&quot;3&quot; show_countdown=&quot;true&quot;]')">
                                    <span class="dashicons dashicons-admin-page"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Single Artist Shortcode -->
                <div class="es-shortcode-card">
                    <div class="es-card-header">
                        <span class="es-card-icon">
                            <span class="dashicons dashicons-admin-users"></span>
                        </span>
                        <h3><?php _e('Single Artist', 'ensemble'); ?></h3>
                    </div>
                    
                    <div class="es-card-body">
                        <p class="es-card-description">
                            <?php _e('Embed a single artist profile with bio, genre, social links, and upcoming events.', 'ensemble'); ?>
                        </p>
                        
                        <div class="es-shortcode-box">
                            <code id="shortcode-artist">[ensemble_artist id="456"]</code>
                            <button class="es-copy-btn" data-target="shortcode-artist">
                                <span class="dashicons dashicons-admin-page"></span>
                            </button>
                        </div>
                        
                        <div class="es-parameters">
                            <h4><?php _e('Parameters:', 'ensemble'); ?></h4>
                            <ul>
                                <li>
                                    <code>id</code>
                                    <span class="es-param-type">int</span>
                                    <span class="es-param-desc"><?php _e('Artist post ID', 'ensemble'); ?></span>
                                    <span class="es-param-default">Required</span>
                                </li>
                                <li>
                                    <code>layout</code>
                                    <span class="es-param-type">string</span>
                                    <span class="es-param-desc"><?php _e('Display layout (card, compact, full)', 'ensemble'); ?></span>
                                    <span class="es-param-default">Default: card</span>
                                </li>
                                <li>
                                    <code>show_genre</code>
                                    <span class="es-param-type">bool</span>
                                    <span class="es-param-desc"><?php _e('Show genre badge', 'ensemble'); ?></span>
                                    <span class="es-param-default">Default: true</span>
                                </li>
                                <li>
                                    <code>show_events</code>
                                    <span class="es-param-type">bool</span>
                                    <span class="es-param-desc"><?php _e('Show upcoming events', 'ensemble'); ?></span>
                                    <span class="es-param-default">Default: true</span>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="es-examples">
                            <h4><?php _e('Examples:', 'ensemble'); ?></h4>
                            <div class="es-example-code">
                                <code>[ensemble_artist id="456" layout="full"]</code>
                                <button class="es-copy-btn-small" onclick="copyToClipboard('[ensemble_artist id=&quot;456&quot; layout=&quot;full&quot;]')">
                                    <span class="dashicons dashicons-admin-page"></span>
                                </button>
                            </div>
                            <div class="es-example-code">
                                <code>[ensemble_artist id="456" layout="compact" show_events="true"]</code>
                                <button class="es-copy-btn-small" onclick="copyToClipboard('[ensemble_artist id=&quot;456&quot; layout=&quot;compact&quot; show_events=&quot;true&quot;]')">
                                    <span class="dashicons dashicons-admin-page"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                   
                </div>
                
                <!-- Single Location Shortcode -->
                <div class="es-shortcode-card">
                    <div class="es-card-header">
                        <span class="es-card-icon">
                            <span class="dashicons dashicons-location"></span>
                        </span>
                        <h3><?php _e('Single Location', 'ensemble'); ?></h3>
                    </div>
                    
                    <div class="es-card-body">
                        <p class="es-card-description">
                            <?php _e('Embed a single location with full address, contact info, and upcoming events.', 'ensemble'); ?>
                        </p>
                        
                        <div class="es-shortcode-box">
                            <code id="shortcode-location">[ensemble_location id="789"]</code>
                            <button class="es-copy-btn" data-target="shortcode-location">
                                <span class="dashicons dashicons-admin-page"></span>
                            </button>
                        </div>
                        
                        <div class="es-parameters">
                            <h4><?php _e('Parameters:', 'ensemble'); ?></h4>
                            <ul>
                                <li>
                                    <code>id</code>
                                    <span class="es-param-type">int</span>
                                    <span class="es-param-desc"><?php _e('Location post ID', 'ensemble'); ?></span>
                                    <span class="es-param-default">Required</span>
                                </li>
                                <li>
                                    <code>layout</code>
                                    <span class="es-param-type">string</span>
                                    <span class="es-param-desc"><?php _e('Display layout (card, compact, full)', 'ensemble'); ?></span>
                                    <span class="es-param-default">Default: card</span>
                                </li>
                                <li>
                                    <code>show_address</code>
                                    <span class="es-param-type">bool</span>
                                    <span class="es-param-desc"><?php _e('Show full address', 'ensemble'); ?></span>
                                    <span class="es-param-default">Default: true</span>
                                </li>
                                <li>
                                    <code>show_events</code>
                                    <span class="es-param-type">bool</span>
                                    <span class="es-param-desc"><?php _e('Show upcoming events', 'ensemble'); ?></span>
                                    <span class="es-param-default">Default: true</span>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="es-examples">
                            <h4><?php _e('Examples:', 'ensemble'); ?></h4>
                            <div class="es-example-code">
                                <code>[ensemble_location id="789" layout="card"]</code>
                                <button class="es-copy-btn-small" onclick="copyToClipboard('[ensemble_location id=&quot;789&quot; layout=&quot;card&quot;]')">
                                    <span class="dashicons dashicons-admin-page"></span>
                                </button>
                            </div>
                            <div class="es-example-code">
                                <code>[ensemble_location id="789" layout="full" show_events="true"]</code>
                                <button class="es-copy-btn-small" onclick="copyToClipboard('[ensemble_location id=&quot;789&quot; layout=&quot;full&quot; show_events=&quot;true&quot;]')">
                                    <span class="dashicons dashicons-admin-page"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                   
                </div>

                <!-- Gallery Shortcode -->
                <div class="es-shortcode-card">
                    <div class="es-card-header">
                        <span class="es-card-icon">
                            <span class="dashicons dashicons-format-gallery"></span>
                        </span>
                        <h3><?php _e('Gallery', 'ensemble'); ?></h3>
                    </div>
                    
                    <div class="es-card-body">
                        <p class="es-card-description">
                            <?php _e('Display image galleries with grid, masonry, or slider layouts.', 'ensemble'); ?>
                        </p>
                        
                        <div class="es-shortcode-box">
                            <code id="shortcode-gallery">[ensemble_gallery id="123"]</code>
                            <button class="es-copy-btn" data-target="shortcode-gallery">
                                <span class="dashicons dashicons-admin-page"></span>
                            </button>
                        </div>
                        
                        <div class="es-parameters">
                            <h4><?php _e('Parameters:', 'ensemble'); ?></h4>
                            <ul>
                                <li>
                                    <code>id</code>
                                    <span class="es-param-type">int</span>
                                    <span class="es-param-desc"><?php _e('Gallery ID', 'ensemble'); ?></span>
                                    <span class="es-param-default"><?php _e('Optional', 'ensemble'); ?></span>
                                </li>
                                <li>
                                    <code>event</code>
                                    <span class="es-param-type">int</span>
                                    <span class="es-param-desc"><?php _e('Show gallery linked to event', 'ensemble'); ?></span>
                                    <span class="es-param-default"><?php _e('Optional', 'ensemble'); ?></span>
                                </li>
                                <li>
                                    <code>artist</code>
                                    <span class="es-param-type">int</span>
                                    <span class="es-param-desc"><?php _e('Show gallery linked to artist', 'ensemble'); ?></span>
                                    <span class="es-param-default"><?php _e('Optional', 'ensemble'); ?></span>
                                </li>
                                <li>
                                    <code>layout</code>
                                    <span class="es-param-type">string</span>
                                    <span class="es-param-desc"><?php _e('grid, masonry, slider', 'ensemble'); ?></span>
                                    <span class="es-param-default">Default: grid</span>
                                </li>
                                <li>
                                    <code>columns</code>
                                    <span class="es-param-type">int</span>
                                    <span class="es-param-desc"><?php _e('Number of columns (2-5)', 'ensemble'); ?></span>
                                    <span class="es-param-default">Default: 3</span>
                                </li>
                                <li>
                                    <code>lightbox</code>
                                    <span class="es-param-type">bool</span>
                                    <span class="es-param-desc"><?php _e('Enable lightbox', 'ensemble'); ?></span>
                                    <span class="es-param-default">Default: true</span>
                                </li>
                                <li>
                                    <code>limit</code>
                                    <span class="es-param-type">int</span>
                                    <span class="es-param-desc"><?php _e('Max number of images', 'ensemble'); ?></span>
                                    <span class="es-param-default">Default: all</span>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="es-examples">
                            <h4><?php _e('Examples:', 'ensemble'); ?></h4>
                            <div class="es-example-code">
                                <code>[ensemble_gallery id="123" columns="4"]</code>
                                <button class="es-copy-btn-small" onclick="copyToClipboard('[ensemble_gallery id=&quot;123&quot; columns=&quot;4&quot;]')">
                                    <span class="dashicons dashicons-admin-page"></span>
                                </button>
                            </div>
                            <div class="es-example-code">
                                <code>[ensemble_gallery event="456" layout="masonry"]</code>
                                <button class="es-copy-btn-small" onclick="copyToClipboard('[ensemble_gallery event=&quot;456&quot; layout=&quot;masonry&quot;]')">
                                    <span class="dashicons dashicons-admin-page"></span>
                                </button>
                            </div>
                            <div class="es-example-code">
                                <code>[ensemble_gallery artist="789" columns="5" lightbox="true"]</code>
                                <button class="es-copy-btn-small" onclick="copyToClipboard('[ensemble_gallery artist=&quot;789&quot; columns=&quot;5&quot; lightbox=&quot;true&quot;]')">
                                    <span class="dashicons dashicons-admin-page"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                
                <!-- Event Lineup Shortcode -->
                <div class="es-shortcode-card">
                    <div class="es-card-header">
                        <span class="es-card-icon">
                            <span class="dashicons dashicons-groups"></span>
                        </span>
                        <h3><?php _e('Event Lineup', 'ensemble'); ?></h3>
                    </div>
                    
                    <div class="es-card-body">
                        <p class="es-card-description">
                            <?php _e('Display artist lineup for a specific event with details.', 'ensemble'); ?>
                        </p>
                        
                        <div class="es-shortcode-box">
                            <code id="shortcode-lineup">[ensemble_lineup event_id="123"]</code>
                            <button class="es-copy-btn" data-target="shortcode-lineup">
                                <span class="dashicons dashicons-admin-page"></span>
                            </button>
                        </div>
                        
                        <div class="es-parameters">
                            <h4><?php _e('Parameters:', 'ensemble'); ?></h4>
                            <ul>
                                <li>
                                    <code>event_id</code>
                                    <span class="es-param-type">int</span>
                                    <span class="es-param-desc"><?php _e('Event ID', 'ensemble'); ?></span>
                                    <span class="es-param-default">Required</span>
                                </li>
                                <li>
                                    <code>show_times</code>
                                    <span class="es-param-type">bool</span>
                                    <span class="es-param-desc"><?php _e('Show times', 'ensemble'); ?></span>
                                    <span class="es-param-default">Default: false</span>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="es-examples">
                            <h4><?php _e('Examples:', 'ensemble'); ?></h4>
                            <div class="es-example-code">
                                <code>[ensemble_lineup event_id="123" show_times="true"]</code>
                                <button class="es-copy-btn-small" onclick="copyToClipboard('[ensemble_lineup event_id=&quot;123&quot; show_times=&quot;true&quot;]')">
                                    <span class="dashicons dashicons-admin-page"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Featured Events Shortcode -->
                <div class="es-shortcode-card">
                    <div class="es-card-header">
                        <span class="es-card-icon">
                            <span class="dashicons dashicons-star-filled"></span>
                        </span>
                        <h3><?php _e('Featured Events', 'ensemble'); ?></h3>
                    </div>
                    
                    <div class="es-card-body">
                        <p class="es-card-description">
                            <?php _e('Showcase featured events - perfect for homepage heroes.', 'ensemble'); ?>
                        </p>
                        
                        <div class="es-shortcode-box">
                            <code id="shortcode-featured">[ensemble_featured_events limit="3"]</code>
                            <button class="es-copy-btn" data-target="shortcode-featured">
                                <span class="dashicons dashicons-admin-page"></span>
                            </button>
                        </div>
                        
                        <div class="es-parameters">
                            <h4><?php _e('Parameters:', 'ensemble'); ?></h4>
                            <ul>
                                <li>
                                    <code>limit</code>
                                    <span class="es-param-type">int</span>
                                    <span class="es-param-desc"><?php _e('Number of events', 'ensemble'); ?></span>
                                    <span class="es-param-default">Default: 3</span>
                                </li>
                                <li>
                                    <code>columns</code>
                                    <span class="es-param-type">int</span>
                                    <span class="es-param-desc"><?php _e('Grid columns', 'ensemble'); ?></span>
                                    <span class="es-param-default">Default: 3</span>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="es-examples">
                            <h4><?php _e('Examples:', 'ensemble'); ?></h4>
                            <div class="es-example-code">
                                <code>[ensemble_featured_events limit="3" columns="3"]</code>
                                <button class="es-copy-btn-small" onclick="copyToClipboard('[ensemble_featured_events limit=&quot;3&quot; columns=&quot;3&quot;]')">
                                    <span class="dashicons dashicons-admin-page"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Filterable Events Grid Shortcode -->
               <!-- Filterable Events Grid Shortcode -->
<div class="es-shortcode-card">
    <div class="es-card-header">
        <span class="es-card-icon">
            <span class="dashicons dashicons-filter"></span>
        </span>
        <h3><?php _e('Filterable Events Grid', 'ensemble'); ?></h3>
    </div>
    
    <div class="es-card-body">
        <p class="es-card-description">
            <?php _e('Display events in a grid with filters for categories, artists, locations, and search functionality. Events reload automatically via AJAX when filters change.', 'ensemble'); ?>
        </p>
        
        <div class="es-shortcode-box">
            <code id="shortcode-grid">[ensemble_events_grid]</code>
            <button class="es-copy-btn" data-target="shortcode-grid">
                <span class="dashicons dashicons-admin-page"></span>
            </button>
        </div>
        
        <div class="es-parameters">
            <h4><?php _e('Parameters:', 'ensemble'); ?></h4>
            <ul>
                <li>
                    <code>layout</code>
                    <span class="es-param-type">string</span>
                    <span class="es-param-desc"><?php _e('Display layout (grid, list, card)', 'ensemble'); ?></span>
                    <span class="es-param-default">Default: grid</span>
                </li>
                <li>
                    <code>columns</code>
                    <span class="es-param-type">int</span>
                    <span class="es-param-desc"><?php _e('Number of columns (2-4)', 'ensemble'); ?></span>
                    <span class="es-param-default">Default: 3</span>
                </li>
                <li>
                    <code>limit</code>
                    <span class="es-param-type">int</span>
                    <span class="es-param-desc"><?php _e('Number of events to show', 'ensemble'); ?></span>
                    <span class="es-param-default">Default: 12</span>
                </li>
                <li>
                    <code>show_filters</code>
                    <span class="es-param-type">bool</span>
                    <span class="es-param-desc"><?php _e('Show filter dropdowns', 'ensemble'); ?></span>
                    <span class="es-param-default">Default: true</span>
                </li>
                <li>
                    <code>show_search</code>
                    <span class="es-param-type">bool</span>
                    <span class="es-param-desc"><?php _e('Show search field', 'ensemble'); ?></span>
                    <span class="es-param-default">Default: true</span>
                </li>
            </ul>
        </div>
        
        <div class="es-examples">
            <h4><?php _e('Examples:', 'ensemble'); ?></h4>
            <div class="es-example-code">
                <code>[ensemble_events_grid columns="4"]</code>
                <button class="es-copy-btn-small" onclick="copyToClipboard('[ensemble_events_grid columns=&quot;4&quot;]')">
                    <span class="dashicons dashicons-admin-page"></span>
                </button>
            </div>
            <div class="es-example-code">
                <code>[ensemble_events_grid show_filters="true" limit="20"]</code>
                <button class="es-copy-btn-small" onclick="copyToClipboard('[ensemble_events_grid show_filters=&quot;true&quot; limit=&quot;20&quot;]')">
                    <span class="dashicons dashicons-admin-page"></span>
                </button>
            </div>
        </div>
    </div>
</div>
            </div>
            
            <?php
            // Add-on Shortcodes Section
            if (class_exists('ES_Addon_Manager')) {
                $has_addon_shortcodes = false;
                
                // Check if any shortcode-providing addon is active
                if (ES_Addon_Manager::is_addon_active('tickets') || ES_Addon_Manager::is_addon_active('maps')) {
                    $has_addon_shortcodes = true;
                }
                
                if ($has_addon_shortcodes):
            ?>
            
            <!-- Add-on Shortcodes Section Header -->
            <div class="es-section-divider" style="margin: 40px 0 30px 0;">
                <div class="es-divider-icon">
                    <?php ES_Icons::icon('puzzle'); ?>
                </div>
                <h2><?php _e('Add-on Shortcodes', 'ensemble'); ?></h2>
                <p><?php _e('Shortcodes von aktivierten Add-ons', 'ensemble'); ?></p>
            </div>
            
            <div class="es-shortcode-grid">
                
                <?php if (ES_Addon_Manager::is_addon_active('tickets')): ?>
                <!-- Tickets Shortcode -->
                <div class="es-shortcode-card">
                    <div class="es-card-header">
                        <span class="es-card-icon es-card-icon-addon">
                            <?php ES_Icons::icon('ticket'); ?>
                        </span>
                        <h3><?php _e('Event Tickets', 'ensemble'); ?></h3>
                    </div>
                    
                    <div class="es-card-body">
                        <p class="es-card-description">
                            <?php _e('Displays tickets for an event with price, availability and purchase links.', 'ensemble'); ?>
                        </p>
                        
                        <div class="es-shortcode-box">
                            <code id="shortcode-tickets">[ensemble_tickets]</code>
                            <button class="es-copy-btn" data-target="shortcode-tickets">
                                <span class="dashicons dashicons-admin-page"></span>
                            </button>
                        </div>
                        
                        <div class="es-parameters">
                            <h4><?php _e('Parameters:', 'ensemble'); ?></h4>
                            <ul>
                                <li>
                                    <code>event_id</code>
                                    <span class="es-param-type">int</span>
                                    <span class="es-param-desc"><?php _e('Event ID', 'ensemble'); ?></span>
                                    <span class="es-param-default"><?php _e('Default: current event', 'ensemble'); ?></span>
                                </li>
                                <li>
                                    <code>layout</code>
                                    <span class="es-param-type">string</span>
                                    <span class="es-param-desc"><?php _e('Display layout (list, grid, compact)', 'ensemble'); ?></span>
                                    <span class="es-param-default">Default: list</span>
                                </li>
                                <li>
                                    <code>show_price</code>
                                    <span class="es-param-type">bool</span>
                                    <span class="es-param-desc"><?php _e('Show ticket prices', 'ensemble'); ?></span>
                                    <span class="es-param-default">Default: true</span>
                                </li>
                                <li>
                                    <code>show_availability</code>
                                    <span class="es-param-type">bool</span>
                                    <span class="es-param-desc"><?php _e('Show availability status', 'ensemble'); ?></span>
                                    <span class="es-param-default">Default: true</span>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="es-examples">
                            <h4><?php _e('Examples:', 'ensemble'); ?></h4>
                            <div class="es-example-code">
                                <code>[ensemble_tickets event_id="123"]</code>
                                <button class="es-copy-btn-small" onclick="copyToClipboard('[ensemble_tickets event_id=&quot;123&quot;]')">
                                    <span class="dashicons dashicons-admin-page"></span>
                                </button>
                            </div>
                            <div class="es-example-code">
                                <code>[ensemble_tickets layout="compact" show_price="true"]</code>
                                <button class="es-copy-btn-small" onclick="copyToClipboard('[ensemble_tickets layout=&quot;compact&quot; show_price=&quot;true&quot;]')">
                                    <span class="dashicons dashicons-admin-page"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (ES_Addon_Manager::is_addon_active('maps')): ?>
                <!-- Maps Shortcode -->
                <div class="es-shortcode-card">
                    <div class="es-card-header">
                        <span class="es-card-icon es-card-icon-addon">
                            <?php ES_Icons::icon('map'); ?>
                        </span>
                        <h3><?php _e('Event Map', 'ensemble'); ?></h3>
                    </div>
                    
                    <div class="es-card-body">
                        <p class="es-card-description">
                            <?php _e('Zeigt eine interaktive Karte mit Event-Locations und Markern.', 'ensemble'); ?>
                        </p>
                        
                        <div class="es-shortcode-box">
                            <code id="shortcode-map">[ensemble_map]</code>
                            <button class="es-copy-btn" data-target="shortcode-map">
                                <span class="dashicons dashicons-admin-page"></span>
                            </button>
                        </div>
                        
                        <div class="es-parameters">
                            <h4><?php _e('Parameters:', 'ensemble'); ?></h4>
                            <ul>
                                <li>
                                    <code>height</code>
                                    <span class="es-param-type">string</span>
                                    <span class="es-param-desc"><?php _e('Map height', 'ensemble'); ?></span>
                                    <span class="es-param-default">Default: 400px</span>
                                </li>
                                <li>
                                    <code>zoom</code>
                                    <span class="es-param-type">int</span>
                                    <span class="es-param-desc"><?php _e('Initial zoom level (1-20)', 'ensemble'); ?></span>
                                    <span class="es-param-default">Default: 12</span>
                                </li>
                                <li>
                                    <code>center</code>
                                    <span class="es-param-type">string</span>
                                    <span class="es-param-desc"><?php _e('Center coordinates (lat,lng)', 'ensemble'); ?></span>
                                    <span class="es-param-default">Default: auto</span>
                                </li>
                                <li>
                                    <code>category</code>
                                    <span class="es-param-type">string</span>
                                    <span class="es-param-desc"><?php _e('Filter by category slug', 'ensemble'); ?></span>
                                    <span class="es-param-default">Default: all</span>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="es-examples">
                            <h4><?php _e('Examples:', 'ensemble'); ?></h4>
                            <div class="es-example-code">
                                <code>[ensemble_map height="500px" zoom="14"]</code>
                                <button class="es-copy-btn-small" onclick="copyToClipboard('[ensemble_map height=&quot;500px&quot; zoom=&quot;14&quot;]')">
                                    <span class="dashicons dashicons-admin-page"></span>
                                </button>
                            </div>
                            <div class="es-example-code">
                                <code>[ensemble_map category="concerts" center="48.1351,11.5820"]</code>
                                <button class="es-copy-btn-small" onclick="copyToClipboard('[ensemble_map category=&quot;concerts&quot; center=&quot;48.1351,11.5820&quot;]')">
                                    <span class="dashicons dashicons-admin-page"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (ES_Addon_Manager::is_addon_active('reservations')): ?>
                <!-- Reservation Form Shortcode -->
                <div class="es-shortcode-card">
                    <div class="es-card-header">
                        <span class="es-card-icon es-card-icon-addon">
                            <?php ES_Icons::icon('clipboard-list'); ?>
                        </span>
                        <h3><?php _e('Reservation Form', 'ensemble'); ?></h3>
                    </div>
                    
                    <div class="es-card-body">
                        <p class="es-card-description">
                            <?php _e('Displays a reservation form for guest lists, table reservations or VIP lists.', 'ensemble'); ?>
                        </p>
                        
                        <div class="es-shortcode-box">
                            <code id="shortcode-reservation">[ensemble_reservation_form]</code>
                            <button class="es-copy-btn" data-target="shortcode-reservation">
                                <span class="dashicons dashicons-admin-page"></span>
                            </button>
                        </div>
                        
                        <div class="es-parameters">
                            <h4><?php _e('Parameters:', 'ensemble'); ?></h4>
                            <ul>
                                <li>
                                    <code>event</code>
                                    <span class="es-param-type">int</span>
                                    <span class="es-param-desc"><?php _e('Event ID', 'ensemble'); ?></span>
                                    <span class="es-param-default"><?php _e('Default: current event', 'ensemble'); ?></span>
                                </li>
                                <li>
                                    <code>type</code>
                                    <span class="es-param-type">string</span>
                                    <span class="es-param-desc"><?php _e('Reservation type (guestlist, table, vip)', 'ensemble'); ?></span>
                                    <span class="es-param-default"><?php _e('Default: all enabled', 'ensemble'); ?></span>
                                </li>
                                <li>
                                    <code>button</code>
                                    <span class="es-param-type">string</span>
                                    <span class="es-param-desc"><?php _e('Button text', 'ensemble'); ?></span>
                                    <span class="es-param-default">Default: Reservieren</span>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="es-examples">
                            <h4><?php _e('Examples:', 'ensemble'); ?></h4>
                            <div class="es-example-code">
                                <code>[ensemble_reservation_form event="123"]</code>
                                <button class="es-copy-btn-small" onclick="copyToClipboard('[ensemble_reservation_form event=&quot;123&quot;]')">
                                    <span class="dashicons dashicons-admin-page"></span>
                                </button>
                            </div>
                            <div class="es-example-code">
                                <code>[ensemble_reservation_form type="vip" button="VIP-Liste"]</code>
                                <button class="es-copy-btn-small" onclick="copyToClipboard('[ensemble_reservation_form type=&quot;vip&quot; button=&quot;VIP-Liste&quot;]')">
                                    <span class="dashicons dashicons-admin-page"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Guestlist Shortcode -->
                <div class="es-shortcode-card">
                    <div class="es-card-header">
                        <span class="es-card-icon es-card-icon-addon">
                            <?php ES_Icons::icon('users'); ?>
                        </span>
                        <h3><?php _e('Public Guestlist', 'ensemble'); ?></h3>
                    </div>
                    
                    <div class="es-card-body">
                        <p class="es-card-description">
                            <?php _e('Displays a public guest list with number of guests or names.', 'ensemble'); ?>
                        </p>
                        
                        <div class="es-shortcode-box">
                            <code id="shortcode-guestlist">[ensemble_guestlist]</code>
                            <button class="es-copy-btn" data-target="shortcode-guestlist">
                                <span class="dashicons dashicons-admin-page"></span>
                            </button>
                        </div>
                        
                        <div class="es-parameters">
                            <h4><?php _e('Parameters:', 'ensemble'); ?></h4>
                            <ul>
                                <li>
                                    <code>event</code>
                                    <span class="es-param-type">int</span>
                                    <span class="es-param-desc"><?php _e('Event ID', 'ensemble'); ?></span>
                                    <span class="es-param-default"><?php _e('Default: current event', 'ensemble'); ?></span>
                                </li>
                                <li>
                                    <code>show_count</code>
                                    <span class="es-param-type">bool</span>
                                    <span class="es-param-desc"><?php _e('Show total guest count', 'ensemble'); ?></span>
                                    <span class="es-param-default">Default: true</span>
                                </li>
                                <li>
                                    <code>show_names</code>
                                    <span class="es-param-type">bool</span>
                                    <span class="es-param-desc"><?php _e('Show guest names publicly', 'ensemble'); ?></span>
                                    <span class="es-param-default">Default: false</span>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="es-examples">
                            <h4><?php _e('Examples:', 'ensemble'); ?></h4>
                            <div class="es-example-code">
                                <code>[ensemble_guestlist event="123" show_count="true"]</code>
                                <button class="es-copy-btn-small" onclick="copyToClipboard('[ensemble_guestlist event=&quot;123&quot; show_count=&quot;true&quot;]')">
                                    <span class="dashicons dashicons-admin-page"></span>
                                </button>
                            </div>
                            <div class="es-example-code">
                                <code>[ensemble_guestlist show_names="true"]</code>
                                <button class="es-copy-btn-small" onclick="copyToClipboard('[ensemble_guestlist show_names=&quot;true&quot;]')">
                                    <span class="dashicons dashicons-admin-page"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (ES_Addon_Manager::is_addon_active('catalog')): ?>
                <!-- Catalog Shortcode -->
                <div class="es-shortcode-card">
                    <div class="es-card-header">
                        <span class="es-card-icon es-card-icon-addon">
                            <?php ES_Icons::icon('menu'); ?>
                        </span>
                        <h3><?php _e('Catalog / Menu', 'ensemble'); ?></h3>
                    </div>
                    
                    <div class="es-card-body">
                        <p class="es-card-description">
                            <?php _e('Displays a catalog (menu, drink list, services, etc.) with categories and items.', 'ensemble'); ?>
                        </p>
                        
                        <div class="es-shortcode-box">
                            <code id="shortcode-catalog">[ensemble_catalog id="123"]</code>
                            <button class="es-copy-btn" data-target="shortcode-catalog">
                                <span class="dashicons dashicons-admin-page"></span>
                            </button>
                        </div>
                        
                        <div class="es-parameters">
                            <h4><?php _e('Parameters:', 'ensemble'); ?></h4>
                            <ul>
                                <li>
                                    <code>id</code>
                                    <span class="es-param-type">int</span>
                                    <span class="es-param-desc"><?php _e('Catalog ID (required unless location/event set)', 'ensemble'); ?></span>
                                    <span class="es-param-default"></span>
                                </li>
                                <li>
                                    <code>location</code>
                                    <span class="es-param-type">int</span>
                                    <span class="es-param-desc"><?php _e('Load catalog assigned to this location', 'ensemble'); ?></span>
                                    <span class="es-param-default"></span>
                                </li>
                                <li>
                                    <code>event</code>
                                    <span class="es-param-type">int</span>
                                    <span class="es-param-desc"><?php _e('Load catalog assigned to this event', 'ensemble'); ?></span>
                                    <span class="es-param-default"></span>
                                </li>
                                <li>
                                    <code>layout</code>
                                    <span class="es-param-type">string</span>
                                    <span class="es-param-desc"><?php _e('Display layout (list, grid)', 'ensemble'); ?></span>
                                    <span class="es-param-default">Default: list</span>
                                </li>
                                <li>
                                    <code>show_prices</code>
                                    <span class="es-param-type">bool</span>
                                    <span class="es-param-desc"><?php _e('Show item prices', 'ensemble'); ?></span>
                                    <span class="es-param-default">Default: true</span>
                                </li>
                                <li>
                                    <code>show_images</code>
                                    <span class="es-param-type">bool</span>
                                    <span class="es-param-desc"><?php _e('Show item images', 'ensemble'); ?></span>
                                    <span class="es-param-default">Default: false</span>
                                </li>
                                <li>
                                    <code>show_filter</code>
                                    <span class="es-param-type">bool</span>
                                    <span class="es-param-desc"><?php _e('Show category filter', 'ensemble'); ?></span>
                                    <span class="es-param-default">Default: false</span>
                                </li>
                                <li>
                                    <code>columns</code>
                                    <span class="es-param-type">int</span>
                                    <span class="es-param-desc"><?php _e('Number of columns (1-4)', 'ensemble'); ?></span>
                                    <span class="es-param-default">Default: 1</span>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="es-examples">
                            <h4><?php _e('Examples:', 'ensemble'); ?></h4>
                            <div class="es-example-code">
                                <code>[ensemble_catalog id="45" layout="list"]</code>
                                <button class="es-copy-btn-small" onclick="copyToClipboard('[ensemble_catalog id=&quot;45&quot; layout=&quot;list&quot;]')">
                                    <span class="dashicons dashicons-admin-page"></span>
                                </button>
                            </div>
                            <div class="es-example-code">
                                <code>[ensemble_catalog location="123" show_images="true" columns="2"]</code>
                                <button class="es-copy-btn-small" onclick="copyToClipboard('[ensemble_catalog location=&quot;123&quot; show_images=&quot;true&quot; columns=&quot;2&quot;]')">
                                    <span class="dashicons dashicons-admin-page"></span>
                                </button>
                            </div>
                            <div class="es-example-code">
                                <code>[ensemble_catalog event="789" show_filter="true"]</code>
                                <button class="es-copy-btn-small" onclick="copyToClipboard('[ensemble_catalog event=&quot;789&quot; show_filter=&quot;true&quot;]')">
                                    <span class="dashicons dashicons-admin-page"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
            </div>
            
            <?php 
                endif;
            }
            ?>
            
            <!-- Info Box -->
            <div class="es-frontend-info-box">
                <span class="dashicons dashicons-info"></span>
                <div>
                    <strong><?php _e('Need Help?', 'ensemble'); ?></strong>
                    <p>
                        <?php _e('Shortcodes can be inserted into any page, post, or widget. Use the copy button to quickly grab the code. All parameters are optional and can be combined.', 'ensemble'); ?>
                    </p>
                </div>
            </div>
            
        </div>
