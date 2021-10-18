"use strict";

jQuery(document).ready(function($) {
   $( '#restrict_content_legacy_switch' ).on( 'click', function() {
       $.ajax({
           data: {
               action: 'rc_process_legacy_switch',
               rc_process_legacy_nonce: $( '#rcp_settings_nonce').val(),
           },
           type: "post",
           url: rcp_admin_settings_options.ajax_url,
           success: function( response ) {
               if ( response.success ) {
                   window.location.assign( response.data.redirect );
               }
           },
           error: function( response ) {
               console.dir( response );
           }
       });
   });
});