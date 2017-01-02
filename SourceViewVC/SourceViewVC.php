<?php

# Copyright (c) 2015 John Bailey
# Copyright (c) 2012 John Reese
# Licensed under the MIT license

if ( false === include_once( config_get( 'plugin_path' ) . 'SourceSVN/SourceSVN.php' ) ) {
	return;
}

class SourceViewVCPlugin extends SourceSVNPlugin {
	public function register() {
		$this->name = lang_get( 'plugin_SourceViewVC_title' );
		$this->description = lang_get( 'plugin_SourceViewVC_description' );

		$this->version = '0.1';
		$this->requires = array(
			'MantisCore' => self::MANTIS_VERSION,
			'Source' => '0.16',
			'SourceSVN' => '0.16',
		);

		$this->author = 'John Bailey';
		$this->contact = 'dev@brightsilence.com';
		$this->url = 'https://github.com/mantisbt-plugins/source-integration';
	}

	public $type = 'viewvc';

	public function show_type() {
		return lang_get( 'plugin_SourceViewVC_svn' );
	}

	public function get_viewvc_url( $p_repo ) {
		return isset( $p_repo->info['viewvc_url'] )
			? $p_repo->info['viewvc_url']
			: '';
	}

	public function get_viewvc_name( $p_repo ) {
		return isset( $p_repo->info['viewvc_name'] )
			? $p_repo->info['viewvc_name']
			: '';
	}

	public function get_viewvc_use_checkout( $p_repo ) {
		return isset( $p_repo->info['viewvc_use_checkout'] )
			? $p_repo->info['viewvc_use_checkout']
			: false;
	}

	public function get_viewvc_root_as_url( $p_repo ) {
		return isset( $p_repo->info['viewvc_root_as_url'] )
			? $p_repo->info['viewvc_root_as_url']
			: false;
	}

	/**
	 * Builds the ViewVC URL base string
	 * @param object $p_repo repository
	 * @param string $p_file optional filename (as absolute path from root)
	 * @param array $p_opts optional additional ViewVC URL parameters
	 * @return string ViewVC URL
	 */
	protected function url_base( $p_repo, $p_file = '', $p_opts=array() ) {
		$t_name = urlencode( $this->get_viewvc_name( $p_repo ) );
		$t_root_as_url = $this->get_viewvc_root_as_url( $p_repo );

		$t_url = rtrim( $this->get_viewvc_url( $p_repo ), '/' );

		if( $t_root_as_url ) {
			$t_url_name = '/'.$t_name;
		} else {
			$t_url_name = '';
			$p_opts['root']=$t_name;
		}

		return $t_url . $t_url_name . $p_file .  '?' . http_build_query( $p_opts );
	}

	public function url_repo( $p_repo, $p_changeset=null ) {
		$t_opts = array();

		if ( !is_null( $p_changeset ) ) {
			$t_opts['revision'] = $p_changeset->revision;
		}

		return $this->url_base( $p_repo, '', $t_opts);
	}

	public function url_changeset( $p_repo, $p_changeset ) {
		$t_rev = $p_changeset->revision;
		$t_opts = array();
		$t_opts['view'] = 'revision';
		$t_opts['revision'] = $t_rev;

		return $this->url_base( $p_repo, '', $t_opts );
	}

	public function url_file( $p_repo, $p_changeset, $p_file ) {

		# if the file has been removed, it doesn't exist in current revision
		# so we generate a link to (current revision - 1)
		$t_revision = ($p_file->action == 'rm')
					? $p_changeset->revision - 1
					: $p_changeset->revision;
		$t_use_checkout = $this->get_viewvc_use_checkout( $p_repo );

		$t_opts = array();
		$t_opts['revision'] = $t_revision;

		if( !$t_use_checkout )
		{
		    $t_opts['view'] = 'markup';
		}

		return $this->url_base( $p_repo, $p_file->filename, $t_opts );
	}

	public function url_diff( $p_repo, $p_changeset, $p_file ) {
		if ( $p_file->action == 'rm' || $p_file->action == 'add' ) {
			return '';
		}

		$t_opts = array();
		$t_opts['r1'] = $p_changeset->revision;
		$t_opts['r2'] = $p_changeset->revision - 1;

		return $this->url_base( $p_repo, $p_file->filename, $t_opts );
	}

	public function update_repo_form( $p_repo ) {
		$t_url          = $this->get_viewvc_url( $p_repo );
		$t_name         = $this->get_viewvc_name( $p_repo );
		$t_use_checkout = $this->get_viewvc_use_checkout( $p_repo );
		$t_root_as_url  = $this->get_viewvc_root_as_url( $p_repo );

?>

<div class="field-container">
	<label><span><?php echo plugin_lang_get( 'viewvc_url' ) ?></span></label>
	<span class="input">
		<input name="viewvc_url" maxlength="250" size="40" value="<?php echo string_attribute( $t_url ) ?>"/>
	</span>
	<span class="label-style"></span>
</div>

<div class="field-container">
	<label><span><?php echo plugin_lang_get( 'viewvc_name' ) ?></span></label>
	<span class="input">
		<input name="viewvc_name" maxlength="250" size="40" value="<?php echo string_attribute( $t_name ) ?>"/>
	</span>
	<span class="label-style"></span>
</div>

<div class="field-container">
	<label><span><?php echo plugin_lang_get( 'viewvc_root_as_url' ) ?></span></label>
	<span class="input">
		<input name="viewvc_root_as_url" type="checkbox" <?php echo ($t_root_as_url ? 'checked="checked"' : '') ?>/>
	</span>
	<span class="label-style"></span>
</div>

<div class="field-container">
	<label><span><?php echo plugin_lang_get( 'viewvc_use_checkout' ) ?></span></label>
	<span class="input">
		<input name="viewvc_use_checkout" type="checkbox" <?php echo ($t_use_checkout ? 'checked="checked"' : '') ?>/>
	</span>
	<span class="label-style"></span>
</div>

<?php

		return parent::update_repo_form( $p_repo );
	}

	public function update_repo( $p_repo ) {

		$p_repo->info['viewvc_url'] = gpc_get_string( 'viewvc_url' );
		$p_repo->info['viewvc_name'] = gpc_get_string( 'viewvc_name' );
		$p_repo->info['viewvc_use_checkout'] = gpc_get_bool( 'viewvc_use_checkout', false );
		$p_repo->info['viewvc_root_as_url'] = gpc_get_bool( 'viewvc_root_as_url', false );

		return parent::update_repo( $p_repo );
	}
}
