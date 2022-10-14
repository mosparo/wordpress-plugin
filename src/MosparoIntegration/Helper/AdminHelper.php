<?php

namespace MosparoIntegration\Helper;

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

        require_once($this->pluginPath . '/views/admin/settings.php');
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

        if ($action === 'reset') {
            if (!isset($_REQUEST['_wpnonce']) || !wp_verify_nonce(sanitize_key($_REQUEST['_wpnonce']), 'reset-connection')) {
                wp_die(__('No access.', 'mosparo-integration'), __('mosparo Integration', 'mosparo-integration'));
            }

            $configHelper->resetConnectionSettings();
            $configHelper->saveConfiguration();

            $frontendHelper = FrontendHelper::getInstance();
            $frontendHelper->clearCssUrlCache();

            $this->redirectToSettingsPage('connection-reseted');
        } else if ($action === 'refresh_css_cache') {
            if (!isset($_REQUEST['_wpnonce']) || !wp_verify_nonce(sanitize_key($_REQUEST['_wpnonce']), 'refresh-css-cache')) {
                wp_die(__('No access.', 'mosparo-integration'), __('mosparo Integration', 'mosparo-integration'));
            }

            $frontendHelper = FrontendHelper::getInstance();
            $frontendHelper->refreshCssUrlCache();

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

        if (!$this->verifyNonce()) {
            return;
        }

        $configHelper = ConfigHelper::getInstance();
        if (!$configHelper->isActive()) {
            $host = trim(sanitize_url($_POST['host']), '/');
            if (!filter_var($host, FILTER_VALIDATE_URL)) {
                $this->redirectToSettingsPage('invalid');
                return;
            }

            $configHelper->setHost($host);

            $uuid = sanitize_text_field($_POST['uuid']);
            $configHelper->setUuid($uuid);

            $publicKey = sanitize_text_field($_POST['publicKey']);
            $configHelper->setPublicKey($publicKey);

            $privateKey = sanitize_text_field($_POST['privateKey']);
            $configHelper->setPrivateKey($privateKey);
        }

        // Save verify ssl
        if (!isset($_POST['verifySsl'])) {
            $configHelper->setVerifySsl(false);
        } else {
            $configHelper->setVerifySsl(true);
        }

        // Save load resources always
        if (!isset($_POST['loadResourcesAlways'])) {
            $configHelper->setLoadResourcesAlways(false);
        } else {
            $configHelper->setLoadResourcesAlways(true);
        }

        // Save load css resource on initialization
        if (!isset($_POST['loadCssResourceOnInitialization'])) {
            $configHelper->setLoadCssResourceOnInitialization(false);
        } else {
            $configHelper->setLoadCssResourceOnInitialization(true);
        }

        $configHelper->saveConfiguration();

        // Try to cache the resource url for the first time
        $frontendHelper = FrontendHelper::getInstance();
        $frontendHelper->refreshCssUrlCache();

        $this->redirectToSettingsPage('saved');
    }

    protected function verifyNonce()
    {
        if (!isset($_POST['save-settings'])) {
            return false;
        }

        $field  = wp_unslash(sanitize_key($_POST['save-settings']));
        $action = 'mosparo-settings-form';

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

    protected function getMaskedPrivateKey()
    {
        $configHelper = ConfigHelper::getInstance();
        if (!$configHelper->isActive()) {
            return '';
        }

        $privateKey = $configHelper->getPrivateKey();
        if (strlen($privateKey) < 12) {
            return str_repeat('*', strlen($privateKey));
        }
        $maskedPart = str_repeat('*', strlen($privateKey) - 8);
        $maskedKey = substr($privateKey, 0, 4) . $maskedPart . substr($privateKey, -4);

        return $maskedKey;
    }

    public function displayAdminNotice()
    {
        $message = sanitize_key($_GET['message'] ?? '');

        if ($message === 'invalid') {
            echo sprintf('<div class="notice notice-error"><p><strong>%1$s</strong>: %2$s</p></div>', esc_html(__('Error', 'mosparo-integration')), esc_html(__('Invalid connection data.', 'mosparo-integration')));
        } else if ($message === 'saved') {
            echo sprintf('<div class="notice notice-success"><p>%s</p></div>', esc_html(__('The settings were successfully saved.', 'mosparo-integration')));
        } else if ($message === 'multiple-enabled') {
            echo sprintf('<div class="notice notice-success"><p>%s</p></div>', esc_html(__('The modules were successfully enabled.', 'mosparo-integration')));
        } else if ($message === 'multiple-disabled') {
            echo sprintf('<div class="notice notice-success"><p>%s</p></div>', esc_html(__('The modules were successfully disabled.', 'mosparo-integration')));
        } else if ($message === 'one-enabled') {
            echo sprintf('<div class="notice notice-success"><p>%s</p></div>', esc_html(__('The module was successfully enabled.', 'mosparo-integration')));
        } else if ($message === 'one-disabled') {
            echo sprintf('<div class="notice notice-success"><p>%s</p></div>', esc_html(__('The module was successfully disabled.', 'mosparo-integration')));
        } else if ($message === 'connection-reseted') {
            echo sprintf('<div class="notice notice-success"><p>%s</p></div>', esc_html(__('The connection settings were reseted successfully.', 'mosparo-integration')));
        } else if ($message === 'css-cache-refreshed') {
            echo sprintf('<div class="notice notice-success"><p>%s</p></div>', esc_html(__('The CSS cache was refreshed successfully.', 'mosparo-integration')));
        }
    }
}