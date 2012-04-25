<?php

# Copyright (c) 2012 John Reese
# Licensed under the MIT license

/**
 * Abstract class for simplifying creation of source control plugins.
 * @author John Reese
 */ 
abstract class MantisSourcePlugin extends MantisPlugin {
	public function hooks() {
		return array(
			'EVENT_SOURCE_INTEGRATION'		=> 'integration',
			'EVENT_SOURCE_PRECOMMIT'		=> '_precommit',
		);
	}

	/**
	 * A short, unique, lowercase string representing the plugin's source control type.
	 */
	public $type = null;

	/**
	 * Override this to "true" if there are configuration options for the vcs plugin.
	 */
	public $configuration = false;

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
	 * Process form elements for custom repository data.
	 * @param object Repository
	 */
	public function update_repo( $p_repo ) {}

	/**
	 * Output form elements for configuration options.
	 */
	public function update_config_form() {}

	/**
	 * Process form elements for configuration options.
	 */
	public function update_config() {}

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
	 * Pass the precommit event to the interface without the
	 * event paramater.
	 */
	final public function _precommit( $p_event ) {
		return $this->precommit();
	}
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
		$this->version = SourcePlugin::$framework_version;
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
