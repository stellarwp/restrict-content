jQuery(document).ready(function($) {

	// Dismissible notice hook in
	$( document ).on( 'click', '.rcp-plugin-migration-notice .notice-dismiss', function() {
		$.ajax({
			url: ajaxurl,
			method: 'POST',
			data: {
				action: 'rcp_ajax_dismissed_notice_handler',
				name: 'rcp-plugin-migration-notice',
				rcp_nonce: rcp_admin_notices_vars.rcp_dismissed_nonce
			},
			success: function ( response ) {
				console.log( response );
			},
			error: function ( error ) {
				console.log( error );
			}
		});
	});

	$( document ).on( 'click', '.restrict-content-upgrade-notice .notice-dismiss', function() {
		$.ajax({
			url: ajaxurl,
			method: 'POST',
			data: {
				action: 'rcp_ajax_dismissed_notice_handler',
				name: 'restrict-content-upgrade-notice',
				rcp_nonce: rcp_admin_notices_vars.rcp_dismissed_nonce
			},
			success: function ( response ) {
				console.log( response );
			},
			error: function ( error ) {
				console.log( error );
			}
		});
	});
	
	$( document ).on( 'click', '.restrict-content-bfcm-notice .notice-dismiss', function() {
		$.ajax({
			url: ajaxurl,
			method: 'POST',
			data: {
				action: 'rcp_ajax_dismissed_notice_handler',
				name: 'restrict-content-bfcm-notice',
				rcp_nonce: rcp_admin_notices_vars.rcp_dismissed_nonce
			},
			success: function ( response ) {
				console.log( response );
			},
			error: function ( error ) {
				console.log( error );
			}
		});
	});
});
