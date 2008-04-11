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
$t_can_manage = access_has_global_level( plugin_config_get( 'manage_threshold' ) );

$t_types = SourceTypes();
$t_repos = SourceRepo::load_all();

html_page_top1( lang_get( 'plugin_Source_title' ) );
html_page_top2();
?>

<br/>
<table class="width75" align="center" cellspacing="1">

<tr>
<td class="form-title" colspan="2"><?php echo lang_get( 'plugin_Source_repositories' ) ?></td>
</tr>

<tr class="row-category">
<td width="30%"><?php echo lang_get( 'plugin_Source_repository' ) ?></td>
<td width="15%"><?php echo lang_get( 'plugin_Source_type' ) ?></td>
<td width="10%"><?php echo lang_get( 'plugin_Source_changesets' ) ?></td>
<td width="10%"><?php echo lang_get( 'plugin_Source_files' ) ?></td>
<td width="10%"><?php echo lang_get( 'plugin_Source_issues' ) ?></td>
<td width="25%"><?php echo lang_get( 'plugin_Source_actions' ) ?></td>
</tr>

<?php foreach( $t_repos as $t_repo ) {
	$t_stats = $t_repo->stats(); ?>
<tr <?php echo helper_alternate_class() ?>>
<td><?php echo string_display( $t_repo->name ) ?></td>
<td class="center"><?php echo string_display( SourceType( $t_repo->type ) ) ?></td>
<td class="right"><?php echo $t_stats['changesets'] ?></td>
<td class="right"><?php echo $t_stats['files'] ?></td>
<td class="right"><?php echo $t_stats['bugs'] ?></td>
<td class="center">
<?php 
	print_bracket_link( event_signal( 'EVENT_SOURCE_URL_REPO', $t_repo ), lang_get( 'plugin_Source_browse' ) );
	if ( $t_can_manage ) {
		print_bracket_link( plugin_page( 'repo_manage_page' ) . '&id=' . $t_repo->id, lang_get( 'plugin_Source_manage' ) );
	}
?>
</td>
</tr>
<?php } ?>

</table>

<br/>
<form action="<?php echo plugin_page( 'repo_create.php' ) ?>" method="post">
<table class="width50" align="center" cellspacing="1">

<tr>
<td class="form-title" colspan="2"><?php echo lang_get( 'plugin_Source_create' ), ' ', lang_get( 'plugin_Source_repository' ) ?></td>
</tr>

<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo lang_get( 'plugin_Source_name' ) ?></td>
<td><input name="repo_name" maxlength="128" size="40"/></td>
</tr>

<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo lang_get( 'plugin_Source_type' ) ?></td>
<td>
<select name="repo_type">
	<option value=""><?php echo lang_get( 'plugin_Source_select_one' ) ?></option>
<?php foreach( $t_types as $t_type => $t_type_name ) { ?>
	<option value="<?php echo $t_type ?>"><?php echo string_display( $t_type_name ) ?></option>
<?php } ?>
</select>
</td>
</tr>

<tr>
<td class="center" colspan="2"><input type="submit" value="<?php echo lang_get( 'plugin_Source_create' ), ' ', lang_get( 'plugin_Source_repository' ) ?>"/></td>
</tr>

</table>
</form>

<?php
html_page_bottom1( __FILE__ );

