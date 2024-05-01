<?php

namespace MosparoIntegration\Module\GravityForms;

use MosparoIntegration\Module\AbstractModule;
use GF_Fields;

class GravityFormsModule extends AbstractModule
{
    protected $key = 'gravity-forms';

    public function __construct()
    {
        $this->name = __('Gravity Forms', 'mosparo-integration');
        $this->description = __('Protects Gravity Forms forms with mosparo.', 'mosparo-integration');
        $this->dependencies = [
            'gravity-forms' => ['name' => __('Gravity Forms', 'mosparo-integration'), 'url' => 'https://www.gravityforms.com/']
        ];
    }

    public function canInitialize()
    {
        return class_exists('GFCommon');
    }

    public function initializeModule($pluginDirectoryPath, $pluginDirectoryUrl)
    {
        GF_Fields::register(new MosparoField());
    }
}
