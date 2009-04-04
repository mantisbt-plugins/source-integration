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

require_once( 'MantisSourcePlugin.class.php' );

/**
 * General source control integration API.
 * @author John Reese
 */

# branch mapping strategies
define( 'SOURCE_EXPLICIT',		0 );
define( 'SOURCE_NEAR',			1 );
define( 'SOURCE_FAR',			2 );
define( 'SOURCE_FIRST',			3 );
define( 'SOURCE_LAST',			4 );
define( 'SOURCE_NEAR_REGEX',	5 );
define( 'SOURCE_FAR_REGEX',		6 );
define( 'SOURCE_FIRST_REGEX',	7 );
define( 'SOURCE_LAST_REGEX',	8 );

global $g_Source_cache_types;
$g_Source_cache_types = null;

function SourceType( $p_type ) {
	$t_types = SourceTypes();

	if ( isset( $t_types[$p_type] ) ) {
		return $t_types[$p_type];
	}

	return $p_type;
}

function SourceTypes() {
	global $g_Source_cache_types;

	if ( is_null( $g_Source_cache_types ) ) {
		$t_types = array();

		$t_raw_data = event_signal( 'EVENT_SOURCE_GET_TYPES' );
		foreach ( $t_raw_data as $t_plugin => $t_callbacks ) {
			foreach ( $t_callbacks as $t_callback => $t_data ) {
				foreach ( $t_data as $t_type => $t_name ) {
					$t_types[$t_type] = $t_name;
				}
			}
		}

		asort( $t_types );
		$g_Source_cache_types = $t_types;
	}

	return $g_Source_cache_types;
}

/**
 * Parse basic bug links from a changeset commit message
 * and return a list of referenced bug IDs.
 * @param string Changeset commit message
 * @return array Bug IDs
 */
function Source_Parse_Buglinks( $p_string ) {
	static $s_regex1, $s_regex2;

	$t_bugs = array();

	if ( is_null( $s_regex1 ) ) {
		$s_regex1 = config_get( 'plugin_Source_buglink_regex_1' );
		$s_regex2 = config_get( 'plugin_Source_buglink_regex_2' );
	}

	preg_match_all( $s_regex1, $p_string, $t_matches_all );

	foreach( $t_matches_all[0] as $t_substring ) {
		preg_match_all( $s_regex2, $t_substring, $t_matches );
		foreach ( $t_matches[1] as $t_match ) {
			if ( 0 < (int)$t_match ) {
				$t_bugs[$t_match] = true;
			}
		}
	}

	return array_keys( $t_bugs );
}

/**
 * Parse resolved bug fix links from a changeset commit message
 * and return a list of referenced bug IDs.
 * @param string Changeset commit message
 * @return array Bug IDs
 */
function Source_Parse_Bugfixes( $p_string ) {
	static $s_regex1, $s_regex2;

	$t_bugs = array();

	if ( is_null( $s_regex1 ) ) {
		$s_regex1 = config_get( 'plugin_Source_bugfix_regex_1' );
		$s_regex2 = config_get( 'plugin_Source_bugfix_regex_2' );
	}

	preg_match_all( $s_regex1, $p_string, $t_matches_all );

	foreach( $t_matches_all[0] as $t_substring ) {
		preg_match_all( $s_regex2, $t_substring, $t_matches );
		foreach ( $t_matches[1] as $t_match ) {
			if ( 0 < (int)$t_match ) {
				$t_bugs[$t_match] = true;
			}
		}
	}

	return array_keys( $t_bugs );
}

/**
 * Determine the user ID for both the author and committer.
 * First checks the email address for a matching user, then
 * checks the name for a matching username or realname.
 * @param object Changeset object
 */
function Source_Parse_Users( &$p_changeset ) {
	static $s_names = array();
	static $s_emails = array();

	# Handle the changeset author
	while ( !$p_changeset->user_id ) {

		# Look up the email address if given
		if ( $t_email = $p_changeset->author_email ) {
			if ( isset( $s_emails[ $t_email ] ) ) {
				$p_changeset->user_id = $s_emails[ $t_email ];
				break;

			} else if ( false !== ( $t_email_id = user_get_id_by_email( $t_email ) ) ) {
				$s_emails[ $t_email ] = $p_changeset->user_id = $t_email_id;
				break;
			}
		}

		# Look up the name if the email failed
		if ( $t_name = $p_changeset->author ) {
			if ( isset( $s_names[ $t_name ] ) ) {
				$p_changeset->user_id = $s_names[ $t_name ];
				break;

			} else if ( false !== ( $t_user_id = user_get_id_by_realname( $t_name ) ) ) {
				$s_names[ $t_name ] = $p_changeset->user_id = $t_user_id;
				break;

			} else if ( false !== ( $t_user_id = user_get_id_by_name( $p_changeset->author ) ) ) {
				$s_names[ $t_name ] = $p_changeset->user_id = $t_user_id;
				break;
			}
		}

		# Don't actually loop
		break;
	}

	# Handle the changeset committer
	while ( !$p_changeset->committer_id ) {

		# Look up the email address if given
		if ( $t_email = $t_email ) {
			if ( isset( $s_emails[ $t_email ] ) ) {
				$p_changeset->committer_id = $s_emails[ $t_email ];
				break;

			} else if ( false !== ( $t_email_id = user_get_id_by_email( $t_email ) ) ) {
				$s_emails[ $t_email ] = $p_changeset->committer_id = $t_email_id;
				break;
			}
		}

		# Look up the name if the email failed
		if ( $t_name = $p_changeset->committer ) {
			if ( isset( $s_names[ $t_name ] ) ) {
				$p_changeset->committer_id = $s_names[ $t_name ];
				break;

			} else if ( false !== ( $t_user_id = user_get_id_by_realname( $t_name ) ) ) {
				$s_names[ $t_name ] = $p_changeset->committer_id = $t_user_id;
				break;

			} else if ( false !== ( $t_user_id = user_get_id_by_name( $t_name ) ) ) {
				$s_names[ $t_name ] = $p_changeset->committer_id = $t_user_id;
				break;
			}
		}

		# Don't actually loop
		break;
	}
}
/**
 * Given a set of changeset objects, parse the bug links
 * and save the changes.
 * @param array Changeset objects
 */
function Source_Process_Changesets( $p_changesets ) {
	global $g_cache_current_user_id;

	if ( !is_array( $p_changesets ) ) {
		return;
	}

	# Link author and committer name/email to user accounts
	foreach( $p_changesets as $t_changeset ) {
		Source_Parse_Users( $t_changeset );
	}

	# Parse normal bug links
	foreach( $p_changesets as $t_changeset ) {
		$t_changeset->bugs = Source_Parse_Buglinks( $t_changeset->message );
	}

	# Parse fixed bug links
	$t_fixed_bugs = array();

	# Find and associate resolve links with the changeset
	foreach( $p_changesets as $t_changeset ) {
		$t_bugs = Source_Parse_Bugfixes( $t_changeset->message );

		foreach( $t_bugs as $t_bug_id ) {
			$t_fixed_bugs[ $t_bug_id ] = $t_changeset;
		}

		# Add the link to the normal set of buglinks
		$t_changeset->bugs = array_merge( $t_changeset->bugs, $t_bugs );
	}

	# Resolve any fixed bugs
	if ( config_get( 'plugin_Source_bugfix_resolving' ) ) {
		# Precache information for resolved bugs
		bug_cache_array_rows( $t_fixed_bugs );

		# Start resolving issues
		$t_current_user_id = $g_cache_current_user_id;
		$t_resolution = plugin_config_get( 'bugfix_resolution' );
		foreach( $t_fixed_bugs as $t_bug_id => $t_changeset ) {
			$t_user_id = null;
			if ( $t_changeset->committer_id > 0 ) {
				$t_user_id = $t_changeset->committer_id;
			} else if ( $t_changeset->user_id > 0 ) {
				$t_user_id = $t_changeset->user_id;
			}

			if ( !is_null( $t_user_id ) ) {
				$g_cache_current_user_id = $t_user_id;
			} else if ( !is_null( $t_current_user_id ) ) {
				$g_cache_current_user_id = $t_current_user_id;
			} else {
				$g_cache_current_user_id = 0;
			}

			bug_resolve( $t_bug_id, $t_resolution, '', '', null, $t_user_id );
		}

		$g_cache_current_user_id = $t_current_user_id;
	}

	# Save changes
	foreach( $p_changesets as $t_changeset ) {
		$t_changeset->save();
	}
}

/**
 * preg_replace_callback function for working with VCS links.
 */
function Source_Changeset_Link_Callback( $p_matches ) {
	$t_repo_name = $p_matches[2];
	$t_revision = $p_matches[3];
	$t_string = $p_matches[2] . ':' . $p_matches[3];

	$t_repo_table = plugin_table( 'repository', 'Source' );
	$t_changeset_table = plugin_table( 'changeset', 'Source' );
	$t_file_table = plugin_table( 'file', 'Source' );

	$t_query = "SELECT c.* FROM $t_changeset_table AS c
				JOIN $t_repo_table AS r ON r.id=c.repo_id
				WHERE c.revision LIKE " . db_param() . '
				AND r.name LIKE ' . db_param();
	$t_result = db_query_bound( $t_query, array( '%' . $t_revision . '%', '%' . $t_repo_name . '%' ), 1 );

	if ( db_num_rows( $t_result ) > 0 ) {
		$t_row = db_fetch_array( $t_result );

		$t_changeset = new SourceChangeset( $t_row['repo_id'], $t_row['revision'], $t_row['timestamp'], $t_row['author'], $t_row['message'], $t_row['user_id'] );
		$t_changeset->id = $t_row['id'];

		$t_repo = SourceRepo::load( $t_changeset->repo_id );

		$t_url = event_signal( 'EVENT_SOURCE_URL_CHANGESET', array( $t_repo, $t_changeset ) );
		$t_name = event_signal( 'EVENT_SOURCE_SHOW_CHANGESET', array( $t_repo, $t_changeset ) );

		if ( !is_blank( $t_url ) ) {
			$t_string = '<a href="' . $t_url . '">' . $t_name . '</a>';
		}
	}

	return $p_matches[1] . $t_string;
}

/**
 * Abstract source control repository data.
 */
class SourceRepo {
	var $id;
	var $type;
	var $name;
	var $url;
	var $info;
	var $branches;

	/**
	 * Build a new Repo object given certain properties.
	 * @param string Repo type
	 * @param string Name
	 * @param string URL
	 * @param string Path
	 * @param array Info
	 */
	function __construct( $p_type, $p_name, $p_url='', $p_info='' ) {
		$this->id	= 0;
		$this->type	= $p_type;
		$this->name	= $p_name;
		$this->url	= $p_url;
		if ( is_blank( $p_info ) ) {
			$this->info = array();
		} else {
			$this->info = unserialize( $p_info );
		}
		$this->branches = array();
	}

	/**
	 * Create or update repository data.
	 * Creates database row if $this->id is zero, updates an existing row otherwise.
	 */
	function save() {
		if ( is_blank( $this->type ) || is_blank( $this->name ) ) {
			trigger_error( ERROR_GENERIC, ERROR );
		}

		$t_repo_table = plugin_table( 'repository', 'Source' );

		if ( 0 == $this->id ) { # create
			$t_query = "INSERT INTO $t_repo_table ( type, name, url, info ) VALUES ( " .
				db_param() . ', ' . db_param() . ', ' . db_param() . ', ' . db_param() . ' )';
			db_query_bound( $t_query, array( $this->type, $this->name, $this->url, serialize($this->info) ) );

			$this->id = db_insert_id( $t_repo_table );
		} else { # update
			$t_query = "UPDATE $t_repo_table SET type=" . db_param() . ', name=' . db_param() .
				', url=' . db_param() . ', info=' . db_param() . ' WHERE id=' . db_param();
			db_query_bound( $t_query, array( $this->type, $this->name, $this->url, serialize($this->info), $this->id ) );
		}
	}

	/**
	 * Load and cache the list of unique branches for the repo's changesets.
	 */
	function load_branches() {
		if ( count( $this->branches ) < 1 ) {
			$t_changeset_table = plugin_table( 'changeset', 'Source' );

			$t_query = "SELECT DISTINCT branch FROM $t_changeset_table WHERE repo_id=" .
				db_param() . ' ORDER BY branch ASC';
			$t_result = db_query_bound( $t_query, array( $this->id ) );

			while( $t_row = db_fetch_array( $t_result ) ) {
				$this->branches[] = $t_row['branch'];
			}
		}

		return $this->branches;
	}

	/**
	 * Get a list of repository statistics.
	 * @return array Stats
	 */
	function stats() {
		$t_stats = array();
		
		$t_changeset_table = plugin_table( 'changeset', 'Source' );
		$t_file_table = plugin_table( 'file', 'Source' );
		$t_bug_table = plugin_table( 'bug', 'Source' );

		$t_query = "SELECT COUNT(*) FROM $t_changeset_table WHERE repo_id=" . db_param();
		$t_stats['changesets'] = db_result( db_query_bound( $t_query, array( $this->id ) ) );

		$t_query = "SELECT COUNT(DISTINCT filename) FROM $t_file_table AS f
		   			JOIN $t_changeset_table AS c
					ON c.id=f.change_id
					WHERE c.repo_id=" . db_param();
		$t_stats['files'] = db_result( db_query_bound( $t_query, array( $this->id ) ) );

		$t_query = "SELECT COUNT(DISTINCT bug_id) FROM $t_bug_table AS b
		   			JOIN $t_changeset_table AS c
					ON c.id=b.change_id
					WHERE c.repo_id=" . db_param();
		$t_stats['bugs'] = db_result( db_query_bound( $t_query, array( $this->id ) ) );

		return $t_stats;
	}

	/**
	 * Fetch a new Repo object given an ID.
	 * @param int Repository ID
	 * @return multi Repo object
	 */
	static function load( $p_id ) {
		$t_repo_table = plugin_table( 'repository', 'Source' );

		$t_query = "SELECT * FROM $t_repo_table WHERE id=" . db_param();
		$t_result = db_query_bound( $t_query, array( (int) $p_id ) );

		if ( db_num_rows( $t_result ) < 1 ) {
			trigger_error( ERROR_GENERIC, ERROR );
		}

		$t_row = db_fetch_array( $t_result );

		$t_repo = new SourceRepo( $t_row['type'], $t_row['name'], $t_row['url'], $t_row['info'] );
		$t_repo->id = $t_row['id'];

		return $t_repo;
	}

	/**
	 * Fetch an array of all Repo objects.
	 * @return array All repo objects.
	 */
	static function load_all() {
		$t_repo_table = plugin_table( 'repository', 'Source' );

		$t_query = "SELECT * FROM $t_repo_table ORDER BY name ASC";
		$t_result = db_query( $t_query );

		$t_repos = array();

		while ( $t_row = db_fetch_array( $t_result ) ) {
			$t_repo = new SourceRepo( $t_row['type'], $t_row['name'], $t_row['url'], $t_row['info'] );
			$t_repo->id = $t_row['id'];

			$t_repos[] = $t_repo;
		}

		return $t_repos;
	}

	/**
	 * Fetch a repository object with the given name.
	 * @return multi Repo object, or null if not found
	 */
	static function load_by_name( $p_repo_name ) {
		$t_repo_table = plugin_table( 'repository', 'Source' );

		$t_query = "SELECT * FROM $t_repo_table WHERE name LIKE " . db_param();
		$t_result = db_query_bound( $t_query, array( '%' . $p_repo_name . '%' ) );

		if ( db_num_rows( $t_result ) < 1 ) {
			return null;
		}

		$t_row = db_fetch_array( $t_result );

		$t_repo = new SourceRepo( $t_row['type'], $t_row['name'], $t_row['url'], $t_row['info'] );
		$t_repo->id = $t_row['id'];

		return $t_repo;
	}

	/**
	 * Fetch an array of repository objects that includes all given changesets.
	 * @param array Changeset objects
	 * @return array Repository objects
	 */
	static function load_by_changesets( $p_changesets ) { 
		if ( !is_array( $p_changesets ) ) {
			$p_changesets = array( $p_changesets );
		}
		elseif ( count( $p_changesets ) < 1 ) {
			return array();
		}

		$t_repo_table = plugin_table( 'repository', 'Source' );

		$t_repos = array();
		
		foreach ( $p_changesets as $t_changeset ) {
			if ( !isset( $t_repos[$t_changeset->repo_id] ) ) {
				$t_repos[$t_changeset->repo_id] = true;
			}
		}

		$t_query = "SELECT * FROM $t_repo_table WHERE id IN ( ";
		$t_first = true;

		foreach ( $t_repos as $t_repo_id => $t_repo ) {
			$t_query .= ( $t_first ? (int)$t_repo_id : ', ' . (int)$t_repo_id );
			$t_first = false;
		}

		$t_query .= ' ) ORDER BY name ASC';
		$t_result = db_query( $t_query );

		while ( $t_row = db_fetch_array( $t_result ) ) {
			$t_repo = new SourceRepo( $t_row['type'], $t_row['name'], $t_row['url'], $t_row['info'] );
			$t_repo->id = $t_row['id'];

			$t_repos[$t_repo->id] = $t_repo;
		}

		return $t_repos;
	}

	/**
	 * Delete a repository with the given ID.
	 * @param int Repository ID
	 */
	static function delete( $p_id ) {
		SourceChangeset::delete_by_repo( $p_id );

		$t_repo_table = plugin_table( 'repository', 'Source' );

		$t_query = "DELETE FROM $t_repo_table WHERE id=" . db_param();
		$t_result = db_query_bound( $t_query, array( (int) $p_id ) );
	}

	/**
	 * Check to see if a repository exists with the given ID.
	 * @param int Repository ID
	 * @return boolean True if repository exists
	 */
	static function exists( $p_id ) {
		$t_repo_table = plugin_table( 'repository', 'Source' );

		$t_query = "SELECT COUNT(*) FROM $t_repo_table WHERE id=" . db_param();
		$t_result = db_query_bound( $t_query, array( (int) $p_id ) );

		return db_result( $t_result ) > 0;
	}

	static function ensure_exists( $p_id ) {
		if ( !SourceRepo::exists( $p_id ) ) {
			trigger_error( ERROR_GENERIC, ERROR );
		}
	}
}

/**
 * Abstract source control changeset data.
 */
class SourceChangeset {
	var $id;
	var $repo_id;
	var $user_id;
	var $revision;
	var $parent;
	var $branch;
	var $ported;
	var $timestamp;
	var $author;
	var $author_email;
	var $committer;
	var $committer_email;
	var $committer_id;
	var $message;

	var $files; # array of SourceFile's
	var $bugs;
	var $__bugs;
	var $repo;

	/**
	 * Build a new changeset object given certain properties.
	 * @param int Repository ID
	 * @param string Changeset revision
	 * @param string Timestamp
	 * @param string Author
	 * @param string Commit message
	 */
	function __construct( $p_repo_id, $p_revision, $p_branch='', $p_timestamp='',
		$p_author='', $p_message='', $p_user_id=0, $p_parent='', $p_ported='', $p_author_email='' ) {

		$this->id				= 0;
		$this->user_id			= $p_user_id;
		$this->repo_id			= $p_repo_id;
		$this->revision			= $p_revision;
		$this->parent			= $p_parent;
		$this->branch			= $p_branch;
		$this->ported			= $p_ported;
		$this->timestamp		= $p_timestamp;
		$this->author			= $p_author;
		$this->author_email		= $p_author_email;
		$this->message			= $p_message;
		$this->committer		= '';
		$this->committer_email	= '';
		$this->committer_id		= 0;

		$this->files			= array();
		$this->bugs				= array();
		$this->__bugs			= array();
	}

	/**
	 * Create or update changeset data.
	 * Creates database row if $this->id is zero, updates an existing row otherwise.
	 */
	function save() {
		if ( 0 == $this->repo_id ) {
			trigger_error( ERROR_GENERIC, ERROR );
		}

		$t_changeset_table = plugin_table( 'changeset', 'Source' );

		if ( 0 == $this->id ) { # create
			$t_query = "INSERT INTO $t_changeset_table ( repo_id, revision, parent, branch, user_id,
				timestamp, author, message, ported, author_email, committer, committer_email, committer_id
				) VALUES ( " .
				db_param() . ', ' . db_param() . ', ' . db_param() . ', ' . db_param() . ', ' .
				db_param() . ', ' . db_param() . ', ' . db_param() . ', ' . db_param() . ', ' .
				db_param() . ', ' . db_param() . ', ' . db_param() . ', ' . db_param() . ', ' .
				db_param() . ' )';
			db_query_bound( $t_query, array(
				$this->repo_id, $this->revision, $this->parent, $this->branch,
				$this->user_id, $this->timestamp, $this->author, $this->message,
				$this->ported, $this->author_email, $this->committer, $this->committer_email,
				$this->committer_id ) );

			$this->id = db_insert_id( $t_changeset_table );

			foreach( $this->files as $t_file ) {
				$t_file->change_id = $this->id;
			}

		} else { # update
			$t_query = "UPDATE $t_changeset_table SET repo_id=" . db_param() . ', revision=' . db_param() .
				', parent=' . db_param() . ', branch=' . db_param() . ', user_id=' . db_param() .
				', timestamp=' . db_param() . ', author=' . db_param() . ', message=' . db_param() .
				', ported=' . db_param() . ', author_email=' . db_param() . ', committer=' . db_param() .
				', committer_email=' . db_param() . ', committer_id=' . db_param() .
				' WHERE id=' . db_param();
			db_query_bound( $t_query, array(
				$this->repo_id, $this->revision,
				$this->parent, $this->branch, $this->user_id,
				$this->timestamp, $this->author, $this->message,
				$this->ported, $this->author_email, $this->committer,
				$this->committer_email, $this->committer_id,
				$this->id ) );
		}

		foreach( $this->files as $t_file ) {
			$t_file->save();
		}

		$this->save_bugs();
	}

	/**
	 * Update changeset relations to affected bugs.
	 */
	function save_bugs( $p_user_id=null ) {
		$t_bug_table = plugin_table( 'bug', 'Source' );

		$t_bugs_added = array_diff( $this->bugs, $this->__bugs );
		$t_bugs_deleted = array_diff( $this->__bugs, $this->bugs );

		$this->load_repo();

		$t_user_id = (int)$p_user_id;
		if ( $t_user_id < 1 ) {
			if ( $this->committer_id > 0 ) {
				$t_user_id = $this->committer_id;
			} else if ( $this->user_id > 0 ) {
				$t_user_id = $this->user_id;
			}
		}

		if ( count( $t_bugs_deleted ) ) {
			$t_bugs_deleted_str = join( ',', $t_bugs_deleted );

			$t_query = "DELETE FROM $t_bug_table WHERE change_id=" . $this->id .
				" AND bug_id IN ( $t_bugs_deleted_str )";
			db_query_bound( $t_query );

			foreach( $t_bugs_deleted as $t_bug_id ) {
				plugin_history_log( $t_bug_id, 'changeset_removed',
					event_signal( 'EVENT_SOURCE_SHOW_CHANGESET', array( $this->repo, $this ) ),
					'', $t_user_id, 'Source' );
			}
		}

		if ( count( $t_bugs_added ) > 0 ) {
			$t_query = "INSERT INTO $t_bug_table ( change_id, bug_id ) VALUES ";

			$t_count = 0;
			$t_params = array();

			foreach( $t_bugs_added as $t_bug_id ) {
				$t_query .= ( $t_count == 0 ? '' : ', ' ) .
					'(' . db_param() . ', ' . db_param() . ')';
				$t_params[] = $this->id;
				$t_params[] = $t_bug_id;
				$t_count++;
			}

			db_query_bound( $t_query, $t_params );

			foreach( $t_bugs_added as $t_bug_id ) {
				plugin_history_log( $t_bug_id, 'changeset_attached',
					event_signal( 'EVENT_SOURCE_SHOW_CHANGESET', array( $this->repo, $this ) ),
					'', $t_user_id, 'Source' );
			}
		}
	}

	/**
	 * Load/cache repo object.
	 */
	function load_repo() {
		if ( is_null( $this->repo ) ) {
			$t_repos = SourceRepo::load_by_changesets( $this );
			$this->repo = array_shift( $t_repos );
		}
	}

	/**
	 * Load all file objects associated with this changeset.
	 */
	function load_files() {
		if ( count( $this->files ) < 1 ) {
			$this->files = SourceFile::load_by_changeset( $this->id );
		}

		return $this->files;
	}

	/**
	 * Load all bug numbers associated with this changeset.
	 */
	function load_bugs() {
		if ( count( $this->bugs ) < 1 ) {
			$t_bug_table = plugin_table( 'bug', 'Source' );

			$t_query = "SELECT bug_id FROM $t_bug_table WHERE change_id=" . db_param();
			$t_result = db_query_bound( $t_query, array( $this->id ) );

			$this->bugs = array();
			$this->__bugs = array();
			while( $t_row = db_fetch_array( $t_result ) ) {
				$this->bugs[] = $t_row['bug_id'];
				$this->__bugs[] = $t_row['bug_id'];
			}
		}

		return $this->bugs;
	}

	/**
	 * Check if a repository's changeset already exists in the database.
	 * @param int Repo ID
	 * @param string Revision
	 * @param string Branch
	 * @return boolean True if changeset exists
	 */
	static function exists( $p_repo_id, $p_revision, $p_branch=null ) {
		$t_changeset_table = plugin_table( 'changeset', 'Source' );

		$t_query = "SELECT * FROM $t_changeset_table WHERE repo_id=" . db_param() . '
				AND revision=' . db_param();
		$t_params = array( $p_repo_id, $p_revision );

		if ( !is_null( $p_branch ) ) {
			$t_query .= ' AND branch=' . db_param();
			$t_params[] = $p_branch;
		}

		$t_result = db_query_bound( $t_query, $t_params );
		return db_num_rows( $t_result ) > 0;
	}

	/**
	 * Fetch a new changeset object given an ID.
	 * @param int Changeset ID
	 * @return multi Changeset object
	 */
	static function load( $p_id ) {
		$t_changeset_table = plugin_table( 'changeset', 'Source' );

		$t_query = "SELECT * FROM $t_changeset_table WHERE id=" . db_param() . '
				ORDER BY timestamp DESC';
		$t_result = db_query_bound( $t_query, array( $p_id ) );

		if ( db_num_rows( $t_result ) < 1 ) {
			trigger_error( ERROR_GENERIC, ERROR );
		}

		$t_row = db_fetch_array( $t_result );
		$t_changeset = new SourceChangeset( $t_row['repo_id'], $t_row['revision'],
			$t_row['branch'], $t_row['timestamp'], $t_row['author'], $t_row['message'],
			$t_row['user_id'], $t_row['parent'], $t_row['ported'], $t_row['author_email'] );
		$t_changeset->id = $t_row['id'];

		return $t_changeset;
	}

	/**
	 * Fetch a changeset object given a repository and revision.
	 * @param multi Repo object
	 * @param string Revision
	 * @return multi Changeset object
	 */
	static function load_by_revision( $p_repo, $p_revision ) {
		$t_changeset_table = plugin_table( 'changeset', 'Source' );

		$t_query = "SELECT * FROM $t_changeset_table WHERE repo_id=" . db_param() . '
				AND revision=' . db_param() . ' ORDER BY timestamp DESC';
		$t_result = db_query_bound( $t_query, array( $p_repo->id, $p_revision ) );

		if ( db_num_rows( $t_result ) < 1 ) {
			trigger_error( ERROR_GENERIC, ERROR );
		}

		$t_row = db_fetch_array( $t_result );
		$t_changeset = new SourceChangeset( $t_row['repo_id'], $t_row['revision'],
			$t_row['branch'], $t_row['timestamp'], $t_row['author'], $t_row['message'],
			$t_row['user_id'], $t_row['parent'], $t_row['ported'], $t_row['author_email'] );
		$t_changeset->id = $t_row['id'];

		return $t_changeset;
	}

	/**
	 * Fetch an array of changeset objects for a given repository ID.
	 * @param int Repository ID
	 * @return array Changeset objects
	 */
	static function load_by_repo( $p_repo_id, $p_load_files=false, $p_page=null, $p_limit=25  ) {
		$t_changeset_table = plugin_table( 'changeset', 'Source' );

		$t_query = "SELECT * FROM $t_changeset_table WHERE repo_id=" . db_param() . '
				ORDER BY timestamp DESC';
		if ( is_null( $p_page ) ) {
			$t_result = db_query_bound( $t_query, array( $p_repo_id ) );
		} else {
			$t_result = db_query_bound( $t_query, array( $p_repo_id ), $p_limit, ($p_page - 1) * $p_limit );
		}

		$t_changesets = array();

		while ( $t_row = db_fetch_array( $t_result ) ) {
			$t_changeset = new SourceChangeset( $t_row['repo_id'], $t_row['revision'],
				$t_row['branch'], $t_row['timestamp'], $t_row['author'], $t_row['message'],
				$t_row['user_id'], $t_row['parent'], $t_row['ported'], $t_row['author_email'] );
			$t_changeset->id = $t_row['id'];

			if ( $p_load_files ) {
				$t_changeset->load_files();
			}

			$t_changesets[] = $t_changeset;
		}

		return $t_changesets;
	}

	/**
	 * Fetch an array of changeset objects for a given bug ID.
	 * @param int Bug ID
	 * @return array Changeset objects
	 */
	static function load_by_bug( $p_bug_id, $p_load_files=false ) {
		$t_changeset_table = plugin_table( 'changeset', 'Source' );
		$t_bug_table = plugin_table( 'bug', 'Source' );

		$t_query = "SELECT c.* FROM $t_changeset_table AS c
		   		JOIN $t_bug_table AS b ON c.id=b.change_id
				WHERE b.bug_id=" . db_param() . '
				ORDER BY c.timestamp DESC';
		$t_result = db_query_bound( $t_query, array( $p_bug_id ) );

		$t_changesets = array();

		while ( $t_row = db_fetch_array( $t_result ) ) {
			$t_changeset = new SourceChangeset( $t_row['repo_id'], $t_row['revision'],
				$t_row['branch'], $t_row['timestamp'], $t_row['author'], $t_row['message'],
				$t_row['user_id'], $t_row['parent'], $t_row['ported'], $t_row['author_email'] );
			$t_changeset->id = $t_row['id'];

			if ( $p_load_files ) {
				$t_changeset->load_files();
			}

			$t_changesets[] = $t_changeset;
		}

		return $t_changesets;
	}

	/**
	 * Delete all changesets for a given repository ID.
	 * @param int Repository ID
	 */
	static function delete_by_repo( $p_repo_id ) {
		$t_bug_table = plugin_table( 'bug', 'Source' );
		$t_changeset_table = plugin_table( 'changeset', 'Source' );

		$t_changesets = SourceChangeset::load_by_repo( $p_repo_id );

		if ( count( $t_changesets ) > 0 ) {
			$t_query = "DELETE FROM $t_bug_table WHERE change_id in ( ";
			$t_first = true;

			foreach ( $t_changesets as $t_changeset ) {
				SourceFile::delete_by_changeset( $t_changeset->id );

				$t_query .= ( $t_first ? (int)$t_changeset->id : ', ' . (int)$t_changeset->id );
				$t_first = false;
			}

			$t_query .= ' )';

			db_query( $t_query );
		}

		$t_query = "DELETE FROM $t_changeset_table WHERE repo_id=" . db_param();
		db_query_bound( $t_query, array( $p_repo_id ) );
	}

}

/**
 * Abstract source control file data.
 */
class SourceFile {
	var $id;
	var $change_id;
	var $revision;
	var $action;
	var $filename;

	function __construct( $p_change_id, $p_revision, $p_filename, $p_action='' ) {
		$this->id			= 0;
		$this->change_id	= $p_change_id;
		$this->revision		= $p_revision;
		$this->action		= $p_action;
		$this->filename		= $p_filename;
	}

	function save() {
		if ( 0 == $this->change_id ) {
			trigger_error( ERROR_GENERIC, ERROR );
		}

		$t_file_table = plugin_table( 'file', 'Source' );

		if ( 0 == $this->id ) { # create
			$t_query = "INSERT INTO $t_file_table ( change_id, revision, action, filename ) VALUES ( " .
				db_param() . ', ' . db_param() . ', ' . db_param() . ', ' . db_param() . ' )';
			db_query_bound( $t_query, array( $this->change_id, $this->revision, $this->action, $this->filename ) );

			$this->id = db_insert_id( $t_file_table );
		} else { # update
			$t_query = "UPDATE $t_file_table SET change_id=" . db_param() . ', revision=' . db_param() .
				', action=' . db_param() . ', filename=' . db_param() . ' WHERE id=' . db_param();
			db_query_bound( $t_query, array( $this->change_id, $this->revision, $this->action, $this->filename, $this->id ) );
		}
	}

	static function load( $p_id ) {
		$t_file_table = plugin_table( 'file', 'Source' );

		$t_query = "SELECT * FROM $t_file_table WHERE id=" . db_param();
		$t_result = db_query_bound( $t_query, array( $p_id ) );

		if ( db_num_rows( $t_result ) < 1 ) {
			trigger_error( ERROR_GENERIC, ERROR );
		}

		$t_row = db_fetch_array( $t_result );
		$t_file = new SourceFile( $t_row['change_id'], $t_row['revision'], $t_row['filename'], $t_row['action'] );
		$t_file->id = $t_row['id'];

		return $t_file;
	}

	static function load_by_changeset( $p_change_id ) {
		$t_file_table = plugin_table( 'file', 'Source' );

		$t_query = "SELECT * FROM $t_file_table WHERE change_id=" . db_param();
		$t_result = db_query_bound( $t_query, array( $p_change_id ) );

		$t_files = array();

		while ( $t_row = db_fetch_array( $t_result ) ) {
			$t_file = new SourceFile( $t_row['change_id'], $t_row['revision'], $t_row['filename'], $t_row['action'] );
			$t_file->id = $t_row['id'];
			$t_files[] = $t_file;
		}

		return $t_files;
	}

	static function delete_by_changeset( $p_change_id ) {
		$t_file_table = plugin_table( 'file', 'Source' );

		$t_query = "DELETE FROM $t_file_table WHERE change_id=" . db_param();
		db_query_bound( $t_query, array( $p_change_id ) );
	}
}

