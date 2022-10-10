<?php

namespace MosparoIntegration\Helper;

class ConfigHelper
{
    private static $instance;
    private $connection = [];

    public static function getInstance()
    {
        if (empty(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
        $this->loadConfiguration();
    }

    public function isActive()
    {
        $host = $this->getHost();
        $uuid = $this->getUuid();
        $publicKey = $this->getPublicKey();
        $privateKey = $this->getPrivateKey();

        return $host && $uuid && $publicKey && $privateKey;
    }

    public function getHost()
    {
        return $this->config['host'] ?? '';
    }

    public function setHost($host)
    {
        $this->config['host'] = $host;
    }

    public function getUuid()
    {
        return $this->config['uuid'] ?? '';
    }

    public function setUuid($uuid)
    {
        $this->config['uuid'] = $uuid;
    }

    public function getPublicKey()
    {
        return $this->config['publicKey'] ?? '';
    }

    public function setPublicKey($publicKey)
    {
        $this->config['publicKey'] = $publicKey;
    }

    public function getPrivateKey()
    {
        return $this->config['privateKey'] ?? '';
    }

    public function setPrivateKey($privateKey)
    {
        $this->config['privateKey'] = $privateKey;
    }

    public function getVerifySsl()
    {
        return $this->config['verifySsl'] ?? true;
    }

    public function setVerifySsl($verifySsl)
    {
        $this->config['verifySsl'] = $verifySsl;
    }

    public function getLoadResourcesAlways()
    {
        return $this->config['loadResourcesAlways'] ?? true;
    }

    public function setLoadResourcesAlways($loadResourcesAlways)
    {
        $this->config['loadResourcesAlways'] = $loadResourcesAlways;
    }

    public function getLoadCssResourceOnInitialization()
    {
        return $this->config['loadCssResourceOnInitialization'] ?? false;
    }

    public function setLoadCssResourceOnInitialization($loadCssResourceOnInitialization)
    {
        $this->config['loadCssResourceOnInitialization'] = $loadCssResourceOnInitialization;
    }

    public function resetConnectionSettings()
    {
        if (!$this->isActive()) {
            return;
        }

        unset($this->config['host']);
        unset($this->config['uuid']);
        unset($this->config['publicKey']);
        unset($this->config['privateKey']);
    }

    public function isModuleActive($moduleKey)
    {
        if (in_array($moduleKey,$this->config['modules'] ?? [])) {
            return true;
        }
        
        return false;
    }
    
    public function enableModule($moduleKey)
    {
        if (!is_array($this->config['modules'])) {
            $this->config['modules'] = [];
        }

        if (!$this->isModuleActive($moduleKey)) {
            $this->config['modules'][] = $moduleKey;
        }
    }

    public function disableModule($moduleKey)
    {
        if (!isset($this->config['modules']) || !is_array($this->config['modules'])) {
            return;
        }

        $moduleIndex = array_search($moduleKey, $this->config['modules']);
        if ($moduleIndex !== false) {
            unset($this->config['modules'][$moduleIndex]);
        }
    }

    public function loadConfiguration()
    {
        $this->config = get_option('mosparo-integration-configuration', []);
    }

    public function saveConfiguration()
    {
        update_option('mosparo-integration-configuration', $this->config);
    }

    protected function resetConfiguration()
    {
        $this->config = [];
        $this->saveConfiguration();
    }
}