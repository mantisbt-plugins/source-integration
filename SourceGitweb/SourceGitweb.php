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

require_once( config_get( 'core_path' ) . 'json_api.php' );

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

		return $this->uri_base() . ( $t_changeset ? 'h=' . $t_changeset->revision : '' );
	}

	function url_changeset( $p_event, $p_repo, $p_changeset ) {
		if ( 'gitweb' != $p_repo->type ) {
			return;
		}

		return $this->uri_base() . 'a=commit;h=' . $p_changeset->revision;
	}

	function url_file( $p_event, $p_repo, $p_changeset, $p_file ) {
		if ( 'gitweb' != $p_repo->type ) {
			return;
		}

		return $this->uri_base() . 'a=blob;f=' . $p_file->filename . ';h=' . $p_changeset->revision;
	}

	function url_diff( $p_event, $p_repo, $p_changeset, $p_file ) {
		if ( 'gitweb' != $p_repo->type ) {
			return;
		}

		return $this->uri_base() . 'a=blobdiff;f=' . $p_file->filename . ';h=' . $p_changeset->revision;
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

		$f_payload = gpc_get_string( 'payload', null );
		if ( is_null( $f_payload ) ) {
			return;
		}

		if ( false === stripos( $f_payload, 'github.com' ) ) {
			return;
		}

		$t_data = json_decode( $f_payload, true );
		$t_reponame = $t_data['repository']['name'];

		$t_repo_table = plugin_table( 'repository', 'Source' );

		$t_query = "SELECT * FROM $t_repo_table WHERE info LIKE " . db_param();
		$t_result = db_query_bound( $t_query, array( '%' . $t_reponame . '%' ) );

		if ( db_num_rows( $t_result ) < 1 ) {
			return;
		}
	
		while ( $t_row = db_fetch_array( $t_result ) ) {
			$t_repo = new SourceRepo( $t_row['type'], $t_row['name'], $t_row['url'], $t_row['info'] );
			$t_repo->id = $t_row['id'];

			if ( $t_repo->info['hub_reponame'] == $t_reponame ) {
				return array( 'repo' => $t_repo, 'data' => $t_data );
			}
		}

		return;
	}

	function commit( $p_event, $p_repo, $p_data ) {
		# TODO: Implement real commit sequence.
		return;

		if ( 'gitweb' != $p_repo->type ) {
			return;
		}

		$t_commits = array();

		foreach( $p_data['commits'] as $t_commit ) {
			$t_commits[] = $t_commit['id'];
		}

		$t_branch = '';
		if ( preg_match( '@refs/heads/([a-zA-Z0-9_-]*)@', $p_data['ref'], $t_matches ) ) {
			$t_branch = $t_matches[1];
		}

		$t_result = $this->import_commits( $p_repo, $this->uri_base( $p_repo ), $t_commits, $t_branch );

		return true;
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

		foreach( $t_branches as $t_branch ) {
			$t_result = $this->import_commits( $p_repo, $this->uri_base( $p_repo ), $t_branch, $t_branch  );
		}

		echo '</pre>';

		return $t_result;
	}

	function import_latest( $p_event, $p_repo ) {
		$t_result = $this->import_full( $p_event, $p_repo );

		return $t_result;
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

			$t_uri = $p_uri_base . 'commit/' . $t_commit_id;
			$t_json = json_url( $t_uri, 'commit' );

			if ( false === $t_json ) {
				echo "failed.\n";
				continue;
			}

			$t_commit_parents = $this->json_commit_changeset( $p_repo, $t_json, $p_branch );

			$t_parents = array_merge( $t_parents, $t_commit_parents );
		}

		return true;
	}

	function json_commit_changeset( $p_repo, $p_json, $p_branch='' ) {

		echo "processing $p_json->id ... ";
		if ( !SourceChangeset::exists( $p_repo->id, $p_json->id ) ) {
			$t_user_id = user_get_id_by_email( $p_json->author->email );
			if ( false === $t_user_id ) {
				$t_user_id = user_get_id_by_realname( $p_json->author->name );
			}

			$t_parents = array();
			foreach( $p_json->parents as $t_parent ) {
				$t_parents[] = $t_parent->id;
			}

			$t_changeset = new SourceChangeset( $p_repo->id, $p_json->id, $p_branch,
				$p_json->authored_date, $p_json->author->email,
				$p_json->message, $t_user_id );

			foreach( $p_json->added as $t_added ) {
				$t_changeset->files[] = new SourceFile( 0, '', $t_added->filename, 'add' );
			}

			foreach( $p_json->removed as $t_removed ) {
				$t_changeset->files[] = new SourceFile( 0, '', $t_removed->filename, 'rm' );
			}

			foreach( $p_json->modified as $t_modified ) {
				$t_changeset->files[] = new SourceFile( 0, '', $t_modified->filename, 'mod' );
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
