<?php

namespace MosparoIntegration\Helper;

use MosparoIntegration\Entity\Connection;
use MosparoIntegration\Module\ContactForm7\MosparoField as ContactForm7MosparoField;
use MosparoIntegration\Module\ElementorForm\MosparoField as ElementorFormMosparoField;
use MosparoIntegration\Module\WPForms\MosparoField as WpFormsMosparoField;
use WP_Error;
use GF_Field;

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
        add_action('mosparo_integration_refresh_css_url_cache', [$this, 'refreshCssUrlCacheForAllConnections']);
    }

    public function initializeResourceRegistration()
    {
        add_action('wp_enqueue_scripts', [$this, 'registerResources']);
    }

    public function refreshCssUrlCacheForAllConnections()
    {
        $configHelper = ConfigHelper::getInstance();
        $connections = $configHelper->getConnections();

        foreach ($connections as $connection) {
            $this->refreshCssUrlCache($connection);
        }
    }

    public function clearCssUrlCache(Connection $connection)
    {
        delete_transient($this->getCssUrlCacheTransientKey($connection));
    }

    public function refreshCssUrlCache(Connection $connection)
    {
        $host = $connection->getHost();
        $uuid = $connection->getUuid();
        $sslVerify = $connection->shouldVerifySsl();

        $url = sprintf('%s/resources/%s/url', $host, $uuid);

        $response = wp_remote_get($url, ['sslverify' => $sslVerify]);
        if ($response instanceof WP_Error) {
            return;
        }

        $fullCssUrl = sanitize_url(wp_remote_retrieve_body($response));
        if ($fullCssUrl != '') {
            set_transient($this->getCssUrlCacheTransientKey($connection), $fullCssUrl, 7 * 86400);
        }
    }

    public function registerResources(Connection $connection)
    {
        wp_enqueue_script(
            'mosparo-integration-mosparo-js',
            $this->getJavaScriptUrl($connection),
            ['jquery'],
            '1.0',
            true
        );
    }

    public function getStylesheetUrl(Connection $connection)
    {
        $fullCssUrl = get_transient($this->getCssUrlCacheTransientKey($connection));

        if ($fullCssUrl == '') {
            $host = $connection->getHost();
            $uuid = $connection->getUuid();

            $fullCssUrl = sprintf('%s/resources/%s.css', $host, $uuid);
        }

        return $fullCssUrl;
    }

    public function getJavaScriptUrl(Connection $connection)
    {
        return $connection->getHost() . '/build/mosparo-frontend.js';
    }

    public function getFrontendOptions($options, Connection $connection)
    {
        $options['loadCssResource'] = true;
        $options['cssResourceUrl'] = esc_url($this->getStylesheetUrl($connection));

        return apply_filters('mosparo_integration_filter_frontend_options', $options);
    }

    public function displayDummy()
    {
        return '<div style="width: 400px; height: 80px; border: 2px solid #888888; border-radius: 10px; display: flex; justify-content: center; align-items: center">mosparo</div>';
    }

    public function isGutenbergRequest()
    {
        return (defined('REST_REQUEST') && REST_REQUEST && !empty($_REQUEST['context']) && 'edit' === sanitize_key($_REQUEST['context']));
    }

    public function generateField(Connection $connection, $options = [], $field = null)
    {
        $this->registerResources($connection);

        $instanceId = uniqid();

        $options = $this->getFrontendOptions($options, $connection);
        $additionalCode = $this->prepareAdditionalJavaScriptCode($instanceId, $field);

        $html = sprintf('
            <div id="mosparo-box-%s"></div>
            <script>
                if (typeof mosparoInstances == "undefined") {
                    var mosparoInstances = [];
                }
                
                (function () {
                    let initializeMosparo = function () {
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
                        
                        if (typeof mosparoInstances[id] !== "undefined") {
                            return;
                        }
                        
                        %s
                        
                        mosparoInstances[id] = new mosparo(id, "%s", "%s", "%s", options);
                    };
                    
                    document.addEventListener("DOMContentLoaded", initializeMosparo);
                    
                    %s
                })();
            </script>',
            esc_attr($instanceId),
            esc_attr($instanceId),
            wp_json_encode($options),
            $additionalCode['before'],
            esc_url($connection->getHost()),
            esc_attr($connection->getUuid()),
            esc_attr($connection->getPublicKey()),
            $additionalCode['after']
        );

        return $html;
    }

    protected function prepareAdditionalJavaScriptCode($instanceId, $field)
    {
        if (function_exists('wpcf7_add_form_tag') && $field instanceof ContactForm7MosparoField) {
            return [
                'before' => '
                    if (typeof wpcf7 !== "undefined" && formEl.closest(".wpcf7").length > 0) {
                        if (typeof wpcf7.cached !== "undefined" && wpcf7.cached) {
                            options.requestSubmitTokenOnInit = false;
                        }
                        
                        formEl.closest(".wpcf7").on("wpcf7spam", resetMosparoField);
                    }
                ',
                'after' => '',
            ];
        }

        if (defined('WPFORMS_VERSION') && $field instanceof WpFormsMosparoField) {
            return [
                'before' => '
                    if (typeof wpforms !== "undefined" && formEl.closest(".wpforms-form").length > 0) {
                        formEl.closest(".wpforms-form").on("wpformsAjaxSubmitFailed", resetMosparoField);
                    }
                ',
                'after' => ''
            ];
        }

        if (class_exists('GF_Field') && $field instanceof GF_Field) {
            return [
                'before' => '',
                'after' => sprintf('
                    jQuery(document).on("gform_field_added", function(event, form, field) {
                        if (field["type"] === "mosparo") {
                            initializeMosparo();
                        }
                    });
                    
                    gform.addAction("gform_after_refresh_field_preview", function (fieldId) {
                        let id = "mosparo-box-%s";
                        let eventFieldId = %d;
                        
                        if (fieldId !== eventFieldId) {
                            return;
                        }
                        
                        delete mosparoInstances[id];
                        
                        initializeMosparo();
                    });', $instanceId, $field->id),
            ];
        }

        if (function_exists('elementor_pro_load_plugin') && $field instanceof ElementorFormMosparoField) {
            return [
                'before' => '',
                'after' => sprintf('
                    jQuery(document).ready(function () {
                        initializeMosparo();
                    
                        let id = "mosparo-box-%s";
                        jQuery("#" + id).parents("form").on("error", function () {
                            if (!mosparoInstances[id]) {
                                return;
                            }
                            
                            mosparoInstances[id].resetState();
                            mosparoInstances[id].requestSubmitToken();
                        });
                    });
                ', $instanceId),
            ];
        }

        return ['before' => '', 'after' => ''];
    }

    protected function getCssUrlCacheTransientKey(Connection $connection)
    {
        return self::MOSPARO_FULL_CSS_URL_TRANSIENT_KEY . '_' . $connection->getKey();
    }
}
