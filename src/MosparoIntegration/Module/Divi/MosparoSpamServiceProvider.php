<?php

namespace MosparoIntegration\Module\Divi;

use ET_Core_API_Spam_Provider;
use MosparoIntegration\Helper\ConfigHelper;
use MosparoIntegration\Helper\FrontendHelper;
use MosparoIntegration\Helper\VerificationHelper;

class MosparoSpamServiceProvider extends ET_Core_API_Spam_Provider
{
    /**
     * @inheritDoc
     */
    public $name = 'mosparo';

    /**
     * @inheritDoc
     */
    public $slug = 'mosparo';

    /**
     * @inheritDoc
     */
    public $max_accounts = 1;

    public function __construct($owner = 'ET_Core', $account_name = 'default', $api_key = '')
    {
        parent::__construct($owner, $account_name, $api_key);

        $this->addActionsAndFilters();
    }

    protected function addActionsAndFilters()
    {
        add_filter('option_et_core_api_spam_options', [$this, 'enforceAccount']);
        add_filter('et_pb_contact_form_shortcode_output', [$this, 'addMosparoBox']);
        add_filter('et_pb_module_shortcode_attributes', [$this, 'setToken'], 10, 5);

        if (!is_admin() && !et_core_is_fb_enabled()) {
            add_action('wp_enqueue_scripts', array($this, 'enqueueScripts'), 1000);

            /* These two hooks should not be necessary, but the Divi stylesheets are way too aggressive. */
            add_filter('et_builder_plugin_stylesheet_contents', [$this, 'adjustCssSelector']);
            add_action('wp_print_styles', [$this, 'adjustCriticalCssSelector']);
        }
    }

    public function enforceAccount($value)
    {
        $unserializedValue = maybe_unserialize($value);
        $unserializedValue['accounts']['mosparo'] = [
            'Default mosparo connection' => [
                'mosparoHost' => 'mosparo-default',
                'mosparoUuid' => 'mosparo-default',
                'mosparoPublicKey' => 'mosparo-default',
                'mosparoPrivateKey' => 'mosparo-default',
            ],
        ];

        return $unserializedValue;
    }

    public function addMosparoBox($output)
    {
        $configHelper = ConfigHelper::getInstance();
        $connection = $configHelper->getConnectionFor('module_divi');
        if ($connection === false) {
            return __('No mosparo connection available. Please configure the connection in the mosparo settings.', 'mosparo-integration');
        }

        $frontendHelper = FrontendHelper::getInstance();
        $mosparoBox = sprintf(
            '<div class="et_pb_contact_field et_pb_contact_field_last">%s</div>',
            $frontendHelper->generateField($connection, [], $this)
        );

        $searchPattern = '<div class="et_contact_bottom_container">';
        $output = str_replace($searchPattern, $mosparoBox . $searchPattern, $output);

        return $output;
    }

    public function setToken($props, $atts, $renderSlug, $address, $content)
    {
        if ($renderSlug === 'et_pb_contact_form' && isset($_POST['token']) && $_POST['token'] === '') {
            // Set some dummy value to the field so that we can process it
            $_POST['token'] = 'mosparo_token';
        }

        return $props;
    }

    public function enqueueScripts()
    {
        wp_dequeue_script('et-core-api-spam-recaptcha');
    }

    public function adjustCssSelector($cssContent)
    {
        $cssContent = str_replace('#et-boc .et-l div', '#et-boc .et-l div:not([class*="mosparo"])', $cssContent);
        $cssContent = str_replace('#et-boc .et-l label', '#et-boc .et-l label:not([class*="mosparo"])', $cssContent);
        $cssContent = str_replace('#et-boc .et-l span', '#et-boc .et-l span:not([class*="mosparo"])', $cssContent);
        $cssContent = str_replace('#et-boc .et-l input', '#et-boc .et-l input:not([class*="mosparo"]):not([id*="mosparo"])', $cssContent);

        return $cssContent;
    }

    public function adjustCriticalCssSelector()
    {
        if (isset(wp_styles()->registered['divi-builder-dynamic-critical']->extra['after'][0])) {
            $cssContent = wp_styles()->registered['divi-builder-dynamic-critical']->extra['after'][0];

            $cssContent = str_replace('.et-db #et-boc .et-l p.et_pb_contact_field{', '.et-db #et-boc .et-l p.et_pb_contact_field, .et-db #et-boc .et-l div.et_pb_contact_field{', $cssContent);
            $cssContent = str_replace('.et-db #et-boc .et-l .et_pb_contact_field *', '.et-db #et-boc .et-l .et_pb_contact_field *:not([class*="mosparo"]):not([id*="mosparo"])', $cssContent);

            wp_styles()->registered['divi-builder-dynamic-critical']->extra['after'][0] = $cssContent;
        }
    }

    public function is_enabled()
    {
        return true;
    }

    public function verify_form_submission()
    {
        $rawData = null;
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'et_pb_contact_email_fields_') === 0) {
                $rawData = $value;
                break;
            }
        }

        if ($rawData) {
            $preparedJson = str_replace('\\', '', $rawData);
            $data = json_decode($preparedJson, true);

            if (!$data) {
                return __('No form field data found.', 'mosparo-integration');
            }
        }

        return $this->verifyFormData($data);
    }

    public function get_account_fields()
    {
        return array(
            'mosparoHost'   => array(
                'label' => esc_html__('Host', 'mosparo-integration'),
            ),
            'mosparoUuid' => array(
                'label' => esc_html__('UUID', 'mosparo-integration'),
            ),
            'mosparoPublicKey' => array(
                'label' => esc_html__('Public key', 'mosparo-integration'),
            ),
            'mosparoPrivateKey' => array(
                'label' => esc_html__('Private key', 'mosparo-integration'),
            ),
        );
    }

    public function verifyFormData($formFields)
    {
        // Stop the verification if the mosparo tokens are not found in the post data
        $mosparoData = $this->searchMosparoDataInPostData($_POST);
        if (!$mosparoData) {
            return __('Cannot find mosparo fields in request.', 'mosparo-integration');
        }

        $configHelper = ConfigHelper::getInstance();
        $connection = $configHelper->getConnectionFor('module_divi');
        if ($connection === false) {
            return __('No mosparo connection available. Please configure the connection in the mosparo settings.', 'mosparo-integration');
        }

        [ $formData, $requiredFields, $verifiableFields ] = $this->getFormData($formFields, $_POST);
        $submitToken = trim($mosparoData['_mosparo_submitToken'] ?? '');
        $validationToken = trim($mosparoData['_mosparo_validationToken'] ?? '');

        // If the tokens are not available, the submission cannot be valid.
        if (empty($submitToken) || empty($validationToken)) {
            return __('No submit or validation tokens found.', 'mosparo-integration');
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
                return ['success' => true, 'score' => 1000];
            }
        }

        return __('Verification failed which means the form contains spam.', 'mosparo-integration');
    }

    protected function getFormData($fields, $fieldData)
    {
        $formData = [];
        $requiredFields = [];
        $verifiableFields = [];
        $ignoredTypes = apply_filters('mosparo_integration_divi_ignored_field_types', [
            'checkbox',
            'radio',
        ]);
        $verifiableFieldTypes = apply_filters('mosparo_integration_divi_verifiable_field_types', [
            'input',
            'text',
            'email',
        ]);

        foreach ($fields as $field) {
            if (!$field['field_type'] || in_array($field['field_type'], $ignoredTypes)) {
                continue;
            }

            $fullKey = $field['field_id'];

            $formData[$fullKey] = sanitize_text_field($fieldData[$fullKey]);

            if (($field['required_mark'] ?? false) === 'required') {
                $requiredFields[] = $fullKey;
            }

            if (in_array($field['field_type'], $verifiableFieldTypes)) {
                $verifiableFields[] = $fullKey;
            }
        }

        $formData = apply_filters('mosparo_integration_divi_form_data', $formData);

        return [ $formData, $requiredFields, $verifiableFields ];
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
}