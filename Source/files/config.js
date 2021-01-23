/*
 * Copyright (c) 2021 Damien Regad
 * Licensed under the MIT license
 */

jQuery(function($) {
	// Disable File Statistics checkbox if Repository Statistics is not checked
	$("#show_repo_stats").on("change", function() {
		$("#show_file_stats").prop("disabled", !this.checked);
		})
		.trigger("change");
});
