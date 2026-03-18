<?php

if (!defined('ABSPATH')) {
    exit;
}

function pcc_dashboard() {
    $configured = pcc_get_moodle_url() !== '' && pcc_get_moodle_token() !== '';
    $last_sync = pcc_get_last_sync_state();

    $synced_products = new WP_Query(array(
        'post_type'      => 'product',
        'post_status'    => array('publish', 'draft', 'private'),
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'meta_query'     => array(
            array(
                'key'     => '_pcc_synced',
                'value'   => 1,
                'compare' => '=',
            ),
        ),
    ));

    ?>
    <div class="wrap pcc-admin">
        <h1>PCC WooOTEC Chile</h1>

        <div class="pcc-card" style="margin-bottom:16px;">
            <h2>Estado general</h2>
            <p><strong>Moodle configurado:</strong> <?php echo $configured ? 'Si' : 'No'; ?></p>
            <p><strong>SSO:</strong> <?php echo pcc_is_sso_enabled() ? 'Activo' : 'Desactivado'; ?></p>
            <p><strong>Productos sincronizados:</strong> <?php echo esc_html((string) $synced_products->found_posts); ?></p>
            <p><strong>Ultima sincronizacion:</strong> <?php echo !empty($last_sync['timestamp']) ? esc_html((string) $last_sync['timestamp']) : 'Sin ejecuciones'; ?></p>
        </div>

        <div class="pcc-grid">
            <div class="pcc-card">
                <h3>Sincronizacion</h3>
                <p>Categorias primero, cursos despues.</p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=pcc-sync')); ?>" class="button button-primary">Ir a sincronizacion</a>
            </div>
            <div class="pcc-card">
                <h3>Configuracion</h3>
                <p>Moodle, defaults, SSO y updater GitHub.</p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=pcc-settings')); ?>" class="button">Configurar</a>
            </div>
            <div class="pcc-card">
                <h3>Reintentos</h3>
                <p>Monitorea fallos de matricula y reintenta.</p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=pcc-retries')); ?>" class="button">Ver cola</a>
            </div>
        </div>
    </div>
    <?php
}
