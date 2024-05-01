<?php

namespace MosparoIntegration\Module\ElementorForm;

use MosparoIntegration\Module\AbstractModule;

class ElementorFormModule extends AbstractModule
{
    protected $key = 'elementor-form';

    public function __construct()
    {
        $this->name = __('Elementor Form', 'mosparo-integration');
        $this->description = __('Protects Elementor Form forms with mosparo.', 'mosparo-integration');
        $this->dependencies = [
            'elementor-pro' => ['name' => __('Elementor Pro', 'mosparo-integration'), 'url' => 'https://elementor.com/']
        ];
    }

    public function canInitialize()
    {
        return function_exists('elementor_pro_load_plugin');
    }

    public function initializeModule($pluginDirectoryPath, $pluginDirectoryUrl)
    {
        $mosparoElementorField = MosparoField::getInstance($pluginDirectoryUrl);
        $mosparoElementorField->registerHooks();
    }
}
