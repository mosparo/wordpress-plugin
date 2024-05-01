<?php

namespace MosparoIntegration\Module\WPForms;

use MosparoIntegration\Helper\ConfigHelper;
use MosparoIntegration\Helper\FrontendHelper;
use MosparoIntegration\Helper\VerificationHelper;
use WPForms_Field;

class MosparoField extends WPForms_Field
{
    public function init()
    {
        $this->name = esc_html__('mosparo', 'mosparo-integration');
        $this->type = 'mosparo';
        $this->icon = 'fa-cogs';
        $this->order = 200;

        add_action('wpforms_process', [$this, 'validateSubmission'], 10, 3);
    }

    public function field_options($field)
    {
        $this->field_option('basic-options', $field, [
            'markup' => 'open',
        ]);

        $this->field_option('basic-options', $field, [
            'markup' => 'close',
        ]);
    }

    public function field_preview($field)
    {
        $configHelper = ConfigHelper::getInstance();
        $connection = $configHelper->getConnectionFor('module_wpforms');
        if ($connection === false) {
            echo __('No mosparo connection available. Please configure the connection in the mosparo settings.', 'mosparo-integration');
            return;
        }

        $frontendHelper = FrontendHelper::getInstance();
        echo $frontendHelper->generateField($connection, [
            'designMode' => true,
        ], $this);
    }

    public function field_display($field, $deprecated, $formData)
    {
        $configHelper = ConfigHelper::getInstance();
        $connection = $configHelper->getConnectionFor('module_wpforms');
        if ($connection === false) {
            echo __('No mosparo connection available. Please configure the connection in the mosparo settings.', 'mosparo-integration');
            return;
        }

        $frontendHelper = FrontendHelper::getInstance();
        if ($frontendHelper->isGutenbergRequest()) {
            echo $frontendHelper->displayDummy();
        } else {
            $primary = $field['properties']['inputs']['primary'];

            echo $frontendHelper->generateField($connection, [
                'name' => $primary['attr']['name'] ?? '',
            ], $this);
        }
    }

    public function validate($fieldId, $fieldSubmit, $formData)
    {
        // Nothing to do since we do not validate the form data in this step
    }

    public function format($fieldId, $fieldSubmit, $formData)
    {
        // Nothing to do since we do not store the mosparo data in the form data
    }

    public function validateSubmission($fields, $entry, $formData)
    {
        // Stop the verification if the mosparo tag is not found in the form
        if (!$this->searchMosparoFieldInForm($formData)) {
            return;
        }

        $configHelper = ConfigHelper::getInstance();
        $connection = $configHelper->getConnectionFor('module_wpforms');
        if ($connection === false) {
            return;
        }

        $formId = $entry['id'];
        $mosparoFieldId = null;

        // Get the mosparo field id
        foreach ($formData['fields'] as $key => $field) {
            if ($field['type'] === 'mosparo') {
                $mosparoFieldId = $key;
                break;
            }
        }

        if ($mosparoFieldId === null) {
            wpforms()->process->errors[$formId]['footer'] = __('No mosparo field found in this form.', 'mosparo-integration');
            return;
        }

        // Find the validation data
        [ $data, $requiredFields, $verifiableFields ] = $this->getFormData($entry, $formData);
        $submitToken = trim(sanitize_text_field($_REQUEST['_mosparo_submitToken'] ?? ''));
        $validationToken = trim(sanitize_text_field($_REQUEST['_mosparo_validationToken'] ?? ''));

        // If the tokens are not available, the submission cannot be valid.
        if (empty($submitToken) || empty($validationToken)) {
            wpforms()->process->errors[$formId][$mosparoFieldId] = __('Your submission is not valid.', 'mosparo-integration');
            return;
        }

        // If the submission is valid, the submission is not spam.
        $verificationHelper = VerificationHelper::getInstance();
        $verificationResult = $verificationHelper->verifySubmission($connection, $submitToken, $validationToken, $data);
        if ($verificationResult !== null) {
            // Confirm that all required fields were verified
            $verifiedFields = array_keys($verificationResult->getVerifiedFields());
            $fieldDifference = array_diff($requiredFields, $verifiedFields);
            $verifiableFieldDifference = array_diff($verifiableFields, $verifiedFields);

            if ($verificationResult->isSubmittable() && empty($fieldDifference) && empty($verifiableFieldDifference)) {
                return;
            }
        }

        wpforms()->process->errors[$formId][$mosparoFieldId] = __('Your submission is not valid.', 'mosparo-integration');
    }

    protected function getFormData($entry, $formData)
    {
        $data = [];
        $requiredFields = [];
        $verifiableFields = [];
        $ignoredTypes = apply_filters('mosparo_integration_wpforms_ignored_field_types', [
            'radio',
            'checkbox',
            'file-upload',
            'password',
            'pagebreak',
            'divider',
            'entry-preview',
            'hidden',
            'html',
            'rating',
            'signature',
            'likert_scale',
            'net_promoter_score',
            'gdpr-checkbox',
            'mosparo',
            'captcha',
            'captcha-none',
            'captcha-grecaptcha',
            'captcha-hcaptcha'
        ]);
        $verifiableFieldTypes = apply_filters('mosparo_integration_wpforms_verifiable_field_types', [
            'text',
            'textarea',
            'name',
            'email',
            'url',
        ]);
        $fieldsData = $entry['fields'];

        foreach ($formData['fields'] as $key => $field) {
            if (in_array($field['type'], $ignoredTypes)) {
                continue;
            }

            if (is_array($fieldsData[$key])) {
                foreach ($fieldsData[$key] as $subKey => $value) {
                    $name = sprintf('wpforms[fields][%s][%s]', $key, $subKey);
                    $data[$name] = $value;

                    if ($field['required'] == 1) {
                        $requiredFields[] = $name;
                    }

                    if (in_array($field['type'], $verifiableFieldTypes)) {
                        $verifiableFields[] = $name;
                    }
                }
            } else {
                $name = sprintf('wpforms[fields][%s]', $key);
                $data[$name] = $fieldsData[$key];

                if ($field['required'] == 1) {
                    $requiredFields[] = $name;
                }

                if (in_array($field['type'], $verifiableFieldTypes)) {
                    $verifiableFields[] = $name;
                }
            }
        }

        $data = apply_filters('mosparo_integration_wpforms_get_form_data', $data);

        return [ $data, $requiredFields, $verifiableFields ];
    }

    protected function searchMosparoFieldInForm($formData)
    {
        foreach ($formData['fields'] as $key => $field) {
            if ($field['type'] === 'mosparo') {
                return true;
            }
        }

        return false;
    }
}