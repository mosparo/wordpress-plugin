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
    }

    public function initializeModule($pluginDirectoryPath, $pluginDirectoryUrl)
    {
        $accountForm = AccountForm::getInstance();
        $accountForm->registerHooks();

        add_action('login_head', function () use ($pluginDirectoryUrl) {
            wp_enqueue_style('mosparo-integration-user-form', $pluginDirectoryUrl . 'assets/module/user/css/login.css');
        });
    }
}