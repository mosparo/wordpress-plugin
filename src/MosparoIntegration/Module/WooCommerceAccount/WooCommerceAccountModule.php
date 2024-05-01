<?php

namespace MosparoIntegration\Module\WooCommerceAccount;

use MosparoIntegration\Module\AbstractModule;
use MosparoIntegration\Module\ModuleSettings;
use MosparoIntegration\Module\Account\AccountForm;
use MosparoIntegration\ModuleForm\AccountLoginForm;
use MosparoIntegration\ModuleForm\AccountLostPasswordForm;
use MosparoIntegration\ModuleForm\AccountRegisterForm;

class WooCommerceAccountModule extends AbstractModule
{
    protected $key = 'woocommerceaccount';

    public function __construct()
    {
        $this->name = __('WooCommerce Account', 'mosparo-integration');
        $this->description = __('Protects the WooCommerce account forms like login, register and lost password with mosparo.', 'mosparo-integration');
        $this->dependencies = [
            'woocommerce' => ['name' => __('WooCommerce', 'mosparo-integration'), 'url' => 'https://woocommerce.com/']
        ];
        $this->settings = new ModuleSettings(
            [
                'login_form' => [
                    'label' => __('Woocommerce login form', 'mosparo-integration'),
                    'type' => 'boolean',
                    'value' => true,
                ],
                'register_form' => [
                    'label' => __('Woocommerce registration form', 'mosparo-integration'),
                    'type' => 'boolean',
                    'value' => true,
                ],
                'lostpassword_form' => [
                    'label' => __('Woocommerce lost password form', 'mosparo-integration'),
                    'type' => 'boolean',
                    'value' => true,
                ]
            ],
            [
                'header' => __('Please choose which WooCommerce forms you want to protect with mosparo.', 'mosparo-integration'),
            ]
        );
    }

    public function canInitialize()
    {
        return class_exists('WooCommerce');
    }

    public function initializeModule($pluginDirectoryPath, $pluginDirectoryUrl)
    {
        add_filter('mosparo_integration_' . $this->getKey() . '_login_form_data', function($formData) {
            $formData['username'] = sanitize_user($_REQUEST['username']);
            return $formData;
        }, 1, 1);

        add_action('login_head', function () use ($pluginDirectoryUrl) {
            if (!wp_style_is('mosparo-integration-user-form')) {
                wp_enqueue_style('mosparo-integration-user-form', $pluginDirectoryUrl . 'assets/module/user/css/login.css');
            }
        });

        if ($this->getSettings()->getFieldValue('login_form')) {
            $loginForm = new AccountLoginForm($this);
            add_action('woocommerce_login_form', [$loginForm, 'displayMosparoField']);
            add_filter('wp_authenticate_user', [$loginForm, 'verifyLoginForm'], 10, 1);
        }

        if ($this->getSettings()->getFieldValue('register_form')) {
            $registerForm = new AccountRegisterForm($this);
            add_action('woocommerce_register_form', [$registerForm, 'displayMosparoField']);
            add_filter('registration_errors', [$registerForm, 'verifyRegisterForm'], 999, 3);
        }

        if ($this->getSettings()->getFieldValue('lostpassword_form')) {
            $lostPasswordForm = new AccountLostPasswordForm($this);
            add_action('woocommerce_lostpassword_form', [$lostPasswordForm, 'displayMosparoField']);
            add_filter('lostpassword_errors', [$lostPasswordForm, 'verifyLostPasswordForm'], 999, 2);
        }
    }
}
