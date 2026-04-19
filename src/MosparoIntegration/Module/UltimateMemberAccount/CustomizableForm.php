<?php

namespace MosparoIntegration\Module\UltimateMemberAccount;

use MosparoIntegration\Helper\ConfigHelper;
use MosparoIntegration\Helper\VerificationHelper;
use MosparoIntegration\Module\AbstractModule;
use MosparoIntegration\ModuleForm\AbstractAccountForm;

/**
 * This class is used to verify a customizable Ultimate Member form like
 * login or register.
 */
class CustomizableForm extends AbstractAccountForm
{
    protected $mode = '';

    public function __construct(AbstractModule $module, $mode)
    {
        parent::__construct($module);

        $this->mode = $mode;
    }

    public function verifyForm($args, $umFormData = [])
    {
        if (!isset($umFormData['mode']) || $umFormData['mode'] !== $this->mode) {
            return;
        }

        $connection = ConfigHelper::getInstance()->getConnectionFor($this->module->getDefaultKey());
        if ($connection === false) {
            UM()->form()->add_error('mosparo', __('A general error occurred: no available connection', 'mosparo-integration'));
            return;
        }

        $submitToken = trim(sanitize_text_field($args['submitted']['_mosparo_submitToken'] ?? ''));
        $validationToken = trim(sanitize_text_field($args['submitted']['_mosparo_validationToken'] ?? ''));
        if (!$submitToken || !$validationToken) {
            UM()->form()->add_error('mosparo', __('Please check the mosparo checkbox below the form.', 'mosparo-integration'));
            return;
        }

        [ $formData, $requiredFields, $verifiableFields ] = $this->getFormData($args, $umFormData);

        $verificationHelper = VerificationHelper::getInstance();
        $verificationResult = $verificationHelper->verifySubmission($connection, $submitToken, $validationToken, $formData);
        if ($verificationResult === null) {
            UM()->form()->add_error('mosparo', sprintf(__('A general error occurred: %s', 'mosparo-integration'), $verificationHelper->getLastException()->getMessage()));
            return;
        }

        // Confirm that all required fields were verified
        $verifiedFields = array_keys($verificationResult->getVerifiedFields());
        $fieldDifference = array_diff(array_keys($formData), $verifiedFields);

        if ($verificationResult !== null) {
            // Confirm that all required fields were verified
            $verifiedFields = array_keys($verificationResult->getVerifiedFields());
            $fieldDifference = array_diff($requiredFields, $verifiedFields);
            $verifiableFieldDifference = array_diff($verifiableFields, $verifiedFields);

            if ($verificationResult->isSubmittable() && empty($fieldDifference) && empty($verifiableFieldDifference)) {
                return;
            }
        }

        UM()->form()->add_error('mosparo', __('Verification failed which means the form was not submitted correctly.', 'mosparo-integration'));
    }

    public function displayMosparoField()
    {
        echo '<div class="um-field-mosparo">';
        parent::displayMosparoField();
        echo '</div>';
    }

    protected function getFormData($args, $umFormData)
    {
        $formId = $umFormData['form_id'];
        $fields = unserialize($umFormData['custom_fields']);
        $formData = [];
        $requiredFields = [];
        $verifiableFields = [];
        $ignoredTypes = apply_filters('mosparo_integration_um_ignored_field_types', [
            'password',
            'row',
            'checkbox',
            'radio',
            'date',
            'time',
            'hidden',
            'image',
            'file',
            'submit',
            'shortcode',
            'block',
            'spacing',
            'divider',
        ]);
        $verifiableFieldTypes = apply_filters('mosparo_integration_um_verifiable_field_types', [
            'text',
            'textarea',
            'email',
            'url',
            'googlemap',
            'youtube_video',
            'vimeo_video',
            'soundcloud_track',
            'spotify',
            'oembed',
        ]);

        foreach ($fields as $fieldKey => $field) {
            if (in_array($field['type'], $ignoredTypes)) {
                continue;
            }

            $originFieldKey = $fieldKey;
            if (!in_array($field['type'], ['textarea', 'select', 'multiselect'])) {
                $originFieldKey = $fieldKey . '-' . $formId;
            }

            if ($field['required'] ?? false) {
                $requiredFields[] = $originFieldKey;
            }

            $value = $args['submitted'][$fieldKey] ?? null;

            // If a select field allows multiple values but is not filled out, we have to make sure that we send an
            // array to mosparo, since otherwise, the comparison is a string against an array, which will always fail.
            if ($field['type'] === 'multiselect' && !is_array($value)) {
                $value = [$value];
            }

            // Ultimate Member trims the values. If the value contains a space at the start or the end, mosparo will
            // detect this as a manipulated field and blocks the submission. Because of this, we're using the $_POST
            // value if the trimmed $_POST value is the same as the prepared Ultimate Member value.
            if (isset($_POST[$originFieldKey]) && $_POST[$originFieldKey] !== $value && stripslashes(trim($_POST[$originFieldKey])) === $value) {
                $value = $_POST[$originFieldKey];
            }

            $formData[$originFieldKey] = stripslashes_deep($value);

            if (in_array($field['type'], $verifiableFieldTypes)) {
                $verifiableFields[] = $originFieldKey;
            }
        }

        $formData = apply_filters('mosparo_integration_um_customizable_form_data', $formData);

        return [ $formData, $requiredFields, $verifiableFields ];
    }
}
