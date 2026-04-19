<?php

namespace MosparoIntegration\Module\UltimateMemberAccount;

use MosparoIntegration\Module\AbstractModule;
use MosparoIntegration\Module\ModuleSettings;

class UltimateMemberAccountModule extends AbstractModule
{
    protected $key = 'ultimatememberaccount';

    public function __construct()
    {
        $this->name = __('Ultimate Member Account', 'mosparo-integration');
        $this->description = __('Protects the Ultimate Member account forms like login and lost password with mosparo.', 'mosparo-integration');
        $this->dependencies = [
            'ultimatemember' => ['name' => __('Ultimate Member', 'mosparo-integration'), 'url' => 'https://wordpress.org/plugins/ultimate-member/']
        ];
        $this->settings = new ModuleSettings(
            [
                'login_form' => [
                    'label' => __('Login form', 'mosparo-integration'),
                    'description' => __('Protect the Ultimate Member login form with mosparo', 'mosparo-integration'),
                    'type' => 'boolean',
                    'value' => true,
                ],
                'register_form' => [
                    'label' => __('Registration form', 'mosparo-integration'),
                    'description' => __('Protect the Ultimate Member registration form with mosparo', 'mosparo-integration'),
                    'type' => 'boolean',
                    'value' => true,
                ],
                'lostpassword_form' => [
                    'label' => __('Lost password form', 'mosparo-integration'),
                    'description' => __('Protect the Ultimate Member lost password form with mosparo', 'mosparo-integration'),
                    'type' => 'boolean',
                    'value' => true,
                ]
            ],
            [
                'header' => __('Please choose which Ultimate Member account forms you want to protect with mosparo.', 'mosparo-integration'),
            ]
        );
    }

    public function canInitialize()
    {
        return class_exists('UM');
    }

    public function initializeModule($pluginDirectoryPath, $pluginDirectoryUrl)
    {
        add_action('wp_enqueue_scripts', function () use ($pluginDirectoryUrl) {
            global $post;
            if (
                !wp_style_is('mosparo-integration-ultimate-member-form') &&
                is_object($post) &&
                (has_shortcode($post->post_content, 'ultimatemember') || has_shortcode($post->post_content, 'ultimatemember_password'))
            ) {
                wp_enqueue_style('mosparo-integration-ultimate-member-form', $pluginDirectoryUrl . 'assets/module/ultimate-member/css/form.css');
            }
        }, 200);

        if ($this->getSettings()->getFieldValue('login_form')) {
            $loginForm = new CustomizableForm($this, 'login');
            add_action('um_after_login_fields', [$loginForm, 'displayMosparoField']);
            add_filter('um_submit_form_errors_hook', [$loginForm, 'verifyForm'], 20, 2);
        }

        if ($this->getSettings()->getFieldValue('register_form')) {
            $registrationForm = new CustomizableForm($this, 'register');
            add_action('um_after_register_fields', [$registrationForm, 'displayMosparoField']);
            add_filter('um_submit_form_errors_hook', [$registrationForm, 'verifyForm'], 20, 2);
        }

        if ($this->getSettings()->getFieldValue('lostpassword_form')) {
            $lostPasswordForm = new AccountLostPasswordForm($this);
            add_action('um_after_password_reset_fields', [$lostPasswordForm, 'displayMosparoField']);
            add_filter('um_reset_password_errors_hook', [$lostPasswordForm, 'verifyLostPasswordForm'], 20, 2);
        }
    }
}
