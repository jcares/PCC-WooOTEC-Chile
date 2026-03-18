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
            <span class="pcc-brand-bar__logo-wrap">
                <img class="pcc-brand-bar__logo" src="<?php echo esc_url($brand_logo_url); ?>" alt="PCCurico">
            </span>
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

            <div class="pcc-tabs-wrap">
                <div class="pcc-tabs" role="tablist" aria-label="Secciones de configuracion">
                    <button type="button" class="pcc-tab is-active" id="pcc-tab-moodle" data-tab="moodle" role="tab" aria-selected="true" aria-controls="pcc-panel-moodle">Configuracion Moodle</button>
                    <button type="button" class="pcc-tab" id="pcc-tab-defaults" data-tab="defaults" role="tab" aria-selected="false" aria-controls="pcc-panel-defaults">Configuracion por defecto</button>
                    <button type="button" class="pcc-tab" id="pcc-tab-sso" data-tab="sso" role="tab" aria-selected="false" aria-controls="pcc-panel-sso">SSO</button>
                    <button type="button" class="pcc-tab" id="pcc-tab-emails" data-tab="emails" role="tab" aria-selected="false" aria-controls="pcc-panel-emails">Correos</button>
                    <button type="button" class="pcc-tab" id="pcc-tab-logs" data-tab="logs" role="tab" aria-selected="false" aria-controls="pcc-panel-logs">Logs</button>
                    <button type="button" class="pcc-tab" id="pcc-tab-updates" data-tab="updates" role="tab" aria-selected="false" aria-controls="pcc-panel-updates">Actualizaciones</button>
                </div>
            </div>

            <section class="pcc-tab-panel is-active" id="pcc-panel-moodle" data-panel="moodle" role="tabpanel" aria-labelledby="pcc-tab-moodle">
                <h3>Configuracion Moodle</h3>
                <p class="description">Configura la conexion principal con tu LMS Moodle.</p>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="pcc_woootec_pro_moodle_url">Moodle URL</label></th>
                    <td>
                        <input class="regular-text" type="url" id="pcc_woootec_pro_moodle_url" name="pcc_woootec_pro_moodle_url" value="<?php echo esc_attr((string) $core->get_option('moodle_url', '')); ?>">
                        <p class="pcc-field-help">Ingresa la URL principal de tu plataforma Moodle, por ejemplo: `https://campus.tudominio.cl`.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="pcc_woootec_pro_moodle_token">Moodle Token</label></th>
                    <td>
                        <input class="regular-text" type="password" id="pcc_woootec_pro_moodle_token" name="pcc_woootec_pro_moodle_token" value="<?php echo esc_attr((string) $core->get_option('moodle_token', '')); ?>" autocomplete="off">
                        <p class="pcc-field-help">Pega aquí el token del servicio web de Moodle con permisos para consultar cursos, categorías y usuarios.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="pcc_woootec_pro_student_role_id">Role ID estudiante</label></th>
                    <td>
                        <input class="small-text" type="number" id="pcc_woootec_pro_student_role_id" name="pcc_woootec_pro_student_role_id" value="<?php echo esc_attr((string) $core->get_option('student_role_id', 5)); ?>">
                        <p class="pcc-field-help">Es el ID numérico del rol de estudiante dentro de Moodle. En muchos sitios suele ser `5`, pero puede variar.</p>
                    </td>
                </tr>
            </table>
            </section>

            <section class="pcc-tab-panel" id="pcc-panel-defaults" data-panel="defaults" role="tabpanel" aria-labelledby="pcc-tab-defaults">
                <h3>Configuracion por defecto</h3>
                <p class="description">Valores base cuando Moodle no entregue algun dato.</p>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">Imagen por defecto</th>
                    <td>
                        <input type="hidden" id="pcc_woootec_pro_default_image_id" name="pcc_woootec_pro_default_image_id" value="<?php echo esc_attr((string) $default_image_id); ?>">
                        <button type="button" class="button pcc-media-picker" data-target="#pcc_woootec_pro_default_image_id" data-preview="#pcc-default-image-preview">Seleccionar imagen</button>
                        <p class="pcc-field-help">Se usará cuando un curso no traiga imagen desde Moodle o no tenga imagen destacada asignada.</p>
                        <div class="pcc-image-preview-wrap">
                            <img id="pcc-default-image-preview" src="<?php echo esc_url($default_image_url); ?>" alt="" class="pcc-image-preview<?php echo $default_image_url === '' ? ' is-hidden' : ''; ?>">
                        </div>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="pcc_woootec_pro_default_price">Precio por defecto</label></th>
                    <td>
                        <input class="regular-text" type="text" id="pcc_woootec_pro_default_price" name="pcc_woootec_pro_default_price" value="<?php echo esc_attr((string) $core->get_option('default_price', '49000')); ?>">
                        <p class="pcc-field-help">Valor usado cuando el curso no tenga precio definido. Ingresa sólo el monto, por ejemplo `49000`.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="pcc_woootec_pro_default_instructor">Instructor por defecto</label></th>
                    <td>
                        <input class="regular-text" type="text" id="pcc_woootec_pro_default_instructor" name="pcc_woootec_pro_default_instructor" value="<?php echo esc_attr((string) $core->get_option('default_instructor', 'No asignado')); ?>">
                        <p class="pcc-field-help">Nombre que se mostrará si Moodle no informa docente, relator o instructor para el curso.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="pcc_woootec_pro_fallback_description">Descripcion fallback</label></th>
                    <td>
                        <textarea class="large-text" rows="4" id="pcc_woootec_pro_fallback_description" name="pcc_woootec_pro_fallback_description"><?php echo esc_textarea((string) $core->get_option('fallback_description', '')); ?></textarea>
                        <p class="pcc-field-help">Texto alternativo para completar la descripción del producto cuando Moodle no entregue contenido suficiente.</p>
                    </td>
                </tr>
            </table>
            </section>

            <section class="pcc-tab-panel" id="pcc-panel-sso" data-panel="sso" role="tabpanel" aria-labelledby="pcc-tab-sso">
                <h3>SSO</h3>
                <p class="description">Activa el acceso directo a Moodle por usuario y curso.</p>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="pcc_woootec_pro_sso_enabled">Activar SSO</label></th>
                    <td>
                        <input type="checkbox" id="pcc_woootec_pro_sso_enabled" name="pcc_woootec_pro_sso_enabled" value="yes" <?php checked($core->get_option('sso_enabled', 'yes'), 'yes'); ?>>
                        <p class="pcc-field-help">Activa el ingreso automático al aula para que el alumno no tenga que iniciar sesión manualmente en Moodle.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="pcc_woootec_pro_sso_base_url">URL base SSO</label></th>
                    <td>
                        <input class="regular-text" type="url" id="pcc_woootec_pro_sso_base_url" name="pcc_woootec_pro_sso_base_url" value="<?php echo esc_attr((string) $core->get_option('sso_base_url', '')); ?>">
                        <p class="pcc-field-help">Dirección base que usa Moodle para el acceso directo. Debe ser la URL exacta del endpoint SSO configurado en tu plataforma.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="pcc_woootec_pro_redirect_after_purchase">Redirigir tras compra</label></th>
                    <td>
                        <input type="checkbox" id="pcc_woootec_pro_redirect_after_purchase" name="pcc_woootec_pro_redirect_after_purchase" value="yes" <?php checked($core->get_option('redirect_after_purchase', 'no'), 'yes'); ?>>
                        <p class="pcc-field-help">Si está activo, al completar la compra el usuario será enviado directamente al acceso de su aula o curso.</p>
                    </td>
                </tr>
            </table>
            </section>

            <section class="pcc-tab-panel" id="pcc-panel-emails" data-panel="emails" role="tabpanel" aria-labelledby="pcc-tab-emails">
                <h3>Correos</h3>
                <p class="description">Configura el envio automatico de accesos y prueba la plantilla.</p>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="pcc_woootec_pro_email_enabled">Activar envio</label></th>
                        <td>
                            <input type="checkbox" id="pcc_woootec_pro_email_enabled" name="pcc_woootec_pro_email_enabled" value="yes" <?php checked($core->get_option('email_enabled', 'yes'), 'yes'); ?>>
                            <p class="pcc-field-help">Envía automáticamente al alumno un correo con sus datos de acceso después de la compra o matrícula.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pcc_woootec_pro_email_subject">Asunto</label></th>
                        <td>
                            <input class="regular-text" type="text" id="pcc_woootec_pro_email_subject" name="pcc_woootec_pro_email_subject" value="<?php echo esc_attr((string) $core->get_option('email_subject', '')); ?>">
                            <p class="pcc-field-help">Escribe el título del correo que verá el alumno en su bandeja de entrada.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pcc_woootec_pro_email_template">Plantilla HTML</label></th>
                        <td>
                            <textarea class="large-text code" rows="12" id="pcc_woootec_pro_email_template" name="pcc_woootec_pro_email_template"><?php echo esc_textarea((string) $core->get_option('email_template', '')); ?></textarea>
                            <p class="pcc-field-help">Puedes personalizar el diseño del correo. Si no conoces HTML, modifica sólo los textos visibles y conserva las variables.</p>
                            <p class="description">Variables: {{nombre}}, {{email}}, {{password}}, {{url_acceso}}, {{cursos}}, {{sitio}}</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pcc_woootec_pro_email_test_recipient">Correo de prueba</label></th>
                        <td>
                            <input class="regular-text" type="email" id="pcc_woootec_pro_email_test_recipient" name="pcc_woootec_pro_email_test_recipient" value="<?php echo esc_attr((string) $core->get_option('email_test_recipient', '')); ?>">
                            <p class="pcc-field-help">Ingresa un correo real para probar la plantilla antes de usarla con alumnos.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pcc_woootec_pro_retry_limit">Reintentos de matricula</label></th>
                        <td>
                            <input class="small-text" type="number" min="1" max="10" id="pcc_woootec_pro_retry_limit" name="pcc_woootec_pro_retry_limit" value="<?php echo esc_attr((string) $core->get_option('retry_limit', 3)); ?>">
                            <p class="pcc-field-help">Cantidad de veces que el sistema volverá a intentar una matrícula fallida antes de marcarla como error.</p>
                        </td>
                    </tr>
                </table>
                <div class="pcc-email-tools">
                    <button type="button" class="button" data-email-preview>Vista previa</button>
                    <button type="button" class="button button-secondary" data-email-send-test>Enviar prueba</button>
                </div>
                <div class="pcc-email-feedback" data-email-feedback></div>
                <div class="pcc-email-preview" data-email-preview-box></div>
            </section>

            <section class="pcc-tab-panel" id="pcc-panel-logs" data-panel="logs" role="tabpanel" aria-labelledby="pcc-tab-logs">
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
                        <td>
                            <input type="checkbox" id="pcc_woootec_pro_debug_enabled" name="pcc_woootec_pro_debug_enabled" value="yes" <?php checked($core->get_option('debug_enabled', 'no'), 'yes'); ?>>
                            <p class="pcc-field-help">Guarda más detalle técnico en los logs. Úsalo para diagnóstico y desactívalo cuando no sea necesario.</p>
                        </td>
                    </tr>
                </table>
            </section>

            <section class="pcc-tab-panel" id="pcc-panel-updates" data-panel="updates" role="tabpanel" aria-labelledby="pcc-tab-updates">
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
                        <p class="pcc-field-help">Formato esperado: `usuario/repositorio`. Ejemplo: `jcares/PCC-WooOTEC-Chile`.</p>
                        <p class="description"><a href="<?php echo esc_url($release_page_url); ?>" target="_blank" rel="noopener noreferrer">Abrir pagina de releases</a></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="pcc_woootec_pro_github_release_url">release.json URL</label></th>
                    <td>
                        <input class="regular-text" type="url" id="pcc_woootec_pro_github_release_url" name="pcc_woootec_pro_github_release_url" value="<?php echo esc_attr((string) $core->get_option('github_release_url', $release_json_url)); ?>">
                        <p class="pcc-field-help">URL directa al archivo `release.json` que informa la última versión disponible del plugin.</p>
                        <p class="description"><a href="<?php echo esc_url($release_json_url); ?>" target="_blank" rel="noopener noreferrer">Ver release.json</a></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="pcc_woootec_pro_auto_update">Auto update</label></th>
                    <td>
                        <input type="checkbox" id="pcc_woootec_pro_auto_update" name="pcc_woootec_pro_auto_update" value="yes" <?php checked($core->get_option('auto_update', 'no'), 'yes'); ?>>
                        <p class="pcc-field-help">Si lo activas, WordPress podrá instalar nuevas versiones automáticamente cuando el update server las detecte.</p>
                    </td>
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
