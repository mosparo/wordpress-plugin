<?php

namespace MosparoWp\Module\ContactForm7;

use MosparoWp\Helper\ConfigHelper;
use MosparoWp\Helper\FrontendHelper;
use MosparoWp\Helper\VerificationHelper;
use WPCF7_FormTag;
use WPCF7_Pipes;
use WPCF7_Submission;

class MosparoWpField
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
        $configHelper = ConfigHelper::getInstance();
        if (!$configHelper->isActive() || !$configHelper->isModuleActive('contact-form-7')) {
            return;
        }

        add_action('wpcf7_init', [$this, 'addFormTag'], 10, 0);
        add_filter('wpcf7_spam', [$this, 'verifyResponse'], 9, 2);
        add_action('wpcf7_admin_init', [$this, 'addFormTagGenerator'], 100);

        // Register the filters to store the original value
        add_action('wpcf7_posted_data_select', [$this, 'storeOriginalValue'], 10, 3);
        add_action('wpcf7_posted_data_select*', [$this, 'storeOriginalValue'], 10, 3);
        add_action('wpcf7_posted_data_checkbox', [$this, 'storeOriginalValue'], 10, 3);
        add_action('wpcf7_posted_data_checkbox*', [$this, 'storeOriginalValue'], 10, 3);
        add_action('wpcf7_posted_data_radio', [$this, 'storeOriginalValue'], 10, 3);
    }

    public function addFormTag()
    {
        wpcf7_add_form_tag('mosparo', [$this, 'displayFormField'], [
            'name-attr' => true,
        ]);
    }

    public function addFormTagGenerator()
    {
        wpcf7_add_tag_generator('mosparo', 'mosparo', '', [$this, 'getTagGeneratorContent'], ['nameless' => 1]);
    }

    public function displayFormField()
    {
        $frontendHelper = FrontendHelper::getInstance();
        return $frontendHelper->generateField();
    }

    public function storeOriginalValue($value, $originalValue, WPCF7_FormTag $tag)
    {
        $pipes = $tag->pipes;
        if (!empty($tag->name) && WPCF7_USE_PIPE && $pipes instanceof WPCF7_Pipes && !$pipes->zero()) {
            $this->originalValues[$tag->name] = $originalValue;
        }

        return $value;
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
        $ignoredTypes = apply_filters('mosparo_wp_cf7_ignored_field_types', [
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

            if (isset($this->originalValues[$tag->name])) {
                $value = $this->originalValues[$tag->name];
            } else {
                $value = $submission->get_posted_data($tag->name);
            }

            if ($value !== null) {
                $formData[$tag->name] = $value;
            }
        }

        $formData = apply_filters('mosparo_wp_cf7_form_data', $formData);

        return $formData;
    }

    public function getTagGeneratorContent($contactForm, $args = '')
    {
        $args = wp_parse_args($args, []);
        ?>
        <div class="control-box">
            <fieldset>
                <legend>
                    <?php echo sprintf(
                            __('Adds a mosparo field to your form. Please configure the connection to mosparo in the %ssettings%s.', 'mosparo-wp'),
                            '<a href="' . get_admin_url(null, 'options-general.php?page=mosparo-configuration') . '">',
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
}
