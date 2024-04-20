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

    public function verifyLoginForm($user)
    {
        if ($this->module->getKey() != 'woocommerceaccount' && $this->isWoocommerceRequest('woocommerce-login-nonce')) {
            return $user;
        }
        $errors = new WP_Error();
        $connection = ConfigHelper::getInstance()->getConnectionFor($this->module->getDefaultKey(), true);
        if ($connection === false) {
            return $user;
        }
        $submitToken = trim(sanitize_text_field($_REQUEST['_mosparo_submitToken'] ?? ''));
        $validationToken = trim(sanitize_text_field($_REQUEST['_mosparo_validationToken'] ?? ''));

        $formData = apply_filters('mosparo_integration_'.$this->module->getKey().'_login_form_data', []);

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
            return $errors;
        }
        return $user;
    }

    public function verifyRegisterForm($username, $email, WP_Error $errors)
    {
        if ($this->module->getKey() != 'woocommerceaccount' && $this->isWoocommerceRequest('woocommerce-register-nonce')) {
            return $username;
        }
        $connection = ConfigHelper::getInstance()->getConnectionFor($this->module->getDefaultKey(), true);
        if ($connection === false) {
            return;
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
            return;
        }

        // Confirm that all required fields were verified
        $verifiedFields = array_keys($verificationResult->getVerifiedFields());
        $fieldDifference = array_diff(['user_login', 'user_email'], $verifiedFields);

        if (!$verificationResult->isSubmittable() || !empty($fieldDifference)) {
            $errors->add(
                'mosparo_integration_spam_error',
                __('Verification failed which means the form contains spam.', 'mosparo-integration')
            );
        }
    }

    public function verifyLostPasswordForm(WP_Error $errors, $user_data)
    {
        if (!$this->module->getKey() != 'woocommerceaccount' && $this->isWoocommerceRequest('woocommerce-lost-password-nonce')) {
            return;
        }
        $connection = ConfigHelper::getInstance()->getConnectionFor($this->module->getDefaultKey(), true);
        if ($connection === false) {
            return;
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
        $fieldDifference = array_diff(['user_login'], $verifiedFields);

        if (!$verificationResult->isSubmittable() || !empty($fieldDifference)) {
            $errors->add(
                'mosparo_integration_spam_error',
                __('Verification failed which means the form contains spam.', 'mosparo-integration')
            );
        }
    }

    public function verifyWoocommerceResetPasswordForm(WP_Error $errors, $user)
    {
        $connection = ConfigHelper::getInstance()->getConnectionFor($this->module->getDefaultKey(), true);
        if ($connection === false) {
            return;
        }

        $submitToken = trim(sanitize_text_field($_REQUEST['_mosparo_submitToken'] ?? ''));
        $validationToken = trim(sanitize_text_field($_REQUEST['_mosparo_validationToken'] ?? ''));

        $formData = apply_filters('mosparo_integration_'.$this->module->getKey().'_reset_password_form_data', [
            // 'reset_login' => sanitize_user($_REQUEST['reset_login']),
            // 'reset_key' => sanitize_user($_REQUEST['reset_key']),
        ]);

        $verificationHelper = VerificationHelper::getInstance();
        $verificationResult = $verificationHelper->verifySubmission($connection, $submitToken, $validationToken, $formData);
        if ($verificationResult === null) {
            $errors->add(
                'mosparo_integration_general_error',
                sprintf(__('A general error occurred: %s', 'mosparo-integration'), $verificationHelper->getLastException()->getMessage())
            );
            return;
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
    }

}
