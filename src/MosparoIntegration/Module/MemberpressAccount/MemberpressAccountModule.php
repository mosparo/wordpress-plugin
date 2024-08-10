<?php

namespace MosparoIntegration\Module\MemberpressAccount;

use MosparoIntegration\Module\AbstractModule;
use MosparoIntegration\Module\ModuleSettings;

class MemberpressAccountModule extends AbstractModule
{
    protected $key = 'memberpressaccount';

    public function __construct()
    {
        $this->name = __('Memberpress Account', 'mosparo-integration');
        $this->description = __('Protects the Memberpress account forms like login and lost password with mosparo.', 'mosparo-integration');
        $this->dependencies = [
            'memberpress' => ['name' => __('Memberpress', 'mosparo-integration'), 'url' => 'https://memberpress.com/']
        ];
        $this->settings = new ModuleSettings(
            [
                'login_form' => [
                    'label' => __('Login form', 'mosparo-integration'),
                    'description' => __('Protect the Memberpress login form with mosparo', 'mosparo-integration'),
                    'type' => 'boolean',
                    'value' => true,
                ],
                'lostpassword_form' => [
                    'label' => __('Lost password form', 'mosparo-integration'),
                    'description' => __('Protect the Memberpress lost password form with mosparo', 'mosparo-integration'),
                    'type' => 'boolean',
                    'value' => true,
                ]
            ],
            [
                'header' => __('Please choose which Memberpress account forms you want to protect with mosparo.', 'mosparo-integration'),
            ]
        );
    }

    public function canInitialize()
    {
        return class_exists('MeprHooks');
    }

    public function initializeModule($pluginDirectoryPath, $pluginDirectoryUrl)
    {
        add_action('mepr-before-login-form', function () use ($pluginDirectoryUrl) {
            if (!wp_style_is('mosparo-integration-memberpress-form')) {
                wp_enqueue_style('mosparo-integration-memberpress-form', $pluginDirectoryUrl . 'assets/module/memberpress/css/login.css');
            }
        });

        if ($this->getSettings()->getFieldValue('login_form')) {
            $loginForm = new AccountLoginForm($this);
            add_action('mepr-login-form-before-submit', [$loginForm, 'displayMosparoField']);
            add_filter('mepr-validate-login', [$loginForm, 'verifyLoginForm'], 10, 1);
        }

        if ($this->getSettings()->getFieldValue('lostpassword_form')) {
            $lostPasswordForm = new AccountLostPasswordForm($this);
            add_action('mepr-forgot-password-form', [$lostPasswordForm, 'displayMosparoField']);
            add_filter('mepr-validate-forgot-password', [$lostPasswordForm, 'verifyLostPasswordForm'], 999, 2);
        }
    }
}
