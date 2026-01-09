<?php
/**
 * Export Settings Page
 * Admin page for export and feed settings
 *
 * @package Ensemble
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Ensemble_Export_Settings {

    private $feed_generator;

    public function __construct() {
        require_once 'class-feed-generator.php';
        $this->feed_generator = new Ensemble_Feed_Generator();

        // Register admin menu
        add_action( 'admin_menu', array( $this, 'register_admin_menu' ), 20 );
        
        // Handle form submissions
        add_action( 'admin_init', array( $this, 'handle_form_submit' ) );
    }

    /**
     * Register admin menu page
     */
    public function register_admin_menu() {
        add_submenu_page(
            'ensemble',
            'Export & Feed',
            'Export & Feed',
            'manage_options',
            'ensemble-export',
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        $feed_settings = $this->feed_generator->get_feed_settings();
        
        ?>
        <div class="wrap">
            <h1>📤 Export & Feed Settings</h1>

            <?php if ( isset( $_GET['updated'] ) ) : ?>
                <div class="notice notice-success is-dismissible">
                    <p><strong>Settings saved.</strong></p>
                </div>
            <?php endif; ?>

            <div class="es-export-settings">
                
                <!-- Export Section -->
                <div class="es-settings-card">
                    <h2>📥 Export Events</h2>
                    <p>Download your events as an .ics file that you can import into other calendar applications.</p>
                    
                    <form method="post" action="" class="es-export-form">
                        <?php wp_nonce_field( 'ensemble_export_action', 'ensemble_export_nonce' ); ?>
                        <input type="hidden" name="action" value="export_events">
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">Date Range</th>
                                <td>
                                    <label>
                                        From: <input type="date" name="date_from" value="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>">
                                    </label>
                                    <label style="margin-left: 15px;">
                                        To: <input type="date" name="date_to" value="<?php echo esc_attr( date( 'Y-m-d', strtotime( '+12 months' ) ) ); ?>">
                                    </label>
                                    <p class="description">Leave empty to export all events.</p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">Category</th>
                                <td>
                                    <?php
                                    $categories = get_terms( array(
                                        'taxonomy' => 'ensemble_category',
                                        'hide_empty' => false,
                                    ) );
                                    ?>
                                    <select name="category_id">
                                        <option value="">All Categories</option>
                                        <?php foreach ( $categories as $category ) : ?>
                                            <option value="<?php echo esc_attr( $category->term_id ); ?>">
                                                <?php echo esc_html( $category->name ); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <button type="submit" class="button button-primary button-large">
                                📥 Download .ics File
                            </button>
                        </p>
                    </form>
                </div>

                <!-- Feed Section -->
                <div class="es-settings-card" style="margin-top: 30px;">
                    <h2>🔗 Public iCal Feed</h2>
                    <p>Generate a public URL that others can subscribe to. The feed updates automatically when you add or modify events.</p>
                    
                    <form method="post" action="">
                        <?php wp_nonce_field( 'ensemble_feed_action', 'ensemble_feed_nonce' ); ?>
                        <input type="hidden" name="action" value="toggle_feed">
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">Feed Status</th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="feed_enabled" value="1" <?php checked( $feed_settings['enabled'] ); ?>>
                                        Enable Public Feed
                                    </label>
                                    <p class="description">
                                        <?php if ( $feed_settings['enabled'] ) : ?>
                                            ✅ Feed is <strong>enabled</strong>
                                        <?php else : ?>
                                            ⚠️ Feed is <strong>disabled</strong>
                                        <?php endif; ?>
                                    </p>
                                </td>
                            </tr>
                            
                            <?php if ( $feed_settings['enabled'] ) : ?>
                            <tr>
                                <th scope="row">Feed URL</th>
                                <td>
                                    <input type="text" 
                                           value="<?php echo esc_url( $feed_settings['url'] ); ?>" 
                                           class="large-text" 
                                           readonly 
                                           onclick="this.select();">
                                    <p class="description">
                                        Share this URL with others so they can subscribe to your events.
                                    </p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">How to Subscribe</th>
                                <td>
                                    <strong>Google Calendar:</strong><br>
                                    <?php echo esc_html( $feed_settings['instructions']['google'] ); ?>
                                    
                                    <br><br><strong>Outlook:</strong><br>
                                    <?php echo esc_html( $feed_settings['instructions']['outlook'] ); ?>
                                    
                                    <br><br><strong>Apple Calendar:</strong><br>
                                    <?php echo esc_html( $feed_settings['instructions']['apple'] ); ?>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </table>
                        
                        <p class="submit">
                            <button type="submit" class="button button-primary">
                                💾 Save Feed Settings
                            </button>
                        </p>
                    </form>
                </div>

                <!-- Usage Tips -->
                <div class="es-settings-card" style="margin-top: 30px;">
                    <h2>💡 Usage Tips</h2>
                    
                    <h3>Export (.ics Download)</h3>
                    <ul>
                        <li>✅ One-time download of your events</li>
                        <li>✅ Can be imported into any calendar app</li>
                        <li>✅ Filter by date range or category</li>
                        <li>⚠️ No automatic updates - need to re-export if events change</li>
                    </ul>
                    
                    <h3>Feed (Subscription)</h3>
                    <ul>
                        <li>✅ Automatic updates when you change events</li>
                        <li>✅ Perfect for sharing with partners, press, or public</li>
                        <li>✅ Works with Google Calendar, Outlook, Apple Calendar</li>
                        <li>⚠️ Feed is public - anyone with the URL can see your events</li>
                        <li>💡 Updates typically within 1 hour (depends on subscriber's calendar app)</li>
                    </ul>
                    
                    <h3>Security Note</h3>
                    <p>
                        ⚠️ The feed URL contains only <strong>published events</strong>. 
                        Draft or private events are not included in the feed.
                    </p>
                </div>

            </div>
        </div>

        <style>
        .es-export-settings {
            max-width: 900px;
        }
        
        .es-settings-card {
            background: #fff;
            border: 1px solid #ccd0d4;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
            padding: 20px;
        }
        
        .es-settings-card h2 {
            margin-top: 0;
        }
        
        .es-settings-card h3 {
            margin-top: 20px;
            margin-bottom: 10px;
        }
        
        .es-settings-card ul {
            list-style: none;
            padding-left: 0;
        }
        
        .es-settings-card ul li {
            padding: 5px 0;
        }
        </style>
        <?php
    }

    /**
     * Handle form submission
     */
    public function handle_form_submit() {
        // Check if form submitted
        if ( ! isset( $_POST['action'] ) ) {
            return;
        }

        // Export events
        if ( $_POST['action'] === 'export_events' ) {
            if ( ! wp_verify_nonce( $_POST['ensemble_export_nonce'], 'ensemble_export_action' ) ) {
                wp_die( 'Security check failed' );
            }

            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( 'Insufficient permissions' );
            }

            // Build export args
            $args = array();

            if ( ! empty( $_POST['date_from'] ) ) {
                $args['date_from'] = sanitize_text_field( $_POST['date_from'] );
            }

            if ( ! empty( $_POST['date_to'] ) ) {
                $args['date_to'] = sanitize_text_field( $_POST['date_to'] );
            }

            if ( ! empty( $_POST['category_id'] ) ) {
                $args['category_id'] = intval( $_POST['category_id'] );
            }

            // Generate and download
            require_once dirname(dirname(__FILE__)) . '/class-export-handler.php';
            $export_handler = new Ensemble_Export_Handler();
            $ical = $export_handler->export_to_ical( $args );

            if ( ! is_wp_error( $ical ) ) {
                $filename = 'ensemble-events-' . current_time( 'Y-m-d' ) . '.ics';
                $export_handler->download_ical( $ical, $filename );
            }
        }

        // Toggle feed
        if ( $_POST['action'] === 'toggle_feed' ) {
            if ( ! wp_verify_nonce( $_POST['ensemble_feed_nonce'], 'ensemble_feed_action' ) ) {
                wp_die( 'Security check failed' );
            }

            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( 'Insufficient permissions' );
            }

            $feed_enabled = isset( $_POST['feed_enabled'] ) && $_POST['feed_enabled'] === '1';

            if ( $feed_enabled ) {
                $this->feed_generator->enable_feed();
            } else {
                $this->feed_generator->disable_feed();
            }

            // Redirect with success message
            wp_redirect( add_query_arg( 'updated', 'true', admin_url( 'admin.php?page=ensemble-export' ) ) );
            exit;
        }
    }
}

// Initialize
new Ensemble_Export_Settings();
