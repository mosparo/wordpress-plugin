<?php

namespace MosparoIntegration\Module\Account;

use MosparoIntegration\Module\AbstractModule;

class AccountModule extends AbstractModule
{
    protected $key = 'account';

    public function __construct()
    {
        $this->name = __('Account', 'mosparo-integration');
        $this->description = __('Protects the account forms like login, register and password reset with mosparo.', 'mosparo-integration');
        $this->dependencies = [];
        $this->settings = [
            'login_form' => [
                'label' => __('Enable Wordpress login form protection', 'mosparo-integration'),
                'type' => 'boolean',
                'value' => true,
            ],
            'register_form' => [
                'label' => __('Enable Wordpress registration form protection', 'mosparo-integration'),
                'type' => 'boolean',
                'value' => true,
            ],
            'lostpassword_form' => [
                'label' => __('Enable Wordpress lost password form protection', 'mosparo-integration'),
                'type' => 'boolean',
                'value' => true,
            ]
        ];
    }

    public function initializeModule($pluginDirectoryPath, $pluginDirectoryUrl)
    {
        $accountForm = new AccountForm($this);
        //$accountForm->registerWordpressHooks($pluginDirectoryPath, $pluginDirectoryUrl);
        add_filter('mosparo_integration_'.$this->getKey().'_login_form_data', function($form_data) {
            $form_data['log'] = sanitize_user($_REQUEST['log']);
            return $form_data;
        }, 1, 1);
        add_action('login_head', function () use ($pluginDirectoryUrl) {
            if (!wp_style_is('mosparo-integration-user-form'))  {
                wp_enqueue_style('mosparo-integration-user-form', $pluginDirectoryUrl . 'assets/module/user/css/login.css');
            }
        });
        if ($this->getSetting('login_form')) {
            add_action('login_form', [$accountForm, 'displayMosparoField'], 10, 1);
            add_filter('wp_authenticate_user', [$accountForm, 'verifyLoginForm'], 1000);
        }
        if ($this->getSetting('register_form')) {
            add_action('register_form', [$accountForm, 'displayMosparoField'], 10, 1);
            add_action('register_post', [$accountForm, 'verifyRegisterForm'], 10, 3);
        }
        if ($this->getSetting('lostpassword_form')) {
            add_action('lostpassword_form', [$accountForm, 'displayMosparoField'], 10, 1);
            add_action('lostpassword_post', [$accountForm, 'verifyLostPasswordForm']);
        }
    }

}
