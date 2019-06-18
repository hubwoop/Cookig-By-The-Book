<?php

namespace ProAtCooking\Recipe;

include_once 'pre-flight.php';
include_once 'class-settings.php';

class Log {

	private $logFile;
	private $enabled;
	private $verbose = true;
	protected static $_instance = null;

	public static function get_instance(): Log {
		if ( null === self::$_instance ) {
			self::$_instance = new self( CBTB_PLUGIN_ROOT );
		}

		return self::$_instance;
	}

	function __construct( string $pluginRoot ) {
		$this->logFile = $pluginRoot . 'debug.log';
		$this->enabled = Settings::logging_enabled();
	}

	protected function __clone() {
		// Prevent singleton cloning
	}

	function error( string $message ): void {
		$this->writeLog( $this->annotate( $message, LogTypes::$error ) );
	}

	function warning( string $message ): void {
		$this->writeLog( $this->annotate( $message, LogTypes::$warning ) );
	}

	function info( string $message ): void {
		$this->writeLog( $this->annotate( $message, LogTypes::$info ) );
	}

	function debug( string $message ): void {
		$this->writeLog( $this->annotate( $message, LogTypes::$debug ) );
	}

	function json_encoded( $message, $prettyPrint = 0 ): void {
		if ( $prettyPrint ) {
			$prettyPrint = JSON_PRETTY_PRINT;
		}
		$this->writeLog( $this->annotate( json_encode( $message, $prettyPrint ), LogTypes::$debug ) );
	}

	private function writeLog( $annotated_message ): void {
		if ( $this->enabled ) {
			try {
				error_log( $annotated_message, 3, $this->logFile );
			} catch ( \Exception $e ) {
				show_message( $e->getMessage() );
			}
		}
	}

	private function annotate( string $message, string $type ): string {
		return '[' . $type . " " . date( 'Y-m-d H:i:s' ) . '] '
		       . $message . $this->get_caller_details_if_verbose() . PHP_EOL;
	}

	/**
	 * Maps and reduces debug_backtrace()'s result.
	 * @return string
	 */
	private function get_caller_details_if_verbose(): string {
		// we jumped 3 times to come here from the caller.
		if ( ! $this->verbose ) {
			return '';
		}

		$trace = debug_backtrace();
		if ( is_array( $trace ) && sizeof( $trace ) > 3 ) {

			$caller_mapped = $this->map_trace_entry( $trace[3] );

			return $this->reduce_to_string( $caller_mapped );
		}

		return '';
	}

	private function map_trace_entry( array $caller ): array {
		$mapped_caller = array(
			'function' => '',
			'class'    => '',
			'type'     => ''
		);
		if ( array_key_exists( 'function', $caller ) ) {
			$mapped_caller['function'] = $caller['function'];
		}
		if ( array_key_exists( 'class', $caller ) ) {
			$mapped_caller['class'] = $caller['class'];
		}
		$mapped_caller['type'] = $caller['type'];

		return $mapped_caller;
	}

	private function reduce_to_string( array $caller_reduced ): string {
		return ' [' . $caller_reduced['class'] . $caller_reduced['type'] . $caller_reduced['function'] . ']';
	}
}

class LogTypes {
	// @formatter:off
	public static $error   = 'ERROR  ';
	public static $warning = 'WARNING';
	public static $info    = 'INFO   ';
	public static $debug   = 'DEBUG  ';
	// @formatter:on
}