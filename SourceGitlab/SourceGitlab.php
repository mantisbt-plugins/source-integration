<?php

# Copyright (c) 2014 Johannes Goehr
# Copyright (c) 2014 Bob Clough
# Licensed under the MIT license

if ( false === include_once( config_get( 'plugin_path' ) . 'Source/MantisSourceGitBasePlugin.class.php' ) ) {
	return;
}

require_once( config_get( 'core_path' ) . 'url_api.php' );
require_once( config_get( 'core_path' ) . 'json_api.php' );

class SourceGitlabPlugin extends MantisSourceGitBasePlugin {

	const PLUGIN_VERSION = '1.1.0';
	const FRAMEWORK_VERSION_REQUIRED = '1.5.0';

	public function register() {
		$this->name = plugin_lang_get( 'title' );
		$this->description = plugin_lang_get( 'description' );

		$this->version = self::PLUGIN_VERSION;
		$this->requires = array(
			'MantisCore' => self::MANTIS_VERSION,
			'Source' => self::FRAMEWORK_VERSION_REQUIRED,
		);

		$this->author = 'Johannes Goehr';
		$this->contact = 'johannes.goehr@mobilexag.de';
		$this->url = 'https://github.com/mantisbt-plugins/source-integration/';
	}

	public $type = 'gitlab';

	public function show_type() {
		return plugin_lang_get( 'gitlab' );
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
		$t_root = $p_repo->info['hub_root'];
		$t_reponame = $p_repo->info['hub_reponame'];
		$t_ref = "";

		if ( !is_null( $p_changeset ) ) {
			$t_ref = "/$p_changeset->revision";
		}
		if ( !is_null( $t_ref ) ) {
			return "$t_root/$t_reponame/";
		}
		return "$t_root/$t_reponame/tree/$t_ref";
	}

	public function url_changeset( $p_repo, $p_changeset ) {
		$t_root = $p_repo->info['hub_root'];
		$t_reponame = $p_repo->info['hub_reponame'];
		$t_ref = $p_changeset->revision;

		return "$t_root/$t_reponame/commit/$t_ref";
	}

	public function url_file( $p_repo, $p_changeset, $p_file ) {
		$t_root = $p_repo->info['hub_root'];
		$t_reponame = $p_repo->info['hub_reponame'];
		$t_ref = $p_changeset->revision;
		$t_filename = $p_file->filename;

		return "$t_root/$t_reponame/blob/$t_ref/$t_filename";
	}

	public function url_diff( $p_repo, $p_changeset, $p_file ) {
		$t_root = $p_repo->info['hub_root'];
		$t_reponame = $p_repo->info['hub_reponame'];
		$t_ref = $p_changeset->revision;
		$t_filename = $p_file->filename;

		return "t_root/$t_reponame/commit/$t_ref?view=parallel";
	}

public function update_repo_form( $p_repo ) {
		$t_hub_root       = null;
		$t_hub_repoid     = null;
		$t_hub_reponame   = null;
		$t_hub_app_secret = null;

		if ( isset( $p_repo->info['hub_root'] ) ) {
			$t_hub_root = $p_repo->info['hub_root'];
		}
		if ( isset( $p_repo->info['hub_repoid'] ) ) {
			$t_hub_repoid = $p_repo->info['hub_repoid'];
		}
		if ( isset( $p_repo->info['hub_reponame'] ) ) {
			$t_hub_reponame = $p_repo->info['hub_reponame'];
		}
		if ( isset( $p_repo->info['hub_app_secret'] ) ) {
			$t_hub_app_secret = $p_repo->info['hub_app_secret'];
		}
		if ( isset( $p_repo->info['master_branch'] ) ) {
			$t_master_branch = $p_repo->info['master_branch'];
		} else {
			$t_master_branch = 'master';
		}
?>

<div class="field-container">
	<label><span><?php echo plugin_lang_get( 'hub_root' ) ?></span></label>
	<span class="input">
		<input name="hub_root" maxlength="250" size="40" value="<?php echo string_attribute( $t_hub_root ) ?>"/>
	</span>
	<span class="label-style"></span>
</div>

<div class="field-container">
	<label><span><?php echo plugin_lang_get( 'hub_repoid' ) ?></span></label>
	<span class="input">
		<input name="hub_repoid" maxlength="250" size="40" value="<?php echo string_attribute( $t_hub_repoid ) ?>"/>
	</span>
	<span class="label-style"></span>
</div>

<div class="field-container">
	<label><span><?php echo plugin_lang_get( 'hub_reponame' ) ?></span></label>
	<span class="input">
		<input name="hub_reponame" maxlength="250" size="40" value="<?php echo string_attribute( $t_hub_reponame ) ?>"/>
	</span>
	<span class="label-style"></span>
</div>

<div class="field-container">
	<label><span><?php echo plugin_lang_get( 'hub_app_secret' ) ?></span></label>
	<span class="input">
		<input name="hub_app_secret" maxlength="250" size="40" value="<?php echo string_attribute( $t_hub_app_secret ) ?>"/>
	</span>
	<span class="label-style"></span>
</div>

<div class="field-container">
	<label><span><?php echo plugin_lang_get( 'master_branch' ) ?></span></label>
	<span class="input">
		<input name="master_branch" maxlength="250" size="40" value="<?php echo string_attribute( $t_master_branch ) ?>"/>
	</span>
	<span class="label-style"></span>
</div>

<?php
	}

	public function update_repo( $p_repo ) {
		$f_hub_root = gpc_get_string( 'hub_root' );
		$f_hub_repoid = gpc_get_string( 'hub_repoid' );
		$f_hub_reponame = gpc_get_string( 'hub_reponame' );
		$f_hub_app_secret = gpc_get_string( 'hub_app_secret' );

		# Update info required for getting the repoid
		$p_repo->info['hub_root'] = $f_hub_root;
		$p_repo->info['hub_reponame'] = $f_hub_reponame;
		$p_repo->info['hub_app_secret'] = $f_hub_app_secret;

		# Getting the repoid from reponame
		if( !is_numeric( $f_hub_repoid ) && !empty( $f_hub_reponame ) ) {
			$t_hub_reponame_enc = urlencode( $f_hub_reponame );
			$t_uri = $this->api_uri( $p_repo, "projects/$t_hub_reponame_enc" );
			$t_member = null;
			$t_json = json_url( $t_uri, $t_member );

			$f_hub_repoid = 'Repository Name is invalid';
			if( !is_null( $t_json ) ) {
				if ( property_exists( $t_json, 'id' ) ) {
					$f_hub_repoid = (string)$t_json ->id;
				}
			}
		}

		$f_master_branch = gpc_get_string( 'master_branch' );
		$this->validate_branch_list( $f_master_branch );

		# Update other fields
		$p_repo->info['hub_repoid'] = $f_hub_repoid;
		$p_repo->info['master_branch'] = $f_master_branch;

		return $p_repo;
	}

	private function api_uri( $p_repo, $p_path ) {
		$t_root = $p_repo->info['hub_root'];
		$t_uri = $t_root . '/api/v3/' . $p_path;

		if( isset( $p_repo->info['hub_app_secret'] ) ) {
			$t_access_token = $p_repo->info['hub_app_secret'];
			if ( !is_blank( $t_access_token ) ) {
				$t_uri .= '?private_token=' . $t_access_token;
			}
		}

		return $t_uri;
	}

	public function precommit() {
		$f_payload = file_get_contents( "php://input" );
		if( is_null( $f_payload ) ) {
			return;
		}

		$t_data = json_decode( $f_payload, true );
		if( is_null( $t_data ) ) {
			return;
		}

		$t_repoid = $t_data['project_id'];
		$t_repo_table = plugin_table( 'repository', 'Source' );

		$t_query = "SELECT * FROM $t_repo_table WHERE info LIKE " . db_param();
		$t_result = db_query( $t_query, array( '%' . $t_repoid . '%' ) );

		if ( db_num_rows( $t_result ) < 1 ) {
			return;
		}
		while ( $t_row = db_fetch_array( $t_result ) ) {
			$t_repo = new SourceRepo( $t_row['type'], $t_row['name'], $t_row['url'], $t_row['info'] );
			$t_repo->id = $t_row['id'];
			if ( $t_repo->info['hub_repoid'] == $t_repoid ) {
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

		# extract branch name 'refs/heads/issue/branch-description' => ['refs', 'heads', 'issue/branch-description']
		$t_refData = explode( '/', $p_data['ref'], 3 );
		$t_branch = $t_refData[2];

		return $this->import_commits( $p_repo, $t_commits, $t_branch );
	}

	public function import_full( $p_repo ) {
		echo '<pre>';

		$t_branch = $p_repo->info['master_branch'];
		if ( is_blank( $t_branch ) ) {
			$t_branch = 'master';
		}

		# if we're not allowed everything, populate an array of what we are allowed
		if( $t_branch != '*' ) {
			$t_branches_allowed = array_map( 'trim', explode( ',', $t_branch ) );
		}

		# Always pull back full list of repos
		$t_repoid = $p_repo->info['hub_repoid'];
		$t_uri = $this->api_uri( $p_repo, "projects/$t_repoid/repository/branches" );

		$t_member = null;
		$t_json = json_url( $t_uri, $t_member );
		$t_branches = array();
		foreach( $t_json as $t_branch ) {
			if( empty( $t_branches_allowed ) || in_array( $t_branch->name, $t_branches_allowed ) ) {
				$t_branches[] = $t_branch;
			}
		}

		$t_changesets = array();

		$t_changeset_table = plugin_table( 'changeset', 'Source' );

		foreach( $t_branches as $t_branch ) {
			$t_query = "SELECT parent FROM $t_changeset_table
				WHERE repo_id=" . db_param() . ' AND branch=' . db_param() .
				' ORDER BY timestamp ASC';
			$t_result = db_query( $t_query, array( $p_repo->id, $t_branch->name ), 1 );

			$t_commits = array( $t_branch->commit->id );
			if ( db_num_rows( $t_result ) > 0 ) {
				$t_parent = db_result( $t_result );
				echo "Oldest '$t_branch->name' branch parent: '$t_parent'\n";

				if ( !empty( $t_parent ) ) {
					$t_commits[] = $t_parent;
					echo "Parents not empty";
				}
				echo "Parents  empty";
			}

			$t_changesets = array_merge( $t_changesets, $this->import_commits( $p_repo, $t_commits, $t_branch->name ) );
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
		$t_repoid = $p_repo->info['hub_repoid'];

		if ( is_array( $p_commit_ids ) ) {
			$s_parents = array_merge( $s_parents, $p_commit_ids );
		} else {
			$s_parents[] = $p_commit_ids;
		}

		$t_changesets = array();

		while( count( $s_parents ) > 0 && $s_counter < 200 ) {
			$t_commit_id = array_shift( $s_parents );
			echo "Retrieving $t_commit_id ... <br/>";
			$t_uri = $this->api_uri( $p_repo, "projects/$t_repoid/repository/commits/$t_commit_id" );
			$t_member = null;
			$t_json = json_url( $t_uri, $t_member );
			if ( false === $t_json || is_null( $t_json ) ) {
				# Some error occured retrieving the commit
				echo "failed.\n";
				continue;
			} else if ( !property_exists( $t_json, 'id' ) ) {
				echo "failed ($t_json->message).\n";
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
			foreach( $p_json->parent_ids as $t_parent ) {
				$t_parents[] = $t_parent;
			}
			# Message will be replaced by title in gitlab version earlier than 7.2
			$t_message = ( !property_exists( $p_json, 'message' ) )
				? $p_json->title
				: $p_json->message;
			$t_changeset = new SourceChangeset(
				$p_repo->id,
				$p_json->id,
				$p_branch,
				date( 'Y-m-d H:i:s', strtotime( $p_json->created_at ) ),
				$p_json->author_name,
				$t_message
			);

			if ( count( $p_json->parents ) > 0 ) {
				$t_parent = $p_json->parents[0];
				$t_changeset->parent = $t_parent->id;
			}

			$t_changeset->author_email = $p_json->author_email;
			$t_changeset->save();

			echo "saved.\n";
			return array( $t_changeset, $t_parents );
		} else {
			echo "already exists.\n";
			return array( null, array() );
		}
	}



	public static function url_post( $p_url, $p_post_data ) {
		$t_post_data = http_build_query( $p_post_data );

		# Use the PHP cURL extension
		if( function_exists( 'curl_init' ) ) {
			$t_curl = curl_init( $p_url );
			curl_setopt( $t_curl, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $t_curl, CURLOPT_POST, true );
			curl_setopt( $t_curl, CURLOPT_POSTFIELDS, $t_post_data );

			$t_data = curl_exec( $t_curl );
			curl_close( $t_curl );

			return $t_data;
		} else {
			# Last resort system call
			$t_url = escapeshellarg( $p_url );
			$t_post_data = escapeshellarg( $t_post_data );
			return shell_exec( 'curl ' . $t_url . ' -d ' . $t_post_data );
		}
	}

}
