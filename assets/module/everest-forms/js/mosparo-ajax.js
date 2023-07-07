var activeForm = false;

jQuery(function($) {
    'use strict';
    var evf_ajax_submission_init = function() {
        var form = $('form[data-ajax_submission="1"]');
        form.each(function (idx, formEl) {
            $(document).ready(function () {
                let form = $(formEl);
                let mosparoEl = form.find('.mosparo__container');
                let id = mosparoEl.attr('id');
                let mosparoInstance = mosparoInstances[id];
                let btn = form.find('.evf-submit');

                form.on('evf-frontend-ajax-submission-before-submit', function (ev) {
                    if (mosparoInstance.invisible && (!mosparoInstance.checkboxFieldElement.checked || !mosparoInstance.verifyCheckedFormData())) {
                        return false;
                    }
                });
            });
        });
    };

    evf_ajax_submission_init();
});
