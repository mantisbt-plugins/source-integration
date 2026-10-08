<?php

# Copyright (c) 2026 Karsten Fusenig
# Based on SourceGitea, Copyright (c) 2024 Pasquale Pizzuti
# Licensed under the MIT license

/** @noinspection SqlResolve, PhpIncludeInspection */

if ( false === include_once( config_get( 'plugin_path' ) . 'Source/MantisSourceGitBasePlugin.class.php' ) ) {
	return;
}

/**
 * Forgejo integration for the Source Integration framework.
 *
 * Forgejo exposes the Gitea-compatible REST API (v1), but this plugin does not
 * share code with SourceGitea, as it works differently in several areas:
 * - API token is sent in the Authorization header, never in the URL
 * - webhook payloads are authenticated with the webhook secret (HMAC-SHA256)
 * - history is imported through the paginated commit list (1 request per
 *   page instead of 1 request per commit), including the changed files
 * - web links use Forgejo's permalink format (/src/commit/<sha>/<path>)
 */
class SourceForgejoPlugin extends MantisSourceGitBasePlugin {

	const PLUGIN_VERSION = '1.0.0';
	const FRAMEWORK_VERSION_REQUIRED = '2.5.0';

	public $type = 'forgejo';

	/**
	 * Forgejo API version, used to build the API URI
	 * @see api_uri()
	 */
	const API_VERSION = 'v1';

	/**
	 * Number of commits requested per API call when listing a branch.
	 * Forgejo caps this at its [api] MAX_RESPONSE_ITEMS setting (default 50).
	 */
	const API_PAGE_SIZE = 50;

	/**
	 * Safety net: maximum number of pages retrieved per branch and import.
	 */
	const API_MAX_PAGES = 400;

	/**
	 * Timeout in seconds for API requests.
	 */
	const API_TIMEOUT = 30;

	/**
	 * Appended to the repository's URL to build a Pull Request link.
	 * @see SourcePlugin::Changeset_Link_Callback()
	 */
	public $linkPullRequest = '/pulls/%s';

	/**
	 * @var string Error message of the last failed API request
	 */
	private $api_error = '';

	public function register() {
		parent::register();

		$this->author = 'Karsten Fusenig';
		$this->contact = 'public@fsng.de';
	}

	public function show_type() {
		return plugin_lang_get( 'forgejo' );
	}

	public function show_changeset( $p_repo, $p_changeset ) {
		$t_ref = substr( $p_changeset->revision, 0, 8 );
		$t_branch = $p_changeset->branch;

		return "$t_branch $t_ref";
	}

	public function show_file( $p_repo, $p_changeset, $p_file ) {
		return "$p_file->action - $p_file->filename";
	}

	/**
	 * Web URL of the repository, without trailing slash.
	 * @param SourceRepo $p_repo
	 * @return string
	 */
	public function url_base( $p_repo ) {
		if( empty( $p_repo->info['hub_root'] ) ) {
			return '';
		}
		return rtrim( $p_repo->info['hub_root'], '/' )
			. '/' . rawurlencode( $p_repo->info['hub_ownerid'] )
			. '/' . rawurlencode( $p_repo->info['hub_repoid'] );
	}

	public function url_repo( $p_repo, $p_changeset = null ) {
		$t_url = $this->url_base( $p_repo );
		if( $t_url && !is_null( $p_changeset ) ) {
			$t_url .= '/src/commit/' . $p_changeset->revision;
		}
		return $t_url;
	}

	public function url_changeset( $p_repo, $p_changeset ) {
		return $this->url_base( $p_repo ) . '/commit/' . $p_changeset->revision;
	}

	public function url_file( $p_repo, $p_changeset, $p_file ) {
		# Can't link to a deleted file
		if( $p_file->action == SourceFile::DELETED ) {
			return '';
		}
		$t_path = implode( '/', array_map( 'rawurlencode', explode( '/', $p_file->getFilename() ) ) );
		return $this->url_base( $p_repo ) . '/src/commit/' . $p_changeset->revision . '/' . $t_path;
	}

	public function url_diff( $p_repo, $p_changeset, $p_file ) {
		return $this->url_changeset( $p_repo, $p_changeset );
	}

	public function update_repo_form( $p_repo ) {
		$t_hub_root = isset( $p_repo->info['hub_root'] ) ? $p_repo->info['hub_root'] : null;
		$t_hub_ownerid = isset( $p_repo->info['hub_ownerid'] ) ? $p_repo->info['hub_ownerid'] : null;
		$t_hub_repoid = isset( $p_repo->info['hub_repoid'] ) ? $p_repo->info['hub_repoid'] : null;
		$t_hub_app_secret = isset( $p_repo->info['hub_app_secret'] ) ? $p_repo->info['hub_app_secret'] : null;
		$t_hub_webhook_secret = isset( $p_repo->info['hub_webhook_secret'] ) ? $p_repo->info['hub_webhook_secret'] : null;

		if( isset( $p_repo->info['master_branch'] ) ) {
			$t_master_branch = $p_repo->info['master_branch'];
		} else {
			$t_master_branch = $this->get_default_primary_branches();
		}
?>
<tr>
	<th class="category">
		<label for="hub_root">
			<?php echo plugin_lang_get( 'hub_root' ) ?>
		</label>
	</th>
	<td>
		<input id="hub_root" name="hub_root"
			   type="text" maxlength="250" size="40"
			   value="<?php echo string_attribute( $t_hub_root ) ?>"
		/>
<?php if( !filter_var( $t_hub_root, FILTER_VALIDATE_URL ) ) { ?>
		<i class="fa fa-warning ace-icon fa-lg red"></i>
<?php } ?>
	</td>
</tr>
<tr>
	<th class="category">
		<label for="hub_ownerid">
			<?php echo plugin_lang_get( 'hub_ownerid' ) ?>
		</label>
	</th>
	<td>
		<input id="hub_ownerid" name="hub_ownerid"
			   type="text" maxlength="250" size="40"
			   value="<?php echo string_attribute( $t_hub_ownerid ) ?>"
		/>
	</td>
</tr>
<tr>
	<th class="category">
		<label for="hub_repoid">
			<?php echo plugin_lang_get( 'hub_repoid' ) ?>
		</label>
	</th>
	<td>
		<input id="hub_repoid" name="hub_repoid"
			   type="text" maxlength="250" size="40"
			   value="<?php echo string_attribute( $t_hub_repoid ) ?>"
		/>
	</td>
</tr>
<tr>
	<th class="category">
		<label for="hub_app_secret">
			<?php echo plugin_lang_get( 'hub_app_secret' ) ?>
		</label>
	</th>
	<td>
		<input id="hub_app_secret" name="hub_app_secret"
			   type="password" maxlength="250" size="40"
			   value="<?php echo string_attribute( $t_hub_app_secret ) ?>"
		/>
	</td>
</tr>
<tr>
	<th class="category">
		<label for="hub_webhook_secret">
			<?php echo plugin_lang_get( 'hub_webhook_secret' ) ?>
		</label>
	</th>
	<td>
		<input id="hub_webhook_secret" name="hub_webhook_secret"
			   type="password" maxlength="250" size="40"
			   value="<?php echo string_attribute( $t_hub_webhook_secret ) ?>"
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
		# Connection check and webhook URL are only meaningful once the
		# repository has been saved with its Forgejo settings.
		if( $this->url_base( $p_repo ) ) {
?>
<tr>
	<th class="category">
		<?php echo plugin_lang_get( 'api_status' ) ?>
	</th>
	<td>
		<?php echo $this->api_status_html( $p_repo ) ?>
	</td>
</tr>
<tr>
	<th class="category">
		<?php echo plugin_lang_get( 'webhook_url' ) ?>
	</th>
	<td>
		<code><?php echo string_display_line( $this->webhook_url() ) ?></code>
	</td>
</tr>
<?php
		}
	}

	public function update_repo( $p_repo ) {
		$f_hub_root = trim( gpc_get_string( 'hub_root' ) );
		$f_hub_ownerid = trim( gpc_get_string( 'hub_ownerid' ) );
		$f_hub_repoid = trim( gpc_get_string( 'hub_repoid' ) );
		$f_hub_app_secret = trim( gpc_get_string( 'hub_app_secret' ) );
		$f_hub_webhook_secret = gpc_get_string( 'hub_webhook_secret' );

		$f_master_branch = gpc_get_string( 'master_branch' );
		$this->validate_branch_list( $f_master_branch );

		$p_repo->info['hub_root'] = rtrim( $f_hub_root, '/' );
		$p_repo->info['hub_ownerid'] = $f_hub_ownerid;
		$p_repo->info['hub_repoid'] = $f_hub_repoid;
		$p_repo->info['hub_app_secret'] = $f_hub_app_secret;
		$p_repo->info['hub_webhook_secret'] = $f_hub_webhook_secret;
		$p_repo->info['master_branch'] = $f_master_branch;

		return $p_repo;
	}

	/**
	 * URL to register as webhook target in Forgejo.
	 * The Source plugin's API key is replaced by a placeholder, as this page
	 * is not restricted to the users allowed to see the plugin's configuration.
	 * @return string
	 */
	private function webhook_url() {
		return config_get_global( 'path' ) . plugin_page( 'checkin', true, 'Source' ) . '&api_key=…';
	}

	/**
	 * Checks access to the repository through the API.
	 * @param SourceRepo $p_repo
	 * @return string HTML snippet describing the result
	 */
	private function api_status_html( $p_repo ) {
		$t_json = $this->api_get( $p_repo, $this->api_repo_path( $p_repo ) );
		if( $t_json === null || !isset( $t_json->full_name ) ) {
			return '<i class="fa fa-warning ace-icon fa-lg red"></i> '
				. string_display_line(
					sprintf( plugin_lang_get( 'api_status_failed' ), $this->api_error )
				);
		}
		return '<i class="fa fa-check ace-icon fa-lg green"></i> '
			. string_display_line(
				sprintf(
					plugin_lang_get( 'api_status_ok' ),
					$t_json->full_name,
					isset( $t_json->default_branch ) ? $t_json->default_branch : '?'
				)
			);
	}

	/**
	 * API path of the repository, e.g. "repos/owner/name".
	 * @param SourceRepo $p_repo
	 * @return string
	 */
	private function api_repo_path( $p_repo ) {
		return 'repos/' . rawurlencode( $p_repo->info['hub_ownerid'] )
			. '/' . rawurlencode( $p_repo->info['hub_repoid'] );
	}

	/**
	 * Builds the full URI of an API endpoint.
	 * @param SourceRepo $p_repo
	 * @param string     $p_path  Endpoint, relative to the API root
	 * @param array      $p_query Query parameters
	 * @return string
	 */
	private function api_uri( $p_repo, $p_path, array $p_query = array() ) {
		$t_uri = rtrim( $p_repo->info['hub_root'], '/' )
			. '/api/' . self::API_VERSION . '/' . $p_path;
		if( $p_query ) {
			$t_uri .= '?' . http_build_query( $p_query );
		}
		return $t_uri;
	}

	/**
	 * Sends a GET request to the Forgejo API and decodes the JSON response.
	 *
	 * The access token is transmitted in the Authorization header, so that it
	 * does not end up in web server logs (the "access_token" query parameter
	 * is deprecated in Forgejo and can be disabled by the administrator).
	 *
	 * @param SourceRepo $p_repo
	 * @param string     $p_path  Endpoint, relative to the API root
	 * @param array      $p_query Query parameters
	 * @return mixed|null Decoded JSON, null on failure (see $this->api_error)
	 */
	private function api_get( $p_repo, $p_path, array $p_query = array() ) {
		$this->api_error = '';
		$t_uri = $this->api_uri( $p_repo, $p_path, $p_query );

		$t_headers = array(
			'Accept: application/json',
			'User-Agent: MantisBT-SourceForgejo/' . self::PLUGIN_VERSION,
		);
		if( isset( $p_repo->info['hub_app_secret'] ) && !is_blank( $p_repo->info['hub_app_secret'] ) ) {
			$t_headers[] = 'Authorization: token ' . $p_repo->info['hub_app_secret'];
		}

		if( function_exists( 'curl_init' ) ) {
			$t_curl = curl_init( $t_uri );
			curl_setopt_array( $t_curl, array(
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_FOLLOWLOCATION => false,
				CURLOPT_HTTPHEADER => $t_headers,
				CURLOPT_CONNECTTIMEOUT => 10,
				CURLOPT_TIMEOUT => self::API_TIMEOUT,
			) );
			$t_body = curl_exec( $t_curl );
			$t_status = (int)curl_getinfo( $t_curl, CURLINFO_RESPONSE_CODE );
			if( $t_body === false ) {
				$this->api_error = curl_error( $t_curl );
				return null;
			}
		} else {
			$t_context = stream_context_create( array( 'http' => array(
				'method' => 'GET',
				'header' => implode( "\r\n", $t_headers ),
				'timeout' => self::API_TIMEOUT,
				'ignore_errors' => true,
				'follow_location' => 0,
			) ) );
			$t_body = @file_get_contents( $t_uri, false, $t_context );
			if( $t_body === false ) {
				$this->api_error = 'connection failed';
				return null;
			}
			# $http_response_header is deprecated as of PHP 8.5
			$t_response_headers = function_exists( 'http_get_last_response_headers' )
				? http_get_last_response_headers()
				: $http_response_header;
			$t_status = 0;
			if( isset( $t_response_headers[0] )
				&& preg_match( '{^HTTP/\S+\s+(\d{3})}', $t_response_headers[0], $t_matches )
			) {
				$t_status = (int)$t_matches[1];
			}
		}

		$t_json = json_decode( $t_body );
		if( $t_status < 200 || $t_status >= 300 ) {
			$t_message = is_object( $t_json ) && isset( $t_json->message ) ? $t_json->message : '';
			$this->api_error = trim( "HTTP $t_status $t_message" );
			return null;
		}
		if( $t_json === null ) {
			$this->api_error = 'invalid JSON response';
		}
		return $t_json;
	}

	/**
	 * Returns the value of an HTTP request header, or null if not set.
	 * @param string $p_name Header name, e.g. 'X-Forgejo-Event'
	 * @return string|null
	 */
	private function request_header( $p_name ) {
		$t_key = 'HTTP_' . strtoupper( str_replace( '-', '_', $p_name ) );
		return array_key_exists( $t_key, $_SERVER ) ? $_SERVER[$t_key] : null;
	}

	/**
	 * Validates the webhook payload against the repository's webhook secret.
	 *
	 * Checks OK if
	 * - no webhook secret is defined and Forgejo did not sign the payload, OR
	 * - the payload's HMAC-SHA256 keyed with the secret matches the signature
	 *
	 * @param SourceRepo $p_repo
	 * @param string     $p_payload Raw request body
	 * @return bool
	 */
	private function is_signature_valid( $p_repo, $p_payload ) {
		# Forgejo sends the hex digest in X-Forgejo-Signature (and X-Gitea-Signature)
		# and the GitHub-style "sha256=<digest>" in X-Hub-Signature-256.
		$t_signature = $this->request_header( 'X-Forgejo-Signature' );
		if( is_blank( $t_signature ) ) {
			$t_signature = $this->request_header( 'X-Gitea-Signature' );
		}
		if( is_blank( $t_signature ) ) {
			$t_signature = $this->request_header( 'X-Hub-Signature-256' );
			if( !is_blank( $t_signature ) ) {
				$t_signature = preg_replace( '/^sha256=/', '', $t_signature );
			}
		}

		$t_secret = isset( $p_repo->info['hub_webhook_secret'] ) ? $p_repo->info['hub_webhook_secret'] : '';
		if( is_blank( $t_secret ) ) {
			return is_blank( $t_signature );
		}
		if( is_blank( $t_signature ) ) {
			return false;
		}
		return hash_equals( hash_hmac( 'sha256', $p_payload, $t_secret ), strtolower( trim( $t_signature ) ) );
	}

	public function precommit() {
		# Only handle requests sent by a Forgejo webhook. Forgejo also sends
		# X-Gitea-* and X-GitHub-* headers for compatibility, so the dedicated
		# header is the only reliable way to tell it apart from other forges.
		if( is_null( $this->request_header( 'X-Forgejo-Event' ) ) ) {
			return null;
		}

		$f_payload = file_get_contents( 'php://input' );
		if( $f_payload === false || is_blank( $f_payload ) ) {
			return null;
		}

		$t_data = json_decode( $f_payload, true );
		if( !is_array( $t_data ) || !isset( $t_data['repository']['full_name'] ) ) {
			return null;
		}
		$t_full_name = $t_data['repository']['full_name'];
		$t_html_url = isset( $t_data['repository']['html_url'] ) ? $t_data['repository']['html_url'] : '';

		$t_repo_table = plugin_table( 'repository', 'Source' );
		$t_query = "SELECT * FROM $t_repo_table WHERE type = " . db_param();
		$t_result = db_query( $t_query, array( $this->type ) );

		while( $t_row = db_fetch_array( $t_result ) ) {
			$t_repo = new SourceRepo( $t_row['type'], $t_row['name'], $t_row['url'], $t_row['info'] );
			$t_repo->id = $t_row['id'];

			if( !isset( $t_repo->info['hub_ownerid'] ) || !isset( $t_repo->info['hub_repoid'] ) ) {
				continue;
			}

			# Owner and repository names are case-insensitive in Forgejo
			$t_repo_full_name = $t_repo->info['hub_ownerid'] . '/' . $t_repo->info['hub_repoid'];
			if( strcasecmp( $t_repo_full_name, $t_full_name ) != 0 ) {
				continue;
			}

			# Several Forgejo instances may host a repository with the same name
			if( $t_html_url
				&& strcasecmp(
					(string)parse_url( $t_html_url, PHP_URL_HOST ),
					(string)parse_url( $t_repo->info['hub_root'], PHP_URL_HOST )
				) != 0
			) {
				continue;
			}

			if( !$this->is_signature_valid( $t_repo, $f_payload ) ) {
				# Do not fall back to other plugins or generic processing
				http_response_code( HTTP_STATUS_FORBIDDEN );
				die( plugin_lang_get( 'error_invalid_signature' ) );
			}

			return array( 'repo' => $t_repo, 'data' => $t_data );
		}

		return null;
	}

	public function commit( $p_repo, $p_data ) {
		$t_event = $this->request_header( 'X-Forgejo-Event' );

		# Only push events carry commits; acknowledge anything else
		# (e.g. create, delete, pull_request) without processing it.
		if( $t_event != 'push' || !isset( $p_data['ref'] ) ) {
			echo sprintf( plugin_lang_get( 'webhook_event_ignored' ), $t_event );
			return array();
		}

		# 'refs/heads/issue/branch-description' => ['refs', 'heads', 'issue/branch-description']
		$t_ref = explode( '/', $p_data['ref'], 3 );
		if( count( $t_ref ) < 3 || $t_ref[1] != 'heads' ) {
			# Tags and other refs do not add commits to a branch
			echo sprintf( plugin_lang_get( 'webhook_ref_ignored' ), $p_data['ref'] );
			return array();
		}
		$t_branch = $t_ref[2];

		# Branch deletion: nothing to import
		if( isset( $p_data['after'] ) && preg_match( '/^0+$/', $p_data['after'] ) ) {
			echo sprintf( plugin_lang_get( 'webhook_ref_ignored' ), $p_data['ref'] );
			return array();
		}

		if( !$this->is_branch_allowed( $p_repo, $t_branch ) ) {
			echo sprintf( plugin_lang_get( 'webhook_branch_ignored' ), $t_branch );
			return array();
		}

		# The payload only lists the first few commits of a push (Forgejo's
		# [ui] FEED_MAX_COMMIT_NUM, 5 by default), so the branch is imported
		# through the API instead of relying on the payload's commit list.
		return $this->import_branch( $p_repo, $t_branch );
	}

	/**
	 * List of branches to import, null if all branches are allowed.
	 * @param SourceRepo $p_repo
	 * @return array|null
	 */
	private function get_allowed_branches( $p_repo ) {
		$t_branches = isset( $p_repo->info['master_branch'] ) ? $p_repo->info['master_branch'] : '';
		if( is_blank( $t_branches ) ) {
			$t_branches = $this->get_default_primary_branches();
		}
		if( trim( $t_branches ) == '*' ) {
			return null;
		}
		return array_map( 'trim', explode( ',', $t_branches ) );
	}

	/**
	 * Determines whether commits of the given branch should be imported.
	 * @param SourceRepo $p_repo
	 * @param string     $p_branch
	 * @return bool
	 */
	private function is_branch_allowed( $p_repo, $p_branch ) {
		$t_allowed = $this->get_allowed_branches( $p_repo );
		return $t_allowed === null || in_array( $p_branch, $t_allowed );
	}

	public function import_full( $p_repo ) {
		echo '<pre>';

		# Retrieve the complete list of branches (paginated)
		$t_branches = array();
		for( $t_page = 1; $t_page <= self::API_MAX_PAGES; $t_page++ ) {
			$t_json = $this->api_get(
				$p_repo,
				$this->api_repo_path( $p_repo ) . '/branches',
				array( 'page' => $t_page, 'limit' => self::API_PAGE_SIZE )
			);
			if( !is_array( $t_json ) ) {
				echo "Could not retrieve branches from Forgejo ($this->api_error). Make sure your ";
				print_link(
					plugin_page( 'repo_update_page', null, 'Source' ) . "&id=$p_repo->id",
					'repository settings'
				);
				echo " are correct.";
				echo '</pre>';
				return array();
			}
			foreach( $t_json as $t_branch ) {
				if( $this->is_branch_allowed( $p_repo, $t_branch->name ) ) {
					$t_branches[] = $t_branch->name;
				}
			}
			if( count( $t_json ) < self::API_PAGE_SIZE ) {
				break;
			}
		}

		$t_changesets = array();
		foreach( $t_branches as $t_branch ) {
			$t_changesets = array_merge( $t_changesets, $this->import_branch( $p_repo, $t_branch ) );
		}

		echo '</pre>';

		return $t_changesets;
	}

	public function import_latest( $p_repo ) {
		return $this->import_full( $p_repo );
	}

	/**
	 * Imports the commits of a branch which are not known yet.
	 *
	 * Commits are listed newest first. Paging stops as soon as a page does not
	 * contain any new commit, i.e. when reaching the history imported earlier.
	 *
	 * @param SourceRepo $p_repo
	 * @param string     $p_branch
	 * @return SourceChangeset[] New changesets
	 */
	private function import_branch( $p_repo, $p_branch ) {
		$t_changesets = array();

		echo "Retrieving commits of branch '" . string_display_line( $p_branch ) . "' ...\n";

		for( $t_page = 1; $t_page <= self::API_MAX_PAGES; $t_page++ ) {
			$t_json = $this->api_get(
				$p_repo,
				$this->api_repo_path( $p_repo ) . '/commits',
				array(
					'sha' => $p_branch,
					'page' => $t_page,
					'limit' => self::API_PAGE_SIZE,
					# Skip the expensive parts of the response, keep the file list
					'stat' => 'false',
					'verification' => 'false',
					'files' => 'true',
				)
			);
			if( !is_array( $t_json ) ) {
				echo "failed ($this->api_error).\n";
				break;
			}

			$t_new = 0;
			foreach( $t_json as $t_commit ) {
				$t_changeset = $this->json_commit_changeset( $p_repo, $t_commit, $p_branch );
				if( $t_changeset ) {
					$t_changesets[] = $t_changeset;
					$t_new++;
				}
			}

			if( $t_new == 0 || count( $t_json ) < self::API_PAGE_SIZE ) {
				break;
			}
		}

		# Oldest first, so that issues are updated in chronological order
		return array_reverse( $t_changesets );
	}

	/**
	 * Creates a changeset from a commit returned by the API.
	 * @param SourceRepo $p_repo
	 * @param object     $p_json   Commit as returned by the API
	 * @param string     $p_branch
	 * @return SourceChangeset|null Null if the commit is invalid or already imported
	 */
	private function json_commit_changeset( $p_repo, $p_json, $p_branch = '' ) {
		if( !is_object( $p_json ) || !isset( $p_json->sha ) || !isset( $p_json->commit ) ) {
			return null;
		}

		echo "processing $p_json->sha ... ";
		if( SourceChangeset::exists( $p_repo->id, $p_json->sha ) ) {
			echo "already exists.\n";
			return null;
		}

		$t_author = isset( $p_json->commit->author ) ? $p_json->commit->author : null;
		$t_committer = isset( $p_json->commit->committer ) ? $p_json->commit->committer : null;

		$t_changeset = new SourceChangeset(
			$p_repo->id,
			$p_json->sha,
			$p_branch,
			$t_author && isset( $t_author->date ) ? $t_author->date : '',
			$t_author && isset( $t_author->name ) ? $t_author->name : '',
			isset( $p_json->commit->message ) ? $p_json->commit->message : ''
		);

		if( !empty( $p_json->parents ) ) {
			$t_changeset->parent = $p_json->parents[0]->sha;
		}

		$t_changeset->author_email = $t_author && isset( $t_author->email ) ? $t_author->email : '';
		if( $t_committer ) {
			$t_changeset->committer = isset( $t_committer->name ) ? $t_committer->name : '';
			$t_changeset->committer_email = isset( $t_committer->email ) ? $t_committer->email : '';
		}

		if( !empty( $p_json->files ) ) {
			foreach( $p_json->files as $t_file ) {
				$t_status = isset( $t_file->status ) ? $t_file->status : '';
				switch( $t_status ) {
					case 'added':
						$t_action = SourceFile::ADDED;
						break;
					case 'modified':
					case 'changed':
						$t_action = SourceFile::MODIFIED;
						break;
					case 'removed':
					case 'deleted':
						$t_action = SourceFile::DELETED;
						break;
					case 'renamed':
						$t_action = SourceFile::RENAMED;
						break;
					default:
						$t_action = trim( SourceFile::UNKNOWN . ' ' . $t_status );
				}
				$t_changeset->files[] = new SourceFile( 0, '', $t_file->filename, $t_action );
			}
		}

		$t_changeset->save();

		echo "saved.\n";
		return $t_changeset;
	}

}
