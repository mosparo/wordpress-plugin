<?php

namespace MosparoIntegration\Module;

use MosparoIntegration\Helper\ConfigHelper;
use MosparoIntegration\Entity\Connection;

abstract class AbstractModule
{
    protected $key;
    protected $name;
    protected $description;
    protected $dependencies = [];
    protected $settings = [];

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


    public function canInitialize() {
        return empty($this->dependencies);
    }

    abstract public function initializeModule($pluginDirectoryPath, $pluginDirectoryUrl);
}
