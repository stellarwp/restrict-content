<?php
/**
 * Restrict Post Type View
 *
 * HTML display of the restrict post type page.
 *
 * @package     restrict-content-pro
 * @subpackage  Admin/Restrict Post Type View
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     GPL2+
 * @since       2.9
 */

$screen            = get_current_screen();
$post_type         = ! empty( $screen->post_type ) ? $screen->post_type : 'post';
$restrictions      = rcp_get_post_type_restrictions( $post_type );
$is_paid           = array_key_exists( 'is_paid', $restrictions ) ? true : false;
$sub_levels        = array_key_exists( 'subscription_level', $restrictions ) ? $restrictions['subscription_level'] : false;
$set_level         = is_array( $sub_levels ) ? '' : $sub_levels;
$access_level      = array_key_exists( 'access_level', $restrictions ) ? $restrictions['access_level'] : false;
$access_level      = is_numeric( $access_level ) ? absint( $access_level ) : '';
$user_role         = array_key_exists( 'user_level', $restrictions ) ? $restrictions['user_level'] : false;
$access_display    = is_numeric( $access_level ) ? '' : ' style="display:none;"';
$level_set_display = ! empty( $sub_levels ) || ! empty( $is_paid ) ? '' : ' style="display:none;"';
$levels_display    = is_array( $sub_levels ) ? '' : ' style="display:none;"';
$role_set_display  = '' != $user_role ? '' : ' style="display:none;"';
?>
<form method="POST" action="">
	<p><?php _e( 'Use this form to restrict an entire post type. If set to "Everyone", you will be able to configure restrictions on a post-by-post basis.', 'rcp' ); ?></p>
	<div id="rcp-metabox-field-restrict-by" class="rcp-metabox-field">
		<p><strong><?php _e( 'Member access options', 'rcp' ); ?></strong></p>
		<p>
			<?php _e( 'Select who should have access to this content.', 'rcp' ); ?>
			<span alt="f223" class="rcp-help-tip dashicons dashicons-editor-help" title="<?php _e( '<strong>Membership level</strong>: a membership level refers to a membership option. For example, you might have a Gold, Silver, and Bronze membership level. <br/><br/><strong>Access Level</strong>: refers to a tiered system where a member\'s ability to view content is determined by the access level assigned to their account. A member with an access level of 5 can view content assigned to access levels of 5 and lower.', 'rcp' ); ?>"></span>
		</p>
		<p>
			<label for="rcp-restrict-by" class="screen-reader-text"><?php _e( 'Select who should have access to this content', 'rcp' ); ?></label>
			<select id="rcp-restrict-by" name="rcp_restrict_by">
				<option value="unrestricted" <?php selected( true, ( empty( $sub_levels ) && empty( $access_level ) && empty( $is_paid ) ) ); ?>><?php _e( 'Everyone (or configure posts individually)', 'rcp' ); ?></option>
				<option value="subscription-level"<?php selected( true, ! empty( $sub_levels ) || ! empty( $is_paid ) ); ?>><?php _e( 'Members of membership level(s)', 'rcp' ); ?></option>
				<option value="access-level"<?php selected( true, is_numeric( $access_level ) ); ?>><?php _e( 'Members with an access level', 'rcp' ); ?></option>
				<option value="registered-users"<?php selected( true, empty( $sub_levels ) && ! is_numeric( $access_level ) && ! empty( $user_role ) && 'All' !== $user_role ); ?>><?php _e( 'Members with a certain role', 'rcp' ); ?></option>
			</select>
		</p>
	</div>
	<div id="rcp-metabox-field-levels" class="rcp-metabox-field"<?php echo $level_set_display; ?>>
		<label for="rcp_subscription_level_any">
			<input type="radio" name="rcp_subscription_level_any_set" id="rcp_subscription_level_any" value="any"<?php checked( 'any', $set_level ); ?>/>
			&nbsp;<?php _e( 'Members of any membership level(s)', 'rcp' ); ?><br/>
		</label>
		<label for="rcp_subscription_level_any_paid">
			<input type="radio" name="rcp_subscription_level_any_set" id="rcp_subscription_level_any_paid" value="any-paid"<?php checked( true, $set_level == 'any-paid' || ( ! empty( $is_paid ) && 'any' !== $sub_levels ) ); ?>/>
			&nbsp;<?php _e( 'Members of any paid membership level(s)', 'rcp' ); ?><br/>
		</label>
		<label for="rcp_subscription_level_specific">
			<input type="radio" name="rcp_subscription_level_any_set" id="rcp_subscription_level_specific" value="specific"<?php checked( true, is_array( $sub_levels ) ); ?>/>
			&nbsp;<?php _e( 'Members of specific membership levels', 'rcp' ); ?><br/>
		</label>
		<p class="rcp-subscription-levels"<?php echo $levels_display; ?>>
			<?php foreach ( rcp_get_membership_levels( array( 'number' => 999 ) ) as $level ) : ?>
				<label for="rcp_subscription_level_<?php echo esc_attr( $level->get_id() ); ?>">
					<input type="checkbox" name="rcp_subscription_level[]"<?php checked( true, in_array( $level->get_id(), (array) $sub_levels ) ); ?> class="rcp_subscription_level" id="rcp_subscription_level_<?php echo esc_attr( $level->get_id() ); ?>" value="<?php echo esc_attr( $level->get_id() ); ?>" data-price="<?php echo esc_attr( $level->get_price() ); ?>"/>
					&nbsp;<?php echo esc_html( $level->get_name() ); ?><br/>
				</label>
			<?php endforeach; ?>
		</p>
	</div>
	<div id="rcp-metabox-field-access-levels" class="rcp-metabox-field"<?php echo $access_display; ?>>
		<p>
			<label for="rcp-access-level-field" class="screen-reader-text"><?php _e( 'Require members to have this access level or higher.', 'rcp' ); ?></label>
			<select name="rcp_access_level" id="rcp-access-level-field">
				<?php foreach ( rcp_get_access_levels() as $key => $access_level_label ) : ?>
					<option id="rcp_access_level<?php echo $key; ?>" value="<?php echo esc_attr( $key ); ?>"<?php selected( $key, $access_level ); ?>><?php printf( __( '%s and higher', 'rcp' ), $key ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
	</div>
	<div id="rcp-metabox-field-role" class="rcp-metabox-field"<?php echo $role_set_display; ?>>
		<p>
			<label for="rcp-user-level-field"><?php _e( 'Require member to have capabilities from this user role.', 'rcp' ); ?></label>
		</p>
		<p>
			<?php
			$roles          = get_editable_roles();
			$roles          = array_merge( array( 'all' => array( 'name' => 'Any' ) ), $roles );
			$selected_roles = is_array( $user_role ) ? $user_role : array( strtolower( $user_role ) );
			foreach(  $roles as $key => $role ) : ?>
				<label for="rcp_user_level_<?php echo esc_attr( $key ); ?>">
					<input type="checkbox" name="rcp_user_level[]" id="rcp_user_level_<?php echo esc_attr( $key ); ?>" class="rcp-user-role" value="<?php echo esc_attr( $key ); ?>"<?php checked( true, in_array( $key, $selected_roles ) ); ?>>
					&nbsp;<?php echo translate_user_role( $role['name'] ); ?><br/>
				</label>
			<?php endforeach; ?>
		</p>
	</div>

	<?php
	/**
	 * Used to insert additional post type restriction settings or notes.
	 *
	 * @param string $post_type    Current post type.
	 * @param array  $restrictions Array of restrictions for this post type.
	 *
	 * @since 2.9
	 */
	do_action( 'rcp_post_type_restrictions', $post_type, $restrictions );
	?>

	<p>
		<?php wp_nonce_field( 'rcp_save_post_type_restrictions', 'rcp_save_post_type_restrictions_nonce' ); ?>
		<input type="hidden" name="rcp_post_type" value="<?php echo esc_attr( $post_type ); ?>">
		<input type="hidden" name="rcp-action" value="save_post_type_restrictions"/>
		<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Save', 'rcp' ); ?>">
	</p>
</form>
