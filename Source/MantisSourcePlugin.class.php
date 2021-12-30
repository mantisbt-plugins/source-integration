<?php

# Copyright (c) 2012 John Reese
# Licensed under the MIT license

require_once( 'MantisSourceBase.class.php' );

/**
 * Abstract class for simplifying creation of source control plugins.
 * @author John Reese
 */
abstract class MantisSourcePlugin extends MantisSourceBase {

	/**
	 * @var string Plugin Version string - MUST BE SET BY VCS PLUGIN
	 */
	const PLUGIN_VERSION = '0';

	/**
	 * @var string Minimum framework version - MUST BE SET BY VCS PLUGIN
	 */
	const FRAMEWORK_VERSION_REQUIRED = '0';

	/**
	 * @var string A short, unique, lowercase string representing the plugin's source control type.
	 */
	public $type = null;

	/**
	 * @var bool Override this to "true" if there are configuration options for the vcs plugin.
	 */
	public $configuration = false;

	/**
	 * Standard plugin registration.
	 *
	 * Child plugins are expected to override the following class constants
	 * - PLUGIN_VERSION
	 * - FRAMEWORK_VERSION_REQUIRED
	 */
	public function register() {
		$this->name = plugin_lang_get( 'title' );
		$this->description = plugin_lang_get( 'description' );

		$this->version = static::PLUGIN_VERSION;
		$this->requires = array(
			'MantisCore' => static::MANTIS_VERSION,
			'Source' => static::FRAMEWORK_VERSION_REQUIRED,
		);

		$this->author = 'John Reese';
		$this->contact = 'john@noswap.com';
		$this->url = 'https://github.com/mantisbt-plugins/source-integration/';
	}

	public function hooks() {
		return array(
			'EVENT_SOURCE_INTEGRATION'		=> 'integration',
			'EVENT_SOURCE_PRECOMMIT'		=> '_precommit',
		);
	}

	/**
	 * Define the VCS's ability to handle links to Pull Requests.
	 * If false, Pull Request links are not supported; otherwise this should be
	 * a sprintf template used to build an URL linking to a Pull Request, by
	 * appending it at the end of url_repo().
	 * e.g. '/pull/%s', %s being the PR's number
	 * @var false|string linkPullRequest
	 */
	public $linkPullRequest = false;

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
	abstract public function url_repo( $p_repo, $p_changeset=null );

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
	 * @param SourceRepo Repository
	 */
	public function update_repo( $p_repo ) {}

	/**
	 * Output form elements for configuration options.
	 *
	 * They are displayed at the bottom of the plugin's config page
	 * (see manage_config_page.php). The first row should have class 'spacer',
	 * and the function should output an even number of rows (including the
	 * spacer row), to ensure that the VCS-specific section always start on an
	 * even row (i.e. with white background). Add an empty row if needed.
	 */
	public function update_config_form() {}

	/**
	 * Process form elements for configuration options.
	 */
	public function update_config() {}

	/**
	 * If necessary, check GPC inputs to determine if the checkin data
	 * is for a repository handled by this VCS type.
	 * @return array|null Array with "repo"=>Repository, "data"=>...
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
	public $type = 'generic';

	function register() {
		$this->name = plugin_lang_get( 'title', 'Source' );
		$this->version = self::FRAMEWORK_VERSION;
	}

	function show_type() {
		return 'Generic';
	}

	function show_changeset( $p_repo, $p_changeset ) {
		return $p_repo->type . ' ' . $p_changeset->revision;
	}

	function show_file( $p_repo, $p_changeset, $p_file ) {
		return $p_file->filename . ' (' . $p_file->revision . ')';
	}

	function url_repo( $p_repo, $p_changeset=null ) {
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
