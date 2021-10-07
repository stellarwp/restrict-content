<?php
/**
 * Lost Password - Check Email
 *
 * This message is shown after filling out the lost password form.
 *
 * For modifying this template, please see: http://docs.restrictcontentpro.com/article/1738-template-files
 *
 * @package     Restrict Content Pro
 * @subpackage  Templates/Lost Password/Check Email
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

if( ! is_user_logged_in() ) : ?>

    <?php do_action( 'rcp_before_lostpassword_checkemail_message' ); ?>

    <p><?php _e('Check your e-mail for the confirmation link.', 'rcp'); ?></p>

    <?php do_action( 'rcp_after_lostpassword_checkemail_message' ); ?>

<?php else : ?>
    <div class="rcp_logged_in"><a href="<?php echo wp_logout_url( home_url() ); ?>"><?php _e( 'Log out', 'rcp' ); ?></a></div>
<?php endif; ?>
