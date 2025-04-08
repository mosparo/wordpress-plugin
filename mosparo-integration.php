<?php
/**
 * mosparo Integration
 *
 * @package           MosparoIntegration
 * @author            mosparo Core Developers and contributors
 * @copyright         2021-2025 mosparo Core Developers and contributors
 * @license           MIT
 *
 * @wordpress-plugin
 * Plugin Name:       mosparo Integration
 * Plugin URI:        https://mosparo.io/integrations/wordpress/
 * Description:       Adds the ability to protect forms in WordPress with mosparo.
 * Author:            mosparo
 * Author URI:        https://mosparo.io/
 * License:           MIT
 * Version:           1.13.5
 * Text Domain:       mosparo-integration
 * Domain Path:       /languages
 */

use MosparoIntegration\Helper\AdminHelper;
use MosparoIntegration\Helper\ConfigHelper;
use MosparoIntegration\Helper\FrontendHelper;
use MosparoIntegration\Helper\ModuleHelper;

require_once(__DIR__ . '/src/autoload.php');
require_once(__DIR__ . '/vendor-prefixed/autoload.php');

function mosparoIntegrationInitialize()
{
    $configHelper = ConfigHelper::getInstance();

    $moduleHelper = ModuleHelper::getInstance();
    $moduleHelper->initializeActiveModules(plugin_dir_path(__FILE__), plugin_dir_url(__FILE__));

    $frontendHelper = FrontendHelper::getInstance();
    $frontendHelper->initializeScheduleEvents();

    $adminHelper = AdminHelper::getInstance();
    $adminHelper->initializeAdmin(__DIR__, plugin_dir_url(__FILE__), plugin_basename(__FILE__));
}
add_action('plugins_loaded', 'mosparoIntegrationInitialize', 1);

function mosparoIntegrationInitializeLate()
{
    // This second round is to initialize the modules that depend on a Theme instead of a plugin.
    $moduleHelper = ModuleHelper::getInstance();
    $moduleHelper->initializeActiveModules(plugin_dir_path(__FILE__), plugin_dir_url(__FILE__));
}
add_action('after_setup_theme', 'mosparoIntegrationInitializeLate', 1000);

function mosparoIntegrationInitializeTextDomain()
{
    load_plugin_textdomain('mosparo-integration', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('init', 'mosparoIntegrationInitializeTextDomain');

function mosparoIntegrationActivatePlugin()
{
    if (!wp_next_scheduled('mosparo_integration_refresh_css_url_cache')) {
        wp_schedule_event(time(), 'daily', 'mosparo_integration_refresh_css_url_cache');
    }
}
register_activation_hook(__FILE__, 'mosparoIntegrationActivatePlugin');

function mosparoIntegrationDeactivatePlugin()
{
    wp_clear_scheduled_hook('mosparo_integration_refresh_css_url_cache');
}
register_deactivation_hook(__FILE__, 'mosparoIntegrationDeactivatePlugin');
