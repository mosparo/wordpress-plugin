<?php

namespace MosparoWp\Module\NinjaForms;

use MosparoWp\Module\AbstractModule;

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
        $this->name = __('Ninja Forms', 'mosparo-wp');
        $this->description = __('Protects WPForms forms with mosparo.', 'mosparo-wp');
        $this->dependencies = [
            'ninja-forms' => ['name' => __('Ninja Forms', 'mosparo-wp'), 'url' => 'https://wordpress.org/plugins/ninja-forms/']
        ];
    }

    /**
     * Initializes the module
     *
     * @param string $pluginDirectoryPath
     * @param string $pluginDirectoryUrl
     */
    public function initializeModule($pluginDirectoryPath, $pluginDirectoryUrl)
    {
        if (!class_exists('\Ninja_Forms')) {
            return;
        }

        add_filter('ninja_forms_register_fields', function ($fields) {
            $fields['mosparo'] = new MosparoWpField();
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
            wp_enqueue_script('mosparo-field-validation', $pluginDirectoryUrl . 'assets/module/ninja-forms/js/mosparo.js', ['nf-front-end']);
        });
    }
}