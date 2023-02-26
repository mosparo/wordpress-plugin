<?php

namespace MosparoIntegration\Module\GravityForms;

use GFFormsModel;
use MosparoIntegration\Helper\FrontendHelper;
use MosparoIntegration\Helper\VerificationHelper;
use GF_Field;

class MosparoField extends GF_Field
{
    public $type = 'mosparo';

    public function get_form_editor_field_title()
    {
        return esc_attr__('mosparo', 'mosparo-integration');
    }

    public function get_form_editor_button()
    {
        return array(
            'group' => 'advanced_fields',
            'text'  => $this->get_form_editor_field_title()
        );
    }

    function get_form_editor_field_settings()
    {
        return array(
            'label_setting',
            'label_placement_setting',
            'description_setting',
        );
    }

    public function get_field_input($form, $value = '', $entry = null)
    {
        $frontendHelper = FrontendHelper::getInstance();

        if ($frontendHelper->isGutenbergRequest()) {
            return $frontendHelper->displayDummy();
        } else {
            $html = $frontendHelper->generateField(['designMode' => $this->is_form_editor()], $this);

            if ($this->is_form_editor()) {
                return '<form class="gform-mosparo-form">' . $html . '</form>';
            } else {
                return $html;
            }
        }
    }

    public function validate($value, $form)
    {
        [ $formData, $requiredFields ] = $this->getFormData($form);
        $submitToken = trim(sanitize_text_field($_POST['_mosparo_submitToken'] ?? ''));
        $validationToken = trim(sanitize_text_field($_POST['_mosparo_validationToken'] ?? ''));

        // If the tokens are not available, the submission cannot be valid.
        if (empty($submitToken) || empty($validationToken)) {
            $this->failed_validation = true;
            $this->validation_message = __('Submit or validation token is empty.', 'mosparo-integration');

            return;
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
                $this->failed_validation = false;
                return;
            }
        }

        $this->failed_validation = true;
        $this->validation_message = __('Verification failed which means the form contains spam.', 'mosparo-integration');
    }

    protected function getFormData(array $form): array
    {
        $formData = [];
        $requiredFields = [];
        $ignoredTypes = apply_filters('mosparo_integration_cf7_ignored_field_types', [
            'checkbox',
            'radio',
            'hidden',
            'html',
            'section',
            'page',
            'fileupload',
            'captcha',
            'consent',
            'mosparo',
            'post_title',
            'post_content',
            'post_excerpt',
            'post_tags',
            'post_category',
            'post_image',
            'post_custom_field',
            'product',
            'quantity',
            'option',
            'shipping',
            'total',
        ]);

        foreach ($form['fields'] as $field) {
            if (in_array($field['type'], $ignoredTypes)) {
                continue;
            }

            $value = GFFormsModel::get_field_value($field);

            if (is_array($field['inputs']) && $field['inputs']) {
                foreach ($field['inputs'] as $subField) {
                    if ($subField['isHidden'] ?? false) {
                        continue;
                    }

                    $subValue = $value[$subField['id']];

                    $formData['input_' . $subField['id']] = $subValue;

                    if ($field['isRequired']) {
                        $requiredFields[] = 'input_' . $subField['id'];
                    }
                }
            } else if ($value !== null) {
                $formData['input_' . $field['id']] = $value;

                if ($field['isRequired']) {
                    $requiredFields[] = 'input_' . $field['id'];
                }

                if ($field['type'] === 'email' && $field['emailConfirmEnabled'] ?? false) {
                    $formData['input_' . $field['id'] . '_2'] = $value;

                    if ($field['isRequired']) {
                        $requiredFields[] = 'input_' . $field['id'] . '_2';
                    }
                }
            }
        }

        $formData = apply_filters('mosparo_integration_gravity_forms_form_data', $formData);

        return [ $formData, $requiredFields ];
    }
}
