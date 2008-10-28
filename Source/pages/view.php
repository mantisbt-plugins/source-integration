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

access_ensure_global_level( plugin_config_get( 'view_threshold' ) );
$t_can_update = access_has_project_level( plugin_config_get( 'update_threshold' ) );

$f_changeset_id = gpc_get_int( 'id' );
$f_offset = gpc_get_int( 'offset', 0 );

$t_changeset = SourceChangeset::load( $f_changeset_id );
$t_changeset->load_files();
$t_changeset->load_bugs();
bug_cache_array_rows( $t_changeset->bugs );

$t_repos = SourceRepo::load_by_changesets( $t_changeset );
if ( count( $t_repos ) < 1 ) {
	trigger_error( ERROR_GENERIC, ERROR );
}

$t_repo = array_shift( $t_repos );
$t_repo->load_branches();

if ( $t_changeset->parent ) {
	$t_changeset_parent = SourceChangeset::load_by_revision( $t_repo, $t_changeset->parent );
} else {
	$t_changeset_parent = null;
}

$t_type = SourceType($t_repo->type);

$t_use_porting = plugin_config_get( 'enable_porting' );

$t_columns =
	( $t_use_porting ? 1 : 0 ) +
	4;

$t_update_form = $t_use_porting || false;

html_page_top1( plugin_lang_get( 'title' ) );
html_page_top2();
?>

<br/>
<?php if ( $t_update_form ) { ?>
<form action="<?php echo plugin_page( 'update' ) ?>" method="post">
<input type="hidden" name="id" value="<?php echo $t_changeset->id ?>"/>
<?php echo form_security_field( 'plugin_Source_update' ) ?>
<?php } ?>
<table class="<?php echo $t_columns > 4 ? 'width90' : 'width75' ?>" cellspacing="1" align="center">

<tr>
<td class="form-title" colspan="<?php echo $t_columns - 2 ?>"><?php echo string_display_line( $t_repo->name ), ': ', event_signal( 'EVENT_SOURCE_SHOW_CHANGESET', array( $t_repo, $t_changeset ) ) ?></td>
<td class="right" colspan="2">
<?php
	if ( $t_url = event_signal( 'EVENT_SOURCE_URL_CHANGESET', array( $t_repo, $t_changeset ) ) ) {
		print_bracket_link( $t_url, plugin_lang_get( 'diff', 'Source' ) );
	}
	print_bracket_link( plugin_page( 'list' ) . '&id=' . $t_repo->id . '&offset=' . $f_offset, "Back to Repository" );
?>
</td>
<tr>

<tr class="row-category">
<td><?php echo plugin_lang_get( 'author' ) ?></td>
<td><?php echo plugin_lang_get( 'branch' ) ?></td>
<td><?php echo plugin_lang_get( 'timestamp' ) ?></td>
<td><?php echo plugin_lang_get( 'parent' ) ?></td>
<?php if ( $t_use_porting ) { ?>
<td><?php echo plugin_lang_get( 'ported' ) ?></td>
<?php } ?>
</tr>

<tr <?php echo helper_alternate_class() ?>>
<td class="center"><?php echo string_display_line( $t_changeset->author ),
	( $t_changeset->author_email ? '<br/>' . $t_changeset->author_email : '' ) ?></td>
<td class="center"><?php echo string_display_line( $t_changeset->branch ) ?></td>
<td class="center"><?php echo string_display_line( $t_changeset->timestamp ) ?></td>
<td class="center"><?php if ( $t_changeset_parent ) { print_link( plugin_page( 'view' ) . '&id=' . $t_changeset_parent->id, event_signal( 'EVENT_SOURCE_SHOW_CHANGESET', array( $t_repo, $t_changeset_parent ) ) ); } ?></td>
<?php if ( $t_use_porting ) { ?>
<td class="center">
<select name="ported">
<?php
	echo '<option value=""',
		( $t_changeset->ported == '' ? ' selected="selected"' : '' ),
		'>', plugin_lang_get( 'pending' ), '</option>',
		'<option value="0"',
		( $t_changeset->ported == '0' ? ' selected="selected"' : '' ),
		'>', plugin_lang_get( 'na' ), '</option>',
		'<option value="">--</option>';

	foreach( $t_repo->branches as $t_branch ) {
		if ( $t_branch == $t_changeset->branch ) { continue; }

		echo '<option value="', $t_branch, '"',
			( $t_changeset->ported == $t_branch ? ' selected="selected"' : '' ),
			'>', $t_branch, '</option>';
	}
?>
</select>
</td>
<?php } ?>
</tr>

<?php if ( $t_update_form ) { ?>
<tr>
<td colspan="4"></td>
<td colspan="<?php echo $t_columns-4 ?>" class="center"><input type="submit" value="<?php echo plugin_lang_get( 'update' ) ?>"/></td>
</tr>
</form>
<?php } ?>

<?php if ( count( $t_changeset->bugs ) > 0 ) { ?>
<tr><td class="spacer"></td></tr>

<tr <?php echo helper_alternate_class() ?>>
<td class="category" rowspan="<?php echo ( count( $t_changeset->bugs ) + ( $t_can_update ? 1 : 0 ) ) ?>">
	<?php echo plugin_lang_get( 'affected_issues' ) ?>
</td>

<?php
$t_first = true;
foreach ( $t_changeset->bugs as $t_bug_id ) {
	$t_bug = bug_get( $t_bug_id );
	echo ( $t_first ? '' : '<tr ' . helper_alternate_class() . '>' );
?>
<td colspan="<?php echo $t_columns-( $t_can_update ? 2 : 1 ) ?>"><?php echo '<a href="view.php?id=', $t_bug_id, '">', bug_format_id( $t_bug_id ), '</a>: ', string_display_line( $t_bug->summary ) ?></td>
<?php if ( $t_can_update ) { ?>
<td class="center"><?php print_bracket_link( plugin_page( 'detach' ) . '&id=' . $t_changeset->id . '&bug_id=' . $t_bug_id . form_security_param( 'plugin_Source_detach' ), plugin_lang_get( 'detach' ) ) ?>
<?php } ?>
</tr>

<?php $t_first = false; } }
	if ( $t_can_update ) { ?>
	<tr <?php echo helper_alternate_class() ?>><td colspan="<?php echo $t_columns-1 ?>">
<form action="<?php echo plugin_page( 'attach' )  ?>" method="post">
<?php echo form_security_field( 'plugin_Source_attach' ) ?>
<input type="hidden" name="id" value="<?php echo $t_changeset->id ?>"/>
<?php echo plugin_lang_get( 'attach_to_issue' ) ?> <input name="bug_ids" size="15"/>
<input type="submit" value="<?php echo plugin_lang_get( 'attach' ) ?>"/>
</form>
</td></tr>
<?php } ?>

<tr><td class="spacer"></td></tr>

<tr <?php echo helper_alternate_class() ?>>
<td class="category" rowspan="<?php echo count( $t_changeset->files ) + 1 ?>">
	<?php echo "Changeset" ?>
</td>
<td colspan="<?php echo $t_columns-1 ?>"><?php echo string_display_links( $t_changeset->message ) ?></td>
</tr>

<?php foreach ( $t_changeset->files as $t_file ) { ?>
<tr <?php echo helper_alternate_class() ?>>
<td class="small mono" colspan="<?php echo $t_columns-2 ?>"><?php echo string_display_line( event_signal( 'EVENT_SOURCE_SHOW_FILE', array( $t_repo, $t_changeset, $t_file ) ) ) ?></td>
<td class="center"><span class="small-links">
	<?php print_bracket_link( event_signal( 'EVENT_SOURCE_URL_FILE_DIFF', array( $t_repo, $t_changeset, $t_file ) ), plugin_lang_get( 'diff', 'Source' ) ) ?>
	<?php print_bracket_link( event_signal( 'EVENT_SOURCE_URL_FILE', array( $t_repo, $t_changeset, $t_file ) ), plugin_lang_get( 'file', 'Source' ) ) ?>
</span></td>
</tr>

<?php } ?>

</table>

<?php
html_page_bottom1( __FILE__ );

