<?php

namespace MosparoIntegration\Module;

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

    public function canInitialize()
    {
        return empty($this->dependencies);
    }

    abstract public function initializeModule($pluginDirectoryPath, $pluginDirectoryUrl);
}
