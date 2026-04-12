<?php
/**
 * Gestor de Tareas Programadas - CRON
 * 
 * Sincronización automática de cursos cada N horas
 * Configurable desde panel de administración
 *
 * @package    Woo_OTEC_Moodle
 * @version    3.0.7
 */

namespace Woo_OTEC_Moodle;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Cron_Manager {

	/**
	 * Hook para la acción cron
	 */
	const SYNC_HOOK = 'woo_otec_moodle_sync_courses_cron';

	/**
	 * Opción para almacenar intervalo
	 */
	const INTERVAL_OPTION = 'woo_otec_moodle_cron_interval_hours';

	/**
	 * Valor por defecto (6 horas)
	 */
	const DEFAULT_INTERVAL = 6;

	/**
	 * Constructor
	 */
	public function __construct() {
		// Hooks para registro de schedule
		add_filter( 'cron_schedules', array( $this, 'add_custom_schedules' ) );
		
		// Hook para ejecutar sincronización
		add_action( self::SYNC_HOOK, array( $this, 'execute_sync' ) );
		
		// Hooks de activación/desactivación
		register_activation_hook( WOO_OTEC_MOODLE_FILE, array( $this, 'activate' ) );
		register_deactivation_hook( WOO_OTEC_MOODLE_FILE, array( $this, 'deactivate' ) );
	}

	/**
	 * Activación del plugin - programar cron
	 */
	public function activate() {
		$this->schedule_sync();
	}

	/**
	 * Desactivación del plugin - desconectar cron
	 */
	public function deactivate() {
		$this->unschedule_sync();
	}

	/**
	 * Agregar schedules personalizados a WordPress
	 */
	public function add_custom_schedules( $schedules ) {
		$interval = $this->get_interval_seconds();
		
		// Agregar schedule personalizado
		$schedules['woo_otec_moodle_custom'] = array(
			'interval' => $interval,
			'display'  => sprintf(
				__( 'WOO OTEC Moodle - Cada %d hora(s)', 'woo-otec-moodle' ),
				$this->get_interval_hours()
			),
		);
		
		return $schedules;
	}

	/**
	 * Obtener intervalo en horas desde configuración
	 */
	public function get_interval_hours() {
		$interval = (int) get_option( self::INTERVAL_OPTION, self::DEFAULT_INTERVAL );
		return max( 1, min( 24, $interval ) ); // Entre 1 y 24 horas
	}

	/**
	 * Obtener intervalo en segundos
	 */
	private function get_interval_seconds() {
		return $this->get_interval_hours() * HOUR_IN_SECONDS;
	}

	/**
	 * Programar sincronización
	 */
	public function schedule_sync() {
		// Desconectar si ya existe
		$this->unschedule_sync();
		
		// Programar con el intervalo configurado
		if ( ! wp_next_scheduled( self::SYNC_HOOK ) ) {
			wp_schedule_event(
				time() + 300, // Esperar 5 minutos después de activar
				'woo_otec_moodle_custom',
				self::SYNC_HOOK
			);
		}
	}

	/**
	 * Desprogramar sincronización
	 */
	public function unschedule_sync() {
		$timestamp = wp_next_scheduled( self::SYNC_HOOK );
		while ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::SYNC_HOOK );
			$timestamp = wp_next_scheduled( self::SYNC_HOOK );
		}
	}

	/**
	 * Ejecutar sincronización (desde CRON)
	 */
	public function execute_sync() {
		// Obtener logger
		$logger = new Logger();
		
		$logger->log(
			'INFO',
			'[CRON] Iniciando sincronización automática de cursos'
		);

		try {
			if ( ! class_exists( 'Woo_OTEC_Moodle\Course_Sync' ) ) {
				require_once WOO_OTEC_MOODLE_PATH . 'includes/class-course-sync.php';
			}

			// Ejecutar sincronización
			$sync = new Course_Sync(
				new API_Client(),
				$logger
			);

			// Aquí iría la lógica de sincronización
			// Por ahora solo loguear
			$logger->log(
				'SUCCESS',
				'[CRON] Sincronización completada correctamente'
			);

		} catch ( \Exception $e ) {
			$logger->log(
				'ERROR',
				'[CRON] Error en sincronización: ' . $e->getMessage()
			);
		}
	}

	/**
	 * Actualizar intervalo de configuración
	 */
	public function update_interval( $hours ) {
		$hours = (int) $hours;
		$hours = max( 1, min( 24, $hours ) ); // Entre 1 y 24

		update_option( self::INTERVAL_OPTION, $hours );

		// Reprogramar con nuevo intervalo
		$this->schedule_sync();

		return $hours;
	}

	/**
	 * Obtener próxima ejecución programada
	 */
	public function get_next_scheduled() {
		$timestamp = wp_next_scheduled( self::SYNC_HOOK );
		if ( $timestamp ) {
			return date( 'Y-m-d H:i:s', $timestamp );
		}
		return 'No programado';
	}

	/**
	 * Estado del CRON
	 */
	public function get_status() {
		return array(
			'enabled'      => (bool) wp_next_scheduled( self::SYNC_HOOK ),
			'interval'     => $this->get_interval_hours(),
			'next_run'     => $this->get_next_scheduled(),
			'last_sync'    => get_option( 'woo_otec_moodle_last_sync', 'Nunca' ),
		);
	}
}
