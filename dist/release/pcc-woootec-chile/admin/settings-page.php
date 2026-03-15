<?php

if (!defined('ABSPATH')) {
    exit;
}

function pcc_register_settings() {
    register_setting(
        'pcc_woootec_settings',
        'pcc_moodle_url',
        array(
            'type'              => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default'           => '',
        )
    );

    register_setting(
        'pcc_woootec_settings',
        'pcc_moodle_token',
        array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        )
    );

    register_setting(
        'pcc_woootec_settings',
        'pcc_moodle_student_role',
        array(
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'default'           => 5,
        )
    );

    register_setting(
        'pcc_woootec_settings',
        'pcc_aula_url',
        array(
            'type'              => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default'           => '',
        )
    );

    register_setting(
        'pcc_woootec_settings',
        'pcc_debug_mode',
        array(
            'type'              => 'boolean',
            'sanitize_callback' => static function ($value) {
                return (bool) $value;
            },
            'default'           => false,
        )
    );

    register_setting(
        'pcc_woootec_settings',
        'pcc_wc_course_category',
        array(
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'default'           => 0,
        )
    );
}

function pcc_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $moodle_url = (string) get_option('pcc_moodle_url');
    $token = (string) get_option('pcc_moodle_token');
    $roleid = (int) get_option('pcc_moodle_student_role', 5);
    $aula_url = (string) get_option('pcc_aula_url');
    $debug = (bool) get_option('pcc_debug_mode', false);
    $category = (int) get_option('pcc_wc_course_category', 0);

    ?>

    <div class="wrap pcc-admin">
        <h1>Configuración PCC WooOTEC</h1>

        <form method="post" action="options.php">
            <?php settings_fields('pcc_woootec_settings'); ?>

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="pcc_moodle_url">Moodle URL</label></th>
                    <td>
                        <input name="pcc_moodle_url" id="pcc_moodle_url" type="url" class="regular-text" value="<?php echo esc_attr($moodle_url); ?>" placeholder="https://aula.tuotec.cl">
                        <p class="description">URL base de Moodle (sin trailing slash).</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="pcc_moodle_token">Moodle Token</label></th>
                    <td>
                        <input name="pcc_moodle_token" id="pcc_moodle_token" type="password" class="regular-text" value="<?php echo esc_attr($token); ?>" autocomplete="off">
                        <p class="description">Token de Web Services de Moodle.</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="pcc_moodle_student_role">Role ID Estudiante</label></th>
                    <td>
                        <input name="pcc_moodle_student_role" id="pcc_moodle_student_role" type="number" class="small-text" value="<?php echo esc_attr((string) $roleid); ?>" min="1" step="1">
                        <p class="description">Por defecto 5 (student). Ajusta según tu Moodle.</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="pcc_aula_url">Aula URL (correo)</label></th>
                    <td>
                        <input name="pcc_aula_url" id="pcc_aula_url" type="url" class="regular-text" value="<?php echo esc_attr($aula_url); ?>" placeholder="https://aula.tuotec.cl">
                        <p class="description">Link que se envía por email. Si queda vacío, se usa Moodle URL.</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="pcc_wc_course_category">Categoría WooCommerce</label></th>
                    <td>
                        <input name="pcc_wc_course_category" id="pcc_wc_course_category" type="number" class="small-text" value="<?php echo esc_attr((string) $category); ?>" min="0" step="1">
                        <p class="description">ID de categoría para asignar a productos de cursos (0 = no asignar).</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="pcc_debug_mode">Modo Debug</label></th>
                    <td>
                        <label>
                            <input name="pcc_debug_mode" id="pcc_debug_mode" type="checkbox" value="1" <?php checked($debug); ?>>
                            Activar logs extra
                        </label>
                    </td>
                </tr>
            </table>

            <?php submit_button('Guardar cambios'); ?>
        </form>

        <hr>

        <h2>Test de conexión</h2>
        <?php if (function_exists('pcc_moodle_test_connection')) : ?>
            <?php
            $ok = pcc_moodle_test_connection();
            if ($ok) {
                echo '<p><strong style="color: #1d7f1d;">Conexión OK</strong></p>';
            } else {
                echo '<p><strong style="color: #b32d2e;">Conexión FALLIDA</strong></p>';
            }
            ?>
        <?php else : ?>
            <p>No se pudo cargar el módulo de Moodle API.</p>
        <?php endif; ?>
    </div>

    <?php
}

