/* global jQuery, window, document, l */
(function($) {
    window.iworks_omnibus_migration_v3 = function() {
        $.ajax({
            url: window.ajaxurl,
            data: {
                action: 'iworks_omnibus_migrate_v3',
                _wpnonce: $('#omnibus-migration-v3-nonce').val(),
            },
            type: "post",
            success: function(response) {
                if (response.success) {
                    if ('continue' === response.data.action) {
                        var init = parseInt($('#omnibus-migration-v3-init').val());
                        var current = parseInt(response.data.count);
                        $('#omnibus-migration-v3-p-counter').html(current);
                        $('#omnibus-migration-v3-p-progress').val(((init - current) * 100) / init);
                        window.iworks_omnibus_migration_v3();
                    } else if ('done' === response.data.action) {
                        $('#omnibus-migration-v3-p-counter').parent().detach();
                        $('#omnibus-migration-v3-p-progress').detach();
                        if (response.data.message) {
                            $(
                                '<div class="notice notice-success"><p>' + response.data.message + '</p></div>'
                            ).insertBefore($('#omnibus-migration-v3-form-button'));
                            $('#omnibus-migration-v3-form-button').detach();
                        }
                    }
                } else {
                    if (response.data.message) {
                        $(
                            '<div class="notice notice-error"><p>' + response.data.message + '</p></div>'
                        ).insertBefore($('#omnibus-migration-v3-form-button'));
                    }
                }
            }
        });
    };
    $(document).ready(function() {
        $('#omnibus-migration-v3-form-button').on('click', function(e) {
            e.preventDefault();
            window.iworks_omnibus_migration_v3();
            return false;
        });
    });
})(jQuery);