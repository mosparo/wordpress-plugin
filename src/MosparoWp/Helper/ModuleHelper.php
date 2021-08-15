<?php

namespace MosparoWp\Helper;

use MosparoWp\Module\Comments\CommentsModule;
use MosparoWp\Module\ContactForm7\ContactForm7Module;
use MosparoWp\Module\NinjaForms\NinjaFormsModule;
use MosparoWp\Module\WPForms\WPFormsModule;

class ModuleHelper
{
    private static $instance;
    protected static $availableModules = [
        CommentsModule::class,
        ContactForm7Module::class,
        NinjaFormsModule::class,
        WPFormsModule::class,
    ];
    protected $activeModules = [];

    public static function getInstance()
    {
        if (empty(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {

    }

    public function initializeActiveModules($pluginDirectoryPath, $pluginDirectoryUrl)
    {
        $configHelper = ConfigHelper::getInstance();
        foreach (self::getAvailableModules() as $moduleClass) {
            $module = new $moduleClass();

            if ($configHelper->isModuleActive($module->getKey())) {
                $module->initializeModule($pluginDirectoryPath, $pluginDirectoryUrl);
                $this->activeModules[$module->getKey()] = $module;
            }
        }
    }

    public function getModules(): array
    {
        $modules = [];
        foreach (self::getAvailableModules() as $moduleClass) {
            $modules[] = new $moduleClass;
        }

        return $modules;
    }

    public static function getAvailableModules()
    {
        return apply_filters('mosparo_wp_filter_available_modules', self::$availableModules);
    }
}