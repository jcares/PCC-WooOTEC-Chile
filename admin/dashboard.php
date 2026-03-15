<?php

if (!defined('ABSPATH')) {
    exit;
}

function pcc_dashboard() {
    $moodle_url = (string) get_option('pcc_moodle_url');
    $token = (string) get_option('pcc_moodle_token');
    $configured = $moodle_url !== '' && $token !== '';

    $connection_ok = null;
    if ($configured && function_exists('pcc_moodle_test_connection')) {
        $connection_ok = pcc_moodle_test_connection();
    }

    $synced_products = 0;
    $synced_query = new WP_Query(
        array(
            'post_type'      => 'product',
            'post_status'    => array('publish', 'draft', 'private'),
            'posts_per_page' => 1,
            'meta_query'     => array(
                array(
                    'key'     => '_pcc_synced',
                    'value'   => 1,
                    'compare' => '=',
                ),
            ),
            'fields'         => 'ids',
        )
    );
    $synced_products = (int) $synced_query->found_posts;

    $enrolled_orders = 0;
    $orders_query = new WP_Query(
        array(
            'post_type'      => 'shop_order',
            'post_status'    => array('wc-completed'),
            'posts_per_page' => 1,
            'meta_query'     => array(
                array(
                    'key'     => '_pcc_moodle_enrollment_complete',
                    'value'   => 1,
                    'compare' => '=',
                ),
            ),
            'fields'         => 'ids',
        )
    );
    $enrolled_orders = (int) $orders_query->found_posts;

    ?>

    <div class="wrap pcc-admin">
        <h1>PCC WooOTEC Chile</h1>

        <div class="pcc-card" style="margin-bottom: 16px;">
            <h3>Estado</h3>
            <p><strong>Moodle configurado:</strong> <?php echo $configured ? 'Sí' : 'No'; ?></p>
            <p><strong>Conexión Moodle:</strong>
                <?php
                if ($connection_ok === true) {
                    echo '<span style="color:#1d7f1d;">OK</span>';
                } elseif ($connection_ok === false) {
                    echo '<span style="color:#b32d2e;">Fallida</span>';
                } else {
                    echo '—';
                }
                ?>
            </p>
            <p><strong>Cursos sincronizados (productos):</strong> <?php echo esc_html((string) $synced_products); ?></p>
            <p><strong>Órdenes con matrícula OK:</strong> <?php echo esc_html((string) $enrolled_orders); ?></p>
        </div>

        <div class="pcc-grid">
            <div class="pcc-card">
                <img alt="" src="<?php echo esc_url(PCC_WOOOTEC_URL . 'assets/img/course-icon.svg'); ?>">
                <h3>Cursos Moodle</h3>
                <p>Sincroniza los cursos desde Moodle hacia WooCommerce.</p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=pcc-sync')); ?>" class="button button-primary">Sincronizar</a>
            </div>

            <div class="pcc-card">
                <img alt="" src="<?php echo esc_url(PCC_WOOOTEC_URL . 'assets/img/moodle-icon.svg'); ?>">
                <h3>Configuración API</h3>
                <p>Configura conexión con Moodle.</p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=pcc-settings')); ?>" class="button">Configurar</a>
            </div>

            <div class="pcc-card">
                <img alt="" src="<?php echo esc_url(PCC_WOOOTEC_URL . 'assets/img/sync-icon.svg'); ?>">
                <h3>Logs</h3>
                <p>Revisar eventos y matrículas (por ahora en error_log).</p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=pcc-logs')); ?>" class="button">Ver Logs</a>
            </div>
        </div>
    </div>

    <?php
}
