<?php

# Copyright (c) 2012 John Reese
# Licensed under the MIT license

if ( false === include_once( config_get( 'plugin_path' ) . 'SourceSVN/SourceSVN.php' ) ) {
	return;
}

class SourceSFSVNPlugin extends SourceSVNPlugin {

	const FRAMEWORK_VERSION_REQUIRED = '1.3.2';
	const SOURCESVN_VERSION_REQUIRED = '0.16';

	public function register() {
		$this->name = lang_get( 'plugin_SourceSFSVN_title' );
		$this->description = lang_get( 'plugin_SourceSFSVN_description' );

		$this->version = '0.16';
		$this->requires = array(
			'MantisCore' => self::MANTIS_VERSION,
			'Source' => self::FRAMEWORK_VERSION_REQUIRED,
			'SourceSVN' => self::SOURCESVN_VERSION_REQUIRED,
		);

		$this->author = 'John Reese';
		$this->contact = 'john@noswap.com';
		$this->url = 'http://noswap.com';
	}

	public $type = 'sfsvn';

	public function show_type() {
		return lang_get( 'plugin_SourceSFSVN_svn' );
	}

	private function sf_url( $p_repo ) {
		$t_project = urlencode( $p_repo->info['sf_project'] );
		return "http://$t_project.svn.sourceforge.net/viewvc/$t_project";
	}

	public function url_repo( $p_repo, $p_changeset=null ) {
		if ( !is_null( $p_changeset ) ) {
			$t_rev = '?pathrev=' . urlencode( $p_changeset->revision );
		}
		return $this->sf_url( $p_repo ) . "/$t_rev";
	}

	public function url_changeset( $p_repo, $p_changeset ) {
		$t_rev = '&revision=' . urlencode( $p_changeset->revision );
		return $this->sf_url( $p_repo ) . "?view=rev$t_rev";
	}

	public function url_file( $p_repo, $p_changeset, $p_file ) {
		# if the file has been removed, it doesn't exist in current revision
		# so we generate a link to (current revision - 1)
		$t_revision = ($p_file->action == 'rm')
					? $p_changeset->revision - 1
					: $p_changeset->revision;
		return $this->sf_url( $p_repo ) . urlencode( $p_file->filename ) .
			'?view=markup&pathrev=' . urlencode( $t_revision );
	}

	public function url_diff( $p_repo, $p_changeset, $p_file ) {
		if ( $p_file->action == 'rm' || $p_file->action == 'add' ) {
			return '';
		}
		$t_diff = '?r1=' . urlencode( $p_changeset->revision ) . '&r2=' . urlencode( $p_changeset->revision - 1 );
		return $this->sf_url( $p_repo ) . urlencode( $p_file->filename ) . $t_diff .
			'&pathrev=' . urlencode( $p_changeset->revision );
	}

	public function update_repo_form( $p_repo ) {
		$t_sf_project = isset( $p_repo->info['sf_project'] ) ? $p_repo->info['sf_project'] : '';

?>

<div class="field-container">
	<label><span><?php echo plugin_lang_get( 'sf_project' ) ?></span></label>
	<span class="input">
		<input name="sf_project" maxlength="250" size="40" value="<?php echo string_attribute( $t_sf_project ) ?>"/>
	</span>
	<span class="label-style"></span>
</div>

<?php

		return parent::update_repo_form( $p_repo );
	}

	public function update_repo( $p_repo ) {
		$p_repo->info['sf_project'] = gpc_get_string( 'sf_project' );

		return parent::update_repo( $p_repo );
	}
}
