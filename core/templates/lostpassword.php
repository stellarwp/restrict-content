<?php
/**
 * Lost Password Form
 *
 * Template for displaying the lost password form. This is used in the [login_form] shortcode
 * when a user clicks the "lost your password" link.
 *
 * For modifying this template, please see: http://docs.restrictcontentpro.com/article/1738-template-files
 *
 * @package     Restrict Content Pro
 * @subpackage  Templates/Lost Password
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

if( ! is_user_logged_in() ) : ?>

    <?php rcp_show_error_messages( 'lostpassword' ); ?>

    <form id="rcp_lostpassword_form" class="rcp_form" method="POST" action="<?php echo esc_url( add_query_arg( 'rcp_action', 'lostpassword') ); ?>">

        <?php do_action( 'rcp_before_lostpassword_form_fields' ); ?>

        <fieldset class="rcp_lostpassword_data">
            <p>
                <label for="rcp_user_login"><?php _e( 'Username or E-mail:', 'rcp' ); ?></label>
                <input name="rcp_user_login" id="rcp_user_login" class="required" type="text"/>
            </p>
            <?php do_action( 'rcp_lostpassword_form_fields_before_submit' ); ?>
            <p>
                <input type="hidden" name="rcp_action" value="lostpassword"/>
                <input type="hidden" name="rcp_redirect" value="<?php echo esc_url( rcp_get_current_url() ); ?>"/>
                <input type="hidden" name="rcp_lostpassword_nonce" value="<?php echo wp_create_nonce( 'rcp-lostpassword-nonce' ); ?>"/>
                <input id="rcp_lostpassword_submit" class="rcp-button" type="submit" value="<?php esc_attr_e( 'Request Password Reset', 'rcp' ); ?>"/>
            </p>
            <?php do_action( 'rcp_lostpassword_form_fields_after_submit' ); ?>
        </fieldset>

        <?php do_action( 'rcp_after_lostpassword_form_fields' ); ?>

    </form>
<?php else : ?>
    <div class="rcp_logged_in"><a href="<?php echo wp_logout_url( home_url() ); ?>"><?php _e( 'Log out', 'rcp' ); ?></a></div>
<?php endif; ?>
