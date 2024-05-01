<?php

namespace MosparoIntegration\Module\NinjaForms;

use MosparoIntegration\Module\AbstractModule;

class NinjaFormsModule extends AbstractModule
{
    /**
     * @var string
     */
    protected $key = 'ninja-forms';

    /**
     * Constructs the object
     */
    public function __construct()
    {
        $this->name = __('Ninja Forms', 'mosparo-integration');
        $this->description = __('Protects Ninja Forms forms with mosparo.', 'mosparo-integration');
        $this->dependencies = [
            'ninja-forms' => ['name' => __('Ninja Forms', 'mosparo-integration'), 'url' => 'https://wordpress.org/plugins/ninja-forms/']
        ];
    }

    public function canInitialize()
    {
        return class_exists('\Ninja_Forms');
    }

    /**
     * Initializes the module
     *
     * @param string $pluginDirectoryPath
     * @param string $pluginDirectoryUrl
     */
    public function initializeModule($pluginDirectoryPath, $pluginDirectoryUrl)
    {
        add_filter('ninja_forms_register_fields', function ($fields) {
            $fields['mosparo'] = new MosparoField();
            return $fields;
        });

        add_filter('ninja_forms_register_actions', function ($actions) {
            $actions['mosparo'] = new MosparoAction();
            return $actions;
        });

        add_filter('ninja_forms_field_template_file_paths', function ($paths) use ($pluginDirectoryPath) {
            $paths[] = $pluginDirectoryPath . '/views/module/ninja-forms/';
            return $paths;
        });

        add_action('wp_enqueue_scripts', function () use ($pluginDirectoryUrl) {
            wp_enqueue_script('mosparo-field-validation', $pluginDirectoryUrl . 'assets/module/ninja-forms/js/mosparo.js', ['nf-front-end', 'jquery', 'backbone']);
        });
    }
}
