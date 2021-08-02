<?php

namespace MosparoWp\Helper;

class AdminHelper
{
    private static $instance;

    protected $pluginPath;

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

    public function initializeAdmin($pluginPath)
    {
        $this->pluginPath = $pluginPath;

        add_action('admin_menu', [$this, 'registerSubmenu']);
        add_action('admin_post', [$this, 'saveSettings']);
        add_action('admin_init', [$this, 'executeAction']);
    }

    public function registerSubmenu()
    {
        add_submenu_page(
            'options-general.php',
            __('mosparo for WordPress', 'mosparo-wp'),
            __('mosparo', 'mosparo-wp'),
            'manage_options',
            'mosparo-configuration',
            array($this, 'displayConfiguration')
        );
    }

    public function displayConfiguration($action = '')
    {
        if (!current_user_can( 'manage_options')) {
            wp_die(__('No access.', 'mosparo-wp'), __('mosparo for WordPress', 'mosparo-wp'));
        }

        require_once($this->pluginPath . '/views/admin/settings.php');
    }

    public function executeAction()
    {
        if (!current_user_can( 'manage_options')) {
            wp_die(__('No access.', 'mosparo-wp'), __('mosparo for WordPress', 'mosparo-wp'));
        }

        if (!isset($_REQUEST['action']) || $_REQUEST['action'] === '') {
            return;
        }

        $configHelper = ConfigHelper::getInstance();
        if (isset($_REQUEST['action'])) {
            $action = $_REQUEST['action'];

            if ($action === 'reset') {
                $configHelper->resetConnectionSettings();
                $configHelper->saveConfiguration();

                $this->redirectToSettingsPage();
            } else if ($action === 'enable' || $action === 'disable') {
                if (!isset($_REQUEST['_wpnonce']) || (!wp_verify_nonce($_REQUEST['_wpnonce'], 'bulk-modules') && !wp_verify_nonce($_REQUEST['_wpnonce'], 'change-module'))) {
                    wp_die(__('No access.', 'mosparo-wp'), __('mosparo for WordPress', 'mosparo-wp'));
                }

                if (!isset($_REQUEST['module']) && !isset($_REQUEST['module'])) {
                    $this->redirectToSettingsPage();
                }

                $modules = $_REQUEST['module'];
                if (!is_array($modules)) {
                    $modules = [$modules];
                }

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
    }

    public function saveSettings()
    {
        if (!current_user_can( 'manage_options')) {
            wp_die(__('No access.', 'mosparo-wp'), __('mosparo for WordPress', 'mosparo-wp'));
        }

        if (!$this->verifyNonce()) {
            return;
        }

        $configHelper = ConfigHelper::getInstance();
        if (!$configHelper->isActive()) {
            $host = sanitize_text_field($_POST['host']);
            $configHelper->setHost($host);

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

        $configHelper->saveConfiguration();

        $this->redirectToSettingsPage('saved');
    }

    protected function verifyNonce()
    {
        if (!isset($_POST['save-settings'])) {
            return false;
        }

        $field  = wp_unslash($_POST['save-settings']);
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
        $maskedPart = str_repeat('*', strlen($privateKey) - 8);
        $maskedKey = substr($privateKey, 0, 4) . $maskedPart . substr($privateKey, -4);

        return $maskedKey;
    }

    public function displayAdminNotice()
    {
        $message = $_GET['message'] ?? '';

        if ($message === 'invalid') {
            echo sprintf('<div class="notice notice-error"><p><strong>%1$s</strong>: %2$s</p></div>', esc_html(__('Error', 'mosparo-wp')), esc_html(__('Invalid connection data.', 'mosparo-wp')));
        } else if ($message === 'saved') {
            echo sprintf('<div class="notice notice-success"><p>%s</p></div>', esc_html(__('The settings were successfully saved.', 'mosparo-wp')));
        } else if ($message === 'multiple-enabled') {
            echo sprintf('<div class="notice notice-success"><p>%s</p></div>', esc_html(__('The modules were successfully enabled.', 'mosparo-wp')));
        } else if ($message === 'multiple-disabled') {
            echo sprintf('<div class="notice notice-success"><p>%s</p></div>', esc_html(__('The modules were successfully disabled.', 'mosparo-wp')));
        } else if ($message === 'one-enabled') {
            echo sprintf('<div class="notice notice-success"><p>%s</p></div>', esc_html(__('The module was successfully enabled.', 'mosparo-wp')));
        } else if ($message === 'one-disabled') {
            echo sprintf('<div class="notice notice-success"><p>%s</p></div>', esc_html(__('The module was successfully disabled.', 'mosparo-wp')));
        }
    }
}