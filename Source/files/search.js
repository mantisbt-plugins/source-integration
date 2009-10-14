/*
# Copyright (C) 2008-2009	John Reese
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
*/

$(document).ready( function() {

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
