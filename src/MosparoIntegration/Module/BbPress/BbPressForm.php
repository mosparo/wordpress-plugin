<?php

namespace MosparoIntegration\Module\BbPress;

use MosparoIntegration\Helper\ConfigHelper;
use MosparoIntegration\Helper\FrontendHelper;
use MosparoIntegration\Helper\VerificationHelper;

class BbPressForm
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

    public function registerHooks(BbPressModule $module)
    {
        $that = $this;

        $settingNewTopic = $module->getSettings()->getFieldValue('new_topic_form');
        if ((($settingNewTopic['anonymous'] ?? false) && bbp_is_anonymous()) || (($settingNewTopic['authenticated'] ?? false) && !bbp_is_anonymous())) {
            add_action('bbp_theme_before_topic_form_submit_wrapper', [$this, 'displayMosparoField'], 10, 1);
            add_action('bbp_new_topic_pre_extras', [$this, 'verifySubmission']);

            add_action('bbp_edit_topic_pre_extras', function ($forumId) use ($that) {
                $that->verifySubmission($forumId, false, true);
            });
        }

        $settingNewReply = $module->getSettings()->getFieldValue('new_reply_form');
        if ((($settingNewReply['anonymous'] ?? false) && bbp_is_anonymous()) || (($settingNewReply['authenticated'] ?? false) && !bbp_is_anonymous())) {
            add_action('bbp_theme_before_reply_form_submit_wrapper', [$this, 'displayMosparoField'], 10, 1);
            add_action('bbp_new_reply_pre_extras', function ($forumId) use ($that) {
                $that->verifySubmission($forumId, true);
            });

            add_action('bbp_edit_reply_pre_extras', function ($forumId) use ($that) {
                $that->verifySubmission($forumId, true, true);
            });
        }
    }

    public function displayMosparoField()
    {
        $configHelper = ConfigHelper::getInstance();
        $connection = $configHelper->getConnectionFor('module_bbpress');
        if ($connection === false) {
            echo __('No mosparo connection available. Please configure the connection in the mosparo settings.', 'mosparo-integration');
            return;
        }

        $frontendHelper = FrontendHelper::getInstance();
        $boxHtml = $frontendHelper->generateField($connection);

        $containerHtml = apply_filters('mosparo_integration_comments_html_container', '<div class="bbp-mosparo-integration">%s</div>');

        echo sprintf($containerHtml, $boxHtml);
    }

    public function verifySubmission($forumId, $isReply = false, $isEdit = false)
    {
        $configHelper = ConfigHelper::getInstance();
        $connection = $configHelper->getConnectionFor('module_bbpress');
        if ($connection === false) {
            return;
        }

        $submitToken = trim(sanitize_text_field($_POST['_mosparo_submitToken'] ?? ''));
        $validationToken = trim(sanitize_text_field($_POST['_mosparo_validationToken'] ?? ''));

        // If the tokens are not available, the submission cannot be valid.
        if (empty($submitToken) || empty($validationToken)) {
            bbp_add_error('bbp_mosparo', __('No submit or validation token found. Abort.', 'mosparo-integration'));
            return;
        }

        $this->verifiedSubmitTokens[] = $submitToken;

        $formData = [];

        if (isset($_POST['bbp_anonymous_name'])) {
            $formData = [
                'bbp_anonymous_name' => $_POST['bbp_anonymous_name'] ?? '',
                'bbp_anonymous_email' => $_POST['bbp_anonymous_email'] ?? '',
                'bbp_anonymous_website' => $_POST['bbp_anonymous_website'] ?? '',
            ];
        }

        if ($isReply) {
            $formData['bbp_reply_content'] = $_POST['bbp_reply_content'];

            if ($isEdit) {
                $formData['bbp_reply_to'] = $_POST['bbp_reply_to'] ?? '';
                $formData['bbp_reply_status'] = $_POST['bbp_reply_status'] ?? '';
                $formData['bbp_reply_edit_reason'] = $_POST['bbp_reply_edit_reason'] ?? '';
            }
        } else {
            $formData['bbp_topic_title'] = $_POST['bbp_topic_title'];
            $formData['bbp_topic_content'] = $_POST['bbp_topic_content'];
            $formData['bbp_forum_id'] = $_POST['bbp_forum_id'] ?? '';
            $formData['bbp_stick_topic'] = $_POST['bbp_stick_topic'] ?? '';
            $formData['bbp_topic_status'] = $_POST['bbp_topic_status'] ?? '';

            if ($isEdit) {
                $formData['bbp_topic_edit_reason'] = $_POST['bbp_topic_edit_reason'] ?? '';
            }
        }

        $formData['bbp_topic_tags'] = $_POST['bbp_topic_tags'] ?? '';

        $formData = apply_filters('mosparo_integration_bbpress_form_data', $formData);
        $requiredFormData = apply_filters('mosparo_integration_bbpress_required_data', array_diff(array_keys($formData), [
            'bbp_forum_id',
            'bbp_stick_topic',
            'bbp_topic_status',
            'bbp_topic_tags',
        ]));

        // Verify the submission
        $verificationHelper = VerificationHelper::getInstance();
        $verificationResult = $verificationHelper->verifySubmission($connection, $submitToken, $validationToken, $formData);
        if ($verificationResult === null) {
            bbp_add_error( 'bbp_mosparo', __('The submission is invalid (verification). Abort.', 'mosparo-integration'));
            return;
        }

        // Confirm that all required fields were verified
        $verifiedFields = array_keys($verificationResult->getVerifiedFields());
        $fieldDifference = array_diff($requiredFormData, $verifiedFields);
        if (!$verificationResult->isSubmittable() || !empty($fieldDifference)) {
            bbp_add_error( 'bbp_mosparo', __('The submission is invalid (fields). Abort.', 'mosparo-integration'));
        }
    }
}