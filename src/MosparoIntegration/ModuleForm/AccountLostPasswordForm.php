<?php

namespace MosparoIntegration\ModuleForm;

use MosparoIntegration\Helper\ConfigHelper;
use MosparoIntegration\Helper\VerificationHelper;
use WP_Error;

/**
 * Account lost password form for the WordPress and WooCommerce lost password forms.
 */
class AccountLostPasswordForm extends AbstractAccountForm
{
    public function verifyLostPasswordForm(WP_Error $errors, $userData)
    {
        $userId = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;

        // If the user can edit the user, this method is called from an admin and therefore no verification required.
        // This is the case, because in the edit user form in the backend we cannot show the mosparo box, so validation
        // of the form is not possible at all (and not required).
        if ($userId && current_user_can('edit_user', $userId)) {
            return $errors;
        }

        if ($errors->has_errors() || !$this->canProcessRequest('woocommerce-lost-password-nonce')) {
            return $errors;
        }

        $connection = ConfigHelper::getInstance()->getConnectionFor($this->module->getDefaultKey(), true);
        if ($connection === false) {
            $errors->add(
                'mosparo_integration_general_error',
                __('A general error occurred: no available connection', 'mosparo-integration')
            );

            return $errors;
        }

        $submitToken = trim(sanitize_text_field($_POST['_mosparo_submitToken'] ?? ''));
        $validationToken = trim(sanitize_text_field($_POST['_mosparo_validationToken'] ?? ''));

        $formData = apply_filters('mosparo_integration_' . $this->module->getKey() . '_lost_password_form_data', [
            'user_login' => sanitize_user($_POST['user_login']),
        ]);

        $verificationHelper = VerificationHelper::getInstance();
        $verificationResult = $verificationHelper->verifySubmission($connection, $submitToken, $validationToken, $formData);
        if ($verificationResult === null) {
            $errors->add(
                'mosparo_integration_general_error',
                sprintf(__('A general error occurred: %s', 'mosparo-integration'), $verificationHelper->getLastException()->getMessage())
            );

            return $errors;
        }

        // Confirm that all required fields were verified
        $verifiedFields = array_keys($verificationResult->getVerifiedFields());
        $fieldDifference = array_diff(array_keys($formData), $verifiedFields);

        if (!$verificationResult->isSubmittable() || !empty($fieldDifference)) {
            $errors->add(
                'mosparo_integration_spam_error',
                __('Verification failed which means the form contains spam.', 'mosparo-integration')
            );
        }

        return $errors;
    }

}
