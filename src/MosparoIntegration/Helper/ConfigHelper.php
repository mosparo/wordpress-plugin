<?php

namespace MosparoIntegration\Helper;

use MosparoIntegration\Module\AbstractModule;
use MosparoIntegration\Entity\Connection;

class ConfigHelper
{
    const ORIGIN_WP_CONFIG = 'c';
    const ORIGIN_NETWORK = 'n';
    const ORIGIN_LOCAL = 'l';

    private static $instance;
    private $config = [];

    private $networkConfig = [];

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
            $connection->setVerifySsl(boolval($this->config['verifySsl'] ?? ''));
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

        if (is_multisite()) {
            $connections = array_merge($connections, $this->networkConfig['connections'] ?? []);
        }

        if (defined('WP_MOSPARO_HOST')) {
            $connection = new Connection();
            $connection->setKey('mc__wp_config');
            $connection->setName(__('mosparo Connection configured in wp-config.php', 'mosparo-integration'));
            $connection->setHost(defined('WP_MOSPARO_HOST') ? WP_MOSPARO_HOST : '');
            $connection->setUuid(defined('WP_MOSPARO_UUID') ? WP_MOSPARO_UUID : '');
            $connection->setPublicKey(defined('WP_MOSPARO_PUBLIC_KEY') ? WP_MOSPARO_PUBLIC_KEY : '');
            $connection->setPrivateKey(defined('WP_MOSPARO_PRIVATE_KEY') ? WP_MOSPARO_PRIVATE_KEY : '');
            $connection->setVerifySsl(boolval(defined('WP_MOSPARO_VERIFY_SSL') ? WP_MOSPARO_VERIFY_SSL : true));
            $connection->setOrigin(self::ORIGIN_WP_CONFIG);
            $connection->setDefaults([
                'general'
            ]);

            $connections[$connection->getKey()] = $connection;
        }

        return $connections;
    }

    public function getDefaultConnections()
    {
        $connections = $this->getConnections();
        $defaultConnections = [];
        foreach ($connections as $connection) {
            foreach ($connection->getDefaults() as $defaultKey) {
                if (!isset($defaultConnections[$defaultKey]) || $connection->getOriginPriority() > $defaultConnections[$defaultKey]->getOriginPriority()) {
                    $defaultConnections[$defaultKey] = $connection;
                }
            }
        }

        return $defaultConnections;
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
        $connections = $this->getConnections();

        return isset($connections[$key]);
    }

    public function getConnection($key)
    {
        $connections = $this->getConnections();

        return $connections[$key] ?? false;
    }

    public function getConnectionFor($key, $fallbackToGeneral = true)
    {
        return $this->getDefaultConnectionFor($key, $fallbackToGeneral);
    }

    public function getDefaultConnectionFor($key, $fallbackToGeneral = true)
    {
        $defaultConnections = $this->getDefaultConnections();

        if (isset($defaultConnections[$key]) && $defaultConnections[$key]) {
            return $defaultConnections[$key];
        }

        if ($fallbackToGeneral && isset($defaultConnections['general']) && $defaultConnections['general']) {
            return $defaultConnections['general'];
        }

        return false;
    }

    public function isConnectionDefaultConnectionFor($connection, $key, $fallbackToGeneral = true)
    {
        $defaultConnection = $this->getDefaultConnectionFor($key, $fallbackToGeneral);
        if ($defaultConnection === false) {
            return false;
        }

        return ($defaultConnection->getKey() === $connection->getKey());
    }

    public function resetDefaultConnections($defaults)
    {
        $targetOrigin = self::ORIGIN_LOCAL;
        if (is_multisite() && is_network_admin()) {
            $targetOrigin = self::ORIGIN_NETWORK;
        }

        $connections = $this->getConnections();

        foreach ($connections as $connection) {
            foreach ($defaults as $default) {
                if ($connection->isDefaultFor($default) && $connection->getOrigin() === $targetOrigin) {
                    $connection->removeDefault($default);
                }
            }
        }
    }

    public function hasAccessToConnection($connectionKey)
    {
        foreach ($this->getConnections() as $connection) {
            if ($connection->getKey() !== $connectionKey) {
                continue;
            }

            // A user can never edit the connection configured by wp-config.php
            if ($connection->getOrigin() === self::ORIGIN_WP_CONFIG) {
                return false;
            }

            // If it's a multisite setup, and it's in the network admin and the connection origin is the network,
            // the user has access to the connection.
            if (is_multisite() && is_network_admin() && $connection->getOrigin() === self::ORIGIN_NETWORK) {
                return true;
            }

            // If it's a local connection, and not a multisite setup or not in the network admin, the user has
            // access to the connection.
            if ((!is_multisite() || !is_network_admin()) && $connection->getOrigin() === self::ORIGIN_LOCAL) {
                return true;
            }
        }

        return false;
    }

    public function isModuleActive($moduleKey)
    {
        if (in_array($moduleKey,$this->config['modules'] ?? [])) {
            return true;
        }

        if (is_multisite() && in_array($moduleKey, $this->networkConfig['modules'] ?? [])) {
            return true;
        }
        
        return false;
    }

    public function getOriginOfModuleActivation($moduleKey)
    {
        if (is_multisite() && !is_network_admin() && in_array($moduleKey,$this->networkConfig['modules'] ?? [])) {
            return self::ORIGIN_NETWORK;
        }

        if (in_array($moduleKey, $this->config['modules'] ?? [])) {
            return self::ORIGIN_LOCAL;
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
            // Also remove module settings on disable
            unset($this->config['modules-settings'][$moduleKey]);
        }
    }

    public function getTypedValue($value, $type = 'text')
    {
        switch ($type) {
            case "boolean":
                return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            case "number":
                return filter_var($value, FILTER_VALIDATE_INT, ['options' => ['default' => 0]]);
            case "string":
            case "text":
            default:
                break;
        }

        return strval($value);
    }

    public function loadModuleConfiguration(AbstractModule $module): array
    {
        $moduleSettings = $module->getSettings();
        if (!$moduleSettings) {
            return [];
        }

        $moduleConfig = [];

        // First, load the network config and set it in the array
        if (is_multisite() && !empty($this->networkConfig) && isset($this->networkConfig['modules-settings'][$module->getKey()])) {
            $moduleConfig = $this->networkConfig['modules-settings'][$module->getKey()];
        }

        // Secondly, if we have website settings, merge them with the network settings and override the network settings
        if (isset($this->config['modules-settings'][$module->getKey()])) {
            $moduleConfig = array_merge($moduleConfig, $this->config['modules-settings'][$module->getKey()]);
        }

        $fields = $moduleSettings->getFields();
        foreach ($fields as $key => $setting) {
            $v = null;

            if (isset($moduleConfig[$key])) {
                $v = $moduleConfig[$key];
            }

            if ($v !== null) {
                $fields[$key]['value'] = $this->getTypedValue($v, $setting['type']);
            }
        }

        $moduleSettings->setSettings(apply_filters('mosparo_integration_filter_module_settings', $fields, $module->getKey()));

        return $moduleSettings->getSettings();
    }

    public function saveModuleConfiguration(AbstractModule $module)
    {
        $moduleSettings = $module->getSettings();
        if (!$moduleSettings) {
            return;
        }

        if (!isset($this->config['modules-settings'])) {
            $this->config['modules-settings'] = [];
        }

        if (!isset($this->config['modules-settings'][$module->getKey()])) {
            $this->config['modules-settings'][$module->getKey()] = [];
        }

        foreach ($moduleSettings->getFields() as $key => $setting) {
            $formKey = $module->getKey() . '_' . $key;
            $v = '';

            if (isset($_POST[$formKey])) {
                $v = sanitize_text_field($_POST[$formKey]);
            }

            $this->config['modules-settings'][$module->getKey()][$key] = $this->getTypedValue($v, $setting['type']);
        }

        $this->saveConfiguration();
    }

    public function loadConfiguration()
    {
        if (is_multisite() && is_network_admin()) {
            $this->config = get_site_option('mosparo-integration-network-configuration', []);
        } else {
            $this->config = get_option('mosparo-integration-configuration', []);

            if (is_multisite()) {
                $this->networkConfig = get_site_option('mosparo-integration-network-configuration', []);
            }
        }
    }

    public function saveConfiguration()
    {
        $this->config['lastChangedAt'] = new \DateTime();

        if (is_multisite() && is_network_admin()) {
            update_site_option('mosparo-integration-network-configuration', $this->config);
        } else {
            update_option('mosparo-integration-configuration', $this->config);
        }
    }
}
