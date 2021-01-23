<?php

# Copyright (c) 2012 John Reese
# Licensed under the MIT license

require_once( 'MantisSourceBase.class.php' );

/**
 * Creates an extensible API for integrating source control applications
 * with the Mantis bug tracker software.
 */
class SourcePlugin extends MantisSourceBase {

	static $cache = array();

	const PLUGIN_VERSION = self::FRAMEWORK_VERSION;

	/**
	 * Error constants
	 */
	const ERROR_CHANGESET_MISSING_ID = 'changeset_missing_id';
	const ERROR_CHANGESET_MISSING_REPO = 'changeset_missing_repo';
	const ERROR_CHANGESET_INVALID_REPO = 'changeset_invalid_repo';
	const ERROR_FILE_MISSING = 'file_missing';
	const ERROR_FILE_INVALID_CHANGESET = 'file_invalid_changeset';
	const ERROR_PRODUCTMATRIX_NOT_LOADED = 'productmatrix_not_loaded';
	const ERROR_REPO_MISSING = 'repo_missing';
	const ERROR_REPO_MISSING_CHANGESET = 'repo_missing_changeset';

	/**
	 * Changeset link matching pattern.
	 * format: '<type>:<reponame>:<revision>:', where
	 * <type> = link type, 'c' or 's' for changeset details, 'd' or 'v' for diff
	 *          the type may be omitted; if unspecified, defaults to 'c'
	 * <repo> = repository name
	 * <rev>  = changeset revision ID (e.g. SVN rev number, GIT SHA, etc.)
	 * The match is not case-sensitive.
	 */
	const CHANGESET_LINKING_REGEX = '/(?:(?<=^|[^\w])([cdsvp]?):([^:\s][^:\n\t]*):([^:\s]+):)/i';

	function register() {
		$this->name = plugin_lang_get( 'title' );
		$this->description = plugin_lang_get( 'description' );

		$this->version = self::PLUGIN_VERSION;
		$this->requires = array(
			'MantisCore' => self::MANTIS_VERSION,
		);
		$this->page		= 'manage_config_page';

		$this->author	= 'John Reese';
		$this->contact	= 'john@noswap.com';
		$this->url		= 'https://github.com/mantisbt-plugins/source-integration/';
	}

	function config() {
		return array(
			'show_repo_link'	=> ON,
			'show_search_link'	=> OFF,
			'show_repo_stats'	=> ON,
			'show_file_stats'	=> OFF,

			'view_threshold'	=> VIEWER,
			'update_threshold'	=> UPDATER,
			'manage_threshold'	=> ADMINISTRATOR,
			'username_threshold' => DEVELOPER,

			'enable_linking'	=> ON,
			'enable_mapping'	=> OFF,
			'enable_porting'	=> OFF,
			'enable_resolving'	=> OFF,
			'enable_message'	=> OFF,
			'enable_product_matrix' => OFF,

			'buglink_regex_1'	=> '/(?:bugs?|issues?|reports?)+\s*:?\s+(?:#(?:\d+)[,\.\s]*)+/i',
			'buglink_regex_2'	=> '/#?(\d+)/',

			'bugfix_regex_1'	=> '/(?:fixe?d?s?|resolved?s?)+\s*:?\s+(?:#(?:\d+)[,\.\s]*)+/i',
			'bugfix_regex_2'	=> '/#?(\d+)/',
			'bugfix_status'		=> -1,
			'bugfix_resolution'	=> FIXED,
			'bugfix_status_pvm'	=> 0,
			'bugfix_handler'	=> ON,
			'bugfix_message'	=> 'Fix committed to $1 branch.',
			'bugfix_message_view_status'	=> VS_PUBLIC,

			'default_primary_branch' => 'master',

			'remote_checkin'	=> OFF,
			'checkin_urls'		=> serialize( array( 'localhost' ) ),

			'remote_imports'	=> OFF,
			'import_urls'		=> serialize( array( 'localhost' ) ),

			'api_key'           => '',
		);
	}

	function events() {
		return array(
			# Allow source integration plugins to announce themselves
			'EVENT_SOURCE_INTEGRATION' => EVENT_TYPE_DEFAULT,

			# Allow vcs plugins to pre-process commit data
			'EVENT_SOURCE_PRECOMMIT' => EVENT_TYPE_FIRST,

			# Allow other plugins to post-process commit data
			'EVENT_SOURCE_COMMITS' => EVENT_TYPE_EXECUTE,
			'EVENT_SOURCE_FIXED' => EVENT_TYPE_EXECUTE,
		);
	}

	function hooks() {
		return array(
			'EVENT_CORE_READY' => 'core_ready',
			'EVENT_MENU_MAIN' => 'menu_main',
			'EVENT_FILTER_COLUMNS' => 'filter_columns',
		);
	}

	function init() {
		require_once( 'Source.API.php' );

		require_once( 'SourceIntegration.php' );
		plugin_child( 'SourceIntegration' );
	}

	function errors() {
		$t_errors_list = array(
			self::ERROR_CHANGESET_MISSING_ID,
			self::ERROR_CHANGESET_MISSING_REPO,
			self::ERROR_CHANGESET_INVALID_REPO,
			self::ERROR_FILE_MISSING,
			self::ERROR_FILE_INVALID_CHANGESET,
			self::ERROR_PRODUCTMATRIX_NOT_LOADED,
			self::ERROR_REPO_MISSING,
			self::ERROR_REPO_MISSING_CHANGESET,
		);

		foreach( $t_errors_list as $t_error ) {
			$t_errors[$t_error] = plugin_lang_get( 'error_' . $t_error );
		}

		return array_merge( parent::errors(), $t_errors );
	}

	/**
	 * Register source integration plugins with the framework.
	 */
	function core_ready() {
		# register the generic vcs type
		plugin_child( 'SourceGeneric' );

		# initialize the vcs type cache
		SourceVCS::init();

		if ( plugin_config_get( 'enable_linking' ) ) {
			plugin_event_hook( 'EVENT_DISPLAY_FORMATTED', 'display_formatted' );
		}
	}

	function filter_columns()
	{
		require_once( 'classes/RelatedChangesetsColumn.class.php' );
		return array(
			'SourceRelatedChangesetsColumn',
		);
	}

	function menu_main() {
		$t_menu_options = array();

		if ( plugin_config_get( 'show_repo_link' ) ) {
			$t_page = plugin_page( 'index', false, 'Source' );
			$t_lang = plugin_lang_get( 'repositories', 'Source' );

			$t_menu_option = array(
				'title' => $t_lang,
				'url' => $t_page,
				'access_level' => plugin_config_get( 'view_threshold' ),
				'icon' => 'fa-code-fork'
			);

			$t_menu_options[] = $t_menu_option;
		}

		if ( plugin_config_get( 'show_search_link' ) ) {
			$t_page = plugin_page( 'search_page', false, 'Source' );
			$t_lang = plugin_lang_get( 'search', 'Source' );

			$t_menu_option = array(
				'title' => $t_lang,
				'url' => $t_page,
				'access_level' => plugin_config_get( 'view_threshold' ),
				'icon' => 'fa-search'
			);

			$t_menu_options[] = $t_menu_option;
		}

		return $t_menu_options;
	}

	function display_formatted( $p_event, $p_text, $p_multiline ) {
		$p_text = preg_replace_callback(
			self::CHANGESET_LINKING_REGEX,
			array( $this, 'Changeset_Link_Callback' ),
			$p_text
		);
		return $p_text;
	}

	function schema() {
		return array(
			array( 'CreateTableSQL', array( plugin_table( 'repository' ), "
				id			I		NOTNULL UNSIGNED AUTOINCREMENT PRIMARY,
				type		C(8)	NOTNULL DEFAULT \" '' \" PRIMARY,
				name		C(128)	NOTNULL DEFAULT \" '' \" PRIMARY,
				url			C(250)	DEFAULT \" '' \",
				info		XL		NOTNULL
				",
				array( 'mysql' => 'DEFAULT CHARSET=utf8' ) ) ),
			array( 'CreateTableSQL', array( plugin_table( 'changeset' ), "
				id			I		NOTNULL UNSIGNED AUTOINCREMENT PRIMARY,
				repo_id		I		NOTNULL UNSIGNED PRIMARY,
				revision	C(250)	NOTNULL PRIMARY,
				branch		C(250)	NOTNULL DEFAULT \" '' \",
				user_id		I		NOTNULL UNSIGNED DEFAULT '0',
				timestamp	T		NOTNULL,
				author		C(250)	NOTNULL DEFAULT \" '' \",
				message		XL		NOTNULL,
				info		XL		NOTNULL
				",
				array( 'mysql' => 'DEFAULT CHARSET=utf8' ) ) ),
			array( 'CreateIndexSQL', array( 'idx_changeset_stamp_author', plugin_table( 'changeset' ), 'timestamp, author' ) ),
			array( 'CreateTableSQL', array( plugin_table( 'file' ), "
				id			I		NOTNULL UNSIGNED AUTOINCREMENT PRIMARY,
				change_id	I		NOTNULL UNSIGNED,
				revision	C(250)	NOTNULL,
				filename	XL		NOTNULL
				",
				array( 'mysql' => 'DEFAULT CHARSET=utf8' ) ) ),
			array( 'CreateIndexSQL', array( 'idx_file_change_revision', plugin_table( 'file' ), 'change_id, revision' ) ),
			array( 'CreateTableSQL', array( plugin_table( 'bug' ), "
				change_id	I		NOTNULL UNSIGNED PRIMARY,
				bug_id		I		NOTNULL UNSIGNED PRIMARY
				",
				array( 'mysql' => 'DEFAULT CHARSET=utf8' ) ) ),
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
			# 2009-04-03 - Add committer information properties to changesets
			array( 'AddColumnSQL', array( plugin_table( 'changeset' ), "
				committer		C(250)	NOTNULL DEFAULT \" '' \",
				committer_email	C(250)	NOTNULL DEFAULT \" '' \",
				committer_id	I		NOTNULL UNSIGNED DEFAULT '0'
				" ) ),
			# 2009-03-03 - Add mappings from repository branches to project versions
			array( 'CreateTableSQL', array( plugin_table( 'branch' ), "
				repo_id		I		NOTNULL UNSIGNED PRIMARY,
				branch		C(128)	NOTNULL PRIMARY,
				type		I		NOTNULL UNSIGNED DEFAULT '0',
				version		C(64)	NOTNULL DEFAULT \" '' \",
				regex		C(128)	NOTNULL DEFAULT \" '' \"
				",
				array( 'mysql' => 'DEFAULT CHARSET=utf8' ) ) ),
			# 2009-04-15 - Allow a user/admin to specify a user's VCS username
			array( 'CreateTableSQL', array( plugin_table( 'user' ), "
				user_id		I		NOTNULL UNSIGNED PRIMARY,
				username	C(64)	NOTNULL DEFAULT \" '' \"
				",
				array( 'mysql' => 'DEFAULT CHARSET=utf8' ) ) ),
			array( 'CreateIndexSQL', array( 'idx_source_user_username', plugin_table( 'user' ), 'username', array( 'UNIQUE' ) ) ),
			# 2010-02-11 - Update repo types from svn->websvn
			array( 'UpdateSQL', array( plugin_table( 'repository' ), " SET type='websvn' WHERE type='svn'" ) ),
			# 2010-07-29 - Integrate with the Product Matrix plugin
			array( 'AddColumnSQL', array( plugin_table( 'branch' ), "
				pvm_version_id	I		NOTNULL UNSIGNED DEFAULT '0'
				" ) ),
		);
	}

	/**
	 * preg_replace callback to generate VCS links to changesets and pull requests.
	 * @param string $p_matches
	 * @return string
	 */
	protected function Changeset_Link_Callback( $p_matches ) {
		$t_url_type = strtolower($p_matches[1]);
		$t_repo_name = $p_matches[2];
		$t_revision = $p_matches[3];

		// Pull request links
		if( $t_url_type == 'p' ) {
			$t_repo = SourceRepo::load_by_name( $t_repo_name );
			if( $t_repo !== null ) {
				$t_vcs = SourceVCS::repo( $t_repo );
				if( $t_vcs->linkPullRequest ) {
					$t_url = $t_vcs->url_repo( $t_repo )
						. sprintf( $t_vcs->linkPullRequest, $t_revision );
					$t_name = string_display_line(
						$t_repo->name . ' ' .
						plugin_lang_get ( 'pullrequest' ) . ' ' .
						$t_revision
					);
					return '<a href="' . $t_url . '">' . $t_name . '</a>';
				}
			}
			return $p_matches[0];
		}

		// Changeset links
		$t_repo_table = plugin_table( 'repository', 'Source' );
		$t_changeset_table = plugin_table( 'changeset', 'Source' );

		$t_query = "SELECT c.* FROM $t_changeset_table AS c
			JOIN $t_repo_table AS r ON r.id=c.repo_id
			WHERE c.revision LIKE " . db_param() . '
			AND r.name LIKE ' . db_param();
		$t_result = db_query( $t_query, array( $t_revision . '%', $t_repo_name . '%' ), 1 );

		if( db_num_rows( $t_result ) > 0 ) {
			$t_row = db_fetch_array( $t_result );

			$t_changeset = new SourceChangeset(
				$t_row['repo_id'], $t_row['revision'], $t_row['branch'],
				$t_row['timestamp'], $t_row['author'], $t_row['message'],
				$t_row['user_id']
			);
			$t_changeset->id = $t_row['id'];

			$t_repo = SourceRepo::load( $t_changeset->repo_id );
			$t_vcs = SourceVCS::repo( $t_repo );

			switch( $t_url_type ) {
				case 'v':
				case 'd':
					$t_url = $t_vcs->url_changeset( $t_repo, $t_changeset );
					break;
				case 'c':
				case 's':
				default:
					$t_url = plugin_page( 'view' ) . '&id=' . $t_changeset->id;
			}

			$t_name = string_display_line( $t_repo->name . ' ' . $t_vcs->show_changeset( $t_repo, $t_changeset ) );

			return '<a href="' . $t_url . '">' . $t_name . '</a>';
		}

		return $p_matches[0];
	}

}

