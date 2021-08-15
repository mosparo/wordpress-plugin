<?php

namespace MosparoWp\Module\ContactForm7;

use MosparoWp\Module\AbstractModule;

class ContactForm7Module extends AbstractModule
{
    protected $key = 'contact-form-7';

    public function __construct()
    {
        $this->name = __('Contact Form 7', 'mosparo-wp');
        $this->description = __('Protects Contact Form 7 forms with mosparo.', 'mosparo-wp');
        $this->dependencies = [
            'contact-form-7' => ['name' => __('Contact Form 7', 'mosparo-wp'), 'url' => 'https://wordpress.org/plugins/contact-form-7/']
        ];
    }

    public function initializeModule($pluginDirectoryPath, $pluginDirectoryUrl)
    {
        if (!function_exists('wpcf7_add_form_tag')) {
            return;
        }
        
        $mosparoWpCf7Field = MosparoWpField::getInstance();
        $mosparoWpCf7Field->registerHooks();
    }
}