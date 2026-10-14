<?php
/**
 * RCP Add-On Updater
 *
 * Deprecated. The legacy add-on update server at restrictcontentpro.com has been retired, and
 * requests to it only redirect to a marketing page.
 *
 * @package     Kadence Memberships Pro
 * @subpackage  Admin/Add-On Updater
 * @copyright   Copyright (c) 2017, Kadence Memberships Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Inert shell kept for backwards compatibility.
 *
 * Add-ons that still instantiate this class do not fatal, but it no longer hooks into WordPress or
 * makes any HTTP requests. Arguments passed to the constructor, such as the add-on ID, plugin file and
 * version, are ignored.
 *
 * @deprecated 4.0.9 Add-ons are updated through the standard plugin update system.
 */
class RCP_Add_On_Updater {

	/**
	 * Class constructor.
	 *
	 * Intentionally does nothing: no filters or actions are registered and no remote requests are made.
	 */
	public function __construct() {
	}
}
