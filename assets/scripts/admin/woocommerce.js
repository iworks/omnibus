/*! Omnibus — show the lowest price - v2.3.4
 * http://iworks.pl/
 * Copyright (c) 2023; * Licensed GPL-3.0 */
(function($) {
    $(function() {
        $('input#_iwo_price_lowest_message_settings')
            .on('change', function() {
                if ($(this).is(':checked')) {
                    $(this)
                        .closest('tbody')
                        .find('.iworks_omnibus_messages_settings_field')
                        .closest('tr')
                        .show();
                } else {
                    $(this)
                        .closest('tbody')
                        .find('.iworks_omnibus_messages_settings_field')
                        .closest('tr')
                        .hide();
                }
            })
            .trigger('change');
    });
})(jQuery);
