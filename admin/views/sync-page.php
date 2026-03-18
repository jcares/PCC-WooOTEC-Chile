<?php

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap pcc-admin-wrap">
    <h1>Sincronizacion Moodle -> WooCommerce</h1>

    <div class="pcc-card">
        <h2>Estado</h2>
        <p><strong>Ultima ejecucion:</strong> <?php echo !empty($last_sync['timestamp']) ? esc_html((string) $last_sync['timestamp']) : 'Sin ejecuciones'; ?></p>
        <p><strong>Mensaje:</strong> <?php echo !empty($last_sync['message']) ? esc_html((string) $last_sync['message']) : 'Sin datos'; ?></p>
        <p><strong>Categorias:</strong> creadas <?php echo esc_html((string) ($last_sync['categories_created'] ?? 0)); ?> / actualizadas <?php echo esc_html((string) ($last_sync['categories_updated'] ?? 0)); ?></p>
        <p><strong>Productos:</strong> creados <?php echo esc_html((string) ($last_sync['products_created'] ?? 0)); ?> / actualizados <?php echo esc_html((string) ($last_sync['products_updated'] ?? 0)); ?></p>
    </div>

    <div class="pcc-card">
        <h2>Ejecutar ahora</h2>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="pcc_woootec_run_sync">
            <?php wp_nonce_field('pcc_woootec_run_sync'); ?>
            <?php submit_button('Sincronizar ahora', 'primary', 'submit', false); ?>
        </form>
        <p>El cron automatico corre cada 1 hora.</p>
    </div>
</div>
