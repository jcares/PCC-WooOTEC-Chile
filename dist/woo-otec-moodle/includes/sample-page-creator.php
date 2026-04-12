<?php
/**
 * Crear página de muestra con cursos de Moodle
 * Se ejecuta durante la activación del plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function wom_create_sample_page() {
	// Verificar si ya existe
	$existing = get_page_by_title( 'Cursos Disponibles' );
	if ( $existing ) {
		return;
	}

	$page_id = wp_insert_post(
		array(
			'post_title'    => 'Cursos Disponibles',
			'post_content'  => '[moodle_courses limit="12" columns="3"]',
			'post_status'   => 'publish',
			'post_type'     => 'page',
			'post_author'   => 1,
			'post_name'     => 'cursos-disponibles',
		)
	);

	if ( $page_id ) {
		update_post_meta( $page_id, '_wom_sample_page', true );
	}
}

// Ejecutar en activación
register_activation_hook( WOO_OTEC_MOODLE_FILE, 'wom_create_sample_page' );
