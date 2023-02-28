<?php

namespace MosparoIntegration\Helper;

use MosparoIntegration\Entity\Connection;

class ConfigHelper
{
    private static $instance;
    private $config = [];

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

    public function getConnections()
    {
        $connections = $this->config['connections'] ?? [];

        // Before V1.1 of this plugin, there was only one connection possible, so the connection
        // was stored directly into the config array. This fallback converts these originally
        // stored connection into a Connection object.
        if (!$connections && isset($this->config['host'])) {
            $connection = new Connection();
            $connection->setKey('mosparo-connection-v1');
            $connection->setName('mosparo Connection');
            $connection->setHost($this->config['host'] ?? '');
            $connection->setUuid($this->config['uuid'] ?? '');
            $connection->setPublicKey($this->config['publicKey'] ?? '');
            $connection->setPrivateKey($this->config['privateKey'] ?? '');
            $connection->setVerifySsl($this->config['verifySsl'] ?? '');
            $connection->setDefaults(['general']);

            $this->addConnection($connection);

            unset($this->config['host']);
            unset($this->config['uuid']);
            unset($this->config['publicKey']);
            unset($this->config['privateKey']);
            unset($this->config['verifySsl']);

            $this->saveConfiguration();

            $connections = $this->config['connections'] ?? [];
        }

        return $connections;
    }

    public function addConnection(Connection $connection)
    {
        // Every connection needs a key
        if ($connection->getKey() === '') {
            return;
        }

        if (!is_array($this->config['connections'])) {
            $this->config['connections'] = [];
        }

        $this->config['connections'][$connection->getKey()] = $connection;
    }

    public function deleteConnection(Connection $connection)
    {
        if (!$this->hasConnection($connection->getKey())) {
            return;
        }

        unset($this->config['connections'][$connection->getKey()]);
    }

    public function hasConnection($key)
    {
        return (isset($this->config['connections'][$key]));
    }

    public function getConnection($key)
    {
        return $this->config['connections'][$key] ?? false;
    }

    public function getConnectionFor($key, $fallbackToGeneral = true)
    {
        $generalConnection = false;

        foreach ($this->config['connections'] as $connection) {
            if ($connection->isDefaultFor($key)) {
                return $connection;
            } else if ($connection->isDefaultFor('general')) {
                $generalConnection = $connection;
            }
        }

        if ($fallbackToGeneral) {
            return $generalConnection;
        }

        return false;
    }

    public function resetDefaultConnections($defaults)
    {
        foreach ($this->config['connections'] as $connection) {
            foreach ($defaults as $default) {
                if ($connection->isDefaultFor($default)) {
                    $connection->removeDefault($default);
                }
            }
        }
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
}