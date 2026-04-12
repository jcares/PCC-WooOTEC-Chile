/**
 * Admin Application Logic for Woo OTEC Moodle
 * 
 * Usando delegación de eventos para mayor confiabilidad en todas las pestañas.
 */
(function($) {
    'use strict';

    const WomAdmin = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            const self = this;

            // Delegación de eventos para botones de acción
            $(document).on('click', '#wom-test-connection', this.handleTestConnection.bind(this));
            $(document).on('click', '#wom-sync-now', this.handleSyncCourses.bind(this));
            $(document).on('click', '#test-email-btn', this.handleTestEmail.bind(this));

            // Toggle password visibility
            $(document).on('click', '.wom-toggle-password', this.handleTogglePassword.bind(this));

            // Copy to clipboard
            $(document).on('click', '.wom-copy-btn', this.handleCopyToClipboard.bind(this));

            // Cerrar notificaciones al hacer clic
            $(document).on('click', '.wom-notification', function() {
                $(this).fadeOut(() => $(this).remove());
            });
        },

        handleTestConnection: function(e) {
            e.preventDefault();
            const self = this;
            const $btn = $(e.currentTarget);
            const $result = $('#wom-test-result');
            
            $btn.prop('disabled', true).addClass('loading');
            $result.hide().removeClass('success error').html('');
            this.notify('info', 'Probando conexión...');

            $.ajax({
                url: wooOtecMoodle.ajax_url,
                type: 'POST',
                data: {
                    action: 'woo_otec_test_connection',
                    nonce: wooOtecMoodle.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $result.addClass('success').css({background: '#d1fae5', color: '#065f46', border: '1px solid #6ee7b7'}).html('✓ ' + response.data).fadeIn();
                        self.notify('success', response.data);
                    } else {
                        $result.addClass('error').css({background: '#fee2e2', color: '#991b1b', border: '1px solid #fca5a5'}).html('✗ ' + response.data).fadeIn();
                        self.notify('error', response.data);
                    }
                },
                error: function() {
                    $result.addClass('error').css({background: '#fee2e2', color: '#991b1b', border: '1px solid #fca5a5'}).html('✗ Error en la conexión').fadeIn();
                    self.notify('error', 'Error en la comunicación con el servidor.');
                },
                complete: function() {
                    $btn.prop('disabled', false).removeClass('loading');
                }
            });
        },

        handleTestEmail: function(e) {
            e.preventDefault();
            const self = this;
            const $btn = $(e.currentTarget);
            const $result = $('#test-email-result');

            $btn.prop('disabled', true);
            $result.hide().removeClass('wom-notice-success wom-notice-error');
            this.notify('info', 'Enviando correo de prueba...');

            $.ajax({
                url: wooOtecMoodle.ajax_url,
                type: 'POST',
                data: {
                    action: 'woo_otec_test_email',
                    nonce: wooOtecMoodle.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $result.addClass('wom-notice-success').html('<strong>✓ Éxito:</strong> ' + response.data).fadeIn();
                        self.notify('success', 'Email de prueba enviado correctamente');
                    } else {
                        $result.addClass('wom-notice-error').html('<strong>✗ Error:</strong> ' + response.data).fadeIn();
                        self.notify('error', response.data);
                    }
                },
                error: function(xhr, status, error) {
                    $result.addClass('wom-notice-error').html('<strong>✗ Error:</strong> Error en la comunicación con el servidor').fadeIn();
                    self.notify('error', 'Error fatal al enviar email: ' + error);
                },
                complete: function() {
                    $btn.prop('disabled', false);
                }
            });
        },

        handleSyncCourses: function(e) {
            e.preventDefault();
            const self = this;
            const $btn = $(e.currentTarget);
            const $progress = $('#wom-progress-container');
            const $fill = $('#wom-progress-fill');
            const $status = $('#wom-progress-status');

            $btn.prop('disabled', true);
            if ($progress.length) $progress.fadeIn();
            
            this.updateProgress($fill, $status, 20, 'Consultando categorías de Moodle...');

            $.ajax({
                url: wooOtecMoodle.ajax_url,
                type: 'POST',
                data: {
                    action: 'woo_otec_sync_courses',
                    nonce: wooOtecMoodle.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.updateProgress($fill, $status, 100, '¡Sincronización completada!');
                        self.notify('success', response.data);
                        
                        // Recargar página después de un éxito para ver los cursos
                        setTimeout(() => {
                            location.reload();
                        }, 2000);
                    } else {
                        self.updateProgress($fill, $status, 0, 'Fallo en la sincronización');
                        self.notify('error', response.data);
                    }
                },
                error: function(xhr, status, error) {
                    self.updateProgress($fill, $status, 0, 'Error crítico');
                    self.notify('error', 'Error fatal al sincronizar: ' + error);
                },
                complete: function() {
                    setTimeout(() => {
                        $btn.prop('disabled', false);
                    }, 5000);
                }
            });
        },

        updateProgress: function($fill, $status, percent, text) {
            if ($fill && $fill.length) $fill.css('width', percent + '%');
            if ($status && $status.length && text) $status.text(text);
        },

        notify: function(type, message) {
            const icon = type === 'success' ? 'yes' : (type === 'error' ? 'no-alt' : 'info');
            const $notifArea = $('#wom-notifications');
            
            if ($notifArea.length === 0) {
                return;
            }
            
            const $notif = $(`
                <div class="wom-notification ${type}" style="cursor:pointer;">
                    <span class="dashicons dashicons-${icon}"></span>
                    <span>${message}</span>
                </div>
            `);
            
            $notifArea.empty().append($notif);
            
            // Auto fade out
            if (type !== 'info') {
                setTimeout(() => {
                    $notif.fadeOut(() => $notif.remove());
                }, 8000);
            }
        },

        handleTogglePassword: function(e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            const targetId = $btn.data('target');
            const $input = $(targetId);
            const $icon = $btn.find('.dashicons');

            if ($input.attr('type') === 'password') {
                $input.attr('type', 'text');
                $icon.removeClass('dashicons-visibility').addClass('dashicons-visibility-alt');
                $btn.attr('title', 'Ocultar');
            } else {
                $input.attr('type', 'password');
                $icon.removeClass('dashicons-visibility-alt').addClass('dashicons-visibility');
                $btn.attr('title', 'Mostrar');
            }

            $btn.toggleClass('active');
        },

        handleCopyToClipboard: function(e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            const targetId = $btn.data('target');
            const $input = $(targetId);
            const value = $input.val();

            if (value) {
                navigator.clipboard.writeText(value).then(() => {
                    this.notify('success', 'Copiado al portapapeles');
                    $btn.find('.dashicons').removeClass('dashicons-admin-page').addClass('dashicons-yes');
                    setTimeout(() => {
                        $btn.find('.dashicons').removeClass('dashicons-yes').addClass('dashicons-admin-page');
                    }, 2000);
                }).catch(() => {
                    this.notify('error', 'Error al copiar');
                });
            } else {
                this.notify('error', 'Campo vacío');
            }
        }
    };

    $(document).ready(() => WomAdmin.init());

})(jQuery);
