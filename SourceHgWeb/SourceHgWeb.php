<?php
# Copyright (C) 2010 David Hicks, Ton Plomp, Marcel Bennett
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

if ( false === include_once( config_get( 'plugin_path' ) . 'Source/MantisSourcePlugin.class.php' ) ) {
	return;
}

require_once( config_get( 'core_path' ) . 'url_api.php' );

class SourceHgWebPlugin extends MantisSourcePlugin {

	const PLUGIN_VERSION = '2.0.1';
	const FRAMEWORK_VERSION_REQUIRED = '2.0.0';

	/**
	 * Various PCRE patterns used to parse HgWeb output when retrieving
	 * changeset info
	 * @see commit_changeset()
	 */
	const PATTERN_USER = '(?<id>User) (?<user>[^<>]*)(?(?=(?=<))<(?<email>[^<>]*)>|.*)';
	const PATTERN_DATE = '(?<id>Date) (?<date>\d+) (?<tz>-?\d+)';
	const PATTERN_REVISION = '(?<id>Node ID|Parent) +(?<rev>[0-9a-f]+)';
	const PATTERN_DIFF = 'diff[\s]*-r[\s]([^\s]*)[\s]*-r[\s]([^\s]*)[\s]([^\n]*)';
	const PATTERN_BINARY_FILE = 'Binary file[\s]([^\r\n\t\f\v]*)[\s]has changed';
	# Don't use '/' as pattern delimiter with this one
	const PATTERN_PLUS_MINUS = '\-{3}[\s](/dev/null)?[^\t]*[^\n]*\n\+{3}[\s](/dev/null)?[^\t]*\t[^\n]*';

	function register() {
		$this->name = plugin_lang_get( 'title' );
		$this->description = plugin_lang_get( 'description' );

		$this->version = self::PLUGIN_VERSION;
		$this->requires = array(
			'MantisCore' => self::MANTIS_VERSION,
			'Source' => self::FRAMEWORK_VERSION_REQUIRED,
		);

		$this->author = 'David Hicks';
		$this->contact = '';
		$this->url = 'https://github.com/mantisbt-plugins/source-integration/';
	}

	public $type = 'hgweb';

	public function show_type() {
		return plugin_lang_get( 'hgweb' );
	}

	public function show_changeset( $p_repo, $p_changeset ) {
		$t_ref = substr( $p_changeset->revision, 0, 8 );
		$t_branch = $p_changeset->branch;

		return "$t_branch $t_ref";
	}

	function show_file( $p_repo, $p_changeset, $p_file ) {
		return "$p_file->action - $p_file->filename";
	}

	private function uri_base( $p_repo ) {
		$t_uri_base = $p_repo->info['hgweb_root'] . $p_repo->info['hgweb_project'] . '/';

		return $t_uri_base;
	}

	public function url_repo( $p_repo, $p_changeset=null ) {
		return $this->uri_base( $p_repo ) . ( $p_changeset ? 'rev/' . $p_changeset->revision : '' );
	}

	public function url_changeset( $p_repo, $p_changeset ) {
		return $this->url_repo( $p_repo, $p_changeset );
	}

	public function url_file( $p_repo, $p_changeset, $p_file ) {
		return $this->uri_base( $p_repo ) . 'raw-file/' .
			$p_file->revision . '/' . $p_file->filename;
	}

	public function url_diff( $p_repo, $p_changeset, $p_file ) {
		return $this->uri_base( $p_repo ) . 'raw-diff/' .
			$p_file->revision . '/' . $p_file->filename;
	}

	public function update_repo_form( $p_repo ) {
		$t_hgweb_root = null;
		$t_hgweb_project = null;

		if ( isset( $p_repo->info['hgweb_root'] ) ) {
			$t_hgweb_root = $p_repo->info['hgweb_root'];
		}

		if ( isset( $p_repo->info['hgweb_project'] ) ) {
			$t_hgweb_project = $p_repo->info['hgweb_project'];
		}

		if ( isset( $p_repo->info['master_branch'] ) ) {
			$t_master_branch = $p_repo->info['master_branch'];
		} else {
			$t_master_branch = 'default';
		}
?>
<tr>
	<td class="category"><?php echo plugin_lang_get( 'hgweb_root' ) ?></td>
	<td>
		<input type="text" name="hgweb_root" maxlength="250" size="40" value="<?php echo string_attribute( $t_hgweb_root ) ?>"/>
	</td>
</tr>
<tr>
	<td class="category"><?php echo plugin_lang_get( 'hgweb_project' ) ?></td>
	<td>
		<input type="text" name="hgweb_project" maxlength="250" size="40" value="<?php echo string_attribute( $t_hgweb_project ) ?>"/>
	</td>
</tr>
<tr>
	<td class="category"><?php echo plugin_lang_get( 'master_branch' ) ?></td>
	<td>
		<input type="text" name="master_branch" maxlength="250" size="40" value="<?php echo string_attribute( $t_master_branch ) ?>"/>
	</td>
</tr>
<?php
	}

	public function update_repo( $p_repo ) {
		$f_hgweb_root = gpc_get_string( 'hgweb_root' );
		$f_hgweb_project = gpc_get_string( 'hgweb_project' );
		$f_master_branch = gpc_get_string( 'master_branch' );

		$p_repo->info['hgweb_root'] = $f_hgweb_root;
		$p_repo->info['hgweb_project'] = $f_hgweb_project;
		$p_repo->info['master_branch'] = $f_master_branch;

		return $p_repo;
	}

	public function precommit() {
		# TODO: Implement real commit sequence.
		return;
	}

	public function commit( $p_repo, $p_data ) {
		# The -d option from curl requires you to encode your own data.
		# Once it reaches here it is decoded. Hence we split by a space
		# were as the curl command uses a '+' character instead.
		# i.e. DATA=`echo $INPUT | sed -e 's/ /+/g'`
		list ( , $t_commit_id, $t_branch ) = explode( ' ', $p_data );
		list ( , , $t_branch ) = explode( '/', $t_branch );

		return $this->import_commits($p_repo, null, $t_commit_id, $t_branch);
	}

	public function import_full( $p_repo ) {
		echo '<pre>';

		$t_branch = $p_repo->info['master_branch'];
		if ( is_blank( $t_branch ) ) {
			$t_branch = 'default';
		}

		$t_branches = array_map( 'trim', explode( ',', $t_branch ) );
		$t_changesets = array();

		$t_changeset_table = plugin_table( 'changeset', 'Source' );

		foreach( $t_branches as $t_branch ) {
			$t_query = "SELECT parent FROM $t_changeset_table
				WHERE repo_id=" . db_param() . ' AND branch=' . db_param() .
				' ORDER BY timestamp ASC';
			$t_result = db_query( $t_query, array( $p_repo->id, $t_branch ), 1 );

			$t_commits = array( $t_branch );

			if ( db_num_rows( $t_result ) > 0 ) {
				$t_parent = db_result( $t_result );
				echo 'Oldest \'' . string_display_line( $t_branch ) . '\' branch parent: \'' . string_display_line( $t_parent ) . "'\n";

				if ( !empty( $t_parent ) ) {
					$t_commits[] = $t_parent;
				}
			}

			$t_changesets = array_merge( $t_changesets, $this->import_commits( $p_repo, $this->uri_base( $p_repo ), $t_commits, $t_branch  ) );
		}

		echo '</pre>';

		return $t_changesets;
	}

	public function import_latest( $p_repo ) {
		return $this->import_full( $p_repo );
	}

	private function import_commits( $p_repo, $p_uri_base, $p_commit_ids, $p_branch='' ) {
		static $s_parents = array();
		static $s_counter = 0;

		if ( is_array( $p_commit_ids ) ) {
			$s_parents = array_merge( $s_parents, $p_commit_ids );
		} else {
			$s_parents[] = $p_commit_ids;
		}

		$t_changesets = array();

		while( count( $s_parents ) > 0 && $s_counter < 200 ) {
			$t_commit_id = array_shift( $s_parents );

			echo 'Retrieving ' . string_display_line( $t_commit_id ) . '... ';

			$t_commit_url = $this->uri_base( $p_repo ) . 'raw-rev/' . $t_commit_id;
			$t_input = url_get( $t_commit_url );

			if( !$t_input ) {
				echo "failed.\n";
				continue;
			}

			list( $t_changeset, $t_commit_parents ) = $this->commit_changeset( $p_repo, $t_input, $p_branch );
			if ( !is_null( $t_changeset ) ) {
				$t_changesets[] = $t_changeset;
			}

			$s_parents = array_merge( $s_parents, $t_commit_parents );
		}

		$s_counter = 0;
		return $t_changesets;
	}

	/**
	 * Parse changeset data and store it if it does not exist already.
	 * This assumes a standard Mercurial template for raw changesets. Using a
	 * customized one may break the parsing logic.
	 * @param SourceRepo $p_repo Repository
	 * @param string     $p_input Raw changeset data
	 * @param string     $p_branch
	 * @return array SourceChangeset object, list of parent revisions
	 */
	private function commit_changeset( $p_repo, $p_input, $p_branch='' ) {
		$t_input = explode( "\n", $p_input );
		$i = 0;

		# Skip changeset header
		while( strpos( $t_input[$i++], '# HG changeset patch' ) === false );

		# Process changeset metadata
		$t_commit = array();
		$t_parents = array();
		static $s_pattern_metadata = '/^# (?:'
			. self::PATTERN_USER . '|'
			. self::PATTERN_DATE . '|'
			. self::PATTERN_REVISION
			. ')/J';
		while( true ) {
			$t_match = preg_match( $s_pattern_metadata, $t_input[$i], $t_metadata );
			if( $t_match == false ) {
				# We reached the end of metadata, next line is the commit message
				break;
			}
			switch( $t_metadata['id'] ) {
				case 'User':
					$t_commit['author'] = isset( $t_metadata['user'] ) ? trim( $t_metadata['user'] ) : '';
					$t_commit['author_email'] = isset( $t_metadata['email'] ) ? $t_metadata['email'] : '';
					break;
				case 'Date':
					$t_timestamp_gmt = $t_metadata['date'] - (int)$t_metadata['tz'];
					$t_commit['date'] = gmdate( 'Y-m-d H:i:s', $t_timestamp_gmt );
					break;
				case 'Node ID':
					$t_commit['revision'] = $t_metadata['rev'];
					break;
				case 'Parent':
					$t_parents[] = $t_commit['parent'] = $t_metadata['rev'];
					break;
			}
			$i++;
		}

		if( !SourceChangeset::exists( $p_repo->id, $t_commit['revision'] ) ) {
			# Read commit message
			$t_message = '';
			while( $i < count( $t_input ) ) {
				$t_match = preg_match(
					'/^' . self::PATTERN_DIFF . '/',
					$t_input[$i]
				);
				if( $t_match ) {
					break;
				}
				$t_message .= $t_input[$i++] . "\n";
			}
			$t_commit['message'] = trim( $t_message );

			$t_changeset = new SourceChangeset( $p_repo->id, $t_commit['revision'],
				$p_branch, $t_commit['date'], $t_commit['author'],
				$t_commit['message'], 0,
				(isset( $t_commit['parent'] ) ? $t_commit['parent'] : '')
			);

			$t_changeset->author_email = empty($t_commit['author_email'])? '': $t_commit['author_email'];

			static $s_pattern_diff = '#'
				. self::PATTERN_DIFF . '\n('
				. self::PATTERN_BINARY_FILE . '|'
				. self::PATTERN_PLUS_MINUS
				. ')#u';
			preg_match_all( $s_pattern_diff, $p_input, $t_matches, PREG_SET_ORDER );

			$t_commit['files'] = array();

			foreach( $t_matches as $t_file_matches ) {
				$t_file = array();
				$t_file['filename'] = $t_file_matches[3];
				$t_file['revision'] = $t_commit['revision'];

				if(!empty($t_file_matches[3])) {
					if (empty($t_file_matches[5]) && empty($t_file_matches[6]) && empty($t_file_matches[7])) {
						$t_file['action'] = 'mod';
					}
					else if(!empty($t_file_matches[5])) {
						$t_file['action'] = 'bin';
					}
					else if ("/dev/null" == $t_file_matches[6]) {
						$t_file['action'] = 'add';
					}
					else if ("/dev/null" == $t_file_matches[7]) {
						$t_file['action'] = 'rm';
					}
					else if ("/dev/null" == $t_file_matches[7] && "/dev/null" == $t_file_matches[6]) {
						$t_file['action'] = 'n/a';
					}
				}
				$t_commit['files'][] = $t_file;
			}

			foreach( $t_commit['files'] as $t_file ) {
				$t_changeset->files[] = new SourceFile( 0, $t_file['revision'], $t_file['filename'], $t_file['action'] );
			}

			$t_changeset->save();

			echo "saved.\n";
			return array( $t_changeset, $t_parents );
		} else {
			echo "already exists.\n";
			return array( null, array() );
		}
	}
}
