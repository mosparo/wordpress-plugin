<?php

namespace MosparoIntegration\Module\Formidable;

use MosparoIntegration\Helper\ConfigHelper;
use MosparoIntegration\Helper\FrontendHelper;
use FrmFieldType;

class MosparoField extends FrmFieldType
{
    /**
     * @inheritdoc
     */
    protected $has_input = false;

    /**
     * @inheritdoc
     */
    public function show_on_form_builder($name = '')
    {
        $configHelper = ConfigHelper::getInstance();
        $connection = $configHelper->getConnectionFor('module_formidable');
        if ($connection === false) {
            echo __('No mosparo connection available. Please configure the connection in the mosparo settings.', 'mosparo-integration');
            return;
        }

        $frontendHelper = FrontendHelper::getInstance();
        echo $frontendHelper->generateField($connection, [
            'designMode' => true
        ]);
    }

    /**
     * @inheritdoc
     */
    public function front_field_input($args, $shortcode_atts)
    {
        $configHelper = ConfigHelper::getInstance();
        $connection = $configHelper->getConnectionFor('module_formidable');
        if ($connection === false) {
            return __('No mosparo connection available. Please configure the connection in the mosparo settings.', 'mosparo-integration');
        }

        $frontendHelper = FrontendHelper::getInstance();
        if ($frontendHelper->isGutenbergRequest()) {
            return $frontendHelper->displayDummy();
        } else {
            return $frontendHelper->generateField($connection);
        }
    }

    /**
     * @inheritdoc
     */
    public function validate($args)
    {
        return [];
    }
}
