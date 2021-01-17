// Copyright (c) 2019 Damien Regad
// Licensed under the MIT license

// noinspection ES6ConvertVarToLetConst
/**
 * Namespace for global function used in this script
 */
var SourceGithub = SourceGithub || {};

/**
 * Return MantisBT REST API URL for given endpoint
 * @param {string} endpoint
 * @returns {string} REST API URL
 */
SourceGithub.rest_api = function(endpoint) {
	// Using the full URL (through index.php) to avoid issues on sites
	// where URL rewriting is not working
	return "api/rest/index.php/plugins/SourceGithub/" + endpoint;
};

jQuery(function($) {
	const webhook_secret = $("#hub_webhook_secret");
	const status_message = $('#webhook_status > span');

	$('#hub_app_client_id, #hub_app_secret').on("change", set_visibility);
	$('#btn_auth_revoke').on("click", revoke_token);
	$('#webhook_create > button').on("click", webhook_create);

	webhook_secret.on("change", function() {
		if (webhook_secret.val() != webhook_secret.attr('value')) {
			set_status_icon("fa-exclamation-triangle orange");
			console.log(webhook_secret.data('changed'));
			status_message.text(webhook_secret.data('changed'));
			disable_webhook_button();
		} else {
			set_status_icon()
			status_message.text("");
			disable_webhook_button(false);
		}
	});

	function set_status_icon(icon='') {
		$('#webhook_status > i')
			.removeClass("fa-exclamation-triangle fa-check green orange red")
			.addClass(icon);
	}

	// The PHP code initially hides all token authorization elements using the.
	// 'hidden' class, which we need to remove so we can set visibility using
	// show/hide functions
	set_visibility();
	$('.sourcegithub_token, #id_secret_missing').removeClass('hidden');

	function disable_webhook_button(value=true) {
		$('#webhook_create > button').prop("disabled", value);
	}

	function set_visibility() {
		const div_id_secret_missing = $('#id_secret_missing');
		const client_id = $('#hub_app_client_id');
		const secret = $('#hub_app_secret');

		// If Client ID and secret are set and equal to the recorded values
		// for the repository, we hide the information message and display the
		// authorize or revoke button and authorization status as needed.
		if (   client_id.val() !== ''
			&& client_id.val() === client_id.data('original')
			&& secret.val() !== ''
			&& secret.val() === secret.data('original')
		) {
			const div_token_authorized = $('#token_authorized');
			const div_token_missing = $('#token_missing');
			const div_webhook = $('#webhook_create');
			const token = div_token_authorized.children('input');

			div_id_secret_missing.hide();
			if (token.val() !== '') {
				div_token_authorized.add(div_webhook).show();
				div_token_missing.hide();
			} else {
				div_token_authorized.add(div_webhook).hide();
				div_token_missing.show();
			}
		} else {
			div_id_secret_missing.show();
			$('.sourcegithub_token').hide();
		}
	}

	function revoke_token() {
		const repo_id = $('#repo_id').val();

		$.ajax({
			type: 'DELETE',
			url: SourceGithub.rest_api(repo_id + '/token'),
			success: function() {
					$('#hub_app_access_token').val('');
					set_visibility();
				}
		});
	}

	function webhook_create() {
		const repo_id = $('#repo_id').val();

		$.ajax({
			type: 'POST',
			dataType: 'json',
			url: SourceGithub.rest_api(repo_id + '/webhook'),
			success: function(data, textStatus, xhr) {
				set_status_icon("fa-check green");
				status_message.text(xhr.statusText);
			},
			error: function(xhr, textStatus, errorThrown) {
				set_status_icon("fa-exclamation-triangle red");

				if (xhr.status === 409) {
					// noinspection JSUnresolvedVariable
					status_message.html(
						'<a href="' + xhr.responseJSON.web_url + '">' + errorThrown + '</a>'
					);
				} else {
					status_message.text(errorThrown);
				}

				console.error(
					'Webhook creation failed',
					{ error: errorThrown, details: xhr.responseJSON, request: this.url, x: textStatus }
				);
			}
		});
	}

});
