/* global jQuery, window, document, l */
(function($) {
	window.iworks_omnibus_migration_v4 = function() {
		$.ajax({
			url: window.ajaxurl,
			data: {
				action: 'iworks_omnibus_migrate_v4',
				items: $('#omnibus-migration-v4-items').val(),
				older: $('#omnibus-migration-v4-form-older').is(':checked'),
				_wpnonce: $('#omnibus-migration-v4-nonce').val(),
			},
			type: "post",
			success: function(response) {
				if (response.success) {
					if ('continue' === response.data.action) {
						var init = parseInt($('#omnibus-migration-v4-init').val());
						var current = parseInt(response.data.count);
						$('#omnibus-migration-v4-items').val('current');
						$('#omnibus-migration-v4-p-counter').html(current);
						$('#omnibus-migration-v4-p-progress').val(((init - current) * 100) / init);
						window.iworks_omnibus_migration_v4();
					} else if ('done' === response.data.action) {
						$('#omnibus-migration-v4-p-counter').parent().detach();
						$('#omnibus-migration-v4-p-progress').detach();
						if (response.data.message) {
							$(
								'<div class="notice notice-success"><p>' + response.data.message + '</p></div>'
							).insertBefore($('#omnibus-migration-v4-form-button'));
							$('#omnibus-migration-v4-form-button').detach();
						}
						$('#omnibus-migration-v4-form').parent().addClass('done');
					}
				} else {
					if (response.data.message) {
						$(
							'<div class="notice notice-error"><p>' + response.data.message + '</p></div>'
						).insertBefore($('#omnibus-migration-v4-form-button'));
					}
				}
			}
		});
	};
	$(document).ready(function() {
		$('#omnibus-migration-v4-form-button').on('click', function(e) {
			e.preventDefault();
			$('.notice', $('#wpbody')).detach();
			window.iworks_omnibus_migration_v4();
			return false;
		});
		$('#omnibus-migration-v4-form-backup').on( 'change', function(e) {
			e.preventDefault();
			if ( $(this).is(':checked')) {
				$('#omnibus-migration-v4-form-button').removeAttr('disabled');
			} else {
				$('#omnibus-migration-v4-form-button').attr('disabled', 'disabled' );
			}
		});
	});
})(jQuery);
