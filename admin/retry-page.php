<?php

if (!defined('ABSPATH')) {
    exit;
}

function pcc_retry_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    if (!class_exists('PCC_WooOTEC_Cron')) {
        echo '<div class="notice notice-error"><p>No se pudo cargar el módulo de cron/reintentos.</p></div>';
        return;
    }

    $counts = PCC_WooOTEC_Cron::get_queue_counts();
    $results = null;

    if (isset($_POST['pcc_retry_now'])) {
        check_admin_referer('pcc_retry_now');

        $results = PCC_WooOTEC_Cron::retry_failed_enrollments(true);
        if (!is_array($results)) {
            $results = array();
        }
    }

    $rows = PCC_WooOTEC_Cron::get_queue_rows(50, array('pending', 'failed', 'abandoned', 'enrolled'));

    ?>

    <div class="wrap pcc-admin">
        <h1>Reintentos de Matrícula</h1>

        <div class="pcc-card" style="margin-bottom: 16px;">
            <p><strong>En cola:</strong>
                Pendientes <?php echo esc_html((string) $counts['pending']); ?> |
                Fallidas <?php echo esc_html((string) $counts['failed']); ?> |
                Abandonadas <?php echo esc_html((string) $counts['abandoned']); ?> |
                OK <?php echo esc_html((string) $counts['enrolled']); ?>
            </p>

            <form method="post">
                <?php wp_nonce_field('pcc_retry_now'); ?>
                <p>
                    <input type="submit" name="pcc_retry_now" class="button button-primary" value="Ejecutar tareas pendientes ahora">
                    <span class="description">Procesa hasta el batch configurado por `pcc_retry_batch_size`.</span>
                </p>
            </form>
        </div>

        <?php if (is_array($results)) : ?>
            <h2>Resultado de la ejecución</h2>
            <?php if (empty($results)) : ?>
                <div class="notice notice-info"><p>No había tareas pendientes para procesar en este batch.</p></div>
            <?php else : ?>
                <div class="notice notice-success"><p>Batch ejecutado. Detalle:</p></div>
                <pre style="background:#fff;border:1px solid #ccd0d4;padding:12px;max-height:320px;overflow:auto;"><?php
                foreach ($results as $r) {
                    printf(
                        "#%d order=%d course=%d %s→%s | %s\n",
                        (int) $r['row_id'],
                        (int) $r['order_id'],
                        (int) $r['course_id'],
                        (string) $r['from'],
                        (string) $r['to'],
                        (string) $r['message']
                    );
                }
                ?></pre>
            <?php endif; ?>
        <?php endif; ?>

        <h2>Últimos registros</h2>

        <table class="widefat striped">
            <thead>
            <tr>
                <th>ID</th>
                <th>Orden</th>
                <th>Email</th>
                <th>Curso</th>
                <th>Intentos</th>
                <th>Status</th>
                <th>Último intento</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($rows)) : ?>
                <tr><td colspan="7">Sin registros todavía.</td></tr>
            <?php else : ?>
                <?php foreach ($rows as $row) : ?>
                    <tr>
                        <td><?php echo esc_html((string) (int) $row->id); ?></td>
                        <td><?php echo esc_html((string) (int) $row->order_id); ?></td>
                        <td><?php echo esc_html((string) $row->user_email); ?></td>
                        <td><?php echo esc_html((string) (int) $row->course_id); ?></td>
                        <td><?php echo esc_html((string) (int) $row->attempts); ?></td>
                        <td><?php echo esc_html((string) $row->status); ?></td>
                        <td><?php echo esc_html((string) $row->last_try); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php
}

