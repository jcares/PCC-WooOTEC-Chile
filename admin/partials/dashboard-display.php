<?php
/**
 * Vista de Dashboard - PÁGINA PRINCIPAL
 * 
 * ¿Qué hace?
 * - Muestra estado general del plugin
 * - Estadísticas de sincronización
 * - Resumen de actividad reciente
 * 
 * ¿Qué debe funcionar?
 * ✅ Cargar sin errores
 * ✅ Mostrar estadísticas (cursos, matrículas, estado API)
 * ✅ Botón "Probar Conexión" (AJAX)
 * ✅ Botones de acción rápida (links a otras páginas)
 */
include WOO_OTEC_MOODLE_PATH . 'admin/partials/tabs-header.php';

// Obtener estadísticas reales
$api_status = get_option( 'woo_otec_moodle_api_url' ) ? 'Conectado' : 'No configurada';
$courses_count = count( get_posts( array( 'post_type' => 'product', 'meta_key' => '_moodle_course_id', 'numberposts' => -1 ) ) );
$total_enrollments = count( get_posts( array( 'post_type' => 'shop_order', 'post_status' => 'wc-completed', 'numberposts' => -1 ) ) );

// Leer logs recientes para la actividad
$logger = new \Woo_OTEC_Moodle\Logger();
$recent_activity = $logger->get_recent_logs( 5 );
?>

<div class="wom-cards-grid">
    <div class="wom-card">
        <div class="wom-card-icon wom-icon-primary">
            <span class="dashicons dashicons-admin-links"></span>
        </div>
        <div class="wom-card-content">
            <h3>Estado API</h3>
            <p class="wom-stat"><?php echo esc_html( $api_status ); ?></p>
            <button type="button" id="wom-test-connection" class="wom-btn wom-btn--secondary wom-btn-icon-small">
                <span class="dashicons dashicons-update-alt"></span> Probar
            </button>
            <div id="wom-test-result" class="wom-result-message" style="display: none;"></div>
        </div>
    </div>

    <div class="wom-card">
        <div class="wom-card-icon wom-icon-success">
            <span class="dashicons dashicons-welcome-learn-more"></span>
        </div>
        <div class="wom-card-content">
            <h3>Cursos Vinculados</h3>
            <p class="wom-stat"><?php echo esc_html( $courses_count ); ?></p>
            <a href="?page=woo-otec-moodle-courses" class="wom-btn wom-btn-action">
                <span class="dashicons dashicons-external"></span> Ver Cursos
            </a>
        </div>
    </div>

    <div class="wom-card">
        <div class="wom-card-icon" style="background:rgba(245, 158, 11, 0.1); color:#f59e0b;">
            <span class="dashicons dashicons-groups"></span>
        </div>
        <div class="wom-card-content">
            <h3>Matrículas Totales</h3>
            <p class="wom-stat"><?php echo esc_html( $total_enrollments ); ?></p>
            <span style="font-size:12px; color:#6b7280;">Actividad últimos 30 días</span>
        </div>
    </div>
</div>

<div class="wom-section" style="margin-top:0;">
    <h2><span class="dashicons dashicons-list-view"></span> Actividad Reciente del Sistema</h2>
    <p style="margin-bottom:15px; color:#6b7280;">Últimos eventos operativos registrados en tiempo real.</p>
    
    <div class="wom-activity-list" style="background:#fff; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden;">
        <?php if ( ! empty( $recent_activity ) ) : ?>
            <?php 
            $lines = explode( PHP_EOL, $recent_activity );
            foreach ( $lines as $line ) : 
                if ( empty( trim( $line ) ) ) continue;
                
                $type = 'INFO';
                if ( strpos( $line, '[ERROR]' ) !== false ) $type = 'ERROR';
                if ( strpos( $line, '[SUCCESS]' ) !== false ) $type = 'SUCCESS';
                
                $color = '#4f46e5';
                if ( $type === 'ERROR' ) $color = '#ef4444';
                if ( $type === 'SUCCESS' ) $color = '#10b981';
            ?>
                <div style="padding:12px 20px; border-bottom:1px solid #f3f4f6; display:flex; align-items:center; gap:15px; font-size:13px;">
                    <span style="width:8px; height:8px; border-radius:50%; background:<?php echo $color; ?>;"></span>
                    <span style="white-space:nowrap; color:#9ca3af; font-family:monospace;"><?php echo esc_html( substr($line, 1, 19) ); ?></span>
                    <span style="color:#374151;"><?php echo esc_html( substr($line, 23) ); ?></span>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <div style="padding:40px; text-align:center; color:#9ca3af;">
                No hay actividad reciente registrada.
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="wom-footer">
    Woo OTEC Moodle v<?php echo WOO_OTEC_MOODLE_VERSION; ?>
</div>

</div> <!-- Close wom-wrap -->
