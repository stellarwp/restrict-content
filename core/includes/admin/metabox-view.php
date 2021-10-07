<?php
/**
 * Meta Box View
 *
 * HTML display of the meta box.
 *
 * @package     Restrict Content Pro
 * @subpackage  Admin/Meta Box View
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

global $rcp_options;
$pt_restrictions   = rcp_get_post_type_restrictions( get_post_type( get_the_ID() ) );
$disabled          = ! empty( $pt_restrictions ) ? ' disabled="disabled"' : '';

// If entire post type is restricted, use those settings. Otherwise use individual post settings.
$is_paid           = ( ! empty( $pt_restrictions ) && array_key_exists( 'is_paid', $pt_restrictions ) ) ? $pt_restrictions['is_paid'] : get_post_meta( get_the_ID(), '_is_paid', true );
$sub_levels        = ( ! empty( $pt_restrictions ) && array_key_exists( 'subscription_level', $pt_restrictions ) ) ? $pt_restrictions['subscription_level'] : get_post_meta( get_the_ID(), 'rcp_subscription_level', true );
$set_level         = is_array( $sub_levels ) ? '' : $sub_levels;
$access_level      = ( ! empty( $pt_restrictions ) && array_key_exists( 'access_level', $pt_restrictions ) ) ? $pt_restrictions['access_level'] : get_post_meta( get_the_ID(), 'rcp_access_level', true );
$access_level      = is_numeric( $access_level ) ? absint( $access_level ) : '';
$content_excerpts  = isset( $rcp_options['content_excerpts'] ) ? $rcp_options['content_excerpts'] : 'individual';
$show_excerpt      = 'always' === $content_excerpts || ( 'individual' === $content_excerpts && get_post_meta( get_the_ID(), 'rcp_show_excerpt', true ) );
$user_role         = ( ! empty( $pt_restrictions ) && array_key_exists( 'user_level', $pt_restrictions ) ) ? $pt_restrictions['user_level'] : get_post_meta( get_the_ID(), 'rcp_user_level', true );
$access_display    = is_numeric( $access_level ) ? '' : ' style="display:none;"';
$level_set_display = ! empty( $sub_levels ) || ! empty( $is_paid ) ? '' : ' style="display:none;"';
$levels_display    = is_array( $sub_levels ) ? '' : ' style="display:none;"';
$role_set_display  = '' != $user_role ? '' : ' style="display:none;"';

/* If nothing is set for "Members of membership level(s)", default the choice
 * to "Members of any membership level(s)".
 */
if ( empty( $sub_levels ) && empty( $set_level ) ) {
	$set_level = 'any';
}
?>
<div id="rcp-metabox-field-restrict-by" class="rcp-metabox-field">
	<?php if ( ! empty( $pt_restrictions ) ) : ?>
		<p><?php printf( __( 'This entire post type is restricted. Visit the <a href="%s">post type\'s restriction page</a> to change the settings.', 'rcp' ), esc_url( rcp_get_restrict_post_type_page_url( get_post_type() ) ) ); ?></p>
	<?php endif; ?>
	<p><strong><?php _e( 'Member access options', 'rcp' ); ?></strong></p>
	<p>
		<?php _e( 'Select who should have access to this content.', 'rcp' ); ?>
		<span alt="f223" class="rcp-help-tip dashicons dashicons-editor-help" title="<?php _e( '<strong>Membership level</strong>: a membership level refers to a membership option. For example, you might have a Gold, Silver, and Bronze membership level. <br/><br/><strong>Access Level</strong>: refers to a tiered system where a member\'s ability to view content is determined by the access level assigned to their account. A member with an access level of 5 can view content assigned to access levels of 5 and lower.', 'rcp' ); ?>"></span>
	</p>
	<p>
		<select id="rcp-restrict-by" name="rcp_restrict_by" <?php echo $disabled; ?>>
			<option value="unrestricted" <?php selected( true, ( empty( $sub_levels ) && empty( $access_level ) && empty( $is_paid ) ) ); ?>><?php _e( 'Everyone', 'rcp' ); ?></option>
			<option value="subscription-level"<?php selected( true, ! empty( $sub_levels ) || ! empty( $is_paid ) ); ?>><?php _e( 'Members of membership level(s)', 'rcp' ); ?></option>
			<option value="access-level"<?php selected( true, is_numeric( $access_level ) ); ?>><?php _e( 'Members with an access level', 'rcp' ); ?></option>
			<option value="registered-users"<?php selected( true, empty( $sub_levels ) && ! is_numeric( $access_level ) && ! empty( $user_role ) && 'All' !== $user_role ); ?>><?php _e( 'Members with a certain role', 'rcp' ); ?></option>
		</select>
	</p>
</div>
<div id="rcp-metabox-field-levels" class="rcp-metabox-field"<?php echo $level_set_display; ?>>
	<label for="rcp_subscription_level_any">
		<input type="radio" name="rcp_subscription_level_any_set" id="rcp_subscription_level_any" value="any"<?php checked( 'any', $set_level ); echo $disabled; ?>/>
		&nbsp;<?php _e( 'Members of any membership level(s)', 'rcp' ); ?><br/>
	</label>
	<label for="rcp_subscription_level_any_paid">
		<input type="radio" name="rcp_subscription_level_any_set" id="rcp_subscription_level_any_paid" value="any-paid"<?php checked( true, $set_level == 'any-paid' || ( ! empty( $is_paid ) && 'any' !== $sub_levels ) ); echo $disabled; ?>/>
		&nbsp;<?php _e( 'Members of any paid membership level(s)', 'rcp' ); ?><br/>
	</label>
	<label for="rcp_subscription_level_specific">
		<input type="radio" name="rcp_subscription_level_any_set" id="rcp_subscription_level_specific" value="specific"<?php checked( true, is_array( $sub_levels ) ); echo $disabled; ?>/>
		&nbsp;<?php _e( 'Members of specific membership levels', 'rcp' ); ?><br/>
	</label>
	<p class="rcp-subscription-levels"<?php echo $levels_display; ?>>
		<?php foreach( rcp_get_membership_levels( array( 'number' => 999 ) ) as $level ) : ?>
			<label for="rcp_subscription_level_<?php echo esc_attr( $level->get_id() ); ?>">
				<input type="checkbox" name="rcp_subscription_level[]"<?php checked( true, in_array( $level->get_id(), (array) $sub_levels ) ); ?> class="rcp_subscription_level" id="rcp_subscription_level_<?php echo esc_attr( $level->get_id() ); ?>" value="<?php echo esc_attr( $level->get_id() ); ?>" data-price="<?php echo esc_attr( $level->get_price() ); ?>"<?php echo $disabled; ?>/>
				&nbsp;<?php echo esc_html( $level->get_name() ); ?><br/>
			</label>
		<?php endforeach; ?>
	</p>
</div>
<div id="rcp-metabox-field-access-levels" class="rcp-metabox-field"<?php echo $access_display; ?>>
	<p>
		<select name="rcp_access_level" id="rcp-access-level-field" <?php echo $disabled; ?>>
			<?php foreach( rcp_get_access_levels() as $key => $access_level_label ) : ?>
				<option id="rcp_access_level<?php echo $key; ?>" value="<?php echo esc_attr( $key ); ?>"<?php selected( $key, $access_level ); ?>><?php printf( __( '%s and higher', 'rcp' ), $key ); ?></option>
			<?php endforeach; ?>
		</select>
	</p>
</div>
<div id="rcp-metabox-field-role" class="rcp-metabox-field"<?php echo $role_set_display; ?>>
	<p>
		<span><?php _e( 'Require member to have capabilities from this user role.', 'rcp' ); ?></span>
	</p>
	<p>
		<?php
		$roles          = get_editable_roles();
		$roles          = array_merge( array( 'all' => array( 'name' => 'Any' ) ), $roles );
		$selected_roles = is_array( $user_role ) ? $user_role : array( strtolower( $user_role ) );
		foreach(  $roles as $key => $role ) : ?>
			<label for="rcp_user_level_<?php echo esc_attr( $key ); ?>">
				<input type="checkbox" name="rcp_user_level[]" id="rcp_user_level_<?php echo esc_attr( $key ); ?>" class="rcp-user-role" value="<?php echo esc_attr( $key ); ?>"<?php checked( true, in_array( $key, $selected_roles ) ); echo $disabled; ?>>
				&nbsp;<?php echo translate_user_role( $role['name'] ); ?><br/>
			</label>
		<?php endforeach; ?>
	</p>
</div>
<?php do_action( 'rcp_metabox_additional_options_before' ); ?>
<?php if( apply_filters( 'rcp_metabox_show_additional_options', true ) ) : ?>
	<div id="rcp-metabox-field-options" class="rcp-metabox-field">

		<p><strong><?php _e( 'Additional options', 'rcp' ); ?></strong></p>
		<p>
			<?php
			$disabled = ( 'always' === $content_excerpts || 'never' === $content_excerpts ) ? ' disabled="disabled"' : '';
			$message  = __( 'You can automatically enable or disable excerpts for all posts by adjusting your Content Excerpts setting in Restrict > Settings > Misc.', 'rcp' );

			if ( 'always' === $content_excerpts ) {
				$message = __( 'This option is disabled because excerpts are enabled for all posts. This can be changed in Restrict > Settings > Misc.', 'rcp' );
			} elseif ( 'never' === $content_excerpts ) {
				$message = __( 'This option is disabled because excerpts are disabled for all posts. This can be changed in Restrict > Settings > Misc.', 'rcp' );
			}
			?>
			<label for="rcp-show-excerpt">
				<input type="checkbox" name="rcp_show_excerpt" id="rcp-show-excerpt" value="1"<?php echo $disabled; checked( true, $show_excerpt ); ?>/>
				<?php _e( 'Show excerpt to members without access to this content.', 'rcp' ); ?>
			</label>
			<span alt="f223" class="rcp-help-tip dashicons dashicons-editor-help" title="<?php echo esc_attr( $message ); ?>"></span>
		</p>
		<p>
			<?php printf(
				__( 'Optionally use [restrict paid="true"] ... [/restrict] shortcode to restrict partial content. %sView documentation for additional options%s.', 'rcp' ),
				'<a href="' . esc_url( 'http://docs.restrictcontentpro.com/article/1593-restricting-post-and-page-content' ) . '" target="_blank">',
				'</a>'
			); ?>
		</p>
	</div>
<?php endif; ?>
