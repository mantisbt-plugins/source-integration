<?php

# Copyright (c) 2012 John Reese
# Licensed under the MIT license

if ( false === include_once( config_get( 'plugin_path' ) . 'Source/MantisSourceGitBasePlugin.class.php' ) ) {
	return;
}

require_once( config_get( 'core_path' ) . 'json_api.php' );

class SourceGithubPlugin extends MantisSourceGitBasePlugin {

	const PLUGIN_VERSION = '2.0.2';
	const FRAMEWORK_VERSION_REQUIRED = '2.0.0';

	public $linkPullRequest = '/pull/%s';

	public function register() {
		$this->name = plugin_lang_get( 'title' );
		$this->description = plugin_lang_get( 'description' );

		$this->version = self::PLUGIN_VERSION;
		$this->requires = array(
			'MantisCore' => self::MANTIS_VERSION,
			'Source' => self::FRAMEWORK_VERSION_REQUIRED,
		);

		$this->author = 'John Reese';
		$this->contact = 'john@noswap.com';
		$this->url = 'https://github.com/mantisbt-plugins/source-integration/';
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
		if( empty( $p_repo->info ) ) {
			return '';
		}
		$t_username = $p_repo->info['hub_username'];
		$t_reponame = $p_repo->info['hub_reponame'];
		$t_ref = "";

		if ( !is_null( $p_changeset ) ) {
			$t_ref = "/tree/$p_changeset->revision";
		}

		return "http://github.com/$t_username/$t_reponame$t_ref";
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
		$t_hub_app_client_id = null;
		$t_hub_app_secret = null;
		$t_hub_app_access_token = null;
		$t_hub_webhook_secret = null;

		if ( isset( $p_repo->info['hub_username'] ) ) {
			$t_hub_username = $p_repo->info['hub_username'];
		}

		if ( isset( $p_repo->info['hub_reponame'] ) ) {
			$t_hub_reponame = $p_repo->info['hub_reponame'];
		}

		if ( isset( $p_repo->info['hub_app_client_id'] ) ) {
			$t_hub_app_client_id = $p_repo->info['hub_app_client_id'];
		}

		if ( isset( $p_repo->info['hub_app_secret'] ) ) {
			$t_hub_app_secret = $p_repo->info['hub_app_secret'];
		}

		if ( isset( $p_repo->info['hub_app_access_token'] ) ) {
			$t_hub_app_access_token = $p_repo->info['hub_app_access_token'];
		}

		if ( isset( $p_repo->info['hub_webhook_secret'] ) ) {
			$t_hub_webhook_secret = $p_repo->info['hub_webhook_secret'];
		}

		if ( isset( $p_repo->info['master_branch'] ) ) {
			$t_master_branch = $p_repo->info['master_branch'];
		} else {
			$t_master_branch = 'master';
		}
?>

<tr>
	<td class="category"><?php echo plugin_lang_get( 'hub_username' ) ?></td>
	<td>
		<input type="text" name="hub_username" maxlength="250" size="40" value="<?php echo string_attribute( $t_hub_username ) ?>"/>
	</td>
</tr>

<tr>
	<td class="category"><?php echo plugin_lang_get( 'hub_reponame' ) ?></td>
	<td>
		<input type="text" name="hub_reponame" maxlength="250" size="40" value="<?php echo string_attribute( $t_hub_reponame ) ?>"/>
	</td>
</tr>


<tr>
	<td class="category"><?php echo plugin_lang_get( 'hub_app_client_id' ) ?></td>
	<td>
		<input type="text" name="hub_app_client_id" maxlength="250" size="40" value="<?php echo string_attribute( $t_hub_app_client_id ) ?>"/>
	</td>
</tr>

<tr>
	<td class="category"><?php echo plugin_lang_get( 'hub_app_secret' ) ?></td>
	<td>
		<input type="text" name="hub_app_secret" maxlength="250" size="40" value="<?php echo string_attribute( $t_hub_app_secret ) ?>"/>
	</td>
</tr>

<tr>
	<td class="category"><?php echo plugin_lang_get( 'hub_app_access_token' ) ?></td>
	<td>
		<?php
		if( empty( $t_hub_app_client_id ) || empty( $t_hub_app_secret ) ) {
			echo plugin_lang_get( 'hub_app_client_id_secret_missing' );
		} elseif( empty( $t_hub_app_access_token ) ) {
			print_link( $this->oauth_authorize_uri( $p_repo ), plugin_lang_get( 'hub_app_authorize' ) );
		} else {
			echo plugin_lang_get( 'hub_app_authorized' );
			# @TODO This would be better with an AJAX, but this will do for now
			?>
			<input type="submit" class="btn btn-xs btn-primary btn-white btn-round" name="revoke" value="<?php echo plugin_lang_get( 'hub_app_revoke' ) ?>"/>
			<?php
		}
		?>
	</td>
</tr>

<tr>
	<td class="category"><?php echo plugin_lang_get( 'hub_webhook_secret' ) ?></td>
	<td>
		<input type="text" name="hub_webhook_secret" maxlength="250" size="40" value="<?php echo string_attribute( $t_hub_webhook_secret ) ?>"/>
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
		# Revoking previously authorized Github access token
		if( gpc_isset( 'revoke' ) ) {
			unset( $p_repo->info['hub_app_access_token'] );
			return $p_repo;
		}

		$f_hub_username = gpc_get_string( 'hub_username' );
		$f_hub_reponame = gpc_get_string( 'hub_reponame' );
		$f_hub_app_client_id = gpc_get_string( 'hub_app_client_id' );
		$f_hub_app_secret = gpc_get_string( 'hub_app_secret' );
		$f_hub_webhook_secret = gpc_get_string( 'hub_webhook_secret' );
		$f_master_branch = gpc_get_string( 'master_branch' );

		$this->validate_branch_list( $f_master_branch );

		$p_repo->info['hub_username'] = $f_hub_username;
		$p_repo->info['hub_reponame'] = $f_hub_reponame;
		$p_repo->info['hub_app_client_id'] = $f_hub_app_client_id;
		$p_repo->info['hub_app_secret'] = $f_hub_app_secret;
		$p_repo->info['hub_webhook_secret'] = $f_hub_webhook_secret;
		$p_repo->info['master_branch'] = $f_master_branch;

		return $p_repo;
	}

	private function api_uri( $p_repo, $p_path ) {
		$t_uri = 'https://api.github.com/' . $p_path;

		if( isset( $p_repo->info['hub_app_access_token'] ) ) {
			$t_access_token = $p_repo->info['hub_app_access_token'];
			if ( !is_blank( $t_access_token ) ) {
				$t_uri .= '?access_token=' . $t_access_token;
			}
		}

		return $t_uri;
	}

	private function api_json_url( $p_repo, $p_url, $p_member = null ) {
		static $t_start_time;
		if ( $t_start_time === null ) {
			$t_start_time = microtime( true );
		} else if ( ( microtime( true ) - $t_start_time ) >= 3600.0 ) {
			$t_start_time = microtime( true );
		}

		$t_uri = $this->api_uri( $p_repo, 'rate_limit' );
		$t_json = json_url( $t_uri, 'rate' );

		if ( false !== $t_json && !is_null( $t_json ) ) {
			if ( $t_json->remaining <= 0 ) {
				// do we need to do something here?
			} else if ( $t_json->remaining < ( $t_json->limit / 2 ) ) {
				$t_time_remaining = 3600.0 - ( microtime( true ) - $t_start_time );
				$t_sleep_time = ( $t_time_remaining / $t_json->remaining ) * 1000000;
				usleep( $t_sleep_time );
			}
		}

		return json_url( $p_url, $p_member );
	}

	public function precommit() {
		# Legacy GitHub Service sends the payload via eponymous form variable
		$f_payload = gpc_get_string( 'payload', null );
		if ( is_null( $f_payload ) ) {
			# If empty, retrieve the webhook's payload from the body
			$f_payload = file_get_contents( 'php://input' );
			if ( is_null( $f_payload ) ) {
				return;
			}
		}

		if ( false === stripos( $f_payload, 'github.com' ) ) {
			return;
		}

		$t_data = json_decode( $f_payload, true );
		$t_reponame = $t_data['repository']['name'];

		$t_repo_table = plugin_table( 'repository', 'Source' );

		$t_query = "SELECT * FROM $t_repo_table WHERE info LIKE " . db_param();
		$t_result = db_query( $t_query, array( '%' . $t_reponame . '%' ) );

		if ( db_num_rows( $t_result ) < 1 ) {
			return;
		}

		while ( $t_row = db_fetch_array( $t_result ) ) {
			$t_repo = new SourceRepo( $t_row['type'], $t_row['name'], $t_row['url'], $t_row['info'] );
			$t_repo->id = $t_row['id'];

			if ( $t_repo->info['hub_reponame'] == $t_reponame ) {
				# Retrieve the payload's signature from the request headers
				# Reference https://developer.github.com/webhooks/#delivery-headers
				$t_signature = null;
				if( array_key_exists( 'HTTP_X_HUB_SIGNATURE', $_SERVER ) ) {
					$t_signature = explode( '=', $_SERVER['HTTP_X_HUB_SIGNATURE'] );
					if( $t_signature[0] != 'sha1' ) {
						# Invalid hash - as per docs, only sha1 is supported
						return;
					}
					$t_signature = $t_signature[1];
				}

				# Validate payload against webhook secret: checks OK if
				# - Webhook secret not defined and no signature received from GitHub, OR
				# - Payload's SHA1 hash salted with Webhook secret matches signature
				$t_secret = $t_repo->info['hub_webhook_secret'];
				$t_valid = ( !$t_secret && !$t_signature )
					|| $t_signature == hash_hmac('sha1', $f_payload, $t_secret);
				if( !$t_valid ) {
					# Invalid signature
					return;
				}

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

		if ($t_branch != '*')
		{
			$t_branches = array_map( 'trim', explode( ',', $t_branch ) );
		}
		else
		{

			$t_username = $p_repo->info['hub_username'];
			$t_reponame = $p_repo->info['hub_reponame'];

			$t_uri = $this->api_uri( $p_repo, "repos/$t_username/$t_reponame/branches" );
			$t_json = $this->api_json_url( $p_repo, $t_uri );

			$t_branches = array();
			foreach ($t_json as $t_branch)
			{
				$t_branches[] = $t_branch->name;
			}
		}
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
			$t_uri = $this->api_uri( $p_repo, "repos/$t_username/$t_reponame/commits/$t_commit_id" );
			$t_json = $this->api_json_url( $p_repo, $t_uri );

			if ( false === $t_json || is_null( $t_json ) ) {
				# Some error occured retrieving the commit
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
				date( 'Y-m-d H:i:s', strtotime( $p_json->commit->author->date ) ),
				$p_json->commit->author->name,
				$p_json->commit->message
			);

			if ( count( $p_json->parents ) > 0 ) {
				$t_parent = $p_json->parents[0];
				$t_changeset->parent = $t_parent->sha;
			}

			$t_changeset->author_email = $p_json->commit->author->email;
			$t_changeset->committer = $p_json->commit->committer->name;
			$t_changeset->committer_email = $p_json->commit->committer->email;

			if ( isset( $p_json->files ) ) {
				foreach ( $p_json->files as $t_file ) {
					switch ( $t_file->status ) {
						case 'added':
							$t_changeset->files[] = new SourceFile( 0, '', $t_file->filename, 'add' );
							break;
						case 'modified':
							$t_changeset->files[] = new SourceFile( 0, '', $t_file->filename, 'mod' );
							break;
						case 'removed':
							$t_changeset->files[] = new SourceFile( 0, '', $t_file->filename, 'rm' );
							break;
					}
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

	private function oauth_authorize_uri( $p_repo ) {
		$t_hub_app_client_id = null;
		$t_hub_app_secret = null;
		$t_hub_app_access_token = null;

		if ( isset( $p_repo->info['hub_app_client_id'] ) ) {
			$t_hub_app_client_id = $p_repo->info['hub_app_client_id'];
		}

		if ( isset( $p_repo->info['hub_app_secret'] ) ) {
			$t_hub_app_secret = $p_repo->info['hub_app_secret'];
		}

		if ( !empty( $t_hub_app_client_id ) && !empty( $t_hub_app_secret ) ) {
			return 'https://github.com/login/oauth/authorize?client_id=' . $t_hub_app_client_id . '&redirect_uri=' . urlencode(config_get('path') . 'plugin.php?page=SourceGithub/oauth_authorize&id=' . $p_repo->id ) . '&scope=repo';
		} else {
			return '';
		}
	}

	public static function oauth_get_access_token( $p_repo, $p_code ) {
		# build the GitHub URL & POST data
		$t_url = 'https://github.com/login/oauth/access_token';
		$t_post_data = array( 'client_id' => $p_repo->info['hub_app_client_id'],
			'client_secret' => $p_repo->info['hub_app_secret'],
			'code' => $p_code );
		$t_data = self::url_post( $t_url, $t_post_data );

		$t_access_token = '';
		if ( !empty( $t_data ) ) {
			$t_response = array();
			parse_str( $t_data, $t_response );
			if ( isset( $t_response['access_token'] ) === true ) {
				$t_access_token = $t_response['access_token'];
			}
		}

		if ( !empty( $t_access_token ) ) {
			if( !array_key_exists( 'hub_app_access_token', $p_repo->info )
				|| $t_access_token != $p_repo->info['hub_app_access_token']
			) {
				$p_repo->info['hub_app_access_token'] = $t_access_token;
				$p_repo->save();
			}
			return true;
		} else {
			return false;
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
