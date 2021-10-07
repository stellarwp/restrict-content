<?php
/**
 * Backwards Compatibility Base Class
 *
 * @package   restrict-content-pro
 * @copyright Copyright (c) 2018, Restrict Content Pro team
 * @license   GPL2+
 * @since     3.0
 */

namespace RCP\Compat;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Base
 *
 * @package RCP\Compat
 *
 * @since   3.0
 */
abstract class Base {

	/**
	 * Whether or not to show deprecated notices.
	 *
	 * @var bool
	 */
	protected $show_notices;

	/**
	 * Whether or not to show backtrace.
	 *
	 * @var bool
	 */
	protected $show_backtrace;

	/**
	 * Base constructor.
	 *
	 * @since 3.0
	 */
	public function __construct() {
		$this->hooks();

		$this->show_notices   = apply_filters( 'rcp_show_deprecated_notices', ( defined( 'WP_DEBUG' ) && WP_DEBUG ) );
		$this->show_backtrace = apply_filters( 'rcp_show_backtrace', ( defined( 'WP_DEBUG' ) && WP_DEBUG ) );
	}

	/**
	 * Backwards compatibility hooks.
	 *
	 * @access protected
	 * @return void
	 */
	abstract protected function hooks();

}