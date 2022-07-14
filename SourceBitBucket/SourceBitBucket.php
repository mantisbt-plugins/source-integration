<?php

# Copyright (c) 2014 Sergey Marchenko
# Licensed under the MIT license

if( false === include_once(config_get( 'plugin_path' ) . 'Source/MantisSourceGitBasePlugin.class.php') ) {
	return;
}

class SourceBitBucketPlugin extends MantisSourceGitBasePlugin {

	const PLUGIN_VERSION = '2.2.0';
	const FRAMEWORK_VERSION_REQUIRED = '2.5.0';

	/**
	 * Error constants
	 */
	const ERROR_BITBUCKET_API = 'bitbucket_api';

	protected $main_url = "https://bitbucket.org/";
	protected $api_url = 'https://bitbucket.org/api/2.0/';

	public $type = 'bb';

	public $linkPullRequest = '/pull-request/%s';

	public function register() {
		parent::register();

		$this->author  = 'Sergey Marchenko';
		$this->contact = 'sergey@mzsl.ru';
	}

	function errors() {
		$t_errors_list = array(
			self::ERROR_BITBUCKET_API,
		);

		foreach( $t_errors_list as $t_error ) {
			$t_errors[$t_error] = plugin_lang_get( 'error_' . $t_error );
		}

		return array_merge( parent::errors(), $t_errors );
	}

	public function show_type() {
		return plugin_lang_get( 'bitbucket' );
	}

	public function show_changeset( $p_repo, $p_changeset ) {
		$t_ref    = substr( $p_changeset->revision, 0, 8 );
		$t_branch = $p_changeset->branch;

		return "$t_branch $t_ref";
	}

	public function show_file( $p_repo, $p_changeset, $p_file ) {
		$t_filename = $p_file->getFilename();
		return "$p_file->action - $t_filename";
	}

	public function url_repo( $p_repo, $p_changeset = null ) {
		if( empty($p_repo->info) ) return '';

		$t_username = $p_repo->info['bit_username'];
		$t_reponame = $p_repo->info['bit_reponame'];
		$t_ref      = '';
		if( !is_null( $p_changeset ) )
			$t_ref = "/src/?at=$p_changeset->revision";

		return $this->main_url . "$t_username/$t_reponame$t_ref";
	}

	public function url_changeset( $p_repo, $p_changeset ) {
		$t_username = $p_repo->info['bit_username'];
		$t_reponame = $p_repo->info['bit_reponame'];
		$t_ref      = $p_changeset->revision;
		return $this->main_url . "$t_username/$t_reponame/commits/$t_ref";
	}

	public function url_file( $p_repo, $p_changeset, $p_file ) {
		# Can't link to a deleted file
		if( $p_file->action == SourceFile::DELETED ) {
			return '';
		}

		$t_ref      = $p_changeset->revision;
		$t_filename = $p_file->getFilename();
		return $this->main_url . "$t_username/$t_reponame/src/$t_ref/$t_filename";
	}

	public function url_diff( $p_repo, $p_changeset, $p_file ) {
		# No point viewing the diff to a deleted file
		if( $p_file->action == SourceFile::DELETED ) {
			return '';
		}

		$t_ref      = $p_changeset->revision;
		$t_filename = $p_file->getFilename();
		return $this->main_url . "$t_username/$t_reponame/diff/$t_filename?diff2=$t_ref";
	}

	public function update_repo_form( $p_repo ) {
		$t_bit_basic_login = null;
		$t_bit_basic_pwd   = null;
		$t_bit_username    = null;
		$t_bit_reponame    = null;

		if( isset($p_repo->info['bit_basic_login']) ) {
			$t_bit_basic_login = $p_repo->info['bit_basic_login'];
		}
		if( isset($p_repo->info['bit_basic_pwd']) ) {
			$t_bit_basic_pwd = $p_repo->info['bit_basic_pwd'];
		}

		if( isset($p_repo->info['bit_username']) ) {
			$t_bit_username = $p_repo->info['bit_username'];
		}

		if( isset($p_repo->info['bit_reponame']) ) {
			$t_bit_reponame = $p_repo->info['bit_reponame'];
		}

		if( isset($p_repo->info['master_branch']) ) {
			$t_master_branch = $p_repo->info['master_branch'];
		} else {
			$t_master_branch = $this->get_default_primary_branches();
		}
		?>
		<tr>
			<th class="category">
				<label for="bit_basic_login" class="category">
					<?php echo plugin_lang_get( 'bit_basic_login' ) ?>
				</label>
			</th>

			<td>
				<input id="bit_basic_login" name="bit_basic_login"
					   type="text" maxlength="250" size="40"
					   value="<?php echo string_attribute( $t_bit_basic_login ) ?>"/>
			</td>
		</tr>
		<tr>
			<th class="category">
				<label for="bit_basic_pwd">
					<?php echo plugin_lang_get( 'bit_basic_pwd' ) ?>
				</label>
			</th>
			<td>
				<input id="bit_basic_pwd" name="bit_basic_pwd"
					   type="password" maxlength="250" size="40"
					   value="<?php echo string_attribute( $t_bit_basic_pwd ) ?>"/>
			</td>
		</tr>
		<tr>
			<th class="category">
				<label for="bit_username">
					<?php echo plugin_lang_get( 'bit_username' ) ?>
				</label>
			</th>
			<td>
				<input id="bit_username" name="bit_username"
					   type="text" maxlength="250" size="40"
					   value="<?php echo string_attribute( $t_bit_username ) ?>"/>
			</td>
		</tr>
		<tr>
			<th class="category">
				<label for="bit_reponame">
					<?php echo plugin_lang_get( 'bit_reponame' ) ?>
				</label>
			</th>
			<td>
				<input id="bit_reponame" name="bit_reponame"
					   type="text" maxlength="250" size="40"
					   value="<?php echo string_attribute( $t_bit_reponame ) ?>"/>
			</td>
		</tr>
		<tr>
			<td class="spacer"></td>
		</tr>
		<tr>
			<th class="category">
				<label for="master_branch">
					<?php echo plugin_lang_get( 'master_branch' ) ?>
				</label>
			</th>
			<td>
				<input id="master_branch" name="master_branch"
					   type="text" maxlength="250" size="40"
					   value="<?php echo string_attribute( $t_master_branch ) ?>"/>
			</td>
		</tr>
	<?php
	}

	public function update_repo( $p_repo ) {
		$f_basic_login   = gpc_get_string( 'bit_basic_login' );
		$f_basic_pwd     = gpc_get_string( 'bit_basic_pwd' );
		$f_bit_username  = gpc_get_string( 'bit_username' );
		$f_bit_reponame  = gpc_get_string( 'bit_reponame' );
		$f_master_branch = gpc_get_string( 'master_branch' );

		$this->validate_branch_list( $f_master_branch );

		$p_repo->info['bit_basic_login'] = $f_basic_login;
		$p_repo->info['bit_basic_pwd']   = $f_basic_pwd;
		$p_repo->info['bit_username']    = $f_bit_username;
		$p_repo->info['bit_reponame']    = $f_bit_reponame;
		$p_repo->info['master_branch']   = $f_master_branch;

		return $p_repo;
	}

	private function api_url( $p_path ) {
		return $this->api_url . $p_path;
	}

	private function api_json_url( $p_repo, $p_url ) {
		$t_data = $this->url_get( $p_repo, $p_url );
		$t_json = json_decode( utf8_encode( $t_data ) );
		if( json_last_error() != JSON_ERROR_NONE ) {
			error_parameters( $t_data );
			plugin_error( self::ERROR_BITBUCKET_API );
		}
		return $t_json;
	}

	private function api_json_url_values( $p_repo, $p_url ) {
		$t_json = $this->api_json_url( $p_repo, $p_url );
		$values = [];

		if( property_exists( $t_json, 'values' ) ) {
		    foreach( $t_json->values as $t_item ) {
				$values[] = $t_item;
			}
		}

		if( property_exists( $t_json, 'next' ) ) {
			$values = array_merge( $values, $this->api_json_url_values( $p_repo, $t_json->next ) );
		}

		return $values;
	}

	public function precommit() {}

	public function commit( $p_repo, $p_data ) {
		$t_commits = array();

		foreach ( $p_data['commits'] as $t_commit ) {
			$t_commits[] = $t_commit['id'];
		}

		$t_refData = explode( '/', $p_data['ref'] );
		$t_branch  = $t_refData[2];

		return $this->import_commits( $p_repo, $t_commits, $t_branch );
	}

	public function import_full( $p_repo, $p_use_cache = true ) {
		echo '<pre>';
		$t_branch = $p_repo->info['master_branch'];

		if( is_blank( $t_branch ) ) {
			$t_branch = $this->get_default_primary_branches();
		}

		$t_username = $p_repo->info['bit_username'];
		$t_reponame = $p_repo->info['bit_reponame'];

		if( $t_branch != '*' ) {
			$t_branches = array_map( 'trim', explode( ',', $t_branch ) );
		} else {
			$t_uri  = $this->api_url( "repositories/$t_username/$t_reponame/refs/branches" );
			$t_json = $this->api_json_url_values( $p_repo, $t_uri );
			$t_branches = array();
			foreach( $t_json as $t_branch ) {
				if( isset($t_branch->name) ) {
					$t_branches[] = $t_branch->name;
				}
			}
		}
		$t_changesets = array();

		$t_changeset_table = plugin_table( 'changeset', 'Source' );

		foreach ( $t_branches as $t_branch ) {
			$t_query  = "SELECT parent FROM $t_changeset_table
				WHERE repo_id=" . db_param() . ' AND branch=' . db_param() .
						' ORDER BY timestamp ASC';
			$t_result = db_query( $t_query, array($p_repo->id, $t_branch), 1 );

			$t_commits = array($t_branch);

			if( db_num_rows( $t_result ) > 0 ) {
				$t_parent = db_result( $t_result );
				echo "Oldest '$t_branch' branch parent: '$t_parent'\n";

				if( !empty($t_parent) ) {
					$t_commits[] = $t_parent;
				}
			}
			if( $p_use_cache ) {
				foreach ( $t_commits as $t_commit_id ) {
					$this->load_all_commits( $p_repo, $t_commit_id );
				}
			}

			$t_changesets = array_merge(
					$t_changesets,
					$this->import_commits( $p_repo, $t_commits, $t_branch )
				);
		}

		echo '</pre>';

		return $t_changesets;
	}

	public function import_latest( $p_repo ) {
		return $this->import_full( $p_repo, false );
	}

	private $commits_cache = array();

	private function load_all_commits( $p_repo, $p_commit_id, $p_next = '' ) {
		$t_username = $p_repo->info['bit_username'];
		$t_reponame = $p_repo->info['bit_reponame'];

		$t_url  = empty($p_next)
			? $this->api_url( "repositories/$t_username/$t_reponame/commits/$p_commit_id" )
			: $p_next;
		$t_json = $this->api_json_url( $p_repo, $t_url );

		if( property_exists( $t_json, 'values' ) ) {
			foreach ( $t_json->values as $t_item ) {
				$this->commits_cache[$t_item->hash] = $t_item;
			}
		}
		if( property_exists( $t_json, 'next' ) ) {
			$this->load_all_commits( $p_repo, $p_commit_id, $t_json->next );
		}
	}

	public function import_commits( $p_repo, $p_commit_ids, $p_branch = '' ) {
		static $s_parents = array();
		static $s_counter = 0;

		$t_username = $p_repo->info['bit_username'];
		$t_reponame = $p_repo->info['bit_reponame'];

		if( is_array( $p_commit_ids ) ) {
			$s_parents = array_merge( $s_parents, $p_commit_ids );
		} else {
			$s_parents[] = $p_commit_ids;
		}

		// If branch name contains a '/', lookup its target commit
		// Workaround for https://jira.atlassian.com/browse/BCLOUD-9969
		// https://github.com/mantisbt-plugins/source-integration/issues/376
		foreach( $s_parents as &$t_branch ) {
			if( strpos( $t_branch, '/' ) !== false ) {
				echo "Looking up target commit for $t_branch ... ";
				$t_url  = $this->api_url( "repositories/$t_username/$t_reponame/refs/branches/$t_branch" );
				$t_json = $this->api_json_url( $p_repo, $t_url );
				if( null !== $t_json ) {
					$t_branch = $t_json->target->hash;
				}
			}
		}

		$t_changesets = array();

		while( count( $s_parents ) > 0 && $s_counter < 200 ) {
			$t_commit_id = array_shift( $s_parents );

			echo "Retrieving $t_commit_id ... ";
			$t_json = null;
			if( empty($this->commits_cache[$t_commit_id]) ) {
				$t_url  = $this->api_url( "repositories/$t_username/$t_reponame/commit/$t_commit_id" );
				$t_json = $this->api_json_url( $p_repo, $t_url );
			} else {
				$t_json = $this->commits_cache[$t_commit_id];
			}

			if( false === $t_json || is_null( $t_json ) ) {
				# Some error occured retrieving the commit
				echo "failed.\n";
				continue;
			} else if( !property_exists( $t_json, 'hash' ) ) {
				echo 'failed (', $t_json->error->message, ").\n";
				continue;
			}

			list($t_changeset, $t_commit_parents) = $this->json_commit_changeset( $p_repo, $t_json, $p_branch );
			if( $t_changeset ) {
				$t_changesets[] = $t_changeset;
			}

			$s_parents = array_merge( $s_parents, $t_commit_parents );
		}

		$s_counter = 0;
		return $t_changesets;
	}

	private function get_author_name( $p_row ) {
		return trim( substr( $p_row, 0, strpos( $p_row, '<' ) ) );
	}

	private function get_author_email( $p_row ) {
		$start  = strpos( $p_row, '<' ) + 1;
		$length = strpos( $p_row, '>' ) - $start;
		return trim( substr( $p_row, $start, $length ) );
	}

	private function json_commit_changeset( $p_repo, $p_json, $p_branch = '' ) {

		echo "processing $p_json->hash ... ";
		if( !SourceChangeset::exists( $p_repo->id, $p_json->hash ) ) {
			$t_parents = array();
			foreach ( $p_json->parents as $t_parent ) {
				$t_parents[] = $t_parent->hash;
			}

			$t_changeset = new SourceChangeset(
				$p_repo->id,
				$p_json->hash,
				$p_branch,
				$p_json->date,
				$this->get_author_name( $p_json->author->raw ),
				$p_json->message
			);

			if( count( $p_json->parents ) > 0 ) {
				$t_parent            = $p_json->parents[0];
				$t_changeset->parent = $t_parent->hash;
			}

			$t_changeset->author_email    = $this->get_author_email( $p_json->author->raw );
			$t_changeset->committer       = $t_changeset->author;
			$t_changeset->committer_email = $t_changeset->author_email;


			$t_username  = $p_repo->info['bit_username'];
			$t_reponame  = $p_repo->info['bit_reponame'];
			$t_commit_id = $p_json->hash;
			$t_url       = $this->api_url( "repositories/$t_username/$t_reponame/diffstat/$t_commit_id" );
			$t_files     = $this->api_json_url_values( $p_repo, $t_url );
			if( !empty($t_files) ) {
				foreach ( $t_files as $t_file ) {
					$t_filename = $t_file->new->path;
					switch( $t_file->status ) {
						case 'added':
							$t_action = SourceFile::ADDED;
							break;
						case 'modified':
							$t_action = SourceFile::MODIFIED;
							break;
						case 'removed':
							$t_action = SourceFile::DELETED;
							$t_filename = $t_file->old->path;
							break;
						case 'renamed':
							$t_action = SourceFile::RENAMED;
							$t_filename = $t_file->old->path . SourceFile::RENAMED_SEPARATOR . $t_file->new->path;
							break;
						default:
							$t_action = SourceFile::UNKNOWN . ' ' . $t_file->status;
					}
					$t_changeset->files[] = new SourceFile(0, '', $t_filename, $t_action );
				}
			}

			$t_changeset->save();

			echo "saved.\n";
			return array($t_changeset, $t_parents);
		} else {
			echo "already exists.\n";
			return array(null, array());
		}
	}

	public function url_get( $p_repo, $p_url ) {
		$t_user_pass = $p_repo->info['bit_basic_login'] . ':' . $p_repo->info['bit_basic_pwd'];
		# Use the PHP cURL extension
		if( function_exists( 'curl_init' ) ) {
			$t_curl = curl_init( $p_url );
			# cURL options
			$t_curl_opt[CURLOPT_USERPWD]        = $t_user_pass;
			$t_curl_opt[CURLOPT_RETURNTRANSFER] = true;

			$t_vers                        = curl_version();
			$t_curl_opt[CURLOPT_USERAGENT] =
				'mantisbt/' . MANTIS_VERSION . ' php-curl/' . $t_vers['version'];

			# Set the options
			curl_setopt_array( $t_curl, $t_curl_opt );

			# Retrieve data
			$t_data = curl_exec( $t_curl );
			curl_close( $t_curl );

			if( $t_data !== false ) {
				return $t_data;
			}
		}
		# Last resort system call
		$t_url = escapeshellarg( $p_url ); //use -u user:pass
		return shell_exec( 'curl -u ' . $t_user_pass . ' ' . $t_url );
	}

}
