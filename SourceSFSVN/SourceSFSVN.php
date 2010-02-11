<?php
# Copyright (C) 2008-2010 John Reese, LeetCode.net
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU Affero General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Affero General Public License for more details.

if ( false === include_once( config_get( 'plugin_path' ) . 'SourceSVN/SourceSVN.php' ) ) {
	return;
}

class SourceSFSVNPlugin extends SourceSVNPlugin {
	public function register() {
		$this->name = lang_get( 'plugin_SourceSFSVN_title' );
		$this->description = lang_get( 'plugin_SourceSFSVN_description' );

		$this->version = '0.14';
		$this->requires = array(
			'MantisCore' => '1.2.0',
			'Source' => '0.14',
		);

		$this->author = 'John Reese';
		$this->contact = 'jreese@leetcode.net';
		$this->url = 'http://leetcode.net';
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
		if ( $p_file->action == 'D' ) {
			return '';
		}
		return $this->sf_url( $p_repo ) . urlencode( $p_file->filename ) .
			'?view=markup&pathrev=' . urlencode( $p_changeset->revision );
	}

	public function url_diff( $p_repo, $p_changeset, $p_file ) {
		if ( $p_file->action == 'D' || $p_file->action == 'A' ) {
			return '';
		}
		$t_diff = '?r1=' . urlencode( $p_changeset->revision ) . '&r2=' . urlencode( $p_changeset->revision - 1 );
		return $this->sf_url( $p_repo ) . urlencode( $p_file->filename ) . $t_diff .
			'&pathrev=' . urlencode( $p_changeset->revision );
	}

	public function update_repo_form( $p_repo ) {
		$t_sf_project = isset( $p_repo->info['sf_project'] ) ? $p_repo->info['sf_project'] : '';

?>
<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo lang_get( 'plugin_SourceSFSVN_sf_project' ) ?></td>
<td><input name="sf_project" maxlength="250" size="40" value="<?php echo string_attribute( $t_sf_project ) ?>"/></td>
</tr>
<?php

		return parent::update_repo_form( $p_repo );
	}

	public function update_repo( $p_repo ) {
		$p_repo->info['sf_project'] = gpc_get_string( 'sf_project' );

		return parent::update_repo( $p_repo );
	}
}
