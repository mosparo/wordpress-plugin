var mosparoInstances = [];

var mosparoFieldController = Marionette.Object.extend({
    initialize: function()
    {
        jQuery(document).on('nfFormReady', function (ev, form) {
            form.$el.find('.mosparo-integration-container').each(function () {
                let model = nfRadio.channel('fields').request('get:field', jQuery(this).data('field-id'));
                let formModel = nfRadio.channel("form-" + model.get('formID')).request("get:form")

                let mosparoOptions = model.get('mosparoOptions');
                mosparoOptions.onCheckForm = function () {
                    nfRadio.channel('fields').trigger('change:modelValue', model);
                };

                mosparoOptions.doSubmitFormInvisible = function () {
                    nfRadio.channel('form-' + model.get('formID')).request('submit', formModel);
                };

                mosparoOptions.onValidateFormInvisible = function () {
                    nfRadio.channel('form-' + model.get('formID')).trigger('submit:failed', {});
                }

                let id = "mosparo-box-" + model.get('id');
                mosparoInstances[id] = new mosparo(id, model.get('host'), model.get('uuid'), model.get('publicKey'), mosparoOptions);
            });
        });

        // Check the field status when the form is validated
        var fieldsChannel = nfRadio.channel('fields');
        this.listenTo(fieldsChannel, 'change:modelValue', this.validateRequired);

        // Check the field status when the form is submitted
        var submitChannel = nfRadio.channel('submit');
        this.listenTo(submitChannel, 'validate:field', this.validateRequired);

        var formsChannel = nfRadio.channel('forms');
        formsChannel.reply('maybe:validate', this.stopValidateIfInvisible, this);
        this.listenTo(formsChannel, 'submit:response', this.afterSubmit);
        this.listenTo(formsChannel, 'init:model', this.registerSubmitHandler);

        // Store the tokens in the field data before submission
        var fieldChannel = nfRadio.channel('mosparo');
        fieldChannel.reply('get:submitData', this.beforeSubmit, this);
    },

    registerSubmitHandler: function (model)
    {
        let formChannel = nfRadio.channel('form-' + model.get('id'));
        formChannel.reply('maybe:submit', this.stopValidateIfInvisible, this);
        this.listenTo(formChannel, 'submit:cancel', this.checkFormIfInvisible);
    },

    beforeSubmit: function (fieldData, field)
    {
        let id = 'mosparo-box-' + field.attributes.id;
        let mosparoInstance = mosparoInstances[id];

        if (!mosparoInstance) {
            return;
        }

        if (!mosparoInstance.checkboxFieldElement.checked || !mosparoInstance.verifyCheckedFormData()) {
            return false;
        }

        let el = jQuery('#' + id);
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

        let id = jQuery('#nf-form-' + model.get('id') + '-cont .mosparo__container').prop('id');
        let mosparoInstance = mosparoInstances[id];
        if (!mosparoInstance) {
            return;
        }

        let el = jQuery('#mosparo-box-' + model.attributes.id).find('input[type="checkbox"]');
        if (!mosparoInstance.invisible) {
            if (el[0].checked) {
                nfRadio.channel('fields').request('remove:error', model.get('id'), 'custom-field-error');
            } else {
                nfRadio.channel('fields').request('add:error', model.get('id'), 'custom-field-error');
            }
        }
    },

    stopValidateIfInvisible: function (model)
    {
        let id = jQuery('#nf-form-' + model.get('id') + '-cont .mosparo__container').prop('id');
        let mosparoInstance = mosparoInstances[id];
        if (!mosparoInstance) {
            return;
        }

        if (mosparoInstance.invisible && (!mosparoInstance.checkboxFieldElement.checked || !mosparoInstance.verifyCheckedFormData())) {
            return false;
        }
    },

    checkFormIfInvisible: function (model)
    {
        let id = jQuery('#nf-form-' + model.get('id') + '-cont .mosparo__container').prop('id');
        let mosparoInstance = mosparoInstances[id];

        if (!mosparoInstance) {
            return;
        }

        if (mosparoInstance.invisible && (!mosparoInstance.checkboxFieldElement.checked || !mosparoInstance.verifyCheckedFormData())) {
            let ev = new CustomEvent('c_submit');
            mosparoInstance.onSubmit(ev);
        }
    }
});

jQuery(document).ready(function($) {
    new mosparoFieldController();
});