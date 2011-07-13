<?php

# Copyright (c) 2010 John Reese
# Licensed under the MIT license

if ( false === include_once( config_get( 'plugin_path' ) . 'Source/MantisSourcePlugin.class.php' ) ) {
	return;
}

require_once( config_get( 'core_path' ) . 'json_api.php' );

class SourceGithubPlugin extends MantisSourcePlugin {
	public function register() {
		$this->name = plugin_lang_get( 'title' );
		$this->description = plugin_lang_get( 'description' );

		$this->version = '0.16';
		$this->requires = array(
			'MantisCore' => '1.2.0',
			'Source' => '0.16',
			'Meta' => '0.1',
		);

		$this->author = 'John Reese';
		$this->contact = 'jreese@leetcode.net';
		$this->url = 'http://leetcode.net';
	}

	public $type = 'github';

	public function show_type() {
		return plugin_lang_get( 'github' );
	}

	public function show_changeset( $p_repo, $p_changeset ) {
		$t_ref = substr( $p_changeset->revision, 0, 8 );
		$t_branch = $p_changeset->branch;

		return "$t_branch $t_ref";
	}

	public function show_file( $p_repo, $p_changeset, $p_file ) {
		return  "$p_file->action - $p_file->filename";
	}

	public function url_repo( $p_repo, $p_changeset=null ) {
		$t_username = $p_repo->info['hub_username'];
		$t_reponame = $p_repo->info['hub_reponame'];
		$t_ref = "";

		if ( !is_null( $p_changeset ) ) {
			$t_ref = "/$p_changeset->revision";
		}

		return "http://github.com/$t_username/$t_reponame/tree$t_ref";
	}

	public function url_changeset( $p_repo, $p_changeset ) {
		$t_username = $p_repo->info['hub_username'];
		$t_reponame = $p_repo->info['hub_reponame'];
		$t_ref = $p_changeset->revision;

		return "http://github.com/$t_username/$t_reponame/commit/$t_ref";
	}

	public function url_file( $p_repo, $p_changeset, $p_file ) {
		$t_username = $p_repo->info['hub_username'];
		$t_reponame = $p_repo->info['hub_reponame'];
		$t_ref = $p_changeset->revision;
		$t_filename = $p_file->filename;

		return "http://github.com/$t_username/$t_reponame/tree/$t_ref/$t_filename";
	}

	public function url_diff( $p_repo, $p_changeset, $p_file ) {
		$t_username = $p_repo->info['hub_username'];
		$t_reponame = $p_repo->info['hub_reponame'];
		$t_ref = $p_changeset->revision;
		$t_filename = $p_file->filename;

		return "http://github.com/$t_username/$t_reponame/commit/$t_ref";
	}

	public function update_repo_form( $p_repo ) {
		$t_hub_username = null;
		$t_hub_reponame = null;
		$t_hub_api_login = null;
		$t_hub_api_token = null;

		if ( isset( $p_repo->info['hub_username'] ) ) {
			$t_hub_username = $p_repo->info['hub_username'];
		}

		if ( isset( $p_repo->info['hub_reponame'] ) ) {
			$t_hub_reponame = $p_repo->info['hub_reponame'];
		}

		if ( isset( $p_repo->info['hub_api_login'] ) ) {
			$t_hub_api_login = $p_repo->info['hub_api_login'];
		}

		if ( isset( $p_repo->info['hub_api_token'] ) ) {
			$t_hub_api_token = $p_repo->info['hub_api_token'];
		}

		if ( isset( $p_repo->info['master_branch'] ) ) {
			$t_master_branch = $p_repo->info['master_branch'];
		} else {
			$t_master_branch = 'master';
		}
?>
<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'hub_username' ) ?></td>
<td><input name="hub_username" maxlength="250" size="40" value="<?php echo string_attribute( $t_hub_username ) ?>"/></td>
</tr>
<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'hub_reponame' ) ?></td>
<td><input name="hub_reponame" maxlength="250" size="40" value="<?php echo string_attribute( $t_hub_reponame ) ?>"/></td>
</tr>
<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'hub_api_login' ) ?></td>
<td><input name="hub_api_login" maxlength="250" size="40" value="<?php echo string_attribute( $t_hub_api_login ) ?>"/></td>
</tr>
<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'hub_api_token' ) ?></td>
<td><input name="hub_api_token" maxlength="250" size="40" value="<?php echo string_attribute( $t_hub_api_token ) ?>"/></td>
</tr>
<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'master_branch' ) ?></td>
<td><input name="master_branch" maxlength="250" size="40" value="<?php echo string_attribute( $t_master_branch ) ?>"/></td>
</tr>
<?php
	}

	public function update_repo( $p_repo ) {
		$f_hub_username = gpc_get_string( 'hub_username' );
		$f_hub_reponame = gpc_get_string( 'hub_reponame' );
		$f_hub_api_login = gpc_get_string( 'hub_api_login' );
		$f_hub_api_token = gpc_get_string( 'hub_api_token' );
		$f_master_branch = gpc_get_string( 'master_branch' );

		if ( !preg_match( '/^[a-zA-Z0-9_\., -]*$/', $f_master_branch ) ) {
			echo 'Invalid parameter: \'Primary Branch\'';
			trigger_error( ERROR_GENERIC, ERROR );
		}

		$p_repo->info['hub_username'] = $f_hub_username;
		$p_repo->info['hub_reponame'] = $f_hub_reponame;
		$p_repo->info['hub_api_login'] = $f_hub_api_login;
		$p_repo->info['hub_api_token'] = $f_hub_api_token;
		$p_repo->info['master_branch'] = $f_master_branch;

		return $p_repo;
	}

	private function api_uri( $p_repo, $p_path ) {
		$t_uri = 'http://github.com/api/v2/json/' . $p_path;

		if ( !is_blank( $p_repo->info['hub_api_token'] ) ) {
			$t_token = $p_repo->info['hub_api_token'];
			$t_login = $p_repo->info['hub_username'];

			if ( !is_blank( $p_repo->info['hub_api_login'] ) ) {
				$t_login = $p_repo->info['hub_api_login'];
			}

			$t_uri .= '?login=' . $t_login . '&token=' . $t_token;
		}

		return $t_uri;
	}

	public function precommit() {
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

	public function commit( $p_repo, $p_data ) {
		$t_commits = array();

		foreach( $p_data['commits'] as $t_commit ) {
			$t_commits[] = $t_commit['id'];
		}

		$t_branch = $p_data['ref_name'];

		return $this->import_commits( $p_repo, $t_commits, $t_branch );
	}

	public function import_full( $p_repo ) {
		echo '<pre>';

		$t_branch = $p_repo->info['master_branch'];
		if ( is_blank( $t_branch ) ) {
			$t_branch = 'master';
		}

		$t_branches = map( 'trim', explode( ',', $t_branch ) );
		$t_changesets = array();

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

			$t_changesets = array_merge( $t_changesets, $this->import_commits( $p_repo, $t_commits, $t_branch ) );
		}

		echo '</pre>';

		return $t_changesets;
	}

	public function import_latest( $p_repo ) {
		return $this->import_full( $p_repo );
	}

	public function import_commits( $p_repo, $p_commit_ids, $p_branch='' ) {
		static $s_parents = array();
		static $s_counter = 0;

		$t_username = $p_repo->info['hub_username'];
		$t_reponame = $p_repo->info['hub_reponame'];

		if ( is_array( $p_commit_ids ) ) {
			$s_parents = array_merge( $s_parents, $p_commit_ids );
		} else {
			$s_parents[] = $p_commit_ids;
		}

		$t_changesets = array();

		while( count( $s_parents ) > 0 && $s_counter < 200 ) {
			$t_commit_id = array_shift( $s_parents );

			echo "Retrieving $t_commit_id ... ";
			$t_uri = $this->api_uri( $p_repo, "commits/show/{$t_username}/{$t_reponame}/{$t_commit_id}" );
			$t_json = json_url( $t_uri, 'commit' );

			if ( false === $t_json || is_null( $t_json ) ) {
				echo "failed.\n";
				continue;
			}

			list( $t_changeset, $t_commit_parents ) = $this->json_commit_changeset( $p_repo, $t_json, $p_branch );
			if ( $t_changeset ) {
				$t_changesets[] = $t_changeset;
			}

			$s_parents = array_merge( $s_parents, $t_commit_parents );
		}

		$s_counter = 0;
		return $t_changesets;
	}

	private function json_commit_changeset( $p_repo, $p_json, $p_branch='' ) {

		echo "processing $p_json->id ... ";
		if ( !SourceChangeset::exists( $p_repo->id, $p_json->id ) ) {
			$t_parents = array();
			foreach( $p_json->parents as $t_parent ) {
				$t_parents[] = $t_parent->id;
			}

			$t_changeset = new SourceChangeset( $p_repo->id, $p_json->id, $p_branch,
				$p_json->authored_date, $p_json->author->name, $p_json->message );

			if ( count( $p_json->parents ) > 0 ) {
				$t_parent = $p_json->parents[0];
				$t_changeset->parent = $t_parent->id;
			}

			$t_changeset->author_email = $p_json->author->email;
			$t_changeset->committer = $p_json->committer->name;
			$t_changeset->committer_email = $p_json->committer->email;

			if ( isset( $p_json->added ) ) {
				foreach( $p_json->added as $t_added ) {
					$t_changeset->files[] = new SourceFile( 0, '', $t_added, 'add' );
				}
			}

			if ( isset( $p_json->removed ) ) {
				foreach( $p_json->removed as $t_removed ) {
					$t_changeset->files[] = new SourceFile( 0, '', $t_removed, 'rm' );
				}
			}

			if ( isset( $p_json->modified ) ) {
				foreach( $p_json->modified as $t_modified ) {
					$t_changeset->files[] = new SourceFile( 0, '', $t_modified->filename, 'mod' );
				}
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
