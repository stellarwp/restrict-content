<?php
/**
 * WooCommerce - No Access Message
 *
 * This template is used to display the restriction message if an unauthorized user
 * tries to view a restricted product.
 *
 * For modifying this template, please see: http://docs.restrictcontentpro.com/article/1738-template-files
 *
 * @package     Restrict Content Pro
 * @subpackage  Templates/WooCommerce No Access
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

global $rcp_options;
$message = rcp_get_restricted_content_message( true );
?>

<div class="rcp-woocommerce-no-access">
	<?php echo rcp_format_teaser( $message ); ?>
</div>