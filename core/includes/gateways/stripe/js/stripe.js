/* global rcp_processing, rcp_script_options */
// Get bootstrapped data from the page.
var rcpStripe = window.rcpStripe || {};

// Configure Stripe API.
rcpStripe.Stripe = Stripe( rcpStripe.keys.publishable );

// Setup Elements.
rcpStripe.Elements = rcpStripe.Stripe.elements();
rcpStripe.elements = {
	card: rcpStripe.Elements.create( 'card', rcpStripe.elementsConfig ),
};

/**
 * Unblock the form, hide loading symbols, and enable registration button.
 */
function rcpStripeEnableForm() {
	jQuery( '#rcp_registration_form #rcp_submit' ).attr( 'disabled', false );
	jQuery( '#rcp_ajax_loading' ).hide();
	jQuery( '#rcp_registration_form' ).unblock();
	jQuery( '#rcp_submit' ).val( rcp_script_options.register );

	rcp_processing = false;
}

/**
 *
 * Handle form errors, given an error message.
 *
 * @param {string} message Error message.
 * @param {string} code Optional. Error code.
 *
 * @since 3.2
 */
function rcpStripeHandleError( message, code ) {

	// Use localized error message if available.
	if ( 'undefined' !== typeof code && 'undefined' !== typeof rcpStripe.errors[code] ) {
		message = rcpStripe.errors[code];
	}

	// If we're already on the registration form page or update billing card page, just show that error message.
	if ( document.getElementById( 'rcp_registration_form' ) || document.getElementById( 'rcp_update_card_form' ) ) {
		rcpStripeToggleElementErrors( {
			error: {
				message: message
			}
		} );
		rcpStripeEnableForm();
	} else if ( document.getElementById( 'rcp-stripe-confirm-form' ) ) {
		// We're on the confirmation page - show an inline error.
		jQuery( '#rcp-stripe-confirmation-loading' ).empty().append( rcpStripeGenerateNotice( message ) ).append( '<p><a href="' + document.referrer + '">' + rcp_script_options.click_try_again + '</a></p>' );
	}
}

/**
 * Generate a notice element.
 *
 * @param {string} message The notice text.
 * @return {Element} HTML element containing errors.
 */
function rcpStripeGenerateNotice( message ) {
	var span = document.createElement( 'span' );
	span.innerText = message;

	var notice = document.createElement( 'p' );
	notice.classList.add( 'rcp_error' );
	notice.appendChild( span );

	var wrapper = document.createElement( 'div' );
	wrapper.classList.add( 'rcp_message' );
	wrapper.classList.add( 'error' );
	wrapper.appendChild( notice );

	return wrapper;
}

/**
 * Show or hide errors based on input to the Card Element.
 *
 * @param {Event} event Change event on the Card Element.
 */
function rcpStripeToggleElementErrors( event ) {
	var errorContainer = document.getElementById( 'rcp-card-element-errors' );
	if ( null !== errorContainer ) {
		errorContainer.innerHTML = '';

		if ( event.error ) {
			errorContainer.appendChild( rcpStripeGenerateNotice( event.error.message ) );
		}
	}
}

/**
 * Copy styles from an existing element to the Stripe Card Element.
 *
 * @param cardElement Stripe card element.
 * @param selector Selector to copy styles from.
 *
 * @since 3.3
 */
function rcpStripeUpdateElementStyles( cardElement, selector ) {

	if ( undefined === typeof selector ) {
		selector = '.rcp_card_name';
	}

	let inputField = document.querySelector( selector );

	if ( null === inputField ) {
		return;
	}

	// Bail if we already have custom styles.
	if ( null !== rcpStripe.elementsConfig && rcpStripe.elementsConfig.style ) {
		return;
	}

	let inputStyles = window.getComputedStyle( inputField );
	let styleTag = document.createElement( 'style' );

	styleTag.innerHTML = '.StripeElement {' +
		'background-color:' + inputStyles.getPropertyValue( 'background-color' ) + ';' +
		'border-top-color:' + inputStyles.getPropertyValue( 'border-top-color' ) + ';' +
		'border-right-color:' + inputStyles.getPropertyValue( 'border-right-color' ) + ';' +
		'border-bottom-color:' + inputStyles.getPropertyValue( 'border-bottom-color' ) + ';' +
		'border-left-color:' + inputStyles.getPropertyValue( 'border-left-color' ) + ';' +
		'border-top-width:' + inputStyles.getPropertyValue( 'border-top-width' ) + ';' +
		'border-right-width:' + inputStyles.getPropertyValue( 'border-right-width' ) + ';' +
		'border-bottom-width:' + inputStyles.getPropertyValue( 'border-bottom-width' ) + ';' +
		'border-left-width:' + inputStyles.getPropertyValue( 'border-left-width' ) + ';' +
		'border-top-style:' + inputStyles.getPropertyValue( 'border-top-style' ) + ';' +
		'border-right-style:' + inputStyles.getPropertyValue( 'border-right-style' ) + ';' +
		'border-bottom-style:' + inputStyles.getPropertyValue( 'border-bottom-style' ) + ';' +
		'border-left-style:' + inputStyles.getPropertyValue( 'border-left-style' ) + ';' +
		'border-top-left-radius:' + inputStyles.getPropertyValue( 'border-top-left-radius' ) + ';' +
		'border-top-right-radius:' + inputStyles.getPropertyValue( 'border-top-right-radius' ) + ';' +
		'border-bottom-left-radius:' + inputStyles.getPropertyValue( 'border-bottom-left-radius' ) + ';' +
		'border-bottom-right-radius:' + inputStyles.getPropertyValue( 'border-bottom-right-radius' ) + ';' +
		'padding-top:' + inputStyles.getPropertyValue( 'padding-top' ) + ';' +
		'padding-right:' + inputStyles.getPropertyValue( 'padding-right' ) + ';' +
		'padding-bottom:' + inputStyles.getPropertyValue( 'padding-bottom' ) + ';' +
		'padding-left:' + inputStyles.getPropertyValue( 'padding-left' ) + ';' +
	'}';
	document.body.appendChild( styleTag );

	cardElement.update( {
		style: {
			base: {
				color: inputStyles.getPropertyValue( 'color' ),
				fontFamily: inputStyles.getPropertyValue( 'font-family' ),
				fontSize: inputStyles.getPropertyValue( 'font-size' ),
				fontWeight: inputStyles.getPropertyValue( 'font-weight' ),
				fontSmoothing: inputStyles.getPropertyValue( '-webkit-font-smoothing' )
			}
		}
	} );

}
