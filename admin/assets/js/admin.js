(function ($) {
    'use strict';

    let frame;

    $(document).on('click', '.pcc-media-picker', function (event) {
        event.preventDefault();

        const targetSelector = $(this).data('target');
        const previewSelector = $(this).data('preview');

        if (!targetSelector || !previewSelector) {
            return;
        }

        if (frame) {
            frame.off('select');
        }

        frame = wp.media({
            title: 'Selecciona una imagen por defecto',
            button: {
                text: 'Usar esta imagen'
            },
            multiple: false
        });

        frame.on('select', function () {
            const attachment = frame.state().get('selection').first().toJSON();
            $(targetSelector).val(attachment.id);
            $(previewSelector).attr('src', attachment.url).removeClass('is-hidden');
        });

        frame.open();
    });
}(jQuery));
