"use strict";

var RCP_Import = {

	listen: function () {

		var self = this;

		jQuery('.rcp-import-form').ajaxForm({
			beforeSubmit: self.before_submit,
			success: self.success,
			complete: self.complete,
			dataType: 'json',
			error: self.error
		} );

	},

	/**
	 * Triggers before the form is submitted.
	 *
	 * @param {array}  form_array
	 * @param {object} form_object
	 * @param {object} options
	 */
	before_submit: function ( form_array, form_object, options ) {

		let error_wrap = form_object.find( '#rcp-import-csv-errors' );
		let spinner    = form_object.find('.spinner');
		error_wrap.empty().hide();
		spinner.addClass('is-active').show();

		//check whether client browser fully supports all File API
		if ( window.File && window.FileReader && window.FileList && window.Blob ) {

			// HTML5 File API is supported by browser

		} else {

			error_wrap.append( rcp_csv_import_vars.unsupported_browser ).show();
			spinner.removeClass( 'is-active' ).hide();

			return false;
		}

	},

	/**
	 * On success
	 *
	 * @param responseText
	 * @param statusText
	 * @param xhr
	 * @param form
	 */
	success: function( responseText, statusText, xhr, form ) {},

	/**
	 * On form submission completion
	 *
	 * Show column mapping options and then listen to process the steps.
	 *
	 * @param {object} xhr
	 */
	complete: function( xhr ) {
		const response = jQuery.parseJSON( xhr.responseText );
		const form     = jQuery( '.rcp-import-form' );

		// Hide the spinner.
		form.find('.spinner').removeClass('is-active').hide();

		if ( response.success ) {
			form.find( '.rcp-import-file-wrap' ).remove();
			form.find( '.rcp-import-options' ).slideDown();

			// Show column mapping
			let select = form.find( 'select.rcp-import-csv-column' ),
				options = '',
				columns = response.data.columns.sort( function( a, b ) {
					if ( a < b ) {
						return -1;
					}
					if ( a > b ) {
						return 1;
					}
					return 0;
				} );

			jQuery.each( columns, function( key, value ) {
				options += '<option value="' + value + '">' + value + '</option>';
			} );

			select.append( options );

			select.on( 'change', function() {
				const key = jQuery( this ).val();

				if ( ! key ) {
					jQuery( this ).parent().next().html( '' );
				} else if ( false !== response.data.first_row[ key ] ) {
					jQuery( this ).parent().next().html( response.data.first_row[ key ] );
				} else {
					jQuery( this ).parent().next().html( '' );
				}
			} );

			jQuery.each( select, function() {
				jQuery( this ).val( jQuery( this ).attr( 'data-field' ) ).change();
			} );

			jQuery( document.body ).on( 'click', '.rcp-import-proceed', function( e ) {
				e.preventDefault();

				jQuery( this ).parents( '.submit' ).find( '.spinner' ).addClass( 'is-active' ).show();

				response.data.mapping = form.serialize();

				RCP_Import.begin_import( response.data );
			} );
		} else {
			RCP_Import.error( xhr );
		}
	},

	/**
	 * Error
	 *
	 * @todo
	 *
	 * @param xhr
	 */
	error: function( xhr ) {
		// Something went wrong. This will display error on form
		let error_wrap = jQuery( '#rcp-import-csv-errors' );

		// Empty errors.
		error_wrap.empty();

		const response = jQuery.parseJSON( xhr.responseText );

		if ( ! response.success ) {
			error_wrap.append( response.data.message ).show();
		} else {
			error_wrap.hide();
		}
	},

	/**
	 * Begin import
	 *
	 * Final ajax request to save mapping information.
	 *
	 * @param {object} data
	 */
	begin_import: function( data ) {

		data.action = 'rcp_process_csv_import';

		jQuery.ajax( {
			dataType: 'json',
			data: data,
			type: 'POST',
			url: ajaxurl,
			success: function ( response ) {
				if ( response.success ) {
					window.location = response.data;
				}
			},
			error: function ( response ) {
				console.log( new Date() + ' error' );
				console.log( response );
			}
		} );

	}

};

/**
 * Loads the import listener.
 */
jQuery(document).ready(function() {
	RCP_Import.listen();
} );
