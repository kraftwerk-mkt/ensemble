<?php
/**
 * Downloads Settings Template
 * 
 * @package Ensemble
 * @subpackage Addons/Downloads
 * 
 * Variables available:
 * @var array $settings Settings array (passed from render_settings())
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get addon instance for type info
$addon = null;
if (class_exists('ES_Addon_Manager') && ES_Addon_Manager::is_addon_active('downloads')) {
    $addon = ES_Addon_Manager::get_active_addon('downloads');
}

// Get download types
$download_types = $addon ? $addon->get_download_types() : array();

// Ensure $settings has defaults
$settings = wp_parse_args($settings, array(
    'default_style'         => 'grid',
    'show_file_size'        => true,
    'show_download_count'   => false,
    'show_file_type'        => true,
    'show_date'             => true,
    'require_login'         => false,
    'allowed_roles'         => array(),
    'auto_display_events'   => true,
    'event_position'        => 'after_content',
    'event_title'           => 'Downloads & Materials',
    'auto_display_artists'  => true,
    'artist_position'       => 'after_content',
    'artist_title'          => 'Downloads',
    'enable_scheduling'     => true,
    'track_downloads'       => true,
    'type_colors'           => array(),
));

// Get available roles
$wp_roles = wp_roles();
$all_roles = $wp_roles->get_names();
?>

<div class="es-downloads-settings">
    
    <!-- Display Settings -->
    <div class="es-settings-section">
        <h4 class="es-settings-section__title">Display Settings</h4>
        
        <div class="es-settings-field">
            <label class="es-settings-field__label" for="downloads_default_style">
                Default Layout
            </label>
            <select name="default_style" id="downloads_default_style" class="es-settings-field__select">
                <option value="grid" <?php selected($settings['default_style'], 'grid'); ?>>Grid (Cards)</option>
                <option value="list" <?php selected($settings['default_style'], 'list'); ?>>List (Detailed)</option>
                <option value="compact" <?php selected($settings['default_style'], 'compact'); ?>>Compact (Sidebar)</option>
            </select>
        </div>
        
        <div class="es-settings-toggles">
            <label class="es-settings-toggle">
                <span class="es-settings-toggle__switch">
                    <input type="checkbox" name="show_file_size" value="1" <?php checked($settings['show_file_size']); ?>>
                    <span class="es-settings-toggle__track"></span>
                </span>
                <span class="es-settings-toggle__label">Show file size</span>
            </label>
            
            <label class="es-settings-toggle">
                <span class="es-settings-toggle__switch">
                    <input type="checkbox" name="show_file_type" value="1" <?php checked($settings['show_file_type']); ?>>
                    <span class="es-settings-toggle__track"></span>
                </span>
                <span class="es-settings-toggle__label">Show download type badge</span>
            </label>
            
            <label class="es-settings-toggle">
                <span class="es-settings-toggle__switch">
                    <input type="checkbox" name="show_download_count" value="1" <?php checked($settings['show_download_count']); ?>>
                    <span class="es-settings-toggle__track"></span>
                </span>
                <span class="es-settings-toggle__label">Show download count</span>
            </label>
            
            <label class="es-settings-toggle">
                <span class="es-settings-toggle__switch">
                    <input type="checkbox" name="show_date" value="1" <?php checked($settings['show_date']); ?>>
                    <span class="es-settings-toggle__track"></span>
                </span>
                <span class="es-settings-toggle__label">Show upload date</span>
            </label>
        </div>
    </div>
    
    <!-- Type Colors -->
    <div class="es-settings-section">
        <h4 class="es-settings-section__title">Type Badge Colors</h4>
        <p class="es-settings-section__desc">Customize the colors for each download type badge.</p>
        
        <div class="es-type-colors-grid">
            <?php foreach ($download_types as $slug => $type): 
                $saved_color = isset($settings['type_colors'][$slug]) ? $settings['type_colors'][$slug] : $type['color'];
            ?>
                <div class="es-type-color-item">
                    <input type="color" 
                           name="type_colors[<?php echo esc_attr($slug); ?>]" 
                           value="<?php echo esc_attr($saved_color); ?>"
                           class="es-type-color-input"
                           data-default="<?php echo esc_attr($type['color']); ?>">
                    <span class="es-type-color-label">
                        <span class="dashicons <?php echo esc_attr($type['icon']); ?>"></span>
                        <?php echo esc_html($type['label']); ?>
                    </span>
                    <button type="button" class="es-type-color-reset" title="Reset to default">
                        <span class="dashicons dashicons-image-rotate"></span>
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Tracking & Scheduling -->
    <div class="es-settings-section">
        <h4 class="es-settings-section__title">Tracking & Scheduling</h4>
        
        <div class="es-settings-toggles">
            <label class="es-settings-toggle">
                <span class="es-settings-toggle__switch">
                    <input type="checkbox" name="track_downloads" value="1" <?php checked($settings['track_downloads']); ?>>
                    <span class="es-settings-toggle__track"></span>
                </span>
                <span class="es-settings-toggle__label">Track download statistics</span>
            </label>
            
            <label class="es-settings-toggle">
                <span class="es-settings-toggle__switch">
                    <input type="checkbox" name="enable_scheduling" value="1" <?php checked($settings['enable_scheduling']); ?>>
                    <span class="es-settings-toggle__track"></span>
                </span>
                <span class="es-settings-toggle__label">Enable scheduled availability</span>
            </label>
        </div>
        
        <p class="es-settings-hint">
            Scheduled availability allows downloads to automatically become available at specific times (e.g., presentation slides after the talk ends).
        </p>
    </div>
    
    <!-- Access Control -->
    <div class="es-settings-section">
        <h4 class="es-settings-section__title">Access Control</h4>
        
        <div class="es-settings-toggles">
            <label class="es-settings-toggle">
                <span class="es-settings-toggle__switch">
                    <input type="checkbox" name="require_login" value="1" id="downloads_require_login" <?php checked($settings['require_login']); ?>>
                    <span class="es-settings-toggle__track"></span>
                </span>
                <span class="es-settings-toggle__label">Require login to download (default)</span>
            </label>
        </div>
        
        <div class="es-settings-subsection" id="downloads_roles_section" style="<?php echo $settings['require_login'] ? '' : 'opacity: 0.5; pointer-events: none;'; ?>">
            <label class="es-settings-field__label">
                Allowed user roles <span class="es-settings-hint-inline">(empty = all logged-in users)</span>
            </label>
            <div class="es-settings-checkboxes">
                <?php foreach ($all_roles as $role_slug => $role_name): ?>
                    <label class="es-settings-checkbox">
                        <input type="checkbox" 
                               name="allowed_roles[]" 
                               value="<?php echo esc_attr($role_slug); ?>"
                               <?php checked(in_array($role_slug, (array) $settings['allowed_roles'])); ?>>
                        <span><?php echo esc_html(translate_user_role($role_name)); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
            <p class="es-settings-hint">This is the default setting. Each download can be configured individually.</p>
        </div>
    </div>
    
    <!-- Event Integration -->
    <div class="es-settings-section">
        <h4 class="es-settings-section__title">Event Integration</h4>
        
        <div class="es-settings-toggles">
            <label class="es-settings-toggle">
                <span class="es-settings-toggle__switch">
                    <input type="checkbox" name="auto_display_events" value="1" id="downloads_auto_events" <?php checked($settings['auto_display_events']); ?>>
                    <span class="es-settings-toggle__track"></span>
                </span>
                <span class="es-settings-toggle__label">Automatically show downloads on event pages</span>
            </label>
        </div>
        
        <div class="es-settings-subsection" id="downloads_event_options" style="<?php echo $settings['auto_display_events'] ? '' : 'opacity: 0.5; pointer-events: none;'; ?>">
            <div class="es-settings-row">
                <div class="es-settings-field es-settings-field--half">
                    <label class="es-settings-field__label" for="downloads_event_title">Section Title</label>
                    <input type="text" 
                           name="event_title" 
                           id="downloads_event_title" 
                           class="es-settings-field__input"
                           value="<?php echo esc_attr($settings['event_title']); ?>"
                           placeholder="Downloads & Materials">
                </div>
                
                <div class="es-settings-field es-settings-field--half">
                    <label class="es-settings-field__label" for="downloads_event_position">Position</label>
                    <select name="event_position" id="downloads_event_position" class="es-settings-field__select">
                        <option value="before_content" <?php selected($settings['event_position'], 'before_content'); ?>>Before content</option>
                        <option value="after_content" <?php selected($settings['event_position'], 'after_content'); ?>>After content</option>
                        <option value="after_artists" <?php selected($settings['event_position'], 'after_artists'); ?>>After speakers/artists</option>
                        <option value="footer" <?php selected($settings['event_position'], 'footer'); ?>>Footer area</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Artist/Speaker Integration -->
    <div class="es-settings-section">
        <h4 class="es-settings-section__title">Speaker/Artist Integration</h4>
        
        <div class="es-settings-toggles">
            <label class="es-settings-toggle">
                <span class="es-settings-toggle__switch">
                    <input type="checkbox" name="auto_display_artists" value="1" id="downloads_auto_artists" <?php checked($settings['auto_display_artists']); ?>>
                    <span class="es-settings-toggle__track"></span>
                </span>
                <span class="es-settings-toggle__label">Automatically show downloads on speaker/artist pages</span>
            </label>
        </div>
        
        <div class="es-settings-subsection" id="downloads_artist_options" style="<?php echo $settings['auto_display_artists'] ? '' : 'opacity: 0.5; pointer-events: none;'; ?>">
            <div class="es-settings-row">
                <div class="es-settings-field es-settings-field--half">
                    <label class="es-settings-field__label" for="downloads_artist_title">Section Title</label>
                    <input type="text" 
                           name="artist_title" 
                           id="downloads_artist_title" 
                           class="es-settings-field__input"
                           value="<?php echo esc_attr($settings['artist_title']); ?>"
                           placeholder="Downloads">
                </div>
                
                <div class="es-settings-field es-settings-field--half">
                    <label class="es-settings-field__label" for="downloads_artist_position">Position</label>
                    <select name="artist_position" id="downloads_artist_position" class="es-settings-field__select">
                        <option value="before_content" <?php selected($settings['artist_position'], 'before_content'); ?>>Before content</option>
                        <option value="after_content" <?php selected($settings['artist_position'], 'after_content'); ?>>After content</option>
                        <option value="after_events" <?php selected($settings['artist_position'], 'after_events'); ?>>After events</option>
                        <option value="footer" <?php selected($settings['artist_position'], 'footer'); ?>>Footer area</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Shortcode Reference -->
    <div class="es-settings-section es-settings-section--info">
        <h4 class="es-settings-section__title">Shortcode Reference</h4>
        
        <!-- Basic Shortcode -->
        <div class="es-shortcode-block">
            <h5 class="es-shortcode-block__title">Basic Downloads</h5>
            <code class="es-shortcode-block__code">[ensemble_downloads]</code>
            
            <div class="es-shortcode-examples">
                <div class="es-shortcode-ref__item">
                    <code>[ensemble_downloads speaker="123"]</code>
                    <span>Downloads for a specific speaker</span>
                </div>
                <div class="es-shortcode-ref__item">
                    <code>[ensemble_downloads event="456" type="presentation"]</code>
                    <span>Only presentations for an event</span>
                </div>
                <div class="es-shortcode-ref__item">
                    <code>[ensemble_downloads style="list" limit="5"]</code>
                    <span>List style, max 5 items</span>
                </div>
            </div>
            
            <details class="es-shortcode-details">
                <summary>All parameters for [ensemble_downloads]</summary>
                <div class="es-shortcode-params">
                    <table>
                        <tr><th>Parameter</th><th>Values</th><th>Default</th><th>Description</th></tr>
                        <tr><td><code>speaker</code></td><td>ID</td><td>-</td><td>Filter by speaker/artist ID</td></tr>
                        <tr><td><code>event</code></td><td>ID</td><td>-</td><td>Filter by event/session ID</td></tr>
                        <tr><td><code>location</code></td><td>ID</td><td>-</td><td>Filter by location/room ID</td></tr>
                        <tr><td><code>type</code></td><td>presentation, cv, handout, video, photo, package, other</td><td>-</td><td>Filter by download type</td></tr>
                        <tr><td><code>style</code></td><td>grid, list, compact</td><td>grid</td><td>Display layout</td></tr>
                        <tr><td><code>columns</code></td><td>1-6</td><td>3</td><td>Grid columns</td></tr>
                        <tr><td><code>limit</code></td><td>Number</td><td>-1 (all)</td><td>Maximum number of items</td></tr>
                        <tr><td><code>title</code></td><td>Text</td><td>-</td><td>Section heading</td></tr>
                        <tr><td><code>show_empty</code></td><td>yes, no</td><td>no</td><td>Show "no downloads" message</td></tr>
                        <tr><td><code>orderby</code></td><td>date, title, menu_order</td><td>date</td><td>Sort field</td></tr>
                        <tr><td><code>order</code></td><td>ASC, DESC</td><td>DESC</td><td>Sort direction</td></tr>
                        <tr><td><code>class</code></td><td>CSS class</td><td>-</td><td>Additional CSS class</td></tr>
                    </table>
                </div>
            </details>
        </div>
        
        <!-- Archive Shortcode -->
        <div class="es-shortcode-block">
            <h5 class="es-shortcode-block__title">Downloads Archive (Grouped)</h5>
            <code class="es-shortcode-block__code">[ensemble_downloads_archive]</code>
            
            <div class="es-shortcode-examples">
                <div class="es-shortcode-ref__item">
                    <code>[ensemble_downloads_archive group_by="speaker" style="accordion"]</code>
                    <span>Grouped by speaker, accordion style</span>
                </div>
                <div class="es-shortcode-ref__item">
                    <code>[ensemble_downloads_archive group_by="type" style="tabs"]</code>
                    <span>Grouped by type with tab navigation</span>
                </div>
                <div class="es-shortcode-ref__item">
                    <code>[ensemble_downloads_archive group_by="event" events="10,20,30"]</code>
                    <span>Only specific events</span>
                </div>
            </div>
            
            <details class="es-shortcode-details">
                <summary>All parameters for [ensemble_downloads_archive]</summary>
                <div class="es-shortcode-params">
                    <table>
                        <tr><th>Parameter</th><th>Values</th><th>Default</th><th>Description</th></tr>
                        <tr><td><code>group_by</code></td><td>event, speaker, location, type</td><td>event</td><td>How to group downloads</td></tr>
                        <tr><td><code>style</code></td><td>accordion, tabs, list, grid</td><td>accordion</td><td>Display style</td></tr>
                        <tr><td><code>events</code></td><td>IDs (comma-separated)</td><td>-</td><td>Filter to specific events</td></tr>
                        <tr><td><code>speakers</code></td><td>IDs (comma-separated)</td><td>-</td><td>Filter to specific speakers</td></tr>
                        <tr><td><code>locations</code></td><td>IDs (comma-separated)</td><td>-</td><td>Filter to specific locations</td></tr>
                        <tr><td><code>types</code></td><td>Slugs (comma-separated)</td><td>-</td><td>Filter to specific types</td></tr>
                        <tr><td><code>show_empty</code></td><td>yes, no</td><td>no</td><td>Show groups with no downloads</td></tr>
                        <tr><td><code>show_count</code></td><td>yes, no</td><td>yes</td><td>Show download count per group</td></tr>
                        <tr><td><code>expanded</code></td><td>first, all, none</td><td>first</td><td>Initially expanded accordions</td></tr>
                        <tr><td><code>columns</code></td><td>1-6</td><td>3</td><td>Grid columns (for grid style)</td></tr>
                        <tr><td><code>title</code></td><td>Text</td><td>-</td><td>Archive heading</td></tr>
                        <tr><td><code>orderby</code></td><td>title, count</td><td>title</td><td>Group sort field</td></tr>
                        <tr><td><code>order</code></td><td>ASC, DESC</td><td>ASC</td><td>Group sort direction</td></tr>
                        <tr><td><code>class</code></td><td>CSS class</td><td>-</td><td>Additional CSS class</td></tr>
                    </table>
                </div>
            </details>
        </div>
    </div>
    
</div>

<style>
/* Settings Sections */
.es-downloads-settings {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.es-settings-section {
    background: var(--es-surface-secondary, #252525);
    border: 1px solid var(--es-border, #333);
    border-radius: var(--es-radius, 6px);
    padding: 20px;
}

.es-settings-section--info {
    background: transparent;
    border-style: dashed;
}

.es-settings-section__title {
    margin: 0 0 16px;
    font-size: 14px;
    font-weight: 600;
    color: var(--es-text, #e0e0e0);
}

.es-settings-section__desc {
    margin: -8px 0 16px;
    font-size: 13px;
    color: var(--es-text-muted, #888);
}

/* Settings Fields */
.es-settings-field {
    margin-bottom: 16px;
}

.es-settings-field:last-child {
    margin-bottom: 0;
}

.es-settings-field__label {
    display: block;
    margin-bottom: 6px;
    font-size: 13px;
    font-weight: 500;
    color: var(--es-text, #e0e0e0);
}

.es-settings-field__input,
.es-settings-field__select {
    width: 100%;
    padding: 8px 12px;
    background: var(--es-surface, #1e1e1e);
    border: 1px solid var(--es-border, #333);
    border-radius: 4px;
    color: var(--es-text, #e0e0e0);
    font-size: 13px;
}

.es-settings-field__input:focus,
.es-settings-field__select:focus {
    border-color: var(--es-primary, #3b82f6);
    outline: none;
}

/* Settings Row */
.es-settings-row {
    display: flex;
    gap: 16px;
}

.es-settings-field--half {
    flex: 1;
}

/* Toggle Switches */
.es-settings-toggles {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.es-settings-toggle {
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
}

.es-settings-toggle__switch {
    position: relative;
    flex-shrink: 0;
}

.es-settings-toggle__switch input {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}

.es-settings-toggle__track {
    display: block;
    width: 40px;
    height: 22px;
    background: var(--es-surface, #1e1e1e);
    border: 1px solid var(--es-border, #333);
    border-radius: 11px;
    position: relative;
    transition: all 0.2s ease;
}

.es-settings-toggle__track::after {
    content: '';
    position: absolute;
    top: 2px;
    left: 2px;
    width: 16px;
    height: 16px;
    background: var(--es-text-muted, #888);
    border-radius: 50%;
    transition: all 0.2s ease;
}

.es-settings-toggle__switch input:checked + .es-settings-toggle__track {
    background: var(--es-primary, #3b82f6);
    border-color: var(--es-primary, #3b82f6);
}

.es-settings-toggle__switch input:checked + .es-settings-toggle__track::after {
    left: 20px;
    background: #fff;
}

.es-settings-toggle__label {
    font-size: 13px;
    color: var(--es-text, #e0e0e0);
}

/* Subsections */
.es-settings-subsection {
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid var(--es-border, #333);
    transition: opacity 0.2s ease;
}

/* Hints */
.es-settings-hint {
    margin: 12px 0 0;
    font-size: 12px;
    color: var(--es-text-muted, #888);
    line-height: 1.5;
}

.es-settings-hint-inline {
    font-weight: 400;
    color: var(--es-text-muted, #888);
}

/* Checkboxes */
.es-settings-checkboxes {
    display: flex;
    flex-wrap: wrap;
    gap: 12px 20px;
    margin-top: 8px;
}

.es-settings-checkbox {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    color: var(--es-text, #e0e0e0);
    cursor: pointer;
}

.es-settings-checkbox input {
    margin: 0;
}

/* Type Colors */
.es-type-colors-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 12px;
}

.es-type-color-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 12px;
    background: var(--es-surface, #1e1e1e);
    border: 1px solid var(--es-border, #333);
    border-radius: 6px;
}

.es-type-color-input {
    width: 32px;
    height: 32px;
    padding: 0;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    flex-shrink: 0;
}

.es-type-color-input::-webkit-color-swatch-wrapper {
    padding: 0;
}

.es-type-color-input::-webkit-color-swatch {
    border: none;
    border-radius: 4px;
}

.es-type-color-label {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: var(--es-text, #e0e0e0);
}

.es-type-color-label .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
    color: var(--es-text-muted, #888);
}

.es-type-color-reset {
    background: none;
    border: none;
    color: var(--es-text-muted, #888);
    cursor: pointer;
    padding: 4px;
    opacity: 0.5;
    transition: opacity 0.2s;
}

.es-type-color-reset:hover {
    opacity: 1;
}

.es-type-color-reset .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

/* Shortcode Reference */
.es-shortcode-block {
    background: var(--es-surface-secondary, #252525);
    border: 1px solid var(--es-border, #333);
    border-radius: 6px;
    padding: 16px;
    margin-bottom: 16px;
}

.es-shortcode-block:last-child {
    margin-bottom: 0;
}

.es-shortcode-block__title {
    margin: 0 0 12px;
    font-size: 13px;
    font-weight: 600;
    color: var(--es-text, #e0e0e0);
}

.es-shortcode-block__code {
    display: block;
    padding: 10px 14px;
    background: var(--es-surface, #1e1e1e);
    border-radius: 4px;
    font-size: 13px;
    color: var(--es-primary, #3b82f6);
    margin-bottom: 12px;
}

.es-shortcode-examples {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.es-shortcode-ref__item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 6px 10px;
    background: var(--es-surface, #1e1e1e);
    border-radius: 4px;
}

.es-shortcode-ref__item code {
    background: transparent;
    padding: 0;
    font-size: 11px;
    color: var(--es-primary, #3b82f6);
    white-space: nowrap;
    flex-shrink: 0;
}

.es-shortcode-ref__item span {
    font-size: 11px;
    color: var(--es-text-muted, #888);
}

.es-shortcode-details {
    margin-top: 12px;
    border-top: 1px solid var(--es-border, #333);
    padding-top: 12px;
}

.es-shortcode-details summary {
    cursor: pointer;
    font-size: 12px;
    color: var(--es-primary, #3b82f6);
    padding: 4px 0;
}

.es-shortcode-details summary:hover {
    text-decoration: underline;
}

.es-shortcode-params {
    margin-top: 12px;
    overflow-x: auto;
}

.es-shortcode-params table {
    width: 100%;
    border-collapse: collapse;
    font-size: 11px;
    min-width: 500px;
}

.es-shortcode-params th,
.es-shortcode-params td {
    padding: 6px 10px;
    text-align: left;
    border-bottom: 1px solid var(--es-border, #333);
}

.es-shortcode-params th {
    background: var(--es-surface, #1e1e1e);
    font-weight: 600;
    color: var(--es-text, #e0e0e0);
    white-space: nowrap;
}

.es-shortcode-params td {
    color: var(--es-text-muted, #888);
}

.es-shortcode-params td:first-child {
    white-space: nowrap;
}

.es-shortcode-params code {
    background: var(--es-surface, #1e1e1e);
    padding: 2px 5px;
    border-radius: 3px;
    font-size: 10px;
    color: var(--es-primary, #3b82f6);
}

/* Responsive */
@media (max-width: 600px) {
    .es-settings-row {
        flex-direction: column;
    }
    
    .es-type-colors-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
jQuery(function($) {
    // Toggle require login options
    $('#downloads_require_login').on('change', function() {
        $('#downloads_roles_section').css({
            'opacity': this.checked ? '1' : '0.5',
            'pointer-events': this.checked ? 'auto' : 'none'
        });
    });
    
    // Toggle event options
    $('#downloads_auto_events').on('change', function() {
        $('#downloads_event_options').css({
            'opacity': this.checked ? '1' : '0.5',
            'pointer-events': this.checked ? 'auto' : 'none'
        });
    });
    
    // Toggle artist options
    $('#downloads_auto_artists').on('change', function() {
        $('#downloads_artist_options').css({
            'opacity': this.checked ? '1' : '0.5',
            'pointer-events': this.checked ? 'auto' : 'none'
        });
    });
    
    // Reset color to default
    $('.es-type-color-reset').on('click', function() {
        var $input = $(this).closest('.es-type-color-item').find('.es-type-color-input');
        $input.val($input.data('default'));
    });
});
</script>
