"use strict";

jQuery(document).ready(function($) {
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
   };
});

function enableUpgradeButton() { // this function enables the "Use the new version of Restrict Content?" button when the checkbox is checked that tells them they can't downgrade again once they upgrade
    const upgradeBtn = document.querySelector("#restrict_content_legacy_switch");
    const upgradeCheckbox = document.querySelector("#restrict_content_legacy_switch_agree");
    upgradeBtn.disabled = (false) ? upgradeCheckbox.checked : !upgradeCheckbox.checked;
    }