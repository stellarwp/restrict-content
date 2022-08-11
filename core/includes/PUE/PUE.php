<?php

namespace RCP\PUE;

class PUE {
	/**
	 * Container instance.
	 *
	 * @since TBD
	 *
	 * @var \tad_DI52_Container
	 */
	public $container;

	/**
	 * Constructor.
	 *
	 * @since TBD
	 *
	 * @param \tad_DI52_Container|null $container Container instance.
	 */
	public function __construct( $container = null ) {
		$this->container = $container ?: tribe();
	}

	/**
	 * Has PUE license.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public static function has_embedded_license() {
		if ( defined( 'RCP_FAKE_EMBEDDED_KEY' ) && RCP_FAKE_EMBEDDED_KEY ) {
			return true;
		}

		return !! Helper::DATA;
	}

	/**
	 * Whether or not the ithemes licensing page should be hidden.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public static function should_hide_ithemes_licensing() {
		$details = \Ithemes_Updater_Packages::get_full_details();
	}
}
