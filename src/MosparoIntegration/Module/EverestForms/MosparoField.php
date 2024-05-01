<?php

namespace MosparoIntegration\Module\EverestForms;

use MosparoIntegration\Helper\ConfigHelper;
use MosparoIntegration\Helper\FrontendHelper;
use MosparoIntegration\Helper\VerificationHelper;
use EVF_Form_Fields;

class MosparoField extends EVF_Form_Fields
{
    /**
     * Constructor.
     */
    public function __construct() {
        $this->name   = esc_html__('mosparo', 'everest-forms');
        $this->type   = 'mosparo';
        $this->icon   = 'evf-icon evf-icon-captcha';
        $this->order  = 50;
        $this->group  = 'advanced';
        $this->is_pro = false;

        $this->settings = array(
            'basic-options' => array(
                'field_options' => array(
                    'meta',
                ),
            ),
        );

        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    public function field_prefill_value_property($properties, $field, $formData)
    {
        $properties = parent::field_prefill_value_property($properties, $field, $formData);

        // Process only for current field.
        if ($this->type !== $field['type']) {
            return $properties;
        }

        // We always hide the label for the mosparo box
        $properties['label']['hidden'] = true;
        $properties['label']['class'][] = 'evf-label-hide';

        return $properties;
    }

    /**
     * @inheritDoc
     */
    public function field_display($field, $fieldAtts, $formData)
    {
        $configHelper = ConfigHelper::getInstance();
        $connection = $configHelper->getConnectionFor('module_everest-forms');
        if ($connection === false) {
            echo __('No mosparo connection available. Please configure the connection in the mosparo settings.', 'mosparo-integration');
            return;
        }

        $frontendHelper = FrontendHelper::getInstance();
        if ($frontendHelper->isGutenbergRequest()) {
            echo $frontendHelper->displayDummy();
        } else {
            echo $frontendHelper->generateField($connection);
        }
    }

    /**
     * @inheritDoc
     */
    public function field_preview($field)
    {
        $this->field_preview_option('label', $field);

        $configHelper = ConfigHelper::getInstance();
        $connection = $configHelper->getConnectionFor('module_everest-forms');
        if ($connection === false) {
            echo __('No mosparo connection available. Please configure the connection in the mosparo settings.', 'mosparo-integration');
            return;
        }

        $frontendHelper = FrontendHelper::getInstance();
        echo $frontendHelper->generateField($connection, ['designMode' => true]);
    }

    /**
     * @inheritDoc
     */
    public function validate($fieldId, $fieldSubmit, $form)
    {
        $configHelper = ConfigHelper::getInstance();
        $connection = $configHelper->getConnectionFor('module_everest-forms');
        if ($connection === false) {
            return true;
        }

        [ $formData, $requiredFields, $verifiableFields ] = $this->getFormData($form);
        $submitToken = trim(sanitize_text_field($_POST['_mosparo_submitToken'] ?? ''));
        $validationToken = trim(sanitize_text_field($_POST['_mosparo_validationToken'] ?? ''));

        // If the tokens are not available, the submission cannot be valid.
        if (empty($submitToken) || empty($validationToken)) {
            evf()->task->errors[$form['id']][$fieldId] = __('Submit or validation token is empty.', 'mosparo-integration');
            update_option('evf_validation_error', 'yes');

            return false;
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
                return true;
            }
        }

        // Verification failed, form invalid.
        evf()->task->errors[$form['id']][$fieldId] = __('mosparo verification failed.', 'mosparo-integration');
        update_option('evf_validation_error', 'yes');

        return false;
    }

    /**
     * Extracts the form data for the fields in the form and returns two arrays. One array
     * contains the submitted form data prepared for mosparo, the other contains all required form fields.
     *
     * @param array $form
     * @return array
     */
    protected function getFormData($form): array
    {
        $formData = [];
        $requiredFields = [];
        $verifiableFields = [];
        $ignoredTypes = apply_filters('mosparo_integration_everest_forms_ignored_field_types', [
            'title',
            'html',
            'captcha',
            'image-upload',
            'file-upload',
            'divider',
            'signature',
            'country',
            'progress',
            'privacy-policy',
            'color',
            'credit-card',
            'repeater-fields',
            'payment-single',
            'payment-multiple',
            'payment-coupon',
            'payment-checkbox',
            'payment-quantity',
            'payment-total',
            'payment-subtotal',
            'rating',
            'yes-no',
            'likert',
            'scale-rating',
            'reset',
            'select',
            'radio',
            'checkbox',
            'mosparo'
        ]);
        $verifiableFieldTypes = apply_filters('mosparo_integration_everest_forms_verifiable_field_types', [
            'text',
            'textarea',
            'first-name',
            'last-name',
            'email',
            'url',
        ]);
        $fields = $form['form_fields'];
        $fieldData = $form['entry']['form_fields'];

        foreach ($fields as $fieldName => $field) {
            if (in_array($field['type'], $ignoredTypes)) {
                continue;
            }

            $originFieldName = 'everest_forms[form_fields][' . $fieldName . ']';

            if ($field['required'] ?? false) {
                $requiredFields[] = $originFieldName;
            }

            $formData[$originFieldName] = $fieldData[$fieldName] ?? '';

            if (in_array($field['type'], $verifiableFieldTypes)) {
                $verifiableFields[] = $originFieldName;
            }
        }

        if (isset($form['entry']['hp'])) {
            $formData['everest_forms[hp]'] = $form['entry']['hp'];
        }

        $formData = apply_filters('mosparo_integration_everest_forms_form_data', $formData, $requiredFields);

        return [ $formData, $requiredFields, $verifiableFields ];
    }
}
