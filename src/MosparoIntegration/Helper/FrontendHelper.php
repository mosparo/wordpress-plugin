<?php

namespace MosparoIntegration\Helper;

use JFB_Modules\Captcha\Abstract_Captcha\Base_Captcha_From_Options;
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
            return $response;
        }

        $fullCssUrl = sanitize_url(wp_remote_retrieve_body($response));
        if ($fullCssUrl != '') {
            set_transient($this->getCssUrlCacheTransientKey($connection), $fullCssUrl, 7 * 86400);
        }

        return true;
    }

    public function registerResources(Connection $connection)
    {
        wp_enqueue_script(
            'mosparo-integration-mosparo-js',
            $this->getJavaScriptUrl($connection),
            [],
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
        $instanceId = uniqid();

        $script = $this->getScript($connection, $instanceId, $options, $field);

        $html = sprintf('
            <div id="mosparo-box-%s"></div>
            <script>
                %s
            </script>',
            esc_attr($instanceId),
            $script
        );

        return $html;
    }

    public function getScript(Connection $connection, $instanceId, $options = [], $field = null)
    {
        $this->registerResources($connection);

        $options = $this->getFrontendOptions($options, $connection);
        $additionalCode = $this->prepareAdditionalJavaScriptCode($instanceId, $field);

        return sprintf('
            if (typeof mosparoInstances === "undefined") {
                var mosparoInstances = [];
            }
            
            (function () {
                let scriptEl = null;
                if (typeof mosparo === "undefined") {
                    scriptEl = document.createElement("script");
                    scriptEl.setAttribute("src", "%s");
                    document.body.appendChild(scriptEl);
                }
                
                let initializeMosparo = function (ev) {
                    let id = "mosparo-box-%s";
                    if (typeof mosparoInstances[id] !== "undefined") {
                        return;
                    }
                    
                    let mosparoFieldEl = document.getElementById(id);
                    if (!mosparoFieldEl) {
                        return;
                    }
                    
                    let formEl = null;
                    let el = mosparoFieldEl;
                    while ((el = el.parentNode) && el !== document) {
                        if (el.matches("form")) {
                            formEl = el;
                            break;
                        }
                    }
                
                    let options = %s;
                    let resetMosparoField = function () {
                        if (!mosparoInstances[id]) {
                            return;
                        }
                        
                        mosparoInstances[id].resetState();
                        mosparoInstances[id].requestSubmitToken();
                    };
                    
                    %s
                    
                    mosparoInstances[id] = new mosparo(id, "%s", "%s", "%s", options);
                };
                
                if (scriptEl !== null) {
                    scriptEl.addEventListener("load", function () {
                        initializeMosparo();
                    });
                } else if (document.readyState !== "loading") {
                    initializeMosparo();
                } else {
                    document.addEventListener("DOMContentLoaded", initializeMosparo);
                }
                document.addEventListener("mosparo_integration_initialize_fields", initializeMosparo);
                
                %s
            })();',
            $this->getJavaScriptUrl($connection),
            esc_attr($instanceId),
            wp_json_encode($options),
            $additionalCode['before'],
            esc_url($connection->getHost()),
            esc_attr($connection->getUuid()),
            esc_attr($connection->getPublicKey()),
            $additionalCode['after']
        );
    }

    protected function prepareAdditionalJavaScriptCode($instanceId, $field)
    {
        if (function_exists('wpcf7_add_form_tag') && $field instanceof ContactForm7MosparoField) {
            return [
                'before' => '
                    if (typeof wpcf7 !== "undefined" && mosparoFieldEl.closest(".wpcf7")) {
                        if (typeof wpcf7.cached !== "undefined" && wpcf7.cached) {
                            options.requestSubmitTokenOnInit = false;
                        }
                        
                        mosparoFieldEl.closest(".wpcf7").addEventListener("wpcf7spam", resetMosparoField);
                    }
                ',
                'after' => '',
            ];
        }

        if (defined('WPFORMS_VERSION') && $field instanceof WpFormsMosparoField) {
            return [
                'before' => '
                    if (typeof wpforms !== "undefined" && mosparoFieldEl.closest(".wpforms-form")) {
                        mosparoFieldEl.closest(".wpforms-form").addEventListener("wpformsAjaxSubmitFailed", resetMosparoField);
                    }
                ',
                'after' => ''
            ];
        }

        if (class_exists('GF_Field') && $field instanceof GF_Field) {
            $afterCode = '';
            if (is_admin()) {
                $afterCode = sprintf('
                    document.addEventListener("gform_field_added", function (event, form, field) {
                        if (field["type"] === "mosparo") {
                            initializeMosparo();
                        }
                    });
                    
                    if (typeof gform !== "undefined") {
                        gform.addAction("gform_after_refresh_field_preview", function (fieldId) {
                            let id = "mosparo-box-%s";
                            let eventFieldId = %d;
                            
                            if (fieldId !== eventFieldId) {
                                return;
                            }
                            
                            delete mosparoInstances[id];
                            
                            initializeMosparo();
                        });
                    }',
                    $instanceId,
                    $field->id
                );
            }

            return [
                'before' => '
                    // Remove the existing mosparo box if the initialization is executed again.
                    // This can happen if the form is submitted by AJAX and a validation error occurred.
                    if (mosparoFieldEl.querySelector(".mosparo__row") !== null) {
                        mosparoFieldEl.querySelector(".mosparo__row").remove();
                    }
                    
                    options.onGetFormData = function (formEl, formData) {
                        // Remove the version_hash ignored field since it was not available
                        // when the validation was executed.
                        const vhIndex = formData.ignoredFields.indexOf("version_hash");
                        if (vhIndex > -1) {
                            formData.ignoredFields.splice(vhIndex, 1);    
                        }
                        
                        return formData;
                    };
                    
                    options.doSubmitFormInvisible = function () {
                        formEl.submit();
                    };
                    
                    options.onValidateFormInvisible = function () {
                        let formId = formEl.getAttribute("data-formid");
                        window["gf_submitting_" + formId] = false;
                        jQuery("#gform_ajax_spinner_" + formId).remove();
                    };
                ',
                'after' => sprintf('
                    document.addEventListener("gform/theme/scripts_loaded", (ev) => {
                        gform.utils.addAsyncFilter("gform/submission/pre_submission", async (data) => {
                            if (typeof data.form === "undefined" || parseInt(data.form.getAttribute("data-formid")) !== %d) {
                                return data;
                            }
                            
                            // Get the ID from the form directly, because if the form is included with AJAX, the mosparo
                            // field receives a new ID everytime the form is submitted.
                            const mosparoEl = data.form.querySelector(".mosparo__container");
                            id = mosparoEl.getAttribute("id");
                            
                            if (typeof mosparoInstances[id] === "undefined") {
                                return data;
                            }
                            
                            if (!mosparoInstances[id].checkboxFieldElement.checked || !mosparoInstances[id].verifyCheckedFormData()) {
                                data.abort = true;
                                mosparoInstances[id].onSubmit(ev);
                            }
                            
                            return data;
                        });
                    });
                ', $field->formId) . $afterCode,
            ];
        }

        if (function_exists('elementor_pro_load_plugin') && $field instanceof ElementorFormMosparoField) {
            return [
                // The popup fix searches for hidden popups and skips the initialization of the mosparo box
                // until the popup is visible. This is required because Elementor removes the popup from the DOM tree.
                // If mosparo requested the submit token but Elementor removed the popup from the tree, mosparo is
                // not able to finalize the initialization.
                // When the popup is opened, the regular initialization script is executed again, and mosparo gets
                // correctly initialized.
                'before' => '
                    formEl.addEventListener("error", function () {
                        if (!mosparoInstances[id]) {
                            return;
                        }
                        
                        mosparoInstances[id].resetState();
                        mosparoInstances[id].requestSubmitToken();
                    });
                    
                    let pEl = mosparoFieldEl;
                    let popupEl = null;
                    while ((pEl = pEl.parentNode) && pEl !== document) {
                        if (pEl.matches(\'[data-elementor-type="popup"]\')) {
                            popupEl = pEl;
                            break;
                        }
                    }
                    
                    if (popupEl !== null && popupEl.parentNode.matches("body")) {
                        return;
                    }
                ',
                'after' => sprintf('
                    window.addEventListener("elementor/popup/hide", function (ev) {
                        let id = "mosparo-box-%s";
                        delete mosparoInstances[id];
                    });
                ', $instanceId),
            ];
        }

        if (class_exists('JFB_Modules\Captcha\Abstract_Captcha\Base_Captcha_From_Options') && $field instanceof Base_Captcha_From_Options) {
            return [
                'before' => '
                    options.onBeforeGetFormData = function (formElement) {
                        if (formElement.querySelectorAll(".wp-editor-area").length) {
                            window.tinyMCE.triggerSave();
                        }
                    };
                    ',
                'after' => '',
            ];
        }

        if ($field === 'wsform') {
            return [
                'before' => '
                    options.onGetFormData = function (formElement, formData) {
                        let ignoredFields = [];
                        for (let key of formData.ignoredFields) {
                            if (key.indexOf("wsf_") === -1) {
                                ignoredFields.push(key);
                            }
                        }
                        
                        formData.ignoredFields = ignoredFields;
                        
                        return formData;
                    };
                ',
                'after' => sprintf('
                    jQuery(document).on("wsf-rendered", function (event, formObject, formId, instanceId, eventFormEl, formCanvasEl) {
                        let mosparoEl = jQuery("#mosparo-box-%s");
                        let formEl = mosparoEl.parents("form");
                        if (formEl.data("id") == formId) {
                            initializeMosparo();
                        }
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
