<?php
# Copyright (C) 2008	John Reese
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

if ( false === include_once( config_get( 'plugin_path' ) . 'Source/MantisSourcePlugin.class.php' ) ) {
	return;
}

class SourceGithubPlugin extends MantisSourcePlugin {
	function register() {
		$this->name = lang_get( 'plugin_SourceGithub_title' );
		$this->description = lang_get( 'plugin_SourceGithub_description' );

		$this->version = '0.1';
		$this->requires = array(
			'MantisCore' => '1.2.0',
			'Source' => '0.9a',
		);

		$this->author = 'John Reese';
		$this->contact = 'jreese@leetcode.net';
		$this->url = 'http://leetcode.net';
	}

	function get_types( $p_event ) {
		return array( 'github' => lang_get( 'plugin_SourceGithub_github' ) );
	}

	function show_type( $p_event, $p_type ) {
		if ( 'github' == $p_type ) {
			return lang_get( 'plugin_SourceGithub_github' );
		}
	}

	function show_changeset( $p_event, $p_repo, $p_changeset ) {
	}

	function show_file( $p_event, $p_repo, $p_changeset, $p_file ) {
	}

	function url_repo( $p_event, $p_repo, $t_changeset=null ) {
	}

	function url_changeset( $p_event, $p_repo, $p_changeset ) {
	}

	function url_file( $p_event, $p_repo, $p_changeset, $p_file ) {
	}

	function url_diff( $p_event, $p_repo, $p_changeset, $p_file ) {
	}

	function commit( $p_event, $p_repo, $p_data ) {
	}
}
