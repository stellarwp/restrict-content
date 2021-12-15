"use strict";

jQuery(document).ready(function($) {
   $( '.rcp-pro-button' ).on( 'click', function() {
       processLegacySwitch();
   });

    $( '#restrict_content_legacy_switch' ).on( 'click', function() {
        processLegacySwitch();
    });

   function processLegacySwitch() {
       $.ajax({
           data: {
               action: 'rc_process_legacy_switch',
               rc_process_legacy_nonce: $( '#rcp_settings_nonce').val(),
           },
           type: "post",
           url: rcp_admin_settings_options.ajax_url,
           success: function( response ) {
               console.dir(response);
               if ( response.success ) {
                   window.location.assign( rcp_admin_settings_options.upgrade_redirect );
               }
           },
           error: function( response ) {
               console.dir( response );
           }
       });
   }
});