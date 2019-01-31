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
	$('#webhook_create').click(webhook_create);

	function webhook_create() {
		var repo_id = $('#repo_id').val();

		$.ajax({
			type: 'POST',
			url: SourceGithub.rest_api(repo_id + '/webhook'),
			success: function() {
				$('#webhook_create').prop("disabled", true);
			},
			error: function(xhr, textStatus, errorThrown) {
				console.error(
					'Webhook creation failed',
					{ error: errorThrown, request: this.url }
				);
			}
		});
	}

});
