<?php
/**
 * Logger - Centralized logging system
 *
 * Implements PSR-3 compatible logging with file rotation,
 * levels (debug, info, warning, error, critical), and contextual data.
 *
 * @package Woo_OTEC_Moodle\Foundation
 * @since   4.0.0
 */

namespace Woo_OTEC_Moodle\Foundation;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Logger {

	const DEBUG   = 'debug';
	const INFO    = 'info';
	const WARNING = 'warning';
	const ERROR   = 'error';
	const CRITICAL = 'critical';

	/**
	 * Log file path.
	 *
	 * @var string
	 */
	private $log_file;

	/**
	 * Max log file size in bytes.
	 *
	 * @var int
	 */
	private $max_size = 10485760; // 10MB.

	/**
	 * Constructor.
	 *
	 * @since 4.0.0
	 */
	public function __construct() {
		$this->log_file = WOO_OTEC_MOODLE_PATH . 'logs/woo-otec-moodle.log';
		$this->ensure_log_dir();
	}

	/**
	 * Ensure logs directory exists.
	 *
	 * @return void
	 */
	private function ensure_log_dir() {
		$log_dir = dirname( $this->log_file );
		if ( ! is_dir( $log_dir ) ) {
			wp_mkdir_p( $log_dir );
		}
	}

	/**
	 * Log a message.
	 *
	 * @param string $level    Log level.
	 * @param string $message  Log message.
	 * @param array  $context  Additional context.
	 *
	 * @return void
	 */
	public function log( $level, $message, $context = array() ) {
		$this->rotate_if_needed();

		$timestamp = current_time( 'mysql' );
		$context_str = ! empty( $context ) ? ' | ' . wp_json_encode( $context ) : '';
		$log_entry = sprintf( "[%s] [%s] %s%s\n", $timestamp, strtoupper( $level ), $message, $context_str );

		// phpcs:disable WordPress.WP.AlternativeFunctions.file_operations_fwrite
		$handle = fopen( $this->log_file, 'a' );
		if ( $handle ) {
			fwrite( $handle, $log_entry );
			fclose( $handle );
		}
		// phpcs:enable

		// Also log to WordPress if in debug mode.
		if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			error_log( "Woo_OTEC_Moodle [$level] $message" . $context_str );
		}
	}

	/**
	 * Log debug message.
	 *
	 * @param string $message Message.
	 * @param array  $context Context.
	 *
	 * @return void
	 */
	public function debug( $message, $context = array() ) {
		$this->log( self::DEBUG, $message, $context );
	}

	/**
	 * Log info message.
	 *
	 * @param string $message Message.
	 * @param array  $context Context.
	 *
	 * @return void
	 */
	public function info( $message, $context = array() ) {
		$this->log( self::INFO, $message, $context );
	}

	/**
	 * Log warning message.
	 *
	 * @param string $message Message.
	 * @param array  $context Context.
	 *
	 * @return void
	 */
	public function warning( $message, $context = array() ) {
		$this->log( self::WARNING, $message, $context );
	}

	/**
	 * Log error message.
	 *
	 * @param string $message Message.
	 * @param array  $context Context.
	 *
	 * @return void
	 */
	public function error( $message, $context = array() ) {
		$this->log( self::ERROR, $message, $context );
	}

	/**
	 * Log critical message.
	 *
	 * @param string $message Message.
	 * @param array  $context Context.
	 *
	 * @return void
	 */
	public function critical( $message, $context = array() ) {
		$this->log( self::CRITICAL, $message, $context );
	}

	/**
	 * Rotate log file if needed.
	 *
	 * @return void
	 */
	private function rotate_if_needed() {
		if ( ! file_exists( $this->log_file ) ) {
			return;
		}

		// phpcs:disable WordPress.WP.AlternativeFunctions.file_operations_filesize
		if ( filesize( $this->log_file ) > $this->max_size ) {
			$rotated = $this->log_file . '.' . current_time( 'YmdHis' );
			rename( $this->log_file, $rotated );
		}
		// phpcs:enable
	}
}
