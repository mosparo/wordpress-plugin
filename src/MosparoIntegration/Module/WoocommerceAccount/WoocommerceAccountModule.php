<?php

namespace MosparoIntegration\Module\WoocommerceAccount;

use MosparoIntegration\Module\AbstractModule;
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
        $this->settings = [
            'login_form' => [
                'label' => __('Enable Woocommerce login form protection', 'mosparo-integration'),
                'type' => 'boolean',
                'value' => 'On',
            ],
            'register_form' => [
                'label' => __('Enable Woocommerce registration form protection', 'mosparo-integration'),
                'type' => 'boolean',
                'value' => 'On',
            ],
            'lostpassword_form' => [
                'label' => __('Enable Woocommerce lost password form protection', 'mosparo-integration'),
                'type' => 'boolean',
                'value' => 'On',
            ]
        ];
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
        if ($this->getSetting('login_form')) {
            add_action('woocommerce_login_form', [$accountForm, 'displayMosparoField']);
            add_filter('wp_authenticate_user', [$accountForm, 'verifyLoginForm'], 10, 1);
        }
        if ($this->getSetting('register_form')) {
            add_action('woocommerce_register_form', [$accountForm, 'displayMosparoField']);
            add_filter('registration_errors', [$accountForm, 'verifyRegisterForm'], 999, 3);
        }
        if ($this->getSetting('lostpassword_form')) {
            add_action('woocommerce_lostpassword_form', [$accountForm, 'displayMosparoField']);
            add_filter('lostpassword_errors', [$accountForm, 'verifyLostPasswordForm'], 999, 2);
        }
    }
}
