(function($) {
    $(function() {
        let topSelector = $('#modules_bulk-action-selector-top');
        let bottomSelector = $('#modules_bulk-action-selector-bottom');
        let topButton = $('#modules_doaction');
        let bottomButton = $('#modules_doaction2');

        bottomSelector.on('change', function () {
            topSelector.val($(this).val());
        });

        topSelector.on('change', function () {
            bottomSelector.val($(this).val());
        });

        bottomButton.on('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            topButton.trigger('click');
        });
    });
}(jQuery));
