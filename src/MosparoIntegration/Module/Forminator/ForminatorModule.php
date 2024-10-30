<?php

namespace MosparoIntegration\Module\Forminator;

use MosparoIntegration\Helper\ConfigHelper;
use MosparoIntegration\Helper\FrontendHelper;
use MosparoIntegration\Helper\VerificationHelper;
use MosparoIntegration\Module\AbstractModule;
use MosparoIntegration\Module\ModuleSettings;

class ForminatorModule extends AbstractModule
{
    protected $key = 'forminator';

    public function __construct()
    {
        $this->name = __('Forminator', 'mosparo-integration');
        $this->description = __('Protects Forminator forms with mosparo.', 'mosparo-integration');
        $this->dependencies = [
            'forminator' => ['name' => __('Forminator', 'mosparo-integration'), 'url' => 'https://wordpress.org/plugins/forminator/']
        ];
        $this->settings = new ModuleSettings(
            [
                'protected_forms' => [
                    'label' => __('Protected forms', 'mosparo-integration'),
                    'type' => 'choice_multiple',
                    'value' => [],
                    'defaultValue' => true,
                    'choices' => [$this, 'getChoices'],
                ]
            ],
            [
                'header' => __('Please choose which Forminator forms you want to protect with mosparo.', 'mosparo-integration'),
            ]
        );
    }

    public function canInitialize()
    {
        return class_exists('Forminator');
    }

    public function getChoices()
    {
        $choices = [];

        $forms = \Forminator_Form_Model::model()->get_all_models();
        foreach ($forms['models'] as $form) {
            $choices[$form->settings['formName']] = $form->id;
        }

        return $choices;
    }

    public function initializeModule($pluginDirectoryPath, $pluginDirectoryUrl)
    {
        add_filter('forminator_render_form_submit_markup', [$this, 'renderMosparoField'], 10, 4);
        add_filter('forminator_spam_protection', [$this, 'validateForm'], 10, 4);
    }

    public function renderMosparoField($html, $formId, $postId, $nonce)
    {
        $form = \Forminator_Form_Model::get_model($formId);
        if ($form instanceof \Forminator_Poll_Model || $form instanceof \Forminator_Quiz_Model) {
            // mosparo works only with Forminator forms, not polls and quizzes
            return $html;
        }

        // By default, we protect all forms except those manually disabled.
        $protectedFormIds = $this->getSettings()->getFieldValue('protected_forms');
        if (isset($protectedFormIds[$formId]) && !$protectedFormIds[$formId]) {
            return $html;
        }

        $configHelper = ConfigHelper::getInstance();
        $connection = $configHelper->getConnectionFor('module_forminator');
        if ($connection === false) {
            $html = __('No mosparo connection available. Please configure the connection in the mosparo settings.', 'mosparo-integration') . $html;
            return $html;
        }

        $options = [
            'inputFieldSelector' => '[name]:not(.mosparo__ignored-field):not(.forminator-calculation):not(.forminator-rating):not(.forminator-currency)'
        ];

        $frontendHelper = FrontendHelper::getInstance();

        $formRowStart = '<div class="forminator-row">';
        $formRowEnd = '</div>';
        $html = $formRowStart . $frontendHelper->generateField($connection, $options) . $formRowEnd . $html;

        return $html;
    }

    public function validateForm($isSpam, $fieldData, $formId, $moduleSlug)
    {
        $form = \Forminator_Form_Model::get_model($formId);
        if ($form instanceof \Forminator_Poll_Model || $form instanceof \Forminator_Quiz_Model) {
            // mosparo works only with Forminator forms, not polls and quizzes
            return $isSpam;
        }

        // By default, we protect all forms except those manually disabled.
        $protectedFormIds = $this->getSettings()->getFieldValue('protected_forms');
        if (isset($protectedFormIds[$formId]) && !$protectedFormIds[$formId]) {
            return $isSpam;
        }

        // Stop the verification if the mosparo tag is not found in the form
        $mosparoData = $this->searchMosparoDataInPostData($_POST);
        if (!$mosparoData) {
            return $isSpam;
        }

        $configHelper = ConfigHelper::getInstance();
        $connection = $configHelper->getConnectionFor('module_formidable');
        if ($connection === false) {
            return $isSpam;
        }

        [ $formData, $requiredFields, $verifiableFields ] = $this->getFormData($form, $fieldData);
        $submitToken = trim($mosparoData['_mosparo_submitToken'] ?? '');
        $validationToken = trim($mosparoData['_mosparo_validationToken'] ?? '');

        // If the tokens are not available, the submission cannot be valid.
        if (empty($submitToken) || empty($validationToken)) {
            return true;
        }

        // Verify the submission
        $verificationHelper = VerificationHelper::getInstance();
        $verificationResult = $verificationHelper->verifySubmission($connection, $submitToken, $validationToken, $formData);
        if ($verificationResult !== null) {
            // Confirm that all required fields were verified
            $verifiedFields = array_keys($verificationResult->getVerifiedFields());
            $fieldDifference = array_diff($requiredFields, $verifiedFields);
            $verifiableFieldDifference = array_diff($verifiableFields, $verifiedFields);

            if ($verificationResult->isSubmittable() && empty($fieldDifference) && empty($verifiableFieldDifference)) {
                return false;
            }
        }

        return true;
    }

    protected function getFormData(\Forminator_Form_Model $form, $fieldData)
    {
        $formData = [];
        $requiredFields = [];
        $verifiableFields = [];
        $processedFields = [];
        $ignoredTypes = apply_filters('mosparo_integration_forminator_ignored_field_types', [
            'checkbox',
            'radio',
            'html',
            'hidden',
            'calculation',
            'upload',
            'postdata',
            'captcha',
            'page-break',
            'section',
            'consent',
            'currency',
            'stripe',
            'paypal',
            'group',
            'slider',
            'rating',
        ]);
        $verifiableFieldTypes = apply_filters('mosparo_integration_forminator_verifiable_field_types', [
            'text',
            'textarea',
            'email',
            'url',
            'name',
            'address',
        ]);

        foreach ($fieldData as $field) {
            if (!$field['field_type'] || in_array($field['field_type'], $ignoredTypes)) {
                continue;
            }

            $fullKey = $field['name'];
            $processedFields[] = $fullKey;

            $value = $field['value'];

            if (is_array($value)) {
                foreach ($value as $subKey => $subValue) {
                    $formData[$fullKey . '-' . $subKey] = $subValue;

                    if ($field['field_array']['required'] ?? false) {
                        $requiredFields[] = $fullKey . '-' . $subKey;
                    }

                    if (in_array($field['field_type'], $verifiableFieldTypes)) {
                        $verifiableFields[] = $fullKey . '-' . $subKey;
                    }
                }
            } else {
                $formData[$fullKey] = $value;

                if ($field['field_array']['required'] ?? false) {
                    $requiredFields[] = $fullKey;
                }

                if (in_array($field['field_type'], $verifiableFieldTypes)) {
                    $verifiableFields[] = $fullKey;
                }
            }
        }

        // Add the empty fields because Forminator will not always return these in the validation data above.
        foreach ($form->get_fields_as_array() as $field) {
            if (in_array($field['id'], $processedFields) || in_array($field['type'], $ignoredTypes)) {
                continue;
            }

            if ($field['type'] === 'time') {
                $formData[$field['id'] . '-hours'] = null;
                $formData[$field['id'] . '-minutes'] = null;

                $type = $field['time_type'] ?? 'twelve';
                if ($type === 'twelve') {
                    $formData[$field['id'] . '-ampm'] = 'am';
                }
            } else {
                $formData[$field['id']] = null;
            }
        }

        $formData = apply_filters('mosparo_integration_forminator_form_data', $formData);

        return [ $formData, $requiredFields, $verifiableFields ];
    }

    protected function searchMosparoDataInPostData($postData)
    {
        $mosparoData = [];
        foreach ($postData as $key => $value) {
            if ($key === '_mosparo_submitToken' || $key === '_mosparo_validationToken') {
                $mosparoData[$key] = sanitize_text_field($value);
            }
        }

        if (count($mosparoData) !== 2) {
            return null;
        }

        return $mosparoData;
    }
}