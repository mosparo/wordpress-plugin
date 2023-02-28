<?php

namespace MosparoIntegration\Helper;

use MosparoIntegration\Module\Comments\CommentsModule;
use MosparoIntegration\Module\ContactForm7\ContactForm7Module;
use MosparoIntegration\Module\EverestForms\EverestFormsModule;
use MosparoIntegration\Module\Formidable\FormidableModule;
use MosparoIntegration\Module\GravityForms\GravityFormsModule;
use MosparoIntegration\Module\NinjaForms\NinjaFormsModule;
use MosparoIntegration\Module\WPForms\WPFormsModule;
use MosparoIntegration\Module\User\UserModule;

class ModuleHelper
{
    private static $instance;
    protected static $availableModules = [
        CommentsModule::class,
        ContactForm7Module::class,
        EverestFormsModule::class,
        FormidableModule::class,
        GravityFormsModule::class,
        NinjaFormsModule::class,
        WPFormsModule::class,
        UserModule::class,
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

    public function getActiveModules()
    {
        return $this->activeModules;
    }

    public function getActiveModule($moduleKey)
    {
        if (!isset($this->activeModules[$moduleKey])) {
            return null;
        }

        return $this->activeModules[$moduleKey];
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
        return apply_filters('mosparo_integration_filter_available_modules', self::$availableModules);
    }
}