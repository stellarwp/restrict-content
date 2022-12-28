<?php
/**
 * Shortcodes
 *
 * @package     Restrict Content Pro
 * @subpackage  Shortcodes
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

use RCP\Membership_Level;

add_filter( 'rcp_restrict_shortcode_return', 'wpautop' );
add_filter( 'rcp_restrict_shortcode_return', 'do_shortcode' );
add_filter( 'widget_text', 'do_shortcode' );


/**
 * Restricting content to registered users and or user roles
 *
 * @param array  $atts    Shortcode attributes.
 * @param string $content Shortcode content.
 *
 * @return string
 */
function rcp_restrict_shortcode( $atts, $content = null ) {

	$atts = shortcode_atts(
		array(
			'userlevel'    => 'none',
			'message'      => '',
			'paid'         => false,
			'level'        => 0,
			'subscription' => '',
		),
		$atts,
		'restrict'
	);

	global $rcp_options, $user_ID;

	if ( strlen( $atts['message'] ) > 0 ) {
		$teaser = $atts['message'];
	} else {
		$teaser = rcp_get_restricted_content_message( ! empty( $atts['paid'] ) );
	}

	$subscriptions = array_map( 'trim', explode( ',', $atts['subscription'] ) );

	$has_access = false;
	$classes    = 'rcp_restricted';

	$customer         = rcp_get_customer(); // currently logged in customer
	$is_active        = rcp_user_has_active_membership();
	$has_access_level = rcp_user_has_access( get_current_user_id(), $atts['level'] );

	if ( $atts['paid'] ) {

		if ( rcp_user_has_paid_membership() && $has_access_level ) {
			$has_access = true;
		}

		$classes = 'rcp_restricted rcp_paid_only';

	} elseif ( $has_access_level ) {

		$has_access = true;
	}

	if ( ! empty( $subscriptions ) && ! empty( $subscriptions[0] ) ) {
		if ( $is_active && ! empty( $customer ) && count( array_intersect( rcp_get_customer_membership_level_ids( $customer->get_id() ), $subscriptions ) ) ) {
			$has_access = true;
		} else {
			$has_access = false;
		}
	}

	if ( $atts['userlevel'] === 'none' && ! is_user_logged_in() ) {
		$has_access = false;
	}
	if ( 'none' != $atts['userlevel'] ) {
		$levels                  = array_map( 'trim', explode( ',', $atts['userlevel'] ) );
		$is_level_number         = check_array_only_numbers( $levels );
		$level_exists            = false;
		$user_memberships_levels = array();

		// Check for logged-in users.
		if ( is_object( $customer ) ) {
			if ( $is_level_number ) {
				// Get the customer levels.
				$user_memberships_levels = rcp_get_member_levels( $customer );
				// Check for values in $user_memberships_levels.
				if ( ! empty( $user_memberships_levels ) ) {
					$level_exists = true;
				}

				if ( $level_exists ) {
					foreach ( $levels as $level ) {
						// Check all the users levels against the current level defined in the shortcode attributes.
						if ( in_array( absint( $level ), $user_memberships_levels ) ) {
							$has_access = true;
							break;
						} else {
							$has_access = false;
						}
					}
				}
			} else {
				foreach ( $levels as $level ) {
					if ( current_user_can( strtolower( $level ) ) ) {
						$has_access = true;
						break;
					} else {
						$has_access = false;
					}
				}
			}
		}
	}

	// No access if pending email verification.
	if ( ! empty( $customer ) && $customer->is_pending_verification() ) {
		$has_access = false;
	}

	if ( current_user_can( 'manage_options' ) ) {
		$has_access = true;
	}

	$has_access = (bool) apply_filters( 'rcp_restrict_shortcode_has_access', $has_access, $user_ID, $atts );

	if ( $has_access ) {
		return apply_filters( 'rcp_restrict_shortcode_return', $content );
	} else {
		return '<div class="' . esc_attr( $classes ) . '">' . rcp_format_teaser( $teaser ) . '</div>';
	}
}
add_shortcode( 'restrict', 'rcp_restrict_shortcode' );

/**
 * Check if all the elements in the array are numbers.
 *
 * @since 3.5.16
 * @param array $_array The array with user levels.
 * @return bool
 */
function check_array_only_numbers( $_array ) : bool {
	foreach ( $_array as $level ) {
		if ( ! is_numeric( $level ) ) {
			return false;
		}
	}

	return true;
}

/**
 * Get all the users levels for a customer. Only Memberships with "Active" memberships.
 *
 * @since 3.5.16
 *
 * @param object $_customer The Customer Object.
 *
 * @return array The customer memberships levels, empty otherwise.
 */
function rcp_get_member_levels( $_customer ) : array {
	$user_memberships_levels = array();
	$args                    = array(
		'status' => 'active',
	);

	$user_memberships = rcp_get_customer_memberships( $_customer->get_id(), $args );

	if ( ! empty( $user_memberships ) && is_array( $user_memberships ) ) {
		foreach ( $user_memberships as $user_membership ) {
			// Get the current user subscription.
			$membership_id = $user_membership->get_object_id();
			// Get the current subscription level.
			$membership_level = rcp_get_membership_level( $membership_id );
			// Get the current user membership level.
			$user_level                = $membership_level->get_access_level();
			$user_memberships_levels[] = $user_level;
		}
	}

	return $user_memberships_levels;
}

/**
 * Shows content only to active, paid users
 *
 * @param array  $atts    Shortcode attributes.
 * @param string $content Shortcode content.
 *
 * @return string
 */
function rcp_is_paid_user_shortcode( $atts, $content = null ) {

	if ( rcp_user_has_paid_membership() ) {
		return do_shortcode( $content );
	}

	return '';
}
add_shortcode( 'is_paid', 'rcp_is_paid_user_shortcode' );


/**
 * Shows content only to logged-in free users, and can hide from paid
 *
 * @param array  $atts    Shortcode attributes.
 * @param string $content Shortcode content.
 *
 * @return string
 */
function rcp_is_free_user_shortcode( $atts, $content = null ) {

	$atts = shortcode_atts(
		array(
			'hide_from_paid' => true,
		),
		$atts,
		'is_free'
	);

	if ( $atts['hide_from_paid'] ) {
		if ( is_user_logged_in() && ! rcp_user_has_paid_membership() ) {
			return do_shortcode( $content );
		}
	} elseif ( is_user_logged_in() ) {
		return do_shortcode( $content );
	}

	return '';
}
add_shortcode( 'is_free', 'rcp_is_free_user_shortcode' );

/**
 * Shows content only to expired users
 *
 * @param array  $atts    Shortcode attributes.
 * @param string $content Shortcode content.
 *
 * @return string
 */
function rcp_is_expired_user_shortcode( $atts, $content = null ) {

	if ( rcp_user_has_expired_membership() ) {
		return do_shortcode( $content );
	}

	return '';
}
add_shortcode( 'is_expired', 'rcp_is_expired_user_shortcode' );


/**
 * Shows content only to not logged-in users
 *
 * @param array  $atts    Shortcode attributes.
 * @param string $content Shortcode content.
 *
 * @return string
 */
function rcp_not_logged_in( $atts, $content = null ) {
	if ( ! is_user_logged_in() ) {
		return do_shortcode( $content );
	}

	return '';
}
add_shortcode( 'not_logged_in', 'rcp_not_logged_in' );


/**
 * Allows content to be shown to only users that don't have an active subscription
 *
 * @param array  $atts    Shortcode attributes.
 * @param string $content Shortcode content.
 *
 * @return string
 */
function rcp_is_not_paid( $atts, $content = null ) {

	// If there are no paid, active memberships then show the content.
	if ( ! rcp_user_has_paid_membership() ) {
		return do_shortcode( $content );
	}

	return '';

}
add_shortcode( 'is_not_paid', 'rcp_is_not_paid' );


/**
 * Displays the currently logged-in user display-name
 *
 * @param array  $atts    Shortcode attributes.
 * @param string $content Shortcode content.
 *
 * @return string
 */
function rcp_user_name( $atts, $content = null ) {
	global $user_ID;

	if ( is_user_logged_in() ) {
		return get_userdata( $user_ID )->display_name;
	}

	return '';

}
add_shortcode( 'user_name', 'rcp_user_name' );


/**
 * Displays user registration form
 *
 * @param array  $atts    Shortcode attributes.
 * @param string $content Shortcode content.
 *
 * @return string
 */
function rcp_registration_form( $atts, $content = null ) {

	$customer          = rcp_get_customer(); // current customer
	$logged_in_header  = __( 'Register New Membership', 'rcp' );
	$registration_type = rcp_get_registration()->get_registration_type();
	$membership        = rcp_get_registration()->get_membership();

	if ( empty( $membership ) ) {
		$membership = ! empty( $customer ) ? rcp_get_customer_single_membership( $customer->get_id() ) : false;
	}

	if ( rcp_multiple_memberships_enabled() && 'renewal' == $registration_type ) {
		$logged_in_header = __( 'Renew Your Membership', 'rcp' );
	} elseif ( rcp_multiple_memberships_enabled() && 'upgrade' == $registration_type ) {
		$logged_in_header = __( 'Change Your Membership', 'rcp' );
	} elseif ( ! rcp_multiple_memberships_enabled() && ! empty( $membership ) ) {
		$logged_in_header = __( 'Upgrade or Renew Your Membership', 'rcp' );
	} elseif ( ! rcp_multiple_memberships_enabled() ) {
		$logged_in_header = __( 'Join Now', 'rcp' );
	}

	$atts = shortcode_atts(
		array(
			'id'                 => null, // Single specific level
			'ids'                => null, // Multiple specific levels
			'registered_message' => __( 'You are already registered and have an active subscription.', 'rcp' ),
			'logged_out_header'  => __( 'Register New Account', 'rcp' ),
			'logged_in_header'   => $logged_in_header,
		),
		$atts,
		'register_form'
	);

	global $user_ID;

	/*
	 * Only show the registration form if:
	 *
	 * 		- User does not have a membership; or
	 * 		- User has a membership and one of the following applies:
	 * 			- The membership has upgrades available; or
	 * 			- The membership can be renewed.
	 */
	if ( empty( $membership ) || ( ! empty( $membership ) && ( $membership->upgrade_possible() || $membership->can_renew() ) ) || rcp_multiple_memberships_enabled() ) {

		global $rcp_options, $rcp_load_css, $rcp_load_scripts;

		// set this to true so the CSS and JS scripts are loaded
		$rcp_load_css     = true;
		$rcp_load_scripts = true;

		$output = rcp_registration_form_fields( $atts['id'], $atts );

	} else {
		$output = $atts['registered_message'];
	}
	return $output;
}
add_shortcode( 'register_form', 'rcp_registration_form' );


/**
 * Displays stripe checkout form
 *
 * @param array  $atts    Shortcode attributes.
 * @param string $content Shortcode content.
 *
 * @since 2.5
 * @access public
 * @return string
 */
function rcp_register_form_stripe_checkout( $atts, $content = '' ) {
	global $rcp_options;

	if ( empty( $atts['id'] ) ) {
		return '';
	}

	// button is an alias for data-label
	if ( isset( $atts['button'] ) ) {
		$atts['data-label'] = $atts['button'];
	}

	$key = ( rcp_is_sandbox() ) ? $rcp_options['stripe_test_publishable'] : $rcp_options['stripe_live_publishable'];

	$customer         = rcp_get_customer_by_user_id(); // current customer
	$membership       = ! empty( $customer ) ? rcp_get_customer_single_membership( $customer->get_id() ) : false;
	$user             = wp_get_current_user();
	$membership_level = rcp_get_membership_level( $atts['id'] );

	if ( ! $membership_level instanceof Membership_Level ) {
		return '';
	}

	$amount      = $membership_level->get_price();
	$has_trialed = ! empty( $customer ) ? $customer->has_trialed() : false;
	$is_trial    = $membership_level->has_trial() && ! $has_trialed;

	if ( ! empty( $membership ) ) {
		if ( $membership->get_object_id() != $atts['id'] ) {
			// Only add the signup fee if this is not a renewal.
			$amount += $membership_level->get_fee();

			// Only add prorate credit if this is not a renewal & multiple memberships are disabled.
			if ( ! rcp_multiple_memberships_enabled() ) {
				$amount -= $membership->get_prorate_credit_amount();
			}
		}
	}

	if ( $amount < 0 || $is_trial ) {
		$amount = 0;
	}

	$data = wp_parse_args(
		$atts,
		array(
			'id'                     => 0,
			'data-key'               => $key,
			'data-level-id'          => $membership_level->get_id(),
			'data-name'              => $membership_level->get_name(),
			'data-description'       => $membership_level->get_description(),
			'data-label'             => sprintf( __( 'Join %s', 'rcp' ), $membership_level->get_name() ),
			'data-panel-label'       => empty( $amount ) ? __( 'Start Trial', 'rcp' ) : sprintf( __( 'Pay %s', 'rcp' ), rcp_currency_filter( $amount ) ),
			'data-amount'            => $amount * rcp_stripe_get_currency_multiplier(),
			'data-locale'            => 'auto',
			'data-allow-remember-me' => true,
			'data-currency'          => rcp_get_currency(),
		)
	);

	if ( empty( $data['data-email'] ) && ! empty( $user->user_email ) ) {
		$data['data-email'] = $user->user_email;
	}

	if ( empty( $data['data-image'] ) && $image = get_site_icon_url() ) {
		$data['data-image'] = $image;
	}

	/**
	 * Filters the Stripe Checkout data. This is basically irrelevant now, but keeping it for backwards compatibility.
	 *
	 * @param array $data
	 */
	$data = apply_filters( 'rcp_stripe_checkout_data', $data );

	global $rcp_load_css, $rcp_load_scripts;

	// set this to true so the CSS is loaded. Used for styling error messages.
	$rcp_load_css     = true;
	$rcp_load_scripts = true;
	rcp_show_error_messages( 'register' );

	ob_start();

	if ( ! empty( $membership ) && $membership->get_object_id() == $membership_level->get_id() && ! $membership->can_renew() ) : ?>

		<div class="rcp-stripe-checkout-notice"><?php _e( 'You are already subscribed.', 'rcp' ); ?></div>

		<?php
	else :
		// Load modal HTML.
		if ( ! has_action( 'wp_footer', 'rcp_insert_modal_wrapper' ) ) {
			add_action( 'wp_footer', 'rcp_insert_modal_wrapper' );
		}

		// Load stripe.js stuff
		$stripe_gateway = new RCP_Payment_Gateway_Stripe();
		$stripe_gateway->scripts();
		?>
		<div class="rcp-stripe-register" 
		<?php
		foreach ( $data as $label => $value ) {
			printf( ' %s="%s" ', esc_attr( sanitize_html_class( $label ) ), esc_attr( $value ) ); }
		?>
		>
			<?php do_action( 'register_form_stripe_fields', $data ); ?>
			<button type="button" class="rcp-stripe-register-submit-button"><?php echo esc_attr( $data['data-label'] ); ?></button>
		</div>
		<?php
	endif;

	/**
	 * Filters the shortcode HTML.
	 *
	 * @param string $content Final shortcode HTML.
	 * @param array  $atts    Shortcode attributes.
	 */
	return apply_filters( 'register_form_stripe', ob_get_clean(), $atts );
}
add_shortcode( 'register_form_stripe', 'rcp_register_form_stripe_checkout' );


/**
 * Displays user login form
 *
 * @param array  $atts    Shortcode attributes.
 * @param string $content Shortcode content.
 *
 * @return string
 */
function rcp_login_form( $atts, $content = null ) {

	global $post;

	$current_page = rcp_get_current_url();

	$atts = shortcode_atts(
		array(
			'redirect' => $current_page,
			'class'    => 'rcp_form',
		),
		$atts,
		'login_form'
	);

	$output = '';

	global $rcp_load_css;

	// set this to true so the CSS is loaded
	$rcp_load_css = true;

	return rcp_login_form_fields(
		array(
			'redirect' => $atts['redirect'],
			'class'    => $atts['class'],
		)
	);

}
add_shortcode( 'login_form', 'rcp_login_form' );


/**
 * Displays a password reset form
 *
 * @access public
 * @return string
 */
function rcp_reset_password_form() {
	if ( is_user_logged_in() ) {

		global $rcp_load_css, $rcp_load_scripts;
		// Load CSS and scripts.
		$rcp_load_css     = true;
		$rcp_load_scripts = true;

		// get the password reset form fields
		$output = rcp_change_password_form();

		return $output;
	}
}
add_shortcode( 'password_form', 'rcp_reset_password_form' );


/**
 * Displays a list of premium posts
 *
 * @access public
 * @return string
 */
function rcp_list_paid_posts() {
	$paid_posts = rcp_get_paid_posts();
	$list       = '';
	if ( $paid_posts ) {
		$list .= '<ul class="rcp_paid_posts">';
		foreach ( $paid_posts as $post_id ) {
			$list .= '<li><a href="' . esc_url( get_permalink( $post_id ) ) . '">' . get_the_title( $post_id ) . '</a></li>';
		}
		$list .= '</ul>';
	}
	return $list;
}
add_shortcode( 'paid_posts', 'rcp_list_paid_posts' );


/**
 * Displays the current user's subscription details
 * templates/subscription.php
 *
 * @param array  $atts    Shortcode attributes.
 * @param string $content Shortcode content.
 *
 * @return string
 */
function rcp_user_subscription_details( $atts, $content = null ) {

	$atts = shortcode_atts(
		array(
			'option' => '',
		),
		$atts,
		'subscription_details'
	);

	global $user_ID, $rcp_options, $rcp_load_css;

	$rcp_load_css = true;

	ob_start();

	if ( is_user_logged_in() ) {

		rcp_get_template_part( 'subscription' );

	} else {

		echo rcp_login_form_fields();

	}

	return ob_get_clean();
}
add_shortcode( 'subscription_details', 'rcp_user_subscription_details' );


/**
 * Profile Editor Shortcode
 *
 * Outputs the RCP Profile Editor to allow users to amend their details from the front-end
 * templates/profile-editor.php
 *
 * @param array  $atts    Shortcode attributes.
 * @param string $content Shortcode content.
 *
 * @since 1.5
 * @access public
 * @return string
 */
function rcp_profile_editor_shortcode( $atts, $content = null ) {

	global $rcp_load_css;

	$rcp_load_css = true;

	ob_start();

	rcp_get_template_part( 'profile', 'editor' );

	return ob_get_clean();
}
add_shortcode( 'rcp_profile_editor', 'rcp_profile_editor_shortcode' );


/**
 * Update card form short code
 *
 * Displays a form to update the billing credit / debit card.
 * templates/card-update-form.php
 *
 * @param array  $atts    Shortcode attributes.
 * @param string $content Shortcode content.
 *
 * @since 2.1
 * @access public
 * @return string
 */
function rcp_update_billing_card_shortcode( $atts, $content = null ) {
	global $rcp_load_css, $rcp_load_scripts, $rcp_membership;

	ob_start();

	$membership_id = isset( $_GET['membership_id'] ) ? absint( $_GET['membership_id'] ) : false;

	if ( empty( $membership_id ) ) {
		$customer   = rcp_get_customer(); // currently logged in customer
		$membership = ( ! empty( $customer ) ) ? rcp_get_customer_single_membership( $customer->get_id() ) : false;
	} else {
		$membership = rcp_get_membership( $membership_id );
	}

	if ( is_object( $membership ) && $membership->can_update_billing_card() ) {

		/*
		 * Membership can be found and the billing card can be updated.
		 */

		if ( $membership->get_user_id() != get_current_user_id() ) {
			return __( 'You do not have permission to perform this action.', 'rcp' );
		}

		$rcp_membership   = $membership;
		$rcp_load_css     = true;
		$rcp_load_scripts = true;

		do_action( 'rcp_before_update_billing_card_form', $membership );

		if ( isset( $_GET['card'] ) ) {

			switch ( $_GET['card'] ) {

				case 'updated':
					echo '<p class="rcp_success"><span>' . __( 'Billing card updated successfully', 'rcp' ) . '</span></p>';

					break;

				case 'not-updated':
					if ( isset( $_GET['msg'] ) ) {
						$message = urldecode( sanitize_text_field( wp_unslash( $_GET['msg'] ) ) );
					} else {
						$message = __( 'Billing card could not be updated, please try again.', 'rcp' );
					}

					echo '<p class="rcp_error"><span>' . wp_kses_post( $message ) . '</span></p>';

					break;

			}
		}

		rcp_get_template_part( 'card-update', 'form' );
		do_action( 'rcp_after_update_billing_card_form', $membership );

	} elseif ( is_object( $membership ) && ! $membership->can_update_billing_card() ) {

		/*
		 * Membership can be found and the billing card CANNOT be updated.
		 */

		// Generic message first.
		$message = __( 'Updating billing card details is not supported by your chosen payment method.', 'rcp' );

		// Special PayPal message.
		if ( false !== strpos( $membership->get_gateway(), 'paypal' ) ) {
			$message = __( 'Your billing details can be updated from inside your PayPal account.', 'rcp' );
		}

		echo '<p class="rcp-update-billing-details-unsupported">' . wp_kses_post( $message ) . '</p>';

	} elseif ( empty( $membership ) && is_user_logged_in() ) {

		/*
		 * Can't find the membership record at all.
		 */

	}

	return ob_get_clean();
}
add_shortcode( 'card_details', 'rcp_update_billing_card_shortcode' ); // Old version
add_shortcode( 'rcp_update_card', 'rcp_update_billing_card_shortcode' );

/**
 * Show Customer's Membership Level ID Shortcode
 *
 * Note: This is the ID of the corresponding membership level - NOT the ID of the membership record itself.
 *
 * @since 2.5
 * @access public
 *
 * @return string
 */
function rcp_user_subscription_id_shortcode() {
	if ( ! is_user_logged_in() ) {
		return '';
	}

	$customer = rcp_get_customer(); // currently logged in customer

	if ( empty( $customer ) ) {
		return '';
	}

	$membership = rcp_get_customer_single_membership( $customer->get_id() );

	if ( empty( $membership ) ) {
		return '';
	}

	return $membership->get_object_id();
}
add_shortcode( 'subscription_id', 'rcp_user_subscription_id_shortcode' );


/**
 * Show Customer's Membership Level Name Shortcode
 *
 * @since 2.5
 * @access public
 *
 * @return string
 */
function rcp_user_subscription_name_shortcode() {
	if ( ! is_user_logged_in() ) {
		return '';
	}

	$customer = rcp_get_customer(); // currently logged in customer

	if ( empty( $customer ) ) {
		return '';
	}

	$membership = rcp_get_customer_single_membership( $customer->get_id() );

	if ( empty( $membership ) ) {
		return '';
	}

	return $membership->get_membership_level_name();
}
add_shortcode( 'subscription_name', 'rcp_user_subscription_name_shortcode' );


/**
 * Show User's Expiration Shortcode
 *
 * @since 2.5
 * @access public
 *
 * @return string
 */
function rcp_user_expiration_shortcode() {
	if ( ! is_user_logged_in() ) {
		return '';
	}

	$customer = rcp_get_customer(); // currently logged in customer

	if ( empty( $customer ) ) {
		return '';
	}

	$membership = rcp_get_customer_single_membership( $customer->get_id() );

	if ( empty( $membership ) ) {
		return '';
	}

	return $membership->get_expiration_date();
}
add_shortcode( 'user_expiration', 'rcp_user_expiration_shortcode' );

/**
 * Loads the scripts for the Stripe Checkout shortcode.
 * Hooked into wp_footer via rcp_register_form_stripe_checkout().
 *
 * @deprecated 3.2
 *
 * @since 2.9.11
 */
function rcp_stripe_checkout_shortcode_scripts() {

	wp_print_scripts( 'jquery-blockui' );
	?>

	<script>
		let $ = jQuery;

		let stripeCheckoutHelper = {
			init: function() {
				let stripeCheckoutButton = document.querySelectorAll( '.stripe-button-el' );

				if( ! stripeCheckoutButton ) {
					return;
				}

				stripeCheckoutButton.forEach( function( element ) {
					element.addEventListener( 'click', function( event ) {
						event.preventDefault();
						$.blockUI({
							message: '<?php _e( 'Please wait . . . ', 'rcp' ); ?>',
							css: {
								border: 'none',
								padding: '15px',
								backgroundColor: '#000',
								opacity: 0.75,
								color: '#fff'
							}
						} );
					} );
				} );
			}
		};

		$( document ).ready( function() {
			stripeCheckoutHelper.init();
			$( document ).on( 'DOMNodeRemoved', '.stripe_checkout_app', function() {
				let stripeTokenElement = document.getElementsByName( 'stripeToken' );
				if( ! stripeTokenElement || stripeTokenElement.length === 0 ) {
					$( 'body' ).unblock( { fadeOut: 0 } );
				}
			} );
		} );

	</script>
	<?php
}

/**
 * Add the modal HTML to the footer so we can populate and display it when the trigger button is clicked.
 *
 * @see rcp_register_form_stripe_checkout()
 *
 * @since 3.2
 * @return void
 */
function rcp_insert_modal_wrapper() {
	global $rcp_options;

	$stripe_gateway = new RCP_Payment_Gateway_Stripe();
	?>
	<div class="rcp-modal-wrapper">
		<div class="rcp-modal" style="display: none;">
			<form id="rcp_registration_form" class="rcp_form" method="POST">
				<div class="rcp-modal-inner">
					<div class="rcp-modal-header">
						<span class="rcp-modal-close" aria-label="<?php esc_attr_e( 'Close', 'rcp' ); ?>" role="button">&times;</span>
						<div class="rcp-modal-membership-details">
							<p class="rcp-modal-membership-name" style="display: none;"></p>
							<p class="rcp-modal-membership-description" style="display: none;"></p>
						</div>
					</div>

					<div class="rcp-modal-body">
						<?php
						if ( ! is_user_logged_in() ) :
							$rcp_user_email = empty( $_POST['rcp_user_email'] ) ? '' : sanitize_text_field( wp_unslash( $_POST['rcp_user_email'] ) );
							?>
							<fieldset class="rcp_user_fieldset">
								<p id="rcp_user_email_wrap">
									<label for="rcp_user_email"><?php echo apply_filters( 'rcp_registration_email_label', __( 'Email', 'rcp' ) ); ?></label>
									<input name="rcp_user_email" id="rcp_user_email" class="required" type="text" value="<?php echo esc_attr( $rcp_user_email ); ?>" />
							</fieldset>
						<?php endif; ?>
						<?php echo $stripe_gateway->fields(); ?>

						<?php if ( ! empty( $rcp_options['enable_terms'] ) ) : ?>
							<fieldset class="rcp_agree_to_terms_fieldset">
								<p id="rcp_agree_to_terms_wrap">
									<input type="checkbox" id="rcp_agree_to_terms" name="rcp_agree_to_terms" value="1">
									<label for="rcp_agree_to_terms">
										<?php
										if ( ! empty( $rcp_options['terms_link'] ) ) {
											echo '<a href="' . esc_url( $rcp_options['terms_link'] ) . '" target="_blank">';
										}

										if ( ! empty( $rcp_options['terms_label'] ) ) {
											echo wp_kses_post( $rcp_options['terms_label'] );
										} else {
											_e( 'I agree to the terms and conditions', 'rcp' );
										}

										if ( ! empty( $rcp_options['terms_link'] ) ) {
											echo '</a>';
										}
										?>
									</label>
								</p>
							</fieldset>
						<?php endif; ?>

						<?php if ( ! empty( $rcp_options['enable_privacy_policy'] ) ) : ?>
							<fieldset class="rcp_agree_to_privacy_policy_fieldset">
								<p id="rcp_agree_to_privacy_policy_wrap">
									<input type="checkbox" id="rcp_agree_to_privacy_policy" name="rcp_agree_to_privacy_policy" value="1">
									<label for="rcp_agree_to_privacy_policy">
										<?php
										if ( ! empty( $rcp_options['privacy_policy_link'] ) ) {
											echo '<a href="' . esc_url( $rcp_options['privacy_policy_link'] ) . '" target="_blank">';
										}

										if ( ! empty( $rcp_options['privacy_policy_label'] ) ) {
											echo wp_kses_post( $rcp_options['privacy_policy_label'] );
										} else {
											_e( 'I agree to the privacy policy', 'rcp' );
										}

										if ( ! empty( $rcp_options['privacy_policy_link'] ) ) {
											echo '</a>';
										}
										?>
									</label>
								</p>
							</fieldset>
						<?php endif; ?>

						<?php
						/**
						 * Adds content before the Stripe Checkout submit button.
						 *
						 * @since 3.3.11
						 */
						do_action( 'rcp_before_stripe_checkout_submit_field' );
						?>

						<input type="hidden" id="rcp-stripe-checkout-level-id" name="rcp_level" value="0" />
						<input type="hidden" name="rcp_register_nonce" value="<?php echo esc_attr( wp_create_nonce( 'rcp-register-nonce' ) ); ?>"/>
						<input type="hidden" name="rcp_gateway" value="stripe"/>
						<input type="hidden" name="rcp_stripe_checkout" value="1"/>

						<p id="rcp_submit_wrap">
							<input type="submit" id="rcp_submit" class="rcp-modal-submit" value="<?php esc_attr_e( 'Submit Payment', 'rcp' ); ?>" />
						</p>
					</div>
				</div>
			</form>
		</div>
	</div>
	<?php
}
