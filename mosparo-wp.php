<?php
/**
 * Mosparo for WordPress
 *
 * @package           MosparoWp
 * @author            mosparo
 * @copyright         2021 mosparo and contributors
 * @license           MIT
 *
 * @wordpress-plugin
 * Plugin Name:       Mosparo for WordPress
 * Plugin URI:        https://mosparo.io/integrations/wordpress/
 * Description:       Adds the ability to protect forms in WordPress with mosparo.
 * Version:           1.0.0
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

    if (!is_admin()) {
        $frontendHelper = FrontendHelper::getInstance();

        if ($configHelper->getLoadResourcesAlways()) {
            $frontendHelper->registerResources();
        }
    }

    $adminHelper = AdminHelper::getInstance();
    $adminHelper->initializeAdmin(__DIR__);
}
add_action('plugins_loaded', 'mosparoWpInitialize', 1);

