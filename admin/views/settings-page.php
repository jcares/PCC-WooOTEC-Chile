<?php

if (!defined('ABSPATH')) {
    exit;
}

$default_image_id = (int) $core->get_option('default_image_id', 0);
$default_image_url = $default_image_id > 0 ? wp_get_attachment_image_url($default_image_id, 'medium') : '';
$release_page_url = 'https://github.com/jcares/PCC-WooOTEC-Chile/releases';
$release_json_url = 'https://github.com/jcares/PCC-WooOTEC-Chile/blob/main/release.json';
$brand_logo_url = PCC_WOOOTEC_PRO_URL . 'assets/images/logo-pccurico.png';
?>
<div class="wrap pcc-admin-wrap">
    <div class="pcc-brand-bar">
        <div class="pcc-brand-bar__main">
            <img class="pcc-brand-bar__logo" src="<?php echo esc_url($brand_logo_url); ?>" alt="PCCurico">
            <div>
                <h1>PCC WooOTEC Chile PRO</h1>
                <p class="pcc-brand-bar__subtitle">Plataforma comercial Moodle + WooCommerce para OTEC.</p>
            </div>
        </div>
        <div class="pcc-brand-bar__meta">
            <span>www.pccurico.cl</span>
            <span>desarrollado por JCares</span>
        </div>
    </div>

    <form method="post" action="options.php" class="pcc-settings-form">
        <?php settings_fields('pcc_woootec_pro_settings'); ?>
        <div class="pcc-card pcc-dashboard-shell">
            <div class="pcc-dashboard-hero">
                <div>
                    <h2>Dashboard</h2>
                    <p>Administra Moodle, defaults, SSO, logs y actualizaciones desde un solo lugar.</p>
                </div>
                <div class="pcc-dashboard-status">
                    <span class="pcc-badge <?php echo $connection_ok ? 'is-success' : 'is-warning'; ?>">Moodle <?php echo $connection_ok ? 'conectado' : 'pendiente'; ?></span>
                    <span class="pcc-badge <?php echo !empty($update_available) ? 'is-info' : 'is-muted'; ?>">Actualizacion <?php echo !empty($update_available) ? 'disponible' : 'sin novedades'; ?></span>
                </div>
            </div>

            <div class="pcc-tabs" role="tablist">
                <button type="button" class="pcc-tab is-active" data-tab="moodle">Configuracion Moodle</button>
                <button type="button" class="pcc-tab" data-tab="defaults">Configuracion por defecto</button>
                <button type="button" class="pcc-tab" data-tab="sso">SSO</button>
                <button type="button" class="pcc-tab" data-tab="emails">Correos</button>
                <button type="button" class="pcc-tab" data-tab="logs">Logs</button>
                <button type="button" class="pcc-tab" data-tab="updates">Actualizaciones</button>
            </div>

            <section class="pcc-tab-panel is-active" data-panel="moodle">
                <h3>Configuracion Moodle</h3>
                <p class="description">Configura la conexion principal con tu LMS Moodle.</p>
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
            </section>

            <section class="pcc-tab-panel" data-panel="defaults">
                <h3>Configuracion por defecto</h3>
                <p class="description">Valores base cuando Moodle no entregue algun dato.</p>
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
            </section>

            <section class="pcc-tab-panel" data-panel="sso">
                <h3>SSO</h3>
                <p class="description">Activa el acceso directo a Moodle por usuario y curso.</p>
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
            </section>

            <section class="pcc-tab-panel" data-panel="emails">
                <h3>Correos</h3>
                <p class="description">Configura el envio automatico de accesos y prueba la plantilla.</p>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="pcc_woootec_pro_email_enabled">Activar envio</label></th>
                        <td><input type="checkbox" id="pcc_woootec_pro_email_enabled" name="pcc_woootec_pro_email_enabled" value="yes" <?php checked($core->get_option('email_enabled', 'yes'), 'yes'); ?>></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pcc_woootec_pro_email_subject">Asunto</label></th>
                        <td><input class="regular-text" type="text" id="pcc_woootec_pro_email_subject" name="pcc_woootec_pro_email_subject" value="<?php echo esc_attr((string) $core->get_option('email_subject', '')); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pcc_woootec_pro_email_template">Plantilla HTML</label></th>
                        <td>
                            <textarea class="large-text code" rows="12" id="pcc_woootec_pro_email_template" name="pcc_woootec_pro_email_template"><?php echo esc_textarea((string) $core->get_option('email_template', '')); ?></textarea>
                            <p class="description">Variables: {{nombre}}, {{email}}, {{password}}, {{url_acceso}}, {{cursos}}, {{sitio}}</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pcc_woootec_pro_email_test_recipient">Correo de prueba</label></th>
                        <td><input class="regular-text" type="email" id="pcc_woootec_pro_email_test_recipient" name="pcc_woootec_pro_email_test_recipient" value="<?php echo esc_attr((string) $core->get_option('email_test_recipient', '')); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pcc_woootec_pro_retry_limit">Reintentos de matricula</label></th>
                        <td><input class="small-text" type="number" min="1" max="10" id="pcc_woootec_pro_retry_limit" name="pcc_woootec_pro_retry_limit" value="<?php echo esc_attr((string) $core->get_option('retry_limit', 3)); ?>"></td>
                    </tr>
                </table>
                <div class="pcc-email-tools">
                    <button type="button" class="button" data-email-preview>Vista previa</button>
                    <button type="button" class="button button-secondary" data-email-send-test>Enviar prueba</button>
                </div>
                <div class="pcc-email-feedback" data-email-feedback></div>
                <div class="pcc-email-preview" data-email-preview-box></div>
            </section>

            <section class="pcc-tab-panel" data-panel="logs">
                <h3>Logs</h3>
                <p class="description">Visualizacion rapida de los ultimos eventos de sincronizacion y errores.</p>
                <div class="pcc-log-grid">
                    <div>
                        <h4>sync.log</h4>
                        <pre class="pcc-log-view"><?php echo esc_html(implode("\n", $sync_log)); ?></pre>
                    </div>
                    <div>
                        <h4>error.log</h4>
                        <pre class="pcc-log-view"><?php echo esc_html(implode("\n", $error_log)); ?></pre>
                    </div>
                </div>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="pcc_woootec_pro_debug_enabled">Modo debug</label></th>
                        <td><input type="checkbox" id="pcc_woootec_pro_debug_enabled" name="pcc_woootec_pro_debug_enabled" value="yes" <?php checked($core->get_option('debug_enabled', 'no'), 'yes'); ?>></td>
                    </tr>
                </table>
            </section>

            <section class="pcc-tab-panel" data-panel="updates">
                <h3>Actualizaciones</h3>
                <div class="pcc-update-callout <?php echo !empty($update_available) ? 'has-update' : ''; ?>">
                    <p><strong>Version instalada:</strong> <?php echo esc_html(PCC_WOOOTEC_PRO_VERSION); ?></p>
                    <p><strong>Estado:</strong> <?php echo !empty($update_available) ? 'Existe una actualizacion disponible.' : 'No hay actualizaciones disponibles.'; ?></p>
                    <?php if (!empty($release) && !empty($release['version'])) : ?>
                        <p><strong>Ultima version detectada:</strong> <?php echo esc_html((string) $release['version']); ?></p>
                    <?php endif; ?>
                </div>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="pcc_woootec_pro_github_repo">Repositorio</label></th>
                    <td>
                        <input class="regular-text" type="text" id="pcc_woootec_pro_github_repo" name="pcc_woootec_pro_github_repo" value="<?php echo esc_attr((string) $core->get_option('github_repo', '')); ?>" placeholder="owner/repo">
                        <p class="description"><a href="<?php echo esc_url($release_page_url); ?>" target="_blank" rel="noopener noreferrer">Abrir pagina de releases</a></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="pcc_woootec_pro_github_release_url">release.json URL</label></th>
                    <td>
                        <input class="regular-text" type="url" id="pcc_woootec_pro_github_release_url" name="pcc_woootec_pro_github_release_url" value="<?php echo esc_attr((string) $core->get_option('github_release_url', $release_json_url)); ?>">
                        <p class="description"><a href="<?php echo esc_url($release_json_url); ?>" target="_blank" rel="noopener noreferrer">Ver release.json</a></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="pcc_woootec_pro_auto_update">Auto update</label></th>
                    <td><input type="checkbox" id="pcc_woootec_pro_auto_update" name="pcc_woootec_pro_auto_update" value="yes" <?php checked($core->get_option('auto_update', 'no'), 'yes'); ?>></td>
                </tr>
            </table>
            </section>
        </div>

        <?php submit_button('Guardar configuracion'); ?>
    </form>

    <div class="pcc-admin-signature">
        <span>www.pccurico.cl</span>
        <span>desarrollado por JCares</span>
    </div>
</div>
