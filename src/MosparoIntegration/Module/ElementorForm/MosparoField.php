<?php

namespace MosparoIntegration\Module\ElementorForm;

use ElementorPro\Modules\Forms\Classes\Ajax_Handler;
use ElementorPro\Modules\Forms\Classes\Form_Record;
use ElementorPro\Plugin;
use MosparoIntegration\Helper\ConfigHelper;
use MosparoIntegration\Helper\FrontendHelper;
use MosparoIntegration\Helper\VerificationHelper;

class MosparoField
{
    private static $instance;

    protected $pluginDirectoryUrl;

    public static function getInstance($pluginDirectoryUrl = '')
    {
        if (empty(self::$instance)) {
            self::$instance = new self($pluginDirectoryUrl);
        }

        return self::$instance;
    }

    private function __construct($pluginDirectoryUrl)
    {
        $this->pluginDirectoryUrl = $pluginDirectoryUrl;
    }

    public function registerHooks()
    {
        add_action('elementor/init', [$this, 'initialize'], 10, 0);
        add_action('elementor/preview/enqueue_scripts', [$this, 'enqueuePreviewScripts']);
        add_action('elementor/editor/after_enqueue_scripts', [$this, 'enqueueEditorScripts']);
    }

    public function initialize()
    {
        add_filter('elementor_pro/forms/field_types', [$this, 'registerFieldType']);
        add_action('elementor_pro/forms/register/action', [$this, 'registerAction']);
        add_action('elementor/element/form/section_form_fields/before_section_end', [$this, 'updateControls']);
        add_action('elementor_pro/forms/render/item', [$this, 'filterItem']);
        add_action('elementor_pro/forms/render_field/mosparo', [$this, 'displayFormField'], 10, 3);
        add_filter('elementor_pro/editor/localize_settings', [$this, 'localizeSettings']);
        add_action('elementor_pro/forms/validation', [$this, 'verifyResponse'], 10, 2);
    }

    public function registerFieldType($fieldTypes)
    {
        $fieldTypes['mosparo'] = __('mosparo', 'mosparo-integration');

        return $fieldTypes;
    }

    public function registerAction($module)
    {
        $module->add_component('mosparo', $this);
    }

    public function updateControls($widget)
    {
        $elementor = Plugin::elementor();
        $controlData = $elementor->controls_manager->get_control_from_stack($widget->get_unique_name(), 'form_fields');

        if (is_wp_error($controlData)) {
            return;
        }

        foreach ($controlData['fields'] as $index => $field) {
            if ('required' === $field['name'] || 'width' === $field['name']) {
                $controlData['fields'][ $index ]['conditions']['terms'][] = [
                    'name' => 'field_type',
                    'operator' => '!in',
                    'value' => [
                        'mosparo',
                    ],
                ];
            }
        }

        $widget->update_control('form_fields', $controlData);
    }

    public function localizeSettings($settings)
    {
        $configHelper = ConfigHelper::getInstance();
        $connection = $configHelper->getConnectionFor('module_elementor-form');
        $host = '';
        $uuid = '';
        $publicKey = '';
        $frontendJsUrl = '';

        if ($connection) {
            $host = $connection->getHost();
            $uuid = $connection->getUuid();
            $publicKey = $connection->getPublicKey();

            $frontendHelper = FrontendHelper::getInstance();
            $frontendJsUrl = $frontendHelper->getJavaScriptUrl($connection);
        }

        $settings = array_replace_recursive($settings, [
            'forms' => [
                'mosparo' => [
                    'connectionAvailable' => ($connection !== false),
                    'mosparoHost' => $host,
                    'mosparoUuid' => $uuid,
                    'mosparoPublicKey' => $publicKey,
                    'messageConnectionRequired' => __('No mosparo connection available. Please configure the connection in the mosparo settings.', 'mosparo-integration'),
                    'frontendJsUrl' => $frontendJsUrl,
                ],
            ],
        ]);

        return $settings;
    }

    public function displayFormField($item, $idx, $widget)
    {
        $configHelper = ConfigHelper::getInstance();
        $connection = $configHelper->getConnectionFor('module_elementor-form');
        if ($connection === false) {
            return __('No mosparo connection available. Please configure the connection in the mosparo settings.', 'mosparo-integration');
        }

        $frontendHelper = FrontendHelper::getInstance();
        $options = [];
        if (Plugin::elementor()->editor->is_edit_mode()) {
            $options = ['designMode' => true];
        }

        $html = '<div class="elementor-field" id="form-field-' . $item['custom_id'] . '">';
        $html .= $frontendHelper->generateField($connection, $options, $this);
        $html .= '</div>';

        echo $html;
    }

    public function filterItem($item)
    {
        if ($item['field_type'] === 'mosparo') {
            $item['field_label'] = false;
        }

        return $item;
    }

    public function enqueuePreviewScripts()
    {
        $configHelper = ConfigHelper::getInstance();
        $connection = $configHelper->getConnectionFor('module_elementor-form');
        if ($connection === false) {
            return;
        }

        $frontendHelper = FrontendHelper::getInstance();
        wp_enqueue_script(
            'mosparo-frontend',
            $frontendHelper->getJavaScriptUrl($connection),
            [],
            '1.0',
            true
        );
    }

    public function enqueueEditorScripts()
    {
        wp_enqueue_script(
            'mosparo-elementor-form-script',
            $this->pluginDirectoryUrl . 'assets/module/elementor-form/js/mosparo.js',
            ['elementor-editor', 'jquery'],
            '1.0',
            true
        );
    }



    public function verifyResponse(Form_Record $record, Ajax_Handler $ajaxHandler)
    {
        // Stop the verification if the mosparo tag is not found in the form
        $mosparoField = $this->searchMosparoFieldInForm($record);
        if (!$mosparoField) {
            return;
        }

        $configHelper = ConfigHelper::getInstance();
        $connection = $configHelper->getConnectionFor('module_elementor-form');
        if ($connection === false) {
            return;
        }

        [ $formData, $requiredFields, $verifiableFields ] = $this->getFormData($record);

        // We have to access directly the global $_POST variable to get the tokens
        $submitToken = trim(sanitize_text_field($_POST['_mosparo_submitToken'] ?? ''));
        $validationToken = trim(sanitize_text_field($_POST['_mosparo_validationToken'] ?? ''));

        // If the tokens are not available, the submission cannot be valid.
        if (empty($submitToken) || empty($validationToken)) {
            $ajaxHandler->add_error(
                $mosparoField['id'],
                __('Submit or validation token is empty.', 'mosparo-integration')
            );

            return;
        }
        
        // Remove the mosparo fields from the form data
        $formData = array_filter($formData, function($key) {
            return strpos($key, '_mosparo_') === false;
        }, ARRAY_FILTER_USE_KEY);

        // Verify the submission
        $verificationHelper = VerificationHelper::getInstance();
        $verificationResult = $verificationHelper->verifySubmission($connection, $submitToken, $validationToken, $formData);
        if ($verificationResult !== null) {
            // Confirm that all required fields were verified
            $verifiedFields = array_keys($verificationResult->getVerifiedFields());
            $fieldDifference = array_diff($requiredFields, $verifiedFields);
            $verifiableFieldDifference = array_diff($verifiableFields, $verifiedFields);

            if ($verificationResult->isSubmittable() && empty($fieldDifference) && empty($verifiableFieldDifference)) {
                // Remove the mosparo field from the record since it is not needed in the process
                $record->remove_field($mosparoField['id']);
                return;
            }
        }

        // Everything else is spam
        $ajaxHandler->add_error(
            $mosparoField['id'],
            __('Verification failed which means the form contains spam.', 'mosparo-integration')
        );
    }

    protected function getFormData(Form_Record $record): array
    {
        $requiredFields = [];
        $verifiableFields = [];
        $ignoredTypes = apply_filters('mosparo_integration_elementor_form_ignored_field_types', [
            'checkbox',
            'radio',
            'acceptance',
            'hidden',
            'upload',
            'html',
            'mosparo',
            'password',
            'recaptcha',
            'recaptcha_v3',
            'honeypot',
            'step',
        ]);
        $verifiableFieldTypes = apply_filters('mosparo_integration_elementor_form_verifiable_field_types', [
            'text',
            'textarea',
            'email',
            'url',
        ]);

        $fields = $record->get('fields');

        foreach ($fields as $field) {
            if (in_array($field['type'], $ignoredTypes)) {
                continue;
            }

            $fieldName = 'form_fields[' . $field['id'] . ']';

            if ($field['required'] ?? false) {
                $requiredFields[] = $fieldName;
            }

            $value = $field['raw_value'];

            if ($value !== null) {
                $formData[$fieldName] = $value;
            }

            if (in_array($field['type'], $verifiableFieldTypes)) {
                $verifiableFields[] = $fieldName;
            }
        }

        $formData = apply_filters('mosparo_integration_elementor_form_form_data', $formData);

        return [ $formData, $requiredFields, $verifiableFields ];
    }

    protected function searchMosparoFieldInForm(Form_Record $record)
    {
        $fields = $record->get('fields');
        foreach ($fields as $field) {
            if ($field['type'] === 'mosparo') {
                return $field;
            }
        }

        return false;
    }
}
