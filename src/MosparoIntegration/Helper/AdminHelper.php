<?php

namespace MosparoIntegration\Helper;

use MosparoIntegration\Entity\Connection;

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

    public function initializeAdmin($pluginPath, $pluginUrl)
    {
        $this->pluginPath = $pluginPath;
        $this->pluginUrl = $pluginUrl;

        add_action('admin_menu', [$this, 'registerSubmenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueStyles']);
        add_action('admin_post', [$this, 'saveSettings']);
        add_action('admin_init', [$this, 'executeAction']);
    }

    public function registerSubmenu()
    {
        add_submenu_page(
            'options-general.php',
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
        if ($action === 'add-connection') {
            $connection = new \MosparoIntegration\Entity\Connection();
            require_once($this->pluginPath . '/views/admin/connection-form.php');
        } else if ($action === 'edit-connection') {
            $connectionKey = sanitize_key($_REQUEST['connection'] ?? '');
            if ($connectionKey === '' || !$configHelper->hasConnection($connectionKey)) {
                $this->redirectToSettingsPage();
                return;
            }

            $connection = $configHelper->getConnection($connectionKey);
            require_once($this->pluginPath . '/views/admin/connection-form.php');
        } else if ($action === 'delete-connection') {
            $connectionKey = sanitize_key($_REQUEST['connection'] ?? '');
            if ($connectionKey === '' || !$configHelper->hasConnection($connectionKey)) {
                $this->redirectToSettingsPage();
                return;
            }

            $connection = $configHelper->getConnection($connectionKey);
            if ($connection->isDefaultFor('general')) {
                $this->redirectToSettingsPage('general-locked');
                return;
            }

            require_once($this->pluginPath . '/views/admin/connection-delete.php');
        } else {
            require_once($this->pluginPath . '/views/admin/settings.php');
        }
    }

    public function executeAction()
    {
        if (!current_user_can( 'manage_options')) {
            return;
        }

        if (!isset($_REQUEST['action']) || trim($_REQUEST['action']) === '') {
            return;
        }

        $configHelper = ConfigHelper::getInstance();
        $action = sanitize_key($_REQUEST['action']);

        if ($action === 'refresh-css-cache') {
            if (!isset($_REQUEST['_wpnonce']) || (!wp_verify_nonce(sanitize_key($_REQUEST['_wpnonce']), 'bulk-connections') && !wp_verify_nonce(sanitize_key($_REQUEST['_wpnonce']), 'refresh-css-cache'))) {
                wp_die(__('No access.', 'mosparo-integration'), __('mosparo Integration', 'mosparo-integration'));
            }

            $connectionKeys = $_REQUEST['connection'] ?? '';
            if (!is_array($connectionKeys)) {
                $connectionKeys = [$connectionKeys];
            }

            if (!$connectionKeys) {
                $this->redirectToSettingsPage();
                return;
            }

            foreach ($connectionKeys as $connectionKey) {
                if (!$configHelper->hasConnection($connectionKey)) {
                    continue;
                }

                $connection = $configHelper->getConnection($connectionKey);

                $frontendHelper = FrontendHelper::getInstance();
                $frontendHelper->refreshCssUrlCache($connection);
            }

            $this->redirectToSettingsPage('css-cache-refreshed');
        } else if ($action === 'enable' || $action === 'disable') {
            if (!isset($_REQUEST['_wpnonce']) || (!wp_verify_nonce(sanitize_key($_REQUEST['_wpnonce']), 'bulk-modules') && !wp_verify_nonce(sanitize_key($_REQUEST['_wpnonce']), 'change-module'))) {
                wp_die(__('No access.', 'mosparo-integration'), __('mosparo Integration', 'mosparo-integration'));
            }

            if (!isset($_REQUEST['module'])) {
                $this->redirectToSettingsPage();
            }

            $modules = $_REQUEST['module'];
            if (!is_array($modules)) {
                $modules = [$modules];
            }
            $modules = array_map('sanitize_key', $modules);

            $message = '';
            foreach ($modules as $module) {
                if ($action === 'enable') {
                    $configHelper->enableModule($module);
                    $message = 'enabled';
                } else if ($action === 'disable') {
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
    }

    public function saveSettings()
    {
        if (!current_user_can( 'manage_options')) {
            return;
        }

        $action = sanitize_key($_POST['mosparo_action'] ?? '');
        if (!$this->verifyNonce($action)) {
            return;
        }

        $configHelper = ConfigHelper::getInstance();

        if ($action === 'add-connection' || $action === 'edit-connection') {
            if ($action === 'add-connection') {
                $connection = new Connection();
                $connection->setKey(sanitize_key($_REQUEST['key']));

                if ($connection->getKey() === '') {
                    $connection->setKey(sanitize_key($_REQUEST['name']));
                }
            } else if ($action === 'edit-connection') {
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

            if ($action === 'add-connection') {
                if ($configHelper->hasConnection($connection->getKey())) {
                    $connection->setKey($connection->getKey() . '_' . uniqid());
                }
            }

            $configHelper->addConnection($connection);
            $configHelper->saveConfiguration();

            $frontendHelper = FrontendHelper::getInstance();
            $frontendHelper->clearCssUrlCache($connection);

            $this->redirectToSettingsPage('connection-saved');
        } else if ($action === 'delete-connection') {
            $connectionKey = sanitize_key($_REQUEST['connection'] ?? '');
            if ($connectionKey === '' || !$configHelper->hasConnection($connectionKey)) {
                $this->redirectToSettingsPage();
                return;
            }

            $connection = $configHelper->getConnection($connectionKey);
            if ($connection->isDefaultFor('general')) {
                $this->redirectToSettingsPage('general-locked');
                return;
            }

            $configHelper->deleteConnection($connection);
            $configHelper->saveConfiguration();

            $this->redirectToSettingsPage('connection-deleted');
        } else {
            $this->redirectToSettingsPage();
        }
    }

    protected function verifyNonce($action)
    {
        if (!isset($_POST['save-connection'])) {
            return false;
        }

        $field  = wp_unslash(sanitize_key($_POST['save-connection']));

        return wp_verify_nonce($field, $action);
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

        return add_query_arg($args, admin_url('options-general.php'));
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
        }
    }

    public function displayHowToUseBox($withAddButton = true)
    {
        echo '<div class="mosparo-how-to-use-box">';
        echo '<h1>' . __('How to use', 'mosparo-integration') . '</h1>';
        echo '<p class="tight-text-box">' . __('To use the mosparo plugin, you need a connection to a mosparo project. Learn more about the next steps on our website.', 'mosparo-integration') . '</p>';
        echo '<a href="https://mosparo.io/how-to-use/" class="button button-primary" target="_blank">' . __('Read more', 'mosparo-integration') . '</a>';

        if ($withAddButton) {
            echo '<a href="' . esc_url($this->buildConfigPageUrl(['action' => 'add-connection'])) . '" class="button button-secondary button-space-left">' . __('Add connection', 'mosparo-integration') . '</a>';
        }

        echo '</div>';
    }
}