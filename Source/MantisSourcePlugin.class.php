<?php
# Copyright (C) 2008-2009 John Reese, LeetCode.net
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU Affero General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Affero General Public License for more details.

/**
 * Abstract class for simplifying creation of source control plugins.
 * @author John Reese
 */ 
abstract class MantisSourcePlugin extends MantisPlugin {
	public function hooks() {
		return array(
			'EVENT_SOURCE_INTEGRATION'		=> 'integration',
			'EVENT_SOURCE_PRECOMMIT'		=> 'precommit',
		);
	}

	/**
	 * A short, unique, lowercase string representing the plugin's source control type.
	 */
	public $type = null;

	/**
	 * Get a long, proper string representing the plugin's source control type.
	 * Should be localized if possible.
	 * @return string Source control name
	 */
	abstract public function show_type();

	/**
	 * Get a string representing the given repository and changeset.
	 * @param object Repository
	 * @param object Changeset
	 * @return string Changeset string
	 */
	abstract public function show_changeset( $p_repo, $p_changeset);

	/**
	 * Get a string representing a file for a given repository and changeset.
	 * @param object Repository
	 * @param object Changeset
	 * @param object File
	 * @return string File string
	 */
	abstract public function show_file( $p_repo, $p_changeset, $p_file );

	/**
	 * Get a URL to a view of the repository at the given changeset.
	 * @param object Repository
	 * @param object Changeset
	 * @return string URL
	 */
	abstract public function url_repo( $p_repo, $t_changeset=null );

	/**
	 * Get a URL to a diff view of the given changeset.
	 * @param object Repository
	 * @param object Changeset
	 * @return string URL
	 */
	abstract public function url_changeset( $p_repo, $p_changeset );

	/**
	 * Get a URL to a view of the given file at the given changeset.
	 * @param object Repository
	 * @param object Changeset
	 * @param object File
	 * @return string URL
	 */
	abstract public function url_file( $p_repo, $p_changeset, $p_file );

	/**
	 * Get a URL to a diff view of the given file at the given changeset.
	 * @param object Repository
	 * @param object Changeset
	 * @param object File
	 * @return string URL
	 */
	abstract public function url_diff( $p_repo, $p_changeset, $p_file );

	/**
	 * Output form elements for custom repository data.
	 * @param object Repository
	 */
	public function update_repo_form( $p_repo ) {}

	/**
	 * Process formelements for custom repository data.
	 * @param object Repository
	 */
	public function update_repo( $p_repo ) {}

	/**
	 * If necessary, check GPC inputs to determine if the checkin data
	 * is for a repository handled by this VCS type.
	 * @return array Array with "repo"=>Repository, "data"=>...
	 */
	public function precommit() {}

	/**
	 * Translate commit data to Changeset objects for the given repo.
	 * @param object Repository
	 * @param mixed Commit data
	 * @return array Changesets
	 */
	public function commit( $p_repo, $p_data ) {}

	/**
	 * Post-process changesets from checkin.
	 * @param object Repository
	 * @param array Changesets
	 */
	public function postcommit( $p_repo, $p_changesets ) {}

	/**
	 * Initiate an import of changeset data for the entire repository.
	 * @param object Repository
	 * @return array Changesets
	 */
	public function import_full( $p_repo ) {}

	/**
	 * Initiate an import of changeset data not yet imported.
	 * @param object Repository
	 * @return array Changesets
	 */
	public function import_latest( $p_repo ) {}

	/**
	 * Initialize contact with the integration framework.
	 * @return object The plugin object
	 */
	final public function integration( $p_event ) {
		return $this;
	}

	/**
	 * Post-process changesets from importing latest data.
	 * Not called after a full import.
	 * @param object Repository
	 * @param array Changesets
	 */
	public function postimport( $p_repo, $p_changesets ) {}

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

	public $type = 'generic';

	function show_type() {
		return 'Generic';
	}

	function show_changeset( $p_repo, $p_changeset ) {
		return $p_repo->type . ' ' . $p_changeset->revision;
	}

	function show_file( $p_repo, $p_changeset, $p_file ) {
		return $p_file->filename . ' (' . $p_file->revision . ')';
	}

	function url_repo( $p_repo, $t_changeset=null ) {
		return $p_repo->url;
	}

	function url_changeset( $p_repo, $p_changeset ) {
		return $p_repo->url;
	}

	function url_file( $p_repo, $p_changeset, $p_file ) {
		return $p_repo->url;
	}

	function url_diff( $p_repo, $p_changeset, $p_file ) {
		return $p_repo->url;
	}
}
