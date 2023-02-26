<?php

namespace MosparoIntegration\Module\Formidable;

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
        $frontendHelper = FrontendHelper::getInstance();
        echo $frontendHelper->generateField([
            'designMode' => true
        ]);
    }

    /**
     * @inheritdoc
     */
    public function front_field_input($args, $shortcode_atts)
    {
        $frontendHelper = FrontendHelper::getInstance();
        if ($frontendHelper->isGutenbergRequest()) {
            return $frontendHelper->displayDummy();
        } else {
            return $frontendHelper->generateField();
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
