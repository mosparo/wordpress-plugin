<?php

namespace MosparoIntegration\Module\JetFormBuilder;

use MosparoIntegration\Module\AbstractModule;

class JetFormBuilderModule extends AbstractModule
{
    protected $key = 'jet-form-builder';

    public function __construct()
    {
        $this->name = __('Jet Form Builder', 'mosparo-integration');
        $this->description = __('Protects Jet Form Builder forms with mosparo.', 'mosparo-integration');
        $this->dependencies = [
            'jet-form-builder' => ['name' => __('Jet Form Builder', 'mosparo-integration'), 'url' => 'https://wordpress.org/plugins/jetformbuilder/']
        ];
    }

    public function canInitialize()
    {
        return class_exists('JFB_Modules\Captcha\Abstract_Captcha\Base_Captcha_From_Options');
    }

    public function initializeModule($pluginDirectoryPath, $pluginDirectoryUrl)
    {
        add_filter('jet-form-builder/captcha/types', function ($types) use ($pluginDirectoryPath, $pluginDirectoryUrl) {
            $mosparo = new Mosparo($pluginDirectoryPath, $pluginDirectoryUrl);

            $types[] = $mosparo;

            return $types;
        });
    }
}
