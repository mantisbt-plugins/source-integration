/*
 * Copyright (c) 2012 John Reese
 * Licensed under the MIT license
 */

jQuery(document).ready( function($) {

	var typeselect = $("table.SourceFilters select.SourceType");
	var reposelect = $("table.SourceFilters select.SourceRepo");
	var branchselect = $("table.SourceFilters select.SourceBranch");

	function SourceTypeChange() {
		var options = $(this).children("option:selected");
		var types = new Array();
		options.each( function(index) {
				types.push( "SourceType" + this.value )
			});
		reposelect.children("option").each( function(index) {
				if ( this.value == "" ) {
					//continue;
				}

				var show = types.length < 1 || $(this).hasClass( "SourceAny" );
				for(var i = 0; !show && i < types.length; i++) {
					if ( types[i] == "SourceType" || $(this).hasClass( types[i] ) ) {
						show = true;
					}
				}

				if ( show ) {
					$(this).show();
				} else {
					$(this).hide();
					$(this).removeAttr("selected");
				}
			});

		reposelect.each( SourceRepoChange );
	}

	function SourceRepoChange() {
		var options = $(this).children("option:selected");
		var repos = new Array();
		options.each( function(index) {
				repos.push( "SourceRepo" + this.value )
			});
		branchselect.children("option").each( function(index) {
				if ( this.value == "" ) {
					//continue;
				}

				var show = repos.length < 1 || $(this).hasClass( "SourceAny" );
				for(var i = 0; !show && i < repos.length; i++) {
					if ( repos[i] == "SourceRepo" || $(this).hasClass( repos[i] ) ) {
						show = true;
					}
				}

				if ( show ) {
					$(this).show();
				} else {
					$(this).hide();
					$(this).removeAttr("selected");
				}
			});
	}

	typeselect.change( SourceTypeChange );
	reposelect.change( SourceRepoChange );

});
