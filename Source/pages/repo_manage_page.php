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

access_ensure_global_level( plugin_config_get( 'manage_threshold' ) );

$f_repo_id = gpc_get_int( 'id' );

$t_repo = SourceRepo::load( $f_repo_id );
$t_type = SourceType($t_repo->type);

$t_mappings = $t_repo->load_mappings();

function display_strategies( $p_type=null ) {
	if ( is_null( $p_type ) ) {
		echo '<option>', plugin_lang_get( 'select_one' ), '</option>';
	}

	echo '<option value="', SOURCE_EXPLICIT, '"', ( $p_type == SOURCE_EXPLICIT ? ' selected="selected"' : '' ),
		'>', plugin_lang_get( 'mapping_explicit' ), '</option>',
		'<option value="', SOURCE_NEAR, '"', ( $p_type == SOURCE_NEAR ? ' selected="selected"' : '' ),
		'>', plugin_lang_get( 'mapping_near' ), '</option>',
		'<option value="', SOURCE_FAR, '"', ( $p_type == SOURCE_FAR ? ' selected="selected"' : '' ),
		'>', plugin_lang_get( 'mapping_far' ), '</option>',
		'<option value="', SOURCE_FIRST, '"', ( $p_type == SOURCE_FIRST ? ' selected="selected"' : '' ),
		'>', plugin_lang_get( 'mapping_first' ), '</option>',
		'<option value="', SOURCE_LAST, '"', ( $p_type == SOURCE_LAST ? ' selected="selected"' : '' ),
		'>', plugin_lang_get( 'mapping_last' ), '</option>';
}

html_page_top1( plugin_lang_get( 'title' ) );
html_page_top2();
?>

<br/>
<table class="width75" align="center" cellspacing="1">

<tr>
<td class="form-title" colspan="2"><?php echo plugin_lang_get( 'manage' ), ' ', plugin_lang_get( 'repository' ) ?></td>
<td class="right">
<?php
	print_bracket_link( plugin_page( 'list' ) . "&id=$f_repo_id", plugin_lang_get( 'browse' ) );
	print_bracket_link( plugin_page( 'index' ), plugin_lang_get( 'back' ) );
?>
</td>
</tr>

<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'name' ) ?></td>
<td colspan="2"><?php echo string_display( $t_repo->name ) ?></td>
</tr>

<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'type' ) ?></td>
<td colspan="2"><?php echo string_display( $t_type ) ?></td>
</tr>

<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'url' ) ?></td>
<td colspan="2"><?php echo string_display( $t_repo->url ) ?></td>
</tr>

<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'info' ) ?></td>
<td colspan="2"><pre><?php
foreach( $t_repo->info as $t_key => $t_value ) {
	echo string_display( $t_key . ' => ' );
	var_dump( $t_value );
}
?></pre></td>
</tr>

<tr>
<td width="30%"></td>
<td width="20%"></td>
<td width="50%"></td>
</tr>

<tr>
<td colspan="2">
<form action="<?php echo plugin_page( 'repo_update_page' ) . '&id=' . $t_repo->id ?>" method="post">
	<input type="submit" value="<?php echo plugin_lang_get( 'update' ), ' ', plugin_lang_get( 'repository' ) ?>"/>
</form>
<form action="<?php echo plugin_page( 'repo_delete' ) . '&id=' . $t_repo->id ?>" method="post">
	<?php echo form_security_field( 'plugin_Source_repo_delete' ) ?>
	<input type="submit" value="<?php echo plugin_lang_get( 'delete' ), ' ', plugin_lang_get( 'repository' ) ?>"/>
</form>
</td>
<td class="right">
<form action="<?php echo plugin_page( 'repo_import_latest' ) . '&id=' . $t_repo->id ?>" method="post">
	<?php echo form_security_field( 'plugin_Source_repo_import_latest' ) ?>
	<input type="submit" value="<?php echo plugin_lang_get( 'import_latest' ) ?>"/>
</form>
<form action="<?php echo plugin_page( 'repo_import_full' ) . '&id=' . $t_repo->id ?>" method="post">
	<?php echo form_security_field( 'plugin_Source_repo_import_full' ) ?>
	<input type="submit" value="<?php echo plugin_lang_get( 'import_full' ) ?>"/>
</form>
</td>
</tr>

</table>
</form>

<?php if ( plugin_config_get( 'enable_mapping' ) ) { ?>
<br/>
<form action="<?php echo plugin_page( 'repo_update_mappings' ) . '&id=' . $t_repo->id ?>" method="post">
<table class="width75" align="center" cellspacing="1">

<tr>
<td class="form-title"><?php echo plugin_lang_get( 'branch_mapping' ) ?></td>
</tr>

<tr class="row-category">
<td><?php echo plugin_lang_get( 'branch' ) ?></td>
<td><?php echo plugin_lang_get( 'mapping_strategy' ) ?></td>
<td><?php echo plugin_lang_get( 'mapping_version' ), ' ', plugin_lang_get( 'mapping_version_info' ) ?></td>
<td><?php echo plugin_lang_get( 'mapping_regex' ), ' ', plugin_lang_get( 'mapping_regex_info' ) ?></td>
</tr>

<?php foreach( $t_mappings as $t_mapping ) { ?>

<tr <?php echo helper_alternate_class() ?>>
<td><input name="<?php echo $t_mapping->branch ?>_branch" value="<?php echo string_attribute( $t_mapping->branch ) ?>" size="12" maxlength="128"/></td>
<td><select name="<?php echo $t_mapping->branch ?>_type"><?php display_strategies( $t_mapping->type ) ?></select></td>
<td><select name="<?php echo $t_mapping->branch ?>_version"><?php print_version_option_list( $t_mapping->version, ALL_PROJECTS, false, true, true ) ?></select></td>
<td><input name="<?php echo $t_mapping->branch ?>_regex" value="<?php echo string_attribute( $t_mapping->regex ) ?>" size="18" maxlength="128"/></td>
</tr>
<?php } ?>

<tr><td></td></tr>

<tr <?php echo helper_alternate_class() ?>>
<td><input name="_name" size="12" maxlength="128"/></td>
<td><select name=_type"><?php display_strategies(); ?></select></td>
<td><select name="_version"><?php print_version_option_list( '', ALL_PROJECTS, false, true, true ) ?></td>
<td><input name="_regex" size="18" maxlength="128"/></td>
</tr>

<tr>
<td class="center" colspan="4"><input type="submit" value="<?php echo plugin_lang_get( 'mapping_update' ) ?>"/></td>
</tr>

</table>
</form>

<?php } ?>

<?php
html_page_bottom1( __FILE__ );

