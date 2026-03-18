<?php

if (!defined('ABSPATH')) {
    exit;
}

function pcc_sync_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    if (isset($_POST['pcc_sync_now'])) {
        check_admin_referer('pcc_sync_courses');
        pcc_run_course_sync();
    }

    $last_sync = pcc_get_last_sync_state();
    ?>
    <div class="wrap pcc-admin">
        <h1>Sincronizacion Moodle</h1>
        <p>La sincronizacion corre en dos etapas: categorias y luego productos/cursos.</p>

        <div class="pcc-card" style="margin-bottom:16px;">
            <h2>Ultima sincronizacion</h2>
            <?php if (empty($last_sync)) : ?>
                <p>Aun no se ha ejecutado ninguna sincronizacion.</p>
            <?php else : ?>
                <p><strong>Fecha:</strong> <?php echo esc_html((string) $last_sync['timestamp']); ?></p>
                <p><strong>Estado:</strong> <?php echo esc_html((string) $last_sync['status']); ?></p>
                <p><strong>Categorias:</strong> creadas <?php echo esc_html((string) $last_sync['categories_created']); ?> / actualizadas <?php echo esc_html((string) $last_sync['categories_updated']); ?></p>
                <p><strong>Productos:</strong> creados <?php echo esc_html((string) $last_sync['products_created']); ?> / actualizados <?php echo esc_html((string) $last_sync['products_updated']); ?></p>
                <p><strong>Mensaje:</strong> <?php echo esc_html((string) $last_sync['message']); ?></p>
            <?php endif; ?>
        </div>

        <form method="post">
            <?php wp_nonce_field('pcc_sync_courses'); ?>
            <p><input type="submit" name="pcc_sync_now" class="button button-primary" value="Sincronizar ahora"></p>
        </form>
    </div>
    <?php
}
