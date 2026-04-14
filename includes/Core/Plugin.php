<?php
/**
 * Main Plugin Class (Refactored)
 *
 * Handles plugin initialization, service registration,
 * event listeners, and lifecycle hooks.
 *
 * @package Woo_OTEC_Moodle\Core
 * @since   4.0.0
 */

namespace Woo_OTEC_Moodle\Core;

use Woo_OTEC_Moodle\Foundation\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Plugin {

	/**
	 * Plugin instance (Singleton).
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Service container.
	 *
	 * @var ServiceContainer
	 */
	public $container;

	/**
	 * Event bus.
	 *
	 * @var EventBus
	 */
	public $events;

	/**
	 * Logger instance.
	 *
	 * @var Logger
	 */
	public $logger;

	/**
	 * Get plugin instance.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor (private - Singleton).
	 *
	 * @since 4.0.0
	 */
	private function __construct() {
		$this->container = new ServiceContainer();
		$this->events = new EventBus();
		$this->logger = new Logger();

		$this->register_services();
		$this->register_listeners();

		add_action( 'plugins_loaded', array( $this, 'boot' ), 10 );
	}

	/**
	 * Register core services in container.
	 *
	 * This method centralizes all service definitions.
	 * Override this in child classes for custom services.
	 *
	 * @return void
	 */
	protected function register_services() {
		// Logger is a singleton.
		$this->container->singleton( 'logger', $this->logger );

		// Event Bus is a singleton.
		$this->container->singleton( 'events', $this->events );

		// To be extended: Register other services here when refactoring classes.
		// Examples:
		// $this->container->register( 'api_client', function( $c ) {
		//    return new Integrations\Moodle\MoodleClient( $c->get( 'logger' ) );
		// } );
	}

	/**
	 * Register event listeners.
	 *
	 * This method is called during initialization to wire up
	 * event handlers. Specific listeners are registered here.
	 *
	 * @return void
	 */
	protected function register_listeners() {
		// To be extended: Register listeners here when refactoring components.
		// Examples:
		// $this->events->listen( 'order_completed', array( $this, 'on_order_completed' ), 10 );
		// $this->events->listen( 'course_created', array( $this, 'on_course_created' ), 10 );
	}

	/**
	 * Bootstrap plugin after plugins_loaded.
	 *
	 * At this point, WooCommerce and all plugins are loaded.
	 * Initialize all components here.
	 *
	 * @return void
	 */
	public function boot() {
		// Check WooCommerce availability.
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->logger->warning( 'WooCommerce not found. Plugin inactive.' );
			return;
		}

		// Load admin components if in admin.
		if ( is_admin() ) {
			$this->boot_admin();
		} else {
			$this->boot_frontend();
		}

		$this->logger->info( 'Plugin initialized', array( 'version' => WOO_OTEC_MOODLE_VERSION ) );
	}

	/**
	 * Bootstrap admin components.
	 *
	 * @return void
	 */
	private function boot_admin() {
		// To be extended with admin initialization.
	}

	/**
	 * Bootstrap frontend components.
	 *
	 * @return void
	 */
	private function boot_frontend() {
		// To be extended with frontend initialization.
	}

	/**
	 * Get service from container.
	 *
	 * @param string $key Service key.
	 *
	 * @return mixed|null
	 */
	public function get( $key ) {
		return $this->container->get( $key );
	}
}
