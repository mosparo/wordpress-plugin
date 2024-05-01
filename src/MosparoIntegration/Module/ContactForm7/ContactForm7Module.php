<?php

namespace MosparoIntegration\Module\ContactForm7;

use MosparoIntegration\Module\AbstractModule;

class ContactForm7Module extends AbstractModule
{
    protected $key = 'contact-form-7';

    public function __construct()
    {
        $this->name = __('Contact Form 7', 'mosparo-integration');
        $this->description = __('Protects Contact Form 7 forms with mosparo.', 'mosparo-integration');
        $this->dependencies = [
            'contact-form-7' => ['name' => __('Contact Form 7', 'mosparo-integration'), 'url' => 'https://wordpress.org/plugins/contact-form-7/']
        ];
    }

    public function canInitialize()
    {
        return function_exists('wpcf7_add_form_tag');
    }

    public function initializeModule($pluginDirectoryPath, $pluginDirectoryUrl)
    {        
        $mosparoCf7Field = MosparoField::getInstance();
        $mosparoCf7Field->registerHooks();
    }

}
