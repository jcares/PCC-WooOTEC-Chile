<?php
/**
 * Gestor de Single Sign-On (SSO)
 * 
 * Genera URLs de login automático para Moodle
 * Permite a usuarios acceder a Moodle sin ingresar credenciales
 *
 * @package    Woo_OTEC_Moodle
 * @version    3.0.7
 */

namespace Woo_OTEC_Moodle;

use \WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SSO_Manager {

	/**
	 * Opción para habilitar/deshabilitar SSO
	 */
	const ENABLED_OPTION = 'woo_otec_moodle_sso_enabled';

	/**
	 * Opción para URL base de Moodle
	 */
	const BASE_URL_OPTION = 'woo_otec_moodle_sso_base_url';

	/**
	 * Opción para token SSO (si es necesario)
	 */
	const TOKEN_OPTION = 'woo_otec_moodle_sso_token';

	/**
	 * Parámetro de email para login
	 */
	const EMAIL_PARAM = 'email';

	/**
	 * Parámetro de ID de curso (opcional)
	 */
	const COURSE_PARAM = 'course_id';

	/**
	 * Constructor
	 */
	public function __construct() {
		// Inicializar si no existen las opciones
		if ( ! get_option( self::ENABLED_OPTION ) ) {
			add_option( self::ENABLED_OPTION, false );
		}
	}

	/**
	 * Verificar si SSO está habilitado
	 */
	public static function is_enabled() {
		return (bool) get_option( self::ENABLED_OPTION, false );
	}

	/**
	 * Obtener URL base de Moodle
	 */
	public static function get_base_url() {
		$url = get_option( self::BASE_URL_OPTION, '' );
		return rtrim( $url, '/' ); // Eliminar trailing slash
	}

	/**
	 * Actualizar configuración SSO
	 */
	public static function update_settings( $enabled, $base_url ) {
		$enabled = (bool) $enabled;
		$base_url = sanitize_url( $base_url );

		update_option( self::ENABLED_OPTION, $enabled );
		update_option( self::BASE_URL_OPTION, $base_url );

		return array(
			'enabled'  => $enabled,
			'base_url' => $base_url,
		);
	}

	/**
	 * Generar URL de login para un usuario
	 * 
	 * @param string $email Email del usuario
	 * @param int|null $course_id ID del curso (opcional)
	 * @return string URL de login o vacío si SSO no está habilitado
	 */
	public static function build_login_url( $email, $course_id = null ) {
		if ( ! self::is_enabled() ) {
			return '';
		}

		$base_url = self::get_base_url();
		if ( empty( $base_url ) ) {
			return '';
		}

		// Sanitizar email
		$email = sanitize_email( $email );
		if ( ! is_email( $email ) ) {
			return '';
		}

		// Construir URL
		$url = $base_url . '/auth/email/';
		$params = array(
			self::EMAIL_PARAM => $email,
		);

		if ( ! empty( $course_id ) ) {
			$params[ self::COURSE_PARAM ] = intval( $course_id );
		}

		return add_query_arg( $params, $url );
	}

	/**
	 * Generar URL de login para un pedido
	 * 
	 * @param int $order_id ID del pedido
	 * @param int|null $course_id ID del curso (opcional)
	 * @return string URL de login
	 */
	public static function build_order_login_url( $order_id, $course_id = null ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return '';
		}

		$email = $order->get_billing_email();
		return self::build_login_url( $email, $course_id );
	}

	/**
	 * Almacenar URL de login en metadata del pedido
	 * 
	 * @param int $order_id ID del pedido
	 * @param int $course_id ID del curso en Moodle
	 * @param string $course_name Nombre del curso
	 */
	public static function store_order_login_url( $order_id, $course_id, $course_name = '' ) {
		if ( ! self::is_enabled() ) {
			return false;
		}

		$url = self::build_order_login_url( $order_id, $course_id );
		if ( empty( $url ) ) {
			return false;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return false;
		}

		// Obtener URLs existentes
		$sso_urls = $order->get_meta( '_wom_sso_login_urls', true );
		if ( ! is_array( $sso_urls ) ) {
			$sso_urls = array();
		}

		// Agregar nueva URL
		$sso_urls[ $course_id ] = array(
			'url'         => $url,
			'course_id'   => $course_id,
			'course_name' => $course_name,
			'created'     => current_time( 'mysql' ),
		);

		// Guardar
		$order->update_meta_data( '_wom_sso_login_urls', $sso_urls );
		$order->save();

		return true;
	}

	/**
	 * Obtener URLs de login almacenadas en un pedido
	 * 
	 * @param int $order_id ID del pedido
	 * @return array URLs de login por curso
	 */
	public static function get_order_login_urls( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return array();
		}

		$urls = $order->get_meta( '_wom_sso_login_urls', true );
		return is_array( $urls ) ? $urls : array();
	}

	/**
	 * Generar HTML con enlace y botón de login
	 * 
	 * @param int $order_id ID del pedido
	 * @param int $course_id ID del curso
	 * @param string $label Texto del botón
	 * @return string HTML con botón de login
	 */
	public static function get_login_button_html( $order_id, $course_id, $label = 'Acceder a Moodle' ) {
		$url = self::build_order_login_url( $order_id, $course_id );
		if ( empty( $url ) ) {
			return '';
		}

		$label = esc_html( $label );
		$url = esc_url( $url );

		return sprintf(
			'<a href="%s" class="wom-sso-login-btn" style="display: inline-block; padding: 12px 24px; background: #4f46e5; color: #fff; text-decoration: none; border-radius: 4px; font-weight: 600;">%s</a>',
			$url,
			$label
		);
	}

	/**
	 * Obtener estado de configuración SSO
	 */
	public static function get_status() {
		return array(
			'enabled'   => self::is_enabled(),
			'base_url'  => self::get_base_url(),
			'configured' => ! empty( self::get_base_url() ),
		);
	}

	/**
	 * Validar URL base de Moodle
	 * 
	 * @param string $url URL a validar
	 * @return bool|WP_Error
	 */
	public static function validate_base_url( $url ) {
		$url = esc_url_raw( $url );
		
		if ( empty( $url ) ) {
			return new \WP_Error( 'empty_url', 'La URL de Moodle no puede estar vacía' );
		}

		// Verificar que sea una URL válida
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return new \WP_Error( 'invalid_url', 'La URL de Moodle no es válida' );
		}

		// Verificar que sea HTTPS o localhost HTTP
		if ( ! preg_match( '/^https:\/\/|^http:\/\/localhost/', $url ) ) {
			return new \WP_Error( 'insecure_url', 'Se recomienda usar HTTPS para URLs de Moodle en producción' );
		}

		return true;
	}
}
