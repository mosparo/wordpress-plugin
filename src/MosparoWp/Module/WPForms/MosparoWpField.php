<?php

namespace MosparoWp\Module\WPForms;

use MosparoWp\Helper\FrontendHelper;
use MosparoWp\Helper\VerificationHelper;
use WPForms_Field;

class MosparoWpField extends WPForms_Field
{
    public function init()
    {
        $this->name = esc_html__('mosparo', 'mosparo-wp');
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
        return (defined('REST_REQUEST') && REST_REQUEST && !empty($_REQUEST['context']) && 'edit' === $_REQUEST['context']);
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
            wpforms()->process->errors[$formId]['footer'] = __('No mosparo field found in this form.', 'mosparo-wp');
            return;
        }

        // Find the validation data
        $data = $this->getFormData($entry, $formData);
        $submitToken = trim($_REQUEST['_mosparo_submitToken'] ?? '');
        $validationToken = trim($_REQUEST['_mosparo_validationToken'] ?? '');

        // If the tokens are not available, the submission cannot be valid.
        if (empty($submitToken) || empty($validationToken)) {
            wpforms()->process->errors[$formId][$mosparoFieldId] = __('Your submission is not valid.', 'mosparo-wp');
            return;
        }

        // If the submission is valid, the submission is not spam.
        $verificationHelper = VerificationHelper::getInstance();
        if ($verificationHelper->verifySubmission($submitToken, $validationToken, $data)) {
            return;
        }

        wpforms()->process->errors[$formId][$mosparoFieldId] = __('Your submission is not valid.', 'mosparo-wp');
    }

    protected function getFormData($entry, $formData)
    {
        $ignoredFields = apply_filters('mosparo_wp_wpforms_ignored_field_types', [
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
        $fieldsData = $entry['fields'];

        foreach ($formData['fields'] as $key => $field) {
            if (in_array($field['type'], $ignoredFields)) {
                continue;
            }

            if (is_array($fieldsData[$key])) {
                foreach ($fieldsData[$key] as $subKey => $value) {
                    $name = sprintf('wpforms[fields][%s][%s]', $key, $subKey);
                    $data[$name] = $value;
                }
            } else {
                $name = sprintf('wpforms[fields][%s]', $key);
                $data[$name] = $fieldsData[$key];
            }
        }

        $data = apply_filters('mosparo_wp_wpforms_get_form_data', $data);

        return $data;
    }
}