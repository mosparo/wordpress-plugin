<?php

namespace MosparoIntegration\Helper;

use MosparoIntegration\Entity\Connection;
use MosparoIntegration\Module\AbstractModule;

class AdminHelper
{
    private static $instance;

    protected $pluginPath;

    protected $pluginUrl;

    public static function getInstance()
    {
        if (empty(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
    }

    public function get_bulk_action() {
        $action = false;

        if (isset($_REQUEST['action2'])) {
            $action = $_REQUEST['action2'];
        }
        return $action;
    }

    public function initializeAdmin($pluginPath, $pluginUrl, $pluginBasename)
    {
        $this->pluginPath = $pluginPath;
        $this->pluginUrl = $pluginUrl;

        add_filter('plugin_action_links_' . $pluginBasename, [$this, 'addPluginLink']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueStyles']);

        $actions = [
            'mosparo-refresh-css-cache' => [$this, 'actionRefreshCssCache'],
            'mosparo-add-connection' => [$this, 'actionSaveConnection'],
            'mosparo-edit-connection' => [$this, 'actionSaveConnection'],
            'mosparo-delete-connection' => [$this, 'actionDeleteConnection'],
            'mosparo-enable-module' => [$this, 'actionEnableModule'],
            'mosparo-disable-module' => [$this, 'actionDisableModule'],
            'mosparo-module-settings' => [$this, 'actionSaveModuleSettings'],
        ];

        $action_hook_prefix = 'admin_post_';

        if (is_multisite() && is_network_admin()) {
            $action_hook_prefix = 'network_admin_edit_';
            add_action('network_admin_menu', [$this, 'registerSubmenu']);
        } else {
            add_action('admin_menu', [$this, 'registerSubmenu']);
        }
        foreach ($actions as $action => $callback) {
            add_action($action_hook_prefix . $action, function() use ($action, $callback) {
                if (!$this->get_bulk_action()) {
                    check_admin_referer($action, 'mosparo-nonce');
                }
                $callback($action);
            });
        }
        $bulk_action_handler = function() use ($action_hook_prefix) {
            check_admin_referer('mosparo-settings-bulk-actions', 'mosparo-nonce');
            do_action($action_hook_prefix . $this->get_bulk_action());
        };
        add_action($action_hook_prefix.'mosparo-settings-bulk-actions', $bulk_action_handler);
    }

    function addPluginLink($links)
    {
        $link = '<a href="' . $this->buildConfigPageUrl() . '">' . __('Settings', 'mosparo-integration') . '</a>';

        array_unshift($links, $link);

        return $links;
    }

    public function registerSubmenu()
    {
        $parentSlug = 'options-general.php';
        if (is_multisite() && is_network_admin()) {
            $parentSlug = 'settings.php';
        }

        add_submenu_page(
            $parentSlug,
            __('mosparo Integration', 'mosparo-integration'),
            __('mosparo Integration', 'mosparo-integration'),
            'manage_options',
            'mosparo-configuration',
            [$this, 'displayConfiguration']
        );
    }

    public function enqueueStyles()
    {
        wp_enqueue_style(
            'mosparo-integration-admin-css',
            $this->pluginUrl . '/assets/css/mosparo-admin.css',
            [],
            '1.0'
        );
    }

    public function displayConfiguration($action = '')
    {
        if (!current_user_can( 'manage_options')) {
            wp_die(__('No access.', 'mosparo-integration'), __('mosparo Integration', 'mosparo-integration'));
        }

        $configHelper = ConfigHelper::getInstance();
        $action = sanitize_key($_REQUEST['action'] ?? '');
        if ($action === 'mosparo-add-connection') {
            $connection = new \MosparoIntegration\Entity\Connection();
            require_once($this->pluginPath . '/views/admin/connection-form.php');
        } else if ($action === 'mosparo-edit-connection') {
            $connectionKey = sanitize_key($_REQUEST['connection'] ?? '');
            if ($connectionKey === '' || !$configHelper->hasConnection($connectionKey) || !$configHelper->hasAccessToConnection($connectionKey)) {
                $this->redirectToSettingsPage();
                return;
            }

            $connection = $configHelper->getConnection($connectionKey);
            require_once($this->pluginPath . '/views/admin/connection-form.php');
        } else if ($action === 'mosparo-delete-connection') {
            $connectionKey = sanitize_key($_REQUEST['connection'] ?? '');
            if ($connectionKey === '' || !$configHelper->hasConnection($connectionKey) || !$configHelper->hasAccessToConnection($connectionKey)) {
                $this->redirectToSettingsPage();
                return;
            }
            $connection = $configHelper->getConnection($connectionKey);
            if ($configHelper->isConnectionDefaultConnectionFor($connection, 'general')) {
                $this->redirectToSettingsPage('general-locked');
                return;
            }
            require_once($this->pluginPath . '/views/admin/connection-delete.php');
        } else if ($action === 'mosparo-module-settings') {
            $moduleHelper = ModuleHelper::getInstance();
            $moduleKey = sanitize_key($_REQUEST['module'] ?? '');
            if (empty($moduleKey) || !$moduleHelper->getActiveModule($moduleKey)) {
                $this->redirectToSettingsPage();
                return;
            }
            $module = $moduleHelper->getActiveModule($moduleKey);
            require_once($this->pluginPath . '/views/admin/module-settings.php');
        } else {
            require_once($this->pluginPath . '/views/admin/settings.php');
        }
    }

    //Single & bulk action handle
    public function actionRefreshCssCache($action) {
        $connectionKeys = $_REQUEST['connection'] ?? '';
        if (!is_array($connectionKeys)) {
            $connectionKeys = [$connectionKeys];
        }

        if (!$connectionKeys) {
            $this->redirectToSettingsPage();
            return;
        }
        $configHelper = ConfigHelper::getInstance();

        foreach ($connectionKeys as $connectionKey) {
            if (!$configHelper->hasConnection($connectionKey)) {
                continue;
            }

            $connection = $configHelper->getConnection($connectionKey);

            $frontendHelper = FrontendHelper::getInstance();
            $frontendHelper->refreshCssUrlCache($connection);
        }

        $this->redirectToSettingsPage('css-cache-refreshed');
    }

    //Single & bulk action handle
    public function toggleEnableModule($enable) {
        if (!isset($_REQUEST['module'])) {
            $this->redirectToSettingsPage();
        }

        $configHelper = ConfigHelper::getInstance();
        $modules = $_REQUEST['module'];
        if (!is_array($modules)) {
            $modules = [$modules];
        }
        $modules = array_map('sanitize_key', $modules);
        $message = '';
        foreach ($modules as $module) {
            if ($enable) {
                $configHelper->enableModule($module);
                $message = 'enabled';
            } else {
                $configHelper->disableModule($module);
                $message = 'disabled';
            }
        }

        if (count($modules) > 1) {
            $message = 'multiple-' . $message;
        } else {
            $message = 'one-' . $message;
        }

        $configHelper->saveConfiguration();
        $this->redirectToSettingsPage($message);
    }

    public function actionEnableModule() {
        return $this->toggleEnableModule(true);
    }

    public function actionDisableModule() {
        return $this->toggleEnableModule(false);
    }

    // Add & Edit connection
    public function actionSaveConnection($action) {
        $configHelper = ConfigHelper::getInstance();

        if ($action === 'mosparo-add-connection') {
            $connection = new Connection();
            $connection->setKey(sanitize_key($_REQUEST['key']));

            // It's not allowed to use mc__wp_config as key since this is the key for the connection
            // defined in the wp-config.php
            if (strtolower($connection->getKey()) === 'mc__wp_config') {
                $connection->setKey($connection->getKey() . uniqid());
            }

            if ($connection->getKey() === '') {
                $connection->setKey(sanitize_key($_REQUEST['name']));
            }

            if (is_multisite() && is_network_admin()) {
                $connection->setOrigin(ConfigHelper::ORIGIN_NETWORK);

            } else {
                $connection->setOrigin(ConfigHelper::ORIGIN_LOCAL);
            }
        } else if ($action === 'mosparo-edit-connection') {
            $key = sanitize_key($_REQUEST['key']);

            if (!$key || !$configHelper->hasConnection($key)) {
                $this->redirectToSettingsPage();
                return;
            }

            $connection = $configHelper->getConnection($key);
        }

        $connection->setName(sanitize_text_field($_REQUEST['name']));
        $connection->setHost(sanitize_url($_REQUEST['host']));
        $connection->setUuid(sanitize_key($_REQUEST['uuid']));
        $connection->setPublicKey(sanitize_text_field($_REQUEST['publicKey']));

        $defaults = $_REQUEST['defaults'];
        if (!is_array($defaults)) {
            $defaults = [];
        }

        foreach ($defaults as $key => $default) {
            $defaults[$key] = (sanitize_key($default));
        }

        if (
            ($connection->isDefaultFor('general') && !in_array('general', $defaults)) ||
                $configHelper->getConnectionFor('general') === false
        ) {
            $defaults[] = 'general';
        }

        $configHelper->resetDefaultConnections($defaults);
        $connection->setDefaults($defaults);

        // Only update the private key if it is not empty
        if ($_REQUEST['privateKey'] !== '') {
            $connection->setPrivateKey(sanitize_text_field($_REQUEST['privateKey']));
        }

        $connection->setVerifySsl(boolval(sanitize_key($_REQUEST['verifySsl'] ?? false)));

        if ($action === 'mosparo-add-connection') {
            if ($configHelper->hasConnection($connection->getKey())) {
                $connection->setKey($connection->getKey() . '_' . uniqid());
            }
        }

        $configHelper->addConnection($connection);
        $configHelper->saveConfiguration();

        $frontendHelper = FrontendHelper::getInstance();
        $frontendHelper->clearCssUrlCache($connection);

        $this->redirectToSettingsPage('connection-saved');
    }

    public function actionDeleteConnection($action) {
        $configHelper = ConfigHelper::getInstance();

        $connectionKey = sanitize_key($_REQUEST['connection'] ?? '');
        if ($connectionKey === '' || !$configHelper->hasConnection($connectionKey)) {
            $this->redirectToSettingsPage();
            return;
        }

        $connection = $configHelper->getConnection($connectionKey);
        if ($configHelper->isConnectionDefaultConnectionFor($connection, 'general')) {
            $this->redirectToSettingsPage('general-locked');
            return;
        }

        $configHelper->deleteConnection($connection);
        $configHelper->saveConfiguration();
            
        $this->redirectToSettingsPage('connection-deleted');
    }

    public function actionSaveModuleSettings($action) {
        $configHelper = ConfigHelper::getInstance();
        $moduleHelper = ModuleHelper::getInstance();
        $moduleKey = sanitize_key($_REQUEST['module'] ?? '');
        if (empty($moduleKey) || !$moduleHelper->getActiveModule($moduleKey)) {
            $this->redirectToSettingsPage();
            return;
        }
        $module = $moduleHelper->getActiveModule($moduleKey);
        $configHelper->saveModuleConfiguration($module);

        $this->redirectToSettingsPage('module-settings-saved');
    }

    public function redirectToSettingsPage($message = null)
    {
        $args = [];
        if ($message !== null) {
            $args['message'] = $message;
        }

        wp_safe_redirect($this->buildConfigPageUrl($args));
        exit;
    }

    public function buildConfigPageUrl($args = [])
    {
        $defaultArgs = ['page' => 'mosparo-configuration'];
        $args = array_merge($defaultArgs, $args);

        $settingsUrl = admin_url('options-general.php');
        if (is_network_admin()) {
            $settingsUrl = network_admin_url('settings.php');
        }

        return add_query_arg($args, $settingsUrl);
    }

    public function buildConfigPostUrl($action = '')
    {
        $url = '';
        if (is_network_admin()) {
            $url = network_admin_url('edit.php');
        } else {
            $url = admin_url('admin-post.php');
        }
        $args = [];
        if ($action && is_string($action)) {
            $args['action'] = $action;
        } else if (is_array($action)) {
            $args = $action;
            $action = $args['action'];
        }
        return wp_nonce_url(add_query_arg($args, $url), $action, 'mosparo-nonce');
    }

    public function displayAdminNotice()
    {
        $message = sanitize_key($_GET['message'] ?? '');

        if ($message === 'invalid') {
            echo sprintf('<div class="notice notice-error"><p><strong>%1$s</strong>: %2$s</p></div>', esc_html(__('Error', 'mosparo-integration')), esc_html(__('Invalid connection data.', 'mosparo-integration')));
        } else if ($message === 'connection-saved') {
            echo sprintf('<div class="notice notice-success"><p>%s</p></div>', esc_html(__('The connection was successfully saved.', 'mosparo-integration')));
        } else if ($message === 'connection-deleted') {
            echo sprintf('<div class="notice notice-success"><p>%s</p></div>', esc_html(__('The connection was successfully deleted.', 'mosparo-integration')));
        } else if ($message === 'general-locked') {
            echo sprintf('<div class="notice notice-error"><p><strong>%1$s</strong>: %2$s</p></div>', esc_html(__('Error', 'mosparo-integration')), esc_html(__('You cannot delete the default connection.', 'mosparo-integration')));
        } else if ($message === 'multiple-enabled') {
            echo sprintf('<div class="notice notice-success"><p>%s</p></div>', esc_html(__('The modules were successfully enabled.', 'mosparo-integration')));
        } else if ($message === 'multiple-disabled') {
            echo sprintf('<div class="notice notice-success"><p>%s</p></div>', esc_html(__('The modules were successfully disabled.', 'mosparo-integration')));
        } else if ($message === 'one-enabled') {
            echo sprintf('<div class="notice notice-success"><p>%s</p></div>', esc_html(__('The module was successfully enabled.', 'mosparo-integration')));
        } else if ($message === 'one-disabled') {
            echo sprintf('<div class="notice notice-success"><p>%s</p></div>', esc_html(__('The module was successfully disabled.', 'mosparo-integration')));
        } else if ($message === 'css-cache-refreshed') {
            echo sprintf('<div class="notice notice-success"><p>%s</p></div>', esc_html(__('The CSS cache was refreshed successfully.', 'mosparo-integration')));
        } else if ($message === 'module-settings-saved') {
            echo sprintf('<div class="notice notice-success"><p>%s</p></div>', esc_html(__('The module configuration was successfully saved.', 'mosparo-integration')));
        }
    }

    public function displayHowToUseBox($withAddButton = true)
    {
        echo '<div class="mosparo-how-to-use-box">';
        echo '<h1>' . __('How to use', 'mosparo-integration') . '</h1>';
        echo '<p class="tight-text-box">' . __('To use the mosparo plugin, you need a connection to a mosparo project. Learn more about the next steps on our website.', 'mosparo-integration') . '</p>';
        echo '<a href="https://mosparo.io/how-to-use/" class="button button-primary" target="_blank">' . __('Read more', 'mosparo-integration') . '</a>';

        if ($withAddButton) {
            echo '<a href="' . esc_url($this->buildConfigPageUrl(['action' => 'mosparo-add-connection'])) . '" class="button button-secondary button-space-left">' . __('Add connection', 'mosparo-integration') . '</a>';
        }

        echo '</div>';
    }
}
