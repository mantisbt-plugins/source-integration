<?php

# Copyright (c) 2012 John Reese
# Licensed under the MIT license

if ( false === include_once( config_get( 'plugin_path' ) . 'SourceSVN/SourceSVN.php' ) ) {
	return;
}

class SourceWebSVNPlugin extends SourceSVNPlugin {
	public function register() {
		$this->name = lang_get( 'plugin_SourceWebSVN_title' );
		$this->description = lang_get( 'plugin_SourceWebSVN_description' );

		$this->version = '0.17';
		$this->requires = array(
			'MantisCore' => '1.2.0',
			'Source' => '0.16',
			'SourceSVN' => '0.16',
		);

		$this->author = 'John Reese';
		$this->contact = 'john@noswap.com';
		$this->url = 'http://noswap.com';
	}

	public $type = 'websvn';

	public function show_type() {
		return lang_get( 'plugin_SourceWebSVN_svn' );
	}

	protected function websvn_url($p_repo) {
		$t_path = '';
		if ( !is_blank( $p_repo->info['websvn_path'] ) ) {
			$t_path = '/' . urlencode( $p_repo->info['websvn_path'] );
		}
		return $p_repo->info['websvn_url'] . $p_repo->info['websvn_name'] . $t_path;
	}

	public function url_repo( $p_repo, $p_changeset=null ) {
		$t_rev = '';

		if ( !is_null( $p_changeset ) ) {
			$t_rev = '?rev=' . urlencode( $p_changeset->revision );
		}
		return $this->websvn_url($p_repo) . $t_rev;
	}

	public function url_changeset( $p_repo, $p_changeset ) {
		return $this->websvn_url($p_repo) . '?op=comp' .
			'&compare[]=/@' . urlencode( $p_changeset->revision-1 ) .
			'&compare[]=/@' . urlencode( $p_changeset->revision);
	}

	public function url_file( $p_repo, $p_changeset, $p_file ) {
		if ( $p_file->action == 'D' ) {
			return '';
		}
		return $this->websvn_url($p_repo) . $p_file->filename . '?op=filedetails' .
			'&rev=' . urlencode( $p_changeset->revision ) . '&peg=' . urlencode( $p_changeset->revision ) ;
	}

	public function url_diff( $p_repo, $p_changeset, $p_file ) {
		if ( $p_file->action == 'D' || $p_file->action == 'A' ) {
			return '';
		}
		return $this->websvn_url( $p_repo ) . $p_file->filename . '?op=diff' .
			'&rev=' . urlencode( $p_changeset->revision ) . '&peg=' . urlencode( $p_changeset->revision ) ;
	}

	public function update_repo_form( $p_repo ) {
		$t_url = isset( $p_repo->info['websvn_url'] ) ? $p_repo->info['websvn_url'] : '';
		$t_multiviews = isset( $p_repo->info['websvn_multiviews'] ) ? $p_repo->info['websvn_multiviews'] : false;
		$t_name = isset( $p_repo->info['websvn_name'] ) ? $p_repo->info['websvn_name'] : '';
		$t_path = isset( $p_repo->info['websvn_path'] ) ? $p_repo->info['websvn_path'] : '';

?>
<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo lang_get( 'plugin_SourceWebSVN_websvn_url' ) ?></td>
<td><input name="websvn_url" maxlength="250" size="40" value="<?php echo string_attribute( $t_url ) ?>"/></td>
</tr>
<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo lang_get( 'plugin_SourceWebSVN_websvn_multiviews' ) ?></td>
<td><input name="websvn_multiviews" type="checkbox" <?php echo ($t_multiviews ? 'checked="checked"' : '') ?>/></td>
</tr>
<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo lang_get( 'plugin_SourceWebSVN_websvn_name' ) ?></td>
<td><input name="websvn_name" maxlength="250" size="40" value="<?php echo string_attribute( $t_name ) ?>"/></td>
</tr>
<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo lang_get( 'plugin_SourceWebSVN_websvn_path' ) ?></td>
<td><input name="websvn_path" maxlength="250" size="40" value="<?php echo string_attribute( $t_path ) ?>"/></td>
</tr>
<?php

		return parent::update_repo_form( $p_repo );
	}

	public function update_repo( $p_repo ) {
		$p_repo->info['websvn_url'] = gpc_get_string( 'websvn_url' );
		$p_repo->info['websvn_multiviews'] = gpc_get_bool( 'websvn_multiviews', false );
		$p_repo->info['websvn_name'] = gpc_get_string( 'websvn_name' );
		$p_repo->info['websvn_path'] = gpc_get_string( 'websvn_path' );
		
		return parent::update_repo( $p_repo );
	}
}
