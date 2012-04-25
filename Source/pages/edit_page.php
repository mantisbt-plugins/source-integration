<?php

# Copyright (c) 2012 John Reese
# Licensed under the MIT license

access_ensure_global_level( plugin_config_get( 'update_threshold' ) );
require_once( config_get( 'plugin_path' ) . 'Source/Source.ViewAPI.php' );

$f_changeset_id = gpc_get_int( 'id' );
$f_offset = gpc_get_int( 'offset', 0 );

$t_changeset = SourceChangeset::load( $f_changeset_id );

$t_repos = SourceRepo::load_by_changesets( $t_changeset );
if ( count( $t_repos ) < 1 ) {
	trigger_error( ERROR_GENERIC, ERROR );
}

$t_repo = array_shift( $t_repos );
$t_repo->load_branches();
$t_vcs = SourceVCS::repo( $t_repo );

$t_use_porting = plugin_config_get( 'enable_porting' );

html_page_top1( plugin_lang_get( 'title' ) );
html_page_top2();
?>

<form action="<?php echo plugin_page( 'edit' ), '&id=', $t_changeset->id ?>" method="post">
<?php echo form_security_field( 'plugin_Source_edit' ) ?>
<input type="hidden" name="offset" value="<?php echo $f_offset ?>"/>

<br/>
<table class="<?php echo $t_columns > 4 ? 'width90' : 'width75' ?>" cellspacing="1" align="center">

<tr>
<td class="form-title" colspan="2"><?php echo string_display_line( $t_repo->name ), ': ', $t_vcs->show_changeset( $t_repo, $t_changeset ) ?></td>
<td class="right">
<?php print_bracket_link( plugin_page( 'view' ) . '&id=' . $t_changeset->id . '&offset=' . $f_offset, plugin_lang_get( 'back_changeset' ) ); ?>
</td>
<tr>

<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'author' ) ?></td>
<td colspan="2"><select name="user_id">
<option value="0" <?php echo check_selected( 0, $t_changeset->user_id ) ?>>--</option>
<?php print_user_option_list( $t_changeset->user_id ) ?>
</select></td>
</tr>

<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'committer' ) ?></td>
<td colspan="2"><select name="committer_id">
<option value="0" <?php echo check_selected( 0, $t_changeset->committer_id ) ?>>--</option>
<?php print_user_option_list( $t_changeset->committer_id ) ?>
</select></td>
</tr>

<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'branch' ) ?></td>
<td colspan="2"><select name="branch">
<?php if ( $t_changeset->branch == "" ) { ?>
<option value="" <?php echo check_selected( "", $t_changeset->branch ) ?>>--</option>
<?php } foreach( $t_repo->branches as $t_branch ) { ?>
<option value="<?php echo string_attribute( $t_branch ) ?>" <?php echo check_selected( $t_branch, $t_changeset->branch ) ?>><?php echo string_display_line( $t_branch ) ?></option>
<?php } ?>
</select></td>
</tr>

<tr class="spacer">
<td width="25%"></td>
<td width="25%"></td>
<td width="50%"></td>
</tr>

<?php if ( $t_use_porting ) { ?>
<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'ported' ) ?></td>
<td colspan="2"><select name="ported">
<option value="" <?php echo check_selected( "", $t_changeset->ported ) ?>><?php echo plugin_lang_get( 'pending' ) ?></option>
<option value="0" <?php echo check_selected( "0", $t_changeset->ported ) ?>><?php echo plugin_lang_get( 'na' ) ?></option>
<option value="">--</option>
<?php foreach( $t_repo->branches as $t_branch ) { if ( $t_branch == $t_changeset->branch ) { continue; } ?>
<option value="<?php echo string_attribute( $t_branch ) ?>" <?php echo check_selected( $t_branch, $t_changeset->ported ) ?>><?php echo string_display_line( $t_branch ) ?></option>
<?php } ?>
</select></td>
</tr>

<tr class="spacer"><td></td><td></td><td></td></tr>
<?php } ?>

<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'message' ) ?></td>
<td colspan="2"><textarea name="message" cols="80" rows="8"><?php echo string_textarea( $t_changeset->message ) ?></textarea></td>
</tr>

<tr>
<td class="center" colspan="3"><input type="submit" value="<?php echo plugin_lang_get( 'edit' ) ?>"/></td>
</tr>

</table>
</form>

<?php
html_page_bottom1( __FILE__ );

