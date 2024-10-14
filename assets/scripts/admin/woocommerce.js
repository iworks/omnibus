/*! Omnibus — show the lowest price - v4.0.3
 * https://github.com/iworks/omnibus/
 * Copyright (c) 2024; * Licensed GPL-3.0 */
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
		$('input#_iwo_price_lowest_allow_to_delete')
			.on('change', function() {
				if ($(this).is(':checked')) {
					$(this)
						.closest('tbody')
						.find('.iworks_omnibus_delete_older_field')
						.closest('tr')
						.show();
				} else {
					$(this)
						.closest('tbody')
						.find('.iworks_omnibus_delete_older_field')
						.closest('tr')
						.hide();
				}
			})
			.trigger('change');
	});
})(jQuery);
