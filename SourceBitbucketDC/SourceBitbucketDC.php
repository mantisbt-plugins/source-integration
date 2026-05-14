<?php

# Licensed under the MIT license

if( false === include_once( config_get( 'plugin_path' ) . 'Source/MantisSourceGitBasePlugin.class.php' ) ) {
	return;
}

/**
 * Source integration plugin for Bitbucket Data Center (self-hosted).
 *
 * Supports webhook-based check-in (repo:refs_changed event) and manual
 * full/latest import via the Bitbucket DC REST API 1.0.
 *
 * Authentication uses a Personal Access Token (Bearer).
 * The standard "URL" field holds the Bitbucket DC instance root URL
 * (e.g. https://bitbucket.example.com).
 */
class SourceBitbucketDCPlugin extends MantisSourceGitBasePlugin {

	const PLUGIN_VERSION = '1.0.0';
	const FRAMEWORK_VERSION_REQUIRED = '2.5.0';

	const ERROR_BITBUCKET_DC_API = 'bitbucketdc_api_error';

	public $type = 'bbdc';

	/**
	 * sprintf template appended to url_repo() to build a Pull Request link.
	 * %s is replaced by the PR number.
	 */
	public $linkPullRequest = '/pull-requests/%s';

	public function register() {
		parent::register();
		$this->name        = plugin_lang_get( 'title' );
		$this->description = plugin_lang_get( 'description' );
		$this->author      = 'Nikolai Orekhov';
		$this->contact     = 'no@3cx.com';
		$this->url         = '';
	}

	public function errors() {
		$t_errors = array(
			self::ERROR_BITBUCKET_DC_API => plugin_lang_get( 'error_' . self::ERROR_BITBUCKET_DC_API ),
		);
		return array_merge( parent::errors(), $t_errors );
	}

	// -----------------------------------------------------------------------
	// Display helpers
	// -----------------------------------------------------------------------

	public function show_type() {
		return plugin_lang_get( 'bitbucketdc' );
	}

	public function show_changeset( $p_repo, $p_changeset ) {
		return substr( $p_changeset->revision, 0, 8 );
	}

	public function show_file( $p_repo, $p_changeset, $p_file ) {
		return $p_file->action . ' - ' . $p_file->filename;
	}

	// -----------------------------------------------------------------------
	// URL builders
	// -----------------------------------------------------------------------

	/**
	 * Web UI base URL for the repository.
	 * e.g. https://bitbucket.example.com/projects/PROJ/repos/my-repo
	 */
	private function url_base( $p_repo ) {
		$t_base    = rtrim( $p_repo->url, '/' );
		$t_project = $p_repo->info['dc_project_key'];
		$t_slug    = $p_repo->info['dc_repo_slug'];
		return "$t_base/projects/$t_project/repos/$t_slug";
	}

	/**
	 * REST API base URL for the repository.
	 * e.g. https://bitbucket.example.com/rest/api/1.0/projects/PROJ/repos/my-repo
	 */
	private function api_base( $p_repo ) {
		$t_base    = rtrim( $p_repo->url, '/' );
		$t_project = $p_repo->info['dc_project_key'];
		$t_slug    = $p_repo->info['dc_repo_slug'];
		return "$t_base/rest/api/1.0/projects/$t_project/repos/$t_slug";
	}

	public function url_repo( $p_repo, $p_changeset = null ) {
		return $this->url_base( $p_repo ) . '/browse';
	}

	public function url_changeset( $p_repo, $p_changeset ) {
		return $this->url_base( $p_repo ) . '/commits/' . $p_changeset->revision;
	}

	public function url_file( $p_repo, $p_changeset, $p_file ) {
		if( $p_file->action === SourceFile::DELETED ) {
			return '';
		}
		return $this->url_base( $p_repo ) . '/browse/' . $p_file->filename
			. '?at=' . $p_changeset->revision;
	}

	public function url_diff( $p_repo, $p_changeset, $p_file ) {
		if( $p_file->action === SourceFile::DELETED ) {
			return '';
		}
		return $this->url_base( $p_repo ) . '/diff/' . $p_file->filename
			. '?at=' . $p_changeset->revision;
	}

	// -----------------------------------------------------------------------
	// Manage page actions
	// -----------------------------------------------------------------------

	public function show_manage_actions( $p_repo ) {
		static $s_script_loaded = false;

		$t_url = plugin_page( 'test_connection', true, 'SourceBitbucketDC' ) . '&id=' . $p_repo->id;
?>
<button type="button"
		class="btn btn-xs btn-primary btn-white btn-round bbdc-test-connection"
		data-url="<?php echo string_attribute( $t_url ) ?>">
	<?php echo plugin_lang_get( 'test_connection', 'SourceBitbucketDC' ) ?>
</button>
<?php
		if( !$s_script_loaded ) {
			$s_script_loaded = true;
			$t_js = plugin_file( 'test_connection.js', false, 'SourceBitbucketDC' );
			echo '<script src="' . string_attribute( $t_js ) . '"></script>';
		}
	}

	/**
	 * Test connectivity and authentication against the Bitbucket DC API.
	 * Returns an array with 'ok' (bool) and 'message' (string).
	 */
	public function test_connection( $p_repo ) {
		$t_url   = $this->api_base( $p_repo );
		$t_token = $p_repo->info['dc_token'] ?? '';

		$t_ch = curl_init( $t_url );
		curl_setopt_array( $t_ch, array(
			CURLOPT_HTTPHEADER     => array(
				'Authorization: Bearer ' . $t_token,
				'Accept: application/json',
			),
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_USERAGENT      => 'mantisbt/' . MANTIS_VERSION,
			CURLOPT_SSL_VERIFYPEER => true,
			CURLOPT_TIMEOUT        => 10,
		) );
		$t_data      = curl_exec( $t_ch );
		$t_http_code = curl_getinfo( $t_ch, CURLINFO_HTTP_CODE );
		$t_curl_err  = curl_error( $t_ch );
		curl_close( $t_ch );

		if( $t_data === false ) {
			return array( 'ok' => false, 'message' => 'cURL error: ' . $t_curl_err );
		}
		if( $t_http_code === 401 ) {
			return array( 'ok' => false, 'message' => 'Authentication failed (HTTP 401) — check your access token.' );
		}
		if( $t_http_code === 404 ) {
			return array( 'ok' => false, 'message' => 'Repository not found (HTTP 404) — check project key and repo slug.' );
		}
		if( $t_http_code >= 400 ) {
			return array( 'ok' => false, 'message' => 'HTTP ' . $t_http_code . ' from Bitbucket DC API.' );
		}

		$t_json = json_decode( $t_data );
		$t_name = $t_json->name ?? '(unknown)';
		return array( 'ok' => true, 'message' => 'Connected successfully to repository: ' . $t_name );
	}

	// -----------------------------------------------------------------------
	// Repository form
	// -----------------------------------------------------------------------

	public function update_repo_form( $p_repo ) {
		$t_dc_project_key = $p_repo->info['dc_project_key'] ?? '';
		$t_dc_repo_slug   = $p_repo->info['dc_repo_slug']   ?? '';
		$t_dc_token       = $p_repo->info['dc_token']       ?? '';
		$t_master_branch  = $p_repo->info['master_branch']  ?? $this->get_default_primary_branches();

?>
<tr>
	<th class="category">
		<label for="dc_project_key"><?php echo plugin_lang_get( 'dc_project_key' ) ?></label>
	</th>
	<td>
		<input id="dc_project_key" name="dc_project_key"
			   type="text" maxlength="100" size="20"
			   value="<?php echo string_attribute( $t_dc_project_key ) ?>" />
		<br/><span class="small"><?php echo plugin_lang_get( 'dc_project_key_info' ) ?></span>
	</td>
</tr>
<tr>
	<th class="category">
		<label for="dc_repo_slug"><?php echo plugin_lang_get( 'dc_repo_slug' ) ?></label>
	</th>
	<td>
		<input id="dc_repo_slug" name="dc_repo_slug"
			   type="text" maxlength="250" size="60"
			   value="<?php echo string_attribute( $t_dc_repo_slug ) ?>" />
		<br/><span class="small"><?php echo plugin_lang_get( 'dc_repo_slug_info' ) ?></span>
	</td>
</tr>
<tr>
	<th class="category">
		<label for="dc_token"><?php echo plugin_lang_get( 'dc_token' ) ?></label>
	</th>
	<td>
		<input id="dc_token" name="dc_token"
			   type="password" maxlength="250" size="60"
			   value="<?php echo string_attribute( $t_dc_token ) ?>" />
		<br/><span class="small"><?php echo plugin_lang_get( 'dc_token_info' ) ?></span>
	</td>
</tr>
<tr>
	<th class="category">
		<label for="master_branch"><?php echo plugin_lang_get( 'master_branch' ) ?></label>
	</th>
	<td>
		<input id="master_branch" name="master_branch"
			   type="text" maxlength="250" size="60"
			   value="<?php echo string_attribute( $t_master_branch ) ?>" />
		<br/><span class="small"><?php echo plugin_lang_get( 'master_branch_info' ) ?></span>
	</td>
</tr>
<?php
	}

	public function update_repo( $p_repo ) {
		$f_master_branch = gpc_get_string( 'master_branch' );
		$this->validate_branch_list( $f_master_branch );

		$p_repo->info['dc_project_key'] = strtoupper( gpc_get_string( 'dc_project_key' ) );
		$p_repo->info['dc_repo_slug']   = gpc_get_string( 'dc_repo_slug' );
		$p_repo->info['dc_token']       = gpc_get_string( 'dc_token' );
		$p_repo->info['master_branch']  = $f_master_branch;

		return $p_repo;
	}

	// -----------------------------------------------------------------------
	// HTTP / API helpers
	// -----------------------------------------------------------------------

	/**
	 * Fetch a URL using the configured Bearer token.
	 * Returns the raw response body, or triggers a plugin error on failure.
	 */
	private function url_get( $p_repo, $p_url ) {
		$t_token = $p_repo->info['dc_token'] ?? '';

		if( function_exists( 'curl_init' ) ) {
			$t_curl_version = curl_version();
			$t_ch = curl_init( $p_url );
			curl_setopt_array( $t_ch, array(
				CURLOPT_HTTPHEADER     => array(
					'Authorization: Bearer ' . $t_token,
					'Accept: application/json',
				),
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_USERAGENT      => 'mantisbt/' . MANTIS_VERSION
					. ' php-curl/' . $t_curl_version['version'],
				CURLOPT_SSL_VERIFYPEER => true,
			) );
			$t_data      = curl_exec( $t_ch );
			$t_http_code = curl_getinfo( $t_ch, CURLINFO_HTTP_CODE );
			curl_close( $t_ch );

			if( $t_data === false ) {
				log_event( LOG_PLUGIN, '[SourceBitbucketDC] cURL error fetching ' . $p_url );
				error_parameters( 'cURL error fetching ' . $p_url );
				plugin_error( self::ERROR_BITBUCKET_DC_API );
			}
			if( $t_http_code >= 400 ) {
				log_event( LOG_PLUGIN, '[SourceBitbucketDC] HTTP ' . $t_http_code . ' from ' . $p_url );
				error_parameters( "HTTP $t_http_code from Bitbucket DC API" );
				plugin_error( self::ERROR_BITBUCKET_DC_API );
			}
			return $t_data;
		}

		# Fallback: shell curl
		$t_url_safe   = escapeshellarg( $p_url );
		$t_auth_header = escapeshellarg( 'Authorization: Bearer ' . $t_token );
		return shell_exec( "curl -s -H $t_auth_header $t_url_safe" );
	}

	/**
	 * Fetch and JSON-decode a Bitbucket DC API URL.
	 */
	private function api_json( $p_repo, $p_url ) {
		$t_data = $this->url_get( $p_repo, $p_url );
		$t_json = json_decode( $t_data );
		if( json_last_error() !== JSON_ERROR_NONE ) {
			error_parameters( substr( $t_data, 0, 512 ) );
			plugin_error( self::ERROR_BITBUCKET_DC_API );
		}
		return $t_json;
	}

	/**
	 * Fetch all pages from a Bitbucket DC paginated API endpoint.
	 *
	 * DC pagination uses isLastPage + nextPageStart.
	 *
	 * @param SourceRepo $p_repo
	 * @param string     $p_url       Base endpoint URL (no pagination params)
	 * @param int        $p_limit     Page size
	 * @return array     Flat array of all 'values' items
	 */
	private function api_paged( $p_repo, $p_url, $p_limit = 100 ) {
		$t_values    = array();
		$t_start     = 0;
		$t_separator = strpos( $p_url, '?' ) !== false ? '&' : '?';

		do {
			$t_page_url = $p_url . $t_separator . 'limit=' . $p_limit . '&start=' . $t_start;
			$t_json     = $this->api_json( $p_repo, $t_page_url );

			if( isset( $t_json->values ) ) {
				foreach( $t_json->values as $t_item ) {
					$t_values[] = $t_item;
				}
			}

			$t_is_last = $t_json->isLastPage ?? true;
			$t_start   = $t_json->nextPageStart ?? 0;
		} while( !$t_is_last );

		return $t_values;
	}

	// -----------------------------------------------------------------------
	// Webhook handling
	// -----------------------------------------------------------------------

	/**
	 * Intercept an incoming Bitbucket DC webhook (repo:refs_changed).
	 *
	 * Bitbucket DC sends a JSON body; we match the repository by project key
	 * and repo slug. If matched, returns array('repo' => ..., 'data' => ...)
	 * for checkin.php to pass to commit().
	 */
	public function precommit() {
		# Only handle DC webhooks (JSON body, no 'data' POST field)
		$t_event = $_SERVER['HTTP_X_EVENT_KEY'] ?? '';
		if( $t_event !== '' && $t_event !== 'repo:refs_changed' ) {
			return null;
		}

		$t_input = file_get_contents( 'php://input' );
		if( empty( $t_input ) ) {
			return null;
		}

		$t_json = json_decode( $t_input );
		if( json_last_error() !== JSON_ERROR_NONE ) {
			return null;
		}

		# Validate it looks like a DC push payload
		if( !isset( $t_json->changes ) || !isset( $t_json->repository ) ) {
			return null;
		}

		$t_repo_slug   = $t_json->repository->slug ?? null;
		$t_project_key = $t_json->repository->project->key ?? null;
		if( is_null( $t_repo_slug ) || is_null( $t_project_key ) ) {
			return null;
		}

		log_event( LOG_PLUGIN, '[SourceBitbucketDC] Webhook received for ' . $t_project_key . '/' . $t_repo_slug );

		# Find a MantisBT repository matching the webhook's project + slug
		foreach( SourceRepo::load_all() as $t_repo ) {
			if( $t_repo->type !== $this->type ) {
				continue;
			}
			$t_cfg_slug    = $t_repo->info['dc_repo_slug']   ?? '';
			$t_cfg_project = $t_repo->info['dc_project_key'] ?? '';
			if( strcasecmp( $t_cfg_slug, $t_repo_slug ) === 0
				&& strcasecmp( $t_cfg_project, $t_project_key ) === 0 ) {

				plugin_push_current( 'Source' );
				$t_secret = plugin_config_get( 'api_key', '' );
				plugin_pop_current();

				if( $t_secret !== '' ) {
					$t_sig_header = $_SERVER['HTTP_X_HUB_SIGNATURE'] ?? '';
					$t_expected   = 'sha256=' . hash_hmac( 'sha256', $t_input, $t_secret );
					if( !hash_equals( $t_expected, $t_sig_header ) ) {
						log_event( LOG_PLUGIN, '[SourceBitbucketDC] Invalid webhook signature for repo: ' . $t_repo->name );
						http_response_code( 403 );
						die( 'Invalid webhook signature' );
					}
				}

				log_event( LOG_PLUGIN, '[SourceBitbucketDC] Matched repo: ' . $t_repo->name );
				return array( 'repo' => $t_repo, 'data' => $t_json );
			}
		}

		log_event( LOG_PLUGIN, '[SourceBitbucketDC] No matching repo found for ' . $t_project_key . '/' . $t_repo_slug );
		return null;
	}

	/**
	 * Process a webhook payload: import new commits for each changed branch.
	 *
	 * @param SourceRepo $p_repo
	 * @param object     $p_data  Decoded webhook JSON
	 * @return SourceChangeset[]
	 */
	public function commit( $p_repo, $p_data ) {
		$t_changesets = array();

		$t_master_list = array_map( 'trim', explode( ',', $p_repo->info['master_branch'] ?? 'master' ) );
		$t_use_all     = in_array( '*', $t_master_list );

		# Build a hash-indexed lookup of commits included in the webhook payload
		$t_payload_commits = array();
		if( isset( $p_data->commits ) && is_array( $p_data->commits ) ) {
			foreach( $p_data->commits as $t_c ) {
				$t_payload_commits[$t_c->id] = $t_c;
			}
		}

		foreach( $p_data->changes as $t_change ) {
			# Skip non-branch refs and deletions
			if( ( $t_change->ref->type ?? '' ) !== 'BRANCH' ) {
				continue;
			}
			if( ( $t_change->type ?? '' ) === 'DELETE' ) {
				continue;
			}

			$t_branch    = $t_change->ref->displayId;

			if( !$t_use_all && !$this->branch_matches( $t_branch, $t_master_list ) ) {
				log_event( LOG_PLUGIN, '[SourceBitbucketDC] Skipping branch (filtered): ' . $t_branch );
				continue;
			}

			$t_from_hash = $t_change->fromHash ?? null;
			$t_to_hash   = $t_change->toHash   ?? null;

			if( is_null( $t_to_hash ) ) {
				continue;
			}

			# Try to resolve the full commit range from the payload before hitting the API
			$t_range = $this->resolve_range_from_payload( $t_payload_commits, $t_from_hash, $t_to_hash );

			if( $t_range !== null ) {
				log_event( LOG_PLUGIN, '[SourceBitbucketDC] Processing branch ' . $t_branch . ' from webhook payload (' . count( $t_range ) . ' commits)' );
				$t_changesets = array_merge(
					$t_changesets,
					$this->process_commits( $p_repo, $t_range, $t_branch )
				);
			} else {
				log_event( LOG_PLUGIN, '[SourceBitbucketDC] Processing branch ' . $t_branch . ' via API (payload insufficient)' );
				$t_changesets = array_merge(
					$t_changesets,
					$this->import_commits_range( $p_repo, $t_branch, $t_from_hash, $t_to_hash )
				);
			}
		}

		log_event( LOG_PLUGIN, '[SourceBitbucketDC] Webhook processed ' . count( $t_changesets ) . ' new changesets for repo: ' . $p_repo->name );
		return $t_changesets;
	}

	/**
	 * Try to resolve commits in range (fromHash, toHash] using only the
	 * payload commits. Walks the parent chain from toHash back to fromHash.
	 * Returns an array of commit objects on success, or null if the payload
	 * does not cover the full range and an API call is needed instead.
	 *
	 * @param array       $p_payload_commits Hash-indexed payload commits
	 * @param string|null $p_from_hash       Exclusive lower bound
	 * @param string      $p_to_hash         Inclusive upper bound
	 * @return array|null
	 */
	private function resolve_range_from_payload( $p_payload_commits, $p_from_hash, $p_to_hash ) {
		if( empty( $p_payload_commits ) || !isset( $p_payload_commits[$p_to_hash] ) ) {
			return null;
		}

		$t_zero_hash = '0000000000000000000000000000000000000000';
		$t_result    = array();
		$t_current   = $p_to_hash;

		while( true ) {
			if( $t_current === $p_from_hash || $t_current === $t_zero_hash ) {
				break;
			}
			if( !isset( $p_payload_commits[$t_current] ) ) {
				return null;
			}
			$t_commit  = $p_payload_commits[$t_current];
			$t_result[] = $t_commit;
			$t_current  = $t_commit->parents[0]->id ?? null;
			if( is_null( $t_current ) ) {
				break;
			}
		}

		return $t_result;
	}

	// -----------------------------------------------------------------------
	// Import
	// -----------------------------------------------------------------------

	/**
	 * Import commits in the range (fromHash, toHash] for a branch.
	 *
	 * @param SourceRepo  $p_repo
	 * @param string      $p_branch
	 * @param string|null $p_from_hash  Exclusive lower bound (null = initial push)
	 * @param string      $p_to_hash    Inclusive upper bound
	 * @return SourceChangeset[]
	 */
	private function import_commits_range( $p_repo, $p_branch, $p_from_hash, $p_to_hash ) {
		$t_url = $this->api_base( $p_repo ) . '/commits?until=' . urlencode( $p_to_hash );
		if( !is_null( $p_from_hash ) && $p_from_hash !== '0000000000000000000000000000000000000000' ) {
			$t_url .= '&since=' . urlencode( $p_from_hash );
		}
		$t_commits = $this->api_paged( $p_repo, $t_url );
		return $this->process_commits( $p_repo, $t_commits, $p_branch );
	}

	/**
	 * Full import: import all commits from all configured branches.
	 *
	 * @param SourceRepo $p_repo
	 * @return SourceChangeset[]
	 */
	public function import_full( $p_repo ) {
		log_event( LOG_PLUGIN, '[SourceBitbucketDC] Full import started for repo: ' . $p_repo->name );

		$t_branches_url = $this->api_base( $p_repo ) . '/branches';
		$t_branches     = $this->api_paged( $p_repo, $t_branches_url );

		$t_master_list = array_map( 'trim', explode( ',', $p_repo->info['master_branch'] ?? 'master' ) );
		$t_use_all     = in_array( '*', $t_master_list );

		$t_changesets = array();
		foreach( $t_branches as $t_branch ) {
			$t_branch_name = $t_branch->displayId;
			if( !$t_use_all && !$this->branch_matches( $t_branch_name, $t_master_list ) ) {
				log_event( LOG_PLUGIN, '[SourceBitbucketDC] Skipping branch (filtered): ' . $t_branch_name );
				continue;
			}

			log_event( LOG_PLUGIN, '[SourceBitbucketDC] Importing branch: ' . $t_branch_name );
			$t_url     = $this->api_base( $p_repo ) . '/commits?until='
				. urlencode( $t_branch->latestCommit );
			$t_commits = $this->api_paged( $p_repo, $t_url );
			$t_changesets = array_merge(
				$t_changesets,
				$this->process_commits( $p_repo, $t_commits, $t_branch_name )
			);
		}

		log_event( LOG_PLUGIN, '[SourceBitbucketDC] Full import complete for repo: ' . $p_repo->name . ' — ' . count( $t_changesets ) . ' new changesets' );
		return $t_changesets;
	}

	protected function validate_branch_list( $p_list ) {
		foreach( array_map( 'trim', explode( ',', $p_list ) ) as $t_pattern ) {
			if( $t_pattern === '*' || strpbrk( $t_pattern, '*?' ) !== false ) {
				continue;
			}
			$this->ensure_branch_valid( $t_pattern );
		}
	}

	private function branch_matches( $p_branch, $p_patterns ) {
		foreach( $p_patterns as $t_pattern ) {
			if( fnmatch( $t_pattern, $p_branch ) ) {
				return true;
			}
		}
		return false;
	}

	public function import_latest( $p_repo ) {
		return $this->import_full( $p_repo );
	}

	// -----------------------------------------------------------------------
	// Commit processing
	// -----------------------------------------------------------------------

	/**
	 * Convert an array of DC API commit objects into saved SourceChangesets.
	 * Skips commits already in the database.
	 *
	 * @param SourceRepo $p_repo
	 * @param array      $p_commits  DC API commit objects
	 * @param string     $p_branch
	 * @return SourceChangeset[]
	 */
	private function process_commits( $p_repo, $p_commits, $p_branch ) {
		$t_changesets = array();
		foreach( $p_commits as $t_commit ) {
			if( SourceChangeset::exists( $p_repo->id, $t_commit->id ) ) {
				log_event( LOG_PLUGIN, '[SourceBitbucketDC] Skipping existing commit: ' . substr( $t_commit->id, 0, 8 ) );
				continue;
			}
			$t_changeset = $this->json_commit_to_changeset( $p_repo, $t_commit, $p_branch );
			if( !is_null( $t_changeset ) ) {
				$t_changeset->save();
				log_event( LOG_PLUGIN, '[SourceBitbucketDC] Saved commit: ' . substr( $t_commit->id, 0, 8 ) . ' on ' . $p_branch . ' by ' . ( $t_commit->author->name ?? 'unknown' ) );
				$t_changesets[] = $t_changeset;
			}
		}
		return $t_changesets;
	}

	/**
	 * Convert a single DC API commit object into a SourceChangeset.
	 * Also fetches the list of changed files via a second API call.
	 *
	 * @param SourceRepo $p_repo
	 * @param object     $p_commit  DC commit JSON object
	 * @param string     $p_branch
	 * @return SourceChangeset
	 */
	private function json_commit_to_changeset( $p_repo, $p_commit, $p_branch ) {
		$t_revision     = $p_commit->id;
		$t_author       = $p_commit->author->name         ?? 'unknown';
		$t_author_email = $p_commit->author->emailAddress ?? '';
		# DC timestamps are Unix milliseconds
		$t_timestamp    = date( 'Y-m-d H:i:s', intval( $p_commit->authorTimestamp / 1000 ) );
		$t_message      = $p_commit->message ?? '';
		$t_parent       = $p_commit->parents[0]->id ?? '';

		$t_changeset               = new SourceChangeset(
			$p_repo->id, $t_revision, $p_branch, $t_timestamp,
			$t_author, $t_message
		);
		$t_changeset->author_email = $t_author_email;
		$t_changeset->parent       = $t_parent;

		# Fetch files changed in this commit
		$t_changes_url = $this->api_base( $p_repo ) . '/commits/' . $t_revision . '/changes';
		$t_file_items  = $this->api_paged( $p_repo, $t_changes_url );

		foreach( $t_file_items as $t_item ) {
			$t_filename = $t_item->path->toString ?? '';
			if( $t_filename === '' ) {
				continue;
			}

			switch( $t_item->type ?? 'MODIFY' ) {
				case 'ADD':    $t_action = SourceFile::ADDED;    break;
				case 'DELETE': $t_action = SourceFile::DELETED;  break;
				case 'RENAME': $t_action = SourceFile::RENAMED;  break;
				case 'COPY':   $t_action = SourceFile::ADDED;    break;
				default:       $t_action = SourceFile::MODIFIED; break;
			}

			$t_changeset->files[] = new SourceFile( $t_changeset->id, '', $t_filename, $t_action );
		}

		return $t_changeset;
	}
}
