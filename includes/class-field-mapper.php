<?php
/**
 * Gestor de Mapeo de Campos (Field Mapper)
 * 
 * Mapea campos de Moodle a WooCommerce meta keys
 * Permite sincronización flexible de datos
 *
 * @package    Woo_OTEC_Moodle
 * @version    3.0.7
 */

namespace Woo_OTEC_Moodle;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Field_Mapper {

	/**
	 * Opción para almacenar mapeos
	 */
	const MAPPINGS_OPTION = 'woo_otec_moodle_field_mappings';

	/**
	 * Mapeos por defecto
	 */
	const DEFAULT_MAPPINGS = array(
		'fullname'      => array(
			'wc_key'      => 'post_title',
			'label'       => 'Nombre Completo del Curso',
			'description' => 'Título del producto en WooCommerce',
			'type'        => 'string',
			'enabled'     => true,
		),
		'shortname'     => array(
			'wc_key'      => '_short_name',
			'label'       => 'Nombre Corto',
			'description' => 'Identificador corto del curso',
			'type'        => 'string',
			'enabled'     => true,
		),
		'summary'       => array(
			'wc_key'      => 'post_content',
			'label'       => 'Descripción',
			'description' => 'Descripción completa del producto',
			'type'        => 'text',
			'enabled'     => true,
		),
		'startdate'     => array(
			'wc_key'      => '_start_date',
			'label'       => 'Fecha de Inicio',
			'description' => 'Fecha de inicio del curso',
			'type'        => 'date',
			'enabled'     => true,
		),
		'enddate'       => array(
			'wc_key'      => '_end_date',
			'label'       => 'Fecha de Fin',
			'description' => 'Fecha de finalización del curso',
			'type'        => 'date',
			'enabled'     => true,
		),
		'teacher'       => array(
			'wc_key'      => '_instructor',
			'label'       => 'Profesor/Instructor',
			'description' => 'Nombre del instructor del curso',
			'type'        => 'string',
			'enabled'     => true,
		),
		'duration'      => array(
			'wc_key'      => '_duration',
			'label'       => 'Duración (horas)',
			'description' => 'Horas totales del curso',
			'type'        => 'number',
			'enabled'     => true,
		),
		'modality'      => array(
			'wc_key'      => '_modality',
			'label'       => 'Modalidad',
			'description' => 'Online, Presencial, Híbrida, etc.',
			'type'        => 'string',
			'enabled'     => true,
		),
		'sence_code'    => array(
			'wc_key'      => '_sence_code',
			'label'       => 'Código SENCE (Chile)',
			'description' => 'Código regulatorio SENCE',
			'type'        => 'string',
			'enabled'     => true,
		),
		'total_hours'   => array(
			'wc_key'      => '_total_hours',
			'label'       => 'Total de Horas SENCE',
			'description' => 'Horas reportables a SENCE',
			'type'        => 'number',
			'enabled'     => true,
		),
	);

	/**
	 * Obtener todos los mapeos disponibles
	 */
	public static function get_all_mappings() {
		$mappings = get_option( self::MAPPINGS_OPTION, self::DEFAULT_MAPPINGS );
		return wp_parse_args( $mappings, self::DEFAULT_MAPPINGS );
	}

	/**
	 * Obtener mapeos habilitados
	 */
	public static function get_enabled_mappings() {
		$all_mappings = self::get_all_mappings();
		$enabled = array();

		foreach ( $all_mappings as $moodle_field => $config ) {
			if ( isset( $config['enabled'] ) && $config['enabled'] ) {
				$enabled[ $moodle_field ] = $config;
			}
		}

		return $enabled;
	}

	/**
	 * Actualizar mapeo de un campo
	 */
	public static function update_field_mapping( $moodle_field, $wc_key, $enabled = true ) {
		$mappings = self::get_all_mappings();

		if ( ! isset( $mappings[ $moodle_field ] ) ) {
			return new \WP_Error( 'unknown_field', "Campo Moodle '{$moodle_field}' no existe" );
		}

		$mappings[ $moodle_field ]['wc_key'] = sanitize_text_field( $wc_key );
		$mappings[ $moodle_field ]['enabled'] = (bool) $enabled;

		update_option( self::MAPPINGS_OPTION, $mappings );

		return true;
	}

	/**
	 * Deshabilitar mapeo de un campo
	 */
	public static function disable_field( $moodle_field ) {
		return self::update_field_mapping( $moodle_field, '', false );
	}

	/**
	 * Habilitar mapeo de un campo
	 */
	public static function enable_field( $moodle_field ) {
		$mappings = self::get_all_mappings();
		if ( ! isset( $mappings[ $moodle_field ] ) ) {
			return false;
		}

		$default_wc_key = $mappings[ $moodle_field ]['wc_key'];
		return self::update_field_mapping( $moodle_field, $default_wc_key, true );
	}

	/**
	 * Aplicar mapeo a un producto
	 * 
	 * @param int $product_id ID del producto WooCommerce
	 * @param array $moodle_data Datos del curso desde Moodle
	 */
	public static function apply_mapping_to_product( $product_id, $moodle_data ) {
		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return false;
		}

		$enabled_mappings = self::get_enabled_mappings();
		$updated = false;

		foreach ( $enabled_mappings as $moodle_field => $config ) {
			if ( ! isset( $moodle_data[ $moodle_field ] ) ) {
				continue;
			}

			$value = $moodle_data[ $moodle_field ];
			$wc_key = $config['wc_key'];

			// Determinar si es post data o meta data
			if ( in_array( $wc_key, array( 'post_title', 'post_content', 'post_excerpt' ) ) ) {
				// Actualizar post data
				$args = array(
					'ID' => $product_id,
					$wc_key => $value,
				);
				wp_update_post( $args );
			} else {
				// Actualizar meta data
				// Convertir timestamps si es fecha
				if ( $config['type'] === 'date' && is_numeric( $value ) ) {
					$value = date( 'Y-m-d', (int) $value );
				}

				// Convertir a número si es tipo number
				if ( $config['type'] === 'number' ) {
					$value = (float) $value;
				}

				$product->update_meta_data( $wc_key, $value );
			}

			$updated = true;
		}

		if ( $updated ) {
			$product->save();
		}

		return $updated;
	}

	/**
	 * Obtener datos aplicando mapeo inverso (producto -> datos Moodle)
	 * 
	 * @param int $product_id ID del producto
	 * @return array Datos mapeados
	 */
	public static function extract_mapped_data( $product_id ) {
		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return array();
		}

		$enabled_mappings = self::get_enabled_mappings();
		$data = array();

		foreach ( $enabled_mappings as $moodle_field => $config ) {
			$wc_key = $config['wc_key'];

			// Obtener valor de post o meta
			if ( in_array( $wc_key, array( 'post_title', 'post_content', 'post_excerpt' ) ) ) {
				if ( $wc_key === 'post_title' ) {
					$value = $product->get_name();
				} elseif ( $wc_key === 'post_content' ) {
					$value = $product->get_description();
				} else {
					$value = $product->get_short_description();
				}
			} else {
				$value = $product->get_meta( $wc_key );
			}

			if ( ! empty( $value ) ) {
				$data[ $moodle_field ] = $value;
			}
		}

		return $data;
	}

	/**
	 * Resetear mapeos a valores por defecto
	 */
	public static function reset_to_defaults() {
		update_option( self::MAPPINGS_OPTION, self::DEFAULT_MAPPINGS );
		return true;
	}

	/**
	 * Obtener estadísticas de mapeo
	 */
	public static function get_stats() {
		$all_mappings = self::get_all_mappings();
		$enabled = 0;

		foreach ( $all_mappings as $config ) {
			if ( ! isset( $config['enabled'] ) || $config['enabled'] ) {
				$enabled++;
			}
		}

		return array(
			'total'   => count( $all_mappings ),
			'enabled' => $enabled,
			'disabled' => count( $all_mappings ) - $enabled,
		);
	}

	/**
	 * Exportar mapeos como JSON
	 */
	public static function export_mappings() {
		$mappings = self::get_all_mappings();
		return json_encode( $mappings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
	}

	/**
	 * Importar mapeos desde JSON
	 */
	public static function import_mappings( $json_data ) {
		$mappings = json_decode( $json_data, true );
		
		if ( ! is_array( $mappings ) ) {
			return new \WP_Error( 'invalid_json', 'Datos JSON inválidos' );
		}

		update_option( self::MAPPINGS_OPTION, $mappings );
		return true;
	}
}
