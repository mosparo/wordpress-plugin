<?php

namespace MosparoIntegration\Module\NinjaForms;

use MosparoIntegration\Helper\ConfigHelper;
use MosparoIntegration\Helper\FrontendHelper;
use NF_Abstracts_Field;

class MosparoField extends NF_Abstracts_Field
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

        $this->_nicename = esc_html__('mosparo', 'mosparo-integration');

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

        $connection = $configHelper->getConnectionFor('module_ninja-forms');
        if ($connection === false) {
            return [];
        }

        $frontendHelper->registerResources($connection);

        $settings['host'] = $connection->getHost();
        $settings['uuid'] = $connection->getuuid();
        $settings['publicKey'] = $connection->getPublicKey();

        $options = $frontendHelper->getFrontendOptions([], $connection);
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