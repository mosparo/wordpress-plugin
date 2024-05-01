<?php

namespace MosparoIntegration\Module\EverestForms;

use MosparoIntegration\Module\AbstractModule;

class EverestFormsModule extends AbstractModule
{
    protected $key = 'everest-forms';

    public function __construct()
    {
        $this->name = __('Everest Forms', 'mosparo-integration');
        $this->description = __('Protects Everest Forms forms with mosparo.', 'mosparo-integration');
        $this->dependencies = [
            'everest-forms' => ['name' => __('Everest Forms', 'mosparo-integration'), 'url' => 'https://wordpress.org/plugins/everest-forms/']
        ];
    }

    public function canInitialize()
    {
        return class_exists('EVF_Form_Fields');
    }

    public function initializeModule($pluginDirectoryPath, $pluginDirectoryUrl)
    {        
        add_filter('everest_forms_fields', [$this, 'addFieldType']);

        add_action('wp_enqueue_scripts', function () use ($pluginDirectoryUrl) {
            wp_enqueue_script('mosparo-ajax', $pluginDirectoryUrl . 'assets/module/everest-forms/js/mosparo-ajax.js', ['jquery', 'everest-forms-ajax-submission']);
        });
    }

    public function addFieldType($fields)
    {
        $fields[] = MosparoField::class;

        return $fields;
    }
}
