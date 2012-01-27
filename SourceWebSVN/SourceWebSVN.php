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

		$this->version = '0.16';
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

	public function url_repo( $p_repo, $p_changeset=null ) {
		$t_rev = '';
		$t_path = '';

		if ( !is_null( $p_changeset ) ) {
			$t_rev = '&rev=' . urlencode( $p_changeset->revision );
		}
		if ( !is_blank( $p_repo->info['websvn_path'] ) ) {
			$t_path = '&path=' . urlencode( $p_repo->info['websvn_path'] );
		}
		return $p_repo->info['websvn_url'] . 'listing.php?repname=' . urlencode( $p_repo->info['websvn_name'] ) . "$t_path$t_rev&sc=1";
	}

	public function url_changeset( $p_repo, $p_changeset ) {
		return $this->url_repo( $p_repo, $p_changeset );
	}

	public function url_file( $p_repo, $p_changeset, $p_file ) {
		if ( $p_file->action == 'D' ) {
			return '';
		}
		return $p_repo->info['websvn_url'] . 'filedetails.php?repname=' . urlencode( $p_repo->info['websvn_name'] ) .
			'&rev=' . urlencode( $p_changeset->revision ) . '&peg=' . urlencode( $p_changeset->revision ) .
			'&path=' . urlencode( $p_file->filename ) . '&sc=1';
	}

	public function url_diff( $p_repo, $p_changeset, $p_file ) {
		if ( $p_file->action == 'D' || $p_file->action == 'A' ) {
			return '';
		}
		return $p_repo->info['websvn_url'] . 'diff.php?repname=' . urlencode( $p_repo->info['websvn_name'] ) .
			'&rev=' . urlencode( $p_changeset->revision ) . '&peg=' . urlencode( $p_changeset->revision ) .
			'&path=' . urlencode( $p_file->filename ) . '&sc=1';
	}

	public function update_repo_form( $p_repo ) {
		$t_url = isset( $p_repo->info['websvn_url'] ) ? $p_repo->info['websvn_url'] : '';
		$t_name = isset( $p_repo->info['websvn_name'] ) ? $p_repo->info['websvn_name'] : '';
		$t_path = isset( $p_repo->info['websvn_path'] ) ? $p_repo->info['websvn_path'] : '';

?>
<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo lang_get( 'plugin_SourceWebSVN_websvn_url' ) ?></td>
<td><input name="websvn_url" maxlength="250" size="40" value="<?php echo string_attribute( $t_url ) ?>"/></td>
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
		$p_repo->info['websvn_name'] = gpc_get_string( 'websvn_name' );
		$p_repo->info['websvn_path'] = gpc_get_string( 'websvn_path' );

		return parent::update_repo( $p_repo );
	}
}
