// Create a new object for custom validation of a custom field.
var mosparoFieldController = Marionette.Object.extend( {
    initialize: function()
    {
        jQuery(document).on('nfFormReady', function () {
            jQuery('.mosparo-wp-container').each(function () {
                let model = nfRadio.channel('fields').request('get:field', jQuery(this).data('field-id'));

                let mosparoOptions = model.get('mosparoOptions');
                mosparoOptions.onCheckForm = function () {
                    Backbone.Radio.channel('fields').trigger('change:modelValue', model);
                };

                new mosparo("mosparo-box-" + model.get('id'), model.get('host'), model.get('publicKey'), mosparoOptions);
            });
        });

        // Check the field status when the form is validated
        var fieldsChannel = Backbone.Radio.channel('fields');
        this.listenTo(fieldsChannel, 'change:modelValue', this.validateRequired);

        // Check the field status when the form is submitted
        var submitChannel = Backbone.Radio.channel('submit');
        this.listenTo(submitChannel, 'validate:field', this.validateRequired);

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