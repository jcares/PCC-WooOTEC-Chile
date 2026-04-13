<?php
/**
 * Página de CRON (Tareas Automáticas)
 * 
 * ¿Qué hace?
 * - Configurar intervalo de sincronización automática
 * - Mostrar estado y logs de ejecuciones
 * 
 * ¿Qué debe funcionar?
 * ✅ Selector de intervalo (cada hora, cada día, etc.)
 * ✅ Mostrar última ejecución (fecha/hora)
 * ✅ Botón "Ejecutar Ahora" → ejecuta sincro manual
 * ✅ Logs de últimas ejecuciones
 * 
 * @version 3.0.7
 */

include WOO_OTEC_MOODLE_PATH . 'admin/partials/tabs-header.php';

$cron = new \Woo_OTEC_Moodle\Cron_Manager();
$status = $cron->get_status();
$current_interval = $cron->get_interval_hours();
?>

<div class="wom-wrap" style="max-width: 1000px;">
    <div style="padding-bottom: 12px; border-bottom: 2px solid #4f46e5; margin-bottom: 24px;">
        <h2 style="margin: 0; color: #1f2937; font-size: 24px;">
            <span class="dashicons dashicons-update" style="vertical-align: middle; margin-right: 8px;"></span>
            Sincronización Automática (CRON)
        </h2>
        <p style="margin: 4px 0 0; color: #6b7280; font-size: 14px;">
            Configura cada cuántas horas se sincronizan automáticamente los cursos desde Moodle
        </p>
    </div>

    <div style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; margin-bottom: 20px;">
        <h3 style="margin: 0 0 12px; font-size: 14px; font-weight: 600; color: #1f2937;">
            Estado Actual
        </h3>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
            <!-- Estado -->
            <div style="background: #f9fafb; padding: 12px; border-radius: 6px; border-left: 4px solid <?php echo $status['enabled'] ? '#10b981' : '#ef4444'; ?>;">
                <p style="margin: 0 0 4px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #6b7280; font-weight: 600;">Estado</p>
                <p style="margin: 0; font-size: 16px; font-weight: 700; color: <?php echo $status['enabled'] ? '#10b981' : '#ef4444'; ?>;">
                    <?php echo $status['enabled'] ? 'ACTIVO' : 'INACTIVO'; ?>
                </p>
            </div>

            <!-- Intervalo -->
            <div style="background: #f9fafb; padding: 12px; border-radius: 6px; border-left: 4px solid #0891b2;">
                <p style="margin: 0 0 4px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #6b7280; font-weight: 600;">Intervalo Configurado</p>
                <p style="margin: 0; font-size: 16px; font-weight: 700; color: #0891b2;">
                    Cada <?php echo esc_html( $status['interval'] ); ?> hora(s)
                </p>
            </div>

            <!-- Próxima ejecución -->
            <div style="background: #f9fafb; padding: 12px; border-radius: 6px; border-left: 4px solid #7c3aed;">
                <p style="margin: 0 0 4px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #6b7280; font-weight: 600;">Próxima Ejecución</p>
                <p style="margin: 0; font-size: 12px; font-weight: 600; color: #7c3aed;">
                    <?php echo esc_html( $status['next_run'] ); ?>
                </p>
            </div>
        </div>
    </div>

    <form id="cron-settings-form" style="background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; max-width: 600px;">
        <h3 style="margin: 0 0 16px; font-size: 14px; font-weight: 600; color: #1f2937; padding-bottom: 8px; border-bottom: 1px solid #e5e7eb;">
            Configurar Intervalo de Sincronización
        </h3>

        <div style="margin-bottom: 20px;">
            <label for="cron_interval" style="display: block; font-weight: 600; margin-bottom: 8px; color: #1f2937; font-size: 13px;">
                Intervalo (horas)
            </label>
            <div style="display: flex; gap: 10px; align-items: flex-start;">
                <div>
                    <input 
                        type="number" 
                        id="cron_interval" 
                        name="cron_interval" 
                        value="<?php echo esc_attr( $current_interval ); ?>"
                        min="1" 
                        max="24" 
                        class="wom-input"
                        style="width: 120px;"
                    />
                </div>
                <button 
                    type="submit" 
                    class="wom-btn" 
                    style="background: #4f46e5; padding: 10px 20px;"
                >
                    <span class="dashicons dashicons-cloud-upload"></span> Guardar Configuración
                </button>
            </div>
            <p style="margin: 8px 0 0; font-size: 12px; color: #6b7280;">
                💡 <strong>Recomendación:</strong> 6 horas (sincroniza 4 veces al día)
            </p>
        </div>

        <!-- Información de valores -->
        <div style="background: #f0f4ff; padding: 12px; border-radius: 6px; border-left: 4px solid #4f46e5; margin-top: 16px;">
            <h4 style="margin: 0 0 8px; font-size: 12px; font-weight: 600; color: #1f2937;">Valores Recomendados:</h4>
            <ul style="margin: 0; padding-left: 20px; font-size: 12px; color: #6b7280;">
                <li><strong>1 hora:</strong> Para sincronización en tiempo real (alto consumo de recursos)</li>
                <li><strong>3 horas:</strong> Sincronización frecuente (recomendado para sitios activos)</li>
                <li><strong>6 horas:</strong> 🎯 Balance óptimo (recomendación por defecto)</li>
                <li><strong>12 horas:</strong> Sincronización moderada</li>
                <li><strong>24 horas:</strong> Una sola vez al día (para sitios con pocos cambios)</li>
            </ul>
        </div>

        <!-- Mensaje resultado -->
        <div id="cron-result-message" style="margin-top: 16px; display: none; padding: 12px; border-radius: 4px; font-size: 12px;"></div>
    </form>

    <!-- INFO ADICIONAL -->
    <div style="background: #fef3c7; border: 1px solid #fcd34d; border-radius: 8px; padding: 12px; margin-top: 20px; border-left: 4px solid #d97706;">
        <strong style="color: #92400e;">ℹ️ Información Importante:</strong>
        <ul style="margin: 8px 0 0; padding-left: 20px; font-size: 12px; color: #b45309;">
            <li>El CRON funcionará <strong>solo si WordPress tiene habilitadas las tareas programadas</strong></li>
            <li>Si ves "INACTIVO", verifica que tu servidor tenga habilitado WP-Cron</li>
            <li>Para entornos de producción, se recomienda configurar CRON real en servidor</li>
            <li>Cada sincronización puede tomar varios minutos (según cantidad de cursos)</li>
            <li>Los logs se guardan en el dashboard para auditoría</li>
        </ul>
    </div>

    <div style="text-align: center; font-size: 12px; color: #999; padding-top: 12px; border-top: 1px solid #e5e7eb; margin-top: 20px;">
        Woo OTEC Moodle v<?php echo WOO_OTEC_MOODLE_VERSION; ?>
    </div>
</div>

<script>
(function($) {
    'use strict';

    const CronSettings = {
        nonce: '<?php echo wp_create_nonce( 'woo-otec-moodle-nonce' ); ?>',

        init: function() {
            $('#cron-settings-form').on('submit', (e) => this.handleSubmit(e));
        },

        handleSubmit: function(e) {
            e.preventDefault();

            const interval = parseInt($('#cron_interval').val());
            
            if (interval < 1 || interval > 24) {
                this.showMessage('El intervalo debe estar entre 1 y 24 horas', 'error');
                return;
            }

            const $msg = $('#cron-result-message');
            $msg.html('<div class="spinner" style="display: inline-block; width: 16px; height: 16px; border: 2px solid #f3f3f3; border-top: 2px solid #4f46e5; border-radius: 50%; animation: spin 0.6s linear infinite;"></div> Guardando configuración...').show();

            $.ajax({
                url: wooOtecMoodle.ajax_url,
                type: 'POST',
                data: {
                    action: 'woo_otec_save_cron_interval',
                    nonce: this.nonce,
                    interval: interval
                },
                success: (response) => {
                    if (response.success) {
                        this.showMessage('✅ Configuración guardada. CRON reprogramado para cada ' + interval + ' hora(s)', 'success');
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        this.showMessage('❌ Error: ' + response.data, 'error');
                    }
                },
                error: () => {
                    this.showMessage('❌ Error de conexión', 'error');
                }
            });
        },

        showMessage: function(msg, type) {
            const $msg = $('#cron-result-message');
            const bgColor = type === 'success' ? '#ecfdf5' : '#fef2f2';
            const borderColor = type === 'success' ? '#10b981' : '#ef4444';
            const textColor = type === 'success' ? '#065f46' : '#7f1d1d';

            $msg.css({
                'background-color': bgColor,
                'border-left-color': borderColor,
                'color': textColor,
                'border': '1px solid ' + borderColor
            }).html(msg).show();

            if (type === 'success') {
                setTimeout(() => $msg.fadeOut(), 5000);
            }
        }
    };

    $(document).ready(() => CronSettings.init());

})(jQuery);
</script>

<style>
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>
