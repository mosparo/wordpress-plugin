<?php

namespace MosparoIntegration\Module\UltimateMemberAccount;

use MosparoIntegration\Helper\ConfigHelper;
use MosparoIntegration\Helper\VerificationHelper;
use MosparoIntegration\ModuleForm\AbstractAccountForm;

/**
 * Account lost password form for the Ultimate Member lost password form.
 */
class AccountLostPasswordForm extends AbstractAccountForm
{
    public function verifyLostPasswordForm($args)
    {
        if (!isset($args['mode']) || $args['mode'] !== 'password') {
            return;
        }

        $connection = ConfigHelper::getInstance()->getConnectionFor($this->module->getDefaultKey(), true);
        if ($connection === false) {
            UM()->form()->add_error('mosparo', __('A general error occurred: no available connection', 'mosparo-integration'));
            return;
        }

        $submitToken = trim(sanitize_text_field($args['_mosparo_submitToken'] ?? ''));
        $validationToken = trim(sanitize_text_field($args['_mosparo_validationToken'] ?? ''));

        if (!$submitToken || !$validationToken) {
            UM()->form()->add_error('mosparo', __('Please check the mosparo checkbox below the form.', 'mosparo-integration'));
            return;
        }

        $formData = apply_filters('mosparo_integration_' . $this->module->getKey() . '_lost_password_form_data', [
            'username_b' => sanitize_user($args['username_b'] ?? ''),
        ]);

        $verificationHelper = VerificationHelper::getInstance();
        $verificationResult = $verificationHelper->verifySubmission($connection, $submitToken, $validationToken, $formData);

        if ($verificationResult === null) {
            UM()->form()->add_error('mosparo', sprintf(__('A general error occurred: %s', 'mosparo-integration'), $verificationHelper->getLastException()->getMessage()));
            return;
        }

        // Confirm that all required fields were verified
        $verifiedFields = array_keys($verificationResult->getVerifiedFields());
        $fieldDifference = array_diff(array_keys($formData), $verifiedFields);

        if (!$verificationResult->isSubmittable() || !empty($fieldDifference)) {
            UM()->form()->add_error('mosparo', __('Verification failed which means the form was not submitted correctly.', 'mosparo-integration'));
        }
    }

    public function displayMosparoField()
    {
        echo '<div class="um-field-mosparo">';
        parent::displayMosparoField();
        echo '</div>';
    }
}
