<?php

namespace MosparoIntegration\Module\User;

use MosparoIntegration\Module\AbstractModule;

class UserModule extends AbstractModule
{
    protected $key = 'user';

    public function __construct()
    {
        $this->name = __('User', 'mosparo-integration');
        $this->description = __('Protects the user forms like login, register and password reset with mosparo.', 'mosparo-integration');
        $this->dependencies = [];
    }

    public function initializeModule($pluginDirectoryPath, $pluginDirectoryUrl)
    {
        $userForm = UserForm::getInstance();
        $userForm->registerHooks();

        add_action('login_head', function () use ($pluginDirectoryUrl) {
            wp_enqueue_style('mosparo-integration-user-form', $pluginDirectoryUrl . 'assets/module/user/css/login.css');
        });
    }
}