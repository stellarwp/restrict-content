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
				console.log( response )
			},
			error: function ( error ) {
				console.log( error )
			}
		});
	});
});
