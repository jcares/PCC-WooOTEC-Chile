<?php
/**
 * Gestor de Logs Operativos del Plugin.
 *
 * @package    Woo_OTEC_Moodle
 */

namespace Woo_OTEC_Moodle;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clase Logger para gestionar logs operativos.
 */
class Logger {

	/**
	 * Ruta del archivo de log.
	 *
	 * @var string
	 */
	private $log_file;

	/**
	 * Ruta del archivo de log de errores.
	 *
	 * @var string
	 */
	private $error_log_file;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->log_file = WOO_OTEC_MOODLE_PATH . 'logs/operaciones.log';
		$this->error_log_file = WOO_OTEC_MOODLE_PATH . 'logs/errores.log';
		$this->init_log_files();
	}

	/**
	 * Asegurar que los archivos de log existen y son escribibles.
	 */
	private function init_log_files() {
		if ( ! file_exists( $this->log_file ) ) {
			touch( $this->log_file );
			chmod( $this->log_file, 0644 );
		}
		if ( ! file_exists( $this->error_log_file ) ) {
			touch( $this->error_log_file );
			chmod( $this->error_log_file, 0644 );
		}
	}

	/**
	 * Registrar un mensaje en el log.
	 *
	 * @param string $type    Tipo de mensaje (INFO, SUCCESS, WARNING, ERROR).
	 * @param string $message Mensaje a registrar.
	 * @param array  $context Contexto adicional (opcional).
	 */
	public function log( $type, $message, $context = array() ) {
		$timestamp = date( 'Y-m-d H:i:s' );
		$user_id = get_current_user_id();
		$user_name = $user_id ? get_user_by( 'ID', $user_id )->user_login : 'SYSTEM';
		
		$context_str = ! empty( $context ) ? ' | ' . json_encode( $context ) : '';
		$entry = "[{$timestamp}] [{$type}] [User: {$user_name}] {$message}{$context_str}" . PHP_EOL;

		error_log( $entry, 3, $this->log_file );

		// Registrar errores en archivo separado
		if ( in_array( $type, array( 'ERROR', 'WARNING' ), true ) ) {
			error_log( $entry, 3, $this->error_log_file );
		}
	}

	/**
	 * Registrar evento de CRON.
	 *
	 * @param string $action Acción realizada.
	 * @param mixed  $result Resultado de la acción.
	 */
	public function log_cron( $action, $result ) {
		$this->log( 'INFO', "CRON: {$action}", array( 'result' => $result ) );
	}

	/**
	 * Registrar evento de SSO.
	 *
	 * @param string $action Acción realizada.
	 * @param int    $user_id ID del usuario.
	 * @param mixed  $data Datos adicionales.
	 */
	public function log_sso( $action, $user_id, $data = array() ) {
		$this->log( 'INFO', "SSO: {$action}", array( 'user_id' => $user_id, 'data' => $data ) );
	}

	/**
	 * Registrar evento de Sincronización.
	 *
	 * @param string $source Fuente de sincronización.
	 * @param int    $count Cantidad de items procesados.
	 * @param mixed  $details Detalles de la sincronización.
	 */
	public function log_sync( $source, $count, $details = '' ) {
		$this->log( 'SUCCESS', "SYNC: {$source} procesados ({$count} items). {$details}" );
	}

	/**
	 * Registrar evento de Email.
	 *
	 * @param string $action Acción realizada.
	 * @param string $email Email del destinatario.
	 * @param bool   $success Si fue exitoso.
	 * @param string $message Mensaje de error o confirmación.
	 */
	public function log_email( $action, $email, $success, $message = '' ) {
		$type = $success ? 'SUCCESS' : 'ERROR';
		$this->log( $type, "EMAIL: {$action} to {$email}. {$message}" );
	}

	/**
	 * Registrar evento de Integración.
	 *
	 * @param string $service Servicio (Moodle, WooCommerce, etc).
	 * @param string $action Acción realizada.
	 * @param bool   $success Si fue exitoso.
	 * @param string $message Mensaje de error o confirmación.
	 */
	public function log_integration( $service, $action, $success, $message = '' ) {
		$type = $success ? 'SUCCESS' : 'ERROR';
		$this->log( $type, "INTEGRATION: {$service} - {$action}. {$message}" );
	}

	/**
	 * Registrar evento de API.
	 *
	 * @param string $endpoint Endpoint de la API.
	 * @param string $method Método HTTP.
	 * @param int    $status_code Código de estado HTTP.
	 * @param string $message Mensaje de error o confirmación.
	 */
	public function log_api( $endpoint, $method, $status_code, $message = '' ) {
		$type = ( $status_code >= 200 && $status_code < 300 ) ? 'SUCCESS' : 'ERROR';
		$this->log( $type, "API: {$method} {$endpoint} ({$status_code}). {$message}" );
	}

	/**
	 * Leer las últimas líneas del log.
	 *
	 * @param int $lines Número de líneas a leer.
	 * @return string Contenido del log.
	 */
	public function get_recent_logs( $lines = 50 ) {
		if ( ! file_exists( $this->log_file ) ) {
			return 'El archivo de log no existe aún.';
		}

		$data = file( $this->log_file );
		$data = array_slice( $data, -$lines );

		return implode( '', array_reverse( $data ) );
	}

	/**
	 * Leer los últimos errores registrados.
	 *
	 * @param int $lines Número de líneas a leer.
	 * @return string Contenido del log de errores.
	 */
	public function get_recent_errors( $lines = 50 ) {
		if ( ! file_exists( $this->error_log_file ) ) {
			return 'No hay errores registrados.';
		}

		$data = file( $this->error_log_file );
		$data = array_slice( $data, -$lines );

		return implode( '', array_reverse( $data ) );
	}

	/**
	 * Limpiar log antiguo (mantener últimos 10000 registros).
	 */
	public function cleanup_logs() {
		$this->cleanup_log_file( $this->log_file, 10000 );
		$this->cleanup_log_file( $this->error_log_file, 5000 );
	}

	/**
	 * Limpiar un archivo de log específico.
	 *
	 * @param string $file Ruta del archivo.
	 * @param int    $max_lines Máximo de líneas a mantener.
	 */
	private function cleanup_log_file( $file, $max_lines ) {
		if ( ! file_exists( $file ) ) {
			return;
		}

		$data = file( $file );
		if ( count( $data ) > $max_lines ) {
			$data = array_slice( $data, -$max_lines );
			file_put_contents( $file, implode( '', $data ) );
		}
	}
}
