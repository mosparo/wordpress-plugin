<?php

namespace MosparoIntegration\Module\NinjaForms;

use MosparoIntegration\Helper\ConfigHelper;
use MosparoIntegration\Helper\VerificationHelper;
use NinjaForms\Includes\Abstracts\SotAction;
use NinjaForms\Includes\Traits\SotGetActionProperties;
use NinjaForms\Includes\Interfaces\SotAction as InterfacesSotAction;

class MosparoAction extends SotAction implements InterfacesSotAction
{
    use SotGetActionProperties;

	/**
	 * @var array
	 */
	protected $_tags = ['spam', 'filtering', 'mosparo'];

	/**
	 * Constructor
	 */
	public function __construct()
    {
		parent::__construct();

        $this->_name  = 'email';
        $this->_nicename = 'mosparo';
        $this->_timing = 'early';
        $this->_priority = 1;
        $this->_group = 'core';
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
	public function process(array $actionSettings, int $formId, array $data): array
    {
		if (!$this->isModuleEnabled()) {
			return $data;
		}

        // Stop the verification if the mosparo tag is not found in the form
        if (!$this->searchMosparoFieldInForm($data)) {
            return $data;
        }

        $configHelper = ConfigHelper::getInstance();
        $connection = $configHelper->getConnectionFor('module_ninja-forms');
        if ($connection === false) {
            return $data;
        }

        // Find the validation data
        [$tokens, $data, $requiredFields, $verifiableFields] = $this->getFormData($data);

        // If the tokens are not available, the submission cannot be valid.
        if (empty($tokens['submitToken']) || empty($tokens['validationToken'])) {
            $nfData['errors']['form']['spam'] = __('Your submission is not valid.', 'mosparo-integration');
            return $data;
        }

        // Verify the submission
        $verificationHelper = VerificationHelper::getInstance();
        $verificationResult = $verificationHelper->verifySubmission($connection, $tokens['submitToken'], $tokens['validationToken'], $data);
        if ($verificationResult !== null) {
            // Confirm that all required fields were verified
            $verifiedFields = array_keys($verificationResult->getVerifiedFields());
            $fieldDifference = array_diff($requiredFields, $verifiedFields);
            $verifiableFieldDifference = array_diff($verifiableFields, $verifiedFields);

            if ($verificationResult->isSubmittable() && empty($fieldDifference) && empty($verifiableFieldDifference)) {
                return $data;
            }
        }

        $data['errors']['form']['spam'] = __('Your submission is not valid.', 'mosparo-integration');

		return $data;
	}

    /**
     * Returns the form data for the given form data
     *
     * @param $nfData
     * @return array
     */
    protected function getFormData($nfData)
    {
        $data = [];
        $requiredFields = [];
        $verifiableFields = [];
        $ignoredTypes = apply_filters('mosparo_integration_ninja_forms_ignored_field_types', [
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
            'unknown',
            'mosparo',
        ]);
        $verifiableFieldTypes = apply_filters('mosparo_integration_ninja_forms_verifiable_field_types', [
            'textbox',
            'textarea',
            'firstname',
            'lastname',
            'address',
            'city',
            'email',
        ]);

        // These field types receive the field type as suffix to the field name
        $typesWithSuffix = [
            'address',
            'address2',
            'city',
            'email',
            'firstname',
            'lastname',
            'tel',
            'textbox',
            'zip',
        ];

        $tokens = ['submitToken' => '', 'validationToken' => ''];

        foreach ($nfData['fields'] as $field) {
            // Save the mosparo data separated
            if ($field['type'] == 'mosparo') {
                $val = (is_array($field['value'])) ? $field['value'] : [];
                $tokens = array_map('sanitize_text_field', $val);
                continue;
            }

            if (in_array($field['type'], $ignoredTypes)) {
                continue;
            }

            $key = sprintf('nf-field-%d', $field['id']);
            if (in_array($field['type'], $typesWithSuffix)) {
                $key = sprintf('nf-field-%d-%s', $field['id'], $field['type']);
            }

            if (isset($field['settings']['custom_name_attribute']) && $field['settings']['custom_name_attribute'] != '') {
                $key = $field['settings']['custom_name_attribute'];
            }

            $value = $field['value'];
            if ($field['type'] === 'repeater') {
                foreach ($value as $subValue) {
                    $id = $subValue['id'];
                    $idParts = explode('.', $id);
                    $subFieldIdPart = $idParts[1];

                    $subFieldId = $field['id'] . '.' . substr($subFieldIdPart, 0, strpos($subFieldIdPart, '_'));
                    foreach ($field['fields'] as $subField) {
                        if ($subField['id'] != $subFieldId) {
                            continue;
                        }

                        if (in_array($subField['type'], $ignoredTypes)) {
                            continue;
                        }

                        $subKey = $key . '.' . $subFieldIdPart;

                        if ($subField['required'] == 1) {
                            $requiredFields[] = $subKey;
                        }

                        if (in_array($subField['type'], $verifiableFieldTypes)) {
                            $verifiableFields[] = $subKey;
                        }

                        $data[$subKey] = $subValue['value'];
                    }
                }
            } else {
                if ($field['required'] == 1) {
                    $requiredFields[] = $key;
                }

                if (in_array($field['type'], $verifiableFieldTypes)) {
                    $verifiableFields[] = $key;
                }

                $data[$key] = $value;
            }
        }

        $data = apply_filters('mosparo_integration_ninja_forms_get_form_data', $data);

        return [$tokens, $data, $requiredFields, $verifiableFields];
    }

    /**
     * Returns true if the form contains a mosparo field
     *
     * @param array $nfData
     * @return bool
     */
    protected function searchMosparoFieldInForm($nfData)
    {
        foreach ($nfData['fields'] as $field) {
            if ($field['type'] === 'mosparo') {
                return true;
            }
        }

        return false;
    }
}