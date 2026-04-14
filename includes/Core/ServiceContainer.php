<?php
/**
 * Service Container - Dependency Injection & Service Registry
 *
 * Manages service instantiation, factories, and singleton instances.
 * Simplifies dependency injection and loose coupling between components.
 *
 * @package Woo_OTEC_Moodle\Core
 * @since   4.0.0
 */

namespace Woo_OTEC_Moodle\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ServiceContainer {

	/**
	 * Registered services and factories.
	 *
	 * @var array
	 */
	private $services = array();

	/**
	 * Resolved singleton instances.
	 *
	 * @var array
	 */
	private $instances = array();

	/**
	 * Register a service factory.
	 *
	 * @param string   $key     Service identifier.
	 * @param callable $factory Factory callback.
	 *
	 * @return void
	 */
	public function register( $key, callable $factory ) {
		$this->services[ $key ] = $factory;
	}

	/**
	 * Register a singleton instance.
	 *
	 * @param string $key      Service identifier.
	 * @param object $instance Service instance.
	 *
	 * @return void
	 */
	public function singleton( $key, $instance ) {
		$this->instances[ $key ] = $instance;
	}

	/**
	 * Get or resolve a service.
	 *
	 * @param string $key Service identifier.
	 *
	 * @return mixed|null
	 */
	public function get( $key ) {
		// Return singleton if exists.
		if ( isset( $this->instances[ $key ] ) ) {
			return $this->instances[ $key ];
		}

		// Resolve from factory.
		if ( isset( $this->services[ $key ] ) ) {
			$instance = call_user_func( $this->services[ $key ], $this );
			$this->instances[ $key ] = $instance;
			return $instance;
		}

		return null;
	}

	/**
	 * Check if service is registered.
	 *
	 * @param string $key Service identifier.
	 *
	 * @return bool
	 */
	public function has( $key ) {
		return isset( $this->services[ $key ] ) || isset( $this->instances[ $key ] );
	}
}
