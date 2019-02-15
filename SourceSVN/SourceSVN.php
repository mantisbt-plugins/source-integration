<?php

# Copyright (c) 2012 John Reese
# Licensed under the MIT license

if ( false === include_once( config_get( 'plugin_path' ) . 'Source/MantisSourcePlugin.class.php' ) ) {
	return;
}

class SourceSVNPlugin extends MantisSourcePlugin {

	const PLUGIN_VERSION = '2.1.1';
	const FRAMEWORK_VERSION_REQUIRED = '2.0.0';

	/**
	 * Error constants
	 */
	const ERROR_PATH_INVALID = 'path_invalid';
	const ERROR_SVN_RUN = 'svn_run';
	const ERROR_SVN_CMD = 'svn_cmd';

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

	public function config() {
		return array(
			'svnpath' => '',
			'svnargs' => '',
			'svnssl' => false,
			'winstart' => false,
		);
	}

	public function errors() {
		$t_errors_list = array(
			self::ERROR_PATH_INVALID,
			self::ERROR_SVN_RUN,
			self::ERROR_SVN_CMD,
		);

		foreach( $t_errors_list as $t_error ) {
			$t_errors[$t_error] = plugin_lang_get( 'error_' . $t_error, 'SourceSVN' );
		}

		return array_merge( parent::errors(), $t_errors );
	}

	public $type = 'svn';

	public $configuration = true;

	public function show_type() {
		return plugin_lang_get( 'svn' );
	}

	public function show_changeset( $p_repo, $p_changeset ) {
		return "$p_changeset->branch r$p_changeset->revision";
	}

	public function show_file( $p_repo, $p_changeset, $p_file ) {
		return $p_file->action . ' - ' . $p_file->filename;
	}

	public function url_repo( $p_repo, $p_changeset=null ) {}

	public function url_changeset( $p_repo, $p_changeset ) {}

	public function url_file( $p_repo, $p_changeset, $p_file ) {}

	public function url_diff( $p_repo, $p_changeset, $p_file ) {}

	public function update_repo_form( $p_repo ) {
		$t_svn_username = isset( $p_repo->info['svn_username'] ) ? $p_repo->info['svn_username'] : '';
		$t_svn_password = isset( $p_repo->info['svn_password'] ) ? $p_repo->info['svn_password'] : '';
		$t_standard_repo = isset( $p_repo->info['standard_repo'] ) ? $p_repo->info['standard_repo'] : '';
		$t_trunk_path = isset( $p_repo->info['trunk_path'] ) ? $p_repo->info['trunk_path'] : '';
		$t_branch_path = isset( $p_repo->info['branch_path'] ) ? $p_repo->info['branch_path'] : '';
		$t_tag_path = isset( $p_repo->info['tag_path'] ) ? $p_repo->info['tag_path'] : '';
		$t_ignore_paths = isset( $p_repo->info['ignore_paths'] ) ? $p_repo->info['ignore_paths'] : '';

?>
<tr>
	<td class="category"><?php echo plugin_lang_get( 'svn_username' ) ?></td>
	<td>
		<input type="text" name="svn_username" maxlength="250" size="40" value="<?php echo string_attribute( $t_svn_username ) ?>"/>
	</td>
</tr>
<tr>
	<td class="category"><?php echo plugin_lang_get( 'svn_password' ) ?></td>
	<td>
		<input type="text" name="svn_password" maxlength="250" size="40" value="<?php echo string_attribute( $t_svn_password ) ?>"/>
	</td>
</tr>
<tr>
	<td class="category"><?php echo plugin_lang_get( 'standard_repo' ) ?></td>
	<td>
		<label>
			<input name="standard_repo" type="checkbox" class="ace" <?php echo ($t_standard_repo ? 'checked="checked"' : '') ?>/>
			<span class="lbl"></span>
		</label>
	</td>
</tr>
<tr>
	<td class="category"><?php echo plugin_lang_get( 'trunk_path' ) ?></td>
	<td>
		<input type="text" name="trunk_path" maxlength="250" size="40" value="<?php echo string_attribute( $t_trunk_path ) ?>"/>
	</td>
</tr>
<tr>
	<td class="category"><?php echo plugin_lang_get( 'branch_path' ) ?></td>
	<td>
		<input type="text" name="branch_path" maxlength="250" size="40" value="<?php echo string_attribute( $t_branch_path ) ?>"/>
	</td>
</tr>
<tr>
	<td class="category"><?php echo plugin_lang_get( 'tag_path' ) ?></td>
	<td>
		<input type="text" name="tag_path" maxlength="250" size="40" value="<?php echo string_attribute( $t_tag_path ) ?>"/>
	</td>
</tr>
<tr>
	<td class="category"><?php echo plugin_lang_get( 'ignore_paths' ) ?></td>
	<td>
		<label>
			<input name="ignore_paths" type="checkbox" class="ace" <?php echo ($t_ignore_paths ? 'checked="checked"' : '') ?>/>
			<span class="lbl"></span>
		</label>
	</td>
</tr>
		<?php
	}

	public function update_repo( $p_repo ) {
		$p_repo->info['svn_username'] = gpc_get_string( 'svn_username' );
		$p_repo->info['svn_password'] = gpc_get_string( 'svn_password' );
		$p_repo->info['standard_repo'] = gpc_get_bool( 'standard_repo', false );
		$p_repo->info['trunk_path'] = gpc_get_string( 'trunk_path' );
		$p_repo->info['branch_path'] = gpc_get_string( 'branch_path' );
		$p_repo->info['tag_path'] = gpc_get_string( 'tag_path' );
		$p_repo->info['ignore_paths'] = gpc_get_bool( 'ignore_paths', false );

		return $p_repo;
	}

	private static $config_form_handled = false;

	public function update_config_form() {
		# Prevent more than one SVN class from outputting form elements.
		if ( !SourceSVNPlugin::$config_form_handled ) {
			SourceSVNPlugin::$config_form_handled = true;

			$t_svnpath = plugin_config_get( 'svnpath', '' );
			$t_svnargs = plugin_config_get( 'svnargs', '' );
			$t_svnssl = plugin_config_get( 'svnssl', '' );
			$t_winstart = plugin_config_get( 'winstart', '' );

?>
<table class="table table-striped table-bordered table-condensed">
	<tr class="spacer"></tr>
	<tr>
		<td colspan="2"><h4><?php echo plugin_lang_get( 'title' ) ?></h4></td>
	</tr>
	<tr>
		<td class="category"><?php echo plugin_lang_get( 'svnpath' ) ?></td>
		<td>
			<input type="text" name="plugin_SourceSVN_svnpath" value="<?php echo string_attribute( $t_svnpath ) ?>" size="40"/>
		</td>
	</tr>
	<tr>
		<td class="category"><?php echo plugin_lang_get( 'svnargs' ) ?></td>
		<td>
			<input type="text" name="plugin_SourceSVN_svnargs" value="<?php echo string_attribute( $t_svnargs ) ?>" size="40"/>
		</td>
	</tr>
	<tr>
		<td class="category"><?php echo plugin_lang_get( 'svnssl' ) ?></td>
		<td>
			<label>
				<input name="plugin_SourceSVN_svnssl" type="checkbox" class="ace" <?php check_checked( (bool)$t_svnssl ) ?>/>
				<span class="lbl"></span>
			</label>
		</td>
	</tr>
	<tr>
		<td class="category"><?php echo plugin_lang_get( 'winstart' ) ?></td>
		<td>
			<label>
				<input name="plugin_SourceSVN_winstart" type="checkbox" class="ace" <?php check_checked( (bool)$t_winstart ) ?>/>
				<span class="lbl"></span>
			</label>
		</td>
	</tr>
	<tr class="spacer"></tr>
</table>

<?php
		}
	}

	public function update_config() {
		# Prevent more than one SVN class from handling form elements.
		if ( !SourceSVNPlugin::$config_form_handled ) {
			SourceSVNPlugin::$config_form_handled = true;

			$f_svnpath = gpc_get_string( 'plugin_SourceSVN_svnpath', '' );
			$t_svnpath = plugin_config_get( 'svnpath', '' );

			$f_svnpath = rtrim( $f_svnpath, DIRECTORY_SEPARATOR );

			if ( $f_svnpath != $t_svnpath ) {
				if ( is_blank( $f_svnpath ) ) {
					plugin_config_delete( 'svnpath' );

				} else {
					# be sure that the path is valid
					if ( ( $t_binary = SourceSVNPlugin::svn_binary( $f_svnpath, true ) ) != 'svn' ) {
						plugin_config_set( 'svnpath', $f_svnpath );
					} else {
						plugin_error( self::ERROR_PATH_INVALID );
					}
				}
			}

			$f_svnargs = gpc_get_string( 'plugin_SourceSVN_svnargs', '' );
			if ( $f_svnargs != plugin_config_get( 'svnargs', '' ) ) {
				plugin_config_set( 'svnargs', $f_svnargs );
			}

			$f_svnssl = gpc_get_bool( 'plugin_SourceSVN_svnssl', false );
			if ( $f_svnssl != plugin_config_get( 'svnssl', false ) ) {
				plugin_config_set( 'svnssl', $f_svnssl );
			}

			$f_winstart = gpc_get_bool( 'plugin_SourceSVN_winstart', false );
			if ( $f_winstart != plugin_config_get( 'winstart', false ) ) {
				plugin_config_set( 'winstart', $f_winstart );
			}
		}
	}

	public function commit( $p_repo, $p_data ) {
		if ( preg_match( '/(\d+)/', $p_data, $p_matches ) ) {

			// Detect if there is a svn:log revprop change, assume not
			$t_revprop = gpc_get_bool( 'revprop', false );

			$t_url = $p_repo->url;
			$t_revision = $p_matches[1];
			$t_svnlog_xml = $this->svn_run( "log -v $t_url -r$t_revision --xml", $p_repo );

			if ( $t_revprop == false ) {
				if ( SourceChangeset::exists( $p_repo->id, $t_revision ) ) {
					echo sprintf( plugin_lang_get( 'revision_already_committed' ), $t_revision );
					return null;
				}
			}

			return $this->process_svn_log_xml( $p_repo, $t_svnlog_xml, $t_revprop );
		}
	}

	public function import_full( $p_repo ) {

		$t_changeset_table = plugin_table( 'changeset', 'Source' );

		$t_max_query = "SELECT revision FROM $t_changeset_table
						WHERE repo_id=" . db_param() . '
						ORDER BY CAST( revision AS DECIMAL ) DESC';
		$t_db_revision = db_result( db_query( $t_max_query, array( $p_repo->id ), 1 ) );

		$t_url = $p_repo->url;
		$t_rev = ( false === $t_db_revision ? 0 : $t_db_revision + 1 );

		# finding max revision
		$t_svninfo_xml = $this->svn_run( "info $t_url --xml", $p_repo );
		# create parser
		$t_svninfo_parsed_xml = new SimpleXMLElement($t_svninfo_xml);

		$t_max_rev = (integer) $t_svninfo_parsed_xml->entry->commit['revision'];

		# this is required because invalid revision number render invalid xml output for svn log
		if($t_rev > $t_max_rev) {
			echo "<pre>Next lookup revision ($t_rev) exceeds head revision ($t_max_rev), skipping...</pre>";
			return array();
		}

		echo '<pre>';
		echo "Requesting svn log for {$p_repo->name} starting with revision {$t_rev}...\n";

		# get the svn log in xml format
		$t_svnlog_xml = $this->svn_run( "log -v -r $t_rev:HEAD --limit 200 $t_url --xml", $p_repo );

		# parse the changesets
		$t_changesets = $this->process_svn_log_xml( $p_repo, $t_svnlog_xml );

		echo "</pre>";
		return $t_changesets;
	}

	public function import_latest( $p_repo ) {
		return $this->import_full( $p_repo );
	}

	/**
	 * Execute SVN command, catching & raising errors in both
	 *   execution and output
	 * @param  string     $p_cmd  Command and any parameters
	 * @param  SourceRepo $p_repo Repository to access
	 * @return string             Output of SVN command
	 */
	private function svn_run( $p_cmd, $p_repo = null )
	{
		# Get "base" SVN command, including any configured parameters
		$t_svn_exe = self::svn_call( $p_repo );
		# Append specific cmd & params
		$t_svn_cmd = "$t_svn_exe $p_cmd";

		$t_svn_proc = proc_open(
			$t_svn_cmd,
			array( array( 'pipe', 'r' ), array( 'pipe', 'w' ), array( 'pipe', 'w' ) ),
			$t_pipes
		);

		# Check & report execution failure
		if( $t_svn_proc === false ) {
			plugin_error( self::ERROR_SVN_RUN );
		}

		# Get output of the process & clean up
		$t_stderr = stream_get_contents( $t_pipes[2] );
		fclose( $t_pipes[2] );
		$t_svn_out = stream_get_contents( $t_pipes[1] );
		fclose( $t_pipes[1] );
		fclose( $t_pipes[0] );
		proc_close( $t_svn_proc );

		# Error handling
		if( $t_stderr ) {
			error_parameters( trim( $t_stderr ) );
			plugin_error( self::ERROR_SVN_CMD );
		}

		return $t_svn_out;
	}

	private function svn_call( $p_repo=null ) {
		static $s_call;

		# Get a full binary call, including configured parameters
		if ( is_null( $s_call ) ) {
			plugin_push_current( 'SourceSVN' );

			$s_call = self::svn_binary() . ' --non-interactive';

			if ( plugin_config_get( 'svnssl', false ) ) {
				$s_call .= ' --trust-server-cert';
			}

			$t_svnargs = plugin_config_get( 'svnargs', '' );
			if ( !is_blank( $t_svnargs ) ) {
				$s_call .= " $t_svnargs";
			}

			plugin_pop_current();
		}

		# If not given a repo, just return the base SVN binary
		if ( is_null( $p_repo ) ) {
			return $s_call;
		}

		$t_call = $s_call;

		# With a repo, add arguments for repo info
		$t_username = escapeshellarg($p_repo->info['svn_username']);
		$t_password = escapeshellarg($p_repo->info['svn_password']);

		if ( !is_blank( $t_username ) ) {
			$t_call .= ' --username ' . $t_username;
		}
		if ( !is_blank( $t_password ) ) {
			$t_call .= ' --password ' . $t_password;
		}

		# Done
		return $t_call;
	}

	/**
	 * Generate, validate, and cache the SVN binary path.
	 * @param string Path to SVN
	 * @param boolean Reset cached value
	 * @return string SVN binary
	 */
	public static function svn_binary( $p_path=null, $p_reset=false ) {
		static $s_binary;

		if ( is_null( $s_binary ) || $p_reset ) {
			if ( is_null( $p_path ) ) {
				$t_path = plugin_config_get( 'svnpath', '' );
			} else {
				$t_path = $p_path;
			}

			if ( !is_blank( $t_path ) && is_dir( $t_path ) ) {

				# Linux / UNIX paths
				$t_binary = $t_path . DIRECTORY_SEPARATOR . 'svn';
				if ( is_file( $t_binary ) && is_executable( $t_binary ) ) {
					return $s_binary = $t_binary;
				}

				# Windows paths
				$t_binary = $t_path . DIRECTORY_SEPARATOR . 'svn.exe';
				if ( is_file( $t_binary ) && is_executable( $t_binary ) ) {
					if ( plugin_config_get( 'winstart', '' ) ) {
						return "start /B /D \"{$t_path}\" svn.exe";
					} else {
						return $s_binary = $t_binary;
					}
				}

			}

			# Generic pathless call
			$s_binary = 'svn';
		}

		return $s_binary;
	}


	/**
	 * Parse the svn log output (with --xml option)
	 * @param SourceRepo SVN repository object
	 * @param string SVN log (XML formated)
	 * @param boolean REVPROP change flag
	 * @return SourceChangeset[] Changesets for the provided input (empty on error)
	 */
	private function process_svn_log_xml( $p_repo, $p_svnlog_xml, $p_revprop = false ) {
		$t_changesets = array();
		$t_changeset = null;
		$t_comments = '';
		$t_count = 0;

		$t_trunk_path = $p_repo->info['trunk_path'];
		$t_branch_path = $p_repo->info['branch_path'];
		$t_tag_path = $p_repo->info['tag_path'];
		$t_ignore_paths = $p_repo->info['ignore_paths'];

		echo "Processing svn log (xml)...\n";
		# empty log?
		if( trim($p_svnlog_xml) === '' )
			return array();

		# parse XML
		$t_xml = new SimpleXMLElement($p_svnlog_xml);

		# timezone for conversions in loca
		$t_utc = new DateTimeZone('UTC');
		$t_localtz = new DateTimeZone( date_default_timezone_get() );

		foreach( $t_xml->logentry as $t_entry ) {
			# time conversion to local time
			$t_date = new DateTime( $t_entry->date, $t_utc );
			$t_date->setTimeZone($t_localtz);

			# create the changeset
			$t_str_date = $t_date->format('Y-m-d H:i:s');
			$t_changeset = new SourceChangeset( $p_repo->id, (integer)$t_entry['revision'], '', $t_str_date, (string)$t_entry->author, '');

			# files
			if(isset($t_entry->paths->path)){
				foreach( $t_entry->paths->path as $t_path ) {
					switch( (string)$t_path['action'] ) {
						case 'A': $t_action = 'add'; break;
						case 'D': $t_action = 'rm'; break;
						case 'M': $t_action = 'mod'; break;
						case 'R': $t_action = 'mv'; break;
						default: $t_action = (string)$t_path['action'];
					}

					$t_file = new SourceFile( $t_changeset->id, '', (string)$t_path, $t_action );
					$t_changeset->files[] = $t_file;

					# Branch-checking
					if( is_blank( $t_changeset->branch ) ) {
						# Look for standard trunk/branches/tags information
						if( $p_repo->info['standard_repo'] ) {
							if( preg_match( '@/(?:(trunk)|(?:branches|tags)/([^/]+))@i', $t_file->filename, $t_matches ) ) {
								if( !is_blank( $t_matches[1] ) ) {
									$t_changeset->branch = $t_matches[1];
								} else {
									$t_changeset->branch = $t_matches[2];
								}
							}
						} else {
							# Look for non-standard trunk path
							if( !is_blank( $t_trunk_path ) && preg_match( '@^/*(' . $t_trunk_path . ')@i', $t_file->filename, $t_matches ) ) {
								$t_changeset->branch = $t_matches[1];

							# Look for non-standard branch path
							} else if( !is_blank( $t_branch_path ) && preg_match( '@^/*(?:' . $t_branch_path . ')/([^/]+)@i', $t_file->filename, $t_matches ) ) {
								$t_changeset->branch = $t_matches[1];

							# Look for non-standard tag path
							} else if( !is_blank( $t_tag_path ) && preg_match( '@^/*(?:' . $t_tag_path . ')/([^/]+)@i', $t_file->filename, $t_matches ) ) {
								$t_changeset->branch = $t_matches[1];

							# Fall back to just using the root folder as the branch name
							} else if( !$t_ignore_paths && preg_match( '@/([^/]+)@', $t_file->filename, $t_matches ) ) {
								$t_changeset->branch = $t_matches[1];
							}
						}
					} # end is_blank( $t_changeset->branch ) if
				} # end files in revision ($t_path) foreach
			} else { # no file paths set
				$t_changeset->branch = $t_trunk_path;
			}

			# get the log message
			$t_changeset->message = (string)$t_entry->msg;

			// Save changeset and append to array
			if( !is_null( $t_changeset) ) {
				if( !is_blank( $t_changeset->branch ) ) {
					if( $p_revprop ) {
						echo plugin_lang_get( 'revprop_detected' );
						$t_existing_changeset = SourceChangeset::load_by_revision( $p_repo, $t_changeset->revision );
						$t_changeset->id = $t_existing_changeset->id;
						$t_changeset->user_id = $t_existing_changeset->user_id;
						$t_changeset->files = $t_existing_changeset->files;
						$t_old_bugs = array_unique( Source_Parse_Buglinks( $t_existing_changeset->message ));
						$t_new_bugs = array_unique( Source_Parse_Buglinks( $t_changeset->message ));
						if( count( $t_old_bugs ) >= count( $t_new_bugs )) {
							$t_changeset->__bugs = array_diff( $t_old_bugs, $t_new_bugs );
						}
					}
					$t_changeset->save();
					$t_changesets[] = $t_changeset;
				}
			}
		}

		if( !is_null( $t_changeset ) ) {
			echo "Parsed to revision {$t_changeset->revision}.\n";
		} else {
			echo "No revisions parsed.\n";
		}

		return $t_changesets;
	}
}
