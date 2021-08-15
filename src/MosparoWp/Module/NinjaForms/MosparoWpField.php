<?php

namespace MosparoWp\Module\NinjaForms;

use MosparoWp\Helper\ConfigHelper;
use MosparoWp\Helper\FrontendHelper;
use NF_Abstracts_Field;

class MosparoWpField extends NF_Abstracts_Field
{
    /**
     * @var string
     */
    protected $_name = 'mosparo';

    /**
     * @var string
     */
    protected $_type = 'mosparo';

    /**
     * @var string
     */
    protected $_section = 'misc';

    /**
     * @var string
     */
    protected $_icon = 'cogs';

    /**
     * @var array
     */
    protected $_templates = ['mosparo'];

    /**
     * @var string
     */
    protected $_test_value = '';

    /**
     * @var string
     */
    protected $_wrap_template = 'wrap-no-label';

    /**
     * @var array
     */
    protected $_settings = [];

    /**
     * Constructs the object
     */
    public function __construct()
    {
        parent::__construct();

        $this->_nicename = esc_html__('mosparo', 'mosparo-wp');

        add_filter('nf_sub_hidden_field_types', [$this, 'hideFieldType']);
    }

    /**
     * Localizes the settings
     *
     * @param array $settings
     * @param int $form
     * @return array
     */
    public function localize_settings($settings, $formId)
    {
        $configHelper = ConfigHelper::getInstance();
        $frontendHelper = FrontendHelper::getInstance();

        if (!$configHelper->getLoadResourcesAlways()) {
            $frontendHelper->registerResources();
        }

        $settings['host'] = $configHelper->getHost();
        $settings['publicKey'] = $configHelper->getPublicKey();

        $options = $frontendHelper->getFrontendOptions([]);
        $options['inputFieldSelector'] = '[name]:not(.mosparo__ignored-field):not(.nf-field-hp)';
        $settings['mosparoOptions'] = $options;

        return $settings;
    }

    /**
     * Validates the field
     *
     * @param $field
     * @param $data
     * @return array|void
     */
    public function validate($field, $data)
    {
        // Do nothing because we do the validation in the action
    }

    /**
     * Hide mosparo field type from other actions
     *
     * @param array $fieldTypes
     * @return array
     */
    public function hideFieldType($fieldTypes)
    {
        $fieldTypes[] = $this->_name;

        return $fieldTypes;
    }
}