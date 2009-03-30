<?php
# Copyright (C) 2008	John Reese
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.

final class SourceIntegrationPlugin extends MantisPlugin {
	function register() {
		$this->name = plugin_lang_get( 'title', 'Source' );
		$this->version = plugin_lang_get( 'version', 'Source' );
	}

	function hooks() {
		return array(
			'EVENT_VIEW_BUG_EXTRA'		=> 'display_bug',
			'EVENT_DISPLAY_FORMATTED'	=> 'display_formatted',
			'EVENT_MENU_ISSUE'			=> 'display_changeset_link'
		);
	}

	function display_changeset_link( $p_event, $p_bug_id ) {
		$this->changesets = SourceChangeset::load_by_bug( $p_bug_id, true );

		if ( count( $this->changesets ) > 1 ) {
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
		$t_repos = SourceRepo::load_by_changesets( $t_changesets );

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
		<?php Source_View_Changesets( $t_changesets, $t_repos ); ?>
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
}
