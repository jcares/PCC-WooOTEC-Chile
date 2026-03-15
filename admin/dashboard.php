<?php

if (!defined('ABSPATH')) {
    exit;
}

function pcc_dashboard() {
    ?>

    <div class="wrap pcc-admin">

        <h1>PCC WooOTEC Chile</h1>

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
                <p>Revisar eventos y matrículas.</p>
                <a href="#" class="button">Ver Logs</a>
            </div>

        </div>

    </div>

    <?php
}

