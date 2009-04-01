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

if ( false === include_once( config_get( 'plugin_path' ) . 'Source/MantisSourcePlugin.class.php' ) ) {
	return;
}

require_once( config_get( 'core_path' ) . 'url_api.php' );

class SourceGitwebPlugin extends MantisSourcePlugin {
	function register() {
		$this->name = plugin_lang_get( 'title' );
		$this->description = plugin_lang_get( 'description' );

		$this->version = '0.13';
		$this->requires = array(
			'MantisCore' => '1.2.0',
			'Source' => '0.13',
			'Meta' => '0.1',
		);

		$this->author = 'John Reese';
		$this->contact = 'jreese@leetcode.net';
		$this->url = 'http://leetcode.net';
	}

	function get_types( $p_event ) {
		return array( 'gitweb' => plugin_lang_get( 'gitweb' ) );
	}

	function show_type( $p_event, $p_type ) {
		if ( 'gitweb' == $p_type ) {
			return plugin_lang_get( 'gitweb' );
		}
	}

	function show_changeset( $p_event, $p_repo, $p_changeset ) {
		if ( 'gitweb' != $p_repo->type ) {
			return;
		}

		$t_ref = substr( $p_changeset->revision, 0, 8 );
		$t_branch = $p_changeset->branch;

		return "$t_branch $t_ref";
	}

	function show_file( $p_event, $p_repo, $p_changeset, $p_file ) {
		if ( 'gitweb' != $p_repo->type ) {
			return;
		}

		return  "$p_file->action - $p_file->filename";
	}

	function uri_base( $p_repo ) {
		$t_uri_base = $p_repo->info['gitweb_root'] . '?p=' . $p_repo->info['gitweb_project'] . ';';

		return $t_uri_base;
	}

	function url_repo( $p_event, $p_repo, $t_changeset=null ) {
		if ( 'gitweb' != $p_repo->type ) {
			return;
		}

		return $this->uri_base( $p_repo ) . ( $t_changeset ? 'h=' . $t_changeset->revision : '' );
	}

	function url_changeset( $p_event, $p_repo, $p_changeset ) {
		if ( 'gitweb' != $p_repo->type ) {
			return;
		}

		return $this->uri_base( $p_repo ) . 'a=commitdiff;h=' . $p_changeset->revision;
	}

	function url_file( $p_event, $p_repo, $p_changeset, $p_file ) {
		if ( 'gitweb' != $p_repo->type ) {
			return;
		}

		return $this->uri_base( $p_repo ) . 'a=blob;f=' . $p_file->filename .
			';h=' . $p_file->revision . ';hb=' . $p_changeset->revision;
	}

	function url_diff( $p_event, $p_repo, $p_changeset, $p_file ) {
		if ( 'gitweb' != $p_repo->type ) {
			return;
		}

		return $this->uri_base( $p_repo ) . 'a=blobdiff;f=' . $p_file->filename .
			';h=' . $p_file->revision . ';hb=' . $p_changeset->revision . ';hpb=' . $p_changeset->parent;
	}

	function update_repo_form( $p_event, $p_repo ) {
		if ( 'gitweb' != $p_repo->type ) {
			return;
		}

		$t_gitweb_root = null;
		$t_gitweb_project = null;

		if ( isset( $p_repo->info['gitweb_root'] ) ) {
			$t_gitweb_root = $p_repo->info['gitweb_root'];
		}

		if ( isset( $p_repo->info['gitweb_project'] ) ) {
			$t_gitweb_project = $p_repo->info['gitweb_project'];
		}

		if ( isset( $p_repo->info['master_branch'] ) ) {
			$t_master_branch = $p_repo->info['master_branch'];
		} else {
			$t_master_branch = 'master';
		}
?>
<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'gitweb_root' ) ?></td>
<td><input name="gitweb_root" maxlength="250" size="40" value="<?php echo string_attribute( $t_gitweb_root ) ?>"/></td>
</tr>
<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'gitweb_project' ) ?></td>
<td><input name="gitweb_project" maxlength="250" size="40" value="<?php echo string_attribute( $t_gitweb_project ) ?>"/></td>
</tr>
<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'master_branch' ) ?></td>
<td><input name="master_branch" maxlength="250" size="40" value="<?php echo string_attribute( $t_master_branch ) ?>"/></td>
</tr>
<?php
	}

	function update_repo( $p_event, $p_repo ) {
		if ( 'gitweb' != $p_repo->type ) {
			return;
		}

		$f_gitweb_root = gpc_get_string( 'gitweb_root' );
		$f_gitweb_project = gpc_get_string( 'gitweb_project' );
		$f_master_branch = gpc_get_string( 'master_branch' );

		$p_repo->info['gitweb_root'] = $f_gitweb_root;
		$p_repo->info['gitweb_project'] = $f_gitweb_project;
		$p_repo->info['master_branch'] = $f_master_branch;

		return $p_repo;
	}

	function precommit( $p_event ) {
		# TODO: Implement real commit sequence.
		return;
	}

	function commit( $p_event, $p_repo, $p_data ) {
		# TODO: Implement real commit sequence.
		return;

		if ( 'gitweb' != $p_repo->type ) {
			return;
		}
	}

	function import_full( $p_event, $p_repo ) {
		if ( 'gitweb' != $p_repo->type ) {
			return;
		}
		echo '<pre>';

		$t_branch = $p_repo->info['master_branch'];
		if ( is_blank( $t_branch ) ) {
			$t_branch = 'master';
		}

		$t_branches = map( 'trim', explode( ',', $t_branch ) );

		$t_changeset_table = plugin_table( 'changeset', 'Source' );

		foreach( $t_branches as $t_branch ) {
			$t_query = "SELECT parent FROM $t_changeset_table
				WHERE repo_id=" . db_param() . ' AND branch=' . db_param() .
				'ORDER BY timestamp ASC';
			$t_result = db_query_bound( $t_query, array( $p_repo->id, $t_branch ), 1 );

			$t_commits = array( $t_branch );

			if ( db_num_rows( $t_result ) > 0 ) {
				$t_parent = db_result( $t_result );
				echo "Oldest '$t_branch' branch parent: '$t_parent'\n";

				if ( !empty( $t_parent ) ) {
					$t_commits[] = $t_parent;
				}
			}

			$t_status = $this->import_commits( $p_repo, $this->uri_base( $p_repo ), $t_commits, $t_branch  );
		}

		echo '</pre>';

		return $t_status;
	}

	function import_latest( $p_event, $p_repo ) {
		$t_status = $this->import_full( $p_event, $p_repo );

		return $t_status;
	}

	function import_commits( $p_repo, $p_uri_base, $p_commit_ids, $p_branch='' ) {
		if ( is_array( $p_commit_ids ) ) {
			$t_parents = $p_commit_ids;
		} else {
			$t_parents = array( $p_commit_ids );
		}

		while( count( $t_parents ) > 0 ) {
			$t_commit_id = array_shift( $t_parents );

			echo "Retrieving $t_commit_id ... ";

			$t_commit_url = $this->uri_base( $p_repo ) . 'a=commit;h=' . $t_commit_id;
			$t_input = url_get( $t_commit_url );

			if ( false === $t_input ) {
				echo "failed.\n";
				continue;
			}

			$t_commit_parents = $this->commit_changeset( $p_repo, $t_input, $p_branch );

			$t_parents = array_merge( $t_parents, $t_commit_parents );
		}

		return true;
	}

	function commit_changeset( $p_repo, $p_input, $p_branch='' ) {

		$t_input = str_replace( array(PHP_EOL, '&lt;', '&gt;', '&nbsp;'), array('', '<', '>', ' '), $p_input );

		# Exract sections of commit data and changed files
		$t_input_p1 = strpos( $t_input, '<div class="title_text">' );
		$t_input_p2 = strpos( $t_input, '<div class="list_head">' );
		if ( false === $t_input_p1 || false === $t_input_p2 ) {
			echo 'commit data failure.';
			var_dump( strlen( $t_input ), $t_input_p1, $t_input_p2 );
			die();
		}
		$t_gitweb_data = substr( $t_input, $t_input_p1, $t_input_p2 - $t_input_p1 );

		$t_input_p1 = strpos( $t_input, '<table class="diff_tree">' );

		if ( false === $t_input_p1) {
			$t_input_p1 = strpos( $t_input, '<table class="combined diff_tree">' );
		}

		$t_input_p2 = strpos( $t_input, '<div class="page_footer">' );
		if ( false === $t_input_p1 || false === $t_input_p2 ) {
			echo 'file data failure.';
			var_dump( strlen( $t_input ), $t_input_p1, $t_input_p2 );
			die();
		}
		$t_gitweb_files = substr( $t_input, $t_input_p1, $t_input_p2 - $t_input_p1 );

		# Get commit revsion and make sure it's not a dupe
		preg_match( '#<tr><td>commit</td><td class="sha1">([a-f0-9]*)</td></tr>#', $t_gitweb_data, $t_matches );
		$t_commit['revision'] = $t_matches[1];

		echo "processing $t_commit[revision] ... ";
		if ( !SourceChangeset::exists( $p_repo->id, $t_commit['revision'] ) ) {

			# Parse for commit data
			preg_match( '#<tr><td>author</td><td>([^<>]*) <([^<>]*)></td></tr>'.
				'<tr><td></td><td> \w*, (\d* \w* \d* \d*:\d*:\d*)#', $t_gitweb_data, $t_matches );
			$t_commit['author'] = $t_matches[1];
			$t_commit['author_email'] = $t_matches[2];
			$t_commit['date'] = date( 'Y-m-d H:i:s', strtotime( $t_matches[3] ) );

			if( preg_match( '#<tr><td>parent</td><td class="sha1"><a [^<>]*>([a-f0-9]*)</a></td>#', $t_gitweb_data, $t_matches ) ) {
				$t_commit['parent'] = $t_matches[1];
			}

			preg_match( '#<div class="page_body">(.*)</div>#', $t_gitweb_data, $t_matches );
			$t_commit['message'] = trim( str_replace( '<br/>', PHP_EOL, $t_matches[1] ) );

			# Strip ref links and signoff spans from commit message
			$t_commit['message'] = preg_replace( array(
					'@<a[^>]*>([^<]*)<\/a>@',
					'@<span[^>]*>([^<]*<[^>]*>[^<]*)<\/span>@', #finds <span..>signed-off by <email></span>
				), '$1', $t_commit['message'] );

			# Parse for changed file data
			$t_commit['files'] = array();

			preg_match_all( '#<tr class="(?:light|dark)"><td><a class="list" href="[^"]*;h=(\w+);[^"]*">([^<>]+)</a></td>'.
				'<td>(?:<span class="file_status (\w+)">[^<>]*</span>)?</td>#',
				$t_gitweb_files, $t_matches, PREG_SET_ORDER );

			foreach( $t_matches as $t_file_matches ) {
				$t_file = array();
				$t_file['filename'] = $t_file_matches[2];
				$t_file['revision'] = $t_file_matches[1];

				if ( isset( $t_file_matches[3] ) ) {
					if ( 'new' == $t_file_matches[3] ) {
						$t_file['action'] = 'add';
					} else if ( 'deleted' == $t_file_matches[3] ) {
						$t_file['action'] = 'rm';
					}
				} else {
					$t_file['action'] = 'mod';
				}

				$t_commit['files'][] = $t_file;
			}

			# Start building the changeset
			$t_user_id = user_get_id_by_email( $t_commit['author_email'] );
			if ( false === $t_user_id ) {
				$t_user_id = user_get_id_by_realname( $t_commit['author'] );
			}

			$t_parents = array();
			if ( isset( $t_commit['parent'] ) ) {
				$t_parents[] = $t_commit['parent'];
			}

			$t_changeset = new SourceChangeset( $p_repo->id, $t_commit['revision'], $p_branch,
				$t_commit['date'], $t_commit['author'], $t_commit['message'], $t_user_id,
				( isset( $t_commit['parent'] ) ? $t_commit['parent'] : '' ) );

			foreach( $t_commit['files'] as $t_file ) {
				$t_changeset->files[] = new SourceFile( 0, $t_file['revision'], $t_file['filename'], $t_file['action'] );
			}

			$t_changeset->bugs = Source_Parse_Buglinks( $t_changeset->message );
			$t_changeset->save();

			echo "saved.\n";
			return $t_parents;
		} else {
			echo "already exists.\n";
			return array();
		}
	}
}
