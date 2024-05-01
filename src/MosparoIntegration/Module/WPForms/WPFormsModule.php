<?php

namespace MosparoIntegration\Module\WPForms;

use MosparoIntegration\Module\AbstractModule;

class WPFormsModule extends AbstractModule
{
    protected $key = 'wpforms';

    public function __construct()
    {
        $this->name = __('WPForms', 'mosparo-integration');
        $this->description = __('Protects WPForms forms with mosparo.', 'mosparo-integration');
        $this->dependencies = [
            'wpforms-lite' => ['name' => __('WPForms', 'mosparo-integration'), 'url' => 'https://wordpress.org/plugins/wpforms-lite/']
        ];
    }

    public function canInitialize()
    {
        return defined('WPFORMS_VERSION');
    }

    public function initializeModule($pluginDirectoryPath, $pluginDirectoryUrl)
    {
        new MosparoField();
    }
}
