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

		$g_Source_cache_types = $t_types;
	}

	return $g_Source_cache_types;
}

function Source_Parse_Buglinks( $p_string ) {
	$t_bugs = array();

	$t_regex1 = config_get( 'plugin_Source_buglink_regex_1' );
	$t_regex2 = config_get( 'plugin_Source_buglink_regex_2' );

	preg_match_all( $t_regex1, $p_string, $t_matches_all );

	foreach( $t_matches_all[0] as $t_substring ) {
		preg_match_all( $t_regex2, $t_substring, $t_matches );
		foreach ( $t_matches[1] as $t_match ) {
			if ( 0 < (int)$t_match ) {
				$t_bugs[$t_match] = true;
			}
		}
	}

	return array_keys( $t_bugs );
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
				WHERE c.revision LIKE " . db_param(0) . '
				AND r.name LIKE ' . db_param(1);
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
	var $path;
	var $info;

	/**
	 * Build a new Repo object given certain properties.
	 * @param string Repo type
	 * @param string Name
	 * @param string URL
	 * @param string Path
	 * @param array Info
	 */
	function __construct( $p_type, $p_name, $p_url='', $p_path='', $p_info='' ) {
		$this->id	= 0;
		$this->type	= $p_type;
		$this->name	= $p_name;
		$this->url	= $p_url;
		$this->path	= $p_path;
		if ( is_blank( $p_info ) ) {
			$this->info = array();
		} else {
			$this->info = unserialize( $p_info );
		}
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
			$t_query = "INSERT INTO $t_repo_table ( type, name, url, path, info ) VALUES ( " .
				db_param(0) . ', ' . db_param(1) . ', ' . db_param(2) . ', ' . db_param(3) . ', ' . db_param(4) . ' )';
			db_query_bound( $t_query, array( $this->type, $this->name, $this->url, $this->path, serialize($this->info) ) );

			$this->id = db_insert_id( $t_repo_table );
		} else { # update
			$t_query = "UPDATE $t_repo_table SET type=" . db_param(0) . ', name=' . db_param(1) .
				', url=' . db_param(2) . ', path=' . db_param(3) . ', info=' . db_param(4) . ' WHERE id=' . db_param(5);
			db_query_bound( $t_query, array( $this->type, $this->name, $this->url, $this->path, serialize($this->info), $this->id ) );
		}
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

		$t_query = "SELECT COUNT(*) FROM $t_changeset_table WHERE repo_id=" . db_param(0);
		$t_stats['changesets'] = db_result( db_query_bound( $t_query, array( $this->id ) ) );

		$t_query = "SELECT COUNT(DISTINCT filename) FROM $t_file_table AS f
		   			JOIN $t_changeset_table AS c
					ON c.id=f.change_id
					WHERE c.repo_id=" . db_param(0);
		$t_stats['files'] = db_result( db_query_bound( $t_query, array( $this->id ) ) );

		$t_query = "SELECT COUNT(DISTINCT bug_id) FROM $t_bug_table AS b
		   			JOIN $t_changeset_table AS c
					ON c.id=b.change_id
					WHERE c.repo_id=" . db_param(0);
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

		$t_query = "SELECT * FROM $t_repo_table WHERE id=" . db_param(0);
		$t_result = db_query_bound( $t_query, array( (int) $p_id ) );

		if ( db_num_rows( $t_result ) < 1 ) {
			trigger_error( ERROR_GENERIC, ERROR );
		}

		$t_row = db_fetch_array( $t_result );

		$t_repo = new SourceRepo( $t_row['type'], $t_row['name'], $t_row['url'], $t_row['path'], $t_row['info'] );
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
			$t_repo = new SourceRepo( $t_row['type'], $t_row['name'], $t_row['url'], $t_row['path'], $t_row['info'] );
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

		$t_query = "SELECT * FROM $t_repo_table WHERE name LIKE " . db_param(0);
		$t_result = db_query_bound( $t_query, array( '%' . $p_repo_name . '%' ) );

		if ( db_num_rows( $t_result ) < 1 ) {
			return null;
		}

		$t_row = db_fetch_array( $t_result );

		$t_repo = new SourceRepo( $t_row['type'], $t_row['name'], $t_row['url'], $t_row['path'], $t_row['info'] );
		$t_repo->id = $t_row['id'];

		return $t_repo;
	}

	/**
	 * Fetch an array of repository objects that includes all given changesets.
	 * @param array Changeset objects
	 * @return array Repository objects
	 */
	static function load_by_changesets( $p_changesets ) { 
		if ( count( $p_changesets ) < 1 ) {
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
			$t_repo = new SourceRepo( $t_row['type'], $t_row['name'], $t_row['url'], $t_row['path'], $t_row['info'] );
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

		$t_query = "DELETE FROM $t_repo_table WHERE id=" . db_param(0);
		$t_result = db_query_bound( $t_query, array( (int) $p_id ) );
	}

	/**
	 * Check to see if a repository exists with the given ID.
	 * @param int Repository ID
	 * @return boolean True if repository exists
	 */
	static function exists( $p_id ) {
		$t_repo_table = plugin_table( 'repository', 'Source' );

		$t_query = "SELECT COUNT(*) FROM $t_repo_table WHERE id=" . db_param(0);
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
	var $branch;
	var $timestamp;
	var $author;
	var $message;

	var $files; # array of SourceFile's
	var $bugs;

	/**
	 * Build a new changeset object given certain properties.
	 * @param int Repository ID
	 * @param string Changeset revision
	 * @param string Timestamp
	 * @param string Author
	 * @param string Commit message
	 */
	function __construct( $p_repo_id, $p_revision, $p_branch='', $p_timestamp='', $p_author='', $p_message='', $p_user_id=0 ) {
		$this->id			= 0;
		$this->user_id		= $p_user_id;
		$this->repo_id		= $p_repo_id;
		$this->revision		= $p_revision;
		$this->branch		= $p_branch;
		$this->timestamp	= $p_timestamp;
		$this->author		= $p_author;
		$this->message		= $p_message;

		$this->files		= array();
		$this->bugs			= array();
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
			$t_query = "INSERT INTO $t_changeset_table ( repo_id, revision, branch, user_id, timestamp, author, message ) VALUES ( " .
				db_param(0) . ', ' . db_param(1) . ', ' . db_param(2) . ', ' . db_param(3) . ', ' .
				db_param(4) . ', ' . db_param(5) . ', ' . db_param(6) . ' )';
			db_query_bound( $t_query, array( $this->repo_id, $this->revision, $this->branch, $this->user_id,
							$this->timestamp, $this->author, $this->message ) );

			$this->id = db_insert_id( $t_changeset_table );

			foreach( $this->files as $t_file ) {
				$t_file->change_id = $this->id;
			}

		} else { # update
			$t_query = "UPDATE $t_changeset_table SET repo_id=" . db_param(0) . ', revision=' . db_param(1) .
				', branch=' . db_param(2) . ', user_id=' . db_param(3) . ', timestamp=' . db_param(4) .
				', author=' . db_param(5) . ', message=' . db_param(6) . ' WHERE id=' . db_param(7);
			db_query_bound( $t_query, array( $this->repo_id, $this->revision, $this->branch, $this->user_id,
							$this->timestamp, $this->author, $this->message, $this->id ) );
		}

		foreach( $this->files as $t_file ) {
			$t_file->save();
		}

		$this->save_bugs();
	}

	function save_bugs() {
		$t_bug_table = plugin_table( 'bug', 'Source' );

		$t_query = "DELETE FROM $t_bug_table WHERE change_id=" . $this->id;
		db_query( $t_query );

		if ( count( $this->bugs ) > 0 ) {
			$t_query = "INSERT INTO $t_bug_table ( change_id, bug_id ) VALUES ";

			$t_count = 0;
			$t_params = array();

			foreach( $this->bugs as $t_bug_id ) {
				$t_query .= ( $t_count == 0 ? '' : ', ' ) .
					'(' . db_param( $t_count++ ) . ', ' . db_param( $t_count++ ) . ')';
				$t_params[] = $this->id;
				$t_params[] = $t_bug_id;
			}

			db_query_bound( $t_query, $t_params );
		}
	}

	/**
	 * Load all file objects associated with this changeset.
	 */
	function load_files() {
		$this->files = SourceFile::load_by_changeset( $this->id );
		return $this->files;
	}

	/**
	 * Load all bug numbers associated with this changeset.
	 */
	function load_bugs() {
		$t_bug_table = plugin_table( 'bug', 'Source' );

		$t_query = "SELECT bug_id FROM $t_bug_table WHERE change_id=" . db_param(0);
		$t_result = db_query_bound( $t_query, array( $this->id ) );

		$this->bugs = array();
		while( $t_row = db_fetch_array( $t_result ) ) {
			$this->bugs[] = $t_row['bug_id'];
		}
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

		$t_query = "SELECT * FROM $t_changeset_table WHERE repo_id=" . db_param(0) . '
				AND revision=' . db_param(1);
		$t_params = array( $p_repo_id, $p_revision );

		if ( !is_null( $p_branch ) ) {
			$t_query .= ' AND branch=' . db_param(2);
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

		$t_query = "SELECT * FROM $t_changeset_table WHERE id=" . db_param(0) . '
				ORDER BY timestamp ASC';
		$t_result = db_query_bound( $t_query, array( $p_id ) );

		if ( db_num_rows( $t_result ) < 1 ) {
			trigger_error( ERROR_GENERIC, ERROR );
		}

		$t_row = db_fetch_array( $t_result );
		$t_changeset = new SourceChangeset( $t_row['repo_id'], $t_row['revision'], $t_row['branch'], $t_row['timestamp'], $t_row['author'], $t_row['message'], $t_row['user_id'] );
		$t_changeset->id = $t_row['id'];

		return $t_changeset;
	}

	/**
	 * Fetch an array of changeset objects for a given repository ID.
	 * @param int Repository ID
	 * @return array Changeset objects
	 */
	static function load_by_repo( $p_repo_id, $p_load_files=false  ) {
		$t_changeset_table = plugin_table( 'changeset', 'Source' );

		$t_query = "SELECT * FROM $t_changeset_table WHERE repo_id=" . db_param(0) . '
				ORDER BY timestamp ASC';
		$t_result = db_query_bound( $t_query, array( $p_repo_id ) );

		$t_changesets = array();

		while ( $t_row = db_fetch_array( $t_result ) ) {
			$t_changeset = new SourceChangeset( $t_row['repo_id'], $t_row['revision'], $t_row['branch'], $t_row['timestamp'], $t_row['author'], $t_row['message'], $t_row['user_id'] );
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
				WHERE b.bug_id=" . db_param(0) . '
				ORDER BY c.timestamp ASC';
		$t_result = db_query_bound( $t_query, array( $p_bug_id ) );

		$t_changesets = array();

		while ( $t_row = db_fetch_array( $t_result ) ) {
			$t_changeset = new SourceChangeset( $t_row['repo_id'], $t_row['revision'], $t_row['branch'], $t_row['timestamp'], $t_row['author'], $t_row['message'], $t_row['user_id'] );
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

		$t_query = "DELETE FROM $t_changeset_table WHERE repo_id=" . db_param(0);
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
				db_param(0) . ', ' . db_param(1) . ', ' . db_param(2) . ', ' . db_param(3) . ' )';
			db_query_bound( $t_query, array( $this->change_id, $this->revision, $this->action, $this->filename ) );

			$this->id = db_insert_id( $t_file_table );
		} else { # update
			$t_query = "UPDATE $t_file_table SET change_id=" . db_param(0) . ', revision=' . db_param(1) .
				', action=' . db_param(2) . ', filename=' . db_param(3) . ' WHERE id=' . db_param(4);
			db_query_bound( $t_query, array( $this->change_id, $this->revision, $this->action, $this->filename, $this->id ) );
		}
	}

	static function load( $p_id ) {
		$t_file_table = plugin_table( 'file', 'Source' );

		$t_query = "SELECT * FROM $t_file_table WHERE id=" . db_param(0);
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

		$t_query = "SELECT * FROM $t_file_table WHERE change_id=" . db_param(0);
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

		$t_query = "DELETE FROM $t_file_table WHERE change_id=" . db_param(0);
		db_query_bound( $t_query, array( $p_change_id ) );
	}
}

