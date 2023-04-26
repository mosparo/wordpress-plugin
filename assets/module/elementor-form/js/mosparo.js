class MosparoElementorForm extends elementorModules.editor.utils.Module {
    static enqueueFrontendJavaScript(url)
    {
        if (!elementorFrontend.elements.$body.find('[id="mosparo-frontend-js"]').length) {
            elementorFrontend.elements.$body.append('<scr' + 'ipt src="' + url + '" id="mosparo-frontend-js">' + '</scri' + 'pt>');
        }
    }

    static renderHtmlForField(item)
    {
        const config = elementorPro.config.forms[item.field_type];

        if (!config.connectionAvailable) {
            return (
                '<div class="elementor-alert elementor-alert-info">' +
                config.messageConnectionRequired +
                '</div>'
            );
        }

        MosparoElementorForm.enqueueFrontendJavaScript(config.frontendJsUrl);

        return (
            '<div id="mosparo-' + item.custom_id + '"></div>' +
            '<script>' +
            'jQuery(document).ready(function () {' +
                'new mosparo(' +
                    '"mosparo-' + item.custom_id + '", ' +
                    '"' + config.mosparoHost + '", ' +
                    '"' + config.mosparoUuid + '", ' +
                    '"' + config.mosparoPublicKey + '", ' +
                    '{loadCssResource: true, designMode: true}' +
                ');' +
            '});' +
            '</script>'
        );
    }

    renderField(inputField, item)
    {
        inputField += (
            '<div class="elementor-field" id="form-field-' + item.custom_id + '">' +
            '<div class="elementor-mosparo' + _.escape(item.css_classes) + '">' +
            MosparoElementorForm.renderHtmlForField(item) +
            '</div>' +
            '</div>'
        );

        return inputField;
    }

    filterItem(item)
    {
        if ('mosparo' === item.field_type) {
            item.field_label = false;
        }

        return item;
    }

    onInit()
    {
        elementor.hooks.addFilter('elementor_pro/forms/content_template/item', this.filterItem);
        elementor.hooks.addFilter('elementor_pro/forms/content_template/field/mosparo', this.renderField, 10, 2);
    }
}

window.mosparoElementorForm = new MosparoElementorForm();