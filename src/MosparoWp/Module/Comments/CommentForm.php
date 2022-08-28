<?php

namespace MosparoWp\Module\Comments;

use MosparoWp\Helper\ConfigHelper;
use MosparoWp\Helper\FrontendHelper;
use MosparoWp\Helper\VerificationHelper;

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
        $configHelper = ConfigHelper::getInstance();
        if (!$configHelper->isActive() || !$configHelper->isModuleActive('comments')) {
            return;
        }

        add_action('comment_form_after_fields', [$this, 'displayMosparoField'], 10, 1);
        add_filter('pre_comment_approved', [$this, 'verifyComment'], 9, 2);
    }

    public function displayMosparoField($fields = [])
    {
        $frontendHelper = FrontendHelper::getInstance();
        echo $frontendHelper->generateField();
    }

    public function verifyComment($approved, $commentData)
    {
        // Skip the mosparo verification for logged in users
        $user = wp_get_current_user();
        if (isset($commentData['user_ID']) && $user->exists() && $user->ID == $commentData['user_ID']) {
            return $approved;
        }

        $submitToken = trim($_REQUEST['_mosparo_submitToken'] ?? '');
        $validationToken = trim($_REQUEST['_mosparo_validationToken'] ?? '');

        // If the tokens are not available, the submission cannot be valid.
        if (empty($submitToken) || empty($validationToken)) {
            return 'spam';
        }

        $formData = apply_filters('mosparo_wp_comments_form_data', [
            'comment' => $commentData['comment_content'],
            'author' => $commentData['comment_author'],
            'email' => $commentData['comment_author_email'],
            'url' => $commentData['comment_author_url']
        ]);

        // Verify the submission
        $verificationHelper = VerificationHelper::getInstance();
        $verificationResult = $verificationHelper->verifySubmission($submitToken, $validationToken, $formData);
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