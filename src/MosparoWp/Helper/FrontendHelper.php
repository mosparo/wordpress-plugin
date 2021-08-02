<?php

namespace MosparoWp\Helper;

use Mosparo\ApiClient\Client;

class FrontendHelper
{
    private static $instance;

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

    public function registerResources()
    {
        $configHelper = ConfigHelper::getInstance();
        if (!$configHelper->isActive()) {
            return;
        }

        wp_enqueue_style(
            'mosparo-wp-mosparo-css',
            $this->getStylesheetUrl(),
            [],
            '1.0'
        );

        wp_enqueue_script(
            'mosparo-wp-mosparo-js',
            $this->getJavaScriptUrl(),
            [],
            '1.0',
            true
        );
    }

    public function getStylesheetUrl()
    {
        $configHelper = ConfigHelper::getInstance();
        return $configHelper->getHost() . '/build/mosparo-frontend.css';
    }

    public function getJavaScriptUrl()
    {
        $configHelper = ConfigHelper::getInstance();
        return $configHelper->getHost() . '/build/mosparo-frontend.js';
    }

    public function generateField()
    {
        $configHelper = ConfigHelper::getInstance();
        if (!$configHelper->getLoadResourcesAlways()) {
            $this->registerResources();
        }

        $instanceId = uniqid();
        $html = sprintf('
            <div id="mosparo-box-%s"></div>
            <script>
                window.onload = function(){
                    new mosparo("mosparo-box-%s", "%s", "%s", {});
                };
            </script>
        ', $instanceId, $instanceId, $configHelper->getHost(), $configHelper->getPublicKey());

        return $html;
    }
}