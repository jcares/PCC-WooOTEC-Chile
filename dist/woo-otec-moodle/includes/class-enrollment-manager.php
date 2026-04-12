<?php
/**
 * Gestión de matriculación de alumnos en Moodle tras la compra en WooCommerce.
 * 
 * @package    Woo_OTEC_Moodle
 */

namespace Woo_OTEC_Moodle;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Enrollment_Manager {

	/**
	 * Cliente de la API de Moodle.
	 */
	private $api_client;

	/**
	 * Gestor de Logs.
	 */
	private $logger;

	/**
	 * Gestor de Email.
	 */
	private $email_manager;

	/**
	 * Constructor.
	 */
	public function __construct( $api_client, $logger, $email_manager = null ) {
		$this->api_client    = $api_client;
		$this->logger        = $logger;
		$this->email_manager = $email_manager;

		// Enganchar a WooCommerce - Pedido completado
		add_action( 'woocommerce_order_status_completed', array( $this, 'process_order_enrollment' ) );
		
		// AJAX Handler para inscripción desde shortcodes
		add_action( 'wp_ajax_wom_enroll_product', array( $this, 'ajax_enroll_product' ) );
	}

	/**
	 * Procesar la matriculación al completarse un pedido.
	 */
	public function process_order_enrollment( $order_id ) {
		$order = wc_get_order( $order_id );
		$email = $order->get_billing_email();

		// 1. Verificar si el usuario ya existe en Moodle
		$moodle_user = $this->api_client->get_user_by_email( $email );
		$moodle_user_id = null;

		if ( ! $moodle_user ) {
			// 2. Crear usuario si no existe
			$moodle_user_id = $this->create_moodle_user( $order );
		} else {
			$moodle_user_id = $moodle_user['id'];
		}

		if ( ! $moodle_user_id ) {
			$this->logger->log( 'ERROR', "Pedido #{$order_id}: No se pudo obtener o crear el usuario en Moodle." );
			return;
		}

		// 3. Matricular en los cursos del pedido
		$items = $order->get_items();
		$role_id = get_option( 'woo_otec_moodle_role_id', '5' );
		$enrolled_courses = array();
		$temp_pass = get_post_meta( $order_id, '_moodle_temp_pass', true );

		foreach ( $items as $item ) {
			$product_id = $item->get_product_id();
			$moodle_course_id = get_post_meta( $product_id, '_moodle_course_id', true );

			if ( $moodle_course_id ) {
				$result = $this->api_client->enrol_user( $moodle_user_id, $moodle_course_id, $role_id );
				
				if ( ! is_wp_error( $result ) ) {
					$enrolled_courses[] = $item->get_name();
					$order->add_order_note( sprintf( 'Usuario matriculado con éxito en el curso de Moodle: %s (ID: %d).', $item->get_name(), $moodle_course_id ) );
					$this->logger->log( 'SUCCESS', "Pedido #{$order_id}: Usuario {$email} matriculado en curso {$moodle_course_id}" );
				} else {
					$this->logger->log( 'ERROR', "Pedido #{$order_id}: Fallo de matrícula en curso {$moodle_course_id} ({$result->get_error_message()})" );
				}
			}
		}

		// 4. Enviar email de bienvenida si hubo matrículas
		if ( ! empty( $enrolled_courses ) ) {
			$this->send_enrollment_email( $order, $enrolled_courses, $temp_pass );
			delete_post_meta( $order_id, '_moodle_temp_pass' ); // Limpiar pass temporal después de enviar
		}
	}

	/**
	 * Enviar email de bienvenida al alumno con los detalles de acceso.
	 */
	private function send_enrollment_email( $order, $courses, $user_pass = '' ) {
		$user_name   = $order->get_billing_first_name();
		$user_email  = $order->get_billing_email();
		$order_id    = $order->get_id();
		$moodle_url  = get_option( 'woo_otec_moodle_api_url' );
		
		// Buffer para cargar la plantilla
		ob_start();
		include WOO_OTEC_MOODLE_PATH . 'templates/email-enrollment.php';
		$message = ob_get_clean();

		$subject = sprintf( '[%s] ¡Bienvenido! Tus cursos en el Aula Virtual ya están listos', get_bloginfo( 'name' ) );
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );

		// Usar Email Manager si está disponible, sino usar wp_mail
		if ( $this->email_manager ) {
			$this->email_manager->send_email( $user_email, $subject, $message, $headers );
		} else {
			wp_mail( $user_email, $subject, $message, $headers );
		}
		
		$order->add_order_note( 'Email de bienvenida y acceso enviado al alumno.' );
	}

	/**
	 * Crear un nuevo usuario en Moodle desde un pedido de WooCommerce.
	 */
	private function create_moodle_user( $order ) {
		$username = str_replace( '@', '_', $order->get_billing_email() );
		$password = wp_generate_password( 12, true, true );

		$user_data = array(
			'username'  => $username,
			'password'  => $password,
			'firstname' => $order->get_billing_first_name(),
			'lastname'  => $order->get_billing_last_name(),
			'email'     => $order->get_billing_email(),
		);

		$result = $this->api_client->create_user( $user_data );

		if ( is_wp_error( $result ) ) {
			$order->add_order_note( 'ERROR al crear usuario en Moodle: ' . $result->get_error_message() );
			return null;
		}

		// Guardar contraseña temporal para el email
		update_post_meta( $order->get_id(), '_moodle_temp_pass', $password );

		return isset( $result['id'] ) ? $result['id'] : null;
	}

	/**
	 * AJAX Handler: Inscribir usuario en un producto desde shortcode
	 */
	public function ajax_enroll_product() {
		check_ajax_referer( 'woo-otec-moodle-nonce', 'nonce' );

		$product_id = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : 0;

		if ( ! $product_id ) {
			wp_send_json_error( 'ID de producto inválido' );
		}

		// Verificar que el usuario esté logueado
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( 'Debes estar logueado para inscribirte' );
		}

		$current_user = wp_get_current_user();
		$product = wc_get_product( $product_id );
		$moodle_course_id = get_post_meta( $product_id, '_moodle_course_id', true );

		if ( ! $product || ! $moodle_course_id ) {
			wp_send_json_error( 'Producto no válido o no está vinculado a un curso de Moodle' );
		}

		// Crear orden simple para la inscripción
		try {
			$order = wc_create_order();

			// Agregar el producto a la orden
			$order->add_product( $product, 1 );

			// Establecer datos del cliente
			$order->set_billing_email( $current_user->user_email );
			$order->set_billing_first_name( $current_user->first_name ?: 'Usuario' );
			$order->set_billing_last_name( $current_user->last_name ?: '' );

			// Establecer estado a completado (asumiendo que es gratuito o prepago)
			$order->set_status( 'completed' );
			$order->save();

			// Procesar la matriculación
			$this->process_order_enrollment( $order->get_id() );

			$this->logger->log( 'SUCCESS', "Usuario {$current_user->user_email} inscrito en producto $product_id" );

			wp_send_json_success( array(
				'message' => '¡Inscripción completada exitosamente!',
				'order_id' => $order->get_id(),
			) );
		} catch ( Exception $e ) {
			wp_send_json_error( 'Error al procesar inscripción: ' . $e->getMessage() );
		}
	}
}

