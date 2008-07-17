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
			'EVENT_MENU_MAIN'			=> 'menu_main',
			'EVENT_VIEW_BUG_EXTRA'		=> 'display_bug',
			'EVENT_DISPLAY_FORMATTED'	=> 'display_formatted',
			'EVENT_SOURCE_COMMIT'		=> 'commit',
		);
	}

	function menu_main() {
		$t_page = plugin_page( 'index', false, 'Source' );
		$t_repos = plugin_lang_get( 'repositories', 'Source' );
		return "<a href=\"$t_page\">$t_repos</a>";
	}

	function display_bug( $p_event, $p_bug_id ) {
		if ( !access_has_global_level( config_get( 'plugin_Source_view_threshold' ) ) ) {
			return;
		}

		$t_changesets = SourceChangeset::load_by_bug( $p_bug_id, true );
		$t_repos = SourceRepo::load_by_changesets( $t_changesets );

		if ( count( $t_changesets ) < 1 ) {
			return;
		}

		collapse_open( 'Source' );
		?>
<br/>
<table class="width100" cellspacing="1">

<tr>
	<td class="form-title"><?php collapse_icon( 'Source' ); echo plugin_lang_get( 'related_changesets', 'Source' ) ?></td>
</tr>

		<?php
		foreach ( $t_changesets as $t_changeset ) {
			$t_repo = $t_repos[$t_changeset->repo_id];
			$t_css = helper_alternate_class();
			?>

<tr class="row-1">
<td class="category" width="25%" rowspan="<?php echo count( $t_changeset->files ) + 1 ?>">
	<?php echo string_display( $t_repo->name . ': ' . event_signal( 'EVENT_SOURCE_SHOW_CHANGESET', array( $t_repo, $t_changeset ) ) ) ?>
	<br/><span class="small"><?php echo plugin_lang_get( 'timestamp', 'Source' ), ': ', string_display_line( $t_changeset->timestamp ) ?></span>
	<br/><span class="small"><?php echo plugin_lang_get( 'author', 'Source' ), ': ', string_display_line( $t_changeset->author ) ?></span>
	<br/><span class="small"><?php print_bracket_link( event_signal( 'EVENT_SOURCE_URL_REPO', array( $t_repo, $t_changeset ) ), plugin_lang_get( 'browse', 'Source' ) ) ?>
		<?php print_bracket_link( event_signal( 'EVENT_SOURCE_URL_CHANGESET', array( $t_repo, $t_changeset ) ), plugin_lang_get( 'changeset', 'Source' ) ) ?></span>
</td>
<td colspan="2"><?php echo string_display_links( $t_changeset->message ) ?></td>
</tr>

		<?php foreach ( $t_changeset->files as $t_file ) { ?>
<tr class="row-2">
<td><?php echo string_display_line( event_signal( 'EVENT_SOURCE_SHOW_FILE', array( $t_repo, $t_changeset, $t_file ) ) ) ?></td>
<td class="center" width="15%">
	<?php print_bracket_link( event_signal( 'EVENT_SOURCE_URL_FILE_DIFF', array( $t_repo, $t_changeset, $t_file ) ), plugin_lang_get( 'diff', 'Source' ) ) ?>
	<?php print_bracket_link( event_signal( 'EVENT_SOURCE_URL_FILE', array( $t_repo, $t_changeset, $t_file ) ), plugin_lang_get( 'file', 'Source' ) ) ?>
</td>
</tr>
		<?php } ?>

<tr><td class="spacer"></td></tr>

		<?php } ?>
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
