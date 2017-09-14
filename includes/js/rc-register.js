"use strict";

jQuery(document).ready(function($) {

	let form = $('#rc_registration_form');

	form.on('click', '#rc_submit_registration', function(event) {

		event.preventDefault();

		$('.rc-message.error').remove();

		$(event.target).val(rc_register_options.strings.please_wait);

		$.ajax({
			data: {
				action: 'rc_process_registration_form',
				rc_user_login: form.find('#rc_user_login').val(),
				rc_user_email: form.find('#rc_user_email').val(),
				rc_user_pass: form.find('#rc_password').val(),
				rc_user_pass_confirm: form.find('#rc_password_again').val(),
				rc_user_first: form.find('#rc_user_first').val(),
				rc_user_last: form.find('#rc_user_last').val(),
				rc_register_nonce: $('#rc_register_nonce').val(),
				rc_redirect: form.find('#rc_redirect').val()
			},
			type: "post",
			url: rc_register_options.ajax_url,
			success: function(response) {
				if ( response.success ) {
					window.location.assign(response.data.redirect);
				} else if ( response.data.errors ) {
					$(response.data.errors).insertBefore('#rc_submit_wrap');
					$(event.target).val(rc_register_options.strings.register);
				}
			},
			error: function(response) {
				console.log(response);
			}
		});
	});
});