<?php
/**
 * Templates Tab
 * 
 * Design templates selection and preview
 * 
 * @package Ensemble
 * @since 2.9.3
 */

if (!defined('ABSPATH')) exit;

// Handle template activation
if (isset($_POST['activate_template']) && check_admin_referer('es_activate_template')) {
    $template_name = sanitize_text_field($_POST['template_name']);
    if (ES_Design_Settings::load_template($template_name)) {
        echo '<div class="notice notice-success is-dismissible"><p>' . 
             sprintf(__('Template "%s" has been successfully activated!', 'ensemble'), $template_name) . 
             '</p></div>';
    }
}

$active_template = ES_Design_Settings::get_active_template();
$all_templates = ES_Design_Templates::get_all_templates();
$template_names = ES_Design_Templates::get_template_names();
?>

<div class="es-templates-section">
    
    <div class="es-section-intro">
        <h2><?php _e('Design Templates', 'ensemble'); ?></h2>
        <p class="es-description">
            <?php _e('Choose a pre-made design template for your events. All templates work with all shortcodes.', 'ensemble'); ?>
        </p>
    </div>
    
    <div class="es-templates-grid">
        
        <?php foreach ($all_templates as $template_id => $template): ?>
        <div class="es-template-card <?php echo $active_template === $template_id ? 'active' : ''; ?>">
            <div class="es-template-header">
                <h3><?php echo esc_html($template['name']); ?></h3>
                <?php if ($active_template === $template_id): ?>
                <span class="es-badge-active"><?php _e('Aktiv', 'ensemble'); ?></span>
                <?php endif; ?>
            </div>
            
            <div class="es-template-preview">
                <!-- Visual color preview instead of placeholder -->
                <div class="es-template-colors-large" style="
                    display: grid;
                    grid-template-columns: repeat(3, 1fr);
                    gap: 8px;
                    padding: 20px;
                    background: <?php echo esc_attr($template['settings']['card_background']); ?>;
                    border-radius: <?php echo esc_attr($template['settings']['card_radius']); ?>px;
                ">
                    <div style="
                        height: 60px;
                        background: <?php echo esc_attr($template['settings']['primary_color']); ?>;
                        border-radius: 8px;
                    "></div>
                    <div style="
                        height: 60px;
                        background: <?php echo esc_attr($template['settings']['secondary_color']); ?>;
                        border-radius: 8px;
                    "></div>
                    <div style="
                        height: 60px;
                        background: <?php echo esc_attr($template['settings']['button_bg']); ?>;
                        border-radius: 8px;
                    "></div>
                </div>
            </div>
            
            <div class="es-template-description">
                <p><?php echo esc_html($template['description']); ?></p>
            </div>
            
            <div class="es-template-colors">
                <span class="es-color-dot" style="background: <?php echo esc_attr($template['settings']['primary_color']); ?>"></span>
                <span class="es-color-dot" style="background: <?php echo esc_attr($template['settings']['secondary_color']); ?>"></span>
                <span class="es-color-dot" style="background: <?php echo esc_attr($template['settings']['button_bg']); ?>"></span>
            </div>
            
            <?php if ($active_template !== $template_id): ?>
            <form method="post" class="es-template-action">
                <?php wp_nonce_field('es_activate_template'); ?>
                <input type="hidden" name="template_name" value="<?php echo esc_attr($template_id); ?>">
                <button type="submit" name="activate_template" class="es-button-primary">
                    <?php _e('Aktivieren', 'ensemble'); ?>
                </button>
            </form>
            <?php else: ?>
            <div class="es-template-action">
                <span class="es-active-indicator">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <?php _e('This template is active', 'ensemble'); ?>
                </span>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        
    </div>
    
    <div class="es-templates-info">
        <div class="es-info-box">
            <h3>
                <span class="dashicons dashicons-info"></span>
                <?php _e('Wie funktionieren Templates?', 'ensemble'); ?>
            </h3>
            <ul>
                <li><?php _e('Choose a template and click "Activate"', 'ensemble'); ?></li>
                <li><?php _e('Das Design wird <strong>automatisch</strong> auf alle Ensemble-Shortcodes angewendet', 'ensemble'); ?></li>
                <li><?php _e('You don\'t need to change <strong>anything</strong> in the shortcode - the template is active immediately', 'ensemble'); ?></li>
                <li><?php _e('Du kannst jederzeit zu einem anderen Template wechseln', 'ensemble'); ?></li>
                <li><?php _e('Im <strong>Designer Tab</strong> kannst du jedes Detail des aktiven Templates anpassen', 'ensemble'); ?></li>
            </ul>
        </div>
        
        <div class="es-info-box">
            <h3>
                <span class="dashicons dashicons-admin-tools"></span>
                <?php _e('Import / Export', 'ensemble'); ?>
            </h3>
            <p><?php _e('Exportiere deine aktuellen Design-Einstellungen oder importiere ein gespeichertes Design.', 'ensemble'); ?></p>
            <div class="es-import-export-buttons">
                <button class="es-button-secondary" onclick="exportDesignSettings()">
                    <span class="dashicons dashicons-download"></span>
                    <?php _e('Export', 'ensemble'); ?>
                </button>
                <button class="es-button-secondary" onclick="document.getElementById('import-file').click()">
                    <span class="dashicons dashicons-upload"></span>
                    <?php _e('Import', 'ensemble'); ?>
                </button>
                <input type="file" id="import-file" accept=".json" style="display: none;" onchange="importDesignSettings(this)">
            </div>
        </div>
    </div>
    
</div>

<style>
/* ========================================
   TEMPLATES TAB STYLES
   ======================================== */

.es-templates-section {
    padding: 30px;
}

.es-templates-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 30px;
    margin: 40px 0;
}

.es-template-card {
    background: var(--es-card-bg, #2a2a2a);
    border: 2px solid var(--es-border, #3a3a3a);
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s ease;
}

.es-template-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
}

.es-template-card.active {
    border-color: var(--es-primary, #667eea);
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
}

.es-template-header {
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid var(--es-border, #3a3a3a);
}

.es-template-header h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
    color: var(--es-text, #e0e0e0);
}

.es-badge-active {
    background: var(--es-success, #10b981);
    color: white;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.es-template-preview {
    position: relative;
    height: 200px;
    overflow: hidden;
}

.es-template-preview-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.es-preview-sample {
    width: 100%;
    max-width: 200px;
}

.es-preview-card {
    text-align: center;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.es-preview-title {
    font-size: 14px;
    font-weight: 700;
    margin-bottom: 8px;
}

.es-preview-meta {
    font-size: 11px;
    margin-bottom: 12px;
}

.es-preview-button {
    border: none;
    cursor: pointer;
    font-size: 12px;
    font-weight: 600;
    width: 100%;
}

.es-template-description {
    padding: 20px;
    border-bottom: 1px solid var(--es-border, #3a3a3a);
}

.es-template-description p {
    margin: 0;
    font-size: 14px;
    color: var(--es-text-secondary, #a0a0a0);
    line-height: 1.6;
}

.es-template-colors {
    padding: 15px 20px;
    display: flex;
    gap: 8px;
    border-bottom: 1px solid var(--es-border, #3a3a3a);
}

.es-color-dot {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    border: 2px solid var(--es-border, #3a3a3a);
}

.es-template-action {
    padding: 20px;
}

.es-button-primary {
    width: 100%;
    padding: 12px 24px;
    background: var(--es-primary, #667eea);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.es-button-primary:hover {
    background: var(--es-primary-hover, #5568d3);
    transform: scale(1.02);
}

.es-button-secondary {
    padding: 10px 20px;
    background: var(--es-card-bg, #2a2a2a);
    color: var(--es-text, #e0e0e0);
    border: 1px solid var(--es-border, #3a3a3a);
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.es-button-secondary:hover {
    background: var(--es-hover-bg, #3a3a3a);
}

.es-active-indicator {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    color: var(--es-success, #10b981);
    font-weight: 600;
    font-size: 14px;
}

.es-templates-info {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin-top: 40px;
}

.es-info-box {
    background: var(--es-card-bg, #2a2a2a);
    border: 1px solid var(--es-border, #3a3a3a);
    border-radius: 12px;
    padding: 24px;
}

.es-info-box h3 {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0 0 16px 0;
    font-size: 16px;
    font-weight: 600;
    color: var(--es-text, #e0e0e0);
}

.es-info-box ul {
    margin: 0;
    padding-left: 20px;
}

.es-info-box li {
    margin-bottom: 8px;
    color: var(--es-text-secondary, #a0a0a0);
    line-height: 1.6;
}

.es-info-box p {
    color: var(--es-text-secondary, #a0a0a0);
    line-height: 1.6;
    margin-bottom: 16px;
}

.es-import-export-buttons {
    display: flex;
    gap: 12px;
}

@media (max-width: 1024px) {
    .es-templates-grid {
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    }
    
    .es-templates-info {
        grid-template-columns: 1fr;
    }
}


</style>

<script>
function exportDesignSettings() {
    // Get current settings via AJAX
    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=es_export_design_settings&_wpnonce=<?php echo wp_create_nonce('es_export_design'); ?>'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Create download
            const blob = new Blob([data.data.json], {type: 'application/json'});
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'ensemble-design-' + new Date().toISOString().split('T')[0] + '.json';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        } else {
            alert('Export fehlgeschlagen: ' + data.data.message);
        }
    });
}

function importDesignSettings(input) {
    const file = input.files[0];
    if (!file) return;
    
    const reader = new FileReader();
    reader.onload = function(e) {
        const json = e.target.result;
        
        // Import via AJAX
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=es_import_design_settings&_wpnonce=<?php echo wp_create_nonce('es_import_design'); ?>&json=' + encodeURIComponent(json)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Design erfolgreich importiert!');
                location.reload();
            } else {
                alert('Import fehlgeschlagen: ' + data.data.message);
            }
        });
    };
    reader.readAsText(file);
}

    function showNotice(message, type) {
        const $notice = $('<div>')
            .addClass('notice notice-' + type + ' is-dismissible')
            .html('<p>' + message + '</p>')
            .css({
                'position': 'fixed',
                'top': '32px',
                'right': '20px',
                'z-index': '999999',
                'min-width': '300px',
                'box-shadow': '0 4px 12px rgba(0,0,0,0.15)'
            });
        
        $('body').append($notice);
        
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
    }
});

</script>
