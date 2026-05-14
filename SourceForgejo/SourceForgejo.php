<?php

# Copyright (c) 2025 Bart van Leeuwen
# Licensed under the MIT license

/** @noinspection SqlResolve, PhpIncludeInspection */

if ( false === include_once( config_get( 'plugin_path' ) . 'Source/MantisSourceGitBasePlugin.class.php' ) ) {
	return;
}

require_once( config_get( 'core_path' ) . 'url_api.php' );
require_once( config_get( 'core_path' ) . 'json_api.php' );

class SourceForgejoPlugin extends MantisSourceGitBasePlugin {

	const PLUGIN_VERSION = '1.0.0';
	const FRAMEWORK_VERSION_REQUIRED = '2.5.0';

	const ERROR_FORGEJO_API = 'forgejo_api';

	public $type = 'forgejo';

	/**
	 * Forgejo API version, used to build the API URI
	 * @see api_uri()
	 */
	const API_VERSION = 'v1';

	public $linkPullRequest = 'merge_requests/%s';

	public function register() {
		parent::register();

		$this->author = 'Bart van Leeuwen';
		$this->contact = 'bart@netage.nl';
	}

	public function show_type() {
		return plugin_lang_get( 'Forgejo' );
	}

	public function show_changeset( $p_repo, $p_changeset ) {
		$t_ref = substr( $p_changeset->revision, 0, 8 );
		$t_branch = $p_changeset->branch;

		return "$t_branch $t_ref";
	}

	public function show_file( $p_repo, $p_changeset, $p_file ) {
		return  "$p_file->action - $p_file->filename";
	}

	public function url_base( $p_repo ) {
		$t_root = rtrim($p_repo->info['fj_root'], '/');
		return $t_root . '/' . $p_repo->info['fj_ownerid'] . '/' . $p_repo->info['fj_repoid'];
	}

	public function url_repo( $p_repo, $p_changeset=null ) {
		if( empty( $p_repo->info ) ) {
			return '';
		}
		$t_ref = '';
		if ( !is_null( $p_changeset ) ) {
			$t_ref = "/src/branch/$p_changeset->revision";
		}
		return $this->url_base( $p_repo ) . $t_ref;
	}

	public function url_changeset( $p_repo, $p_changeset ) {
		return $this->url_base( $p_repo ) . '/commit/' . $p_changeset->revision;
	}

	public function url_file( $p_repo, $p_changeset, $p_file ) {
		# Can't link to a deleted file
		if( $p_file->action == SourceFile::DELETED ) {
			return '';
		}
		$t_ref = $p_changeset->revision;
		$t_filename = $p_file->getFilename();
		return $this->url_base( $p_repo ) . "/src/branch/$t_ref/$t_filename";
	}

	public function url_diff( $p_repo, $p_changeset, $p_file ) {
		return $this->url_changeset( $p_repo, $p_changeset );
	}

	public function update_repo_form( $p_repo ) {
		$t_fj_root = null;
		$t_fj_ownerid = null;
		$t_fj_repoid = null;
		$t_fj_app_secret = null;

		if ( isset( $p_repo->info['fj_root'] ) ) {
			$t_fj_root = $p_repo->info['fj_root'];
		}
		if ( isset( $p_repo->info['fj_ownerid'] ) ) {
			$t_fj_ownerid = $p_repo->info['fj_ownerid'];
		}
		if ( isset( $p_repo->info['fj_repoid'] ) ) {
			$t_fj_repoid = $p_repo->info['fj_repoid'];
		}
		if ( isset( $p_repo->info['fj_app_secret'] ) ) {
			$t_fj_app_secret = $p_repo->info['fj_app_secret'];
		}
		if ( isset( $p_repo->info['master_branch'] ) ) {
			$t_master_branch = $p_repo->info['master_branch'];
		} else {
			$t_master_branch = $this->get_default_primary_branches();
		}
?>
<tr>
	<th class="category">
		<label for="fj_root">
			<?php echo plugin_lang_get( 'fj_root' ) ?>
		</label>
	</th>
	<td>
		<input id="fj_root" name="fj_root"
			   type="text" maxlength="250" size="40"
			   value="<?php echo string_attribute( $t_fj_root ) ?>"
		/>
<?php if( !filter_var( $t_fj_root, FILTER_VALIDATE_URL ) ) { ?>
		<i class="fa fa-warning ace-icon fa-lg red"></i>
<?php } ?>
	</td>
</tr>
<tr>
	<th class="category">
		<label for="fj_ownerid">
			<?php echo plugin_lang_get( 'fj_ownerid' ) ?>
		</label>
	</th>
	<td>
		<input id="fj_ownerid" name="fj_ownerid"
			   type="text" maxlength="250" size="40"
			   value="<?php echo string_attribute( $t_fj_ownerid ) ?>"
		/>
	</td>
</tr>
<tr>
	<th class="category">
		<label for="fj_repoid">
			<?php echo plugin_lang_get( 'fj_repoid' ) ?>
		</label>
	</th>
	<td>
		<input id="fj_repoid" name="fj_repoid"
			   type="text" maxlength="250" size="40"
			   value="<?php echo string_attribute( $t_fj_repoid ) ?>"
		/>
	</td>
</tr>
<tr>
	<th class="category">
		<label for="fj_app_secret">
			<?php echo plugin_lang_get( 'fj_app_secret' ) ?>
		</label>
	</th>
	<td>
		<input id="fj_app_secret" name="fj_app_secret"
			   type="password" maxlength="250" size="40"
			   value="<?php echo string_attribute( $t_fj_app_secret ) ?>"
		/>
	</td>
</tr>
<tr>
	<th class="category">
		<label for="master_branch"><?php echo plugin_lang_get( 'master_branch' ) ?></label>
	</th>
	<td>
		<input id="master_branch" name="master_branch"
			   type="text" maxlength="250" size="40"
			   value="<?php echo string_attribute( $t_master_branch ) ?>"
		/>
	</td>
</tr>
<?php
	}

	public function update_repo( $p_repo ) {
		$f_fj_root = gpc_get_string( 'fj_root' );
		$f_fj_ownerid = gpc_get_string( 'fj_ownerid' );
		$f_fj_repoid = gpc_get_string( 'fj_repoid' );
		$f_fj_app_secret = gpc_get_string( 'fj_app_secret' );

		$p_repo->info['fj_root'] = $f_fj_root;
		$p_repo->info['fj_ownerid'] = $f_fj_ownerid;
		$p_repo->info['fj_repoid'] = $f_fj_repoid;
		$p_repo->info['fj_app_secret'] = $f_fj_app_secret;

		$f_master_branch = gpc_get_string( 'master_branch' );
		$this->validate_branch_list( $f_master_branch );

		# Update other fields
		$p_repo->info['master_branch'] = $f_master_branch;

		return $p_repo;
	}

	private function api_uri( $p_repo, $p_path ) {
		$t_root = rtrim($p_repo->info['fj_root'], '/');
		$t_uri = $t_root . '/api/' . self::API_VERSION . '/' . $p_path;

		return $t_uri;
	}

	public function precommit() {
		$f_payload = file_get_contents( "php://input" );
		if( is_null( $f_payload ) ) {
			return null;
		}

		$t_data = json_decode( $f_payload, true );
		if( is_null( $t_data ) || !isset( $t_data['repository'] ) || !isset( $t_data['repository']['html_url'] )) {
			return null;
		}

		$t_repourl = $t_data['repository']['html_url'];
		$t_repo_table = plugin_table( 'repository', 'Source' );

		$t_query = "SELECT * FROM $t_repo_table WHERE type = " . db_param();
		$t_result = db_query( $t_query, array( $this->type ) );

		if ( db_num_rows( $t_result ) < 1 ) {
			return null;
		}
		while ( $t_row = db_fetch_array( $t_result ) ) {
			$t_repo = new SourceRepo( $t_row['type'], $t_row['name'], $t_row['url'], $t_row['info'] );
			$t_repo->id = $t_row['id'];
			if ( $this->url_base( $t_repo ) == $t_repourl ) {
				return array( 'repo' => $t_repo, 'data' => $t_data );
			}
		}
		return null;
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
			$t_branch = $this->get_default_primary_branches();
		}

		# if we're not allowed everything, populate an array of what we are allowed
		if( $t_branch != '*' ) {
			$t_branches_allowed = array_map( 'trim', explode( ',', $t_branch ) );
		}

		# Always pull back full list of repos
		$t_ownerid = $p_repo->info['fj_ownerid'];
		$t_repoid = $p_repo->info['fj_repoid'];
		$t_uri = $this->api_uri( $p_repo, "repos/$t_ownerid/$t_repoid/branches" );
		echo $t_uri;
		$t_member = null;
		$t_json = $this->api_json_url( $p_repo,$t_uri );
		if( $t_json === null ) {
			echo "Could not retrieve data from Forgejo at '$t_uri'. Make sure your ";
			print_link(
				plugin_page( 'repo_update_page', null, 'Source' )
				. "&id=$p_repo->id",
				'repository settings'
			);
			echo " are correct.";
			echo '</pre>';
			return array();
		}

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
				' ORDER BY timestamp';
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
		$t_ownerid = $p_repo->info['fj_ownerid'];
		$t_repoid = $p_repo->info['fj_repoid'];

		if ( is_array( $p_commit_ids ) ) {
			$s_parents = array_merge( $s_parents, $p_commit_ids );
		} else {
			$s_parents[] = $p_commit_ids;
		}

		$t_changesets = array();

		while( count( $s_parents ) > 0 && $s_counter < 200 ) {
			$t_commit_id = array_shift( $s_parents );
			echo "Retrieving $t_commit_id ... <br/>";
			$t_uri = $this->api_uri( $p_repo, "repos/$t_ownerid/$t_repoid/git/commits/$t_commit_id" );
			$t_member = null;
			$t_json = json_url( $t_uri, $t_member );
			if ( false === $t_json || is_null( $t_json ) ) {
				# Some error occurred retrieving the commit
				echo "failed.\n";
				continue;
			} else if ( !property_exists( $t_json, 'sha' ) ) {
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
		echo "processing $p_json->sha ... ";
		if ( !SourceChangeset::exists( $p_repo->id, $p_json->sha ) ) {
			$t_parents = array();
			foreach( $p_json->parents as $t_parent ) {
				$t_parents[] = $t_parent->sha;
			}

			$t_changeset = new SourceChangeset(
				$p_repo->id,
				$p_json->sha,
				$p_branch,
				$p_json->commit->author->date,
				$p_json->commit->author->username ?? $p_json->commit->author->name,
				$p_json->commit->message
			);

			if ( count( $p_json->parents ) > 0 ) {
				$t_parent = $p_json->parents[0];
				$t_changeset->parent = $t_parent->sha;
			}

			$t_changeset->author_email = $p_json->author->email ?? "noempty@email.com";
			$t_changeset->committer = $p_json->commit->committer->name;
			$t_changeset->committer_email = $p_json->commit->committer->email;
			$t_changeset->save();

			echo "saved.\n";
			return array( $t_changeset, $t_parents );
		} else {
			echo "already exists.\n";
			return array( null, array() );
		}
	}

	public function url_get( $p_repo, $p_url ) {
		$t_curl_header = array();
		$t_curl_header[] = "Authorization: token ". $p_repo->info['fj_app_secret'];
		# Use the PHP cURL extension
		if( function_exists( 'curl_init' ) ) {
			$t_curl = curl_init( $p_url );
			# cURL options
			$t_curl_opt[CURLOPT_HTTPHEADER] = $t_curl_header;
			$t_curl_opt[CURLOPT_RETURNTRANSFER] = true;

			$t_vers                        = curl_version();
			$t_curl_opt[CURLOPT_USERAGENT] =
				'mantisbt/' . MANTIS_VERSION . ' php-curl/' . $t_vers['version'];

			# Set the options
			curl_setopt_array( $t_curl, $t_curl_opt );

			# Retrieve data
			$t_data = curl_exec( $t_curl );
			curl_close( $t_curl );
			error_log(print_r($t_data,true));
			if( $t_data !== false ) {
				return $t_data;
			}
		}
		
	}

	private function api_json_url( $p_repo, $p_url ) {
		$t_data = $this->url_get( $p_repo, $p_url );
		$t_json = json_decode( utf8_encode( $t_data ) );
		if( json_last_error() != JSON_ERROR_NONE ) {
			error_parameters( $t_data );
			plugin_error( self::ERROR_FORGEJO_API );
		}
		return $t_json;
	}

}
