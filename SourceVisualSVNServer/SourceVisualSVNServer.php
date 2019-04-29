<?php

# Copyright (c) 2019 David Hopkins, FBR Ltd
# Copyright (c) 2015 John Bailey
# Copyright (c) 2012 John Reese
# Licensed under the MIT license

if ( false === include_once( config_get( 'plugin_path' ) . 'SourceSVN/SourceSVN.php' ) ) {
	return;
}

class SourceVisualSVNServerPlugin extends SourceSVNPlugin {

	const PLUGIN_VERSION = '2.0.2';
	const FRAMEWORK_VERSION_REQUIRED = '2.0.0';
	const SOURCESVN_VERSION_REQUIRED = '2.0.0';

	public function register() {
		$this->name = plugin_lang_get( 'title' );
		$this->description = plugin_lang_get( 'description' );

		$this->version = self::PLUGIN_VERSION;
		$this->requires = array(
			'MantisCore' => self::MANTIS_VERSION,
			'Source' => self::FRAMEWORK_VERSION_REQUIRED,
			'SourceSVN' => self::SOURCESVN_VERSION_REQUIRED,
		);

		$this->author = 'David Hopkins';
		$this->contact = 'david.hopkins@fbr.com.au';
		$this->url = 'https://github.com/mantisbt-plugins/source-integration';
	}

	public $type = 'vsvns';

	public function show_type() {
		return plugin_lang_get( 'vsvns' );
	}

	public function get_visualsvnserver_url_prefix( $p_repo ) {
		return isset( $p_repo->info['visualsvnserver_url_prefix'] )
			? $p_repo->info['visualsvnserver_url_prefix']
			: 'svn'; # Match VisualSVN Server default configuration
	}

	/**
	 * Builds the VisualSVNServer URL base string
	 * @param object $p_repo repository
	 * @return string VisualSVNServer URL
	 */
	protected function url_base( $p_repo ) {
		# VisualSVN Server web interface is always on the same host name
		# as the HTTP(S)-served repositories, accessed via the /!/#reponame path
		$t_repo_url = parse_url($p_repo->url);
		$t_repo_path = $t_repo_url['path'];

		$t_url_prefix = $this->get_visualsvnserver_url_prefix( $p_repo );

		# Strip repo prefix (typically 'svn') from path, if present
		if ( !empty( $t_url_prefix ) ) {
			$t_prefix = '/' . $t_url_prefix . '/';
			
			if (substr($t_repo_path, 0, strlen($t_prefix)) == $t_prefix) {
				$t_repo_path = substr($t_repo_path, strlen($t_prefix));
			}
		}

		# Only include port in final URL if it was present originally
		$t_port = isset( $t_url['port'] ) ? ':' . $t_url['port'] : '';

		return $t_url['scheme'] . '://' . $t_url['host'] . $t_port . '/!/#' . $t_repo_path;
	}

	public function url_repo( $p_repo, $p_changeset=null ) {
		$t_repo_url = $this->url_base( $p_repo );
		
		if ( !is_null( $p_changeset ) ) {
			$t_revision = $p_changeset->revision;
			$t_repo_url .= '/view/r' . urlencode($t_revision) . '/';
		}

		return $t_repo_url;
	}

	public function url_changeset( $p_repo, $p_changeset ) {
		$t_repo_url = $this->url_base( $p_repo );
		$t_revision = $p_changeset->revision;

		return $t_repo_url . '/commit/r' . urlencode($t_revision) + '/';
	}

	public function url_file( $p_repo, $p_changeset, $p_file ) {
		$t_repo_url = $this->url_base( $p_repo );

		# if the file has been removed, it doesn't exist in current revision
		# so we generate a link to (current revision - 1)
		$t_revision = ($p_file->action == 'rm')
					? $p_changeset->revision - 1
					: $p_changeset->revision;
		
		return $t_repo_url . '/view/r' . urlencode($t_revision) . '/' . $p_file;
	}

	public function url_diff( $p_repo, $p_changeset, $p_file ) {
		if ( $p_file->action == 'rm' || $p_file->action == 'add' ) {
			return '';
		}

		# The web interface for VisualSVN Server displays file diffs as inline content 
		# when viewing a particular commit.
		# It doesn't have a specific page for single-file diffs, 
		# at least as of v3.9.5, 2019-04-29
		return url_changeset( $p_repo, $p_changeset );
	}

	public function update_repo_form( $p_repo ) {
		$t_url_prefix   = $this->get_visualsvnserver_url_prefix( $p_repo );

?>
<tr>
	<td class="category"><?php echo plugin_lang_get( 'visualsvnserver_url_prefix' ) ?></td>
	<td>
		<input type="text" name="visualsvnserver_url_prefix" maxlength="250" size="40" value="<?php echo string_attribute( $t_url_prefix ) ?>"/>
	</td>
</tr>
<?php

		return parent::update_repo_form( $p_repo );
	}

	public function update_repo( $p_repo ) {

		$p_repo->info['visualsvnserver_url_prefix'] = gpc_get_string( 'visualsvnserver_url_prefix' );

		return parent::update_repo( $p_repo );
	}
}
