<?php

use MosparoIntegration\Helper\ConfigHelper;

function mosparoModuleSettingsFieldInputHtml(ConfigHelper $configHelper, $fieldKey, $field)
{
    $html = '';
    switch ($field['type']) {
        case "boolean":
            $html .= '<input type="checkbox" class="" name="' . esc_attr($fieldKey) . '" id="' . esc_attr($fieldKey) . '" ' . checked($configHelper->getTypedValue($field['value'], $field['type']), true, false) . ' /> ';
            break;
        case "number":
        case "string":
        case "text":
        default:
            $html .= '<input type="'.esc_attr($field['type']).'" name="' . esc_attr($fieldKey) . '" id="' . esc_attr($fieldKey) . '" value="' . esc_attr($configHelper->getTypedValue($field['value'], $field['type'])) . '" /> ';
    }

    if (isset($field['description'])) {
        $html .= '<p class="description">' . $field['description'] . '</p>';
    }

    return $html;
};

function mosparoModuleSettingsDisplayRow(ConfigHelper $configHelper, $fieldKey, $field)
{
    $html = '<tr>';
    $html .= '<th><label for="'.esc_attr($fieldKey).'">'.$field['label'].'</label></th>';
    $html .= '<td>';
    $html .= mosparoModuleSettingsFieldInputHtml($configHelper, $fieldKey, $field);
    $html .= '</td>';
    $html .= '</tr>';

    echo $html;
};

function mosparoModuleSettingsFormHeader($module)
{
    if (!isset($module->getSettings()->getSettingsForm()['header'])) {
        return;
    }

    $formHeader = $module->getSettings()->getSettingsForm()['header'];
    $html = '<h2>';
    $html .= $formHeader;
    $html .= '</h2>';

    echo $html;
};

?>

<div class="wrap">
    <h1 class="mosparo-header">
        <?php echo esc_html(get_admin_page_title()); ?>
        &ndash;
        <?php echo sprintf(__('"%s" Module configuration', 'mosparo-integration'), $module->getName()); ?>
    </h1>

    <?php $this->displayAdminNotice(); ?>

    <div class="mosparo-two-columns">
        <div class="left-column">
            <?php mosparoModuleSettingsFormHeader($module); ?>
            <form method="post" action="<?php echo esc_url($this->buildConfigPostUrl($action)); ?>">
                <input type="hidden" name="module" value="<?php echo esc_attr($module->getKey()); ?>" />

                <table class="form-table" role="presentation">
                    <tbody>
                    <?php
                    foreach ( $module->getSettings()->getFields() as $key => $setting ) {
                        mosparoModuleSettingsDisplayRow($configHelper, $module->getKey() . '_' . $key, $setting);
                    }
                    ?>
                    </tbody>
                </table>
                <p>
                <?php
                    submit_button(__('Save module settings', 'mosparo-integration'), 'primary', 'submit', false);
                ?>
                </p>
            </form>
        </div>
        <div class="right-column">
            <?php $this->displayHowToUseBox(false); ?>
        </div>
    </div>
</div>
