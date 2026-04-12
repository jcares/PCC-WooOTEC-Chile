<?php
/**
 * Gestor de Metadatos de Moodle - Extracción y sincronización
 * 
 * Responsabilidades:
 * - Conectar con API de Moodle
 * - Extraer metadatos de cursos
 * - Normalizar datos
 * - Gestionar caché local
 * 
 * @package    Woo_OTEC_Moodle
 */

namespace Woo_OTEC_Moodle;

use \WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Metadata_Manager {

	/**
	 * Cliente API de Moodle
	 */
	private $api_client;

	/**
	 * Gestor de logs
	 */
	private $logger;

	/**
	 * Constructor
	 */
	public function __construct( $api_client, $logger ) {
		$this->api_client = $api_client;
		$this->logger     = $logger;

		// AJAX para obtener metadatos disponibles
		add_action( 'wp_ajax_wom_fetch_metadata', array( $this, 'ajax_fetch_metadata' ) );
		
		// AJAX para guardar configuración de campos
		add_action( 'wp_ajax_wom_save_metadata_config', array( $this, 'ajax_save_metadata_config' ) );
		
		// AJAX para resetear configuración
		add_action( 'wp_ajax_wom_reset_metadata_config', array( $this, 'ajax_reset_metadata_config' ) );
	}

	/**
	 * Obtener estructura de metadatos disponibles desde Moodle
	 * Retorna todos los campos disponibles con sus definiciones
	 * 
	 * @return array Estructura de metadatos o error
	 */
	public function get_available_metadata() {
		// Intentar obtener de caché primero
		$cached = get_transient( 'wom_available_metadata' );
		if ( false !== $cached ) {
			return $cached;
		}

		// Estructura base de metadatos esperados de Moodle y WooCommerce
		$metadata_structure = array(
			// Metadatos de Moodle
			'id'                      => array(
				'label'       => 'ID del Curso',
				'type'        => 'number',
				'description' => 'Identificador único en Moodle',
				'editable'    => false,
			),
			'fullname'                => array(
				'label'       => 'Nombre del Curso',
				'type'        => 'text',
				'description' => 'Nombre completo del curso',
				'editable'    => false,
			),
			'shortname'               => array(
				'label'       => 'Nombre Corto del Curso',
				'type'        => 'text',
				'description' => 'Código o nombre abreviado',
				'editable'    => false,
			),
			'summary'                 => array(
				'label'       => 'Descripción',
				'type'        => 'textarea',
				'description' => 'Descripción completa del curso',
				'editable'    => false,
			),
			'categoryname'            => array(
				'label'       => 'Categoría del Curso',
				'type'        => 'text',
				'description' => 'Categoría a la que pertenece',
				'editable'    => false,
			),
			'startdate'               => array(
				'label'       => 'Fecha en que Comienza el Curso',
				'type'        => 'date',
				'description' => 'Fecha de inicio del curso',
				'editable'    => false,
			),
			'enddate'                 => array(
				'label'       => 'Fecha en que Finaliza el Curso',
				'type'        => 'date',
				'description' => 'Fecha de término del curso',
				'editable'    => false,
			),
			'visible'                 => array(
				'label'       => 'Estado de Publicación en Moodle',
				'type'        => 'boolean',
				'description' => 'Si el curso está publicado',
				'editable'    => false,
			),
			'enrolledusers'           => array(
				'label'       => 'Alumnos Matriculados',
				'type'        => 'number',
				'description' => 'Cantidad de usuarios matriculados',
				'editable'    => false,
			),
			'teacher'                 => array(
				'label'       => 'Profesor o Instructor',
				'type'        => 'text',
				'description' => 'Nombre del docente responsable',
				'editable'    => false,
			),
			'contact'                 => array(
				'label'       => 'Contacto del Profesor',
				'type'        => 'text',
				'description' => 'Email o teléfono del instructor',
				'editable'    => false,
			),
			// Metadatos de WooCommerce Producto
			'current_price'           => array(
				'label'       => 'Precio Actual',
				'type'        => 'price',
				'description' => 'Precio con descuento aplicado',
				'editable'    => false,
			),
			'regular_price'           => array(
				'label'       => 'Precio Normal (sin descuento)',
				'type'        => 'price',
				'description' => 'Precio original sin descuentos',
				'editable'    => false,
			),
			'stock_quantity'          => array(
				'label'       => 'Cupos Disponibles',
				'type'        => 'number',
				'description' => 'Cantidad de unidades disponibles',
				'editable'    => false,
			),
			'is_in_stock'             => array(
				'label'       => 'Disponibilidad',
				'type'        => 'boolean',
				'description' => 'Si el producto está en stock',
				'editable'    => false,
			),
			'is_virtual'              => array(
				'label'       => 'Curso en Modalidad Online',
				'type'        => 'boolean',
				'description' => 'Si es un producto virtual',
				'editable'    => false,
			),
			'product_code'            => array(
				'label'       => 'Código del Producto',
				'type'        => 'text',
				'description' => 'SKU del producto',
				'editable'    => false,
			),
			'product_image'           => array(
				'label'       => 'Portada del Curso',
				'type'        => 'image',
				'description' => 'Imagen destacada del producto',
				'editable'    => false,
			),
			'tax_class'               => array(
				'label'       => 'Tipo de Impuesto',
				'type'        => 'text',
				'description' => 'Clasificación fiscal del producto',
				'editable'    => false,
			),
			'tax_status'              => array(
				'label'       => 'Aplicación de Impuestos',
				'type'        => 'text',
				'description' => 'Estado de aplicabilidad de impuestos',
				'editable'    => false,
			),
		);

		// Intentar obtener datos reales de Moodle para verificar disponibilidad
		$test_courses = $this->get_courses_from_moodle( 1 );
		
		if ( ! is_wp_error( $test_courses ) && ! empty( $test_courses ) ) {
			$first_course = $test_courses[0];
			
			// Detectar campos personalizados si existen
			if ( ! empty( $first_course['customfields'] ) ) {
				foreach ( $first_course['customfields'] as $field ) {
					$field_key = 'custom_' . sanitize_key( $field['shortname'] );
					$metadata_structure[ $field_key ] = array(
						'label'       => $field['name'],
						'type'        => 'custom',
						'description' => 'Campo personalizado de Moodle',
						'editable'    => false,
					);
				}
			}
		}

		// Cachear por 24 horas
		set_transient( 'wom_available_metadata', $metadata_structure, DAY_IN_SECONDS );
		
		$this->logger->log( 'INFO', 'Metadatos disponibles generados y cacheados' );
		
		return $metadata_structure;
	}

	/**
	 * Obtener cursos desde Moodle con todos sus datos
	 * 
	 * @param int $limit Cantidad de cursos a obtener
	 * @return array|WP_Error Cursos con datos normalizados
	 */
	private function get_courses_from_moodle( $limit = 50 ) {
		$courses = $this->api_client->get_courses();
		
		if ( is_wp_error( $courses ) ) {
			$this->logger->log( 'ERROR', 'Error obteniendo cursos de Moodle: ' . $courses->get_error_message() );
			return $courses;
		}

		// Procesar y normalizar datos
		$normalized = array();
		$count = 0;

		foreach ( $courses as $course ) {
			if ( $count >= $limit ) {
				break;
			}

			// Saltar curso por defecto
			if ( 1 === (int) $course['id'] ) {
				continue;
			}

			$normalized[] = $this->normalize_course_data( $course );
			$count++;
		}

		return $normalized;
	}

	/**
	 * Normalizar datos de un curso de Moodle
	 * Asegura que todos los campos esperados existan
	 * 
	 * @param array $course Datos del curso desde Moodle
	 * @return array Datos normalizados
	 */
	private function normalize_course_data( $course ) {
		return array(
			'id'             => isset( $course['id'] ) ? (int) $course['id'] : 0,
			'fullname'       => isset( $course['fullname'] ) ? sanitize_text_field( $course['fullname'] ) : '',
			'shortname'      => isset( $course['shortname'] ) ? sanitize_text_field( $course['shortname'] ) : '',
			'summary'        => isset( $course['summary'] ) ? wp_kses_post( $course['summary'] ) : '',
			'categoryname'   => isset( $course['categoryname'] ) ? sanitize_text_field( $course['categoryname'] ) : '',
			'startdate'      => isset( $course['startdate'] ) ? (int) $course['startdate'] : 0,
			'enddate'        => isset( $course['enddate'] ) ? (int) $course['enddate'] : 0,
			'visible'        => isset( $course['visible'] ) ? (bool) $course['visible'] : true,
			'enrolledusers'  => isset( $course['enrolledusers'] ) ? (int) $course['enrolledusers'] : 0,
			'customfields'   => isset( $course['customfields'] ) ? $course['customfields'] : array(),
		);
	}

	/**
	 * Obtener y cachear todos los cursos con sus datos completos
	 * 
	 * @return array|WP_Error Cursos completos
	 */
	public function sync_courses_data() {
		$courses = $this->get_courses_from_moodle( 999 );
		
		if ( is_wp_error( $courses ) ) {
			return $courses;
		}

		// Guardar en transient por 6 horas
		set_transient( 'wom_cached_courses', $courses, 6 * HOUR_IN_SECONDS );
		
		$this->logger->log( 'SUCCESS', 'Sincronización de cursos completada: ' . count( $courses ) . ' cursos' );
		
		return $courses;
	}

	/**
	 * Obtener cursos desde caché o sincronizar
	 * 
	 * @return array|WP_Error
	 */
	public function get_cached_courses() {
		$cached = get_transient( 'wom_cached_courses' );
		
		if ( false === $cached ) {
			return $this->sync_courses_data();
		}

		return $cached;
	}

	/**
	 * AJAX: Obtener metadatos disponibles
	 */
	public function ajax_fetch_metadata() {
		check_ajax_referer( 'woo-otec-moodle-nonce', 'nonce' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'No autorizado' );
		}

		$metadata = $this->get_available_metadata();
		wp_send_json_success( $metadata );
	}

	/**
	 * AJAX: Guardar configuración de campos seleccionados
	 */
	public function ajax_save_metadata_config() {
		check_ajax_referer( 'woo-otec-moodle-nonce', 'nonce' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'No autorizado' );
		}

		$fields = isset( $_POST['fields'] ) ? array_map( 'sanitize_text_field', $_POST['fields'] ) : array();
		
		if ( empty( $fields ) ) {
			wp_send_json_error( 'Debes seleccionar al menos un campo' );
		}

		// Guardar configuración
		update_option( 'wom_metadata_config', $fields );
		
		$this->logger->log( 'SUCCESS', 'Configuración de metadatos guardada: ' . count( $fields ) . ' campos' );
		
		wp_send_json_success( 'Configuración guardada correctamente' );
	}

	/**
	 * AJAX: Resetear configuración a valores por defecto
	 */
	public function ajax_reset_metadata_config() {
		check_ajax_referer( 'woo-otec-moodle-nonce', 'nonce' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'No autorizado' );
		}

		// Eliminar opción para que use valores por defecto
		delete_option( 'wom_metadata_config' );
		
		// Limpiar caché de metadatos
		delete_transient( 'wom_available_metadata' );
		
		$this->logger->log( 'SUCCESS', 'Configuración de metadatos restablecida' );
		
		wp_send_json_success( 'Configuración restablecida a valores por defecto' );
	}

	/**
	 * Obtener campos seleccionados en la configuración
	 * 
	 * @return array
	 */
	public function get_configured_fields() {
		$configured = get_option( 'wom_metadata_config', array() );
		
		if ( empty( $configured ) ) {
			// Si no hay configuración, retornar campos por defecto
			return array( 'fullname', 'summary', 'categoryname', 'startdate' );
		}

		return $configured;
	}
}
