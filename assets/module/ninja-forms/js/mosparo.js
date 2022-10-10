var mosparoInstances = [];

var mosparoFieldController = Marionette.Object.extend( {
    initialize: function()
    {
        jQuery(document).on('nfFormReady', function (ev, form) {
            form.$el.find('.mosparo-integration-container').each(function () {
                let model = nfRadio.channel('fields').request('get:field', jQuery(this).data('field-id'));

                let mosparoOptions = model.get('mosparoOptions');
                mosparoOptions.onCheckForm = function () {
                    Backbone.Radio.channel('fields').trigger('change:modelValue', model);
                };

                let id = "mosparo-box-" + model.get('id');
                mosparoInstances[id] = new mosparo(id, model.get('host'), model.get('uuid'), model.get('publicKey'), mosparoOptions);
            });
        });

        // Check the field status when the form is validated
        var fieldsChannel = Backbone.Radio.channel('fields');
        this.listenTo(fieldsChannel, 'change:modelValue', this.validateRequired);

        // Check the field status when the form is submitted
        var submitChannel = Backbone.Radio.channel('submit');
        this.listenTo(submitChannel, 'validate:field', this.validateRequired);

        var formsChannel = Backbone.Radio.channel("forms");
        this.listenTo(formsChannel, "submit:response", this.afterSubmit);

        // Store the tokens in the field data before submission
        var fieldChannel = Backbone.Radio.channel('mosparo');
        fieldChannel.reply('get:submitData', this.beforeSubmit, this);
    },

    beforeSubmit: function (fieldData, field)
    {
        let el = jQuery('#mosparo-box-' + field.attributes.id);
        fieldData.value = {
            submitToken: el.find('input[name="_mosparo_submitToken"]').val() || '',
            validationToken: el.find('input[name="_mosparo_validationToken"]').val() || ''
        };

        return fieldData;
    },

    afterSubmit: function (response)
    {
        if (typeof response.errors.form !== 'undefined' && typeof response.errors.form.spam !== 'undefined') {
            let id = jQuery('#nf-form-' + response.data.form_id + '-cont .mosparo__container').prop('id');

            if (!mosparoInstances[id]) {
                return;
            }

            mosparoInstances[id].resetState();
            mosparoInstances[id].requestSubmitToken();
        }
    },

    validateRequired: function(model)
    {
        if (model.get('type') !== 'mosparo') {
            return;
        }

        let el = jQuery('#mosparo-box-' + model.attributes.id).find('input[type="checkbox"]');
        if (el[0].checked) {
            Backbone.Radio.channel('fields').request('remove:error', model.get('id'), 'custom-field-error');
        } else {
            Backbone.Radio.channel('fields').request('add:error', model.get('id'), 'custom-field-error');
        }
    }
});

jQuery(document).ready(function($) {
    new mosparoFieldController();
});