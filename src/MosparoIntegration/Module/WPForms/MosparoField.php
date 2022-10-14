<?php

namespace MosparoIntegration\Module\WPForms;

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
        $this->displayDummy();
    }

    public function field_display($field, $deprecated, $form_data)
    {
        if ($this->isGutenbergRequest()) {
            $this->displayDummy();
        } else {
            $primary = $field['properties']['inputs']['primary'];

            $frontendHelper = FrontendHelper::getInstance();
            echo $frontendHelper->generateField([
                'name' => $primary['attr']['name'] ?? ''
            ]);
        }
    }

    protected function displayDummy()
    {
        echo '<div style="width: 400px; height: 80px; border: 2px solid #888888; border-radius: 10px; display: flex; justify-content: center; align-items: center">mosparo</div>';
    }

    public function isGutenbergRequest()
    {
        return (defined('REST_REQUEST') && REST_REQUEST && !empty($_REQUEST['context']) && 'edit' === sanitize_key($_REQUEST['context']));
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
        [ $data, $requiredFields ] = $this->getFormData($entry, $formData);
        $submitToken = trim(sanitize_text_field($_REQUEST['_mosparo_submitToken'] ?? ''));
        $validationToken = trim(sanitize_text_field($_REQUEST['_mosparo_validationToken'] ?? ''));

        // If the tokens are not available, the submission cannot be valid.
        if (empty($submitToken) || empty($validationToken)) {
            wpforms()->process->errors[$formId][$mosparoFieldId] = __('Your submission is not valid.', 'mosparo-integration');
            return;
        }

        // If the submission is valid, the submission is not spam.
        $verificationHelper = VerificationHelper::getInstance();
        $verificationResult = $verificationHelper->verifySubmission($submitToken, $validationToken, $data);
        if ($verificationResult !== null) {
            // Confirm that all required fields were verified
            $verifiedFields = array_keys($verificationResult->getVerifiedFields());
            $fieldDifference = array_diff($requiredFields, $verifiedFields);

            if ($verificationResult->isSubmittable() && empty($fieldDifference)) {
                return;
            }
        }

        wpforms()->process->errors[$formId][$mosparoFieldId] = __('Your submission is not valid.', 'mosparo-integration');
    }

    protected function getFormData($entry, $formData)
    {
        $ignoredFields = apply_filters('mosparo_integration_wpforms_ignored_field_types', [
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
        $data = [];
        $requiredFields = [];
        $fieldsData = $entry['fields'];

        foreach ($formData['fields'] as $key => $field) {
            if (in_array($field['type'], $ignoredFields)) {
                continue;
            }

            if (is_array($fieldsData[$key])) {
                foreach ($fieldsData[$key] as $subKey => $value) {
                    $name = sprintf('wpforms[fields][%s][%s]', $key, $subKey);
                    $data[$name] = $value;

                    if ($field['required'] == 1) {
                        $requiredFields[] = $name;
                    }
                }
            } else {
                $name = sprintf('wpforms[fields][%s]', $key);
                $data[$name] = $fieldsData[$key];

                if ($field['required'] == 1) {
                    $requiredFields[] = $name;
                }
            }
        }

        $data = apply_filters('mosparo_integration_wpforms_get_form_data', $data);

        return [ $data, $requiredFields ];
    }
}