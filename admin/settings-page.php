<?php

if (!defined('ABSPATH')) {
    exit;
}

function pcc_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $defaults = pcc_get_default_options();
    $values = array();
    foreach (array_keys($defaults) as $key) {
        $values[$key] = pcc_get_option($key, $defaults[$key]);
    }

    $image_preview = '';
    if ((int) $values['pcc_default_image_id'] > 0) {
        $image_preview = wp_get_attachment_image_url((int) $values['pcc_default_image_id'], 'medium');
    }
    ?>
    <div class="wrap pcc-admin">
        <h1>PCC WooOTEC Chile</h1>

        <form method="post" action="options.php">
            <?php settings_fields('pcc_woootec_settings'); ?>

            <h2>Configuracion Moodle</h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="pcc_moodle_url">Moodle URL</label></th>
                    <td><input name="pcc_moodle_url" id="pcc_moodle_url" type="url" class="regular-text" value="<?php echo esc_attr((string) $values['pcc_moodle_url']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="pcc_moodle_token">Moodle Token</label></th>
                    <td><input name="pcc_moodle_token" id="pcc_moodle_token" type="password" class="regular-text" value="<?php echo esc_attr((string) $values['pcc_moodle_token']); ?>" autocomplete="off"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="pcc_moodle_student_role">Role ID estudiante</label></th>
                    <td><input name="pcc_moodle_student_role" id="pcc_moodle_student_role" type="number" class="small-text" value="<?php echo esc_attr((string) $values['pcc_moodle_student_role']); ?>"></td>
                </tr>
            </table>

            <h2>Configuracion por defecto</h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">Imagen por defecto</th>
                    <td>
                        <input type="hidden" name="pcc_default_image_id" id="pcc_default_image_id" value="<?php echo esc_attr((string) $values['pcc_default_image_id']); ?>">
                        <button type="button" class="button" id="pcc-select-default-image">Seleccionar imagen</button>
                        <button type="button" class="button" id="pcc-remove-default-image">Quitar</button>
                        <div style="margin-top:12px;">
                            <img id="pcc-default-image-preview" src="<?php echo esc_url($image_preview); ?>" alt="" style="max-width:180px;<?php echo $image_preview ? '' : 'display:none;'; ?>">
                        </div>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="pcc_default_price">Precio por defecto</label></th>
                    <td><input name="pcc_default_price" id="pcc_default_price" type="text" class="regular-text" value="<?php echo esc_attr((string) $values['pcc_default_price']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="pcc_default_duration">Duracion por defecto</label></th>
                    <td><input name="pcc_default_duration" id="pcc_default_duration" type="text" class="regular-text" value="<?php echo esc_attr((string) $values['pcc_default_duration']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="pcc_default_instructor">Instructor por defecto</label></th>
                    <td><input name="pcc_default_instructor" id="pcc_default_instructor" type="text" class="regular-text" value="<?php echo esc_attr((string) $values['pcc_default_instructor']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="pcc_fallback_description">Descripcion fallback</label></th>
                    <td><textarea name="pcc_fallback_description" id="pcc_fallback_description" rows="4" class="large-text"><?php echo esc_textarea((string) $values['pcc_fallback_description']); ?></textarea></td>
                </tr>
            </table>

            <h2>Configuracion SSO</h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="pcc_sso_enabled">Activar SSO</label></th>
                    <td><input name="pcc_sso_enabled" id="pcc_sso_enabled" type="checkbox" value="1" <?php checked(!empty($values['pcc_sso_enabled'])); ?>></td>
                </tr>
                <tr>
                    <th scope="row"><label for="pcc_sso_base_url">URL base SSO</label></th>
                    <td><input name="pcc_sso_base_url" id="pcc_sso_base_url" type="url" class="regular-text" value="<?php echo esc_attr((string) $values['pcc_sso_base_url']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="pcc_aula_url">URL aula visible al usuario</label></th>
                    <td><input name="pcc_aula_url" id="pcc_aula_url" type="url" class="regular-text" value="<?php echo esc_attr((string) $values['pcc_aula_url']); ?>"></td>
                </tr>
            </table>

            <h2>Actualizaciones GitHub</h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="pcc_github_repo">Repositorio</label></th>
                    <td><input name="pcc_github_repo" id="pcc_github_repo" type="text" class="regular-text" value="<?php echo esc_attr((string) $values['pcc_github_repo']); ?>" placeholder="owner/repo"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="pcc_github_release_json">release.json URL</label></th>
                    <td><input name="pcc_github_release_json" id="pcc_github_release_json" type="url" class="regular-text" value="<?php echo esc_attr((string) $values['pcc_github_release_json']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="pcc_github_auto_update">Auto update</label></th>
                    <td><input name="pcc_github_auto_update" id="pcc_github_auto_update" type="checkbox" value="1" <?php checked(!empty($values['pcc_github_auto_update'])); ?>></td>
                </tr>
            </table>

            <h2>Debug</h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="pcc_debug_mode">Activar logs</label></th>
                    <td><input name="pcc_debug_mode" id="pcc_debug_mode" type="checkbox" value="1" <?php checked(!empty($values['pcc_debug_mode'])); ?>></td>
                </tr>
            </table>

            <?php submit_button('Guardar configuracion'); ?>
        </form>

        <script>
        (function($){
            var frame;
            $('#pcc-select-default-image').on('click', function(e){
                e.preventDefault();
                if (frame) {
                    frame.open();
                    return;
                }
                frame = wp.media({
                    title: 'Selecciona una imagen por defecto',
                    button: { text: 'Usar imagen' },
                    multiple: false
                });
                frame.on('select', function(){
                    var attachment = frame.state().get('selection').first().toJSON();
                    $('#pcc_default_image_id').val(attachment.id);
                    $('#pcc-default-image-preview').attr('src', attachment.url).show();
                });
                frame.open();
            });
            $('#pcc-remove-default-image').on('click', function(e){
                e.preventDefault();
                $('#pcc_default_image_id').val('');
                $('#pcc-default-image-preview').attr('src', '').hide();
            });
        })(jQuery);
        </script>
    </div>
    <?php
}
