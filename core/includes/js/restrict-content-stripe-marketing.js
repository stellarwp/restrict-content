'use strict';

jQuery(document).ready(function($) {
	$( '.stripe-form-table' ).css( 'display', 'none' );
	$( '.rcp_stripe_marketing_container' ).css( 'display', 'table' );

	$( '#restrict-content-stripe-marketing-submit' ).on( 'click', function( event ) {
		event.preventDefault();
		$.ajax({
			data: {
				action: 'rcp_add_to_stripe_mailing_list',
				stripe_mailing_list_email: $( '#stripe_mailing_list' ).val(),
				restrict_content_shown_stripe_marketing: $( '#restrict_content_shown_stripe_marketing' ).val()
			},
			type: 'POST',
			url: rcp_admin_stripe_marketing.ajax_url,
			success: function( response ) {
				$( '#rcp_stripe_marketing_container_inner_container').css( 'display', 'none');
				$( '.stripe-form-table' ).css( 'display', 'block' );
			},
			error: function( response ) {
				//TODO Add error handling
				console.error( response );
			}
		});
	});

	$( '#skip_stripe_marketing_setup' ).on( 'click', function( event ) {
		event.preventDefault();
		$.ajax({
			data: {
				action: 'rcp_add_to_stripe_mailing_list',
				stripe_mailing_list_email: $( '#stripe_mailing_list' ).val(),
				restrict_content_shown_stripe_marketing: $( '#restrict_content_shown_stripe_marketing' ).val()
			},
			type: 'POST',
			url: rcp_admin_stripe_marketing.ajax_url,
			success: function( response ) {
				$( '#rcp_stripe_marketing_container_inner_container').css( 'display', 'none');
				$( '.stripe-form-table' ).css( 'display', 'block' );
			},
			error: function( response ) {
				//TODO Add error handling
				console.error( response );
			}
		});
	});
});
