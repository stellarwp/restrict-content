/**
 * Internet Explorer support for `Object.assign()`
 */
if (typeof Object.assign != 'function') {
	Object.assign = function(target) {
		'use strict';
		if (target == null) {
			throw new TypeError('Cannot convert undefined or null to object');
		}

		target = Object(target);
		for (var index = 1; index < arguments.length; index++) {
			var source = arguments[index];
			if (source != null) {
				for (var key in source) {
					if (Object.prototype.hasOwnProperty.call(source, key)) {
						target[key] = source[key];
					}
				}
			}
		}
		return target;
	};
}

var rcp_processing = false;

jQuery( document ).ready( function ( $ ) {

	// Validate the default/current registration state.
	rcp_validate_registration_state();

	// Toggle membership renew/change.
	$('#rcp-membership-renew-upgrade-toggle').on( 'click', function( e ) {
		e.preventDefault();
		$('#rcp-membership-renew-upgrade-choice').toggle();
	} );

	// When the gateway changes, trigger the "rcp_gateway_change" event.
	$( '#rcp_payment_gateways select, #rcp_payment_gateways input' ).on( 'change', function () {
		$( 'body' ).trigger( 'rcp_gateway_change', {gateway: rcp_get_gateway().val()} );
	} );

	// When the chosen membership level changes, trigger the "rcp_level_change" event.
	$( '.rcp_level' ).on( 'change', function () {
		$( 'body' ).trigger( 'rcp_level_change', {subscription_level: $( '#rcp_subscription_levels .rcp_level:checked' ).val()} );
	} );

	// When the "apply discount" button is clicked, trigger the "rcp_discount_change" event.
	$( '#rcp_apply_discount' ).on( 'click', function ( e ) {
		e.preventDefault();
		$( 'body' ).trigger( 'rcp_discount_change', {discount_code: $( '#rcp_discount_code' ).val()} );
	} );

	// When the auto renew checkbox changes, trigger the rcp_auto_renew_change" event.
	$( '#rcp_auto_renew' ).on( 'change', function () {
		$( 'body' ).trigger( 'rcp_auto_renew_change', {auto_renew: $( this ).prop( 'checked' )} );
	} );

	// Validate registration.
	$( 'body' ).on( 'rcp_discount_change rcp_level_change rcp_gateway_change rcp_auto_renew_change', function ( event, data ) {

		let reg = Object.assign( {}, rcp_get_registration_form_state(), data );

		rcp_validate_registration_state( reg, event.type );
	} );

	if ( '1' === rcp_script_options.recaptcha_enabled && '3' !== rcp_script_options.recaptcha_version ) {
		// Disable submit button. It's then re-enabled via rcp_validate_recaptcha()
		jQuery( '#rcp_registration_form #rcp_submit' ).prop( 'disabled', true );
	}

	/**
	 * Kick off registration when submit button is clicked.
	 */
	$( document ).on( 'click', '#rcp_registration_form #rcp_submit', function ( e ) {

		e.preventDefault();

		var submission_form = document.getElementById( 'rcp_registration_form' );
		var form = $( '#rcp_registration_form' );

		if ( typeof submission_form.checkValidity === "function" && false === submission_form.checkValidity() ) {
			return;
		}

		form.block( {
			message: rcp_script_options.pleasewait,
			css: {
				border: 'none',
				padding: '15px',
				backgroundColor: '#000',
				'-webkit-border-radius': '10px',
				'-moz-border-radius': '10px',
				opacity: .5,
				color: '#fff'
			}
		} );

		$( '#rcp_submit', form ).val( rcp_script_options.pleasewait );

		// Don't allow form to be submitted multiple times simultaneously
		if ( rcp_processing ) {
			return;
		}

		rcp_processing = true;

		/**
		 * Registration chain:
		 * 		- 1. Get a reCAPTCHA v3 token, if required.
		 *      - 2. Validate form via ajax.
		 *      - 3. Proceed to form processing via ajax.
		 *      - 4. Generate a new nonce.
		 *      - 5. Unless `gateway-submits-form`, submit the HTML form.
		 *
		 *      If a gateway declares support for `gateway-submits-form`, then the gateway is responsible for
		 *      triggering `rcp_submit_registration_form()` when ready.
		 *
		 *      @see rcp_get_recaptchav3_token()         - Step 1 - get a reCAPTCHA token if required
		 *      @see rcp_validate_registration_form()    - Step 1 - ajax validation
		 *      @see rcp_process_registration_form()     - Step 3 - ajax processing
		 *      @see rcp_regenerate_registration_nonce() - Step 4 - ajax nonce replacement (after authentication)
		 *      @see rcp_submit_registration_form()      - Step 5 - submit the HTML form
		 *
		 *      @see rcp_handle_registration_errors() - Error handling.
		 */
		var registrationChecks = ( window.rcpRegistrationChecks || [] ).map( function( callback ) {
			return callback( form );
		} );

		Promise.all( registrationChecks ).then( function() {
			return rcp_get_recaptchav3_token( form );
		} ).then( function() {
			return rcp_validate_registration_form( form );
		} ).then( function( response ) {
			return rcp_process_registration_form( form );
		} ).then( function( response ) {
			return rcp_regenerate_registration_nonce( form, response );
		} ).then( function( response ) {
			// Set payment ID.
			let paymentIDField = form.find( 'input[name="rcp_registration_payment_id"]' );

			if ( paymentIDField.length ) {
				paymentIDField.val( response.payment_id );
			} else {
				form.append( '<input type="hidden" name="rcp_registration_payment_id" value="' + response.payment_id + '"/>' );
			}

			$( 'body' ).trigger( 'rcp_register_form_submission', [ response, form.attr( 'id' ) ] );

			let gateway_submits_form = false;
			if ( response.gateway.supports && response.gateway.supports.indexOf( 'gateway-submits-form' ) !== -1 ) {
				gateway_submits_form = true;
			}

			/**
			 * Submit the HTML form if:
			 *      - The total due today is $0 and the recurring total is $0; or:
			 *      - The gateway has declared that it would like to be responsible for form submission (e.g. Stripe).
			 *
			 *      Submitting the form sends the registration information to the gateway for further processing and
			 *      activates the membership if possible.
			 *
			 *      If the gateway has declared support for submitting the form, then it will then be the gateway's
			 *      responsibility to hook into the `rcp_registration_form_processed` event and manually trigger
			 *      rcp_submit_registration_form().
			 */
			if ( ( response.total === 0 && response.recurring_total === 0 ) || ! gateway_submits_form ) {
				rcp_submit_registration_form( form, response );
			} else {
				$( 'body' ).trigger( 'rcp_registration_form_processed', [ form, response ] );
			}
		} ).catch ( function ( error ) {
			console.trace( 'Registration Error', error );
			// This catches errors from any part of the process (validation, processing, etc.).
			rcp_regenerate_registration_nonce( form, error ).then( function( response ) {
				rcp_handle_registration_errors( response, form );
			} ).catch( function( nonceError ) {
				rcp_handle_registration_errors( error, form );
			} );
		} );

	} );

} );

/**
 * Step 1: Get a reCAPTCHA v3 token.
 *
 * @param {*|jQuery|HTMLElement} form
 */
function rcp_get_recaptchav3_token( form ) {

	return new Promise( function( resolve, reject ) {

		if ( '1' !== rcp_script_options.recaptcha_enabled || '3' !== rcp_script_options.recaptcha_version ) {
			resolve();

			return;
		}

		grecaptcha.ready(function () {
			grecaptcha.execute( jQuery('#rcp_recaptcha').data('sitekey'), {
				action: 'register'
			} ).then(function ( token ) {
				// Add token to form.
				jQuery( form ).find('input[name="g-recaptcha-response"]').val( token );

				resolve();
			});
		});

	} );
}

/**
 * Step 2: Validate the registration form via ajax.
 *
 * This ensures required fields are filled out.
 *
 * @param {*|jQuery|HTMLElement} form
 *
 * @since 3.2
 * @returns {Promise}
 */
function rcp_validate_registration_form( form ) {
	let $ = jQuery;

	return new Promise( function ( resolve, reject ) {

		$.post( rcp_script_options.ajaxurl, form.serialize() + '&action=rcp_process_register_form&rcp_ajax=true&validate_only=true', function ( response ) {

			// Remove errors first.
			$( '.rcp-submit-ajax', form ).remove();
			$( '.rcp_message.error', form ).remove();

			// Handle possible validation error.
			if ( ! response.success ) {
				reject( response.data );
			} else {
				$( 'body' ).trigger( 'rcp_registration_form_validated', [form, response.data] );
				resolve( response.data );
			}

		} ).done( function ( response ) {
		} ).fail( function ( response ) {
			console.log( response );
			reject( Error( response ) );
		} ).always( function ( response ) {
		} );

	} );
}

/**
 * Step 3: Process the registration form via ajax.
 *
 *      - Creates user account.
 *      - Creates customer record.
 *      - Creates pending membership record.
 *      - Creates pending payment record.
 *      - Sends registration to gateway for ajax processing (Stripe payment intent is created here).
 *
 * @param {*|jQuery|HTMLElement} form
 *
 * @since 3.2
 * @returns {Promise}
 */
function rcp_process_registration_form( form ) {

	let $ = jQuery;

	rcp_processing = true;

	return new Promise( function ( resolve, reject ) {

		$.post( rcp_script_options.ajaxurl, form.serialize() + '&action=rcp_process_register_form&rcp_ajax=true', function ( response ) {

			// Handle processing errors.
			if ( ! response.success ) {
				reject( response.data );
			} else {
				resolve( response.data );
			}

		} ).done( function ( response ) {
		} ).fail( function ( response ) {
			console.log( response );
		} ).always( function ( response ) {
		} );

	} );

}

/**
 * Step 4: Generate a new registration nonce.
 *
 * This is annoying, but logging in a new user (via step #2) will have invalidated our existing nonce
 * and we have to start a whole new request to get a valid one.
 *
 * We will replace the old value in `response` with the new one and return the new response object.
 *
 * @param {*|jQuery|HTMLElement} form
 * @param {object} response Ajax response from initial processing.
 *
 * @since 3.2
 * @returns {Promise}
 */
function rcp_regenerate_registration_nonce( form, response ) {

	let $ = jQuery;

	rcp_processing = true;

	return new Promise( function ( resolve, reject ) {
		$.ajax( {
			type: 'post',
			dataType: 'json',
			url: rcp_script_options.ajaxurl,
			data: {
				action: 'rcp_generate_registration_nonce'
			},
			success: function ( nonceResponse ) {
				if ( ! nonceResponse.success ) {
					reject( response );
				} else {
					response.nonce = nonceResponse.data;

					// Replace the nonce field with the new value.
					form.find( 'input[name="rcp_register_nonce"]' ).val( response.nonce );

					resolve( response );
				}
			}
		} );
	} );

}

/**
 * Step 5: Submit the registration form.
 *
 * @param {*|jQuery|HTMLElement} form
 * @param {object} response
 *
 * @since 3.2
 */
function rcp_submit_registration_form( form, response ) {

	rcp_processing = true;

	// Submit form.
	form.submit();

}

/**
 * Handle registration errors
 *
 *      - Resets the submit button value
 *      - Adds error messages before the submit button
 *      - Resets the nonce
 *      - Unblocks the form
 *      - Sets the `rcp_processing` var to `false` to indicate processing is over
 *
 * @param {object} response
 * @param {*|jQuery|HTMLElement} form
 *
 * @since 3.2
 */
function rcp_handle_registration_errors( response, form ) {

	let $ = jQuery;
	if ( 'undefined' === typeof form ) {
		form = $( '#rcp_registration_form' );
	}

	if ( 'undefined' === typeof response.errors ) {
		response.errors = '<div class="rcp_message error" role="list"><p class="rcp_error" role="listitem">' + rcp_script_options.error_occurred + '</p></div>';
	}

	form.find( '#rcp_submit' ).val( rcp_script_options.register ).before( response.errors );
	form.find( 'input[name="rcp_register_nonce"]' ).val( response.nonce );
	form.unblock();
	rcp_processing = false;

}

/**
 * Returns the selected gateway slug.
 *
 * @returns {*|jQuery|HTMLElement}
 */
function rcp_get_gateway() {
	let gateway;
	let $ = jQuery;

	if ( $( '#rcp_payment_gateways' ).length > 0 ) {

		gateway = $( '#rcp_payment_gateways select option:selected' );

		if ( gateway.length < 1 ) {

			// Support radio input fields
			gateway = $( 'input[name="rcp_gateway"]:checked' );

		}

	} else {

		gateway = $( 'input[name="rcp_gateway"]' );

	}

	return gateway;
}

/**
 * Get registration form state
 *
 * Returns all data relevant to the current registration, including the selected membership level,
 * whether or not it's a free trial, which gateway was selected, gateway data, and auto renew
 * checked status.
 *
 * @returns {{gateway_data: *, membership_level: (jQuery|*), auto_renew: (*|jQuery), discount_code: (jQuery|*), is_free: boolean, lifetime: boolean, gateway: *, level_has_trial: boolean}}
 */
function rcp_get_registration_form_state() {

	let $ = jQuery;
	let $level = $( '#rcp_subscription_levels .rcp_level:checked' );

	if ( ! $level.length ) {
		$level = $('#rcp_registration_form').find('input[name=rcp_level]');
	}

	return {
		membership_level: $level.val(),
		is_free: $level.attr( 'rel' ) == 0,
		lifetime: $level.data( 'duration' ) === 'forever',
		level_has_trial: rcp_script_options.trial_levels.indexOf( $level.val() ) !== -1,
		discount_code: $( '#rcp_discount_code' ).val(),
		gateway: rcp_get_gateway().val(),
		gateway_data: rcp_get_gateway(),
		auto_renew: $( '#rcp_auto_renew' ).prop( 'checked' )
	}

}

/**
 * Validate the entire registration state and prepare the registration fields
 *
 * @param reg_state
 * @param event_type
 */
function rcp_validate_registration_state( reg_state, event_type ) {

	if ( !reg_state ) {
		reg_state = rcp_get_registration_form_state();
	}

	let $ = jQuery;
	let form = $( '#rcp_registration_form' );

	form.block( {
		message: rcp_script_options.pleasewait,
		css: {
			border: 'none',
			padding: '15px',
			backgroundColor: '#000',
			'-webkit-border-radius': '10px',
			'-moz-border-radius': '10px',
			opacity: .5,
			color: '#fff'
		}
	} );

	let data = form.serialize() +
		'&action=rcp_validate_registration_state' +
		'&rcp_ajax=true' +
		'&rcp_level=' + reg_state.membership_level +
		'&lifetime=' + reg_state.lifetime +
		'&level_has_trial=' + reg_state.level_has_trial +
		'&is_free=' + reg_state.is_free +
		'&discount_code=' + reg_state.discount_code +
		'&rcp_gateway=' + reg_state.gateway +
		'&rcp_auto_renew=' + ( true === reg_state.auto_renew ? true : '' ) +
		'&event_type=' + event_type +
		'&registration_type=' + $( '#rcp-registration-type' ).val() +
		'&membership_id=' + $( '#rcp-membership-id' ).val() +
		'&rcp_registration_payment_id=' + $( '#rcp-payment-id' ).val();

	$.ajax( {
		type: 'post',
		dataType: 'json',
		url: rcp_script_options.ajaxurl,
		data: data,
		success: function ( response ) {

			if ( response.success ) {

				// Only refresh the gateway fields if we need to.
				if ( ! $( '.rcp_gateway_' + response.data.gateway + '_fields' ).length || ! response.data.show_gateway_fields ) {
					$( '#rcp_gateway_extra_fields' ).remove();

					if ( true == response.data.show_gateway_fields && response.data.gateway_fields ) {

						if ( $( '.rcp_gateway_fields' ).length ) {

							$( '<div class="rcp_gateway_' + response.data.gateway + '_fields" id="rcp_gateway_extra_fields">' + response.data.gateway_fields + '</div>' ).insertAfter( '.rcp_gateway_fields' );

						} else {

							// Pre 2.1 template files
							$( '<div class="rcp_gateway_' + response.data.gateway + '_fields" id="rcp_gateway_extra_fields">' + response.data.gateway_fields + '</div>' ).insertAfter( '.rcp_gateways_fieldset' );
						}

						$( 'body' ).trigger( 'rcp_gateway_loaded', response.data.gateway );
					}
				}

				rcp_prepare_registration_fields( response.data );

			} else {
				console.log( response );
			}

			$( '#rcp_registration_form' ).unblock();
		}
	} );

}

/**
 * Show/hide fields according to the arguments.
 *
 * @param args
 */
function rcp_prepare_registration_fields( args ) {

	let $ = jQuery;

	// Show recurring checkbox if it's available. Otherwise hide and uncheck it.
	if ( args.recurring_available ) {
		$( '#rcp_auto_renew_wrap' ).show();
	} else {
		$( '#rcp_auto_renew_wrap' ).hide();
		$( '#rcp_auto_renew_wrap input' ).prop( 'checked', false );
	}

	// If this is an eligible free trial, auto renew needs to be forced on and hidden.
	if ( args.level_has_trial && args.trial_eligible ) {
		$( '#rcp_auto_renew_wrap' ).hide();
		$( '#rcp_auto_renew_wrap input' ).prop( 'checked', true );
	}

	// Should the gateway selection be shown?
	if ( args.initial_total > 0.00 || args.recurring_total > 0.00 ) {
		$( '.rcp_gateway_fields' ).show();
	} else {
		$( '.rcp_gateway_fields' ).hide();
	}

	// Should the gateway fields be shown?
	if ( args.show_gateway_fields ) {
		$( '#rcp_gateway_extra_fields' ).show();
	} else {
		$( '#rcp_gateway_extra_fields' ).remove();
	}

	// Show discount code validity.
	$( '.rcp_discount_amount' ).remove();
	$( '.rcp_discount_valid, .rcp_discount_invalid' ).hide();

	if ( args.discount_code ) {
		if ( args.discount_valid ) {
			// Discount code is valid.
			$( '.rcp_discount_valid' ).show();
			$( '#rcp_discount_code_wrap label' ).append( '<span class="rcp_discount_amount"> - ' + args.discount_amount + '</span>' );

			if ( args.full_discount ) {
				$( '.rcp_gateway_fields' ).addClass( 'rcp_discounted_100' );
			} else {
				$( '.rcp_gateway_fields' ).removeClass( 'rcp_discounted_100' );
			}
		} else {
			// Discount code is invalid.
			$( '.rcp_discount_invalid' ).show();
			$( '.rcp_gateway_fields' ).removeClass( 'rcp_discounted_100' );
		}

		let discount_data = {
			valid: args.discount_valid,
			full: args.full_discount,
			amount: args.discount_amount
		};

		$( 'body' ).trigger( 'rcp_discount_applied', [discount_data] );
	}

	// Load the total details.
	$( '.rcp_registration_total' ).html( args.total_details_html );

}

/**
 * Enables the submit button when a successful
 * reCAPTCHA response is triggered.
 *
 * This function is referenced via the data-callback
 * attribute on the #rcp_recaptcha element.
 */
function rcp_validate_recaptcha(response) {
	jQuery('#rcp_registration_form #rcp_submit').prop('disabled', false);
}

/************* Deprecated Functions Below */

var rcp_validating_discount = false;
var rcp_validating_gateway = false;
var rcp_validating_level = false;
var rcp_calculating_total = false;

/**
 * @deprecated In favour of `rcp_validate_registration_state()`
 * @see rcp_validate_registration_state()
 *
 * @param validate_gateways
 */
function rcp_validate_form( validate_gateways ) {
	rcp_validate_registration_state();
}

/**
 * @deprecated In favour of `rcp_validate_registration_state()`
 * @see rcp_validate_registration_state()
 */
function rcp_validate_subscription_level() {

	if ( rcp_validating_level ) {
		return;
	}

	rcp_validating_level = true;

	rcp_validate_registration_state();

	rcp_validating_level = false;
}

/**
 * @deprecated In favour of `rcp_validate_registration_state()`
 * @see rcp_validate_registration_state()
 */
function rcp_validate_gateways() {

	if ( rcp_validating_gateway ) {
		return;
	}

	rcp_validating_gateway = true;

	rcp_validate_registration_state();

	rcp_validating_gateway = false;

}

/**
 * @deprecated In favour of `rcp_validate_registration_state()`
 * @see rcp_validate_registration_state()
 */
function rcp_validate_discount() {

	if ( rcp_validating_discount ) {
		return;
	}

	rcp_validating_discount = true;

	rcp_validate_registration_state();

	rcp_validating_discount = false;

}

/**
 * @deprecated In favour of `rcp_validate_registration_state()`
 * @see rcp_validate_registration_state()
 */
function rcp_calc_total() {

	if ( rcp_calculating_total ) {
		return;
	}

	rcp_calculating_total = true;

	rcp_validate_registration_state();

	rcp_calculating_total = false;

}
