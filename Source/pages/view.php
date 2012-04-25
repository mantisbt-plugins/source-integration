<?php

# Copyright (c) 2012 John Reese
# Licensed under the MIT license

access_ensure_global_level( plugin_config_get( 'view_threshold' ) );
$t_can_update = access_has_project_level( plugin_config_get( 'update_threshold' ) );

require_once( config_get( 'plugin_path' ) . 'Source/Source.ViewAPI.php' );

$f_changeset_id = gpc_get_int( 'id' );
$f_offset = gpc_get_int( 'offset', 0 );

$t_changeset = SourceChangeset::load( $f_changeset_id );
$t_changeset->load_files();
$t_changeset->load_bugs();
bug_cache_array_rows( $t_changeset->bugs );

$t_bug_rows = array();
foreach( $t_changeset->bugs as $t_bug_id ) {
	$t_bug_row = bug_cache_row( $t_bug_id, false );
	if ( false === $t_bug_row ) { continue; }

	$t_bug_rows[$t_bug_id] = $t_bug_row;
}
$t_affected_rowspan = count( $t_bug_rows ) + ( $t_can_update ? 1 : 0 );

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

$t_vcs = SourceVCS::repo( $t_repo );
$t_type = SourceType($t_repo->type);

$t_use_porting = plugin_config_get( 'enable_porting' );

$t_columns =
	( $t_use_porting ? 1 : 0 ) +
	5;

$t_update_form = $t_use_porting && $t_can_update;

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
<td class="form-title" colspan="<?php echo $t_columns - 2 ?>"><?php echo string_display_line( $t_repo->name ), ': ', $t_vcs->show_changeset( $t_repo, $t_changeset ) ?></td>
<td class="right" colspan="2">
<?php
	if ( $t_url = $t_vcs->url_changeset( $t_repo, $t_changeset ) ) {
		print_bracket_link( $t_url, plugin_lang_get( 'diff', 'Source' ) );
	}
	print_bracket_link( plugin_page( 'list' ) . '&id=' . $t_repo->id . '&offset=' . $f_offset, plugin_lang_get( 'back_repo' ) );
?>
</td>
<tr>

<tr class="row-category">
<td><?php echo plugin_lang_get( 'author' ) ?></td>
<td><?php echo plugin_lang_get( 'committer' ) ?></td>
<td><?php echo plugin_lang_get( 'branch' ) ?></td>
<td><?php echo plugin_lang_get( 'timestamp' ) ?></td>
<td><?php echo plugin_lang_get( 'parent' ) ?></td>
<?php if ( $t_use_porting ) { ?>
<td><?php echo plugin_lang_get( 'ported' ) ?></td>
<?php } ?>
</tr>

<tr <?php echo helper_alternate_class() ?>>
<td class="center"><?php Source_View_Author( $t_changeset ) ?></td>
<td class="center"><?php Source_View_Committer( $t_changeset ) ?></td>
<td class="center"><?php echo string_display_line( $t_changeset->branch ) ?></td>
<td class="center"><?php echo string_display_line( $t_changeset->timestamp ) ?></td>
<td class="center"><?php if ( $t_changeset_parent ) { print_link( plugin_page( 'view' ) . '&id=' . $t_changeset_parent->id, $t_vcs->show_changeset( $t_repo, $t_changeset_parent ) ); } ?></td>
<?php if ( $t_use_porting ) { ?>
<td class="center">
<?php if ( $t_update_form ) { ?>
<select name="ported">
<option value="" <?php echo check_selected( "", $t_changeset->ported ) ?>><?php echo plugin_lang_get( 'pending' ) ?></option>
<option value="0" <?php echo check_selected( "0", $t_changeset->ported ) ?>><?php echo plugin_lang_get( 'na' ) ?></option>
<option value="">--</option>
<?php foreach( $t_repo->branches as $t_branch ) { if ( $t_branch == $t_changeset->branch ) { continue; } ?>
<option value="<?php echo string_attribute( $t_branch ) ?>" <?php echo check_selected( $t_branch, $t_changeset->ported ) ?>><?php echo string_display_line( $t_branch ) ?></option>
<?php } ?>
</select>
<?php } else {
	echo $t_changeset->ported == "0" ? plugin_lang_get( 'na' ) : $t_changeset->ported == "" ? plugin_lang_get( 'pending' ) : string_display_line( $t_changeset->ported );
} ?>
</td>
<?php } ?>
</tr>

<?php if ( $t_update_form ) { ?>
<tr>
<td colspan="<?php echo $t_columns-1 ?>"></td>
<td class="center"><input type="submit" value="<?php echo plugin_lang_get( 'update' ) ?>"/></td>
</tr>
</form>
<?php } ?>

<?php if ( $t_affected_rowspan > 0 ) { ?>
<tr><td class="spacer"></td></tr>

<tr <?php echo helper_alternate_class() ?>>
<td class="category" rowspan="<?php echo $t_affected_rowspan ?>">
	<?php echo plugin_lang_get( 'affected_issues' ) ?>
</td>
<?php } ?>

<?php
$t_first = true;
foreach ( $t_bug_rows as $t_bug_id => $t_bug_row ) {
	echo ( $t_first ? '' : '<tr ' . helper_alternate_class() . '>' );
?>
<td colspan="<?php echo $t_columns-( $t_can_update ? 2 : 1 ) ?>"><?php echo '<a href="view.php?id=', $t_bug_id, '">', bug_format_id( $t_bug_id ), '</a>: ', string_display_line( $t_bug_row['summary'] ) ?></td>
<?php if ( $t_can_update ) { ?>
<td class="center"><span class="small-links"><?php print_bracket_link( plugin_page( 'detach' ) . '&id=' . $t_changeset->id . '&bug_id=' . $t_bug_id . form_security_param( 'plugin_Source_detach' ), plugin_lang_get( 'detach' ) ) ?></span>
<?php } ?>
</tr>

<?php
	$t_first = false;
}
if ( $t_can_update ) {
	if ( !$t_first ) { ?>
<tr <?php echo helper_alternate_class() ?>>
<?php } ?>
<td colspan="<?php echo $t_columns-1 ?>">
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
	<?php echo plugin_lang_get( 'changeset' ) ?>
</td>
<td colspan="<?php echo $t_columns-1 ?>"><?php echo string_display_links( $t_changeset->message ) ?></td>
</tr>

<?php foreach ( $t_changeset->files as $t_file ) { ?>
<tr <?php echo helper_alternate_class() ?>>
<td class="small mono" colspan="<?php echo $t_columns-2 ?>"><?php echo string_display_line( $t_vcs->show_file( $t_repo, $t_changeset, $t_file ) ) ?></td>
<td class="center"><span class="small-links">
	<?php print_bracket_link( $t_vcs->url_diff( $t_repo, $t_changeset, $t_file ), plugin_lang_get( 'diff', 'Source' ) ) ?>
	<?php print_bracket_link( $t_vcs->url_file( $t_repo, $t_changeset, $t_file ), plugin_lang_get( 'file', 'Source' ) ) ?>
</span></td>
</tr>

<?php } ?>

<?php if ( $t_can_update ) { ?>
<tr>
<td class="center" colspan="<?php echo $t_columns ?>">
<form action="<?php echo helper_mantis_url( 'plugin.php' ) ?>" method="get">
<input type="hidden" name="page" value="Source/edit_page"/>
<input type="hidden" name="id" value="<?php echo $t_changeset->id ?>"/>
<input type="submit" value="<?php echo plugin_lang_get( 'edit' ) ?>"/>
</form>
</td>
</tr>
<?php } ?>

</table>

<?php
html_page_bottom1( __FILE__ );

