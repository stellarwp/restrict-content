/**
 * Membership Account Page JS ( [subscription_details] shortcode )
 *
 * @package   Restrict Content Pro
 * @copyright Copyright (c) 2020, Sandhills Development, LLC
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */
jQuery(document).ready((function(n){n(".rcp-disable-auto-renew").on("click",(function(n){return confirm(rcpAccountVars.confirmDisableAutoRenew)})),n(".rcp-enable-auto-renew").on("click",(function(c){const e=n(this).data("expiration");let r=rcpAccountVars.confirmEnableAutoRenew;return r=r.replace("%s",e),confirm(r)}))}));