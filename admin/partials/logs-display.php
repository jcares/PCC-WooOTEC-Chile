<?php
/**
 * Vista de Bitácora Operativa (Real-time Logs).
 */
include WOO_OTEC_MOODLE_PATH . 'admin/partials/tabs-header.php';
?>

<div class="wom-section">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <h2><span class="dashicons dashicons-media-text"></span> Bitácora de Operaciones Reales</h2>
        <span class="wom-badge" style="background:#e5e7eb; color:#374151;">Últimos 100 eventos</span>
    </div>

    <p style="margin-bottom:20px; color:#6b7280;">Aquí se registran todas las comunicaciones con la API de Moodle y los resultados de las matrículas automáticas.</p>

    <div class="wom-log-container" style="background:#1e293b; color:#f8fafc; padding:20px; border-radius:10px; font-family: 'Courier New', Courier, monospace; font-size:13px; line-height:1.6; max-height:600px; overflow-y:auto; border:1px solid #334155;">
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
                <div style="color: <?php echo $color; ?>; margin-bottom:4px; border-bottom:1px solid #334155; padding-bottom:4px;">
                    <?php echo esc_html( $line ); ?>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <div style="color:#94a3b8; text-align:center; padding:40px;">
                No hay registros operativos aún.
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="wom-footer">
    Woo OTEC Moodle v<?php echo WOO_OTEC_MOODLE_VERSION; ?>
</div>

</div> <!-- Close wom-wrap -->
