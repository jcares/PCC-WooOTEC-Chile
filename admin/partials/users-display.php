<?php
/**
 * Página de Usuarios
 * 
 * ¿Qué hace?
 * - Sincronizar usuarios desde Moodle a WordPress
 * - Gestionar usuarios creados
 * 
 * ¿Qué debe funcionar?
 * ✅ Botón "Sincronizar Usuarios" (AJAX)
 * ✅ Tabla: Email | Nombre | Rol en Moodle | Estado
 * ✅ Mostrar usuarios sincronizados
 * ✅ Copiar contraseña temporal
 */
include WOO_OTEC_MOODLE_PATH . 'admin/partials/tabs-header.php';
?>

<div class="wom-section">
    <h2><span class="dashicons dashicons-admin-users"></span> Últimos Matriculados</h2>
    <p>Historial de matriculaciones automáticas.</p>
    
    <table class="wp-list-table widefat fixed striped wom-table" style="margin-top:20px;">
        <thead>
            <tr>
                <th style="width:100px;">Pedido</th>
                <th>Usuario</th>
                <th>Cursos</th>
                <th style="width:120px;">Estado API</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="4" class="wom-empty">No hay registros de matriculación aún.</td>
            </tr>
        </tbody>
    </table>
</div>

<div class="wom-footer">
    Woo OTEC Moodle v<?php echo WOO_OTEC_MOODLE_VERSION; ?>
</div>

</div> <!-- Close wom-wrap -->
