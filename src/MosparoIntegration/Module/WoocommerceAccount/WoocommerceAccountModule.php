<?php

namespace MosparoIntegration\Module\WoocommerceAccount;

use MosparoIntegration\Module\AbstractModule;
use MosparoIntegration\Module\ModuleSettings;
use MosparoIntegration\Module\Account\AccountForm;

class WoocommerceAccountModule extends AbstractModule
{
    protected $key = 'woocommerceaccount';

    public function __construct()
    {
        $this->name = __('WoocommerceAccount', 'mosparo-integration');
        $this->description = __('Protects the account forms like login, register and password reset with mosparo.', 'mosparo-integration');
        $this->dependencies = [
            'woocommerce' => ['name' => __('Woocommerce', 'mosparo-integration'), 'url' => 'https://woocommerce.com/']
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
                'header' => __('Please choose which Woocommerce forms you want to protect with mosparo.', 'mosparo-integration'),
            ]
        );
    }

    public function canInitialize() {
        return class_exists('WooCommerce');
    }

    public function initializeModule($pluginDirectoryPath, $pluginDirectoryUrl)
    {
        $accountForm = new AccountForm($this);
        add_filter('mosparo_integration_'.$this->getKey().'_login_form_data', function($form_data) {
            $form_data['username'] = sanitize_user($_REQUEST['username']);
            return $form_data;
        }, 1, 1);
        add_action('login_head', function () use ($pluginDirectoryUrl) {
            if (!wp_style_is('mosparo-integration-user-form')) {
                wp_enqueue_style('mosparo-integration-user-form', $pluginDirectoryUrl . 'assets/module/user/css/login.css');
            }
        });
        if ($this->getSettings()->getFieldValue('login_form')) {
            add_action('woocommerce_login_form', [$accountForm, 'displayMosparoField']);
            add_filter('wp_authenticate_user', [$accountForm, 'verifyLoginForm'], 10, 1);
        }
        if ($this->getSettings()->getFieldValue('register_form')) {
            add_action('woocommerce_register_form', [$accountForm, 'displayMosparoField']);
            add_filter('registration_errors', [$accountForm, 'verifyRegisterForm'], 999, 3);
        }
        if ($this->getSettings()->getFieldValue('lostpassword_form')) {
            add_action('woocommerce_lostpassword_form', [$accountForm, 'displayMosparoField']);
            add_filter('lostpassword_errors', [$accountForm, 'verifyLostPasswordForm'], 999, 2);
        }
    }
}
