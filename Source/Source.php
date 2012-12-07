<?php

# Copyright (c) 2012 John Reese
# Licensed under the MIT license

require_once( config_get_global( 'class_path' ) . 'MantisPlugin.class.php' );

/**
 * Creates an extensible API for integrating source control applications
 * with the Mantis bug tracker software.
 */
class SourcePlugin extends MantisPlugin {
	static $framework_version = '0.17';
	static $cache = array();

	function register() {
		$this->name = plugin_lang_get( 'title' );
		$this->description = plugin_lang_get( 'description' );

		$this->version = self::$framework_version;
		$this->requires = array(
			'MantisCore' => '1.2.0',
		);
		$this->uses = array(
			'jQuery' => '1.3',
		);
		$this->page		= 'manage_config_page';

		$this->author	= 'John Reese';
		$this->contact	= 'john@noswap.com';
		$this->url		= 'http://noswap.com';
	}

	function config() {
		return array(
			'show_repo_link'	=> ON,
			'show_search_link'	=> OFF,
			'show_repo_stats'	=> ON,

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

			'buglink_regex_1'	=> '/(?:bugs?|issues?|reports?)+\s+(?:#(?:\d+)[,\.\s]*)+/i',
			'buglink_regex_2'	=> '/#?(\d+)/',

			'bugfix_regex_1'	=> '/(?:fixe?d?s?|resolved?s?)+\s+(?:#(?:\d+)[,\.\s]*)+/i',
			'bugfix_regex_2'	=> '/#?(\d+)/',
			'bugfix_status'		=> -1,
			'bugfix_resolution'	=> FIXED,
			'bugfix_status_pvm'	=> 0,
			'bugfix_handler'	=> ON,
			'bugfix_message'	=> 'Fix committed to $1 branch.',

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
			'EVENT_LAYOUT_RESOURCES' => 'css',
			'EVENT_MENU_MAIN' => 'menu_main',
			'EVENT_FILTER_COLUMNS' => 'filter_columns',
		);
	}

	function init() {
		require_once( 'Source.API.php' );

		require_once( 'SourceIntegration.php' );
		plugin_child( 'SourceIntegration' );
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

	function display_formatted( $p_event, $p_text, $p_multiline ) {
		$p_text = preg_replace_callback( '/(?:([sv]):([^:\n\t]+):([^:\n\t\s]+):)/i', 'Source_Changeset_Link_Callback', $p_text );
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

}

