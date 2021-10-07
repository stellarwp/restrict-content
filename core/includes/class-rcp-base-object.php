<?php
/**
 * Base Core Object
 *
 * @package   restrict-content-pro
 * @copyright Copyright (c) 2018, Restrict Content Pro team
 * @license   GPL2+
 * @since     3.0
 */

namespace RCP;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Implements a base object to be extended by core objects.
 *
 * @since 3.0
 * @abstract
 */
abstract class Base_Object {

	/**
	 * Object constructor.
	 *
	 * @param mixed $args Object to populate members for.
	 *
	 * @since 3.0
	 */
	public function __construct( $args = null ) {
		$this->set_vars( $args );
	}

	/**
	 * Set class variables from arguments.
	 *
	 * @param array $args
	 *
	 * @since 3.0
	 */
	protected function set_vars( $args = array() ) {

		// Bail if empty or not an array
		if ( empty( $args ) ) {
			return;
		}

		// Cast to an array
		if ( ! is_array( $args ) ) {
			$args = (array) $args;
		}

		// Set all properties
		foreach ( $args as $key => $value ) {
			if ( '0000-00-00 00:00:00' === $value ) {
				$value = null;
			}

			$this->{$key} = $value;
		}
	}

	/**
	 * Get all object properties as an array
	 *
	 * @since 3.4
	 * @return array
	 */
	public function export_vars() {
		return get_object_vars( $this );
	}

}
