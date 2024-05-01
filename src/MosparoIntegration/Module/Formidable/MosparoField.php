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


    /** Field HTML **/
    public function default_html()
    {
        if (!$this->has_html) {
            return '';
        }

        $input = $this->input_html();

        $defaultHtml = <<<DEFAULT_HTML
<div id="frm_field_[id]_container" class="frm_form_field form-field [required_class][error_class]">
    $input
    [if description]<div class="frm_description" id="frm_desc_field_[key]">[description]</div>[/if description]
    [if error]<div class="frm_error" role="alert" id="frm_error_field_[key]">[error]</div>[/if error]
</div>
DEFAULT_HTML;

        return $defaultHtml;
    }

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
    public function front_field_input($args, $shortcodeAtts)
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
