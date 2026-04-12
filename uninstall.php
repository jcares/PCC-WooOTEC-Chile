<?php
/**
 * Desinstalación del plugin Woo OTEC Moodle
 * 
 * Este archivo se ejecuta cuando el plugin es desinstalado desde WordPress
 * Limpia todas las opciones y datos creados por el plugin
 *
 * @package    Woo_OTEC_Moodle
 * @version    3.0.7
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Opciones de configuración a eliminar
 */
$options_to_delete = array(
	// Configuración general
	'woo_otec_moodle_api_url',
	'woo_otec_moodle_api_token',
	'woo_otec_moodle_role_id',
	'woo_otec_moodle_auto_sync',

	// CRON
	'woo_otec_moodle_cron_interval_hours',

	// SSO
	'woo_otec_moodle_sso_enabled',
	'woo_otec_moodle_sso_base_url',
	'woo_otec_moodle_sso_token',

	// Field Mapper
	'woo_otec_moodle_field_mappings',

	// Metadatos
	'woo_otec_moodle_configured_fields',
	'woo_otec_moodle_custom_metadata',

	// Templating
	'wom_template_config',
	'wom_email_templates',
	'wom_template_customization',

	// Logs (datos transitorios)
	'woo_otec_moodle_last_sync',
	'woo_otec_moodle_sync_status',

	// Caché y transitorios
	'woo_otec_moodle_courses_cache',
	'woo_otec_moodle_auth_cache',
	'woo_otec_moodle_version',
);

/**
 * Eliminar opciones del sitio
 */
foreach ( $options_to_delete as $option ) {
	delete_option( $option );
}

/**
 * Si es multisite, eliminar opciones globales
 */
if ( is_multisite() ) {
	foreach ( $options_to_delete as $option ) {
		delete_site_option( $option );
	}
}

/**
 * Limpiar transitorios (caché temporal)
 */
delete_transient( 'woo_otec_moodle_courses' );
delete_transient( 'woo_otec_moodle_users' );
delete_transient( 'woo_otec_moodle_api_connection' );

/**
 * Limpiar post meta de productos
 * Eliminar metadata creada por el plugin en productos
 */
global $wpdb;

$product_meta_keys = array(
	'_wom_sso_login_urls',
	'_wom_course_sync_data',
	'_wom_enrollment_data',
	'_instructor',
	'_start_date',
	'_end_date',
	'_duration',
	'_modality',
	'_sence_code',
	'_total_hours',
	'_course_format',
);

foreach ( $product_meta_keys as $meta_key ) {
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM $wpdb->postmeta WHERE meta_key = %s AND post_id IN (SELECT ID FROM $wpdb->posts WHERE post_type = 'product')",
			$meta_key
		)
	);
}

/**
 * Limpiar post meta de custom post types
 */
$wpdb->query(
	"DELETE FROM $wpdb->postmeta WHERE meta_key LIKE '%wom_%' OR meta_key LIKE '%woo_otec_%'"
);

/**
 * Limpiar comentarios (si existen logs como comentarios)
 */
$wpdb->query(
	"DELETE FROM $wpdb->comments WHERE comment_type LIKE '%woo_otec%'"
);

/**
 * Limpiar tabla de logs personalizada (si existe)
 */
$logs_table = $wpdb->prefix . 'woo_otec_moodle_logs';
if ( $wpdb->get_var( "SHOW TABLES LIKE '$logs_table'" ) === $logs_table ) {
	$wpdb->query( "DROP TABLE $logs_table" );
}

/**
 * Desactivar eventos CRON
 */
wp_clear_scheduled_hook( 'woo_otec_moodle_sync_courses_cron' );
wp_clear_scheduled_hook( 'woo_otec_moodle_hourly_sync' );

/**
 * Log de desinstalación (opcional)
 * Se ejecuta solo si se desee guardar un registro de desinstalación
 */
error_log( '[WOO OTEC MOODLE] Plugin desinstalado en: ' . current_time( 'mysql' ) );

do_action( 'woo_otec_moodle_uninstalled' );
