<?php


namespace MosparoIntegration\Module\JetFormBuilder;

use Jet_Form_Builder\Blocks\Block_Helper;
use JFB_Modules\Captcha\Abstract_Captcha\Base_Captcha_From_Options;
use JFB_Modules\Captcha\Abstract_Captcha\Captcha_Separate_Editor_Script;
use JFB_Modules\Security\Exceptions\Spam_Exception;
use MosparoIntegration\Helper\AdminHelper;
use MosparoIntegration\Helper\ConfigHelper;
use MosparoIntegration\Helper\FrontendHelper;
use MosparoIntegration\Helper\VerificationHelper;

class Mosparo extends Base_Captcha_From_Options implements Captcha_Separate_Editor_Script {

    protected $pluginDirectoryPath;
    protected $pluginDirectoryUrl;

    public function __construct($pluginDirectoryPath, $pluginDirectoryUrl)
    {
        $this->pluginDirectoryPath = $pluginDirectoryPath;
        $this->pluginDirectoryUrl = $pluginDirectoryUrl;
    }

    public function get_id(): string
    {
        return 'mosparo';
    }

    public function get_title(): string
    {
        return __('mosparo', 'mosparo-integration');
    }

    public function verify( array $request )
    {
        $configHelper = ConfigHelper::getInstance();
        $connection = $configHelper->getConnectionFor('module_jet-form-builder');
        if ($connection === false) {
            return;
        }

        [ $formData, $requiredFields, $verifiableFields ] = $this->getFormData($request);
        $submitToken = trim(sanitize_text_field($request['_mosparo_submitToken'] ?? ''));
        $validationToken = trim(sanitize_text_field($request['_mosparo_validationToken'] ?? ''));

        // If the tokens are not available, the submission cannot be valid.
        if (empty($submitToken) || empty($validationToken)) {
            $this->failed_validation = true;
            $this->validation_message = __('Submit or validation token is empty.', 'mosparo-integration');

            return;
        }

        // Remove the mosparo fields from the form data
        $formData = array_filter($formData, function($key) {
            return strpos($key, '_mosparo_') === false;
        }, ARRAY_FILTER_USE_KEY);

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

        throw new Spam_Exception(
            'captcha_failed',
            __('Verification failed which means the form contains spam.', 'mosparo-integration')
        );
    }

    /**
     * @return string
     */
    public function render(): string
    {
        $configHelper = ConfigHelper::getInstance();
        $connection = $configHelper->getConnectionFor('module_jet-form-builder');
        if ($connection === false) {
            return __('No mosparo connection available. Please configure the connection in the mosparo settings.', 'mosparo-integration');
        }

        $frontendHelper = FrontendHelper::getInstance();

        return $frontendHelper->generateField($connection, [], $this);
    }

    public function on_save_options(array $post_request): array
    {
        return [];
    }

    public function enqueue_editor_script()
    {
        $adminHelper = AdminHelper::getInstance();
        $script_asset = require_once $this->pluginDirectoryPath . 'assets/module/jetformbuilder/build/editor.asset.php';

        $handle = $this->module()->get_handle($this->get_id());
        wp_enqueue_script(
            $handle,
            $this->pluginDirectoryUrl . 'assets/module/jetformbuilder/build/editor.js',
            $script_asset['dependencies'],
            $script_asset['version'],
            true
        );

        wp_set_script_translations($handle, 'mosparo-integration');

        wp_localize_script(
            $handle,
            'jfbMosparoIntegration',
            [
                'settingsUrl' => $adminHelper->buildConfigPageUrl(),
            ]
        );
    }

    protected function getFormData(array $form): array
    {
        $formData = [];
        $requiredFields = [];
        $verifiableFields = [];
        $ignoredTypes = apply_filters('mosparo_integration_jet_form_builder_ignored_field_types', [
            'checkbox-field',
            'radio-field',
            'choices-field',
            'switcher',
            'hidden-field',
            'submit-field',
            'calculated-field',
            'color-picker-field',
            'conditional-block',
            'form-break-field',
            'form-break-start',
            'form-block',
            'group-break-field',
            'heading-field',
            'media-field',
            'progress-bar',
            'range-field',
        ]);
        $verifiableFieldTypes = apply_filters('mosparo_integration_jet_form_builder_verifiable_field_types', [
            'text-field',
            'textarea-field',
            'wysiwyg-field',
        ]);

        jet_fb_context()->set_parsers(Block_Helper::get_blocks_by_post($form['_jet_engine_booking_form_id']));

        foreach (jet_fb_context()->iterate_parsers() as $name => $parser) {
            if ($parser->is_secure()) {
                continue;
            }

            $type = $parser->get_type();
            if (in_array($type, $ignoredTypes)) {
                continue;
            }

            $value = $form[$name] ?? null;

            if ($parser->get_inner_template() && is_array($value)) {
                [$innerFormData, $innerRequiredFields, $innerVerifiableFields] = $this->getInnerFormData($parser->get_inner_template(), $name, $value, $ignoredTypes, $verifiableFieldTypes);

                $formData = array_merge($formData, $innerFormData);
                $requiredFields = array_merge($requiredFields, $innerRequiredFields);
                $verifiableFields = array_merge($verifiableFields, $innerVerifiableFields);
            } else {
                $fieldKey = $name;
                $formData[$fieldKey] = $value;

                if ($parser->is_required) {
                    $requiredFields[] = $fieldKey;
                }

                if (in_array($type, $verifiableFieldTypes)) {
                    $verifiableFields[] = $fieldKey;
                }
            }
        }

        $formData = apply_filters('mosparo_integration_jet_form_builder_form_data', $formData);

        return [ $formData, $requiredFields, $verifiableFields ];
    }

    protected function getInnerFormData($template, string $name, array $value, array $ignoredTypes, array $verifiableFieldTypes): array
    {
        $formData = [];
        $requiredFields = [];
        $verifiableFields = [];

        foreach ($value as $key => $data) {
            foreach ($template->iterate_parsers() as $subName => $parser) {
                if ($parser->is_secure()) {
                    continue;
                }

                $rowKey = sprintf('%s[%d]', $name, $key);

                $type = $parser->get_type();
                if (in_array($type, $ignoredTypes)) {
                    continue;
                }

                $fieldKey = sprintf('%s[%s]', $rowKey, $subName);
                $formData[$fieldKey] = $data[$subName] ?? null;

                if ($parser->is_required) {
                    $requiredFields[] = $fieldKey;
                }

                if (in_array($type, $verifiableFieldTypes)) {
                    $verifiableFields[] = $fieldKey;
                }
            }
        }

        return [$formData, $requiredFields, $verifiableFields];
    }
}
