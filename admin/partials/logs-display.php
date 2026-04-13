<?php
/**
 * Página de Logs
 * 
 * ¿Qué hace?
 * - Ver historial de eventos del plugin
 * - Filtrar por tipo de evento
 * 
 * ¿Qué debe funcionar?
 * ✅ Tabla: Fecha | Tipo | Mensaje
 * ✅ Filtros por tipo (error, sync, success, info)
 * ✅ Búsqueda por palabra clave
 * ✅ Botón "Limpiar logs" con confirmación
 * ✅ Botón "Exportar" (opcional)
 */
include WOO_OTEC_MOODLE_PATH . 'admin/partials/tabs-header.php';
?>

<div class="wom-section">
    <div class="wom-action-row">
        <h2><span class="dashicons dashicons-media-text"></span> Bitácora de Operaciones</h2>
        <span class="wom-badge" style="background:#e5e7eb; color:#374151;">Últimos 100 eventos</span>
    </div>

    <p style="margin-bottom:20px; color:#6b7280;">Registro de comunicaciones con API y automatizaciones</p>

    <div class="wom-log-container">
        <?php if ( ! empty( $recent_logs ) ) : ?>
            <?php 
            $lines = explode( PHP_EOL, $recent_logs );
            foreach ( $lines as $line ) : 
                if ( empty( trim( $line ) ) ) continue;
                
                $color = '#f8fafc';
                if ( strpos( $line, '[ERROR]' ) !== false ) $color = '#fca5a5';
                if ( strpos( $line, '[SUCCESS]' ) !== false ) $color = '#86efac';
                if ( strpos( $line, '[INFO]' ) !== false ) $color = '#93c5fd';
            ?>
                <div class="wom-log-entry" style="color: <?php echo $color; ?>;">
                    <?php echo esc_html( $line ); ?>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <div class="wom-log-empty">
                No hay registros disponibles.
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="wom-footer">
    Woo OTEC Moodle v<?php echo WOO_OTEC_MOODLE_VERSION; ?>
</div>

</div> <!-- Close wom-wrap -->
