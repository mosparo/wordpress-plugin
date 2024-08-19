<?php

namespace MosparoIntegration\Module\MemberpressAccount;

use MosparoIntegration\Helper\ConfigHelper;
use MosparoIntegration\Helper\VerificationHelper;
use MosparoIntegration\ModuleForm\AbstractAccountForm;

/**
 * Account lost password form for the Memberpress lost password form.
 */
class AccountLostPasswordForm extends AbstractAccountForm
{
    public function verifyLostPasswordForm($errors)
    {
        if ($errors) {
            return $errors;
        }

        $connection = ConfigHelper::getInstance()->getConnectionFor($this->module->getDefaultKey(), true);
        if ($connection === false) {
            $errors[] = __('A general error occurred: no available connection', 'mosparo-integration');
            return $errors;
        }

        $submitToken = trim(sanitize_text_field($_POST['_mosparo_submitToken'] ?? ''));
        $validationToken = trim(sanitize_text_field($_POST['_mosparo_validationToken'] ?? ''));

        $formData = apply_filters('mosparo_integration_' . $this->module->getKey() . '_lost_password_form_data', [
            'mepr_user_or_email' => sanitize_user($_POST['mepr_user_or_email']),
        ]);

        $verificationHelper = VerificationHelper::getInstance();
        $verificationResult = $verificationHelper->verifySubmission($connection, $submitToken, $validationToken, $formData);
        if ($verificationResult === null) {
            $errors[] = sprintf(__('A general error occurred: %s', 'mosparo-integration'), $verificationHelper->getLastException()->getMessage());
            return $errors;
        }

        // Confirm that all required fields were verified
        $verifiedFields = array_keys($verificationResult->getVerifiedFields());
        $fieldDifference = array_diff(array_keys($formData), $verifiedFields);

        if (!$verificationResult->isSubmittable() || !empty($fieldDifference)) {
            $errors[] = __('Verification failed which means the form contains spam.', 'mosparo-integration');
        }

        return $errors;
    }

    public function displayMosparoField()
    {
        echo '<div class="mp-form-row mp-mosparo-row">';
        parent::displayMosparoField();
        echo '</div>';
    }
}
