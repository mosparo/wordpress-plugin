<?php

namespace MosparoIntegration\Module\BbPress;

use MosparoIntegration\Module\AbstractModule;
use MosparoIntegration\Module\ModuleSettings;

class BbPressModule extends AbstractModule
{
    protected $key = 'bbpress';

    public function __construct()
    {
        $this->name = __('bbPress', 'mosparo-integration');
        $this->description = __('Protects the bbPress forms with mosparo.', 'mosparo-integration');
        $this->dependencies = [];
        $this->settings = new ModuleSettings(
            [
                'hint_account_forms' => [
                    'label' => __('Account forms', 'mosparo-integration'),
                    'description' => __('To protect the bbPress login, registration, and lost password forms with mosparo, please enable the WordPress account forms module.', 'mosparo-integration'),
                    'type' => 'info',
                    'value' => false,
                ],
                'new_topic_form' => [
                    'label' => __('New topic form', 'mosparo-integration'),
                    'description' => __('Protect the bbPress new topic form with mosparo.', 'mosparo-integration'),
                    'type' => 'choice_multiple',
                    'value' => true,
                    'choices' => function () {
                        return [
                            __('Add mosparo for anonymous users', 'mosparo-integration') => 'anonymous',
                            __('Add mosparo for authenticated users', 'mosparo-integration') => 'authenticated',
                        ];
                    }
                ],
                'new_reply_form' => [
                    'label' => __('New reply form', 'mosparo-integration'),
                    'description' => __('Protect the bbPress new reply form with mosparo.', 'mosparo-integration'),
                    'type' => 'choice_multiple',
                    'value' => true,
                    'choices' => function () {
                        return [
                            __('Add mosparo for anonymous users', 'mosparo-integration') => 'anonymous',
                            __('Add mosparo for authenticated users', 'mosparo-integration') => 'authenticated',
                        ];
                    }
                ],
            ],
            [
                'header' => __('Please choose which bbPress forms you want to protect with mosparo.', 'mosparo-integration'),
            ]
        );
    }

    public function canInitialize()
    {
        return function_exists('bbp_is_anonymous');
    }

    public function initializeModule($pluginDirectoryPath, $pluginDirectoryUrl)
    {
        $bbPressForm = BbPressForm::getInstance();
        $bbPressForm->registerHooks($this);

        add_action('bbp_enqueue_scripts', function () use ($pluginDirectoryUrl) {
            if (!wp_style_is('mosparo-integration-bbpress')) {
                wp_enqueue_style('mosparo-integration-bbpress', $pluginDirectoryUrl . 'assets/module/bbpress/css/bbpress.css', 'bbp-default');
            }
        });
    }
}