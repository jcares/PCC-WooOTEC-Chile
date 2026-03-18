(function ($) {
    'use strict';

    let frame;
    const syncApp = $('[data-sync-app]');
    const emailFeedback = $('[data-email-feedback]');
    const tabs = $('.pcc-tab');

    function setActiveTab(tab) {
        const target = tab.data('tab');
        if (!target) {
            return;
        }

        tabs.removeClass('is-active').attr('aria-selected', 'false');
        $('.pcc-tab-panel').removeClass('is-active').attr('hidden', true);

        tab.addClass('is-active').attr('aria-selected', 'true');
        $('.pcc-tab-panel[data-panel="' + target + '"]').addClass('is-active').attr('hidden', false);
    }

    $(document).on('click', '.pcc-tab', function () {
        setActiveTab($(this));
    });

    if (tabs.length) {
        const currentTab = tabs.filter('.is-active').first();
        setActiveTab(currentTab.length ? currentTab : tabs.first());
    }

    function setEmailFeedback(message, state) {
        if (!emailFeedback.length) {
            return;
        }
        emailFeedback.removeClass('is-error is-success').addClass(state ? 'is-' + state : '').html(message || '');
    }

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

    function setSyncProgress(percent) {
        const value = Math.max(0, Math.min(100, percent));
        $('[data-sync-progress]').css('width', value + '%').text(value + '%');
    }

    function setStageState(stage, state, text) {
        const card = $('[data-stage-card="' + stage + '"]');
        const status = $('[data-stage-status="' + stage + '"]');

        card.removeClass('is-pending is-running is-success is-error').addClass('is-' + state);
        status.text(text);
    }

    function renderSyncResult(payload) {
        const result = $('[data-sync-result]');
        const lines = [];

        if (payload.categories_created !== undefined) {
            lines.push('<p><strong>Categorias:</strong> creadas ' + (payload.categories_created || 0) + ' / actualizadas ' + (payload.categories_updated || 0) + '</p>');
        }

        if (payload.products_created !== undefined) {
            lines.push('<p><strong>Productos:</strong> creados ' + (payload.products_created || 0) + ' / actualizados ' + (payload.products_updated || 0) + '</p>');
        }

        lines.unshift('<p><strong>Mensaje:</strong> ' + (payload.message || '') + '</p>');
        result.html(lines.join(''));
    }

    function runStage(stage, onSuccess) {
        return $.post(pccWoootecAdmin.ajaxUrl, {
            action: 'pcc_woootec_sync_stage',
            nonce: pccWoootecAdmin.nonce,
            stage: stage
        }).done(function (response) {
            if (!response || !response.success) {
                throw new Error(response && response.data && response.data.message ? response.data.message : 'Error de sincronizacion');
            }

            onSuccess(response.data);
        });
    }

    $(document).on('click', '[data-sync-start]', function () {
        if (!syncApp.length) {
            return;
        }

        const button = $(this);
        button.prop('disabled', true).text('Sincronizando...');
        setSyncProgress(10);
        setStageState('categories', 'running', 'Procesando...');
        setStageState('courses', 'pending', 'Pendiente');

        runStage('categories', function (payload) {
            setStageState('categories', 'success', 'Completada');
            renderSyncResult(payload);
            setSyncProgress(50);
            setStageState('courses', 'running', 'Procesando...');

            runStage('courses', function (coursePayload) {
                setStageState('courses', 'success', 'Completada');
                renderSyncResult(coursePayload);
                setSyncProgress(100);
                button.prop('disabled', false).text('Iniciar sincronizacion');
            }).fail(function (xhr) {
                const message = xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message ? xhr.responseJSON.data.message : 'Error en etapa de cursos';
                setStageState('courses', 'error', message);
                renderSyncResult({ message: message });
                button.prop('disabled', false).text('Reintentar sincronizacion');
            });
        }).fail(function (xhr) {
            const message = xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message ? xhr.responseJSON.data.message : 'Error en etapa de categorias';
            setStageState('categories', 'error', message);
            renderSyncResult({ message: message });
            button.prop('disabled', false).text('Reintentar sincronizacion');
        });
    });

    $(document).on('click', '[data-email-preview]', function () {
        setEmailFeedback('Generando vista previa...', '');
        $.post(pccWoootecAdmin.ajaxUrl, {
            action: 'pcc_woootec_email_preview',
            nonce: pccWoootecAdmin.emailNonce
        }).done(function (response) {
            if (!response || !response.success) {
                setEmailFeedback('No se pudo generar la vista previa.', 'error');
                return;
            }

            $('[data-email-preview-box]').html(response.data.html || '');
            setEmailFeedback('Vista previa generada.', 'success');
        }).fail(function (xhr) {
            const message = xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message ? xhr.responseJSON.data.message : 'Error al generar vista previa.';
            setEmailFeedback(message, 'error');
        });
    });

    $(document).on('click', '[data-email-send-test]', function () {
        const recipient = $('#pcc_woootec_pro_email_test_recipient').val();
        setEmailFeedback('Enviando correo de prueba...', '');
        $.post(pccWoootecAdmin.ajaxUrl, {
            action: 'pcc_woootec_send_test_email',
            nonce: pccWoootecAdmin.emailNonce,
            recipient: recipient
        }).done(function (response) {
            if (!response || !response.success) {
                setEmailFeedback('No se pudo enviar el correo de prueba.', 'error');
                return;
            }

            setEmailFeedback(response.data.message || 'Correo enviado.', 'success');
        }).fail(function (xhr) {
            const message = xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message ? xhr.responseJSON.data.message : 'Error al enviar correo de prueba.';
            setEmailFeedback(message, 'error');
        });
    });
}(jQuery));
