<?php

namespace MosparoWp\Module\ContactForm7;

use MosparoWp\Helper\ConfigHelper;
use MosparoWp\Helper\FrontendHelper;
use MosparoWp\Helper\VerificationHelper;
use WPCF7_Submission;

class MosparoWpField
{
    private static $instance;

    public static function getInstance()
    {
        if (empty(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
    }

    public function registerHooks()
    {
        $configHelper = ConfigHelper::getInstance();
        if (!$configHelper->isActive() || !$configHelper->isModuleActive('contact-form-7')) {
            return;
        }

        add_action('wpcf7_init', [$this, 'addFormTag'], 10, 0);
        add_filter('wpcf7_spam', [$this, 'verifyResponse'], 9, 2);
    }

    public function addFormTag()
    {
        wpcf7_add_form_tag('mosparo', [$this, 'displayFormField'], true);
    }

    public function displayFormField()
    {
        $frontendHelper = FrontendHelper::getInstance();
        return $frontendHelper->generateField();
    }

    public function verifyResponse($spam, WPCF7_Submission $submission)
    {
        if ($spam) {
            return $spam;
        }

        $formData = $this->getFormData($submission);
        $submitToken = trim($formData['_mosparo_submitToken'] ?? '');
        $validationToken = trim($formData['_mosparo_validationToken'] ?? '');

        // If the tokens are not available, the submission cannot be valid.
        if (empty($submitToken) || empty($validationToken)) {
            $submission->add_spam_log([
                'agent' => 'mosparo-wp-cf7',
                'reason' => __('Submit or validation token is empty.', 'mosparo-wp'),
            ]);

            return true;
        }
        
        // Remove the mosparo fields from the form data
        $formData = array_filter($formData, function($key) {
            return strpos($key, '_mosparo_') === false;
        }, ARRAY_FILTER_USE_KEY);

        // If the submission is valid, the submission is no spam.
        $verificationHelper = VerificationHelper::getInstance();
        if ($verificationHelper->verifySubmission($submitToken, $validationToken, $formData)) {
            return false;
        }

        // Everything else is spam
        $submission->add_spam_log( array(
            'agent' => 'mosparo-wp-cf7',
            'reason' => __('Verification failed which means the form contains spam.', 'mosparo-wp')
        ));

        return true;
    }

    protected function getFormData(WPCF7_Submission $submission): array
    {
        $ignoredTypes = apply_filters('mosparo_wp_cf7_ignored_field_types', ['hidden', 'file', 'file*', 'submit']);
        $formData = (array) $_POST;
        $tags = $submission->get_contact_form()->scan_form_tags();

        $formData = array_filter($formData, function($key) {
            return substr($key, 0, 6) !== '_wpcf7';
        }, ARRAY_FILTER_USE_KEY);

        foreach ((array) $tags as $tag) {
            if (empty($tag->name)) {
                continue;
            }

            if (in_array($tag->type, $ignoredTypes) || in_array('class:mosparo__ignored-field', $tag->options)) {
                if (isset($formData[$tag->name])) {
                    unset($formData[$tag->name]);
                }

                continue;
            }

            $value = $submission->get_posted_data($tag->name);
            if ($value !== null) {
                $formData[$tag->name] = $value;
            }
        }

        $formData = apply_filters('mosparo_wp_cf7_form_data', $formData);

        return $formData;
    }
}
