<?php

namespace MosparoIntegration\Module\Account;

use MosparoIntegration\Module\AbstractModule;
use MosparoIntegration\Module\ModuleSettings;
use MosparoIntegration\ModuleForm\AccountLoginForm;
use MosparoIntegration\ModuleForm\AccountLostPasswordForm;
use MosparoIntegration\ModuleForm\AccountRegisterForm;

class AccountModule extends AbstractModule
{
    protected $key = 'account';

    public function __construct()
    {
        $this->name = __('Account', 'mosparo-integration');
        $this->description = __('Protects the WordPress account forms like login, register and lost password with mosparo.', 'mosparo-integration');
        $this->dependencies = [];
        $this->settings = new ModuleSettings(
            [
                'login_form' => [
                    'label' => __('Login form', 'mosparo-integration'),
                    'description' => __('Protect the WordPress login form with mosparo', 'mosparo-integration'),
                    'type' => 'boolean',
                    'value' => true,
                ],
                'register_form' => [
                    'label' => __('Registration form', 'mosparo-integration'),
                    'description' => __('Protect the WordPress registration form with mosparo', 'mosparo-integration'),
                    'type' => 'boolean',
                    'value' => true,
                ],
                'lostpassword_form' => [
                    'label' => __('Lost password form', 'mosparo-integration'),
                    'description' => __('Protect the WordPress lost password form with mosparo', 'mosparo-integration'),
                    'type' => 'boolean',
                    'value' => true,
                ]
            ],
            [
                'header' => __('Please choose which WordPress account forms you want to protect with mosparo.', 'mosparo-integration'),
            ]
        );
    }

    public function initializeModule($pluginDirectoryPath, $pluginDirectoryUrl)
    {
        add_action('login_head', function () use ($pluginDirectoryUrl) {
            if (!wp_style_is('mosparo-integration-user-form')) {
                wp_enqueue_style('mosparo-integration-user-form', $pluginDirectoryUrl . 'assets/module/user/css/login.css');
            }
        });

        if ($this->getSettings()->getFieldValue('login_form')) {
            $loginForm = new AccountLoginForm($this);
            add_action('login_form', [$loginForm, 'displayMosparoField'], 10, 1);
            add_filter('wp_authenticate_user', [$loginForm, 'verifyLoginForm'], 1000);
        }

        if ($this->getSettings()->getFieldValue('register_form')) {
            $registerForm = new AccountRegisterForm($this);
            add_action('register_form', [$registerForm, 'displayMosparoField'], 10, 1);
            add_filter('registration_errors', [$registerForm, 'verifyRegisterForm'], 999, 1);
        }

        if ($this->getSettings()->getFieldValue('lostpassword_form')) {
            $lostPasswordForm = new AccountLostPasswordForm($this);
            add_action('lostpassword_form', [$lostPasswordForm, 'displayMosparoField'], 10, 1);
            add_action('lostpassword_post', [$lostPasswordForm, 'verifyLostPasswordForm'], 999, 2);
        }
    }

}
