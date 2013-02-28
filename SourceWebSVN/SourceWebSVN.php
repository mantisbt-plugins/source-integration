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

	/**
	 * Retrieves the repository's multiviews setting (set to true if WebSVN is
	 * configured to use Apache Multiviews link format)
	 * @param object $p_repo repository
	 * @return bool
	 */
	public function is_multiviews( $p_repo ) {
		return isset( $p_repo->info['websvn_multiviews'] )
			? $p_repo->info['websvn_multiviews']
			: false;
	}

	public function get_websvn_url( $p_repo ) {
		return isset( $p_repo->info['websvn_url'] )
			? $p_repo->info['websvn_url']
			: '';
	}

	public function get_websvn_name( $p_repo ) {
		return isset( $p_repo->info['websvn_name'] )
			? $p_repo->info['websvn_name']
			: '';
	}

	public function get_websvn_path( $p_repo ) {
		return isset( $p_repo->info['websvn_path'] )
			? $p_repo->info['websvn_path']
			: '';
	}

	/**
	 * Builds the WebSVN URL base string
	 * @param object $p_repo repository
	 * @param string $p_op optional WebSVN operation
	 * @param string $p_file optional filename (as absolute path from root)
	 * @return string WebSVN URL
	 */
	protected function url_base( $p_repo, $p_op = '', $p_file = '', $p_opts=array()) {
		$t_name = urlencode( $this->get_websvn_name( $p_repo ) );

		if( $this->is_multiviews( $p_repo ) ) {
			$t_url = $this->get_websvn_url( $p_repo ) . $t_name;

			if( is_blank( $p_file ) ) {
				$t_url .= $this->get_websvn_path( $p_repo );
			} else {
				$t_url .= $p_file;
			}

			$t_url = rtrim( $t_url, '/' );

			if( !is_blank( $p_op ) ) {
				$p_opts["op"] = $p_op;
			}

			return $t_url . "?" . http_build_query( $p_opts );
		} else {
			$t_url = $this->get_websvn_url( $p_repo );

			if( !is_blank( $p_op ) ) {
				$t_url .= "$p_op.php";
			}

			if( is_blank( $p_file ) ) {
				$t_path = $this->get_websvn_path( $p_repo );
			} else {
				$t_path = $p_file;
			}
			
			if( !is_blank( $t_path ) ) {
				$p_opts["path"] = $t_path;
			}
			
			$p_opts["repname"] = $t_name;;

			return $t_url . "?" . http_build_query( $p_opts );
		}
	}

	public function url_repo( $p_repo, $p_changeset=null ) {
		$t_opts = array();
		
		if ( !is_null( $p_changeset ) ) {
			$t_opts["rev"] = $p_changeset->revision;
		}

		$t_op = $this->is_multiviews( $p_repo ) ? '' : 'listing';

		return $this->url_base( $p_repo, $t_op, '', $t_opts);
	}

	public function url_changeset( $p_repo, $p_changeset ) {
		$t_rev = $p_changeset->revision;
		$t_path = $this->get_websvn_path( $p_repo );
		$t_opts = array();
		$t_opts['compare[0]'] = $t_path . '@' . ($t_rev - 1);
		$t_opts['compare[1]'] = $t_path . '@' . $t_rev;
		return $this->url_base( $p_repo, 'comp', '', $t_opts );
	}

	public function url_file( $p_repo, $p_changeset, $p_file ) {
		
		# if the file has been removed, it doesn't exists in current revision
		# so we generate a link to (current revision - 1)
		$t_revision = ($p_file->action == 'rm') 
					? $p_changeset->revision - 1
					: $p_changeset->revision;
		
		$t_opts = array();
		$t_opts['rev'] = $t_revision;
		$t_opts['peg'] = $t_revision;

		return $this->url_base( $p_repo, 'filedetails', $p_file->filename, $t_opts );
	}

	public function url_diff( $p_repo, $p_changeset, $p_file ) {
		if ( $p_file->action == 'rm' || $p_file->action == 'add' ) {
			return '';
		}

		$t_opts = array();
		$t_opts['rev'] = $p_changeset->revision;
		$t_opts['peg'] = $p_changeset->revision;

		return $this->url_base( $p_repo, 'diff', $p_file->filename, $t_opts );
	}

	public function update_repo_form( $p_repo ) {
		$t_url  = $this->get_websvn_url( $p_repo );
		$t_name = $this->get_websvn_name( $p_repo );
		$t_path = $this->get_websvn_path( $p_repo );

?>
<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo lang_get( 'plugin_SourceWebSVN_websvn_url' ) ?></td>
<td><input name="websvn_url" maxlength="250" size="40" value="<?php echo string_attribute( $t_url ) ?>"/></td>
</tr>
<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo lang_get( 'plugin_SourceWebSVN_websvn_multiviews' ) ?></td>
<td><input name="websvn_multiviews" type="checkbox" <?php check_checked( $this->is_multiviews( $p_repo ) ) ?>/></td>
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
