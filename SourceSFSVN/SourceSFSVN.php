<?php

# Copyright (c) 2012 John Reese
# Licensed under the MIT license

if ( false === include_once( config_get( 'plugin_path' ) . 'SourceSVN/SourceSVN.php' ) ) {
	return;
}

class SourceSFSVNPlugin extends SourceSVNPlugin {

	const PLUGIN_VERSION = '2.1.2';
	const FRAMEWORK_VERSION_REQUIRED = '2.5.0';
	const SOURCESVN_VERSION_REQUIRED = '2.4.0';

	public $type = 'sfsvn';

	public function register() {
		parent::register();

		$this->requires['SourceSVN'] = self::SOURCESVN_VERSION_REQUIRED;
	}

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
		$t_revision = ($p_file->action == SourceFile::DELETED)
					? $p_changeset->revision - 1
					: $p_changeset->revision;
		return $this->sf_url( $p_repo ) . urlencode( $p_file->filename ) .
			'?view=markup&pathrev=' . urlencode( $t_revision );
	}

	public function url_diff( $p_repo, $p_changeset, $p_file ) {
		if ( $p_file->action == SourceFile::DELETED || $p_file->action == SourceFile::ADDED ) {
			return '';
		}
		$t_diff = '?r1=' . urlencode( $p_changeset->revision ) . '&r2=' . urlencode( $p_changeset->revision - 1 );
		return $this->sf_url( $p_repo ) . urlencode( $p_file->filename ) . $t_diff .
			'&pathrev=' . urlencode( $p_changeset->revision );
	}

	public function update_repo_form( $p_repo ) {
		$t_sf_project = isset( $p_repo->info['sf_project'] ) ? $p_repo->info['sf_project'] : '';

?>
<tr>
	<td class="category"><?php echo lang_get( 'plugin_SourceSFSVN_sf_project' ) ?></td>
	<td>
		<input type="text" name="sf_project" maxlength="250" size="40" value="<?php echo string_attribute( $t_sf_project ) ?>"/>
	</td>
</tr>
<?php

		return parent::update_repo_form( $p_repo );
	}

	public function update_repo( $p_repo ) {
		$p_repo->info['sf_project'] = gpc_get_string( 'sf_project' );

		return parent::update_repo( $p_repo );
	}
}
