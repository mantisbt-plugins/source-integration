<?php

# Copyright (c) 2012 John Reese
# Licensed under the MIT license

/**
 * Class SourceIntegrationPlugin
 *
 * Child plugin handling the framework's integration with the MantisBT UI
 */
final class SourceIntegrationPlugin extends MantisSourceBase {
	function register() {
		$this->name = plugin_lang_get( 'title', 'Source' );
		$this->version = self::FRAMEWORK_VERSION;
	}

	function hooks() {
		return array(
			'EVENT_VIEW_BUG_EXTRA'		=> 'display_bug',
			'EVENT_MENU_ISSUE'			=> 'display_changeset_link',

			'EVENT_ACCOUNT_PREF_UPDATE_FORM' => 'account_update_form',
			'EVENT_ACCOUNT_PREF_UPDATE' => 'account_update',
		);
	}

	function display_changeset_link( $p_event, $p_bug_id ) {
		$this->changesets = SourceChangeset::load_by_bug( $p_bug_id, true );

		if ( count( $this->changesets ) > 0 ) {
			return array( plugin_lang_get( 'related_changesets', 'Source' ) => '#changesets' );
		}

		return array();
	}

	function display_bug( $p_event, $p_bug_id ) {
		require_once( 'Source.ViewAPI.php' );

		$t_project_id = bug_get_field( $p_bug_id, 'project_id' );
		$t_view_threshold = config_get( 'plugin_Source_view_threshold' );
		if ( !access_has_project_level( $t_view_threshold, $t_project_id ) ) {
			return;
		}

		$t_changesets = $this->changesets;

		if ( count( $t_changesets ) < 1 ) {
			return;
		}
		?>
		<div class="col-md-12 col-xs-12">
			<div class="space-10"></div>
			<div class="widget-box widget-color-blue2" id="changesets">
				<div class="widget-header widget-header-small">
					<h4 class="widget-title lighter">
						<i class="ace-icon fa fa-file-code-o"></i>
						<?php echo plugin_lang_get( 'related_changesets', 'Source' ) ?>
					</h4>
					<div class="widget-toolbar">
						<a href="#" data-action="collapse">
							<i class="1 ace-icon fa-chevron-down fa bigger-125"></i>
						</a>
					</div>
				</div>
				<div class="widget-body">
					<div class="table-responsive">
						<table class="table table-bordered table-striped table-condensed no-margin">
							<?php Source_View_Changesets( $t_changesets ); ?>
						</table>
					</div>
				</div>
			</div>
		</div>
<?php
	} #display_bug

	/**
	 * When updating user preferences, allowing the user or admin to specify
	 * a version control username to be associated with the account.
	 * @param string Event name
	 * @param int User ID
	 */
	function account_update_form( $p_event, $p_user_id ) {
		if ( !access_has_global_level( config_get( 'plugin_Source_username_threshold' ) ) ) {
			return;
		}

		$t_user = SourceUser::load( $p_user_id );

		echo '<tr>'
			. '<td class="category">' . plugin_lang_get( 'vcs_username', 'Source' ) . '</td>'
			. '<td>'
			. '<input type="hidden" name="Source_vcs" value="1"/>'
			. '<input id="source-vcs-username" type="text" name="Source_vcs_username" class="input-sm" value="' . $t_user->username . '" />'
			. '</td>'
			. '</tr>';
	}

	/**
	 * When updating user preferences, allowing the user or admin to specify
	 * a version control username to be associated with the account.
	 * @param string Event name
	 * @param int User ID
	 */
	function account_update( $p_event, $p_user_id ) {
		if ( !access_has_global_level( config_get( 'plugin_Source_username_threshold' ) ) ) {
			return;
		}

		$f_vcs_sent = gpc_get_bool( 'Source_vcs', false );
		$f_vcs_username = gpc_get_string( 'Source_vcs_username', '' );

		# only load and persist the username if things are set and changed
		if ( $f_vcs_sent ) {
			$t_user = SourceUser::load( $p_user_id );

			if ( $t_user->username != $f_vcs_username ) {
				$t_user->username = $f_vcs_username;
				$t_user->save();
			}
		}
	}
}
