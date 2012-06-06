<?php

# Copyright (c) 2012 John Reese
# Licensed under the MIT license

final class SourceIntegrationPlugin extends MantisPlugin {
	function register() {
		$this->name = plugin_lang_get( 'title', 'Source' );
		$this->version = SourcePlugin::$framework_version;
	}

	function hooks() {
		return array(
			'EVENT_VIEW_BUG_EXTRA'		=> 'display_bug',
			'EVENT_DISPLAY_FORMATTED'	=> 'display_formatted',
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

		if ( !access_has_global_level( config_get( 'plugin_Source_view_threshold' ) ) ) {
			return;
		}

		$t_changesets = $this->changesets;

		if ( count( $t_changesets ) < 1 ) {
			return;
		}

		collapse_open( 'Source' );

		?>
<br/>
<a name="changesets"/>
<table class="width100" cellspacing="1">

<tr>
	<td class="form-title"><?php collapse_icon( 'Source' ); echo plugin_lang_get( 'related_changesets', 'Source' ) ?></td>
</tr>
		<?php Source_View_Changesets( $t_changesets ); ?>
</table>
<?php
			collapse_closed( 'Source' );
?>
<br/>
<table class="width100" cellspacing="1">

<tr>
	<td class="form-title"><?php collapse_icon( 'Source' ); echo plugin_lang_get( 'related_changesets', 'Source' ) ?></td>
</tr>

</table>
<?php

		collapse_end( 'Source' );
	} #display_bug

	function display_formatted( $p_event, $p_string, $p_multiline ) {
		$t_string = $p_string;
		$t_string = preg_replace_callback( '/(\s)c?:([\w ]+):([\w]+)\b/', 'Source_Changeset_Link_Callback',	$t_string );

		return $t_string;
	}

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

		echo '<tr ', helper_alternate_class(), '><td class="category">', plugin_lang_get( 'vcs_username', 'Source' ),
			'<input type="hidden" name="Source_vcs" value="1"/></td><td>',
			'<input name="Source_vcs_username" value="', $t_user->username, '"/></td></tr>';
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
