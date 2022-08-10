<?php
/**
 * mosparo for WordPress
 *
 * @package           MosparoWp
 * @author            mosparo
 * @copyright         2021-2022 mosparo
 * @license           MIT
 *
 * @wordpress-plugin
 * Plugin Name:       mosparo for WordPress
 * Plugin URI:        https://mosparo.io/integrations/wordpress/
 * Description:       Adds the ability to protect forms in WordPress with mosparo.
 * Author:            mosparo
 * Author URI:        https://mosparo.io/
 * License:           MIT
 * Version:           0.1.0
 * Text Domain:       mosparo-wp
 * Domain Path:       /languages
 */

use MosparoWp\Helper\AdminHelper;
use MosparoWp\Helper\ConfigHelper;
use MosparoWp\Helper\FrontendHelper;
use MosparoWp\Helper\ModuleHelper;

require_once(__DIR__ . '/src/autoload.php');
require_once(__DIR__ . '/vendor/autoload.php');

function mosparoWpInitialize()
{
    $configHelper = ConfigHelper::getInstance();

    $moduleHelper = ModuleHelper::getInstance();
    $moduleHelper->initializeActiveModules(plugin_dir_path(__FILE__), plugin_dir_url(__FILE__));

    $frontendHelper = FrontendHelper::getInstance();
    $frontendHelper->initializeScheduleEvents();

    if (!is_admin() && $configHelper->getLoadResourcesAlways()) {
        $frontendHelper->initializeResourceRegistration();
    }

    $adminHelper = AdminHelper::getInstance();
    $adminHelper->initializeAdmin(__DIR__, plugin_dir_url(__FILE__));
}
add_action('plugins_loaded', 'mosparoWpInitialize', 1);

function mosparoWpInitializeTextDomain()
{
    load_plugin_textdomain('mosparo-wp', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('init', 'mosparoWpInitializeTextDomain');

function mosparoWpActivatePlugin()
{
    if (!wp_next_scheduled('mosparo_wp_refresh_css_url_cache')) {
        wp_schedule_event(time(), 'daily', 'mosparo_wp_refresh_css_url_cache');
    }
}
register_activation_hook(__FILE__, 'mosparoWpActivatePlugin');

function mosparoWpDeactivatePlugin()
{
    wp_clear_scheduled_hook('mosparo_wp_refresh_css_url_cache');
}
register_deactivation_hook(__FILE__, 'mosparoWpDeactivatePlugin');
