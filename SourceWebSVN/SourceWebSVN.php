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
	protected function url_base( $p_repo, $p_op = '', $p_file = '' ) {
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
				$t_url .= "?op=$p_op";
			}

			return $t_url;
		} else {
			$t_url = $this->get_websvn_url( $p_repo );

			if( !is_blank( $p_op ) ) {
				$t_url .= "$p_op.php";
			}

			if( !is_blank( $p_file ) ) {
				$t_path = urlencode( $p_file );
			} else {
				$t_path = urlencode( $this->get_websvn_path( $p_repo ) );
			}
			if( !is_blank( $t_path ) ) {
				$t_path = "&path=$t_path";
			}

			return $t_url . "?repname=$t_name" . $t_path;
		}
	}

	public function url_repo( $p_repo, $p_changeset=null ) {
		$t_rev = '';

		if ( !is_null( $p_changeset ) ) {
			$t_rev = '&rev=' . urlencode( $p_changeset->revision );
		}

		if( $this->is_multiviews( $p_repo ) ) {
			return $this->url_base( $p_repo ) . "?$t_rev";
		} else {
			return $this->url_base( $p_repo, 'listing' ) . "$t_rev&sc=1";
		}
	}

	public function url_changeset( $p_repo, $p_changeset ) {
		if( $this->is_multiviews( $p_repo ) ) {
			$t_rev = $p_changeset->revision;
			return $this->url_base( $p_repo, 'comp' )
				. '&compare[]=/@' . urlencode( $t_rev - 1 )
				. '&compare[]=/@' . urlencode( $t_rev );
		} else {
			return $this->url_repo( $p_repo, $p_changeset );
		}
	}

	public function url_file( $p_repo, $p_changeset, $p_file ) {
		if ( $p_file->action == 'D' ) {
			return '';
		}

		$t_rev = urlencode( $p_changeset->revision );
		$t_url = $this->url_base( $p_repo, 'filedetails', $p_file->filename )
			. "&rev=$t_rev&peg=$t_rev";

		if( !$this->is_multiviews( $p_repo ) ) {
			$t_url .= "&sc=1";
		}
		return $t_url;
	}

	public function url_diff( $p_repo, $p_changeset, $p_file ) {
		if ( $p_file->action == 'D' || $p_file->action == 'A' ) {
			return '';
		}

		$t_rev = urlencode( $p_changeset->revision );
		$t_url = $this->url_base( $p_repo, 'diff', $p_file->filename )
			. "&rev=$t_rev&peg=$t_rev";

		if( !$this->is_multiviews( $p_repo ) ) {
			$t_url .= "&sc=1";
		}
		return $t_url;
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
