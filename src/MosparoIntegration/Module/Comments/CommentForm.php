<?php

namespace MosparoIntegration\Module\Comments;

use MosparoIntegration\Helper\ConfigHelper;
use MosparoIntegration\Helper\FrontendHelper;
use MosparoIntegration\Helper\VerificationHelper;

class CommentForm
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
        add_action('comment_form_after_fields', [$this, 'displayMosparoField'], 10, 1);
        add_filter('pre_comment_approved', [$this, 'verifyComment'], 9, 2);
    }

    public function displayMosparoField($fields = [])
    {
        $configHelper = ConfigHelper::getInstance();
        $connection = $configHelper->getConnectionFor('module_comments');
        if ($connection === false) {
            echo __('No mosparo connection available. Please configure the connection in the mosparo settings.', 'mosparo-integration');
            return;
        }

        $frontendHelper = FrontendHelper::getInstance();
        $boxHtml = $frontendHelper->generateField($connection);

        $containerHtml = apply_filters('mosparo_integration_comments_html_container', '<p class="comment-mosparo-integration">%s</p>');

        echo sprintf($containerHtml, $boxHtml);
    }

    public function verifyComment($approved, $commentData)
    {
        // Skip the mosparo verification for logged in users
        $user = wp_get_current_user();
        if (isset($commentData['user_ID']) && $user->exists() && $user->ID == $commentData['user_ID']) {
            return $approved;
        }

        $configHelper = ConfigHelper::getInstance();
        $connection = $configHelper->getConnectionFor('module_comments');
        if ($connection === false) {
            return $approved;
        }

        $submitToken = trim(sanitize_text_field($_REQUEST['_mosparo_submitToken'] ?? ''));
        $validationToken = trim(sanitize_text_field($_REQUEST['_mosparo_validationToken'] ?? ''));

        // If the tokens are not available, the submission cannot be valid.
        if (empty($submitToken) || empty($validationToken)) {
            return 'spam';
        }

        $formData = [
            'comment' => $commentData['comment_content'],
            'author' => $commentData['comment_author'],
            'email' => $commentData['comment_author_email'],
            'url' => $commentData['comment_author_url']
        ];

        // Add the rating field from the WooCommerce reviews to the form data
        if ($commentData['comment_type'] === 'review' && isset($_POST['rating'])) {
            $formData['rating'] = (int) $_POST['rating'];
        }

        $formData = apply_filters('mosparo_integration_comments_form_data', $formData);

        // Verify the submission
        $verificationHelper = VerificationHelper::getInstance();
        $verificationResult = $verificationHelper->verifySubmission($connection, $submitToken, $validationToken, $formData);
        if ($verificationResult === null) {
            return 'spam';
        }

        // Confirm that all required fields were verified
        $verifiedFields = array_keys($verificationResult->getVerifiedFields());
        $fieldDifference = array_diff(['comment', 'author', 'email'], $verifiedFields);

        if (!$verificationResult->isSubmittable() || !empty($fieldDifference)) {
            return 'spam';
        }

        return $approved;
    }
}