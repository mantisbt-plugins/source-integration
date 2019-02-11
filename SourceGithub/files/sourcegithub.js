// Copyright (c) 2019 Damien Regad
// Licensed under the MIT license

/**
 * Namespace for global function used in list_action.js
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

jQuery(document).ready(function($) {
	$('#webhook_create > button').click(webhook_create);

	function webhook_create() {
		var repo_id = $('#repo_id').val();
		var status_icon = $('#webhook_status > i');
		var status_message = $('#webhook_status > span');

		$.ajax({
			type: 'POST',
			url: SourceGithub.rest_api(repo_id + '/webhook'),
			success: function(data, textStatus, xhr) {
				status_icon.removeClass("fa-exclamation-triangle red").addClass("fa-check green");
				status_message.text(xhr.statusText);
				$('#webhook_create > button').prop("disabled", true);
			},
			error: function(xhr, textStatus, errorThrown) {
				status_icon.removeClass("fa-check green").addClass("fa-exclamation-triangle red");

				var details = JSON.parse(xhr.responseText);
				if (xhr.status === 409) {
					status_message.html(
						'<a href="' + details.web_url + '">' + errorThrown + '</a>'
					);
				} else {
					status_message.text(errorThrown);
				}

				console.error(
					'Webhook creation failed',
					{ error: errorThrown, details: details, request: this.url, x: textStatus }
				);
			}
		});
	}

});
