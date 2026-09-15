<?php
/**
 * Component
 *
 * @package   restrict-content-pro
 * @copyright Copyright (c) 2020, Sandhills Development, LLC
 * @license   GPL2+
 * @since     3.4
 */

namespace RCP;

/**
 * Class Component
 *
 * @package RCP
 */
#[\AllowDynamicProperties]
class Component extends Base_Object {

	/**
	 * Array of interface objects
	 *
	 * @since 3.4
	 * @var array
	 */
	private $interfaces = array();

	/**
	 * Array of dynamicKeys
	 *
	 * @since 3.4
	 * @var array
	 */
	private $dynamicKeys = array();

	/**
	 * Returns an interface object
	 *
	 * @param string $key Interface key
	 *
	 * @since 3.4
	 * @return object|false
	 */
	public function get_interface( $key ) {
		return isset( $this->interfaces[ $key ] ) ? $this->interfaces[ $key ] : false;
	} 

	/**
	 * Set up interfaces
	 *
	 * @param array $args
	 *
	 * @since 3.4
	 */
	protected function set_vars( $args = array() ) {

		$keys = array(
			'schema',
			'table',
			'query',
			'object',
			'meta'
		);

		foreach ( $args as $key => $value ) {

			if ( in_array( $key, $keys, true ) && class_exists( $value ) ) {
				$this->interfaces[ $key ] = new $value;
			} else {
				$this->dynamicKeys[$key] = $value;
			}

		}

	}

	public function __get(string $name) {
	        if(property_exists($this, $name))
	            return $this->$name;
	        else
	            return $this->dynamicKeys[$name];
        }

}
