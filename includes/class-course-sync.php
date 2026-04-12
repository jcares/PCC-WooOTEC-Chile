<?php
/**
 * Lógica de sincronización de cursos y categorías entre Moodle y WooCommerce.
 * 
 * @package    Woo_OTEC_Moodle
 */

namespace Woo_OTEC_Moodle;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Course_Sync {

	/**
	 * Cliente de la API de Moodle.
	 */
	private $api_client;

	/**
	 * Gestor de Logs.
	 */
	private $logger;

	/**
	 * Constructor.
	 */
	public function __construct( $api_client, $logger ) {
		$this->api_client = $api_client;
		$this->logger     = $logger;

		// AJAX para sincronización manual
		add_action( 'wp_ajax_woo_otec_sync_courses', array( $this, 'ajax_sync_courses' ) );
		add_action( 'wp_ajax_wom_set_product_image', array( $this, 'ajax_set_product_image' ) );
		add_action( 'wp_ajax_wom_sync_courses', array( $this, 'ajax_sync_courses_with_template' ) );
		
		// Añadir campo de ID de Moodle en la edición del producto
		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'add_moodle_id_field' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'save_moodle_id_field' ) );
	}

	/**
	 * AJAX Handler para sincronizar categorías y cursos (MEJORADO).
	 */
	public function ajax_sync_courses() {
		check_ajax_referer( 'woo-otec-moodle-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'No autorizado' );
		}

		// Iniciar sincronización
		$this->logger->log( 'INFO', 'Iniciando sincronización de cursos...' );
		
		// 1. Sincronizar Categorías (Fail-safe)
		$cat_stats = $this->sync_categories();

		// 2. Sincronizar Cursos
		$courses = $this->api_client->get_courses();

		if ( is_wp_error( $courses ) ) {
			$this->logger->log( 'ERROR', 'Error al obtener cursos: ' . $courses->get_error_message() );
			wp_send_json_error( $courses->get_error_message() );
		}

		$created = 0;
		$updated = 0;
		$errors  = 0;

		foreach ( $courses as $course ) {
			if ( 1 === (int) $course['id'] ) {
				continue; // Saltar el curso "Sitio" por defecto de Moodle
			}

			try {
				// Validar que el curso tenga datos mínimos
				if ( empty( $course['fullname'] ) || empty( $course['id'] ) ) {
					$errors++;
					continue;
				}

				// Buscar producto existente por ID de Moodle
				$product_id = $this->get_product_by_moodle_id( $course['id'] );

				if ( $product_id ) {
					$this->update_product( $product_id, $course );
					$updated++;
					$this->logger->log( 'SUCCESS', "Curso actualizado: {$course['fullname']} (ID: {$course['id']})" );
				} else {
					$product_id = $this->create_product( $course );
					if ( $product_id ) {
						$created++;
						$this->logger->log( 'SUCCESS', "Curso creado: {$course['fullname']} (ID: {$course['id']})" );
					} else {
						$errors++;
						$this->logger->log( 'ERROR', "No se pudo crear el curso: {$course['fullname']}" );
					}
				}

				// Asignar categoría al producto
				if ( $product_id && ! empty( $course['categoryid'] ) ) {
					$this->assign_category_to_product( $product_id, $course['categoryid'] );
				}
			} catch ( Exception $e ) {
				$errors++;
				$this->logger->log( 'ERROR', "Excepción en sincronización: " . $e->getMessage() );
			}
		}

		$summary = sprintf( 
			'Sincronización completada exitosamente. Categorías: %d procesadas. Cursos: %d creados, %d actualizados. Errores: %d',
			$cat_stats['total'], 
			$created, 
			$updated,
			$errors
		);

		$this->logger->log( 'INFO', $summary );
		wp_send_json_success( $summary );
	}

	/**
	 * Sincronizar categorías de Moodle con WooCommerce.
	 */
	public function sync_categories() {
		$stats = array( 'total' => 0, 'errors' => 0 );
		$categories = $this->api_client->get_categories();

		if ( is_wp_error( $categories ) ) {
			// Fail-safe: No detenemos el proceso si las categorías fallan
			return $stats;
		}

		foreach ( $categories as $cat ) {
			$this->get_or_create_category( $cat['id'], $cat['name'], $cat['parent'] );
			$stats['total']++;
		}

		return $stats;
	}

	/**
	 * Obtener o crear una categoría de WooCommerce mapeada con Moodle.
	 */
	private function get_or_create_category( $moodle_id, $name, $parent_moodle_id = 0 ) {
		$taxonomy = 'product_cat';
		
		// Buscar por meta ID de Moodle
		$terms = get_terms( array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
			'meta_query' => array(
				array(
					'key'   => '_moodle_category_id',
					'value' => $moodle_id,
				),
			),
		) );

		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
			return $terms[0]->term_id;
		}

		// Si no existe, crearla
		$parent_term_id = 0;
		if ( $parent_moodle_id ) {
			// Intento recursivo para el padre (limitado para evitar bucles si la API está mal)
			$parent_term_id = $this->get_or_create_category( $parent_moodle_id, 'Categoría Padre' );
		}

		$result = wp_insert_term( $name, $taxonomy, array(
			'parent' => $parent_term_id,
		) );

		if ( is_wp_error( $result ) ) {
			// Si el error es que ya existe el nombre, intentamos buscarla por nombre
			$term = get_term_by( 'name', $name, $taxonomy );
			if ( $term ) {
				$term_id = $term->term_id;
				update_term_meta( $term_id, '_moodle_category_id', $moodle_id );
				return $term_id;
			}
			return 0;
		}

		$term_id = $result['term_id'];
		update_term_meta( $term_id, '_moodle_category_id', $moodle_id );

		return $term_id;
	}

	/**
	 * Asignar categoría a un producto basado en el ID de Moodle.
	 */
	private function assign_category_to_product( $product_id, $moodle_cat_id ) {
		$term_id = $this->get_or_create_category( $moodle_cat_id, 'Moodle Category' );
		if ( $term_id ) {
			wp_set_object_terms( $product_id, array( (int) $term_id ), 'product_cat' );
		}
	}

	/**
	 * Obtener el ID del producto de WooCommerce mediante el ID de curso de Moodle.
	 */
	private function get_product_by_moodle_id( $moodle_id ) {
		$args = array(
			'post_type'  => 'product',
			'meta_query' => array(
				array(
					'key'   => '_moodle_course_id',
					'value' => $moodle_id,
				),
			),
			'posts_per_page' => 1,
			'fields'         => 'ids',
		);
		$products = get_posts( $args );
		return ! empty( $products ) ? $products[0] : false;
	}

	/**
	 * Crear un nuevo producto de WooCommerce a partir de un curso de Moodle.
	 */
	private function create_product( $course ) {
		$post_id = wp_insert_post( array(
			'post_title'   => $course['fullname'],
			'post_content' => $course['summary'],
			'post_status'  => 'publish',
			'post_type'    => 'product',
		) );

		if ( $post_id ) {
			update_post_meta( $post_id, '_moodle_course_id', $course['id'] );
			update_post_meta( $post_id, '_virtual', 'yes' );
			update_post_meta( $post_id, '_downloadable', 'no' );
			update_post_meta( $post_id, '_regular_price', '0' );
			update_post_meta( $post_id, '_price', '0' );
			update_post_meta( $post_id, '_stock_status', 'instock' );
			update_post_meta( $post_id, '_visibility', 'visible' );
			
			wp_set_object_terms( $post_id, 'simple', 'product_type' );
		}
		
		return $post_id;
	}

	/**
	 * Actualizar un producto existente con los datos de Moodle.
	 */
	private function update_product( $product_id, $course ) {
		$post_data = array(
			'ID'           => $product_id,
			'post_title'   => $course['fullname'],
			'post_content' => $course['summary'],
		);
		wp_update_post( $post_data );
		update_post_meta( $product_id, '_moodle_course_id', $course['id'] );
	}

	/**
	 * Añadir campo de Moodle ID en la interfaz de WooCommerce.
	 */
	public function add_moodle_id_field() {
		woocommerce_wp_text_input( array(
			'id'          => '_moodle_course_id',
			'label'       => __( 'ID de Curso Moodle', 'woo-otec-moodle' ),
			'description' => __( 'Este ID vincula este producto con el curso de Moodle.', 'woo-otec-moodle' ),
			'desc_tip'    => true,
			'type'        => 'number',
		) );
	}

	/**
	 * Guardar el campo de Moodle ID.
	 */
	public function save_moodle_id_field( $post_id ) {
		$moodle_id = isset( $_POST['_moodle_course_id'] ) ? sanitize_text_field( $_POST['_moodle_course_id'] ) : '';
		update_post_meta( $post_id, '_moodle_course_id', $moodle_id );
	}

	/**
	 * AJAX Handler para establecer imagen de producto
	 */
	public function ajax_set_product_image() {
		check_ajax_referer( 'woo-otec-moodle-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'No autorizado' );
		}

		$product_id = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : 0;
		$attachment_id = isset( $_POST['attachment_id'] ) ? intval( $_POST['attachment_id'] ) : 0;

		if ( ! $product_id || ! $attachment_id ) {
			wp_send_json_error( 'Parámetros requeridos faltantes' );
		}

		set_post_thumbnail( $product_id, $attachment_id );
		$this->logger->log( 'SUCCESS', "Imagen establecida para producto ID: $product_id" );

		wp_send_json_success( array(
			'message' => 'Imagen actualizada correctamente',
			'attachment_id' => $attachment_id,
		) );
	}

	/**
	 * AJAX Handler para sincronizar cursos aplicando template settings
	 */
	public function ajax_sync_courses_with_template() {
		check_ajax_referer( 'woo-otec-moodle-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'No autorizado' );
		}

		$apply_template = isset( $_POST['apply_template'] ) ? rest_sanitize_boolean( $_POST['apply_template'] ) : false;

		// Ejecutar sincronización normal
		$courses = $this->api_client->get_courses();

		if ( is_wp_error( $courses ) ) {
			$this->logger->log( 'ERROR', 'Error al obtener cursos: ' . $courses->get_error_message() );
			wp_send_json_error( $courses->get_error_message() );
		}

		$synced = 0;
		$applied = 0;

		foreach ( $courses as $course ) {
			if ( 1 === (int) $course['id'] ) {
				continue;
			}

			$product_id = $this->get_product_by_moodle_id( $course['id'] );
			if ( $product_id ) {
				$synced++;

				// Si se solicita aplicar template, guardar metatags
				if ( $apply_template ) {
					$this->apply_template_metatags( $product_id, $course );
					$applied++;
				}
			}
		}

		$this->logger->log_sync( "Sincronización completada: $synced cursos, $applied con template aplicado" );

		wp_send_json_success( array(
			'message' => "Sincronización completada: $synced cursos actualizados",
			'synced' => $synced,
			'applied' => $applied,
		) );
	}

	/**
	 * Aplicar metatags de template a un producto
	 *
	 * @param int $product_id ID del producto
	 * @param array $course Datos del curso de Moodle
	 */
	private function apply_template_metatags( $product_id, $course ) {
		// Guardar información del curso como metatags
		update_post_meta( $product_id, '_moodle_course_name', sanitize_text_field( $course['fullname'] ) );
		update_post_meta( $product_id, '_moodle_course_summary', wp_kses_post( $course['summary'] ) );

		if ( ! empty( $course['categoryid'] ) ) {
			update_post_meta( $product_id, '_moodle_course_category_id', intval( $course['categoryid'] ) );
		}

		// Marcar que tiene datos de template aplicados
		update_post_meta( $product_id, '_moodle_template_applied', '1' );
	}
}
