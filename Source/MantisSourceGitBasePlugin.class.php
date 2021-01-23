<?php

# Copyright (c) 2017 Damien Regad
# Licensed under the MIT license

require_once( 'MantisSourcePlugin.class.php' );

/**
 * Class MantisSourceGitBasePlugin
 *
 * Base class providing common methods for all git-based Source Integration
 * Plugin classes.
 *
 */
abstract class MantisSourceGitBasePlugin extends MantisSourcePlugin
{
	/**
	 * Git branch name validation regex.
	 * Based on rules defined in man page
	 * http://www.kernel.org/pub/software/scm/git/docs/git-check-ref-format.html
	 * - Must not start with '/'; cannot contain '/.', '//', '@{' or '\';
	 *   cannot be a single '@': `^(?!/|.*([/.]\.|//|@\{|\\\\)|@$)`
	 * - One or more chars, except the following: ASCII control, space,
	 *   tilde, caret, colon, question mark, asterisk, open bracket:
	 *   `[^\000-\037\177 ~^:?*[]+`
	 * - Must not end with '.lock', '/' or '.': `(?<!\.lock|[/.])$`
	 */
	private $valid_branch_regex = '%^(?!/|.*([/.]\.|//|@\{|\\\\)|@$)[^\000-\037\177 ~^:?*[]+(?<!\.lock|[/.])$%';

	/**
	 * @var bool Parent class includes global configuratino
	 */
	public $configuration = true;

	/**
	 * @var bool Prevent more than one Git-based plugin from processing form
	 */
	private static $config_form_handled = false;

	/**
	 *
	 */
	const CFG_DEFAULT_PRIMARY_BRANCH = 'git_default_primary_branch';

	/**
	 * Error constants
	 */
	const ERROR_INVALID_BRANCH = 'invalid_branch';

	/**
	 * Define plugin's Error strings
	 * @return array
	 */
	public function errors() {
		$t_errors_list = array(
			self::ERROR_INVALID_BRANCH,
		);

		foreach( $t_errors_list as $t_error ) {
			$t_errors[$t_error] = plugin_lang_get( 'error_' . $t_error, 'Source' );
		}

		return array_merge( parent::errors(), $t_errors );
	}

	/**
	 * Determines if given string name is a valid git branch name.
	 * @param string $p_branch Branch name to validate
	 * @return bool True if valid
	 */
	protected function is_branch_valid( $p_branch )
	{
		return (bool)preg_match( $this->valid_branch_regex, $p_branch );
	}

	/**
	 * Triggers an error if the branch is invalid
	 * @param string $p_branch Branch name to validate
	 * @return void
	 */
	protected function ensure_branch_valid( $p_branch )
	{
		if( !$this->is_branch_valid( $p_branch ) ) {
			error_parameters( $p_branch );
			plugin_error( self::ERROR_INVALID_BRANCH );
		}
	}

	/**
	 * Validates a comma-delimited list of git branches.
	 * Triggers an ERROR_INVALID_BRANCH if one of the branches is invalid
	 * @param string $p_list Comma-delimited list of branch names (or '*')
	 * @return void
	 */
	protected function validate_branch_list( $p_list )
	{
		if( $p_list == '*' ) {
			return;
		}

		foreach( explode( ',', $p_list ) as $t_branch ) {
			$this->ensure_branch_valid( trim( $t_branch ) );
		}
	}

	/**
	 * Retrieves the default primary branches from Source plugin's config
	 * @return string
	 */
	protected function get_default_primary_branches() {
		plugin_push_current( 'Source' );
		$t_value = plugin_config_get( self::CFG_DEFAULT_PRIMARY_BRANCH, 'master' );
		plugin_pop_current();
		return $t_value;
	}

	/**
	 * Output form elements for configuration options.
	 */
	public function update_config_form() {
		# Prevent more than one Git-based class from outputting form elements.
		if( !MantisSourceGitBasePlugin::$config_form_handled ) {
			plugin_push_current( 'Source' );
			MantisSourceGitBasePlugin::$config_form_handled = true;
?>
	<tr class="spacer"></tr>
	<tr>
		<td colspan="2"><h4><?php echo plugin_lang_get( 'git_title' ) ?></h4></td>
	</tr>
	<tr>
		<td class="category">
			<label for="<?php echo self::CFG_DEFAULT_PRIMARY_BRANCH ?>">
				<?php echo plugin_lang_get( self::CFG_DEFAULT_PRIMARY_BRANCH ) ?>
			</label>
		</td>
		<td>
			<input id="<?php echo self::CFG_DEFAULT_PRIMARY_BRANCH ?>"
				   name="<?php echo self::CFG_DEFAULT_PRIMARY_BRANCH ?>"
				   type="text" class="input-sm" size="50"
				   value="<?php echo string_attribute( plugin_config_get( self::CFG_DEFAULT_PRIMARY_BRANCH, 'master' ) ) ?>"
			/>
			<br>
			<span class="small"><?php echo plugin_lang_get( 'git_default_primary_branch_info' ) ?></span>
		</td>
	</tr>
	<tr></tr>
<?php
			plugin_pop_current();
		}
	}

	/**
	 * Process form elements for configuration options.
	 */
	public function update_config() {
		# Prevent more than one SVN class from handling form elements.
		if( !MantisSourceGitBasePlugin::$config_form_handled ) {
			MantisSourceGitBasePlugin::$config_form_handled = true;

			plugin_push_current( 'Source' );
			$f_default_branch = trim( gpc_get_string( self::CFG_DEFAULT_PRIMARY_BRANCH ) ) ?: 'master';
			$t_default_branch = plugin_config_get( self::CFG_DEFAULT_PRIMARY_BRANCH, 'master' );
			if ( $f_default_branch != $t_default_branch ) {
				plugin_config_set( self::CFG_DEFAULT_PRIMARY_BRANCH, $f_default_branch );
			}
			plugin_pop_current();
		}
	}
}
