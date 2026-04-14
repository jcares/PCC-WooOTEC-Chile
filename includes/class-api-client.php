<?php
/**
 * Cliente de API para Moodle.
 * 
 * @package    Woo_OTEC_Moodle
 */

namespace Woo_OTEC_Moodle;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class API_Client {

	/**
	 * URL de la API de Moodle.
	 */
	private $api_url;

	/**
	 * Token de acceso de Moodle.
	 */
	private $api_token;

	/**
	 * Constructor.
	 * 
	 * @throws \Exception Si faltan credenciales de API.
	 */
	public function __construct() {
		$url   = get_option( 'woo_otec_moodle_api_url' );
		$token = get_option( 'woo_otec_moodle_api_token' );

		// Validar que las credenciales estén configuradas
		if ( empty( $url ) || empty( $token ) ) {
			// En desarrollo, esto puede loguear pero no fallar
			if ( WP_DEBUG ) {
				// Log pero no lanzar exception (para mantener compatibilidad)
				error_log( 'Woo OTEC Moodle: Credenciales de API no configuradas' );
			}
		}

		// Normalizar URL (Slash en lugar de Backslash)
		$this->api_url   = str_replace( '\\', '/', $url );
		$this->api_token = $token;
	}

	/**
	 * Realizar una llamada a la API de Moodle.
	 *
	 * @param string $function Función de la API a llamar.
	 * @param array  $params   Parámetros de la función.
	 * @return array|WP_Error  Respuesta de la API o error.
	 */
	private function call( $function, $params = array() ) {
		if ( empty( $this->api_url ) || empty( $this->api_token ) ) {
			return new WP_Error( 'missing_credentials', __( 'Faltan credenciales de la API de Moodle.', 'woo-otec-moodle' ) );
		}

		$params['wstoken']            = $this->api_token;
		$params['wsfunction']         = $function;
		$params['moodlewsrestformat'] = 'json';

		$url = trailingslashit( $this->api_url ) . 'webservice/rest/server.php';
		$url = add_query_arg( $params, $url );

		$response = wp_remote_get( $url, array( 'timeout' => 30 ) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( isset( $data['exception'] ) ) {
			return new WP_Error( 'moodle_exception', $data['message'], $data );
		}

		return $data;
	}

	/**
	 * Obtener la lista de cursos de Moodle.
	 */
	public function get_courses() {
		return $this->call( 'core_course_get_courses' );
	}

	/**
	 * Obtener la lista de categorías de cursos de Moodle.
	 */
	public function get_categories() {
		return $this->call( 'core_course_get_categories' );
	}

	/**
	 * Obtener un usuario por correo electrónico.
	 */
	public function get_user_by_email( $email ) {
		$params = array(
			'criteria' => array(
				array(
					'key'   => 'email',
					'value' => $email,
				),
			),
		);
		$response = $this->call( 'core_user_get_users', $params );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( ! empty( $response['users'] ) ) {
			return $response['users'][0];
		}

		return null;
	}

	/**
	 * Crear un nuevo usuario en Moodle.
	 */
	public function create_user( $user_data ) {
		$params = array(
			'users' => array(
				array(
					'username'  => $user_data['username'],
					'password'  => $user_data['password'],
					'firstname' => $user_data['firstname'],
					'lastname'  => $user_data['lastname'],
					'email'     => $user_data['email'],
				),
			),
		);
		$response = $this->call( 'core_user_create_users', $params );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( ! empty( $response ) && is_array( $response ) ) {
			return $response[0];
		}

		return new WP_Error( 'create_user_failed', __( 'Error al crear el usuario en Moodle.', 'woo-otec-moodle' ) );
	}

	/**
	 * Matricular a un usuario en un curso.
	 */
	public function enrol_user( $user_id, $course_id, $role_id = 5 ) {
		$params = array(
			'enrolments' => array(
				array(
					'roleid'   => $role_id,
					'userid'   => $user_id,
					'courseid' => $course_id,
				),
			),
		);
		return $this->call( 'enrol_manual_enrol_users', $params );
	}

	/**
	 * Probar la conexión con la API.
	 */
	public function test_connection() {
		$site_info = $this->call( 'core_webservice_get_site_info' );
		if ( is_wp_error( $site_info ) ) {
			return $site_info;
		}
		return true;
	}
}
