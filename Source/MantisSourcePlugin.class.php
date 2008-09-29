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

/**
 * Abstract class for simplifying creation of source control plugins.
 * @author John Reese
 */ 
abstract class MantisSourcePlugin extends MantisPlugin {
	function hooks() {
		return array(
			'EVENT_SOURCE_GET_TYPES'		=> 'get_types',

			'EVENT_SOURCE_SHOW_TYPE'		=> 'show_type',
			'EVENT_SOURCE_SHOW_CHANGESET'	=> 'show_changeset',
			'EVENT_SOURCE_SHOW_FILE'		=> 'show_file',

			'EVENT_SOURCE_URL_REPO'			=> 'url_repo',
			'EVENT_SOURCE_URL_CHANGESET'	=> 'url_changeset',
			'EVENT_SOURCE_URL_FILE'			=> 'url_file',
			'EVENT_SOURCE_URL_FILE_DIFF'	=> 'url_diff',

			'EVENT_SOURCE_UPDATE_REPO_FORM'	=> 'update_repo_form',
			'EVENT_SOURCE_UPDATE_REPO'		=> 'update_repo',

			'EVENT_SOURCE_PRECOMMIT'		=> 'precommit',
			'EVENT_SOURCE_COMMIT'			=> 'commit',

			'EVENT_SOURCE_IMPORT_FULL'		=> 'import_full',
			'EVENT_SOURCE_IMPORT_LATEST'	=> 'import_latest',
		);
	}

	/**
	 * Get a short, unique, lowercase string representing the plugin's source
	 * control type.
	 * @return string Source control type
	 */
	abstract function get_types( $p_event );

	/**
	 * Get a long, proper string representing the plugin's source control type.
	 * Should ignore any $p_type not matching the output from get_type()
	 * @param string Source control type
	 * @return string Source control name
	 */
	abstract function show_type( $p_event, $p_type );

	/**
	 * Get a string representing the given repository and changeset.
	 * @param object Repository
	 * @param object Changeset
	 * @return string Changeset string
	 */
	abstract function show_changeset( $p_event, $p_repo, $p_changeset);

	/**
	 * Get a string representing a file for a given repository and changeset.
	 * @param object Repository
	 * @param object Changeset
	 * @param object File
	 * @return string File string
	 */
	abstract function show_file( $p_event, $p_repo, $p_changeset, $p_file );

	/**
	 * Get a URL to a view of the repository at the given changeset.
	 * @param object Repository
	 * @param object Changeset
	 * @return string URL
	 */
	abstract function url_repo( $p_event, $p_repo, $t_changeset=null );

	/**
	 * Get a URL to a view of the given changeset.
	 * @param object Repository
	 * @param object Changeset
	 * @return string URL
	 */
	abstract function url_changeset( $p_event, $p_repo, $p_changeset );

	/**
	 * Get a URL to a view of the given file at the given changeset.
	 * @param object Repository
	 * @param object Changeset
	 * @param object File
	 * @return string URL
	 */
	abstract function url_file( $p_event, $p_repo, $p_changeset, $p_file );

	/**
	 * Get a URL to a diff view of the given file at the given changeset.
	 * @param object Repository
	 * @param object Changeset
	 * @param object File
	 * @return string URL
	 */
	abstract function url_diff( $p_event, $p_repo, $p_changeset, $p_file );

	function update_repo_form( $p_event, $p_repo ) {}
	function update_repo( $p_event, $p_repo ) {}

	function precommit( $p_event ) {}
	function commit( $p_event, $p_repo, $p_data ) {}

	function import_full( $p_event, $p_repo ) {}
	function import_latest( $p_event, $p_repo )	{}
}

/**
 * Generic Source integration plugin to handle anything it can once
 * other plugins have passed on handling the information.
 * Most generic source control integration possible.
 * Does not check source types, and should always be the last plugin
 * to execute the event.
 */
class SourceGenericPlugin extends MantisSourcePlugin {
	function register() {
		$this->name = plugin_lang_get( 'title', 'Source' );
		$this->version = plugin_lang_get( 'version', 'Source' );
	}

	function get_types( $p_event ) {
		return array('generic' => 'Generic');
	}

	function show_type( $p_event, $p_type ) {
		if ( 'generic' == strtolower( $p_type ) ) {
			return 'Generic';
		}

		return $p_type;
	}

	function show_changeset( $p_event, $p_repo, $p_changeset ) {
		return $p_repo->type . ' ' . $p_changeset->revision;
	}

	function show_file( $p_event, $p_repo, $p_changeset, $p_file ) {
		return $p_file->filename . ' (' . $p_file->revision . ')';
	}

	function url_repo( $p_event, $p_repo, $t_changeset=null ) {
		return $p_repo->url;
	}

	function url_changeset( $p_event, $p_repo, $p_changeset ) {
		return $p_repo->url;
	}

	function url_file( $p_event, $p_repo, $p_changeset, $p_file ) {
		return $p_repo->url;
	}

	function url_diff( $p_event, $p_repo, $p_changeset, $p_file ) {
		return $p_repo->url;
	}
}
