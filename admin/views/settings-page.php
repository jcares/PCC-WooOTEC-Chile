<?php

if (!defined('ABSPATH')) {
    exit;
}

$default_image_id = (int) $core->get_option('default_image_id', 0);
$default_image_url = $default_image_id > 0 ? wp_get_attachment_image_url($default_image_id, 'medium') : '';
$brand_logo_url = PCC_WOOOTEC_PRO_URL . 'assets/images/logo-pccurico.png';
$release_page_url = 'https://github.com/jcares/PCC-WooOTEC-Chile/releases';
$release_json_url = 'https://github.com/jcares/PCC-WooOTEC-Chile/blob/main/release.json';
$last_sync_label = !empty($last_sync['timestamp']) ? (string) $last_sync['timestamp'] : 'Sin ejecuciones';
$sync_status = !empty($last_sync['status']) ? (string) $last_sync['status'] : 'idle';
$email_enabled = $core->get_option('email_enabled', 'yes') === 'yes';
$sso_enabled = $core->get_option('sso_enabled', 'yes') === 'yes';
$email_from_address = (string) $core->get_option('email_from_address', '');
$fallback_from_address = PCC_WooOTEC_Pro_Mailer::instance()->filter_mail_from(get_option('admin_email', ''));
$tabs = array(
    'general'  => 'General',
    'sync'     => 'Sincronizacion',
    'sso'      => 'SSO',
    'emails'   => 'Emails',
    'licenses' => 'Licencias',
);
?>
<div class="wrap pcc-admin-wrap">
    <div class="pcc-brand-bar">
        <div class="pcc-brand-bar__main">
            <span class="pcc-brand-bar__logo-wrap">
                <img class="pcc-brand-bar__logo" src="<?php echo esc_url($brand_logo_url); ?>" alt="PCCurico">
            </span>
            <div>
                <h1>PCC WooOTEC Chile PRO</h1>
                <p class="pcc-brand-bar__subtitle">Integracion WooCommerce + Moodle con base mas limpia para escalar como producto SaaS.</p>
            </div>
        </div>
        <div class="pcc-brand-bar__meta">
            <span>www.pccurico.cl</span>
            <span>desarrollado por JCares</span>
        </div>
    </div>

    <?php if ($status !== '') : ?>
        <div class="notice notice-<?php echo $status === 'success' ? 'success' : 'warning'; ?> is-dismissible">
            <p>
                <?php
                echo esc_html(
                    $status === 'success'
                        ? 'La sincronizacion manual finalizo correctamente.'
                        : 'La sincronizacion manual termino con incidencias. Revisa los logs del tab Sincronizacion.'
                );
                ?>
            </p>
        </div>
    <?php endif; ?>

    <form method="post" action="options.php" class="pcc-settings-form">
        <?php settings_fields('pcc_woootec_pro_settings'); ?>

        <div class="pcc-card pcc-dashboard-shell">
            <div class="pcc-dashboard-hero">
                <div class="pcc-dashboard-hero__copy">
                    <span class="pcc-section-kicker">Panel admin</span>
                    <h2>Configuracion central del plugin</h2>
                    <p>Gestiona conexion, sincronizacion, SSO, plantillas de email y preparacion para licencias desde una interfaz segmentada por tabs.</p>
                </div>
                <div class="pcc-dashboard-status">
                    <span class="pcc-badge <?php echo $connection_ok ? 'is-success' : 'is-warning'; ?>">Moodle <?php echo $connection_ok ? 'conectado' : 'pendiente'; ?></span>
                    <span class="pcc-badge <?php echo $sso_enabled ? 'is-info' : 'is-muted'; ?>">SSO <?php echo $sso_enabled ? 'activo' : 'desactivado'; ?></span>
                    <span class="pcc-badge <?php echo $email_enabled ? 'is-success' : 'is-muted'; ?>">Correos <?php echo $email_enabled ? 'activos' : 'apagados'; ?></span>
                    <span class="pcc-badge <?php echo !empty($update_available) ? 'is-info' : 'is-muted'; ?>">Release <?php echo !empty($update_available) ? 'disponible' : 'estable'; ?></span>
                </div>
            </div>

            <div class="pcc-overview-grid">
                <article class="pcc-overview-card">
                    <span class="pcc-overview-card__label">Conexion Moodle</span>
                    <strong class="pcc-overview-card__value"><?php echo $connection_ok ? 'Operativa' : 'Pendiente'; ?></strong>
                    <p><?php echo $connection_ok ? 'La API responde correctamente.' : 'Revisa URL y token para habilitar la integracion.'; ?></p>
                </article>
                <article class="pcc-overview-card">
                    <span class="pcc-overview-card__label">Ultima sincronizacion</span>
                    <strong class="pcc-overview-card__value"><?php echo esc_html($last_sync_label); ?></strong>
                    <p>Estado actual: <?php echo esc_html($sync_status); ?>.</p>
                </article>
                <article class="pcc-overview-card">
                    <span class="pcc-overview-card__label">Remitente efectivo</span>
                    <strong class="pcc-overview-card__value"><?php echo esc_html($email_from_address !== '' ? $email_from_address : $fallback_from_address); ?></strong>
                    <p>Se usa solo para los correos enviados por este plugin.</p>
                </article>
                <article class="pcc-overview-card">
                    <span class="pcc-overview-card__label">Version</span>
                    <strong class="pcc-overview-card__value"><?php echo esc_html(PCC_WOOOTEC_PRO_VERSION); ?></strong>
                    <p><?php echo !empty($update_available) ? 'Hay una nueva version detectada.' : 'Sin actualizaciones pendientes.'; ?></p>
                </article>
            </div>

            <div class="pcc-dashboard-actions">
                <button type="button" class="button button-primary pcc-quick-tab" data-jump-tab="general">Ir a general</button>
                <button type="button" class="button pcc-quick-tab" data-jump-tab="sync">Ir a sincronizacion</button>
                <button type="button" class="button pcc-quick-tab" data-jump-tab="emails">Ir a emails</button>
            </div>

            <div class="pcc-tabs-wrap">
                <div class="pcc-tabs" role="tablist" aria-label="Secciones de configuracion">
                    <?php foreach ($tabs as $tab_key => $tab_label) : ?>
                        <button
                            type="button"
                            class="pcc-tab <?php echo $active_tab === $tab_key ? 'is-active' : ''; ?>"
                            id="pcc-tab-<?php echo esc_attr($tab_key); ?>"
                            data-tab="<?php echo esc_attr($tab_key); ?>"
                            role="tab"
                            aria-selected="<?php echo $active_tab === $tab_key ? 'true' : 'false'; ?>"
                            aria-controls="pcc-panel-<?php echo esc_attr($tab_key); ?>"
                        >
                            <?php echo esc_html($tab_label); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <section class="pcc-tab-panel <?php echo $active_tab === 'general' ? 'is-active' : ''; ?>" id="pcc-panel-general" data-panel="general" role="tabpanel" aria-labelledby="pcc-tab-general">
                <h3>General</h3>
                <p class="description">Credenciales, parametros base y valores por defecto usados por toda la integracion.</p>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="pcc_woootec_pro_moodle_url">URL Moodle</label></th>
                        <td>
                            <input class="regular-text" type="url" id="pcc_woootec_pro_moodle_url" name="pcc_woootec_pro_moodle_url" value="<?php echo esc_attr((string) $core->get_option('moodle_url', '')); ?>">
                            <p class="pcc-field-help">Ejemplo: `https://campus.tudominio.cl`.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pcc_woootec_pro_moodle_token">Token Moodle</label></th>
                        <td>
                            <input class="regular-text" type="password" id="pcc_woootec_pro_moodle_token" name="pcc_woootec_pro_moodle_token" value="<?php echo esc_attr((string) $core->get_option('moodle_token', '')); ?>" autocomplete="off">
                            <p class="pcc-field-help">Token del servicio web con permisos para usuarios, cursos, categorias y matriculas.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pcc_woootec_pro_student_role_id">Role ID estudiante</label></th>
                        <td>
                            <input class="small-text" type="number" id="pcc_woootec_pro_student_role_id" name="pcc_woootec_pro_student_role_id" value="<?php echo esc_attr((string) $core->get_option('student_role_id', 5)); ?>">
                            <p class="pcc-field-help">Normalmente es `5`, pero depende de tu Moodle.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Imagen por defecto</th>
                        <td>
                            <input type="hidden" id="pcc_woootec_pro_default_image_id" name="pcc_woootec_pro_default_image_id" value="<?php echo esc_attr((string) $default_image_id); ?>">
                            <button type="button" class="button pcc-media-picker" data-target="#pcc_woootec_pro_default_image_id" data-preview="#pcc-default-image-preview">Seleccionar imagen</button>
                            <p class="pcc-field-help">Se usa cuando el curso no trae imagen desde Moodle.</p>
                            <div class="pcc-image-preview-wrap">
                                <img id="pcc-default-image-preview" src="<?php echo esc_url($default_image_url); ?>" alt="" class="pcc-image-preview<?php echo $default_image_url === '' ? ' is-hidden' : ''; ?>">
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pcc_woootec_pro_default_price">Precio default</label></th>
                        <td>
                            <input class="regular-text" type="text" id="pcc_woootec_pro_default_price" name="pcc_woootec_pro_default_price" value="<?php echo esc_attr((string) $core->get_option('default_price', '49000')); ?>">
                            <p class="pcc-field-help">Monto sin simbolos ni separadores, por ejemplo `49000`.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pcc_woootec_pro_default_instructor">Instructor default</label></th>
                        <td>
                            <input class="regular-text" type="text" id="pcc_woootec_pro_default_instructor" name="pcc_woootec_pro_default_instructor" value="<?php echo esc_attr((string) $core->get_option('default_instructor', 'No asignado')); ?>">
                            <p class="pcc-field-help">Texto fallback cuando Moodle no informa docente.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pcc_woootec_pro_fallback_description">Descripcion fallback</label></th>
                        <td>
                            <textarea class="large-text" rows="4" id="pcc_woootec_pro_fallback_description" name="pcc_woootec_pro_fallback_description"><?php echo esc_textarea((string) $core->get_option('fallback_description', '')); ?></textarea>
                            <p class="pcc-field-help">Descripcion usada al crear o actualizar productos sin contenido suficiente.</p>
                        </td>
                    </tr>
                </table>
            </section>

            <section class="pcc-tab-panel <?php echo $active_tab === 'sync' ? 'is-active' : ''; ?>" id="pcc-panel-sync" data-panel="sync" role="tabpanel" aria-labelledby="pcc-tab-sync">
                <h3>Sincronizacion</h3>
                <p class="description">Ejecuta sincronizaciones manuales, revisa el ultimo estado y consulta logs basicos desde el mismo tab.</p>

                <div class="pcc-sync-toolbar">
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="pcc-inline-form">
                        <input type="hidden" name="action" value="pcc_woootec_run_sync">
                        <?php wp_nonce_field('pcc_woootec_run_sync'); ?>
                        <button type="submit" class="button button-primary">Sincronizar ahora</button>
                    </form>
                    <p class="pcc-sync-toolbar__note">Este boton ejecuta la sincronizacion completa con nonce de seguridad. Debajo puedes usar la vista guiada por etapas.</p>
                </div>

                <div class="pcc-sync-result pcc-sync-summary">
                    <p><strong>Ultima ejecucion:</strong> <?php echo esc_html($last_sync_label); ?></p>
                    <p><strong>Mensaje:</strong> <?php echo !empty($last_sync['message']) ? esc_html((string) $last_sync['message']) : 'Sin datos'; ?></p>
                    <p><strong>Categorias:</strong> creadas <?php echo esc_html((string) ($last_sync['categories_created'] ?? 0)); ?> / actualizadas <?php echo esc_html((string) ($last_sync['categories_updated'] ?? 0)); ?></p>
                    <p><strong>Productos:</strong> creados <?php echo esc_html((string) ($last_sync['products_created'] ?? 0)); ?> / actualizados <?php echo esc_html((string) ($last_sync['products_updated'] ?? 0)); ?></p>
                </div>

                <div class="pcc-card pcc-sync-shell" data-sync-app>
                    <div class="pcc-sync-header">
                        <div>
                            <span class="pcc-section-kicker">Sync guiada</span>
                            <h2>Sincronizacion interactiva</h2>
                            <p>Primero sincroniza categorias y luego cursos, con feedback visual por etapa.</p>
                        </div>
                        <div class="pcc-badge is-info">Cron cada 1 hora</div>
                    </div>

                    <div class="pcc-progress" aria-label="Progreso de sincronizacion">
                        <div class="pcc-progress-bar" data-sync-progress style="width:0%">0%</div>
                    </div>

                    <div class="pcc-stage-grid">
                        <article class="pcc-stage-card" data-stage-card="categories">
                            <div class="pcc-stage-step">Etapa 1</div>
                            <h3>Categorias</h3>
                            <p>Replica categorias Moodle y su jerarquia en WooCommerce.</p>
                            <div class="pcc-stage-status" data-stage-status="categories">Pendiente</div>
                        </article>
                        <article class="pcc-stage-card" data-stage-card="courses">
                            <div class="pcc-stage-step">Etapa 2</div>
                            <h3>Cursos</h3>
                            <p>Crea o actualiza productos con datos comerciales y academicos.</p>
                            <div class="pcc-stage-status" data-stage-status="courses">Pendiente</div>
                        </article>
                    </div>

                    <div class="pcc-sync-actions">
                        <button type="button" class="button button-secondary" data-sync-start>Ejecutar sincronizacion guiada</button>
                    </div>

                    <div class="pcc-sync-result" data-sync-result>
                        <p><strong>Mensaje:</strong> Sin ejecuciones en esta sesion.</p>
                    </div>
                </div>

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
            </section>

            <section class="pcc-tab-panel <?php echo $active_tab === 'sso' ? 'is-active' : ''; ?>" id="pcc-panel-sso" data-panel="sso" role="tabpanel" aria-labelledby="pcc-tab-sso">
                <h3>SSO</h3>
                <p class="description">Controla el acceso directo a Moodle y el comportamiento despues de la compra.</p>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="pcc_woootec_pro_sso_enabled">Activar SSO</label></th>
                        <td>
                            <input type="checkbox" id="pcc_woootec_pro_sso_enabled" name="pcc_woootec_pro_sso_enabled" value="yes" <?php checked($core->get_option('sso_enabled', 'yes'), 'yes'); ?>>
                            <p class="pcc-field-help">Permite acceso directo al aula sin login manual adicional.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pcc_woootec_pro_sso_base_url">URL base SSO</label></th>
                        <td>
                            <input class="regular-text" type="url" id="pcc_woootec_pro_sso_base_url" name="pcc_woootec_pro_sso_base_url" value="<?php echo esc_attr((string) $core->get_option('sso_base_url', '')); ?>">
                            <p class="pcc-field-help">Endpoint base usado para construir los links de acceso a Moodle.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pcc_woootec_pro_redirect_after_purchase">Redirigir tras compra</label></th>
                        <td>
                            <input type="checkbox" id="pcc_woootec_pro_redirect_after_purchase" name="pcc_woootec_pro_redirect_after_purchase" value="yes" <?php checked($core->get_option('redirect_after_purchase', 'no'), 'yes'); ?>>
                            <p class="pcc-field-help">Envia al alumno directo al aula cuando la compra termina correctamente.</p>
                        </td>
                    </tr>
                </table>
            </section>

            <section class="pcc-tab-panel <?php echo $active_tab === 'emails' ? 'is-active' : ''; ?>" id="pcc-panel-emails" data-panel="emails" role="tabpanel" aria-labelledby="pcc-tab-emails">
                <h3>Emails</h3>
                <p class="description">Plantilla HTML real, variables dinamicas, remitente personalizado y herramientas de prueba.</p>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="pcc_woootec_pro_email_enabled">Activar envio</label></th>
                        <td>
                            <input type="checkbox" id="pcc_woootec_pro_email_enabled" name="pcc_woootec_pro_email_enabled" value="yes" <?php checked($core->get_option('email_enabled', 'yes'), 'yes'); ?>>
                            <p class="pcc-field-help">Envia automaticamente el correo de acceso al completar la compra y la matricula.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pcc_woootec_pro_email_from_address">Email remitente</label></th>
                        <td>
                            <input class="regular-text" type="email" id="pcc_woootec_pro_email_from_address" name="pcc_woootec_pro_email_from_address" value="<?php echo esc_attr($email_from_address); ?>" placeholder="<?php echo esc_attr($fallback_from_address); ?>">
                            <p class="pcc-field-help">Si lo dejas vacio se usa `<?php echo esc_html($fallback_from_address); ?>` en lugar de `WordPress`.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pcc_woootec_pro_email_from_name">Nombre remitente</label></th>
                        <td>
                            <input class="regular-text" type="text" id="pcc_woootec_pro_email_from_name" name="pcc_woootec_pro_email_from_name" value="<?php echo esc_attr((string) $core->get_option('email_from_name', 'Plataforma de Cursos')); ?>">
                            <p class="pcc-field-help">Ejemplo: `Plataforma de Cursos` o el nombre comercial de tu OTEC.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pcc_woootec_pro_email_subject">Asunto</label></th>
                        <td>
                            <input class="regular-text" type="text" id="pcc_woootec_pro_email_subject" name="pcc_woootec_pro_email_subject" value="<?php echo esc_attr((string) $core->get_option('email_subject', '')); ?>">
                            <p class="pcc-field-help">Puedes usar variables dinamicas como `{{nombre}}` y `{{sitio}}`.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pcc_woootec_pro_email_template">Plantilla HTML</label></th>
                        <td>
                            <textarea class="large-text code pcc-email-template-field" rows="18" id="pcc_woootec_pro_email_template" name="pcc_woootec_pro_email_template"><?php echo esc_textarea((string) $core->get_option('email_template', '')); ?></textarea>
                            <p class="pcc-field-help">El sistema decodifica entidades HTML antes de enviar, reemplaza variables y fuerza `Content-Type: text/html; charset=UTF-8`.</p>
                            <p class="description">Variables disponibles: {{nombre}}, {{email}}, {{password}}, {{cursos}}, {{url_acceso}}, {{sitio}}</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pcc_woootec_pro_email_test_recipient">Correo de prueba</label></th>
                        <td>
                            <input class="regular-text" type="email" id="pcc_woootec_pro_email_test_recipient" name="pcc_woootec_pro_email_test_recipient" value="<?php echo esc_attr((string) $core->get_option('email_test_recipient', '')); ?>">
                            <p class="pcc-field-help">Usa un correo real para validar render, remitente y entregabilidad basica.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pcc_woootec_pro_retry_limit">Reintentos de matricula</label></th>
                        <td>
                            <input class="small-text" type="number" min="1" max="10" id="pcc_woootec_pro_retry_limit" name="pcc_woootec_pro_retry_limit" value="<?php echo esc_attr((string) $core->get_option('retry_limit', 3)); ?>">
                            <p class="pcc-field-help">Cantidad maxima de reintentos antes de marcar una matricula como fallida.</p>
                        </td>
                    </tr>
                </table>

                <div class="pcc-email-callout">
                    <strong>Mejora futura recomendada:</strong> reemplazar el envio de contrasena por enlace de activacion con token y expiracion.
                </div>

                <div class="pcc-email-tools">
                    <button type="button" class="button" data-email-preview>Vista previa</button>
                    <button type="button" class="button button-secondary" data-email-send-test>Enviar prueba</button>
                </div>
                <div class="pcc-email-feedback" data-email-feedback></div>
                <div class="pcc-email-preview" data-email-preview-box></div>
            </section>

            <section class="pcc-tab-panel <?php echo $active_tab === 'licenses' ? 'is-active' : ''; ?>" id="pcc-panel-licenses" data-panel="licenses" role="tabpanel" aria-labelledby="pcc-tab-licenses">
                <h3>Licencias</h3>
                <p class="description">Espacio preparado para el futuro modulo de licenciamiento y distribucion controlada del plugin.</p>

                <div class="pcc-update-callout <?php echo !empty($update_available) ? 'has-update' : ''; ?>">
                    <p><strong>Estado del modulo:</strong> preparado para integrar claves, activacion remota y planes SaaS en una siguiente etapa.</p>
                    <p><strong>Version instalada:</strong> <?php echo esc_html(PCC_WOOOTEC_PRO_VERSION); ?></p>
                    <p><strong>Actualizacion:</strong> <?php echo !empty($update_available) ? 'Hay una release nueva detectada.' : 'Sin nuevas releases.'; ?></p>
                    <?php if (!empty($release['version'])) : ?>
                        <p><strong>Ultima version detectada:</strong> <?php echo esc_html((string) $release['version']); ?></p>
                    <?php endif; ?>
                </div>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="pcc_woootec_pro_github_repo">Repositorio</label></th>
                        <td>
                            <input class="regular-text" type="text" id="pcc_woootec_pro_github_repo" name="pcc_woootec_pro_github_repo" value="<?php echo esc_attr((string) $core->get_option('github_repo', '')); ?>" placeholder="owner/repo">
                            <p class="pcc-field-help">Base de distribucion actual del plugin, por ejemplo `jcares/PCC-WooOTEC-Chile`.</p>
                            <p class="description"><a href="<?php echo esc_url($release_page_url); ?>" target="_blank" rel="noopener noreferrer">Abrir releases</a></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pcc_woootec_pro_github_release_url">release.json URL</label></th>
                        <td>
                            <input class="regular-text" type="url" id="pcc_woootec_pro_github_release_url" name="pcc_woootec_pro_github_release_url" value="<?php echo esc_attr((string) $core->get_option('github_release_url', $release_json_url)); ?>">
                            <p class="pcc-field-help">Fuente actual de metadata de version, reutilizable luego para validacion de licencias y upgrades.</p>
                            <p class="description"><a href="<?php echo esc_url($release_json_url); ?>" target="_blank" rel="noopener noreferrer">Ver release.json</a></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pcc_woootec_pro_auto_update">Auto update</label></th>
                        <td>
                            <input type="checkbox" id="pcc_woootec_pro_auto_update" name="pcc_woootec_pro_auto_update" value="yes" <?php checked($core->get_option('auto_update', 'no'), 'yes'); ?>>
                            <p class="pcc-field-help">Permite automatizar instalaciones futuras cuando el flujo de releases este validado.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pcc_woootec_pro_debug_enabled">Modo debug</label></th>
                        <td>
                            <input type="checkbox" id="pcc_woootec_pro_debug_enabled" name="pcc_woootec_pro_debug_enabled" value="yes" <?php checked($core->get_option('debug_enabled', 'no'), 'yes'); ?>>
                            <p class="pcc-field-help">Mantiene trazabilidad tecnica para soporte y futuras funciones de licenciamiento.</p>
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
