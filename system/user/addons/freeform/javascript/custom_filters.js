;(function (global, $) {
    //es5 strict mode
    "use strict";

    var $context = $('#custom-filters');
    var $valueLinks = $('a[data-value]', $context);
    var $inputs = $('input[type=text]', $context);
    var $dateRangeOccurrences = $('#date-range-inputs', $context);

    var $dateChoice = $('input[name=search_date_range]', $context);

    $valueLinks
        .unbind('click')
        .on({
            click: function () {
                var $self = $(this);
                var $menu = $self.parents('div.sub-menu');
                var target = $self.parents('ul:first[data-target]').data('target');
                var $input = $('input[name=' + target + ']', $context);

                var value = $self.data('value');

                $input.val(value);
                $menu.hide();

                $menu.parents('li:first').find('a.has-sub > span.faded').html('(' + $self.html().trim() + ')');

                if (target == 'search_date_range') {
                    if (value == 'date_range') {
                        $dateRangeOccurrences.show();
                    } else {
                        $dateRangeOccurrences
                            .hide()
                            .find('input')
                            .val('');
                    }
                }

                if (!$self.data('prevent-trigger')) {
                    $self.parents('form').submit();
                }
            }
        });

    $inputs.on({
        keypress: function(event) {
            var which = event.which ? event.which : event.charCode;

            if (which == 13) {
                event.preventDefault();
                event.stopPropagation();

                $(this).parents('form').submit();
            }
        }
    });

    if ($dateChoice.val() == 'date_range') {
        $dateRangeOccurrences.show();
    }

    $('input', $context).each(function(){
        if ($(this).val()) {
            $('.filter-clear', $context).show();
        }
    });

}(window, jQuery));
