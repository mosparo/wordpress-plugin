<?php

namespace MosparoIntegration\Module\Account;

use MosparoIntegration\Helper\ConfigHelper;
use MosparoIntegration\Helper\FrontendHelper;
use MosparoIntegration\Helper\VerificationHelper;
use MosparoIntegration\Module\AbstractModule;
use WP_Error;

//Wordpress + Woocommerce accounts forms
class AccountForm
{
    private static $instance;

    private AbstractModule $module;

    public function __construct(AbstractModule $module)
    {
        $this->module = $module;
    }

    //Checks that woocommerce-specific nonce values are present in POST request
    //Nonce checks have already been done at this point, only checks presence of token
    public function isWoocommerceRequest($nonce) {
        $iswoo = false;

        if (class_exists('WooCommerce') && isset($_REQUEST[$nonce])) {
            $iswoo = true;
        }
        return $iswoo;
    }

    //Wordpress or woocommerce mutual exclusion for same hooks
    public function canProcessRequest($woocommerce_nonce) {
        $bool = true;

        if ($this->isWoocommerceRequest($woocommerce_nonce)) {
            $bool = ($this->module->getKey() == 'woocommerceaccount');
        } else {
            $bool = ($this->module->getKey() == 'account');
        }
        return $bool;
    }

    public function displayMosparoField()
    {
        $connection = ConfigHelper::getInstance()->getConnectionFor($this->module->getDefaultKey(), true);
        if ($connection === false) {
            echo __('No mosparo connection available. Please configure the connection in the mosparo settings.', 'mosparo-integration');
            return;
        }
        $frontendHelper = FrontendHelper::getInstance();
        echo $frontendHelper->generateField($connection);
    }

    public function verifyLoginForm($user_or_error)
    {
        if (is_wp_error($user_or_error) ||
            !$this->canProcessRequest('woocommerce-login-nonce')) {
            return $user_or_error;
        }
        $connection = ConfigHelper::getInstance()->getConnectionFor($this->module->getDefaultKey(), true);
        if ($connection === false) {
            return new WP_Error('mosparo_integration_general_error',
                                __('A general error occurred: no available connection', 'mosparo-integration')
            );
        }
        $submitToken = trim(sanitize_text_field($_REQUEST['_mosparo_submitToken'] ?? ''));
        $validationToken = trim(sanitize_text_field($_REQUEST['_mosparo_validationToken'] ?? ''));

        $formData = apply_filters('mosparo_integration_'.$this->module->getKey().'_login_form_data', []);

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
        return $user_or_error;
    }

    public function verifyRegisterForm(WP_Error $errors, $login, $email)
    {
        if ($errors->has_errors() ||
            !$this->canProcessRequest('woocommerce-register-nonce')) {
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

        $submitToken = trim(sanitize_text_field($_REQUEST['_mosparo_submitToken'] ?? ''));
        $validationToken = trim(sanitize_text_field($_REQUEST['_mosparo_validationToken'] ?? ''));

        $formData = apply_filters('mosparo_integration_'.$this->module->getKey().'_register_form_data', [
            'user_login' => sanitize_user($_REQUEST['user_login']),
            'user_email' => sanitize_email($_REQUEST['user_email']),
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

    public function verifyLostPasswordForm(WP_Error $errors, $user_data)
    {
        if ($errors->has_errors()
            || !$this->canProcessRequest('woocommerce-lost-password-nonce')) {
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

        $submitToken = trim(sanitize_text_field($_REQUEST['_mosparo_submitToken'] ?? ''));
        $validationToken = trim(sanitize_text_field($_REQUEST['_mosparo_validationToken'] ?? ''));

        $formData = apply_filters('mosparo_integration_'.$this->module->getKey().'_lost_password_form_data', [
            'user_login' => sanitize_user($_REQUEST['user_login']),
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
