<?php

namespace MosparoIntegration\Helper;

use MosparoIntegration\Entity\Connection;
use WP_Error;

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

    protected function isBulkAction()
    {
        return (isset($_REQUEST['action']) && isset($_REQUEST['action2']) && $_REQUEST['action2'] !== '-1' && $_REQUEST['action'] === $_REQUEST['action2']);
    }

    protected function verifyNonce($action)
    {
        // Do not replace this `if-elseif-else` with a `if (isBulkAction)-else` or remember to verify
        // the `else` within the `if (isBulkAction)` to make sure that it's not possible to bypass the
        // nonce verification.
        if ($this->isBulkAction() && isset($_REQUEST['connection'])) {
            return check_admin_referer('bulk-connections');
        } else if ($this->isBulkAction() && isset($_REQUEST['module'])) {
            return check_admin_referer('bulk-modules');
        } else {
            return check_admin_referer($action, 'mosparo-nonce');
        }
    }

    public function initializeAdmin($pluginPath, $pluginUrl, $pluginBasename)
    {
        $this->pluginPath = $pluginPath;
        $this->pluginUrl = $pluginUrl;

        add_filter('plugin_action_links_' . $pluginBasename, [$this, 'addPluginLink']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueStylesAndScripts']);

        $actions = [
            'mosparo-refresh-css-cache' => [$this, 'actionRefreshCssCache'],
            'mosparo-add-connection' => [$this, 'actionSaveConnection'],
            'mosparo-edit-connection' => [$this, 'actionSaveConnection'],
            'mosparo-delete-connection' => [$this, 'actionDeleteConnection'],
            'mosparo-enable-module' => [$this, 'actionEnableModule'],
            'mosparo-disable-module' => [$this, 'actionDisableModule'],
            'mosparo-module-settings' => [$this, 'actionSaveModuleSettings'],
        ];

        $actionHookPrefix = 'admin_post_';

        if (is_multisite() && is_network_admin()) {
            $actionHookPrefix = 'network_admin_edit_';
            add_action('network_admin_menu', [$this, 'registerSubmenu']);
        } else {
            add_action('admin_menu', [$this, 'registerSubmenu']);
        }

        $helper = $this;
        foreach ($actions as $action => $callback) {
            add_action($actionHookPrefix . $action, function() use ($action, $callback, $helper) {
                $helper->verifyNonce($action);

                $callback($action);
            });
        }

        add_action($actionHookPrefix . 'mosparo-settings-bulk-actions', function() use ($actionHookPrefix, $helper) {
            // The $_GET['action'] parameter is always 'mosparo-settings-bulk-actions',
            // so we're always using the $_POST['action'] parameter.
            $action = $_POST['action'] ?? false;
            $connections = $_POST['connection'] ?? false;
            $modules = $_POST['module'] ?? false;
            if (!$action || $action === '-1' || (!$connections && !$modules)) {
                // Redirect back to the settings page if no action was chosen or no connection or module was selected
                $this->redirectToSettingsPage();
            }

            // If it's not a bulk action, let the other actions take over.
            if (!$helper->isBulkAction()) {
                return;
            }

            do_action($actionHookPrefix . $action);
        });
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

    public function enqueueStylesAndScripts()
    {
        wp_enqueue_style(
            'mosparo-integration-admin-css',
            $this->pluginUrl . '/assets/css/mosparo-admin.css',
            [],
            '1.0'
        );

        wp_enqueue_script(
            'mosparo-admin',
            $this->pluginUrl . '/assets/js/mosparo-admin.js',
            ['jquery',  'utils'],
            '1.0',
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

    // Single & bulk action handle
    public function actionRefreshCssCache($action)
    {
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
            $result = $frontendHelper->refreshCssUrlCache($connection);

            if ($result !== true && $result instanceof WP_Error) {
                $this->redirectToSettingsPage('refresh-css-cache-error', ['connection' => $connectionKey, 'error' => base64_encode(json_encode($result->get_error_messages()))]);
            }
        }

        $this->redirectToSettingsPage('css-cache-refreshed');
    }

    // Single & bulk action handle
    public function toggleEnableModule($enable)
    {
        if (!isset($_REQUEST['module'])) {
            $this->redirectToSettingsPage();
        }

        $configHelper = ConfigHelper::getInstance();
        $moduleKeys = $_REQUEST['module'];
        if (!is_array($moduleKeys)) {
            $moduleKeys = [$moduleKeys];
        }

        $moduleHelper = ModuleHelper::getInstance();

        $moduleKeys = array_map('sanitize_key', $moduleKeys);
        $messageChangeType = '';
        $numberOfAdjustedModules = 0;
        foreach ($moduleKeys as $moduleKey) {
            $module = $moduleHelper->getModule($moduleKey);
            if (!$module) {
                continue;
            }

            if ($enable) {
                if (!$module->canInitialize() || $configHelper->isModuleActive($moduleKey)) {
                    continue;
                }

                $configHelper->enableModule($moduleKey);
                $messageChangeType = 'enabled';
            } else {
                if (!$configHelper->isModuleActive($moduleKey)) {
                    continue;
                }

                $configHelper->disableModule($moduleKey);
                $messageChangeType = 'disabled';
            }

            $numberOfAdjustedModules++;
        }

        // Show no message if no module was changed
        $message = null;
        if ($numberOfAdjustedModules === 1) {
            $message = 'one-' . $messageChangeType;
        } else if ($numberOfAdjustedModules > 1) {
            $message = 'multiple-' . $messageChangeType;
        }

        $configHelper->saveConfiguration();
        $this->redirectToSettingsPage($message);
    }

    public function actionEnableModule()
    {
        $this->toggleEnableModule(true);
    }

    public function actionDisableModule()
    {
        $this->toggleEnableModule(false);
    }

    // Add & Edit connection
    public function actionSaveConnection($action)
    {
        $configHelper = ConfigHelper::getInstance();

        if ($action === 'mosparo-add-connection') {
            $connection = new Connection();
            $connection->setKey(sanitize_key($_POST['key']));

            // It's not allowed to use mc__wp_config as key since this is the key for the connection
            // defined in the wp-config.php
            if (strtolower($connection->getKey()) === 'mc__wp_config') {
                $connection->setKey($connection->getKey() . uniqid());
            }

            if ($connection->getKey() === '') {
                $connection->setKey(sanitize_key($_POST['name']));
            }

            if (is_multisite() && is_network_admin()) {
                $connection->setOrigin(ConfigHelper::ORIGIN_NETWORK);

            } else {
                $connection->setOrigin(ConfigHelper::ORIGIN_LOCAL);
            }
        } else if ($action === 'mosparo-edit-connection') {
            $key = sanitize_key($_POST['key']);

            if (!$key || !$configHelper->hasConnection($key)) {
                $this->redirectToSettingsPage();
                return;
            }

            $connection = $configHelper->getConnection($key);
        }

        $connection->setName(sanitize_text_field($_POST['name']));
        $connection->setHost(sanitize_url($_POST['host']));
        $connection->setUuid(sanitize_key($_POST['uuid']));
        $connection->setPublicKey(sanitize_text_field($_POST['publicKey']));

        $defaults = $_POST['defaults'];
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
        if ($_POST['privateKey'] !== '') {
            $connection->setPrivateKey(sanitize_text_field($_POST['privateKey']));
        }

        $connection->setVerifySsl(boolval(sanitize_key($_POST['verifySsl'] ?? false)));

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

    public function actionDeleteConnection($action)
    {
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

    public function actionSaveModuleSettings($action)
    {
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

    public function redirectToSettingsPage($message = null, array $args = [])
    {
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

    public function buildConfigPostUrl($action = '', $addNonce = true)
    {
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

        if (!$action || !$addNonce) {
            return add_query_arg($args, $url);
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
        } else if ($message === 'refresh-css-cache-error') {
            $configHelper = ConfigHelper::getInstance();
            $connectionKey = $_GET['connection'] ?? null;
            $connection = $configHelper->getConnection($connectionKey);
            $connectionName = '-';
            if ($connection instanceof Connection) {
                $connectionName = $connection->getName();
            }

            $error = '-';
            $errorMessages = json_decode(base64_decode($_GET['error'] ?? ''));
            if ($errorMessages) {
                $error = implode(' ', $errorMessages);
            }

            echo sprintf(
                '<div class="notice notice-error"><p><strong>%1$s</strong>: %2$s<br><strong>%3$s</strong>: %4$s<br><strong>%5$s</strong>: %6$s</p></div>',
                esc_html(__('Error', 'mosparo-integration')),
                esc_html(__('An error occurred while refreshing the CSS cache.', 'mosparo-integration')),
                esc_html(__('Connection', 'mosparo-integration')),
                esc_html($connectionName),
                esc_html(__('Error message', 'mosparo-integration')),
                esc_html($error)
            );
        } else if ($message === 'module-settings-saved') {
            echo sprintf('<div class="notice notice-success"><p>%s</p></div>', esc_html(__('The module settings were successfully saved.', 'mosparo-integration')));
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
