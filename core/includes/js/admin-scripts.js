jQuery(document).ready(function($) {

	// Tooltips
	$( '.rcp-help-tip' ).tooltip({
		content: function() {
			return $( this ).prop( 'title' );
		},
		position: {
			my: 'center top',
			at: 'center bottom+10',
			collision: 'flipfit'
		},
		hide: {
			duration: 500
		},
		show: {
			duration: 500
		}
	});

	var restriction_control        = $('#rcp-restrict-by');
	var role_control               = $('#rcp-metabox-field-role');
	var sub_levels_control         = $('#rcp-metabox-field-levels');
	var sub_levels_select          = $('.rcp-subscription-levels');
	var sub_levels_radio           = $('input[name=rcp_subscription_level_any_set]');
	var access_levels_control      = $('#rcp-metabox-field-access-levels');
	var additional_options_control = $('#rcp-metabox-field-options');

	var Settings_Controls = {
		prepare_type: function(type) {
			if ('unrestricted' === type) {
				role_control.hide();
				sub_levels_control.hide();
				access_levels_control.hide();
				additional_options_control.hide();
			}

			if ('registered-users' === type) {
				role_control.show();
				sub_levels_control.hide();
				access_levels_control.hide();
				additional_options_control.show();
			}

			if ('subscription-level' === type) {
				role_control.show();
				sub_levels_control.show();
				access_levels_control.hide();
				additional_options_control.show();
			}

			if ('access-level' === type) {
				role_control.show();
				sub_levels_control.hide();
				access_levels_control.show();
				additional_options_control.show();
			}
		},

		prepare_sub_levels: function(type) {
			if ('any' === type) {
				sub_levels_select.hide();
			}

			if ('any-paid' === type) {
				sub_levels_select.hide();
			}

			if ('specific' === type) {
				sub_levels_radio.show();
				sub_levels_select.show();
				access_levels_control.hide();
				additional_options_control.show();
			}
		}
	}

	var restriction_type = restriction_control.val();
	Settings_Controls.prepare_type(restriction_type);

	// restrict content metabox.
	restriction_control.on('change', function() {
		var type = $(this).val();
		Settings_Controls.prepare_type(type);
	});

	sub_levels_radio.on('change', function() {
		var type = $(this).val();
		Settings_Controls.prepare_sub_levels(type);
	});

	// Deselect all roles if "All" is selected.
	$('.rcp-user-role').on( 'change', function (e) {
		var this_role_value = $(this).val();
		if ( 'all' === this_role_value && this.checked ) {
			// Uncheck all other roles when selecting "All".
			$(this).parents('p').find('input:checkbox').each(function() {
				if ( $(this).val() !== 'all' ) {
					$(this).removeAttr( 'checked' );
				}
			});
		} else if ( 'all' !== this_role_value && this.checked ) {
			// Uncheck "All" when selecting another role.
			$('#rcp_user_level_all').removeAttr( 'checked' );
		}
	});

	// settings tabs

	//when the history state changes, gets the url from the hash and display
	$(window).on( 'hashchange', function(e) {

		if ( $('#rcp-settings-wrap').length == 0) {
			return;
		}

		let url = window.location.hash;
		if (url) {
			url = url.replace(/[^a-zA-Z0-9]/g, '');
		}

		//hide all
		jQuery( '#rcp-settings-wrap #tab_container .tab_content' ).hide();
		jQuery( '#rcp-settings-wrap #tab_container' ).children(".tab_content").hide();
		jQuery( '#rcp-settings-wrap .nav-tab-wrapper a' ).removeClass("nav-tab-active");

		//find a href that matches url
		if (url && jQuery.inArray(url, [ 'general', 'payments', 'emails', 'invoices', 'misc' ])) {
			jQuery( '#rcp-settings-wrap .nav-tab-wrapper a[href="#' + url + '"]' ).addClass( 'nav-tab-active' );
			jQuery("#rcp-settings-wrap #tab_container #" + url).addClass("selected").fadeIn();
		} else {
			jQuery( '#rcp-settings-wrap  h2.nav-tab-wrapper a[href="#general"]' ).addClass( 'nav-tab-active' );
			jQuery("#rcp-settings-wrap  #tab_container #general").addClass("selected nav-tab-active").fadeIn();
		}
	});

	// Since the event is only triggered when the hash changes, we need to trigger
	// the event now, to handle the hash the page may have loaded with.
	$(window).trigger( 'hashchange' );


	if($('.rcp-datepicker').length > 0 ) {
		var dateFormat = 'yy-mm-dd';
		$('.rcp-datepicker').datepicker({
			dateFormat: dateFormat,
			beforeShow: function() {
				$( this ).datepicker( 'widget' ).addClass( 'rcp-datepicker-wrap' );
			},
			onClose: function() {
				$( this ).datepicker( 'widget' ).removeClass( 'rcp-datepicker-wrap' );
			}
		});
	}
	if($('.rcp-datetimepicker').length > 0 ) {
		$('.rcp-datetimepicker').datetimepicker({
			dateFormat: 'yy-mm-dd',
			timeFormat: 'HH:mm:ss',
			beforeShow: function( input_id, input_field, timepicker ) {
				$('#ui-datepicker-div').addClass('rcp-datepicker-wrap');
			},
			onClose: function( dateText, instance ) {
				$('#ui-datepicker-div').removeClass('rcp-datepicker-wrap');
			}
		});
	}
	$('.rcp_cancel').on( 'click', function() {
		if ($(this).is('[disabled=disabled]')) {
			return false;
		}

		if(confirm(rcp_vars.cancel_user)) {
			return true;
		} else {
			return false;
		}
	});
	$('#rcp-expire-membership-button').on( 'click', function() {
		if(confirm(rcp_vars.expire_membership)) {
			return true;
		} else {
			return false;
		}
	});
	$('.rcp_delete_subscription').on( 'click', function() {
		if(confirm(rcp_vars.delete_subscription)) {
			return true;
		}
		return false;
	});
	$('.rcp-delete-payment').on( 'click', function() {
		if(confirm(rcp_vars.delete_payment)) {
			return true;
		}
		return false;
	});
	$('.rcp_delete_discount').on( 'click', function() {
		if(confirm(rcp_vars.delete_discount)) {
			return true;
		}
		return false;
	});
	$('.rcp-delete-reminder').on( 'click', function () {
		if(confirm(rcp_vars.delete_reminder)) {
			return true;
		}
		return false;
	});
	$('.rcp-delete-membership').on( 'click', function () {
		if(confirm(rcp_vars.delete_membership)) {
			return true;
		}
		return false;
	});
	$('.rcp-delete-customer').on( 'click', function () {
		if(confirm(rcp_vars.delete_customer)) {
			return true;
		}
		return false;
	});
	$('#rcp-add-new-member').on( 'submit', function() {
		if($('#rcp-user').val() == '') {
			alert(rcp_vars.missing_username);
			return false;
		}
		return true;
	});
	// make columns sortable via drag and drop
	if( $('.rcp-subscriptions tbody').length ) {
		$('.rcp-subscriptions tbody tr').each( function() {
			let id = $(this).find('.check-column input').val();
			$(this).attr('id', 'recordsArray_' + id);
			$(this).addClass('rcp-subscription rcp_row');
		} );
		$( ".rcp-subscriptions tbody" ).sortable({
			handle: '.rcp-drag-handle', items: 'tr', opacity: 0.6, cursor: 'move', axis: 'y', update: function() {
				var order = $( this ).sortable( "serialize" ) + '&action=update-subscription-order';
				$.post( ajaxurl, order, function( response ) {
					// response here
				});
			}
		});
	}

	// auto calculate the subscription expiration when manually adding a user
	$('#rcp-membership-level').on( 'change', function() {
		var level_id = $('option:selected', this).val();
		data = {
			action: 'rcp_get_subscription_expiration',
			subscription_level: level_id
		};
		$.post(ajaxurl, data, function(response) {
			let expiration = $( '#rcp-membership-expiration' );
			let no_expiration = $( '#rcp-membership-expiration-none' );

			if ( 'none' === response ) {
				expiration.val( '' );
				no_expiration.prop( 'checked', true );
			} else {
				expiration.val( response );
				no_expiration.prop( 'checked', false );
			}
		});
	});

	$('.rcp-user-search').on( 'keyup', function() {
		var user_search = $(this).val();
		$('.rcp-ajax').show();
		data = {
			action: 'rcp_search_users',
			user_name: user_search,
			return_field: $(this).data('return-field'),
			rcp_nonce: rcp_vars.rcp_member_nonce
		};

		$.ajax({
         type: "POST",
         data: data,
         dataType: "json",
         url: ajaxurl,
			success: function (search_response) {

				$('.rcp-ajax').hide();

				let search_results = $('#rcp_user_search_results');

				search_results.html('');

				if(search_response.id == 'found') {
					$(search_response.results).appendTo('#rcp_user_search_results');
				} else if(search_response.id == 'fail') {
					search_results.html(search_response.msg);
				}

				$(document).on( 'click', function ( e ) {
					let target = $( e.target );

					// Empty the search results if we didn't click inside the input or on a result.
					if ( ! target.closest( '.rcp-user-search' ).length && ! target.closest( '#rcp_user_search_results' ).length ) {
						search_results.html('');
					}
				} );
			}
		});
	});
	$('body').on('click.rcpSelectUser', '#rcp_user_search_results a', function(e) {
		e.preventDefault();
		var login = $(this).data('login');
		$('.rcp-user-search').val(login);
		$('#rcp_user_search_results').html('');
	});

	$( '#rcp-graphs-date-options' ).on( 'change', function() {
		var $this = $(this);
		if( $this.val() == 'other' ) {
			$( '#rcp-date-range-options' ).show();
		} else {
			$( '#rcp-date-range-options' ).hide();
		}
	});

	$( '#rcp-unlimited' ).on( 'change', function() {
		var $this = $(this);
		if( $this.attr( 'checked' ) ) {
			$( '#rcp-expiration' ).val('none');
		} else if( 'none' == $( '#rcp-expiration' ).val() ) {
			$( '#rcp-expiration' ).val('').trigger('focus');
		}
	});

	// WP 3.5+ uploader
	var file_frame;
	$('body').on('click', '.rcp-upload', function(e) {

		e.preventDefault();

		var formfield = $(this).prev();

		// If the media frame already exists, reopen it.
		if ( file_frame ) {
			//file_frame.uploader.uploader.param( 'post_id', set_to_post_id );
			file_frame.open();
			return;
		}

		// Create the media frame.
		file_frame = wp.media.frames.file_frame = wp.media({
			frame: 'select',
			title: rcp_vars.choose_logo,
			multiple: false,
			library: {
				type: 'image'
			},
			button: {
				text: rcp_vars.use_as_logo
			}
		});

		file_frame.on( 'menu:render:default', function(view) {
	        // Store our views in an object.
	        var views = {};

	        // Unset default menu items
	        view.unset('library-separator');
	        view.unset('gallery');
	        view.unset('featured-image');
	        view.unset('embed');

	        // Initialize the views in our view object.
	        view.set(views);
	    });

		// When an image is selected, run a callback.
		file_frame.on( 'select', function() {

			var attachment = file_frame.state().get('selection').first().toJSON();
			formfield.val(attachment.url);

		});

		// Finally, open the modal
		file_frame.open();
	});

	$('#rcp-bulk-select-all').on('change', function() {
		if( $(this).prop('checked') ) {
			$('#rcp-members-form .rcp-member-cb').prop('checked', true );
		} else {
			$('#rcp-members-form .rcp-member-cb').prop('checked', false );
		}
	});


	// Cancel user's subscription when updating status to "Cancelled".
	$('#rcp-status').on('change', function () {
		if ( 'cancelled' == $(this).val() ) {
			if ( rcp_vars.can_cancel_member ) {
				$(this).parent().append('<p id="rcp-cancel-subscription-wrap"><input type="checkbox" id="rcp-cancel-subscription" name="cancel_subscription" value="1"><label for="rcp-cancel-subscription">' + rcp_vars.cancel_subscription + '</label></p>');
			}
			$('#rcp-revoke-access-wrap').show();
		} else {
			$('#rcp-cancel-subscription-wrap').remove();
			$('#rcp-revoke-access-wrap').hide();
		}
	});

	// Show "Revoke access now" checkbox when marking as cancelled via bulk edit.
	$('#rcp-bulk-member-action').on('change', function () {
		if ('mark-cancelled' == $(this).val()) {
			$('#rcp-revoke-access-wrap').show();
		} else {
			$('#rcp-revoke-access-wrap').hide();
		}
	});

	// Show/hide auto renew default based on settings.
	$('#rcp_settings_auto_renew').on('change', function() {
		if( '3' == $(this).val() ) {
			$(this).parents('tr').next().css('display', 'table-row');
		} else {
			$(this).parents('tr').next().css('display', 'none');
		}
	});

	// Show/hide email fields based on their activation state.
	$('.rcp-disable-email').on('change', function () {
		var subject  = $(this).parents('tr').next();
		var body     = subject.next();
		var disabled = false;

		if( 'SELECT' == $(this).prop('tagName') && 'off' == $(this).val() ) {
			// Select dropdowns, like email verification.
			disabled = true;
		} else {
			// Checkboxes.
			disabled = $(this).prop('checked');
		}

		if( true === disabled ) {
			subject.css('display', 'none');
			body.css('display', 'none');
		} else {
			subject.css('display', 'table-row');
			body.css('display', 'table-row');
		}
	});

	// Show/hide "times billed" box when option changes.
	$( '#rcp-maximum-renewals-setting' ).on( 'change', function() {
		var after_payment_field = $(this).parents('tr').next();
		var show = 'forever' !== $(this).val();

		$('#rcp-maximum-renewals').toggle( show );
		after_payment_field.toggle( show );
	});

	// Changes the currency symbol in the Currency Position dropdown to match the Currency setting.
	var currencySelect = $('#rcp_settings\\[currency\\]');
	if (currencySelect.length) {
		var currencies = JSON.parse(rcp_vars.currencies);
		var currentSymbol = currencies[currencySelect.val()].match(/\(([^)]+)\)/);
		if ( null == currentSymbol ) {
			currentSymbol = currencySelect.val();
		} else {
			currentSymbol = currentSymbol[1];
		}
		var currencyPositionBefore = $('#rcp_settings\\[currency_position\\] option[value="before"]');
		var currencyPositionAfter = $('#rcp_settings\\[currency_position\\] option[value="after"]');

		currencyPositionBefore.text(function () {
			// If using kr, add a space after the symbol.
			if ( 'NOK' === currencySelect.val() ) {
				currentSymbol = currentSymbol + ' ';
			}
			return $(this).text().replace("$", currentSymbol);
		});

		currencyPositionAfter.text(function () {
			// If using kr, add a space before the symbol.
			if ( 'NOK' === currencySelect.val() ) {
				currentSymbol = ' ' + currentSymbol;
			}
			return $(this).text().replace("$", currentSymbol);
		});

		$(currencySelect).on('change', function () {
			var currencyCode = $(this).val();
			var newCurrency = currencies[$(this).val()].match(/\(([^)]+)\)/);
			if ( null == newCurrency ) {
				newCurrency = currencyCode;
			} else {
				newCurrency = newCurrency[1];
			}

			currencyPositionBefore.text(function () {
				// If using kr, add a space after the symbol.
				if ( 'NOK' === currencyCode ) {
					newCurrency = newCurrency + ' ';
				}
				return $(this).text().replace(currentSymbol, newCurrency);
			});
			currencyPositionAfter.text(function () {
				// If using kr, add a space before the symbol.
				if ( 'NOK' === currencyCode ) {
					newCurrency = ' ' + newCurrency;
				}
				return $(this).text().replace(currentSymbol, newCurrency);
			});

			currentSymbol = newCurrency;
		});
	}

	// Customer: show/hide fields based on new or existing user.
	$('input[name="rcp_customer_user_account"]:radio').on( 'change', function() {
		if ( 'new' === this.value ) {
			$('.rcp-customer-new-user-field').show();
		} else {
			$('.rcp-customer-new-user-field').hide();
		}
	});

	/**
	 * Membership management screen JS
	 */
	var RCP_Membership = {

		vars: {
			member_card_wrap_editable: $('.rcp-membership-card-wrapper .rcp-editable'),
			member_card_wrap_edit_item: $('.rcp-membership-card-wrapper .rcp-edit-item')
		},

		init: function () {
			this.recurring_select();
			this.edit_membership_level();
			this.confirm_level_change();
			this.edit_recurring_price();
			this.edit_created_date();
			this.edit_expiration();
			this.never_expires();
			this.edit_gateway_customer_id();
			this.edit_gateway_subscription_id();
			this.set_billing_cycle();

			// Trigger change to set cycle on initial page load.
			$('#rcp-add-membership-info #rcp-membership-level').trigger( 'change' );
		},

		/**
		 * Edit Subscription Text Input
		 *
		 * @param link object The edit/cancelled element the user clicked.
		 * @param input object The editable field.
		 *
		 * @since 3.0
		 */
		edit_membership_input: function ( link, input ) {

			// User clicks edit
			if ( link.text() === rcp_vars.action_edit ) {
				// Preserve current value
				link.data( 'current-value', input.val() );
				// Update text to 'cancel'
				link.text( rcp_vars.action_cancel );
			} else {
				// User clicked cancel, return previous value
				input.val( link.data('current-value') );
				// Update link text back to 'edit'
				link.text( rcp_vars.action_edit );
			}

		},

		edit_membership_level: function () {
			$( '#rcp-edit-membership-level' ).on( 'click', function ( e ) {
				e.preventDefault();

				let link = $( this );
				let created_input = $( '#rcp-membership-level' );

				RCP_Membership.edit_membership_input( link, created_input );

				$('.rcp-current-membership-level').toggle();
				$( '#rcp-membership-level' ).toggle();
				$( '#rcp-change-membership-level-button' ).toggle();
			} );
		},

		confirm_level_change: function () {
			$( '#rcp-change-membership-level-button' ).on( 'click', function ( e ) {
				if(confirm(rcp_vars.change_membership_level)) {
					return true;
				}
				return false;
			} );
		},

		/**
		 * Editing the "recurring" flag for a membership.
		 *
		 * @since 3.0
		 */
		recurring_select: function () {
			$( document.body ).on( 'change', '#rcp-recurring', function ( e ) {
				if ( $( this ).prop( 'checked' ) ) {
					$( '#rcp-membership-recurring-enable-auto-renew' ).show();
					$( '#rcp-membership-recurring-disable-auto-renew' ).hide();
				} else {
					$( '#rcp-membership-recurring-enable-auto-renew' ).hide();
					$( '#rcp-membership-recurring-disable-auto-renew' ).show();
				}

				$( '#rcp-membership-recurring-update-notice' ).slideToggle();
			} );
		},

		/**
		 * Editing the recurring price for a membership.
		 *
		 * @since 3.2
		 */
		edit_recurring_price: function () {
			let recurring_field = $( '#rcp-recurring-amount' );

			if ( ! recurring_field.length ) {
				return;
			}

			recurring_field.on( 'keyup', function ( e ) {
				$( '#rcp-membership-recurring-price-update-notice' ).slideDown();
			} );
		},

		/**
		 * Editing the expiration date for a membership.
		 *
		 * @since 3.0
		 */
		edit_created_date: function () {
			$( '#rcp-edit-membership-created' ).on( 'click', function ( e ) {
				e.preventDefault();

				let link = $( this );
				let created_input = $( '#rcp-membership-created' );

				RCP_Membership.edit_membership_input( link, created_input );

				$( '.rcp-membership-created' ).toggle();
			} );
		},

		/**
		 * Editing the expiration date for a membership.
		 *
		 * @since 3.0
		 */
		edit_expiration: function () {
			$( '#rcp-edit-membership-expiration' ).on( 'click', function ( e ) {
				e.preventDefault();

				let link = $( this );
				let expiration_input = $( '#rcp-membership-expiration' );

				RCP_Membership.edit_membership_input( link, expiration_input );

				$( '.rcp-membership-expiration' ).toggle();
				$( '#rcp-membership-expiration-update-notice' ).slideToggle();
				$( '.rcp-membership-expiration-none-wrap' ).toggle();
			} );
		},

		/**
		 * When "never expires" is checked, wipe the expiration date.
		 */
		never_expires: function () {
			$( '#rcp-membership-expiration-none' ).on( 'change', function () {
				if ( $( this ).is( ':checked' ) ) {
					$( '#rcp-membership-expiration' ).val( '' );
				}
			} );
		},

		/**
		 * Editing the gateway customer ID for a membership.
		 *
		 * @since 3.0
		 */
		edit_gateway_customer_id: function () {
			$( '#rcp-edit-membership-gateway-customer-id' ).on( 'click', function ( e ) {
				e.preventDefault();

				let link = $( this );
				let gateway_input = $( '#rcp-membership-gateway-customer-id' );

				RCP_Membership.edit_membership_input( link, gateway_input );

				$( '#rcp-membership-gateway-customer-id' ).toggle();
			} );
		},

		/**
		 * Editing the gateway subscription ID for a membership.
		 *
		 * @since 3.0
		 */
		edit_gateway_subscription_id: function () {
			$( '#rcp-edit-membership-gateway-subscription-id' ).on( 'click', function ( e ) {
				e.preventDefault();

				let link = $( this );
				let gateway_input = $( '#rcp-membership-gateway-subscription-id' );

				RCP_Membership.edit_membership_input( link, gateway_input );

				$( '#rcp-membership-gateway-subscription-id' ).toggle();
				$( '#rcp-membership-gateway-subscription-id-update-notice' ).slideToggle();
			} );
		},

		/**
		 * Set the billing cycle fields when changing the membership level.
		 *
		 * @since 3.0
		 */
		set_billing_cycle: function () {
			$('#rcp-add-membership-info').on('change', '#rcp-membership-level', function () {
				let object_id = $(this).val();
				let initial_amount = $('#rcp-initial-amount');
				let recurring_amount = $('#rcp-recurring-amount');
				let recurring_wrap = $('#rcp-billing-cycle-recurring');

				let data = {
					action: 'rcp_get_membership_billing_cycle',
					object_id: object_id,
					rcp_nonce: rcp_vars.rcp_member_nonce
				};

				$.post(ajaxurl, data, function(response) {

					if ( 'free' === response.data ) {
						initial_amount.val('0.00');
						recurring_amount.val('0.00');
					} else {
						initial_amount.val(response.data.initial_amount);
						recurring_amount.val(response.data.recurring_amount);
					}

					if ( 'free' === response.data || 0 == response.data.recurring_amount ) {
						recurring_wrap.hide();
					} else {
						recurring_wrap.show();
					}
				});
			});
		},

	};
	RCP_Membership.init();
	let RCP_Settings = {

		init: function() {
			this.listeners();
		},

		listeners: function() {
			let sandboxCheckbox = document.getElementById( 'rcp_settings[sandbox]' );

			if( ! sandboxCheckbox ) {
				return;
			}

			sandboxCheckbox.addEventListener( 'change', function() {
				let testpub = document.getElementById( 'rcp_settings[stripe_test_publishable]' );
				let livepub = document.getElementById( 'rcp_settings[stripe_live_publishable]' );
				let notice = document.getElementById( 'rcp-sandbox-toggle-notice' );

				if( this.checked && testpub.value === '' ) {
					RCP_Settings.showErrorNotice( notice );
				}

				if( ! this.checked && livepub.value === '' ) {
					RCP_Settings.showErrorNotice( notice );
				}
			} );
		},

		showErrorNotice: function( element = false ) {
			if( ! element ) {
				return;
			}

			element.className = 'notice error';
			element.style.visibility = 'visible';
		}
	};

	if( window.adminpage === 'restrict_page_rcp-settings' ) {
		RCP_Settings.init();
	}

	// When the general tab is clicked on the settings page, change the forms action to maintain tab after form submission
	$("#general-tab").on( 'click', function() {
		$(".rcp_options_form").attr("action", "options.php#general");
	});

	// If the tab is active on load then update the action for the form
	if ( $( "#general-tab" ).hasClass( "nav-tab-active" ) ) {
		$( ".rcp_options_form" ).attr( "action", "options.php#general" );
	}

	// When the payments tab is clicked on the settings page, change the forms action to maintain tab after submission
	$( "#payments-tab" ).on( 'click', function() {
		$( ".rcp_options_form").attr( "action", "options.php#payments" );
	});

	// If the tab is active on load then update the action for the form
	if ( $( "#payments-tab" ).hasClass( "nav-tab-active" ) ) {
		$( ".rcp_options_form" ).attr( "action", "options.php#payments" );
	}

	// When the emails tab is clicked on the settings page, change the forms action to maintain tab after submission
	$( "#emails-tab" ).on( 'click', function() {
		$( ".rcp_options_form" ).attr( "action", "options.php#emails" );
	});

	// If the tab is active on load then update the action for the form
	if ( $( "#emails-tab" ).hasClass( "nav-tab-active" ) ) {
		$( ".rcp_options_form" ).attr( "action", "options.php#emails" );
	}

	// When the invoices tab is clicked on the settings page, change the forms action to maintain tab after submission
	$( "#invoices-tab" ).on( 'click', function() {
		$( ".rcp_options_form").attr("action", "options.php#invoices" );
	});

	// If the tab is active on load then update the action for the form
	if ( $( "#invoices-tab" ).hasClass( "nav-tab-active" ) ) {
		$( ".rcp_options_form" ).attr( "action", "options.php#invoices" );
	}

	// When the misc tab is clicked on the settings page, change the forms action to maintain tab after submission
	$( "#misc-tab" ).on( 'click', function() {
		$( ".rcp_options_form" ).attr( "action", "options.php#misc" );
	});

	// If the tab is active on load then update the action for the form
	if ( $( "#misc-tab" ).hasClass( "nav-tab-active" ) ) {
		$(".rcp_options_form").attr("action", "options.php#misc");
	}

	// Remove 2Checkout option if it is not checked.
	if ( $( "#rcp_settings[gateways][twocheckout]" ).attr('checked', false) ) {
		//	Remove the input
		$( ".twocheckout_settings" ).hide();
		$( ".twocheckout_secret_word" ).hide();
		$( ".twocheckout_private_key" ).hide();
		$( ".twocheckout_test_publishable_key" ).hide();
		$( ".twocheckout_test_seller_id" ).hide();
		$( ".twocheckout_live_private_key" ).hide();
		$( ".twocheckout_live_publishable_key" ).hide();
		$( ".twocheckout_live_seller_id" ).hide();
	}

	$( "#rcp-code" ).on( 'focusout', function() {
		$(this).val($(this).val().replace(/[^a-z0-9]/gi, ''));
	});

	/*
	 * Validate the Stripe Statement Descriptor
	 *
	 * https://stripe.com/docs/statement-descriptors
	 *
	 * @since 3.15.14
	 */
	$( "#rcp_settings\\[statement_descriptor\\]" ).on( 'keypress', function(event) {
		let st_descriptor_value = $(this).val();
		let keyCode = (event.keyCode ? event.keyCode : event.which); // Normalize the key code.

		if( (keyCode >= 65 && keyCode <= 90) // A-Z
			||
			(keyCode >= 97  && keyCode <= 122) // a-z
			||
			(keyCode >= 48  && keyCode <= 57) // 0-9
		)
		{
			if ( st_descriptor_value.length > 22) {
				st_descriptor_value = st_descriptor_value.substring(0, 22);
			}
			$(this).val(st_descriptor_value);
			return true;
		}
		else {
			return false;
		}

	});

	$( '#restrict_content_legacy_switch' ).on( 'click', function() {
		$.ajax({
			data: {
				action: 'rc_process_legacy_switch',
				rc_process_legacy_nonce: $( '#rcp_settings_nonce').val(),
			},
			type: "post",
			url: ajaxurl,
			success: function( response ) {
				if ( response.success ) {
					window.location.assign( rcp_vars.downgrade_redirect );
				}
			},
			error: function( response ) {
				console.error( response );
			}
		});
	});

	$(".toggle-credentials").on('click', function() {
		let inputName = $( this ).attr( 'toggle' ).toString();
		let inputObject = $( document.getElementById( inputName ) );

		if ( $( inputObject ).attr( "type" ) === "password" ) {
			$( inputObject ).attr( "type", "text" );
			$( this ).removeClass( "dashicons dashicons-visibility" );
			$( this ).addClass( "dashicons dashicons-hidden" );
		} else {
			$( inputObject ).attr( "type", "password" );
			$( this ).removeClass( "dashicons dashicons-hidden" );
			$( this ).addClass( "dashicons dashicons-visibility" );
		}
	});
	$("#rcp_setting_braintree_toggle_live").on('click', function() {
		var toggle = document.getElementById('rcp_setting_braintree_toggle_live');
		var textArea = document.getElementById('rcp_settings[braintree_live_encryptionKey]');
		var input = document.getElementById('rcp_settings[braintree_live_encryptionKey_input]');

		if ( textArea.style.display === "none" ) {
			input.style.display = 'none';
			textArea.style.display = 'inline-block';
			toggle.className =  "";
			toggle.className = "dashicons dashicons-hidden";
		} else {
			input.style.display = 'inline-block';
			textArea.style.display = 'none';
			toggle.className =  "";
			toggle.className = "dashicons dashicons-visibility";
		}
	});
	$("#rcp_setting_braintree_toggle_sandbox").on('click', function() {
		var toggle = document.getElementById('rcp_setting_braintree_toggle');
		var textArea = document.getElementById('rcp_settings[braintree_sandbox_encryptionKey]');
		var input = document.getElementById('rcp_settings[braintree_sandbox_encryptionKey_input]');

		if ( textArea.style.display === "none" ) {
			input.style.display = 'none';
			textArea.style.display = 'inline-block';
			toggle.className =  "";
			toggle.className = "dashicons dashicons-hidden";
		} else {
			input.style.display = 'inline-block';
			textArea.style.display = 'none';
			toggle.className =  "";
			toggle.className = "dashicons dashicons-visibility";
		}
	});


	$('body').addClass('restrict-content');
});

function validatePositiveNumber(input) {
	if (input.value < 0) {
		input.value = 0;
	}
}
