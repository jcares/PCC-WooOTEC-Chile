<?php
/**
 * Event Bus - Centralized event routing
 *
 * Implements an internal event system that decouples components.
 * Events are fired and listeners respond without direct coupling.
 *
 * @package Woo_OTEC_Moodle\Core
 * @since   4.0.0
 */

namespace Woo_OTEC_Moodle\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EventBus {

	/**
	 * Registered listeners.
	 *
	 * @var array
	 */
	private $listeners = array();

	/**
	 * Listen for an event.
	 *
	 * @param string   $event    Event name.
	 * @param callable $listener Callback function.
	 * @param int      $priority Execution priority.
	 *
	 * @return void
	 */
	public function listen( $event, $listener, $priority = 10 ) {
		if ( ! isset( $this->listeners[ $event ] ) ) {
			$this->listeners[ $event ] = array();
		}

		$this->listeners[ $event ][ $priority ][] = $listener;
		krsort( $this->listeners[ $event ] );
	}

	/**
	 * Fire an event.
	 *
	 * @param string $event Event name.
	 * @param array  $data  Event data.
	 *
	 * @return void
	 */
	public function fire( $event, $data = array() ) {
		if ( ! isset( $this->listeners[ $event ] ) ) {
			return;
		}

		foreach ( $this->listeners[ $event ] as $priority => $callbacks ) {
			foreach ( $callbacks as $callback ) {
				call_user_func( $callback, $data );
			}
		}
	}

	/**
	 * Stop listening for an event.
	 *
	 * @param string   $event    Event name.
	 * @param callable $listener Callback function.
	 *
	 * @return void
	 */
	public function remove( $event, $listener ) {
		if ( ! isset( $this->listeners[ $event ] ) ) {
			return;
		}

		foreach ( $this->listeners[ $event ] as $priority => $callbacks ) {
			foreach ( $callbacks as $key => $callback ) {
				if ( $callback === $listener ) {
					unset( $this->listeners[ $event ][ $priority ][ $key ] );
				}
			}
		}
	}
}
