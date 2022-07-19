/* global rcpStripe, rcpStripeToggleElementErrors, rcp_stripe_script_options */

// Setup on page load.
( function() {
	var container = document.getElementById( 'rcp-card-wrapper' );

	if ( ! container ) {
		return;
	}

	let $ = jQuery;

	if ( ! document.getElementById( 'rcp-card-element' ) || ! document.getElementById( 'rcp-card-element-errors' ) ) {
		container.innerHTML = '';

		// Need to dynamically generate a container to hold errors under the card field.
		var errorContainer = document.createElement( 'div' );
		errorContainer.id = 'rcp-card-element-errors';

		var cardContainer = document.createElement( 'div' );
		cardContainer.id = 'rcp-card-element';

		container.appendChild( cardContainer );
		container.appendChild( errorContainer );
	}

	// Update element styles.
	rcpStripeUpdateElementStyles( rcpStripe.elements.card, '.rcp_card_name' );

	// Field is available, mount.
	rcpStripe.elements.card.mount( '#rcp-card-element' );

	// Handle errors.
	rcpStripe.elements.card.addEventListener( 'change', rcpStripeToggleElementErrors );

	// Show / hide new card fields.
	let savedPaymentMethod = $( 'input[name="rcp_gateway_existing_payment_method"]' );
	let newCardFields      = $( '.rcp-gateway-new-card-fields' );

	/**
	 * Listen for selected payment method changing.
	 */
	savedPaymentMethod.on( 'change', function() {
		let value = $( 'input[name="rcp_gateway_existing_payment_method"]:checked' ).val();
		if ( 'new' === value ) {
			newCardFields.show();
		} else {
			newCardFields.hide();
		}
	} );

	// Trigger change event so we can determine new vs saved card right off the bat.
	savedPaymentMethod.trigger( 'change' );

	/**
	 * Saved card management.
	 */
	var RCP_Stripe_Manage_Cards = {

		/**
		 * Initialize
		 */
		init: function() {
			$( '.rcp-gateway-saved-card-delete' ).on( 'click', 'a', RCP_Stripe_Manage_Cards.delete );
		},

		/**
		 * Delete a payment method
		 */
		delete: function( e ) {
			e.preventDefault();

			if ( ! confirm( rcp_stripe_script_options.confirm_delete_card ) ) {
				return false;
			}

			let paymentMethod = $( this );
			let errorWrap     = $( '#rcp-card-element-errors' );

			paymentMethod.data( 'text', paymentMethod.text() ).text( rcp_stripe_script_options.pleasewait );
			errorWrap.empty();

			$.ajax( {
				type: 'post',
				dataType: 'json',
				url: rcp_stripe_script_options.ajaxurl,
				data: {
					action: 'rcp_stripe_delete_saved_payment_method',
					payment_method_id: paymentMethod.data( 'id' ),
					nonce: paymentMethod.data( 'nonce' )
				},
				xhrFields: {
					withCredentials: true
				},
				success: function( response ) {
					if ( response.success ) {
						paymentMethod.parents( 'li' ).remove();
					} else {
						console.log( response );
						errorWrap.append( '<div class="rcp_message error"><p class="rcp_error"><span>' + response.data + '</span></p></div>' );
						paymentMethod.text( paymentMethod.data( 'text' ) );
					}
				}
			} ).fail( function( response ) {
				if ( window.console && window.console.log ) {
					console.log( response );
				}
			} );
		}

	};

	RCP_Stripe_Manage_Cards.init();
} )();

/**
 * Attempt to generate a token when the form is submitted.
 *
 * @param {Event} e Form submission event.
 */
function rcpStripeSubmitBillingCardUpdate( e ) {
	// Halt here.
	e.preventDefault();

	let cardHolderName  = document.querySelector( '.rcp_card_name' ).value;
	let submitButton    = jQuery( '#rcp_submit' );
	let form            = jQuery( '#rcp_update_card_form' );
	let paymentMethodID = jQuery( 'input[name="rcp_gateway_existing_payment_method"]:checked' ).val();

	if ( 'new' === paymentMethodID ) {
		paymentMethodID = false;
	}

	if ( ! paymentMethodID && '' === cardHolderName ) {
		rcpStripeHandleCardUpdateError( rcp_stripe_script_options.enter_card_name );

		return;
	}

	submitButton.prop( 'disabled', true );

	form.block( {
		message: rcp_stripe_script_options.pleasewait,
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

	/**
	 * Create a setup intent.
	 */
	jQuery.ajax( {
		type: 'post',
		dataType: 'json',
		url: rcp_stripe_script_options.ajaxurl,
		data: {
			action: 'rcp_stripe_create_setup_intent_for_saved_card',
			membership_id: jQuery( 'input[name="rcp_membership_id"]' ).val(),
			payment_method_id: paymentMethodID ? paymentMethodID : 'new'
		},
		success: function ( response ) {
			if ( response.success ) {
				let args = {
					payment_method: {
						card: rcpStripe.elements.card,
						billing_details: { name: cardHolderName }
					}
				};

				if ( paymentMethodID ) {
					args = {
						payment_method: paymentMethodID
					};
				}

				let handler = 'payment_intent' === response.data.payment_intent_object ? 'confirmCardPayment' : 'confirmCardSetup';

				/*
				 * Payment Intent
				 * Handle a card payment.
				 */
				rcpStripeHandleIntent(
					handler, response.data.payment_intent_client_secret, args
				).then( function( result ) {
					if ( result.error ) {
						rcpStripeHandleCardUpdateError( result.error.message, result.error.code );
					} else {
						// Success
						rcpStripeHandleCardUpdateSuccess( response );
					}
				} );
			} else {
				rcpStripeHandleCardUpdateError( response.data );
			}
		}
	} );
}

/**
 * Handles a Stripe payment / setup intent, dynamically factoring in:
 *      - Saved vs new payment method
 *      - Setup Intent vs Payment Intent
 *
 * @param {string}  handler              Stripe handler to call - either `handleCardPayment` or `confirmCardSetup`.
 * @param {string}  client_secret        Client secret to pass into the function.
 * @param {object}  args                 Arguments to pass into the function. For a saved payment method this
 *                                       should contain the payment method ID. For a new payment method this
 *                                       might contain the billing card name.
 *
 * @since 3.3
 * @return {Promise}
 */
function rcpStripeHandleIntent( handler, client_secret, args ) {
	return rcpStripe.Stripe[ handler ]( client_secret, args );
}

/**
 * Handle card update success
 *      - Add `stripe_payment_intent_id` hidden field.
 *      - Add `stripe_payment_intent_object` hidden field.
 *
 * @param {object} response Ajax response.
 *
 * @since 3.2
 */
function rcpStripeHandleCardUpdateSuccess( response ) {
	let inputField = document.createElement( 'input' );
	inputField.value = response.data.payment_intent_id;
	inputField.type = 'hidden';
	inputField.name = 'stripe_payment_intent_id';

	form.appendChild( inputField );

	let objectField = document.createElement( 'input' );
	objectField.value = response.data.payment_intent_object;
	objectField.type = 'hidden';
	objectField.name = 'stripe_payment_intent_object';

	form.appendChild( objectField );

	// Re-submit form.
	form.removeEventListener( 'submit', rcpStripeSubmitBillingCardUpdate );
	form.submit();
}

/**
 * Handle card update failures
 *      - Show error message.
 *      - Re-enable submit button.
 *
 * @param {string} message Error message to display.
 * @param {string} code Optional. Error code.
 *
 * @since 3.2
 */
function rcpStripeHandleCardUpdateError( message, code = '' ) {

	// Use localized error message if available.
	if ( '' !== code && 'undefined' !== typeof rcpStripe.errors[code] ) {
		message = rcpStripe.errors[code];
	}

	jQuery( '#rcp_update_card_form' ).unblock();

	rcpStripeToggleElementErrors( {
		error: {
			message: message
		}
	} );

	jQuery( '#rcp_submit' ).prop( 'disabled', false );

}

var form = document.getElementById( 'rcp_update_card_form' );

form.addEventListener( 'submit', rcpStripeSubmitBillingCardUpdate );
