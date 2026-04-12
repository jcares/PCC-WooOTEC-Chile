<?php
/**
 * Excepción Personalizada para Woo OTEC Moodle
 * 
 * Define excepciones personalizadas para el plugin
 *
 * @package    Woo_OTEC_Moodle
 * @version    3.0.7
 */

namespace Woo_OTEC_Moodle;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Excepción base para Woo OTEC Moodle
 */
class Woo_OTEC_Exception extends \Exception {
	
	/**
	 * Código de error
	 */
	public $error_code = 'unknown_error';

	/**
	 * Contexto adicional
	 */
	public $context = array();

	/**
	 * Constructor
	 */
	public function __construct( $message = '', $code = 0, $error_code = 'unknown_error', $context = array() ) {
		parent::__construct( $message, $code );
		$this->error_code = $error_code;
		$this->context = $context;
	}

	/**
	 * Obtener información completa del error
	 */
	public function get_full_info() {
		return array(
			'message'    => $this->getMessage(),
			'code'       => $this->getCode(),
			'error_code' => $this->error_code,
			'context'    => $this->context,
			'file'       => $this->getFile(),
			'line'       => $this->getLine(),
			'trace'      => $this->getTraceAsString(),
		);
	}
}

/**
 * Excepción para errores de conexión a API
 */
class API_Connection_Exception extends Woo_OTEC_Exception {
	
	public function __construct( $message = 'Error de conexión a Moodle', $context = array() ) {
		parent::__construct( $message, 0, 'api_connection_error', $context );
	}
}

/**
 * Excepción para errores de autenticación
 */
class Authentication_Exception extends Woo_OTEC_Exception {
	
	public function __construct( $message = 'Error de autenticación', $context = array() ) {
		parent::__construct( $message, 0, 'authentication_error', $context );
	}
}

/**
 * Excepción para datos no encontrados
 */
class Data_Not_Found_Exception extends Woo_OTEC_Exception {
	
	public function __construct( $message = 'Datos no encontrados', $context = array() ) {
		parent::__construct( $message, 0, 'data_not_found', $context );
	}
}

/**
 * Excepción para datos inválidos
 */
class Invalid_Data_Exception extends Woo_OTEC_Exception {
	
	public function __construct( $message = 'Datos inválidos', $context = array() ) {
		parent::__construct( $message, 0, 'invalid_data', $context );
	}
}

/**
 * Excepción para errores de operación en Moodle
 */
class Moodle_Operation_Exception extends Woo_OTEC_Exception {
	
	public function __construct( $message = 'Error en operación de Moodle', $context = array() ) {
		parent::__construct( $message, 0, 'moodle_operation_error', $context );
	}
}

/**
 * Excepción para errores de sincronización
 */
class Sync_Exception extends Woo_OTEC_Exception {
	
	public function __construct( $message = 'Error en sincronización', $context = array() ) {
		parent::__construct( $message, 0, 'sync_error', $context );
	}
}

/**
 * Handler global de excepciones
 */
class Exception_Handler {
	
	/**
	 * Logger
	 */
	private $logger;

	/**
	 * Constructor
	 */
	public function __construct( Logger $logger ) {
		$this->logger = $logger;
		
		// Registrar handlers
		set_exception_handler( array( $this, 'handle_exception' ) );
		set_error_handler( array( $this, 'handle_error' ) );
		register_shutdown_function( array( $this, 'handle_shutdown' ) );
	}

	/**
	 * Manejar excepciones
	 */
	public function handle_exception( \Throwable $exception ) {
		
		// Determinar si es una excepción personalizada
		if ( $exception instanceof Woo_OTEC_Exception ) {
			$full_info = $exception->get_full_info();
			
			// Loguear con nivel apropiado
			$this->logger->log(
				'ERROR',
				sprintf(
					'[EXCEPTION] %s - Código: %s - Contexto: %s',
					$exception->getMessage(),
					$full_info['error_code'],
					wp_json_encode( $full_info['context'] )
				)
			);

			// Mostrar mensaje amigable al usuario
			if ( is_admin() && current_user_can( 'manage_options' ) ) {
				wp_die(
					'<h2>Error en Woo OTEC Moodle</h2>' .
					'<p>' . esc_html( $exception->getMessage() ) . '</p>' .
					'<p style="color: #999; font-size: 12px;">Código: ' . esc_html( $full_info['error_code'] ) . '</p>',
					'Woo OTEC Moodle - Error'
				);
			} else {
				wp_die( 'Ocurrió un error. Por favor, contacte al administrador del sitio.' );
			}
		} else {
			// Excepción genérica de PHP
			$this->logger->log(
				'ERROR',
				sprintf(
					'[GENERIC EXCEPTION] %s en %s línea %d',
					$exception->getMessage(),
					$exception->getFile(),
					$exception->getLine()
				)
			);
		}
	}

	/**
	 * Manejar errores PHP
	 */
	public function handle_error( $errno, $errstr, $errfile, $errline ) {
		
		// Solo procesar errores graves
		if ( ! ( error_reporting() & $errno ) ) {
			return false;
		}

		$error_type = match ( $errno ) {
			E_WARNING     => 'WARNING',
			E_NOTICE      => 'NOTICE',
			E_USER_ERROR  => 'USER_ERROR',
			E_USER_NOTICE => 'USER_NOTICE',
			default       => 'ERROR',
		};

		$this->logger->log(
			$error_type,
			sprintf(
				'[PHP ERROR] %s en %s línea %d',
				$errstr,
				$errfile,
				$errline
			)
		);

		return true;
	}

	/**
	 * Manejar shutdown
	 */
	public function handle_shutdown() {
		$last_error = error_get_last();
		
		if ( $last_error !== null && in_array( $last_error['type'], array( E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR ) ) ) {
			$this->logger->log(
				'FATAL',
				sprintf(
					'[FATAL ERROR] %s en %s línea %d',
					$last_error['message'],
					$last_error['file'],
					$last_error['line']
				)
			);
		}
	}
}
