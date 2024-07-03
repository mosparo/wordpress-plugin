<?php

namespace MosparoIntegration\ModuleForm;

use MosparoIntegration\Helper\ConfigHelper;
use MosparoIntegration\Helper\VerificationHelper;
use WP_Error;

/**
 * Account register form for the WordPress and WooCommerce register forms.
 */
class AccountRegisterForm extends AbstractAccountForm
{
    public function verifyRegisterForm(WP_Error $errors)
    {
        if ($errors->has_errors() || !$this->canProcessRequest('woocommerce-register-nonce')) {
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

        $formData = apply_filters('mosparo_integration_' . $this->module->getKey() . '_register_form_data', [
            'user_login' => sanitize_user($_POST['user_login'] ?? ''),
            'user_email' => sanitize_email($_POST['user_email'] ?? ''),
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
