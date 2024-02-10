<?php

namespace MosparoIntegration\Module\ContactForm7;

use MosparoIntegration\Helper\ConfigHelper;
use MosparoIntegration\Helper\FrontendHelper;
use MosparoIntegration\Helper\VerificationHelper;
use WPCF7_FormTag;
use WPCF7_Submission;

class MosparoField
{
    private static $instance;

    protected $originalValues = [];

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
        add_action('wpcf7_init', [$this, 'addFormTag'], 10, 0);
        add_filter('wpcf7_spam', [$this, 'verifyResponse'], 9, 2);
        add_action('wpcf7_admin_init', [$this, 'addFormTagGenerator'], 100);

        // Register the filters to store the original value
        add_action('wpcf7_posted_data_select', [$this, 'storeOriginalValue'], 10, 3);
        add_action('wpcf7_posted_data_select*', [$this, 'storeOriginalValue'], 10, 3);
    }

    public function addFormTag()
    {
        wpcf7_add_form_tag('mosparo', [$this, 'displayFormField'], []);
    }

    public function addFormTagGenerator()
    {
        wpcf7_add_tag_generator('mosparo', 'mosparo', '', [$this, 'getTagGeneratorContent'], ['nameless' => 1]);
    }

    public function displayFormField()
    {
        $configHelper = ConfigHelper::getInstance();
        $connection = $configHelper->getConnectionFor('module_contact-form-7');
        if ($connection === false) {
            return __('No mosparo connection available. Please configure the connection in the mosparo settings.', 'mosparo-integration');
        }

        $frontendHelper = FrontendHelper::getInstance();
        return $frontendHelper->generateField($connection, [], $this);
    }

    public function storeOriginalValue($value, $originalValue, WPCF7_FormTag $tag)
    {
        $this->originalValues[$tag->name] = $originalValue;

        return $value;
    }

    public function verifyResponse($spam, WPCF7_Submission $submission)
    {
        if ($spam) {
            return $spam;
        }

        // Stop the verification if the mosparo tag is not found in the form
        if (!$this->searchMosparoFieldInForm($submission)) {
            return $spam;
        }

        $configHelper = ConfigHelper::getInstance();
        $connection = $configHelper->getConnectionFor('module_contact-form-7');
        if ($connection === false) {
            return $spam;
        }

        [ $formData, $requiredFields, $verifiableFields ] = $this->getFormData($submission);
        $submitToken = trim(sanitize_text_field($formData['_mosparo_submitToken'] ?? ''));
        $validationToken = trim(sanitize_text_field($formData['_mosparo_validationToken'] ?? ''));

        // If the tokens are not available, the submission cannot be valid.
        if (empty($submitToken) || empty($validationToken)) {
            $submission->add_spam_log([
                'agent' => 'mosparo-integration-cf7',
                'reason' => __('Submit or validation token is empty.', 'mosparo-integration'),
            ]);

            return true;
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
                return false;
            }
        }

        // Everything else is spam
        $submission->add_spam_log( array(
            'agent' => 'mosparo-integration-cf7',
            'reason' => __('Verification failed which means the form contains spam.', 'mosparo-integration')
        ));

        return true;
    }

    protected function getFormData(WPCF7_Submission $submission): array
    {
        $requiredFields = [];
        $verifiableFields = [];
        $ignoredTypes = apply_filters('mosparo_integration_cf7_ignored_field_types', [
            'checkbox',
            'checkbox*',
            'radio',
            'acceptance',
            'hidden',
            'file',
            'file*',
            'submit',
            'mosparo'
        ]);
        $verifiableFieldTypes = apply_filters('mosparo_integration_cf7_verifiable_field_types', [
            'text',
            'text*',
            'textarea',
            'textarea*',
            'email',
            'email*',
            'url',
            'url*',
        ]);

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

            if (substr($tag->type, -1) === '*') {
                $requiredFields[] = $tag->name;
            }

            $value = null;
            if (isset($this->originalValues[$tag->name])) {
                $value = $this->originalValues[$tag->name];
            } else {
                $value = $submission->get_posted_data($tag->name);
            }

            if ($value !== null) {
                $formData[$tag->name] = $value;
            }

            if (in_array($tag->type, $verifiableFieldTypes)) {
                $verifiableFields[] = $tag->name;
            }
        }

        $formData = apply_filters('mosparo_integration_cf7_form_data', $formData);

        return [ $formData, $requiredFields, $verifiableFields ];
    }

    public function getTagGeneratorContent($contactForm, $args = '')
    {
        $args = wp_parse_args($args, []);
        ?>
        <div class="control-box">
            <fieldset>
                <legend>
                    <?php echo sprintf(
                            __('Adds a mosparo field to your form. Please configure the connection to mosparo in the %ssettings%s.', 'mosparo-integration'),
                            '<a href="' . esc_url(get_admin_url(null, 'options-general.php?page=mosparo-configuration')) . '">',
                            '</a>'
                        );
                    ?>
                </legend>

                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="<?php echo esc_attr($args['content'] . '-id'); ?>"><?php echo esc_html(__('Id attribute', 'contact-form-7')); ?></label></th>
                            <td><input type="text" name="id" class="idvalue oneline option" id="<?php echo esc_attr($args['content'] . '-id'); ?>" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="<?php echo esc_attr($args['content'] . '-class'); ?>"><?php echo esc_html(__('Class attribute', 'contact-form-7')); ?></label></th>
                            <td><input type="text" name="class" class="classvalue oneline option" id="<?php echo esc_attr($args['content'] . '-class'); ?>" /></td>
                        </tr>
                    </tbody>
                </table>
            </fieldset>
        </div>

        <div class="insert-box">
            <input type="text" name="mosparo" class="tag code" readonly="readonly" onfocus="this.select()" />

            <div class="submitbox">
                <input type="button" class="button button-primary insert-tag" value="<?php echo esc_attr(__('Insert Tag', 'contact-form-7')); ?>" />
            </div>
        </div>
        <?php
    }

    protected function searchMosparoFieldInForm(WPCF7_Submission $submission)
    {
        $tags = $submission->get_contact_form()->scan_form_tags();
        foreach ($tags as $tag) {
            if ($tag->type === 'mosparo') {
                return true;
            }
        }

        return false;
    }
}
