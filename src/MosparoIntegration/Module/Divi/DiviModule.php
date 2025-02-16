<?php

namespace MosparoIntegration\Module\Divi;

use MosparoIntegration\Module\AbstractModule;

class DiviModule extends AbstractModule
{
    protected $key = 'divi';

    public function __construct()
    {
        $this->name = __('Divi', 'mosparo-integration');
        $this->description = __('Protects Divi contact forms with mosparo.', 'mosparo-integration');
        $this->dependencies = [
            'divi' => ['name' => __('Divi', 'mosparo-integration'), 'url' => 'https://www.elegantthemes.com/modules/contact-form/']
        ];
    }

    public function canInitialize()
    {
        return class_exists('ET_Builder_Plugin');
    }

    public function initializeModule($pluginDirectoryPath, $pluginDirectoryUrl)
    {
        add_filter('et_core_get_third_party_components', [$this, 'registerProvider'], 10, 2);
    }

    public function registerProvider($providers, $group)
    {
        if ($group !== '' && $group !== 'api/spam') {
            return $providers;
        }

        $providers['mosparo'] = new MosparoSpamServiceProvider();

        return $providers;
    }
}