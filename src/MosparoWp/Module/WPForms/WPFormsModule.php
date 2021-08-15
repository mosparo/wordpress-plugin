<?php

namespace MosparoWp\Module\WPForms;

use MosparoWp\Module\AbstractModule;

class WPFormsModule extends AbstractModule
{
    protected $key = 'wpforms';

    public function __construct()
    {
        $this->name = __('WPForms', 'mosparo-wp');
        $this->description = __('Protects WPForms forms with mosparo.', 'mosparo-wp');
        $this->dependencies = [
            'wpforms-lite' => ['name' => __('WPForms', 'mosparo-wp'), 'url' => 'https://wordpress.org/plugins/wpforms-lite/']
        ];
    }

    public function initializeModule($pluginDirectoryPath, $pluginDirectoryUrl)
    {
        if (!defined('WPFORMS_VERSION')) {
            return;
        }
        
        new MosparoWpField();
    }
}