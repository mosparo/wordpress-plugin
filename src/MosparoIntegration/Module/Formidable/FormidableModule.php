<?php

namespace MosparoIntegration\Module\Formidable;

use MosparoIntegration\Helper\VerificationHelper;
use MosparoIntegration\Module\AbstractModule;

class FormidableModule extends AbstractModule
{
    protected $key = 'formidable';

    public function __construct()
    {
        $this->name = __('Formidable', 'mosparo-integration');
        $this->description = __('Protects Formidable forms with mosparo.', 'mosparo-integration');
        $this->dependencies = [
            'formidable' => ['name' => __('Formidable', 'mosparo-integration'), 'url' => 'https://wordpress.org/plugins/formidable/']
        ];
    }

    public function initializeModule($pluginDirectoryPath, $pluginDirectoryUrl)
    {
        if (!function_exists('load_formidable_forms')) {
            return;
        }

        add_filter('frm_available_fields', [$this, 'addFieldType']);
        add_filter('frm_get_field_type_class', [$this, 'getFieldClass'], 10, 2);
        add_filter('frm_validate_entry', [$this, 'validateForm'], 10, 3);
    }

    public function addFieldType($fields)
    {
        $fields['mosparo'] = [
            'name' => 'mosparo',
            'icon' => 'frm_icon_font frm_shield_check_icon',
        ];

        return $fields;
    }

    public function getFieldClass($class, $fieldType)
    {
        if ($fieldType === 'mosparo') {
            return MosparoField::class;
        }

        return $class;
    }

    public function validateForm($errors, $values, $data)
    {
        [ $formData, $requiredFields ] = $this->getFormData($values, $data['posted_fields']);
        $submitToken = trim(sanitize_text_field($values['_mosparo_submitToken'] ?? ''));
        $validationToken = trim(sanitize_text_field($values['_mosparo_validationToken'] ?? ''));

        // If the tokens are not available, the submission cannot be valid.
        if (empty($submitToken) || empty($validationToken)) {
            $errors['form'] = __('Submit or validation token is empty.', 'mosparo-integration');
            return $errors;
        }

        // Remove the mosparo fields from the form data
        $formData = array_filter($formData, function($key) {
            return strpos($key, '_mosparo_') === false;
        }, ARRAY_FILTER_USE_KEY);

        // Verify the submission
        $verificationHelper = VerificationHelper::getInstance();
        $verificationResult = $verificationHelper->verifySubmission($submitToken, $validationToken, $formData);
        if ($verificationResult !== null) {
            // Confirm that all required fields were verified
            $verifiedFields = array_keys($verificationResult->getVerifiedFields());
            $fieldDifference = array_diff($requiredFields, $verifiedFields);

            if ($verificationResult->isSubmittable() && empty($fieldDifference)) {
                return $errors;
            }
        }

        $errors['form'] = __('Verification failed which means the form contains spam.', 'mosparo-integration');
        return $errors;
    }

    protected function getFormData($values, $postedFields): array
    {
        $formData = [];
        $requiredFields = [];
        $ignoredTypes = apply_filters('mosparo_integration_cf7_ignored_field_types', [
            'checkbox',
            'radio',
            'select',
            'html',
            'hidden',
            'user_id',
            'captcha',
            'mosparo',
            'file',
            'date',
            'time',
            'scale',
            'star',
            'range',
            'toggle',
            'data',
            'lookup',
            'divider|repeat',
            'divider',
            'break',
            'form',
            'likert',
            'nps',
            'password',
            'tag',
            'credit_card',
            'summary',
            'signature',
            'ssa-appointment',
            'product',
            'quantity',
            'total',
        ]);

        foreach ($postedFields as $key => $field) {
            if (in_array($field->type, $ignoredTypes)) {
                continue;
            }

            $fullKey = 'item_meta[' . $field->id . ']';

            if ($field->required ?? false) {
                $requiredFields[] = $fullKey;
            }

            $formData[$fullKey] = $values['item_meta'][$field->id];
        }

        if (isset($values['frm_verify'])) {
            $formData['frm_verify'] = $values['frm_verify'];
        }

        $formData = apply_filters('mosparo_integration_formidable_form_data', $formData);

        return [ $formData, $requiredFields ];
    }
}