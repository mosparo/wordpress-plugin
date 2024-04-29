<?php

namespace MosparoIntegration\Module;

use MosparoIntegration\Helper\ConfigHelper;
use MosparoIntegration\Entity\Connection;

class ModuleSettings
{
    protected $settings = [];
    protected $settings_form = [];

    public function __construct($fields, $form)
    {
        $this->settings = $fields;
        $this->settings_form = $form;
    }

    public function getSettingsForm()
    {
        return $this->settings_form;
    }

    public function getFields()
    {
        return $this->settings;
    }

    public function getFieldValue($key)
    {
        if (isset($this->settings[$key]['value'])) {
            return $this->settings[$key]['value'];
        }
        return null;
    }

    public function setSettings(array $fields): array
    {
        $this->settings = $fields;
        return $this->settings;
    }

}

abstract class AbstractModule
{
    protected $key;
    protected $name;
    protected $description;
    protected $dependencies = [];
    protected ?ModuleSettings $settings = null;

    public function getKey(): string
    {
        return $this->key;
    }

    final public function getDefaultKey(): string
    {
        return 'module_' . $this->key;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    public function hasSettings(): bool {
        return $this->settings != null;
    }

    public function getSettings(): ?ModuleSettings
    {
        return $this->settings;
    }

    public function canInitialize() {
        return empty($this->dependencies);
    }

    abstract public function initializeModule($pluginDirectoryPath, $pluginDirectoryUrl);
}
