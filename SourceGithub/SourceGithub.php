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

class SourceGithubPlugin extends MantisSourcePlugin {
	function register() {
		$this->name = lang_get( 'plugin_SourceGithub_title' );
		$this->description = lang_get( 'plugin_SourceGithub_description' );

		$this->version = '0.1';
		$this->requires = array(
			'MantisCore' => '1.2.0',
			'Source' => '0.9a',
		);

		$this->author = 'John Reese';
		$this->contact = 'jreese@leetcode.net';
		$this->url = 'http://leetcode.net';
	}

	function get_types( $p_event ) {
		return array( 'github' => lang_get( 'plugin_SourceGithub_github' ) );
	}

	function show_type( $p_event, $p_type ) {
		if ( 'github' == $p_type ) {
			return lang_get( 'plugin_SourceGithub_github' );
		}
	}

	function show_changeset( $p_event, $p_repo, $p_changeset ) {
		if ( 'github' != $p_repo->type ) {
			return;
		}

		$t_ref = substr( $p_changeset->revision, 0, 8 );
		$t_branch = $p_changeset->branch;

		return "$p_repo->name $t_ref ($t_branch)";
	}

	function show_file( $p_event, $p_repo, $p_changeset, $p_file ) {
		if ( 'github' != $p_repo->type ) {
			return;
		}

		return  "$p_action - $p_file->filename";
	}

	function url_repo( $p_event, $p_repo, $t_changeset=null ) {
		if ( 'github' != $p_repo->type ) {
			return;
		}

		$t_username = $p_repo->info['hub_username'];
		$t_reponame = $p_repo->info['hub_reponame'];

		if ( !is_null( $t_changeset ) ) {
			$t_ref = "/$t_changeset->revision";
		}

		return "http://github.com/$t_username/$t_reponame/tree$t_ref";
	}

	function url_changeset( $p_event, $p_repo, $p_changeset ) {
		if ( 'github' != $p_repo->type ) {
			return;
		}

		$t_username = $p_repo->info['hub_username'];
		$t_reponame = $p_repo->info['hub_reponame'];
		$t_ref = "$t_changeset->revision";

		return "http://github.com/$t_username/$t_reponame/commit/$t_ref";
	}

	function url_file( $p_event, $p_repo, $p_changeset, $p_file ) {
		if ( 'github' != $p_repo->type ) {
			return;
		}

		$t_username = $p_repo->info['hub_username'];
		$t_reponame = $p_repo->info['hub_reponame'];
		$t_ref = "$t_changeset->revision";
		$t_filename = $t_file->filename;

		return "http://github.com/$t_username/$t_reponame/tree/$t_ref/$t_filename";
	}

	function url_diff( $p_event, $p_repo, $p_changeset, $p_file ) {
		if ( 'github' != $p_repo->type ) {
			return;
		}

		$t_username = $p_repo->info['hub_username'];
		$t_reponame = $p_repo->info['hub_reponame'];
		$t_ref = "$t_changeset->revision";
		$t_filename = $t_file->filename;

		return "http://github.com/$t_username/$t_reponame/commit/$t_ref";
	}

	function update_repo_form( $p_event, $p_repo ) {
		if ( 'github' != $p_repo->type ) {
			return;
		}

		if ( isset( $p_repo->info['hub_username'] ) ) {
			$t_hub_username = $p_repo->info['hub_username'];
		}

		if ( isset( $p_repo->info['hub_reponame'] ) ) {
			$t_hub_reponame = $p_repo->info['hub_reponame'];
		}

		if ( isset( $p_repo->info['hub_branch'] ) ) {
			$t_hub_branch = $p_repo->info['hub_branch'];
		} else {
			$t_hub_branch = 'master';
		}
?>
<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo lang_get( 'plugin_SourceGithub_hub_username' ) ?></td>
<td><input name="hub_username" maxlength="250" size="40" value="<?php echo string_attribute( $t_hub_username ) ?>"/></td>
</tr>
<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo lang_get( 'plugin_SourceGithub_hub_reponame' ) ?></td>
<td><input name="hub_reponame" maxlength="250" size="40" value="<?php echo string_attribute( $t_hub_reponame ) ?>"/></td>
</tr>
<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo lang_get( 'plugin_SourceGithub_hub_branch' ) ?></td>
<td><input name="hub_branch" maxlength="250" size="40" value="<?php echo string_attribute( $t_hub_branch ) ?>"/></td>
</tr>
<?php
	}

	function update_repo( $p_event, $p_repo ) {
		if ( 'github' != $p_repo->type ) {
			return;
		}

		$f_hub_username = gpc_get_string( 'hub_username' );
		$f_hub_reponame = gpc_get_string( 'hub_reponame' );
		$f_hub_branch = gpc_get_string( 'hub_branch' );

		$p_repo->info['hub_username'] = $f_hub_username;
		$p_repo->info['hub_reponame'] = $f_hub_reponame;
		$p_repo->info['hub_branch'] = $f_hub_branch;

		return $p_repo;
	}

	function uri_base( $p_repo ) {
		$t_uri_base = 'http://github.com/api/v1/json/' .
			urlencode( $p_repo->info['hub_username'] ) . '/' .
			urlencode( $p_repo->info['hub_reponame'] ) . '/';

		return $t_uri_base;
	}

	function precommit( $p_event ) {
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

		$t_query = "SELECT * FROM $t_repo_table WHERE info LIKE " . db_param(0);
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
		if ( 'github' != $p_repo->type ) {
			return;
		}

		$t_commits = array();

		foreach( $p_data['commits'] as $t_id => $t_details ) {
			$t_commits[] = $t_id;
		}

		$t_result = $this->import_json_commits( $p_repo, $this->uri_base( $p_repo ), $t_commits );

		return array();
	}

	function import_repo( $p_event, $p_repo ) {
		if ( 'github' != $p_repo->type ) {
			return;
		}
		echo '<pre>';

		$t_branch = $p_repo->info['hub_branch'];
		if ( is_blank( $t_branch ) ) {
			$t_branch = 'master';
		}

		$t_result = $this->import_json_commits( $p_repo, $this->uri_base( $p_repo ), $t_branch );

		echo '</pre>';

		return true;
	}

	function import_json_commits( $p_repo, $p_uri_base, $p_commit_id ) {
		if ( is_array( $p_commit_id ) ) {
			$t_parents = $p_commit_id;
		} else {
			$t_parents = array( $p_commit_id );
		}

		while( count( $t_parents ) > 0 ) {
			$t_commit_id = array_shift( $t_parents );

			$t_uri = $p_uri_base . 'commit/' . $t_commit_id;
			$t_json = json_url( $t_uri, 'commit' );

			$t_commit_parents = $this->json_commit_changeset( $p_repo, $t_json );

			$t_parents = array_merge( $t_parents, $t_commit_parents );
		}

		return true;
	}

	function json_commit_changeset( $p_repo, $p_json ) {

		echo "Retrieved $p_json->id ... ";
		if ( !SourceChangeset::exists( $p_repo->id, $p_json->id ) ) {
			$t_user_id = user_get_id_by_email( $p_json->author->email );
			if ( false === $t_user_id ) {
				$t_user_id = user_get_id_by_realname( $p_json->author->name );
			}

			$t_parents = array();
			foreach( $p_json->parents as $t_parent ) {
				$t_parents[] = $t_parent->id;
			}

			$t_changeset = new SourceChangeset( $p_repo->id, $p_json->id, '',
				$p_json->authored_date, $p_json->author->email,
				$p_json->message, $t_user_id );

			foreach( $p_json->added as $t_added ) {
				$t_changeset->files[] = new SourceFile( 0, '', $t_added->filename, 'A' );
			}

			foreach( $p_json->removed as $t_removed ) {
				$t_changeset->files[] = new SourceFile( 0, '', $t_removed->filename, 'D' );
			}

			foreach( $p_json->modified as $t_modified ) {
				$t_changeset->files[] = new SourceFile( 0, '', $t_modified->filename, 'M' );
			}

			$t_changeset->bugs = Source_Parse_Buglinks( $t_changeset->message );
			$t_changeset->save();

			echo "saved.\n";
			return $t_parents;
		} else {
			echo "rejected.\n";
			return array();
		}
	}
}
