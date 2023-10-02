<?php
/**
 * Email Functions
 *
 * Functions for sending emails to members.
 *
 * @package     Restrict Content Pro
 * @subpackage  Email Functions
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Send membership email. Will send one of the following based on the membership status:
 * 		- Active Email
 * 		- Free Email
 * 		- Trial Email
 * 		- Expired Email
 * 		- Cancellation Email
 *
 * @param int|RCP_Membership $membership_id_or_object Membership ID or object.
 * @param string             $status                  Which email to send. Leave blank to automatically determine based
 *                                                    on membership status. Options: `active`, `trial`, `expired`, cancelled`.
 *
 * @since 3.0
 * @return void
 */
function rcp_send_membership_email( $membership_id_or_object, $status = '' ) {

	if ( ! is_object( $membership_id_or_object ) ) {
		$membership = rcp_get_membership( $membership_id_or_object );
	} else {
		$membership = $membership_id_or_object;
	}

	if ( empty( $membership ) || ! is_a( $membership, 'RCP_Membership' ) || 0 === $membership->get_id() ) {
		return;
	}

	if ( empty( $status ) ) {
		$status = $membership->get_status();
	}

	global $rcp_options;

	$user_id       = $membership->get_user_id();
	$user_info     = get_userdata( $user_id );
	$message       = $subject = $admin_subject = '';
	$admin_message = '';
	$site_name     = stripslashes_deep( html_entity_decode( get_bloginfo('name'), ENT_COMPAT, 'UTF-8' ) );

	$admin_emails  = ! empty( $rcp_options['admin_notice_emails'] ) ? $rcp_options['admin_notice_emails'] : get_option('admin_email');
	$admin_emails  = apply_filters( 'rcp_admin_notice_emails', explode( ',', $admin_emails ) );
	$admin_emails  = array_map( 'sanitize_email', $admin_emails );

	// Allow add-ons to add file attachments

	/**
	 * Filter email attachments.
	 *
	 * @param array          $attachments
	 * @param int            $user_id
	 * @param string         $status
	 * @param RCP_Membership $membership
	 */
	$attachments = apply_filters( 'rcp_email_attachments', array(), $user_id, $status, $membership );

	$emails = new RCP_Emails;
	$emails->member_id = $user_id;
	$emails->membership = $membership;

	switch ( $status ) {

		/**
		 * Activation email
		 */
		case 'active' :

			if( ! isset( $rcp_options['disable_active_email'] ) ) {

				$message = isset( $rcp_options['active_email'] ) ? $rcp_options['active_email'] : '';
				$message = apply_filters( 'rcp_subscription_active_email', $message, $user_id, $status, $membership );
				$subject = isset( $rcp_options['active_subject'] ) ? $rcp_options['active_subject'] : '';
				$subject = apply_filters( 'rcp_subscription_active_subject', $subject, $user_id, $status, $membership );

			}

			if( ! isset( $rcp_options['disable_active_email_admin'] ) ) {
				$admin_message = isset( $rcp_options['active_email_admin'] ) ? $rcp_options['active_email_admin'] : '';
				$admin_subject = isset( $rcp_options['active_subject_admin'] ) ? $rcp_options['active_subject_admin'] : '';

				if( empty( $admin_message ) ) {
					$admin_message = __( 'Hello', 'rcp' ) . "\n\n" . $user_info->display_name . ' (' . $user_info->user_login . ') ' . __( 'is now subscribed to', 'rcp' ) . ' ' . $site_name . ".\n\n" . __( 'Membership level', 'rcp' ) . ': ' . $membership->get_membership_level_name() . "\n\n";
					$admin_message = apply_filters( 'rcp_before_admin_email_active_thanks', $admin_message, $user_id );
					$admin_message .= __( 'Thank you', 'rcp' );
				}

				if( empty( $admin_subject ) ) {
					$admin_subject = sprintf( __( 'New membership on %s', 'rcp' ), $site_name );
				}

				$admin_subject = apply_filters( 'rcp_email_admin_membership_active_subject', $admin_subject, $user_id, $status, $membership );
				$admin_message = apply_filters( 'rcp_email_admin_membership_active_message', $admin_message, $user_id, $status, $membership );
			}
			break;

		/**
		 * Free email
		 */
		case 'free' :

			if( ! isset( $rcp_options['disable_free_email'] ) ) {

				$message = isset( $rcp_options['free_email'] ) ? $rcp_options['free_email'] : '';
				$message = apply_filters( 'rcp_subscription_free_email', $message, $user_id, $status, $membership );
				$subject = isset( $rcp_options['free_subject'] ) ? $rcp_options['free_subject'] : '';
				$subject = apply_filters( 'rcp_subscription_free_subject', $subject, $user_id, $status, $membership );

			}

			if( ! isset( $rcp_options['disable_free_email_admin'] ) ) {
				$admin_message = isset( $rcp_options['free_email_admin'] ) ? $rcp_options['free_email_admin'] : '';
				$admin_subject = isset( $rcp_options['free_subject_admin'] ) ? $rcp_options['free_subject_admin'] : '';

				if( empty( $admin_message ) ) {
					$admin_message = __( 'Hello', 'rcp' ) . "\n\n" . $user_info->display_name . ' (' . $user_info->user_login . ') ' . __( 'is now subscribed to', 'rcp' ) . ' ' . $site_name . ".\n\n" . __( 'Membership level', 'rcp' ) . ': ' . $membership->get_membership_level_name() . "\n\n";
					$admin_message = apply_filters( 'rcp_before_admin_email_free_thanks', $admin_message, $user_id );
					$admin_message .= __( 'Thank you', 'rcp' );
				}

				if( empty( $admin_subject ) ) {
					$admin_subject = sprintf( __( 'New free membership on %s', 'rcp' ), $site_name );
				}

				$admin_subject = apply_filters( 'rcp_email_admin_membership_free_subject', $admin_subject, $user_id, $status, $membership );
				$admin_message = apply_filters( 'rcp_email_admin_membership_free_message', $admin_message, $user_id, $status, $membership );
			}
			break;

		/**
		 * Trialling email
		 */
		case 'trial' :

			if( ! isset( $rcp_options['disable_trial_email'] ) ) {

				$message = isset( $rcp_options['trial_email'] ) ? $rcp_options['trial_email'] : '';
				$message = apply_filters( 'rcp_subscription_trial_email', $message, $user_id, $status, $membership );

				$subject = isset( $rcp_options['trial_subject'] ) ? $rcp_options['trial_subject'] : '';
				$subject = apply_filters( 'rcp_subscription_trial_subject', $subject, $user_id, $status, $membership );

			}

			if( ! isset( $rcp_options['disable_trial_email_admin'] ) ) {
				$admin_message = isset( $rcp_options['trial_email_admin'] ) ? $rcp_options['trial_email_admin'] : '';
				$admin_subject = isset( $rcp_options['trial_subject_admin'] ) ? $rcp_options['trial_subject_admin'] : '';

				if( empty( $admin_message ) ) {
					$admin_message = __( 'Hello', 'rcp' ) . "\n\n" . $user_info->display_name . ' (' . $user_info->user_login . ') ' . __( 'is now subscribed to', 'rcp' ) . ' ' . $site_name . ".\n\n" . __( 'Membership level', 'rcp' ) . ': ' . $membership->get_membership_level_name() . "\n\n";
					$admin_message = apply_filters( 'rcp_before_admin_email_trial_thanks', $admin_message, $user_id );
					$admin_message .= __( 'Thank you', 'rcp' );
				}

				if( empty( $admin_subject ) ) {
					$admin_subject = sprintf( __( 'New trial membership on %s', 'rcp' ), $site_name );
				}

				$admin_subject = apply_filters( 'rcp_email_admin_membership_trial_subject', $admin_subject, $user_id, $status, $membership );
				$admin_message = apply_filters( 'rcp_email_admin_membership_trial_message', $admin_message, $user_id, $status, $membership );
			}
			break;

		/**
		 * Cancellation email
		 */
		case 'cancelled' :

			if( ! isset( $rcp_options['disable_cancelled_email'] ) ) {

				$message = isset( $rcp_options['cancelled_email'] ) ? $rcp_options['cancelled_email'] : '';
				$message = apply_filters( 'rcp_subscription_cancelled_email', $message, $user_id, $status, $membership );
				$subject = isset( $rcp_options['cancelled_subject'] ) ? $rcp_options['cancelled_subject'] : '';
				$subject = apply_filters( 'rcp_subscription_cancelled_subject', $subject, $user_id, $status, $membership );

			}

			if( ! isset( $rcp_options['disable_cancelled_email_admin'] ) ) {
				$admin_message = isset( $rcp_options['cancelled_email_admin'] ) ? $rcp_options['cancelled_email_admin'] : '';
				$admin_subject = isset( $rcp_options['cancelled_subject_admin'] ) ? $rcp_options['cancelled_subject_admin'] : '';

				if( empty( $admin_message ) ) {
					$admin_message = __( 'Hello', 'rcp' ) . "\n\n" . $user_info->display_name . ' (' . $user_info->user_login . ') ' . __( 'has cancelled their membership to', 'rcp' ) . ' ' . $site_name . ".\n\n" . __( 'Their membership level was', 'rcp' ) . ': ' . $membership->get_membership_level_name() . "\n\n";
					$admin_message = apply_filters( 'rcp_before_admin_email_cancelled_thanks', $admin_message, $user_id );
					$admin_message .= __( 'Thank you', 'rcp' );
				}

				if( empty( $admin_subject ) ) {
					$admin_subject = sprintf( __( 'Cancelled membership on %s', 'rcp' ), $site_name );
				}

				$admin_subject = apply_filters( 'rcp_email_admin_membership_cancelled_subject', $admin_subject, $user_id, $status, $membership );
				$admin_message = apply_filters( 'rcp_email_admin_membership_cancelled_message', $admin_message, $user_id, $status, $membership );
			}
			break;

		/**
		 * Expiration email
		 */
		case 'expired' :

			if( ! isset( $rcp_options['disable_expired_email'] ) ) {

				$message = isset( $rcp_options['expired_email'] ) ? $rcp_options['expired_email'] : '';
				$message = apply_filters( 'rcp_subscription_expired_email', $message, $user_id, $status, $membership );

				$subject = isset( $rcp_options['expired_subject'] ) ? $rcp_options['expired_subject'] : '';
				$subject = apply_filters( 'rcp_subscription_expired_subject', $subject, $user_id, $status, $membership );

				add_user_meta( $user_id, '_rcp_expired_email_sent', 'yes' );

			}

			if( ! isset( $rcp_options['disable_expired_email_admin'] ) ) {
				$admin_message = isset( $rcp_options['expired_email_admin'] ) ? $rcp_options['expired_email_admin'] : '';
				$admin_subject = isset( $rcp_options['expired_subject_admin'] ) ? $rcp_options['expired_subject_admin'] : '';

				if ( empty( $admin_message ) ) {
					$admin_message = __( 'Hello', 'rcp' ) . "\n\n" . $user_info->display_name . "'s " . __( 'membership has expired', 'rcp' ) . "\n\n";
					$admin_message = apply_filters( 'rcp_before_admin_email_expired_thanks', $admin_message, $user_id );
					$admin_message .= __( 'Thank you', 'rcp' );
				}

				if ( empty( $admin_subject ) ) {
					$admin_subject = sprintf( __( 'Expired membership on %s', 'rcp' ), $site_name );
				}

				$admin_subject = apply_filters( 'rcp_email_admin_membership_expired_subject', $admin_subject, $user_id, $status, $membership );
				$admin_message = apply_filters( 'rcp_email_admin_membership_expired_message', $admin_message, $user_id, $status, $membership );
			}
			break;

	}

	if( ! empty( $message ) ) {
		$emails->send( $user_info->user_email, $subject, $message, $attachments );
		rcp_log( sprintf( '%s email sent to user #%d for membership #%d.', ucwords( $status ), $user_info->ID, $membership->get_id() ) );
	} else {
		rcp_log( sprintf( '%s email not sent to user #%d - message is empty or disabled.', ucwords( $status ), $user_info->ID ) );
	}

	if( ! empty( $admin_message ) ) {
		$emails->send( $admin_emails, $admin_subject, $admin_message );
		rcp_log( sprintf( '%s email sent to admin(s) regarding membership #%d.', ucwords( $status ), $membership->get_id() ) );
	} else {
		rcp_log( sprintf( '%s email not sent to admin(s) - message is empty or disabled.', ucwords( $status ) ) );
	}

}

/**
 * Sends "expiring soon" notice to user.
 *
 * @deprecated
 *
 * @param int $user_id ID of the user to send the email to.
 *
 * @return void
 */
function rcp_email_expiring_notice( $user_id = 0 ) {

	global $rcp_options;
	$user_info = get_userdata( $user_id );
	$message   = ! empty( $rcp_options['renew_notice_email'] ) ? $rcp_options['renew_notice_email'] : false;
	$message   = apply_filters( 'rcp_expiring_soon_email', $message, $user_id );
	$subject   = apply_filters( 'rcp_expiring_soon_subject', $rcp_options['renewal_subject'], $user_id );

	if( ! $message ) {
		return;
	}

	$emails = new RCP_Emails;
	$emails->member_id = $user_id;
	$emails->send( $user_info->user_email, $subject, $message );

}

/**
 * Triggers the activation email when the membership is activated.
 *
 * @param int            $membership_id ID of the membership.
 * @param RCP_Membership $membership    Membership object.
 *
 * @since 3.0
 * @return void
 */
function rcp_email_on_membership_activation( $membership_id, $membership ) {

	if ( $membership->is_trialing() ) {
		rcp_send_membership_email( $membership, 'trial' );
	} elseif ( ! $membership->is_paid() ) {
		rcp_send_membership_email( $membership, 'free' );
	} else {
		rcp_send_membership_email( $membership, 'active' );
	}

}
add_action( 'rcp_membership_post_activate', 'rcp_email_on_membership_activation', 10, 2 );

/**
 * Triggers the cancellation email when the membership is cancelled.
 *
 * @param int            $membership_id ID of the membership.
 * @param RCP_Membership $membership    Membership object.
 *
 * @since 3.0
 * @return void
 */
function rcp_email_on_membership_cancellation( $membership_id, $membership ) {

	if ( ! $membership->is_disabled() && ! $membership->was_upgraded() ) {
		rcp_send_membership_email( $membership, 'cancelled' );
	}

}
add_action( 'rcp_membership_post_cancel', 'rcp_email_on_membership_cancellation', 10, 2 );

/**
 * Triggers the expiration email when the membership expires.
 *
 * @param string $old_status
 * @param int    $membership_id
 *
 * @since 3.0
 * @return void
 */
function rcp_email_on_membership_expiration( $old_status, $membership_id ) {

	if ( 'expired' == $old_status || 'new' == $old_status ) {
		return;
	}

	$membership = rcp_get_membership( $membership_id );

	rcp_send_membership_email( $membership, 'expired' );

}
add_action( 'rcp_transition_membership_status_expired', 'rcp_email_on_membership_expiration', 10, 2 );

/**
 * Triggers an email to the member when a payment is received.
 *
 * @param int    $payment_id ID of the payment being completed.
 *
 * @access  public
 * @since   2.3
 * @return  void
 */
function rcp_email_payment_received( $payment_id ) {

	global $rcp_options;

	if ( isset( $rcp_options['disable_payment_received_email'] ) && isset( $rcp_options['disable_payment_received_email_admin'] ) ) {
		return;
	}

	/**
	 * @var RCP_Payments $rcp_payments_db
	 */
	global $rcp_payments_db;

	$payment = $rcp_payments_db->get_payment( $payment_id );

	$user_info = get_userdata( $payment->user_id );

	if( ! $user_info ) {
		return;
	}

	// Don't send an email if payment amount is 0.
	$amount = (float) $payment->amount;
	if ( empty( $amount ) ) {
		rcp_log( sprintf( 'Payment Received email not sent to user #%d - payment amount is 0.', $user_info->ID ) );

		return;
	}

	$payment = (array) $payment;

	$admin_emails  = ! empty( $rcp_options['admin_notice_emails'] ) ? $rcp_options['admin_notice_emails'] : get_option('admin_email');
	$admin_emails  = apply_filters( 'rcp_admin_notice_emails', explode( ',', $admin_emails ) );
	$admin_emails  = array_map( 'sanitize_email', $admin_emails );

	/*
	 * Configure member message.
	 */
	$message = ! empty( $rcp_options['payment_received_email'] ) ? $rcp_options['payment_received_email'] : false;
	$message = apply_filters( 'rcp_payment_received_email', $message, $payment_id, $payment );
	$subject = ! empty( $rcp_options['payment_received_subject'] ) ? $rcp_options['payment_received_subject'] : false;
	$subject = apply_filters( 'rcp_email_payment_received_subject', $subject, $payment_id, $payment );

	/*
	 * Configure admin message.
	 */
	$admin_message = ! empty( $rcp_options['payment_received_email_admin'] ) ? $rcp_options['payment_received_email_admin'] : false;
	$admin_subject = ! empty( $rcp_options['payment_received_subject_admin'] ) ? $rcp_options['payment_received_subject_admin'] : false;

	/**
	 * Filters the admin email message.
	 *
	 * @param string $admin_message Admin email contents.
	 * @param int    $payment_id    ID of the payment record.
	 * @param object $payment       Full payment object from the database.
	 *
	 * @since 3.3.9
	 */
	$admin_message = apply_filters( 'rcp_payment_received_admin_email', $admin_message, $payment_id, $payment );

	/**
	 * Filters the admin email subject.
	 *
	 * @param string $admin_subject Admin email subject.
	 * @param int    $payment_id    ID of the payment record.
	 * @param object $payment       Full payment object from the database.
	 *
	 * @since 3.3.9
	 */
	$admin_subject = apply_filters( 'rcp_email_payment_received_admin_subject', $admin_subject, $payment_id, $payment );

	$emails = new RCP_Emails;
	$emails->member_id = $payment['user_id'];
	$emails->payment_id = $payment_id;

	if ( ! empty( $payment['membership_id'] ) ) {
		$emails->membership = rcp_get_membership( $payment['membership_id'] );
	}

	/*
	 * Send member email.
	 */
	if ( ! isset( $rcp_options['disable_payment_received_email'] ) && ! empty( $message ) && ! empty( $subject ) ) {
		$emails->send( $user_info->user_email, $subject, $message );

		rcp_log( sprintf( 'Payment Received email sent to user #%d for payment ID #%d.', $user_info->ID, $payment_id ) );
	} else {
		rcp_log( sprintf( 'Payment Received email not sent to user #%d for payment ID #%d - message is empty or disabled.', $user_info->ID, $payment_id ) );
	}

	/*
	 * Send admin email.
	 */
	if ( ! isset( $rcp_options['disable_payment_received_email_admin'] ) && ! empty( $admin_message ) && ! empty( $admin_subject ) ) {
		$emails->send( $admin_emails, $admin_subject, $admin_message );

		rcp_log( sprintf( 'Payment Received email sent to admin(s) for payment ID #%d.', $payment_id ) );
	} else {
		rcp_log( sprintf( 'Payment Received email not sent to admin(s) for payment ID #%d - message is empty or disabled.', $payment_id ) );
	}

}
add_action( 'rcp_update_payment_status_complete', 'rcp_email_payment_received', 100 );

/**
 * Emails a member and/or administrator when a renewal payment fails.
 *
 * @since 2.7
 * @param RCP_Member $member  The member (RCP_Member object).
 * @param RCP_Payment_Gateway $gateway The gateway used to process the renewal.
 * @return void
 */
function rcp_email_member_on_renewal_payment_failure( RCP_Member $member, RCP_Payment_Gateway $gateway ) {

	global $rcp_options;

	$membership  = ! empty( $gateway->membership ) ? $gateway->membership : false;
	$status      = ! empty( $membership ) ? $membership->get_status() : '';
	$sent        = false;
	$last_sent   = ! empty( $membership ) ? rcp_get_membership_meta( $membership->get_id(), 'renewal_payment_failed_email_last_sent', true ) : false;
	$number_sent = ! empty( $membership ) ? (int) rcp_get_membership_meta( $membership->get_id(), 'renewal_payment_failed_emails_number', true ) : 0;

	// Bail if an email was sent within the last 20 hours.
	if ( ! empty( $last_sent ) && $last_sent > date( 'Y-m-d H:i:s', strtotime( '-20 hours' ) ) ) {
		rcp_log( sprintf( 'Skipping renewal payment failed email for membership #%d. Email was last sent less than 20 hours ago: %s.', $membership->get_id(), $last_sent ) );

		return;
	}

	/**
	 * Filters the maximum number of failure emails allowed to be sent per membership.
	 *
	 * @param int                  $max_emails Maximum number of emails to send.
	 * @param RCP_Membership|false $membership Associated membership record.
	 * @param RCP_Payment_Gateway  $gateway    Gateway object.
	 *
	 * @since 3.3.2
	 */
	$max_emails = (int) apply_filters( 'rcp_maximum_renewal_payment_failed_emails', 5, $membership, $gateway );

	// Bail if we've exceeded the maximum of 5 emails for a single membership.
	if ( $number_sent >= $max_emails ) {
		rcp_log( sprintf( 'Skipping renewal payment failed email for membership #%d. Reached the maximum number of %d emails.', $membership->get_id(), $max_emails ) );

		return;
	}

	// Email member
	if ( empty( $rcp_options['disable_renewal_payment_failed_email'] ) ) {
		$message = isset( $rcp_options['renewal_payment_failed_email'] ) ? $rcp_options['renewal_payment_failed_email'] : '';

		/**
		 * Filters the message sent to the customer.
		 *
		 * @param string               $message    Message body.
		 * @param int                  $user_id    ID of the customer's user account.
		 * @param string               $status     Membership status.
		 * @param RCP_Membership|false $membership Associated membership record.
		 */
		$message = apply_filters( 'rcp_subscription_renewal_payment_failed_email', $message, $member->ID, $status, $membership );

		$subject = isset( $rcp_options['renewal_payment_failed_subject'] ) ? $rcp_options['renewal_payment_failed_subject'] : '';

		/**
		 * Filters the subject used for the customer's email.
		 *
		 * @param string               $subject    Message subject.
		 * @param int                  $user_id    ID of the customer's user account.
		 * @param string               $status     Membership status.
		 * @param RCP_Membership|false $membership Associated membership record.
		 */
		$subject = apply_filters( 'rcp_subscription_renewal_payment_failed_subject', $subject, $member->ID, $status, $membership );

		if ( ! empty( $subject ) && ! empty( $message ) ) {
			$emails            = new RCP_Emails;
			$emails->member_id = $member->ID;
			$emails->membership = $membership;

			$sent = $emails->send( $member->user_email, $subject, $message );

			rcp_log( sprintf( 'Renewal Payment Failure email sent to user #%d.', $member->ID ) );
		} else {
			rcp_log( sprintf( 'Renewal Payment Failure email not sent to user #%d - subject or message is empty.', $member->ID ) );
		}
	}

	// Email admin
	if ( empty( $rcp_options['disable_renewal_payment_failed_email_admin'] ) ) {
		$admin_emails  = ! empty( $rcp_options['admin_notice_emails'] ) ? $rcp_options['admin_notice_emails'] : get_option('admin_email');
		$admin_emails  = apply_filters( 'rcp_admin_notice_emails', explode( ',', $admin_emails ) );
		$admin_emails  = array_map( 'sanitize_email', $admin_emails );

		$message = isset( $rcp_options['renewal_payment_failed_email_admin'] ) ? $rcp_options['renewal_payment_failed_email_admin'] : '';

		/**
		 * Filters the message sent to the administrator.
		 *
		 * @param string               $message    Message body.
		 * @param int                  $user_id    ID of the customer's user account.
		 * @param string               $status     Membership status.
		 * @param RCP_Membership|false $membership Associated membership record.
		 */
		$message = apply_filters( 'rcp_subscription_renewal_payment_failed_admin_email', $message, $member->ID, $status, $membership );

		$subject = isset( $rcp_options['renewal_payment_failed_subject_admin'] ) ? $rcp_options['renewal_payment_failed_subject_admin'] : '';

		/**
		 * Filters the subject used for the administrator's email.
		 *
		 * @param string               $subject    Message subject.
		 * @param int                  $user_id    ID of the customer's user account.
		 * @param string               $status     Membership status.
		 * @param RCP_Membership|false $membership Associated membership record.
		 */
		$subject = apply_filters( 'rcp_subscription_renewal_payment_failed_admin_subject', $subject, $member->ID, $status, $membership );

		if ( ! empty( $subject ) && ! empty( $message ) ) {
			$emails            = new RCP_Emails;
			$emails->member_id = $member->ID;
			$emails->membership = $membership;

			$sent = $emails->send( $admin_emails, $subject, $message );

			rcp_log( 'Renewal Payment Failure email sent to admin(s).' );
		} else {
			rcp_log( 'Renewal Payment Failure email not sent to admin(s) - subject or message is empty.' );
		}
	}

	if ( $sent && ! empty( $membership ) ) {
		rcp_update_membership_meta( $membership->get_id(), 'renewal_payment_failed_email_last_sent', date( 'Y-m-d H:i:s' ) );
		rcp_update_membership_meta( $membership->get_id(), 'renewal_payment_failed_emails_number', $number_sent + 1 );
	}

}
add_action( 'rcp_recurring_payment_failed', 'rcp_email_member_on_renewal_payment_failure', 10, 2 );

/**
 * Email the site admin when a new manual payment is received.
 *
 * @param RCP_Member                 $member
 * @param int                        $payment_id
 * @param RCP_Payment_Gateway_Manual $gateway
 *
 * @since  2.7.3
 * @return void
 */
function rcp_email_admin_on_manual_payment( $member, $payment_id, $gateway ) {

	global $rcp_options;

	if ( isset( $rcp_options['disable_new_user_notices'] ) ) {
		return;
	}

	$admin_emails  = ! empty( $rcp_options['admin_notice_emails'] ) ? $rcp_options['admin_notice_emails'] : get_option( 'admin_email' );
	$admin_emails  = apply_filters( 'rcp_admin_notice_emails', explode( ',', $admin_emails ) );
	$admin_emails  = array_map( 'sanitize_email', $admin_emails );

	$emails             = new RCP_Emails;
	$emails->member_id  = $member->ID;
	$emails->payment_id = $payment_id;
	$emails->membership = $gateway->membership;

	$site_name = stripslashes_deep( html_entity_decode( get_bloginfo( 'name' ), ENT_COMPAT, 'UTF-8' ) );

	$admin_message = __( 'Hello', 'rcp' ) . "\n\n" . $member->display_name . ' (' . $member->user_login . ') ' . __( 'just submitted a manual payment on', 'rcp' ) . ' ' . $site_name . ".\n\n" . __( 'Membership level', 'rcp' ) . ': ' . $member->get_pending_subscription_name() . "\n\n";
	$admin_message = apply_filters( 'rcp_before_admin_email_manual_payment_thanks', $admin_message, $member->ID );
	$admin_message .= __( 'Thank you', 'rcp' );
	$admin_subject = sprintf( __( 'New manual payment on %s', 'rcp' ), $site_name );

	$emails->send( $admin_emails, $admin_subject, $admin_message );

	rcp_log( sprintf( 'New Manual Payment email sent to admin(s) regarding payment #%d.', $payment_id ) );

}
add_action( 'rcp_process_manual_signup', 'rcp_email_admin_on_manual_payment', 10, 3 );

/**
 * Send email verification message
 *
 * @see rcp_trigger_email_verification()
 *
 * @param int $user_id
 *
 * @since 2.8.2
 * @return void
 */
function rcp_send_email_verification( $user_id ) {

	global $rcp_options;

	$emails = new RCP_Emails;
	$emails->member_id = $user_id;

	$customer = rcp_get_customer_by_user_id( $user_id );

	$message = isset( $rcp_options['verification_email'] ) ? $rcp_options['verification_email'] : '';
	$message = apply_filters( 'rcp_verification_email', $message, $user_id );
	$subject = isset( $rcp_options['verification_subject'] ) ? $rcp_options['verification_subject'] : __( 'Please confirm your email address', 'rcp' );
	$subject = apply_filters( 'rcp_verification_subject', $subject, $user_id );

	if( ! empty( $message ) && ! empty( $subject ) ) {
		$user_info = get_userdata( $user_id );
		$emails->send( $user_info->user_email, $subject, $message );

		if ( is_object( $customer ) ) {
			$customer->add_note( __( 'Verification email sent to member.', 'rcp'  ) );
		}

		rcp_log( sprintf( 'Email Verification email sent to user #%d.', $user_id ) );
	} else {
		rcp_log( sprintf( 'Email Verification email not sent to user #%d - message or subject is empty.', $user_id ) );
	}

}

/**
 * Get a list of available email templates
 *
 * @since 2.7
 * @return array
 */
function rcp_get_email_templates() {
	$emails = new RCP_Emails;
	return $emails->get_templates();
}

/**
 * Get a formatted HTML list of all available tags
 *
 * @since 2.7
 * @return string $list HTML formated list
 */
function rcp_get_emails_tags_list() {
	$tags_needs_membership = array(
		'expiration',
		'subscription_name',
		'subscription_key',
		'amount',
		'invoice_url',
		'membership_renew_url',
		'membership_change_url',
		'update_billing_card_url',
		'member_id',
	);
	// The list
	$list = '<ul>';

	// Get all tags
	$emails = new RCP_Emails;
	$email_tags = $emails->get_tags();

	// Check
	if( count( $email_tags ) > 0 ) {
		foreach( $email_tags as $email_tag ) {
			$class = in_array( $email_tag['tag'], $tags_needs_membership ) ? ' class="rcp-template-tag-warning"' : '';
			$list .= '<li' . $class . '><em>%' . $email_tag['tag'] . '%</em> - ' . $email_tag['description'] . '</li>';
		}
	}

	// Backwards compatibility for displaying extra tags from add-ons, etc.
	ob_start();
	do_action( 'rcp_available_template_tags' );
	$list .= ob_get_clean();

	$list .= '</ul>';

	// Return the list
	return $list;
}


/**
 * Email template tag: name
 * The member's name
 *
 * @since 2.7
 * @param int $user_id
 * @return string name
 */
function rcp_email_tag_name( $user_id = 0 ) {
	$member = get_userdata( $user_id );
	return $member->first_name . ' ' . $member->last_name;
}

/**
 * Email template tag: username
 * The member's username on the site
 *
 * @since 2.7
 * @param int $user_id
 * @return string username
 */
function rcp_email_tag_user_name( $user_id = 0 ) {
	$member = get_userdata( $user_id );
	return $member->user_login;
}

/**
 * Email template tag: user_email
 * The member's email
 *
 * @since 2.7
 * @param int $user_id
 * @return string email
 */
function rcp_email_tag_user_email( $user_id = 0 ) {
	$member = get_userdata( $user_id );
	return $member->user_email;
}

/**
 * Email template tag: firstname
 * The member's first name
 *
 * @since 2.7
 * @param int $user_id
 * @return string first name
 */
function rcp_email_tag_first_name( $user_id = 0 ) {
	$member = get_userdata( $user_id );
	return $member->first_name;
}

/**
 * Email template tag: lastname
 * The member's last name
 *
 * @since 2.7
 * @param int $user_id
 * @return string last name
 */
function rcp_email_tag_last_name( $user_id = 0 ) {
	$member = get_userdata( $user_id );
	return $member->last_name;
}

/**
 * Email template tag: displayname
 * The member's display name
 *
 * @since 2.7
 * @param int $user_id
 * @return string last name
 */
function rcp_email_tag_display_name( $user_id = 0 ) {
	$member = get_userdata( $user_id );
	return $member->display_name;
}

/**
 * Email template tag: expiration
 * The member's expiration date
 *
 * @since 2.7
 * @param int $user_id
 * @param int $payment_id
 * @param string $tag
 * @param RCP_Membership|false $membership
 * @return string expiration
 */
function rcp_email_tag_expiration( $user_id = 0, $payment_id = 0, $tag = '', $membership = false ) {

	// In case the membership object wasn't included...
	if ( empty( $membership ) ) {
		$customer = rcp_get_customer_by_user_id( $user_id );

		if ( empty( $customer ) ) {
			return '';
		}

		$membership = rcp_get_customer_single_membership( $customer->get_id() );
	}

	if ( empty( $membership ) ) {
		return '';
	}

	return $membership->get_expiration_date();
}

/**
 * Email template tag: subscription_name
 * The name of the member's membership level
 *
 * @since 2.7
 * @param int $user_id
 * @param int $payment_id
 * @param string $tag
 * @param RCP_Membership|false $membership
 * @return string Membership name
 */
function rcp_email_tag_subscription_name( $user_id = 0, $payment_id = 0, $tag = '', $membership = false ) {
	// In case the membership object wasn't included...
	if ( empty( $membership ) ) {
		$customer = rcp_get_customer_by_user_id( $user_id );

		if ( empty( $customer ) ) {
			return '';
		}

		$membership = rcp_get_customer_single_membership( $customer->get_id() );
	}

	if ( empty( $membership ) ) {
		return '';
	}

	return $membership->get_membership_level_name();
}

/**
 * Email template tag: subscription_key
 * The member's subscription key
 *
 * @since 2.7
 * @param int $user_id
 * @param int $payment_id
 * @param string $tag
 * @param RCP_Membership|false $membership
 * @return string subscription key
 */
function rcp_email_tag_subscription_key( $user_id = 0, $payment_id = 0, $tag = '', $membership = false ) {
	// In case the membership object wasn't included...
	if ( empty( $membership ) ) {
		$customer = rcp_get_customer_by_user_id( $user_id );

		if ( empty( $customer ) ) {
			return '';
		}

		$membership = rcp_get_customer_single_membership( $customer->get_id() );
	}

	if ( empty( $membership ) ) {
		return '';
	}

	return $membership->get_subscription_key();
}

/**
 * Email template tag: member_id
 * The member's user ID number
 *
 * @since 2.7
 * @param int $user_id
 * @return string User ID number
 */
function rcp_email_tag_member_id( $user_id = 0 ) {
	return $user_id;
}

/**
 * Email template tag: amount
 * The amount of the member's payment.
 *
 * @since 2.7
 * @param int $user_id The user ID.
 * @param int $payment_id The payment ID
 * @return string amount
 */
function rcp_email_tag_amount( $user_id = 0, $payment_id = 0 ) {

	global $rcp_payments_db;

	if ( ! empty( $payment_id ) ) {
		$payment = $rcp_payments_db->get_payment( $payment_id );
	} else {
		$payment = $rcp_payments_db->get_payments( array(
			'user_id' => $user_id,
			'order'   => 'DESC',
			'number'  => 1
		) );

		$payment = reset( $payment );

		if ( empty( $payment ) || ! is_object( $payment ) ) {
			$payment = new stdClass;
			$payment->amount = false;
		}
	}

	return html_entity_decode( rcp_currency_filter( $payment->amount ), ENT_COMPAT, 'UTF-8' );
}

/**
 * Email template tag: invoice_url
 * URL to the member's most recent invoice.
 *
 * @param int $user_id    The user ID.
 * @param int $payment_id The payment ID.
 *
 * @since 2.9
 * @return string URL to the invoice.
 */
function rcp_email_tag_invoice_url( $user_id = 0, $payment_id = 0 ) {

	/**
	 * @var RCP_Payments $rcp_payments_db
	 */
	global $rcp_payments_db;

	if ( ! empty( $payment_id ) ) {
		$payment = $rcp_payments_db->get_payment( $payment_id );
	} else {
		$payment = $rcp_payments_db->get_payments( array(
			'user_id' => $user_id,
			'order'   => 'DESC',
			'number'  => 1
		) );

		$payment = reset( $payment );
	}

	if ( empty( $payment ) || ! is_object( $payment ) ) {
		$url = '';

		// Use the page with [subscription_details] instead.
		global $rcp_options;

		if ( ! empty( $rcp_options['account_page'] ) ) {
			$url = esc_url_raw( get_permalink( $rcp_options['account_page'] ) );
		}
	} else {
		$url = esc_url_raw( rcp_get_invoice_url( $payment->id ) );
	}

	return $url;

}

/**
 * Email template tag: sitename
 * Your site name
 *
 * @since 2.7
 * @return string sitename
 */
function rcp_email_tag_site_name() {
	return wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
}

/**
 * Email template tag: discount_code
 * The discount code used with the most recent payment.
 *
 * @param int $user_id    The user ID.
 * @param int $payment_id The ID of the member's latest payment.
 *
 * @since 2.9.4
 * @return string
 */
function rcp_email_tag_discount_code( $user_id = 0, $payment_id = 0 ) {

	/**
	 * @var RCP_Payments $rcp_payments_db
	 */
	global $rcp_payments_db;

	if ( ! empty( $payment_id ) ) {
		$payment = $rcp_payments_db->get_payment( $payment_id );
	} else {
		$payment = $rcp_payments_db->get_payments( array(
			'user_id' => $user_id,
			'order'   => 'DESC',
			'number'  => 1
		) );

		$payment = reset( $payment );
	}

	if ( is_object( $payment ) && ! empty( $payment->discount_code ) ) {
		$discount_code = $payment->discount_code;
	} else {
		$discount_code = __( 'None', 'rcp' );
	}

	return $discount_code;

}

/**
 * Email template tag: email verification
 * The URL for verifying an email address.
 *
 * @param int $user_id    The member ID.
 * @param int $payment_id The payment ID.
 *
 * @since 2.8.2
 * @return string
 */
function rcp_email_tag_email_verification( $user_id = 0, $payment_id = 0 ) {
	$verification_link = rcp_generate_verification_link( $user_id );

	if ( $verification_link === false ) {
		$verification_link = __( 'Not available', 'rcp' );
	} else {
		$verification_link = esc_url_raw( rcp_generate_verification_link( $user_id ) );
	}
	return $verification_link;
}

/**
 * Email template tag: %update_billing_card_url%
 * The URL to update a membership billing card. This is better than inserting the link manually
 * because it adds the `membership_id` query arg.
 *
 * @param int $user_id    The member ID.
 * @param int $payment_id The payment ID.
 *
 * @since 3.2
 * @return string
 */
function rcp_email_tag_update_billing_card_url( $user_id = 0, $payment_id = 0 ) {

	global $rcp_options;

	if ( empty( $rcp_options['update_card'] ) ) {
		return '';
	}

	$url = get_permalink( absint( $rcp_options['update_card'] ) );

	if ( empty( $url ) ) {
		return '';
	}

	/**
	 * @var RCP_Payments $rcp_payments_db
	 */
	global $rcp_payments_db;

	if ( ! empty( $payment_id ) ) {
		$payment = $rcp_payments_db->get_payment( $payment_id );
	} else {
		$payment = $rcp_payments_db->get_payments( array(
			'user_id' => $user_id,
			'order'   => 'DESC',
			'number'  => 1
		) );

		$payment = reset( $payment );
	}

	if ( is_object( $payment ) && ! empty( $payment->membership_id ) ) {
		$url = add_query_arg( 'membership_id', urlencode( $payment->membership_id ), $url );
	}

	return $url;

}

/**
 * Email template tag: %membership_renew_url%
 *
 * The URL to renew a membership.
 *
 * @param int    $user_id    The user ID.
 * @param int    $payment_id The payment ID.
 * @param string $tag        The email tag.
 * @param bool   $membership The membership object.
 *
 * @since 3.3.1
 * @return string
 */
function rcp_email_tag_membership_renew_url( $user_id = 0, $payment_id = 0, $tag = '', $membership = false ) {

	$url = '';
	// In case the membership object is not provided.
	if ( empty( $membership ) ) {
		$customer = rcp_get_customer_by_user_id( $user_id );

		if ( empty( $customer ) ) {
			return '';
		}

		$membership = rcp_get_customer_single_membership( $customer->get_id() );
	}

	if ( $membership instanceof RCP_Membership ) {
		$url = rcp_get_membership_renewal_url( $membership->get_id() );
	}

	return $url;

}

/**
 * Email template tag: %membership_change_url%
 *
 * The URL to change a membership.
 *
 * @param int    $user_id    The user ID.
 * @param int    $payment_id The payment ID.
 * @param string $tag        The email tag.
 * @param bool   $membership The membership object.
 *
 * @since 3.3.1
 * @return string
 */
function rcp_email_tag_membership_change_url( $user_id = 0, $payment_id = 0, $tag = '', $membership = false ) {

	$url = '';

	// In case the membership object is not provided.
	if ( empty( $membership ) ) {
		$customer = rcp_get_customer_by_user_id( $user_id );

		if ( empty( $customer ) ) {
			return '';
		}

		$membership = rcp_get_customer_single_membership( $customer->get_id() );
	}

	if ( $membership instanceof RCP_Membership ) {
		$url = rcp_get_membership_upgrade_url( $membership->get_id() );
	}

	return $url;

}

/**
 * Disable the mandrill_nl2br filter while sending RCP emails
 *
 * @since 2.7.2
 * @return void
 */
function rcp_disable_mandrill_nl2br() {
	add_filter( 'mandrill_nl2br', '__return_false' );
}
add_action( 'rcp_email_send_before', 'rcp_disable_mandrill_nl2br' );
