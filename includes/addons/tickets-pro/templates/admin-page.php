<?php
/**
 * Tickets Pro Admin Page
 * 
 * Main admin page with tabs for Overview, Categories, Gateways, Settings
 * Uses unified admin CSS classes for consistent styling
 *
 * @package Ensemble
 * @subpackage Addons/TicketsPro
 * @since 3.1.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'overview';
$addon = ES_Tickets_Pro();

// Define tabs
$tabs = array(
    'overview'   => array(
        'title' => __('Overview', 'ensemble'),
        'icon'  => 'dashicons-chart-area',
    ),
    'tickets' => array(
        'title' => __('All Tickets', 'ensemble'),
        'icon'  => 'dashicons-list-view',
    ),
    'categories' => array(
        'title' => __('Templates', 'ensemble'),
        'icon'  => 'dashicons-tickets-alt',
    ),
    'gateways'   => array(
        'title' => __('Payment Gateways', 'ensemble'),
        'icon'  => 'dashicons-money-alt',
    ),
    'settings'   => array(
        'title' => __('Settings', 'ensemble'),
        'icon'  => 'dashicons-admin-settings',
    ),
);
?>
<div class="es-manager-wrap">
    
    <!-- Page Title -->
    <h1>
        <span class="dashicons dashicons-tickets-alt"></span>
        <?php _e('Tickets Pro', 'ensemble'); ?>
    </h1>
    
    <!-- Tab Navigation -->
    <div class="es-tabs">
        <?php foreach ($tabs as $tab_id => $tab): ?>
        <a href="<?php echo esc_url(add_query_arg('tab', $tab_id, remove_query_arg(array('gateway', 'action', 'id')))); ?>" 
           class="es-tab <?php echo $current_tab === $tab_id ? 'active' : ''; ?>">
            <span class="dashicons <?php echo esc_attr($tab['icon']); ?>"></span>
            <?php echo esc_html($tab['title']); ?>
        </a>
        <?php endforeach; ?>
    </div>
    
    <!-- Tab Content -->
    <div class="es-tab-content">
        <?php
        $template_path = $addon->get_addon_path() . 'templates/';
        
        switch ($current_tab) {
            case 'tickets':
                if (file_exists($template_path . 'admin-tickets.php')) {
                    include $template_path . 'admin-tickets.php';
                }
                break;
                
            case 'categories':
                if (file_exists($template_path . 'admin-categories.php')) {
                    include $template_path . 'admin-categories.php';
                }
                break;
                
            case 'gateways':
                if (file_exists($template_path . 'admin-gateways.php')) {
                    include $template_path . 'admin-gateways.php';
                }
                break;
                
            case 'settings':
                if (file_exists($template_path . 'admin-settings.php')) {
                    include $template_path . 'admin-settings.php';
                }
                break;
                
            case 'overview':
            default:
                if (file_exists($template_path . 'admin-overview.php')) {
                    include $template_path . 'admin-overview.php';
                }
                break;
        }
        ?>
    </div>
    
</div>
