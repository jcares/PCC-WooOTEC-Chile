<?php
/**
 * Validator - Centralized validation logic
 *
 * Base validation class for schemas, sanitization, and error tracking.
 *
 * @package Woo_OTEC_Moodle\Foundation
 * @since   4.0.0
 */

namespace Woo_OTEC_Moodle\Foundation;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Validator {

	/**
	 * Validation errors.
	 *
	 * @var array
	 */
	protected $errors = array();

	/**
	 * Validation rules.
	 *
	 * @var array
	 */
	protected $rules = array();

	/**
	 * Validate data against rules.
	 *
	 * @param array $data  Data to validate.
	 * @param array $rules Validation rules.
	 *
	 * @return bool
	 */
	public function validate( $data, $rules ) {
		$this->errors = array();
		$this->rules = $rules;

		foreach ( $rules as $field => $rule ) {
			$this->validate_field( $field, $data[ $field ] ?? null, $rule );
		}

		return empty( $this->errors );
	}

	/**
	 * Validate a single field.
	 *
	 * @param string $field     Field name.
	 * @param mixed  $value     Field value.
	 * @param string $rule_str  Rule string (e.g., 'required|email|min:3').
	 *
	 * @return void
	 */
	private function validate_field( $field, $value, $rule_str ) {
		$rules = explode( '|', $rule_str );

		foreach ( $rules as $rule ) {
			$params = explode( ':', $rule );
			$rule_name = array_shift( $params );

			switch ( $rule_name ) {
				case 'required':
					if ( empty( $value ) && '0' !== $value ) {
						$this->add_error( $field, "Field '$field' is required." );
					}
					break;

				case 'email':
					if ( ! empty( $value ) && ! is_email( $value ) ) {
						$this->add_error( $field, "Field '$field' must be a valid email." );
					}
					break;

				case 'numeric':
					if ( ! empty( $value ) && ! is_numeric( $value ) ) {
						$this->add_error( $field, "Field '$field' must be numeric." );
					}
					break;

				case 'min':
					$min = $params[0] ?? 0;
					if ( ! empty( $value ) && strlen( $value ) < $min ) {
						$this->add_error( $field, "Field '$field' must be at least $min characters." );
					}
					break;

				case 'max':
					$max = $params[0] ?? 0;
					if ( ! empty( $value ) && strlen( $value ) > $max ) {
						$this->add_error( $field, "Field '$field' must not exceed $max characters." );
					}
					break;
			}
		}
	}

	/**
	 * Add validation error.
	 *
	 * @param string $field   Field name.
	 * @param string $message Error message.
	 *
	 * @return void
	 */
	protected function add_error( $field, $message ) {
		if ( ! isset( $this->errors[ $field ] ) ) {
			$this->errors[ $field ] = array();
		}
		$this->errors[ $field ][] = $message;
	}

	/**
	 * Get validation errors.
	 *
	 * @return array
	 */
	public function errors() {
		return $this->errors;
	}

	/**
	 * Get first error message.
	 *
	 * @return string|null
	 */
	public function get_first_error() {
		foreach ( $this->errors as $field_errors ) {
			if ( ! empty( $field_errors ) ) {
				return $field_errors[0];
			}
		}
		return null;
	}
}
