<?php

namespace MosparoIntegration\Module\Formidable;

use MosparoIntegration\Helper\ConfigHelper;
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

    public function canInitialize()
    {
        return function_exists('load_formidable_forms');
    }

    public function initializeModule($pluginDirectoryPath, $pluginDirectoryUrl)
    {
        add_filter('frm_available_fields', [$this, 'addFieldType']);
        add_filter('frm_get_field_type_class', [$this, 'getFieldClass'], 10, 2);
        add_filter('frm_validate_entry', [$this, 'validateForm'], 10, 3);
    }

    public function addFieldType($fields)
    {
        $fields['mosparo_field'] = [
            'name' => 'mosparo',
            'icon' => 'frm_icon_font frm_shield_check_icon',
        ];

        return $fields;
    }

    public function getFieldClass($class, $fieldType)
    {
        if ($fieldType === 'mosparo_field') {
            return MosparoField::class;
        }

        return $class;
    }

    public function validateForm($errors, $values, $data)
    {
        // Stop the verification if the mosparo tag is not found in the form
        if (!$this->searchMosparoFieldInForm($data['posted_fields'])) {
            return $errors;
        }

        $configHelper = ConfigHelper::getInstance();
        $connection = $configHelper->getConnectionFor('module_formidable');
        if ($connection === false) {
            return $errors;
        }

        [ $formData, $requiredFields, $verifiableFields ] = $this->getFormData($values, $data['posted_fields']);
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
        $verificationResult = $verificationHelper->verifySubmission($connection, $submitToken, $validationToken, $formData);
        if ($verificationResult !== null) {
            // Confirm that all required fields were verified
            $verifiedFields = array_keys($verificationResult->getVerifiedFields());
            $fieldDifference = array_diff($requiredFields, $verifiedFields);
            $verifiableFieldDifference = array_diff($verifiableFields, $verifiedFields);

            if ($verificationResult->isSubmittable() && empty($fieldDifference) && empty($verifiableFieldDifference)) {
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
        $verifiableFields = [];
        $ignoredTypes = apply_filters('mosparo_integration_formidable_ignored_field_types', [
            'checkbox',
            'radio',
            'select',
            'html',
            'hidden',
            'user_id',
            'captcha',
            'mosparo_field',
            'file',
            'scale',
            'star',
            'range',
            'toggle',
            'break',
            'likert',
            'end_divider',
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
        $verifiableFieldTypes = apply_filters('mosparo_integration_formidable_verifiable_field_types', [
            'text',
            'textarea',
            'email',
            'url',
            'name',
        ]);

        foreach ($postedFields as $key => $field) {
            if (in_array($field->type, $ignoredTypes)) {
                continue;
            }

            $fullKey = 'item_meta[' . $field->id . ']';

            $value = $values['item_meta'][$field->id] ?? '';

            if ($field->type === 'divider' || $field->type === 'form') {
                $subFields = \FrmField::get_all_for_form($field->field_options['form_select']);

                foreach ($value as $subKey => $subValues) {
                    if ($subKey === 'form' || $subKey === 'row_ids') {
                        continue;
                    }

                    $subValKey = $fullKey . '[' . $subKey . ']';
                    foreach ($subFields as $subField) {
                        if (in_array($subField->type, $ignoredTypes)) {
                            continue;
                        }

                        $fullSubKey = $subValKey . '[' . $subField->id . ']';

                        if ($subField->required == 1) {
                            $requiredFields[] = $fullSubKey;
                        }

                        if (in_array($subField->type, $verifiableFieldTypes)) {
                            $verifiableFields[] = $fullSubKey;
                        }

                        $formData[$fullSubKey] = $subValues[$subField->id];
                    }
                }
            } else if (is_array($value) && isset($value['row_ids'])) {
                foreach ($value['row_ids'] as $idx) {
                    $row = $value[$idx];
                    $rowKey = $fullKey . '[' . $idx . ']';

                    foreach ($row as $subKey => $subValue) {
                        if ($subKey === 0) {
                            continue;
                        }

                        $fullSubKey = $rowKey . '[' . $subKey . ']';
                        $formData[$fullSubKey] = $subValue;

                        if ($field->required ?? false) {
                            $requiredFields[] = $fullSubKey;
                        }

                        if (in_array($field->type, $verifiableFieldTypes)) {
                            $verifiableFields[] = $fullSubKey;
                        }
                    }
                }
            } else if (is_array($value) && !isset($value['row_ids'])) {
                foreach ($value as $subKey => $val) {
                    $fullSubKey = $fullKey . '[' . $subKey . ']';
                    $formData[$fullSubKey] = $val;

                    if ($field->required ?? false) {
                        $requiredFields[] = $fullSubKey;
                    }

                    if (in_array($field->type, $verifiableFieldTypes)) {
                        $verifiableFields[] = $fullSubKey;
                    }
                }
            } else {
                $formData[$fullKey] = $value;

                if ($field->required ?? false) {
                    $requiredFields[] = $fullKey;
                }

                if (in_array($field->type, $verifiableFieldTypes)) {
                    $verifiableFields[] = $fullKey;
                }
            }
        }

        if (isset($values['frm_verify'])) {
            $formData['frm_verify'] = $values['frm_verify'];
        }

        $formData = apply_filters('mosparo_integration_formidable_form_data', $formData);

        return [ $formData, $requiredFields, $verifiableFields ];
    }

    protected function searchMosparoFieldInForm($postedFields)
    {
        foreach ($postedFields as $key => $field) {
            if ($field->type === 'mosparo_field') {
                return true;
            }
        }

        return false;
    }
}