<?php

if (!defined('ABSPATH')) {
    exit;
}

$default_image_id = (int) $core->get_option('default_image_id', 0);
$default_image_url = $default_image_id > 0 ? wp_get_attachment_image_url($default_image_id, 'medium') : '';
?>
<div class="wrap pcc-admin-wrap">
    <h1>PCC WooOTEC Chile PRO</h1>

    <div class="pcc-admin-grid">
        <div class="pcc-card">
            <h2>Estado</h2>
            <p><strong>Conexion Moodle:</strong> <?php echo $connection_ok ? 'OK' : 'Pendiente o fallida'; ?></p>
            <p><strong>Ultima sincronizacion:</strong> <?php echo !empty($last_sync['timestamp']) ? esc_html((string) $last_sync['timestamp']) : 'Sin ejecuciones'; ?></p>
            <p><strong>Resultado:</strong> <?php echo !empty($last_sync['message']) ? esc_html((string) $last_sync['message']) : 'Sin datos'; ?></p>
        </div>
    </div>

    <form method="post" action="options.php" class="pcc-settings-form">
        <?php settings_fields('pcc_woootec_pro_settings'); ?>

        <div class="pcc-card">
            <h2>Configuracion Moodle</h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="pcc_woootec_pro_moodle_url">Moodle URL</label></th>
                    <td><input class="regular-text" type="url" id="pcc_woootec_pro_moodle_url" name="pcc_woootec_pro_moodle_url" value="<?php echo esc_attr((string) $core->get_option('moodle_url', '')); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="pcc_woootec_pro_moodle_token">Moodle Token</label></th>
                    <td><input class="regular-text" type="password" id="pcc_woootec_pro_moodle_token" name="pcc_woootec_pro_moodle_token" value="<?php echo esc_attr((string) $core->get_option('moodle_token', '')); ?>" autocomplete="off"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="pcc_woootec_pro_student_role_id">Role ID estudiante</label></th>
                    <td><input class="small-text" type="number" id="pcc_woootec_pro_student_role_id" name="pcc_woootec_pro_student_role_id" value="<?php echo esc_attr((string) $core->get_option('student_role_id', 5)); ?>"></td>
                </tr>
            </table>
        </div>

        <div class="pcc-card">
            <h2>Configuracion por defecto</h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">Imagen por defecto</th>
                    <td>
                        <input type="hidden" id="pcc_woootec_pro_default_image_id" name="pcc_woootec_pro_default_image_id" value="<?php echo esc_attr((string) $default_image_id); ?>">
                        <button type="button" class="button pcc-media-picker" data-target="#pcc_woootec_pro_default_image_id" data-preview="#pcc-default-image-preview">Seleccionar imagen</button>
                        <div class="pcc-image-preview-wrap">
                            <img id="pcc-default-image-preview" src="<?php echo esc_url($default_image_url); ?>" alt="" class="pcc-image-preview<?php echo $default_image_url === '' ? ' is-hidden' : ''; ?>">
                        </div>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="pcc_woootec_pro_default_price">Precio por defecto</label></th>
                    <td><input class="regular-text" type="text" id="pcc_woootec_pro_default_price" name="pcc_woootec_pro_default_price" value="<?php echo esc_attr((string) $core->get_option('default_price', '49000')); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="pcc_woootec_pro_default_instructor">Instructor por defecto</label></th>
                    <td><input class="regular-text" type="text" id="pcc_woootec_pro_default_instructor" name="pcc_woootec_pro_default_instructor" value="<?php echo esc_attr((string) $core->get_option('default_instructor', 'No asignado')); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="pcc_woootec_pro_fallback_description">Descripcion fallback</label></th>
                    <td><textarea class="large-text" rows="4" id="pcc_woootec_pro_fallback_description" name="pcc_woootec_pro_fallback_description"><?php echo esc_textarea((string) $core->get_option('fallback_description', '')); ?></textarea></td>
                </tr>
            </table>
        </div>

        <div class="pcc-card">
            <h2>SSO</h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="pcc_woootec_pro_sso_enabled">Activar SSO</label></th>
                    <td><input type="checkbox" id="pcc_woootec_pro_sso_enabled" name="pcc_woootec_pro_sso_enabled" value="yes" <?php checked($core->get_option('sso_enabled', 'yes'), 'yes'); ?>></td>
                </tr>
                <tr>
                    <th scope="row"><label for="pcc_woootec_pro_sso_base_url">URL base SSO</label></th>
                    <td><input class="regular-text" type="url" id="pcc_woootec_pro_sso_base_url" name="pcc_woootec_pro_sso_base_url" value="<?php echo esc_attr((string) $core->get_option('sso_base_url', '')); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="pcc_woootec_pro_redirect_after_purchase">Redirigir tras compra</label></th>
                    <td><input type="checkbox" id="pcc_woootec_pro_redirect_after_purchase" name="pcc_woootec_pro_redirect_after_purchase" value="yes" <?php checked($core->get_option('redirect_after_purchase', 'no'), 'yes'); ?>></td>
                </tr>
            </table>
        </div>

        <div class="pcc-card">
            <h2>Actualizaciones GitHub</h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="pcc_woootec_pro_github_repo">Repositorio</label></th>
                    <td><input class="regular-text" type="text" id="pcc_woootec_pro_github_repo" name="pcc_woootec_pro_github_repo" value="<?php echo esc_attr((string) $core->get_option('github_repo', '')); ?>" placeholder="owner/repo"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="pcc_woootec_pro_github_release_url">release.json URL</label></th>
                    <td><input class="regular-text" type="url" id="pcc_woootec_pro_github_release_url" name="pcc_woootec_pro_github_release_url" value="<?php echo esc_attr((string) $core->get_option('github_release_url', '')); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="pcc_woootec_pro_auto_update">Auto update</label></th>
                    <td><input type="checkbox" id="pcc_woootec_pro_auto_update" name="pcc_woootec_pro_auto_update" value="yes" <?php checked($core->get_option('auto_update', 'no'), 'yes'); ?>></td>
                </tr>
            </table>
        </div>

        <div class="pcc-card">
            <h2>Logs</h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="pcc_woootec_pro_debug_enabled">Modo debug</label></th>
                    <td><input type="checkbox" id="pcc_woootec_pro_debug_enabled" name="pcc_woootec_pro_debug_enabled" value="yes" <?php checked($core->get_option('debug_enabled', 'no'), 'yes'); ?>></td>
                </tr>
            </table>
        </div>

        <?php submit_button('Guardar configuracion'); ?>
    </form>
</div>
