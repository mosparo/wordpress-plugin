<?php

namespace MosparoIntegration\Module\WSForm;

use MosparoIntegration\Helper\AdminHelper;
use MosparoIntegration\Helper\ConfigHelper;
use MosparoIntegration\Helper\FrontendHelper;
use MosparoIntegration\Helper\VerificationHelper;
use MosparoIntegration\Module\AbstractModule;
use WS_Form_Submit;

class WSFormModule extends AbstractModule
{
    protected $key = 'wsform';

    public function __construct()
    {
        $this->name = __('WS Form', 'mosparo-integration');
        $this->description = __('Protects WS Form forms with mosparo. Please use mosparo version 1.3.6 or newer for this module.', 'mosparo-integration');
        $this->dependencies = [
            'wsform' => ['name' => __('WS Form', 'mosparo-integration'), 'url' => 'https://wordpress.org/plugins/wsform-lite/']
        ];
    }

    public function canInitialize()
    {
        return class_exists('WS_Form_Submit');
    }

    public function initializeModule($pluginDirectoryPath, $pluginDirectoryUrl)
    {
        add_filter('wsf_config_field_types', [$this, 'registerMosparoField'], 10, 1);
        add_filter('wsf_config_meta_keys', [$this, 'registerMosparoMetaKeys'], 10, 1);

        add_action('wsf_form_pre_process_field', [$this, 'registerMosparoFrontend'], 10, 2);

        add_filter('wsf_submit_validate', [$this, 'validateForm'], 10, 3);
    }

    public function registerMosparoField($fieldTypes)
    {
        $fieldTypes['spam']['types']['mosparo'] = array (

            'label' => 'mosparo',
            'pro_required' => false,
            'kb_url' => 'https://documentation.mosparo.io/',
            'label_default' => 'mosparo',
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><path d="M55.2,39.5c3.6-13.5-4.3-27.4-17.7-31.1-8.4-2.3-17.5,0-23.8,6.1l-12-1.6,5.5,12c-4.2,13,2.5,26.9,15.2,31.7l1.4-3.9c-10.9-4-16.5-16.2-12.5-27.2l.3-.8-3.1-6.7,6.6.9.7-.8c8-8.5,21.3-8.8,29.6-.7,5.5,5.3,7.7,13.2,5.8,20.6l-30.3-11.2-.7,2c-3.6,9.5,1,20.2,10.4,23.8,8.9,3.5,18.9-.6,23-9.2l7.1,2.6,1.4-3.9-7.1-2.6ZM32.6,48.5c-6.6-2.4-10.4-9.4-9-16.3l26.3,9.7c-3.4,6.3-10.8,9.1-17.4,6.6Z"/><path d="M29.7,17c1.7-1.3,4.1-.9,5.3.8s.9,4.1-.8,5.4-4.1.9-5.3-.8-.9-4.1.8-5.4Z"/></svg>',
            'mask_field' => '#pre_help<div id="#id" name="#name" style="border: none; padding: 0" required data-mosparo#attributes><div id="mosparo-box-field_#field_id"></div></div>#invalid_feedback#post_help',
            'mask_field_attributes' => array('class', 'mosparo_connection'),
            'submit_save' => false,
            'submit_edit' => false,
            'calc_in' => false,
            'calc_out' => false,
            'text_in' => false,
            'text_out' => false,
            'value_out' => false,
            'mappable' => false,
            'progress' => false,
            'keyword' => __('mosparo modern spam protection', 'mosparo-integration'),
            'multiple' => false,
            'conditional' => array(
                'logics_enabled' => array('mosparo', 'mosparo_not'),
                'actions_enabled' => array('visibility', 'class_add_wrapper', 'class_remove_wrapper'),
                'condition_event' => 'mosparo'
            ),
            'events' => [
                'event' => 'mousedown touchstart',
                'event_action' => __('Field', 'ws-form')
            ],
            'fieldsets' => array(
                // Tab: Basic
                'basic' => array(
                    'label' => __('Basic', 'ws-form'),
                    'meta_keys' => array('mosparo_connection', 'help'),
                ),
                // Tab: Advanced
                'advanced' => array(
                    'label' => __('Advanced', 'ws-form'),
                    'fieldsets' => array(
                        array(
                            'label' => __('Style', 'ws-form'),
                            'meta_keys' => array('class_single_vertical_align')
                        ),
                        array(
                            'label' => __('Classes', 'ws-form'),
                            'meta_keys' => array('class_field_wrapper')
                        ),
                        array(
                            'label' => __('Restrictions', 'ws-form'),
                            'meta_keys' => array('field_user_status', 'field_user_roles', 'field_user_capabilities')
                        ),
                        array(
                            'label' => __('Validation', 'ws-form'),
                            'meta_keys' => array('invalid_feedback_render', 'validate_inline', 'invalid_feedback')
                        ),
                        array(
                            'label' => __('Breakpoints', 'ws-form'),
                            'meta_keys' => array('breakpoint_sizes'),
                            'class' => array('wsf-fieldset-panel')
                        )
                    )
                )
            )
        );

        return $fieldTypes;
    }

    public function registerMosparoMetaKeys($metaKeys)
    {
        $configHelper = ConfigHelper::getInstance();
        $adminHelper = AdminHelper::getInstance();
        $connections = [
            ['value' => '', 'text' => __('Default connection for WS Form', 'mosparo-integration')]
        ];
        foreach ($configHelper->getConnections() as $connection) {
            $connections[] = ['value' => $connection->getKey(), 'text' => $connection->getName()];
        }

        $metaKeys['mosparo_connection'] = array(
            'label'	=> __('Connection', 'mosparo-integration'),
            'type' => 'select',
            'help' => sprintf(
                '%s <a href="%s" target="_blank">%s</a>',
                esc_html__('Configure the connection in the mosparo settings.', 'mosparo-integration'),
                $adminHelper->buildConfigPageUrl(),
                esc_html__('mosparo Settings', 'mosparo-integration')
            ),
            'default' => '',
            'options' => $connections,
            'admin' => true,
            'public' => true
        );

        return $metaKeys;
    }

    public function registerMosparoFrontend($field, $shortcodeObj)
    {
        $configHelper = ConfigHelper::getInstance();
        $frontendHelper = FrontendHelper::getInstance();
        if (!isset($field->type) || $field->type !== 'mosparo') {
            return;
        }

        $connectionKey = $field->meta->mosparo_connection ?? '';
        if ($connectionKey) {
            $connection = $configHelper->getConnection($connectionKey);
        } else {
            $connection = $configHelper->getDefaultConnectionFor('module_wsform');
        }

        if ($connection === false) {
            return;
        }

        $ignoredFieldKeys = ['password', 'file', 'progress', 'meter', 'signature', 'color', 'rating', 'legal', 'googlemap', 'googleaddress', 'googleroute'];
        $notPattern = '';
        foreach ($ignoredFieldKeys as $ignoredFieldKey) {
            $notPattern .= sprintf(':not([data-type="%s"])', $ignoredFieldKey);
        }
        $script = $frontendHelper->getScript($connection, 'field_' . $field->id, [
            'inputFieldSelector' => sprintf('.wsf-field-wrapper%s:not(.mosparo__ignored-field) [name]:not(.mosparo__ignored-field)', $notPattern)
        ], 'wsform');
        $shortcodeObj->footer_js .= $script;
    }

    public function validateForm($errorValidationActions, $postMode, WS_Form_Submit $wsFormSubmit)
    {
        $field = $this->searchMosparoField($wsFormSubmit);
        // Stop the verification if the mosparo field is not found in the form
        if (!$field) {
            return;
        }

        // Stop the verification if the mosparo fields were not submitted
        $mosparoData = $this->searchMosparoDataInPostData($_POST);
        if (!$mosparoData) {
            return;
        }

        $configHelper = ConfigHelper::getInstance();
        $connectionKey = $field->meta->mosparo_connection ?? '';
        if ($connectionKey) {
            $connection = $configHelper->getConnection($connectionKey);
        } else {
            $connection = $configHelper->getDefaultConnectionFor('module_wsform');
        }

        if ($connection === false) {
            return;
        }

        [ $formData, $requiredFields, $verifiableFields ] = $this->getFormData($wsFormSubmit);
        $submitToken = trim($mosparoData['_mosparo_submitToken'] ?? '');
        $validationToken = trim($mosparoData['_mosparo_validationToken'] ?? '');

        // If the tokens are not available, the submission cannot be valid.
        if (empty($submitToken) || empty($validationToken)) {
            throw new \Exception(__('Submit or validation token is empty.', 'mosparo-integration'));
        }

        // Verify the submission
        $verificationHelper = VerificationHelper::getInstance();
        $verificationResult = $verificationHelper->verifySubmission($connection, $submitToken, $validationToken, $formData);
        if ($verificationResult !== null) {
            // Confirm that all required fields were verified
            $verifiedFields = array_keys($verificationResult->getVerifiedFields());
            $fieldDifference = array_diff($requiredFields, $verifiedFields);
            $verifiableFieldDifference = array_diff($verifiableFields, $verifiedFields);

            if ($verificationResult->isSubmittable() && empty($fieldDifference) && empty($verifiableFieldDifference)) {
                return;
            }
        }

        throw new \Exception(__('Verification failed which means the form contains spam.', 'mosparo-integration'));
    }

    protected function getFormData(WS_Form_Submit $wsFormSubmit)
    {
        $formData = [];
        $requiredFields = [];
        $verifiableFields = [];
        $ignoredTypes = apply_filters('mosparo_integration_wsform_ignored_field_types', [
            'password',
            'checkbox',
            'radio',
            'hidden',
            'signature',
            'texteditor',
            'legal',
            'googlemap',
            'googleaddress',
            'googleroute',
            'color',
            'rating',
            'html',
            'divider',
            'spacer',
            'message',
            'progress',
            'meter',
            'submit',
            'save',
            'reset',
            'clear',
            'tab_previous',
            'tab_next',
            'section_add',
            'section_delete',
            'mosparo',
            'file',
        ]);
        $verifiableFieldTypes = apply_filters('mosparo_integration_wsform_verifiable_field_types', [
            'text',
            'textarea',
            'email',
            'url',
        ]);
        $ignoredFieldPattern = 'mosparo__ignored-field';

        foreach ($wsFormSubmit->form_object->groups as $group) {
            foreach ($group->sections as $section) {
                $sectionRepeatable = false;
                if ($section->meta->section_repeatable === 'on') {
                    $sectionRepeatable = true;
                }
                $sectionData = $this->findSectionData($wsFormSubmit, $section->id, $sectionRepeatable);

                foreach ($section->fields as $field) {
                    if (!isset($field->type) || in_array($field->type, $ignoredTypes)) {
                        continue;
                    }

                    if (
                        strpos($field->meta->class_field ?? '', $ignoredFieldPattern) !== false ||
                        strpos($field->meta->class_field_wrapper ?? '', $ignoredFieldPattern) !== false
                    ) {
                        continue;
                    }

                    $fieldKey = 'field_' . $field->id;
                    $fieldData = $sectionData[$fieldKey];
                    $value = $fieldData['value'] ?? null;

                    if ($sectionRepeatable) {
                        foreach ($fieldData as $subFieldData) {
                            $subValue = $subFieldData['value'];
                            $realFieldKey = $fieldKey . '[' . $subFieldData['repeatable_index'] . ']';
                            $formData[$realFieldKey] = $this->getOriginalValue($fieldKey . '_' . $subFieldData['repeatable_index'], $subValue);

                            if (isset($field->meta->required) && $field->meta->required === 'on') {
                                $requiredFields[] = $realFieldKey;
                            }

                            if (in_array($field->type, $verifiableFieldTypes)) {
                                $verifiableFields[] = $realFieldKey;
                            }
                        }
                    } else {
                        $formData[$fieldKey] = $this->getOriginalValue($fieldKey, $value);

                        if (isset($field->meta->required) && $field->meta->required === 'on') {
                            $requiredFields[] = $fieldKey;
                        }

                        if (in_array($field->type, $verifiableFieldTypes)) {
                            $verifiableFields[] = $fieldKey;
                        }
                    }
                }
            }
        }

        $formData = apply_filters('mosparo_integration_wsform_form_data', $formData);

        return [ $formData, $requiredFields, $verifiableFields ];
    }

    protected function searchMosparoField($wsFormSubmit)
    {
        foreach ($wsFormSubmit->form_object->groups as $group) {
            foreach ($group->sections as $section) {
                foreach ($section->fields as $field) {
                    if (isset($field->type) && $field->type === 'mosparo') {
                        return $field;
                    }
                }
            }
        }

        return null;
    }

    protected function findSectionData(WS_Form_Submit $wsFormSubmit, $sectionId, $sectionRepeatable)
    {
        $formData = [];
        foreach ($wsFormSubmit->meta as $fieldKey => $fieldData) {
            if ($fieldData['section_id'] == $sectionId) {
                if ($sectionRepeatable) {
                    if ($fieldData['repeatable_index'] === false) {
                        continue;
                    }

                    $formData['field_' . $fieldData['id']][$fieldData['repeatable_index']] = $fieldData;
                } else {
                    $formData[$fieldKey] = $fieldData;
                }
            }
        }

        return $formData;
    }

    protected function searchMosparoDataInPostData($postData)
    {
        $mosparoData = [];
        foreach ($postData as $key => $value) {
            if ($key === '_mosparo_submitToken' || $key === '_mosparo_validationToken') {
                $mosparoData[$key] = sanitize_text_field($value);
            }
        }

        if (count($mosparoData) !== 2) {
            return null;
        }

        return $mosparoData;
    }

    protected function getOriginalValue($fullKey, $value)
    {
        // WS Form does not deliver the value if the value is invalid. But mosparo
        // needs the original value to verify the submission.
        if (isset($_POST[$fullKey]) && $_POST[$fullKey] !== $value) {
            $value = $_POST[$fullKey];
        }

        return $value;
    }
}