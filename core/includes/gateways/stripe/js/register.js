/* global rcpStripe, rcp_processing, rcp_script_options */

/**
 * Handle a failed Stripe payment
 *
 * This does an ajax request to trigger `rcp_registration_failed`, which changes the payment
 * status to `failed` and disables the pending membership.
 *
 * @param {int} payment_id
 * @param {string} message
 *
 * @since 3.2
 */
function rcpStripeHandlePaymentFailure( payment_id, message ) {

	let $ = jQuery;

	$.ajax( {
		type: 'post',
		dataType: 'json',
		url: rcp_script_options.ajaxurl,
		data: {
			action: 'rcp_stripe_handle_initial_payment_failure',
			payment_id: payment_id,
			message: message
		},
		success: function ( response ) { }
	} );

}

/**
 * Close the Stripe Checkout modal.
 *
 * @since 3.2
 */
function rcpStripeCloseCheckoutModal() {
	// Don't allow closing if we're already processing the form.
	if ( rcp_processing ) {
		return;
	}

	let $ = jQuery;
	let modalWrapper = $( '.rcp-modal-wrapper' );
	let modalContainer = $( '.rcp-modal' );

	modalWrapper.fadeOut( 250 );
	modalContainer.hide();

	if ( ! document.getElementById( 'rcp-card-element' ) ) {
		return;
	}

	// Unmount Stripe Elements.
	rcpStripe.elements.card.unmount( '#rcp-card-element' );
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

// Reliant on jQuery triggers, so setup jQuery.
jQuery( function( $ ) {

	var RCP_Stripe_Registration = {

		/**
		 * Whether or not a payment method has been filled out.
		 */
		hasPaymentMethod: false,

		/**
		 * ID of the saved payment method, or `false` if using a new card.
		 */
		paymentMethodID: false,

		/**
		 * Initialize all our events
		 */
		init: function() {
			$( 'body' ).on( 'rcp_gateway_loaded', RCP_Stripe_Registration.mountElements );
			$( '#rcp_submit' ).on( 'click', RCP_Stripe_Registration.maybeBlockSubmit );
			$( 'body' ).on( 'rcp_registration_form_processed', RCP_Stripe_Registration.handlePayment );
			$( 'body' ).on( 'click', '.rcp-stripe-register-submit-button', RCP_Stripe_Registration.launchModal );
		},

		/**
		 * Attempt to mount the Stripe Elements card when the gateway changes.
		 *
		 * @param e
		 * @param gateway
		 */
		mountElements: function ( e, gateway ) {
			if ( ! document.getElementById( 'rcp-card-element' ) ) {
				return;
			}

			// Update element styles.
			rcpStripeUpdateElementStyles( rcpStripe.elements.card, '.rcp_card_name' );

			// Field is available, mount.
			rcpStripe.elements.card.mount( '#rcp-card-element' );

			// Flag as having card details.
			rcpStripe.elements.card.addEventListener( 'change', function ( event ) {
				if ( event.complete ) {
					RCP_Stripe_Registration.hasPaymentMethod = true;
				}
			} );

			/**
			 * Listen for selected payment method changing.
			 */
			RCP_Stripe_Registration.setPaymentMethodID();

			// Handle errors.
			rcpStripe.elements.card.addEventListener( 'change', rcpStripeToggleElementErrors );
		},

		/**
		 * Set the current payment method ID and listen for changes.
		 */
		setPaymentMethodID: function() {
			let savedPaymentMethod = $( 'input[name="rcp_gateway_existing_payment_method"]' );
			savedPaymentMethod.on( 'change', function( e ) {
				rcpStripeToggleElementErrors( e ); // clear errors
				let value = $( 'input[name="rcp_gateway_existing_payment_method"]:checked' ).val();
				$( '.rcp-gateway-saved-payment-method, .rcp-gateway-add-payment-method-wrap' ).removeClass( 'rcp-gateway-selected-payment-method' );
				if ( 'new' === value ) {
					$( '.rcp-gateway-new-card-fields' ).show();
					$( '.rcp-gateway-add-payment-method-wrap' ).addClass( 'rcp-gateway-selected-payment-method' );
					RCP_Stripe_Registration.paymentMethodID = false;
				} else {
					$( '.rcp-gateway-new-card-fields' ).hide();
					$( this ).parents( '.rcp-gateway-saved-payment-method' ).addClass( 'rcp-gateway-selected-payment-method' );
					RCP_Stripe_Registration.paymentMethodID = value;
				}
			} );

			savedPaymentMethod.trigger( 'change' );
		},

		/**
		 * Block the form submission if Stripe is the selected gateway, payment is due, a new payment method is being
		 * entered, but no details have been provided.
		 *
		 * @param e
		 * @returns {boolean}
		 */
		maybeBlockSubmit: function ( e ) {
			if ( 'stripe' === rcp_get_gateway().val() && document.getElementById( 'rcp-card-element' ) && ! RCP_Stripe_Registration.hasPaymentMethod && ! RCP_Stripe_Registration.paymentMethodID ) {
				e.stopPropagation();
				rcpStripeHandleError( rcp_script_options.enter_card_details );
				return false;
			}
		},

		/**
		 * After registration has been processed, handle card payments.
		 *
		 * @param event
		 * @param form
		 * @param response
		 */
		handlePayment: function ( event, form, response ) {
			// Not on Stripe gateway, bail.
			if ( response.gateway.slug !== 'stripe' ) {
				return;
			}

			/*
			 * Bail if the amount due today is 0 AND:
			 * the recurring amount is 0, or auto renew is off
			 */
			if ( ! response.total && ( ! response.recurring_total || ! response.auto_renew ) ) {
				return;
			}

			// 100% discount, bail.
			if ( $( '.rcp_gateway_fields' ).hasClass( 'rcp_discounted_100' ) ) {
				return;
			}

			// Trigger error if we don't have a client secret.
			if ( ! response.gateway.data.stripe_client_secret ) {
				rcpStripeHandleError( rcp_script_options.error_occurred );
				rcpStripeHandlePaymentFailure( response.payment_id, rcp_script_options.error_occurred );

				return;
			}

			let cardHolderName = $( '.card-name' ).val();

			let handler = 'payment_intent' === response.gateway.data.stripe_intent_type ? 'confirmCardPayment' : 'confirmCardSetup';
			let args = {
				payment_method: {
					card: rcpStripe.elements.card,
					billing_details: { name: cardHolderName }
				}
			};

			if ( RCP_Stripe_Registration.paymentMethodID ) {
				args = {
					payment_method: RCP_Stripe_Registration.paymentMethodID
				};
			}

			/**
			 * Handle payment intent / setup intent.
			 */
			rcpStripeHandleIntent(
				handler, response.gateway.data.stripe_client_secret, args
			).then( function( paymentResult ) {
				if ( paymentResult.error ) {
					rcpStripeHandleError( paymentResult.error.message, paymentResult.error.code );
					rcpStripeHandlePaymentFailure( response.payment_id, paymentResult.error.message );
				} else {
					rcp_submit_registration_form( form, response );
				}
			} );
		},

		/**
		 * Launch the [register_form_stripe] modal.
		 *
		 * @param e
		 */
		launchModal: function( e ) {
			e.preventDefault();

			let modalWrapper = $( '.rcp-modal-wrapper' );
			let modalContainer = $( '.rcp-modal' );
			let form = $( this ).parents( '.rcp-stripe-register' );

			// Mount the Stripe Elements card.
			if ( ! document.getElementById( 'rcp-card-element' ) ) {
				return;
			}

			// Fade in wrapper.
			modalWrapper.fadeIn( 250 );

			// Field is available, mount.
			rcpStripe.elements.card.mount( '#rcp-card-element' );

			let registerButton = modalContainer.find( 'input.rcp-modal-submit' );

			// Flag as having card details.
			rcpStripe.elements.card.addEventListener( 'change', function ( event ) {
				if ( event.complete ) {
					RCP_Stripe_Registration.hasPaymentMethod = true;
				}
			} );

			// Handle errors.
			rcpStripe.elements.card.addEventListener( 'change', rcpStripeToggleElementErrors );

			// Set up saved vs. new card.
			RCP_Stripe_Registration.setPaymentMethodID();

			// Set form content.
			if ( form.data( 'name' ) ) {
				$( '.rcp-modal-membership-name' ).text( form.data( 'name' ) ).show();
			}
			if ( form.data( 'description' ) ) {
				$( '.rcp-modal-membership-description' ).text( form.data( 'description' ) ).show();
			}
			if ( form.data( 'panel-label' ) ) {
				registerButton.val( form.data( 'panel-label' ) );
			}
			if ( form.data( 'level-id' ) ) {
				modalContainer.find( '#rcp-stripe-checkout-level-id' ).val( form.data( 'level-id' ) );
			}

			// Fade in container.
			modalContainer.fadeIn( 250 );

			/**
			 * Close the modal on these three events...
			 */

			// If they click the close icon, close modal.
			modalWrapper.find( '.rcp-modal-close' ).on( 'click', function() {
				rcpStripeCloseCheckoutModal();
			} );

			// If they press "ESC" on the keyboard, close modal.
			$( document ).on( 'keydown', function ( e ) {
				if ( 27 === e.keyCode ) {
					rcpStripeCloseCheckoutModal();
				}
			} );

			// If they click outside the container, close modal.
			$( document ).mouseup( function( e ) {
				if ( ! modalContainer.is( e.target ) && modalContainer.has( e.target ).length === 0 ) {
					rcpStripeCloseCheckoutModal();
				}
			} );
		}

	};
	RCP_Stripe_Registration.init();
} );
