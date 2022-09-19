<?php

namespace MosparoWp\Helper;

use Mosparo\ApiClient\Client;
use WP_Error;

class FrontendHelper
{
    const MOSPARO_FULL_CSS_URL_TRANSIENT_KEY = 'mosparo_full_css_resource_url';

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

    public function initializeScheduleEvents()
    {
        add_action('mosparo_wp_refresh_css_url_cache', [$this, 'refreshCssUrlCache']);
    }

    public function initializeResourceRegistration()
    {
        add_action('wp_enqueue_scripts', [$this, 'registerResources']);
    }

    public function clearCssUrlCache()
    {
        delete_transient(self::MOSPARO_FULL_CSS_URL_TRANSIENT_KEY);
    }

    public function refreshCssUrlCache()
    {
        $configHelper = ConfigHelper::getInstance();
        $host = $configHelper->getHost();
        $uuid = $configHelper->getUuid();
        $sslVerify = $configHelper->getVerifySsl();

        $url = sprintf('%s/resources/%s/url', $host, $uuid);

        $response = wp_remote_get($url, ['sslverify' => $sslVerify]);
        if ($response instanceof WP_Error) {
            return;
        }

        $fullCssUrl = wp_remote_retrieve_body($response);
        if ($fullCssUrl != '') {
            set_transient(self::MOSPARO_FULL_CSS_URL_TRANSIENT_KEY, $fullCssUrl, 7 * 86400);
        }
    }

    public function registerResources()
    {
        $configHelper = ConfigHelper::getInstance();
        if (!$configHelper->isActive()) {
            return;
        }

        if (!$configHelper->getLoadCssResourceOnInitialization()) {
            wp_enqueue_style(
                'mosparo-wp-mosparo-css',
                $this->getStylesheetUrl(),
                [],
                '1.0'
            );
        }

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
        $fullCssUrl = get_transient(self::MOSPARO_FULL_CSS_URL_TRANSIENT_KEY);

        if ($fullCssUrl == '') {
            $configHelper = ConfigHelper::getInstance();
            $host = $configHelper->getHost();
            $uuid = $configHelper->getUuid();

            $fullCssUrl = sprintf('%s/resources/%s.css', $host, $uuid);
        }

        return $fullCssUrl;
    }

    public function getJavaScriptUrl()
    {
        $configHelper = ConfigHelper::getInstance();
        return $configHelper->getHost() . '/build/mosparo-frontend.js';
    }

    public function getFrontendOptions($options, ConfigHelper $configHelper)
    {
        if ($configHelper->getLoadCssResourceOnInitialization()) {
            $options['loadCssResource'] = true;
            $options['cssResourceUrl'] = $this->getStylesheetUrl();
        }

        return apply_filters('mosparo_wp_filter_frontend_options', $options);
    }

    public function generateField($options = [])
    {
        $configHelper = ConfigHelper::getInstance();
        if (!$configHelper->getLoadResourcesAlways()) {
            $this->registerResources();
        }

        $options = $this->getFrontendOptions($options, $configHelper);

        $instanceId = uniqid();
        $html = sprintf('
            <div id="mosparo-box-%s"></div>
            <script>
                if (typeof mosparoInstances == "undefined") {
                    var mosparoInstances = [];
                }
                
                document.addEventListener("DOMContentLoaded", function () {
                    let id = "mosparo-box-%s";
                    let formEl = jQuery("#" + id);
                    let options = %s;
                    let resetMosparoField = function () {
                        if (!mosparoInstances[id]) {
                            return;
                        }
                        
                        mosparoInstances[id].resetState();
                        mosparoInstances[id].requestSubmitToken();
                    };
                    
                    // Contact Form 7
                    if (typeof wpcf7 !== "undefined" && formEl.closest(".wpcf7").length > 0) {
                        if (typeof wpcf7.cached !== "undefined" && wpcf7.cached) {
                            options.requestSubmitTokenOnInit = false;
                        }
                        
                        formEl.closest(".wpcf7").on("wpcf7spam", resetMosparoField);
                    }
                    
                    // WP Forms
                    if (typeof wpforms !== "undefined" && formEl.closest(".wpforms-form").length > 0) {
                        jQuery(formEl).closest(".wpforms-form").on("wpformsAjaxSubmitFailed", resetMosparoField);
                    }
                    
                    mosparoInstances[id] = new mosparo(id, "%s", "%s", "%s", options);
                });
            </script>
        ', $instanceId, $instanceId, json_encode($options), $configHelper->getHost(), $configHelper->getUuid(), $configHelper->getPublicKey());

        return $html;
    }
}
