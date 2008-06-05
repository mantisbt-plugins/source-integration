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

html_page_top1( plugin_lang_get( 'title' ) );
html_page_top2();
?>

<br/>
<table class="width75" align="center" cellspacing="1">

<tr>
<td class="form-title" colspan="2"><?php echo plugin_lang_get( 'manage' ), ' ', plugin_lang_get( 'repository' ) ?></td>
<td class="right"><?php print_bracket_link( plugin_page( 'index' ), plugin_lang_get( 'back' ) ) ?></td>
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
<form action="<?php echo plugin_page( 'repo_update_page' ) . '&id=' . $t_repo->id ?>" method="post"><input type="submit" value="<?php echo plugin_lang_get( 'update' ), ' ', plugin_lang_get( 'repository' ) ?>"/></form>
<form action="<?php echo plugin_page( 'repo_delete' ) . '&id=' . $t_repo->id ?>" method="post"><input type="submit" value="<?php echo plugin_lang_get( 'delete' ), ' ', plugin_lang_get( 'repository' ) ?>"/></form>
</td>
<td class="right">
<form action="<?php echo plugin_page( 'repo_import_latest' ) . '&id=' . $t_repo->id ?>" method="post"><input type="submit" value="<?php echo plugin_lang_get( 'import_latest' ) ?>"/></form>
<form action="<?php echo plugin_page( 'repo_import_full' ) . '&id=' . $t_repo->id ?>" method="post"><input type="submit" value="<?php echo plugin_lang_get( 'import_full' ) ?>"/></form>
</td>
</tr>

</table>
</form>

<?php
html_page_bottom1( __FILE__ );

