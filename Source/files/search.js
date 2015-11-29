/*
 * Copyright (c) 2012 John Reese
 * Copyright (c) 2015 Damien Regad
 * Licensed under the MIT license
 */

jQuery(document).ready( function($) {

	var typeselect = $("select.SourceType");
	var reposelect = $("select.SourceRepo");
	var branchselect = $("select.SourceBranch");

	/**
	 * Show/hide options in child selection list
	 * Given a relationship between a parent and its dependent (child) selection
	 * list, shows/hides the relevant options in the child depending on the
	 * options selected in the parent
	 * @param {Object} parent Select list being changed
	 * @param {Object} child  Dependent select list
	 */
	function ProcessSelectChange(parent, child) {
		var parentName = parent.className;
		var options = $(parent).children("option:selected");
		var values = new Array();

		options.each(
			function(index) {
				values.push(parentName + this.value)
			}
		);
		child.children("option").each(
			function(index) {
				if (this.value == "") {
					//continue;
				}

				var show = values.length < 1 || $(this).hasClass("SourceAny");
				for (var i = 0; !show && i < values.length; i++) {
					if (values[i] == parentName || $(this).hasClass(values[i])) {
						show = true;
					}
				}

				if (show) {
					$(this).show();
				} else {
					$(this).hide();
					$(this).removeAttr("selected");
				}
			}
		);
	}

	function SourceTypeChange() {
		ProcessSelectChange(this, reposelect);
		reposelect.each(SourceRepoChange);
	}

	function SourceRepoChange() {
		ProcessSelectChange(this, branchselect);
	}

	typeselect.change( SourceTypeChange );
	reposelect.change( SourceRepoChange );

});
