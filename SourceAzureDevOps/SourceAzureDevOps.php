<?php

# Copyright (c) 2020 Stefan Gross
# Licensed under the MIT license

use GuzzleHttp\RequestOptions;

if ( false === include_once( config_get( 'plugin_path' ) . 'Source/MantisSourceGitBasePlugin.class.php' ) ) {
	return;
}

require_once( config_get( 'core_path' ) . 'url_api.php' );

class SourceAzureDevOpsPlugin extends MantisSourceGitBasePlugin {

	const PLUGIN_VERSION = '1.0.1';
	const FRAMEWORK_VERSION_REQUIRED = '2.5.0';

	/**
	 * Error constants
	 */
	const ERROR_BAD_REQUEST = 'bad_request';

	public $type = 'azure';

	/**
	 * @var \GuzzleHttp\Client
	 */
	private $azureApi;

	/**
	 * register plugin
	 */
	public function register() {
		parent::register();

		$this->author = 'Stefan Gross';
		$this->contact = 'stefan.gross@jenoptik.com';
	}

	/**
	 * register errors
	 */
	public function errors() {
		$t_errors_list = array(
			self::ERROR_BAD_REQUEST,
		);

		foreach( $t_errors_list as $t_error ) {
			$t_errors[$t_error] = plugin_lang_get( 'error_' . $t_error, 'SourceAzureDevOps' );
		}

		return array_merge( parent::errors(), $t_errors );
	}

	/**
	 * provides localized clear name for repository type
	 */
	public function show_type() {
		return plugin_lang_get( 'azure' );
	}

	/**
	 * string representing changeset
	 * 
	 * @param SourceRepo $p_repo repository
	 * @param SourceChangeset $p_changeset changeset inside repository
	 * 
	 */
	public function show_changeset( $p_repo, $p_changeset ) {
		$t_ref = substr( $p_changeset->revision, 0, 8 );
		$t_branch = $p_changeset->branch;

		return "$t_branch $t_ref";
	}

	/**
	 * string representation of file including change type
	 * @param SourceRepo $p_repo repository
	 * @param SourceChangeset $p_changeset changeset inside repository
	 * @param SourceFile file referenced in changeset
	 */
	public function show_file( $p_repo, $p_changeset, $p_file ) {
		return  "$p_file->action - $p_file->filename";
	}

	/**
	 *  url to the Azure DevOps repository
	 */
	public function url_repo( $p_repo, $p_changeset=null ) {
		if( empty( $p_repo->info ) ) {
			return '';
		}
		$t_url = $p_repo->url;
		$t_projectname = $p_repo->info['az_projectname'];
		$t_reponame = $p_repo->info['az_reponame'];
		$t_ref = "";

		if ( !is_null( $p_changeset ) ) {
			$t_ref = "/commit/$p_changeset->revision";
		}

		return "$t_url/$t_projectname/_git/$t_reponame$t_ref";
	}

	/**
	 * url to the selected changeset
	 * @param SourceRepo $p_repo repository
	 * @param SourceChangeset $p_changeset changeset inside repository
	
	 */
	public function url_changeset( $p_repo, $p_changeset ) 
	{
		$t_url = $p_repo->url;
		$t_projectname = $p_repo->info['az_projectname'];
		$t_reponame = $p_repo->info['az_reponame'];
		$t_ref = $p_changeset->revision;

		return "$t_url/$t_projectname/_git/$t_reponame/commit/$t_ref";
	}

	/**
	 * url to the file in Azure DevOps
	 * @param SourceRepo $p_repo repository
	 * @param SourceChangeset $p_changeset changeset inside repository
	 * @param SourceFile file referenced in changeset
	 */
	public function url_file( $p_repo, $p_changeset, $p_file ) 
	{
		return $this->url_diff($p_repo, $p_changeset, $p_file);
	}

	/**
	 * url to the file diff in Azure DevOps
	 * @param SourceRepo $p_repo repository
	 * @param SourceChangeset $p_changeset changeset inside repository
	 * @param SourceFile file referenced in changeset
	 */
	public function url_diff( $p_repo, $p_changeset, $p_file ) {
		$t_url = $p_repo->url;
		$t_projectname = $p_repo->info['az_projectname'];
		$t_reponame = $p_repo->info['az_reponame'];
		$t_ref = $p_changeset->revision;
		$t_filename = $p_file->filename;

		return "$t_url/$t_projectname/_git/$t_reponame?oversion=PGC{$t_ref}&mversion=GC{$t_ref}&_a=compare&path={$t_filename}";
	}

	/**
	 * form content for repository property page
	 * @param SourceRepo $p_repo repository
	 */

	public function update_repo_form( $p_repo ) 
	{
		$t_az_organame = null;
		$t_az_projectname = null;
		$t_az_reponame = null;
		$t_az_app_access_token = null;
		$t_az_http_proxy = null;
		$t_az_item_regex = null;

		if ( isset( $p_repo->info['az_projectname'] ) ) {
			$t_az_projectname = $p_repo->info['az_projectname'];
		}

		if ( isset( $p_repo->info['az_reponame'] ) ) {
			$t_az_reponame = $p_repo->info['az_reponame'];
		}
		if ( isset( $p_repo->info['az_app_access_token'] ) ) {
			$t_az_app_access_token = $p_repo->info['az_app_access_token'];
		}

		if ( isset( $p_repo->info['az_http_proxy'] ) ) {
			$t_az_http_proxy = $p_repo->info['az_http_proxy'];
		}

		if ( isset( $p_repo->info['master_branch'] ) ) {
			$t_master_branch = $p_repo->info['master_branch'];
		} else {
			$t_master_branch = $this->get_default_primary_branches();
		}
		
	?>
	<tr>
		<td class="category"><?php echo plugin_lang_get( 'az_projectname' ) ?></td>
		<td>
			<input type="text" name="az_projectname" maxlength="250" size="40" value="<?php echo string_attribute( $t_az_projectname ) ?>"/>
		</td>
	</tr>

	<tr>
		<td class="category"><?php echo plugin_lang_get( 'az_reponame' ) ?></td>
		<td>
			<input type="text" name="az_reponame" maxlength="250" size="40" value="<?php echo string_attribute( $t_az_reponame ) ?>"/>
		</td>
	</tr>

	<tr>
		<td class="category"><?php echo plugin_lang_get( 'az_app_access_token' ) ?></td>
		<td>
			<input type="password" name="az_app_access_token" maxlength="250" size="40" value="<?php echo string_attribute( $t_az_app_access_token ) ?>"/>
		</td>
	</tr>

	<tr>
		<td class="category"><?php echo plugin_lang_get( 'az_http_proxy' ) ?></td>
		<td>
			<input type="text" name="az_http_proxy" maxlength="250" size="40" value="<?php echo string_attribute( $t_az_http_proxy ) ?>"/>
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

	/**
	 * synchronization of form content for repository property page
	 * @param SourceRepo $p_repo repository
	 */

	public function update_repo( $p_repo ) 
	{

		$f_az_projectname = gpc_get_string( 'az_projectname' );
		$f_az_reponame = gpc_get_string( 'az_reponame' );
		$f_az_app_access_token = gpc_get_string( 'az_app_access_token' );
		?><tr><?php echo "ACCESSTOKEN={$f_az_app_access_token}" ;?></tr><?php
		$f_master_branch = gpc_get_string( 'master_branch' );
		$f_az_http_proxy = gpc_get_string( 'az_http_proxy');

		$this->validate_branch_list( $f_master_branch );

		$p_repo->info['az_projectname'] = $f_az_projectname;
		$p_repo->info['az_reponame'] = $f_az_reponame;
		$p_repo->info['az_app_access_token'] = $f_az_app_access_token;
		$p_repo->info['master_branch'] = $f_master_branch;
		$p_repo->info['az_http_proxy'] = $f_az_http_proxy;

		return $p_repo;
	}

	/**
	 * action to for a clean full import of all commits according to repository configuration
	 * @param SourceRepo $p_repo repository
	 */
	public function import_full( $p_repo ) 
	{

		echo '<pre>';
		$t_branch = $p_repo->info['master_branch'];
		if ( is_blank( $t_branch ) ) {
			$t_branch = $this->get_default_primary_branches();
		}

		if ($t_branch != '*')
		{
			$t_branches = array_map( 'trim', explode( ',', $t_branch ) );
		}
		# get all branches of the repository in case of selection '*'
		else
		{
			$t_projectname = $p_repo->info['az_projectname'];
			$t_reponame = $p_repo->info['az_reponame'];
			$t_url = $p_repo->url;

			$t_json = $this->api_get($p_repo, "$t_url/$t_projectname/_apis/git/repositories/$t_reponame/refs?api-version=5.1");
			if($t_json != null)
			{
				# limiting to refs/heads/ is maybe not a good idea in general, but works for my setup, TODO: investigate 
				$prefix = 'refs/heads/';
				foreach ($t_json->value as $ref) {
					$str = $ref->name;
					if (substr($str, 0, strlen($prefix)) == $prefix) {
						$t_branches[] = substr($str, strlen($prefix));
					} 
				}
			}
		}
		$t_changesets = array();

		$t_changeset_table = plugin_table( 'changeset', 'Source' );

		foreach( $t_branches as $t_branch ) {
		$t_changesets = array_merge( $t_changesets, $this->import_commits( $p_repo, $t_branch ) );
		}
		echo '</pre>';

		return $t_changesets;
	}

	public function import_latest( $p_repo ) 
	{
		return $this->import_full( $p_repo );
	}


	public function import_commits( $p_repo, $p_branch='' ) 
	{
		static $s_parents = array();
		static $s_counter = 0;

		$t_projectname = $p_repo->info['az_projectname'];
		$t_reponame = $p_repo->info['az_reponame'];
		$t_url = $p_repo->url;

		$t_changesets = array();

		echo "Retrieving $p_branch ... \n";
		$t_body = array( 'itemVersion' => array( 'versionType' => 'branch', 'version' => $p_branch ) );

		#reading all commits from repository
		#TODO: this can be improved by reading commits with pagination an keeping only nonexisting in array
		#Azure DevOps API does not allow filtering for commits by id, the lower and upper bounds are checked lexically, d'uh
		# (fromCommitId, toCommitId)
		#see https://docs.microsoft.com/en-us/rest/api/azure/devops/git/commits/get%20commits%20batch?view=azure-devops-rest-5.1
		$t_json = $this->api_post( $p_repo, "$t_url/$t_projectname/_apis/git/repositories/$t_reponame/commitsbatch?api-version=5.1" , $t_body);

		if ( false === $t_json || is_null( $t_json ) ) {
			# Some error occured retrieving the commit
			echo "failed.\n";
			return;
		} 
		else if ( !property_exists( $t_json, 'value' ) ) {
			echo "failed ($t_json->message).\n";
			return;
		}

		foreach($t_json->value as $t_commit_ref)
		{
			list( $t_changeset, $t_commit_parents ) = $this->json_commit_changeset( $p_repo, $t_commit_ref, $p_branch );
			#if changeset has been saved succesfully it is given back to underlying SourcePlugin
			#be aware that this plugin calls import in a while(true) loop until no changesets are reported.
			#see ../Source/pages/repo_import_latest.php or ../Source/pages/repo_import_full.php
			if ( $t_changeset ) {
				$t_changesets[] = $t_changeset;
			}
		}
		return $t_changesets;
	}

	private function json_commit_changeset( $p_repo, $p_commit_ref, $p_branch='' ) 
	{
		echo "processing $p_commit_ref->commitId ... ";
		if ( !SourceChangeset::exists( $p_repo->id, $p_commit_ref->commitId ) ) 
		{
			# get full details of commit as we hafe only a slim commit reference here
			$t_commit = $this->api_get( $p_repo, $p_commit_ref->url );
			if ($t_commit == null)
			{
				$t_commit = $p_commit_ref; #fallback, should not happen on sane Azure DevOps API
			}

			$t_changeset = new SourceChangeset(
				$p_repo->id,
				$t_commit->commitId,
				$p_branch,
				$t_commit->author->date,
				$t_commit->author->name,
				$t_commit->comment
			);

			if (property_exists($t_commit, 'parents'))
			{
				if ( count( $t_commit->parents ) > 0 ) {
					$t_parent = $t_commit->parents[0];
					$t_changeset->parent = $t_parent;
				}
			}

			$t_changeset->author_email = $t_commit->author->email;
			$t_changeset->committer = $t_commit->committer->name;
			$t_changeset->committer_email = $t_commit->committer->email;

			# get files for changeset
			if (property_exists($t_commit, '_links'))
			{
				$t_changes = $this->api_get( $p_repo, $t_commit->_links->changes->href );

				if ($t_changes != null)
				{
					foreach($t_changes->changes as $t_change)
					{
						$t_action = '';
						switch($t_change->changeType)
						{
							case "add":
							case "undelete":
								$t_action = SourceFile::ADDED;
							break;
							case "edit":
							case "merge":
								$t_action = SourceFile::MODIFIED;
							break;
							case 'rename':
							case 'sourceRename':
							case 'targetRename':
								$t_action = SourceFile::RENAMED;
							break;
							case 'delete':
								$t_action = SourceFile::DELETED;
							break;
						}
						$t_changeset->files[] = new SourceFile( 0, $t_change->item->commitId, $t_change->item->path, $t_action );
					}
				}
			}
			# VICTORY!
			$t_changeset->save();
			echo "saved.\n";
			return array( $t_changeset, array() );
			
		} 
		else 
		{
			# Also quite successful ;-)
			echo "already exists.\n";
			return array( null, array() );
		}

	}

	/**
	 * Initialize Azure DevOps API for the given repository.
	 *
	 * @param SourceRepo $p_repo Repository
	 */
	private function api_init( $p_repo ) {
		# Initialize Guzzle client if not done already
		if( !$this->azureApi ) {
			$t_options = array(
				'base_uri' => $p_repo->url
			);

			#set http proxy if configured
			if( isset($p_repo->info['az_http_proxy'] ) ) {
				$t_proxy = $p_repo->info['az_http_proxy'];
				if( !is_blank( $t_proxy ) ) {
					$t_options['proxy'] = $t_proxy;
				}
			}

			# i am just taking the api key authorization,
			# i just did not figure out some other possibilities like oauth2,...
			# Set the Authorization header
			if( isset( $p_repo->info['az_app_access_token'] ) ) {
				$t_access_token = $p_repo->info['az_app_access_token'];
				if ( !is_blank( $t_access_token ) ) {
					$t_options[RequestOptions::HEADERS] = array(
						'Authorization' => 'Basic ' .base64_encode(":".$t_access_token),
					);
				}
			}
			$this->azureApi = new GuzzleHttp\Client( $t_options );
		}
		return $this->azureApi;
	}

	/**
	 * Retrieves data from the Azure DevOps API for the given repository by some inputs.
	 *
	 * The JSON data is returned as an stdClass object.
	 *
	 * @param SourceRepo $p_repo   Repository
	 * @param string     $p_path   Azure DevOps API path
	 * @param array      $p_body   content of the POST message
	 * @param string     $p_member Optional top-level member to retrieve
	 *
	 * @return stdClass|stdClass[]|null
	 */
	private function api_post( $p_repo, $p_path, $p_body, $p_member = '' ) {
		$this->api_init( $p_repo );
		$t_json = array();

		try
		{
			$t_response = $this->azureApi->request("POST", $p_path, ['json' => $p_body]);
		}
		catch(Exception $e)
		{
			error_parameters( trim( $e->getMessage() ) );
			plugin_error( self::ERROR_BAD_REQUEST);
			return null;
		}
		
		$t_data = json_decode( $t_response->getBody() );

		if( !is_array( $t_data ) ) {
			$t_json = $t_data;
		}
		else{
			$t_json = array_merge( $t_json, $t_data );
		}

		if( empty( $p_member ) ) {
			return $t_json;
		} elseif( property_exists( $t_json, $p_member ) ) {
			return $t_json->$p_member;
		} else {
			return null;
		}
	}

	/**
	 * Retrieves data from the Azure DevOps API for the given repository.
	 *
	 * The JSON data is returned as an stdClass object.
	 *
	 * @param SourceRepo $p_repo   Repository
	 * @param string     $p_path   Azure DevOps API path
	 * @param string     $p_member Optional top-level member to retrieve
	 *
	 * @return stdClass|stdClass[]|null
	 */
	private function api_get( $p_repo, $p_path, $p_member = '' ) {
		$this->api_init( $p_repo );
		$t_json = array();

		try
		{
			$t_response = $this->azureApi->get( $p_path );
		}
		catch(Exception $e)
		{
			error_parameters( trim( $e->getMessage() ) );
			plugin_error( self::ERROR_BAD_REQUEST);
			return null;
		}
		
		$t_data = json_decode( $t_response->getBody() );

		if( !is_array( $t_data ) ) {
			$t_json = $t_data;
		}
		else {
			$t_json = array_merge( $t_json, $t_data );
		}

		if( empty( $p_member ) ) {
			return $t_json;
		} elseif( property_exists( $t_json, $p_member ) ) {
			return $t_json->$p_member;
		} else {
			return null;
		}
	}
}
