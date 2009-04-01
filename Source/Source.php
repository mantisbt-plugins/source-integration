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

require_once( config_get_global( 'class_path' ) . 'MantisPlugin.class.php' );

/**
 * Creates an extensible API for integrating source control applications
 * with the Mantis bug tracker software.
 */ 
class SourcePlugin extends MantisPlugin {
	function register() {
		$this->name = plugin_lang_get( 'title' );
		$this->description = plugin_lang_get( 'description' );

		$this->version = plugin_lang_get( 'version' );
		$this->requires = array(
			'MantisCore' => '1.2.0',
			'Meta' => '0.1',
		);
		$this->page		= 'manage_config_page';

		$this->author	= 'John Reese';
		$this->contact	= 'jreese@leetcode.net';
		$this->url		= 'http://leetcode.net';
	}

	function config() {
		return array(
			'show_repo_link'	=> ON,
			'show_search_link'	=> OFF,

			'view_threshold'	=> VIEWER,
			'update_threshold'	=> UPDATER,
			'manage_threshold'	=> ADMINISTRATOR,

			'enable_porting'	=> OFF,

			'buglink_regex_1'	=> '/(?:bugs?|issues?|reports?)+\s+(?:#?(?:\d+)[,\.\s]*)+/i',
			'buglink_regex_2'	=> '/#?(\d+)/',

			'bugfix_resolving'	=> OFF,
			'bugfix_regex_1'	=> '/(?:fixe?s?|resolves?)+\s+(?:bugs?|issues?|reports?)+\s+(?:#?(?:\d+)[,\.\s]*)+/i',
			'bugfix_regex_2'	=> '/#?(\d+)/',

			'remote_checkin'	=> OFF,
			'checkin_urls'		=> serialize( array( 'localhost' ) ),

			'remote_imports'	=> OFF,
			'import_urls'		=> serialize( array( 'localhost' ) ),
		);
	}

	function events() {
		return array(
			'EVENT_SOURCE_GET_TYPES'		=> EVENT_TYPE_DEFAULT,	# Get a list of registered source control types

			'EVENT_SOURCE_SHOW_TYPE'		=> EVENT_TYPE_FIRST,	# Given a VCS type, return a long name (eg, svn -> Subversion)
			'EVENT_SOURCE_SHOW_CHANGESET'	=> EVENT_TYPE_FIRST,	# Return an appropriate Type/Revision string for a given changeset
			'EVENT_SOURCE_SHOW_FILE'		=> EVENT_TYPE_FIRST,	# Return an appropriate Filename/Revision string for a given changeset file

			'EVENT_SOURCE_URL_REPO'			=> EVENT_TYPE_FIRST,	# Return a URL to see data for the repository
			'EVENT_SOURCE_URL_CHANGESET'	=> EVENT_TYPE_FIRST,	# Return a URL to see data for a single changeset
			'EVENT_SOURCE_URL_FILE'			=> EVENT_TYPE_FIRST,	# Return a URL to see a given revision of a file
			'EVENT_SOURCE_URL_FILE_DIFF'	=> EVENT_TYPE_FIRST,	# Return a URL to see a given revision's diff of a file

			'EVENT_SOURCE_UPDATE_REPO_FORM'	=> EVENT_TYPE_FIRST,	# Output HTML form elements for a repository update
			'EVENT_SOURCE_UPDATE_REPO'		=> EVENT_TYPE_FIRST,	# Handle form data after submitting a repo update form

			'EVENT_SOURCE_PRECOMMIT'		=> EVENT_TYPE_FIRST,	# Allow plugins to try finding commit information before Source looks
			'EVENT_SOURCE_COMMIT'			=> EVENT_TYPE_FIRST,	# Source control commit handling, passed commit details from checkin script
			'EVENT_SOURCE_POSTCOMMIT'		=> EVENT_TYPE_EXECUTE,	# Allow processing of the newly-committed changesets

			'EVENT_SOURCE_IMPORT_FULL'		=> EVENT_TYPE_FIRST,	# Import an existing repository from scratch
			'EVENT_SOURCE_IMPORT_LATEST'	=> EVENT_TYPE_FIRST,	# Import the latest changesets from a repository
			'EVENT_SOURCE_POSTIMPORT'		=> EVENT_TYPE_EXECUTE,	# Allow processing of the newly-imported changesets
		);
	}

	function hooks() {
		return array(
			'EVENT_PLUGIN_INIT' => 'post_init',
			'EVENT_LAYOUT_RESOURCES' => 'css',
			'EVENT_MENU_MAIN' => 'menu_main',
		);
	}

	function init() {
		require_once( 'Source.API.php' );

		require_once( 'SourceIntegration.php' );
		plugin_child( 'SourceIntegration' );
	}

	function post_init() {
		# post-init register the generic source integration child plugin
		# so that it always has lowest priority.
		plugin_child( 'SourceGeneric' );
	}

	function css() {
		return '<link rel="stylesheet" type="text/css" href="' . plugin_file( 'style.css' ) . '"/>';
	}

	function menu_main() {
		$t_links = array();

		if ( plugin_config_get( 'show_repo_link' ) ) {
			$t_page = plugin_page( 'index', false, 'Source' );
			$t_lang = plugin_lang_get( 'repositories', 'Source' );
			$t_links[] = "<a href=\"$t_page\">$t_lang</a>";
		}

		if ( plugin_config_get( 'show_search_link' ) ) {
			$t_page = plugin_page( 'search_page', false, 'Source' );
			$t_lang = plugin_lang_get( 'search', 'Source' );
			$t_links[] = "<a href=\"$t_page\">$t_lang</a>";
		}

		return $t_links;
	}

	function schema() {
		return array(
			array( 'CreateTableSQL', array( plugin_table( 'repository' ), "
				id			I		NOTNULL UNSIGNED AUTOINCREMENT PRIMARY,
				type		C(8)	NOTNULL DEFAULT \" '' \" PRIMARY,
				name		C(128)	NOTNULL DEFAULT \" '' \" PRIMARY,
				url			C(250)	DEFAULT \" '' \",
				info		XL		NOTNULL DEFAULT \" '' \"
				" ) ),
			array( 'CreateTableSQL', array( plugin_table( 'changeset' ), "
				id			I		NOTNULL UNSIGNED AUTOINCREMENT PRIMARY,
				repo_id		I		NOTNULL UNSIGNED PRIMARY,
				revision	C(250)	NOTNULL PRIMARY,
				branch		C(250)	NOTNULL DEFAULT \" '' \",
				user_id		I		NOTNULL UNSIGNED DEFAULT '0',
				timestamp	T		NOTNULL,
				author		C(250)	NOTNULL DEFAULT \" '' \",
				message		XL		NOTNULL DEFAULT \" '' \",
				info		XL		NOTNULL DEFAULT \" '' \"
				" ) ),
			array( 'CreateIndexSQL', array( 'idx_changeset_stamp_author', plugin_table( 'changeset' ), 'timestamp, author' ) ),
			array( 'CreateTableSQL', array( plugin_table( 'file' ), "
				id			I		NOTNULL UNSIGNED AUTOINCREMENT PRIMARY,
				change_id	I		NOTNULL UNSIGNED,
				revision	C(250)	NOTNULL,
				filename	XL		NOTNULL
				" ) ),
			array( 'CreateIndexSQL', array( 'idx_file_change_revision', plugin_table( 'file' ), 'change_id, revision' ) ),
			array( 'CreateTableSQL', array( plugin_table( 'bug' ), "
				change_id	I		NOTNULL UNSIGNED PRIMARY,
				bug_id		I		NOTNULL UNSIGNED PRIMARY
				" ) ),
			array( 'AddColumnSQL', array( plugin_table( 'file' ), "
				action		C(8)	NOTNULL DEFAULT \" '' \"
				" ) ),
			array( 'AddColumnSQL', array( plugin_table( 'changeset' ), "
				parent		C(250)	NOTNULL DEFAULT \" '' \"
				" ) ),
			# 2008-10-02
			array( 'AddColumnSQL', array( plugin_table( 'changeset' ), "
				ported		C(250)	NOTNULL DEFAULT \" '' \"
				" ) ),
			array( 'AddColumnSQL', array( plugin_table( 'changeset' ), "
				author_email	C(250)	NOTNULL DEFAULT \" '' \"
				" ) ),
		);
	}

}


