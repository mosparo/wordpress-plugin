<?php

namespace MosparoIntegration\Module\NinjaForms;

use MosparoIntegration\Helper\ConfigHelper;
use MosparoIntegration\Helper\VerificationHelper;
use NF_Abstracts_Action;

class MosparoAction extends NF_Abstracts_Action
{
	/**
	 * @var string
	 */
	protected $_name = 'mosparo';

	/**
	 * @var array
	 */
	protected $_tags = ['spam', 'filtering', 'mosparo'];

	/**
	 * @var string
	 */
	protected $_timing = 'early';

	/**
	 * @var int
	 */
	protected $_priority = 1;

	/**
	 * Constructor
	 */
	public function __construct()
    {
		parent::__construct();

		$this->_nicename = esc_html__( 'mosparo', 'mosparo-integration');
	}

	/**
	 * Returns true if the module is enabled
	 *
	 * @return bool
	 */
	protected function isModuleEnabled()
    {
		$configHelper = ConfigHelper::getInstance();

		return $configHelper->isModuleActive('ninja-forms');
	}

	/**
	 * Process the action
	 *
	 * @param array $actionSettings
	 * @param int $formId
	 * @param array $data
	 * @return array
	 */
	public function process($actionSettings, $formId, $nfData)
    {
		if (!$this->isModuleEnabled()) {
			return $nfData;
		}

        // Find the validation data
        [$tokens, $data, $requiredFields] = $this->getFormData($nfData);

        // If the tokens are not available, the submission cannot be valid.
        if (empty($tokens['submitToken']) || empty($tokens['validationToken'])) {
            $nfData['errors']['form']['spam'] = __('Your submission is not valid.', 'mosparo-integration');
            return $nfData;
        }

        // Verify the submission
        $verificationHelper = VerificationHelper::getInstance();
        $verificationResult = $verificationHelper->verifySubmission($tokens['submitToken'], $tokens['validationToken'], $data);
        if ($verificationResult !== null) {
            // Confirm that all required fields were verified
            $verifiedFields = array_keys($verificationResult->getVerifiedFields());
            $fieldDifference = array_diff($requiredFields, $verifiedFields);

            if ($verificationResult->isSubmittable() && empty($fieldDifference)) {
                return $nfData;
            }
        }

        $nfData['errors']['form']['spam'] = __('Your submission is not valid.', 'mosparo-integration');

		return $nfData;
	}

    /**
     * Returns the form data for the given form data
     *
     * @param $nfData
     * @return array
     */
    protected function getFormData($nfData)
    {
        $ignoredFields = apply_filters('mosparo_integration_ninja_forms_ignored_field_types', [
            'checkbox',
            'hr',
            'hidden',
            'html',
            'listcheckbox',
            'listradio',
            'password',
            'passwordconfirm',
            'recaptcha',
            'spam',
            'starrating',
            'submit',
            'unknown'
        ]);

        $data = [];
        $requiredFields = [];
        $tokens = ['submitToken' => '', 'validationToken' => ''];

        foreach ($nfData['fields'] as $field) {
            if (in_array($field['type'], $ignoredFields)) {
                continue;
            }

            // Save the mosparo data separated
            if ($field['type'] == 'mosparo') {
                $tokens = array_map('sanitize_text_field', $field['value']);
                continue;
            }

            $key = sprintf('nf-field-%d', $field['id']);
            if (isset($field['settings']['custom_name_attribute']) && $field['settings']['custom_name_attribute'] != '') {
                $key = $field['settings']['custom_name_attribute'];
            }

            if ($field['required'] == 1) {
                $requiredFields[] = $key;
            }

            $value = $field['value'];
            $data[$key] = $value;
        }

        $data = apply_filters('mosparo_integration_ninja_forms_get_form_data', $data);

        return [$tokens, $data, $requiredFields];
    }
}