<?php

use MosparoIntegration\Helper\ConfigHelper;

function mosparoModuleSettingsFieldInputHtml(ConfigHelper $configHelper, $fieldKey, $field)
{
    $html = '';
    switch ($field['type']) {
        case "boolean":
            $checkboxHtml = '<input type="checkbox" class="" name="' . esc_attr($fieldKey) . '" id="' . esc_attr($fieldKey) . '" ' . checked($configHelper->getTypedValue($field['value'], $field['type']), true, false) . ' /> ';

            if (isset($field['description'])) {
                $html .= '<fieldset>';
                $html .= '<legend class="screen-reader-text"><span>' . $field['label'] . '</span></legend>';
                $html .= '<label for="' . esc_attr($fieldKey) . '">' . $checkboxHtml . esc_html($field['description']) . '</label>';
                $html .= '</fieldset>';
            } else {
                $html .= $checkboxHtml;
            }

            break;
        case "number":
        case "string":
        case "text":
        default:
            $html .= '<input type="' . esc_attr($field['type']) . '" name="' . esc_attr($fieldKey) . '" id="' . esc_attr($fieldKey) . '" value="' . esc_attr($configHelper->getTypedValue($field['value'], $field['type'])) . '" /> ';

            if (isset($field['description'])) {
                $html .= '<p class="description">' . esc_html($field['description']) . '</p>';
            }
    }

    return $html;
};

function mosparoModuleSettingsDisplayRow(ConfigHelper $configHelper, $fieldKey, $field)
{
    $labelHtml = '<th><label for="' . esc_attr($fieldKey) . '">' . esc_html($field['label']) . '</label></th>';
    if ($field['type'] === 'boolean') {
        $labelHtml = '<th scope="row">' . esc_html($field['label']) . '</th>';
    }

    $html = '<tr>';
    $html .= $labelHtml;
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
    $html .= esc_html($formHeader);
    $html .= '</h2>';

    echo $html;
};

?>

<div class="wrap">
    <h1 class="mosparo-header">
        <?php echo esc_html(get_admin_page_title()); ?>
        &ndash;
        <?php echo sprintf(__('%s &ndash; Module settings', 'mosparo-integration'), $module->getName()); ?>
    </h1>

    <?php $this->displayAdminNotice(); ?>

    <?php mosparoModuleSettingsFormHeader($module); ?>
    <form method="post" action="<?php echo esc_url($this->buildConfigPostUrl($action)); ?>">
        <input type="hidden" name="module" value="<?php echo esc_attr($module->getKey()); ?>" />

        <table class="form-table" role="presentation">
            <tbody>
            <?php
                foreach ($module->getSettings()->getFields() as $key => $setting) {
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
