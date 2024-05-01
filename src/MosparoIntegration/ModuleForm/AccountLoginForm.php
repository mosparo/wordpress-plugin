<?php

namespace MosparoIntegration\ModuleForm;

use MosparoIntegration\Helper\ConfigHelper;
use MosparoIntegration\Helper\VerificationHelper;
use WP_Error;

/**
 * Account login form for the WordPress and WooCommerce login forms.
 */
class AccountLoginForm extends AbstractAccountForm
{
    public function verifyLoginForm($userOrError)
    {
        if (is_wp_error($userOrError) || !$this->canProcessRequest('woocommerce-login-nonce')) {
            return $userOrError;
        }

        $connection = ConfigHelper::getInstance()->getConnectionFor($this->module->getDefaultKey(), true);
        if ($connection === false) {
            return new WP_Error('mosparo_integration_general_error',
                __('A general error occurred: no available connection', 'mosparo-integration')
            );
        }

        $submitToken = trim(sanitize_text_field($_REQUEST['_mosparo_submitToken'] ?? ''));
        $validationToken = trim(sanitize_text_field($_REQUEST['_mosparo_validationToken'] ?? ''));

        $formData = apply_filters('mosparo_integration_' . $this->module->getKey() . '_login_form_data', []);

        $verificationHelper = VerificationHelper::getInstance();
        $verificationResult = $verificationHelper->verifySubmission($connection, $submitToken, $validationToken, $formData);
        if ($verificationResult === null) {
            return new WP_Error(
                'mosparo_integration_general_error',
                sprintf(__('A general error occurred: %s', 'mosparo-integration'), $verificationHelper->getLastException()->getMessage())
            );
        }

        // Confirm that all required fields were verified
        $verifiedFields = array_keys($verificationResult->getVerifiedFields());
        $fieldDifference = array_diff(array_keys($formData), $verifiedFields);

        if (!$verificationResult->isSubmittable() || !empty($fieldDifference)) {
            return new WP_Error(
                'mosparo_integration_spam_error',
                __('Verification failed which means the form contains spam.', 'mosparo-integration')
            );
        }

        return $userOrError;
    }
}
